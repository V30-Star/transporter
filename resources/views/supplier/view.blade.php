@extends('layouts.app')

@section('title', 'View - Master Supplier')

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
            background-color: #f9fafb;
            color: #4b5563;
            cursor: not-allowed;
            transition: border-color 0.15s, box-shadow 0.15s;
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
            cursor: not-allowed;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
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
            opacity: 0.6;
        }

        input:checked + .slider:before {
            transform: translateX(22px);
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

    <div x-data="{ open: true, selected: 'surat', nonactive: {{ $supplier->fnonactive == '1' ? 'true' : 'false' }} }">
        <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1400px] w-full mx-auto">
            
            {{-- Header bar: page title + status toggle --}}
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-3">
                    <button type="button" @click="window.location.href='{{ route('supplier.index') }}'"
                        class="text-gray-400 hover:text-gray-600">
                        <x-heroicon-o-arrow-left class="w-5 h-5" />
                    </button>
                </div>

                <label class="flex items-center gap-2 cursor-not-allowed">
                    <span class="text-sm font-semibold text-gray-600">Non Aktif</span>
                    <div class="switch">
                        <input type="checkbox" x-model="nonactive" disabled>
                        <span class="slider"></span>
                    </div>
                </label>
            </div>

            {{-- Section: Identitas Supplier --}}
            <div class="section-card">
                <div class="section-title"><i class="fa fa-id-card"></i> Identitas Supplier</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="field-label">Kode Supplier</label>
                        <input type="text" value="{{ $supplier->fsuppliercode }}"
                            class="field-input uppercase border-gray-200" readonly>
                    </div>

                    <div>
                        <label class="field-label">Nama Supplier</label>
                        <input type="text" value="{{ $supplier->fsuppliername }}"
                            class="field-input uppercase border-gray-200" readonly>
                    </div>

                    <div>
                        <label class="field-label">Mata Uang</label>
                        <select class="field-select border-gray-200" disabled>
                            <option value="IDR" {{ $supplier->fcurr == 'IDR' ? 'selected' : '' }}>IDR (Rupiah)</option>
                            <option value="USD" {{ $supplier->fcurr == 'USD' ? 'selected' : '' }}>USD (Dollar)</option>
                            <option value="EUR" {{ $supplier->fcurr == 'EUR' ? 'selected' : '' }}>EUR (Euro)</option>
                        </select>
                    </div>

                    <div>
                        <label class="field-label">NPWP</label>
                        <input type="text" value="{{ $supplier->fnpwp }}"
                            class="field-input border-gray-200" readonly>
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
                            <input type="text" value="{{ $supplier->fkontakperson }}"
                                class="field-input border-gray-200" readonly>
                        </div>
                        <div>
                            <label class="field-label">Jabatan</label>
                            <input type="text" value="{{ $supplier->fjabatan }}"
                                class="field-input border-gray-200" readonly>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="field-label">Telepon</label>
                                <input type="text" value="{{ $supplier->ftelp }}"
                                    class="field-input border-gray-200" readonly>
                            </div>
                            <div>
                                <label class="field-label">Fax</label>
                                <input type="text" value="{{ $supplier->ffax }}"
                                    class="field-input border-gray-200" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Alamat --}}
                <div class="section-card mb-0">
                    <div class="section-title"><i class="fa fa-map-marker"></i> Alamat</div>
                    <textarea rows="6" class="field-textarea resize-y border-gray-200" readonly>{{ $supplier->faddress }}</textarea>
                </div>
            </div>

            {{-- Section: Pembayaran --}}
            <div class="section-card">
                <div class="section-title"><i class="fa fa-credit-card"></i> Pembayaran</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="field-label">Jatuh Tempo</label>
                        <div class="flex items-center gap-2">
                            <input type="number" value="{{ $supplier->ftempo }}"
                                class="field-input border-gray-200" readonly>
                            <span class="unit-suffix">hari</span>
                        </div>
                    </div>

                    <div>
                        <label class="field-label">No. Rekening</label>
                        <input type="text" value="{{ $supplier->fnorekening }}"
                            class="field-input border-gray-200" readonly>
                    </div>
                </div>
            </div>

            {{-- Section: Memo --}}
            <div class="section-card">
                <div class="section-title"><i class="fa fa-sticky-note"></i> Memo</div>
                <textarea rows="4" class="field-textarea border-gray-200" readonly>{{ $supplier->fmemo }}</textarea>
            </div>

            {{-- Action Bar --}}
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="window.location.href='{{ route('supplier.index') }}'"
                    class="btn-cancel">
                    <x-heroicon-o-arrow-left class="w-4 h-4" />
                    Kembali
                </button>
            </div>
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
