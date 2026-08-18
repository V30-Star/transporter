@extends('layouts.app')

@section('title', $pageTitle)

@section('content')
    <div class="min-h-[70vh] flex items-center justify-center px-4 py-8">
        <div class="w-full max-w-md">
            {{-- Card Utama --}}
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100/80 p-8 sm:p-10 relative overflow-hidden backdrop-blur-sm">
                {{-- Decorative top accent line --}}
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-500"></div>

                {{-- Icon Badge --}}
                <div class="flex justify-center mb-5">
                    <div class="relative">
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-500 text-white flex items-center justify-center text-2xl shadow-lg shadow-blue-500/30">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <div class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-amber-400 text-gray-900 flex items-center justify-center text-xs shadow">
                            <i class="fa-solid fa-key"></i>
                        </div>
                    </div>
                </div>

                {{-- Title & Subtitle --}}
                <div class="text-center mb-6">
                    <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">{{ $pageTitle }}</h1>
                    <p class="text-xs sm:text-sm text-gray-500 mt-2 leading-relaxed">
                        Halaman ini diproteksi. Masukkan password otorisasi untuk mengelola identitas perusahaan.
                    </p>
                </div>

                {{-- Alert Error --}}
                @if(session('error'))
                    <div class="p-3.5 bg-red-50/90 border border-red-200 text-red-700 rounded-xl text-xs sm:text-sm mb-5 flex items-start gap-2.5 shadow-sm">
                        <i class="fa-solid fa-circle-exclamation text-red-500 mt-0.5 flex-shrink-0 text-base"></i>
                        <span class="leading-snug">{{ session('error') }}</span>
                    </div>
                @endif

                {{-- Form --}}
                <form action="{{ route('penamaanperusahaan.verify') }}" method="POST" class="space-y-5" x-data="{ show: false }">
                    @csrf
                    <div>
                        <label for="password" class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-2">
                            Password Otorisasi
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i class="fa-solid fa-lock text-sm"></i>
                            </div>
                            <input :type="show ? 'text' : 'password'" id="password" name="password" required autofocus
                                class="w-full rounded-xl border border-gray-300 pl-10 pr-11 py-3 text-sm text-gray-800 placeholder-gray-400 transition-all focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-100 @error('password') border-red-400 focus:ring-red-100 @enderror"
                                placeholder="Masukkan password...">
                            <button type="button" @click="show = !show"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 transition focus:outline-none"
                                :title="show ? 'Sembunyikan password' : 'Lihat password'">
                                <i class="fa-solid" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                        @error('password')
                            <p class="text-xs text-red-500 mt-1.5 flex items-center gap-1">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Tombol Aksi --}}
                    <div class="pt-2 flex items-center gap-3">
                        <a href="{{ route('dashboard') }}"
                            class="w-1/3 inline-flex justify-center items-center rounded-xl bg-gray-100 border border-gray-200 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-200 transition">
                            Batal
                        </a>
                        <button type="submit"
                            class="w-2/3 inline-flex justify-center items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 py-3 text-sm font-semibold text-white hover:from-blue-700 hover:to-indigo-700 shadow-md hover:shadow-blue-500/25 hover:shadow-lg transition active:scale-[0.98]">
                            <i class="fa-solid fa-unlock-keyhole"></i>
                            Buka Akses
                        </button>
                    </div>
                </form>

                {{-- Security Footer Notice --}}
                <div class="mt-6 pt-5 border-t border-gray-100 text-center">
                    <span class="inline-flex items-center gap-1.5 text-[11px] text-gray-400 bg-gray-50 px-3 py-1 rounded-full border border-gray-200/60">
                        <i class="fa-solid fa-shield text-blue-500"></i>
                        Data terenkripsi menggunakan AES-256
                    </span>
                </div>
            </div>
        </div>
    </div>
@endsection
