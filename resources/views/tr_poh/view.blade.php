@extends('layouts.app')

@section('title', 'Detail Order Pembelian')

@section('content')
    @php
        $permissions = explode(',', session('user_restricted_permissions', ''));
        $canPrint = in_array('viewTr_poh', $permissions, true) || in_array('updateTr_poh', $permissions, true) || in_array('deleteTr_poh', $permissions, true) || in_array('createTr_poh', $permissions, true);
    @endphp
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

    <div class="bg-white rounded shadow p-6 md:p-8 max-w-[96rem] mx-auto" x-data="viewForm()"
        x-init="init()">
        @if (!empty($approvalLockMessage))
            <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                {{ $approvalLockMessage }}
            </div>
        @endif

        {{-- ================================================================
             HEADER â€” semua readonly/disabled
             ================================================================ --}}
        @include('tr_poh._form', [
            'mode' => 'view',
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'suppliers' => $suppliers,
            'currencies' => $currencies,
            'currentCurrency' => $currentCurrency,
            'tr_poh' => $tr_poh,
        ])

        @php
            $canApproval = in_array('approvePO', explode(',', session('user_restricted_permissions', '')));
            $isPrinted = (int) ($tr_poh->fprint ?? 0) === 1;
        @endphp

        @if ($canApproval)
            <div class="flex justify-center items-center space-x-2 mt-6">
                <label class="block text-sm font-medium">Status Persetujuan</label>
                <label class="switch" style="pointer-events:none; opacity: 0.8;">
                    <input type="checkbox" disabled {{ \App\Support\ApprovalState::isApprovedRecord($tr_poh) ? 'checked' : '' }}>
                    <span class="slider"></span>
                </label>
            </div>
        @endif

        {{-- ACTIONS --}}
        <div class="mt-8 flex justify-center gap-4">
            @if ($canPrint)
                <a href="{{ route('tr_poh.print', $tr_poh->fpono) }}" target="_blank"
                    class="{{ $isPrinted ? 'bg-gray-400 pointer-events-none cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700' }} text-white px-6 py-2 rounded flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m10 0v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5m10 0v5H7v-5">
                        </path>
                    </svg>
                    Print
                </a>
            @endif
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
            showDescModal: false,
            descValue: '',
            _descTarget: null,
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
            formatQtyValue(value) {
                const num = Number(value);
                if (!Number.isFinite(num)) return '0,00';
                return num.toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            },
            openDesc(targetRow) {
                this._descTarget = targetRow;
                this.descValue = targetRow?.fdesc || '';
                this.showDescModal = true;
            },
            closeDesc() {
                this.showDescModal = false;
                this._descTarget = null;
            },
            applyDesc() {
                this.closeDesc();
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
