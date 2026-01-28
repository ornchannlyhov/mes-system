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
        Schema::create('cost_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable();
            $table->foreignId('manufacturing_order_id')->nullable()->constrained();
            $table->foreignId('work_order_id')->nullable()->constrained();
            $table->foreignId('consumption_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('scrap_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('cost_type', ['material', 'labor', 'overhead', 'scrap', 'material_variance']);

            // Details
            $table->foreignId('product_id')->nullable()->constrained(); // For material costs
            $table->decimal('quantity', 15, 4)->nullable();
            $table->decimal('unit_cost', 15, 4)->nullable();
            $table->decimal('total_cost', 15, 4);

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cost_entries');
    }
};
