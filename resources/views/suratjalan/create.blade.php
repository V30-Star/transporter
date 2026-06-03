@extends('layouts.app')

@section('title', 'Surat Jalan - New')

@section('content')
    @php
        $oldSjItemCodes = old('fitemcode', []);
        $oldSjItemNames = old('fitemname', []);
        $oldSjUnits = old('fsatuan', []);
        $oldSjRefNos = old('frefdtno', []);
        $oldSjRefPrs = old('frefpr', []);
        $oldSjRefSos = old('frefso', []);
        $oldSjNoAcaks = old('fnoacak', []);
        $oldSjRefNoAcaks = old('frefnoacak', []);
        $oldSjDiscPersens = old('fdiscpersen', []);
        $oldSjQtys = old('fqty', []);
        $oldSjPrices = old('fprice', []);
        $oldSjTotals = old('ftotal', []);
        $oldSjDescs = old('fdesc', []);
        $oldSjMaxQtys = old('fmaxqty', []);
        $oldSjKetdts = old('fketdt', []);
        $initialSuratJalanItems = [];
        $oldSjIndexes = array_keys(is_array($oldSjItemCodes) ? $oldSjItemCodes : []);

        foreach ($oldSjIndexes as $index) {
            $code = trim((string) ($oldSjItemCodes[$index] ?? ''));
            $name = trim((string) ($oldSjItemNames[$index] ?? ''));
            if ($code === '' && $name === '') {
                continue;
            }

            $unit = trim((string) ($oldSjUnits[$index] ?? ''));
            $refPr = trim((string) ($oldSjRefPrs[$index] ?? ''));
            $refSo = trim((string) ($oldSjRefSos[$index] ?? ''));
            $refDtNo = trim((string) ($oldSjRefNos[$index] ?? ''));

            $initialSuratJalanItems[] = [
                'uid' => 'old-sj-' . $index,
                'formIndex' => (int) $index,
                'is_restored_old' => true,
                'fitemcode' => $code,
                'fitemname' => $name,
                'units' => $unit !== '' ? [$unit] : [],
                'fsatuan' => $unit,
                'frefdtno' => $refDtNo,
                'fnoacak' => trim((string) ($oldSjNoAcaks[$index] ?? '')),
                'frefnoacak' => trim((string) ($oldSjRefNoAcaks[$index] ?? '')),
                'frefno_display' => $refPr !== '' ? $refPr : ($refSo !== '' ? $refSo : $refDtNo),
                'frefpr' => $refPr,
                'frefso' => $refSo,
                'fdiscpersen' => (float) ($oldSjDiscPersens[$index] ?? 0),
                'fqty' => (float) ($oldSjQtys[$index] ?? 0),
                'maxqty' => max(0, (float) ($oldSjMaxQtys[$index] ?? $oldSjQtys[$index] ?? 0)),
                'fprice' => (float) ($oldSjPrices[$index] ?? 0),
                'ftotal' => (float) ($oldSjTotals[$index] ?? 0),
                'fdesc' => (string) ($oldSjDescs[$index] ?? ''),
                'fketdt' => (string) ($oldSjKetdts[$index] ?? ''),
            ];
        }

        $nextSuratJalanItemIndex = empty($oldSjIndexes) ? 0 : max(array_map('intval', $oldSjIndexes)) + 1;
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
    </style>
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow p-0 overflow-hidden" role="alert">
            {{-- Header Strip --}}
            <div class="d-flex align-items-center px-4 py-3" style="background-color: #c0392b;">
                <i class="bi bi-exclamation-triangle-fill text-white me-2 fs-5"></i>
                <strong class="text-white fs-6">{{ 'Gagal Menyimpan Data!' }}</strong>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert"
                    aria-label="Close"></button>
            </div>

            {{-- Body --}}
            <div class="px-4 py-3" style="background-color: #fdeded; border-left: 5px solid #c0392b;">
                <p class="mb-2 text-danger fw-semibold">
                    <i class="bi bi-info-circle me-1"></i>
                    {{ 'Periksa kembali data berikut sebelum menyimpan:' }}
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
    <div x-data="{ open: true }">
        <div x-data="{ includePPN: false, ppnRate: 0, ppnAmount: 0, totalHarga: 100000 }" class="lg:col-span-5">
            <div class="bg-white rounded shadow p-6 md:p-8 max-w-[128rem] mx-auto">
                <form action="{{ route('suratjalan.store') }}" method="POST" class="mt-6" data-form-draft="true"
                    data-draft-key="suratjalan:create" x-data="{ showNoItems: false, showWarehouseRequired: false }"
                    @submit.prevent="
        const duplicateCode = window.getSuratJalanDuplicateCode?.($el);
        if (duplicateCode) {
            Swal.fire({
                icon: 'warning',
                title: 'Produk Duplikat',
                text: `Kode produk ${duplicateCode} tidak boleh sama dalam satu Surat Jalan.`,
                confirmButtonText: 'OK',
                customClass: {
                    confirmButton: 'bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700'
                }
            });
            return;
        }
        const warehouseCode = (document.getElementById('warehouseCodeHidden')?.value || '').toString().trim();
        showWarehouseRequired = false;
        if (!warehouseCode) {
            showWarehouseRequired = true;
            window.toast?.error('Gudang wajib diisi sebelum simpan.');
            return;
        }
        const n = Number(document.getElementById('itemsCount')?.value || 0);
        if (n < 1) { showNoItems = true } else { $el.submit() }
      ">
                    @csrf

                    {{-- HEADER FORM --}}
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                        <div class="lg:col-span-4">
                            <label class="block text-sm font-bold">Cabang</label>
                            <input type="text" class="w-full border rounded px-3 py-2 bg-gray-200 cursor-not-allowed"
                                value="{{ trim(($fbranchcode ?? '') . ($fcabang ?? '' ? ' - ' . $fcabang : '')) }}"
                                disabled>
                            <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                        </div>
                        <div class="lg:col-span-4" x-data="{ autoCode: true }">
                            <label class="block text-sm font-bold mb-1">Transaksi#</label>
                            <div class="flex items-center gap-3">
                                <input type="text" name="fstockmtno" class="w-full border rounded px-3 py-2"
                                    :disabled="autoCode"
                                    :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'">
                                <label class="inline-flex items-center select-none">
                                    <input type="checkbox" x-model="autoCode" checked>
                                    <span class="ml-2 text-sm text-gray-700">Auto</span>
                                </label>
                            </div>
                        </div>

                        <input type="hidden" name="fstockmtid" value="fstockmtid">

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-bold">Tanggal</label>
                            <input type="date" name="fstockmtdate" value="{{ old('fstockmtdate') ?? date('Y-m-d') }}"
                                class="w-full border rounded px-3 py-2 @error('fstockmtdate') border-red-500 @enderror">
                            @error('fstockmtdate')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-bold mb-1">Customer</label>
                            <div class="flex">
                                <div class="relative flex-1" for="modal_filter_customer_id">
                                    <select id="modal_filter_customer_id" name="filter_customer_id"
                                        class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
                                        disabled>
                                        <option value=""></option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->fcustomercode }}"
                                                {{ $filterSupplierId == $customer->fcustomercode ? 'selected' : '' }}>
                                                {{ $customer->fcustomername }} ({{ $customer->fcustomercode }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-0" role="button" aria-label="{{ 'Browse Customer' }}"
                                        @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))"></div>
                                </div>
                                <input type="hidden" name="fsupplier" id="customerCodeHidden"
                                    value="{{ old('fsupplier') }}">
                                <button type="button"
                                    @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))"
                                    class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                                    title="{{ 'Browse Customer' }}">
                                    <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                </button>
                                @if (in_array('createCustomer', explode(',', session('user_restricted_permissions', '')), true))
                                    <a href="{{ route('customer.create') }}" target="_blank" rel="noopener"
                                        class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50"
                                        title="Tambah Customer">
                                        <x-heroicon-o-plus class="w-5 h-5" />
                                    </a>
                                @endif
                            </div>
                            @error('fsupplier')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-bold mb-1">Gudang</label>
                            <div class="flex">
                                <div class="relative flex-1">
                                    <select id="warehouseSelect"
                                        class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
                                        disabled>
                                        <option value=""></option>
                                        @foreach ($warehouses as $wh)
                                            <option value="{{ $wh->fwhcode }}" data-id="{{ $wh->fwhid }}"
                                                data-branch="{{ $wh->fbranchcode }}"
                                                {{ old('ffrom') == $wh->fwhcode ? 'selected' : '' }}>
                                                {{ $wh->fwhcode }} - {{ $wh->fwhname }}
                                            </option>
                                        @endforeach
                                    </select>

                                    {{-- Overlay untuk buka browser gudang --}}
                                    <div class="absolute inset-0" role="button" aria-label="{{ 'Browse Gudang' }}"
                                        @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open'))"></div>
                                </div>

                                <input type="hidden" name="ffrom" id="warehouseCodeHidden"
                                    value="{{ old('ffrom') }}">
                                <input type="hidden" name="fwhid" id="warehouseIdHidden"
                                    value="{{ old('fwhid') }}">

                                <button type="button"
                                    @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open'))"
                                    class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                                    title="{{ 'Browse Gudang' }}">
                                    <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                </button>

                                {{-- ganti route di bawah sesuai halaman tambah gudangmu --}}
                                <a href="{{ route('gudang.create') }}" target="_blank" rel="noopener"
                                    class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50"
                                    title="Tambah Gudang">
                                    <x-heroicon-o-plus class="w-5 h-5" />
                                </a>
                            </div>

                            @error('ffrom')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            <p x-show="showWarehouseRequired" x-cloak class="text-red-600 text-sm mt-1">
                                Gudang harus diisi dahulu sebelum Simpan.
                            </p>
                        </div>

                        <div class="lg:col-span-4">
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-bold">Kirim ke</label>
                            <textarea name="fkirim" rows="3"
                                class="w-full border rounded px-3 py-2 @error('fkirim') border-red-500 @enderror"
                                placeholder="Tulis kirim tambahan di sini...">{{ old('fkirim') }}</textarea>
                            @error('fkirim')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="lg:col-span-4">
                            <label class="block text-sm font-bold">Keterangan</label>
                            <textarea name="fket" rows="3"
                                class="w-full border rounded px-3 py-2 @error('fket') border-red-500 @enderror"
                                placeholder="Tulis keterangan tambahan di sini...">{{ old('fket') }}</textarea>
                            @error('fket')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="lg:col-span-4">
                            <label class="block text-sm font-bold">Catatan Internal</label>
                            <textarea name="fketinternal" id="fketinternal" rows="3"
                                class="w-full border rounded px-3 py-2 @error('fketinternal') border-red-500 @enderror"
                                placeholder="Tulis catatan internal di sini...">{{ old('fketinternal') }}</textarea>
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
                                        <th class="p-2 text-left w-40">Kode Produk</th>
                                        <th class="p-2 text-left w-102">Nama Produk</th>
                                        <th class="p-2 text-left w-36">No. Ref</th>
                                        <th class="p-2 text-right w-24">Sat</th>
                                        <th class="p-2 text-right w-28">Qty</th>
                                        <th class="p-2 text-center w-36">Aksi</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <template x-for="(it, i) in savedItems" :key="it.uid">
                                        <tr class="border-t align-top">
                                            <td class="p-2" x-text="i + 1"></td>
                                            <td class="p-2">
                                                <div class="flex">
                                                    <input type="text"
                                                        class="flex-1 border rounded-l px-2 py-1 font-mono text-sm"
                                                        x-model.trim="it.fitemcode" @input="onCodeTypedRow(it, i)"
                                                        @keydown.enter.prevent="focusRowUnit(it, i)">
                                                    <button type="button" @click="openBrowseFor(i)"
                                                        class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50"
                                                        title="Cari Produk">
                                                        <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="p-2">
                                                <div class="desc-inline-field">
                                                    <div class="desc-inline-field__text rounded-l border bg-gray-100 px-2 py-1 text-sm leading-5 text-gray-600 whitespace-normal break-words"
                                                        x-text="it.fitemname"></div>
                                                    <button type="button" @click="openDesc(i)"
                                                        class="desc-inline-field__button inline-flex items-center rounded-r border border-l-0 bg-slate-50 px-2 py-1 text-slate-700 hover:bg-slate-100"
                                                        title="Deskripsi">
                                                        <x-heroicon-o-document-text class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="p-2">
                                                <input type="text"
                                                    class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                                    :value="it.frefno_display || (it.frefdtno && it.frefdtno !== '0' ? it.frefdtno : '') || '-'" disabled>
                                            </td>
                                            <td class="p-2 text-right">
                                                <template x-if="it.units && it.units.length > 1">
                                                    <select class="w-full border rounded px-2 py-1 text-xs"
                                                        :id="'unit_row_' + i" x-model="it.fsatuan"
                                                        x-effect="$el.value = it.fsatuan" @change="onRowUpdated(i)"
                                                        @keydown.enter.prevent="focusRowQty(i)">
                                                        <template x-for="u in it.units" :key="u">
                                                            <option :value="u" :selected="u === it.fsatuan"
                                                                x-text="u"></option>
                                                        </template>
                                                    </select>
                                                </template>
                                                <template x-if="!(it.units && it.units.length > 1)">
                                                    <input type="text"
                                                        class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-xs"
                                                        :value="it.fsatuan || '-'" disabled>
                                                </template>
                                            </td>
                                            <td class="p-2 text-right">
                                                <input type="number"
                                                    class="w-full border rounded px-2 py-1 text-right text-sm"
                                                    :id="'qty_row_' + i" x-model.number="it.fqty"
                                                    @input="enforceQtyRow(it); onRowUpdated(i)"
                                                    @change="enforceQtyRow(it); onRowUpdated(i)">
                                                <div class="text-xs text-gray-400 mt-0.5 text-right">
                                                    <span x-show="it.fitemcode" x-html="formatStockLimit(it)"></span>
                                                </div>
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
                            <template x-for="(it, i) in submitItems" :key="'submit-' + (it.uid || i)">
                                <div>
                                    <input type="hidden" :name="`fitemcode[${it.formIndex}]`" :value="it.fitemcode">
                                    <input type="hidden" :name="`fitemname[${it.formIndex}]`" :value="it.fitemname">
                                    <input type="hidden" :name="`fsatuan[${it.formIndex}]`" :value="it.fsatuan">
                                    <input type="hidden" :name="`frefdtno[${it.formIndex}]`" :value="it.frefdtno">
                                    <input type="hidden" :name="`frefpr[${it.formIndex}]`" :value="it.frefpr">
                                    <input type="hidden" :name="`frefso[${it.formIndex}]`" :value="it.frefso">
                                    <input type="hidden" :name="`fnoacak[${it.formIndex}]`" :value="it.fnoacak">
                                    <input type="hidden" :name="`frefnoacak[${it.formIndex}]`" :value="it.frefnoacak">
                                    <input type="hidden" :name="`fdiscpersen[${it.formIndex}]`" :value="it.fdiscpersen ?? 0">
                                    <input type="hidden" :name="`fqty[${it.formIndex}]`" :value="it.fqty">
                                    <input type="hidden" :name="`fprice[${it.formIndex}]`" :value="it.fprice">
                                    <input type="hidden" :name="`ftotal[${it.formIndex}]`" :value="it.ftotal">
                                    <input type="hidden" :name="`fmaxqty[${it.formIndex}]`" :value="it.maxqty">
                                    <input type="hidden" :name="`fdesc[${it.formIndex}]`" :value="it.fdesc">
                                    <input type="hidden" :name="`fketdt[${it.formIndex}]`" :value="it.fketdt">
                                </div>
                            </template>
                        </div>

                        <div class="mt-3 flex flex-wrap items-start gap-3">
                            <div class="flex flex-wrap items-center gap-3">
                                {{-- SO --}}
                                <div x-data="soFormModal()" class="min-w-fit">
                                    <div class="w-full flex justify-start">
                                        <button type="button" @click="openModal()"
                                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                            Add SO
                                        </button>
                                    </div>

                                    {{-- MODAL SO --}}
                                    <div x-show="show" x-cloak x-transition.opacity
                                        class="fixed inset-0 z-50 flex items-center justify-center overflow-hidden p-3 md:p-6">
                                        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal()">
                                        </div>

                                        <div class="relative w-full max-w-7xl rounded-xl bg-white shadow-2xl flex flex-col overflow-hidden"
                                            style="height: min(760px, calc(100vh - 1.5rem));">
                                            <!-- Header -->
                                            <div
                                                class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-teal-50 to-white">
                                                <div>
                                                    <h3 class="text-xl font-bold text-gray-800">Add SO</h3>
                                                    <p class="text-sm text-gray-500 mt-0.5">
                                                        {{ 'Pilih Purchase Order (PO)' }}</p>
                                                </div>
                                                <button type="button" @click="closeModal()"
                                                    class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-bold text-gray-700 text-sm">
                                                    {{ 'Tutup' }}
                                                </button>
                                            </div>

                                            <!-- Search & Length Menu -->
                                            <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                                                <div id="poTableControls"></div>
                                            </div>

                                            <!-- Table with fixed height and scroll -->
                                            <div class="flex-1 overflow-x-auto overflow-y-hidden px-6"
                                                style="min-height: 0;">
                                                <div class="bg-white">
                                                    <table id="poTable"
                                                        class="min-w-full text-sm display nowrap stripe hover"
                                                        style="width:100%">
                                                        <thead class="sticky top-0 z-10">
                                                            <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                                                <th
                                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                    No.SO</th>
                                                                <th
                                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                    Tanggal</th>
                                                                <th
                                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                    Customer</th>
                                                                <th
                                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                    Alamat</th>
                                                                <th
                                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                    No Ref</th>
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
                                                <div id="poTablePagination"></div>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                {{-- Invoice --}}
                                <div x-data="invoiceFormModal()" class="min-w-fit">
                                    <div class="w-full flex justify-start">
                                        <button type="button" @click="openModal()"
                                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                            Add Faktur
                                        </button>
                                    </div>

                                    <div x-show="show" x-transition.opacity class="fixed inset-0 z-40 bg-black/50"
                                        @keydown.escape.window="closeModal()"></div>
                                    <div>
                                        <div x-show="show" x-cloak x-transition.opacity
                                            class="fixed inset-0 z-50 flex items-center justify-center overflow-hidden p-3 md:p-6"
                                            aria-modal="true" role="dialog">
                                            <div class="relative w-full max-w-7xl rounded-xl bg-white shadow-2xl flex flex-col overflow-hidden"
                                                style="height: min(760px, calc(100vh - 1.5rem));">
                                                <div
                                                    class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-sky-50 to-white">
                                                    <div>
                                                        <h3 class="text-xl font-bold text-gray-800">Add Faktur</h3>
                                                        <p class="text-sm text-gray-500 mt-0.5">Pilih transaksi faktur penjualan</p>
                                                    </div>
                                                    <button type="button" @click="closeModal()"
                                                        class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                                                        Tutup
                                                    </button>
                                                </div>
                                                <div class="flex-1 overflow-hidden p-6" style="min-height: 0;">
                                                    <table id="invoiceTable"
                                                        class="min-w-full text-sm display nowrap stripe hover"
                                                        style="width:100%">
                                                        <thead class="sticky top-0 z-10">
                                                            <tr class="bg-gray-50 border-b-2 border-gray-200">
                                                                <th class="p-3 text-left font-semibold text-gray-700">
                                                                    No Faktur</th>
                                                                <th class="p-3 text-left font-semibold text-gray-700">
                                                                    Tanggal</th>
                                                                <th class="p-3 text-left font-semibold text-gray-700">
                                                                    No Ref</th>
                                                                <th class="p-3 text-left font-semibold text-gray-700">
                                                                    Customer</th>
                                                                <th class="p-3 text-center font-semibold text-gray-700">
                                                                    Aksi</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody></tbody>
                                                    </table>
                                                </div>
                                                <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
                                                    <div id="invoiceTablePagination"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div x-show="showDupModal" x-cloak x-transition.opacity
                                        class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                                        <div class="absolute inset-0 bg-black/50" @click="closeDupModal()"></div>

                                        <div
                                            class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden">
                                            <div class="px-5 py-4 border-b flex items-center gap-2 bg-amber-50">
                                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                                <h3 class="text-lg font-semibold text-gray-800">Item Duplikat Ditemukan
                                                </h3>
                                            </div>

                                            <div class="px-5 py-4 space-y-3">
                                                <p class="text-sm text-gray-700">
                                                    Ditemukan <span class="font-semibold text-amber-600"
                                                        x-text="dupCount"></span>
                                                    item duplikat. Item duplikat <span class="font-semibold">tidak akan
                                                        ditambahkan</span>.
                                                </p>

                                                <div class="rounded-lg border border-amber-200 bg-amber-50">
                                                    <div
                                                        class="px-3 py-2 border-b border-amber-200 text-sm font-bold text-gray-800">
                                                        Preview Item Duplikat
                                                    </div>
                                                    <ul class="max-h-40 overflow-auto divide-y divide-amber-100">
                                                        <template x-for="d in dupSample"
                                                            :key="`${d.fitemcode}::${d.fitemname}`">
                                                            <li
                                                                class="px-3 py-2 text-sm flex items-center gap-2 hover:bg-amber-100 transition-colors">
                                                                <span
                                                                    class="inline-flex w-5 h-5 items-center justify-center rounded-full bg-amber-200 text-amber-800 text-xs font-bold">!</span>
                                                                <span class="font-mono font-bold text-gray-700"
                                                                    x-text="d.fitemcode || '-'"></span>
                                                                <span class="text-gray-600 truncate"
                                                                    x-text="d.fitemname || '-'"></span>
                                                            </li>
                                                        </template>
                                                    </ul>
                                                </div>
                                            </div>

                                            <div class="px-5 py-3 border-t bg-gray-50 flex items-center justify-end gap-2">
                                                <button type="button" @click="closeDupModal()"
                                                    class="h-9 px-4 rounded-lg border-2 border-gray-300 text-gray-700 text-sm font-bold hover:bg-gray-100 transition-colors">
                                                    Batal
                                                </button>
                                                <button x-show="pendingUniques.length > 0" type="button"
                                                    @click="confirmAddUniques()"
                                                    class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-bold hover:bg-emerald-700">
                                                    Lanjut Tambah yang Valid
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- MODAL DUPLIKASI --}}
                                <div x-data="{ showDupModal: false, dupCount: 0, dupSample: [], closeDupModal() { this.showDupModal = false } }" x-show="showDupModal" x-cloak x-transition.opacity
                                    class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                                    <div class="absolute inset-0 bg-black/50" @click="closeDupModal()"></div>

                                    <div
                                        class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden">
                                        <!-- Header -->
                                        <div class="px-5 py-4 border-b flex items-center gap-2 bg-amber-50">
                                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            <h3 class="text-lg font-semibold text-gray-800">Item Duplikat Ditemukan</h3>
                                        </div>

                                        <!-- Body -->
                                        <div class="px-5 py-4 space-y-3">
                                            <p class="text-sm text-gray-700">
                                                Ditemukan <span class="font-semibold text-amber-600"
                                                    x-text="dupCount"></span>
                                                item duplikat.
                                                Item duplikat <span class="font-semibold">tidak akan ditambahkan</span>.
                                            </p>

                                            <!-- Preview list -->
                                            <div class="rounded-lg border border-amber-200 bg-amber-50">
                                                <div
                                                    class="px-3 py-2 border-b border-amber-200 text-sm font-bold text-gray-800">
                                                    Preview Item Duplikat
                                                </div>
                                                <ul class="max-h-40 overflow-auto divide-y divide-amber-100">
                                                    <template x-for="d in dupSample"
                                                        :key="`${d.fitemcode}::${d.fitemname}`">
                                                        <li
                                                            class="px-3 py-2 text-sm flex items-center gap-2 hover:bg-amber-100 transition-colors">
                                                            <span
                                                                class="inline-flex w-5 h-5 items-center justify-center rounded-full bg-amber-200 text-amber-800 text-xs font-bold">!</span>
                                                            <span class="font-mono font-bold text-gray-700"
                                                                x-text="d.fitemcode || '-'"></span>
                                                            <span class="text-gray-400">•</span>
                                                            <span class="text-gray-600 truncate"
                                                                x-text="d.fitemname || '-'"></span>
                                                        </li>
                                                    </template>
                                                    <template x-if="dupCount === 0">
                                                        <li class="px-3 py-2 text-sm text-gray-500 text-center">Tidak ada
                                                            contoh.</li>
                                                    </template>
                                                </ul>
                                                <div x-show="dupCount > 6"
                                                    class="px-3 py-2 text-xs text-center text-amber-700 border-t border-amber-200">
                                                    ... dan <span x-text="dupCount - 6"></span> item lainnya
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Footer -->
                                        <div class="px-5 py-3 border-t bg-gray-50 flex items-center justify-end gap-2">
                                            <button type="button" @click="closeDupModal()"
                                                class="h-9 px-4 rounded-lg border-2 border-gray-300 text-gray-700 text-sm font-bold hover:bg-gray-100 transition-colors">
                                                {{ 'Batal' }}
                                            </button>
                                        </div>
                                    </div>
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

                                    <div class="px-5 py-4 space-y-4">
                                        <div>
                                            <div class="mb-1 flex items-center justify-between gap-3">
                                                <div class="text-sm text-gray-700">Nama Produk</div>
                                                <button type="button" @click="copyDescName()"
                                                    class="h-8 px-3 rounded-lg bg-blue-50 text-blue-700 text-sm font-medium hover:bg-blue-100">
                                                    Copy
                                                </button>
                                            </div>
                                            <div class="rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800"
                                                x-text="descItemName || '-'"></div>
                                        </div>

                                        <label class="block text-sm text-gray-700">Deskripsi</label>
                                        <textarea x-model="descValue" rows="5" class="w-full border rounded px-3 py-2"
                                            placeholder="Tulis deskripsi item di sini..."></textarea>
                                    </div>

                                    <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
                                        <button type="button" @click="closeDesc()"
                                            class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">
                                            {{ 'Batal' }}
                                        </button>
                                        <button type="button" @click="applyDesc()"
                                            class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
                                            Simpan
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" id="itemsCount" :value="submitItems.length">

                            {{-- MODAL ERROR: belum ada item --}}
                            <div x-show="showNoItems && submitItems.length === 0" x-cloak
                                class="fixed inset-0 z-[90] flex items-center justify-center" x-transition.opacity>
                                <div class="absolute inset-0 bg-black/50" @click="showNoItems=false"></div>

                                <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden"
                                    x-transition.scale>
                                    <div class="px-5 py-4 border-b flex items-center">
                                        <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-500 mr-2" />
                                        <h3 class="text-lg font-semibold text-gray-800">{{ 'Tidak Ada Item' }}</h3>
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
                                            class="h-9 px-4 rounded-lg bg-blue-600 text-white text-sm font-bold hover:bg-blue-700">
                                            OK
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <x-transaction.browse-customer-modal />

                            <x-transaction.browse-warehouse-modal />

                            <x-transaction.browse-product-modal show-controls="true" show-pagination="true" />

                            <div class="mt-8 flex w-full items-center justify-center gap-4">
                                <button type="submit"
                                    class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                                    <x-heroicon-o-check class="w-5 h-5 mr-2" /> Simpan
                                </button>
                                <button type="button" @click="window.location.href='{{ route('suratjalan.index') }}'"
                                    class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" /> Keluar
                                </button>
                            </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
@endpush
<x-transaction.datatables-length-styles :tables="['supplierTable', 'warehouseTable', 'productTable', 'poTable', 'invoiceTable']" />
{{-- DATA & SCRIPTS --}}
<script>
    // Map produk untuk auto-fill tabel
    window.PRODUCT_MAP = {
        @foreach ($products as $p)
            "{{ $p->fprdcode }}": {
                name: @json($p->fprdname),
                default_unit: @json($productMap[$p->fprdcode]['default_unit'] ?? $p->fsatuankecil),
                units: @json(
                    $productMap[$p->fprdcode]['units'] ??
                        array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2]))),
                stock: @json($p->fminstock ?? 0),
                unit_ratios: {
                    satuankecil: 1,
                    satuanbesar: @json((float) ($p->fqtykecil ?? 1)),
                    satuanbesar2: @json((float) ($p->fqtykecil2 ?? 1)),
                },
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

    window.getSuratJalanDuplicateCode = function(form) {
        return '';
    };

    function itemsTable() {
        return {
            savedItems: @json($initialSuratJalanItems),
            nextFormIndex: @json($nextSuratJalanItemIndex),
            minimumVisibleRows: 5,
            browseTarget: null,
            showDescModal: false,
            descSavedIndex: null,
            descItemName: '',
            descValue: '',

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

            fmtMoney(value) {
                return this.fmt(value);
            },

            recalc(row) {
                this.$nextTick(() => {
                    row.fqty = Math.max(0, Number(row.fqty) || 0);
                    row.fterima = Math.max(0, Number(row.fterima) || 0);
                    row.fprice = Math.max(0, Number(row.fprice) || 0);

                    row.ftotal = Number((row.fqty * row.fprice).toFixed(2));

                    this.recalcTotals();
                });
            },

            recalcTotals() {
                this.totalHarga = (this.savedItems || []).reduce((sum, it) => {
                    if (!this.isRowSavable(it)) return sum;
                    const v = Number(it?.ftotal ?? 0);
                    return sum + (Number.isFinite(v) ? v : 0);
                }, 0);
            },

            removeSaved(i) {
                this.savedItems.splice(i, 1);
                this.syncDescList?.();
                this.ensureMinimumRows();
                this.ensureTrailingRow();
                this.recalcTotals();
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

            formatStockLimit(row) {
                return '';
            },

            rowHasContent(row) {
                return [
                    row?.fitemcode,
                    row?.fitemname,
                    row?.fsatuan,
                    row?.frefdtno,
                    row?.frefpr,
                    row?.frefso,
                    row?.fqty,
                    row?.fprice,
                    row?.ftotal,
                    row?.fdesc,
                    row?.fketdt,
                ].some((value) => {
                    if (typeof value === 'number') return value !== 0;
                    return String(value ?? '').trim() !== '';
                });
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

            onRowUpdated(index = null) {
                const row = typeof index === 'number' ? this.savedItems[index] : null;
                if (row) {
                    this.recalc(row);
                }
                this.recalcTotals();
                this.ensureTrailingRow(index);
            },

            getRowQtyLimit(row) {
                if (!row?.fitemcode) return 0;

                const meta = this.productMeta(row.fitemcode);
                const limitSource = Number(row.maxqty ?? 0);
                if (!Number.isFinite(limitSource) || limitSource <= 0) return 0;

                const units = meta.units || [];
                const ratios = meta.unit_ratios || {
                    satuankecil: 1,
                    satuanbesar: 1,
                    satuanbesar2: 1
                };
                const satuan = row.fsatuan || '';
                const satKecil = units[0] || 'pcs';
                const satBesar = units[1] || '';
                const satBesar2 = units[2] || '';

                let ratio = 1;
                if (satuan === satBesar2 && ratios.satuanbesar2 > 0) {
                    ratio = ratios.satuanbesar2;
                } else if (satuan === satBesar && ratios.satuanbesar > 0) {
                    ratio = ratios.satuanbesar;
                } else if (satuan === satKecil) {
                    ratio = 1;
                }

                const limit = limitSource / ratio;
                return Number.isFinite(limit) && limit > 0 ? limit : 0;
            },

            formatQtyLimit(limit) {
                const numericLimit = Number(limit ?? 0);
                if (!Number.isFinite(numericLimit) || numericLimit <= 0) {
                    return '0';
                }

                return numericLimit.toFixed(2).replace(/\.00$/, '').replace(/(\.\d*[1-9])0+$/, '$1');
            },

            validateSoQtyRow(row, showToast = true) {
                const refDoc = String(row?.frefso ?? '').trim();
                if (!refDoc) return true;
                const refLabel = refDoc.startsWith('INV.') ? 'Faktur Penjualan' : 'SO';

                const limit = this.getRowQtyLimit(row);
                if (limit <= 0) {
                    if (showToast) {
                        window.toast?.error(`Qty ${refLabel} untuk item ini sudah habis atau sudah digunakan.`);
                    }
                    return false;
                }

                const qty = Number(row?.fqty ?? 0);
                if (qty > limit) {
                    if (showToast) {
                        window.toast?.error(
                            `Qty melebihi sisa ${refLabel}. Maksimal ${this.formatQtyLimit(limit)} ${row.fsatuan || ''}`
                            .trim());
                    }
                    return false;
                }

                return Number(row?.fqty ?? 0) > 0;
            },

            enforceQtyRow(row) {
                const n = +row.fqty;
                const meta = this.productMeta(row.fitemcode);
                const units = meta?.units || [];
                const ratios = meta?.unit_ratios || {
                    satuankecil: 1,
                    satuanbesar: 1,
                    satuanbesar2: 1
                };
                const satKecil = units[0] || 'pcs';
                const satBesar = units[1] || '';
                const satBesar2 = units[2] || '';
                const satuan = row.fsatuan || '';

                let ratio = 1;
                if (satuan === satBesar2 && ratios.satuanbesar2 > 0) {
                    ratio = ratios.satuanbesar2;
                } else if (satuan === satBesar && ratios.satuanbesar > 0) {
                    ratio = ratios.satuanbesar;
                }

                if (!Number.isFinite(n)) {
                    row.fqty = 0;
                    return;
                }
                if (n < 0) row.fqty = 0;
            },

            hydrateRowFromMeta(row, meta, forceDefaultUnit = false) {
                if (!meta) {
                    row.fitemname = '';
                    row.units = [];
                    row.fsatuan = '';
                    row.maxqty = 0;
                    row.frefdtno = '';
                    return;
                }
                row.fitemname = meta.name || '';
                const units = [...new Set((meta.units || []).map(u => (u ?? '').toString().trim()).filter(Boolean))];
                const currentUnit = (row.fsatuan ?? '').toString().trim();
                row.units = units;
                const defaultUnit = (meta.default_unit || '').toString().trim();
                const resolvedDefaultUnit = defaultUnit && units.includes(defaultUnit) ? defaultUnit : (units[0] || '');
                if (forceDefaultUnit) {
                    row.fsatuan = resolvedDefaultUnit;
                } else {
                    row.fsatuan = currentUnit;
                    if (currentUnit && !units.includes(currentUnit)) {
                        row.units.unshift(currentUnit);
                    }
                    if (!row.fsatuan) {
                        row.fsatuan = resolvedDefaultUnit;
                    }
                }
                if (meta.unit_ratios) row.unit_ratios = meta.unit_ratios;
                row.maxqty = Number.isFinite(+row.maxqty) ? +row.maxqty : 0;
            },

            onCodeTypedRow(row, index = null) {
                const hasReference = String(row?.frefso ?? '').trim() !== '' || String(row?.frefdtno ?? '').trim() !==
                    '';
                if (hasReference) {
                    row.fitemcode = (row?.foriginalitemcode ?? row?.fitemcode ?? '').toString().trim();
                    this.onRowUpdated(index);
                    return;
                }
                this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode), true);
                row.fnoacak = this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak(row.uid);
                this.onRowUpdated(index);
            },

            isRowSavable(row) {
                return row.fitemcode && row.fitemname && row.fsatuan && Number(row.fqty) > 0;
            },

            normalizeNoAcak(value) {
                const normalized = String(value ?? '').trim();
                return /^\d{3}$/.test(normalized) ? normalized : '';
            },

            generateUniqueNoAcak(exceptUid = null) {
                const used = new Set(
                    this.savedItems
                    .filter(item => item.uid !== exceptUid)
                    .map(item => this.normalizeNoAcak(item.fnoacak))
                    .filter(Boolean)
                );
                let candidate = '';
                do {
                    candidate = Array.from({
                        length: 3
                    }, () => '123456789' [Math.floor(Math.random() * 9)]).join('');
                } while (used.has(candidate));

                return candidate;
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
                if (!items || !Array.isArray(items)) {
                    window.toast?.error('Data items tidak valid atau kosong.');
                    return;
                }

                const internalNoteInput = document.getElementById('fketinternal');
                if (internalNoteInput) {
                    const currentValue = String(internalNoteInput.value ?? '').trim();
                    const sourceValue = String(header?.fketinternal ?? '').trim();
                    if (sourceValue !== '' && currentValue === '') {
                        internalNoteInput.value = sourceValue;
                    }
                }

                const shippingAddressInput = document.querySelector('textarea[name="fkirim"]');
                if (shippingAddressInput) {
                    const currentValue = String(shippingAddressInput.value ?? '').trim();
                    const sourceValue = String(header?.falamatkirim ?? '').trim();
                    if (sourceValue !== '' && currentValue === '') {
                        shippingAddressInput.value = sourceValue;
                    }
                }

                const existing = new Set(this.getCurrentItemKeys());
                let added = 0,
                    duplicates = [],
                    skipped = [];
                const rowsToAdd = [];

                items.forEach((src, index) => {
                    const itemcode = (src.fitemcode ?? '').toString().trim();
                    const itemname = (src.fitemname ?? '').toString().trim();
                    const satuan = (src.fsatuan ?? '').toString().trim();
                    const frefdtno = (header?.fstockmtno ?? header?.fsono ?? src.frefdtno ?? '').toString()
                        .trim();

                    // VALIDASI MINIMAL: harus ada kode, nama, dan satuan
                    if (!itemcode || !itemname || !satuan) {
                        skipped.push({
                            code: itemcode || 'NO_CODE',
                            reason: 'Data tidak lengkap'
                        });
                        return;
                    }

                    const meta = this.productMeta(itemcode);
                    const normalizedUnits = meta ? [...new Set((meta.units || []).map(u => (u ?? '').toString()
                        .trim()).filter(Boolean))] : [satuan].filter(Boolean);

                    if (satuan && !normalizedUnits.includes(satuan)) {
                        normalizedUnits.unshift(satuan);
                    }

                    const row = {
                        uid: cryptoRandom(),
                        fitemcode: itemcode,
                        fitemname: itemname,
                        fsatuan: satuan,
                        frefdtno: frefdtno,
                        fnoacak: this.generateUniqueNoAcak(),
                        frefnoacak: this.normalizeNoAcak(src.frefnoacak ?? src.fnoacak ?? ''),
                        frefno_display: (src.frefpr ?? header?.fsono ?? '').toString().trim(),
                        frefpr: (src.frefpr ?? header?.fpono ?? header?.fsono ?? '').toString().trim(),
                        frefso: header?.fsono ?? null,
                        fdiscpersen: Number(src.fdiscpersen ?? 0),
                        fqty: (src.fqty !== null && src.fqty !== undefined && Number(src.fqty) > 0) ?
                            Number(src.fqty) : 1,
                        fprice: Number(src.fprice ?? src.fharga ?? 0), // ← Boleh 0
                        fterima: Number(src.fterima ?? 0),
                        ftotal: 0,
                        fdesc: src.fdesc ? src.fdesc.toString().trim() : '',
                        fketdt: src.fketdt ? src.fketdt.toString().trim() : '',
                        units: normalizedUnits,
                        maxqty: Math.max(0, Number(src.maxqty ?? src.fqtyremain ?? src.fqty ?? 0)),
                    };

                    if (!(Number(row.maxqty) > 0)) return;
                    row.ftotal = Number((row.fqty * row.fprice).toFixed(2));

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

                    rowsToAdd.push({
                        ...this.createRow(),
                        ...row,
                        foriginalitemcode: row.fitemcode,
                    });
                    existing.add(key);
                    added++;
                });

                if (rowsToAdd.length > 0) {
                    const shouldReplaceStarter = this.savedItems.every((row) => !this.rowHasContent(row));
                    if (shouldReplaceStarter) {
                        this.savedItems = rowsToAdd;
                    } else {
                        this.savedItems.push(...rowsToAdd);
                    }
                }

                this.ensureMinimumRows();
                this.ensureTrailingRow();
                this.recalcTotals();

                // Tampilkan notifikasi
                if (added > 0) {
                    window.toast?.success(`✓ Berhasil menambahkan ${added} item ke detail`);
                }

                if (duplicates.length > 0) {
                    window.toast?.info(`${duplicates.length} item diabaikan (sudah ada)`);
                }

                if (skipped.length > 0) {
                    window.toast?.error(`${skipped.length} item diabaikan (data tidak lengkap)`);
                }

                if (added === 0 && duplicates.length === 0 && skipped.length === 0) {
                    window.toast?.error('Tidak ada item yang valid untuk ditambahkan');
                }
            },
            get submitItems() {
                return this.savedItems.filter(row => this.isRowSavable(row));
            },

            openDesc(index = null) {
                this.descSavedIndex = index;
                this.descItemName = index !== null ? (this.savedItems[index]?.fitemname || '') : '';
                this.descValue = index !== null ? (this.savedItems[index]?.fdesc || '') : '';
                this.showDescModal = true;
            },
            copyDescName() {
                this.descValue = this.descItemName || '';
            },
            closeDesc() {
                this.showDescModal = false;
                this.descSavedIndex = null;
                this.descItemName = '';
                this.descValue = '';
            },
            applyDesc() {
                if (this.descSavedIndex !== null) {
                    this.savedItems[this.descSavedIndex].fdesc = this.descValue;
                    this.onRowUpdated(this.descSavedIndex);
                }
                this.closeDesc();
            },

            itemKey(it) {
                return (it.fitemcode ?? '').toString().trim().toUpperCase();
            },

            getCurrentItemKeys() {
                return this.savedItems
                    .filter(it => it.fitemcode)
                    .map(it => this.itemKey(it));
            },

            allocateFormIndex() {
                const index = Number(this.nextFormIndex || 0);
                this.nextFormIndex = index + 1;
                return index;
            },

            createRow(overrides = {}) {
                return {
                    ...newRow(),
                    uid: overrides.uid || cryptoRandom(),
                    formIndex: overrides.formIndex ?? this.allocateFormIndex(),
                    ...overrides,
                    foriginalitemcode: (overrides.foriginalitemcode ?? overrides.fitemcode ?? '').toString().trim(),
                    fnoacak: this.normalizeNoAcak(overrides.fnoacak) || this.generateUniqueNoAcak(overrides.uid ||
                        null),
                    frefnoacak: this.normalizeNoAcak(overrides.frefnoacak),
                };
            },

            focusRowUnit(row, index) {
                if (row.units && row.units.length > 1) {
                    this.$nextTick(() => document.getElementById(`unit_row_${index}`)?.focus());
                    return;
                }
                this.focusRowQty(index);
            },

            focusRowQty(index) {
                this.$nextTick(() => document.getElementById(`qty_row_${index}`)?.focus());
            },

            normalizeRestoredRow(item, index = 0) {
                const keepOldValues = Boolean(item?.is_restored_old);
                const row = this.createRow({
                    ...(item || {}),
                    uid: item?.uid || `restored-${index}`,
                });
                const oldValues = keepOldValues ? {
                    fitemname: row.fitemname,
                    fsatuan: row.fsatuan,
                    frefdtno: row.frefdtno,
                    frefpr: row.frefpr,
                    frefso: row.frefso,
                    frefno_display: row.frefno_display,
                } : null;
                this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode));
                if (oldValues) {
                    Object.assign(row, oldValues);
                    if (row.fsatuan && !row.units.includes(row.fsatuan)) {
                        row.units.unshift(row.fsatuan);
                    }
                }
                this.recalc(row);
                return row;
            },

            pruneEmptyRows() {
                const filled = this.savedItems.filter(row => this.rowHasContent(row));
                this.savedItems = filled.length ? filled : [];
            },

            init() {
                window.getCurrentItemKeys = () => this.getCurrentItemKeys();
                this.savedItems = Array.isArray(this.savedItems) ?
                    this.savedItems.map((item, index) => this.normalizeRestoredRow(item, index)) : [];
                this.pruneEmptyRows();
                this.ensureMinimumRows();
                this.ensureTrailingRow();

                window.addEventListener('pr-picked', this.onPrPicked.bind(this), {
                    passive: true
                });

                window.addEventListener('product-chosen', (e) => {
                    const {
                        product
                    } = e.detail || {};
                    if (!product) return;

                    const index = Number.isInteger(this.browseTarget) ? this.browseTarget : -1;
                    if (index < 0 || !this.savedItems[index]) return;

                    const row = this.savedItems[index];
                    const apply = () => {
                        row.fitemcode = (product.fprdcode || '').toString();
                        row.frefdtno = '';
                        row.frefpr = '';
                        row.frefso = null;
                        row.frefno_display = '';
                        this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode), true);
                        row.fnoacak = this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak(row
                            .uid);
                        this.onRowUpdated(index);
                    };
                    apply();
                    this.focusRowQty(index);
                }, {
                    passive: true
                });

                this.$nextTick(() => {
                    this.recalcTotals();
                });

                this.autoLoadSalesOrder();
            },

            async autoLoadSalesOrder() {
                const salesOrderId = @json($autoLoadSalesOrderId ?? null);
                if (!salesOrderId) return;

                try {
                    const url = @json(route('salesorder.items', ['id' => 'SO_ID_PLACEHOLDER']))
                        .replace('SO_ID_PLACEHOLDER', salesOrderId);
                    const res = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!res.ok) {
                        throw new Error(`Server error: ${res.status}`);
                    }

                    const json = await res.json();
                    window.applyTransactionCustomerSelection?.({
                        fcustomercode: json.header?.fcustno ?? '',
                        fcustomername: json.header?.fcustomername ?? '',
                    });

                    const shippingAddressInput = document.querySelector('textarea[name="fkirim"]');
                    if (shippingAddressInput && String(shippingAddressInput.value ?? '').trim() === '') {
                        shippingAddressInput.value = String(json.header?.falamatkirim ?? '').trim();
                    }

                    this.addManyFromPR(json.header, json.items || []);
                } catch (e) {
                    console.error('Auto-load Sales Order gagal:', e);
                    window.toast?.error(`Gagal mengambil detail Sales Order: ${e.message}`);
                }
            },

            openBrowseFor(index) {
                this.browseTarget = index;
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
                formIndex: null,
                fitemcode: '',
                fitemname: '',
                units: [],
                fsatuan: '',
                frefdtno: '',
                fnoacak: '',
                frefnoacak: '',
                frefno_display: '',
                frefpr: '',
                frefso: null,
                foriginalitemcode: '',
                fqty: 0,
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

@include('components.transaction.suratjalan-so-modal-script')
@include('components.transaction.suratjalan-invoice-modal-script')

<script>
    // Helper function untuk format tanggal
    function formatDate(s) {
        if (!s || s === 'No Date') return '-';
        const d = new Date(s);
        if (isNaN(d)) return '-';
        const pad = n => n.toString().padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
    }
</script>

<script>
    window.PRODUCT_MAP = @json($productMap ?? []);
</script>

@include('components.transaction.browse-customer-script')

@include('components.transaction.browse-warehouse-script')

<script>
    // Helper: update field saat warehouse-picked
    document.addEventListener('DOMContentLoaded', () => {
        window.addEventListener('warehouse-picked', (ev) => {
            const {
                fwhcode,
                fwhid
            } = ev.detail || {};

            const sel = document.getElementById('warehouseSelect');
            const hid = document.getElementById('warehouseIdHidden');
            // TAMBAHKAN INI:
            const codeHid = document.getElementById('warehouseCodeHidden');

            if (sel) {
                const opt = [...sel.options].find(o => String(o.value).trim() === String(fwhcode).trim());
                sel.value = opt ? opt.value : (fwhcode || '');
                sel.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }

            if (hid) hid.value = fwhid || '';

            // TAMBAHKAN LOGIKA INI:
            if (codeHid) {
                codeHid.value = fwhcode || ''; // Ini yang akan mengisi 'ffrom'
            }
        });
    });

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

@include('components.transaction.browse-product-script', [
    'showControls' => true,
    'showPagination' => true,
    'supportsForEdit' => true,
    'openDelay' => 50,
])

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
