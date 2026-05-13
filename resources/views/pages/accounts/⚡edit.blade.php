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

        <form wire:submit="update" class="mt-6 space-y-8">
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

            <flux:button variant="primary" type="submit">Save</flux:button>
        </form>
    </div>
</section>
