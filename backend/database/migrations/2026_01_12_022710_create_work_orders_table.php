<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('manufacturing_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('operation_id')->constrained();
            $table->foreignId('work_center_id')->constrained();
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->integer('sequence')->default(0);
            $table->enum('status', ['pending', 'ready', 'in_progress', 'paused', 'done', 'blocked'])->default('pending');
            $table->decimal('duration_expected', 10, 2)->default(0);
            $table->decimal('duration_actual', 10, 2)->default(0);
            $table->decimal('quantity_produced', 10, 4)->default(0);
            $table->datetime('started_at')->nullable();
            $table->datetime('actual_start')->nullable();
            $table->datetime('finished_at')->nullable();
            $table->text('notes')->nullable();

            // QA Fields
            $table->enum('qa_status', ['pending', 'pass', 'fail'])->default('pending');
            $table->text('qa_comments')->nullable();
            $table->foreignId('qa_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('qa_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Performance Indexes
            $table->index('status');
            $table->index('qa_status');
            $table->index('work_center_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
