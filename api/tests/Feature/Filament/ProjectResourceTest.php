<?php

namespace Tests\Feature\Filament;

use App\Domain\Content\Actions\PublishContent;
use App\Domain\Content\Actions\ReplaceOwnedAsset;
use App\Enums\PublicationStatus;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Models\Project;
use App\Models\Technology;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
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

    public function test_all_four_bilingual_pairs_are_required_to_publish(): void
    {
        $this->authenticateAdmin();

        foreach (['title', 'summary', 'problem', 'solution'] as $field) {
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

    // ---- Optional image with alt-text-required-when-present ----

    public function test_image_alt_text_is_not_required_for_publication_without_an_image(): void
    {
        $project = Project::factory()->create($this->completeAttributes());
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->callAction('publish')
            ->assertNotified('Publish succeeded');
    }

    public function test_image_alt_text_is_required_in_both_locales_when_an_image_is_present(): void
    {
        $project = Project::factory()->create(array_merge($this->completeAttributes(), [
            'image_private_path' => 'projects/synthetic.jpg',
            'image_public_path' => null,
            'image_mime' => 'image/jpeg',
            'image_size' => 1024,
            'image_alt_es' => null,
            'image_alt_en' => null,
        ]));
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->callAction('publish')
            ->assertNotified('Publish failed');

        $this->assertSame(PublicationStatus::Draft, $project->refresh()->status);
    }

    public function test_project_with_an_image_publishes_once_both_alt_locales_are_present(): void
    {
        $project = Project::factory()->create(array_merge($this->completeAttributes(), [
            'image_private_path' => 'projects/synthetic.jpg',
            'image_public_path' => null,
            'image_mime' => 'image/jpeg',
            'image_size' => 1024,
            'image_alt_es' => 'Imagen sintética',
            'image_alt_en' => 'Synthetic image',
        ]));
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->callAction('publish')
            ->assertNotified('Publish succeeded');

        $this->assertSame(PublicationStatus::Published, $project->refresh()->status);
    }

    // ---- Lifecycle-controlled image upload/removal ----

    public function test_uploading_an_image_replaces_it_through_replace_owned_asset(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $project = Project::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->fillForm([
                'image' => $this->png('cover.png'),
                'image_alt_es' => 'Imagen sintética',
                'image_alt_en' => 'Synthetic image',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $project->refresh();
        $this->assertNotNull($project->image_private_path);
        $this->assertSame('Imagen sintética', $project->image_alt_es);
        $this->assertSame('Synthetic image', $project->image_alt_en);
        Storage::disk('local')->assertExists($project->image_private_path);
    }

    public function test_uploading_a_first_image_with_alt_text_on_a_published_project_saves_both(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $project = Project::factory()->create($this->completeAttributes());
        app(PublishContent::class)($project);
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->fillForm([
                'image' => $this->png('cover.png'),
                'image_alt_es' => 'Imagen sintética',
                'image_alt_en' => 'Synthetic image',
            ])
            ->call('save')
            ->assertNotified('Saved');

        $project->refresh();
        $this->assertNotNull($project->image_private_path);
        $this->assertSame('Imagen sintética', $project->image_alt_es);
        $this->assertSame('Synthetic image', $project->image_alt_en);
        Storage::disk('local')->assertExists($project->image_private_path);
    }

    public function test_remove_image_action_is_only_visible_with_an_existing_image_and_clears_it(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $project = Project::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->assertActionHidden('remove_image');

        app(ReplaceOwnedAsset::class)($project, $this->png('cover.png'));

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->assertActionVisible('remove_image')
            ->callAction('remove_image');

        $this->assertNull($project->fresh()->image_private_path);
    }

    public function test_editing_image_alt_text_goes_through_update_owned_asset_alt_text(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $project = Project::factory()->create([
            'image_alt_es' => 'Alt original', 'image_alt_en' => 'Original alt',
        ]);
        app(ReplaceOwnedAsset::class)($project, $this->png('cover.png'));
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->fillForm(['image_alt_en' => 'Updated alt'])
            ->call('save')
            ->assertNotified('Saved');

        $this->assertSame('Updated alt', $project->fresh()->image_alt_en);
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

    public function test_deleting_a_project_cascades_to_technology_pivots_and_the_owned_asset(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $project = Project::factory()->create();
        app(ReplaceOwnedAsset::class)($project, $this->png('cover.png'));
        $project->refresh();
        $imagePath = $project->image_private_path;
        $technology = Technology::factory()->create();
        $project->technologies()->attach($technology, ['position' => 0]);
        $this->authenticateAdmin();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->assertActionExists('delete', checkActionUsing: fn ($action) => $action->isConfirmationRequired())
            ->callAction('delete');

        $this->assertSame(0, Project::query()->count());
        $this->assertSame(0, DB::table('project_technology')->count());
        // The technology itself is not deleted, only the relation row.
        $this->assertSame(1, Technology::query()->count());
        Storage::disk('local')->assertMissing($imagePath);
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

    private function completeAttributes(): array
    {
        return [
            'title_es' => 'Proyecto técnico sintético', 'title_en' => 'Synthetic technical project',
            'summary_es' => 'Resumen técnico sintético.', 'summary_en' => 'Synthetic technical summary.',
            'problem_es' => 'Problema técnico sintético.', 'problem_en' => 'Synthetic technical problem.',
            'solution_es' => 'Solución técnica sintética.', 'solution_en' => 'Synthetic technical solution.',
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

    /**
     * A real, tiny, valid PNG's bytes, matching the established fixture
     * pattern in EditorialActionTest: the sandbox's PHP build has no GD
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
}
