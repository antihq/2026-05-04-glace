<?php

namespace Database\Factories;

use App\Models\Checkin;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Checkin>
 */
class CheckinFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'checked_in_at' => fake()->dateTimeThisMonth(),
        ];
    }
}
