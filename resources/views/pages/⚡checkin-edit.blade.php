<?php

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
        $this->accounts = Account::where('team_id', Auth::user()->currentTeam->id)->ordered()->get();

        foreach ($checkinModel->balances as $balance) {
            $this->balances[$balance->account_id] = $balance->amount;
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

        $this->redirectRoute('checkins', ['current_team' => Auth::user()->currentTeam->slug], navigate: true);
    }

    public function delete(): void
    {
        $checkin = Checkin::where('id', $this->checkinId)
            ->where('team_id', Auth::user()->currentTeam->id)
            ->firstOrFail();

        $checkin->delete();

        Flux::toast(variant: 'success', text: 'Check-in deleted.');

        $this->redirectRoute('checkins', ['current_team' => Auth::user()->currentTeam->slug], navigate: true);
    }
}; ?>

<div class="flex flex-col gap-3">
    <div class="flex items-center gap-3">
        <flux:heading class="whitespace-nowrap">Edit Check-in</flux:heading>
        <flux:separator />
    </div>

    <div class="text-sm text-zinc-500">
        {{ $this->checkedInAt }}
        &middot; <flux:link :href="route('checkins', ['current_team' => Auth::user()->currentTeam->slug])" wire:navigate>Back to history</flux:link>
    </div>

    <div class="text-sm">Update balance amounts or leave blank to remove an account from this check-in.</div>

    <form wire:submit="update" class="flex flex-col gap-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach ($this->accounts as $account)
                <flux:input
                    wire:model="balances.{{ $account->id }}"
                    :label="$account->name"
                    type="number"
                    step="0.01"
                    placeholder="Leave blank to remove"
                    size="sm"
                />
            @endforeach
        </div>

        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit">Save Changes</flux:button>
            <flux:button size="sm" variant="danger" wire:click="delete" wire:confirm="Are you sure you want to delete this check-in? This cannot be undone.">
                Delete Check-in
            </flux:button>
        </div>
    </form>
</div>
