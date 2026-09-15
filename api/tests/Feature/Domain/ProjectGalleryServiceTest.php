<?php

namespace Tests\Feature\Domain;

use App\Domain\Assets\AssetOperationException;
use App\Domain\Content\Actions\PublishContent;
use App\Domain\Content\Actions\ShowContent;
use App\Domain\Content\Actions\SyncProjectImages;
use App\Domain\Publishing\PublicationValidationException;
use App\Models\Project;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

final class ProjectGalleryServiceTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_it_adds_ordered_screenshots_to_a_draft_project_without_public_copies(): void
    {
        $project = Project::factory()->create();

        $updated = app(SyncProjectImages::class)($project, [
            $this->item(null, $this->png('first.png'), 'Primera', 'First'),
            $this->item(null, $this->png('second.png'), 'Segunda', 'Second'),
        ]);

        $images = $updated->images;
        $this->assertSame(['First', 'Second'], $images->pluck('alt_en')->all());
        $this->assertSame([0, 1], $images->pluck('position')->all());
        $this->assertSame([null, null], $images->pluck('public_path')->all());
        foreach ($images as $image) {
            $this->assertMatchesRegularExpression('#^projects/[0-9a-f-]+\.png$#', $image->private_path);
            Storage::disk('local')->assertExists($image->private_path);
        }
    }

    public function test_it_rejects_a_thirteenth_screenshot_before_storing_anything(): void
    {
        $project = Project::factory()->create();
        $items = array_map(fn (int $index): array => $this->item(null, $this->png("shot-{$index}.png")), range(0, 12));

        try {
            app(SyncProjectImages::class)($project, $items);
            $this->fail('A thirteenth screenshot must be rejected.');
        } catch (PublicationValidationException $exception) {
            $this->assertSame('too_many_images', $exception->issues()[0]->code);
        }

        $this->assertSame(0, $project->images()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_a_published_project_rejects_a_screenshot_without_alt_text_and_discards_the_upload(): void
    {
        $project = app(PublishContent::class)(Project::factory()->create($this->completeAttributes()));

        try {
            app(SyncProjectImages::class)($project, [$this->item(null, $this->png('shot.png'), 'Captura', null)]);
            $this->fail('Publication rules must apply to the proposed gallery.');
        } catch (PublicationValidationException $exception) {
            $this->assertSame('images.0.alt_en', $exception->issues()[0]->path);
        }

        $this->assertSame(0, $project->images()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_it_reorders_updates_alt_text_and_removes_screenshots(): void
    {
        $project = Project::factory()->create();
        $initial = app(SyncProjectImages::class)($project, [
            $this->item(null, $this->png('a.png'), 'A', 'A'),
            $this->item(null, $this->png('b.png'), 'B', 'B'),
            $this->item(null, $this->png('c.png'), 'C', 'C'),
        ])->images;
        [$a, $b, $c] = [$initial[0], $initial[1], $initial[2]];

        $updated = app(SyncProjectImages::class)($project->fresh(), [
            $this->item($c->id, null, 'C nueva', 'C new'),
            $this->item($a->id, null, 'A', 'A'),
        ]);

        $this->assertSame([$c->id, $a->id], $updated->images->pluck('id')->all());
        $this->assertSame(['C new', 'A'], $updated->images->pluck('alt_en')->all());
        $this->assertSame($c->private_path, $updated->images[0]->private_path);
        Storage::disk('local')->assertMissing($b->private_path);
    }

    public function test_replacing_a_file_keeps_the_image_and_retires_the_old_original(): void
    {
        $project = Project::factory()->create();
        $image = app(SyncProjectImages::class)($project, [$this->item(null, $this->png('old.png'))])->images[0];

        $updated = app(SyncProjectImages::class)($project->fresh(), [$this->item($image->id, $this->png('new.png'))]);

        $this->assertSame($image->id, $updated->images[0]->id);
        $this->assertNotSame($image->private_path, $updated->images[0]->private_path);
        Storage::disk('local')->assertMissing($image->private_path);
        Storage::disk('local')->assertExists($updated->images[0]->private_path);
    }

    public function test_a_visible_project_gets_public_copies_for_new_screenshots_and_loses_retired_ones(): void
    {
        $project = $this->visibleProjectWithOneImage();
        $old = $project->images[0];

        $updated = app(SyncProjectImages::class)($project, [$this->item(null, $this->png('new.png'), 'Nueva', 'New')]);

        $new = $updated->images[0];
        $this->assertNotNull($new->public_path);
        Storage::disk('public')->assertExists($new->public_path);
        Storage::disk('public')->assertMissing($old->public_path);
        Storage::disk('local')->assertMissing($old->private_path);
    }

    public function test_it_rejects_an_image_id_from_another_project(): void
    {
        $other = Project::factory()->create();
        $foreign = app(SyncProjectImages::class)($other, [$this->item(null, $this->png('foreign.png'))])->images[0];
        $project = Project::factory()->create();

        try {
            app(SyncProjectImages::class)($project, [$this->item($foreign->id, null)]);
            $this->fail('A foreign image id must be rejected.');
        } catch (PublicationValidationException $exception) {
            $this->assertSame('invalid_relation', $exception->issues()[0]->code);
            $this->assertSame('images.0.id', $exception->issues()[0]->path);
        }

        $this->assertSame(1, $other->images()->count());
    }

    public function test_a_failed_public_copy_restores_the_previous_gallery_and_discards_the_upload(): void
    {
        $project = $this->visibleProjectWithOneImage();
        $old = $project->images[0];
        $local = Storage::disk('local');
        $failingPublic = Mockery::mock();
        $failingPublic->shouldReceive('put')->andReturnFalse();
        $failingPublic->shouldReceive('exists')->andReturnFalse();
        $failingPublic->shouldReceive('delete')->andReturnTrue();
        Storage::shouldReceive('disk')->with('local')->andReturn($local);
        Storage::shouldReceive('disk')->with('public')->andReturn($failingPublic);

        try {
            app(SyncProjectImages::class)($project, [
                $this->item($old->id, null, $old->alt_es, $old->alt_en),
                $this->item(null, $this->png('new.png'), 'Nueva', 'New'),
            ]);
            $this->fail('A failed public copy must be controlled.');
        } catch (AssetOperationException) {
            $images = $project->images()->get();
            $this->assertSame([$old->id], $images->pluck('id')->all());
            $this->assertSame($old->public_path, $images[0]->public_path);
            $this->assertSame([$old->private_path], $local->allFiles());
        }
    }

    private function visibleProjectWithOneImage(): Project
    {
        $project = Project::factory()->create($this->completeAttributes());
        app(SyncProjectImages::class)($project, [$this->item(null, $this->png('cover.png'), 'Portada', 'Cover')]);

        return app(ShowContent::class)(app(PublishContent::class)($project->fresh()))->load('images');
    }

    /** @return array{id: ?int, upload: ?UploadedFile, alt_es: ?string, alt_en: ?string} */
    private function item(?int $id, ?UploadedFile $upload, ?string $altEs = 'Captura sintética', ?string $altEn = 'Synthetic screenshot'): array
    {
        return ['id' => $id, 'upload' => $upload, 'alt_es' => $altEs, 'alt_en' => $altEn];
    }

    /** @return array<string, string> */
    private function completeAttributes(): array
    {
        return [
            'title_es' => 'Proyecto sintético', 'title_en' => 'Synthetic project',
            'role_es' => 'Rol sintético', 'role_en' => 'Synthetic role',
            'delivery_status' => 'in_development',
            'summary_es' => 'Resumen sintético.', 'summary_en' => 'Synthetic summary.',
            'problem_es' => 'Problema sintético.', 'problem_en' => 'Synthetic problem.',
            'solution_es' => 'Solución sintética.', 'solution_en' => 'Synthetic solution.',
            'result_es' => 'Resultado sintético.', 'result_en' => 'Synthetic result.',
        ];
    }

    private function png(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL9SAAAAABJRU5ErkJggg=='),
        );
    }
}
