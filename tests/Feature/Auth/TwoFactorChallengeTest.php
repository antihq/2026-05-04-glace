<?php

use App\Models\User;
use Laravel\Fortify\Features;
use PragmaRX\Google2FA\Google2FA;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());
});

test('two factor challenge redirects to login when not authenticated', function () {
    $response = $this->get(route('two-factor.login'));

    $response->assertRedirect(route('login'));
});

test('two factor challenge can be rendered', function () {
    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('two-factor.login'));
});

test('two factor challenge redirects to dashboard on success', function () {
    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $secret = (new Google2FA)->generateSecretKey();
    $otp = (new Google2FA)->getCurrentOtp($secret);

    $user = User::factory()->create([
        'two_factor_secret' => encrypt($secret),
        'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
        'two_factor_confirmed_at' => now(),
    ]);
    $team = $user->currentTeam;

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response = $this->post(route('two-factor.login.store'), [
        'code' => $otp,
        'recovery_code' => '',
    ]);

    $response->assertRedirect("/{$team->slug}/dashboard");

    $this->assertAuthenticated();
});
