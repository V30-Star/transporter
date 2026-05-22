@extends('layouts.app')

@section('title', 'Pelunasan Customer - New')

@section('content')
    @php
        $oldDetails = old('details', []);
        $initialDetailRows = collect(is_array($oldDetails) && count($oldDetails) > 0 ? $oldDetails : [[]])
            ->values()
            ->map(function ($detail, $index) {
                return [
                    'uid' => 'pc-' . $index . '-' . substr(md5((string) $index), 0, 8),
                    'frefno' => trim((string) ($detail['frefno'] ?? '')),
                    'fnilai_nota' => (float) ($detail['fnilai_nota'] ?? 0),
                    'fsisa_piutang' => (float) ($detail['fsisa_piutang'] ?? 0),
                    'fdiscpersen' => (float) ($detail['fdiscpersen'] ?? 0),
                    'fdiscount' => (float) ($detail['fdiscount'] ?? 0),
                    'fkasdtvalue' => (float) ($detail['fkasdtvalue'] ?? 0),
                ];
            })
            ->all();

        $selectedCustomerCode = trim((string) old('fcustomer', $selectedCustomer->fcustomercode ?? ''));
        $selectedCustomerName = trim((string) ($selectedCustomer->fcustomername ?? ''));
        $selectedCustomerTempo = (int) old('fcustomer_tempo', $selectedCustomer->ftempo ?? 0);
        $selectedAccountCode = trim((string) old('faccountheader', $selectedAccount->faccount ?? ''));
        $selectedAccountName = trim((string) ($selectedAccount->faccname ?? ''));
        $selectedCustomerLabel = $selectedCustomerCode !== ''
            ? trim($selectedCustomerName . ' (' . $selectedCustomerCode . ')')
            : '';
    @endphp

    <div class="bg-white rounded shadow p-6 md:p-8 max-w-[96rem] mx-auto">
        <form action="{{ route('pelunasancustomer.store') }}" method="POST" class="space-y-6"
            data-form-draft="true" data-draft-key="pelunasancustomer:create"
            x-data="pelunasanCustomerForm(@js($initialDetailRows), @js($selectedCustomerTempo))" x-init="init()">
            @csrf

            <input type="hidden" name="fbranchcode" value="{{ old('fbranchcode', $currentBranchCode) }}">
            <input type="hidden" name="fcustomer_tempo" x-model="customerTempo">

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                <div class="lg:col-span-3">
                    <label class="block text-sm font-bold mb-1">{{ 'No. Voucher' }}</label>
                    <input type="text" name="fkasmtno" value="{{ old('fkasmtno', $voucherNo) }}"
                        class="w-full border rounded px-3 py-2 @error('fkasmtno') border-red-500 @enderror"
                        placeholder="Kosongkan untuk auto number">
                    @error('fkasmtno')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="lg:col-span-3">
                    <label class="block text-sm font-bold mb-1">{{ 'No.Giro/Cek' }}</label>
                    <input type="text" name="fnogiro" value="{{ old('fnogiro') }}"
                        class="w-full border rounded px-3 py-2 @error('fnogiro') border-red-500 @enderror">
                    @error('fnogiro')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="lg:col-span-3">
                    <label class="block text-sm font-bold mb-1">{{ 'Tanggal' }}</label>
                    <input type="date" name="fkasmtdate" x-model="transactionDate"
                        value="{{ old('fkasmtdate', $transactionDate) }}"
                        class="w-full border rounded px-3 py-2 @error('fkasmtdate') border-red-500 @enderror">
                    @error('fkasmtdate')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="lg:col-span-3">
                    <label class="block text-sm font-bold mb-1">{{ 'Giro Mundur' }}</label>
                    <label class="inline-flex items-center gap-2 h-10 px-3 border rounded w-full">
                        <input type="checkbox" x-model="isGiroMundur">
                        <span>{{ 'Aktifkan giro mundur' }}</span>
                    </label>
                    <input type="hidden" name="fgiromundur" :value="isGiroMundur ? '1' : '0'">
                </div>

                <div class="lg:col-span-4">
                    <label class="block text-sm font-bold mb-1">{{ 'Customer' }}</label>
                    <div class="flex">
                        <div class="relative flex-1">
                            <select id="modal_filter_customer_id" name="filter_customer_id"
                                class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
                                disabled>
                                <option value="{{ $selectedCustomerCode }}">{{ $selectedCustomerLabel }}</option>
                            </select>
                            <div class="absolute inset-0" role="button" aria-label="Browse Customer"
                                @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))"></div>
                        </div>
                        <input type="hidden" name="fcustomer" id="customerCodeHidden" x-model="customerCode">
                        <button type="button" @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))"
                            class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r" title="Browse Customer">
                            <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                        </button>
                    </div>
                    @error('fcustomer')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="lg:col-span-4">
                    <label class="block text-sm font-bold mb-1">{{ 'Account' }}</label>
                    <div class="flex">
                        <input type="text" x-model="accountLabel"
                            class="w-full border rounded-l px-3 py-2 bg-gray-100 cursor-not-allowed" readonly>
                        <input type="hidden" name="faccountheader" x-model="accountCode">
                        <button type="button" @click="window.dispatchEvent(new CustomEvent('account-browse-open'))"
                            class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r" title="Browse Account">
                            <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                        </button>
                    </div>
                    @error('faccountheader')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="lg:col-span-4">
                    <label class="block text-sm font-bold mb-1">{{ 'Tgl.Jatuh Tempo' }}</label>
                    <input type="date" name="ftgljatuhtempo" x-model="dueDate"
                        class="w-full border rounded px-3 py-2 @error('ftgljatuhtempo') border-red-500 @enderror"
                        :readonly="!isGiroMundur" :disabled="!isGiroMundur"
                        :class="!isGiroMundur ? 'bg-gray-100 cursor-not-allowed' : 'bg-white'">
                    @error('ftgljatuhtempo')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="lg:col-span-3">
                    <label class="block text-sm font-bold mb-1">{{ 'Add Nota' }}</label>
                    <button type="button" @click="openNotaModal()"
                        class="w-full h-10 inline-flex items-center justify-center gap-2 border rounded px-3 py-2 bg-white hover:bg-gray-50">
                        <x-heroicon-o-arrow-down-tray class="w-5 h-5" />
                        <span>{{ 'Add Nota' }}</span>
                    </button>
                </div>

                <div class="lg:col-span-9">
                    <label class="block text-sm font-bold mb-1">{{ 'Keterangan' }}</label>
                    <input type="text" name="fket" value="{{ old('fket') }}"
                        class="w-full border rounded px-3 py-2 @error('fket') border-red-500 @enderror">
                    @error('fket')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-base font-semibold text-gray-800">{{ 'Detail Item' }}</h3>
                </div>

                <div class="overflow-auto border rounded-lg">
                    <table class="min-w-full text-sm balanced-detail-table">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border px-2 py-2 w-12">{{ 'No' }}</th>
                                <th class="border px-2 py-2 min-w-[12rem]">{{ 'No.Nota' }}</th>
                                <th class="border px-2 py-2 min-w-[10rem] text-right">{{ 'Nilai Nota' }}</th>
                                <th class="border px-2 py-2 min-w-[10rem] text-right">{{ 'Sisa Piutang' }}</th>
                                <th class="border px-2 py-2 min-w-[8rem] text-right">{{ 'Disc%' }}</th>
                                <th class="border px-2 py-2 min-w-[10rem] text-right">{{ 'Discount' }}</th>
                                <th class="border px-2 py-2 min-w-[10rem] text-right">{{ 'Total Bayar' }}</th>
                                <th class="border px-2 py-2 w-28 text-center">{{ 'Aksi' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, index) in rows" :key="row.uid">
                                <tr>
                                    <td class="border px-2 py-2 text-center" x-text="index + 1"></td>
                                    <td class="border px-2 py-2">
                                        <input type="text" :name="`details[${index}][frefno]`" x-model="row.frefno"
                                            class="w-full border rounded px-2 py-1.5">
                                        <input type="hidden" :name="`details[${index}][freftype]`" :value="row.freftype || 'INV'">
                                    </td>
                                    <td class="border px-2 py-2">
                                        <input type="number" min="0" step="0.01" :name="`details[${index}][fnilai_nota]`"
                                            x-model="row.fnilai_nota" @input="recalcTotals()"
                                            class="w-full border rounded px-2 py-1.5 text-right">
                                    </td>
                                    <td class="border px-2 py-2">
                                        <input type="number" min="0" step="0.01"
                                            :name="`details[${index}][fsisa_piutang]`" x-model="row.fsisa_piutang"
                                            @input="recalcTotals()" class="w-full border rounded px-2 py-1.5 text-right">
                                    </td>
                                    <td class="border px-2 py-2">
                                        <input type="number" min="0" max="100" step="0.01"
                                            :name="`details[${index}][fdiscpersen]`" x-model="row.fdiscpersen"
                                            @input="syncDiscountFromPercent(row)" class="w-full border rounded px-2 py-1.5 text-right">
                                    </td>
                                    <td class="border px-2 py-2">
                                        <input type="number" min="0" step="0.01"
                                            :name="`details[${index}][fdiscount]`" x-model="row.fdiscount"
                                            @input="syncTotalBayar(row)" class="w-full border rounded px-2 py-1.5 text-right">
                                    </td>
                                    <td class="border px-2 py-2">
                                        <input type="number" min="0" step="0.01"
                                            :name="`details[${index}][fkasdtvalue]`" x-model="row.fkasdtvalue"
                                            @input="recalcTotals()" class="w-full border rounded px-2 py-1.5 text-right">
                                    </td>
                                    <td class="border px-2 py-2">
                                        <div class="flex items-center justify-center gap-2">
                                            <button type="button" @click="addRow()"
                                                x-show="index === rows.length - 1"
                                                class="inline-flex items-center justify-center w-9 h-9 rounded bg-blue-600 text-white hover:bg-blue-700">
                                                <x-heroicon-o-plus class="w-4 h-4" />
                                            </button>
                                            <button type="button" @click="removeRow(index)"
                                                class="inline-flex items-center justify-center w-9 h-9 rounded bg-red-600 text-white hover:bg-red-700"
                                                :disabled="rows.length === 1">
                                                <x-heroicon-o-trash class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <div class="lg:col-span-8"></div>

                <div class="lg:col-span-4">
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 space-y-3">
                        <div class="flex items-center justify-between gap-4">
                            <label class="text-sm font-semibold text-gray-700">{{ 'Biaya Admin Bank' }}</label>
                            <div class="w-40">
                                <input type="number" min="0" step="0.01" name="fbiayaadminbank" x-model="bankAdminFee"
                                    @input="recalcTotals()"
                                    class="w-full border rounded px-3 py-2 text-right @error('fbiayaadminbank') border-red-500 @enderror">
                            </div>
                        </div>
                        @error('fbiayaadminbank')
                            <p class="text-red-600 text-sm">{{ $message }}</p>
                        @enderror

                        <div class="flex items-center justify-between gap-4 border-t pt-3">
                            <span class="text-sm font-semibold text-gray-800">{{ 'Total Penerimaan' }}</span>
                            <input type="text" x-model="totalPenerimaanDisplay"
                                class="w-40 border rounded px-3 py-2 bg-gray-100 text-right font-semibold cursor-not-allowed"
                                readonly>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('pelunasancustomer.index') }}"
                    class="inline-flex items-center px-4 py-2 rounded border border-gray-300 bg-white hover:bg-gray-50">
                    {{ 'Kembali' }}
                </a>
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">
                    {{ 'Simpan' }}
                </button>
            </div>
        </form>
    </div>

    @include('components.transaction.browse-customer-modal')
    @include('components.transaction.browse-customer-script')
    <x-transaction.browse-account-modal />
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.6/css/dataTables.dataTables.min.css">
    <style>
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, .2);
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/2.1.6/js/dataTables.min.js"></script>
    <script>
        function pelunasanCustomerForm(initialRows, initialTempo) {
            return {
                rows: [],
                customerCode: @js($selectedCustomerCode),
                customerTempo: Number(initialTempo || 0),
                accountCode: @js($selectedAccountCode),
                accountLabel: @js($selectedAccountCode !== '' ? trim($selectedAccountCode . ' - ' . $selectedAccountName) : ''),
                transactionDate: @js(old('fkasmtdate', $transactionDate)),
                dueDate: @js(old('ftgljatuhtempo', '')),
                isGiroMundur: @js(old('fgiromundur', '0') === '1'),
                bankAdminFee: @js((float) old('fbiayaadminbank', 0)),
                totalPenerimaanDisplay: '0.00',

                init() {
                    this.rows = (Array.isArray(initialRows) && initialRows.length ? initialRows : [this.emptyRow()])
                        .map((row, index) => this.normalizeRow(row, index));

                    this.recalcTotals();

                    window.addEventListener('customer-selected', (event) => {
                        const detail = event.detail || {};
                        const code = String(detail.fcustomercode || '').trim();
                        const name = String(detail.fcustomername || '').trim();

                        this.customerCode = code;
                        this.customerTempo = Number(detail.ftempo || 0);

                        if (this.isGiroMundur) {
                            this.syncDueDate();
                        }

                        const select = document.getElementById('modal_filter_customer_id');
                        if (select) {
                            const label = name ? `${name} (${code})` : code;
                            let option = Array.from(select.options).find((item) => item.value === code);
                            if (!option) {
                                option = new Option(label, code, true, true);
                                select.add(option);
                            } else {
                                option.text = label;
                                option.selected = true;
                            }
                        }
                    });

                    window.addEventListener('account-picked', (event) => {
                        const detail = event.detail || {};
                        const code = String(detail.faccount || '').trim();
                        const name = String(detail.faccname || '').trim();
                        this.accountCode = code;
                        this.accountLabel = code && name ? `${code} - ${name}` : code;
                    });

                    this.$watch('transactionDate', () => {
                        if (this.isGiroMundur) {
                            this.syncDueDate();
                        }
                    });

                    this.$watch('isGiroMundur', (value) => {
                        if (value) {
                            this.syncDueDate();
                            return;
                        }
                        this.dueDate = '';
                    });
                },

                emptyRow() {
                    return {
                        uid: this.makeUid(),
                        frefno: '',
                        fnilai_nota: 0,
                        fsisa_piutang: 0,
                        fdiscpersen: 0,
                        fdiscount: 0,
                        fkasdtvalue: 0,
                    };
                },

                normalizeRow(row = {}, index = 0) {
                    return {
                        uid: row.uid || `pc-row-${index}-${this.makeUid()}`,
                        frefno: String(row.frefno || '').trim(),
                        fnilai_nota: this.toNumber(row.fnilai_nota),
                        fsisa_piutang: this.toNumber(row.fsisa_piutang),
                        fdiscpersen: this.toNumber(row.fdiscpersen),
                        fdiscount: this.toNumber(row.fdiscount),
                        fkasdtvalue: this.toNumber(row.fkasdtvalue),
                    };
                },

                makeUid() {
                    return `${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
                },

                toNumber(value) {
                    const number = Number(value);
                    return Number.isFinite(number) ? number : 0;
                },

                formatNumber(value) {
                    return this.toNumber(value).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                },

                addRow() {
                    this.rows.push(this.emptyRow());
                },

                removeRow(index) {
                    if (this.rows.length === 1) {
                        this.rows = [this.emptyRow()];
                    } else {
                        this.rows.splice(index, 1);
                    }

                    this.recalcTotals();
                },

                syncDiscountFromPercent(row) {
                    const percent = this.toNumber(row.fdiscpersen);
                    const basis = this.toNumber(row.fsisa_piutang || row.fnilai_nota);
                    row.fdiscount = basis * percent / 100;
                    row.fkasdtvalue = Math.max(basis - row.fdiscount, 0);
                    this.recalcTotals();
                },

                syncTotalBayar(row) {
                    const basis = this.toNumber(row.fsisa_piutang || row.fnilai_nota);
                    const discount = this.toNumber(row.fdiscount);
                    row.fkasdtvalue = Math.max(basis - discount, 0);
                    this.recalcTotals();
                },

                recalcTotals() {
                    const totalBayar = this.rows.reduce((sum, row) => sum + this.toNumber(row.fkasdtvalue), 0);
                    this.totalPenerimaanDisplay = this.formatNumber(totalBayar);
                },

                syncDueDate() {
                    if (!this.transactionDate) {
                        return;
                    }

                    const baseDate = new Date(`${this.transactionDate}T00:00:00`);
                    if (Number.isNaN(baseDate.getTime())) {
                        return;
                    }

                    baseDate.setDate(baseDate.getDate() + this.customerTempo);
                    const year = baseDate.getFullYear();
                    const month = String(baseDate.getMonth() + 1).padStart(2, '0');
                    const day = String(baseDate.getDate()).padStart(2, '0');
                    this.dueDate = `${year}-${month}-${day}`;
                },

                handleImportInvoice() {
                    if (!this.customerCode) {
                        if (window.showTransactionErrorModal) {
                            window.showTransactionErrorModal('Pilih customer terlebih dahulu sebelum import faktur.');
                            return;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Terjadi Kesalahan',
                            text: 'Pilih customer terlebih dahulu sebelum import faktur.'
                        });
                        return;
                    }

                    if (window.showAppInfoAlert) {
                        window.showAppInfoAlert('Informasi', 'Fitur import faktur belum disiapkan pada halaman ini.');
                        return;
                    }

                    Swal.fire({
                        icon: 'info',
                        title: 'Informasi',
                        text: 'Fitur import faktur belum disiapkan pada halaman ini.'
                    });
                }
            }
        }
    </script>
@endpush
