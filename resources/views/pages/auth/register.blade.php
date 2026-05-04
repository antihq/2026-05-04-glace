<x-layouts::app :title="__('Register')">
    <div>
        <div class="flex items-center gap-3">
            <flux:heading class="whitespace-nowrap">{{ __('Register') }}</flux:heading>
            <flux:separator />
        </div>

        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
                <x-auth-session-status :status="session('status')" />

                <form method="POST" action="{{ route('register.store') }}" class="space-y-8">
                    @csrf

                    <flux:input
                        size="sm"
                        name="name"
                        :label="__('Name')"
                        :value="old('name')"
                        type="text"
                        required
                        autofocus
                        autocomplete="name"
                        :placeholder="__('Full name')"
                    />

                    <flux:input
                        size="sm"
                        name="email"
                        :label="__('Email address')"
                        :value="old('email')"
                        type="email"
                        required
                        autocomplete="email"
                        placeholder="email@example.com"
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

                    <div class="flex items-center justify-end">
                        <flux:button size="sm" type="submit" variant="primary" data-test="register-user-button">
                            {{ __('Create account') }}
                        </flux:button>
                    </div>
                </form>

                <div class="mt-4 space-x-1 rtl:space-x-reverse text-sm/6 text-zinc-600 dark:text-zinc-400">
                    <span>{{ __('Already have an account?') }}</span>
                    <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
                </div>
            </div>

            <div class="space-y-4">
                <h2 class="text-2xl font-bold tracking-tight leading-tight">All your account balances. One screen.</h2>
                <p class="text-zinc-500 dark:text-zinc-400 leading-relaxed">Stop logging into 5 different banking apps. Open Glance, enter your balances, see your total. That's it.</p>
            </div>
        </div>
    </div>
</x-layouts::app>
