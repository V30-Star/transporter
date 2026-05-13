@extends('layouts.app')

@section('title', $action === 'delete' ? 'Hapus Jurnal Transaksi' : 'Edit Jurnal Transaksi')

@section('content')
    @php
        $isDelete = $action === 'delete';
    @endphp

    <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1600px] w-full mx-auto"
        x-data="journalForm({
            mode: @js($action),
            items: @js($savedItems),
            accounts: @js($accounts),
            subaccounts: @js($subaccounts),
            referenceAllowedAccountCodes: @js($referenceAllowedAccountCodes ?? []),
            deleteUrl: @js(route('jurnaltransaksi.destroy', $jurnaltransaksi->fjurnalmtid)),
            indexUrl: @js(route('jurnaltransaksi.index')),
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
                    <a href="{{ route('jurnaltransaksi.index') }}"
                        class="inline-flex items-center bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                        Kembali
                    </a>
                </div>
            </div>
        @else
            <form action="{{ route('jurnaltransaksi.update', $jurnaltransaksi->fjurnalmtid) }}" method="POST" class="space-y-6">
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
                        <select name="fjurnaltype" class="w-full border rounded px-3 py-2">
                            @foreach (['JV' => 'JV - Journal Voucher', 'AP' => 'AP - Accounts Payable', 'AR' => 'AR - Accounts Receivable'] as $code => $label)
                                <option value="{{ $code }}" @selected(old('fjurnaltype', $jurnaltransaksi->fjurnaltype) === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
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
                                    <th class="p-2 text-left w-12">#</th>
                                    <th class="p-2 text-left w-44">Kode Account</th>
                                    <th class="p-2 text-left w-60">Nama Account</th>
                                    <th class="p-2 text-left w-60">Sub Account</th>
                                    <th class="p-2 text-left w-44">Ref No</th>
                                    <th class="p-2 text-left w-20">D/K</th>
                                    <th class="p-2 text-left w-[24rem]">Keterangan</th>
                                    <th class="p-2 text-right w-40">Jumlah</th>
                                    <th class="p-2 text-right w-28">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, index) in items" :key="item.uid ?? index">
                                    <tr class="border-t align-top">
                                        <td class="p-2 text-gray-500" x-text="index + 1"></td>
                                        <td class="p-2">
                                            <div class="flex items-center gap-2">
                                                <input type="text" class="w-full border rounded px-2 py-1 font-mono uppercase"
                                                    :name="'faccount[]'" x-model.trim="item.faccount"
                                                    @input="syncAccountFromCode(item)"
                                                    @keydown.enter.prevent="openBrowseFor(index)">
                                                <button type="button" @click="openBrowseFor(index)"
                                                    class="border rounded px-2 py-1 bg-white hover:bg-gray-50"
                                                    title="Cari account">
                                                    <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                        <td class="p-2">
                                            <input type="text" class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                :value="accountName(item.faccount) || '-'" disabled>
                                        </td>
                                        <td class="p-2">
                                            <select class="w-full border rounded px-2 py-1" :name="'fsubaccount[]'" x-model="item.fsubaccountcode">
                                                <option value="">Pilih Sub Akun</option>
                                                <template x-for="subaccount in subaccounts" :key="subaccount.fsubaccountid">
                                                    <option :value="subaccount.fsubaccountcode"
                                                        x-text="`${subaccount.fsubaccountcode} - ${subaccount.fsubaccountname}`"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="p-2">
                                            <input type="text"
                                                class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-400 cursor-not-allowed"
                                                x-model="item.frefno" placeholder="No referensi" disabled>
                                            <input type="hidden" :name="'frefno[]'" :value="item.frefno">
                                        </td>
                                        <td class="p-2">
                                            <select class="w-full border rounded px-2 py-1" :name="'fdk[]'" x-model="item.fdk"
                                                @change="autofillBalancedAmount(item, index)">
                                                <option value="D">D</option>
                                                <option value="K">K</option>
                                            </select>
                                        </td>
                                        <td class="p-2">
                                            <input type="text" class="w-full border rounded px-2 py-1"
                                                :name="'faccountnote[]'" x-model="item.faccountnote" placeholder="Keterangan">
                                        </td>
                                        <td class="p-2">
                                            <input type="text" inputmode="decimal"
                                                class="w-full border rounded px-2 py-1 text-right"
                                                x-model="item.famountInput" @blur="normalizeItemAmount(item)">
                                            <input type="hidden" :name="'famount[]'" :value="item.famount">
                                            <input type="hidden" :name="'frate[]'" x-model="item.frate">
                                        </td>
                                        <td class="p-2 text-right">
                                            <button type="button" @click="removeRow(index)"
                                                class="inline-flex items-center bg-red-100 text-red-600 px-3 py-1 rounded hover:bg-red-200">
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <button type="button" @click="addRow()"
                        class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        <x-heroicon-o-plus class="w-4 h-4 mr-1" />
                        Tambah Detail
                    </button>
                </div>

                <div class="flex justify-center gap-4">
                    <button type="submit"
                        class="inline-flex items-center bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                        <x-heroicon-o-check class="w-5 h-5 mr-2" />
                        Simpan
                    </button>
                    <a href="{{ route('jurnaltransaksi.index') }}"
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
                items: (config.items || []).map((item, index) => ({
                    uid: item.uid ?? index + 1,
                    faccount: item.faccount || '',
                    fsubaccountcode: item.fsubaccountcode || '',
                    fdk: item.fdk || 'D',
                    faccountnote: item.faccountnote || '',
                    frefno: item.frefno || '',
                    famount: Number(item.famount || 0),
                    frate: Number(item.frate || 1),
                })),
                accounts: config.accounts || [],
                subaccounts: config.subaccounts || [],
                referenceAllowedAccountCodes: (config.referenceAllowedAccountCodes || []).map((code) => String(code).trim().toUpperCase()),
                deleteUrl: config.deleteUrl || '',
                indexUrl: config.indexUrl || '',
                csrfToken: config.csrfToken || '',
                browseIndex: null,

                addRow() {
                    this.items.push({
                        uid: Date.now() + Math.random(),
                        faccount: '',
                        fsubaccountcode: '',
                        fdk: 'D',
                        faccountnote: '',
                        frefno: '',
                        famount: 0,
                        famountInput: '0,00',
                        frate: 1,
                    });
                },

                openBrowseFor(index) {
                    this.browseIndex = index;
                    window.dispatchEvent(new CustomEvent('account-browse-open'));
                },

                removeRow(index) {
                    this.items.splice(index, 1);
                },

                totalDebit() {
                    return this.items.reduce((sum, item) => sum + (item.fdk === 'D' ? Number(item.famount || 0) : 0), 0);
                },

                totalKredit() {
                    return this.items.reduce((sum, item) => sum + (item.fdk === 'K' ? Number(item.famount || 0) : 0), 0);
                },

                getBalanceSuggestion(targetType, rowIndex) {
                    let debit = 0;
                    let kredit = 0;

                    this.items.forEach((item, index) => {
                        if (index === rowIndex) return;
                        if (item.fdk === 'D') debit += Number(item.famount || 0);
                        if (item.fdk === 'K') kredit += Number(item.famount || 0);
                    });

                    if (targetType === 'D') {
                        return Math.max(0, Number((kredit - debit).toFixed(2)));
                    }

                    if (targetType === 'K') {
                        return Math.max(0, Number((debit - kredit).toFixed(2)));
                    }

                    return 0;
                },

                isBalanced() {
                    return this.totalDebit().toFixed(2) === this.totalKredit().toFixed(2);
                },

                formatAmount(value) {
                    return Number(value || 0).toLocaleString('id-ID', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    });
                },

                parseDecimal(value) {
                    if (typeof value === 'number') {
                        return Number.isFinite(value) ? value : 0;
                    }

                    let normalized = String(value ?? '').trim();
                    if (!normalized) return 0;

                    normalized = normalized.replace(/\s+/g, '');

                    const commaPos = normalized.lastIndexOf(',');
                    const dotPos = normalized.lastIndexOf('.');

                    if (commaPos !== -1 && dotPos !== -1) {
                        if (commaPos > dotPos) {
                            normalized = normalized.replace(/\./g, '').replace(',', '.');
                        } else {
                            normalized = normalized.replace(/,/g, '');
                        }
                    } else if (commaPos !== -1) {
                        normalized = normalized.replace(/\./g, '').replace(',', '.');
                    } else {
                        normalized = normalized.replace(/,/g, '');
                    }

                    normalized = normalized.replace(/[^0-9.\-]/g, '');
                    const parsed = Number.parseFloat(normalized);
                    return Number.isFinite(parsed) ? parsed : 0;
                },

                formatDecimalInput(value) {
                    return this.parseDecimal(value).toLocaleString('id-ID', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    });
                },

                normalizeItemAmount(item) {
                    item.famount = Number(this.parseDecimal(item.famountInput).toFixed(2));
                    item.famountInput = this.formatDecimalInput(item.famount);
                },

                autofillBalancedAmount(item, index) {
                    const suggested = this.getBalanceSuggestion(item.fdk, index);
                    if (!(suggested > 0)) return;

                    item.famount = suggested;
                    item.famountInput = this.formatDecimalInput(suggested);
                },

                isRefAllowed(accountCode) {
                    return this.referenceAllowedAccountCodes.includes(String(accountCode ?? '').trim().toUpperCase());
                },

                syncAccountFromCode(item) {
                    const code = String(item.faccount ?? '').trim().toUpperCase();
                    const match = this.accounts.find((account) => String(account.faccount ?? '').trim().toUpperCase() === code);

                    if (!match) {
                        item.frefno = '';
                        return;
                    }

                    item.faccount = match.faccount;
                    if (!this.isRefAllowed(item.faccount)) {
                        item.frefno = '';
                    }
                },

                accountName(code) {
                    const match = this.accounts.find((account) => String(account.faccount) === String(code));
                    return match ? match.faccname : '';
                },

                subaccountName(code) {
                    const match = this.subaccounts.find((subaccount) => String(subaccount.fsubaccountcode) === String(code));
                    return match ? match.fsubaccountname : '';
                },

                confirmDelete() {
                    const form = document.getElementById('deleteForm');
                    if (form) {
                        form.submit();
                    }
                },

                init() {
                    this.items = this.items.map((item, index) => ({
                        ...item,
                        uid: item.uid ?? index + 1,
                        famount: Number(this.parseDecimal(item.famount).toFixed(2)),
                        famountInput: this.formatDecimalInput(item.famount),
                    }));

                    window.addEventListener('account-picked', (event) => {
                        if (this.browseIndex === null || !this.items[this.browseIndex]) {
                            return;
                        }

                        const detail = event.detail || {};
                        this.items[this.browseIndex].faccount = (detail.faccount || '').toString().trim();
                        if (!this.isRefAllowed(this.items[this.browseIndex].faccount)) {
                            this.items[this.browseIndex].frefno = '';
                        }
                    }, {
                        passive: true
                    });
                }
            };
        }
    </script>
@endpush
