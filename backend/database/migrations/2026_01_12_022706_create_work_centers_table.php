<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('work_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50)->unique()->nullable();
            $table->string('location')->nullable(); // Simple text, not FK to locations table
            $table->decimal('cost_per_hour', 10, 2)->default(0);
            $table->decimal('overhead_per_hour', 10, 2)->default(0);
            $table->decimal('efficiency_percent', 5, 2)->default(100);
            $table->enum('status', ['active', 'maintenance', 'down'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_centers');
    }
};
