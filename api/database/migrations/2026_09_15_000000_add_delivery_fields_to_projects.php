<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6 project dossier fields (spec 2026-09-14-phase-6-portfolio-redesign-design.md §4.1).
 *
 * `delivery_status` is named apart from the existing publication `status`
 * column; the public API exposes it as `status`. Every new editorial column is
 * nullable so drafts can be saved incomplete; publication rules live in
 * PublicationValidator.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->enum('kind', ['client', 'personal'])->default('personal')->after('position');
            $table->string('client_name')->nullable()->after('kind');
            $table->string('role_es')->nullable()->after('title_en');
            $table->string('role_en')->nullable()->after('role_es');
            $table->enum('delivery_status', ['in_production', 'in_use', 'public_demo', 'in_development'])->nullable()->after('role_en');
            $table->text('result_es')->nullable()->after('solution_en');
            $table->text('result_en')->nullable()->after('result_es');
        });

        DB::statement("ALTER TABLE projects ADD CONSTRAINT projects_client_name_kind_check CHECK (kind = 'client' OR client_name IS NULL)");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE projects DROP CHECK projects_client_name_kind_check');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['kind', 'client_name', 'role_es', 'role_en', 'delivery_status', 'result_es', 'result_en']);
        });
    }
};
