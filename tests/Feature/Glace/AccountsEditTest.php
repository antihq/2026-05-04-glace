<?php

use App\Models\Account;
use App\Models\User;
use Livewire\Livewire;

test('accounts.edit page can be rendered', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    $response = $this
        ->actingAs($user)
        ->get(route('accounts.edit', ['current_team' => $user->currentTeam->slug, 'account' => $account->id]));

    $response->assertOk();
});

test('accounts.edit redirects guests to login', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    $response = $this->get(route('accounts.edit', ['current_team' => $user->currentTeam->slug, 'account' => $account->id]));
    $response->assertRedirect(route('login'));
});

test('accounts.edit returns 404 for other team account', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherAccount = Account::factory()->create(['team_id' => $otherUser->currentTeam->id]);

    $response = $this
        ->actingAs($user)
        ->get(route('accounts.edit', ['current_team' => $user->currentTeam->slug, 'account' => $otherAccount->id]));

    $response->assertNotFound();
});

test('accounts.edit pre-populates account name', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Checking']);

    $this->actingAs($user);

    Livewire::test('pages::accounts.edit', ['account' => $account->id])
        ->assertSet('name', 'Checking');
});

test('accounts.edit updates account name', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Checking']);

    $this->actingAs($user);

    Livewire::test('pages::accounts.edit', ['account' => $account->id])
        ->set('name', 'Savings')
        ->call('update')
        ->assertRedirect(route('accounts.show', ['current_team' => $user->currentTeam->slug, 'account' => $account->id]));

    $account->refresh();
    expect($account->name)->toBe('Savings');
});

test('accounts.edit validates name is required', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    $this->actingAs($user);

    Livewire::test('pages::accounts.edit', ['account' => $account->id])
        ->set('name', '')
        ->call('update')
        ->assertHasErrors(['name']);
});

test('accounts.edit pre-populates account type', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id, 'type' => 'savings']);

    $this->actingAs($user);

    Livewire::test('pages::accounts.edit', ['account' => $account->id])
        ->assertSet('type', 'savings');
});

test('accounts.edit pre-populates credit limit for credit card', function () {
    $user = User::factory()->create();
    $account = Account::factory()->creditCard(500000)->create(['team_id' => $user->currentTeam->id, 'name' => 'Visa']);

    $this->actingAs($user);

    Livewire::test('pages::accounts.edit', ['account' => $account->id])
        ->assertSet('credit_limit', '5000.00');
});

test('accounts.edit updates account type', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id, 'type' => 'checking']);

    $this->actingAs($user);

    Livewire::test('pages::accounts.edit', ['account' => $account->id])
        ->set('type', 'savings')
        ->call('update')
        ->assertHasNoErrors();

    $account->refresh();
    expect($account->type->value)->toBe('savings');
});

test('accounts.edit updates credit limit', function () {
    $user = User::factory()->create();
    $account = Account::factory()->creditCard(500000)->create(['team_id' => $user->currentTeam->id, 'name' => 'Visa']);

    $this->actingAs($user);

    Livewire::test('pages::accounts.edit', ['account' => $account->id])
        ->set('credit_limit', '10000.00')
        ->call('update')
        ->assertHasNoErrors();

    $account->refresh();
    expect($account->credit_limit_in_cents)->toBe(1000000);
});

test('accounts.edit clears credit limit when type changes away from credit card', function () {
    $user = User::factory()->create();
    $account = Account::factory()->creditCard(500000)->create(['team_id' => $user->currentTeam->id, 'name' => 'Visa']);

    $this->actingAs($user);

    Livewire::test('pages::accounts.edit', ['account' => $account->id])
        ->set('type', 'checking')
        ->set('credit_limit', '5000.00')
        ->call('update')
        ->assertHasNoErrors();

    $account->refresh();
    expect($account->credit_limit_in_cents)->toBeNull();
});
