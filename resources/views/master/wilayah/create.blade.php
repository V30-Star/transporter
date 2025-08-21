@extends('layouts.app')

@section('title', 'Master Wilayah')

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
            <h2 class="text-2xl font-semibold text-gray-800 flex items-center space-x-2">
                <x-heroicon-o-globe-alt class="w-8 h-8 text-blue-600" />
                <span>Wilayah Baru</span>
            </h2>
            <form action="{{ route('wilayah.store') }}" method="POST">
                @csrf

                <div class="space-y-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium">Kode Wilayah</label>
                        <input type="text" name="fwilayahcode" value="{{ old('fwilayahcode') }}"
                            class="w-full border rounded px-3 py-2 @error('fwilayahcode') border-red-500 @enderror">
                        @error('fwilayahcode')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Nama Wilayah</label>
                        <input type="text" name="fwilayahname" value="{{ old('fwilayahname') }}"
                            class="w-full border rounded px-3 py-2 @error('fwilayahname') border-red-500 @enderror">
                        @error('fwilayahname')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <br>
                    <div class="md:col-span-2 flex justify-center items-center space-x-2">
                        <input type="checkbox" name="fnonactive" id="statusToggle"
                            class="form-checkbox h-5 w-5 text-indigo-600" {{ old('fnonactive') == '1' ? 'checked' : '' }}>
                        <label class="block text-sm font-medium">Non Aktif</label>
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
                    <button type="button" @click="window.location.href='{{ route('wilayah.index') }}'"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                        Keluar
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
