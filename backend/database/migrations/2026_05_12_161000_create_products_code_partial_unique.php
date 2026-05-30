<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create a unique index for non-deleted records using raw SQL
        DB::statement('
            CREATE UNIQUE INDEX products_code_unique_active 
            ON products (code) 
            WHERE deleted_at IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS products_code_unique_active');
    }
};
