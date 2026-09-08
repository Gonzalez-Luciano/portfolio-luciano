<?php

namespace Tests\Feature\Filament;

use App\Enums\PublicationStatus;
use App\Filament\Pages\EditProfile;
use App\Filament\Pages\EditSiteConfiguration;
use App\Filament\Resources\ProfileResource;
use App\Filament\Resources\SiteConfigurationResource;
use App\Models\Profile;
use App\Models\SiteConfiguration;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class SingletonPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_reach_the_edit_profile_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(EditProfile::getUrl())
            ->assertOk();
    }

    public function test_administrator_can_reach_the_edit_site_configuration_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(EditSiteConfiguration::getUrl())
            ->assertOk();
    }

    public function test_guest_is_redirected_from_the_edit_profile_page(): void
    {
        $this->get(EditProfile::getUrl())
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_guest_is_redirected_from_the_edit_site_configuration_page(): void
    {
        $this->get(EditSiteConfiguration::getUrl())
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_non_administrator_is_denied_the_edit_profile_page(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(EditProfile::getUrl())
            ->assertForbidden();
    }

    public function test_non_administrator_is_denied_the_edit_site_configuration_page(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(EditSiteConfiguration::getUrl())
            ->assertForbidden();
    }

    public function test_no_filament_resource_is_registered_for_either_singleton_entity(): void
    {
        $this->assertFalse(class_exists(ProfileResource::class));
        $this->assertFalse(class_exists(SiteConfigurationResource::class));
    }

    public function test_edit_profile_page_has_no_create_duplicate_or_delete_action(): void
    {
        $this->authenticateAdmin();

        Livewire::test(EditProfile::class)
            ->assertActionDoesNotExist('create')
            ->assertActionDoesNotExist('duplicate')
            ->assertActionDoesNotExist('delete');
    }

    public function test_edit_site_configuration_page_has_no_create_duplicate_or_delete_action(): void
    {
        $this->authenticateAdmin();

        Livewire::test(EditSiteConfiguration::class)
            ->assertActionDoesNotExist('create')
            ->assertActionDoesNotExist('duplicate')
            ->assertActionDoesNotExist('delete');
    }

    public function test_opening_the_edit_profile_page_ensures_a_missing_default_row_exists(): void
    {
        Profile::query()->where('singleton_key', 'default')->delete();
        $this->assertSame(0, Profile::query()->count());

        $this->authenticateAdmin();

        Livewire::test(EditProfile::class);

        $this->assertSame(1, Profile::query()->count());
        $this->assertSame('default', Profile::query()->first()->singleton_key);
        $this->assertSame(PublicationStatus::Draft, Profile::query()->first()->status);
    }

    public function test_opening_the_edit_site_configuration_page_ensures_a_missing_default_row_exists(): void
    {
        SiteConfiguration::query()->where('singleton_key', 'default')->delete();
        $this->assertSame(0, SiteConfiguration::query()->count());

        $this->authenticateAdmin();

        Livewire::test(EditSiteConfiguration::class);

        $this->assertSame(1, SiteConfiguration::query()->count());
        $this->assertSame('default', SiteConfiguration::query()->first()->singleton_key);
    }

    public function test_a_public_profile_get_never_creates_a_missing_singleton(): void
    {
        Profile::query()->where('singleton_key', 'default')->delete();

        $this->getJson('/api/v1/es/profile')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');

        $this->assertSame(0, Profile::query()->count());
    }

    public function test_a_public_site_get_never_creates_a_missing_singleton(): void
    {
        SiteConfiguration::query()->where('singleton_key', 'default')->delete();

        $this->getJson('/api/v1/en/site')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');

        $this->assertSame(0, SiteConfiguration::query()->count());
    }

    public function test_edit_profile_form_renders_spanish_and_english_tabs(): void
    {
        $this->authenticateAdmin();

        Livewire::test(EditProfile::class)
            ->assertFormFieldExists('headline_es')
            ->assertFormFieldExists('headline_en')
            ->assertFormFieldExists('short_summary_es')
            ->assertFormFieldExists('short_summary_en')
            ->assertFormFieldExists('introduction_es')
            ->assertFormFieldExists('introduction_en')
            ->assertFormFieldExists('availability_es')
            ->assertFormFieldExists('availability_en')
            ->assertFormFieldExists('cta_es')
            ->assertFormFieldExists('cta_en');
    }

    public function test_edit_site_configuration_form_renders_spanish_and_english_tabs(): void
    {
        $this->authenticateAdmin();

        Livewire::test(EditSiteConfiguration::class)
            ->assertFormFieldExists('projects_empty_message_es')
            ->assertFormFieldExists('projects_empty_message_en')
            ->assertFormFieldExists('contact_intro_es')
            ->assertFormFieldExists('contact_intro_en')
            ->assertFormFieldExists('technology_backend_label_es')
            ->assertFormFieldExists('technology_backend_label_en')
            ->assertFormFieldExists('technology_data_label_es')
            ->assertFormFieldExists('technology_data_label_en')
            ->assertFormFieldExists('technology_integration_label_es')
            ->assertFormFieldExists('technology_integration_label_en')
            ->assertFormFieldExists('technology_collaboration_label_es')
            ->assertFormFieldExists('technology_collaboration_label_en');
    }

    public function test_edit_profile_editorial_fields_are_read_only_not_editable_inputs(): void
    {
        $this->authenticateAdmin();

        Livewire::test(EditProfile::class)
            ->assertFormFieldDoesNotExist('status')
            ->assertFormFieldDoesNotExist('is_visible')
            ->assertFormFieldDoesNotExist('published_at');
    }

    public function test_edit_site_configuration_editorial_fields_are_read_only_not_editable_inputs(): void
    {
        $this->authenticateAdmin();

        Livewire::test(EditSiteConfiguration::class)
            ->assertFormFieldDoesNotExist('status')
            ->assertFormFieldDoesNotExist('is_visible')
            ->assertFormFieldDoesNotExist('published_at');
    }

    public function test_publish_action_publishes_a_complete_profile_draft(): void
    {
        $this->completeProfileDraft();
        $this->authenticateAdmin();

        Livewire::test(EditProfile::class)
            ->assertActionExists('publish')
            ->callAction('publish');

        $profile = Profile::query()->where('singleton_key', 'default')->first();
        $this->assertSame(PublicationStatus::Published, $profile->status);
        $this->assertFalse($profile->is_visible);
        $this->assertNotNull($profile->published_at);
    }

    public function test_publish_action_on_an_incomplete_profile_draft_surfaces_a_failure_and_does_not_change_status(): void
    {
        $this->authenticateAdmin();

        Livewire::test(EditProfile::class)
            ->callAction('publish')
            ->assertNotified('Publish failed');

        $profile = Profile::query()->where('singleton_key', 'default')->first();
        $this->assertSame(PublicationStatus::Draft, $profile->status);
        $this->assertFalse($profile->is_visible);
        $this->assertNull($profile->published_at);
    }

    public function test_show_action_makes_published_hidden_profile_visible(): void
    {
        $this->completeProfileDraft();
        Profile::query()->where('singleton_key', 'default')->update([
            'status' => PublicationStatus::Published->value,
            'is_visible' => false,
            'published_at' => now(),
        ]);
        $this->authenticateAdmin();

        Livewire::test(EditProfile::class)
            ->assertActionExists('show')
            ->callAction('show');

        $profile = Profile::query()->where('singleton_key', 'default')->first();
        $this->assertTrue($profile->is_visible);
    }

    public function test_hide_action_withdraws_visibility_from_a_published_visible_profile(): void
    {
        $this->completeProfileDraft();
        Profile::query()->where('singleton_key', 'default')->update([
            'status' => PublicationStatus::Published->value,
            'is_visible' => true,
            'published_at' => now(),
        ]);
        $this->authenticateAdmin();

        Livewire::test(EditProfile::class)
            ->assertActionExists('hide')
            ->callAction('hide');

        $profile = Profile::query()->where('singleton_key', 'default')->first();
        $this->assertSame(PublicationStatus::Published, $profile->status);
        $this->assertFalse($profile->is_visible);
    }

    public function test_return_to_draft_action_requires_confirmation_and_returns_published_profile_to_draft(): void
    {
        $this->completeProfileDraft();
        Profile::query()->where('singleton_key', 'default')->update([
            'status' => PublicationStatus::Published->value,
            'is_visible' => true,
            'published_at' => now(),
        ]);
        $this->authenticateAdmin();

        $component = Livewire::test(EditProfile::class);
        $component->assertActionExists(
            'return_to_draft',
            checkActionUsing: fn ($action) => $action->isConfirmationRequired(),
        );

        $component->callAction('return_to_draft');

        $profile = Profile::query()->where('singleton_key', 'default')->first();
        $this->assertSame(PublicationStatus::Draft, $profile->status);
        $this->assertFalse($profile->is_visible);
        $this->assertNull($profile->published_at);
    }

    private function authenticateAdmin(): User
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        return $admin;
    }

    private function completeProfileDraft(): void
    {
        Profile::query()->where('singleton_key', 'default')->update([
            'name' => 'Synthetic Portfolio Engineer',
            'headline_es' => 'Especialista técnico sintético',
            'headline_en' => 'Synthetic technical specialist',
            'short_summary_es' => 'Resumen técnico sintético.',
            'short_summary_en' => 'Synthetic technical summary.',
            'introduction_es' => 'Introducción técnica sintética.',
            'introduction_en' => 'Synthetic technical introduction.',
            'availability_es' => 'Disponibilidad sintética.',
            'availability_en' => 'Synthetic availability.',
            'cta_es' => 'Ver caso sintético',
            'cta_en' => 'View synthetic case',
        ]);
    }
}
