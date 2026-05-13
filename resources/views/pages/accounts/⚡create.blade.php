<?php

use App\Models\Account;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Account')] class extends Component
{
    public string $name = '';

    public function submit(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
        ]);

        $team = Auth::user()->currentTeam;

        $account = Account::create([
            'team_id' => $team->id,
            'name' => $validated['name'],
        ]);

        Flux::toast(variant: 'success', text: 'Account created.');

        $this->redirectRoute('accounts.show', ['current_team' => $team->slug, 'account' => $account->id], navigate: true);
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl" level="1">Create Account</flux:heading>

    <form wire:submit="submit" class="mt-6 space-y-8 max-w-lg">
        <flux:field>
            <flux:label>Name</flux:label>
            <flux:input wire:model="name" type="text" required autofocus autocomplete="off" class="max-w-lg" />
            <flux:error name="name" />
        </flux:field>

        <flux:button variant="primary" type="submit">Create</flux:button>
    </form>
</section>
