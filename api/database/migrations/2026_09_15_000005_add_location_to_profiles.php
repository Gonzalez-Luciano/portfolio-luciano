<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * About section location and work modes (spec 2026-09-14-phase-6-portfolio-redesign-design.md §4.7).
 * The location is not translated; work modes are a JSON list of WorkMode values.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('location')->nullable()->after('name');
            $table->json('work_modes')->nullable()->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['location', 'work_modes']);
        });
    }
};
