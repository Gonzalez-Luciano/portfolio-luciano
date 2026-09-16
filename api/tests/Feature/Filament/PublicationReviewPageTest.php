<?php

namespace Tests\Feature\Filament;

use App\Domain\Content\Actions\ReplaceOwnedAsset;
use App\Domain\Publishing\EditorialMutationContext;
use App\Enums\PublicationStatus;
use App\Enums\SupportedLocale;
use App\Filament\Pages\EditProfile;
use App\Filament\Pages\EditSiteConfiguration;
use App\Filament\Pages\ReviewContent;
use App\Filament\Resources\CvDocuments\Pages\EditCvDocument;
use App\Filament\Resources\CvDocuments\Pages\ListCvDocuments;
use App\Filament\Resources\EducationEntries\Pages\EditEducationEntry;
use App\Filament\Resources\Experiences\Pages\EditExperience;
use App\Filament\Resources\Experiences\Pages\ListExperiences;
use App\Filament\Resources\ExpertiseAreas\Pages\EditExpertiseArea;
use App\Filament\Resources\ExpertiseAreas\Pages\ListExpertiseAreas;
use App\Filament\Resources\Languages\Pages\EditLanguage;
use App\Filament\Resources\ProfessionalLinks\Pages\EditProfessionalLink;
use App\Filament\Resources\ProfessionalLinks\Pages\ListProfessionalLinks;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Technologies\Pages\EditTechnology;
use App\Filament\Resources\Technologies\Pages\ListTechnologies;
use App\Filament\Resources\WorkCases\Pages\EditWorkCase;
use App\Filament\Resources\WorkCases\Pages\ListWorkCases;
use App\Filament\Resources\WorkPrinciples\Pages\EditWorkPrinciple;
use App\Filament\Resources\WorkPrinciples\Pages\ListWorkPrinciples;
use App\Filament\Support\ReviewLink;
use App\Models\CvDocument;
use App\Models\EducationEntry;
use App\Models\Experience;
use App\Models\ExpertiseArea;
use App\Models\Language;
use App\Models\ProfessionalLink;
use App\Models\Profile;
use App\Models\Project;
use App\Models\SiteConfiguration;
use App\Models\Technology;
use App\Models\User;
use App\Models\WorkCase;
use App\Models\WorkPrinciple;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class PublicationReviewPageTest extends TestCase
{
    use RefreshDatabase;

    // ---- Access, across every manageable entity ----

    #[DataProvider('manageableEntities')]
    public function test_authorized_admin_can_reach_the_review_page_for_any_entity(string $type, \Closure $makeRecord): void
    {
        $record = $makeRecord();
        $this->authenticateAdmin();

        $this->get(ReviewContent::getUrl(['type' => $type, 'record' => $record->getKey()]))->assertOk();
    }

    #[DataProvider('manageableEntities')]
    public function test_guest_is_redirected_from_the_review_page(string $type, \Closure $makeRecord): void
    {
        $record = $makeRecord();

        $this->get(ReviewContent::getUrl(['type' => $type, 'record' => $record->getKey()]))
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    #[DataProvider('manageableEntities')]
    public function test_non_administrator_is_denied_the_review_page(string $type, \Closure $makeRecord): void
    {
        $record = $makeRecord();
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user);

        $this->get(ReviewContent::getUrl(['type' => $type, 'record' => $record->getKey()]))->assertForbidden();
    }

    public static function manageableEntities(): array
    {
        return [
            'profile' => ['profile', fn () => Profile::query()->where('singleton_key', 'default')->firstOrFail()],
            'site-configuration' => ['site-configuration', fn () => SiteConfiguration::query()->where('singleton_key', 'default')->firstOrFail()],
            'experience' => ['experience', fn () => Experience::factory()->create()],
            'work-case' => ['work-case', fn () => WorkCase::factory()->create()],
            'project' => ['project', fn () => Project::factory()->create()],
            'technology' => ['technology', fn () => Technology::factory()->create()],
            'expertise-area' => ['expertise-area', fn () => ExpertiseArea::factory()->create()],
            'work-principle' => ['work-principle', fn () => WorkPrinciple::factory()->create()],
            'education-entry' => ['education-entry', fn () => EducationEntry::factory()->create()],
            'language' => ['language', fn () => Language::factory()->create()],
            'professional-link' => ['professional-link', fn () => ProfessionalLink::factory()->create()],
            'cv-document' => ['cv-document', fn () => CvDocument::factory()->create()],
        ];
    }

    // ---- Unknown type/record are a controlled 404 ----

    public function test_an_unknown_type_is_a_not_found_response(): void
    {
        $this->authenticateAdmin();
        $experience = Experience::factory()->create();

        $this->get(ReviewContent::getUrl(['type' => 'not-a-real-type', 'record' => $experience->getKey()]))
            ->assertNotFound();
    }

    public function test_a_nonexistent_record_id_is_a_not_found_response(): void
    {
        $this->authenticateAdmin();

        $this->get(ReviewContent::getUrl(['type' => 'experience', 'record' => 999999]))
            ->assertNotFound();
    }

    // ---- Read-only: no mutating action of any kind ----

    public function test_the_review_page_exposes_no_mutating_action(): void
    {
        $experience = Experience::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(ReviewContent::class, ['type' => 'experience', 'record' => $experience->getKey()])
            ->assertActionDoesNotExist('publish')
            ->assertActionDoesNotExist('show')
            ->assertActionDoesNotExist('hide')
            ->assertActionDoesNotExist('return_to_draft')
            ->assertActionDoesNotExist('delete')
            ->assertActionDoesNotExist('change_key');

        $this->assertFalse(method_exists(ReviewContent::class, 'save'));
    }

    public function test_the_review_page_has_no_form(): void
    {
        $experience = Experience::factory()->create();
        $this->authenticateAdmin();

        $html = Livewire::test(ReviewContent::class, ['type' => 'experience', 'record' => $experience->getKey()])
            ->html();

        $this->assertStringNotContainsString('wire:submit', $html);
        $this->assertStringNotContainsString('<form', $html);
    }

    // ---- Spanish and English are shown together, not behind a tab click ----

    public function test_spanish_and_english_content_are_both_present_in_the_same_render(): void
    {
        $experience = Experience::factory()->create([
            'role_es' => 'Ingeniero de revisión sintético',
            'role_en' => 'Synthetic review engineer',
        ]);
        $this->authenticateAdmin();

        $component = Livewire::test(ReviewContent::class, ['type' => 'experience', 'record' => $experience->getKey()]);
        $component->assertSee('Ingeniero de revisión sintético')
            ->assertSee('Synthetic review engineer');
    }

    // ---- Experience: dates, order, highlights, contextual technologies ----

    public function test_experience_review_shows_dates_position_highlights_and_ordered_technologies(): void
    {
        $experience = Experience::factory()->create([
            'position' => 3,
            'start_year' => 2020, 'start_month' => 3,
            'end_year' => 2024, 'end_month' => 6,
        ]);
        $this->insertHighlight($experience, 'Primer hito sintético', 'First synthetic highlight', 0);
        $this->insertHighlight($experience, 'Segundo hito sintético', 'Second synthetic highlight', 1);
        $second = Technology::factory()->create(['key' => 'synthetic-technology-second', 'name' => 'Synthetic Second']);
        $first = Technology::factory()->create(['key' => 'synthetic-technology-first', 'name' => 'Synthetic First']);
        $experience->technologies()->attach($first, ['position' => 0]);
        $experience->technologies()->attach($second, ['position' => 1]);
        $this->authenticateAdmin();

        $component = Livewire::test(ReviewContent::class, ['type' => 'experience', 'record' => $experience->getKey()])
            ->assertSee('2020-03')
            ->assertSee('2024-06')
            ->assertSee('Position: 3')
            ->assertSee('Primer hito sintético')
            ->assertSee('Second synthetic highlight')
            ->assertSee('Synthetic First')
            ->assertSee('Synthetic Second');

        $review = $component->instance()->review;
        $this->assertSame(
            ['Synthetic First', 'Synthetic Second'],
            array_column($review['relationships']['Contextual technologies'], 'value'),
        );
    }

    public function test_experience_review_shows_current_for_an_open_ended_experience(): void
    {
        $experience = Experience::factory()->current()->create();
        $this->authenticateAdmin();

        Livewire::test(ReviewContent::class, ['type' => 'experience', 'record' => $experience->getKey()])
            ->assertSee('Current');
    }

    // ---- WorkCase: bilingual content and ordered contextual technologies ----

    public function test_work_case_review_shows_bilingual_content_and_ordered_technologies(): void
    {
        $workCase = WorkCase::factory()->create([
            'title_es' => 'Caso técnico sintético',
            'title_en' => 'Synthetic technical case',
        ]);
        $second = Technology::factory()->create(['key' => 'synthetic-workcase-technology-second', 'name' => 'Synthetic Second']);
        $first = Technology::factory()->create(['key' => 'synthetic-workcase-technology-first', 'name' => 'Synthetic First']);
        $workCase->technologies()->attach($first, ['position' => 0]);
        $workCase->technologies()->attach($second, ['position' => 1]);
        $this->authenticateAdmin();

        $component = Livewire::test(ReviewContent::class, ['type' => 'work-case', 'record' => $workCase->getKey()])
            ->assertSee('Caso técnico sintético')
            ->assertSee('Synthetic technical case')
            ->assertSee('Synthetic First')
            ->assertSee('Synthetic Second');

        $review = $component->instance()->review;
        $this->assertSame(
            ['Synthetic First', 'Synthetic Second'],
            array_column($review['relationships']['Contextual technologies'], 'value'),
        );
    }

    // ---- Project: image asset info without exposing a raw path ----

    public function test_project_review_shows_gallery_asset_presence_and_metadata_without_a_raw_path(): void
    {
        $project = Project::factory()->create();
        $image = app(EditorialMutationContext::class)->run(fn () => $project->images()->create([
            'position' => 0, 'private_path' => 'projects/synthetic-review.png', 'mime' => 'image/png', 'size' => 68,
            'alt_es' => 'Captura sintética', 'alt_en' => 'Synthetic screenshot',
        ]));
        $this->authenticateAdmin();

        $component = Livewire::test(ReviewContent::class, ['type' => 'project', 'record' => $project->getKey()])
            ->assertSee('Present')
            ->assertSee('image/png');

        $this->assertStringNotContainsString($image->private_path, $component->html());
    }

    public function test_project_review_shows_asset_absent_when_no_image_exists(): void
    {
        $project = Project::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(ReviewContent::class, ['type' => 'project', 'record' => $project->getKey()])
            ->assertSee('Absent');
    }

    public function test_project_review_shows_ordered_contextual_technologies(): void
    {
        $project = Project::factory()->create();
        $second = Technology::factory()->create(['key' => 'synthetic-project-technology-second', 'name' => 'Synthetic Second']);
        $first = Technology::factory()->create(['key' => 'synthetic-project-technology-first', 'name' => 'Synthetic First']);
        $project->technologies()->attach($first, ['position' => 0]);
        $project->technologies()->attach($second, ['position' => 1]);
        $this->authenticateAdmin();

        $component = Livewire::test(ReviewContent::class, ['type' => 'project', 'record' => $project->getKey()])
            ->assertSee('Synthetic First')
            ->assertSee('Synthetic Second');

        $review = $component->instance()->review;
        $this->assertSame(
            ['Synthetic First', 'Synthetic Second'],
            array_column($review['relationships']['Contextual technologies'], 'value'),
        );
    }

    // ---- CvDocument: PDF asset presence/metadata ----

    public function test_cv_document_review_shows_locale_label_and_pdf_presence(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $cv = CvDocument::factory()->create(['label' => 'Synthetic review CV']);
        app(ReplaceOwnedAsset::class)($cv, UploadedFile::fake()->createWithContent('resume.pdf', "%PDF-1.4\nsynthetic"));
        $this->authenticateAdmin();

        Livewire::test(ReviewContent::class, ['type' => 'cv-document', 'record' => $cv->getKey()])
            ->assertSee('Synthetic review CV')
            ->assertSee('Present')
            ->assertSee('application/pdf');
    }

    // ---- Technology: reverse relations ----

    public function test_technology_review_shows_every_reverse_relation(): void
    {
        $technology = Technology::factory()->create(['key' => 'synthetic-shared-technology']);
        $experience = Experience::factory()->create(['key' => 'synthetic-consumer-experience']);
        $workCase = WorkCase::factory()->create(['key' => 'synthetic-consumer-work-case']);
        $project = Project::factory()->create(['key' => 'synthetic-consumer-project']);
        $experience->technologies()->attach($technology, ['position' => 0]);
        $workCase->technologies()->attach($technology, ['position' => 0]);
        $project->technologies()->attach($technology, ['position' => 0]);
        $this->authenticateAdmin();

        Livewire::test(ReviewContent::class, ['type' => 'technology', 'record' => $technology->getKey()])
            ->assertSee('synthetic-consumer-experience')
            ->assertSee('synthetic-consumer-work-case')
            ->assertSee('synthetic-consumer-project');
    }

    // ---- WorkPrinciple: bilingual statement ----

    public function test_work_principle_review_shows_bilingual_statement(): void
    {
        $principle = WorkPrinciple::factory()->create([
            'statement_es' => 'Principio de revisión sintético.',
            'statement_en' => 'Synthetic review principle.',
        ]);
        $this->authenticateAdmin();

        Livewire::test(ReviewContent::class, ['type' => 'work-principle', 'record' => $principle->getKey()])
            ->assertSee('Principio de revisión sintético.')
            ->assertSee('Synthetic review principle.');
    }

    // ---- ProfessionalLink: label and destination on its own review page ----

    public function test_professional_link_review_shows_label_and_destination(): void
    {
        $link = ProfessionalLink::factory()->create([
            'label_es' => 'Enlace de revisión sintético',
            'label_en' => 'Synthetic review link',
            'destination' => 'https://example.test/synthetic-review-destination',
        ]);
        $this->authenticateAdmin();

        Livewire::test(ReviewContent::class, ['type' => 'professional-link', 'record' => $link->getKey()])
            ->assertSee('Enlace de revisión sintético')
            ->assertSee('Synthetic review link')
            ->assertSee('https://example.test/synthetic-review-destination');
    }

    // ---- Exact blocking issue codes/messages, verbatim, spot-checked ----

    public function test_profile_review_shows_the_exact_blocking_issue_codes_and_messages(): void
    {
        Profile::query()->where('singleton_key', 'default')->update(['name' => null]);
        $profile = Profile::query()->where('singleton_key', 'default')->firstOrFail();
        $this->authenticateAdmin();

        Livewire::test(ReviewContent::class, ['type' => 'profile', 'record' => $profile->getKey()])
            ->assertSee('required')
            ->assertSee('This field is required for publication.')
            ->assertSee('headline_es')
            ->assertSee('Both Spanish and English values are required for publication.');
    }

    public function test_experience_review_shows_exact_required_translation_issue_for_role(): void
    {
        $experience = Experience::factory()->create(['role_es' => 'Ingeniero sintético', 'role_en' => null]);
        $this->authenticateAdmin();

        Livewire::test(ReviewContent::class, ['type' => 'experience', 'record' => $experience->getKey()])
            ->assertSee('required_translation')
            ->assertSee('role_en');
    }

    public function test_cv_document_review_shows_exact_asset_required_issue_without_a_pdf(): void
    {
        $cv = CvDocument::factory()->create(['label' => 'Synthetic CV']);
        $this->authenticateAdmin();

        Livewire::test(ReviewContent::class, ['type' => 'cv-document', 'record' => $cv->getKey()])
            ->assertSee('asset_required')
            ->assertSee('A published CV requires a private PDF.');
    }

    public function test_a_complete_record_shows_no_blocking_issues(): void
    {
        $expertiseArea = ExpertiseArea::factory()->draft()->create([
            'title_es' => 'Área técnica sintética',
            'title_en' => 'Synthetic technical area',
            'description_es' => 'Descripción técnica sintética.',
            'description_en' => 'Synthetic technical description.',
        ]);
        $this->forcePublished($expertiseArea, visible: false);
        $this->authenticateAdmin();

        $component = Livewire::test(ReviewContent::class, ['type' => 'expertise-area', 'record' => $expertiseArea->getKey()]);

        $this->assertSame([], $component->instance()->review['issues']);
        $component->assertSee('publishable')
            ->assertSee('Área técnica sintética')
            ->assertSee('Synthetic technical area');
    }

    // ---- SiteConfiguration dependencies are informative only ----

    public function test_site_configuration_review_shows_empty_dependencies_as_informative_only_never_as_blocking(): void
    {
        $this->assertSame(0, ProfessionalLink::query()->count());
        $this->assertSame(0, ExpertiseArea::query()->count());
        $this->assertSame(0, WorkPrinciple::query()->count());
        $this->assertSame(0, CvDocument::query()->count());

        $site = SiteConfiguration::query()->where('singleton_key', 'default')->firstOrFail();
        $this->authenticateAdmin();

        $component = Livewire::test(ReviewContent::class, ['type' => 'site-configuration', 'record' => $site->getKey()])
            ->assertSee('0 published, 0 draft')
            ->assertSee('Spanish: absent, English: absent');

        $issuePaths = array_column($component->instance()->review['issues'], 'path');
        $this->assertNotContains('professional_links', $issuePaths);
        $this->assertNotContains('expertise_areas', $issuePaths);
        $this->assertNotContains('work_principles', $issuePaths);
        $this->assertNotContains('cv', $issuePaths);
    }

    public function test_site_configuration_review_reports_link_area_and_principle_counts(): void
    {
        $publishedLink = ProfessionalLink::factory()->draft()->github()->create();
        $this->forcePublished($publishedLink, visible: false);
        ProfessionalLink::factory()->draft()->email()->create();
        $publishedArea = ExpertiseArea::factory()->draft()->create();
        $this->forcePublished($publishedArea, visible: false);
        WorkPrinciple::factory()->draft()->create();
        CvDocument::factory()->create(['locale' => SupportedLocale::English]);

        $site = SiteConfiguration::query()->where('singleton_key', 'default')->firstOrFail();
        $this->authenticateAdmin();

        Livewire::test(ReviewContent::class, ['type' => 'site-configuration', 'record' => $site->getKey()])
            ->assertSee('1 published, 1 draft')
            ->assertSee('1 published, 0 draft')
            ->assertSee('0 published, 1 draft')
            ->assertSee('Spanish: absent, English: present');
    }

    // ---- No public preview URL, token, or draft serializer ----

    public function test_the_review_route_exists_only_under_the_admin_panel_and_requires_authentication(): void
    {
        $matches = collect(Route::getRoutes())->filter(
            fn ($route) => str_contains((string) $route->getName(), 'review-content'),
        );

        $this->assertCount(1, $matches);
        $route = $matches->first();
        $this->assertStringStartsWith('filament.admin.pages.', $route->getName());
        $this->assertContains(Authenticate::class, $route->middleware());
    }

    public function test_no_public_api_route_references_review_content(): void
    {
        $publicRoutes = collect(Route::getRoutes())->filter(
            fn ($route) => str_starts_with($route->uri(), 'api/'),
        );

        foreach ($publicRoutes as $route) {
            $this->assertStringNotContainsString('review', $route->uri());
        }
    }

    public function test_review_content_page_declares_no_public_url_generation_helper(): void
    {
        $this->assertFalse(method_exists(ReviewContent::class, 'publicUrl'));
        $this->assertFalse(method_exists(ReviewContent::class, 'previewUrl'));
        $this->assertFalse(method_exists(ReviewContent::class, 'token'));
    }

    // ---- Each of the ten admin surfaces links to the review page ----

    public function test_edit_profile_page_has_a_review_action(): void
    {
        $this->authenticateAdmin();

        $component = Livewire::test(EditProfile::class);
        $record = $component->instance()->record;

        $component->assertActionExists('review', checkActionUsing: fn ($action) => $action->getUrl() === ReviewLink::url($record));
    }

    public function test_edit_site_configuration_page_has_a_review_action(): void
    {
        $this->authenticateAdmin();

        $component = Livewire::test(EditSiteConfiguration::class);
        $record = $component->instance()->record;

        $component->assertActionExists('review', checkActionUsing: fn ($action) => $action->getUrl() === ReviewLink::url($record));
    }

    public function test_experience_list_table_has_a_review_row_action(): void
    {
        $experience = Experience::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(ListExperiences::class)
            ->assertTableActionExists('review');
    }

    public function test_work_case_list_table_has_a_review_row_action(): void
    {
        WorkCase::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(ListWorkCases::class)
            ->assertTableActionExists('review');
    }

    public function test_project_list_table_has_a_review_row_action(): void
    {
        Project::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(ListProjects::class)
            ->assertTableActionExists('review');
    }

    public function test_technology_list_table_has_a_review_row_action(): void
    {
        Technology::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(ListTechnologies::class)
            ->assertTableActionExists('review');
    }

    public function test_expertise_area_list_table_has_a_review_row_action(): void
    {
        ExpertiseArea::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(ListExpertiseAreas::class)
            ->assertTableActionExists('review');
    }

    public function test_work_principle_list_table_has_a_review_row_action(): void
    {
        WorkPrinciple::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(ListWorkPrinciples::class)
            ->assertTableActionExists('review');
    }

    public function test_professional_link_list_table_has_a_review_row_action(): void
    {
        ProfessionalLink::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(ListProfessionalLinks::class)
            ->assertTableActionExists('review');
    }

    public function test_cv_document_list_table_has_a_review_row_action(): void
    {
        CvDocument::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(ListCvDocuments::class)
            ->assertTableActionExists('review');
    }

    // ---- Review page links back to the record's own Edit page ----

    #[DataProvider('editUrlCases')]
    public function test_review_page_links_back_to_the_correct_edit_page(string $type, \Closure $makeRecord, \Closure $expectedEditUrl): void
    {
        $record = $makeRecord();
        $this->authenticateAdmin();

        $component = Livewire::test(ReviewContent::class, ['type' => $type, 'record' => $record->getKey()]);

        $this->assertSame($expectedEditUrl($record), $component->instance()->editUrl);
    }

    public static function editUrlCases(): array
    {
        return [
            'experience' => ['experience', fn () => Experience::factory()->create(), fn ($record) => EditExperience::getUrl(['record' => $record])],
            'work-case' => ['work-case', fn () => WorkCase::factory()->create(), fn ($record) => EditWorkCase::getUrl(['record' => $record])],
            'project' => ['project', fn () => Project::factory()->create(), fn ($record) => EditProject::getUrl(['record' => $record])],
            'technology' => ['technology', fn () => Technology::factory()->create(), fn ($record) => EditTechnology::getUrl(['record' => $record])],
            'expertise-area' => ['expertise-area', fn () => ExpertiseArea::factory()->create(), fn ($record) => EditExpertiseArea::getUrl(['record' => $record])],
            'work-principle' => ['work-principle', fn () => WorkPrinciple::factory()->create(), fn ($record) => EditWorkPrinciple::getUrl(['record' => $record])],
            'education-entry' => ['education-entry', fn () => EducationEntry::factory()->create(), fn ($record) => EditEducationEntry::getUrl(['record' => $record])],
            'language' => ['language', fn () => Language::factory()->create(), fn ($record) => EditLanguage::getUrl(['record' => $record])],
            'professional-link' => ['professional-link', fn () => ProfessionalLink::factory()->create(), fn ($record) => EditProfessionalLink::getUrl(['record' => $record])],
            'cv-document' => ['cv-document', fn () => CvDocument::factory()->create(), fn ($record) => EditCvDocument::getUrl(['record' => $record])],
        ];
    }

    /**
     * A real, tiny, valid PNG's bytes, matching the established fixture
     * pattern in ProjectResourceTest: the sandbox's PHP build has no GD
     * extension, so `UploadedFile::fake()->image()` (which shells out to
     * `imagecreatetruecolor()`) is unusable here.
     */
    private function png(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL9SAAAAABJRU5ErkJggg=='),
        );
    }

    /**
     * Publishes a fixture without going through `PublishContent`, matching
     * the established pattern in ExperienceResourceTest/ProjectResourceTest/
     * SiteCollectionResourceTest: a plain `->update()` query-builder call
     * bypasses Eloquent model events entirely, so it does not trip
     * `EditorialMutationGuard::creating()`/`updating()`'s requirement that
     * managed content only ever become published through the domain action.
     */
    private function forcePublished(Model $model, bool $visible): void
    {
        $attributes = [
            'status' => PublicationStatus::Published->value,
            'is_visible' => $visible,
            'published_at' => now(),
        ];
        if (array_key_exists('key_locked', $model->getAttributes())) {
            $attributes['key_locked'] = true;
        }
        $model::query()->whereKey($model->getKey())->update($attributes);
        $model->refresh();
    }

    private function authenticateAdmin(): User
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        return $admin;
    }

    /**
     * Inserts an experience highlight directly, bypassing Eloquent events,
     * matching the established fixture pattern (`ExperienceHighlight` may
     * only be created/updated through `UpdateExperienceAggregate`).
     */
    private function insertHighlight(Experience $experience, ?string $contentEs, ?string $contentEn, int $position): int
    {
        return (int) DB::table('experience_highlights')->insertGetId([
            'experience_id' => $experience->getKey(),
            'content_es' => $contentEs,
            'content_en' => $contentEn,
            'position' => $position,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
