@extends('layouts.app')

@section('title', "Pengaturan")

@section('content')
    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded shadow p-6 md:p-8">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold text-gray-800">{{ "Pengaturan Tema" }}</h1>
                <p class="mt-2 text-sm text-gray-600">
                    {{ "Pilih tampilan warna aplikasi. Preferensi akan otomatis disimpan untuk browser ini." }}
                </p>
            </div>

            <div x-data="{ theme: window.appTheme ? window.appTheme.get() : 'light' }"
                x-init="window.addEventListener('theme-changed', (event) => theme = event.detail.theme)"
                class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <button type="button" @click="window.appTheme.set('light')"
                    class="text-left rounded-xl border p-5 transition hover:shadow-md"
                    :class="theme === 'light' ? 'border-blue-500 ring-2 ring-blue-200 bg-blue-50' : 'border-gray-200 bg-white'">
                    <div class="flex items-center justify-between">
                        <span class="text-lg font-semibold text-gray-800">{{ "Mode Putih" }}</span>
                        <span class="text-sm font-semibold text-gray-500">LIGHT</span>
                    </div>
                    <p class="mt-3 text-sm text-gray-600">
                        {{ "Tampilan terang untuk penggunaan normal dan pencahayaan ruangan yang cukup." }}
                    </p>
                </button>

                <button type="button" @click="window.appTheme.set('dark')"
                    class="text-left rounded-xl border p-5 transition hover:shadow-md"
                    :class="theme === 'dark' ? 'border-blue-500 ring-2 ring-blue-200 bg-blue-50' : 'border-gray-200 bg-white'">
                    <div class="flex items-center justify-between">
                        <span class="text-lg font-semibold text-gray-800">{{ "Mode Hitam" }}</span>
                        <span class="text-sm font-semibold text-gray-500">DARK</span>
                    </div>
                    <p class="mt-3 text-sm text-gray-600">
                        {{ "Tampilan gelap untuk mengurangi silau saat dipakai di malam hari atau layar terang." }}
                    </p>
                </button>

                <a href="{{ route('editperiode.edit') }}"
                    class="text-left rounded-xl border border-gray-200 bg-white p-5 transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-lg font-semibold text-gray-800">{{ "Edit Periode" }}</span>
                        <span class="text-sm font-semibold text-gray-500">YYYYMM</span>
                    </div>
                    <p class="mt-3 text-sm text-gray-600">
                        {{ "Atur batas periode posting untuk create, edit, dan delete transaksi." }}
                    </p>
                </a>

                <a href="{{ route('systemsetting.edit') }}"
                    class="text-left rounded-xl border border-gray-200 bg-white p-5 transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-lg font-semibold text-gray-800">{{ "System Setting" }}</span>
                        <span class="text-sm font-semibold text-gray-500">SETINI</span>
                    </div>
                    <p class="mt-3 text-sm text-gray-600">
                        {{ "Atur identitas perusahaan, NPWP, penandatangan Invoice & PO, dan parameter sistem." }}
                    </p>
                </a>

                <a href="{{ route('penamaanperusahaan.index') }}"
                    class="text-left rounded-xl border border-gray-200 bg-white p-5 transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-lg font-semibold text-gray-800">{{ "Penamaan Perusahaan" }}</span>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded bg-blue-100 text-blue-700">TERKUNCI</span>
                    </div>
                    <p class="mt-3 text-sm text-gray-600">
                        {{ "Ubah nama perusahaan / header faktur yang terenkripsi dengan proteksi password." }}
                    </p>
                </a>
            </div>

        </div>
    </div>
@endsection

