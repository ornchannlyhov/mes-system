<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable();
            $table->foreignId('manufacturing_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            $table->foreignId('lot_id')->nullable()->constrained();
            $table->foreignId('location_id')->nullable()->constrained();
            $table->decimal('qty_planned', 15, 4);
            $table->decimal('qty_consumed', 15, 4)->default(0);
            $table->decimal('cost_impact', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumptions');
    }
};
