@extends('layouts.app')

@section('title', 'Master Subaccount')

@section('content')
    <div class="bg-white rounded shadow p-6  md:p-8 max-w-[700px] mx-auto">
        <h2 class="text-2xl font-semibold text-gray-800 flex items-center space-x-2">
            <x-heroicon-o-document-duplicate class="w-8 h-8 text-blue-600" />
            <span>Subaccount Edit</span>
        </h2>

        <form action="{{ route('subaccount.update', $subaccount->fsubaccountid) }}" method="POST">
            @csrf
            @method('PATCH')

            <div class="space-y-4 mt-4">
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
                <br>
                <div class="md:col-span-2 flex justify-center items-center space-x-2">
                    <input type="checkbox" name="fnonactive" id="statusToggle" class="form-checkbox h-5 w-5 text-indigo-600"
                        {{ old('fnonactive', $subaccount->fnonactive) == '1' ? 'checked' : '' }}>
                    <label class="block text-sm font-medium">Non Aktif</label>
                </div>
            </div>
            <br>
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
            <br>
            <hr>
            <br>
            <span class="text-sm text-gray-600 md:col-span-2 flex justify-between items-center">
                <strong>{{ auth()->user()->fname ?? '—' }}</strong>

                <span class="ml-2 text-right">
                    {{ now()->format('d M Y, H:i') }}
                    , Terakhir di Update oleh: <strong>{{ $subaccount->fupdatedby ?? '—' }}</strong>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
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
            const currentTimeElement = document.getElementById('current-time');

            if (currentTimeElement) {
                currentTimeElement.textContent = formattedTime;
            } else {
                console.error("Element with ID 'current-time' not found.");
            }
        }

        setInterval(updateTime, 1000);
        updateTime();
    });
</script>
