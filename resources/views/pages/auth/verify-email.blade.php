<x-layouts::app :title="__('Email verification')">
    <div>
        <div class="flex items-center gap-3">
            <flux:heading class="whitespace-nowrap">{{ __('Email verification') }}</flux:heading>
            <flux:separator />
        </div>

        <div class="mt-8 max-w-md space-y-4">
            <p class="text-sm">
                {{ __('Please verify your email address by clicking on the link we just emailed to you.') }}
                Check your inbox and spam folder. The verification link expires after 60 minutes.
            </p>

            @if (session('status') == 'verification-link-sent')
                <p class="text-sm font-medium !dark:text-green-400 !text-green-600">
                    {{ __('A new verification link has been sent to the email address you provided during registration.') }}
                </p>
            @endif

            <div class="flex items-center gap-3">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <flux:button size="sm" type="submit" variant="primary" color="emerald" icon:trailing="arrow-right">
                        {{ __('Resend verification email') }}
                    </flux:button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <flux:button size="sm" variant="ghost" type="submit" data-test="logout-button">
                        {{ __('Log out') }}
                    </flux:button>
                </form>
            </div>
        </div>
    </div>
</x-layouts::app>
