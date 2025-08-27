@extends('layouts.app')

@section('title', 'Permintaan Pembelian')

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

    <div x-data="{ open: true, selected: 'surat' }">
        <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1200px] mx-auto">
            <h2 class="text-2xl font-semibold text-gray-800 flex items-center space-x-2">
                <x-heroicon-o-scale class="w-8 h-8 text-blue-600" />
                <span>Permintaan Pembelian Baru</span>
            </h2>
            <form action="{{ route('tr_prh.store') }}" method="POST">
                @csrf

                <div class="space-y-4 mt-4">
                    <div class="mt-2 w-1/3">
                        <label class="block text-sm font-medium">Cabang User</label>
                        <input type="text" name="fcabang"
                            class="w-full border rounded px-3 py-2 bg-gray-200 cursor-not-allowed"
                            value="{{ $fcabang }}" disabled>
                        <input type="hidden" name="fbranchcode" value="{{ $fcabang }}">
                    </div>

                    <div x-data="{ autoCode: true }" class="flex items-center gap-4">
                        <!-- Input Kode Product -->
                        <div class="mt-2 w-1/3">
                            <label class="block text-sm font-medium">PR#</label>
                            <input type="text" name="fprno" class="w-full border rounded px-3 py-2"
                                placeholder="Masukkan nilai PR#" :disabled="autoCode"
                                :value="autoCode ? '' : '{{ old('fprno') }}'"
                                :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'">
                        </div>

                        <!-- Checkbox Auto Generate -->
                        <label class="inline-flex items-center mt-6">
                            <input type="checkbox" x-model="autoCode" class="form-checkbox text-indigo-600" checked>
                            <span class="ml-2 text-sm text-gray-700">Auto</span>
                        </label>
                    </div>

                    <div class="mt-4 w-1/4">
                        <label class="block text-sm font-medium">Supplier</label>
                        <select name="fsupplier"
                            class="w-full border rounded px-3 py-2 @error('fsupplier') border-red-500 @enderror"
                            id="groupSelect">
                            <option value="">-- Pilih Supplier --</option>
                            @foreach ($supplier as $suppliers)
                                <option value="{{ $suppliers->fsupplierid }}"
                                    {{ old('fsupplier') == $suppliers->fsupplierid ? 'selected' : '' }}>
                                    {{ $suppliers->fsuppliername }}
                                </option>
                            @endforeach
                        </select>
                        @error('fsupplier')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-4 w-1/3">
                        <label class="block text-sm font-medium">Tanggal</label>
                        <input type="date" name="fprdate" value="{{ old('fprdate') ?? date('Y-m-d') }}"
                            class="w-full border rounded px-3 py-2 @error('fprdate') border-red-500 @enderror">
                        @error('fprdate')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-4 w-1/3">
                        <label class="block text-sm font-medium">Tanggal Dibutuhkan</label>
                        <input type="date" name="fneeddate" value="{{ old('fneeddate') ?? date('Y-m-d') }}"
                            class="w-full border rounded px-3 py-2 @error('fneeddate') border-red-500 @enderror">
                        @error('fneeddate')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-4 w-1/3">
                        <label class="block text-sm font-medium">Tanggal Paling Lambat</label>
                        <input type="date" name="fduedate" value="{{ old('fduedate') ?? date('Y-m-d') }}"
                            class="w-full border rounded px-3 py-2 @error('fduedate') border-red-500 @enderror">
                        @error('fduedate')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium">Keterangan</label>
                        <textarea name="fket" rows="4" class="w-full border rounded px-3 py-2 @error('fket') border-red-500 @enderror"
                            placeholder="Tulis keterangan tambahan di sini...">{{ old('fket') }}</textarea>
                        @error('fket')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <br?>

                        <div x-data="detailProduk()" class="mt-6 border rounded">
                            <!-- HEADER -->
                            <div
                                class="grid grid-cols-12 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200 text-[13px] font-semibold text-gray-700">
                                <div class="col-span-1 p-2 text-center">No.</div>
                                <div class="col-span-2 p-2">Kode Produk</div>
                                <div class="col-span-3 p-2">Nama Produk</div>
                                <div class="col-span-1 p-2">Desc.</div>
                                <div class="col-span-1 p-2">Sat.</div>
                                <div class="col-span-1 p-2 text-right">Qty.</div>
                                <div class="col-span-2 p-2">Keterangan</div>
                                <div class="col-span-1 p-2 text-center">Action</div>
                            </div>

                            <!-- BODY -->
                            <div class="max-h-64 overflow-y-auto">
                                <template x-for="(row, idx) in items" :key="row.uid">
                                    <div class="grid grid-cols-12 border-b text-sm">
                                        <!-- No. -->
                                        <div class="col-span-1 p-2 text-center" x-text="idx + 1"></div>

                                        <!-- KODE PRODUK (Select2: value=kode, text=kode) -->
                                        <div class="col-span-2 p-1">
                                            <select class="w-full border rounded px-2 py-1 product-code-select"
                                                :id="`code-${row.uid}`" x-model="row.selectedCode"
                                                @change="onProductChange(row, $event.target.value)">
                                                <option value="">-- pilih kode --</option>
                                                @foreach ($products as $p)
                                                    <option value="{{ $p->fproductcode }}">{{ $p->fproductcode }}</option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="fitemcode[]" :value="row.fitemcode">
                                        </div>

                                        <!-- NAMA PRODUK (Select2: value=kode, text=nama) -->
                                        <div class="col-span-3 p-1">
                                            <select class="w-full border rounded px-2 py-1 product-name-select"
                                                :id="`name-${row.uid}`" x-model="row.selectedCode"
                                                @change="onProductChange(row, $event.target.value)">
                                                <option value="">-- pilih produk --</option>
                                                @foreach ($products as $p)
                                                    <option value="{{ $p->fproductcode }}">{{ $p->fproductname }}</option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="fitemname[]" :value="row.fitemname">
                                        </div>

                                        <!-- DESC. -->
                                        <div class="col-span-1 p-1">
                                            <input type="text" class="w-full border rounded px-2 py-1"
                                                x-model="row.fdesc" name="fdesc[]" placeholder="(Memo)"
                                                @input="checkAutoAdd(idx)">
                                        </div>

                                        <!-- SAT. -->
                                        <div class="col-span-1 p-1">
                                            <select name="fsatuan[]" class="w-full border rounded px-2 py-1"
                                                x-model="row.fsatuan">
                                                <option value="" x-show="row.units.length === 0">-</option>
                                                <template x-for="u in row.units" :key="u">
                                                    <option :value="u" x-text="u"></option>
                                                </template>
                                            </select>
                                        </div>

                                        <!-- Qty -->
                                        <div class="col-span-1 p-1">
                                            <input type="number" :min="1"
                                                :max="row.maxqty > 0 ? row.maxqty : null" step="1"
                                                class="w-full border rounded px-2 py-1 text-right"
                                                x-model.number="row.fqty" name="fqty[]" placeholder="0"
                                                @input="enforceQty(row); checkAutoAdd(idx)">
                                            <p class="text-[11px] text-gray-500 mt-1" x-show="row.maxqty > 0">
                                                Maks: <span x-text="row.maxqty"></span>
                                            </p>
                                        </div>

                                        <!-- KETERANGAN -->
                                        <div class="col-span-2 p-1">
                                            <input type="text" class="w-full border rounded px-2 py-1"
                                                x-model="row.fketdt" name="fketdt[]" placeholder="Keterangan"
                                                @input="checkAutoAdd(idx)">
                                        </div>

                                        <!-- ACTION -->
                                        <div class="col-span-1 p-1 flex flex-col items-center justify-center space-y-2">
                                            <button type="button" @click="openBrowse(idx)"
                                                class="w-full px-2 py-1 rounded text-xs flex items-center justify-center bg-emerald-100 hover:bg-emerald-200 text-emerald-700">
                                                <x-heroicon-o-magnifying-glass class="w-4 h-4 mr-1" />
                                                Browse
                                            </button>
                                            
                                            <button type="button" @click="delAt(idx)" :disabled="idx === 0"
                                                :class="idx === 0 ?
                                                    'w-full px-2 py-1 rounded text-xs flex items-center justify-center bg-gray-100 text-gray-400 cursor-not-allowed' :
                                                    'w-full px-2 py-1 rounded text-xs flex items-center justify-center bg-red-100 hover:bg-red-200 text-red-600'">
                                                <x-heroicon-o-trash class="w-4 h-4 mr-1" />
                                                Delete
                                            </button>

                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- MODAL BROWSE PRODUCT -->
                            <div x-data="productBrowser()" x-show="open" x-cloak x-transition.opacity
                                class="fixed inset-0 z-50 flex items-center justify-center">
                                <!-- backdrop -->
                                <div class="absolute inset-0 bg-black/40" @click="close()"></div>

                                <!-- card -->
                                <div
                                    class="relative bg-white rounded-2xl shadow-xl w-[90vw] max-w-5xl max-h-[85vh] flex flex-col">
                                    <!-- header -->
                                    <div class="p-4 border-b flex items-center gap-3">
                                        <h3 class="text-lg font-semibold">Browse Product</h3>
                                        <div class="ml-auto flex items-center gap-2">
                                            <input type="text" x-model="keyword" @keydown.enter.prevent="search()"
                                                placeholder="Cari kode / nama…" class="border rounded px-3 py-2 w-64">
                                            <button type="button" @click="search()"
                                                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Search</button>
                                        </div>
                                    </div>

                                    <!-- body -->
                                    <div class="p-0 overflow-auto">
                                        <table class="min-w-full text-sm">
                                            <thead class="bg-gray-100 sticky top-0">
                                                <tr>
                                                    <th class="text-left p-2 w-40">Kode</th>
                                                    <th class="text-left p-2">Nama</th>
                                                    <th class="text-left p-2 w-48">Satuan</th>
                                                    <th class="text-center p-2 w-28">Stock</th>
                                                    <th class="text-center p-2 w-28">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-for="p in rows" :key="p.fproductcode">
                                                    <tr class="border-b hover:bg-gray-50">
                                                        <td class="p-2 font-mono" x-text="p.fproductcode"></td>
                                                        <td class="p-2" x-text="p.fproductname"></td>
                                                        <td class="p-2">
                                                            <span
                                                                x-text="[p.fsatuankecil, p.fsatuanbesar, p.fsatuanbesar2].filter(Boolean).join(' / ')"></span>
                                                        </td>
                                                        <td class="p-2 text-center" x-text="p.fminstock"></td>
                                                        <td class="p-2 text-center">
                                                            <button type="button" @click="choose(p)"
                                                                class="px-3 py-1 rounded text-xs bg-emerald-600 hover:bg-emerald-700 text-white">
                                                                Pilih
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </template>

                                                <tr x-show="rows.length === 0">
                                                    <td colspan="5" class="p-4 text-center text-gray-500">Tidak ada
                                                        data.</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- footer -->
                                    <div class="p-3 border-t flex items-center gap-2">
                                        <div class="text-sm text-gray-600">
                                            <span x-text="`Page ${page} / ${lastPage} • Total ${total}`"></span>
                                        </div>
                                        <div class="ml-auto flex items-center gap-2">
                                            <button type="button" @click="prev()" :disabled="page <= 1"
                                                :class="page <= 1 ? 'bg-gray-200 text-gray-400 cursor-not-allowed' :
                                                    'bg-gray-100 hover:bg-gray-200'"
                                                class="px-3 py-1 rounded border">Prev</button>
                                            <button type="button" @click="next()" :disabled="page >= lastPage"
                                                :class="page >= lastPage ? 'bg-gray-200 text-gray-400 cursor-not-allowed' :
                                                    'bg-gray-100 hover:bg-gray-200'"
                                                class="px-3 py-1 rounded border">Next</button>
                                            <button type="button" @click="close()"
                                                class="px-3 py-1 rounded border bg-gray-100 hover:bg-gray-200">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <script>
                            function productBrowser() {
                                return {
                                    open: false,
                                    targetIndex: null,
                                    keyword: '',
                                    page: 1,
                                    lastPage: 1,
                                    perPage: 10,
                                    total: 0,
                                    rows: [],

                                    apiUrl() {
                                        const u = new URL("{{ route('products.browse') }}", window.location.origin);
                                        u.searchParams.set('q', this.keyword || '');
                                        u.searchParams.set('per_page', this.perPage);
                                        u.searchParams.set('page', this.page);
                                        return u.toString();
                                    },

                                    async fetch() {
                                        try {
                                            const res = await fetch(this.apiUrl(), {
                                                headers: {
                                                    'Accept': 'application/json'
                                                }
                                            });
                                            const j = await res.json();
                                            this.rows = j.data || [];
                                            this.page = j.current_page || 1;
                                            this.lastPage = j.last_page || 1;
                                            this.total = j.total || 0;
                                        } catch (e) {
                                            console.error(e);
                                            this.rows = [];
                                            this.page = 1;
                                            this.lastPage = 1;
                                            this.total = 0;
                                        }
                                    },

                                    openFor(index) {
                                        this.targetIndex = index;
                                        this.open = true;
                                        this.page = 1;
                                        this.fetch();
                                    },

                                    close() {
                                        this.open = false;
                                        this.targetIndex = null;
                                        this.keyword = '';
                                        this.rows = [];
                                    },

                                    search() {
                                        this.page = 1;
                                        this.fetch();
                                    },

                                    prev() {
                                        if (this.page > 1) {
                                            this.page--;
                                            this.fetch();
                                        }
                                    },

                                    next() {
                                        if (this.page < this.lastPage) {
                                            this.page++;
                                            this.fetch();
                                        }
                                    },

                                    choose(p) {
                                        window.dispatchEvent(new CustomEvent('product-chosen', {
                                            detail: {
                                                index: this.targetIndex,
                                                product: p
                                            }
                                        }));
                                        this.close();
                                    },

                                    init() {
                                        window.addEventListener('browse-open', (e) => {
                                            const i = e.detail && e.detail.index;
                                            if (Number.isInteger(i)) this.openFor(i);
                                        }, {
                                            passive: true
                                        });
                                    }
                                }
                            }
                        </script>

                        <script>
                            window.PRODUCT_MAP = {
                                @foreach ($products as $p)
                                    "{{ $p->fproductcode }}": {
                                        name: @json($p->fproductname),
                                        units: @json(array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2]))),
                                        stock: @json($p->fminstock ?? 0)
                                    },
                                @endforeach
                            };
                        </script>

                        <!-- Helper UID -->
                        <script>
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
                        </script>

                        <script>
                            function detailProduk() {
                                return {
                                    items: [{
                                        uid: cryptoRandom(),
                                        selectedCode: '',
                                        fitemcode: '',
                                        fitemname: '',
                                        units: [],
                                        fdesc: '',
                                        fsatuan: 'PCS',
                                        fqty: '',
                                        fketdt: '',
                                        maxqty: 0
                                    }],

                                    addItem() {
                                        const newRow = {
                                            uid: cryptoRandom(),
                                            selectedCode: '',
                                            fitemcode: '',
                                            fitemname: '',
                                            units: [],
                                            fdesc: '',
                                            fsatuan: 'PCS',
                                            fqty: '',
                                            fketdt: '',
                                            maxqty: 0
                                        };
                                        this.items.push(newRow);
                                        this.$nextTick(() => this.initSelect2ForRow(newRow));
                                    },

                                    delAt(i) {
                                        if (i === 0) return;
                                        const row = this.items[i];
                                        this.destroySelect2ForRow(row);
                                        this.items.splice(i, 1);
                                    },

                                    onProductChange(row, code) {
                                        const key = (code ?? '').toString().trim();
                                        row.selectedCode = key;

                                        const meta = window.PRODUCT_MAP[key] || {
                                            name: '',
                                            units: [],
                                            stock: 0
                                        };

                                        row.fitemcode = key;
                                        row.fitemname = meta.name || '';
                                        row.maxqty = Number.isFinite(+meta.stock) && +meta.stock > 0 ? +meta.stock : 0;

                                        if (row.maxqty > 0) {
                                            if (!row.fqty || +row.fqty < 1) row.fqty = 1;
                                            if (+row.fqty > row.maxqty) row.fqty = row.maxqty;
                                        } else {
                                            row.fqty = '';
                                        }

                                        const cleanUnits = Array.from(new Set(
                                            (meta.units || []).map(u => (u ?? '').toString().trim()).filter(Boolean)
                                        ));
                                        row.units = cleanUnits;

                                        if (!row.units.includes(row.fsatuan)) {
                                            row.fsatuan = row.units[0] || '';
                                        }
                                    },

                                    enforceQty(row) {
                                        const n = +row.fqty;
                                        if (!Number.isFinite(n)) {
                                            row.fqty = '';
                                            return;
                                        }
                                        if (n < 1) row.fqty = 1;
                                        if (row.maxqty > 0 && n > row.maxqty) row.fqty = row.maxqty;
                                    },

                                    checkAutoAdd(idx) {
                                        if (idx === this.items.length - 1) {
                                            const r = this.items[idx];
                                            if (r.fitemcode && r.fsatuan && r.fqty) this.addItem();
                                        }
                                    },

                                    // ==== Select2 helpers ====
                                    select2Ready() {
                                        return !!(window.jQuery && $.fn && $.fn.select2);
                                    },

                                    initSelect2ForRow(row) {
                                        if (!this.select2Ready()) return;
                                        const codeSel = `#code-${row.uid}`;
                                        const nameSel = `#name-${row.uid}`;

                                        $(codeSel).select2({
                                            width: '100%',
                                            dropdownParent: $(codeSel).closest('.p-1'),
                                            minimumResultsForSearch: 8
                                        });
                                        $(nameSel).select2({
                                            width: '100%',
                                            dropdownParent: $(nameSel).closest('.p-1'),
                                            minimumResultsForSearch: 8
                                        });

                                        $(codeSel).val(row.selectedCode).trigger('change.select2');
                                        $(nameSel).val(row.selectedCode).trigger('change.select2');

                                        $(codeSel).on('change.select2', () => {
                                            const val = ($(codeSel).val() || '').toString();
                                            if (row.selectedCode !== val) {
                                                this.onProductChange(row, val);
                                                $(nameSel).val(val).trigger('change.select2');
                                            }
                                        });

                                        $(nameSel).on('change.select2', () => {
                                            const val = ($(nameSel).val() || '').toString();
                                            if (row.selectedCode !== val) {
                                                this.onProductChange(row, val);
                                                $(codeSel).val(val).trigger('change.select2');
                                            }
                                        });

                                        this.$watch(() => row.selectedCode, (val) => {
                                            const v = (val || '').toString();
                                            $(codeSel).val(v).trigger('change.select2');
                                            $(nameSel).val(v).trigger('change.select2');
                                        });
                                    },

                                    destroySelect2ForRow(row) {
                                        if (!(window.jQuery && $.fn)) return;
                                        const codeSel = `#code-${row.uid}`;
                                        const nameSel = `#name-${row.uid}`;
                                        if ($(codeSel).data('select2')) $(codeSel).off().select2('destroy');
                                        if ($(nameSel).data('select2')) $(nameSel).off().select2('destroy');
                                    },

                                    // ==== Browse modal trigger ====
                                    openBrowse(idx) {
                                        window.dispatchEvent(new CustomEvent('browse-open', {
                                            detail: {
                                                index: idx
                                            }
                                        }));
                                    },

                                    // ==== Apply chosen product from modal ====
                                    applyChosenProduct(row, p) {
                                        const code = (p.fproductcode || '').toString();
                                        const name = (p.fproductname || '').toString();

                                        const units = Array.from(new Set(
                                            [p.fsatuankecil, p.fsatuanbesar, p.fsatuanbesar2]
                                            .map(u => (u ?? '').toString().trim())
                                            .filter(Boolean)
                                        ));

                                        row.selectedCode = code;
                                        row.fitemcode = code;
                                        row.fitemname = name;
                                        row.units = units;

                                        if (!row.units.includes(row.fsatuan)) {
                                            row.fsatuan = row.units[0] || '';
                                        }

                                        const stock = Number.isFinite(+p.fminstock) ? +p.fminstock : 0;
                                        row.maxqty = stock > 0 ? stock : 0;

                                        if (row.maxqty > 0) {
                                            if (!row.fqty || +row.fqty < 1) row.fqty = 1;
                                            if (+row.fqty > row.maxqty) row.fqty = row.maxqty;
                                        } else {
                                            row.fqty = '';
                                        }

                                        if (this.select2Ready()) {
                                            const codeSel = `#code-${row.uid}`;
                                            const nameSel = `#name-${row.uid}`;
                                            this.$nextTick(() => {
                                                if ($(codeSel).data('select2')) $(codeSel).val(code).trigger('change.select2');
                                                if ($(nameSel).data('select2')) $(nameSel).val(code).trigger('change.select2');
                                            });
                                        }
                                    },

                                    init() {
                                        this.$nextTick(() => this.items.forEach(r => this.initSelect2ForRow(r)));

                                        window.addEventListener('keydown', (e) => {
                                            if (e.key === 'F2') {
                                                e.preventDefault();
                                                this.addItem();
                                            }
                                        });

                                        window.addEventListener('product-chosen', (e) => {
                                            const {
                                                index,
                                                product
                                            } = e.detail || {};
                                            if (index == null || !this.items[index] || !product) return;
                                            const row = this.items[index];
                                            this.applyChosenProduct(row, product);
                                        });
                                    }
                                }
                            }
                        </script>

                        <script>
                            window.PRODUCTS_LOCAL = [
                                @foreach ($products as $p)
                                    {
                                        fproductcode: @json($p->fproductcode),
                                        fproductname: @json($p->fproductname),
                                        fsatuankecil: @json($p->fsatuankecil),
                                        fsatuanbesar: @json($p->fsatuanbesar),
                                        fsatuanbesar2: @json($p->fsatuanbesar2),
                                        fminstock: @json($p->fminstock ?? 0),
                                    },
                                @endforeach
                            ];
                        </script>

                        <br>

                        <div class="md:col-span-2 flex justify-center items-center space-x-2">
                            <label class="block text-sm font-medium">Approval</label>
                            <label class="switch">
                                <input type="checkbox" name="fuserapproved" id="approvalToggle"
                                    {{ session('fuserapproved') ? 'checked' : '' }}>
                                <span class="slider round"></span>
                            </label>
                        </div>

                </div>

                <br>

                <!-- Tombol Aksi -->
                <div class="mt-6 flex justify-center space-x-4">
                    <!-- Simpan -->
                    <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                        <x-heroicon-o-check class="w-5 h-5 mr-2" />
                        Simpan
                    </button>

                    <!-- Keluar -->
                    <button type="button" @click="window.location.href='{{ route('tr_prh.index') }}'"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                        Keluar
                    </button>
                </div>
            </form>
        </div>
    </div>
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

@endsection

<style>
    .select2-container {
        width: 100% !important;
    }

    .select2-container--default .select2-selection--single {
        height: 36px !important;
        min-height: 36px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.375rem !important;
        display: flex !important;
        align-items: center !important;
        padding: 0 0.5rem !important;
        font-size: 0.875rem !important;
        line-height: 1.25rem !important;
        background-color: #fff !important;
    }


    .select2-container--default .select2-selection--single .select2-selection__rendered {
        margin: 0 !important;
        padding: 0 !important;
        color: #111827 !important;
        line-height: 1.25rem !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #6b7280 !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        right: 0.5rem !important;
    }

    .select2-container--default .select2-selection--single:hover {
        border-color: #9ca3af !important;
    }

    .select2-container--default.select2-container--focus .select2-selection--single,
    .select2-container--default .select2-selection--single:focus {
        border-color: #2563eb !important;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2) !important;
        outline: none !important;
    }

    .select2-dropdown {
        border: 1px solid #d1d5db !important;
        border-radius: 0.375rem !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
    }

    .select2-results__option--highlighted {
        background-color: #2563eb !important;
        color: #fff !important;
    }

    .p-1>.select2,
    .p-1>.select2-container {
        width: 100% !important;
    }

    .switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
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
        background-color: #ccc;
        transition: 0.4s;
        border-radius: 34px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        border-radius: 50%;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: 0.4s;
    }

    input:checked+.slider {
        background-color: #4CAF50;
    }

    input:checked+.slider:before {
        transform: translateX(26px);
    }

    .slider.round {
        border-radius: 34px;
    }

    .slider.round:before {
        border-radius: 50%;
    }
</style>

<style>
    input:focus,
    select:focus,
    textarea:focus,
    .select2-container--default .select2-selection--single:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
    }

    .select2-container--default .select2-selection--single {
        border: 1px solid #000000 !important;
        border-radius: 0.375rem;
        height: 42px;
        padding: 0.5rem 0.75rem;
        width: 100% !important;
        background-color: white;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px;
    }

    .select2-dropdown {
        border: 1px solid #000000 !important;
        border-radius: 0.375rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .select2-results__option {
        padding: 8px 12px;
    }

    .select2-results__option--highlighted {
        background-color: #2563eb !important;
        color: white !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #000000 !important;
    }
</style>

<style>
    [x-cloak] {
        display: none !important;
    }
</style>
