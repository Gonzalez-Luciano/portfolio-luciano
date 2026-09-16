<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional work case → experience link (spec 2026-09-14-phase-6-portfolio-redesign-design.md §4.3).
 * Deleting an experience leaves its cases unlinked instead of deleting them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_cases', function (Blueprint $table) {
            $table->foreignId('experience_id')->nullable()->after('position')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('work_cases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('experience_id');
        });
    }
};
