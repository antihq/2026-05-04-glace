<?php

use App\Models\User;
use Laravel\Fortify\Features;
use Livewire\Livewire;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);
});

test('two factor setup page can be rendered', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('two-factor.setup'))
        ->assertOk();
});

test('two factor authentication can be enabled', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test('pages::settings.two-factor')
        ->call('startTwoFactorSetup');

    $component->assertSet('qrCodeSvg', fn ($svg) => ! empty($svg));

    $component->call('showVerificationIfNecessary')
        ->assertSet('showVerificationStep', true);
});

test('two factor setup requires valid confirmation code', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::settings.two-factor')
        ->call('startTwoFactorSetup')
        ->call('showVerificationIfNecessary')
        ->set('code', 'abc')
        ->call('confirmTwoFactor')
        ->assertHasErrors(['code']);
});

test('two factor authentication can be disabled', function () {
    $user = User::factory()->create();

    $user->forceFill([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $this->actingAs($user);

    Livewire::test('pages::settings.two-factor')
        ->call('disable')
        ->assertRedirect(route('security.edit'));

    expect($user->fresh()->two_factor_secret)->toBeNull();
});
