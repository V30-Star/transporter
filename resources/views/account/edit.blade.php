@extends('layouts.app')

@section('title', 'Edit Account')

@section('content')
    <div class="bg-white rounded shadow p-6 md:p-8 max-w-5xl mx-auto">
        <h2 class="text-2xl font-semibold mb-6">Edit Account</h2>

        <form action="{{ route('account.update', $account->faccid) }}" method="POST">
            @csrf
            @method('PATCH')

            <!-- Account Code -->
            <div>
                <label class="block text-sm font-medium">Kode Account</label>
                <input type="text" name="faccount" value="{{ old('faccount', $account->faccount) }}"
                    class="w-full border rounded px-3 py-2 @error('faccount') border-red-500 @enderror" maxlength="10"
                    pattern="^\d+(-\d+)*$" title="Format harus berupa angka dengan tanda hubung (misal: 1-123)">
                @error('faccount')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Account Name -->
            <div>
                <label class="block text-sm font-medium">Nama Account</label>
                <input type="text" name="faccname" value="{{ old('faccname', $account->faccname) }}"
                    class="w-full border rounded px-3 py-2 @error('faccname') border-red-500 @enderror" maxlength="50">
                @error('faccname')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Saldo Normal (Normal Balance) -->
            <div class="mt-4">
                <label for="fnormal" class="block text-sm font-medium">Saldo Normal</label>
                <select name="fnormal" id="fnormal" class="w-full border rounded px-3 py-2">
                    <option value="1" {{ old('fnormal', $account->fnormal) == '1' ? 'selected' : '' }}>Debet</option>
                    <option value="2" {{ old('fnormal', $account->fnormal) == '2' ? 'selected' : '' }}>Kredit</option>
                </select>
            </div>

            <!-- Account Type -->
            <div class="mt-4">
                <label for="fend" class="block text-sm font-medium">Account Type</label>
                <select name="fend" id="fend" class="w-full border rounded px-3 py-2">
                    <option value="1" {{ old('fend', $account->fend) == '1' ? 'selected' : '' }}>Detil</option>
                    <option value="2" {{ old('fend', $account->fend) == '2' ? 'selected' : '' }}>Header</option>
                </select>
            </div>

            <div x-data="{ subAccount: {{ old('fhavesubaccount', 0) }} }">
                <!-- Sub Account Checkbox -->
                <div class="mt-4">
                    <label for="fhavesubaccount" class="flex items-center space-x-2">
                        <input type="checkbox" name="fhavesubaccount" id="fhavesubaccount" value="1"
                            x-model="subAccount"
                            @change="subAccount ? $refs.ftypesubaccount.removeAttribute('disabled') : $refs.ftypesubaccount.setAttribute('disabled', true)">
                        <span class="text-sm">Ada Sub Account?</span>
                    </label>
                </div>

                <!-- Type Field (Always visible, but disabled when checkbox is unchecked) -->
                <div class="mt-4">
                    <label for="ftypesubaccount" class="block text-sm font-medium">Type</label>
                    <select name="ftypesubaccount" id="ftypesubaccount" class="w-full border rounded px-3 py-2"
                        x-ref="ftypesubaccount" :disabled="!subAccount" :class="!subAccount ? 'bg-gray-200' : ''">
                        <option value="Sub Account" {{ old('ftypesubaccount') == 'Sub Account' ? 'selected' : '' }}>
                            Sub Account
                        </option>
                        <option value="Customer" {{ old('ftypesubaccount') == 'Customer' ? 'selected' : '' }}>
                            Customer
                        </option>
                        <option value="Supplier" {{ old('ftypesubaccount') == 'Supplier' ? 'selected' : '' }}>
                            Supplier
                        </option>
                    </select>
                </div>
            </div>
            <!-- Initial Jurnal# -->
            <div class="mt-4">
                <label class="block text-sm font-medium">Initial Jurnal#</label>
                <input type="text" name="finitjurnal" value="{{ old('finitjurnal', $account->finitjurnal) }}"
                    class="w-full border rounded px-3 py-2 @error('finitjurnal') border-red-500 @enderror" maxlength="2">
                @error('finitjurnal')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p class="text-red-600 text-sm mt-1">** Khusus Jurnal Kas/Bank</p>
            </div>

            <!-- User Level -->
            <div class="mt-4">
                <label for="fuserlevel" class="block text-sm font-medium">User Level</label>
                <select name="fuserlevel" id="fuserlevel" class="w-full border rounded px-3 py-2">
                    <option value="1" {{ old('fuserlevel', $account->fuserlevel) == '1' ? 'selected' : '' }}>User
                    </option>
                    <option value="2" {{ old('fuserlevel', $account->fuserlevel) == '2' ? 'selected' : '' }}>
                        Supervisor</option>
                    <option value="3" {{ old('fuserlevel', $account->fuserlevel) == '3' ? 'selected' : '' }}>Admin
                    </option>
                </select>
            </div>

            <div class="mt-6 flex justify-center space-x-4">
                <!-- Save Button -->
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                    <x-heroicon-o-check class="w-5 h-5 mr-2" />
                    Simpan
                </button>

                <!-- Cancel Button -->
                <button type="button" onclick="window.location.href='{{ route('account.index') }}'"
                    class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                    Kembali
                </button>
            </div>
        </form>
    </div>
@endsection
