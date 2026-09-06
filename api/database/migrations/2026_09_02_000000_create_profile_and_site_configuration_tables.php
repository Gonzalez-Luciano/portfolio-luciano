<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('singleton_key', 32)->default('default')->unique();
            $table->string('name')->nullable();
            $table->text('headline_es')->nullable();
            $table->text('headline_en')->nullable();
            $table->text('short_summary_es')->nullable();
            $table->text('short_summary_en')->nullable();
            $table->text('introduction_es')->nullable();
            $table->text('introduction_en')->nullable();
            $table->text('availability_es')->nullable();
            $table->text('availability_en')->nullable();
            $table->string('cta_es')->nullable();
            $table->string('cta_en')->nullable();
            $table->string('photo_private_path', 512)->nullable();
            $table->string('photo_public_path', 512)->nullable();
            $table->string('photo_mime')->nullable();
            $table->unsignedBigInteger('photo_size')->nullable();
            $table->string('photo_alt_es')->nullable();
            $table->string('photo_alt_en')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->boolean('is_visible')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('site_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('singleton_key', 32)->default('default')->unique();
            $table->text('projects_empty_message_es')->nullable();
            $table->text('projects_empty_message_en')->nullable();
            $table->text('contact_intro_es')->nullable();
            $table->text('contact_intro_en')->nullable();
            $table->string('technology_backend_label_es')->nullable();
            $table->string('technology_backend_label_en')->nullable();
            $table->string('technology_data_label_es')->nullable();
            $table->string('technology_data_label_en')->nullable();
            $table->string('technology_integration_label_es')->nullable();
            $table->string('technology_integration_label_en')->nullable();
            $table->string('technology_collaboration_label_es')->nullable();
            $table->string('technology_collaboration_label_en')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->boolean('is_visible')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        // Schema Builder cannot express these named MySQL checks.
        $this->addCheck('profiles', 'profiles_singleton_key_check', "singleton_key = 'default'");
        $this->addPublicationCheck('profiles');
        $this->addOwnedAssetCheck('profiles', 'photo');
        $this->addCheck('site_configurations', 'site_configurations_singleton_key_check', "singleton_key = 'default'");
        $this->addPublicationCheck('site_configurations');

        DB::table('profiles')->insert([
            'singleton_key' => 'default',
            'status' => 'draft',
            'is_visible' => false,
            'published_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('site_configurations')->insert([
            'singleton_key' => 'default',
            'status' => 'draft',
            'is_visible' => false,
            'published_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_configurations');
        Schema::dropIfExists('profiles');
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
