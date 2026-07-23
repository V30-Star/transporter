@extends('layouts.app')

@section('title', 'Faktur Penjualan - New')

@section('content')
    @php
        $oldInvoiceItemCodes = old('fitemcode', []);
        $oldInvoiceItemNames = old('fitemname', []);
        $oldInvoiceUnits = old('fsatuan', []);
        $oldInvoiceRefCodes = old('frefcode', []);
        $oldInvoiceRefNos = old('frefdtno', []);
        $oldInvoiceNouRefs = old('fnouref', []);
        $oldInvoiceRefPrs = old('frefpr', []);
        $oldInvoiceRefSos = old('frefso', []);
        $oldInvoiceRefSrjs = old('frefsrj', []);
        $oldInvoiceNoAcaks = old('fnoacak', []);
        $oldInvoiceRefNoAcaks = old('frefnoacak', []);
        $oldInvoiceQtys = old('fqty', []);
        $oldInvoiceTerimas = old('fterima', []);
        $oldInvoicePrices = old('fprice', []);
        $oldInvoiceDiscs = old('fdisc', []);
        $oldInvoiceTotals = old('ftotal', []);
        $oldInvoiceDescs = old('fdesc', []);
        $oldInvoiceKetdts = old('fketdt', []);
        $oldInvoiceMaxQtys = old('fmaxqty', []);
        $initialInvoiceItems = [];

        $oldInvoiceIndexes = array_keys(is_array($oldInvoiceItemCodes) ? $oldInvoiceItemCodes : []);

        foreach ($oldInvoiceIndexes as $index) {
            $itemCode = $oldInvoiceItemCodes[$index] ?? '';
            $code = trim((string) $itemCode);
            $name = trim((string) ($oldInvoiceItemNames[$index] ?? ''));
            if ($code === '' && $name === '') {
                continue;
            }

            $unit = trim((string) ($oldInvoiceUnits[$index] ?? ''));
            $refSo = trim((string) ($oldInvoiceRefSos[$index] ?? ''));
            $refSrj = trim((string) ($oldInvoiceRefSrjs[$index] ?? ''));
            $refDtNo = trim((string) ($oldInvoiceRefNos[$index] ?? ''));

            $initialInvoiceItems[] = [
                'uid' => 'old-invoice-' . $index,
                'formIndex' => (int) $index,
                'is_restored_old' => true,
                'fitemcode' => $code,
                'fitemname' => $name,
                'frefcode' => trim((string) ($oldInvoiceRefCodes[$index] ?? '')),
                'units' => $unit !== '' ? [$unit] : [],
                'fsatuan' => $unit,
                'frefdtno' => $refDtNo,
                'frefno_display' => $refSrj !== '' ? $refSrj : ($refSo !== '' ? $refSo : $refDtNo),
                'fnouref' => trim((string) ($oldInvoiceNouRefs[$index] ?? '')),
                'frefpr' => trim((string) ($oldInvoiceRefPrs[$index] ?? '')),
                'frefso' => $refSo,
                'frefsrj' => $refSrj,
                'fnoacak' => trim((string) ($oldInvoiceNoAcaks[$index] ?? '')),
                'frefnoacak' => trim((string) ($oldInvoiceRefNoAcaks[$index] ?? '')),
                'fqty' => (float) ($oldInvoiceQtys[$index] ?? 0),
                'fterima' => (float) ($oldInvoiceTerimas[$index] ?? 0),
                'fprice' => (float) ($oldInvoicePrices[$index] ?? 0),
                'fdisc' => $oldInvoiceDiscs[$index] ?? 0,
                'ftotal' => (float) ($oldInvoiceTotals[$index] ?? 0),
                'fdesc' => (string) ($oldInvoiceDescs[$index] ?? ''),
                'fketdt' => (string) ($oldInvoiceKetdts[$index] ?? ''),
                'maxqty' => max(0, (float) ($oldInvoiceMaxQtys[$index] ?? ($oldInvoiceQtys[$index] ?? 0))),
            ];
        }

        $nextInvoiceItemIndex = empty($oldInvoiceIndexes) ? 0 : max(array_map('intval', $oldInvoiceIndexes)) + 1;
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

        .invoice-detail-table th,
        .invoice-detail-table td {
            padding: .25rem .375rem !important;
        }

        .invoice-detail-table input:not([type="hidden"]),
        .invoice-detail-table select,
        .invoice-detail-table button {
            min-height: 1.875rem;
            padding-top: .25rem !important;
            padding-bottom: .25rem !important;
            line-height: 1.25rem;
        }

        .invoice-detail-table .rounded-l.border,
        .invoice-detail-table .rounded-r.border {
            min-height: 1.875rem;
            padding-top: .25rem !important;
            padding-bottom: .25rem !important;
            line-height: 1.25rem;
        }

        .invoice-detail-table button {
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
        <form id="invoiceForm" action="{{ route('invoice.store') }}" method="POST" data-form-draft="true"
            data-draft-key="invoice:create" data-tranmtid="" x-data="{ showNoItems: false }"
            @submit.prevent="
        const duplicateCode = window.getInvoiceDuplicateCode?.($el);
        if (duplicateCode) {
            Swal.fire({
                icon: 'warning',
                title: 'Produk Duplikat',
                text: `Kode produk ${duplicateCode} tidak boleh sama dalam satu Faktur Penjualan.`,
                confirmButtonText: 'OK',
                customClass: {
                    confirmButton: 'bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700'
                }
            });
            return;
        }
        const n = Number(document.getElementById('itemsCount')?.value || 0);
        if (n < 1) { showNoItems = true } else { window.invoiceCreditApprovalGuard($el).then(ok => { if (ok) $el.submit() }) }
      ">
            @csrf
            <input type="hidden" name="fneedacc" id="invoiceNeedAcc" value="{{ old('fneedacc', '0') }}">
            <input type="hidden" name="fuseracc" id="invoiceUserAcc" value="{{ old('fuseracc', '') }}">

            {{-- ─── CARD 1: Identitas Faktur Penjualan ────────────── --}}
            <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                <div class="flex items-center gap-2 px-4 pt-3 pb-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Identitas Faktur Penjualan</p>
                </div>
                <div class="p-4 space-y-3">
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-bold mb-1">Cabang</label>
                            <input type="text"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                                value="{{ trim(($fbranchcode ?? '') . ($fcabang ?? '' ? ' - ' . $fcabang : '')) }}"
                                disabled>
                            <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                        </div>

                        <div x-data="{ autoCode: @json(old('_token') === null || trim((string) old('fsono', '')) === '') }">
                            <label class="block text-xs font-bold mb-1">Faktur#</label>
                            <div class="flex items-center gap-2">
                                <input type="text" name="fsono" value="{{ old('fsono') }}"
                                    class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                    :disabled="autoCode"
                                    :class="autoCode ? 'bg-gray-100 text-gray-500 border-gray-200 cursor-not-allowed' :
                                        'bg-white'"
                                    :placeholder="autoCode ? 'Auto Generated' : ''">
                                <label
                                    class="inline-flex items-center select-none font-medium text-sm text-gray-600 cursor-pointer">
                                    <input type="checkbox" x-model="autoCode">
                                    <span class="ml-1.5">Auto</span>
                                </label>
                            </div>
                        </div>

                        <div x-data="{ autoTax: {{ old('_token') !== null ? (old('ftax_auto') == '1' ? 'true' : 'false') : 'true' }} }">
                            <label class="block text-xs font-bold mb-1">Faktur Pajak#</label>
                            <div class="flex items-center gap-2">
                                <input type="text" id="ftaxno" name="ftaxno" value="{{ old('ftaxno') }}"
                                    :disabled="autoTax"
                                    :class="autoTax ? 'bg-gray-100 text-gray-500 border-gray-200 cursor-not-allowed' :
                                        'bg-white'"
                                    :placeholder="autoTax ? 'Auto Generated' : ''"
                                    class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('ftaxno') border-red-500 @enderror">
                                <label
                                    class="inline-flex items-center select-none font-medium text-sm text-gray-600 cursor-pointer">
                                    <input type="checkbox" id="taxAutoCheckbox" name="ftax_auto" value="1"
                                        x-model="autoTax" @change="if (autoTax) window.syncInvoiceTaxNoFromInvoiceNo()">
                                    <span class="ml-1.5">Auto</span>
                                </label>
                            </div>
                            @error('ftaxno')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold mb-1">Type</label>
                            <select name="ftypesales" id="ftypesales" x-model.number="ftypesales" x-init="ftypesales = @json((int) old('ftypesales', 0))"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('ftypesales') border-red-500 @enderror">
                                <option value="0">Penjualan</option>
                                <option value="1">Uang Muka</option>
                            </select>
                            @error('ftypesales')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold mb-1">Tanggal</label>
                            <input type="date" id="fsodate" name="fsodate"
                                value="{{ old('fsodate') ?? date('Y-m-d') }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fsodate') border-red-500 @enderror">
                            @error('fsodate')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Customer --}}
                        <div>
                            <label class="block text-xs font-bold mb-1">Customer</label>
                            <div class="flex">
                                <div class="relative flex-1" for="modal_filter_customer_id">
                                    <select id="modal_filter_customer_id" name="filter_customer_id"
                                        class="w-full border border-gray-300 rounded-l-lg px-3 py-2 text-sm bg-gray-50 text-gray-700 cursor-pointer focus:outline-none focus:border-blue-500 pointer-events-none"
                                        disabled>
                                        <option value=""></option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->fcustomercode }}"
                                                data-ftempo="{{ (int) ($customer->ftempo ?? 0) }}"
                                                data-fkodefp="{{ $customer->fkodefp }}"
                                                data-fsalesman="{{ $customer->fsalesman }}"
                                                {{ old('fcustno', $filterSupplierId) == $customer->fcustomercode ? 'selected' : '' }}>
                                                {{ $customer->fcustomername }} ({{ $customer->fcustomercode }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-0 cursor-pointer z-10" role="button"
                                        aria-label="Browse Customer"
                                        @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))"></div>
                                </div>
                                <input type="hidden" name="fcustno" id="customerCodeHidden"
                                    value="{{ old('fcustno') }}">
                                <button type="button"
                                    @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))"
                                    class="border border-l-0 border-gray-300 px-3 py-2 bg-white hover:bg-gray-50 text-gray-500 transition-colors"
                                    title="Browse Customer">
                                    <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                </button>
                                @if (in_array('createCustomer', explode(',', session('user_restricted_permissions', '')), true))
                                    <a href="{{ route('customer.create') }}" target="_blank" rel="noopener"
                                        class="border border-l-0 border-gray-300 rounded-r-lg px-3 py-2 bg-white hover:bg-gray-50 text-gray-500 transition-colors"
                                        title="Tambah Customer">
                                        <x-heroicon-o-plus class="w-5 h-5" />
                                    </a>
                                @endif
                            </div>
                            <div id="customerAdvanceWarningBox" class="hidden my-2">
                                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-yellow-700"
                                                id="customerAdvanceWarningText"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @error('fcustno')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold mb-1">Ref.PO</label>
                            <input type="text" name="frefno" id="invoiceFrefno" value="{{ old('frefno') }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('frefno') border-red-500 @enderror">
                            @error('frefno')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Salesman --}}
                        <div>
                            <label class="block text-xs font-bold mb-1">Salesman</label>
                            <div class="flex">
                                <div class="relative flex-1" for="modal_filter_salesman_id">
                                    <select id="modal_filter_salesman_id" name="filter_salesman_id"
                                        class="w-full border border-gray-300 rounded-l-lg px-3 py-2 text-sm bg-gray-50 text-gray-700 cursor-pointer focus:outline-none focus:border-blue-500 pointer-events-none"
                                        disabled>
                                        <option value=""></option>
                                        @foreach ($salesmans as $salesman)
                                            <option value="{{ $salesman->fsalesmancode }}"
                                                {{ old('fsalesman', $filterSalesmanId) == $salesman->fsalesmancode ? 'selected' : '' }}>
                                                {{ $salesman->fsalesmanname }} ({{ $salesman->fsalesmancode }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-0 cursor-pointer z-10" role="button"
                                        aria-label="Browse Salesman"
                                        @click="window.dispatchEvent(new CustomEvent('salesman-browse-open'))"></div>
                                </div>
                                <input type="hidden" name="fsalesman" id="salesmanCodeHidden"
                                    value="{{ old('fsalesman') }}">
                                <button type="button"
                                    @click="window.dispatchEvent(new CustomEvent('salesman-browse-open'))"
                                    class="border border-l-0 border-gray-300 px-3 py-2 bg-white hover:bg-gray-50 text-gray-500 transition-colors"
                                    title="Browse Salesman">
                                    <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                </button>
                                @if (in_array('createSalesman', explode(',', session('user_restricted_permissions', '')), true))
                                    <a href="{{ route('salesman.create') }}" target="_blank" rel="noopener"
                                        class="border border-l-0 border-gray-300 rounded-r-lg px-3 py-2 bg-white hover:bg-gray-50 text-gray-500 transition-colors"
                                        title="Tambah Salesman">
                                        <x-heroicon-o-plus class="w-5 h-5" />
                                    </a>
                                @endif
                            </div>
                            @error('fsalesman')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-bold mb-1">TOP (Hari)</label>
                                <input type="number" id="ftempohr" name="ftempohr" value="{{ old('ftempohr', '0') }}"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('ftempohr') border-red-500 @enderror"
                                    placeholder="Masukkan jumlah hari">
                                @error('ftempohr')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold mb-1">Tgl. Jatuh Tempo</label>
                                <input type="date" id="fjatuhtempo" name="fjatuhtempo"
                                    value="{{ old('fjatuhtempo') ?? date('Y-m-d') }}" readonly
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200 @error('fjatuhtempo') border-red-500 @enderror">
                                @error('fjatuhtempo')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold mb-1">Kode FP</label>
                                <input type="text" name="fkodefp" id="invoiceFkodefp" value="{{ old('fkodefp') }}"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fkodefp') border-red-500 @enderror">
                                @error('fkodefp')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                function calculateDueDate() {
                                    const poDate = document.getElementById('fsodate').value;
                                    const tempoDays = parseInt(document.getElementById('ftempohr').value) || 0;

                                    if (poDate) {
                                        const date = new Date(poDate);
                                        date.setMinutes(date.getMinutes() + date.getTimezoneOffset());
                                        date.setDate(date.getDate() + tempoDays);

                                        const year = date.getFullYear();
                                        const month = String(date.getMonth() + 1).padStart(2, '0');
                                        const day = String(date.getDate()).padStart(2, '0');

                                        document.getElementById('fjatuhtempo').value = `${year}-${month}-${day}`;
                                    } else {
                                        document.getElementById('fjatuhtempo').value = '';
                                    }
                                }

                                // Event listeners
                                document.getElementById('fsodate').addEventListener('change', calculateDueDate);
                                document.getElementById('ftempohr').addEventListener('input', calculateDueDate);

                                // Preserve submitted due date after failed store; recalc on later edits.
                                if (!@json(old('fjatuhtempo') !== null)) {
                                    calculateDueDate();
                                }
                            });
                        </script>
                        <div> <label class="block text-xs font-bold mb-1">Keterangan</label>
                            <textarea name="fket" rows="2"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fket') border-red-500 @enderror"
                                placeholder="Keterangan isi di sini...">{{ old('fket') }}</textarea>
                            @error('fket')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold mb-1">Catatan Internal</label>
                            <textarea name="fketinternal" id="fketinternal" rows="2"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fketinternal') border-red-500 @enderror"
                                placeholder="Catatan internal isi di sini...">{{ old('fketinternal') }}</textarea>
                            @error('fketinternal')
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
                    <div x-data="itemsTable()" x-init="init()" class="space-y-2">

                        <div class="overflow-auto border rounded">
                            <table class="invoice-detail-table min-w-full text-sm balanced-detail-table"
                                data-skip-auto-detail-style="true">
                                <colgroup>
                                    <col style="width:2%;">
                                    <col style="width:16%;">
                                    <col style="width:25%;">
                                    <col style="width:8%;">
                                    <col style="width:13%;">
                                    <col style="width:8%;">
                                    <col style="width:9%;">
                                    <col style="width:6%;">
                                    <col style="width:10%;">
                                    <col style="width:3%;">
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
                                        <th class="p-2 text-right w-36 whitespace-nowrap">Disc. %</th>
                                        <th class="p-2 text-right w-36 whitespace-nowrap">Total Harga</th>
                                        <th class="p-2 text-center w-28">Aksi</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <template x-for="(it, i) in savedItems" :key="it.uid">
                                        <tr class="border-t align-top">
                                            <td class="p-2" x-text="i + 1"></td>
                                            <td class="p-2">
                                                <div class="flex">
                                                    <input type="text"
                                                        class="flex-1 border rounded-l px-2 py-1 font-mono text-sm focus:ring-1 focus:ring-blue-500"
                                                        x-model.trim="it.fitemcode" @input="onCodeTypedRow(it, i)"
                                                        @keydown.enter.prevent="focusRowUnit(it, i)">
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
                                                        x-text="it.fitemname"></div>
                                                    <button type="button" @click="openDesc(i)"
                                                        class="shrink-0 inline-flex items-center border border-l-0 rounded-r bg-slate-50 px-2 py-1 text-slate-700 hover:bg-slate-100"
                                                        title="Deskripsi">
                                                        <x-heroicon-o-document-text class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="p-2">
                                                <template x-if="it.units && it.units.length > 1">
                                                    <select
                                                        class="w-full border rounded px-2 py-1 text-sm focus:ring-1 focus:ring-blue-500"
                                                        :id="'unit_row_' + i" x-model="it.fsatuan"
                                                        @change="applyInvoicePrice(it); onRowUpdated(i)"
                                                        @keydown.enter.prevent="focusRowQty(i)">
                                                        <template x-for="u in it.units" :key="u">
                                                            <option :value="u" :selected="u === it.fsatuan"
                                                                x-text="u"></option>
                                                        </template>
                                                    </select>
                                                </template>
                                                <template x-if="!(it.units && it.units.length > 1)">
                                                    <div class="px-2 py-1 text-sm text-gray-600 bg-gray-50 border rounded"
                                                        x-text="it.fsatuan || '-'"></div>
                                                </template>
                                            </td>
                                            <td class="p-2 text-blue-600">
                                                <input type="text"
                                                    class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                                    :value="it.frefno_display || it.frefdtno || '-'" disabled>
                                            </td>
                                            <td class="p-2 text-right">
                                                <input type="number"
                                                    class="w-full border rounded px-2 py-1 text-right text-sm"
                                                    :id="'qty_row_' + i" x-model.number="it.fqty"
                                                    @input="enforceQtyRow(it); onRowUpdated(i)"
                                                    @change="enforceQtyRow(it); onRowUpdated(i)"
                                                    @keydown.enter.prevent="focusRowPrice(i)">
                                                <div class="text-xs text-gray-400 mt-0.5 text-right">
                                                    <span x-show="it.fitemcode" x-html="formatStockLimit(it)"></span>
                                                </div>
                                            </td>
                                            <td class="p-2 text-right">
                                                <input type="text" inputmode="decimal"
                                                    class="w-full border rounded px-2 py-1 text-right text-sm"
                                                    :id="'price_row_' + i" x-model="it.fpriceInput"
                                                    @focus="activeRow = it.uid; focusPriceInput(it); $event.target.select()"
                                                    @blur="activeRow = null; blurPriceInput(it)"
                                                    @input="onPriceInput(it); onRowUpdated(i)"
                                                    @keydown.enter.prevent="focusRowDisc(i)">
                                            </td>
                                            <td class="p-2 text-right">
                                                <input type="text"
                                                    class="w-full border rounded px-2 py-1 text-right text-sm"
                                                    :id="'disc_row_' + i" :value="normalizeDiscountValue(it.fdisc)"
                                                    @focus="activeRow = it.uid; $event.target.select()"
                                                    @blur="activeRow = null; normalizeDiscountInput($event, it)"
                                                    @input="it.fdisc = $event.target.value; onRowUpdated(i)"
                                                    @keydown.enter.prevent="$event.target.blur()">
                                            </td>
                                            <td class="p-2">
                                                <input type="text"
                                                    class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm text-right"
                                                    :value="fmt(it.ftotal)" disabled>
                                            </td>
                                            <td class="p-2 text-center text-xs">
                                                <button type="button" @click="removeSaved(i)"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded bg-red-100 text-red-600 hover:bg-red-200"
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
                                    <input type="hidden" :name="`frefcode[${it.formIndex}]`" :value="it.frefcode">
                                    <input type="hidden" :name="`fnouref[${it.formIndex}]`" :value="it.fnouref">
                                    <input type="hidden" :name="`frefso[${it.formIndex}]`" :value="it.frefso">
                                    <input type="hidden" :name="`frefsrj[${it.formIndex}]`" :value="it.frefsrj">
                                    <input type="hidden" :name="`fnoacak[${it.formIndex}]`" :value="it.fnoacak">
                                    <input type="hidden" :name="`frefnoacak[${it.formIndex}]`" :value="it.frefnoacak">
                                    <input type="hidden" :name="`fprhid[${it.formIndex}]`" :value="it.fprhid">
                                    <input type="hidden" :name="`frefpr[${it.formIndex}]`" :value="it.frefpr">
                                    <input type="hidden" :name="`fqty[${it.formIndex}]`" :value="it.fqty">
                                    <input type="hidden" :name="`fterima[${it.formIndex}]`" :value="it.fterima">
                                    <input type="hidden" :name="`fprice[${it.formIndex}]`" :value="it.fprice">
                                    <input type="hidden" :name="`fdisc[${it.formIndex}]`" :value="it.fdisc">
                                    <input type="hidden" :name="`ftotal[${it.formIndex}]`" :value="it.ftotal">
                                    <input type="hidden" :name="`fdesc[${it.formIndex}]`" :value="it.fdesc">
                                    <input type="hidden" :name="`fmaxqty[${it.formIndex}]`" :value="it.maxqty">
                                    <input type="hidden" :name="`fketdt[${it.formIndex}]`" :value="it.fketdt">
                                </div>
                            </template>
                        </div>

                        <input type="hidden" name="frefcode_header" id="frefcode"
                            value="{{ old('frefcode_header') }}">
                        <input type="hidden" name="frefso_header" id="frefso" value="{{ old('frefso_header') }}">
                        <input type="hidden" name="frefsrj_header" id="frefsrj" value="{{ old('frefsrj_header') }}">

                        <script>
                            document.addEventListener('DOMContentLoaded', () => {
                                const inputRefCode = document.getElementById('frefcode');
                                const inputRefSo = document.getElementById('frefso');
                                const inputRefSrj = document.getElementById('frefsrj');

                                // Menangkap Event saat SO dipilih
                                window.addEventListener('pr-picked', (e) => {
                                    const header = e.detail.header;
                                    inputRefCode.value = 'SO';
                                    inputRefSo.value = header.fsono; // ← pakai fsono bukan ftrsomtid
                                    inputRefSrj.value = '';
                                });

                                // Menangkap Event saat SRJ dipilih
                                window.addEventListener('srj-picked', (e) => {
                                    const header = e.detail.header;
                                    inputRefCode.value = 'SRJ';
                                    inputRefSrj.value = header.fstockmtno;
                                    inputRefSo.value = ''; // Reset yang lain
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
                                                    <h3 class="text-xl font-bold text-gray-800">{{ 'Pilih Surat Jalan' }}
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
                                                <h3 class="text-lg font-semibold text-gray-800">
                                                    {{ 'Item Duplikat Surat Jalan' }}</h3>
                                            </div>
                                            <div class="px-5 py-4">
                                                <p class="text-sm text-gray-700 mb-3">
                                                    {{ Str::before('Ditemukan :count item yang sudah ada dalam daftar. Hanya item unik yang akan ditambahkan.', '__COUNT__') }}<span
                                                        x-text="dupCount"
                                                        class="font-bold"></span>{{ Str::after('Ditemukan :count item yang sudah ada dalam daftar. Hanya item unik yang akan ditambahkan.', '__COUNT__') }}
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
                                                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg">{{ 'Tambahkan Sisa Item' }}</button>
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
                                                {{ 'Add SO' }}
                                            </button>
                                        </div>
                                    </div>

                                    {{-- MODAL SO --}}
                                    <div x-show="show" x-cloak x-transition.opacity
                                        class="fixed inset-0 z-50 flex items-center justify-center p-4">
                                        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal()">
                                        </div>

                                        <div class="relative bg-white rounded-2xl shadow-2xl w-[96vw] max-w-[110rem] flex flex-col overflow-hidden"
                                            style="height: 85vh;">
                                            <!-- Header -->
                                            <div
                                                class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-teal-50 to-white">
                                                <div>
                                                    <h3 class="text-xl font-bold text-gray-800">{{ 'Pilih Sales Order' }}
                                                    </h3>
                                                    <p class="text-sm text-gray-500 mt-0.5">
                                                        {{ 'Pilih Sales Order yang diinginkan' }}</p>
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
                                            <div class="flex-1 overflow-x-auto overflow-y-hidden px-6"
                                                style="min-height: 0;">
                                                <div class="bg-white">
                                                    <table id="poTable"
                                                        class="min-w-full text-sm display nowrap stripe hover"
                                                        style="width:100%">
                                                        <thead class="sticky top-0 z-10">
                                                            <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                                                <th
                                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                    {{ 'Cab' }}</th>
                                                                <th
                                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                    {{ 'No. SO' }}</th>
                                                                <th
                                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                    {{ 'Tanggal' }}</th>
                                                                <th
                                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                    {{ 'Customer' }}</th>
                                                                <th
                                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                    {{ 'No. PO' }}</th>
                                                                <th
                                                                    class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                    {{ 'Aksi' }}</th>
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
                                                <h3 class="text-lg font-semibold text-gray-800">
                                                    {{ 'Item Duplikat Ditemukan' }}</h3>
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
                                                        {{ 'Preview Item Duplikat' }}
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
                                                                <span class="text-gray-400">â€¢</span>
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

                            <!-- ===== Panel Totals (DESAIN ASLI dipertahankan, hanya wrapper yang diperbaiki) ===== -->
                            <div x-data="prhFormModal()" class="w-full md:w-auto md:min-w-[550px]">
                                <div class="rounded-lg border bg-gray-50 p-3 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-gray-800">Total Harga</span>
                                        <span class="font-bold text-gray-900"
                                            x-text="formatTransactionAmount(totalHarga)"></span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-gray-800">Discount</span>
                                        <input type="number" min="0" max="100" step="0.01"
                                            name="fdiscpersen" x-model.number="headerDiscPercent"
                                            class="w-16 h-9 px-2 text-sm leading-tight text-right border border-gray-300 rounded transition-opacity
                                                [appearance:textfield]
                                                [&::-webkit-outer-spin-button]:appearance-none
                                                [&::-webkit-inner-spin-button]:appearance-none">
                                        <span class="text-gray-500">%</span>
                                        <span class="flex-1"></span>
                                        <span class="font-bold text-right" x-text="rupiah(headerDiscAmount)"></span>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-gray-800">Total Setelah Disc</span>
                                        <span class="font-bold text-gray-900" x-text="rupiah(totalSetelahDisc)"></span>
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
                                            class="w-28 h-9 px-2 text-sm leading-tight border border-gray-300 rounded transition-opacity appearance-none
                                                disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed">
                                            <option value="0">Exclude</option>
                                            <option value="1">Include</option>
                                        </select>

                                        <!-- Input Rate + Nominal -->
                                        <input type="number" min="0" max="100" name="ppn_rate"
                                            step="0.01" x-model.number="ppnRate" :disabled="!includePPN"
                                            class="w-16 h-9 px-2 text-sm leading-tight text-right border border-gray-300 rounded transition-opacity
                                                    [appearance:textfield]
                                                    [&::-webkit-outer-spin-button]:appearance-none
                                                    [&::-webkit-inner-spin-button]:appearance-none
                                                    disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed">
                                        <span class="text-gray-500">%</span>
                                        <span class="flex-1"></span>
                                        <span class="font-bold" x-text="rupiah(ppnAmount)"></span>
                                    </div>

                                    <div class="border-t my-1"></div>

                                    <div class="flex items-center justify-between">
                                        <span class="font-extrabold text-gray-900">Grand Total</span>
                                        <span class="font-extrabold text-blue-700 text-lg"
                                            x-text="rupiah(grandTotal)"></span>
                                    </div>
                                </div>

                                <!-- Hidden inputs for submit -->
                                <input type="hidden" name="famountgross" :value="totalHarga">
                                <input type="hidden" name="famountpajak" :value="ppnAmount">
                                <input type="hidden" name="famountsonet" :value="netTotal">
                                <input type="hidden" name="famountso" :value="grandTotal">
                                <input type="hidden" name="famountpopajak" :value="ppnAmount">
                                <input type="hidden" name="fppnpersen" :value="ppnRate">
                                <input type="hidden" name="fdiscount" :value="headerDiscAmount">

                                <!-- Modal backdrop - sekarang bisa akses 'show' -->
                                <div x-show="show" x-transition.opacity class="fixed inset-0 z-40 bg-black/50"
                                    @keydown.escape.window="closeModal()"></div>

                                {{-- MODAL PR dengan DataTables - HAPUS x-data di sini --}}
                                <div>
                                    {{-- MODAL PR --}}
                                    <div x-show="show" x-cloak x-transition.opacity
                                        class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-8"
                                        aria-modal="true" role="dialog">

                                        <div class="relative bg-white rounded-2xl shadow-2xl w-[96vw] max-w-[110rem] flex flex-col overflow-hidden"
                                            style="height: 85vh;">
                                            <!-- Header -->
                                            <div
                                                class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
                                                <h3 class="text-xl font-bold text-gray-800">
                                                    {{ 'Pilih Purchase Order (PO)' }}
                                                </h3>
                                                <button type="button" @click="closeModal()"
                                                    class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                                                    {{ 'Tutup' }}
                                                </button>
                                            </div>

                                            <!-- Table Container -->
                                            <div class="flex-1 overflow-x-auto overflow-y-hidden p-6"
                                                style="min-height: 0;">
                                                <table id="prTable"
                                                    class="min-w-full text-sm display nowrap stripe hover"
                                                    style="width:100%">
                                                    <thead class="sticky top-0 z-10">
                                                        <tr class="bg-gray-50 border-b-2 border-gray-200">
                                                            <th class="p-3 text-left font-semibold text-gray-700">
                                                                {{ 'PO No' }}</th>
                                                            <th class="p-3 text-left font-semibold text-gray-700">
                                                                {{ 'Customer' }}</th>
                                                            <th class="p-3 text-left font-semibold text-gray-700">
                                                                {{ 'Tanggal' }}</th>
                                                            <th class="p-3 text-center font-semibold text-gray-700">
                                                                {{ 'Aksi' }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <!-- DataTables data here -->
                                                    </tbody>
                                                </table>
                                            </div>

                                            <!-- Footer (Pagination rendered by DataTables, just provide space if needed) -->
                                            <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
                                                <!-- DataTables pagination will be rendered automatically based on the 'dom' setting. -->
                                            </div>
                                        </div>
                                    </div>
                                    {{-- Modal Duplikasi --}}
                                    <div x-show="showDupModal" x-cloak x-transition.opacity
                                        class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                                        <div class="absolute inset-0 bg-black/40" @click="closeDupModal()"></div>
                                        <div class="relative bg-white rounded-xl shadow-xl max-w-2xl w-full p-6">
                                            <h3 class="text-lg font-semibold mb-4">{{ 'Peringatan Duplikasi' }}</h3>
                                            <p class="mb-4">
                                                {{ Str::before('Ditemukan :count item yang sudah ada dalam daftar. Hanya item unik yang akan ditambahkan.', '__COUNT__') }}<strong
                                                    x-text="dupCount"></strong>{{ Str::after('Ditemukan :count item yang sudah ada dalam daftar. Hanya item unik yang akan ditambahkan.', '__COUNT__') }}
                                            </p>

                                            <div class="mb-4 max-h-48 overflow-auto border rounded p-2 bg-gray-50"
                                                x-show="dupSample.length > 0">
                                                <p class="text-sm font-medium mb-2">{{ 'Contoh item duplikat:' }}</p>
                                                <template x-for="(item, idx) in dupSample" :key="idx">
                                                    <div class="text-xs py-1">
                                                        â€¢ <span x-text="item.fitemcode"></span> - <span
                                                            x-text="item.frefdtno"></span>
                                                    </div>
                                                </template>
                                            </div>

                                            <div class="flex justify-end gap-2">
                                                <button type="button" @click="closeDupModal()"
                                                    class="rounded bg-gray-200 px-4 py-2 text-sm font-medium hover:bg-gray-300">
                                                    {{ 'Batal' }}
                                                </button>
                                                <button type="button" @click="confirmAddUniques()"
                                                    class="rounded bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                                                    {{ 'Tambahkan Item Unik' }}
                                                </button>
                                            </div>
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
                                    <h3 class="text-lg font-semibold text-gray-800">{{ 'Isi Deskripsi Item' }}</h3>
                                </div>

                                <div class="px-5 py-4 space-y-4">
                                    <div>
                                        <div class="mb-1 flex items-center justify-between gap-3">
                                            <div class="text-sm text-gray-700">{{ 'Nama Produk' }}</div>
                                            <button type="button" @click="copyDescName()"
                                                class="h-8 px-3 rounded-lg bg-blue-50 text-blue-700 text-sm font-medium hover:bg-blue-100">
                                                {{ 'Copy' }}
                                            </button>
                                        </div>
                                        <div class="rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800"
                                            x-text="descItemName || '-'"></div>
                                    </div>
                                    <label class="block text-sm text-gray-700">{{ 'Keterangan' }}</label>
                                    <textarea x-model="descValue" rows="5" class="w-full border rounded px-3 py-2"
                                        placeholder="{{ 'Tulis deskripsi item di sini...' }}"></textarea>
                                </div>

                                <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
                                    <button type="button" @click="closeDesc()"
                                        class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">
                                        {{ 'Batal' }}
                                    </button>
                                    <button type="button" @click="applyDesc()"
                                        class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
                                        {{ 'Simpan' }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div x-show="showCustomerRequired" x-cloak
                            class="fixed inset-0 z-[94] flex items-center justify-center" x-transition.opacity>
                            <div class="absolute inset-0 bg-black/50" @click="showCustomerRequired = false"></div>

                            <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden"
                                x-transition.scale>
                                <div class="px-5 py-4 border-b flex items-center">
                                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-amber-500 mr-2" />
                                    <h3 class="text-lg font-semibold text-gray-800">{{ 'Pilih Customer Dulu' }}</h3>
                                </div>

                                <div class="px-5 py-4">
                                    <p class="text-sm text-gray-700">
                                        Pilih customer dulu sebelum menambah produk manual.
                                        Jika ambil dari SO atau SRJ, customer boleh dipilih setelahnya.
                                    </p>
                                </div>

                                <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
                                    <button type="button"
                                        @click="showCustomerRequired = false; document.getElementById('modal_filter_customer_id')?.focus()"
                                        class="h-9 px-4 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                                        {{ 'OK' }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" id="itemsCount" :value="submitItems.length">
                    </div> {{-- End itemsTable --}}
                </div> {{-- End CARD 2 body --}}
            </div> {{-- End CARD 2 --}}

            {{-- MODAL ERROR: belum ada item --}}
            <div x-show="showNoItems && submitItems.length === 0" x-cloak
                class="fixed inset-0 z-[90] flex items-center justify-center" x-transition.opacity>
                <div class="absolute inset-0 bg-black/50" @click="showNoItems=false"></div>

                <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden" x-transition.scale>
                    <div class="px-5 py-4 border-b flex items-center">
                        <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-500 mr-2" />
                        <h3 class="text-lg font-semibold text-gray-800">{{ 'Tidak Ada Item' }}</h3>
                    </div>

                    <div class="px-5 py-4">
                        <p class="text-sm text-gray-700">
                            Anda belum menambahkan item apa pun pada tabel. Silakan isi baris â€œDetail Itemâ€
                            terlebih
                            dahulu.
                        </p>
                    </div>

                    <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
                        <button type="button" @click="showNoItems=false"
                            class="h-9 px-4 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                            {{ 'OK' }}
                        </button>
                    </div>
                </div>
            </div>

            <x-transaction.browse-customer-modal />

            <x-transaction.browse-salesman-modal />

            <x-transaction.browse-product-modal show-controls="true" show-pagination="true" />

            @php
                $canApproval = in_array(
                    'approveFakturPenjualan',
                    explode(',', session('user_restricted_permissions', '')),
                );
            @endphp

            {{-- Footer Buttons --}}
            <div class="flex items-center justify-end gap-3 px-5 py-2 bg-gray-50 border-t border-gray-200">
                <button type="button" onclick="window.location.href='{{ route('invoice.index') }}'"
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
<x-transaction.datatables-length-styles :tables="['productTable', 'supplierTable', 'prTable']" />
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
    window.INVOICE_CUSTOMER_FP_MAP = @json(collect($customers)->mapWithKeys(fn($customer) => [
                (string) $customer->fcustomercode => (string) ($customer->fkodefp ?? ''),
            ]));

    window.syncInvoiceCustomerTaxCode = function(payload = null, isInitial = false) {
        const kodeFpInput = document.getElementById('invoiceFkodefp');
        if (!kodeFpInput) {
            return;
        }

        if (isInitial && kodeFpInput.value.trim() !== '') {
            return;
        }

        const select = document.getElementById('modal_filter_customer_id');
        const hidden = document.getElementById('customerCodeHidden');
        const normalize = (value) => String(value ?? '').trim();
        const eventCode = typeof payload === 'object' && payload !== null ? normalize(payload.fcustomercode) : '';
        const eventValue = typeof payload === 'object' && payload !== null ? normalize(payload.fkodefp) : normalize(
            payload);
        const customerCode = eventCode || normalize(hidden?.value) || normalize(select?.value);
        const selectedOption = customerCode ? [...(select?.options || [])].find(option => normalize(option
                .value) === customerCode) :
            select?.selectedOptions?.[0];
        const optionValue = normalize(selectedOption?.dataset?.fkodefp);
        const mappedValue = customerCode ? (window.INVOICE_CUSTOMER_FP_MAP?.[customerCode] || '') : '';

        kodeFpInput.value = eventValue || optionValue || mappedValue || '';
    };

    window.invoicePreserveOldTempo = @json(old('ftempohr') !== null);

    window.syncInvoiceTempoFromSource = function(days, options = {}) {
        if (window.invoicePreserveOldTempo && !options.force) return;

        const tempoInput = document.getElementById('ftempohr');
        if (!tempoInput) return;
        const numericDays = Number(days ?? 0);
        tempoInput.value = Number.isFinite(numericDays) ? String(Math.max(0, numericDays)) : '0';
        tempoInput.dispatchEvent(new Event('input', {
            bubbles: true
        }));
        tempoInput.dispatchEvent(new Event('change', {
            bubbles: true
        }));
    };

    window.syncInvoiceSalesmanFromSource = function(payload = null) {
        const salesmanCode = String(
            payload?.fsalesman ?? payload?.fsalesmancode ?? payload?.salesman_code ?? ''
        ).trim();
        const salesmanName = String(
            payload?.fsalesmanname ?? payload?.salesman_name ?? ''
        ).trim();

        if (!salesmanCode) return;

        window.applyTransactionSalesmanSelection?.({
            fsalesman: salesmanCode,
            fsalesmancode: salesmanCode,
            fsalesmanname: salesmanName,
        });
    };

    window.syncInvoicePpnFromSource = function(header = null) {
        const root = document.querySelector('[x-data*="itemsTable()"]');
        const component = root && window.Alpine ? Alpine.$data(root) : null;
        if (!component || !header) return;

        component.includePPN = Number(header.fincludeppn ?? 0) === 1;
        component.fapplyppn = Number(header.fapplyppn ?? 0) === 1 ? 1 : 0;

        const rate = Number(header.fppnpersen ?? 11);
        component.ppnRate = Number.isFinite(rate) && rate >= 0 ? rate : 11;
    };

    window.syncInvoiceTempoFromCustomer = function(payload = null) {
        const normalize = (value) => String(value ?? '').trim();
        const select = document.getElementById('modal_filter_customer_id');
        const hidden = document.getElementById('customerCodeHidden');
        const customerCode = normalize(payload?.fcustomercode) || normalize(hidden?.value) || normalize(select
            ?.value);
        const selectedOption = customerCode ? [...(select?.options || [])].find(option => normalize(option
                .value) === customerCode) :
            select?.selectedOptions?.[0];
        const eventTempo = normalize(payload?.ftempo);
        const optionTempo = normalize(selectedOption?.dataset?.ftempo);
        window.syncInvoiceTempoFromSource(eventTempo || optionTempo || '0');
    };

    window.syncInvoiceSalesmanFromCustomer = function(payload = null) {
        const normalize = (value) => String(value ?? '').trim();
        const select = document.getElementById('modal_filter_customer_id');
        const hidden = document.getElementById('customerCodeHidden');
        const customerCode = normalize(payload?.fcustomercode) || normalize(hidden?.value) || normalize(select
            ?.value);
        const selectedOption = customerCode ? [...(select?.options || [])].find(option => normalize(option
            .value) === customerCode) : select?.selectedOptions?.[0];

        const salesmanCode = normalize(payload?.fsalesman) || normalize(selectedOption?.dataset?.fsalesman);
        if (!salesmanCode) return;
        const salesmanSelect = document.getElementById('modal_filter_salesman_id');
        const salesmanOption = [...(salesmanSelect?.options || [])].find(option => normalize(option.value) ===
            salesmanCode);
        const salesmanName = normalize(payload?.fsalesmanname) || normalize(salesmanOption?.textContent).replace(
            new RegExp(`\\s*\\(${salesmanCode.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\)\\s*$`), '');

        window.applyTransactionSalesmanSelection?.({
            fsalesmancode: salesmanCode,
            fsalesmanname: salesmanName,
        });
    };

    window.syncInvoiceTaxNoFromInvoiceNo = function() {
        const taxAutoCheckbox = document.getElementById('taxAutoCheckbox');
        if (taxAutoCheckbox && !taxAutoCheckbox.checked) {
            return;
        }
        const invoiceInput = document.querySelector('input[name="fsono"]');
        const taxInput = document.getElementById('ftaxno');
        if (!invoiceInput || !taxInput) return;
        taxInput.value = String(invoiceInput.value ?? '').trim();
    };

    document.addEventListener('customer-selected', function(event) {
        window.syncInvoiceCustomerTaxCode(event.detail || null);
        window.syncInvoiceTempoFromCustomer(event.detail || null);
        window.syncInvoiceSalesmanFromCustomer(event.detail || null);
    });

    window.addEventListener('customer-browse-open', function() {
        window.invoicePreserveOldTempo = false;
    });

    document.addEventListener('DOMContentLoaded', function() {
        const select = document.getElementById('modal_filter_customer_id');
        const invoiceInput = document.querySelector('input[name="fsono"]');
        if (select) {
            select.addEventListener('change', function() {
                window.syncInvoiceCustomerTaxCode();
                window.syncInvoiceTempoFromCustomer();
                window.syncInvoiceSalesmanFromCustomer();
            });
        }
        if (invoiceInput) {
            invoiceInput.addEventListener('input', window.syncInvoiceTaxNoFromInvoiceNo);
            invoiceInput.addEventListener('change', window.syncInvoiceTaxNoFromInvoiceNo);
        }

        window.syncInvoiceCustomerTaxCode(null, true);
        if (!@json(old('ftempohr') !== null)) {
            window.syncInvoiceTempoFromCustomer();
        }
        if (!document.getElementById('salesmanCodeHidden')?.value) {
            window.syncInvoiceSalesmanFromCustomer();
        }
        window.syncInvoiceTaxNoFromInvoiceNo();
    });

    window.getInvoiceDuplicateCode = function(form) {
        const seen = new Set();
        const tableRoot = form.querySelector('[x-data*="itemsTable()"]');
        const alpineData = tableRoot?._x_dataStack?.[0] || null;
        const rows = Array.isArray(alpineData?.submitItems) ? alpineData.submitItems : null;

        if (rows) {
            for (const row of rows) {
                const code = (row?.fitemcode || '').toString().trim().toUpperCase();
                if (!code) continue;
                if (seen.has(code)) {
                    return code;
                }
                seen.add(code);
            }
            return '';
        }

        const inputs = Array.from(form.querySelectorAll('input[name^="fitemcode["]'));
        for (const input of inputs) {
            const code = (input.value || '').toString().trim().toUpperCase();
            if (!code) continue;
            if (seen.has(code)) {
                return code;
            }
            seen.add(code);
        }

        return '';
    };

    window.invoiceCreditApprovalGuard = async function(form) {
        const customerCode = form.querySelector('[name="fcustno"]')?.value?.trim() || '';
        const amountValue = parseFloat(form.querySelector('[name="famountso"]')?.value || '0') || 0;
        const tranmtId = form.dataset.tranmtid || '';
        const needAccInput = form.querySelector('#invoiceNeedAcc');
        const userAccInput = form.querySelector('#invoiceUserAcc');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        if (needAccInput) needAccInput.value = '0';
        if (userAccInput) userAccInput.value = '';

        if (!customerCode) {
            return true;
        }

        try {
            const response = await fetch('{{ route('invoice.credit-check') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    fcustno: customerCode,
                    famountso: amountValue,
                    ftranmtid: tranmtId || null
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
                            <div class="mt-3">${@json('Transaksi ini membutuhkan persetujuan kredit. Lanjutkan?')}</div>
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
                            <div class="mt-3">${@json('Transaksi ini membutuhkan persetujuan kredit. Lanjutkan?')}</div>
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
    window.INVOICE_PRICE_INFO_URL = @json(route('invoice.price-info'));
    window.INVOICE_PRICE_FLAGS = @json($priceFlags ?? []);

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

    // Modal salesman

    function itemsTable() {
        return {
            savedItems: @json($initialInvoiceItems),
            nextFormIndex: @json($nextInvoiceItemIndex),
            minimumVisibleRows: 5,
            browseTarget: null,
            descSavedIndex: null,
            showDescModal: false,
            descItemName: '',
            descValue: '',
            showCustomerRequired: false,

            totalHarga: 0,
            headerDiscPercent: @json((float) old('fdiscpersen', 0)),
            ppnRate: @json((float) old('fppnpersen', old('ppn_rate', 11))),

            initialGrandTotal: @json($famountso ?? 0),
            initialPpnAmount: @json($famountpopajak ?? 0),

            includePPN: @json(old('fapplyppn') !== null || old('fincludeppn') !== null),
            fapplyppn: @json(old('fincludeppn', '0') == '1'),

            get ppnMode() {
                return this.fapplyppn ? 1 : 0;
            },

            set ppnMode(value) {
                this.fapplyppn = Number(value) === 1;
            },

            get headerDiscAmount() {
                const total = +this.totalHarga || 0;
                const percent = Math.min(100, Math.max(0, +this.headerDiscPercent || 0));
                return +(total * (percent / 100)).toFixed(2);
            },

            get totalSetelahDisc() {
                const total = +this.totalHarga || 0;
                return +(total - this.headerDiscAmount).toFixed(2);
            },

            get ppnIncluded() {
                const total = this.totalSetelahDisc;
                const rate = +this.ppnRate || 0;
                if (!this.fapplyppn || !this.includePPN) return 0;
                return Math.round((100 / (100 + rate)) * total * (rate / 100));
            },

            get netFromGross() {
                const total = this.totalSetelahDisc;
                return total - this.ppnIncluded;
            },

            get ppnAdded() {
                const rate = +this.ppnRate || 0;
                if (!this.includePPN || this.fapplyppn) return 0;
                const total = this.totalSetelahDisc;
                return Math.round(total * (rate / 100));
            },

            get ppnAmount() {
                if (!this.includePPN) return 0;
                if (this.fapplyppn) {
                    return this.ppnIncluded;
                }
                return this.ppnAdded;
            },

            get netTotal() {
                const total = this.totalSetelahDisc;
                if (!this.includePPN) return total;
                if (this.fapplyppn) {
                    return this.netFromGross;
                }
                return total;
            },

            get grandTotal() {
                const total = this.totalSetelahDisc;
                if (!this.includePPN || this.fapplyppn) {
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

            // âœ… FUNGSI BARU: Parse diskon dengan format "10+2"
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

            isReferenceRow(row) {
                return String(row?.frefso ?? '').trim() !== '' || String(row?.frefsrj ?? '').trim() !== '';
            },

            async applyInvoicePrice(row) {
                if (this.isReferenceRow(row)) return;
                const customerCode = this.getSelectedCustomerCode();
                const productCode = (row?.fitemcode || '').toString().trim();
                const unit = (row?.fsatuan || '').toString().trim();
                if (!customerCode || !productCode || !unit) return;

                const params = new URLSearchParams({
                    fcustno: customerCode,
                    fprdcode: productCode,
                    fsatuan: unit,
                });

                try {
                    const response = await fetch(`${window.INVOICE_PRICE_INFO_URL}?${params.toString()}`, {
                        headers: {
                            Accept: 'application/json'
                        }
                    });
                    if (!response.ok) return;

                    const payload = await response.json();
                    row.fsatuan = payload.unit || row.fsatuan;
                    row.fprice = Math.max(0, Number(payload.price || 0));
                    row.fpriceInput = this.fmt(row.fprice);
                    row.fdisc = payload.discount ?? '0';
                    this.recalc(row);
                } catch (error) {
                    console.warn('Gagal mengambil harga faktur penjualan:', error);
                }
            },

            // ✅ UPDATE FUNGSI recalc untuk menggunakan parseDiscount
            recalc(row) {
                row.fqty = Math.max(0, +row.fqty || 0);
                row.fterima = Math.max(0, +row.fterima || 0);
                row.fprice = Math.max(0, +row.fprice || 0);
                if (row.fprice < 0) row.fprice = 0;
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
                this.totalHarga = this.savedItems.reduce((sum, item) => {
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
                    row?.frefno_display,
                    row?.frefcode,
                    row?.frefpr,
                    row?.frefso,
                    row?.frefsrj,
                    row?.fqty,
                    row?.fterima,
                    row?.fprice,
                    row?.fdisc,
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

            getFirstEmptyRowIndex() {
                return this.savedItems.findIndex((row) => !this.rowHasContent(row));
            },

            onRowUpdated(index = null) {
                const row = typeof index === 'number' ? this.savedItems[index] : null;
                if (row) {
                    this.recalc(row);
                }
                this.recalcTotals();
                this.ensureTrailingRow(index);
            },

            getRowQtyLimit(row) {
                const limitSource = Math.max(0, Number(row?.maxqty ?? 0) || 0);
                if (row?.maxqty_unit !== 'kecil') return limitSource;

                return this.qtyKecilToUnit(limitSource, row?.fsatuan || '', this.productMeta(row?.fitemcode));
            },

            validateReferenceQty(row, showToast = true) {
                const hasRef = String(row?.frefso ?? '').trim() !== '' ||
                    String(row?.frefsrj ?? '').trim() !== '';
                if (!hasRef) return true;

                const limit = this.getRowQtyLimit(row);
                if (limit <= 0) {
                    if (showToast) window.toast?.error('Sisa referensi sudah habis.');
                    return false;
                }

                const qty = Number(row?.fqty ?? 0);
                if (qty > limit) {
                    if (showToast) window.toast?.error(
                        `Qty melebihi sisa referensi. Maksimal ${limit} ${row.fsatuan || ''}`.trim());
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
                    row.frefcode = '';
                    row.maxqty = 0;
                    return;
                }
                row.fitemname = meta.name || '';
                row.frefcode = meta.fprdcode || meta.id || '';
                const units = [...new Set((meta.units || []).map(u => (u ?? '').toString().trim()).filter(Boolean))];
                const preferredUnit = (row.fsatuan || '').toString().trim();
                const matchedUnit = preferredUnit === '' ? '' : (units.find(u => u.toLowerCase() === preferredUnit
                    .toLowerCase()) || '');
                const preservedUnit = matchedUnit || preferredUnit;

                row.units = preservedUnit !== '' ? [preservedUnit, ...units.filter(u => u.toLowerCase() !==
                        preservedUnit.toLowerCase())] :
                    units;

                const defaultUnit = (meta.default_unit || '').toString().trim();
                const resolvedDefaultUnit = defaultUnit && units.includes(defaultUnit) ? defaultUnit : (units[0] || '');

                if (forceDefaultUnit) {
                    row.fsatuan = resolvedDefaultUnit;
                } else if (preservedUnit !== '') {
                    row.fsatuan = preservedUnit;
                } else if (!row.units.includes(row.fsatuan)) {
                    row.fsatuan = resolvedDefaultUnit;
                }
                if (meta.unit_ratios) row.unit_ratios = meta.unit_ratios;
                row.maxqty = Number.isFinite(+row.maxqty) ? +row.maxqty : 0;
            },

            onCodeTypedRow(row, index = null) {
                if ((row.fitemcode || '').toString().trim() !== '' && !this.requireCustomerBeforeManualProduct()) {
                    row.fitemcode = '';
                    this.hydrateRowFromMeta(row, null);
                    return;
                }
                this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode), true);
                row.fnoacak = this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak(row.uid);
                this.applyInvoicePrice(row);
                this.onRowUpdated(index);
            },

            getSelectedCustomerCode() {
                return (document.getElementById('customerCodeHidden')?.value || document.getElementById(
                    'modal_filter_customer_id')?.value || '').trim();
            },

            requireCustomerBeforeManualProduct() {
                if (this.getSelectedCustomerCode()) return true;
                this.showCustomerRequired = true;
                return false;
            },

            isRowSavable(row) {
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

            onPrPicked(e, source = 'SO') {
                const {
                    header,
                    items
                } = e.detail || {};
                if (!items || !Array.isArray(items)) return;
                this.addManyFromPR(header, items, source);
            },

            addManyFromPR(header, items, source = 'SO') {
                const existing = new Set(this.getCurrentItemKeys());
                let added = 0;

                window.syncInvoiceTempoFromSource?.(header?.ftempohr ?? header?.ftempo ?? 0, {
                    force: true
                });
                window.syncInvoiceSalesmanFromSource?.(header);
                window.syncInvoicePpnFromSource?.(header);

                const internalNoteInput = document.getElementById('fketinternal');
                if (internalNoteInput) {
                    const currentValue = String(internalNoteInput.value ?? '').trim();
                    const sourceValue = String(header?.fketinternal ?? '').trim();
                    if (sourceValue !== '' && currentValue === '') {
                        internalNoteInput.value = sourceValue;
                    }
                }

                items.forEach(src => {
                    const itemcode = (src.fitemcode ?? '').toString().trim();
                    const satuan = (src.fsatuan ?? '').toString().trim();
                    const meta = this.productMeta(itemcode);
                    const displayQty = Number(src.fqtyremain_dokumen ?? 0) > 0 ?
                        Number(src.fqtyremain_dokumen) :
                        this.qtyKecilToUnit(src.fqtyremain, satuan, meta);

                    const row = {
                        uid: cryptoRandom(),
                        fitemcode: itemcode,
                        fitemname: src.fitemname ?? '',
                        fsatuan: satuan,
                        frefdtno: src.frefdtno ?? '',
                        frefno_display: src.frefno_display ?? (header?.fstockmtno ?? header?.fprno ?? header
                            ?.fsono ?? ''),
                        fnouref: src.fnouref ?? '',
                        frefpr: src.frefpr ?? (header?.fstockmtno ?? header?.fsono ?? ''),
                        fnouref: (src.frefdtno ?? src.fnouref ?? null),
                        frefno_display: src.frefno_display ?? header?.fstockmtno ?? header?.fsono ?? '',
                        frefso: source === 'SO' ? (header?.fsono ?? '') : '',
                        frefsrj: source === 'SRJ' ? (header?.fstockmtno ?? '') : '',
                        fnoacak: this.generateUniqueNoAcak(),
                        frefnoacak: this.normalizeRefNoAcak(source === 'SRJ' ? (src.fnoacak ?? '') : (src
                            .frefnoacak ?? src.fnoacak ?? '')),
                        frefpr: (src.frefpr ?? header?.fsono ?? header?.fpono ?? header?.fstockmtno ?? '')
                            .toString().trim(),
                        fprhid: src.fprhid ?? header?.fprhid ?? '',

                        fqty: displayQty > 0 ? displayQty : 1,
                        fterima: Number(src.fterima ?? 0),
                        fprice: Number(src.fprice ?? src.fharga ?? 0),
                        fdisc: src.fdisc ?? src.fdiscpersen ?? 0,
                        ftotal: Number(src.ftotal ?? 0),
                        fdesc: src.fdesc ?? '',
                        fketdt: src.fketdt ?? '',
                        units: Array.isArray(src.units) && src.units.length ? src.units : [src.fsatuan]
                            .filter(Boolean),
                        maxqty: Math.max(0, Number(src.fqtyremain ?? 0)),
                        maxqty_unit: 'kecil',
                    };

                    const key = this.itemKey({
                        fitemcode: row.fitemcode,
                        frefdtno: row.frefdtno
                    });

                    if (existing.has(key)) return;

                    this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode));

                    const rowLimit = this.getRowQtyLimit(row);
                    if (!(rowLimit > 0)) return;
                    row.fpriceInput = this.fmt(row.fprice);
                    const nextRow = {
                        ...this.createRow(),
                        ...row,
                    };
                    const emptyIndex = this.getFirstEmptyRowIndex();

                    if (emptyIndex >= 0) {
                        this.savedItems.splice(emptyIndex, 1, nextRow);
                        this.onRowUpdated(emptyIndex);
                    } else {
                        this.savedItems.push(nextRow);
                        this.onRowUpdated(this.savedItems.length - 1);
                    }
                    existing.add(key);
                    added++;
                });

                this.ensureMinimumRows();
                this.ensureTrailingRow();
                this.recalcTotals();
            },

            removeSaved(i) {
                this.savedItems.splice(i, 1);
                this.syncDescList?.();
                this.ensureMinimumRows();
                this.ensureTrailingRow();
                this.recalcTotals();
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
                return `${(it.fitemcode ?? '').toString().trim()}::${(it.frefdtno ?? '').toString().trim()}`;
            },

            getCurrentItemKeys() {
                return this.submitItems.map(it => this.itemKey(it));
            },

            normalizeRestoredRow(item, index = 0) {
                const keepOldValues = Boolean(item?.is_restored_old);
                const row = {
                    ...newRow(),
                    ...(item || {}),
                    uid: item?.uid || `restored-${index}`,
                    formIndex: item?.formIndex ?? this.allocateFormIndex(),
                };
                const oldValues = keepOldValues ? {
                    fitemname: row.fitemname,
                    fsatuan: row.fsatuan,
                    frefdtno: row.frefdtno,
                    frefno_display: row.frefno_display,
                    fnouref: row.fnouref,
                    frefpr: row.frefpr,
                    frefso: row.frefso,
                    frefsrj: row.frefsrj,
                } : null;
                row.fnoacak = this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak();
                row.frefnoacak = this.normalizeRefNoAcak(row.frefnoacak);

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

                const meta = this.productMeta(row.fitemcode);
                if (meta?.units?.length) {
                    row.units = [...new Set([...row.units, ...meta.units])];
                } else if (row.fsatuan && !row.units.includes(row.fsatuan)) {
                    row.units.unshift(row.fsatuan);
                }

                const preferredUnit = (row.fsatuan || '').toString().trim();
                if (preferredUnit !== '') {
                    const matchedUnit = row.units.find((u) => (u ?? '').toString().trim().toLowerCase() ===
                        preferredUnit.toLowerCase()) || preferredUnit;
                    row.units = [matchedUnit, ...row.units.filter((u) => (u ?? '').toString().trim().toLowerCase() !==
                        matchedUnit.toLowerCase())];
                    row.fsatuan = matchedUnit;
                }

                if (meta?.unit_ratios) {
                    row.unit_ratios = row.unit_ratios || meta.unit_ratios;
                }

                if (oldValues) {
                    Object.assign(row, oldValues);
                    if (row.fsatuan && !row.units.includes(row.fsatuan)) {
                        row.units.unshift(row.fsatuan);
                    }
                }

                this.recalc(row);
                return row;
            },

            allocateFormIndex() {
                const index = Number(this.nextFormIndex || 0);
                this.nextFormIndex = index + 1;
                return index;
            },

            createRow(overrides = {}) {
                const row = {
                    ...newRow(),
                    uid: overrides.uid || cryptoRandom(),
                    formIndex: overrides.formIndex ?? this.allocateFormIndex(),
                    ...overrides,
                    fsatuan: (overrides.fsatuan ?? '').toString().trim(),
                    fnoacak: this.normalizeNoAcak(overrides.fnoacak) || this.generateUniqueNoAcak(overrides.uid ||
                        null),
                    frefnoacak: this.normalizeRefNoAcak(overrides.frefnoacak),
                };
                row.fpriceInput = this.fmt(row.fprice);
                return row;
            },

            pruneEmptyRows() {
                const filled = this.savedItems.filter(row => this.rowHasContent(row));
                this.savedItems = filled.length ? filled : [];
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

            focusRowPrice(index) {
                this.$nextTick(() => document.getElementById(`price_row_${index}`)?.focus());
            },

            focusRowDisc(index) {
                this.$nextTick(() => document.getElementById(`disc_row_${index}`)?.focus());
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
                this.$watch('includePPN', () => this.recalcTotals());
                this.$watch('fapplyppn', () => this.recalcTotals());
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
                this.savedItems = Array.isArray(this.savedItems) ?
                    this.savedItems.map((item, index) => this.normalizeRestoredRow(item, index)) : [];
                this.pruneEmptyRows();
                this.ensureMinimumRows();
                this.ensureTrailingRow();
                window.addEventListener('pr-picked', (e) => this.onPrPicked(e, 'SO'), {
                    passive: true
                });
                window.addEventListener('srj-picked', (e) => this.onPrPicked(e, 'SRJ'), {
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
                    row.fitemcode = (product.fprdcode || '').toString();
                    row.frefcode = product.fprdcode || product.id || '';
                    this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode), true);
                    row.fnoacak = this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak(row.uid);
                    this.applyInvoicePrice(row);
                    this.onRowUpdated(index);
                    this.focusRowQty(index);
                }, {
                    passive: true
                });

                this.autoLoadSuratJalan();
            },

            async autoLoadSuratJalan() {
                const suratJalanId = @json($autoLoadSuratJalanId ?? null);
                if (!suratJalanId) return;

                try {
                    const url = @json(route('suratjalan.items', ['id' => 'SRJ_ID_PLACEHOLDER']))
                        .replace('SRJ_ID_PLACEHOLDER', suratJalanId);
                    const res = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!res.ok) {
                        throw new Error(`Server error: ${res.status}`);
                    }

                    const json = await res.json();
                    const items = (json.items || []).filter(src => Number(src.maxqty ?? src.fqtyremain ?? 0) > 0);
                    if (items.length === 0) {
                        window.toast?.warning('Semua item SRJ ini sudah habis difakturkan.');
                        return;
                    }

                    window.applyTransactionCustomerSelection?.({
                        fcustomercode: json.header?.fsupplier ?? json.header?.fcustno ?? '',
                        fcustomername: json.header?.fsuppliername ?? json.header?.fcustomername ?? '',
                    });

                    this.onPrPicked({
                        detail: {
                            header: json.header,
                            items: items
                        }
                    }, 'SRJ');
                } catch (e) {
                    console.error('Auto-load Surat Jalan gagal:', e);
                    window.toast?.error(`Gagal mengambil detail Surat Jalan: ${e.message}`);
                }
            },

            openBrowseFor(index) {
                if (!this.requireCustomerBeforeManualProduct()) {
                    return;
                }
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
                frefcode: '',
                units: [],
                fsatuan: '',
                frefdtno: '',
                frefno_display: '',
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
    window.PRODUCT_MAP = window.PRODUCT_MAP || @json($productMap ?? []);
</script>
@include('components.transaction.browse-customer-script')
@include('components.transaction.browse-salesman-script')

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
                window.transactionReferenceModalHelper.confirmAddUniques(this, 'pr-picked');
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
                    const customerCode = (document.getElementById('customerCodeHidden')?.value || document
                        .getElementById('modal_filter_customer_id')?.value || '').toString().trim();
                    const params = new URLSearchParams({
                        search: this.search ?? '',
                        per_page: this.perPage,
                        page: this.currentPage,
                        customer_code: customerCode,
                    });

                    const res = await fetch(`{{ route('penerimaanbarang.pickable') }}?` + params.toString(), {
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
                            title: @json('Produk Tidak Tersedia'),
                            html: `${@json('Produk :name sudah tidak tersedia.').replace('__NAME__', `<b>${row.fprdname}</b>`)}<br><br>${@json('Penyimpanan dibatalkan.')}`,
                            confirmButtonColor: '#f59e0b', // Warna orange amber
                            confirmButtonText: @json('Kembali')
                        });
                        return; // Hentikan proses, jangan tambahkan ke tabel
                    }
                    const url = `{{ route('penerimaanbarang.items', ['id' => 'PR_ID_PLACEHOLDER']) }}`
                        .replace('PR_ID_PLACEHOLDER', row.fprhid);

                    const res = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const json = await res.json();
                    window.applyTransactionCustomerSelection?.({
                        fcustomercode: json.header?.fcustno ?? row.fcustno ?? row.fcustomercode ?? '',
                        fcustomername: row.fcustomername ?? row.fsuppliername ?? '',
                    });

                    const items = json.items || [];
                    const currentKeys = new Set((window.getCurrentItemKeys?.() || []).map(String));

                    const keyOf = (src) =>
                        `${(src.fitemcode ?? '').toString().trim()}::${(src.frefdtno ?? '').toString().trim()}`;

                    const duplicates = items.filter(src => currentKeys.has(keyOf(src)));
                    const uniques = items.filter(src => !currentKeys.has(keyOf(src)));

                    if (duplicates.length > 0) {
                        this.openDupModal(row, duplicates, uniques);
                        return; // tunggu aksi user di modal
                    }

                    // tidak ada duplikat â†’ langsung kirim semua item yang unik (atau 'items' kalau mau semua)
                    window.dispatchEvent(new CustomEvent('pr-picked', {
                        detail: {
                            header: row,
                            items
                        } // jika ingin hanya unik, ganti 'items' â†’ 'uniques'
                    }));
                    this.closeModal();

                    window.dispatchEvent(new CustomEvent('pr-picked', {
                        detail: {
                            header: row,
                            items
                        }
                    }));

                    this.closeModal();
                } catch (e) {
                    console.error(e);
                    window.showAppErrorAlert('TERJADI KESALAHAN', @json('GAGAL MENGAMBIL DETAIL PR.'));
                }
            },
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

@include('components.transaction.invoice-so-modal-script')
@include('components.transaction.invoice-srj-modal-script')

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    @include('components.transaction.browse-product-script', [
        'showControls' => true,
        'showPagination' => true,
        'supportsForEdit' => true,
    ])
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const customerAdvanceWarnings = @json($customerAdvanceWarnings ?? []);
            const warningBox = document.getElementById('customerAdvanceWarningBox');
            const warningText = document.getElementById('customerAdvanceWarningText');
            const hiddenInput = document.getElementById('customerCodeHidden');
            const selectInput = document.getElementById('modal_filter_customer_id');
            const updateCustomerAdvanceWarning = (customerCode = null) => {
                if (!warningBox || !warningText) {
                    return;
                }

                const code = (customerCode ?? hiddenInput?.value ?? selectInput?.value ?? '').toString().trim();
                const warning = customerAdvanceWarnings[code] ?? null;

                if (!warning || !warning.message) {
                    warningBox.classList.add('hidden');
                    warningText.textContent = '';
                    return;
                }

                warningText.textContent = warning.message;
                warningBox.classList.remove('hidden');
            };

            if (hiddenInput) {
                hiddenInput.addEventListener('change', (e) => {
                    updateCustomerAdvanceWarning(e.target.value);
                });
                hiddenInput.addEventListener('input', (e) => {
                    updateCustomerAdvanceWarning(e.target.value);
                });
            }
            if (selectInput) {
                selectInput.addEventListener('change', (e) => {
                    updateCustomerAdvanceWarning(e.target.value);
                });
            }

            // Listen to customer-selected custom event
            window.addEventListener('customer-selected', (e) => {
                const code = e.detail?.fcustomercode;
                updateCustomerAdvanceWarning(code);
            });

            // Initial check
            setTimeout(() => {
                updateCustomerAdvanceWarning();
            }, 100);
        });
    </script>
@endpush
