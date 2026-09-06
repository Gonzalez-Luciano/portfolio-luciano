<?php

namespace App\Support;

use App\Enums\PublicEndpoint;
use App\Enums\SupportedLocale;
use App\Exceptions\PublicContentUnavailable;
use Closure;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class PublicContentCache
{
    private const LOCK_LEASE_SECONDS = 60;

    private const PUBLIC_LOCK_WAIT_SECONDS = 5;

    private const MUTATION_LOCK_WAIT_SECONDS = 10;

    private const INVALIDATION_ATTEMPTS = 3;

    /**
     * @param  Closure(): array<mixed>  $resolver
     * @return array<mixed>
     */
    public function remember(SupportedLocale $locale, PublicEndpoint $endpoint, Closure $resolver): array
    {
        $key = $this->key($locale, $endpoint);
        $cached = Cache::get($key);

        if ($cached !== null) {
            return $this->dataArray($cached, $key);
        }

        $lock = $this->lock($locale, $endpoint);

        try {
            return $lock->block(self::PUBLIC_LOCK_WAIT_SECONDS, function () use ($key, $resolver): array {
                $cached = Cache::get($key);

                if ($cached !== null) {
                    return $this->dataArray($cached, $key);
                }

                $data = $this->dataArray($resolver(), $key);
                Cache::forever($key, $data);

                return $data;
            });
        } catch (LockTimeoutException) {
            throw new PublicContentUnavailable;
        }
    }

    /**
     * @param  list<PublicEndpoint>  $endpoints
     */
    public function invalidate(array $endpoints): void
    {
        foreach ($this->invalidationKeys($endpoints) as $key) {
            $this->forgetWithRetry($key);
        }
    }

    /**
     * @template T
     *
     * @param  list<PublicEndpoint>  $endpoints
     * @param  Closure(): T  $callback
     * @return T
     */
    public function withMutationLocks(array $endpoints, Closure $callback): mixed
    {
        $locks = [];

        try {
            foreach ($this->mutationLockKeys($endpoints) as $key) {
                $lock = $this->lockByKey($key);
                $lock->block(self::MUTATION_LOCK_WAIT_SECONDS);
                $locks[] = $lock;
            }

            return $callback();
        } catch (LockTimeoutException) {
            throw new PublicContentUnavailable;
        } finally {
            foreach (array_reverse($locks) as $lock) {
                $lock->release();
            }
        }
    }

    public function key(SupportedLocale $locale, PublicEndpoint $endpoint): string
    {
        return "public-content:v1:{$locale->value}:{$endpoint->value}";
    }

    public function lockKey(SupportedLocale $locale, PublicEndpoint $endpoint): string
    {
        return "public-content-rebuild:v1:{$locale->value}:{$endpoint->value}";
    }

    /**
     * @param  list<PublicEndpoint>  $endpoints
     * @return list<string>
     */
    private function invalidationKeys(array $endpoints): array
    {
        $keys = [];

        foreach ($this->uniqueEndpoints($endpoints) as $endpoint) {
            foreach (SupportedLocale::cases() as $locale) {
                $keys[] = $this->key($locale, $endpoint);
            }
        }

        sort($keys, SORT_STRING);

        return $keys;
    }

    /**
     * @param  list<PublicEndpoint>  $endpoints
     * @return list<string>
     */
    private function mutationLockKeys(array $endpoints): array
    {
        $keys = [];

        foreach ($this->uniqueEndpoints($endpoints) as $endpoint) {
            foreach (SupportedLocale::cases() as $locale) {
                $keys[] = $this->lockKey($locale, $endpoint);
            }
        }

        sort($keys, SORT_STRING);

        return $keys;
    }

    private function lock(SupportedLocale $locale, PublicEndpoint $endpoint): Lock
    {
        return $this->lockByKey($this->lockKey($locale, $endpoint));
    }

    private function lockByKey(string $key): Lock
    {
        $store = Cache::store()->getStore();

        if (! $store instanceof LockProvider) {
            throw new \LogicException('The public content cache store ['.config('cache.default').'] must implement Laravel LockProvider.');
        }

        return Cache::lock($key, self::LOCK_LEASE_SECONDS);
    }

    private function forgetWithRetry(string $key): void
    {
        for ($attempt = 1; $attempt <= self::INVALIDATION_ATTEMPTS; $attempt++) {
            $forgotten = Cache::forget($key);

            if ($forgotten || ! Cache::has($key)) {
                return;
            }
        }

        Log::error('Public content cache invalidation failed.', ['cache_key' => $key]);

        throw new PublicContentUnavailable;
    }

    /**
     * @return array<mixed>
     */
    private function dataArray(mixed $value, string $key): array
    {
        if (! is_array($value)) {
            throw new \UnexpectedValueException("Public content cache key [{$key}] must contain an array.");
        }

        return $value;
    }

    /**
     * @param  list<PublicEndpoint>  $endpoints
     * @return list<PublicEndpoint>
     */
    private function uniqueEndpoints(array $endpoints): array
    {
        $unique = [];

        foreach ($endpoints as $endpoint) {
            $unique[$endpoint->value] = $endpoint;
        }

        return array_values($unique);
    }
}
