<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $team = User::first()?->currentTeam;

        if (! $team) {
            return;
        }

        $names = ['Checking', 'Savings', 'Credit Card', 'Investment', 'Cash'];

        foreach ($names as $i => $name) {
            Account::firstOrCreate([
                'team_id' => $team->id,
                'name' => $name,
            ], [
                'sort_order' => $i + 1,
            ]);
        }
    }
}
