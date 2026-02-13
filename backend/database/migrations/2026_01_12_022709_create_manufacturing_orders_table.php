<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('manufacturing_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name', 50)->unique();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('bom_id')->constrained();
            $table->decimal('qty_to_produce', 15, 4);
            $table->decimal('qty_produced', 15, 4)->default(0);
            $table->enum('status', ['draft', 'scheduled', 'confirmed', 'in_progress', 'done', 'cancelled'])->default('draft');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->datetime('scheduled_start')->nullable();
            $table->datetime('scheduled_end')->nullable();
            $table->datetime('actual_start')->nullable();
            $table->datetime('actual_end')->nullable();
            $table->foreignId('lot_id')->nullable()->constrained(); // output lot
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Performance Indexes
            $table->index('status');
            $table->index('priority');
            $table->index('scheduled_start');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturing_orders');
    }
};
