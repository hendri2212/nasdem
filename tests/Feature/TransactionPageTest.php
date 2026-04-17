<?php

namespace Tests\Feature;

use App\Enums\TransactionLocation;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page_from_the_transactions_page(): void
    {
        $response = $this->get('/transactions');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_users_can_visit_the_transactions_page(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create([
            'description' => 'Regional donation transfer',
            'type' => TransactionType::Credit->value,
            'location' => TransactionLocation::Bank->value,
            'amount' => 1500000,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('transactions'));

        $response->assertOk();
        $response->assertSee('Regional donation transfer');
        $response->assertSee((string) $transaction->amount);
    }

    public function test_authenticated_users_can_create_a_transaction(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'transaction_date' => '2026-04-15 10:30:00',
            'type' => TransactionType::Credit->value,
            'location' => TransactionLocation::Cash->value,
            'description' => 'Cash contribution from district office',
            'amount' => 2500000,
        ]);

        $response->assertRedirect(route('transactions'));
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => TransactionType::Credit->value,
            'location' => TransactionLocation::Cash->value,
            'description' => 'Cash contribution from district office',
            'amount' => 2500000,
        ]);
    }

    public function test_authenticated_users_can_update_a_transaction(): void
    {
        $user = User::factory()->create();

        Transaction::factory()->create([
            'type' => TransactionType::Credit->value,
            'location' => TransactionLocation::Bank->value,
            'amount' => 3000000,
        ]);

        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'transaction_date' => '2026-04-15 10:30:00',
            'type' => TransactionType::Credit->value,
            'location' => TransactionLocation::Cash->value,
            'description' => 'Original cash contribution',
            'amount' => 2500000,
        ]);

        $response = $this->from(route('transactions'))->actingAs($user)->put(route('transactions.update', $transaction), [
            'transaction_date' => '2026-04-16 13:15:00',
            'type' => TransactionType::Debit->value,
            'location' => TransactionLocation::Bank->value,
            'description' => 'Updated operational transfer',
            'amount' => 1750000,
        ]);

        $response->assertRedirect(route('transactions'));

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'user_id' => $user->id,
            'transaction_date' => '2026-04-16 13:15:00',
            'type' => TransactionType::Debit->value,
            'location' => TransactionLocation::Bank->value,
            'description' => 'Updated operational transfer',
            'amount' => 1750000,
        ]);
    }

    public function test_authenticated_users_can_delete_a_transaction(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->from(route('transactions'))->actingAs($user)->delete(route('transactions.destroy', $transaction));

        $response->assertRedirect(route('transactions'));
        $this->assertDatabaseMissing('transactions', [
            'id' => $transaction->id,
        ]);
    }

    public function test_transaction_creation_requires_valid_enum_values_and_a_non_negative_amount(): void
    {
        $user = User::factory()->create();

        $response = $this->from(route('transactions'))->actingAs($user)->post(route('transactions.store'), [
            'transaction_date' => '2026-04-15 10:30:00',
            'type' => 'incoming',
            'location' => 'office',
            'description' => 'Invalid values',
            'amount' => -500,
        ]);

        $response->assertRedirect(route('transactions'));
        $response->assertSessionHasErrors(['type', 'location', 'amount']);
    }

    public function test_transaction_creation_requires_a_description_and_transaction_date(): void
    {
        $user = User::factory()->create();

        $response = $this->from(route('transactions'))->actingAs($user)->post(route('transactions.store'), [
            'transaction_date' => '',
            'type' => TransactionType::Debit->value,
            'location' => TransactionLocation::Bank->value,
            'description' => '',
            'amount' => 1000,
        ]);

        $response->assertRedirect(route('transactions'));
        $response->assertSessionHasErrors(['transaction_date', 'description']);
    }

    public function test_debit_transaction_can_not_be_created_when_cash_balance_is_zero(): void
    {
        $user = User::factory()->create();

        $response = $this->from(route('transactions'))->actingAs($user)->post(route('transactions.store'), [
            'transaction_date' => '2026-04-17 09:15:00',
            'type' => TransactionType::Debit->value,
            'location' => TransactionLocation::Cash->value,
            'description' => 'Cash withdrawal with zero balance',
            'amount' => 50000,
        ]);

        $response->assertRedirect(route('transactions'));
        $response->assertSessionHasErrors(['amount']);
        $this->assertDatabaseMissing('transactions', [
            'description' => 'Cash withdrawal with zero balance',
        ]);
    }

    public function test_debit_transaction_can_not_be_created_when_bank_balance_is_zero(): void
    {
        $user = User::factory()->create();

        $response = $this->from(route('transactions'))->actingAs($user)->post(route('transactions.store'), [
            'transaction_date' => '2026-04-17 09:15:00',
            'type' => TransactionType::Debit->value,
            'location' => TransactionLocation::Bank->value,
            'description' => 'Bank payment with zero balance',
            'amount' => 75000,
        ]);

        $response->assertRedirect(route('transactions'));
        $response->assertSessionHasErrors(['amount']);
        $this->assertDatabaseMissing('transactions', [
            'description' => 'Bank payment with zero balance',
        ]);
    }

    public function test_debit_transaction_can_not_be_created_when_amount_exceeds_available_balance(): void
    {
        $user = User::factory()->create();

        Transaction::factory()->create([
            'type' => TransactionType::Credit->value,
            'location' => TransactionLocation::Cash->value,
            'amount' => 100000,
        ]);

        $response = $this->from(route('transactions'))->actingAs($user)->post(route('transactions.store'), [
            'transaction_date' => '2026-04-17 09:15:00',
            'type' => TransactionType::Debit->value,
            'location' => TransactionLocation::Cash->value,
            'description' => 'Debit exceeds cash balance',
            'amount' => 150000,
        ]);

        $response->assertRedirect(route('transactions'));
        $response->assertSessionHasErrors(['amount']);
        $this->assertDatabaseMissing('transactions', [
            'description' => 'Debit exceeds cash balance',
        ]);
    }
}
