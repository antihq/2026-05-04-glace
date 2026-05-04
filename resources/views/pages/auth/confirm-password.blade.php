<x-layouts::app :title="__('Confirm password')">
    <div>
        <div class="flex items-center gap-3">
            <flux:heading class="whitespace-nowrap">{{ __('Confirm password') }}</flux:heading>
            <flux:separator />
        </div>

        <div class="mt-8 max-w-md">
            <x-auth-session-status :status="session('status')" />

            <p class="text-sm leading-relaxed mb-5">You're confirming your password because you're about to perform a sensitive action — changing your email, adjusting security settings, or deleting your account. Enter your current password to continue.</p>

            <form method="POST" action="{{ route('password.confirm.store') }}" class="space-y-5">
                @csrf

                <flux:input
                    size="sm"
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Password')"
                    viewable
                />

                    <div class="flex items-center">
                        <flux:button size="sm" variant="primary" color="emerald" icon:trailing="arrow-right" type="submit" data-test="confirm-password-button">
                            {{ __('Confirm') }}
                        </flux:button>
                    </div>
            </form>
        </div>
    </div>
</x-layouts::app>
