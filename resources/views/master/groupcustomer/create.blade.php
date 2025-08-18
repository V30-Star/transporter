@extends('layouts.app')

@section('title', 'Master Group Customer')

@section('content')
    <div x-data="{ open: true }">
        <div class="bg-white rounded shadow p-6 md:p-8 max-w-[700px] mx-auto">
            <h2 class="text-2xl font-semibold text-gray-800 flex items-center space-x-2">
                <x-heroicon-o-user-plus class="w-6 h-6 text-blue-600" />
                <span>Group Customer Baru</span>
            </h2>
            <form action="{{ route('groupcustomer.store') }}" method="POST">
                @csrf

                <div class="space-y-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium">Kode Group</label>
                        <input type="text" name="fgroupcode" value="{{ old('fgroupcode') }}"
                            class="w-full border rounded px-3 py-2 @error('fgroupcode') border-red-500 @enderror">
                        @error('fgroupcode')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Nama Group</label>
                        <input type="text" name="fgroupname" value="{{ old('fgroupname') }}"
                            class="w-full border rounded px-3 py-2 @error('fgroupname') border-red-500 @enderror">
                        @error('fgroupname')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-center space-x-4">
                    <!-- Simpan -->
                    <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                        <x-heroicon-o-check class="w-5 h-5 mr-2" />
                        Simpan
                    </button>

                    <!-- Kembali -->
                    <button type="button" onclick="window.location.href='{{ route('groupcustomer.index') }}'"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                        Kembali
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
