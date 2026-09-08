<?php

namespace Tests\Feature\Filament;

use App\Domain\Content\Actions\ReplaceOwnedAsset;
use App\Enums\PublicationStatus;
use App\Enums\TechnologyCategory;
use App\Filament\Resources\Technologies\Pages\CreateTechnology;
use App\Filament\Resources\Technologies\Pages\EditTechnology;
use App\Filament\Resources\Technologies\Pages\ListTechnologies;
use App\Filament\Resources\Technologies\Schemas\TechnologyForm;
use App\Models\Experience;
use App\Models\Project;
use App\Models\Technology;
use App\Models\User;
use App\Models\WorkCase;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

final class TechnologyResourceTest extends TestCase
{
    use RefreshDatabase;

    // ---- Zero technologies is a valid state ----

    public function test_zero_technologies_is_a_valid_state_for_the_list_page(): void
    {
        $this->assertSame(0, Technology::query()->count());
        $this->authenticateAdmin();

        $this->get(ListTechnologies::getUrl())->assertOk();
    }

    // ---- Access ----

    public function test_administrator_can_reach_technology_pages(): void
    {
        $this->authenticateAdmin();
        $technology = Technology::factory()->create();

        $this->get(ListTechnologies::getUrl())->assertOk();
        $this->get(CreateTechnology::getUrl())->assertOk();
        $this->get(EditTechnology::getUrl(['record' => $technology]))->assertOk();
    }

    public function test_guest_is_redirected_from_technology_list_page(): void
    {
        $this->get(ListTechnologies::getUrl())->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_non_administrator_is_denied_technology_list_page(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user);

        $this->get(ListTechnologies::getUrl())->assertForbidden();
    }

    // ---- No forbidden fields, and there is no fillable column for alt text ----

    public function test_technology_form_never_exposes_forbidden_fields(): void
    {
        $this->authenticateAdmin();
        $technology = Technology::factory()->create();

        Livewire::test(EditTechnology::class, ['record' => $technology->getKey()])
            ->assertFormFieldDoesNotExist('status')
            ->assertFormFieldDoesNotExist('is_visible')
            ->assertFormFieldDoesNotExist('published_at')
            ->assertFormFieldDoesNotExist('key_locked')
            ->assertFormFieldDoesNotExist('position')
            ->assertFormFieldDoesNotExist('icon_alt_es')
            ->assertFormFieldDoesNotExist('icon_alt_en');
    }

    public function test_the_technology_model_has_no_alt_text_or_approval_column(): void
    {
        $this->assertSame(
            ['key', 'key_locked', 'position', 'name', 'category', 'icon_private_path', 'icon_public_path', 'icon_mime', 'icon_size', 'status', 'is_visible', 'published_at'],
            (new Technology)->getFillable(),
        );
    }

    // ---- Canonical, non-translated name ----

    public function test_name_is_not_required_to_save_a_draft_but_is_required_to_publish(): void
    {
        $technology = Technology::factory()->create(['name' => null]);
        $this->authenticateAdmin();

        Livewire::test(EditTechnology::class, ['record' => $technology->getKey()])
            ->fillForm(['category' => TechnologyCategory::Data->value])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($technology->refresh()->name);

        Livewire::test(EditTechnology::class, ['record' => $technology->getKey()])
            ->callAction('publish')
            ->assertNotified('Publish failed');

        $this->assertSame(PublicationStatus::Draft, $technology->refresh()->status);
    }

    public function test_technology_publishes_once_a_canonical_name_is_present(): void
    {
        $technology = Technology::factory()->create(['name' => 'Synthetic Technical Component']);
        $this->authenticateAdmin();

        Livewire::test(EditTechnology::class, ['record' => $technology->getKey()])
            ->callAction('publish')
            ->assertNotified('Publish succeeded');

        $this->assertSame(PublicationStatus::Published, $technology->refresh()->status);
    }

    // ---- Closed category select, canonical order ----

    public function test_category_select_exposes_the_canonical_enum_order(): void
    {
        $this->assertSame(
            ['backend', 'data', 'integration', 'collaboration'],
            array_column(TechnologyCategory::cases(), 'value'),
        );

        $schema = TechnologyForm::configure(Schema::make());
        $select = collect($schema->getComponents())
            ->first(fn ($component): bool => $component instanceof Select && $component->getName() === 'category');

        $this->assertNotNull($select);
        $this->assertSame(['backend', 'data', 'integration', 'collaboration'], array_keys($select->getOptions()));
    }

    public function test_category_is_a_closed_selectable_value(): void
    {
        $technology = Technology::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(EditTechnology::class, ['record' => $technology->getKey()])
            ->fillForm(['category' => TechnologyCategory::Collaboration->value])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(TechnologyCategory::Collaboration, $technology->refresh()->category);
    }

    // ---- Icon lifecycle (decorative, no alt text) ----

    public function test_uploading_an_icon_replaces_it_through_replace_owned_asset(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $technology = Technology::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(EditTechnology::class, ['record' => $technology->getKey()])
            ->fillForm(['icon' => $this->png('icon.png')])
            ->call('save')
            ->assertHasNoFormErrors();

        $technology->refresh();
        $this->assertNotNull($technology->icon_private_path);
        $this->assertSame('image/png', $technology->icon_mime);
        Storage::disk('local')->assertExists($technology->icon_private_path);
    }

    public function test_uploading_a_webp_icon_is_accepted(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $technology = Technology::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(EditTechnology::class, ['record' => $technology->getKey()])
            ->fillForm(['icon' => UploadedFile::fake()->create('icon.webp', 10, 'image/webp')])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('image/webp', $technology->refresh()->icon_mime);
    }

    public function test_remove_icon_action_is_only_visible_with_an_existing_icon_and_clears_it(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $technology = Technology::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(EditTechnology::class, ['record' => $technology->getKey()])
            ->assertActionHidden('remove_icon');

        app(ReplaceOwnedAsset::class)($technology, $this->png('icon.png'));

        Livewire::test(EditTechnology::class, ['record' => $technology->getKey()])
            ->assertActionVisible('remove_icon')
            ->callAction('remove_icon');

        $this->assertNull($technology->fresh()->icon_private_path);
    }

    // ---- Relationship usage display (read-only, informational) ----

    public function test_the_edit_form_displays_current_relationship_usage_counts(): void
    {
        $technology = Technology::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(EditTechnology::class, ['record' => $technology->getKey()])
            ->assertSee('Not linked to any experience, work case, or project.');

        $experience = Experience::factory()->create();
        $workCase = WorkCase::factory()->create();
        $project = Project::factory()->create();
        $experience->technologies()->attach($technology, ['position' => 0]);
        $workCase->technologies()->attach($technology, ['position' => 0]);
        $project->technologies()->attach($technology, ['position' => 0]);

        Livewire::test(EditTechnology::class, ['record' => $technology->getKey()])
            ->assertSee('1 experience(s), 1 work case(s), 1 project(s).');
    }

    // ---- Delete restriction while relations exist ----

    public function test_deleting_a_technology_with_an_existing_relation_is_rejected_with_a_clear_notification(): void
    {
        $technology = Technology::factory()->create();
        $experience = Experience::factory()->create();
        $experience->technologies()->attach($technology, ['position' => 0]);
        $this->authenticateAdmin();

        Livewire::test(EditTechnology::class, ['record' => $technology->getKey()])
            ->callAction('delete')
            ->assertNotified('Delete failed');

        $this->assertSame(1, Technology::query()->whereKey($technology->getKey())->count());
    }

    public function test_the_database_itself_still_rejects_a_restricted_technology_delete_bypassing_the_ui(): void
    {
        $technology = Technology::factory()->create();
        $workCase = WorkCase::factory()->create();
        $workCase->technologies()->attach($technology, ['position' => 0]);

        $this->assertDatabaseRejects(function () use ($technology): void {
            DB::table('technologies')->where('id', $technology->getKey())->delete();
        });

        $this->assertSame(1, Technology::query()->whereKey($technology->getKey())->count());
    }

    public function test_deleting_an_unrelated_technology_succeeds_and_removes_its_icon(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $technology = Technology::factory()->create();
        app(ReplaceOwnedAsset::class)($technology, $this->png('icon.png'));
        $technology->refresh();
        $iconPath = $technology->icon_private_path;
        $this->authenticateAdmin();

        Livewire::test(EditTechnology::class, ['record' => $technology->getKey()])
            ->assertActionExists('delete', checkActionUsing: fn ($action) => $action->isConfirmationRequired())
            ->callAction('delete');

        $this->assertSame(0, Technology::query()->count());
        Storage::disk('local')->assertMissing($iconPath);
    }

    // ---- Transitions ----

    public function test_technology_editorial_transitions_go_through_editorial_actions(): void
    {
        $technology = Technology::factory()->create(['name' => 'Synthetic Technical Component']);
        $this->authenticateAdmin();

        Livewire::test(EditTechnology::class, ['record' => $technology->getKey()])
            ->assertActionExists('publish')
            ->callAction('publish')
            ->assertNotified('Publish succeeded');

        $technology->refresh();
        $this->assertSame(PublicationStatus::Published, $technology->status);
        $this->assertFalse($technology->is_visible);

        Livewire::test(EditTechnology::class, ['record' => $technology->getKey()])
            ->callAction('show')
            ->assertNotified('Show succeeded');
        $this->assertTrue($technology->refresh()->is_visible);

        Livewire::test(EditTechnology::class, ['record' => $technology->getKey()])
            ->callAction('hide')
            ->assertNotified('Hide succeeded');
        $this->assertFalse($technology->refresh()->is_visible);

        Livewire::test(EditTechnology::class, ['record' => $technology->getKey()])
            ->assertActionExists('return_to_draft', checkActionUsing: fn ($action) => $action->isConfirmationRequired())
            ->callAction('return_to_draft')
            ->assertNotified('Return to draft succeeded');
        $this->assertSame(PublicationStatus::Draft, $technology->refresh()->status);
    }

    // ---- Reorder (top-level position) ----

    public function test_technology_reorder_action_updates_position_via_reorder_content(): void
    {
        $technology = Technology::factory()->create(['position' => 0]);
        $this->authenticateAdmin();

        Livewire::test(ListTechnologies::class)
            ->callTableAction('reorder', $technology, data: ['position' => 3])
            ->assertHasNoTableActionErrors();

        $this->assertSame(3, $technology->refresh()->position);
    }

    public function test_technology_reorder_action_rejects_a_negative_position(): void
    {
        $technology = Technology::factory()->create(['position' => 2]);
        $this->authenticateAdmin();

        Livewire::test(ListTechnologies::class)
            ->callTableAction('reorder', $technology, data: ['position' => -1])
            ->assertHasTableActionErrors(['position']);

        $this->assertSame(2, $technology->refresh()->position);
    }

    // ---- Key locking/change ----

    public function test_technology_key_can_change_freely_before_publication(): void
    {
        $technology = Technology::factory()->create(['key' => 'synthetic-before']);
        $this->authenticateAdmin();

        Livewire::test(EditTechnology::class, ['record' => $technology->getKey()])
            ->assertActionExists('change_key', checkActionUsing: fn ($action) => ! $action->isConfirmationRequired())
            ->callAction('change_key', data: ['key' => 'synthetic-after']);

        $this->assertSame('synthetic-after', $technology->refresh()->key);
    }

    public function test_technology_key_change_requires_confirmation_once_locked(): void
    {
        $technology = Technology::factory()->create(['key' => 'synthetic-locked', 'name' => 'Synthetic Technical Component']);
        $this->forcePublished($technology, visible: true);
        $this->assertTrue($technology->key_locked);
        $this->authenticateAdmin();

        Livewire::test(EditTechnology::class, ['record' => $technology->getKey()])
            ->assertActionExists('change_key', checkActionUsing: fn ($action) => $action->isConfirmationRequired())
            ->callAction('change_key', data: ['key' => 'synthetic-relocked']);

        $this->assertSame('synthetic-relocked', $technology->refresh()->key);
    }

    // ---- Cache invalidation across all four dependent endpoints ----

    public function test_mutating_a_technology_invalidates_all_four_dependent_public_endpoints(): void
    {
        $technology = Technology::factory()->create(['name' => 'Original Technical Name']);
        $experience = Experience::factory()->create();
        $workCase = WorkCase::factory()->create();
        $project = Project::factory()->create();
        $experience->technologies()->attach($technology, ['position' => 0]);
        $workCase->technologies()->attach($technology, ['position' => 0]);
        $project->technologies()->attach($technology, ['position' => 0]);

        $this->makePublic($technology);
        $this->makePublic($experience);
        $this->makePublic($workCase);
        $this->makePublic($project);

        // Prime the public cache with the original name in every dependent endpoint.
        $this->getJson('/api/v1/en/technologies')->assertOk()->assertJsonPath('data.0.name', 'Original Technical Name');
        $this->getJson('/api/v1/en/experiences')->assertOk()->assertJsonPath('data.0.technologies.0.name', 'Original Technical Name');
        $this->getJson('/api/v1/en/work-cases')->assertOk()->assertJsonPath('data.0.technologies.0.name', 'Original Technical Name');
        $this->getJson('/api/v1/en/projects')->assertOk()->assertJsonPath('data.0.technologies.0.name', 'Original Technical Name');

        $this->authenticateAdmin();

        Livewire::test(EditTechnology::class, ['record' => $technology->getKey()])
            ->fillForm(['name' => 'Updated Technical Name'])
            ->call('save')
            ->assertNotified('Saved');

        $this->getJson('/api/v1/en/technologies')->assertOk()->assertJsonPath('data.0.name', 'Updated Technical Name');
        $this->getJson('/api/v1/en/experiences')->assertOk()->assertJsonPath('data.0.technologies.0.name', 'Updated Technical Name');
        $this->getJson('/api/v1/en/work-cases')->assertOk()->assertJsonPath('data.0.technologies.0.name', 'Updated Technical Name');
        $this->getJson('/api/v1/en/projects')->assertOk()->assertJsonPath('data.0.technologies.0.name', 'Updated Technical Name');
    }

    private function authenticateAdmin(): User
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        return $admin;
    }

    /**
     * Forces a row directly into a published+visible state via the query
     * builder (bypassing Eloquent events), matching the established
     * fixture pattern in ProjectResourceTest/ExperienceResourceTest.
     */
    private function makePublic(Model $model, array $attributes = []): void
    {
        $values = [
            'status' => PublicationStatus::Published->value,
            'is_visible' => true,
            'published_at' => now(),
            ...$attributes,
        ];

        if (array_key_exists('key_locked', $model->getAttributes())) {
            $values['key_locked'] = true;
        }

        DB::table($model->getTable())->where('id', $model->getKey())->update($values);
    }

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

    private function assertDatabaseRejects(callable $operation): void
    {
        try {
            $operation();
            $this->fail('Expected the database constraint to reject the operation.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }
    }

    /**
     * A real, tiny, valid PNG's bytes, matching the established fixture
     * pattern in EditorialActionTest/AssetLifecycleTest: the sandbox's PHP
     * build has no GD extension, so `UploadedFile::fake()->image()` (which
     * shells out to `imagecreatetruecolor()`) is unusable here.
     */
    private function png(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL9SAAAAABJRU5ErkJggg=='),
        );
    }
}
