@extends('layouts.app')

@section('title', 'Order Pembelian')

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
        <div x-data="{ includePPN: {{ old('fincludeppn', $tr_poh->fincludeppn ?? 0) ? 'true' : 'false' }}, ppnRate: 0, ppnAmount: 0, totalHarga: 100000 }" class="lg:col-span-5">
            <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1600px] w-full mx-auto">
                <form action="{{ route('tr_poh.update', $tr_poh->fpohdid) }}" method="POST" class="mt-6"
                    x-data="{ showNoItems: false }"
                    @submit.prevent="
        const n = Number(document.getElementById('itemsCount')?.value || 0);
        if (n < 1) { showNoItems = true } else { $el.submit() }
      ">
                    @csrf
                    @method('PATCH')

                    {{-- HEADER FORM --}}
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">Cabang</label>
                            <input type="text" class="w-full border rounded px-3 py-2 bg-gray-200 cursor-not-allowed"
                                value="{{ $fcabang }}" disabled>
                            <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                        </div>

                        <div class="lg:col-span-4" x-data="{ autoCode: true }">
                            <label class="block text-sm font-medium mb-1">PO#</label>
                            <div class="flex items-center gap-3">
                                <input type="text" name="fpono" class="w-full border rounded px-3 py-2"
                                    :disabled="autoCode"
                                    :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'">
                                <label class="inline-flex items-center select-none">
                                    <input type="checkbox" x-model="autoCode" checked>
                                    <span class="ml-2 text-sm text-gray-700">Auto</span>
                                </label>
                            </div>
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium mb-1">Supplier</label>
                            <div class="flex">
                                <div class="relative flex-1" for="modal_filter_supplier_id">
                                    <select id="modal_filter_supplier_id" name="filter_supplier_id"
                                        class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
                                        disabled>
                                        <option value=""></option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->fsupplierid }}"
                                                {{ old('fsupplier', $tr_poh->fsupplier) == $supplier->fsupplierid ? 'selected' : '' }}>
                                                {{ $supplier->fsuppliername }}
                                                ({{ $supplier->fsupplierid }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-0" role="button" aria-label="Browse supplier"
                                        @click="window.dispatchEvent(new CustomEvent('supplier-browse-open'))"></div>
                                </div>
                                {{-- kirim ID supplier ke server --}}
                                <input type="hidden" name="fsupplier" id="supplierCodeHidden"
                                    value="{{ old('fsupplier', $tr_poh->fsupplier) }}">
                                <button type="button"
                                    @click="window.dispatchEvent(new CustomEvent('supplier-browse-open'))"
                                    class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                                    title="Browse Supplier">
                                    <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                </button>
                                <a href="{{ route('supplier.create') }}" target="_blank" rel="noopener"
                                    class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50"
                                    title="Tambah Supplier">
                                    <x-heroicon-o-plus class="w-5 h-5" />
                                </a>
                            </div>
                            @error('fsupplier')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">Tanggal</label>
                            <input type="date" name="fpodate" value="{{ old('fpodate') ?? date('Y-m-d') }}"
                                class="w-full border rounded px-3 py-2 @error('fpodate') border-red-500 @enderror">
                            @error('fpodate')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">Tgl. Kirim</label>
                            <input type="date" name="fkirimdate" value="{{ old('fkirimdate', '') }}"
                                class="w-full border rounded px-3 py-2 @error('fkirimdate') border-red-500 @enderror">
                            @error('fkirimdate')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium mb-1">Tempo</label>
                            <div class="flex items-center">
                                <input type="number" id="ftempohr" name="ftempohr" value="{{ old('ftempohr', 0) }}"
                                    class="w-full border rounded px-3 py-2 @error('ftempohr') border-red-500 @enderror">
                                <span class="ml-2">Hari</span>
                            </div>
                            @error('ftempohr')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <script>
                            function updateTempo() {
                                const supplierSelect = document.getElementById('supplierSelect');
                                const tempoInput = document.getElementById('ftempohr');

                                const selectedOption = supplierSelect.options[supplierSelect.selectedIndex];
                                const tempo = selectedOption.getAttribute('data-tempo');

                                tempoInput.value = tempo || 0;
                            }

                            document.addEventListener('DOMContentLoaded', function() {
                                updateTempo();
                            });
                        </script>

                        {{-- ===== Currency & Rate ===== --}}
                        <div x-data="ratesForm()" class="lg:col-span-8 grid grid-cols-1 lg:grid-cols-8 gap-4">

                            {{-- Currency --}}
                            <div class="lg:col-span-4">
                                <label class="block text-sm font-medium">Currency</label>
                                <select name="fcurrency" x-model="currency" @change="applyDefaultIfNeeded()"
                                    class="w-full border rounded px-3 py-2 @error('fcurrency') border-red-500 @enderror">
                                    <option value="IDR"
                                        {{ old('fcurrency', $tr_poh->fcurrency ?? 'IDR') === 'IDR' ? 'selected' : '' }}>IDR
                                    </option>
                                    <option value="USD"
                                        {{ old('fcurrency', $tr_poh->fcurrency ?? 'IDR') === 'USD' ? 'selected' : '' }}>USD
                                    </option>
                                </select>
                                @error('fcurrency')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Editable: 1 USD = X IDR --}}
                            <div class="lg:col-span-4">
                                <label class="block text-sm font-medium">Rate (1 USD = ? IDR)</label>
                                <input type="number" step="0.0001" min="0" name="frate_display"
                                    x-model.number="rateUsdIdr"
                                    class="w-full border rounded px-3 py-2 @error('frate') border-red-500 @enderror"
                                    placeholder="Contoh: 15500" value="{{ old('frate', $tr_poh->frate ?? 15500) }}">
                                @error('frate')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Read-only mirrors --}}
                            <div class="lg:col-span-8">
                                <div class="flex flex-wrap items-center gap-2 text-sm">
                                    <span class="inline-flex items-center gap-2 rounded border px-2 py-1 bg-gray-50">
                                        <span class="font-medium">1 USD</span>
                                        <span>=</span>
                                        <span x-text="fmt(rateUsdIdr, 4)"></span>
                                        <span class="text-gray-600">IDR</span>
                                    </span>

                                    <span class="inline-flex items-center gap-2 rounded border px-2 py-1 bg-gray-50">
                                        <span class="font-medium">1 IDR</span>
                                        <span>=</span>
                                        <span x-text="fmt(invRate, 8)"></span>
                                        <span class="text-gray-600">USD</span>
                                    </span>
                                </div>
                            </div>

                            {{-- Hidden real rate for backend --}}
                            <input type="hidden" name="frate" :value="rateUsdIdr">
                        </div>

                        <script>
                            function ratesForm() {
                                return {
                                    currency: @json(old('fcurrency', $tr_poh->fcurrency ?? 'IDR')),
                                    rateUsdIdr: Number(@json(old('frate', $tr_poh->frate ?? 15500))),

                                    get invRate() {
                                        return this.rateUsdIdr > 0 ? 1 / this.rateUsdIdr : 0;
                                    },

                                    fmt(n, dec = 2) {
                                        return Number(n || 0).toLocaleString('id-ID', {
                                            minimumFractionDigits: dec,
                                            maximumFractionDigits: dec
                                        });
                                    },

                                    applyDefaultIfNeeded() {
                                        if (this.currency === 'IDR' && (!this.rateUsdIdr || this.rateUsdIdr <= 0)) {
                                            this.rateUsdIdr = 15500;
                                        }
                                    }
                                }
                            }
                        </script>

                        <div class="lg:col-span-5">
                            <input id="fincludeppn" type="checkbox" name="fincludeppn" value="1"
                                x-model="includePPN" class="h-4 w-4 text-blue-600 border-gray-300 rounded"
                                {{ old('fincludeppn', $tr_poh->fincludeppn ?? 0) ? 'checked' : '' }}>
                            <label for="fincludeppn" class="ml-2 text-sm font-medium text-gray-700">
                                Harga Termasuk <span class="font-bold">PPN</span>
                            </label>
                        </div>

                        <div class="lg:col-span-12">
                            <label class="block text-sm font-medium">Keterangan</label>
                            <textarea name="fket" rows="3"
                                class="w-full border rounded px-3 py-2 @error('fket') border-red-500 @enderror"
                                placeholder="Tulis keterangan tambahan di sini...">{{ old('fket') }}</textarea>
                            @error('fket')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div x-data="itemsTable()" x-init="init();
                    recalcTotals()" class="mt-6 space-y-2">
                        <h3 class="text-base font-semibold text-gray-800">Detail Item</h3>

                        <div class="overflow-auto border rounded">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="p-2 text-left w-10">#</th>
                                        <th class="p-2 text-left w-40">Kode Produk</th>
                                        <th class="p-2 text-left w-72">Nama Produk</th>
                                        <th class="p-2 text-left w-28">Satuan</th>
                                        <th class="p-2 text-left w-36">Ref.PR#</th>
                                        <th class="p-2 text-right w-24 whitespace-nowrap">Qty</th>
                                        <th class="p-2 text-right w-28 whitespace-nowrap">Terima</th>
                                        <th class="p-2 text-right w-32 whitespace-nowrap">@ Harga</th>
                                        <th class="p-2 text-right w-24 whitespace-nowrap">Disc. %</th>
                                        <th class="p-2 text-right w-36 whitespace-nowrap">Total Harga</th>
                                        <th class="p-2 text-center w-28">Aksi</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <template x-for="(it, i) in savedItems" :key="it.uid">
                                        <!-- ROW UTAMA -->
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
                                            <td class="p-2" x-text="it.fsatuan"></td>
                                            <td class="p-2" x-text="it.fpono || '-'"></td>
                                            <td class="p-2 text-right" x-text="fmt(it.fqty)"></td>
                                            <td class="p-2 text-right" x-text="fmt(it.fterima)"></td>
                                            <td class="p-2 text-right" x-text="fmt(it.fprice)"></td>
                                            <td class="p-2 text-right" x-text="fmt(it.fdisc)"></td>
                                            <td class="p-2 text-right" x-text="fmt(it.ftotal)"></td>
                                            <td class="p-2 text-center">
                                                <div class="flex items-center justify-center gap-2 flex-wrap">
                                                    <button type="button" @click="edit(i)"
                                                        class="px-3 py-1 rounded text-xs bg-amber-100 text-amber-700 hover:bg-amber-200">Edit</button>
                                                    <button type="button" @click="removeSaved(i)"
                                                        class="px-3 py-1 rounded text-xs bg-red-100 text-red-600 hover:bg-red-200">Hapus</button>
                                                </div>
                                            </td>

                                            <!-- hidden inputs -->
                                            <td class="hidden">
                                                <input type="hidden" name="fitemcode[]" :value="it.fitemcode">
                                                <input type="hidden" name="fitemname[]" :value="it.fitemname">
                                                <input type="hidden" name="fsatuan[]" :value="it.fsatuan">
                                                <input type="hidden" name="frefdtno[]" :value="it.frefdtno">
                                                <input type="hidden" name="fnouref[]" :value="it.fnouref">
                                                <input type="hidden" name="frefpr[]" :value="it.frefpr">
                                                <input type="hidden" name="fqty[]" :value="it.fqty">
                                                <input type="hidden" name="fterima[]" :value="it.fterima">
                                                <input type="hidden" name="fprice[]" :value="it.fprice">
                                                <input type="hidden" name="fdisc[]" :value="it.fdisc">
                                                <input type="hidden" name="ftotal[]" :value="it.ftotal">
                                                <input type="hidden" name="fdesc[]" :value="it.fdesc">
                                                <input type="hidden" name="fketdt[]" :value="it.fketdt">
                                            </td>
                                        </tr>

                                        <!-- ROW DESC (di bawah Nama Produk) -->
                                        <tr class="border-b">
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                            <td class="p-2">
                                                <textarea x-model="it.fdesc" rows="2" class="w-full border rounded px-2 py-1"
                                                    placeholder="Deskripsi (opsional)"></textarea>
                                            </td>
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                        </tr>
                                    </template>

                                    <!-- ROW EDIT UTAMA -->
                                    <tr x-show="editingIndex !== null" class="border-t align-top" x-cloak>
                                        <!-- # -->
                                        <td class="p-2" x-text="(editingIndex ?? 0) + 1"></td>

                                        <!-- Kode Produk -->
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
                                                <a href="{{ route('product.create') }}" target="_blank" rel="noopener"
                                                    class="border border-l-0 rounded-r px-2 py-1 bg-white hover:bg-gray-50"
                                                    title="Tambah Produk">
                                                    <x-heroicon-o-plus class="w-4 h-4" />
                                                </a>
                                            </div>
                                        </td>

                                        <!-- Nama Produk (readonly) -->
                                        <td class="p-2">
                                            <input type="text"
                                                class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                :value="editRow.fitemname" disabled>
                                        </td>

                                        <!-- Satuan -->
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

                                        <!-- Ref.PR# -->
                                        <td class="p-2">
                                            <input type="text" class="w-full border rounded px-2 py-1"
                                                x-ref="editRefPr" x-model.trim="editRow.frefpr"
                                                @keydown.enter.prevent="$refs.editQty?.focus()" placeholder="Ref PR">
                                        </td>

                                        <!-- Qty -->
                                        <td class="p-2 text-right">
                                            <input type="number" class="border rounded px-2 py-1 w-24 text-right"
                                                min="0" step="1" x-ref="editQty"
                                                x-model.number="editRow.fqty" @input="recalc(editRow)"
                                                @keydown.enter.prevent="$refs.editTerima?.focus()">
                                        </td>

                                        <!-- Terima -->
                                        <td class="p-2 text-right">
                                            <input type="number" class="border rounded px-2 py-1 w-24 text-right"
                                                min="0" step="1" x-ref="editTerima"
                                                x-model.number="editRow.fterima" @input="recalc(editRow)"
                                                @keydown.enter.prevent="$refs.editPrice?.focus()">
                                        </td>

                                        <!-- @ Harga -->
                                        <td class="p-2 text-right">
                                            <input type="number" class="border rounded px-2 py-1 w-28 text-right"
                                                min="0" step="0.01" x-ref="editPrice"
                                                x-model.number="editRow.fprice" @input="recalc(editRow)"
                                                @keydown.enter.prevent="$refs.editDisc?.focus()">
                                        </td>

                                        <!-- Disc.% -->
                                        <td class="p-2 text-right">
                                            <input type="number" class="border rounded px-2 py-1 w-24 text-right"
                                                min="0" max="100" step="0.01" x-ref="editDisc"
                                                x-model.number="editRow.fdisc" @input="recalc(editRow)"
                                                @keydown.enter.prevent="applyEdit()">
                                        </td>

                                        <!-- Total Harga (readonly) -->
                                        <td class="p-2 text-right" x-text="fmt(editRow.ftotal)"></td>

                                        <!-- Aksi -->
                                        <td class="p-2 text-center">
                                            <div class="flex items-center justify-center gap-2 flex-wrap">
                                                <button type="button" @click="applyEdit()"
                                                    class="px-3 py-1 rounded text-xs bg-emerald-600 text-white">Simpan</button>
                                                <button type="button" @click="cancelEdit()"
                                                    class="px-3 py-1 rounded text-xs bg-gray-100">Batal</button>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- ROW EDIT DESC -->
                                    <tr x-show="editingIndex !== null" class="border-b" x-cloak>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                        <td class="p-2">
                                            <textarea x-model="editRow.fdesc" rows="2" class="w-full border rounded px-2 py-1"
                                                placeholder="Deskripsi (opsional)"></textarea>
                                        </td>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                    </tr>

                                    <!-- ROW DRAFT UTAMA -->
                                    <tr class="border-t align-top">
                                        <!-- # -->
                                        <td class="p-2" x-text="savedItems.length + 1"></td>

                                        <!-- Kode Produk -->
                                        <td class="p-2">
                                            <div class="flex">
                                                <input type="text" class="flex-1 border rounded-l px-2 py-1 font-mono"
                                                    x-ref="draftCode" x-model.trim="draft.fitemcode"
                                                    @input="onCodeTypedRow(draft)"
                                                    @keydown.enter.prevent="handleEnterOnCode('draft')">
                                                <button type="button" @click="openBrowseFor('draft')"
                                                    class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50"
                                                    title="Cari Produk">
                                                    <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                </button>
                                                <a href="{{ route('product.create') }}" target="_blank" rel="noopener"
                                                    class="border border-l-0 rounded-r px-2 py-1 bg-white hover:bg-gray-50"
                                                    title="Tambah Produk">
                                                    <x-heroicon-o-plus class="w-4 h-4" />
                                                </a>
                                            </div>
                                        </td>

                                        <!-- Nama Produk (readonly) -->
                                        <td class="p-2">
                                            <input type="text"
                                                class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                :value="draft.fitemname" disabled>
                                        </td>

                                        <!-- Satuan -->
                                        <td class="p-2">
                                            <template x-if="draft.units.length > 1">
                                                <select class="w-full border rounded px-2 py-1" x-ref="draftUnit"
                                                    x-model="draft.fsatuan"
                                                    @keydown.enter.prevent="$refs.draftRefPr?.focus()">
                                                    <template x-for="u in draft.units" :key="u">
                                                        <option :value="u" x-text="u"></option>
                                                    </template>
                                                </select>
                                            </template>
                                            <template x-if="draft.units.length <= 1">
                                                <input type="text"
                                                    class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                    :value="draft.fsatuan || '-'" disabled>
                                            </template>
                                        </td>

                                        <!-- Ref.PR# -->
                                        <td class="p-2">
                                            <input type="text" class="w-full border rounded px-2 py-1"
                                                x-ref="draftRefPr" x-model.trim="draft.frefpr"
                                                @keydown.enter.prevent="$refs.draftQty?.focus()" placeholder="Ref PR">
                                        </td>

                                        <!-- Qty -->
                                        <td class="p-2 text-right">
                                            <input type="number" class="border rounded px-2 py-1 w-24 text-right"
                                                min="0" step="1" x-ref="draftQty"
                                                x-model.number="draft.fqty" @input="recalc(draft)"
                                                @keydown.enter.prevent="$refs.draftTerima?.focus()">
                                        </td>

                                        <!-- Terima -->
                                        <td class="p-2 text-right">
                                            <input type="number" class="border rounded px-2 py-1 w-24 text-right"
                                                min="0" step="1" x-ref="draftTerima"
                                                x-model.number="draft.fterima" @input="recalc(draft)"
                                                @keydown.enter.prevent="$refs.draftPrice?.focus()">
                                        </td>

                                        <!-- @ Harga -->
                                        <td class="p-2 text-right">
                                            <input type="number" class="border rounded px-2 py-1 w-28 text-right"
                                                min="0" step="0.01" x-ref="draftPrice"
                                                x-model.number="draft.fprice" @input="recalc(draft)"
                                                @keydown.enter.prevent="$refs.draftDisc?.focus()">
                                        </td>

                                        <!-- Disc.% -->
                                        <td class="p-2 text-right">
                                            <input type="number" class="border rounded px-2 py-1 w-24 text-right"
                                                min="0" max="100" step="0.01" x-ref="draftDisc"
                                                x-model.number="draft.fdisc" @input="recalc(draft)"
                                                @keydown.enter.prevent="addIfComplete()">
                                        </td>

                                        <!-- Total Harga (readonly) -->
                                        <td class="p-2 text-right" x-text="fmt(draft.ftotal)"></td>

                                        <!-- Aksi -->
                                        <td class="p-2 text-center">
                                            <div class="flex items-center justify-center gap-2 flex-wrap">
                                                <button type="button" @click="addIfComplete()"
                                                    class="px-3 py-1 rounded text-xs bg-emerald-600 text-white">Tambah</button>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- ROW DRAFT DESC -->
                                    <tr class="border-b">
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                        <td class="p-2">
                                            <textarea x-model="draft.fdesc" rows="2" class="w-full border rounded px-2 py-1"
                                                placeholder="Deskripsi (opsional)"></textarea>
                                        </td>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- ===== Trigger: Add tr_prh dari panel kanan ===== -->
                        <!-- ===== Trigger: Add tr_prh dari panel kanan ===== -->
                        <div x-data="prhFormModal()">
                            <!-- Trigger: Add PR dari panel kanan -->
                            <div class="mt-3 flex justify-between items-start gap-4">
                                <div class="w-full flex justify-start mb-3">
                                    <!-- Button ini sekarang bisa akses openModal() -->
                                    <button type="button" @click="openModal()"
                                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                        Add PR
                                    </button>
                                </div>
                                <!-- Kanan: Panel Totals -->
                                <div class="w-1/2">
                                    <div class="rounded-lg border bg-gray-50 p-3 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-700">Total Harga</span>
                                            <span class="min-w-[140px] text-right font-medium"
                                                x-text="rupiah(totalHarga)"></span>
                                        </div>
                                        <div class="flex items-center justify-between gap-6">
                                            <!-- Checkbox -->
                                            <div class="flex items-center">
                                                <input id="fapplyppn" type="checkbox" name="fapplyppn" value="1"
                                                    x-model="includePPN"
                                                    class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                <label for="fapplyppn" class="ml-2 text-sm font-medium text-gray-700">
                                                    <span class="font-bold">PPN</span>
                                                </label>
                                            </div>

                                            <!-- Dropdown Include / Exclude (tengah) -->
                                            <div class="flex items-center gap-2">
                                                <select id="includePPN" name="includePPN" x-model.number="fapplyppn"
                                                    x-init="fapplyppn = 0" :disabled="!(includePPN || fapplyppn)"
                                                    class="w-28 h-9 px-2 text-sm leading-tight border rounded transition-opacity appearance-none
                                                           disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed">
                                                    <option value="0">Exclude</option>
                                                    <option value="1">Include</option>
                                                </select>
                                            </div>

                                            <!-- Input Rate + Nominal (kanan) -->
                                            <div class="flex items-center gap-2">
                                                <input type="number" min="0" max="100" step="0.01"
                                                    x-model.number="ppnRate" :disabled="!(includePPN || fapplyppn)"
                                                    class="w-20 h-9 px-2 text-sm leading-tight text-right border rounded transition-opacity
                                                            [appearance:textfield]
                                                            [&::-webkit-outer-spin-button]:appearance-none
                                                            [&::-webkit-inner-spin-button]:appearance-none
                                                            disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed">
                                                <span class="text-sm">%</span>
                                                <span class="min-w-[140px] text-right font-medium"
                                                    x-text="rupiah(ppnAmount)"></span>
                                            </div>

                                        </div>

                                        <div class="border-t my-1"></div>

                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-semibold text-gray-800">Grand Total</span>
                                            <span class="min-w-[140px] text-right text-lg font-semibold"
                                                x-text="rupiah(grandTotal)"></span>
                                        </div>

                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-semibold text-gray-800">Grand Total (RP)</span>
                                            <span class="min-w-[140px] text-right text-lg font-semibold"
                                                x-text="rupiah(grandTotal)"></span>
                                        </div>
                                    </div>

                                    <!-- Hidden inputs for submit -->
                                    <input type="hidden" name="famountponet" :value="totalHarga">
                                    <input type="hidden" name="" :value="ppnAmount">
                                    <input type="hidden" name="famountpo" :value="grandTotal">
                                    <input type="hidden" name="famountpopajak" :value="ppnRate">
                                </div>
                            </div>
                            <!-- Modal backdrop - sekarang bisa akses 'show' -->
                            <div x-show="show" x-transition.opacity class="fixed inset-0 z-40 bg-black/50"
                                @keydown.escape.window="closeModal()"></div>

                            {{-- MODAL PR dengan DataTables - HAPUS x-data di sini --}}
                            <div>
                                {{-- MODAL PR --}}
                                <div x-show="show" x-cloak x-transition.opacity
                                    class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-8"
                                    aria-modal="true" role="dialog">

                                    <div class="absolute inset-0 bg-black/40" @click="closeModal()"></div>

                                    <div class="relative w-full max-w-3xl rounded-xl bg-white shadow-xl">
                                        <div class="flex items-center justify-between border-b px-4 py-3">
                                            <h3 class="text-lg font-semibold">Add PR</h3>
                                            <button type="button" @click="closeModal()"
                                                class="text-gray-500 hover:text-gray-700">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>

                                        <div class="px-4 py-3">
                                            <table id="prTable" class="min-w-full text-sm display nowrap"
                                                style="width:100%">
                                                <thead class="bg-gray-100">
                                                    <tr>
                                                        <th class="p-2 text-left">PR No</th>
                                                        <th class="p-2 text-left">Supplier</th>
                                                        <th class="p-2 text-left">Tanggal</th>
                                                        <th class="p-2 text-right">Aksi</th>
                                                    </tr>
                                                </thead>
                                            </table>
                                        </div>

                                        <div class="flex justify-end gap-2 border-t px-4 py-3">
                                            <button type="button" @click="closeModal()"
                                                class="rounded bg-gray-200 px-4 py-2 text-sm font-medium hover:bg-gray-300">
                                                Kembali
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Modal Duplikasi --}}
                                <div x-show="showDupModal" x-cloak x-transition.opacity
                                    class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                                    <div class="absolute inset-0 bg-black/40" @click="closeDupModal()"></div>
                                    <div class="relative bg-white rounded-xl shadow-xl max-w-2xl w-full p-6">
                                        <h3 class="text-lg font-semibold mb-4">Peringatan Duplikasi</h3>
                                        <p class="mb-4">
                                            Ditemukan <strong x-text="dupCount"></strong> item yang sudah ada dalam daftar.
                                            Hanya item unik yang akan ditambahkan.
                                        </p>

                                        <div class="mb-4 max-h-48 overflow-auto border rounded p-2 bg-gray-50"
                                            x-show="dupSample.length > 0">
                                            <p class="text-sm font-medium mb-2">Contoh item duplikat:</p>
                                            <template x-for="(item, idx) in dupSample" :key="idx">
                                                <div class="text-xs py-1">
                                                    • <span x-text="item.fitemcode"></span> - <span
                                                        x-text="item.frefdtno"></span>
                                                </div>
                                            </template>
                                        </div>

                                        <div class="flex justify-end gap-2">
                                            <button type="button" @click="closeDupModal()"
                                                class="rounded bg-gray-200 px-4 py-2 text-sm font-medium hover:bg-gray-300">
                                                Batal
                                            </button>
                                            <button type="button" @click="confirmAddUniques()"
                                                class="rounded bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                                                Tambahkan Item Unik
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- MODAL DESC (di dalam itemsTable) -->
                        <div x-show="showDescModal" x-cloak class="fixed inset-0 z-[95] flex items-center justify-center"
                            x-transition.opacity>
                            <div class="absolute inset-0 bg-black/50" @click="closeDesc()"></div>

                            <div class="relative bg-white w-[92vw] max-w-lg rounded-2xl shadow-2xl overflow-hidden"
                                x-transition.scale>
                                <div class="px-5 py-4 border-b flex items-center">
                                    <x-heroicon-o-document-text class="w-6 h-6 text-blue-600 mr-2" />
                                    <h3 class="text-lg font-semibold text-gray-800">Isi Deskripsi Item</h3>
                                </div>

                                <div class="px-5 py-4 space-y-2">
                                    <label class="block text-sm text-gray-700">Deskripsi</label>
                                    <textarea x-model="descValue" rows="5" class="w-full border rounded px-3 py-2"
                                        placeholder="Tulis deskripsi item di sini..."></textarea>
                                </div>

                                <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
                                    <button type="button" @click="closeDesc()"
                                        class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">
                                        Batal
                                    </button>
                                    <button type="button" @click="applyDesc()"
                                        class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
                                        Simpan
                                    </button>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" id="itemsCount" :value="savedItems.length">
                    </div>

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
                                    Anda belum menambahkan item apa pun pada tabel. Silakan isi baris “Detail Item” terlebih
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

                    {{-- MODAL SUPPLIER --}}
                    <div x-data="supplierBrowser()" x-show="open" x-cloak x-transition.opacity
                        class="fixed inset-0 z-50 flex items-center justify-center">
                        <div class="absolute inset-0 bg-black/40" @click="close()"></div>
                        <div
                            class="relative bg-white rounded-2xl shadow-xl w-[90vw] **max-w-7xl** max-h-[90vh] flex flex-col">
                            <div class="p-4 border-b flex items-center gap-3">
                                <h3 class="text-lg font-semibold">Browse Supplier</h3>
                                <button type="button" @click="close()"
                                    class="ml-auto px-3 py-1 rounded border bg-gray-100 hover:bg-gray-200">Close</button>
                            </div>
                            <div class="p-4 overflow-auto flex-1">
                                <table id="supplierBrowseTable" class="min-w-full text-sm display">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="text-left p-2">Kode</th>
                                            <th class="text-left p-2">Nama Supplier</th>
                                            <th class="text-left p-2">Alamat</th>
                                            <th class="text-left p-2">Telepon</th>
                                            <th class="text-center p-2">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- DataTables akan mengisi ini -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- MODAL PRODUK --}}
                    <div x-data="productBrowser()" x-show="open" x-cloak x-transition.opacity
                        class="fixed inset-0 z-50 flex items-center justify-center">
                        <div class="absolute inset-0 bg-black/40" @click="close()"></div>
                        <div
                            class="relative bg-white rounded-2xl shadow-xl w-[90vw] **max-w-7xl** max-h-[90vh] flex flex-col">
                            <div class="p-4 border-b flex items-center gap-3">
                                <h3 class="text-lg font-semibold">Browse Produk</h3>
                                <button type="button" @click="close()"
                                    class="ml-auto px-3 py-1 rounded border bg-gray-100 hover:bg-gray-200">
                                    Close
                                </button>
                            </div>
                            <div class="p-4 overflow-auto flex-1">
                                <table id="productTable" class="min-w-full text-sm display nowrap" style="width:100%">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="text-left p-2">Kode</th>
                                            <th class="text-left p-2">Nama</th>
                                            <th class="text-left p-2">Satuan</th>
                                            <th class="text-center p-2">Stock</th>
                                            <th class="text-center p-2">Aksi</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>

                    @php
                        $canApproval = in_array('approvalpr', explode(',', session('user_restricted_permissions', '')));
                    @endphp


                    {{-- APPROVAL & ACTIONS --}}
                    <div class="md:col-span-2 flex justify-center items-center space-x-2 mt-6">
                        @if ($canApproval)
                            <label class="block text-sm font-medium">Approval</label>

                            {{-- fallback 0 saat checkbox tidak dicentang --}}
                            <input type="hidden" name="fapproval" value="0">

                            <label class="switch">
                                <input type="checkbox" name="fapproval" id="approvalToggle" value="1"
                                    {{ old('fapproval', session('fapproval') ? 1 : 0) ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        @endif
                    </div>

                    <div class="mt-8 flex justify-center gap-4">
                        <button type="submit"
                            class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                            <x-heroicon-o-check class="w-5 h-5 mr-2" /> Simpan
                        </button>
                        <button type="button" @click="window.location.href='{{ route('tr_poh.index') }}'"
                            class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                            <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" /> Keluar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
@endpush
<style>
    /* Targeting lebih spesifik untuk length select */
    div#supplierBrowseTable_length select,
    .dataTables_wrapper #supplierBrowseTable_length select,
    table#supplierBrowseTable+.dataTables_wrapper .dataTables_length select {
        min-width: 140px !important;
        width: auto !important;
        padding: 8px 45px 8px 16px !important;
        font-size: 14px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.375rem !important;
    }

    /* Wrapper length */
    div#supplierBrowseTable_length,
    .dataTables_wrapper #supplierBrowseTable_length,
    .dataTables_wrapper .dataTables_length {
        min-width: 250px !important;
    }

    /* Label wrapper */
    div#supplierBrowseTable_length label,
    .dataTables_wrapper #supplierBrowseTable_length label,
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
                                q: d.search.value,
                                page: (d.start / d.length) + 1,
                                per_page: d.length,
                                draw: d.draw // ✅ Tambahkan draw
                            };
                        },
                        dataSrc: function(json) {
                            // ✅ PERBAIKAN: Return json langsung, bukan hanya data
                            return json.data || [];
                        }
                    },
                    columns: [{
                            data: 'fsuppliercode',
                            name: 'fsuppliercode',
                            width: '15%'
                        },
                        {
                            data: 'fsuppliername',
                            name: 'fsuppliername',
                            width: '20%'
                        },
                        {
                            data: 'faddress',
                            name: 'faddress',
                            defaultContent: '-',
                            orderable: false,
                            width: '30%'
                        },
                        {
                            data: 'ftelp',
                            name: 'ftelp',
                            defaultContent: '-',
                            orderable: false,
                            width: '20%'
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center',
                            width: '20%',
                            render: function(data, type, row) {
                                // ✅ PERBAIKAN: Escape quotes untuk mencegah error
                                const code = (row.fsuppliercode || '').replace(/'/g, "\\'");
                                const name = (row.fsuppliername || '').replace(/'/g, "\\'");
                                const address = (row.faddress || '').replace(/'/g, "\\'");
                                const telp = (row.ftelp || '').replace(/'/g, "\\'");

                                return `<button type="button" 
                                onclick="window.chooseSupplier('${row.fsupplierid}', '${code}', '${name}', '${address}', '${telp}')" 
                                class="px-3 py-1 rounded text-xs bg-emerald-600 hover:bg-emerald-700 text-white">
                                Pilih
                            </button>`;
                            }
                        }
                    ],
                    pageLength: 10,
                    lengthMenu: [10, 25, 50, 100],
                    order: [
                        [0, 'asc']
                    ],

                    // ✅ HILANGKAN scrollX, biarkan full width
                    scrollX: false,
                    autoWidth: true,

                    dom: '<"flex justify-between items-center mb-4"f<"ml-auto"l>>rtip',

                    language: {
                        processing: "Memuat...",
                        search: "Cari:",
                        lengthMenu: "Tampilkan _MENU_ data",
                        info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                        infoEmpty: "Menampilkan 0 data",
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
                    ], // Sort by nama
                    autoWidth: false,
                    initComplete: function() {
                        const api = this.api();
                        const $container = $(api.table().container());

                        // Lebarkan search input
                        $container.find('.dt-search .dt-input, .dataTables_filter input').css({
                            width: '400px',
                            maxWidth: '100%',
                            minWidth: '300px'
                        });

                        $container.find('.dt-search, .dataTables_filter').css({
                            minWidth: '420px'
                        });

                        // Adjust kolom
                        api.columns.adjust().draw(false);
                    }
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
                    this.dataTable.destroy();
                    this.dataTable = null;
                }
            },

            init() {
                window.chooseSupplier = (id, code, name, address, telp) => {
                    const sel = document.getElementById('modal_filter_supplier_id');
                    const hid = document.getElementById('supplierCodeHidden');

                    if (!sel) {
                        this.close();
                        return;
                    }

                    let opt = [...sel.options].find(o => o.value == String(id));
                    const label = `${name} (${code})`;

                    if (!opt) {
                        opt = new Option(label, id, true, true);
                        sel.add(opt);
                    } else {
                        opt.text = label;
                        opt.selected = true;
                    }

                    sel.dispatchEvent(new Event('change'));
                    if (hid) hid.value = id;
                    this.close();
                };

                window.addEventListener('supplier-browse-open', () => this.openBrowse(), {
                    passive: true
                });
            }
        }
    }

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

    function itemsTable() {
        return {
            showNoItems: false,
            savedItems: @json($savedItems ?? []),
            draft: newRow(),
            editingIndex: null,
            editRow: newRow(),

            totalHarga: 0,
            ppnRate: 11,
            grandTotal: @json($famountpo ?? 0),

            initialGrandTotal: @json($famountpo ?? 0),
            initialPpnAmount: @json($famountpopajak ?? 0),

            get ppnAmount() {
                if (!this.includePPN) return 0;
                const total = +this.totalHarga || 0;
                const rate = +this.ppnRate || 0;
                return Math.round(total * rate / 100);
            },
            get grandTotal() {
                if (!this.includePPN) {
                    return this.initialGrandTotal; // pakai nilai lama dari DB
                }
                const total = +this.totalHarga || 0;
                return total + this.ppnAmount; // hitung baru kalau PPN aktif
            },

            fmtMoney(value) {
                return this.fmt(value); // format uang
            },

            fmt(n) {
                if (n === null || n === undefined || n === '') return '-';
                const v = Number(n);
                if (!isFinite(v)) return '-';

                // Jika angka adalah bulat, hilangkan desimal
                if (Number.isInteger(v)) {
                    return v.toLocaleString('id-ID'); // Format sebagai angka bulat
                } else {
                    return v.toLocaleString('id-ID', {
                        style: 'currency',
                        currency: 'IDR'
                    }); // Jika angka desimal, tampilkan dalam format mata uang
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

            recalc(row) {
                row.fqty = Math.max(0, +row.fqty || 0);
                row.fterima = Math.max(0, +row.fterima || 0);
                row.fprice = Math.max(0, +row.fprice || 0);
                row.fdisc = Math.min(100, Math.max(0, +row.fdisc || 0));
                row.ftotal = +(row.fqty * row.fprice * (1 - row.fdisc / 100)).toFixed(2);
                this.recalcTotals();
            },

            recalcTotals() {
                this.totalHarga = this.savedItems.reduce((sum, it) => sum + (+it.ftotal || 0), 0);
            },

            calculatePPN() {
                this.ppnAmount = this.includePPN ? (this.totalHarga * (this.ppnRate / 100)) : 0;
                this.grandTotal = this.totalHarga + this.ppnAmount;
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
                const existing = new Set(this.getCurrentItemKeys()); // gunakan helper

                let added = 0,
                    duplicates = [];

                items.forEach(src => {
                    const row = {
                        uid: cryptoRandom(),
                        fitemcode: src.fitemcode ?? '',
                        fitemname: src.fitemname ?? '',
                        fsatuan: (src.fsatuan ?? ''),
                        frefdtno: src.frefdtno ?? '',
                        fnouref: src.fnouref ?? '',
                        frefpr: src.frefpr ?? '',
                        fpono: header?.fpono ?? src.fpono ?? '',
                        fprnoid: src.fprnoid ?? header?.fprnoid ?? '',
                        fqty: Number(src.fqty ?? 0),
                        fterima: Number(src.ferima ?? 0),
                        fprice: Number(src.fprice ?? 0),
                        fdisc: Number(src.fdisc ?? 0),
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

                const dupe = this.savedItems.find(it => it.fitemcode === r.fitemcode && it.fsatuan === r.fsatuan && (it
                    .frefpr || '') === (r.frefpr || ''));
                if (dupe) {
                    alert('Item sama sudah ada.');
                    return;
                }

                this.savedItems.push({
                    ...r,
                    uid: cryptoRandom()
                });
                this.showNoItems = false;
                this.resetDraft(); // Reset draft setelah item ditambahkan
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

            removeSaved(i) {
                this.savedItems.splice(i, 1);
                this.syncDescList?.();
            },

            resetDraft() {
                this.draft = newRow();
            },

            onSubmit($event) {
                if (this.savedItems.length === 0) {
                    $event.preventDefault();
                    this.showNoItems = true;
                    return;
                }
            },

            // Handle enter for navigating fields (similar to your current logic)
            handleEnterOnCode(where) {
                if (where === 'edit') {
                    if (this.editRow.units.length > 1) this.$refs.editUnit?.focus();
                    else this.$refs.editQty?.focus();
                } else {
                    if (this.draft.units.length > 1) this.$refs.draftUnit?.focus();
                    else this.$refs.draftQty?.focus();
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
                this.$watch('includePPN', () => this.recalcTotals());
                this.$watch('ppnRate', () => this.recalcTotals());

                window.getCurrentItemKeys = () => this.getCurrentItemKeys();
                window.addEventListener('pr-picked', this.onPrPicked.bind(this), {
                    passive: true
                });

                // Listen for product picked from product modal
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
                this.$watch('ppnAmount', () => {
                    const ppnValue = (+this.totalHarga || 0) * (+this.ppnAmount || 0) / 100;
                    this.grandTotal = (+this.totalHarga || 0) + ppnValue;
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
                fnouref: '',
                frefpr: '',
                fqty: 0,
                fterima: 0,
                fprice: 0,
                fdisc: 0,
                ftotal: 0,
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

<script>
    window.prhFormModal = function() {
        return {
            show: false,
            table: null,

            // Duplikasi modal
            showDupModal: false,
            dupCount: 0,
            dupSample: [],
            pendingHeader: null,
            pendingUniques: [],

            initDataTable() {
                if (this.table) {
                    this.table.destroy();
                }

                this.table = $('#prTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('tr_poh.pickable') }}",
                        type: 'GET',
                        data: function(d) {
                            return {
                                draw: d.draw,
                                page: (d.start / d.length) + 1,
                                per_page: d.length,
                                search: d.search.value
                            };
                        },
                        dataSrc: function(json) {
                            return json.data;
                        }
                    },
                    columns: [{
                            data: 'fprno',
                            name: 'fprno'
                        },
                        {
                            data: 'fsupplier',
                            name: 'fsupplier',
                            render: function(data) {
                                return data || '-';
                            }
                        },
                        {
                            data: 'fprdate',
                            name: 'fprdate',
                            render: function(data) {
                                return formatDate(data);
                            }
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-right',
                            render: function(data, type, row) {
                                return '<button type="button" class="btn-pick inline-flex items-center gap-1 rounded bg-emerald-600 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-emerald-700">Pilih</button>';
                            }
                        }
                    ],
                    pageLength: 10,
                    lengthMenu: [
                        [10, 25, 50],
                        [10, 25, 50]
                    ],
                    dom: '<"flex justify-between items-center mb-4"f<"ml-auto"l>>rtip',

                    language: {
                        processing: "Memuat...",
                        search: "Cari:",
                        lengthMenu: "Tampilkan _MENU_ data",
                        info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                        infoEmpty: "Menampilkan 0 data",
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
                        [2, 'desc']
                    ], // Sort by tanggal terbaru
                    autoWidth: false,
                    initComplete: function() {
                        const api = this.api();
                        const $container = $(api.table().container());

                        // Lebarkan search input
                        $container.find('.dt-search .dt-input, .dataTables_filter input').css({
                            width: '400px',
                            maxWidth: '100%',
                            minWidth: '300px'
                        });

                        // Opsional: lebarkan wrapper search juga
                        $container.find('.dt-search, .dataTables_filter').css({
                            minWidth: '420px'
                        });
                    }
                });

                // Handle button click
                const self = this;
                $('#prTable').on('click', '.btn-pick', function() {
                    const data = self.table.row($(this).closest('tr')).data();
                    self.pick(data);
                });
            },

            openModal() {
                this.show = true;
                this.$nextTick(() => {
                    this.initDataTable();
                });
            },

            closeModal() {
                this.show = false;
                if (this.table) {
                    this.table.search('').draw();
                }
            },

            // Duplikasi handlers
            openDupModal(header, duplicates, uniques) {
                this.dupCount = duplicates.length;
                this.dupSample = duplicates.slice(0, 6);
                this.pendingHeader = header;
                this.pendingUniques = uniques;
                this.showDupModal = true;
            },

            closeDupModal() {
                this.showDupModal = false;
                this.dupCount = 0;
                this.dupSample = [];
                this.pendingHeader = null;
                this.pendingUniques = [];
            },

            confirmAddUniques() {
                window.dispatchEvent(new CustomEvent('pr-picked', {
                    detail: {
                        header: this.pendingHeader,
                        items: this.pendingUniques
                    }
                }));
                this.closeDupModal();
                this.closeModal();
            },

            async pick(row) {
                try {
                    const url = `{{ route('tr_poh.items', ['id' => 'PR_ID_PLACEHOLDER']) }}`
                        .replace('PR_ID_PLACEHOLDER', row.fprid);

                    const res = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const json = await res.json();

                    const items = json.items || [];
                    const currentKeys = new Set((window.getCurrentItemKeys?.() || []).map(String));

                    const keyOf = (src) =>
                        `${(src.fitemcode ?? '').toString().trim()}::${(src.frefdtno ?? '').toString().trim()}`;

                    const duplicates = items.filter(src => currentKeys.has(keyOf(src)));
                    const uniques = items.filter(src => !currentKeys.has(keyOf(src)));

                    if (duplicates.length > 0) {
                        this.openDupModal(row, duplicates, uniques);
                        return;
                    }

                    // Tidak ada duplikat
                    window.dispatchEvent(new CustomEvent('pr-picked', {
                        detail: {
                            header: row,
                            items
                        }
                    }));

                    this.closeModal();
                } catch (e) {
                    console.error(e);
                    alert('Gagal mengambil detail PR');
                }
            }
        };
    };

    // Helper function untuk format tanggal
    function formatDate(s) {
        if (!s || s === 'No Date') return '-';
        const d = new Date(s);
        if (isNaN(d)) return '-';
        const pad = n => n.toString().padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }
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
                                // DataTables mengirim parameter search[value] untuk pencarian
                                return {
                                    draw: d.draw,
                                    page: (d.start / d.length) + 1,
                                    per_page: d.length,
                                    q: d.search.value
                                };
                            },
                            dataSrc: function(json) {
                                // Mapping response ke format DataTables
                                return json.data;
                            }
                        },
                        columns: [{
                                data: 'fprdcode',
                                name: 'fprdcode',
                                className: 'font-mono'
                            },
                            {
                                data: 'fprdname',
                                name: 'fprdname'
                            },
                            {
                                data: 'fsatuanbesar',
                                name: 'fsatuanbesar',
                                render: function(data) {
                                    return data || '-';
                                }
                            },
                            {
                                data: 'fminstock',
                                name: 'fminstock',
                                className: 'text-center'
                            },
                            {
                                data: null,
                                orderable: false,
                                searchable: false,
                                className: 'text-center',
                                render: function(data, type, row) {
                                    return '<button type="button" class="btn-choose px-3 py-1 rounded text-xs bg-emerald-600 hover:bg-emerald-700 text-white">Pilih</button>';
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
                            processing: "Memuat...",
                            search: "Cari:",
                            lengthMenu: "Tampilkan _MENU_ data",
                            info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                            infoEmpty: "Menampilkan 0 data",
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
                        ], // Sort by nama
                        autoWidth: false,
                        initComplete: function() {
                            const api = this.api();
                            const $container = $(api.table().container());

                            // Lebarkan search input
                            $container.find('.dt-search .dt-input, .dataTables_filter input').css({
                                width: '400px',
                                maxWidth: '100%',
                                minWidth: '300px'
                            });

                            // Opsional: lebarkan wrapper search juga
                            $container.find('.dt-search, .dataTables_filter').css({
                                minWidth: '420px'
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
