<?php

use App\Models\Account;
use App\Models\Balance;
use App\Models\Team;

test('account belongs to a team', function () {
    $team = Team::factory()->create();
    $account = Account::factory()->create(['team_id' => $team->id]);

    expect($account->team->is($team))->toBeTrue();
});

test('account has many balances ordered by checked_in_at desc', function () {
    $account = Account::factory()->create();

    $old = Balance::factory()->create(['account_id' => $account->id, 'checked_in_at' => now()->subDays(2)]);
    $new = Balance::factory()->create(['account_id' => $account->id, 'checked_in_at' => now()]);

    $balances = $account->balances;

    expect($balances->first()->is($new))->toBeTrue();
    expect($balances->last()->is($old))->toBeTrue();
});
