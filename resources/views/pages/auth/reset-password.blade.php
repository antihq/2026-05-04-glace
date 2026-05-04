<x-layouts::app :title="__('Reset password')">
    <div>
        <div class="flex items-center gap-3">
            <flux:heading class="whitespace-nowrap">{{ __('Reset password') }}</flux:heading>
            <flux:separator />
        </div>

        <div class="mt-8 max-w-md">
            <x-auth-session-status :status="session('status')" />

            <p class="text-sm leading-relaxed mb-5">Choose a new password. Minimum 8 characters. Mix uppercase, lowercase, numbers, and symbols for a stronger password. After resetting, you'll be redirected to the login page.</p>

            <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
                @csrf

                <input type="hidden" name="token" value="{{ request()->route('token') }}">

                <flux:input
                    size="sm"
                    name="email"
                    value="{{ request('email') }}"
                    :label="__('Email')"
                    type="email"
                    required
                    autocomplete="email"
                />

                <flux:input
                    size="sm"
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Password')"
                    viewable
                />

                <flux:input
                    size="sm"
                    name="password_confirmation"
                    :label="__('Confirm password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Confirm password')"
                    viewable
                />

                    <div class="flex items-center">
                        <flux:button size="sm" type="submit" variant="primary" color="emerald" icon:trailing="arrow-right" data-test="reset-password-button">
                            {{ __('Reset password') }}
                        </flux:button>
                    </div>
            </form>
        </div>
    </div>
</x-layouts::app>
