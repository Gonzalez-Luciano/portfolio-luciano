<?php

namespace App\Domain\Content\Actions;

use App\Domain\Publishing\EditorialMutationContext;
use App\Domain\Publishing\PublicationIssue;
use App\Domain\Publishing\PublicationValidator;
use App\Enums\PublicationStatus;
use App\Enums\PublicEndpoint;
use App\Models\Experience;
use App\Models\ExperienceHighlight;
use App\Models\Technology;
use App\Support\PublicContentCache;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class UpdateExperienceAggregate
{
    /** @var list<string> */
    private const PROTECTED_ATTRIBUTES = [
        'status', 'is_visible', 'published_at', 'key', 'key_locked',
        'photo_private_path', 'photo_public_path', 'photo_mime', 'photo_size', 'photo_alt_es', 'photo_alt_en',
        'image_private_path', 'image_public_path', 'image_mime', 'image_size', 'image_alt_es', 'image_alt_en',
        'icon_private_path', 'icon_public_path', 'icon_mime', 'icon_size', 'private_path', 'mime', 'size',
    ];

    public function __construct(
        private readonly EditorialMutationContext $context,
        private readonly PublicationValidator $validator,
        private readonly PublicContentCache $cache,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<array{content_es: mixed, content_en: mixed, position: int}>  $highlights
     * @param  list<array{technology_id: int, position: int}>  $technologies
     */
    public function __invoke(Experience $experience, array $attributes, array $highlights, array $technologies): Experience
    {
        if (array_intersect(array_keys($attributes), self::PROTECTED_ATTRIBUTES) !== []) {
            throw new \LogicException('Sensitive editorial mutation must be performed through its dedicated domain action.');
        }

        return DB::transaction(function () use ($experience, $attributes, $highlights, $technologies): Experience {
            /** @var Experience $locked */
            $locked = Experience::query()->with(['highlights', 'technologies'])->lockForUpdate()->findOrFail($experience->getKey());
            $technologyModels = $this->resolveTechnologies($technologies);
            $candidate = clone $locked;
            $candidate->fill($attributes);
            $candidate->setRelation('highlights', $this->proposedHighlights($highlights));
            $candidate->setRelation('technologies', $technologyModels);
            if ($candidate->status === PublicationStatus::Published) {
                $this->validator->assertPublishable($candidate);
            }

            $updated = $this->context->runAggregate(function () use ($locked, $attributes, $highlights, $technologies): Experience {
                $locked->fill($attributes)->save();
                $locked->highlights->each(static fn (ExperienceHighlight $highlight): bool => $highlight->delete());
                foreach ($highlights as $highlight) {
                    $locked->highlights()->create($highlight);
                }
                $locked->technologies()->sync(collect($technologies)->mapWithKeys(
                    static fn (array $technology): array => [(int) $technology['technology_id'] => ['position' => (int) $technology['position']]],
                )->all());

                return $locked->fresh(['highlights', 'technologies']);
            });

            DB::afterCommit(function (): void {
                $this->cache->invalidate([PublicEndpoint::Experiences]);
            });

            return $updated;
        });
    }

    /** @param list<array{content_es: mixed, content_en: mixed, position: int}> $highlights */
    private function proposedHighlights(array $highlights): Collection
    {
        return new Collection(array_map(static function (array $highlight): ExperienceHighlight {
            $model = new ExperienceHighlight;
            $model->forceFill($highlight);

            return $model;
        }, $highlights));
    }

    /** @param list<array{technology_id: int, position: int}> $technologies */
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
