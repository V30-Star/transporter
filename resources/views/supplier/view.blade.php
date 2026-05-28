@extends('layouts.app')

@section('title', 'View Supplier')

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

    @if (session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'warning',
                    title: 'Supplier Tidak Dapat Dihapus',
                    text: @json(session('error')),
                    confirmButtonText: 'OK'
                });
            });
        </script>
    @endif

    <div x-data="{ open: true, selected: 'surat' }">
        <div class="bg-white rounded shadow p-6 md:p-8 max-w-[96rem] mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                    <label class="block text-sm font-bold">Kode Supplier</label>
                    <input readonly type="text" name="fsuppliercode"
                        value="{{ old('fsuppliercode', $supplier->fsuppliercode) }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 uppercase @error('fsuppliercode') border-red-500 @enderror"
                        autofocus>
                    @error('fsuppliercode')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold">Nama Supplier</label>
                    <input readonly type="text" name="fsuppliername"
                        value="{{ old('fsuppliername', $supplier->fsuppliername) }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 uppercase @error('fsuppliername') border-red-500 @enderror">
                    @error('fsuppliername')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold">Mata Uang</label>
                    <select name="fcurr" disabled
                        class="w-full border rounded px-3 py-2 bg-gray-100 @error('fcurr') border-red-500 @enderror">
                        <option value="IDR" {{ old('fcurr', $supplier->fcurr) == 'IDR' ? 'selected' : '' }}>IDR (Rupiah)</option>
                        <option value="USD" {{ old('fcurr', $supplier->fcurr) == 'USD' ? 'selected' : '' }}>USD (Dollar)</option>
                        <option value="EUR" {{ old('fcurr', $supplier->fcurr) == 'EUR' ? 'selected' : '' }}>EUR (Euro)</option>
                    </select>
                    @error('fcurr')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold">NPWP</label>
                    <input readonly type="text" name="fnpwp" value="{{ old('fnpwp', $supplier->fnpwp) }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 @error('fnpwp') border-red-500 @enderror">
                    @error('fnpwp')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold">Alamat</label>
                    <textarea readonly name="faddress" rows="3"
                        class="w-full border rounded px-3 py-2 resize-y bg-gray-100 @error('faddress') border-red-500 @enderror">{{ old('faddress', $supplier->faddress) }}</textarea>
                    @error('faddress')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold">Telepon</label>
                    <input readonly type="text" name="ftelp" value="{{ old('ftelp', $supplier->ftelp) }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 @error('ftelp') border-red-500 @enderror">
                    @error('ftelp')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold">Fax</label>
                    <input readonly type="text" name="ffax" value="{{ old('ffax', $supplier->ffax) }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 @error('ffax') border-red-500 @enderror">
                    @error('ffax')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold">Jatuh Tempo</label>
                    <input readonly type="number" name="ftempo" value="{{ old('ftempo', $supplier->ftempo) }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 @error('ftempo') border-red-500 @enderror"
                        min="0" max="999" step="1">
                    @error('ftempo')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold">Kontak Person</label>
                    <input readonly type="text" name="fkontakperson"
                        value="{{ old('fkontakperson', $supplier->fkontakperson) }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 @error('fkontakperson') border-red-500 @enderror">
                    @error('fkontakperson')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold">Jabatan</label>
                    <input readonly type="text" name="fjabatan" value="{{ old('fjabatan', $supplier->fjabatan) }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 @error('fjabatan') border-red-500 @enderror">
                    @error('fjabatan')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold">No. Rekening</label>
                    <input readonly type="text" name="fnorekening"
                        value="{{ old('fnorekening', $supplier->fnorekening) }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 @error('fnorekening') border-red-500 @enderror">
                    @error('fnorekening')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold">Memo</label>
                    <textarea readonly name="fmemo"
                        class="w-full border rounded px-3 py-2 bg-gray-100 @error('fmemo') border-red-500 @enderror">{{ old('fmemo', $supplier->fmemo) }}</textarea>
                    @error('fmemo')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2 flex justify-center items-center space-x-2">
                    <label for="statusToggle"
                        class="flex items-center justify-between w-40 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                        <span class="text-sm font-medium">Non Aktif</span>
                        <input disabled type="checkbox" name="fnonactive" id="statusToggle"
                            class="h-5 w-5 text-green-600 rounded focus:ring-green-500 bg-gray-100"
                            {{ old('fnonactive', $supplier->fnonactive) == '1' ? 'checked' : '' }}>
                    </label>
                </div>
            </div>
            <br>

            <div class="mt-6 flex justify-center space-x-4">
                <button type="button" onclick="window.location.href='{{ route('supplier.index') }}'"
                    class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                    Kembali
                </button>
            </div>
        </div>
    </div>
@endsection
