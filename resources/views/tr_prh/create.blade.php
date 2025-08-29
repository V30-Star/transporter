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

                    <div x-data="detailProduk()" class="mt-6 space-y-4">
                        <!-- SINGLE CARD (tanpa loop) -->
                        <div class="rounded-lg border shadow-sm overflow-hidden">
                            <div class="bg-gray-50 border-b px-4 py-2 flex items-center justify-between">
                                <div class="font-medium text-gray-700">Input Item Barang </div>
                            </div>

                            <div class="p-4 space-y-4">
                                <!-- Baris 1 -->
                                <div class="grid grid-cols-12 gap-3">
                                    <!-- Kode + Browse -->
                                    <div class="col-span-12 md:col-span-4">
                                        <label class="block text-sm font-medium mb-1">Kode Produk</label>
                                        <div class="flex">
                                            <input type="text" class="flex-1 border rounded-l px-3 py-2"
                                                x-model.trim="form.fitemcode" @input="onCodeTyped()">
                                            <button type="button" @click="openBrowse()"
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
                                            :value="form.fitemname" disabled>
                                    </div>

                                    <!-- Qty -->
                                    <div class="col-span-12 md:col-span-3">
                                        <label class="block text-sm font-medium mb-1">Qty.</label>
                                        <input type="number" :min="1"
                                            :max="form.maxqty > 0 ? form.maxqty : null" step="1"
                                            class="w-full border rounded px-3 py-2 text-right" x-model.number="form.fqty"
                                            @input="enforceQty()">
                                        <p class="text-[11px] text-gray-500 mt-1" x-show="form.maxqty > 0">
                                            Maks: <span x-text="form.maxqty"></span>
                                        </p>
                                    </div>
                                </div>

                                <!-- Baris 2 -->
                                <div class="grid grid-cols-12 gap-3">
                                    <!-- Satuan -->
                                    <div class="col-span-12 md:col-span-3">
                                        <label class="block text-sm font-medium mb-1">Satuan</label>

                                        <template x-if="form.units.length > 1">
                                            <select class="w-full border rounded px-3 py-2" x-model="form.fsatuan">
                                                <template x-for="u in form.units" :key="u">
                                                    <option :value="u" x-text="u"></option>
                                                </template>
                                            </select>
                                        </template>

                                        <template x-if="form.units.length <= 1">
                                            <div>
                                                <input type="text"
                                                    class="w-full border rounded px-3 py-2 bg-gray-100 text-gray-600"
                                                    :value="form.fsatuan || '-'" disabled>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Desc -->
                                    <div class="col-span-12 md:col-span-4">
                                        <label class="block text-sm font-medium mb-1">Desc.</label>
                                        <input type="text" class="w-full border rounded px-3 py-2"
                                            x-model="form.fdesc">
                                    </div>

                                    <!-- Keterangan -->
                                    <div class="col-span-12 md:col-span-5">
                                        <label class="block text-sm font-medium mb-1">Keterangan</label>
                                        <input type="text" class="w-full border rounded px-3 py-2"
                                            x-model="form.fketdt">
                                    </div>
                                </div>
                                <!-- Tombol di kanan bawah -->
                                <div class="flex justify-end gap-3 mt-3">
                                    <button type="button" @click="saveCurrent()"
                                        class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
                                        Save
                                    </button>
                                    <button type="button" @click="resetForm()"
                                        class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">
                                        Clear
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- RINGKASAN ITEM TERSIMPAN -->
                        <div class="mt-6">
                            <h3 class="text-base font-semibold text-gray-800 mb-2">Ringkasan Item</h3>

                            <div x-show="savedItems.length === 0" class="text-sm text-gray-500">
                                Belum ada item. Isi form di atas lalu klik <b>Save</b>.
                            </div>

                            <div x-show="savedItems.length > 0" class="overflow-auto border rounded">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="p-2 text-left">#</th>
                                            <th class="p-2 text-left">PR#</th>
                                            <th class="p-2 text-left">Supplier</th>
                                            <th class="p-2 text-left">Tanggal</th>
                                            <th class="p-2 text-left">Tanggal Dibutuhkan</th>
                                            <th class="p-2 text-left">Tanggal Paling Lambat</th>
                                            <th class="p-2 text-left">Kode Product</th>
                                            <th class="p-2 text-left">Nama Nama Product</th>
                                            <th class="p-2 text-left">Satuan</th>
                                            <th class="p-2 text-right">Qty</th>
                                            <th class="p-2 text-left">Desc</th>
                                            <th class="p-2 text-left">Ket PR</th>
                                            <th class="p-2 text-left">Ket Item</th>
                                            <th class="p-2 text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(it, i) in savedItems" :key="it.uid">
                                            <tr class="border-t">
                                                <td class="p-2" x-text="i+1"></td>

                                                <!-- header columns -->
                                                <td class="p-2 font-mono" x-text="it._prno || '-'"></td>
                                                <td class="p-2" x-text="it._supplierNm || it._supplier || '-'"></td>
                                                <td class="p-2" x-text="it._prdate || '-'"></td>
                                                <td class="p-2" x-text="it._needdate || '-'"></td>
                                                <td class="p-2" x-text="it._duedate || '-'"></td>

                                                <!-- item columns -->
                                                <td class="p-2 font-mono" x-text="it.fitemcode"></td>
                                                <td class="p-2" x-text="it.fitemname"></td>
                                                <td class="p-2" x-text="it.fsatuan"></td>
                                                <td class="p-2 text-right" x-text="it.fqty"></td>
                                                <td class="p-2" x-text="it.fdesc || '-'"></td>
                                                <td class="p-2" x-text="it._ketHeader || '-'"></td>
                                                <td class="p-2" x-text="it.fketdt || '-'"></td>

                                                <td class="p-2 text-center">
                                                    <button type="button" @click="removeSaved(i)"
                                                        class="px-3 py-1 rounded text-xs bg-red-100 text-red-600 hover:bg-red-200">
                                                        Hapus
                                                    </button>
                                                </td>

                                                <!-- hidden inputs agar ikut submit -->
                                                <td class="hidden">
                                                    <!-- header fields per-row -->
                                                    <input type="hidden" name="row_prno[]" :value="it._prno">
                                                    <input type="hidden" name="row_supplier[]" :value="it._supplier">
                                                    <!-- kode -->
                                                    <input type="hidden" name="row_supplier_nm[]"
                                                        :value="it._supplierNm"> <!-- label (opsional) -->
                                                    <input type="hidden" name="row_prdate[]" :value="it._prdate">
                                                    <input type="hidden" name="row_needdate[]" :value="it._needdate">
                                                    <input type="hidden" name="row_duedate[]" :value="it._duedate">
                                                    <input type="hidden" name="row_ketheader[]" :value="it._ketHeader">

                                                    <!-- item fields per-row -->
                                                    <input type="hidden" name="fitemcode[]" :value="it.fitemcode">
                                                    <input type="hidden" name="fitemname[]" :value="it.fitemname">
                                                    <input type="hidden" name="fsatuan[]" :value="it.fsatuan">
                                                    <input type="hidden" name="fqty[]" :value="it.fqty">
                                                    <input type="hidden" name="fdesc[]" :value="it.fdesc">
                                                    <input type="hidden" name="fketdt[]" :value="it.fketdt">
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
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
            choose(p) {
                window.dispatchEvent(new CustomEvent('product-chosen', {
                    detail: {
                        product: p
                    }
                }));
                this.close();
            },
            init() {
                window.addEventListener('browse-open', () => this.openBrowse(), {
                    passive: true
                });
            }
        }
    }
</script>

<script>
    function detailProduk() {
        return {
            form: {
                fitemcode: '',
                fitemname: '',
                units: [],
                fsatuan: '',
                fqty: '',
                fdesc: '',
                fketdt: '',
                maxqty: 0
            },
            savedItems: [],

            // === Ambil nilai field header (atas) ===
            getHeaderValues() {
                const prno = document.querySelector('input[name="fprno"]')?.value?.trim() || '';
                const prdate = document.querySelector('input[name="fprdate"]')?.value || '';
                const needdate = document.querySelector('input[name="fneeddate"]')?.value || '';
                const duedate = document.querySelector('input[name="fduedate"]')?.value || '';
                const ketHeader = document.querySelector('textarea[name="fket"]')?.value?.trim() || '';

                const sel = document.getElementById('supplierSelect');
                const supplierLabel = sel?.selectedOptions?.[0]?.text?.trim() || '';
                const supplierCode = document.getElementById('supplierCodeHidden')?.value?.trim() || '';

                return {
                    prno,
                    supplierCode,
                    supplierLabel,
                    prdate,
                    needdate,
                    duedate,
                    ketHeader
                };
            },

            // === Kosongkan field header setelah Save ===
            clearHeaderFields() {
                const fprno = document.querySelector('input[name="fprno"]');
                const prdate = document.querySelector('input[name="fprdate"]');
                const needdate = document.querySelector('input[name="fneeddate"]');
                const duedate = document.querySelector('input[name="fduedate"]');
                const ketHeader = document.querySelector('textarea[name="fket"]');

                const sel = document.getElementById('supplierSelect');
                const hid = document.getElementById('supplierCodeHidden');

                if (fprno && !fprno.disabled) fprno.value = '';
                if (prdate) prdate.value = '';
                if (needdate) needdate.value = '';
                if (duedate) duedate.value = '';
                if (ketHeader) ketHeader.value = '';

                if (sel) {
                    sel.selectedIndex = -1; // kosongkan tampilan supplier
                    sel.dispatchEvent(new Event('change'));
                }
                if (hid) hid.value = ''; // kosongkan kode supplier (untuk submit)
            },

            // === Reset form item (card) ===
            resetForm() {
                this.form = {
                    fitemcode: '',
                    fitemname: '',
                    units: [],
                    fsatuan: '',
                    fqty: '',
                    fdesc: '',
                    fketdt: '',
                    maxqty: 0
                };
            },

            // === Simpan 1 baris item + attach header ===
            saveCurrent() {
                const r = this.form;
                if (!r.fitemcode) return alert('Kode Produk belum diisi.');
                if (!r.fitemname) return alert('Nama Produk belum terisi.');
                if (!r.fsatuan) return alert('Satuan belum dipilih.');
                if (!r.fqty || +r.fqty <= 0) return alert('Qty harus > 0.');

                // ambil field header
                const h = this.getHeaderValues();

                // deteksi duplikat yang memperhitungkan header
                const dupe = this.savedItems.find(it =>
                    it.fitemcode === r.fitemcode &&
                    it.fsatuan === r.fsatuan &&
                    (it.fdesc || '') === (r.fdesc || '') &&
                    (it.fketdt || '') === (r.fketdt || '') &&
                    it._prno === h.prno &&
                    it._supplier === h.supplierCode &&
                    it._prdate === h.prdate &&
                    it._needdate === h.needdate &&
                    it._duedate === h.duedate &&
                    (it._ketHeader || '') === (h.ketHeader || '')
                );
                if (dupe && !confirm('Item dengan header yang sama sudah ada. Tambahkan lagi sebagai baris baru?'))
                    return;

                // push ke list
                this.savedItems.push({
                    uid: cryptoRandom(),

                    // item detail
                    fitemcode: r.fitemcode,
                    fitemname: r.fitemname,
                    fsatuan: r.fsatuan,
                    fqty: +r.fqty,
                    fdesc: r.fdesc || '',
                    fketdt: r.fketdt || '',

                    // header attach
                    _prno: h.prno,
                    _supplier: h.supplierCode, // kode untuk submit
                    _supplierNm: h.supplierLabel, // label untuk tampil
                    _prdate: h.prdate,
                    _needdate: h.needdate,
                    _duedate: h.duedate,
                    _ketHeader: h.ketHeader
                });

                // bersihkan form item + header
                this.resetForm();
                this.clearHeaderFields();
            },

            // === Hapus baris di ringkasan ===
            removeSaved(i) {
                this.savedItems.splice(i, 1);
            },

            // === Ketik kode produk manual ===
            onCodeTyped() {
                const key = (this.form.fitemcode || '').trim();
                const meta = window.PRODUCT_MAP[key] || null;
                if (!meta) {
                    this.form.fitemname = '';
                    this.form.units = [];
                    this.form.fsatuan = '';
                    this.form.maxqty = 0;
                    return;
                }
                this.form.fitemname = meta.name || '';

                const cleanUnits = Array.from(new Set(
                    (meta.units || [])
                    .map(u => (u ?? '').toString().trim())
                    .filter(Boolean)
                ));
                this.form.units = cleanUnits;
                if (!this.form.units.includes(this.form.fsatuan)) {
                    this.form.fsatuan = this.form.units[0] || '';
                }

                this.form.maxqty = Number.isFinite(+meta.stock) && +meta.stock > 0 ? +meta.stock : 0;
                if (this.form.maxqty > 0) {
                    if (!this.form.fqty || +this.form.fqty < 1) this.form.fqty = 1;
                    if (+this.form.fqty > this.form.maxqty) this.form.fqty = this.form.maxqty;
                } else {
                    this.form.fqty = '';
                }
            },

            // === Apply produk dari modal browse ===
            applyChosenProduct(p) {
                const code = (p.fproductcode || '').toString();
                const name = (p.fproductname || '').toString();
                const units = Array.from(new Set(
                    [p.fsatuankecil, p.fsatuanbesar, p.fsatuanbesar2]
                    .map(u => (u ?? '').toString().trim())
                    .filter(Boolean)
                ));

                this.form.fitemcode = code;
                this.form.fitemname = name;
                this.form.units = units;
                if (!this.form.units.includes(this.form.fsatuan)) {
                    this.form.fsatuan = this.form.units[0] || '';
                }

                const stock = Number.isFinite(+p.fminstock) ? +p.fminstock : 0;
                this.form.maxqty = stock > 0 ? stock : 0;
                if (this.form.maxqty > 0) {
                    if (!this.form.fqty || +this.form.fqty < 1) this.form.fqty = 1;
                    if (+this.form.fqty > this.form.maxqty) this.form.fqty = this.form.maxqty;
                } else {
                    this.form.fqty = '';
                }
            },

            // === Paksa qty dalam batas ===
            enforceQty() {
                const n = +this.form.fqty;
                if (!Number.isFinite(n)) {
                    this.form.fqty = '';
                    return;
                }
                if (n < 1) this.form.fqty = 1;
                if (this.form.maxqty > 0 && n > this.form.maxqty) this.form.fqty = this.form.maxqty;
            },

            // === Buka modal browse product ===
            openBrowse() {
                window.dispatchEvent(new CustomEvent('browse-open'));
            },

            // === Init: dengarkan event pilih produk dari modal ===
            init() {
                window.addEventListener('product-chosen', (e) => {
                    const {
                        product
                    } = e.detail || {};
                    if (!product) return;
                    this.applyChosenProduct(product);
                });
            }
        }
    }
</script>
