@extends('layouts.app')

@section('title', 'Master Wilayah')

@section('content')
    <div class="bg-white rounded shadow p-6 md:p-8 max-w-[700px] mx-auto">
        <h2 class="text-2xl font-semibold mb-6">Wilayah Edit</h2>

        <form action="{{ route('wilayah.update', $wilayah->fwilayahid) }}" method="POST">
            @csrf
            @method('PATCH')

            <div class="space-y-4 mt-4">
                <div>
                    <label class="block text-sm font-medium">Kode Wilayah</label>
                    <input type="text" name="fwilayahcode" value="{{ old('fwilayahcode', $wilayah->fwilayahcode) }}"
                        class="w-full border rounded px-3 py-2 @error('fwilayahcode') border-red-500 @enderror">
                    @error('fwilayahcode')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium">Nama Wilayah</label>
                    <input type="text" name="fwilayahname" value="{{ old('fwilayahname', $wilayah->fwilayahname) }}"
                        class="w-full border rounded px-3 py-2 @error('fwilayahname') border-red-500 @enderror">
                    @error('fwilayahname')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 flex justify-center space-x-4">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                    <x-heroicon-o-check class="w-5 h-5 mr-2" />
                    Simpan
                </button>

                <button type="button" onclick="window.location.href='{{ route('wilayah.index') }}'"
                    class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                    Kembali
                </button>
            </div>
        </form>
    </div>
@endsection
