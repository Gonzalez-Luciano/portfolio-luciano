<?php

namespace Tests\Feature\Assets;

use App\Domain\Assets\AssetLifecycleService;
use App\Domain\Assets\AssetOperationException;
use App\Domain\Content\Actions\DeleteContent;
use App\Domain\Content\Actions\HideContent;
use App\Domain\Content\Actions\PublishContent;
use App\Domain\Content\Actions\ReturnContentToDraft;
use App\Domain\Content\Actions\ShowContent;
use App\Models\Project;
use Illuminate\Cache\Repository;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\Support\RecordingLockStore;
use Tests\TestCase;

final class AssetTransitionActionTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_show_copies_a_published_hidden_owned_asset_only_after_the_owner_is_committed_visible(): void
    {
        $project = $this->projectForPublication();
        app(AssetLifecycleService::class)->replace($project, $this->png('project.png'));
        $project = app(PublishContent::class)($project->fresh());

        $shown = app(ShowContent::class)($project);

        $this->assertTrue($shown->is_visible);
        $this->assertNotNull($shown->image_public_path);
        Storage::disk('public')->assertExists($shown->image_public_path);
    }

    public function test_hide_and_return_to_draft_withdraw_the_public_copy_and_leave_no_public_reference(): void
    {
        $project = $this->visibleProjectWithImage();

        $hidden = app(HideContent::class)($project);
        Storage::disk('public')->assertMissing($hidden->image_public_path ?? 'missing');
        $this->assertFalse($hidden->is_visible);
        $this->assertNull($hidden->image_public_path);

        $draft = app(ReturnContentToDraft::class)($hidden);
        $this->assertSame('draft', $draft->status->value);
        $this->assertFalse($draft->is_visible);
        $this->assertNull($draft->published_at);
    }

    public function test_delete_withdraws_public_media_before_removing_the_owner_and_private_original(): void
    {
        $project = $this->visibleProjectWithImage();
        $privatePath = $project->image_private_path;
        $publicPath = $project->image_public_path;

        app(DeleteContent::class)($project);

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        Storage::disk('public')->assertMissing($publicPath);
        Storage::disk('local')->assertMissing($privatePath);
    }

    public function test_failed_public_replacement_restores_the_prior_reference_and_retains_the_old_public_copy(): void
    {
        $project = $this->visibleProjectWithImage();
        $oldPrivate = $project->image_private_path;
        $oldPublic = $project->image_public_path;
        $local = Storage::disk('local');
        $public = Storage::disk('public');
        $failingPublic = Mockery::mock();
        $failingPublic->shouldReceive('put')->once()->andReturnFalse();
        $failingPublic->shouldReceive('delete')->once()->andReturnTrue();
        $failingPublic->shouldReceive('exists')->andReturnFalse();
        Storage::shouldReceive('disk')->with('local')->andReturn($local);
        Storage::shouldReceive('disk')->with('public')->andReturn($failingPublic);

        try {
            app(AssetLifecycleService::class)->replace($project, $this->png('replacement.png'));
            $this->fail('A failed public replacement must be controlled.');
        } catch (AssetOperationException) {
            $project->refresh();
            $this->assertSame($oldPrivate, $project->image_private_path);
            $this->assertSame($oldPublic, $project->image_public_path);
            $this->assertTrue($public->exists($oldPublic));
        }
    }

    public function test_failed_public_withdrawal_preserves_the_visible_owner_and_reference(): void
    {
        $project = $this->visibleProjectWithImage();
        $public = Mockery::mock();
        $public->shouldReceive('delete')->once()->andReturnFalse();
        $public->shouldReceive('exists')->once()->andReturnTrue();
        Storage::shouldReceive('disk')->with('public')->andReturn($public);

        try {
            app(HideContent::class)($project);
            $this->fail('A failed withdrawal must be controlled.');
        } catch (AssetOperationException) {
            $project->refresh();
            $this->assertTrue($project->is_visible);
            $this->assertNotNull($project->image_public_path);
        }
    }

    public function test_hide_withdraws_then_forgets_mutates_and_forgets_again_while_mutation_locks_remain_held(): void
    {
        $project = $this->visibleProjectWithImage();
        $store = new RecordingLockStore($this->app['files'], storage_path('framework/cache/data'));
        $this->app['cache']->extend('asset-recording', static fn (): Repository => new Repository($store));
        config(['cache.default' => 'asset-recording', 'cache.stores.asset-recording' => ['driver' => 'asset-recording']]);
        Cache::forgetDriver('asset-recording');
        $public = Storage::disk('public');
        $recordingPublic = Mockery::mock();
        $recordingPublic->shouldReceive('delete')->once()->andReturnUsing(function (string $path) use ($store, $public): bool {
            $store->events[] = 'withdraw';

            return $public->delete($path);
        });
        $recordingPublic->shouldReceive('exists')->andReturnUsing(fn (string $path): bool => $public->exists($path));
        Storage::shouldReceive('disk')->with('public')->andReturn($recordingPublic);
        DB::listen(function ($query) use ($store): void {
            if (str_contains($query->sql, 'update `projects`')) {
                $store->events[] = 'db_commit';
            }
        });

        app(HideContent::class)($project);

        $withdraw = array_search('withdraw', $store->events, true);
        $mutation = array_search('db_commit', $store->events, true);
        $forgets = array_keys(array_filter($store->events, static fn (string $event): bool => str_starts_with($event, 'forget:')));
        $releases = array_keys(array_filter($store->events, static fn (string $event): bool => str_starts_with($event, 'release:')));
        $acquires = array_keys(array_filter($store->events, static fn (string $event): bool => str_starts_with($event, 'acquire:')));

        $this->assertNotFalse($withdraw);
        $this->assertNotFalse($mutation);
        $this->assertNotEmpty($acquires);
        $this->assertGreaterThan($acquires[0], $withdraw);
        $this->assertGreaterThan($withdraw, $forgets[0]);
        $this->assertGreaterThan($forgets[0], $mutation);
        $this->assertGreaterThan($mutation, end($forgets));
        $this->assertGreaterThan(end($forgets), $releases[0]);
    }

    private function visibleProjectWithImage(): Project
    {
        $project = $this->projectForPublication();
        app(AssetLifecycleService::class)->replace($project, $this->png('project.png'));

        return app(ShowContent::class)(app(PublishContent::class)($project->fresh()));
    }

    private function png(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL9SAAAAABJRU5ErkJggg=='),
        );
    }

    private function projectForPublication(): Project
    {
        return Project::factory()->create([
            'title_es' => 'Proyecto sintético',
            'title_en' => 'Synthetic project',
            'summary_es' => 'Resumen sintético.',
            'summary_en' => 'Synthetic summary.',
            'problem_es' => 'Problema sintético.',
            'problem_en' => 'Synthetic problem.',
            'solution_es' => 'Solución sintética.',
            'solution_en' => 'Synthetic solution.',
            'image_alt_es' => 'Imagen sintética',
            'image_alt_en' => 'Synthetic image',
        ]);
    }
}
