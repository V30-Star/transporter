@extends('layouts.app')

@section('title', 'Pemakaian Barang - View')

@section('content')
    @php
        $permissions = explode(',', session('user_restricted_permissions', ''));
        $canPrint =
            in_array('updatePemakaianBarang', $permissions, true) ||
            in_array('deletePemakaianBarang', $permissions, true) ||
            in_array('createPemakaianbarang', $permissions, true);
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

        /* Hilangkan panah di input number (Firefox) */
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

        input::placeholder,
        textarea::placeholder {
            color: #9ca3af !important;
            font-weight: normal !important;
        }

        input:disabled::placeholder,
        textarea:disabled::placeholder {
            color: #9ca3af !important;
            -webkit-text-fill-color: #9ca3af !important;
            font-weight: normal !important;
        }
    </style>

    <div x-data="{ open: true }">
        <div x-data="{
            open: true,
            accounts: @js($accounts),
            subaccounts: @js($subaccounts),
            savedItems: []
        }">
            <div>
                {{-- ─── CARD 1: Identitas ────────────────────── --}}
                <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                        <x-heroicon-o-identification class="w-5 h-5 text-blue-600" />
                        <h2 class="font-semibold text-gray-800">Identitas Pemakaian Barang</h2>
                    </div>
                    <div class="p-4">
                    {{-- HEADER FORM --}}
                    <div class="grid grid-cols-3 gap-3">
                        <div class="lg:col-span-4">
                            <label class="text-xs font-bold text-gray-600 mb-1">Cabang</label>
                            <input type="text" class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 bg-gray-200 cursor-not-allowed"
                                value="{{ trim(($fbranchcode ?? '') . ($fcabang ?? '' ? ' - ' . $fcabang : '')) }}"
                                disabled>
                            <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                        </div>
                        <div class="lg:col-span-4" x-data="{ autoCode: true }">
                            <label class="text-xs font-bold text-gray-600 mb-1">Transaksi#</label>
                            <div class="flex items-center gap-3">
                                <input type="text" name="fstockmtno"
                                    value="{{ old('fstockmtno', $pemakaianbarang->fstockmtno) }}"
                                    class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" :disabled="autoCode"
                                    :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'">
                                <label class="inline-flex items-center select-none">
                                    <input type="checkbox" x-model="autoCode" checked>
                                    <span class="ml-2 text-sm text-gray-700">Auto</span>
                                </label>
                            </div>
                        </div>

                        <input type="hidden" name="fstockmtid" value="fstockmtid">

                        <div class="lg:col-span-4">
                            <label class="text-xs font-bold text-gray-600 mb-1">Tanggal</label>
                            <input disabled type="date" name="fstockmtdate"
                                value="{{ old('fstockmtdate') ?? date('Y-m-d') }}"
                                class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 bg-gray-100 @error('fstockmtdate') border-red-500 @enderror">
                            @error('fstockmtdate')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Field FROM -->
                        <div class="lg:col-span-4">
                            <label class="text-xs font-bold text-gray-600 mb-1">Gudang</label>
                            <div class="flex">
                                <div class="relative flex-1">
                                    <select id="warehouseSelectFrom"
                                        class="w-full border-gray-300 rounded-l-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 bg-gray-100 text-gray-700 cursor-not-allowed"
                                        disabled>
                                        <option value=""></option>
                                        @foreach ($warehouses as $wh)
                                            <option value="{{ $wh->fwhcode }}" data-id="{{ $wh->fwhid }}"
                                                data-branch="{{ $wh->fbranchcode }}"
                                                {{ old('ffrom', $pemakaianbarang->ffrom) == $wh->fwhcode ? 'selected' : '' }}>
                                                {{ $wh->fwhcode }} - {{ $wh->fwhname }}
                                            </option>
                                        @endforeach
                                    </select>

                                    {{-- Overlay untuk buka browser gudang --}}
                                    <div class="absolute inset-0" role="button" aria-label="Browse warehouse"
                                        @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open'))"></div>
                                </div>
                                <input type="hidden" name="ffrom" id="warehouseCodeHiddenFrom"
                                    value="{{ old('ffrom', $pemakaianbarang->ffrom) }}">

                                {{-- Tombol-tombol Anda --}}
                                <button type="button" disabled
                                    @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open'))"
                                    class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                                    title="Browse Gudang">
                                    <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                </button>
                                <button disabled href="{{ route('gudang.create') }}" target="_blank" rel="noopener"
                                    class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50"
                                    title="Tambah Supplier">
                                    <x-heroicon-o-plus class="w-5 h-5" />
                                </button>
                            </div>
                        </div>

                        <div class="lg:col-span-12">
                            <label class="text-xs font-bold text-gray-600 mb-1">Keterangan</label>
                            <textarea readonly name="fket" rows="3"
                                class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 bg-gray-100 @error('fket') border-red-500 @enderror"
                                placeholder="Tulis keterangan tambahan di sini...">{{ old('fket', $pemakaianbarang->fket) }}</textarea>
                            @error('fket')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    </div>

                    {{-- ─── CARD 2: Detail Item ─────────────────────── --}}
                    <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                            <x-heroicon-o-list-bullet class="w-5 h-5 text-blue-600" />
                            <h2 class="font-semibold text-gray-800">Detail Item</h2>
                        </div>
                        <div class="p-4">

                    <div x-data="itemsTable()" x-init="init()" class="space-y-2">

                        {{-- DETAIL ITEM (tabel input) --}}
                        <div class="overflow-auto border rounded">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="p-2 text-left w-10">#</th>
                                        <th class="p-2 text-left w-40">Kode Produk</th>
                                        <th class="p-2 text-left min-w-[12rem]">Nama Produk</th>
                                        <th class="p-2 text-left w-48">Account</th>
                                        <th class="p-2 text-left w-48">Sub Account</th>
                                        <th class="p-2 text-left w-24">Sat</th>
                                        <th class="p-2 text-right w-36">Qty</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <template x-for="(it, i) in savedItems" :key="it.uid">
                                        <!-- ROW UTAMA -->
                                        <tr class="border-t align-top">
                                            <td class="p-2" x-text="i + 1"></td>
                                            <td class="p-2 font-mono" x-text="it.fitemcode"></td>
                                            <td class="p-2 text-gray-800" style="width: 20rem; min-width: 20rem;">
                                                <div class="desc-inline-field">
                                                    <div class="desc-inline-field__text rounded-l border bg-gray-100 px-2 py-1 text-sm leading-5 text-gray-600 whitespace-normal break-words"
                                                        x-text="it.fitemname"></div>
                                                    <button type="button" @click="openDesc(it)"
                                                        class="desc-inline-field__button inline-flex items-center border border-l-0 rounded-r px-2 py-1 transition-colors"
                                                        :class="it.fdesc ?
                                                            'border-emerald-300 bg-emerald-50 text-emerald-600 hover:bg-emerald-100' :
                                                            'border-gray-300 bg-white text-gray-500 hover:bg-gray-50'"
                                                        title="Deskripsi item">
                                                        <x-heroicon-o-document-text class="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="p-2 text-left">
                                                <span x-text="it.account_label"></span>
                                            </td>
                                            <td class="p-2 text-left">
                                                <span x-text="it.subaccount_label"></span>
                                            </td>
                                            <td class="p-2 text-left" x-text="it.fsatuan"></td>
                                            <td class="p-2 text-right" x-text="fmt(it.fqty)"></td>

                                            <!-- hidden inputs -->
                                            <td class="hidden">
                                                <input type="hidden" name="fitemcode[]" :value="it.fitemcode">
                                                <input type="hidden" name="fitemname[]" :value="it.fitemname">
                                                <input type="hidden" name="fsatuan[]" :value="it.fsatuan">
                                                <input type="hidden" name="frefdtno[]" :value="it.account_code">
                                                <input type="hidden" name="frefso[]" :value="it.subaccount_code">
                                                <input type="hidden" name="frefpr[]" :value="it.frefpr">
                                                <input type="hidden" name="fqty[]" :value="it.fqty">
                                                <input type="hidden" name="fdesc[]" :value="it.fdesc">
                                                <input type="hidden" name="fketdt[]" :value="it.fketdt">
                                            </td>
                                        </tr>

                                        <tr class="border-b">
                                            <td class="p-0"></td> <!-- # -->
                                            <td class="p-0"></td> <!-- Kode -->
                                            <!-- Deskripsi HANYA di kolom Nama Produk -->
                                            <!-- Kolom sisanya kosong supaya total 7 kolom -->
                                            <td class="p-0"></td> <!-- Satuan -->
                                            <td class="p-0"></td> <!-- Qty -->
                                            <td class="p-0"></td> <!-- Ket Item -->
                                            <td class="p-0"></td> <!-- Aksi -->
                                        </tr>
                                    </template>

                                    <!-- ROW EDIT DESC -->
                                    <tr x-show="editingIndex !== null" class="bg-amber-50 border-b" x-cloak>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                    </tr>

                                    <!-- ROW DRAFT DESC -->
                                    <tr class="bg-green-50 border-b">
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    </div>
                </div>

                {{-- ─── CARD 3: Aksi ─────────────────────────── --}}
                <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                <div class="flex items-center justify-end gap-3 px-4 py-3 bg-gray-50 border-t border-gray-200">
                @php $isPrinted = (int) ($pemakaianbarang->fprint ?? 0) === 1; @endphp
                    <button type="button" onclick="window.location.href='{{ route('pemakaianbarang.index') }}'"
                        class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 bg-white text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-colors">
                        <x-heroicon-o-arrow-left class="w-4 h-4" />
                        Kembali
                    </button>
                    @if ($canPrint)
                        <a href="{{ route('pemakaianbarang.print', $pemakaianbarang->fstockmtno) }}" target="_blank"
                            class="inline-flex items-center gap-2 px-5 py-2 {{ $isPrinted ? 'bg-gray-400 pointer-events-none cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700' }} text-white text-sm font-medium rounded-lg transition-colors">
                            <x-heroicon-o-printer class="w-4 h-4" />
                            Print
                        </a>
                    @endif
                </div>
                </div>

                <div x-show="$store.pemakaianDesc.show" x-cloak
                    class="fixed inset-0 z-[95] flex items-center justify-center" x-transition.opacity>
                    <div class="absolute inset-0 bg-black/50" @click="$store.pemakaianDesc.close()"></div>
                    <div class="relative bg-white w-[92vw] max-w-lg rounded-2xl shadow-2xl overflow-hidden"
                        x-transition.scale>
                        <div class="px-5 py-4 border-b flex items-center">
                            <x-heroicon-o-document-text class="w-6 h-6 text-blue-600 mr-2" />
                            <h3 class="text-lg font-semibold text-gray-800">Deskripsi Item</h3>
                        </div>
                        <div class="px-5 py-4 space-y-4">
                            <div>
                                <div class="mb-1 flex items-center justify-between gap-3">
                                    <div class="text-sm text-gray-700">Nama Produk</div>
                                    <button type="button" @click="$store.pemakaianDesc.copyName()"
                                        class="h-8 px-3 rounded-lg bg-blue-50 text-blue-700 text-sm font-medium hover:bg-blue-100">
                                        Copy
                                    </button>
                                </div>
                                <div class="rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800"
                                    x-text="$store.pemakaianDesc.itemName || '-'"></div>
                            </div>
                            <label class="block text-sm text-gray-700">Deskripsi</label>
                            <textarea x-model="$store.pemakaianDesc.value" rows="5"
                                class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 bg-gray-100 cursor-not-allowed text-gray-600" readonly></textarea>
                        </div>
                        <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
                            <button type="button" @click="$store.pemakaianDesc.close()"
                                class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">
                                Tutup
                            </button>
                        </div>
                    </div>
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
    div#productTable_length select,
    .dataTables_wrapper #productTable_length select,
    table#supplierBrowseTable+.dataTables_wrapper .dataTables_length select {
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
</style>
{{-- DATA & SCRIPTS --}}

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            $('.select2').select2({
                width: '100%'
            });
        });

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
            Alpine.store('prh', {
                // desc yang sedang dipreview
                descPreview: {
                    uid: null,
                    index: null,
                    label: '',
                    text: ''
                },
                // optional: daftar semua desc
                descList: []
            });
            Alpine.store('pemakaianDesc', {
                show: false,
                value: '',
                itemName: '',
                readonly: false,
                target: null,
                open(targetRow, readonly = true) {
                    if (!targetRow) return;
                    this.target = targetRow;
                    this.value = (targetRow?.fdesc || '').toString();
                    this.itemName = (targetRow?.fitemname || '').toString();
                    this.readonly = !!readonly;
                    this.show = true;
                },
                close() {
                    this.show = false;
                    this.value = '';
                    this.itemName = '';
                    this.readonly = false;
                    this.target = null;
                },
                copyName() {
                    this.value = this.itemName || '';
                },
                apply() {
                    this.close();
                }
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

                updateAccount(row, accountCode, accName) {
                    row.account_code = accountCode;
                    row.account_name = accName;

                    // Opsional: Cek apakah item lain di draft/edit perlu di-recalc
                    // this.recalc(row); 
                },

                updateSubAccount(row, subAccountCode, subAccName) {
                    row.subaccount_code = subAccountCode;
                    row.subaccount_name = subAccName;
                },

                fmt(n) {
                    if (n === null || n === undefined || n === '') return '-';
                    const v = Number(n);
                    if (!isFinite(v)) return '-';

                    // Jika angka adalah bulat, hilangkan desimal
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
                    this.$nextTick(() => {
                        row.fqty = Math.max(0, Number(row.fqty) || 0);
                        row.fterima = Math.max(0, Number(row.fterima) || 0);
                        row.fprice = Math.max(0, Number(row.fprice) || 0);

                        row.ftotal = Number((row.fqty * row.fprice).toFixed(2));

                        this.recalcTotals();
                    });
                },

                recalcTotals() {
                    this.totalHarga = (this.savedItems || []).reduce((sum, it) => {
                        const v = Number(it?.ftotal ?? 0);
                        return sum + (Number.isFinite(v) ? v : 0);
                    }, 0);
                },

                removeSaved(i) {
                    this.savedItems.splice(i, 1);
                    this.syncDescList?.();
                    this.recalcTotals();
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
                            frefpr: src.frefpr ?? (header?.fpono ?? ''),
                            fqty: Number(src.fqty ?? 0),
                            fdesc: src.fdesc ?? '',
                            fketdt: src.fketdt ?? '',
                            units: Array.isArray(src.units) && src.units.length ? src.units : [src.fsatuan]
                                .filter(Boolean),
                        };

                        const key = this.itemKey({
                            fitemcode: row.fitemcode,
                            account_code: row.account_code
                        });

                        if (existing.has(key)) {
                            duplicates.push({
                                key,
                                code: row.fitemcode,
                                ref: row.account_code
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

                    const dupe = this.savedItems.find(it =>
                        it.fitemcode === r.fitemcode &&
                        it.fsatuan === r.fsatuan && (it.fdesc || '') === (r.fdesc || '') &&
                        (it.frefpr || '') === (r.frefpr || '')
                    );

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

                handleEnterOnPrice(where) {
                    if (where === 'edit') {
                        this.applyEdit();
                    } else {
                        this.addIfComplete();
                    }
                },

                openDesc(targetRow) {
                    Alpine.store('pemakaianDesc').open(targetRow, true);
                },

                itemKey(it) {
                    return `${(it.fitemcode ?? '').toString().trim()}::${(it.account_code ?? '').toString().trim()}`;
                },

                getCurrentItemKeys() {
                    return this.savedItems.map(it => this.itemKey(it));
                },

                init() {
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
                    frefpr: '',
                    fqty: 0,
                    fdesc: '',
                    fketdt: '',
                    maxqty: 0,
                    account_code: null,
                    account_name: '',
                    account_label: '',
                    subaccount_code: null,
                    subaccount_name: '',
                    subaccount_label: '',
                };
            }

            function cryptoRandom() {
                return (window.crypto?.getRandomValues ? [...window.crypto.getRandomValues(new Uint32Array(2))].map(n => n
                        .toString(16)).join('') :
                    Math.random().toString(36).slice(2)) + Date.now();
            }
        }

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
                    fwhcode,
                    fwhid
                } = ev.detail || {};
                const sel = document.getElementById('warehouseSelectFrom');
                const hid = document.getElementById('warehouseCodeHiddenFrom');
                if (sel) {
                    sel.value = fwhcode || '';
                    sel.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                }
                if (hid) hid.value = fwhcode || '';
            });
        });

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
    </script>
@endpush
