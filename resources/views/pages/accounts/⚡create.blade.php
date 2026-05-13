<?php

use App\Enums\AccountType;
use App\Models\Account;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Account')] class extends Component
{
    public string $name = '';

    public string $type = 'checking';

    public ?string $credit_limit = null;

    public function submit(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|in:' . implode(',', array_column(AccountType::cases(), 'value')),
        ];

        if ($this->type === AccountType::CreditCard->value && $this->credit_limit !== null && $this->credit_limit !== '') {
            $rules['credit_limit'] = 'numeric|min:0';
        }

        $validated = $this->validate($rules);

        $team = Auth::user()->currentTeam;

        $account = Account::create([
            'team_id' => $team->id,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'credit_limit' => $this->type === AccountType::CreditCard->value ? $this->credit_limit : null,
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

        <flux:field>
            <flux:label>Type</flux:label>
            <flux:select wire:model.live="type">
                @foreach (AccountType::cases() as $case)
                    <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="type" />
        </flux:field>

        @if ($type === 'credit_card')
            <flux:field>
                <flux:label>Credit Limit</flux:label>
                <flux:input wire:model="credit_limit" type="number" step="0.01" placeholder="Optional — used to calculate balance from available credit" class="max-w-lg" />
                <flux:error name="credit_limit" />
            </flux:field>
        @endif

        <flux:button variant="primary" type="submit">Create</flux:button>
    </form>
</section>
