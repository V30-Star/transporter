@extends('layouts.app')

@section('title', $pageTitle)

@section('content')
    <div class="max-w-md mx-auto mt-10">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8 text-center">
            <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl shadow-inner">
                <i class="fa-solid fa-lock"></i>
            </div>

            <h1 class="text-2xl font-bold text-gray-800">{{ $pageTitle }}</h1>
            <p class="text-sm text-gray-500 mt-2 mb-6">
                Masukkan password khusus untuk mengakses dan mengubah <strong>Penamaan Perusahaan</strong>.
            </p>

            @if(session('error'))
                <div class="p-3.5 bg-red-50 border border-red-200 text-red-600 rounded-xl text-sm mb-5 flex items-center justify-center gap-2 text-left">
                    <i class="fa-solid fa-circle-exclamation flex-shrink-0"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <form action="{{ route('penamaanperusahaan.verify') }}" method="POST" class="space-y-4">
                @csrf
                <div class="text-left">
                    <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-gray-600 mb-1.5">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required autofocus
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                            placeholder="Masukkan password...">
                    </div>
                    @error('password')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-2 flex gap-3">
                    <a href="{{ route('dashboard') }}"
                        class="w-1/2 inline-flex justify-center items-center rounded-xl bg-gray-100 border border-gray-200 py-3 text-sm font-medium text-gray-700 hover:bg-gray-200 transition">
                        Batal
                    </a>
                    <button type="submit"
                        class="w-1/2 inline-flex justify-center items-center gap-2 rounded-xl bg-blue-600 py-3 text-sm font-medium text-white hover:bg-blue-700 shadow-md hover:shadow-lg transition">
                        <i class="fa-solid fa-key"></i>
                        Buka Akses
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
