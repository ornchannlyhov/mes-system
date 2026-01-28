<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable();
            $table->foreignId('equipment_id')->constrained('equipment');
            $table->string('name'); // "Monthly Oil Change"

            $table->enum('trigger_type', ['time', 'cycles']);
            $table->integer('interval_days')->nullable();
            $table->integer('interval_cycles')->nullable();

            $table->date('last_maintenance')->nullable();
            $table->date('next_maintenance')->nullable();

            $table->text('instructions')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_schedules');
    }
};
