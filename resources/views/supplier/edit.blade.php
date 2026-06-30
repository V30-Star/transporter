@extends('layouts.app')

@section('title', 'Edit - Master Supplier')

@section('content')
    <style>
        .field-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 4px;
        }

        .field-label .req {
            color: #dc2626;
            margin-left: 2px;
        }

        .field-input,
        .field-select,
        .field-textarea {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.55rem 0.75rem;
            font-size: 0.875rem;
            background-color: #ffffff;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .field-input:focus,
        .field-select:focus,
        .field-textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .section-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1rem;
        }

        .section-title {
            font-size: 0.75rem;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            font-size: 0.9rem;
            color: #9ca3af;
        }

        .unit-suffix {
            font-size: 0.8rem;
            color: #9ca3af;
            white-space: nowrap;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 28px;
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
            background-color: #d1d5db;
            transition: 0.25s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            border-radius: 50%;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: 0.25s;
        }

        input:checked + .slider {
            background-color: #ef4444;
        }

        input:checked + .slider:before {
            transform: translateX(22px);
        }

        .btn-save {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #2563eb;
            color: #fff;
            padding: 0.6rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .btn-save:hover {
            background: #1d4ed8;
        }

        .btn-cancel {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #ffffff;
            color: #4b5563;
            border: 1px solid #d1d5db;
            padding: 0.6rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .btn-cancel:hover {
            background: #f9fafb;
        }
    </style>

    <div x-data="{ open: true, selected: 'surat', nonactive: {{ old('fnonactive', $supplier->fnonactive) == '1' ? 'true' : 'false' }} }">
        <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1400px] w-full mx-auto">
            <form action="{{ route('supplier.update', $supplier->fsupplierid) }}" method="POST" data-form-draft="true"
                data-draft-key="supplier:edit">
                @csrf
                @method('PATCH')

                {{-- Header bar: page title + status toggle --}}
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <button type="button" @click="window.location.href='{{ route('supplier.index') }}'"
                            class="text-gray-400 hover:text-gray-600">
                            <x-heroicon-o-arrow-left class="w-5 h-5" />
                        </button>
                    </div>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <span class="text-sm font-semibold text-gray-600">Non Aktif</span>
                        <div class="switch">
                            <input type="checkbox" x-model="nonactive" name="fnonactive" id="statusToggle">
                            <span class="slider"></span>
                        </div>
                    </label>
                </div>

                {{-- Section: Identitas Supplier --}}
                <div class="section-card">
                    <div class="section-title"><i class="fa fa-id-card"></i> Identitas Supplier</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="field-label">Kode Supplier <span class="req">*</span></label>
                            <input type="text" name="fsuppliercode" value="{{ old('fsuppliercode', $supplier->fsuppliercode) }}"
                                class="field-input uppercase @error('fsuppliercode') is-invalid border-red-500 @enderror {{ !empty($isTransactionLocked) ? 'bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200' : '' }}"
                                {{ !empty($isTransactionLocked) ? 'readonly' : '' }} autofocus>
                            @if (!empty($isTransactionLocked))
                                <p class="text-amber-600 text-xs mt-1 font-medium">Kode supplier dikunci karena sudah direferensi di transaksi.</p>
                            @endif
                            @error('fsuppliercode')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="field-label">Nama Supplier <span class="req">*</span></label>
                            <input type="text" name="fsuppliername" value="{{ old('fsuppliername', $supplier->fsuppliername) }}"
                                class="field-input uppercase @error('fsuppliername') border-red-500 @enderror">
                            @error('fsuppliername')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="field-label">Mata Uang</label>
                            <select name="fcurr" class="field-select @error('fcurr') border-red-500 @enderror">
                                <option value="IDR" {{ old('fcurr', $supplier->fcurr) == 'IDR' ? 'selected' : '' }}>IDR (Rupiah)</option>
                                <option value="USD" {{ old('fcurr', $supplier->fcurr) == 'USD' ? 'selected' : '' }}>USD (Dollar)</option>
                                <option value="EUR" {{ old('fcurr', $supplier->fcurr) == 'EUR' ? 'selected' : '' }}>EUR (Euro)</option>
                            </select>
                            @error('fcurr')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="field-label">NPWP</label>
                            <input type="text" name="fnpwp" value="{{ old('fnpwp', $supplier->fnpwp) }}"
                                class="field-input @error('fnpwp') border-red-500 @enderror">
                            @error('fnpwp')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Section: Kontak & Alamat (side by side) --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    {{-- Kontak --}}
                    <div class="section-card mb-0">
                        <div class="section-title"><i class="fa fa-phone"></i> Kontak</div>
                        <div class="space-y-3">
                            <div>
                                <label class="field-label">Kontak Person</label>
                                <input type="text" name="fkontakperson" value="{{ old('fkontakperson', $supplier->fkontakperson) }}"
                                    class="field-input @error('fkontakperson') border-red-500 @enderror">
                                @error('fkontakperson')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="field-label">Jabatan</label>
                                <input type="text" name="fjabatan" value="{{ old('fjabatan', $supplier->fjabatan) }}"
                                    class="field-input @error('fjabatan') border-red-500 @enderror">
                                @error('fjabatan')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="field-label">Telepon</label>
                                    <input type="text" name="ftelp" value="{{ old('ftelp', $supplier->ftelp) }}"
                                        class="field-input @error('ftelp') border-red-500 @enderror">
                                    @error('ftelp')
                                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="field-label">Fax</label>
                                    <input type="text" name="ffax" value="{{ old('ffax', $supplier->ffax) }}"
                                        class="field-input @error('ffax') border-red-500 @enderror">
                                    @error('ffax')
                                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Alamat --}}
                    <div class="section-card mb-0">
                        <div class="section-title"><i class="fa fa-map-marker"></i> Alamat</div>
                        <textarea name="faddress" rows="6"
                            class="field-textarea resize-y @error('faddress') border-red-500 @enderror">{{ old('faddress', $supplier->faddress) }}</textarea>
                        @error('faddress')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Section: Pembayaran --}}
                <div class="section-card">
                    <div class="section-title"><i class="fa fa-credit-card"></i> Pembayaran</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="field-label">Jatuh Tempo</label>
                            <div class="flex items-center gap-2">
                                <input type="number" name="ftempo" value="{{ old('ftempo', $supplier->ftempo) }}"
                                    class="field-input @error('ftempo') border-red-500 @enderror" min="0" max="999"
                                    step="1" oninput="this.value = this.value.replace(/[^0-9]/g,'').slice(0,3)">
                                <span class="unit-suffix">hari</span>
                            </div>
                            @error('ftempo')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="field-label">No. Rekening</label>
                            <input type="text" name="fnorekening" value="{{ old('fnorekening', $supplier->fnorekening) }}"
                                class="field-input @error('fnorekening') border-red-500 @enderror">
                            @error('fnorekening')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Section: Memo --}}
                <div class="section-card">
                    <div class="section-title"><i class="fa fa-sticky-note"></i> Memo</div>
                    <textarea name="fmemo" rows="4"
                        class="field-textarea @error('fmemo') border-red-500 @enderror">{{ old('fmemo', $supplier->fmemo) }}</textarea>
                    @error('fmemo')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Action Bar --}}
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="window.location.href='{{ route('supplier.index') }}'"
                        class="btn-cancel">
                        <x-heroicon-o-arrow-left class="w-4 h-4" />
                        Kembali
                    </button>
                    <button type="submit" class="btn-save">
                        <x-heroicon-o-check class="w-4 h-4" />
                        Simpan
                    </button>
                </div>
            </form>
        </div>

        {{-- FOOTER INFO --}}
        @php
            $lastUpdate = $supplier->fupdatedat ?: $supplier->fcreatedat;
            $updatedBy = $supplier->fupdatedby ?: ($supplier->fcreatedby ?: '—');
        @endphp
        <div class="max-w-[1400px] mx-auto mt-4 px-4 flex justify-between items-center text-xs text-gray-400">
            <span>Terakhir diupdate oleh: <strong>{{ $updatedBy }}</strong></span>
            <span>{{ $lastUpdate ? \Carbon\Carbon::parse($lastUpdate)->timezone('Asia/Jakarta')->format('d M Y, H:i:s') : '—' }}</span>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
@endsection
