<?php

namespace App\Domain\Publishing;

use Closure;

final class EditorialMutationContext
{
    private int $depth = 0;

    /** @template T @param Closure(): T $callback @return T */
    public function run(Closure $callback): mixed
    {
        $this->depth++;

        try {
            return $callback();
        } finally {
            $this->depth--;
        }
    }

    public function isActive(): bool
    {
        return $this->depth > 0;
    }
}
