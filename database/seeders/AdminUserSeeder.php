<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@nasdem.test'],
            [
                'name' => 'Admin Nasdem',
                'role' => UserRole::Superadmin,
                'email_verified_at' => now(),
                'password' => 'password',
            ],
        );
    }
}
