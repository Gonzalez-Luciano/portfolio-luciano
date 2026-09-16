<?php

namespace Tests\Feature\Filament;

use App\Enums\LanguageLevel;
use App\Enums\PublicationStatus;
use App\Filament\Resources\Languages\Pages\CreateLanguage;
use App\Filament\Resources\Languages\Pages\EditLanguage;
use App\Filament\Resources\Languages\Pages\ListLanguages;
use App\Models\Language;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class LanguageResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_reach_language_pages_and_guests_cannot(): void
    {
        $language = Language::factory()->create();

        $this->get(ListLanguages::getUrl())->assertRedirect(route('filament.admin.auth.login'));

        $this->authenticateAdmin();
        $this->get(ListLanguages::getUrl())->assertOk();
        $this->get(CreateLanguage::getUrl())->assertOk();
        $this->get(EditLanguage::getUrl(['record' => $language]))->assertOk();
    }

    public function test_non_administrator_is_denied_the_language_list(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]));

        $this->get(ListLanguages::getUrl())->assertForbidden();
    }

    public function test_creating_a_language_with_a_duplicate_key_is_rejected_as_a_validation_error(): void
    {
        Language::factory()->create(['key' => 'synthetic-duplicate']);
        $this->authenticateAdmin();

        Livewire::test(CreateLanguage::class)
            ->fillForm(['key' => 'synthetic-duplicate', 'name_es' => 'Nombre', 'name_en' => 'Name', 'level' => 'b2'])
            ->call('create')
            ->assertHasFormErrors(['key']);

        $this->assertSame(1, Language::query()->count());
    }

    public function test_administrator_creates_several_languages_as_ordered_drafts(): void
    {
        $this->authenticateAdmin();

        foreach (['spanish' => 'native', 'english' => 'b2'] as $key => $level) {
            Livewire::test(CreateLanguage::class)
                ->fillForm(['key' => $key, 'name_es' => 'Nombre', 'name_en' => 'Name', 'level' => $level])
                ->call('create')
                ->assertHasNoFormErrors();
        }

        $languages = Language::query()->orderBy('position')->get();
        $this->assertSame(['spanish', 'english'], $languages->pluck('key')->all());
        $this->assertSame([LanguageLevel::Native, LanguageLevel::B2], $languages->pluck('level')->all());
        $this->assertSame([0, 1], $languages->pluck('position')->all());
    }

    public function test_level_select_rejects_values_outside_the_enum(): void
    {
        $this->authenticateAdmin();

        Livewire::test(CreateLanguage::class)
            ->fillForm(['key' => 'synthetic-language', 'level' => 'fluent'])
            ->call('create')
            ->assertHasFormErrors(['level']);

        $this->assertSame(0, Language::query()->count());
    }

    public function test_publication_requires_both_names_and_a_level(): void
    {
        $language = Language::factory()->create(['name_es' => 'Inglés']);
        $this->authenticateAdmin();

        Livewire::test(EditLanguage::class, ['record' => $language->getKey()])
            ->callAction('publish')
            ->assertNotified('Publish failed');
        $this->assertSame(PublicationStatus::Draft, $language->refresh()->status);

        Livewire::test(EditLanguage::class, ['record' => $language->getKey()])
            ->fillForm(['name_en' => 'English', 'level' => 'b2'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->callAction('publish')
            ->assertNotified('Publish succeeded');
        $this->assertSame(PublicationStatus::Published, $language->refresh()->status);
    }

    public function test_reorder_action_changes_the_position(): void
    {
        $language = Language::factory()->create(['position' => 0]);
        $this->authenticateAdmin();

        Livewire::test(ListLanguages::class)
            ->callTableAction('reorder', $language, data: ['position' => 3])
            ->assertHasNoTableActionErrors();

        $this->assertSame(3, $language->refresh()->position);
    }

    public function test_reorder_action_rejects_a_negative_position(): void
    {
        $language = Language::factory()->create(['position' => 3]);
        $this->authenticateAdmin();

        Livewire::test(ListLanguages::class)
            ->callTableAction('reorder', $language, data: ['position' => -1])
            ->assertHasTableActionErrors(['position']);

        $this->assertSame(3, $language->refresh()->position);
    }

    public function test_delete_requires_confirmation_and_uses_delete_content(): void
    {
        $language = Language::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(EditLanguage::class, ['record' => $language->getKey()])
            ->assertActionExists('delete', checkActionUsing: fn ($action) => $action->isConfirmationRequired())
            ->callAction('delete');

        $this->assertSame(0, Language::query()->count());
    }

    public function test_table_filters_by_status_and_visibility(): void
    {
        $draft = Language::factory()->draft()->create();
        $publishedHidden = Language::factory()->draft()->create();
        $this->forcePublished($publishedHidden, visible: false);
        $publishedVisible = Language::factory()->draft()->create();
        $this->forcePublished($publishedVisible, visible: true);
        $this->authenticateAdmin();

        Livewire::test(ListLanguages::class)
            ->filterTable('status', PublicationStatus::Draft->value)
            ->assertCanSeeTableRecords([$draft])
            ->assertCanNotSeeTableRecords([$publishedHidden, $publishedVisible]);

        Livewire::test(ListLanguages::class)
            ->filterTable('is_visible', true)
            ->assertCanSeeTableRecords([$publishedVisible])
            ->assertCanNotSeeTableRecords([$draft, $publishedHidden]);
    }

    public function test_key_can_change_freely_before_publication(): void
    {
        $language = Language::factory()->create(['key' => 'synthetic-before']);
        $this->authenticateAdmin();

        Livewire::test(EditLanguage::class, ['record' => $language->getKey()])
            ->assertActionExists('change_key', checkActionUsing: fn ($action) => ! $action->isConfirmationRequired())
            ->callAction('change_key', data: ['key' => 'synthetic-after']);

        $this->assertSame('synthetic-after', $language->refresh()->key);
    }

    public function test_key_change_requires_confirmation_once_locked(): void
    {
        // A real published row always satisfies the full publication
        // validator (PublishContent enforces it), so the fixture must too:
        // ChangePublicKey's save re-triggers the guard's full
        // assertPublishable() check on any update to published content.
        $language = Language::factory()->draft()->create([
            'key' => 'synthetic-locked',
            'name_es' => 'Idioma sintético',
            'name_en' => 'Synthetic language',
            'level' => LanguageLevel::B2,
        ]);
        $this->forcePublished($language, visible: true);
        $this->assertTrue($language->key_locked);
        $this->authenticateAdmin();

        Livewire::test(EditLanguage::class, ['record' => $language->getKey()])
            ->assertActionExists('change_key', checkActionUsing: fn ($action) => $action->isConfirmationRequired())
            ->callAction('change_key', data: ['key' => 'synthetic-relocked']);

        $this->assertSame('synthetic-relocked', $language->refresh()->key);
    }

    public function test_editorial_fields_are_not_plain_editable_inputs(): void
    {
        $language = Language::factory()->create();
        $this->authenticateAdmin();

        Livewire::test(EditLanguage::class, ['record' => $language->getKey()])
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
