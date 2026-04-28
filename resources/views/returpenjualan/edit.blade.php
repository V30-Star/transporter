@extends('layouts.app')

@section('title', $action === 'delete' ? 'Hapus Retur Penjualan' : 'Edit Retur Penjualan')

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

        /* Hilangkan panah di input number (Firefox) */
        input[type=number] {
            -moz-appearance: textfield;
        }

        .delete-readonly [role="button"],
        .delete-readonly button,
        .delete-readonly a {
            pointer-events: none;
        }

        .delete-readonly select,
        .delete-readonly input:not([type="hidden"]),
        .delete-readonly textarea,
        .delete-readonly button {
            background-color: #e5e7eb;
            cursor: not-allowed;
        }
    </style>
    @php
        $usageLocked = !empty($isUsageLocked);
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
                            {{ $action === 'delete' ? 'Retur Penjualan Tidak Dapat Dihapus' : 'Retur Penjualan Tidak Dapat Diedit' }}
                        </h3>
                        <p class="text-sm text-orange-500 mt-0.5">{{ $usageLockMessage }}</p>
                    </div>
                    <button type="button" @click="open = false"
                        class="flex-shrink-0 w-8 h-8 rounded-full bg-orange-100 hover:bg-orange-200 flex items-center justify-center transition-colors"
                        title="Tutup">
                        <x-heroicon-o-x-mark class="w-4 h-4 text-orange-600" />
                    </button>
                </div>
                <div class="px-6 py-4 border-t bg-gray-50 flex justify-end">
                    <button type="button" @click="open = false"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center gap-2">
                        <x-heroicon-o-arrow-left class="w-5 h-5" />
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div>
        <div class="lg:col-span-12">
            <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1600px] w-full mx-auto">
                @if ($action === 'delete')
                    <fieldset disabled class="delete-readonly space-y-4">

                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                            <div class="lg:col-span-4">
                                <label class="block text-sm font-medium">Cabang</label>
                                <input type="text" class="w-full border rounded px-3 py-2 bg-gray-200 cursor-not-allowed"
                                    value="{{ $fcabang }}" disabled>
                                <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                            </div>

                            {{-- SO# --}}
                            <div class="lg:col-span-4" x-data="{ autoCode: false }">
                                <label class="block text-sm font-medium mb-1">SO#</label>
                                <div class="flex items-center gap-3">
                                    <input type="text" name="fsono" value="{{ old('fsono', $returpenjualan->fsono) }}"
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
                                <label class="block text-sm font-medium">Retur Pajak#</label>
                                <input type="text" name="ftaxno" value="{{ old('ftaxno', $returpenjualan->ftaxno) }}"
                                    class="w-full border rounded px-3 py-2 @error('ftaxno') border-red-500 @enderror"
                                    readonly>
                                @error('ftaxno')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="lg:col-span-4">
                                <label class="block text-sm font-medium">Type</label>
                                <select name="ftypesales" id="ftypesales" x-model.number="ftypesales"
                                    x-init="ftypesales = 0" disabled
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
                                <input disabled type="date" name="fsodate" value="{{ old('fsodate') ?? date('Y-m-d') }}"
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
                                                <option value="{{ $customer->fcustomercode }}" {{-- CEK DISINI: Bandingkan dengan data yang tersimpan di DB --}}
                                                    {{ old('fcustno', $returpenjualan->fcustno) == $customer->fcustomercode ? 'selected' : '' }}>
                                                    {{ $customer->fcustomername }} ({{ $customer->fcustomercode }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="absolute inset-0" role="button" aria-label="Browse Customer"
                                            @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))">
                                        </div>
                                    </div>
                                    <input type="hidden" name="fcustno" id="customerCodeHidden"
                                        value="{{ old('fcustno', $returpenjualan->fcustno) }}">
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
                                                <option value="{{ $salesman->fsalesmancode }}" {{-- CEK DISINI: Bandingkan old input atau data dari database --}}
                                                    {{ old('fsalesman', $returpenjualan->fsalesman) == $salesman->fsalesmancode ? 'selected' : '' }}>
                                                    {{ $salesman->fsalesmanname }} ({{ $salesman->fsalesmancode }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="absolute inset-0" role="button" aria-label="Browse Salesman"
                                            @click="window.dispatchEvent(new CustomEvent('salesman-browse-open'))">
                                        </div>
                                    </div>
                                    <input type="hidden" name="fsalesman" id="salesmanCodeHidden"
                                        value="{{ old('fsalesman', $returpenjualan->fsalesman) }}">
                                </div>
                                @error('fsalesman')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="lg:col-span-4">
                                <label class="block text-sm font-medium">Tgl. Jatuh Tempo</label>
                                <input type="date" id="fjatuhtempo" name="fjatuhtempo" readonly
                                    value="{{ old('fjatuhtempo') ?? date('Y-m-d') }}" readonly
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
                                    placeholder="Keterangan isi di sini...">{{ old('fket', $returpenjualan->fket) }}</textarea>
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
                                                <td class="p-2 font-mono" x-text="it.fitemcode"></td>
                                                <td class="p-2 text-gray-800" x-text="it.fitemname"></td>
                                                <td class="p-2">
                                                    <template x-if="it.units && it.units.length > 1">
                                                        <select class="w-full border rounded px-2 py-1 text-xs"
                                                            x-model="it.fsatuan">
                                                            <template x-for="u in it.units" :key="u">
                                                                <option :value="u" x-text="u"
                                                                    :selected="u === it.fsatuan"></option>
                                                            </template>
                                                        </select>
                                                    </template>
                                                    <template x-if="!it.units || it.units.length <= 1">
                                                        <span x-text="it.fsatuan"></span>
                                                    </template>
                                                </td>
                                                <td class="p-2">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800"
                                                        x-text="it.frefpr || it.frefcode || '-'">
                                                    </span>
                                                </td>
                                                <td class="p-2 text-right">
                                                    <input type="number"
                                                        class="w-full border rounded px-2 py-1 text-right"
                                                        x-model.number="it.fqty"
                                                        @input="recalc(it); enforceQtyRow(it); recalc(it);">
                                                </td>
                                                <td class="p-2 text-right">
                                                    <input type="number"
                                                        class="w-full border rounded px-2 py-1 text-right"
                                                        x-model.number="it.fprice" @input="recalc(it)">
                                                </td>
                                                <td class="p-2 text-right">
                                                    <input type="text"
                                                        class="w-full border rounded px-2 py-1 text-right"
                                                        x-model="it.fdisc" @input="recalc(it)">
                                                </td>
                                                <td class="p-2 text-right font-semibold" x-text="fmt(it.ftotal)"></td>
                                                <td class="p-2 text-center">
                                                    <button type="button" @click="removeSaved(i)"
                                                        class="px-3 py-1 rounded text-xs bg-red-100 text-red-600 hover:bg-red-200">Hapus</button>

                                                    <!-- Hidden inputs moved here to ensure they are submitted -->
                                                    <input type="hidden" name="fitemcode[]" :value="it.fitemcode">
                                                    <input type="hidden" name="fitemname[]" :value="it.fitemname">
                                                    <input type="hidden" name="fsatuan[]" :value="it.fsatuan">
                                                    <input type="hidden" name="frefcode[]" :value="it.frefcode">
                                                    <input type="hidden" name="fnouref[]" :value="it.fnouref">
                                                    <input type="hidden" name="frefpr[]" :value="it.frefpr">
                                                    <input type="hidden" name="frefso[]" :value="it.frefso">
                                                    <input type="hidden" name="frefsoid[]" :value="it.frefsoid">
                                                    <input type="hidden" name="frefsrj[]" :value="it.frefsrj">
                                                    <input type="hidden" name="frefsrjid[]" :value="it.frefsrjid">
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

                                            <tr class="border-b transition-colors hover:bg-gray-50">
                                                <td class="p-0"></td>
                                                <td class="p-0"></td>
                                                <td class="p-2" colspan="2">
                                                    <textarea x-model="it.fdesc" rows="3" class="w-full border rounded px-2 py-1 text-xs"
                                                        placeholder="Deskripsi item (opsional)"></textarea>
                                                </td>
                                                <td class="p-1" colspan="6"></td>
                                            </tr>
                                        </template>

                                        <!-- ROW DRAFT UTAMA -->
                                        <tr class="border-t align-top">
                                            <td class="p-2" x-text="savedItems.length + 1"></td>
                                            <td class="p-2">
                                                <div class="flex">
                                                    <input type="text"
                                                        class="flex-1 border rounded-l px-2 py-1 font-mono"
                                                        x-ref="draftCode" x-model.trim="draft.fitemcode"
                                                        @input="onCodeTypedRow(draft)"
                                                        @keydown.enter.prevent="handleEnterOnCode('draft')">
                                                    <button type="button" @click="openBrowseFor('draft')"
                                                        class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50">
                                                        <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="p-2">
                                                <input type="text" class="w-full border rounded px-2 py-1 bg-gray-100"
                                                    :value="draft.fitemname" disabled>
                                            </td>
                                            <td class="p-2">
                                                <template x-if="draft.units.length > 1">
                                                    <select class="w-full border rounded px-2 py-1"
                                                        x-model="draft.fsatuan">
                                                        <template x-for="u in draft.units" :key="u">
                                                            <option :value="u" x-text="u"></option>
                                                        </template>
                                                    </select>
                                                </template>
                                                <template x-if="draft.units.length <= 1">
                                                    <input type="text"
                                                        class="w-full border rounded px-2 py-1 bg-gray-100"
                                                        :value="draft.fsatuan || '-'" disabled>
                                                </template>
                                            </td>
                                            <td class="p-2">
                                                <input type="text" class="w-full border rounded px-2 py-1 bg-gray-100"
                                                    :value="draft.frefcode" disabled placeholder="Ref SO">
                                            </td>
                                            <td class="p-2 text-right">
                                                <input type="number" class="border rounded px-2 py-1 w-24 text-right"
                                                    x-model.number="draft.fqty" @input="
                                                        recalc(draft);
                                                        enforceQtyRow(draft);
                                                        recalc(draft);
                                                    " x-ref="draftQty">
                                            </td>
                                            <td class="p-2 text-right">
                                                <input type="number" class="border rounded px-2 py-1 w-28 text-right"
                                                    x-model.number="draft.fprice" @input="recalc(draft)"
                                                    x-ref="draftPrice">
                                            </td>
                                            <td class="p-2 text-right">
                                                <input type="text" class="border rounded px-2 py-1 w-24 text-right"
                                                    x-model="draft.fdisc" @input="recalc(draft)" x-ref="draftDisc">
                                            </td>
                                            <td class="p-2 text-right font-semibold" x-text="fmt(draft.ftotal)"></td>
                                            <td class="p-2 text-center">
                                                <button type="button" @click="addIfComplete()"
                                                    class="px-3 py-1 rounded text-xs bg-emerald-600 text-white">Tambah</button>
                                            </td>
                                        </tr>

                                        <!-- ROW DRAFT DESC -->
                                        <tr class="border-b">
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                            <td class="p-2" colspan="7">
                                                <textarea x-model="draft.fdesc" rows="1" class="w-full border rounded px-2 py-1 text-xs"
                                                    placeholder="Deskripsi draft (opsional)"></textarea>
                                            </td>
                                            <td class="p-0"></td>
                                        </tr>
                                    </tbody>
                                </table>
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
                                                <span class="text-sm text-gray-700">Total Harga</span>
                                                <span class="min-w-[140px] text-right font-medium"
                                                    x-text="rupiah(totalHarga)"></span>
                                            </div>
                                            <div class="flex items-center justify-between gap-6">
                                                <!-- Checkbox -->
                                                <div class="flex items-center">
                                                    <input id="fapplyppn" type="checkbox" name="fapplyppn"
                                                        value="1" x-model="includePPN" disabled
                                                        class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                    <label for="fapplyppn" class="ml-2 text-sm font-medium text-gray-700">
                                                        <span class="font-bold">PPN</span>
                                                    </label>
                                                </div>

                                                <!-- Dropdown Include / Exclude (tengah) -->
                                                <div class="flex items-center gap-2">
                                                    <select id="fapplyppn_input" name="fapplyppn"
                                                        x-model.number="fapplyppn"
                                                        :disabled="!(includePPN || fapplyppn) || action === 'view' || action === 'delete'"
                                                        class="w-28 h-9 px-2 text-sm leading-tight border rounded transition-opacity appearance-none
                                                           disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed">
                                                        <option value="0">Exclude</option>
                                                        <option value="1">Include</option>
                                                    </select>
                                                </div>

                                                <!-- Input Rate + Nominal (kanan) -->
                                                <div class="flex items-center gap-2">
                                                    <input type="number" min="0" max="100" id="fppnpersen" name="fppnpersen"
                                                        step="0.01" x-model.number="ppnRate"
                                                        :disabled="!(includePPN || fapplyppn) || action === 'view' || action === 'delete'"
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

                                        <div class="px-5 py-4 space-y-2">
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
                                        <h3 class="text-lg font-semibold text-gray-800">Tidak Ada Item</h3>
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
                                            class="h-9 px-4 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                                            OK
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @php
                            $canApproval = in_array(
                                'approvalpr',
                                explode(',', session('user_restricted_permissions', '')),
                            );
                        @endphp

                        {{-- APPROVAL & ACTIONS --}}
                        <div class="md:col-span-2 flex justify-center items-center space-x-2 mt-6">
                            @if ($canApproval)
                                <label class="block text-sm font-medium">Approval</label>

                                {{-- fallback 0 saat checkbox tidak dicentang --}}
                                <input type="hidden" name="fapproval" value="0">

                                <label class="switch">
                                    <input type="checkbox" name="fapproval" id="approvalToggle" value="1" disabled
                                        {{ old('fapproval', session('fapproval') ? 1 : 0) ? 'checked' : '' }}>
                                    <span class="slider"></span>
                                </label>
                            @endif
                        </div>
                    </fieldset>

                        <div class="mt-6 flex justify-center space-x-4">
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
                            <button type="button" onclick="window.location.href='{{ route('returpenjualan.index') }}'"
                                class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                                <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                                Kembali
                            </button>
                        </div>

                        {{-- ============================================ --}}
                        {{-- MODE EDIT: FORM EDITABLE                    --}}
                        {{-- ============================================ --}}
                    @else
                        <form action="{{ route('returpenjualan.update', $returpenjualan->ftranmtid) }}" method="POST"
                            class="mt-6" x-data="{ showNoItems: false }"
                            @submit.prevent="
                        const n = Number(document.getElementById('itemsCount')?.value || 0);
                        if (n < 1) { showNoItems = true } else { $el.submit() }
                    ">
                            @csrf
                            @method('PATCH')

                            {{-- HEADER FORM --}}
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                                <div class="lg:col-span-4">
                                    <label class="block text-sm font-medium">Cabang</label>
                                    <input type="text"
                                        class="w-full border rounded px-3 py-2 bg-gray-200 cursor-not-allowed"
                                        value="{{ $fcabang }}" disabled>
                                    <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                                </div>

                                <div class="lg:col-span-4" x-data="{ autoCode: true }">
                                    <label class="block text-sm font-medium mb-1">SO#</label>
                                    <div class="flex items-center gap-3">
                                        <input type="text" name="fsono" class="w-full border rounded px-3 py-2"
                                            :disabled="autoCode" value="{{ $returpenjualan->fsono }}"
                                            :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'">
                                        <label class="inline-flex items-center select-none">
                                            <input type="checkbox" x-model="autoCode" checked>
                                            <span class="ml-2 text-sm text-gray-700">Auto</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="lg:col-span-4">
                                    <label class="block text-sm font-medium">Retur Pajak#</label>
                                    <input type="text" name="ftaxno"
                                        value="{{ old('ftaxno', $returpenjualan->ftaxno) }}"
                                        class="w-full border rounded px-3 py-2 @error('ftaxno') border-red-500 @enderror">
                                    @error('ftaxno')
                                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="lg:col-span-4">
                                    <label class="block text-sm font-medium">Type</label>
                                    <select name="ftypesales" id="ftypesales" x-model.number="ftypesales"
                                        x-init="ftypesales = {{ old('ftypesales', $returpenjualan->ftypesales) }}"
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
                                        value="{{ old('fsodate', \Carbon\Carbon::parse($returpenjualan->fsodate)->format('Y-m-d')) }}"
                                        class="w-full border rounded px-3 py-2 @error('fsodate') border-red-500 @enderror">
                                    <input type="hidden" id="ftempohr" value="0">
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
                                                    <option value="{{ $customer->fcustomercode }}" {{-- CEK DISINI: Bandingkan dengan data yang tersimpan di DB --}}
                                                        {{ old('fcustno', $returpenjualan->fcustno) == $customer->fcustomercode ? 'selected' : '' }}>
                                                        {{ $customer->fcustomername }} ({{ $customer->fcustomercode }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="absolute inset-0" role="button" aria-label="Browse Customer"
                                                @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))">
                                            </div>
                                        </div>
                                        <input type="hidden" name="fcustno" id="customerCodeHidden"
                                            value="{{ old('fcustno', $returpenjualan->fcustno) }}">
                                        <button type="button"
                                            @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))"
                                            class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                                            title="Browse Customer">
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
                                                    <option value="{{ $salesman->fsalesmancode }}" {{-- CEK DISINI: Bandingkan old input atau data dari database --}}
                                                        {{ old('fsalesman', $returpenjualan->fsalesman) == $salesman->fsalesmancode ? 'selected' : '' }}>
                                                        {{ $salesman->fsalesmanname }} ({{ $salesman->fsalesmancode }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="absolute inset-0" role="button" aria-label="Browse Salesman"
                                                @click="window.dispatchEvent(new CustomEvent('salesman-browse-open'))">
                                            </div>
                                        </div>
                                        <input type="hidden" name="fsalesman" id="salesmanCodeHidden"
                                            value="{{ old('fsalesman', $returpenjualan->fsalesman ?? '0') }}">
                                        <button type="button"
                                            @click="window.dispatchEvent(new CustomEvent('salesman-browse-open'))"
                                            class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                                            title="Browse Salesman">
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
                                        placeholder="Keterangan isi di sini...">{{ old('fket', $returpenjualan->fket) }}</textarea>
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
                                                    <td class="p-2 font-mono" x-text="it.fitemcode"></td>
                                                    <td class="p-2 text-gray-800" x-text="it.fitemname"></td>
                                                    <td class="p-2">
                                                        <template x-if="it.units && it.units.length > 1">
                                                            <select class="w-full border rounded px-2 py-1 text-xs"
                                                                x-model="it.fsatuan">
                                                                <template x-for="u in it.units" :key="u">
                                                                    <option :value="u" x-text="u"
                                                                        :selected="u === it.fsatuan"></option>
                                                                </template>
                                                            </select>
                                                        </template>
                                                        <template x-if="!it.units || it.units.length <= 1">
                                                            <span x-text="it.fsatuan"></span>
                                                        </template>
                                                    </td>
                                                    <td class="p-2 text-blue-600 font-semibold" x-text="it.frefpr || '-'">
                                                    </td>
                                                    <td class="p-2 text-right">
                                                        <input type="number"
                                                            class="w-full border rounded px-2 py-1 text-right"
                                                            type="number"
                                                            x-model.number="it.fqty" @input="
                                                                recalc(it);
                                                                enforceQtyRow(it);
                                                                recalc(it);
                                                            ">
                                                    </td>
                                                    <td class="p-2 text-right">
                                                        <input type="number"
                                                            class="w-full border rounded px-2 py-1 text-right"
                                                            x-model.number="it.fprice" @input="recalc(it)">
                                                    </td>
                                                    <td class="p-2 text-right">
                                                        <input type="text"
                                                            class="w-full border rounded px-2 py-1 text-right"
                                                            x-model="it.fdisc" @input="recalc(it)">
                                                    </td>
                                                    <td class="p-2 text-right font-semibold" x-text="fmt(it.ftotal)"></td>
                                                    <td class="p-2 text-center">
                                                        <button type="button" @click="removeSaved(i)"
                                                            class="px-3 py-1 rounded text-xs bg-red-100 text-red-600 hover:bg-red-200">Hapus</button>

                                                        <!-- Hidden inputs for submission -->
                                                        <input type="hidden" name="fitemcode[]" :value="it.fitemcode">
                                                        <input type="hidden" name="fitemname[]" :value="it.fitemname">
                                                        <input type="hidden" name="fsatuan[]" :value="it.fsatuan">
                                                        <input type="hidden" name="frefcode[]" :value="it.frefcode">
                                                        <input type="hidden" name="fnouref[]" :value="it.fnouref">
                                                        <input type="hidden" name="frefpr[]" :value="it.frefpr">
                                                        <input type="hidden" name="frefso[]" :value="it.frefso">
                                                        <input type="hidden" name="frefsoid[]" :value="it.frefsoid">
                                                        <input type="hidden" name="frefsrj[]" :value="it.frefsrj">
                                                        <input type="hidden" name="frefsrjid[]" :value="it.frefsrjid">
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

                                                <tr class="border-b transition-colors hover:bg-gray-50">
                                                    <td class="p-0"></td>
                                                    <td class="p-0"></td>
                                                    <td class="p-2" colspan="2">
                                                        <textarea x-model="it.fdesc" rows="3" class="w-full border rounded px-2 py-1 text-xs"
                                                            placeholder="Deskripsi item (opsional)"></textarea>
                                                    </td>
                                                    <td class="p-1" colspan="6"></td>
                                                </tr>
                                            </template>

                                            <!-- ROW DRAFT UTAMA -->
                                            <tr class="border-t align-top">
                                                <td class="p-2" x-text="savedItems.length + 1"></td>
                                                <td class="p-2">
                                                    <div class="flex">
                                                        <input type="text"
                                                            class="flex-1 border rounded-l px-2 py-1 font-mono"
                                                            x-ref="draftCode" x-model.trim="draft.fitemcode"
                                                            @input="onCodeTypedRow(draft)"
                                                            @keydown.enter.prevent="handleEnterOnCode('draft')">
                                                        <button type="button" @click="openBrowseFor('draft')"
                                                            class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50"
                                                            title="Cari Produk">
                                                            <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                </td>
                                                <td class="p-2">
                                                    <input type="text"
                                                        class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                        :value="draft.fitemname" disabled>
                                                </td>
                                                <td class="p-2">
                                                    <template x-if="draft.units.length > 1">
                                                        <select id="draftUnitSelect" class="w-full border rounded px-2 py-1"
                                                            x-model="draft.fsatuan"
                                                            @keydown.enter.prevent="$refs.draftRefPr?.focus()">
                                                            <template x-for="u in draft.units" :key="u">
                                                                <option :value="u" x-text="u"></option>
                                                            </template>
                                                        </select>
                                                    </template>
                                                    <template x-if="draft.units.length <= 1">
                                                        <input type="text"
                                                            class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                            :value="draft.fsatuan || '-'" disabled>
                                                    </template>
                                                </td>
                                                <td class="p-2">
                                                    <input type="text"
                                                        class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                        :value="draft.frefpr || '-'" disabled placeholder="Ref No">
                                                </td>
                                                <td class="p-2 text-right">
                                                    <input type="number" class="border rounded px-2 py-1 w-24 text-right"
                                                        min="0" step="1" x-ref="draftQty"
                                                        x-model.number="draft.fqty" @input="
                                                            recalc(draft);
                                                            enforceQtyRow(draft);
                                                            recalc(draft);
                                                        "
                                                        @keydown.enter.prevent="$refs.draftTerima?.focus()">
                                                    <div class="text-xs text-gray-400 mt-0.5 text-right">
                                                        <span x-show="draft.fitemcode" x-html="formatStockLimit(draft)"></span>
                                                    </div>
                                                </td>
                                                <td class="p-2 text-right">
                                                    <input type="number" class="border rounded px-2 py-1 w-28 text-right"
                                                        min="0" step="0.01" x-ref="draftPrice"
                                                        x-model.number="draft.fprice" @input="recalc(draft)"
                                                        @keydown.enter.prevent="$refs.draftDisc?.focus()">
                                                </td>
                                                <td class="p-2 text-right">
                                                    <input type="text" class="border rounded px-2 py-1 w-24 text-right"
                                                        x-ref="draftDisc" x-model="draft.fdisc" @input="recalc(draft)"
                                                        @keydown.enter.prevent="addIfComplete()" placeholder="10+2">
                                                </td>
                                                <td class="p-2 text-right" x-text="fmt(draft.ftotal)"></td>
                                                <td class="p-2 text-center">
                                                    <div class="flex items-center justify-center gap-2 flex-wrap">
                                                        <button type="button" @click="addIfComplete()"
                                                            class="px-3 py-1 rounded text-xs bg-emerald-600 text-white">Tambah</button>
                                                    </div>
                                                </td>
                                            </tr>

                                            <!-- ROW DRAFT DESC -->
                                            <tr class="border-b">
                                                <td class="p-0"></td>
                                                <td class="p-0"></td>
                                                <td class="p-2" colspan="2">
                                                    <textarea x-model="draft.fdesc" rows="3" class="w-full border rounded px-2 py-1 text-xs"
                                                        placeholder="Deskripsi item (opsional)"></textarea>
                                                </td>
                                                <td class="p-0" colspan="6"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <input type="hidden" id="itemsCount" :value="savedItems.length">
                                <input type="hidden" name="frefcode_global" id="frefcode_global"
                                    value="{{ old('frefcode_global', $returpenjualan->frefcode) }}">
                                <input type="hidden" name="frefso" id="frefso"
                                    value="{{ old('frefso', $returpenjualan->frefso) }}">
                                <input type="hidden" name="frefsrj" id="frefsrj"
                                    value="{{ old('frefsrj', $returpenjualan->frefsrj) }}">

                                <script>
                                    document.addEventListener('DOMContentLoaded', () => {
                                        const inputRefCode = document.getElementById('frefcode');
                                        const inputRefSo = document.getElementById('frefso');
                                        const inputRefSrj = document.getElementById('frefsrj');

                                        // Menangkap Event saat SO dipilih
                                        window.addEventListener('pr-picked', (e) => {
                                            const header = e.detail.header;
                                            inputRefCode.value = 'SO';
                                            inputRefSo.value = header.ftrsomtid; // Sesuaikan ID header SO
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
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="1.5" d="M12 4.5v15m7.5-7.5h-15" />
                                                </svg>
                                                Add SRJ
                                            </button>

                                            {{-- MODAL SRJ --}}
                                            <div x-show="showSrjModal" x-cloak x-transition.opacity
                                                class="fixed inset-0 z-50 flex items-center justify-center p-4">
                                                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                                                    @click="closeSrjModal()">
                                                </div>

                                                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-5xl flex flex-col overflow-hidden"
                                                    style="height: 650px;">
                                                    <div
                                                        class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-indigo-50 to-white">
                                                        <div>
                                                            <h3 class="text-xl font-bold text-gray-800">Browse Surat Jalan
                                                            </h3>
                                                        </div>
                                                        <button type="button" @click="closeSrjModal()"
                                                            class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-bold text-gray-700 text-sm">
                                                            Tutup
                                                        </button>
                                                    </div>

                                                    <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                                                        <div id="srjTableControls"></div>
                                                    </div>

                                                    <div class="flex-1 overflow-y-auto px-6" style="min-height: 0;">
                                                        <div class="bg-white">
                                                            <table id="srjTable"
                                                                class="min-w-full text-sm display nowrap stripe hover"
                                                                style="width:100%">
                                                                <thead class="sticky top-0 z-10">
                                                                    <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                                                        <th
                                                                            class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                            No. Transaksi</th>
                                                                        <th
                                                                            class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                            No. Ref PO</th>
                                                                        <th
                                                                            class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                            Tanggal</th>
                                                                        <th
                                                                            class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                            Custoomer</th>
                                                                        <th
                                                                            class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                            Aksi</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody></tbody>
                                                            </table>
                                                        </div>
                                                    </div>

                                                    <div
                                                        class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
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
                                                        <h3 class="text-lg font-semibold text-gray-800">Item Duplikat SRJ
                                                        </h3>
                                                    </div>
                                                    <div class="px-5 py-4">
                                                        <p class="text-sm text-gray-700 mb-3">
                                                            Ditemukan <span x-text="dupCount" class="font-bold"></span>
                                                            item sudah
                                                            ada
                                                            di
                                                            daftar.
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
                                                            class="px-4 py-2 border rounded-lg">Batal</button>
                                                        <button type="button" @click="confirmAddUniques()"
                                                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg">Tambahkan
                                                            Sisa
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
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="1.5" d="M12 4.5v15m7.5-7.5h-15" />
                                                        </svg>
                                                        Add SO
                                                    </button>
                                                </div>
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
                                                            <p class="text-sm text-gray-500 mt-0.5">Pilih Purchase Order
                                                                yang
                                                                diinginkan
                                                            </p>
                                                        </div>
                                                        <button type="button" @click="closeModal()"
                                                            class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-bold text-gray-700 text-sm">
                                                            Tutup
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
                                                                            SO No</th>
                                                                        <th
                                                                            class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                            No Ref</th>
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
                                                    <div
                                                        class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
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
                                                                    <li
                                                                        class="px-3 py-2 text-sm text-gray-500 text-center">
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
                                                            Batal
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ===== Panel Totals (DESAIN ASLI dipertahankan, hanya wrapper yang diperbaiki) ===== -->
                                    <div class="w-full md:w-auto md:min-w-[550px] lg:min-w-[650px]">
                                        <div class="rounded-lg border bg-gray-50 p-3 space-y-2">
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm text-gray-700">Total Harga (Net)</span>
                                                <span class="min-w-[140px] text-right font-medium"
                                                    x-text="rupiah(netTotal)"></span>
                                            </div>
                                            <div class="flex items-center justify-between gap-6">
                                                <!-- Checkbox -->
                                                <div class="flex items-center">
                                                    <input id="fincludeppn_input" type="checkbox" name="fincludeppn"
                                                        value="1" x-model="includePPN"
                                                        :disabled="action === 'delete' || action === 'view'"
                                                        class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                    <label for="fincludeppn_input" class="ml-2 text-sm font-medium text-gray-700">
                                                        <span class="font-bold">PPN</span>
                                                    </label>
                                                </div>

                                                <!-- Dropdown Include / Exclude (tengah) -->
                                                <div class="flex items-center gap-2">
                                                    <select id="fapplyppn_input" name="fapplyppn" x-model.number="fapplyppn"
                                                        :disabled="!(includePPN || fapplyppn) || action === 'delete' || action === 'view'"
                                                        class="w-28 h-9 px-2 text-sm leading-tight border rounded transition-opacity appearance-none
                               disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed">
                                                        <option value="0">Exclude</option>
                                                        <option value="1">Include</option>
                                                    </select>
                                                </div>

                                                <!-- Input Rate + Nominal (kanan) -->
                                                <div class="flex items-center gap-2">
                                                    <input type="number" min="0" max="100" step="0.01"
                                                        x-model.number="ppnRate" :disabled="!(includePPN || fapplyppn) || action === 'delete' || action === 'view'"
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
                                        <input type="hidden" name="famountsonet" :value="netTotal">
                                        <input type="hidden" name="famountpajak" :value="ppnAmount">
                                        <input type="hidden" name="famountso" :value="grandTotal">
                                        <input type="hidden" name="famountpopajak" :value="ppnAmount">
                                        <input type="hidden" name="fppnpersen" :value="ppnRate">

                                        <!-- Modal backdrop -->
                                        <div x-show="show" x-transition.opacity class="fixed inset-0 z-40 bg-black/50"
                                            @keydown.escape.window="closeModal()"></div>

                                        <div>
                                            {{-- MODAL PR --}}
                                            <div x-show="show" x-cloak x-transition.opacity
                                                class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-8"
                                                aria-modal="true" role="dialog">

                                                <div class="relative w-full max-w-5xl rounded-xl bg-white shadow-2xl flex flex-col"
                                                    style="height: 600px;">
                                                    <!-- Header -->
                                                    <div
                                                        class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
                                                        <h3 class="text-xl font-bold text-gray-800">Pilih Purchase Request
                                                            (PR)
                                                        </h3>
                                                        <button type="button" @click="closeModal()"
                                                            class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                                                            Tutup
                                                        </button>
                                                    </div>

                                                    <!-- Table Container -->
                                                    <div class="flex-1 overflow-y-auto p-6" style="min-height: 0;">
                                                        <table id="prTable"
                                                            class="min-w-full text-sm display nowrap stripe hover"
                                                            style="width:100%">
                                                            <thead class="sticky top-0 z-10">
                                                                <tr class="bg-gray-50 border-b-2 border-gray-200">
                                                                    <th class="p-3 text-left font-semibold text-gray-700">
                                                                        PR No
                                                                    </th>
                                                                    <th class="p-3 text-left font-semibold text-gray-700">
                                                                        Customer
                                                                    </th>
                                                                    <th class="p-3 text-left font-semibold text-gray-700">
                                                                        Tanggal
                                                                    </th>
                                                                    <th
                                                                        class="p-3 text-center font-semibold text-gray-700">
                                                                        Aksi
                                                                    </th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <!-- DataTables data here -->
                                                            </tbody>
                                                        </table>
                                                    </div>

                                                    <!-- Footer -->
                                                    <div
                                                        class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
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
                                                        Ditemukan <strong x-text="dupCount"></strong> item yang sudah ada
                                                        dalam
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
                                                            Batal
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

                                <!-- MODAL DESC -->
                                <div x-show="showDescModal" x-cloak
                                    class="fixed inset-0 z-[95] flex items-center justify-center" x-transition.opacity>
                                    <div class="absolute inset-0 bg-black/50" @click="closeDesc()"></div>

                                    <div class="relative bg-white w-[92vw] max-w-lg rounded-2xl shadow-2xl overflow-hidden"
                                        x-transition.scale>
                                        <div class="px-5 py-4 border-b flex items-center">
                                            <x-heroicon-o-document-text class="w-6 h-6 text-blue-600 mr-2" />
                                            <h3 class="text-lg font-semibold text-gray-800">Isi Deskripsi Item</h3>
                                        </div>

                                        <div class="px-5 py-4 space-y-2">
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

                            {{-- MODAL ERROR --}}
                            <div x-show="showNoItems && savedItems.length === 0" x-cloak
                                class="fixed inset-0 z-[90] flex items-center justify-center" x-transition.opacity>
                                <div class="absolute inset-0 bg-black/50" @click="showNoItems=false"></div>

                                <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden"
                                    x-transition.scale>
                                    <div class="px-5 py-4 border-b flex items-center">
                                        <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-500 mr-2" />
                                        <h3 class="text-lg font-semibold text-gray-800">Tidak Ada Item</h3>
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
                                            <h3 class="text-xl font-bold text-gray-800">Browse Customer</h3>
                                            <p class="text-sm text-gray-500 mt-0.5">Pilih customer yang diinginkan</p>
                                        </div>
                                        <button type="button" @click="close()"
                                            class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                                            Tutup
                                        </button>
                                    </div>

                                    <!-- Search & Length Menu -->
                                    <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                                        <div id="supplierTableControls"></div>
                                    </div>

                                    <!-- Table with fixed height and scroll -->
                                    <div class="flex-1 overflow-y-auto px-6" style="min-height: 0;">
                                        <div class="bg-white">
                                            <table id="customerBrowseTable"
                                                class="min-w-full text-sm display nowrap stripe hover"
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
                                            <h3 class="text-xl font-bold text-gray-800">Browse Salesman</h3>
                                            <p class="text-sm text-gray-500 mt-0.5">Pilih salesman yang diinginkan</p>
                                        </div>
                                        <button type="button" @click="close()"
                                            class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                                            Tutup
                                        </button>
                                    </div>

                                    <!-- Search & Length Menu -->
                                    <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                                        <div id="salesmanTableControls"></div>
                                    </div>

                                    <!-- Table with fixed height and scroll -->
                                    <div class="flex-1 overflow-y-auto px-6" style="min-height: 0;">
                                        <div class="bg-white">
                                            <table id="salesmanBrowseTable"
                                                class="min-w-full text-sm display nowrap stripe hover"
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
                                            <h3 class="text-xl font-bold text-gray-800">Browse Produk</h3>
                                            <p class="text-sm text-gray-500 mt-0.5">Pilih produk yang diinginkan</p>
                                        </div>
                                        <button type="button" @click="close()"
                                            class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                                            Tutup
                                        </button>
                                    </div>

                                    <!-- Search & Length Menu -->
                                    <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                                        <div id="productTableControls"></div>
                                    </div>

                                    <!-- Table with fixed height and scroll -->
                                    <div class="flex-1 overflow-y-auto px-6" style="min-height: 0;">
                                        <div class="bg-white">
                                            <table id="productTable"
                                                class="min-w-full text-sm display nowrap stripe hover"
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

                            @php
                                $canApproval = in_array(
                                    'approvalpr',
                                    explode(',', session('user_restricted_permissions', '')),
                                );
                            @endphp

                            {{-- APPROVAL & ACTIONS --}}
                            <div class="md:col-span-2 flex justify-center items-center space-x-2 mt-6">
                                @if ($canApproval)
                                    <label class="block text-sm font-medium">Approval</label>

                                    {{-- fallback 0 saat checkbox tidak dicentang --}}
                                    <input type="hidden" name="fapproval" value="0">

                                    <label class="switch">
                                        <input type="checkbox" name="fapproval" id="approvalToggle" value="1"
                                            {{ old('fapproval', session('fapproval') ? 1 : 0) ? 'checked' : '' }}>
                                        <span class="slider"></span>
                                    </label>
                                @endif
                            </div>

                            <div class="mt-8 flex justify-center gap-4">
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
                                <button type="button" @click="window.location.href='{{ route('returpenjualan.index') }}'"
                                    class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" /> Keluar
                                </button>
                            </div>
                        </form>
                @endif
            </div>
        </div>
    </div>
    {{-- ============================================ --}}
    {{-- MODAL & TOAST (HANYA UNTUK MODE DELETE)     --}}
    {{-- ============================================ --}}
    @if ($action === 'delete')
        {{-- Modal Delete --}}
        <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Konfirmasi Hapus returpenjualan ini?</h3>
                <form id="deleteForm" action="{{ route('returpenjualan.destroy', $returpenjualan->ftranmtid) }}"
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
            // Tampilkan Modal
            function showDeleteModal() {
                document.getElementById('deleteModal').classList.remove('hidden');
            }

            // Tutup Modal
            function closeDeleteModal() {
                document.getElementById('deleteModal').classList.add('hidden');
            }

            // Tutup Toast
            function closeToast() {
                document.getElementById('toast').classList.add('hidden');
            }

            // Tampilkan Toast
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

            // Konfirmasi Delete
            function confirmDelete() {
                const btnYa = document.getElementById('btnYa');
                const btnTidak = document.getElementById('btnTidak');

                // Disable buttons
                btnYa.disabled = true;
                btnTidak.disabled = true;
                btnYa.textContent = 'Menghapus...';

                // Kirim request delete
                fetch('{{ route('returpenjualan.destroy', $returpenjualan->ftranmtid) }}', {
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

                        // Redirect ke index setelah 0.5 detik
                        setTimeout(() => {
                            window.location.href = '{{ route('returpenjualan.index') }}';
                        }, 500);
                    })
                    .catch(error => {
                        btnYa.disabled = false;
                        btnTidak.disabled = false;
                        btnYa.textContent = 'Ya, Hapus';
                        showToast('Terjadi kesalahan saat menghapus data', false);
                    });
            }
        </script>
    @endif
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
<script>
    // Fallback toast system if not defined
    if (!window.toast) {
        window.toast = {
            success: (msg) => {
                if (typeof Swal !== 'undefined') Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: msg, showConfirmButton: false, timer: 3000 });
                else console.log('Success:', msg);
            },
            error: (msg) => {
                if (typeof Swal !== 'undefined') Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: msg, showConfirmButton: false, timer: 3000 });
                else console.error('Error:', msg);
            },
            info: (msg) => {
                if (typeof Swal !== 'undefined') Swal.fire({ toast: true, position: 'top-end', icon: 'info', title: msg, showConfirmButton: false, timer: 3000 });
                else console.info('Info:', msg);
            }
        };
    }

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
                if (!opt) {
                    opt = new Option(`${customer.fcustomername} (${customer.fcustomercode})`, customer.fcustomercode,
                        true, true);
                    sel.add(opt);
                } else {
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
            savedItems: @json($savedItems ?? []),
            draft: newRow(),
            action: @js($action ?? 'edit'),

            totalHarga: 0,
            ppnRate: @json($returpenjualan->fppnpersen ?? 11),

            includePPN: @json($returpenjualan->fincludeppn == '1'),
            fapplyppn: @json((int)($returpenjualan->fapplyppn ?? 0)),

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

                if (Number.isInteger(v)) {
                    return v.toLocaleString('id-ID');
                } else {
                    return v.toLocaleString('id-ID', {
                        style: 'currency',
                        currency: 'IDR'
                    });
                }
            },

            rupiah(n) {
                const v = Number(n || 0);
                if (!isFinite(v)) return 'Rp -';
                return 'Rp ' + v.toLocaleString('id-ID', {
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
                row.fqty = Math.max(1, +row.fqty || 1);
                row.fterima = Math.max(0, +row.fterima || 0);
                row.fprice = Math.max(0, +row.fprice || 0);

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

            init() {
                this.recalcTotals();
            },

            showToast(message, type = 'success') {
                if (window.toast) {
                    if (type === 'error' || type === 'danger') window.toast.error(message);
                    else if (type === 'warning') window.toast.info(message);
                    else window.toast.success(message);
                } else if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: type === 'danger' ? 'error' : type,
                        title: message,
                        showConfirmButton: false,
                        timer: 3000
                    });
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
                        unit_ratios: { satuankecil: 1, satuanbesar: 1, satuanbesar2: 1 }
                    };
                }
                if (!meta.unit_ratios) {
                    meta.unit_ratios = { satuankecil: 1, satuanbesar: 1, satuanbesar2: 1 };
                }
                return meta;
            },

            formatStockLimit(code, qty, satuan) {
                // On Retur Penjualan Edit: do not show/compute stock/max qty limit.
                // Qty limiting is handled only by fqtyremain validation (if present on the row).
                return '';
            },

            enforceQtyRow(row) {
                const n = +row.fqty;

                if (!Number.isFinite(n)) {
                    row.fqty = 1;
                    return;
                }
                if (n < 1) row.fqty = 1;

                // Keep only fqtyremain validation on edit.
                const remain = Number(row.fqtyremain ?? 0);
                if (Number.isFinite(remain) && remain > 0 && n > remain) {
                    row.fqty = remain;
                }
            },

            hydrateRowFromMeta(row, meta) {
                if (!meta) {
                    row.fitemname = '';
                    row.units = [];
                    row.fsatuan = '';
                    row.maxqty = 0;
                    if (row === this.draft) {
                        clearDraftUnitSelect();
                    }
                    return;
                }
                row.fitemname = meta.name || '';
                const units = [...new Set((meta.units || []).map(u => (u ?? '').toString().trim()).filter(Boolean))];
                row.units = units;
                if (!units.includes(row.fsatuan)) row.fsatuan = units[0] || '';
                row.fsatuan = row.fsatuan;
                if (meta.unit_ratios) row.unit_ratios = meta.unit_ratios;
                const keepRefLimit = Number.isFinite(+row.maxqty) && +row.maxqty > 0 &&
                    (Number(row.frefsoid) > 0 || Number(row.frefsrjid) > 0);
                row.maxqty = keepRefLimit ? +row.maxqty : 0;
                
                if (row === this.draft) {
                    if (units.length > 1) {
                        populateDraftUnitSelect(units);
                    } else {
                        clearDraftUnitSelect();
                    }
                }
            },

            onCodeTypedRow(row) {
                this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode));
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
                return [...new Set(parts)].join(',');
            },

            generateUniqueNoAcak() {
                const used = new Set(this.savedItems.map(item => this.normalizeNoAcak(item.fnoacak)).filter(Boolean));
                let candidate = '';
                do {
                    candidate = Array.from({ length: 3 }, () => '123456789'[Math.floor(Math.random() * 9)]).join('');
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

            resetDraft() {
                this.draft = newRow();
                this.draft.fnoacak = this.generateUniqueNoAcak();
                this.$nextTick(() => this.$refs.draftCode?.focus());
            },

            addManyFromPR(header, items, source) {
                if (!items || !Array.isArray(items)) {
                    window.toast?.error('Data items tidak valid atau kosong.');
                    return;
                }

                const existing = new Set(this.getCurrentItemKeys());
                let added = 0,
                    duplicates = 0,
                    skipped = [];

                items.forEach((src, index) => {
                    const itemcode = (src.fitemcode ?? '').toString().trim();
                    const itemname = (src.fitemname ?? '').toString().trim();
                    const satuan = (src.fsatuan ?? '').toString().trim();

                    if (!itemcode || !itemname || !satuan) {
                        skipped.push({
                            code: itemcode || 'NO_CODE',
                            reason: 'Data tidak lengkap'
                        });
                        return;
                    }

                    const key = this.itemKey({
                        fitemcode: itemcode,
                        frefdtno: src.frefdtno ?? ''
                    });

                    if (existing.has(key)) {
                        duplicates++;
                        return;
                    }
                    existing.add(key);

                    let docNo = '';
                    if (source === 'SO') {
                        docNo = (header?.fsono || '').trim();
                    } else if (source === 'SRJ') {
                        docNo = (header?.fstockmtno || '').trim();
                    }

                    const meta = this.productMeta(itemcode);

                    const row = {
                        uid: cryptoRandom(),
                        fitemcode: itemcode,
                        fitemname: itemname,
                        fsatuan: satuan,
                        frefdtno: src.frefdtno ?? '',
                        fnouref: (src.frefdtno ?? src.fnouref ?? null),
                        frefcode: source,
                        frefpr: docNo,
                        frefso: source === 'SO' ? docNo : (src.frefso || '').trim(),
                        frefsoid: source === 'SO' ? (src.frefdtno ?? null) : null,
                        frefsrj: source === 'SRJ' ? docNo : (src.frefsrj || '').trim(),
                        frefsrjid: source === 'SRJ' ? (src.frefdtno ?? null) : null,
                        fnoacak: this.generateUniqueNoAcak(),
                        frefnoacak: this.normalizeRefNoAcak(src.frefnoacak ?? src.fnoacak ?? ''),

                        fqty: (src.fqty !== null && src.fqty !== undefined && Number(src.fqty) > 0) ? Number(src.fqty) : 1,
                        fprice: Number(src.fprice ?? src.fharga ?? 0),
                        fterima: Number(src.fterima ?? 0),
                        ftotal: 0,
                        fdesc: src.fdesc ? src.fdesc.toString().trim() : '',
                        fketdt: src.fketdt ? src.fketdt.toString().trim() : '',
                        units: meta ? [...new Set((meta.units || []).map(u => (u ?? '').toString().trim())
                            .filter(Boolean))] : [satuan].filter(Boolean),
                        maxqty: Math.max(0, Number(src.fqtyremain ?? src.fqty ?? 0)),
                    };

                    row.ftotal = Number((row.fqty * row.fprice).toFixed(2));

                    this.recalc(row);
                    this.savedItems.push(row);
                    added++;
                });

                this.recalcTotals();

                if (added > 0) {
                    window.toast?.success(`Berhasil menambahkan ${added} item ke detail`);
                }

                if (duplicates > 0) {
                    window.toast?.info(`${duplicates} item diabaikan (sudah ada)`);
                }

                if (skipped.length > 0) {
                    window.toast?.error(`${skipped.length} item diabaikan (data tidak lengkap)`);
                }
            },

            addIfComplete() {
                const r = this.draft;

                if (r.fitemcode === 'UM' && this.ftypesales === 0) {
                    this.showToast('Produk UM hanya untuk tipe Uang Muka!', 'error');
                    return;
                }

                if (Number(r.fqty) <= 0) {
                    this.showToast('Quantity harus lebih besar dari 0!', 'warning');
                    this.$refs.draftQty?.focus();
                    return;
                }

                if (Number(r.fprice) < 0) {
                    this.showToast('Harga tidak boleh minus!', 'warning');
                    this.$refs.draftPrice?.focus();
                    return;
                }

                if (!this.isComplete(r)) {
                    if (!r.fitemcode) return this.$refs.draftCode?.focus();
                    if (!r.fitemname) return this.$refs.draftCode?.focus();
                    if (!r.fsatuan) return (r.units.length > 1 ? this.$refs.draftUnit?.focus() : this.$refs.draftCode
                        ?.focus());
                    if (!(Number(r.fqty) > 0)) return this.$refs.draftQty?.focus();
                    return;
                }

                this.recalc(r);

                const key = this.itemKey({
                    fitemcode: r.fitemcode,
                    frefdtno: r.frefdtno || ''
                });
                const dupe = this.savedItems.find(it => this.itemKey({
                    fitemcode: it.fitemcode,
                    frefdtno: it.frefdtno || ''
                }) === key);

                if (dupe) {
                    this.showToast('Item sama sudah ada di daftar', 'warning');
                    return;
                }

                this.savedItems.push({
                    ...r,
                    fnoacak: this.normalizeNoAcak(r.fnoacak) || this.generateUniqueNoAcak(),
                    frefnoacak: this.normalizeRefNoAcak(r.frefnoacak),
                    uid: cryptoRandom()
                });
                this.showNoItems = false;
                this.resetDraft();
                this.$nextTick(() => this.$refs.draftCode?.focus());
                this.syncDescList?.();
                this.recalcTotals();
            },


            removeSaved(i) {
                this.savedItems.splice(i, 1);
                this.syncDescList?.();
                this.recalcTotals();
            },

            onSubmit($event) {
                if (this.savedItems.length === 0) {
                    $event.preventDefault();
                    this.showNoItems = true;
                    return;
                }
            },

            handleEnterOnCode(where) {
                if (where === 'edit') {
                    if (this.editRow.units.length > 1) this.$refs.editUnit?.focus();
                    else this.$refs.editQty?.focus();
                } else {
                    if (this.draft.units.length > 1) this.$refs.draftUnit?.focus();
                    else this.$refs.draftQty?.focus();
                }
            },

            showDescModal: false,
            descTarget: 'draft',
            descSavedIndex: null,
            descValue: '',
            openDesc() {},
            closeDesc() {},
            applyDesc() {},

            itemKey(it) {
                return `${(it.fitemcode ?? '').toString().trim()}::${(it.frefdtno ?? '').toString().trim()}`;
            },

            getCurrentItemKeys() {
                return this.savedItems.map(it => this.itemKey(it));
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

                this.savedItems.forEach((item) => {
                    item.fnoacak = this.normalizeNoAcak(item.fnoacak) || this.generateUniqueNoAcak();
                    item.frefnoacak = this.normalizeRefNoAcak(item.frefnoacak);
                    const soLimit = Number(item.maxqty ?? item.fqtyremain ?? 0);
                    item.maxqty = (Number(item.frefsoid) > 0 || Number(item.frefsrjid) > 0) && soLimit > 0 ? soLimit : 0;
                });

                window.getCurrentItemKeys = () => this.getCurrentItemKeys();
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
                        row.frefcode = product.fprdid || '';
                        this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode));
                        row.fnoacak = this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak();
                        if (!row.fqty) row.fqty = 1;
                        this.recalc(row);
                    };
                    if (this.browseTarget === 'edit') {
                        apply(this.editRow);
                        this.$nextTick(() => this.$refs.editQty?.focus());
                    } else {
                        apply(this.draft);
                        this.$nextTick(() => this.$refs.draftQty?.focus());
                    }
                }, {
                    passive: true
                });
                
                const self = this;
                document.addEventListener('change', function(e) {
                    if (e.target && e.target.id === 'draftUnitSelect') {
                        self.draft.fsatuan = e.target.value;
                    }
                });

                this.recalcTotals();
            },

            browseTarget: 'draft',
            openBrowseFor(where) {
                this.browseTarget = (where === 'edit' ? 'edit' : 'draft');
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
                frefcode: '',
                fnouref: '',
                frefpr: '',
                frefso: '',
                frefsoid: null,
                frefsrj: '',
                frefsrjid: null,
                fnoacak: '',
                frefnoacak: '',
                fqty: 0,
                fterima: 0,
                fqtyremain: 0,
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

        function clearDraftUnitSelect() {
            const sel = getDraftUnitSelect();
            if (sel) sel.innerHTML = '';
        }
    }
</script>

<script>
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
                        url: "{{ route('salesorder.pickable') }}",
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
                            data: 'fsono', // Nomor SO
                            name: 'trsomt.fsono',
                            className: 'font-mono text-sm'
                        },
                        {
                            data: 'frefno', // Nomor SO
                            name: 'frefno',
                            className: 'font-mono text-sm'
                        },
                        {
                            data: 'fsodate', // Tanggal SO
                            name: 'trsomt.fsodate',
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
                                return '<button type="button" class="btn-pick px-4 py-1.5 rounded-md text-sm font-bold bg-teal-600 hover:bg-teal-700 text-white transition-colors duration-150">Pilih</button>';
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
                const currentKeys = new Set((window.getCurrentItemKeys?.() || []).map(String));
                const keyOf = (src) => `${(src.fitemcode ?? '').toString().trim()}::${(src.frefdtno ?? '').toString().trim()}`;
                
                const safeUniques = this.pendingUniques.filter(src => !currentKeys.has(keyOf(src)));
                
                if (safeUniques.length > 0) {
                    window.dispatchEvent(new CustomEvent('pr-picked', {
                        detail: {
                            header: this.pendingHeader,
                            items: safeUniques
                        }
                    }));
                }
                this.closeDupModal();
                this.closeModal();
            },

            async pick(row) {
                try {
                    if (row.fdiscontinue == '1') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Produk Discontinue',
                            html: `Produk <b>${row.fprdname}</b> sudah tidak diproduksi lagi.<br><br>Penyimpanan Batal.`,
                            confirmButtonColor: '#f59e0b', // Warna orange amber
                            confirmButtonText: 'Kembali'
                        });
                        return; // Hentikan proses, jangan tambahkan ke tabel
                    }

                    const url = `{{ route('salesorder.items', ['id' => 'SO_ID_PLACEHOLDER']) }}`
                        .replace('SO_ID_PLACEHOLDER', row.ftrsomtid);

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
                    window.toast?.error(`Gagal mengambil detail Sales Order: ${e.message}`);
                }
            }
        };
    };

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
                                return '<button type="button" class="btn-pick-srj px-4 py-1.5 rounded-md text-sm font-bold bg-indigo-600 hover:bg-indigo-700 text-white transition-colors duration-150">Pilih</button>';
                            }
                        }
                    ],
                    pageLength: 10,
                    dom: '<"#srjHeader"fl>rt<"#srjFooter"ip>',
                    language: {
                        processing: "Memuat data...",
                        search: "Cari:",
                        lengthMenu: "Tampilkan _MENU_",
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
                const currentKeys = new Set((window.getCurrentItemKeys?.() || []).map(String));
                const keyOf = (src) => `${(src.fitemcode ?? '').toString().trim()}::${(src.frefdtno ?? '').toString().trim()}`;
                
                const safeUniques = this.pendingUniques.filter(src => !currentKeys.has(keyOf(src)));
                
                if (safeUniques.length > 0) {
                    window.dispatchEvent(new CustomEvent('srj-picked', {
                        detail: {
                            header: this.pendingHeader,
                            items: safeUniques
                        }
                    }));
                }
                this.closeDupModal();
                this.closeSrjModal();
            },

            async pick(row) {
                try {
                    if (row.fdiscontinue == '1') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Produk Discontinue',
                            html: `Produk <b>${row.fprdname}</b> sudah tidak diproduksi lagi.<br><br>Penyimpanan Batal.`,
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
                    window.toast?.error(`Gagal mengambil detail SRJ: ${e.message}`);
                }
            }
        };
    };

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
                                order_dir: d.order[0].dir
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
                                return '<button type="button" class="btn-pick px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150">Pilih</button>';
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
                        processing: "Memuat data...", // Diseragamkan
                        search: "Cari:", // Diseragamkan
                        lengthMenu: "Tampilkan _MENU_", // Diseragamkan
                        info: "Menampilkan _START_ - _END_ dari _TOTAL_ data", // Diseragamkan
                        infoEmpty: "Tidak ada data", // Diseragamkan
                        infoFiltered: "(disaring dari _MAX_ total data)", // Diseragamkan
                        zeroRecords: "Tidak ada data yang ditemukan", // Diseragamkan
                        emptyTable: "Tidak ada data tersedia", // Diseragamkan
                        paginate: {
                            first: "Pertama",
                            last: "Terakhir",
                            next: "Selanjutnya",
                            previous: "Sebelumnya"
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
                    // Tampilkan loading indicator (opsional)

                    const url = `{{ route('tr_poh.items', ['id' => 'PR_ID_PLACEHOLDER']) }}`
                        .replace('PR_ID_PLACEHOLDER', row.fprhid);

                    const res = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const json = await res.json();

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
                    console.log('Gagal mengambil detail PR. Lihat konsol untuk detail.');
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
        return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }
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
