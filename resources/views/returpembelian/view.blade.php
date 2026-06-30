@extends('layouts.app')

@section('title', 'Retur Pembelian - View')

@section('content')
    @php
        $permissions = explode(',', session('user_restricted_permissions', ''));
        $canPrint =
            in_array('printReturPembelian', $permissions, true) ||
            in_array('updateReturPembelian', $permissions, true) ||
            in_array('deleteReturPembelian', $permissions, true) ||
            in_array('createReturPembelian', $permissions, true);
    @endphp
    <style>
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, .2);
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0
        }

        .slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background: #ccc;
            transition: .4s;
            border-radius: 34px
        }

        .slider:before {
            content: "";
            position: absolute;
            height: 26px;
            width: 26px;
            border-radius: 50%;
            left: 4px;
            bottom: 4px;
            background: #fff;
            transition: .4s
        }

        input:checked+.slider {
            background: #4CAF50
        }

        input:checked+.slider:before {
            transform: translateX(26px)
        }

        [x-cloak] {
            display: none !important
        }

        /* select supplier tanpa caret */
        #supplierSelect,
        #supplierSelect:disabled {
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
            background-image: none !important;
            background-repeat: no-repeat !important;
        }

        #supplierSelect::-ms-expand {
            display: none
        }

        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type=number] {
            -moz-appearance: textfield;
        }

        .desc-inline-field {
            display: flex !important;
            width: 100%;
            min-width: 0;
            align-items: stretch;
            flex-wrap: nowrap !important;
        }

        .desc-inline-field__text {
            min-width: 0;
            flex: 1 1 auto;
        }

        .desc-inline-field__button {
            flex: 0 0 auto;
            width: 2.5rem;
            justify-content: center;
        }

        .retur-detail-table th,
        .retur-detail-table td {
            padding: .25rem .375rem !important;
        }

        .retur-detail-table input:not([type="hidden"]),
        .retur-detail-table select,
        .retur-detail-table button,
        .retur-detail-table .desc-inline-field__text {
            min-height: 1.875rem;
            padding-top: .25rem !important;
            padding-bottom: .25rem !important;
            line-height: 1.25rem;
        }

        .retur-detail-table button {
            display: inline-flex;
            align-items: center;
        }

        .retur-detail-table .desc-inline-field__button {
            width: 2rem;
        }
    </style>

    @php
        // Definisikan semua variabel Anda di sini
        $currentType = old('ftypebuy', $returpembelian->ftypebuy);
        $currentAccount = trim((string) old('fprdjadi', $returpembelian->fprdjadi));
        $currentAccountId = old('faccid', $returpembelian->faccid);
        $currentPpnAmount = old('famountpajak', $returpembelian->famountpajak ?? 0);
        $currentSubtotal = old('famount', $returpembelian->famount ?? 0);
    @endphp

    <div x-data="{
        open: true,
        {{-- State untuk form --}}
        showNoItems: false
    }" class="lg:col-span-5">
                        <div class="space-y-4">
                    {{-- ─── CARD 1: Identitas Retur Pembelian (View) ────────────────────── --}}
                    <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                        <div class="px-4 pt-3 pb-0">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Identitas Retur Pembelian</p>
                        </div>
                        <div class="p-4 space-y-3">
                            <div class="grid grid-cols-3 gap-3">
                                {{-- Cabang --}}
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Cabang</label>
                                    <input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                                        value="{{ $fcabang }}" disabled>
                                    <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                                </div>

                                {{-- Transaksi# --}}
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">No.Transaksi#</label>
                                    <input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                                        value="{{ old('fpono') ?? $returpembelian->fstockmtno }}" disabled>
                                </div>

                                {{-- Tanggal --}}
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal</label>
                                    <input type="date" value="{{ old('fstockmtdate') ?? date('Y-m-d', strtotime($returpembelian->fstockmtdate)) }}" disabled
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                {{-- Supplier --}}
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Supplier</label>
                                    <select name="filter_supplier_id" disabled
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200">
                                        <option value=""></option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->fsuppliercode }}"
                                                {{ old('fsupplier', $returpembelian->fsupplier) == $supplier->fsuppliercode ? 'selected' : '' }}>
                                                {{ $supplier->fsuppliername }} ({{ $supplier->fsuppliercode }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Gudang --}}
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Gudang</label>
                                    <select name="ffrom" disabled
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200">
                                        <option value=""></option>
                                        @foreach ($warehouses as $wh)
                                            <option value="{{ $wh->fwhcode }}"
                                                {{ old('ffrom', $returpembelian->ffrom) == $wh->fwhcode ? 'selected' : '' }}>
                                                {{ $wh->fwhcode }} - {{ $wh->fwhname }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ─── CARD 2: Detail Item (View) ────────────────────── --}}
                    <div x-data="itemsTable()" x-init="init()" class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                        <div class="px-4 pt-3 pb-0">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Detail Item</p>
                        </div>
                        <div class="p-4 space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Keterangan</label>
                                <textarea name="fket" rows="2" disabled readonly
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200">{{ old('fket', $returpembelian->fket) }}</textarea>
                            </div>

                            <div class="overflow-auto border border-gray-200 rounded-lg">
                                <table class="fpb-detail-table min-w-full text-sm">
                                    <thead class="bg-gray-50 border-b border-gray-200">
                                        <tr>
                                            <th class="p-2 text-left w-10 text-xs font-semibold text-gray-500 uppercase">#</th>
                                            <th class="p-2 text-left w-36 text-xs font-semibold text-gray-500 uppercase">Kode Produk</th>
                                            <th class="p-2 text-left text-xs font-semibold text-gray-500 uppercase" style="width: 20rem; min-width: 20rem;">Nama Produk</th>
                                            <th class="p-2 text-left w-28 text-xs font-semibold text-gray-500 uppercase">No Referensi</th>
                                            <th class="p-2 text-left w-20 text-xs font-semibold text-gray-500 uppercase">Satuan</th>
                                            <th class="p-2 text-right w-20 text-xs font-semibold text-gray-500 uppercase">Qty</th>
                                            <th class="p-2 text-right w-24 text-xs font-semibold text-gray-500 uppercase">@ Harga</th>
                                            <th class="p-2 text-right w-28 text-xs font-semibold text-gray-500 uppercase">Total Harga</th>
                                        </tr>
                                    </thead>
                                    <template x-for="(it, i) in savedItems" :key="it.uid">
                                        <tbody>
                                            <tr class="border-t border-gray-150 align-top hover:bg-gray-55">
                                                <td class="p-2 text-gray-400" x-text="i + 1"></td>

                                                {{-- Kode Produk --}}
                                                <td class="p-2 font-mono text-gray-700" x-text="it.fitemcode"></td>

                                                {{-- Nama Produk --}}
                                                <td class="p-2" style="width: 20rem; min-width: 20rem;">
                                                    <div class="desc-inline-field flex w-full min-w-0 flex-nowrap items-stretch">
                                                        <div class="desc-inline-field__text min-w-0 flex-1 rounded-l-lg border border-gray-300 bg-gray-55 px-2 py-1 text-sm leading-5 text-gray-600 whitespace-normal break-words"
                                                            style="flex:1 1 auto !important; min-width:0 !important;"
                                                            x-text="it.fitemname"></div>
                                                        <button type="button" @click="openDesc(it)"
                                                            class="desc-inline-field__button inline-flex w-10 shrink-0 items-center justify-center border border-l-0 border-gray-300 rounded-r-lg px-2 py-1 transition-colors border-gray-300 bg-white text-gray-500 hover:bg-gray-50"
                                                            style="display:inline-flex !important; flex:0 0 2rem !important; width:2rem !important; justify-content:center !important; align-items:center !important;"
                                                            title="Deskripsi">
                                                            <x-heroicon-o-document-text class="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                </td>

                                                {{-- No Referensi --}}
                                                <td class="p-2 text-gray-650" x-text="it.frefdtno"></td>

                                                {{-- Satuan --}}
                                                <td class="p-2 text-gray-650" x-text="it.fsatuan"></td>

                                                {{-- Qty --}}
                                                <td class="p-2 text-right text-gray-700" x-text="fmt(it.fqty)"></td>

                                                {{-- @ Harga --}}
                                                <td class="p-2 text-right text-gray-700" x-text="fmt(it.fprice)"></td>

                                                {{-- Total Harga --}}
                                                <td class="p-2 text-right text-gray-700" x-text="fmt(it.ftotprice)"></td>
                                            </tr>
                                        </tbody>
                                    </template>
                                </table>
                            </div>

                            {{-- MODAL DESC (di dalam itemsTable) --}}
                            <div x-show="showDescModal" x-cloak class="fixed inset-0 z-[95] flex items-center justify-center bg-black/50"
                                x-transition.opacity>
                                <div class="absolute inset-0 bg-black/50" @click="closeDesc()"></div>
                                <div class="relative bg-white w-[92vw] max-w-lg rounded-2xl shadow-2xl overflow-hidden"
                                    x-transition.scale>
                                    <div class="px-5 py-4 border-b flex items-center">
                                        <x-heroicon-o-document-text class="w-6 h-6 text-blue-600 mr-2" />
                                        <h3 class="text-lg font-semibold text-gray-800">Deskripsi Item</h3>
                                    </div>
                                    <div class="px-5 py-4 space-y-4">
                                        <div>
                                            <div class="text-sm text-gray-500 mb-1">Nama Produk</div>
                                            <div class="rounded-lg border bg-gray-55 px-3 py-2 text-sm text-gray-800"
                                                x-text="descItemName || '-'"></div>
                                        </div>
                                        <div>
                                            <div class="text-sm text-gray-500 mb-1">Deskripsi</div>
                                            <textarea x-model="descValue" rows="5" disabled class="w-full border rounded px-3 py-2 bg-gray-50 cursor-not-allowed text-sm text-gray-750"></textarea>
                                        </div>
                                    </div>
                                    <div class="px-5 py-3 border-t flex items-center justify-end bg-gray-50">
                                        <button type="button" @click="closeDesc()"
                                            class="h-9 px-4 rounded-lg bg-white border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors">
                                            Tutup
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Totals Panel --}}
                            <div class="mt-3 flex justify-end">
                                <div class="w-[480px] shrink-0 max-w-full">
                                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 space-y-3 text-sm">
                                        <div class="flex items-center justify-between">
                                            <span class="text-gray-600">Total Harga</span>
                                            <span class="font-bold text-gray-850" x-text="formatTransactionAmount(totalHarga)"></span>
                                        </div>

                                        <div class="flex items-center justify-between">
                                            <span class="text-gray-650">PPN (Rate: <span x-text="ppnRate"></span>%)</span>
                                            <span class="font-bold text-gray-850" x-text="rupiah(ppnAmount)"></span>
                                        </div>

                                        <div class="border-t border-gray-200 my-1"></div>

                                        <div class="flex items-center justify-between">
                                            <span class="font-bold text-gray-800 text-base">Grand Total</span>
                                            <span class="font-bold text-gray-900 text-lg" x-text="rupiah(grandTotal)"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ─── CARD 3: Aksi / Footer (View) ────────────────────── --}}
                    <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                        <div class="flex items-center justify-between px-4 py-3 bg-gray-55">
                            <button type="button"
                                onclick="window.location.href='{{ route('returpembelian.index') }}'"
                                class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 bg-white text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-colors">
                                <x-heroicon-o-arrow-left class="w-4 h-4" />
                                Kembali
                            </button>
                            @php $isPrinted = (int) ($returpembelian->fprint ?? 0) === 1; @endphp
                            @if ($canPrint)
                                <a href="{{ route('returpembelian.print', $returpembelian->fstockmtno) }}" target="_blank"
                                    class="inline-flex items-center gap-2 px-5 py-2 text-white text-sm font-medium rounded-lg transition-colors {{ $isPrinted ? 'bg-gray-400 pointer-events-none cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m10 0v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5m10 0v5H7v-5">
                                        </path>
                                    </svg>
                                    Print
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

    </div>
@endsection
@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
@endpush
<style>
    /* Targeting lebih spesifik untuk length select */
    div#productTable_length select,
    .dataTables_wrapper #productTable_length select,
    table#productTable+.dataTables_wrapper .dataTables_length select {
        min-width: 140px !important;
        width: auto !important;
        padding: 8px 45px 8px 16px !important;
        font-size: 14px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.375rem !important;
    }

    /* Wrapper length */
    div#productTable_length,
    .dataTables_wrapper #productTable_length,
    .dataTables_wrapper .dataTables_length {
        min-width: 250px !important;
    }

    /* Label wrapper */
    div#productTable_length label,
    .dataTables_wrapper #productTable_length label,
    .dataTables_wrapper .dataTables_length label {
        font-size: 14px !important;
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
    }

    /* Targeting lebih spesifik untuk length select */
    div#warehouseTable_length select,
    .dataTables_wrapper #warehouseTable_length select,
    table#warehouseTable+.dataTables_wrapper .dataTables_length select {
        min-width: 140px !important;
        width: auto !important;
        padding: 8px 45px 8px 16px !important;
        font-size: 14px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.375rem !important;
    }

    /* Wrapper length */
    div#warehouseTable_length,
    .dataTables_wrapper #warehouseTable_length,
    .dataTables_wrapper .dataTables_length {
        min-width: 250px !important;
    }

    /* Label wrapper */
    div#warehouseTable_length label,
    .dataTables_wrapper #warehouseTable_length label,
    .dataTables_wrapper .dataTables_length label {
        font-size: 14px !important;
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
    }

    /* Targeting lebih spesifik untuk length select */
    div#supplierTable_length select,
    .dataTables_wrapper #supplierTable_length select,
    table#supplierBrowseTable+.dataTables_wrapper .dataTables_length select {
        min-width: 140px !important;
        width: auto !important;
        padding: 8px 45px 8px 16px !important;
        font-size: 14px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.375rem !important;
    }

    /* Wrapper length */
    div#supplierTable_length,
    .dataTables_wrapper #supplierTable_length,
    .dataTables_wrapper .dataTables_length {
        min-width: 250px !important;
    }

    /* Label wrapper */
    div#supplierTable_length label,
    .dataTables_wrapper #supplierTable_length label,
    .dataTables_wrapper .dataTables_length label {
        font-size: 14px !important;
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
    }

    /* Targeting lebih spesifik untuk length select */
    div#prTable_length select,
    .dataTables_wrapper #prTable_length select,
    table#supplierBrowseTable+.dataTables_wrapper .dataTables_length select {
        min-width: 140px !important;
        width: auto !important;
        padding: 8px 45px 8px 16px !important;
        font-size: 14px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.375rem !important;
    }

    /* Wrapper length */
    div#prTable_length,
    .dataTables_wrapper #prTable_length,
    .dataTables_wrapper .dataTables_length {
        min-width: 250px !important;
    }

    /* Label wrapper */
    div#prTable_length label,
    .dataTables_wrapper #prTable_length label,
    .dataTables_wrapper .dataTables_length label {
        font-size: 14px !important;
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
    }
</style>
<script>
    // Map produk untuk auto-fill tabel
    window.PRODUCT_MAP = {
        @foreach ($products as $p)
            "{{ $p->fprdcode }}": {
                name: @json($p->fprdname),
                units: @json(array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2]))),
                stock: @json($p->fminstock ?? 0)
            },
        @endforeach
    };

    // id unik
    window.cryptoRandom = function() {
        try {
            if (window.crypto?.getRandomValues) {
                const arr = new Uint32Array(1);
                window.crypto.getRandomValues(arr);
                return 'r' + arr[0].toString(16);
            }
        } catch (e) {}
        return 'r' + (Date.now().toString(16) + Math.random().toString(16).slice(2));
    };

    document.addEventListener('alpine:init', () => {
        // Store untuk PRH (desc preview) - HANYA INI
        Alpine.store('prh', {
            descPreview: {
                uid: null,
                index: null,
                label: '',
                text: ''
            },
            descList: []
        });
    });

    function itemsTable() {
        return {
            showNoItems: false,
            savedItems: @json($savedItems),
            draft: newRow(),
            editingIndex: null,
            editRow: newRow(),

            totalHarga: 0,
            ppnRate: @json((float) ($returpembelian->fppnpersen ?? 11)),

            initialGrandTotal: @json($famountmt ?? 0),
            initialPpnAmount: @json($famountpajak ?? 0),

            includePPN: @json(($returpembelian->fincludeppn ?? 0) == 1),
            fapplyppn: @json(($returpembelian->fapplyppn ?? 0) == 1),

            get ppnIncluded() {
                const total = +this.totalHarga || 0;
                const rate = +this.ppnRate || 0;
                if (!this.fapplyppn) return 0;
                return Math.round((100 / (100 + rate)) * total * (rate / 100));
            },

            get netFromGross() {
                const total = +this.totalHarga || 0;
                return total - this.ppnIncluded;
            },

            get ppnAdded() {
                const rate = +this.ppnRate || 0;
                if (!this.includePPN) return 0;
                const total = +this.totalHarga || 0;
                const base = this.fapplyppn ? total : total;
                return Math.round(base * (rate / 100));
            },

            get ppnAmount() {
                if (this.includePPN && this.fapplyppn) {
                    return this.ppnAdded;
                }
                return (this.ppnIncluded ?? 0) + (this.ppnAdded ?? 0);
            },

            get grandTotal() {
                const total = +this.totalHarga || 0;
                if (this.includePPN) return total + this.ppnAdded;
                if (this.fapplyppn) return total;
                return total;
            },

            fmt(n) {
                if (n === null || n === undefined || n === '') return '-';
                const v = Number(n);
                if (!isFinite(v)) return '-';

                return v.toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            },

            rupiah(n) {
                const v = Number(n || 0);
                if (!isFinite(v)) return '-';
                return v.toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            },

            fmtMoney(value) {
                return this.fmt(value);
            },

            recalc(row) {
                row.fqty = Math.max(0, +row.fqty || 0);
                row.fterima = Math.max(0, +row.fterima || 0);
                row.fprice = Math.max(0, +row.fprice || 0);
                row.fdiscpersen = Math.min(100, Math.max(0, +row.fdiscpersen || 0));
                row.ftotprice = +(row.fqty * row.fprice * (1 - row.fdiscpersen / 100)).toFixed(2);
                this.recalcTotals();
            },

            recalcTotals() {
                this.totalHarga = this.savedItems.reduce((sum, item) => sum + item.ftotprice, 0);
            },

            productMeta(code) {
                const key = (code || '').trim();
                return window.PRODUCT_MAP?.[key] || null;
            },

            hydrateRowFromMeta(row, meta) {
                if (!meta) {
                    row.fitemname = '';
                    row.units = [];
                    row.fsatuan = '';
                    row.maxqty = 0;
                    return;
                }
                row.fitemname = meta.name || '';
                const units = [...new Set((meta.units || []).map(u => (u ?? '').toString().trim()).filter(Boolean))];
                row.units = units;
                if (!units.includes(row.fsatuan)) row.fsatuan = units[0] || '';
                row.fsatuan = row.fsatuan;
                const stock = Number.isFinite(+meta.stock) && +meta.stock > 0 ? +meta.stock : 0;
                row.maxqty = stock;
            },

            onCodeTypedRow(row) {
                this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode));
            },

            isComplete(row) {
                return row.fitemcode && row.fitemname && row.fsatuan && Number(row.fqty) > 0;
            },

            onPrPicked(e) {
                const {
                    header,
                    items
                } = e.detail || {};
                if (!items || !Array.isArray(items)) return;

                this.resetDraft();
                this.addManyFromPR(header, items);
            },

            resetDraft() {
                this.draft = newRow();
                this.$nextTick(() => this.$refs.draftCode?.focus());
            },

            addManyFromPR(header, items) {
                const existing = new Set(this.getCurrentItemKeys());

                let added = 0,
                    duplicates = [];

                items.forEach(src => {
                    const row = {
                        uid: cryptoRandom(),
                        fitemcode: src.fitemcode ?? '',
                        fitemname: src.fitemname ?? '',
                        fsatuan: src.fsatuan ?? '',
                        frefdtno: src.frefdtno ?? '',
                        fnouref: src.fnouref ?? '',
                        frefpr: src.frefpr ?? (header?.fpono ?? ''),
                        fprhid: src.fprhid ?? header?.fprhid ?? '',
                        fqty: Number(src.fqty ?? 0),
                        fterima: Number(src.fterima ?? 0),
                        fprice: Number(src.fprice ?? 0),
                        fdiscpersen: Number(src.fdiscpersen ?? 0),
                        ftotprice: Number(src.ftotprice ?? 0),
                        fdesc: src.fdesc ?? '',
                        fketdt: src.fketdt ?? '',
                        units: Array.isArray(src.units) && src.units.length ? src.units : [src.fsatuan]
                            .filter(Boolean),
                    };
                    const key = this.itemKey({
                        fitemcode: row.fitemcode,
                        frefdtno: row.frefdtno
                    });

                    if (existing.has(key)) {
                        duplicates.push({
                            key,
                            code: row.fitemcode,
                            ref: row.frefdtno
                        });
                        return;
                    }

                    this.savedItems.push(row);
                    existing.add(key);
                    added++;
                });

                this.recalcTotals();
            },

            addIfComplete() {
                const r = this.draft;
                if (!this.isComplete(r)) {
                    if (!r.fitemcode) return this.$refs.draftCode?.focus();
                    if (!r.fitemname) return this.$refs.draftCode?.focus();
                    if (!r.fsatuan) return (r.units.length > 1 ? this.$refs.draftUnit?.focus() : this.$refs.draftCode
                        ?.focus());
                    if (!(Number(r.fqty) > 0)) return this.$refs.draftQty?.focus();
                    return;
                }

                this.recalc(r);

                const dupe = this.savedItems.find(it => it.fitemcode === r.fitemcode && it.fsatuan === r.fsatuan && (it
                    .frefpr || '') === (r.frefpr || ''));
                if (dupe) {
                    window.showAppWarningAlert('WARNING', 'ITEM SAMA SUDAH ADA.');
                    return;
                }

                this.savedItems.push({
                    ...r,
                    uid: cryptoRandom()
                });
                this.showNoItems = false;
                this.resetDraft();
                this.$nextTick(() => this.$refs.draftCode?.focus());
                this.syncDescList?.();
                this.showNoItems = false;

                this.recalcTotals();
            },

            edit(i) {
                this.editingIndex = i;
                this.editRow = {
                    ...this.savedItems[i]
                };
                this.hydrateRowFromMeta(this.editRow, this.productMeta(this.editRow.fitemcode));
                this.$nextTick(() => this.$refs.editQty?.focus());
            },

            applyEdit() {
                const r = this.editRow;
                if (!this.isComplete(r)) {
                    window.showAppWarningAlert('WARNING', 'LENGKAPI DATA ITEM.');
                    return;
                }
                this.recalc(r);
                this.savedItems.splice(this.editingIndex, 1, {
                    ...r
                });
                this.cancelEdit();
                this.syncDescList?.();
                this.recalcTotals();
            },

            cancelEdit() {
                this.editingIndex = null;
                this.editRow = newRow();
            },

            removeSaved(i) {
                this.savedItems.splice(i, 1);
                this.syncDescList?.();
                this.recalcTotals();
            },

            onSubmit($event) {
                if (this.savedItems.length === 0) {
                    $event.preventDefault();
                    this.showNoItems = true;
                    return;
                }
            },

            handleEnterOnCode(where) {
                if (where === 'edit') {
                    if (this.editRow.units.length > 1) this.$refs.editUnit?.focus();
                    else this.$refs.editQty?.focus();
                } else {
                    if (this.draft.units.length > 1) this.$refs.draftUnit?.focus();
                    else this.$refs.draftQty?.focus();
                }
            },

            // DESC Modal variables - INI SUDAH ADA DI COMPONENT, BUKAN DI STORE
            showDescModal: false,
            descTarget: 'draft',
            descSavedIndex: null,
            descValue: '',
            descItemName: '',

            openDesc(target, index = null) {
                this.descTarget = target;
                this.descSavedIndex = index;
                if (target === 'edit') {
                    this.descItemName = this.editRow.fitemname || '';
                    this.descValue = this.editRow.fdesc || '';
                } else if (target === 'saved' && index !== null) {
                    this.descItemName = this.savedItems[index]?.fitemname || '';
                    this.descValue = this.savedItems[index]?.fdesc || '';
                } else {
                    this.descItemName = this.draft.fitemname || '';
                    this.descValue = this.draft.fdesc || '';
                }
                this.showDescModal = true;
            },

            closeDesc() {
                this.showDescModal = false;
                this.descItemName = '';
                this.descValue = '';
            },
            copyDescName() {
                this.descValue = this.descItemName || '';
            },

            applyDesc() {
                if (this.descTarget === 'edit') {
                    this.editRow.fdesc = this.descValue;
                } else if (this.descTarget === 'saved' && this.descSavedIndex !== null) {
                    this.savedItems[this.descSavedIndex].fdesc = this.descValue;
                } else {
                    this.draft.fdesc = this.descValue;
                }
                this.closeDesc();
            },

            itemKey(it) {
                return `${(it.fitemcode ?? '').toString().trim()}::${(it.frefdtno ?? '').toString().trim()}`;
            },

            getCurrentItemKeys() {
                return this.savedItems.map(it => this.itemKey(it));
            },

            init() {
                this.$watch('includePPN', () => this.recalcTotals());
                this.$watch('fapplyppn', () => this.recalcTotals());
                this.$watch('ppnRate', () => this.recalcTotals());

                window.getCurrentItemKeys = () => this.getCurrentItemKeys();
                window.addEventListener('pr-picked', this.onPrPicked.bind(this), {
                    passive: true
                });

                window.addEventListener('product-chosen', (e) => {
                    const {
                        product
                    } = e.detail || {};
                    if (!product) return;
                    const apply = (row) => {
                        row.fitemcode = (product.fprdcode || '').toString();
                        this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode));
                        if (!row.fqty) row.fqty = 1;
                        this.recalc(row);
                    };
                    if (this.browseTarget === 'edit') {
                        apply(this.editRow);
                        this.$nextTick(() => this.$refs.editQty?.focus());
                    } else {
                        apply(this.draft);
                        this.$nextTick(() => this.$refs.draftQty?.focus());
                    }
                }, {
                    passive: true
                });

                // Recalculate totals on init
                this.recalcTotals();
            },

            browseTarget: 'draft',
            openBrowseFor(where) {
                this.browseTarget = (where === 'edit' ? 'edit' : 'draft');
                window.dispatchEvent(new CustomEvent('browse-open', {
                    detail: {
                        forEdit: this.browseTarget === 'edit'
                    }
                }));
            },
        };

        function newRow() {
            return {
                uid: null,
                fitemcode: '',
                fitemname: '',
                units: [],
                fsatuan: '',
                frefdtno: '',
                fnouref: '',
                frefpr: '',
                fqty: 0,
                fterima: 0,
                fprice: 0,
                fdiscpersen: 0,
                fbiaya: 0,
                ftotprice: 0,
                fdesc: '',
                fketdt: '',
                maxqty: 0,
            };
        }

        function cryptoRandom() {
            return (window.crypto?.getRandomValues ? [...window.crypto.getRandomValues(new Uint32Array(2))].map(n => n
                .toString(16)).join('') : Math.random().toString(36).slice(2)) + Date.now();
        }
    }
</script>

@include('components.transaction.salesorder-pr-modal-script')

<script>
    // Helper function untuk format tanggal (ditingkatkan sedikit)
    function formatDate(s) {
        if (!s || s === 'No Date') return '-';
        // Mencoba parsing format standar ISO 8601 atau yang didukung Date
        const d = new Date(s);
        if (isNaN(d.getTime())) return '-';

        // Format YYYY-MM-DD HH:MM
        const pad = n => n.toString().padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
    }
</script>

<script>
    // Warehouse Browser dengan DataTables
    window.warehouseBrowser = function() {
        return {
            open: false,
            table: null,

            initDataTable() {
                if (this.table) {
                    this.table.columns.adjust().draw(false);
                    return;
                }
                $('#warehouseTable').off('click.whpick');
                this.table = $('#warehouseTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('gudang.browse') }}",
                        type: 'GET',
                        data: function(d) {
                            return {
                                draw: d.draw,
                                start: d.start,
                                length: d.length,
                                search: d.search.value,
                                order_column: d.columns[d.order[0].column].data,
                                order_dir: d.order[0].dir
                            };
                        }
                    },
                    columns: [{
                            data: 'fbranchcode',
                            name: 'fbranchcode',
                            className: 'text-sm',
                            width: '15%',
                            render: function(data) {
                                return data || '<span class="text-gray-400">-</span>';
                            }
                        },
                        {
                            data: 'fwhcode',
                            name: 'fwhcode',
                            className: 'font-mono text-sm font-semibold',
                            width: '20%'
                        },
                        {
                            data: 'fwhname',
                            name: 'fwhname',
                            className: 'text-sm',
                            width: '50%'
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center',
                            width: '15%',
                            render: function(data, type, row) {
                                return '<button type="button" class="btn-choose px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150">Pilih</button>';
                            }
                        }
                    ],
                    pageLength: 10,
                    lengthMenu: [
                        [10, 25, 50, 100],
                        [10, 25, 50, 100]
                    ],
                    dom: '<"flex justify-between items-center mb-4"f<"ml-auto"l>>rtip',
                    language: {
                        processing: "Memuat data...",
                        search: "Cari:",
                        lengthMenu: "Tampilkan _MENU_",
                        info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                        infoEmpty: "Tidak ada data",
                        infoFiltered: "(disaring dari _MAX_ total data)",
                        zeroRecords: "Tidak ada data yang ditemukan",
                        emptyTable: "Tidak ada data tersedia",
                        paginate: {
                            first: "Pertama",
                            last: "Terakhir",
                            next: "Selanjutnya",
                            previous: "Sebelumnya"
                        }
                    },
                    order: [
                        [1, 'asc']
                    ],
                    autoWidth: false,
                    scrollX: false,
                    initComplete: function() {
                        const api = this.api();
                        const $container = $(api.table().container());

                        // Move controls to designated areas
                        const $filter = $container.find('.dataTables_filter');
                        const $length = $container.find('.dataTables_length');
                        const $info = $container.find('.dataTables_info');
                        const $paginate = $container.find('.dataTables_paginate');

                        // Style search input
                        $container.find('.dt-search .dt-input, .dataTables_filter input').css({
                            width: '300px',
                            padding: '8px 12px',
                            border: '2px solid #e5e7eb',
                            borderRadius: '8px',
                            fontSize: '14px'
                        }).focus();

                        // Style length select
                        $container.find('.dt-length select, .dataTables_length select').css({
                            padding: '6px 32px 6px 10px',
                            border: '2px solid #e5e7eb',
                            borderRadius: '8px',
                            fontSize: '14px'
                        });
                    }
                });

                // Handle button click
                $('#warehouseTable').on('click.whpick', '.btn-choose', (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    const data = this.table?.row($(e.currentTarget).closest('tr')).data();
                    if (!data) return;
                    this.choose(data);
                });
            },

            openModal() {
                this.open = true;
                this.$nextTick(() => {
                    this.initDataTable();
                });
            },

            close() {
                this.open = false;
                if (this.table) {
                    this.table.search('').draw();
                }
            },

            choose(w) {
                window.dispatchEvent(new CustomEvent('warehouse-picked', {
                    detail: {
                        fwhid: w.fwhid,
                        fwhcode: w.fwhcode,
                        fwhname: w.fwhname,
                        fbranchcode: w.fbranchcode
                    }
                }));
                this.close();
            },

            init() {
                window.addEventListener('warehouse-browse-open', () => this.openModal());
            }
        }
    };


    // Helper: update field saat warehouse-picked
    document.addEventListener('DOMContentLoaded', () => {
        window.addEventListener('warehouse-picked', (ev) => {
            const {
                fwhcode
            } = ev.detail || {};
            const sel = document.getElementById('warehouseSelect');
            const hid = document.getElementById('warehouseCodeHidden');
            if (sel) {
                sel.value = fwhcode || '';
                sel.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }
            if (hid) hid.value = fwhcode || '';
        });
    });
</script>

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    <script>
        // Modal produk dengan DataTables
        function productBrowser() {
            return {
                open: false,
                forEdit: false,
                table: null,

                initDataTable() {
                    if (this.table) {
                        this.table.destroy();
                    }

                    this.table = $('#productTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "{{ route('products.browse') }}",
                            type: 'GET',
                            data: function(d) {
                                return {
                                    draw: d.draw,
                                    start: d.start,
                                    length: d.length,
                                    search: d.search.value,
                                    order_column: d.columns[d.order[0].column].data,
                                    order_dir: d.order[0].dir
                                };
                            }
                        },
                        columns: [{
                                data: 'fprdcode',
                                name: 'fprdcode',
                                className: 'font-mono text-sm'
                            },
                            {
                                data: 'fprdname',
                                name: 'fprdname',
                                className: 'text-sm'
                            },
                            {
                                data: 'fsatuanbesar',
                                name: 'fsatuanbesar',
                                className: 'text-sm',
                                render: function(data) {
                                    return data || '-';
                                }
                            },
                            {
                                data: 'fmerekname',
                                name: 'fmerekname',
                                className: 'text-center text-sm',
                                render: function(data) {
                                    return data || '-';
                                }
                            },
                            {
                                data: 'fminstock',
                                name: 'fminstock',
                                className: 'text-center text-sm'
                            },
                            {
                                data: null,
                                orderable: false,
                                searchable: false,
                                className: 'text-center',
                                width: '100px',
                                render: function(data, type, row) {
                                    return '<button type="button" class="btn-choose px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150">Pilih</button>';
                                }
                            }
                        ],
                        pageLength: 10,
                        lengthMenu: [
                            [10, 25, 50, 100],
                            [10, 25, 50, 100]
                        ],
                        dom: '<"flex justify-between items-center mb-4"f<"ml-auto"l>>rtip',
                        language: {
                            processing: "Memuat data...",
                            search: "Cari:",
                            lengthMenu: "Tampilkan _MENU_",
                            info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                            infoEmpty: "Tidak ada data",
                            infoFiltered: "(disaring dari _MAX_ total data)",
                            zeroRecords: "Tidak ada data yang ditemukan",
                            emptyTable: "Tidak ada data tersedia",
                            paginate: {
                                first: "Pertama",
                                last: "Terakhir",
                                next: "Selanjutnya",
                                previous: "Sebelumnya"
                            }
                        },
                        order: [
                            [1, 'asc']
                        ],
                        autoWidth: false,
                        initComplete: function() {
                            const api = this.api();
                            const $container = $(api.table().container());

                            // Move controls to designated areas
                            const $filter = $container.find('.dataTables_filter');
                            const $length = $container.find('.dataTables_length');
                            const $info = $container.find('.dataTables_info');
                            const $paginate = $container.find('.dataTables_paginate');

                            // Style search input
                            $container.find('.dt-search .dt-input, .dataTables_filter input').css({
                                width: '300px',
                                padding: '8px 12px',
                                border: '2px solid #e5e7eb',
                                borderRadius: '8px',
                                fontSize: '14px'
                            }).focus();

                            // Style length select
                            $container.find('.dt-length select, .dataTables_length select').css({
                                padding: '6px 32px 6px 10px',
                                border: '2px solid #e5e7eb',
                                borderRadius: '8px',
                                fontSize: '14px'
                            });
                        }
                    });

                    // Handle button click
                    $('#productTable').on('click', '.btn-choose', (e) => {
                        const data = this.table.row($(e.target).closest('tr')).data();
                        this.choose(data);
                    });
                },

                close() {
                    this.open = false;
                    if (this.table) {
                        this.table.search('').draw();
                    }
                },

                choose(product) {
                    window.dispatchEvent(new CustomEvent('product-chosen', {
                        detail: {
                            product: product,
                            forEdit: this.forEdit
                        }
                    }));
                    this.close();
                },

                init() {
                    window.addEventListener('browse-open', (e) => {
                        this.open = true;
                        this.forEdit = !!(e.detail && e.detail.forEdit);

                        // Initialize DataTable setelah modal terbuka
                        this.$nextTick(() => {
                            this.initDataTable();
                        });
                    }, {
                        passive: true
                    });
                }
            }
        }

        document.addEventListener('alpine:init', () => {
            Alpine.store('prh', {
                descPreview: {
                    uid: null,
                    index: null,
                    label: '',
                    text: ''
                },
                descList: []
            });
        });

        // Modal supplier
        function supplierBrowser() {
            return {
                open: false,
                dataTable: null,

                initDataTable() {
                    if (this.dataTable) {
                        this.dataTable.destroy();
                    }

                    this.dataTable = $('#supplierBrowseTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "{{ route('suppliers.browse') }}",
                            type: 'GET',
                            data: function(d) {
                                return {
                                    draw: d.draw,
                                    start: d.start,
                                    length: d.length,
                                    search: d.search.value,
                                    order_column: d.columns[d.order[0].column].data,
                                    order_dir: d.order[0].dir
                                };
                            }
                        },
                        columns: [{
                                data: 'fsuppliercode',
                                name: 'fsuppliercode',
                                className: 'font-mono text-sm',
                                width: '15%'
                            },
                            {
                                data: 'fsuppliername',
                                name: 'fsuppliername',
                                className: 'text-sm',
                                width: '25%'
                            },
                            {
                                data: 'faddress',
                                name: 'faddress',
                                className: 'text-sm',
                                defaultContent: '-',
                                orderable: false,
                                width: '30%'
                            },
                            {
                                data: 'ftelp',
                                name: 'ftelp',
                                className: 'text-sm',
                                defaultContent: '-',
                                orderable: false,
                                width: '15%'
                            },
                            {
                                data: null,
                                orderable: false,
                                searchable: false,
                                className: 'text-center',
                                width: '15%',
                                render: function(data, type, row) {
                                    return '<button type="button" class="btn-choose px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150">Pilih</button>';
                                }
                            }
                        ],
                        pageLength: 10,
                        lengthMenu: [
                            [10, 25, 50, 100],
                            [10, 25, 50, 100]
                        ],
                        dom: '<"flex justify-between items-center mb-4"f<"ml-auto"l>>rtip',
                        language: {
                            processing: "Memuat data...",
                            search: "Cari:",
                            lengthMenu: "Tampilkan _MENU_",
                            info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                            infoEmpty: "Tidak ada data",
                            infoFiltered: "(disaring dari _MAX_ total data)",
                            zeroRecords: "Tidak ada data yang ditemukan",
                            emptyTable: "Tidak ada data tersedia",
                            paginate: {
                                first: "Pertama",
                                last: "Terakhir",
                                next: "Selanjutnya",
                                previous: "Sebelumnya"
                            }
                        },
                        order: [
                            [1, 'asc']
                        ],
                        autoWidth: false,
                        initComplete: function() {
                            const api = this.api();
                            const $container = $(api.table().container());

                            // Move controls to designated areas
                            const $filter = $container.find('.dataTables_filter');
                            const $length = $container.find('.dataTables_length');
                            const $info = $container.find('.dataTables_info');
                            const $paginate = $container.find('.dataTables_paginate');

                            // Style search input
                            $container.find('.dt-search .dt-input, .dataTables_filter input').css({
                                width: '300px',
                                padding: '8px 12px',
                                border: '2px solid #e5e7eb',
                                borderRadius: '8px',
                                fontSize: '14px'
                            }).focus();

                            // Style length select
                            $container.find('.dt-length select, .dataTables_length select').css({
                                padding: '6px 32px 6px 10px',
                                border: '2px solid #e5e7eb',
                                borderRadius: '8px',
                                fontSize: '14px'
                            });
                        }
                    });

                    // Handle button click
                    $('#supplierBrowseTable').on('click', '.btn-choose', (e) => {
                        const data = this.dataTable.row($(e.target).closest('tr')).data();
                        this.chooseSupplier(data);
                    });
                },

                openBrowse() {
                    this.open = true;
                    this.$nextTick(() => {
                        this.initDataTable();
                    });
                },

                close() {
                    this.open = false;
                    if (this.dataTable) {
                        this.dataTable.search('').draw();
                    }
                },

                chooseSupplier(supplier) {
                    const sel = document.getElementById('modal_filter_supplier_id');
                    const hid = document.getElementById('supplierCodeHidden');

                    if (!sel) {
                        this.close();
                        return;
                    }

                    let opt = [...sel.options].find(o => o.value == String(supplier.fsuppliercode));
                    const label = `${supplier.fsuppliername} (${supplier.fsuppliercode})`;

                    if (!opt) {
                        opt = new Option(label, supplier.fsuppliercode, true, true);
                        sel.add(opt);
                    } else {
                        opt.text = label;
                        opt.selected = true;
                    }

                    sel.dispatchEvent(new Event('change'));
                    if (hid) hid.value = supplier.fsuppliercode;
                    this.close();
                },

                init() {
                    window.addEventListener('supplier-browse-open', () => this.openBrowse(), {
                        passive: true
                    });
                }
            }
        }
    </script>
@endpush
