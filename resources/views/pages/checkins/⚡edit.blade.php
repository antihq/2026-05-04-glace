<?php

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Balance;
use App\Models\Checkin;
use App\Support\CurrencyFormatter;
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

    public array $originalBalances = [];

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
            $this->originalBalances[$balance->account_id] = CurrencyFormatter::cents($balance->amount_in_cents);
        }
    }

    public function computedBalance(int $accountId): ?float
    {
        $raw = $this->balances[$accountId] ?? null;

        if ($raw === null || $raw === '') {
            return null;
        }

        $account = $this->accounts->firstWhere('id', $accountId);

        if (! $account) {
            return null;
        }

        $value = (float) $raw;

        if ($account->type === AccountType::CreditCard) {
            if ($account->credit_limit_in_cents !== null) {
                $limitDollars = (float) $account->credit_limit;
                return -(abs($limitDollars - $value));
            }
            return -abs($value);
        }

        return $value;
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
                $computed = $this->computedBalance($account->id);

                if ($existingBalance) {
                    $existingBalance->update(['amount' => $computed]);
                } else {
                    Balance::create([
                        'account_id' => $account->id,
                        'checkin_id' => $checkin->id,
                        'amount' => $computed,
                        'checked_in_at' => $checkin->checked_in_at,
                    ]);
                }
            } else {
                if ($existingBalance) {
                    $existingBalance->delete();
                }
            }
        }

        Flux::toast(variant: 'success', text: 'Check-in updated.');

        $this->redirectRoute('checkins.show', ['current_team' => Auth::user()->currentTeam->slug, 'checkin' => $this->checkinId], navigate: true);
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl" level="1">Edit Check-in</flux:heading>

    <x-description.list class="mt-2.5">
        <x-description.term>Recorded</x-description.term>
        <x:description.details class="tabular-nums">{{ $this->checkedInAt }}</x-description.details>
    </x-description.list>

    <flux:text class="mt-4 max-w-prose">Leave any account blank to remove it from this check-in.</flux:text>

    <form wire:submit="update" class="mt-6 space-y-8 max-w-xl">
        @foreach ($this->accounts as $account)
            @php
                $original = $this->originalBalances[$account->id] ?? null;
            @endphp
            @if ($account->type === AccountType::CreditCard && $account->credit_limit_in_cents !== null)
                <flux:field>
                    <flux:label>{{ $account->name }} — Available credit</flux:label>
                    <flux:input wire:model.live="balances.{{ $account->id }}" type="number" step="0.01" />
                    <flux:description>Balance owed = credit limit ({{ $account->credit_limit }}) − available credit.</flux:description>
                    @if ($original !== null)
                        <flux:text class="mt-1 text-xs text-zinc-500">Original: {{ $original }}</flux:text>
                    @endif
                    @if (($computed = $this->computedBalance($account->id)) !== null)
                        <flux:text class="mt-1 font-mono text-sm">
                            Stored as: {{ CurrencyFormatter::cents((int) round($computed * 100)) }}
                        </flux:text>
                    @endif
                </flux:field>
            @elseif ($account->type === AccountType::CreditCard)
                <flux:field>
                    <flux:label>{{ $account->name }} — Balance owed</flux:label>
                    <flux:input wire:model.live="balances.{{ $account->id }}" type="number" step="0.01" />
                    <flux:description>Entered as positive. Stored as negative.</flux:description>
                    @if ($original !== null)
                        <flux:text class="mt-1 text-xs text-zinc-500">Original: {{ $original }}</flux:text>
                    @endif
                    @if (($computed = $this->computedBalance($account->id)) !== null)
                        <flux:text class="mt-1 font-mono text-sm">
                            Stored as: {{ CurrencyFormatter::cents((int) round($computed * 100)) }}
                        </flux:text>
                    @endif
                </flux:field>
            @else
                <flux:field>
                    <flux:label>{{ $account->name }}</flux:label>
                    <flux:input wire:model="balances.{{ $account->id }}" type="number" step="0.01" />
                    @if ($original !== null)
                        <flux:text class="mt-1 text-xs text-zinc-500">Original: {{ $original }}</flux:text>
                    @endif
                </flux:field>
            @endif
        @endforeach

        <flux:button variant="primary" type="submit">Save changes</flux:button>
    </form>
</section>
