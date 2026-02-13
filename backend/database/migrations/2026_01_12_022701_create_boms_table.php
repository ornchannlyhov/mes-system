<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('boms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['normal', 'phantom'])->default('normal');
            $table->decimal('qty_produced', 15, 4)->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Performance Index
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boms');
    }
};
