<?php

use App\Enums\AccountType;
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

    public string $type = 'checking';

    public ?string $credit_limit = null;

    public function mount($account): void
    {
        $accountModel = Account::where('team_id', Auth::user()->currentTeam->id)
            ->where('id', $account)
            ->firstOrFail();

        $this->accountId = $accountModel->id;
        $this->name = $accountModel->name;
        $this->type = $accountModel->type->value;
        $this->credit_limit = $accountModel->credit_limit;
    }

    public function update(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|in:' . implode(',', array_column(AccountType::cases(), 'value')),
        ];

        if ($this->type === AccountType::CreditCard->value && $this->credit_limit !== null && $this->credit_limit !== '') {
            $rules['credit_limit'] = 'numeric|min:0';
        }

        $validated = $this->validate($rules);

        $account = Account::where('team_id', Auth::user()->currentTeam->id)
            ->where('id', $this->accountId)
            ->firstOrFail();

        $account->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'credit_limit' => $this->type === AccountType::CreditCard->value ? $this->credit_limit : null,
        ]);

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

        <form wire:submit="update" class="mt-6 space-y-8 max-w-lg">
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
                <flux:description>Credit Card changes how check-in balances are entered. All other types are informational only.</flux:description>
                <flux:error name="type" />
            </flux:field>

            <flux:field>
                <flux:label>Credit Limit</flux:label>
                <flux:input wire:model="credit_limit" type="number" step="0.01" class="max-w-lg" :disabled="$type !== 'credit_card'" />
                <flux:description>When set, check-ins prompt for available credit and compute balance owed as credit limit minus available credit. When unset, check-ins prompt for balance owed directly.</flux:description>
                <flux:error name="credit_limit" />
            </flux:field>

            <flux:button variant="primary" type="submit">Save</flux:button>
        </form>
    </div>
</section>
