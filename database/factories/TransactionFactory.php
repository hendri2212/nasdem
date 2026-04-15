<?php

namespace Database\Factories;

use App\Enums\TransactionLocation;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'transaction_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'type' => fake()->randomElement(TransactionType::values()),
            'location' => fake()->randomElement(TransactionLocation::values()),
            'description' => fake()->sentence(4),
            'amount' => fake()->numberBetween(10_000, 5_000_000),
        ];
    }
}
