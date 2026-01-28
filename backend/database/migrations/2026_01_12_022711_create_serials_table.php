<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name', 100)->unique();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('lot_id')->nullable()->constrained();
            $table->foreignId('manufacturing_order_id')->nullable()->constrained();
            $table->enum('status', ['available', 'in_use', 'sold', 'scrapped'])->default('available');
            $table->json('component_serials')->nullable(); // genealogy
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serials');
    }
};
