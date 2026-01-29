<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['preventive', 'corrective'])->default('preventive');
            $table->text('description')->nullable();
            $table->text('actions_taken')->nullable();
            $table->decimal('cost', 10, 2)->default(0);
            $table->foreignId('performed_by')->constrained('users');
            $table->datetime('performed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_logs');
    }
};
