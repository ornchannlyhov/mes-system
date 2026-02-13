<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50)->unique()->nullable();
            $table->foreignId('work_center_id')->nullable()->constrained();
            $table->date('last_maintenance')->nullable();
            $table->date('next_maintenance')->nullable();
            $table->integer('maintenance_interval_days')->default(30);
            $table->enum('status', ['operational', 'maintenance', 'broken'])->default('operational');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Performance Index
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
