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
        collect([
            [
                'name' => 'Super Admin Nasdem',
                'email' => 'admin@nasdem.test',
                'role' => UserRole::Superadmin,
            ],
            [
                'name' => 'Admin Nasdem',
                'email' => 'admin@gmail.com',
                'role' => UserRole::Admin,
            ],
            [
                'name' => 'User Nasdem',
                'email' => 'user@gmail.com',
                'role' => UserRole::User,
            ],
        ])->each(function (array $user): void {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'password' => 'password',
                ],
            );
        });
    }
}
