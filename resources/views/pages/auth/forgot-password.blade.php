<x-layouts::app :title="__('Forgot password')">
    <div>
        <div class="flex items-center gap-3">
            <flux:heading class="whitespace-nowrap">{{ __('Forgot password') }}</flux:heading>
            <flux:separator />
        </div>

        <div class="mt-8 max-w-md">
            <x-auth-session-status :status="session('status')" />

            <p class="text-sm leading-relaxed mb-5">Enter your email address and we'll send a link to reset your password. The link expires after 60 minutes. Check your spam folder if you don't receive it.</p>

            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                @csrf

                <flux:input
                    size="sm"
                    name="email"
                    :label="__('Email address')"
                    type="email"
                    required
                    autofocus
                    placeholder="email@example.com"
                />

                <div class="flex items-center">
                    <flux:button size="sm" variant="primary" color="emerald" icon:trailing="arrow-right" type="submit" data-test="email-password-reset-link-button">
                        {{ __('Email password reset link') }}
                    </flux:button>
                </div>
            </form>
        </div>

        <div class="flex items-center mt-8">
            <flux:button size="sm" :href="route('login')" wire:navigate icon="arrow-left" class="rounded-full!">
                {{ __('Return to Log in') }}
            </flux:button>
            <flux:separator class="ml-3" />
        </div>
    </div>
</x-layouts::app>
