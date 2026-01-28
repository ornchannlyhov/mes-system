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
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name')->unique(); // MR-2024-001

            $table->foreignId('equipment_id')->constrained('equipment');
            $table->enum('request_type', ['preventive', 'corrective'])->default('corrective');
            $table->enum('priority', ['low', 'normal', 'high', 'critical'])->default('normal');

            $table->enum('status', ['draft', 'confirmed', 'in_progress', 'done', 'cancelled'])->default('draft');

            // Scheduling
            $table->timestamp('scheduled_date')->nullable();
            $table->timestamp('actual_start')->nullable();
            $table->timestamp('actual_end')->nullable();
            $table->decimal('duration_hours', 8, 2)->nullable();

            $table->text('description');
            $table->text('diagnosis')->nullable();
            $table->text('actions_taken')->nullable();

            // Costs
            $table->decimal('parts_cost', 10, 2)->default(0);
            $table->decimal('labor_cost', 10, 2)->default(0);

            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('assigned_to')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};
