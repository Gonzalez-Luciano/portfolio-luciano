<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6 scroll scene copy (spec 2026-09-14-phase-6-cinematic-scroll-design.md).
 *
 * The public scene renders the professional statement in three emphasis
 * tiers and the closing title on two lines. Those splits are editorial
 * decisions, so they are managed as optional bilingual Profile pairs instead
 * of being derived in the frontend. All columns are nullable: an empty group
 * never blocks publication and the API exposes it as `null`.
 */
return new class extends Migration
{
    private const COLUMNS = [
        'statement_lead_es', 'statement_lead_en',
        'statement_emphasis_es', 'statement_emphasis_en',
        'statement_tail_es', 'statement_tail_en',
        'closing_line_one_es', 'closing_line_one_en',
        'closing_line_two_es', 'closing_line_two_en',
    ];

    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $previous = 'availability_en';

            foreach (self::COLUMNS as $column) {
                $table->text($column)->nullable()->after($previous);
                $previous = $column;
            }
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(self::COLUMNS);
        });
    }
};
