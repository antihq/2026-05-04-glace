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
