@extends('layouts.app')

@section('title', $action === 'delete' ? 'Mutasi Stock - Delete' : ($action === 'view' ? 'Mutasi Stock - View' : 'Mutasi Stock - Edit'))

@section('content')
    @php
        $permissions = explode(',', session('user_restricted_permissions', ''));
        $canEditPermission = in_array('updatePenerimaanBarang', $permissions, true);
        $canDeletePermission = in_array('deletePenerimaanBarang', $permissions, true);
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
        .mutasi-detail-table th,
        .mutasi-detail-table td {
            padding: .25rem .375rem !important;
        }

        .mutasi-detail-table input:not([type="hidden"]),
        .mutasi-detail-table select,
        .mutasi-detail-table button {
            min-height: 1.875rem;
            padding-top: .25rem !important;
            padding-bottom: .25rem !important;
            line-height: 1.25rem;
        }

        .mutasi-detail-table .rounded-l.border,
        .mutasi-detail-table .rounded-r.border {
            min-height: 1.875rem;
            padding-top: .25rem !important;
            padding-bottom: .25rem !important;
            line-height: 1.25rem;
        }

        .mutasi-detail-table button {
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
        $oldMutasiCodes = old('fitemcode', []);
        $oldMutasiNames = old('fitemname', []);
        $oldMutasiUnits = old('fsatuan', []);
        $oldMutasiRefs = old('frefdtno', []);
        $oldMutasiRefPrs = old('frefpr', []);
        $oldMutasiRefSos = old('frefso', []);
        $oldMutasiQtys = old('fqty', []);
        $oldMutasiDescs = old('fdesc', []);
        $oldMutasiKetdts = old('fketdt', []);
        $initialEditMutasiItems = [];

        foreach ($oldMutasiCodes as $index => $itemCode) {
            $code = trim((string) $itemCode);
            $name = trim((string) ($oldMutasiNames[$index] ?? ''));
            if ($code === '' && $name === '') {
                continue;
            }

            $unit = trim((string) ($oldMutasiUnits[$index] ?? ''));
            $initialEditMutasiItems[] = [
                'uid' => 'old-mutasi-edit-' . $index,
                'fitemcode' => $code,
                'fitemname' => $name,
                'units' => $unit !== '' ? [$unit] : [],
                'fsatuan' => $unit,
                'frefdtno' => trim((string) ($oldMutasiRefs[$index] ?? '')),
                'frefpr' => trim((string) ($oldMutasiRefPrs[$index] ?? '')),
                'frefso' => trim((string) ($oldMutasiRefSos[$index] ?? '')),
                'frefnoacak' => trim((string) ($oldMutasiRefs[$index] ?? '')),
                'fqty' => (float) ($oldMutasiQtys[$index] ?? 0),
                'fprice' => 0,
                'fdesc' => (string) ($oldMutasiDescs[$index] ?? ''),
                'fketdt' => (string) ($oldMutasiKetdts[$index] ?? ''),
                'maxqty' => 0,
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
                            {{ $action === 'delete' ? 'Mutasi Stock Tidak Dapat Dihapus' : 'Mutasi Stock Tidak Dapat Diedit' }}
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

    <div x-data="{
        includePPN: false,
        ppnRate: 0,
        ppnAmount: 0,
        totalHarga: 100000,
        showNoItems: false,
        savedItems: []
    }" {{ $action === 'delete' || $action === 'view' || $usageLocked ? 'readonly-mode' : '' }}>
        @if ($action === 'delete' || $action === 'view' || $usageLocked)
            {{-- ─── CARD 1: Identitas (Delete) ──────────────── --}}
            <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
    <div class="flex items-center gap-2 px-4 pt-3 pb-0">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
            viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
        </svg>
        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Identitas Mutasi Stock</p>
    </div>
    <div class="p-4">
        <div class="grid grid-cols-3 gap-3">
            <div class="lg:col-span-4">
                <label class="block text-xs font-bold mb-1">Cabang</label>
                <input type="text"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                    value="{{ trim(($fbranchcode ?? '') . ($fcabang ?? '' ? ' - ' . $fcabang : '')) }}" disabled>
                <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
            </div>
            <div class="lg:col-span-4">
                <label class="block text-xs font-bold mb-1">Transaksi#</label>
                <input type="text"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                    value="{{ old('fstockmtno', $mutasi->fstockmtno) }}" disabled>
                <input type="hidden" name="fstockmtno" value="{{ old('fstockmtno', $mutasi->fstockmtno) }}">
            </div>

            <input type="hidden" name="fstockmtid" value="{{ $mutasi->fstockmtid }}">

            <!-- Tanggal - styled like Cabang (assembling) -->
            <div class="lg:col-span-4">
                <label class="block text-xs font-bold mb-1">Tanggal</label>
                <input type="date"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                    value="{{ old('fstockmtdate') ?? date('Y-m-d') }}" disabled>
                <input type="hidden" name="fstockmtdate" value="{{ old('fstockmtdate') ?? date('Y-m-d') }}">
            </div>

            <!-- Field FROM - styled like Cabang (assembling) -->
            <div class="lg:col-span-4">
                <label class="block text-xs font-bold mb-1">Gudang (Dari)</label>
                <div class="flex">
                    <div class="relative flex-1">
                        <select id="warehouseSelectFrom"
                            class="w-full border border-gray-300 rounded-l-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                            disabled>
                            <option value=""></option>
                            @foreach (($fromWarehouses ?? $warehouses) as $wh)
                                <option value="{{ $wh->fwhcode }}" data-id="{{ $wh->fwhid }}"
                                    data-branch="{{ $wh->fbranchcode }}"
                                    {{ old('ffrom', $mutasi->ffrom) == $wh->fwhcode ? 'selected' : '' }}>
                                    {{ $wh->fwhcode }} - {{ $wh->fwhname }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <input type="hidden" name="ffrom" id="warehouseCodeHiddenFrom"
                        value="{{ old('ffrom', $mutasi->ffrom) }}">

                    <button type="button" disabled
                        class="border border-gray-300 -ml-px px-3 py-2 bg-gray-100 text-gray-500 cursor-not-allowed rounded-r-lg"
                        title="Browse Gudang">
                        <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <!-- Field TO - styled like Cabang (assembling) -->
            <div class="lg:col-span-4">
                <label class="block text-xs font-bold mb-1">Gudang (Tujuan)</label>
                <div class="flex">
                    <div class="relative flex-1">
                        <select id="warehouseSelectTo"
                            class="w-full border border-gray-300 rounded-l-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                            disabled>
                            <option value=""></option>
                            @foreach (($fromWarehouses ?? $warehouses) as $wh)
                                <option value="{{ $wh->fwhcode }}" data-id="{{ $wh->fwhid }}"
                                    data-branch="{{ $wh->fbranchcode }}"
                                    {{ old('fto', $mutasi->fto) == $wh->fwhcode ? 'selected' : '' }}>
                                    {{ $wh->fwhcode }} - {{ $wh->fwhname }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <input type="hidden" name="fto" id="warehouseCodeHiddenTo"
                        value="{{ old('fto', $mutasi->fto) }}">

                    <button type="button" disabled
                        class="border border-gray-300 -ml-px px-3 py-2 bg-gray-100 text-gray-500 cursor-not-allowed rounded-r-lg"
                        title="Browse Gudang">
                        <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <!-- Keterangan - styled like Cabang (assembling) -->
            <div class="lg:col-span-12">
                <label class="block text-xs font-bold mb-1">Keterangan</label>
                <textarea rows="3"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                    disabled>{{ old('fket', $mutasi->fket) }}</textarea>
                <input type="hidden" name="fket" value="{{ old('fket', $mutasi->fket) }}">
            </div>
        </div>
    </div>
</div>

            {{-- ─── CARD 2: Detail Item (Delete) ───────────── --}}
            <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                <div class="flex items-center gap-2 px-4 pt-3 pb-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Detail Item</p>
                </div>
                <div class="p-4">
                    <div x-data="itemsTable()" x-init="init()" class="space-y-2">

                            {{-- DETAIL ITEM (tabel input) --}}
                            <h3 class="text-base font-semibold text-gray-800">Detail Item</h3>

                            <div class="overflow-auto border rounded">
                                <table class="mutasi-detail-table min-w-full text-sm balanced-detail-table"
                                    data-skip-auto-detail-style="true">
                                    <colgroup>
                                        <col style="width:2%;">
                                        <col style="width:18%;">
                                        <col style="width:50%;">
                                        <col style="width:12%;">
                                        <col style="width:18%;">
                                    </colgroup>
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="p-2 text-left w-10">#</th>
                                            <th class="p-2 text-left w-40">Kode Produk</th>
                                            <th class="p-2 text-left w-[20rem]">Nama Produk</th>
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
                                                    <div class="px-2 py-1 text-sm text-gray-650 bg-gray-50 border rounded" x-text="it.fsatuan"></div>
                                                </td>
                                                <td class="p-2 text-right">
                                                    <div class="px-2 py-1 text-sm text-gray-700 bg-gray-50 border rounded text-right font-medium" x-text="fmt(it.fqty)"></div>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                            <input type="hidden" id="itemsCount" :value="submitItems.length">
                    </div> {{-- End itemsTable delete --}}
                </div> {{-- End CARD 2 body --}}
            </div> {{-- End CARD 2 --}}

                        <!-- MODAL DESC (di dalam itemsTable) -->
                        <div x-show="$store.mutasiDesc.show" x-cloak
                            class="fixed inset-0 z-[95] flex items-center justify-center" x-transition.opacity>
                            <div class="absolute inset-0 bg-black/50" @click="$store.mutasiDesc.close()"></div>

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
                                            <button x-show="!$store.mutasiDesc.readonly" type="button" @click="$store.mutasiDesc.copyName()"
                                                class="h-8 px-3 rounded-lg bg-blue-50 text-blue-700 text-sm font-medium hover:bg-blue-100">
                                                Copy
                                            </button>
                                        </div>
                                        <div class="rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800" x-text="$store.mutasiDesc.itemName || '-'"></div>
                                    </div>
                                    <label class="block text-sm text-gray-700">Deskripsi</label>
                                    <textarea x-model="$store.mutasiDesc.value" rows="5" class="w-full border rounded px-3 py-2"
                                        :readonly="$store.mutasiDesc.readonly"
                                        :class="$store.mutasiDesc.readonly ? 'bg-gray-100 cursor-not-allowed text-gray-600' : ''"
                                        placeholder="Tulis deskripsi item di sini..."></textarea>
                                </div>

                                <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
                                    <button type="button" @click="$store.mutasiDesc.close()"
                                        class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">
                                        Tutup
                                    </button>
                                    <button x-show="!$store.mutasiDesc.readonly" type="button" @click="$store.mutasiDesc.apply()"
                                        class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
                                        Simpan
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- MODAL ERROR: belum ada item --}}
                        <div x-show="showNoItems && submitItems.length === 0" x-cloak
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
                                        Anda belum menambahkan item apa pun pada tabel. Silakan isi baris â€œDetail Itemâ€
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
                        <div x-data="warehouseBrowser()" x-init="init()" x-show="open" x-cloak x-transition.opacity
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
                        <div x-data="productBrowser()" x-init="init()" x-show="open" x-cloak x-transition.opacity
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
            {{-- ─── CARD 3: Aksi (Delete) ──────────────────── --}}
            <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                <div class="p-4 flex items-center justify-end gap-3 allow-action">
                    @if ($action === 'delete')
                    <button type="button" onclick="showDeleteModal()"
                    @if ($usageLocked) disabled @endif
                    class="inline-flex h-9 items-center justify-center rounded-lg bg-red-600 px-4 text-xs font-semibold text-white shadow-sm hover:bg-red-700 disabled:opacity-60 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                    Hapus
                </button>
                @endif
                    <button type="button" onclick="window.location.href='{{ route('mutasi.index') }}'"
                        class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Kembali
                    </button>
                </div>
            </div>

        {{-- ============================================ --}}
        {{-- MODE EDIT / VIEW: FORM                      --}}
        {{-- ============================================ --}}
        @else
            <form action="{{ route('mutasi.update', $mutasi->fstockmtid) }}" method="POST"
                data-form-draft="true" data-draft-key="mutasi:edit:{{ $mutasi->fstockmtid }}"
                @submit="onSubmit($event)">
                @csrf
                @method('PATCH')

                {{-- ─── CARD 1: Identitas (Edit) ──────────── --}}
<div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
    <div class="flex items-center gap-2 px-4 pt-3 pb-0">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
            viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
        </svg>
        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Identitas Mutasi Stock</p>
    </div>
    <div class="p-4">
        <div class="grid grid-cols-3 gap-3">
            <div class="lg:col-span-4">
                <label class="block text-xs font-bold mb-1">Cabang</label>
                <input type="text"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                    value="{{ trim(($fbranchcode ?? '') . ($fcabang ?? '' ? ' - ' . $fcabang : '')) }}" disabled>
                <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
            </div>
            <div class="lg:col-span-4" x-data="{ autoCode: true }">
                <label class="block text-xs font-bold mb-1">Transaksi#</label>
                <div class="flex items-center gap-3">
                    <input type="text" name="fstockmtno"
                        value="{{ old('fstockmtno', $mutasi->fstockmtno) }}"
                        class="w-full border rounded px-3 py-2" :disabled="autoCode"
                        :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'">
                    <label class="inline-flex items-center select-none">
                        <input type="checkbox" x-model="autoCode" checked>
                        <span class="ml-2 text-sm text-gray-700">Auto</span>
                    </label>
                </div>
            </div>

            <input type="hidden" name="fstockmtid" value="{{ $mutasi->fstockmtid }}">

            <!-- Tanggal - editable, label matches Cabang -->
            <div class="lg:col-span-4">
                <label class="block text-xs font-bold mb-1">Tanggal</label>
                <input type="date" name="fstockmtdate"
                    value="{{ old('fstockmtdate') ?? date('Y-m-d') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm @error('fstockmtdate') border-red-500 @enderror">
                @error('fstockmtdate')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Field FROM -->
            <div class="lg:col-span-4">
                <label class="block text-xs font-bold mb-1">Gudang (Dari)</label>
                <div class="flex">
                    <div class="relative flex-1">
                        <select id="warehouseSelectFrom"
                            class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
                            disabled>
                            <option value=""></option>
                            @foreach ($warehouses as $wh)
                            <option value="{{ $wh->fwhcode }}" data-id="{{ $wh->fwhid }}"
                                data-branch="{{ $wh->fbranchcode }}"
                                {{ old('ffrom', $mutasi->ffrom) == $wh->fwhcode ? 'selected' : '' }}>
                                    {{ $wh->fwhcode }} - {{ $wh->fwhname }}
                                </option>
                            @endforeach
                        </select>

                        <div class="absolute inset-0" role="button" aria-label="Browse warehouse"
                            @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open', { detail: 'from' }))">
                        </div>
                    </div>

                    <input type="hidden" name="ffrom" id="warehouseCodeHiddenFrom"
                        value="{{ old('ffrom', $mutasi->ffrom) }}">

                    <button type="button"
                        @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open', { detail: 'from' }))"
                        class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                        title="Browse Gudang">
                        <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                    </button>

                    <a href="{{ route('gudang.create') }}" target="_blank" rel="noopener"
                        class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50"
                        title="Tambah Gudang">
                        <x-heroicon-o-plus class="w-5 h-5" />
                    </a>
                </div>

                @error('ffrom')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Field TO -->
            <div class="lg:col-span-4">
                <label class="block text-xs font-bold mb-1">Gudang (Tujuan)</label>
                <div class="flex">
                    <div class="relative flex-1">
                        <select id="warehouseSelectTo"
                            class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
                            disabled>
                            <option value=""></option>
                            @foreach ($warehouses as $wh)
                            <option value="{{ $wh->fwhcode }}" data-id="{{ $wh->fwhid }}"
                                data-branch="{{ $wh->fbranchcode }}"
                                {{ old('fto', $mutasi->fto) == $wh->fwhcode ? 'selected' : '' }}>
                                    {{ $wh->fwhcode }} - {{ $wh->fwhname }}
                                </option>
                            @endforeach
                        </select>

                        <div class="absolute inset-0" role="button" aria-label="Browse warehouse"
                            @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open', { detail: 'to' }))">
                        </div>
                    </div>

                    <input type="hidden" name="fto" id="warehouseCodeHiddenTo"
                        value="{{ old('fto', $mutasi->fto) }}">

                    <button type="button"
                        @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open', { detail: 'to' }))"
                        class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                        title="Browse Gudang">
                        <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                    </button>

                    <a href="{{ route('gudang.create') }}" target="_blank" rel="noopener"
                        class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50"
                        title="Tambah Gudang">
                        <x-heroicon-o-plus class="w-5 h-5" />
                    </a>
                </div>

                @error('fto')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Keterangan - editable, label matches Cabang -->
            <div class="lg:col-span-12">
                <label class="block text-xs font-bold mb-1">Keterangan</label>
                <textarea name="fket" rows="3"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm @error('fket') border-red-500 @enderror"
                    placeholder="Tulis keterangan tambahan di sini...">{{ old('fket', $mutasi->fket) }}</textarea>
                @error('fket')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>
</div>

                {{-- ─── CARD 2: Detail Item (Edit/View) ─────── --}}
                <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                    <div class="flex items-center gap-2 px-4 pt-3 pb-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Detail Item</p>
                    </div>
                    <div class="p-4">
                        <div x-data="itemsTable()" x-init="init()" class="space-y-2">

                            {{-- DETAIL ITEM (tabel input) --}}
                            <h3 class="text-base font-semibold text-gray-800">Detail Item</h3>

                            <div class="overflow-auto border rounded">
                                <table class="mutasi-detail-table min-w-full text-sm balanced-detail-table"
                                    data-skip-auto-detail-style="true">
                                    <colgroup>
                                        <col style="width:2%;">
                                        <col style="width:18%;">
                                        <col style="width:40%;">
                                        <col style="width:12%;">
                                        <col style="width:18%;">
                                        <col style="width:10%;">
                                    </colgroup>
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="p-2 text-left w-10">#</th>
                                            <th class="p-2 text-left w-40">Kode Produk</th>
                                            <th class="p-2 text-left w-[20rem]">Nama Produk</th>
                                            <th class="p-2 text-left w-24">Sat</th>
                                            <th class="p-2 text-right w-36 whitespace-nowrap">Qty</th>
                                            <th class="p-2 text-center w-36">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(it, i) in savedItems" :key="it.uid || `item-${i}`">
                                            <tr class="border-t align-top hover:bg-gray-55">
                                                <td class="p-2 text-gray-400" x-text="i + 1"></td>
                                                <td class="p-2">
                                                    <div class="flex">
                                                        <input type="text" class="flex-1 border rounded-l px-2 py-1 font-mono text-sm focus:ring-1 focus:ring-blue-500 min-w-0 bg-white"
                                                            :id="'mutasi_code_row_' + i"
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
                                                    <template x-if="it.units && it.units.length > 1">
                                                        <select class="w-full border rounded px-2 py-1 text-sm focus:ring-1 focus:ring-blue-500"
                                                            :id="'mutasi_unit_row_' + i"
                                                            x-model="it.fsatuan"
                                                            @change="onRowUpdated(i)"
                                                            @keydown.enter.prevent="focusRowQty(i)">
                                                            <template x-for="u in it.units" :key="u">
                                                                <option :value="u" x-text="u"></option>
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
                                                        :id="'mutasi_qty_row_' + i"
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
                            <div class="hidden">
                                <template x-for="(it, i) in submitItems" :key="'submit-mutasi-edit-' + (it.uid || i)">
                                    <div>
                                        <input type="hidden" name="fitemcode[]" :value="it.fitemcode">
                                        <input type="hidden" name="fitemname[]" :value="it.fitemname">
                                        <input type="hidden" name="fsatuan[]" :value="it.fsatuan">
                                        <input type="hidden" name="frefdtno[]" :value="it.frefdtno">
                                        <input type="hidden" name="frefpr[]" :value="it.frefpr">
                                        <input type="hidden" name="frefso[]" :value="it.frefso">
                                        <input type="hidden" name="frefnoacak[]" :value="it.frefnoacak">
                                        <input type="hidden" name="fqty[]" :value="it.fqty">
                                        <input type="hidden" name="fdesc[]" :value="it.fdesc">
                                        <input type="hidden" name="fketdt[]" :value="it.fketdt">
                                    </div>
                                </template>
                                <input type="hidden" id="itemsCount" :value="submitItems.length">
                        </div> {{-- End itemsTable edit --}}
                    </div> {{-- End CARD 2 body --}}
                </div> {{-- End CARD 2 --}}

                        <!-- MODAL DESC (diamankan di dalam itemsTable) -->
                        <div x-show="$store.mutasiDesc.show" x-cloak
                            class="fixed inset-0 z-[95] flex items-center justify-center" x-transition.opacity>
                            <div class="absolute inset-0 bg-black/50" @click="$store.mutasiDesc.close()"></div>

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
                                            <button x-show="!$store.mutasiDesc.readonly" type="button" @click="$store.mutasiDesc.copyName()"
                                                class="h-8 px-3 rounded-lg bg-blue-50 text-blue-700 text-sm font-medium hover:bg-blue-100">
                                                Copy
                                            </button>
                                        </div>
                                        <div class="rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800" x-text="$store.mutasiDesc.itemName || '-'"></div>
                                    </div>
                                    <label class="block text-sm text-gray-700">Deskripsi</label>
                                    <textarea x-model="$store.mutasiDesc.value" rows="5" class="w-full border rounded px-3 py-2"
                                        placeholder="Tulis deskripsi item di sini..."></textarea>
                                </div>

                                <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
                                    <button type="button" @click="$store.mutasiDesc.close()"
                                        class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">
                                        Batal
                                    </button>
                                    <button type="button" @click="$store.mutasiDesc.apply()"
                                        class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
                                        Simpan
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- MODAL ERROR: belum ada item --}}
                        <div x-show="showNoItems && submitItems.length === 0" x-cloak
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
                                        Anda belum menambahkan item apa pun pada tabel. Silakan isi baris â€œDetail Itemâ€
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
                        <div x-data="warehouseBrowser()" x-init="init()" x-show="open" x-cloak x-transition.opacity
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
                        <div x-data="productBrowser()" x-init="init()" x-show="open" x-cloak x-transition.opacity
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

                {{-- ─── CARD 3: Aksi (Edit/View) ───────────── --}}
                <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                    <div class="p-4 flex items-center justify-end gap-3 allow-action">
                        @if ($action === 'edit' && $canEditPermission)
                            <button type="submit"
                                @if ($usageLocked) disabled @endif
                                class="inline-flex h-9 items-center justify-center rounded-lg bg-blue-600 px-4 text-xs font-semibold text-white shadow-sm hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Simpan
                            </button>
                        @endif
                        <button type="button" @click="window.location.href='{{ route('mutasi.index') }}'"
                            class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            {{ $action === 'view' ? 'Kembali' : 'Keluar' }}
                        </button>
                    </div>
                </div>
            </form>
        @endif
    </div>

    {{-- ============================================ --}}
    {{-- MODAL & TOAST (HANYA UNTUK MODE DELETE)     --}}
    {{-- ============================================ --}}
    @if ($action === 'delete' && $canDeletePermission)
        {{-- Modal Delete --}}
        <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6 allow-action">
                <h3 class="text-lg font-semibold mb-4">Konfirmasi Hapus mutasi ini?</h3>
                <form id="deleteForm" action="{{ route('mutasi.destroy', $mutasi->fstockmtid) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="flex justify-end space-x-2">
                        <button onclick="closeDeleteModal()" class="px-5 py-2 bg-gray-300 rounded hover:bg-gray-400"
                            id="btnTidak">
                            Tidak
                        </button>
                        <button type="submit" @if ($usageLocked) disabled @endif class="px-5 py-2 bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-60 disabled:cursor-not-allowed">
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

                fetch('{{ route('mutasi.destroy', $mutasi->fstockmtid) }}', {
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
                            window.location.href = '{{ route('mutasi.index') }}';
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

    /* Targeting lebih spesifik untuk length select */
    div#poTable_length select,
    .dataTables_wrapper #poTable_length select,
    table#supplierBrowseTable+.dataTables_wrapper .dataTables_length select {
        min-width: 140px !important;
        width: auto !important;
        padding: 8px 45px 8px 16px !important;
        font-size: 14px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.375rem !important;
    }

    /* Wrapper length */
    div#poTable_length,
    .dataTables_wrapper #poTable_length,
    .dataTables_wrapper .dataTables_length {
        min-width: 250px !important;
    }

    /* Label wrapper */
    div#poTable_length label,
    .dataTables_wrapper #poTable_length label,
    .dataTables_wrapper .dataTables_length label {
        font-size: 14px !important;
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
    }
</style>
@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
@endpush
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
        Alpine.store('mutasiDesc', {
            show: false,
            value: '',
            itemName: '',
            readonly: false,
            target: null,
            open(targetRow, readonly = false) {
                const itemCode = (targetRow?.fitemcode || '').toString().trim();
                if (!itemCode) return;

                this.target = targetRow || null;
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

    window.getMutasiDuplicateCode = function(form) {
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

    function itemsTable() {
        return {
            showNoItems: false,
            savedItems: @json(count($initialEditMutasiItems) ? $initialEditMutasiItems : $savedItems),
            minimumVisibleRows: @json(count($initialEditMutasiItems) ? count($initialEditMutasiItems) + 5 : count($savedItems ?? []) + 5),
            browseTarget: null,

            totalHarga: 0,

            fmt(n) {
                if (n === null || n === undefined || n === '') return '-';
                const v = Number(n);
                if (!isFinite(v)) return '-';

                // Jika angka adalah bulat, hilangkan desimal
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
                    row.frefdtno,
                    row.frefpr,
                    row.frefso,
                    row.frefnoacak,
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

            isComplete(row) {
                return row.fitemcode && row.fitemname && row.fsatuan && (@json(stock_boleh_minus()) ? Number(row.fqty) !== 0 : Number(row.fqty) > 0);
            },

            onPrPicked(e) {
                const {
                    header,
                    items
                } = e.detail || {};
                if (!items || !Array.isArray(items)) return;

                this.addManyFromPR(header, items, 'PO');
            },

            onSoPicked(e) {
                const {
                    header,
                    items
                } = e.detail || {};
                if (!items || !Array.isArray(items)) return;

                this.addManyFromPR(header, items, 'SO');
            },

            addManyFromPR(header, items, source = 'PO') {
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
                        frefpr: src.frefpr ?? (header?.fprno ?? header?.fpono ?? ''),
                        frefso: header?.fprno ?? header?.fpono ?? '',
                        frefnoacak: src.frefnoacak ?? '',
                        fqty: (src.fqty !== null && src.fqty !== undefined && (@json(stock_boleh_minus()) ? Number(src.fqty) !== 0 : Number(src.fqty) > 0)) ?
                            Number(src.fqty) : 1,
                        fprice: Number(src.fprice ?? src.fharga ?? 0),
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

                    this.savedItems.push({
                        ...this.createRow(),
                        ...row,
                        uid: cryptoRandom(),
                    });
                    existing.add(key);
                    added++;
                });

                this.pruneEmptyRows();
                this.ensureMinimumRows();
                this.ensureTrailingRow();
                this.recalcTotals();
            },

            onRowUpdated(index = null) {
                const row = typeof index === 'number' ? this.savedItems[index] : null;
                if (row) {
                    this.recalc(row);
                }
                this.showNoItems = false;
                this.ensureTrailingRow(index);
                this.recalcTotals();
            },

            onSubmit($event) {
                const duplicateCode = window.getMutasiDuplicateCode?.($event.target);
                if (duplicateCode) {
                    $event.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Produk Duplikat',
                        text: `Kode produk ${duplicateCode} tidak boleh sama dalam satu Mutasi.`,
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
                    document.getElementById(`mutasi_unit_row_${index}`)?.focus();
                    return;
                }
                this.focusRowQty(index);
            },

            focusRowQty(index) {
                document.getElementById(`mutasi_qty_row_${index}`)?.focus();
            },

            openDesc(targetRow, readonly = false) {
                Alpine.store('mutasiDesc').open(targetRow, readonly);
            },

            itemKey(it) {
                return `${(it.fitemcode ?? '').toString().trim()}::${(it.frefdtno ?? '').toString().trim()}`;
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

            pruneEmptyRows() {
                const filled = this.savedItems.filter(row => this.rowHasContent(row));
                this.savedItems = filled.length ? filled : [];
            },

            init() {
                this.savedItems = (Array.isArray(this.savedItems) ? this.savedItems : []).map(item => {
                    const row = {
                        ...this.createRow(),
                        ...item,
                        uid: item?.uid || cryptoRandom(),
                    };
                    this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode));
                    this.recalc(row);
                    return row;
                });

                window.getCurrentItemKeys = () => this.getCurrentItemKeys();

                window.addEventListener('pr-picked', this.onPrPicked.bind(this), {
                    passive: true
                });
                window.addEventListener('so-picked', this.onSoPicked.bind(this), {
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
                         this.rows.splice(this.browseTarget, 1, {
                        ...this.rows[this.browseTarget]
                    });

                        if (!row.fqty) row.fqty = @json(stock_boleh_minus()) ? 1 : 0;
                        this.recalc(row);
                    };

                    const index = typeof this.browseTarget === 'number' ? this.browseTarget : this.savedItems.length - 1;
                    if (index < 0 || !this.savedItems[index]) return;
                    const row = this.savedItems[index];
                    apply(row);
                    this.onRowUpdated(index);
                    this.$nextTick(() => this.focusRowQty(index));
                }, {
                    passive: true
                });

                this.ensureMinimumRows();
                this.ensureTrailingRow();
                this.recalcTotals();
            },

            openBrowseFor(index) {
                this.browseTarget = typeof index === 'number' ? index : this.savedItems.length - 1;
                window.dispatchEvent(new CustomEvent('browse-open', {
                    detail: {
                        forEdit: false
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
                frefpr: '',
                frefso: '',
                frefnoacak: '',
                fqty: 0,
                fprice: 0,
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
    // Warehouse Browser dengan DataTables
    window.warehouseBrowser = function() {
        return {
            open: false,
            table: null,
            currentTarget: '',

            initDataTable() {
                if (this.table) {
                    this.table.columns.adjust().draw(false);
                    return;
                }
                $('#warehouseTable').off('click.whpick');
                $('#warehouseTable tbody').off('click.whpick');
                const browser = this;
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
                                order_dir: d.order[0].dir,
                                branch_scope: browser.currentTarget === 'to' ? 'all' : 'user'
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
                            className: 'font-mono text-sm',
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

                $('#warehouseTable tbody').on('click.whpick', 'tr', (e) => {
                    if ($(e.target).closest('button, a, input, select, textarea').length) return;

                    const data = this.table?.row(e.currentTarget).data();
                    if (!data) return;
                    this.choose(data);
                });
            },

            openModal(target) {
                this.currentTarget = target;
                this.open = true;
                this.$nextTick(() => {
                    this.initDataTable();
                    if (this.table) {
                        this.table.ajax.reload(null, false);
                    }
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
                        fbranchcode: w.fbranchcode,
                        target: this.currentTarget
                    }
                }));
                this.close();
            },

            init() {
                window.addEventListener('warehouse-browse-open', (e) => this.openModal(e.detail));
            }
        }
    };

    // Helper: update field saat warehouse-picked
    document.addEventListener('DOMContentLoaded', () => {
        window.addEventListener('warehouse-picked', (ev) => {
            const {
                fwhcode,
                fwhname,
                target
            } = ev.detail || {};
            const suffix = target === 'from' ? 'From' : 'To';
            const sel = document.getElementById('warehouseSelect' + suffix);
            const hid = document.getElementById('warehouseCodeHidden' + suffix);
            if (sel) {
                const code = String(fwhcode || '').trim();
                let opt = [...sel.options].find((o) => String(o.value).trim() === code);
                if (code && !opt) {
                    opt = new Option(fwhname ? `${fwhname} (${code})` : code, code, true, true);
                    sel.add(opt);
                }
                sel.value = opt ? opt.value : code;
                sel.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }
            if (hid) {
                hid.value = fwhcode || '';
                hid.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }
        });
    });
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
                    $('#productTable').off('click.prodpick');
                    $('#productTable tbody').off('click.prodpick');

                    $('#productTable').on('click.prodpick', '.btn-choose', (e) => {
                        e.preventDefault();
                        e.stopPropagation();

                        const data = this.table?.row($(e.currentTarget).closest('tr')).data();
                        if (data) this.choose(data);
                    });

                    $('#productTable tbody').on('click.prodpick', 'tr', (e) => {
                        if ($(e.target).closest('button, a, input, select, textarea').length) return;

                        const data = this.table?.row(e.currentTarget).data();
                        if (data) this.choose(data);
                    });
                },

                close() {
                    this.open = false;
                    if (this.table) {
                        this.table.search('').draw();
                    }
                },

                choose(product) {
                    if (!product) return;
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
