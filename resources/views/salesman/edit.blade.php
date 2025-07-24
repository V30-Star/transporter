@extends('layouts.app')

@section('title', 'Edit Salesman')

@section('content')
    <div class="bg-white rounded shadow p-4 max-w-2xl mx-auto">
        <h2 class="text-2xl font-semibold mb-6">Edit Salesman</h2>

        <form action="{{ route('salesman.update', $salesman->fsalesmanid) }}" method="POST">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium">Kode Salesman</label>
                    <input type="text" name="fsalesmancode" value="{{ old('fsalesmancode', $salesman->fsalesmancode) }}"
                        class="w-full border rounded px-3 py-2 @error('fsalesmancode') border-red-500 @enderror">
                    @error('fsalesmancode')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium">Nama Salesman</label>
                    <input type="text" name="fsalesmanname" value="{{ old('fsalesmanname', $salesman->fsalesmanname) }}"
                        class="w-full border rounded px-3 py-2 @error('fsalesmanname') border-red-500 @enderror">
                    @error('fsalesmanname')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-4">
                <label for="fnonactive" class="flex items-center space-x-2">
                    <input type="checkbox" name="fnonactive" id="fnonactive" value="1" class="form-checkbox"
                        {{ old('fnonactive', $salesman->fnonactive) == '1' ? 'checked' : '' }}>
                    <span class="text-sm">Nonaktifkan Salesman</span>
                </label>
            </div>

            <div class="mt-6 flex justify-center space-x-4">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                    <x-heroicon-o-check class="w-5 h-5 mr-2" />
                    Simpan
                </button>

                <button type="button" onclick="window.location.href='{{ route('salesman.index') }}'"
                    class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                    Kembali
                </button>
            </div>
        </form>
    </div>
@endsection
