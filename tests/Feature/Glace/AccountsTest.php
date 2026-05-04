<?php

use App\Models\Account;
use App\Models\User;
use Livewire\Livewire;

test('accounts page can be rendered', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('accounts', ['current_team' => $user->currentTeam->slug]));

    $response->assertOk();
});

test('accounts page redirects guests to login', function () {
    $user = User::factory()->create();

    $response = $this->get(route('accounts', ['current_team' => $user->currentTeam->slug]));
    $response->assertRedirect(route('login'));
});

test('accounts page shows existing accounts', function () {
    $user = User::factory()->create();
    Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Checking']);
    Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Savings']);

    $this->actingAs($user);

    Livewire::test('pages::accounts')
        ->assertSee('Checking')
        ->assertSee('Savings');
});

test('accounts are scoped to current team', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Account::factory()->create(['team_id' => $otherUser->currentTeam->id, 'name' => 'Other Team Account']);
    Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'My Account']);

    $this->actingAs($user);

    Livewire::test('pages::accounts')
        ->assertSee('My Account')
        ->assertDontSee('Other Team Account');
});

test('accounts can be added', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::accounts')
        ->set('newAccountName', 'Checking')
        ->call('addAccount')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('accounts', [
        'team_id' => $user->currentTeam->id,
        'name' => 'Checking',
    ]);
});

test('account name is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::accounts')
        ->set('newAccountName', '')
        ->call('addAccount')
        ->assertHasErrors(['newAccountName']);
});

test('accounts can be deleted', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    $this->actingAs($user);

    Livewire::test('pages::accounts')
        ->call('deleteAccount', $account->id)
        ->assertHasNoErrors();

    $this->assertSoftDeleted('accounts', ['id' => $account->id]);
});

test('cannot delete account from another team', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $otherUser->currentTeam->id]);

    $this->actingAs($user);

    Livewire::test('pages::accounts')
        ->call('deleteAccount', $account->id)
        ->assertHasNoErrors();

    expect(Account::find($account->id))->not->toBeNull();
});

test('accounts auto-increment sort order', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::accounts')
        ->set('newAccountName', 'Checking')
        ->call('addAccount');

    Livewire::test('pages::accounts')
        ->set('newAccountName', 'Savings')
        ->call('addAccount');

    Livewire::test('pages::accounts')
        ->set('newAccountName', 'Credit Card')
        ->call('addAccount');

    $accounts = Account::where('team_id', $user->currentTeam->id)->ordered()->pluck('sort_order');

    expect($accounts->toArray())->toBe([1, 2, 3]);
});

test('accounts sort order considers soft deleted accounts', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::accounts')
        ->set('newAccountName', 'Checking')
        ->call('addAccount');

    $checking = Account::where('team_id', $user->currentTeam->id)->first();
    Livewire::test('pages::accounts')
        ->call('deleteAccount', $checking->id);

    Livewire::test('pages::accounts')
        ->set('newAccountName', 'Savings')
        ->call('addAccount');

    $savings = Account::where('team_id', $user->currentTeam->id)->where('name', 'Savings')->first();
    expect($savings->sort_order)->toBe(2);
});
