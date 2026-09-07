<?php

namespace Tests\Feature\Assets;

use App\Domain\Assets\AssetLifecycleService;
use App\Domain\Content\Actions\DeleteContent;
use App\Domain\Content\Actions\HideContent;
use App\Domain\Content\Actions\PublishContent;
use App\Domain\Content\Actions\ReturnContentToDraft;
use App\Domain\Content\Actions\ShowContent;
use App\Models\Project;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
