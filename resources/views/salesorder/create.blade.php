@extends('layouts.app')

@section('title', 'Sales Order - New')

@section('content')
    @php
        $oldSoItemCodes = old('fitemcode', []);
        $oldSoItemNames = old('fitemname', []);
        $oldSoUnits = old('fsatuan', []);
        $oldSoRefNos = old('frefdtno', []);
        $oldSoNouRefs = old('fnouref', []);
        $oldSoRefPrs = old('frefpr', []);
        $oldSoNoAcaks = old('fnoacak', []);
        $oldSoQtys = old('fqty', []);
        $oldSoTerimas = old('fterima', []);
        $oldSoPrices = old('fprice', []);
        $oldSoDiscs = old('fdisc', []);
        $oldSoTotals = old('ftotal', []);
        $oldSoDescs = old('fdesc', []);
        $oldSoKetdts = old('fketdt', []);
        $initialSalesOrderItems = [];

        foreach ($oldSoItemCodes as $index => $itemCode) {
            $code = trim((string) $itemCode);
            $name = trim((string) ($oldSoItemNames[$index] ?? ''));
            if ($code === '' && $name === '') {
                continue;
            }

            $unit = trim((string) ($oldSoUnits[$index] ?? ''));
            $refPr = trim((string) ($oldSoRefPrs[$index] ?? ''));
            $refDtNo = trim((string) ($oldSoRefNos[$index] ?? ''));

            $initialSalesOrderItems[] = [
                'uid' => 'old-so-' . $index,
                'fprdcode' => $code,
                'fitemname' => $name,
                'units' => $unit !== '' ? [$unit] : [],
                'fsatuan' => $unit,
                'fnoacak' => trim((string) ($oldSoNoAcaks[$index] ?? '')),
                'frefdtno' => $refDtNo,
                'fnouref' => trim((string) ($oldSoNouRefs[$index] ?? '')),
                'frefpr' => $refPr,
                'fqty' => (float) ($oldSoQtys[$index] ?? 0),
                'fterima' => (float) ($oldSoTerimas[$index] ?? 0),
                'fprice' => (float) ($oldSoPrices[$index] ?? 0),
                'fdisc' => $oldSoDiscs[$index] ?? 0,
                'ftotal' => (float) ($oldSoTotals[$index] ?? 0),
                'fdesc' => (string) ($oldSoDescs[$index] ?? ''),
                'fketdt' => (string) ($oldSoKetdts[$index] ?? ''),
                'frefno_display' => $refPr !== '' ? $refPr : $refDtNo,
            ];
        }
    @endphp
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

        .sales-detail-table th,
        .sales-detail-table td {
            padding: .25rem .375rem !important;
        }

        .sales-detail-table input:not([type="hidden"]),
        .sales-detail-table select,
        .sales-detail-table button {
            min-height: 1.875rem;
            padding-top: .25rem !important;
            padding-bottom: .25rem !important;
            line-height: 1.25rem;
        }

        .sales-detail-table .rounded-l.border,
        .sales-detail-table .rounded-r.border {
            min-height: 1.875rem;
            padding-top: .25rem !important;
            padding-bottom: .25rem !important;
            line-height: 1.25rem;
        }

        .sales-detail-table button {
            display: inline-flex;
            align-items: center;
        }
    </style>
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow p-0 overflow-hidden" role="alert">
            {{-- Header Strip --}}
            <div class="d-flex align-items-center px-4 py-3" style="background-color: #c0392b;">
                <i class="bi bi-exclamation-triangle-fill text-white me-2 fs-5"></i>
                <strong class="text-white fs-6">{{ 'Data Belum Bisa Disimpan' }}</strong>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert"
                    aria-label="Close"></button>
            </div>

            {{-- Body --}}
            <div class="px-4 py-3" style="background-color: #fdeded; border-left: 5px solid #c0392b;">
                <p class="mb-2 text-danger fw-semibold">
                    <i class="bi bi-info-circle me-1"></i>
                    {{ 'Tolong perbaiki bagian ini dulu:' }}
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
        <div x-data="{ includePPN: false, ppnRate: 11, ppnAmount: 0, selected: 'alamatsurat', totalHarga: 0 }" class="lg:col-span-5">
            <div class="bg-white rounded shadow p-6 md:p-8 max-w-[96rem] mx-auto">
                {{-- Taruh di atas form --}}
                <script>
                    window._soLabels = {
                        noItemsTitle: @json(__('Tidak Ada Item')),
                        noItemsText: @json(__('Silakan tambahkan minimal 1 item terlebih dahulu.')),
                        noItemsBtn: @json(__('OK')),
                    };
                </script>

                <form id="salesOrderForm" action="{{ route('salesorder.store') }}" method="POST" class="mt-6"
                    data-form-draft="true" data-draft-key="salesorder:create" x-data="{
                        showNoItems: false,
                        handleSubmit() {
                            if (window.salesOrderItemsTable?.submitForm) {
                                window.salesOrderItemsTable.submitForm(this.$el);
                                return;
                            }
                            this.$el.submit();
                        }
                    }"
                    @submit.prevent="handleSubmit()">
                    @csrf
                    <input type="hidden" name="fneedacc" id="salesOrderNeedAcc" value="{{ old('fneedacc', '0') }}">
                    <input type="hidden" name="fuseracc" id="salesOrderUserAcc" value="{{ old('fuseracc', '') }}">

                    {{-- HEADER FORM --}}
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                        <div class="lg:col-span-4">
                            <label class="block text-sm font-bold">{{ 'Cabang' }}</label>
                            <input type="text" class="w-full border rounded px-3 py-2 bg-gray-200 cursor-not-allowed"
                                value="{{ trim(($fbranchcode ?? '') . ($fcabang ?? '' ? ' - ' . $fcabang : '')) }}" disabled>
                            <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                        </div>

                        <div class="lg:col-span-4" x-data="{ autoCode: true }">
                            <label class="block text-sm font-bold mb-1">SO#</label>
                            <div class="flex items-center gap-3">
                                <input type="text" name="fsono" class="w-full border rounded px-3 py-2"
                                    :disabled="autoCode"
                                    :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'">
                                <label class="inline-flex items-center select-none">
                                    <input type="checkbox" x-model="autoCode" checked>
                                    <span class="ml-2 text-sm text-gray-700">{{ 'Auto' }}</span>
                                </label>
                            </div>
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-bold">{{ 'Tanggal' }}</label>
                            <input type="date" name="fsodate" value="{{ old('fsodate') ?? date('Y-m-d') }}"
                                class="w-full border rounded px-3 py-2 @error('fsodate') border-red-500 @enderror">
                            @error('fsodate')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Customer --}}
                        <div class="lg:col-span-4">
                            <label class="block text-sm font-bold mb-1">{{ 'Customer' }}</label>
                            <div class="flex">
                                <div class="relative flex-1" for="modal_filter_customer_id">
                                    <select id="modal_filter_customer_id" name="filter_customer_id"
                                        class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
                                        disabled>
                                        <option value=""></option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->fcustomercode }}"
                                                data-ftempo="{{ trim((string) ($customer->ftempo ?? 0)) }}"
                                                data-fsalesman="{{ trim((string) ($customer->fsalesman ?? '')) }}"
                                                {{ $filterSupplierId == $customer->fcustomercode ? 'selected' : '' }}>
                                                {{ $customer->fcustomername }} ({{ $customer->fcustomercode }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-0" role="button" aria-label="Browse Customer"
                                        @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))"></div>
                                </div>
                                <input type="hidden" name="fcustno" id="customerCodeHidden" value="{{ old('fcustno') }}">
                                <button type="button"
                                    @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))"
                                    class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                                    title="Browse Customer">
                                    <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                </button>
                                @if (in_array('createCustomer', explode(',', session('user_restricted_permissions', '')), true))
                                    <a href="{{ route('customer.create') }}" target="_blank" rel="noopener"
                                        class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50"
                                        title="Tambah Customer">
                                        <x-heroicon-o-plus class="w-5 h-5" />
                                    </a>
                                @endif
                            </div>
                            @error('fcustno')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Salesman --}}
                        <div class="lg:col-span-4">
                            <label class="block text-sm font-bold mb-1">{{ 'Salesman' }}</label>
                            <div class="flex">
                                <div class="relative flex-1" for="modal_filter_salesman_id">
                                    <select id="modal_filter_salesman_id" name="filter_salesman_id"
                                        class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
                                        disabled>
                                        <option value=""></option>
                                        @foreach ($salesmans as $salesman)
                                            <option value="{{ $salesman->fsalesmancode }}"
                                                {{ $filterSalesmanId == $salesman->fsalesmancode ? 'selected' : '' }}>
                                                {{ $salesman->fsalesmanname }} ({{ $salesman->fsalesmancode }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-0" role="button" aria-label="Browse Salesman"
                                        @click="window.dispatchEvent(new CustomEvent('salesman-browse-open'))"></div>
                                </div>
                                <input type="hidden" name="fsalesman" id="salesmanCodeHidden"
                                    value="{{ old('fsalesman') }}">
                                <button type="button"
                                    @click="window.dispatchEvent(new CustomEvent('salesman-browse-open'))"
                                    class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                                    title="Browse Salesman">
                                    <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                </button>
                                @if (in_array('createSalesman', explode(',', session('user_restricted_permissions', '')), true))
                                    <a href="{{ route('salesman.create') }}" target="_blank" rel="noopener"
                                        class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50"
                                        title="Tambah Salesman">
                                        <x-heroicon-o-plus class="w-5 h-5" />
                                    </a>
                                @endif
                            </div>
                            @error('fsalesman')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="lg:col-span-2">
                            <label class="block text-sm font-bold mb-1">{{ 'Tempo' }}</label>
                            <div class="flex items-center">
                                <input type="number" id="ftempohr" name="ftempohr" value="{{ old('ftempohr', 0) }}"
                                    class="w-full border rounded px-3 py-2 @error('ftempohr') border-red-500 @enderror">
                                <span class="ml-2">{{ 'Hari' }}</span>
                            </div>
                            @error('ftempohr')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="lg:col-span-2">
                            <label class="block text-sm font-bold mb-1">{{ 'Ref.PO' }}</label>
                            <input type="text" name="frefpo" value="{{ old('frefpo') }}"
                                class="w-full border rounded px-3 py-2 @error('frefpo') border-red-500 @enderror"
                                placeholder="PO Customer">
                            @error('frefpo')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-12 mt-4">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">

                                <div x-data="{
                                    tab: 1,
                                    addr1: '{{ old('fkirimaddress1') }}',
                                    addr2: '{{ old('fkirimaddress2') }}',
                                    addr3: '{{ old('fkirimaddress3') }}',
                                    updateFinal() {
                                        let val = '';
                                        if (this.tab === 1) val = this.addr1;
                                        else if (this.tab === 2) val = this.addr2;
                                        else if (this.tab === 3) val = this.addr3;
                                        document.getElementById('falamatkirim_final').value = val;
                                    }
                                }" x-init="updateFinal();
                                $watch('tab', v => updateFinal());
                                $watch('addr1', v => updateFinal());"
                                    @customer-selected.window="addr1 = $event.detail.f1; addr2 = $event.detail.f2; addr3 = $event.detail.f3; tab = 1; updateFinal();"
                                    class="flex flex-col gap-2">

                                    <input type="hidden" name="falamatkirim" id="falamatkirim_final"
                                        value="{{ old('falamatkirim') }}">

                                    <div class="flex items-center gap-2">
                                        <label class="text-sm font-bold text-gray-700 mr-2">{{ 'Kirim ke' }}
                                            :</label>

                                        <div class="inline-flex rounded-md shadow-sm" role="group">
                                            <button type="button" @click="tab = 1"
                                                :class="tab === 1 ? 'bg-blue-600 text-white z-10 ring-2 ring-blue-300' :
                                                    'bg-white text-gray-700 hover:bg-gray-50'"
                                                class="px-4 py-1.5 text-xs font-semibold border border-gray-300 rounded-l-md transition-all">
                                                {{ 'Alamat 1' }}
                                            </button>
                                            <button type="button" @click="tab = 2"
                                                :class="tab === 2 ? 'bg-blue-600 text-white z-10 ring-2 ring-blue-300' :
                                                    'bg-white text-gray-700 hover:bg-gray-50'"
                                                class="px-4 py-1.5 text-xs font-semibold border-t border-b border-r border-gray-300 transition-all">
                                                {{ 'Alamat 2' }}
                                            </button>
                                            <button type="button" @click="tab = 3"
                                                :class="tab === 3 ? 'bg-blue-600 text-white z-10 ring-2 ring-blue-300' :
                                                    'bg-white text-gray-700 hover:bg-gray-50'"
                                                class="px-4 py-1.5 text-xs font-semibold border-t border-b border-r border-gray-300 rounded-r-md transition-all">
                                                {{ 'Alamat 3' }}
                                            </button>
                                        </div>
                                    </div>

                                    <div class="w-full">
                                        <textarea x-show="tab === 1" x-model="addr1"
                                            class="w-full p-2 text-sm border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 min-h-[80px]"
                                            placeholder="{{ 'Alamat 1' }}..."></textarea>

                                        <textarea x-show="tab === 2" x-model="addr2"
                                            class="w-full p-2 text-sm border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 min-h-[80px]"
                                            placeholder="{{ 'Alamat 2' }}..."></textarea>

                                        <textarea x-show="tab === 3" x-model="addr3"
                                            class="w-full p-2 text-sm border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 min-h-[80px]"
                                            placeholder="{{ 'Alamat 3' }}..."></textarea>
                                    </div>

                                    <p class="text-[10px] text-gray-500 italic">*Klik tombol Alamat 1/2/3 untuk memilih
                                        alamat yang akan digunakan.</p>
                                </div>

                                <div class="flex flex-col">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">{{ 'Keterangan' }}</label>
                                    <div
                                        class="flex-1 border-2 border-gray-200 rounded-xl p-3 bg-white min-h-[50px] focus-within:border-blue-400">
                                        <textarea name="fket" class="w-full h-full border-none focus:ring-0 p-0 text-sm resize-none"
                                            placeholder="Keterangan isi di sini...">{{ old('fket') }}</textarea>
                                    </div>
                                    @error('fket')
                                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                    @enderror
                                </div>

                            </div>
                        </div>

                        <div class="lg:col-span-12">
                            <label class="block text-sm font-bold">{{ 'Catatan Internal' }}</label>
                            <textarea name="fketinternal" rows="3"
                                class="w-full border rounded px-3 py-2 @error('fketinternal') border-red-500 @enderror"
                                placeholder="Tulis Catatan Internal tambahan di sini...">{{ old('fketinternal') }}</textarea>
                            @error('fketinternal')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div x-data="itemsTable()" x-init="init()" class="mt-6 space-y-2">

                        {{-- DETAIL ITEM (tabel input) --}}
                        <h3 class="text-base font-semibold text-gray-800">Detail Item</h3>

                        <div class="overflow-x-auto border rounded">
                            <table class="sales-detail-table min-w-full text-sm balanced-detail-table" data-skip-auto-detail-style="true">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="p-2 text-left w-10">#</th>
                                        <th class="p-2 text-left w-52">Kode Produk</th>
                                        <th class="p-2 text-left w-[28rem]">Nama Produk</th>
                                        <th class="p-2 text-left w-30">Satuan</th>
                                        <th class="p-2 text-right w-28 whitespace-nowrap">Jumlah</th>
                                        <th class="p-2 text-right w-28 whitespace-nowrap">@ Harga</th>
                                        <th class="p-2 text-right w-28 whitespace-nowrap">Disc. %</th>
                                        <th class="p-2 text-right w-34 whitespace-nowrap">Total Harga</th>
                                        <th class="p-2 text-center w-24">Aksi</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <template x-for="(row, i) in rows" :key="row.uid">
                                        <!-- ROW UTAMA -->
                                        <tr class="border-t align-top">
                                            <td class="p-2" x-text="i + 1"></td>
                                            <td class="p-2">
                                                <div class="flex">
                                                    <input type="text"
                                                        class="flex-1 border rounded-l px-2 py-1 font-mono"
                                                        x-model.trim="row.fprdcode" @input="onCodeTypedRow(row, i)"
                                                        @keydown.enter.prevent="focusRowUnit(row, i)">
                                                    <button type="button" @click="openBrowseFor(i)"
                                                        class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50"
                                                        title="Cari Produk">
                                                        <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="p-2">
                                                <div class="flex w-full max-w-full">
                                                    <div class="min-w-0 flex-1 rounded-l border bg-gray-100 px-2 py-1 text-sm leading-5 text-gray-600 whitespace-normal break-words"
                                                        x-text="row.fitemname"></div>
                                                    <button type="button" @click="openDesc(row)"
                                                        class="shrink-0 inline-flex items-center border border-l-0 rounded-r bg-slate-50 px-2 py-1 text-slate-700 hover:bg-slate-100"
                                                        title="Deskripsi">
                                                        <x-heroicon-o-document-text class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="p-2">
                                                <template x-if="row.units && row.units.length > 1">
                                                    <select class="w-full border rounded px-2 py-1 text-xs"
                                                        :id="'unit_row_' + i" x-model="row.fsatuan"
                                                        @change="onRowUpdated(i)" @keydown.enter.prevent="focusRowQty(i)">
                                                        <template x-for="u in row.units" :key="u">
                                                            <option :value="u" x-text="u"
                                                                :selected="u === row.fsatuan"></option>
                                                        </template>
                                                    </select>
                                                </template>
                                                <template x-if="!row.units || row.units.length <= 1">
                                                    <input type="text"
                                                        class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-xs"
                                                        :value="row.fsatuan || '-'" disabled>
                                                </template>
                                            </td>
                                            <td class="p-2 text-right">
                                                <input type="number" class="w-full border rounded px-2 py-1 text-right"
                                                    :id="'qty_row_' + i" x-model.number="row.fqty" min="0"
                                                    @input="onRowUpdated(i)" @keydown.enter.prevent="focusRowPrice(i)">
                                            </td>
                                            <td class="p-2 text-right">
                                                <input type="text" inputmode="decimal"
                                                    class="w-full border rounded px-2 py-1 text-right"
                                                    :id="'price_row_' + i" x-model="row.fpriceInput"
                                                    @focus="activeRow = row.uid; focusPriceInput(row); $event.target.select()"
                                                    @blur="activeRow = null; blurPriceInput(row)"
                                                    @input="onPriceInput(row)" @keydown.enter.prevent="focusRowDisc(i)">
                                            </td>
                                            <td class="p-2 text-right">
                                                <input type="text" class="w-full border rounded px-2 py-1 text-right"
                                                    :id="'disc_row_' + i" :value="normalizeDiscountValue(row.fdisc)"
                                                    @focus="activeRow = row.uid; $event.target.select()"
                                                    @blur="activeRow = null; normalizeDiscountInput($event, row)"
                                                    @input="row.fdisc = $event.target.value; recalc(row)"
                                                    @keydown.enter.prevent="$event.target.blur()">
                                            </td>
                                            <td class="p-2">
                                                <input type="text"
                                                    class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm text-right"
                                                    :value="fmt(row.ftotal)" disabled>
                                            </td>
                                            <td class="p-2 text-center">
                                                <div class="flex items-center justify-center gap-2 flex-wrap">
                                                    <button type="button" @click="removeRow(i)"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded bg-red-100 text-red-600 hover:bg-red-200"
                                                        title="Hapus baris">-</button>
                                                </div>
                                            </td>
                                        </tr>

                                    </template>

                                </tbody>
                            </table>
                        </div>

                        <div class="hidden">
                            <template x-for="row in rowsToSubmit" :key="'submit-' + row.uid">
                                <div>
                                    <input type="hidden" name="fprdcode[]" :value="row.fprdcode">
                                    <input type="hidden" name="fitemname[]" :value="row.fitemname">
                                    <input type="hidden" name="fsatuan[]" :value="row.fsatuan">
                                    <input type="hidden" name="fnoacak[]" :value="row.fnoacak">
                                    <input type="hidden" name="fqty[]" :value="row.fqty">
                                    <input type="hidden" name="fprice[]" :value="row.fprice">
                                    <input type="hidden" name="fdisc[]" :value="row.fdisc">
                                    <input type="hidden" name="ftotal[]" :value="row.ftotal">
                                    <input type="hidden" name="fdesc[]" :value="row.fdesc">
                                </div>
                            </template>
                        </div>

                        <!-- Kanan: Panel Totals -->
                        <div class="mt-3 flex justify-end">
                            <div class="w-[560px] shrink-0">
                                <div class="rounded-lg border bg-gray-50 p-4 space-y-3 text-sm">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-gray-800">Total Harga</span>
                                        <span class="font-bold text-gray-900"
                                            x-text="formatTransactionAmount(totalHarga)"></span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-gray-800">Discount</span>
                                        <input type="number" min="0" max="100" step="0.01"
                                            name="fdiscpersen" x-model.number="headerDiscPercent"
                                            class="w-16 h-9 px-2 text-sm leading-tight text-right border rounded transition-opacity
                                                [appearance:textfield]
                                                [&::-webkit-outer-spin-button]:appearance-none
                                                [&::-webkit-inner-spin-button]:appearance-none">
                                        <span class="text-gray-500">%</span>
                                        <span class="flex-1"></span>
                                        <span class="font-medium text-right"
                                            x-text="rupiah(headerDiscAmount)"></span>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-gray-800">Total Setelah Disc</span>
                                        <span class="font-medium text-gray-900"
                                            x-text="rupiah(totalSetelahDisc)"></span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <!-- Checkbox -->
                                        <label class="flex items-center gap-1.5 cursor-pointer select-none">
                                            <input id="fapplyppn" name="fapplyppn" type="checkbox" value="1"
                                                x-model="includePPN"
                                                class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                            <span class="font-bold">PPN</span>
                                        </label>

                                        <!-- Dropdown Include / Exclude -->
                                        <select id="ppnMode" name="fincludeppn" x-model.number="ppnMode"
                                            :disabled="!includePPN"
                                            class="w-28 h-9 px-2 text-sm leading-tight border rounded transition-opacity appearance-none
                                                   disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed">
                                            <option value="0">Exclude</option>
                                            <option value="1">Include</option>
                                        </select>

                                        <!-- Input Rate + Nominal -->
                                        <input type="number" min="0" max="100" name="ppn_rate"
                                            step="0.01" x-model.number="ppnRate" :disabled="!includePPN"
                                            class="w-16 h-9 px-2 text-sm leading-tight text-right border rounded transition-opacity
                                                    [appearance:textfield]
                                                    [&::-webkit-outer-spin-button]:appearance-none
                                                    [&::-webkit-inner-spin-button]:appearance-none
                                                    disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed">
                                        <span class="text-gray-500">%</span>
                                        <span class="flex-1"></span>
                                        <span class="font-medium"
                                            x-text="rupiah(ppnAmount)"></span>
                                    </div>

                                    <div class="border-t my-1"></div>

                                    <div class="flex items-center justify-between text-base">
                                        <span class="font-extrabold text-gray-900">Grand Total</span>
                                        <span class="font-extrabold text-blue-700 text-lg"
                                            x-text="rupiah(grandTotal)"></span>
                                    </div>
                                </div>

                                <!-- Hidden inputs for submit -->
                                <input type="hidden" name="famountgross" :value="totalHarga">
                                <input type="hidden" name="fdiscount" :value="headerDiscAmount">
                                <input type="hidden" name="famountpajak" :value="ppnAmount">
                                <input type="hidden" name="famountso" :value="grandTotal">
                                <input type="hidden" name="famountsonet" :value="totalDPP">
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
                                            <button x-show="!descReadonly" type="button" @click="copyDescName()"
                                                class="h-8 px-3 rounded-lg bg-blue-50 text-blue-700 text-sm font-medium hover:bg-blue-100">
                                                Copy
                                            </button>
                                        </div>
                                        <div class="rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800"
                                            x-text="descItemName || '-'"></div>
                                    </div>
                                    <label class="block text-sm text-gray-700">Deskripsi</label>
                                    <textarea x-model="descValue" rows="5" class="w-full border rounded px-3 py-2" :readonly="descReadonly"
                                        placeholder="Tulis deskripsi item di sini..."></textarea>
                                </div>

                                <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
                                    <button type="button" @click="closeDesc()"
                                        class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">
                                        Batal
                                    </button>
                                    <button x-show="!descReadonly" type="button" @click="applyDesc()"
                                        class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
                                        Simpan
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div x-show="showWarningModal" x-cloak
                            class="fixed inset-0 z-[96] flex items-center justify-center" x-transition.opacity>
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
                                        class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">
                                        Tutup
                                    </button>
                                    <button type="button" x-show="warningCanProceed" @click="confirmWarningAndSubmit()"
                                        class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
                                        Lanjut Simpan
                                    </button>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="itemsCount" :value="rowsToSubmit.length">
                    </div>

                    <x-transaction.browse-customer-modal />

                    <x-transaction.browse-salesman-modal />

                    <x-transaction.browse-product-modal show-controls="true" show-pagination="true" />

                    @php
                        $canApproval = in_array(
                            'approveSalesOrder',
                            explode(',', session('user_restricted_permissions', '')),
                        );
                    @endphp

                    <div class="mt-8 flex justify-center gap-4">
                        <button type="submit"
                            class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                            <x-heroicon-o-check class="w-5 h-5 mr-2" /> Simpan
                        </button>
                        <button type="button" @click="window.location.href='{{ route('salesorder.index') }}'"
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
<x-transaction.datatables-length-styles :tables="['productTable', 'supplierTable']" />
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
<script>
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
            showNoItems: false,
            rows: [],
            rowsToSubmit: [],
            minimumVisibleRows: 5,

            totalHarga: 0,
            headerDiscPercent: @json((float) old('fdiscpersen', 0)),
            ppnRate: 11,

            initialGrandTotal: @json($famountso ?? 0),
            initialPpnAmount: @json($famountpopajak ?? 0),

            includePPN: false,
            ppnMode: 0, // 0: Exclude, 1: Include
            ppnRate: 11,
            showWarningModal: false,
            warningTitle: 'Perhatian',
            warningMessage: '',
            warningItems: [],
            warningCanProceed: false,
            pendingSubmitForm: null,
            pendingRowsToSubmit: [],

            get headerDiscAmount() {
                const total = +this.totalHarga || 0;
                const percent = Math.min(100, Math.max(0, +this.headerDiscPercent || 0));
                return +(total * (percent / 100)).toFixed(2);
            },

            get totalSetelahDisc() {
                const total = +this.totalHarga || 0;
                return +(total - this.headerDiscAmount).toFixed(2);
            },

            get ppnBaseAmount() {
                return this.totalSetelahDisc;
            },

            get totalDPP() {
                const total = this.ppnBaseAmount;
                if (!this.includePPN) return +total.toFixed(2);
                const rate = +this.ppnRate || 0;
                if (this.ppnMode === 1) { // Include
                    return +((100 / (100 + rate)) * total).toFixed(2);
                }
                return +total.toFixed(2); // Exclude
            },

            get ppnAmount() {
                if (!this.includePPN) return 0;
                const dpp = this.totalDPP;
                const rate = +this.ppnRate || 0;
                return +((dpp * rate) / 100).toFixed(2);
            },

            get grandTotal() {
                const total = this.ppnBaseAmount;
                if (!this.includePPN) return +total.toFixed(2);
                if (this.ppnMode === 1) return +total.toFixed(2); // Include: total already has PPN
                return +(total + this.ppnAmount).toFixed(2); // Exclude: total + PPN
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
                row.fprice = Math.max(0, +row.fprice || 0);
                if (typeof row.fpriceInput === 'undefined') {
                    row.fpriceInput = this.fmt(row.fprice);
                }

                // Parse discount menggunakan fungsi baru
                const discPercent = this.parseDiscount(row.fdisc);

                // Hitung total
                const subtotal = row.fqty * row.fprice;
                const discAmount = subtotal * (discPercent / 100);
                row.ftotal = +(subtotal - discAmount).toFixed(2);

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

            focusPriceInput(row) {
                const price = Math.max(0, +row.fprice || 0);
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

            recalcTotals() {
                this.totalHarga = this.rows.reduce((sum, item) => {
                    if (!this.isRowSavable(item)) return sum;
                    return sum + item.ftotal;
                }, 0);
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

            formatStockLimit(code, qty, satuan) {
                return '';
            },

            hydrateRowFromMeta(row, meta, forceDefaultUnit = false) {
                if (!meta) {
                    row.fitemname = '';
                    row.units = [];
                    row.fsatuan = '';
                    return;
                }
                row.fitemname = meta.name || '';
                const units = [...new Set((meta.units || []).map(u => (u ?? '').toString().trim()).filter(Boolean))];
                const defaultUnit = (meta.default_unit || '').toString().trim();
                const resolvedDefaultUnit = defaultUnit && units.includes(defaultUnit) ?
                    defaultUnit :
                    (units[0] || '');
                row.units = units;
                row.fsatuan = forceDefaultUnit ?
                    resolvedDefaultUnit :
                    (units.includes(row.fsatuan) ? row.fsatuan : resolvedDefaultUnit);
                if (meta.unit_ratios) row.unit_ratios = meta.unit_ratios;
            },

            rowHasContent(row) {
                if (!row) return false;
                return this.isRowFilled(row);
            },

            ensureMinimumRows() {
                while (this.rows.length < this.minimumVisibleRows) {
                    this.rows.push(this.createRow());
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
                    this.rows.push(this.createRow());
                }
            },

            onRowUpdated(index = null) {
                const row = typeof index === 'number' ? this.rows[index] : null;
                if (row) {
                    this.recalc(row);
                }
                this.recalcTotals();
                this.ensureTrailingRow(index);
            },

            onCodeTypedRow(row, index = null) {
                this.hydrateRowFromMeta(row, this.productMeta(row.fprdcode), true);
                row.fnoacak = this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak(row.uid);
                this.onRowUpdated(index);
            },

            isRowSavable(row) {
                return row.fprdcode && row.fitemname && row.fsatuan && Number(row.fqty) > 0;
            },
            isRowFilled(row) {
                return [
                        row.fprdcode,
                        row.fitemname,
                        row.fsatuan,
                        row.fqty,
                        row.fprice,
                        row.fdisc,
                        row.fdesc,
                        row.fketdt
                    ].some((value) => String(value ?? '').trim() !== '' && Number(value ?? 0) !== 0) ||
                    Number(row.fqty || 0) > 0;
            },

            onPrPicked(e) {
                const {
                    header,
                    items
                } = e.detail || {};
                if (!items || !Array.isArray(items)) return;
                this.addManyFromPR(header, items);
            },

            normalizeNoAcak(value) {
                const normalized = String(value ?? '').trim();
                return /^\d{3}$/.test(normalized) ? normalized : '';
            },

            generateUniqueNoAcak(exceptUid = null) {
                const used = new Set(this.rows
                    .filter(item => item.uid !== exceptUid)
                    .map(item => this.normalizeNoAcak(item.fnoacak)).filter(Boolean));
                let candidate = '';
                do {
                    candidate = Array.from({
                        length: 3
                    }, () => '123456789' [Math.floor(Math.random() * 9)]).join('');
                } while (used.has(candidate));

                return candidate;
            },

            addManyFromPR(header, items) {
                const existing = new Set(this.getCurrentItemKeys());
                const incomingRows = [];

                items.forEach(src => {
                    const row = {
                        uid: cryptoRandom(),
                        fprdcode: src.fitemcode ?? '',
                        fitemname: src.fitemname ?? '',
                        fsatuan: src.fsatuan ?? '',
                        fnoacak: this.generateUniqueNoAcak(),
                        frefdtno: src.frefdtno ?? '',
                        fnouref: src.fnouref ?? '',
                        frefpr: src.frefpr ?? (header?.fpono ?? header?.fsono ?? ''),
                        fprhid: src.fprhid ?? header?.fprhid ?? header?.fpohid ?? '',
                        fqty: (src.fqtyremain_dokumen !== null && src.fqtyremain_dokumen !== undefined && Number(src.fqtyremain_dokumen) > 0) ?
                            Number(src.fqtyremain_dokumen) : ((src.fqtysisa !== null && src.fqtysisa !== undefined && Number(src.fqtysisa) > 0) ?
                                Number(src.fqtysisa) : ((src.fqtyremain !== null && src.fqtyremain !== undefined &&
                                    Number(src.fqtyremain) > 0) ? Number(src.fqtyremain) : ((src.fqty !== null &&
                                        src.fqty !== undefined && Number(src.fqty) > 0) ? Number(src.fqty) : 1))),
                        fterima: Number(src.fterima ?? 0),
                        fprice: Number(src.fprice ?? 0),
                        fdisc: src.fdisc ?? 0,
                        ftotal: Number(src.ftotal ?? 0),
                        fdesc: src.fdesc ?? '',
                        fketdt: src.fketdt ?? '',
                        hideQtyLimitHint: false,
                        units: Array.isArray(src.units) && src.units.length ? src.units : [src.fsatuan]
                            .filter(Boolean),
                    };

                    const key = this.itemKey({
                        fprdcode: row.fprdcode,
                        frefdtno: row.frefdtno
                    });

                    if (!(Number(row.fqty) > 0)) return;
                    if (existing.has(key)) return;

                    this.recalc(row);
                    incomingRows.push(row);
                    existing.add(key);
                });

                if (incomingRows.length > 0) {
                    const shouldReplaceStarter = this.rows.length === 1 && !this.isRowFilled(this.rows[0]);
                    if (shouldReplaceStarter) {
                        this.rows = incomingRows;
                    } else {
                        this.rows.push(...incomingRows);
                    }
                }
                this.recalcTotals();
                this.ensureMinimumRows();
                this.ensureTrailingRow();
            },
            removeRow(i) {
                if (this.rows.length === 1) {
                    this.rows.splice(0, 1, this.createRow());
                    this.recalcTotals();
                    return;
                }
                this.rows.splice(i, 1);
                this.ensureMinimumRows();
                this.recalcTotals();
            },

            focusRowUnit(row, i) {
                if (row.units.length > 1) this.$nextTick(() => document.getElementById('unit_row_' + i)?.focus());
                else this.focusRowQty(i);
            },
            focusRowQty(i) {
                this.$nextTick(() => document.getElementById('qty_row_' + i)?.focus());
            },
            focusRowPrice(i) {
                this.$nextTick(() => document.getElementById('price_row_' + i)?.focus());
            },
            focusRowDisc(i) {
                this.$nextTick(() => document.getElementById('disc_row_' + i)?.focus());
            },

            showDescModal: false,
            descValue: '',
            descItemName: '',
            _descTarget: null,
            descReadonly: false,
            openDesc(targetRow, readonly = false) {
                this._descTarget = targetRow;
                this.descItemName = targetRow?.fitemname || '';
                this.descReadonly = readonly;
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
                this.descReadonly = false;
            },
            applyDesc() {
                if (this._descTarget) {
                    this._descTarget.fdesc = this.descValue;
                    const index = this.rows.findIndex((row) => row.uid === this._descTarget.uid);
                    this.onRowUpdated(index >= 0 ? index : null);
                }
                this.closeDesc();
            },
            closeWarning() {
                this.showWarningModal = false;
                this.warningTitle = 'Perhatian';
                this.warningMessage = '';
                this.warningItems = [];
                this.warningCanProceed = false;
                this.pendingSubmitForm = null;
                this.pendingRowsToSubmit = [];
            },
            confirmWarningAndSubmit() {
                if (!this.warningCanProceed || !this.pendingSubmitForm || this.pendingRowsToSubmit.length < 1) {
                    this.closeWarning();
                    return;
                }
                this.rowsToSubmit = this.pendingRowsToSubmit;
                const form = this.pendingSubmitForm;
                this.closeWarning();
                this.$nextTick(() => {
                    window.salesOrderDuplicateRefPoGuard(form).then(ok => {
                        if (!ok) return;
                        window.salesOrderCreditApprovalGuard(form).then(approved => {
                            if (approved) form.submit();
                        });
                    });
                });
            },

            itemKey(it) {
                return `${(it.fprdcode ?? '').toString().trim()}::${(it.frefdtno ?? '').toString().trim()}`;
            },

            getCurrentItemKeys() {
                return this.rows.map(it => this.itemKey(it));
            },

            normalizeRestoredRow(item, index = 0) {
                const row = {
                    ...newRow(),
                    ...(item || {}),
                    uid: item?.uid || `restored-${index}`
                };
                row.fnoacak = this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak();
                row.hideQtyLimitHint = !((row.frefdtno ?? '').toString().trim());

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

                const meta = this.productMeta(row.fprdcode);
                if (meta?.units?.length) {
                    row.units = [...new Set([...row.units, ...meta.units])];
                } else if (row.fsatuan && !row.units.includes(row.fsatuan)) {
                    row.units.unshift(row.fsatuan);
                }

                if (meta?.unit_ratios) {
                    row.unit_ratios = row.unit_ratios || meta.unit_ratios;
                }

                this.recalc(row);
                return row;
            },

            restoreRows(items = []) {
                this.rows = Array.isArray(items) ?
                    items.map((item, index) => this.normalizeRestoredRow(item, index)) : [];
                if (this.rows.length === 0) {
                    this.rows = [this.createRow()];
                }
                this.ensureMinimumRows();
                this.ensureTrailingRow();
                this.recalcTotals();
            },
            createRow(source = {}) {
                const row = this.normalizeRestoredRow({
                    ...newRow(),
                    ...source,
                    fnoacak: source.fnoacak || this.generateUniqueNoAcak(source.uid || null),
                }, source.uid || cryptoRandom());
                row.fpriceInput = this.fmt(row.fprice);
                return row;
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

            init() {
                window.salesOrderItemsTable = this;
                this.$watch('includePPN', () => this.recalcTotals());
                this.$watch('ppnMode', () => this.recalcTotals());
                this.$watch('ppnRate', () => this.recalcTotals());
                this.$watch('headerDiscPercent', (value) => {
                    const normalized = Math.min(100, Math.max(0, Number(value) || 0));
                    if (normalized !== Number(value)) {
                        this.headerDiscPercent = normalized;
                        return;
                    }
                    this.recalcTotals();
                });

                window.getCurrentItemKeys = () => this.getCurrentItemKeys();
                window.addEventListener('pr-picked', this.onPrPicked.bind(this), {
                    passive: true
                });

                window.addEventListener('product-chosen', (e) => {
                    const {
                        product
                    } = e.detail || {};
                    if (!product) return;
                    if (typeof this.browseTarget !== 'number') return;

                    const i = this.browseTarget;
                    let row = this.rows[i];
                    if (!row) return;

                    row.fprdcode = (product.fprdcode || '').toString();
                    this.hydrateRowFromMeta(row, this.productMeta(row.fprdcode), true);

                    this.rows.splice(i, 1, {
                        ...this.rows[i]
                    });

                    row = this.rows[i];

                    row.fnoacak = this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak(row.uid);
                    if (!row.fqty) row.fqty = 1;
                    this.onRowUpdated(i);
                    this.$nextTick(() => document.getElementById('qty_row_' + i)?.focus());
                }, {
                    passive: true
                });

                this.restoreRows(@json($initialSalesOrderItems));
            },

            browseTarget: null,
            openBrowseFor(index) {
                this.browseTarget = index;
                window.dispatchEvent(new CustomEvent('browse-open', {
                    detail: {
                        forEdit: false
                    }
                }));
            },
            rowWarningLabel(row) {
                return `Data Produk ${row.fitemname || row.fprdcode || '(tanpa nama)'} qty masih 0, tidak akan tersimpan.`;
            },
            submitForm(form) {
                const validRows = this.rows.filter((row) => this.isRowSavable(row));
                const warningRows = this.rows.filter((row) => this.isRowFilled(row) && !this.isRowSavable(row));
                const seenCodes = new Set();

                for (const row of validRows) {
                    const code = (row.fprdcode || '').toString().trim().toUpperCase();
                    if (!code) continue;
                    if (seenCodes.has(code)) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Produk Duplikat',
                            text: `Kode produk ${code} tidak boleh sama dalam satu Sales Order.`,
                            confirmButtonText: 'OK',
                            customClass: {
                                confirmButton: 'bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700'
                            }
                        });
                        return;
                    }
                    seenCodes.add(code);
                }

                if (warningRows.length > 0) {
                    this.warningTitle = 'Qty Belum Diisi';
                    this.warningMessage = validRows.length > 0 ?
                        'Beberapa item tidak akan disimpan karena qty masih 0.' :
                        'Tidak ada item yang bisa disimpan karena qty masih 0 atau data belum lengkap.';
                    this.warningItems = warningRows.map((row) => this.rowWarningLabel(row));
                    this.warningCanProceed = validRows.length > 0;
                    this.pendingSubmitForm = form;
                    this.pendingRowsToSubmit = validRows;
                    this.showWarningModal = true;
                    return;
                }

                if (validRows.length === 0) {
                    this.showNoItems = true;
                    Swal.fire({
                        icon: 'warning',
                        title: window._soLabels.noItemsTitle,
                        text: window._soLabels.noItemsText,
                        confirmButtonText: window._soLabels.noItemsBtn,
                        customClass: {
                            confirmButton: 'bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700'
                        }
                    });
                    return;
                }

                this.rowsToSubmit = validRows;
                this.$nextTick(() => {
                    window.salesOrderDuplicateRefPoGuard(form).then(ok => {
                        if (!ok) return;
                        window.salesOrderCreditApprovalGuard(form).then(approved => {
                            if (approved) form.submit();
                        });
                    });
                });
            },
        };

        function newRow() {
            return {
                uid: null,
                fprdcode: '',
                fitemname: '',
                units: [],
                fsatuan: '',
                fnoacak: '',
                frefdtno: '',
                fnouref: '',
                frefpr: '',
                fqty: 0,
                fterima: 0,
                fprice: 0,
                fdisc: 0, // Bisa berupa string "10+2" atau angka 12
                ftotal: 0,
                fdesc: '',
                fketdt: '',
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
    window.salesOrderDuplicateRefPoGuard = async function(form) {
        const customerCode = form.querySelector('[name="fcustno"]')?.value?.trim() || '';
        const refPo = form.querySelector('[name="frefpo"]')?.value?.trim() || '';
        const exceptId = form.getAttribute('data-salesorder-id') || '';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        if (!customerCode || !refPo) {
            return true;
        }

        try {
            const response = await fetch('{{ route('salesorder.duplicate-refpo-check') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    fcustno: customerCode,
                    frefpo: refPo,
                    except_id: exceptId || null
                })
            });

            const payload = await response.json();
            if (!response.ok) {
                const message = payload?.message || Object.values(payload?.errors || {}).flat().join('\n') ||
                    @json('Gagal memeriksa Reff PO.');
                await window.showAppErrorAlert(@json('Cek Reff PO Gagal'), message, {
                    html: `<div class="text-left whitespace-pre-line">${message}</div>`,
                    text: undefined
                });
                return false;
            }

            if (!payload?.exists) {
                return true;
            }

            const record = payload.record || {};
            const label = [record.fsono, record.fsodate].filter(Boolean).join(' / ');
            const result = await Swal.fire({
                icon: 'warning',
                title: @json('Reff PO Sudah Ada'),
                html: `
                    <div class="text-left text-sm">
                        <div>${@json('Customer dan Reff PO ini sudah ada.')}</div>
                        ${label ? `<div class="mt-2"><strong>${label}</strong></div>` : ''}
                        <div class="mt-3">${@json('Apakah anda ingin melanjutkan penyimpanan?')}</div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: @json('Ok'),
                cancelButtonText: @json('No')
            });

            return !!result.isConfirmed;
        } catch (error) {
            await window.showAppErrorAlert(
                @json('Cek Reff PO Gagal'),
                @json("Gagal memeriksa duplikasi customer dan Reff PO.\nSilakan coba lagi."), {
                    html: `<div class="text-left whitespace-pre-line">@json("Gagal memeriksa duplikasi customer dan Reff PO.\nSilakan coba lagi.")</div>`,
                    text: undefined
                }
            );
            return false;
        }
    };

    window.salesOrderCreditApprovalGuard = async function(form) {
        const customerCode = form.querySelector('[name="fcustno"]')?.value?.trim() || '';
        const amountValue = parseFloat(form.querySelector('[name="famountso"]')?.value || '0') || 0;
        const needAccInput = form.querySelector('#salesOrderNeedAcc');
        const userAccInput = form.querySelector('#salesOrderUserAcc');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        if (needAccInput) needAccInput.value = '0';
        if (userAccInput) userAccInput.value = '';

        if (!customerCode) {
            return true;
        }

        try {
            const response = await fetch('{{ route('salesorder.credit-check') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    fcustno: customerCode,
                    famountso: amountValue
                })
            });

            const payload = await response.json();
            if (!response.ok) {
                const message = payload?.message || Object.values(payload?.errors || {}).flat().join('\n') ||
                    @json('Gagal cek limit customer.');
                await window.showAppErrorAlert(@json('Cek Customer Gagal'), message, {
                    html: `<div class="text-left whitespace-pre-line">${message}</div>`,
                    text: undefined
                });
                return false;
            }

            const checks = payload.checks || {};
            const limitCheck = checks.limit_check || {};
            const overdueCheck = checks.overdue_check || {};
            const canApprove = !!payload.can_approve;
            const currentUser = payload.current_user || '';

            if (limitCheck.enabled && limitCheck.exceeded) {
                const confirmed = await Swal.fire({
                    icon: 'warning',
                    title: @json('Limit Piutang Terlampaui'),
                    html: `
                        <div class="text-left text-sm">
                            <div>${@json('Total piutang berjalan')}: <strong>${Number(limitCheck.outstanding_total || 0).toLocaleString('id-ID')}</strong></div>
                            <div>${@json('Nilai transaksi ini')}: <strong>${Number(limitCheck.transaction_amount || 0).toLocaleString('id-ID')}</strong></div>
                            <div>${@json('Limit customer')}: <strong>${Number(limitCheck.limit || 0).toLocaleString('id-ID')}</strong></div>
                            <div>${@json('Total setelah transaksi')}: <strong>${Number(limitCheck.projected_total || 0).toLocaleString('id-ID')}</strong></div>
                            <div class="mt-3">${@json('Sales Order ini membutuhkan persetujuan kredit. Lanjutkan?')}</div>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: @json('Ya'),
                    cancelButtonText: @json('Tidak')
                });

                if (!confirmed.isConfirmed) {
                    if (userAccInput) userAccInput.value = '';
                    return false;
                }

                if (!canApprove) {
                    await window.showAppErrorAlert(@json('Persetujuan Kredit Ditolak'), '', {
                        html: `
                            <div class="text-left text-sm">
                                <div class="font-medium mb-2">Persetujuan diperlukan:</div>
                                <ul class="list-disc pl-5 space-y-1">
                                    <li>Limit piutang customer sudah terlampaui.</li>
                                    <li>Ada nota yang lewat jatuh tempo.</li>
                                </ul>
                                <div class="mt-3">User login ini tidak punya wewenang menyetujui.</div>
                            </div>
                        `,
                        text: undefined
                    });
                    return false;
                }

                if (needAccInput) needAccInput.value = '1';
                if (userAccInput) userAccInput.value = currentUser;
                return true;
            }

            if (overdueCheck.enabled && overdueCheck.has_overdue) {
                const overdueHtml = (overdueCheck.items || []).slice(0, 5).map((item) => `
                    <li>${item.fsono} - JT ${item.fjatuhtempo ?? '-'} - Sisa ${Number(item.famountremain || 0).toLocaleString('id-ID')}</li>
                `).join('');

                const confirmed = await Swal.fire({
                    icon: 'warning',
                    title: @json('Ada Nota Lewat Jatuh Tempo'),
                    html: `
                        <div class="text-left text-sm">
                            <div>${@json('Customer punya nota yang lewat jatuh tempo lebih dari')} <strong>${overdueCheck.max_tempo || 0}</strong> ${@json('hari.')}</div>
                            <ul class="mt-3 list-disc pl-5">${overdueHtml}</ul>
                            <div class="mt-3">${@json('Sales Order ini membutuhkan persetujuan kredit. Lanjutkan?')}</div>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: @json('Ya'),
                    cancelButtonText: @json('Tidak')
                });

                if (!confirmed.isConfirmed) {
                    if (userAccInput) userAccInput.value = '';
                    return false;
                }

                if (!canApprove) {
                    await window.showAppErrorAlert(@json('Persetujuan Kredit Ditolak'), '', {
                        html: `
                            <div class="text-left text-sm">
                                <div class="font-medium mb-2">Persetujuan diperlukan:</div>
                                <ul class="list-disc pl-5 space-y-1">
                                    <li>Customer punya nota lewat jatuh tempo.</li>
                                </ul>
                                <div class="mt-3">User login ini tidak punya wewenang menyetujui.</div>
                            </div>
                        `,
                        text: undefined
                    });
                    return false;
                }

                if (needAccInput) needAccInput.value = '1';
                if (userAccInput) userAccInput.value = currentUser;
            }

            return true;
        } catch (error) {
            await window.showAppErrorAlert(
                @json('Pemeriksaan Persetujuan Gagal'),
                @json("Gagal memeriksa persetujuan customer.\nSilakan coba lagi."), {
                    html: `<div class="text-left whitespace-pre-line">@json("Gagal memeriksa persetujuan customer.\nSilakan coba lagi.")</div>`,
                    text: undefined
                }
            );
            return false;
        }
    };

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

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    @include('components.transaction.browse-customer-script')
    @include('components.transaction.browse-salesman-script')
    @include('components.transaction.browse-product-script', [
        'showControls' => true,
        'showPagination' => true,
        'supportsForEdit' => true,
    ])

    <script>
        window.PRODUCT_MAP = @json($productMap ?? []);
    </script>
    <script>
        (() => {
            function syncSalesOrderCustomerDefaults(detail = {}) {
                const customerSelect = document.getElementById('modal_filter_customer_id');
                const customerHidden = document.getElementById('customerCodeHidden');
                const tempoInput = document.getElementById('ftempohr');
                const salesmanSelect = document.getElementById('modal_filter_salesman_id');
                const salesmanHidden = document.getElementById('salesmanCodeHidden');

                if (!customerSelect || !tempoInput || !salesmanSelect || !salesmanHidden) {
                    return;
                }

                const normalize = (value) => String(value ?? '').trim();
                const normalizeCode = (value) => normalize(value).toUpperCase();
                const customerCode = normalize(detail.fcustomercode) || normalize(customerHidden.value) || normalize(
                    customerSelect.value);
                const selectedOption = [...customerSelect.options].find((option) => normalizeCode(option.value) ===
                    normalizeCode(customerCode));

                if (customerCode !== '' && selectedOption) {
                    customerSelect.value = customerCode;
                }

                const tempoValue = normalize(detail.ftempo ?? selectedOption?.dataset?.ftempo ?? '');
                const salesmanCode = normalize(detail.fsalesman ?? selectedOption?.dataset?.fsalesman ?? '');

                if (tempoValue !== '') {
                    tempoInput.value = tempoValue;
                }

                if (salesmanCode !== '') {
                    let salesmanOption = [...salesmanSelect.options].find((option) => normalizeCode(option.value) ===
                        normalizeCode(salesmanCode));
                    if (!salesmanOption) {
                        salesmanSelect.value = "";
                        salesmanHidden.value = "";
                    } else {
                        salesmanOption.selected = true;
                        salesmanSelect.value = salesmanOption.value;
                        salesmanHidden.value = salesmanCode;
                    }
                    salesmanSelect.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                } else {
                    salesmanSelect.value = "";
                    salesmanHidden.value = "";
                    salesmanSelect.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                }
            }

            document.addEventListener('DOMContentLoaded', () => {
                syncSalesOrderCustomerDefaults();

                const customerSelect = document.getElementById('modal_filter_customer_id');
                customerSelect?.addEventListener('change', () => syncSalesOrderCustomerDefaults());
            });
            window.addEventListener('customer-selected', (event) => syncSalesOrderCustomerDefaults(event.detail || {}));
        })();
    </script>
@endpush
