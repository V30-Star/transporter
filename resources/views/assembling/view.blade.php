@extends('layouts.app')

@section('title', 'Assembling')

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
        <div x-data="{
            open: true,
            savedItems: []
        }" class="lg:col-span-5">
            <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1600px] w-full mx-auto">
                <div class="space-y-4">
                    {{-- HEADER FORM --}}
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">Cabang</label>
                            <input type="text" class="w-full border rounded px-3 py-2 bg-gray-200 cursor-not-allowed"
                                value="{{ $fcabang }}" disabled>
                            <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                        </div>
                        <div class="lg:col-span-4" x-data="{ autoCode: true }">
                            <label class="block text-sm font-medium mb-1">Transaksi#</label>
                            <div class="flex items-center gap-3">
                                <input type="text" name="fstockmtno" class="w-full border rounded px-3 py-2"
                                    :disabled="autoCode"
                                    :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'">
                                <label class="inline-flex items-center select-none">
                                    <input type="checkbox" x-model="autoCode" checked>
                                    <span class="ml-2 text-sm text-gray-700">Auto</span>
                                </label>
                            </div>
                        </div>

                        <input type="hidden" name="fstockmtid" value="fstockmtid">

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">Tanggal</label>
                            <input disabled type="date" name="fstockmtdate"
                                value="{{ old('fstockmtdate') ?? date('Y-m-d') }}"
                                class="w-full border rounded px-3 py-2 bg-gray-100 @error('fstockmtdate') border-red-500 @enderror">
                            @error('fstockmtdate')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Field FROM -->
                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium mb-1">Gudang</label>
                            <div class="flex">
                                <div class="relative flex-1">

                                    <select id="warehouseSelectFrom"
                                        class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
                                        disabled>
                                        <option value=""></option>
                                        @foreach ($warehouses as $wh)
                                            <option value="{{ $wh->fwhid }}" data-id="{{ $wh->fwhid }}"
                                                data-branch="{{ $wh->fbranchcode }}"
                                                {{ old('ffrom', $assembling->ffrom) == $wh->fwhid ? 'selected' : '' }}>
                                                {{ $wh->fwhcode }} - {{ $wh->fwhname }}
                                            </option>
                                        @endforeach
                                    </select>

                                    {{-- Overlay untuk buka browser gudang --}}
                                    <div class="absolute inset-0" role="button" aria-label="Browse warehouse"
                                        @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open'))"></div>
                                </div>
                                <input type="hidden" name="ffrom" id="warehouseCodeHiddenFrom"
                                    value="{{ old('ffrom', $assembling->ffrom) }}">

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
                            <label class="block text-sm font-medium">Keterangan</label>
                            <textarea readonly name="fket" rows="3"
                                class="w-full border rounded px-3 py-2 bg-gray-100 @error('fket') border-red-500 @enderror"
                                placeholder="Tulis keterangan tambahan di sini...">{{ old('fket', $assembling->fket) }}</textarea>
                            @error('fket')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div x-data="itemsTable()" x-init="init()" class="mt-6 space-y-2">
                        {{-- TAB NAVIGATION --}}
                        <div class="border-b border-gray-200">
                            <nav class="-mb-px flex space-x-4" aria-label="Tabs">
                                <button type="button" @click="activeTab = 'bahan_baku'"
                                    :class="activeTab === 'bahan_baku'
                                        ?
                                        'border-emerald-500 text-emerald-600' :
                                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm transition-colors">
                                    Bahan Baku
                                    <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-gray-100"
                                        x-text="getItemsByTab('bahan_baku').length"></span>
                                </button>
                                <button type="button" @click="activeTab = 'barang_jadi'"
                                    :class="activeTab === 'barang_jadi'
                                        ?
                                        'border-emerald-500 text-emerald-600' :
                                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm transition-colors">
                                    Barang Jadi
                                    <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-gray-100"
                                        x-text="getItemsByTab('barang_jadi').length"></span>
                                </button>
                            </nav>
                        </div>

                        {{-- DETAIL ITEM (tabel input) --}}
                        <h3 class="text-base font-semibold text-gray-800">
                            Detail Item - <span x-text="activeTab === 'bahan_baku' ? 'Bahan Baku' : 'Barang Jadi'"></span>
                        </h3>

                        <div class="overflow-auto border rounded">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="p-2 text-left w-10">#</th>
                                        <th class="p-2 text-left w-40">Kode Produk</th>
                                        <th class="p-2 text-left w-102">Nama Produk</th>
                                        <th class="p-2 text-left w-24">Sat</th>
                                        <th class="p-2 text-right w-36">Qty</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <template x-for="(it, i) in getItemsByTab(activeTab)" :key="it.uid">
                                        <!-- ROW UTAMA -->
                                        <tr class="border-t align-top">
                                            <td class="p-2" x-text="i + 1"></td>
                                            <td class="p-2 font-mono" x-text="it.fitemcode"></td>
                                            <td class="p-2 text-gray-800">
                                                <div x-text="it.fitemname"></div>
                                                <div x-show="it.fdesc" class="mt-1 text-xs">
                                                    <span
                                                        :class="activeTab === 'bahan_baku' ?
                                                            'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                                            'bg-blue-50 text-blue-700 border-blue-200'"
                                                        class="inline-block px-1.5 py-0.5 rounded border mr-2">
                                                        Deskripsi
                                                    </span>
                                                    <span class="align-middle text-gray-600" x-text="it.fdesc"></span>
                                                </div>
                                            </td>
                                            <td class="p-2 text-left" x-text="it.fsatuan"></td>
                                            <td class="p-2 text-right" x-text="fmt(it.fqty)"></td>

                                            <!-- hidden inputs -->
                                            <td class="hidden">
                                                <input type="hidden" name="fitemcode[]" :value="it.fitemcode">
                                                <input type="hidden" name="fitemname[]" :value="it.fitemname">
                                                <input type="hidden" name="fsatuan[]" :value="it.fsatuan">
                                                <input type="hidden" name="frefdtno[]" :value="it.faccid">
                                                <input type="hidden" name="frefso[]" :value="it.fsubaccountid">
                                                <input type="hidden" name="frefpr[]" :value="it.frefpr">
                                                <input type="hidden" name="fqty[]" :value="it.fqty">
                                                <input type="hidden" name="fdesc[]" :value="it.fdesc">
                                                <input type="hidden" name="fketdt[]" :value="it.fketdt">
                                                <input type="hidden" name="fitemtype[]" :value="it.fitemtype">
                                            </td>
                                        </tr>

                                        <tr class="border-b">
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                        </tr>
                                    </template>

                                    <!-- ROW EDIT DESC -->
                                    <tr x-show="editingIndex !== null && editingTab === activeTab"
                                        class="bg-amber-50 border-b" x-cloak>
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

                    <script>
                        // PASTE INI DI FILE JS ANDA, GANTI FUNCTION itemsTable() YANG LAMA
                        function itemsTable() {
                            return {
                                // === TAB STATE ===
                                activeTab: 'bahan_baku',
                                editingTab: null,

                                // === ORIGINAL PROPERTIES ===
                                showNoItems: false,
                                savedItems: @json($savedItems),
                                draft: newRow(),
                                editingIndex: null,
                                editRow: newRow(),
                                totalHarga: 0,

                                // === NEW METHOD: FILTER BY TAB ===
                                getItemsByTab(tab) {
                                    return this.savedItems.filter(item => item.fitemtype === tab);
                                },

                                // === ORIGINAL METHODS (tidak berubah) ===
                                updateAccount(row, faccid, accName) {
                                    row.faccid = faccid;
                                    row.faccname = accName;
                                },

                                updateSubAccount(row, fsubaccountid, SubAccName) {
                                    row.fsubaccountid = fsubaccountid;
                                    row.fsubaccountname = SubAccName;
                                },

                                fmt(n) {
                                    if (n === null || n === undefined || n === '') return '-';
                                    const v = Number(n);
                                    if (!isFinite(v)) return '-';
                                    if (Number.isInteger(v)) {
                                        return v.toLocaleString('id-ID');
                                    } else {
                                        return v.toLocaleString('id-ID', {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        });
                                    }
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

                                // === MODIFIED: removeSaved ===
                                removeSaved(i) {
                                    const items = this.getItemsByTab(this.activeTab);
                                    const actualIndex = this.savedItems.indexOf(items[i]);
                                    this.savedItems.splice(actualIndex, 1);
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
                                            fitemtype: this.activeTab, // SET TYPE
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

                                // === MODIFIED: addIfComplete ===
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
                                        (it.fdesc || '') === (r.fdesc || '') &&
                                        (it.frefpr || '') === (r.frefpr || '') &&
                                        it.fitemtype === this.activeTab // CHECK TYPE
                                    );

                                    if (dupe) {
                                        alert('Item sama sudah ada.');
                                        return;
                                    }

                                    this.savedItems.push({
                                        ...r,
                                        uid: cryptoRandom(),
                                        fitemtype: this.activeTab // SET TYPE
                                    });

                                    this.showNoItems = false;
                                    this.resetDraft();
                                    this.$nextTick(() => this.$refs.draftCode?.focus());
                                    this.syncDescList?.();
                                    this.recalcTotals();
                                },

                                // === MODIFIED: edit ===
                                edit(i) {
                                    const items = this.getItemsByTab(this.activeTab);
                                    const actualIndex = this.savedItems.indexOf(items[i]);

                                    this.editingIndex = i;
                                    this.editingTab = this.activeTab;
                                    this.editRow = {
                                        ...this.savedItems[actualIndex]
                                    };
                                    this.hydrateRowFromMeta(this.editRow, this.productMeta(this.editRow.fitemcode));
                                    this.$nextTick(() => this.$refs.editQty?.focus());
                                },

                                // === MODIFIED: applyEdit ===
                                applyEdit() {
                                    const r = this.editRow;
                                    if (!this.isComplete(r)) {
                                        alert('Lengkapi data item.');
                                        return;
                                    }

                                    this.recalc(r);

                                    const items = this.getItemsByTab(this.editingTab);
                                    const actualIndex = this.savedItems.indexOf(items[this.editingIndex]);

                                    this.savedItems.splice(actualIndex, 1, {
                                        ...r
                                    });
                                    this.cancelEdit();
                                    this.syncDescList?.();
                                    this.recalcTotals();
                                },

                                // === MODIFIED: cancelEdit ===
                                cancelEdit() {
                                    this.editingIndex = null;
                                    this.editingTab = null;
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

                            // === HELPER FUNCTIONS ===
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
                                    fitemtype: '', // ADDED
                                };
                            }

                            function cryptoRandom() {
                                return (window.crypto?.getRandomValues ? [...window.crypto.getRandomValues(new Uint32Array(2))].map(n => n
                                        .toString(16)).join('') :
                                    Math.random().toString(36).slice(2)) + Date.now();
                            }
                        }
                    </script>

                    {{-- MODAL ERROR: belum ada item --}}
                    <div x-show="showNoItems && savedItems.length === 0" x-cloak
                        class="fixed inset-0 z-[90] flex items-center justify-center" x-transition.opacity>
                        <div class="absolute inset-0 bg-black/50" @click="showNoItems=false"></div>

                        <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden"
                            x-transition.scale>
                            <div class="px-5 py-4 border-b flex items-center">
                                <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-500 mr-2" />
                                <h3 class="text-lg font-semibold text-gray-800">Tidak Ada Item</h3>
                            </div>

                            <div class="px-5 py-4">
                                <p class="text-sm text-gray-700">
                                    Anda belum menambahkan item apa pun pada tabel. Silakan isi baris “Detail Item”
                                    terlebih
                                    dahulu.
                                </p>
                            </div>

                            <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
                                <button type="button" @click="showNoItems=false"
                                    class="h-9 px-4 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                                    OK
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- MODAL GUDANG --}}
                    <div x-data="warehouseBrowser()" x-show="open" x-cloak x-transition.opacity
                        class="fixed inset-0 z-50 flex items-center justify-center p-4">
                        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

                        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-6xl flex flex-col overflow-hidden"
                            style="height: 650px;">
                            <!-- Header -->
                            <div
                                class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800">Browse Gudang</h3>
                                    <p class="text-sm text-gray-500 mt-0.5">Pilih gudang yang diinginkan</p>
                                </div>
                                <button type="button" @click="close()"
                                    class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                                    Tutup
                                </button>
                            </div>

                            <!-- Search & Length Menu -->
                            <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                                <div id="warehouseTableControls"></div>
                            </div>

                            <!-- Table with fixed height and scroll -->
                            <div class="flex-1 overflow-y-auto px-6" style="min-height: 0;">
                                <div class="bg-white">
                                    <table id="warehouseTable" class="min-w-full text-sm display nowrap stripe hover"
                                        style="width:100%">
                                        <thead class="sticky top-0 z-10">
                                            <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                                <th
                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                    Gudang (Kode - Nama)</th>
                                                <th
                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                    Branch</th>
                                                <th
                                                    class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                    Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Data will be populated by DataTables -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Pagination & Info -->
                            <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
                                <div id="warehouseTablePagination"></div>
                            </div>
                        </div>
                    </div>

                    {{-- MODAL PRODUK --}}
                    <div x-data="productBrowser()" x-show="open" x-cloak x-transition.opacity
                        class="fixed inset-0 z-50 flex items-center justify-center p-4">
                        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

                        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-6xl flex flex-col overflow-hidden"
                            style="height: 650px;">
                            <!-- Header -->
                            <div
                                class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800">Browse Produk</h3>
                                    <p class="text-sm text-gray-500 mt-0.5">Pilih produk yang diinginkan</p>
                                </div>
                                <button type="button" @click="close()"
                                    class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                                    Tutup
                                </button>
                            </div>

                            <!-- Search & Length Menu -->
                            <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                                <div id="productTableControls"></div>
                            </div>

                            <!-- Table with fixed height and scroll -->
                            <div class="flex-1 overflow-y-auto px-6" style="min-height: 0;">
                                <div class="bg-white">
                                    <table id="productTable" class="min-w-full text-sm display nowrap stripe hover"
                                        style="width:100%">
                                        <thead class="sticky top-0 z-10">
                                            <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                                <th
                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                    Kode</th>
                                                <th
                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                    Nama Produk</th>
                                                <th
                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                    Satuan</th>
                                                <th
                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                    Merek</th>
                                                <th
                                                    class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                    Stock</th>
                                                <th
                                                    class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                    Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Data will be populated by DataTables -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Pagination & Info -->
                            <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
                                <div id="productTablePagination"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex justify-center space-x-4">
                    <button type="button" onclick="window.location.href='{{ route('assembling.index') }}'"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                        Kembali
                    </button>
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
        });

        // function itemsTable() {
        //     return {
        //         showNoItems: false,
        //         savedItems: @json($savedItems),
        //         draft: newRow(),
        //         editingIndex: null,
        //         editRow: newRow(),
        //         totalHarga: 0,

        //         updateAccount(row, faccid, accName) {
        //             row.faccid = faccid;
        //             row.faccname = accName;

        //             // Opsional: Cek apakah item lain di draft/edit perlu di-recalc
        //             // this.recalc(row); 
        //         },

        //         updateSubAccount(row, fsubaccountid, SubAccName) {
        //             row.fsubaccountid = fsubaccountid;
        //             row.fsubaccountname = SubAccName;
        //         },

        //         fmt(n) {
        //             if (n === null || n === undefined || n === '') return '-';
        //             const v = Number(n);
        //             if (!isFinite(v)) return '-';

        //             // Jika angka adalah bulat, hilangkan desimal
        //             if (Number.isInteger(v)) {
        //                 return v.toLocaleString('id-ID');
        //             } else {
        //                 return v.toLocaleString('id-ID', {
        //                     minimumFractionDigits: 2,
        //                     maximumFractionDigits: 2
        //                 });
        //             }
        //         },

        //         rupiah(n) {
        //             const v = Number(n || 0);
        //             if (!isFinite(v)) return 'Rp -';
        //             return 'Rp ' + v.toLocaleString('id-ID', {
        //                 minimumFractionDigits: 2,
        //                 maximumFractionDigits: 2
        //             });
        //         },

        //         fmtMoney(value) {
        //             return this.fmt(value);
        //         },

        //         recalc(row) {
        //             this.$nextTick(() => {
        //                 row.fqty = Math.max(0, Number(row.fqty) || 0);
        //                 row.fterima = Math.max(0, Number(row.fterima) || 0);
        //                 row.fprice = Math.max(0, Number(row.fprice) || 0);

        //                 row.ftotal = Number((row.fqty * row.fprice).toFixed(2));

        //                 this.recalcTotals();
        //             });
        //         },

        //         recalcTotals() {
        //             this.totalHarga = (this.savedItems || []).reduce((sum, it) => {
        //                 const v = Number(it?.ftotal ?? 0);
        //                 return sum + (Number.isFinite(v) ? v : 0);
        //             }, 0);
        //         },

        //         removeSaved(i) {
        //             this.savedItems.splice(i, 1);
        //             this.syncDescList?.();
        //             this.recalcTotals();
        //         },

        //         productMeta(code) {
        //             const key = (code || '').trim();
        //             return window.PRODUCT_MAP?.[key] || null;
        //         },

        //         hydrateRowFromMeta(row, meta) {
        //             if (!meta) {
        //                 row.fitemname = '';
        //                 row.units = [];
        //                 row.fsatuan = '';
        //                 row.maxqty = 0;
        //                 return;
        //             }
        //             row.fitemname = meta.name || '';
        //             const units = [...new Set((meta.units || []).map(u => (u ?? '').toString().trim()).filter(Boolean))];
        //             row.units = units;
        //             if (!units.includes(row.fsatuan)) row.fsatuan = units[0] || '';
        //             const stock = Number.isFinite(+meta.stock) && +meta.stock > 0 ? +meta.stock : 0;
        //             row.maxqty = stock;
        //         },

        //         onCodeTypedRow(row) {
        //             this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode));
        //         },

        //         isComplete(row) {
        //             return row.fitemcode && row.fitemname && row.fsatuan && Number(row.fqty) > 0;
        //         },

        //         onPrPicked(e) {
        //             const {
        //                 header,
        //                 items
        //             } = e.detail || {};
        //             if (!items || !Array.isArray(items)) return;

        //             this.resetDraft();
        //             this.addManyFromPR(header, items);
        //         },

        //         resetDraft() {
        //             this.draft = newRow();
        //             this.$nextTick(() => this.$refs.draftCode?.focus());
        //         },

        //         addManyFromPR(header, items) {
        //             const existing = new Set(this.getCurrentItemKeys());

        //             let added = 0,
        //                 duplicates = [];

        //             items.forEach(src => {
        //                 const row = {
        //                     uid: cryptoRandom(),
        //                     fitemcode: src.fitemcode ?? '',
        //                     fitemname: src.fitemname ?? '',
        //                     fsatuan: src.fsatuan ?? '',
        //                     frefpr: src.frefpr ?? (header?.fpono ?? ''),
        //                     fqty: Number(src.fqty ?? 0),
        //                     fdesc: src.fdesc ?? '',
        //                     fketdt: src.fketdt ?? '',
        //                     units: Array.isArray(src.units) && src.units.length ? src.units : [src.fsatuan]
        //                         .filter(Boolean),
        //                 };

        //                 const key = this.itemKey({
        //                     fitemcode: row.fitemcode,
        //                     frefdtno: row.frefdtno
        //                 });

        //                 if (existing.has(key)) {
        //                     duplicates.push({
        //                         key,
        //                         code: row.fitemcode,
        //                         ref: row.frefdtno
        //                     });
        //                     return;
        //                 }

        //                 this.savedItems.push(row);
        //                 existing.add(key);
        //                 added++;
        //             });

        //             this.recalcTotals();
        //         },

        //         addIfComplete() {
        //             const r = this.draft;
        //             if (!this.isComplete(r)) {
        //                 if (!r.fitemcode) return this.$refs.draftCode?.focus();
        //                 if (!r.fitemname) return this.$refs.draftCode?.focus();
        //                 if (!r.fsatuan) return (r.units.length > 1 ? this.$refs.draftUnit?.focus() : this.$refs.draftCode
        //                     ?.focus());
        //                 if (!(Number(r.fqty) > 0)) return this.$refs.draftQty?.focus();
        //                 return;
        //             }

        //             this.recalc(r);

        //             const dupe = this.savedItems.find(it =>
        //                 it.fitemcode === r.fitemcode &&
        //                 it.fsatuan === r.fsatuan && (it.fdesc || '') === (r.fdesc || '') &&
        //                 (it.frefpr || '') === (r.frefpr || '')
        //             );

        //             if (dupe) {
        //                 alert('Item sama sudah ada.');
        //                 return;
        //             }

        //             this.savedItems.push({
        //                 ...r,
        //                 uid: cryptoRandom()
        //             });

        //             this.showNoItems = false;
        //             this.resetDraft();
        //             this.$nextTick(() => this.$refs.draftCode?.focus());
        //             this.syncDescList?.();
        //             this.recalcTotals();
        //         },

        //         edit(i) {
        //             this.editingIndex = i;
        //             this.editRow = {
        //                 ...this.savedItems[i]
        //             };
        //             this.hydrateRowFromMeta(this.editRow, this.productMeta(this.editRow.fitemcode));
        //             this.$nextTick(() => this.$refs.editQty?.focus());
        //         },

        //         applyEdit() {
        //             const r = this.editRow;
        //             if (!this.isComplete(r)) {
        //                 alert('Lengkapi data item.');
        //                 return;
        //             }

        //             this.recalc(r);
        //             this.savedItems.splice(this.editingIndex, 1, {
        //                 ...r
        //             });
        //             this.cancelEdit();
        //             this.syncDescList?.();
        //             this.recalcTotals();
        //         },

        //         cancelEdit() {
        //             this.editingIndex = null;
        //             this.editRow = newRow();
        //         },

        //         onSubmit($event) {
        //             if (this.savedItems.length === 0) {
        //                 $event.preventDefault();
        //                 this.showNoItems = true;
        //                 return;
        //             }
        //         },

        //         handleEnterOnCode(where) {
        //             if (where === 'edit') {
        //                 if (this.editRow.units.length > 1) this.$refs.editUnit?.focus();
        //                 else this.$refs.editQty?.focus();
        //             } else {
        //                 if (this.draft.units.length > 1) this.$refs.draftUnit?.focus();
        //                 else this.$refs.draftQty?.focus();
        //             }
        //         },

        //         handleEnterOnPrice(where) {
        //             if (where === 'edit') {
        //                 this.applyEdit();
        //             } else {
        //                 this.addIfComplete();
        //             }
        //         },

        //         showDescModal: false,
        //         descTarget: 'draft',
        //         descSavedIndex: null,
        //         descValue: '',
        //         openDesc() {},
        //         closeDesc() {},
        //         applyDesc() {},

        //         itemKey(it) {
        //             return `${(it.fitemcode ?? '').toString().trim()}::${(it.frefdtno ?? '').toString().trim()}`;
        //         },

        //         getCurrentItemKeys() {
        //             return this.savedItems.map(it => this.itemKey(it));
        //         },

        //         init() {
        //             window.getCurrentItemKeys = () => this.getCurrentItemKeys();

        //             window.addEventListener('pr-picked', this.onPrPicked.bind(this), {
        //                 passive: true
        //             });

        //             window.addEventListener('product-chosen', (e) => {
        //                 const {
        //                     product
        //                 } = e.detail || {};
        //                 if (!product) return;

        //                 const apply = (row) => {
        //                     row.fitemcode = (product.fprdcode || '').toString();
        //                     this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode));
        //                     if (!row.fqty) row.fqty = 1;
        //                     this.recalc(row);
        //                 };

        //                 if (this.browseTarget === 'edit') {
        //                     apply(this.editRow);
        //                     this.$nextTick(() => this.$refs.editQty?.focus());
        //                 } else {
        //                     apply(this.draft);
        //                     this.$nextTick(() => this.$refs.draftQty?.focus());
        //                 }
        //             }, {
        //                 passive: true
        //             });
        //         },

        //         browseTarget: 'draft',
        //         openBrowseFor(where) {
        //             this.browseTarget = (where === 'edit' ? 'edit' : 'draft');
        //             window.dispatchEvent(new CustomEvent('browse-open', {
        //                 detail: {
        //                     forEdit: this.browseTarget === 'edit'
        //                 }
        //             }));
        //         },
        //     };

        //     function newRow() {
        //         return {
        //             uid: null,
        //             fitemcode: '',
        //             fitemname: '',
        //             units: [],
        //             fsatuan: '',
        //             frefpr: '',
        //             fqty: 0,
        //             fdesc: '',
        //             fketdt: '',
        //             maxqty: 0,
        //             faccid: null,
        //             faccname: '',
        //             fsubaccountid: null,
        //             fsubaccountname: '',
        //         };
        //     }

        //     function cryptoRandom() {
        //         return (window.crypto?.getRandomValues ? [...window.crypto.getRandomValues(new Uint32Array(2))].map(n => n
        //                 .toString(16)).join('') :
        //             Math.random().toString(36).slice(2)) + Date.now();
        //     }
        // }

        // Warehouse Browser dengan DataTables
        window.warehouseBrowser = function() {
            return {
                open: false,
                table: null,

                initDataTable() {
                    if (this.table) {
                        this.table.destroy();
                    }
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
                                data: null,
                                name: 'fwhcode',
                                className: 'text-sm',
                                render: function(data, type, row) {
                                    return `<span class="font-mono font-semibold">${row.fwhcode}</span> - ${row.fwhname}`;
                                }
                            },
                            {
                                data: 'fbranchcode',
                                name: 'fbranchcode',
                                className: 'text-sm',
                                render: function(data) {
                                    return data || '<span class="text-gray-400">-</span>';
                                }
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
                            [0, 'asc']
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
                    $('#warehouseTable').on('click', '.btn-choose', (e) => {
                        const data = this.table.row($(e.target).closest('tr')).data();
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
                    sel.value = fwhid || '';
                    sel.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                }
                if (hid) hid.value = fwhid || '';
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
