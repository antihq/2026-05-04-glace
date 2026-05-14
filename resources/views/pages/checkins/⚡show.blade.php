<?php

use App\Models\Balance;
use App\Models\Checkin;
use App\Support\CurrencyFormatter;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public int $checkinId;

    public function mount($checkin): void
    {
        $checkinModel = Checkin::where('team_id', Auth::user()->currentTeam->id)
            ->where('id', $checkin)
            ->with('balances.account')
            ->firstOrFail();

        $this->checkinId = $checkinModel->id;
    }

    #[Computed]
    public function checkin()
    {
        return Checkin::where('team_id', Auth::user()->currentTeam->id)
            ->where('id', $this->checkinId)
            ->with('balances.account')
            ->firstOrFail();
    }

    #[Computed]
    public function total(): int
    {
        return $this->checkin->balances->sum('amount_in_cents');
    }

    #[Computed]
    public function previousBalances()
    {
        $prevCheckin = Checkin::where('team_id', Auth::user()->currentTeam->id)
            ->where('checked_in_at', '<', $this->checkin->checked_in_at)
            ->orderByDesc('checked_in_at')
            ->first();

        if (! $prevCheckin) {
            return collect();
        }

        return Balance::where('checkin_id', $prevCheckin->id)
            ->get()
            ->keyBy('account_id');
    }

    public function delete(): void
    {
        $checkin = Checkin::where('id', $this->checkinId)
            ->where('team_id', Auth::user()->currentTeam->id)
            ->firstOrFail();

        Flux::toast(variant: 'success', text: $checkin->balances->count().' balance records deleted.');

        $checkin->delete();

        $this->redirectRoute('checkins.index', ['current_team' => Auth::user()->currentTeam->slug], navigate: true);
    }

    public function render()
    {
        return $this->view()->title('Check-in — '.$this->checkin->checked_in_at->format('M j, Y'));
    }
}; ?>

<section class="w-full">
    <div class="flex items-end justify-between gap-4">
        <flux:heading size="xl" level="1">Check-in — {{ $this->checkin->checked_in_at->format('M j, Y') }}</flux:heading>
        <flux:button variant="primary" :href="route('checkins.edit', ['current_team' => Auth::user()->currentTeam->slug, 'checkin' => $this->checkinId])" wire:navigate>
            Edit
        </flux:button>
    </div>

    <x-description.list class="mt-2.5">
        <x-description.term>Date</x-description.term>
        <x-description.details>
            {{ $this->checkin->checked_in_at->format('M j, Y g:i A') }} ({{ $this->checkin->checked_in_at->diffForHumans() }})
        </x-description.details>

        <x-description.term>Balances</x-description.term>
        <x-description.details>
            <flux:link :accent="false" :href="route('accounts.index', ['current_team' => Auth::user()->currentTeam->slug])" wire:navigate>
                {{ $this->checkin->balances->count() }} {{ str()->plural('account', $this->checkin->balances->count()) }} recorded
            </flux:link>
        </x-description.details>

        <x-description.term>Total</x-description.term>
        <x-description.details class="tabular-nums">{{ CurrencyFormatter::cents($this->total) }}</x-description.details>
    </x-description.list>

    <flux:heading level="2" class="mt-12">Balances</flux:heading>
    <flux:table class="mt-4">
        <flux:table.columns>
            <flux:table.column>Account</flux:table.column>
            <flux:table.column>Type</flux:table.column>
            <flux:table.column align="end">Amount</flux:table.column>
            <flux:table.column align="end">Change</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach ($this->checkin->balances as $balance)
                @php
                    $prev = $this->previousBalances->get($balance->account_id);
                    $change = $prev ? $balance->amount_in_cents - $prev->amount_in_cents : null;
                @endphp
                <flux:table.row>
                    <flux:table.cell class="relative">
                        <x-table-row-link :href="route('accounts.show', ['current_team' => Auth::user()->currentTeam->slug, 'account' => $balance->account_id])" wire:navigate :first="true" />
                        {{ $balance->account->name }}
                    </flux:table.cell>
                    <flux:table.cell class="relative">
                        <x-table-row-link :href="route('accounts.show', ['current_team' => Auth::user()->currentTeam->slug, 'account' => $balance->account_id])" wire:navigate />
                        <flux:badge color="zinc" size="sm" inset="top bottom">{{ $balance->account->type->label() }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="relative" align="end">
                        <x-table-row-link :href="route('accounts.show', ['current_team' => Auth::user()->currentTeam->slug, 'account' => $balance->account_id])" wire:navigate />
                        <span class="tabular-nums">{{ CurrencyFormatter::cents($balance->amount_in_cents) }}</span>
                    </flux:table.cell>
                    <flux:table.cell class="relative" align="end">
                        <x-table-row-link :href="route('accounts.show', ['current_team' => Auth::user()->currentTeam->slug, 'account' => $balance->account_id])" wire:navigate />
                        <span class="tabular-nums">
                            @if ($change !== null && $change !== 0)
                                <span class="{{ $change > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ CurrencyFormatter::deltaCents($change) }}
                                </span>
                            @else
                                &mdash;
                            @endif
                        </span>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:separator class="mt-12" />

    <div class="mt-4">
        <flux:button variant="danger" wire:click="delete" wire:confirm="Are you sure you want to delete this check-in? This cannot be undone.">
            Delete check-in
        </flux:button>
    </div>
</section>
