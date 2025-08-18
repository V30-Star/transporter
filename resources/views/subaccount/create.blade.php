@extends('layouts.app')

@section('title', 'Master Subaccount')

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

    <div x-data="{ open: true, selected: 'subaccount' }">
        <div class="bg-white rounded shadow p-6 md:p-8 max-w-[700px] mx-auto">
            <h2 class="text-2xl font-semibold text-gray-800 flex items-center space-x-2">
                <x-heroicon-o-plus-circle class="w-6 h-6 text-blue-600" />
                <span>Subaccount Baru</span>
            </h2>

            <form action="{{ route('subaccount.store') }}" method="POST">
                @csrf

                <div class="space-y-4 mt-4">
                    <!-- Subaccount Code Field -->
                    <div>
                        <label class="block text-sm font-medium">Kode Subaccount</label>
                        <input type="text" name="fsubaccountcode" value="{{ old('fsubaccountcode') }}"
                            class="w-full border rounded px-3 py-2 @error('fsubaccountcode') border-red-500 @enderror">
                        @error('fsubaccountcode')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Subaccount Name Field -->
                    <div>
                        <label class="block text-sm font-medium">Nama Subaccount</label>
                        <input type="text" name="fsubaccountname" value="{{ old('fsubaccountname') }}"
                            class="w-full border rounded px-3 py-2 @error('fsubaccountname') border-red-500 @enderror">
                        @error('fsubaccountname')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-6 flex justify-center space-x-4">
                    <!-- Save Button -->
                    <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                        <x-heroicon-o-check class="w-5 h-5 mr-2" />
                        Simpan
                    </button>

                    <!-- Cancel Button -->
                    <button type="button" @click="window.location.href='{{ route('subaccount.index') }}'"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                        Keluar
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
