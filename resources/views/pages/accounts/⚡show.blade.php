<?php

use App\Models\Account;
use App\Models\Balance;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public int $accountId;

    public function mount($account): void
    {
        $accountModel = Account::where('team_id', Auth::user()->currentTeam->id)
            ->where('id', $account)
            ->firstOrFail();

        $this->accountId = $accountModel->id;
    }

    #[Computed]
    public function account()
    {
        return Account::where('team_id', Auth::user()->currentTeam->id)
            ->where('id', $this->accountId)
            ->firstOrFail();
    }

    #[Computed]
    public function balances()
    {
        return Balance::where('account_id', $this->accountId)
            ->with('checkin')
            ->orderByDesc('checked_in_at')
            ->get();
    }

    #[Computed]
    public function balanceCount(): int
    {
        return $this->balances->count();
    }

    #[Computed]
    public function latestBalance()
    {
        return $this->balances->first();
    }

    #[Computed]
    public function netChange(): ?int
    {
        if ($this->balances->count() < 2) {
            return null;
        }

        return $this->balances->first()->amount_in_cents - $this->balances->last()->amount_in_cents;
    }

    #[Computed]
    public function monthlyBalances()
    {
        $balances = $this->balances;

        if ($balances->isEmpty()) {
            return collect();
        }

        return $balances
            ->groupBy(fn ($b) => Carbon::parse($b->checked_in_at)->format('Y-m'))
            ->map(fn ($monthBalances) => [
                'amount' => $monthBalances->first()->amount_in_cents,
                'month' => Carbon::parse($monthBalances->first()->checked_in_at),
            ])
            ->sortKeysDesc()
            ->values();
    }

    public function delete(): void
    {
        $account = Account::where('team_id', Auth::user()->currentTeam->id)
            ->where('id', $this->accountId)
            ->firstOrFail();

        $account->delete();

        Flux::toast(variant: 'success', text: 'Account deleted.');

        $this->redirectRoute('accounts.index', ['current_team' => Auth::user()->currentTeam->slug], navigate: true);
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

    public function render()
    {
        return $this->view()->title($this->account->name);
    }
}; ?>

<section class="w-full">
    <div class="flex items-end justify-between gap-4">
        <flux:heading size="xl" level="1">{{ $this->account->name }}</flux:heading>
        <flux:button variant="primary" :href="route('accounts.edit', ['current_team' => Auth::user()->currentTeam->slug, 'account' => $this->accountId])" wire:navigate>
            Edit
        </flux:button>
    </div>

    <x-description.list class="mt-2.5">
        <x-description.term>Balances</x-description.term>
        <x-description.details>
            {{ $this->balanceCount }} {{ str()->plural('balance record', $this->balanceCount) }}
        </x-description.details>

        @if ($this->latestBalance)
            <x-description.term>Latest balance</x-description.term>
            <x-description.details class="tabular-nums">
                {{ $this->formatCents($this->latestBalance->amount_in_cents) }}
                @if ($this->latestBalance->checkin)
                    <flux:link :accent="false" :href="route('checkins.show', ['current_team' => Auth::user()->currentTeam->slug, 'checkin' => $this->latestBalance->checkin_id])" wire:navigate>
                        {{ $this->latestBalance->checked_in_at->format('M j, Y') }}
                    </flux:link>
                @else
                    ({{ $this->latestBalance->checked_in_at->format('M j, Y') }})
                @endif
            </x-description.details>
        @endif

        @if ($this->netChange !== null)
            <x-description.term>Net change</x-description.term>
            <x-description.details class="tabular-nums {{ $this->netChange > 0 ? 'text-green-600 dark:text-green-400' : ($this->netChange < 0 ? 'text-red-600 dark:text-red-400' : '') }}">
                {{ $this->netChange > 0 ? '+' : '' }}{{ $this->formatCents($this->netChange) }}
                <span class="text-zinc-400 text-xs ml-1">since first check-in</span>
            </x-description.details>
        @endif
    </x-description.list>

    <flux:heading level="2" class="mt-12">Balance History</flux:heading>
    <flux:table class="mt-4">
        <flux:table.columns>
            <flux:table.column>Date</flux:table.column>
            <flux:table.column align="end">Amount</flux:table.column>
            <flux:table.column align="end">Change</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach ($this->balances as $i => $balance)
                @php
                    $prev = $this->balances[$i + 1] ?? null;
                    $change = $prev ? $balance->amount_in_cents - $prev->amount_in_cents : null;
                    $checkinUrl = $balance->checkin
                        ? route('checkins.show', ['current_team' => Auth::user()->currentTeam->slug, 'checkin' => $balance->checkin_id])
                        : null;
                @endphp
                <flux:table.row>
                    <flux:table.cell class="relative">
                        @if ($checkinUrl)
                            <x-table-row-link :href="$checkinUrl" wire:navigate :first="true" />
                        @endif
                        {{ $balance->checked_in_at->format('M j, Y g:i A') }}
                    </flux:table.cell>
                    <flux:table.cell class="relative" align="end">
                        @if ($checkinUrl)
                            <x-table-row-link :href="$checkinUrl" wire:navigate />
                        @endif
                        <span class="tabular-nums">{{ $this->formatCents($balance->amount_in_cents) }}</span>
                    </flux:table.cell>
                    <flux:table.cell class="relative" align="end">
                        @if ($checkinUrl)
                            <x-table-row-link :href="$checkinUrl" wire:navigate />
                        @endif
                        <span class="tabular-nums">
                            @if ($change !== null && $change !== 0)
                                <span class="{{ $change > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $change > 0 ? '+' : '' }}{{ $this->formatCents($change) }}
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

    @if ($this->monthlyBalances->count() > 1)
        <flux:heading level="2" class="mt-12">Monthly Balance</flux:heading>
        <flux:table class="mt-4">
            <flux:table.columns>
                <flux:table.column>Month</flux:table.column>
                <flux:table.column align="end">Balance</flux:table.column>
                <flux:table.column align="end">Change</flux:table.column>
                <flux:table.column align="end">Change %</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->monthlyBalances as $i => $monthData)
                    @php
                        $prevMonthData = $this->monthlyBalances[$i + 1] ?? null;
                        $monthDelta = $prevMonthData ? $monthData['amount'] - $prevMonthData['amount'] : null;
                        $monthDeltaPercent = ($monthDelta !== null && $prevMonthData['amount'] != 0)
                            ? round(($monthDelta / abs($prevMonthData['amount'])) * 100, 2)
                            : null;
                    @endphp
                    <flux:table.row>
                        <flux:table.cell>{{ $monthData['month']->format('F Y') }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <span class="tabular-nums">{{ $this->formatCents($monthData['amount']) }}</span>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <span class="tabular-nums">
                                @if ($monthDelta !== null && $monthDelta !== 0)
                                    <span class="{{ $monthDelta > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $monthDelta > 0 ? '+' : '' }}{{ $this->formatCents($monthDelta) }}
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
                                        {{ $this->formatPercent($monthDeltaPercent) }}
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

    <flux:separator class="mt-12" />

    <div class="mt-4">
        <flux:button variant="danger" wire:click="delete" wire:confirm="Delete this account? Its balance history will also be removed.">
            Delete account
        </flux:button>
    </div>
</section>
