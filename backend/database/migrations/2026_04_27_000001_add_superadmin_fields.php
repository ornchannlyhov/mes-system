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
        // Add approval fields to users table
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_approved')->default(false)->after('is_active');
            $table->timestamp('approved_at')->nullable()->after('is_approved');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('approved_at');
        });

        // Add activation fields to organizations table
        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->after('owner_id');
            $table->timestamp('activated_at')->nullable()->after('is_active');
            $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete()->after('activated_at');
        });

        // Add is_system flag to roles table for superadmin role
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('organization_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['is_approved', 'approved_at', 'approved_by']);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropForeign(['activated_by']);
            $table->dropColumn(['is_active', 'activated_at', 'activated_by']);
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['is_system']);
        });
    }
};
