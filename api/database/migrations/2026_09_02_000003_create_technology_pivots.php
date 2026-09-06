<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experience_technology', function (Blueprint $table) {
            $table->foreignId('experience_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technology_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->unique(['experience_id', 'technology_id']);
        });
        Schema::create('technology_work_case', function (Blueprint $table) {
            $table->foreignId('work_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technology_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->unique(['work_case_id', 'technology_id']);
        });
        Schema::create('project_technology', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technology_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->unique(['project_id', 'technology_id']);
        });

        // Schema Builder cannot express these named MySQL checks.
        $this->addCheck('experience_technology', 'experience_technology_position_check');
        $this->addCheck('technology_work_case', 'technology_work_case_position_check');
        $this->addCheck('project_technology', 'project_technology_position_check');
    }

    public function down(): void
    {
        Schema::dropIfExists('project_technology');
        Schema::dropIfExists('technology_work_case');
        Schema::dropIfExists('experience_technology');
    }

    private function addCheck(string $table, string $name): void
    {
        DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$name} CHECK (position >= 0)");
    }
};
