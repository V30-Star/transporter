@extends('layouts.app')

@section('title', 'Edit Subaccount')

@section('content')
    <div class="bg-white rounded shadow p-4 max-w-2xl mx-auto">
        <h2 class="text-2xl font-semibold mb-6">Edit Subaccount</h2>

        <form action="{{ route('subaccount.update', $subaccount->fsubaccountid) }}" method="POST">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Subaccount Code -->
                <div>
                    <label class="block text-sm font-medium">Kode Subaccount</label>
                    <input type="text" name="fsubaccountcode"
                        value="{{ old('fsubaccountcode', $subaccount->fsubaccountcode) }}"
                        class="w-full border rounded px-3 py-2 @error('fsubaccountcode') border-red-500 @enderror">
                    @error('fsubaccountcode')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Subaccount Name -->
                <div>
                    <label class="block text-sm font-medium">Nama Subaccount</label>
                    <input type="text" name="fsubaccountname"
                        value="{{ old('fsubaccountname', $subaccount->fsubaccountname) }}"
                        class="w-full border rounded px-3 py-2 @error('fsubaccountname') border-red-500 @enderror">
                    @error('fsubaccountname')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Checkbox for fnonactive -->
            <div class="mt-4">
                <label for="fnonactive" class="flex items-center space-x-2">
                    <input type="checkbox" name="fnonactive" id="fnonactive" value="1" class="form-checkbox"
                        {{ old('fnonactive', $subaccount->fnonactive) == '1' ? 'checked' : '' }}>
                    <span class="text-sm">Nonaktifkan Subaccount</span>
                </label>
            </div>

            <!-- Action Buttons -->
            <div class="mt-6 flex justify-center space-x-4">
                <!-- Save Button -->
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                    <x-heroicon-o-check class="w-5 h-5 mr-2" />
                    Simpan
                </button>

                <!-- Back Button -->
                <button type="button" onclick="window.location.href='{{ route('subaccount.index') }}'"
                    class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                    Kembali
                </button>
            </div>
        </form>
    </div>
@endsection
