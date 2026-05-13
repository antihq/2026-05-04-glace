<?php

use App\Models\Account;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Accounts')] class extends Component
{
    public string $newAccountName = '';

    #[Computed]
    public function accounts()
    {
        return Account::where('team_id', Auth::user()->currentTeam->id)
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

        Flux::toast(variant: 'success', text: 'Account added.');
    }

    public function deleteAccount(int $accountId): void
    {
        $account = Account::where('team_id', Auth::user()->currentTeam->id)
            ->find($accountId);

        if (! $account) {
            return;
        }

        $account->delete();

        Flux::toast(variant: 'success', text: 'Account removed.');
    }
}; ?>

<div class="flex flex-col gap-3">
    <div class="text-[11px] uppercase tracking-wider text-zinc-500">Accounts</div>

    <form wire:submit="addAccount" class="flex items-end gap-3">
        <flux:input
            wire:model="newAccountName"
            placeholder="Account name"
            class="flex-1"
        />
        <flux:button variant="primary" type="submit">Add</flux:button>
    </form>

    @if ($this->accounts->isNotEmpty())
        <flux:table>
            <flux:table.rows>
                @foreach ($this->accounts as $account)
                    <flux:table.row>
                        <flux:table.cell class="flex-1">{{ $account->name }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:button
                                variant="ghost"
                                size="sm"
                                icon="trash"
                                wire:click="deleteAccount({{ $account->id }})"
                                wire:confirm="Delete this account? Its balance history will also be removed."
                            />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @else
        <p class="text-sm text-zinc-500">No accounts yet.</p>
    @endif
</div>
