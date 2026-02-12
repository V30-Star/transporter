@extends('layouts.app')

@section('title', 'View Gudang')

@section('content')
    <div class="bg-white rounded shadow p-6 md:p-8 max-w-[700px] mx-auto">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium">Cabang</label>
                    <select name="fbranchcode" disabled
                        class="w-full border rounded px-3 py-2 @error('fbranchcode') border-red-500 @enderror">
                        <option value="">Pilih Cabang</option>
                        @foreach ($cabangOptions as $cabang)
                            <option value="{{ $cabang->fbranchcode }}"
                                {{ old('fbranchcode', $gudang->fbranchcode) == $cabang->fbranchcode ? 'selected' : '' }}>
                                {{ $cabang->fcabangname }}
                            </option>
                        @endforeach
                    </select>
                    @error('fbranchcode')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Field 2: Kode Gudang -->
                <div>
                    <label class="block text-sm font-medium">Kode Gudang</label>
                    <input readonly type="text" name="fwhcode" value="{{ old('fwhcode', $gudang->fwhcode) }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 uppercase @error('fwhcode') border-red-500 @enderror">
                    @error('fwhcode')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Field 3: Nama Gudang -->
                <div>
                    <label class="block text-sm font-medium">Nama Gudang</label>
                    <input readonly type="text" name="fwhname" value="{{ old('fwhname', $gudang->fwhname) }}"
                        class="w-full border rounded px-3 py-2 uppercase bg-gray-100 @error('fwhname') border-red-500 @enderror">
                    @error('fwhname')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Field 4: Alamat -->
                <div>
                    <label class="block text-sm font-medium">Alamat</label>
                    <input readonly type="text" name="faddress" value="{{ old('faddress', $gudang->faddress) }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 @error('faddress') border-red-500 @enderror">
                    @error('faddress')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <br>
                <div class="md:col-span-2 flex flex-col items-center space-y-4">
                    <label for="statusToggle"
                        class="flex items-center justify-between w-40 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                        <span class="text-sm font-medium">Non Aktif</span>
                        <input type="checkbox" name="fnonactive" id="statusToggle" disabled
                            class="h-5 w-5 text-green-600 rounded focus:ring-green-500"
                            {{ old('fnonactive', $gudang->fnonactive) == '1' ? 'checked' : '' }}>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex justify-center space-x-4">
                <button type="button" onclick="window.location.href='{{ route('gudang.index') }}'"
                    class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                    Kembali
                </button>
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
