@extends('layouts.app')

@section('title', $action === 'delete' ? 'Hapus Surat Jalan' : 'Edit Surat Jalan')

@section('content')
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
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow p-0 overflow-hidden" role="alert">
            {{-- Header Strip --}}
            <div class="d-flex align-items-center px-4 py-3" style="background-color: #c0392b;">
                <i class="bi bi-exclamation-triangle-fill text-white me-2 fs-5"></i>
                <strong class="text-white fs-6">Gagal Menyimpan Data!</strong>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert"
                    aria-label="Close"></button>
            </div>

            {{-- Body --}}
            <div class="px-4 py-3" style="background-color: #fdeded; border-left: 5px solid #c0392b;">
                <p class="mb-2 text-danger fw-semibold">
                    <i class="bi bi-info-circle me-1"></i>
                    Periksa kembali data berikut sebelum menyimpan:
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
    @php
        $usageLocked = !empty($isUsageLocked);
    @endphp
    @if ($usageLocked)
        <div x-data="{ open: true }" x-show="open" x-cloak class="fixed inset-0 z-[99] flex items-center justify-center"
            x-transition.opacity>
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
            <div class="relative bg-white w-[92vw] max-w-xl rounded-2xl shadow-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-orange-100 bg-orange-50 flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center">
                        <x-heroicon-o-lock-closed class="w-5 h-5 text-orange-600" />
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-bold text-orange-700">
                            {{ $action === 'delete' ? 'Surat Jalan Tidak Dapat Dihapus' : 'Surat Jalan Tidak Dapat Diedit' }}
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
    <div x-data="{ open: true }">
        <div x-data="{ includePPN: false, ppnRate: 0, ppnAmount: 0, totalHarga: 100000 }" class="lg:col-span-5">
            <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1600px] w-full mx-auto">
                {{-- ============================================ --}}
                {{-- MODE DELETE: VIEW ONLY + BUTTON HAPUS       --}}
                {{-- ============================================ --}}
                @if ($action === 'delete')
                    <div class="space-y-4">

                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                            <div class="lg:col-span-4">
                                <label class="block text-sm font-bold">Cabang</label>
                                <input type="text" class="w-full border rounded px-3 py-2 bg-gray-200 cursor-not-allowed"
                                    value="{{ $fcabang }}" disabled>
                                <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                            </div>
                            <div class="lg:col-span-4" x-data="{ autoCode: true }">
                                <label class="block text-sm font-bold mb-1">Transaksi#</label>
                                <div class="flex items-center gap-3">
                                    <input type="text" name="fstockmtno" class="w-full border rounded px-3 py-2"
                                        value="{{ old('fstockmtno', $suratjalan->fstockmtno) }}" :disabled="autoCode"
                                        :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'">
                                    <label class="inline-flex items-center select-none">
                                        <input type="checkbox" x-model="autoCode" checked disabled>
                                        <span class="ml-2 text-sm text-gray-700">Auto</span>
                                    </label>
                                </div>
                            </div>

                            <div class="lg:col-span-4">
                                <label class="block text-sm font-bold">Tanggal</label>
                                <input type="date" name="fstockmtdate" value="{{ old('fstockmtdate') ?? date('Y-m-d') }}"
                                    disabled
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
                                            class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 bg-gray-200 cursor-not-allowed"
                                            disabled>
                                            <option value=""></option>
                                            @foreach ($customers as $customer)
                                                <option value="{{ $customer->fcustomercode }}" {{-- CEK DISINI: Bandingkan dengan data yang tersimpan di DB --}}
                                                    {{ old('fsupplier', $suratjalan->fsupplier) == $customer->fcustomercode ? 'selected' : '' }}>
                                                    {{ $customer->fcustomername }} ({{ $customer->fcustomercode }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="absolute inset-0" role="button" aria-label="Browse Customer"
                                            @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))">
                                        </div>
                                    </div>
                                    <input type="hidden" name="fsupplier" id="customerCodeHidden"
                                        value="{{ old('fsupplier', $suratjalan->fsupplier) }}">
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
                                                    {{ old('ffrom', $suratjalan->ffrom) == $wh->fwhcode ? 'selected' : '' }}>
                                                    {{ $wh->fwhcode }} - {{ $wh->fwhname }}
                                                </option>
                                            @endforeach
                                        </select>

                                        {{-- Overlay untuk buka browser gudang --}}
                                        <div class="absolute inset-0" role="button" aria-label="Browse warehouse"
                                            @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open'))"></div>
                                    </div>
                                    <input type="hidden" name="ffrom" id="warehouseCodeHidden"
                                        value="{{ old('ffrom', $suratjalan->ffrom) }}">
                                </div>
                            </div>

                            <div class="lg:col-span-12">
                                <label class="block text-sm font-bold">Kirim ke</label>
                                <textarea name="fkirim" rows="3" readonly
                                    class="w-full border rounded px-3 py-2 @error('fkirim') border-red-500 @enderror"
                                    placeholder="Tulis kirim tambahan di sini...">{{ old('fkirim', $suratjalan->fkirim) }}</textarea>
                                @error('fkirim')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="lg:col-span-12">
                                <label class="block text-sm font-bold">Keterangan</label>
                                <textarea readonly name="fket" rows="3"
                                    class="w-full border rounded px-3 py-2 text-gray-700 @error('fket') border-red-500 @enderror"
                                    placeholder="Tulis keterangan tambahan di sini...">{{ old('fket', $suratjalan->fket) }}</textarea>
                                @error('fket')
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
                                            <th class="p-2 text-left w-36">Ref.SO#</th>
                                            <th class="p-2 text-right w-24">Sat</th>
                                            <th class="p-2 text-right w-28">Qty</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <template x-for="(it, i) in savedItems" :key="it.uid">
                                            <tr class="border-t align-top">
                                                <td class="p-2" x-text="i + 1"></td>
                                                <td class="p-2 font-mono" x-text="it.fitemcode"></td>
                                                <td class="p-2 text-gray-800">
                                                    <div x-text="it.fitemname"></div>
                                                    <div x-show="it.fdesc" class="mt-1 text-xs">
                                                        <span
                                                            class="inline-block px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 mr-2">Deskripsi</span>
                                                        <span class="align-middle text-gray-600" x-text="it.fdesc"></span>
                                                    </div>
                                                </td>
                                                <td class="p-2" x-text="it.frefno_display || it.frefso || '-'"></td>
                                                <td class="p-2 text-right" x-text="it.fsatuan"></td>
                                                <td class="p-2 text-right" x-text="formatQtyValue(it.fqty)"></td>
                                                <td class="hidden">
                                                    <input type="hidden" name="fitemcode[]" :value="it.fitemcode">
                                                    <input type="hidden" name="fitemname[]" :value="it.fitemname">
                                                    <input type="hidden" name="fsatuan[]" :value="it.fsatuan">

                                                    <input type="hidden" name="frefdtno[]" :value="it.frefdtno">

                                                    <input type="hidden" name="frefpr[]" :value="it.frefpr">
                                                    <input type="hidden" name="fnoacak[]" :value="it.fnoacak">
                                                    <input type="hidden" name="frefnoacak[]" :value="it.frefnoacak">
                                                    <input type="hidden" name="fqty[]" :value="it.fqty">
                                                    <input type="hidden" name="fprice[]" :value="it.fprice">
                                                    <input type="hidden" name="ftotal[]" :value="it.ftotal">
                                                    <input type="hidden" name="fdesc[]" :value="it.fdesc">
                                                    <input type="hidden" name="fketdt[]" :value="it.fketdt">
                                                </td>
                                            </tr>

                                        </template>

                                        <tr x-show="editingIndex !== null" class="border-t align-top" x-cloak>
                                            <td class="p-2" x-text="(editingIndex ?? 0) + 1"></td>

                                            <td class="p-2">
                                                <div class="flex">
                                                    <input type="text"
                                                        class="flex-1 border rounded-l px-2 py-1 font-mono"
                                                        x-ref="editCode" x-model.trim="editRow.fitemcode"
                                                        @input="onCodeTypedRow(editRow)"
                                                        @keydown.enter.prevent="handleEnterOnCode('edit')">
                                                    <button type="button" @click="openBrowseFor('edit')"
                                                        class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50"
                                                        title="Cari Produk">
                                                        <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </td>

                                            <td class="p-2">
                                                <input type="text"
                                                    class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                    :value="editRow.fitemname" disabled>
                                            </td>

                                            <td class="p-2">
                                                <input type="text"
                                                    class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                    :value="editRow.frefno_display || editRow.frefso || '-'" disabled placeholder="Ref SO">
                                            </td>

                                            <td class="p-2">
                                                <template x-if="editRow.fsatuan && editRow.units.length > 1">
                                                    <select class="w-full border rounded px-2 py-1" x-ref="editUnit"
                                                        x-model="editRow.fsatuan"
                                                        @keydown.enter.prevent="$refs.editRefPr?.focus()">
                                                        <template x-for="u in editRow.units" :key="u">
                                                            <option :value="u" x-text="u"></option>
                                                        </template>
                                                    </select>
                                                </template>
                                                <template x-if="!editRow.fsatuan || editRow.units.length <= 1">
                                                    <input type="text"
                                                        class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                        :value="editRow.fsatuan || '-'" disabled>
                                                </template>
                                            </td>

                                            <td class="p-2 text-right">
                                                <input type="number" class="border rounded px-2 py-1 w-24 text-right"
                                                    type="number" x-ref="editQty" x-model.number="editRow.fqty"
                                                    @input="
                                                        recalc(editRow);
                                                        enforceQtyRow(editRow);
                                                        recalc(editRow);
                                                    "
                                                    @keydown.enter.prevent="$refs.editPrice?.focus()">
                                                <div class="text-xs text-gray-400 mt-0.5 text-right">
                                                    <span x-show="editRow.fitemcode"
                                                        x-html="formatStockLimit(editRow)"></span>
                                                </div>
                                            </td>
                                        </tr>

                                        <tr x-show="editingIndex !== null" class="border-b" x-cloak>
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                        </tr>

                                        <tr class="border-b">
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-center space-x-4">
                            @if ($usageLocked)
                                <button type="button" disabled title="{{ $usageLockMessage }}"
                                    class="bg-red-300 text-white px-6 py-2 rounded flex items-center cursor-not-allowed opacity-70">
                                    <x-heroicon-o-lock-closed class="w-5 h-5 mr-2" />
                                    Hapus
                                </button>
                            @else
                                <button type="button" onclick="showDeleteModal()"
                                    class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 flex items-center">
                                    <x-heroicon-o-trash class="w-5 h-5 mr-2" />
                                    Hapus
                                </button>
                            @endif
                            <button type="button" onclick="window.location.href='{{ route('suratjalan.index') }}'"
                                class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                                <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                                Kembali
                            </button>
                        </div>

                        {{-- ============================================ --}}
                        {{-- MODE EDIT: FORM EDITABLE                    --}}
                        {{-- ============================================ --}}
                    @else
                        <form action="{{ route('suratjalan.update', $suratjalan->fstockmtid) }}" method="POST"
                            class="mt-6" x-data="{ showNoItems: false }"
                            @submit.prevent="
        const n = Number(document.getElementById('itemsCount')?.value || 0);
        if (n < 1) { showNoItems = true } else { $el.submit() }
      ">
                            @csrf
                            @method('PATCH')

                            {{-- HEADER FORM --}}
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                                <div class="lg:col-span-4">
                                    <label class="block text-sm font-bold">Cabang</label>
                                    <input type="text"
                                        class="w-full border rounded px-3 py-2 bg-gray-200 cursor-not-allowed"
                                        value="{{ $fcabang }}" disabled>
                                    <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                                </div>
                                <div class="lg:col-span-4" x-data="{ autoCode: true }">
                                    <label class="block text-sm font-bold mb-1">Transaksi#</label>
                                    <div class="flex items-center gap-3">
                                        <input type="text" name="fstockmtno" class="w-full border rounded px-3 py-2"
                                            value="{{ old('fstockmtno', $suratjalan->fstockmtno) }}"
                                            :disabled="autoCode"
                                            :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'">
                                        <label class="inline-flex items-center select-none">
                                            <input type="checkbox" x-model="autoCode" checked>
                                            <span class="ml-2 text-sm text-gray-700">Auto</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="lg:col-span-4">
                                    <label class="block text-sm font-bold">Tanggal</label>
                                    <input type="date" name="fstockmtdate"
                                        value="{{ old('fstockmtdate') ?? date('Y-m-d') }}"
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
                                                    <option value="{{ $customer->fcustomercode }}" {{-- CEK DISINI: Bandingkan dengan data yang tersimpan di DB --}}
                                                        {{ old('fsupplier', $suratjalan->fsupplier) == $customer->fcustomercode ? 'selected' : '' }}>
                                                        {{ $customer->fcustomername }} ({{ $customer->fcustomercode }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="absolute inset-0" role="button" aria-label="Browse Customer"
                                                @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))">
                                            </div>
                                        </div>
                                        <input type="hidden" name="fsupplier" id="customerCodeHidden"
                                            value="{{ old('fsupplier', $suratjalan->fsupplier) }}">
                                        <button type="button"
                                            @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))"
                                            class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                                            title="Browse Customer">
                                            <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                        </button>
                                        <a href="{{ route('customer.create') }}" target="_blank" rel="noopener"
                                            class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50"
                                            title="Tambah Customer">
                                            <x-heroicon-o-plus class="w-5 h-5" />
                                        </a>
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
                                                        {{ old('ffrom', $suratjalan->ffrom) == $wh->fwhcode ? 'selected' : '' }}>
                                                        {{ $wh->fwhcode }} - {{ $wh->fwhname }}
                                                    </option>
                                                @endforeach
                                            </select>

                                            {{-- Overlay untuk buka browser gudang --}}
                                            <div class="absolute inset-0" role="button" aria-label="Browse warehouse"
                                                @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open'))">
                                            </div>
                                        </div>
                                        <input type="hidden" name="ffrom" id="warehouseCodeHidden"
                                            value="{{ old('ffrom', $suratjalan->ffrom_code ?? '') }}">
                                        <input type="hidden" name="fwhid" id="warehouseIdHidden"
                                            value="{{ old('fwhid', $suratjalan->ffrom) }}">

                                        {{-- Tombol-tombol Anda --}}
                                        <button type="button"
                                            @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open'))"
                                            class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                                            title="Browse Gudang">
                                            <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                        </button>
                                        <a href="{{ route('supplier.create') }}" target="_blank" rel="noopener"
                                            class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50"
                                            title="Tambah Supplier">
                                            <x-heroicon-o-plus class="w-5 h-5" />
                                        </a>
                                    </div>
                                </div>

                                <div class="lg:col-span-12">
                                    <label class="block text-sm font-bold">Kirim Ke</label>
                                    <textarea name="fkirim" rows="3"
                                        class="w-full border rounded px-3 py-2 @error('fkirim') border-red-500 @enderror"
                                        placeholder="Tulis Kirim Ke di sini...">{{ old('fkirim', $suratjalan->fkirim) }}</textarea>
                                    @error('fkirim')
                                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="lg:col-span-12">
                                    <label class="block text-sm font-bold">Keterangan</label>
                                    <textarea name="fket" rows="3"
                                        class="w-full border rounded px-3 py-2 @error('fket') border-red-500 @enderror"
                                        placeholder="Tulis keterangan tambahan di sini...">{{ old('fket', $suratjalan->fket) }}</textarea>
                                    @error('fket')
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
                                                <th class="p-2 text-right w-24">Qty</th>
                                                <th class="p-2 text-center w-36">Aksi</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <template x-for="(it, i) in savedItems" :key="it.uid">
                                                <!-- ROW UTAMA -->
                                                <tr class="border-t align-top">
                                                    <td class="p-2" x-text="i + 1"></td>
                                                    <td class="p-2 font-mono" x-text="it.fitemcode"></td>
                                                    <td class="p-2 text-gray-800">
                                                        <div x-text="it.fitemname" class="font-semibold"></div>
                                                    </td>
                                                    <td class="p-2">
                                                        <div x-text="it.frefno_display || it.frefdtno || '-'"
                                                            class="text-xs text-gray-500 italic"></div>
                                                    </td>
                                                    <td class="p-2 text-right">
                                                        <template x-if="it.fsatuan && it.units && it.units.length > 1">
                                                            <select class="border rounded px-1 py-0.5 text-xs w-20"
                                                                x-model="it.fsatuan">
                                                                <template x-for="u in it.units" :key="u">
                                                                    <option :value="u" x-text="u">
                                                                    </option>
                                                                </template>
                                                            </select>
                                                        </template>
                                                        <template x-if="!it.fsatuan || !(it.units && it.units.length > 1)">
                                                            <span x-text="it.fsatuan || '-'"></span>
                                                        </template>
                                                    </td>
                                                    <td class="p-2 text-right">
                                                        <input type="number"
                                                            class="border rounded px-2 py-1 w-24 text-right"
                                                            type="number" x-model.number="it.fqty"
                                                            @input="
                                                                recalc(it);
                                                                enforceQtyRow(it);
                                                                recalc(it);
                                                            "
                                                            @keydown.enter.prevent="$refs[`desc-${i}`]?.focus()">
                                                        <div class="text-xs text-gray-400 mt-0.5 text-right">
                                                            <span x-show="it.fitemcode"
                                                                x-html="formatStockLimit(it)"></span>
                                                        </div>
                                                    </td>
                                                    <td class="p-2 text-center text-xs">
                                                        <button type="button" @click="removeSaved(i)"
                                                            class="px-3 py-1 rounded bg-red-100 text-red-600 hover:bg-red-200 transition-colors">Hapus</button>
                                                    </td>

                                                    <!-- hidden inputs -->
                                                    <td class="hidden">
                                                        <input type="hidden" name="fitemcode[]" :value="it.fitemcode">
                                                        <input type="hidden" name="fitemname[]" :value="it.fitemname">
                                                        <input type="hidden" name="fsatuan[]" :value="it.fsatuan">
                                                        <input type="hidden" name="frefdtno[]" :value="it.frefdtno">
                                                        <input type="hidden" name="frefpr[]" :value="it.frefpr">
                                                        <input type="hidden" name="frefso[]" :value="it.frefso">
                                                        <input type="hidden" name="frefsoid[]" :value="it.frefsoid">
                                                        <input type="hidden" name="fnoacak[]" :value="it.fnoacak">
                                                        <input type="hidden" name="frefnoacak[]" :value="it.frefnoacak">
                                                        <input type="hidden" name="fqty[]" :value="it.fqty">
                                                        <input type="hidden" name="fprice[]" :value="it.fprice">
                                                        <input type="hidden" name="ftotal[]" :value="it.ftotal">
                                                        <input type="hidden" name="fdesc[]" :value="it.fdesc">
                                                        <input type="hidden" name="fketdt[]" :value="it.fketdt">
                                                    </td>
                                                </tr>

                                                <!-- ROW DESC RESTRICTED -->
                                                <tr class="border-b">
                                                    <td class="p-0"></td>
                                                    <td class="p-0"></td>
                                                    <td class="p-2" colspan="3">
                                                        <textarea x-model="it.fdesc" :x-ref="`desc-${i}`" rows="3" class="w-full border rounded px-2 py-1 text-xs"
                                                            placeholder="Deskripsi item (opsional)"></textarea>
                                                    </td>
                                                    <td class="p-0" colspan="2"></td>
                                                </tr>
                                            </template>

                                            <!-- REMOVED THE EDIT ROW SECTION -->

                                            <!-- ROW DRAFT UTAMA -->
                                            <tr class="border-t align-top">
                                                <td class="p-2" x-text="savedItems.length + 1"></td>

                                                <td class="p-2">
                                                    <div class="flex">
                                                        <input type="text"
                                                            class="flex-1 border rounded-l px-2 py-1 font-mono text-sm"
                                                            x-ref="draftCode" x-model.trim="draft.fitemcode"
                                                            @input="onCodeTypedRow(draft)"
                                                            @keydown.enter.prevent="handleEnterOnCode('draft')">
                                                        <button type="button" @click="openBrowseFor('draft')"
                                                            class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50"
                                                            title="Cari Produk">
                                                            <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                </td>

                                                <td class="p-2">
                                                    <input type="text"
                                                        class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                                        :value="draft.fitemname" disabled>
                                                </td>

                                                <td class="p-2">
                                                    <input type="text"
                                                        class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                                        :value="draft.frefno_display || draft.frefdtno" disabled
                                                        placeholder="Ref SO">
                                                </td>

                                                <td class="p-2">
                                                    <template x-if="draft.fsatuan && draft.units.length > 1">
                                                        <select id="draftUnitSelect"
                                                            class="w-full border rounded px-2 py-1 text-sm"
                                                            x-model="draft.fsatuan"
                                                            @keydown.enter.prevent="$refs.draftQty?.focus()">
                                                            <template x-for="u in draft.units" :key="u">
                                                                <option :value="u" x-text="u"></option>
                                                            </template>
                                                        </select>
                                                    </template>
                                                    <template x-if="!draft.fsatuan || draft.units.length <= 1">
                                                        <input type="text"
                                                            class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                                            :value="draft.fsatuan || '-'" disabled>
                                                    </template>
                                                </td>

                                                <td class="p-2 text-right">
                                                    <input type="number"
                                                        class="border rounded px-2 py-1 w-24 text-right text-sm"
                                                        type="number" x-ref="draftQty" x-model.number="draft.fqty"
                                                        @input="
                                                            recalc(draft);
                                                            enforceQtyRow(draft);
                                                            recalc(draft);
                                                        "
                                                        @keydown.enter.prevent="addIfComplete()">
                                                    <div class="text-xs text-gray-400 mt-0.5 text-right">
                                                        <span x-show="draft.fitemcode"
                                                            x-html="formatStockLimit(draft)"></span>
                                                    </div>
                                                </td>

                                                <td class="p-2 text-center text-xs">
                                                    <button type="button" @click="addIfComplete()"
                                                        class="px-3 py-1 rounded bg-emerald-600 text-white hover:bg-emerald-700 transition-colors">Tambah</button>
                                                </td>
                                            </tr>

                                            <!-- ROW DRAFT DESC RESTRICTED -->
                                            <tr class="border-b">
                                                <td class="p-0"></td>
                                                <td class="p-0"></td>
                                                <td class="p-2" colspan="3">
                                                    <textarea x-model="draft.fdesc" x-ref="draftDesc" rows="3" class="w-full border rounded px-2 py-1 text-xs"
                                                        placeholder="Deskripsi item (opsional)" @keydown.enter.prevent="addIfComplete()"></textarea>
                                                </td>
                                                <td class="p-0" colspan="2"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                {{-- SO --}}
                                <div x-data="soFormModal()" class="mt-3">
                                    <div class="mt-3 flex justify-between items-start gap-4">
                                        <div class="w-full flex justify-start mb-3">
                                            <button type="button" @click="openModal()"
                                                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="1.5" d="M12 4.5v15m7.5-7.5h-15" />
                                                </svg>
                                                Add SO
                                            </button>
                                        </div>
                                    </div>

                                    {{-- MODAL SO --}}
                                    <div x-show="show" x-cloak x-transition.opacity
                                        class="fixed inset-0 z-50 flex items-center justify-center p-4">
                                        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal()">
                                        </div>

                                        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-5xl flex flex-col overflow-hidden"
                                            style="height: 650px;">
                                            <!-- Header -->
                                            <div
                                                class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-teal-50 to-white">
                                                <div>
                                                    <h3 class="text-xl font-bold text-gray-800">Add SO</h3>
                                                    <p class="text-sm text-gray-500 mt-0.5">Pilih Purchase Order yang
                                                        diinginkan
                                                    </p>
                                                </div>
                                                <button type="button" @click="closeModal()"
                                                    class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-bold text-gray-700 text-sm">
                                                    Tutup
                                                </button>
                                            </div>

                                            <!-- Search & Length Menu -->
                                            <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                                                <div id="poTableControls"></div>
                                            </div>

                                            <!-- Table with fixed height and scroll -->
                                            <div class="flex-1 overflow-y-auto px-6" style="min-height: 0;">
                                                <div class="bg-white">
                                                    <table id="poTable"
                                                        class="min-w-full text-sm display nowrap stripe hover"
                                                        style="width:100%">
                                                        <thead class="sticky top-0 z-10">
                                                            <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                                                <th
                                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                    SO No</th>
                                                                <th
                                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                    No Ref</th>
                                                                <th
                                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                                    Tanggal</th>
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

                                    {{-- MODAL DUPLIKASI --}}
                                    <div x-show="showDupModal" x-cloak x-transition.opacity
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
                                                <h3 class="text-lg font-semibold text-gray-800">Item Duplikat Ditemukan
                                                </h3>
                                            </div>

                                            <!-- Body -->
                                            <div class="px-5 py-4 space-y-3">
                                                <p class="text-sm text-gray-700">
                                                    Ditemukan <span class="font-semibold text-amber-600"
                                                        x-text="dupCount"></span>
                                                    item duplikat.
                                                    Item duplikat <span class="font-semibold">tidak akan
                                                        ditambahkan</span>.
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
                                                            <li class="px-3 py-2 text-sm text-gray-500 text-center">Tidak
                                                                ada
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
                                                    Batal
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

                                        <div class="px-5 py-4 space-y-2">
                                            <label class="block text-sm text-gray-700">Deskripsi</label>
                                            <textarea x-model="descValue" rows="5" class="w-full border rounded px-3 py-2"
                                                placeholder="Tulis deskripsi item di sini..."></textarea>
                                        </div>

                                        <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
                                            <button type="button" @click="closeDesc()"
                                                class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-bold hover:bg-gray-200">
                                                Batal
                                            </button>
                                            <button type="button" @click="applyDesc()"
                                                class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-bold hover:bg-emerald-700">
                                                Simpan
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <input type="hidden" id="itemsCount" :value="savedItems.length">

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

                                        <div class="px-5 py-4 space-y-2">
                                            <label class="block text-sm text-gray-700">Deskripsi</label>
                                            <textarea x-model="descValue" rows="5" class="w-full border rounded px-3 py-2"
                                                placeholder="Tulis deskripsi item di sini..."></textarea>
                                        </div>

                                        <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
                                            <button type="button" @click="closeDesc()"
                                                class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-bold hover:bg-gray-200">
                                                Batal
                                            </button>
                                            <button type="button" @click="applyDesc()"
                                                class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-bold hover:bg-emerald-700">
                                                Simpan
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <input type="hidden" id="itemsCount" :value="savedItems.length">
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
                                            class="h-9 px-4 rounded-lg bg-blue-600 text-white text-sm font-bold hover:bg-blue-700">
                                            OK
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <x-transaction.browse-customer-modal />

                            <x-transaction.browse-warehouse-modal />

            <x-transaction.browse-product-modal show-controls="true" show-pagination="true" />

            <div class="mt-8 flex justify-center gap-4">
                @if ($usageLocked)
                    <button type="button" disabled title="{{ $usageLockMessage }}"
                        class="bg-blue-300 text-white px-6 py-2 rounded flex items-center cursor-not-allowed opacity-70">
                        <x-heroicon-o-lock-closed class="w-5 h-5 mr-2" /> Simpan
                    </button>
                @else
                    <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                        <x-heroicon-o-check class="w-5 h-5 mr-2" /> Simpan
                    </button>
                @endif
                <button type="button" @click="window.location.href='{{ route('suratjalan.index') }}'"
                    class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" /> Keluar
                </button>
            </div>
            </form>
            @endif
        </div>
    </div>
    </div>

    {{-- ============================================ --}}
    {{-- MODAL & TOAST (HANYA UNTUK MODE DELETE)     --}}
    {{-- ============================================ --}}
    @if ($action === 'delete')
        {{-- Modal Delete --}}
        <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Konfirmasi Hapus Surat Jalan ini?</h3>
                <form id="deleteForm" action="{{ route('suratjalan.destroy', $suratjalan->fstockmtid) }}"
                    method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="flex justify-end space-x-2">
                        <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400"
                            id="btnTidak">
                            Tidak
                        </button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
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

                fetch('{{ route('suratjalan.destroy', $suratjalan->fstockmtid) }}', {
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
                        showToast(data.message || 'Data berhasil dihapus', true);

                        setTimeout(() => {
                            window.location.href = '{{ route('suratjalan.index') }}';
                        }, 500);
                    })
                    .catch(error => {
                        btnYa.disabled = false;
                        btnTidak.disabled = false;
                        btnYa.textContent = 'Ya, Hapus';
                        showToast('Terjadi kesalahan saat menghapus data', false);
                    });
            }
        </script>
    @endif
@endsection
@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
@endpush
<x-transaction.datatables-length-styles :tables="['supplierTable', 'warehouseTable', 'productTable', 'poTable']" />
{{-- DATA & SCRIPTS --}}
<script>
    // Map produk untuk auto-fill tabel
    window.PRODUCT_MAP = {
        @foreach ($products as $p)
            "{{ $p->fprdcode }}": {
                name: @json($p->fprdname),
                units: @json(array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2]))),
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

    function itemsTable() {
        return {
            showNoItems: false,
            savedItems: @json($savedItems),
            draft: newRow(),
            editingIndex: null,
            editRow: newRow(),

            totalHarga: 0,

                fmt(n) {
                if (n === null || n === undefined || n === '') return '-';
                const v = Number(n);
                if (!isFinite(v)) return '-';

                // Jika angka adalah bulat, hilangkan desimal
                if (Number.isInteger(v)) {
                    return v.toLocaleString('id-ID');
                } else {
                    return v.toLocaleString('id-ID', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },

            formatQtyValue(value) {
                const num = Number(value);
                if (!Number.isFinite(num)) return '0,00';
                const hasMoreThanTwoDecimals = Math.abs((num * 100) - Math.round(num * 100)) > 0.000001;
                const digits = hasMoreThanTwoDecimals ? 4 : 2;
                return num.toLocaleString('id-ID', {
                    minimumFractionDigits: digits,
                    maximumFractionDigits: digits
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

            fmtMoney(value) {
                return this.fmt(value);
            },

            recalc(row) {
                this.$nextTick(() => {
                    row.fqty = Math.max(1, Number(row.fqty) || 1);
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
                this.syncDescList?.();
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

                return Math.floor(limitSource / ratio);
            },

            validateSoQtyRow(row, showToast = true) {
                const soDetailId = Number(row?.frefsoid ?? 0);
                if (!(soDetailId > 0)) return true;

                const limit = this.getRowQtyLimit(row);
                if (limit <= 0) {
                    row.fqty = 0;
                    if (showToast) {
                        window.toast?.error('Qty SO untuk item ini sudah habis atau sudah digunakan.');
                    }
                    return false;
                }

                const qty = Number(row?.fqty ?? 0);
                if (qty > limit) {
                    row.fqty = limit;
                    if (showToast) {
                        window.toast?.error(`Qty melebihi sisa SO. Maksimal ${limit} ${row.fsatuan || ''}`.trim());
                    }
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
                    row.fqty = 1;
                    return;
                }
                if (n < 1) row.fqty = 1;
                this.validateSoQtyRow(row, false);
            },

            hydrateRowFromMeta(row, meta) {
                if (!meta) {
                    row.fitemname = '';
                    row.units = [];
                    row.fsatuan = '';
                    row.maxqty = 0;
                    row.frefdtno = 0;
                    if (row === this.draft) {
                        clearDraftUnitSelect();
                    }
                    return;
                }
                row.fitemname = meta.name || '';
                const units = [...new Set((meta.units || []).map(u => (u ?? '').toString().trim()).filter(Boolean))];
                row.units = units;
                if (!units.includes(row.fsatuan)) row.fsatuan = units[0] || '';
                if (meta.unit_ratios) row.unit_ratios = meta.unit_ratios;
                row.maxqty = Number.isFinite(+row.maxqty) ? +row.maxqty : 0;
                row.frefdtno = meta.fprdid || 0;

                if (row === this.draft) {
                    if (units.length > 1) {
                        populateDraftUnitSelect(units);
                    } else {
                        clearDraftUnitSelect();
                    }
                }
            },

            onCodeTypedRow(row) {
                this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode));
            },

            isComplete(row) {
                return row.fitemcode && row.fitemname && row.fsatuan && Number(row.fqty) > 0;
            },

            normalizeNoAcak(value) {
                const normalized = String(value ?? '').trim();
                return /^\d{3}$/.test(normalized) ? normalized : '';
            },

            generateUniqueNoAcak() {
                const used = new Set(this.savedItems.map(item => this.normalizeNoAcak(item.fnoacak)).filter(Boolean));
                let candidate = '';
                do {
                    candidate = Array.from({ length: 3 }, () => '123456789'[Math.floor(Math.random() * 9)]).join('');
                } while (used.has(candidate));

                return candidate;
            },

            onPrPicked(e) {
                const {
                    header,
                    items
                } = e.detail || {};
                if (!items || !Array.isArray(items)) return;

                this.resetDraft();
                this.addManyFromPR(header, items);
            },

            resetDraft() {
                this.draft = newRow();
                this.draft.fnoacak = this.generateUniqueNoAcak();
                this.$nextTick(() => this.$refs.draftCode?.focus());
            },

            addManyFromPR(header, items) {
                if (!items || !Array.isArray(items)) {
                    window.toast?.error('Data items tidak valid atau kosong.');
                    return;
                }

                const existing = new Set(this.getCurrentItemKeys());
                let added = 0,
                    duplicates = [],
                    skipped = [];

                items.forEach((src, index) => {
                    const itemcode = (src.fitemcode ?? '').toString().trim();
                    const itemname = (src.fitemname ?? '').toString().trim();
                    const satuan = (src.fsatuan ?? '').toString().trim();
                    const frefdtno = src.frefdtno ?? '';

                    // VALIDASI MINIMAL: harus ada kode, nama, dan satuan
                    if (!itemcode || !itemname || !satuan) {
                        skipped.push({
                            code: itemcode || 'NO_CODE',
                            reason: 'Data tidak lengkap'
                        });
                        return;
                    }

                    const meta = this.productMeta(itemcode);

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
                        frefsoid: src.frefdtno ?? null,
                        fqty: (src.fqty !== null && src.fqty !== undefined && Number(src.fqty) > 0) ?
                            Number(src.fqty) : 1,
                        fprice: Number(src.fprice ?? src.fharga ?? 0), // ← Boleh 0
                        fterima: Number(src.fterima ?? 0),
                        ftotal: 0,
                        fdesc: src.fdesc ? src.fdesc.toString().trim() : '',
                        fketdt: src.fketdt ? src.fketdt.toString().trim() : '',
                        units: meta ? [...new Set((meta.units || []).map(u => (u ?? '').toString().trim())
                            .filter(Boolean))] : [satuan].filter(Boolean),
                        maxqty: Math.max(0, Number(src.maxqty ?? src.fqtyremain ?? src.fqty ?? 0)),
                        hideQtyLimitHint: false,
                    };

                    if (!(Number(row.maxqty) > 0)) return;
                    if (Number(row.maxqty) > 0) {
                        row.fqty = Number(row.maxqty);
                    }
                    row.ftotal = Number((row.fqty * row.fprice).toFixed(2));
                    this.validateSoQtyRow(row, false);

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

                    this.savedItems.push(row);
                    existing.add(key);
                    added++;
                    this.recalc(row);
                });

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
            addIfComplete() {
                const r = this.draft;
                if (!this.isComplete(r)) {
                    if (!r.fitemcode) {
                        window.toast?.error('Kode item harus diisi');
                        return this.$refs.draftCode?.focus();
                    }
                    if (!r.fitemname) {
                        window.toast?.error('Nama item harus diisi');
                        return this.$refs.draftCode?.focus();
                    }
                    if (!r.fsatuan) {
                        window.toast?.error('Satuan harus dipilih');
                        return (r.units.length > 1 ? this.$refs.draftUnit?.focus() : this.$refs.draftCode?.focus());
                    }
                    if (!(Number(r.fqty) > 0)) {
                        window.toast?.error('Quantity harus lebih dari 0');
                        return this.$refs.draftQty?.focus();
                    }
                    return;
                }

                this.recalc(r);
                if (!this.validateSoQtyRow(r, true)) {
                    return this.$refs.draftQty?.focus();
                }

                const dupe = this.savedItems.find(it =>
                    it.fitemcode === r.fitemcode &&
                    it.fsatuan === r.fsatuan &&
                    (it.frefpr || '') === (r.frefpr || '')
                );

                if (dupe) {
                    window.toast?.error('Item ini sudah ada dalam daftar');
                    return;
                }

                this.savedItems.push({
                    ...r,
                    fnoacak: this.normalizeNoAcak(r.fnoacak) || this.generateUniqueNoAcak(),
                    frefnoacak: this.normalizeNoAcak(r.frefnoacak),
                    uid: cryptoRandom()
                });

                window.toast?.success('Item berhasil ditambahkan');

                this.showNoItems = false;
                this.resetDraft();
                this.$nextTick(() => this.$refs.draftCode?.focus());
                this.syncDescList?.();
                this.recalcTotals();
            },

            applyEdit() {
                const r = this.editRow;
                if (!this.isComplete(r)) {
                    window.toast?.error('Lengkapi data item terlebih dahulu');
                    return;
                }

                this.recalc(r);
                if (!this.validateSoQtyRow(r, true)) {
                    return this.$refs.editQty?.focus();
                }
                this.savedItems.splice(this.editingIndex, 1, {
                    ...r,
                    fnoacak: this.normalizeNoAcak(r.fnoacak) || this.generateUniqueNoAcak(),
                    frefnoacak: this.normalizeNoAcak(r.frefnoacak),
                });

                window.toast?.success('Item berhasil diupdate');

                this.cancelEdit();
                this.syncDescList?.();
                this.recalcTotals();
            },

            edit(i) {
                this.editingIndex = i;
                this.editRow = {
                    ...this.savedItems[i]
                };
                this.hydrateRowFromMeta(this.editRow, this.productMeta(this.editRow.fitemcode));
                this.$nextTick(() => this.$refs.editQty?.focus());
            },

            applyEdit() {
                const r = this.editRow;
                if (!this.isComplete(r)) {
                    alert('Lengkapi data item.');
                    return;
                }

                this.recalc(r);
                this.savedItems.splice(this.editingIndex, 1, {
                    ...r,
                    fnoacak: this.normalizeNoAcak(r.fnoacak) || this.generateUniqueNoAcak(),
                    frefnoacak: this.normalizeNoAcak(r.frefnoacak),
                });
                this.cancelEdit();
                this.syncDescList?.();
                this.recalcTotals();
            },

            cancelEdit() {
                this.editingIndex = null;
                this.editRow = newRow();
            },

            onSubmit($event) {
                if (this.savedItems.length === 0) {
                    $event.preventDefault();
                    this.showNoItems = true;
                    return;
                }
            },

            handleEnterOnCode(where) {
                if (where === 'edit') {
                    if (this.editRow.units.length > 1) this.$refs.editUnit?.focus();
                    else this.$refs.editQty?.focus();
                } else {
                    if (this.draft.units.length > 1) this.$refs.draftUnit?.focus();
                    else this.$refs.draftQty?.focus();
                }
            },

            handleEnterOnPrice(where) {
                if (where === 'edit') {
                    this.applyEdit();
                } else {
                    this.addIfComplete();
                }
            },

            showDescModal: false,
            descTarget: 'draft',
            descSavedIndex: null,
            descValue: '',
            openDesc() {},
            closeDesc() {},
            applyDesc() {},

            itemKey(it) {
                return `${(it.fitemcode ?? '').toString().trim()}::${(it.frefdtno ?? '').toString().trim()}`;
            },

            getCurrentItemKeys() {
                return this.savedItems.map(it => this.itemKey(it));
            },

            init() {
                window.getCurrentItemKeys = () => this.getCurrentItemKeys();
                this.savedItems = (this.savedItems || []).map(item => ({
                    ...item,
                    fnoacak: this.normalizeNoAcak(item.fnoacak) || this.generateUniqueNoAcak(),
                    frefnoacak: this.normalizeNoAcak(item.frefnoacak),
                }));

                this.savedItems.forEach((item) => {
                    const soLimit = Number(item.maxqty ?? item.fqtyremain ?? 0);
                    item.maxqty = Number.isFinite(soLimit) ? soLimit : 0;
                    item.hideQtyLimitHint = false;
                    this.validateSoQtyRow(item, false);
                });
                this.draft.fnoacak = this.generateUniqueNoAcak();

                window.addEventListener('pr-picked', this.onPrPicked.bind(this), {
                    passive: true
                });

                window.addEventListener('product-chosen', (e) => {
                    const {
                        product
                    } = e.detail || {};
                    if (!product) return;

                    const apply = (row) => {
                        row.fitemcode = (product.fprdcode || '').toString();
                        row.hideQtyLimitHint = true;
                        this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode));
                        row.fnoacak = this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak();
                        if (!row.fqty) row.fqty = 1;
                        this.recalc(row);
                    };

                    if (this.browseTarget === 'edit') {
                        apply(this.editRow);
                        this.$nextTick(() => this.$refs.editQty?.focus());
                    } else {
                        apply(this.draft);
                        this.$nextTick(() => this.$refs.draftQty?.focus());
                    }
                }, {
                    passive: true
                });

                const self = this;
                document.addEventListener('change', function(e) {
                    if (e.target && e.target.id === 'draftUnitSelect') {
                        self.draft.fsatuan = e.target.value;
                    }
                });

                this.$nextTick(() => {
                    this.recalcTotals();
                });
            },

            browseTarget: 'draft',
            openBrowseFor(where) {
                this.browseTarget = (where === 'edit' ? 'edit' : 'draft');
                window.dispatchEvent(new CustomEvent('browse-open', {
                    detail: {
                        forEdit: this.browseTarget === 'edit'
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
                fnoacak: '',
                frefnoacak: '',
                frefno_display: '',
                frefpr: '',
                frefso: null,
                frefsoid: null,
                fqty: 0,
                fprice: 0,
                ftotal: 0,
                fdesc: '',
                fketdt: '',
                maxqty: 0,
                hideQtyLimitHint: false,
            };
        }

        function cryptoRandom() {
            return (window.crypto?.getRandomValues ? [...window.crypto.getRandomValues(new Uint32Array(2))].map(n => n
                    .toString(16)).join('') :
                Math.random().toString(36).slice(2)) + Date.now();
        }

        function getDraftUnitSelect() {
            return document.getElementById('draftUnitSelect');
        }

        function populateDraftUnitSelect(units) {
            const sel = getDraftUnitSelect();
            if (!sel) return;
            sel.innerHTML = '';
            units.forEach(u => {
                const opt = document.createElement('option');
                opt.value = u;
                opt.textContent = u;
                sel.appendChild(opt);
            });
        }

        function clearDraftUnitSelect() {
            const sel = getDraftUnitSelect();
            if (sel) sel.innerHTML = '';
        }
    }
</script>

@include('components.transaction.suratjalan-so-modal-script')

<script>
    // Helper function untuk format tanggal
    function formatDate(s) {
        if (!s || s === 'No Date') return '-';
        const d = new Date(s);
        if (isNaN(d)) return '-';
        const pad = n => n.toString().padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }
</script>

<script>
    // Helper: update field saat warehouse-picked
    document.addEventListener('DOMContentLoaded', () => {
        window.addEventListener('warehouse-picked', (ev) => {
            const {
                fwhcode,
                fwhid
            } = ev.detail || {};
            const sel = document.getElementById('warehouseSelect');
            const hidId = document.getElementById('warehouseIdHidden');
            const hidCode = document.getElementById('warehouseCodeHidden');

            if (sel) {
                sel.value = fwhcode || '';
                sel.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }
            if (hidId) hidId.value = fwhid || '';
            if (hidCode) hidCode.value = fwhcode || '';
        });
    });
</script>

@include('components.transaction.browse-product-script', ['showControls' => true, 'showPagination' => true, 'supportsForEdit' => true, 'openDelay' => 50])

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    <script>
        window.PRODUCT_MAP = @json($productMap ?? []);
    </script>

    @include('components.transaction.browse-customer-script')

    @include('components.transaction.browse-warehouse-script')

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
