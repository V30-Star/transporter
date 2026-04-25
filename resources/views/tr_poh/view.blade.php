@extends('layouts.app')

@section('title', 'Detail Order Pembelian')

@section('content')
    <style>
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

    <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1600px] w-full mx-auto" x-data="viewForm()"
        x-init="init()">

        {{-- ================================================================
             HEADER — semua readonly/disabled
             ================================================================ --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

            {{-- Cabang --}}
            <div class="lg:col-span-4">
                <label class="block text-sm font-medium">Cabang</label>
                <input type="text" disabled value="{{ $fcabang }}"
                    class="w-full border rounded px-3 py-2 bg-gray-100 cursor-not-allowed text-gray-600">
            </div>

            {{-- PO# --}}
            <div class="lg:col-span-4">
                <label class="block text-sm font-medium mb-1">PO#</label>
                <input type="text" disabled value="{{ $tr_poh->fpono }}"
                    class="w-full border rounded px-3 py-2 bg-gray-100 cursor-not-allowed text-gray-600">
            </div>

            {{-- Supplier --}}
            <div class="lg:col-span-4">
                <label class="block text-sm font-medium mb-1">Supplier</label>
                <select disabled class="w-full border rounded px-3 py-2 bg-gray-100 text-gray-600 cursor-not-allowed">
                    <option value=""></option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->fsuppliercode }}"
                            {{ $tr_poh->fsupplier == $supplier->fsuppliercode ? 'selected' : '' }}>
                            {{ $supplier->fsuppliername }} ({{ $supplier->fsuppliercode }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Tanggal --}}
            <div class="lg:col-span-4">
                <label class="block text-sm font-medium">Tanggal</label>
                <input type="date" disabled value="{{ substr($tr_poh->fpodate ?? '', 0, 10) }}"
                    class="w-full border rounded px-3 py-2 bg-gray-100 cursor-not-allowed text-gray-600">
            </div>

            {{-- Tgl. Kirim --}}
            <div class="lg:col-span-4">
                <label class="block text-sm font-medium">Tgl. Kirim</label>
                <input type="date" disabled value="{{ substr($tr_poh->fkirimdate ?? '', 0, 10) }}"
                    class="w-full border rounded px-3 py-2 bg-gray-100 cursor-not-allowed text-gray-600">
            </div>

            {{-- Tempo --}}
            <div class="lg:col-span-4">
                <label class="block text-sm font-medium mb-1">Tempo</label>
                <div class="flex items-center">
                    <input type="number" disabled value="{{ $tr_poh->ftempohr ?? 0 }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 cursor-not-allowed text-gray-600">
                    <span class="ml-2 text-sm text-gray-600">Hari</span>
                </div>
            </div>

            {{-- Currency --}}
            <div class="lg:col-span-4">
                <label class="block text-sm font-medium">Currency</label>
                <input type="text" disabled value="{{ $currentCurrency->fcurrname ?? ($tr_poh->fcurrency ?? 'IDR') }}"
                    class="w-full border rounded px-3 py-2 bg-gray-100 cursor-not-allowed text-gray-600">
            </div>

            {{-- Rate --}}
            <div class="lg:col-span-4">
                <label class="block text-sm font-medium">Rate</label>
                <input type="text" disabled value="{{ number_format($tr_poh->frate ?? 1, 2, ',', '.') }}"
                    class="w-full border rounded px-3 py-2 bg-gray-100 cursor-not-allowed text-gray-600">
            </div>

            {{-- Keterangan --}}
            <div class="lg:col-span-12">
                <label class="block text-sm font-medium">Keterangan</label>
                <textarea disabled rows="3" class="w-full border rounded px-3 py-2 bg-gray-100 cursor-not-allowed text-gray-600">{{ $tr_poh->fket }}</textarea>
            </div>
        </div>

        {{-- ================================================================
             DETAIL ITEM — pure readonly
             ================================================================ --}}
        <div class="mt-6 space-y-2">
            <h3 class="text-base font-semibold text-gray-800">Detail Item</h3>

            <div class="overflow-auto border rounded">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-2 text-left w-10">#</th>
                            <th class="p-2 text-left w-44">Kode Produk</th>
                            <th class="p-2 text-left">Nama Produk</th>
                            <th class="p-2 text-left w-28">Satuan</th>
                            <th class="p-2 text-left w-36">Ref.PR#</th>
                            <th class="p-2 text-right w-24 whitespace-nowrap">Qty</th>
                            <th class="p-2 text-right w-32 whitespace-nowrap">Qty Terima</th>
                            <th class="p-2 text-right w-32 whitespace-nowrap">@ Harga</th>
                            <th class="p-2 text-right w-24 whitespace-nowrap">Disc. %</th>
                            <th class="p-2 text-right w-36 whitespace-nowrap">Total Harga</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(it, i) in savedItems" :key="it.uid">
                            <tr class="border-t align-top hover:bg-gray-50">
                                <td class="p-2 text-gray-500" x-text="i + 1"></td>
                                <td class="p-2 font-mono text-sm" x-text="it.fitemcode"></td>
                                <td class="p-2">
                                    <div class="text-sm text-gray-800" x-text="it.fitemname"></div>
                                    <div x-show="it.fdesc" class="mt-1 text-xs">
                                        <span
                                            class="inline-block px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 mr-1">Deskripsi</span>
                                        <span class="text-gray-600" x-text="it.fdesc"></span>
                                    </div>
                                </td>
                                <td class="p-2 text-sm" x-text="it.fsatuan || '-'"></td>
                                <td class="p-2 text-sm text-gray-600" x-text="it.fprno || it.frefdtno || '-'"></td>
                                <td class="p-2 text-right text-sm">
                                    <div x-text="it.fqty"></div>
                                </td>
                                <td class="p-2 text-right text-sm">
                                    <div x-text="it.fqtyterima ?? 0"></div>
                                </td>
                                <td class="p-2 text-right text-sm" x-text="fmtCurr(it.fprice)"></td>
                                <td class="p-2 text-right text-sm" x-text="it.fdisc"></td>
                                <td class="p-2 text-right text-sm font-medium" x-text="fmtCurr(it.ftotal)"></td>
                            </tr>
                        </template>

                        {{-- Empty state --}}
                        <template x-if="savedItems.length === 0">
                            <tr>
                                <td colspan="10" class="p-6 text-center text-gray-400 text-sm">Tidak ada item</td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Panel Totals --}}
            <div class="mt-3 flex justify-end">
                <div class="w-[480px] shrink-0">
                    <div class="rounded-lg border bg-gray-50 p-3 space-y-2 text-sm">

                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Total Harga</span>
                            <span class="font-medium" x-text="fmtCurr(totalHarga)"></span>
                        </div>

                        <div class="flex items-center gap-2">
                            <label class="flex items-center gap-1.5 select-none">
                                <input type="checkbox" disabled x-model="includePPN"
                                    class="h-4 w-4 text-blue-600 border-gray-300 rounded cursor-not-allowed">
                                <span class="font-bold">PPN</span>
                            </label>
                            <input type="text" disabled :value="ppnMode === 1 ? 'Include' : 'Exclude'"
                                class="w-28 h-8 px-2 text-xs border rounded bg-gray-100 cursor-not-allowed text-gray-600">
                            <input type="number" disabled x-model.number="ppnRate"
                                class="w-16 h-8 px-2 text-xs text-right border rounded bg-gray-100 cursor-not-allowed text-gray-600">
                            <span class="text-xs text-gray-500">%</span>
                            <span class="flex-1"></span>
                            <span class="font-medium text-xs" x-text="fmtCurr(ppnNominal)"></span>
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

                        <div class="flex items-center justify-between bg-blue-50 rounded px-2 py-1">
                            <span class="font-semibold text-gray-800">Grand Total (RP)</span>
                            <span class="font-bold text-emerald-700" x-text="rupiah(grandTotalRp)"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @php
            $canApproval = in_array('approvalpr', explode(',', session('user_restricted_permissions', '')));
        @endphp

        {{-- APPROVAL --}}
        @if ($canApproval)
            <div class="flex justify-center items-center space-x-2 mt-6">
                <label class="block text-sm font-medium">Approval</label>
                <label class="switch" style="pointer-events:none; opacity: 0.8;">
                    <input type="checkbox" disabled {{ $tr_poh->fapproval ?? 0 ? 'checked' : '' }}>
                    <span class="slider"></span>
                </label>
            </div>
        @endif

        {{-- ACTIONS --}}
        <div class="mt-8 flex justify-center gap-4">
            <a href="{{ route('tr_poh.print', $tr_poh->fpono) }}" target="_blank"
                class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m10 0v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5m10 0v5H7v-5">
                    </path>
                </svg>
                Print
            </a>
            <button type="button" onclick="window.location.href='{{ route('tr_poh.index') }}'"
                class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                Kembali
            </button>
        </div>

    </div>

@endsection

<style>
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
</style>

<script>
    window.PRODUCT_MAP = @json($productMap ?? []);
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

    function viewForm() {
        return {
            savedItems: @json($savedItems ?? []),
            productMeta(code) {
                return window.PRODUCT_MAP[code] || null;
            },
            selectedCurrCode: '{{ $currentCurrency->fcurrcode ?? 'IDR' }}',
            rateValue: {{ $tr_poh->frate ?? ($currentCurrency->frate ?? 1) }},

            includePPN: {{ (int) old('fapplyppn', $tr_poh->fapplyppn ?? 0) === 1 ? 'true' : 'false' }},
            ppnMode: {{ $tr_poh->fincludeppn ?? 0 }},
            ppnRate: {{ $tr_poh->fppnpersen ?? 11 }},

            get totalHarga() {
                return this.savedItems.reduce((s, it) => s + (it.ftotal || 0), 0);
            },
            get ppnNominal() {
                if (!this.includePPN) return 0;
                const total = this.totalHarga;
                const rate = +this.ppnRate || 0;
                if (this.ppnMode === 1) return Math.round(total * rate / (100 + rate));
                return Math.round(total * rate / 100);
            },
            get grandTotal() {
                if (!this.includePPN) return this.totalHarga;
                if (this.ppnMode === 1) return this.totalHarga;
                return this.totalHarga + this.ppnNominal;
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
                if (!isFinite(v)) return 'Rp -';
                return 'Rp ' + v.toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            },

            init() {
                // Hydrate uid jika tidak ada
                this.savedItems = this.savedItems.map((it, i) => {
                    if (!it.uid) it.uid = 'view_' + i;
                    if (!it.fprno) it.fprno = it.frefdtno || '';
                    return it;
                });

                // Sync currency code dari CURRENCY_MAP
                const currId = {{ $currentCurrency->fcurrid ?? 'null' }};
                if (currId && window.CURRENCY_MAP[currId]) {
                    this.selectedCurrCode = window.CURRENCY_MAP[currId].code;
                }
            }
        };
    }
</script>
