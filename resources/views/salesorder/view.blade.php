@extends('layouts.app')

@section('title', "Sales Order")

@section('content')
    @php
        $permissions = explode(',', session('user_restricted_permissions', ''));
        $canPrint = in_array('viewTr_poh', $permissions, true) || in_array('updateTr_poh', $permissions, true) || in_array('deleteTr_poh', $permissions, true) || in_array('createTr_poh', $permissions, true);
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
    </style>
    @php
        $currentCustomerCode = trim((string) old('fcustno', $salesorder->fcustno ?? ''));
        $currentCustomerName = trim((string) old('fcustomername', $salesorder->customer->fcustomername ?? ''));
        $hasCurrentCustomer = $currentCustomerCode !== '' && $customers->contains(function ($customer) use ($currentCustomerCode) {
            return trim((string) $customer->fcustomercode) === $currentCustomerCode;
        });
    @endphp

    <div>
        <div x-data="{ includePPN: {{ old('fincludeppn', $salesorder->fincludeppn ?? 0) ? 'true' : 'false' }}, ppnRate: 0, ppnAmount: 0, totalHarga: 100000 }" class="lg:col-span-5">
            <div class="bg-white rounded shadow p-6 md:p-8 max-w-[96rem] mx-auto">
                @if (!empty($approvalLockMessage))
                    <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        {{ $approvalLockMessage }}
                    </div>
                @endif
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
                                    class="w-full border rounded px-3 py-2 bg-gray-200" :disabled="autoCode" readonly
                                    :class="autoCode ? 'cursor-not-allowed text-gray-500' : ''">

                                <label class="inline-flex items-center select-none">
                                    <input class="bg-gray-200" type="checkbox" x-model="autoCode" disabled>
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
                                    disabled {{-- text-red-600 mengubah isi centang, border-red-400 mengubah bingkai --}}
                                    class="w-6 h-6 text-red-600 border-red-400 bg-gray-200 rounded cursor-pointer focus:ring-red-500"
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
                                            <option value="{{ $salesman->fsalesmancode }}" {{-- CEK DISINI: Bandingkan old input atau data dari database --}}
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

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium mb-1">Reff PO</label>
                            <input type="text" name="frefpo" disabled
                                value="{{ old('frefpo', $salesorder->frefpo) }}"
                                class="w-full border rounded px-3 py-2 bg-gray-200">
                        </div>

                        <div class="col-span-12 mt-4">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">

                                @php
                                    // Ambil alamat dari customer untuk pembanding
                                    $a1 = trim($salesorder->customer->fkirimaddress1 ?? '');
                                    $a2 = trim($salesorder->customer->fkirimaddress2 ?? '');
                                    $a3 = trim($salesorder->customer->fkirimaddress3 ?? '');
                                    $saved = trim($salesorder->falamatkirim ?? '');

                                    // Tentukan tab mana yang aktif berdasarkan kecocokan string
                                    $activeTab = 1;
                                    if ($saved !== '' && $saved === $a2) {
                                        $activeTab = 2;
                                    } elseif ($saved !== '' && $saved === $a3) {
                                        $activeTab = 3;
                                    }
                                @endphp

                                <div x-data="{ tab: {{ $activeTab }} }" class="flex flex-col gap-2">
                                    <div class="flex items-center gap-2">
                                        <label class="text-sm font-bold text-gray-700 mr-2">Kirim ke :</label>

                                        <div class="inline-flex rounded-md shadow-sm">
                                            <button type="button" x-show="tab === 1"
                                                class="px-4 py-1.5 text-xs font-semibold bg-blue-600 text-white border border-blue-600 rounded-md">
                                                Alamat 1
                                            </button>

                                            <button type="button" x-show="tab === 2"
                                                class="px-4 py-1.5 text-xs font-semibold bg-blue-600 text-white border border-blue-600 rounded-md">
                                                Alamat 2
                                            </button>

                                            <button type="button" x-show="tab === 3"
                                                class="px-4 py-1.5 text-xs font-semibold bg-blue-600 text-white border border-blue-600 rounded-md">
                                                Alamat 3
                                            </button>
                                        </div>
                                    </div>

                                    <div class="w-full">
                                        <div
                                            class="w-full p-2 text-sm border border-gray-300 rounded bg-gray-200 text-gray-700 min-h-[80px] whitespace-pre-line shadow-sm">
                                            {{ $saved ?: 'Alamat tidak ditemukan' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-col">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Keterangan</label>
                                    <textarea name="fket" class="w-full border rounded px-3 py-2 bg-gray-200" readonly
                                        placeholder="Keterangan isi di sini...">{{ old('fket', $salesorder->fket) }}</textarea>
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

                    <div x-data="salesOrderViewItemsTable()" x-init="init()" class="mt-6 space-y-2">

                        {{-- DETAIL ITEM (tabel input) --}}
                        <h3 class="text-base font-semibold text-gray-800">Detail Item</h3>

                        <div class="overflow-x-auto border rounded">
                            <table class="min-w-full text-sm balanced-detail-table" data-skip-auto-detail-style="true">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="p-2 text-left w-10">#</th>
                                        <th class="p-2 text-left w-52">Kode Produk</th>
                                        <th class="p-2 text-left w-[28rem]">Nama Produk</th>
                                        <th class="p-2 text-left w-32">Satuan</th>
                                        <th class="p-2 text-right w-28 whitespace-nowrap">Jumlah</th>
                                        <th class="p-2 text-right w-28 whitespace-nowrap">Jumlah SRJ</th>
                                        <th class="p-2 text-right w-28 whitespace-nowrap">@ Harga</th>
                                        <th class="p-2 text-right w-28 whitespace-nowrap">Disc. %</th>
                                        <th class="p-2 text-right w-32 whitespace-nowrap">Total Harga</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(row, i) in rows" :key="row.uid || `item-${i}`">
                                        <tr class="border-t border-b align-top">
                                            <td class="p-2" x-text="i + 1"></td>
                                            <td class="p-2 font-mono" x-text="row.fprdcode"></td>
                                            <td class="p-2 text-gray-800">
                                                <div class="flex w-full max-w-full">
                                                    <div class="min-w-0 flex-1 rounded-l border bg-gray-100 px-2 py-1 text-sm leading-5 text-gray-600 whitespace-normal break-words" x-text="row.fitemname"></div>
                                                    <button type="button" @click="openDesc(row)"
                                                        class="shrink-0 inline-flex items-center border border-l-0 rounded-r px-2 py-1 transition-colors"
                                                        :class="row.fdesc ? 'border-emerald-300 bg-emerald-50 text-emerald-600 hover:bg-emerald-100' : 'bg-white text-gray-500 hover:bg-gray-50'"
                                                        title="Deskripsi item">
                                                        <x-heroicon-o-document-text class="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="p-2" x-text="row.fsatuan"></td>
                                            <td class="p-2 text-right" x-text="formatQtyValue(row.fqty)"></td>
                                            <td class="p-2 text-right font-medium" x-text="formatQtyValue(row.fqtysrj)"></td>
                                            <td class="p-2 text-right" x-text="fmt(row.fprice)"></td>
                                            <td class="p-2 text-right" x-text="row.fdisc"></td>
                                            <td class="p-2">
                                                <input type="text"
                                                    class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm text-right"
                                                    :value="fmt(row.ftotal)" disabled>
                                            </td>
                                        </tr>
                                    </template>
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
                                            x-text="formatTransactionAmount(totalHarga)"></span>
                                    </div>
                                    <div class="flex items-center justify-between gap-6">
                                        <!-- Checkbox -->
                                        <div class="flex items-center">
                                            <input id="fapplyppn" type="checkbox" name="fapplyppn" x-model="includePPN"
                                                x-init="includePPN = {{ old('fapplyppn', $salesorder->fapplyppn ?? 0) ? 'true' : 'false' }}"
                                                value="1" class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                            <label for="fapplyppn" class="ml-2 text-sm font-medium text-gray-700">
                                                <span class="font-bold">PPN</span>
                                            </label>
                                        </div>

                                        <!-- Dropdown Include / Exclude (tengah) -->
                                        <div class="flex items-center gap-2">
                                            <select id="fincludeppn" name="fincludeppn" x-model.number="ppnMode"
                                                x-init="ppnMode = {{ old('fincludeppn', $salesorder->fincludeppn ?? 0) }}"
                                                :disabled="!includePPN"
                                                class="w-28 h-9 px-2 text-sm leading-tight border rounded transition-opacity appearance-none
                                                           disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed">
                                                <option value="0">Exclude</option>
                                                <option value="1">Include</option>
                                            </select>
                                        </div>

                                        <!-- Input Rate + Nominal (kanan) -->
                                        <div class="flex items-center gap-2">
                                            <input type="number" min="0" max="100" step="0.01"
                                                name="ppn_rate" x-model.number="ppnRate"
                                                x-init="ppnRate = {{ old('ppn_rate', $salesorder->fppnpersen ?? 11) }}"
                                                :disabled="!includePPN"
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
                                <input type="hidden" name="famountpajak" :value="ppnAmount">
                                <input type="hidden" name="famountso" :value="grandTotal">
                                <input type="hidden" name="fppnpersen" :value="ppnRate">
                            </div>
                        </div>

                        <div x-show="showDescModal" x-cloak
                            class="fixed inset-0 z-[95] flex items-center justify-center" x-transition.opacity>
                            <div class="absolute inset-0 bg-black/50" @click="closeDesc()"></div>

                            <div class="relative bg-white w-[92vw] max-w-lg rounded-2xl shadow-2xl overflow-hidden"
                                x-transition.scale>
                                <div class="px-5 py-4 border-b flex items-center">
                                    <x-heroicon-o-document-text class="w-6 h-6 text-blue-600 mr-2" />
                                    <h3 class="text-lg font-semibold text-gray-800">Deskripsi Item</h3>
                                </div>

                                <div class="px-5 py-4 space-y-2">
                                    <label class="block text-sm text-gray-700">Deskripsi</label>
                                    <textarea x-model="descValue" rows="5"
                                        class="w-full border rounded px-3 py-2 bg-gray-100 cursor-not-allowed text-gray-600"
                                        readonly></textarea>
                                </div>

                                <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
                                    <button type="button" @click="closeDesc()"
                                        class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">
                                        Tutup
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- MODAL ERROR: belum ada item --}}
                        <div x-show="showNoItems && rows.length === 0" x-cloak
                            class="fixed inset-0 z-[90] flex items-center justify-center" x-transition.opacity>
                            <div class="absolute inset-0 bg-black/50" @click="showNoItems=false"></div>

                            <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden"
                                x-transition.scale>
                                <div class="px-5 py-4 border-b flex items-center">
                                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-500 mr-2" />
                                    <h3 class="text-lg font-semibold text-gray-800">{{ "Tidak Ada Item" }}</h3>
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
                                        {{ "OK" }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <br>

                        @php $isPrinted = (int) ($salesorder->fprint ?? 0) === 1; @endphp
                        <div class="mt-6 flex justify-center space-x-4">
                            @if ($canPrint)
                                <a href="{{ route('salesorder.print', $salesorder->fsono) }}" target="_blank"
                                    class="{{ $isPrinted ? 'bg-gray-400 pointer-events-none cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700' }} text-white px-6 py-2 rounded flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m10 0v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5m10 0v5H7v-5">
                                        </path>
                                    </svg>
                                    Print
                                </a>
                            @endif
                            <button type="button" onclick="window.location.href='{{ route('salesorder.index') }}'"
                                class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                                <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                                Kembali
                            </button>
                        </div>

                        <x-transaction.browse-customer-modal />
                        <x-transaction.browse-salesman-modal />
                        <x-transaction.browse-product-modal show-controls="true" show-pagination="true" />
                    </div>
                </div>
            @endsection
            @push('styles')
                <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
            @endpush
            <x-transaction.datatables-length-styles :tables="['supplierTable', 'productTable']" />
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

                document.addEventListener('alpine:init', () => {
                    Alpine.store('prh', {
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
            <script>
                function salesOrderViewItemsTable() {
                    return {
                        showNoItems: false,
                        rows: [],
                        totalHarga: 0,
                        ppnRate: 11,
                        includePPN: false,
                        ppnMode: 0,
                        showDescModal: false,
                        descValue: '',
                        _descTarget: null,

                        get totalDPP() {
                            const total = +this.totalHarga || 0;
                            if (!this.includePPN) return total;
                            const rate = +this.ppnRate || 0;
                            if (this.ppnMode === 1) {
                                return (100 / (100 + rate)) * total;
                            }
                            return total;
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
                            if (this.ppnMode === 1) return total;
                            return total + this.ppnAmount;
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

                        parseDiscount(discStr) {
                            if (!discStr && discStr !== 0) return 0;
                            if (typeof discStr === 'number') return discStr;

                            const str = String(discStr).trim();
                            if (!str) return 0;
                            if (!isNaN(str)) return parseFloat(str) || 0;

                            try {
                                const cleaned = str.replace(/\s/g, '');
                                if (!/^[\d+\-*/.()]+$/.test(cleaned)) return 0;
                                const result = new Function('return ' + cleaned)();
                                return isFinite(result) ? Math.min(100, Math.max(0, result)) : 0;
                            } catch (e) {
                                console.warn('Invalid discount format:', discStr);
                                return 0;
                            }
                        },

                        recalc(row) {
                            row.fqty = Math.max(0, +row.fqty || 0);
                            row.fqtysrj = Math.max(0, +row.fqtysrj || 0);
                            row.fterima = Math.max(0, +row.fterima || 0);
                            row.fprice = Math.max(0, +row.fprice || 0);

                            const discPercent = this.parseDiscount(row.fdisc);
                            const subtotal = row.fqty * row.fprice;
                            const discAmount = subtotal * (discPercent / 100);
                            row.ftotal = +(subtotal - discAmount).toFixed(2);
                        },

                        recalcTotals() {
                            this.totalHarga = this.rows.reduce((sum, item) => sum + (+item.ftotal || 0), 0);
                        },

                        productMeta(code) {
                            const key = (code || '').trim();
                            return window.PRODUCT_MAP?.[key] || null;
                        },

                        hydrateRowFromMeta(row, meta) {
                            if (!meta) {
                                if (!row.fitemname) row.fitemname = '';
                                if (!Array.isArray(row.units)) row.units = [];
                                if (!row.fsatuan) row.fsatuan = '';
                                return;
                            }

                            row.fitemname = row.fitemname || meta.name || '';
                            const units = [...new Set((meta.units || []).map((u) => (u ?? '').toString().trim()).filter(Boolean))];
                            row.units = units;
                            if (!units.includes(row.fsatuan)) {
                                row.fsatuan = row.fsatuan || units[0] || '';
                            }
                        },

                        normalizeRow(item = {}, index = 0) {
                            const row = {
                                ...this.newRow(),
                                ...(item || {}),
                                uid: item?.uid || `view-row-${index}`
                            };

                            if (typeof row.units === 'string') {
                                try {
                                    const parsed = JSON.parse(row.units);
                                    row.units = Array.isArray(parsed) ? parsed : [];
                                } catch (e) {
                                    row.units = row.units.split(',').map((u) => u.trim()).filter(Boolean);
                                }
                            } else if (!Array.isArray(row.units)) {
                                row.units = [];
                            }

                            this.hydrateRowFromMeta(row, this.productMeta(row.fprdcode));
                            if (row.fsatuan && !row.units.includes(row.fsatuan)) {
                                row.units.unshift(row.fsatuan);
                            }
                            this.recalc(row);
                            return row;
                        },

                        restoreRows(items = []) {
                            this.rows = Array.isArray(items)
                                ? items.map((item, index) => this.normalizeRow(item, index))
                                : [];
                            this.recalcTotals();
                        },

                        openDesc(targetRow) {
                            this._descTarget = targetRow;
                            this.descValue = targetRow?.fdesc || '';
                            this.showDescModal = true;
                        },

                        closeDesc() {
                            this.showDescModal = false;
                            this._descTarget = null;
                            this.descValue = '';
                        },

                        newRow() {
                            return {
                                uid: null,
                                fprdcode: '',
                                fitemname: '',
                                units: [],
                                fsatuan: '',
                                frefdtno: '',
                                fnouref: '',
                                frefpr: '',
                                fqty: 0,
                                fqtysrj: 0,
                                fterima: 0,
                                fprice: 0,
                                fdisc: 0,
                                ftotal: 0,
                                fdesc: '',
                                fketdt: '',
                            };
                        },

                        init() {
                            this.$watch('includePPN', () => this.recalcTotals());
                            this.$watch('ppnMode', () => this.recalcTotals());
                            this.$watch('ppnRate', () => this.recalcTotals());
                            this.restoreRows(@json($savedItems ?? []));
                        },
                    };
                }

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

            @include('components.transaction.browse-customer-script')
            @include('components.transaction.browse-salesman-script')
            @include('components.transaction.browse-product-script', ['showControls' => true, 'showPagination' => true, 'supportsForEdit' => true])

            @push('scripts')
                <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
                <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
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

