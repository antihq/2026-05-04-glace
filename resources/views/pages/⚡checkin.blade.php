<?php

use App\Models\Account;
use App\Models\Balance;
use App\Models\Checkin;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Check In')] class extends Component {
    public int $currentIndex = 0;

    public array $balances = [];

    public bool $isFirst = true;

    public bool $isLast = false;

    public Collection $accounts;

    public function mount(): void
    {
        $this->accounts = Account::where('team_id', Auth::user()->currentTeam->id)->ordered()->get();
        $this->isLast = $this->accounts->count() <= 1;
    }

    protected function updateFlags(): void
    {
        $this->isFirst = $this->currentIndex === 0;
        $this->isLast = $this->currentIndex === $this->accounts->count() - 1;
    }

    protected function currentAccount(): ?Account
    {
        return $this->accounts[$this->currentIndex] ?? null;
    }

    public function next(): void
    {
        $account = $this->currentAccount();
        $value = $this->balances[$account->id] ?? null;

        if ($value !== null && $value !== '') {
            $this->validateOnly("balances.{$account->id}", [
                "balances.{$account->id}" => 'required|numeric',
            ]);
        }

        if ($this->isLast) {
            $this->submit();

            return;
        }

        $this->currentIndex++;
        $this->updateFlags();
    }

    public function skip(): void
    {
        unset($this->balances[$this->currentAccount()->id]);

        if ($this->isLast) {
            $this->submit();

            return;
        }

        $this->currentIndex++;
        $this->updateFlags();
    }

    public function back(): void
    {
        if (! $this->isFirst) {
            $this->currentIndex--;
            $this->updateFlags();
        }
    }

    public function submit(): void
    {
        $now = now();

        $checkin = Checkin::create([
            'team_id' => Auth::user()->currentTeam->id,
            'checked_in_at' => $now,
        ]);

        foreach ($this->balances as $accountId => $value) {
            if ($value !== null && $value !== '') {
                Balance::create([
                    'account_id' => $accountId,
                    'checkin_id' => $checkin->id,
                    'amount' => $value,
                    'checked_in_at' => $now,
                ]);
            }
        }

        Flux::toast(variant: 'success', text: __('Check-in complete!'));

        $this->redirectRoute('dashboard', ['current_team' => Auth::user()->currentTeam->slug], navigate: true);
    }
}; ?>

<div class="flex flex-col items-center justify-center min-h-[60vh]">
    @if ($this->accounts->isEmpty())
        <flux:callout class="max-w-md">
            <flux:callout.heading>{{ __('No accounts yet') }}</flux:callout.heading>
            <flux:callout.text>
                {{ __('You need to add at least one account before you can check in.') }}
                <flux:link :href="route('accounts', ['current_team' => Auth::user()->currentTeam->slug])" wire:navigate>{{ __('Go to Accounts') }}</flux:link>
            </flux:callout.text>
        </flux:callout>
    @else
        <div class="w-full max-w-sm space-y-8">
            <div class="space-y-2">
                <flux:progress :value="(($this->currentIndex + 1) / $this->accounts->count()) * 100" />
                <flux:text class="text-center text-sm text-zinc-500">
                    {{ $this->currentIndex + 1 }} {{ __('of') }} {{ $this->accounts->count() }}
                </flux:text>
            </div>

            <div class="space-y-6">
                <flux:heading size="xl" class="text-center block">
                    {{ $accounts[$currentIndex]->name }}
                </flux:heading>

                <flux:input
                    wire:model="balances.{{ $accounts[$currentIndex]->id }}"
                    type="number"
                    step="0.01"
                    placeholder="0.00"
                    size="lg"
                    class="text-center text-2xl"
                    autofocus
                    wire:key="input-{{ $accounts[$currentIndex]->id }}"
                />
            </div>

            <div class="flex items-center justify-between gap-3">
                @if (! $this->isFirst)
                    <flux:button variant="ghost" wire:click="back">
                        {{ __('Back') }}
                    </flux:button>
                @else
                    <div></div>
                @endif

                <div class="flex items-center gap-3">
                    <flux:button variant="ghost" wire:click="skip">
                        {{ __('Skip') }}
                    </flux:button>

                    <flux:button variant="primary" wire:click="next">
                        @if ($this->isLast)
                            {{ __('Finish') }}
                        @else
                            {{ __('Next') }}
                        @endif
                    </flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
