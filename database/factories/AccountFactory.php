<?php

namespace Database\Factories;

use App\Enums\AccountType;
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
            'name' => fake()->randomElement(['Checking', 'Savings', 'Cash']),
            'type' => fake()->randomElement([
                AccountType::Checking,
                AccountType::Savings,
                AccountType::Cash,
                AccountType::Other,
            ]),
        ];
    }

    public function creditCard(?int $creditLimitCents = null): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->randomElement(['Credit Card', 'Visa', 'Mastercard']),
            'type' => AccountType::CreditCard,
            'credit_limit' => $creditLimitCents !== null
                ? number_format($creditLimitCents / 100, 2, '.', '')
                : null,
        ]);
    }
}
