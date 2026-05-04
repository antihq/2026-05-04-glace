<x-layouts::app :title="__('Register')">
    <div>
        <div class="flex items-center gap-3">
            <flux:heading class="whitespace-nowrap">{{ __('Register') }}</flux:heading>
            <flux:separator />
        </div>

        <div class="mt-8 max-w-md">
            <x-auth-session-status :status="session('status')" />

            <form method="POST" action="{{ route('register.store') }}" class="space-y-5">
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

                    <div class="flex items-center">
                        <flux:button size="sm" type="submit" variant="primary" color="emerald" icon:trailing="arrow-right" data-test="register-user-button">
                            {{ __('Create account') }}
                        </flux:button>
                    </div>
            </form>

                <flux:text class="mt-4">
                    Have an account? <flux:link :href="route('login')" wire:navigate>Log in</flux:link>
                </flux:text>
        </div>

        <div class="flex items-center mt-8">
            <flux:button size="sm" href="/" wire:navigate icon="arrow-left" class="rounded-full!">
                {{ __('Return to home') }}
            </flux:button>
            <flux:separator class="ml-3" />
        </div>
    </div>
</x-layouts::app>
