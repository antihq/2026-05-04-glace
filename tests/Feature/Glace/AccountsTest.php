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

test('accounts show monthly balance section when multiple months of data exist', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    Balance::factory()->create(['account_id' => $account->id, 'amount' => '500.00', 'checked_in_at' => now()->subMonth()]);
    Balance::factory()->create(['account_id' => $account->id, 'amount' => '750.00', 'checked_in_at' => now()]);

    $this->actingAs($user);

    $html = Livewire::test('pages::accounts.show', ['account' => $account->id])->html();
    expect($html)->toContain('Monthly Balance');
});

test('accounts show hides monthly balance when only one month of data', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    Balance::factory()->create(['account_id' => $account->id, 'amount' => '500.00', 'checked_in_at' => now()]);

    $this->actingAs($user);

    $html = Livewire::test('pages::accounts.show', ['account' => $account->id])->html();
    expect($html)->not->toContain('Monthly Balance');
});

test('accounts show monthly balance shows per-month amounts with change', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    Balance::factory()->create(['account_id' => $account->id, 'amount' => '400.00', 'checked_in_at' => now()->subMonths(2)]);
    Balance::factory()->create(['account_id' => $account->id, 'amount' => '600.00', 'checked_in_at' => now()->subMonth()]);
    Balance::factory()->create(['account_id' => $account->id, 'amount' => '800.00', 'checked_in_at' => now()]);

    $this->actingAs($user);

    $html = Livewire::test('pages::accounts.show', ['account' => $account->id])->html();
    expect($html)->toContain('$400.00');
    expect($html)->toContain('$600.00');
    expect($html)->toContain('$800.00');
    expect($html)->toContain('+$200.00');
});

test('accounts show monthly balance shows negative change between months', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    Balance::factory()->create(['account_id' => $account->id, 'amount' => '1000.00', 'checked_in_at' => now()->subMonth()]);
    Balance::factory()->create(['account_id' => $account->id, 'amount' => '700.00', 'checked_in_at' => now()]);

    $this->actingAs($user);

    $html = Livewire::test('pages::accounts.show', ['account' => $account->id])->html();
    expect($html)->toContain('-$300.00');
    expect($html)->toContain('-30.00%');
});

test('accounts show monthly balance uses last balance in month with multiple checkins', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    Balance::factory()->create(['account_id' => $account->id, 'amount' => '50.00', 'checked_in_at' => now()->subMonth()->startOfMonth()->addDays(5)]);
    Balance::factory()->create(['account_id' => $account->id, 'amount' => '200.00', 'checked_in_at' => now()->subMonth()->endOfMonth()]);
    Balance::factory()->create(['account_id' => $account->id, 'amount' => '400.00', 'checked_in_at' => now()]);

    $this->actingAs($user);

    $html = Livewire::test('pages::accounts.show', ['account' => $account->id])->html();

    $monthlyStart = strpos($html, 'Monthly Balance');
    $monthlySection = substr($html, $monthlyStart);

    expect($monthlySection)->toContain('$200.00');
    expect($monthlySection)->not->toContain('$50.00');
});

test('accounts show monthly balance handles zero previous month balance', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    Balance::factory()->create(['account_id' => $account->id, 'amount' => '0.00', 'checked_in_at' => now()->subMonth()]);
    Balance::factory()->create(['account_id' => $account->id, 'amount' => '500.00', 'checked_in_at' => now()]);

    $this->actingAs($user);

    $html = Livewire::test('pages::accounts.show', ['account' => $account->id])->html();
    expect($html)->toContain('$500.00');
});

test('accounts index shows type badge for each account', function () {
    $user = User::factory()->create();
    Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Checking', 'type' => 'checking']);
    Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Visa', 'type' => 'credit_card']);

    $this->actingAs($user);

    Livewire::test('pages::accounts.index')
        ->assertSee('Checking')
        ->assertSee('Credit Card');
});

test('accounts index shows current balance column', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Savings']);
    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create(['account_id' => $account->id, 'checkin_id' => $checkin->id, 'amount' => '2500.00', 'checked_in_at' => $checkin->checked_in_at]);

    $this->actingAs($user);

    $html = Livewire::test('pages::accounts.index')->html();
    expect($html)->toContain('$2,500.00');
});

test('accounts index shows last check-in date', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);
    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create(['account_id' => $account->id, 'checkin_id' => $checkin->id, 'amount' => '100.00', 'checked_in_at' => $checkin->checked_in_at]);

    $this->actingAs($user);

    Livewire::test('pages::accounts.index')
        ->assertSee($checkin->checked_in_at->format('M j, Y'));
});

test('accounts index shows dash when no balance recorded', function () {
    $user = User::factory()->create();
    Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Empty Account']);

    $this->actingAs($user);

    Livewire::test('pages::accounts.index')
        ->assertSee('Empty Account')
        ->assertSeeHtml('&mdash;');
});

test('accounts index shows empty state when no accounts', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::accounts.index')
        ->assertSee('No accounts');
});

test('accounts show displays account type', function () {
    $user = User::factory()->create();
    Account::factory()->create(['team_id' => $user->currentTeam->id, 'type' => 'savings']);

    $this->actingAs($user);

    $account = Account::where('team_id', $user->currentTeam->id)->first();

    Livewire::test('pages::accounts.show', ['account' => $account->id])
        ->assertSee('Savings');
});

test('accounts show displays credit limit for credit card accounts', function () {
    $user = User::factory()->create();
    Account::factory()->creditCard(500000)->create(['team_id' => $user->currentTeam->id, 'name' => 'Visa']);

    $this->actingAs($user);

    $account = Account::where('team_id', $user->currentTeam->id)->first();

    $html = Livewire::test('pages::accounts.show', ['account' => $account->id])->html();
    expect($html)->toContain('Credit limit');
    expect($html)->toContain('$5,000.00');
});

test('accounts show does not display credit limit for non-credit-card accounts', function () {
    $user = User::factory()->create();
    Account::factory()->create(['team_id' => $user->currentTeam->id, 'type' => 'checking']);

    $this->actingAs($user);

    $account = Account::where('team_id', $user->currentTeam->id)->first();

    $html = Livewire::test('pages::accounts.show', ['account' => $account->id])->html();
    expect($html)->not->toContain('Credit limit');
});

test('accounts show net change includes date range', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    $early = now()->subMonths(3);
    Balance::factory()->create(['account_id' => $account->id, 'amount' => '200.00', 'checked_in_at' => $early]);
    Balance::factory()->create(['account_id' => $account->id, 'amount' => '800.00', 'checked_in_at' => now()]);

    $this->actingAs($user);

    $html = Livewire::test('pages::accounts.show', ['account' => $account->id])->html();
    expect($html)->toContain($early->format('M j, Y'));
    expect($html)->toContain('+$600.00');
});
