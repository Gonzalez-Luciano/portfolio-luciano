<?php

namespace Tests\Feature\Cache;

use App\Enums\PublicationStatus;
use App\Enums\PublicEndpoint;
use App\Enums\SupportedLocale;
use App\Exceptions\PublicContentUnavailable;
use App\Models\Profile;
use App\Support\PublicContentCache;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\Support\RecordingLockStore;
use Tests\TestCase;

final class PublicContentCacheConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_a_normal_cache_hit_never_requests_a_lock_but_a_miss_rejects_a_non_locking_store_with_a_diagnostic(): void
    {
        $this->app['cache']->extend('non-locking', function (): Repository {
            return new Repository(new class implements Store
            {
                private array $values = [];

                public function get($key): mixed
                {
                    return $this->values[$key] ?? null;
                }

                public function many(array $keys): array
                {
                    return array_fill_keys($keys, null);
                }

                public function put($key, $value, $seconds): bool
                {
                    $this->values[$key] = $value;

                    return true;
                }

                public function putMany(array $values, $seconds): bool
                {
                    $this->values = [...$this->values, ...$values];

                    return true;
                }

                public function increment($key, $value = 1): int|bool
                {
                    return false;
                }

                public function decrement($key, $value = 1): int|bool
                {
                    return false;
                }

                public function forever($key, $value): bool
                {
                    $this->values[$key] = $value;

                    return true;
                }

                public function touch($key, $seconds): bool
                {
                    return isset($this->values[$key]);
                }

                public function forget($key): bool
                {
                    unset($this->values[$key]);

                    return true;
                }

                public function flush(): bool
                {
                    $this->values = [];

                    return true;
                }

                public function getPrefix(): string
                {
                    return '';
                }
            });
        });
        config([
            'cache.default' => 'non-locking',
            'cache.stores.non-locking' => ['driver' => 'non-locking'],
        ]);
        Cache::forgetDriver('non-locking');

        $cache = app(PublicContentCache::class);
        $key = $cache->key(SupportedLocale::Spanish, PublicEndpoint::Profile);
        Cache::forever($key, ['cached-without-a-lock']);

        $this->assertSame(['cached-without-a-lock'], $cache->remember(
            SupportedLocale::Spanish,
            PublicEndpoint::Profile,
            static fn (): array => ['resolver-must-not-run'],
        ));

        Cache::forget($key);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The public content cache store [non-locking] must implement Laravel LockProvider.');

        $cache->remember(SupportedLocale::Spanish, PublicEndpoint::Profile, static fn (): array => []);
    }

    #[DataProvider('lockCapableStores')]
    public function test_the_approved_lock_capable_store_rebuilds_once_and_double_checks_inside_the_lock(string $store): void
    {
        $cache = $this->useLockStore($store);
        $this->assertInstanceOf(LockProvider::class, Cache::store()->getStore(), "The approved [{$store}] store must implement Laravel LockProvider.");

        $key = $cache->key(SupportedLocale::Spanish, PublicEndpoint::Profile);
        $lock = Cache::lock($cache->lockKey(SupportedLocale::Spanish, PublicEndpoint::Profile), 60);
        $this->assertTrue($lock->get());

        try {
            Cache::forever($key, ['built-by-lock-holder']);

            $this->assertSame(['built-by-lock-holder'], $cache->remember(
                SupportedLocale::Spanish,
                PublicEndpoint::Profile,
                static fn (): array => ['resolver-must-not-run'],
            ));
        } finally {
            $lock->release();
        }
    }

    public function test_mutation_locks_acquire_all_keys_in_lexical_order_and_release_them_in_reverse_order(): void
    {
        $store = new RecordingLockStore($this->app['files'], storage_path('framework/cache/data'));
        $this->app['cache']->extend('recording-locks', static fn (): Repository => new Repository($store));
        config([
            'cache.default' => 'recording-locks',
            'cache.stores.recording-locks' => ['driver' => 'recording-locks'],
        ]);
        Cache::forgetDriver('recording-locks');
        $cache = app(PublicContentCache::class);
        $expectedKeys = [
            $cache->lockKey(SupportedLocale::English, PublicEndpoint::Experiences),
            $cache->lockKey(SupportedLocale::English, PublicEndpoint::WorkCases),
            $cache->lockKey(SupportedLocale::Spanish, PublicEndpoint::Experiences),
            $cache->lockKey(SupportedLocale::Spanish, PublicEndpoint::WorkCases),
        ];
        sort($expectedKeys, SORT_STRING);

        $result = $cache->withMutationLocks([
            PublicEndpoint::WorkCases,
            PublicEndpoint::Experiences,
        ], static function (): string {
            return 'mutated';
        });

        $this->assertSame('mutated', $result);
        $this->assertSame([
            ...array_map(static fn (string $key): string => "acquire:{$key}", $expectedKeys),
            ...array_map(static fn (string $key): string => "release:{$key}", array_reverse($expectedKeys)),
        ], $store->events);
    }

    public function test_it_converts_a_public_lock_timeout_to_the_controlled_api_error_envelope(): void
    {
        Route::get('/api/v1/cache-timeout-test', static fn (): never => throw new PublicContentUnavailable);

        $this->getJson('/api/v1/cache-timeout-test')
            ->assertServiceUnavailable()
            ->assertContent('{"error":{"code":"content_temporarily_unavailable","message":"Public content is temporarily unavailable. Please try again later.","details":{}}}');
    }

    #[DataProvider('lockCapableStores')]
    public function test_a_visibility_reducing_mutation_cannot_leave_a_paused_old_state_rebuild_cached_after_commit(string $store): void
    {
        $cache = $this->useLockStore($store);
        $profile = Profile::query()->sole();
        $profile->forceFill([
            'name' => 'Old publicly visible profile',
            'status' => PublicationStatus::Published,
            'is_visible' => true,
            'published_at' => now(),
        ])->save();
        $directory = storage_path('framework/testing/cache-race-'.bin2hex(random_bytes(8)));
        mkdir($directory, 0700, true);

        $oldRebuild = new Process([
            PHP_BINARY,
            base_path('tests/Support/CacheRaceWorker.php'),
            'old-rebuild',
            $store,
            $directory,
        ], base_path());
        $mutation = new Process([
            PHP_BINARY,
            base_path('tests/Support/CacheRaceWorker.php'),
            'visibility-reducing-mutation',
            $store,
            $directory,
        ], base_path());
        $oldRebuild->setTimeout(15);
        $mutation->setTimeout(15);

        try {
            $oldRebuild->start();
            $this->waitForFile($directory.'/old-rebuild-ready');

            $mutation->start();
            usleep(250_000);
            $this->assertTrue($mutation->isRunning(), 'The mutation must wait for the paused rebuild lock.');

            touch($directory.'/release-old-rebuild');
            $oldRebuild->wait();
            $mutation->wait();

            $this->assertTrue($oldRebuild->isSuccessful(), $oldRebuild->getErrorOutput());
            $this->assertTrue($mutation->isSuccessful(), $mutation->getErrorOutput());
            $this->assertSame(['name' => 'Old publicly visible profile'], json_decode((string) file_get_contents($directory.'/old-representation.json'), true, 512, JSON_THROW_ON_ERROR));
            $this->assertFalse(Profile::query()->publiclyAvailable()->exists());
            $this->assertNull(Cache::get($cache->key(SupportedLocale::Spanish, PublicEndpoint::Profile)));
            $this->assertNull(Cache::get($cache->key(SupportedLocale::English, PublicEndpoint::Profile)));
            $this->assertFileExists($directory.'/mutation-committed');
        } finally {
            $oldRebuild->stop();
            $mutation->stop();
            foreach (glob($directory.'/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($directory);
        }
    }

    public static function lockCapableStores(): array
    {
        return [
            'file store' => ['file'],
            'database store' => ['database'],
        ];
    }

    private function useLockStore(string $store): PublicContentCache
    {
        config(['cache.default' => $store]);
        Cache::forgetDriver($store);
        $cache = app(PublicContentCache::class);

        foreach (PublicEndpoint::cases() as $endpoint) {
            foreach (SupportedLocale::cases() as $locale) {
                Cache::forget($cache->key($locale, $endpoint));
            }
        }

        return $cache;
    }

    private function waitForFile(string $path): void
    {
        $deadline = microtime(true) + 5;

        while (! file_exists($path) && microtime(true) < $deadline) {
            usleep(25_000);
        }

        $this->assertFileExists($path, 'The old-state rebuild did not reach its controlled pause.');
    }
}
