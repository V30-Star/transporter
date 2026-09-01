<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

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
        <div class="mb-5">
            <x-input-label class="block text-sm font-medium text-gray-700" for="password" :value="__('Password')" />
            <input id="password"
                class="w-full px-5 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none transition duration-200 ease-in-out"
                type="password" name="password" required autocomplete="current-password"
                placeholder="Enter your password">
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-600" />
        </div>

        <!-- Captcha Field (4 Karakter) -->
        <div class="mb-6">
            <label for="captcha" class="block text-sm font-medium text-gray-700 mb-1.5">Kode Captcha</label>
            <div class="flex items-center gap-3">
                <input id="captcha"
                    class="w-1/2 h-12 px-4 border border-gray-300 rounded-lg uppercase tracking-widest text-center font-bold text-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none transition duration-200 ease-in-out shadow-sm"
                    type="text" name="captcha" maxlength="4" required autocomplete="off"
                    placeholder="4 digit">
                <div class="flex items-center gap-2 border border-gray-300 rounded-lg bg-gray-50 px-2 py-1 shadow-sm h-12">
                    <img src="{{ route('captcha') }}" id="captcha-img" alt="Captcha" class="h-10 w-36 rounded select-none cursor-pointer" onclick="refreshCaptcha()" title="Klik untuk ganti captcha">
                    <button type="button" onclick="refreshCaptcha()" title="Ganti Captcha"
                        class="p-1.5 text-gray-500 hover:text-indigo-600 hover:bg-gray-200 rounded transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                </div>
            </div>
            <x-input-error :messages="$errors->get('captcha')" class="mt-2 text-red-600" />
        </div>

        <!-- Submit Button -->
        <div class="flex justify-center mb-4">
            <x-primary-button
                class="w-full py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-300 transition duration-200 ease-in-out flex justify-center items-center">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>

    <script>
        function refreshCaptcha() {
            const img = document.getElementById('captcha-img');
            if (img) {
                img.src = '{{ route('captcha') }}?' + Date.now();
            }
            const input = document.getElementById('captcha');
            if (input) {
                input.value = '';
                input.focus();
            }
        }
    </script>
</x-guest-layout>
