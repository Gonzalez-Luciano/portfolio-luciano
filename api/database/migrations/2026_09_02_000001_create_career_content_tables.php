<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experiences', function (Blueprint $table) {
            $this->keyedColumns($table);
            $table->string('role_es')->nullable();
            $table->string('role_en')->nullable();
            $table->text('summary_es')->nullable();
            $table->text('summary_en')->nullable();
            $table->string('organization_label_es')->nullable();
            $table->string('organization_label_en')->nullable();
            $table->unsignedSmallInteger('start_year');
            $table->unsignedTinyInteger('start_month');
            $table->unsignedSmallInteger('end_year')->nullable();
            $table->unsignedTinyInteger('end_month')->nullable();
            $this->publicationColumns($table);
        });

        Schema::create('experience_highlights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experience_id')->constrained()->cascadeOnDelete();
            $table->text('content_es')->nullable();
            $table->text('content_en')->nullable();
            $table->unsignedInteger('position');
            $table->timestamps();
            $table->index(['experience_id', 'position', 'id']);
        });

        Schema::create('work_cases', function (Blueprint $table) {
            $this->keyedColumns($table);
            $table->string('title_es')->nullable();
            $table->string('title_en')->nullable();
            $table->text('context_es')->nullable();
            $table->text('context_en')->nullable();
            $table->text('problem_es')->nullable();
            $table->text('problem_en')->nullable();
            $table->text('contribution_es')->nullable();
            $table->text('contribution_en')->nullable();
            $table->text('technical_approach_es')->nullable();
            $table->text('technical_approach_en')->nullable();
            $table->text('outcome_es')->nullable();
            $table->text('outcome_en')->nullable();
            $this->publicationColumns($table);
        });

        Schema::create('projects', function (Blueprint $table) {
            $this->keyedColumns($table);
            $table->string('title_es')->nullable();
            $table->string('title_en')->nullable();
            $table->text('summary_es')->nullable();
            $table->text('summary_en')->nullable();
            $table->text('problem_es')->nullable();
            $table->text('problem_en')->nullable();
            $table->text('solution_es')->nullable();
            $table->text('solution_en')->nullable();
            $table->boolean('featured')->default(false);
            $table->string('demo_url', 2048)->nullable();
            $table->string('repository_url', 2048)->nullable();
            $table->string('image_private_path', 512)->nullable();
            $table->string('image_public_path', 512)->nullable();
            $table->string('image_mime')->nullable();
            $table->unsignedBigInteger('image_size')->nullable();
            $table->string('image_alt_es')->nullable();
            $table->string('image_alt_en')->nullable();
            $this->publicationColumns($table);
        });

        $this->addKeyedChecks('experiences');
        $this->addCheck('experiences', 'experiences_start_year_check', 'start_year BETWEEN 1000 AND 9999');
        $this->addCheck('experiences', 'experiences_start_month_check', 'start_month BETWEEN 1 AND 12');
        $this->addCheck('experiences', 'experiences_end_pair_check', '(end_year IS NULL AND end_month IS NULL) OR (end_year IS NOT NULL AND end_month IS NOT NULL)');
        $this->addCheck('experiences', 'experiences_end_year_check', 'end_year IS NULL OR end_year BETWEEN 1000 AND 9999');
        $this->addCheck('experiences', 'experiences_end_month_check', 'end_month IS NULL OR end_month BETWEEN 1 AND 12');
        $this->addCheck('experiences', 'experiences_monthly_chronology_check', 'end_year IS NULL OR (end_year > start_year OR (end_year = start_year AND end_month >= start_month))');
        $this->addCheck('experience_highlights', 'experience_highlights_position_check', 'position >= 0');
        $this->addKeyedChecks('work_cases');
        $this->addKeyedChecks('projects');
        $this->addOwnedAssetCheck('projects', 'image');
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
        Schema::dropIfExists('work_cases');
        Schema::dropIfExists('experience_highlights');
        Schema::dropIfExists('experiences');
    }

    private function keyedColumns(Blueprint $table): void
    {
        $table->id();
        $table->string('key', 100)->unique();
        $table->boolean('key_locked')->default(false);
        $table->unsignedInteger('position')->default(0);
        $table->index(['status', 'is_visible', 'position', 'key']);
    }

    private function publicationColumns(Blueprint $table): void
    {
        $table->enum('status', ['draft', 'published'])->default('draft');
        $table->boolean('is_visible')->default(false);
        $table->timestamp('published_at')->nullable();
        $table->timestamps();
    }

    private function addKeyedChecks(string $table): void
    {
        $this->addPublicationCheck($table);
        $this->addCheck($table, "{$table}_key_locked_check", "status <> 'published' OR key_locked = 1");
        $this->addCheck($table, "{$table}_position_check", 'position >= 0');
    }

    private function addPublicationCheck(string $table): void
    {
        $this->addCheck($table, "{$table}_publication_state_check", "(status = 'draft' AND is_visible = 0 AND published_at IS NULL) OR (status = 'published' AND published_at IS NOT NULL)");
    }

    private function addOwnedAssetCheck(string $table, string $prefix): void
    {
        $this->addCheck($table, "{$table}_{$prefix}_private_group_check", "({$prefix}_private_path IS NULL AND {$prefix}_mime IS NULL AND {$prefix}_size IS NULL) OR ({$prefix}_private_path IS NOT NULL AND {$prefix}_mime IS NOT NULL AND {$prefix}_size IS NOT NULL)");
        $this->addCheck($table, "{$table}_{$prefix}_public_state_check", "{$prefix}_public_path IS NULL OR ({$prefix}_private_path IS NOT NULL AND {$prefix}_mime IS NOT NULL AND {$prefix}_size IS NOT NULL AND status = 'published' AND is_visible = 1)");
    }

    private function addCheck(string $table, string $name, string $expression): void
    {
        DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$name} CHECK ({$expression})");
    }
};
