<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Technology;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Spec section 19 requires the entire private-media confidentiality
 * boundary (unpublished CV PDFs, unpublished photo/image/icon originals)
 * to rest on an explicit, chosen, pinned setting rather than Laravel's
 * stock unsigned-route-rejection default. `CvDownloadTest` already covers
 * the CV private path; this covers the private disk's configuration and
 * the remaining three owned-asset owners.
 */
final class PrivateDiskAccessTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_the_private_local_disk_pins_an_explicit_unserved_configuration(): void
    {
        $this->assertFalse(
            config('filesystems.disks.local.serve'),
            'The private disk must explicitly opt out of the framework default unsigned serving.',
        );
    }

    public function test_no_storage_alias_ever_serves_a_private_profile_photo(): void
    {
        $path = 'profiles/synthetic.webp';
        Storage::disk('local')->put($path, 'synthetic-photo-original');

        DB::table('profiles')->where('singleton_key', 'default')->update([
            'photo_private_path' => $path, 'photo_mime' => 'image/webp', 'photo_size' => strlen('synthetic-photo-original'),
        ]);

        $response = $this->get('/storage/'.$path);

        $response->assertNotFound();
        $response->assertDontSee($path, false);
    }

    public function test_no_storage_alias_ever_serves_a_private_project_image(): void
    {
        $path = 'projects/synthetic.webp';
        Storage::disk('local')->put($path, 'synthetic-image-original');

        $project = Project::factory()->create();
        DB::table('project_images')->insert([
            'project_id' => $project->id, 'position' => 0, 'private_path' => $path, 'public_path' => null,
            'mime' => 'image/webp', 'size' => strlen('synthetic-image-original'), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->get('/storage/'.$path);

        $response->assertNotFound();
        $response->assertDontSee($path, false);
    }

    public function test_no_storage_alias_ever_serves_a_private_technology_icon(): void
    {
        $path = 'technologies/synthetic.png';
        Storage::disk('local')->put($path, 'synthetic-icon-original');

        Technology::factory()->create([
            'icon_private_path' => $path, 'icon_mime' => 'image/png', 'icon_size' => strlen('synthetic-icon-original'),
        ]);

        $response = $this->get('/storage/'.$path);

        $response->assertNotFound();
        $response->assertDontSee($path, false);
    }
}
