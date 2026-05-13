<?php

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Balance;
use App\Models\Checkin;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Check-in')] class extends Component
{
    #[Locked]
    public int $checkinId;

    public array $balances = [];

    public Collection $accounts;

    #[Locked]
    public string $checkedInAt;

    public function mount($checkin): void
    {
        $checkinModel = Checkin::where('team_id', Auth::user()->currentTeam->id)
            ->where('id', $checkin)
            ->firstOrFail();

        $this->checkinId = $checkinModel->id;
        $this->checkedInAt = $checkinModel->checked_in_at->format('M j, Y g:i A');
        $this->accounts = Account::where('team_id', Auth::user()->currentTeam->id)->orderBy('name')->get();

        foreach ($checkinModel->balances as $balance) {
            $amount = $balance->amount;
            $account = $this->accounts->firstWhere('id', $balance->account_id);
            if ($account && $account->type === AccountType::CreditCard) {
                if ($account->credit_limit_in_cents !== null) {
                    $availableDollars = (float) $account->credit_limit - abs((float) $balance->amount);
                    $amount = number_format($availableDollars, 2, '.', '');
                } else {
                    $amount = number_format(abs((float) $amount), 2, '.', '');
                }
            }
            $this->balances[$balance->account_id] = $amount;
        }
    }

    public function update(): void
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

        $checkin = Checkin::where('id', $this->checkinId)
            ->where('team_id', Auth::user()->currentTeam->id)
            ->with('balances')
            ->firstOrFail();

        foreach ($this->accounts as $account) {
            $value = $this->balances[$account->id] ?? null;
            $existingBalance = $checkin->balances->firstWhere('account_id', $account->id);

            if ($value !== null && $value !== '') {
                if ($account->type === AccountType::CreditCard) {
                    if ($account->credit_limit_in_cents !== null) {
                        $limitDollars = (float) $account->credit_limit;
                        $availableDollars = (float) $value;
                        $value = -(abs($limitDollars - $availableDollars));
                    } else {
                        $value = -abs((float) $value);
                    }
                }

                if ($existingBalance) {
                    $existingBalance->update(['amount' => $value]);
                } else {
                    Balance::create([
                        'account_id' => $account->id,
                        'checkin_id' => $checkin->id,
                        'amount' => $value,
                        'checked_in_at' => $checkin->checked_in_at,
                    ]);
                }
            } else {
                if ($existingBalance) {
                    $existingBalance->delete();
                }
            }
        }

        Flux::toast(variant: 'success', text: 'Check-in updated!');

        $this->redirectRoute('checkins.show', ['current_team' => Auth::user()->currentTeam->slug, 'checkin' => $this->checkinId], navigate: true);
    }
}; ?>

<section class="w-full">
    <div>
        <flux:heading size="xl" level="1">Edit Check-in</flux:heading>

        <form wire:submit="update" class="mt-6 space-y-8">
            <div class="text-sm text-zinc-500">
                {{ $this->checkedInAt }}
            </div>

            <flux:text>Leave any account blank to remove it from this check-in.</flux:text>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach ($this->accounts as $account)
                    <div>
                        @if ($account->type === AccountType::CreditCard && $account->credit_limit_in_cents !== null)
                            <flux:field>
                                <flux:label>{{ $account->name }} — Available credit</flux:label>
                                <flux:input wire:model="balances.{{ $account->id }}" type="number" step="0.01" />
                                <flux:description>Balance owed = credit limit ({{ $account->credit_limit }}) − available credit.</flux:description>
                            </flux:field>
                        @elseif ($account->type === AccountType::CreditCard)
                            <flux:field>
                                <flux:label>{{ $account->name }}</flux:label>
                                <flux:input wire:model="balances.{{ $account->id }}" type="number" step="0.01" />
                                <flux:description>Enter balance owed. Stored as negative.</flux:description>
                            </flux:field>
                        @else
                            <flux:field>
                                <flux:label>{{ $account->name }}</flux:label>
                                <flux:input wire:model="balances.{{ $account->id }}" type="number" step="0.01" />
                            </flux:field>
                        @endif
                    </div>
                @endforeach
            </div>

            <flux:button variant="primary" type="submit">Save Changes</flux:button>
        </form>
    </div>
</section>
