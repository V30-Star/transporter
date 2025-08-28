@extends('layouts.app')

@section('title', 'Permintaan Pembelian')

@section('content')
    <!-- Base focus + utilities (digabung, tanpa duplikasi) -->
    <style>
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
        }

        /* Toggle switch (Approval) */
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
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #ccc;
            transition: .4s;
            border-radius: 34px
        }

        .slider:before {
            position: absolute;
            content: "";
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

        .slider.round {
            border-radius: 34px
        }

        .slider.round:before {
            border-radius: 50%
        }

        /* Alpine cloak */
        [x-cloak] {
            display: none !important
        }

        /* Hilangkan caret bawaan select (termasuk yang di-disable) */
        #supplierSelect,
        #supplierSelect:disabled {
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
            background-image: none !important;
            background-repeat: no-repeat !important;
        }

        #supplierSelect::-ms-expand {
            display: none;
        }

        /* old IE/Edge */
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
                    <!-- Cabang -->
                    <div class="mt-2 w-1/3">
                        <label class="block text-sm font-medium">Cabang User</label>
                        <input type="text" name="fcabang"
                            class="w-full border rounded px-3 py-2 bg-gray-200 cursor-not-allowed"
                            value="{{ $fcabang }}" disabled>
                        <input type="hidden" name="fbranchcode" value="{{ $fcabang }}">
                    </div>

                    <!-- PR No. + Auto -->
                    <div x-data="{ autoCode: true }" class="flex items-center gap-4">
                        <div class="mt-2 w-1/3">
                            <label class="block text-sm font-medium">PR#</label>
                            <input type="text" name="fprno" class="w-full border rounded px-3 py-2"
                                :disabled="autoCode" :value="autoCode ? '' : '{{ old('fprno') }}'"
                                :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'">
                        </div>
                        <label class="inline-flex items-center mt-6">
                            <input type="checkbox" x-model="autoCode" class="form-checkbox text-indigo-600" checked>
                            <span class="ml-2 text-sm text-gray-700">Auto</span>
                        </label>
                    </div>

                    <!-- Supplier (disabled, no dropdown) -->
                    <div class="mt-4 w-1/4">
                        <label class="block text-sm font-medium">Supplier</label>

                        <div class="flex">
                            <!-- clickable display-only field -->
                            <div class="relative flex-1">
                                <select id="supplierSelect" name="fsupplier_view"
                                    class="w-full border rounded-l px-3 py-2 appearance-none bg-gray-100 text-gray-700 cursor-not-allowed"
                                    disabled>
                                    <option value=""></option>
                                    @foreach ($supplier as $suppliers)
                                        <option value="{{ $suppliers->fsupplierid }}"
                                            {{ old('fsupplier') == $suppliers->fsupplierid ? 'selected' : '' }}>
                                            {{ $suppliers->fsuppliercode }} - {{ $suppliers->fsuppliername }}
                                        </option>
                                    @endforeach
                                </select>

                                <!-- invisible overlay only over the select; clicking it opens the modal -->
                                <div class="absolute inset-0" role="button" aria-label="Browse supplier"
                                    @click="window.dispatchEvent(new CustomEvent('supplier-browse-open'))"></div>
                            </div>

                            <input type="hidden" name="fsupplier" id="supplierCodeHidden" value="{{ old('fsupplier') }}">

                            <!-- Browse -->
                            <button type="button" @click="window.dispatchEvent(new CustomEvent('supplier-browse-open'))"
                                class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                                title="Browse Supplier">
                                <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                            </button>

                            <!-- Create -->
                            <a href="{{ route('supplier.create') }}" target="_blank" rel="noopener"
                                class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50"
                                title="Tambah Supplier (tab baru)">
                                <x-heroicon-o-plus class="w-5 h-5" />
                            </a>
                        </div>
                        @error('fsupplier')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- MODAL BROWSE SUPPLIER (global) -->
                    <div x-data="supplierBrowser()" x-show="open" x-cloak x-transition.opacity
                        class="fixed inset-0 z-50 flex items-center justify-center">
                        <div class="absolute inset-0 bg-black/40" @click="close()"></div>

                        <div class="relative bg-white rounded-2xl shadow-xl w-[90vw] max-w-4xl max-h-[85vh] flex flex-col">
                            <div class="p-4 border-b flex items-center gap-3">
                                <h3 class="text-lg font-semibold">Browse Supplier</h3>
                                <div class="ml-auto flex items-center gap-2">
                                    <input type="text" x-model="keyword" @keydown.enter.prevent="search()"
                                        placeholder="Cari kode / nama…" class="border rounded px-3 py-2 w-64">
                                    <button type="button" @click="search()"
                                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Search</button>
                                </div>
                            </div>

                            <div class="p-0 overflow-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-100 sticky top-0">
                                        <tr>
                                            <th class="text-left p-2">Supplier (Kode - Nama)</th>
                                            <th class="text-left p-2 w-40">Telepon</th>
                                            <th class="text-center p-2 w-28">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="s in rows" :key="s.fsupplierid">
                                            <tr class="border-b hover:bg-gray-50">
                                                <td class="p-2" x-text="`${s.fsuppliercode} - ${s.fsuppliername}`"></td>
                                                <td class="p-2" x-text="s.ftelp || '-'"></td>
                                                <td class="p-2 text-center">
                                                    <button type="button" @click="choose(s)"
                                                        class="px-3 py-1 rounded text-xs bg-emerald-600 hover:bg-emerald-700 text-white">Pilih</button>
                                                </td>
                                            </tr>
                                        </template>
                                        <tr x-show="rows.length === 0">
                                            <td colspan="3" class="p-4 text-center text-gray-500">Tidak ada data.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

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

                    <script>
                        function supplierBrowser() {
                            return {
                                open: false,
                                keyword: '',
                                page: 1,
                                lastPage: 1,
                                perPage: 10,
                                total: 0,
                                rows: [],

                                apiUrl() {
                                    const u = new URL("{{ route('suppliers.browse') }}", window.location.origin); // ganti jika berbeda
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
                                openBrowse() {
                                    this.open = true;
                                    this.page = 1;
                                    this.fetch();
                                },
                                close() {
                                    this.open = false;
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
                                choose(s) {
                                    const sel = document.getElementById('supplierSelect');
                                    const hid = document.getElementById('supplierCodeHidden'); // submitted (string)
                                    if (!sel) {
                                        this.close();
                                        return;
                                    }

                                    // If the option doesn’t exist yet (e.g., newly created in another tab), append it.
                                    let opt = Array.from(sel.options).find(o => o.value == String(s.fsupplierid));
                                    const label = `${s.fsuppliercode} - ${s.fsuppliername}`;
                                    if (!opt) {
                                        opt = new Option(label, s.fsupplierid, true, true);
                                        sel.add(opt);
                                    } else {
                                        opt.text = label;
                                        opt.selected = true;
                                    }

                                    sel.dispatchEvent(new Event('change'));
                                    if (hid) hid.value = s.fsuppliercode; // <-- SUBMIT CODE (string) to satisfy your validator
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

                    <!-- Tanggal -->
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

                    <!-- Keterangan -->
                    <div class="mt-4">
                        <label class="block text-sm font-medium">Keterangan</label>
                        <textarea name="fket" rows="4"
                            class="w-full border rounded px-3 py-2 @error('fket') border-red-500 @enderror"
                            placeholder="Tulis keterangan tambahan di sini...">{{ old('fket') }}</textarea>
                        @error('fket')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <br>

                    <!-- LIST ITEM: versi card -->
                    <div x-data="detailProduk()" class="mt-6 space-y-4">
                        <template x-for="(row, idx) in items" :key="row.uid">
                            <div class="rounded-lg border shadow-sm overflow-hidden">
                                <!-- header per-item -->
                                <div class="bg-gray-50 border-b px-4 py-2 flex items-center justify-between">
                                    <div class="font-medium text-gray-700">
                                        Input Item Barang
                                        <span class="text-xs text-gray-500">#<span x-text="idx+1"></span></span>
                                    </div>
                                    <button type="button" @click="delAt(idx)" :disabled="idx === 0"
                                        :class="idx === 0 ?
                                            'h-8 px-3 rounded bg-gray-100 text-gray-400 text-xs cursor-not-allowed' :
                                            'h-8 px-3 rounded bg-red-100 text-red-600 text-xs hover:bg-red-200'">
                                        Hapus
                                    </button>
                                </div>

                                <!-- isi form -->
                                <div class="p-4 space-y-4">
                                    <!-- Baris 1 -->
                                    <div class="grid grid-cols-12 gap-3">
                                        <!-- Kode + Browse -->
                                        <div class="col-span-12 md:col-span-4">
                                            <label class="block text-sm font-medium mb-1">Kode Produk</label>
                                            <div class="flex">
                                                <input type="text" class="flex-1 border rounded-l px-3 py-2"
                                                    x-model.trim="row.fitemcode" name="fitemcode[]"
                                                    @input="onCodeTyped(row)">
                                                <button type="button" @click="openBrowse(idx)"
                                                    class="border border-l-0 rounded-r px-3 py-2 bg-white hover:bg-gray-50"
                                                    title="Cari Produk">
                                                    <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                                </button>
                                                <a href="{{ route('product.create') }}" target="_blank" rel="noopener"
                                                    class="border border-l-0 rounded-r px-3 py-2 bg-white hover:bg-gray-50"
                                                    title="Tambah Produk (buka tab baru)">
                                                    <x-heroicon-o-plus class="w-5 h-5" />
                                                </a>
                                            </div>
                                        </div>

                                        <!-- Nama (disabled) -->
                                        <div class="col-span-12 md:col-span-5">
                                            <label class="block text-sm font-medium mb-1">Nama Produk</label>
                                            <input type="text"
                                                class="w-full border rounded px-3 py-2 bg-gray-100 text-gray-600"
                                                :value="row.fitemname" disabled>
                                            <input type="hidden" name="fitemname[]" :value="row.fitemname">
                                        </div>

                                        <!-- Qty -->
                                        <div class="col-span-12 md:col-span-3">
                                            <label class="block text-sm font-medium mb-1">Qty.</label>
                                            <input type="number" :min="1"
                                                :max="row.maxqty > 0 ? row.maxqty : null" step="1"
                                                class="w-full border rounded px-3 py-2 text-right"
                                                x-model.number="row.fqty" name="fqty[]"
                                                @input="enforceQty(row); checkAutoAdd(idx)">
                                            <p class="text-[11px] text-gray-500 mt-1" x-show="row.maxqty > 0">
                                                Maks: <span x-text="row.maxqty"></span>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Baris 2 -->
                                    <div class="grid grid-cols-12 gap-3">
                                        <!-- Satuan: dropdown jika >1 -->
                                        <div class="col-span-12 md:col-span-3">
                                            <label class="block text-sm font-medium mb-1">Satuan</label>

                                            <template x-if="row.units.length > 1">
                                                <select class="w-full border rounded px-3 py-2" name="fsatuan[]"
                                                    x-model="row.fsatuan">
                                                    <template x-for="u in row.units" :key="u">
                                                        <option :value="u" x-text="u"></option>
                                                    </template>
                                                </select>
                                            </template>

                                            <template x-if="row.units.length <= 1">
                                                <div>
                                                    <input type="text"
                                                        class="w-full border rounded px-3 py-2 bg-gray-100 text-gray-600"
                                                        :value="row.fsatuan || '-'" disabled>
                                                    <input type="hidden" name="fsatuan[]" :value="row.fsatuan">
                                                </div>
                                            </template>
                                        </div>

                                        <!-- Desc -->
                                        <div class="col-span-12 md:col-span-4">
                                            <label class="block text-sm font-medium mb-1">Desc.</label>
                                            <input type="text" class="w-full border rounded px-3 py-2"
                                                x-model="row.fdesc" name="fdesc[]" @input="checkAutoAdd(idx)">
                                        </div>

                                        <!-- Keterangan -->
                                        <div class="col-span-12 md:col-span-5">
                                            <label class="block text-sm font-medium mb-1">Keterangan</label>
                                            <input type="text" class="w-full border rounded px-3 py-2"
                                                x-model="row.fketdt" name="fketdt[]" @input="checkAutoAdd(idx)">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- MODAL BROWSE PRODUCT (1x, global) -->
                    <div x-data="productBrowser()" x-show="open" x-cloak x-transition.opacity
                        class="fixed inset-0 z-50 flex items-center justify-center">
                        <div class="absolute inset-0 bg-black/40" @click="close()"></div>

                        <div class="relative bg-white rounded-2xl shadow-xl w-[90vw] max-w-5xl max-h-[85vh] flex flex-col">
                            <div class="p-4 border-b flex items-center gap-3">
                                <h3 class="text-lg font-semibold">Browse Product</h3>
                                <div class="ml-auto flex items-center gap-2">
                                    <input type="text" x-model="keyword" @keydown.enter.prevent="search()"
                                        placeholder="Cari kode / nama…" class="border rounded px-3 py-2 w-64">
                                    <button type="button" @click="search()"
                                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Search</button>
                                </div>
                            </div>

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
                                                        class="px-3 py-1 rounded text-xs bg-emerald-600 hover:bg-emerald-700 text-white">Pilih</button>
                                                </td>
                                            </tr>
                                        </template>
                                        <tr x-show="rows.length === 0">
                                            <td colspan="5" class="p-4 text-center text-gray-500">Tidak ada data.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

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

                    <!-- Approval -->
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
                    <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                        <x-heroicon-o-check class="w-5 h-5 mr-2" /> Simpan
                    </button>
                    <button type="button" @click="window.location.href='{{ route('tr_prh.index') }}'"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" /> Keluar
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mt-4">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
@endsection

<!-- Script Alpine data: PRODUCT_MAP (dipakai), productBrowser, detailProduk -->
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

    function detailProduk() {
        return {
            items: [{
                uid: cryptoRandom(),
                fitemcode: '',
                fitemname: '',
                units: [],
                fdesc: '',
                fsatuan: '',
                fqty: '',
                fketdt: '',
                maxqty: 0
            }],

            addItem() {
                this.items.push({
                    uid: cryptoRandom(),
                    fitemcode: '',
                    fitemname: '',
                    units: [],
                    fdesc: '',
                    fsatuan: '',
                    fqty: '',
                    fketdt: '',
                    maxqty: 0
                });
            },
            delAt(i) {
                if (i === 0) return;
                this.items.splice(i, 1);
            },

            onCodeTyped(row) {
                const key = (row.fitemcode || '').trim();
                const meta = window.PRODUCT_MAP[key] || null;

                if (!meta) {
                    row.fitemname = '';
                    row.units = [];
                    row.fsatuan = '';
                    row.maxqty = 0;
                    return;
                }

                row.fitemname = meta.name || '';
                const cleanUnits = Array.from(new Set((meta.units || []).map(u => (u ?? '').toString().trim()).filter(
                    Boolean)));
                row.units = cleanUnits;

                if (!row.units.includes(row.fsatuan)) row.fsatuan = row.units[0] || '';

                row.maxqty = Number.isFinite(+meta.stock) && +meta.stock > 0 ? +meta.stock : 0;

                if (row.maxqty > 0) {
                    if (!row.fqty || +row.fqty < 1) row.fqty = 1;
                    if (+row.fqty > row.maxqty) row.fqty = row.maxqty;
                } else {
                    row.fqty = '';
                }

                this.checkAutoAdd(this.items.indexOf(row));
            },

            applyChosenProduct(row, p) {
                const code = (p.fproductcode || '').toString();
                const name = (p.fproductname || '').toString();

                const units = Array.from(new Set([p.fsatuankecil, p.fsatuanbesar, p.fsatuanbesar2].map(u => (u ?? '')
                    .toString().trim()).filter(Boolean)));

                row.fitemcode = code;
                row.fitemname = name;
                row.units = units;

                if (!row.units.includes(row.fsatuan)) row.fsatuan = row.units[0] || '';

                const stock = Number.isFinite(+p.fminstock) ? +p.fminstock : 0;
                row.maxqty = stock > 0 ? stock : 0;

                if (row.maxqty > 0) {
                    if (!row.fqty || +row.fqty < 1) row.fqty = 1;
                    if (+row.fqty > row.maxqty) row.fqty = row.maxqty;
                } else {
                    row.fqty = '';
                }

                this.checkAutoAdd(this.items.indexOf(row));
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

            openBrowse(idx) {
                window.dispatchEvent(new CustomEvent('browse-open', {
                    detail: {
                        index: idx
                    }
                }));
            },

            init() {
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
