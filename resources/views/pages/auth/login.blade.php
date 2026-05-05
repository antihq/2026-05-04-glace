<x-layouts::app :title="__('Log in')">
    <div>
        <div class="flex items-center gap-3">
            <flux:heading class="whitespace-nowrap">{{ __('Log in') }}</flux:heading>
            <flux:separator />
        </div>

        <div class="mt-8 max-w-md">
            <x-auth-session-status :status="session('status')" />

            <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                @csrf

                <flux:input
                    size="sm"
                    name="email"
                    :label="__('Email address')"
                    :value="old('email')"
                    type="email"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="email@example.com"
                />

                <div class="relative">
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

                    @if (Route::has('password.request'))
                        <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                            {{ __('Forgot your password?') }}
                        </flux:link>
                    @endif
                </div>

                <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

                <div class="flex items-center">
                    <flux:button size="sm" variant="primary" color="emerald" icon:trailing="arrow-right" type="submit" data-test="login-button">
                        {{ __('Log in') }}
                    </flux:button>
                </div>
            </form>

            @if (Route::has('register'))
                <flux:text class="mt-4">
                    No account? <flux:link :href="route('register')" wire:navigate>Sign up</flux:link>
                </flux:text>
            @endif
        </div>

        <div class="flex items-center mt-8">
            <flux:button size="sm" href="/" wire:navigate icon="arrow-left" class="rounded-full!">
                {{ __('Return to home') }}
            </flux:button>
            <flux:separator class="ml-3" />
        </div>
    </div>
</x-layouts::app>
