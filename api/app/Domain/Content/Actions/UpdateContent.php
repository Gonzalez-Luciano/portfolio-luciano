<?php

namespace App\Domain\Content\Actions;

use App\Domain\Publishing\EditorialMutationContext;
use App\Domain\Publishing\PublicationValidator;
use App\Enums\PublicationStatus;
use App\Models\CvDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class UpdateContent
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
    ) {}

    /** @param array<string, mixed> $attributes */
    public function __invoke(Model $content, array $attributes): Model
    {
        if ($content instanceof CvDocument && array_key_exists('locale', $attributes)) {
            throw new \LogicException('The CV locale is immutable.');
        }

        if (array_intersect(array_keys($attributes), self::PROTECTED_ATTRIBUTES) !== []) {
            throw new \LogicException('Sensitive editorial mutation must be performed through its dedicated domain action.');
        }

        return DB::transaction(function () use ($content, $attributes): Model {
            $modelClass = $content::class;
            /** @var Model $locked */
            $locked = $modelClass::query()->lockForUpdate()->findOrFail($content->getKey());
            $locked->fill($attributes);
            if ($locked->status === PublicationStatus::Published) {
                $this->validator->assertPublishable($locked);
            }

            return $this->context->run(function () use ($locked): Model {
                $locked->save();

                return $locked->fresh();
            });
        });
    }
}
