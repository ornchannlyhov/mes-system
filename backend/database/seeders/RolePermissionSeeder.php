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
        $adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
        $managerRole = \App\Models\Role::firstOrCreate(['name' => 'manager'], ['label' => 'Manager']);
        $operatorRole = \App\Models\Role::firstOrCreate(['name' => 'operator'], ['label' => 'Operator']);

        // 3. Assign Permissions

        // Admin: All permissions
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
