<?php

namespace Tests\Support;

use Illuminate\Cache\FileStore;
use Illuminate\Contracts\Cache\Lock;

final class RecordingLockStore extends FileStore
{
    /** @var list<string> */
    public array $events = [];

    public ?int $failFromForget = null;

    private int $forgetCalls = 0;

    public function lock($name, $seconds = 0, $owner = null): Lock
    {
        $this->events[] = "acquire:{$name}";

        return new RecordingLock(parent::lock($name, $seconds, $owner), $name, $this->events);
    }

    public function forget($key): bool
    {
        $this->forgetCalls++;
        $this->events[] = "forget:{$key}";

        if ($this->failFromForget !== null && $this->forgetCalls >= $this->failFromForget) {
            return false;
        }

        return parent::forget($key);
    }

    public function get($key): mixed
    {
        if ($this->failFromForget !== null && $this->forgetCalls >= $this->failFromForget) {
            return ['still-present'];
        }

        return parent::get($key);
    }
}
