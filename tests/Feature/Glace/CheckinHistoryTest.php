<?php

use App\Models\Account;
use App\Models\Balance;
use App\Models\Checkin;
use App\Models\User;
use Livewire\Livewire;

test('checkins page can be rendered', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('checkins.index', ['current_team' => $user->currentTeam->slug]));

        $response->assertOk();
});

test('checkins page redirects guests to login', function () {
    $user = User::factory()->create();

    $response = $this->get(route('checkins.index', ['current_team' => $user->currentTeam->slug]));
    $response->assertRedirect(route('login'));
});

test('checkins shows empty state when no checkins', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::checkins.index')
        ->assertSee('No check-ins recorded.')
        ->assertSee('Check in now');
});

test('checkins lists checkins ordered newest first', function () {
    $user = User::factory()->create();

    $oldest = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()->subDays(2)]);
    $newest = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    $this->actingAs($user);

    $html = Livewire::test('pages::checkins.index')->html();
    $newestPos = strpos($html, $newest->checked_in_at->format('M j, Y'));
    $oldestPos = strpos($html, $oldest->checked_in_at->format('M j, Y'));

    expect($newestPos)->toBeLessThan($oldestPos);
});

test('checkins shows account count per checkin', function () {
    $user = User::factory()->create();
    $account1 = Account::factory()->create(['team_id' => $user->currentTeam->id]);
    $account2 = Account::factory()->create(['team_id' => $user->currentTeam->id]);
    $account3 = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create(['account_id' => $account1->id, 'checkin_id' => $checkin->id, 'checked_in_at' => $checkin->checked_in_at]);
    Balance::factory()->create(['account_id' => $account2->id, 'checkin_id' => $checkin->id, 'checked_in_at' => $checkin->checked_in_at]);
    Balance::factory()->create(['account_id' => $account3->id, 'checkin_id' => $checkin->id, 'checked_in_at' => $checkin->checked_in_at]);

    $this->actingAs($user);

    Livewire::test('pages::checkins.index')
        ->assertSee('3 accounts');
});

test('checkins shows total per checkin', function () {
    $user = User::factory()->create();
    $account1 = Account::factory()->create(['team_id' => $user->currentTeam->id]);
    $account2 = Account::factory()->create(['team_id' => $user->currentTeam->id]);

    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    Balance::factory()->create(['account_id' => $account1->id, 'checkin_id' => $checkin->id, 'amount' => '1000.00', 'checked_in_at' => $checkin->checked_in_at]);
    Balance::factory()->create(['account_id' => $account2->id, 'checkin_id' => $checkin->id, 'amount' => '500.50', 'checked_in_at' => $checkin->checked_in_at]);

    $this->actingAs($user);

    Livewire::test('pages::checkins.index')
        ->assertSee('$1,500.50');
});

test('checkins shows show link per checkin', function () {
    $user = User::factory()->create();
    $checkin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()]);

    $this->actingAs($user);

    Livewire::test('pages::checkins.index')
        ->assertSeeHtml(route('checkins.show', ['current_team' => $user->currentTeam->slug, 'checkin' => $checkin->id]));
});

test('checkins only shows current team checkins', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $myCheckin = Checkin::factory()->create(['team_id' => $user->currentTeam->id, 'checked_in_at' => now()->subMonth()]);
    $theirCheckin = Checkin::factory()->create(['team_id' => $otherUser->currentTeam->id, 'checked_in_at' => now()]);

    $this->actingAs($user);

    $html = Livewire::test('pages::checkins.index')->html();
    expect($html)->toContain($myCheckin->checked_in_at->format('M j, Y'));
    expect($html)->not->toContain($theirCheckin->checked_in_at->format('M j, Y'));
});
