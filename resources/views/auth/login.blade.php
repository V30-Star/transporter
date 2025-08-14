<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div class="mb-4">
            <label for="fsysuserid" class="block text-gray-700 text-sm font-bold mb-2">Username</label>
            <input id="fsysuserid" class="w-full px-3 py-2 border rounded-md" type="text" name="fsysuserid"
                :value="old('fsysuserid')" required autofocus>
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label class="block text-gray-700 text-sm font-bold mb-2" for="password" :value="__('Password')" />

            <input id="password" class="w-full px-3 py-2 border rounded-md bg-white" type="password" name="password"
                required autocomplete="current-password">

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
