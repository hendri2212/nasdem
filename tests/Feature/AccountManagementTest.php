<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_view_the_account_management_page(): void
    {
        $user = User::factory()->create();
        $listedUsers = User::factory()->count(2)->create();

        $response = $this->actingAs($user)->get(route('account'));

        $response->assertOk();
        $response->assertSee($listedUsers->first()->name);
        $response->assertSee($listedUsers->last()->email);
        $response->assertSee(UserRole::User->value);
    }

    public function test_authenticated_users_can_create_a_new_user_from_the_account_management_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('account.store'), [
            'name' => 'New Team Member',
            'email' => 'member@example.com',
            'role' => UserRole::Admin->value,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('account'));
        $this->assertDatabaseHas('users', [
            'name' => 'New Team Member',
            'email' => 'member@example.com',
            'role' => UserRole::Admin->value,
        ]);
    }

    public function test_user_creation_requires_a_unique_email_address(): void
    {
        $user = User::factory()->create();
        $existingUser = User::factory()->create();

        $response = $this->from(route('account'))->actingAs($user)->post(route('account.store'), [
            'name' => 'Duplicate Email',
            'email' => $existingUser->email,
            'role' => UserRole::User->value,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('account'));
        $response->assertSessionHasErrors(['email']);
    }

    public function test_user_creation_requires_a_valid_role(): void
    {
        $user = User::factory()->create();

        $response = $this->from(route('account'))->actingAs($user)->post(route('account.store'), [
            'name' => 'Invalid Role User',
            'email' => 'invalid-role@example.com',
            'role' => 'manager',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('account'));
        $response->assertSessionHasErrors(['role']);
    }
}
