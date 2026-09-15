<?php

namespace Tests\Feature\Database;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class ProjectImageMigrationTest extends TestCase
{
    use DatabaseMigrations;

    private const MIGRATION = 'database/migrations/2026_09_15_000001_create_project_images_table.php';

    public function test_it_moves_an_existing_single_project_image_to_the_first_gallery_position(): void
    {
        $this->artisan('migrate:rollback', ['--path' => self::MIGRATION])->assertSuccessful();

        $projectId = DB::table('projects')->insertGetId([
            'key' => 'legacy-project', 'key_locked' => false, 'position' => 0,
            'image_private_path' => 'projects/legacy.png', 'image_public_path' => null,
            'image_mime' => 'image/png', 'image_size' => 68,
            'image_alt_es' => 'Captura heredada', 'image_alt_en' => 'Legacy screenshot',
            'status' => 'draft', 'is_visible' => false, 'published_at' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->artisan('migrate', ['--path' => self::MIGRATION])->assertSuccessful();

        $this->assertDatabaseHas('project_images', [
            'project_id' => $projectId, 'position' => 0,
            'private_path' => 'projects/legacy.png', 'public_path' => null,
            'mime' => 'image/png', 'size' => 68,
            'alt_es' => 'Captura heredada', 'alt_en' => 'Legacy screenshot',
        ]);
        foreach (['image_private_path', 'image_public_path', 'image_mime', 'image_size', 'image_alt_es', 'image_alt_en'] as $column) {
            $this->assertFalse(Schema::hasColumn('projects', $column), "projects.{$column} must be dropped.");
        }
    }

    public function test_gallery_rows_cascade_with_their_project_and_reject_invalid_sizes(): void
    {
        $projectId = DB::table('projects')->insertGetId([
            'key' => 'gallery-owner', 'key_locked' => false, 'position' => 0,
            'status' => 'draft', 'is_visible' => false, 'published_at' => null,
        ]);
        $row = [
            'project_id' => $projectId, 'position' => 0, 'private_path' => 'projects/a.png',
            'public_path' => null, 'mime' => 'image/png', 'size' => 1, 'alt_es' => null, 'alt_en' => null,
        ];
        DB::table('project_images')->insert($row);

        try {
            DB::table('project_images')->insert([...$row, 'size' => 0]);
            $this->fail('A zero-byte gallery image must be rejected.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }

        DB::table('projects')->where('id', $projectId)->delete();
        $this->assertDatabaseCount('project_images', 0);
    }
}
