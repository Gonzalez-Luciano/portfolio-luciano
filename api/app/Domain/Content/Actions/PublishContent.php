<?php

namespace App\Domain\Content\Actions;

use App\Domain\Publishing\EditorialMutationContext;
use App\Domain\Publishing\PublicationValidator;
use App\Enums\PublicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class PublishContent
{
    public function __construct(
        private readonly EditorialMutationContext $context,
        private readonly PublicationValidator $validator,
    ) {}

    public function __invoke(Model $content): Model
    {
        if ($content->status !== PublicationStatus::Draft) {
            throw new \LogicException('Only draft content can be published.');
        }

        return DB::transaction(function () use ($content): Model {
            $modelClass = $content::class;
            /** @var Model $locked */
            $locked = $modelClass::query()->lockForUpdate()->findOrFail($content->getKey());
            if ($locked->status !== PublicationStatus::Draft) {
                throw new \LogicException('Only draft content can be published.');
            }
            $this->validator->assertPublishable($locked);

            return $this->context->run(function () use ($locked): Model {
                $attributes = [
                    'status' => PublicationStatus::Published,
                    'is_visible' => false,
                    'published_at' => now(),
                ];
                if (array_key_exists('key_locked', $locked->getAttributes())) {
                    $attributes['key_locked'] = true;
                }
                $locked->forceFill($attributes)->save();

                return $locked->fresh();
            });
        });
    }
}
