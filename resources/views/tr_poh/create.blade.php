@extends('layouts.app')

@section('title', 'Order Pembelian - New')

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
        $oldNoUrefs = old('fnouref', []);
        $oldNoAcaks = old('fnoacak', []);
        $oldRefNoAcaks = old('frefnoacak', []);
        $oldRefPrs = old('frefpr', []);
        $oldPrhIds = old('fprhid', []);
        $oldQtys = old('fqty', []);
        $oldPrices = old('fprice', []);
        $oldDiscs = old('fdisc', []);
        $oldTotals = old('ftotal', []);
        $oldDescs = old('fdesc', []);
        $oldKetdts = old('fketdt', []);
        $initialPoItems = [];

        foreach ($oldItemCodes as $index => $itemCode) {
            $initialPoItems[] = [
                'fitemcode' => $itemCode,
                'fitemname' => $oldItemNames[$index] ?? '',
                'fsatuan' => $oldSatuans[$index] ?? '',
                'frefdtno' => $oldRefDtNos[$index] ?? '',
                'frefdtid' => $oldRefDtIds[$index] ?? '',
                'fnouref' => $oldNoUrefs[$index] ?? '',
                'fnoacak' => $oldNoAcaks[$index] ?? '',
                'frefnoacak' => $oldRefNoAcaks[$index] ?? '',
                'frefpr' => $oldRefPrs[$index] ?? '',
                'fprhid' => $oldPrhIds[$index] ?? '',
                'fqty' => $oldQtys[$index] ?? 0,
                'fprice' => $oldPrices[$index] ?? 0,
                'fdisc' => $oldDiscs[$index] ?? 0,
                'ftotal' => $oldTotals[$index] ?? 0,
                'fdesc' => $oldDescs[$index] ?? '',
                'fketdt' => $oldKetdts[$index] ?? '',
            ];
        }
    @endphp
    <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1600px] w-full mx-auto">
        <form action="{{ route('tr_poh.store') }}" method="POST" class="mt-6" data-form-draft="true"
            data-draft-key="tr_poh:create" x-data="mainForm()" x-init="init()" @submit.prevent="submitForm($el)">
            @csrf

            {{-- HEADER FORM --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

                {{-- Cabang --}}
                <div class="lg:col-span-4">
                    <label class="block text-sm font-medium">Cabang</label>
                    <input type="text" class="w-full border rounded px-3 py-2 bg-gray-200 cursor-not-allowed"
                        value="{{ $fcabang }}" disabled>
                    <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                </div>

                {{-- PO# --}}
                <div class="lg:col-span-4">
                    <label class="block text-sm font-medium mb-1">PO#</label>
                    <div class="flex items-center gap-3">
                        <input type="text" name="fpohid" class="w-full border rounded px-3 py-2" :disabled="autoCode"
                            :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'">
                        <label class="inline-flex items-center select-none">
                            <input type="checkbox" x-model="autoCode" checked>
                            <span class="ml-2 text-sm text-gray-700">Auto</span>
                        </label>
                    </div>
                </div>

                {{-- Supplier --}}
                <div class="lg:col-span-4">
                    <label class="block text-sm font-medium mb-1">Supplier</label>
                    <div class="flex">
                        <div class="relative flex-1">
                            <select id="modal_filter_supplier_id" name="filter_supplier_id"
                                class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
                                disabled>
                                <option value=""></option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->fsuppliercode }}"
                                        data-tempo="{{ (int) ($supplier->ftempo ?? 0) }}"
                                        {{ old('fsupplier', $filterSupplierId) == $supplier->fsuppliercode ? 'selected' : '' }}>
                                        {{ $supplier->fsuppliername }} ({{ $supplier->fsuppliercode }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="absolute inset-0" role="button"
                                @click="window.dispatchEvent(new CustomEvent('tr-poh-supplier-browse-open'))"></div>
                        </div>
                        <input type="hidden" name="fsupplier" id="supplierCodeHidden" value="{{ old('fsupplier') }}">
                        <button type="button" @click="window.dispatchEvent(new CustomEvent('tr-poh-supplier-browse-open'))"
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

                {{-- Tanggal --}}
                <div class="lg:col-span-4">
                    <label class="block text-sm font-medium">Tanggal</label>
                    <input type="date" name="fpodate" value="{{ old('fpodate') ?? date('Y-m-d') }}"
                        class="w-full border rounded px-3 py-2 @error('fpodate') border-red-500 @enderror">
                    @error('fpodate')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tgl. Kirim --}}
                <div class="lg:col-span-4">
                    <label class="block text-sm font-medium">Tgl. Kirim</label>
                    <input type="date" name="fkirimdate" value="{{ old('fkirimdate', '') }}"
                        class="w-full border rounded px-3 py-2 @error('fkirimdate') border-red-500 @enderror">
                    @error('fkirimdate')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tempo --}}
                <div class="lg:col-span-4">
                    <label class="block text-sm font-medium mb-1">Tempo</label>
                    <div class="flex items-center">
                        <input type="number" id="ftempohr" name="ftempohr" value="{{ old('ftempohr', 0) }}"
                            class="w-full border rounded px-3 py-2">
                        <span class="ml-2">Hari</span>
                    </div>
                </div>

                {{-- Currency --}}
                <div class="lg:col-span-4">
                    <label class="block text-sm font-medium">Currency</label>
                    <select name="fcurrency" id="currencySelect" x-model="selectedCurrId" @change="onCurrencyChange()"
                        class="w-full border rounded px-3 py-2 @error('fcurrency') border-red-500 @enderror">
                        <option value="">-- Pilih Currency --</option>
                        @foreach ($currencies as $cur)
                            <option value="{{ $cur->fcurrid }}" data-code="{{ $cur->fcurrcode }}"
                                data-rate="{{ $cur->frate }}"
                                {{ ($cur->fcurrcode === 'IDR' && !old('fcurrencyid')) || old('fcurrencyid') == $cur->fcurrid ? 'selected' : '' }}>
                                {{ $cur->fcurrname }} ({{ $cur->fcurrcode }})
                            </option>
                        @endforeach
                    </select>
                    <input type="hidden" name="fcurrencyid" :value="selectedCurrId">
                    <input type="hidden" name="fcurrcode" :value="selectedCurrCode">
                    @error('fcurrency')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Rate --}}
                <div class="lg:col-span-4">
                    <label class="block text-sm font-medium">Rate</label>
                    <input type="number" step="0.01" min="0" name="frate" x-model.number="rateValue"
                        class="w-full border rounded px-3 py-2 @error('frate') border-red-500 @enderror"
                        placeholder="Rate akan terisi otomatis">
                    @error('frate')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Keterangan --}}
                <div class="lg:col-span-12">
                    <label class="block text-sm font-medium">Keterangan</label>
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
                    <table class="min-w-full text-sm po-detail-table" data-skip-auto-detail-style="true">
                        <colgroup>
                            <col style="width:2%;">
                            <col style="width:12%;">
                            <col style="width:27%;">
                            <col style="width:7%;">
                            <col style="width:10%;">
                            <col style="width:8%;">
                            <col style="width:8%;">
                            <col style="width:6%;">
                            <col style="width:8%;">
                            <col style="width:8%;">
                            <col style="width:4%;">
                        </colgroup>
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-2 text-left w-10">#</th>
                                <th class="p-2 text-left w-20">Kode Produk</th>
                                <th class="p-2 text-left w-[31rem]">Nama Produk</th>
                                <th class="p-2 text-left w-24">Satuan</th>
                                <th class="p-2 text-left w-24">Ref.PR#</th>
                                <th class="p-2 text-right w-24 whitespace-nowrap">Qty</th>
                                <th class="p-2 text-right w-24 whitespace-nowrap">@ Harga</th>
                                <th class="p-2 text-right w-20 whitespace-nowrap">Disc. %</th>
                                <th class="p-2 text-right w-24 whitespace-nowrap">Total Harga</th>
                                <th class="p-2 text-right w-20 whitespace-nowrap">Total Harga (Rp.)</th>
                                <th class="p-2 text-center w-16">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <template x-for="(row, i) in rows" :key="row.uid">
                                <tr class="border-t align-top transition-colors"
                                    :class="activeRow === row.uid ? 'bg-amber-50' : 'hover:bg-gray-50'">
                                    <td class="p-2 text-gray-500" x-text="i + 1"></td>

                                    <td class="p-2">
                                        <div class="flex w-40">
                                            <input type="text"
                                                class="w-32 border rounded-l px-2 py-1 font-mono text-sm min-w-0"
                                                x-model.trim="row.fitemcode" @focus="activeRow = row.uid"
                                                @blur="activeRow = null" @input="onCodeTypedRow(row, i)"
                                                @keydown.enter.prevent="focusRowUnit(row, i)">
                                            <button type="button" @click="openBrowseFor(i)"
                                                class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50"
                                                title="Cari Produk">
                                                <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>

                                    <td class="p-2 align-top overflow-visible">
                                        <div class="flex min-w-0 items-start overflow-visible">
                                            <div
                                                class="min-w-0 flex-1 rounded-l border bg-gray-100 px-2 py-1 text-sm leading-5 text-gray-600 whitespace-normal break-words">
                                                <span x-text="row.fitemname || '-'"></span>
                                            </div>
                                            <button type="button" @click="openDesc(row)"
                                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-r border border-l-0 transition"
                                                :class="row.fdesc ? 'border-emerald-300 bg-emerald-50 text-emerald-600 hover:bg-emerald-100' : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-50'"
                                                title="Deskripsi item">
                                                <x-heroicon-o-document-text class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>

                                    <td class="p-2">
                                        <template x-if="row.units.length > 1 && !row.frefdtid">
                                             <select class="w-full border rounded px-2 py-1 text-sm"
                                                 :id="'unit_row_' + i" x-model="row.fsatuan"
                                                 @focus="activeRow = row.uid" @blur="activeRow = null"
                                                 @change="onRowUpdated(i)"
                                                 @keydown.enter.prevent="focusRowQty(i)">
                                                 <template x-for="unit in row.units" :key="unit">
                                                     <option :value="unit" x-text="unit"></option>
                                                 </template>
                                             </select>
                                         </template>
                                         <template x-if="row.units.length <= 1 || row.frefdtid">
                                             <input type="text"
                                                 class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                                 :value="row.fsatuan || '-'" disabled>
                                         </template>
                                    </td>

                                    <td class="p-2">
                                        <input type="text"
                                            class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                            :value="row.fprno || row.frefdtno || '-'" disabled>
                                    </td>

                                    <td class="p-2 text-right">
                                        <input type="number" class="border rounded px-2 py-1 w-20 text-right text-sm"
                                            :id="'qty_row_' + i" x-model.number="row.fqty" min="0" step="any"
                                            @focus="activeRow = row.uid; $event.target.select()"
                                            @blur="activeRow = null; enforcePrQtyRow(row)"
                                            @input="onRowUpdated(i)"
                                            @change="onRowUpdated(i)"
                                            @keydown.enter.prevent="focusRowPrice(i)">
                                        <div class="text-[10px] text-amber-700 font-medium text-right mt-0.5"
                                            x-show="row.frefdtid && formatPrRemainHint(row)"
                                            x-html="formatPrRemainHint(row)">
                                        </div>
                                    </td>

                                    <td class="p-2 text-right">
                                         <input type="text" inputmode="decimal" class="border rounded px-2 py-1 w-24 text-right text-sm"
                                             x-model="row.fpriceInput"
                                             @input="onPriceInput(row)"
                                             :id="'price_row_' + i"
                                             @focus="activeRow = row.uid; focusPriceInput(row); $event.target.select()"
                                             @blur="activeRow = null; blurPriceInput(row)"
                                             @change="recalc(row)" @keydown.enter.prevent="focusRowDisc(i)">
                                    </td>

                                    <td class="p-2 text-right">
                                         <input type="number" class="border rounded px-2 py-1 w-20 text-right text-sm"
                                             min="0" max="100" step="0.01" :value="Number(row.fdisc || 0).toFixed(2)"
                                             @input="row.fdisc = +$event.target.value; recalc(row)"
                                             :id="'disc_row_' + i"
                                             @focus="activeRow = row.uid; $event.target.select()"
                                             @blur="activeRow = null; $event.target.value = (+row.fdisc || 0).toFixed(2)"
                                             @change="recalc(row)" @keydown.enter.prevent="onRowUpdated(i)">
                                    </td>

                                    <td class="p-2">
                                        <input type="text"
                                            class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm text-right"
                                            :value="fmtCurr(row.ftotal)" disabled>
                                    </td>

                                    <td class="p-2">
                                        <input type="text"
                                            class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm text-right"
                                            :value="rupiah(itemTotalRp(row.ftotal))" disabled>
                                    </td>

                                    <td class="p-2 text-center">
                                        <div class="flex items-center justify-center">
                                            <button type="button" @click="removeRow(i)"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded bg-red-100 text-red-600 hover:bg-red-200"
                                                title="Hapus baris">
                                                -
                                            </button>
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
                            <input type="hidden" name="fitemcode[]" :value="row.fitemcode">
                            <input type="hidden" name="fitemname[]" :value="row.fitemname">
                            <input type="hidden" name="fsatuan[]" :value="row.fsatuan">
                            <input type="hidden" name="frefdtno[]" :value="row.frefdtno">
                            <input type="hidden" name="frefdtid[]" :value="row.frefdtid">
                            <input type="hidden" name="fnouref[]" :value="row.fnouref">
                            <input type="hidden" name="fnoacak[]" :value="row.fnoacak">
                            <input type="hidden" name="frefnoacak[]" :value="row.frefnoacak">
                            <input type="hidden" name="frefpr[]" :value="row.frefpr">
                            <input type="hidden" name="fprhid[]" :value="row.fprhid">
                            <input type="hidden" name="fqty[]" :value="row.fqty">
                            <input type="hidden" name="fprice[]" :value="row.fprice">
                            <input type="hidden" name="fdisc[]" :value="row.fdisc">
                            <input type="hidden" name="ftotal[]" :value="row.ftotal">
                            <input type="hidden" name="fdesc[]" :value="row.fdesc">
                            <input type="hidden" name="fketdt[]" :value="row.fketdt">
                        </div>
                    </template>
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

            {{-- Add PR + Panel Totals --}}
            <div x-data="prhFormModal()">
                    <div class="mt-3 flex justify-between items-start gap-4">
                        <div class="flex justify-start">
                            <button type="button" @click="openModal()"
                                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Add PR
                            </button>
                        </div>

                        {{-- Panel Totals --}}
                        <div class="w-[480px] shrink-0">
                            <div class="rounded-lg border bg-gray-50 p-3 space-y-2 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Total Harga</span>
                                    <span class="font-medium" x-text="fmtCurr(totalHarga)"></span>
                                </div>
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <label class="flex items-center gap-1.5 cursor-pointer select-none">
                                            <input type="checkbox" name="fapplyppn" value="1" x-model="includePPN"
                                                class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                            <span class="font-bold">PPN</span>
                                        </label>
                                        <select name="fincludeppn" x-model.number="ppnMode" :disabled="!includePPN"
                                            class="w-28 h-10 px-2 text-sm border rounded appearance-none disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed">
                                            <option value="0">Exclude</option>
                                            <option value="1">Include</option>
                                        </select>
                                        <input type="number" name="ppn_rate" min="0" max="100"
                                            step="0.01" x-model.number="ppnRate" :disabled="!includePPN"
                                            class="w-16 h-10 px-2 text-sm text-right border rounded [appearance:textfield] disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed">
                                        <span class="text-gray-500">%</span>
                                        <span class="flex-1"></span>
                                        <span class="font-medium" x-text="fmtCurr(ppnNominal)"></span>
                                    </div>
                                </div>
                                <div class="border-t"></div>
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-gray-800">
                                        Grand Total
                                        <span class="text-xs font-normal text-gray-500"
                                            x-text="selectedCurrCode ? '(' + selectedCurrCode + ')' : ''"></span>
                                    </span>
                                    <span class="font-bold text-blue-700" x-text="fmtCurr(grandTotal)"></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-gray-800">Grand Total (RP)</span>
                                    <span class="font-bold text-emerald-700" x-text="rupiah(grandTotalRp)"></span>
                                </div>
                            </div>
                            <input type="hidden" name="famountponet" :value="totalHarga">
                            <input type="hidden" name="famountpopajak" :value="ppnNominal">
                            <input type="hidden" name="famountpo" :value="grandTotal">
                            <input type="hidden" name="famountpo_rp" :value="grandTotalRp">
                        </div>
                    </div>

                    {{-- Modal backdrop --}}
                    <div x-show="show" x-transition.opacity class="fixed inset-0 z-40 bg-black/50"
                        @keydown.escape.window="closeModal()"></div>

                    {{-- MODAL PR --}}
                    <div x-show="show" x-cloak x-transition.opacity
                        class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-8" aria-modal="true"
                        role="dialog">
                        <div class="relative w-full max-w-5xl rounded-xl bg-white shadow-2xl flex flex-col"
                            style="height: 600px;">
                            <div
                                class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
                                <h3 class="text-xl font-bold text-gray-800">Pilih Purchase Request (PR)</h3>
                                <button type="button" @click="closeModal()"
                                    class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 font-medium text-gray-700 text-sm">
                                    Tutup
                                </button>
                            </div>
                            <div class="flex-1 overflow-y-auto p-6" style="min-height: 0;">
                                <table id="prTable" class="min-w-full text-sm display nowrap stripe hover"
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
                            <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50"></div>
                        </div>
                    </div>

                    {{-- Modal Duplikasi --}}
                    <div x-show="showDupModal" x-cloak x-transition.opacity
                        class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                        <div class="absolute inset-0 bg-black/40" @click="closeDupModal()"></div>
                        <div class="relative bg-white rounded-xl shadow-xl max-w-2xl w-full p-6">
                            <h3 class="text-lg font-semibold mb-4">Peringatan Duplikasi</h3>
                            <p class="mb-4">Ditemukan <strong x-text="dupCount"></strong> item yang sudah ada dalam
                                daftar.</p>
                            <div class="mb-4 max-h-48 overflow-auto border rounded p-2 bg-gray-50"
                                x-show="dupSample.length > 0">
                                <p class="text-sm font-medium mb-2">Contoh item duplikat:</p>
                                <template x-for="(item, idx) in dupSample" :key="idx">
                                    <div class="text-xs py-1">• <span x-text="item.fitemcode"></span></div>
                                </template>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button type="button" @click="closeDupModal()"
                                    class="rounded bg-gray-200 px-4 py-2 text-sm font-medium hover:bg-gray-300">Batal</button>
                            </div>
                        </div>
                    </div>
                </div>

                <input type="hidden" id="itemsCount" :value="rowsToSubmit.length">
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
                            @click="showNoSupplier=false; window.dispatchEvent(new CustomEvent('tr-poh-supplier-browse-open'))"
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

            {{-- MODAL SUPPLIER --}}
            <x-transaction.browse-supplier-modal event-name="tr-poh-supplier-browse-open" />

            <x-transaction.browse-product-modal />

            @php
                $canApproval = in_array('approvePO', explode(',', session('user_restricted_permissions', '')));
            @endphp

            <div class="flex justify-center items-center space-x-2 mt-6">
                @if ($canApproval)
                    <label class="block text-sm font-medium">Setujui Sekarang</label>
                    <input type="hidden" name="fapproval" value="0">
                    <label class="switch">
                        <input type="checkbox" name="fapproval" id="approvalToggle" value="1"
                            {{ old('fapproval', 0) ? 'checked' : '' }}>
                        <span class="slider"></span>
                    </label>
                @endif
            </div>

            <div class="mt-8 flex justify-center gap-4">
                <button type="submit"
                    class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                    <x-heroicon-o-check class="w-5 h-5 mr-2" /> Simpan
                </button>
                <button type="button" onclick="window.location.href='{{ route('tr_poh.index') }}'"
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

<style>
    div#productTable_length select,
    .dataTables_wrapper #productTable_length select,
    div#supplierBrowseTable_length select,
    .dataTables_wrapper #supplierBrowseTable_length select,
    div#prTable_length select,
    .dataTables_wrapper #prTable_length select {
        min-width: 140px !important;
        width: auto !important;
        padding: 8px 45px 8px 16px !important;
        font-size: 14px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.375rem !important;
    }

    div#productTable_length,
    .dataTables_wrapper #productTable_length,
    div#supplierBrowseTable_length,
    .dataTables_wrapper #supplierBrowseTable_length,
    div#prTable_length,
    .dataTables_wrapper #prTable_length {
        min-width: 250px !important;
    }

    div#productTable_length label,
    .dataTables_wrapper #productTable_length label,
    div#supplierBrowseTable_length label,
    .dataTables_wrapper #supplierBrowseTable_length label,
    div#prTable_length label,
    .dataTables_wrapper #prTable_length label {
        font-size: 14px !important;
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
    }
</style>

<script>
    window.PRODUCT_MAP = {
        @foreach ($products as $p)
            @php
                $defaultUnit = match ((string) ($p->fsatuandefault ?? '')) {
                    '1' => trim((string) ($p->fsatuankecil ?? '')),
                    '2' => trim((string) ($p->fsatuanbesar ?? '')),
                    '3' => trim((string) ($p->fsatuanbesar2 ?? '')),
                    default => trim((string) ($p->fsatuankecil ?? '')) ?: trim((string) ($p->fsatuanbesar ?? '')) ?: trim((string) ($p->fsatuanbesar2 ?? '')),
                };
            @endphp
            "{{ $p->fprdcode }}": {
                id: @json($p->fprdid),
                name: @json($p->fprdname),
                default_unit: @json($defaultUnit),
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

    window.CURRENCY_MAP = {
        @foreach ($currencies as $cur)
            {{ $cur->fcurrid }}: {
                id: {{ $cur->fcurrid }},
                code: @json($cur->fcurrcode),
                name: @json($cur->fcurrname),
                rate: {{ $cur->frate ?? 0 }}
            },
        @endforeach
    };

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

    window.fetchLastPrice = async function(fprdcode, fsupplier, fsatuan) {
        if (!fprdcode || !fsupplier || !fsatuan) return null;
        try {
            const url = new URL("{{ route('tr_poh.lastPrice') }}", window.location.origin);
            url.searchParams.set('fprdcode', fprdcode);
            url.searchParams.set('fsupplier', fsupplier);
            url.searchParams.set('fsatuan', fsatuan);
            const res = await fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (!res.ok) return null;
            return await res.json();
        } catch (e) {
            return null;
        }
    };

    function mainForm() {
        function baseRow() {
            return {
                uid: cryptoRandom(),
                fitemcode: '',
                fitemname: '',
                units: [],
                fsatuan: '',
                frefdtno: '',
                fnouref: '',
                fnoacak: '',
                frefnoacak: '',
                frefpr: '',
                fprhid: '',
                fprno: '',
                fqty: 0,
                fprice: 0,
                fdisc: 0,
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
                unit_ratios: {
                    satuankecil: 1,
                    satuanbesar: 1,
                    satuanbesar2: 1
                },
                maxqty_satuan: '',
                frefdtid: '',
                fqtypo: 0,
                fqtysisapr: 0,
                fqtydipo: 0,
                fqtykecil_ref: 0,
            };
        }

        return {
            autoCode: true,
            selectedCurrId: '',
            selectedCurrCode: 'IDR',
            rateValue: 1,
            includePPN: false,
            ppnMode: 0,
            ppnRate: 11,
            rows: [],
            rowsToSubmit: [],
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
            pendingRowsToSubmit: [],
            minimumVisibleRows: 5,

            rowHasContent(row) {
                if (!row) return false;
                return this.isRowFilled(row);
            },

            ensureMinimumRows() {
                while (this.rows.length < this.minimumVisibleRows) {
                    this.rows.push(this.createEmptyRow());
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
                    this.rows.push(this.createEmptyRow());
                }
            },

            onRowUpdated(index = null) {
                const row = typeof index === 'number' ? this.rows[index] : null;
                if (row) {
                    row.maxqty = this.calcMaxQty(row);
                    this.recalc(row);
                }
                this.ensureTrailingRow(index);
            },

            normalizeNoAcak(value) {
                return (value || '').toString().replace(/\D/g, '').slice(0, 3);
            },

            generateUniqueNoAcak(exceptUid = null) {
                const used = new Set(
                    this.rows
                        .filter((item) => item.uid !== exceptUid)
                        .map((item) => this.normalizeNoAcak(item.fnoacak))
                        .filter(Boolean)
                );
                let candidate = '';

                do {
                    candidate = Array.from({ length: 3 }, () => '123456789'[Math.floor(Math.random() * 9)]).join('');
                } while (used.has(candidate));

                return candidate;
            },

            get totalHarga() {
                return this.rows.reduce((sum, row) => {
                    if (!this.isRowSavable(row)) return sum;
                    return sum + (Number(row.ftotal) || 0);
                }, 0);
            },
            get ppnNominal() {
                if (!this.includePPN) return 0;
                const total = this.totalHarga;
                const rate = +this.ppnRate || 0;
                return this.ppnMode === 1 ? Math.round(total * rate / (100 + rate)) : Math.round(total * rate / 100);
            },
            get grandTotal() {
                if (!this.includePPN) return this.totalHarga;
                return this.ppnMode === 1 ? this.totalHarga : this.totalHarga + this.ppnNominal;
            },
            get grandTotalRp() {
                if (!this.selectedCurrCode || this.selectedCurrCode === 'IDR') return this.grandTotal;
                return +(this.grandTotal * (+this.rateValue || 1)).toFixed(2);
            },

            fmtCurr(n) {
                const v = Number(n || 0);
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
            itemTotalRp(value) {
                const total = Number(value || 0);
                if (!Number.isFinite(total)) return 0;
                if (!this.selectedCurrCode || this.selectedCurrCode === 'IDR') return total;
                return +(total * (+this.rateValue || 1)).toFixed(2);
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
                this.$nextTick(() => form.submit());
            },

            onCurrencyChange() {
                const id = parseInt(this.selectedCurrId);
                const cur = window.CURRENCY_MAP[id];
                if (cur) {
                    this.selectedCurrCode = cur.code;
                    this.rateValue = cur.rate;
                } else {
                    this.selectedCurrCode = '';
                    this.rateValue = 0;
                }
            },

            recalc(row) {
                const qty = Math.max(0, +row.fqty || 0);
                const price = Math.max(0, +row.fprice || 0);
                const disc = Math.min(100, Math.max(0, +row.fdisc || 0));
                row.fqty = qty;
                row.fprice = price;
                if (typeof row.fpriceInput === 'undefined') {
                    row.fpriceInput = price.toFixed(2);
                }
                row.fdisc = disc;
                row.ftotal = +(qty * price * (1 - disc / 100)).toFixed(2);
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
                this.recalc(row);
            },

            blurPriceInput(row) {
                row.fprice = Math.max(0, +(row.fpriceInput || 0));
                row.fpriceInput = row.fprice.toFixed(2);
                this.recalc(row);
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

            formatPrRemainHint(row) {
                return '';
            },

            enforcePrQtyRow(row) {
                const n = Number(row.fqty);
                if (!Number.isFinite(n)) {
                    row.fqty = 0;
                    this.recalc(row);
                    return;
                }
                if (n < 0) row.fqty = 0;
                if (!row.frefdtid) {
                    this.recalc(row);
                    return;
                }
                row.maxqty = this.calcMaxQty(row);
                this.recalc(row);
            },

            hydrateRowFromMeta(row, meta, keepMaxqty = false, forceDefaultUnit = false) {
                if (!meta) {
                    row.fitemname = '';
                    row.units = [];
                    row.fsatuan = '';
                    if (!keepMaxqty) row.maxqty = 0;
                    return;
                }
                row.fitemname = meta.name || '';
                const units = [...new Set((meta.units || []).map((u) => (u ?? '').toString().trim()).filter(Boolean))];
                const currentSatuan = (row.fsatuan || '').trim();
                const defaultUnit = (meta.default_unit || '').toString().trim();
                const resolvedDefaultUnit = defaultUnit && units.includes(defaultUnit) ? defaultUnit : (units[0] || '');
                if (currentSatuan && !units.includes(currentSatuan)) units.unshift(currentSatuan);
                row.units = units;
                if (!currentSatuan || forceDefaultUnit) row.fsatuan = resolvedDefaultUnit;
                if (meta.unit_ratios) row.unit_ratios = meta.unit_ratios;
                row.fsatuankecil = row.fsatuankecil || meta.fsatuankecil || '';
                row.fsatuanbesar = row.fsatuanbesar || meta.fsatuanbesar || '';
                row.fsatuanbesar2 = row.fsatuanbesar2 || meta.fsatuanbesar2 || '';
                row.fqtykecil = Number(row.fqtykecil || meta.fqtykecil || 0);
                row.fqtykecil2 = Number(row.fqtykecil2 || meta.fqtykecil2 || 0);
                if (!keepMaxqty) row.maxqty = 0;
            },

            onCodeTypedRow(row, index = null) {
                this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode), false, true);
                row.fnoacak = this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak(row.uid);
                row.maxqty = this.calcMaxQty(row);
                this.recalc(row);
                this.$nextTick(() => this.applyLastPrice(row));
                this.onRowUpdated(index);
            },

            getSupplier() {
                return (document.getElementById('supplierCodeHidden')?.value || '').trim();
            },

            syncSupplierTempo(code = null) {
                const supplierCode = (code ?? this.getSupplier() ?? '').toString().trim();
                const sel = document.getElementById('modal_filter_supplier_id');
                const tempoInput = document.getElementById('ftempohr');
                if (!sel || !tempoInput) return;

                const selectedOption = Array.from(sel.options).find((option) => String(option.value) === supplierCode);
                const tempo = Number(selectedOption?.dataset?.tempo ?? 0);
                tempoInput.value = Number.isFinite(tempo) ? tempo : 0;
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

                if (!found && supplierCode) {
                    const option = new Option(supplierCode, supplierCode, true, true);
                    option.dataset.tempo = '0';
                    sel.add(option);
                }

                sel.dispatchEvent(new Event('change', { bubbles: true }));
                this.syncSupplierTempo(supplierCode);
            },

            buildRow(source = {}) {
                const row = {
                    ...baseRow(),
                    ...source
                };
                row.uid = row.uid || cryptoRandom();
                row.fitemcode = (row.fitemcode || '').toString().trim();
                row.fitemname = (row.fitemname || '').toString();
                row.fsatuan = (row.fsatuan || '').toString().trim();
                row.frefdtno = (row.frefdtno || '').toString();
                row.frefdtid = (row.frefdtid || '').toString();
                row.fnouref = (row.fnouref || '').toString();
                row.fnoacak = this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak(row.uid);
                row.frefnoacak = this.normalizeNoAcak(row.frefnoacak);
                row.frefpr = (row.frefpr || '').toString();
                row.fprhid = (row.fprhid || '').toString();
                row.fprno = (row.fprno || '').toString();
                row.fqty = Number(row.fqty || 0);
                row.fprice = Number(row.fprice || 0);
                row.fpriceInput = row.fprice.toFixed(2);
                row.fdisc = Number(row.fdisc || 0);
                row.ftotal = Number(row.ftotal || 0);
                row.fdesc = (row.fdesc || '').toString();
                row.fketdt = (row.fketdt || '').toString();
                row.fqtypr = Number(row.fqtypr || 0);
                row.fqtypo = Number(row.fqtypo || 0);
                row.fqtysisapr = Number(row.fqtysisapr || 0);
                row.fqtydipo = Number(row.fqtydipo || 0);
                row.fqtykecil_ref = Number(row.fqtykecil_ref || 0);

                this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode), true);
                row.maxqty = this.calcMaxQty(row);
                this.recalc(row);
                return row;
            },

            createEmptyRow(source = {}) {
                return this.buildRow({
                    ...source,
                    fnoacak: source.fnoacak || this.generateUniqueNoAcak()
                });
            },

            restoreRows(items = []) {
                if (Array.isArray(items) && items.length > 0) {
                    this.rows = items
                        .filter((item) => (item?.fitemcode || '').toString().trim() !== '')
                        .map((item) => this.buildRow(item));
                }

                if (this.rows.length === 0) {
                    this.rows = [this.createEmptyRow()];
                }

                this.ensureMinimumRows();
                this.ensureTrailingRow();
            },

            async applyLastPrice(row) {
                const supplier = this.getSupplier();
                const code = (row.fitemcode || '').trim();
                const satuan = (row.fsatuan || '').trim();
                if (!code || !supplier || !satuan) return;
                const hist = await window.fetchLastPrice(code, supplier, satuan);
                if (!hist) return;
                if (!row.fprice || row.fprice === 0) {
                    row.fprice = hist.fprice;
                    row.fpriceInput = Number(row.fprice || 0).toFixed(2);
                    row.fdisc = hist.fdisc ?? 0;
                    this.recalc(row);
                }
            },

            isRowFilled(row) {
                return [
                    row.fitemcode,
                    row.fitemname,
                    row.fsatuan,
                    row.frefdtno,
                    row.fqty,
                    row.fprice,
                    row.fdisc,
                    row.fdesc,
                    row.fketdt
                ].some((value) => String(value ?? '').trim() !== '' && Number(value ?? 0) !== 0)
                    || Number(row.fqty || 0) > 0;
            },

            isRowSavable(row) {
                return !!(
                    (row.fitemcode || '').trim() &&
                    (row.fitemname || '').trim() &&
                    (row.fsatuan || '').trim() &&
                    Number(row.fqty || 0) > 0
                );
            },

            rowWarningLabel(row) {
                return `Data Produk ${row.fitemname || row.fitemcode || '(tanpa nama)'} qty masih 0, tidak akan tersimpan.`;
            },

            prepareRowsForSubmit() {
                const seenCodes = new Set();
                for (const row of this.rows) {
                    const code = (row.fitemcode || '').trim().toUpperCase();
                    if (!code) continue;
                    if (seenCodes.has(code)) {
                        this.showWarning('Produk Duplikat', `Kode produk ${code} tidak boleh sama dalam satu Order Pembelian.`);
                        return null;
                    }
                    seenCodes.add(code);
                }

                return this.rows.map((row) => {
                    row.fnoacak = this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak(row.uid);
                    row.frefnoacak = this.normalizeNoAcak(row.frefnoacak);
                    this.recalc(row);
                    return row;
                });
            },

            removeRow(index) {
                if (this.rows.length === 1) {
                    this.rows.splice(0, 1, this.createEmptyRow());
                    this.ensureMinimumRows();
                    return;
                }
                this.rows.splice(index, 1);
                this.ensureMinimumRows();
            },

            isDupeItem(candidate, exceptUid = null) {
                const cCode = (candidate.fitemcode || '').trim().toLowerCase();
                const cSatuan = (candidate.fsatuan || '').trim().toLowerCase();
                const cName = (candidate.fitemname || '').trim().toLowerCase();
                const cMeta = this.productMeta(candidate.fitemcode);
                const cId = cMeta?.id ?? null;

                return this.rows.some((row) => {
                    if (row.uid === exceptUid) return false;
                    if (!(row.fitemcode || '').trim()) return false;

                    const rowCode = (row.fitemcode || '').trim().toLowerCase();
                    const rowSatuan = (row.fsatuan || '').trim().toLowerCase();
                    const rowName = (row.fitemname || '').trim().toLowerCase();
                    const rowMeta = this.productMeta(row.fitemcode);
                    const rowId = rowMeta?.id ?? null;

                    if (rowCode === cCode) return true;
                    if (cId && rowId && cId === rowId && (!cSatuan || rowSatuan === cSatuan)) return true;
                    if (cName && rowName && cName === rowName && (!cSatuan || rowSatuan === cSatuan)) return true;
                    return false;
                });
            },

            focusRowUnit(row, index) {
                if (row.units.length > 1) {
                    this.$nextTick(() => document.getElementById('unit_row_' + index)?.focus());
                    return;
                }
                this.focusRowQty(index);
            },
            focusRowQty(index) {
                this.$nextTick(() => document.getElementById('qty_row_' + index)?.focus());
            },
            focusRowPrice(index) {
                this.$nextTick(() => document.getElementById('price_row_' + index)?.focus());
            },
            focusRowDisc(index) {
                this.$nextTick(() => document.getElementById('disc_row_' + index)?.focus());
            },

            // Sisa PR (tr_prd.fqtykecil) disimpan di satuan kecil. Konversi ke satuan PO memakai rasio master.
            calcMaxQty(row) {
                const eq = (a, b) => (a || '').trim().toLowerCase() === (b || '').trim().toLowerCase();
                const satuanPO = (row.fsatuan || '').trim();
                const satuanPR = (row.fqtypr_satuan || '').trim();
                const satKecil = (row.fsatuankecil || '').trim();
                const satBesar = (row.fsatuanbesar || '').trim();
                const satBesar2 = (row.fsatuanbesar2 || '').trim();
                const rasio = Number(row.fqtykecil || 0);
                const rasio2 = Number(row.fqtykecil2 || 0);
                const sisaPrBaris = Number(row.fqtysisapr ?? 0);

                if (sisaPrBaris > 0 && (!satuanPR || eq(satuanPO, satuanPR))) {
                    return sisaPrBaris;
                }

                const hasRemainField = row.fqtykecil_ref !== undefined && row.fqtykecil_ref !== null && row.fqtykecil_ref !== '';
                let sisaKecil = 0;

                if (hasRemainField) {
                    sisaKecil = Math.max(0, Number(row.fqtykecil_ref) || 0);
                } else {
                    const qtyPR = Number(row.fqtypr) || 0;
                    const fqtypo = Number(row.fqtypo) || 0;
                    if (!satuanPR || !(qtyPR > 0)) return 0;
                    let qtyPRInKecil = qtyPR;
                    if (eq(satuanPR, satBesar) && rasio > 0) qtyPRInKecil = qtyPR * rasio;
                    else if (eq(satuanPR, satBesar2) && rasio2 > 0) qtyPRInKecil = qtyPR * rasio2;
                    sisaKecil = Math.max(0, qtyPRInKecil - fqtypo);
                }

                if (!satuanPO || eq(satuanPO, satKecil)) return sisaKecil;
                if (eq(satuanPO, satBesar) && rasio > 0) return Math.floor(sisaKecil / rasio);
                if (eq(satuanPO, satBesar2) && rasio2 > 0) return Math.floor(sisaKecil / rasio2);
                return sisaKecil;
            },

            onPrPicked(e) {
                const { header, items } = e.detail || {};
                if (!Array.isArray(items)) return;

                const skipped = [];
                const addedRows = [];

                items.forEach((src) => {
                    const fsatuan = (src.fsatuan ?? '').trim();
                    const meta = this.productMeta(src.fitemcode ?? '');
                    const fitemname = meta ? (meta.name || src.fitemname || '') : (src.fitemname ?? '');
                    const candidate = {
                        fitemcode: (src.fitemcode ?? '').trim(),
                        fitemname,
                        fsatuan
                    };

                    if (this.isDupeItem(candidate)) {
                        skipped.push(src);
                        return;
                    }

                    const row = this.buildRow({
                        fitemcode: src.fitemcode ?? '',
                        fitemname,
                        units: Array.isArray(src.units) ? src.units : [],
                        fsatuan,
                        frefdtno: src.frefdtno ?? '',
                        fnouref: src.fnouref ?? '',
                        fnoacak: this.generateUniqueNoAcak(),
                        frefnoacak: this.normalizeNoAcak(src.frefnoacak ?? src.fnoacak ?? ''),
                        frefpr: String(header?.fprhid ?? src.fprhid ?? ''),
                        fprhid: String(src.fprhid ?? header?.fprhid ?? ''),
                        fprno: String(header?.fprno ?? src.fprno ?? ''),
                        fqty: Number(src.fqty ?? 0),
                        fqtypo: Number(src.fqtypo ?? 0),
                        fqtysisapr: Number(src.fqtysisapr ?? 0),
                        fqtydipo: Number(src.fqtydipo ?? 0),
                        fqtykecil_ref: Number(src.fqtykecil_ref ?? src.fqtyremain ?? 0),
                        fqtypr: Number(src.fqtypr ?? src.fqty ?? 0),
                        fqtypr_satuan: (src.fqtypr_satuan ?? src.fsatuan ?? '').trim(),
                        frefdtid: src.frefdtid ?? '',
                        fsatuankecil: src.fsatuankecil || meta?.fsatuankecil || '',
                        fsatuanbesar: src.fsatuanbesar || meta?.fsatuanbesar || '',
                        fsatuanbesar2: src.fsatuanbesar2 || meta?.fsatuanbesar2 || '',
                        fqtykecil: Number(src.fqtykecil ?? meta?.fqtykecil ?? 0),
                        fqtykecil2: Number(src.fqtykecil2 ?? meta?.fqtykecil2 ?? 0),
                        maxqty_satuan: src.maxqty_satuan ?? '',
                        fprice: Number(src.fprice ?? 0),
                        fdisc: Number(src.fdisc ?? 0),
                        ftotal: Number(src.ftotal ?? 0),
                        fdesc: src.fdesc ?? src.fketdt ?? '',
                        fketdt: src.fketdt ?? '',
                    });

                    row.maxqty = this.calcMaxQty(row);
                    if (Number(row.maxqty) > 0) {
                        row.fqty = Number(row.maxqty);
                        this.recalc(row);
                    }

                    addedRows.push(row);
                });

                if (addedRows.length > 0) {
                    const shouldReplaceStarter = this.rows.every((row) => !this.isRowFilled(row));
                    if (shouldReplaceStarter) {
                        this.rows = addedRows;
                    } else {
                        this.rows.push(...addedRows);
                    }

                    this.ensureMinimumRows();
                    this.ensureTrailingRow();

                    addedRows.forEach((row) => {
                        if (!row.fprice || row.fprice === 0) {
                            this.$nextTick(() => this.applyLastPrice(row));
                        }
                    });
                }

                if (skipped.length > 0 && addedRows.length === 0) {
                    this.showDupItemModal = true;
                    this.dupItemName = skipped.map((item) => item.fitemname || item.fitemcode).join(', ');
                    this.dupItemSatuan = '';
                }
            },

            itemKey(it) {
                return `${(it.fitemcode ?? '').toString().trim()}::${(it.fsatuan ?? '').toString().trim()}`;
            },
            getCurrentItemKeys() {
                return this.rows.map((it) => this.itemKey(it));
            },

            openBrowseFor(index) {
                if (!this.getSupplier()) {
                    this.showNoSupplier = true;
                    return;
                }
                this.browseTarget = index;
                window.dispatchEvent(new CustomEvent('browse-open', {
                    detail: {
                        forEdit: false
                    }
                }));
            },

            submitForm(form) {
                const preparedRows = this.prepareRowsForSubmit();
                if (!preparedRows) return;
                const validRows = preparedRows.filter((row) => this.isRowSavable(row));
                const warningRows = preparedRows.filter((row) => this.isRowFilled(row) && !this.isRowSavable(row));

                if (warningRows.length > 0) {
                    this.warningTitle = 'Qty Belum Diisi';
                    this.warningMessage = validRows.length > 0
                        ? 'Beberapa item tidak akan disimpan karena qty masih 0.'
                        : 'Tidak ada item yang bisa disimpan karena qty masih 0 atau data belum lengkap.';
                    this.warningItems = warningRows.map((row) => this.rowWarningLabel(row));
                    this.warningCanProceed = validRows.length > 0;
                    this.pendingSubmitForm = form;
                    this.pendingRowsToSubmit = validRows;
                    this.showWarningModal = true;
                    if (validRows.length < 1) return;
                    return;
                }

                if (validRows.length < 1) {
                    this.showNoItems = true;
                    return;
                }

                this.rowsToSubmit = validRows;
                this.$nextTick(() => form.submit());
            },

            init() {
                const idrEntry = Object.values(window.CURRENCY_MAP).find((c) => c.code === 'IDR');
                if (idrEntry && !this.selectedCurrId) {
                    this.selectedCurrId = String(idrEntry.id);
                    this.selectedCurrCode = idrEntry.code;
                    this.rateValue = 1;
                }

                this.syncSupplierDisplay(@js(old('fsupplier', '')));
                this.restoreRows(@js($initialPoItems));
                window.getCurrentItemKeys = () => this.getCurrentItemKeys();
                window.isDupeItem = (candidate) => this.isDupeItem(candidate);

                const supplierSelect = document.getElementById('modal_filter_supplier_id');
                supplierSelect?.addEventListener('change', () => {
                    this.syncSupplierTempo(supplierSelect.value);
                });

                if (this._ac) this._ac.abort();
                this._ac = new AbortController();
                const sig = {
                    signal: this._ac.signal,
                    passive: true
                };

                window.addEventListener('show-no-supplier', () => {
                    this.showNoSupplier = true;
                }, sig);

                window.addEventListener('pr-picked', (e) => this.onPrPicked(e), sig);

                window.addEventListener('product-chosen', (e) => {
                    const { product } = e.detail || {};
                    if (!product || typeof this.browseTarget !== 'number') return;

                    const row = this.rows[this.browseTarget];
                    if (!row) return;

                    const candidate = {
                        fitemcode: (product.fprdcode || '').toString(),
                        fitemname: product.fprdname || '',
                        fsatuan: row.fsatuan || ''
                    };

                    row.fitemcode = candidate.fitemcode;
                    this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode), true, true);
                    candidate.fsatuan = row.fsatuan || '';

                    if (this.isDupeItem(candidate, row.uid)) {
                        this.showDupItemModal = true;
                        this.dupItemName = row.fitemname || row.fitemcode;
                        this.dupItemSatuan = row.fsatuan || '';
                        row.fitemcode = '';
                        row.fitemname = '';
                        row.units = [];
                        row.fsatuan = '';
                        return;
                    }

                    row.fnoacak = this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak(row.uid);
                    row.maxqty = this.calcMaxQty(row);
                    if (!row.fqty && !row.frefdtid) row.fqty = 1;
                    this.recalc(row);

                    const targetIndex = this.browseTarget;
                    this.onRowUpdated(targetIndex);
                    this.$nextTick(() => {
                        this.applyLastPrice(row);
                        document.getElementById('qty_row_' + targetIndex)?.focus();
                    });
                }, sig);
            }
        };
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
</script>

<script>
    window.prhFormModal = function() {
        return {
            show: false,
            table: null,
            showDupModal: false,
            dupCount: 0,
            dupSample: [],
            pendingHeader: null,
            pendingUniques: [],
            initDataTable() {
                if (this.table) this.table.destroy();
                this.table = $('#prTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('tr_poh.pickable') }}",
                        type: 'GET',
                        data: d => ({
                            draw: d.draw,
                            start: d.start,
                            length: d.length,
                            search: d.search.value,
                            order_column: d.columns[d.order[0].column].data,
                            order_dir: d.order[0].dir
                        })
                    },
                    columns: [{
                            data: 'fprno',
                            name: 'fprno',
                            className: 'font-mono text-sm'
                        },
                        {
                            data: 'fsuppliername',
                            name: 'fsuppliername',
                            className: 'text-sm',
                            render: d => d || '-'
                        },
                        {
                            data: 'fprdate',
                            name: 'fprdate',
                            className: 'text-sm',
                            render: d => formatDate(d)
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center',
                            render: () =>
                                '<button type="button" class="btn-pick px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 text-white">Pilih</button>'
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
                        [2, 'desc']
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
                const self = this;
                $('#prTable').off('click', '.btn-pick').on('click', '.btn-pick', function() {
                    self.pick(self.table.row($(this).closest('tr')).data());
                });
            },
            openModal() {
                if (!(document.getElementById('supplierCodeHidden')?.value || '').trim()) {
                    window.dispatchEvent(new CustomEvent('show-no-supplier'));
                    return;
                }
                this.show = true;
                this.$nextTick(() => this.initDataTable());
            },
            closeModal() {
                this.show = false;
                if (this.table) this.table.search('').draw();
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
            async pick(row) {
                try {
                    const url = `{{ route('tr_poh.items', ['id' => 'PR_ID_PLACEHOLDER']) }}`.replace(
                        'PR_ID_PLACEHOLDER', row.fprhid);
                    const res = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const json = await res.json();
                    const items = json.items || [];
                    const checkDupe = window.isDupeItem ?
                        (src) => window.isDupeItem({
                            fitemcode: src.fitemcode ?? '',
                            fitemname: src.fitemname ?? '',
                            fsatuan: src.fsatuan ?? ''
                        }) :
                        () => false;
                    const duplicates = items.filter(src => checkDupe(src));
                    const uniques = items.filter(src => !checkDupe(src));
                    if (duplicates.length > 0) {
                        this.openDupModal(row, duplicates, uniques);
                        return;
                    }
                    window.dispatchEvent(new CustomEvent('pr-picked', {
                        detail: {
                            header: row,
                            items
                        }
                    }));
                    this.closeModal();
                } catch (e) {
                    console.error(e);
                }
            }
        };
    };

    function formatDate(s) {
        if (!s || s === 'No Date') return '-';
        const d = new Date(s);
        if (isNaN(d.getTime())) return '-';
        const pad = n => n.toString().padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }
</script>

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    @include('components.transaction.browse-product-script')
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
