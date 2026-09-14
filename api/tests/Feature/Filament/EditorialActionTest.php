<?php

namespace Tests\Feature\Filament;

use App\Domain\Assets\AssetLifecycleService;
use App\Domain\Content\Actions\PublishContent;
use App\Domain\Content\Actions\ShowContent;
use App\Enums\PublicationStatus;
use App\Filament\Pages\EditProfile;
use App\Filament\Pages\EditSiteConfiguration;
use App\Models\CvDocument;
use App\Models\ExpertiseArea;
use App\Models\ProfessionalLink;
use App\Models\Profile;
use App\Models\SiteConfiguration;
use App\Models\User;
use App\Models\WorkPrinciple;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

final class EditorialActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_name_is_not_required_to_save_a_draft(): void
    {
        $this->authenticateAdmin();

        Livewire::test(EditProfile::class)
            ->fillForm([
                'name' => '',
                'headline_es' => 'Encabezado',
                'headline_en' => 'Headline',
            ])
            ->call('save')
            ->assertNotified('Saved');

        $profile = Profile::query()->where('singleton_key', 'default')->first();
        $this->assertNull($profile->name);
        $this->assertSame(PublicationStatus::Draft, $profile->status);
    }

    public function test_profile_name_is_required_to_publish(): void
    {
        $this->completeProfileFieldsExceptName();
        $this->authenticateAdmin();

        Livewire::test(EditProfile::class)
            ->callAction('publish')
            ->assertNotified('Publish failed');

        $profile = Profile::query()->where('singleton_key', 'default')->first();
        $this->assertSame(PublicationStatus::Draft, $profile->status);
    }

    public function test_profile_publishes_once_name_is_present(): void
    {
        $this->completeProfileFieldsExceptName();
        Profile::query()->where('singleton_key', 'default')->update(['name' => 'Synthetic Portfolio Engineer']);
        $this->authenticateAdmin();

        Livewire::test(EditProfile::class)
            ->callAction('publish')
            ->assertNotified('Publish succeeded');

        $profile = Profile::query()->where('singleton_key', 'default')->first();
        $this->assertSame(PublicationStatus::Published, $profile->status);
    }

    public function test_site_configuration_bilingual_field_requires_both_locales_to_publish(): void
    {
        $this->completeSiteConfigurationFields();
        SiteConfiguration::query()->where('singleton_key', 'default')->update([
            'technology_backend_label_en' => null,
        ]);
        $this->authenticateAdmin();

        Livewire::test(EditSiteConfiguration::class)
            ->callAction('publish')
            ->assertNotified('Publish failed');

        $configuration = SiteConfiguration::query()->where('singleton_key', 'default')->first();
        $this->assertSame(PublicationStatus::Draft, $configuration->status);
    }

    public function test_site_configuration_publishes_once_every_bilingual_field_is_complete(): void
    {
        $this->completeSiteConfigurationFields();
        $this->authenticateAdmin();

        Livewire::test(EditSiteConfiguration::class)
            ->callAction('publish')
            ->assertNotified('Publish succeeded');

        $configuration = SiteConfiguration::query()->where('singleton_key', 'default')->first();
        $this->assertSame(PublicationStatus::Published, $configuration->status);
        $this->assertFalse($configuration->is_visible);
        $this->assertNotNull($configuration->published_at);
    }

    public function test_site_configuration_publication_does_not_depend_on_related_site_collections_or_cv(): void
    {
        $this->assertSame(0, ProfessionalLink::query()->count());
        $this->assertSame(0, ExpertiseArea::query()->count());
        $this->assertSame(0, WorkPrinciple::query()->count());
        $this->assertSame(0, CvDocument::query()->count());

        $this->completeSiteConfigurationFields();
        $this->authenticateAdmin();

        Livewire::test(EditSiteConfiguration::class)
            ->callAction('publish')
            ->assertNotified('Publish succeeded');

        $configuration = SiteConfiguration::query()->where('singleton_key', 'default')->first();
        $this->assertSame(PublicationStatus::Published, $configuration->status);
    }

    public function test_profile_photo_alt_text_is_not_required_for_publication_without_a_photo(): void
    {
        $this->completeProfileFieldsExceptName();
        Profile::query()->where('singleton_key', 'default')->update(['name' => 'Synthetic Portfolio Engineer']);
        $this->authenticateAdmin();

        Livewire::test(EditProfile::class)
            ->callAction('publish')
            ->assertNotified('Publish succeeded');
    }

    public function test_profile_photo_alt_text_is_required_in_both_locales_when_a_photo_is_present(): void
    {
        $this->completeProfileFieldsExceptName();
        Profile::query()->where('singleton_key', 'default')->update([
            'name' => 'Synthetic Portfolio Engineer',
            'photo_private_path' => 'profiles/synthetic.jpg',
            'photo_mime' => 'image/jpeg',
            'photo_size' => 1024,
            'photo_alt_es' => null,
            'photo_alt_en' => null,
        ]);
        $this->authenticateAdmin();

        Livewire::test(EditProfile::class)
            ->callAction('publish')
            ->assertNotified('Publish failed');

        $profile = Profile::query()->where('singleton_key', 'default')->first();
        $this->assertSame(PublicationStatus::Draft, $profile->status);
    }

    public function test_profile_photo_alt_text_fails_publication_when_only_one_locale_is_present(): void
    {
        $this->completeProfileFieldsExceptName();
        Profile::query()->where('singleton_key', 'default')->update([
            'name' => 'Synthetic Portfolio Engineer',
            'photo_private_path' => 'profiles/synthetic.jpg',
            'photo_mime' => 'image/jpeg',
            'photo_size' => 1024,
            'photo_alt_es' => 'Retrato sintético',
            'photo_alt_en' => null,
        ]);
        $this->authenticateAdmin();

        Livewire::test(EditProfile::class)
            ->callAction('publish')
            ->assertNotified('Publish failed');
    }

    public function test_profile_with_a_photo_publishes_once_both_alt_locales_are_present(): void
    {
        $this->completeProfileFieldsExceptName();
        Profile::query()->where('singleton_key', 'default')->update([
            'name' => 'Synthetic Portfolio Engineer',
            'photo_private_path' => 'profiles/synthetic.jpg',
            'photo_mime' => 'image/jpeg',
            'photo_size' => 1024,
            'photo_alt_es' => 'Retrato sintético',
            'photo_alt_en' => 'Synthetic portrait',
        ]);
        $this->authenticateAdmin();

        Livewire::test(EditProfile::class)
            ->callAction('publish')
            ->assertNotified('Publish succeeded');

        $profile = Profile::query()->where('singleton_key', 'default')->first();
        $this->assertSame(PublicationStatus::Published, $profile->status);
    }

    public function test_uploading_a_first_photo_with_alt_text_on_a_published_profile_saves_both(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $this->completeProfileFieldsExceptName();
        Profile::query()->where('singleton_key', 'default')->update(['name' => 'Synthetic Portfolio Engineer']);
        app(PublishContent::class)(Profile::query()->where('singleton_key', 'default')->first());
        $this->authenticateAdmin();

        Livewire::test(EditProfile::class)
            ->fillForm([
                'photo' => $this->png('profile.png'),
                'photo_alt_es' => 'Retrato sintético',
                'photo_alt_en' => 'Synthetic portrait',
            ])
            ->call('save')
            ->assertNotified('Saved');

        $profile = Profile::query()->where('singleton_key', 'default')->first();
        $this->assertNotNull($profile->photo_private_path);
        $this->assertSame('Retrato sintético', $profile->photo_alt_es);
        $this->assertSame('Synthetic portrait', $profile->photo_alt_en);
        Storage::disk('local')->assertExists($profile->photo_private_path);
    }

    public function test_editing_photo_alt_text_on_a_published_visible_profile_invalidates_the_public_cache(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $this->completeProfileFieldsExceptName();
        Profile::query()->where('singleton_key', 'default')->update([
            'name' => 'Synthetic Portfolio Engineer',
            'photo_alt_es' => 'Retrato original',
            'photo_alt_en' => 'Original portrait',
        ]);
        $profile = Profile::query()->where('singleton_key', 'default')->first();
        app(AssetLifecycleService::class)->replace($profile, $this->png('profile.png'));

        $profile = app(PublishContent::class)(Profile::query()->where('singleton_key', 'default')->first());
        app(ShowContent::class)($profile);

        // Prime the public cache with the original alt text.
        $this->getJson('/api/v1/en/profile')
            ->assertOk()
            ->assertJsonPath('data.photo.alt', 'Original portrait');

        $this->authenticateAdmin();

        Livewire::test(EditProfile::class)
            ->fillForm(['photo_alt_en' => 'Updated portrait'])
            ->call('save')
            ->assertNotified('Saved');

        $this->assertSame(
            'Updated portrait',
            Profile::query()->where('singleton_key', 'default')->first()->photo_alt_en,
        );

        $this->getJson('/api/v1/en/profile')
            ->assertOk()
            ->assertJsonPath('data.photo.alt', 'Updated portrait');
    }

    private function png(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL9SAAAAABJRU5ErkJggg=='),
        );
    }

    private function authenticateAdmin(): User
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        return $admin;
    }

    private function completeProfileFieldsExceptName(): void
    {
        Profile::query()->where('singleton_key', 'default')->update([
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

    private function completeSiteConfigurationFields(): void
    {
        SiteConfiguration::query()->where('singleton_key', 'default')->update([
            'projects_empty_message_es' => 'No hay proyectos sintéticos publicados.',
            'projects_empty_message_en' => 'No synthetic projects are published.',
            'contact_intro_es' => 'Contacto técnico sintético.',
            'contact_intro_en' => 'Synthetic technical contact.',
            'technology_backend_label_es' => 'Backend sintético',
            'technology_backend_label_en' => 'Synthetic backend',
            'technology_data_label_es' => 'Datos sintéticos',
            'technology_data_label_en' => 'Synthetic data',
            'technology_integration_label_es' => 'Integración sintética',
            'technology_integration_label_en' => 'Synthetic integration',
            'technology_collaboration_label_es' => 'Colaboración sintética',
            'technology_collaboration_label_en' => 'Synthetic collaboration',
        ]);
    }
}
