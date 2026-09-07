<?php

namespace App\Domain\Content\Actions;

use App\Domain\Publishing\EditorialMutationContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class ReorderContent
{
    public function __construct(private readonly EditorialMutationContext $context) {}

    public function __invoke(Model $content, int $position): Model
    {
        if ($position < 0 || ! array_key_exists('position', $content->getAttributes())) {
            throw new \InvalidArgumentException('The content position must be a nonnegative integer.');
        }

        return DB::transaction(function () use ($content, $position): Model {
            $modelClass = $content::class;
            /** @var Model $locked */
            $locked = $modelClass::query()->lockForUpdate()->findOrFail($content->getKey());

            return $this->context->run(function () use ($locked, $position): Model {
                $locked->position = $position;
                $locked->save();

                return $locked->fresh();
            });
        });
    }
}
