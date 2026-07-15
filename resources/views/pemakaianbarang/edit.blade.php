@extends('layouts.app')

@section('title', $action === 'delete' ? 'Pemakaian Barang - Delete' : 'Pemakaian Barang - Edit')

@section('content')
    @php
        $permissions = explode(',', session('user_restricted_permissions', ''));
        $canEditPermission = in_array('updatePemakaianBarang', $permissions, true);
        $canDeletePermission = in_array('deletePemakaianBarang', $permissions, true);
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

        .desc-inline-field {
            display: flex !important;
            width: 100%;
            min-width: 0;
            align-items: stretch;
            flex-wrap: nowrap !important;
        }

        .desc-inline-field__text {
            min-width: 0;
            flex: 1 1 auto;
        }

        .desc-inline-field__button {
            flex: 0 0 auto;
            width: 2.5rem;
            justify-content: center;
        }

        .readonly-mode input:not([type="hidden"]),
        .readonly-mode select,
        .readonly-mode textarea,
        .readonly-mode button {
            pointer-events: none;
        }

        .readonly-mode .allow-action,
        .readonly-mode .allow-action * {
            pointer-events: auto;
        }
        .pemakaianbarang-detail-table th,
        .pemakaianbarang-detail-table td {
            padding: .25rem .375rem !important;
        }

        .pemakaianbarang-detail-table input:not([type="hidden"]),
        .pemakaianbarang-detail-table select,
        .pemakaianbarang-detail-table button {
            min-height: 1.875rem;
            padding-top: .25rem !important;
            padding-bottom: .25rem !important;
            line-height: 1.25rem;
        }

        .pemakaianbarang-detail-table .rounded-l.border,
        .pemakaianbarang-detail-table .rounded-r.border {
            min-height: 1.875rem;
            padding-top: .25rem !important;
            padding-bottom: .25rem !important;
            line-height: 1.25rem;
        }

        .pemakaianbarang-detail-table button {
            display: inline-flex;
            align-items: center;
        }

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

    @php
        $usageLocked = !empty($isUsageLocked);
        $accountLookup = collect($accounts ?? [])->keyBy(fn ($account) => trim((string) ($account->faccount ?? '')));
        $subaccountLookup = collect($subaccounts ?? [])->keyBy(fn ($subaccount) => trim((string) ($subaccount->fsubaccountcode ?? '')));
        $oldPemakaianCodes = old('fitemcode', []);
        $oldPemakaianNames = old('fitemname', []);
        $oldPemakaianUnits = old('fsatuan', []);
        $oldPemakaianAccountCodes = old('frefdtno', []);
        $oldPemakaianSubAccountCodes = old('frefso', []);
        $oldPemakaianRefPrs = old('frefpr', []);
        $oldPemakaianQtys = old('fqty', []);
        $oldPemakaianDescs = old('fdesc', []);
        $oldPemakaianKetdts = old('fketdt', []);
        $initialEditPemakaianItems = [];

        foreach ($oldPemakaianCodes as $index => $itemCode) {
            $code = trim((string) $itemCode);
            $name = trim((string) ($oldPemakaianNames[$index] ?? ''));
            if ($code === '' && $name === '') {
                continue;
            }

            $unit = trim((string) ($oldPemakaianUnits[$index] ?? ''));
            $accountCode = trim((string) ($oldPemakaianAccountCodes[$index] ?? ''));
            $subAccountCode = trim((string) ($oldPemakaianSubAccountCodes[$index] ?? ''));
            $account = $accountLookup->get($accountCode);
            $subaccount = $subaccountLookup->get($subAccountCode);
            $accountName = trim((string) ($account->faccname ?? ''));
            $subaccountName = trim((string) ($subaccount->fsubaccountname ?? ''));

            $initialEditPemakaianItems[] = [
                'uid' => 'old-pemakaian-edit-' . $index,
                'fitemcode' => $code,
                'fitemname' => $name,
                'fitemid' => '',
                'units' => $unit !== '' ? [$unit] : [],
                'fsatuan' => $unit,
                'frefpr' => trim((string) ($oldPemakaianRefPrs[$index] ?? '')),
                'fqty' => (float) ($oldPemakaianQtys[$index] ?? 0),
                'fdesc' => (string) ($oldPemakaianDescs[$index] ?? ''),
                'fketdt' => (string) ($oldPemakaianKetdts[$index] ?? ''),
                'maxqty' => 0,
                'account_code' => $accountCode,
                'account_name' => $accountName,
                'account_label' => $accountCode !== '' ? trim($accountCode . ' - ' . $accountName) : '',
                'subaccount_code' => $subAccountCode,
                'subaccount_name' => $subaccountName,
                'subaccount_label' => $subAccountCode !== '' ? trim($subAccountCode . ' - ' . $subaccountName) : '',
            ];
        }
    @endphp

    @if ($usageLocked)
        <div x-data="{ open: true }" x-show="open" x-cloak class="fixed inset-0 z-[99] flex items-center justify-center"
            x-transition.opacity>
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
            <div class="relative bg-white w-[92vw] max-w-xl rounded-2xl shadow-2xl overflow-hidden allow-action">
                <div class="px-6 py-4 border-b border-orange-100 bg-orange-50 flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center">
                        <x-heroicon-o-lock-closed class="w-5 h-5 text-orange-600" />
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-bold text-orange-700">
                            {{ $action === 'delete' ? 'Pemakaian Barang Tidak Dapat Dihapus' : 'Pemakaian Barang Tidak Dapat Diedit' }}
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

    <div x-data="{ open: true }" class="{{ $action === 'delete' || $usageLocked ? 'readonly-mode' : '' }}">
        <div x-data="{
            open: true,
            accounts: @js($accounts),
            subaccounts: @js($subaccounts),
            savedItems: []
        }">
            <div>
                {{-- ============================================ --}}
                {{-- MODE DELETE: VIEW ONLY + BUTTON HAPUS       --}}
                {{-- ============================================ --}}
                @if ($action === 'delete')
                    {{-- ─── CARD 1: Identitas ────────────────────── --}}
                    <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                            <x-heroicon-o-identification class="w-5 h-5 text-blue-600" />
                            <h2 class="font-semibold text-gray-800">Identitas Pemakaian Barang</h2>
                        </div>
                        <div class="p-4">

                        {{-- HEADER FORM --}}
                        <div class="grid grid-cols-3 gap-3">
                            <div class="lg:col-span-4">
                                <label class="text-xs font-bold mb-1">Cabang</label>
                                <input type="text" class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 bg-gray-200 cursor-not-allowed"
                                    value="{{ trim(($fbranchcode ?? '') . ($fcabang ?? '' ? ' - ' . $fcabang : '')) }}" disabled>
                                <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                            </div>
                            <div class="lg:col-span-4" x-data="{ autoCode: true }">
                                <label class="text-xs font-bold mb-1">Transaksi#</label>
                                <div class="flex items-center gap-3">
                                    <input type="text" name="fstockmtno"
                                        value="{{ old('fstockmtno', $pemakaianbarang->fstockmtno) }}"
                                        class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" :disabled="autoCode"
                                        :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'">
                                    <label class="inline-flex items-center select-none">
                                        <input type="checkbox" x-model="autoCode" checked>
                                        <span class="ml-2 text-sm text-gray-700">Auto</span>
                                    </label>
                                </div>
                            </div>

                            <input type="hidden" name="fstockmtid" value="fstockmtid">

                            <div class="lg:col-span-4">
                                <label class="text-xs font-bold mb-1">Tanggal</label>
                                <input disabled type="date" name="fstockmtdate"
                                    value="{{ old('fstockmtdate') ?? date('Y-m-d') }}"
                                    class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 bg-gray-100 @error('fstockmtdate') border-red-500 @enderror">
                                @error('fstockmtdate')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Field FROM -->
                            <div class="lg:col-span-4">
                                <label class="text-xs font-bold mb-1">Gudang</label>
                                <div class="flex">
                                    <div class="relative flex-1">

                                        <select id="warehouseSelectFrom"
                                            class="w-full border-gray-300 rounded-l-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 bg-gray-100 text-gray-700 cursor-not-allowed"
                                            disabled>
                                            <option value=""></option>
                                            @foreach ($warehouses as $wh)
                                            <option value="{{ $wh->fwhcode }}" data-id="{{ $wh->fwhid }}"
                                                data-branch="{{ $wh->fbranchcode }}"
                                                {{ old('ffrom', $pemakaianbarang->ffrom) == $wh->fwhcode ? 'selected' : '' }}>
                                                    {{ $wh->fwhcode }} - {{ $wh->fwhname }}
                                                </option>
                                            @endforeach
                                        </select>

                                        {{-- Overlay untuk buka browser gudang --}}
                                        <div class="absolute inset-0" role="button" aria-label="Browse warehouse"
                                            @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open'))"></div>
                                    </div>
                                    <input type="hidden" name="ffrom" id="warehouseCodeHiddenFrom"
                                        value="{{ old('ffrom', $pemakaianbarang->ffrom) }}">

                                    {{-- Tombol-tombol Anda --}}
                                    <button type="button" disabled
                                        @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open'))"
                                        class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                                        title="Browse Gudang">
                                        <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                    </button>
                                    <a href="{{ route('gudang.create') }}" target="_blank" rel="noopener"
                                        class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50"
                                        title="Tambah Supplier">
                                        <x-heroicon-o-plus class="w-5 h-5" />
                                    </a>
                                </div>
                            </div>

                            <div class="lg:col-span-12">
                                <label class="text-xs font-bold mb-1">Keterangan</label>
                                <textarea readonly name="fket" rows="3"
                                    class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 bg-gray-100 @error('fket') border-red-500 @enderror"
                                    placeholder="Tulis keterangan tambahan di sini...">{{ old('fket', $pemakaianbarang->fket) }}</textarea>
                                @error('fket')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        </div>
                    </div>

                    {{-- ─── CARD 2: Detail Item ─────────────────────── --}}
                    <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                            <x-heroicon-o-list-bullet class="w-5 h-5 text-blue-600" />
                            <h2 class="font-semibold text-gray-800">Detail Item</h2>
                        </div>
                        <div class="p-4">

                        <div x-data="itemsTable()" x-init="init()" class="space-y-2">

                            <div class="overflow-auto border rounded">
                                <table class="pemakaianbarang-detail-table min-w-full text-sm balanced-detail-table"
                                    data-skip-auto-detail-style="true">
                                    <colgroup>
                                        <col style="width:2%;">
                                        <col style="width:12%;">
                                        <col style="width:25%;">
                                        <col style="width:20%;">
                                        <col style="width:20%;">
                                        <col style="width:8%;">
                                        <col style="width:13%;">
                                    </colgroup>
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="p-2 text-left w-10">#</th>
                                            <th class="p-2 text-left w-40">Kode Produk</th>
                                            <th class="p-2 text-left min-w-[12rem]">Nama Produk</th>
                                            <th class="p-2 text-left w-48">Account</th>
                                            <th class="p-2 text-left w-48">Sub Account</th>
                                            <th class="p-2 text-left w-24">Sat</th>
                                            <th class="p-2 text-right w-36 whitespace-nowrap">Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(it, i) in savedItems" :key="it.uid">
                                            <tr class="border-t align-top hover:bg-gray-55">
                                                <td class="p-2 text-gray-400" x-text="i + 1"></td>
                                                <td class="p-2">
                                                    <div class="px-2 py-1 text-sm text-gray-655 bg-gray-50 border rounded font-mono" x-text="it.fitemcode"></div>
                                                </td>
                                                <td class="p-2">
                                                    <div class="flex w-full max-w-full">
                                                        <div class="min-w-0 flex-1 rounded-l border bg-gray-101 px-2 py-1 text-sm leading-5 text-gray-650 whitespace-normal break-words"
                                                            x-text="it.fitemname"></div>
                                                        <button type="button" @click="openDesc(it, true)"
                                                            class="shrink-0 inline-flex items-center border border-l-0 rounded-r bg-slate-50 px-2 py-1 text-slate-700 hover:bg-slate-100 transition-colors border-slate-200"
                                                            :class="it.fdesc ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : ''"
                                                            title="Deskripsi item">
                                                            <x-heroicon-o-document-text class="h-4 w-4" />
                                                        </button>
                                                    </div>
                                                </td>
                                                <td class="p-2">
                                                    <div class="px-2 py-1 text-sm text-gray-650 bg-gray-50 border rounded" x-text="it.account_label"></div>
                                                </td>
                                                <td class="p-2">
                                                    <div class="px-2 py-1 text-sm text-gray-650 bg-gray-50 border rounded" x-text="it.subaccount_label"></div>
                                                </td>
                                                <td class="p-2">
                                                    <div class="px-2 py-1 text-sm text-gray-655 bg-gray-50 border rounded" x-text="it.fsatuan"></div>
                                                </td>
                                                <td class="p-2 text-right">
                                                    <div class="px-2 py-1 text-sm text-gray-700 bg-gray-50 border rounded text-right font-medium" x-text="fmt(it.fqty)"></div>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    </div>

                    {{-- ─── CARD 3: Approval & Aksi ────────────────── --}}
                    <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                        <div class="flex items-center justify-center gap-3 px-4 py-3 bg-gray-50 border-t border-gray-200 allow-action">
                        @if ($canDeletePermission)
                            <button type="button" onclick="showDeleteModal()"
                                @if ($usageLocked) disabled @endif
                                class="inline-flex items-center gap-2 px-5 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                                <x-heroicon-o-trash class="w-6 h-6" />
                                Hapus
                            </button>
                        @endif
                        <button type="button" onclick="window.location.href='{{ route('pemakaianbarang.index') }}'"
                            class="inline-flex items-center gap-2 px-5 py-2 border border-gray-300 bg-white text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-colors">
                            <x-heroicon-o-arrow-left class="w-6 h-6" />
                            Kembali
                        </button>
                    </div>
                    </div>

                    <div x-show="$store.pemakaianDesc.show" x-cloak class="fixed inset-0 z-[95] flex items-center justify-center"
                        x-transition.opacity>
                        <div class="absolute inset-0 bg-black/50" @click="$store.pemakaianDesc.close()"></div>
                        <div class="relative bg-white w-[92vw] max-w-lg rounded-2xl shadow-2xl overflow-hidden"
                            x-transition.scale>
                            <div class="px-5 py-4 border-b flex items-center">
                                <x-heroicon-o-document-text class="w-6 h-6 text-blue-600 mr-2" />
                                <h3 class="text-lg font-semibold text-gray-800">Deskripsi Item</h3>
                            </div>
                            <div class="px-5 py-4 space-y-4">
                                <div>
                                    <div class="mb-1 flex items-center justify-between gap-3">
                                        <div class="text-sm text-gray-700">Nama Produk</div>
                                        <button type="button" @click="$store.pemakaianDesc.copyName()"
                                            class="h-8 px-3 rounded-lg bg-blue-50 text-blue-700 text-sm font-medium hover:bg-blue-100">
                                            Copy
                                        </button>
                                    </div>
                                    <div class="rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800" x-text="$store.pemakaianDesc.itemName || '-'"></div>
                                </div>
                                <label class="block text-sm text-gray-700">Deskripsi</label>
                                <textarea x-model="$store.pemakaianDesc.value" rows="5"
                                    class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 bg-gray-100 cursor-not-allowed text-gray-600"
                                    readonly></textarea>
                            </div>
                            <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
                                <button type="button" @click="$store.pemakaianDesc.close()"
                                    class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">
                                    Tutup
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- ============================================ --}}
                    {{-- MODE EDIT: FORM EDITABLE                    --}}
                    {{-- ============================================ --}}
            @else
                <form action="{{ route('pemakaianbarang.update', $pemakaianbarang->fstockmtid) }}" method="POST"
                    class="mt-6" data-form-draft="true"
                    data-draft-key="pemakaianbarang:edit:{{ $pemakaianbarang->fstockmtid }}"
                    @submit="onSubmit($event)" x-data="{ showNoItems: false }">
                    @csrf
                    @method('PATCH')

                    {{-- ─── CARD 1: Identitas ────────────────────── --}}
                    <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                            <x-heroicon-o-identification class="w-5 h-5 text-blue-600" />
                            <h2 class="font-semibold text-gray-800">Identitas Pemakaian Barang</h2>
                        </div>
                        <div class="p-4">

                        {{-- HEADER FORM --}}
                        <div class="grid grid-cols-3 gap-3">
                            <div class="lg:col-span-4">
                                <label class="text-xs font-bold mb-1">Cabang</label>
                                <input type="text"
                                    class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 bg-gray-200 cursor-not-allowed"
                                    value="{{ trim(($fbranchcode ?? '') . ($fcabang ?? '' ? ' - ' . $fcabang : '')) }}" disabled>
                                <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                            </div>
                            <div class="lg:col-span-4" x-data="{ autoCode: true }">
                                <label class="text-xs font-bold mb-1">Transaksi#</label>
                                <div class="flex items-center gap-3">
                                    <input type="text" name="fstockmtno"
                                        value="{{ old('fstockmtno', $pemakaianbarang->fstockmtno) }}"
                                        class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" :disabled="autoCode"
                                        :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'">
                                    <label class="inline-flex items-center select-none">
                                        <input type="checkbox" x-model="autoCode" checked>
                                        <span class="ml-2 text-sm text-gray-700">Auto</span>
                                    </label>
                                </div>
                            </div>

                            <input type="hidden" name="fstockmtid" value="fstockmtid">

                            <div class="lg:col-span-4">
                                <label class="text-xs font-bold mb-1">Tanggal</label>
                                <input type="date" name="fstockmtdate"
                                    value="{{ old('fstockmtdate') ?? date('Y-m-d') }}"
                                    class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fstockmtdate') border-red-500 @enderror">
                                @error('fstockmtdate')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Field FROM -->
                            <div class="lg:col-span-4">
                                <label class="text-xs font-bold mb-1">Gudang</label>
                                <div class="flex">
                                    <div class="relative flex-1">

                                        <select id="warehouseSelectFrom"
                                            class="w-full border-gray-300 rounded-l-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 bg-gray-100 text-gray-700 cursor-not-allowed"
                                            disabled>
                                            <option value=""></option>
                                            @foreach ($warehouses as $wh)
                                            <option value="{{ $wh->fwhcode }}" data-id="{{ $wh->fwhid }}"
                                                data-branch="{{ $wh->fbranchcode }}"
                                                {{ old('ffrom', $pemakaianbarang->ffrom) == $wh->fwhcode ? 'selected' : '' }}>
                                                    {{ $wh->fwhcode }} - {{ $wh->fwhname }}
                                                </option>
                                            @endforeach
                                        </select>

                                        {{-- Overlay untuk buka browser gudang --}}
                                        <div class="absolute inset-0" role="button" aria-label="Browse warehouse"
                                            @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open'))"></div>
                                    </div>
                                    <input type="hidden" name="ffrom" id="warehouseCodeHiddenFrom"
                                        value="{{ old('ffrom', $pemakaianbarang->ffrom) }}">

                                    {{-- Tombol-tombol Anda --}}
                                    <button type="button"
                                        @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open'))"
                                        class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                                        title="Browse Gudang">
                                        <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                    </button>
                                    <a href="{{ route('gudang.create') }}" target="_blank" rel="noopener"
                                        class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50"
                                        title="Tambah Supplier">
                                        <x-heroicon-o-plus class="w-5 h-5" />
                                    </a>
                                </div>
                            </div>

                            <div class="lg:col-span-12">
                                <label class="text-xs font-bold mb-1">Keterangan</label>
                                <textarea name="fket" rows="3"
                                    class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fket') border-red-500 @enderror"
                                    placeholder="Tulis keterangan tambahan di sini...">{{ old('fket', $pemakaianbarang->fket) }}</textarea>
                                @error('fket')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        </div>
                    </div>

                    {{-- ─── CARD 2: Detail Item ─────────────────────── --}}
                    <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                            <x-heroicon-o-list-bullet class="w-5 h-5 text-blue-600" />
                            <h2 class="font-semibold text-gray-800">Detail Item</h2>
                        </div>
                        <div class="p-4">

                        <div x-data="itemsTable()" x-init="init()" class="space-y-2">

                            {{-- DETAIL ITEM (tabel input) --}}
                            <div class="overflow-auto border rounded">
                                <table class="pemakaianbarang-detail-table min-w-full text-sm balanced-detail-table"
                                        data-skip-auto-detail-style="true">
                                        <colgroup>
                                            <col style="width:2%;">
                                            <col style="width:12%;">
                                            <col style="width:25%;">
                                            <col style="width:20%;">
                                            <col style="width:20%;">
                                            <col style="width:8%;">
                                            <col style="width:8%;">
                                            <col style="width:5%;">
                                        </colgroup>
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th class="p-2 text-left w-10">#</th>
                                                <th class="p-2 text-left w-40">Kode Produk</th>
                                                <th class="p-2 text-left w-[18rem]">Nama Produk</th>
                                                <th class="p-2 text-left w-44">Account</th>
                                                <th class="p-2 text-left w-44">Sub Account</th>
                                                <th class="p-2 text-left w-20">Sat</th>
                                                <th class="p-2 text-right w-28 whitespace-nowrap">Qty</th>
                                                <th class="p-2 text-center w-24">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="(it, i) in savedItems" :key="it.uid || `item-${i}`">
                                                <tr class="border-t align-top hover:bg-gray-55">
                                                    <td class="p-2 text-gray-400" x-text="i + 1"></td>
                                                    <td class="p-2">
                                                        <div class="flex">
                                                            <input type="text" class="flex-1 border rounded-l px-2 py-1 font-mono text-sm focus:ring-1 focus:ring-blue-500 min-w-0 bg-white"
                                                                :id="'pemakaian_code_row_' + i"
                                                                x-model.trim="it.fitemcode"
                                                                @input="onCodeTypedRow(it, i)"
                                                                @keydown.enter.prevent="focusRowUnit(it, i)">
                                                            <button type="button" @click="openBrowseFor(i)"
                                                                class="shrink-0 border border-l-0 px-2 py-1 bg-white hover:bg-gray-55 text-gray-500 transition-colors"
                                                                title="Cari Produk">
                                                                <x-heroicon-o-magnifying-glass class="w-4 h-4 text-gray-500" />
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td class="p-2">
                                                        <div class="flex w-full max-w-full">
                                                            <div class="min-w-0 flex-1 rounded-l border bg-gray-101 px-2 py-1 text-sm leading-5 text-gray-655 whitespace-normal break-words"
                                                                x-text="it.fitemname"></div>
                                                            <button type="button" @click="openDesc(it)"
                                                                class="shrink-0 inline-flex items-center border border-l-0 rounded-r bg-slate-50 px-2 py-1 text-slate-700 hover:bg-slate-100 transition-colors border-slate-200"
                                                                :class="it.fdesc ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : ''"
                                                                title="Deskripsi item">
                                                                <x-heroicon-o-document-text class="h-4 w-4" />
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td class="p-2">
                                                        <select class="w-full border rounded px-2 py-1 select2 text-sm focus:ring-1 focus:ring-blue-500" :value="it.account_code"
                                                            x-init="window.initSelect2($el)"
                                                            @change="it.account_code = $event.target.value; it.account_name = $event.target.options[$event.target.selectedIndex].dataset.name; onRowUpdated(i)">
                                                            <option value="">Pilih Akun</option>
                                                            <template x-for="acc in accounts" :key="acc.faccount">
                                                                <option :value="acc.faccount" :data-name="acc.faccname"
                                                                    x-text="`${acc.faccount} - ${acc.faccname}`"
                                                                    :selected="it.account_code == acc.faccount"></option>
                                                            </template>
                                                        </select>
                                                    </td>
                                                    <td class="p-2">
                                                        <select class="w-full border rounded px-2 py-1 select2 text-sm focus:ring-1 focus:ring-blue-500" :value="it.subaccount_code"
                                                            x-init="window.initSelect2($el)"
                                                            @change="it.subaccount_code = $event.target.value; it.subaccount_name = $event.target.options[$event.target.selectedIndex].dataset.name; onRowUpdated(i)">
                                                            <option value="">Pilih Sub Akun</option>
                                                            <template x-for="sacc in subaccounts" :key="sacc.fsubaccountcode">
                                                                <option :value="sacc.fsubaccountcode" :data-name="sacc.fsubaccountname"
                                                                    x-text="`${sacc.fsubaccountcode} - ${sacc.fsubaccountname}`"
                                                                    :selected="it.subaccount_code == sacc.fsubaccountcode"></option>
                                                            </template>
                                                        </select>
                                                    </td>
                                                    <td class="p-2">
                                                        <template x-if="it.units && it.units.length > 1">
                                                            <select class="w-full border rounded px-2 py-1 text-sm focus:ring-1 focus:ring-blue-500"
                                                                :id="'pemakaian_unit_row_' + i"
                                                                x-model="it.fsatuan"
                                                                @change="onRowUpdated(i)"
                                                                @keydown.enter.prevent="focusRowQty(i)">
                                                                <template x-for="u in it.units" :key="u">
                                                                    <option :value="u" x-text="u" :selected="it.fsatuan == u"></option>
                                                                </template>
                                                            </select>
                                                        </template>
                                                        <template x-if="!it.units || it.units.length <= 1">
                                                            <div class="px-2 py-1 text-sm text-gray-655 bg-gray-50 border rounded"
                                                                x-text="it.fsatuan || '-'"></div>
                                                        </template>
                                                    </td>
                                                    <td class="p-2 text-right">
                                                        <input type="number" class="w-full border rounded px-2 py-1 text-right text-sm focus:ring-1 focus:ring-blue-500 bg-white"
                                                            min="0" step="0.01"
                                                            :id="'pemakaian_qty_row_' + i"
                                                            x-model.number="it.fqty"
                                                            @input="onRowUpdated(i)"
                                                            @change="onRowUpdated(i)">
                                                    </td>
                                                    <td class="p-2 text-center text-xs">
                                                        <button type="button" @click="removeSaved(i)"
                                                            class="inline-flex h-8 w-8 items-center justify-center rounded bg-red-100 text-red-600 hover:bg-red-200 transition-colors"
                                                            title="Hapus baris">-</button>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                            </div>

                                {{-- Hidden submit rows --}}
                                <div class="hidden">
                                    <template x-for="(it, i) in submitItems" :key="'submit-pemakaian-' + (it.uid || i)">
                                        <div>
                                            <input type="hidden" name="fitemcode[]" :value="it.fitemcode">
                                            <input type="hidden" name="fitemname[]" :value="it.fitemname">
                                            <input type="hidden" name="fsatuan[]" :value="it.fsatuan">
                                            <input type="hidden" name="frefdtno[]" :value="it.account_code">
                                            <input type="hidden" name="frefso[]" :value="it.subaccount_code">
                                            <input type="hidden" name="frefpr[]" :value="it.frefpr">
                                            <input type="hidden" name="fqty[]" :value="it.fqty">
                                            <input type="hidden" name="fdesc[]" :value="it.fdesc">
                                            <input type="hidden" name="fketdt[]" :value="it.fketdt">
                                        </div>
                                    </template>
                                    <input type="hidden" id="itemsCount" :value="submitItems.length">
                                </div>
                        </div>
                        </div>
                    </div>

                        <div x-show="$store.pemakaianDesc.show" x-cloak class="fixed inset-0 z-[95] flex items-center justify-center"
                            x-transition.opacity>
                            <div class="absolute inset-0 bg-black/50" @click="$store.pemakaianDesc.close()"></div>
                            <div class="relative bg-white w-[92vw] max-w-lg rounded-2xl shadow-2xl overflow-hidden"
                                x-transition.scale>
                                <div class="px-5 py-4 border-b flex items-center">
                                    <x-heroicon-o-document-text class="w-6 h-6 text-blue-600 mr-2" />
                                    <h3 class="text-lg font-semibold text-gray-800">Isi Deskripsi Item</h3>
                                </div>
                                <div class="px-5 py-4 space-y-4">
                                    <div>
                                        <div class="mb-1 flex items-center justify-between gap-3">
                                            <div class="text-sm text-gray-700">Nama Produk</div>
                                            <button x-show="!$store.pemakaianDesc.readonly" type="button" @click="$store.pemakaianDesc.copyName()"
                                                class="h-8 px-3 rounded-lg bg-blue-50 text-blue-700 text-sm font-medium hover:bg-blue-100">
                                                Copy
                                            </button>
                                        </div>
                                        <div class="rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800" x-text="$store.pemakaianDesc.itemName || '-'"></div>
                                    </div>
                                    <label class="block text-sm text-gray-700">Deskripsi</label>
                                    <textarea x-model="$store.pemakaianDesc.value" rows="5" class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                        :readonly="$store.pemakaianDesc.readonly"
                                        :class="$store.pemakaianDesc.readonly ? 'bg-gray-100 cursor-not-allowed text-gray-600' : ''"
                                        placeholder="Tulis deskripsi item di sini..."></textarea>
                                </div>
                                <div class="px-5 py-3 border-t flex items-center justify-end gap-3">
                                    <button type="button" @click="$store.pemakaianDesc.close()"
                                        class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">
                                        Tutup
                                    </button>
                                    <button x-show="!$store.pemakaianDesc.readonly" type="button" @click="$store.pemakaianDesc.apply()"
                                        class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
                                        Simpan
                                    </button>
                                </div>
                            </div>
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

                        {{-- MODAL GUDANG --}}
                        <div x-data="warehouseBrowser()" x-show="open" x-cloak x-transition.opacity
                            class="fixed inset-0 z-50 flex items-center justify-center p-4">
                            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

                            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-6xl flex flex-col overflow-hidden"
                                style="height: 650px;">
                                <!-- Header -->
                                <div
                                    class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-800">Browse Gudang</h3>
                                        <p class="text-sm text-gray-500 mt-0.5">Pilih gudang yang diinginkan</p>
                                    </div>
                                    <button type="button" @click="close()"
                                        class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                                        Tutup
                                    </button>
                                </div>

                                <!-- Search & Length Menu -->
                                <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                                    <div id="warehouseTableControls"></div>
                                </div>

                                <!-- Table with fixed height and scroll -->
                                <div class="flex-1 overflow-y-auto px-6" style="min-height: 0;">
                                    <div class="bg-white">
                                        <table id="warehouseTable" class="min-w-full text-sm display stripe hover"
                                            style="width:100%">
                                            <thead class="sticky top-0 z-10">
                                                <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                                    <th
                                                        class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                        Branch</th>
                                                    <th
                                                        class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                        Kode Gudang</th>
                                                    <th
                                                        class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                        Nama Gudang</th>
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
                                    <div id="warehouseTablePagination"></div>
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
                                        <table id="productTable" class="min-w-full text-sm display nowrap stripe hover"
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

                    {{-- ─── CARD 3: Approval & Aksi ────────────────── --}}
                    <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                        <div class="flex items-center justify-end gap-3 px-4 py-3 bg-gray-50 border-t border-gray-200">
                            <button type="button" @click="window.location.href='{{ route('pemakaianbarang.index') }}'"
                                class="inline-flex items-center gap-2 px-5 py-2 border border-gray-300 bg-white text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-colors">
                                <x-heroicon-o-arrow-left class="w-6 h-6" />
                                Keluar
                            </button>
                            @if ($canEditPermission)
                                <button type="submit"
                                    @if ($usageLocked) disabled @endif
                                    class="inline-flex items-center gap-2 px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                                    <x-heroicon-o-check class="w-6 h-6" /> Simpan
                                </button>
                            @endif
                        </div>
                    </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
    {{-- ============================================ --}}
    {{-- MODAL & TOAST (HANYA UNTUK MODE DELETE)     --}}
    {{-- ============================================ --}}
    @if ($action === 'delete' && $canDeletePermission)
        {{-- Modal Delete --}}
        <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6 allow-action">
                <h3 class="text-lg font-semibold mb-4">Konfirmasi Hapus pemakaianbarang ini?</h3>
                <form id="deleteForm" action="{{ route('pemakaianbarang.destroy', $pemakaianbarang->fstockmtid) }}"
                    method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="flex justify-end space-x-2">
                        <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400"
                            id="btnTidak">
                            Tidak
                        </button>
                        </button>
                        <button type="submit" @if ($usageLocked) disabled @endif class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-60 disabled:cursor-not-allowed">
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

                fetch('{{ route('pemakaianbarang.destroy', $pemakaianbarang->fstockmtid) }}', {
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
                        showToast(data.message || 'Data berhasil dihapus.', true);

                        setTimeout(() => {
                            window.location.href = '{{ route('pemakaianbarang.index') }}';
                        }, 500);
                    })
                    .catch(error => {
                        btnYa.disabled = false;
                        btnTidak.disabled = false;
                        btnYa.textContent = 'Ya, Hapus';
                        showToast('Terjadi kesalahan saat hapus data.', false);
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
    div#warehouseTable_length select,
    .dataTables_wrapper #warehouseTable_length select,
    table#warehouseTable+.dataTables_wrapper .dataTables_length select {
        min-width: 140px !important;
        width: auto !important;
        padding: 8px 45px 8px 16px !important;
        font-size: 14px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.375rem !important;
    }

    /* Wrapper length */
    div#warehouseTable_length,
    .dataTables_wrapper #warehouseTable_length,
    .dataTables_wrapper .dataTables_length {
        min-width: 250px !important;
    }

    /* Label wrapper */
    div#warehouseTable_length label,
    .dataTables_wrapper #warehouseTable_length label,
    .dataTables_wrapper .dataTables_length label {
        font-size: 14px !important;
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
    }


    /* Targeting lebih spesifik untuk length select */
    div#productTable_length select,
    .dataTables_wrapper #productTable_length select,
    table#supplierBrowseTable+.dataTables_wrapper .dataTables_length select {
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
</style>
{{-- DATA & SCRIPTS --}}

@push('scripts')
    <script>
        window.initSelect2 = function(sel = '.select2', selectedValue = null) {
            if (!window.jQuery || !$.fn || !$.fn.select2) return;

            const $select = $(sel);
            if (!$select.length) return;

            $select.select2({
                width: '100%'
            });

            if (selectedValue !== null && selectedValue !== undefined && selectedValue !== '') {
                $select.val(selectedValue).trigger('change.select2');
            }
        };

        window.syncSelect2Value = function(el, value) {
            if (!window.jQuery || !$.fn || !$.fn.select2) return;

            const $select = $(el);
            if (!$select.length || !$select.hasClass('select2-hidden-accessible')) return;
            const normalized = value ?? '';
            if (($select.val() ?? '') !== normalized) {
                $select.val(normalized).trigger('change.select2');
            }
        };

        $(document).ready(function() {
            // Bridge: Select2 -> Alpine
            $(document).on('select2:select select2:clear', 'select', function() {
                this.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
                this.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            });
            window.initSelect2();
        });

        // Map produk untuk auto-fill tabel
        window.PRODUCT_MAP = {
            @foreach ($products as $p)
                "{{ $p->fprdcode }}": {
                    id: @json($p->fprdid),
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
            Alpine.store('pemakaianDesc', {
                show: false,
                value: '',
                itemName: '',
                readonly: false,
                target: null,
                open(targetRow, readonly = false) {
                    if (!targetRow) return;
                    this.target = targetRow;
                    this.value = (targetRow?.fdesc || '').toString();
                    this.itemName = (targetRow?.fitemname || '').toString();
                    this.readonly = !!readonly;
                    this.show = true;
                },
                copyName() {
                    this.value = this.itemName || '';
                },
close() {
                    this.show = false;
                    this.value = '';
                    this.itemName = '';
                    this.readonly = false;
                    this.target = null;
                },
                apply() {
                    if (!this.readonly && this.target) {
                        this.target.fdesc = this.value;
                    }
                    this.close();
                }
            });
        });

    function itemsTable() {
        return {
            showNoItems: false,
            savedItems: @json(count($initialEditPemakaianItems) ? $initialEditPemakaianItems : $savedItems),
            minimumVisibleRows: 5,
            browseTarget: 'extra',
            browseRow: null,
            totalHarga: 0,

            fmt(n) {
                if (n === null || n === undefined || n === '') return '-';
                const v = Number(n);
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

            fmtMoney(value) {
                return this.fmt(value);
            },

            recalc(row) {
                this.$nextTick(() => {
                    row.fqty = @json(stock_boleh_minus()) ? (Number(row.fqty) || 0) : Math.max(0, Number(row.fqty) || 0);
                    row.fterima = Math.max(0, Number(row.fterima) || 0);
                    row.fprice = Math.max(0, Number(row.fprice) || 0);
                    row.ftotal = Number((row.fqty * row.fprice).toFixed(2));
                    this.recalcTotals();
                });
            },

            recalcTotals() {
                this.totalHarga = (this.savedItems || []).reduce((sum, it) => {
                    const v = Number(it?.ftotal ?? 0);
                    return sum + (Number.isFinite(v) ? v : 0);
                }, 0);
            },

            removeSaved(i) {
                this.savedItems.splice(i, 1);
                this.ensureMinimumRows();
                this.ensureTrailingRow();
                this.recalcTotals();
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
                row.fitemid = meta.id || '';
                row.fitemname = meta.name || '';
                const units = [...new Set((meta.units || []).map(u => (u ?? '').toString().trim()).filter(Boolean))];
                row.units = units;
                if (!units.includes(row.fsatuan)) row.fsatuan = units[0] || '';
                const stock = Number.isFinite(+meta.stock) && +meta.stock > 0 ? +meta.stock : 0;
                row.maxqty = stock;
            },

            rowHasContent(row) {
                if (!row) return false;
                return [
                    row.fitemcode,
                    row.fitemname,
                    row.account_code,
                    row.subaccount_code,
                    row.frefpr,
                    row.fdesc,
                    row.fketdt,
                ].some(value => String(value ?? '').trim() !== '') ||
                    Number(row.fqty ?? 0) !== 0;
            },

            isRowSavable(row) {
                return !!(row && row.fitemcode && row.fitemname && row.fsatuan && (@json(stock_boleh_minus()) ? Number(row.fqty) !== 0 : Number(row.fqty) > 0));
            },

            ensureMinimumRows() {
                while (this.savedItems.length < this.minimumVisibleRows) {
                    this.savedItems.push(this.createRow());
                }
            },

            ensureTrailingRow(index = null) {
                if (!this.savedItems.length) {
                    this.ensureMinimumRows();
                    return;
                }

                const targetIndex = index === null ? this.savedItems.length - 1 : index;
                if (targetIndex !== this.savedItems.length - 1) return;

                if (this.rowHasContent(this.savedItems[targetIndex])) {
                    this.savedItems.push(this.createRow());
                }
            },

            onCodeTypedRow(row, index = null) {
                this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode));
                this.recalc(row);
                this.onRowUpdated(index);
            },

            onRowUpdated(index) {
                this.ensureTrailingRow(index);
                this.recalcTotals();
            },

            onPrPicked(e) {
                const {
                    header,
                    items
                } = e.detail || {};
                if (!items || !Array.isArray(items)) return;

                this.addManyFromPR(header, items);
            },

            addManyFromPR(header, items) {
                const existing = new Set(this.getCurrentItemKeys());
                let added = 0;

                items.forEach(src => {
                    const row = {
                        ...this.createRow(),
                        fitemcode: src.fitemcode ?? '',
                        fitemid: src.fitemid ?? '',
                        fitemname: src.fitemname ?? '',
                        fsatuan: src.fsatuan ?? '',
                        frefpr: src.frefpr ?? (header?.fpono ?? ''),
                        fqty: Number(src.fqty ?? 0),
                        fdesc: src.fdesc ?? '',
                        fketdt: src.fketdt ?? '',
                        units: Array.isArray(src.units) && src.units.length ? src.units : [src.fsatuan].filter(Boolean),
                    };

                    const key = this.itemKey(row);
                    if (existing.has(key)) return;

                    // find first empty row to replace, or push
                    const emptyIdx = this.savedItems.findIndex(r => !this.rowHasContent(r));
                    if (emptyIdx !== -1) {
                        this.savedItems.splice(emptyIdx, 1, row);
                    } else {
                        this.savedItems.push(row);
                    }
                    existing.add(key);
                    added++;
                });

                this.ensureMinimumRows();
                this.ensureTrailingRow();
                this.recalcTotals();
            },

            onSubmit($event) {
                const duplicateCode = window.getPemakaianBarangDuplicateCode?.($event.target);
                if (duplicateCode) {
                    $event.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Produk Duplikat',
                        text: `Kode produk ${duplicateCode} tidak boleh sama dalam satu Pemakaian Barang.`,
                        confirmButtonText: 'OK',
                        customClass: {
                            confirmButton: 'bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700'
                        }
                    });
                    return;
                }
                if (this.submitItems.length === 0) {
                    $event.preventDefault();
                    this.showNoItems = true;
                    return;
                }
                return window.submitFormWithStockMinusConfirmation?.($event);
            },

            focusRowUnit(row, index) {
                if (row?.units?.length > 1) {
                    document.getElementById(`pemakaian_unit_row_${index}`)?.focus();
                    return;
                }
                this.focusRowQty(index);
            },

            focusRowQty(index) {
                document.getElementById(`pemakaian_qty_row_${index}`)?.focus();
            },

            openDesc(targetRow, readonly = false) {
                Alpine.store('pemakaianDesc').open(targetRow, readonly);
            },

            itemKey(it) {
                return `${(it.fitemcode ?? '').toString().trim()}::${(it.account_code ?? '').toString().trim()}`;
            },

            getCurrentItemKeys() {
                return this.submitItems.map(it => this.itemKey(it));
            },

            get submitItems() {
                return this.savedItems.filter(row => this.isRowSavable(row));
            },

            createRow() {
                return {
                    ...newRow(),
                    uid: cryptoRandom(),
                };
            },

            init() {
                window.getCurrentItemKeys = () => this.getCurrentItemKeys();

                window.addEventListener('pr-picked', this.onPrPicked.bind(this), {
                    passive: true
                });

                window.addEventListener('product-chosen', (e) => {
                    const {
                        product
                    } = e.detail || {};
                    if (!product) return;

                    const row = this.browseRow || this.savedItems[this.savedItems.length - 1];
                    if (row) {
                        row.fitemcode = (product.fprdcode || '').toString();
                        this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode));
                        if (!row.fqty) row.fqty = 1;
                        this.recalc(row);
                        this.onRowUpdated(this.savedItems.indexOf(row));
                    }
                }, {
                    passive: true
                });

                // Initialize empty rows if needed
                this.savedItems = (Array.isArray(this.savedItems) ? this.savedItems : []).map(item => ({
                    ...this.createRow(),
                    ...item,
                    uid: item?.uid || cryptoRandom(),
                }));
                this.savedItems.forEach(row => {
                    this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode));
                    this.recalc(row);
                });
                this.ensureMinimumRows();
                this.ensureTrailingRow();
            },

            openBrowseFor(index) {
                this.browseRow = this.savedItems[index];
                window.dispatchEvent(new CustomEvent('browse-open', {
                    detail: {
                        forEdit: true
                    }
                }));
            },
        };

        function newRow() {
            return {
                uid: null,
                fitemcode: '',
                fitemid: '',
                fitemname: '',
                units: [],
                fsatuan: '',
                frefpr: '',
                fqty: 0,
                fdesc: '',
                fketdt: '',
                maxqty: 0,
                account_code: '',
                account_name: '',
                account_label: '',
                subaccount_code: '',
                subaccount_name: '',
                subaccount_label: '',
            };
        }

        function cryptoRandom() {
            return (window.crypto?.getRandomValues ? [...window.crypto.getRandomValues(new Uint32Array(2))].map(n => n
                    .toString(16)).join('') :
                Math.random().toString(36).slice(2)) + Date.now();
        }
    }

        window.getPemakaianBarangDuplicateCode = function(form) {
            const seen = new Set();
            const inputs = Array.from(form.querySelectorAll('input[name="fitemcode[]"]'));

            for (const input of inputs) {
                const code = (input.value || '').toString().trim().toUpperCase();
                if (!code) {
                    continue;
                }

                if (seen.has(code)) {
                    return code;
                }

                seen.add(code);
            }

            return '';
        };

        // Warehouse Browser dengan DataTables
        window.warehouseBrowser = function() {
            return {
                open: false,
                table: null,

                initDataTable() {
                    if (this.table) {
                        this.table.columns.adjust().draw(false);
                        return;
                    }
                    $('#warehouseTable').off('click.whpick');
                    this.table = $('#warehouseTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "{{ route('gudang.browse') }}",
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
                                data: 'fbranchcode',
                                name: 'fbranchcode',
                                className: 'text-sm',
                                width: '15%',
                                render: function(data) {
                                    return data || '<span class="text-gray-400">-</span>';
                                }
                            },
                            {
                                data: 'fwhcode',
                                name: 'fwhcode',
                                className: 'font-mono text-sm font-semibold',
                                width: '20%'
                            },
                            {
                                data: 'fwhname',
                                name: 'fwhname',
                                className: 'text-sm',
                                width: '50%'
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
                        scrollX: false,
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
                    $('#warehouseTable').on('click.whpick', '.btn-choose', (e) => {
                        e.preventDefault();
                        e.stopPropagation();

                        const data = this.table?.row($(e.currentTarget).closest('tr')).data();
                        if (!data) return;
                        this.choose(data);
                    });
                },

                openModal() {
                    this.open = true;
                    this.$nextTick(() => {
                        this.initDataTable();
                    });
                },

                close() {
                    this.open = false;
                    if (this.table) {
                        this.table.search('').draw();
                    }
                },

                choose(w) {
                    window.dispatchEvent(new CustomEvent('warehouse-picked', {
                        detail: {
                            fwhid: w.fwhid,
                            fwhcode: w.fwhcode,
                            fwhname: w.fwhname,
                            fbranchcode: w.fbranchcode
                        }
                    }));
                    this.close();
                },

                init() {
                    window.addEventListener('warehouse-browse-open', () => this.openModal());
                }
            }
        };

        // Helper: update field saat warehouse-picked
        document.addEventListener('DOMContentLoaded', () => {
            window.addEventListener('warehouse-picked', (ev) => {
                const {
                    fwhcode,
                    fwhid
                } = ev.detail || {};
                const sel = document.getElementById('warehouseSelectFrom');
                const hid = document.getElementById('warehouseCodeHiddenFrom');
                if (sel) {
                    sel.value = fwhcode || '';
                    sel.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                }
                if (hid) hid.value = fwhcode || '';
            });
        });

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
