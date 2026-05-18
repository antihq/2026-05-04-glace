<?php

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Balance;
use App\Models\Checkin;
use App\Support\CurrencyFormatter;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('New Check-in')] class extends Component
{
    public array $balances = [];

    public Collection $accounts;

    public function mount(): void
    {
        $this->accounts = Account::where('team_id', Auth::user()->currentTeam->id)->orderBy('name')->get();
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

    public function totalComputedBalance(): ?int
    {
        $total = 0;
        $any = false;

        foreach ($this->accounts as $account) {
            $bal = $this->computedBalance($account->id);
            if ($bal !== null) {
                $any = true;
                $total += (int) round($bal * 100);
            }
        }

        return $any ? $total : null;
    }

    public function submit(): void
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

        $now = now();

        $checkin = Checkin::create([
            'team_id' => Auth::user()->currentTeam->id,
            'checked_in_at' => $now,
        ]);

        foreach ($this->accounts as $account) {
            $value = $this->balances[$account->id] ?? null;
            if ($value !== null && $value !== '') {
                $computed = $this->computedBalance($account->id);

                Balance::create([
                    'account_id' => $account->id,
                    'checkin_id' => $checkin->id,
                    'amount' => $computed,
                    'checked_in_at' => $now,
                ]);
            }
        }

        Flux::toast(variant: 'success', text: 'Check-in recorded at '.$now->format('M j, Y g:i A'));

        $this->redirectRoute('dashboard', ['current_team' => Auth::user()->currentTeam->slug], navigate: true);
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl" level="1">New Check-in</flux:heading>
    <flux:text class="mt-1 max-w-prose">
        Record a snapshot of your current balances. Each check-in is timestamped and used to compute changes over time. Leave any account blank to skip it.
    </flux:text>

    <x-description.list class="mt-4">
        <x-description.term>Recording at</x-description.term>
        <x-description.details class="tabular-nums">{{ now()->format('M j, Y g:i A') }}</x-description.details>

        <x-description.term>Accounts</x:description.term>
        <x-description.details>{{ $this->accounts->count() }} {{ str()->plural('account', $this->accounts->count()) }}</x-description.details>
    </x-description.list>

    @if ($this->accounts->isEmpty())
        <flux:text class="mt-4">No accounts. <flux:link :href="route('accounts.create', ['current_team' => Auth::user()->currentTeam->slug])" wire:navigate>Create one</flux:link> to begin.</flux:text>
    @else
        <form wire:submit="submit" class="mt-8 space-y-8 max-w-xl">
            @foreach ($this->accounts as $account)
                @if ($account->type === AccountType::CreditCard && $account->credit_limit_in_cents !== null)
                    <flux:field>
                        <flux:label>{{ $account->name }} — Available credit</flux:label>
                        <flux:input wire:model.live="balances.{{ $account->id }}" type="number" step="0.01" />
                        <flux:description>Balance owed = credit limit ({{ $account->credit_limit }}) − available credit.</flux:description>
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
                    </flux:field>
                @endif
            @endforeach

            @if (($total = $this->totalComputedBalance()) !== null)
                <flux:separator />

                <x-description.list>
                    <x-description.term>Total across accounts</x-description.term>
                    <x-description.details class="tabular-nums">{{ CurrencyFormatter::cents($total) }}</x-description.details>

                    <x-description.term>Accounts entered</x-description.term>
                    <x-description.details>
                        {{ collect($this->accounts)->filter(fn ($a) => isset($this->balances[$a->id]) && $this->balances[$a->id] !== '')->count() }} of {{ $this->accounts->count() }}
                    </x-description.details>
                </x-description.list>
            @endif

            <flux:button variant="primary" type="submit">Record check-in</flux:button>
        </form>
    @endif
</section>
