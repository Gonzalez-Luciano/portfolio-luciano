<?php

namespace Tests\Feature\Filament;

use App\Enums\PublicationStatus;
use App\Filament\Resources\EducationEntries\Pages\CreateEducationEntry;
use App\Filament\Resources\EducationEntries\Pages\EditEducationEntry;
use App\Filament\Resources\EducationEntries\Pages\ListEducationEntries;
use App\Models\EducationEntry;
use App\Models\User;
use Filament\Facades\Filament;
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

    private function authenticateAdmin(): User
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        return $admin;
    }
}
