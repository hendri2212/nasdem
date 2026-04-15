<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_default_seeded_users(): void
    {
        $this->seed(AdminUserSeeder::class);

        $superadmin = User::query()->where('email', 'admin@nasdem.test')->first();
        $admin = User::query()->where('email', 'admin@gmail.com')->first();
        $user = User::query()->where('email', 'user@gmail.com')->first();

        $this->assertNotNull($superadmin);
        $this->assertSame('Super Admin Nasdem', $superadmin->name);
        $this->assertSame(UserRole::Superadmin, $superadmin->role);
        $this->assertTrue(Hash::check('password', $superadmin->password));

        $this->assertNotNull($admin);
        $this->assertSame('Admin Nasdem', $admin->name);
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue(Hash::check('password', $admin->password));

        $this->assertNotNull($user);
        $this->assertSame('User Nasdem', $user->name);
        $this->assertSame(UserRole::User, $user->role);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_it_does_not_create_duplicate_seeded_users(): void
    {
        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $this->assertSame(1, User::query()->where('email', 'admin@nasdem.test')->count());
        $this->assertSame(1, User::query()->where('email', 'admin@gmail.com')->count());
        $this->assertSame(1, User::query()->where('email', 'user@gmail.com')->count());
    }
}
