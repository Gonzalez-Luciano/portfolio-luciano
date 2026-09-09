<?php

namespace Tests\Feature\Assets;

use App\Domain\Assets\AssetLifecycleService;
use App\Domain\Assets\AssetOperationException;
use App\Domain\Content\Actions\DeleteContent;
use App\Domain\Content\Actions\HideContent;
use App\Domain\Content\Actions\PublishContent;
use App\Domain\Content\Actions\RemoveOwnedAsset;
use App\Domain\Content\Actions\ReturnContentToDraft;
use App\Domain\Content\Actions\ShowContent;
use App\Enums\PublicationStatus;
use App\Models\CvDocument;
use App\Models\Profile;
use App\Models\Project;
use App\Models\SiteConfiguration;
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

    public function test_failed_public_copy_verification_restores_the_prior_reference_and_retains_the_old_public_copy(): void
    {
        $project = $this->visibleProjectWithImage();
        $oldPrivate = $project->image_private_path;
        $oldPublic = $project->image_public_path;
        $local = Storage::disk('local');
        $public = Storage::disk('public');
        $unverifiablePublic = Mockery::mock();
        $unverifiablePublic->shouldReceive('put')->once()->andReturnTrue();
        $unverifiablePublic->shouldReceive('delete')->once()->andReturnTrue();
        $unverifiablePublic->shouldReceive('exists')->twice()->andReturnFalse();
        Storage::shouldReceive('disk')->with('local')->andReturn($local);
        Storage::shouldReceive('disk')->with('public')->andReturn($unverifiablePublic);

        try {
            app(AssetLifecycleService::class)->replace($project, $this->png('unverifiable.png'));
            $this->fail('An unverifiable public copy must be compensated.');
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
        Project::updated(function () use ($store): void {
            DB::afterCommit(function () use ($store): void {
                $store->events[] = 'db_commit';
            });
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

    public function test_cv_can_be_shown_and_hidden_without_any_public_copy_or_reference(): void
    {
        $cv = CvDocument::factory()->create(['label' => 'CV técnico']);
        app(AssetLifecycleService::class)->replace($cv, UploadedFile::fake()->createWithContent('cv.pdf', "%PDF-1.4\nsynthetic"));
        $cv = app(PublishContent::class)($cv->fresh());

        $shown = app(ShowContent::class)($cv);
        $hidden = app(HideContent::class)($shown);

        $this->assertSame(PublicationStatus::Published, $shown->status);
        $this->assertTrue($shown->is_visible);
        $this->assertFalse($hidden->is_visible);
        $this->assertNotNull($hidden->private_path);
        Storage::disk('public')->assertDirectoryEmpty('/');
    }

    /**
     * `changeState()` is used by Show/Hide/Return-to-draft. Simulating the
     * row disappearing between the admin's initial fetch and the action
     * actually running (e.g. a concurrent hard delete) makes its internal
     * `lockForUpdate()->findOrFail()` raise `ModelNotFoundException`; this
     * must reach the caller as a sanitized `AssetOperationException`, not
     * a raw framework exception.
     */
    public function test_a_change_state_failure_is_sanitized_instead_of_leaking_a_raw_exception(): void
    {
        $cv = CvDocument::factory()->create(['label' => 'CV técnico']);
        app(AssetLifecycleService::class)->replace($cv, UploadedFile::fake()->createWithContent('cv.pdf', "%PDF-1.4\nsynthetic"));
        $cv = app(PublishContent::class)($cv->fresh());

        DB::table('cv_documents')->where('id', $cv->getKey())->delete();

        try {
            app(ShowContent::class)($cv);
            $this->fail('A change-state failure must not succeed silently.');
        } catch (AssetOperationException $exception) {
            $this->assertSame('The asset operation could not be completed.', $exception->getMessage());
        }
    }

    public function test_delete_rejects_both_singleton_models(): void
    {
        foreach ([Profile::query()->sole(), SiteConfiguration::query()->sole()] as $singleton) {
            try {
                app(DeleteContent::class)($singleton);
                $this->fail('Singleton content must not be deleted.');
            } catch (AssetOperationException) {
                $this->assertTrue($singleton->exists);
            }
        }
    }

    public function test_visible_remove_reports_private_cleanup_failure_after_the_committed_reference_clear(): void
    {
        $project = $this->visibleProjectWithImage();
        $oldPath = $project->image_private_path;
        $local = Mockery::mock();
        $public = Storage::disk('public');
        $local->shouldReceive('delete')->with($oldPath)->times(3)->andReturnFalse();
        $local->shouldReceive('exists')->with($oldPath)->times(3)->andReturnTrue();
        Storage::shouldReceive('disk')->with('local')->andReturn($local);
        Storage::shouldReceive('disk')->with('public')->andReturn($public);

        try {
            app(RemoveOwnedAsset::class)($project);
            $this->fail('Committed visible removal must report failed cleanup.');
        } catch (AssetOperationException) {
            $project->refresh();
            $this->assertNull($project->image_private_path);
            $this->assertNull($project->image_public_path);
        }
    }

    public function test_post_commit_cache_failure_does_not_restore_the_withdrawn_public_copy(): void
    {
        $project = $this->visibleProjectWithImage();
        $publicPath = $project->image_public_path;
        $store = new RecordingLockStore($this->app['files'], storage_path('framework/cache/data'));
        $this->app['cache']->extend('failing-after-commit', static fn (): Repository => new Repository($store));
        config(['cache.default' => 'failing-after-commit', 'cache.stores.failing-after-commit' => ['driver' => 'failing-after-commit']]);
        Cache::forgetDriver('failing-after-commit');
        $store->failFromForget = 3;

        try {
            app(HideContent::class)($project);
            $this->fail('Post-commit cache invalidation failure must be controlled.');
        } catch (AssetOperationException) {
            $project->refresh();
            $this->assertFalse($project->is_visible);
            $this->assertNull($project->image_public_path);
            Storage::disk('public')->assertMissing($publicPath);
        }
    }

    public function test_pre_commit_reduction_failure_restores_the_withdrawn_public_copy(): void
    {
        $project = $this->visibleProjectWithImage();
        $publicPath = $project->image_public_path;
        $stale = $project->fresh();
        $stale->setAttribute('id', 999999);

        try {
            app(HideContent::class)($stale);
            $this->fail('A pre-commit mutation failure must be controlled.');
        } catch (AssetOperationException) {
            $project->refresh();
            $this->assertTrue($project->is_visible);
            $this->assertSame($publicPath, $project->image_public_path);
            Storage::disk('public')->assertExists($publicPath);
        }
    }

    public function test_visible_delete_reports_private_cleanup_failure_after_the_committed_deletion(): void
    {
        $project = $this->visibleProjectWithImage();
        $oldPath = $project->image_private_path;
        $local = Mockery::mock();
        $public = Storage::disk('public');
        $local->shouldReceive('delete')->with($oldPath)->times(3)->andReturnFalse();
        $local->shouldReceive('exists')->with($oldPath)->times(3)->andReturnTrue();
        Storage::shouldReceive('disk')->with('local')->andReturn($local);
        Storage::shouldReceive('disk')->with('public')->andReturn($public);

        try {
            app(DeleteContent::class)($project);
            $this->fail('Committed visible deletion must report failed cleanup.');
        } catch (AssetOperationException) {
            $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        }
    }

    public function test_earlier_throwing_after_commit_listener_does_not_restore_a_committed_reduction(): void
    {
        $project = $this->visibleProjectWithImage();
        $publicPath = $project->image_public_path;
        Project::updated(static function (): void {
            DB::afterCommit(static function (): never {
                throw new \RuntimeException('Synthetic after-commit listener failure.');
            });
        });

        try {
            app(HideContent::class)($project);
            $this->fail('A post-commit listener failure must be controlled.');
        } catch (AssetOperationException) {
            $project->refresh();
            $this->assertFalse($project->is_visible);
            $this->assertNull($project->image_public_path);
            Storage::disk('public')->assertMissing($publicPath);
        }
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
