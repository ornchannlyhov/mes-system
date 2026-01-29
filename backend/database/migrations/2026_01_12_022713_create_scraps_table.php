<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scraps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('manufacturing_order_id')->nullable()->constrained();
            $table->foreignId('work_order_id')->nullable()->constrained();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('lot_id')->nullable()->constrained();
            $table->foreignId('location_id')->nullable()->constrained(); // Where scrap was found
            $table->decimal('quantity', 15, 4);
            $table->string('reason')->nullable();
            $table->foreignId('reported_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scraps');
    }
};
