@extends('layouts.app')

@section('title', 'Master Group Product')

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

    <div x-data="{ open: true, selected: 'surat' }">
        <div class="bg-white rounded shadow p-6 md:p-8 max-w-[700px] mx-auto">
            <h2 class="text-2xl font-semibold text-gray-800 flex items-center space-x-2">
                <x-heroicon-o-squares-2x2 class="w-8 h-8 text-blue-600" />
                <span>Group Product Baru</span>
            </h2>
            <form action="{{ route('groupproduct.store') }}" method="POST">
                @csrf

                <div class="space-y-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium">Kode Group Product</label>
                        <input type="text" name="fgroupcode" value="{{ old('fgroupcode') }}"
                            class="w-full border rounded px-3 py-2 @error('fgroupcode') border-red-500 @enderror">
                        @error('fgroupcode')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Nama Group Product</label>
                        <input type="text" name="fgroupname" value="{{ old('fgroupname') }}"
                            class="w-full border rounded px-3 py-2 @error('fgroupname') border-red-500 @enderror">
                        @error('fgroupname')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="md:col-span-2 flex justify-center items-center space-x-2">
                        <label class="block text-sm font-medium">Status</label>
                        <label class="switch">
                            <input type="checkbox" name="fnonactive" id="statusToggle"
                                {{ old('fnonactive') == '1' ? 'checked' : '' }}>
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>

                <!-- Tombol Aksi -->
                <div class="mt-6 flex justify-center space-x-4">
                    <!-- Simpan -->
                    <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                        <x-heroicon-o-check class="w-5 h-5 mr-2" />
                        Simpan
                    </button>

                    <!-- Keluar -->
                    <button type="button" @click="window.location.href='{{ route('groupproduct.index') }}'"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                        Keluar
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
