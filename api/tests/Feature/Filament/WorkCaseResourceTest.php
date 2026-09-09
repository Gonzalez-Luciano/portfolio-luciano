<?php

namespace Tests\Feature\Filament;

use App\Enums\PublicationStatus;
use App\Filament\Resources\WorkCases\Pages\CreateWorkCase;
use App\Filament\Resources\WorkCases\Pages\EditWorkCase;
use App\Filament\Resources\WorkCases\Pages\ListWorkCases;
use App\Models\Technology;
use App\Models\User;
use App\Models\WorkCase;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

final class WorkCaseResourceTest extends TestCase
{
    use RefreshDatabase;

    // ---- Access ----

    public function test_administrator_can_reach_work_case_pages(): void
    {
        $this->authenticateAdmin();
        $workCase = WorkCase::factory()->create();

        $this->get(ListWorkCases::getUrl())->assertOk();
        $this->get(CreateWorkCase::getUrl())->assertOk();
        $this->get(EditWorkCase::getUrl(['record' => $workCase]))->assertOk();
    }

    public function test_guest_is_redirected_from_work_case_list_page(): void
    {
        $this->get(ListWorkCases::getUrl())->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_non_administrator_is_denied_work_case_list_page(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user);

        $this->get(ListWorkCases::getUrl())->assertForbidden();
    }

    // ---- No confidentiality_note field ----

    public function test_work_case_form_never_exposes_a_confidentiality_note_field(): void
    {
        $this->authenticateAdmin();
        $workCase = WorkCase::factory()->create();

        Livewire::test(EditWorkCase::class, ['record' => $workCase->getKey()])
            ->assertFormFieldDoesNotExist('confidentiality_note')
            ->assertFormFieldDoesNotExist('status')
            ->assertFormFieldDoesNotExist('is_visible')
            ->assertFormFieldDoesNotExist('published_at')
            ->assertFormFieldDoesNotExist('key_locked')
            ->assertFormFieldDoesNotExist('position');
    }

    // ---- Six exact bilingual required pairs ----

    public function test_all_six_bilingual_pairs_are_required_to_publish(): void
    {
        $this->authenticateAdmin();

        foreach (['title', 'context', 'problem', 'contribution', 'technical_approach', 'outcome'] as $field) {
            $attributes = $this->completeAttributes();
            $attributes["{$field}_en"] = null;
            $workCase = WorkCase::factory()->create($attributes);

            Livewire::test(EditWorkCase::class, ['record' => $workCase->getKey()])
                ->callAction('publish')
                ->assertNotified('Publish failed');

            $this->assertSame(PublicationStatus::Draft, $workCase->refresh()->status, "Expected publish to fail when [{$field}_en] is missing.");
        }
    }

    public function test_work_case_publishes_once_all_six_pairs_are_complete(): void
    {
        $workCase = WorkCase::factory()->create($this->completeAttributes());
        $this->authenticateAdmin();

        Livewire::test(EditWorkCase::class, ['record' => $workCase->getKey()])
            ->callAction('publish')
            ->assertNotified('Publish succeeded');

        $this->assertSame(PublicationStatus::Published, $workCase->refresh()->status);
    }

    // ---- Contextual ordered technologies ----

    public function test_saving_technologies_persists_pivot_position_matching_repeater_order(): void
    {
        $workCase = WorkCase::factory()->create();
        $first = Technology::factory()->create(['key' => 'synthetic-technology-alpha']);
        $second = Technology::factory()->create(['key' => 'synthetic-technology-beta']);
        $this->authenticateAdmin();

        Livewire::test(EditWorkCase::class, ['record' => $workCase->getKey()])
            ->fillForm([
                'technologies' => [
                    ['technology_id' => $second->id],
                    ['technology_id' => $first->id],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $pivots = DB::table('technology_work_case')->where('work_case_id', $workCase->getKey())->orderBy('position')->get();
        $this->assertCount(2, $pivots);
        $this->assertSame($second->id, $pivots[0]->technology_id);
        $this->assertSame(0, $pivots[0]->position);
        $this->assertSame($first->id, $pivots[1]->technology_id);
        $this->assertSame(1, $pivots[1]->position);
    }

    public function test_duplicate_technology_association_is_rejected_and_nothing_is_persisted(): void
    {
        $workCase = WorkCase::factory()->create();
        $technology = Technology::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(EditWorkCase::class, ['record' => $workCase->getKey()])
            ->fillForm([
                'technologies' => [
                    ['technology_id' => $technology->id],
                    ['technology_id' => $technology->id],
                ],
            ])
            ->call('save')
            ->assertNotified('Save failed');

        $this->assertSame(0, DB::table('technology_work_case')->where('work_case_id', $workCase->getKey())->count());
    }

    // ---- Application maximum-length validation ----

    public function test_an_overlong_narrative_text_field_is_rejected_as_a_form_validation_error_on_save(): void
    {
        $workCase = WorkCase::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(EditWorkCase::class, ['record' => $workCase->getKey()])
            ->fillForm(['outcome_es' => str_repeat('a', 10001)])
            ->call('save')
            ->assertHasFormErrors(['outcome_es' => 'max']);
    }

    public function test_editing_an_unrelated_field_does_not_wipe_existing_technologies(): void
    {
        $workCase = WorkCase::factory()->create(['context_es' => 'Contexto existente sintético']);
        $technology = Technology::factory()->create();
        $workCase->technologies()->attach($technology, ['position' => 0]);
        $this->authenticateAdmin();

        Livewire::test(EditWorkCase::class, ['record' => $workCase->getKey()])
            ->fillForm(['context_es' => 'Contexto actualizado sintético'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Saved');

        $workCase = $workCase->fresh();
        $this->assertSame('Contexto actualizado sintético', $workCase->context_es);
        $technologies = $workCase->technologies;
        $this->assertCount(1, $technologies);
        $this->assertSame($technology->getKey(), $technologies[0]->getKey());
        $this->assertSame(0, $technologies[0]->pivot->position);
    }

    // ---- Transitions ----

    public function test_work_case_editorial_transitions_go_through_editorial_actions(): void
    {
        $workCase = WorkCase::factory()->create($this->completeAttributes());
        $this->authenticateAdmin();

        Livewire::test(EditWorkCase::class, ['record' => $workCase->getKey()])
            ->assertActionExists('publish')
            ->callAction('publish')
            ->assertNotified('Publish succeeded');

        $workCase->refresh();
        $this->assertSame(PublicationStatus::Published, $workCase->status);
        $this->assertFalse($workCase->is_visible);

        Livewire::test(EditWorkCase::class, ['record' => $workCase->getKey()])
            ->callAction('show')
            ->assertNotified('Show succeeded');
        $this->assertTrue($workCase->refresh()->is_visible);

        Livewire::test(EditWorkCase::class, ['record' => $workCase->getKey()])
            ->callAction('hide')
            ->assertNotified('Hide succeeded');
        $this->assertFalse($workCase->refresh()->is_visible);

        Livewire::test(EditWorkCase::class, ['record' => $workCase->getKey()])
            ->assertActionExists('return_to_draft', checkActionUsing: fn ($action) => $action->isConfirmationRequired())
            ->callAction('return_to_draft')
            ->assertNotified('Return to draft succeeded');
        $this->assertSame(PublicationStatus::Draft, $workCase->refresh()->status);
    }

    // ---- Reorder (top-level position) ----

    public function test_work_case_reorder_action_updates_position_via_reorder_content(): void
    {
        $workCase = WorkCase::factory()->create(['position' => 0]);
        $this->authenticateAdmin();

        Livewire::test(ListWorkCases::class)
            ->callTableAction('reorder', $workCase, data: ['position' => 4])
            ->assertHasNoTableActionErrors();

        $this->assertSame(4, $workCase->refresh()->position);
    }

    public function test_work_case_reorder_action_rejects_a_negative_position(): void
    {
        $workCase = WorkCase::factory()->create(['position' => 2]);
        $this->authenticateAdmin();

        Livewire::test(ListWorkCases::class)
            ->callTableAction('reorder', $workCase, data: ['position' => -1])
            ->assertHasTableActionErrors(['position']);

        $this->assertSame(2, $workCase->refresh()->position);
    }

    // ---- Key locking/change ----

    public function test_work_case_key_can_change_freely_before_publication(): void
    {
        $workCase = WorkCase::factory()->create(['key' => 'synthetic-before']);
        $this->authenticateAdmin();

        Livewire::test(EditWorkCase::class, ['record' => $workCase->getKey()])
            ->assertActionExists('change_key', checkActionUsing: fn ($action) => ! $action->isConfirmationRequired())
            ->callAction('change_key', data: ['key' => 'synthetic-after']);

        $this->assertSame('synthetic-after', $workCase->refresh()->key);
    }

    public function test_work_case_key_change_requires_confirmation_once_locked(): void
    {
        $workCase = WorkCase::factory()->create(array_merge($this->completeAttributes(), ['key' => 'synthetic-locked']));
        $this->forcePublished($workCase, visible: true);
        $this->assertTrue($workCase->key_locked);
        $this->authenticateAdmin();

        Livewire::test(EditWorkCase::class, ['record' => $workCase->getKey()])
            ->assertActionExists('change_key', checkActionUsing: fn ($action) => $action->isConfirmationRequired())
            ->callAction('change_key', data: ['key' => 'synthetic-relocked']);

        $this->assertSame('synthetic-relocked', $workCase->refresh()->key);
    }

    // ---- Hard delete ----

    public function test_deleting_a_work_case_cascades_to_technology_pivots(): void
    {
        $workCase = WorkCase::factory()->create();
        $technology = Technology::factory()->create();
        $workCase->technologies()->attach($technology, ['position' => 0]);
        $this->authenticateAdmin();

        Livewire::test(EditWorkCase::class, ['record' => $workCase->getKey()])
            ->assertActionExists('delete', checkActionUsing: fn ($action) => $action->isConfirmationRequired())
            ->callAction('delete');

        $this->assertSame(0, WorkCase::query()->count());
        $this->assertSame(0, DB::table('technology_work_case')->count());
        // The technology itself is not deleted, only the relation row.
        $this->assertSame(1, Technology::query()->count());
    }

    // ---- Aggregate atomicity ----

    public function test_invalid_field_and_technology_combination_on_a_published_visible_work_case_fails_atomically(): void
    {
        $workCase = WorkCase::factory()->create($this->completeAttributes());
        $this->forcePublished($workCase, visible: true);
        $originalTitleEs = $workCase->title_es;
        $duplicateCandidate = Technology::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(EditWorkCase::class, ['record' => $workCase->getKey()])
            ->fillForm([
                'title_es' => 'Título modificado sintético',
                'technologies' => [
                    ['technology_id' => $duplicateCandidate->id],
                    ['technology_id' => $duplicateCandidate->id],
                ],
            ])
            ->call('save')
            ->assertNotified('Save failed');

        $workCase->refresh();
        $this->assertSame($originalTitleEs, $workCase->title_es);
        $this->assertSame(0, DB::table('technology_work_case')->where('work_case_id', $workCase->getKey())->count());
    }

    private function completeAttributes(): array
    {
        return [
            'title_es' => 'Caso técnico sintético', 'title_en' => 'Synthetic technical case',
            'context_es' => 'Contexto técnico sintético.', 'context_en' => 'Synthetic technical context.',
            'problem_es' => 'Problema técnico sintético.', 'problem_en' => 'Synthetic technical problem.',
            'contribution_es' => 'Contribución técnica sintética.', 'contribution_en' => 'Synthetic technical contribution.',
            'technical_approach_es' => 'Enfoque técnico sintético.', 'technical_approach_en' => 'Synthetic technical approach.',
            'outcome_es' => 'Resultado técnico sintético.', 'outcome_en' => 'Synthetic technical outcome.',
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
