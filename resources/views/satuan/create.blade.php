@extends('layouts.app')

@section('title', 'Master Satuan')

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
        <div class="bg-white rounded shadow p-6 md:p-8 max-w-[700px] mx-auto">
            <form action="{{ route('satuan.store') }}" method="POST">
                @csrf

                <div class="space-y-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium">Kode Satuan</label>
                        <input type="text" name="fsatuancode" value="{{ old('fsatuancode') }}"
                            class="w-full border rounded px-3 py-2 @error('fsatuancode') border-red-500 @enderror"
                            maxlength="10">
                        @error('fsatuancode')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Nama Satuan</label>
                        <input type="text" name="fsatuanname" value="{{ old('fsatuanname') }}"
                            class="w-full border rounded px-3 py-2 @error('fsatuanname') border-red-500 @enderror"
                            autofocus>
                        @error('fsatuanname')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <br>
                    <div class="md:col-span-2 flex justify-center items-center space-x-2">
                        <label for="statusToggle"
                            class="flex items-center justify-between w-40 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                            <span class="text-sm font-medium">Non Aktif</span>
                            <input type="checkbox" name="fnonactive" id="statusToggle"
                                class="h-5 w-5 text-green-600 rounded focus:ring-green-500"
                                {{ old('fnonactive') == '1' ? 'checked' : '' }}>
                        </label>
                    </div>
                </div>
                <br>
                <!-- Tombol Aksi -->
                <div class="mt-6 flex justify-center space-x-4">
                    <!-- Simpan -->
                    <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                        <x-heroicon-o-check class="w-5 h-5 mr-2" />
                        Simpan
                    </button>

                    <!-- Keluar -->
                    <button type="button" @click="window.location.href='{{ route('satuan.index') }}'"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                        Keluar
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
