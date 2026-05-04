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

test('scope ordered sorts by sort_order then name', function () {
    $team = Team::factory()->create();

    Account::factory()->create(['team_id' => $team->id, 'name' => 'Savings', 'sort_order' => 1]);
    Account::factory()->create(['team_id' => $team->id, 'name' => 'Alpha', 'sort_order' => 2]);
    Account::factory()->create(['team_id' => $team->id, 'name' => 'Checking', 'sort_order' => 1]);

    $accounts = Account::where('team_id', $team->id)->ordered()->get();

    expect($accounts->pluck('name')->toArray())->toBe(['Checking', 'Savings', 'Alpha']);
});

test('account can be soft deleted', function () {
    $account = Account::factory()->create();

    $account->delete();

    expect(Account::find($account->id))->toBeNull();
    expect(Account::withTrashed()->find($account->id))->not->toBeNull();
});
