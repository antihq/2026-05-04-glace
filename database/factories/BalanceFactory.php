<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Balance;
use App\Models\Checkin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Balance>
 */
class BalanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'checkin_id' => Checkin::factory(),
            'amount' => fake()->randomFloat(2, -10000, 10000),
            'checked_in_at' => now()->subMinutes(fake()->numberBetween(0, 10080)),
        ];
    }
}
