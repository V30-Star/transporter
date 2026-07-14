@extends('layouts.app')

@section('title', 'Retur Pembelian - New')

@section('content')
    @php
        $oldReturBeliCodes = old('fitemcode', []);
        $oldReturBeliNames = old('fitemname', []);
        $oldReturBeliUnits = old('fsatuan', []);
        $oldReturBeliQtys = old('fqty', []);
        $oldReturBeliPrices = old('fprice', []);
        $oldReturBeliBiayas = old('fbiaya', []);
        $oldReturBeliDiscs = old('fdiscpersen', []);
        $oldReturBeliTotals = old('ftotprice', []);
        $oldReturBeliDescs = old('fdesc', []);
        $oldReturBeliKetdts = old('fketdt', []);
        $oldReturBeliRefs = old('frefdtno', []);
        $initialReturPembelianItems = [];

        foreach ($oldReturBeliCodes as $index => $itemCode) {
            $code = trim((string) $itemCode);
            $name = trim((string) ($oldReturBeliNames[$index] ?? ''));
            if ($code === '' && $name === '') {
                continue;
            }

            $unit = trim((string) ($oldReturBeliUnits[$index] ?? ''));
            $initialReturPembelianItems[] = [
                'uid' => 'old-returbeli-' . $index,
                'fitemcode' => $code,
                'fitemname' => $name,
                'units' => $unit !== '' ? [$unit] : [],
                'fsatuan' => $unit,
                'fqty' => (float) ($oldReturBeliQtys[$index] ?? 0),
                'fprice' => (float) ($oldReturBeliPrices[$index] ?? 0),
                'fbiaya' => (float) ($oldReturBeliBiayas[$index] ?? 0),
                'fdiscpersen' => $oldReturBeliDiscs[$index] ?? 0,
                'ftotprice' => (float) ($oldReturBeliTotals[$index] ?? 0),
                'fdesc' => (string) ($oldReturBeliDescs[$index] ?? ''),
                'fketdt' => (string) ($oldReturBeliKetdts[$index] ?? ''),
                'frefdtno' => trim((string) ($oldReturBeliRefs[$index] ?? '')),
            ];
        }
    @endphp
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

        input[type=number] {
            -moz-appearance: textfield;
        }

        .desc-inline-field {
            display: flex !important;
            width: 100%;
            min-width: 0;
            align-items: stretch;
            flex-wrap: nowrap !important;
        }

        .desc-inline-field__text {
            min-width: 0;
            flex: 1 1 auto;
        }

        .desc-inline-field__button {
            flex: 0 0 auto;
            width: 2.5rem;
            justify-content: center;
        }

        .retur-detail-table th,
        .retur-detail-table td {
            padding: .25rem .375rem !important;
        }

        .retur-detail-table input:not([type="hidden"]),
        .retur-detail-table select,
        .retur-detail-table button,
        .retur-detail-table .desc-inline-field__text {
            min-height: 1.875rem;
            padding-top: .25rem !important;
            padding-bottom: .25rem !important;
            line-height: 1.25rem;
        }

        .retur-detail-table button {
            display: inline-flex;
            align-items: center;
        }

        .retur-detail-table .desc-inline-field__button {
            width: 2rem;
        }

        input::placeholder,
        textarea::placeholder {
            color: #9ca3af !important;
            font-weight: normal !important;
        }

        input:disabled::placeholder,
        textarea:disabled::placeholder {
            color: #9ca3af !important;
            -webkit-text-fill-color: #9ca3af !important;
            font-weight: normal !important;
        }
    </style>

    <div x-data="{ open: true }">
        <div class="lg:col-span-5">
                        <div>
                <form action="{{ route('returpembelian.store') }}" method="POST" data-form-draft="true"
                    data-draft-key="returpembelian:create" x-data="itemsTable()" x-init="init()"
                    @submit.prevent="
                        const duplicateCode = window.getReturPembelianDuplicateCode?.($el);
                        if (duplicateCode) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Produk Duplikat',
                                text: `Kode produk ${duplicateCode} tidak boleh sama dalam satu Retur Pembelian.`,
                                confirmButtonText: 'OK',
                                customClass: {
                                    confirmButton: 'bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700'
                                }
                            });
                            return;
                        }
                        onSubmit($event);
                        const _completeDrafts = draftRows.filter(dr => dr.fitemcode && dr.fitemname && dr.fsatuan && Number(dr.fqty) > 0);
                        if (savedItems.length > 0 || _completeDrafts.length > 0) { $el.submit(); }
                    ">
                    @csrf

                    {{-- ─── CARD 1: Identitas Retur Pembelian ────────────────────── --}}
                    <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                        <div class="flex items-center gap-2 px-4 pt-3 pb-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Identitas Retur Pembelian</p>
                        </div>
                        <div class="p-4 space-y-3">
                            <div class="grid grid-cols-3 gap-3">
                                {{-- Cabang --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Cabang</label>
                                    <input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                                        value="{{ $fcabang }}" disabled>
                                    <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                                </div>

                                {{-- Transaksi# --}}
                                <div x-data="{ autoCode: true }">
                                    <label class="block text-xs font-bold text-gray-600 mb-1">No.Transaksi#</label>
                                    <div class="flex items-center gap-2">
                                        <input type="text" name="fpono" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                            :disabled="autoCode" :class="autoCode ? 'bg-gray-100 text-gray-500 border-gray-200 cursor-not-allowed' : 'bg-white'"
                                            :placeholder="autoCode ? 'Auto Generated' : ''">
                                        <label class="inline-flex items-center select-none font-medium text-sm text-gray-600 cursor-pointer">
                                            <input type="checkbox" x-model="autoCode" checked class="rounded text-blue-600 border-gray-300 focus:ring-blue-500">
                                            <span class="ml-1.5">Auto</span>
                                        </label>
                                    </div>
                                </div>

                                {{-- Tanggal --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Tanggal <span class="text-red-500">*</span></label>
                                    <input type="date" id="fstockmtdate" name="fstockmtdate"
                                        value="{{ old('fstockmtdate') ?? date('Y-m-d') }}"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fstockmtdate') border-red-400 @enderror">
                                    @error('fstockmtdate')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                {{-- Supplier --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Supplier <span class="text-red-500">*</span></label>
                                    <div class="flex">
                                        <div class="relative flex-1">
                                            <select id="modal_filter_supplier_id" name="filter_supplier_id"
                                                class="w-full border border-gray-300 rounded-l-lg px-3 py-2 text-sm bg-gray-50 text-gray-700 cursor-pointer focus:outline-none focus:border-blue-500"
                                                disabled>
                                                <option value=""></option>
                                                @foreach ($suppliers as $supplier)
                                                    <option value="{{ $supplier->fsuppliercode }}"
                                                        {{ $filterSupplierId == $supplier->fsuppliercode ? 'selected' : '' }}>
                                                        {{ $supplier->fsuppliername }} ({{ $supplier->fsuppliercode }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="absolute inset-0 cursor-pointer" role="button" aria-label="Browse supplier"
                                                @click="window.dispatchEvent(new CustomEvent('supplier-browse-open'))"></div>
                                        </div>
                                        <input type="hidden" name="fsupplier" id="supplierCodeHidden" value="{{ old('fsupplier') }}">
                                        <button type="button"
                                            @click="window.dispatchEvent(new CustomEvent('supplier-browse-open'))"
                                            class="border border-l-0 border-gray-300 px-3 py-2 bg-white hover:bg-gray-50 text-gray-500 transition-colors"
                                            title="Browse Supplier">
                                            <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                        </button>
                                        @if (in_array('createSupplier', explode(',', session('user_restricted_permissions', '')), true))
                                            <a href="{{ route('supplier.create') }}" target="_blank" rel="noopener"
                                                class="border border-l-0 border-gray-300 rounded-r-lg px-3 py-2 bg-white hover:bg-gray-50 text-gray-500 transition-colors"
                                                title="Tambah Supplier">
                                                <x-heroicon-o-plus class="w-4 h-4" />
                                            </a>
                                        @endif
                                    </div>
                                    @error('fsupplier')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Gudang --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Gudang <span class="text-red-500">*</span></label>
                                    <div class="flex">
                                        <div class="relative flex-1">
                                            <select id="warehouseSelect"
                                                class="w-full border border-gray-300 rounded-l-lg px-3 py-2 text-sm bg-gray-50 text-gray-700 cursor-pointer focus:outline-none focus:border-blue-500"
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
                                            <div class="absolute inset-0 cursor-pointer" role="button" aria-label="Browse warehouse"
                                                @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open'))"></div>
                                        </div>
                                        <input type="hidden" name="ffrom" id="warehouseCodeHidden" value="{{ old('ffrom') }}">
                                        <button type="button" @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open'))"
                                            class="border border-l-0 border-gray-300 px-3 py-2 bg-white hover:bg-gray-50 text-gray-500 transition-colors"
                                            title="Browse Gudang">
                                            <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                        </button>
                                        <a href="{{ route('gudang.create') }}" target="_blank" rel="noopener"
                                            class="border border-l-0 border-gray-300 rounded-r-lg px-3 py-2 bg-white hover:bg-gray-50 text-gray-500 transition-colors"
                                            title="Tambah Gudang">
                                            <x-heroicon-o-plus class="w-4 h-4" />
                                        </a>
                                    </div>
                                    @error('ffrom')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ─── CARD 2: Detail Item ────────────────────── --}}
                    <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                        <div class="flex items-center gap-2 px-4 pt-3 pb-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Detail Item</p>
                        </div>
                        <div class="p-4 space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Keterangan</label>
                                <textarea name="fket" rows="2"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fket') border-red-400 @enderror"
                                    placeholder="Tulis keterangan tambahan di sini...">{{ old('fket') }}</textarea>
                                @error('fket')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="overflow-auto border rounded">
                                <table class="fpb-detail-table min-w-full text-sm balanced-detail-table"
                                    data-skip-auto-detail-style="true">
                                    <colgroup>
                                        <col style="width:2%;">
                                        <col style="width:12%;">
                                        <col style="width:25%;">
                                        <col style="width:12%;">
                                        <col style="width:8%;">
                                        <col style="width:8%;">
                                        <col style="width:12%;">
                                        <col style="width:15%;">
                                        <col style="width:6%;">
                                    </colgroup>
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="p-2 text-left w-10">#</th>
                                            <th class="p-2 text-left w-36">Kode Produk</th>
                                            <th class="p-2 text-left w-96">Nama Produk</th>
                                            <th class="p-2 text-left w-28">No Referensi</th>
                                            <th class="p-2 text-left w-20">Satuan</th>
                                            <th class="p-2 text-right w-20 whitespace-nowrap">Qty</th>
                                            <th class="p-2 text-right w-24 whitespace-nowrap">@ Harga</th>
                                            <th class="p-2 text-right w-28 whitespace-nowrap">Total Harga</th>
                                            <th class="p-2 text-center w-24">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {{-- Loop savedItems --}}
                                        <template x-for="(it, i) in savedItems" :key="it.uid">
                                            <tr class="border-t align-top hover:bg-gray-55">
                                                <td class="p-2 text-gray-400" x-text="i + 1"></td>
                                                <td class="p-2">
                                                    <div class="px-2 py-1 text-sm text-gray-600 bg-gray-55 border rounded font-mono" x-text="it.fitemcode"></div>
                                                </td>
                                                <td class="p-2">
                                                    <div class="flex w-full max-w-full">
                                                        <div class="min-w-0 flex-1 rounded-l border bg-gray-100 px-2 py-1 text-sm leading-5 text-gray-600 whitespace-normal break-words"
                                                            x-text="it.fitemname"></div>
                                                        <button type="button" @click="openDesc('saved', i)"
                                                            class="shrink-0 inline-flex items-center border border-l-0 rounded-r bg-slate-50 px-2 py-1 text-slate-700 hover:bg-slate-100 transition-colors border-slate-200"
                                                            :class="it.fdesc ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : ''"
                                                            title="Deskripsi item">
                                                            <x-heroicon-o-document-text class="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                </td>
                                                <td class="p-2">
                                                    <div class="px-2 py-1 text-sm text-gray-600 bg-gray-50 border rounded" x-text="it.frefdtno || '-'"></div>
                                                </td>
                                                <td class="p-2">
                                                    <div class="px-2 py-1 text-sm text-gray-600 bg-gray-50 border rounded" x-text="it.fsatuan || '-'"></div>
                                                </td>
                                                <td class="p-2 text-right">
                                                    <div class="px-2 py-1 text-sm text-gray-700 bg-gray-50 border rounded text-right font-medium" x-text="fmt(it.fqty)"></div>
                                                </td>
                                                <td class="p-2 text-right">
                                                    <div class="px-2 py-1 text-sm text-gray-700 bg-gray-50 border rounded text-right font-medium" x-text="fmt(it.fprice)"></div>
                                                </td>
                                                <td class="p-2 text-right">
                                                    <div class="px-2 py-1 text-sm text-gray-700 bg-gray-50 border rounded text-right font-medium" x-text="fmt(it.ftotprice)"></div>
                                                </td>
                                                <td class="p-2 text-center">
                                                    <div class="flex items-center justify-center gap-1">
                                                        <button type="button" @click="edit(i)"
                                                            class="px-2 py-1 rounded bg-amber-100 text-amber-700 hover:bg-amber-200 text-xs transition-colors">Edit</button>
                                                        <button type="button" @click="removeSaved(i)"
                                                            class="px-2 py-1 rounded bg-red-100 text-red-600 hover:bg-red-200 text-xs transition-colors">-</button>
                                                    </div>
                                                </td>

                                                {{-- hidden inputs --}}
                                                <td class="hidden">
                                                    <input type="hidden" name="fitemcode[]" :value="it.fitemcode">
                                                    <input type="hidden" name="fitemname[]" :value="it.fitemname">
                                                    <input type="hidden" name="fsatuan[]" :value="it.fsatuan">
                                                    <input type="hidden" name="fqty[]" :value="it.fqty">
                                                    <input type="hidden" name="fprice[]" :value="it.fprice">
                                                    <input type="hidden" name="fbiaya[]" :value="it.fbiaya">
                                                    <input type="hidden" name="fdiscpersen[]" :value="it.fdiscpersen">
                                                    <input type="hidden" name="ftotprice[]" :value="it.ftotprice">
                                                    <input type="hidden" name="fdesc[]" :value="it.fdesc">
                                                    <input type="hidden" name="fketdt[]" :value="it.fketdt">
                                                    <input type="hidden" name="frefdtno[]" :value="it.frefdtno">
                                                </td>
                                            </tr>
                                        </template>

                                        {{-- ROW EDIT UTAMA --}}
                                        <tr x-show="editingIndex !== null" class="border-t align-top hover:bg-gray-55 bg-amber-50" x-cloak>
                                            <td class="p-2 text-gray-400" x-text="(editingIndex ?? 0) + 1"></td>

                                            {{-- Kode Produk --}}
                                            <td class="p-2">
                                                <div class="flex">
                                                    <input type="text" class="flex-1 border rounded-l px-2 py-1 font-mono text-sm min-w-0 focus:ring-1 focus:ring-blue-500"
                                                        x-ref="editCode" x-model.trim="editRow.fitemcode"
                                                        @input="onCodeTypedRow(editRow)"
                                                        @keydown.enter.prevent="handleEnterOnCode('edit')">
                                                    <button type="button" @click="openBrowseFor('edit')"
                                                        class="shrink-0 border border-l-0 px-2 py-1 bg-white hover:bg-gray-55 text-gray-500 transition-colors"
                                                        title="Cari Produk">
                                                        <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </td>

                                            {{-- Nama Produk (readonly) --}}
                                            <td class="p-2">
                                                <div class="flex w-full max-w-full">
                                                    <div class="min-w-0 flex-1 rounded-l border bg-gray-105 px-2 py-1 text-sm leading-5 text-gray-600 whitespace-normal break-words"
                                                        x-text="editRow.fitemname"></div>
                                                    <button type="button" @click="openDesc('edit')"
                                                        class="shrink-0 inline-flex items-center border border-l-0 rounded-r bg-slate-50 px-2 py-1 text-slate-700 hover:bg-slate-100 transition-colors border-slate-200"
                                                        :class="editRow.fdesc ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : ''"
                                                        title="Deskripsi item">
                                                        <x-heroicon-o-document-text class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </td>

                                            {{-- No Referensi --}}
                                            <td class="p-2">
                                                <div class="px-2 py-1 text-sm text-gray-600 bg-gray-50 border rounded" x-text="editRow.frefdtno || '-'"></div>
                                            </td>

                                            {{-- Satuan --}}
                                            <td class="p-2">
                                                <template x-if="editRow.units.length > 1">
                                                    <select class="w-full border rounded px-2 py-1 text-sm focus:ring-1 focus:ring-blue-500" x-ref="editUnit"
                                                        x-model="editRow.fsatuan"
                                                        @keydown.enter.prevent="$refs.editRefPr?.focus()">
                                                        <template x-for="u in editRow.units" :key="u">
                                                            <option :value="u" x-text="u"></option>
                                                        </template>
                                                    </select>
                                                </template>
                                                <template x-if="editRow.units.length <= 1">
                                                    <div class="px-2 py-1 text-sm text-gray-600 bg-gray-50 border rounded" x-text="editRow.fsatuan || '-'"></div>
                                                </template>
                                            </td>

                                            {{-- Qty --}}
                                            <td class="p-2 text-right">
                                                <input type="number" class="w-full border rounded px-2 py-1 text-right text-sm focus:ring-1 focus:ring-blue-500"
                                                    min="0" step="0.01" x-ref="editQty"
                                                    x-model.number="editRow.fqty" @input="recalc(editRow)">
                                            </td>

                                            {{-- @ Harga --}}
                                            <td class="p-2 text-right">
                                                <input type="text" class="w-full border rounded px-2 py-1 text-right text-sm focus:ring-1 focus:ring-blue-500"
                                                    x-ref="editPrice" x-model="editRow.fpriceInput"
                                                    @input="onPriceInput(editRow)" @blur="blurPriceInput(editRow)">
                                            </td>

                                            {{-- Total Harga --}}
                                            <td class="p-2 text-right">
                                                <div class="px-2 py-1 text-sm text-gray-700 bg-gray-55 border rounded text-right font-medium" x-text="fmt(editRow.ftotprice)"></div>
                                            </td>

                                            {{-- Aksi --}}
                                            <td class="p-2 text-center">
                                                <div class="flex items-center justify-center gap-1">
                                                    <button type="button" @click="applyEdit()"
                                                        class="px-2 py-1 rounded bg-emerald-600 text-white hover:bg-emerald-700 text-xs transition-colors">Simpan</button>
                                                    <button type="button" @click="cancelEdit()"
                                                        class="px-2 py-1 rounded bg-white border border-gray-305 text-gray-600 hover:bg-gray-55 text-xs transition-colors">Batal</button>
                                                </div>
                                            </td>
                                        </tr>

                                        {{-- ROW DRAFT (multi-row, minimum 5) --}}
                                        <template x-for="(dr, di) in draftRows" :key="dr._uid">
                                            <tr class="border-t align-top hover:bg-gray-55">
                                                <td class="p-2 text-gray-400" x-text="savedItems.length + di + 1"></td>

                                                {{-- Kode Produk --}}
                                                <td class="p-2">
                                                    <div class="flex">
                                                        <input type="text" class="flex-1 border rounded-l px-2 py-1 font-mono text-sm min-w-0 focus:ring-1 focus:ring-blue-500"
                                                            x-model.trim="dr.fitemcode"
                                                            @input="onCodeTypedRow(dr)"
                                                            @keydown.enter.prevent="focusDraftField(di, 'qty')">
                                                        <button type="button" @click="openBrowseFor(di)"
                                                            class="shrink-0 border border-l-0 px-2 py-1 bg-white hover:bg-gray-55 text-gray-500 transition-colors"
                                                            title="Cari Produk">
                                                            <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                </td>

                                                {{-- Nama Produk (readonly) --}}
                                                <td class="p-2">
                                                    <div class="flex w-full max-w-full">
                                                        <div class="min-w-0 flex-1 rounded-l border bg-gray-105 px-2 py-1 text-sm leading-5 text-gray-600 whitespace-normal break-words"
                                                            x-text="dr.fitemname"></div>
                                                        <button type="button" @click="openDesc('draft', di)"
                                                            class="shrink-0 inline-flex items-center border border-l-0 rounded-r bg-slate-50 px-2 py-1 text-slate-700 hover:bg-slate-100 transition-colors border-slate-200"
                                                            :class="dr.fdesc ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : ''"
                                                            title="Deskripsi item">
                                                            <x-heroicon-o-document-text class="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                </td>

                                                {{-- No Referensi --}}
                                                <td class="p-2">
                                                    <div class="px-2 py-1 text-sm text-gray-600 bg-gray-50 border rounded" x-text="dr.frefdtno || '-'"></div>
                                                </td>

                                                {{-- Satuan --}}
                                                <td class="p-2">
                                                    <template x-if="dr.units.length > 1">
                                                        <select class="w-full border rounded px-2 py-1 text-sm focus:ring-1 focus:ring-blue-500"
                                                            x-model="dr.fsatuan">
                                                            <template x-for="u in dr.units" :key="u">
                                                                <option :value="u" x-text="u"></option>
                                                            </template>
                                                        </select>
                                                    </template>
                                                    <template x-if="dr.units.length <= 1">
                                                        <div class="px-2 py-1 text-sm text-gray-605 bg-gray-55 border rounded" x-text="dr.fsatuan || '-'"></div>
                                                    </template>
                                                </td>

                                                {{-- Qty --}}
                                                <td class="p-2 text-right">
                                                    <input type="number" class="w-full border rounded px-2 py-1 text-right text-sm focus:ring-1 focus:ring-blue-500"
                                                        :id="'draft-qty-' + di" min="0" step="0.01"
                                                        x-model.number="dr.fqty" @input="recalc(dr)">
                                                </td>

                                                {{-- @ Harga --}}
                                                <td class="p-2 text-right">
                                                    <input type="text" class="w-full border rounded px-2 py-1 text-right text-sm focus:ring-1 focus:ring-blue-500"
                                                        x-model="dr.fpriceInput"
                                                        @input="onPriceInput(dr)" @blur="blurPriceInput(dr)">
                                                </td>

                                                {{-- Total Harga --}}
                                                <td class="p-2 text-right">
                                                    <div class="px-2 py-1 text-sm text-gray-700 bg-gray-50 border rounded text-right font-medium" x-text="fmt(dr.ftotprice)"></div>
                                                </td>

                                                {{-- Aksi --}}
                                                <td class="p-2 text-center text-xs">
                                                    <button type="button" @click="removeDraftRow(di)"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded bg-red-100 text-red-600 hover:bg-red-200">-</button>
                                                </td>

                                                {{-- hidden inputs for complete draft rows --}}
                                                <template x-if="isComplete(dr)">
                                                    <td class="hidden">
                                                        <input type="hidden" name="fitemcode[]" :value="dr.fitemcode">
                                                        <input type="hidden" name="fitemname[]" :value="dr.fitemname">
                                                        <input type="hidden" name="fsatuan[]" :value="dr.fsatuan">
                                                        <input type="hidden" name="fqty[]" :value="dr.fqty">
                                                        <input type="hidden" name="fprice[]" :value="dr.fprice">
                                                        <input type="hidden" name="fbiaya[]" :value="dr.fbiaya || 0">
                                                        <input type="hidden" name="fdiscpersen[]" :value="dr.fdiscpersen || 0">
                                                        <input type="hidden" name="ftotprice[]" :value="dr.ftotprice">
                                                        <input type="hidden" name="fdesc[]" :value="dr.fdesc || ''">
                                                        <input type="hidden" name="fketdt[]" :value="dr.fketdt || ''">
                                                        <input type="hidden" name="frefdtno[]" :value="dr.frefdtno || ''">
                                                    </td>
                                                </template>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            {{-- MODAL SELECTORS & TOTALS --}}
                            <div class="mt-3 flex justify-between items-start gap-4 flex-wrap">
                                <div class="flex justify-start gap-2" x-data="prhFormModal()">
                                    {{-- Add PR button --}}
                                    <button type="button" @click="openModal()"
                                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-white font-medium text-sm transition-colors hover:bg-emerald-700 focus:outline-none">
                                        <x-heroicon-o-plus class="h-4 w-4" />
                                        Add PR
                                    </button>

                                    <!-- PR Modal backdrop -->
                                    <div x-show="show" x-transition.opacity class="fixed inset-0 z-40 bg-black/55 backdrop-blur-sm"
                                        @keydown.escape.window="closeModal()"></div>

                                    <!-- PR Modal -->
                                    <div x-show="show" x-cloak x-transition.opacity
                                        class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-8"
                                        aria-modal="true" role="dialog">
                                        <div class="relative w-full max-w-5xl rounded-2xl bg-white shadow-2xl flex flex-col overflow-hidden"
                                            style="height: 600px;">
                                            <div
                                                class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-emerald-50 to-white">
                                                <h3 class="text-lg font-bold text-gray-800">Pilih Purchase Request (PR)</h3>
                                                <button type="button" @click="closeModal()"
                                                    class="h-9 px-4 rounded-lg border border-gray-300 bg-white hover:bg-gray-55 font-medium text-gray-700 text-sm transition-colors">Tutup</button>
                                            </div>
                                            <div class="flex-1 overflow-y-auto p-6" style="min-height: 0;">
                                                <table id="prTable"
                                                    class="min-w-full text-sm display nowrap stripe hover"
                                                    style="width:100%">
                                                    <thead class="sticky top-0 z-10">
                                                        <tr class="bg-gray-50 border-b-2 border-gray-200">
                                                            <th class="p-3 text-left font-semibold text-gray-700">PR No</th>
                                                            <th class="p-3 text-left font-semibold text-gray-700">Supplier</th>
                                                            <th class="p-3 text-left font-semibold text-gray-700">Tanggal</th>
                                                            <th class="p-3 text-center font-semibold text-gray-700">Aksi</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody></tbody>
                                                </table>
                                            </div>
                                            <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-55"></div>
                                        </div>
                                    </div>

                                    <!-- Duplicate modal -->
                                    <div x-show="showDupModal" x-cloak x-transition.opacity
                                        class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/40">
                                        <div class="absolute inset-0" @click="closeDupModal()"></div>
                                        <div class="relative bg-white rounded-2xl shadow-xl max-w-xl w-full p-6">
                                            <h3 class="text-lg font-semibold mb-4 text-gray-800">Peringatan Duplikasi</h3>
                                            <p class="mb-4 text-gray-600">
                                                Ditemukan <strong x-text="dupCount"></strong> item yang sudah ada dalam daftar. Hanya item unik yang akan ditambahkan.
                                            </p>
                                            <div class="flex justify-end gap-2">
                                                <button type="button" @click="closeDupModal()"
                                                    class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200 transition-colors">Batal</button>
                                                <button type="button" @click="confirmAddUniques()"
                                                    class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition-colors">Tambahkan Item Unik</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Totals Panel --}}
                                <div class="w-[560px] shrink-0 max-w-full">
                                    <div class="rounded-lg border bg-gray-50 p-4 space-y-3 text-sm">
                                        <div class="flex items-center justify-between">
                                            <span class="font-bold text-gray-800">Total Harga</span>
                                            <span class="font-bold text-gray-900" x-text="formatTransactionAmount(totalHarga)"></span>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <!-- Checkbox -->
                                            <label class="flex items-center gap-1.5 cursor-pointer select-none">
                                                <input id="fapplyppn" name="fapplyppn" type="checkbox" value="1"
                                                    x-model="includePPN"
                                                    class="rounded text-blue-600 border-gray-300 focus:ring-blue-500 h-4 w-4">
                                                <span class="font-bold">PPN</span>
                                            </label>

                                            <!-- Dropdown Include / Exclude -->
                                            <select id="includePPN" name="includePPN" x-model.number="fapplyppn"
                                                x-init="fapplyppn = 0" :disabled="!(includePPN || fapplyppn)"
                                                class="w-28 h-9 px-2 text-sm leading-tight border border-gray-300 rounded transition-opacity appearance-none
                                                       disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed focus:outline-none focus:border-blue-500">
                                                <option value="0">Exclude</option>
                                            </select>

                                            <!-- Input Rate + Nominal -->
                                            <input type="number" min="0" max="100" name="ppn_rate"
                                                step="0.01" x-model.number="ppnRate" :disabled="!(includePPN || fapplyppn)"
                                                class="w-16 h-9 px-2 text-sm leading-tight text-right border border-gray-300 rounded transition-opacity
                                                        [appearance:textfield]
                                                        [&::-webkit-outer-spin-button]:appearance-none
                                                        [&::-webkit-inner-spin-button]:appearance-none
                                                        disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed focus:outline-none focus:border-blue-500">
                                            <span class="text-gray-500">%</span>
                                            <span class="flex-1"></span>
                                            <span class="font-medium" x-text="rupiah(ppnAmount)"></span>
                                        </div>

                                        <div class="border-t my-1"></div>

                                        <div class="flex items-center justify-between text-base">
                                            <span class="font-extrabold text-gray-900">Grand Total</span>
                                            <span class="font-extrabold text-blue-700 text-lg" x-text="rupiah(grandTotal)"></span>
                                        </div>
                                    </div>

                                    <input type="hidden" name="famountponet" :value="totalHarga">
                                    <input type="hidden" name="famountpajak" :value="ppnAmount">
                                    <input type="hidden" name="famountpajak_rp" :value="ppnAmount">
                                    <input type="hidden" name="fincludeppn" :value="includePPN ? 1 : 0">
                                    <input type="hidden" name="famountpo" :value="grandTotal">
                                    <input type="hidden" name="famountpopajak" :value="ppnRate">
                                </div>
                            </div>

                            {{-- MODAL DESC (di dalam itemsTable) --}}
                            <div x-show="showDescModal" x-cloak class="fixed inset-0 z-[95] flex items-center justify-center bg-black/50"
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
                                                    class="h-8 px-3 rounded-lg bg-blue-50 text-blue-700 text-sm font-medium hover:bg-blue-100 transition-colors">
                                                    Copy
                                                </button>
                                            </div>
                                            <div class="rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-850"
                                                x-text="descItemName || '-'"></div>
                                        </div>
                                        <label class="block text-sm text-gray-700 font-bold">Deskripsi</label>
                                        <textarea x-model="descValue" rows="5" class="w-full border rounded px-3 py-2 focus:outline-none focus:border-blue-500"
                                            placeholder="Tulis deskripsi item di sini..."></textarea>
                                    </div>
                                    <div class="px-5 py-3 border-t flex items-center justify-end gap-2 bg-gray-55">
                                        <button type="button" @click="closeDesc()"
                                            class="h-9 px-4 rounded-lg bg-white border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors">
                                            Batal
                                        </button>
                                        <button type="button" @click="applyDesc()"
                                            class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition-colors">
                                            Simpan
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- MODAL: belum ada item --}}
                            <div x-show="showNoItems && savedItems.length === 0" x-cloak
                                class="fixed inset-0 z-[90] flex items-center justify-center bg-black/55" x-transition.opacity>
                                <div class="absolute inset-0 bg-black/50" @click="showNoItems=false"></div>
                                <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden"
                                    x-transition.scale>
                                    <div class="px-5 py-4 border-b flex items-center bg-red-50 text-red-700">
                                        <x-heroicon-o-exclamation-triangle class="w-6 h-6 mr-2" />
                                        <h3 class="text-lg font-semibold">Tidak Ada Item</h3>
                                    </div>
                                    <div class="px-5 py-4">
                                        <p class="text-sm text-gray-700">
                                            Anda belum menambahkan item apa pun pada tabel. Silakan isi baris “Detail Item”
                                            terlebih dahulu.
                                        </p>
                                    </div>
                                    <div class="px-5 py-3 border-t flex items-center justify-end gap-2 bg-gray-50">
                                        <button type="button" @click="showNoItems=false"
                                            class="h-9 px-4 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors">
                                            OK
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" id="itemsCount" :value="savedItems.length">
                        </div>
                    </div>

                    {{-- ─── CARD 3: Aksi / Footer ────────────────────── --}}
                    <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                        <div class="flex items-center justify-end gap-3 px-4 py-3 bg-gray-50">
                            <button type="button"
                                onclick="window.location.href='{{ route('returpembelian.index') }}'"
                                class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 bg-white text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-colors">
                                <x-heroicon-o-arrow-left class="w-4 h-4" />
                                Keluar
                            </button>
                            <button type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                <x-heroicon-o-check class="w-4 h-4" />
                                Simpan
                            </button>
                        </div>
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
    div#productTable_length select,
    .dataTables_wrapper #productTable_length select,
    table#productTable+.dataTables_wrapper .dataTables_length select {
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

    /* Wrapper length */
    div#supplierTable_length,
    .dataTables_wrapper #supplierTable_length,
    .dataTables_wrapper .dataTables_length {
        min-width: 250px !important;
    }

    /* Label wrapper */
    div#supplierTable_length label,
    .dataTables_wrapper #supplierTable_length label,
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

    window.getReturPembelianDuplicateCode = function(form) {
        const seen = new Set();
        const inputs = Array.from(form.querySelectorAll('input[name="fitemcode[]"]'));

        for (const input of inputs) {
            const code = (input.value || '').toString().trim().toUpperCase();
            if (!code) {
                continue;
            }

            if (seen.has(code)) {
                return code;
            }

            seen.add(code);
        }

        return '';
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

    function itemsTable() {
        const _restoredItems = @json($initialReturPembelianItems);
        return {
            showNoItems: false,
            savedItems: [],
            draftRows: [],
            minimumDraftRows: 5,
            editingIndex: null,
            editRow: newRow(),

            totalHarga: 0,
            ppnRate: 11,

            initialGrandTotal: @json($famountmt ?? 0),
            initialPpnAmount: @json($famountpajak ?? 0),

            includePPN: false, // tambah PPN normal di luar total
            fapplyppn: false, // harga sudah termasuk PPN (back-calc)
            // PPN yang SUDAH termasuk (back-calc dari GROSS)
            get ppnIncluded() {
                const total = +this.totalHarga || 0;
                const rate = +this.ppnRate || 0;
                if (!this.fapplyppn) return 0;
                // back-calc from GROSS
                return Math.round((100 / (100 + rate)) * total * (rate / 100));
            },

            // NET dari GROSS jika fapplyppn aktif
            get netFromGross() {
                const total = +this.totalHarga || 0;
                return total - this.ppnIncluded;
            },

            // PPN tambahan (di luar total). Jika sudah include PPN, base = NET (tidak pajak atas pajak)
            get ppnAdded() {
                const rate = +this.ppnRate || 0;
                if (!this.includePPN) return 0;

                const total = +this.totalHarga || 0;

                // When both are ON, compute extra PPN on GROSS (not NET)
                const base = this.fapplyppn ? total : total; // <— effectively: always use total (GROSS)

                return Math.round(base * (rate / 100));
            },

            get ppnAmount() {
                // Jika dua checkbox aktif → tampilkan PPN tambahan saja (hindari double count)
                if (this.includePPN && this.fapplyppn) {
                    return this.ppnAdded;
                }
                // Kasus lain: gabungan PPN yang sudah termasuk + PPN tambahan
                return (this.ppnIncluded ?? 0) + (this.ppnAdded ?? 0);
            },

            get grandTotal() {
                const total = +this.totalHarga || 0;
                if (this.includePPN) return total + this.ppnAdded; // GROSS + extra PPN on GROSS
                if (this.includePPN) return total + this.ppnAdded; // NET + PPN
                if (this.fapplyppn) return total; // GROSS stays GROSS
                return total;
            },

            fmt(n) {
                if (n === null || n === undefined || n === '') return '-';
                const v = Number(n);
                if (!isFinite(v)) return '-';

                return v.toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            },

            rupiah(n) {
                const v = Number(n || 0);
                if (!isFinite(v)) return '-';
                return v.toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            },

            fmtMoney(value) {
                return this.fmt(value);
            },

            recalc(row) {
                row.fqty = Math.max(0, +row.fqty || 0);
                row.fterima = Math.max(0, +row.fterima || 0);
                row.fprice = Math.max(0, +row.fprice || 0);
                if (typeof row.fpriceInput === 'undefined') {
                    row.fpriceInput = this.fmt(row.fprice);
                }
                row.fdiscpersen = Math.min(100, Math.max(0, +row.fdiscpersen || 0));
                row.ftotprice = +(row.fqty * row.fprice * (1 - row.fdiscpersen / 100)).toFixed(2);
                this.recalcTotals();
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

            recalcTotals() {
                const savedSum = (this.savedItems || []).reduce((sum, item) => sum + (item.ftotprice || 0), 0);
                const draftSum = (this.draftRows || [])
                    .filter(item => this.isComplete(item))
                    .reduce((sum, item) => sum + (item.ftotprice || 0), 0);
                this.totalHarga = savedSum + draftSum;
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
                this.ensureMinimumDraftRows();
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
                        fsatuan: src.fsatuan ?? '',
                        frefdtno: src.frefdtno ?? '',
                        fnouref: src.fnouref ?? '',
                        frefpr: src.frefpr ?? (header?.fpono ?? ''),
                        fprhid: src.fprhid ?? header?.fprhid ?? '',
                        fqty: (src.fqty !== null && src.fqty !== undefined && Number(src.fqty) > 0) ? Number(src.fqty) : 1,
                        fterima: Number(src.fterima ?? 0),
                        fprice: Number(src.fprice ?? 0),
                        fpriceInput: this.fmt(Number(src.fprice ?? 0)),
                        fdiscpersen: Number(src.fdiscpersen ?? 0),
                        ftotprice: Number(src.ftotprice ?? 0),
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

            addIfComplete(di) {
                const r = this.draftRows[di];
                if (!r) return;
                if (!this.isComplete(r)) {
                    if (!r.fitemcode || !r.fitemname) {
                        document.getElementById('draft-qty-' + di)?.closest('tr')
                            ?.querySelector('input[type="text"]')?.focus();
                        return;
                    }
                    if (!(Number(r.fqty) > 0)) {
                        document.getElementById('draft-qty-' + di)?.focus();
                        return;
                    }
                    return;
                }

                this.recalc(r);

                const dupe = this.savedItems.find(it => it.fitemcode === r.fitemcode && it.fsatuan === r.fsatuan && (it
                    .frefpr || '') === (r.frefpr || ''));
                if (dupe) {
                    window.showAppWarningAlert('WARNING', 'ITEM SAMA SUDAH ADA.');
                    return;
                }

                this.savedItems.push({
                    ...r,
                    uid: cryptoRandom()
                });
                this.showNoItems = false;
                // Reset this draft row
                this.draftRows.splice(di, 1, newDraftRow());
                this.syncDescList?.();

                this.recalcTotals();
            },

            draftRowHasContent(dr) {
                return !!(dr.fitemcode || dr.fitemname || Number(dr.fqty) > 0 || Number(dr.fprice) > 0);
            },

            removeDraftRow(di) {
                if (this.draftRows.length > this.minimumDraftRows || this.draftRowHasContent(this.draftRows[di])) {
                    this.draftRows.splice(di, 1);
                    this.ensureMinimumDraftRows();
                }
            },

            ensureMinimumDraftRows() {
                while (this.draftRows.length < this.minimumDraftRows) {
                    this.draftRows.push(newDraftRow());
                }
            },

            edit(i) {
                this.editingIndex = i;
                this.editRow = {
                    ...this.savedItems[i]
                };
                this.editRow.fpriceInput = this.fmt(this.editRow.fprice);
                this.hydrateRowFromMeta(this.editRow, this.productMeta(this.editRow.fitemcode));
                this.$nextTick(() => this.$refs.editQty?.focus());
            },

            applyEdit() {
                const r = this.editRow;
                if (!this.isComplete(r)) {
                    window.showAppWarningAlert('WARNING', 'LENGKAPI DATA ITEM.');
                    return;
                }
                this.recalc(r);
                this.savedItems.splice(this.editingIndex, 1, {
                    ...r
                });
                this.cancelEdit();
                this.syncDescList?.();

                // Recalculate totals after applying edit
                this.recalcTotals();
            },

            cancelEdit() {
                this.editingIndex = null;
                this.editRow = newRow();
            },

            removeSaved(i) {
                this.savedItems.splice(i, 1);
                this.syncDescList?.();
                this.recalcTotals();
            },


            onSubmit($event) {
                // Recalculate totals using both savedItems and complete draftRows
                this.recalcTotals();

                // Count total submittable items: savedItems + complete draftRows
                const completeDrafts = this.draftRows.filter(dr => this.isComplete(dr));
                if (this.savedItems.length === 0 && completeDrafts.length === 0) {
                    $event.preventDefault();
                    this.showNoItems = true;
                    return;
                }
                return window.submitFormWithStockMinusConfirmation?.($event);
            },

            handleEnterOnCode(where) {
                if (where === 'edit') {
                    if (this.editRow.units.length > 1) this.$refs.editUnit?.focus();
                    else this.$refs.editQty?.focus();
                }
            },

            showDescModal: false,
            descTarget: 'draft',
            descSavedIndex: null,
            descValue: '',
            descItemName: '',
            openDesc(target, index = null) {
                if (target === 'edit') {
                    this.descTarget = 'edit';
                    this.descSavedIndex = null;
                    this.descItemName = this.editRow.fitemname || '';
                    this.descValue = this.editRow.fdesc || '';
                } else if (target === 'saved') {
                    this.descTarget = 'saved';
                    this.descSavedIndex = index;
                    this.descItemName = this.savedItems[index]?.fitemname || '';
                    this.descValue = this.savedItems[index]?.fdesc || '';
                } else {
                    // draft row by index
                    this.descTarget = 'draft';
                    this.descSavedIndex = index; // re-use to hold draft row index
                    const dr = this.draftRows[index];
                    this.descItemName = dr?.fitemname || '';
                    this.descValue = dr?.fdesc || '';
                }
                this.showDescModal = true;
            },
            copyDescName() {
                this.descValue = this.descItemName || '';
            },
            closeDesc() {
                this.showDescModal = false;
                this.descItemName = '';
                this.descValue = '';
            },
            applyDesc() {
                if (this.descTarget === 'edit') {
                    this.editRow.fdesc = this.descValue;
                } else if (this.descTarget === 'saved') {
                    this.savedItems[this.descSavedIndex].fdesc = this.descValue;
                } else {
                    // draft row
                    if (this.descSavedIndex !== null && this.draftRows[this.descSavedIndex]) {
                        this.draftRows[this.descSavedIndex].fdesc = this.descValue;
                    }
                }
                this.showDescModal = false;
                this.syncDescList?.();
            },

            itemKey(it) {
                return `${(it.fitemcode ?? '').toString().trim()}::${(it.frefdtno ?? '').toString().trim()}`;
            },

            getCurrentItemKeys() {
                return this.savedItems.map(it => this.itemKey(it));
            },

            normalizeRestoredRow(item, index = 0) {
                const row = {
                    ...newRow(),
                    ...(item || {}),
                    uid: item?.uid || `restored-${index}`
                };

                if (typeof row.units === 'string') {
                    try {
                        const parsed = JSON.parse(row.units);
                        row.units = Array.isArray(parsed) ? parsed : [];
                    } catch (e) {
                        row.units = row.units.split(',').map(u => u.trim()).filter(Boolean);
                    }
                } else if (!Array.isArray(row.units)) {
                    row.units = [];
                }

                this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode));
                this.recalc(row);
                return row;
            },

            restoreSavedItems(items = []) {
                this.savedItems = Array.isArray(items)
                    ? items.map((item, index) => this.normalizeRestoredRow(item, index))
                    : [];
                this.syncDescList?.();
                this.recalcTotals();
            },

            restoreDraft(draft = {}) {
                this.draft = this.normalizeRestoredRow(draft, 'draft');
            },

            restoreEditRow(editRow = {}) {
                this.editRow = this.normalizeRestoredRow(editRow, 'edit');
            },

            init() {
                // If there are restored items from a failed POST, put them in draftRows
                this.draftRows = [];
                if (Array.isArray(_restoredItems) && _restoredItems.length > 0) {
                    _restoredItems.forEach(item => {
                        const dr = { ...newDraftRow(), ...item, _uid: cryptoRandom() };
                        if (typeof dr.fqty === 'string') dr.fqty = parseFloat(dr.fqty) || 0;
                        if (typeof dr.fprice === 'string') dr.fprice = parseFloat(dr.fprice) || 0;
                        dr.fpriceInput = this.fmt(dr.fprice);
                        this.hydrateRowFromMeta(dr, this.productMeta(dr.fitemcode));
                        this.recalc(dr);
                        this.draftRows.push(dr);
                    });
                }
                this.ensureMinimumDraftRows();


                this.$watch('includePPN', () => this.recalcTotals());
                this.$watch('fapplyppn', () => this.recalcTotals());
                this.$watch('ppnRate', () => this.recalcTotals());

                // Listen for PR picked from modal PR
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
                    } else if (typeof this.browseTarget === 'number') {
                        // draft row by index
                        const dr = this.draftRows[this.browseTarget];
                        if (dr) {
                            apply(dr);
                            // Force Alpine reactivity
                            this.draftRows.splice(this.browseTarget, 1, { ...dr });
                            this.$nextTick(() => document.getElementById('draft-qty-' + this.browseTarget)?.focus());
                        }
                    }
                }, {
                    passive: true
                });

                this.recalcTotals();
            },

            focusDraftField(di, field) {
                if (field === 'qty') {
                    document.getElementById('draft-qty-' + di)?.focus();
                }
            },

            browseTarget: 'draft',
            openBrowseFor(where) {
                // where is either 'edit' or a numeric draft row index
                this.browseTarget = (where === 'edit' ? 'edit' : Number(where));
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
                fpriceInput: '0,00',
                fdiscpersen: 0,
                fbiaya: 0,
                ftotprice: 0,
                fdesc: '',
                fketdt: '',
                maxqty: 0,
            };
        }

        function newDraftRow() {
            return {
                ...newRow(),
                _uid: cryptoRandom(),
            };
        }

        function cryptoRandom() {
            return (window.crypto?.getRandomValues ? [...window.crypto.getRandomValues(new Uint32Array(2))].map(n => n
                .toString(16)).join('') : Math.random().toString(36).slice(2)) + Date.now();
        }
    }
</script>

@include('components.transaction.salesorder-pr-modal-script')

<script>
    // Helper function untuk format tanggal (ditingkatkan sedikit)
    function formatDate(s) {
        if (!s || s === 'No Date') return '-';
        // Mencoba parsing format standar ISO 8601 atau yang didukung Date
        const d = new Date(s);
        if (isNaN(d.getTime())) return '-';

        // Format YYYY-MM-DD HH:MM
        const pad = n => n.toString().padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
    }
</script>

<script>
    // Warehouse Browser dengan DataTables
    window.warehouseBrowser = function() {
        return {
            open: false,
            table: null,

            initDataTable() {
                if (this.table) {
                    this.table.columns.adjust().draw(false);
                    return;
                }
                $('#warehouseTable').off('click.whpick');
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
                            data: 'fbranchcode',
                            name: 'fbranchcode',
                            className: 'text-sm',
                            width: '15%',
                            render: function(data) {
                                return data || '<span class="text-gray-400">-</span>';
                            }
                        },
                        {
                            data: 'fwhcode',
                            name: 'fwhcode',
                            className: 'font-mono text-sm font-semibold',
                            width: '20%'
                        },
                        {
                            data: 'fwhname',
                            name: 'fwhname',
                            className: 'text-sm',
                            width: '50%'
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center',
                            width: '15%',
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
                    scrollX: false,
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
                $('#warehouseTable').on('click.whpick', '.btn-choose', (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    const data = this.table?.row($(e.currentTarget).closest('tr')).data();
                    if (!data) return;
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
                fwhcode
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
                    $('#supplierBrowseTable').on('click', '.btn-choose', (e) => {
                        const data = this.dataTable.row($(e.target).closest('tr')).data();
                        this.chooseSupplier(data);
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
                        this.dataTable.search('').draw();
                    }
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
    </script>
@endpush
