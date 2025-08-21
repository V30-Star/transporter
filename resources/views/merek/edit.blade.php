@extends('layouts.app')

@section('title', 'Master Merek')

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
            <x-heroicon-o-tag class="w-8 h-8 text-blue-600" />
            <span>Merek Edit</span>
        </h2>
        <form action="{{ route('merek.update', $merek->fmerekid) }}" method="POST">
            @csrf
            @method('PATCH')

            <div class="space-y-4 mt-4">
                <div>
                    <label class="block text-sm font-medium">Kode Merek</label>
                    <input type="text" name="fmerekcode" value="{{ old('fmerekcode', $merek->fmerekcode) }}"
                        class="w-full border rounded px-3 py-2 @error('fmerekcode') border-red-500 @enderror">
                    @error('fmerekcode')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium">Nama Merek</label>
                    <input type="text" name="fmerekname" value="{{ old('fmerekname', $merek->fmerekname) }}"
                        class="w-full border rounded px-3 py-2 @error('fmerekname') border-red-500 @enderror">
                    @error('fmerekname')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <br>
                <div class="md:col-span-2 flex justify-center items-center space-x-2">
                    <input type="checkbox" name="fnonactive" id="statusToggle" class="form-checkbox h-5 w-5 text-indigo-600"
                        {{ old('fnonactive', $merek->fnonactive) == '1' ? 'checked' : '' }}>
                    <label class="block text-sm font-medium">Non Aktif</label>
                </div>
            </div>
            <br>
            <div class="mt-6 flex justify-center space-x-4">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                    <x-heroicon-o-check class="w-5 h-5 mr-2" />
                    Simpan
                </button>

                <button type="button" onclick="window.location.href='{{ route('merek.index') }}'"
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

                <span class="ml-2 text-right" id="current-time">
                    {{ now()->format('d M Y, H:i') }}
                    , Terakhir di Update oleh: <strong>{{ $merek->fupdatedby ?? '—' }}</strong>
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
