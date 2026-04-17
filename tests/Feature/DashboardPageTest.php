<?php

namespace Tests\Feature;

use App\Enums\TransactionLocation;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_transaction_totals(): void
    {
        $user = User::factory()->create();

        Transaction::factory()->create([
            'transaction_date' => now()->startOfDay()->subDays(1),
            'type' => TransactionType::Credit->value,
            'location' => TransactionLocation::Cash->value,
            'amount' => 5_000,
        ]);

        Transaction::factory()->create([
            'transaction_date' => now()->startOfDay()->subDays(1)->addHours(2),
            'type' => TransactionType::Debit->value,
            'location' => TransactionLocation::Cash->value,
            'amount' => 1_500,
        ]);

        Transaction::factory()->create([
            'transaction_date' => now()->startOfDay(),
            'type' => TransactionType::Credit->value,
            'location' => TransactionLocation::Bank->value,
            'amount' => 10_000,
        ]);

        Transaction::factory()->create([
            'transaction_date' => now()->startOfDay()->addHours(3),
            'type' => TransactionType::Debit->value,
            'location' => TransactionLocation::Bank->value,
            'amount' => 2_500,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('Dashboard')
            ->where('totals.cash_balance', 3500)
            ->where('totals.bank_balance', 7500)
            ->where('totals.net_movement', 11000)
            ->has('chart', 7)
            ->where('chart.5.credit', 5000)
            ->where('chart.5.debit', 1500)
            ->where('chart.6.credit', 10000)
            ->where('chart.6.debit', 2500)
        );
    }
}
