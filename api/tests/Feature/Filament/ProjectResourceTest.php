<?php

namespace Tests\Feature\Filament;

use App\Domain\Content\Actions\SyncProjectImages;
use App\Domain\Publishing\EditorialMutationContext;
use App\Enums\ProjectKind;
use App\Enums\PublicationStatus;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Models\Project;
use App\Models\Technology;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

final class ProjectResourceTest extends TestCase
{
    use RefreshDatabase;

    // ---- Zero projects is a valid state ----

    public function test_zero_projects_is_a_valid_state_for_the_list_page(): void
    {
        $this->assertSame(0, Project::query()->count());
        $this->authenticateAdmin();

        $this->get(ListProjects::getUrl())->assertOk();
    }

    // ---- Access ----

    public function test_administrator_can_reach_project_pages(): void
    {
        $this->authenticateAdmin();
        $project = Project::factory()->create();

        $this->get(ListProjects::getUrl())->assertOk();
        $this->get(CreateProject::getUrl())->assertOk();
        $this->get(EditProject::getUrl(['record' => $project]))->assertOk();
    }

    public function test_guest_is_redirected_from_project_list_page(): void
    {
        $this->get(ListProjects::getUrl())->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_non_administrator_is_denied_project_list_page(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user);

        $this->get(ListProjects::getUrl())->assertForbidden();
    }

    // ---- No forbidden fields ----

    public function test_project_form_never_exposes_forbidden_fields(): void
    {
        $this->authenticateAdmin();
        $project = Project::factory()->create();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->assertFormFieldDoesNotExist('confidentiality_note')
            ->assertFormFieldDoesNotExist('technical_description')
            ->assertFormFieldDoesNotExist('name')
            ->assertFormFieldDoesNotExist('video_url')
            ->assertFormFieldDoesNotExist('status')
            ->assertFormFieldDoesNotExist('is_visible')
            ->assertFormFieldDoesNotExist('published_at')
            ->assertFormFieldDoesNotExist('key_locked')
            ->assertFormFieldDoesNotExist('position');
    }

    // ---- Required pairs ----

    public function test_all_bilingual_pairs_are_required_to_publish(): void
    {
        $this->authenticateAdmin();

        foreach (['title', 'role', 'summary', 'problem', 'solution', 'result'] as $field) {
            $attributes = $this->completeAttributes();
            $attributes["{$field}_en"] = null;
            $project = Project::factory()->create($attributes);

            Livewire::test(EditProject::class, ['record' => $project->getKey()])
                ->callAction('publish')
                ->assertNotified('Publish failed');

            $this->assertSame(PublicationStatus::Draft, $project->refresh()->status, "Expected publish to fail when [{$field}_en] is missing.");
        }
    }

    public function test_project_publishes_once_all_four_pairs_are_complete(): void
    {
        $project = Project::factory()->create($this->completeAttributes());
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->callAction('publish')
            ->assertNotified('Publish succeeded');

        $this->assertSame(PublicationStatus::Published, $project->refresh()->status);
    }

    // ---- Featured ----

    public function test_featured_flag_is_a_plain_boolean_field(): void
    {
        $project = Project::factory()->create(['featured' => false]);
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->fillForm(['featured' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($project->refresh()->featured);
    }

    // ---- Optional HTTPS demo/repository URLs ----

    public function test_demo_and_repository_urls_are_optional_but_must_be_valid_https_to_publish(): void
    {
        $project = Project::factory()->create(array_merge($this->completeAttributes(), [
            'demo_url' => 'http://insecure.example.test',
        ]));
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->callAction('publish')
            ->assertNotified('Publish failed');

        $this->assertSame(PublicationStatus::Draft, $project->refresh()->status);
    }

    public function test_project_publishes_with_blank_demo_and_repository_urls(): void
    {
        $project = Project::factory()->create(array_merge($this->completeAttributes(), [
            'demo_url' => null,
            'repository_url' => null,
        ]));
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->callAction('publish')
            ->assertNotified('Publish succeeded');

        $this->assertSame(PublicationStatus::Published, $project->refresh()->status);
    }

    public function test_project_publishes_with_valid_https_demo_and_repository_urls(): void
    {
        $project = Project::factory()->create(array_merge($this->completeAttributes(), [
            'demo_url' => 'https://demo.example.test',
            'repository_url' => 'https://github.example.test/synthetic/repo',
        ]));
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->callAction('publish')
            ->assertNotified('Publish succeeded');

        $this->assertSame(PublicationStatus::Published, $project->refresh()->status);
    }

    // ---- Contextual ordered technologies ----

    public function test_saving_technologies_persists_pivot_position_matching_repeater_order(): void
    {
        $project = Project::factory()->create();
        $first = Technology::factory()->create(['key' => 'synthetic-technology-alpha']);
        $second = Technology::factory()->create(['key' => 'synthetic-technology-beta']);
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->fillForm([
                'technologies' => [
                    ['technology_id' => $second->id],
                    ['technology_id' => $first->id],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $pivots = DB::table('project_technology')->where('project_id', $project->getKey())->orderBy('position')->get();
        $this->assertCount(2, $pivots);
        $this->assertSame($second->id, $pivots[0]->technology_id);
        $this->assertSame(0, $pivots[0]->position);
        $this->assertSame($first->id, $pivots[1]->technology_id);
        $this->assertSame(1, $pivots[1]->position);
    }

    public function test_duplicate_technology_association_is_rejected_and_nothing_is_persisted(): void
    {
        $project = Project::factory()->create();
        $technology = Technology::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->fillForm([
                'technologies' => [
                    ['technology_id' => $technology->id],
                    ['technology_id' => $technology->id],
                ],
            ])
            ->call('save')
            ->assertNotified('Save failed');

        $this->assertSame(0, DB::table('project_technology')->where('project_id', $project->getKey())->count());
    }

    public function test_editing_an_unrelated_field_does_not_wipe_existing_technologies(): void
    {
        $project = Project::factory()->create(['summary_es' => 'Resumen existente sintético']);
        $technology = Technology::factory()->create();
        $project->technologies()->attach($technology, ['position' => 0]);
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->fillForm(['summary_es' => 'Resumen actualizado sintético'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Saved');

        $project = $project->fresh();
        $this->assertSame('Resumen actualizado sintético', $project->summary_es);
        $technologies = $project->technologies;
        $this->assertCount(1, $technologies);
        $this->assertSame($technology->getKey(), $technologies[0]->getKey());
        $this->assertSame(0, $technologies[0]->pivot->position);
    }

    // ---- Transitions ----

    public function test_project_editorial_transitions_go_through_editorial_actions(): void
    {
        $project = Project::factory()->create($this->completeAttributes());
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->assertActionExists('publish')
            ->callAction('publish')
            ->assertNotified('Publish succeeded');

        $project->refresh();
        $this->assertSame(PublicationStatus::Published, $project->status);
        $this->assertFalse($project->is_visible);

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->callAction('show')
            ->assertNotified('Show succeeded');
        $this->assertTrue($project->refresh()->is_visible);

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->callAction('hide')
            ->assertNotified('Hide succeeded');
        $this->assertFalse($project->refresh()->is_visible);

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->assertActionExists('return_to_draft', checkActionUsing: fn ($action) => $action->isConfirmationRequired())
            ->callAction('return_to_draft')
            ->assertNotified('Return to draft succeeded');
        $this->assertSame(PublicationStatus::Draft, $project->refresh()->status);
    }

    // ---- Reorder (top-level position) ----

    public function test_project_reorder_action_updates_position_via_reorder_content(): void
    {
        $project = Project::factory()->create(['position' => 0]);
        $this->authenticateAdmin();

        Livewire::test(ListProjects::class)
            ->callTableAction('reorder', $project, data: ['position' => 4])
            ->assertHasNoTableActionErrors();

        $this->assertSame(4, $project->refresh()->position);
    }

    public function test_project_reorder_action_rejects_a_negative_position(): void
    {
        $project = Project::factory()->create(['position' => 2]);
        $this->authenticateAdmin();

        Livewire::test(ListProjects::class)
            ->callTableAction('reorder', $project, data: ['position' => -1])
            ->assertHasTableActionErrors(['position']);

        $this->assertSame(2, $project->refresh()->position);
    }

    // ---- Key locking/change ----

    public function test_project_key_can_change_freely_before_publication(): void
    {
        $project = Project::factory()->create(['key' => 'synthetic-before']);
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->assertActionExists('change_key', checkActionUsing: fn ($action) => ! $action->isConfirmationRequired())
            ->callAction('change_key', data: ['key' => 'synthetic-after']);

        $this->assertSame('synthetic-after', $project->refresh()->key);
    }

    public function test_project_key_change_requires_confirmation_once_locked(): void
    {
        $project = Project::factory()->create(array_merge($this->completeAttributes(), ['key' => 'synthetic-locked']));
        $this->forcePublished($project, visible: true);
        $this->assertTrue($project->key_locked);
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->assertActionExists('change_key', checkActionUsing: fn ($action) => $action->isConfirmationRequired())
            ->callAction('change_key', data: ['key' => 'synthetic-relocked']);

        $this->assertSame('synthetic-relocked', $project->refresh()->key);
    }

    // ---- Hard delete ----

    public function test_deleting_a_project_cascades_to_technology_pivots_and_gallery_originals(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $project = Project::factory()->create();
        Storage::disk('local')->put('projects/cover.png', 'synthetic-png');
        app(EditorialMutationContext::class)->run(fn () => $project->images()->create([
            'position' => 0, 'private_path' => 'projects/cover.png', 'mime' => 'image/png', 'size' => 13,
            'alt_es' => 'Portada sintética', 'alt_en' => 'Synthetic cover',
        ]));
        $technology = Technology::factory()->create();
        $project->technologies()->attach($technology, ['position' => 0]);
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->assertActionExists('delete', checkActionUsing: fn ($action) => $action->isConfirmationRequired())
            ->callAction('delete');

        $this->assertSame(0, Project::query()->count());
        $this->assertSame(0, DB::table('project_images')->count());
        $this->assertSame(0, DB::table('project_technology')->count());
        $this->assertSame(1, Technology::query()->count());
        Storage::disk('local')->assertMissing('projects/cover.png');
    }

    // ---- Aggregate atomicity ----

    public function test_invalid_field_and_technology_combination_on_a_published_visible_project_fails_atomically(): void
    {
        $project = Project::factory()->create($this->completeAttributes());
        $this->forcePublished($project, visible: true);
        $originalTitleEs = $project->title_es;
        $duplicateCandidate = Technology::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->fillForm([
                'title_es' => 'Título modificado sintético',
                'technologies' => [
                    ['technology_id' => $duplicateCandidate->id],
                    ['technology_id' => $duplicateCandidate->id],
                ],
            ])
            ->call('save')
            ->assertNotified('Save failed');

        $project->refresh();
        $this->assertSame($originalTitleEs, $project->title_es);
        $this->assertSame(0, DB::table('project_technology')->where('project_id', $project->getKey())->count());
    }

    public function test_client_projects_require_a_client_name_to_publish(): void
    {
        $project = Project::factory()->create([...$this->completeAttributes(), 'kind' => ProjectKind::Client, 'client_name' => null]);
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->callAction('publish')
            ->assertNotified('Publish failed');

        $this->assertSame(PublicationStatus::Draft, $project->refresh()->status);
    }

    public function test_editing_kind_client_and_delivery_fields_persists_them(): void
    {
        $project = Project::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->fillForm([
                'kind' => ProjectKind::Client->value,
                'client_name' => 'Synthetic Client',
                'delivery_status' => 'in_use',
                'role_es' => 'Backend', 'role_en' => 'Backend',
                'result_es' => 'Resultado sintético.', 'result_en' => 'Synthetic result.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $project->refresh();
        $this->assertSame(ProjectKind::Client, $project->kind);
        $this->assertSame('Synthetic Client', $project->client_name);
        $this->assertSame('in_use', $project->delivery_status->value);
        $this->assertSame('Synthetic result.', $project->result_en);
    }

    public function test_switching_a_project_to_personal_clears_its_client_name(): void
    {
        $project = Project::factory()->client()->create();
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->fillForm(['kind' => ProjectKind::Personal->value])
            ->call('save')
            ->assertHasNoFormErrors();

        $project->refresh();
        $this->assertSame(ProjectKind::Personal, $project->kind);
        $this->assertNull($project->client_name);
    }

    // ---- Screenshot gallery ----

    /**
     * `Repeater::fake()` is Filament's dedicated testing helper: it disables
     * the repeater's UUID item keys (`CanGenerateUuids`) so item keys stay
     * the plain, predictable indices this test asserts against. Each new
     * screenshot is added through the repeater's own "add" action (not a
     * single `fillForm(['images' => [...]])` call) because only that action
     * runs the item's schema `fill()`, which is what hydrates a `FileUpload`
     * field's internal array-backed state; setting a file directly on an
     * item that was never hydrated leaves the raw state as a bare
     * `TemporaryUploadedFile`, which fails Filament's own array-typed
     * validation rule for the field.
     */
    public function test_uploading_screenshots_through_the_form_stores_them_in_order(): void
    {
        Repeater::fake();
        Storage::fake('local');
        Storage::fake('public');
        $project = Project::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->callFormComponentAction('images', 'add')
            ->callFormComponentAction('images', 'add')
            ->fillForm([
                'images.0.file' => $this->png('first.png'),
                'images.0.alt_es' => 'Primera', 'images.0.alt_en' => 'First',
                'images.1.file' => $this->png('second.png'),
                'images.1.alt_es' => 'Segunda', 'images.1.alt_en' => 'Second',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['First', 'Second'], $project->images()->pluck('alt_en')->all());
    }

    /**
     * No screenshot here carries a real upload: the repeater's own
     * `maxItems(12)` rule is expected to reject the array before Filament
     * even reaches each item's file-specific validation, so a thirteenth
     * blank item is enough to exercise it (see the note on the passing
     * test above for why a real upload needs the item hydrated through the
     * repeater's "add" action instead of a single `fillForm` call).
     */
    public function test_the_gallery_form_rejects_more_than_twelve_screenshots(): void
    {
        $project = Project::factory()->create();
        $this->authenticateAdmin();
        $items = array_map(fn (int $index): array => ['id' => null, 'file' => null, 'alt_es' => 'Captura', 'alt_en' => 'Shot'], range(0, 12));

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->fillForm(['images' => $items])
            ->call('save')
            ->assertHasFormErrors(['images']);

        $this->assertSame(0, $project->images()->count());
    }

    public function test_saving_unrelated_fields_keeps_existing_screenshots(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $project = Project::factory()->create();
        $image = app(SyncProjectImages::class)($project, [
            ['id' => null, 'upload' => $this->png('cover.png'), 'alt_es' => 'Portada', 'alt_en' => 'Cover'],
        ])->images[0];
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->fillForm(['summary_es' => 'Resumen actualizado sintético'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame([$image->id], $project->images()->pluck('id')->all());
        Storage::disk('local')->assertExists($image->private_path);
    }

    /**
     * Regression: `EditRecord::save()` never refills the form after
     * `handleRecordUpdate()`, so without an explicit refresh the repeater
     * keeps the just-uploaded item's hidden `id` at null and its `file` at
     * the now-consumed temporary upload. A second save would otherwise see
     * an id-less item with an upload and delete/recreate the row (and, on a
     * visible project, mint a new public path) instead of just updating its
     * alt text.
     */
    public function test_saving_twice_keeps_the_same_screenshot_row_and_does_not_restage_the_upload(): void
    {
        Repeater::fake();
        Storage::fake('local');
        Storage::fake('public');
        $project = Project::factory()->create();
        $this->authenticateAdmin();

        $test = Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->callFormComponentAction('images', 'add')
            ->fillForm([
                'images.0.file' => $this->png('first.png'),
                'images.0.alt_es' => 'Primera', 'images.0.alt_en' => 'First',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $image = $project->images()->sole();

        $test->fillForm(['images.0.alt_en' => 'First updated'])
            ->call('save')
            ->assertHasNoFormErrors();

        $images = $project->images()->get();
        $this->assertCount(1, $images);
        $this->assertSame($image->id, $images[0]->id);
        $this->assertSame($image->private_path, $images[0]->private_path);
        $this->assertSame('First updated', $images[0]->alt_en);
    }

    /**
     * The panel has no outer database transaction (`UpdateContentWithTechnologies`
     * commits on its own), so by the time a gallery sync fails, the
     * field/technology update is already persisted. The admin must be told
     * the save was only partial, and the page's record must reflect the
     * persisted field change rather than silently reverting it in the UI.
     */
    public function test_a_gallery_failure_reports_a_partial_save_and_keeps_the_field_edit(): void
    {
        Repeater::fake();
        Storage::fake('local');
        Storage::fake('public');
        $project = Project::factory()->create($this->completeAttributes());
        $this->forcePublished($project, visible: true);
        $this->authenticateAdmin();

        $test = Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->callFormComponentAction('images', 'add')
            ->fillForm([
                'summary_es' => 'Resumen actualizado sintético',
                'images.0.file' => $this->png('shot.png'),
                'images.0.alt_es' => 'Captura',
                'images.0.alt_en' => null,
            ])
            ->call('save')
            ->assertNotified('Save partially failed');

        $this->assertSame('Resumen actualizado sintético', $project->refresh()->summary_es);
        $this->assertSame(0, $project->images()->count());
        $this->assertSame('Resumen actualizado sintético', $test->instance()->getRecord()->summary_es);
    }

    /**
     * Regression: a post-commit failure to remove a replaced screenshot's
     * old private file must not be reported the same way as a real save
     * failure. It must not fall into the "Save partially failed" path, and
     * the repeater must still be refilled with the saved row's id, exactly
     * as the success path does, so a further save does not see a stale
     * id-less item and duplicate the row that was already saved.
     */
    public function test_a_gallery_retire_failure_is_reported_without_losing_or_duplicating_the_saved_screenshot(): void
    {
        Repeater::fake();
        Storage::fake('local');
        Storage::fake('public');
        $project = Project::factory()->create();
        $image = app(SyncProjectImages::class)($project, [
            ['id' => null, 'upload' => $this->png('first.png'), 'alt_es' => 'Primera', 'alt_en' => 'First'],
        ])->images[0];
        $oldPrivatePath = $image->private_path;
        $this->authenticateAdmin();

        // The mounted form already carries the existing image's id (from
        // mutateFormDataBeforeFill()), so only its file changes here: no
        // second real upload happens on this Livewire test instance, which
        // is what the "replace, then retry with no changes" scenario below
        // needs to reproduce.
        //
        // Overriding the manager's own cached "local" disk entry (its
        // public set() method, the same one Storage::fake() itself uses)
        // keeps every other disk resolution's real behaviour intact,
        // including Livewire's own lazily-faked "tmp-for-tests" upload
        // disk. Swapping the whole Storage facade root instead (as the
        // domain-level equivalent test does) loses that caching and breaks
        // Livewire's upload plumbing.
        $local = Storage::disk('local');
        $failingLocal = Mockery::mock($local)->makePartial();
        $failingLocal->shouldReceive('delete')->andReturnUsing(
            fn (string $path): bool => $path === $oldPrivatePath ? false : $local->delete($path)
        );
        $failingLocal->shouldReceive('exists')->andReturnUsing(
            fn (string $path): bool => $path === $oldPrivatePath ? true : $local->exists($path)
        );
        Storage::set('local', $failingLocal);

        // assertNotified()/assertNotNotified() both pull (and clear) the
        // same session-flashed notifications, so only one of the two can
        // observe them per response; asserting the "Saved" title alone is
        // enough here because the retire-failure branch in
        // handleRecordUpdate() never reaches the "Save partially failed"
        // notification (it returns before that catch block).
        $test = Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->fillForm(['images.0.file' => $this->png('second.png')])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Saved');

        $images = $project->images()->get();
        $this->assertCount(1, $images);
        $this->assertSame($image->id, $images[0]->id);
        $this->assertNotSame($oldPrivatePath, $images[0]->private_path);

        // A further save with no new upload must not duplicate the row: the
        // repeater must already hold the saved id from the retire-failure
        // save above, not a stale id-less item with a leftover upload.
        $test->call('save')->assertHasNoFormErrors();

        $images = $project->images()->get();
        $this->assertCount(1, $images);
        $this->assertSame($image->id, $images[0]->id);
    }

    private function png(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL9SAAAAABJRU5ErkJggg=='),
        );
    }

    private function completeAttributes(): array
    {
        return [
            'title_es' => 'Proyecto técnico sintético', 'title_en' => 'Synthetic technical project',
            'role_es' => 'Rol técnico sintético', 'role_en' => 'Synthetic technical role',
            'delivery_status' => 'in_development',
            'summary_es' => 'Resumen técnico sintético.', 'summary_en' => 'Synthetic technical summary.',
            'problem_es' => 'Problema técnico sintético.', 'problem_en' => 'Synthetic technical problem.',
            'solution_es' => 'Solución técnica sintética.', 'solution_en' => 'Synthetic technical solution.',
            'result_es' => 'Resultado técnico sintético.', 'result_en' => 'Synthetic technical result.',
        ];
    }

    private function authenticateAdmin(): User
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        return $admin;
    }

    /**
     * Forces a row directly into a published state via the query builder
     * (bypassing Eloquent events, matching the established fixture pattern
     * in ExperienceResourceTest/EditorialActionTest) since the editorial
     * mutation guard forbids creating or updating into a published state
     * through Eloquent outside of the domain actions.
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
}
