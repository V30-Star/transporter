@extends('layouts.app')

@section('title', $pageTitle)

@section('content')
    <div class="max-w-3xl mx-auto pb-10">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
            {{-- Header --}}
            <div class="flex justify-between items-center px-6 py-5 border-b bg-gray-50/70">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-building-shield"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800">{{ $pageTitle }}</h1>
                        <p class="text-xs text-gray-500">
                            Header Faktur / Nama Perusahaan disimpan secara terenkripsi (AES-256) di database.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <form action="{{ route('penamaanperusahaan.lock') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-xs font-semibold px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg transition" title="Kunci Kembali Halaman">
                            <i class="fa-solid fa-lock mr-1"></i> Kunci Sesi
                        </button>
                    </form>
                    <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-2 rounded-lg transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="mx-6 mt-5 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-green-600"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="mx-6 mt-5 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-red-600"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <form action="{{ route('penamaanperusahaan.update') }}" method="POST" class="p-6 space-y-6">
                @csrf
                @method('PATCH')

                {{-- 1. Nama Perusahaan --}}
                <div class="border border-gray-200 rounded-xl p-5 bg-white space-y-3">
                    <label for="fproject" class="block text-sm font-semibold text-gray-800">
                        Header Faktur / Nama Perusahaan <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="fproject" name="fproject" value="{{ old('fproject', $projectName) }}" required
                        class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                        placeholder="Contoh: PT. DEMO VERSION">
                    @error('fproject')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-400">
                        Nama ini akan otomatis ditampilkan pada seluruh header cetakan faktur, surat jalan, PO, dan laporan.
                    </p>
                </div>

                {{-- 2. Ganti Password Menu --}}
                <div class="border border-gray-200 rounded-xl p-5 bg-white space-y-4">
                    <div class="flex items-center gap-2 text-sm font-semibold text-gray-800 border-b pb-2">
                        <i class="fa-solid fa-key text-blue-600"></i>
                        <span>Ubah Password Menu Penamaan Perusahaan (Opsional)</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="new_password" class="block text-xs font-semibold text-gray-600 mb-1">Password Baru</label>
                            <input type="password" id="new_password" name="new_password"
                                class="w-full rounded-xl border border-gray-300 px-3.5 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                placeholder="Kosongkan jika tidak diubah">
                            @error('new_password')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="new_password_confirmation" class="block text-xs font-semibold text-gray-600 mb-1">Konfirmasi Password Baru</label>
                            <input type="password" id="new_password_confirmation" name="new_password_confirmation"
                                class="w-full rounded-xl border border-gray-300 px-3.5 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                placeholder="Ulangi password baru">
                        </div>
                    </div>
                    <p class="text-xs text-gray-400">
                        Password disimpan terenkripsi di kolom <code>fpasswordperusahaan</code>.
                    </p>
                </div>

                {{-- Action Buttons --}}
                <div class="flex justify-end items-center gap-3 pt-3 border-t">
                    <a href="{{ route('dashboard') }}"
                        class="inline-flex items-center rounded-xl bg-gray-100 border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-200 transition">
                        Batal
                    </a>
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-blue-700 shadow-md hover:shadow-lg transition">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
