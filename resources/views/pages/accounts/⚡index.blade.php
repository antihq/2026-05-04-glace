<?php

use App\Models\Account;
use App\Models\Balance;
use App\Support\CurrencyFormatter;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Accounts')] class extends Component
{
    use WithPagination;

    #[Computed]
    public function accounts()
    {
        return Account::where('team_id', Auth::user()->currentTeam->id)
            ->orderBy('name')
            ->paginate(25);
    }

    #[Computed]
    public function latestBalances()
    {
        $accountIds = collect($this->accounts->items())->pluck('id');

        if ($accountIds->isEmpty()) {
            return collect();
        }

        return Balance::whereIn('account_id', $accountIds)
            ->orderByDesc('checked_in_at')
            ->get()
            ->unique('account_id')
            ->keyBy('account_id');
    }
}; ?>

<section class="w-full">
    <div class="flex items-end justify-between gap-4">
        <flux:heading size="xl" level="1">Accounts</flux:heading>
        <flux:button variant="primary" :href="route('accounts.create', ['current_team' => Auth::user()->currentTeam->slug])" wire:navigate>Create account</flux:button>
    </div>

    @if ($this->accounts->isEmpty())
        <flux:text class="mt-4">No accounts. <flux:link :href="route('accounts.create', ['current_team' => Auth::user()->currentTeam->slug])" wire:navigate>Create one</flux:link> to begin tracking balances.</flux:text>
    @else
        <flux:table :paginate="$this->accounts" class="mt-8">
            <flux:table.columns>
                <flux:table.column>Account</flux:table.column>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column align="end">Current Balance</flux:table.column>
                <flux:table.column align="end">Last Check-in</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->accounts as $account)
                    @php
                        $latestBalance = $this->latestBalances->get($account->id);
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
                                @if ($latestBalance)
                                    {{ CurrencyFormatter::cents($latestBalance->amount_in_cents) }}
                                @else
                                    &mdash;
                                @endif
                            </span>
                        </flux:table.cell>
                        <flux:table.cell class="relative" align="end">
                            <x-table-row-link :href="$accountUrl" wire:navigate />
                            <span class="tabular-nums">
                                @if ($latestBalance)
                                    {{ $latestBalance->checked_in_at->format('M j, Y') }}
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
</section>
