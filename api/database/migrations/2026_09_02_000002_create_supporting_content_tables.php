<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technologies', function (Blueprint $table) {
            $this->keyedColumns($table);
            $table->string('name')->nullable();
            $table->enum('category', ['backend', 'data', 'integration', 'collaboration']);
            $table->string('icon_private_path', 512)->nullable();
            $table->string('icon_public_path', 512)->nullable();
            $table->string('icon_mime')->nullable();
            $table->unsignedBigInteger('icon_size')->nullable();
            $this->publicationColumns($table);
        });

        Schema::create('expertise_areas', function (Blueprint $table) {
            $this->keyedColumns($table);
            $table->string('title_es')->nullable();
            $table->string('title_en')->nullable();
            $table->text('description_es')->nullable();
            $table->text('description_en')->nullable();
            $this->publicationColumns($table);
        });

        Schema::create('work_principles', function (Blueprint $table) {
            $this->keyedColumns($table);
            $table->text('statement_es')->nullable();
            $table->text('statement_en')->nullable();
            $this->publicationColumns($table);
        });

        Schema::create('professional_links', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['linkedin', 'github', 'email'])->unique();
            $table->unsignedInteger('position')->default(0);
            $table->string('destination', 2048);
            $table->string('label_es')->nullable();
            $table->string('label_en')->nullable();
            $this->publicationColumns($table);
            $table->index(['status', 'is_visible', 'position', 'type']);
        });

        Schema::create('cv_documents', function (Blueprint $table) {
            $table->id();
            $table->enum('locale', ['es', 'en'])->unique();
            $table->string('label')->nullable();
            $table->string('private_path', 512)->nullable();
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $this->publicationColumns($table);
        });

        $this->addKeyedChecks('technologies');
        $this->addOwnedAssetCheck('technologies', 'icon');
        $this->addKeyedChecks('expertise_areas');
        $this->addKeyedChecks('work_principles');
        $this->addPublicationCheck('professional_links');
        $this->addCheck('professional_links', 'professional_links_position_check', 'position >= 0');
        $this->addPublicationCheck('cv_documents');
        $this->addOwnedFileCheck('cv_documents');
    }

    public function down(): void
    {
        Schema::dropIfExists('cv_documents');
        Schema::dropIfExists('professional_links');
        Schema::dropIfExists('work_principles');
        Schema::dropIfExists('expertise_areas');
        Schema::dropIfExists('technologies');
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

    private function addOwnedFileCheck(string $table): void
    {
        $this->addCheck($table, "{$table}_private_group_check", '(private_path IS NULL AND mime IS NULL AND size IS NULL) OR (private_path IS NOT NULL AND mime IS NOT NULL AND size IS NOT NULL)');
    }

    private function addCheck(string $table, string $name, string $expression): void
    {
        DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$name} CHECK ({$expression})");
    }
};
