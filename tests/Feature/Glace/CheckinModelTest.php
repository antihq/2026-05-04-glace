<?php

use App\Models\Balance;
use App\Models\Checkin;
use App\Models\Team;

test('checkin belongs to team', function () {
    $team = Team::factory()->create();
    $checkin = Checkin::factory()->create(['team_id' => $team->id]);

    expect($checkin->team)->not->toBeNull();
    expect($checkin->team->id)->toBe($team->id);
});

test('checkin has many balances', function () {
    $checkin = Checkin::factory()->create();

    Balance::factory()->create(['checkin_id' => $checkin->id]);
    Balance::factory()->create(['checkin_id' => $checkin->id]);

    expect($checkin->balances)->toHaveCount(2);
});

test('checked_in_at is cast to datetime', function () {
    $checkin = Checkin::factory()->create([
        'checked_in_at' => '2026-01-15 10:30:00',
    ]);

    expect($checkin->checked_in_at)->toBeInstanceOf(\DateTimeInterface::class);
});

test('checkin is fillable', function () {
    $team = Team::factory()->create();

    $checkin = Checkin::create([
        'team_id' => $team->id,
        'checked_in_at' => now(),
    ]);

    expect($checkin->team_id)->toBe($team->id);
    expect($checkin->checked_in_at)->not->toBeNull();
});
