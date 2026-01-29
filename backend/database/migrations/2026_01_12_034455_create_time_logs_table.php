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
        Schema::create('time_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable();
            $table->foreignId('work_order_id')->constrained();
            $table->foreignId('user_id')->constrained();

            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->decimal('duration_minutes', 10, 2)->nullable();

            $table->enum('log_type', ['setup', 'production', 'cleanup', 'break'])->default('production');

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_logs');
    }
};
