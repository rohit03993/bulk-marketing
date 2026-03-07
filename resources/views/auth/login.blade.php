<x-guest-layout title="Log in">
    <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200/60">
        <h1 class="text-xl font-semibold text-slate-900">Welcome back</h1>
        <p class="mt-1 text-sm text-slate-500">Sign in to your account to continue.</p>

        <x-auth-session-status class="mt-4 p-3 rounded-lg bg-emerald-50 text-emerald-800 text-sm" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
            @csrf

            <div>
                <x-input-label for="email" :value="__('Email')" class="text-slate-700 font-medium" />
                <x-text-input
                    id="email"
                    type="email"
                    name="email"
                    :value="old('email')"
                    required
                    autofocus
                    autocomplete="username"
                    class="mt-1.5 block w-full rounded-lg border-slate-300 focus:border-slate-500 focus:ring-slate-500"
                />
                <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="password" :value="__('Password')" class="text-slate-700 font-medium" />
                <x-text-input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    class="mt-1.5 block w-full rounded-lg border-slate-300 focus:border-slate-500 focus:ring-slate-500"
                />
                <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
            </div>

            <div class="flex items-center justify-between">
                <label for="remember_me" class="inline-flex items-center cursor-pointer">
                    <input
                        id="remember_me"
                        type="checkbox"
                        name="remember"
                        class="rounded border-slate-300 text-slate-600 shadow-sm focus:ring-slate-500"
                    />
                    <span class="ml-2 text-sm text-slate-600">{{ __('Remember me') }}</span>
                </label>
                @if (Route::has('password.request'))
                    <a
                        href="{{ route('password.request') }}"
                        class="text-sm font-medium text-slate-600 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 rounded"
                    >
                        {{ __('Forgot password?') }}
                    </a>
                @endif
            </div>

            <button
                type="submit"
                class="w-full flex justify-center items-center px-4 py-3 rounded-lg bg-slate-800 font-semibold text-sm text-white shadow-sm hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition-colors"
            >
                {{ __('Log in') }}
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-500">
            Don't have an account?
            <a href="{{ route('register') }}" class="font-medium text-slate-700 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 rounded">
                {{ __('Register') }}
            </a>
        </p>
    </div>
</x-guest-layout>
