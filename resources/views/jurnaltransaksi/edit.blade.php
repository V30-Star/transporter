@extends('layouts.app')

@section('title', $pageTitle ?? ($action === 'delete' ? 'Hapus Jurnal Transaksi' : 'Edit Jurnal Transaksi'))

@section('content')
    @php
        $isDelete = $action === 'delete';
    @endphp

    <div class="bg-white rounded shadow p-6 md:p-8 max-w-[96rem] mx-auto"
        x-data="journalForm({
            mode: @js($action),
            items: @js($savedItems),
            accounts: @js($accounts),
            subaccounts: @js($subaccounts),
            referenceAllowedAccountCodes: @js($referenceAllowedAccountCodes ?? []),
            deleteUrl: @js(route('jurnaltransaksi.destroy', $jurnaltransaksi->fjurnalmtid)),
            indexUrl: @js($indexUrl ?? route('jurnaltransaksi.index')),
            csrfToken: @js(csrf_token()),
        })"
        x-init="init()">

        @if ($isDelete)
            <div class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                    <div class="lg:col-span-4">
                        <label class="block text-sm font-medium mb-1">Cabang</label>
                        <input type="text" value="{{ $fcabang }}"
                            class="w-full border rounded px-3 py-2 bg-gray-100 cursor-not-allowed" disabled>
                    </div>

                    <div class="lg:col-span-4">
                        <label class="block text-sm font-medium mb-1">No. Jurnal</label>
                        <input type="text" value="{{ $jurnaltransaksi->fjurnalno }}"
                            class="w-full border rounded px-3 py-2 bg-gray-100 cursor-not-allowed" disabled>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium mb-1">Tipe Jurnal</label>
                        <input type="text" value="{{ $jurnaltransaksi->fjurnaltype }}"
                            class="w-full border rounded px-3 py-2 bg-gray-100 cursor-not-allowed" disabled>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium mb-1">Tanggal</label>
                        <input type="text" value="{{ \Carbon\Carbon::parse($jurnaltransaksi->fjurnaldate)->format('d/m/Y') }}"
                            class="w-full border rounded px-3 py-2 bg-gray-100 cursor-not-allowed" disabled>
                    </div>

                    <div class="lg:col-span-12">
                        <label class="block text-sm font-medium mb-1">Keterangan Jurnal</label>
                        <textarea rows="3" class="w-full border rounded px-3 py-2 bg-gray-100 cursor-not-allowed" disabled>{{ $jurnaltransaksi->fjurnalnote }}</textarea>
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                        <h3 class="text-base font-semibold text-gray-800">Detail Jurnal</h3>
                        <div class="flex flex-col gap-1 text-sm sm:flex-row sm:gap-6">
                            <span>Total Debit:
                                <strong class="text-blue-700" x-text="formatAmount(totalDebit())"></strong>
                            </span>
                            <span>Total Kredit:
                                <strong class="text-green-700" x-text="formatAmount(totalKredit())"></strong>
                            </span>
                        </div>
                    </div>

                    <div class="overflow-auto border rounded">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="p-2 text-left w-12">#</th>
                                    <th class="p-2 text-left w-44">Kode Account</th>
                                    <th class="p-2 text-left w-56">Nama Account</th>
                                    <th class="p-2 text-left w-56">Sub Account</th>
                                    <th class="p-2 text-left w-48">Ref No</th>
                                    <th class="p-2 text-left w-20">D/K</th>
                                    <th class="p-2 text-left w-[28rem]">Keterangan</th>
                                    <th class="p-2 text-right w-44">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, index) in items" :key="item.uid ?? index">
                                    <tr class="border-t align-top">
                                        <td class="p-2 text-gray-500" x-text="index + 1"></td>
                                        <td class="p-2 text-gray-700 font-mono" x-text="item.faccount || '-'"></td>
                                        <td class="p-2">
                                            <div class="font-medium text-gray-800" x-text="accountName(item.faccount) || '-'"></div>
                                        </td>
                                        <td class="p-2 text-gray-700" x-text="subaccountName(item.fsubaccountcode) || '-'"></td>
                                        <td class="p-2 text-gray-600" x-text="item.frefno || '-'"></td>
                                        <td class="p-2">
                                            <span class="px-2 py-0.5 rounded text-xs font-semibold"
                                                :class="item.fdk === 'D' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'"
                                                x-text="item.fdk === 'D' ? 'Debit' : 'Kredit'"></span>
                                        </td>
                                        <td class="p-2 text-gray-700" x-text="item.faccountnote || '-'"></td>
                                        <td class="p-2 text-right font-medium" x-text="formatAmount(item.famount)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex justify-center gap-4">
                    <button type="button" @click="showDeleteModal = true"
                        class="inline-flex items-center bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700">
                        <x-heroicon-o-trash class="w-5 h-5 mr-2" />
                        Hapus
                    </button>
                    <a href="{{ $indexUrl ?? route('jurnaltransaksi.index') }}"
                        class="inline-flex items-center bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                        Kembali
                    </a>
                </div>
            </div>
        @else
            <form action="{{ route('jurnaltransaksi.update', $jurnaltransaksi->fjurnalmtid) }}" method="POST"
                class="space-y-6" data-form-draft="true"
                data-draft-key="jurnaltransaksi:edit:{{ $jurnaltransaksi->fjurnalmtid }}"
                @submit="onSubmit($event)">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                    <div class="lg:col-span-4">
                        <label class="block text-sm font-medium mb-1">Cabang</label>
                        <input type="text" value="{{ $fcabang }}"
                            class="w-full border rounded px-3 py-2 bg-gray-100 cursor-not-allowed" disabled>
                        <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                    </div>

                    <div class="lg:col-span-4">
                        <label class="block text-sm font-medium mb-1">No. Jurnal</label>
                        <input type="text" name="fjurnalno" value="{{ old('fjurnalno', $jurnaltransaksi->fjurnalno) }}"
                            class="w-full border rounded px-3 py-2 bg-gray-100 cursor-not-allowed" readonly>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium mb-1">Tipe Jurnal</label>
                        @if (!empty($lockJournalType))
                            <input type="text" value="{{ old('fjurnaltype', $jurnaltransaksi->fjurnaltype) }}"
                                class="w-full border rounded px-3 py-2 bg-gray-100 cursor-not-allowed" disabled>
                            <input type="hidden" name="fjurnaltype" value="{{ old('fjurnaltype', $jurnaltransaksi->fjurnaltype) }}">
                        @else
                            <select name="fjurnaltype" class="w-full border rounded px-3 py-2">
                                @foreach (['JV' => 'JV - Journal Voucher', 'AP' => 'AP - Accounts Payable', 'AR' => 'AR - Accounts Receivable'] as $code => $label)
                                    <option value="{{ $code }}" @selected(old('fjurnaltype', $jurnaltransaksi->fjurnaltype) === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium mb-1">Tanggal</label>
                        <input type="date" name="fjurnaldate"
                            value="{{ old('fjurnaldate', \Carbon\Carbon::parse($jurnaltransaksi->fjurnaldate)->format('Y-m-d')) }}"
                            class="w-full border rounded px-3 py-2 @error('fjurnaldate') border-red-500 @enderror">
                        @error('fjurnaldate')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="lg:col-span-12">
                        <label class="block text-sm font-medium mb-1">Keterangan Jurnal</label>
                        <textarea name="fjurnalnote" rows="3"
                            class="w-full border rounded px-3 py-2 @error('fjurnalnote') border-red-500 @enderror">{{ old('fjurnalnote', $jurnaltransaksi->fjurnalnote) }}</textarea>
                        @error('fjurnalnote')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                        <h3 class="text-base font-semibold text-gray-800">Detail Jurnal</h3>
                        <div class="flex flex-col gap-1 text-sm sm:flex-row sm:gap-6">
                            <span>Total Debit:
                                <strong class="text-blue-700" x-text="formatAmount(totalDebit())"></strong>
                            </span>
                            <span>Total Kredit:
                                <strong class="text-green-700" x-text="formatAmount(totalKredit())"></strong>
                            </span>
                        </div>
                    </div>

                    <div class="overflow-auto border rounded">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="p-2 text-left w-8">#</th>
                                    <th class="p-2 text-left w-40">Kode Account <span class="text-red-500">*</span></th>
                                    <th class="p-2 text-left w-56">Nama Account</th>
                                    <th class="p-2 text-left w-52">Sub Account</th>
                                    <th class="p-2 text-left w-28">Ref No</th>
                                    <th class="p-2 text-left w-20">D/K <span class="text-red-500">*</span></th>
                                    <th class="p-2 text-left w-72">Keterangan</th>
                                    <th class="p-2 text-right w-40">Jumlah <span class="text-red-500">*</span></th>
                                    <th class="p-2 text-center w-28">Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                <template x-for="(item, index) in items" :key="item.uid">
                                    <tr class="border-t align-middle hover:bg-gray-50">
                                        <td class="p-2 text-gray-500" x-text="index + 1"></td>

                                        {{-- Kode Account --}}
                                        <td class="p-2">
                                            <div class="flex items-center gap-1">
                                                <input type="text"
                                                    class="w-full border rounded px-2 py-1 font-mono uppercase text-xs"
                                                    x-model.trim="item.faccount"
                                                    @input="syncAccountFromCode(item)"
                                                    @keydown.enter.prevent="">
                                                <button type="button" @click="openBrowseFor(index)"
                                                    class="border rounded px-1.5 py-1 bg-white hover:bg-gray-50"
                                                    title="Cari account">
                                                    <x-heroicon-o-magnifying-glass class="w-3.5 h-3.5" />
                                                </button>
                                            </div>
                                        </td>

                                        {{-- Nama Account --}}
                                        <td class="p-2">
                                            <input type="text"
                                                class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-xs"
                                                :value="item.faccname || '-'" disabled>
                                        </td>

                                        {{-- Sub Account --}}
                                        <td class="p-2">
                                            <select class="w-full border rounded px-2 py-1 text-xs transition-colors"
                                                x-model="item.fsubaccountcode" :disabled="!item.fhavesubaccount"
                                                :class="!item.fhavesubaccount ? 'bg-gray-100 text-gray-400 cursor-not-allowed opacity-60' : 'bg-white'">
                                                <option value="">— Pilih Sub —</option>
                                                <template x-for="sacc in subaccounts" :key="sacc.fsubaccountid">
                                                    <option :value="sacc.fsubaccountcode"
                                                        x-text="`${sacc.fsubaccountcode} - ${sacc.fsubaccountname}`"></option>
                                                </template>
                                            </select>
                                        </td>

                                        {{-- Ref No --}}
                                        <td class="p-2">
                                            <input type="text" x-model="item.frefno"
                                                class="w-full border rounded px-2 py-1 text-xs"
                                                :disabled="!isRefAllowed(item.faccount)"
                                                :class="!isRefAllowed(item.faccount) ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white'"
                                                placeholder="No Ref">
                                        </td>

                                        {{-- D/K --}}
                                        <td class="p-2">
                                            <select class="w-full border rounded px-2 py-1 text-xs"
                                                x-model="item.fdk"
                                                @change="autofillBalancedAmount(item); recalcTotals()">
                                                <option value="D">D</option>
                                                <option value="K">K</option>
                                            </select>
                                        </td>

                                        {{-- Keterangan --}}
                                        <td class="p-2">
                                            <input type="text" class="w-full border rounded px-2 py-1 text-xs"
                                                x-model="item.faccountnote" placeholder="Keterangan">
                                        </td>

                                        {{-- Jumlah --}}
                                        <td class="p-2 text-right">
                                            <input type="text" class="border rounded px-2 py-1 w-full text-right text-xs"
                                                inputmode="decimal"
                                                x-model="item.famountInput" @blur="normalizeAmount(item)"
                                                @input="recalcTotals()">
                                        </td>

                                        {{-- Aksi --}}
                                        <td class="p-2 text-center">
                                            <div class="flex items-center justify-center">
                                                <button type="button" @click="removeRow(index)"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded bg-red-100 text-red-600 hover:bg-red-200 text-lg font-bold transition-colors duration-150"
                                                    title="Hapus baris">
                                                    -
                                                </button>
                                            </div>
                                        </td>

                                        {{-- Hidden inputs untuk POST --}}
                                        <td class="hidden">
                                            <input type="hidden" name="faccount[]" :value="item.faccount">
                                            <input type="hidden" name="fsubaccount[]" :value="item.fsubaccountcode">
                                            <input type="hidden" name="fdk[]" :value="item.fdk">
                                            <input type="hidden" name="faccountnote[]" :value="item.faccountnote">
                                            <input type="hidden" name="frefno[]" :value="item.frefno">
                                            <input type="hidden" name="famount[]" :value="item.famount">
                                            <input type="hidden" name="frate[]" :value="item.frate || 1">
                                        </td>
                                    </tr>
                                </template>

                                {{-- Total row --}}
                                <tr class="border-t bg-gray-50 font-semibold text-sm">
                                    <td colspan="7" class="p-2 text-right text-gray-600">Total:</td>
                                    <td class="p-2 text-right" x-text="fmt(totalDebit)"></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex justify-center gap-4">
                    <button type="submit"
                        class="inline-flex items-center bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                        <x-heroicon-o-check class="w-5 h-5 mr-2" />
                        Simpan
                    </button>
                    <a href="{{ $indexUrl ?? route('jurnaltransaksi.index') }}"
                        class="inline-flex items-center bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                        Kembali
                    </a>
                </div>
            </form>
        @endif
        @if ($isDelete)
            <div x-show="showDeleteModal" x-cloak
                class="fixed inset-0 z-[95] flex items-center justify-center"
                x-transition.opacity>
                <div class="absolute inset-0 bg-black/50" @click="showDeleteModal = false"></div>
                <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden">
                    <div class="px-5 py-4 border-b flex items-center">
                        <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-500 mr-2" />
                        <h3 class="text-lg font-semibold text-gray-800">Konfirmasi Hapus</h3>
                    </div>
                    <div class="px-5 py-4">
                        <p class="text-sm text-gray-700">Apakah Anda yakin ingin menghapus jurnal transaksi ini?</p>
                    </div>
                    <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
                        <button type="button" @click="showDeleteModal = false"
                            class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">
                            Batal
                        </button>
                        <button type="button" @click="confirmDelete()"
                            class="h-9 px-4 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700">
                            Hapus
                        </button>
                    </div>
                </div>
            </div>

            <form action="{{ route('jurnaltransaksi.destroy', $jurnaltransaksi->fjurnalmtid) }}" method="POST"
                class="hidden" id="deleteForm">
                @csrf
                @method('DELETE')
            </form>
        @endif
    </div>
@endsection

@include('components.transaction.browse-account-modal', [
    'tableId' => 'journalAccountTableEdit',
    'showControls' => true,
    'controlsId' => 'journalAccountTableEditControls',
    'showPagination' => true,
    'paginationId' => 'journalAccountTableEditPagination',
    'routeName' => 'account.browse',
    'eventName' => 'account-browse-open',
    'title' => 'Pilih Account',
])

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.6/css/dataTables.dataTables.min.css">
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.6/js/dataTables.min.js"></script>
    <script>
        function journalForm(config) {
            return {
                mode: config.mode || 'edit',
                showDeleteModal: false,
                items: config.items || [],
                accounts: config.accounts || [],
                subaccounts: config.subaccounts || [],
                referenceAllowedAccountCodes: (config.referenceAllowedAccountCodes || []).map((code) => String(code).trim().toUpperCase()),
                deleteUrl: config.deleteUrl || '',
                indexUrl: config.indexUrl || '',
                csrfToken: config.csrfToken || '',
                browseIndex: null,

                totalDebit: 0,
                totalKredit: 0,

                get isBalanced() {
                    const validItems = this.items.filter(it => it.faccount && Number(it.famount) > 0);
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

                normalizeAmount(item) {
                    item.famount = Number(this.parseDecimal(item.famountInput).toFixed(2));
                    item.famountInput = this.formatDecimalInput(item.famount);
                    this.recalcTotals();
                },

                updateAccount(item, faccid, accName, accCode) {
                    const accObj = this.accounts.find(a => String(a.faccid) === String(faccid));
                    Object.assign(item, {
                        faccid: faccid,
                        faccname: accName || (accObj?.faccname ?? ''),
                        faccount: accCode || (accObj?.faccount ?? ''),
                        fhavesubaccount: accObj ? Number(accObj.fhavesubaccount ?? 0) : 0,
                        fsubaccountid: 0,
                        fsubaccountcode: '',
                        fsubaccountname: '',
                    });
                    if (!this.isRefAllowed(item.faccount)) item.frefno = '';
                },

                isRefAllowed(accountCode) {
                    return this.referenceAllowedAccountCodes.includes(String(accountCode ?? '').trim().toUpperCase());
                },

                syncAccountFromCode(item) {
                    const code = String(item.faccount ?? '').trim().toUpperCase();
                    const accObj = this.accounts.find(a => String(a.faccount ?? '').trim().toUpperCase() === code);
                    if (!accObj) {
                        Object.assign(item, { faccid: '', faccname: '', fhavesubaccount: 0, fsubaccountid: '', fsubaccountcode: '', fsubaccountname: '', frefno: '' });
                        this.recalcTotals();
                        return;
                    }
                    this.updateAccount(item, accObj.faccid, accObj.faccname, accObj.faccount);
                    this.recalcTotals();
                },

                openBrowseFor(index) {
                    this.browseIndex = index;
                    window.dispatchEvent(new CustomEvent('account-browse-open'));
                },

                recalcTotals() {
                    const validItems = this.items.filter(it => it.faccount && Number(it.famount) > 0);
                    this.totalDebit = validItems.filter(it => it.fdk === 'D').reduce((s, it) => s + Number(it.famount || 0), 0);
                    this.totalKredit = validItems.filter(it => it.fdk === 'K').reduce((s, it) => s + Number(it.famount || 0), 0);
                },

                getBalanceSuggestion(targetType, rowIndex) {
                    let debit = 0; let kredit = 0;
                    this.items.forEach((item, index) => {
                        if (index === rowIndex) return;
                        if (item.fdk === 'D') debit += Number(item.famount || 0);
                        if (item.fdk === 'K') kredit += Number(item.famount || 0);
                    });
                    if (targetType === 'D') return Math.max(0, Number((kredit - debit).toFixed(2)));
                    if (targetType === 'K') return Math.max(0, Number((debit - kredit).toFixed(2)));
                    return 0;
                },

                autofillBalancedAmount(item) {
                    const rowIndex = this.items.findIndex(r => r.uid === item.uid);
                    const suggested = this.getBalanceSuggestion(item.fdk, rowIndex);
                    if (!(suggested > 0)) return;
                    item.famount = suggested;
                    item.famountInput = this.formatDecimalInput(suggested);
                },

                removeRow(index) {
                    this.items.splice(index, 1);
                    this.recalcTotals();
                },

                onSubmit($event) {
                    const validItems = this.items.filter(it => it.faccount && Number(it.famount) > 0);
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

                normalizeRow(item = {}, index = 0) {
                    const parsedAmt = Number(this.parseDecimal(item.famount || 0).toFixed(2));
                    const matchedAccount = this.accounts.find(a => String(a.faccount).trim() === String(item.faccount || '').trim());
                    return {
                        uid: item.uid || `jt-row-${index}-${this.makeUid()}`,
                        faccount: String(item.faccount || '').trim(),
                        faccid: matchedAccount ? matchedAccount.faccid : (item.faccid || ''),
                        faccname: matchedAccount ? matchedAccount.faccname : (item.faccname || ''),
                        fhavesubaccount: matchedAccount ? Number(matchedAccount.fhavesubaccount ?? 0) : Number(item.fhavesubaccount || 0),
                        fsubaccountcode: String(item.fsubaccountcode || '').trim(),
                        fsubaccountid: item.fsubaccountid || '',
                        fsubaccountname: String(item.fsubaccountname || '').trim(),
                        fdk: String(item.fdk || 'D').trim() || 'D',
                        faccountnote: String(item.faccountnote || '').trim(),
                        frefno: String(item.frefno || '').trim(),
                        famount: parsedAmt,
                        famountInput: this.formatDecimalInput(parsedAmt),
                        frate: Number(item.frate || 1),
                    };
                },

                makeUid() { return `${Date.now()}-${Math.random().toString(36).slice(2, 10)}`; },

                rowHasContent(item) { return item && String(item.faccount || '').trim() !== ''; },

                ensureMinimumRows() { while (this.items.length < 5) this.items.push(this.emptyRow()); },

                ensureTrailingRow() {
                    if (!this.items.length) { this.ensureMinimumRows(); return; }
                    if (this.rowHasContent(this.items[this.items.length - 1])) this.items.push(this.emptyRow());
                },

                confirmDelete() {
                    const form = document.getElementById('deleteForm');
                    if (form) {
                        form.submit();
                    }
                },

                // Compatibility methods for delete view
                formatAmount(value) {
                    return this.fmt(value);
                },
                accountName(code) {
                    const match = this.accounts.find((account) => String(account.faccount) === String(code));
                    return match ? match.faccname : '';
                },
                subaccountName(code) {
                    const match = this.subaccounts.find((subaccount) => String(subaccount.fsubaccountcode) === String(code));
                    return match ? match.fsubaccountname : '';
                },
                totalDebit() {
                    return this.totalDebit;
                },
                totalKredit() {
                    return this.totalKredit;
                },

                init() {
                    this.items = (Array.isArray(this.items) ? this.items : []).map((item, index) => this.normalizeRow(item, index));
                    this.ensureMinimumRows();
                    this.recalcTotals();
                    this.$watch('items', () => { this.ensureMinimumRows(); this.ensureTrailingRow(); }, { deep: true });
                    
                    window.addEventListener('account-picked', (event) => {
                        if (this.browseIndex === null || !this.items[this.browseIndex]) return;
                        const detail = event.detail || {};
                        this.updateAccount(
                            this.items[this.browseIndex],
                            detail.faccid ?? '',
                            detail.faccname ?? '',
                            detail.faccount ?? ''
                        );
                        this.recalcTotals();
                    }, { passive: true });
                }
            };
        }
    </script>
@endpush
