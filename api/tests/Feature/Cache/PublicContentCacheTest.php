<?php

namespace Tests\Feature\Cache;

use App\Enums\PublicEndpoint;
use App\Enums\SupportedLocale;
use App\Http\Responses\ApiErrorResponse;
use App\Models\CvDocument;
use App\Models\Experience;
use App\Models\ExperienceHighlight;
use App\Models\ExpertiseArea;
use App\Models\ProfessionalLink;
use App\Models\Profile;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\SiteConfiguration;
use App\Models\Technology;
use App\Models\WorkCase;
use App\Models\WorkPrinciple;
use App\Support\PublicContentCache;
use App\Support\PublicContentDependencies;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class PublicContentCacheTest extends TestCase
{
    public function test_it_uses_the_exact_versioned_data_and_rebuild_keys(): void
    {
        $cache = app(PublicContentCache::class);

        $this->assertSame('public-content:v1:es:work-cases', $cache->key(SupportedLocale::Spanish, PublicEndpoint::WorkCases));
        $this->assertSame('public-content-rebuild:v1:en:site', $cache->lockKey(SupportedLocale::English, PublicEndpoint::Site));
    }

    public function test_it_remembers_the_data_array_forever_including_an_empty_array(): void
    {
        $cache = app(PublicContentCache::class);
        $calls = 0;

        $this->assertSame([], $cache->remember(SupportedLocale::Spanish, PublicEndpoint::Projects, function () use (&$calls): array {
            $calls++;

            return [];
        }));
        $this->assertSame([], $cache->remember(SupportedLocale::Spanish, PublicEndpoint::Projects, function () use (&$calls): array {
            $calls++;

            return ['must-not-be-returned'];
        }));
        $this->assertSame(1, $calls);
        $this->assertSame([], Cache::get('public-content:v1:es:projects'));
    }

    public function test_it_caches_only_the_data_array_and_not_an_http_envelope_or_model(): void
    {
        $cache = app(PublicContentCache::class);

        $result = $cache->remember(SupportedLocale::English, PublicEndpoint::Profile, static fn (): array => ['name' => 'Luciano']);

        $this->assertSame(['name' => 'Luciano'], $result);
        $this->assertSame(['name' => 'Luciano'], Cache::get('public-content:v1:en:profile'));
    }

    #[DataProvider('nonArrayResolverValues')]
    public function test_it_rejects_non_array_resolver_values_before_they_can_occupy_a_public_cache_key(mixed $value): void
    {
        $cache = app(PublicContentCache::class);
        $key = $cache->key(SupportedLocale::English, PublicEndpoint::Profile);
        Cache::forget($key);

        try {
            $cache->remember(SupportedLocale::English, PublicEndpoint::Profile, static fn (): mixed => $value);
            $this->fail('A non-array resolver value must be rejected.');
        } catch (\UnexpectedValueException) {
            $this->assertNull(Cache::get($key));
        }
    }

    public static function nonArrayResolverValues(): array
    {
        return [
            'eloquent model' => [new Profile],
            'JSON response' => [new JsonResponse(['data' => ['name' => 'Luciano']])],
            'API error envelope' => [ApiErrorResponse::make('not_found', 'Not found.', [], 404)],
        ];
    }

    public function test_it_expands_every_model_dependency_to_the_approved_endpoints(): void
    {
        $dependencies = app(PublicContentDependencies::class);

        $this->assertSame([PublicEndpoint::Profile], $dependencies->for(Profile::class));
        $this->assertSame([PublicEndpoint::Site], $dependencies->for(SiteConfiguration::class));
        $this->assertSame([PublicEndpoint::Site], $dependencies->for(ProfessionalLink::class));
        $this->assertSame([PublicEndpoint::Site], $dependencies->for(ExpertiseArea::class));
        $this->assertSame([PublicEndpoint::Site], $dependencies->for(WorkPrinciple::class));
        $this->assertSame([PublicEndpoint::Site], $dependencies->for(CvDocument::class));
        $this->assertSame([PublicEndpoint::Experiences], $dependencies->for(Experience::class));
        $this->assertSame([PublicEndpoint::Experiences], $dependencies->for(ExperienceHighlight::class));
        $this->assertSame([PublicEndpoint::WorkCases], $dependencies->for(WorkCase::class));
        $this->assertSame([PublicEndpoint::Projects], $dependencies->for(Project::class));
        $this->assertSame([PublicEndpoint::Projects], $dependencies->for(ProjectImage::class));
        $this->assertSame([
            PublicEndpoint::Technologies,
            PublicEndpoint::Experiences,
            PublicEndpoint::WorkCases,
            PublicEndpoint::Projects,
        ], $dependencies->for(Technology::class));
    }

    public function test_it_invalidates_each_affected_endpoint_for_both_locales_without_flushing_other_values(): void
    {
        $cache = app(PublicContentCache::class);
        $unrelatedKey = 'unrelated-cache-key';

        Cache::forever($cache->key(SupportedLocale::Spanish, PublicEndpoint::Experiences), ['es']);
        Cache::forever($cache->key(SupportedLocale::English, PublicEndpoint::Experiences), ['en']);
        Cache::forever($cache->key(SupportedLocale::Spanish, PublicEndpoint::Technologies), ['es-tech']);
        Cache::forever($cache->key(SupportedLocale::English, PublicEndpoint::Technologies), ['en-tech']);
        Cache::forever($unrelatedKey, ['keep']);

        $cache->invalidate([PublicEndpoint::Experiences, PublicEndpoint::Technologies]);

        $this->assertNull(Cache::get($cache->key(SupportedLocale::Spanish, PublicEndpoint::Experiences)));
        $this->assertNull(Cache::get($cache->key(SupportedLocale::English, PublicEndpoint::Experiences)));
        $this->assertNull(Cache::get($cache->key(SupportedLocale::Spanish, PublicEndpoint::Technologies)));
        $this->assertNull(Cache::get($cache->key(SupportedLocale::English, PublicEndpoint::Technologies)));
        $this->assertSame(['keep'], Cache::get($unrelatedKey));
    }
}
