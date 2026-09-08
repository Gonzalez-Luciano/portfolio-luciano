<?php

namespace Tests\Feature;

use App\Enums\PublicationStatus;
use App\Models\CvDocument;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class CvDownloadTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_the_spanish_route_streams_the_published_visible_private_original(): void
    {
        $cv = $this->createPublishedCv(spanish: true, visible: true, privatePath: 'cv/es.pdf');
        Storage::disk('local')->put($cv->private_path, "%PDF-1.4\nspanish-original");

        $response = $this->get('/cv/luciano-gonzalez-es.pdf')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);

        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringContainsString('luciano-gonzalez-es.pdf', $disposition);
        $this->assertSame("%PDF-1.4\nspanish-original", $response->streamedContent());
    }

    public function test_the_english_route_streams_the_published_visible_private_original(): void
    {
        $cv = $this->createPublishedCv(spanish: false, visible: true, privatePath: 'cv/en.pdf');
        Storage::disk('local')->put($cv->private_path, "%PDF-1.4\nenglish-original");

        $response = $this->get('/cv/luciano-gonzalez-en.pdf')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);

        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringContainsString('luciano-gonzalez-en.pdf', $disposition);
        $this->assertSame("%PDF-1.4\nenglish-original", $response->streamedContent());
    }

    public function test_a_missing_cv_row_for_a_locale_returns_a_plain_web_not_found(): void
    {
        $response = $this->get('/cv/luciano-gonzalez-es.pdf');

        $response->assertNotFound();
        $this->assertStringContainsString('text/html', $response->headers->get('Content-Type'));
        $response->assertDontSee('private_path', false);
        $response->assertDontSee('storage/app', false);
    }

    public function test_an_absent_english_slot_also_returns_not_found(): void
    {
        $this->get('/cv/luciano-gonzalez-en.pdf')->assertNotFound();
    }

    public function test_a_hidden_published_cv_returns_not_found(): void
    {
        $cv = $this->createPublishedCv(spanish: true, visible: false, privatePath: 'cv/hidden.pdf');
        Storage::disk('local')->put($cv->private_path, '%PDF-1.4');

        $this->get('/cv/luciano-gonzalez-es.pdf')->assertNotFound();
    }

    public function test_a_draft_cv_returns_not_found(): void
    {
        $cv = CvDocument::factory()->spanish()->create([
            'private_path' => 'cv/draft.pdf', 'mime' => 'application/pdf', 'size' => 3, 'label' => 'Draft CV',
        ]);
        Storage::disk('local')->put($cv->private_path, '%PDF-1.4');

        $this->get('/cv/luciano-gonzalez-es.pdf')->assertNotFound();
    }

    public function test_a_published_visible_cv_with_a_missing_private_file_returns_not_found(): void
    {
        $this->createPublishedCv(spanish: true, visible: true, privatePath: 'cv/missing-file.pdf');
        // Deliberately never writing the file to the fake disk.

        $this->get('/cv/luciano-gonzalez-es.pdf')->assertNotFound();
    }

    public function test_no_storage_alias_ever_serves_the_private_cv(): void
    {
        $cv = $this->createPublishedCv(spanish: true, visible: true, privatePath: 'cv/es.pdf');
        Storage::disk('local')->put($cv->private_path, '%PDF-1.4');

        $response = $this->get('/storage/'.$cv->private_path);

        $response->assertStatus(403);
    }

    public function test_the_cv_routes_carry_no_dynamic_path_segment(): void
    {
        $cvRoutes = collect(app('router')->getRoutes())
            ->filter(fn ($route): bool => str_starts_with($route->uri(), 'cv/'));

        $this->assertCount(2, $cvRoutes);

        foreach ($cvRoutes as $route) {
            $this->assertStringNotContainsString('{', $route->uri());
        }

        $uris = $cvRoutes->map(fn ($route): string => $route->uri())->sort()->values()->all();
        $this->assertSame(['cv/luciano-gonzalez-en.pdf', 'cv/luciano-gonzalez-es.pdf'], $uris);
    }

    public function test_the_cv_download_limiter_throttles_after_thirty_requests_per_minute(): void
    {
        $cv = $this->createPublishedCv(spanish: true, visible: true, privatePath: 'cv/es.pdf');
        Storage::disk('local')->put($cv->private_path, '%PDF-1.4');

        for ($request = 0; $request < 30; $request++) {
            $this->get('/cv/luciano-gonzalez-es.pdf')->assertOk();
        }

        $this->get('/cv/luciano-gonzalez-es.pdf')->assertTooManyRequests();
    }

    /**
     * Creates a CV document as a draft first (the editorial guard forbids inserting
     * already-published rows) and then publishes it with a raw update, matching the
     * pattern used by the public API contract tests.
     */
    private function createPublishedCv(bool $spanish, bool $visible, string $privatePath): CvDocument
    {
        $factory = $spanish ? CvDocument::factory()->spanish() : CvDocument::factory();

        $cv = $factory->create([
            'private_path' => $privatePath, 'mime' => 'application/pdf', 'size' => 3, 'label' => 'CV',
        ]);

        DB::table('cv_documents')->where('id', $cv->getKey())->update([
            'status' => PublicationStatus::Published->value,
            'is_visible' => $visible,
            'published_at' => now(),
        ]);

        return $cv->fresh();
    }
}
