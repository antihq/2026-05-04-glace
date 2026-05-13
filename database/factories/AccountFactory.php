<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => fake()->randomElement(['Checking', 'Savings', 'Credit Card', 'Investment', 'Cash']),
        ];
    }
}
