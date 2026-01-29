@extends('layouts.app')

@section('title', $action === 'delete' ? 'Hapus Sales Order' : 'Edit Sales Order')

@section('title', 'Sales Order')

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
    </style>

    <div x-data="{ open: true }">
        <div x-data="{ fclose: {{ old('fclose', $salesorder->fclose) == '1' ? 'true' : 'false' }}, includePPN: false, ppnRate: 0, ppnAmount: 0, selected: 'alamatsurat', totalHarga: 100000 }" class="lg:col-span-5">
            <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1600px] w-full mx-auto">
                @if ($action === 'delete')
                    <div class="space-y-4">

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
                                    <input type="text" name="fsono" value="{{ old('fsono', $salesorder->fsono) }}"
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

                            {{-- Tanggal --}}
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium">Tanggal</label>
                                <input disabled type="date" name="fsodate" value="{{ old('fsodate') ?? date('Y-m-d') }}"
                                    class="w-full border rounded px-3 py-2 bg-gray-200 @error('fsodate') border-red-500 @enderror">
                                @error('fsodate')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="lg:col-span-2 flex items-end pb-2">
                                <div class="inline-flex items-center">
                                    <input id="fclose" type="checkbox" name="fclose" value="1" x-model="fclose"
                                        {{-- text-red-600 mengubah isi centang, border-red-400 mengubah bingkai --}}
                                        class="w-6 h-6 text-red-600 border-red-400 rounded cursor-pointer focus:ring-red-500"
                                        {{ old('fclose', $salesorder->fclose) ? 'checked' : '' }}>

                                    <label for="fclose" {{-- text-red-600 mengubah warna tulisan menjadi merah --}}
                                        class="ml-3 text-base font-bold text-red-600 whitespace-nowrap cursor-pointer">
                                        Close
                                    </label>
                                </div>
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
                                                <option value="{{ $customer->fcustomerid }}" {{-- CEK DISINI: Bandingkan dengan data yang tersimpan di DB --}}
                                                    {{ old('fcustno', $salesorder->fcustno) == $customer->fcustomerid ? 'selected' : '' }}>
                                                    {{ $customer->fcustomername }} ({{ $customer->fcustomerid }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="absolute inset-0" role="button" aria-label="Browse Customer"
                                            @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))">
                                        </div>
                                    </div>
                                    <input type="hidden" name="fcustno" id="customerCodeHidden"
                                        value="{{ old('fcustno', $salesorder->fcustno) }}">
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
                                                <option value="{{ $salesman->fsalesmanid }}" {{-- CEK DISINI: Bandingkan old input atau data dari database --}}
                                                    {{ old('fsalesman', $salesorder->fsalesman) == $salesman->fsalesmanid ? 'selected' : '' }}>
                                                    {{ $salesman->fsalesmanname }} ({{ $salesman->fsalesmanid }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="absolute inset-0" role="button" aria-label="Browse Salesman"
                                            @click="window.dispatchEvent(new CustomEvent('salesman-browse-open'))">
                                        </div>
                                    </div>
                                    <input type="hidden" name="fsalesman" id="salesmanCodeHidden"
                                        value="{{ old('fsalesman', $salesorder->fsalesman) }}">
                                </div>
                                @error('fsalesman')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="lg:col-span-4">
                                <label class="block text-sm font-medium mb-1">Tempo</label>
                                <div class="flex items-center">
                                    {{-- Gunakan trim() untuk membuang spasi di belakang angka --}}
                                    <input type="number" id="ftempohr" name="ftempohr" disabled
                                        value="{{ trim(old('ftempohr', $salesorder->ftempohr ?? 0)) }}"
                                        class="w-full border rounded px-3 py-2 bg-gray-200">
                                    <span class="ml-2">Hari</span>
                                </div>
                            </div>
                            <div class="col-span-12 mt-4">
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">

                                    <div x-data="{
                                        tab: 1,
                                        addr1: {{ json_encode(old('fkirimaddress1', $salesorder->customer->fkirimaddress1 ?? '')) }},
                                        addr2: {{ json_encode(old('fkirimaddress2', $salesorder->customer->fkirimaddress2 ?? '')) }},
                                        addr3: {{ json_encode(old('fkirimaddress3', $salesorder->customer->fkirimaddress3 ?? '')) }},
                                    
                                        updateFinal() {
                                            let val = '';
                                            if (this.tab === 1) val = this.addr1;
                                            else if (this.tab === 2) val = this.addr2;
                                            else if (this.tab === 3) val = this.addr3;
                                    
                                            const el = document.getElementById('falamatkirim_final');
                                            if (el) el.value = val;
                                        }
                                    }" x-init="const savedAddr = {{ json_encode(trim($salesorder->falamatkirim ?? '')) }};
                                    if (savedAddr && savedAddr === addr2) { tab = 2; } else if (savedAddr && savedAddr === addr3) { tab = 3; } else { tab = 1; if (savedAddr) addr1 = savedAddr; }
                                    updateFinal();
                                    $watch('tab', v => updateFinal());
                                    $watch('addr1', v => updateFinal());
                                    $watch('addr2', v => updateFinal());
                                    $watch('addr3', v => updateFinal());"
                                        @customer-selected.window="
                                addr1 = $event.detail.f1 || ''; 
                                addr2 = $event.detail.f2 || ''; 
                                addr3 = $event.detail.f3 || ''; 
                                tab = 1; 
                                updateFinal();
                                "
                                        class="flex flex-col gap-2">

                                        <input type="hidden" name="falamatkirim" id="falamatkirim_final"
                                            value="{{ old('falamatkirim') }}">

                                        <div class="flex items-center gap-2">
                                            <label class="text-sm font-bold text-gray-700 mr-2">Kirim ke :</label>

                                            <div class="inline-flex rounded-md shadow-sm" role="group">
                                                <button type="button" @click="tab = 1" disabled
                                                    :class="tab === 1 ? 'bg-blue-600 text-white z-10 ring-2 ring-blue-300' :
                                                        'bg-white text-gray-700 hover:bg-gray-50'"
                                                    class="px-4 py-1.5 text-xs font-semibold border border-gray-300 rounded-l-md transition-all">
                                                    Alamat 1
                                                </button>
                                                <button type="button" @click="tab = 2" disabled
                                                    :class="tab === 2 ? 'bg-blue-600 text-white z-10 ring-2 ring-blue-300' :
                                                        'bg-white text-gray-700 hover:bg-gray-50'"
                                                    class="px-4 py-1.5 text-xs font-semibold border-t border-b border-r border-gray-300 transition-all">
                                                    Alamat 2
                                                </button>
                                                <button type="button" @click="tab = 3" disabled
                                                    :class="tab === 3 ? 'bg-blue-600 text-white z-10 ring-2 ring-blue-300' :
                                                        'bg-white text-gray-700 hover:bg-gray-50'"
                                                    class="px-4 py-1.5 text-xs font-semibold border-t border-b border-r border-gray-300 rounded-r-md transition-all">
                                                    Alamat 3
                                                </button>
                                            </div>
                                        </div>

                                        <div class="w-full">
                                            <textarea x-show="tab === 1" x-model="addr1" readonly
                                                class="w-full p-2 text-sm border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 min-h-[80px]"
                                                placeholder="Isi Alamat 1..."></textarea>

                                            <textarea x-show="tab === 2" x-model="addr2" readonly
                                                class="w-full p-2 text-sm border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 min-h-[80px]"
                                                placeholder="Isi Alamat 2..."></textarea>

                                            <textarea x-show="tab === 3" x-model="addr3" readonly
                                                class="w-full p-2 text-sm border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 min-h-[80px]"
                                                placeholder="Isi Alamat 3..."></textarea>
                                        </div>
                                    </div>

                                    <div class="flex flex-col">
                                        <label class="block text-sm font-bold text-gray-700 mb-2">Keterangan</label>
                                        <div
                                            class="flex-1 border-2 border-gray-200 rounded-xl p-3 bg-gray-200 focus-within:border-blue-400">
                                            <textarea readonly name="fket" class="w-full h-full border-none focus:ring-0 p-0 text-sm resize-none bg-gray-200"
                                                placeholder="Keterangan isi di sini...">{{ old('fket', $salesorder->fket) }}</textarea>
                                        </div>
                                        @error('fket')
                                            <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="lg:col-span-12">
                                <label class="block text-sm font-medium">Catatan Internal</label>
                                <textarea readonly name="fketinternal" rows="3"
                                    class="w-full border rounded px-3 py-2 bg-gray-200 @error('fketinternal') border-red-500 @enderror"
                                    placeholder="Tulis Catatan Internal tambahan di sini...">{{ old('fketinternal', $salesorder->fketinternal) }}</textarea>
                                @error('fketinternal')
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
                                            <th class="p-2 text-right w-36 whitespace-nowrap">Qty</th>
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
                                                <td class="p-2 text-right" x-text="fmt(it.fqty)"></td>
                                                <td class="p-2 text-right" x-text="fmt(it.fprice)"></td>
                                                <td class="p-2 text-right" x-text="it.fdisc"></td>
                                                <td class="p-2 text-right" x-text="fmt(it.ftotal)"></td>
                                            </tr>

                                            <!-- Hidden inputs row -->
                                            <tr class="hidden">
                                                <td colspan="9">
                                                    <input type="hidden" name="fitemcode[]" :value="it.fitemcode">
                                                    <input type="hidden" name="fitemname[]" :value="it.fitemname">
                                                    <input type="hidden" name="fsatuan[]" :value="it.fsatuan">
                                                    <input type="hidden" name="frefdtno[]" :value="it.frefdtno">
                                                    <input type="hidden" name="fnouref[]" :value="it.fnouref">
                                                    <input type="hidden" name="frefpr[]" :value="it.frefpr">
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
                                            <input type="text"
                                                class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                :value="editRow.fitemname" disabled>
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

                                        <!-- Qty -->
                                        <td class="p-2 text-right">
                                            <input type="number" class="border rounded px-2 py-1 w-24 text-right"
                                                min="0" step="1" x-ref="editQty"
                                                x-model.number="editRow.fqty" @input="recalc(editRow)"
                                                @keydown.enter.prevent="$refs.editTerima?.focus()">
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
                                                    <select disabled id="includePPN" name="includePPN"
                                                        x-model.number="fapplyppn" x-init="fapplyppn = 0"
                                                        :disabled="!(includePPN || fapplyppn)"
                                                        class="w-28 h-9 px-2 text-sm leading-tight border rounded transition-opacity appearance-none
                                                           disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed">
                                                        <option value="0">Exclude</option>
                                                        <option value="1">Include</option>
                                                    </select>
                                                </div>

                                                <!-- Input Rate + Nominal (kanan) -->
                                                <div class="flex items-center gap-2">
                                                    <input disabled type="number" min="0" max="100"
                                                        step="0.01" x-model.number="ppnRate"
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
                                            disabled {{ old('fapproval', session('fapproval') ? 1 : 0) ? 'checked' : '' }}>
                                        <span class="slider"></span>
                                    </label>
                                @endif
                            </div>

                            <div class="mt-6 flex justify-center space-x-4">
                                <button type="button" onclick="showDeleteModal()"
                                    class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 flex items-center">
                                    <x-heroicon-o-trash class="w-5 h-5 mr-2" />
                                    Hapus
                                </button>
                                <button type="button" onclick="window.location.href='{{ route('salesorder.index') }}'"
                                    class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                                    Kembali
                                </button>
                            </div>

                            {{-- ============================================ --}}
                            {{-- MODE EDIT: FORM EDITABLE                    --}}
                            {{-- ============================================ --}}
                        @else
                            <form action="{{ route('salesorder.update', $salesorder->ftrsomtid) }}" method="POST"
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

                                    {{-- SO# --}}
                                    <div class="lg:col-span-4" x-data="{ autoCode: false }">
                                        <label class="block text-sm font-medium mb-1">SO#</label>
                                        <div class="flex items-center gap-3">
                                            <input type="text" name="fsono"
                                                value="{{ old('fsono', $salesorder->fsono) }}"
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

                                    {{-- Tanggal --}}
                                    <div class="lg:col-span-2">
                                        <label class="block text-sm font-medium">Tanggal</label>
                                        <input type="date" name="fsodate"
                                            value="{{ old('fsodate') ?? date('Y-m-d') }}"
                                            class="w-full border rounded px-3 py-2 @error('fsodate') border-red-500 @enderror">
                                        @error('fsodate')
                                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="lg:col-span-2 flex items-end pb-2">
                                        <div class="inline-flex items-center">
                                            <input id="fclose" type="checkbox" name="fclose" value="1"
                                                x-model="fclose" {{-- text-red-600 mengubah isi centang, border-red-400 mengubah bingkai --}}
                                                class="w-6 h-6 text-red-600 border-red-400 rounded cursor-pointer focus:ring-red-500"
                                                {{ old('fclose', $salesorder->fclose) ? 'checked' : '' }}>

                                            <label for="fclose" {{-- text-red-600 mengubah warna tulisan menjadi merah --}}
                                                class="ml-3 text-base font-bold text-red-600 whitespace-nowrap cursor-pointer">
                                                Close
                                            </label>
                                        </div>
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
                                                        <option value="{{ $customer->fcustomerid }}"
                                                            {{-- CEK DISINI: Bandingkan dengan data yang tersimpan di DB --}}
                                                            {{ old('fcustno', $salesorder->fcustno) == $customer->fcustomerid ? 'selected' : '' }}>
                                                            {{ $customer->fcustomername }} ({{ $customer->fcustomerid }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <div class="absolute inset-0" role="button" aria-label="Browse Customer"
                                                    @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))">
                                                </div>
                                            </div>
                                            <input type="hidden" name="fcustno" id="customerCodeHidden"
                                                value="{{ old('fcustno', $salesorder->fcustno) }}">
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
                                                        <option value="{{ $salesman->fsalesmanid }}"
                                                            {{-- CEK DISINI: Bandingkan old input atau data dari database --}}
                                                            {{ old('fsalesman', $salesorder->fsalesman) == $salesman->fsalesmanid ? 'selected' : '' }}>
                                                            {{ $salesman->fsalesmanname }} ({{ $salesman->fsalesmanid }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <div class="absolute inset-0" role="button" aria-label="Browse Salesman"
                                                    @click="window.dispatchEvent(new CustomEvent('salesman-browse-open'))">
                                                </div>
                                            </div>
                                            <input type="hidden" name="fsalesman" id="salesmanCodeHidden"
                                                value="{{ old('fsalesman', $salesorder->fsalesman) }}">
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

                                    <div class="lg:col-span-4">
                                        <label class="block text-sm font-medium mb-1">Tempo</label>
                                        <div class="flex items-center">
                                            {{-- Gunakan trim() untuk membuang spasi di belakang angka --}}
                                            <input type="number" id="ftempohr" name="ftempohr"
                                                value="{{ trim(old('ftempohr', $salesorder->ftempohr ?? 0)) }}"
                                                class="w-full border rounded px-3 py-2">
                                            <span class="ml-2">Hari</span>
                                        </div>
                                    </div>
                                    <div class="col-span-12 mt-4">
                                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">

                                            <div x-data="{
                                                tab: 1,
                                                addr1: {{ json_encode(old('fkirimaddress1', $salesorder->customer->fkirimaddress1 ?? '')) }},
                                                addr2: {{ json_encode(old('fkirimaddress2', $salesorder->customer->fkirimaddress2 ?? '')) }},
                                                addr3: {{ json_encode(old('fkirimaddress3', $salesorder->customer->fkirimaddress3 ?? '')) }},
                                            
                                                updateFinal() {
                                                    let val = '';
                                                    if (this.tab === 1) val = this.addr1;
                                                    else if (this.tab === 2) val = this.addr2;
                                                    else if (this.tab === 3) val = this.addr3;
                                            
                                                    const el = document.getElementById('falamatkirim_final');
                                                    if (el) el.value = val;
                                                }
                                            }" x-init="const savedAddr = {{ json_encode(trim($salesorder->falamatkirim ?? '')) }};
                                            if (savedAddr && savedAddr === addr2) { tab = 2; } else if (savedAddr && savedAddr === addr3) { tab = 3; } else { tab = 1; if (savedAddr) addr1 = savedAddr; }
                                            updateFinal();
                                            $watch('tab', v => updateFinal());
                                            $watch('addr1', v => updateFinal());
                                            $watch('addr2', v => updateFinal());
                                            $watch('addr3', v => updateFinal());"
                                                @customer-selected.window="
                                addr1 = $event.detail.f1 || ''; 
                                addr2 = $event.detail.f2 || ''; 
                                addr3 = $event.detail.f3 || ''; 
                                tab = 1; 
                                updateFinal();
                                "
                                                class="flex flex-col gap-2">

                                                <input type="hidden" name="falamatkirim" id="falamatkirim_final"
                                                    value="{{ old('falamatkirim') }}">

                                                <div class="flex items-center gap-2">
                                                    <label class="text-sm font-bold text-gray-700 mr-2">Kirim ke :</label>

                                                    <div class="inline-flex rounded-md shadow-sm" role="group">
                                                        <button type="button" @click="tab = 1" disabled
                                                            :class="tab === 1 ?
                                                                'bg-blue-600 text-white z-10 ring-2 ring-blue-300' :
                                                                'bg-white text-gray-700 hover:bg-gray-50'"
                                                            class="px-4 py-1.5 text-xs font-semibold border border-gray-300 rounded-l-md transition-all">
                                                            Alamat 1
                                                        </button>
                                                        <button type="button" @click="tab = 2" disabled
                                                            :class="tab === 2 ?
                                                                'bg-blue-600 text-white z-10 ring-2 ring-blue-300' :
                                                                'bg-white text-gray-700 hover:bg-gray-50'"
                                                            class="px-4 py-1.5 text-xs font-semibold border-t border-b border-r border-gray-300 transition-all">
                                                            Alamat 2
                                                        </button>
                                                        <button type="button" @click="tab = 3" disabled
                                                            :class="tab === 3 ?
                                                                'bg-blue-600 text-white z-10 ring-2 ring-blue-300' :
                                                                'bg-white text-gray-700 hover:bg-gray-50'"
                                                            class="px-4 py-1.5 text-xs font-semibold border-t border-b border-r border-gray-300 rounded-r-md transition-all">
                                                            Alamat 3
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="w-full">
                                                    <textarea x-show="tab === 1" x-model="addr1" readonly
                                                        class="w-full p-2 text-sm border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 min-h-[80px]"
                                                        placeholder="Isi Alamat 1..."></textarea>

                                                    <textarea x-show="tab === 2" x-model="addr2" readonly
                                                        class="w-full p-2 text-sm border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 min-h-[80px]"
                                                        placeholder="Isi Alamat 2..."></textarea>

                                                    <textarea x-show="tab === 3" x-model="addr3" readonly
                                                        class="w-full p-2 text-sm border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 min-h-[80px]"
                                                        placeholder="Isi Alamat 3..."></textarea>
                                                </div>
                                            </div>

                                            <div class="flex flex-col">
                                                <label
                                                    class="block text-sm font-bold text-gray-700 mb-2">Keterangan</label>
                                                <div
                                                    class="flex-1 border-2 border-gray-200 rounded-xl p-3 bg-white min-h-[150px] focus-within:border-blue-400">
                                                    <textarea name="fket" class="w-full h-full border-none focus:ring-0 p-0 text-sm resize-none"
                                                        placeholder="Keterangan isi di sini...">{{ old('fket', $salesorder->fket) }}</textarea>
                                                </div>
                                                @error('fket')
                                                    <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="lg:col-span-12">
                                        <label class="block text-sm font-medium">Catatan Internal</label>
                                        <textarea name="fketinternal" rows="3"
                                            class="w-full border rounded px-3 py-2 @error('fketinternal') border-red-500 @enderror"
                                            placeholder="Tulis Catatan Internal tambahan di sini...">{{ old('fketinternal', $salesorder->fketinternal) }}</textarea>
                                        @error('fketinternal')
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
                                                    <th class="p-2 text-right w-36 whitespace-nowrap">Qty</th>
                                                    <th class="p-2 text-right w-32 whitespace-nowrap">@ Harga</th>
                                                    <th class="p-2 text-right w-36 whitespace-nowrap">Disc. %</th>
                                                    <th class="p-2 text-right w-36 whitespace-nowrap">Total Harga</th>
                                                    <th class="p-2 text-center w-28">Aksi</th>
                                                </tr>
                                            </thead>
                                            <!-- Loop untuk setiap item yang sudah disimpan -->
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
                                                                <span class="align-middle text-gray-600"
                                                                    x-text="it.fdesc"></span>
                                                            </div>
                                                        </td>
                                                        <td class="p-2" x-text="it.fsatuan"></td>
                                                        <td class="p-2 text-right" x-text="fmt(it.fqty)"></td>
                                                        <td class="p-2 text-right" x-text="fmt(it.fprice)"></td>
                                                        <td class="p-2 text-right" x-text="it.fdisc"></td>
                                                        <td class="p-2 text-right" x-text="fmt(it.ftotal)"></td>
                                                        <td class="p-2 text-center">
                                                            <div class="flex items-center justify-center gap-2 flex-wrap">
                                                                <button type="button" @click="edit(i)"
                                                                    class="px-3 py-1 rounded text-xs bg-amber-100 text-amber-700 hover:bg-amber-200">Edit</button>
                                                                <button type="button" @click="removeSaved(i)"
                                                                    class="px-3 py-1 rounded text-xs bg-red-100 text-red-600 hover:bg-red-200">Hapus</button>
                                                            </div>
                                                        </td>
                                                    </tr>

                                                    <!-- Hidden inputs row -->
                                                    <tr class="hidden">
                                                        <td colspan="9">
                                                            <input type="hidden" name="fitemcode[]"
                                                                :value="it.fitemcode">
                                                            <input type="hidden" name="fitemname[]"
                                                                :value="it.fitemname">
                                                            <input type="hidden" name="fsatuan[]"
                                                                :value="it.fsatuan">
                                                            <input type="hidden" name="frefdtno[]"
                                                                :value="it.frefdtno">
                                                            <input type="hidden" name="fnouref[]"
                                                                :value="it.fnouref">
                                                            <input type="hidden" name="frefpr[]" :value="it.frefpr">
                                                            <input type="hidden" name="fqty[]" :value="it.fqty">
                                                            <input type="hidden" name="fterima[]"
                                                                :value="it.fterima">
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

                                            <!-- Row Edit & Draft (di luar loop) -->
                                            <tbody>
                                                <!-- ROW EDIT UTAMA -->
                                                <tr x-show="editingIndex !== null" class="border-t align-top" x-cloak>
                                                    <td class="p-2" x-text="(editingIndex ?? 0) + 1"></td>

                                                    <!-- Kode Produk -->
                                                    <td class="p-2">
                                                        <div class="flex">
                                                            <input type="text"
                                                                class="flex-1 border rounded-l px-2 py-1 font-mono"
                                                                x-ref="editCode" x-model.trim="editRow.fitemcode"
                                                                @input="onCodeTypedRow(editRow)"
                                                                @keydown.enter.prevent="handleEnterOnCode('edit')">
                                                            <button type="button" @click="openBrowseFor('edit')"
                                                                class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50"
                                                                title="Cari Produk">
                                                                <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                            </button>
                                                            <a href="{{ route('product.create') }}" target="_blank"
                                                                rel="noopener"
                                                                class="border border-l-0 rounded-r px-2 py-1 bg-white hover:bg-gray-50"
                                                                title="Tambah Produk">
                                                                <x-heroicon-o-plus class="w-4 h-4" />
                                                            </a>
                                                        </div>
                                                    </td>

                                                    <!-- Nama Produk (readonly) -->
                                                    <td class="p-2">
                                                        <input type="text"
                                                            class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                            :value="editRow.fitemname" disabled>
                                                    </td>

                                                    <!-- Satuan -->
                                                    <td class="p-2">
                                                        <template x-if="editRow.units.length > 1">
                                                            <select class="w-full border rounded px-2 py-1"
                                                                x-ref="editUnit" x-model="editRow.fsatuan"
                                                                @keydown.enter.prevent="$refs.editQty?.focus()">
                                                                <template x-for="u in editRow.units"
                                                                    :key="u">
                                                                    <option :value="u" x-text="u">
                                                                    </option>
                                                                </template>
                                                            </select>
                                                        </template>
                                                        <template x-if="editRow.units.length <= 1">
                                                            <input type="text"
                                                                class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                                :value="editRow.fsatuan || '-'" disabled>
                                                        </template>
                                                    </td>

                                                    <!-- Qty -->
                                                    <td class="p-2 text-right">
                                                        <input type="number"
                                                            class="border rounded px-2 py-1 w-24 text-right"
                                                            min="0" step="1" x-ref="editQty"
                                                            x-model.number="editRow.fqty" @input="recalc(editRow)"
                                                            @keydown.enter.prevent="$refs.editPrice?.focus()">
                                                    </td>

                                                    <!-- @ Harga -->
                                                    <td class="p-2 text-right">
                                                        <input type="number"
                                                            class="border rounded px-2 py-1 w-28 text-right"
                                                            min="0" step="0.01" x-ref="editPrice"
                                                            x-model.number="editRow.fprice" @input="recalc(editRow)"
                                                            @keydown.enter.prevent="$refs.editDisc?.focus()">
                                                    </td>

                                                    <!-- Disc.% -->
                                                    <td class="p-2 text-right">
                                                        <input type="text"
                                                            class="border rounded px-2 py-1 w-24 text-right"
                                                            x-ref="editDisc" x-model="editRow.fdisc"
                                                            @input="recalc(editRow)"
                                                            @keydown.enter.prevent="$refs.editDesc?.focus()"
                                                            placeholder="10+2">
                                                    </td>

                                                    <!-- Total Harga (readonly) -->
                                                    <td class="p-2 text-right" x-text="fmt(editRow.ftotal)"></td>

                                                    <!-- Aksi -->
                                                    <td class="p-2 text-center">
                                                        <div class="flex items-center justify-center gap-2 flex-wrap">
                                                            <button type="button" @click="applyEdit()"
                                                                class="px-3 py-1 rounded text-xs bg-emerald-600 text-white">Simpan</button>
                                                            <button type="button" @click="cancelEdit()"
                                                                class="px-3 py-1 rounded text-xs bg-gray-100">Batal</button>
                                                        </div>
                                                    </td>
                                                </tr>

                                                <!-- ROW EDIT DESC - Menggunakan editRow.fdesc -->
                                                <tr x-show="editingIndex !== null" class="border-b" x-cloak>
                                                    <td class="p-0"></td>
                                                    <td class="p-0"></td>
                                                    <td class="p-2" colspan="6">
                                                        <textarea x-model="editRow.fdesc" x-ref="editDesc" rows="2" class="w-full border rounded px-4 py-1"
                                                            placeholder="Deskripsi (opsional)" @keydown.enter.prevent="applyEdit()"></textarea>
                                                    </td>
                                                    <td class="p-0"></td>
                                                </tr>

                                                <!-- ROW DRAFT UTAMA -->
                                                <tr class="border-t align-top">
                                                    <td class="p-2" x-text="savedItems.length + 1"></td>

                                                    <!-- Kode Produk -->
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
                                                            <a href="{{ route('product.create') }}" target="_blank"
                                                                rel="noopener"
                                                                class="border border-l-0 rounded-r px-2 py-1 bg-white hover:bg-gray-50"
                                                                title="Tambah Produk">
                                                                <x-heroicon-o-plus class="w-4 h-4" />
                                                            </a>
                                                        </div>
                                                    </td>

                                                    <!-- Nama Produk (readonly) -->
                                                    <td class="p-2">
                                                        <input type="text"
                                                            class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                            :value="draft.fitemname" disabled>
                                                    </td>

                                                    <!-- Satuan -->
                                                    <td class="p-2">
                                                        <template x-if="draft.units.length > 1">
                                                            <select class="w-full border rounded px-2 py-1"
                                                                x-ref="draftUnit" x-model="draft.fsatuan"
                                                                @keydown.enter.prevent="$refs.draftQty?.focus()">
                                                                <template x-for="u in draft.units" :key="u">
                                                                    <option :value="u" x-text="u">
                                                                    </option>
                                                                </template>
                                                            </select>
                                                        </template>
                                                        <template x-if="draft.units.length <= 1">
                                                            <input type="text"
                                                                class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                                :value="draft.fsatuan || '-'" disabled>
                                                        </template>
                                                    </td>

                                                    <!-- Qty -->
                                                    <td class="p-2 text-right">
                                                        <input type="number"
                                                            class="border rounded px-2 py-1 w-24 text-right"
                                                            min="0" step="1" x-ref="draftQty"
                                                            x-model.number="draft.fqty" @input="recalc(draft)"
                                                            @keydown.enter.prevent="$refs.draftPrice?.focus()">
                                                    </td>

                                                    <!-- @ Harga -->
                                                    <td class="p-2 text-right">
                                                        <input type="number"
                                                            class="border rounded px-2 py-1 w-28 text-right"
                                                            min="0" step="0.01" x-ref="draftPrice"
                                                            x-model.number="draft.fprice" @input="recalc(draft)"
                                                            @keydown.enter.prevent="$refs.draftDisc?.focus()">
                                                    </td>

                                                    <!-- Disc.% -->
                                                    <td class="p-2 text-right">
                                                        <input type="text"
                                                            class="border rounded px-2 py-1 w-24 text-right"
                                                            x-ref="draftDisc" x-model="draft.fdisc"
                                                            @input="recalc(draft)"
                                                            @keydown.enter.prevent="$refs.draftDesc?.focus()"
                                                            placeholder="10+2">
                                                    </td>

                                                    <!-- Total Harga (readonly) -->
                                                    <td class="p-2 text-right" x-text="fmt(draft.ftotal)"></td>

                                                    <!-- Aksi -->
                                                    <td class="p-2 text-center">
                                                        <div class="flex items-center justify-center gap-2 flex-wrap">
                                                            <button type="button" @click="addIfComplete()"
                                                                class="px-3 py-1 rounded text-xs bg-emerald-600 text-white">Tambah</button>
                                                        </div>
                                                    </td>
                                                </tr>

                                                <!-- ROW DRAFT DESC - Menggunakan draft.fdesc -->
                                                <tr class="border-b">
                                                    <td class="p-0"></td>
                                                    <td class="p-0"></td>
                                                    <td class="p-2" colspan="6">
                                                        <textarea x-model="draft.fdesc" x-ref="draftDesc" rows="2" class="w-full border rounded px-4 py-1"
                                                            placeholder="Deskripsi (opsional)" @keydown.enter.prevent="addIfComplete()"></textarea>
                                                    </td>
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
                                                            x-text="rupiah(totalHarga)"></span>
                                                    </div>
                                                    <div class="flex items-center justify-between gap-6">
                                                        <!-- Checkbox -->
                                                        <div class="flex items-center">
                                                            <input id="fapplyppn" type="checkbox" name="fapplyppn"
                                                                value="1" x-model="includePPN"
                                                                class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                            <label for="fapplyppn"
                                                                class="ml-2 text-sm font-medium text-gray-700">
                                                                <span class="font-bold">PPN</span>
                                                            </label>
                                                        </div>

                                                        <!-- Dropdown Include / Exclude (tengah) -->
                                                        <div class="flex items-center gap-2">
                                                            <select id="includePPN" name="includePPN"
                                                                x-model.number="fapplyppn" x-init="fapplyppn = 0"
                                                                :disabled="!(includePPN || fapplyppn)"
                                                                class="w-28 h-9 px-2 text-sm leading-tight border rounded transition-opacity appearance-none
                                                           disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed">
                                                                <option value="0">Exclude</option>
                                                                <option value="1">Include</option>
                                                            </select>
                                                        </div>

                                                        <!-- Input Rate + Nominal (kanan) -->
                                                        <div class="flex items-center gap-2">
                                                            <input type="number" min="0" max="100"
                                                                step="0.01" x-model.number="ppnRate"
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
                                                <input type="hidden" name="" :value="ppnAmount">
                                                <input type="hidden" name="famountso" :value="grandTotal">
                                                <input type="hidden" name="famountpopajak" :value="ppnRate">
                                            </div>
                                        </div>

                                        <!-- MODAL DESC (di dalam itemsTable) -->
                                        <div x-show="showDescModal" x-cloak
                                            class="fixed inset-0 z-[95] flex items-center justify-center"
                                            x-transition.opacity>
                                            <div class="absolute inset-0 bg-black/50" @click="closeDesc()"></div>

                                            <div class="relative bg-white w-[92vw] max-w-lg rounded-2xl shadow-2xl overflow-hidden"
                                                x-transition.scale>
                                                <div class="px-5 py-4 border-b flex items-center">
                                                    <x-heroicon-o-document-text class="w-6 h-6 text-blue-600 mr-2" />
                                                    <h3 class="text-lg font-semibold text-gray-800">Isi Deskripsi Item
                                                    </h3>
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
                                                    Anda belum menambahkan item apa pun pada tabel. Silakan isi baris
                                                    “Detail
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
                                                    <p class="text-sm text-gray-500 mt-0.5">Pilih customer yang diinginkan
                                                    </p>
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
                                                    <p class="text-sm text-gray-500 mt-0.5">Pilih salesman yang diinginkan
                                                    </p>
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
                                                    <p class="text-sm text-gray-500 mt-0.5">Pilih produk yang diinginkan
                                                    </p>
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
                                                <input type="checkbox" name="fapproval" id="approvalToggle"
                                                    value="1"
                                                    {{ old('fapproval', session('fapproval') ? 1 : 0) ? 'checked' : '' }}>
                                                <span class="slider"></span>
                                            </label>
                                        @endif
                                    </div>

                                    <div class="mt-8 flex justify-center gap-4">
                                        <button type="submit"
                                            class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                                            <x-heroicon-o-check class="w-5 h-5 mr-2" /> Simpan
                                        </button>
                                        <button type="button"
                                            @click="window.location.href='{{ route('salesorder.index') }}'"
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
                <h3 class="text-lg font-semibold mb-4">Konfirmasi Hapus sales order ini?</h3>
                <form id="deleteForm" action="{{ route('salesorder.destroy', $salesorder->ftrsomtid) }}"
                    method="POST">
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
                fetch('{{ route('salesorder.destroy', $salesorder->ftrsomtid) }}', {
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
                            window.location.href = '{{ route('salesorder.index') }}';
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
                $('#customerBrowseTable').on('click', '.btn-choose', (e) => {
                    const data = this.dataTable.row($(e.target).closest('tr')).data();
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
                const sel = document.getElementById('modal_filter_customer_id');
                const hid = document.getElementById('customerCodeHidden');

                if (!sel) {
                    this.close();
                    return;
                }

                // 1. Set Dropdown Customer (Logika lama Anda)
                let opt = [...sel.options].find(o => o.value == String(customer.fcustomerid));
                if (!opt) {
                    opt = new Option(`${customer.fcustomername} (${customer.fcustomercode})`, customer.fcustomerid,
                        true, true);
                    sel.add(opt);
                } else {
                    opt.selected = true;
                }
                if (hid) hid.value = customer.fcustomerid;

                // 2. Kirim data alamat ke Alpine.js menggunakan Event
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
                $('#salesmanBrowseTable').on('click', '.btn-choose', (e) => {
                    const data = this.dataTable.row($(e.target).closest('tr')).data();
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
                const sel = document.getElementById('modal_filter_salesman_id');
                const hid = document.getElementById('salesmanCodeHidden');

                if (!sel) {
                    this.close();
                    return;
                }

                let opt = [...sel.options].find(o => o.value == String(salesman.fsalesmanid));
                const label = `${salesman.fsalesmanname} (${salesman.fsalesmancode})`;

                if (!opt) {
                    opt = new Option(label, salesman.fsalesmanid, true, true);
                    sel.add(opt);
                } else {
                    opt.text = label;
                    opt.selected = true;
                }

                sel.dispatchEvent(new Event('change'));
                if (hid) hid.value = salesman.fsalesmanid;
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
            editingIndex: null,
            editRow: newRow(),

            totalHarga: 0,
            ppnRate: 11,

            initialGrandTotal: @json($famountso ?? 0),
            initialPpnAmount: @json($famountpopajak ?? 0),

            includePPN: false,
            fapplyppn: false,

            get ppnIncluded() {
                const total = +this.totalHarga || 0;
                const rate = +this.ppnRate || 0;
                if (!this.fapplyppn) return 0;
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
                if (this.fapplyppn) {
                    return this.ppnIncluded;
                }
                if (this.includePPN) {
                    return this.ppnAdded;
                }
                return 0;
            },

            get grandTotal() {
                const total = +this.totalHarga || 0;
                if (this.fapplyppn) {
                    return total;
                }
                if (this.includePPN) {
                    return total + this.ppnAdded;
                }
                return total;
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
                row.fqty = Math.max(0, +row.fqty || 0);
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
                this.draft = newRow();
                this.$nextTick(() => this.$refs.draftCode?.focus());
            },

            addManyFromPR(header, items) {
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
                        fnouref: src.fnouref ?? '',
                        frefpr: src.frefpr ?? (header?.fsono ?? ''),
                        fprnoid: src.fprnoid ?? header?.fprnoid ?? '',
                        fqty: Number(src.fqty ?? 0),
                        fterima: Number(src.fterima ?? 0),
                        fprice: Number(src.fprice ?? 0),
                        fdisc: src.fdisc ?? 0, // ✅ Simpan format asli (bisa string "10+2")
                        ftotal: Number(src.ftotal ?? 0),
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

            addIfComplete() {
                const r = this.draft;

                if (!this.isComplete(r)) {
                    if (!r.fitemcode) return this.$refs.draftCode?.focus();
                    if (!r.fitemname) return this.$refs.draftCode?.focus();
                    if (!r.fsatuan) return (r.units.length > 1 ? this.$refs.draftUnit?.focus() : this.$refs.draftCode
                        ?.focus());
                    if (!(Number(r.fqty) > 0)) return this.$refs.draftQty?.focus();
                    return;
                }

                this.recalc(r);

                const dupe = this.savedItems.find(it =>
                    it.fitemcode === r.fitemcode &&
                    it.fsatuan === r.fsatuan &&
                    (it.frefpr || '') === (r.frefpr || '')
                );

                if (dupe) {
                    this.showToast('Item sama sudah ada di daftar', 'warning');
                    return;
                }

                this.savedItems.push({
                    ...r,
                    uid: cryptoRandom()
                });
                this.showNoItems = false;
                this.resetDraft();
                this.$nextTick(() => this.$refs.draftCode?.focus());
                this.syncDescList?.();
                this.recalcTotals();
            },

            edit(i) {
                this.editingIndex = i;
                this.editRow = {
                    ...this.savedItems[i]
                };
                this.hydrateRowFromMeta(this.editRow, this.productMeta(this.editRow.fitemcode));
                this.$nextTick(() => this.$refs.editQty?.focus());
            },

            applyEdit() {
                const r = this.editRow;
                if (!this.isComplete(r)) {
                    alert('Lengkapi data item.');
                    return;
                }
                this.recalc(r);
                this.savedItems.splice(this.editingIndex, 1, {
                    ...r
                });
                this.cancelEdit();
                this.syncDescList?.();
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

                window.getCurrentItemKeys = () => this.getCurrentItemKeys();
                window.addEventListener('pr-picked', this.onPrPicked.bind(this), {
                    passive: true
                });

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
                    } else {
                        apply(this.draft);
                        this.$nextTick(() => this.$refs.draftQty?.focus());
                    }
                }, {
                    passive: true
                });
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
                        .replace('PR_ID_PLACEHOLDER', row.fprid);

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
                        `${(src.fitemcode ?? '').toString().trim()}::${(src.frefdtno ?? '').toString().trim()}`;

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
