@extends('layouts.app')

@section('title', 'Master Supplier')

@section('content')
    <div class="bg-white rounded shadow p-6 md:p-8 max-w-5xl mx-auto">
        <form action="{{ route('supplier.update', $supplier->fsupplierid) }}" method="POST">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                    <label class="block text-sm font-medium">Kode Supplier</label>
                    <input type="text" name="fsuppliercode" value="{{ old('fsuppliercode', $supplier->fsuppliercode) }}"
                        class="w-full border rounded px-3 py-2 @error('fsuppliercode') border-red-500 @enderror">
                    @error('fsuppliercode')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium">Nama Supplier</label>
                    <input type="text" name="fsuppliername" value="{{ old('fsuppliername', $supplier->fsuppliername) }}"
                        class="w-full border rounded px-3 py-2 @error('fsuppliername') border-red-500 @enderror">
                    @error('fsuppliername')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium">NPWP</label>
                    <input type="text" name="fnpwp" value="{{ old('fnpwp', $supplier->fnpwp) }}"
                        class="w-full border rounded px-3 py-2 @error('fnpwp') border-red-500 @enderror">
                    @error('fnpwp')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium">Alamat</label>
                    <input type="text" name="faddress" value="{{ old('faddress', $supplier->faddress) }}"
                        class="w-full border rounded px-3 py-2 @error('faddress') border-red-500 @enderror">
                    @error('faddress')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium">Telepon</label>
                    <input type="text" name="ftelp" value="{{ old('ftelp', $supplier->ftelp) }}"
                        class="w-full border rounded px-3 py-2 @error('ftelp') border-red-500 @enderror">
                    @error('ftelp')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium">Fax</label>
                    <input type="text" name="ffax" value="{{ old('ffax', $supplier->ffax) }}"
                        class="w-full border rounded px-3 py-2 @error('ffax') border-red-500 @enderror">
                    @error('ffax')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium">Kota</label>
                    <input type="text" name="fcity" value="{{ old('fcity', $supplier->fcity) }}"
                        class="w-full border rounded px-3 py-2 @error('fcity') border-red-500 @enderror">
                    @error('fcity')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium">Jatuh Tempo</label>
                    <input type="number" name="ftempo" value="{{ old('ftempo', $supplier->ftempo) }}"
                        class="w-full border rounded px-3 py-2 @error('ftempo') border-red-500 @enderror">
                    @error('ftempo')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium">Kontak Person</label>
                    <input type="text" name="fkontakperson" value="{{ old('fkontakperson', $supplier->fkontakperson) }}"
                        class="w-full border rounded px-3 py-2 @error('fkontakperson') border-red-500 @enderror">
                    @error('fkontakperson')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium">Jabatan</label>
                    <input type="text" name="fjabatan" value="{{ old('fjabatan', $supplier->fjabatan) }}"
                        class="w-full border rounded px-3 py-2 @error('fjabatan') border-red-500 @enderror">
                    @error('fjabatan')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium">No. Rekening</label>
                    <input type="text" name="fnorekening" value="{{ old('fnorekening', $supplier->fnorekening) }}"
                        class="w-full border rounded px-3 py-2 @error('fnorekening') border-red-500 @enderror">
                    @error('fnorekening')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium">Memo</label>
                    <textarea name="fmemo" class="w-full border rounded px-3 py-2 @error('fmemo') border-red-500 @enderror">{{ old('fmemo', $supplier->fmemo) }}</textarea>
                    @error('fmemo')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium">Mata Uang</label>
                    <select name="fcurr"
                        class="w-full border rounded px-3 py-2 @error('fcurr') border-red-500 @enderror">
                        <option value="IDR" {{ old('fcurr', $supplier->fcurr) == 'IDR' ? 'selected' : '' }}>IDR (Rupiah)
                        </option>
                        <option value="USD" {{ old('fcurr', $supplier->fcurr) == 'USD' ? 'selected' : '' }}>USD (Dollar)
                        </option>
                        <option value="EUR" {{ old('fcurr', $supplier->fcurr) == 'EUR' ? 'selected' : '' }}>EUR (Euro)
                        </option>
                        <!-- Add more currencies as needed -->
                    </select>
                    @error('fcurr')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <br>
                <div class="md:col-span-2 flex flex-col items-center space-y-4">
                    <label for="statusToggle"
                        class="flex items-center justify-between w-40 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                        <span class="text-sm font-medium">Non Aktif</span>
                        <input type="checkbox" name="fnonactive" id="statusToggle"
                            class="h-5 w-5 text-green-600 rounded focus:ring-green-500"
                            {{ old('fnonactive', $supplier->fnonactive) == '1' ? 'checked' : '' }}>
                    </label>
                </div>
            </div>
            <br>
            <!-- Action Buttons -->
            <div class="mt-6 flex justify-center space-x-4">
                <!-- Simpan -->
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                    <x-heroicon-o-check class="w-5 h-5 mr-2" />
                    Simpan
                </button>

                <!-- Keluar -->
                <button type="button" onclick="window.location.href='{{ route('supplier.index') }}'"
                    class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                    Kembali
                </button>
            </div>
            <br>
            <hr>
            <br>
            @php
                $lastUpdate = $supplier->fupdatedat ?: $supplier->fcreatedat;
                $isUpdated = !empty($supplier->fupdatedat);
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
