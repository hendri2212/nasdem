<?php

namespace Tests\Feature;

use App\Enums\TransactionLocation;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_search_transactions(): void
    {
        $user = User::factory()->create();

        Transaction::factory()->create([
            'description' => 'District logistics purchase',
            'type' => TransactionType::Debit->value,
            'location' => TransactionLocation::Cash->value,
        ]);

        Transaction::factory()->create([
            'description' => 'Regional donation transfer',
            'type' => TransactionType::Credit->value,
            'location' => TransactionLocation::Bank->value,
        ]);

        $response = $this->actingAs($user)->get(route('transactions', [
            'search' => 'donation',
        ]));

        $response->assertOk();
        $response->assertSee('Regional donation transfer');
        $response->assertDontSee('District logistics purchase');
    }

    public function test_authenticated_users_can_filter_transactions_by_type_and_location(): void
    {
        $user = User::factory()->create();

        Transaction::factory()->create([
            'description' => 'Bank donation',
            'type' => TransactionType::Credit->value,
            'location' => TransactionLocation::Bank->value,
        ]);

        Transaction::factory()->create([
            'description' => 'Cash donation',
            'type' => TransactionType::Credit->value,
            'location' => TransactionLocation::Cash->value,
        ]);

        Transaction::factory()->create([
            'description' => 'Bank operational expense',
            'type' => TransactionType::Debit->value,
            'location' => TransactionLocation::Bank->value,
        ]);

        $response = $this->actingAs($user)->get(route('transactions', [
            'type' => TransactionType::Credit->value,
            'location' => TransactionLocation::Bank->value,
        ]));

        $response->assertOk();
        $response->assertSee('Bank donation');
        $response->assertDontSee('Cash donation');
        $response->assertDontSee('Bank operational expense');
    }
}
