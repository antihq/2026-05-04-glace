<?php

use App\Models\Account;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Accounts')] class extends Component
{
    #[Computed]
    public function accounts()
    {
        return Account::where('team_id', Auth::user()->currentTeam->id)
            ->orderBy('name')
            ->get();
    }
}; ?>

<section class="w-full">
    <div class="flex items-end justify-between gap-4">
        <flux:heading size="xl" level="1">Accounts</flux:heading>
        <flux:button variant="primary" :href="route('accounts.create', ['current_team' => Auth::user()->currentTeam->slug])" wire:navigate>Add Account</flux:button>
    </div>

    <flux:table class="mt-8">
        <flux:table.columns>
            <flux:table.column>Account</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach ($this->accounts as $account)
                <flux:table.row>
                    <flux:table.cell class="relative">
                        <x-table-row-link :href="route('accounts.show', ['current_team' => Auth::user()->currentTeam->slug, 'account' => $account->id])" wire:navigate :first="true" />
                        {{ $account->name }}
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</section>
