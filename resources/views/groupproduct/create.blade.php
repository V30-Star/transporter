@extends('layouts.app')

@section('title', 'Tambah Groupproduct')

@section('content')
    <style>
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
        }
    </style>

    <div x-data="{ open: true, selected: 'surat' }">
        <div x-show="open" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-cloak>
            <div class="bg-white w-full max-w-5xl p-6 rounded shadow relative overflow-y-auto max-h-screen">
                <!-- Tombol X -->
                <button type="button" @click="window.location.href='{{ route('groupproduct.index') }}'"
                    class="absolute top-4 right-6 text-gray-500 hover:text-red-600 text-xl font-bold">
                    &times;
                </button>
                <!-- Header -->
                <div class="mb-6 border-b pb-4">
                    <h2 class="text-2xl font-semibold text-gray-800 flex items-center space-x-2">
                        <x-heroicon-o-plus-circle class="w-6 h-6 text-blue-600" />
                        <span>Tambah Groupproduct</span>
                    </h2>
                </div>
                <form action="{{ route('groupproduct.store') }}" method="POST">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium">Kode Group</label>
                            <input type="text" name="fgroupcode" value="{{ old('fgroupcode') }}"
                                class="w-full border rounded px-3 py-2 @error('fgroupcode') border-red-500 @enderror">
                            @error('fgroupcode')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Nama Group</label>
                            <input type="text" name="fgroupname" value="{{ old('fgroupname') }}"
                                class="w-full border rounded px-3 py-2 @error('fgroupname') border-red-500 @enderror">
                            @error('fgroupname')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Checkbox for fnonactive -->
                    <div class="mt-4">
                        <label for="fnonactive" class="flex items-center space-x-2">
                            <input type="checkbox" name="fnonactive" id="fnonactive" class="form-checkbox"
                                {{ old('fnonactive') ? 'checked' : '' }}>
                            <span class="text-sm">Nonaktifkan Group</span>
                        </label>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="mt-6 flex justify-center space-x-4">
                        <!-- Simpan -->
                        <button type="submit"
                            class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                            <x-heroicon-o-check class="w-5 h-5 mr-2" />
                            Simpan
                        </button>

                        <!-- Keluar -->
                        <button type="button" @click="window.location.href='{{ route('groupproduct.index') }}'"
                            class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                            <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                            Keluar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
