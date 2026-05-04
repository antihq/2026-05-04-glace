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
        ->get(route('checkin', ['current_team' => $user->currentTeam->slug]));

    $response->assertOk();
});

test('checkin page redirects guests to login', function () {
    $user = User::factory()->create();

    $response = $this->get(route('checkin', ['current_team' => $user->currentTeam->slug]));
    $response->assertRedirect(route('login'));
});

test('checkin shows empty state when no accounts', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::checkin')
        ->assertSee('No accounts yet');
});

test('checkin shows first account name', function () {
    $user = User::factory()->create();
    Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Checking', 'sort_order' => 1]);
    Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Savings', 'sort_order' => 2]);

    $this->actingAs($user);

    Livewire::test('pages::checkin')
        ->assertSee('Checking')
        ->assertSee('1 of 2');
});

test('checkin advances to next account', function () {
    $user = User::factory()->create();
    $account1 = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Checking', 'sort_order' => 1]);
    $account2 = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Savings', 'sort_order' => 2]);

    $this->actingAs($user);

    Livewire::test('pages::checkin')
        ->set("balances.{$account1->id}", '100.00')
        ->call('next')
        ->assertSee('Savings')
        ->assertSee('2 of 2');
});

test('checkin navigates back', function () {
    $user = User::factory()->create();
    $account1 = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Checking', 'sort_order' => 1]);
    $account2 = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Savings', 'sort_order' => 2]);

    $this->actingAs($user);

    Livewire::test('pages::checkin')
        ->set("balances.{$account1->id}", '100.00')
        ->call('next')
        ->assertSee('Savings')
        ->call('back')
        ->assertSee('Checking');
});

test('checkin creates balance records on finish', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    $this->actingAs($user);

    Livewire::test('pages::checkin')
        ->set("balances.{$account->id}", '1500.50')
        ->call('next')
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

    Livewire::test('pages::checkin')
        ->set("balances.{$account->id}", '12.34')
        ->call('next');

    $balance = Balance::first();
    expect($balance->amount_in_cents)->toBe(1234);
});

test('checkin skips account without saving balance', function () {
    $user = User::factory()->create();
    $account1 = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Checking', 'sort_order' => 1]);
    $account2 = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Savings', 'sort_order' => 2]);

    $this->actingAs($user);

    Livewire::test('pages::checkin')
        ->call('skip')
        ->assertSee('Savings');

    expect(Balance::where('account_id', $account1->id)->exists())->toBeFalse();
});

test('checkin only saves accounts with values entered', function () {
    $user = User::factory()->create();
    $account1 = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Checking', 'sort_order' => 1]);
    $account2 = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Savings', 'sort_order' => 2]);

    $this->actingAs($user);

    Livewire::test('pages::checkin')
        ->set("balances.{$account1->id}", '100.00')
        ->call('next')
        ->call('skip');

    expect(Balance::where('account_id', $account1->id)->count())->toBe(1);
    expect(Balance::where('account_id', $account2->id)->exists())->toBeFalse();
    expect(Checkin::where('team_id', $user->currentTeam->id)->count())->toBe(1);
});

test('checkin rejects non-numeric balance input', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    $this->actingAs($user);

    Livewire::test('pages::checkin')
        ->set("balances.{$account->id}", 'abc')
        ->call('next')
        ->assertHasErrors(["balances.{$account->id}"]);
});

test('checkin skip on last account creates checkin without balance', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    $this->actingAs($user);

    Livewire::test('pages::checkin')
        ->call('skip')
        ->assertRedirect(route('dashboard', ['current_team' => $user->currentTeam->slug]));

    expect(Checkin::where('team_id', $user->currentTeam->id)->count())->toBe(1);
    expect(Balance::where('account_id', $account->id)->exists())->toBeFalse();
});

test('checkin shows Next on non-last account', function () {
    $user = User::factory()->create();
    Account::factory()->create(['team_id' => $user->currentTeam->id, 'sort_order' => 1]);
    Account::factory()->create(['team_id' => $user->currentTeam->id, 'sort_order' => 2]);

    $this->actingAs($user);

    Livewire::test('pages::checkin')
        ->assertSee('Next')
        ->assertDontSee('Finish');
});

test('checkin shows Finish on last account', function () {
    $user = User::factory()->create();
    Account::factory()->create(['team_id' => $user->currentTeam->id, 'sort_order' => 1]);
    Account::factory()->create(['team_id' => $user->currentTeam->id, 'sort_order' => 2]);

    $this->actingAs($user);

    $account1 = Account::where('team_id', $user->currentTeam->id)->ordered()->first();

    $html = Livewire::test('pages::checkin')
        ->set("balances.{$account1->id}", '100')
        ->call('next')
        ->html();

    expect($html)->toContain('Finish');
    expect(substr_count($html, '>Next<'))->toBe(0);
});

test('checkin shows Back button after advancing', function () {
    $user = User::factory()->create();
    Account::factory()->create(['team_id' => $user->currentTeam->id, 'sort_order' => 1]);
    Account::factory()->create(['team_id' => $user->currentTeam->id, 'sort_order' => 2]);

    $this->actingAs($user);

    $account1 = Account::where('team_id', $user->currentTeam->id)->ordered()->first();

    Livewire::test('pages::checkin')
        ->set("balances.{$account1->id}", '100')
        ->call('next')
        ->assertSee('Back');
});

test('checkin hides Back button on first account', function () {
    $user = User::factory()->create();
    Account::factory()->create(['team_id' => $user->currentTeam->id, 'sort_order' => 1]);
    Account::factory()->create(['team_id' => $user->currentTeam->id, 'sort_order' => 2]);

    $this->actingAs($user);

    $html = Livewire::test('pages::checkin')->html();
    expect(substr_count($html, '>Back<'))->toBe(0);
});

test('checkin skip clears previously entered balance', function () {
    $user = User::factory()->create();
    $account1 = Account::factory()->create(['team_id' => $user->currentTeam->id, 'sort_order' => 1]);
    $account2 = Account::factory()->create(['team_id' => $user->currentTeam->id, 'sort_order' => 2]);

    $this->actingAs($user);

    Livewire::test('pages::checkin')
        ->set("balances.{$account1->id}", '100.00')
        ->call('skip')
        ->call('next');

    expect(Balance::where('account_id', $account1->id)->exists())->toBeFalse();
    expect(Checkin::where('team_id', $user->currentTeam->id)->count())->toBe(1);
});
