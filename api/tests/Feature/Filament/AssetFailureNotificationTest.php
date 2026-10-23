<?php

namespace Tests\Feature\Filament;

use App\Domain\Assets\AssetOperationException;
use App\Enums\PublicationStatus;
use App\Filament\Pages\EditProfile;
use App\Filament\Support\EditorialActions;
use App\Models\Profile;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

final class AssetFailureNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    public function test_saving_a_photo_without_alt_text_on_a_published_profile_shows_the_publication_issues(): void
    {
        $this->publishedHiddenProfile();

        Livewire::test(EditProfile::class)
            ->fillForm(['photo' => $this->png('portrait.png')])
            ->call('save')
            ->assertNotified(
                Notification::make()
                    ->danger()
                    ->title('Save failed')
                    ->body("[required_translation] photo_alt_es: Image alt text is required in both locales.\n[required_translation] photo_alt_en: Image alt text is required in both locales.")
            );

        $this->assertNull(Profile::query()->sole()->photo_private_path);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_saving_the_same_photo_with_both_alt_texts_succeeds(): void
    {
        $this->publishedHiddenProfile();

        Livewire::test(EditProfile::class)
            ->fillForm([
                'photo' => $this->png('portrait.png'),
                'photo_alt_es' => 'Retrato sintético',
                'photo_alt_en' => 'Synthetic portrait',
            ])
            ->call('save')
            ->assertNotified('Saved');

        $profile = Profile::query()->sole();
        $this->assertNotNull($profile->photo_private_path);
        $this->assertNull($profile->photo_public_path);
    }

    public function test_an_unexpected_exception_never_shows_its_raw_message(): void
    {
        $raw = new QueryException('mysql', 'select * from secrets where token = ?', ['abc'], new \RuntimeException('SQLSTATE[42S02] /var/www/html/secret'));

        EditorialActions::notifyAssetFailure('Save', $raw);

        $notifications = session()->get('filament.notifications', []);
        $body = end($notifications)['body'] ?? '';
        $this->assertSame(EditorialActions::GENERIC_FAILURE, $body);
        $this->assertStringNotContainsString('SQLSTATE', $body);
    }

    public function test_a_curated_asset_operation_message_is_shown(): void
    {
        EditorialActions::notifyAssetFailure('Save', new AssetOperationException('The private original could not be stored.'));

        $notifications = session()->get('filament.notifications', []);
        $this->assertSame('The private original could not be stored.', end($notifications)['body'] ?? null);
    }

    private function publishedHiddenProfile(): void
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
            'status' => PublicationStatus::Published->value,
            'is_visible' => false,
            'published_at' => now(),
        ]);
    }

    private function png(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL9SAAAAABJRU5ErkJggg=='),
        );
    }
}
