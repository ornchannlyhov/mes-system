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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->enum('type', ['raw', 'component', 'finished'])->default('raw');
            $table->enum('tracking', ['none', 'lot', 'serial'])->default('none');
            $table->string('uom', 20)->default('pcs'); // unit of measure
            $table->decimal('cost', 15, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('version', 20)->default('1.0');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
