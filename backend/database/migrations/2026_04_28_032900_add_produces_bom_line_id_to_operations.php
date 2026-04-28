<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('operations', function (Blueprint $table) {
            $table->foreignId('produces_bom_line_id')->nullable()->constrained('bom_lines')->onDelete('set null')->after('instruction_file_url');
        });
    }

    public function down(): void
    {
        Schema::table('operations', function (Blueprint $table) {
            $table->dropForeign(['produces_bom_line_id']);
            $table->dropColumn('produces_bom_line_id');
        });
    }
};
