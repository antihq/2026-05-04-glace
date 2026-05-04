<x-layouts::app :title="config('app.name', 'Glace')">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-12">
        <div class="space-y-6 text-sm leading-relaxed">
            <div class="space-y-3">
                <p>All your account balances. One screen. See how they change over time.</p>
                <p>Stop logging into 5 different banking apps. Open Glance, enter your balances, see your total. That's it.</p>
            </div>

            <div class="space-y-3">
                <p>You create accounts — checking, savings, credit cards, investments, whatever you track. Each account belongs to a team. You check in periodically with the current balance for each account.</p>
                <p>Glace totals them and shows how each one changed over time. No bank connections. No automatic imports. You open the app, type the numbers, close the app.</p>
            </div>

            <div class="space-y-1">
                <p>Accounts belong to teams. Invite collaborators with owner, admin, or member roles.</p>
            </div>

            @if (Route::has('login') && !auth()->check())
                <div class="pt-2">
                    <flux:link :href="route('register')" wire:navigate>Create an account</flux:link>
                    <span class="text-zinc-400 dark:text-zinc-500"> · </span>
                    <flux:link :href="route('login')" wire:navigate>Log in</flux:link>
                </div>
            @endif
        </div>

        <div class="text-sm">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wider text-zinc-400 dark:text-zinc-500 border-b border-zinc-200 dark:border-zinc-700">
                        <th class="pb-2 pr-4 font-medium">Account</th>
                        <th class="pb-2 font-medium text-right">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    <tr>
                        <td class="py-2.5 pr-4">Checking</td>
                        <td class="py-2.5 text-right tabular-nums">$4,230.51</td>
                    </tr>
                    <tr>
                        <td class="py-2.5 pr-4">Savings</td>
                        <td class="py-2.5 text-right tabular-nums">$12,890.00</td>
                    </tr>
                    <tr>
                        <td class="py-2.5 pr-4">Credit Card</td>
                        <td class="py-2.5 text-right tabular-nums text-red-600 dark:text-red-400">−$1,204.33</td>
                    </tr>
                    <tr>
                        <td class="py-2.5 pr-4">Investment</td>
                        <td class="py-2.5 text-right tabular-nums">$34,100.00</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="border-t border-zinc-300 dark:border-zinc-600 font-medium">
                        <td class="pt-3 pr-4">Total</td>
                        <td class="pt-3 text-right tabular-nums">$50,016.18</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</x-layouts::app>
