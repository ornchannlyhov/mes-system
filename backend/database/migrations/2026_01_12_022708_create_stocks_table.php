<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('location_id')->constrained();
            $table->foreignId('lot_id')->nullable()->constrained();
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('reserved_qty', 15, 4)->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'location_id', 'lot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
