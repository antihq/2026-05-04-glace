<?php

use App\Models\Account;
use App\Models\Balance;
use App\Models\Checkin;
use App\Models\User;
use Livewire\Livewire;

test('checkin page can be rendered', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('checkins.create', ['current_team' => $user->currentTeam->slug]));

    $response->assertOk();
});

test('checkin page redirects guests to login', function () {
    $user = User::factory()->create();

    $response = $this->get(route('checkins.create', ['current_team' => $user->currentTeam->slug]));
    $response->assertRedirect(route('login'));
});

test('checkin shows form when no accounts', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::checkins.create')
        ->assertSee('Check In')
        ->assertSeeHtml('type="submit"');
});

test('checkin shows all account names simultaneously', function () {
    $user = User::factory()->create();
    Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Checking']);
    Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Savings']);
    Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Credit Card']);

    $this->actingAs($user);

    $html = Livewire::test('pages::checkins.create')->html();
    expect($html)->toContain('Checking');
    expect($html)->toContain('Savings');
    expect($html)->toContain('Credit Card');
});

test('checkin creates balance records on submit', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    $this->actingAs($user);

    Livewire::test('pages::checkins.create')
        ->set("balances.{$account->id}", '1500.50')
        ->call('submit')
        ->assertRedirect(route('dashboard', ['current_team' => $user->currentTeam->slug]));

    $this->assertDatabaseHas('checkins', [
        'team_id' => $user->currentTeam->id,
    ]);

    $this->assertDatabaseHas('balances', [
        'account_id' => $account->id,
    ]);

    $balance = Balance::first();
    expect($balance->amount_in_cents)->toBe(150050);
    expect($balance->checkin_id)->not->toBeNull();
});

test('checkin stores cents correctly from dollar input', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    $this->actingAs($user);

    Livewire::test('pages::checkins.create')
        ->set("balances.{$account->id}", '12.34')
        ->call('submit');

    $balance = Balance::first();
    expect($balance->amount_in_cents)->toBe(1234);
});

test('checkin only saves accounts with values entered', function () {
    $user = User::factory()->create();
    $account1 = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Checking']);
    $account2 = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Savings']);

    $this->actingAs($user);

    Livewire::test('pages::checkins.create')
        ->set("balances.{$account1->id}", '100.00')
        ->call('submit');

    expect(Balance::where('account_id', $account1->id)->count())->toBe(1);
    expect(Balance::where('account_id', $account2->id)->exists())->toBeFalse();
    expect(Checkin::where('team_id', $user->currentTeam->id)->count())->toBe(1);
});

test('checkin rejects non-numeric balance input', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    $this->actingAs($user);

    Livewire::test('pages::checkins.create')
        ->set("balances.{$account->id}", 'abc')
        ->call('submit')
        ->assertHasErrors(["balances.{$account->id}"]);
});

test('checkin submits empty checkin when no balances entered', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    $this->actingAs($user);

    Livewire::test('pages::checkins.create')
        ->call('submit')
        ->assertRedirect(route('dashboard', ['current_team' => $user->currentTeam->slug]));

    expect(Checkin::where('team_id', $user->currentTeam->id)->count())->toBe(1);
    expect(Balance::where('account_id', $account->id)->exists())->toBeFalse();
});

test('checkin saves balances for multiple accounts', function () {
    $user = User::factory()->create();
    $account1 = Account::factory()->create(['team_id' => $user->currentTeam->id]);
    $account2 = Account::factory()->create(['team_id' => $user->currentTeam->id]);
    $account3 = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    $this->actingAs($user);

    Livewire::test('pages::checkins.create')
        ->set("balances.{$account1->id}", '100.00')
        ->set("balances.{$account2->id}", '250.50')
        ->set("balances.{$account3->id}", '50.00')
        ->call('submit');

    expect(Balance::where('account_id', $account1->id)->first()->amount_in_cents)->toBe(10000);
    expect(Balance::where('account_id', $account2->id)->first()->amount_in_cents)->toBe(25050);
    expect(Balance::where('account_id', $account3->id)->first()->amount_in_cents)->toBe(5000);
    expect(Checkin::where('team_id', $user->currentTeam->id)->count())->toBe(1);
});

test('checkin handles negative balance input', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    $this->actingAs($user);

    Livewire::test('pages::checkins.create')
        ->set("balances.{$account->id}", '-500.75')
        ->call('submit');

    $balance = Balance::first();
    expect($balance->amount_in_cents)->toBe(-50075);
});

test('checkin only shows current team accounts', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'My Checking']);
    Account::factory()->create(['team_id' => $otherUser->currentTeam->id, 'name' => 'Their Savings']);

    $this->actingAs($user);

    $html = Livewire::test('pages::checkins.create')->html();
    expect($html)->toContain('My Checking');
    expect($html)->not->toContain('Their Savings');
});

test('checkin auto-negates credit card balance without credit limit', function () {
    $user = User::factory()->create();
    $account = Account::factory()->creditCard()->create(['team_id' => $user->currentTeam->id]);

    $this->actingAs($user);

    Livewire::test('pages::checkins.create')
        ->set("balances.{$account->id}", '500.00')
        ->call('submit');

    $balance = Balance::first();
    expect($balance->amount_in_cents)->toBe(-50000);
});

test('checkin calculates owed from credit limit and available credit', function () {
    $user = User::factory()->create();
    $account = Account::factory()->creditCard(1000000)->create(['team_id' => $user->currentTeam->id, 'name' => 'Visa']);

    $this->actingAs($user);

    Livewire::test('pages::checkins.create')
        ->set("balances.{$account->id}", '7500.00')
        ->call('submit');

    $balance = Balance::first();
    expect($balance->amount_in_cents)->toBe(-250000);
});

test('checkin stores zero when available credit equals credit limit', function () {
    $user = User::factory()->create();
    $account = Account::factory()->creditCard(1000000)->create(['team_id' => $user->currentTeam->id]);

    $this->actingAs($user);

    Livewire::test('pages::checkins.create')
        ->set("balances.{$account->id}", '10000.00')
        ->call('submit');

    $balance = Balance::first();
    expect($balance->amount_in_cents)->toBe(0);
});

test('checkin shows available credit label for credit card with limit', function () {
    $user = User::factory()->create();
    Account::factory()->creditCard(500000)->create(['team_id' => $user->currentTeam->id, 'name' => 'Visa']);

    $this->actingAs($user);

    $html = Livewire::test('pages::checkins.create')->html();
    expect($html)->toContain('Available credit');
});

test('checkin shows balance owed hint for credit card without limit', function () {
    $user = User::factory()->create();
    Account::factory()->creditCard()->create(['team_id' => $user->currentTeam->id, 'name' => 'Revolut']);

    $this->actingAs($user);

    $html = Livewire::test('pages::checkins.create')->html();
    expect($html)->toContain('Enter balance owed');
});
