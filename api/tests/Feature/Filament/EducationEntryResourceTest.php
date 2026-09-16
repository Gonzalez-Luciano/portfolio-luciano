<?php

namespace Tests\Feature\Filament;

use App\Enums\PublicationStatus;
use App\Filament\Resources\EducationEntries\Pages\CreateEducationEntry;
use App\Filament\Resources\EducationEntries\Pages\EditEducationEntry;
use App\Filament\Resources\EducationEntries\Pages\ListEducationEntries;
use App\Models\EducationEntry;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class EducationEntryResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_reach_education_pages_and_guests_cannot(): void
    {
        $entry = EducationEntry::factory()->create();

        $this->get(ListEducationEntries::getUrl())->assertRedirect(route('filament.admin.auth.login'));

        $this->authenticateAdmin();
        $this->get(ListEducationEntries::getUrl())->assertOk();
        $this->get(CreateEducationEntry::getUrl())->assertOk();
        $this->get(EditEducationEntry::getUrl(['record' => $entry]))->assertOk();
    }

    public function test_non_administrator_is_denied_the_education_list(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]));

        $this->get(ListEducationEntries::getUrl())->assertForbidden();
    }

    public function test_creating_an_education_entry_with_a_duplicate_key_is_rejected_as_a_validation_error(): void
    {
        EducationEntry::factory()->create(['key' => 'synthetic-duplicate']);
        $this->authenticateAdmin();

        Livewire::test(CreateEducationEntry::class)
            ->fillForm(['key' => 'synthetic-duplicate', 'institution' => 'Institución sintética', 'program_es' => 'Programa', 'program_en' => 'Program'])
            ->call('create')
            ->assertHasFormErrors(['key']);

        $this->assertSame(1, EducationEntry::query()->count());
    }

    public function test_administrator_creates_several_education_entries_as_ordered_drafts(): void
    {
        $this->authenticateAdmin();

        foreach (['synthetic-school', 'synthetic-course'] as $key) {
            Livewire::test(CreateEducationEntry::class)
                ->fillForm(['key' => $key, 'institution' => 'Institución sintética', 'program_es' => 'Programa', 'program_en' => 'Program', 'end_year' => 2021])
                ->call('create')
                ->assertHasNoFormErrors();
        }

        $entries = EducationEntry::query()->orderBy('position')->get();
        $this->assertSame(['synthetic-school', 'synthetic-course'], $entries->pluck('key')->all());
        $this->assertSame([0, 1], $entries->pluck('position')->all());
        $this->assertSame([PublicationStatus::Draft, PublicationStatus::Draft], $entries->pluck('status')->all());
    }

    public function test_publication_requires_institution_and_both_program_translations(): void
    {
        $entry = EducationEntry::factory()->create(['institution' => 'Institución sintética', 'program_es' => 'Programa']);
        $this->authenticateAdmin();

        Livewire::test(EditEducationEntry::class, ['record' => $entry->getKey()])
            ->callAction('publish')
            ->assertNotified('Publish failed');
        $this->assertSame(PublicationStatus::Draft, $entry->refresh()->status);

        Livewire::test(EditEducationEntry::class, ['record' => $entry->getKey()])
            ->fillForm(['program_en' => 'Program', 'detail_es' => 'Detalle', 'detail_en' => 'Detail'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->callAction('publish')
            ->assertNotified('Publish succeeded');
        $this->assertSame(PublicationStatus::Published, $entry->refresh()->status);
    }

    public function test_reorder_action_changes_the_position(): void
    {
        $entry = EducationEntry::factory()->create(['position' => 0]);
        $this->authenticateAdmin();

        Livewire::test(ListEducationEntries::class)
            ->callTableAction('reorder', $entry, data: ['position' => 3])
            ->assertHasNoTableActionErrors();

        $this->assertSame(3, $entry->refresh()->position);
    }

    public function test_reorder_action_rejects_a_negative_position(): void
    {
        $entry = EducationEntry::factory()->create(['position' => 3]);
        $this->authenticateAdmin();

        Livewire::test(ListEducationEntries::class)
            ->callTableAction('reorder', $entry, data: ['position' => -1])
            ->assertHasTableActionErrors(['position']);

        $this->assertSame(3, $entry->refresh()->position);
    }

    public function test_delete_requires_confirmation_and_uses_delete_content(): void
    {
        $entry = EducationEntry::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(EditEducationEntry::class, ['record' => $entry->getKey()])
            ->assertActionExists('delete', checkActionUsing: fn ($action) => $action->isConfirmationRequired())
            ->callAction('delete');

        $this->assertSame(0, EducationEntry::query()->count());
    }

    public function test_table_filters_by_status_and_visibility(): void
    {
        $draft = EducationEntry::factory()->draft()->create();
        $publishedHidden = EducationEntry::factory()->draft()->create();
        $this->forcePublished($publishedHidden, visible: false);
        $publishedVisible = EducationEntry::factory()->draft()->create();
        $this->forcePublished($publishedVisible, visible: true);
        $this->authenticateAdmin();

        Livewire::test(ListEducationEntries::class)
            ->filterTable('status', PublicationStatus::Draft->value)
            ->assertCanSeeTableRecords([$draft])
            ->assertCanNotSeeTableRecords([$publishedHidden, $publishedVisible]);

        Livewire::test(ListEducationEntries::class)
            ->filterTable('is_visible', true)
            ->assertCanSeeTableRecords([$publishedVisible])
            ->assertCanNotSeeTableRecords([$draft, $publishedHidden]);
    }

    public function test_key_can_change_freely_before_publication(): void
    {
        $entry = EducationEntry::factory()->create(['key' => 'synthetic-before']);
        $this->authenticateAdmin();

        Livewire::test(EditEducationEntry::class, ['record' => $entry->getKey()])
            ->assertActionExists('change_key', checkActionUsing: fn ($action) => ! $action->isConfirmationRequired())
            ->callAction('change_key', data: ['key' => 'synthetic-after']);

        $this->assertSame('synthetic-after', $entry->refresh()->key);
    }

    public function test_key_change_requires_confirmation_once_locked(): void
    {
        // A real published row always satisfies the full publication
        // validator (PublishContent enforces it), so the fixture must too:
        // ChangePublicKey's save re-triggers the guard's full
        // assertPublishable() check on any update to published content.
        $entry = EducationEntry::factory()->draft()->create([
            'key' => 'synthetic-locked',
            'institution' => 'Institución sintética',
            'program_es' => 'Programa sintético',
            'program_en' => 'Synthetic program',
        ]);
        $this->forcePublished($entry, visible: true);
        $this->assertTrue($entry->key_locked);
        $this->authenticateAdmin();

        Livewire::test(EditEducationEntry::class, ['record' => $entry->getKey()])
            ->assertActionExists('change_key', checkActionUsing: fn ($action) => $action->isConfirmationRequired())
            ->callAction('change_key', data: ['key' => 'synthetic-relocked']);

        $this->assertSame('synthetic-relocked', $entry->refresh()->key);
    }

    public function test_editorial_fields_are_not_plain_editable_inputs(): void
    {
        $entry = EducationEntry::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(EditEducationEntry::class, ['record' => $entry->getKey()])
            ->assertFormFieldDoesNotExist('status')
            ->assertFormFieldDoesNotExist('is_visible')
            ->assertFormFieldDoesNotExist('published_at')
            ->assertFormFieldDoesNotExist('key_locked');
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
     * in SiteCollectionResourceTest) since the editorial mutation guard
     * forbids creating or updating into a published state through Eloquent
     * outside of the domain actions.
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
