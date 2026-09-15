<?php

namespace Tests\Feature\Assets;

use App\Domain\Assets\AssetLifecycleService;
use App\Domain\Assets\AssetOperationException;
use App\Domain\Assets\AssetValidationException;
use App\Domain\Content\Actions\RemoveOwnedAsset;
use App\Models\CvDocument;
use App\Models\Profile;
use App\Models\ProjectImage;
use App\Models\Technology;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

final class AssetLifecycleTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_it_stages_valid_profile_originals_under_generated_private_paths(): void
    {
        $profile = Profile::query()->firstOrFail();

        $staged = app(AssetLifecycleService::class)->stage($profile, $this->png('portrait.png'));

        $this->assertMatchesRegularExpression('#^profiles/[0-9a-f-]+\\.png$#', $staged->privatePath);
        Storage::disk('local')->assertExists($staged->privatePath);
        $this->assertNull($staged->publicPath);
    }

    public function test_it_rejects_invalid_real_content_and_size_for_each_owned_asset_kind(): void
    {
        $owners = [
            [Profile::query()->firstOrFail(), UploadedFile::fake()->create('not-an-image.jpg', 12, 'text/plain')],
            [new ProjectImage, UploadedFile::fake()->create('vector.svg', 12, 'image/svg+xml')],
            [Technology::factory()->create(), $this->png('large.png', str_repeat('x', 1024 * 1024))],
            [CvDocument::factory()->create(), UploadedFile::fake()->create('not-a-pdf.pdf', 12, 'text/plain')],
        ];

        foreach ($owners as [$owner, $upload]) {
            try {
                app(AssetLifecycleService::class)->stage($owner, $upload);
                $this->fail('Invalid owned asset content must be rejected before it is staged.');
            } catch (AssetValidationException) {
                $this->assertSame([], Storage::disk('local')->allFiles());
            }
        }
    }

    public function test_replacing_a_hidden_asset_commits_only_the_new_private_reference_and_cleans_the_old_original(): void
    {
        $technology = Technology::factory()->create();
        $service = app(AssetLifecycleService::class);
        $service->replace($technology, $this->png('first.png'));
        $firstPath = $technology->fresh()->icon_private_path;

        $service->replace($technology->fresh(), $this->png('second.png'));
        $technology->refresh();

        $this->assertNotSame($firstPath, $technology->icon_private_path);
        $this->assertNull($technology->icon_public_path);
        Storage::disk('local')->assertMissing($firstPath);
        Storage::disk('local')->assertExists($technology->icon_private_path);
        Storage::disk('public')->assertDirectoryEmpty('/');
    }

    public function test_cv_replacement_stays_private_and_never_creates_a_public_copy(): void
    {
        $document = CvDocument::factory()->create();

        app(AssetLifecycleService::class)->replace($document, UploadedFile::fake()->createWithContent('resume.pdf', "%PDF-1.4\nsynthetic"));

        $document->refresh();
        $this->assertMatchesRegularExpression('#^cv/[0-9a-f-]+\\.pdf$#', $document->private_path);
        $this->assertSame('application/pdf', $document->mime);
        Storage::disk('local')->assertExists($document->private_path);
        Storage::disk('public')->assertDirectoryEmpty('/');
    }

    public function test_private_upload_failure_leaves_no_reference_or_orphan(): void
    {
        $technology = Technology::factory()->create();
        $local = Mockery::mock();
        $local->shouldReceive('putFileAs')->once()->andReturnFalse();
        Storage::shouldReceive('disk')->with('local')->andReturn($local);

        try {
            app(AssetLifecycleService::class)->replace($technology, $this->png('upload.png'));
            $this->fail('A private upload failure must be controlled.');
        } catch (AssetOperationException) {
            $technology->refresh();
            $this->assertNull($technology->icon_private_path);
            $this->assertNull($technology->icon_public_path);
        }
    }

    public function test_private_cleanup_failure_restores_the_old_hidden_reference(): void
    {
        $technology = Technology::factory()->create();
        $service = app(AssetLifecycleService::class);
        $service->replace($technology, $this->png('old.png'));
        $oldPath = $technology->fresh()->icon_private_path;
        $local = Mockery::mock();
        $local->shouldReceive('delete')->with($oldPath)->once()->andReturnFalse();
        Storage::shouldReceive('disk')->with('local')->andReturn($local);

        try {
            app(RemoveOwnedAsset::class)($technology->fresh());
            $this->fail('A failed private cleanup must not claim success.');
        } catch (AssetOperationException) {
            $this->assertSame($oldPath, $technology->fresh()->icon_private_path);
        }
    }

    public function test_database_failure_removes_the_staged_file_and_preserves_the_old_reference(): void
    {
        $technology = Technology::factory()->create();
        $service = app(AssetLifecycleService::class);
        $service->replace($technology, $this->png('old.png'));
        $oldPath = $technology->fresh()->icon_private_path;
        $stale = $technology->fresh();
        $stale->setAttribute('id', 999999);

        try {
            $service->replace($stale, $this->png('new.png'));
            $this->fail('A database failure must be controlled.');
        } catch (AssetOperationException) {
            $this->assertSame($oldPath, $technology->fresh()->icon_private_path);
            $this->assertSame([$oldPath], Storage::disk('local')->allFiles());
        }
    }

    private function png(string $name, string $padding = ''): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL9SAAAAABJRU5ErkJggg==').$padding,
        );
    }
}
