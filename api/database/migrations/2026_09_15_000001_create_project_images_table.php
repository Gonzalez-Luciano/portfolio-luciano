<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ordered project screenshots (spec 2026-09-14-phase-6-portfolio-redesign-design.md §4.2).
 *
 * The former single `projects.image_*` asset moves to position 0 of the new
 * gallery and its columns and checks are dropped. A gallery row always has a
 * private original; `public_path` is set only while the owning project is
 * published and visible (enforced by AssetLifecycleService and
 * ProjectGalleryService, since a CHECK cannot read the parent row).
 */
return new class extends Migration
{
    private const IMAGE_COLUMNS = ['image_private_path', 'image_public_path', 'image_mime', 'image_size', 'image_alt_es', 'image_alt_en'];

    public function up(): void
    {
        Schema::create('project_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->string('private_path', 512);
            $table->string('public_path', 512)->nullable();
            $table->string('mime');
            $table->unsignedBigInteger('size');
            $table->string('alt_es', 500)->nullable();
            $table->string('alt_en', 500)->nullable();
            $table->timestamps();
            $table->index(['project_id', 'position', 'id']);
        });

        DB::statement('ALTER TABLE project_images ADD CONSTRAINT project_images_position_check CHECK (position >= 0)');
        DB::statement('ALTER TABLE project_images ADD CONSTRAINT project_images_size_check CHECK (size >= 1)');

        DB::table('projects')->whereNotNull('image_private_path')->orderBy('id')->each(function (object $project): void {
            DB::table('project_images')->insert([
                'project_id' => $project->id,
                'position' => 0,
                'private_path' => $project->image_private_path,
                'public_path' => $project->image_public_path,
                'mime' => $project->image_mime,
                'size' => $project->image_size,
                'alt_es' => $project->image_alt_es,
                'alt_en' => $project->image_alt_en,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        DB::statement('ALTER TABLE projects DROP CHECK projects_image_private_group_check');
        DB::statement('ALTER TABLE projects DROP CHECK projects_image_public_state_check');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(self::IMAGE_COLUMNS);
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('image_private_path', 512)->nullable()->after('repository_url');
            $table->string('image_public_path', 512)->nullable()->after('image_private_path');
            $table->string('image_mime')->nullable()->after('image_public_path');
            $table->unsignedBigInteger('image_size')->nullable()->after('image_mime');
            $table->string('image_alt_es', 500)->nullable()->after('image_size');
            $table->string('image_alt_en', 500)->nullable()->after('image_alt_es');
        });

        DB::table('project_images')->where('position', 0)->orderBy('id')->each(function (object $image): void {
            DB::table('projects')->where('id', $image->project_id)->update([
                'image_private_path' => $image->private_path,
                'image_public_path' => $image->public_path,
                'image_mime' => $image->mime,
                'image_size' => $image->size,
                'image_alt_es' => $image->alt_es,
                'image_alt_en' => $image->alt_en,
            ]);
        });

        DB::statement('ALTER TABLE projects ADD CONSTRAINT projects_image_private_group_check CHECK ((image_private_path IS NULL AND image_mime IS NULL AND image_size IS NULL) OR (image_private_path IS NOT NULL AND image_mime IS NOT NULL AND image_size IS NOT NULL))');
        DB::statement("ALTER TABLE projects ADD CONSTRAINT projects_image_public_state_check CHECK (image_public_path IS NULL OR (image_private_path IS NOT NULL AND image_mime IS NOT NULL AND image_size IS NOT NULL AND status = 'published' AND is_visible = 1))");

        Schema::dropIfExists('project_images');
    }
};
