@extends('layouts.app')

@section('title', 'Master Account')

@section('content')
    <div class="bg-white rounded shadow p-6 md:p-8 max-w-5xl mx-auto">
        <h2 class="text-2xl font-semibold text-gray-800 flex items-center space-x-2">
            <x-heroicon-o-banknotes class="w-8 h-8 text-blue-600" />
            <span>Account Edit</span>
        </h2>
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

                <div class="md:col-span-2 flex justify-center items-center space-x-2">
                    <label class="block text-sm font-medium">Status</label>
                    <label class="switch">
                        <input type="checkbox" name="fnonactive" id="statusToggle"
                            {{ old('fnonactive', $account->fnonactive) == '1' ? 'checked' : '' }}>
                        <span class="slider round"></span>
                    </label>
                </div>
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
            <br>
            <hr>
            <br>
            <span class="text-sm text-gray-600 md:col-span-2 flex justify-between items-center">
                <strong>{{ auth()->user()->fname ?? '—' }}</strong>

                <span class="ml-2 text-right">
                    {{ now()->format('d M Y, H:i') }}
                    , Terakhir di Update oleh: <strong>{{ $account->fupdatedby ?? '—' }}</strong>
                </span>
            </span>
        </form>
    </div>
@endsection

<style>
    hr {
        border: 0;
        border-top: 2px dashed #000000;
        margin-top: 20px;
        margin-bottom: 20px;
    }
</style>

<style>
    .switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: 0.4s;
        border-radius: 34px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        border-radius: 50%;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: 0.4s;
    }

    input:checked+.slider {
        background-color: #4CAF50;
    }

    input:checked+.slider:before {
        transform: translateX(26px);
    }

    .slider.round {
        border-radius: 34px;
    }

    .slider.round:before {
        border-radius: 50%;
    }
</style>

<script>
    function updateTime() {
        const now = new Date();
        const formattedTime = now.toLocaleString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        document.getElementById('current-time').textContent = `${formattedTime}`;
    }

    setInterval(updateTime, 1000);
    updateTime();
</script>
