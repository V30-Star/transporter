@extends('layouts.app')

@section('title', $pageTitle ?? 'Jurnal Transaksi')

@section('content')
    <style>
        input::placeholder,
        textarea::placeholder {
            color: #9ca3af !important;
            font-weight: normal !important;
        }

        input:disabled::placeholder,
        textarea:disabled::placeholder {
            color: #9ca3af !important;
            -webkit-text-fill-color: #9ca3af !important;
            font-weight: normal !important;
        }
    </style>
    <script>
        window.ACCOUNTS_DATA = @json($accounts);
        window.SUBACCOUNTS_DATA = @json($subaccounts);
        window.REFERENCE_ALLOWED_ACCOUNT_CODES = @json($referenceAllowedAccountCodes ?? []);
    </script>

    <div>
            <form action="{{ route('jurnaltransaksi.store') }}" method="POST" data-form-draft="true"
                data-draft-key="jurnaltransaksi:create" x-data="itemsTable()" x-init="init()"
                @submit="onSubmit($event)"> @csrf

                {{-- ── HEADER jurnalmt ── --}}
                <div class="border border-gray-200 rounded-xl bg-white p-6 mb-6">
                     <div class="flex items-center gap-2 px-4 pt-3 pb-0 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Identitas Jurnal</p>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                    {{-- fbranchcode --}}
                    <div class="lg:col-span-4">
                        <label class="block text-xs font-bold mb-1">Cabang</label>
                        <input type="text" class="w-full border-gray-300 rounded-lg px-3 py-2 bg-gray-200 cursor-not-allowed focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            value="{{ $fbranchcode }}" disabled>
                        <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                    </div>

                    {{-- fjurnalno (auto) --}}
                    <div class="lg:col-span-4" x-data="{ autoCode: true }">
                        <label class="block text-xs font-bold mb-1">No. Jurnal</label>
                        <div class="flex items-center gap-3">
                            <input type="text" name="fjurnalno" class="w-full border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                :disabled="autoCode" :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'"
                                :placeholder="autoCode ? 'Auto Generated' : ''">
                            <label class="inline-flex items-center select-none">
                                <input type="checkbox" x-model="autoCode" checked>
                                <span class="ml-2 text-xs text-gray-700">Auto</span>
                            </label>
                        </div>
                    </div>

                    {{-- fjurnaltype --}}
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-bold mb-1">Tipe Jurnal</label>
                        @if (!empty($fixedJournalType))
                            <input type="text" value="{{ $fixedJournalType }}"
                                class="w-full border-gray-300 rounded-lg px-3 py-2 bg-gray-100 cursor-not-allowed" disabled>
                            <input type="hidden" name="fjurnaltype" value="{{ $fixedJournalType }}">
                        @else
                            <select name="fjurnaltype" class="w-full border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @foreach ($journalTypes as $type)
                                    <option value="{{ $type->fmastercode }}" @selected(old('fjurnaltype', ($journalType ?: 'SJU')) === $type->fmastercode)>
                                        {{ $type->fmastercode }} - {{ $type->fmastername }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    {{-- fjurnaldate --}}
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-bold mb-1">Tanggal</label>
                        <input type="date" name="fjurnaldate" value="{{ old('fjurnaldate', date('Y-m-d')) }}"
                            class="w-full border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('fjurnaldate') border-red-500 @enderror">
                        @error('fjurnaldate')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- fjurnalnote --}}
                    <div class="lg:col-span-12">
                        <label class="block text-xs font-bold mb-1">Keterangan Jurnal</label>
                        <textarea name="fjurnalnote" rows="2"
                            class="w-full border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('fjurnalnote') border-red-500 @enderror"
                            placeholder="Keterangan jurnal...">{{ old('fjurnalnote') }}</textarea>
                        @error('fjurnalnote')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                </div>{{-- end header grid --}}
                </div>

                {{-- ── DETAIL jurnaldt ── --}}
                <div class="border border-gray-200 rounded-xl bg-white p-6 mb-6">
                    <div class="flex items-center gap-2 px-4 pt-3 pb-0 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Detail Jurnal</p>
                    </div>
                    <div class="mt-6">

                    <div class="flex items-center justify-between mb-4">
                        {{-- Indikator balance --}}
                        <div class="text-xs font-medium flex gap-6">
                            <span>Total Debit: <strong x-text="fmt(totalDebit)" class="text-blue-700"></strong></span>
                            <span>Total Kredit: <strong x-text="fmt(totalKredit)" class="text-green-700"></strong></span>
                        </div>
                    </div>

                    <div class="overflow-auto border rounded">
                        <table class="pr-detail-table min-w-full text-sm balanced-detail-table"
                            data-skip-auto-detail-style="true">
                            <colgroup>
                                <col style="width:2%;">
                                <col style="width:12%;">
                                <col style="width:20%;">
                                <col style="width:15%;">
                                <col style="width:10%;">
                                <col style="width:6%;">
                                <col style="width:20%;">
                                <col style="width:10%;">
                                <col style="width:5%;">
                            </colgroup>
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="p-2 text-left w-8">#</th>
                                    <th class="p-2 text-left w-40">Kode Account <span class="text-red-500">*</span></th>
                                    <th class="p-2 text-left w-56">Nama Account</th>
                                    <th class="p-2 text-left w-52">Sub Account</th>
                                    <th class="p-2 text-left w-28">Ref No</th>
                                    <th class="p-2 text-left w-20">D/K <span class="text-red-500">*</span></th>
                                    <th class="p-2 text-left w-72">Keterangan</th>
                                    <th class="p-2 text-right w-40 whitespace-nowrap">Jumlah <span class="text-red-500">*</span></th>
                                    <th class="p-2 text-center w-28">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(row, index) in savedItems" :key="row.uid">
                                    <tr class="border-t align-top hover:bg-gray-50">
                                        <td class="p-2 text-gray-400" x-text="index + 1"></td>
                                        <td class="p-2">
                                            <div class="flex">
                                                <input type="text"
                                                    class="flex-1 border rounded-l px-2 py-1 font-mono text-sm focus:ring-1 focus:ring-blue-500 min-w-0"
                                                    x-model.trim="row.faccount"
                                                    @input="syncAccountFromCode(row)"
                                                    @keydown.prevent="">
                                                <button type="button" @click="openBrowseFor(index)"
                                                    class="shrink-0 border border-l-0 px-2 py-1 bg-white hover:bg-gray-55 text-gray-500 transition-colors"
                                                    title="Cari account">
                                                    <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                        <td class="p-2">
                                            <div class="px-2 py-1 text-sm text-gray-655 bg-gray-50 border rounded" x-text="row.faccname || '-'"></div>
                                        </td>
                                        <td class="p-2">
                                            <select class="w-full border rounded px-2 py-1 text-sm focus:ring-1 focus:ring-blue-500 transition-colors"
                                                x-model="row.fsubaccountcode" :disabled="!row.fhavesubaccount"
                                                :class="!row.fhavesubaccount ? 'bg-gray-50 text-gray-400 cursor-not-allowed opacity-60' : 'bg-white'">
                                                <option value="">— Pilih Sub —</option>
                                                <template x-for="sacc in subaccounts" :key="sacc.fsubaccountid">
                                                    <option :value="sacc.fsubaccountcode"
                                                        x-text="`${sacc.fsubaccountcode} - ${sacc.fsubaccountname}`"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="p-2">
                                            <input type="text" x-model="row.frefno"
                                                class="w-full border rounded px-2 py-1 text-sm focus:ring-1 focus:ring-blue-500"
                                                :disabled="!isRefAllowed(row.faccount)"
                                                :class="!isRefAllowed(row.faccount) ? 'bg-gray-50 text-gray-400 cursor-not-allowed' : 'bg-white'"
                                                placeholder="No Ref">
                                        </td>
                                        <td class="p-2">
                                            <select class="w-full border rounded px-2 py-1 text-sm focus:ring-1 focus:ring-blue-500"
                                                x-model="row.fdk"
                                                @change="autofillBalancedAmount(row); recalcTotals()">
                                                <option value="D">D</option>
                                                <option value="K">K</option>
                                            </select>
                                        </td>
                                        <td class="p-2">
                                            <input type="text" class="w-full border rounded px-2 py-1 text-sm focus:ring-1 focus:ring-blue-500"
                                                x-model="row.faccountnote" placeholder="Keterangan">
                                        </td>
                                        <td class="p-2 text-right">
                                            <input type="text" class="w-full border rounded px-2 py-1 text-right text-sm focus:ring-1 focus:ring-blue-500"
                                                inputmode="decimal"
                                                x-model="row.famountInput" @blur="normalizeAmount(row)"
                                                @input="recalcTotals()">
                                        </td>
                                        <td class="p-2 text-center text-xs">
                                            <button type="button" @click="removeRow(index)"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded bg-red-100 text-red-600 hover:bg-red-200 transition-colors"
                                                title="Hapus baris">-</button>
                                        </td>
                                        <td class="hidden">
                                            <input type="hidden" name="faccount[]" :value="row.faccount">
                                            <input type="hidden" name="fsubaccount[]" :value="row.fsubaccountcode">
                                            <input type="hidden" name="fdk[]" :value="row.fdk">
                                            <input type="hidden" name="faccountnote[]" :value="row.faccountnote">
                                            <input type="hidden" name="frefno[]" :value="row.frefno">
                                            <input type="hidden" name="famount[]" :value="row.famount">
                                            <input type="hidden" name="frate[]" :value="row.frate || 1">
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    {{-- Error: tidak ada item --}}
                    <div x-show="showNoItems" x-cloak
                        class="fixed inset-0 z-[90] flex items-center justify-center" x-transition.opacity>
                        <div class="absolute inset-0 bg-black/50" @click="showNoItems=false"></div>
                        <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden">
                            <div class="px-5 py-4 border-b flex items-center">
                                <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-500 mr-2" />
                                <h3 class="text-lg font-semibold text-gray-800">Tidak Ada Item</h3>
                            </div>
                            <div class="px-5 py-4">
                                <p class="text-sm text-gray-700">Tambahkan minimal satu baris jurnal sebelum menyimpan.</p>
                            </div>
                            <div class="px-5 py-3 border-t flex justify-end">
                                <button type="button" @click="showNoItems=false"
                                    class="h-9 px-4 rounded-lg bg-blue-600 text-white text-sm font-medium">OK</button>
                            </div>
                        </div>
                    </div>

                </div>{{-- end itemsTable --}}
                </div>

                <div class="border border-gray-200 rounded-xl bg-white p-6 mt-6">
                    <div class="flex justify-end gap-3">
                        <button type="submit"
                            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                            <x-heroicon-o-check class="w-5 h-5 mr-2" /> Simpan
                        </button>
                        <button type="button" onclick="window.location='{{ $indexUrl ?? route('jurnaltransaksi.index') }}'"
                            class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors flex items-center">
                            <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" /> Keluar
                        </button>
                    </div>
                </div>

            </form>
        </div>
@endsection

@include('components.transaction.browse-account-modal', [
    'tableId' => 'journalAccountTable',
    'showControls' => true,
    'controlsId' => 'journalAccountTableControls',
    'showPagination' => true,
    'paginationId' => 'journalAccountTablePagination',
    'routeName' => 'account.browse',
    'eventName' => 'account-browse-open',
    'title' => 'Pilih Account',
])

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.6/css/dataTables.dataTables.min.css">
@endpush

@push('scripts')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.6/js/dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            function initSelect2(selector) {
                $(selector).select2({ width: '100%' });
            }
            $(document).on('select2:select select2:clear', 'select', function() {
                this.dispatchEvent(new Event('input', { bubbles: true }));
            });
        });

        function itemsTable() {
            return {
                showNoItems: false,
                savedItems: [],
                browseIndex: null,

                accounts: window.ACCOUNTS_DATA ?? [],
                subaccounts: window.SUBACCOUNTS_DATA ?? [],
                referenceAllowedAccountCodes: (window.REFERENCE_ALLOWED_ACCOUNT_CODES ?? []).map(code => String(code).trim().toUpperCase()),

                totalDebit: 0,
                totalKredit: 0,

                get isBalanced() {
                    const validItems = this.savedItems.filter(it => it.faccount && Number(it.famount) > 0);
                    const debit = validItems.filter(it => it.fdk === 'D').reduce((s, it) => s + Number(it.famount || 0), 0);
                    const kredit = validItems.filter(it => it.fdk === 'K').reduce((s, it) => s + Number(it.famount || 0), 0);
                    return debit > 0 && Math.abs(debit - kredit) < 0.005;
                },

                fmt(n) {
                    const v = Number(n);
                    if (!isFinite(v) || n === '' || n === null) return '0,00';
                    return v.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                parseDecimal(value) {
                    if (typeof value === 'number') return Number.isFinite(value) ? value : 0;
                    let normalized = String(value ?? '').trim();
                    if (!normalized) return 0;
                    normalized = normalized.replace(/\s+/g, '');
                    const commaPos = normalized.lastIndexOf(',');
                    const dotPos = normalized.lastIndexOf('.');
                    if (commaPos !== -1 && dotPos !== -1) {
                        if (commaPos > dotPos) normalized = normalized.replace(/\./g, '').replace(',', '.');
                        else normalized = normalized.replace(/,/g, '');
                    } else if (commaPos !== -1) normalized = normalized.replace(/\./g, '').replace(',', '.');
                    else normalized = normalized.replace(/,/g, '');
                    normalized = normalized.replace(/[^0-9.\-]/g, '');
                    const parsed = Number.parseFloat(normalized);
                    return Number.isFinite(parsed) ? parsed : 0;
                },

                formatDecimalInput(value) {
                    return this.parseDecimal(value).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                normalizeAmount(row) {
                    row.famount = Number(this.parseDecimal(row.famountInput).toFixed(2));
                    row.famountInput = this.formatDecimalInput(row.famount);
                    this.recalcTotals();
                },

                updateAccount(row, faccid, accName, accCode) {
                    const accObj = this.accounts.find(a => String(a.faccid) === String(faccid));
                    Object.assign(row, {
                        faccid: faccid,
                        faccname: accName || (accObj?.faccname ?? ''),
                        faccount: accCode || (accObj?.faccount ?? ''),
                        fhavesubaccount: accObj ? Number(accObj.fhavesubaccount ?? 0) : 0,
                        fsubaccountid: 0,
                        fsubaccountcode: '',
                        fsubaccountname: '',
                    });
                    if (!this.isRefAllowed(row.faccount)) row.frefno = '';
                },

                isRefAllowed(accountCode) {
                    return this.referenceAllowedAccountCodes.includes(String(accountCode ?? '').trim().toUpperCase());
                },

                syncAccountFromCode(row) {
                    const code = String(row.faccount ?? '').trim().toUpperCase();
                    const accObj = this.accounts.find(a => String(a.faccount ?? '').trim().toUpperCase() === code);
                    if (!accObj) {
                        Object.assign(row, { faccid: '', faccname: '', fhavesubaccount: 0, fsubaccountid: '', fsubaccountcode: '', fsubaccountname: '', frefno: '' });
                        this.recalcTotals();
                        return;
                    }
                    this.updateAccount(row, accObj.faccid, accObj.faccname, accObj.faccount);
                    this.recalcTotals();
                },

                openBrowseFor(index) {
                    this.browseIndex = index;
                    window.dispatchEvent(new CustomEvent('account-browse-open'));
                },

                recalcTotals() {
                    const validItems = this.savedItems.filter(it => it.faccount && Number(it.famount) > 0);
                    this.totalDebit = validItems.filter(it => it.fdk === 'D').reduce((s, it) => s + Number(it.famount || 0), 0);
                    this.totalKredit = validItems.filter(it => it.fdk === 'K').reduce((s, it) => s + Number(it.famount || 0), 0);
                },

                getBalanceSuggestion(targetType, rowIndex) {
                    let debit = 0; let kredit = 0;
                    this.savedItems.forEach((item, index) => {
                        if (index === rowIndex) return;
                        if (item.fdk === 'D') debit += Number(item.famount || 0);
                        if (item.fdk === 'K') kredit += Number(item.famount || 0);
                    });
                    if (targetType === 'D') return Math.max(0, Number((kredit - debit).toFixed(2)));
                    if (targetType === 'K') return Math.max(0, Number((debit - kredit).toFixed(2)));
                    return 0;
                },

                autofillBalancedAmount(row) {
                    const rowIndex = this.savedItems.findIndex(r => r.uid === row.uid);
                    const suggested = this.getBalanceSuggestion(row.fdk, rowIndex);
                    if (!(suggested > 0)) return;
                    row.famount = suggested;
                    row.famountInput = this.formatDecimalInput(suggested);
                },

                removeRow(index) {
                    this.savedItems.splice(index, 1);
                    this.recalcTotals();
                },

                onSubmit($event) {
                    const validItems = this.savedItems.filter(it => it.faccount && Number(it.famount) > 0);
                    if (validItems.length === 0) {
                        $event.preventDefault();
                        window.showTransactionErrorModal('Tambahkan minimal satu baris jurnal sebelum menyimpan.');
                        return;
                    }
                    let tD = validItems.filter(it => it.fdk === 'D').reduce((s, it) => s + Number(it.famount || 0), 0);
                    let tK = validItems.filter(it => it.fdk === 'K').reduce((s, it) => s + Number(it.famount || 0), 0);
                    if (Math.abs(tD - tK) >= 0.005 || tD === 0) {
                        $event.preventDefault();
                        window.showTransactionErrorModal(['Jurnal tidak balance.', `Total Debit: ${this.fmt(tD)}`, `Total Kredit: ${this.fmt(tK)}`], { reason: 'Nilai transaksi tidak seimbang.' });
                    }
                },

                emptyRow() {
                    return { uid: this.makeUid(), faccount: '', faccid: '', faccname: '', fhavesubaccount: 0, fsubaccountcode: '', fsubaccountid: '', fsubaccountname: '', fdk: 'D', faccountnote: '', frefno: '', famount: 0, famountInput: '0,00', frate: 1 };
                },

                normalizeRow(row = {}, index = 0) {
                    const parsedAmt = Number(this.parseDecimal(row.famount || 0).toFixed(2));
                    const matchedAccount = this.accounts.find(a => String(a.faccount).trim() === String(row.faccount || '').trim());
                    return {
                        uid: row.uid || `jt-row-${index}-${this.makeUid()}`,
                        faccount: String(row.faccount || '').trim(),
                        faccid: matchedAccount ? matchedAccount.faccid : (row.faccid || ''),
                        faccname: matchedAccount ? matchedAccount.faccname : (row.faccname || ''),
                        fhavesubaccount: matchedAccount ? Number(matchedAccount.fhavesubaccount ?? 0) : Number(row.fhavesubaccount || 0),
                        fsubaccountcode: String(row.fsubaccountcode || '').trim(),
                        fsubaccountid: row.fsubaccountid || '',
                        fsubaccountname: String(row.fsubaccountname || '').trim(),
                        fdk: String(row.fdk || 'D').trim() || 'D',
                        faccountnote: String(row.faccountnote || '').trim(),
                        frefno: String(row.frefno || '').trim(),
                        famount: parsedAmt,
                        famountInput: this.formatDecimalInput(parsedAmt),
                        frate: Number(row.frate || 1),
                    };
                },

                makeUid() { return `${Date.now()}-${Math.random().toString(36).slice(2, 10)}`; },

                rowHasContent(row) { return row && String(row.faccount || '').trim() !== ''; },

                ensureMinimumRows() { while (this.savedItems.length < 5) this.savedItems.push(this.emptyRow()); },

                ensureTrailingRow() {
                    if (!this.savedItems.length) { this.ensureMinimumRows(); return; }
                    if (this.rowHasContent(this.savedItems[this.savedItems.length - 1])) this.savedItems.push(this.emptyRow());
                },

                init() {
                    this.savedItems = (Array.isArray(this.savedItems) ? this.savedItems : []).map((row, index) => this.normalizeRow(row, index));
                    this.ensureMinimumRows();
                    this.recalcTotals();
                    this.$watch('savedItems', () => { this.ensureMinimumRows(); this.ensureTrailingRow(); }, { deep: true });
                    window.addEventListener('account-picked', (event) => {
                        if (this.browseIndex === null || !this.savedItems[this.browseIndex]) return;
                        const detail = event.detail || {};
                        this.updateAccount(
                            this.savedItems[this.browseIndex],
                            detail.faccid ?? '',
                            detail.faccname ?? '',
                            detail.faccount ?? ''
                        );
                        this.recalcTotals();
                    }, {
                        passive: true
                    });
                },
            };

            // ─── newRow: field = kolom jurnaldt ─────────────────────────────────────
            function newRow() {
                return {
                    uid: null,
                    // jurnaldt columns
                    faccount: '', // kode akun string → di-submit
                    faccid: '', // hanya untuk select2 binding (tidak di-submit)
                    faccname: '', // display only
                    fhavesubaccount: 0, // flag dari master akun
                    fsubaccountcode: '', // → di-submit sebagai fsubaccount
                    fsubaccountid: '', // hanya untuk select2 binding
                    fsubaccountname: '', // display only
                    fdk: '', // 'D' | 'K'
                    faccountnote: '', // keterangan baris
                    frefno: '', // referensi nomor
                    famount: 0, // jumlah
                    famountInput: '0,00', // tampilan jumlah
                    frate: 1, // rate (default 1)
                };
            }

            function cryptoRandom() {
                try {
                    if (window.crypto?.getRandomValues) {
                        const a = new Uint32Array(2);
                        window.crypto.getRandomValues(a);
                        return [...a].map(n => n.toString(16)).join('') + Date.now();
                    }
                } catch (e) {}
                return Math.random().toString(36).slice(2) + Date.now();
            }
        }
    </script>
@endpush
