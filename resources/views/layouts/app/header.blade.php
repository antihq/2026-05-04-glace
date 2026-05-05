<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-100 dark:bg-zinc-950 antialiased py-2 text-zinc-950 dark:text-white">
        <flux:header class="flex items-center px-6">
            <div class="mx-auto w-full h-full [:where(&)]:max-w-4xl flex flex-wrap items-center px-4">
                <flux:spacer />

                <a href="/" class="inline-flex items-stretch font-serif text-xl/8 text-zinc-950 dark:text-white" wire:navigate>{{ config('app.name') }}</a>

                <flux:spacer />

                @auth
                    <flux:navbar class="w-full justify-between grid grid-cols-3">
                        @php($teamSlug = auth()->user()->currentTeam->slug)

                        <div class="flex">
                            <flux:navbar.item :href="route('dashboard', ['current_team' => $teamSlug])" :current="request()->routeIs('dashboard')" class="data-current:after:rounded-full" wire:navigate>
                                {{ __('Dashboard') }}
                            </flux:navbar.item>
                        </div>
                        <div class="flex justify-center">
                            <flux:navbar.item :href="route('checkin', ['current_team' => $teamSlug])" :current="request()->routeIs('checkin')" class="data-current:after:rounded-full" wire:navigate>
                                {{ __('Check In') }}
                            </flux:navbar.item>
                            <flux:navbar.item :href="route('checkins', ['current_team' => $teamSlug])" :current="request()->routeIs('checkins') || request()->routeIs('checkins.edit')" class="data-current:after:rounded-full" wire:navigate>
                                {{ __('History') }}
                            </flux:navbar.item>
                        </div>
                        <div class="flex justify-end">
                            <x-desktop-user-menu class="data-current:after:rounded-full" :showTeam="true" />
                        </div>
                    </flux:navbar>
                @endauth
            </div>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
