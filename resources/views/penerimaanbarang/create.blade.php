@extends('layouts.app')

@section('title', 'Penerimaan Barang - New')

@section('content')
@php
    $canViewHpp = $canViewHpp ?? in_array('viewProductHpp', explode(',', session('user_restricted_permissions', '')));
@endphp
    <style>
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, .2);
        }

        .penerimaan-detail-table th,
        .penerimaan-detail-table td {
            padding: .25rem .35rem !important;
        }

        .penerimaan-detail-table input,
        .penerimaan-detail-table select,
        .penerimaan-detail-table button {
            min-height: 1.9rem;
        }

        .penerimaan-detail-table input,
        .penerimaan-detail-table select {
            padding: .2rem .4rem !important;
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

        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type=number] {
            -moz-appearance: textfield;
        }
    </style>
    @php
        $oldItemCodes = old('fitemcode', []);
        $oldItemNames = old('fitemname', []);
        $oldSatuans = old('fsatuan', []);
        $oldRefDtNos = old('frefdtno', []);
        $oldRefDtIds = old('frefdtid', []);
        $oldNoAcaks = old('fnoacak', []);
        $oldRefNoAcaks = old('frefnoacak', []);
        $oldPonos = old('fpono', []);
        $oldRefSoIds = old('frefsoid', []);
        $oldNoUrefs = old('fnouref', []);
        $oldRefPrs = old('frefpr', []);
        $oldPrhIds = old('fprhid', []);
        $oldQtys = old('fqty', []);
        $oldPrices = old('fprice', []);
        $oldTotals = old('ftotal', []);
        $oldDescs = old('fdesc', []);
        $oldKetdts = old('fketdt', []);
        $initialPenerimaanItems = [];

        foreach ($oldItemCodes as $index => $itemCode) {
            $initialPenerimaanItems[] = [
                'fitemcode' => $itemCode,
                'fitemname' => $oldItemNames[$index] ?? '',
                'fsatuan' => $oldSatuans[$index] ?? '',
                'frefdtno' => $oldRefDtNos[$index] ?? '',
                'frefdtid' => $oldRefDtIds[$index] ?? '',
                'fnoacak' => $oldNoAcaks[$index] ?? '',
                'frefnoacak' => $oldRefNoAcaks[$index] ?? '',
                'fpono' => $oldPonos[$index] ?? '',
                'frefsoid' => $oldRefSoIds[$index] ?? '',
                'fnouref' => $oldNoUrefs[$index] ?? '',
                'frefpr' => $oldRefPrs[$index] ?? '',
                'fprhid' => $oldPrhIds[$index] ?? '',
                'fqty' => $oldQtys[$index] ?? 0,
                'fprice' => $oldPrices[$index] ?? 0,
                'ftotal' => $oldTotals[$index] ?? 0,
                'fdesc' => $oldDescs[$index] ?? '',
                'fketdt' => $oldKetdts[$index] ?? '',
            ];
        }
    @endphp
    <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1600px] w-full mx-auto">
        <form action="{{ route('penerimaanbarang.store') }}" method="POST" class="mt-6" data-form-draft="true"
            data-draft-key="penerimaanbarang:create" x-data="mainForm()" x-init="syncSupplierDisplay(@js(old('fsupplier', '')));
            restoreSavedItems(@js($initialPenerimaanItems));
            init()"
            @submit.prevent="submitForm($el)">
            @csrf

            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                    <p class="font-semibold mb-1">Tidak dapat menyimpan</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- HEADER FORM --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                <div class="lg:col-span-4">
                    <label class="block text-sm font-bold mb-1">Cabang</label>
                    <input type="text"
                        class="w-full border rounded px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
                        value="{{ trim(($fbranchcode ?? '') . ($fcabang ?? '' ? ' - ' . $fcabang : '')) }}" disabled>
                    <input type="hidden" name="fbranchcode" value="{{ old('fbranchcode', $fbranchcode) }}">
                </div>

                <div class="lg:col-span-4" x-data="{ autoCode: true }">
                    <label class="block text-sm font-bold mb-1">Transaksi#</label>
                    <div class="flex items-center gap-3">
                        <input type="text" name="fstockmtno" class="w-full border rounded px-3 py-2"
                            :disabled="autoCode" :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'">
                        <label class="inline-flex items-center select-none font-bold">
                            <input type="checkbox" x-model="autoCode" checked>
                            <span class="ml-2 text-sm text-gray-700">Auto</span>
                        </label>
                    </div>
                </div>

                <div class="lg:col-span-4">
                    <label class="block text-sm font-bold mb-1">Supplier</label>
                    <div class="flex">
                        <div class="relative flex-1">
                            <select id="modal_filter_supplier_id" name="filter_supplier_id"
                                class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
                                disabled>
                                <option value=""></option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->fsuppliercode }}"
                                        {{ old('fsupplier', $filterSupplierId) == $supplier->fsuppliercode ? 'selected' : '' }}>
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
                        @if (in_array('createSupplier', explode(',', session('user_restricted_permissions', '')), true))
                            <a href="{{ route('supplier.create') }}" target="_blank" rel="noopener"
                                class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50" title="Tambah Supplier">
                                <x-heroicon-o-plus class="w-5 h-5" />
                            </a>
                        @endif
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
                                        {{ old('ffrom') == $wh->fwhcode ? 'selected' : '' }}>
                                        {{ $wh->fwhcode }} - {{ $wh->fwhname }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="absolute inset-0" role="button" aria-label="Browse warehouse"
                                @click="window.dispatchEvent(new CustomEvent('penerimaanbarang-warehouse-browse-open'))">
                            </div>
                        </div>

                        <input type="hidden" name="ffrom" id="warehouseCodeHidden" value="{{ old('ffrom') }}">
                        <button type="button"
                            @click="window.dispatchEvent(new CustomEvent('penerimaanbarang-warehouse-browse-open'))"
                            class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none" title="Browse Gudang">
                            <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                        </button>

                        <a href="{{ route('gudang.create') }}" target="_blank" rel="noopener"
                            class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50" title="Tambah Gudang">
                            <x-heroicon-o-plus class="w-5 h-5" />
                        </a>
                    </div>

                    @error('ffrom')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="lg:col-span-4">
                    <label class="block text-sm font-bold">Tanggal</label>
                    <input type="date" name="fstockmtdate" value="{{ old('fstockmtdate') ?? date('Y-m-d') }}"
                        class="w-full border rounded px-3 py-2 @error('fstockmtdate') border-red-500 @enderror">
                    @error('fstockmtdate')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="lg:col-span-12">
                    <label class="block text-sm font-bold">Keterangan</label>
                    <textarea name="fket" rows="3"
                        class="w-full border rounded px-3 py-2 @error('fket') border-red-500 @enderror"
                        placeholder="Tulis keterangan tambahan di sini...">{{ old('fket') }}</textarea>
                    @error('fket')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- DETAIL ITEM --}}
            <div class="mt-6 space-y-2">
                <h3 class="text-base font-semibold text-gray-800">Detail Item</h3>

                <div class="overflow-x-auto border rounded">
                    <table class="min-w-full text-sm balanced-detail-table penerimaan-detail-table" data-skip-auto-detail-style="true">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-2 text-left w-10">#</th>
                                <th class="p-2 text-left w-48">Kode Produk</th>
                                <th class="p-2 text-left w-[26rem]">Nama Produk</th>
                                <th class="p-2 text-left w-28">Satuan</th>
                                <th class="p-2 text-left w-32">Ref.PO#</th>
                                <th class="p-2 text-right w-24 whitespace-nowrap">Qty</th>
                                @if($canViewHpp)
                                <th class="p-2 text-right w-28 whitespace-nowrap">@ Harga</th>
                                <th class="p-2 text-right w-32 whitespace-nowrap">Total Harga</th>
                                @endif
                                <th class="p-2 text-center w-20">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <template x-for="(it, i) in savedItems" :key="it.uid || `row-${i}`">
                                <tr class="border-t align-top transition-colors"
                                    :class="activeRow === it.uid ? 'bg-amber-50' : 'hover:bg-gray-50'">

                                    <td class="p-2 text-gray-500" x-text="i + 1"></td>

                                    {{-- Kode Produk --}}
                                    <td class="p-2">
                                        <div class="flex">
                                            <input type="text"
                                                class="flex-1 border rounded px-2 py-1 font-mono text-sm min-w-0 bg-gray-100 text-gray-600 cursor-not-allowed"
                                                x-model.trim="it.fitemcode" @focus="activeRow = it.uid"
                                                @blur="activeRow = null" readonly>
                                        </div>
                                    </td>

                                    {{-- Nama Produk + Deskripsi --}}
                                    <td class="p-2">
                                        <div class="flex w-full max-w-full">
                                            <div class="min-w-0 flex-1 rounded-l border bg-gray-100 px-2 py-1 text-sm leading-5 text-gray-600 whitespace-normal break-words"
                                                x-text="it.fitemname"></div>
                                            <button type="button" @click="openDesc(it)"
                                                class="shrink-0 inline-flex items-center border border-l-0 rounded-r px-2 py-1 transition-colors"
                                                :class="it.fdesc ?
                                                    'border-emerald-300 bg-emerald-50 text-emerald-600 hover:bg-emerald-100' :
                                                    'bg-white text-gray-500 hover:bg-gray-50'"
                                                title="Deskripsi item">
                                                <x-heroicon-o-document-text class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>

                                    {{-- Satuan --}}
                                    <td class="p-2 align-top">
                                        <template x-if="unitOptions(it).length > 1 && !it.frefdtid">
                                            <select class="w-full border rounded px-2 py-1 text-sm" :id="'unit_saved_' + i"
                                                x-model="it.fsatuan" @focus="activeRow = it.uid" @blur="activeRow = null"
                                                @keydown.enter.prevent="focusSavedQty(i)" @change="onRowUpdated(i)">
                                                <template x-for="u in unitOptions(it)" :key="u">
                                                    <option :value="u" x-text="u"></option>
                                                </template>
                                            </select>
                                        </template>
                                        <input type="text" x-show="unitOptions(it).length <= 1 || it.frefdtid"
                                            class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                            :value="it.fsatuan || '-'" disabled>
                                    </td>

                                    {{-- Ref.PO# --}}
                                    <td class="p-2">
                                        <input type="text"
                                            class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                            :value="it.fpono || '-'" disabled>
                                    </td>

                                    {{-- Qty --}}
                                    <td class="p-2 text-right">
                                        <input type="number" class="border rounded px-2 py-1 w-20 text-right text-sm"
                                            x-model.number="it.fqty" :id="'qty_saved_' + i" step="any"
                                            @focus="activeRow = it.uid; $event.target.select()"
                                            @blur="activeRow = null; enforceQtyRow(it);" @input="onRowUpdated(i)"
                                            @change="onRowUpdated(i)" @keydown.enter.prevent="focusSavedPrice(i)">
                                    </td>

                                    {{-- @ Harga --}}
                                    @if ($canViewHpp)
                                    <td class="p-2 text-right">
                                        <input type="text" inputmode="decimal"
                                            class="border rounded px-2 py-1 w-28 text-right text-sm"
                                            x-model="it.fpriceInput" @input="onPriceInput(it)" :id="'price_saved_' + i"
                                            @focus="activeRow = it.uid; focusPriceInput(it); $event.target.select()"
                                            @blur="activeRow = null; blurPriceInput(it)" @change="recalc(it)"
                                            @keydown.enter.prevent="focusSavedDisc(i)">
                                    </td>
                                    @endif

                                    {{-- Total Harga --}}
                                    @if ($canViewHpp)
                                    <td class="p-2">
                                        <input type="text"
                                            class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm text-right"
                                            :value="formatTransactionAmount(it.ftotal)" disabled>
                                    </td>
                                    @endif

                                    <td class="p-2 text-center">
                                        <div class="flex items-center justify-center">
                                            <button type="button" @click="removeSaved(i)"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded bg-red-100 text-red-600 hover:bg-red-200"
                                                title="Hapus baris">-</button>
                                        </div>
                                    </td>

                                    {{-- Hidden inputs --}}
                                    <td class="hidden">
                                        <input type="hidden" name="fitemcode[]" :value="it.fitemcode">
                                        <input type="hidden" name="fitemname[]" :value="it.fitemname">
                                        <input type="hidden" name="fsatuan[]" :value="it.fsatuan">
                                        <input type="hidden" name="frefdtno[]" :value="it.frefdtno">
                                        <input type="hidden" name="frefdtid[]" :value="it.frefdtid">
                                        <input type="hidden" name="fnoacak[]" :value="it.fnoacak">
                                        <input type="hidden" name="frefnoacak[]" :value="it.frefnoacak">
                                        <input type="hidden" name="fpono[]" :value="it.fpono">
                                        <input type="hidden" name="frefsoid[]" :value="it.frefsoid">
                                        <input type="hidden" name="fnouref[]" :value="it.fnouref">
                                        <input type="hidden" name="frefpr[]" :value="it.frefpr">
                                        <input type="hidden" name="fprhid[]" :value="it.fprhid">
                                        <input type="hidden" name="fqty[]" :value="it.fqty">
                                        <input type="hidden" name="fprice[]" :value="it.fprice">
                                        <input type="hidden" name="ftotal[]" :value="it.ftotal">
                                        <input type="hidden" name="fdesc[]" :value="it.fdesc">
                                        <input type="hidden" name="fketdt[]" :value="it.fketdt">
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- Add PO + Panel Totals --}}
                <div x-data="pohFormModal()">
                    <div class="mt-3 flex justify-between items-start gap-4">
                        <div class="flex justify-start">
                            <button type="button" @click="openModal()"
                                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Add PO
                            </button>
                        </div>

                        {{-- Panel Totals --}}
                        @if($canViewHpp)
                        <div class="w-[480px] shrink-0">
                            <div class="rounded-lg border bg-gray-50 p-3 space-y-2 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Total Harga</span>
                                    <span class="font-medium" x-text="fmtCurr(totalHarga)"></span>
                                </div>
                            </div>
                            <input type="hidden" name="famountponet" :value="totalHarga">
                        </div>
                        @endif
                    </div>

                    {{-- Modal backdrop --}}
                    <div x-show="show" x-transition.opacity class="fixed inset-0 z-40 bg-black/50"
                        @keydown.escape.window="closeModal()"></div>

                    {{-- ============================================================
                         MODAL PO â€” SATU MODAL SAJA (hapus duplikat sebelumnya)
                         ============================================================ --}}
                    <div x-show="show" x-cloak x-transition.opacity
                        class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-8" aria-modal="true"
                        role="dialog">

                        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-5xl flex flex-col overflow-hidden"
                            style="height: 650px;">

                            <!-- Header -->
                            <div
                                class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-teal-50 to-white">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800">Add PO</h3>
                                    <p class="text-sm text-gray-500 mt-0.5">Pilih Purchase Order yang diinginkan</p>
                                </div>
                                <button type="button" @click="closeModal()"
                                    class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                                    Tutup
                                </button>
                            </div>

                            <!-- Table -->
                            <div class="flex-1 overflow-y-auto px-6 pt-4" style="min-height: 0;">
                                <table id="poTable" class="min-w-full text-sm display nowrap stripe hover"
                                    style="width:100%">
                                    <thead>
                                        <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                            <th
                                                class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                PO No</th>
                                            <th
                                                class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                Ref No PO</th>
                                            <th
                                                class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                Supplier</th>
                                            <th
                                                class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                Tanggal</th>
                                            <th
                                                class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>

                            <!-- Footer pagination -->
                            <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50"></div>
                        </div>
                    </div>

                    {{-- MODAL DUPLIKASI --}}
                    <div x-show="showDupModal" x-cloak x-transition.opacity
                        class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                        <div class="absolute inset-0 bg-black/50" @click="closeDupModal()"></div>

                        <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden">
                            <div class="px-5 py-4 border-b flex items-center gap-2 bg-amber-50">
                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <h3 class="text-lg font-semibold text-gray-800">Item Duplikat Ditemukan</h3>
                            </div>

                            <div class="px-5 py-4 space-y-3">
                                <p class="text-sm text-gray-700">
                                    Ditemukan <span class="font-semibold text-amber-600" x-text="dupCount"></span>
                                    item duplikat. Item duplikat <span class="font-semibold">tidak akan ditambahkan</span>.
                                </p>

                                <div class="rounded-lg border border-amber-200 bg-amber-50">
                                    <div class="px-3 py-2 border-b border-amber-200 text-sm font-medium text-gray-800">
                                        Preview Item Duplikat
                                    </div>
                                    <ul class="max-h-40 overflow-auto divide-y divide-amber-100">
                                        <template x-for="d in dupSample" :key="`${d.fitemcode}::${d.fitemname}`">
                                            <li
                                                class="px-3 py-2 text-sm flex items-center gap-2 hover:bg-amber-100 transition-colors">
                                                <span
                                                    class="inline-flex w-5 h-5 items-center justify-center rounded-full bg-amber-200 text-amber-800 text-xs font-bold">!</span>
                                                <span class="font-mono font-medium text-gray-700"
                                                    x-text="d.fitemcode || '-'"></span>
                                                <span class="text-gray-400">â€¢</span>
                                                <span class="text-gray-600 truncate" x-text="d.fitemname || '-'"></span>
                                            </li>
                                        </template>
                                    </ul>
                                    <div x-show="dupCount > 6"
                                        class="px-3 py-2 text-xs text-center text-amber-700 border-t border-amber-200">
                                        ... dan <span x-text="dupCount - 6"></span> item lainnya
                                    </div>
                                </div>
                            </div>

                            <div class="px-5 py-3 border-t bg-gray-50 flex items-center justify-end gap-2">
                                <button type="button" @click="closeDupModal()"
                                    class="h-9 px-4 rounded-lg border-2 border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-100 transition-colors">
                                    Batal
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div x-show="showDescModal" x-cloak class="fixed inset-0 z-[95] flex items-center justify-center"
                    x-transition.opacity>
                    <div class="absolute inset-0 bg-black/50" @click="closeDesc()"></div>
                    <div class="relative bg-white w-[92vw] max-w-lg rounded-2xl shadow-2xl overflow-hidden"
                        x-transition.scale>
                        <div class="px-5 py-4 border-b flex items-center">
                            <x-heroicon-o-document-text class="w-6 h-6 text-blue-600 mr-2" />
                            <h3 class="text-lg font-semibold text-gray-800">Isi Deskripsi Item</h3>
                        </div>
                        <div class="px-5 py-4 space-y-4">
                            <div>
                                <div class="mb-1 flex items-center justify-between gap-3">
                                    <div class="text-sm text-gray-700">Nama Produk</div>
                                    <button type="button" @click="copyDescName()"
                                        class="h-8 px-3 rounded-lg bg-blue-50 text-blue-700 text-sm font-medium hover:bg-blue-100">
                                        Copy
                                    </button>
                                </div>
                                <div class="rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800"
                                    x-text="descItemName || '-'"></div>
                            </div>
                            <label class="block text-sm text-gray-700 font-bold">Deskripsi</label>
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

                <div x-show="showWarningModal" x-cloak class="fixed inset-0 z-[96] flex items-center justify-center"
                    x-transition.opacity>
                    <div class="absolute inset-0 bg-black/50" @click="closeWarning()"></div>
                    <div class="relative bg-white w-[92vw] max-w-lg rounded-2xl shadow-2xl overflow-hidden"
                        x-transition.scale>
                        <div class="px-5 py-4 border-b flex items-center">
                            <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-amber-500 mr-2" />
                            <h3 class="text-lg font-semibold text-gray-800" x-text="warningTitle"></h3>
                        </div>
                        <div class="px-5 py-4 space-y-3">
                            <p class="text-sm text-gray-700" x-text="warningMessage"></p>
                            <template x-if="warningItems.length > 0">
                                <ul class="list-disc pl-5 text-sm text-gray-700 space-y-1">
                                    <template x-for="item in warningItems" :key="item">
                                        <li x-text="item"></li>
                                    </template>
                                </ul>
                            </template>
                        </div>
                        <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
                            <button type="button" @click="closeWarning()"
                                class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">Tutup</button>
                            <button type="button" x-show="warningCanProceed" @click="confirmWarningAndSubmit()"
                                class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Lanjut
                                Simpan</button>
                        </div>
                    </div>
                </div>

                <input type="hidden" id="itemsCount" :value="savedItems.length">
            </div>

            {{-- MODAL: belum ada item --}}
            <div x-show="showNoItems" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center"
                x-transition.opacity>
                <div class="absolute inset-0 bg-black/50" @click="showNoItems=false"></div>
                <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden">
                    <div class="px-5 py-4 border-b flex items-center">
                        <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-500 mr-2" />
                        <h3 class="text-lg font-semibold text-gray-800">Tidak Ada Item</h3>
                    </div>
                    <div class="px-5 py-4">
                        <p class="text-sm text-gray-700">Anda belum menambahkan item. Silakan isi baris "Detail Item"
                            terlebih dahulu.</p>
                    </div>
                    <div class="px-5 py-3 border-t flex justify-end">
                        <button type="button" @click="showNoItems=false"
                            class="h-9 px-4 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">OK</button>
                    </div>
                </div>
            </div>

            {{-- MODAL: supplier belum dipilih --}}
            <div x-show="showNoSupplier" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center"
                x-transition.opacity>
                <div class="absolute inset-0 bg-black/50" @click="showNoSupplier=false"></div>
                <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden">
                    <div class="px-5 py-4 border-b flex items-center">
                        <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-amber-500 mr-2" />
                        <h3 class="text-lg font-semibold text-gray-800">Supplier Belum Dipilih</h3>
                    </div>
                    <div class="px-5 py-4">
                        <p class="text-sm text-gray-700">Silakan pilih <strong>Supplier</strong> terlebih dahulu sebelum
                            menambahkan item.</p>
                    </div>
                    <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
                        <button type="button" @click="showNoSupplier=false"
                            class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">Tutup</button>
                        <button type="button"
                            @click="showNoSupplier=false; window.dispatchEvent(new CustomEvent('supplier-browse-open'))"
                            class="h-9 px-4 rounded-lg bg-amber-500 text-white text-sm font-medium hover:bg-amber-600">
                            Pilih Supplier
                        </button>
                    </div>
                </div>
            </div>

            {{-- MODAL: Produk duplikat --}}
            <div x-show="showDupItemModal" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center"
                x-transition.opacity>
                <div class="absolute inset-0 bg-black/50" @click="showDupItemModal=false"></div>
                <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden">
                    <div class="px-5 py-4 border-b flex items-center">
                        <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-500 mr-2" />
                        <h3 class="text-lg font-semibold text-gray-800">Produk Sudah Ada</h3>
                    </div>
                    <div class="px-5 py-4 space-y-1">
                        <p class="text-sm text-gray-700">
                            Produk <strong x-text="dupItemName"></strong>
                            <template x-if="dupItemSatuan">
                                <span> (<span x-text="dupItemSatuan"></span>)</span>
                            </template>
                            sudah ada di daftar item.
                        </p>
                        <p class="text-sm text-gray-500">Satu produk dengan satuan yang sama hanya boleh ditambahkan satu
                            kali.</p>
                    </div>
                    <div class="px-5 py-3 border-t flex justify-end">
                        <button type="button" @click="showDupItemModal=false"
                            class="h-9 px-4 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700">OK</button>
                    </div>
                </div>
            </div>

            <x-transaction.browse-supplier-modal :open-delay="50" :destroy-on-close="true" />
            <x-transaction.browse-warehouse-modal event-name="penerimaanbarang-warehouse-browse-open" />

            <x-transaction.browse-product-modal />

            <div class="mt-8 flex justify-center gap-4">
                <button type="submit"
                    class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                    <x-heroicon-o-check class="w-5 h-5 mr-2" /> Simpan
                </button>
                <button type="button" onclick="window.location.href='{{ route('penerimaanbarang.index') }}'"
                    class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" /> Keluar
                </button>
            </div>
        </form>
    </div>

@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
@endpush

@push('scripts')
    <script>
        window.PRODUCT_MAP = @json($productMap ?? []);

        function mainForm() {
            function cryptoRandom() {
                if (window.crypto && window.crypto.randomUUID) return window.crypto.randomUUID();
                return `uid-${Date.now()}-${Math.random().toString(16).slice(2)}`;
            }

            function newRow() {
                return {
                    uid: null,
                    fitemcode: '',
                    fitemname: '',
                    units: [],
                    fsatuan: '',
                    frefdtno: '',
                    fnoacak: '',
                    frefnoacak: '',
                    fnouref: '',
                    frefpr: '',
                    fprhid: '',
                    fprno: '',
                    fpono: '',
                    fqty: 0,
                    fprice: 0,
                    fpriceInput: '0.00',
                    ftotal: 0,
                    fdesc: '',
                    fketdt: '',
                    maxqty: 0,
                    fqtypr: 0,
                    fqtypr_satuan: '',
                    fsatuankecil: '',
                    fsatuanbesar: '',
                    fsatuanbesar2: '',
                    fqtykecil: 0,
                    fqtykecil2: 0,
                    maxqty_satuan: '',
                    unit_ratios: {
                        satuankecil: 1,
                        satuanbesar: 1,
                        satuanbesar2: 1
                    },
                    frefdtid: '',
                    fqtykecil_ref: 0,
                    fqtypo: 0,
                    fqtysisapo: 0,
                    fqtyditer: 0,
                    fqtymaxedit: 0,
                };
            }

            return {
                autoCode: true,
                selectedCurrId: '',
                selectedCurrCode: 'IDR',
                rateValue: 1,
                savedItems: [],
                activeRow: null,
                browseTarget: null,
                showNoItems: false,
                showNoSupplier: false,
                showDupItemModal: false,
                dupItemName: '',
                dupItemSatuan: '',
                showDescModal: false,
                descValue: '',
                descItemName: '',
                _descTarget: null,
                showWarningModal: false,
                warningTitle: 'Perhatian',
                warningMessage: '',
                warningItems: [],
                warningCanProceed: false,
                pendingSubmitForm: null,
                pendingValidRows: [],
                minimumVisibleRows: 5,

                rowHasContent(row) {
                    if (!row) return false;
                    return this.isRowFilled(row);
                },

                ensureMinimumRows() {
                    while (this.savedItems.length < this.minimumVisibleRows) {
                        this.savedItems.push(this.createRow());
                    }
                },

                ensureTrailingRow(index = null) {
                    if (!this.savedItems.length) {
                        this.ensureMinimumRows();
                        return;
                    }

                    const targetIndex = index === null ? this.savedItems.length - 1 : index;
                    if (targetIndex !== this.savedItems.length - 1) return;

                    if (this.rowHasContent(this.savedItems[targetIndex])) {
                        this.savedItems.push(this.createRow());
                    }
                },

                onRowUpdated(index = null) {
                    const row = typeof index === 'number' ? this.savedItems[index] : null;
                    if (row) {
                        this.recalc(row);
                        this.calcMaxQty(row);
                    }
                    this.ensureTrailingRow(index);
                },

                get totalHarga() {
                    return this.savedItems.reduce((sum, item) => sum + Number(item.ftotal || 0), 0);
                },

                fmtCurr(n) {
                    const value = Number(n || 0);
                    if (!Number.isFinite(value)) return '0,00';
                    return value.toLocaleString('id-ID', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                },
                rupiah(n) {
                    return this.fmtCurr(n);
                },
                formatStockLimit(itemCode, qty, satuan) {
                    const code = String(itemCode || '').trim();
                    const unit = String(satuan || '').trim();
                    if (!code || !unit) return '';

                    const meta = window.PRODUCT_MAP?.[code];
                    if (!meta) return '';

                    const stock = Number(meta.stock ?? 0);
                    const qtyValue = Number(qty || 0);
                    const stockText = `${this.fmtCurr(stock)} ${unit}`;

                    if (!(qtyValue > 0)) {
                        return `Stok: ${stockText}`;
                    }

                    const statusClass = qtyValue > stock ? 'text-red-600' : 'text-gray-500';
                    return `<span class="${statusClass}">Stok: ${stockText}</span>`;
                },

                recalc(row) {
                    const qty = Math.max(0, Number(row.fqty || 0));
                    const price = Math.max(0, Number(row.fprice || 0));
                    row.fqty = qty;
                    row.fprice = price;
                    if (typeof row.fpriceInput === 'undefined') {
                        row.fpriceInput = this.fmt(price);
                    }
                    row.ftotal = +(qty * price).toFixed(2);
                },
                sanitizePriceValue(value) {
                    let str = (value ?? '').toString().trim();
                    if (str === '') return '';
                    if (str.includes(',')) {
                        str = str.replace(/\./g, '').replace(',', '.');
                    }
                    const raw = str.replace(/[^0-9.]/g, '');
                    const parts = raw.split('.');
                    if (parts.length <= 1) return raw;
                    return `${parts.shift()}.${parts.join('')}`;
                },
                focusPriceInput(row) {
                    const price = Math.max(0, Number(row.fprice || 0));
                    row.fpriceInput = price > 0 ? this.fmt(price) : '';
                },
                onPriceInput(row) {
                    row.fpriceInput = this.sanitizePriceValue(row.fpriceInput);
                    row.fprice = Math.max(0, +(row.fpriceInput || 0));
                    this.recalc(row);
                },
                blurPriceInput(row) {
                    row.fprice = Math.max(0, +(this.sanitizePriceValue(row.fpriceInput) || 0));
                    row.fpriceInput = this.fmt(row.fprice);
                    this.recalc(row);
                },
                enforceQtyRow(row) {
                    const n = Number(row.fqty || 0);
                    if (!Number.isFinite(n) || n < 0.001) {
                        row.fqty = 0.001;
                    }
                },
                enforcePoQtyRow(row) {
                    this.enforceQtyRow(row);
                },
                formatPoRemainHint(row) {
                    const maxQty = Number(this.calcMaxQty(row) || 0);
                    if (!(maxQty > 0)) return '';
                    const used = Number(row.fqty || 0);
                    const remain = Math.max(0, maxQty - used);
                    return `Sisa PO: ${this.fmtCurr(remain)} ${row.fsatuan || ''}`.trim();
                },

                openDesc(targetRow) {
                    this._descTarget = targetRow;
                    this.descItemName = targetRow?.fitemname || '';
                    this.descValue = targetRow?.fdesc || '';
                    this.showDescModal = true;
                },
                copyDescName() {
                    this.descValue = this.descItemName || '';
                },
                closeDesc() {
                    this.showDescModal = false;
                    this._descTarget = null;
                    this.descItemName = '';
                    this.descValue = '';
                },
                applyDesc() {
                    if (this._descTarget) {
                        this._descTarget.fdesc = this.descValue;
                        const index = this.savedItems.findIndex((row) => row.uid === this._descTarget.uid);
                        this.onRowUpdated(index >= 0 ? index : null);
                    }
                    this.closeDesc();
                },

                productMeta(code) {
                    const key = (code || '').trim();
                    const meta = window.PRODUCT_MAP?.[key];
                    if (!meta) {
                        return {
                            name: '',
                            default_unit: '',
                            units: [],
                            stock: 0,
                            unit_ratios: {
                                satuankecil: 1,
                                satuanbesar: 1,
                                satuanbesar2: 1
                            }
                        };
                    }
                    return meta;
                },
                normalizeNoAcak(value) {
                    const normalized = String(value ?? '').trim();
                    return /^\d{3}$/.test(normalized) ? normalized : '';
                },
                generateUniqueNoAcak() {
                    const used = new Set(this.savedItems.map(item => this.normalizeNoAcak(item.fnoacak)).filter(Boolean));
                    let candidate = '';
                    do {
                        candidate = Array.from({
                            length: 3
                        }, () => '123456789' [Math.floor(Math.random() * 9)]).join('');
                    } while (used.has(candidate));
                    return candidate;
                },
                calcMaxQty(row) {
                    const eq = (a, b) => (a || '').trim().toLowerCase() === (b || '').trim().toLowerCase();
                    const editMax = Number(row.fqtymaxedit ?? 0);
                    if (Number.isFinite(editMax) && editMax > 0) return editMax;

                    const satuanPO = (row.fsatuan || '').trim();
                    const satKecil = (row.fsatuankecil || '').trim();
                    const satBesar = (row.fsatuanbesar || '').trim();
                    const satBesar2 = (row.fsatuanbesar2 || '').trim();
                    const rasio = Number(row.fqtykecil || 0);
                    const rasio2 = Number(row.fqtykecil2 || 0);

                    const hasRemainField = row.fqtykecil_ref !== undefined && row.fqtykecil_ref !== null && row
                        .fqtykecil_ref !== '';
                    if (!hasRemainField) return 0;
                    const sisaKecil = Math.max(0, Number(row.fqtykecil_ref) || 0);

                    if (!satuanPO || eq(satuanPO, satKecil)) return sisaKecil;
                    if (eq(satuanPO, satBesar) && rasio > 0) return Math.floor(sisaKecil / rasio);
                    if (eq(satuanPO, satBesar2) && rasio2 > 0) return Math.floor(sisaKecil / rasio2);
                    return sisaKecil;
                },
                async applyLastPrice(row) {
                    const supplier = this.getSupplier();
                    const code = (row.fitemcode || '').trim();
                    const satuan = (row.fsatuan || '').trim();
                    if (!code || !supplier || !satuan || typeof window.fetchLastPrice !== 'function') return;
                    const hist = await window.fetchLastPrice(code, supplier, satuan);
                    if (!hist) return;
                    if (!row.fprice || row.fprice === 0) {
                        row.fprice = hist.fprice;
                        row.fpriceInput = Number(row.fprice || 0).toFixed(2);
                        this.recalc(row);
                    }
                },
                hydrateRowFromMeta(row, meta, forceDefaultUnit = false) {
                    if (!meta) {
                        const fallbackUnits = [
                            row.fsatuankecil || '',
                            row.fsatuanbesar || '',
                            row.fsatuanbesar2 || '',
                        ].map(u => (u ?? '').toString().trim()).filter(Boolean).filter((value, index, self) => self
                            .indexOf(value) === index);
                        row.units = fallbackUnits;
                        if (!(row.fsatuan || '').trim()) {
                            row.fsatuan = fallbackUnits[0] || '';
                        }
                        return;
                    }
                    row.fitemname = meta.name || row.fitemname || '';
                    row.fsatuankecil = row.fsatuankecil || meta.fsatuankecil || '';
                    row.fsatuanbesar = row.fsatuanbesar || meta.fsatuanbesar || '';
                    row.fsatuanbesar2 = row.fsatuanbesar2 || meta.fsatuanbesar2 || '';
                    row.fqtykecil = Number(row.fqtykecil ?? meta.fqtykecil ?? 0);
                    row.fqtykecil2 = Number(row.fqtykecil2 ?? meta.fqtykecil2 ?? 0);
                    const units = [...new Set((meta.units || []).map(u => (u ?? '').toString().trim()).filter(Boolean))];
                    const currentSatuan = (row.fsatuan || '').trim();
                    const defaultUnit = (meta.default_unit || '').toString().trim();
                    const resolvedDefaultUnit = defaultUnit && units.includes(defaultUnit) ? defaultUnit : (units[0] || '');
                    if (currentSatuan && !units.includes(currentSatuan)) units.unshift(currentSatuan);
                    row.units = units;
                    if (forceDefaultUnit) {
                        row.fsatuan = resolvedDefaultUnit;
                    } else if (!currentSatuan) {
                        row.fsatuan = resolvedDefaultUnit;
                    }
                },
                unitOptions(row) {
                    const options = [
                        ...(Array.isArray(row?.units) ? row.units : []),
                        row?.fsatuankecil || '',
                        row?.fsatuanbesar || '',
                        row?.fsatuanbesar2 || '',
                    ].map(u => (u ?? '').toString().trim()).filter(Boolean);
                    const currentSatuan = (row?.fsatuan || '').toString().trim();
                    if (currentSatuan) options.unshift(currentSatuan);
                    return options.filter((value, index, self) => self.indexOf(value) === index);
                },
                metaFromProductPayload(product) {
                    if (!product) return null;
                    const smallUnit = (product.fsatuankecil ?? '').toString().trim();
                    const largeUnit = (product.fsatuanbesar ?? '').toString().trim();
                    const largeUnit2 = (product.fsatuanbesar2 ?? '').toString().trim();
                    const defaultKey = (product.fsatuandefault ?? '').toString().trim();
                    const upperDefaultKey = defaultKey.toUpperCase();
                    const defaultUnit = ({
                        '1': smallUnit,
                        '2': largeUnit,
                        '3': largeUnit2,
                    }[defaultKey] || ([smallUnit, largeUnit, largeUnit2].find((unit) => unit.toUpperCase() ===
                        upperDefaultKey) || smallUnit || largeUnit || largeUnit2));
                    const units = [defaultUnit, smallUnit, largeUnit, largeUnit2]
                        .filter(Boolean)
                        .filter((value, index, self) => self.indexOf(value) === index);
                    return {
                        id: product.fprdid ?? null,
                        name: product.fprdname ?? '',
                        default_unit: defaultUnit,
                        units,
                        stock: Number(product.fminstock ?? 0),
                        fsatuankecil: product.fsatuankecil ?? '',
                        fsatuanbesar: product.fsatuanbesar ?? '',
                        fsatuanbesar2: product.fsatuanbesar2 ?? '',
                        fqtykecil: Number(product.fqtykecil ?? 0),
                        fqtykecil2: Number(product.fqtykecil2 ?? 0),
                        unit_ratios: {
                            satuankecil: 1,
                            satuanbesar: Number(product.fqtykecil ?? 1),
                            satuanbesar2: Number(product.fqtykecil2 ?? 1),
                        }
                    };
                },
                onCodeTypedRow(row) {
                    this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode), true);
                },
                onCodeTypedSaved(item, index = null) {
                    this.hydrateRowFromMeta(item, this.productMeta(item.fitemcode), true);
                    this.onRowUpdated(index);
                },

                getSupplier() {
                    return (document.getElementById('supplierCodeHidden')?.value || '').trim();
                },
                syncSupplierDisplay(code) {
                    const supplierCode = (code || '').toString().trim();
                    const sel = document.getElementById('modal_filter_supplier_id');
                    const hid = document.getElementById('supplierCodeHidden');
                    if (hid) hid.value = supplierCode;
                    if (!sel) return;
                    let found = false;
                    Array.from(sel.options).forEach((option) => {
                        const selected = String(option.value) === supplierCode;
                        option.selected = selected;
                        if (selected) found = true;
                    });
                    if (!found && supplierCode) sel.add(new Option(supplierCode, supplierCode, true, true));
                    sel.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                },
                restoreSavedItems(items = []) {
                    this.savedItems = Array.isArray(items) ? items.filter(i => (i?.fitemcode || '').toString().trim() !==
                        '').map(i => ({
                        ...newRow(),
                        ...i,
                        uid: i.uid || cryptoRandom()
                    })) : [];
                    if (this.savedItems.length === 0) {
                        this.savedItems = [this.createRow()];
                    }
                    this.ensureMinimumRows();
                    this.ensureTrailingRow();
                    this.showNoItems = false;
                },
                createRow(source = {}) {
                    return {
                        ...newRow(),
                        ...source,
                        uid: source.uid || cryptoRandom(),
                        fnoacak: this.normalizeNoAcak(source.fnoacak) || this.generateUniqueNoAcak(),
                    };
                },
                isRowSavable(row) {
                    return !!((row.fitemcode || '').trim() && (row.fsatuan || '').trim() && Number(row.fqty) > 0);
                },
                isRowFilled(row) {
                    return [
                            row.fitemcode,
                            row.fitemname,
                            row.fsatuan,
                            row.frefdtno,
                            row.fpono,
                            row.fqty,
                            row.fprice,
                            row.fdesc,
                            row.fketdt
                        ].some((value) => String(value ?? '').trim() !== '' && Number(value ?? 0) !== 0) ||
                        Number(row.fqty || 0) > 0;
                },
                rowWarningLabel(row) {
                    return `Data Produk ${row.fitemname || row.fitemcode || '(tanpa nama)'} qty masih 0, tidak akan tersimpan.`;
                },
                closeWarning() {
                    this.showWarningModal = false;
                    this.warningTitle = 'Perhatian';
                    this.warningMessage = '';
                    this.warningItems = [];
                    this.warningCanProceed = false;
                    this.pendingSubmitForm = null;
                    this.pendingValidRows = [];
                },
                confirmWarningAndSubmit() {
                    if (!this.warningCanProceed || !this.pendingSubmitForm || this.pendingValidRows.length < 1) {
                        this.closeWarning();
                        return;
                    }
                    this.savedItems = this.pendingValidRows.map((row) => ({
                        ...row
                    }));
                    const form = this.pendingSubmitForm;
                    this.closeWarning();
                    this.$nextTick(() => form.submit());
                },
                removeSaved(i) {
                    if (this.savedItems.length === 1) {
                        this.savedItems.splice(0, 1, this.createRow());
                        this.ensureMinimumRows();
                        return;
                    }
                    this.savedItems.splice(i, 1);
                    this.ensureMinimumRows();
                },
                isDupeItem(candidate) {
                    const cPod = String(candidate.frefdtid ?? '').trim();
                    if (cPod) return this.savedItems.some(it => String(it.frefdtid ?? '').trim() === cPod);
                    const cCode = (candidate.fitemcode || '').trim().toLowerCase();
                    return this.savedItems.some(it => (it.fitemcode || '').trim().toLowerCase() === cCode);
                },
                onPrPicked(e) {
                    const {
                        header,
                        items
                    } = e.detail || {};
                    if (!items || !Array.isArray(items)) return;
                    const skipped = [];
                    const incomingRows = [];
                    items.forEach(src => {
                        const fsatuan = (src.fsatuan ?? '').trim();
                        const meta = this.productMeta(src.fitemcode ?? '');
                        const fitemname = meta ? (meta.name || src.fitemname || '') : (src.fitemname ?? '');
                        const candidate = {
                            fitemcode: (src.fitemcode ?? '').trim(),
                            fitemname,
                            fsatuan,
                            frefdtid: src.frefdtid ?? '',
                        };
                        if (this.isDupeItem(candidate)) {
                            skipped.push(src);
                            return;
                        }

                        const units = meta ? [...new Set((meta.units || []).map(u => (u ?? '').toString().trim())
                                .filter(Boolean))] :
                            (Array.isArray(src.units) && src.units.length ? src.units : [fsatuan].filter(Boolean));
                        if (fsatuan && !units.includes(fsatuan)) units.unshift(fsatuan);

                        const row = {
                            uid: cryptoRandom(),
                            fitemcode: src.fitemcode ?? '',
                            fitemname,
                            units,
                            fsatuan: fsatuan || units[0] || '',
                            frefdtno: src.fpono || header?.fpono || '',
                            fnoacak: this.generateUniqueNoAcak(),
                            frefnoacak: this.normalizeNoAcak(src.frefnoacak ?? src.fnoacak ?? ''),
                            fnouref: src.fnouref ?? '',
                            frefpr: String(header?.fprhid ?? src.fprhid ?? ''),
                            fprhid: String(src.fprhid ?? header?.fprhid ?? ''),
                            fprno: String(header?.fpono ?? src.fpono ?? ''),
                            fpono: String(header?.fpono ?? src.fpono ?? ''),
                            fqty: (src.fqty !== null && src.fqty !== undefined && Number(src.fqty) > 0) ?
                                Number(src.fqty) : 1,
                            fqtypo: 0,
                            fqtysisapo: Number(src.fqtysisapo ?? 0),
                            fqtyditer: Number(src.fqtyditer ?? 0),
                            fqtymaxedit: Number(src.fqtymaxedit ?? src.fqtysisapo ?? src.maxqty ?? 0),
                            fqtykecil_ref: Number(src.fqtykecil_ref ?? src.fqtyremain ?? src.fqtykecil_sisa ??
                                0),
                            frefdtid: src.frefdtid ?? '',
                            fsatuankecil: src.fsatuankecil || meta?.fsatuankecil || '',
                            fsatuanbesar: src.fsatuanbesar || meta?.fsatuanbesar || '',
                            fsatuanbesar2: src.fsatuanbesar2 || meta?.fsatuanbesar2 || '',
                            fqtykecil: Number(src.fqtykecil ?? meta?.fqtykecil ?? 0),
                            fqtykecil2: Number(src.fqtykecil2 ?? meta?.fqtykecil2 ?? 0),
                            maxqty_satuan: src.maxqty_satuan ?? '',
                            fprice: Number(src.fprice ?? 0),
                            fpriceInput: Number(src.fprice ?? 0).toFixed(2),
                            ftotal: Number(src.ftotal ?? 0),
                            fdesc: src.fdesc ?? src.fketdt ?? '',
                            fketdt: src.fketdt ?? '',
                        };
                        row.maxqty = this.calcMaxQty(row);
                        if (!(Number(row.maxqty) > 0)) return;
                        if (Number(row.maxqty) > 0) row.fqty = Number(row.maxqty);
                        if (!row.ftotal && row.fqty && row.fprice) row.ftotal = +(row.fqty * row.fprice).toFixed(2);
                        incomingRows.push(row);
                        if (!row.fprice || row.fprice === 0) this.$nextTick(() => this.applyLastPrice(row));
                    });

                    if (incomingRows.length > 0) {
                        const shouldReplaceStarter = this.savedItems.every((row) => !this.isRowFilled(row));
                        if (shouldReplaceStarter) {
                            this.savedItems = incomingRows;
                        } else {
                            this.savedItems.push(...incomingRows);
                        }
                        this.ensureMinimumRows();
                        this.ensureTrailingRow();
                    }

                    if (skipped.length > 0 && this.savedItems.length === 0) {
                        this.showDupItemModal = true;
                        this.dupItemName = skipped.map(s => s.fitemname || s.fitemcode).join(', ');
                        this.dupItemSatuan = '';
                    }
                    if (this.savedItems.length > 0) this.showNoItems = false;
                },
                getCurrentItemKeys() {
                    return this.savedItems.map(it => {
                        const id = (it.frefdtid ?? '').toString().trim();
                        if (id) return `pod:${id}`;
                        return `manual:${(it.fitemcode ?? '').toString().trim()}::${(it.fsatuan ?? '').toString().trim()}`;
                    });
                },
                openBrowseFor(where, idx = null) {
                    return;
                },
                submitForm(form) {
                    const seenCodes = new Set();
                    for (const row of this.savedItems) {
                        const code = (row.fitemcode || '').trim().toUpperCase();
                        if (!code) continue;
                        if (seenCodes.has(code)) {
                            this.warningTitle = 'Produk Duplikat';
                            this.warningMessage = `Kode produk ${code} tidak boleh sama dalam satu Penerimaan Barang.`;
                            this.warningItems = [];
                            this.warningCanProceed = false;
                            this.pendingSubmitForm = null;
                            this.pendingValidRows = [];
                            this.showWarningModal = true;
                            return;
                        }
                        seenCodes.add(code);
                    }

                    const validRows = this.savedItems.filter((row) => this.isRowSavable(row));
                    const warningRows = this.savedItems.filter((row) => this.isRowFilled(row) && !this.isRowSavable(row));

                    if (warningRows.length > 0) {
                        this.warningTitle = 'Qty Belum Diisi';
                        this.warningMessage = validRows.length > 0 ?
                            'Beberapa item tidak akan disimpan karena qty masih 0.' :
                            'Tidak ada item yang bisa disimpan karena qty masih 0 atau data belum lengkap.';
                        this.warningItems = warningRows.map((row) => this.rowWarningLabel(row));
                        this.warningCanProceed = validRows.length > 0;
                        this.pendingSubmitForm = form;
                        this.pendingValidRows = validRows;
                        this.showWarningModal = true;
                        return;
                    }

                    if (validRows.length < 1) {
                        this.showNoItems = true;
                        return;
                    }

                    this.savedItems = validRows.map((row) => ({
                        ...row
                    }));
                    this.$nextTick(() => form.submit());
                },
                init() {
                    this.syncSupplierDisplay(@js(old('fsupplier', '')));
                    this.restoreSavedItems(@js($initialPenerimaanItems));
                    window.penerimaanBarangCreateForm = this;
                    window.getCurrentItemKeys = () => this.getCurrentItemKeys();
                    window.isDupeItem = (candidate) => this.isDupeItem(candidate);
                    window.addEventListener('show-no-supplier', () => {
                        this.showNoSupplier = true;
                    }, {
                        passive: true
                    });
                    window.addEventListener('pr-picked', (e) => this.onPrPicked(e), {
                        passive: true
                    });
                    window.addEventListener('product-chosen', (e) => {
                        return;
                    }, {
                        passive: true
                    });
                }
            };
        }

        window.pohFormModal = function() {
            return {
                show: false,
                table: null,
                showDupModal: false,
                dupCount: 0,
                dupSample: [],
                pendingHeader: null,
                pendingUniques: [],

                initDataTable() {
                    if (this.table) {
                        this.table.destroy();
                        this.table = null;
                    }
                    if (!document.getElementById('poTable')) return;

                    this.table = $('#poTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "{{ route('penerimaanbarang.pickable') }}",
                            type: 'GET',
                            data: function(d) {
                                return {
                                    draw: d.draw,
                                    start: d.start,
                                    length: d.length,
                                    supplier: document.getElementById('supplierCodeHidden')?.value || '',
                                    search: d.search.value,
                                    order_column: d.columns[d.order[0].column].data,
                                    order_dir: d.order[0].dir
                                };
                            }
                        },
                        columns: [{
                                data: 'fpono',
                                name: 'fpono',
                                className: 'font-mono text-sm'
                            },
                            {
                                data: 'fpono',
                                name: 'fpono',
                                className: 'font-mono text-sm'
                            },
                            {
                                data: 'fsuppliername',
                                name: 'fsuppliername',
                                className: 'text-sm',
                                render: d => d || '<span class="text-gray-400">-</span>'
                            },
                            {
                                data: 'fpodate',
                                name: 'fpodate',
                                className: 'text-sm',
                                render: d => {
                                    if (!d || d === 'No Date') return '-';
                                    const date = new Date(d);
                                    if (isNaN(date.getTime())) return '-';
                                    const pad = n => n.toString().padStart(2, '0');
                                    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}`;
                                }
                            },
                            {
                                data: null,
                                orderable: false,
                                searchable: false,
                                className: 'text-center',
                                width: '100px',
                                render: () =>
                                    '<button type="button" class="btn-pick px-4 py-1.5 rounded-md text-sm font-medium bg-teal-600 hover:bg-teal-700 text-white transition-colors duration-150">Pilih</button>'
                            }
                        ],
                        dom: '<"flex flex-col gap-3 md:flex-row md:items-center mb-4"<"w-full md:w-auto"f><"w-full md:w-auto md:ml-auto md:text-right"l>>rt<"flex flex-col gap-3 md:flex-row md:items-center md:justify-between mt-4"i p>',
                        pageLength: 10,
                        lengthMenu: [
                            [10, 25, 50, 100],
                            [10, 25, 50, 100]
                        ],
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
                            [3, 'desc']
                        ],
                        autoWidth: false,
                        initComplete: function() {
                            const $c = $(this.api().table().container());
                            $c.children().first().css({
                                display: 'flex',
                                width: '100%',
                                gap: '12px',
                                alignItems: 'center',
                                justifyContent: 'space-between'
                            });
                            $c.find('.dt-search, .dataTables_filter').css({
                                marginRight: '12px'
                            });
                            $c.find('.dt-length, .dataTables_length').css({
                                marginLeft: 'auto',
                                textAlign: 'right'
                            });
                            $c.find('.dt-search .dt-input, .dataTables_filter input').css({
                                width: '280px',
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

                    const self = this;
                    $('#poTable').off('click.pohpick').on('click.pohpick', '.btn-pick', function() {
                        const data = self.table.row($(this).closest('tr')).data();
                        if (data) self.pick(data);
                    });
                },

                openModal() {
                    this.show = true;
                    this.$nextTick(() => setTimeout(() => this.initDataTable(), 50));
                },
                closeModal() {
                    this.show = false;
                    if (this.table) {
                        this.table.destroy();
                        this.table = null;
                    }
                },
                openDupModal(header, duplicates, uniques) {
                    window.transactionReferenceModalHelper.openDupModal(this, header, duplicates, uniques);
                },
                closeDupModal() {
                    window.transactionReferenceModalHelper.closeDupModal(this);
                },
                confirmAddUniques() {
                    window.transactionReferenceModalHelper.confirmAddUniques(this, 'pr-picked');
                },
                applySupplierFromPo(header, row) {
                    const supplierCode = (header?.fsupplier || row?.fsuppliercode || '').toString().trim();
                    if (!supplierCode) return;

                    const supplierName = (row?.fsuppliername || '').toString().trim();
                    const label = supplierName ? `${supplierName} (${supplierCode})` : supplierCode;
                    const sel = document.getElementById('modal_filter_supplier_id');
                    const hid = document.getElementById('supplierCodeHidden');

                    if (hid) {
                        hid.value = supplierCode;
                        hid.dispatchEvent(new Event('change', {
                            bubbles: true
                        }));
                    }

                    if (!sel) return;

                    let opt = Array.from(sel.options).find(o => String(o.value) === supplierCode);
                    if (!opt) {
                        opt = new Option(label, supplierCode, true, true);
                        sel.add(opt);
                    } else {
                        opt.text = label;
                    }

                    sel.value = supplierCode;
                    Array.from(sel.options).forEach(option => {
                        option.selected = String(option.value) === supplierCode;
                    });
                    sel.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                },
                async pick(row) {
                    try {
                        const url = `{{ route('penerimaanbarang.items', ['id' => 'PO_ID_PLACEHOLDER']) }}`
                            .replace('PO_ID_PLACEHOLDER', row.fpohid);
                        const res = await fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        const json = await res.json();
                        this.applySupplierFromPo(json.header, row);
                        const items = json.items || [];

                        const currentKeys = new Set((window.getCurrentItemKeys?.() || []).map(String));
                        const keyOf = src =>
                            `${(src.fprdcode ?? '').toString().trim()}::${(src.frefdtno ?? '').toString().trim()}`;

                        const duplicates = items.filter(src => currentKeys.has(keyOf(src)));
                        const uniques = items.filter(src => !currentKeys.has(keyOf(src)));

                        if (duplicates.length > 0) {
                            this.openDupModal(row, duplicates, uniques);
                            return;
                        }

                        const formEl = document.querySelector('form[data-draft-key="penerimaanbarang:create"]');
                        const formState = window.penerimaanBarangCreateForm ||
                            (window.Alpine?.$data ? window.Alpine.$data(formEl) : null) ||
                            (Array.isArray(formEl?._x_dataStack) && formEl._x_dataStack.length > 0 ? formEl
                                ._x_dataStack[0] : null) ||
                            formEl?.__x?.$data;

                        if (formState && typeof formState.onPrPicked === 'function') {
                            formState.onPrPicked({
                                detail: {
                                    header: row,
                                    items
                                }
                            });
                        } else {
                            window.dispatchEvent(new CustomEvent('pr-picked', {
                                detail: {
                                    header: row,
                                    items
                                }
                            }));
                        }
                        this.closeModal();
                    } catch (e) {
                        console.error(e);
                        window.showAppErrorAlert('TERJADI KESALAHAN', 'GAGAL MENGAMBIL DETAIL PO.');
                    }
                }
            };
        };
    </script>
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
        @php
            $oldItemCodes = old('fitemcode', []);
            $oldItemNames = old('fitemname', []);
            $oldSatuans = old('fsatuan', []);
            $oldRefDtNos = old('frefdtno', []);
            $oldRefDtIds = old('frefdtid', []);
            $oldNoAcaks = old('fnoacak', []);
            $oldRefNoAcaks = old('frefnoacak', []);
            $oldPonos = old('fpono', []);
            $oldRefSoIds = old('frefsoid', []);
            $oldNoUrefs = old('fnouref', []);
            $oldRefPrs = old('frefpr', []);
            $oldPrhIds = old('fprhid', []);
            $oldQtys = old('fqty', []);
            $oldPrices = old('fprice', []);
            $oldTotals = old('ftotal', []);
            $oldDescs = old('fdesc', []);
            $oldKetdts = old('fketdt', []);
            $initialPenerimaanItems = [];

            foreach ($oldItemCodes as $index => $itemCode) {
                $initialPenerimaanItems[] = [
                    'fitemcode' => $itemCode,
                    'fitemname' => $oldItemNames[$index] ?? '',
                    'fsatuan' => $oldSatuans[$index] ?? '',
                    'frefdtno' => $oldRefDtNos[$index] ?? '',
                    'frefdtid' => $oldRefDtIds[$index] ?? '',
                    'fnoacak' => $oldNoAcaks[$index] ?? '',
                    'frefnoacak' => $oldRefNoAcaks[$index] ?? '',
                    'fpono' => $oldPonos[$index] ?? '',
                    'frefsoid' => $oldRefSoIds[$index] ?? '',
                    'fnouref' => $oldNoUrefs[$index] ?? '',
                    'frefpr' => $oldRefPrs[$index] ?? '',
                    'fprhid' => $oldPrhIds[$index] ?? '',
                    'fqty' => $oldQtys[$index] ?? 0,
                    'fprice' => $oldPrices[$index] ?? 0,
                    'ftotal' => $oldTotals[$index] ?? 0,
                    'fdesc' => $oldDescs[$index] ?? '',
                    'fketdt' => $oldKetdts[$index] ?? '',
                ];
            }
        @endphp
        document.addEventListener('DOMContentLoaded', () => {
            window.addEventListener('warehouse-picked', (ev) => {
                const {
                    fwhcode,
                    fwhname
                } = ev.detail || {};
                const sel = document.getElementById('warehouseSelect');
                const hid = document.getElementById('warehouseCodeHidden');
                if (sel) {
                    const code = String(fwhcode || '').trim();
                    let opt = [...sel.options].find((o) => String(o.value).trim() === code);
                    if (code && !opt) {
                        opt = new Option(fwhname ? `${fwhname} (${code})` : code, code, true, true);
                        sel.add(opt);
                    }
                    sel.value = opt ? opt.value : code;
                    sel.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                }
                if (hid) {
                    hid.value = fwhcode || '';
                    hid.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                }
            });
        });

        // â”€â”€â”€ PRODUCT BROWSER â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    </script>
    @include('components.transaction.browse-warehouse-script', [
        'eventName' => 'penerimaanbarang-warehouse-browse-open',
    ])
    @include('components.transaction.browse-product-script', ['destroyOnClose' => true, 'openDelay' => 50])
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
