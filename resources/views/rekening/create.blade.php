@extends('layouts.app')

@section('title', 'Master Rekening')

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

    <div x-data="{ open: true, selected: 'rekening' }">
        <div class="bg-white rounded shadow p-6 md:p-8 max-w-[700px] mx-auto">
            <form action="{{ route('rekening.store') }}" method="POST">
                @csrf

                <div class="space-y-4 mt-4">
                    <!-- Rekening Code -->
                    <div>
                        <label class="block text-sm font-medium">Kode Rekening</label>
                        <input type="text" name="frekeningcode" value="{{ old('frekeningcode') }}"
                            class="w-full border rounded px-3 py-2 @error('frekeningcode') border-red-500 @enderror">
                        @error('frekeningcode')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <!-- Rekening Name -->
                    <div>
                        <label class="block text-sm font-medium">Nama Rekening</label>
                        <input type="text" name="frekeningname" value="{{ old('frekeningname') }}"
                            class="w-full border rounded px-3 py-2 @error('frekeningname') border-red-500 @enderror">
                        @error('frekeningname')
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
                <!-- Action Buttons -->
                <div class="mt-6 flex justify-center space-x-4">
                    <!-- Save Button -->
                    <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                        <x-heroicon-o-check class="w-5 h-5 mr-2" />
                        Simpan
                    </button>

                    <!-- Cancel Button -->
                    <button type="button" @click="window.location.href='{{ route('rekening.index') }}'"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                        Keluar
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
