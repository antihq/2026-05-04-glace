<?php

use App\Enums\AccountType;
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

test('account type is cast to AccountType enum', function () {
    $account = Account::factory()->create(['type' => AccountType::CreditCard]);

    expect($account->type)->toBe(AccountType::CreditCard);
    expect($account->type)->toBeInstanceOf(AccountType::class);
});

test('credit_limit accessor converts cents to dollar string', function () {
    $account = Account::factory()->create();
    $account->setRawAttributes(['credit_limit' => 500000] + $account->getAttributes());
    $account->syncOriginal();

    expect($account->credit_limit)->toBe('5000.00');
});

test('credit_limit accessor returns null when not set', function () {
    $account = Account::factory()->create(['credit_limit' => null]);

    expect($account->credit_limit)->toBeNull();
});

test('credit_limit mutator converts dollar string to cents', function () {
    $account = Account::factory()->create(['credit_limit' => '5000.00']);

    expect($account->credit_limit_in_cents)->toBe(500000);
});

test('credit_limit mutator converts integer to cents', function () {
    $account = Account::factory()->create(['credit_limit' => 1000]);

    expect($account->credit_limit_in_cents)->toBe(100000);
});

test('credit_limit mutator handles null', function () {
    $account = Account::factory()->create(['credit_limit' => null]);

    expect($account->credit_limit_in_cents)->toBeNull();
});

test('credit_limit mutator handles empty string', function () {
    $account = Account::factory()->create(['credit_limit' => '']);

    expect($account->credit_limit_in_cents)->toBeNull();
});

test('credit_limit_in_cents returns raw integer', function () {
    $account = Account::factory()->create(['credit_limit' => '1234.56']);

    expect($account->credit_limit_in_cents)->toBe(123456);
});
