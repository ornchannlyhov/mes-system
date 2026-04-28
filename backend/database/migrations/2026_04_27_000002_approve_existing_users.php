<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get the superadmin user (or first admin if no superadmin)
        $superadmin = DB::table('users')
            ->whereNull('organization_id')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('roles')
                    ->whereColumn('roles.id', 'users.role_id')
                    ->where('roles.name', 'superadmin');
            })
            ->first();

        $approvedBy = $superadmin?->id ?? null;

        // Approve all existing organization users that are not yet approved
        DB::table('users')
            ->whereNotNull('organization_id')
            ->where('is_approved', false)
            ->update([
                'is_approved' => true,
                'approved_at' => now(),
                'approved_by' => $approvedBy,
            ]);

        // Activate all organizations that are not yet active
        DB::table('organizations')
            ->where('is_active', false)
            ->update([
                'is_active' => true,
                'activated_at' => now(),
                'activated_by' => $approvedBy,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot un-approve users safely
    }
};
