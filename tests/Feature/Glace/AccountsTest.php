<?php

use App\Models\Account;
use App\Models\Balance;
use App\Models\Checkin;
use App\Models\User;
use Livewire\Livewire;

test('accounts index page can be rendered', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('accounts.index', ['current_team' => $user->currentTeam->slug]));

    $response->assertOk();
});

test('accounts index page redirects guests to login', function () {
    $user = User::factory()->create();

    $response = $this->get(route('accounts.index', ['current_team' => $user->currentTeam->slug]));
    $response->assertRedirect(route('login'));
});

test('accounts index shows existing accounts', function () {
    $user = User::factory()->create();
    Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Checking']);
    Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Savings']);

    $this->actingAs($user);

    Livewire::test('pages::accounts.index')
        ->assertSee('Checking')
        ->assertSee('Savings');
});

test('accounts index are scoped to current team', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Account::factory()->create(['team_id' => $otherUser->currentTeam->id, 'name' => 'Other Team Account']);
    Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'My Account']);

    $this->actingAs($user);

    Livewire::test('pages::accounts.index')
        ->assertSee('My Account')
        ->assertDontSee('Other Team Account');
});

test('accounts create page can be rendered', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('accounts.create', ['current_team' => $user->currentTeam->slug]));

    $response->assertOk();
});

test('accounts create page redirects guests to login', function () {
    $user = User::factory()->create();

    $response = $this->get(route('accounts.create', ['current_team' => $user->currentTeam->slug]));
    $response->assertRedirect(route('login'));
});

test('accounts can be created', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::accounts.create')
        ->set('name', 'Checking')
        ->call('submit')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('accounts', [
        'team_id' => $user->currentTeam->id,
        'name' => 'Checking',
    ]);
});

test('account creation redirects to show page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::accounts.create')
        ->set('name', 'Checking')
        ->call('submit')
        ->assertHasNoErrors();

    $account = Account::where('team_id', $user->currentTeam->id)->where('name', 'Checking')->first();
    expect($account)->not->toBeNull();
});

test('account name is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::accounts.create')
        ->set('name', '')
        ->call('submit')
        ->assertHasErrors(['name']);
});

test('accounts show page can be rendered', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    $response = $this
        ->actingAs($user)
        ->get(route('accounts.show', ['current_team' => $user->currentTeam->slug, 'account' => $account->id]));

    $response->assertOk();
});

test('accounts show page redirects guests to login', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    $response = $this->get(route('accounts.show', ['current_team' => $user->currentTeam->slug, 'account' => $account->id]));
    $response->assertRedirect(route('login'));
});

test('accounts show displays account name', function () {
    $user = User::factory()->create();
    Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Checking']);

    $this->actingAs($user);

    $account = Account::where('team_id', $user->currentTeam->id)->first();

    Livewire::test('pages::accounts.show', ['account' => $account->id])
        ->assertSee('Checking');
});

test('accounts show displays balance count', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    Balance::factory()->create(['account_id' => $account->id, 'checked_in_at' => now()->subDays(2)]);
    Balance::factory()->create(['account_id' => $account->id, 'checked_in_at' => now()]);

    $this->actingAs($user);

    Livewire::test('pages::accounts.show', ['account' => $account->id])
        ->assertSee('2 balance records');
});

test('accounts show displays latest balance', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    Balance::factory()->create(['account_id' => $account->id, 'amount' => '750.00', 'checked_in_at' => now()]);

    $this->actingAs($user);

    $html = Livewire::test('pages::accounts.show', ['account' => $account->id])->html();
    expect($html)->toContain('$750.00');
});

test('accounts show shows net change when multiple balances', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    Balance::factory()->create(['account_id' => $account->id, 'amount' => '500.00', 'checked_in_at' => now()->subDays(2)]);
    Balance::factory()->create(['account_id' => $account->id, 'amount' => '800.00', 'checked_in_at' => now()]);

    $this->actingAs($user);

    $html = Livewire::test('pages::accounts.show', ['account' => $account->id])->html();
    expect($html)->toContain('Net change');
    expect($html)->toContain('+$300.00');
});

test('accounts show does not show net change with single balance', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    Balance::factory()->create(['account_id' => $account->id, 'amount' => '500.00', 'checked_in_at' => now()]);

    $this->actingAs($user);

    $html = Livewire::test('pages::accounts.show', ['account' => $account->id])->html();
    expect($html)->not->toContain('Net change');
});

test('accounts show shows balance history with per-row change', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    Balance::factory()->create(['account_id' => $account->id, 'amount' => '100.00', 'checked_in_at' => now()->subDays(2)]);
    Balance::factory()->create(['account_id' => $account->id, 'amount' => '150.00', 'checked_in_at' => now()]);

    $this->actingAs($user);

    $html = Livewire::test('pages::accounts.show', ['account' => $account->id])->html();
    expect($html)->toContain('+$50.00');
});

test('accounts can be deleted', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    $this->actingAs($user);

    Livewire::test('pages::accounts.show', ['account' => $account->id])
        ->call('delete')
        ->assertRedirect(route('accounts.index', ['current_team' => $user->currentTeam->slug]));

    $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
});

test('accounts show delete removes associated balances', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    Balance::factory()->create(['account_id' => $account->id, 'checked_in_at' => now()]);

    $this->actingAs($user);

    Livewire::test('pages::accounts.show', ['account' => $account->id])
        ->call('delete');

    expect(Balance::where('account_id', $account->id)->exists())->toBeFalse();
});

test('cannot view account from another team', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $otherUser->currentTeam->id]);

    $response = $this
        ->actingAs($user)
        ->get(route('accounts.show', ['current_team' => $user->currentTeam->slug, 'account' => $account->id]));

    $response->assertNotFound();
    expect(Account::find($account->id))->not->toBeNull();
});

test('accounts show links latest balance to checkin show page', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);
    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create([
        'account_id' => $account->id,
        'checkin_id' => $checkin->id,
        'amount' => '750.00',
        'checked_in_at' => $checkin->checked_in_at,
    ]);

    $this->actingAs($user);

    $html = Livewire::test('pages::accounts.show', ['account' => $account->id])->html();
    expect($html)->toContain(route('checkins.show', ['current_team' => $user->currentTeam->slug, 'checkin' => $checkin->id]));
});

test('account can be created with a specific type', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::accounts.create')
        ->set('name', 'My Savings')
        ->set('type', 'savings')
        ->call('submit')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('accounts', [
        'team_id' => $user->currentTeam->id,
        'name' => 'My Savings',
        'type' => 'savings',
    ]);
});

test('credit card account can be created with credit limit', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::accounts.create')
        ->set('name', 'Visa')
        ->set('type', 'credit_card')
        ->set('credit_limit', '5000.00')
        ->call('submit')
        ->assertHasNoErrors();

    $account = Account::where('team_id', $user->currentTeam->id)->where('name', 'Visa')->first();
    expect($account)->not->toBeNull();
    expect($account->type->value)->toBe('credit_card');
    expect($account->credit_limit_in_cents)->toBe(500000);
});

test('credit card account can be created without credit limit', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::accounts.create')
        ->set('name', 'Revolut')
        ->set('type', 'credit_card')
        ->call('submit')
        ->assertHasNoErrors();

    $account = Account::where('team_id', $user->currentTeam->id)->where('name', 'Revolut')->first();
    expect($account)->not->toBeNull();
    expect($account->type->value)->toBe('credit_card');
    expect($account->credit_limit_in_cents)->toBeNull();
});

test('credit limit validation rejects non-numeric input', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::accounts.create')
        ->set('name', 'Visa')
        ->set('type', 'credit_card')
        ->set('credit_limit', 'abc')
        ->call('submit')
        ->assertHasErrors(['credit_limit']);
});

test('credit limit is cleared when type is not credit card', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::accounts.create')
        ->set('name', 'Checking')
        ->set('type', 'checking')
        ->set('credit_limit', '5000.00')
        ->call('submit')
        ->assertHasNoErrors();

    $account = Account::where('team_id', $user->currentTeam->id)->where('name', 'Checking')->first();
    expect($account->credit_limit_in_cents)->toBeNull();
});
