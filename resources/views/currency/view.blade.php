@extends('layouts.app')

@section('title', 'View Currency')

@section('content')

    <div class="bg-white rounded shadow p-6 md:p-8 max-w-[700px] mx-auto">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium">Kode Currency</label>
                    <input disabled type="text" name="fcurrcode" value="{{ old('fcurrcode', $currency->fcurrcode) }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 uppercase @error('fcurrcode') border-red-500 @enderror"
                        {{ old('fcurrcode', $currency->fcurrcode) }} autofocus>
                    @error('fcurrcode')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium">Nama Currency</label>
                    <input disabled type="text" name="fcurrname"
                        class="w-full border rounded px-3 py-2 bg-gray-100 uppercase @error('fcurrname') border-red-500 @enderror"
                        value="{{ old('fcurrname', $currency->fcurrname) }}">
                    @error('fcurrname')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium">Rate</label>
                    <input disabled type="number" name="frate"
                        class="w-full border rounded px-3 py-2 bg-gray-100 uppercase @error('frate') border-red-500 @enderror"
                        value="{{ old('frate', $currency->frate) }}">
                    @error('frate')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-center mt-4">
                    <label class="flex items-center justify-between w-40 p-3 border rounded-lg bg-gray-100">
                        <span class="text-sm font-medium">Non Active</span>
                        <input disabled type="checkbox" class="h-5 w-5 text-green-600 rounded"
                            {{ $currency->fnonactive == '1' ? 'checked' : '' }}>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex justify-center space-x-4">
                <button type="button" onclick="window.location.href='{{ route('currency.index') }}'"
                    class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                    Kembali
                </button>
            </div>
        <br>
        <hr><br>
        <span class="text-sm text-gray-600 flex justify-between items-center">
            <strong>{{ auth('sysuser')->user()->fname ?? '—' }}</strong>
            <span>{{ \Carbon\Carbon::parse($currency->fupdatedat ?: $currency->fcreatedat)->timezone('Asia/Jakarta')->format('d M Y, H:i:s') }}</span>
        </span>
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
