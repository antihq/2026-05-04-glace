<?php

use App\Models\Account;
use App\Models\Balance;
use App\Models\Checkin;
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

    private function formatCents(int $cents): string
    {
        $dollars = abs($cents / 100);

        return ($cents < 0 ? '-' : '').'$'.number_format($dollars, 2);
    }

    private function formatPercent(float $percent): string
    {
        return ($percent > 0 ? '+' : '').number_format($percent, 2).'%';
    }
}; ?>

<section class="w-full">
    <div class="flex items-end justify-between gap-4">
        <flux:heading size="xl" level="1">Dashboard</flux:heading>
        @if ($this->latestCheckinTime)
            <flux:button variant="primary" :href="route('checkins.create', ['current_team' => Auth::user()->currentTeam->slug])" wire:navigate>Check In</flux:button>
        @endif
    </div>

    @if ($this->accounts->isEmpty())
        <p class="mt-2.5 text-sm text-zinc-500">
            No accounts tracked.
            <flux:link :href="route('accounts.create', ['current_team' => Auth::user()->currentTeam->slug])" wire:navigate>Add accounts</flux:link>
        </p>
    @elseif (! $this->latestCheckinTime)
        <p class="mt-2.5 text-sm text-zinc-500">
            No check-ins recorded.
            <flux:link :href="route('checkins.create', ['current_team' => Auth::user()->currentTeam->slug])" wire:navigate>Check in now</flux:link>
        </p>
    @else
        <x-description.list class="mt-2.5">
            <x-description.term>Total</x-description.term>
            <x-description.details class="tabular-nums">{{ $this->formatCents($this->total) }}</x-description.details>

            <x-description.term>Previous</x-description.term>
            <x-description.details class="tabular-nums">
                @if ($this->previousTotal !== null)
                    {{ $this->formatCents($this->previousTotal) }}
                @else
                    &mdash;
                @endif
            </x-description.details>

            @if ($this->delta !== null && $this->delta !== 0)
                <x-description.term>Change</x-description.term>
                <x-description.details class="tabular-nums {{ $this->delta > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $this->delta > 0 ? '+' : '' }}{{ $this->formatCents($this->delta) }}
                </x-description.details>
            @endif

            @if ($this->deltaPercent !== null)
                <x-description.term>Change %</x-description.term>
                <x-description.details class="tabular-nums {{ $this->deltaPercent > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $this->formatPercent($this->deltaPercent) }}
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
                <flux:link :accent="false" :href="route('checkins.show', ['current_team' => Auth::user()->currentTeam->slug, 'checkin' => $this->latestCheckin->id])" wire:navigate>
                    {{ $this->latestCheckinTime->format('M j, Y g:i A') }} ({{ $this->latestCheckinTime->diffForHumans() }})
                </flux:link>
            </x-description.details>

            <x-description.term>Total check-ins</x-description.term>
            <x-description.details>
                <flux:link :accent="false" :href="route('checkins.index', ['current_team' => Auth::user()->currentTeam->slug])" wire:navigate>
                    {{ $this->checkinCount }} {{ str()->plural('check-in', $this->checkinCount) }}
                </flux:link>
            </x-description.details>
        </x-description.list>

        @if ($this->accounts->isNotEmpty())
            <flux:heading level="2" class="mt-12">Account Balances</flux:heading>
            <flux:table class="mt-4">
                <flux:table.columns>
                    <flux:table.column>Account</flux:table.column>
                    <flux:table.column align="right">Current</flux:table.column>
                    <flux:table.column align="right">Previous</flux:table.column>
                    <flux:table.column align="right">Change</flux:table.column>
                    <flux:table.column align="right">%</flux:table.column>
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
                                <span class="tabular-nums">
                                    @if ($current)
                                        {{ $this->formatCents($current->amount_in_cents) }}
                                    @else
                                        &mdash;
                                    @endif
                                </span>
                            </flux:table.cell>
                            <flux:table.cell class="relative">
                                <x-table-row-link :href="$accountUrl" wire:navigate />
                                <span class="tabular-nums">
                                    @if ($previous)
                                        {{ $this->formatCents($previous->amount_in_cents) }}
                                    @else
                                        &mdash;
                                    @endif
                                </span>
                            </flux:table.cell>
                            <flux:table.cell class="relative">
                                <x-table-row-link :href="$accountUrl" wire:navigate />
                                <span class="tabular-nums">
                                    @if ($accountDelta !== null && $accountDelta !== 0)
                                        <span class="{{ $accountDelta > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $accountDelta > 0 ? '+' : '' }}{{ $this->formatCents($accountDelta) }}
                                        </span>
                                    @else
                                        &mdash;
                                    @endif
                                </span>
                            </flux:table.cell>
                            <flux:table.cell class="relative">
                                <x-table-row-link :href="$accountUrl" wire:navigate />
                                <span class="tabular-nums">
                                    @if ($accountDeltaPercent !== null)
                                        <span class="{{ $accountDeltaPercent > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $this->formatPercent($accountDeltaPercent) }}
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
