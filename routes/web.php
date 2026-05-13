<?php

use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages::home')->name('home')->middleware('guest');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');
        Route::livewire('checkin', 'pages::checkin')->name('checkin');
        Route::livewire('checkins', 'pages::checkins')->name('checkins');
        Route::livewire('checkins/{checkin}/edit', 'pages::checkin-edit')->name('checkins.edit');
        Route::livewire('accounts', 'pages::accounts')->name('accounts');
    });

Route::middleware(['auth'])->group(function () {
    Route::livewire('invitations/{invitation}', 'pages::invitations.show')->name('invitations.show');
});

require __DIR__.'/settings.php';
