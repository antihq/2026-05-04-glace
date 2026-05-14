<?php

use App\Models\Account;
use App\Models\Balance;
use App\Models\Checkin;
use App\Support\CurrencyFormatter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component
{
    #[Computed]
    public function accounts()
    {
        return Account::where('team_id', Auth::user()->currentTeam->id)->orderBy('name')->get();
    }

    #[Computed]
    public function latestCheckin()
    {
        return Checkin::where('team_id', Auth::user()->currentTeam->id)
            ->orderByDesc('checked_in_at')
            ->first();
    }

    #[Computed]
    public function latestCheckinTime()
    {
        return $this->latestCheckin?->checked_in_at
            ? Carbon::parse($this->latestCheckin->checked_in_at)
            : null;
    }

    #[Computed]
    public function previousCheckinTime()
    {
        if (! $this->latestCheckinTime) {
            return null;
        }

        $time = Checkin::where('team_id', Auth::user()->currentTeam->id)
            ->where('checked_in_at', '<', $this->latestCheckinTime)
            ->max('checked_in_at');

        return $time ? Carbon::parse($time) : null;
    }

    #[Computed]
    public function checkinCount(): int
    {
        return Checkin::where('team_id', Auth::user()->currentTeam->id)->count();
    }

    #[Computed]
    public function currentBalances()
    {
        if (! $this->latestCheckinTime) {
            return collect();
        }

        return Balance::whereIn('account_id', $this->accounts->pluck('id'))
            ->where('checked_in_at', '<=', $this->latestCheckinTime)
            ->orderByDesc('checked_in_at')
            ->get()
            ->unique('account_id')
            ->keyBy('account_id');
    }

    #[Computed]
    public function previousBalances()
    {
        if (! $this->previousCheckinTime) {
            return collect();
        }

        return Balance::whereIn('account_id', $this->accounts->pluck('id'))
            ->where('checked_in_at', '<=', $this->previousCheckinTime)
            ->orderByDesc('checked_in_at')
            ->get()
            ->unique('account_id')
            ->keyBy('account_id');
    }

    #[Computed]
    public function total(): int
    {
        return $this->currentBalances->sum('amount_in_cents');
    }

    #[Computed]
    public function previousTotal(): ?int
    {
        if ($this->previousBalances->isEmpty()) {
            return null;
        }

        return $this->previousBalances->sum('amount_in_cents');
    }

    #[Computed]
    public function delta(): ?int
    {
        if ($this->previousTotal === null) {
            return null;
        }

        return $this->total - $this->previousTotal;
    }

    #[Computed]
    public function deltaPercent(): ?float
    {
        if ($this->previousTotal === null || $this->previousTotal === 0) {
            return null;
        }

        return round((($this->total - $this->previousTotal) / abs($this->previousTotal)) * 100, 2);
    }

    #[Computed]
    public function monthlyBalances()
    {
        $accountIds = $this->accounts->pluck('id');

        if ($accountIds->isEmpty()) {
            return collect();
        }

        return Balance::whereIn('account_id', $accountIds)
            ->orderByDesc('checked_in_at')
            ->get()
            ->groupBy(fn ($b) => Carbon::parse($b->checked_in_at)->format('Y-m'))
            ->map(function ($monthBalances) {
                $latestPerAccount = $monthBalances->unique('account_id');

                return [
                    'total' => $latestPerAccount->sum('amount_in_cents'),
                    'month' => Carbon::parse($monthBalances->first()->checked_in_at),
                ];
            })
            ->sortKeysDesc()
            ->values();
    }
}; ?>

<section class="w-full">
    <div class="flex items-end justify-between gap-4">
        <flux:heading size="xl" level="1">Dashboard</flux:heading>
        <flux:button variant="primary" :href="route('checkins.create', ['current_team' => Auth::user()->currentTeam->slug])" wire:navigate>New Check-in</flux:button>
    </div>

    @if ($this->accounts->isEmpty())
        <flux:text class="mt-4">No accounts. <flux:link :href="route('accounts.create', ['current_team' => Auth::user()->currentTeam->slug])" wire:navigate>Create one</flux:link> to begin tracking balances.</flux:text>

        @if ($this->checkinCount === 0)
            <flux:text class="mt-2">No check-ins recorded yet.</flux:text>
        @endif
    @else
        <x-description.list class="mt-2.5">
            <x-description.term>Total</x-description.term>
            <x-description.details class="tabular-nums">{{ CurrencyFormatter::cents($this->total) }}</x-description.details>

            <x-description.term>Previous</x-description.term>
            <x-description.details class="tabular-nums">
                @if ($this->previousTotal !== null)
                    {{ CurrencyFormatter::cents($this->previousTotal) }}
                @else
                    &mdash;
                @endif
            </x-description.details>

            @if ($this->delta !== null && $this->delta !== 0)
                <x-description.term>Change</x-description.term>
                <x-description.details class="tabular-nums {{ $this->delta > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ CurrencyFormatter::deltaCents($this->delta) }}
                </x-description.details>
            @endif

            @if ($this->deltaPercent !== null)
                <x-description.term>Change %</x-description.term>
                <x-description.details class="tabular-nums {{ $this->deltaPercent > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ CurrencyFormatter::percent($this->deltaPercent) }}
                </x-description.details>
            @endif

            <x-description.term>Accounts</x-description.term>
            <x-description.details>
                <flux:link :accent="false" :href="route('accounts.index', ['current_team' => Auth::user()->currentTeam->slug])" wire:navigate>
                    {{ $this->accounts->count() }} {{ str()->plural('account', $this->accounts->count()) }} tracked
                </flux:link>
            </x-description.details>

            <x-description.term>Last check-in</x-description.term>
            <x-description.details>
                @if ($this->latestCheckinTime)
                    <flux:link :accent="false" :href="route('checkins.show', ['current_team' => Auth::user()->currentTeam->slug, 'checkin' => $this->latestCheckin->id])" wire:navigate>
                        {{ $this->latestCheckinTime->format('M j, Y g:i A') }}
                    </flux:link>
                    <span class="text-zinc-500 text-sm/6 sm:text-xs/6">
                        {{ $this->latestCheckinTime->diffForHumans() }}
                    </span>
                @else
                    &mdash;
                @endif
            </x-description.details>

            <x-description.term>Total check-ins</x-description.term>
            <x-description.details>
                <flux:link :accent="false" :href="route('checkins.index', ['current_team' => Auth::user()->currentTeam->slug])" wire:navigate>
                    {{ $this->checkinCount }} {{ str()->plural('check-in', $this->checkinCount) }}
                </flux:link>
            </x-description.details>
        </x-description.list>

        <flux:heading level="2" class="mt-12">Account Balances</flux:heading>
        <flux:table class="mt-4">
            <flux:table.columns>
                <flux:table.column>Account</flux:table.column>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column align="end">Current</flux:table.column>
                <flux:table.column align="end">Previous</flux:table.column>
                <flux:table.column align="end">Change</flux:table.column>
                <flux:table.column align="end">%</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->accounts as $account)
                    @php
                        $current = $this->currentBalances->get($account->id);
                        $previous = $this->previousBalances->get($account->id);
                        $accountDelta = ($current && $previous) ? $current->amount_in_cents - $previous->amount_in_cents : null;
                        $accountDeltaPercent = ($accountDelta !== null && $previous && $previous->amount_in_cents != 0)
                            ? round(($accountDelta / abs($previous->amount_in_cents)) * 100, 2)
                            : null;
                        $accountUrl = route('accounts.show', ['current_team' => Auth::user()->currentTeam->slug, 'account' => $account->id]);
                    @endphp
                    <flux:table.row>
                        <flux:table.cell class="relative">
                            <x-table-row-link :href="$accountUrl" wire:navigate :first="true" />
                            {{ $account->name }}
                        </flux:table.cell>
                        <flux:table.cell class="relative">
                            <x-table-row-link :href="$accountUrl" wire:navigate />
                            <flux:badge color="zinc" size="sm" inset="top bottom">{{ $account->type->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="relative" align="end">
                            <x-table-row-link :href="$accountUrl" wire:navigate />
                            <span class="tabular-nums">
                                @if ($current)
                                    {{ CurrencyFormatter::cents($current->amount_in_cents) }}
                                @else
                                    &mdash;
                                @endif
                            </span>
                        </flux:table.cell>
                        <flux:table.cell class="relative" align="end">
                            <x-table-row-link :href="$accountUrl" wire:navigate />
                            <span class="tabular-nums">
                                @if ($previous)
                                    {{ CurrencyFormatter::cents($previous->amount_in_cents) }}
                                @else
                                    &mdash;
                                @endif
                            </span>
                        </flux:table.cell>
                        <flux:table.cell class="relative" align="end">
                            <x-table-row-link :href="$accountUrl" wire:navigate />
                            <span class="tabular-nums">
                                @if ($accountDelta !== null && $accountDelta !== 0)
                                    <span class="{{ $accountDelta > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ CurrencyFormatter::deltaCents($accountDelta) }}
                                    </span>
                                @else
                                    &mdash;
                                @endif
                            </span>
                        </flux:table.cell>
                        <flux:table.cell class="relative" align="end">
                            <x-table-row-link :href="$accountUrl" wire:navigate />
                            <span class="tabular-nums">
                                @if ($accountDeltaPercent !== null)
                                    <span class="{{ $accountDeltaPercent > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ CurrencyFormatter::percent($accountDeltaPercent) }}
                                    </span>
                                @else
                                    &mdash;
                                @endif
                            </span>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        @if ($this->monthlyBalances->isNotEmpty())
            <flux:heading level="2" class="mt-12">Monthly Overview</flux:heading>
            <flux:table class="mt-4">
                <flux:table.columns>
                    <flux:table.column>Month</flux:table.column>
                    <flux:table.column align="end">Total Balance</flux:table.column>
                    <flux:table.column align="end">Change</flux:table.column>
                    <flux:table.column align="end">Change %</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->monthlyBalances as $i => $monthData)
                        @php
                            $prevMonthData = $this->monthlyBalances[$i + 1] ?? null;
                            $monthDelta = $prevMonthData ? $monthData['total'] - $prevMonthData['total'] : null;
                            $monthDeltaPercent = ($monthDelta !== null && $prevMonthData['total'] != 0)
                                ? round(($monthDelta / abs($prevMonthData['total'])) * 100, 2)
                                : null;
                        @endphp
                        <flux:table.row>
                            <flux:table.cell>{{ $monthData['month']->format('F Y') }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <span class="tabular-nums">{{ CurrencyFormatter::cents($monthData['total']) }}</span>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <span class="tabular-nums">
                                    @if ($monthDelta !== null && $monthDelta !== 0)
                                        <span class="{{ $monthDelta > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ CurrencyFormatter::deltaCents($monthDelta) }}
                                        </span>
                                    @else
                                        &mdash;
                                    @endif
                                </span>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <span class="tabular-nums">
                                    @if ($monthDeltaPercent !== null)
                                        <span class="{{ $monthDeltaPercent > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ CurrencyFormatter::percent($monthDeltaPercent) }}
                                        </span>
                                    @else
                                        &mdash;
                                    @endif
                                </span>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    @endif
</section>
