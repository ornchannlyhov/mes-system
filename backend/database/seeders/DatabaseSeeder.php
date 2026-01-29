<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Users
        // 0. Create Organization
        $organization = \App\Models\Organization::create([
            'name' => 'Demo Organization',
        ]);

        // 1. Roles & Permissions
        $this->call(RolePermissionSeeder::class);

        $adminRole = \App\Models\Role::where('name', 'admin')->first();
        $managerRole = \App\Models\Role::where('name', 'manager')->first();
        $operatorRole = \App\Models\Role::where('name', 'operator')->first();

        // 2. Users
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'is_active' => true,
            'organization_id' => $organization->id,
        ]);
        $organization->update(['owner_id' => $admin->id]);

        User::create([
            'name' => 'John Operator',
            'email' => 'operator@example.com',
            'password' => Hash::make('password'),
            'role_id' => $operatorRole->id,
            'is_active' => true,
            'organization_id' => $organization->id,
        ]);

        User::create([
            'name' => 'Jane Manager',
            'email' => 'manager@example.com',
            'password' => Hash::make('password'),
            'role_id' => $managerRole->id,
            'is_active' => true,
            'organization_id' => $organization->id,
        ]);

        // For seeders to use the team, we can login the admin
        Auth::login($admin);

        // 2. Clothing Demo Data (Replaces old Master/Transactional Data)
        $this->call([
            ClothingSeeder::class,
        ]);

        $this->command->info('✅ Database fully seeded for Demo!');
        $this->command->info('   Admin: admin@example.com / password');
        $this->command->info('   Operator: operator@example.com / password');
        $this->command->info('   Manager: manager@example.com / password');
    }
}
