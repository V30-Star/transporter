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

        .item-row-active {
            background-color: #f0fdf4;
        }
    </style>
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow p-0 overflow-hidden" role="alert">
            {{-- Header Strip --}}
            <div class="d-flex align-items-center px-4 py-3" style="background-color: #c0392b;">
                <i class="bi bi-exclamation-triangle-fill text-white me-2 fs-5"></i>
                <strong class="text-white fs-6">Gagal Menyimpan Data!</strong>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert"
                    aria-label="Close"></button>
            </div>

            {{-- Body --}}
            <div class="px-4 py-3" style="background-color: #fdeded; border-left: 5px solid #c0392b;">
                <p class="mb-2 text-danger fw-semibold">
                    <i class="bi bi-info-circle me-1"></i>
                    Periksa kembali data berikut sebelum menyimpan:
                </p>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li class="text-danger mb-1">
                            <i class="bi bi-dot fs-5 align-middle"></i>
                            {{ $error }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
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
                        <label class="block text-sm font-medium mb-1">PR#</label>
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
                            <div class="relative flex-1" for="modal_filter_supplier_id">
                                <select id="modal_filter_supplier_id" name="filter_supplier_id"
                                    class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
                                    disabled>
                                    <option value=""></option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->fsuppliercode }}"
                                            {{ $filterSupplierId == $supplier->fsuppliercode ? 'selected' : '' }}>
                                            {{ $supplier->fsuppliername }} ({{ $supplier->fsuppliercode }})
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
                        <label class="block text-sm font-medium">Tanggal Dibutuhkan</label>
                        <input type="date" name="fneeddate" value="{{ old('fneeddate', '') }}"
                            class="w-full border rounded px-3 py-2 @error('fneeddate') border-red-500 @enderror">
                        @error('fneeddate')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="lg:col-span-4">
                        <label class="block text-sm font-medium">Tanggal Paling Lambat</label>
                        <input type="date" name="fduedate" value="{{ old('fduedate', '') }}"
                            class="w-full border rounded px-3 py-2 @error('fduedate') border-red-500 @enderror">
                        @error('fduedate')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
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

                {{-- DETAIL ITEM --}}
                <div x-data="itemsTable()" x-init="init()" class="mt-6 space-y-2">
                    <h3 class="text-base font-semibold text-gray-800">Detail Item</h3>

                    <div class="overflow-auto border rounded">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="p-2 text-left w-10">#</th>
                                    <th class="p-2 text-left w-52">Kode Produk</th>
                                    <th class="p-2 text-left">Nama Produk</th>
                                    <th class="p-2 text-left w-40">Satuan</th>
                                    <th class="p-2 text-right w-28">Qty</th>
                                    <th class="p-2 text-left w-56">Ket Item</th>
                                    <th class="p-2 text-center w-20">Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                {{-- BARIS TERSIMPAN --}}
                                <template x-for="(it, i) in savedItems" :key="it.uid">
                                    <tr class="border-t align-top transition-colors"
                                        :class="activeRow === it.uid ? 'bg-amber-50' : 'hover:bg-gray-50'">

                                        <td class="p-2 text-gray-500" x-text="i + 1"></td>

                                        <td class="p-2">
                                            <div class="flex">
                                                <input type="text"
                                                    class="flex-1 border rounded-l px-2 py-1 font-mono text-sm min-w-0"
                                                    x-model.trim="it.fitemcode" @focus="activeRow = it.uid"
                                                    @blur="activeRow = null" @input="onCodeTypedSaved(it)"
                                                    @keydown.enter.prevent="focusUnitOrQty(it, i)">
                                                <button type="button" @click="openBrowseFor('saved', i)"
                                                    class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50"
                                                    title="Cari Produk">
                                                    <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>

                                        <td class="p-2">
                                            <input type="text"
                                                class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                                :value="it.fitemname" disabled>
                                            <textarea x-model="it.fdesc" rows="2" class="w-full border rounded px-2 py-1 text-xs text-gray-600 mt-1"
                                                placeholder="Deskripsi (opsional)" @focus="activeRow = it.uid" @blur="activeRow = null"></textarea>
                                        </td>

                                        {{--
                                            SATUAN SAVED:
                                            Pakai x-effect untuk paksa set select.value setelah x-for render options.
                                            Ini fix masalah x-for me-reset selectedIndex ke 0 saat render ulang.
                                        --}}
                                        <td class="p-2">
                                            <template x-if="it.units.length > 1">
                                                <select class="w-full border rounded px-2 py-1 text-sm"
                                                    :id="'unit_saved_' + i"
                                                    x-effect="$nextTick(() => { const el = document.getElementById('unit_saved_' + i); if (el) el.value = it.fsatuan; })"
                                                    @change="it.fsatuan = $event.target.value" @focus="activeRow = it.uid"
                                                    @blur="activeRow = null" @keydown.enter.prevent="focusSavedQty(i)">
                                                    <template x-for="u in it.units" :key="u">
                                                        <option :value="u" x-text="u"></option>
                                                    </template>
                                                </select>
                                            </template>
                                            <template x-if="it.units.length <= 1">
                                                <input type="text"
                                                    class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                                    :value="it.fsatuan || '-'" disabled>
                                            </template>
                                        </td>

                                        <td class="p-2 text-right">
                                            <input type="number" class="w-full border rounded px-2 py-1 text-right"
                                                x-model.number="it.fqty" min="1"
                                                @focus="activeRow = it.uid; $event.target.select()"
                                                @blur="activeRow = null">
                                        </td>

                                        <td class="p-2">
                                            <input type="text" class="border rounded px-2 py-1 w-full text-sm"
                                                x-model="it.fketdt" :id="'ket_saved_' + i" @focus="activeRow = it.uid"
                                                @blur="activeRow = null" @keydown.enter.prevent="focusDraftCode()">
                                        </td>

                                        <td class="p-2 text-center">
                                            <button type="button" @click="removeSaved(i)"
                                                class="px-3 py-1 rounded text-xs bg-red-100 text-red-600 hover:bg-red-200 whitespace-nowrap">
                                                Hapus
                                            </button>
                                        </td>

                                        <td class="hidden">
                                            <input type="hidden" name="fitemcode[]" :value="it.fitemcode">
                                            <input type="hidden" name="fitemname[]" :value="it.fitemname">
                                            <input type="hidden" name="fnoacak[]" :value="it.fnoacak">
                                            <input type="hidden" name="fsatuan[]" :value="it.fsatuan">
                                            <input type="hidden" name="fqty[]" :value="it.fqty">
                                            <input type="hidden" name="fqtypo[]" :value="it.fqtypo">
                                            <input type="hidden" name="fdesc[]" :value="it.fdesc">
                                            <input type="hidden" name="fketdt[]" :value="it.fketdt">
                                        </td>
                                    </tr>
                                </template>

                                {{-- BARIS DRAFT --}}
                                <tr class="border-t bg-green-50 align-top">
                                    <td class="p-2 text-gray-400" x-text="savedItems.length + 1"></td>

                                    <td class="p-2">
                                        <div class="flex">
                                            <input type="text"
                                                class="flex-1 border rounded-l px-2 py-1 font-mono text-sm min-w-0"
                                                x-ref="draftCode" x-model.trim="draft.fitemcode"
                                                @input="onCodeTypedRow(draft)"
                                                @keydown.enter.prevent="handleEnterOnCode()">
                                            <button type="button" @click="openBrowseFor('draft')"
                                                class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50"
                                                title="Cari Produk">
                                                <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>

                                    <td class="p-2">
                                        <input type="text"
                                            class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                            :value="draft.fitemname" disabled>
                                        <textarea x-model="draft.fdesc" rows="2" class="w-full border rounded px-2 py-1 text-xs text-gray-600 mt-1"
                                            placeholder="Deskripsi (opsional)"></textarea>
                                    </td>

                                    {{--
                                        SATUAN DRAFT: TIDAK pakai x-model / x-for.
                                        Options diisi manual via JS (populateDraftUnitSelect).
                                        Nilai dibaca dari DOM saat addIfComplete().
                                    --}}
                                    <td class="p-2">
                                        <select id="draftUnitSelect" class="w-full border rounded px-2 py-1 text-sm"
                                            x-show="draft.units.length > 1"
                                            @keydown.enter.prevent="$refs.draftQty?.focus()">
                                        </select>
                                        <input type="text"
                                            class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                            x-show="draft.units.length <= 1" :value="draft.fsatuan || '-'" disabled>
                                    </td>

                                    <td class="p-2">
                                        <input type="number" class="w-full border rounded px-2 py-1 text-right"
                                            x-model.number="draft.fqty" min="1" x-ref="draftQty"
                                            @keydown.enter.prevent="addIfComplete()">
                                    </td>

                                    <td class="p-2">
                                        <input type="text" class="border rounded px-2 py-1 w-full text-sm"
                                            x-model="draft.fketdt" x-ref="draftKet"
                                            @keydown.enter.prevent="addIfComplete()">
                                    </td>

                                    <td class="p-2 text-center">
                                        <button type="button" @click="addIfComplete()"
                                            class="px-3 py-1 rounded text-xs bg-emerald-600 text-white hover:bg-emerald-700 whitespace-nowrap">
                                            Tambah
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
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
                                Anda belum menambahkan item apa pun pada tabel. Silakan isi baris "Detail Item" terlebih
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
                    class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-6xl flex flex-col overflow-hidden"
                        style="height: 650px;">
                        <div
                            class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
                            <div>
                                <h3 class="text-xl font-bold text-gray-800">Browse Supplier</h3>
                                <p class="text-sm text-gray-500 mt-0.5">Pilih supplier yang diinginkan</p>
                            </div>
                            <button type="button" @click="close()"
                                class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                                Tutup
                            </button>
                        </div>
                        <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                            <div id="supplierTableControls"></div>
                        </div>
                        <div class="flex-1 overflow-y-auto px-6" style="min-height: 0;">
                            <div class="bg-white">
                                <table id="supplierBrowseTable" class="min-w-full text-sm display nowrap stripe hover"
                                    style="width:100%">
                                    <thead class="sticky top-0 z-10">
                                        <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                            <th
                                                class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                Kode</th>
                                            <th
                                                class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                Nama Supplier</th>
                                            <th
                                                class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                Alamat</th>
                                            <th
                                                class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                Telepon</th>
                                            <th
                                                class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
                            <div id="supplierTablePagination"></div>
                        </div>
                    </div>
                </div>

                {{-- MODAL PRODUK --}}
                <div x-data="productBrowser()" x-show="open" x-cloak x-transition.opacity
                    class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-6xl flex flex-col overflow-hidden"
                        style="height: 650px;">
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
                        <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                            <div id="productTableControls"></div>
                        </div>
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
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
                            <div id="productTablePagination"></div>
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

@push('styles')
    <style>
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

        div#productTable_length,
        .dataTables_wrapper #productTable_length,
        .dataTables_wrapper .dataTables_length {
            min-width: 250px !important;
        }

        div#productTable_length label,
        .dataTables_wrapper #productTable_length label,
        .dataTables_wrapper .dataTables_length label {
            font-size: 14px !important;
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
        }

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

        div#supplierTable_length,
        .dataTables_wrapper #supplierTable_length,
        .dataTables_wrapper .dataTables_length {
            min-width: 250px !important;
        }

        div#supplierTable_length label,
        .dataTables_wrapper #supplierTable_length label,
        .dataTables_wrapper .dataTables_length label {
            font-size: 14px !important;
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
        }
    </style>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
@endpush

{{-- DATA & SCRIPTS --}}
<script>
    window.PRODUCT_MAP = @json($productMap ?? []);

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

    // ── Pure DOM helpers untuk draft unit select ──────────────────────────────
    function getDraftUnitSelect() {
        return document.getElementById('draftUnitSelect');
    }

    function populateDraftUnitSelect(units) {
        const sel = getDraftUnitSelect();
        if (!sel) return;
        sel.innerHTML = '';
        units.forEach(u => {
            const opt = document.createElement('option');
            opt.value = u;
            opt.textContent = u;
            sel.appendChild(opt);
        });
    }

    function getDraftUnitValue() {
        const sel = getDraftUnitSelect();
        return sel ? sel.value : '';
    }

    function clearDraftUnitSelect() {
        const sel = getDraftUnitSelect();
        if (sel) sel.innerHTML = '';
    }

    // ── supplierBrowser ───────────────────────────────────────────────────────
    function supplierBrowser() {
        return {
            open: false,
            dataTable: null,

            initDataTable() {
                if (this.dataTable) this.dataTable.destroy();
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
                            render: () =>
                                '<button type="button" class="btn-choose px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150">Pilih</button>'
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
                        const $c = $(this.api().table().container());
                        $c.find('.dt-search .dt-input, .dataTables_filter input').css({
                            width: '300px',
                            padding: '8px 12px',
                            border: '2px solid #e5e7eb',
                            borderRadius: '8px',
                            fontSize: '14px'
                        }).focus();
                        $c.find('.dt-length select, .dataTables_length select').css({
                            padding: '6px 32px 6px 10px',
                            border: '2px solid #e5e7eb',
                            borderRadius: '8px',
                            fontSize: '14px'
                        });
                    }
                });
                $('#supplierBrowseTable').on('click', '.btn-choose', (e) => {
                    this.chooseSupplier(this.dataTable.row($(e.target).closest('tr')).data());
                });
            },

            openBrowse() {
                this.open = true;
                this.$nextTick(() => this.initDataTable());
            },
            close() {
                this.open = false;
                if (this.dataTable) this.dataTable.search('').draw();
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

    // ── itemsTable ────────────────────────────────────────────────────────────
    function itemsTable() {
        return {
            savedItems: [],
            activeRow: null,
            browseTarget: 'draft',

            draft: {
                fitemcode: '',
                fitemname: '',
                fnoacak: '',
                units: [],
                fsatuan: '',
                fqty: '',
                fdesc: '',
                fketdt: '',
                maxqty: 0
            },

            resetDraft() {
                this.draft = {
                    fitemcode: '',
                    fitemname: '',
                    fnoacak: this.generateUniqueNoAcak(),
                    units: [],
                    fsatuan: '',
                    fqty: '',
                    fdesc: '',
                    fketdt: '',
                    maxqty: 0
                };
                clearDraftUnitSelect();
            },

            normalizeNoAcak(value) {
                return (value || '').toString().replace(/\D/g, '').slice(0, 3);
            },

            generateUniqueNoAcak() {
                const used = new Set(this.savedItems.map(item => this.normalizeNoAcak(item.fnoacak)).filter(Boolean));
                let candidate = '';

                do {
                    candidate = Array.from({ length: 3 }, () => '123456789'[Math.floor(Math.random() * 9)]).join('');
                } while (used.has(candidate));

                return candidate;
            },

            productMeta(code) {
                const key = (code || '').trim();
                return window.PRODUCT_MAP?.[key] || {
                    name: '',
                    units: [],
                    stock: 0,
                    unit_ratios: {
                        satuankecil: 1,
                        satuanbesar: 1,
                        satuanbesar2: 1
                    }
                };
            },

            formatStockLimit(code, qty, satuan) {
                const meta = this.productMeta(code);
                if (!code || !meta.stock) return '';

                const entered = Number(qty) || 0;
                const remaining = Math.max(0, meta.stock - entered);
                const units = meta.units || [];
                const ratios = meta.unit_ratios || {
                    satuankecil: 1,
                    satuanbesar: 1,
                    satuanbesar2: 1
                };

                if (!units.length || !satuan) return '';

                const satKecil = units[0] || 'pcs';
                const satBesar = units[1] || '';
                const satBesar2 = units[2] || '';

                let ratio = 1;
                if (satuan === satBesar2 && ratios.satuanbesar2 > 0) {
                    ratio = ratios.satuanbesar2;
                } else if (satuan === satBesar && ratios.satuanbesar > 0) {
                    ratio = ratios.satuanbesar;
                } else if (satuan === satKecil) {
                    ratio = 1;
                }

                const limitValue = Math.floor(remaining / ratio);
                return '<span class="font-medium">limit:</span> ' + limitValue + ' ' + satuan;
            },

            // Hydrate baris TERSIMPAN — Alpine reaktif
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
                const prev = row.fsatuan;
                row.units = units;
                row.fsatuan = units.includes(prev) ? prev : (units[0] || '');
                row.maxqty = Number.isFinite(+meta.stock) && +meta.stock > 0 ? +meta.stock : 0;
                row.fnoacak = this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak();
            },

            // Hydrate baris DRAFT — pure DOM untuk select
            hydrateDraftFromMeta(meta) {
                const row = this.draft;
                if (!meta) {
                    row.fitemname = '';
                    row.units = [];
                    row.fsatuan = '';
                    row.maxqty = 0;
                    clearDraftUnitSelect();
                    return;
                }
                row.fitemname = meta.name || '';
                const units = [...new Set((meta.units || []).map(u => (u ?? '').toString().trim()).filter(Boolean))];
                row.units = units;
                row.maxqty = Number.isFinite(+meta.stock) && +meta.stock > 0 ? +meta.stock : 0;
                if (units.length > 1) {
                    populateDraftUnitSelect(units);
                    row.fsatuan = units[0]; // hanya fallback
                } else {
                    clearDraftUnitSelect();
                    row.fsatuan = units[0] || '';
                }
                row.fnoacak = this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak();
            },

            onCodeTypedRow(row) {
                this.hydrateDraftFromMeta(this.productMeta(row.fitemcode));
            },
            onCodeTypedSaved(item) {
                this.hydrateRowFromMeta(item, this.productMeta(item.fitemcode));
            },

            enforceQtyRow(row) {
                // max qty validation dihapus: qty tidak lagi dipaksa mengikuti stok maksimum.
                // HTML/server tetap menangani minimal qty.
                return;
            },

            focusUnitOrQty(item, i) {
                if (item.units.length > 1) this.$nextTick(() => document.getElementById('unit_saved_' + i)?.focus());
                else this.$nextTick(() => document.getElementById('qty_saved_' + i)?.focus());
            },
            focusSavedQty(i) {
                this.$nextTick(() => document.getElementById('qty_saved_' + i)?.focus());
            },
            focusSavedKet(i) {
                this.$nextTick(() => document.getElementById('ket_saved_' + i)?.focus());
            },
            focusDraftCode() {
                this.$nextTick(() => this.$refs.draftCode?.focus());
            },

            handleEnterOnCode() {
                if (this.draft.units.length > 1) getDraftUnitSelect()?.focus();
                else this.$refs.draftQty?.focus();
            },

            addIfComplete() {
                const r = this.draft;

                // Baca satuan langsung dari DOM — sumber kebenaran tunggal
                const satuanFinal = r.units.length > 1 ?
                    (getDraftUnitValue() || r.fsatuan) :
                    r.fsatuan;

                if (!r.fitemcode || !r.fitemname || !satuanFinal || !(Number(r.fqty) > 0)) {
                    if (!r.fitemcode) return this.$refs.draftCode?.focus();
                    if (!r.fitemname) return this.$refs.draftCode?.focus();
                    if (!satuanFinal) return (r.units.length > 1 ? getDraftUnitSelect()?.focus() : this.$refs.draftCode
                        ?.focus());
                    if (!(Number(r.fqty) > 0)) return this.$refs.draftQty?.focus();
                    return;
                }

                this.savedItems.push({
                    uid: cryptoRandom(),
                    fitemcode: r.fitemcode,
                    fitemname: r.fitemname,
                    fnoacak: this.normalizeNoAcak(r.fnoacak) || this.generateUniqueNoAcak(),
                    units: [...r.units],
                    fsatuan: satuanFinal, // dari DOM, bukan Alpine proxy
                    fqty: +r.fqty,
                    fdesc: r.fdesc || '',
                    fketdt: r.fketdt || '',
                    maxqty: r.maxqty,
                    fqtypo: 0,
                });

                this.resetDraft();
                this.$nextTick(() => this.$refs.draftCode?.focus());
            },

            removeSaved(i) {
                this.savedItems.splice(i, 1);
            },

            openBrowseFor(where, idx = null) {
                this.browseTarget = (where === 'saved' && idx !== null) ? idx : 'draft';
                window.dispatchEvent(new CustomEvent('browse-open', {
                    detail: {
                        forEdit: false
                    }
                }));
            },

            init() {
                this.resetDraft();

                window.addEventListener('product-chosen', (e) => {
                    const {
                        product
                    } = e.detail || {};
                    if (!product) return;

                    if (this.browseTarget === 'draft') {
                        this.draft.fitemcode = (product.fprdcode || '').toString();
                        this.hydrateDraftFromMeta(this.productMeta(this.draft.fitemcode));
                        this.draft.fqty = (+this.draft.fqty || 1);
                        this.$nextTick(() => {
                            if (this.draft.units.length > 1) getDraftUnitSelect()?.focus();
                            else this.$refs.draftQty?.focus();
                        });
                    } else {
                        const item = this.savedItems[this.browseTarget];
                        if (item) {
                            item.fitemcode = (product.fprdcode || '').toString();
                            this.hydrateRowFromMeta(item, this.productMeta(item.fitemcode));
                            item.fqty = (+item.fqty || 1);
                            const i = this.browseTarget;
                            this.$nextTick(() => document.getElementById('qty_saved_' + i)?.focus());
                        }
                    }
                }, {
                    passive: true
                });

                document.addEventListener('change', (e) => {
                    if (e.target && e.target.id === 'draftUnitSelect') {
                        this.draft.fsatuan = e.target.value;
                    }
                });
            }
        }
    }
</script>

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    <script>
        function productBrowser() {
            return {
                open: false,
                table: null,

                initDataTable() {
                    if (this.table) this.table.destroy();
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
                                render: d => d || '-'
                            },
                            {
                                data: 'fmerekname',
                                name: 'fmerekname',
                                className: 'text-center text-sm',
                                render: d => d || '-'
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
                                render: () =>
                                    '<button type="button" class="btn-choose px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150">Pilih</button>'
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
                            const $c = $(this.api().table().container());
                            $c.find('.dt-search .dt-input, .dataTables_filter input').css({
                                width: '300px',
                                padding: '8px 12px',
                                border: '2px solid #e5e7eb',
                                borderRadius: '8px',
                                fontSize: '14px'
                            }).focus();
                            $c.find('.dt-length select, .dataTables_length select').css({
                                padding: '6px 32px 6px 10px',
                                border: '2px solid #e5e7eb',
                                borderRadius: '8px',
                                fontSize: '14px'
                            });
                        }
                    });
                    $('#productTable').off('click', '.btn-choose').on('click', '.btn-choose', (e) => {
                        const product = this.table.row($(e.target).closest('tr')).data();
                        if (product) this.choose(product);
                    });
                },

                close() {
                    this.open = false;
                    if (this.table) this.table.search('').draw();
                },
                choose(product) {
                    window.dispatchEvent(new CustomEvent('product-chosen', {
                        detail: {
                            product
                        }
                    }));
                    this.close();
                },
                init() {
                    window.addEventListener('browse-open', () => {
                        this.open = true;
                        this.$nextTick(() => this.initDataTable());
                    }, {
                        passive: true
                    });
                }
            }
        }
    </script>
@endpush
