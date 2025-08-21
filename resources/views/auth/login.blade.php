<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <h2 class="text-3xl font-semibold text-center text-indigo-600 mb-6">My App</h2>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Username Field -->
        <div class="mb-6">
            <label for="fsysuserid" class="block text-sm font-medium text-gray-700">Username</label>
            <input id="fsysuserid"
                class="w-full px-5 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none transition duration-200 ease-in-out"
                type="text" name="fsysuserid" value="{{ old('fsysuserid') }}" required autofocus
                placeholder="Enter your username">
            <x-input-error :messages="$errors->get('fsysuserid')" class="mt-2 text-red-600" />
        </div>

        <!-- Password Field -->
        <div class="mb-6">
            <x-input-label class="block text-sm font-medium text-gray-700" for="password" :value="__('Password')" />
            <input id="password"
                class="w-full px-5 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none transition duration-200 ease-in-out"
                type="password" name="password" required autocomplete="current-password"
                placeholder="Enter your password">
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-600" />
        </div>

        <!-- Forgot Password Link -->
        <div class="flex justify-between items-center mb-6">
            @if (Route::has('password.request'))
                <a class="text-sm text-indigo-600 hover:text-indigo-800" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <!-- Submit Button -->
        <div class="flex justify-center mb-4">
            <x-primary-button
                class="w-full py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-300 transition duration-200 ease-in-out flex justify-center items-center">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
