<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SimpleDemoSeeder extends Seeder
{
    public function run(): void
    {
        $org = \App\Models\Organization::where('name', 'Simple Demo')->first();

        if (!$org) {
            $this->command->warn('SimpleDemoSeeder: "Simple Demo" organisation not found — skipping.');
            return;
        }

        \App\Models\User::withoutGlobalScope('organization')->firstOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name'            => 'Demo User',
                'password'        => Hash::make('password'),
                'organization_id' => $org->id,
            ]
        );

        $this->command->info('  ✔ Simple Demo user ready — login: demo@example.com / password');
    }
}
