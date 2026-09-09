<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Spec section 8 requires alt text to be a bounded string with a
 * 500-character application maximum, but the Task 1 migrations declared
 * `photo_alt_es/en` (profiles) and `image_alt_es/en` (projects) as the
 * default `VARCHAR(255)`. Migrations are purely additive in this codebase,
 * so this widens those four columns in place through a new migration
 * rather than editing the Task 1 files. `doctrine/dbal` is not installed,
 * so this uses a raw `MODIFY COLUMN` statement instead of Blueprint's
 * `change()`, matching this migration set's existing convention of raw
 * `DB::statement` calls for anything the schema builder cannot express.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE profiles MODIFY photo_alt_es VARCHAR(500) NULL');
        DB::statement('ALTER TABLE profiles MODIFY photo_alt_en VARCHAR(500) NULL');
        DB::statement('ALTER TABLE projects MODIFY image_alt_es VARCHAR(500) NULL');
        DB::statement('ALTER TABLE projects MODIFY image_alt_en VARCHAR(500) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE profiles MODIFY photo_alt_es VARCHAR(255) NULL');
        DB::statement('ALTER TABLE profiles MODIFY photo_alt_en VARCHAR(255) NULL');
        DB::statement('ALTER TABLE projects MODIFY image_alt_es VARCHAR(255) NULL');
        DB::statement('ALTER TABLE projects MODIFY image_alt_en VARCHAR(255) NULL');
    }
};
