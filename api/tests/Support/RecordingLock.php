<?php

namespace Tests\Support;

use Illuminate\Contracts\Cache\Lock;

final class RecordingLock implements Lock
{
    /**
     * @param  list<string>  $events
     */
    public function __construct(private readonly Lock $lock, private readonly string $name, private array &$events) {}

    public function get($callback = null): mixed
    {
        return $this->lock->get($callback);
    }

    public function block($seconds, $callback = null): mixed
    {
        return $this->lock->block($seconds, $callback);
    }

    public function release(): bool
    {
        $this->events[] = "release:{$this->name}";

        return $this->lock->release();
    }

    public function owner(): string
    {
        return $this->lock->owner();
    }

    public function forceRelease(): void
    {
        $this->lock->forceRelease();
    }
}
