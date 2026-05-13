<?php

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Balance;
use App\Models\Checkin;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Check In')] class extends Component
{
    public array $balances = [];

    public Collection $accounts;

    public function mount(): void
    {
        $this->accounts = Account::where('team_id', Auth::user()->currentTeam->id)->orderBy('name')->get();
    }

    public function submit(): void
    {
        $rules = [];
        foreach ($this->accounts as $account) {
            $value = $this->balances[$account->id] ?? null;
            if ($value !== null && $value !== '') {
                $rules["balances.{$account->id}"] = 'numeric';
            }
        }

        if (! empty($rules)) {
            $this->validate($rules);
        }

        $now = now();

        $checkin = Checkin::create([
            'team_id' => Auth::user()->currentTeam->id,
            'checked_in_at' => $now,
        ]);

        foreach ($this->accounts as $account) {
            $value = $this->balances[$account->id] ?? null;
            if ($value !== null && $value !== '') {
                if ($account->type === AccountType::CreditCard) {
                    if ($account->credit_limit_in_cents !== null) {
                        $limitDollars = (float) $account->credit_limit;
                        $availableDollars = (float) $value;
                        $value = -(abs($limitDollars - $availableDollars));
                    } else {
                        $value = -abs((float) $value);
                    }
                }

                Balance::create([
                    'account_id' => $account->id,
                    'checkin_id' => $checkin->id,
                    'amount' => $value,
                    'checked_in_at' => $now,
                ]);
            }
        }

        Flux::toast(variant: 'success', text: 'Check-in complete!');

        $this->redirectRoute('dashboard', ['current_team' => Auth::user()->currentTeam->slug], navigate: true);
    }
}; ?>

<section class="w-full">
    @if ($this->accounts->isEmpty())
        <flux:heading size="xl" level="1">Check In</flux:heading>
        <flux:text class="mt-2.5">
            No accounts to check in.
            <flux:link :href="route('accounts.create', ['current_team' => Auth::user()->currentTeam->slug])" wire:navigate>Add accounts</flux:link> first.
        </flux:text>
    @else
        <flux:heading size="xl" level="1">Check In</flux:heading>
        <flux:text class="mt-1 max-w-prose">Record a snapshot of your current balances. Each check-in is timestamped and used to compute changes over time. Leave any account blank to skip it.</flux:text>

        <form wire:submit="submit" class="mt-6 space-y-8 max-w-lg">
            @foreach ($this->accounts as $account)
                @if ($account->type === AccountType::CreditCard && $account->credit_limit_in_cents !== null)
                    <flux:input
                        wire:model="balances.{{ $account->id }}"
                        :label="$account->name . ' — Available credit'"
                        type="number"
                        step="0.01"
                        placeholder="0.00"
                    />
                    <flux:text class="!mt-1 text-xs text-zinc-500">Balance owed = credit limit ({{ $account->credit_limit }}) − available credit</flux:text>
                @elseif ($account->type === AccountType::CreditCard)
                    <flux:input
                        wire:model="balances.{{ $account->id }}"
                        :label="$account->name"
                        type="number"
                        step="0.01"
                        placeholder="0.00"
                    />
                    <flux:text class="!mt-1 text-xs text-zinc-500">Enter balance owed — stored as negative</flux:text>
                @else
                    <flux:input
                        wire:model="balances.{{ $account->id }}"
                        :label="$account->name"
                        type="number"
                        step="0.01"
                        placeholder="0.00"
                    />
                @endif
            @endforeach

            <flux:button variant="primary" type="submit">Check In</flux:button>
        </form>
    @endif
</section>
