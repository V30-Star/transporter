@extends('layouts.app')

@section('title', 'Permintaan Order')

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
        <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1600px] w-full mx-auto">
            <form action="{{ route('tr_prh.store') }}" method="POST" class="mt-6" x-data="{ showNoItems: false }"
                @submit.prevent="
        const n = Number(document.getElementById('itemsCount')?.value || 0);
        if (n < 1) { showNoItems = true } else { $el.submit() }
      ">
                @csrf

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
                            <input type="text" name="fprno" class="w-full border rounded px-3 py-2"
                                :disabled="autoCode" :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'">
                            <label class="inline-flex items-center select-none">
                                <input type="checkbox" x-model="autoCode" checked>
                                <span class="ml-2 text-sm text-gray-700">Auto</span>
                            </label>
                        </div>
                    </div>

                    <div class="lg:col-span-4">
                        <label class="block text-sm font-medium mb-1">Supplier</label>
                        <div class="flex">
                            <div class="relative flex-1">
                                <select id="supplierSelect" name="fsupplier_view"
                                    class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
                                    disabled>
                                    <option value=""></option>
                                    @foreach ($supplier as $suppliers)
                                        <option value="{{ $suppliers->fsupplierid }}"
                                            {{ old('fsupplier') == $suppliers->fsupplierid ? 'selected' : '' }}>
                                            {{ $suppliers->fsuppliercode }} - {{ $suppliers->fsuppliername }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="absolute inset-0" role="button" aria-label="Browse supplier"
                                    @click="window.dispatchEvent(new CustomEvent('supplier-browse-open'))"></div>
                            </div>
                            <input type="hidden" name="fsupplier" id="supplierCodeHidden" value="{{ old('fsupplier') }}">
                            <button type="button" @click="window.dispatchEvent(new CustomEvent('supplier-browse-open'))"
                                class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                                title="Browse Supplier">
                                <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                            </button>
                            <a href="{{ route('supplier.create') }}" target="_blank" rel="noopener"
                                class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50" title="Tambah Supplier">
                                <x-heroicon-o-plus class="w-5 h-5" />
                            </a>
                        </div>
                        @error('fsupplier')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="lg:col-span-4">
                        <label class="block text-sm font-medium">Tanggal</label>
                        <input type="date" name="fprdate" value="{{ old('fprdate') ?? date('Y-m-d') }}"
                            class="w-full border rounded px-3 py-2 @error('fprdate') border-red-500 @enderror">
                        @error('fprdate')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="lg:col-span-4">
                        <label class="block text-sm font-medium">Tgl. Kirim</label>
                        <input type="date" name="fneeddate" value="{{ old('fneeddate', '') }}"
                            class="w-full border rounded px-3 py-2 @error('fneeddate') border-red-500 @enderror">
                        @error('fneeddate')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="lg:col-span-4">
                        <label class="block text-sm font-medium mb-1">Tempo</label>
                        <div class="flex items-center">
                            <input type="number" name="fduedate" value="{{ old('fduedate', 0) }}"
                                class="w-full border rounded px-3 py-2 @error('fduedate') border-red-500 @enderror">
                            <span class="ml-2">Hari</span>
                        </div>
                        @error('fduedate')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="lg:col-span-4">
                        <input id="ppn" type="checkbox" name="ppn" value="1"
                            class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                        <label for="ppn" class="ml-2 text-sm font-medium text-gray-700">
                            Harga Termasuk <span class="font-bold">PPN</span>
                        </label>
                    </div>

                    <div class="lg:col-span-12">
                        <label class="block text-sm font-medium">Keterangan</label>
                        <textarea name="fket" rows="3" class="w-full border rounded px-3 py-2 @error('fket') border-red-500 @enderror"
                            placeholder="Tulis keterangan tambahan di sini...">{{ old('fket') }}</textarea>
                        @error('fket')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- DETAIL ITEM (tabel input) --}}
                <div x-data="itemsTable()" x-init="init()" class="mt-6 space-y-2">
                    <h3 class="text-base font-semibold text-gray-800">Detail Item</h3>

                    <div class="overflow-auto border rounded">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="p-2 text-left w-10">#</th>
                                    <th class="p-2 text-left w-44">Kode Produk</th>
                                    <th class="p-2 text-left">Nama Produk</th>
                                    <th class="p-2 text-left w-40">Satuan</th>
                                    <th class="p-2 text-left w-56">Ref.PR#</th>
                                    <th class="p-2 text-right w-28">Qty</th>
                                    <th class="p-2 text-right w-28">Terima</th>
                                    <th class="p-2 text-right w-28">@ Harga</th>
                                    <th class="p-2 text-right w-28">Disc. %</th>
                                    <th class="p-2 text-right w-32">Total Harga</th>
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
                                        <td class="p-2" x-text="it.frefpr || '-'"></td>
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
                                <tr x-show="editingIndex !== null" class="border-t bg-green-50 align-top" x-cloak>
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
                                        <input type="text" class="w-full border rounded px-2 py-1" x-ref="editRefPr"
                                            x-model.trim="editRow.frefpr" @keydown.enter.prevent="$refs.editQty?.focus()"
                                            placeholder="Ref PR">
                                    </td>

                                    <!-- Qty -->
                                    <td class="p-2 text-right">
                                        <input type="number" class="border rounded px-2 py-1 w-24 text-right"
                                            min="0" step="1" x-ref="editQty" x-model.number="editRow.fqty"
                                            @input="recalc(editRow)" @keydown.enter.prevent="$refs.editTerima?.focus()">
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
                                <tr x-show="editingIndex !== null" class="bg-green-50 border-b" x-cloak>
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
                                <tr class="border-t bg-green-50 align-top">
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
                                        <input type="text" class="w-full border rounded px-2 py-1" x-ref="draftRefPr"
                                            x-model.trim="draft.frefpr" @keydown.enter.prevent="$refs.draftQty?.focus()"
                                            placeholder="Ref PR">
                                    </td>

                                    <!-- Qty -->
                                    <td class="p-2 text-right">
                                        <input type="number" class="border rounded px-2 py-1 w-24 text-right"
                                            min="0" step="1" x-ref="draftQty" x-model.number="draft.fqty"
                                            @input="recalc(draft)" @keydown.enter.prevent="$refs.draftTerima?.focus()">
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
                                <tr class="bg-green-50 border-b">
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
                    <div class="mt-3 grid grid-cols-12 gap-4 items-start">
                        <!-- Kiri: pakai area Deskripsi yang sudah ada -->
                        <div class="col-span-8">
                            <!-- biarkan textarea Deskripsi milikmu di sini -->
                            <!-- contoh:
                <label class="block text-sm font-medium mb-1">Deskripsi</label>
                <textarea x-model="formDesc" rows="4" class="w-full border rounded px-3 py-2"></textarea>
                -->
                        </div>

                        <!-- Kanan: panel totals -->
                        <div class="col-span-4">
                            <div class="rounded-lg border bg-gray-50 p-3 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-700">Total Harga</span>
                                    <span class="min-w-[140px] text-right font-medium" x-text="fmtMoney(subtotal)"></span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <label class="text-sm text-gray-700">PPN</label>
                                    <div class="flex items-center gap-2">
                                        <input type="number" min="0" max="100" step="0.01"
                                            x-model.number="ppnRate" class="w-20 border rounded px-2 py-1 text-right" />
                                        <span class="text-sm">%</span>
                                        <span class="min-w-[140px] text-right font-medium"
                                            x-text="fmtMoney(ppnAmount)"></span>
                                    </div>
                                </div>

                                <div class="border-t my-1"></div>

                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-gray-800">Grand Total</span>
                                    <span class="min-w-[140px] text-right text-lg font-semibold"
                                        x-text="fmtMoney(grandTotal)"></span>
                                </div>
                            </div>

                            <!-- (opsional) hidden inputs untuk submit -->
                            <input type="hidden" name="subtotal" :value="subtotal">
                            <input type="hidden" name="ppn_rate" :value="ppnRate">
                            <input type="hidden" name="ppn_amount" :value="ppnAmount">
                            <input type="hidden" name="grand_total" :value="grandTotal">
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
                <div x-show="showNoItems" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center"
                    x-transition.opacity>
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
                    <div class="relative bg-white rounded-2xl shadow-xl w-[92vw] max-w-4xl max-h-[85vh] flex flex-col">
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
                            <div class="text-sm text-gray-600"><span
                                    x-text="`Page ${page} / ${lastPage} • Total ${total}`"></span></div>
                            <div class="ml-auto flex items-center gap-2">
                                <button type="button" @click="prev()" :disabled="page <= 1"
                                    class="px-3 py-1 rounded border"
                                    :class="page <= 1 ? 'bg-gray-200 text-gray-400 cursor-not-allowed' :
                                        'bg-gray-100 hover:bg-gray-200'">Prev</button>
                                <button type="button" @click="next()" :disabled="page >= lastPage"
                                    class="px-3 py-1 rounded border"
                                    :class="page >= lastPage ? 'bg-gray-200 text-gray-400 cursor-not-allowed' :
                                        'bg-gray-100 hover:bg-gray-200'">Next</button>
                                <button type="button" @click="close()"
                                    class="px-3 py-1 rounded border bg-gray-100 hover:bg-gray-200">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- MODAL PRODUK --}}
                <div x-data="productBrowser()" x-show="open" x-cloak x-transition.opacity
                    class="fixed inset-0 z-50 flex items-center justify-center">
                    <div class="absolute inset-0 bg-black/40" @click="close()"></div>
                    <div class="relative bg-white rounded-2xl shadow-xl w-[92vw] max-w-5xl max-h-[85vh] flex flex-col">
                        <div class="p-4 border-b flex items-center gap-3">
                            <h3 class="text-lg font-semibold">Browse Produk</h3>
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
                                    <template x-for="p in rows" :key="p.fprdcode">
                                        <tr class="border-b hover:bg-gray-50">
                                            <td class="p-2 font-mono" x-text="p.fprdcode"></td>
                                            <td class="p-2" x-text="p.fprdname"></td>
                                            <td class="p-2">
                                                <span x-text="p.fsatuanbesar || '-'"></span>
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
                            <div class="text-sm text-gray-600"><span
                                    x-text="`Page ${page} / ${lastPage} • Total ${total}`"></span></div>
                            <div class="ml-auto flex items-center gap-2">
                                <button type="button" @click="prev()" :disabled="page <= 1"
                                    class="px-3 py-1 rounded border"
                                    :class="page <= 1 ? 'bg-gray-200 text-gray-400 cursor-not-allowed' :
                                        'bg-gray-100 hover:bg-gray-200'">Prev</button>
                                <button type="button" @click="next()" :disabled="page >= lastPage"
                                    class="px-3 py-1 rounded border"
                                    :class="page >= lastPage ? 'bg-gray-200 text-gray-400 cursor-not-allowed' :
                                        'bg-gray-100 hover:bg-gray-200'">Next</button>
                                <button type="button" @click="close()"
                                    class="px-3 py-1 rounded border bg-gray-100 hover:bg-gray-200">Close</button>
                            </div>
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
                    <button type="button" @click="window.location.href='{{ route('tr_prh.index') }}'"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" /> Keluar
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection

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
            keyword: '',
            page: 1,
            lastPage: 1,
            perPage: 10,
            total: 0,
            rows: [],
            apiUrl() {
                const u = new URL("{{ route('suppliers.browse') }}", window.location.origin);
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
                const hid = document.getElementById('supplierCodeHidden');
                if (!sel) {
                    this.close();
                    return;
                }
                let opt = [...sel.options].find(o => o.value == String(s.fsupplierid));
                const label = `${s.fsuppliercode} - ${s.fsuppliername}`;
                if (!opt) {
                    opt = new Option(label, s.fsupplierid, true, true);
                    sel.add(opt);
                } else {
                    opt.text = label;
                    opt.selected = true;
                }
                sel.dispatchEvent(new Event('change'));
                if (hid) hid.value = s.fsuppliercode;
                this.close();
            },
            init() {
                window.addEventListener('supplier-browse-open', () => this.openBrowse(), {
                    passive: true
                });
            }
        }
    }

    // Modal produk
    function productBrowser() {
        return {
            open: false,
            forEdit: false,
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
                    this.rows = [];
                    this.page = 1;
                    this.lastPage = 1;
                    this.total = 0;
                }
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
                        product: p,
                        forEdit: this.forEdit
                    }
                }));
                this.close();
            },
            init() {
                window.addEventListener('browse-open', (e) => {
                    this.open = true;
                    this.forEdit = !!(e.detail && e.detail.forEdit);
                    this.page = 1;
                    this.fetch();
                }, {
                    passive: true
                });
            },
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

    // Tabel inline
    function itemsTable() {
        return {
            savedItems: [],

            draft: newRow(),
            editingIndex: null,
            editRow: newRow(),

            // ===== Helpers =====
            fmt(n) { // format angka sederhana
                if (n === null || n === undefined || n === '') return '-';
                const v = Number(n);
                if (!isFinite(v)) return '-';
                return v.toLocaleString(); // ganti sesuai kebutuhan (IDR dll)
            },

            recalc(row) {
                // Normalisasi & guard rails
                row.fqty = Math.max(0, +row.fqty || 0);
                row.fterima = Math.max(0, +row.fterima || 0);
                row.fprice = Math.max(0, +row.fprice || 0);
                row.fdisc = Math.min(100, Math.max(0, +row.fdisc || 0));
                // Total berdasarkan QTY (bukan "Terima")
                row.ftotal = +(row.fqty * row.fprice * (1 - row.fdisc / 100)).toFixed(2);
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
                // harga boleh 0; disc boleh 0
                return row.fitemcode && row.fitemname && row.fsatuan && Number(row.fqty) > 0;
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

                // hitung total sebelum push
                this.recalc(r);

                // dupe detector (opsional: abaikan fdesc/ket)
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
                this.resetDraft();
                this.$nextTick(() => this.$refs.draftCode?.focus());
                this.syncDescList?.();
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

            // navigasi enter dari kode -> unit/qty tetap seperti milikmu
            handleEnterOnCode(where) {
                if (where === 'edit') {
                    if (this.editRow.units.length > 1) this.$refs.editUnit?.focus();
                    else this.$refs.editQty?.focus();
                } else {
                    if (this.draft.units.length > 1) this.$refs.draftUnit?.focus();
                    else this.$refs.draftQty?.focus();
                }
            },

            // modal deskripsi: biarkan kode milikmu (tak diubah)
            showDescModal: false,
            descTarget: 'draft',
            descSavedIndex: null,
            descValue: '',
            openDesc() {},
            closeDesc() {},
            applyDesc() {},

            init() {
                // event pilih produk via modal browse (biarkan seperti milikmu)
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
                    .toString(16)).join('') :
                Math.random().toString(36).slice(2)) + Date.now();
        }
    }
</script>
