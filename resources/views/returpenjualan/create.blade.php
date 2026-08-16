@extends('layouts.app')

@section('title', 'Retur Penjualan - New')

@section('content')
    @php
        $oldReturJualCodes = old('fitemcode', []);
        $oldReturJualNames = old('fitemname', []);
        $oldReturJualUnits = old('fsatuan', []);
        $oldReturJualRefCodes = old('frefcode', []);
        $oldReturJualRefNos = old('frefdtno', []);
        $oldReturJualNouRefs = old('fnouref', []);
        $oldReturJualRefPrs = old('frefpr', []);
        $oldReturJualRefSos = old('frefso', []);
        $oldReturJualRefSrjs = old('frefsrj', []);
        $oldReturJualNoAcaks = old('fnoacak', []);
        $oldReturJualRefNoAcaks = old('frefnoacak', []);
        $oldReturJualQtys = old('fqty', []);
        $oldReturJualTerimas = old('fterima', []);
        $oldReturJualPrices = old('fprice', []);
        $oldReturJualDiscs = old('fdisc', []);
        $oldReturJualTotals = old('ftotal', []);
        $oldReturJualDescs = old('fdesc', []);
        $oldReturJualKetdts = old('fketdt', []);
        $initialReturPenjualanItems = [];

        $oldReturJualIndexes = array_keys(is_array($oldReturJualCodes) ? $oldReturJualCodes : []);

        foreach ($oldReturJualIndexes as $index) {
            $itemCode = $oldReturJualCodes[$index] ?? '';
            $code = trim((string) $itemCode);
            $name = trim((string) ($oldReturJualNames[$index] ?? ''));
            if ($code === '' && $name === '') {
                continue;
            }

            $unit = trim((string) ($oldReturJualUnits[$index] ?? ''));
            $refSo = trim((string) ($oldReturJualRefSos[$index] ?? ''));
            $refSrj = trim((string) ($oldReturJualRefSrjs[$index] ?? ''));

            $initialReturPenjualanItems[] = [
                'uid' => 'old-returjual-' . $index,
                'formIndex' => (int) $index,
                'is_restored_old' => true,
                'fitemcode' => $code,
                'fitemname' => $name,
                'frefcode' => trim((string) ($oldReturJualRefCodes[$index] ?? '')),
                'units' => $unit !== '' ? [$unit] : [],
                'fsatuan' => $unit,
                'frefdtno' => trim((string) ($oldReturJualRefNos[$index] ?? '')),
                'fnouref' => trim((string) ($oldReturJualNouRefs[$index] ?? '')),
                'frefpr' => trim((string) ($oldReturJualRefPrs[$index] ?? '')),
                'frefso' => $refSo,
                'frefsrj' => $refSrj,
                'fnoacak' => trim((string) ($oldReturJualNoAcaks[$index] ?? '')),
                'frefnoacak' => trim((string) ($oldReturJualRefNoAcaks[$index] ?? '')),
                'fqty' => (float) ($oldReturJualQtys[$index] ?? 0),
                'fterima' => (float) ($oldReturJualTerimas[$index] ?? 0),
                'fprice' => (float) ($oldReturJualPrices[$index] ?? 0),
                'ref_price' => (float) (old('fref_price', [])[$index] ?? $oldReturJualPrices[$index] ?? 0),
                'maxprice' => (float) (old('fmaxprice', [])[$index] ?? $oldReturJualPrices[$index] ?? 0),
                'source_price' => (float) (old('fsource_price', [])[$index] ?? $oldReturJualPrices[$index] ?? 0),
                'fdisc' => $oldReturJualDiscs[$index] ?? 0,
                'ftotal' => (float) ($oldReturJualTotals[$index] ?? 0),
                'fdesc' => (string) ($oldReturJualDescs[$index] ?? ''),
                'fketdt' => (string) ($oldReturJualKetdts[$index] ?? ''),
                'maxqty' => max(0, (float) ($oldReturJualQtys[$index] ?? 0)),
                'maxqty_unit' => 'kecil',
            ];
        }

        $nextReturPenjualanItemIndex = empty($oldReturJualIndexes)
            ? 0
            : max(array_map('intval', $oldReturJualIndexes)) + 1;
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

        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        /* Hilangkan panah di input number (Firefox) */
        input[type=number] {
            -moz-appearance: textfield;
        }

        .returpenjualan-detail-table th,
        .returpenjualan-detail-table td {
            padding: .25rem .375rem !important;
        }

        .returpenjualan-detail-table input:not([type="hidden"]),
        .returpenjualan-detail-table select,
        .returpenjualan-detail-table button {
            min-height: 1.875rem;
            padding-top: .25rem !important;
            padding-bottom: .25rem !important;
            line-height: 1.25rem;
        }

        .returpenjualan-detail-table .rounded-l.border,
        .returpenjualan-detail-table .rounded-r.border {
            min-height: 1.875rem;
            padding-top: .25rem !important;
            padding-bottom: .25rem !important;
            line-height: 1.25rem;
        }

        .returpenjualan-detail-table button {
            display: inline-flex;
            align-items: center;
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

        .btn-desc-filled {
            background-color: #16a34a !important;
            color: #ffffff !important;
            border-color: #15803d !important;
        }

        .btn-desc-filled:hover {
            background-color: #15803d !important;
            color: #ffffff !important;
        }

        .btn-desc-filled svg {
            color: #ffffff !important;
            stroke: #ffffff !important;
        }

        .btn-desc-empty {
            background-color: #ffffff !important;
            color: #6b7280 !important;
            border-color: #d1d5db !important;
        }

        .btn-desc-empty:hover {
            background-color: #f9fafb !important;
        }
    </style>
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow p-0 overflow-hidden mb-3" role="alert">
            <div class="d-flex align-items-center px-4 py-3" style="background-color: #c0392b;">
                <i class="bi bi-exclamation-triangle-fill text-white me-2 fs-5"></i>
                <strong class="text-white fs-6">{{ session('error') }}</strong>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif
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
    <div x-data="itemsTable()" x-init="init()">
        
        <form action="{{ route('returpenjualan.store') }}" method="POST" data-form-draft="true"
            data-draft-key="returpenjualan:create"
            @submit.prevent="
        const duplicateCode = window.getReturPenjualanDuplicateCode?.($el);
        if (duplicateCode) {
            Swal.fire({
                icon: 'warning',
                title: 'Produk Duplikat',
                text: `Kode produk ${duplicateCode} tidak boleh sama dalam satu Retur Penjualan.`,
                confirmButtonText: 'OK',
                customClass: {
                    confirmButton: 'bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700'
                }
            });
            return;
        }
        const n = Number(document.getElementById('itemsCount')?.value || 0);
        if (n < 1) {
            window.dispatchEvent(new CustomEvent('returpenjualan-show-no-items'));
            return;
        }
        onSubmit($event);
      ">
            @csrf

            {{-- ─── CARD 1: Identitas Retur Penjualan ─────────────── --}}
            <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                <div class="flex items-center gap-2 px-4 pt-3 pb-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Identitas Retur Penjualan</p>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-bold mb-1">Cabang</label>
                            <input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                                value="{{ trim(($fbranchcode ?? '') . ($fcabang && trim($fcabang) !== trim($fbranchcode ?? '') ? ' - ' . trim($fcabang) : '')) }}" disabled>
                            <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                        </div>

                        <div x-data="{ autoCode: true }">
                            <label class="block text-xs font-bold mb-1">
                                Transaksi# <span class="text-red-500" x-show="!autoCode">*</span>
                            </label>
                            <div class="flex items-center gap-3">
                                <input type="text" name="fsono" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                    :disabled="autoCode"
                                    :required="!autoCode"
                                    :class="autoCode ? 'bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200' : 'bg-white'"
                                    :placeholder="autoCode ? 'Auto Generated' : 'Wajib diisi'"
                                    oninput="this.value = this.value.toUpperCase()">
                                <label class="inline-flex items-center select-none">
                                    <input type="checkbox" name="auto_generate" value="1" x-model="autoCode" checked>
                                    <span class="ml-2 text-sm text-gray-700">Auto</span>
                                </label>
                            </div>
                        </div>

                        @php
                            $currentSodate = old('fsodate', session('last_header.fsodate', date('Y-m-d')));
                            $currentCustNo = old('fcustno', session('last_header.fcustno', $filterSupplierId));
                            $currentSalesman = old('fsalesman', session('last_header.fsalesman', $filterSalesmanId ?: '0'));
                            $currentWarehouse = old('ffrom', session('last_header.ffrom', ''));
                            $currentKet = old('fket', session('last_header.fket', ''));
                        @endphp

                        <div class="flex gap-2">
                            <div class="w-1/2">
                                <label class="block text-xs font-bold mb-1">Tanggal <span class="text-red-500">*</span></label>
                                <input type="date" id="fsodate" name="fsodate"
                                    value="{{ $currentSodate }}"
                                    class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fsodate') border-red-500 text-red-600 @else border-gray-300 @enderror">
                                @error('fsodate')
                                    <p class="text-red-500 text-xs mt-1 font-semibold flex items-center gap-1"><x-heroicon-o-exclamation-circle class="w-3.5 h-3.5 flex-shrink-0" /> {{ $message }}</p>
                                @enderror
                            </div>
                            <div class="w-1/2">
                                <label class="block text-xs font-bold mb-1">Type <span class="text-red-500">*</span></label>
                                <select name="ftypesales" x-model.number="ftypesales" @change="onTypeChanged()"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('ftypesales') border-red-500 text-red-600 @enderror">
                                    <option value="0" {{ old('ftypesales', session('last_header.ftypesales', 0)) == 0 ? 'selected' : '' }}>Penjualan</option>
                                    <option value="1" {{ old('ftypesales', session('last_header.ftypesales', 0)) == 1 ? 'selected' : '' }}>Uang Muka</option>
                                </select>
                                @error('ftypesales')
                                    <p class="text-red-500 text-xs mt-1 font-semibold flex items-center gap-1"><x-heroicon-o-exclamation-circle class="w-3.5 h-3.5 flex-shrink-0" /> {{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Customer --}}
                        <div>
                            <label class="block text-xs font-bold mb-1">Customer <span class="text-red-500">*</span></label>
                            <div class="flex">
                                <div class="relative flex-1" for="modal_filter_customer_id">
                                    <select id="modal_filter_customer_id" name="filter_customer_id"
                                        class="w-full border rounded-l-lg px-3 py-2 text-sm bg-gray-50 text-gray-700 cursor-pointer focus:outline-none focus:border-blue-500 pointer-events-none @error('fcustno') border-red-500 text-red-600 @else border-gray-300 @enderror"
                                        disabled>
                                        <option value=""></option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->fcustomercode }}"
                                                {{ $currentCustNo == $customer->fcustomercode ? 'selected' : '' }}>
                                                {{ $customer->fcustomercode }} - {{ $customer->fcustomername }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-0 cursor-pointer z-10" role="button" aria-label="{{ 'Browse Customer' }}"
                                        @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))"></div>
                                </div>
                                <input type="hidden" name="fcustno" id="customerCodeHidden" value="{{ $currentCustNo }}">
                                <button type="button"
                                    @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))"
                                    class="border border-l-0 px-3 py-2 bg-white hover:bg-gray-50 text-gray-500 transition-colors @error('fcustno') border-red-500 text-red-600 @else border-gray-300 @enderror"
                                    title="{{ 'Browse Customer' }}">
                                    <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                </button>
                                @if (in_array('createCustomer', explode(',', session('user_restricted_permissions', '')), true))
                                    <a href="{{ route('customer.create') }}" target="_blank" rel="noopener"
                                        class="border border-l-0 rounded-r-lg px-3 py-2 bg-white hover:bg-gray-50 text-gray-500 transition-colors @error('fcustno') border-red-500 text-red-600 @else border-gray-300 @enderror"
                                        title="Tambah Customer">
                                        <x-heroicon-o-plus class="w-5 h-5" />
                                    </a>
                                @endif
                            </div>
                            @error('fcustno')
                                <p class="text-red-500 text-xs mt-1 font-semibold flex items-center gap-1"><x-heroicon-o-exclamation-circle class="w-3.5 h-3.5 flex-shrink-0" /> {{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Salesman --}}
                        <div>
                            <label class="block text-xs font-bold mb-1">Salesman</label>
                            <div class="flex">
                                <div class="relative flex-1" for="modal_filter_salesman_id">
                                    <select id="modal_filter_salesman_id" name="filter_salesman_id"
                                        class="w-full border rounded-l-lg px-3 py-2 text-sm bg-gray-50 text-gray-700 cursor-pointer focus:outline-none focus:border-blue-500 pointer-events-none @error('fsalesman') border-red-500 text-red-600 @else border-gray-300 @enderror"
                                        disabled>
                                        <option value=""></option>
                                        @foreach ($salesmans as $salesman)
                                            <option value="{{ $salesman->fsalesmancode }}"
                                                {{ $currentSalesman == $salesman->fsalesmancode ? 'selected' : '' }}>
                                                {{ $salesman->fsalesmanname }} ({{ $salesman->fsalesmancode }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-0 cursor-pointer z-10" role="button" aria-label="{{ 'Browse Salesman' }}"
                                        @click="window.dispatchEvent(new CustomEvent('salesman-browse-open'))"></div>
                                </div>
                                <input type="hidden" name="fsalesman" id="salesmanCodeHidden"
                                    value="{{ $currentSalesman }}">
                                <button type="button"
                                    @click="window.dispatchEvent(new CustomEvent('salesman-browse-open'))"
                                    class="border border-l-0 px-3 py-2 bg-white hover:bg-gray-50 text-gray-500 transition-colors @error('fsalesman') border-red-500 text-red-600 @else border-gray-300 @enderror"
                                    title="{{ 'Browse Salesman' }}">
                                    <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                </button>
                                @if (in_array('createSalesman', explode(',', session('user_restricted_permissions', '')), true))
                                    <a href="{{ route('salesman.create') }}" target="_blank" rel="noopener"
                                        class="border border-l-0 rounded-r-lg px-3 py-2 bg-white hover:bg-gray-50 text-gray-500 transition-colors @error('fsalesman') border-red-500 text-red-600 @else border-gray-300 @enderror"
                                        title="Tambah Salesman">
                                        <x-heroicon-o-plus class="w-5 h-5" />
                                    </a>
                                @endif
                            </div>
                            @error('fsalesman')
                                <p class="text-red-500 text-xs mt-1 font-semibold flex items-center gap-1"><x-heroicon-o-exclamation-circle class="w-3.5 h-3.5 flex-shrink-0" /> {{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Gudang --}}
                        <div>
                            <label class="block text-xs font-bold mb-1">Gudang <span class="text-red-500">*</span></label>
                            <div class="flex">
                                <div class="relative flex-1" for="warehouseSelect">
                                    <select id="warehouseSelect" name="filter_warehouse_id"
                                        class="w-full border rounded-l-lg px-3 py-2 text-sm bg-gray-50 text-gray-700 cursor-pointer focus:outline-none focus:border-blue-500 pointer-events-none @error('ffrom') border-red-500 text-red-600 @else border-gray-300 @enderror"
                                        disabled>
                                        <option value=""></option>
                                        @foreach ($warehouses as $wh)
                                            <option value="{{ $wh->fwhcode }}"
                                                {{ $currentWarehouse == $wh->fwhcode ? 'selected' : '' }}>
                                                {{ $wh->fwhname }} ({{ $wh->fwhcode }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-0 cursor-pointer z-10" role="button" aria-label="Browse Gudang"
                                        @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open'))"></div>
                                </div>
                                <input type="hidden" name="ffrom" id="warehouseCodeHidden"
                                    value="{{ $currentWarehouse }}">
                                <button type="button"
                                    @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open'))"
                                    class="border border-l-0 rounded-r-lg px-3 py-2 bg-white hover:bg-gray-50 text-gray-500 transition-colors @error('ffrom') border-red-500 text-red-600 @else border-gray-300 @enderror"
                                    title="Browse Gudang">
                                    <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                </button>
                            </div>
                            @error('ffrom')
                                <p class="text-red-500 text-xs mt-1 font-semibold flex items-center gap-1"><x-heroicon-o-exclamation-circle class="w-3.5 h-3.5 flex-shrink-0" /> {{ $message }}</p>
                            @enderror
                        </div>

                        <input type="hidden" name="ftaxno" value="0">

                        <div class="lg:col-span-3">
                            <label class="block text-xs font-bold mb-1">Keterangan</label>
                            <textarea name="fket" rows="2"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fket') border-red-500 @enderror"
                                placeholder="Keterangan isi di sini...">{{ $currentKet }}</textarea>
                            @error('fket')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
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
                <div class="p-4">
                    <div class="space-y-2">

                        <div class="overflow-auto border rounded">
                            <table class="returpenjualan-detail-table min-w-full text-sm balanced-detail-table"
                                data-skip-auto-detail-style="true">
                                <colgroup>
                                    <col style="width:2%;">
                                    <col style="width:12%;">
                                    <col style="width:25%;">
                                    <col style="width:8%;">
                                    <col style="width:15%;">
                                    <col style="width:8%;">
                                    <col style="width:12%;">
                                    <col style="width:14%;">
                                    <col style="width:6%;">
                                </colgroup>
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="p-2 text-left w-10">#</th>
                                        <th class="p-2 text-left w-42">Kode Produk</th>
                                        <th class="p-2 text-left w-96">Nama Produk</th>
                                        <th class="p-2 text-left w-36">Satuan</th>
                                        <th class="p-2 text-left w-36">No.Ref</th>
                                        <th class="p-2 text-right w-36 whitespace-nowrap">Qty</th>
                                        <th class="p-2 text-right w-32 whitespace-nowrap">@ Harga</th>
                                        <th class="p-2 text-right w-36 whitespace-nowrap">Total Harga</th>
                                        <th class="p-2 text-center w-28">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(it, i) in savedItems" :key="it.uid || `item-${i}`">
                                        <tr class="border-t align-top hover:bg-gray-50" :class="it.qtyInvalid ? 'bg-red-50' : ''">
                                            <td class="p-2 text-gray-400" x-text="i + 1"></td>
                                            <td class="p-2">
                                                <div class="flex">
                                                    <input type="text"
                                                        class="flex-1 border rounded-l px-2 py-1 font-mono text-sm focus:ring-1 focus:ring-blue-500 min-w-0"
                                                        :id="'code_row_' + i" x-model.trim="it.fitemcode"
                                                        @input="onCodeTypedRow(it, i)"
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
                                                    <div class="min-w-0 flex-1 rounded-l border bg-gray-105 px-2 py-1 text-sm leading-5 text-gray-650 whitespace-normal break-words"
                                                        x-text="it.fitemname"></div>
                                                    <button type="button" @click="openDesc(it)"
                                                        class="shrink-0 inline-flex items-center border border-l-0 rounded-r px-2 py-1 transition-colors"
                                                        :class="it.fdesc ? 'btn-desc-filled font-medium' : 'btn-desc-empty'"
                                                        title="Deskripsi item">
                                                        <x-heroicon-o-document-text class="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="p-2">
                                                <template x-if="it.units && it.units.length > 1">
                                                    <select class="w-full border rounded px-2 py-1 text-sm focus:ring-1 focus:ring-blue-500"
                                                        :id="'unit_row_' + i" x-model="it.fsatuan"
                                                        @change="onRowUpdated(i); fetchProductPriceHistory(it, i)" @keydown.enter.prevent="focusRowQty(i)">
                                                        <template x-for="u in it.units" :key="u">
                                                            <option :value="u" x-text="u"></option>
                                                        </template>
                                                    </select>
                                                </template>
                                                <template x-if="!it.units || it.units.length <= 1">
                                                    <div class="px-2 py-1 text-sm text-gray-650 bg-gray-50 border rounded"
                                                        x-text="it.fsatuan || '-'"></div>
                                                </template>
                                            </td>
                                            <td class="p-2">
                                                 <div class="flex w-full max-w-full">
                                                     <input type="text"
                                                         class="min-w-0 flex-1 border rounded-l px-2 py-1 text-sm font-mono bg-gray-100 text-gray-600 cursor-not-allowed"
                                                         :value="getItemNoRef(it)"
                                                         placeholder="No Ref" disabled>
                                                     <button type="button" @click="openProductHistory(it)"
                                                         class="shrink-0 inline-flex items-center border border-l-0 rounded-r bg-slate-50 px-2 py-1 text-slate-700 hover:bg-slate-100 transition-colors border-slate-200"
                                                         title="Riwayat produk / Uang Muka">
                                                         <x-heroicon-o-clock class="w-4 h-4" />
                                                     </button>
                                                 </div>
                                            </td>
                                            <td class="p-2 text-right">
                                                <input type="number"
                                                    class="w-full border rounded px-2 py-1 text-right text-sm focus:ring-1 focus:ring-blue-500"
                                                    :class="it.qtyInvalid ? 'border-red-500 bg-red-50' : ''"
                                                    min="0" step="0.01" :id="'qty_row_' + i"
                                                    x-model.number="it.fqty" @input="enforceQtyRow(it); onRowUpdated(i)"
                                                    @change="enforceQtyRow(it); onRowUpdated(i)"
                                                    @keydown.enter.prevent="focusRowPrice(i)">
                                                <div class="text-xs text-gray-400 mt-0.5 flex justify-end items-center"
                                                    x-show="it.fitemcode">
                                                    <div x-html="formatStockLimit(it)"></div>
                                                </div>
                                            </td>
                                            <td class="p-2 text-right">
                                                 <input type="text" inputmode="decimal"
                                                     class="w-full border rounded px-2 py-1 text-right text-sm disabled:bg-gray-100 disabled:text-gray-500 disabled:cursor-not-allowed"
                                                      :id="'price_row_' + i" x-model="it.fpriceInput"
                                                      :disabled="isPriceDisabled(it)"
                                                      @focus="focusPriceInput(it); $event.target.select()"
                                                      @input="onPriceInput(it); onRowUpdated(i)"
                                                     @blur="blurPriceInput(it); onRowUpdated(i)"
                                                     @keydown.enter.prevent="onRowUpdated(i)">
                                            </td>
                                            <td class="p-2 text-right">
                                                <div class="px-2 py-1 text-sm text-gray-700 bg-gray-50 border rounded text-right font-medium" x-text="fmt(it.ftotal)"></div>
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
                                    <input type="hidden" :name="`frefcode[${it.formIndex}]`" :value="it.frefcode">
                                    <input type="hidden" :name="`frefdtno[${it.formIndex}]`" :value="it.frefdtno">
                                    <input type="hidden" :name="`fnouref[${it.formIndex}]`" :value="it.fnouref">
                                    <input type="hidden" :name="`frefpr[${it.formIndex}]`" :value="it.frefpr">
                                    <input type="hidden" :name="`frefso[${it.formIndex}]`" :value="it.frefso">
                                    <input type="hidden" :name="`frefsrj[${it.formIndex}]`" :value="it.frefsrj">
                                    <input type="hidden" :name="`fnoacak[${it.formIndex}]`" :value="it.fnoacak">
                                    <input type="hidden" :name="`frefnoacak[${it.formIndex}]`" :value="it.frefnoacak">
                                    <input type="hidden" :name="`fqty[${it.formIndex}]`" :value="it.fqty">
                                    <input type="hidden" :name="`fterima[${it.formIndex}]`" :value="it.fterima">
                                    <input type="hidden" :name="`fprice[${it.formIndex}]`" :value="it.fprice">
                                    <input type="hidden" :name="`fref_price[${it.formIndex}]`" :value="it.ref_price || it.maxprice || it.source_price || 0">
                                    <input type="hidden" :name="`fmaxqty[${it.formIndex}]`" :value="it.maxqty">
                                    <input type="hidden" :name="`fdisc[${it.formIndex}]`" :value="it.fdisc">
                                    <input type="hidden" :name="`ftotal[${it.formIndex}]`" :value="it.ftotal">
                                    <input type="hidden" :name="`fdesc[${it.formIndex}]`" :value="it.fdesc">
                                    <input type="hidden" :name="`fketdt[${it.formIndex}]`" :value="it.fketdt">
                                </div>
                            </template>
                        </div>

                        <input type="hidden" id="itemsCount" :value="submitItems.length">
                        <input type="hidden" name="frefcode_global" id="frefcode"
                            value="{{ old('frefcode_global') }}">
                        <input type="hidden" name="frefso_header" id="frefso" value="{{ old('frefso_header') }}">
                        <input type="hidden" name="frefsrj_header" id="frefsrj" value="{{ old('frefsrj_header') }}">

                        <script>
                            document.addEventListener('DOMContentLoaded', () => {
                                const inputRefCode = document.getElementById('frefcode');
                                const inputRefSo = document.getElementById('frefso');
                                const inputRefSrj = document.getElementById('frefsrj');
                                const inputRefDisplay = document.getElementById('frefdisplay_header');
                                const inputRefDisplayText = document.getElementById('headerReferenceDisplay');

                                function setHeaderReferenceDisplay(value) {
                                    const resolved = (value ?? '').toString().trim();
                                    if (inputRefDisplay) inputRefDisplay.value = resolved;
                                    if (inputRefDisplayText) inputRefDisplayText.value = resolved;
                                }

                                /**
                                 * Auto-fill customer dari header referensi (Faktur/SRJ).
                                 * @param {string} customerCode - Kode customer
                                 * @param {string} customerName - Nama customer (opsional)
                                 */
                                function autoFillCustomer(customerCode, customerName) {
                                    const code = (customerCode ?? '').toString().trim();
                                    if (code === '') return;

                                    const sel = document.getElementById('modal_filter_customer_id');
                                    const hid = document.getElementById('customerCodeHidden');

                                    if (!sel) return;

                                    // Cek apakah customer sudah terpilih sama
                                    if (hid && hid.value === code) return;

                                    const name = (customerName ?? '').toString().trim();
                                    const label = name !== '' ? `${code} - ${name}` : code;

                                    if (typeof window.applyTransactionCustomerSelection === 'function') {
                                        window.applyTransactionCustomerSelection({
                                            fcustomercode: code,
                                            fcustomername: name,
                                        });
                                        return;
                                    }

                                    // Set option di select dropdown
                                    let opt = [...sel.options].find(o => o.value === code);
                                    if (!opt) {
                                        opt = new Option(label, code, true, true);
                                        sel.add(opt);
                                    } else {
                                        opt.text = label;
                                        opt.selected = true;
                                    }

                                    // Set hidden input
                                    if (hid) {
                                        hid.value = code;
                                        hid.dispatchEvent(new Event('change', {
                                            bubbles: true
                                        }));
                                    }
                                    sel.dispatchEvent(new Event('change', {
                                        bubbles: true
                                    }));
                                }

                                // Menangkap Event saat referensi faktur dipilih
                                window.addEventListener('invoice-picked', (e) => {
                                    const header = e.detail.header;
                                    inputRefCode.value = 'INV';
                                    inputRefSo.value = header.fsono ?? '';
                                    inputRefSrj.value = ''; // Reset yang lain
                                    setHeaderReferenceDisplay(header.fdisplayref ?? header.fsono ?? header.frefno ?? '');

                                    // Auto-fill customer dari faktur
                                    autoFillCustomer(
                                        header.fcustno ?? header.fcustomercode ?? '',
                                        header.fcustomername ?? ''
                                    );
                                });

                                // Menangkap Event saat SRJ dipilih
                                window.addEventListener('srj-picked', (e) => {
                                    const header = e.detail.header;
                                    inputRefCode.value = 'SRJ';
                                    inputRefSrj.value = header.fstockmtid; // Sesuaikan ID header SRJ
                                    inputRefSo.value = ''; // Reset yang lain
                                    setHeaderReferenceDisplay(header.fdisplayref ?? header.fstockmtno ?? header.fsono ?? '');

                                    // Auto-fill customer dari SRJ (fsupplier = customer code)
                                    autoFillCustomer(
                                        header.fsupplier ?? header.fcustomercode ?? '',
                                        header.fsuppliername ?? header.fcustomername ?? ''
                                    );
                                });

                                // Auto-sync salesman ketika customer dipilih/diisi
                                window.addEventListener('customer-selected', async (e) => {
                                    const customerCode = (e.detail?.fcustomercode ?? '').toString().trim();
                                    if (!customerCode) return;

                                    const fsalesman = (e.detail?.fsalesman ?? '').toString().trim();
                                    const fsalesmanname = (e.detail?.fsalesmanname ?? '').toString().trim();

                                    if (fsalesman) {
                                        if (typeof window.applyTransactionSalesmanSelection === 'function') {
                                            window.applyTransactionSalesmanSelection({
                                                fsalesmancode: fsalesman,
                                                fsalesmanname: fsalesmanname
                                            });
                                        }
                                    } else {
                                        try {
                                            const url =
                                                `{{ route('customer.browse') }}?search=${encodeURIComponent(customerCode)}`;
                                            const res = await fetch(url, {
                                                headers: {
                                                    'X-Requested-With': 'XMLHttpRequest'
                                                }
                                            });
                                            if (res.ok) {
                                                const json = await res.json();
                                                const customer = (json.data || []).find(c => String(c.fcustomercode)
                                                .trim() === customerCode);
                                                if (customer && customer.fsalesman) {
                                                    if (typeof window.applyTransactionSalesmanSelection === 'function') {
                                                        window.applyTransactionSalesmanSelection({
                                                            fsalesmancode: customer.fsalesman,
                                                            fsalesmanname: customer.fsalesmanname
                                                        });
                                                    }
                                                }
                                            }
                                        } catch (err) {
                                            console.error('Error fetching customer salesman:', err);
                                        }
                                    }
                                });
                            });
                        </script>

                        <div class="mt-6 flex flex-col md:flex-row justify-between items-start gap-4 w-full">
                            <div class="flex flex-wrap items-center gap-3 flex-shrink-0">
                                {{-- Container Alpine.js --}}
                                <div x-data="srjFormModal()" class="mt-3">
                                    {{-- Button Trigger --}}
                                    <button type="button" @click="openSrjModal()"
                                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 ml-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                        Add SRJ
                                    </button>

                                    {{-- MODAL SRJ --}}
                                    <div x-show="showSrjModal" x-cloak x-transition.opacity
                                        class="fixed inset-0 z-50 flex items-center justify-center p-4">
                                        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                                            @click="closeSrjModal()">
                                        </div>

                                        <div class="relative bg-white rounded-2xl shadow-2xl w-[94vw] max-w-[100rem] flex flex-col overflow-hidden"
                                            style="height: 82vh;">
                                            <div
                                                class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-indigo-50 to-white">
                                                <div>
                                                    <h3 class="text-xl font-bold text-gray-800">Pilih Surat Jalan
                                                    </h3>
                                                </div>
                                                <button type="button" @click="closeSrjModal()"
                                                    class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-bold text-gray-700 text-sm">
                                                    {{ 'Tutup' }}
                                                </button>
                                            </div>

                                            <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                                                <div id="srjTableControls"></div>
                                            </div>

                                            <div class="flex-1 overflow-x-auto overflow-y-hidden px-6"
                                                style="min-height: 0;">
                                                <div class="bg-white">
                                                    <table id="srjTable"
                                                        class="min-w-full text-sm display nowrap stripe hover"
                                                        style="width:100%">
                                                        <thead class="sticky top-0 z-10">
                                                            <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                                                <th
                                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                    {{ 'Cabang' }}</th>
                                                                <th
                                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                    {{ 'No. SRJ' }}</th>
                                                                <th
                                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                    {{ 'Tanggal' }}</th>
                                                                <th
                                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                    {{ 'Customer' }}</th>
                                                                <th
                                                                    class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                    {{ 'Aksi' }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody></tbody>
                                                    </table>
                                                </div>
                                            </div>

                                            <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
                                                <div id="srjTablePagination"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div x-show="showDupModal" x-cloak x-transition.opacity
                                        class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                                        <div class="absolute inset-0 bg-black/50" @click="closeDupModal()"></div>
                                        <div
                                            class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden">
                                            <div class="px-5 py-4 border-b flex items-center gap-2 bg-amber-50">
                                                <h3 class="text-lg font-semibold text-gray-800">Item Duplikat SRJ</h3>
                                            </div>
                                            <div class="px-5 py-4">
                                                <p class="text-sm text-gray-700 mb-3">
                                                    Ditemukan <span x-text="dupCount" class="font-bold"></span> item sudah
                                                    ada
                                                    di
                                                    daftar.
                                                </p>
                                                <div
                                                    class="rounded-lg border border-amber-200 bg-amber-50 max-h-40 overflow-auto">
                                                    <template x-for="d in dupSample">
                                                        <div class="p-2 text-xs border-b border-amber-100">
                                                            <span x-text="d.fitemcode" class="font-bold"></span> - <span
                                                                x-text="d.fitemname"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                            <div class="px-5 py-3 border-t bg-gray-50 flex justify-end gap-2">
                                                <button type="button" @click="closeDupModal()"
                                                    class="px-4 py-2 border rounded-lg">{{ 'Batal' }}</button>
                                                <button type="button" @click="confirmAddUniques()"
                                                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg">Tambahkan Sisa
                                                    Item</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- SO --}}
                                <div x-data="soFormModal()" class="mt-3">
                                    <div class="mt-3 flex justify-between items-start gap-4">
                                        <div class="w-full flex justify-start mb-3">
                                            <button type="button" @click="openModal()"
                                                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="1.5" d="M12 4.5v15m7.5-7.5h-15" />
                                                </svg>
                                                Add Faktur
                                            </button>
                                        </div>
                                    </div>

                                    {{-- MODAL FAKTUR PENJUALAN --}}
                                    <div x-show="show" x-cloak x-transition.opacity
                                        class="fixed inset-0 z-50 flex items-center justify-center p-4">
                                        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal()">
                                        </div>

                                        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-5xl flex flex-col overflow-hidden"
                                            style="height: 650px;">
                                            <!-- Header -->
                                            <div
                                                class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-teal-50 to-white">
                                                <div>
                                                    <h3 class="text-xl font-bold text-gray-800">Add Faktur Penjualan</h3>
                                                    <p class="text-sm text-gray-500 mt-0.5">Pilih Faktur Penjualan yang
                                                        sudah di-approve
                                                    </p>
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
                                                                    No. Faktur</th>
                                                                <th
                                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                    No. Referensi</th>
                                                                <th
                                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                    Tanggal</th>
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
                                            <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
                                                <div id="poTablePagination"></div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- MODAL DUPLIKASI --}}
                                    <div x-show="showDupModal" x-cloak x-transition.opacity
                                        class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                                        <div class="absolute inset-0 bg-black/50" @click="closeDupModal()"></div>

                                        <div
                                            class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden">
                                            <!-- Header -->
                                            <div class="px-5 py-4 border-b flex items-center gap-2 bg-amber-50">
                                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
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
                                                            <li class="px-3 py-2 text-sm text-gray-500 text-center">Tidak
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
                                            <div class="px-5 py-3 border-t bg-gray-50 flex items-center justify-end gap-2">
                                                <button type="button" @click="closeDupModal()"
                                                    class="h-9 px-4 rounded-lg border-2 border-gray-300 text-gray-700 text-sm font-bold hover:bg-gray-100 transition-colors">
                                                    {{ 'Batal' }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ===== Panel Totals ===== -->
                            <div class="w-[560px] shrink-0 max-w-full">
                                <div class="rounded-lg border bg-gray-50 p-4 space-y-3 text-sm">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-gray-800">Total Harga (Net)</span>
                                        <span class="font-bold text-gray-900" x-text="formatTransactionAmount(netTotal)"></span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <!-- Checkbox -->
                                        <label class="flex items-center gap-1.5 cursor-pointer select-none">
                                            <input id="fincludeppn_input" name="fincludeppn" type="checkbox" value="1"
                                                x-model="includePPN"
                                                class="rounded text-blue-600 border-gray-300 focus:ring-blue-500 h-4 w-4">
                                            <span class="font-bold">PPN</span>
                                        </label>

                                        <!-- Hidden fapplyppn (always Exclude = 0) -->
                                        <input type="hidden" name="fapplyppn" value="0">

                                        <!-- Input Rate + Nominal -->
                                        <input type="number" min="0" max="100" name="ppn_rate"
                                            step="0.01" x-model.number="ppnRate" :disabled="!includePPN"
                                            class="w-16 h-9 px-2 text-sm leading-tight text-right border border-gray-300 rounded transition-opacity
                                                    [appearance:textfield]
                                                    [&::-webkit-outer-spin-button]:appearance-none
                                                    [&::-webkit-inner-spin-button]:appearance-none
                                                    disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed focus:outline-none focus:border-blue-500">
                                        <span class="text-gray-500">%</span>
                                        <span class="flex-1"></span>
                                        <span class="font-medium text-gray-900" x-text="rupiah(ppnAmount)"></span>
                                    </div>

                                    <div class="border-t my-1"></div>

                                    <div class="flex items-center justify-between text-base">
                                        <span class="font-extrabold text-gray-900">Grand Total</span>
                                        <span class="font-extrabold text-blue-700 text-lg" x-text="rupiah(grandTotal)"></span>
                                    </div>
                                </div>

                                <!-- Hidden inputs for submit -->
                                <input type="hidden" name="famountgross" :value="totalHarga">
                                <input type="hidden" name="famountpajak" :value="ppnAmount">
                                <input type="hidden" name="famountsonet" :value="netTotal">
                                <input type="hidden" name="famountso" :value="grandTotal">
                                <input type="hidden" name="famountpopajak" :value="ppnAmount">
                                <input type="hidden" name="fppnpersen" :value="ppnRate">
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

                        <div x-show="showHistoryModal" x-cloak
                            class="fixed inset-0 z-[96] flex items-center justify-center" x-transition.opacity>
                            <div class="absolute inset-0 bg-black/50" @click="closeHistory()"></div>
                            <div class="relative bg-white w-[92vw] max-w-4xl rounded-2xl shadow-2xl overflow-hidden">
                                <div class="px-5 py-4 border-b flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-gray-800">Browse Uang Muka</h3>
                                    <button type="button" @click="closeHistory()"
                                        class="text-gray-500 hover:text-gray-700">Tutup</button>
                                </div>
                                <div class="p-5 overflow-auto max-h-[65vh]">
                                    <template x-if="historyLoading">
                                        <div class="text-sm text-gray-500">Memuat data...</div>
                                    </template>
                                    <template x-if="!historyLoading">
                                        <table class="min-w-full text-sm">
                                            <thead class="bg-gray-100">
                                                <tr>
                                                    <th class="p-2 text-left">No. Transaksi</th>
                                                    <th class="p-2 text-left">Tanggal</th>
                                                    <th class="p-2 text-right">Qty</th>
                                                    <th class="p-2 text-right">Harga</th>
                                                    <th class="p-2 text-right">Total</th>
                                                    <th class="p-2 text-center">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-for="row in historyRows" :key="row.fsono + row.fsodate">
                                                    <tr class="border-t">
                                                        <td class="p-2" x-text="row.fsono"></td>
                                                        <td class="p-2" x-text="row.fsodate"></td>
                                                        <td class="p-2 text-right" x-text="row.fqty + ' ' + row.fsatuan">
                                                        </td>
                                                        <td class="p-2 text-right" x-text="fmt(row.fprice)"></td>
                                                        <td class="p-2 text-right" x-text="fmt(row.famount)"></td>
                                                        <td class="p-2 text-center">
                                                            <button type="button" @click="selectHistory(row)"
                                                                class="px-3 py-1 rounded bg-blue-600 text-white text-xs font-medium hover:bg-blue-700">
                                                                Pilih
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </template>
                                                <tr x-show="!historyRows.length">
                                                    <td colspan="6" class="p-4 text-center text-gray-500">Tidak ada
                                                        riwayat.</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div> {{-- End itemsTable --}}
                </div> {{-- End CARD 2 body --}}
            </div> {{-- End CARD 2 --}}

                    {{-- MODAL ERROR: belum ada item --}}
                    <div x-data="{ showNoItems: false }" x-init="window.addEventListener('returpenjualan-show-no-items', () => { showNoItems = true })"
                        x-show="showNoItems && Number(document.getElementById('itemsCount')?.value || 0) === 0" x-cloak
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
                                    Anda belum menambahkan item apa pun pada tabel. Silakan isi baris “Detail Item”
                                    terlebih
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

                    {{-- MODAL CUSTOMER --}}
                    <div x-data="customerBrowser()" x-show="open" x-cloak x-transition.opacity
                        class="fixed inset-0 z-50 flex items-center justify-center p-4">
                        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

                        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-6xl flex flex-col overflow-hidden"
                            style="height: 650px;">
                            <!-- Header -->
                            <div
                                class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800">{{ 'Browse Customer' }}</h3>
                                    <p class="text-sm text-gray-500 mt-0.5">{{ 'Pilih customer yang diinginkan' }}</p>
                                </div>
                                <button type="button" @click="close()"
                                    class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                                    {{ 'Tutup' }}
                                </button>
                            </div>

                            <!-- Search & Length Menu -->
                            <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                                <div id="supplierTableControls"></div>
                            </div>

                            <!-- Table with fixed height and scroll -->
                            <div class="flex-1 overflow-y-auto px-6" style="min-height: 0;">
                                <div class="bg-white">
                                    <table id="customerBrowseTable" class="min-w-full text-sm display nowrap stripe hover"
                                        style="width:100%">
                                        <thead class="sticky top-0 z-10">
                                            <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                                <th
                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                    Kode</th>
                                                <th
                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                    Nama Customer</th>
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
                                        <tbody>
                                            <!-- Data will be populated by DataTables -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Pagination & Info -->
                            <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
                                <div id="supplierTablePagination"></div>
                            </div>
                        </div>
                    </div>

                    {{-- MODAL Salesman --}}
                    <div x-data="salesmanBrowser()" x-show="open" x-cloak x-transition.opacity
                        class="fixed inset-0 z-50 flex items-center justify-center p-4">
                        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

                        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-6xl flex flex-col overflow-hidden"
                            style="height: 650px;">
                            <!-- Header -->
                            <div
                                class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800">{{ 'Browse Salesman' }}</h3>
                                    <p class="text-sm text-gray-500 mt-0.5">{{ 'Pilih salesman yang diinginkan' }}</p>
                                </div>
                                <button type="button" @click="close()"
                                    class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                                    {{ 'Tutup' }}
                                </button>
                            </div>

                            <!-- Search & Length Menu -->
                            <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                                <div id="salesmanTableControls"></div>
                            </div>

                            <!-- Table with fixed height and scroll -->
                            <div class="flex-1 overflow-y-auto px-6" style="min-height: 0;">
                                <div class="bg-white">
                                    <table id="salesmanBrowseTable" class="min-w-full text-sm display nowrap stripe hover"
                                        style="width:100%">
                                        <thead class="sticky top-0 z-10">
                                            <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                                <th
                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                    Kode</th>
                                                <th
                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                    Nama Salesman</th>
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
                            <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
                                <div id="salesmanTablePagination"></div>
                            </div>
                        </div>
                    </div>

                    <x-transaction.browse-warehouse-modal />

                    {{-- MODAL PRODUK --}}
                    <div x-data="productBrowser()" x-show="open" x-cloak x-transition.opacity
                        class="fixed inset-0 z-50 flex items-center justify-center p-4">
                        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

                        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-6xl flex flex-col overflow-hidden"
                            style="height: 650px;">
                            <!-- Header -->
                            <div
                                class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800">{{ 'Browse Produk' }}</h3>
                                    <p class="text-sm text-gray-500 mt-0.5">{{ 'Pilih produk yang diinginkan' }}</p>
                                </div>
                                <button type="button" @click="close()"
                                    class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                                    {{ 'Tutup' }}
                                </button>
                            </div>

                            <!-- Search & Length Menu -->
                            <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                                <div id="productTableControls"></div>
                            </div>

                            <!-- Table with fixed height and scroll -->
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
                                        <tbody>
                                            <!-- Data will be populated by DataTables -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Pagination & Info -->
                            <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
                                <div id="productTablePagination"></div>
                            </div>
                        </div>
                    </div>
                    {{-- Footer Buttons --}}
                    <div class="flex items-center justify-end gap-3 px-5 py-2 bg-gray-50 border-t border-gray-200">
                        <button type="button" onclick="window.location.href='{{ route('returpenjualan.index') }}'"
                            class="inline-flex items-center gap-2 px-5 py-2 border border-gray-300 bg-white text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-colors">
                            <x-heroicon-o-arrow-left class="w-6 h-6" />
                            Keluar
                        </button>
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                            <x-heroicon-o-check class="w-6 h-6" />
                            Simpan
                        </button>
                    </div>
        </form>
    </div>

@endsection
@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
@endpush
<style>
    /* Targeting lebih spesifik untuk length select */
    div#productTable_length select,
    .dataTables_wrapper #productTable_length select,
    table#customerBrowseTable+.dataTables_wrapper .dataTables_length select {
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
    div#supplierTable_length select,
    .dataTables_wrapper #supplierTable_length select,
    table#customerBrowseTable+.dataTables_wrapper .dataTables_length select {
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
    table#customerBrowseTable+.dataTables_wrapper .dataTables_length select {
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
<style>
    @keyframes slide-in {
        from {
            transform: translateX(100%);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slide-out {
        from {
            transform: translateX(0);
            opacity: 1;
        }

        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }

    .animate-slide-in {
        animation: slide-in 0.3s ease-out;
    }

    .animate-slide-out {
        animation: slide-out 0.3s ease-in;
    }
</style>
{{-- DATA & SCRIPTS --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Fallback toast system if not defined
    if (!window.toast) {
        window.toast = {
            success: (msg) => {
                if (typeof window.showAppSuccessToast === 'function') window.showAppSuccessToast(msg);
                else console.log('Success:', msg);
            },
            error: (msg) => {
                if (typeof window.showAppErrorAlert === 'function') window.showAppErrorAlert(
                    'Terjadi Kesalahan', msg);
                else console.error('Error:', msg);
            },
            info: (msg) => {
                if (typeof window.showAppInfoAlert === 'function') window.showAppInfoAlert('Information', msg);
                else console.info('Info:', msg);
            },
            warning: (msg) => {
                if (typeof window.showAppInfoAlert === 'function') window.showAppInfoAlert('Warning', msg);
                else console.warn('Warning:', msg);
            }
        };
    }

    // Map produk untuk auto-fill tabel
    // Standardized Product Map initialization
    window.PRODUCT_MAP = @json($productMap ?? []);
    window.RP_CUSTOMER_ADVANCE_WARNINGS = @json($customerAdvanceWarnings ?? []);

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

    // Modal customer
    function customerBrowser() {
        return {
            open: false,
            dataTable: null,

            initDataTable() {
                if (this.dataTable) {
                    this.dataTable.destroy();
                }

                this.dataTable = $('#customerBrowseTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('customer.browse') }}",
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
                    // --- MODIFIKASI: Mewarnai Baris ---
                    createdRow: function(row, data, dataIndex) {
                        if (data.fblokir == 1) {
                            $(row).addClass('text-red-600 italic'); // Menggunakan class Tailwind
                            // Atau jika ingin manual: $(row).css('color', 'red');
                        }
                    },
                    // ----------------------------------
                    columns: [{
                            data: 'fcustomercode',
                            name: 'fcustomercode',
                            className: 'font-mono text-sm',
                            width: '15%'
                        },
                        {
                            data: 'fcustomername',
                            name: 'fcustomername',
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
                                // --- MODIFIKASI: Disable tombol jika diblokir ---
                                if (row.fblokir == 1) {
                                    return '<span class="text-xs font-bold text-red-500">BLOKIR</span>';
                                }
                                return '<button type="button" class="btn-choose px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150">{{ 'Pilih' }}</button>';
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
                        processing: @json('Memuat data...'),
                        search: @json('Search' . ':'),
                        lengthMenu: @json('Tampilkan _MENU_'),
                        info: @json('Menampilkan _START_ - _END_ dari _TOTAL_ data'),
                        infoEmpty: @json('Tidak ada data'),
                        infoFiltered: "(disaring dari _MAX_ total data)",
                        zeroRecords: @json('Tidak ada data yang ditemukan'),
                        emptyTable: @json('Tidak ada data tersedia'),
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
                $('#customerBrowseTable').off('click.custpick');
                $('#customerBrowseTable tbody').off('click.custpick');

                $('#customerBrowseTable').off('click.custpick').on('click.custpick', '.btn-choose', (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    const data = this.dataTable.row($(e.target).closest('tr')).data();
                    if (!data) return;
                    // Pastikan fblokir tidak bernilai 1 sebelum diproses
                    if (data.fblokir != 1) {
                        this.chooseCustomer(data);
                    }
                });

                $('#customerBrowseTable tbody').on('click.custpick', 'tr', (e) => {
                    if ($(e.target).closest('button, a, input, select, textarea').length) {
                        return;
                    }

                    const data = this.dataTable?.row(e.currentTarget).data();
                    if (!data || data.fblokir == 1) {
                        return;
                    }

                    this.chooseCustomer(data);
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

            chooseCustomer(customer) {
                if (typeof window.applyTransactionCustomerSelection === 'function') {
                    window.applyTransactionCustomerSelection(customer);
                    this.close();
                    return;
                }

                const sel = document.getElementById('modal_filter_customer_id');
                const hid = document.getElementById('customerCodeHidden');

                if (!sel) {
                    this.close();
                    return;
                }

                let opt = [...sel.options].find(o => o.value == String(customer.fcustomercode));
                const label = `${customer.fcustomercode} - ${customer.fcustomername}`;
                if (!opt) {
                    opt = new Option(label, customer.fcustomercode, true, true);
                    sel.add(opt);
                } else {
                    opt.text = label;
                    opt.selected = true;
                }
                if (hid) {
                    hid.value = customer.fcustomercode;
                    hid.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                }

                window.dispatchEvent(new CustomEvent('customer-selected', {
                    detail: {
                        f1: customer.fkirimaddress1 || '',
                        f2: customer.fkirimaddress2 || '',
                        f3: customer.fkirimaddress3 || ''
                    }
                }));

                sel.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
                this.close();
            },

            init() {
                window.addEventListener('customer-browse-open', () => this.openBrowse(), {
                    passive: true
                });
            }
        }
    }

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

    // Modal salesman
    function salesmanBrowser() {
        return {
            open: false,
            dataTable: null,

            initDataTable() {
                if (this.dataTable) {
                    this.dataTable.destroy();
                }

                this.dataTable = $('#salesmanBrowseTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('salesman.browse') }}",
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
                            data: 'fsalesmancode',
                            name: 'fsalesmancode',
                            className: 'font-mono text-sm',
                            width: '15%'
                        },
                        {
                            data: 'fsalesmanname',
                            name: 'fsalesmanname',
                            className: 'text-sm',
                            width: '25%'
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center',
                            width: '15%',
                            render: function(data, type, row) {
                                return '<button type="button" class="btn-choose px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150">{{ 'Pilih' }}</button>';
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
                        processing: @json('Memuat data...'),
                        search: @json('Search' . ':'),
                        lengthMenu: @json('Tampilkan _MENU_'),
                        info: @json('Menampilkan _START_ - _END_ dari _TOTAL_ data'),
                        infoEmpty: @json('Tidak ada data'),
                        infoFiltered: "(disaring dari _MAX_ total data)",
                        zeroRecords: @json('Tidak ada data yang ditemukan'),
                        emptyTable: @json('Tidak ada data tersedia'),
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
                $('#salesmanBrowseTable').off('click.salespick');
                $('#salesmanBrowseTable tbody').off('click.salespick');

                $('#salesmanBrowseTable').off('click.salespick').on('click.salespick', '.btn-choose', (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    const data = this.dataTable.row($(e.target).closest('tr')).data();
                    if (data) this.chooseSalesman(data);
                });

                $('#salesmanBrowseTable tbody').on('click.salespick', 'tr', (e) => {
                    if ($(e.target).closest('button, a, input, select, textarea').length) {
                        return;
                    }

                    const data = this.dataTable?.row(e.currentTarget).data();
                    if (!data) {
                        return;
                    }

                    this.chooseSalesman(data);
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

            chooseSalesman(salesman) {
                if (typeof window.applyTransactionSalesmanSelection === 'function') {
                    window.applyTransactionSalesmanSelection(salesman);
                    this.close();
                    return;
                }

                const sel = document.getElementById('modal_filter_salesman_id');
                const hid = document.getElementById('salesmanCodeHidden');

                if (!sel) {
                    this.close();
                    return;
                }

                let opt = [...sel.options].find(o => o.value == String(salesman.fsalesmancode));
                const label = `${salesman.fsalesmanname} (${salesman.fsalesmancode})`;

                if (!opt) {
                    opt = new Option(label, salesman.fsalesmancode, true, true);
                    sel.add(opt);
                } else {
                    opt.text = label;
                    opt.selected = true;
                }

                sel.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
                if (hid) {
                    hid.value = salesman.fsalesmancode;
                    hid.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                }
                this.close();
            },

            init() {
                window.addEventListener('salesman-browse-open', () => this.openBrowse(), {
                    passive: true
                });
            }
        }
    }

    document.addEventListener('alpine:init', () => {
        Alpine.store('trsomt', {
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
            getItemNoRef(it) {
                if (!it) return '';
                let val = '';
                const isUM = String(it.fitemcode || '').toUpperCase().trim() === 'UM';
                if (isUM) {
                    val = it.frefsrj || it.frefdtno || it.frefpr || it.fnouref || '';
                } else {
                    val = it.frefsrj || it.frefso || it.frefpr || it.fnouref || '';
                    if (!val && ['INV', 'SRJ', 'SO', 'UM'].includes(it.frefcode)) {
                        val = it.frefcode;
                    }
                }
                val = String(val || '').trim();
                if (/^(RUJ|REJ)[\/\.]/i.test(val) || val === 'RUJ' || val === 'REJ' || val === '-') {
                    return '';
                }
                return val;
            },
            showNoItems: false,
            savedItems: @json($initialReturPenjualanItems),
            nextFormIndex: @json($nextReturPenjualanItemIndex),
            minimumVisibleRows: 5,
            browseTarget: null,
            editingIndex: null,
            editRow: newRow(),

            totalHarga: 0,
            ppnRate: @json((float) ($defaultPpnTarif ?? 11)),
            ftypesales: @json((int) old('ftypesales', session('last_header.ftypesales', 0))),

            onTypeChanged() {
                this.syncTransactionType();
                const isUM = this.ftypesales !== 0;
                if (isUM) {
                    let hasInvalid = false;
                    (this.savedItems || []).forEach((row) => {
                        const code = (row.fitemcode || '').toString().trim().toUpperCase();
                        if (code !== '' && !code.startsWith('UM')) {
                            hasInvalid = true;
                            this.clearRow(row);
                        }
                    });
                    if (hasInvalid) {
                        window.showAppWarningAlert('WARNING', 'Tipe Uang Muka hanya boleh menginput Uang Muka (UM).');
                    }
                } else {
                    let hasInvalid = false;
                    (this.savedItems || []).forEach((row) => {
                        const code = (row.fitemcode || '').toString().trim().toUpperCase();
                        if (code !== '' && code.startsWith('UM')) {
                            hasInvalid = true;
                            this.clearRow(row);
                        }
                    });
                    if (hasInvalid) {
                        window.showAppWarningAlert('WARNING', 'Tipe Penjualan tidak boleh menginput Uang Muka (UM).');
                    }
                }
                this.recalcTotals();
            },

            syncTransactionType() {
                const selected = document.querySelector('select[name="ftypesales"]:not([disabled])')?.value ?? this.ftypesales;
                if (selected !== undefined && selected !== null && selected !== '') {
                    this.ftypesales = Number(selected);
                }
            },

            initialGrandTotal: @json($famountso ?? 0),
            initialPpnAmount: @json($famountpopajak ?? 0),

            includePPN: @json(old('fincludeppn', $tr_poh->fincludeppn ?? 0) == '1'),
            fapplyppn: 0,

            get ppnIncluded() {
                return 0;
            },

            get netFromGross() {
                return +this.totalHarga || 0;
            },

            get ppnAdded() {
                const rate = +this.ppnRate || 0;
                if (!this.includePPN) return 0;
                const total = +this.totalHarga || 0;
                return Math.round(total * (rate / 100));
            },

            get ppnAmount() {
                if (!this.includePPN) return 0;
                return this.ppnAdded;
            },

            get netTotal() {
                return +this.totalHarga || 0;
            },

            get grandTotal() {
                const total = +this.totalHarga || 0;
                if (!this.includePPN) {
                    return total;
                }
                return total + this.ppnAdded;
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

            parseMoney(val) {
                if (val === null || val === undefined || val === '') return 0;
                let str = val.toString().trim();
                if (str.includes('.') && str.includes(',')) {
                    if (str.lastIndexOf('.') > str.lastIndexOf(',')) {
                        str = str.replace(/,/g, '');
                    } else {
                        str = str.replace(/\./g, '').replace(',', '.');
                    }
                } else if (str.includes(',')) {
                    str = str.replace(',', '.');
                } else if (str.includes('.')) {
                    const parts = str.split('.');
                    if (parts.length > 2 || (parts.length === 2 && parts[1].length === 3)) {
                        str = parts.join('');
                    }
                }
                const num = Number(str.replace(/[^0-9.-]+/g, ''));
                return Number.isFinite(num) ? num : 0;
            },

            sanitizePriceValue(value) {
                return this.parseMoney(value);
            },

            hasNonUMProducts() {
                const activeRows = [...(this.savedItems || [])];
                return activeRows.some(row => {
                    const code = (row?.fitemcode || '').toString().trim().toUpperCase();
                    return code !== '' && !code.startsWith('UM');
                });
            },

            focusPriceInput(row) {
                const absPrice = Math.abs(+row.fprice || 0);
                row.fpriceInput = absPrice > 0 ? this.fmt(absPrice) : '';
            },

            onPriceInput(row) {
                let absPrice = Math.abs(this.parseMoney(row.fpriceInput));
                const isUM = (row.fitemcode || '').toString().trim().toUpperCase().startsWith('UM');
                const refPrice = Math.abs(Number(row.ref_price || row.maxprice || row.source_price || row.fsisadp || 0));
                if (refPrice > 0 && absPrice > refPrice) {
                    absPrice = refPrice;
                    const labelRef = isUM ? 'sisa Uang Muka' : 'Harga Referensi';
                    const message = `Harga tidak boleh melebihi ${labelRef} (${this.fmt(refPrice)}).`;
                    if (typeof window.showAppWarningAlert === 'function') {
                        window.showAppWarningAlert('WARNING', message);
                    } else if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'warning', title: 'Validasi Gagal', text: message });
                    }
                }
                const hasOther = this.hasNonUMProducts();
                row.fprice = (isUM && hasOther) ? -absPrice : absPrice;
                this.recalc(row);
                this.recalcTotals();
            },

            blurPriceInput(row) {
                let absPrice = Math.abs(this.parseMoney(row.fpriceInput !== undefined ? row.fpriceInput : row.fprice));
                const isUM = (row.fitemcode || '').toString().trim().toUpperCase().startsWith('UM');
                const refPrice = Math.abs(Number(row.ref_price || row.maxprice || row.source_price || row.fsisadp || 0));
                if (refPrice > 0 && absPrice > refPrice) {
                    absPrice = refPrice;
                    const labelRef = isUM ? 'sisa Uang Muka' : 'Harga Referensi';
                    const message = `Harga tidak boleh melebihi ${labelRef} (${this.fmt(refPrice)}).`;
                    if (typeof window.showAppWarningAlert === 'function') {
                        window.showAppWarningAlert('WARNING', message);
                    } else if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'warning', title: 'Validasi Gagal', text: message });
                    }
                }
                const hasOther = this.hasNonUMProducts();
                row.fprice = (isUM && hasOther) ? -absPrice : absPrice;
                row.fpriceInput = ((isUM && hasOther && absPrice > 0) ? '-' : '') + this.fmt(absPrice);
                this.recalc(row);
                this.recalcTotals();
            },

            isPriceDisabled(row) {
                const code = String(row?.fitemcode || '').toUpperCase().trim();
                if (!code || code.startsWith('UM')) return false;
                const ref = String(row?.frefdtno || row?.frefno_display || row?.frefcode || row?.frefso || row?.frefsrj || row?.frefpr || row?.fnouref || '').trim();
                return Boolean(ref);
            },

            // ✅ FUNGSI BARU: Parse diskon dengan format "10+2"
            parseDiscount(discStr) {
                if (!discStr && discStr !== 0) return 0;

                // Jika sudah berupa angka, langsung return
                if (typeof discStr === 'number') return discStr;

                const str = String(discStr).trim();

                // Jika string kosong
                if (!str) return 0;

                // Jika angka biasa (tanpa operator)
                if (!isNaN(str)) {
                    return parseFloat(str) || 0;
                }

                // Parse ekspresi matematika sederhana (10+2, 5+3+2, dll)
                try {
                    // Hapus semua spasi
                    const cleaned = str.replace(/\s/g, '');

                    // Validasi: hanya boleh angka, titik desimal, dan operator + - * /
                    if (!/^[\d+\-*/.()]+$/.test(cleaned)) {
                        return 0;
                    }

                    // Hitung menggunakan Function (lebih aman dari eval)
                    const result = new Function('return ' + cleaned)();

                    // Return hasil jika valid, batasi maksimal 100%
                    return isFinite(result) ? Math.min(100, Math.max(0, result)) : 0;
                } catch (e) {
                    console.warn('Invalid discount format:', discStr);
                    return 0;
                }
            },

            normalizeDiscountValue(value) {
                const cleaned = String(value ?? '').replace(/\s+/g, '');
                if (cleaned === '') return '0';
                if (!cleaned.includes('+')) {
                    const num = Number(cleaned);
                    if (Number.isFinite(num)) {
                        return String(num);
                    }
                }
                return cleaned;
            },

            normalizeDiscountInput(event, row) {
                const normalized = this.normalizeDiscountValue(row?.fdisc);
                if (row) {
                    row.fdisc = normalized;
                    this.recalc(row);
                }
                if (event?.target) {
                    event.target.value = normalized;
                }
            },

            // ✅ UPDATE FUNGSI recalc untuk menggunakan parseDiscount
            recalc(row) {
                row.fqty = Math.max(0, +row.fqty || 0);
                row.fterima = Math.max(0, +row.fterima || 0);
                if (this.isSRJRow(row)) {
                    row.fprice = 0;
                    row.fpriceInput = this.fmt(0);
                    row.fdisc = '0';
                    row.ftotal = 0;
                } else {
                    const isUM = (row.fitemcode || '').toString().trim().toUpperCase() === 'UM';
                    let absPrice = Math.abs(+row.fprice || 0);
                    if (typeof row.fpriceInput !== 'undefined' && row.fpriceInput !== '') {
                        const sanitized = this.sanitizePriceValue(row.fpriceInput);
                        if (sanitized !== '') absPrice = Math.abs(+sanitized || 0);
                    }
                    const hasOther = this.hasNonUMProducts();
                    const shouldBeNegative = isUM && hasOther;
                    row.fprice = shouldBeNegative ? -absPrice : absPrice;

                    const discPercent = this.parseDiscount(row.fdisc);

                    const subtotal = row.fqty * row.fprice;
                    const discAmount = subtotal * (discPercent / 100);
                    row.ftotal = +(subtotal - discAmount).toFixed(2);
                }

                this.recalcTotals();
            },

            recalcTotals() {
                const hasOther = this.hasNonUMProducts();
                (this.savedItems || []).forEach(row => {
                    if (!row || !row.fitemcode) return;
                    const isUM = (row.fitemcode || '').toString().trim().toUpperCase() === 'UM';
                    if (isUM && !this.isSRJRow(row)) {
                        let absPrice = Math.abs(+row.fprice || 0);
                        const shouldBeNegative = hasOther;
                        row.fprice = shouldBeNegative ? -absPrice : absPrice;
                        const discPercent = this.parseDiscount ? this.parseDiscount(row.fdisc) : 0;
                        const subtotal = (row.fqty || 0) * row.fprice;
                        const discAmount = subtotal * (discPercent / 100);
                        row.ftotal = +(subtotal - discAmount).toFixed(2);
                        if (typeof row.fpriceInput !== 'undefined') {
                            row.fpriceInput = (shouldBeNegative && absPrice > 0 ? '-' : '') + this.fmt(absPrice);
                        }
                    }
                });

                this.totalHarga = this.savedItems.reduce((sum, item) => sum + (+item.ftotal || 0), 0);
            },

            productMeta(code) {
                const key = (code || '').toString().trim();
                const keyUpper = key.toUpperCase();
                let meta = window.PRODUCT_MAP?.[key] || window.PRODUCT_MAP?.[keyUpper];
                if (!meta && window.PRODUCT_MAP) {
                    const foundKey = Object.keys(window.PRODUCT_MAP).find(k => k.trim().toUpperCase() === keyUpper);
                    if (foundKey) meta = window.PRODUCT_MAP[foundKey];
                }
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
                if (!meta.unit_ratios) {
                    meta.unit_ratios = {
                        satuankecil: 1,
                        satuanbesar: 1,
                        satuanbesar2: 1
                    };
                }
                if (!meta.unit_names) {
                    meta.unit_names = {
                        satuankecil: meta.units?.[0] || '',
                        satuanbesar: meta.units?.[1] || '',
                        satuanbesar2: meta.units?.[2] || ''
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

            parseQtyValue(value) {
                const raw = String(value ?? '').trim();
                if (raw === '') return 0;
                const lastComma = raw.lastIndexOf(',');
                const lastDot = raw.lastIndexOf('.');
                const decimal = lastComma > lastDot ? ',' : '.';
                let normalized = raw.replace(/[^0-9,.-]+/g, '');
                if (lastComma !== -1 && lastDot !== -1) {
                    normalized = decimal === ',' ? normalized.replace(/\./g, '').replace(',', '.') : normalized.replace(/,/g, '');
                } else if (/^[+-]?\d{1,3}([,.]\d{3})+$/.test(normalized)) {
                    normalized = normalized.replace(/[,.]/g, '');
                } else {
                    normalized = normalized.replace(',', '.');
                }
                return Number(normalized) || 0;
            },

            getRowQtyLimit(row) {
                const parsedLimit = this.parseQtyValue(row?.maxqty ?? 0);
                const limitSource = Math.max(0, Number.isFinite(parsedLimit) ? parsedLimit : 0);
                if (row?.maxqty_unit !== 'kecil') return limitSource;

                return this.qtyKecilToUnit(limitSource, row?.fsatuan || '', this.productMeta(row?.fitemcode));
            },

            getRowReferenceQty(row) {
                const value = row?.faktur_qty ?? row?.qty_faktur ?? row?.srj_qty ?? row?.qty_asal ?? row?.ref_qty ?? row?.fsono_qty;
                if (value === undefined || value === null || value === '') return 0;
                const qty = this.parseQtyValue(value);
                return Number.isFinite(qty) ? qty : 0;
            },

            validateReferenceQty(row, showToast = true) {
                const hasRef = String(row?.frefso ?? '').trim() !== '' ||
                    String(row?.frefsrj ?? '').trim() !== '' ||
                    String(row?.frefdtno ?? '').trim() !== '';
                if (!hasRef) return true;

                const limit = this.getRowReferenceQty(row);
                if (limit <= 0) {
                    row.qtyInvalid = false;
                    return true;
                }

                const qty = this.parseQtyValue(row?.fqty ?? 0);
                row.qtyInvalid = !Number.isFinite(qty) || qty <= 0 || qty > limit;
                if (qty > limit) {
                    const refDoc = String(row?.frefsrj || row?.frefso || row?.frefdtno || '').trim();
                    const refLabel = (!refDoc.startsWith('SO.') && !refDoc.startsWith('SO/')) ? (refDoc.startsWith('SRJ.') || refDoc.startsWith('SRJ/') ? 'SRJ' : (refDoc.startsWith('UMJ') ? 'UMJ' : (refDoc.startsWith('RUJ') ? 'RUJ' : 'referensi'))) : 'SO';
                    const message = `Qty melebihi sisa ${refLabel}. Maksimal ${limit} ${row.fsatuan || ''}`.trim();
                    if (showToast) {
                        if (typeof window.showAppWarningAlert === 'function') {
                            window.showAppWarningAlert('WARNING', message);
                        } else if (typeof Swal !== 'undefined') {
                            Swal.fire({ icon: 'warning', title: 'Validasi Gagal', text: message });
                        } else {
                            window.toast?.error(message);
                        }
                    }
                    return false;
                }

                row.qtyInvalid = false;
                return qty > 0;
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
                if (n < 0) {
                    row.fqty = 0;
                    return;
                }

                // Enforce reference limit validation
                const hasRef = String(row?.frefso ?? '').trim() !== '' ||
                    String(row?.frefsrj ?? '').trim() !== '' ||
                    String(row?.frefdtno ?? '').trim() !== '';
                if (hasRef) {
                    const limit = this.getRowReferenceQty(row);
                    if (limit > 0 && n > limit) {
                        row.fqty = limit;
                        row.qtyInvalid = false;
                        const refDoc = String(row?.frefsrj || row?.frefso || row?.frefdtno || '').trim();
                        const refLabel = (!refDoc.startsWith('SO.') && !refDoc.startsWith('SO/')) ? (refDoc.startsWith('SRJ.') || refDoc.startsWith('SRJ/') ? 'SRJ' : (refDoc.startsWith('UMJ') ? 'UMJ' : (refDoc.startsWith('RUJ') ? 'RUJ' : 'referensi'))) : 'SO';
                        const message = `Qty tidak boleh melebihi sisa ${refLabel} (${limit} ${row.fsatuan || ''}).`.trim();
                        if (typeof window.showAppWarningAlert === 'function') {
                            window.showAppWarningAlert('WARNING', message);
                        } else if (typeof Swal !== 'undefined') {
                            Swal.fire({ icon: 'warning', title: 'Validasi Gagal', text: message });
                        } else {
                            window.toast?.error(message);
                        }
                    }
                }
            },

            hydrateRowFromMeta(row, meta) {
                if (!meta) {
                    row.fitemname = '';
                    row.units = [];
                    row.fsatuan = '';
                    row.frefcode = '';
                    row.maxqty = 0;
                    return;
                }
                row.fitemname = meta.name || '';
                if (row.frefcode !== 'SRJ' && row.frefcode !== 'SO' && row.frefcode !== 'INV' && row.frefcode !==
                    'UM' && !['REJ', 'RUJ'].includes(row.frefcode)) {
                    row.frefcode = '';
                }
                const currentUnit = (row.fsatuan ?? '').toString().trim();
                const units = [...new Set((meta.units || []).map(u => (u ?? '').toString().trim()).filter(Boolean))];
                const defaultUnit = (meta.default_unit || '').toString().trim();
                const resolvedDefaultUnit = defaultUnit && units.includes(defaultUnit) ? defaultUnit : (units[0] || '');
                row.units = currentUnit ?
                    [currentUnit, ...units.filter(u => u !== currentUnit)] :
                    units;
                if (!row.units.includes(currentUnit)) {
                    row.fsatuan = resolvedDefaultUnit;
                } else {
                    row.fsatuan = currentUnit;
                }
                if (meta.unit_ratios) row.unit_ratios = meta.unit_ratios;
                row.maxqty = Number.isFinite(+row.maxqty) ? +row.maxqty : 0;
                this.applyOutstandingDpRef(row);
            },

            async applyOutstandingDpRef(row) {
                const productCode = (row?.fitemcode || '').toString().trim().toUpperCase();
                if (!productCode.startsWith('UM')) return;

                const customerCode = this.getSelectedCustomerCode();
                if (!customerCode) return;

                const documents = window.RP_CUSTOMER_ADVANCE_WARNINGS?.[customerCode]?.documents || [];
                let doc = documents.find(item => Number(item.fsisadp || 0) > 0 && String(item.fsono || '').trim() !== '');

                if (!doc) {
                    try {
                        const params = new URLSearchParams({ fcustno: customerCode, fprdcode: 'UM' });
                        const res = await fetch(`{{ route('returpenjualan.product-history') }}?${params.toString()}`, {
                            headers: { Accept: 'application/json' }
                        });
                        if (res.ok) {
                            const payload = await res.json();
                            if (Array.isArray(payload?.data) && payload.data.length > 0) {
                                doc = payload.data[0];
                            }
                        }
                    } catch (e) {
                        console.warn('Gagal memuat history UM otomatis:', e);
                    }
                }

                if (doc) {
                    const docNo = doc.fsono || doc.fstockmtno || '';
                    row.frefsrj = docNo;
                    row.frefdtno = docNo;
                    row.frefpr = docNo;
                    row.frefso = docNo;
                    row.frefcode = 'UM';
                    const refQty = Number(doc.fqtyremain ?? doc.maxqty ?? doc.ref_qty ?? doc.source_qty ?? doc.faktur_qty ?? doc.qty_asal ?? doc.fqty ?? 1);
                    row.ref_qty = refQty;
                    row.maxqty = refQty;
                    row.source_qty = refQty;
                    row.faktur_qty = refQty;
                    row.qty_asal = Number(doc.qty_asal ?? refQty);
                    row.fqty = refQty;
                    if (doc.fsatuan && Array.isArray(row.units) && row.units.includes(doc.fsatuan)) {
                        row.fsatuan = doc.fsatuan;
                    } else if (doc.fsatuan) {
                        row.fsatuan = doc.fsatuan;
                    }
                    const price = Math.abs(Number(doc.fprice ?? doc.fsisadp ?? doc.fsisadp_rp ?? doc.famountmt ?? 0));
                    if (price > 0) {
                        row.ref_price = price;
                        row.maxprice = price;
                        row.source_price = price;
                        row.fsisadp = price;
                        row.fprice = price;
                        row.fpriceInput = this.fmt(price);
                    }
                    this.recalc?.(row);
                    this.recalcTotals?.();
                } else {
                    row.frefsrj = '';
                    row.frefdtno = '';
                    row.frefpr = '';
                    row.frefso = '';
                    row.frefcode = '';
                }
            },

            getSelectedCustomerCode() {
                const hid = document.getElementById('customerCodeHidden');
                const sel = document.getElementById('modal_filter_customer_id');
                return ((hid?.value || sel?.value || '')).toString().trim();
            },

            refreshOutstandingDpRefs() {
                (this.savedItems || []).forEach(row => this.applyOutstandingDpRef(row));
            },

            rowHasContent(row) {
                if (!row) return false;
                return [
                        row.fitemcode,
                        row.fitemname,
                        row.frefpr,
                        row.frefso,
                        row.frefsrj,
                        row.fdesc,
                        row.fketdt,
                    ].some(value => String(value ?? '').trim() !== '') ||
                    Number(row.fqty ?? 0) > 0 ||
                    Number(row.fprice ?? 0) > 0 ||
                    Number(row.fdisc ?? 0) > 0;
            },

            isRowSavable(row) {
                if (!row) return false;
                return String(row.fitemcode ?? '').trim() !== '' && Number(row.fqty ?? 0) > 0;
            },

            hasRequiredReference(row) {
                if (!row) return true;
                if (String(row.fitemcode || '').toUpperCase().trim() === 'UM') return true;
                return String(row.frefso || '').trim() !== '' ||
                       String(row.frefsrj || '').trim() !== '' ||
                       String(row.frefdtno || '').trim() !== '' ||
                       String(row.frefpr || '').trim() !== '' ||
                       String(row.frefno_display || '').trim() !== '';
            },

            isSRJRow(row) {
                if (!row) return false;
                if (String(row.fitemcode || '').toUpperCase().trim() === 'UM') return false;
                return row.frefcode === 'SRJ' || String(row.frefsrj ?? '').trim() !== '';
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

            onCodeTypedRow(row, index = null) {
                const typedCode = (row.fitemcode || '').toString().trim().toUpperCase();
                if (!typedCode) {
                    this.clearRow(row);
                    this.onRowUpdated(index);
                    return;
                }
                this.syncTransactionType();
                const isUM = this.ftypesales !== 0;
                if (isUM && typedCode !== '' && !typedCode.startsWith('UM') && !'UM'.startsWith(typedCode)) {
                    this.clearRow(row);
                    window.showAppWarningAlert('WARNING', 'Tipe Uang Muka hanya boleh menginput Uang Muka (UM).');
                    return;
                }
                if (!isUM && typedCode !== '' && typedCode.startsWith('UM')) {
                    this.clearRow(row);
                    window.showAppWarningAlert('WARNING', 'Tipe Penjualan tidak boleh menginput Uang Muka (UM).');
                    return;
                }
                row.fitemcode = typedCode;
                this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode));
                if (!this.normalizeNoAcak(row.fnoacak)) {
                    row.fnoacak = this.generateUniqueNoAcak();
                }
                this.recalc(row);
                this.onRowUpdated(index);
            },

            isComplete(row) {
                return row.fitemcode && row.fitemname && row.fsatuan && Number(row.fqty) > 0;
            },

            normalizeNoAcak(value) {
                const normalized = String(value ?? '').trim();
                return /^\d{3}$/.test(normalized) ? normalized : '';
            },

            normalizeRefNoAcak(value) {
                const parts = String(value ?? '').split(',').map(v => v.trim()).filter(v => /^\d{3}$/.test(v));
                return parts[0] ?? '';
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

            onPrPicked(e, source) {
                const {
                    header,
                    items
                } = e.detail || {};
                if (!items || !Array.isArray(items)) return;
                this.addManyFromPR(header, items, source);
            },

            addManyFromPR(header, items, source) {
                const existing = new Set(this.getCurrentItemKeys());
                let added = 0,
                    duplicates = [];

                items.forEach(src => {
                    const sourceUnit = (src.fsatuan ?? '').toString().trim();
                    const itemcode = (src.fitemcode ?? '').toString().trim();
                    const meta = this.productMeta(itemcode);
                    const qtyAvail = (src.fqtyremain !== undefined && src.fqtyremain !== null && Number(src.fqtyremain) > 0)
                        ? Number(src.fqtyremain)
                        : Number(src.fqty ?? src.ref_qty ?? src.qty_asal ?? 0);
                    const displayQty = Number(src.fqtyremain_dokumen ?? 0) > 0 ?
                        Number(src.fqtyremain_dokumen) :
                        this.qtyKecilToUnit(qtyAvail, sourceUnit, meta);
                    const refQty = this.parseQtyValue(src.faktur_qty ?? src.qty_faktur ?? src.srj_qty ?? src.qty_asal ?? src.fqty ?? displayQty);
                    const documentNo = (source === 'SRJ' ? (header?.fstockmtno ?? '') : (header?.fsono ?? ''))
                        .toString()
                        .trim();
                    const referenceNo = (src.frefpr ?? header?.fsono ?? header?.fdisplayref ?? documentNo ??
                            '')
                        .toString()
                        .trim();
                    const row = {
                        uid: cryptoRandom(),
                        fitemcode: itemcode,
                        fitemname: src.fitemname ?? '',
                        fsatuan: sourceUnit,
                        fdisplayunit: (src.fdisplayunit ?? src.fsatuan ?? '').toString().trim(),
                        frefdtno: src.frefdtno ?? '',
                        fnouref: ((src.fnouref ?? documentNo) ?? '').toString().trim(),
                        frefpr: referenceNo,
                        frefcode: source,

                        frefso: source === 'SRJ' ? '' : ((source === 'SO' || source === 'INV') ? (header
                            ?.fsono ?? '') : ((src.frefso ?? '').toString().trim())),
                        frefsrj: source === 'SRJ' ? (header?.fstockmtno ?? '') : ((src.frefsrj ?? '')
                            .toString().trim()),
                        fnoacak: this.generateUniqueNoAcak(),
                        frefnoacak: this.normalizeRefNoAcak(src.frefnoacak ?? src.fnoacak ?? ''),

                        fprhid: src.fprhid ?? header?.fprhid ?? '',
                        ref_qty: refQty,
                        faktur_qty: source === 'INV' ? refQty : 0,
                        srj_qty: source === 'SRJ' ? refQty : 0,
                        fsono_qty: source === 'SO' ? refQty : 0,
                        fqty: displayQty > 0 ? displayQty : 1,
                        fterima: Number(src.fterima ?? 0),
                        fprice: Number(src.fprice ?? src.fharga ?? 0),
                        ref_price: Number(src.fprice ?? src.fharga ?? 0),
                        maxprice: Number(src.fprice ?? src.fharga ?? 0),
                        source_price: Number(src.fprice ?? src.fharga ?? 0),
                        fdisc: src.fdisc ?? 0,
                        ftotal: Number(src.ftotal ?? 0),
                        fdesc: src.fdesc ?? '',
                        fketdt: src.fketdt ?? '',
                        units: sourceUnit ?
                            [
                                sourceUnit,
                                ...(Array.isArray(src.units) ?
                                    src.units.map(u => (u ?? '').toString().trim()).filter(Boolean).filter(
                                        u => u !== sourceUnit) :
                                    []),
                            ] :
                            (Array.isArray(src.units) ?
                                src.units.map(u => (u ?? '').toString().trim()).filter(Boolean) :
                                []),
                        maxqty: Math.max(0, qtyAvail),
                        maxqty_unit: 'kecil',
                    };

                    this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode));

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

                    const rowLimit = this.getRowQtyLimit(row);
                    if (!(rowLimit > 0)) return;
                    if (!(Number(row.fqty) > 0) && rowLimit > 0) {
                        row.fqty = Number(rowLimit);
                    }
                    const nextRow = {
                        ...this.createRow(),
                        ...row,
                        uid: cryptoRandom(),
                    };
                    nextRow.fpriceInput = this.fmt(nextRow.fprice);
                    this.savedItems.push(nextRow);
                    this.$nextTick(() => {
                        const target = this.savedItems[this.savedItems.length - 1];
                        const lockedUnit = (target?.fdisplayunit ?? '').toString().trim();
                        if (target && lockedUnit) {
                            target.fsatuan = lockedUnit;
                            if (!Array.isArray(target.units)) {
                                target.units = [];
                            }
                            if (!target.units.includes(lockedUnit)) {
                                target.units.unshift(lockedUnit);
                            }
                        }
                    });
                    existing.add(key);
                    added++;
                    this.onRowUpdated(this.savedItems.length - 1);
                });

                this.pruneEmptyRows();
                this.ensureMinimumRows();
                this.ensureTrailingRow();
                this.recalcTotals();
            },

            onRowUpdated(index = null) {
                this.syncTransactionType();
                const row = typeof index === 'number' ? this.savedItems[index] : null;
                if (row) {
                    this.enforceQtyRow(row);
                    const isUM = this.ftypesales !== 0;
                    const code = (row.fitemcode || '').toString().trim().toUpperCase();
                    if (isUM && code !== '' && !code.startsWith('UM') && !'UM'.startsWith(code)) {
                        this.clearRow(row);
                    } else if (!isUM && code.startsWith('UM')) {
                        this.clearRow(row);
                    }

                    if (Number(row.fprice) < 0) {
                        row.fprice = 0;
                    }

                    row.fnoacak = this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak();
                    row.frefnoacak = this.normalizeRefNoAcak(row.frefnoacak);
                    this.recalc(row);
                }

                this.showNoItems = false;
                this.ensureTrailingRow(index);
                this.recalcTotals();
            },

            removeSaved(i) {
                this.savedItems.splice(i, 1);
                this.ensureMinimumRows();
                this.ensureTrailingRow();
                this.recalcTotals();
            },

            onSubmit($event) {
                $event?.preventDefault?.();
                const items = this.submitItems;

                if (items.length === 0) {
                    this.showNoItems = true;
                    return false;
                }

                for (let i = 0; i < items.length; i++) {
                    const row = items[i];
                    row.qtyInvalid = false;
                    if (!this.hasRequiredReference(row)) {
                        this.showToast(`Produk ${row.fitemcode} wajib memiliki no. referensi SRJ atau Faktur Penjualan.`, 'error');
                        return false;
                    }

                    const returnQty = this.parseQtyValue(row.fqty ?? row.qty ?? 0);
                    const refQty = this.getRowReferenceQty(row);
                    console.log({ returnQty, refQty, rowData: row });
                    if (refQty <= 0 && String(row?.fitemcode || '').toUpperCase().trim() !== 'UM') {
                        row.qtyInvalid = true;
                        this.focusRowQty(this.savedItems.indexOf(row));
                        Swal.fire({
                            icon: 'error',
                            title: 'Validasi Gagal',
                            text: `Row ${i + 1}: Qty referensi Faktur/SRJ tidak ditemukan.`,
                        });
                        return false;
                    }
                    if (refQty > 0 && (returnQty > refQty || !this.validateReferenceQty(row, true))) {
                        row.qtyInvalid = true;
                        this.focusRowQty(this.savedItems.indexOf(row));
                        Swal.fire({
                            icon: 'error',
                            title: 'Validasi Gagal',
                            text: `Row ${i + 1}: Qty retur (${returnQty}) melebihi Qty referensi (${refQty})`,
                        });
                        return false;
                    }
                }

                return window.submitFormWithStockMinusConfirmation?.($event?.target);
            },

            focusRowUnit(row, index) {
                if (row?.units?.length > 1) {
                    document.getElementById(`unit_row_${index}`)?.focus();
                    return;
                }
                this.focusRowQty(index);
            },

            focusRowQty(index) {
                document.getElementById(`qty_row_${index}`)?.focus();
            },

            focusRowPrice(index) {
                document.getElementById(`price_row_${index}`)?.focus();
            },

            showDescModal: false,
            descTarget: 'draft',
            descSavedIndex: null,
            descValue: '',
            descItemName: '',
            _descTarget: null,
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
                if (this._descTarget) this._descTarget.fdesc = this.descValue;
                this.closeDesc();
            },

            showHistoryModal: false,
            historyLoading: false,
            historyRows: [],
            historyTargetRow: null,
            canOpenHistory(targetRow) {
                const customerCode = (document.getElementById('customerCodeHidden')?.value || '').trim();
                const productCode = (targetRow?.fitemcode || '').toString().trim();
                return customerCode !== '' && productCode !== '';
            },
            closeHistory() {
                this.showHistoryModal = false;
                this.historyLoading = false;
                this.historyRows = [];
                this.historyTargetRow = null;
            },
            async openProductHistory(targetRow) {
                const customerCode = (document.getElementById('customerCodeHidden')?.value || '').trim();
                const productCode = (targetRow?.fitemcode || '').toString().trim();

                if (customerCode === '') {
                    if (typeof window.showAppWarningAlert === 'function') {
                        window.showAppWarningAlert('WARNING', 'Customer wajib dipilih terlebih dahulu.');
                    } else if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'warning', title: 'Peringatan', text: 'Customer wajib dipilih terlebih dahulu.' });
                    } else {
                        this.showToast('Customer wajib dipilih terlebih dahulu.', 'warning');
                    }
                    return;
                }

                if (productCode === '') {
                    this.showToast('Pilih produk terlebih dahulu.', 'warning');
                    return;
                }

                this.showHistoryModal = true;
                this.historyLoading = true;
                this.historyRows = [];
                this.historyTargetRow = targetRow;

                try {
                    const params = new URLSearchParams({
                        fcustno: customerCode,
                        fprdcode: productCode,
                    });
                    const response = await fetch(
                        `{{ route('returpenjualan.product-history') }}?${params.toString()}`, {
                            headers: {
                                Accept: 'application/json',
                            },
                        });

                    const payload = await response.json();
                    if (!response.ok) {
                        throw new Error(payload?.message || 'Gagal memuat riwayat produk.');
                    }

                    this.historyRows = Array.isArray(payload?.data) ? payload.data : [];
                } catch (error) {
                    this.historyRows = [];
                    this.showToast(error.message || 'Gagal memuat riwayat produk.', 'error');
                } finally {
                    this.historyLoading = false;
                }
            },
            selectHistory(row) {
                if (this.historyTargetRow && row) {
                    const isUM = (this.historyTargetRow?.fitemcode || '').toString().trim().toUpperCase().startsWith('UM');
                    this.historyTargetRow.frefpr = row.fsono || '';
                    this.historyTargetRow.frefso = row.fsono || '';
                    this.historyTargetRow.frefcode = isUM ? 'UM' : 'INV';
                    this.historyTargetRow.frefdtno = row.frefdtno || row.fsono || '';
                    this.historyTargetRow.frefsrj = isUM ? (row.fsono || '') : '';
                    this.historyTargetRow.frefnoacak = this.normalizeRefNoAcak(row.frefnoacak ?? '');
                    const refQtyVal = Number(row.fqtyremain ?? row.maxqty ?? row.faktur_qty ?? row.qty_faktur ?? row.qty_asal ?? row.fqty ?? 0);
                    this.historyTargetRow.ref_qty = refQtyVal;
                    this.historyTargetRow.maxqty = refQtyVal;
                    this.historyTargetRow.source_qty = refQtyVal;
                    this.historyTargetRow.faktur_qty = refQtyVal;
                    this.historyTargetRow.qty_asal = Number(row.qty_asal ?? refQtyVal);
                    if (typeof row.fqty !== 'undefined' && +row.fqty > 0) {
                        this.historyTargetRow.fqty = Math.min(+row.fqty, refQtyVal > 0 ? refQtyVal : +row.fqty);
                    }
                    const priceVal = typeof row.fprice !== 'undefined' ? +row.fprice : (typeof row.fharga !== 'undefined' ? +row.fharga : null);
                    if (priceVal !== null && priceVal >= 0) {
                        const absPrice = Math.abs(priceVal);
                        this.historyTargetRow.ref_price = absPrice;
                        this.historyTargetRow.maxprice = absPrice;
                        this.historyTargetRow.source_price = absPrice;
                        this.historyTargetRow.fsisadp = Number(row.fsisadp ?? absPrice);
                        this.historyTargetRow.fprice = absPrice;
                        if (typeof this.historyTargetRow.fpriceInput !== 'undefined') {
                            this.historyTargetRow.fpriceInput = this.fmt(absPrice);
                        }
                    }
                    if (row.fsatuan && Array.isArray(this.historyTargetRow.units) && this.historyTargetRow.units.includes(row.fsatuan)) {
                        this.historyTargetRow.fsatuan = row.fsatuan;
                    }
                    const targetIndex = this.savedItems.indexOf(this.historyTargetRow);
                    if (targetIndex !== -1) {
                        this.onRowUpdated(targetIndex);
                    }
                    this.recalc?.(this.historyTargetRow);
                    this.recalcTotals?.();
                }
                this.closeHistory();
            },

            itemKey(it) {
                return (it.fitemcode ?? '').toString().trim().toUpperCase();
            },

            getCurrentItemKeys() {
                return this.savedItems
                    .filter(it => it.fitemcode)
                    .map(it => this.itemKey(it));
            },

            get submitItems() {
                return this.savedItems.filter(row => this.isRowSavable(row));
            },

            createRow() {
                return {
                    ...newRow(),
                    uid: cryptoRandom(),
                    formIndex: this.allocateFormIndex(),
                    fnoacak: this.generateUniqueNoAcak(),
                };
            },

            clearRow(row) {
                if (!row) return;
                Object.assign(row, newRow(), { uid: row.uid, formIndex: row.formIndex });
                this.recalc(row);
            },

            allocateFormIndex() {
                const index = Number(this.nextFormIndex || 0);
                this.nextFormIndex = index + 1;
                return index;
            },

            pruneEmptyRows() {
                const filled = this.savedItems.filter(row => this.rowHasContent(row));
                this.savedItems = filled.length ? filled : [];
            },

            // Tambahkan di Alpine data
            showToast(message, type = 'info') {
                // Buat element toast
                const toast = document.createElement('div');
                toast.className = `fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg flex items-center gap-3 animate-slide-in ${
        type === 'warning' ? 'bg-amber-100 text-amber-800 border border-amber-300' :
        type === 'error' ? 'bg-red-100 text-red-800 border border-red-300' :
        type === 'success' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' :
        'bg-blue-100 text-blue-800 border border-blue-300'
    }`;

                toast.innerHTML = `
        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            ${type === 'warning' ? '<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>' :
            type === 'error' ? '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>' :
            type === 'success' ? '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>' :
            '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>'}
        </svg>
        <span class="font-medium">${message}</span>
    `;

                document.body.appendChild(toast);

                // Auto remove setelah 3 detik
                setTimeout(() => {
                    toast.classList.add('animate-slide-out');
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            },

            async fetchProductPriceHistory(row, index = null) {
                const customerCode = (document.getElementById('customerCodeHidden')?.value || '').trim();
                const productCode = (row?.fitemcode || '').toString().trim();
                if (!customerCode || !productCode || String(productCode).toUpperCase().startsWith('UM')) return;
                if (row.frefsrj || row.frefso || row.frefcode === 'SRJ' || row.frefcode === 'INV') return;

                try {
                    const params = new URLSearchParams({
                        fcustno: customerCode,
                        fprdcode: productCode,
                        fsatuan: (row.fsatuan || '').toString().trim(),
                    });
                    const res = await fetch(`{{ route('returpenjualan.product-price') }}?${params.toString()}`, {
                        headers: { Accept: 'application/json' }
                    });
                    if (!res.ok) return;
                    const json = await res.json();
                    if ((row?.fitemcode || '').toString().trim() !== productCode) return;
                    if (json.source === 'history' || json.price > 0) {
                        row.fprice = Number(json.price ?? 0);
                        row.fpriceInput = this.fmt(row.fprice);
                        if (json.discount !== undefined && json.discount !== null && json.discount !== '') {
                            row.fdisc = json.discount;
                        }
                        if (typeof index === 'number') {
                            this.onRowUpdated(index);
                        } else {
                            this.recalc(row);
                        }
                    }
                } catch (e) {
                    console.error('Error fetching product price history:', e);
                }
            },

            init() {
                this.$watch('includePPN', () => this.recalcTotals());
                this.$watch('ppnRate', () => this.recalcTotals());

                window.getCurrentItemKeys = () => this.getCurrentItemKeys();
                this.savedItems = (this.savedItems || []).map(item => {
                    const row = {
                        ...this.createRow(),
                        ...item,
                        fnoacak: this.normalizeNoAcak(item.fnoacak) || this.generateUniqueNoAcak(),
                        frefnoacak: this.normalizeRefNoAcak(item.frefnoacak),
                    };
                    this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode));
                    if (item.fitemname) {
                        row.fitemname = item.fitemname;
                    }
                    this.recalc(row);
                    return row;
                });
                this.savedItems.forEach((item) => {
                    const lockedUnit = (item?.fdisplayunit ?? '').toString().trim();
                    if (lockedUnit) {
                        item.fsatuan = lockedUnit;
                        if (!Array.isArray(item.units)) {
                            item.units = [];
                        }
                        if (!item.units.includes(lockedUnit)) {
                            item.units.unshift(lockedUnit);
                        }
                    }
                });
                window.addEventListener('invoice-picked', (e) => this.onPrPicked(e, 'INV'), {
                    passive: true
                });
                window.addEventListener('srj-picked', (e) => this.onPrPicked(e, 'SRJ'), {
                    passive: true
                });
                window.addEventListener('customer-selected', () => this.refreshOutstandingDpRefs(), {
                    passive: true
                });

                window.addEventListener('product-chosen', async (e) => {
                    const {
                        product
                    } = e.detail || {};
                    if (!product) return;

                    // ── UM / Type guard ─────────────────────────────────────
                    const chosenCode  = (product.fprdcode || '').toString().trim().toUpperCase();
                    const isUMProduct = chosenCode.startsWith('UM');
                    const isUMType    = this.ftypesales !== 0;

                    if (isUMProduct && !isUMType) {
                        window.showAppWarningAlert('WARNING', 'Tipe Penjualan tidak boleh menginput Uang Muka (UM).');
                        return;
                    }
                    if (!isUMProduct && chosenCode !== '' && isUMType) {
                        window.showAppWarningAlert('WARNING', 'Tipe Uang Muka hanya boleh menginput Uang Muka (UM).');
                        return;
                    }
                    // ────────────────────────────────────────────────────────

                    const apply = (row) => {
                        row.fitemcode = (product.fprdcode || '').toString();
                        row.frefcode = '';
                        row.frefpr = '';
                        row.fnouref = '';
                        row.frefdtno = '';
                        row.frefso = '';
                        row.frefsrj = '';
                        this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode));
                        row.fnoacak = this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak();
                        if (!row.fqty) row.fqty = 0;
                        this.recalc(row);
                    };
                    const index = typeof this.browseTarget === 'number' ? this.browseTarget : this.savedItems
                        .length - 1;
                    if (index < 0 || !this.savedItems[index]) return;
                    const row = this.savedItems[index];
                    apply(row);
                    this.onRowUpdated(index);
                    await this.fetchProductPriceHistory(row, index);
                    this.$nextTick(() => this.focusRowQty(index));
                }, {
                    passive: true
                });

                this.pruneEmptyRows();
                this.ensureMinimumRows();
                this.ensureTrailingRow();
                this.recalcTotals();
            },

            openBrowseFor(index) {
                this.browseTarget = typeof index === 'number' ? index : this.savedItems.length - 1;
                const isUM = this.ftypesales !== 0;
                window.dispatchEvent(new CustomEvent('browse-open', {
                    detail: {
                        forEdit: false,
                        codePrefix: isUM ? 'UM' : ''
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
                frefcode: '',
                units: [],
                fsatuan: '',
                frefdtno: '',
                fnouref: '',
                frefpr: '',
                frefso: '',
                frefsrj: '',
                fnoacak: '',
                frefnoacak: '',
                fqty: 0,
                fterima: 0,
                fprice: 0,
                fpriceInput: '0,00',
                fdisc: 0,
                ftotal: 0,
                fdesc: '',
                fketdt: '',
                maxqty: 0,
                maxqty_unit: '',
            };
        }

        function cryptoRandom() {
            return (window.crypto?.getRandomValues ? [...window.crypto.getRandomValues(new Uint32Array(2))].map(n => n
                    .toString(16)).join('') :
                Math.random().toString(36).slice(2)) + Date.now();
        }

    }
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {});
</script>
<script>
    window.prhFormModal = function() {
        return {
            show: false,
            rows: [],
            search: '',
            perPage: 10,
            currentPage: 1,
            lastPage: 1,
            total: 0,
            loading: false,

            showDupModal: false,
            dupCount: 0,
            dupSample: [],
            pendingHeader: null,
            pendingUniques: [],

            openDupModal(header, duplicates, uniques) {
                window.transactionReferenceModalHelper.openDupModal(this, header, duplicates, uniques);
            },
            closeDupModal() {
                window.transactionReferenceModalHelper.closeDupModal(this);
            },
            confirmAddUniques() {
                // kirim hanya item unik
                const currentKeys = new Set((window.getCurrentItemKeys?.() || []).map(String));
                const keyOf = (src) =>
                    (src.fitemcode ?? '').toString().trim().toUpperCase();
                const safeUniques = this.pendingUniques.filter(src => !currentKeys.has(keyOf(src)));

                if (safeUniques.length > 0) {
                    window.transactionReferenceModalHelper.dispatchPick('invoice-picked', this.pendingHeader,
                        safeUniques);
                }

                window.transactionReferenceModalHelper.closeDupModal(this);
                this.closeModal?.();
            },

            openModal() {
                this.show = true;
                this.goToPage(1);
            },
            closeModal() {
                this.show = false;
            },

            async fetchData() {
                this.loading = true;
                try {
                    const params = new URLSearchParams({
                        search: this.search ?? '',
                        per_page: this.perPage,
                        page: this.currentPage,
                        only_remaining: true,
                    });

                    // Filter by selected customer
                    const custCode = (document.getElementById('customerCodeHidden')?.value || '').trim();
                    if (custCode) params.set('fcustno', custCode);

                    const res = await fetch(`{{ route('returpenjualan.pickable') }}?` + params.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const json = await res.json();

                    this.rows = json.data ?? [];
                    this.currentPage = (json.current_page ?? json.links?.current_page) ?? 1;
                    this.lastPage = (json.last_page ?? json.links?.last_page) ?? 1;
                    this.total = (json.total ?? json.links?.total) ?? (json.data_total ?? 0);
                } catch (e) {
                    console.error(e);
                    this.rows = [];
                } finally {
                    this.loading = false;
                }
            },

            goToPage(p) {
                if (p < 1) p = 1;
                this.currentPage = p;
                this.fetchData();
            },

            formatDate(s) {
                if (!s || s === 'No Date') return '-';
                const d = new Date(s);
                if (isNaN(d)) return '-';
                const pad = n => n.toString().padStart(2, '0');
                return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
            },

            async pick(row) {
                try {
                    if (row.fnonactive == '1') {
                        Swal.fire({
                            icon: 'warning',
                            title: @json('Produk Discontinue'),
                            html: `${@json('Produk :name sudah tidak diproduksi lagi.').replace('__NAME__', `<b>${row.fprdname}</b>`)}<br><br>${@json('Penyimpanan Batal')}.`,
                            confirmButtonColor: '#f59e0b', // Warna orange amber
                            confirmButtonText: 'Kembali'
                        });
                        return; // Hentikan proses, jangan tambahkan ke tabel
                    }
                    const url = `{{ route('returpenjualan.items', ['id' => 'INV_ID_PLACEHOLDER']) }}`
                        .replace('INV_ID_PLACEHOLDER', row.ftranmtid);

                    const res = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const json = await res.json();

                    const items = (json.items || []).filter(src => Number(src.fqtyremain ?? src.fqty ?? src.ref_qty ?? 0) > 0);
                    if (items.length === 0) {
                        window.toast?.warning('Semua item Faktur ini sudah habis atau sudah digunakan.');
                        return;
                    }
                    const currentKeys = new Set((window.getCurrentItemKeys?.() || []).map(String));

                    const keyOf = (src) =>
                        (src.fitemcode ?? '').toString().trim().toUpperCase();

                    const seenKeys = new Set(currentKeys);
                    const duplicates = [];
                    const uniques = [];

                    items.forEach(src => {
                        const key = keyOf(src);
                        if (seenKeys.has(key)) {
                            duplicates.push(src);
                        } else {
                            uniques.push(src);
                            seenKeys.add(key);
                        }
                    });

                    if (duplicates.length > 0) {
                        this.openDupModal(row, duplicates, uniques);
                        return; // tunggu aksi user di modal
                    }

                    // tidak ada duplikat → langsung kirim semua item yang unik
                    window.dispatchEvent(new CustomEvent('invoice-picked', {
                        detail: {
                            header: row,
                            items: uniques
                        }
                    }));
                    this.closeModal();

                } catch (e) {
                    console.error(e);
                    window.showAppErrorAlert('TERJADI KESALAHAN', @json('GAGAL MENGAMBIL DETAIL FAKTUR PENJUALAN.'));
                }
            },
        };
    };

    window.soFormModal = function() {
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

                this.table = $('#poTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('returpenjualan.pickable') }}",
                        type: 'GET',
                        data: function(d) {
                            var params = {
                                draw: d.draw,
                                start: d.start,
                                length: d.length,
                                search: d.search.value,
                                order_column: d.columns[d.order[0].column].data,
                                order_dir: d.order[0].dir,
                                only_remaining: true
                            };
                            // Filter by selected customer
                            var custCode = (document.getElementById('customerCodeHidden')?.value || '')
                                .trim();
                            if (custCode) params.fcustno = custCode;
                            return params;
                        }
                    },
                    columns: [{
                            data: 'fsono',
                            name: 'fsono',
                            className: 'font-mono text-sm'
                        },
                        {
                            data: 'frefno',
                            name: 'frefno',
                            className: 'font-mono text-sm'
                        },
                        {
                            data: 'fsodate',
                            name: 'fsodate',
                            className: 'text-sm',
                            render: function(data) {
                                return formatDate(data);
                            }
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center',
                            width: '100px',
                            render: function(data, type, row) {
                                return '<button type="button" class="btn-pick px-4 py-1.5 rounded-md text-sm font-bold bg-teal-600 hover:bg-teal-700 text-white transition-colors duration-150">{{ 'Pilih' }}</button>';
                            }
                        }
                    ],
                    pageLength: 10,
                    lengthMenu: [
                        [10, 25, 50, 100],
                        [10, 25, 50, 100]
                    ],
                    dom: '<"#poTableControls"fl>rt<"#poTablePagination"ip>',
                    language: {
                        processing: @json('Memuat data...'),
                        search: @json('Search' . ':'),
                        lengthMenu: @json('Tampilkan _MENU_'),
                        info: @json('Menampilkan _START_ - _END_ dari _TOTAL_ data'),
                        infoEmpty: @json('Tidak ada data'),
                        infoFiltered: "(disaring dari _MAX_ total data)",
                        zeroRecords: @json('Tidak ada data yang ditemukan'),
                        emptyTable: @json('Tidak ada data tersedia'),
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
                        const api = this.api();
                        const $container = $(api.table().container());

                        // Move controls to designated areas (Search left, Tampilkan 10 right)
                        const $filter = $container.find('.dt-search, .dataTables_filter');
                        const $length = $container.find('.dt-length, .dataTables_length');

                        $filter.css({ float: 'none', margin: 0 });
                        $length.css({ float: 'none', margin: 0 });
                        $('#poTableControls').css({
                            display: 'flex',
                            'justify-content': 'space-between',
                            'align-items': 'center',
                            width: '100%'
                        }).empty().append($filter).append($length);

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
                const self = this;
                $('#poTable').off('click', '.btn-pick').on('click', '.btn-pick', function() {
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
                window.dispatchEvent(new CustomEvent('invoice-picked', {
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
                    if (row.fnonactive == '1') {
                        Swal.fire({
                            icon: 'warning',
                            title: @json('Produk Discontinue'),
                            html: `${@json('Produk :name sudah tidak diproduksi lagi.').replace('__NAME__', `<b>${row.fprdname}</b>`)}<br><br>${@json('Penyimpanan Batal')}.`,
                            confirmButtonColor: '#f59e0b', // Warna orange amber
                            confirmButtonText: 'Kembali'
                        });
                        return; // Hentikan proses, jangan tambahkan ke tabel
                    }

                    const url = `{{ route('returpenjualan.items', ['id' => 'INV_ID_PLACEHOLDER']) }}`
                        .replace('INV_ID_PLACEHOLDER', row.ftranmtid);

                    const res = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!res.ok) {
                        throw new Error(`Server error: ${res.status}`);
                    }

                    const json = await res.json();

                    const items = (json.items || []).filter(src => Number(src.fqtyremain ?? 0) > 0);
                    if (items.length === 0) {
                        window.toast?.warning('Semua item Faktur ini sudah habis atau sudah digunakan.');
                        return;
                    }
                    const currentKeys = new Set((window.getCurrentItemKeys?.() || []).map(String));
                    const keyOf = (src) =>
                        (src.fitemcode ?? '').toString().trim().toUpperCase();

                    const seenKeys = new Set(currentKeys);
                    const duplicates = [];
                    const uniques = [];

                    items.forEach(src => {
                        const key = keyOf(src);
                        if (seenKeys.has(key)) {
                            duplicates.push(src);
                        } else {
                            uniques.push(src);
                            seenKeys.add(key);
                        }
                    });

                    if (duplicates.length > 0) {
                        this.openDupModal(json.header, duplicates, uniques);
                        return;
                    }

                    window.dispatchEvent(new CustomEvent('invoice-picked', {
                        detail: {
                            header: json.header,
                            items: uniques
                        }
                    }));

                    this.closeModal();
                } catch (e) {
                    console.error('Error:', e);
                    window.toast?.error(`${@json('Gagal mengambil detail Faktur Penjualan:')} ${e.message}`);
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
        return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
    }
</script>

@include('components.transaction.invoice-srj-modal-script')

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    @include('components.transaction.browse-warehouse-script')

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
                                const ftypesales = Number(document.querySelector('select[name="ftypesales"]')?.value ?? 0);
                                const result = {
                                    draw: d.draw,
                                    start: d.start,
                                    length: d.length,
                                    search: d.search.value,
                                    order_column: d.columns[d.order[0].column].data,
                                    order_dir: d.order[0].dir
                                };
                                // Uang Muka: tampilkan hanya kode produk UM; Penjualan: sembunyikan tipe UM
                                if (ftypesales !== 0) {
                                    result.fprdcode_prefix = 'UM';
                                } else {
                                    result.exclude_type = 'UM';
                                }
                                return result;
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
                                    return '<button type="button" class="btn-choose px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150">{{ 'Pilih' }}</button>';
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
                            processing: @json('Memuat data...'),
                            search: @json('Search' . ':'),
                            lengthMenu: @json('Tampilkan _MENU_'),
                            info: @json('Menampilkan _START_ - _END_ dari _TOTAL_ data'),
                            infoEmpty: @json('Tidak ada data'),
                            infoFiltered: "(disaring dari _MAX_ total data)",
                            zeroRecords: @json('Tidak ada data yang ditemukan'),
                            emptyTable: @json('Tidak ada data tersedia'),
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
                    $('#productTable').off('click.prodpick');
                    $('#productTable tbody').off('click.prodpick');

                    $('#productTable').off('click.prodpick').on('click.prodpick', '.btn-choose', (e) => {
                        e.preventDefault();
                        e.stopPropagation();

                        const data = this.table.row($(e.target).closest('tr')).data();
                        if (data) this.choose(data);
                    });

                    $('#productTable tbody').on('click.prodpick', 'tr', (e) => {
                        if ($(e.target).closest('button, a, input, select, textarea').length) {
                            return;
                        }

                        const data = this.table?.row(e.currentTarget).data();
                        if (!data) {
                            return;
                        }

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

        window.getReturPenjualanDuplicateCode = function(form) {
            const seen = new Set();
            const inputs = Array.from(form.querySelectorAll('input[name^="fitemcode["]'));

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
    </script>
@endpush
