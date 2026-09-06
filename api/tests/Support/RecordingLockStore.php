<?php

namespace Tests\Support;

use Illuminate\Cache\FileStore;
use Illuminate\Contracts\Cache\Lock;

final class RecordingLockStore extends FileStore
{
    /** @var list<string> */
    public array $events = [];

    public function lock($name, $seconds = 0, $owner = null): Lock
    {
        $this->events[] = "acquire:{$name}";

        return new RecordingLock(parent::lock($name, $seconds, $owner), $name, $this->events);
    }
}
