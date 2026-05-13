<?php

use App\Models\Account;
use App\Models\Balance;
use App\Models\Checkin;
use App\Models\User;
use Livewire\Livewire;

test('checkins.show page can be rendered', function () {
    $user = User::factory()->create();
    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    $response = $this
        ->actingAs($user)
        ->get(route('checkins.show', ['current_team' => $user->currentTeam->slug, 'checkin' => $checkin->id]));

    $response->assertOk();
});

test('checkins.show redirects guests to login', function () {
    $user = User::factory()->create();
    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    $response = $this->get(route('checkins.show', ['current_team' => $user->currentTeam->slug, 'checkin' => $checkin->id]));
    $response->assertRedirect(route('login'));
});

test('checkins.show returns 404 for other team checkin', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherCheckin = Checkin::factory()->create(['team_id' => $otherUser->currentTeam->id, 'checked_in_at' => now()]);

    $response = $this
        ->actingAs($user)
        ->get(route('checkins.show', ['current_team' => $user->currentTeam->slug, 'checkin' => $otherCheckin->id]));

    $response->assertNotFound();
});

test('checkins.show displays checkin date and relative time', function () {
    $user = User::factory()->create();
    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    $this->actingAs($user);
    $this->travelTo(now());

    Livewire::test('pages::checkins.show', ['checkin' => $checkin->id])
        ->assertSee($checkin->checked_in_at->format('M j, Y g:i A'))
        ->assertSee($checkin->checked_in_at->diffForHumans());
});

test('checkins.show displays balance count', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);
    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create(['account_id' => $account->id, 'checkin_id' => $checkin->id, 'checked_in_at' => $checkin->checked_in_at]);

    $this->actingAs($user);

    Livewire::test('pages::checkins.show', ['checkin' => $checkin->id])
        ->assertSee('1 account recorded');
});

test('checkins.show displays total', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);
    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create(['account_id' => $account->id, 'checkin_id' => $checkin->id, 'amount' => '1234.56', 'checked_in_at' => $checkin->checked_in_at]);

    $this->actingAs($user);

    Livewire::test('pages::checkins.show', ['checkin' => $checkin->id])
        ->assertSee('$1,234.56');
});

test('checkins.show lists balances with account names and amounts', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Savings']);
    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create(['account_id' => $account->id, 'checkin_id' => $checkin->id, 'amount' => '500.00', 'checked_in_at' => $checkin->checked_in_at]);

    $this->actingAs($user);

    $html = Livewire::test('pages::checkins.show', ['checkin' => $checkin->id])->html();
    expect($html)->toContain('Savings');
    expect($html)->toContain('$500.00');
});

test('checkins.show hides balances table when no balances', function () {
    $user = User::factory()->create();
    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    $this->actingAs($user);

    Livewire::test('pages::checkins.show', ['checkin' => $checkin->id])
        ->assertDontSee('Account')
        ->assertDontSee('Amount');
});

test('checkins.show can delete a checkin', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);
    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create(['account_id' => $account->id, 'checkin_id' => $checkin->id, 'amount' => '100.00', 'checked_in_at' => $checkin->checked_in_at]);

    $this->actingAs($user);

    Livewire::test('pages::checkins.show', ['checkin' => $checkin->id])
        ->call('delete')
        ->assertRedirect(route('checkins.index', ['current_team' => $user->currentTeam->slug]));

    expect(Checkin::find($checkin->id))->toBeNull();
    expect(Balance::where('checkin_id', $checkin->id)->exists())->toBeFalse();
});

test('checkins.show links balances count to accounts index', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['team_id' => $user->currentTeam->id]);
    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create(['account_id' => $account->id, 'checkin_id' => $checkin->id, 'checked_in_at' => $checkin->checked_in_at]);

    $this->actingAs($user);

    $html = Livewire::test('pages::checkins.show', ['checkin' => $checkin->id])->html();
    expect($html)->toContain(route('accounts.index', ['current_team' => $user->currentTeam->slug]));
});
