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
        Schema::create('quality_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operation_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('type', ['pass_fail', 'measurement', 'text'])->default('pass_fail');
            $table->string('label'); // "Check voltage", "Inspect visual defects"
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->boolean('is_template')->default(false);
            $table->decimal('min_value', 10, 4)->nullable();
            $table->decimal('max_value', 10, 4)->nullable();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('quality_check_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable();
            $table->foreignId('quality_check_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('check_name')->nullable();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pass', 'fail']);
            $table->string('value')->nullable(); // "12.5" or "Passed"
            $table->text('notes')->nullable();
            $table->foreignId('checked_by')->constrained('users');
            $table->timestamp('checked_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_check_results');
        Schema::dropIfExists('quality_checks');
    }
};
