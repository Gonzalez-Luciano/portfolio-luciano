<?php

namespace Tests\Feature\Filament;

use App\Enums\ProfessionalLinkType;
use App\Enums\PublicationStatus;
use App\Filament\Resources\ExpertiseAreas\Pages\CreateExpertiseArea;
use App\Filament\Resources\ExpertiseAreas\Pages\EditExpertiseArea;
use App\Filament\Resources\ExpertiseAreas\Pages\ListExpertiseAreas;
use App\Filament\Resources\ProfessionalLinks\Pages\CreateProfessionalLink;
use App\Filament\Resources\ProfessionalLinks\Pages\EditProfessionalLink;
use App\Filament\Resources\ProfessionalLinks\Pages\ListProfessionalLinks;
use App\Filament\Resources\WorkPrinciples\Pages\CreateWorkPrinciple;
use App\Filament\Resources\WorkPrinciples\Pages\EditWorkPrinciple;
use App\Filament\Resources\WorkPrinciples\Pages\ListWorkPrinciples;
use App\Models\ExpertiseArea;
use App\Models\ProfessionalLink;
use App\Models\User;
use App\Models\WorkPrinciple;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class SiteCollectionResourceTest extends TestCase
{
    use RefreshDatabase;

    // ---- Access ----

    public function test_administrator_can_reach_each_collection_list_page(): void
    {
        $this->authenticateAdmin();

        $this->get(ListProfessionalLinks::getUrl())->assertOk();
        $this->get(ListExpertiseAreas::getUrl())->assertOk();
        $this->get(ListWorkPrinciples::getUrl())->assertOk();
    }

    public function test_guest_is_redirected_from_each_collection_list_page(): void
    {
        $this->get(ListProfessionalLinks::getUrl())->assertRedirect(route('filament.admin.auth.login'));
        $this->get(ListExpertiseAreas::getUrl())->assertRedirect(route('filament.admin.auth.login'));
        $this->get(ListWorkPrinciples::getUrl())->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_non_administrator_is_denied_each_collection_list_page(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user);

        $this->get(ListProfessionalLinks::getUrl())->assertForbidden();
        $this->get(ListExpertiseAreas::getUrl())->assertForbidden();
        $this->get(ListWorkPrinciples::getUrl())->assertForbidden();
    }

    public function test_administrator_can_reach_create_and_edit_pages_for_each_collection(): void
    {
        $this->authenticateAdmin();
        $link = ProfessionalLink::factory()->create(['type' => ProfessionalLinkType::GitHub]);
        $area = ExpertiseArea::factory()->create();
        $principle = WorkPrinciple::factory()->create();

        $this->get(CreateProfessionalLink::getUrl())->assertOk();
        $this->get(EditProfessionalLink::getUrl(['record' => $link]))->assertOk();
        $this->get(CreateExpertiseArea::getUrl())->assertOk();
        $this->get(EditExpertiseArea::getUrl(['record' => $area]))->assertOk();
        $this->get(CreateWorkPrinciple::getUrl())->assertOk();
        $this->get(EditWorkPrinciple::getUrl(['record' => $principle]))->assertOk();
    }

    // ---- Creation / identity ----

    public function test_administrator_can_create_a_professional_link_as_a_draft(): void
    {
        $this->authenticateAdmin();

        Livewire::test(CreateProfessionalLink::class)
            ->fillForm(['type' => ProfessionalLinkType::Email->value, 'destination' => 'synthetic@example.test'])
            ->call('create')
            ->assertHasNoFormErrors();

        $link = ProfessionalLink::query()->where('type', ProfessionalLinkType::Email)->firstOrFail();
        $this->assertSame(PublicationStatus::Draft, $link->status);
        $this->assertFalse($link->is_visible);
    }

    public function test_professional_link_type_select_rejects_a_value_outside_the_three_enum_cases(): void
    {
        $this->authenticateAdmin();

        Livewire::test(CreateProfessionalLink::class)
            ->fillForm(['type' => 'other', 'destination' => 'https://example.test'])
            ->call('create')
            ->assertHasFormErrors(['type']);

        $this->assertSame(0, ProfessionalLink::query()->count());
    }

    public function test_creating_a_professional_link_with_a_duplicate_type_is_rejected_as_a_validation_error(): void
    {
        ProfessionalLink::factory()->create(['type' => ProfessionalLinkType::LinkedIn]);
        $this->authenticateAdmin();

        Livewire::test(CreateProfessionalLink::class)
            ->fillForm(['type' => ProfessionalLinkType::LinkedIn->value, 'destination' => 'https://example.test/synthetic'])
            ->call('create')
            ->assertHasFormErrors(['type']);

        $this->assertSame(1, ProfessionalLink::query()->count());
    }

    public function test_administrator_can_create_an_expertise_area_as_a_draft(): void
    {
        $this->authenticateAdmin();

        Livewire::test(CreateExpertiseArea::class)
            ->fillForm(['key' => 'synthetic-area'])
            ->call('create')
            ->assertHasNoFormErrors();

        $area = ExpertiseArea::query()->where('key', 'synthetic-area')->firstOrFail();
        $this->assertSame(PublicationStatus::Draft, $area->status);
        $this->assertFalse($area->key_locked);
    }

    public function test_creating_an_expertise_area_with_a_duplicate_key_is_rejected_as_a_validation_error(): void
    {
        ExpertiseArea::factory()->create(['key' => 'synthetic-area']);
        $this->authenticateAdmin();

        Livewire::test(CreateExpertiseArea::class)
            ->fillForm(['key' => 'synthetic-area'])
            ->call('create')
            ->assertHasFormErrors(['key']);

        $this->assertSame(1, ExpertiseArea::query()->count());
    }

    public function test_administrator_can_create_a_work_principle_as_a_draft(): void
    {
        $this->authenticateAdmin();

        Livewire::test(CreateWorkPrinciple::class)
            ->fillForm(['key' => 'synthetic-principle'])
            ->call('create')
            ->assertHasNoFormErrors();

        $principle = WorkPrinciple::query()->where('key', 'synthetic-principle')->firstOrFail();
        $this->assertSame(PublicationStatus::Draft, $principle->status);
    }

    public function test_creating_a_work_principle_with_a_duplicate_key_is_rejected_as_a_validation_error(): void
    {
        WorkPrinciple::factory()->create(['key' => 'synthetic-principle']);
        $this->authenticateAdmin();

        Livewire::test(CreateWorkPrinciple::class)
            ->fillForm(['key' => 'synthetic-principle'])
            ->call('create')
            ->assertHasFormErrors(['key']);

        $this->assertSame(1, WorkPrinciple::query()->count());
    }

    // ---- Editorial transitions ----

    public function test_professional_link_editorial_transitions_go_through_editorial_actions(): void
    {
        $link = ProfessionalLink::factory()->create([
            'type' => ProfessionalLinkType::GitHub,
            'destination' => 'https://example.test/synthetic-github',
            'label_es' => 'Enlace sintético',
            'label_en' => 'Synthetic link',
        ]);
        $this->authenticateAdmin();

        Livewire::test(EditProfessionalLink::class, ['record' => $link->getKey()])
            ->assertActionExists('publish')
            ->callAction('publish')
            ->assertNotified('Publish succeeded');

        $link->refresh();
        $this->assertSame(PublicationStatus::Published, $link->status);
        $this->assertFalse($link->is_visible);

        Livewire::test(EditProfessionalLink::class, ['record' => $link->getKey()])
            ->callAction('show')
            ->assertNotified('Show succeeded');
        $this->assertTrue($link->refresh()->is_visible);

        Livewire::test(EditProfessionalLink::class, ['record' => $link->getKey()])
            ->callAction('hide')
            ->assertNotified('Hide succeeded');
        $this->assertFalse($link->refresh()->is_visible);

        Livewire::test(EditProfessionalLink::class, ['record' => $link->getKey()])
            ->assertActionExists('return_to_draft', checkActionUsing: fn ($action) => $action->isConfirmationRequired())
            ->callAction('return_to_draft')
            ->assertNotified('Return to draft succeeded');
        $this->assertSame(PublicationStatus::Draft, $link->refresh()->status);
    }

    // ---- Delete ----

    public function test_professional_link_delete_requires_confirmation_and_uses_delete_content(): void
    {
        $link = ProfessionalLink::factory()->create(['type' => ProfessionalLinkType::Email, 'destination' => 'synthetic@example.test']);
        $this->authenticateAdmin();

        Livewire::test(EditProfessionalLink::class, ['record' => $link->getKey()])
            ->assertActionExists('delete', checkActionUsing: fn ($action) => $action->isConfirmationRequired())
            ->callAction('delete');

        $this->assertSame(0, ProfessionalLink::query()->count());
    }

    public function test_expertise_area_delete_requires_confirmation_and_uses_delete_content(): void
    {
        $area = ExpertiseArea::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(EditExpertiseArea::class, ['record' => $area->getKey()])
            ->assertActionExists('delete', checkActionUsing: fn ($action) => $action->isConfirmationRequired())
            ->callAction('delete');

        $this->assertSame(0, ExpertiseArea::query()->count());
    }

    public function test_work_principle_delete_requires_confirmation_and_uses_delete_content(): void
    {
        $principle = WorkPrinciple::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(EditWorkPrinciple::class, ['record' => $principle->getKey()])
            ->assertActionExists('delete', checkActionUsing: fn ($action) => $action->isConfirmationRequired())
            ->callAction('delete');

        $this->assertSame(0, WorkPrinciple::query()->count());
    }

    // ---- Reorder ----

    public function test_professional_link_reorder_action_updates_position_via_reorder_content(): void
    {
        $link = ProfessionalLink::factory()->create(['type' => ProfessionalLinkType::LinkedIn, 'position' => 0]);
        $this->authenticateAdmin();

        Livewire::test(ListProfessionalLinks::class)
            ->callTableAction('reorder', $link, data: ['position' => 5])
            ->assertHasNoTableActionErrors();

        $this->assertSame(5, $link->refresh()->position);
    }

    public function test_professional_link_reorder_action_rejects_a_negative_position(): void
    {
        $link = ProfessionalLink::factory()->create(['type' => ProfessionalLinkType::LinkedIn, 'position' => 3]);
        $this->authenticateAdmin();

        Livewire::test(ListProfessionalLinks::class)
            ->callTableAction('reorder', $link, data: ['position' => -1])
            ->assertHasTableActionErrors(['position']);

        $this->assertSame(3, $link->refresh()->position);
    }

    public function test_expertise_area_reorder_action_updates_position_via_reorder_content(): void
    {
        $area = ExpertiseArea::factory()->create(['position' => 0]);
        $this->authenticateAdmin();

        Livewire::test(ListExpertiseAreas::class)
            ->callTableAction('reorder', $area, data: ['position' => 2])
            ->assertHasNoTableActionErrors();

        $this->assertSame(2, $area->refresh()->position);
    }

    public function test_work_principle_reorder_action_updates_position_via_reorder_content(): void
    {
        $principle = WorkPrinciple::factory()->create(['position' => 0]);
        $this->authenticateAdmin();

        Livewire::test(ListWorkPrinciples::class)
            ->callTableAction('reorder', $principle, data: ['position' => 7])
            ->assertHasNoTableActionErrors();

        $this->assertSame(7, $principle->refresh()->position);
    }

    // ---- Filters ----

    public function test_professional_links_table_filters_by_status_and_visibility(): void
    {
        $draft = ProfessionalLink::factory()->draft()->create(['type' => ProfessionalLinkType::LinkedIn]);
        $publishedHidden = ProfessionalLink::factory()->draft()->create(['type' => ProfessionalLinkType::GitHub]);
        $this->forcePublished($publishedHidden, visible: false);
        $publishedVisible = ProfessionalLink::factory()->draft()->create(['type' => ProfessionalLinkType::Email]);
        $this->forcePublished($publishedVisible, visible: true);
        $this->authenticateAdmin();

        Livewire::test(ListProfessionalLinks::class)
            ->filterTable('status', PublicationStatus::Draft->value)
            ->assertCanSeeTableRecords([$draft])
            ->assertCanNotSeeTableRecords([$publishedHidden, $publishedVisible]);

        Livewire::test(ListProfessionalLinks::class)
            ->filterTable('is_visible', true)
            ->assertCanSeeTableRecords([$publishedVisible])
            ->assertCanNotSeeTableRecords([$draft, $publishedHidden]);
    }

    public function test_expertise_areas_table_filters_by_status_and_visibility(): void
    {
        $draft = ExpertiseArea::factory()->draft()->create();
        $publishedVisible = ExpertiseArea::factory()->draft()->create();
        $this->forcePublished($publishedVisible, visible: true);
        $this->authenticateAdmin();

        Livewire::test(ListExpertiseAreas::class)
            ->filterTable('status', PublicationStatus::Published->value)
            ->assertCanSeeTableRecords([$publishedVisible])
            ->assertCanNotSeeTableRecords([$draft]);
    }

    public function test_work_principles_table_filters_by_status_and_visibility(): void
    {
        $draft = WorkPrinciple::factory()->draft()->create();
        $publishedVisible = WorkPrinciple::factory()->draft()->create();
        $this->forcePublished($publishedVisible, visible: true);
        $this->authenticateAdmin();

        Livewire::test(ListWorkPrinciples::class)
            ->filterTable('is_visible', false)
            ->assertCanSeeTableRecords([$draft])
            ->assertCanNotSeeTableRecords([$publishedVisible]);
    }

    // ---- ProfessionalLink destination validation ----

    public function test_linkedin_and_github_destinations_must_be_https_to_publish(): void
    {
        $link = ProfessionalLink::factory()->create([
            'type' => ProfessionalLinkType::LinkedIn,
            'destination' => 'http://example.test/insecure',
            'label_es' => 'Enlace sintético',
            'label_en' => 'Synthetic link',
        ]);
        $this->authenticateAdmin();

        Livewire::test(EditProfessionalLink::class, ['record' => $link->getKey()])
            ->callAction('publish')
            ->assertNotified('Publish failed');

        $this->assertSame(PublicationStatus::Draft, $link->refresh()->status);

        Livewire::test(EditProfessionalLink::class, ['record' => $link->getKey()])
            ->fillForm(['destination' => 'https://example.test/secure'])
            ->call('save')
            ->callAction('publish')
            ->assertNotified('Publish succeeded');

        $this->assertSame(PublicationStatus::Published, $link->refresh()->status);
    }

    public function test_email_destination_must_be_a_plain_address_without_mailto_prefix_to_publish(): void
    {
        $link = ProfessionalLink::factory()->create([
            'type' => ProfessionalLinkType::Email,
            'destination' => 'mailto:synthetic@example.test',
            'label_es' => 'Enlace sintético',
            'label_en' => 'Synthetic link',
        ]);
        $this->authenticateAdmin();

        Livewire::test(EditProfessionalLink::class, ['record' => $link->getKey()])
            ->callAction('publish')
            ->assertNotified('Publish failed');

        $this->assertSame(PublicationStatus::Draft, $link->refresh()->status);

        Livewire::test(EditProfessionalLink::class, ['record' => $link->getKey()])
            ->fillForm(['destination' => 'synthetic@example.test'])
            ->call('save')
            ->callAction('publish')
            ->assertNotified('Publish succeeded');

        $this->assertSame(PublicationStatus::Published, $link->refresh()->status);
    }

    // ---- Bilingual contracts ----

    public function test_professional_link_label_is_a_required_pair_to_publish(): void
    {
        $link = ProfessionalLink::factory()->create([
            'type' => ProfessionalLinkType::GitHub,
            'destination' => 'https://example.test/synthetic-github',
            'label_es' => 'Enlace sintético',
            'label_en' => null,
        ]);
        $this->authenticateAdmin();

        Livewire::test(EditProfessionalLink::class, ['record' => $link->getKey()])
            ->callAction('publish')
            ->assertNotified('Publish failed');

        $this->assertSame(PublicationStatus::Draft, $link->refresh()->status);
    }

    public function test_expertise_area_title_is_required_and_description_is_an_optional_pair(): void
    {
        $area = ExpertiseArea::factory()->create([
            'title_es' => 'Título sintético',
            'title_en' => 'Synthetic title',
            'description_es' => null,
            'description_en' => null,
        ]);
        $this->authenticateAdmin();

        Livewire::test(EditExpertiseArea::class, ['record' => $area->getKey()])
            ->callAction('publish')
            ->assertNotified('Publish succeeded');

        $this->assertSame(PublicationStatus::Published, $area->refresh()->status);

        $unpaired = ExpertiseArea::factory()->create([
            'title_es' => 'Título sintético',
            'title_en' => 'Synthetic title',
            'description_es' => 'Descripción sintética.',
            'description_en' => null,
        ]);

        Livewire::test(EditExpertiseArea::class, ['record' => $unpaired->getKey()])
            ->callAction('publish')
            ->assertNotified('Publish failed');

        $this->assertSame(PublicationStatus::Draft, $unpaired->refresh()->status);
    }

    public function test_work_principle_statement_is_a_required_pair_to_publish(): void
    {
        $principle = WorkPrinciple::factory()->create([
            'statement_es' => 'Principio sintético.',
            'statement_en' => null,
        ]);
        $this->authenticateAdmin();

        Livewire::test(EditWorkPrinciple::class, ['record' => $principle->getKey()])
            ->callAction('publish')
            ->assertNotified('Publish failed');

        $this->assertSame(PublicationStatus::Draft, $principle->refresh()->status);
    }

    // ---- Key change ----

    public function test_expertise_area_key_can_change_freely_before_publication(): void
    {
        $area = ExpertiseArea::factory()->create(['key' => 'synthetic-before']);
        $this->authenticateAdmin();

        Livewire::test(EditExpertiseArea::class, ['record' => $area->getKey()])
            ->assertActionExists('change_key', checkActionUsing: fn ($action) => ! $action->isConfirmationRequired())
            ->callAction('change_key', data: ['key' => 'synthetic-after'])
            ->assertNotified();

        $this->assertSame('synthetic-after', $area->refresh()->key);
    }

    public function test_expertise_area_key_change_requires_confirmation_once_locked(): void
    {
        // A real published row always satisfies the full publication
        // validator (PublishContent enforces it), so the fixture must too:
        // ChangePublicKey's save re-triggers the guard's full
        // assertPublishable() check on any update to published content.
        $area = ExpertiseArea::factory()->draft()->create([
            'key' => 'synthetic-locked',
            'title_es' => 'Título sintético',
            'title_en' => 'Synthetic title',
        ]);
        $this->forcePublished($area, visible: true);
        $this->assertTrue($area->key_locked);
        $this->authenticateAdmin();

        Livewire::test(EditExpertiseArea::class, ['record' => $area->getKey()])
            ->assertActionExists('change_key', checkActionUsing: fn ($action) => $action->isConfirmationRequired())
            ->callAction('change_key', data: ['key' => 'synthetic-relocked']);

        $this->assertSame('synthetic-relocked', $area->refresh()->key);
    }

    public function test_work_principle_key_can_change_freely_before_publication(): void
    {
        $principle = WorkPrinciple::factory()->create(['key' => 'synthetic-before']);
        $this->authenticateAdmin();

        Livewire::test(EditWorkPrinciple::class, ['record' => $principle->getKey()])
            ->assertActionExists('change_key', checkActionUsing: fn ($action) => ! $action->isConfirmationRequired())
            ->callAction('change_key', data: ['key' => 'synthetic-after']);

        $this->assertSame('synthetic-after', $principle->refresh()->key);
    }

    public function test_work_principle_key_change_requires_confirmation_once_locked(): void
    {
        // A real published row always satisfies the full publication
        // validator (PublishContent enforces it), so the fixture must too:
        // ChangePublicKey's save re-triggers the guard's full
        // assertPublishable() check on any update to published content.
        $principle = WorkPrinciple::factory()->draft()->create([
            'key' => 'synthetic-locked',
            'statement_es' => 'Principio sintético.',
            'statement_en' => 'Synthetic principle.',
        ]);
        $this->forcePublished($principle, visible: true);
        $this->assertTrue($principle->key_locked);
        $this->authenticateAdmin();

        Livewire::test(EditWorkPrinciple::class, ['record' => $principle->getKey()])
            ->assertActionExists('change_key', checkActionUsing: fn ($action) => $action->isConfirmationRequired())
            ->callAction('change_key', data: ['key' => 'synthetic-relocked']);

        $this->assertSame('synthetic-relocked', $principle->refresh()->key);
    }

    public function test_professional_link_has_no_change_key_action(): void
    {
        $link = ProfessionalLink::factory()->create(['type' => ProfessionalLinkType::LinkedIn]);
        $this->authenticateAdmin();

        Livewire::test(EditProfessionalLink::class, ['record' => $link->getKey()])
            ->assertActionDoesNotExist('change_key');
    }

    // ---- Editorial fields are not plain editable inputs ----

    public function test_editorial_fields_are_not_plain_editable_inputs(): void
    {
        $link = ProfessionalLink::factory()->create(['type' => ProfessionalLinkType::LinkedIn]);
        $area = ExpertiseArea::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(EditProfessionalLink::class, ['record' => $link->getKey()])
            ->assertFormFieldDoesNotExist('status')
            ->assertFormFieldDoesNotExist('is_visible')
            ->assertFormFieldDoesNotExist('published_at');

        Livewire::test(EditExpertiseArea::class, ['record' => $area->getKey()])
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
     * in SingletonPageTest/EditorialActionTest) since the editorial
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
