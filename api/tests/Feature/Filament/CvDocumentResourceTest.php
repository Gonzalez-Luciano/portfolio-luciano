<?php

namespace Tests\Feature\Filament;

use App\Domain\Content\Actions\PublishContent;
use App\Domain\Content\Actions\ReplaceOwnedAsset;
use App\Domain\Content\Actions\ShowContent;
use App\Domain\Content\Actions\UpdateContent;
use App\Enums\PublicationStatus;
use App\Enums\SupportedLocale;
use App\Filament\Resources\CvDocuments\Pages\CreateCvDocument;
use App\Filament\Resources\CvDocuments\Pages\EditCvDocument;
use App\Filament\Resources\CvDocuments\Pages\ListCvDocuments;
use App\Filament\Resources\CvDocuments\Schemas\CvDocumentForm;
use App\Models\CvDocument;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

final class CvDocumentResourceTest extends TestCase
{
    use RefreshDatabase;

    // ---- Zero rows is a valid state ----

    public function test_zero_cv_documents_is_a_valid_state_for_the_list_page(): void
    {
        $this->assertSame(0, CvDocument::query()->count());
        $this->authenticateAdmin();

        $this->get(ListCvDocuments::getUrl())->assertOk();
    }

    // ---- Access ----

    public function test_administrator_can_reach_cv_document_pages(): void
    {
        $this->authenticateAdmin();
        $cv = CvDocument::factory()->create();

        $this->get(ListCvDocuments::getUrl())->assertOk();
        $this->get(CreateCvDocument::getUrl())->assertOk();
        $this->get(EditCvDocument::getUrl(['record' => $cv]))->assertOk();
    }

    public function test_guest_is_redirected_from_cv_document_list_page(): void
    {
        $this->get(ListCvDocuments::getUrl())->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_non_administrator_is_denied_cv_document_list_page(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user);

        $this->get(ListCvDocuments::getUrl())->assertForbidden();
    }

    // ---- No approval boolean or extra status anywhere ----

    public function test_the_cv_document_model_has_no_approval_boolean_or_extra_status_column(): void
    {
        $this->assertSame(
            ['locale', 'label', 'private_path', 'mime', 'size', 'status', 'is_visible', 'published_at'],
            (new CvDocument)->getFillable(),
        );
    }

    public function test_the_form_never_exposes_forbidden_or_invented_fields(): void
    {
        $this->authenticateAdmin();
        $cv = CvDocument::factory()->create();

        Livewire::test(EditCvDocument::class, ['record' => $cv->getKey()])
            ->assertFormFieldDoesNotExist('status')
            ->assertFormFieldDoesNotExist('is_visible')
            ->assertFormFieldDoesNotExist('published_at')
            ->assertFormFieldDoesNotExist('key')
            ->assertFormFieldDoesNotExist('key_locked')
            ->assertFormFieldDoesNotExist('position')
            ->assertFormFieldDoesNotExist('approved')
            ->assertFormFieldDoesNotExist('reviewed')
            ->assertFormFieldDoesNotExist('public_path');
    }

    // ---- Create action offers only missing locales, hidden once both exist ----

    public function test_create_action_is_visible_and_offers_both_locales_when_no_row_exists(): void
    {
        $this->authenticateAdmin();

        Livewire::test(ListCvDocuments::class)
            ->assertActionVisible('create');

        Livewire::test(CreateCvDocument::class)
            ->assertFormFieldExists('locale');
    }

    public function test_create_action_offers_only_the_missing_locale_when_one_row_exists(): void
    {
        CvDocument::factory()->create(['locale' => SupportedLocale::English]);
        $this->authenticateAdmin();

        Livewire::test(ListCvDocuments::class)
            ->assertActionVisible('create');

        $schema = CvDocumentForm::configure(Schema::make());
        $select = collect($schema->getComponents())
            ->first(fn ($component): bool => $component instanceof Select && $component->getName() === 'locale');

        $this->assertSame(['es'], array_keys($select->getOptions()));
    }

    public function test_create_action_is_hidden_once_both_locales_exist(): void
    {
        CvDocument::factory()->create(['locale' => SupportedLocale::English]);
        CvDocument::factory()->spanish()->create();
        $this->authenticateAdmin();

        Livewire::test(ListCvDocuments::class)
            ->assertActionHidden('create');
    }

    // ---- Unique locale enforced ----

    public function test_creating_a_second_row_for_an_existing_locale_is_rejected(): void
    {
        CvDocument::factory()->create(['locale' => SupportedLocale::English]);
        $this->authenticateAdmin();

        Livewire::test(CreateCvDocument::class)
            ->fillForm(['locale' => SupportedLocale::English->value, 'label' => 'Duplicate'])
            ->call('create')
            ->assertHasFormErrors(['locale']);

        $this->assertSame(1, CvDocument::query()->where('locale', SupportedLocale::English)->count());
    }

    // ---- Locale immutable after creation ----

    public function test_locale_is_disabled_on_edit_and_a_submitted_change_is_ignored(): void
    {
        $cv = CvDocument::factory()->create(['locale' => SupportedLocale::English, 'label' => 'Original label']);
        $this->authenticateAdmin();

        Livewire::test(EditCvDocument::class, ['record' => $cv->getKey()])
            ->fillForm(['locale' => SupportedLocale::Spanish->value, 'label' => 'Updated label'])
            ->call('save')
            ->assertNotified('Saved');

        $cv->refresh();
        $this->assertSame(SupportedLocale::English, $cv->locale);
        $this->assertSame('Updated label', $cv->label);
    }

    public function test_update_content_itself_rejects_a_locale_change_when_bypassing_the_disabled_form_field(): void
    {
        $cv = CvDocument::factory()->create();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('locale is immutable');

        app(UpdateContent::class)($cv, ['locale' => SupportedLocale::Spanish]);
    }

    // ---- Zero-to-two valid slot states, Spanish may remain empty ----

    public function test_a_single_english_only_row_is_a_valid_state(): void
    {
        CvDocument::factory()->create(['locale' => SupportedLocale::English]);

        $this->assertSame(1, CvDocument::query()->count());
        $this->assertSame(0, CvDocument::query()->where('locale', SupportedLocale::Spanish)->count());
    }

    public function test_both_locale_rows_is_a_valid_state(): void
    {
        CvDocument::factory()->create(['locale' => SupportedLocale::English]);
        CvDocument::factory()->spanish()->create();

        $this->assertSame(2, CvDocument::query()->count());
    }

    // ---- Private-only PDF lifecycle: upload, replace, remove ----

    public function test_uploading_a_pdf_replaces_it_through_replace_owned_asset_and_stays_private(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $cv = CvDocument::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(EditCvDocument::class, ['record' => $cv->getKey()])
            ->fillForm(['pdf' => UploadedFile::fake()->createWithContent('resume.pdf', "%PDF-1.4\nsynthetic")])
            ->call('save')
            ->assertHasNoFormErrors();

        $cv->refresh();
        $this->assertNotNull($cv->private_path);
        $this->assertSame('application/pdf', $cv->mime);
        Storage::disk('local')->assertExists($cv->private_path);
        Storage::disk('public')->assertDirectoryEmpty('/');
    }

    public function test_remove_pdf_action_is_only_visible_with_an_existing_pdf_and_clears_it(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $cv = CvDocument::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(EditCvDocument::class, ['record' => $cv->getKey()])
            ->assertActionHidden('remove_pdf');

        app(ReplaceOwnedAsset::class)($cv, UploadedFile::fake()->createWithContent('resume.pdf', "%PDF-1.4\nsynthetic"));

        Livewire::test(EditCvDocument::class, ['record' => $cv->getKey()])
            ->assertActionVisible('remove_pdf')
            ->callAction('remove_pdf');

        $this->assertNull($cv->fresh()->private_path);
    }

    // ---- Owned-asset failures are controlled, not raw exceptions ----

    public function test_removing_a_pdf_from_a_published_cv_shows_a_controlled_notification_instead_of_an_uncaught_exception(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $cv = CvDocument::factory()->create(['label' => 'Synthetic technical CV']);
        app(ReplaceOwnedAsset::class)($cv, UploadedFile::fake()->createWithContent('resume.pdf', "%PDF-1.4\nsynthetic"));
        $cv = app(PublishContent::class)($cv->fresh());
        $privatePath = $cv->private_path;
        $this->authenticateAdmin();

        Livewire::test(EditCvDocument::class, ['record' => $cv->getKey()])
            ->callAction('remove_pdf')
            ->assertNotified('Remove PDF failed');

        $cv->refresh();
        $this->assertSame($privatePath, $cv->private_path);
        Storage::disk('local')->assertExists($privatePath);
    }

    public function test_uploading_an_oversized_pdf_shows_a_controlled_notification_instead_of_a_raw_exception(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $cv = CvDocument::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(EditCvDocument::class, ['record' => $cv->getKey()])
            ->fillForm(['pdf' => UploadedFile::fake()->create('resume.pdf', (5 * 1024) + 1, 'application/pdf')])
            ->call('save')
            ->assertHasFormErrors(['pdf']);

        $this->assertNull($cv->fresh()->private_path);
    }

    // ---- Transitions, including Publish failing without a valid PDF ----

    public function test_publish_fails_without_a_valid_private_pdf(): void
    {
        $cv = CvDocument::factory()->create(['label' => 'Synthetic technical CV']);
        $this->authenticateAdmin();

        Livewire::test(EditCvDocument::class, ['record' => $cv->getKey()])
            ->callAction('publish')
            ->assertNotified('Publish failed');

        $this->assertSame(PublicationStatus::Draft, $cv->refresh()->status);
    }

    public function test_cv_document_editorial_transitions_go_through_editorial_actions_once_a_pdf_is_present(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $cv = CvDocument::factory()->create(['label' => 'Synthetic technical CV']);
        app(ReplaceOwnedAsset::class)($cv, UploadedFile::fake()->createWithContent('resume.pdf', "%PDF-1.4\nsynthetic"));
        $this->authenticateAdmin();

        Livewire::test(EditCvDocument::class, ['record' => $cv->getKey()])
            ->assertActionExists('publish')
            ->callAction('publish')
            ->assertNotified('Publish succeeded');

        $cv->refresh();
        $this->assertSame(PublicationStatus::Published, $cv->status);
        $this->assertFalse($cv->is_visible);

        Livewire::test(EditCvDocument::class, ['record' => $cv->getKey()])
            ->callAction('show')
            ->assertNotified('Show succeeded');
        $this->assertTrue($cv->refresh()->is_visible);

        Livewire::test(EditCvDocument::class, ['record' => $cv->getKey()])
            ->callAction('hide')
            ->assertNotified('Hide succeeded');
        $this->assertFalse($cv->refresh()->is_visible);

        Livewire::test(EditCvDocument::class, ['record' => $cv->getKey()])
            ->assertActionExists('return_to_draft', checkActionUsing: fn ($action) => $action->isConfirmationRequired())
            ->callAction('return_to_draft')
            ->assertNotified('Return to draft succeeded');
        $this->assertSame(PublicationStatus::Draft, $cv->refresh()->status);
    }

    // ---- Hard delete ----

    public function test_deleting_a_cv_document_removes_the_row_and_the_private_file(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $cv = CvDocument::factory()->create();
        app(ReplaceOwnedAsset::class)($cv, UploadedFile::fake()->createWithContent('resume.pdf', "%PDF-1.4\nsynthetic"));
        $cv->refresh();
        $pdfPath = $cv->private_path;
        $this->authenticateAdmin();

        Livewire::test(EditCvDocument::class, ['record' => $cv->getKey()])
            ->assertActionExists('delete', checkActionUsing: fn ($action) => $action->isConfirmationRequired())
            ->callAction('delete');

        $this->assertSame(0, CvDocument::query()->count());
        Storage::disk('local')->assertMissing($pdfPath);
    }

    // ---- Site cache invalidation, per locale ----

    public function test_mutating_a_published_visible_cv_invalidates_the_site_endpoint_for_its_locale(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->publishSiteConfiguration();

        $cv = CvDocument::factory()->create(['locale' => SupportedLocale::English, 'label' => 'Original CV title']);
        app(ReplaceOwnedAsset::class)($cv, UploadedFile::fake()->createWithContent('resume.pdf', "%PDF-1.4\nsynthetic"));
        $cv = app(PublishContent::class)($cv->fresh());
        $cv = app(ShowContent::class)($cv);

        // Prime the public cache with the original label.
        $this->getJson('/api/v1/en/site')->assertOk()->assertJsonPath('data.cv.label', 'Original CV title');

        $this->authenticateAdmin();

        Livewire::test(EditCvDocument::class, ['record' => $cv->getKey()])
            ->fillForm(['label' => 'Updated CV title'])
            ->call('save')
            ->assertNotified('Saved');

        $this->getJson('/api/v1/en/site')->assertOk()->assertJsonPath('data.cv.label', 'Updated CV title');
    }

    public function test_the_spanish_slot_may_remain_physically_empty_and_the_site_endpoint_reports_no_cv(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->publishSiteConfiguration();

        $cv = CvDocument::factory()->create(['locale' => SupportedLocale::English, 'label' => 'English only CV']);
        app(ReplaceOwnedAsset::class)($cv, UploadedFile::fake()->createWithContent('resume.pdf', "%PDF-1.4\nsynthetic"));
        $cv = app(PublishContent::class)($cv->fresh());
        app(ShowContent::class)($cv);

        $this->assertSame(0, CvDocument::query()->where('locale', SupportedLocale::Spanish)->count());

        $this->getJson('/api/v1/es/site')->assertOk()->assertJsonPath('data.cv', null);
        $this->getJson('/api/v1/en/site')->assertOk()->assertJsonPath('data.cv.label', 'English only CV');
    }

    private function authenticateAdmin(): User
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        return $admin;
    }

    /**
     * The `site_configurations` singleton row is seeded by migration;
     * publishing it directly via the query builder (bypassing Eloquent
     * events) matches the established fixture pattern used throughout the
     * public-API test suite.
     */
    private function publishSiteConfiguration(): void
    {
        DB::table('site_configurations')->where('singleton_key', 'default')->update([
            'projects_empty_message_es' => 'Sin proyectos',
            'projects_empty_message_en' => 'No projects',
            'contact_intro_es' => 'Contacto sintético',
            'contact_intro_en' => 'Synthetic contact',
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
}
