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
            ->get();
        $managerRole->permissions()->sync($managerPermissions);

        // Operator: Execution and View permissions
        $operatorPermissions = \App\Models\Permission::where(function ($query) {
            $query->where('name', 'like', '%:read')
                ->orWhereIn('name', ['manufacturing:execute', 'quality:write', 'inventory:transfer']);
        })->get();
        $operatorRole->permissions()->sync($operatorPermissions);
    }
}
