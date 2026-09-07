<?php

namespace App\Domain\Publishing;

use Closure;

final class EditorialMutationContext
{
    private int $depth = 0;

    private int $aggregateDepth = 0;

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

    /** @template T @param Closure(): T $callback @return T */
    public function runAggregate(Closure $callback): mixed
    {
        $this->aggregateDepth++;

        try {
            return $this->run($callback);
        } finally {
            $this->aggregateDepth--;
        }
    }

    public function isAggregateActive(): bool
    {
        return $this->aggregateDepth > 0;
    }
}
