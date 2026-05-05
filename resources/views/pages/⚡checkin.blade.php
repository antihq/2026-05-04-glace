<?php

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
        $this->accounts = Account::where('team_id', Auth::user()->currentTeam->id)->ordered()->get();
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

        foreach ($this->balances as $accountId => $value) {
            if ($value !== null && $value !== '') {
                Balance::create([
                    'account_id' => $accountId,
                    'checkin_id' => $checkin->id,
                    'amount' => $value,
                    'checked_in_at' => $now,
                ]);
            }
        }

        Flux::toast(variant: 'success', text: __('Check-in complete!'));

        $this->redirectRoute('dashboard', ['current_team' => Auth::user()->currentTeam->slug], navigate: true);
    }
}; ?>

<div class="flex flex-col gap-3">
    @if ($this->accounts->isEmpty())
        <p class="text-sm text-zinc-500">
            No accounts to check in.
            <flux:link :href="route('accounts', ['current_team' => Auth::user()->currentTeam->slug])" wire:navigate>Add accounts</flux:link>
        </p>
    @else
        <form wire:submit="submit" class="flex flex-col gap-3">
            <div class="flex items-center gap-3">
                <flux:heading class="whitespace-nowrap">{{ __('Check In') }}</flux:heading>
                <flux:separator />
            </div>

            <div class="text-sm">Records a snapshot of your current balances. Each check-in is timestamped and used to compute changes over time. Leave any account blank to skip it &mdash; its balance carries forward from the last check-in.</div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach ($this->accounts as $account)
                    <flux:input
                        wire:model="balances.{{ $account->id }}"
                        :label="$account->name"
                        type="number"
                        step="0.01"
                        placeholder="0.00"
                        size="sm"
                    />
                @endforeach
            </div>

            <div class="flex items-center">
                <flux:button size="sm" variant="primary" color="emerald" icon:trailing="arrow-right" type="submit">{{ __('Check In') }}</flux:button>
            </div>
        </form>
    @endif
</div>
