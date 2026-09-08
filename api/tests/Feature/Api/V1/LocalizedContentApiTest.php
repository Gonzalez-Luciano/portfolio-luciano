<?php

namespace Tests\Feature\Api\V1;

use App\Enums\PublicationStatus;
use App\Enums\TechnologyCategory;
use App\Models\Experience;
use App\Models\Project;
use App\Models\Technology;
use App\Models\WorkCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class LocalizedContentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_route_locale_is_the_only_localization_source_for_all_public_endpoints(): void
    {
        $this->publishSingletons();
        $this->createLocalizedPublicCollections();

        foreach (['profile', 'experiences', 'work-cases', 'projects', 'technologies', 'site'] as $endpoint) {
            $spanish = $this->getJson("/api/v1/es/{$endpoint}?locale=en", ['Accept-Language' => 'en-US,en;q=0.9'])
                ->assertOk()
                ->json('data');
            $english = $this->getJson("/api/v1/en/{$endpoint}?locale=es", ['Accept-Language' => 'es-AR,es;q=0.9'])
                ->assertOk()
                ->json('data');

            $this->assertSameStructure($spanish, $english);
        }

        $this->assertSame('Titular español', $this->getJson('/api/v1/es/profile?locale=en', ['Accept-Language' => 'en'])->json('data.headline'));
        $this->assertSame('English headline', $this->getJson('/api/v1/en/profile?locale=es', ['Accept-Language' => 'es'])->json('data.headline'));
        $this->assertSame('Contacto español', $this->getJson('/api/v1/es/site?locale=en', ['Accept-Language' => 'en'])->json('data.contact_intro'));
        $this->assertSame('English contact', $this->getJson('/api/v1/en/site?locale=es', ['Accept-Language' => 'es'])->json('data.contact_intro'));
        $this->assertSame('Rol español distinto', $this->getJson('/api/v1/es/experiences?locale=en', ['Accept-Language' => 'en'])->json('data.0.role'));
        $this->assertSame('Distinct English role', $this->getJson('/api/v1/en/experiences?locale=es', ['Accept-Language' => 'es'])->json('data.0.role'));
        $this->assertSame('Caso español distinto', $this->getJson('/api/v1/es/work-cases?locale=en', ['Accept-Language' => 'en'])->json('data.0.title'));
        $this->assertSame('Distinct English case', $this->getJson('/api/v1/en/work-cases?locale=es', ['Accept-Language' => 'es'])->json('data.0.title'));
        $this->assertSame('Proyecto español distinto', $this->getJson('/api/v1/es/projects?locale=en', ['Accept-Language' => 'en'])->json('data.0.title'));
        $this->assertSame('Distinct English project', $this->getJson('/api/v1/en/projects?locale=es', ['Accept-Language' => 'es'])->json('data.0.title'));
        $this->assertSame('tecnologia-compartida', $this->getJson('/api/v1/es/projects')->json('data.0.technologies.0.key'));
    }

    public function test_all_collection_endpoints_return_empty_arrays_when_no_public_records_exist(): void
    {
        foreach (['es', 'en'] as $locale) {
            foreach (['experiences', 'work-cases', 'projects', 'technologies'] as $endpoint) {
                $this->getJson("/api/v1/{$locale}/{$endpoint}")
                    ->assertOk()
                    ->assertExactJson(['data' => []]);
            }
        }
    }

    public function test_unsupported_locale_is_enveloped_before_any_content_query_or_fallback(): void
    {
        $this->publishSingletons();
        $contentQueries = 0;

        DB::listen(static function ($query) use (&$contentQueries): void {
            if (str_contains($query->sql, 'profiles') || str_contains($query->sql, 'site_configurations')) {
                $contentQueries++;
            }
        });

        $this->getJson('/api/v1/fr/profile')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'unsupported_locale')
            ->assertJsonPath('error.details', []);

        $this->assertSame(0, $contentQueries);
    }

    public function test_missing_or_nonpublic_singletons_return_controlled_not_found_responses(): void
    {
        DB::table('profiles')->where('singleton_key', 'default')->delete();
        DB::table('site_configurations')->where('singleton_key', 'default')->update([
            'status' => PublicationStatus::Draft->value,
            'is_visible' => false,
            'published_at' => null,
        ]);

        $this->getJson('/api/v1/es/profile')->assertNotFound()->assertJsonPath('error.code', 'not_found');
        $this->getJson('/api/v1/en/site')->assertNotFound()->assertJsonPath('error.code', 'not_found');
    }

    public function test_collections_and_nested_technologies_are_public_filtered_and_deterministically_ordered(): void
    {
        $visibleTechnology = Technology::factory()->create([
            'key' => 'a-visible',
            'position' => 99,
            'name' => 'Visible technology',
            'category' => TechnologyCategory::Backend,
        ]);
        $hiddenTechnology = Technology::factory()->create(['key' => 'hidden-technology']);
        $first = Experience::factory()->create(['key' => 'a-first', 'position' => 2]);
        $second = Experience::factory()->create(['key' => 'z-second', 'position' => 2]);
        $hiddenExperience = Experience::factory()->create(['key' => 'hidden-experience']);
        $this->makePublic($visibleTechnology);
        $this->makePublic($hiddenTechnology, ['is_visible' => false]);
        $this->makePublic($first);
        $this->makePublic($second);
        $this->makePublic($hiddenExperience, ['is_visible' => false]);
        $first->technologies()->attach($hiddenTechnology, ['position' => 0]);
        $first->technologies()->attach($visibleTechnology, ['position' => 1]);

        $this->getJson('/api/v1/es/experiences')
            ->assertOk()
            ->assertJsonPath('data.0.key', 'a-first')
            ->assertJsonPath('data.1.key', 'z-second')
            ->assertJsonPath('data.0.technologies', [[
                'key' => 'a-visible',
                'name' => 'Visible technology',
                'category' => 'backend',
                'icon' => null,
            ]]);
    }

    public function test_public_api_cache_reuses_the_serialized_collection_until_invalidated(): void
    {
        $project = Project::factory()->create(['key' => 'cached-project', 'title_es' => 'Original title']);
        $this->makePublic($project);

        $this->getJson('/api/v1/es/projects')->assertJsonPath('data.0.title', 'Original title');
        DB::table('projects')->where('id', $project->id)->update(['title_es' => 'Changed directly']);

        $this->getJson('/api/v1/es/projects')->assertJsonPath('data.0.title', 'Original title');
    }

    private function publishSingletons(): void
    {
        DB::table('profiles')->where('singleton_key', 'default')->update([
            'name' => 'Luciano González',
            'headline_es' => 'Titular español',
            'headline_en' => 'English headline',
            'short_summary_es' => 'Resumen español',
            'short_summary_en' => 'English summary',
            'introduction_es' => 'Introducción española',
            'introduction_en' => 'English introduction',
            'availability_es' => 'Disponible',
            'availability_en' => 'Available',
            'cta_es' => 'Ver trabajo',
            'cta_en' => 'View work',
            'status' => PublicationStatus::Published->value,
            'is_visible' => true,
            'published_at' => now(),
        ]);
        DB::table('site_configurations')->where('singleton_key', 'default')->update([
            'projects_empty_message_es' => 'Sin proyectos',
            'projects_empty_message_en' => 'No projects',
            'contact_intro_es' => 'Contacto español',
            'contact_intro_en' => 'English contact',
            'technology_backend_label_es' => 'Backend',
            'technology_backend_label_en' => 'Backend',
            'technology_data_label_es' => 'Datos',
            'technology_data_label_en' => 'Data',
            'technology_integration_label_es' => 'Integraciones',
            'technology_integration_label_en' => 'Integrations',
            'technology_collaboration_label_es' => 'Colaboración',
            'technology_collaboration_label_en' => 'Collaboration',
            'status' => PublicationStatus::Published->value,
            'is_visible' => true,
            'published_at' => now(),
        ]);
    }

    private function createLocalizedPublicCollections(): void
    {
        $technology = Technology::factory()->create([
            'key' => 'tecnologia-compartida',
            'name' => 'Canonical Technology',
            'category' => TechnologyCategory::Integration,
        ]);
        $experience = Experience::factory()->create(['key' => 'experiencia-localizada']);
        $workCase = WorkCase::factory()->create(['key' => 'caso-localizado']);
        $project = Project::factory()->create(['key' => 'proyecto-localizado']);

        $this->makePublic($technology);
        $this->makePublic($experience, [
            'role_es' => 'Rol español distinto', 'role_en' => 'Distinct English role',
            'summary_es' => 'Resumen español distinto', 'summary_en' => 'Distinct English summary',
        ]);
        DB::table('experience_highlights')->insert([
            'experience_id' => $experience->id,
            'content_es' => 'Hito español distinto',
            'content_en' => 'Distinct English highlight',
            'position' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->makePublic($workCase, [
            'title_es' => 'Caso español distinto', 'title_en' => 'Distinct English case',
            'context_es' => 'Contexto español distinto', 'context_en' => 'Distinct English context',
            'problem_es' => 'Problema español distinto', 'problem_en' => 'Distinct English problem',
            'contribution_es' => 'Contribución española distinta', 'contribution_en' => 'Distinct English contribution',
            'technical_approach_es' => 'Enfoque español distinto', 'technical_approach_en' => 'Distinct English approach',
            'outcome_es' => 'Resultado español distinto', 'outcome_en' => 'Distinct English outcome',
        ]);
        $this->makePublic($project, [
            'title_es' => 'Proyecto español distinto', 'title_en' => 'Distinct English project',
            'summary_es' => 'Resumen de proyecto español', 'summary_en' => 'Distinct English project summary',
            'problem_es' => 'Problema de proyecto español', 'problem_en' => 'Distinct English project problem',
            'solution_es' => 'Solución de proyecto española', 'solution_en' => 'Distinct English project solution',
        ]);

        $experience->technologies()->attach($technology, ['position' => 0]);
        $workCase->technologies()->attach($technology, ['position' => 0]);
        $project->technologies()->attach($technology, ['position' => 0]);
    }

    private function assertSameStructure(mixed $spanish, mixed $english): void
    {
        $this->assertSame(get_debug_type($spanish), get_debug_type($english));

        if (! is_array($spanish)) {
            return;
        }

        $this->assertSame(array_keys($spanish), array_keys($english));

        foreach ($spanish as $key => $value) {
            $this->assertSameStructure($value, $english[$key]);
        }
    }

    /** @param array<string, mixed> $attributes */
    private function makePublic(Model $model, array $attributes = []): void
    {
        $values = [
            'status' => PublicationStatus::Published->value,
            'is_visible' => true,
            'published_at' => now(),
            ...$attributes,
        ];

        if (in_array($model::class, [Experience::class, Project::class, Technology::class, WorkCase::class], true)) {
            $values['key_locked'] = true;
        }

        DB::table($model->getTable())->where('id', $model->getKey())->update($values);
    }
}
