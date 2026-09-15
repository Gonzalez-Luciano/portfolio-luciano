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
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Storage;
use Mockery;
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
