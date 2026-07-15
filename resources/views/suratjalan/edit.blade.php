@extends('layouts.app')

@section('title',
    $action === 'delete'
    ? 'Surat Jalan - Delete'
    : ($action === 'view'
    ? 'Surat Jalan - View'
    : 'Surat
    Jalan - Edit'))

@section('content')
    @php
        $permissions = explode(',', session('user_restricted_permissions', ''));
        $canEditPermission = in_array('updateSuratJalan', $permissions, true);
        $canDeletePermission = in_array('deleteSuratJalan', $permissions, true);
        $canPrintPermission =
            in_array('createSuratJalan', $permissions, true) ||
            in_array('updateSuratJalan', $permissions, true) ||
            in_array('deleteSuratJalan', $permissions, true);
        $isDelete = $action === 'delete';
        $isView = $action === 'view';
        $isReadOnly = $isDelete || $isView;
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

        /* Hilangkan panah di input number (Firefox) */
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

        .suratjalan-detail-table th,
        .suratjalan-detail-table td {
            padding: .25rem .375rem !important;
        }

        .suratjalan-detail-table input:not([type="hidden"]),
        .suratjalan-detail-table select,
        .suratjalan-detail-table button,
        .suratjalan-detail-table .desc-inline-field__text {
            min-height: 1.875rem;
            padding-top: .25rem !important;
            padding-bottom: .25rem !important;
            line-height: 1.25rem;
        }

        .suratjalan-detail-table button {
            display: inline-flex;
            align-items: center;
        }

        .suratjalan-detail-table .desc-inline-field__button {
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
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow p-0 overflow-hidden" role="alert">
            {{-- Header Strip --}}
            <div class="d-flex align-items-center px-4 py-3" style="background-color: #c0392b;">
                <i class="bi bi-exclamation-triangle-fill text-white me-2 fs-5"></i>
                <strong class="text-white fs-6">{{ 'Gagal Menyimpan Data!' }}</strong>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert"
                    aria-label="Close"></button>
            </div>

            {{-- Body --}}
            <div class="px-4 py-3" style="background-color: #fdeded; border-left: 5px solid #c0392b;">
                <p class="mb-2 text-danger fw-semibold">
                    <i class="bi bi-info-circle me-1"></i>
                    {{ 'Periksa kembali data berikut sebelum menyimpan:' }}
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
    @php
        $usageLocked = !empty($isUsageLocked);
        $oldSjItemCodes = old('fitemcode', []);
        $oldSjItemNames = old('fitemname', []);
        $oldSjUnits = old('fsatuan', []);
        $oldSjRefNos = old('frefdtno', []);
        $oldSjRefPrs = old('frefpr', []);
        $oldSjRefSos = old('frefso', []);
        $oldSjNoAcaks = old('fnoacak', []);
        $oldSjRefNoAcaks = old('frefnoacak', []);
        $oldSjDiscPersens = old('fdiscpersen', []);
        $oldSjQtys = old('fqty', []);
        $oldSjPrices = old('fprice', []);
        $oldSjTotals = old('ftotal', []);
        $oldSjDescs = old('fdesc', []);
        $oldSjMaxQtys = old('fmaxqty', []);
        $oldSjKetdts = old('fketdt', []);
        $initialEditSuratJalanItems = [];
        $oldSjIndexes = array_keys(is_array($oldSjItemCodes) ? $oldSjItemCodes : []);

        foreach ($oldSjIndexes as $index) {
            $code = trim((string) ($oldSjItemCodes[$index] ?? ''));
            $name = trim((string) ($oldSjItemNames[$index] ?? ''));
            if ($code === '' && $name === '') {
                continue;
            }

            $unit = trim((string) ($oldSjUnits[$index] ?? ''));
            $refPr = trim((string) ($oldSjRefPrs[$index] ?? ''));
            $refSo = trim((string) ($oldSjRefSos[$index] ?? ''));
            $refDtNo = trim((string) ($oldSjRefNos[$index] ?? ''));

            $initialEditSuratJalanItems[] = [
                'uid' => 'old-sj-edit-' . $index,
                'formIndex' => (int) $index,
                'is_restored_old' => true,
                'fitemcode' => $code,
                'fitemname' => $name,
                'units' => $unit !== '' ? [$unit] : [],
                'fsatuan' => $unit,
                'frefdtno' => $refDtNo,
                'fnoacak' => trim((string) ($oldSjNoAcaks[$index] ?? '')),
                'frefnoacak' => trim((string) ($oldSjRefNoAcaks[$index] ?? '')),
                'frefno_display' => $refPr !== '' ? $refPr : ($refSo !== '' ? $refSo : $refDtNo),
                'frefpr' => $refPr,
                'frefso' => $refSo,
                'fdiscpersen' => (float) ($oldSjDiscPersens[$index] ?? 0),
                'fqty' => (float) ($oldSjQtys[$index] ?? 0),
                'fprice' => (float) ($oldSjPrices[$index] ?? 0),
                'ftotal' => (float) ($oldSjTotals[$index] ?? 0),
                'fdesc' => (string) ($oldSjDescs[$index] ?? ''),
                'fketdt' => (string) ($oldSjKetdts[$index] ?? ''),
                'maxqty' => max(0, (float) ($oldSjMaxQtys[$index] ?? ($oldSjQtys[$index] ?? 0))),
            ];
        }

        $nextSuratJalanItemIndex = empty($oldSjIndexes) ? count($savedItems ?? []) : max(array_map('intval', $oldSjIndexes)) + 1;
    @endphp
    @if ($usageLocked && !$isView)
        <div x-data="{ open: true }" x-show="open" x-cloak class="fixed inset-0 z-[99] flex items-center justify-center"
            x-transition.opacity>
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
            <div class="relative bg-white w-[92vw] max-w-xl rounded-2xl shadow-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-orange-100 bg-orange-50 flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center">
                        <x-heroicon-o-lock-closed class="w-5 h-5 text-orange-600" />
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-bold text-orange-700">
                            {{ 'Surat Jalan' }} {{ $isDelete ? 'Tidak Dapat Dihapus' : 'Tidak Dapat Diedit' }}
                        </h3>
                        <p class="text-sm text-orange-500 mt-0.5">{{ $usageLockMessage }}</p>
                    </div>
                    <button type="button" @click="open = false"
                        class="flex-shrink-0 w-8 h-8 rounded-full bg-orange-100 hover:bg-orange-200 flex items-center justify-center transition-colors"
                        title="{{ 'Tutup' }}">
                        <x-heroicon-o-x-mark class="w-4 h-4 text-orange-600" />
                    </button>
                </div>
                <div class="px-6 py-4 border-t bg-gray-50 flex justify-end">
                    <button type="button" @click="open = false"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center gap-2">
                        <x-heroicon-o-arrow-left class="w-5 h-5" />
                        {{ 'Tutup' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
    <div>
        @if ($isReadOnly)
            {{-- ─── CARD 1: Identitas Surat Jalan (Readonly) ──────── --}}
            <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                <div class="flex items-center gap-2 px-4 pt-3 pb-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Identitas Surat Jalan</p>
                </div>
                <div class="p-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-xs font-semibold uppercase tracking-wider mb-1">Cabang</label>
                                            <input type="text"
                                                class="w-full border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 text-gray-500 text-sm cursor-not-allowed"
                                                value="{{ trim(($fbranchcode ?? '') . ($fcabang ?? '' ? ' - ' . $fcabang : '')) }}"
                                                disabled>
                                            <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                                        </div>
                                        <div x-data="{ autoCode: true }">
                                            <label class="block text-xs font-semibold uppercase tracking-wider mb-1">Transaksi#</label>
                                            <div class="flex items-center gap-2">
                                                <input type="text" name="fstockmtno" 
                                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                                    value="{{ old('fstockmtno', $displayFstockmtno ?? $suratjalan->fstockmtno) }}"
                                                    :disabled="autoCode" disabled
                                                    :class="autoCode ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'bg-white'">
                                                <label class="inline-flex items-center select-none cursor-pointer">
                                                    <input disabled type="checkbox" x-model="autoCode" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                    <span class="ml-1.5 text-sm text-gray-700">Auto</span>
                                                </label>
                                            </div>
                                            <p x-show="showWarehouseRequired" x-cloak class="text-red-600 text-sm mt-1">
                                                Gudang harus diisi dahulu sebelum Simpan.
                                            </p>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold uppercase tracking-wider mb-1">Tanggal</label>
                                            <input type="date" name="fstockmtdate" disabled
                                                value="{{ old('fstockmtdate', !empty($suratjalan->fstockmtdate) ? \Illuminate\Support\Carbon::parse($suratjalan->fstockmtdate)->format('Y-m-d') : date('Y-m-d')) }}"
                                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition @error('fstockmtdate') border-red-500 @enderror">
                                            @error('fstockmtdate')
                                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold uppercase tracking-wider mb-1">Customer</label>
                                            <div class="flex rounded-lg shadow-sm">
                                                <div class="relative flex-1" for="modal_filter_customer_id">
                                                    <select id="modal_filter_customer_id" name="filter_customer_id"
                                                        class="w-full border border-gray-300 rounded-l-lg px-3 py-2 bg-gray-50 text-gray-500 text-sm cursor-not-allowed appearance-none"
                                                        disabled>
                                                        <option value=""></option>
                                                        @foreach ($customers as $customer)
                                                            <option value="{{ $customer->fcustomercode }}"
                                                                {{ old('fsupplier', $suratjalan->fsupplier) == $customer->fcustomercode ? 'selected' : '' }}>
                                                                {{ $customer->fcustomername }} ({{ $customer->fcustomercode }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <div class="absolute inset-0 cursor-pointer" role="button"
                                                        aria-label="{{ 'Browse Customer' }}"
                                                        @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))">
                                                    </div>
                                                </div>
                                                <input type="hidden" name="fsupplier" id="customerCodeHidden"
                                                    value="{{ old('fsupplier', $suratjalan->fsupplier) }}">
                                                <button type="button"
                                                    @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))"
                                                    class="border border-l-0 border-gray-300 px-3 py-2 bg-white hover:bg-gray-50 rounded-r-lg text-gray-600 transition"
                                                    title="{{ 'Browse Customer' }}">
                                                    <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                </button>
                                                @if (in_array('createCustomer', explode(',', session('user_restricted_permissions', '')), true))
                                                    <a href="{{ route('customer.create') }}" target="_blank" rel="noopener"
                                                        class="border border-l-0 border-gray-300 rounded-r px-3 py-2 bg-white hover:bg-gray-50 text-gray-600 transition"
                                                        title="Tambah Customer">
                                                        <x-heroicon-o-plus class="w-4 h-4" />
                                                    </a>
                                                @endif
                                            </div>
                                            @error('fsupplier')
                                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold uppercase tracking-wider mb-1">Gudang</label>
                                            <div class="flex rounded-lg shadow-sm">
                                                <div class="relative flex-1">
                                                    <select id="warehouseSelect"
                                                        class="w-full border border-gray-300 rounded-l-lg px-3 py-2 bg-gray-50 text-gray-500 text-sm cursor-not-allowed appearance-none"
                                                        disabled>
                                                        <option value=""></option>
                                                        @foreach ($warehouses as $wh)
                                                            <option value="{{ $wh->fwhcode }}" data-id="{{ $wh->fwhid }}"
                                                                data-branch="{{ $wh->fbranchcode }}"
                                                                {{ old('ffrom', $suratjalan->ffrom) == $wh->fwhcode ? 'selected' : '' }}>
                                                                {{ $wh->fwhcode }} - {{ $wh->fwhname }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <div class="absolute inset-0 cursor-pointer" role="button"
                                                        aria-label="{{ 'Browse Gudang' }}"
                                                        @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open'))">
                                                    </div>
                                                </div>
                                                <input type="hidden" name="ffrom" id="warehouseCodeHidden"
                                                    value="{{ old('ffrom', $suratjalan->ffrom_code ?? '') }}">
                                                <input type="hidden" name="fwhid" id="warehouseIdHidden"
                                                    value="{{ old('fwhid', $suratjalan->ffrom) }}">
                                                <button type="button"
                                                    @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open'))"
                                                    class="border border-l-0 border-gray-300 px-3 py-2 bg-white hover:bg-gray-50 rounded-r-lg text-gray-600 transition"
                                                    title="{{ 'Browse Gudang' }}">
                                                    <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>

                                        <div></div>

                                        <div>
                                            <label class="block text-xs font-semibold uppercase tracking-wider mb-1">Kirim Ke</label>
                                            <textarea name="fkirim" rows="3" readonly
                                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition @error('fkirim') border-red-500 @enderror"
                                                placeholder="Tulis Kirim Ke di sini...">{{ old('fkirim', $suratjalan->fkirim) }}</textarea>
                                            @error('fkirim')
                                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold uppercase tracking-wider mb-1">Keterangan</label>
                                            <textarea name="fket" rows="3" readonly
                                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition @error('fket') border-red-500 @enderror"
                                                placeholder="Tulis keterangan tambahan di sini...">{{ old('fket', $suratjalan->fket) }}</textarea>
                                            @error('fket')
                                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold uppercase tracking-wider mb-1">Catatan Internal</label>
                                            <textarea name="fketinternal" id="fketinternal" rows="3" readonly
                                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition @error('fketinternal') border-red-500 @enderror"
                                                placeholder="Tulis catatan internal di sini...">{{ old('fketinternal', $suratjalan->fketinternal) }}</textarea>
                                            @error('fketinternal')
                                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

            {{-- ─── CARD 2: Detail Item (Readonly) ──────────────── --}}
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
                    <div x-data="itemsTable()" x-init="init()" class="space-y-2">

                            <div class="overflow-auto border rounded">
                                <table class="suratjalan-detail-table min-w-full text-sm balanced-detail-table"
                                    data-skip-auto-detail-style="true">
                                    <colgroup>
                                        <col style="width:2%;">
                                        <col style="width:15%;">
                                        <col style="width:35%;">
                                        <col style="width:20%;">
                                        <col style="width:13%;">
                                        <col style="width:15%;">
                                    </colgroup>
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="p-2 text-left w-10">#</th>
                                            <th class="p-2 text-left w-40">Kode Produk</th>
                                            <th class="p-2 text-left w-102">Nama Produk</th>
                                            <th class="p-2 text-left">No.Ref</th>
                                            <th class="p-2 text-left w-24">Sat</th>
                                            <th class="p-2 text-right w-28 whitespace-nowrap">Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(it, i) in savedItems" :key="it.uid">
                                            <tr class="border-t align-top hover:bg-gray-55">
                                                <td class="p-2 text-gray-400" x-text="i + 1"></td>
                                                <td class="p-2">
                                                    <div class="px-2 py-1 text-sm text-gray-600 bg-gray-50 border rounded font-mono" x-text="it.fitemcode"></div>
                                                </td>
                                                <td class="p-2">
                                                    <div class="flex w-full max-w-full">
                                                        <div class="min-w-0 flex-1 rounded-l border bg-gray-100 px-2 py-1 text-sm leading-5 text-gray-600 whitespace-normal break-words"
                                                            x-text="it.fitemname"></div>
                                                        <button type="button" @click="openDesc(i)"
                                                            class="shrink-0 inline-flex items-center border border-l-0 rounded-r bg-slate-50 px-2 py-1 text-slate-700 hover:bg-slate-100 transition-colors border-slate-200"
                                                            :class="it.fdesc ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : ''"
                                                            title="Deskripsi item">
                                                            <x-heroicon-o-document-text class="h-4 w-4" />
                                                        </button>
                                                    </div>
                                                </td>
                                                <td class="p-2">
                                                    <div class="px-2 py-1 text-sm text-gray-650 bg-gray-50 border rounded" x-text="it.frefno_display || it.frefso || '-'"></div>
                                                </td>
                                                <td class="p-2">
                                                    <div class="px-2 py-1 text-sm text-gray-650 bg-gray-50 border rounded" x-text="it.fsatuan || '-'"></div>
                                                </td>
                                                <td class="p-2 text-right">
                                                    <div class="px-2 py-1 text-sm text-gray-700 bg-gray-50 border rounded text-right font-medium" x-text="formatQtyValue(it.fqty)"></div>
                                                </td>
                                                <td class="hidden">
                                                    <input type="hidden" :name="`fitemcode[${it.formIndex}]`" :value="it.fitemcode">
                                                    <input type="hidden" :name="`fitemname[${it.formIndex}]`" :value="it.fitemname">
                                                    <input type="hidden" :name="`fsatuan[${it.formIndex}]`" :value="it.fsatuan">
                                                    <input type="hidden" :name="`frefdtno[${it.formIndex}]`" :value="it.frefdtno">
                                                    <input type="hidden" :name="`fmaxqty[${it.formIndex}]`" :value="it.maxqty">
                                                    <input type="hidden" :name="`frefpr[${it.formIndex}]`" :value="it.frefpr">
                                                    <input type="hidden" :name="`fnoacak[${it.formIndex}]`" :value="it.fnoacak">
                                                    <input type="hidden" :name="`frefnoacak[${it.formIndex}]`" :value="it.frefnoacak">
                                                    <input type="hidden" :name="`fqty[${it.formIndex}]`" :value="it.fqty">
                                                    <input type="hidden" :name="`fprice[${it.formIndex}]`" :value="it.fprice">
                                                    <input type="hidden" :name="`ftotal[${it.formIndex}]`" :value="it.ftotal">
                                                    <input type="hidden" :name="`fdesc[${it.formIndex}]`" :value="it.fdesc">
                                                    <input type="hidden" :name="`fketdt[${it.formIndex}]`" :value="it.fketdt">
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─── CARD 3: Aksi ────────────────── --}}
            <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                <div class="p-4 bg-gray-50 flex justify-end gap-3 items-center space-x-2">
                    @if ($isDelete)
                        @if ($usageLocked)
                            <button type="button" disabled title="{{ $usageLockMessage }}"
                                class="bg-red-300 text-white px-4 py-2 rounded-lg flex items-center cursor-not-allowed opacity-70 text-sm font-semibold shadow-sm">
                                <x-heroicon-o-lock-closed class="w-4 h-4 mr-1.5" />
                                Hapus
                            </button>
                        @else
                            <button type="button" onclick="showDeleteModal()"
                                class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 flex items-center text-sm font-semibold shadow-sm transition">
                                <x-heroicon-o-trash class="w-4 h-4 mr-1.5" />
                                Hapus
                            </button>
                        @endif
                    @elseif ($isView && $canPrintPermission)
                        <a href="{{ route('suratjalan.print', $suratjalan->fstockmtno) }}" target="_blank"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center text-sm font-semibold shadow-sm transition">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m10 0v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5m10 0v5H7v-5">
                                </path>
                            </svg>
                            Print
                        </a>
                    @endif
                    <button type="button" onclick="window.location.href='{{ route('suratjalan.index') }}'"
                        class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 flex items-center text-sm font-semibold shadow-sm transition">
                        <x-heroicon-o-arrow-left class="w-4 h-4 mr-1.5" />
                        Kembali
                    </button>
                </div>
            </div>

                        {{-- ============================================ --}}
                        {{-- MODE EDIT: FORM EDITABLE                    --}}
                        {{-- ============================================ --}}
                    @else
                        <form action="{{ route('suratjalan.update', $suratjalan->fstockmtid) }}" method="POST"
                            class="mt-6" data-form-draft="true"
                            data-draft-key="suratjalan:edit:{{ $suratjalan->fstockmtid }}" x-data="{ showNoItems: false, showWarehouseRequired: false }"
                            @submit.prevent="
        const duplicateCode = window.getSuratJalanDuplicateCode?.($el);
        if (duplicateCode) {
            Swal.fire({
                icon: 'warning',
                title: 'Produk Duplikat',
                text: `Kode produk ${duplicateCode} tidak boleh sama dalam satu Surat Jalan.`,
                confirmButtonText: 'OK',
                customClass: {
                    confirmButton: 'bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700'
                }
            });
            return;
        }
        const warehouseCode = (document.getElementById('warehouseCodeHidden')?.value || '').toString().trim();
        showWarehouseRequired = false;
        if (!warehouseCode) {
            showWarehouseRequired = true;
            window.toast?.error('Gudang wajib diisi sebelum simpan.');
            return;
        }
        const n = Number(document.getElementById('itemsCount')?.value || 0);
        if (n < 1) { showNoItems = true } else { $el.submit() }
      ">
                            @csrf
                            @method('PATCH')

                            {{-- ─── CARD 1: Identitas ────────────────── --}}
                            <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                               <div class="flex items-center gap-2 px-4 pt-3 pb-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Identitas Surat Jalan</p>
                </div>
                                <div class="p-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-xs font-semibold  uppercase tracking-wider mb-1">Cabang</label>
                                            <input type="text"
                                                class="w-full border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 text-gray-500 text-sm cursor-not-allowed"
                                                value="{{ trim(($fbranchcode ?? '') . ($fcabang ?? '' ? ' - ' . $fcabang : '')) }}"
                                                disabled>
                                            <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                                        </div>
                                        <div x-data="{ autoCode: true }">
                                            <label class="block text-xs font-semibold uppercase tracking-wider mb-1">Transaksi#</label>
                                            <div class="flex items-center gap-2">
                                                <input type="text" name="fstockmtno" 
                                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                                    value="{{ old('fstockmtno', $displayFstockmtno ?? $suratjalan->fstockmtno) }}"
                                                    :disabled="autoCode"
                                                    :class="autoCode ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : 'bg-white'">
                                                <label class="inline-flex items-center select-none cursor-pointer">
                                                    <input type="checkbox" x-model="autoCode" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                    <span class="ml-1.5 text-sm text-gray-700">Auto</span>
                                                </label>
                                            </div>
                                            <p x-show="showWarehouseRequired" x-cloak class="text-red-600 text-sm mt-1">
                                                Gudang harus diisi dahulu sebelum Simpan.
                                            </p>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold uppercase tracking-wider mb-1">Tanggal</label>
                                            <input type="date" name="fstockmtdate"
                                                value="{{ old('fstockmtdate', !empty($suratjalan->fstockmtdate) ? \Illuminate\Support\Carbon::parse($suratjalan->fstockmtdate)->format('Y-m-d') : date('Y-m-d')) }}"
                                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition @error('fstockmtdate') border-red-500 @enderror">
                                            @error('fstockmtdate')
                                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold uppercase tracking-wider mb-1">Customer</label>
                                            <div class="flex rounded-lg shadow-sm">
                                                <div class="relative flex-1" for="modal_filter_customer_id">
                                                    <select id="modal_filter_customer_id" name="filter_customer_id"
                                                        class="w-full border border-gray-300 rounded-l-lg px-3 py-2 bg-gray-50 text-gray-500 text-sm cursor-not-allowed appearance-none"
                                                        disabled>
                                                        <option value=""></option>
                                                        @foreach ($customers as $customer)
                                                            <option value="{{ $customer->fcustomercode }}"
                                                                {{ old('fsupplier', $suratjalan->fsupplier) == $customer->fcustomercode ? 'selected' : '' }}>
                                                                {{ $customer->fcustomername }} ({{ $customer->fcustomercode }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <div class="absolute inset-0 cursor-pointer" role="button"
                                                        aria-label="{{ 'Browse Customer' }}"
                                                        @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))">
                                                    </div>
                                                </div>
                                                <input type="hidden" name="fsupplier" id="customerCodeHidden"
                                                    value="{{ old('fsupplier', $suratjalan->fsupplier) }}">
                                                <button type="button"
                                                    @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))"
                                                    class="border border-l-0 border-gray-300 px-3 py-2 bg-white hover:bg-gray-50 rounded-r-lg text-gray-600 transition"
                                                    title="{{ 'Browse Customer' }}">
                                                    <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                </button>
                                                @if (in_array('createCustomer', explode(',', session('user_restricted_permissions', '')), true))
                                                    <a href="{{ route('customer.create') }}" target="_blank" rel="noopener"
                                                        class="border border-l-0 border-gray-300 rounded-r px-3 py-2 bg-white hover:bg-gray-50 text-gray-600 transition"
                                                        title="Tambah Customer">
                                                        <x-heroicon-o-plus class="w-4 h-4" />
                                                    </a>
                                                @endif
                                            </div>
                                            @error('fsupplier')
                                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold uppercase tracking-wider mb-1">Gudang</label>
                                            <div class="flex rounded-lg shadow-sm">
                                                <div class="relative flex-1">
                                                    <select id="warehouseSelect"
                                                        class="w-full border border-gray-300 rounded-l-lg px-3 py-2 bg-gray-50 text-gray-500 text-sm cursor-not-allowed appearance-none"
                                                        disabled>
                                                        <option value=""></option>
                                                        @foreach ($warehouses as $wh)
                                                            <option value="{{ $wh->fwhcode }}" data-id="{{ $wh->fwhid }}"
                                                                data-branch="{{ $wh->fbranchcode }}"
                                                                {{ old('ffrom', $suratjalan->ffrom) == $wh->fwhcode ? 'selected' : '' }}>
                                                                {{ $wh->fwhcode }} - {{ $wh->fwhname }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <div class="absolute inset-0 cursor-pointer" role="button"
                                                        aria-label="{{ 'Browse Gudang' }}"
                                                        @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open'))">
                                                    </div>
                                                </div>
                                                <input type="hidden" name="ffrom" id="warehouseCodeHidden"
                                                    value="{{ old('ffrom', $suratjalan->ffrom_code ?? '') }}">
                                                <input type="hidden" name="fwhid" id="warehouseIdHidden"
                                                    value="{{ old('fwhid', $suratjalan->ffrom) }}">
                                                <button type="button"
                                                    @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open'))"
                                                    class="border border-l-0 border-gray-300 px-3 py-2 bg-white hover:bg-gray-50 rounded-r-lg text-gray-600 transition"
                                                    title="{{ 'Browse Gudang' }}">
                                                    <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>

                                        <div></div>

                                        <div>
                                            <label class="block text-xs font-semibold uppercase tracking-wider mb-1">Kirim Ke</label>
                                            <textarea name="fkirim" rows="3"
                                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition @error('fkirim') border-red-500 @enderror"
                                                placeholder="Tulis Kirim Ke di sini...">{{ old('fkirim', $suratjalan->fkirim) }}</textarea>
                                            @error('fkirim')
                                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold uppercase tracking-wider mb-1">Keterangan</label>
                                            <textarea name="fket" rows="3"
                                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition @error('fket') border-red-500 @enderror"
                                                placeholder="Tulis keterangan tambahan di sini...">{{ old('fket', $suratjalan->fket) }}</textarea>
                                            @error('fket')
                                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Catatan Internal</label>
                                            <textarea name="fketinternal" id="fketinternal" rows="3"
                                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition @error('fketinternal') border-red-500 @enderror"
                                                placeholder="Tulis catatan internal di sini...">{{ old('fketinternal', $suratjalan->fketinternal) }}</textarea>
                                            @error('fketinternal')
                                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div x-data="itemsTable()" x-init="init()" class="space-y-4">
                                {{-- ─── CARD 2: Detail Item ────────────────── --}}
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
                                        <div class="overflow-x-auto border rounded">
                                            <table class="min-w-full text-sm balanced-detail-table"
                                                data-skip-auto-detail-style="true">
                                                <colgroup>
                                                    <col style="width:2%;">
                                                    <col style="width:15%;">
                                                    <col style="width:30%;">
                                                    <col style="width:18%;">
                                                    <col style="width:10%;">
                                                    <col style="width:15%;">
                                                    <col style="width:10%;">
                                                </colgroup>
                                                <thead class="bg-gray-100">
                                                    <tr>
                                                        <th class="p-2 text-left w-10">#</th>
                                                        <th class="p-2 text-left w-40">Kode Produk</th>
                                                        <th class="p-2 text-left w-102">Nama Produk</th>
                                                        <th class="p-2 text-left">No.Ref</th>
                                                        <th class="p-2 text-right w-24">Sat</th>
                                                        <th class="p-2 text-right w-24 whitespace-nowrap">Qty</th>
                                                        <th class="p-2 text-center w-36">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                            <template x-for="(it, i) in savedItems" :key="it.uid">
                                                <tr class="border-t align-top hover:bg-gray-50">
                                                    <td class="p-2 text-gray-400" x-text="i + 1"></td>
                                                    <td class="p-2">
                                                        <div class="flex">
                                                            <input type="text"
                                                                class="flex-1 border rounded-l px-2 py-1 font-mono text-sm focus:ring-1 focus:ring-blue-500 min-w-0"
                                                                x-model.trim="it.fitemcode" @input="onCodeTypedRow(it, i)"
                                                                @keydown.enter.prevent="focusRowUnit(it, i)">
                                                            <button type="button" @click="openBrowseFor(i)"
                                                                class="shrink-0 border border-l-0 px-2 py-1 bg-white hover:bg-gray-55 text-gray-500 transition-colors"
                                                                title="Cari Produk">
                                                                <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td class="p-2">
                                                        <div class="flex w-full max-w-full">
                                                            <div class="min-w-0 flex-1 rounded-l border bg-gray-101 px-2 py-1 text-sm leading-5 text-gray-650 whitespace-normal break-words"
                                                                x-text="it.fitemname"></div>
                                                            <button type="button" @click="openDesc(i)"
                                                                class="shrink-0 inline-flex items-center border border-l-0 rounded-r bg-slate-50 px-2 py-1 text-slate-700 hover:bg-slate-100 transition-colors border-slate-200"
                                                                :class="it.fdesc ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : ''"
                                                                title="Deskripsi">
                                                                <x-heroicon-o-document-text class="h-4 w-4" />
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td class="p-2">
                                                        <div class="px-2 py-1 text-sm text-gray-655 bg-gray-50 border rounded"
                                                            x-text="it.frefno_display || (it.frefdtno && it.frefdtno !== '0' ? it.frefdtno : '') || '-'"></div>
                                                    </td>
                                                    <td class="p-2">
                                                        <template x-if="it.units && it.units.length > 1">
                                                            <select class="w-full border rounded px-2 py-1 text-sm focus:ring-1 focus:ring-blue-500"
                                                                :id="'unit_row_' + i" x-model="it.fsatuan"
                                                                x-effect="$el.value = it.fsatuan" @change="onRowUpdated(i)"
                                                                @keydown.enter.prevent="focusRowQty(i)">
                                                                <template x-for="u in it.units" :key="u">
                                                                    <option :value="u"
                                                                        :selected="u === it.fsatuan" x-text="u"></option>
                                                                </template>
                                                            </select>
                                                        </template>
                                                        <template x-if="!(it.units && it.units.length > 1)">
                                                            <div class="px-2 py-1 text-sm text-gray-650 bg-gray-50 border rounded" x-text="it.fsatuan || '-'"></div>
                                                        </template>
                                                    </td>
                                                    <td class="p-2 text-right">
                                                        <input type="number"
                                                            class="w-full border rounded px-2 py-1 text-right text-sm focus:ring-1 focus:ring-blue-500"
                                                            :id="'qty_row_' + i" x-model.number="it.fqty"
                                                            @input="enforceQtyRow(it); onRowUpdated(i)"
                                                            @change="enforceQtyRow(it); onRowUpdated(i)">
                                                        <div class="text-xs text-gray-400 mt-0.5 text-right">
                                                            <span x-show="it.fitemcode"
                                                                x-html="formatStockLimit(it)"></span>
                                                        </div>
                                                    </td>
                                                    <td class="p-2 text-center text-xs">
                                                        <button type="button" @click="removeSaved(i)"
                                                            class="inline-flex h-8 w-8 items-center justify-center rounded bg-red-100 text-red-600 hover:bg-red-200 transition-colors"
                                                            title="Hapus baris">-</button>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="hidden">
                                    <template x-for="(it, i) in submitItems" :key="'submit-' + (it.uid || i)">
                                        <div>
                                            <input type="hidden" :name="`fitemcode[${it.formIndex}]`" :value="it.fitemcode">
                                            <input type="hidden" :name="`fitemname[${it.formIndex}]`" :value="it.fitemname">
                                            <input type="hidden" :name="`fsatuan[${it.formIndex}]`" :value="it.fsatuan">
                                            <input type="hidden" :name="`frefdtno[${it.formIndex}]`" :value="it.frefdtno">
                                            <input type="hidden" :name="`frefpr[${it.formIndex}]`" :value="it.frefpr">
                                            <input type="hidden" :name="`frefso[${it.formIndex}]`" :value="it.frefso">
                                            <input type="hidden" :name="`fnoacak[${it.formIndex}]`" :value="it.fnoacak">
                                            <input type="hidden" :name="`frefnoacak[${it.formIndex}]`" :value="it.frefnoacak">
                                            <input type="hidden" :name="`fdiscpersen[${it.formIndex}]`"
                                                :value="it.fdiscpersen ?? it.fdisc ?? 0">
                                            <input type="hidden" :name="`fqty[${it.formIndex}]`" :value="it.fqty">
                                            <input type="hidden" :name="`fprice[${it.formIndex}]`" :value="it.fprice">
                                            <input type="hidden" :name="`fmaxqty[${it.formIndex}]`" :value="it.maxqty">
                                            <input type="hidden" :name="`ftotal[${it.formIndex}]`" :value="it.ftotal">
                                            <input type="hidden" :name="`fdesc[${it.formIndex}]`" :value="it.fdesc">
                                            <input type="hidden" :name="`fketdt[${it.formIndex}]`" :value="it.fketdt">
                                        </div>
                                    </template>
                                </div>

                                <div class="mt-3 flex flex-wrap items-start gap-3">
                                    <div class="flex flex-wrap items-center gap-3">
                                        {{-- SO --}}
                                        <div x-data="soFormModal()" class="min-w-fit">
                                            <div class="w-full flex justify-start">
                                                <button type="button" @click="openModal()"
                                                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="1.5" d="M12 4.5v15m7.5-7.5h-15" />
                                                    </svg>
                                                    Add SO
                                                </button>
                                            </div>

                                            {{-- MODAL SO --}}
                                            <div x-show="show" x-cloak x-transition.opacity
                                                class="fixed inset-0 z-50 flex items-center justify-center p-4">
                                                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                                                    @click="closeModal()">
                                                </div>

                                                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-5xl flex flex-col overflow-hidden"
                                                    style="height: 650px;">
                                                    <!-- Header -->
                                                    <div
                                                        class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-teal-50 to-white">
                                                        <div>
                                                            <h3 class="text-xl font-bold text-gray-800">Add SO</h3>
                                                            <p class="text-sm text-gray-500 mt-0.5">
                                                                {{ 'Pilih Purchase Order (PO)' }}</p>
                                                        </div>
                                                        <button type="button" @click="closeModal()"
                                                            class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-bold text-gray-700 text-sm">
                                                            {{ 'Tutup' }}
                                                        </button>
                                                    </div>

                                                    <!-- Search & Length Menu -->
                                                    <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                                                        <div id="poTableControls"></div>
                                                    </div>

                                                    <!-- Table with fixed height and scroll -->
                                                    <div class="flex-1 overflow-y-auto px-6" style="min-height: 0;">
                                                        <div class="bg-white">
                                                            <table id="poTable"
                                                                class="min-w-full text-sm display nowrap stripe hover"
                                                                style="width:100%">
                                                                <thead class="sticky top-0 z-10">
                                                                    <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                                                        <th
                                                                            class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                            Cab</th>
                                                                        <th
                                                                            class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                            No.SO</th>
                                                                        <th
                                                                            class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                            Tanggal</th>
                                                                        <th
                                                                            class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                            Customer</th>
                                                                        <th
                                                                            class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                            Alamat</th>
                                                                        <th
                                                                            class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                            No Ref</th>
                                                                        <th
                                                                            class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                            Aksi</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <!-- Data will be populated by DataTables -->
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>

                                                    <!-- Pagination & Info -->
                                                    <div
                                                        class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
                                                        <div id="poTablePagination"></div>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>

                                        <div x-data="invoiceFormModal()" class="min-w-fit">
                                            <div class="w-full flex justify-start">
                                                <button type="button" @click="openModal()"
                                                    class="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-3 py-2 text-white hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="1.5" d="M12 4.5v15m7.5-7.5h-15" />
                                                    </svg>
                                                    Add Faktur
                                                </button>
                                            </div>

                                            <div x-show="show" x-transition.opacity
                                                class="fixed inset-0 z-40 bg-black/50"
                                                @keydown.escape.window="closeModal()"></div>
                                            <div>
                                                <div x-show="show" x-cloak x-transition.opacity
                                                    class="fixed inset-0 z-50 flex items-center justify-center overflow-hidden p-3 md:p-6"
                                                    aria-modal="true" role="dialog">
                                                    <div class="relative w-full max-w-7xl rounded-xl bg-white shadow-2xl flex flex-col overflow-hidden"
                                                        style="height: min(760px, calc(100vh - 1.5rem));">
                                                        <div
                                                            class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-sky-50 to-white">
                                                            <div>
                                                                <h3 class="text-xl font-bold text-gray-800">Add Faktur</h3>
                                                                <p class="text-sm text-gray-500 mt-0.5">Pilih transaksi faktur penjualan</p>
                                                            </div>
                                                            <button type="button" @click="closeModal()"
                                                                class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                                                                Tutup
                                                            </button>
                                                        </div>
                                                        <div class="flex-1 overflow-hidden p-6" style="min-height: 0;">
                                                            <table id="invoiceTable"
                                                                class="min-w-full text-sm display nowrap stripe hover"
                                                                style="width:100%">
                                                                <thead class="sticky top-0 z-10">
                                                                    <tr class="bg-gray-50 border-b-2 border-gray-200">
                                                                        <th
                                                                            class="p-3 text-left font-semibold text-gray-700">
                                                                            Cab</th>
                                                                        <th
                                                                            class="p-3 text-left font-semibold text-gray-700">
                                                                            No Faktur</th>
                                                                        <th
                                                                            class="p-3 text-left font-semibold text-gray-700">
                                                                            Tanggal</th>
                                                                        <th
                                                                            class="p-3 text-left font-semibold text-gray-700">
                                                                            No Ref</th>
                                                                        <th
                                                                            class="p-3 text-left font-semibold text-gray-700">
                                                                            Customer</th>
                                                                        <th
                                                                            class="p-3 text-center font-semibold text-gray-700">
                                                                            Aksi</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody></tbody>
                                                            </table>
                                                        </div>
                                                        <div
                                                            class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
                                                            <div id="invoiceTablePagination"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div x-show="showDupModal" x-cloak x-transition.opacity
                                                class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                                                <div class="absolute inset-0 bg-black/50" @click="closeDupModal()"></div>

                                                <div
                                                    class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden">
                                                    <div class="px-5 py-4 border-b flex items-center gap-2 bg-amber-50">
                                                        <svg class="w-6 h-6 text-amber-600" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                        </svg>
                                                        <h3 class="text-lg font-semibold text-gray-800">Item Duplikat
                                                            Ditemukan
                                                        </h3>
                                                    </div>

                                                    <div class="px-5 py-4 space-y-3">
                                                        <p class="text-sm text-gray-700">
                                                            Ditemukan <span class="font-semibold text-amber-600"
                                                                x-text="dupCount"></span>
                                                            item duplikat.
                                                            Item duplikat <span class="font-semibold">tidak akan
                                                                ditambahkan</span>.
                                                        </p>

                                                        <div class="rounded-lg border border-amber-200 bg-amber-50">
                                                            <div
                                                                class="px-3 py-2 border-b border-amber-200 text-sm font-bold text-gray-800">
                                                                Preview Item Duplikat
                                                            </div>
                                                            <ul class="max-h-40 overflow-auto divide-y divide-amber-100">
                                                                <template x-for="d in dupSample"
                                                                    :key="`${d.fitemcode}::${d.fitemname}`">
                                                                    <li
                                                                        class="px-3 py-2 text-sm flex items-center gap-2 hover:bg-amber-100 transition-colors">
                                                                        <span
                                                                            class="inline-flex w-5 h-5 items-center justify-center rounded-full bg-amber-200 text-amber-800 text-xs font-bold">!</span>
                                                                        <span class="font-mono font-bold text-gray-700"
                                                                            x-text="d.fitemcode || '-'"></span>
                                                                        <span class="text-gray-600 truncate"
                                                                            x-text="d.fitemname || '-'"></span>
                                                                    </li>
                                                                </template>
                                                            </ul>
                                                        </div>
                                                    </div>

                                                    <div
                                                        class="px-5 py-3 border-t bg-gray-50 flex items-center justify-end gap-2">
                                                        <button type="button" @click="closeDupModal()"
                                                            class="h-9 px-4 rounded-lg border-2 border-gray-300 text-gray-700 text-sm font-bold hover:bg-gray-100 transition-colors">
                                                            Batal
                                                        </button>
                                                        <button x-show="pendingUniques.length > 0" type="button"
                                                            @click="confirmAddUniques()"
                                                            class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-bold hover:bg-emerald-700">
                                                            Lanjut Tambah yang Valid
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- MODAL DUPLIKASI --}}
                                        <div x-data="{ showDupModal: false, dupCount: 0, dupSample: [], closeDupModal() { this.showDupModal = false } }" x-show="showDupModal" x-cloak x-transition.opacity
                                            class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                                            <div class="absolute inset-0 bg-black/50" @click="closeDupModal()"></div>

                                            <div
                                                class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden">
                                                <!-- Header -->
                                                <div class="px-5 py-4 border-b flex items-center gap-2 bg-amber-50">
                                                    <svg class="w-6 h-6 text-amber-600" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                    </svg>
                                                    <h3 class="text-lg font-semibold text-gray-800">Item Duplikat Ditemukan
                                                    </h3>
                                                </div>

                                                <!-- Body -->
                                                <div class="px-5 py-4 space-y-3">
                                                    <p class="text-sm text-gray-700">
                                                        Ditemukan <span class="font-semibold text-amber-600"
                                                            x-text="dupCount"></span>
                                                        item duplikat.
                                                        Item duplikat <span class="font-semibold">tidak akan
                                                            ditambahkan</span>.
                                                    </p>

                                                    <!-- Preview list -->
                                                    <div class="rounded-lg border border-amber-200 bg-amber-50">
                                                        <div
                                                            class="px-3 py-2 border-b border-amber-200 text-sm font-bold text-gray-800">
                                                            Preview Item Duplikat
                                                        </div>
                                                        <ul class="max-h-40 overflow-auto divide-y divide-amber-100">
                                                            <template x-for="d in dupSample"
                                                                :key="`${d.fitemcode}::${d.fitemname}`">
                                                                <li
                                                                    class="px-3 py-2 text-sm flex items-center gap-2 hover:bg-amber-100 transition-colors">
                                                                    <span
                                                                        class="inline-flex w-5 h-5 items-center justify-center rounded-full bg-amber-200 text-amber-800 text-xs font-bold">!</span>
                                                                    <span class="font-mono font-bold text-gray-700"
                                                                        x-text="d.fitemcode || '-'"></span>
                                                                    <span class="text-gray-400">•</span>
                                                                    <span class="text-gray-600 truncate"
                                                                        x-text="d.fitemname || '-'"></span>
                                                                </li>
                                                            </template>
                                                            <template x-if="dupCount === 0">
                                                                <li class="px-3 py-2 text-sm text-gray-500 text-center">
                                                                    Tidak
                                                                    ada
                                                                    contoh.</li>
                                                            </template>
                                                        </ul>
                                                        <div x-show="dupCount > 6"
                                                            class="px-3 py-2 text-xs text-center text-amber-700 border-t border-amber-200">
                                                            ... dan <span x-text="dupCount - 6"></span> item lainnya
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Footer -->
                                                <div
                                                    class="px-5 py-3 border-t bg-gray-50 flex items-center justify-end gap-2">
                                                    <button type="button" @click="closeDupModal()"
                                                        class="h-9 px-4 rounded-lg border-2 border-gray-300 text-gray-700 text-sm font-bold hover:bg-gray-100 transition-colors">
                                                        {{ 'Batal' }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- MODAL DESC (di dalam itemsTable) -->
                                    <div x-show="showDescModal" x-cloak
                                        class="fixed inset-0 z-[95] flex items-center justify-center" x-transition.opacity>
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

                                                <label class="block text-sm text-gray-700">Deskripsi</label>
                                                <textarea x-model="descValue" rows="5" class="w-full border rounded px-3 py-2"
                                                    placeholder="Tulis deskripsi item di sini..."></textarea>
                                            </div>

                                            <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
                                                <button type="button" @click="closeDesc()"
                                                    class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">
                                                    {{ 'Batal' }}
                                                </button>
                                                <button type="button" @click="applyDesc()"
                                                    class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
                                                    Simpan
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <input type="hidden" id="itemsCount" :value="submitItems.length">
                                </div>
                            </div>
                        </div>

                        {{-- MODAL ERROR: belum ada item --}}
                        <div x-show="showNoItems && submitItems.length === 0" x-cloak
                            class="fixed inset-0 z-[90] flex items-center justify-center" x-transition.opacity>
                            <div class="absolute inset-0 bg-black/50" @click="showNoItems=false"></div>

                            <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden"
                                x-transition.scale>
                                <div class="px-5 py-4 border-b flex items-center">
                                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-500 mr-2" />
                                    <h3 class="text-lg font-semibold text-gray-800">{{ 'Tidak Ada Item' }}</h3>
                                </div>

                                <div class="px-5 py-4">
                                    <p class="text-sm text-gray-700">
                                        Anda belum menambahkan item apa pun pada tabel. Silakan isi baris “Detail
                                        Item”
                                        terlebih
                                        dahulu.
                                    </p>
                                </div>

                                <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
                                    <button type="button" @click="showNoItems=false"
                                        class="h-9 px-4 rounded-lg bg-blue-600 text-white text-sm font-bold hover:bg-blue-700">
                                        OK
                                    </button>
                                </div>
                            </div>
                        </div>

                        <x-transaction.browse-customer-modal />

                        <x-transaction.browse-warehouse-modal />

                        <x-transaction.browse-product-modal show-controls="true" show-pagination="true" />
                    </div>

                    {{-- ─── CARD 3: Aksi ────────────────── --}}
                    <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                        <div class="p-4 bg-gray-50 flex justify-end gap-3 items-center space-x-2">
                            <button type="button"
                                @click="window.location.href='{{ route('suratjalan.index') }}'"
                                class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 flex items-center text-sm font-semibold shadow-sm transition">
                                <x-heroicon-o-arrow-left class="w-4 h-4 mr-1.5" />
                                Keluar
                            </button>
                            @if ($canEditPermission)
                                @if ($usageLocked)
                                    <button type="button" disabled title="{{ $usageLockMessage }}"
                                        class="bg-blue-300 text-white px-4 py-2 rounded-lg flex items-center cursor-not-allowed opacity-70 text-sm font-semibold shadow-sm">
                                        <x-heroicon-o-lock-closed class="w-4 h-4 mr-1.5" /> Simpan
                                    </button>
                                @else
                                    <button type="submit"
                                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center text-sm font-semibold shadow-sm transition">
                                        <x-heroicon-o-check class="w-4 h-4 mr-1.5" /> Simpan
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                        </form>
                @endif
            </div>
        </div>
    </div>

    {{-- ============================================ --}}
    {{-- MODAL & TOAST (HANYA UNTUK MODE DELETE)     --}}
    {{-- ============================================ --}}
    @if ($action === 'delete' && $canDeletePermission)
        {{-- Modal Delete --}}
        <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold mb-4">{{ 'Konfirmasi Hapus' }}</h3>
                <form id="deleteForm" action="{{ route('suratjalan.destroy', $suratjalan->fstockmtid) }}"
                    method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="flex justify-end space-x-2">
                        <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400"
                            id="btnTidak">
                            Tidak
                        </button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                            Ya, Hapus
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function showDeleteModal() {
                document.getElementById('deleteModal').classList.remove('hidden');
            }

            function closeDeleteModal() {
                document.getElementById('deleteModal').classList.add('hidden');
            }

            function closeToast() {
                document.getElementById('toast').classList.add('hidden');
            }

            function showToast(message, isSuccess = true) {
                const toast = document.getElementById('toast');
                const toastContent = document.getElementById('toastContent');
                const toastMessage = document.getElementById('toastMessage');

                toastMessage.textContent = message;
                toastContent.className = isSuccess ?
                    'bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg flex items-center' :
                    'bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg flex items-center';

                toast.classList.remove('hidden');
            }

            function confirmDelete() {
                const btnYa = document.getElementById('btnYa');
                const btnTidak = document.getElementById('btnTidak');

                btnYa.disabled = true;
                btnTidak.disabled = true;
                btnYa.textContent = 'Menghapus...';

                fetch('{{ route('suratjalan.destroy', $suratjalan->fstockmtid) }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            _method: 'DELETE'
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        closeDeleteModal();
                        showToast(data.message || 'Data berhasil dihapus.', true);

                        setTimeout(() => {
                            window.location.href = '{{ route('suratjalan.index') }}';
                        }, 500);
                    })
                    .catch(error => {
                        btnYa.disabled = false;
                        btnTidak.disabled = false;
                        btnYa.textContent = 'Ya, Hapus';
                        showToast('Terjadi kesalahan saat hapus data.', false);
                    });
            }
        </script>
    @endif
@endsection
@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
@endpush
<x-transaction.datatables-length-styles :tables="['supplierTable', 'warehouseTable', 'productTable', 'poTable', 'invoiceTable']" />
{{-- DATA & SCRIPTS --}}
<script>
    // Map produk untuk auto-fill tabel
    window.PRODUCT_MAP = {
        @foreach ($products as $p)
            "{{ $p->fprdcode }}": {
                name: @json($p->fprdname),
                default_unit: @json($productMap[$p->fprdcode]['default_unit'] ?? $p->fsatuankecil),
                units: @json(
                    $productMap[$p->fprdcode]['units'] ??
                        array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2]))),
                stock: @json($p->fminstock ?? 0),
                unit_names: {
                    satuankecil: @json($p->fsatuankecil),
                    satuanbesar: @json($p->fsatuanbesar),
                    satuanbesar2: @json($p->fsatuanbesar2),
                },
                unit_ratios: {
                    satuankecil: 1,
                    satuanbesar: @json((float) ($p->fqtykecil ?? 1)),
                    satuanbesar2: @json((float) ($p->fqtykecil2 ?? 1)),
                },
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

    window.getSuratJalanDuplicateCode = function(form) {
        return '';
    };

    document.addEventListener('alpine:init', () => {
        Alpine.store('trsomt', {
            descPreview: {
                uid: null,
                index: null,
                label: '',
                text: ''
            },
            descList: []
        });
    });

    function itemsTable() {
        return {
            savedItems: @json(count($initialEditSuratJalanItems) ? $initialEditSuratJalanItems : $savedItems),
            nextFormIndex: @json($nextSuratJalanItemIndex),
            minimumVisibleRows: @json((count($initialEditSuratJalanItems) ? count($initialEditSuratJalanItems) : count($savedItems ?? [])) + 5),
            browseTarget: null,
            showDescModal: false,
            descSavedIndex: null,
            descItemName: '',
            descValue: '',
            isReadOnlyMode: @json($isReadOnly),

            totalHarga: 0,

            fmt(n) {
                if (n === null || n === undefined || n === '') return '-';
                const v = Number(n);
                if (!isFinite(v)) return '-';

                // Jika angka adalah bulat, hilangkan desimal
                return v.toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            },

            formatQtyValue(value) {
                const num = Number(value);
                if (!Number.isFinite(num)) return '0,00';
                return num.toLocaleString('id-ID', {
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
                this.$nextTick(() => {
                    row.fqty = Math.max(0, Number(row.fqty) || 0);
                    row.fterima = Math.max(0, Number(row.fterima) || 0);
                    row.fprice = Math.max(0, Number(row.fprice) || 0);

                    row.ftotal = Number((row.fqty * row.fprice).toFixed(2));

                    this.recalcTotals();
                });
            },

            recalcTotals() {
                this.totalHarga = (this.savedItems || []).reduce((sum, it) => {
                    if (!this.isRowSavable(it)) return sum;
                    const v = Number(it?.ftotal ?? 0);
                    return sum + (Number.isFinite(v) ? v : 0);
                }, 0);
            },

            removeSaved(i) {
                this.savedItems.splice(i, 1);
                this.syncDescList?.();
                if (!this.isReadOnlyMode) {
                    this.ensureMinimumRows();
                    this.ensureTrailingRow();
                }
                this.recalcTotals();
            },

            productMeta(code) {
                const key = (code || '').trim();
                const meta = window.PRODUCT_MAP?.[key];
                if (!meta) {
                    return {
                        name: '',
                        units: [],
                        stock: 0,
                        unit_names: {
                            satuankecil: '',
                            satuanbesar: '',
                            satuanbesar2: ''
                        },
                        unit_ratios: {
                            satuankecil: 1,
                            satuanbesar: 1,
                            satuanbesar2: 1
                        }
                    };
                }
                return meta;
            },

            getUnitRatio(meta, satuan) {
                const unit = (satuan || '').toString().trim();
                const names = meta?.unit_names || {};
                const ratios = meta?.unit_ratios || {};

                if (unit && unit === (names.satuanbesar2 || '').toString().trim() && Number(ratios.satuanbesar2) > 0) {
                    return Number(ratios.satuanbesar2);
                }

                if (unit && unit === (names.satuanbesar || '').toString().trim() && Number(ratios.satuanbesar) > 0) {
                    return Number(ratios.satuanbesar);
                }

                return 1;
            },

            qtyKecilToUnit(qtyKecil, satuan, meta) {
                const qty = Number(qtyKecil ?? 0);
                if (!Number.isFinite(qty) || qty <= 0) return 0;

                const ratio = this.getUnitRatio(meta, satuan);
                return ratio > 0 ? qty / ratio : qty;
            },

            formatStockLimit(row) {
                return '';
            },

            rowHasContent(row) {
                return [
                    row?.fitemcode,
                    row?.fitemname,
                    row?.fsatuan,
                    row?.frefdtno,
                    row?.frefpr,
                    row?.frefso,
                    row?.fqty,
                    row?.fprice,
                    row?.ftotal,
                    row?.fdesc,
                    row?.fketdt,
                ].some((value) => {
                    if (typeof value === 'number') return value !== 0;
                    return String(value ?? '').trim() !== '';
                });
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

            addRowsToEmptySlots(rows) {
                rows.forEach((row) => {
                    const emptyIndex = this.savedItems.findIndex((item) => !this.rowHasContent(item));
                    if (emptyIndex === -1) {
                        this.savedItems.push(row);
                    } else {
                        this.savedItems.splice(emptyIndex, 1, row);
                    }
                });
            },

            onRowUpdated(index = null) {
                const row = typeof index === 'number' ? this.savedItems[index] : null;
                if (row) {
                    this.recalc(row);
                }
                this.recalcTotals();
                if (!this.isReadOnlyMode) {
                    this.ensureTrailingRow(index);
                }
            },

            getRowQtyLimit(row) {
                if (!row?.fitemcode) return 0;

                const meta = this.productMeta(row.fitemcode);
                const limitSource = Number(row.maxqty ?? 0);
                if (!Number.isFinite(limitSource) || limitSource <= 0) return 0;

                const satuan = row.fsatuan || '';
                const ratio = this.getUnitRatio(meta, satuan);

                const limit = limitSource / ratio;
                return Number.isFinite(limit) && limit > 0 ? limit : 0;
            },

            formatQtyLimit(limit) {
                const numericLimit = Number(limit ?? 0);
                if (!Number.isFinite(numericLimit) || numericLimit <= 0) {
                    return '0';
                }

                return numericLimit.toFixed(2).replace(/\.00$/, '').replace(/(\.\d*[1-9])0+$/, '$1');
            },

            validateSoQtyRow(row, showToast = true) {
                const refDoc = String(row?.frefso ?? '').trim();
                if (!refDoc) return true;
                const refLabel = refDoc.startsWith('INV.') ? 'Faktur Penjualan' : 'SO';

                const limit = this.getRowQtyLimit(row);
                if (limit <= 0) {
                    if (showToast) {
                        window.toast?.error(`Qty ${refLabel} untuk item ini sudah habis atau sudah digunakan.`);
                    }
                    return false;
                }

                const qty = Number(row?.fqty ?? 0);
                if (qty > limit) {
                    if (showToast) {
                        window.toast?.error(
                            `Qty melebihi sisa ${refLabel}. Maksimal ${this.formatQtyLimit(limit)} ${row.fsatuan || ''}`
                            .trim());
                    }
                    return false;
                }

                return Number(row?.fqty ?? 0) > 0;
            },

            enforceQtyRow(row) {
                const n = +row.fqty;
                const meta = this.productMeta(row.fitemcode);
                const units = meta?.units || [];
                const ratios = meta?.unit_ratios || {
                    satuankecil: 1,
                    satuanbesar: 1,
                    satuanbesar2: 1
                };
                const satKecil = units[0] || 'pcs';
                const satBesar = units[1] || '';
                const satBesar2 = units[2] || '';
                const satuan = row.fsatuan || '';

                let ratio = 1;
                if (satuan === satBesar2 && ratios.satuanbesar2 > 0) {
                    ratio = ratios.satuanbesar2;
                } else if (satuan === satBesar && ratios.satuanbesar > 0) {
                    ratio = ratios.satuanbesar;
                }

                if (!Number.isFinite(n)) {
                    row.fqty = 0;
                    return;
                }
                if (n < 0) row.fqty = 0;
            },

            hydrateRowFromMeta(row, meta, forceDefaultUnit = false) {
                if (!meta) {
                    row.fitemname = '';
                    row.units = [];
                    row.fsatuan = '';
                    row.maxqty = 0;
                    row.frefdtno = '';
                    return;
                }
                row.fitemname = meta.name || '';
                const units = [...new Set((meta.units || []).map(u => (u ?? '').toString().trim()).filter(Boolean))];
                const currentUnit = (row.fsatuan ?? '').toString().trim();
                row.units = units;
                const defaultUnit = (meta.default_unit || '').toString().trim();
                const resolvedDefaultUnit = defaultUnit && units.includes(defaultUnit) ? defaultUnit : (units[0] || '');
                if (forceDefaultUnit) {
                    row.fsatuan = resolvedDefaultUnit;
                } else {
                    row.fsatuan = currentUnit;
                    if (currentUnit && !units.includes(currentUnit)) {
                        row.units.unshift(currentUnit);
                    }
                    if (!row.fsatuan) {
                        row.fsatuan = resolvedDefaultUnit;
                    }
                }
                if (meta.unit_ratios) row.unit_ratios = meta.unit_ratios;
                row.maxqty = Number.isFinite(+row.maxqty) ? +row.maxqty : 0;
            },

            onCodeTypedRow(row, index = null) {
                const hasReference = String(row?.frefso ?? '').trim() !== '' || String(row?.frefdtno ?? '').trim() !==
                    '';
                if (hasReference) {
                    row.fitemcode = (row?.foriginalitemcode ?? row?.fitemcode ?? '').toString().trim();
                    this.onRowUpdated(index);
                    return;
                }
                this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode), true);
                row.fnoacak = this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak(row.uid);
                this.onRowUpdated(index);
            },

            isRowSavable(row) {
                return row.fitemcode && row.fitemname && row.fsatuan && Number(row.fqty) > 0;
            },

            normalizeNoAcak(value) {
                const normalized = String(value ?? '').trim();
                return /^\d{3}$/.test(normalized) ? normalized : '';
            },

            generateUniqueNoAcak(exceptUid = null) {
                const used = new Set(
                    this.savedItems
                    .filter(item => item.uid !== exceptUid)
                    .map(item => this.normalizeNoAcak(item.fnoacak))
                    .filter(Boolean)
                );
                let candidate = '';
                do {
                    candidate = Array.from({
                        length: 3
                    }, () => '123456789' [Math.floor(Math.random() * 9)]).join('');
                } while (used.has(candidate));

                return candidate;
            },

            onPrPicked(e) {
                const {
                    header,
                    items
                } = e.detail || {};
                if (!items || !Array.isArray(items)) return;
                this.addManyFromPR(header, items);
            },

            addManyFromPR(header, items) {
                if (!items || !Array.isArray(items)) {
                    window.toast?.error('Data items tidak valid atau kosong.');
                    return;
                }

                const internalNoteInput = document.getElementById('fketinternal');
                if (internalNoteInput) {
                    const currentValue = String(internalNoteInput.value ?? '').trim();
                    const sourceValue = String(header?.fketinternal ?? '').trim();
                    if (sourceValue !== '' && currentValue === '') {
                        internalNoteInput.value = sourceValue;
                    }
                }

                const shippingAddressInput = document.querySelector('textarea[name="fkirim"]');
                if (shippingAddressInput) {
                    const currentValue = String(shippingAddressInput.value ?? '').trim();
                    const sourceValue = String(header?.falamatkirim ?? '').trim();
                    if (sourceValue !== '' && currentValue === '') {
                        shippingAddressInput.value = sourceValue;
                    }
                }

                const existing = new Set(this.getCurrentItemKeys());
                let added = 0,
                    duplicates = [],
                    skipped = [];
                const rowsToAdd = [];

                items.forEach((src, index) => {
                    const itemcode = (src.fitemcode ?? '').toString().trim();
                    const itemname = (src.fitemname ?? '').toString().trim();
                    const satuan = (src.fsatuan ?? '').toString().trim();
                    const frefdtno = (header?.fstockmtno ?? header?.fsono ?? src.frefdtno ?? '').toString()
                        .trim();

                    // VALIDASI MINIMAL: harus ada kode, nama, dan satuan
                    if (!itemcode || !itemname || !satuan) {
                        skipped.push({
                            code: itemcode || 'NO_CODE',
                            reason: 'Data tidak lengkap'
                        });
                        return;
                    }

                    const meta = this.productMeta(itemcode);
                    const normalizedUnits = meta ? [...new Set((meta.units || []).map(u => (u ?? '').toString()
                        .trim()).filter(Boolean))] : [satuan].filter(Boolean);

                    if (satuan && !normalizedUnits.includes(satuan)) {
                        normalizedUnits.unshift(satuan);
                    }

                    const displayQty = Number(src.fqty ?? 0) > 0 ?
                        Number(src.fqty) :
                        (Number(src.fqtyremain_dokumen ?? 0) > 0 ?
                            Number(src.fqtyremain_dokumen) :
                            this.qtyKecilToUnit(src.fqtyremain, satuan, meta));

                    const row = {
                        uid: cryptoRandom(),
                        fitemcode: itemcode,
                        fitemname: itemname,
                        fsatuan: satuan,
                        frefdtno: frefdtno,
                        fnoacak: this.generateUniqueNoAcak(),
                        frefnoacak: this.normalizeNoAcak(src.frefnoacak ?? src.fnoacak ?? ''),
                        frefno_display: (src.frefpr ?? header?.fsono ?? '').toString().trim(),
                        frefpr: (src.frefpr ?? header?.fpono ?? header?.fsono ?? '').toString().trim(),
                        frefso: header?.fsono ?? null,
                        fdiscpersen: Number(src.fdiscpersen ?? src.fdisc ?? 0),
                        fqty: displayQty > 0 ? displayQty : 1,
                        fprice: Number(src.fprice ?? src.fharga ?? 0), // ← Boleh 0
                        fterima: Number(src.fterima ?? 0),
                        ftotal: 0,
                        fdesc: src.fdesc ? src.fdesc.toString().trim() : '',
                        fketdt: src.fketdt ? src.fketdt.toString().trim() : '',
                        units: normalizedUnits,
                        maxqty: Math.max(0, Number(src.fqtyremain ?? 0)),
                        hideQtyLimitHint: false,
                    };

                    if (!(Number(row.maxqty) > 0)) return;
                    row.ftotal = Number((row.fqty * row.fprice).toFixed(2));

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

                    rowsToAdd.push({
                        ...this.createRow(),
                        ...row,
                        foriginalitemcode: row.fitemcode,
                    });
                    existing.add(key);
                    added++;
                });

                if (rowsToAdd.length > 0) {
                    this.addRowsToEmptySlots(rowsToAdd);
                }

                if (!this.isReadOnlyMode) {
                    this.ensureMinimumRows();
                    this.ensureTrailingRow();
                }
                this.recalcTotals();

                // Tampilkan notifikasi
                if (added > 0) {
                    window.toast?.success(`✓ Berhasil menambahkan ${added} item ke detail`);
                }

                if (duplicates.length > 0) {
                    window.toast?.info(`${duplicates.length} item diabaikan (sudah ada)`);
                }

                if (skipped.length > 0) {
                    window.toast?.error(`${skipped.length} item diabaikan (data tidak lengkap)`);
                }

                if (added === 0 && duplicates.length === 0 && skipped.length === 0) {
                    window.toast?.error('Tidak ada item yang valid untuk ditambahkan');
                }
            },
            get submitItems() {
                return this.savedItems.filter(row => this.isRowSavable(row));
            },

            openDesc(index = null) {
                this.descSavedIndex = index;
                this.descItemName = index !== null ? (this.savedItems[index]?.fitemname || '') : '';
                this.descValue = index !== null ? (this.savedItems[index]?.fdesc || '') : '';
                this.showDescModal = true;
            },
            copyDescName() {
                this.descValue = this.descItemName || '';
            },
            closeDesc() {
                this.showDescModal = false;
                this.descSavedIndex = null;
                this.descItemName = '';
                this.descValue = '';
            },
            applyDesc() {
                if (this.descSavedIndex !== null) {
                    this.savedItems[this.descSavedIndex].fdesc = this.descValue;
                    this.onRowUpdated(this.descSavedIndex);
                }
                this.closeDesc();
            },

            itemKey(it) {
                return (it.fitemcode ?? '').toString().trim().toUpperCase();
            },

            getCurrentItemKeys() {
                return this.savedItems
                    .filter(it => it.fitemcode)
                    .map(it => this.itemKey(it));
            },

            allocateFormIndex() {
                const index = Number(this.nextFormIndex || 0);
                this.nextFormIndex = index + 1;
                return index;
            },

            createRow(overrides = {}) {
                return {
                    ...newRow(),
                    uid: overrides.uid || cryptoRandom(),
                    formIndex: overrides.formIndex ?? this.allocateFormIndex(),
                    ...overrides,
                    foriginalitemcode: (overrides.foriginalitemcode ?? overrides.fitemcode ?? '').toString().trim(),
                    fnoacak: this.normalizeNoAcak(overrides.fnoacak) || this.generateUniqueNoAcak(overrides.uid ||
                        null),
                    frefnoacak: this.normalizeNoAcak(overrides.frefnoacak),
                };
            },

            focusRowUnit(row, index) {
                if (row.units && row.units.length > 1) {
                    this.$nextTick(() => document.getElementById(`unit_row_${index}`)?.focus());
                    return;
                }
                this.focusRowQty(index);
            },

            focusRowQty(index) {
                this.$nextTick(() => document.getElementById(`qty_row_${index}`)?.focus());
            },

            normalizeRestoredRow(item, index = 0) {
                const keepOldValues = Boolean(item?.is_restored_old);
                const row = this.createRow({
                    ...(item || {}),
                    uid: item?.uid || `restored-${index}`,
                });
                const oldValues = keepOldValues ? {
                    fitemname: row.fitemname,
                    fsatuan: row.fsatuan,
                    frefdtno: row.frefdtno,
                    frefpr: row.frefpr,
                    frefso: row.frefso,
                    frefno_display: row.frefno_display,
                } : null;
                this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode));
                if (oldValues) {
                    Object.assign(row, oldValues);
                    if (row.fsatuan && !row.units.includes(row.fsatuan)) {
                        row.units.unshift(row.fsatuan);
                    }
                }
                this.recalc(row);
                return row;
            },

            pruneEmptyRows() {
                const filled = this.savedItems.filter(row => this.rowHasContent(row));
                this.savedItems = filled.length ? filled : [];
            },

            init() {
                window.getCurrentItemKeys = () => this.getCurrentItemKeys();
                this.savedItems = Array.isArray(this.savedItems) ?
                    this.savedItems.map((item, index) => this.normalizeRestoredRow(item, index)) : [];
                this.pruneEmptyRows();

                this.savedItems.forEach((item) => {
                    const soLimit = Number(item.maxqty ?? item.fqtyremain ?? 0);
                    item.maxqty = Number.isFinite(soLimit) ? soLimit : 0;
                    item.hideQtyLimitHint = false;
                });
                if (!this.isReadOnlyMode) {
                    this.ensureMinimumRows();
                    this.ensureTrailingRow();
                }

                window.addEventListener('pr-picked', this.onPrPicked.bind(this), {
                    passive: true
                });

                window.addEventListener('product-chosen', (e) => {
                    const {
                        product
                    } = e.detail || {};
                    if (!product) return;

                    const index = Number.isInteger(this.browseTarget) ? this.browseTarget : -1;
                    if (index < 0 || !this.savedItems[index]) return;

                    const row = this.savedItems[index];
                    const apply = () => {
                        row.fitemcode = (product.fprdcode || '').toString();
                        row.frefdtno = '';
                        row.frefpr = '';
                        row.frefso = null;
                        row.frefno_display = '';
                        row.hideQtyLimitHint = true;
                        this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode), true);
                        row.fnoacak = this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak(row
                            .uid);
                        this.onRowUpdated(index);
                    };
                    apply();
                    this.focusRowQty(index);
                }, {
                    passive: true
                });

                this.$nextTick(() => {
                    this.recalcTotals();
                });
            },

            openBrowseFor(index) {
                this.browseTarget = index;
                window.dispatchEvent(new CustomEvent('browse-open', {
                    detail: {
                        forEdit: false
                    }
                }));
            },
        };

        function newRow() {
            return {
                uid: null,
                formIndex: null,
                fitemcode: '',
                fitemname: '',
                units: [],
                fsatuan: '',
                frefdtno: '',
                fnoacak: '',
                frefnoacak: '',
                frefno_display: '',
                frefpr: '',
                frefso: null,
                foriginalitemcode: '',
                fqty: 0,
                fprice: 0,
                ftotal: 0,
                fdesc: '',
                fketdt: '',
                maxqty: 0,
                hideQtyLimitHint: false,
            };
        }

        function cryptoRandom() {
            return (window.crypto?.getRandomValues ? [...window.crypto.getRandomValues(new Uint32Array(2))].map(n => n
                    .toString(16)).join('') :
                Math.random().toString(36).slice(2)) + Date.now();
        }
    }
</script>

@include('components.transaction.suratjalan-so-modal-script')
@include('components.transaction.suratjalan-invoice-modal-script')

<script>
    // Helper function untuk format tanggal
    function formatDate(s) {
        if (!s || s === 'No Date') return '-';
        const d = new Date(s);
        if (isNaN(d)) return '-';
        const pad = n => n.toString().padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
    }
</script>

<script>
    // Helper: update field saat warehouse-picked
    document.addEventListener('DOMContentLoaded', () => {
        window.addEventListener('warehouse-picked', (ev) => {
            const {
                fwhcode,
                fwhid,
                fwhname
            } = ev.detail || {};
            const sel = document.getElementById('warehouseSelect');
            const hidId = document.getElementById('warehouseIdHidden');
            const hidCode = document.getElementById('warehouseCodeHidden');

            if (sel) {
                const code = String(fwhcode || '').trim();
                let opt = [...sel.options].find(o => String(o.value).trim() === code);
                if (code && !opt) {
                    opt = new Option(fwhname ? `${fwhname} (${code})` : code, code, true, true);
                    sel.add(opt);
                }
                sel.value = opt ? opt.value : code;
                sel.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }
            if (hidId) {
                hidId.value = fwhid || '';
                hidId.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }
            if (hidCode) {
                hidCode.value = fwhcode || '';
                hidCode.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }
        });
    });
</script>

@include('components.transaction.browse-product-script', [
    'showControls' => true,
    'showPagination' => true,
    'supportsForEdit' => true,
    'openDelay' => 50,
])

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    <script>
        window.PRODUCT_MAP = @json($productMap ?? []);
    </script>

    @include('components.transaction.browse-customer-script')

    @include('components.transaction.browse-warehouse-script')

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
