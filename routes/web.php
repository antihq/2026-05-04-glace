<?php

use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages::home')->name('home')->middleware('guest');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');
        Route::livewire('checkins', 'pages::checkins.index')->name('checkins.index');
        Route::livewire('checkins/create', 'pages::checkins.create')->name('checkins.create');
        Route::livewire('checkins/{checkin}', 'pages::checkins.show')->name('checkins.show');
        Route::livewire('checkins/{checkin}/edit', 'pages::checkins.edit')->name('checkins.edit');
        Route::livewire('accounts', 'pages::accounts.index')->name('accounts.index');
        Route::livewire('accounts/create', 'pages::accounts.create')->name('accounts.create');
        Route::livewire('accounts/{account}', 'pages::accounts.show')->name('accounts.show');
        Route::livewire('accounts/{account}/edit', 'pages::accounts.edit')->name('accounts.edit');
    });

Route::middleware(['auth'])->group(function () {
    Route::livewire('invitations/{invitation}', 'pages::invitations.show')->name('invitations.show');
});

require __DIR__.'/settings.php';
