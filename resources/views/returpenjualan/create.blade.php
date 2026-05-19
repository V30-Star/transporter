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

        foreach ($oldReturJualCodes as $index => $itemCode) {
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
                'fdisc' => $oldReturJualDiscs[$index] ?? 0,
                'ftotal' => (float) ($oldReturJualTotals[$index] ?? 0),
                'fdesc' => (string) ($oldReturJualDescs[$index] ?? ''),
                'fketdt' => (string) ($oldReturJualKetdts[$index] ?? ''),
                'maxqty' => max(0, (float) ($oldReturJualQtys[$index] ?? 0)),
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
    <div>
        <div class="lg:col-span-5">
            <div class="bg-white rounded shadow p-6 md:p-8 max-w-[96rem] mx-auto">
                <form action="{{ route('returpenjualan.store') }}" method="POST" class="mt-6" data-form-draft="true"
                    data-draft-key="returpenjualan:create"
                    @submit.prevent="
        const n = Number(document.getElementById('itemsCount')?.value || 0);
        if (n < 1) { window.dispatchEvent(new CustomEvent('returpenjualan-show-no-items')) } else { $el.submit() }
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
                            <label class="block text-sm font-medium mb-1">SO#</label>
                            <div class="flex items-center gap-3">
                                <input type="text" name="fsono" class="w-full border rounded px-3 py-2"
                                    :disabled="autoCode"
                                    :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'">
                                <label class="inline-flex items-center select-none">
                                    <input type="checkbox" x-model="autoCode" checked>
                                    <span class="ml-2 text-sm text-gray-700">Auto</span>
                                </label>
                            </div>
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">Retur Pajak#</label>
                            <input type="text" name="ftaxno" value="{{ old('ftaxno') }}"
                                class="w-full border rounded px-3 py-2 @error('ftaxno') border-red-500 @enderror">
                            @error('ftaxno')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">Type</label>
                            <select name="ftypesales" id="ftypesales" x-model.number="ftypesales" x-init="ftypesales = 0"
                                class="w-full border rounded px-3 py-2 @error('ftypesales') border-red-500 @enderror">
                                <option value="0">Penjualan</option>
                                <option value="1">Uang Muka</option>
                            </select>
                            @error('ftypesales')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">Tanggal</label>
                            <input type="date" id="fsodate" name="fsodate"
                                value="{{ old('fsodate') ?? date('Y-m-d') }}"
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
                                                {{ $filterSupplierId == $customer->fcustomercode ? 'selected' : '' }}>
                                                {{ $customer->fcustomername }} ({{ $customer->fcustomercode }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-0" role="button" aria-label="{{ 'Browse Customer' }}"
                                        @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))"></div>
                                </div>
                                <input type="hidden" name="fcustno" id="customerCodeHidden" value="{{ old('fcustno') }}">
                                <button type="button"
                                    @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))"
                                    class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                                    title="{{ 'Browse Customer' }}">
                                    <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                </button>
                                <a href="{{ route('customer.create') }}" target="_blank" rel="noopener"
                                    class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50"
                                    title="Tambah Customer">
                                    <x-heroicon-o-plus class="w-5 h-5" />
                                </a>
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
                                    <div class="absolute inset-0" role="button" aria-label="{{ 'Browse Salesman' }}"
                                        @click="window.dispatchEvent(new CustomEvent('salesman-browse-open'))"></div>
                                </div>
                                <input type="hidden" name="fsalesman" id="salesmanCodeHidden"
                                    value="{{ old('fsalesman', '0') }}">
                                <button type="button"
                                    @click="window.dispatchEvent(new CustomEvent('salesman-browse-open'))"
                                    class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                                    title="{{ 'Browse Salesman' }}">
                                    <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                </button>
                                <a href="{{ route('salesman.create') }}" target="_blank" rel="noopener"
                                    class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50"
                                    title="Tambah Salesman">
                                    <x-heroicon-o-plus class="w-5 h-5" />
                                </a>
                            </div>
                            @error('fsalesman')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="lg:col-span-12">
                            <label class="block text-sm font-medium">Keterangan</label>
                            <textarea name="fket" rows="3"
                                class="w-full border rounded px-3 py-2 @error('fket') border-red-500 @enderror"
                                placeholder="Keterangan isi di sini..."></textarea>
                            @error('fket')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div x-data="itemsTable()" x-init="init()" class="mt-6 space-y-2">

                        {{-- DETAIL ITEM (tabel input) --}}
                        <h3 class="text-base font-semibold text-gray-800">Detail Item</h3>

                        <div class="overflow-auto border rounded">
                            <table class="min-w-full text-sm balanced-detail-table" data-skip-auto-detail-style="true">
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
                                        <th class="p-2 text-right w-36 whitespace-nowrap">Qty</th>
                                        <th class="p-2 text-right w-32 whitespace-nowrap">@ Harga</th>
                                        <th class="p-2 text-right w-36 whitespace-nowrap">Disc. %</th>
                                        <th class="p-2 text-right w-36 whitespace-nowrap">Total Harga</th>
                                        <th class="p-2 text-center w-28">Aksi</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <template x-for="(it, i) in savedItems" :key="it.uid || `item-${i}`">
                                        <tr class="border-t align-top transition-colors hover:bg-gray-50">
                                            <td class="p-2" x-text="i + 1"></td>
                                            <td class="p-2">
                                                <div class="flex">
                                                    <input type="text"
                                                        class="flex-1 border rounded-l px-2 py-1 font-mono text-sm"
                                                        :id="'code_row_' + i"
                                                        x-model.trim="it.fitemcode"
                                                        @input="onCodeTypedRow(it, i)"
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
                                                    <button type="button" @click="openDesc(it)"
                                                        class="shrink-0 inline-flex items-center border border-l-0 rounded-r px-2 py-1 transition-colors"
                                                        :class="it.fdesc ?
                                                            'border-emerald-300 bg-emerald-50 text-emerald-600 hover:bg-emerald-100' :
                                                            'border-gray-300 bg-white text-gray-500 hover:bg-gray-50'"
                                                        title="Deskripsi item">
                                                        <x-heroicon-o-document-text class="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="p-2">
                                                <template x-if="it.units && it.units.length > 1">
                                                    <select class="w-full border rounded px-2 py-1 text-xs"
                                                        :id="'unit_row_' + i"
                                                        x-model="it.fsatuan"
                                                        @change="onRowUpdated(i)"
                                                        @keydown.enter.prevent="focusRowQty(i)">
                                                        <template x-for="u in it.units" :key="u">
                                                            <option :value="u" x-text="u"></option>
                                                        </template>
                                                    </select>
                                                </template>
                                                <template x-if="!it.units || it.units.length <= 1">
                                                    <div class="px-2 py-1 text-sm text-gray-600 bg-gray-50 border rounded"
                                                        x-text="it.fsatuan || '-'"></div>
                                                </template>
                                            </td>
                                            <td class="p-2">
                                                <div class="flex w-full max-w-full">
                                                    <input type="text"
                                                        class="min-w-0 flex-1 border rounded-l px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                                        :value="it.frefcode || '-'"
                                                        disabled>
                                                    <button type="button" @click="openProductHistory(it)"
                                                        class="shrink-0 border border-l-0 px-2 py-1 bg-white hover:bg-gray-50 rounded-r"
                                                        :disabled="!canOpenHistory(it)"
                                                        :class="!canOpenHistory(it) ? 'opacity-50 cursor-not-allowed' : ''"
                                                        title="Riwayat produk">
                                                        <x-heroicon-o-clock class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="p-2 text-right">
                                                <input type="number"
                                                    class="w-full border rounded px-2 py-1 text-right text-sm"
                                                    min="0" step="0.01"
                                                    :id="'qty_row_' + i"
                                                    x-model.number="it.fqty"
                                                    @input="enforceQtyRow(it); onRowUpdated(i)"
                                                    @change="enforceQtyRow(it); onRowUpdated(i)"
                                                    @keydown.enter.prevent="focusRowPrice(i)">
                                                <div class="text-xs text-gray-400 mt-0.5 flex justify-end items-center"
                                                    x-show="it.fitemcode">
                                                    <div x-html="formatStockLimit(it)"></div>
                                                </div>
                                            </td>
                                            <td class="p-2 text-right">
                                                <input type="number"
                                                    class="w-full border rounded px-2 py-1 text-right text-sm"
                                                    min="0" step="0.01"
                                                    :id="'price_row_' + i"
                                                    x-model.number="it.fprice"
                                                    @input="onRowUpdated(i)"
                                                    @keydown.enter.prevent="focusRowDisc(i)">
                                            </td>
                                            <td class="p-2 text-right">
                                                <input type="text"
                                                    class="w-full border rounded px-2 py-1 text-right text-sm"
                                                    :id="'disc_row_' + i"
                                                    x-model="it.fdisc"
                                                    @input="onRowUpdated(i)"
                                                    @keydown.enter.prevent="onRowUpdated(i)">
                                            </td>
                                            <td class="p-2">
                                                <input type="text"
                                                    class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm text-right"
                                                    :value="fmt(it.ftotal)" disabled>
                                            </td>
                                            <td class="p-2 text-center">
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
                                    <input type="hidden" name="fitemcode[]" :value="it.fitemcode">
                                    <input type="hidden" name="fitemname[]" :value="it.fitemname">
                                    <input type="hidden" name="fsatuan[]" :value="it.fsatuan">
                                    <input type="hidden" name="frefcode[]" :value="it.frefcode">
                                    <input type="hidden" name="frefdtno[]" :value="it.frefdtno">
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

                        <input type="hidden" id="itemsCount" :value="submitItems.length">
                        <input type="hidden" name="frefcode" id="frefcode" value="{{ old('frefcode') }}">
                        <input type="hidden" name="frefso" id="frefso" value="{{ old('frefso') }}">
                        <input type="hidden" name="frefsrj" id="frefsrj" value="{{ old('frefsrj') }}">

                        <script>
                            document.addEventListener('DOMContentLoaded', () => {
                                const inputRefCode = document.getElementById('frefcode');
                                const inputRefSo = document.getElementById('frefso');
                                const inputRefSrj = document.getElementById('frefsrj');

                                // Menangkap Event saat referensi faktur dipilih
                                window.addEventListener('pr-picked', (e) => {
                                    const header = e.detail.header;
                                    inputRefCode.value = 'SO';
                                    inputRefSo.value = header.ftranmtid ?? header.ftrsomtid ?? ''; // Simpan id referensi aktif
                                    inputRefSrj.value = ''; // Reset yang lain
                                });

                                // Menangkap Event saat SRJ dipilih
                                window.addEventListener('srj-picked', (e) => {
                                    const header = e.detail.header;
                                    inputRefCode.value = 'SRJ';
                                    inputRefSrj.value = header.fstockmtid; // Sesuaikan ID header SRJ
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

                                        <div class="relative bg-white rounded-2xl shadow-2xl w-[96vw] max-w-[110rem] flex flex-col overflow-hidden"
                                            style="height: 85vh;">
                                            <div
                                                class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-indigo-50 to-white">
                                                <div>
                                                    <h3 class="text-xl font-bold text-gray-800">Browse Surat Jalan
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

                                            <div class="flex-1 overflow-x-auto overflow-y-hidden px-6" style="min-height: 0;">
                                                <div class="bg-white">
                                                    <table id="srjTable"
                                                        class="min-w-full text-sm display nowrap stripe hover"
                                                        style="width:100%">
                                                        <thead class="sticky top-0 z-10">
                                                            <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                                                <th
                                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                    No. SRJ</th>
                                                                <th
                                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                    Tanggal</th>
                                                                <th
                                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                    Customer #</th>
                                                                <th
                                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                    Nama Customer</th>
                                                                <th
                                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                    Kota</th>
                                                                <th
                                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                    Ref PO</th>
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

                            <!-- ===== Panel Totals (DESAIN ASLI dipertahankan, hanya wrapper yang diperbaiki) ===== -->
                            <div x-data="prhFormModal()" class="w-full md:w-auto md:min-w-[550px] lg:min-w-[650px]">
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
                                                class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                            <label for="fincludeppn_input" class="ml-2 text-sm font-medium text-gray-700">
                                                <span class="font-bold">PPN</span>
                                            </label>
                                        </div>

                                        <!-- Dropdown Include / Exclude (tengah) -->
                                        <div class="flex items-center gap-2">
                                            <select id="fapplyppn_input" name="fapplyppn" x-model.number="fapplyppn"
                                                x-init="fapplyppn = 0" :disabled="!(includePPN || fapplyppn)"
                                                class="w-28 h-9 px-2 text-sm leading-tight border rounded transition-opacity appearance-none
                               disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed">
                                                <option value="0">Exclude</option>
                                                <option value="1">Include</option>
                                            </select>
                                        </div>

                                        <!-- Input Rate + Nominal (kanan) -->
                                        <div class="flex items-center gap-2">
                                            <input type="number" min="0" max="100" step="0.01"
                                                x-model.number="ppnRate" :disabled="!(includePPN || fapplyppn)"
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

                                    <div class="border-t my-1"></div>

                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-semibold text-gray-800">Grand Total</span>
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

                                <!-- Modal backdrop -->
                                <div x-show="show" x-transition.opacity class="fixed inset-0 z-40 bg-black/50"
                                    @keydown.escape.window="closeModal()"></div>

                                <div>
                                    {{-- MODAL FAKTUR PENJUALAN --}}
                                    <div x-show="show" x-cloak x-transition.opacity
                                        class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-8"
                                        aria-modal="true" role="dialog">

                                        <div class="relative w-full max-w-5xl rounded-xl bg-white shadow-2xl flex flex-col"
                                            style="height: 600px;">
                                            <!-- Header -->
                                            <div
                                                class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
                                                <h3 class="text-xl font-bold text-gray-800">{{ 'Pilih Faktur Penjualan' }}
                                                </h3>
                                                <button type="button" @click="closeModal()"
                                                    class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                                                    {{ 'Tutup' }}
                                                </button>
                                            </div>

                                            <!-- Table Container -->
                                            <div class="flex-1 overflow-y-auto p-6" style="min-height: 0;">
                                                <table id="prTable"
                                                    class="min-w-full text-sm display nowrap stripe hover"
                                                    style="width:100%">
                                                    <thead class="sticky top-0 z-10">
                                                        <tr class="bg-gray-50 border-b-2 border-gray-200">
                                                            <th class="p-3 text-left font-semibold text-gray-700">No.
                                                                Faktur
                                                            </th>
                                                            <th class="p-3 text-left font-semibold text-gray-700">Customer
                                                            </th>
                                                            <th class="p-3 text-left font-semibold text-gray-700">Tanggal
                                                            </th>
                                                            <th class="p-3 text-center font-semibold text-gray-700">Aksi
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <!-- DataTables data here -->
                                                    </tbody>
                                                </table>
                                            </div>

                                            <!-- Footer -->
                                            <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
                                            </div>
                                        </div>
                                    </div>
                                    {{-- Modal Duplikasi --}}
                                    <div x-show="showDupModal" x-cloak x-transition.opacity
                                        class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                                        <div class="absolute inset-0 bg-black/40" @click="closeDupModal()"></div>
                                        <div class="relative bg-white rounded-xl shadow-xl max-w-2xl w-full p-6">
                                            <h3 class="text-lg font-semibold mb-4">Peringatan Duplikasi</h3>
                                            <p class="mb-4">
                                                Ditemukan <strong x-text="dupCount"></strong> item yang sudah ada dalam
                                                daftar.
                                                Hanya item unik yang akan ditambahkan.
                                            </p>

                                            <div class="mb-4 max-h-48 overflow-auto border rounded p-2 bg-gray-50"
                                                x-show="dupSample.length > 0">
                                                <p class="text-sm font-medium mb-2">Contoh item duplikat:</p>
                                                <template x-for="(item, idx) in dupSample" :key="idx">
                                                    <div class="text-xs py-1">
                                                        • <span x-text="item.fitemcode"></span> - <span
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
                                                    Tambahkan Item Unik
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
                                    <h3 class="text-lg font-semibold text-gray-800">Isi Deskripsi Item</h3>
                                </div>

                                <div class="px-5 py-4 space-y-4">
                                    <div>
                                        <div class="mb-1 text-sm text-gray-700">Nama Produk</div>
                                        <div class="rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800" x-text="descItemName || '-'"></div>
                                    </div>
                                    <label class="block text-sm text-gray-700">Deskripsi</label>
                                    <textarea x-model="descValue" rows="5" class="w-full border rounded px-3 py-2"
                                        placeholder="Tulis deskripsi item di sini..."></textarea>
                                </div>

                                <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
                                    <button type="button" @click="copyDescName()"
                                        class="h-9 px-4 rounded-lg bg-blue-50 text-blue-700 text-sm font-medium hover:bg-blue-100">
                                        Copy
                                    </button>
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
                                    <h3 class="text-lg font-semibold text-gray-800">Riwayat Produk</h3>
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
                                                    </tr>
                                                </template>
                                                <tr x-show="!historyRows.length">
                                                    <td colspan="5" class="p-4 text-center text-gray-500">Tidak ada
                                                        riwayat.</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

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

                    <div class="mt-8 flex justify-center gap-4">
                        <button type="submit"
                            class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                            <x-heroicon-o-check class="w-5 h-5 mr-2" /> Simpan
                        </button>
                        <button type="button" @click="window.location.href='{{ route('returpenjualan.index') }}'"
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
    // Map produk untuk auto-fill tabel
    // Standardized Product Map initialization
    window.PRODUCT_MAP = @json($productMap ?? []);

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
                $('#customerBrowseTable').off('click', '.btn-choose').on('click', '.btn-choose', (e) => {
                    const data = this.dataTable.row($(e.target).closest('tr')).data();
                    if (!data) return;
                    // Pastikan fblokir tidak bernilai 1 sebelum diproses
                    if (data.fblokir != 1) {
                        this.chooseCustomer(data);
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
                    this.dataTable.search('').draw();
                }
            },

            chooseCustomer(customer) {
                const sel = document.getElementById('modal_filter_customer_id');
                const hid = document.getElementById('customerCodeHidden');

                if (!sel) {
                    this.close();
                    return;
                }

                let opt = [...sel.options].find(o => o.value == String(customer.fcustomercode));
                const label = `${customer.fcustomername} (${customer.fcustomercode})`;
                if (!opt) {
                    opt = new Option(label, customer.fcustomercode, true, true);
                    sel.add(opt);
                } else {
                    opt.text = label;
                    opt.selected = true;
                }
                if (hid) hid.value = customer.fcustomercode;

                window.dispatchEvent(new CustomEvent('customer-selected', {
                    detail: {
                        f1: customer.fkirimaddress1 || '',
                        f2: customer.fkirimaddress2 || '',
                        f3: customer.fkirimaddress3 || ''
                    }
                }));

                sel.dispatchEvent(new Event('change'));
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
                $('#salesmanBrowseTable').off('click', '.btn-choose').on('click', '.btn-choose', (e) => {
                    const data = this.dataTable.row($(e.target).closest('tr')).data();
                    if (data) this.chooseSalesman(data);
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

                sel.dispatchEvent(new Event('change'));
                if (hid) hid.value = salesman.fsalesmancode;
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
            showNoItems: false,
            savedItems: @json($initialReturPenjualanItems),
            minimumVisibleRows: 5,
            browseTarget: null,
            editingIndex: null,
            editRow: newRow(),

            totalHarga: 0,
            ppnRate: 11,

            initialGrandTotal: @json($famountso ?? 0),
            initialPpnAmount: @json($famountpopajak ?? 0),

            includePPN: @json(old('fincludeppn', $tr_poh->fincludeppn ?? 0) == '1'),
            fapplyppn: @json((int) old('fapplyppn', $tr_poh->fapplyppn ?? 0)),

            get ppnIncluded() {
                const total = +this.totalHarga || 0;
                const rate = +this.ppnRate || 0;
                if (!this.fapplyppn || !this.includePPN) return 0;
                return Math.round((100 / (100 + rate)) * total * (rate / 100));
            },

            get netFromGross() {
                const total = +this.totalHarga || 0;
                return total - this.ppnIncluded;
            },

            get ppnAdded() {
                const rate = +this.ppnRate || 0;
                if (!this.includePPN || this.fapplyppn) return 0;
                const total = +this.totalHarga || 0;
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
                const total = +this.totalHarga || 0;
                if (!this.includePPN) return total;
                if (this.fapplyppn) {
                    return this.netFromGross;
                }
                return total;
            },

            get grandTotal() {
                const total = +this.totalHarga || 0;
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

            // ✅ UPDATE FUNGSI recalc untuk menggunakan parseDiscount
            recalc(row) {
                row.fqty = Math.max(0, +row.fqty || 0);
                row.fterima = Math.max(0, +row.fterima || 0);
                row.fprice = Math.max(0, +row.fprice || 0);
                if (row.fprice < 0) row.fprice = 0;

                // Parse discount menggunakan fungsi baru
                const discPercent = this.parseDiscount(row.fdisc);

                // Hitung total
                const subtotal = row.fqty * row.fprice;
                const discAmount = subtotal * (discPercent / 100);
                row.ftotal = +(subtotal - discAmount).toFixed(2);

                this.recalcTotals();
            },

            recalcTotals() {
                this.totalHarga = this.savedItems.reduce((sum, item) => sum + item.ftotal, 0);
            },

            productMeta(code) {
                const key = (code || '').toString().trim();
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
                if (!meta.unit_ratios) {
                    meta.unit_ratios = {
                        satuankecil: 1,
                        satuanbesar: 1,
                        satuanbesar2: 1
                    };
                }
                return meta;
            },

            formatStockLimit(row) {
                return '';
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
                    if (showToast) window.toast?.error('Qty referensi sudah habis atau sudah digunakan.');
                    return false;
                }

                const qty = Number(row?.fqty ?? 0);
                if (qty > limit) {
                    row.fqty = limit;
                    if (showToast) window.toast?.error(
                        `Qty melebihi sisa referensi. Maksimal ${limit} ${row.fsatuan || ''}`.trim());
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
                this.validateReferenceQty(row, false);
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
                row.frefcode = meta.id || meta.fprdid || '';
                const units = [...new Set((meta.units || []).map(u => (u ?? '').toString().trim()).filter(Boolean))];
                row.units = units;
                if (!units.includes(row.fsatuan)) row.fsatuan = units[0] || '';
                row.fsatuan = row.fsatuan;
                if (meta.unit_ratios) row.unit_ratios = meta.unit_ratios;
                row.maxqty = Number.isFinite(+row.maxqty) ? +row.maxqty : 0;

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
                this.resetDraft();
                this.addManyFromPR(header, items, source);
            },

            addManyFromPR(header, items, source) {
                const existing = new Set(this.getCurrentItemKeys());
                let added = 0,
                    duplicates = [];

                items.forEach(src => {
                    const row = {
                        uid: cryptoRandom(),
                        fitemcode: src.fitemcode ?? '',
                        fitemname: src.fitemname ?? '',
                        fsatuan: src.fsatuan ?? '',
                        frefdtno: src.frefdtno ?? '',
                        fnouref: (src.frefdtno ?? src.fnouref ?? null),
                        frefpr: src.frefpr ?? (source === 'SRJ' ? (header?.fstockmtno ?? '') : (header
                            ?.fsono ?? '')),
                        frefcode: source === 'SRJ' ? (header?.fstockmtno ?? '') : (header?.fsono ?? ''),

                        frefso: source === 'SO' ? (header?.fsono ?? '') : '',
                        frefsrj: source === 'SRJ' ? (header?.fstockmtno ?? '') : '',
                        fnoacak: this.generateUniqueNoAcak(),
                        frefnoacak: this.normalizeRefNoAcak(src.frefnoacak ?? src.fnoacak ?? ''),

                        fprhid: src.fprhid ?? header?.fprhid ?? '',
                        fqty: (src.fqty !== null && src.fqty !== undefined && Number(src.fqty) > 0) ?
                            Number(src.fqty) : 1,
                        fterima: Number(src.fterima ?? 0),
                        fprice: Number(src.fprice ?? 0),
                        fdisc: src.fdisc ?? 0,
                        ftotal: Number(src.ftotal ?? 0),
                        fdesc: src.fdesc ?? '',
                        fketdt: src.fketdt ?? '',
                        units: Array.isArray(src.units) && src.units.length ? src.units : [src.fsatuan]
                            .filter(Boolean),
                        maxqty: Math.max(0, Number(src.maxqty ?? src.fqtyremain ?? src.fqty ?? 0)),
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

                    const rowLimit = this.getRowQtyLimit(row);
                    if (!(rowLimit > 0)) return;
                    if (rowLimit > 0) {
                        row.fqty = Number(rowLimit);
                    }
                    this.validateReferenceQty(row, false);
                    this.savedItems.push({
                        ...this.createRow(),
                        ...row,
                        uid: cryptoRandom(),
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
                const row = typeof index === 'number' ? this.savedItems[index] : null;
                if (row) {
                    if (row.fitemcode === 'UM' && this.ftypesales === 0) {
                        this.showToast('Produk UM hanya untuk tipe Uang Muka!', 'error');
                        row.fitemcode = '';
                        row.fitemname = '';
                        row.fsatuan = '';
                        row.units = [];
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
                if (this.submitItems.length === 0) {
                    $event.preventDefault();
                    this.showNoItems = true;
                    return;
                }
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

            focusRowDisc(index) {
                document.getElementById(`disc_row_${index}`)?.focus();
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
            canOpenHistory(targetRow) {
                const customerCode = (document.getElementById('customerCodeHidden')?.value || '').trim();
                const productCode = (targetRow?.fitemcode || '').toString().trim();
                return customerCode !== '' && productCode !== '';
            },
            closeHistory() {
                this.showHistoryModal = false;
                this.historyLoading = false;
                this.historyRows = [];
            },
            async openProductHistory(targetRow) {
                const customerCode = (document.getElementById('customerCodeHidden')?.value || '').trim();
                const productCode = (targetRow?.fitemcode || '').toString().trim();

                if (customerCode === '') {
                    this.showToast('Pilih customer terlebih dahulu.', 'warning');
                    return;
                }

                if (productCode === '') {
                    this.showToast('Pilih produk terlebih dahulu.', 'warning');
                    return;
                }

                this.showHistoryModal = true;
                this.historyLoading = true;
                this.historyRows = [];

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

            itemKey(it) {
                return `${(it.fitemcode ?? '').toString().trim()}::${(it.frefdtno ?? '').toString().trim()}`;
            },

            getCurrentItemKeys() {
                return this.submitItems.map(it => this.itemKey(it));
            },

            get submitItems() {
                return this.savedItems.filter(row => this.isRowSavable(row));
            },

            createRow() {
                return {
                    ...newRow(),
                    uid: cryptoRandom(),
                    fnoacak: this.generateUniqueNoAcak(),
                };
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

            init() {
                this.$watch('includePPN', () => this.recalcTotals());
                this.$watch('fapplyppn', () => this.recalcTotals());
                this.$watch('ppnRate', () => this.recalcTotals());

                window.getCurrentItemKeys = () => this.getCurrentItemKeys();
                this.savedItems = (this.savedItems || []).map(item => ({
                    ...this.createRow(),
                    ...item,
                    fnoacak: this.normalizeNoAcak(item.fnoacak) || this.generateUniqueNoAcak(),
                    frefnoacak: this.normalizeRefNoAcak(item.frefnoacak),
                }));
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
                    const apply = (row) => {
                        row.fitemcode = (product.fprdcode || '').toString();
                        row.frefcode = product.fprdid || product.id || '';
                        this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode));
                        row.fnoacak = this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak();
                        if (!row.fqty) row.fqty = 0;
                        this.recalc(row);
                    };
                    const index = typeof this.browseTarget === 'number' ? this.browseTarget : this.savedItems.length - 1;
                    if (index < 0 || !this.savedItems[index]) return;
                    const row = this.savedItems[index];
                    apply(row);
                    this.onRowUpdated(index);
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
    document.addEventListener('DOMContentLoaded', function() {
        // Cek jika ada flash message "error" dari controller
        @if (session('error'))
            Swal.fire({
                icon: 'error',
                title: @json('Penyimpanan Batal'),
                text: "{{ session('error') }}",
                confirmButtonColor: '#ef4444', // Warna merah tailwind
                confirmButtonText: 'OK',
                allowOutsideClick: false
            });
        @endif

        // Cek jika ada flash message "success"
        @if (session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: "{{ session('success') }}",
                timer: 2000,
                showConfirmButton: false
            });
        @endif
    });
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
                    `${(src.fitemcode ?? '').toString().trim()}::${(src.frefdtno ?? '').toString().trim()}`;
                const safeUniques = this.pendingUniques.filter(src => !currentKeys.has(keyOf(src)));

                if (safeUniques.length > 0) {
                    window.transactionReferenceModalHelper.dispatchPick('pr-picked', this.pendingHeader,
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
                    });

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
                return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
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

                    // tidak ada duplikat → langsung kirim semua item yang unik (atau 'items' kalau mau semua)
                    window.dispatchEvent(new CustomEvent('pr-picked', {
                        detail: {
                            header: row,
                            items
                        } // jika ingin hanya unik, ganti 'items' → 'uniques'
                    }));
                    this.closeModal();

                } catch (e) {
                    console.error(e);
                    alert(@json('Gagal mengambil detail Faktur Penjualan. Lihat konsol untuk detail.'));
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
                    dom: '<"#poTableControls"lf>rt<"#poTablePagination"ip>',
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
                const self = this;
                $('#poTable').on('click', '.btn-pick', function() {
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
                window.dispatchEvent(new CustomEvent('pr-picked', {
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

                    const items = json.items || [];
                    const currentKeys = new Set((window.getCurrentItemKeys?.() || []).map(String));

                    const keyOf = (src) =>
                        `${(src.fitemcode ?? '').toString().trim()}::${(src.frefdtno ?? '').toString().trim()}`;

                    const duplicates = items.filter(src => currentKeys.has(keyOf(src)));
                    const uniques = items.filter(src => !currentKeys.has(keyOf(src)));

                    if (duplicates.length > 0) {
                        this.openDupModal(json.header, duplicates, uniques);
                        return;
                    }

                    window.dispatchEvent(new CustomEvent('pr-picked', {
                        detail: {
                            header: json.header,
                            items: items
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
        return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    window.srjFormModal = function() {
        return {
            showSrjModal: false,
            table: null,

            // Fitur Duplikasi (mirip logic SO)
            showDupModal: false,
            dupCount: 0,
            dupSample: [],
            pendingHeader: null,
            pendingUniques: [],

            initDataTable() {
                if (this.table) {
                    this.table.destroy();
                }

                this.table = $('#srjTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('suratjalan.pickable') }}", // Pastikan route ini ada di web.php
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
                            data: 'fstockmtno',
                            className: 'font-mono text-sm'
                        },
                        {
                            data: 'frefpo',
                            defaultContent: '-',
                            className: 'font-mono text-sm'
                        },
                        {
                            data: 'fstockmtdate',
                            className: 'text-sm',
                            render: function(data) {
                                return formatDate(
                                    data); // Menggunakan helper formatDate yang sudah Anda miliki
                            }
                        },
                        {
                            data: 'fsuppliername',
                            name: 'fsuppliername',
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center',
                            width: '100px',
                            render: function() {
                                return '<button type="button" class="btn-pick-srj px-4 py-1.5 rounded-md text-sm font-bold bg-indigo-600 hover:bg-indigo-700 text-white transition-colors duration-150">{{ 'Pilih' }}</button>';
                            }
                        }
                    ],
                    pageLength: 10,
                    dom: '<"#srjHeader"fl>rt<"#srjFooter"ip>',
                    language: {
                        processing: @json('Memuat data...'),
                        search: @json('Search' . ':'),
                        lengthMenu: @json('Tampilkan _MENU_'),
                        paginate: {
                            next: "Selanjutnya",
                            previous: "Sebelumnya"
                        }
                    },
                    order: [
                        [2, 'desc']
                    ], // Urutkan berdasarkan tanggal
                    autoWidth: false,
                    initComplete: function() {
                        const api = this.api();
                        const $container = $(api.table().container());

                        // 1. Bungkus Header (Search & Length) supaya sejajar (Flex)
                        const $header = $container.find('#srjHeader');
                        $header.addClass('flex items-center justify-between mb-4 gap-4');

                        // 2. Styling Input Search
                        $header.find('.dataTables_filter input').css({
                            'width': '300px',
                            'padding': '8px 12px',
                            'border': '2px solid #e5e7eb',
                            'border-radius': '8px',
                            'outline': 'none'
                        }).addClass('focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500');

                        // 3. Styling Dropdown "Tampilkan 10" (Length Menu)
                        $header.find('.dataTables_length select').css({
                            'padding': '6px 30px 6px 12px', // Beri padding kanan lebih untuk icon panah
                            'border': '2px solid #e5e7eb',
                            'border-radius': '8px',
                            'background-position': 'right 8px center',
                            'appearance': 'none', // Hilangkan style default browser
                            'min-width': '80px'
                        }).addClass('focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500');

                        // 4. Bungkus Footer (Info & Pagination)
                        const $footer = $container.find('#srjFooter');
                        $footer.addClass('flex items-center justify-between mt-4');
                    }
                });

                // Handle button click (pola delegasi jQuery)
                const self = this;
                $('#srjTable').off('click', '.btn-pick-srj').on('click', '.btn-pick-srj', function() {
                    const data = self.table.row($(this).closest('tr')).data();
                    self.pick(data);
                });
            },

            // Handler Modal SRJ
            openSrjModal() {
                this.showSrjModal = true;
                this.$nextTick(() => {
                    this.initDataTable();
                });
            },

            closeSrjModal() {
                this.showSrjModal = false;
                if (this.table) {
                    this.table.search('').draw();
                }
            },

            // Fitur Duplikasi SRJ
            openDupModal(header, duplicates, uniques) {
                this.dupCount = duplicates.length;
                this.dupSample = duplicates.slice(0, 6);
                this.pendingHeader = header;
                this.pendingUniques = uniques;
                this.showDupModal = true;
            },

            closeDupModal() {
                this.showDupModal = false;
            },

            confirmAddUniques() {
                window.dispatchEvent(new CustomEvent('srj-picked', {
                    detail: {
                        header: this.pendingHeader,
                        items: this.pendingUniques
                    }
                }));
                this.closeDupModal();
                this.closeSrjModal();
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
                    // Pastikan route srj.items sudah didefinisikan di Laravel
                    const url = `{{ route('suratjalan.items', ['id' => 'PLACEHOLDER']) }}`
                        .replace('PLACEHOLDER', row.fstockmtid);

                    const res = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!res.ok) throw new Error(`Server error: ${res.status}`);

                    const json = await res.json();
                    const items = json.items || [];

                    // Ambil key item yang sudah ada di table input (untuk cek duplikat)
                    const currentKeys = new Set((window.getCurrentItemKeys?.() || []).map(String));

                    // Logic key unik (Item Code + Ref Detail No)
                    const keyOf = (src) =>
                        `${(src.fitemcode ?? '').toString().trim()}::${(src.frefdtno ?? '').toString().trim()}`;

                    const duplicates = items.filter(src => currentKeys.has(keyOf(src)));
                    const uniques = items.filter(src => !currentKeys.has(keyOf(src)));

                    if (duplicates.length > 0) {
                        this.openDupModal(json.header, duplicates, uniques);
                        return;
                    }

                    window.dispatchEvent(new CustomEvent('srj-picked', {
                        detail: {
                            header: json.header,
                            items: items
                        }
                    }));

                    this.closeSrjModal();
                } catch (e) {
                    console.error('Error SRJ:', e);
                    window.toast?.error(`${@json('Gagal mengambil detail Surat Jalan:')} ${e.message}`);
                }
            }
        };
    };
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
                    $('#productTable').off('click', '.btn-choose').on('click', '.btn-choose', (e) => {
                        const data = this.table.row($(e.target).closest('tr')).data();
                        if (data) this.choose(data);
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
    </script>
@endpush
