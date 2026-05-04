<?php

use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
    Route::livewire('settings/profile/delete', 'pages::settings.delete-user')->name('profile.delete');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');

    Route::livewire('settings/security', 'pages::settings.security')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('security.edit');

    Route::livewire('settings/security/two-factor', 'pages::settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.setup');

    Route::livewire('settings/teams', 'pages::teams.index')->name('teams.index');
    Route::livewire('settings/teams/create', 'pages::teams.create')->name('teams.create');

    Route::middleware(EnsureTeamMembership::class)->group(function () {
        Route::livewire('settings/teams/{team}', 'pages::teams.edit')->name('teams.edit');
        Route::livewire('settings/teams/{team}/invite', 'pages::teams.invite')->name('teams.invite');
        Route::livewire('settings/teams/{team}/delete', 'pages::teams.delete')->name('teams.delete');
    });
});
