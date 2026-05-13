<?php

use App\Models\Account;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public int $accountId;

    public string $name = '';

    public function mount($account): void
    {
        $accountModel = Account::where('team_id', Auth::user()->currentTeam->id)
            ->where('id', $account)
            ->firstOrFail();

        $this->accountId = $accountModel->id;
        $this->name = $accountModel->name;
    }

    public function update(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
        ]);

        $account = Account::where('team_id', Auth::user()->currentTeam->id)
            ->where('id', $this->accountId)
            ->firstOrFail();

        $account->update(['name' => $validated['name']]);

        Flux::toast(variant: 'success', text: 'Account updated.');

        $this->redirectRoute('accounts.show', ['current_team' => Auth::user()->currentTeam->slug, 'account' => $this->accountId], navigate: true);
    }

    public function render()
    {
        return $this->view()->title('Edit '.$this->name);
    }
}; ?>

<section class="w-full">
    <div>
        <flux:heading size="xl" level="1">Edit Account</flux:heading>

        <form wire:submit="update" class="mt-6 space-y-8">
            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="name" type="text" required autofocus autocomplete="off" class="max-w-lg" />
                <flux:error name="name" />
            </flux:field>

            <flux:button variant="primary" type="submit">Save</flux:button>
        </form>
    </div>
</section>
