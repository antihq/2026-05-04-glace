<?php

use App\Models\Checkin;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Check-in History')] class extends Component
{
    #[Computed]
    public function checkins()
    {
        return Checkin::where('team_id', Auth::user()->currentTeam->id)
            ->with('balances')
            ->orderByDesc('checked_in_at')
            ->get();
    }

    private function formatCents(int $cents): string
    {
        $dollars = abs($cents / 100);

        return ($cents < 0 ? '-' : '').'$'.number_format($dollars, 2);
    }
}; ?>

<section class="w-full">
    <div class="flex items-end justify-between gap-4">
        <flux:heading size="xl" level="1">Check-in History</flux:heading>
        <flux:button variant="primary" :href="route('checkins.create', ['current_team' => Auth::user()->currentTeam->slug])" wire:navigate>Check In</flux:button>
    </div>

    <flux:table class="mt-8">
        <flux:table.columns>
            <flux:table.column>Date</flux:table.column>
            <flux:table.column align="end">Accounts</flux:table.column>
            <flux:table.column align="end">Total</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach ($this->checkins as $checkin)
                <flux:table.row>
                    <flux:table.cell class="relative">
                        <x-table-row-link :href="route('checkins.show', ['current_team' => Auth::user()->currentTeam->slug, 'checkin' => $checkin->id])" wire:navigate :first="true" />
                        {{ $checkin->checked_in_at->format('M j, Y g:i A') }} {{ $checkin->checked_in_at->diffForHumans() }}
                    </flux:table.cell>
                    <flux:table.cell class="relative" align="end">
                        <x-table-row-link :href="route('checkins.show', ['current_team' => Auth::user()->currentTeam->slug, 'checkin' => $checkin->id])" wire:navigate />
                        <span class="tabular-nums">{{ $checkin->balances->count() }} {{ str()->plural('account', $checkin->balances->count()) }}</span>
                    </flux:table.cell>
                    <flux:table.cell class="relative" align="end">
                        <x-table-row-link :href="route('checkins.show', ['current_team' => Auth::user()->currentTeam->slug, 'checkin' => $checkin->id])" wire:navigate />
                        <span class="tabular-nums">{{ $this->formatCents($checkin->balances->sum('amount_in_cents')) }}</span>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</section>
