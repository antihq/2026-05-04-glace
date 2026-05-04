<?php

use App\Models\Account;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Accounts')] class extends Component {
    public string $newAccountName = '';

    #[Computed]
    public function accounts()
    {
        $team = Auth::user()->currentTeam;

        return Account::where('team_id', $team->id)
            ->ordered()
            ->get();
    }

    public function addAccount(): void
    {
        $validated = $this->validate([
            'newAccountName' => 'required|string|max:255',
        ]);

        $team = Auth::user()->currentTeam;
        $maxOrder = Account::where('team_id', $team->id)->withTrashed()->max('sort_order') ?? 0;

        Account::create([
            'team_id' => $team->id,
            'name' => $validated['newAccountName'],
            'sort_order' => $maxOrder + 1,
        ]);

        $this->reset('newAccountName');

        Flux::toast(variant: 'success', text: __('Account added.'));
    }

    public function deleteAccount(int $accountId): void
    {
        $account = Account::where('team_id', Auth::user()->currentTeam->id)
            ->find($accountId);

        if (! $account) {
            return;
        }

        $account->delete();

        Flux::toast(variant: 'success', text: __('Account removed.'));
    }
}; ?>

<div class="flex flex-col gap-6">
    <flux:heading size="xl">{{ __('Accounts') }}</flux:heading>
    <flux:text>{{ __('Add the accounts you want to track balances for.') }}</flux:text>

    <form wire:submit="addAccount" class="flex items-end gap-3">
        <flux:input
            wire:model="newAccountName"
            :placeholder="__('e.g. Checking, Savings, Credit Card…')"
            class="flex-1"
        />
        <flux:button variant="primary" type="submit" icon="plus">
            {{ __('Add') }}
        </flux:button>
    </form>

    @if ($this->accounts->isNotEmpty())
        <flux:table>
            <flux:table.rows>
                @foreach ($this->accounts as $account)
                    <flux:table.row>
                        <flux:table.cell class="flex-1">
                            <span class="font-medium">{{ $account->name }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:button
                                variant="ghost"
                                size="sm"
                                icon="trash"
                                wire:click="deleteAccount({{ $account->id }})"
                                wire:confirm="{{ __('Delete this account? Its balance history will also be removed.') }}"
                            />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @else
        <flux:callout>
            <flux:callout.heading>{{ __('No accounts yet') }}</flux:callout.heading>
            <flux:callout.text>{{ __('Add your first account above to get started — like "Checking" or "Savings".') }}</flux:callout.text>
        </flux:callout>
    @endif
</div>
