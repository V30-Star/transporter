@extends('layouts.app')

@section('title', 'Master Salesman')

@section('content')
    <div class="bg-white rounded shadow p-6 md:p-8 max-w-[700px] mx-auto">
        <form action="{{ route('salesman.update', $salesman->fsalesmanid) }}" method="POST">
            @csrf
            @method('PATCH')

            <div class="space-y-4 mt-4">
                <div>
                    <label class="block text-sm font-medium">Kode Salesman</label>
                    <input type="text" name="fsalesmancode" value="{{ old('fsalesmancode', $salesman->fsalesmancode) }}"
                        class="w-full border rounded px-3 py-2 uppercase @error('fsalesmancode') border-red-500 @enderror">
                    @error('fsalesmancode')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium">Nama Salesman</label>
                    <input type="text" name="fsalesmanname" value="{{ old('fsalesmanname', $salesman->fsalesmanname) }}"
                        class="w-full border rounded px-3 py-2 uppercase @error('fsalesmanname') border-red-500 @enderror">
                    @error('fsalesmanname')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <br>
                <div class="flex justify-center mt-4">
                    <label for="statusToggle"
                        class="flex items-center justify-between w-40 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                        <span class="text-sm font-medium">Non Active</span>
                        <input type="checkbox" name="fnonactive" id="statusToggle"
                            class="h-5 w-5 text-green-600 rounded focus:ring-green-500"
                            {{ old('fnonactive', $salesman->fnonactive) == '1' ? 'checked' : '' }}>
                    </label>
                </div>
            </div>
            <br>
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
            <br>
            <hr>
            <br>
            @php
                $lastUpdate = $salesman->fupdatedat ?: $salesman->fcreatedat;
                $isUpdated = !empty($salesman->fupdatedat);
            @endphp

            <span class="text-sm text-gray-600 md:col-span-2 flex justify-between items-center">
                <strong>{{ auth('sysuser')->user()->fname ?? '—' }}</strong>

                <span class="ml-2 text-right">
                    {{ \Carbon\Carbon::parse($lastUpdate)->timezone('Asia/Jakarta')->format('d M Y, H:i:s') }}
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
