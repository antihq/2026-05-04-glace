<?php

use App\Models\Account;
use App\Models\Balance;
use App\Models\Checkin;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    #[Computed]
    public function accounts()
    {
        $team = Auth::user()->currentTeam;

        return Account::where('team_id', $team->id)->ordered()->get();
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
    public function currentBalances()
    {
        if (! $this->latestCheckinTime) {
            return collect();
        }

        $accountIds = $this->accounts->pluck('id');

        return Balance::whereIn('account_id', $accountIds)
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

        $accountIds = $this->accounts->pluck('id');

        return Balance::whereIn('account_id', $accountIds)
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

    private function formatCents(int $cents): string
    {
        $dollars = abs($cents / 100);

        return ($cents < 0 ? '-' : '').'$'.number_format($dollars, 2);
    }
}; ?>

<div class="flex flex-col gap-6">
    @if ($this->accounts->isEmpty())
        <div class="flex flex-col items-center justify-center min-h-[60vh] gap-4">
            <flux:heading size="xl">{{ __('Welcome to Glace') }}</flux:heading>
            <flux:text>{{ __('Start by adding the accounts you want to track.') }}</flux:text>
            <flux:button
                variant="primary"
                icon="plus"
                :href="route('accounts', ['current_team' => Auth::user()->currentTeam->slug])"
                wire:navigate
            >
                {{ __('Add Accounts') }}
            </flux:button>
        </div>
    @elseif (! $this->latestCheckinTime)
        <div class="flex flex-col items-center justify-center min-h-[60vh] gap-4">
            <flux:heading size="xl">{{ __('Ready for your first check-in') }}</flux:heading>
            <flux:text>{{ __('Tap the button below to enter your current balances.') }}</flux:text>
            <flux:button
                variant="primary"
                icon="plus-circle"
                :href="route('checkin', ['current_team' => Auth::user()->currentTeam->slug])"
                wire:navigate
            >
                {{ __('Check In Now') }}
            </flux:button>
        </div>
    @else
        <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <flux:text class="text-sm text-zinc-500">
                    {{ __('Last checked in') }} {{ $this->latestCheckinTime->diffForHumans() }}
                </flux:text>
                <flux:heading size="xl" class="tabular-nums">
                    {{ $this->formatCents($this->total) }}
                </flux:heading>
                @if ($this->delta !== null && $this->delta !== 0)
                    <div>
                        @if ($this->delta > 0)
                            <flux:badge color="green" icon="arrow-trending-up">
                                +{{ $this->formatCents($this->delta) }}
                            </flux:badge>
                        @else
                            <flux:badge color="red" icon="arrow-trending-down">
                                {{ $this->formatCents($this->delta) }}
                            </flux:badge>
                        @endif
                    </div>
                @endif
            </div>
            <flux:button
                variant="primary"
                icon="plus-circle"
                :href="route('checkin', ['current_team' => Auth::user()->currentTeam->slug])"
                wire:navigate
            >
                {{ __('Check In') }}
            </flux:button>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Account') }}</flux:table.column>
                <flux:table.column class="text-right">{{ __('Balance') }}</flux:table.column>
                @if ($this->previousBalances->isNotEmpty())
                    <flux:table.column class="text-right">{{ __('Change') }}</flux:table.column>
                @endif
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->accounts as $account)
                    @php
                        $current = $this->currentBalances->get($account->id);
                        $previous = $this->previousBalances->get($account->id);
                        $accountDelta = ($current && $previous) ? $current->amount_in_cents - $previous->amount_in_cents : null;
                    @endphp
                    <flux:table.row>
                        <flux:table.cell>
                            <span class="font-medium">{{ $account->name }}</span>
                        </flux:table.cell>
                        <flux:table.cell class="text-right tabular-nums">
                            @if ($current)
                                {{ $this->formatCents($current->amount_in_cents) }}
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>
                        @if ($this->previousBalances->isNotEmpty())
                            <flux:table.cell class="text-right tabular-nums">
                                @if ($accountDelta !== null && $accountDelta !== 0)
                                    @if ($accountDelta > 0)
                                        <span class="text-sm text-green-600 dark:text-green-400">
                                            +{{ $this->formatCents($accountDelta) }}
                                        </span>
                                    @else
                                        <span class="text-sm text-red-600 dark:text-red-400">
                                            {{ $this->formatCents($accountDelta) }}
                                        </span>
                                    @endif
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </flux:table.cell>
                        @endif
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
