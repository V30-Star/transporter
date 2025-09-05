@extends('layouts.app')

@section('title', 'Master Gudang')

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
            <form action="{{ route('gudang.store') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <!-- Field 1: Cabang (Dropdown) -->
                    <div>
                        <label class="block text-sm font-medium">Cabang</label>
                        <select name="fcabangkode"
                            class="w-full border rounded px-3 py-2 @error('fcabangkode') border-red-500 @enderror">
                            <option value="">Pilih Cabang</option>
                            @foreach ($cabangOptions as $cabang)
                                <option value="{{ $cabang->fcabangkode }}"
                                    {{ old('fcabangkode') == $cabang->fcabangkode ? 'selected' : '' }}>
                                    {{ $cabang->fcabangname }}
                                </option>
                            @endforeach
                        </select>
                        @error('fcabangkode')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Field 2: Kode Gudang -->
                    <div>
                        <label class="block text-sm font-medium">Kode Gudang</label>
                        <input type="text" name="fgudangcode" value="{{ old('fgudangcode') }}"
                            class="w-full border rounded px-3 py-2 @error('fgudangcode') border-red-500 @enderror">
                        @error('fgudangcode')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Field 3: Nama Gudang -->
                    <div>
                        <label class="block text-sm font-medium">Nama Gudang</label>
                        <input type="text" name="fgudangname" value="{{ old('fgudangname') }}"
                            class="w-full border rounded px-3 py-2 @error('fgudangname') border-red-500 @enderror">
                        @error('fgudangname')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Field 4: Alamat -->
                    <div>
                        <label class="block text-sm font-medium">Alamat</label>
                        <input type="text" name="faddress" value="{{ old('faddress') }}"
                            class="w-full border rounded px-3 py-2 @error('faddress') border-red-500 @enderror">
                        @error('faddress')
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
                    <button type="button" @click="window.location.href='{{ route('gudang.index') }}'"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                        Keluar
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
