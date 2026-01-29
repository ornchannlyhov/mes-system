<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable();
            $table->foreignId('bom_id')->constrained()->onDelete('cascade');
            $table->foreignId('work_center_id')->constrained();
            $table->string('name');
            $table->integer('sequence')->default(0);
            $table->decimal('duration_minutes', 10, 2)->default(0);
            $table->boolean('needs_quality_check')->default(false);
            $table->text('instructions')->nullable();
            $table->string('instruction_file_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operations');
    }
};
