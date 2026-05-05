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
        return Account::where('team_id', Auth::user()->currentTeam->id)->ordered()->get();
    }

    #[Computed]
    public function latestCheckinTime()
    {
        $time = Checkin::where('team_id', Auth::user()->currentTeam->id)
            ->max('checked_in_at');

        return $time ? Carbon::parse($time) : null;
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

<div class="flex flex-col gap-3">
    @if ($this->accounts->isEmpty())
        <p class="text-sm text-zinc-500">
            No accounts tracked.
            <flux:link :href="route('accounts', ['current_team' => Auth::user()->currentTeam->slug])" wire:navigate>Add accounts</flux:link>
        </p>
    @elseif (! $this->latestCheckinTime)
        <p class="text-sm text-zinc-500">
            No check-ins recorded.
            <flux:link :href="route('checkin', ['current_team' => Auth::user()->currentTeam->slug])" wire:navigate>Check in now</flux:link>
        </p>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
            <div>
                <div class="text-[11px] uppercase tracking-wider text-zinc-500">Total</div>
                <div class="tabular-nums font-medium">{{ $this->formatCents($this->total) }}</div>
            </div>
            <div>
                <div class="text-[11px] uppercase tracking-wider text-zinc-500">Previous</div>
                @if ($this->previousTotal !== null)
                    <div class="tabular-nums">{{ $this->formatCents($this->previousTotal) }}</div>
                @else
                    <div class="text-zinc-400">—</div>
                @endif
            </div>
            <div>
                <div class="text-[11px] uppercase tracking-wider text-zinc-500">Change</div>
                @if ($this->delta !== null && $this->delta !== 0)
                    <div class="tabular-nums {{ $this->delta > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $this->delta > 0 ? '+' : '' }}{{ $this->formatCents($this->delta) }}
                    </div>
                @else
                    <div class="text-zinc-400">—</div>
                @endif
            </div>
            <div>
                <div class="text-[11px] uppercase tracking-wider text-zinc-500">&Delta;%</div>
                @if ($this->deltaPercent !== null)
                    <div class="tabular-nums {{ $this->deltaPercent > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $this->formatPercent($this->deltaPercent) }}
                    </div>
                @else
                    <div class="text-zinc-400">—</div>
                @endif
            </div>
            <div>
                <div class="text-[11px] uppercase tracking-wider text-zinc-500">Accounts</div>
                <div class="tabular-nums">{{ $this->accounts->count() }}</div>
            </div>
        </div>

        <div class="text-sm text-zinc-500">
            Last check-in: {{ $this->latestCheckinTime->format('M j, Y g:i A') }} ({{ $this->latestCheckinTime->diffForHumans() }})
            @if ($this->previousCheckinTime)
                &middot; Previous: {{ $this->previousCheckinTime->format('M j, Y') }}
            @endif
            &middot; {{ $this->checkinCount }} check-in{{ $this->checkinCount === 1 ? '' : 's' }}
            &middot; <flux:link :href="route('checkin', ['current_team' => Auth::user()->currentTeam->slug])" wire:navigate>Check in</flux:link>
            &middot; <flux:link :href="route('checkins', ['current_team' => Auth::user()->currentTeam->slug])" wire:navigate>History</flux:link>
        </div>

        <flux:separator />

        <flux:table>
            <flux:table.columns>
                <flux:table.column>Account</flux:table.column>
                <flux:table.column class="text-right">Current</flux:table.column>
                <flux:table.column class="text-right">Previous</flux:table.column>
                <flux:table.column class="text-right">&pm; Change</flux:table.column>
                <flux:table.column class="text-right">&pm; %</flux:table.column>
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
                    @endphp
                    <flux:table.row>
                        <flux:table.cell>{{ $account->name }}</flux:table.cell>
                        <flux:table.cell class="text-right tabular-nums">
                            @if ($current)
                                {{ $this->formatCents($current->amount_in_cents) }}
                            @else
                                <span class="text-zinc-400">&mdash;</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-right tabular-nums">
                            @if ($previous)
                                {{ $this->formatCents($previous->amount_in_cents) }}
                            @else
                                <span class="text-zinc-400">&mdash;</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-right tabular-nums">
                            @if ($accountDelta !== null && $accountDelta !== 0)
                                <span class="{{ $accountDelta > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $accountDelta > 0 ? '+' : '' }}{{ $this->formatCents($accountDelta) }}
                                </span>
                            @else
                                <span class="text-zinc-400">&mdash;</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-right tabular-nums">
                            @if ($accountDeltaPercent !== null)
                                <span class="{{ $accountDeltaPercent > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $this->formatPercent($accountDeltaPercent) }}
                                </span>
                            @else
                                <span class="text-zinc-400">&mdash;</span>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
