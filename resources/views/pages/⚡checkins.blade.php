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

<div class="flex flex-col gap-3">
    <div class="flex items-center gap-3">
        <flux:heading class="whitespace-nowrap">Check-in History</flux:heading>
        <flux:separator />
    </div>

    @if ($this->checkins->isEmpty())
        <p class="text-sm text-zinc-500">
            No check-ins recorded.
            <flux:link :href="route('checkin', ['current_team' => Auth::user()->currentTeam->slug])" wire:navigate>Check in now</flux:link>
        </p>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Date</flux:table.column>
                <flux:table.column class="text-right">Accounts</flux:table.column>
                <flux:table.column class="text-right">Total</flux:table.column>
                <flux:table.column class="text-right"></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->checkins as $checkin)
                    <flux:table.row>
                        <flux:table.cell>
                            {{ $checkin->checked_in_at->format('M j, Y g:i A') }}
                            <span class="text-zinc-400 text-xs ml-1">{{ $checkin->checked_in_at->diffForHumans() }}</span>
                        </flux:table.cell>
                        <flux:table.cell class="text-right tabular-nums">{{ $checkin->balances->count() }}</flux:table.cell>
                        <flux:table.cell class="text-right tabular-nums">{{ $this->formatCents($checkin->balances->sum('amount_in_cents')) }}</flux:table.cell>
                        <flux:table.cell class="text-right">
                            <flux:link :href="route('checkins.edit', ['current_team' => Auth::user()->currentTeam->slug, 'checkin' => $checkin->id])" wire:navigate size="sm">
                                Edit
                            </flux:link>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
