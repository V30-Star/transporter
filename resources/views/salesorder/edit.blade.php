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
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow p-0 overflow-hidden" role="alert">
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
    @php
        $currentCustomerCode = trim((string) old('fcustno', $salesorder->fcustno ?? ''));
        $currentCustomerName = trim((string) old('fcustomername', $salesorder->customer->fcustomername ?? ''));
        $hasCurrentCustomer = $currentCustomerCode !== '' && $customers->contains(function ($customer) use ($currentCustomerCode) {
            return trim((string) $customer->fcustomercode) === $currentCustomerCode;
        });
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
                            {{ $action === 'delete' ? 'Sales Order Tidak Dapat Dihapus' : 'Sales Order Tidak Dapat Diedit' }}
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
                                <input disabled type="date" name="fsodate"
                                    value="{{ old('fsodate') ?? date('Y-m-d', strtotime($salesorder->fsodate)) }}"
                                    class="w-full border rounded px-3 py-2 bg-gray-200 @error('fsodate') border-red-500 @enderror">
                                @error('fsodate')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="lg:col-span-2 flex items-end pb-2">
                                <div class="inline-flex items-center">
                                    <input id="fclose" type="checkbox" name="fclose" value="1" x-model="fclose"
                                        disabled
                                        {{-- text-red-600 mengubah isi centang, border-red-400 mengubah bingkai --}}
                                        class="w-6 h-6 text-red-600 border-red-400 bg-gray-200 rounded cursor-not-allowed focus:ring-red-500"
                                        {{ old('fclose', $salesorder->fclose) ? 'checked' : '' }}>

                                    <label for="fclose" {{-- text-red-600 mengubah warna tulisan menjadi merah --}}
                                        class="ml-3 text-base font-bold text-red-600 whitespace-nowrap cursor-not-allowed">
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
                                            @if ($currentCustomerCode !== '' && !$hasCurrentCustomer)
                                                <option value="{{ $currentCustomerCode }}" selected>
                                                    {{ $currentCustomerName !== '' ? $currentCustomerName . ' (' . $currentCustomerCode . ')' : $currentCustomerCode }}
                                                </option>
                                            @endif
                                            @foreach ($customers as $customer)
                                                <option value="{{ $customer->fcustomercode }}" {{-- CEK DISINI: Bandingkan dengan data yang tersimpan di DB --}}
                                                    {{ old('fcustno', $salesorder->fcustno) == $customer->fcustomercode ? 'selected' : '' }}>
                                                    {{ $customer->fcustomername }} ({{ $customer->fcustomercode }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="absolute inset-0 pointer-events-none" role="button"
                                            aria-label="Browse Customer">
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
                                                <option value="{{ $salesman->fsalesmancode }}" {{-- CEK DISINI: Bandingkan old input atau data dari database --}}
                                                    {{ old('fsalesman', $salesorder->fsalesman) == $salesman->fsalesmancode ? 'selected' : '' }}>
                                                    {{ $salesman->fsalesmanname }} ({{ $salesman->fsalesmancode }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="absolute inset-0 pointer-events-none" role="button"
                                            aria-label="Browse Salesman">
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
                                            <th class="p-2 text-right w-36 whitespace-nowrap">Qty.SRJ</th>
                                            <th class="p-2 text-right w-32 whitespace-nowrap">@ Harga</th>
                                            <th class="p-2 text-right w-36 whitespace-nowrap">Disc. %</th>
                                            <th class="p-2 text-right w-36 whitespace-nowrap">Total Harga</th>
                                            <th class="p-2 text-center w-28">Aksi</th>
                                        </tr>
                                    </thead>

                                    <template x-for="(it, i) in savedItems" :key="it.uid || `item-${i}`">
                                        <tbody>
                                            <!-- ROW UTAMA - SAVED ITEM (READ ONLY) -->
                                            <tr class="border-t align-top">
                                                <td class="p-2" x-text="i + 1"></td>
                                                <td class="p-2 font-mono" x-text="it.fprdcode"></td>
                                                <td class="p-2 text-gray-800" x-text="it.fitemname"></td>
                                                <td class="p-2">
                                                    <template x-if="it.units && it.units.length > 1">
                                                        <select class="w-full border rounded px-2 py-1 text-xs bg-gray-100 text-gray-600"
                                                            x-model="it.fsatuan" disabled>
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
                                                <td class="p-2 text-right font-medium" x-text="formatQtyValue(it.fqty)"></td>
                                                <td class="p-2 text-right">
                                                    <input type="text"
                                                        class="w-full border rounded px-2 py-1 text-right bg-gray-100 text-gray-600"
                                                        :value="formatQtyValue(it.fqtysrj)" disabled>
                                                </td>
                                                <td class="p-2 text-right">
                                                    <input type="number"
                                                        class="w-full border rounded px-2 py-1 text-right bg-gray-100 text-gray-600"
                                                        x-model.number="it.fprice" disabled>
                                                </td>
                                                <td class="p-2 text-right">
                                                    <input type="text"
                                                        class="w-full border rounded px-2 py-1 text-right bg-gray-100 text-gray-600"
                                                        x-model="it.fdisc" disabled>
                                                </td>
                                                <td class="p-2 text-right font-semibold" x-text="fmt(it.ftotal)"></td>
                                                <td class="p-2 text-center">
                                                    <button type="button" disabled
                                                        class="px-3 py-1 rounded text-xs bg-gray-100 text-gray-400 cursor-not-allowed">Hapus</button>
                                                </td>
                                            </tr>

                                            <!-- ROW DESC -->
                                            <tr class="border-b">
                                                <td class="p-0"></td>
                                                <td class="p-0"></td>
                                                <td class="p-2" colspan="2">
                                                    <textarea x-model="it.fdesc" rows="1" readonly class="w-full border rounded px-2 py-1 text-xs bg-gray-100 text-gray-600"
                                                        placeholder="Deskripsi item (opsional)"></textarea>
                                                </td>
                                                <td class="p-0" colspan="6"></td>
                                            </tr>

                                            <!-- Hidden inputs row -->
                                            <tr class="hidden">
                                                <td colspan="9">
                                                    <input type="hidden" name="fitemcode[]" :value="it.fitemcode">
                                                    <input type="hidden" name="fitemname[]" :value="it.fitemname">
                                                    <input type="hidden" name="fsatuan[]" :value="it.fsatuan">
                                                    <input type="hidden" name="fnoacak[]" :value="it.fnoacak">
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
                                    <tr x-show="false" class="border-t align-top" x-cloak>
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
                                                type="number" x-ref="editQty" x-model.number="editRow.fqty"
                                                @input="
                                                    recalc(editRow);
                                                "
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
                                    <tr x-show="false" class="border-b" x-cloak>
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
                                    <tr x-show="false" class="border-b">
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
                                                        x-init="includePPN = {{ $salesorder->fppn == '1' ? 'true' : 'false' }}"
                                                        class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                    <label for="fapplyppn" class="ml-2 text-sm font-medium text-gray-700">
                                                        <span class="font-bold">PPN</span>
                                                    </label>
                                                </div>

                                                <!-- Dropdown Include / Exclude (tengah) -->
                                                <div class="flex items-center gap-2">
                                                    <select disabled id="includePPN" name="includePPN"
                                                        x-model.number="fapplyppn" x-init="fapplyppn = {{ old('includePPN', $salesorder->fincludeppn) }}"
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
                                                        step="0.01" x-model.number="ppnRate" x-init="ppnRate = {{ old('fppnpersen', $fppnpersen) }}"
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

                                <input type="hidden" name="itemsCount" :value="savedItems.length">
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
                                class="mt-6"
                                @submit.prevent="
        const n = Number($el.querySelector('input[name=itemsCount]')?.value || 0);
        if (n < 1) { 
            Swal.fire({
                icon: 'warning',
                title: 'Tidak Ada Item',
                text: 'Silakan tambahkan minimal 1 item terlebih dahulu.',
                confirmButtonText: 'OK',
                customClass: {
                    confirmButton: 'bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700'
                }
            });
        } else { 
            $el.submit();
        }
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
                                            value="{{ old('fsodate') ?? date('Y-m-d', strtotime($salesorder->fsodate)) }}"
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
                                                    @if ($currentCustomerCode !== '' && !$hasCurrentCustomer)
                                                        <option value="{{ $currentCustomerCode }}" selected>
                                                            {{ $currentCustomerName !== '' ? $currentCustomerName . ' (' . $currentCustomerCode . ')' : $currentCustomerCode }}
                                                        </option>
                                                    @endif
                                                    @foreach ($customers as $customer)
                                                        <option value="{{ $customer->fcustomercode }}"
                                                            {{-- CEK DISINI: Bandingkan dengan data yang tersimpan di DB --}}
                                                            {{ old('fcustno', $salesorder->fcustno) == $customer->fcustomercode ? 'selected' : '' }}>
                                                            {{ $customer->fcustomername }} ({{ $customer->fcustomercode }})
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
                                                        <option value="{{ $salesman->fsalesmancode }}"
                                                            {{-- CEK DISINI: Bandingkan old input atau data dari database --}}
                                                            {{ old('fsalesman', $salesorder->fsalesman) == $salesman->fsalesmancode ? 'selected' : '' }}>
                                                            {{ $salesman->fsalesmanname }} ({{ $salesman->fsalesmancode }})
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
                                                    <th class="p-2 text-right w-28 whitespace-nowrap">Qty</th>
                                                    <th class="p-2 text-right w-28 whitespace-nowrap">Qty.SRJ</th>
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
                                                    <tr class="border-t align-top">
                                                        <td class="p-2" x-text="i + 1"></td>
                                                        <td class="p-2 font-mono" x-text="it.fprdcode"></td>
                                                        <td class="p-2 text-gray-800" x-text="it.fitemname"></td>
                                                        <td class="p-2">
                                                            <template x-if="it.units && it.units.length > 1">
                                                                <select class="w-full border rounded px-2 py-1 text-xs"
                                                                    x-model="it.fsatuan" @change="recalc(it)">
                                                                    <template x-for="u in it.units"
                                                                        :key="u">
                                                                        <option :value="u" x-text="u"
                                                                            :selected="u === it.fsatuan"></option>
                                                                    </template>
                                                                </select>
                                                            </template>
                                                            <template x-if="!it.units || it.units.length <= 1">
                                                                <span class="text-xs" x-text="it.fsatuan"></span>
                                                            </template>
                                                        </td>
                                                        <td class="p-2 text-right">
                                                            <input type="number"
                                                                class="w-full border rounded px-2 py-1 text-right"
                                                                type="number" x-model.number="it.fqty"
                                                                @input="
                                                                    recalc(it);
                                                                    
                                                                ">
                                                        </td>
                                                        <td class="p-2 text-right font-medium"
                                                            x-text="formatQtyValue(it.fqtysrj)"></td>
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
                                                        <td class="p-2 text-right font-semibold" x-text="fmt(it.ftotal)">
                                                        </td>
                                                        <td class="p-2 text-center">
                                                            <button type="button" @click="removeSaved(i)"
                                                                class="px-3 py-1 rounded text-xs bg-red-100 text-red-600 hover:bg-red-200">Hapus</button>
                                                        </td>
                                                    </tr>

                                                    <tr class="hidden">
                                                        <td colspan="10">
                                                            <input type="hidden" name="fprdcode[]"
                                                                :value="it.fprdcode">
                                                            <input type="hidden" name="fitemname[]"
                                                                :value="it.fitemname">
                                                            <input type="hidden" name="fsatuan[]"
                                                                :value="it.fsatuan">
                                                            <input type="hidden" name="fqty[]" :value="it.fqty">
                                                            <input type="hidden" name="fprice[]" :value="it.fprice">
                                                            <input type="hidden" name="fdisc[]" :value="it.fdisc">
                                                            <input type="hidden" name="ftotal[]" :value="it.ftotal">
                                                            <input type="hidden" name="fdesc[]" :value="it.fdesc">
                                                        </td>
                                                    </tr>

                                                    <!-- ROW DESC RESTRICTED -->
                                                    <tr class="border-b">
                                                        <td class="p-0"></td>
                                                        <td class="p-0"></td>
                                                        <td class="p-0"></td>
                                                        <td class="p-2" colspan="2">
                                                            <textarea x-model="it.fdesc" rows="1" class="w-full border rounded px-2 py-1 text-xs"
                                                                placeholder="Deskripsi item (opsional)"></textarea>
                                                        </td>
                                                        <td class="p-0" colspan="5"></td>
                                                    </tr>

                                                    <!-- TIDAK ADA TEXTAREA DI SINI! -->
                                                </tbody>
                                            </template>

                                            <!-- Row Edit & Draft (di luar loop) -->
                                            <tbody>


                                                <!-- ROW DRAFT UTAMA -->
                                                <tr class="border-t align-top">
                                                    <td class="p-2" x-text="savedItems.length + 1"></td>

                                                    <!-- Kode Produk -->
                                                    <td class="p-2">
                                                        <div class="flex">
                                                            <input type="text"
                                                                class="flex-1 border rounded-l px-2 py-1 font-mono"
                                                                x-ref="draftCode" x-model.trim="draft.fprdcode"
                                                                @input="onCodeTypedRow(draft)"
                                                                @keydown.enter.prevent="handleEnterOnCode('draft')">
                                                            <button type="button" @click="openBrowseFor('draft')"
                                                                class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50"
                                                                title="Cari Produk">
                                                                <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                            </button>
                                                        </div>
                                                    </td>

                                                    <!-- Nama Produk (readonly) -->
                                                    <td class="p-2">
                                                        <input type="text"
                                                            class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                            :value="draft.fitemname" disabled>
                                                    </td>

                                                    <td class="p-2">
                                                        <template x-if="draft.units.length > 1">
                                                            <select id="draftUnitSelect"
                                                                class="w-full border rounded px-2 py-1 text-xs"
                                                                x-model="draft.fsatuan" @change="recalc(draft)"
                                                                @keydown.enter.prevent="$refs.draftQty?.focus()">
                                                                <template x-for="u in draft.units" :key="u">
                                                                    <option :value="u" x-text="u">
                                                                    </option>
                                                                </template>
                                                            </select>
                                                        </template>
                                                        <template x-if="draft.units.length <= 1">
                                                            <input type="text"
                                                                class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-xs"
                                                                :value="draft.fsatuan || '-'" disabled>
                                                        </template>
                                                    </td>

                                                    <!-- Qty -->
                                                    <td class="p-2 text-right">
                                                        <input type="number"
                                                            class="border rounded px-2 py-1 w-24 text-right"
                                                            type="number" x-ref="draftQty" x-model.number="draft.fqty"
                                                            @input="
                                                                recalc(draft);
                                                            "
                                                            @keydown.enter.prevent="$refs.draftPrice?.focus()">
                                                        <div class="text-xs text-gray-400 mt-0.5 text-right invisible">
                                                            limit
                                                        </div>
                                                    </td>
                                                    <td class="p-2 text-right font-medium" x-text="formatQtyValue(0)"></td>
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

                                                <!-- ROW DRAFT DESC RESTRICTED -->
                                                <tr class="border-b">
                                                    <td class="p-0"></td>
                                                    <td class="p-0"></td>
                                                    <td class="p-0"></td>
                                                    <td class="p-2" colspan="2">
                                                        <textarea x-model="draft.fdesc" x-ref="draftDesc" rows="2" class="w-full border rounded px-4 py-1"
                                                            placeholder="Deskripsi (opsional)" @keydown.enter.prevent="addIfComplete()"></textarea>
                                                    </td>
                                                    <td class="p-0" colspan="6"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Kanan: Panel Totals -->
                                    <div class="mt-3 flex justify-end">
                                        <div class="w-full md:w-1/2">
                                            <div class="rounded-lg border bg-gray-50 p-3 space-y-2">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-sm text-gray-700">Total Harga</span>
                                                    <span class="min-w-[140px] text-right font-medium"
                                                        x-text="rupiah(totalHarga)"></span>
                                                </div>

                                                <div class="flex items-center justify-between">
                                                    <span class="text-sm text-gray-700">Total DPP</span>
                                                    <span class="min-w-[140px] text-right font-medium"
                                                        x-text="rupiah(totalDPP)"></span>
                                                </div>

                                                <div class="flex items-center justify-between gap-6">
                                                    <!-- Checkbox -->
                                                    <div class="flex items-center">
                                                        <input id="fapplyppn" type="checkbox" value="1"
                                                            name="fapplyppn" x-model="includePPN" x-init="includePPN = {{ $salesorder->fapplyppn == '1' ? 'true' : 'false' }}"
                                                            class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                        <label for="fapplyppn"
                                                            class="ml-2 text-sm font-medium text-gray-700">
                                                            <span class="font-bold">PPN</span>
                                                        </label>
                                                    </div>

                                                    <!-- Dropdown Include / Exclude (tengah) -->
                                                    <div class="flex items-center gap-2">
                                                        <select id="ppnMode" name="fincludeppn"
                                                            x-model.number="ppnMode" x-init="ppnMode = {{ old('fincludeppn', $salesorder->fincludeppn ?? 0) }}"
                                                            :disabled="!includePPN"
                                                            class="w-28 h-9 px-2 text-sm leading-tight border rounded transition-opacity appearance-none
                                                           disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed">
                                                            <option value="0">Exclude</option>
                                                            <option value="1">Include</option>
                                                        </select>
                                                    </div>

                                                    <!-- Input Rate + Nominal (kanan) -->
                                                    <div class="flex items-center gap-2">
                                                        <input type="number" name="fppnpersen" min="0"
                                                            max="100" step="0.01" x-model.number="ppnRate"
                                                            x-init="ppnRate = {{ old('fppnpersen', $salesorder->fppnpersen ?? 11) }}" :disabled="!includePPN"
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
                                            <input type="hidden" name="famountso" :value="grandTotal">
                                            <input type="hidden" name="famountsonet" :value="totalDPP">
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

                                    <input type="hidden" name="itemsCount" :value="savedItems.length">
                                </div>

                                <x-transaction.browse-customer-modal />

                                <x-transaction.browse-salesman-modal />

                                <x-transaction.browse-product-modal show-controls="true" show-pagination="true" />

                                @php
                                    $canApproval = in_array(
                                        'approvalpr',
                                        explode(',', session('user_restricted_permissions', '')),
                                    );
                                @endphp

                                {{-- APPROVAL & ACTIONS --}}
                                @php
                                    $canApproval = in_array(
                                        'approvalpr',
                                        explode(',', session('user_restricted_permissions', '')),
                                    );
                                @endphp

                                <div class="flex justify-center items-center space-x-2 mt-6">
                                    @if ($canApproval)
                                        <label class="block text-sm font-medium">Approval</label>

                                        {{-- fallback 0 saat checkbox tidak dicentang --}}
                                        <input type="hidden" name="fapproval" value="0">

                                        <label class="switch">
                                            <input type="checkbox" name="fapproval" id="approvalToggle" value="1"
                                                {{ old('fapproval', $salesorder->fapproval) ? 'checked' : '' }}>
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
                <form id="deleteForm" action="{{ route('salesorder.destroy', $salesorder->ftrsomtid) }}" method="POST">
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

            totalHarga: 0,
            ppnRate: 11,

            initialGrandTotal: @json($famountso ?? 0),
            initialPpnAmount: @json($famountpopajak ?? 0),

            includePPN: false,
            ppnMode: 0, // 0: Exclude, 1: Include
            ppnRate: 11,

            get totalDPP() {
                if (!this.includePPN) return 0;
                const total = +this.totalHarga || 0;
                const rate = +this.ppnRate || 0;
                if (this.ppnMode === 1) { // Include
                    return (100 / (100 + rate)) * total;
                }
                return total; // Exclude
            },

            get ppnAmount() {
                if (!this.includePPN) return 0;
                const dpp = this.totalDPP;
                const rate = +this.ppnRate || 0;
                return Math.round(dpp * (rate / 100));
            },

            get grandTotal() {
                const total = +this.totalHarga || 0;
                if (!this.includePPN) return total;
                if (this.ppnMode === 1) return total; // Include: total already has PPN
                return total + this.ppnAmount; // Exclude: total + PPN
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

            formatQtyValue(value) {
                const num = Number(value);
                if (!Number.isFinite(num)) return '0,00';
                const hasMoreThanTwoDecimals = Math.abs((num * 100) - Math.round(num * 100)) > 0.000001;
                const digits = hasMoreThanTwoDecimals ? 4 : 2;
                return num.toLocaleString('id-ID', {
                    minimumFractionDigits: digits,
                    maximumFractionDigits: digits
                });
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

            formatStockLimit(code, qty, satuan, hideQtyLimitHint = false) {
                return '';
            },

            hydrateRowFromMeta(row, meta) {
                if (!meta) {
                    row.fitemname = '';
                    row.units = [];
                    row.fsatuan = '';
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

                if (row === this.draft) {
                    if (units.length > 1) {
                        populateDraftUnitSelect(units);
                    } else {
                        clearDraftUnitSelect();
                    }
                }
            },

            onCodeTypedRow(row) {
                this.hydrateRowFromMeta(row, this.productMeta(row.fprdcode));
            },

            isComplete(row) {
                return row.fprdcode && row.fitemname && row.fsatuan && Number(row.fqty) > 0;
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

            normalizeNoAcak(value) {
                const normalized = String(value ?? '').trim();
                return /^\d{3}$/.test(normalized) ? normalized : '';
            },

            generateUniqueNoAcak() {
                const used = new Set(this.savedItems.map(item => this.normalizeNoAcak(item.fnoacak)).filter(Boolean));
                let candidate = '';
                do {
                    candidate = Array.from({ length: 3 }, () => '123456789'[Math.floor(Math.random() * 9)]).join('');
                } while (used.has(candidate));

                return candidate;
            },

            addManyFromPR(header, items) {
                const existing = new Set(this.getCurrentItemKeys());
                let added = 0;

                items.forEach(src => {
                    const row = {
                        uid: cryptoRandom(),
                        fprdcode: src.fitemcode ?? '',
                        fitemname: src.fitemname ?? '',
                        fsatuan: src.fsatuan ?? '',
                        fnoacak: this.generateUniqueNoAcak(),
                        frefdtno: src.frefdtno ?? '',
                        fnouref: src.fnouref ?? '',
                        frefpr: src.frefpr ?? (header?.fsono ?? ''),
                        fprhid: src.fprhid ?? header?.fprhid ?? '',
                        fqty: (src.fqtysisa !== null && src.fqtysisa !== undefined && Number(src.fqtysisa) > 0) ?
                            Number(src.fqtysisa) :
                            ((src.fqtyremain !== null && src.fqtyremain !== undefined && Number(src.fqtyremain) > 0) ?
                                Number(src.fqtyremain) :
                                ((src.fqty !== null && src.fqty !== undefined && Number(src.fqty) > 0) ?
                                    Number(src.fqty) : 1)),
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
                    this.savedItems.push(row);
                    existing.add(key);
                    added++;
                });

                this.recalcTotals();
            },

            resetDraft() {
                this.draft = newRow();
                this.$nextTick(() => this.$refs.draftCode?.focus());
            },



            addIfComplete() {
                const r = this.draft;

                if (!this.isComplete(r)) {
                    if (!r.fprdcode) return this.$refs.draftCode?.focus();
                    if (!r.fitemname) return this.$refs.draftCode?.focus();
                    if (!r.fsatuan) return (r.units.length > 1 ? this.$refs.draftUnit?.focus() : this.$refs.draftCode
                        ?.focus());
                    if (!(Number(r.fqty) > 0)) return this.$refs.draftQty?.focus();
                    return;
                }

                this.recalc(r);

                const dupe = this.savedItems.find(it =>
                    it.fprdcode === r.fprdcode &&
                    it.fsatuan === r.fsatuan &&
                    (it.frefpr || '') === (r.frefpr || '')
                );

                if (dupe) {
                    this.showToast('Item sama sudah ada di daftar', 'warning');
                    return;
                }

                this.savedItems.push({
                    ...r,
                    fnoacak: this.normalizeNoAcak(r.fnoacak) || this.generateUniqueNoAcak(),
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
                if (this.draft.units.length > 1) this.$refs.draftUnit?.focus();
                else this.$refs.draftQty?.focus();
            },

            showDescModal: false,
            descTarget: 'draft',
            descSavedIndex: null,
            descValue: '',
            openDesc() {},
            closeDesc() {},
            applyDesc() {},

            itemKey(it) {
                return `${(it.fprdcode ?? '').toString().trim()}::${(it.frefdtno ?? '').toString().trim()}`;
            },

            getCurrentItemKeys() {
                return this.savedItems.map(it => this.itemKey(it));
            },

            labelOf(row) {
                return [row.fprdcode, row.fitemname].filter(Boolean).join(' — ');
            },

            syncDescList() {
                if (window.Alpine && Alpine.store('trsomt')) {
                    Alpine.store('trsomt').descList = this.savedItems
                        .map((it, i) => ({
                            uid: it.uid,
                            index: i + 1,
                            label: this.labelOf(it),
                            text: it.fdesc || ''
                        }))
                        .filter(x => x.text);
                }
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
                this.$watch('ppnMode', () => this.recalcTotals());
                this.$watch('ppnRate', () => this.recalcTotals());

                this.savedItems.forEach((item) => {
                    item.fnoacak = this.normalizeNoAcak(item.fnoacak) || this.generateUniqueNoAcak();
                    item.hideQtyLimitHint = !((item.frefdtno ?? '').toString().trim());
                    item.units = item.units || [];
                    if (typeof item.units === 'string') {
                        try {
                            const parsed = JSON.parse(item.units);
                            item.units = Array.isArray(parsed) ? parsed : [];
                        } catch (e) {
                            item.units = item.units.split(',').map(u => u.trim());
                        }
                    } else if (!Array.isArray(item.units)) {
                        item.units = [];
                    }

                    const meta = this.productMeta(item.fprdcode);
                    if (meta) {
                        if (meta.units && meta.units.length) {
                            item.units = [...new Set([...item.units, ...meta.units])];
                        } else if (item.fsatuan && !item.units.includes(item.fsatuan)) {
                            item.units.unshift(item.fsatuan);
                        }
                        if (meta.unit_ratios) {
                            item.unit_ratios = item.unit_ratios || meta.unit_ratios;
                        }
                    } else {
                        if (item.fsatuan && !item.units.includes(item.fsatuan)) {
                            item.units.unshift(item.fsatuan);
                        }
                    }
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
                    const apply = (row) => {
                        row.fprdcode = (product.fprdcode || '').toString();
                        row.hideQtyLimitHint = true;
                        row.fnoacak = this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak();

                        // Gunakan data dari modal sebaga primary, fallback ke PRODUCT_MAP
                        const meta = {
                            name: product.fprdname,
                            units: [product.fsatuankecil, product.fsatuanbesar, product.fsatuanbesar2]
                                .filter(Boolean),
                            stock: product.fqty || product.fminstock || 0
                        };

                        // Jika PRODUCT_MAP punya data lebih lengkap, timpa
                        const localMeta = this.productMeta(row.fprdcode);
                        if (localMeta) {
                            if (localMeta.name) meta.name = localMeta.name;
                            if (localMeta.units && localMeta.units.length) meta.units = localMeta.units;
                            if (localMeta.stock) meta.stock = localMeta.stock;
                        }

                        this.hydrateRowFromMeta(row, meta);
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
                this.draft.fnoacak = this.generateUniqueNoAcak();
                document.addEventListener('change', function(e) {
                    if (e.target && e.target.id === 'draftUnitSelect') {
                        self.draft.fsatuan = e.target.value;
                    }
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
                hideQtyLimitHint: false,
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

@include('components.transaction.salesorder-pr-modal-script')

<script>
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
    @include('components.transaction.browse-customer-script')
    @include('components.transaction.browse-salesman-script')
    @include('components.transaction.browse-product-script', ['showControls' => true, 'showPagination' => true, 'supportsForEdit' => true])

    <script>
        window.PRODUCT_MAP = @json($productMap ?? []);

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
