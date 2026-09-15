<?php

namespace Tests\Feature\Assets;

use App\Domain\Assets\AssetOperationException;
use App\Domain\Content\Actions\DeleteContent;
use App\Domain\Content\Actions\HideContent;
use App\Domain\Content\Actions\PublishContent;
use App\Domain\Content\Actions\ShowContent;
use App\Domain\Publishing\EditorialMutationContext;
use App\Domain\Publishing\PublicationValidationException;
use App\Models\Project;
use App\Models\ProjectImage;
use Illuminate\Cache\Repository;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\Support\RecordingLockStore;
use Tests\TestCase;

final class ProjectGalleryLifecycleTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_show_publishes_a_verified_public_copy_of_every_gallery_image(): void
    {
        $project = $this->publishedProjectWithImages(2);

        $shown = app(ShowContent::class)($project);

        $this->assertTrue($shown->is_visible);
        $images = $shown->images()->get();
        $this->assertCount(2, $images);
        foreach ($images as $image) {
            $this->assertMatchesRegularExpression('#^projects/[0-9a-f-]+\.png$#', $image->public_path);
            Storage::disk('public')->assertExists($image->public_path);
        }
    }

    public function test_hide_withdraws_every_public_gallery_copy(): void
    {
        $shown = app(ShowContent::class)($this->publishedProjectWithImages(2));
        $publicPaths = $shown->images()->pluck('public_path')->all();

        $hidden = app(HideContent::class)($shown);

        $this->assertFalse($hidden->is_visible);
        $this->assertSame([null, null], $hidden->images()->pluck('public_path')->all());
        foreach ($publicPaths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_delete_removes_gallery_rows_public_copies_and_private_originals(): void
    {
        $shown = app(ShowContent::class)($this->publishedProjectWithImages(2));
        $images = $shown->images()->get();

        app(DeleteContent::class)($shown);

        $this->assertDatabaseCount('project_images', 0);
        foreach ($images as $image) {
            Storage::disk('local')->assertMissing($image->private_path);
            Storage::disk('public')->assertMissing($image->public_path);
        }
    }

    public function test_publication_requires_alt_text_for_every_gallery_image(): void
    {
        $project = $this->draftProjectWithImages(1);
        app(EditorialMutationContext::class)->run(fn () => $project->images()->firstOrFail()->forceFill(['alt_en' => null])->save());

        try {
            app(PublishContent::class)($project->fresh());
            $this->fail('A gallery image without alt text must block publication.');
        } catch (PublicationValidationException $exception) {
            $this->assertSame('images.0.alt_en', $exception->issues()[0]->path);
        }
    }

    public function test_a_failed_public_copy_leaves_the_project_hidden_without_public_references(): void
    {
        $project = $this->publishedProjectWithImages(1);
        $local = Storage::disk('local');
        $failingPublic = Mockery::mock();
        $failingPublic->shouldReceive('put')->once()->andReturnFalse();
        $failingPublic->shouldReceive('exists')->andReturnFalse();
        $failingPublic->shouldReceive('delete')->andReturnTrue();
        Storage::shouldReceive('disk')->with('local')->andReturn($local);
        Storage::shouldReceive('disk')->with('public')->andReturn($failingPublic);

        try {
            app(ShowContent::class)($project);
            $this->fail('A failed public copy must be controlled.');
        } catch (AssetOperationException) {
            $project->refresh();
            $this->assertFalse($project->is_visible);
            $this->assertSame([null], $project->images()->pluck('public_path')->all());
        }
    }

    public function test_a_failed_copy_of_a_later_image_deletes_the_earlier_copies_and_leaves_every_image_unpublished(): void
    {
        $project = $this->publishedProjectWithImages(2);
        $local = Storage::disk('local');
        $public = Storage::disk('public');
        $putCalls = 0;
        $failingPublic = Mockery::mock();
        $failingPublic->shouldReceive('put')->twice()->andReturnUsing(function (string $path, string $contents) use (&$putCalls, $public): bool {
            $putCalls++;

            return $putCalls === 1 ? $public->put($path, $contents) : false;
        });
        $failingPublic->shouldReceive('exists')->andReturnUsing(fn (string $path): bool => $public->exists($path));
        $failingPublic->shouldReceive('delete')->andReturnUsing(fn (string $path): bool => $public->delete($path));
        Storage::shouldReceive('disk')->with('local')->andReturn($local);
        Storage::shouldReceive('disk')->with('public')->andReturn($failingPublic);

        try {
            app(ShowContent::class)($project);
            $this->fail('A failed copy of a later gallery image must be controlled.');
        } catch (AssetOperationException) {
            $project->refresh();
            $this->assertFalse($project->is_visible);
            $this->assertSame([null, null], $project->images()->pluck('public_path')->all());
            $public->assertDirectoryEmpty('/');
        }
    }

    public function test_a_failed_gallery_withdrawal_restores_the_already_withdrawn_copies_and_keeps_the_project_visible(): void
    {
        $shown = app(ShowContent::class)($this->publishedProjectWithImages(2));
        $publicPaths = $shown->images()->pluck('public_path')->all();
        $local = Storage::disk('local');
        $public = Storage::disk('public');
        $deleteCalls = 0;
        $failingPublic = Mockery::mock();
        $failingPublic->shouldReceive('delete')->twice()->andReturnUsing(function (string $path) use (&$deleteCalls, $public): bool {
            $deleteCalls++;

            return $deleteCalls === 1 ? $public->delete($path) : false;
        });
        $failingPublic->shouldReceive('exists')->andReturnUsing(fn (string $path): bool => $public->exists($path));
        $failingPublic->shouldReceive('put')->andReturnUsing(fn (string $path, string $contents): bool => $public->put($path, $contents));
        Storage::shouldReceive('disk')->with('local')->andReturn($local);
        Storage::shouldReceive('disk')->with('public')->andReturn($failingPublic);

        try {
            app(HideContent::class)($shown);
            $this->fail('A failed gallery withdrawal must be controlled.');
        } catch (AssetOperationException) {
            $shown->refresh();
            $this->assertTrue($shown->is_visible);
            $this->assertSame($publicPaths, $shown->images()->pluck('public_path')->all());
            foreach ($publicPaths as $path) {
                $public->assertExists($path);
            }
        }
    }

    public function test_a_pre_commit_cache_invalidation_failure_restores_every_withdrawn_gallery_copy(): void
    {
        $shown = app(ShowContent::class)($this->publishedProjectWithImages(2));
        $publicPaths = $shown->images()->pluck('public_path')->all();
        $store = new RecordingLockStore($this->app['files'], storage_path('framework/cache/data'));
        $this->app['cache']->extend('gallery-pre-commit-invalidate-failure', static fn (): Repository => new Repository($store));
        config([
            'cache.default' => 'gallery-pre-commit-invalidate-failure',
            'cache.stores.gallery-pre-commit-invalidate-failure' => ['driver' => 'gallery-pre-commit-invalidate-failure'],
        ]);
        Cache::forgetDriver('gallery-pre-commit-invalidate-failure');
        $store->failFromForget = 1;

        try {
            app(HideContent::class)($shown);
            $this->fail('A pre-commit cache invalidation failure must be controlled.');
        } catch (AssetOperationException) {
            $shown->refresh();
            $this->assertTrue($shown->is_visible);
            $this->assertSame($publicPaths, $shown->images()->pluck('public_path')->all());
            foreach ($publicPaths as $path) {
                Storage::disk('public')->assertExists($path);
            }
        }
    }

    public function test_a_pre_commit_transaction_failure_restores_every_withdrawn_gallery_copy(): void
    {
        $shown = app(ShowContent::class)($this->publishedProjectWithImages(2));
        $publicPaths = $shown->images()->pluck('public_path')->all();
        Project::updating(static function (): never {
            throw new \RuntimeException('Synthetic pre-commit transaction failure.');
        });

        try {
            app(HideContent::class)($shown);
            $this->fail('A pre-commit transaction failure must be controlled.');
        } catch (AssetOperationException) {
            $shown->refresh();
            $this->assertTrue($shown->is_visible);
            $this->assertSame($publicPaths, $shown->images()->pluck('public_path')->all());
            foreach ($publicPaths as $path) {
                Storage::disk('public')->assertExists($path);
            }
        }
    }

    public function test_delete_reports_a_failed_gallery_private_cleanup_after_the_committed_deletion(): void
    {
        $shown = app(ShowContent::class)($this->publishedProjectWithImages(1));
        $privatePath = $shown->images()->firstOrFail()->private_path;
        $local = Mockery::mock();
        $public = Storage::disk('public');
        $local->shouldReceive('delete')->with($privatePath)->times(3)->andReturnFalse();
        $local->shouldReceive('exists')->with($privatePath)->times(3)->andReturnTrue();
        Storage::shouldReceive('disk')->with('local')->andReturn($local);
        Storage::shouldReceive('disk')->with('public')->andReturn($public);

        try {
            app(DeleteContent::class)($shown);
            $this->fail('A failing gallery private cleanup must not claim success.');
        } catch (AssetOperationException) {
            $this->assertDatabaseMissing('projects', ['id' => $shown->id]);
            $this->assertDatabaseCount('project_images', 0);
        }
    }

    private function publishedProjectWithImages(int $count): Project
    {
        return app(PublishContent::class)($this->draftProjectWithImages($count));
    }

    private function draftProjectWithImages(int $count): Project
    {
        $project = Project::factory()->create([
            'title_es' => 'Proyecto sintético', 'title_en' => 'Synthetic project',
            'role_es' => 'Rol sintético', 'role_en' => 'Synthetic role',
            'delivery_status' => 'in_development',
            'summary_es' => 'Resumen sintético.', 'summary_en' => 'Synthetic summary.',
            'problem_es' => 'Problema sintético.', 'problem_en' => 'Synthetic problem.',
            'solution_es' => 'Solución sintética.', 'solution_en' => 'Synthetic solution.',
            'result_es' => 'Resultado sintético.', 'result_en' => 'Synthetic result.',
        ]);

        app(EditorialMutationContext::class)->run(function () use ($project, $count): void {
            for ($position = 0; $position < $count; $position++) {
                $path = "projects/synthetic-{$position}.png";
                Storage::disk('local')->put($path, 'synthetic-png');
                ProjectImage::factory()->for($project)->create(['position' => $position, 'private_path' => $path, 'size' => 13]);
            }
        });

        return $project->fresh();
    }
}
