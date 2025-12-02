@extends('layouts.app')

@section('title', 'Permintaan Pembelian')

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

        /* select supplier tanpa caret (view-only select) */
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
            margin: 0
        }

        input[type=number] {
            -moz-appearance: textfield
        }
    </style>

    <div x-data="{ open: true }">
        <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1600px] w-full mx-auto">
            <form action="{{ route('tr_prh.update', $tr_prh->fprid) }}" method="POST" class="mt-6" x-data="{ showNoItems: false }"
                @submit.prevent="
                window.__prh_flush_ok = true;
                window.dispatchEvent(new CustomEvent('prh-before-submit'));
                if (!window.__prh_flush_ok) return;

                const n = Number(document.getElementById('itemsCount')?.value || 0);
                if (n < 1) { showNoItems = true; return; }

                // Tunda submit sampai DOM update selesai
                $nextTick(() => { $el.submit() });
                ">
                @csrf
                @method('PATCH')
                @php
                    // anggap "approved" kalau sudah ada fuserapproved ATAU fapproval=1
                    $isApproved = !empty($tr_prh->fuserapproved) || (int) $tr_prh->fapproval === 1;
                @endphp

                @php
                    $fmt = fn($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('Y-m-d') : '';
                @endphp

                {{-- HEADER FORM --}}
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                    <div class="lg:col-span-4">
                        <label class="block text-sm font-medium">Cabang</label>
                        <input type="text" class="w-full border rounded px-3 py-2 bg-gray-200 cursor-not-allowed"
                            value="{{ $fcabang }}" disabled>
                        <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                    </div>

                    <div class="lg:col-span-4">
                        <label class="block text-sm font-medium mb-1">PR#</label>
                        <div class="flex items-center gap-3">
                            <input type="text" name="fprno"
                                class="w-full border rounded px-3 py-2 bg-gray-200 cursor-not-allowed"
                                value="{{ $tr_prh->fprno }}" disabled>
                        </div>
                    </div>

                    {{-- Supplier (browse seperti create) --}}
                    <div class="lg:col-span-4">
                        <label class="block text-sm font-medium mb-1">Supplier</label>
                        <div class="flex">
                            <div class="relative flex-1">
                                <select id="supplierSelect" name="fsupplier_view"
                                    class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
                                    disabled>
                                    <option value=""></option>
                                    @foreach ($supplier as $sup)
                                        <option value="{{ $sup->fsupplierid }}"
                                            {{ old('fsupplier', $tr_prh->fsupplier) == $sup->fsupplierid ? 'selected' : '' }}>
                                            {{ $sup->fsuppliercode }} - {{ $sup->fsuppliername }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="absolute inset-0" role="button" aria-label="Browse supplier"
                                    @click="window.dispatchEvent(new CustomEvent('supplier-browse-open'))"></div>
                            </div>
                            {{-- kirim ID supplier ke server --}}
                            <input type="hidden" name="fsupplier" id="supplierCodeHidden"
                                value="{{ old('fsupplier', $tr_prh->fsupplier) }}">
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
                        <input type="date" name="fprdate" value="{{ old('fprdate', $fmt($tr_prh->fprdate)) }}"
                            class="w-full border rounded px-3 py-2 @error('fprdate') border-red-500 @enderror">
                        @error('fprdate')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="lg:col-span-4">
                        <label class="block text-sm font-medium">Tanggal Dibutuhkan</label>
                        <input type="date" name="fneeddate" value="{{ old('fneeddate', $fmt($tr_prh->fneeddate)) }}"
                            class="w-full border rounded px-3 py-2 @error('fneeddate') border-red-500 @enderror">
                        @error('fneeddate')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="lg:col-span-4">
                        <label class="block text-sm font-medium">Tanggal Paling Lambat</label>
                        <input type="date" name="fduedate" value="{{ old('fduedate', $fmt($tr_prh->fduedate)) }}"
                            class="w-full border rounded px-3 py-2 @error('fduedate') border-red-500 @enderror">
                        @error('fduedate')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="lg:col-span-12">
                        <label class="block text-sm font-medium">Keterangan</label>
                        <textarea name="fket" rows="3" class="w-full border rounded px-3 py-2 @error('fket') border-red-500 @enderror"
                            placeholder="Tulis keterangan tambahan di sini...">{{ old('fket', $tr_prh->fket) }}</textarea>
                        @error('fket')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- DETAIL ITEM (tabel inline, sama seperti create, tapi prefill dari details) --}}
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
                                    <th class="p-2 text-right w-28">Qty</th>
                                    <th class="p-2 text-right w-28">Qty PO</th>
                                    <th class="p-2 text-left w-56">Ket Item</th>
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
                                                    class="inline-block px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 mr-2">
                                                    Deskripsi
                                                </span>
                                                <span class="align-middle text-gray-600" x-text="it.fdesc"></span>
                                            </div>
                                        </td>
                                        <td class="p-2" x-text="it.fsatuan"></td>
                                        <td class="p-2 text-right" x-text="it.fqty"></td>
                                        <td class="p-2 text-right" x-text="it.fqtypo"></td>
                                        <td class="p-2" x-text="it.fketdt || '-'"></td>
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
                                            <input type="hidden" name="fitemcode[]" x-model="it.fitemcode">
                                            <input type="hidden" name="fitemname[]" x-model="it.fitemname">
                                            <input type="hidden" name="fsatuan[]" x-model="it.fsatuan">
                                            <input type="hidden" name="fqty[]" x-model="it.fqty">
                                            <input type="hidden" name="fqtypo[]" x-model="it.fqtypo">
                                            <input type="hidden" name="fprdid[]" :value="it.fprdid ?? ''">
                                            <input type="hidden" name="fprdid[]" x-model="it.fprdid">
                                            <input type="hidden" name="fdesc[]" x-model="it.fdesc">
                                            <input type="hidden" name="fketdt[]" x-model="it.fketdt">
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
                                    </tr>
                                </template>

                                <!-- ROW EDIT UTAMA -->
                                <tr x-show="editingIndex !== null" class="border-t bg-amber-50 align-top" x-cloak>
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
                                            <a href="{{ route('product.create') }}" target="_blank" rel="noopener"
                                                class="border border-l-0 rounded-r px-2 py-1 bg-white hover:bg-gray-50"
                                                title="Tambah Produk">
                                                <x-heroicon-o-plus class="w-4 h-4" />
                                            </a>
                                        </div>
                                    </td>

                                    <td class="p-2">
                                        <input type="text"
                                            class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                            :value="editRow.fitemname" disabled>
                                    </td>

                                    <td class="p-2">
                                        <template x-if="editRow.units.length > 1">
                                            <select class="w-full border rounded px-2 py-1" x-ref="editUnit"
                                                x-model="editRow.fsatuan" @keydown.enter.prevent="$refs.editQty?.focus()">
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
                                            min="1" :max="editRow.maxqty || null" step="1"
                                            x-model.number="editRow.fqty" x-ref="editQty" @focus="$event.target.select()"
                                            @input="enforceQtyRow(editRow)"
                                            @keydown.enter.prevent="$refs.editKet?.focus()">
                                    </td>

                                    <td class="p-2 text-right">
                                        <input type="number" class="w-full border rounded px-2 py-1 text-gray-600"
                                            min="0" step="1" x-model.number="editRow.fqtypo" disabled>
                                    </td>

                                    <td class="p-2">
                                        <input type="text" class="border rounded px-2 py-1 w-full"
                                            x-model="editRow.fketdt" x-ref="editKet"
                                            @keydown.enter.prevent="applyEdit()">
                                    </td>

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
                                <tr x-show="editingIndex !== null" class="bg-amber-50 border-b" x-cloak>
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
                                    <td class="p-2" x-text="savedItems.length + 1"></td>

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

                                    <td class="p-2">
                                        <input type="text"
                                            class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                            :value="draft.fitemname" disabled>
                                    </td>

                                    <td class="p-2">
                                        <template x-if="draft.units.length > 1">
                                            <select class="w-full border rounded px-2 py-1" x-ref="draftUnit"
                                                x-model="draft.fsatuan" @keydown.enter.prevent="$refs.draftQty?.focus()">
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

                                    <td class="p-2 text-right">
                                        <input type="number" class="border rounded px-2 py-1 w-24 text-right"
                                            min="1" :max="draft.maxqty || null" step="1"
                                            x-model.number="draft.fqty" x-ref="draftQty" @focus="$event.target.select()"
                                            @input="enforceQtyRow(draft)"
                                            @keydown.enter.prevent="$refs.draftKet?.focus()">
                                    </td>

                                    <td class="p-2 text-right">
                                        <input type="number" class="w-full border rounded px-2 py-1 text-gray-600"
                                            min="0" step="1" x-model.number="draft.fqtypo" disabled>
                                    </td>

                                    <td class="p-2">
                                        <input type="text" class="border rounded px-2 py-1 w-full"
                                            x-model="draft.fketdt" x-ref="draftKet"
                                            @keydown.enter.prevent="addIfComplete()">
                                    </td>

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

                    <!-- MODAL DESC -->
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
                                    class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">Batal</button>
                                <button type="button" @click="applyDesc()"
                                    class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Simpan</button>
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
                            <p class="text-sm text-gray-700">Anda belum menambahkan item apa pun pada tabel. Silakan isi
                                baris “Detail Item” terlebih dahulu.</p>
                        </div>
                        <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
                            <button type="button" @click="showNoItems=false"
                                class="h-9 px-4 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">OK</button>
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
                    <div class="relative bg-white rounded-2xl shadow-xl w-[90vw] **max-w-7xl** max-h-[90vh] flex flex-col">
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

                {{-- STATUS & ACTIONS --}}
                <div class="md:col-span-2 flex justify-center items-center space-x-2 mt-6">
                    <label for="statusToggle"
                        class="flex items-center justify-between w-40 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                        <span class="text-sm font-medium">Tutup</span>
                        <input type="checkbox" name="fnonactive" id="statusToggle"
                            class="h-5 w-5 text-green-600 rounded focus:ring-green-500"
                            {{ old('fnonactive') == '1' ? 'checked' : '' }}>
                    </label>
                </div>

                <fieldset {{ $isApproved ? 'disabled' : '' }}>
                    <div class="md:col-span-2 flex justify-center items-center space-x-2 mt-6">
                        <label class="text-sm font-medium">Approval</label>

                        {{-- default 0 supaya unchecked tetap terkirim --}}
                        <input type="hidden" name="fapproval" value="0">

                        <label class="switch">
                            <input type="checkbox" name="fapproval" id="approvalToggle" value="1"
                                {{ $isApproved ? 'checked' : '' }}>
                            <span class="slider round"></span>
                        </label>
                    </div>

                    @if ($isApproved)
                        <div class="text-xs text-gray-600 text-center mt-2">
                            Disetujui oleh: <strong>{{ $tr_prh->fuserapproved }}</strong>
                            @if (!empty($tr_prh->fdateapproved))
                                pada {{ \Carbon\Carbon::parse($tr_prh->fdateapproved)->format('d-m-Y H:i') }}
                            @endif
                        </div>
                    @endif
                </fieldset>

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
</style>
{{-- DATA & SCRIPTS --}}
<script>
    // Map produk untuk auto-fill tabel
    window.PRODUCT_MAP = {
        @foreach ($products as $p)
            "{{ $p->fprdcode }}": {
                id: @json($p->fprdid),
                name: @json($p->fprdname),
                units: @json(array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2]))),
                stock: @json($p->fminstock ?? 0)
            },
        @endforeach
    };

    // Seed items dari server (details)
    window.INIT_ITEMS = [
        @foreach ($tr_prh->details as $d)
            {
                uid: null, // akan diisi cryptoRandom()
                fitemcode: @json($d->fprdcode),
                fitemname: @json($d->fprdname),
                fsatuan: @json($d->fsatuan),
                fqty: {{ (int) $d->fqty }},
                fqtypo: @json($d->fqtypo ?? ''),
                fdesc: @json($d->fdesc ?? ''),
                fketdt: @json($d->fketdt ?? ''),
            },
        @endforeach
    ];

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

    // Modal supplier (sama dengan create)
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

    // Alpine store untuk desc (optional, sama seperti create)
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

    // Tabel inline (re-use dari create, plus initFromServer)
    function itemsTable() {
        return {
            savedItems: @json($savedItems ?? []),
            draft: {
                fitemcode: '',
                fitemname: '',
                units: [],
                fsatuan: '',
                fqty: 1,
                fqtypo: 0,
                fdesc: '',
                fketdt: '',
                maxqty: 0
            },
            editingIndex: null,
            editRow: {
                fitemcode: '',
                fitemname: '',
                units: [],
                fsatuan: '',
                fqty: 1,
                fdesc: '',
                fqtypo: 0,
                fketdt: '',
                maxqty: 0
            },

            resetDraft() {
                this.draft = {
                    fitemcode: '',
                    fitemname: '',
                    units: [],
                    fsatuan: '',
                    fqty: 1,
                    fqtypo: 0,
                    fdesc: '',
                    fketdt: '',
                    maxqty: 0
                };
            },
            productMeta(code) {
                const key = (code || '').trim();
                return window.PRODUCT_MAP[key] || null;
            },
            hydrateRowFromMeta(row, meta) {
                if (!meta) {
                    row.fprdid = null; // ⬅️ reset
                    row.fitemname = '';
                    row.units = [];
                    row.fsatuan = '';
                    row.maxqty = 0;
                    return;
                }
                row.fprdid = meta.id || null; // ⬅️ ambil ID
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
            sanitizeNumber(v, d = 0) {
                const n = +v;
                return Number.isFinite(n) ? n : d;
            },
            enforceQtyRow(row) {
                const n = this.sanitizeNumber(row.fqty, 1);
                if (!Number.isFinite(n)) {
                    row.fqty = '';
                    return;
                }
                if (n < 1) row.fqty = 1;
                if (row.maxqty > 0 && n > row.maxqty) row.fqty = row.maxqty;
                row.fqtypo = Math.max(0, Math.min(this.sanitizeNumber(row.fqtypo, 0), row.fqty));
            },
            isComplete(row) {
                return row.fitemcode && row.fitemname && row.fsatuan && Number(row.fqty) > 0;
            },

            addIfComplete() {
                const r = this.draft;
                if (!this.isComplete(r)) {
                    /* ... */
                    return;
                }

                r.fqtypo = Number.isFinite(+r.fqtypo) ? +r.fqtypo : 0;
                if (r.fqtypo > r.fqty) r.fqtypo = r.fqty;
                r.fqty = this.sanitizeNumber(r.fqty, 1);

                // pastikan ada ID:
                if (!r.fprdid) {
                    const meta = this.productMeta(r.fitemcode);
                    r.fprdid = meta?.id ?? null;
                }
                if (!r.fprdid) {
                    alert('Produk tidak valid / ID produk tidak ditemukan.');
                    return;
                }

                const dupe = this.savedItems.find(it =>
                    it.fitemcode === r.fitemcode && it.fsatuan === r.fsatuan &&
                    (it.fdesc || '') === (r.fdesc || '') && (it.fketdt || '') === (r.fketdt || '')
                );
                if (dupe) {
                    alert('Item sama sudah ada.');
                    return;
                }

                this.savedItems.push({
                    uid: cryptoRandom(),
                    fprdid: r.fprdid, // ⬅️ simpan ID
                    fitemcode: r.fitemcode,
                    fitemname: r.fitemname,
                    fsatuan: r.fsatuan,
                    fqtypo: r.fqtypo,
                    fqty: +r.fqty,
                    fdesc: r.fdesc || '',
                    fketdt: r.fketdt || ''
                });

                this.resetDraft();
                this.$nextTick(() => this.$refs.draftCode?.focus());
                this.syncDescList();
            },

            initFromServer() {
                // 1) Ambil data detail dari server (dibawa blade ke window.INIT_ITEMS)
                const rows = Array.isArray(window.INIT_ITEMS) ? window.INIT_ITEMS : [];

                this.savedItems = rows.map((r) => {
                    const it = {
                        uid: cryptoRandom(),
                        fitemcode: (r.fitemcode || '').toString(),
                        fitemname: r.fitemname || '',
                        fsatuan: r.fsatuan || '',
                        fqty: Number(r.fqty) || 0,
                        fqtypo: Number(r.fqtypo) || 0, // <-- ADD THIS
                        fdesc: r.fdesc || '',
                        fketdt: r.fketdt || '',
                        units: [],
                        maxqty: 0,
                    };

                    // 2) Hydrate dari PRODUCT_MAP (seperti saat create)
                    const meta = this.productMeta(it.fitemcode);
                    this.hydrateRowFromMeta(it, meta);

                    // 3) Pastikan qty sesuai rules (min 1, max stok kalau ada)
                    this.enforceQtyRow(it);

                    return it;
                });

                // 4) Sinkron daftar deskripsi untuk preview/list
                this.syncDescList();
                this.init();
            },

            // === Deskripsi via Modal ===
            showDescModal: false,
            descTarget: 'draft', // 'draft' | 'edit' | 'saved'
            descSavedIndex: null, // index untuk 'saved'
            descValue: '',
            descPreview: '', // untuk ditampilkan di luar card

            openDesc(where, idx = null, currentVal = '') {
                this.descTarget = where;
                this.descSavedIndex = (where === 'saved' ? idx : null);
                this.descValue = currentVal || '';
                this.showDescModal = true;

                // set preview sementara (sebelum disimpan) biar user tahu baris mana
                let meta = {
                    uid: null,
                    index: null,
                    label: '',
                    text: this.descValue
                };
                if (where === 'saved' && idx !== null) {
                    const it = this.savedItems[idx];
                    meta = {
                        uid: it.uid,
                        index: idx + 1,
                        label: this.labelOf(it),
                        text: this.descValue
                    };
                } else if (where === 'edit') {
                    meta = {
                        uid: 'editing',
                        index: (this.editingIndex ?? 0) + 1,
                        label: this.labelOf(this.editRow),
                        text: this.descValue
                    };
                } else {
                    meta = {
                        uid: 'draft',
                        index: this.savedItems.length + 1,
                        label: this.labelOf(this.draft),
                        text: this.descValue
                    };
                }
                Alpine.store('prh').descPreview = meta;
            },
            closeDesc() {
                this.showDescModal = false;
            },
            applyDesc() {
                const val = (this.descValue || '').trim();

                if (this.descTarget === 'draft') {
                    this.draft.fdesc = val;
                    Alpine.store('prh').descPreview = {
                        uid: 'draft',
                        index: this.savedItems.length + 1,
                        label: this.labelOf(this.draft),
                        text: val
                    };
                } else if (this.descTarget === 'edit') {
                    this.editRow.fdesc = val;
                    Alpine.store('prh').descPreview = {
                        uid: 'editing',
                        index: (this.editingIndex ?? 0) + 1,
                        label: this.labelOf(this.editRow),
                        text: val
                    };
                } else if (this.descTarget === 'saved' && this.descSavedIndex !== null) {
                    const it = this.savedItems[this.descSavedIndex];
                    it.fdesc = val;
                    Alpine.store('prh').descPreview = {
                        uid: it.uid,
                        index: this.descSavedIndex + 1,
                        label: this.labelOf(it),
                        text: val
                    };
                }

                this.showDescModal = false;
                this.syncDescList(); // update daftar semua desc
            },

            labelOf(row) {
                // bebas: pakai kode - nama atau apa pun
                return [row.fitemcode, row.fitemname].filter(Boolean).join(' — ');
            },
            syncDescList() {
                Alpine.store('prh').descList = this.savedItems
                    .map((it, i) => ({
                        uid: it.uid,
                        index: i + 1,
                        label: this.labelOf(it),
                        text: it.fdesc || ''
                    }))
                    .filter(x => x.text); // hanya yang ada deskripsi
            },

            handleEnterOnCode(where) {
                // Pindah fokus dari Kode -> (Unit jika >1) else -> Qty
                if (where === 'edit') {
                    if (this.editRow.units.length > 1) this.$refs.editUnit?.focus();
                    else this.$refs.editQty?.focus();
                } else {
                    if (this.draft.units.length > 1) this.$refs.draftUnit?.focus();
                    else this.$refs.draftQty?.focus();
                }
            },

            edit(i) {
                const it = this.savedItems[i];
                this.editingIndex = i;
                this.editRow = {
                    fprdid: it.fprdid || null, // ⬅️ penting
                    fitemcode: it.fitemcode,
                    fitemname: it.fitemname,
                    units: [],
                    fsatuan: it.fsatuan,
                    fqty: it.fqty,
                    fdesc: it.fdesc,
                    fqtypo: it.fqtypo,
                    fketdt: it.fketdt,
                    maxqty: 0
                };
                this.hydrateRowFromMeta(this.editRow, this.productMeta(this.editRow.fitemcode));
                this.enforceQtyRow(this.editRow);
                this.$nextTick(() => this.$refs.editQty?.focus());
            },
            cancelEdit() {
                this.editingIndex = null;
            },
            applyEdit() {
                const r = this.editRow;
                if (!this.isComplete(r)) {
                    alert('Lengkapi data item.');
                    return;
                }

                // pastikan ada ID untuk produk hasil edit
                if (!r.fprdid) {
                    const meta = this.productMeta(r.fitemcode);
                    r.fprdid = meta?.id ?? null;
                }
                if (!r.fprdid) {
                    alert('Produk tidak valid / ID produk tidak ditemukan.');
                    return;
                }

                const it = this.savedItems[this.editingIndex];
                it.fprdid = r.fprdid; // ⬅️ copy ID
                it.fitemcode = r.fitemcode;
                it.fitemname = r.fitemname;
                it.fsatuan = r.fsatuan;
                it.fqty = this.sanitizeNumber(r.fqty, 1);
                it.fdesc = r.fdesc || '';
                it.fqtypo = Math.max(0, Math.min(this.sanitizeNumber(r.fqtypo, 0), it.fqty));
                it.fketdt = r.fketdt || '';

                this.cancelEdit();
                this.syncDescList();
            },

            removeSaved(i) {
                this.savedItems.splice(i, 1);
                this.syncDescList(); // <= tambahkan ini
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

            init() {
                window.addEventListener('product-chosen', (e) => {
                    const {
                        product
                    } = e.detail || {};
                    if (!product) return;

                    const apply = (row) => {
                        row.fitemcode = (product.fprdcode || '').toString();
                        row.fprdid = product.fprdid ?? (window.PRODUCT_INDEX_BY_CODE?.[row.fitemcode]?.id ??
                            null); // ⬅️ set ID
                        // kalau browse nggak kirim name/units, hydrasi lewat PRODUCT_MAP:
                        const meta = this.productMeta(row.fitemcode);
                        if (meta) {
                            this.hydrateRowFromMeta(row,
                                meta); // ini juga akan set fprdid, name, units, stock, dll
                        } else {
                            // fallback kalau meta kosong, set minimal name
                            row.fitemname = product.fprdname || row.fitemname || '';
                        }
                        // perbaiki qty
                        row.fqtypo = Math.max(0, Math.min(this.sanitizeNumber(row.fqtypo, 0), row.fqty));
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

                // ... (listener prh-before-submit tetap)
            },

        }
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
