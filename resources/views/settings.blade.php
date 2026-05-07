@extends('layouts.app')

@section('title', __('ui.settings'))

@section('content')
    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded shadow p-6 md:p-8">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold text-gray-800">{{ __('ui.theme_settings') }}</h1>
                <p class="mt-2 text-sm text-gray-600">
                    {{ __('ui.select_theme') }}
                </p>
            </div>

            <div x-data="{ theme: window.appTheme ? window.appTheme.get() : 'light' }"
                x-init="window.addEventListener('theme-changed', (event) => theme = event.detail.theme)"
                class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <button type="button" @click="window.appTheme.set('light')"
                    class="text-left rounded-xl border p-5 transition hover:shadow-md"
                    :class="theme === 'light' ? 'border-blue-500 ring-2 ring-blue-200 bg-blue-50' : 'border-gray-200 bg-white'">
                    <div class="flex items-center justify-between">
                        <span class="text-lg font-semibold text-gray-800">{{ __('ui.light_mode') }}</span>
                        <span class="text-sm font-semibold text-gray-500">LIGHT</span>
                    </div>
                    <p class="mt-3 text-sm text-gray-600">
                        {{ __('ui.light_mode_desc') }}
                    </p>
                </button>

                <button type="button" @click="window.appTheme.set('dark')"
                    class="text-left rounded-xl border p-5 transition hover:shadow-md"
                    :class="theme === 'dark' ? 'border-blue-500 ring-2 ring-blue-200 bg-blue-50' : 'border-gray-200 bg-white'">
                    <div class="flex items-center justify-between">
                        <span class="text-lg font-semibold text-gray-800">{{ __('ui.dark_mode') }}</span>
                        <span class="text-sm font-semibold text-gray-500">DARK</span>
                    </div>
                    <p class="mt-3 text-sm text-gray-600">
                        {{ __('ui.dark_mode_desc') }}
                    </p>
                </button>
            </div>

            <div class="mt-8 mb-6">
                <h2 class="text-2xl font-semibold text-gray-800">{{ __('ui.language_settings') }}</h2>
                <p class="mt-2 text-sm text-gray-600">
                    {{ __('ui.select_language') }}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <button type="button" onclick="window.appLanguage.set('id')"
                    class="text-left rounded-xl border p-5 transition hover:shadow-md theme-toggle-button !block !w-full"
                    id="settings-language-id">
                    <div class="flex items-center justify-between">
                        <span class="text-lg font-semibold text-gray-800">{{ __('ui.indonesian') }}</span>
                        <span class="text-sm font-semibold text-gray-500">ID</span>
                    </div>
                </button>

                <button type="button" onclick="window.appLanguage.set('en')"
                    class="text-left rounded-xl border p-5 transition hover:shadow-md theme-toggle-button !block !w-full"
                    id="settings-language-en">
                    <div class="flex items-center justify-between">
                        <span class="text-lg font-semibold text-gray-800">{{ __('ui.english') }}</span>
                        <span class="text-sm font-semibold text-gray-500">EN</span>
                    </div>
                </button>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const locale = @json(app()->getLocale());
                    const idButton = document.getElementById('settings-language-id');
                    const enButton = document.getElementById('settings-language-en');

                    if (idButton) {
                        idButton.classList.toggle('ring-2', locale === 'id');
                        idButton.classList.toggle('ring-blue-300', locale === 'id');
                        idButton.classList.toggle('border-blue-500', locale === 'id');
                    }

                    if (enButton) {
                        enButton.classList.toggle('ring-2', locale === 'en');
                        enButton.classList.toggle('ring-blue-300', locale === 'en');
                        enButton.classList.toggle('border-blue-500', locale === 'en');
                    }
                });
            </script>
        </div>
    </div>
@endsection
