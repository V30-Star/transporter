@extends('layouts.app')

@section('title', 'Surat Jalan')

@section('content')
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
    </style>

    <div x-data="{ open: true }">
        <div x-data="{ includePPN: false, ppnRate: 0, ppnAmount: 0, totalHarga: 100000 }" class="lg:col-span-5">
            <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1600px] w-full mx-auto">
                <div class="space-y-4">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                        <div class="lg:col-span-4">
                            <label class="block text-sm font-bold">Cabang</label>
                            <input type="text" class="w-full border rounded px-3 py-2 bg-gray-200 cursor-not-allowed"
                                value="{{ $fcabang }}" disabled>
                            <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                        </div>
                        <div class="lg:col-span-4" x-data="{ autoCode: true }">
                            <label class="block text-sm font-bold mb-1">Transaksi#</label>
                            <div class="flex items-center gap-3">
                                <input type="text" name="fstockmtno" class="w-full border rounded px-3 py-2" value="{{ old('fstockmtno', $suratjalan->fstockmtno) }}"
                                    :disabled="autoCode"
                                    :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'">
                                <label class="inline-flex items-center select-none">
                                    <input type="checkbox" x-model="autoCode" checked disabled>
                                    <span class="ml-2 text-sm text-gray-700">Auto</span>
                                </label>
                            </div>
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-bold">Tanggal</label>
                            <input type="date" name="fstockmtdate" value="{{ old('fstockmtdate') ?? date('Y-m-d') }}" disabled
                                class="w-full border rounded px-3 py-2 @error('fstockmtdate') border-red-500 @enderror">
                            @error('fstockmtdate')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-bold mb-1">Customer</label>
                            <div class="flex">
                                <div class="relative flex-1" for="modal_filter_customer_id">
                                    <select id="modal_filter_customer_id" name="filter_customer_id"
                                        class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 bg-gray-200 cursor-not-allowed"
                                        disabled>
                                        <option value=""></option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->fcustomercode }}" {{-- CEK DISINI: Bandingkan dengan data yang tersimpan di DB --}}
                                                {{ old('fsupplier', $suratjalan->fsupplier) == $customer->fcustomercode ? 'selected' : '' }}>
                                                {{ $customer->fcustomername }} ({{ $customer->fcustomercode }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-0" role="button" aria-label="Browse Customer"
                                        @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))">
                                    </div>
                                </div>
                                <input type="hidden" name="fsupplier" id="customerCodeHidden"
                                    value="{{ old('fsupplier', $suratjalan->fsupplier) }}">
                            </div>
                            @error('fsupplier')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-bold mb-1">Gudang</label>
                            <div class="flex">
                                <div class="relative flex-1">

                                    <select id="warehouseSelect"
                                        class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
                                        disabled>
                                        <option value=""></option>
                                        @foreach ($warehouses as $wh)
                                            <option value="{{ $wh->fwhcode }}" data-id="{{ $wh->fwhid }}"
                                                data-branch="{{ $wh->fbranchcode }}"
                                                {{ old('ffrom', $suratjalan->ffrom) == $wh->fwhcode ? 'selected' : '' }}>
                                                {{ $wh->fwhcode }} - {{ $wh->fwhname }}
                                            </option>
                                        @endforeach
                                    </select>

                                    {{-- Overlay untuk buka browser gudang --}}
                                    <div class="absolute inset-0" role="button" aria-label="Browse warehouse"
                                        @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open'))"></div>
                                </div>
                                <input type="hidden" name="ffrom" id="warehouseCodeHidden"
                                    value="{{ old('ffrom', $suratjalan->ffrom) }}">
                            </div>
                        </div>

                        <div class="lg:col-span-12">
                            <label class="block text-sm font-bold">Kirim ke</label>
                            <textarea readonly name="fkirim" rows="3"
                                class="w-full border rounded px-3 py-2 bg-gray-200 @error('fkirim') border-red-500 @enderror"
                                placeholder="Tulis kirim tambahan di sini...">{{ old('fkirim', $suratjalan->fkirim) }}</textarea>
                            @error('fkirim')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="lg:col-span-12">
                            <label class="block text-sm font-bold">Keterangan</label>
                            <textarea readonly name="fket" rows="3"
                                class="w-full border rounded px-3 py-2 text-gray-700 bg-gray-200 @error('fket') border-red-500 @enderror"
                                placeholder="Tulis keterangan tambahan di sini...">{{ old('fket', $suratjalan->fket) }}</textarea>
                            @error('fket')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div x-data="itemsTable()" x-init="init()" class="mt-6 space-y-2">

                        {{-- DETAIL ITEM (tabel input) --}}
                        <h3 class="text-base font-semibold text-gray-800">Detail Item</h3>

                        <div class="overflow-auto border rounded">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="p-2 text-left w-10">#</th>
                                        <th class="p-2 text-left w-40">Kode Produk</th>
                                        <th class="p-2 text-left w-102">Nama Produk</th>
                                        <th class="p-2 text-left w-36">Ref.PO#</th>
                                        <th class="p-2 text-right w-24">Sat</th>
                                        <th class="p-2 text-right w-28">Qty</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <template x-for="(it, i) in savedItems" :key="it.uid">
                                        <tr class="border-t align-top">
                                            <td class="p-2" x-text="i + 1"></td>
                                            <td class="p-2 font-mono" x-text="it.fitemcode"></td>
                                            <td class="p-2 text-gray-800">
                                                <div x-text="it.fitemname"></div>
                                                <div x-show="it.fdesc" class="mt-1 text-xs">
                                                    <span
                                                        class="inline-block px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 mr-2">Deskripsi</span>
                                                    <span class="align-middle text-gray-600" x-text="it.fdesc"></span>
                                                </div>
                                            </td>
                                            <td class="p-2" x-text="it.frefno_display || it.frefso || '-'"></td>
                                            <td class="p-2 text-right" x-text="it.fsatuan"></td>
                                            <td class="p-2 text-right" x-text="formatQtyValue(it.fqty)"></td>
                                            <td class="hidden">
                                                <input type="hidden" name="fitemcode[]" :value="it.fitemcode">
                                                <input type="hidden" name="fitemname[]" :value="it.fitemname">
                                                <input type="hidden" name="fsatuan[]" :value="it.fsatuan">

                                                <input type="hidden" name="frefdtno[]" :value="it.frefdtno">

                                                <input type="hidden" name="frefpr[]" :value="it.frefpr">
                                                <input type="hidden" name="fqty[]" :value="it.fqty">
                                                <input type="hidden" name="fprice[]" :value="it.fprice">
                                                <input type="hidden" name="ftotal[]" :value="it.ftotal">
                                                <input type="hidden" name="fdesc[]" :value="it.fdesc">
                                                <input type="hidden" name="fketdt[]" :value="it.fketdt">
                                            </td>
                                        </tr>

                                    </template>

                                    <tr x-show="editingIndex !== null" class="border-t align-top" x-cloak>
                                        <td class="p-2" x-text="(editingIndex ?? 0) + 1"></td>

                                        <td class="p-2">
                                            <div class="flex">
                                                <input type="text" class="flex-1 border rounded-l px-2 py-1 font-mono"
                                                    x-ref="editCode" x-model.trim="editRow.fitemcode"
                                                    @input="onCodeTypedRow(editRow)"
                                                    @keydown.enter.prevent="handleEnterOnCode('edit')">
                                                <button type="button" @click="openBrowseFor('edit')"
                                                    class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50"
                                                    title="Cari Produk">
                                                    <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                </button>
                                              
                                            </div>
                                        </td>

                                        <td class="p-2">
                                            <input type="text"
                                                class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                :value="editRow.fitemname" disabled>
                                        </td>

                                        <td class="p-2">
                                            <input type="text"
                                                class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                    :value="editRow.frefno_display || editRow.frefso || '-'" disabled placeholder="Ref SO">
                                        </td>

                                        <td class="p-2">
                                            <template x-if="editRow.units.length > 1">
                                                <select class="w-full border rounded px-2 py-1" x-ref="editUnit"
                                                    x-model="editRow.fsatuan"
                                                    @keydown.enter.prevent="$refs.editRefPr?.focus()">
                                                    <template x-for="u in editRow.units" :key="u">
                                                        <option :value="u" x-text="u"></option>
                                                    </template>
                                                </select>
                                            </template>
                                            <template x-if="editRow.units.length <= 1">
                                                <input type="text"
                                                    class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                    :value="editRow.fsatuan || '-'" disabled>
                                            </template>
                                        </td>

                                        <td class="p-2 text-right">
                                            <input type="number" class="border rounded px-2 py-1 w-24 text-right"
                                                min="0" step="1" x-ref="editQty"
                                                x-model.number="editRow.fqty" @change="recalc(editRow)"
                                                @blur="recalc(editRow)" @keydown.enter.prevent="$refs.editPrice?.focus()">
                                        </td>
                                    </tr>

                                    <tr x-show="editingIndex !== null" class="border-b" x-cloak>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                    </tr>

                                    <tr class="border-b">
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
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
                <x-transaction.browse-customer-modal />

                <x-transaction.browse-warehouse-modal />
                <x-transaction.browse-product-modal show-controls="true" show-pagination="true" />

                <div class="mt-6 flex justify-center space-x-4">
                    <a href="{{ route('suratjalan.print', $suratjalan->fstockmtno) }}" target="_blank"
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m10 0v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5m10 0v5H7v-5">
                            </path>
                        </svg>
                        Print
                    </a>
                    <button type="button" onclick="window.location.href='{{ route('suratjalan.index') }}'"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                        Kembali
                    </button>
                </div>
            </div>
        </div>
    @endsection
    @push('styles')
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    @endpush
    <x-transaction.datatables-length-styles :tables="['supplierTable', 'warehouseTable', 'productTable', 'poTable']" />
    {{-- DATA & SCRIPTS --}}
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
            Alpine.store('trsomt', {
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
        });

        function itemsTable() {
            return {
                showNoItems: false,
                savedItems: @json($savedItems),
                draft: newRow(),
                editingIndex: null,
                editRow: newRow(),

                totalHarga: 0,

                fmt(n) {
                    if (n === null || n === undefined || n === '') return '-';
                    const v = Number(n);
                    if (!isFinite(v)) return '-';

                    // Jika angka adalah bulat, hilangkan desimal
                    if (Number.isInteger(v)) {
                        return v.toLocaleString('id-ID');
                    } else {
                        return v.toLocaleString('id-ID', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                    }
                },

                formatQtyValue(value) {
                    const num = Number(value);
                    if (!Number.isFinite(num)) return '0,00';
                    const hasMoreThanTwoDecimals = Math.abs((num * 100) - Math.round(num * 100)) > 0.000001;
                    const digits = hasMoreThanTwoDecimals ? 4 : 2;
                    return num.toLocaleString('id-ID', {
                        minimumFractionDigits: digits,
                        maximumFractionDigits: digits
                    });
                },

                rupiah(n) {
                    const v = Number(n || 0);
                    if (!isFinite(v)) return 'Rp -';
                    return 'Rp ' + v.toLocaleString('id-ID', {
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
                            frefdtno: src.frefdtno ?? '',
                            // frefdtno: src.frefdtno ?? '', // <-- Ini duplikat, saya hapus 1
                            frefpr: src.frefpr ?? (header?.fpono ?? ''),
                            fqty: Number(src.fqty ?? 0),
                            fprice: Number(src.fprice ?? 0),
                            ftotal: Number(src.ftotal ?? 0),
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

                    const dupe = this.savedItems.find(it =>
                        it.fitemcode === r.fitemcode &&
                        it.fsatuan === r.fsatuan &&
                        (it.frefpr || '') === (r.frefpr || '')
                    );

                    if (dupe) {
                        alert('Item sama sudah ada.');
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
                        alert('Lengkapi data item.');
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

                showDescModal: false,
                descTarget: 'draft',
                descSavedIndex: null,
                descValue: '',
                openDesc() {},
                closeDesc() {},
                applyDesc() {},

                itemKey(it) {
                    return `${(it.fitemcode ?? '').toString().trim()}::${(it.frefdtno ?? '').toString().trim()}`;
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
                    this.$nextTick(() => {
                        this.recalcTotals();
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
                    frefdtno: '',
                    frefpr: '',
                    fqty: 0,
                    fprice: 0,
                    ftotal: 0,
                    fdesc: '',
                    fketdt: '',
                    maxqty: 0,
                };
            }

            function cryptoRandom() {
                return (window.crypto?.getRandomValues ? [...window.crypto.getRandomValues(new Uint32Array(2))].map(n => n
                        .toString(16)).join('') :
                    Math.random().toString(36).slice(2)) + Date.now();
            }
        }
    </script>

    @include('components.transaction.suratjalan-po-modal-script')

    <script>
        // Helper function untuk format tanggal
        function formatDate(s) {
            if (!s || s === 'No Date') return '-';
            const d = new Date(s);
            if (isNaN(d)) return '-';
            const pad = n => n.toString().padStart(2, '0');
            return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
        }
    </script>

    <script>
        // Helper: update field saat warehouse-picked
        document.addEventListener('DOMContentLoaded', () => {
            window.addEventListener('warehouse-picked', (ev) => {
                const {
                    fwhcode,
                    fwhid
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

    @include('components.transaction.browse-customer-script')
    @include('components.transaction.browse-warehouse-script')
    @include('components.transaction.browse-product-script', ['showControls' => true, 'showPagination' => true, 'supportsForEdit' => true])

    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

        <script>
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
