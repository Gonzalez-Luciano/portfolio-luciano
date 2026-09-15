<?php

namespace App\Domain\Content\Actions;

use App\Domain\Publishing\EditorialMutationContext;
use App\Domain\Publishing\PublicationIssue;
use App\Domain\Publishing\PublicationValidator;
use App\Enums\PublicationStatus;
use App\Models\Technology;
use App\Support\PublicContentCache;
use App\Support\PublicContentDependencies;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Updates a WorkCase's or Project's plain attributes and its ordered
 * `technologies` pivot together, atomically, as one validated unit. Both
 * models share the exact same contextual technology-relation shape
 * (`belongsToMany(Technology::class)->withPivot('position')`), so this one
 * small, typed, single-purpose action serves both rather than duplicating
 * the same transaction/validation/cache-invalidation contract per entity.
 *
 * It is not a generic content-mutation abstraction: it knows nothing about
 * any other relation shape, and only ever replaces one specific relation on
 * one of two specific model types.
 *
 * Its transaction/context/invalidation ordering mirrors
 * `UpdateExperienceAggregate` (and, by extension, `UpdateOwnedAssetAltText`
 * / `AssetLifecycleService`): the whole mutation runs inside
 * `DB::transaction()`, the actual writes run inside
 * `EditorialMutationContext::run()` *nested inside* that transaction (never
 * the reverse), and the explicit `PublicContentCache::invalidate()` call is
 * registered with `DB::afterCommit()` so it only ever runs once the
 * transaction has actually committed and the mutation context has already
 * exited. Task 9 shipped a real bug from getting this backwards (running
 * `EditorialMutationContext::run()` with no surrounding transaction, which
 * let the deferred cache-invalidation event fire *while the context was
 * still active*, silently no-opping the guard's own invalidation); this
 * ordering is what prevents that here.
 */
final class UpdateContentWithTechnologies
{
    /** @var list<string> */
    private const PROTECTED_ATTRIBUTES = [
        'status', 'is_visible', 'published_at', 'key', 'key_locked',
        'photo_private_path', 'photo_public_path', 'photo_mime', 'photo_size', 'photo_alt_es', 'photo_alt_en',
        'icon_private_path', 'icon_public_path', 'icon_mime', 'icon_size', 'private_path', 'mime', 'size',
    ];

    public function __construct(
        private readonly EditorialMutationContext $context,
        private readonly PublicationValidator $validator,
        private readonly PublicContentCache $cache,
        private readonly PublicContentDependencies $dependencies,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<array{technology_id: int, position: int}>  $technologies
     */
    public function __invoke(Model $content, array $attributes, array $technologies): Model
    {
        if (! method_exists($content, 'technologies')) {
            throw new \InvalidArgumentException('This action only supports content with an ordered technologies() relation.');
        }

        if (array_intersect(array_keys($attributes), self::PROTECTED_ATTRIBUTES) !== []) {
            throw new \LogicException('Sensitive editorial mutation must be performed through its dedicated domain action.');
        }

        $endpoints = $this->dependencies->for($content);

        return DB::transaction(function () use ($content, $attributes, $technologies, $endpoints): Model {
            $modelClass = $content::class;
            /** @var Model $locked */
            $locked = $modelClass::query()->with('technologies')->lockForUpdate()->findOrFail($content->getKey());
            $technologyModels = $this->resolveTechnologies($technologies);
            $candidate = clone $locked;
            $candidate->fill($attributes);
            $candidate->setRelation('technologies', $technologyModels);
            if ($candidate->status === PublicationStatus::Published) {
                $this->validator->assertPublishable($candidate);
            }

            $updated = $this->context->run(function () use ($locked, $attributes, $technologies): Model {
                $locked->fill($attributes)->save();
                $locked->technologies()->sync(collect($technologies)->mapWithKeys(
                    static fn (array $technology): array => [(int) $technology['technology_id'] => ['position' => (int) $technology['position']]],
                )->all());

                return $locked->fresh('technologies');
            });

            DB::afterCommit(function () use ($endpoints): void {
                $this->cache->invalidate($endpoints);
            });

            return $updated;
        });
    }

    /**
     * Mirrors `UpdateExperienceAggregate::resolveTechnologies()`: the same
     * duplicate-detection and existence-check semantics, kept in the action
     * layer (not `PublicationValidator`, which only validates an entity's
     * own fields) since relation-shape validation belongs alongside the
     * relation it validates.
     *
     * @param  list<array{technology_id: int, position: int}>  $technologies
     */
    private function resolveTechnologies(array $technologies): Collection
    {
        $ids = array_map(static fn (array $technology): int => (int) $technology['technology_id'], $technologies);
        if (count($ids) !== count(array_unique($ids))) {
            $this->validator->assertIssues([
                new PublicationIssue('duplicate_relation', 'technologies', 'A technology can only be related once.'),
            ]);
        }

        $models = Technology::query()->whereKey($ids)->get()->keyBy('id');
        foreach ($ids as $index => $id) {
            if (! $models->has($id)) {
                $this->validator->assertIssues([
                    new PublicationIssue('invalid_relation', "technologies.{$index}.technology_id", 'The selected technology does not exist.'),
                ]);
            }
        }

        return new Collection(array_map(static fn (int $id): Technology => $models->get($id), $ids));
    }
}
