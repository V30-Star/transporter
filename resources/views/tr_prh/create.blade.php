@extends('layouts.app')

@section('title', 'Permintaan Pembelian - New')

@section('content')
    @php
        $canCreateSupplier = in_array('createSupplier', explode(',', session('user_restricted_permissions', '')), true);
    @endphp
    <style>
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, .2);
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

        .desc-inline-field {
            display: flex;
            width: 100%;
            min-width: 0;
            align-items: stretch;
            flex-wrap: nowrap;
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

        .pr-detail-table th,
        .pr-detail-table td {
            padding: .25rem .375rem !important;
        }

        .pr-detail-table input:not([type="hidden"]),
        .pr-detail-table select,
        .pr-detail-table button,
        .pr-detail-table .desc-inline-field__text {
            min-height: 1.875rem;
            padding-top: .25rem !important;
            padding-bottom: .25rem !important;
            line-height: 1.25rem;
        }

        .pr-detail-table button {
            display: inline-flex;
            align-items: center;
        }

        .pr-detail-table .desc-inline-field__button {
            width: 2rem !important;
            flex-basis: 2rem !important;
        }

    </style>
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow p-0 overflow-hidden mb-4 rounded-xl"
            role="alert">
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

    <div>
        <div>
            <form action="{{ route('tr_prh.store') }}" method="POST" data-form-draft="true" data-draft-key="tr_prh:create"
                @submit.prevent="window.dispatchEvent(new CustomEvent('tr-prh-submit-request'))">
                @csrf

                {{-- ─── CARD 1: Identitas Permintaan ────────────────────── --}}
                <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                    <div class="flex items-center gap-2 px-4 pt-3 pb-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Identitas Permintaan</p>
                    </div>
                    <div class="p-4 space-y-3">

                        <div class="grid grid-cols-3 gap-3">
                            {{-- Cabang --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Cabang</label>
                                <input type="text"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                                    value="{{ $fbranchlabel ?? $fcabang }}" disabled>
                                <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                            </div>

                            {{-- PR# --}}
                            <div x-data="{ autoCode: true }">
                                <label class="block text-xs font-bold text-gray-600 mb-1">PR#</label>
                                <div class="flex items-center gap-2">
                                    <input type="text" name="fprno"
                                        class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                        :disabled="autoCode"
                                        :class="autoCode ? 'bg-gray-100 text-gray-500 border-gray-200 cursor-not-allowed' :
                                            'bg-white'"
                                        :placeholder="autoCode ? 'Auto Generated' : ''">
                                    <label
                                        class="inline-flex items-center select-none font-medium text-sm text-gray-600 cursor-pointer">
                                        <input type="checkbox" x-model="autoCode" checked
                                            class="rounded text-blue-600 border-gray-300 focus:ring-blue-500">
                                        <span class="ml-1.5">Auto</span>
                                    </label>
                                </div>
                            </div>

                            {{-- Tanggal --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Tanggal <span
                                        class="text-red-500">*</span></label>
                                <input type="date" name="fprdate" value="{{ old('fprdate') ?? date('Y-m-d') }}"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fprdate') border-red-400 @enderror">
                                @error('fprdate')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>     
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                           {{-- Supplier --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Supplier <span
                                        class="text-red-500">*</span></label>
                                <div class="flex">
                                    <div class="relative flex-1" for="modal_filter_supplier_id">
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
                                        <div class="absolute inset-0 cursor-pointer" role="button"
                                            aria-label="Browse supplier"
                                            @click="window.dispatchEvent(new CustomEvent('supplier-browse-open'))"></div>
                                    </div>
                                    <input type="hidden" name="fsupplier" id="supplierCodeHidden"
                                        value="{{ old('fsupplier') }}">
                                    <button type="button"
                                        @click="window.dispatchEvent(new CustomEvent('supplier-browse-open'))"
                                        class="border border-l-0 border-gray-300 px-3 py-2 bg-white hover:bg-gray-50 text-gray-500 transition-colors"
                                        title="Browse Supplier">
                                        <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                    </button>
                                    @if ($canCreateSupplier)
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

                            {{-- Tanggal Dibutuhkan --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Tanggal Dibutuhkan <span
                                        class="text-red-500">*</span></label>
                                <input type="date" name="fneeddate" value="{{ old('fneeddate') }}"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fneeddate') border-red-400 @enderror">
                                @error('fneeddate')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Tanggal Paling Lambat --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Tanggal Paling Lambat <span
                                        class="text-red-500">*</span></label>
                                <input type="date" name="fduedate" value="{{ old('fduedate') }}"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fduedate') border-red-400 @enderror">
                                @error('fduedate')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Keterangan --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Keterangan</label>
                            <textarea name="fket" rows="2"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fket') border-red-400 @enderror"
                                placeholder="Tulis keterangan tambahan di sini...">{{ old('fket') }}</textarea>
                            @error('fket')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
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
                    <div class="p-4">
                        <div x-data="itemsTableRows()" x-init="init()" class="space-y-2">
                            <div class="overflow-auto border rounded">
                                <table class="pr-detail-table min-w-full text-sm balanced-detail-table"
                                    data-skip-auto-detail-style="true">
                                    <colgroup>
                                        <col style="width:2%;">
                                        <col style="width:18%;">
                                        <col style="width:35%;">
                                        <col style="width:12%;">
                                        <col style="width:10%;">
                                        <col style="width:20%;">
                                        <col style="width:3%;">
                                    </colgroup>
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="p-2 text-left w-10">#</th>
                                            <th class="p-2 text-left w-52">Kode Produk</th>
                                            <th class="p-2 text-left w-96">Nama Produk</th>
                                            <th class="p-2 text-left w-40">Satuan</th>
                                            <th class="p-2 text-right w-28 whitespace-nowrap">Qty</th>
                                            <th class="p-2 text-left w-56">Ket Item</th>
                                            <th class="p-2 text-center w-24">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(row, i) in rows" :key="row.uid">
                                            <tr class="border-t align-top"
                                                :class="i === 0 ? 'bg-green-50/40' : 'bg-white'">
                                                <td class="p-2 text-gray-400" x-text="i + 1"></td>
                                                <td class="p-2">
                                                    <div class="flex">
                                                        <input type="text"
                                                            class="flex-1 border rounded-l px-2 py-1 font-mono text-sm min-w-0 focus:ring-1 focus:ring-blue-500"
                                                            x-model.trim="row.fitemcode" @input="onCodeTyped(row, i)"
                                                            @keydown.enter.prevent="focusNextField(row, i)">
                                                        <button type="button" @click="openBrowseFor(i)"
                                                            class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50 text-gray-500 transition-colors"
                                                            title="Cari Produk">
                                                            <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                </td>
                                                <td class="p-2">
                                                    <div class="flex w-full max-w-full">
                                                        <div class="min-w-0 flex-1 rounded-l border bg-gray-100 px-2 py-1 text-sm leading-5 text-gray-600 whitespace-normal break-words"
                                                            x-text="row.fitemname || '-'"></div>
                                                        <button type="button" @click="openDesc(i)"
                                                            class="shrink-0 inline-flex items-center border border-l-0 rounded-r bg-slate-50 px-2 py-1 text-slate-700 hover:bg-slate-100 transition-colors"
                                                            :class="descButtonClass(row.fdesc)" title="Deskripsi item">
                                                            <x-heroicon-o-document-text class="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                </td>
                                                <td class="p-2">
                                                    <template x-if="row.units.length > 1">
                                                        <select
                                                            class="w-full border rounded px-2 py-1 text-sm focus:ring-1 focus:ring-blue-500"
                                                            x-model="row.fsatuan" @change="onRowUpdated(i)">
                                                            <template x-for="unit in row.units" :key="unit">
                                                                <option :value="unit" x-text="unit"></option>
                                                            </template>
                                                        </select>
                                                    </template>
                                                    <template x-if="row.units.length <= 1">
                                                        <div class="px-2 py-1 text-sm text-gray-600 bg-gray-50 border rounded"
                                                            x-text="row.fsatuan || '-'"></div>
                                                    </template>
                                                </td>
                                                <td class="p-2 text-right">
                                                    <input type="text" inputmode="decimal"
                                                        class="w-full border rounded px-2 py-1 text-right text-sm focus:ring-1 focus:ring-blue-500"
                                                        x-model="row.fqty" @focus="unformatQtyInput(row)"
                                                        @input="onQtyInput(row, i)" @blur="formatQtyInput(row, i)">
                                                </td>
                                                <td class="p-2">
                                                    <input type="text"
                                                        class="w-full border rounded px-2 py-1 text-sm focus:ring-1 focus:ring-blue-500"
                                                        x-model="row.fketdt" @input="onRowUpdated(i)">
                                                </td>
                                                <td class="p-2 text-center text-xs">
                                                    <button type="button" @click="removeRow(i)"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded bg-red-100 text-red-600 hover:bg-red-200"
                                                        title="Hapus baris">-</button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <div class="hidden">
                                <template x-for="row in rowsToSubmit" :key="'submit-' + row.uid">
                                    <div>
                                        <input type="hidden" name="fitemcode[]" :value="row.fitemcode">
                                        <input type="hidden" name="fitemname[]" :value="row.fitemname">
                                        <input type="hidden" name="fnoacak[]" :value="row.fnoacak">
                                        <input type="hidden" name="fsatuan[]" :value="row.fsatuan">
                                        <input type="hidden" name="fqty[]" :value="row.fqty">
                                        <input type="hidden" name="fdesc[]" :value="row.fdesc">
                                        <input type="hidden" name="fketdt[]" :value="row.fketdt">
                                    </div>
                                </template>
                            </div>

                            <input type="hidden" id="itemsCount" :value="rowsToSubmit.length">

                            <div x-show="showDescModal" x-cloak
                                class="fixed inset-0 z-[95] flex items-center justify-center bg-black/50"
                                x-transition.opacity>
                                <div class="absolute inset-0" @click="closeDesc()"></div>
                                <div class="relative bg-white w-[92vw] max-w-lg rounded-2xl shadow-2xl overflow-hidden"
                                    x-transition.scale>
                                    <div class="px-5 py-4 border-b flex items-center">
                                        <x-heroicon-o-document-text class="w-6 h-6 text-blue-600 mr-2" />
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-800">Deskripsi Item</h3>
                                            <p class="text-xs text-gray-500" x-text="descItemLabel"></p>
                                        </div>
                                    </div>
                                    <div class="px-5 py-4 space-y-4">
                                        <div>
                                            <div class="mb-1 flex items-center justify-between gap-3">
                                                <div class="text-sm text-gray-700 font-medium">Nama Produk</div>
                                                <button type="button" @click="descValue = descItemLabel || ''"
                                                    class="h-8 px-3 rounded-lg bg-blue-50 text-blue-700 text-sm font-medium hover:bg-blue-100 transition-colors">
                                                    Copy
                                                </button>
                                            </div>
                                            <div class="rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800"
                                                x-text="descItemLabel || '-'"></div>
                                        </div>
                                        <div>
                                            <label class="block text-sm text-gray-700 font-bold mb-1">Deskripsi</label>
                                            <textarea x-model="descValue" rows="5"
                                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500"
                                                placeholder="Tulis deskripsi item di sini..."></textarea>
                                        </div>
                                    </div>
                                    <div class="px-5 py-3 border-t flex items-center justify-end gap-2 bg-gray-50">
                                        <button type="button" @click="closeDesc()"
                                            class="h-9 px-4 rounded-lg bg-white border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors">
                                            Batal
                                        </button>
                                        <button type="button" @click="applyDesc()"
                                            class="h-9 px-4 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors">
                                            Simpan
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div x-show="showNoItems" x-cloak
                                class="fixed inset-0 z-[90] flex items-center justify-center bg-black/50"
                                x-transition.opacity>
                                <div class="absolute inset-0" @click="showNoItems = false"></div>
                                <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden"
                                    x-transition.scale>
                                    <div class="px-5 py-4 border-b flex items-center bg-red-50 text-red-700">
                                        <x-heroicon-o-exclamation-triangle class="w-6 h-6 mr-2" />
                                        <h3 class="text-lg font-semibold">Tidak Ada Item</h3>
                                    </div>
                                    <div class="px-5 py-4">
                                        <p class="text-sm text-gray-700">
                                            Belum ada item dengan Qty lebih dari 0 yang bisa disimpan.
                                        </p>
                                    </div>
                                    <div class="px-5 py-3 border-t flex items-center justify-end gap-2 bg-gray-50">
                                        <button type="button" @click="showNoItems = false"
                                            class="h-9 px-4 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors">
                                            OK
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div x-show="showWarningModal" x-cloak
                                class="fixed inset-0 z-[96] flex items-center justify-center bg-black/50"
                                x-transition.opacity>
                                <div class="absolute inset-0" @click="closeWarning()"></div>
                                <div class="relative bg-white w-[92vw] max-w-lg rounded-2xl shadow-2xl overflow-hidden"
                                    x-transition.scale>
                                    <div class="px-5 py-4 border-b flex items-center bg-amber-50 text-amber-700">
                                        <x-heroicon-o-exclamation-triangle class="w-6 h-6 mr-2" />
                                        <h3 class="text-lg font-semibold" x-text="warningTitle"></h3>
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
                                    <div class="px-5 py-3 border-t flex items-center justify-end gap-2 bg-gray-50">
                                        <button type="button" @click="closeWarning()"
                                            class="h-9 px-4 rounded-lg bg-white border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors">
                                            Tutup
                                        </button>
                                        <button type="button" x-show="warningCanProceed"
                                            @click="confirmWarningAndSubmit()"
                                            class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition-colors">
                                            Lanjut Simpan
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─── CARD 3: Approval & Aksi ────────────────────── --}}
                @php
                    $canApproval = in_array('approvePR', explode(',', session('user_restricted_permissions', '')));
                @endphp
                <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                    <div class="flex items-center gap-2 px-4 pt-3 pb-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Approval & Aksi</p>
                    </div>
                    <div class="p-4 space-y-4">
                        @if ($canApproval)
                            <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg bg-gray-50 cursor-pointer hover:border-gray-300 transition-colors"
                                x-data="{ active: {{ old('fapproval', session('fapproval') ? 1 : 0) ? 'true' : 'false' }} }"
                                @click="active = !active; $el.querySelector('input[name=fapproval]').value = active ? '1' : '0'">
                                <div>
                                    <p class="text-sm text-gray-800 font-medium">Setujui Sekarang</p>
                                    <p class="text-xs text-gray-400 mt-0.5">Aktifkan untuk langsung menyetujui dokumen
                                        permintaan pembelian ini</p>
                                </div>
                                <div class="relative w-9 h-5 rounded-full transition-colors duration-200 flex-shrink-0"
                                    :class="active ? 'bg-blue-500' : 'bg-gray-300'">
                                    <div class="absolute w-3.5 h-3.5 bg-white rounded-full top-0.5 transition-transform duration-200"
                                        :class="active ? 'translate-x-4 left-0.5' : 'left-0.5'"></div>
                                </div>
                                <input type="hidden" name="fapproval" :value="active ? '1' : '0'">
                            </div>
                        @endif
                    </div>

                  {{-- Footer Buttons --}}
<div class="flex items-center justify-end gap-3 px-4 py-3 bg-gray-50 border-t border-gray-200">
    <button type="button" onclick="window.location.href='{{ route('tr_prh.index') }}'"
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
                                <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Kode</th>
                                <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Nama
                                    Supplier</th>
                                <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Alamat
                                </th>
                                <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Telepon
                                </th>
                                <th class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Aksi
                                </th>
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
                    <table id="productTable" class="min-w-full text-sm display nowrap stripe hover" style="width:100%">
                        <thead class="sticky top-0 z-10">
                            <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Kode</th>
                                <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Nama
                                    Produk</th>
                                <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Satuan
                                </th>
                                <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Merek</th>
                                <th class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Stock
                                </th>
                                <th class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Aksi
                                </th>
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

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
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

    function itemsTableRows() {
        return {
            rows: [],
            rowsToSubmit: [],
            browseTargetIndex: null,
            showDescModal: false,
            descRowIndex: null,
            descValue: '',
            descItemLabel: '',
            showNoItems: false,
            showWarningModal: false,
            warningTitle: '',
            warningMessage: '',
            warningItems: [],
            warningCanProceed: false,
            minimumVisibleRows: 5,

            emptyRow() {
                return {
                    uid: cryptoRandom(),
                    fitemcode: '',
                    fitemname: '',
                    fnoacak: this.generateUniqueNoAcak(),
                    units: [],
                    fsatuan: '',
                    fqty: '',
                    fdesc: '',
                    fketdt: ''
                };
            },

            sanitizeQtyValue(value) {
                const raw = (value ?? '').toString().replace(',', '.').replace(/[^0-9.]/g, '');
                const parts = raw.split('.');
                if (parts.length <= 1) return raw;
                return `${parts.shift()}.${parts.join('')}`;
            },

            formatQtyDisplay(value) {
                const raw = this.sanitizeQtyValue(value);
                if (raw === '') return '';
                const numeric = Number(raw);
                return Number.isFinite(numeric) ? numeric.toFixed(2) : '';
            },

            unformatQtyInput(row) {
                const raw = this.sanitizeQtyValue(row?.fqty);
                row.fqty = raw === '' ? '' : String(Number(raw));
            },

            onQtyInput(row, index) {
                row.fqty = this.sanitizeQtyValue(row?.fqty);
                this.onRowUpdated(index);
            },

            formatQtyInput(row, index = null) {
                row.fqty = this.formatQtyDisplay(row?.fqty);
                this.onRowUpdated(index);
            },

            rowHasContent(row) {
                if (!row) return false;
                return [
                    row.fitemcode,
                    row.fitemname,
                    row.fsatuan,
                    row.fqty,
                    row.fdesc,
                    row.fketdt
                ].some((value) => String(value ?? '').trim() !== '' && Number(value ?? 0) !== 0) || Number(row
                    .fqty || 0) > 0;
            },

            ensureMinimumRows() {
                while (this.rows.length < this.minimumVisibleRows) {
                    this.rows.push(this.emptyRow());
                }
            },

            ensureTrailingRow(index = null) {
                if (!this.rows.length) {
                    this.ensureMinimumRows();
                    return;
                }

                const targetIndex = index === null ? this.rows.length - 1 : index;
                if (targetIndex !== this.rows.length - 1) return;

                if (this.rowHasContent(this.rows[targetIndex])) {
                    this.rows.push(this.emptyRow());
                }
            },

            onRowUpdated(index) {
                this.ensureTrailingRow(index);
            },

            normalizeNoAcak(value) {
                return (value || '').toString().replace(/\D/g, '').slice(0, 3);
            },

            generateUniqueNoAcak() {
                const used = new Set(this.rows.map(item => this.normalizeNoAcak(item.fnoacak)).filter(Boolean));
                let candidate = '';

                do {
                    candidate = Array.from({
                        length: 3
                    }, () => '123456789' [Math.floor(Math.random() * 9)]).join('');
                } while (used.has(candidate));

                return candidate;
            },

            hasDesc(value) {
                return String(value ?? '').trim() !== '';
            },

            descButtonClass(value) {
                return this.hasDesc(value) ?
                    'border-emerald-300 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 font-medium' :
                    'border-gray-300 bg-white text-gray-500 hover:bg-gray-50';
            },

            productMeta(code) {
                const key = (code || '').trim();
                return window.PRODUCT_MAP?.[key] || {
                    name: '',
                    default_unit: '',
                    units: []
                };
            },

            hydrateRowFromMeta(row, meta, forceDefaultUnit = false) {
                if (!meta) {
                    row.fitemname = '';
                    row.units = [];
                    row.fsatuan = '';
                    return;
                }

                row.fitemname = meta.name || '';
                const units = [...new Set((meta.units || []).map(unit => (unit ?? '').toString().trim()).filter(
                    Boolean))];
                const defaultUnit = (meta.default_unit || '').toString().trim();
                const resolvedDefaultUnit = defaultUnit && units.includes(defaultUnit) ? defaultUnit : (units[0] || '');
                row.units = units;
                row.fsatuan = forceDefaultUnit ?
                    resolvedDefaultUnit :
                    (units.includes(row.fsatuan) ? row.fsatuan : resolvedDefaultUnit);
                row.fnoacak = this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak();
            },

            onCodeTyped(row, index = null) {
                this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode), true);
                this.onRowUpdated(index);
            },

            openDesc(index = null) {
                const row = this.rows[index] || null;
                if (!row) return;
                this.descRowIndex = index;
                this.descValue = (row.fdesc || '').toString();
                this.descItemLabel = (row.fitemname || '').toString();
                this.showDescModal = true;
            },

            closeDesc() {
                this.showDescModal = false;
                this.descRowIndex = null;
                this.descValue = '';
                this.descItemLabel = '';
            },

            applyDesc() {
                if (this.descRowIndex !== null && this.rows[this.descRowIndex]) {
                    this.rows[this.descRowIndex].fdesc = (this.descValue || '').trim();
                    this.onRowUpdated(this.descRowIndex);
                }
                this.closeDesc();
            },

            showWarning(title, message, items = [], canProceed = false) {
                this.warningTitle = title;
                this.warningMessage = message;
                this.warningItems = items;
                this.warningCanProceed = canProceed;
                this.showWarningModal = true;
            },

            closeWarning() {
                this.showWarningModal = false;
                this.warningTitle = '';
                this.warningMessage = '';
                this.warningItems = [];
                this.warningCanProceed = false;
            },

            addRow(index) {
                const row = this.rows[index];
                if (!String(row?.fitemcode || '').trim()) {
                    this.showWarning('Kode Produk Belum Diisi',
                        'Isi kode produk terlebih dahulu sebelum menambah baris baru.');
                    return;
                }
                if (!String(row?.fitemname || '').trim()) {
                    this.showWarning('Produk Belum Valid',
                        'Produk pada baris ini belum ditemukan. Pilih produk yang valid terlebih dahulu.');
                    return;
                }
                this.rows.splice(index + 1, 0, this.emptyRow());
            },

            removeRow(index) {
                this.rows.splice(index, 1);
                this.ensureMinimumRows();
            },

            openBrowseFor(index) {
                this.browseTargetIndex = index;
                window.dispatchEvent(new CustomEvent('browse-open', {
                    detail: {
                        forEdit: false
                    }
                }));
            },

            prepareRowsForSubmit() {
                const validRows = [];
                const zeroQtyRows = [];
                const seenCodes = new Set();

                for (const row of this.rows) {
                    const code = String(row.fitemcode || '').trim();
                    const name = String(row.fitemname || '').trim();
                    const sat = String(row.fsatuan || '').trim();
                    const qty = Number(row.fqty || 0);
                    const ket = String(row.fketdt || '').trim();
                    const desc = String(row.fdesc || '').trim();

                    if (!code && !name && !sat && !qty && !ket && !desc) {
                        continue;
                    }

                    if (!code) {
                        return {
                            invalidMessage: 'Masih ada baris detail item tanpa kode produk.',
                            validRows: [],
                            zeroQtyRows: []
                        };
                    }

                    if (!name) {
                        return {
                            invalidMessage: `Kode produk ${code} belum valid atau belum dipilih dari daftar produk.`,
                            validRows: [],
                            zeroQtyRows: []
                        };
                    }

                    if (!sat) {
                        return {
                            invalidMessage: `Satuan untuk produk ${name} belum dipilih.`,
                            validRows: [],
                            zeroQtyRows: []
                        };
                    }

                    const normalizedCode = code.toUpperCase();
                    if (seenCodes.has(normalizedCode)) {
                        return {
                            invalidMessage: `Produk ${name || code} sudah diinput. Kode produk yang sama tidak boleh dipakai lebih dari 1 kali.`,
                            validRows: [],
                            zeroQtyRows: []
                        };
                    }

                    seenCodes.add(normalizedCode);

                    if (!(qty > 0)) {
                        zeroQtyRows.push(name || code);
                        continue;
                    }

                    validRows.push({
                        ...row,
                        fitemcode: code,
                        fitemname: name,
                        fsatuan: sat,
                        fqty: qty,
                        fketdt: ket,
                        fdesc: desc,
                        fnoacak: this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak()
                    });
                }

                return {
                    invalidMessage: '',
                    validRows,
                    zeroQtyRows
                };
            },

            handleSubmit(forceSubmit = false) {
                this.showNoItems = false;
                const prepared = this.prepareRowsForSubmit();

                if (prepared.invalidMessage) {
                    this.showWarning('Data Item Belum Lengkap', prepared.invalidMessage);
                    return;
                }

                if (prepared.validRows.length < 1) {
                    this.showNoItems = true;
                    return;
                }

                this.rowsToSubmit = prepared.validRows;

                if (prepared.zeroQtyRows.length > 0 && !forceSubmit) {
                    this.showWarning(
                        'Qty Produk Masih 0',
                        'Data produk berikut qty-nya masih 0, tidak akan tersimpan:',
                        prepared.zeroQtyRows,
                        true
                    );
                    return;
                }

                this.$nextTick(() => this.$root.closest('form')?.submit());
            },

            confirmWarningAndSubmit() {
                this.closeWarning();
                this.handleSubmit(true);
            },

            init() {
                this.rows = [];
                this.ensureMinimumRows();
                this.rows.forEach(row => {
                    row.fqty = this.formatQtyDisplay(row.fqty);
                });

                window.addEventListener('product-chosen', (e) => {
                    const {
                        product
                    } = e.detail || {};
                    if (!product) return;

                    const idx = this.browseTargetIndex;
                    const row = this.rows[idx];
                    if (!row) return;

                    row.fitemcode = (product.fprdcode || '').toString();
                    this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode), true);

                    // Force Alpine reactivity
                    this.rows.splice(idx, 1, {
                        ...this.rows[idx]
                    });
                    this.onRowUpdated(idx);
                }, {
                    passive: true
                });
                window.addEventListener('tr-prh-submit-request', () => this.handleSubmit(), {
                    passive: true
                });
            }
        };
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
