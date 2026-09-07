<?php

namespace App\Domain\Content\Actions;

use App\Domain\Publishing\EditorialMutationContext;
use App\Domain\Publishing\PublicationValidator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class ChangePublicKey
{
    public function __construct(
        private readonly EditorialMutationContext $context,
        private readonly PublicationValidator $validator,
    ) {}

    public function __invoke(Model $content, string $key, bool $confirmed): Model
    {
        if (! array_key_exists('key_locked', $content->getAttributes())) {
            throw new \InvalidArgumentException('This content does not have a public key.');
        }
        if ((bool) $content->key_locked && ! $confirmed) {
            throw new \LogicException('A locked public key requires explicit confirmation.');
        }

        return DB::transaction(function () use ($content, $key, $confirmed): Model {
            $modelClass = $content::class;
            /** @var Model $locked */
            $locked = $modelClass::query()->lockForUpdate()->findOrFail($content->getKey());
            if ((bool) $locked->key_locked && ! $confirmed) {
                throw new \LogicException('A locked public key requires explicit confirmation.');
            }
            $locked->key = $key;
            $this->validator->assertKey($locked);

            return $this->context->run(function () use ($locked): Model {
                $locked->save();

                return $locked->fresh();
            });
        });
    }
}
