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
        // 1. Roles & Permissions (creates roles including superadmin)
        $this->call(RolePermissionSeeder::class);

        $superadminRole = \App\Models\Role::where('name', 'superadmin')->first();
        $adminRole = \App\Models\Role::where('name', 'admin')->first();
        $managerRole = \App\Models\Role::where('name', 'manager')->first();
        $operatorRole = \App\Models\Role::where('name', 'operator')->first();

        // 0. Create Superadmin User (no organization)
        $superadmin = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role_id' => $superadminRole->id,
                'is_active' => true,
                'is_approved' => true,
                'organization_id' => null, // No organization
            ]
        );

        // 1. Create Demo Organization
        $organization = \App\Models\Organization::firstOrCreate(
            ['name' => 'Demo Organization'],
            [
                'is_active' => true,
                'activated_at' => now(),
                'activated_by' => $superadmin->id,
                'user_limit' => 3, // Add user limit
            ]
        );

        // 2. Users (all pre-approved for demo)
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role_id' => $adminRole->id,
                'is_active' => true,
                'is_approved' => true,
                'approved_at' => now(),
                'approved_by' => $superadmin->id,
                'organization_id' => $organization->id,
            ]
        );
        $organization->update(['owner_id' => $admin->id]);

        User::firstOrCreate(
            ['email' => 'operator@example.com'],
            [
                'name' => 'John Operator',
                'password' => Hash::make('password'),
                'role_id' => $operatorRole->id,
                'is_active' => true,
                'is_approved' => true,
                'organization_id' => $organization->id,
            ]
        );

        User::firstOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'Jane Manager',
                'password' => Hash::make('password'),
                'role_id' => $managerRole->id,
                'is_active' => true,
                'is_approved' => true,
                'approved_at' => now(),
                'approved_by' => $superadmin->id,
                'organization_id' => $organization->id,
            ]
        );

        // 3. Create a pending/unapproved organization for testing approvals
        $pendingOrg = \App\Models\Organization::firstOrCreate(
            ['name' => 'Pending Organization'],
            [
                'is_active' => false,
                'activated_at' => null,
                'activated_by' => null,
            ]
        );

        User::firstOrCreate(
            ['email' => 'pending@example.com'],
            [
                'name' => 'Pending User',
                'password' => Hash::make('password'),
                'role_id' => $adminRole->id,
                'is_active' => true,
                'is_approved' => false, // NOT approved
                'approved_at' => null,
                'approved_by' => null,
                'organization_id' => $pendingOrg->id,
            ]
        );
        $pendingOrg->update(['owner_id' => \App\Models\User::where('email', 'pending@example.com')->first()->id]);

        // 4. Create another pending organization for testing
        $pendingOrg2 = \App\Models\Organization::firstOrCreate(
            ['name' => 'New Company Ltd'],
            [
                'is_active' => false,
                'activated_at' => null,
                'activated_by' => null,
            ]
        );

        User::firstOrCreate(
            ['email' => 'newuser@example.com'],
            [
                'name' => 'New User',
                'password' => Hash::make('password'),
                'role_id' => $adminRole->id,
                'is_active' => true,
                'is_approved' => false, // NOT approved
                'approved_at' => null,
                'approved_by' => null,
                'organization_id' => $pendingOrg2->id,
            ]
        );
        $pendingOrg2->update(['owner_id' => \App\Models\User::where('email', 'newuser@example.com')->first()->id]);

        // For seeders to use the team, we can login the admin
        Auth::login($admin);

        // 2. Clothing Demo Data (Replaces old Master/Transactional Data)
        $this->call([
            ClothingSeeder::class,
        ]);

        $this->command->info('✅ Database fully seeded for Demo!');
        $this->command->info('   Superadmin: superadmin@example.com / password (use /sysadmin/login)');
        $this->command->info('   Admin: admin@example.com / password');
        $this->command->info('   Operator: operator@example.com / password');
        $this->command->info('   Manager: manager@example.com / password');
        $this->command->info('   Pending Users: pending@example.com, newuser@example.com / password (need approval)');
    }
}
