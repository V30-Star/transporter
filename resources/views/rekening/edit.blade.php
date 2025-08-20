@extends('layouts.app')

@section('title', 'Master Rekening')

@section('content')

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

    <div class="bg-white rounded shadow p-6 md:p-8 max-w-[700px] mx-auto">
        <h2 class="text-2xl font-semibold text-gray-800 flex items-center space-x-2">
            <x-heroicon-o-credit-card class="w-8 h-8 text-blue-600" />
            <span>Rekening Edit</span>
        </h2>
        <form action="{{ route('rekening.update', $rekening->frekeningid) }}" method="POST">
            @csrf
            @method('PATCH')

            <div class="space-y-4 mt-4">
                <!-- Rekening Code -->
                <div>
                    <label class="block text-sm font-medium">Kode Rekening</label>
                    <input type="text" name="frekeningcode" value="{{ old('frekeningcode', $rekening->frekeningcode) }}"
                        class="w-full border rounded px-3 py-2 @error('frekeningcode') border-red-500 @enderror">
                    @error('frekeningcode')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Rekening Name -->
                <div>
                    <label class="block text-sm font-medium">Nama Rekening</label>
                    <input type="text" name="frekeningname" value="{{ old('frekeningname', $rekening->frekeningname) }}"
                        class="w-full border rounded px-3 py-2 @error('frekeningname') border-red-500 @enderror">
                    @error('frekeningname')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <span class="text-sm text-gray-600 md:col-span-2 flex justify-between items-center">
                    <strong>{{ auth()->user()->fname ?? '—' }}</strong>

                    <span class="ml-2 text-right">
                        {{ now()->format('d M Y, H:i') }}
                        , Terakhir di Update oleh: <strong>{{ $rekening->fupdatedby ?? '—' }}</strong>
                    </span>
                </span>
            </div>

            <!-- Action Buttons -->
            <div class="mt-6 flex justify-center space-x-4">
                <!-- Save Button -->
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                    <x-heroicon-o-check class="w-5 h-5 mr-2" />
                    Simpan
                </button>

                <!-- Back Button -->
                <button type="button" onclick="window.location.href='{{ route('rekening.index') }}'"
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
                    , Terakhir di Update oleh: <strong>{{ $rekening->fupdatedby ?? '—' }}</strong>
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
