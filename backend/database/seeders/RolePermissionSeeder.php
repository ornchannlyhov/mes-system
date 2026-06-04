<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Permissions
        // First truncate to avoid stale permissions (optional, but good for dev)
        // \App\Models\Permission::truncate(); 
        // We use firstOrCreate so existing ones stay, but we might want to cleanup unused ones manually or via migration.

        foreach (\App\Models\Permission::DEFAULT_PERMISSIONS as $name => $label) {
            \App\Models\Permission::firstOrCreate(['name' => $name], ['label' => $label]);
        }

        // 2. Create Roles
        // is_system = true marks roles that belong to no organization and must not appear
        // in any org's role management UI. organization_id = null is shared by both
        // system roles and global template roles, so is_system is the discriminator.

        $superadminRole = \App\Models\Role::firstOrCreate(
            ['name' => 'superadmin'],
            ['label' => 'System Administrator', 'is_system' => true, 'organization_id' => null]
        );

        // Global template roles — visible to all orgs (organization_id = null + allowGlobalRecords),
        // but is_system = false so they appear in org role lists and can be customised.
        $adminRole = \App\Models\Role::firstOrCreate(
            ['name' => 'admin'],
            ['label' => 'Administrator', 'is_system' => false, 'organization_id' => null]
        );
        $managerRole = \App\Models\Role::firstOrCreate(
            ['name' => 'manager'],
            ['label' => 'Manager', 'is_system' => false, 'organization_id' => null]
        );
        $operatorRole = \App\Models\Role::firstOrCreate(
            ['name' => 'operator'],
            ['label' => 'Operator', 'is_system' => false, 'organization_id' => null]
        );

        // 3. Assign Permissions

        // Superadmin: All permissions (system-wide access)
        $superadminRole->permissions()->sync(\App\Models\Permission::all());

        // Admin: All permissions (organization-only)
        $adminRole->permissions()->sync(\App\Models\Permission::all());

        // Manager: All except some admin functions
        $managerPermissions = \App\Models\Permission::where('name', 'not like', 'roles:%')
            ->where('name', 'not like', 'settings:%')
            ->where('name', 'not like', 'users:%')
            ->get();
        $managerRole->permissions()->sync($managerPermissions);

        // Operator: Execution, View, and simple operations
        $operatorPermissions = \App\Models\Permission::where(function ($query) {
            $query->where('name', 'like', '%:read') // View everything
                ->orWhereIn('name', [
                    'manufacturing:execute',
                    'quality:write',
                    'inventory:transfer',
                    'inventory:adjust',
                    'maintenance:read'
                ]);
        })->get();
        $operatorRole->permissions()->sync($operatorPermissions);
    }
}
