<?php

namespace Tests\Feature\Assets;

use App\Domain\Assets\AssetLifecycleService;
use App\Domain\Assets\AssetOperationException;
use App\Domain\Content\Actions\RemoveOwnedAsset;
use App\Domain\Content\Actions\ReplaceOwnedAsset;
use App\Domain\Content\Actions\UpdateOwnedAssetAltText;
use App\Domain\Publishing\PublicationValidationException;
use App\Enums\PublicationStatus;
use App\Models\Profile;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class AssetErrorPropagationTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_a_photo_without_alt_text_on_a_published_hidden_profile_surfaces_the_publication_issues(): void
    {
        $profile = $this->publishedProfile(visible: false);

        try {
            app(ReplaceOwnedAsset::class)($profile, $this->png('portrait.png'));
            $this->fail('A photo without alt text must be rejected on published content.');
        } catch (PublicationValidationException $exception) {
            $paths = array_map(static fn ($issue): string => $issue->path, $exception->issues());
            $this->assertContains('photo_alt_es', $paths);
            $this->assertContains('photo_alt_en', $paths);
        }

        $profile->refresh();
        $this->assertNull($profile->photo_private_path);
        $this->assertNull($profile->photo_public_path);
        $this->assertNull($profile->photo_mime);
        $this->assertSame(PublicationStatus::Published, $profile->status);
        $this->assertFalse($profile->is_visible);
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_a_photo_without_alt_text_on_a_published_visible_profile_leaves_no_public_copy(): void
    {
        $profile = $this->publishedProfile(visible: true);

        try {
            app(ReplaceOwnedAsset::class)($profile, $this->png('portrait.png'));
            $this->fail('A photo without alt text must be rejected on published content.');
        } catch (PublicationValidationException) {
        }

        $this->assertNull($profile->fresh()->photo_private_path);
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_the_same_photo_succeeds_once_both_alt_texts_exist_and_stays_private_while_hidden(): void
    {
        $profile = $this->publishedProfile(visible: false);
        $profile = app(UpdateOwnedAssetAltText::class)($profile, 'Retrato sintético', 'Synthetic portrait');

        $updated = app(ReplaceOwnedAsset::class)($profile, $this->png('portrait.png'));

        $this->assertMatchesRegularExpression('#^profiles/[0-9a-f-]+\.png$#', $updated->photo_private_path);
        $this->assertNull($updated->photo_public_path);
        Storage::disk('local')->assertExists($updated->photo_private_path);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_replacing_a_visible_photo_publishes_a_new_copy_and_cleans_the_previous_files_and_remove_withdraws_both(): void
    {
        $profile = $this->publishedProfile(visible: true);
        $profile = app(UpdateOwnedAssetAltText::class)($profile, 'Retrato sintético', 'Synthetic portrait');
        $first = app(ReplaceOwnedAsset::class)($profile, $this->png('first.png'));
        $this->assertNotNull($first->photo_public_path);
        Storage::disk('public')->assertExists($first->photo_public_path);

        $second = app(ReplaceOwnedAsset::class)($first, $this->png('second.png'));

        Storage::disk('local')->assertMissing($first->photo_private_path);
        Storage::disk('public')->assertMissing($first->photo_public_path);
        Storage::disk('local')->assertExists($second->photo_private_path);
        Storage::disk('public')->assertExists($second->photo_public_path);

        $removed = app(RemoveOwnedAsset::class)($second);

        $this->assertNull($removed->fresh()->photo_private_path);
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_clearing_alt_text_while_a_photo_exists_on_published_content_surfaces_the_publication_issues(): void
    {
        $profile = $this->publishedProfile(visible: false);
        $profile = app(UpdateOwnedAssetAltText::class)($profile, 'Retrato sintético', 'Synthetic portrait');
        $profile = app(ReplaceOwnedAsset::class)($profile, $this->png('portrait.png'));

        $this->expectException(PublicationValidationException::class);

        app(UpdateOwnedAssetAltText::class)($profile, null, null);
    }

    public function test_an_unexpected_internal_failure_is_still_generic_and_logged(): void
    {
        Log::spy();
        $profile = $this->publishedProfile(visible: false);
        $stale = $profile->fresh();
        $stale->setAttribute('id', 999999);

        try {
            app(AssetLifecycleService::class)->updateAltText($stale, 'a', 'b');
            $this->fail('A missing row must not succeed.');
        } catch (AssetOperationException $exception) {
            $this->assertSame('The asset operation could not be completed.', $exception->getMessage());
            $this->assertNotNull($exception->getPrevious());
        }

        Log::shouldHaveReceived('warning')->withArgs(
            static fn (string $message, array $context): bool => $message === 'Owned asset lifecycle operation failed.'
                && $context['operation'] === 'update_alt_text'
                && ! array_key_exists('message', $context),
        )->once();
    }

    private function publishedProfile(bool $visible): Profile
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
            'is_visible' => $visible,
            'published_at' => now(),
        ]);

        return Profile::query()->where('singleton_key', 'default')->firstOrFail();
    }

    private function png(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL9SAAAAABJRU5ErkJggg=='),
        );
    }
}
