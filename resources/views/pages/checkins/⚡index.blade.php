<?php

use App\Models\Checkin;
use App\Support\CurrencyFormatter;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Check-ins')] class extends Component
{
    use WithPagination;

    #[Computed]
    public function checkins()
    {
        return Checkin::where('team_id', Auth::user()->currentTeam->id)
            ->with('balances')
            ->orderByDesc('checked_in_at')
            ->paginate(25);
    }

    #[Computed]
    public function previousCheckinTotals()
    {
        $checkins = $this->checkins->items();

        if (empty($checkins)) {
            return [];
        }

        $firstOnPage = reset($checkins)->checked_in_at;
        $lastOnPage = end($checkins)->checked_in_at;

        $allCheckins = Checkin::where('team_id', Auth::user()->currentTeam->id)
            ->where('checked_in_at', '<=', $firstOnPage)
            ->with('balances')
            ->orderByDesc('checked_in_at')
            ->get();

        $ordered = $allCheckins->values();
        $prevMap = [];

        foreach ($ordered as $i => $c) {
            $prevMap[$c->id] = isset($ordered[$i + 1])
                ? $ordered[$i + 1]->balances->sum('amount_in_cents')
                : null;
        }

        return $prevMap;
    }
}; ?>

<section class="w-full">
    <div class="flex items-end justify-between gap-4">
        <flux:heading size="xl" level="1">Check-ins</flux:heading>
        <flux:button variant="primary" :href="route('checkins.create', ['current_team' => Auth::user()->currentTeam->slug])" wire:navigate>New Check-in</flux:button>
    </div>

    @if ($this->checkins->isEmpty())
        <flux:text class="mt-4">No check-ins recorded yet. <flux:link :href="route('checkins.create', ['current_team' => Auth::user()->currentTeam->slug])" wire:navigate>Record one</flux:link> to begin tracking changes.</flux:text>
    @else
        <flux:table :paginate="$this->checkins" class="mt-8">
            <flux:table.columns>
                <flux:table.column>Date</flux:table.column>
                <flux:table.column align="end">Accounts</flux:table.column>
                <flux:table.column align="end">Total</flux:table.column>
                <flux:table.column align="end">Change</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->checkins as $checkin)
                    @php
                        $total = $checkin->balances->sum('amount_in_cents');
                        $prevTotal = $this->previousCheckinTotals[$checkin->id] ?? null;
                        $delta = $prevTotal !== null ? $total - $prevTotal : null;
                    @endphp
                    <flux:table.row>
                        <flux:table.cell class="relative">
                            <x-table-row-link :href="route('checkins.show', ['current_team' => Auth::user()->currentTeam->slug, 'checkin' => $checkin->id])" wire:navigate :first="true" />
                            {{ $checkin->checked_in_at->format('M j, Y g:i A') }} <span class="text-zinc-500">{{ $checkin->checked_in_at->diffForHumans() }}</span>
                        </flux:table.cell>
                        <flux:table.cell class="relative" align="end">
                            <x-table-row-link :href="route('checkins.show', ['current_team' => Auth::user()->currentTeam->slug, 'checkin' => $checkin->id])" wire:navigate />
                            <span class="tabular-nums">{{ $checkin->balances->count() }} {{ str()->plural('account', $checkin->balances->count()) }}</span>
                        </flux:table.cell>
                        <flux:table.cell class="relative" align="end">
                            <x-table-row-link :href="route('checkins.show', ['current_team' => Auth::user()->currentTeam->slug, 'checkin' => $checkin->id])" wire:navigate />
                            <span class="tabular-nums">{{ CurrencyFormatter::cents($total) }}</span>
                        </flux:table.cell>
                        <flux:table.cell class="relative" align="end">
                            <x-table-row-link :href="route('checkins.show', ['current_team' => Auth::user()->currentTeam->slug, 'checkin' => $checkin->id])" wire:navigate />
                            <span class="tabular-nums">
                                @if ($delta !== null && $delta !== 0)
                                    <span class="{{ $delta > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ CurrencyFormatter::deltaCents($delta) }}
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
    @endif
</section>
