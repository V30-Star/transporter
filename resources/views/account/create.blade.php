@extends('layouts.app')

@section('title', 'Master Account')

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

    <div x-data="{ open: true, selected: 'rekening', subAccount: false }">
        <div class="bg-white rounded shadow p-6 md:p-8 max-w-5xl mx-auto">
            <form action="{{ route('account.store') }}" method="POST">
                @csrf

                <div class="border-b pb-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-700 mb-4">Account Header</h2>
                    <div>
                        <label for="faccupline" class="block text-sm font-medium">Pilih Account Header</label>
                        <select name="faccupline" id="faccupline"
                            class="w-full border rounded px-3 py-2 @error('faccupline') border-red-500 @enderror">
                            @foreach ($accounts as $header)
                                <option value="{{ $header->id }}" {{ old('faccupline') == $header->id ? 'selected' : '' }}>
                                    {{ $header->faccount }} - {{ $header->faccname }}
                                </option>
                            @endforeach 
                        </select>
                        @error('faccupline')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium">Account #</label>
                    <input type="text" name="faccount" value="{{ old('faccount') }}"
                        class="w-full border rounded px-3 py-2 @error('faccount') border-red-500 @enderror" maxlength="10"
                        pattern="^\d+(-\d+)*$" title="Format harus berupa angka dengan tanda hubung (misal: 1-123)">
                    @error('faccount')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium">Account Name</label>
                    <input type="text" name="faccname" value="{{ old('faccname') }}"
                        class="w-full border rounded px-3 py-2 @error('faccname') border-red-500 @enderror">
                    @error('faccname')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-4">
                    <label for="fnormal" class="block text-sm font-medium">Saldo Normal</label>
                    <select name="fnormal" id="fnormal" class="w-full border rounded px-3 py-2">
                        <option value="1" {{ old('fnormal') == '1' ? 'selected' : '' }}>Debet</option>
                        <option value="2" {{ old('fnormal') == '2' ? 'selected' : '' }}>Kredit</option>
                    </select>
                </div>

                <div class="mt-4">
                    <label for="fend" class="block text-sm font-medium">Account Type</label>
                    <select name="fend" id="fend" class="w-full border rounded px-3 py-2">
                        <option value="1" {{ old('fend') == '1' ? 'selected' : '' }}>Detil</option>
                        <option value="2" {{ old('fend') == '2' ? 'selected' : '' }}>Header</option>
                    </select>
                </div>

                <div x-data="{ subAccount: {{ old('fhavesubaccount', 0) ? 'true' : 'false' }} }">
                    <div class="mt-4">
                        <label for="fhavesubaccount" class="flex items-center space-x-2">
                            <input type="checkbox" name="fhavesubaccount" id="fhavesubaccount" value="1"
                                x-model="subAccount" {{ old('fhavesubaccount') ? 'checked' : '' }}>
                            <span class="text-sm">Ada Sub Account?</span>
                        </label>
                    </div>

                    <div class="mt-4">
                        <label for="ftypesubaccount" class="block text-sm font-medium">Type</label>
                        <select name="ftypesubaccount" id="ftypesubaccount" class="w-full border rounded px-3 py-2"
                            :disabled="!subAccount" :class="!subAccount ? 'bg-gray-200' : ''">
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

                <div class="mt-4">
                    <label class="block text-sm font-medium">Initial Jurnal#</label>
                    <input type="text" name="finitjurnal" value="{{ old('finitjurnal') }}"
                        class="w-full border rounded px-3 py-2 @error('finitjurnal') border-red-500 @enderror"
                        maxlength="2">
                    @error('finitjurnal')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-red-600 text-sm mt-1">** Khusus Jurnal Kas/Bank</p>
                </div>

                <div class="mt-4">
                    <label for="fuserlevel" class="block text-sm font-medium">User Level</label>
                    <select name="fuserlevel" id="fuserlevel" class="w-full border rounded px-3 py-2">
                        <option value="1" {{ old('fuserlevel') == '1' ? 'selected' : '' }}>User</option>
                        <option value="2" {{ old('fuserlevel') == '2' ? 'selected' : '' }}>
                            Supervisor
                        </option>
                        <option value="3" {{ old('fuserlevel') == '3' ? 'selected' : '' }}>Admin</option>
                    </select>
                </div>
                <br>
                <div class="md:col-span-2 flex justify-center items-center space-x-2">
                    <input type="checkbox" name="fnonactive_checkbox" id="statusToggle"
                        class="form-checkbox h-5 w-5 text-indigo-600" value="1"
                        {{ old('fnonactive_checkbox') == '1' ? 'checked' : '' }}>
                    <label for="statusToggle" class="block text-sm font-medium">Non Aktif</label>
                </div>
                <input type="hidden" name="faccupline" value='IDR'>
                <input type="hidden" name="fcurrency" value='IDR'>
                {{-- <input type="hidden" name="fcreatedby" value="{{ auth()->user()->fsysuserid }}">
                    <input type="hidden" name="fupdatedby" value="{{ auth()->user()->fsysuserid }}"> --}}
                <input type="hidden" name="fnonactive" value='0'>
                <br>
                <div class="mt-6 flex justify-center space-x-4">
                    <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                        <x-heroicon-o-check class="w-5 h-5 mr-2" />
                        Simpan
                    </button>

                    <button type="button" @click="window.location.href='{{ route('account.index') }}'"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                        Keluar
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
