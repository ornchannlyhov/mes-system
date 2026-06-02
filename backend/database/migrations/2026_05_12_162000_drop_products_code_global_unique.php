<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // The original migration added a global unique index on code (including soft-deleted rows).
        // The partial index products_code_unique_active (WHERE deleted_at IS NULL) is the correct
        // constraint, so the global one must be dropped to allow reuse of codes from deleted products.
        DB::statement('ALTER TABLE products DROP CONSTRAINT IF EXISTS products_code_unique');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_code_unique UNIQUE (code)');
    }
};
