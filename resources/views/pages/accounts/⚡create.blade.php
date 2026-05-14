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
            <flux:input wire:model="name" type="text" required autofocus autocomplete="off" />
            <flux:description>A label for this account, shown in tables and reports.</flux:description>
            <flux:error name="name" />
        </flux:field>

        <flux:field>
            <flux:label>Type</flux:label>
            <flux:select wire:model.live="type">
                @foreach (AccountType::cases() as $case)
                    <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:description>
                <span class="block">Checking — standard bank account. Savings — interest-bearing account.</span>
                <span class="block">Credit Card — revolving credit, balances entered as available credit or amount owed and stored as negative. Cash — physical currency. Other — miscellaneous.</span>
            </flux:description>
            <flux:error name="type" />
        </flux:field>

        <flux:field>
            <flux:label>Credit Limit</flux:label>
            <flux:input wire:model="credit_limit" type="number" step="0.01" class="max-w-lg" :disabled="$type !== 'credit_card'" />
            <flux:description>When set, check-ins prompt for available credit and compute balance owed as credit limit minus available credit. When unset, check-ins prompt for balance owed directly.</flux:description>
            <flux:error name="credit_limit" />
        </flux:field>

        <flux:button variant="primary" type="submit">Create account</flux:button>
    </form>
</section>
