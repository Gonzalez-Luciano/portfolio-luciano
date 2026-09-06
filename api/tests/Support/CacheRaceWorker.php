<?php

use App\Enums\PublicEndpoint;
use App\Enums\SupportedLocale;
use App\Models\Profile;
use App\Support\PublicContentCache;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';

/** @var Application $app */
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

[$mode, $store, $directory] = array_slice($argv, 1);

config(['cache.default' => $store]);
Cache::forgetDriver($store);

$cache = $app->make(PublicContentCache::class);
$endpoint = PublicEndpoint::Profile;
$locale = SupportedLocale::Spanish;
$ready = $directory.'/old-rebuild-ready';
$release = $directory.'/release-old-rebuild';
$committed = $directory.'/mutation-committed';

if ($mode === 'old-rebuild') {
    Cache::forget($cache->key($locale, $endpoint));
    $cache->remember($locale, $endpoint, static function () use ($directory, $ready, $release): array {
        $profile = Profile::query()->publiclyAvailable()->sole();
        $representation = ['name' => $profile->name];
        file_put_contents($directory.'/old-representation.json', json_encode($representation, JSON_THROW_ON_ERROR));
        touch($ready);

        while (! file_exists($release)) {
            usleep(10_000);
        }

        return $representation;
    });

    exit(0);
}

if ($mode === 'visibility-reducing-mutation') {
    $cache->withMutationLocks([$endpoint], static function () use ($cache, $endpoint, $committed): void {
        $cache->invalidate([$endpoint]);
        DB::transaction(static function (): void {
            Profile::query()->publiclyAvailable()->sole()->update(['is_visible' => false]);
        });
        touch($committed);
        $cache->invalidate([$endpoint]);
    });

    exit(0);
}

fwrite(STDERR, "Unknown cache race worker mode.\n");
exit(2);
