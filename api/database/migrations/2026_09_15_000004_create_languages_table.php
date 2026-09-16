<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Structured language list for the About section (spec 2026-09-14-phase-6-portfolio-redesign-design.md §4.6).
 * `level` is nullable so drafts can be saved incomplete; publication requires it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->boolean('key_locked')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->string('name_es')->nullable();
            $table->string('name_en')->nullable();
            $table->enum('level', ['native', 'a1', 'a2', 'b1', 'b2', 'c1', 'c2'])->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->boolean('is_visible')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'is_visible', 'position', 'key']);
        });

        foreach ([
            'languages_publication_state_check' => "(status = 'draft' AND is_visible = 0 AND published_at IS NULL) OR (status = 'published' AND published_at IS NOT NULL)",
            'languages_key_locked_check' => "status <> 'published' OR key_locked = 1",
            'languages_position_check' => 'position >= 0',
        ] as $name => $expression) {
            DB::statement("ALTER TABLE languages ADD CONSTRAINT {$name} CHECK ({$expression})");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
