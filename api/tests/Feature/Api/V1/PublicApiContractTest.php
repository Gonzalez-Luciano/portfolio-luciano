<?php

namespace Tests\Feature\Api\V1;

use App\Domain\Content\Actions\DeleteContent;
use App\Domain\Content\Actions\HideContent;
use App\Domain\Content\Actions\PublishContent;
use App\Domain\Content\Actions\ShowContent;
use App\Enums\PublicationStatus;
use App\Enums\TechnologyCategory;
use App\Models\CvDocument;
use App\Models\EducationEntry;
use App\Models\Experience;
use App\Models\ExpertiseArea;
use App\Models\Language;
use App\Models\ProfessionalLink;
use App\Models\Project;
use App\Models\Technology;
use App\Models\WorkCase;
use App\Models\WorkPrinciple;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class PublicApiContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Storage::fake('public');
        Storage::fake('local');
    }

    public function test_profile_resource_has_the_exact_public_shape_and_only_exposes_a_verified_public_photo(): void
    {
        DB::table('profiles')->where('singleton_key', 'default')->update([
            'name' => 'Luciano González', 'headline_es' => 'Backend', 'headline_en' => 'Backend',
            'short_summary_es' => 'Resumen', 'short_summary_en' => 'Summary',
            'introduction_es' => 'Introducción', 'introduction_en' => 'Introduction',
            'availability_es' => 'Disponible', 'availability_en' => 'Available', 'cta_es' => 'Contacto', 'cta_en' => 'Contact',
            'photo_private_path' => 'profiles/private.jpg', 'photo_public_path' => 'profiles/public.jpg',
            'photo_mime' => 'image/jpeg', 'photo_size' => 1,
            'photo_alt_es' => 'Retrato', 'photo_alt_en' => 'Portrait',
            'status' => PublicationStatus::Published->value, 'is_visible' => true, 'published_at' => now(),
        ]);

        $this->getJson('/api/v1/es/profile')->assertExactJson(['data' => [
            'name' => 'Luciano González', 'headline' => 'Backend', 'short_summary' => 'Resumen',
            'introduction' => 'Introducción', 'availability' => 'Disponible', 'statement' => null, 'closing' => null,
            'cta' => 'Contacto', 'photo' => null,
        ]]);

        Storage::disk('public')->put('profiles/public.jpg', 'image');
        Cache::flush();

        $this->getJson('/api/v1/en/profile')->assertExactJson(['data' => [
            'name' => 'Luciano González', 'headline' => 'Backend', 'short_summary' => 'Summary',
            'introduction' => 'Introduction', 'availability' => 'Available', 'statement' => null, 'closing' => null,
            'cta' => 'Contact', 'photo' => ['url' => '/storage/profiles/public.jpg', 'alt' => 'Portrait'],
        ]]);
    }

    public function test_profile_scene_copy_groups_are_exposed_only_when_every_localized_part_is_filled(): void
    {
        DB::table('profiles')->where('singleton_key', 'default')->update([
            'name' => 'Luciano González', 'headline_es' => 'Backend', 'headline_en' => 'Backend',
            'short_summary_es' => 'Resumen', 'short_summary_en' => 'Summary',
            'introduction_es' => 'Introducción', 'introduction_en' => 'Introduction',
            'availability_es' => 'Disponible', 'availability_en' => 'Available', 'cta_es' => 'Contacto', 'cta_en' => 'Contact',
            'statement_lead_es' => 'Inicio', 'statement_lead_en' => 'Lead',
            'statement_emphasis_es' => 'Énfasis', 'statement_emphasis_en' => 'Emphasis',
            'statement_tail_es' => 'Cierre', 'statement_tail_en' => 'Tail',
            'closing_line_one_es' => 'Línea uno', 'closing_line_one_en' => 'Line one',
            'closing_line_two_es' => 'Línea dos', 'closing_line_two_en' => '   ',
            'status' => PublicationStatus::Published->value, 'is_visible' => true, 'published_at' => now(),
        ]);

        $this->getJson('/api/v1/es/profile')
            ->assertOk()
            ->assertJsonPath('data.statement', ['lead' => 'Inicio', 'emphasis' => 'Énfasis', 'tail' => 'Cierre'])
            ->assertJsonPath('data.closing', ['line_one' => 'Línea uno', 'line_two' => 'Línea dos']);

        $this->getJson('/api/v1/en/profile')
            ->assertOk()
            ->assertJsonPath('data.statement', ['lead' => 'Lead', 'emphasis' => 'Emphasis', 'tail' => 'Tail'])
            ->assertJsonPath('data.closing', null);
    }

    public function test_collection_resources_emit_exact_types_normalized_dates_and_no_editorial_data(): void
    {
        $technology = Technology::factory()->create([
            'key' => 'laravel', 'name' => 'Laravel', 'category' => TechnologyCategory::Backend,
        ]);
        $experience = Experience::factory()->create([
            'key' => 'experience', 'organization_label_es' => null, 'organization_label_en' => null,
            'start_year' => 2024, 'start_month' => 3, 'end_year' => 2025, 'end_month' => 1,
        ]);
        DB::table('experience_highlights')->insert([
            'experience_id' => $experience->id, 'content_es' => 'Hito técnico sintético.', 'content_en' => 'Synthetic technical highlight.',
            'position' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $experience->technologies()->attach($technology, ['position' => 0]);
        $workCase = WorkCase::factory()->create(['key' => 'case', 'experience_id' => $experience->id]);
        $workCase->technologies()->attach($technology, ['position' => 0]);
        $project = Project::factory()->create(['key' => 'project']);
        $this->makePublic($technology, [
            'icon_private_path' => 'technologies/private.webp', 'icon_public_path' => 'technologies/icon.webp',
            'icon_mime' => 'image/webp', 'icon_size' => 1,
        ]);
        $this->makePublic($experience, [
            'role_es' => 'Ingeniero técnico sintético', 'role_en' => 'Synthetic technical engineer',
            'summary_es' => 'Resumen de experiencia sintética.', 'summary_en' => 'Synthetic experience summary.',
        ]);
        $this->makePublic($workCase, [
            'title_es' => 'Caso técnico sintético', 'title_en' => 'Synthetic technical case',
            'context_es' => 'Contexto técnico sintético.', 'context_en' => 'Synthetic technical context.',
            'problem_es' => 'Problema técnico sintético.', 'problem_en' => 'Synthetic technical problem.',
            'contribution_es' => 'Contribución técnica sintética.', 'contribution_en' => 'Synthetic technical contribution.',
            'technical_approach_es' => 'Enfoque técnico sintético.', 'technical_approach_en' => 'Synthetic technical approach.',
            'outcome_es' => 'Resultado técnico sintético.', 'outcome_en' => 'Synthetic technical outcome.',
        ]);
        $this->makePublic($project, [
            'title_es' => 'Proyecto técnico sintético', 'title_en' => 'Synthetic technical project',
            'summary_es' => 'Resumen técnico sintético.', 'summary_en' => 'Synthetic technical summary.',
            'problem_es' => 'Problema técnico sintético.', 'problem_en' => 'Synthetic technical problem.',
            'solution_es' => 'Solución técnica sintética.', 'solution_en' => 'Synthetic technical solution.',
            'kind' => 'client', 'client_name' => 'Synthetic Client', 'role_es' => 'Backend sintético', 'role_en' => 'Synthetic backend', 'delivery_status' => 'in_use', 'result_es' => 'Resultado técnico sintético.', 'result_en' => 'Synthetic technical result.',
        ]);
        $project->technologies()->attach($technology, ['position' => 0]);
        DB::table('project_images')->insert([
            ['project_id' => $project->id, 'position' => 1, 'private_path' => 'projects/second-private.jpg', 'public_path' => 'projects/second.jpg', 'mime' => 'image/jpeg', 'size' => 1, 'alt_es' => 'Segunda', 'alt_en' => 'Second', 'created_at' => now(), 'updated_at' => now()],
            ['project_id' => $project->id, 'position' => 0, 'private_path' => 'projects/private.jpg', 'public_path' => 'projects/image.jpg', 'mime' => 'image/jpeg', 'size' => 1, 'alt_es' => 'Imagen', 'alt_en' => 'Image', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->getJson('/api/v1/es/experiences')->assertExactJson(['data' => [[
            'key' => 'experience', 'organization' => null, 'role' => 'Ingeniero técnico sintético', 'start' => '2024-03', 'end' => '2025-01',
            'summary' => 'Resumen de experiencia sintética.', 'highlights' => ['Hito técnico sintético.'],
            'technologies' => [['key' => 'laravel', 'name' => 'Laravel', 'category' => 'backend', 'icon' => null]],
        ]]]);
        $this->getJson('/api/v1/es/work-cases')->assertExactJson(['data' => [[
            'key' => 'case', 'experience_key' => 'experience', 'title' => 'Caso técnico sintético', 'context' => 'Contexto técnico sintético.', 'problem' => 'Problema técnico sintético.',
            'contribution' => 'Contribución técnica sintética.', 'technical_approach' => 'Enfoque técnico sintético.', 'outcome' => 'Resultado técnico sintético.',
            'technologies' => [['key' => 'laravel', 'name' => 'Laravel', 'category' => 'backend', 'icon' => null]],
        ]]]);
        $this->getJson('/api/v1/es/projects')->assertExactJson(['data' => [[
            'key' => 'project', 'kind' => 'client', 'client_name' => 'Synthetic Client', 'title' => 'Proyecto técnico sintético',
            'role' => 'Backend sintético', 'status' => 'in_use', 'summary' => 'Resumen técnico sintético.', 'problem' => 'Problema técnico sintético.',
            'solution' => 'Solución técnica sintética.', 'result' => 'Resultado técnico sintético.', 'featured' => false, 'images' => [],
            'demo_url' => null, 'repository_url' => null,
            'technologies' => [['key' => 'laravel', 'name' => 'Laravel', 'category' => 'backend', 'icon' => null]],
        ]]]);
        $this->getJson('/api/v1/es/technologies')->assertExactJson(['data' => [[
            'key' => 'laravel', 'name' => 'Laravel', 'category' => 'backend', 'icon' => null,
        ]]]);

        Storage::disk('public')->put('technologies/icon.webp', 'icon');
        Storage::disk('public')->put('projects/image.jpg', 'image');
        Storage::disk('public')->put('projects/second.jpg', 'image');
        Cache::flush();

        $this->getJson('/api/v1/en/projects')->assertJsonPath('data.0.images', [
            ['url' => '/storage/projects/image.jpg', 'alt' => 'Image'],
            ['url' => '/storage/projects/second.jpg', 'alt' => 'Second'],
        ]);
        $this->getJson('/api/v1/en/technologies')->assertJsonPath('data.0.icon', [
            'url' => '/storage/technologies/icon.webp',
        ]);
    }

    public function test_site_resource_has_ordered_groups_independently_filtered_collections_and_a_private_cv_contract(): void
    {
        $this->publishSite();
        $email = ProfessionalLink::factory()->email()->create(['position' => 2, 'label_es' => 'Correo', 'label_en' => 'Email']);
        $github = ProfessionalLink::factory()->github()->create(['position' => 1]);
        $expertise = ExpertiseArea::factory()->withoutDescription()->create(['key' => 'apis']);
        $principle = WorkPrinciple::factory()->create(['key' => 'quality']);
        $cv = CvDocument::factory()->spanish()->create(['private_path' => 'cv/es.pdf', 'mime' => 'application/pdf', 'size' => 3, 'label' => 'Descargar CV']);
        $this->makePublic($email);
        $this->makePublic($github, ['is_visible' => false]);
        $this->makePublic($expertise, ['title_es' => 'Área técnica sintética', 'title_en' => 'Synthetic technical area']);
        $this->makePublic($principle, ['statement_es' => 'Principio técnico sintético.', 'statement_en' => 'Synthetic technical principle.']);
        $this->makePublic($cv);

        $this->getJson('/api/v1/es/site')->assertExactJson(['data' => [
            'projects_empty_message' => 'Sin proyectos', 'contact_intro' => 'Contacto',
            'technology_groups' => [
                ['key' => 'backend', 'label' => 'Backend'], ['key' => 'data', 'label' => 'Datos'],
                ['key' => 'integration', 'label' => 'Integraciones'], ['key' => 'collaboration', 'label' => 'Colaboración'],
            ],
            'professional_links' => [['key' => 'email', 'label' => 'Correo', 'href' => 'mailto:synthetic@example.test']],
            'expertise_areas' => [['key' => 'apis', 'title' => 'Área técnica sintética', 'description' => null]],
            'work_principles' => [['key' => 'quality', 'statement' => 'Principio técnico sintético.']],
            'education' => [],
            'languages' => [],
            'cv' => null,
        ]]);

        Storage::disk('local')->put('cv/es.pdf', 'pdf');
        Cache::flush();
        $this->getJson('/api/v1/es/site')->assertJsonPath('data.cv', ['url' => '/cv/luciano-gonzalez-es.pdf', 'label' => 'Descargar CV']);
        $this->getJson('/api/v1/en/site')->assertJsonPath('data.cv', null);
    }

    public function test_site_resource_exposes_public_education_in_order(): void
    {
        $this->publishSite();
        $course = EducationEntry::factory()->create(['key' => 'course', 'position' => 1]);
        $school = EducationEntry::factory()->create(['key' => 'school', 'position' => 0]);
        $hidden = EducationEntry::factory()->create(['key' => 'hidden-entry', 'position' => 2]);
        $this->makePublic($school, ['institution' => 'Instituto sintético', 'program_es' => 'Bachiller sintético', 'program_en' => 'Synthetic diploma', 'end_year' => 2021]);
        $this->makePublic($course, ['institution' => 'Centro sintético', 'program_es' => 'Curso sintético', 'program_en' => 'Synthetic course', 'detail_es' => 'Detalle', 'detail_en' => 'Detail', 'start_year' => 2022, 'end_year' => 2022]);
        $this->makePublic($hidden, ['institution' => 'Oculto', 'program_es' => 'Oculto', 'program_en' => 'Hidden', 'is_visible' => false]);

        $this->getJson('/api/v1/en/site')->assertJsonPath('data.education', [
            ['key' => 'school', 'institution' => 'Instituto sintético', 'program' => 'Synthetic diploma', 'detail' => null, 'start_year' => null, 'end_year' => 2021],
            ['key' => 'course', 'institution' => 'Centro sintético', 'program' => 'Synthetic course', 'detail' => 'Detail', 'start_year' => 2022, 'end_year' => 2022],
        ]);
    }

    public function test_site_resource_exposes_public_languages_in_order(): void
    {
        $this->publishSite();
        $english = Language::factory()->create(['key' => 'english', 'position' => 1]);
        $spanish = Language::factory()->create(['key' => 'spanish', 'position' => 0]);
        $this->makePublic($english, ['name_es' => 'Inglés', 'name_en' => 'English', 'level' => 'b2']);
        $this->makePublic($spanish, ['name_es' => 'Español', 'name_en' => 'Spanish', 'level' => 'native']);

        $this->getJson('/api/v1/es/site')->assertJsonPath('data.languages', [
            ['key' => 'spanish', 'name' => 'Español', 'level' => 'native'],
            ['key' => 'english', 'name' => 'Inglés', 'level' => 'b2'],
        ]);
    }

    public function test_public_responses_recursively_exclude_internal_and_editorial_fields(): void
    {
        $technology = Technology::factory()->create(['key' => 'safe-tech']);
        $project = Project::factory()->create(['key' => 'safe-project']);
        $this->makePublic($technology);
        $this->makePublic($project);
        $project->technologies()->attach($technology, ['position' => 4]);

        $payload = $this->getJson('/api/v1/es/projects')->assertOk()->json();
        $serialized = json_encode($payload, JSON_THROW_ON_ERROR);

        foreach (['id', 'position', 'pivot', 'private_path', 'public_path', 'created_at', 'updated_at', '"status":"draft"', '"status":"published"', 'is_visible', 'published_at', 'key_locked', '_es', '_en', 'location', 'confidentiality_note', 'video_url', 'seo'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $serialized);
        }
    }

    public function test_work_case_experience_key_is_exposed_only_while_the_experience_is_public(): void
    {
        $experience = Experience::factory()->create(['key' => 'linked-role']);
        $workCase = WorkCase::factory()->create(['key' => 'linked-case', 'experience_id' => $experience->id]);
        $this->makePublic($experience, [
            'role_es' => 'Rol sintético', 'role_en' => 'Synthetic role',
            'summary_es' => 'Resumen sintético.', 'summary_en' => 'Synthetic summary.',
        ]);
        $this->makePublic($workCase);

        $this->getJson('/api/v1/es/work-cases')->assertJsonPath('data.0.experience_key', 'linked-role');

        DB::table('experiences')->where('id', $experience->id)->update(['is_visible' => false]);
        Cache::flush();
        $this->getJson('/api/v1/es/work-cases')->assertJsonPath('data.0.experience_key', null);

        DB::table('experiences')->where('id', $experience->id)->delete();
        Cache::flush();
        $this->getJson('/api/v1/es/work-cases')->assertJsonPath('data.0.experience_key', null);
        $this->assertDatabaseHas('work_cases', ['id' => $workCase->id, 'experience_id' => null]);
    }

    public function test_hiding_or_deleting_an_experience_through_a_domain_action_invalidates_the_cached_work_cases_endpoint(): void
    {
        $experience = Experience::factory()->draft()->create([
            'key' => 'invalidation-role',
            'role_es' => 'Rol sintético', 'role_en' => 'Synthetic role',
            'summary_es' => 'Resumen sintético.', 'summary_en' => 'Synthetic summary.',
        ]);
        $experience = app(ShowContent::class)(app(PublishContent::class)($experience));

        $workCase = WorkCase::factory()->draft()->create([
            'key' => 'invalidation-case',
            'experience_id' => $experience->id,
            'title_es' => 'Caso técnico sintético', 'title_en' => 'Synthetic technical case',
            'context_es' => 'Contexto técnico sintético.', 'context_en' => 'Synthetic technical context.',
            'problem_es' => 'Problema técnico sintético.', 'problem_en' => 'Synthetic technical problem.',
            'contribution_es' => 'Contribución técnica sintética.', 'contribution_en' => 'Synthetic technical contribution.',
            'technical_approach_es' => 'Enfoque técnico sintético.', 'technical_approach_en' => 'Synthetic technical approach.',
            'outcome_es' => 'Resultado técnico sintético.', 'outcome_en' => 'Synthetic technical outcome.',
        ]);
        app(ShowContent::class)(app(PublishContent::class)($workCase));

        // Warm the work-cases cache while the experience is still public.
        $this->getJson('/api/v1/es/work-cases')->assertJsonPath('data.0.experience_key', 'invalidation-role');

        // A real domain-action transition (not a manual Cache::flush()) must
        // invalidate the already-warmed work-cases cache because Experience
        // now depends on both `experiences` and `work-cases` (spec 4.8/4.3).
        app(HideContent::class)($experience->fresh());

        $this->getJson('/api/v1/es/work-cases')->assertJsonPath('data.0.experience_key', null);

        app(DeleteContent::class)($experience->fresh());

        $this->getJson('/api/v1/es/work-cases')->assertJsonPath('data.0.experience_key', null);
        $this->assertDatabaseHas('work_cases', ['id' => $workCase->id, 'experience_id' => null]);
    }

    private function publishSite(): void
    {
        DB::table('site_configurations')->where('singleton_key', 'default')->update([
            'projects_empty_message_es' => 'Sin proyectos', 'projects_empty_message_en' => 'No projects',
            'contact_intro_es' => 'Contacto', 'contact_intro_en' => 'Contact',
            'technology_backend_label_es' => 'Backend', 'technology_backend_label_en' => 'Backend',
            'technology_data_label_es' => 'Datos', 'technology_data_label_en' => 'Data',
            'technology_integration_label_es' => 'Integraciones', 'technology_integration_label_en' => 'Integrations',
            'technology_collaboration_label_es' => 'Colaboración', 'technology_collaboration_label_en' => 'Collaboration',
            'status' => PublicationStatus::Published->value, 'is_visible' => true, 'published_at' => now(),
        ]);
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

        if (in_array($model::class, [Experience::class, ExpertiseArea::class, Project::class, Technology::class, WorkCase::class, WorkPrinciple::class, EducationEntry::class, Language::class], true)) {
            $values['key_locked'] = true;
        }

        DB::table($model->getTable())->where('id', $model->getKey())->update($values);
    }
}
