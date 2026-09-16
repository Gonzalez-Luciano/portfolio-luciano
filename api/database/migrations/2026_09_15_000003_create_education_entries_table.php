<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Structured education list for the About section (spec 2026-09-14-phase-6-portfolio-redesign-design.md §4.5).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('education_entries', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->boolean('key_locked')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->string('institution')->nullable();
            $table->string('program_es')->nullable();
            $table->string('program_en')->nullable();
            $table->string('detail_es')->nullable();
            $table->string('detail_en')->nullable();
            $table->unsignedSmallInteger('start_year')->nullable();
            $table->unsignedSmallInteger('end_year')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->boolean('is_visible')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'is_visible', 'position', 'key']);
        });

        foreach ([
            'education_entries_publication_state_check' => "(status = 'draft' AND is_visible = 0 AND published_at IS NULL) OR (status = 'published' AND published_at IS NOT NULL)",
            'education_entries_key_locked_check' => "status <> 'published' OR key_locked = 1",
            'education_entries_position_check' => 'position >= 0',
            'education_entries_start_year_check' => 'start_year IS NULL OR start_year BETWEEN 1000 AND 9999',
            'education_entries_end_year_check' => 'end_year IS NULL OR end_year BETWEEN 1000 AND 9999',
            'education_entries_chronology_check' => 'start_year IS NULL OR end_year IS NULL OR end_year >= start_year',
        ] as $name => $expression) {
            DB::statement("ALTER TABLE education_entries ADD CONSTRAINT {$name} CHECK ({$expression})");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('education_entries');
    }
};
