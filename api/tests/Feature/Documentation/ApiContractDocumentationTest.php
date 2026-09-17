<?php

namespace Tests\Feature\Documentation;

use App\Enums\LanguageLevel;
use App\Enums\ProjectDeliveryStatus;
use App\Enums\ProjectKind;
use App\Enums\PublicEndpoint;
use App\Enums\SupportedLocale;
use App\Enums\TechnologyCategory;
use App\Enums\WorkMode;
use App\Support\PublicContentCache;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Drift detector: every fact asserted here must be re-derived from live
 * application constants/routes, never hardcoded to mirror the Markdown file.
 * This is intentionally a simple string-containment check against the
 * Markdown content, not a full Markdown parser.
 */
final class ApiContractDocumentationTest extends TestCase
{
    public function test_the_public_api_contract_document_exists(): void
    {
        $this->assertNotNull($this->contractPath(), 'docs/api/PUBLIC_API_V1.md must exist and be reachable from the test runtime.');
    }

    public function test_every_registered_locale_prefixed_public_route_is_mentioned_by_path(): void
    {
        $document = $this->document();

        $localeRoutes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route): bool => str_starts_with($route->uri(), 'api/v1'))
            ->map(fn ($route): string => $route->uri())
            ->values();

        $this->assertNotEmpty($localeRoutes, 'No api/v1 routes are registered.');

        foreach ($localeRoutes as $uri) {
            $this->assertStringContainsString(
                $uri,
                $document,
                "The registered route [{$uri}] must be documented by exact path in docs/api/PUBLIC_API_V1.md."
            );
        }
    }

    public function test_both_stable_cv_routes_are_documented_by_exact_path(): void
    {
        $document = $this->document();

        $esRoute = Route::getRoutes()->getByName('cv.download.es');
        $enRoute = Route::getRoutes()->getByName('cv.download.en');

        $this->assertNotNull($esRoute, 'The cv.download.es route must be registered.');
        $this->assertNotNull($enRoute, 'The cv.download.en route must be registered.');

        $this->assertSame('cv/luciano-gonzalez-es.pdf', $esRoute->uri());
        $this->assertSame('cv/luciano-gonzalez-en.pdf', $enRoute->uri());

        $this->assertStringContainsString('/cv/luciano-gonzalez-es.pdf', $document);
        $this->assertStringContainsString('/cv/luciano-gonzalez-en.pdf', $document);
    }

    public function test_the_technology_category_canonical_order_matches_the_enum_declaration_order(): void
    {
        $document = $this->document();

        $order = array_map(fn (TechnologyCategory $case): string => $case->value, TechnologyCategory::cases());

        $this->assertSame(['backend', 'data', 'integration', 'collaboration'], $order, 'This test itself must track the real enum order.');

        $positions = array_map(function (string $value) use ($document): int {
            // Anchored on the `technology_groups` array shape specifically
            // (`{"key": "backend", ...}`) rather than a bare quoted value,
            // because the envelope's own `"data"` key would otherwise collide
            // with the `data` category value and corrupt the ordering check.
            $position = strpos($document, "{\"key\": \"{$value}\"");
            $this->assertIsInt($position, "The documented technology_groups entry for [{$value}] was not found in docs/api/PUBLIC_API_V1.md.");

            return $position;
        }, $order);

        $sorted = $positions;
        sort($sorted);

        $this->assertSame($sorted, $positions, 'Documented technology category order must match TechnologyCategory::cases() declaration order exactly: backend, data, integration, collaboration.');
    }

    public function test_the_project_kind_canonical_order_matches_the_enum_declaration_order(): void
    {
        $document = $this->document();

        $order = array_map(fn (ProjectKind $case): string => $case->value, ProjectKind::cases());

        $this->assertSame(['client', 'personal'], $order, 'This test itself must track the real enum order.');

        $this->assertQuotedValuesAreOrderedOnDocumentedLine(
            $document,
            'App\Enums\ProjectKind',
            $order,
            'ProjectKind'
        );
    }

    public function test_the_project_delivery_status_canonical_order_matches_the_enum_declaration_order(): void
    {
        $document = $this->document();

        $order = array_map(fn (ProjectDeliveryStatus $case): string => $case->value, ProjectDeliveryStatus::cases());

        $this->assertSame(['in_production', 'in_use', 'public_demo', 'in_development'], $order, 'This test itself must track the real enum order.');

        $this->assertQuotedValuesAreOrderedOnDocumentedLine(
            $document,
            'App\Enums\ProjectDeliveryStatus',
            $order,
            'ProjectDeliveryStatus'
        );
    }

    public function test_the_language_level_canonical_order_matches_the_enum_declaration_order(): void
    {
        $document = $this->document();

        $order = array_map(fn (LanguageLevel $case): string => $case->value, LanguageLevel::cases());

        $this->assertSame(['native', 'a1', 'a2', 'b1', 'b2', 'c1', 'c2'], $order, 'This test itself must track the real enum order.');

        $this->assertQuotedValuesAreOrderedOnDocumentedLine(
            $document,
            'App\Enums\LanguageLevel',
            $order,
            'LanguageLevel'
        );
    }

    public function test_the_work_mode_canonical_order_matches_the_enum_declaration_order(): void
    {
        $document = $this->document();

        $order = array_map(fn (WorkMode $case): string => $case->value, WorkMode::cases());

        $this->assertSame(['on_site', 'hybrid', 'remote'], $order, 'This test itself must track the real enum order.');

        $this->assertQuotedValuesAreOrderedOnDocumentedLine(
            $document,
            'App\Enums\WorkMode',
            $order,
            'WorkMode'
        );
    }

    public function test_the_cache_key_namespace_matches_public_content_cache_key_building(): void
    {
        $document = $this->document();

        $cache = app(PublicContentCache::class);
        $actualKey = $cache->key(SupportedLocale::Spanish, PublicEndpoint::Profile);

        $this->assertSame('public-content:v1:es:profile', $actualKey);
        $this->assertStringContainsString('public-content:v1:{locale}:{endpoint}', $document, 'The documented cache key template must match PublicContentCache::key() exactly, not merely share a namespace prefix.');
        $this->assertSame($actualKey, strtr('public-content:v1:{locale}:{endpoint}', ['{locale}' => 'es', '{endpoint}' => 'profile']), 'Substituting the documented template placeholders must reproduce the actual built key.');
    }

    /**
     * Locates the single Markdown line referencing the given fully-qualified
     * enum class (its documented row/sentence) and asserts the given
     * enum-declaration-order values appear, quoted, on that line in the same
     * order — proving the doc's own ordering, not merely that every value is
     * present somewhere in the file.
     *
     * @param  list<string>  $order
     */
    private function assertQuotedValuesAreOrderedOnDocumentedLine(string $document, string $classMarker, array $order, string $enumLabel): void
    {
        $line = $this->documentedLineContaining($document, $classMarker);

        $positions = array_map(function (string $value) use ($line, $enumLabel): int {
            $position = strpos($line, "\"{$value}\"");
            $this->assertIsInt($position, "The documented {$enumLabel} value [{$value}] was not found on its documented line in docs/api/PUBLIC_API_V1.md.");

            return $position;
        }, $order);

        $sorted = $positions;
        sort($sorted);

        $this->assertSame($sorted, $positions, "Documented {$enumLabel} order must match its enum declaration order exactly: ".implode(', ', $order).'.');
    }

    private function documentedLineContaining(string $document, string $needle): string
    {
        $position = strpos($document, $needle);
        $this->assertIsInt($position, "The documented reference [{$needle}] was not found in docs/api/PUBLIC_API_V1.md.");

        $lineStart = strrpos(substr($document, 0, $position), "\n");
        $lineStart = $lineStart === false ? 0 : $lineStart + 1;
        $lineEnd = strpos($document, "\n", $position);
        $lineEnd = $lineEnd === false ? strlen($document) : $lineEnd;

        return substr($document, $lineStart, $lineEnd - $lineStart);
    }

    private function document(): string
    {
        $path = $this->contractPath();
        $this->assertNotNull($path, 'docs/api/PUBLIC_API_V1.md must exist and be reachable from the test runtime.');

        return (string) file_get_contents($path);
    }

    /**
     * The `api-test` container's `./api:/var/www/html` bind mount replaces
     * the container's view above /var/www/html, so the repository's own
     * `docs/` directory (a sibling of `api/`, not a descendant) is otherwise
     * unreachable. `compose.yaml` additionally mounts it read-only at
     * `/mnt/repo-docs` for exactly this test. A plain host run of the test
     * suite against a full checkout (no Docker isolation) still works via the
     * ordinary relative path.
     */
    private function contractPath(): ?string
    {
        foreach ([
            '/mnt/repo-docs/api/PUBLIC_API_V1.md',
            __DIR__.'/../../../../docs/api/PUBLIC_API_V1.md',
        ] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
