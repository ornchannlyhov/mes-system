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
        Schema::create('oee_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable();
            $table->foreignId('work_center_id')->constrained();
            $table->date('record_date');

            // Availability
            $table->decimal('planned_time_minutes', 10, 2);
            $table->decimal('actual_runtime_minutes', 10, 2);
            $table->decimal('total_standard_minutes', 12, 4)->default(0);
            $table->decimal('downtime_minutes', 10, 2)->default(0);

            // Performance
            $table->decimal('ideal_cycle_time', 10, 4); // Seconds per unit
            $table->integer('total_units_produced');

            // Quality
            $table->integer('good_units');
            $table->integer('defect_units');

            // Scores (0-100)
            $table->decimal('availability_score', 5, 2);
            $table->decimal('performance_score', 5, 2);
            $table->decimal('quality_score', 5, 2);
            $table->decimal('oee_score', 5, 2);

            $table->unique(['work_center_id', 'record_date']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oee_records');
    }
};
