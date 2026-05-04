<x-layouts::app :title="__('Log in')">
    <div>
        <div class="flex items-center gap-3">
            <flux:heading class="whitespace-nowrap">{{ __('Log in') }}</flux:heading>
            <flux:separator />
        </div>

        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
                <x-auth-session-status :status="session('status')" />

                <form method="POST" action="{{ route('login.store') }}" class="space-y-8">
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

                    <div class="flex items-center justify-end">
                        <flux:button size="sm" variant="primary" type="submit" data-test="login-button">
                            {{ __('Log in') }}
                        </flux:button>
                    </div>
                </form>

                @if (Route::has('register'))
                    <div class="mt-4 space-x-1 rtl:space-x-reverse text-sm/6 text-zinc-600 dark:text-zinc-400">
                        <span>{{ __('Don\'t have an account?') }}</span>
                        <flux:link :href="route('register')" wire:navigate>{{ __('Sign up') }}</flux:link>
                    </div>
                @endif
            </div>

            <div class="space-y-4">
                <h2 class="text-2xl font-bold tracking-tight leading-tight">All your account balances. One screen.</h2>
                <p class="text-zinc-500 dark:text-zinc-400 leading-relaxed">Stop logging into 5 different banking apps. Open Glance, enter your balances, see your total. That's it.</p>
            </div>
        </div>
    </div>
</x-layouts::app>
