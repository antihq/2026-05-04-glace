<?php

use App\Models\Account;
use App\Models\Balance;
use App\Models\Checkin;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $user = User::factory()->create();

    $response = $this->get(route('dashboard', ['current_team' => $user->currentTeam->slug]));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard', ['current_team' => $user->currentTeam->slug]));

    $response->assertOk();
});

test('dashboard shows data structure when no accounts', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::dashboard')
        ->assertSee('Total')
        ->assertSee('$0.00')
        ->assertSee('0 accounts tracked')
        ->assertSee('Account Balances');
});

test('dashboard shows data structure when accounts exist but no check-ins', function () {
    $user = User::factory()->create();
    Account::factory()->create(['team_id' => $user->currentTeam->id]);

    $this->actingAs($user);

    Livewire::test('pages::dashboard')
        ->assertSee('Total')
        ->assertSee('$0.00')
        ->assertSee('1 account tracked')
        ->assertSee('Account Balances');
});

test('dashboard shows balances after check-in', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Checking']);
    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create([
        'account_id' => $account->id,
        'checkin_id' => $checkin->id,
        'amount' => '1500.00',
        'checked_in_at' => $checkin->checked_in_at,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::dashboard')
        ->assertSee('Checking')
        ->assertSee('$1,500.00');
});

test('dashboard computes total from all accounts', function () {
    $user = User::factory()->create();
    $checking = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Checking']);
    $savings = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Savings']);
    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create([
        'account_id' => $checking->id,
        'checkin_id' => $checkin->id,
        'amount' => '1000.00',
        'checked_in_at' => $checkin->checked_in_at,
    ]);

    Balance::factory()->create([
        'account_id' => $savings->id,
        'checkin_id' => $checkin->id,
        'amount' => '500.50',
        'checked_in_at' => $checkin->checked_in_at,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::dashboard')
        ->assertSee('$1,500.50');
});

test('dashboard shows positive delta from previous check-in', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    $previousCheckin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()->subDay()]);
    $latestCheckin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create([
        'account_id' => $account->id,
        'checkin_id' => $previousCheckin->id,
        'amount' => '800.00',
        'checked_in_at' => $previousCheckin->checked_in_at,
    ]);

    Balance::factory()->create([
        'account_id' => $account->id,
        'checkin_id' => $latestCheckin->id,
        'amount' => '1000.00',
        'checked_in_at' => $latestCheckin->checked_in_at,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::dashboard')
        ->assertSee('+$200.00');
});

test('dashboard shows negative delta from previous check-in', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    $previousCheckin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()->subDay()]);
    $latestCheckin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create([
        'account_id' => $account->id,
        'checkin_id' => $previousCheckin->id,
        'amount' => '1000.00',
        'checked_in_at' => $previousCheckin->checked_in_at,
    ]);

    Balance::factory()->create([
        'account_id' => $account->id,
        'checkin_id' => $latestCheckin->id,
        'amount' => '750.00',
        'checked_in_at' => $latestCheckin->checked_in_at,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::dashboard')
        ->assertSee('-$250.00');
});

test('dashboard shows check-in time when some accounts were skipped', function () {
    $user = User::factory()->create();
    $checking = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Checking']);
    $savings = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Savings']);
    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create([
        'account_id' => $checking->id,
        'checkin_id' => $checkin->id,
        'amount' => '1000.00',
        'checked_in_at' => $checkin->checked_in_at,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::dashboard')
        ->assertSee('Last check-in')
        ->assertSee('Checking')
        ->assertSee('$1,000.00')
        ->assertSee('Savings');
});

test('dashboard shows dash for account that was never balanced', function () {
    $user = User::factory()->create();
    $checking = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Checking']);
    $savings = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Savings']);
    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create([
        'account_id' => $checking->id,
        'checkin_id' => $checkin->id,
        'amount' => '500.00',
        'checked_in_at' => $checkin->checked_in_at,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::dashboard')
        ->assertSee('Checking')
        ->assertSee('$500.00')
        ->assertSee('Savings')
        ->assertSeeHtml('&mdash;');
});

test('dashboard carries forward previous balance for skipped account', function () {
    $user = User::factory()->create();
    $checking = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Checking']);
    $savings = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Savings']);

    $previousCheckin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()->subDay()]);
    $latestCheckin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create([
        'account_id' => $checking->id,
        'checkin_id' => $previousCheckin->id,
        'amount' => '1000.00',
        'checked_in_at' => $previousCheckin->checked_in_at,
    ]);

    Balance::factory()->create([
        'account_id' => $savings->id,
        'checkin_id' => $previousCheckin->id,
        'amount' => '500.00',
        'checked_in_at' => $previousCheckin->checked_in_at,
    ]);

    Balance::factory()->create([
        'account_id' => $checking->id,
        'checkin_id' => $latestCheckin->id,
        'amount' => '1100.00',
        'checked_in_at' => $latestCheckin->checked_in_at,
    ]);

    $this->actingAs($user);

    $html = Livewire::test('pages::dashboard')->html();
    expect($html)->toContain('$1,100.00');
    expect($html)->toContain('$500.00');
    expect($html)->toContain('$1,600.00');
});

test('dashboard total includes carried forward balances', function () {
    $user = User::factory()->create();
    $checking = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Checking']);
    $savings = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Savings']);

    $previousCheckin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()->subDay()]);
    $latestCheckin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create([
        'account_id' => $savings->id,
        'checkin_id' => $previousCheckin->id,
        'amount' => '2000.00',
        'checked_in_at' => $previousCheckin->checked_in_at,
    ]);

    Balance::factory()->create([
        'account_id' => $checking->id,
        'checkin_id' => $latestCheckin->id,
        'amount' => '500.00',
        'checked_in_at' => $latestCheckin->checked_in_at,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::dashboard')
        ->assertSee('$2,500.00');
});

test('dashboard computes delta correctly when account was skipped', function () {
    $user = User::factory()->create();
    $checking = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Checking']);
    $savings = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Savings']);

    $previousCheckin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()->subDay()]);
    $latestCheckin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create([
        'account_id' => $checking->id,
        'checkin_id' => $previousCheckin->id,
        'amount' => '800.00',
        'checked_in_at' => $previousCheckin->checked_in_at,
    ]);

    Balance::factory()->create([
        'account_id' => $savings->id,
        'checkin_id' => $previousCheckin->id,
        'amount' => '500.00',
        'checked_in_at' => $previousCheckin->checked_in_at,
    ]);

    Balance::factory()->create([
        'account_id' => $checking->id,
        'checkin_id' => $latestCheckin->id,
        'amount' => '1000.00',
        'checked_in_at' => $latestCheckin->checked_in_at,
    ]);

    $this->actingAs($user);

    $previousTotal = 80000 + 50000;
    $currentTotal = 100000 + 50000;
    $expectedDelta = $currentTotal - $previousTotal;

    Livewire::test('pages::dashboard')
        ->assertSee('+$'.number_format($expectedDelta / 100, 2));
});

test('dashboard does not show other teams data', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $otherAccount = Account::factory()->create(['team_id' => $otherUser->currentTeam->id, 'name' => 'Secret Account']);
    $otherCheckin = Checkin::factory()->create(['team_id' => $otherUser->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create([
        'account_id' => $otherAccount->id,
        'checkin_id' => $otherCheckin->id,
        'amount' => '9999.00',
        'checked_in_at' => $otherCheckin->checked_in_at,
    ]);

    $this->actingAs($user);

    $html = Livewire::test('pages::dashboard')->html();
    expect($html)->not->toContain('Secret Account');
    expect($html)->not->toContain('$9,999.00');
});

test('dashboard shows dash for change when balance unchanged between checkins', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    $previousCheckin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()->subDay()]);
    $latestCheckin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create([
        'account_id' => $account->id,
        'checkin_id' => $previousCheckin->id,
        'amount' => '500.00',
        'checked_in_at' => $previousCheckin->checked_in_at,
    ]);

    Balance::factory()->create([
        'account_id' => $account->id,
        'checkin_id' => $latestCheckin->id,
        'amount' => '500.00',
        'checked_in_at' => $latestCheckin->checked_in_at,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::dashboard')
        ->assertSeeHtml('&mdash;');
});

test('dashboard handles negative balances', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Credit Card']);
    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create([
        'account_id' => $account->id,
        'checkin_id' => $checkin->id,
        'amount' => '-250.75',
        'checked_in_at' => $checkin->checked_in_at,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::dashboard')
        ->assertSee('-$250.75');
});

test('dashboard shows checkin count in metadata strip', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()->subDays(2)]);
    Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()->subDay()]);

    $latestCheckin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create([
        'account_id' => $account->id,
        'checkin_id' => $latestCheckin->id,
        'amount' => '100.00',
        'checked_in_at' => $latestCheckin->checked_in_at,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::dashboard')
        ->assertSee('3 check-ins');
});

test('dashboard shows percentage change in summary grid', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    $previousCheckin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()->subDay()]);
    $latestCheckin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create([
        'account_id' => $account->id,
        'checkin_id' => $previousCheckin->id,
        'amount' => '1000.00',
        'checked_in_at' => $previousCheckin->checked_in_at,
    ]);

    Balance::factory()->create([
        'account_id' => $account->id,
        'checkin_id' => $latestCheckin->id,
        'amount' => '1500.00',
        'checked_in_at' => $latestCheckin->checked_in_at,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::dashboard')
        ->assertSee('+50.00%');
});

test('dashboard shows per-account percentage change in table', function () {
    $user = User::factory()->create();
    $checking = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Checking']);
    $savings = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Savings']);

    $previousCheckin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()->subDay()]);
    $latestCheckin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create(['account_id' => $checking->id, 'checkin_id' => $previousCheckin->id, 'amount' => '200.00', 'checked_in_at' => $previousCheckin->checked_in_at]);
    Balance::factory()->create(['account_id' => $savings->id, 'checkin_id' => $previousCheckin->id, 'amount' => '500.00', 'checked_in_at' => $previousCheckin->checked_in_at]);
    Balance::factory()->create(['account_id' => $checking->id, 'checkin_id' => $latestCheckin->id, 'amount' => '250.00', 'checked_in_at' => $latestCheckin->checked_in_at]);
    Balance::factory()->create(['account_id' => $savings->id, 'checkin_id' => $latestCheckin->id, 'amount' => '400.00', 'checked_in_at' => $latestCheckin->checked_in_at]);

    $this->actingAs($user);

    $html = Livewire::test('pages::dashboard')->html();
    expect($html)->toContain('+25.00%');
    expect($html)->toContain('-20.00%');
});

test('dashboard always shows previous balance column', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);
    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create([
        'account_id' => $account->id,
        'checkin_id' => $checkin->id,
        'amount' => '100.00',
        'checked_in_at' => $checkin->checked_in_at,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::dashboard')
        ->assertSee('Previous');
});

test('dashboard handles zero previous total without division error', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    $previousCheckin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()->subDay()]);
    $latestCheckin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create([
        'account_id' => $account->id,
        'checkin_id' => $previousCheckin->id,
        'amount' => '0.00',
        'checked_in_at' => $previousCheckin->checked_in_at,
    ]);

    Balance::factory()->create([
        'account_id' => $account->id,
        'checkin_id' => $latestCheckin->id,
        'amount' => '500.00',
        'checked_in_at' => $latestCheckin->checked_in_at,
    ]);

    $this->actingAs($user);

    $html = Livewire::test('pages::dashboard')->html();
    expect($html)->toContain('$500.00');
});

test('dashboard shows Overview heading', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::dashboard')
        ->assertSee('Overview');
});

test('dashboard always shows New Check-in button', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $html = Livewire::test('pages::dashboard')->html();
    expect($html)->toContain(route('checkins.create', ['current_team' => $user->currentTeam->slug]));
});

test('dashboard links to accounts index from description list', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);
    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create([
        'account_id' => $account->id,
        'checkin_id' => $checkin->id,
        'amount' => '100.00',
        'checked_in_at' => $checkin->checked_in_at,
    ]);

    $this->actingAs($user);

    $html = Livewire::test('pages::dashboard')->html();
    expect($html)->toContain(route('accounts.index', ['current_team' => $user->currentTeam->slug]));
});

test('dashboard links to latest checkin show page', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);
    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create([
        'account_id' => $account->id,
        'checkin_id' => $checkin->id,
        'amount' => '100.00',
        'checked_in_at' => $checkin->checked_in_at,
    ]);

    $this->actingAs($user);

    $html = Livewire::test('pages::dashboard')->html();
    expect($html)->toContain(route('checkins.show', ['current_team' => $user->currentTeam->slug, 'checkin' => $checkin->id]));
});

test('dashboard links to checkins index from total check-ins', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);
    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create([
        'account_id' => $account->id,
        'checkin_id' => $checkin->id,
        'amount' => '100.00',
        'checked_in_at' => $checkin->checked_in_at,
    ]);

    $this->actingAs($user);

    $html = Livewire::test('pages::dashboard')->html();
    expect($html)->toContain(route('checkins.index', ['current_team' => $user->currentTeam->slug]));
});

test('dashboard links account rows to accounts show page', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Checking']);
    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create([
        'account_id' => $account->id,
        'checkin_id' => $checkin->id,
        'amount' => '100.00',
        'checked_in_at' => $checkin->checked_in_at,
    ]);

    $this->actingAs($user);

    $html = Livewire::test('pages::dashboard')->html();
    expect($html)->toContain(route('accounts.show', ['current_team' => $user->currentTeam->slug, 'account' => $account->id]));
});

test('dashboard monthly overview shows heading when balances exist', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);
    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create([
        'account_id' => $account->id,
        'checkin_id' => $checkin->id,
        'amount' => '100.00',
        'checked_in_at' => $checkin->checked_in_at,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::dashboard')
        ->assertSee('Monthly Overview');
});

test('dashboard monthly overview is hidden when no balances exist', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $html = Livewire::test('pages::dashboard')->html();
    expect($html)->not->toContain('Monthly Overview');
});

test('dashboard monthly overview shows totals per month with change', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    $month1 = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()->subMonths(2)]);
    $month2 = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()->subMonth()]);
    $month3 = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create(['account_id' => $account->id, 'checkin_id' => $month1->id, 'amount' => '500.00', 'checked_in_at' => $month1->checked_in_at]);
    Balance::factory()->create(['account_id' => $account->id, 'checkin_id' => $month2->id, 'amount' => '750.00', 'checked_in_at' => $month2->checked_in_at]);
    Balance::factory()->create(['account_id' => $account->id, 'checkin_id' => $month3->id, 'amount' => '900.00', 'checked_in_at' => $month3->checked_in_at]);

    $this->actingAs($user);

    $html = Livewire::test('pages::dashboard')->html();
    expect($html)->toContain('$500.00');
    expect($html)->toContain('$750.00');
    expect($html)->toContain('$900.00');
    expect($html)->toContain('+$250.00');
    expect($html)->toContain('+$150.00');
});

test('dashboard monthly overview shows negative change between months', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    $month1 = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()->subMonth()]);
    $month2 = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create(['account_id' => $account->id, 'checkin_id' => $month1->id, 'amount' => '1000.00', 'checked_in_at' => $month1->checked_in_at]);
    Balance::factory()->create(['account_id' => $account->id, 'checkin_id' => $month2->id, 'amount' => '750.00', 'checked_in_at' => $month2->checked_in_at]);

    $this->actingAs($user);

    $html = Livewire::test('pages::dashboard')->html();
    expect($html)->toContain('-$250.00');
    expect($html)->toContain('-25.00%');
});

test('dashboard monthly overview uses last balance per account per month', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'AcctLast']);

    $early = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()->subMonth()->startOfMonth()->addDays(5)]);
    $late = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()->subMonth()->endOfMonth()]);

    Balance::factory()->create(['account_id' => $account->id, 'checkin_id' => $early->id, 'amount' => '100.00', 'checked_in_at' => $early->checked_in_at]);
    Balance::factory()->create(['account_id' => $account->id, 'checkin_id' => $late->id, 'amount' => '200.00', 'checked_in_at' => $late->checked_in_at]);

    $this->actingAs($user);

    $html = Livewire::test('pages::dashboard')->html();

    $monthlyOverviewStart = strpos($html, 'Monthly Overview');
    $monthlySection = substr($html, $monthlyOverviewStart);

    expect($monthlySection)->toContain('$200.00');
    expect($monthlySection)->not->toContain('$100.00');
});

test('dashboard monthly overview sums across multiple accounts', function () {
    $user = User::factory()->create();
    $checking = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Checking']);
    $savings = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Savings']);

    $month1 = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()->subMonth()]);
    $month2 = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create(['account_id' => $checking->id, 'checkin_id' => $month1->id, 'amount' => '300.00', 'checked_in_at' => $month1->checked_in_at]);
    Balance::factory()->create(['account_id' => $savings->id, 'checkin_id' => $month1->id, 'amount' => '700.00', 'checked_in_at' => $month1->checked_in_at]);
    Balance::factory()->create(['account_id' => $checking->id, 'checkin_id' => $month2->id, 'amount' => '400.00', 'checked_in_at' => $month2->checked_in_at]);
    Balance::factory()->create(['account_id' => $savings->id, 'checkin_id' => $month2->id, 'amount' => '800.00', 'checked_in_at' => $month2->checked_in_at]);

    $this->actingAs($user);

    $html = Livewire::test('pages::dashboard')->html();
    expect($html)->toContain('$1,000.00');
    expect($html)->toContain('$1,200.00');
    expect($html)->toContain('+$200.00');
});

test('dashboard monthly overview handles zero previous month total', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    $month1 = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()->subMonth()]);
    $month2 = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create(['account_id' => $account->id, 'checkin_id' => $month1->id, 'amount' => '0.00', 'checked_in_at' => $month1->checked_in_at]);
    Balance::factory()->create(['account_id' => $account->id, 'checkin_id' => $month2->id, 'amount' => '500.00', 'checked_in_at' => $month2->checked_in_at]);

    $this->actingAs($user);

    $html = Livewire::test('pages::dashboard')->html();
    expect($html)->toContain('$500.00');
});

test('dashboard monthly overview does not show other teams data', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherAccount = Account::factory()->create(['team_id' => $otherUser->currentTeam->id, 'name' => 'Secret']);
    $otherCheckin = Checkin::factory()->create(['team_id' => $otherUser->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create(['account_id' => $otherAccount->id, 'checkin_id' => $otherCheckin->id, 'amount' => '9999.00', 'checked_in_at' => $otherCheckin->checked_in_at]);

    $this->actingAs($user);

    $html = Livewire::test('pages::dashboard')->html();
    expect($html)->not->toContain('Secret');
    expect($html)->not->toContain('$9,999.00');
});
