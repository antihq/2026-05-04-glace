<?php

use App\Models\Account;
use App\Models\Balance;

test('amount accessor converts cents to dollar string', function () {
    $account = Account::factory()->create();

    $balance = (new Balance)->setRawAttributes([
        'account_id' => $account->id,
        'amount' => 123456,
    ]);

    expect($balance->amount)->toBe('1234.56');
});

test('amount accessor handles zero', function () {
    $account = Account::factory()->create();

    $balance = (new Balance)->setRawAttributes([
        'account_id' => $account->id,
        'amount' => 0,
    ]);

    expect($balance->amount)->toBe('0.00');
});

test('amount accessor handles small amounts', function () {
    $account = Account::factory()->create();

    $balance = (new Balance)->setRawAttributes([
        'account_id' => $account->id,
        'amount' => 99,
    ]);

    expect($balance->amount)->toBe('0.99');
});

test('amount accessor handles single cent', function () {
    $account = Account::factory()->create();

    $balance = (new Balance)->setRawAttributes([
        'account_id' => $account->id,
        'amount' => 1,
    ]);

    expect($balance->amount)->toBe('0.01');
});

test('amount mutator converts dollar string to cents', function () {
    $account = Account::factory()->create();

    $balance = Balance::create([
        'account_id' => $account->id,
        'amount' => '12.34',
        'checked_in_at' => now(),
    ]);

    expect($balance->amount_in_cents)->toBe(1234);
});

test('amount mutator handles integer input', function () {
    $account = Account::factory()->create();

    $balance = Balance::create([
        'account_id' => $account->id,
        'amount' => 100,
        'checked_in_at' => now(),
    ]);

    expect($balance->amount_in_cents)->toBe(10000);
});

test('amount mutator handles comma-formatted input', function () {
    $account = Account::factory()->create();

    $balance = Balance::create([
        'account_id' => $account->id,
        'amount' => '1,234.56',
        'checked_in_at' => now(),
    ]);

    expect($balance->amount_in_cents)->toBe(123456);
});

test('amount mutator handles zero', function () {
    $account = Account::factory()->create();

    $balance = Balance::create([
        'account_id' => $account->id,
        'amount' => '0',
        'checked_in_at' => now(),
    ]);

    expect($balance->amount_in_cents)->toBe(0);
});

test('amount in cents returns raw integer bypassing accessor', function () {
    $account = Account::factory()->create();

    $balance = Balance::create([
        'account_id' => $account->id,
        'amount' => '-5000.00',
        'checked_in_at' => now(),
    ]);

    expect($balance->amount_in_cents)->toBe(-500000);
});

test('checked_in_at is cast to datetime', function () {
    $account = Account::factory()->create();

    $balance = Balance::create([
        'account_id' => $account->id,
        'amount' => '10.00',
        'checked_in_at' => '2026-01-15 10:30:00',
    ]);

    expect($balance->checked_in_at)->toBeInstanceOf(DateTimeInterface::class);
});
