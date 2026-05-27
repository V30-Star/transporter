@extends('layouts.app')

@section('title', $action === 'delete' ? 'Faktur Penjualan - Delete' : ($action === 'view' ? 'Faktur Penjualan - View' : 'Faktur Penjualan - Edit'))

@section('content')
    @php
        $permissions = explode(',', session('user_restricted_permissions', ''));
        $canEditPermission = in_array('updateInvoice', $permissions, true);
        $canDeletePermission = in_array('deleteInvoice', $permissions, true);
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
    @php
        $usageLocked = !empty($isUsageLocked);
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
        $initialEditInvoiceItems = [];

        foreach ($oldInvoiceItemCodes as $index => $itemCode) {
            $code = trim((string) $itemCode);
            $name = trim((string) ($oldInvoiceItemNames[$index] ?? ''));
            if ($code === '' && $name === '') {
                continue;
            }

            $unit = trim((string) ($oldInvoiceUnits[$index] ?? ''));
            $refSo = trim((string) ($oldInvoiceRefSos[$index] ?? ''));
            $refSrj = trim((string) ($oldInvoiceRefSrjs[$index] ?? ''));
            $refDtNo = trim((string) ($oldInvoiceRefNos[$index] ?? ''));

            $initialEditInvoiceItems[] = [
                'uid' => 'old-invoice-edit-' . $index,
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
                'maxqty' => max(0, (float) ($oldInvoiceQtys[$index] ?? 0)),
            ];
        }
    @endphp
    @if ($usageLocked)
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
                            {{ 'Faktur Penjualan' }}
                            {{ $action === 'delete' ? 'Tidak Dapat Dihapus' : 'Tidak Dapat Diedit' }}
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
    <div x-data="{ open: true }">
        <div class="bg-white rounded shadow p-6 md:p-8 max-w-[96rem] mx-auto">
            @if ($action === 'delete')
                <div class="space-y-4">

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">Cabang</label>
                            <input type="text" class="w-full border rounded px-3 py-2 bg-gray-200 cursor-not-allowed"
                                value="{{ trim(($fbranchcode ?? '') . ($fcabang ?? '' ? ' - ' . $fcabang : '')) }}" disabled>
                            <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                        </div>

                        {{-- SO# --}}
                        <div class="lg:col-span-4" x-data="{ autoCode: false }">
                            <label class="block text-sm font-medium mb-1">Faktur#</label>
                            <div class="flex items-center gap-3">
                                <input type="text" name="fsono" value="{{ old('fsono', $invoice->fsono) }}"
                                    class="w-full border rounded px-3 py-2" :disabled="autoCode" readonly
                                    :class="autoCode ? 'bg-gray-200 cursor-not-allowed text-gray-500' : 'bg-white'">

                                <label class="inline-flex items-center select-none">
                                    <input type="checkbox" x-model="autoCode" disabled>
                                    <span class="ml-2 text-sm text-gray-700">Auto</span>
                                </label>
                            </div>
                            <p x-show="autoCode" class="text-[10px] text-blue-600 mt-1">* Nomor akan digenerate
                                otomatis
                                saat simpan</p>
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">Faktur Pajak#</label>
                            <input type="text" name="ftaxno" value="{{ old('ftaxno', $invoice->ftaxno) }}"
                                class="w-full border rounded px-3 py-2 @error('ftaxno') border-red-500 @enderror" readonly>
                            @error('ftaxno')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">Type</label>
                            <select name="ftypesales" id="ftypesales" x-model.number="ftypesales" x-init="ftypesales = 0"
                                disabled
                                class="w-full border rounded px-3 py-2 @error('ftypesales') border-red-500 @enderror">
                                <option value="0">Penjualan</option>
                                <option value="1">Uang Muka</option>
                            </select>
                            @error('ftypesales')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Tanggal --}}
                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">Tanggal</label>
                            <input disabled type="date" name="fsodate"
                                value="{{ old('fsodate') ?? date('Y-m-d', strtotime($invoice->fsodate)) }}"
                                class="w-full border rounded px-3 py-2 bg-gray-200 @error('fsodate') border-red-500 @enderror">
                            @error('fsodate')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Customer --}}
                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium mb-1">Customer</label>
                            <div class="flex">
                                <div class="relative flex-1" for="modal_filter_customer_id">
                                    <select id="modal_filter_customer_id" name="filter_customer_id"
                                        class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 bg-gray-200 cursor-not-allowed"
                                        disabled>
                                        <option value=""></option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->fcustomercode }}"
                                                data-ftempo="{{ (int) ($customer->ftempo ?? 0) }}"
                                                {{ old('fcustno', $invoice->fcustno) == $customer->fcustomercode ? 'selected' : '' }}>
                                                {{ $customer->fcustomername }} ({{ $customer->fcustomercode }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-0" role="button" aria-label="Browse Customer"
                                        @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))">
                                    </div>
                                </div>
                                <input type="hidden" name="fcustno" id="customerCodeHidden"
                                    value="{{ old('fcustno', $invoice->fcustno) }}">
                            </div>
                            @error('fcustno')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Salesman --}}
                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium mb-1">Salesman</label>
                            <div class="flex">
                                <div class="relative flex-1" for="modal_filter_salesman_id">
                                    <select id="modal_filter_salesman_id" name="filter_salesman_id"
                                        class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 bg-gray-200 cursor-not-allowed"
                                        disabled>
                                        <option value=""></option>
                                        @foreach ($salesmans as $salesman)
                                            <option value="{{ $salesman->fsalesmancode }}"
                                                {{ old('fsalesman', $invoice->fsalesman) == $salesman->fsalesmancode ? 'selected' : '' }}>
                                                {{ $salesman->fsalesmanname }} ({{ $salesman->fsalesmancode }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-0" role="button" aria-label="Browse Salesman"
                                        @click="window.dispatchEvent(new CustomEvent('salesman-browse-open'))">
                                    </div>
                                </div>
                                <input type="hidden" name="fsalesman" id="salesmanCodeHidden"
                                    value="{{ old('fsalesman', $invoice->fsalesman) }}">
                            </div>
                            @error('fsalesman')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">TOP (Hari)</label>
                            <input type="number" id="ftempohr" name="ftempohr" value="{{ old('ftempohr', '0') }}"
                                readonly
                                class="w-full border rounded px-3 py-2 @error('ftempohr') border-red-500 @enderror"
                                placeholder="Masukkan jumlah hari">
                            @error('ftempohr')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">Tgl. Jatuh Tempo</label>
                            <input type="date" id="fjatuhtempo" name="fjatuhtempo" readonly
                                value="{{ old('fjatuhtempo') ?? date('Y-m-d', strtotime($invoice->fjatuhtempo)) }}"
                                readonly
                                class="w-full border rounded px-3 py-2 bg-gray-100 @error('fjatuhtempo') border-red-500 @enderror">
                            @error('fjatuhtempo')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
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

                                // Initial calculation
                                calculateDueDate();
                            });
                        </script>

                        <div class="lg:col-span-12">
                            <label class="block text-sm font-medium">Keterangan</label>
                            <textarea name="fket" rows="3" disabled
                                class="w-full border rounded px-3 py-2 @error('fket') border-red-500 @enderror"
                                placeholder="Keterangan isi di sini...">{{ old('fket', $invoice->fket) }}</textarea>
                            @error('fket')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div x-data="itemsTable()" x-init="init()" class="mt-6 space-y-2">

                        {{-- DETAIL ITEM (tabel input) --}}
                        <h3 class="text-base font-semibold text-gray-800">Detail Item</h3>

                        <div class="overflow-auto border rounded">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="p-2 text-left w-10">#</th>
                                        <th class="p-2 text-left w-42">Kode Produk</th>
                                        <th class="p-2 text-left w-96">Nama Produk</th>
                                        <th class="p-2 text-left w-36">Satuan</th>
                                        <th class="p-2 text-left w-36">No.Ref</th>
                                        <th class="p-2 text-right w-36 whitespace-nowrap">Jumlah</th>
                                        <th class="p-2 text-right w-32 whitespace-nowrap">@ Harga</th>
                                        <th class="p-2 text-right w-36 whitespace-nowrap">Disc. %</th>
                                        <th class="p-2 text-right w-36 whitespace-nowrap">Total Harga</th>
                                    </tr>
                                </thead>

                                <template x-for="(it, i) in savedItems" :key="it.uid || `item-${i}`">
                                    <tbody>
                                        <!-- ROW UTAMA - SAVED ITEM (READ ONLY) -->
                                        <tr class="border-t border-b align-top">
                                            <td class="p-2" x-text="i + 1"></td>
                                            <td class="p-2 font-mono" x-text="it.fitemcode"></td>
                                            <td class="p-2 text-gray-800">
                                                <div x-text="it.fitemname"></div>
                                                <!-- Tampilkan deskripsi yang sudah tersimpan (READ ONLY) -->
                                                <div x-show="it.fdesc" class="mt-1 text-xs">
                                                    <span
                                                        class="inline-block px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 mr-2">Deskripsi</span>
                                                    <span class="align-middle text-gray-600" x-text="it.fdesc"></span>
                                                </div>
                                            </td>
                                            <td class="p-2" x-text="it.fsatuan"></td>
                                            <td class="p-2" x-text="it.frefno_display || it.frefcode || '-'"></td>
                                            <td class="p-2 text-right" x-text="fmt(it.fqty)"></td>
                                            <td class="p-2 text-right" x-text="fmt(it.fprice)"></td>
                                            <td class="p-2 text-right" x-text="it.fdisc && it.fdisc.toString().includes('+') ? it.fdisc : fmt(it.fdisc)"></td>
                                            <td class="p-2 text-right" x-text="fmt(it.ftotal)"></td>
                                        </tr>

                                        <!-- Hidden inputs row -->
                                        <tr class="hidden">
                                            <td colspan="9">
                                                <input type="hidden" name="fitemcode[]" :value="it.fitemcode">
                                                <input type="hidden" name="fitemname[]" :value="it.fitemname">
                                                <input type="hidden" name="fsatuan[]" :value="it.fsatuan">
                                                <input type="hidden" name="frefcode[]" :value="it.frefcode">
                                                <input type="hidden" name="fnouref[]" :value="it.fnouref">
                                                <input type="hidden" name="frefpr[]" :value="it.frefpr">
                                                <input type="hidden" name="frefso[]" :value="it.frefso">
                                                <input type="hidden" name="frefsrj[]" :value="it.frefsrj">
                                                <input type="hidden" name="fnoacak[]" :value="it.fnoacak">
                                                <input type="hidden" name="frefnoacak[]" :value="it.frefnoacak">
                                                <input type="hidden" name="fqty[]" :value="it.fqty">
                                                <input type="hidden" name="fterima[]" :value="it.fterima">
                                                <input type="hidden" name="fprice[]" :value="it.fprice">
                                                <input type="hidden" name="fdisc[]" :value="it.fdisc">
                                                <input type="hidden" name="ftotal[]" :value="it.ftotal">
                                                <input type="hidden" name="fdesc[]" :value="it.fdesc">
                                                <input type="hidden" name="fketdt[]" :value="it.fketdt">
                                            </td>
                                        </tr>

                                        <!-- TIDAK ADA TEXTAREA DI SINI! -->
                                    </tbody>
                                </template>
                                <!-- ROW EDIT UTAMA -->
                                <tr x-show="editingIndex !== null" class="border-t align-top" x-cloak>
                                    <!-- # -->
                                    <td class="p-2" x-text="(editingIndex ?? 0) + 1"></td>

                                    <!-- Kode Produk -->
                                    <td class="p-2">
                                        <div class="flex">
                                            <input type="text" class="flex-1 border rounded-l px-2 py-1 font-mono"
                                                x-ref="editCode" x-model.trim="editRow.fitemcode"
                                                @input="onCodeTypedRow(editRow)"
                                                @keydown.enter.prevent="handleEnterOnCode('edit')">
                                        </div>
                                    </td>

                                    <!-- Nama Produk (readonly) -->
                                    <td class="p-2">
                                        <div class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm leading-5 whitespace-normal break-words"
                                            x-text="editRow.fitemname"></div>
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
                                        <input type="text"
                                            class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                            :value="editRow.frefcode" disabled placeholder="Ref PR">
                                    </td>

                                    <!-- Qty -->
                                    <td class="p-2 text-right">
                                        <input type="number" class="border rounded px-2 py-1 w-24 text-right"
                                            x-ref="editQty" x-model.number="editRow.fqty"
                                            @input="
                                                        recalc(editRow);
                                                    "
                                            @blur="
                                                        enforceQtyRow(editRow);
                                                        recalc(editRow);
                                                    "
                                            @keydown.enter.prevent="$refs.editTerima?.focus()">
                                        <div class="text-xs text-gray-400 mt-0.5 text-right">
                                            <span x-show="editRow.fitemcode" x-html="formatStockLimit(editRow)"></span>
                                        </div>
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
                                        <input type="text" class="border rounded px-2 py-1 w-24 text-right"
                                            x-ref="editDisc" x-model="editRow.fdisc" @input="recalc(editRow)"
                                            @keydown.enter.prevent="applyEdit()" placeholder="10+2">
                                    </td>

                                    <!-- Total Harga (readonly) -->
                                    <td class="p-2 text-right" x-text="fmt(editRow.ftotal)"></td>
                                </tr>

                                <!-- ROW EDIT DESC -->
                                <tr x-show="editingIndex !== null" class="border-b" x-cloak>
                                    <td class="p-0"></td>
                                    <td class="p-0"></td>
                                    <td class="p-0"></td>
                                    <td class="p-0"></td>
                                    <td class="p-0"></td>
                                    <td class="p-0"></td>
                                    <td class="p-0"></td>
                                    <td class="p-0"></td>
                                </tr>

                                <!-- ROW DRAFT DESC -->
                                <tr class="border-b">
                                    <td class="p-0"></td>
                                    <td class="p-0"></td>
                                    <td class="p-0"></td>
                                    <td class="p-0"></td>
                                    <td class="p-0"></td>
                                    <td class="p-0"></td>
                                    <td class="p-0"></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- ===== Trigger: Add tr_prh dari panel kanan ===== -->
                        <div x-data="prhFormModal()">
                            <!-- Trigger: Add PR dari panel kanan -->
                            <div class="mt-3 flex justify-between items-start gap-4">
                                <div class="w-full flex justify-start mb-3">
                                </div>
                                <!-- Kanan: Panel Totals -->
                                <div class="w-1/2">
                                    <div class="rounded-lg border bg-gray-50 p-3 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-700">Total Harga</span>
                                            <span class="min-w-[140px] text-right font-medium"
                                                x-text="formatTransactionAmount(totalHarga)"></span>
                                        </div>
                                        <div class="flex items-center justify-between gap-6">
                                            <!-- Checkbox -->
                                            <div class="flex items-center">
                                                <input id="fapplyppn" type="checkbox" name="fapplyppn" value="1"
                                                    x-model="includePPN" disabled
                                                    class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                <label for="fapplyppn" class="ml-2 text-sm font-medium text-gray-700">
                                                    <span class="font-bold">PPN</span>
                                                </label>
                                            </div>

                                            <!-- Dropdown Include / Exclude (tengah) -->
                                            <div class="flex items-center gap-2">
                                                <select disabled id="includePPN" name="includePPN"
                                                    x-model.number="fapplyppn" x-init="fapplyppn = 0"
                                                    :disabled="!(includePPN || fapplyppn)"
                                                    class="w-28 h-9 px-2 text-sm leading-tight border rounded transition-opacity appearance-none
                                                           disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed">
                                                    <option disabled value="0">Exclude</option>
                                                    <option disabled value="1">Include</option>
                                                </select>
                                            </div>

                                            <!-- Input Rate + Nominal (kanan) -->
                                            <div class="flex items-center gap-2">
                                                <input disabled type="number" min="0" max="100"
                                                    step="0.01" x-model.number="ppnRate" readonly
                                                    :disabled="!(includePPN || fapplyppn)"
                                                    class="w-20 h-9 px-2 text-sm leading-tight text-right border rounded transition-opacity
                                                            [appearance:textfield]
                                                            [&::-webkit-outer-spin-button]:appearance-none
                                                            [&::-webkit-inner-spin-button]:appearance-none
                                                            disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed">
                                                <span class="text-sm">%</span>
                                                <span class="min-w-[140px] text-right font-medium"
                                                    x-text="rupiah(ppnAmount)"></span>
                                            </div>

                                        </div>

                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-sm text-gray-700">Discount</span>
                                            <div class="flex items-center gap-2">
                                                <input type="number" min="0" max="100" step="0.01"
                                                    name="fdiscpersen" x-model.number="headerDiscPercent" disabled
                                                    class="w-20 h-9 px-2 text-sm leading-tight text-right border rounded transition-opacity
                                                            [appearance:textfield]
                                                            [&::-webkit-outer-spin-button]:appearance-none
                                                            [&::-webkit-inner-spin-button]:appearance-none
                                                            disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed">
                                                <span class="text-sm">%</span>
                                                <span class="min-w-[140px] text-right font-medium"
                                                    x-text="rupiah(headerDiscAmount)"></span>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-700">Total Setelah Disc</span>
                                            <span class="min-w-[140px] text-right font-medium"
                                                x-text="rupiah(totalSetelahDisc)"></span>
                                        </div>

                                        <div class="border-t my-1"></div>

                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-semibold text-gray-800">Grand Total</span>
                                            <span class="min-w-[140px] text-right text-lg font-semibold"
                                                x-text="rupiah(grandTotal)"></span>
                                        </div>

                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-semibold text-gray-800">Grand Total
                                                (RP)</span>
                                            <span class="min-w-[140px] text-right text-lg font-semibold"
                                                x-text="rupiah(grandTotal)"></span>
                                        </div>
                                    </div>

                                    <!-- Hidden inputs for submit -->
                                    <input type="hidden" name="famountgross" :value="totalHarga">
                                    <input type="hidden" name="" :value="ppnAmount">
                                    <input type="hidden" name="famountso" :value="grandTotal">
                                    <input type="hidden" name="famountpopajak" :value="ppnRate">
                                    <input type="hidden" name="fdiscpersen" :value="headerDiscPercent">
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
                                            <div class="rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800" x-text="descItemName || '-'"></div>
                                        </div>
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
                        <div x-show="showNoItems && savedItems.length === 0" x-cloak
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
                                        Anda belum menambahkan item apa pun pada tabel. Silakan isi baris â€œDetail
                                        Itemâ€
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
                    </div>

                    @php
                        $canApproval = in_array(
                            'approveFakturPenjualan',
                            explode(',', session('user_restricted_permissions', '')),
                        );
                    @endphp

                    @if ($canApproval)
                        <div
                            class="mt-6 mx-auto max-w-2xl rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            <div class="font-semibold">Status Persetujuan Kredit</div>
                            <div class="mt-1">
                                {{ !empty($invoice->fuseracc) ? 'Sudah disetujui oleh: ' . $invoice->fuseracc : 'Belum ada persetujuan kredit pada transaksi ini.' }}
                            </div>
                        </div>
                    @endif

                    <div class="mt-6 flex justify-center space-x-4">
                        @if ($canDeletePermission)
                            @if ($usageLocked)
                                <button type="button" disabled title="{{ $usageLockMessage }}"
                                    class="bg-red-300 text-white px-6 py-2 rounded flex items-center cursor-not-allowed opacity-70">
                                    <x-heroicon-o-lock-closed class="w-5 h-5 mr-2" />
                                    Hapus
                                </button>
                            @else
                                <button type="button" onclick="showDeleteModal()"
                                    class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 flex items-center">
                                    <x-heroicon-o-trash class="w-5 h-5 mr-2" />
                                    Hapus
                                </button>
                            @endif
                        @endif
                        <button type="button" onclick="window.location.href='{{ route('invoice.index') }}'"
                            class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                            <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                            Kembali
                        </button>
                    </div>

                    {{-- ============================================ --}}
                    {{-- MODE EDIT: FORM EDITABLE                    --}}
                    {{-- ============================================ --}}
                @else
                    <form id="invoiceForm" action="{{ route('invoice.update', parameters: $invoice->ftranmtid) }}"
                        method="POST" class="mt-6" data-form-draft="true"
                        data-draft-key="invoice:edit:{{ $invoice->ftranmtid }}"
                        data-tranmtid="{{ $invoice->ftranmtid }}" x-data="{ showNoItems: false }"
                        @submit.prevent="
        if ('{{ $action }}' === 'view') { return }
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
                        @method('PATCH')
                        <input type="hidden" name="fneedacc" id="invoiceNeedAcc"
                            value="{{ old('fneedacc', $invoice->fneedacc ?? '0') }}">
                        <input type="hidden" name="fuseracc" id="invoiceUserAcc"
                            value="{{ old('fuseracc', $invoice->fuseracc ?? '') }}">
                        <fieldset {{ $action === 'view' ? 'disabled' : '' }}>

                        {{-- HEADER FORM --}}
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                            <div class="lg:col-span-4">
                                <label class="block text-sm font-medium">Cabang</label>
                                <input type="text"
                                    class="w-full border rounded px-3 py-2 bg-gray-200 cursor-not-allowed"
                                    value="{{ trim(($fbranchcode ?? '') . ($fcabang ?? '' ? ' - ' . $fcabang : '')) }}" disabled>
                                <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                            </div>

                            {{-- SO# --}}
                            <div class="lg:col-span-4" x-data="{ autoCode: false }">
                                <label class="block text-sm font-medium mb-1">Faktur#</label>
                                <div class="flex items-center gap-3">
                                    <input type="text" name="fsono" value="{{ old('fsono', $invoice->fsono) }}"
                                        class="w-full border rounded px-3 py-2" :disabled="autoCode"
                                        :class="autoCode ? 'bg-gray-200 cursor-not-allowed text-gray-500' : 'bg-white'">

                                    <label class="inline-flex items-center select-none">
                                        <input type="checkbox" x-model="autoCode">
                                        <span class="ml-2 text-sm text-gray-700">Auto</span>
                                    </label>
                                </div>
                                <p x-show="autoCode" class="text-[10px] text-blue-600 mt-1">* Nomor akan
                                    digenerate
                                    otomatis
                                    saat simpan</p>
                            </div>

                            <div class="lg:col-span-4">
                                <label class="block text-sm font-medium">Faktur Pajak#</label>
                                <input type="text" name="ftaxno" value="{{ old('ftaxno', $invoice->ftaxno) }}"
                                    class="w-full border rounded px-3 py-2 @error('ftaxno') border-red-500 @enderror">
                                @error('ftaxno')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="lg:col-span-4">
                                <label class="block text-sm font-medium">Type</label>
                                <select name="ftypesales" id="ftypesales" x-model.number="ftypesales"
                                    x-init="ftypesales = {{ old('ftypesales', $invoice->ftypesales) }}"
                                    class="w-full border rounded px-3 py-2 @error('ftypesales') border-red-500 @enderror">
                                    <option value="0">Penjualan</option>
                                    <option value="1">Uang Muka</option>
                                </select>
                                @error('ftypesales')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Tanggal --}}
                            <div class="lg:col-span-4">
                                <label class="block text-sm font-medium">Tanggal</label>
                                <input type="date" id="fsodate" name="fsodate"
                                    value="{{ old('fsodate') ?? date('Y-m-d', strtotime($invoice->fsodate)) }}"
                                    class="w-full border rounded px-3 py-2 @error('fsodate') border-red-500 @enderror">
                                @error('fsodate')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Customer --}}
                            <div class="lg:col-span-4">
                                <label class="block text-sm font-medium mb-1">Customer</label>
                                <div class="flex">
                                    <div class="relative flex-1" for="modal_filter_customer_id">
                                        <select id="modal_filter_customer_id" name="filter_customer_id"
                                            class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
                                            disabled>
                                            <option value=""></option>
                                            @foreach ($customers as $customer)
                                                <option value="{{ $customer->fcustomercode }}"
                                                    data-fkodefp="{{ $customer->fkodefp }}"
                                                    {{ old('fcustno', $invoice->fcustno) == $customer->fcustomercode ? 'selected' : '' }}>
                                                    {{ $customer->fcustomername }} ({{ $customer->fcustomercode }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @if ($action !== 'view')
                                            <div class="absolute inset-0" role="button" aria-label="Browse Customer"
                                                @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))">
                                            </div>
                                        @endif
                                    </div>
                                    <input type="hidden" name="fcustno" id="customerCodeHidden"
                                        value="{{ old('fcustno', $invoice->fcustno) }}">
                                    @if ($action !== 'view')
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
                                    @endif
                                </div>
                                @error('fcustno')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="lg:col-span-4">
                                <label class="block text-sm font-medium">Kode FP</label>
                                <input type="text" name="fkodefp" id="invoiceFkodefp"
                                    value="{{ old('fkodefp', $invoice->fkodefp ?? optional($invoice->customer)->fkodefp) }}"
                                    readonly
                                    class="w-full border rounded px-3 py-2 bg-gray-100 @error('fkodefp') border-red-500 @enderror">
                                @error('fkodefp')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Salesman --}}
                            <div class="lg:col-span-4">
                                <label class="block text-sm font-medium mb-1">Salesman</label>
                                <div class="flex">
                                    <div class="relative flex-1" for="modal_filter_salesman_id">
                                        <select id="modal_filter_salesman_id" name="filter_salesman_id"
                                            class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
                                            disabled>
                                            <option value=""></option>
                                            @foreach ($salesmans as $salesman)
                                                <option value="{{ $salesman->fsalesmancode }}"
                                                    {{ old('fsalesman', $invoice->fsalesman) == $salesman->fsalesmancode ? 'selected' : '' }}>
                                                    {{ $salesman->fsalesmanname }} ({{ $salesman->fsalesmancode }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @if ($action !== 'view')
                                            <div class="absolute inset-0" role="button" aria-label="Browse Salesman"
                                                @click="window.dispatchEvent(new CustomEvent('salesman-browse-open'))">
                                            </div>
                                        @endif
                                    </div>
                                    <input type="hidden" name="fsalesman" id="salesmanCodeHidden"
                                        value="{{ old('fsalesman', $invoice->fsalesman) }}">
                                    @if ($action !== 'view')
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
                                    @endif
                                </div>
                                @error('fsalesman')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium">TOP (Hari)</label>
                                <input type="number" id="ftempohr" name="ftempohr"
                                    value="{{ old('ftempohr', $invoice->ftempohr) }}"
                                    class="w-full border rounded px-3 py-2 @error('ftempohr') border-red-500 @enderror"
                                    placeholder="Masukkan jumlah hari">
                                @error('ftempohr')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium">Tgl. Jatuh Tempo</label>
                                <input type="date" id="fjatuhtempo" name="fjatuhtempo"
                                    value="{{ old('fjatuhtempo') ?? date('Y-m-d', strtotime($invoice->fjatuhtempo)) }}"
                                    readonly
                                    class="w-full border rounded px-3 py-2 bg-gray-100 @error('fjatuhtempo') border-red-500 @enderror">
                                @error('fjatuhtempo')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
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

                                    // Initial calculation
                                    calculateDueDate();
                                });
                            </script>

                            <div class="lg:col-span-12">
                                <label class="block text-sm font-medium">Keterangan</label>
                                <textarea name="fket" rows="3"
                                    class="w-full border rounded px-3 py-2 @error('fket') border-red-500 @enderror"
                                    placeholder="Keterangan isi di sini...">{{ old('fket', $invoice->fket) }}</textarea>
                                @error('fket')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div x-data="itemsTable()" x-init="init()" class="mt-6 space-y-2">

                            {{-- DETAIL ITEM (tabel input) --}}
                            <h3 class="text-base font-semibold text-gray-800">Detail Item</h3>

                            <div class="overflow-auto border rounded">
                                <table class="min-w-full text-sm balanced-detail-table"
                                    data-skip-auto-detail-style="true">
                                    <colgroup>
                                        <col style="width:2%;">
                                        <col style="width:16%;">
                                        <col style="width:25%;">
                                        <col style="width:9%;">
                                        <col style="width:16%;">
                                        <col style="width:8%;">
                                        <col style="width:8%;">
                                        <col style="width:6%;">
                                        <col style="width:7%;">
                                        <col style="width:3%;">
                                    </colgroup>
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="p-2 text-left w-10">#</th>
                                            <th class="p-2 text-left w-42">Kode Produk</th>
                                            <th class="p-2 text-left w-96">Nama Produk</th>
                                            <th class="p-2 text-left w-36">Satuan</th>
                                            <th class="p-2 text-left w-36">No.Ref</th>
                                            <th class="p-2 text-right w-36 whitespace-nowrap">Jumlah</th>
                                            <th class="p-2 text-right w-32 whitespace-nowrap">@ Harga</th>
                                            <th class="p-2 text-right w-36 whitespace-nowrap">Disc. %</th>
                                            <th class="p-2 text-right w-36 whitespace-nowrap">Total Harga</th>
                                            @if ($action !== 'view')
                                                <th class="p-2 text-center w-28">Aksi</th>
                                            @endif
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
                                                            x-model.trim="it.fitemcode"
                                                            @input="onCodeTypedRow(it, i)"
                                                            @keydown.enter.prevent="focusRowUnit(it, i)">
                                                        @if ($action !== 'view')
                                                            <button type="button" @click="openBrowseFor(i)"
                                                                class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50"
                                                                title="Cari Produk">
                                                                <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                            </button>
                                                        @endif
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
                                                        <select class="w-full border rounded px-2 py-1 text-sm focus:ring-1 focus:ring-blue-500"
                                                            :id="'unit_row_' + i"
                                                            x-model="it.fsatuan"
                                                            @change="onRowUpdated(i)"
                                                            @keydown.enter.prevent="focusRowQty(i)">
                                                            <template x-for="u in it.units" :key="u">
                                                                <option :value="u" x-text="u"></option>
                                                            </template>
                                                        </select>
                                                    </template>
                                                    <template x-if="!(it.units && it.units.length > 1)">
                                                        <div class="px-2 py-1 text-sm text-gray-600 bg-gray-50 border rounded"
                                                            x-text="it.fsatuan || '-'"></div>
                                                    </template>
                                                </td>
                                                <td class="p-2">
                                                    <input type="text"
                                                        class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                                        :value="it.frefno_display || it.frefcode || '-'"
                                                        disabled>
                                                </td>
                                                <td class="p-2 text-right">
                                                    <input type="number"
                                                        class="w-full border rounded px-2 py-1 text-right text-sm"
                                                        :id="'qty_row_' + i"
                                                        x-model.number="it.fqty"
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
                                                        :id="'price_row_' + i"
                                                        x-model="it.fpriceInput"
                                                        @focus="activeRow = it.uid; focusPriceInput(it); $event.target.select()"
                                                        @blur="activeRow = null; blurPriceInput(it)"
                                                        @input="onPriceInput(it); onRowUpdated(i)"
                                                        @keydown.enter.prevent="focusRowDisc(i)">
                                                </td>
                                                <td class="p-2 text-right">
                                                    <input type="text"
                                                        class="w-full border rounded px-2 py-1 text-right text-sm"
                                                        :id="'disc_row_' + i"
                                                        :value="normalizeDiscountValue(it.fdisc)"
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
                                                @if ($action !== 'view')
                                                    <td class="p-2 text-center">
                                                        <button type="button" @click="removeSaved(i)"
                                                            class="inline-flex h-8 w-8 items-center justify-center rounded bg-red-100 text-red-600 hover:bg-red-200"
                                                            title="Hapus baris">-</button>
                                                    </td>
                                                @endif
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <div class="hidden">
                                <template x-for="(it, i) in submitItems" :key="'submit-' + (it.uid || i)">
                                    <div>
                                        <input type="hidden" name="fitemcode[]" :value="it.fitemcode">
                                        <input type="hidden" name="fitemname[]" :value="it.fitemname">
                                        <input type="hidden" name="fsatuan[]" :value="it.fsatuan">
                                        <input type="hidden" name="frefcode[]" :value="it.frefcode">
                                        <input type="hidden" name="fnouref[]" :value="it.fnouref">
                                        <input type="hidden" name="frefpr[]" :value="it.frefpr">
                                        <input type="hidden" name="frefso[]" :value="it.frefso">
                                        <input type="hidden" name="frefsrj[]" :value="it.frefsrj">
                                        <input type="hidden" name="fnoacak[]" :value="it.fnoacak">
                                        <input type="hidden" name="frefnoacak[]" :value="it.frefnoacak">
                                        <input type="hidden" name="fqty[]" :value="it.fqty">
                                        <input type="hidden" name="fterima[]" :value="it.fterima">
                                        <input type="hidden" name="fprice[]" :value="it.fprice">
                                        <input type="hidden" name="fdisc[]" :value="it.fdisc">
                                        <input type="hidden" name="ftotal[]" :value="it.ftotal">
                                        <input type="hidden" name="fdesc[]" :value="it.fdesc">
                                        <input type="hidden" name="fketdt[]" :value="it.fketdt">
                                    </div>
                                </template>
                            </div>

                            <div class="mt-3 flex justify-between items-start gap-4">
                                <div class="flex flex-wrap items-center gap-3 flex-shrink-0">
                                    @if ($action !== 'view')
                                        <div x-data="srjFormModal()" class="mt-3">
                                        <button type="button" @click="openSrjModal()"
                                            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 ml-4">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                            Add SRJ
                                        </button>

                                        <div x-show="showSrjModal" x-cloak x-transition.opacity
                                            class="fixed inset-0 z-50 flex items-center justify-center p-4">
                                            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                                                @click="closeSrjModal()">
                                            </div>

                                            <div class="relative bg-white rounded-2xl shadow-2xl w-[96vw] max-w-[110rem] flex flex-col overflow-hidden"
                                                style="height: 85vh;">
                                                <div
                                                    class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-indigo-50 to-white">
                                                    <div>
                                                        <h3 class="text-xl font-bold text-gray-800">
                                                            {{ 'Pilih Surat Jalan' }}</h3>
                                                    </div>
                                                    <button type="button" @click="closeSrjModal()"
                                                        class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-bold text-gray-700 text-sm">
                                                        {{ 'Tutup' }}
                                                    </button>
                                                </div>

                                                <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                                                    <div id="srjTableControls"></div>
                                                </div>

                                                <div class="flex-1 overflow-x-auto overflow-y-hidden px-6" style="min-height: 0;">
                                                    <div class="bg-white">
                                                        <table id="srjTable"
                                                            class="min-w-full text-sm display nowrap stripe hover"
                                                            style="width:100%">
                                                            <thead class="sticky top-0 z-10">
                                                                <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
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
                                                                        class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                        {{ 'Alamat' }}</th>
                                                                    <th
                                                                        class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                        {{ 'No Ref' }}</th>
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
                                                                <span x-text="d.fitemcode" class="font-bold"></span> -
                                                                <span x-text="d.fitemname"></span>
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

                                    <div x-data="soFormModal()" class="mt-3">
                                        <button type="button" @click="openModal()"
                                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                            {{ 'Tambah SO' }}
                                        </button>

                                        <div x-show="show" x-cloak x-transition.opacity
                                            class="fixed inset-0 z-50 flex items-center justify-center p-4">
                                            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                                                @click="closeModal()">
                                            </div>

                                            <div class="relative bg-white rounded-2xl shadow-2xl w-[96vw] max-w-[110rem] flex flex-col overflow-hidden"
                                                style="height: 85vh;">
                                                <div
                                                    class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-teal-50 to-white">
                                                    <div>
                                                        <h3 class="text-xl font-bold text-gray-800">
                                                            {{ 'Pilih Sales Order' }}</h3>
                                                        <p class="text-sm text-gray-500 mt-0.5">
                                                            {{ 'Pilih Sales Order yang diinginkan' }}</p>
                                                    </div>
                                                    <button type="button" @click="closeModal()"
                                                        class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-bold text-gray-700 text-sm">
                                                        {{ 'Tutup' }}
                                                    </button>
                                                </div>

                                                <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                                                    <div id="poTableControls"></div>
                                                </div>

                                                <div class="flex-1 overflow-x-auto overflow-y-hidden px-6" style="min-height: 0;">
                                                    <div class="bg-white">
                                                        <table id="poTable"
                                                            class="min-w-full text-sm display nowrap stripe hover"
                                                            style="width:100%">
                                                            <thead class="sticky top-0 z-10">
                                                                <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
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
                                                                        {{ 'Alamat' }}</th>
                                                                    <th
                                                                        class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                        {{ 'No Ref' }}</th>
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
                                                    <div id="poTablePagination"></div>
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
                                                    <h3 class="text-lg font-semibold text-gray-800">
                                                        {{ 'Item Duplikat Ditemukan' }}</h3>
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
                                                                <li class="px-3 py-2 text-sm text-gray-500 text-center">
                                                                    {{ 'Tidak ada contoh.' }}</li>
                                                            </template>
                                                        </ul>
                                                    </div>
                                                </div>

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
                                    @endif
                                </div>
                            </div>

                            <!-- ===== Trigger: Add tr_prh dari panel kanan ===== -->
                            <div>
                                <!-- Trigger: Add PR dari panel kanan -->
                                <div class="mt-3 flex justify-between items-start gap-4">
                                    <div class="w-full flex justify-start mb-3">
                                    </div>
                                    <!-- Kanan: Panel Totals -->
                                    <div class="w-1/2">
                                        <div class="rounded-lg border bg-gray-50 p-3 space-y-2">
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm text-gray-700">Total Harga (Net)</span>
                                                <span class="min-w-[140px] text-right font-medium"
                                                    x-text="formatTransactionAmount(netTotal)"></span>
                                            </div>
                                            <div class="flex items-center justify-between gap-6">
                                                <!-- Checkbox -->
                                                <div class="flex items-center">
                                                    <input id="fincludeppn_input" type="checkbox" name="fincludeppn"
                                                        value="1" x-model="includePPN"
                                                        :disabled="action === 'delete' || action === 'view'"
                                                        class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                    <label for="fincludeppn_input"
                                                        class="ml-2 text-sm font-medium text-gray-700">
                                                        <span class="font-bold">PPN</span>
                                                    </label>
                                                </div>

                                                <!-- Dropdown Include / Exclude (tengah) -->
                                                <div class="flex items-center gap-2">
                                                    <select id="fapplyppn_input" name="fapplyppn"
                                                        x-model.number="fapplyppn"
                                                        :disabled="!(includePPN || fapplyppn) || action === 'delete' ||
                                                            action === 'view'"
                                                        class="w-28 h-9 px-2 text-sm leading-tight border rounded transition-opacity appearance-none
                                                           disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed">
                                                        <option value="0">Exclude</option>
                                                        <option value="1">Include</option>
                                                    </select>
                                                </div>

                                                <!-- Input Rate + Nominal (kanan) -->
                                                <div class="flex items-center gap-2">
                                                    <input type="number" min="0" max="100" step="0.01"
                                                        x-model.number="ppnRate"
                                                        :disabled="!(includePPN || fapplyppn) || action === 'delete' ||
                                                            action === 'view'"
                                                        class="w-20 h-9 px-2 text-sm leading-tight text-right border rounded transition-opacity
                                                            [appearance:textfield]
                                                            [&::-webkit-outer-spin-button]:appearance-none
                                                            [&::-webkit-inner-spin-button]:appearance-none
                                                            disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed">
                                                    <span class="text-sm">%</span>
                                                    <span class="min-w-[140px] text-right font-medium"
                                                        x-text="rupiah(ppnAmount)"></span>
                                                </div>

                                            </div>

                                            <div class="flex items-center justify-between gap-3">
                                                <span class="text-sm text-gray-700">Discount</span>
                                                <div class="flex items-center gap-2">
                                                    <input type="number" min="0" max="100" step="0.01"
                                                        name="fdiscpersen" x-model.number="headerDiscPercent"
                                                        :disabled="action === 'delete' || action === 'view'"
                                                        class="w-20 h-9 px-2 text-sm leading-tight text-right border rounded transition-opacity
                                                            [appearance:textfield]
                                                            [&::-webkit-outer-spin-button]:appearance-none
                                                            [&::-webkit-inner-spin-button]:appearance-none
                                                            disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed">
                                                    <span class="text-sm">%</span>
                                                    <span class="min-w-[140px] text-right font-medium"
                                                        x-text="rupiah(headerDiscAmount)"></span>
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm text-gray-700">Total Setelah Disc</span>
                                                <span class="min-w-[140px] text-right font-medium"
                                                    x-text="rupiah(totalSetelahDisc)"></span>
                                            </div>

                                            <div class="border-t my-1"></div>

                                            <div class="flex items-center justify-between">
                                                <span class="text-sm font-semibold text-gray-800">Grand
                                                    Total</span>
                                                <span class="min-w-[140px] text-right text-lg font-semibold"
                                                    x-text="rupiah(grandTotal)"></span>
                                            </div>

                                            <div class="flex items-center justify-between">
                                                <span class="text-sm font-semibold text-gray-800">Grand Total
                                                    (RP)</span>
                                                <span class="min-w-[140px] text-right text-lg font-semibold"
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
                                            <h3 class="text-lg font-semibold text-gray-800">Isi Deskripsi Item
                                            </h3>
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
                                                <div class="rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800" x-text="descItemName || '-'"></div>
                                            </div>
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

                                <input type="hidden" id="itemsCount" :value="submitItems.length">
                            </div>
                        </fieldset>

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
                                            Anda belum menambahkan item apa pun pada tabel. Silakan isi baris
                                            â€œDetail
                                            Itemâ€
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
                                            Customer wajib dipilih sebelum input produk manual. Untuk Tambah SO atau Add
                                            SRJ,
                                            customer tidak wajib dipilih terlebih dahulu.
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

                            <x-transaction.browse-customer-modal />

                            <x-transaction.browse-salesman-modal />

                            <x-transaction.browse-product-modal show-controls="true" show-pagination="true" />

                            @php
                                $canApproval = in_array(
                                    'approveFakturPenjualan',
                                    explode(',', session('user_restricted_permissions', '')),
                                );
                            @endphp

                            <div class="mt-8 flex justify-center gap-4">
                                @if ($action !== 'view' && $canEditPermission)
                                    @if ($usageLocked)
                                        <button type="button" disabled title="{{ $usageLockMessage }}"
                                            class="bg-blue-300 text-white px-6 py-2 rounded flex items-center cursor-not-allowed opacity-70">
                                            <x-heroicon-o-lock-closed class="w-5 h-5 mr-2" /> Simpan
                                        </button>
                                    @else
                                        <button type="submit"
                                            class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                                            <x-heroicon-o-check class="w-5 h-5 mr-2" /> Simpan
                                        </button>
                                    @endif
                                @endif
                                <button type="button" @click="window.location.href='{{ route('invoice.index') }}'"
                                    class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" /> {{ $action === 'view' ? 'Kembali' : 'Keluar' }}
                                </button>
                            </div>
                    </form>
            @endif
        </div>
    </div>
    {{-- ============================================ --}}
    {{-- MODAL & TOAST (HANYA UNTUK MODE DELETE)     --}}
    {{-- ============================================ --}}
    @if ($action === 'delete' && $canDeletePermission)
        {{-- Modal Delete --}}
        <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Konfirmasi Hapus invoice ini?</h3>
                <form id="deleteForm" action="{{ route('invoice.destroy', $invoice->ftranmtid) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="flex justify-end space-x-2">
                        <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400"
                            id="btnTidak">
                            Tidak
                        </button>
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

                fetch('{{ route('invoice.destroy', $invoice->ftranmtid) }}', {
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
                        showToast(data.message || 'Data berhasil dihapus', true);

                        setTimeout(() => {
                            window.location.href = '{{ route('invoice.index') }}';
                        }, 500);
                    })
                    .catch(error => {
                        btnYa.disabled = false;
                        btnTidak.disabled = false;
                        btnYa.textContent = 'Ya, Hapus';
                        showToast('Gagal menghapus data.', false);
                    });
            }
        </script>
    @endif
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
<script>
    // Fallback toast system if not defined
    if (!window.toast) {
        window.toast = {
            success: (msg) => {
                if (typeof window.showAppSuccessToast === 'function') window.showAppSuccessToast(msg);
                else console.log('Success:', msg);
            },
            error: (msg) => {
                if (typeof window.showAppErrorAlert === 'function') window.showAppErrorAlert('Terjadi Kesalahan', msg);
                else console.error('Error:', msg);
            },
            info: (msg) => {
                if (typeof window.showAppInfoAlert === 'function') window.showAppInfoAlert('Information', msg);
                else console.info('Info:', msg);
            }
        };
    }

    window.INVOICE_CUSTOMER_FP_MAP = @json(collect($customers)->mapWithKeys(fn($customer) => [
                (string) $customer->fcustomercode => (string) ($customer->fkodefp ?? ''),
            ]));

    window.syncInvoiceCustomerTaxCode = function(payload = null) {
        const kodeFpInput = document.getElementById('invoiceFkodefp');
        if (!kodeFpInput) {
            return;
        }

        const select = document.getElementById('modal_filter_customer_id');
        const hidden = document.getElementById('customerCodeHidden');
        const normalize = (value) => String(value ?? '').trim();
        const eventCode = typeof payload === 'object' && payload !== null ? normalize(payload.fcustomercode) : '';
        const eventValue = typeof payload === 'object' && payload !== null ? normalize(payload.fkodefp) : normalize(payload);
        const customerCode = eventCode || normalize(hidden?.value) || normalize(select?.value);
        const selectedOption = customerCode ?
            [...(select?.options || [])].find(option => normalize(option.value) === customerCode) :
            select?.selectedOptions?.[0];
        const optionValue = normalize(selectedOption?.dataset?.fkodefp);
        const mappedValue = customerCode ? (window.INVOICE_CUSTOMER_FP_MAP?.[customerCode] || '') : '';

        kodeFpInput.value = eventValue || optionValue || mappedValue || '';
    };

    window.syncInvoiceTempoFromSource = function(days) {
        const tempoInput = document.getElementById('ftempohr');
        if (!tempoInput) return;
        const numericDays = Number(days ?? 0);
        tempoInput.value = Number.isFinite(numericDays) ? String(Math.max(0, numericDays)) : '0';
        tempoInput.dispatchEvent(new Event('input', { bubbles: true }));
        tempoInput.dispatchEvent(new Event('change', { bubbles: true }));
    };

    window.syncInvoiceTempoFromCustomer = function(payload = null) {
        const normalize = (value) => String(value ?? '').trim();
        const select = document.getElementById('modal_filter_customer_id');
        const hidden = document.getElementById('customerCodeHidden');
        const customerCode = normalize(payload?.fcustomercode) || normalize(hidden?.value) || normalize(select?.value);
        const selectedOption = customerCode ?
            [...(select?.options || [])].find(option => normalize(option.value) === customerCode) :
            select?.selectedOptions?.[0];
        const eventTempo = normalize(payload?.ftempo);
        const optionTempo = normalize(selectedOption?.dataset?.ftempo);
        window.syncInvoiceTempoFromSource(eventTempo || optionTempo || '0');
    };

    document.addEventListener('customer-selected', function(event) {
        window.syncInvoiceCustomerTaxCode(event.detail || null);
        window.syncInvoiceTempoFromCustomer(event.detail || null);
    });

    document.addEventListener('DOMContentLoaded', function() {
        const select = document.getElementById('modal_filter_customer_id');
        if (select) {
            select.addEventListener('change', function() {
                window.syncInvoiceCustomerTaxCode();
                window.syncInvoiceTempoFromCustomer();
            });
        }

        window.syncInvoiceCustomerTaxCode();
        window.syncInvoiceTempoFromCustomer();
    });

    window.getInvoiceDuplicateCode = function(form) {
        const seen = new Set();
        const inputs = Array.from(form.querySelectorAll('input[name="fitemcode[]"]'));

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
                @json("Gagal memeriksa persetujuan customer.\nSilakan coba lagi."),
                {
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
                units: @json(array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2]))),
                stock: @json($p->fminstock ?? 0),
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
            savedItems: @json(count($initialEditInvoiceItems) ? $initialEditInvoiceItems : $savedItems ?? []),
            minimumVisibleRows: @json(count($initialEditInvoiceItems) ? count($initialEditInvoiceItems) + 5 : count($savedItems ?? []) + 5),
            browseTarget: null,
            editingIndex: null,
            editRow: newRow(),
            showDescModal: false,
            descValue: '',
            descSavedIndex: null,
            descItemName: '',

            totalHarga: 0,
            headerDiscPercent: @json((float) old('fdiscpersen', $invoice->fdiscpersen ?? 0)),
            ppnRate: @json($invoice->fppnpersen ?? 11),

            initialGrandTotal: @json($invoice->famountso ?? 0),
            initialPpnAmount: @json($invoice->famountpajak ?? 0),

            includePPN: @json($invoice->fincludeppn == '1'),
            fapplyppn: @json((int) ($invoice->fapplyppn ?? 0)),
            action: @js($action ?? 'edit'),

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
                if (cleaned === '') return '0.00';
                if (!cleaned.includes('+')) {
                    const num = Number(cleaned);
                    if (Number.isFinite(num)) {
                        return num.toFixed(2);
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

            recalc(row) {
                row.fqty = Math.max(0, +row.fqty || 0);
                row.fprice = Math.max(0, +row.fprice || 0);
                if (typeof row.fpriceInput === 'undefined') {
                    row.fpriceInput = row.fprice.toFixed(2);
                }
                const discPercent = this.parseDiscount(row.fdisc);
                const subtotal = row.fqty * row.fprice;
                const discAmount = subtotal * (discPercent / 100);
                row.ftotal = +(subtotal - discAmount).toFixed(2);
                this.recalcTotals();
            },

            sanitizePriceValue(value) {
                const raw = (value ?? '').toString().replace(',', '.').replace(/[^0-9.]/g, '');
                const parts = raw.split('.');
                if (parts.length <= 1) return raw;
                return `${parts.shift()}.${parts.join('')}`;
            },

            focusPriceInput(row) {
                const price = Math.max(0, +row.fprice || 0);
                row.fpriceInput = price > 0 ? String(price) : '';
            },

            onPriceInput(row) {
                row.fpriceInput = this.sanitizePriceValue(row.fpriceInput);
                row.fprice = Math.max(0, +(row.fpriceInput || 0));
            },

            blurPriceInput(row) {
                row.fprice = Math.max(0, +(row.fpriceInput || 0));
                row.fpriceInput = row.fprice.toFixed(2);
                this.recalc(row);
            },

            recalcTotals() {
                this.totalHarga = this.savedItems.reduce((sum, item) => {
                    if (!this.isRowSavable(item)) return sum;
                    return sum + item.ftotal;
                }, 0);
            },

            showToast(message, type = 'success') {
                if (window.toast) {
                    if (type === 'error' || type === 'danger') window.toast.error(message);
                    else if (type === 'warning') window.toast.info(message);
                    else window.toast.success(message);
                } else if (typeof window.showAppErrorAlert === 'function' || typeof window.showAppInfoAlert === 'function' || typeof window.showAppSuccessToast === 'function') {
                    if (type === 'error' || type === 'danger') window.showAppErrorAlert('Terjadi Kesalahan', message);
                    else if (type === 'warning') window.showAppWarningAlert('Warning', message);
                    else window.showAppSuccessToast(message, { timer: 3000 });
                } else {
                    console.info('Toast:', message);
                }
            },

            productMeta(code) {
                const key = (code || '').trim();
                const meta = window.PRODUCT_MAP?.[key];
                if (!meta) {
                    return {
                        name: '',
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
                // On Invoice Edit: do not show/compute stock/max qty limit.
                // Qty limiting is handled only by reference max qty validation (if present on the row).
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

            onRowUpdated(index = null) {
                const row = typeof index === 'number' ? this.savedItems[index] : null;
                if (row) {
                    this.recalc(row);
                }
                this.recalcTotals();
                this.ensureTrailingRow(index);
            },

            getRowQtyLimit(row) {
                return Math.max(0, Number(row?.maxqty ?? 0) || 0);
            },

            validateReferenceQty(row, showToast = true) {
                const hasRef = String(row?.frefso ?? '').trim() !== '' ||
                    String(row?.frefsrj ?? '').trim() !== '';
                if (!hasRef) return true;

                const limit = this.getRowQtyLimit(row);
                if (limit <= 0) {
                    row.fqty = 0;
                    if (showToast) window.toast?.error('Sisa referensi sudah habis.');
                    return false;
                }

                const qty = Number(row?.fqty ?? 0);
                if (qty > limit) {
                    row.fqty = limit;
                    if (showToast) window.toast?.error(
                        `Jumlah melebihi sisa referensi. Maksimal ${limit} ${row.fsatuan || ''}`.trim());
                }

                return Number(row?.fqty ?? 0) > 0;
            },

            enforceQtyRow(row) {
                const n = +row.fqty;

                if (!Number.isFinite(n)) {
                    row.fqty = 0;
                    return;
                }
                if (n < 0) row.fqty = 0;
                this.validateReferenceQty(row, false);

            },

            hydrateRowFromMeta(row, meta, forceDefaultUnit = false) {
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
                if (forceDefaultUnit || !units.includes(row.fsatuan)) {
                    row.fsatuan = units[0] || '';
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

            onPrPicked(e, source = 'SO') {
                const {
                    header,
                    items
                } = e.detail || {};
                if (!items || !Array.isArray(items)) return;
                this.addManyFromPR(header, items, source);
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

            addManyFromPR(header, items, source = 'SO') {
                const existing = new Set(this.getCurrentItemKeys());
                let added = 0;

                if (source === 'SO') {
                    window.syncInvoiceTempoFromSource?.(header?.ftempohr ?? 0);
                }

                const refNo = source === 'SRJ' ?
                    (header?.fstockmtno ?? '') :
                    (header?.fsono ?? '');

                items.forEach(src => {
                    const itemcode = (src.fitemcode ?? '').toString().trim();
                    const frefdtno = (src.frefdtno ?? src.frefcode ?? '').toString().trim();
                    const remainingQty = Math.max(0, Number(src.maxqty ?? src.fqtyremain ?? src.fqty ?? 0));
                    const key = `${itemcode}::${frefdtno}`;

                    if (existing.has(key)) return;
                    existing.add(key);

                    const row = {
                        uid: cryptoRandom(),
                        fitemcode: itemcode,
                        fitemname: (src.fitemname ?? '').toString().trim(),
                        fsatuan: (src.fsatuan ?? '').toString().trim(),
                        frefdtno: frefdtno,
                        fnouref: (src.frefdtno ?? src.fnouref ?? null),
                        frefno_display: refNo || src.frefcode || '',
                        frefcode: src.frefcode || '',
                        frefpr: refNo,
                        frefso: source === 'SO' ? (header?.fsono ?? '') : '',
                        frefsrj: source === 'SRJ' ? (header?.fstockmtno ?? '') : '',
                        fnoacak: this.generateUniqueNoAcak(),
                        frefnoacak: this.normalizeRefNoAcak(src.frefnoacak ?? src.fnoacak ?? ''),
                        fqty: (src.fqty !== null && src.fqty !== undefined && Number(src.fqty) > 0) ?
                            Number(src.fqty) : 1,
                        fprice: Number(src.fprice ?? src.fharga ?? 0),
                        ftotal: 0,
                        fdesc: src.fdesc ? src.fdesc.toString().trim() : '',
                        units: [(src.fsatuan ?? '').toString().trim()].filter(Boolean),
                        maxqty: Math.max(0, Number(src.maxqty ?? src.fqtyremain ?? 0)),
                    };

                    const rowLimit = this.getRowQtyLimit(row);
                    if (!(rowLimit > 0)) return;
                    this.recalc(row);
                    this.validateReferenceQty(row, false);
                    this.savedItems.push({
                        ...this.createRow(),
                        ...row,
                    });
                    added++;
                    this.onRowUpdated(this.savedItems.length - 1);
                });

                if (added > 0) window.toast?.success(@json('Berhasil menambahkan :count item').replace('__COUNT__', added));
                this.ensureMinimumRows();
                this.ensureTrailingRow();
                this.recalcTotals();
            },

            removeSaved(i) {
                this.savedItems.splice(i, 1);
                this.ensureMinimumRows();
                this.ensureTrailingRow();
                this.recalcTotals();
            },

            get submitItems() {
                return this.savedItems.filter(row => this.isRowSavable(row));
            },

            itemKey(it) {
                return `${(it.fitemcode ?? '').toString().trim()}::${(it.frefdtno ?? '').toString().trim()}`;
            },

            getCurrentItemKeys() {
                return this.submitItems.map(it => this.itemKey(it));
            },

            normalizeRestoredRow(item, index = 0) {
                const row = {
                    ...newRow(),
                    ...(item || {}),
                    uid: item?.uid || `restored-${index}`
                };
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

                if (meta?.unit_ratios) {
                    row.unit_ratios = row.unit_ratios || meta.unit_ratios;
                }

                this.recalc(row);
                return row;
            },

            createRow(overrides = {}) {
                const row = {
                    ...newRow(),
                    uid: overrides.uid || cryptoRandom(),
                    ...overrides,
                    fnoacak: this.normalizeNoAcak(overrides.fnoacak) || this.generateUniqueNoAcak(overrides.uid || null),
                    frefnoacak: this.normalizeRefNoAcak(overrides.frefnoacak),
                };
                row.fpriceInput = Number(row.fprice || 0).toFixed(2);
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

            init() {
                this.recalcTotals();
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
                this.savedItems = Array.isArray(this.savedItems)
                    ? this.savedItems.map((item, index) => this.normalizeRestoredRow(item, index))
                    : [];
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
                    this.rows.splice(this.browseTarget, 1, {
                        ...this.rows[this.browseTarget]
                    });
                    const targetRow = this.savedItems[index];
                    targetRow.fitemcode = (product.fprdcode || '').toString();
                    this.onCodeTypedRow(targetRow, index);
                    targetRow.fnoacak = this.normalizeNoAcak(targetRow.fnoacak) || this.generateUniqueNoAcak(targetRow.uid);
                    this.focusRowQty(index);
                }, {
                    passive: true
                });
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
            showCustomerRequired: false,
        };

        function newRow() {
            return {
                uid: null,
                fitemcode: '',
                fitemname: '',
                units: [],
                fsatuan: '',
                frefdtno: '',
                frefno_display: '',
                frefcode: '',
                frefpr: '',
                fnoacak: '',
                frefnoacak: '',
                fqty: 0,
                maxqty: 0,
                fprice: 0,
                fpriceInput: '0.00',
                fdisc: 0,
                ftotal: 0,
                fdesc: '',
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
    window.prhFormModal = function() {
        return {
            show: false,
            table: null,

            // Duplikasi modal state
            showDupModal: false,
            dupCount: 0,
            dupSample: [],
            pendingHeader: null,
            pendingUniques: [],

            initDataTable() {
                if (this.table) {
                    this.table.destroy();
                }

                this.table = $('#prTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('tr_poh.pickable') }}",
                        type: 'GET',
                        data: function(d) {
                            return {
                                draw: d.draw,
                                start: d.start,
                                length: d.length,
                                search: d.search.value,
                                // Menambahkan parameter order untuk server-side processing
                                order_column: d.columns[d.order[0].column].data,
                                order_dir: d.order[0].dir,
                                customer_code: document.getElementById('customerCodeHidden')?.value || ''
                            };
                        },
                        // Karena kita sudah menggunakan parameter start/length standar DataTables,
                        // properti dataSrc bisa dihilangkan jika backend langsung mengembalikan format DataTables.
                        // Jika backend tetap menggunakan pagination Laravel, dataSrc perlu dipertahankan. 
                        // Kita asumsikan backend sudah disesuaikan untuk server-side DataTables penuh.
                        // Jika masih menggunakan format pagination Laravel, kita bisa menggunakan:
                        // dataSrc: function(json) { return json.data; }
                    },
                    columns: [{
                            data: 'fprno',
                            name: 'fprno',
                            className: 'font-mono text-sm' // Styling konsisten
                        },
                        {
                            data: 'fsuppliername',
                            name: 'fsuppliername',
                            className: 'text-sm', // Styling konsisten
                            render: function(data) {
                                return data || '-';
                            }
                        },
                        {
                            data: 'fprdate',
                            name: 'fprdate',
                            className: 'text-sm', // Styling konsisten
                            render: function(data) {
                                return formatDate(data);
                            }
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center',
                            render: function(data, type, row) {
                                // Menggunakan styling yang lebih seragam
                                return '<button type="button" class="btn-pick px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150">{{ 'Pilih' }}</button>';
                            }
                        }
                    ],
                    pageLength: 10,
                    lengthMenu: [
                        [10, 25, 50, 100], // Menambahkan 100
                        [10, 25, 50, 100]
                    ],
                    // Menggunakan DOM custom yang sudah diseragamkan
                    dom: '<"flex justify-between items-center mb-4"f<"ml-auto"l>>rtip',

                    language: {
                        processing: @json('Memuat data...'),
                        search: @json('Search' . ':'),
                        lengthMenu: @json('Tampilkan _MENU_'),
                        info: @json('Menampilkan _START_ - _END_ dari _TOTAL_ data'),
                        infoEmpty: @json('Tidak ada data'),
                        infoFiltered: @json('(disaring dari _MAX_ total data)'),
                        zeroRecords: @json('Tidak ada data yang ditemukan'),
                        emptyTable: @json('Tidak ada data tersedia'),
                        paginate: {
                            first: @json('Pertama'),
                            last: @json('Terakhir'),
                            next: @json('Selanjutnya'),
                            previous: @json('Sebelumnya')
                        }
                    },
                    order: [
                        [2, 'desc']
                    ], // Sort by tanggal terbaru
                    autoWidth: false,
                    initComplete: function() {
                        const api = this.api();
                        const $container = $(api.table().container());

                        // Style search input (disamakan dengan Customer)
                        $container.find('.dt-search .dt-input, .dataTables_filter input').css({
                            width: '300px', // Menggunakan 300px agar konsisten dengan customerBrowser
                            padding: '8px 12px',
                            border: '2px solid #e5e7eb',
                            borderRadius: '8px',
                            fontSize: '14px'
                        }).focus();

                        // Style length select (disamakan dengan Customer)
                        $container.find('.dt-length select, .dataTables_length select').css({
                            padding: '6px 32px 6px 10px',
                            border: '2px solid #e5e7eb',
                            borderRadius: '8px',
                            fontSize: '14px'
                        });
                    }
                });

                // Handle button click (Menggunakan self for consistency)
                const self = this;
                $('#prTable').off('click', '.btn-pick').on('click', '.btn-pick', function() {
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

            // --- Duplikasi Handlers (Tetap sama, logic sudah baik) ---
            openDupModal(header, duplicates, uniques) {
                window.transactionReferenceModalHelper.openDupModal(this, header, duplicates, uniques);
            },

            closeDupModal() {
                window.transactionReferenceModalHelper.closeDupModal(this);
            },

            confirmAddUniques() {
                window.transactionReferenceModalHelper.confirmAddUniques(this, 'pr-picked');
            },

            async pick(row) {
                try {
                    // Tampilkan loading indicator (opsional)

                    const url = `{{ route('tr_poh.items', ['id' => 'PR_ID_PLACEHOLDER']) }}`
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
                    // Pastikan window.getCurrentItemKeys() tersedia
                    const currentKeys = new Set((window.getCurrentItemKeys?.() || []).map(String));

                    const keyOf = (src) =>
                        `${(src.fitemcode ?? '').toString().trim()}::${(src.frefcode ?? '').toString().trim()}`;

                    const duplicates = items.filter(src => currentKeys.has(keyOf(src)));
                    const uniques = items.filter(src => !currentKeys.has(keyOf(src)));

                    if (duplicates.length > 0) {
                        this.openDupModal(row, duplicates, uniques);
                        return;
                    }

                    // Tidak ada duplikat
                    window.dispatchEvent(new CustomEvent('pr-picked', {
                        detail: {
                            header: row,
                            items
                        }
                    }));

                    this.closeModal();
                } catch (e) {
                    console.error(e);
                    // Menggunakan custom alert/modal, bukan alert() bawaan browser
                    // Idealnya: tampilkan notifikasi di UI
                    console.log(@json('Gagal mengambil detail PR.'));
                }
            }
        };
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
    @include('components.transaction.invoice-so-modal-script')
    @include('components.transaction.invoice-srj-modal-script')
    @include('components.transaction.browse-product-script', [
        'showControls' => true,
        'showPagination' => true,
        'supportsForEdit' => true,
    ])
@endpush
