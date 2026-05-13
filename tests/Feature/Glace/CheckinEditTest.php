<?php

use App\Models\Account;
use App\Models\Balance;
use App\Models\Checkin;
use App\Models\User;
use Livewire\Livewire;

test('checkin edit page can be rendered', function () {
    $user = User::factory()->create();
    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    $response = $this
        ->actingAs($user)
        ->get(route('checkins.edit', ['current_team' => $user->currentTeam->slug, 'checkin' => $checkin->id]));

    $response->assertOk();
});

test('checkin edit page redirects guests to login', function () {
    $user = User::factory()->create();
    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    $response = $this->get(route('checkins.edit', ['current_team' => $user->currentTeam->slug, 'checkin' => $checkin->id]));
    $response->assertRedirect(route('login'));
});

test('checkin edit returns 404 for other team checkin', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherCheckin = Checkin::factory()->create(['team_id' => $otherUser->currentTeam->id, 'checked_in_at' => now()]);

    $response = $this
        ->actingAs($user)
        ->get(route('checkins.edit', ['current_team' => $user->currentTeam->slug, 'checkin' => $otherCheckin->id]));

    $response->assertNotFound();
});

test('checkin edit pre-populates existing balances', function () {
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

    Livewire::test('pages::checkins.edit', ['checkin' => $checkin->id])
        ->assertSet("balances.{$account->id}", '1500.00');
});

test('checkin edit updates existing balance', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);
    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    $balance = Balance::factory()->create([
        'account_id' => $account->id,
        'checkin_id' => $checkin->id,
        'amount' => '1000.00',
        'checked_in_at' => $checkin->checked_in_at,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::checkins.edit', ['checkin' => $checkin->id])
        ->set("balances.{$account->id}", '2500.50')
        ->call('update')
        ->assertRedirect(route('checkins.show', ['current_team' => $user->currentTeam->slug, 'checkin' => $checkin->id]));

    $balance->refresh();
    expect($balance->amount_in_cents)->toBe(250050);
});

test('checkin edit creates balance for previously-skipped account', function () {
    $user = User::factory()->create();
    $account1 = Account::factory()->create(['team_id' => $user->currentTeam->id]);
    $account2 = Account::factory()->create(['team_id' => $user->currentTeam->id]);
    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create([
        'account_id' => $account1->id,
        'checkin_id' => $checkin->id,
        'amount' => '100.00',
        'checked_in_at' => $checkin->checked_in_at,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::checkins.edit', ['checkin' => $checkin->id])
        ->set("balances.{$account2->id}", '500.00')
        ->call('update');

    expect(Balance::where('account_id', $account2->id)->where('checkin_id', $checkin->id)->count())->toBe(1);
    $newBalance = Balance::where('account_id', $account2->id)->where('checkin_id', $checkin->id)->first();
    expect($newBalance->amount_in_cents)->toBe(50000);
    expect($newBalance->checked_in_at->format('Y-m-d'))->toBe($checkin->checked_in_at->format('Y-m-d'));
});

test('checkin edit removes balance when field is cleared', function () {
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

    Livewire::test('pages::checkins.edit', ['checkin' => $checkin->id])
        ->set("balances.{$account->id}", '')
        ->call('update');

    expect(Balance::where('account_id', $account->id)->where('checkin_id', $checkin->id)->exists())->toBeFalse();
});

test('checkin edit validates non-numeric input', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);
    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    $this->actingAs($user);

    Livewire::test('pages::checkins.edit', ['checkin' => $checkin->id])
        ->set("balances.{$account->id}", 'abc')
        ->call('update')
        ->assertHasErrors(["balances.{$account->id}"]);
});

test('checkin edit only shows current team accounts', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'My Checking']);
    Account::factory()->create(['team_id' => $otherUser->currentTeam->id, 'name' => 'Their Savings']);

    $this->actingAs($user);

    $html = Livewire::test('pages::checkins.edit', ['checkin' => $checkin->id])->html();
    expect($html)->toContain('My Checking');
    expect($html)->not->toContain('Their Savings');
});
