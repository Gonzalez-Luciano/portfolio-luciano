<?php

namespace Tests\Feature\Filament;

use App\Enums\PublicationStatus;
use App\Filament\Resources\ExperienceHighlights\ExperienceHighlightResource;
use App\Filament\Resources\Experiences\Pages\CreateExperience;
use App\Filament\Resources\Experiences\Pages\EditExperience;
use App\Filament\Resources\Experiences\Pages\ListExperiences;
use App\Models\Experience;
use App\Models\ExperienceHighlight;
use App\Models\Technology;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

final class ExperienceResourceTest extends TestCase
{
    use RefreshDatabase;

    // ---- Access ----

    public function test_administrator_can_reach_experience_pages(): void
    {
        $this->authenticateAdmin();
        $experience = Experience::factory()->create();

        $this->get(ListExperiences::getUrl())->assertOk();
        $this->get(CreateExperience::getUrl())->assertOk();
        $this->get(EditExperience::getUrl(['record' => $experience]))->assertOk();
    }

    public function test_guest_is_redirected_from_experience_list_page(): void
    {
        $this->get(ListExperiences::getUrl())->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_non_administrator_is_denied_experience_list_page(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user);

        $this->get(ListExperiences::getUrl())->assertForbidden();
    }

    // ---- Application maximum-length validation ----

    public function test_an_overlong_bounded_field_is_rejected_as_a_form_validation_error_on_save(): void
    {
        $experience = Experience::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(EditExperience::class, ['record' => $experience->getKey()])
            ->fillForm(['role_es' => str_repeat('a', 256)])
            ->call('save')
            ->assertHasFormErrors(['role_es' => 'max']);
    }

    // ---- Aggregate is not a standalone resource ----

    public function test_experience_highlight_has_no_dedicated_resource_or_pages(): void
    {
        $this->assertFalse(class_exists(ExperienceHighlightResource::class));
    }

    public function test_highlights_repeater_is_embedded_in_the_experience_form(): void
    {
        $this->authenticateAdmin();
        $experience = Experience::factory()->create();

        Livewire::test(EditExperience::class, ['record' => $experience->getKey()])
            ->assertFormFieldExists('highlights')
            ->assertFormFieldExists('technologies')
            ->assertFormFieldDoesNotExist('status')
            ->assertFormFieldDoesNotExist('is_visible')
            ->assertFormFieldDoesNotExist('published_at')
            ->assertFormFieldDoesNotExist('key_locked')
            ->assertFormFieldDoesNotExist('position');
    }

    // ---- Bilingual role/summary and organization-label parity ----

    public function test_role_and_summary_are_a_required_pair_to_publish(): void
    {
        $experience = Experience::factory()->create([
            'role_es' => 'Ingeniero sintético',
            'role_en' => null,
            'summary_es' => 'Resumen sintético.',
            'summary_en' => 'Synthetic summary.',
        ]);
        $this->authenticateAdmin();

        Livewire::test(EditExperience::class, ['record' => $experience->getKey()])
            ->callAction('publish')
            ->assertNotified('Publish failed');

        $this->assertSame(PublicationStatus::Draft, $experience->refresh()->status);
    }

    public function test_organization_label_is_an_optional_pair(): void
    {
        $experience = Experience::factory()->create([
            'role_es' => 'Ingeniero sintético',
            'role_en' => 'Synthetic engineer',
            'summary_es' => 'Resumen sintético.',
            'summary_en' => 'Synthetic summary.',
            'organization_label_es' => null,
            'organization_label_en' => null,
        ]);
        $this->authenticateAdmin();

        Livewire::test(EditExperience::class, ['record' => $experience->getKey()])
            ->callAction('publish')
            ->assertNotified('Publish succeeded');

        $this->assertSame(PublicationStatus::Published, $experience->refresh()->status);

        $unpaired = Experience::factory()->create([
            'role_es' => 'Ingeniero sintético',
            'role_en' => 'Synthetic engineer',
            'summary_es' => 'Resumen sintético.',
            'summary_en' => 'Synthetic summary.',
            'organization_label_es' => 'Organización sintética',
            'organization_label_en' => null,
        ]);

        Livewire::test(EditExperience::class, ['record' => $unpaired->getKey()])
            ->callAction('publish')
            ->assertNotified('Publish failed');

        $this->assertSame(PublicationStatus::Draft, $unpaired->refresh()->status);
    }

    // ---- Monthly dates ----
    //
    // Invalid year/month ranges, an incomplete end pair, and reversed
    // chronology can never be persisted at all (even as a draft): the
    // `experiences` table's own CHECK constraints reject such a row at the
    // database level unconditionally. So the validator's date rules can
    // only ever be exercised by editing a row that already exists (and is
    // therefore already valid) into an invalid shape; for a *draft* row
    // that update would itself simply fail the same DB constraint, so the
    // one place `PublicationValidator::experienceDateIssues()` truly
    // guards against a raw database error — the scenario tested below —
    // is an edit to a currently-*published* experience, where the
    // aggregate action validates the in-memory candidate before ever
    // attempting to save it.

    public function test_invalid_start_date_fails_to_save_on_a_published_experience(): void
    {
        $experience = $this->bilingualDraft(['start_year' => 2024, 'start_month' => 1]);
        $this->forcePublished($experience, visible: true);
        $this->authenticateAdmin();

        Livewire::test(EditExperience::class, ['record' => $experience->getKey()])
            ->fillForm(['start_month' => 13])
            ->call('save')
            ->assertNotified('Save failed');

        $this->assertSame(1, $experience->refresh()->start_month);
    }

    public function test_end_date_must_be_present_as_a_complete_pair(): void
    {
        $experience = $this->bilingualDraft(['start_year' => 2024, 'start_month' => 1, 'end_year' => null, 'end_month' => null]);
        $this->forcePublished($experience, visible: true);
        $this->authenticateAdmin();

        Livewire::test(EditExperience::class, ['record' => $experience->getKey()])
            ->fillForm(['end_year' => 2025, 'end_month' => null])
            ->call('save')
            ->assertNotified('Save failed');

        $this->assertNull($experience->refresh()->end_year);
        $this->assertNull($experience->end_month);
    }

    public function test_end_date_must_not_precede_the_start_date(): void
    {
        $experience = $this->bilingualDraft(['start_year' => 2024, 'start_month' => 6, 'end_year' => null, 'end_month' => null]);
        $this->forcePublished($experience, visible: true);
        $this->authenticateAdmin();

        Livewire::test(EditExperience::class, ['record' => $experience->getKey()])
            ->fillForm(['end_year' => 2024, 'end_month' => 1])
            ->call('save')
            ->assertNotified('Save failed');

        $this->assertNull($experience->refresh()->end_year);
        $this->assertNull($experience->end_month);
    }

    public function test_valid_dates_publish_successfully(): void
    {
        $experience = $this->bilingualDraft(['start_year' => 2020, 'start_month' => 3, 'end_year' => 2024, 'end_month' => 3]);
        $this->authenticateAdmin();

        Livewire::test(EditExperience::class, ['record' => $experience->getKey()])
            ->callAction('publish')
            ->assertNotified('Publish succeeded');

        $this->assertSame(PublicationStatus::Published, $experience->refresh()->status);
    }

    // ---- Highlight ordering ----

    public function test_saving_highlights_persists_them_with_position_matching_repeater_order(): void
    {
        $experience = Experience::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(EditExperience::class, ['record' => $experience->getKey()])
            ->fillForm([
                'highlights' => [
                    ['content_es' => 'Primer hito sintético.', 'content_en' => 'First synthetic highlight.'],
                    ['content_es' => 'Segundo hito sintético.', 'content_en' => 'Second synthetic highlight.'],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $highlights = $experience->highlights()->orderBy('position')->get();
        $this->assertCount(2, $highlights);
        $this->assertSame(0, $highlights[0]->position);
        $this->assertSame('First synthetic highlight.', $highlights[0]->content_en);
        $this->assertSame(1, $highlights[1]->position);
        $this->assertSame('Second synthetic highlight.', $highlights[1]->content_en);

        // Reorder: swap the two highlights and resave.
        Livewire::test(EditExperience::class, ['record' => $experience->getKey()])
            ->fillForm([
                'highlights' => [
                    ['content_es' => 'Segundo hito sintético.', 'content_en' => 'Second synthetic highlight.'],
                    ['content_es' => 'Primer hito sintético.', 'content_en' => 'First synthetic highlight.'],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $reordered = $experience->highlights()->orderBy('position')->get();
        $this->assertSame('Second synthetic highlight.', $reordered[0]->content_en);
        $this->assertSame('First synthetic highlight.', $reordered[1]->content_en);
    }

    // ---- Technology ordering and duplicate prevention ----

    public function test_saving_technologies_persists_pivot_position_matching_repeater_order(): void
    {
        $experience = Experience::factory()->create();
        $first = Technology::factory()->create(['key' => 'synthetic-technology-alpha']);
        $second = Technology::factory()->create(['key' => 'synthetic-technology-beta']);
        $this->authenticateAdmin();

        Livewire::test(EditExperience::class, ['record' => $experience->getKey()])
            ->fillForm([
                'technologies' => [
                    ['technology_id' => $second->id],
                    ['technology_id' => $first->id],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $pivots = DB::table('experience_technology')->where('experience_id', $experience->getKey())->orderBy('position')->get();
        $this->assertCount(2, $pivots);
        $this->assertSame($second->id, $pivots[0]->technology_id);
        $this->assertSame(0, $pivots[0]->position);
        $this->assertSame($first->id, $pivots[1]->technology_id);
        $this->assertSame(1, $pivots[1]->position);
    }

    public function test_duplicate_technology_association_is_rejected_and_nothing_is_persisted(): void
    {
        $experience = Experience::factory()->create();
        $technology = Technology::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(EditExperience::class, ['record' => $experience->getKey()])
            ->fillForm([
                'technologies' => [
                    ['technology_id' => $technology->id],
                    ['technology_id' => $technology->id],
                ],
            ])
            ->call('save')
            ->assertNotified('Save failed');

        $this->assertSame(0, DB::table('experience_technology')->where('experience_id', $experience->getKey())->count());
    }

    // ---- Transitions ----

    public function test_experience_editorial_transitions_go_through_editorial_actions(): void
    {
        $experience = $this->bilingualDraft();
        $this->authenticateAdmin();

        Livewire::test(EditExperience::class, ['record' => $experience->getKey()])
            ->assertActionExists('publish')
            ->callAction('publish')
            ->assertNotified('Publish succeeded');

        $experience->refresh();
        $this->assertSame(PublicationStatus::Published, $experience->status);
        $this->assertFalse($experience->is_visible);

        Livewire::test(EditExperience::class, ['record' => $experience->getKey()])
            ->callAction('show')
            ->assertNotified('Show succeeded');
        $this->assertTrue($experience->refresh()->is_visible);

        Livewire::test(EditExperience::class, ['record' => $experience->getKey()])
            ->callAction('hide')
            ->assertNotified('Hide succeeded');
        $this->assertFalse($experience->refresh()->is_visible);

        Livewire::test(EditExperience::class, ['record' => $experience->getKey()])
            ->assertActionExists('return_to_draft', checkActionUsing: fn ($action) => $action->isConfirmationRequired())
            ->callAction('return_to_draft')
            ->assertNotified('Return to draft succeeded');
        $this->assertSame(PublicationStatus::Draft, $experience->refresh()->status);
    }

    // ---- Reorder (top-level position) ----

    public function test_experience_reorder_action_updates_position_via_reorder_content(): void
    {
        $experience = Experience::factory()->create(['position' => 0]);
        $this->authenticateAdmin();

        Livewire::test(ListExperiences::class)
            ->callTableAction('reorder', $experience, data: ['position' => 4])
            ->assertHasNoTableActionErrors();

        $this->assertSame(4, $experience->refresh()->position);
    }

    public function test_experience_reorder_action_rejects_a_negative_position(): void
    {
        $experience = Experience::factory()->create(['position' => 2]);
        $this->authenticateAdmin();

        Livewire::test(ListExperiences::class)
            ->callTableAction('reorder', $experience, data: ['position' => -1])
            ->assertHasTableActionErrors(['position']);

        $this->assertSame(2, $experience->refresh()->position);
    }

    // ---- Key locking/change ----

    public function test_experience_key_can_change_freely_before_publication(): void
    {
        $experience = Experience::factory()->create(['key' => 'synthetic-before']);
        $this->authenticateAdmin();

        Livewire::test(EditExperience::class, ['record' => $experience->getKey()])
            ->assertActionExists('change_key', checkActionUsing: fn ($action) => ! $action->isConfirmationRequired())
            ->callAction('change_key', data: ['key' => 'synthetic-after']);

        $this->assertSame('synthetic-after', $experience->refresh()->key);
    }

    public function test_experience_key_change_requires_confirmation_once_locked(): void
    {
        $experience = Experience::factory()->draft()->create([
            'key' => 'synthetic-locked',
            'role_es' => 'Ingeniero sintético',
            'role_en' => 'Synthetic engineer',
            'summary_es' => 'Resumen sintético.',
            'summary_en' => 'Synthetic summary.',
        ]);
        $this->forcePublished($experience, visible: true);
        $this->assertTrue($experience->key_locked);
        $this->authenticateAdmin();

        Livewire::test(EditExperience::class, ['record' => $experience->getKey()])
            ->assertActionExists('change_key', checkActionUsing: fn ($action) => $action->isConfirmationRequired())
            ->callAction('change_key', data: ['key' => 'synthetic-relocked']);

        $this->assertSame('synthetic-relocked', $experience->refresh()->key);
    }

    // ---- Delete cascade ----

    public function test_deleting_an_experience_cascades_to_highlights_and_technology_pivots(): void
    {
        $experience = Experience::factory()->create();
        $this->insertHighlight($experience, 'Hito sintético.', 'Synthetic highlight.', 0);
        $technology = Technology::factory()->create();
        $experience->technologies()->attach($technology, ['position' => 0]);
        $this->authenticateAdmin();

        Livewire::test(EditExperience::class, ['record' => $experience->getKey()])
            ->assertActionExists('delete', checkActionUsing: fn ($action) => $action->isConfirmationRequired())
            ->callAction('delete');

        $this->assertSame(0, Experience::query()->count());
        $this->assertSame(0, ExperienceHighlight::query()->count());
        $this->assertSame(0, DB::table('experience_technology')->count());
        // The technology itself is not deleted, only the relation row.
        $this->assertSame(1, Technology::query()->count());
    }

    // ---- Aggregate atomicity ----

    public function test_invalid_highlight_on_a_published_visible_experience_fails_the_whole_save_atomically(): void
    {
        $experience = $this->bilingualDraft();
        $this->forcePublished($experience, visible: true);
        $originalHighlightId = $this->insertHighlight($experience, 'Hito original sintético.', 'Original synthetic highlight.', 0);
        $originalHighlight = ExperienceHighlight::query()->findOrFail($originalHighlightId);
        $originalRoleEs = $experience->role_es;
        $this->authenticateAdmin();

        Livewire::test(EditExperience::class, ['record' => $experience->getKey()])
            ->fillForm([
                'role_es' => 'Rol sintético modificado',
                'highlights' => [
                    ['content_es' => 'Hito incompleto sintético.', 'content_en' => null],
                ],
            ])
            ->call('save')
            ->assertNotified('Save failed');

        $experience->refresh();
        $this->assertSame($originalRoleEs, $experience->role_es);
        $highlights = $experience->highlights()->get();
        $this->assertCount(1, $highlights);
        $this->assertSame($originalHighlight->getKey(), $highlights[0]->getKey());
        $this->assertSame($originalHighlight->content_en, $highlights[0]->content_en);
    }

    public function test_duplicate_technology_on_a_published_visible_experience_fails_the_whole_save_atomically(): void
    {
        $experience = $this->bilingualDraft();
        $this->forcePublished($experience, visible: true);
        $originalTechnology = Technology::factory()->create();
        $experience->technologies()->attach($originalTechnology, ['position' => 0]);
        $duplicateCandidate = Technology::factory()->create();
        $originalRoleEs = $experience->role_es;
        $this->authenticateAdmin();

        Livewire::test(EditExperience::class, ['record' => $experience->getKey()])
            ->fillForm([
                'role_es' => 'Rol sintético modificado',
                'technologies' => [
                    ['technology_id' => $duplicateCandidate->id],
                    ['technology_id' => $duplicateCandidate->id],
                ],
            ])
            ->call('save')
            ->assertNotified('Save failed');

        $experience->refresh();
        $this->assertSame($originalRoleEs, $experience->role_es);
        $pivots = DB::table('experience_technology')->where('experience_id', $experience->getKey())->get();
        $this->assertCount(1, $pivots);
        $this->assertSame($originalTechnology->id, $pivots[0]->technology_id);
    }

    /**
     * The highest-risk behavior of an aggregate-root form: since
     * `UpdateExperienceAggregate` always fully replaces the highlights list
     * and the technology pivot from whatever the `highlights`/`technologies`
     * form state holds, editing an unrelated field (here, `role_es`) must
     * NOT wipe existing highlights/technologies just because the test
     * itself never mentions them. This only proves anything if the form is
     * exercised through its normal mount/fill cycle (so `highlights`/
     * `technologies` are populated by `mutateFormDataBeforeFill()` from the
     * existing relations, exactly as a real admin session would leave them
     * untouched), so `fillForm()` below sets `role_es` only.
     */
    public function test_editing_an_unrelated_field_does_not_wipe_existing_highlights_or_technologies(): void
    {
        $experience = $this->bilingualDraft();
        $this->insertHighlight($experience, 'Hito existente sintético.', 'Existing synthetic highlight.', 0);
        $technology = Technology::factory()->create();
        $experience->technologies()->attach($technology, ['position' => 0]);
        $this->authenticateAdmin();

        Livewire::test(EditExperience::class, ['record' => $experience->getKey()])
            ->fillForm(['role_es' => 'Rol sintético actualizado'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Saved');

        $experience = $experience->fresh();
        $this->assertSame('Rol sintético actualizado', $experience->role_es);

        $highlights = $experience->highlights;
        $this->assertCount(1, $highlights);
        $this->assertSame('Hito existente sintético.', $highlights[0]->content_es);
        $this->assertSame('Existing synthetic highlight.', $highlights[0]->content_en);
        $this->assertSame(0, $highlights[0]->position);

        $technologies = $experience->technologies;
        $this->assertCount(1, $technologies);
        $this->assertSame($technology->getKey(), $technologies[0]->getKey());
        $this->assertSame(0, $technologies[0]->pivot->position);
    }

    /**
     * Inserts an experience highlight directly via the query builder,
     * bypassing Eloquent events: `ExperienceHighlight` may only be
     * created/updated/deleted through `UpdateExperienceAggregate`'s
     * aggregate mutation context (see `EditorialMutationGuard`), so test
     * fixtures that need a pre-existing highlight row must not go through
     * the model/factory directly.
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

    private function bilingualDraft(array $attributes = []): Experience
    {
        return Experience::factory()->create(array_merge([
            'role_es' => 'Ingeniero sintético',
            'role_en' => 'Synthetic engineer',
            'summary_es' => 'Resumen sintético.',
            'summary_en' => 'Synthetic summary.',
        ], $attributes));
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
     * in SiteCollectionResourceTest/SingletonPageTest/EditorialActionTest)
     * since the editorial mutation guard forbids creating or updating into
     * a published state through Eloquent outside of the domain actions.
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
