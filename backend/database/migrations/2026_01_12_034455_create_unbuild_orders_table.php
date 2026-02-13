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
        Schema::create('unbuild_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name')->unique();

            $table->foreignId('product_id')->constrained();
            $table->foreignId('bom_id')->constrained();
            $table->decimal('quantity', 15, 4);

            $table->enum('status', ['draft', 'in_progress', 'done', 'cancelled'])->default('draft');

            // Source (what we unbuild)
            $table->foreignId('lot_id')->nullable()->constrained();
            $table->foreignId('serial_number_id')->nullable()->constrained('serials');
            $table->foreignId('manufacturing_order_id')->nullable()->constrained();

            $table->text('reason')->nullable();

            $table->foreignId('location_id')->nullable()->constrained('locations');
            $table->foreignId('component_location_id')->nullable()->constrained('locations');

            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unbuild_orders');
    }
};
