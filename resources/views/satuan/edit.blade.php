@extends('layouts.app')

@section('title', 'Edit Satuan')

@section('content')
    <div class="bg-white rounded shadow p-4 max-w-2xl mx-auto">
        <h2 class="text-2xl font-semibold mb-6">Edit Satuan</h2>

        <form action="{{ route('satuan.update', $satuan->fsatuanid) }}" method="POST">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium">Kode Satuan</label>
                    <input type="text" name="fsatuancode" value="{{ old('fsatuancode', $satuan->fsatuancode) }}"
                        class="w-full border rounded px-3 py-2 @error('fsatuancode') border-red-500 @enderror"
                        maxlength="3">
                    @error('fsatuancode')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium">Nama Satuan</label>
                    <input type="text" name="fsatuanname" value="{{ old('fsatuanname', $satuan->fsatuanname) }}"
                        class="w-full border rounded px-3 py-2 @error('fsatuanname') border-red-500 @enderror">
                    @error('fsatuanname')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 flex justify-center space-x-4">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                    <x-heroicon-o-check class="w-5 h-5 mr-2" />
                    Simpan
                </button>

                <button type="button" onclick="window.location.href='{{ route('satuan.index') }}'"
                    class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                    Kembali
                </button>
            </div>
        </form>
    </div>
@endsection
