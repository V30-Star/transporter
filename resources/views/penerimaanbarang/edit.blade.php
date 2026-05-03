@extends('layouts.app')

@section('title', $action === 'delete' ? 'Hapus Penerimaan Barang' : 'Edit Penerimaan Barang')

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

        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

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
                            {{ $action === 'delete' ? 'Penerimaan Barang Tidak Dapat Dihapus' : 'Penerimaan Barang Tidak Dapat Diedit' }}
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
    <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1600px] w-full mx-auto">
        <form action="{{ $action === 'delete' ? '#' : route('penerimaanbarang.update', $penerimaanbarang->fstockmtid) }}"
            method="POST" class="mt-6" x-data="mainForm()" x-init="init()" @submit.prevent="submitForm($el)">

            @csrf
            @if ($action !== 'delete')
                @method('PATCH')
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                    <p class="font-semibold mb-1">Tidak dapat menyimpan</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- HEADER FORM --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

                <div class="lg:col-span-4">
                    <label class="block text-sm font-medium mb-1">Cabang</label>
                    <input type="text" class="w-full border rounded px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
                        value="{{ trim(($fbranchcode ?? '') . (($fcabang ?? '') ? ' - ' . $fcabang : '')) }}" disabled>
                    <input type="hidden" name="fbranchcode" value="{{ old('fbranchcode', $fbranchcode) }}">
                </div>
                
                {{-- Transaksi# — selalu disabled (nomor tidak bisa diubah) --}}
                <div class="lg:col-span-4">
                    <label class="block text-sm font-medium mb-1">Transaksi#</label>
                    <div class="flex items-center gap-3">
                        <input type="text" class="w-full border rounded px-3 py-2 bg-gray-200 cursor-not-allowed"
                            value="{{ $penerimaanbarang->fstockmtno }}" disabled>
                    </div>
                    <input type="hidden" name="fstockmtno" value="{{ $penerimaanbarang->fstockmtno }}">
                </div>

                <input type="hidden" name="fstockmtid" value="{{ $penerimaanbarang->fstockmtid }}">

                {{-- Supplier --}}
                <div class="lg:col-span-4">
                    <label class="block text-sm font-medium mb-1">Supplier</label>
                    <div class="flex">
                        <div class="relative flex-1">
                            <select id="modal_filter_supplier_id" name="filter_supplier_id"
                                class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
                                disabled>
                                <option value=""></option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->fsuppliercode }}"
                                        {{ old('fsupplier', $penerimaanbarang->fsupplier) == $supplier->fsuppliercode ? 'selected' : '' }}>
                                        {{ $supplier->fsuppliername }} ({{ $supplier->fsuppliercode }})
                                    </option>
                                @endforeach
                            </select>
                            @if ($action !== 'delete')
                                <div class="absolute inset-0" role="button" aria-label="Browse supplier"
                                    @click="window.dispatchEvent(new CustomEvent('supplier-browse-open'))"></div>
                            @endif
                        </div>
                        <input type="hidden" name="fsupplier" id="supplierCodeHidden"
                            value="{{ old('fsupplier', $penerimaanbarang->fsupplier) }}">
                        @if ($action !== 'delete')
                            <button type="button" @click="window.dispatchEvent(new CustomEvent('supplier-browse-open'))"
                                class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                                title="Browse Supplier">
                                <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                            </button>
                            <a href="{{ route('supplier.create') }}" target="_blank" rel="noopener"
                                class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50" title="Tambah Supplier">
                                <x-heroicon-o-plus class="w-5 h-5" />
                            </a>
                        @endif
                    </div>
                    @error('fsupplier')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Gudang --}}
                <div class="lg:col-span-4">
                    <label class="block text-sm font-medium mb-1">Gudang</label>
                    <div class="flex">
                        <div class="relative flex-1">
                            <select id="warehouseSelect"
                                class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
                                disabled>
                                <option value=""></option>
                                @foreach ($warehouses as $wh)
                                    <option value="{{ $wh->fwhcode }}" data-id="{{ $wh->fwhid }}"
                                        data-branch="{{ $wh->fbranchcode }}"
                                        {{ old('ffrom', $penerimaanbarang->ffrom) == $wh->fwhcode ? 'selected' : '' }}>
                                        {{ $wh->fwhcode }} - {{ $wh->fwhname }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($action !== 'delete')
                                <div class="absolute inset-0" role="button" aria-label="Browse warehouse"
                                    @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open'))"></div>
                            @endif
                        </div>
                        <input type="hidden" name="ffrom" id="warehouseCodeHidden"
                            value="{{ old('ffrom', $penerimaanbarang->ffrom) }}">
                        @if ($action !== 'delete')
                            <button type="button" @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open'))"
                                class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                                title="Browse Gudang">
                                <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                            </button>
                            <a href="{{ route('gudang.create') }}" target="_blank" rel="noopener"
                                class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50" title="Tambah Gudang">
                                <x-heroicon-o-plus class="w-5 h-5" />
                            </a>
                        @endif
                    </div>
                    @error('ffrom')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tanggal --}}
                <div class="lg:col-span-4">
                    <label class="block text-sm font-medium">Tanggal</label>
                    <input type="date" name="fstockmtdate"
                        value="{{ old('fstockmtdate', \Carbon\Carbon::parse($penerimaanbarang->fstockmtdate)->format('Y-m-d')) }}"
                        {{ $action === 'delete' ? 'disabled' : '' }}
                        class="w-full border rounded px-3 py-2
                            {{ $action === 'delete' ? 'bg-gray-100 cursor-not-allowed' : '' }}
                            @error('fstockmtdate') border-red-500 @enderror">
                    @error('fstockmtdate')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Keterangan --}}
                <div class="lg:col-span-12">
                    <label class="block text-sm font-medium">Keterangan</label>
                    <textarea name="fket" rows="3" {{ $action === 'delete' ? 'disabled' : '' }}
                        class="w-full border rounded px-3 py-2
                            {{ $action === 'delete' ? 'bg-gray-100 cursor-not-allowed' : '' }}
                            @error('fket') border-red-500 @enderror"
                        placeholder="Tulis keterangan tambahan di sini...">{{ old('fket', $penerimaanbarang->fket) }}</textarea>
                    @error('fket')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- DETAIL ITEM --}}
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
                                <th class="p-2 text-left w-36">Ref.PO#</th>
                                <th class="p-2 text-right w-24 whitespace-nowrap">Qty</th>
                                <th class="p-2 text-right w-32 whitespace-nowrap">@ Harga</th>
                                <th class="p-2 text-right w-36 whitespace-nowrap">Total Harga</th>
                                <th class="p-2 text-center w-20 {{ $action === 'delete' ? 'hidden' : '' }}">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            {{-- BARIS TERSIMPAN --}}
                            <template x-for="(it, i) in savedItems" :key="it.uid">
                                <tr class="border-t align-top transition-colors"
                                    :class="activeRow === it.uid ? 'bg-amber-50' : 'hover:bg-gray-50'">

                                    <td class="p-2 text-gray-500" x-text="i + 1"></td>

                                    {{-- Kode Produk --}}
                                    <td class="p-2">
                                        <div class="flex">
                                            <input type="text"
                                                class="flex-1 border rounded-l px-2 py-1 font-mono text-sm min-w-0
                                                    {{ $action === 'delete' ? 'bg-gray-100 cursor-not-allowed' : '' }}"
                                                x-model.trim="it.fitemcode" @focus="activeRow = it.uid"
                                                @blur="activeRow = null"
                                                @if ($action !== 'delete') @input="onCodeTypedSaved(it)"
                                                    @keydown.enter.prevent="focusSavedUnit(it, i)" @endif
                                                {{ $action === 'delete' ? 'disabled' : '' }}>
                                            @if ($action !== 'delete')
                                                <button type="button" @click="openBrowseFor('saved', i)"
                                                    class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50"
                                                    title="Cari Produk">
                                                    <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                </button>
                                            @else
                                                <span
                                                    class="border border-l-0 rounded-r px-2 py-1 bg-gray-100 text-gray-400 text-xs flex items-center">—</span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Nama Produk + Deskripsi --}}
                                    <td class="p-2 relative overflow-visible">
                                        <input type="text"
                                            class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                            :value="it.fitemname" disabled>
                                        <textarea x-model="it.fdesc" rows="2"
                                            class="border rounded px-2 py-1 text-xs text-gray-600 mt-1 relative z-10
                                                {{ $action === 'delete' ? 'bg-gray-100 cursor-not-allowed' : '' }}"
                                            style="width: calc(100% + 8rem);" placeholder="Deskripsi (opsional)" @focus="activeRow = it.uid"
                                            @blur="activeRow = null" {{ $action === 'delete' ? 'disabled' : '' }}></textarea>
                                    </td>

                                    {{-- Satuan --}}
                                    <td class="p-2 align-top">
                                        @if ($action !== 'delete')
                                            <template x-if="it.units.length > 1">
                                                <select class="w-full border rounded px-2 py-1 text-sm"
                                                    :id="'unit_saved_' + i" x-model="it.fsatuan"
                                                    @focus="activeRow = it.uid" @blur="activeRow = null"
                                                    @keydown.enter.prevent="focusSavedQty(i)"
                                                    @change="enforcePoQtyRow(it);">
                                                    <template x-for="u in it.units" :key="u">
                                                        <option :value="u" x-text="u"></option>
                                                    </template>
                                                </select>
                                            </template>
                                        @endif
                                        <input type="text"
                                            x-show="{{ $action === 'delete' ? 'true' : 'it.units.length <= 1' }}"
                                            class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                            :value="it.fsatuan || '-'" disabled>
                                    </td>

                                    {{-- Ref.PO# --}}
                                    <td class="p-2">
                                        <input type="text"
                                            class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                            :value="it.frefdtno || '-'" disabled>
                                    </td>

                                    {{-- Qty --}}
                                    <td class="p-2 text-right">
                                        @if ($action !== 'delete')
                                            <input type="number" class="border rounded px-2 py-1 w-20 text-right text-sm"
                                                x-model.number="it.fqty" :id="'qty_saved_' + i"
                                                @focus="activeRow = it.uid; $event.target.select()" @blur="activeRow = null"
                                                @input="
                                                    recalc(it);
                                                    calcMaxQty(it);
                                                "
                                                @change="
                                                    recalc(it);
                                                    calcMaxQty(it);
                                                "
                                                @keydown.enter.prevent="focusSavedPrice(i)">
                                            <div class="text-[10px] text-amber-700 font-medium text-right mt-0.5"
                                                x-show="it.frefdtid && calcMaxQty(it) > 0"
                                                x-html="formatPoRemainHint(it)">
                                            </div>
                                        @else
                                            <span class="text-sm" x-text="it.fqty"></span>
                                        @endif
                                    </td>

                                    {{-- @ Harga --}}
                                    <td class="p-2 text-right">
                                        <input type="number"
                                            class="border rounded px-2 py-1 w-28 text-right text-sm
                                                {{ $action === 'delete' ? 'bg-gray-100 cursor-not-allowed' : '' }}"
                                            min="0" step="0.01" x-model.number="it.fprice"
                                            :id="'price_saved_' + i" @focus="activeRow = it.uid; $event.target.select()"
                                            @blur="activeRow = null"
                                            @if ($action !== 'delete') @input="recalc(it)"
                                                @change="recalc(it)"
                                                @keydown.enter.prevent="focusSavedDisc(i)" @endif
                                            {{ $action === 'delete' ? 'disabled' : '' }}>
                                    </td>

                                    {{-- Total Harga --}}
                                    <td class="p-2 text-right text-sm font-medium" x-text="rupiah(it.ftotal)"></td>

                                    {{-- Aksi --}}
                                    <td class="p-2 text-center {{ $action === 'delete' ? 'hidden' : '' }}">
                                        @if ($action !== 'delete')
                                            <button type="button" @click="removeSaved(i)"
                                                class="px-3 py-1 rounded text-xs bg-red-100 text-red-600 hover:bg-red-200 whitespace-nowrap">
                                                Hapus
                                            </button>
                                        @else
                                            <span class="text-gray-300 text-xs">—</span>
                                        @endif
                                    </td>

                                    {{-- Hidden inputs --}}
                                    <td class="hidden">
                                        <input type="hidden" name="fitemcode[]" :value="it.fitemcode">
                                        <input type="hidden" name="fitemname[]" :value="it.fitemname">
                                        <input type="hidden" name="fsatuan[]" :value="it.fsatuan">
                                        <input type="hidden" name="frefdtno[]" :value="it.frefdtno">
                                        <input type="hidden" name="frefdtid[]" :value="it.frefdtid">
                                        <input type="hidden" name="fnoacak[]" :value="it.fnoacak">
                                        <input type="hidden" name="frefnoacak[]" :value="it.frefnoacak">
                                        <input type="hidden" name="fnouref[]" :value="it.fnouref">
                                        <input type="hidden" name="frefpr[]" :value="it.frefpr">
                                        <input type="hidden" name="fprhid[]" :value="it.fprhid">
                                        <input type="hidden" name="fqty[]" :value="it.fqty">
                                        <input type="hidden" name="fprice[]" :value="it.fprice">
                                        <input type="hidden" name="ftotal[]" :value="it.ftotal">
                                        <input type="hidden" name="fdesc[]" :value="it.fdesc">
                                        <input type="hidden" name="fketdt[]" :value="it.fketdt">
                                    </td>
                                </tr>
                            </template>

                            {{-- BARIS DRAFT — hanya tampil saat mode edit --}}
                            @if ($action !== 'delete')
                                <tr class="border-t bg-green-50 align-top">
                                    <td class="p-2 text-gray-400" x-text="savedItems.length + 1"></td>

                                    <td class="p-2">
                                        <div class="flex">
                                            <input type="text"
                                                class="flex-1 border rounded-l px-2 py-1 font-mono text-sm min-w-0"
                                                x-ref="draftCode" x-model.trim="draft.fitemcode"
                                                @input="onCodeTypedRow(draft)"
                                                @keydown.enter.prevent="handleEnterOnCode()">
                                            <button type="button" @click="openBrowseFor('draft')"
                                                class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50"
                                                title="Cari Produk">
                                                <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                            </button>

                                        </div>
                                    </td>

                                    <td class="p-2 relative overflow-visible">
                                        <input type="text"
                                            class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                            :value="draft.fitemname" disabled>
                                        <textarea x-model="draft.fdesc" rows="2"
                                            class="border rounded px-2 py-1 text-xs text-gray-600 mt-1 relative z-10" style="width: calc(100% + 8rem);"
                                            placeholder="Deskripsi (opsional)"></textarea>
                                    </td>

                                    {{-- Satuan Draft --}}
                                    <td class="p-2 align-top">
                                        <select id="draftUnitSelect" class="w-full border rounded px-2 py-1 text-sm"
                                            x-show="draft.units.length > 1"
                                            @keydown.enter.prevent="$refs.draftQty?.focus()">
                                        </select>
                                        <input type="text" x-show="draft.units.length <= 1"
                                            class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                            :value="draft.fsatuan || '-'" disabled>
                                    </td>

                                    <td class="p-2">
                                        <input type="text"
                                            class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                            :value="draft.frefdtno || ''" disabled placeholder="Ref PO">
                                    </td>

                                    <td class="p-2 text-right">
                                        <input type="number" class="border rounded px-2 py-1 w-20 text-right text-sm"
                                            min="0" step="1" x-ref="draftQty" x-model.number="draft.fqty"
                                        @input="recalc(draft)" @blur="enforcePoQtyRow(draft);"
                                        @keydown.enter.prevent="$refs.draftPrice?.focus()">
                                        <div class="text-[10px] text-amber-700 font-medium text-right mt-0.5"
                                            x-show="draft.frefdtid && calcMaxQty(draft) > 0"
                                            x-html="formatPoRemainHint(draft)">
                                        </div>
                                    </td>

                                    <td class="p-2 text-right">
                                        <input type="number" class="border rounded px-2 py-1 w-28 text-right text-sm"
                                            min="0" step="0.01" x-ref="draftPrice"
                                            x-model.number="draft.fprice" @input="recalc(draft)"
                                            @keydown.enter.prevent="$refs.draftDisc?.focus()">
                                    </td>

                                    <td class="p-2 text-right text-sm font-medium" x-text="rupiah(draft.ftotal)"></td>

                                    <td class="p-2 text-center">
                                        <button type="button" @click="addIfComplete()"
                                            class="px-3 py-1 rounded text-xs bg-emerald-600 text-white hover:bg-emerald-700 whitespace-nowrap">
                                            Tambah
                                        </button>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                {{-- Add PO + Panel Totals --}}
                <div x-data="pohFormModal()">
                    <div class="mt-3 flex justify-between items-start gap-4">
                        <div class="flex justify-start">
                            @if ($action !== 'delete')
                                <button type="button" @click="openModal()"
                                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    Add PO
                                </button>
                            @endif
                        </div>

                        {{-- Panel Totals --}}
                        <div class="w-[480px] shrink-0">
                            <div class="rounded-lg border bg-gray-50 p-3 space-y-2 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Total Harga</span>
                                    <span class="font-medium" x-text="fmtCurr(totalHarga)"></span>
                                </div>
                            </div>
                            <input type="hidden" name="famountponet" :value="totalHarga">
                        </div>
                    </div>

                    {{-- MODAL PO — hanya mode edit --}}
                    @if ($action !== 'delete')
                        <div x-show="show" x-cloak x-transition.opacity
                            class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-8" aria-modal="true"
                            role="dialog">

                            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal()"></div>

                            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-5xl flex flex-col overflow-hidden"
                                style="height: 650px;">
                                <div
                                    class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-teal-50 to-white">
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-800">Add PO</h3>
                                        <p class="text-sm text-gray-500 mt-0.5">Pilih Purchase Order yang diinginkan</p>
                                    </div>
                                    <button type="button" @click="closeModal()"
                                        class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                                        Tutup
                                    </button>
                                </div>

                                <div class="flex-1 overflow-y-auto px-6 pt-4" style="min-height: 0;">
                                    <table id="poTable" class="min-w-full text-sm display nowrap stripe hover"
                                        style="width:100%">
                                        <thead>
                                            <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                                <th
                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                    PO No</th>
                                                <th
                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                    Ref No PO</th>
                                                <th
                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                    Supplier</th>
                                                <th
                                                    class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                    Tanggal</th>
                                                <th
                                                    class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                    Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>

                                <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50"></div>
                            </div>
                        </div>

                        {{-- MODAL DUPLIKASI --}}
                        <div x-show="showDupModal" x-cloak x-transition.opacity
                            class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                            <div class="absolute inset-0 bg-black/50" @click="closeDupModal()"></div>
                            <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden">
                                <div class="px-5 py-4 border-b flex items-center gap-2 bg-amber-50">
                                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <h3 class="text-lg font-semibold text-gray-800">Item Duplikat Ditemukan</h3>
                                </div>
                                <div class="px-5 py-4 space-y-3">
                                    <p class="text-sm text-gray-700">
                                        Ditemukan <span class="font-semibold text-amber-600" x-text="dupCount"></span>
                                        item duplikat. Item duplikat <span class="font-semibold">tidak akan
                                            ditambahkan</span>.
                                    </p>
                                    <div class="rounded-lg border border-amber-200 bg-amber-50">
                                        <div class="px-3 py-2 border-b border-amber-200 text-sm font-medium text-gray-800">
                                            Preview Item Duplikat</div>
                                        <ul class="max-h-40 overflow-auto divide-y divide-amber-100">
                                            <template x-for="d in dupSample" :key="`${d.fitemcode}::${d.fitemname}`">
                                                <li
                                                    class="px-3 py-2 text-sm flex items-center gap-2 hover:bg-amber-100 transition-colors">
                                                    <span
                                                        class="inline-flex w-5 h-5 items-center justify-center rounded-full bg-amber-200 text-amber-800 text-xs font-bold">!</span>
                                                    <span class="font-mono font-medium text-gray-700"
                                                        x-text="d.fitemcode || '-'"></span>
                                                    <span class="text-gray-400">•</span>
                                                    <span class="text-gray-600 truncate"
                                                        x-text="d.fitemname || '-'"></span>
                                                </li>
                                            </template>
                                        </ul>
                                        <div x-show="dupCount > 6"
                                            class="px-3 py-2 text-xs text-center text-amber-700 border-t border-amber-200">
                                            ... dan <span x-text="dupCount - 6"></span> item lainnya
                                        </div>
                                    </div>
                                </div>
                                <div class="px-5 py-3 border-t bg-gray-50 flex items-center justify-end gap-2">
                                    <button type="button" @click="closeDupModal()"
                                        class="h-9 px-4 rounded-lg border-2 border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-100 transition-colors">
                                        Batal
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- MODAL DESC --}}
                <div x-show="showDescModal" x-cloak class="fixed inset-0 z-[95] flex items-center justify-center"
                    x-transition.opacity>
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
                                class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">
                                Batal
                            </button>
                            <button type="button" @click="applyDesc()"
                                class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
                                Simpan
                            </button>
                        </div>
                    </div>
                </div>

                <input type="hidden" id="itemsCount" :value="savedItems.length">
            </div>

            {{-- MODAL: belum ada item --}}
            <div x-show="showNoItems" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center"
                x-transition.opacity>
                <div class="absolute inset-0 bg-black/50" @click="showNoItems=false"></div>
                <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden">
                    <div class="px-5 py-4 border-b flex items-center">
                        <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-500 mr-2" />
                        <h3 class="text-lg font-semibold text-gray-800">Tidak Ada Item</h3>
                    </div>
                    <div class="px-5 py-4">
                        <p class="text-sm text-gray-700">Anda belum menambahkan item. Silakan isi baris "Detail Item"
                            terlebih dahulu.</p>
                    </div>
                    <div class="px-5 py-3 border-t flex justify-end">
                        <button type="button" @click="showNoItems=false"
                            class="h-9 px-4 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">OK</button>
                    </div>
                </div>
            </div>

            {{-- MODAL: supplier belum dipilih (hanya edit) --}}
            @if ($action !== 'delete')
                <div x-show="showNoSupplier" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center"
                    x-transition.opacity>
                    <div class="absolute inset-0 bg-black/50" @click="showNoSupplier=false"></div>
                    <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden">
                        <div class="px-5 py-4 border-b flex items-center">
                            <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-amber-500 mr-2" />
                            <h3 class="text-lg font-semibold text-gray-800">Supplier Belum Dipilih</h3>
                        </div>
                        <div class="px-5 py-4">
                            <p class="text-sm text-gray-700">Silakan pilih <strong>Supplier</strong> terlebih dahulu
                                sebelum menambahkan item.</p>
                        </div>
                        <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
                            <button type="button" @click="showNoSupplier=false"
                                class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">Tutup</button>
                            <button type="button"
                                @click="showNoSupplier=false; window.dispatchEvent(new CustomEvent('supplier-browse-open'))"
                                class="h-9 px-4 rounded-lg bg-amber-500 text-white text-sm font-medium hover:bg-amber-600">
                                Pilih Supplier
                            </button>
                        </div>
                    </div>
                </div>

                {{-- MODAL: Produk duplikat --}}
                <div x-show="showDupItemModal" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center"
                    x-transition.opacity>
                    <div class="absolute inset-0 bg-black/50" @click="showDupItemModal=false"></div>
                    <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden">
                        <div class="px-5 py-4 border-b flex items-center">
                            <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-500 mr-2" />
                            <h3 class="text-lg font-semibold text-gray-800">Produk Sudah Ada</h3>
                        </div>
                        <div class="px-5 py-4 space-y-1">
                            <p class="text-sm text-gray-700">
                                Produk <strong x-text="dupItemName"></strong>
                                <template x-if="dupItemSatuan">
                                    <span> (<span x-text="dupItemSatuan"></span>)</span>
                                </template>
                                sudah ada di daftar item.
                            </p>
                            <p class="text-sm text-gray-500">Satu produk dengan satuan yang sama hanya boleh ditambahkan
                                satu kali.</p>
                        </div>
                        <div class="px-5 py-3 border-t flex justify-end">
                            <button type="button" @click="showDupItemModal=false"
                                class="h-9 px-4 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700">OK</button>
                        </div>
                    </div>
                </div>

                {{-- MODAL SUPPLIER --}}
                <div x-data="supplierBrowser()" x-show="open" x-cloak x-transition.opacity
                    class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-6xl flex flex-col overflow-hidden"
                        style="height: 650px;">
                        <div
                            class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
                            <div>
                                <h3 class="text-xl font-bold text-gray-800">Browse Supplier</h3>
                                <p class="text-sm text-gray-500 mt-0.5">Pilih supplier yang diinginkan</p>
                            </div>
                            <button type="button" @click="close()"
                                class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 font-medium text-gray-700 text-sm">Tutup</button>
                        </div>
                        <div class="flex-1 overflow-y-auto px-6 pt-4" style="min-height: 0;">
                            <table id="supplierBrowseTable" class="min-w-full text-sm display nowrap stripe hover"
                                style="width:100%">
                                <thead>
                                    <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                        <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                            Kode</th>
                                        <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                            Nama Supplier</th>
                                        <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                            Alamat</th>
                                        <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                            Telepon</th>
                                        <th class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                            Aksi</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50"></div>
                    </div>
                </div>

                {{-- MODAL GUDANG --}}
                <div x-data="warehouseBrowser()" x-show="open" x-cloak x-transition.opacity
                    class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-6xl flex flex-col overflow-hidden"
                        style="height: 650px;">
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
                        <div class="flex-1 overflow-y-auto px-6 pt-4" style="min-height: 0;">
                            <table id="warehouseTable" class="min-w-full text-sm display nowrap stripe hover"
                                style="width:100%">
                                <thead>
                                    <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                        <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                            Gudang (Kode - Nama)</th>
                                        <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                            Branch</th>
                                        <th class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                            Aksi</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50"></div>
                    </div>
                </div>

                {{-- MODAL PRODUK --}}
                <div x-data="productBrowser()" x-show="open" x-cloak x-transition.opacity
                    class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-6xl flex flex-col overflow-hidden"
                        style="height: 650px;">
                        <div
                            class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
                            <div>
                                <h3 class="text-xl font-bold text-gray-800">Browse Produk</h3>
                                <p class="text-sm text-gray-500 mt-0.5">Pilih produk yang diinginkan</p>
                            </div>
                            <button type="button" @click="close()"
                                class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 font-medium text-gray-700 text-sm">Tutup</button>
                        </div>
                        <div class="flex-1 overflow-y-auto px-6 pt-4" style="min-height: 0;">
                            <table id="productTable" class="min-w-full text-sm display nowrap stripe hover"
                                style="width:100%">
                                <thead>
                                    <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                        <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                            Kode</th>
                                        <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                            Nama Produk</th>
                                        <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                            Satuan</th>
                                        <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                            Merek</th>
                                        <th class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                            Stock</th>
                                        <th class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                            Aksi</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50"></div>
                    </div>
                </div>
            @endif

            {{-- TOMBOL AKSI --}}
            <div class="mt-8 flex justify-center gap-4">
                @if ($action === 'delete')
                    @if ($usageLocked)
                        <button type="button" disabled title="{{ $usageLockMessage }}"
                            class="bg-red-300 text-white px-6 py-2 rounded flex items-center cursor-not-allowed opacity-70">
                            <x-heroicon-o-lock-closed class="w-5 h-5 mr-2" /> Hapus
                        </button>
                    @else
                        <button type="button" onclick="showDeleteModal()"
                            class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 flex items-center">
                            <x-heroicon-o-trash class="w-5 h-5 mr-2" /> Hapus
                        </button>
                    @endif
                    <button type="button" onclick="window.location.href='{{ route('penerimaanbarang.index') }}'"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" /> Kembali
                    </button>
                @else
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
                    <button type="button" onclick="window.location.href='{{ route('penerimaanbarang.index') }}'"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" /> Keluar
                    </button>
                @endif
            </div>
        </form>
    </div>

    {{-- MODAL KONFIRMASI HAPUS --}}
    @if ($action === 'delete')
        <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Konfirmasi Hapus Penerimaan Barang ini?</h3>
                <form action="{{ route('penerimaanbarang.destroy', $penerimaanbarang->fstockmtid) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeDeleteModal()"
                            class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Tidak</button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Ya,
                            Hapus</button>
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
        </script>
    @endif

@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
        {{-- ── Identik dengan create.blade.php ── --}}
        window.CURRENCY_MAP = window.CURRENCY_MAP || {};

        window.PRODUCT_MAP = {
            @foreach ($products as $p)
                "{{ $p->fprdcode }}": {
                    id: @json($p->fprdid),
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

        window.fetchLastPrice = async function(fprdcode, fsupplier, fsatuan) {
            if (!fprdcode || !fsupplier || !fsatuan) return null;
            try {
                const url = new URL("{{ route('tr_poh.lastPrice') }}", window.location.origin);
                url.searchParams.set('fprdcode', fprdcode);
                url.searchParams.set('fsupplier', fsupplier);
                url.searchParams.set('fsatuan', fsatuan);
                const res = await fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!res.ok) return null;
                return await res.json();
            } catch (e) {
                return null;
            }
        };

        // ─── mainForm() — sama persis dengan create, satu-satunya beda:
        //     savedItems diisi dari $savedItems (data existing dari DB)
        function mainForm() {
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
                    fnouref: '',
                    frefpr: '',
                    fprhid: '',
                    fprno: '',
                    fpono: '',
                    fqty: 0,
                    fprice: 0,
                    ftotal: 0,
                    fdesc: '',
                    fketdt: '',
                    maxqty: 0,
                    fqtypr: 0,
                    fqtypr_satuan: '',
                    fsatuankecil: '',
                    fsatuanbesar: '',
                    fsatuanbesar2: '',
                    fqtykecil: 0,
                    fqtykecil2: 0,
                    maxqty_satuan: '',
                    unit_ratios: { satuankecil: 1, satuanbesar: 1, satuanbesar2: 1 },
                    frefdtid: '',
                    fqtykecil_ref: 0,
                    fqtypo: 0,
                    fqtysisapo: 0,
                    fqtyditer: 0,
                    fqtymaxedit: 0,
                };
            }

            return {
                autoCode: true,
                selectedCurrId: '',
                selectedCurrCode: 'IDR',
                rateValue: 1,
                includePPN: false,
                ppnMode: 0,
                ppnRate: 11,
                // ── Diisi dari DB (perbedaan utama vs create) ──────────────────
                savedItems: @json($savedItems),
                draft: newRow(),
                activeRow: null,
                browseTarget: 'draft',
                showNoItems: false,
                showNoSupplier: false,
                showDupItemModal: false,
                dupItemName: '',
                dupItemSatuan: '',
                showDescModal: false,
                descValue: '',
                _descTarget: null,

                get totalHarga() {
                    return this.savedItems.reduce((s, it) => s + (it.ftotal || 0), 0);
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

                recalc(row) {
                    const qty = Math.max(0, +row.fqty || 0);
                    const price = Math.max(0, +row.fprice || 0);
                    const disc = Math.min(100, Math.max(0, +row.fdisc || 0));
                    row.fqty = qty;
                    row.fprice = price;
                    row.fdisc = disc;
                    row.ftotal = +(qty * price * (1 - disc / 100)).toFixed(2);
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

                formatPoRemainHint(row) {
                    return '';
                },

                enforcePoQtyRow(row) {
                    const n = +row.fqty;
                    if (!Number.isFinite(n)) {
                        row.fqty = 1;
                        return;
                    }
                    if (n < 0.001) row.fqty = 0.001;

                    if (!row.frefdtid) return;
                    row.maxqty = this.calcMaxQty(row);
                },

                hydrateRowFromMeta(row, meta, keepMaxqty = false) {
                    if (!meta) {
                        row.fitemname = '';
                        row.units = [];
                        row.fsatuan = '';
                        if (!keepMaxqty) row.maxqty = 0;
                        if (row === this.draft) {
                            clearDraftUnitSelect();
                        }
                        return;
                    }
                    row.fitemname = meta.name || '';
                    const units = [...new Set((meta.units || []).map(u => (u ?? '').toString().trim()).filter(Boolean))];
                    const currentSatuan = (row.fsatuan || '').trim();
                    if (currentSatuan && !units.includes(currentSatuan)) units.unshift(currentSatuan);
                    row.units = units;
                    if (!currentSatuan) row.fsatuan = units[0] || '';
                    if (meta.unit_ratios) row.unit_ratios = meta.unit_ratios;
                    if (!keepMaxqty) row.maxqty = 0;
                    
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
                    this.$nextTick(() => this.applyLastPrice(row));
                },
                onCodeTypedSaved(item) {
                    this.hydrateRowFromMeta(item, this.productMeta(item.fitemcode));
                    this.$nextTick(() => this.applyLastPrice(item));
                },

                getSupplier() {
                    return (document.getElementById('supplierCodeHidden')?.value || '').trim();
                },

                async applyLastPrice(row) {
                    const supplier = this.getSupplier();
                    const code = (row.fitemcode || '').trim();
                    const satuan = (row.fsatuan || '').trim();
                    if (!code || !supplier || !satuan) return;
                    const hist = await window.fetchLastPrice(code, supplier, satuan);
                    if (!hist) return;
                    if (!row.fprice || row.fprice === 0) {
                        row.fprice = hist.fprice;
                        row.fdisc = hist.fdisc ?? 0;
                        this.recalc(row);
                    }
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

                calcMaxQty(row) {
                    const eq = (a, b) => (a || '').trim().toLowerCase() === (b || '').trim().toLowerCase();
                    const hasEditMax = row.fqtymaxedit !== undefined && row.fqtymaxedit !== null && row.fqtymaxedit !== '';
                    if (hasEditMax) return Math.max(0, Number(row.fqtymaxedit) || 0);

                    const satuanPO = (row.fsatuan || '').trim();
                    const satKecil = (row.fsatuankecil || '').trim();
                    const satBesar = (row.fsatuanbesar || '').trim();
                    const satBesar2 = (row.fsatuanbesar2 || '').trim();
                    const rasio = Number(row.fqtykecil || 0);
                    const rasio2 = Number(row.fqtykecil2 || 0);

                    const hasRemainField = row.fqtykecil_ref !== undefined && row.fqtykecil_ref !== null && row.fqtykecil_ref !== '';
                    if (!hasRemainField) return 0;
                    const sisaKecil = Math.max(0, Number(row.fqtykecil_ref) || 0);

                    if (!satuanPO || eq(satuanPO, satKecil)) return sisaKecil;
                    if (eq(satuanPO, satBesar) && rasio > 0) return Math.floor(sisaKecil / rasio);
                    if (eq(satuanPO, satBesar2) && rasio2 > 0) return Math.floor(sisaKecil / rasio2);
                    return sisaKecil;
                },

                isDupeItem(candidate) {
                    const cPod = String(candidate.frefdtid ?? '').trim();
                    if (cPod) {
                        return this.savedItems.some(it => String(it.frefdtid ?? '').trim() === cPod);
                    }
                    const cCode = (candidate.fitemcode || '').trim().toLowerCase();
                    const cSatuan = (candidate.fsatuan || '').trim().toLowerCase();
                    const cName = (candidate.fitemname || '').trim().toLowerCase();
                    const cMeta = this.productMeta(candidate.fitemcode);
                    const cId = cMeta?.id ?? null;
                    return this.savedItems.some(it => {
                        const itCode = (it.fitemcode || '').trim().toLowerCase();
                        const itSatuan = (it.fsatuan || '').trim().toLowerCase();
                        const itName = (it.fitemname || '').trim().toLowerCase();
                        const itMeta = this.productMeta(it.fitemcode);
                        const itId = itMeta?.id ?? null;
                        if (itCode === cCode && itSatuan === cSatuan) return true;
                        if (cId && itId && cId === itId) return true;
                        if (cName && itName && cName === itName) return true;
                        return false;
                    });
                },

                openDesc(targetRow) {
                    this._descTarget = targetRow;
                    this.descValue = targetRow.fdesc || '';
                    this.showDescModal = true;
                },
                closeDesc() {
                    this.showDescModal = false;
                    this._descTarget = null;
                },
                applyDesc() {
                    if (this._descTarget) this._descTarget.fdesc = this.descValue;
                    this.closeDesc();
                },

                focusSavedUnit(item, i) {
                    if (item.units.length > 1) this.$nextTick(() => document.getElementById('unit_saved_' + i)?.focus());
                    else this.focusSavedQty(i);
                },
                focusSavedQty(i) {
                    this.$nextTick(() => document.getElementById('qty_saved_' + i)?.focus());
                },
                focusSavedPrice(i) {
                    this.$nextTick(() => document.getElementById('price_saved_' + i)?.focus());
                },
                focusSavedDisc(i) {
                    this.$nextTick(() => document.getElementById('disc_saved_' + i)?.focus());
                },
                focusDraftCode() {
                    this.$nextTick(() => this.$refs.draftCode?.focus());
                },

                addIfComplete() {
                    if (!this.getSupplier()) {
                        this.showNoSupplier = true;
                        return;
                    }
                    const r = this.draft;
                    if (!this.isComplete(r)) {
                        if (!r.fitemcode) return this.$refs.draftCode?.focus();
                        if (!r.fitemname) return this.$refs.draftCode?.focus();
                        if (!r.fsatuan) return r.units.length > 1 ? this.$refs.draftUnit?.focus() : this.$refs.draftCode
                            ?.focus();
                        if (!(Number(r.fqty) > 0)) return this.$refs.draftQty?.focus();
                        return;
                    }
                    this.recalc(r);
                    if (this.isDupeItem(r)) {
                        this.showDupItemModal = true;
                        this.dupItemName = r.fitemname || r.fitemcode;
                        this.dupItemSatuan = r.fsatuan;
                        return;
                    }
                    this.savedItems.push({
                        ...r,
                        fnoacak: this.normalizeNoAcak(r.fnoacak) || this.generateUniqueNoAcak(),
                        frefnoacak: this.normalizeNoAcak(r.frefnoacak),
                        uid: cryptoRandom()
                    });
                    this.showNoItems = false;
                    this.draft = newRow();
                    this.draft.fnoacak = this.generateUniqueNoAcak();
                    this.$nextTick(() => this.$refs.draftCode?.focus());
                },

                removeSaved(i) {
                    this.savedItems.splice(i, 1);
                },

                handleEnterOnCode() {
                    if (!this.getSupplier()) {
                        this.showNoSupplier = true;
                        return;
                    }
                    if (this.draft.units.length > 1) this.$refs.draftUnit?.focus();
                    else this.$refs.draftQty?.focus();
                },

                onPrPicked(e) {
                    const {
                        header,
                        items
                    } = e.detail || {};
                    if (!items || !Array.isArray(items)) return;
                    const skipped = [],
                        toAdd = [];
                    items.forEach(src => {
                        const fsatuan = (src.fsatuan ?? '').trim();
                        const meta = this.productMeta(src.fitemcode ?? '');
                        const fitemname = meta ? (meta.name || src.fitemname || '') : (src.fitemname ?? '');
                        const candidate = {
                            fitemcode: (src.fitemcode ?? '').trim(),
                            fitemname,
                            fsatuan,
                            frefdtid: src.frefdtid ?? '',
                        };
                        if (this.isDupeItem(candidate)) {
                            skipped.push(src);
                            return;
                        }

                        const units = meta ? [...new Set((meta.units || []).map(u => (u ?? '').toString().trim())
                                .filter(Boolean))] :
                            (Array.isArray(src.units) && src.units.length ? src.units : [fsatuan].filter(Boolean));
                        if (fsatuan && !units.includes(fsatuan)) units.unshift(fsatuan);

                        const row = {
                            uid: cryptoRandom(),
                            fitemcode: src.fitemcode ?? '',
                            fitemname,
                            units,
                            fsatuan: fsatuan || units[0] || '',
                            frefdtno: src.fpono || header?.fpono || '',
                            fnoacak: this.generateUniqueNoAcak(),
                            frefnoacak: this.normalizeNoAcak(src.frefnoacak ?? src.fnoacak ?? ''),
                            fnouref: src.fnouref ?? '',
                            frefpr: String(header?.fprhid ?? src.fprhid ?? ''),
                            fprhid: String(src.fprhid ?? header?.fprhid ?? ''),
                            fprno: String(header?.fpono ?? src.fpono ?? ''),
                            fpono: String(header?.fpono ?? src.fpono ?? ''),
                            fqty: (src.fqty !== null && src.fqty !== undefined && Number(src.fqty) > 0) ? Number(src.fqty) : 1,
                            fqtypo: 0,
                            fqtysisapo: Number(src.fqtysisapo ?? 0),
                            fqtyditer: Number(src.fqtyditer ?? 0),
                            fqtymaxedit: Number(src.fqtymaxedit ?? 0),
                            fqtykecil_ref: Number(src.fqtykecil_ref ?? src.fqtyremain ?? src.fqtykecil_sisa ?? 0),
                            frefdtid: src.frefdtid ?? '',
                            fsatuankecil: src.fsatuankecil || meta?.fsatuankecil || '',
                            fsatuanbesar: src.fsatuanbesar || meta?.fsatuanbesar || '',
                            fsatuanbesar2: src.fsatuanbesar2 || meta?.fsatuanbesar2 || '',
                            fqtykecil: Number(src.fqtykecil ?? meta?.fqtykecil ?? 0),
                            fqtykecil2: Number(src.fqtykecil2 ?? meta?.fqtykecil2 ?? 0),
                            maxqty_satuan: src.maxqty_satuan ?? '',
                            fprice: Number(src.fprice ?? 0),
                            ftotal: Number(src.ftotal ?? 0),
                            fdesc: src.fdesc ?? src.fketdt ?? '',
                            fketdt: src.fketdt ?? '',
                        };
                        row.maxqty = this.calcMaxQty(row);
                        if (Number(row.maxqty) > 0) {
                            row.fqty = Number(row.maxqty);
                        }
                        if (!row.ftotal && row.fqty && row.fprice)
                            row.ftotal = +(row.fqty * row.fprice * (1 - (row.fdisc || 0) / 100)).toFixed(2);
                        toAdd.push(row);
                        this.savedItems.push(row);
                        if (!row.fprice || row.fprice === 0)
                            this.$nextTick(() => this.applyLastPrice(row));
                    });

                    if (skipped.length > 0 && toAdd.length === 0) {
                        this.showDupItemModal = true;
                        this.dupItemName = skipped.map(s => s.fitemname || s.fitemcode).join(', ');
                        this.dupItemSatuan = '';
                    }
                },

                itemKey(it) {
                    const id = (it.frefdtid ?? '').toString().trim();
                    if (id) return `pod:${id}`;
                    return `manual:${(it.fitemcode??'').toString().trim()}::${(it.fsatuan??'').toString().trim()}`;
                },
                getCurrentItemKeys() {
                    return this.savedItems.map(it => this.itemKey(it));
                },

                openBrowseFor(where, idx = null) {
                    if (!this.getSupplier()) {
                        this.showNoSupplier = true;
                        return;
                    }
                    this.browseTarget = (where === 'saved' && idx !== null) ? idx : 'draft';
                    window.dispatchEvent(new CustomEvent('browse-open', {
                        detail: {
                            forEdit: false
                        }
                    }));
                },

                submitForm(form) {
                    if (this.savedItems.length < 1) {
                        this.showNoItems = true;
                        return;
                    }
                    form.submit();
                },

                init() {
                    this.savedItems = this.savedItems.map(it => {
                        const meta = this.productMeta(it.fitemcode);
                        const units = (it.units && it.units.length) ? it.units : (meta ? [...new Set((meta.units || []).filter(Boolean))] : []);
                        const fsatuankecil = it.fsatuankecil || meta?.fsatuankecil || '';
                        const fsatuanbesar = it.fsatuanbesar || meta?.fsatuanbesar || '';
                        const fsatuanbesar2 = it.fsatuanbesar2 || meta?.fsatuanbesar2 || '';
                        const fqtykecil = Number(it.fqtykecil ?? meta?.fqtykecil ?? 0);
                        const fqtykecil2 = Number(it.fqtykecil2 ?? meta?.fqtykecil2 ?? 0);
                        const row = {
                            ...it,
                            uid: it.uid || cryptoRandom(),
                            units,
                            fsatuankecil,
                            fsatuanbesar,
                            fsatuanbesar2,
                            fqtykecil,
                            fqtykecil2,
                            fqtysisapo: Number(it.fqtysisapo ?? 0),
                            fqtymaxedit: Number(it.fqtymaxedit ?? 0),
                            fqtykecil_ref: Number(it.fqtykecil_ref ?? it.fqtyremain ?? 0),
                            fnoacak: this.normalizeNoAcak(it.fnoacak) || this.generateUniqueNoAcak(),
                            frefnoacak: this.normalizeNoAcak(it.frefnoacak),
                        };
                        return row;
                    });

                    // ── Guard CURRENCY_MAP ──────────────────────────────────────
                    if (window.CURRENCY_MAP && typeof window.CURRENCY_MAP === 'object') {
                        const idrEntry = Object.values(window.CURRENCY_MAP).find(c => c.code === 'IDR');
                        if (idrEntry && !this.selectedCurrId) {
                            this.selectedCurrId = String(idrEntry.id);
                            this.selectedCurrCode = idrEntry.code;
                            this.rateValue = 1;
                        }
                    }

                    window.getCurrentItemKeys = () => this.getCurrentItemKeys();
                    window.isDupeItem = (candidate) => this.isDupeItem(candidate);
                    this.draft.fnoacak = this.generateUniqueNoAcak();

                    if (this._ac) this._ac.abort();
                    this._ac = new AbortController();
                    const sig = {
                        signal: this._ac.signal,
                        passive: true
                    };

                    window.addEventListener('show-no-supplier', () => {
                        this.showNoSupplier = true;
                    }, sig);
                    window.addEventListener('pr-picked', (e) => this.onPrPicked(e), sig);
                    window.addEventListener('product-chosen', (e) => {
                        const {
                            product
                        } = e.detail || {};
                        if (!product) return;
                        const apply = (row) => {
                            row.fitemcode = (product.fprdcode || '').toString();
                            this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode));
                            row.fnoacak = this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak();
                            if (!row.fqty) row.fqty = 1;
                            this.recalc(row);
                            this.$nextTick(() => this.applyLastPrice(row));
                        };
                        if (typeof this.browseTarget === 'number') {
                            const item = this.savedItems[this.browseTarget];
                            if (item) {
                                apply(item);
                                const i = this.browseTarget;
                                this.$nextTick(() => document.getElementById('qty_saved_' + i)?.focus());
                            }
                        } else {
                            apply(this.draft);
                            this.$nextTick(() => this.$refs.draftQty?.focus());
                        }
                    }, sig);

                    const self = this;
                    document.addEventListener('change', function(e) {
                        if (e.target && e.target.id === 'draftUnitSelect') {
                            self.draft.fsatuan = e.target.value;
                        }
                    });
                }
            };
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

        // ─── pohFormModal — identik create ───────────────────────────────────────
        window.pohFormModal = function() {
            return {
                show: false,
                table: null,
                showDupModal: false,
                dupCount: 0,
                dupSample: [],
                pendingHeader: null,
                pendingUniques: [],

                initDataTable() {
                    if (this.table) {
                        this.table.destroy();
                        this.table = null;
                    }
                    if (!document.getElementById('poTable')) return;

                    this.table = $('#poTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "{{ route('penerimaanbarang.pickable') }}",
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
                                data: 'fpono',
                                name: 'fpono',
                                className: 'font-mono text-sm'
                            },
                            {
                                data: 'fpono',
                                name: 'fpono',
                                className: 'font-mono text-sm'
                            },
                            {
                                data: 'fsuppliername',
                                name: 'fsuppliername',
                                className: 'text-sm',
                                render: d => d || '<span class="text-gray-400">-</span>'
                            },
                            {
                                data: 'fpodate',
                                name: 'fpodate',
                                className: 'text-sm',
                                render: d => formatDate(d)
                            },
                            {
                                data: null,
                                orderable: false,
                                searchable: false,
                                className: 'text-center',
                                width: '100px',
                                render: () =>
                                    '<button type="button" class="btn-pick px-4 py-1.5 rounded-md text-sm font-medium bg-teal-600 hover:bg-teal-700 text-white transition-colors duration-150">Pilih</button>'
                            }
                        ],
                        pageLength: 10,
                        lengthMenu: [
                            [10, 25, 50, 100],
                            [10, 25, 50, 100]
                        ],
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
                            [3, 'desc']
                        ],
                        autoWidth: false,
                        initComplete: function() {
                            const $c = $(this.api().table().container());
                            $c.find('.dt-search .dt-input, .dataTables_filter input').css({
                                width: '280px',
                                padding: '8px 12px',
                                border: '2px solid #e5e7eb',
                                borderRadius: '8px',
                                fontSize: '14px'
                            }).focus();
                            $c.find('.dt-length select, .dataTables_length select').css({
                                padding: '6px 32px 6px 10px',
                                border: '2px solid #e5e7eb',
                                borderRadius: '8px',
                                fontSize: '14px'
                            });
                        }
                    });

                    const self = this;
                    $('#poTable').off('click.pohpick').on('click.pohpick', '.btn-pick', function() {
                        const data = self.table.row($(this).closest('tr')).data();
                        if (data) self.pick(data);
                    });
                },

                openModal() {
                    this.show = true;
                    this.$nextTick(() => setTimeout(() => this.initDataTable(), 50));
                },
                closeModal() {
                    this.show = false;
                    if (this.table) {
                        this.table.destroy();
                        this.table = null;
                    }
                },

                openDupModal(header, duplicates, uniques) {
                    this.dupCount = duplicates.length;
                    this.dupSample = duplicates.slice(0, 6);
                    this.pendingHeader = header;
                    this.pendingUniques = uniques;
                    this.showDupModal = true;
                },
                closeDupModal() {
                    this.showDupModal = false;
                    this.dupCount = 0;
                    this.dupSample = [];
                    this.pendingHeader = null;
                    this.pendingUniques = [];
                },
                confirmAddUniques() {
                    window.dispatchEvent(new CustomEvent('pr-picked', {
                        detail: {
                            header: this.pendingHeader,
                            items: this.pendingUniques
                        }
                    }));
                    this.closeDupModal();
                    this.closeModal();
                },

                async pick(row) {
                    try {
                        const url = `{{ route('penerimaanbarang.items', ['id' => 'PO_ID_PLACEHOLDER']) }}`
                            .replace('PO_ID_PLACEHOLDER', row.fpohid);
                        const res = await fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        const json = await res.json();
                        const items = json.items || [];

                        const currentKeys = new Set((window.getCurrentItemKeys?.() || []).map(String));
                        const keyOf = src =>
                            `${(src.fprdcode ?? '').toString().trim()}::${(src.frefdtno ?? '').toString().trim()}`;

                        const duplicates = items.filter(src => currentKeys.has(keyOf(src)));
                        const uniques = items.filter(src => !currentKeys.has(keyOf(src)));

                        if (duplicates.length > 0) {
                            this.openDupModal(row, duplicates, uniques);
                            return;
                        }

                        window.dispatchEvent(new CustomEvent('pr-picked', {
                            detail: {
                                header: row,
                                items
                            }
                        }));
                        this.closeModal();
                    } catch (e) {
                        console.error(e);
                        alert('Gagal mengambil detail PO');
                    }
                }
            };
        };

        function formatDate(s) {
            if (!s || s === 'No Date') return '-';
            const d = new Date(s);
            if (isNaN(d)) return '-';
            const p = n => n.toString().padStart(2, '0');
            return `${d.getFullYear()}-${p(d.getMonth()+1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}`;
        }

        // ─── supplierBrowser — identik create ────────────────────────────────────
        function supplierBrowser() {
            return {
                open: false,
                dataTable: null,
                initDataTable() {
                    if (this.dataTable) {
                        this.dataTable.destroy();
                        this.dataTable = null;
                    }
                    this.dataTable = $('#supplierBrowseTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "{{ route('suppliers.browse') }}",
                            type: 'GET',
                            data: d => ({
                                draw: d.draw,
                                start: d.start,
                                length: d.length,
                                search: d.search.value,
                                order_column: d.columns[d.order[0].column].data,
                                order_dir: d.order[0].dir
                            })
                        },
                        columns: [{
                                data: 'fsuppliercode',
                                name: 'fsuppliercode',
                                className: 'font-mono text-sm',
                                width: '15%'
                            },
                            {
                                data: 'fsuppliername',
                                name: 'fsuppliername',
                                className: 'text-sm',
                                width: '25%'
                            },
                            {
                                data: 'faddress',
                                name: 'faddress',
                                className: 'text-sm',
                                defaultContent: '-',
                                orderable: false,
                                width: '30%'
                            },
                            {
                                data: 'ftelp',
                                name: 'ftelp',
                                className: 'text-sm',
                                defaultContent: '-',
                                orderable: false,
                                width: '15%'
                            },
                            {
                                data: null,
                                orderable: false,
                                searchable: false,
                                className: 'text-center',
                                width: '15%',
                                render: () =>
                                    '<button type="button" class="btn-choose px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150">Pilih</button>'
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
                            const $c = $(this.api().table().container());
                            $c.find('.dt-search .dt-input, .dataTables_filter input').css({
                                width: '280px',
                                padding: '8px 12px',
                                border: '2px solid #e5e7eb',
                                borderRadius: '8px',
                                fontSize: '14px'
                            }).focus();
                            $c.find('.dt-length select, .dataTables_length select').css({
                                padding: '6px 32px 6px 10px',
                                border: '2px solid #e5e7eb',
                                borderRadius: '8px',
                                fontSize: '14px'
                            });
                        }
                    });
                    $('#supplierBrowseTable').off('click.suppick').on('click.suppick', '.btn-choose', (e) => {
                        const data = this.dataTable.row($(e.target).closest('tr')).data();
                        if (data) this.chooseSupplier(data);
                    });
                },
                openBrowse() {
                    this.open = true;
                    this.$nextTick(() => setTimeout(() => this.initDataTable(), 50));
                },
                close() {
                    this.open = false;
                    if (this.dataTable) {
                        this.dataTable.destroy();
                        this.dataTable = null;
                    }
                },
                chooseSupplier(supplier) {
                    const sel = document.getElementById('modal_filter_supplier_id');
                    const hid = document.getElementById('supplierCodeHidden');
                    if (!sel) {
                        this.close();
                        return;
                    }
                    let opt = [...sel.options].find(o => o.value == String(supplier.fsuppliercode));
                    const label = `${supplier.fsuppliername} (${supplier.fsuppliercode})`;
                    if (!opt) {
                        opt = new Option(label, supplier.fsuppliercode, true, true);
                        sel.add(opt);
                    } else {
                        opt.text = label;
                        opt.selected = true;
                    }
                    sel.dispatchEvent(new Event('change'));
                    if (hid) hid.value = supplier.fsuppliercode;
                    this.close();
                },
                init() {
                    window.addEventListener('supplier-browse-open', () => this.openBrowse(), {
                        passive: true
                    });
                }
            };
        }

        // ─── warehouseBrowser — identik create ───────────────────────────────────
        window.warehouseBrowser = function() {
            return {
                open: false,
                table: null,
                initDataTable() {
                    if (this.table) {
                        this.table.destroy();
                        this.table = null;
                    }
                    this.table = $('#warehouseTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "{{ route('gudang.browse') }}",
                            type: 'GET',
                            data: d => ({
                                draw: d.draw,
                                start: d.start,
                                length: d.length,
                                search: d.search.value,
                                order_column: d.columns[d.order[0].column].data,
                                order_dir: d.order[0].dir
                            })
                        },
                        columns: [{
                                data: null,
                                name: 'fwhcode',
                                className: 'text-sm',
                                render: (d, t, row) =>
                                    `<span class="font-mono font-semibold">${row.fwhcode}</span> - ${row.fwhname}`
                            },
                            {
                                data: 'fbranchcode',
                                name: 'fbranchcode',
                                className: 'text-sm',
                                render: d => d || '<span class="text-gray-400">-</span>'
                            },
                            {
                                data: null,
                                orderable: false,
                                searchable: false,
                                className: 'text-center',
                                width: '100px',
                                render: () =>
                                    '<button type="button" class="btn-choose px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150">Pilih</button>'
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
                            [0, 'asc']
                        ],
                        autoWidth: false,
                        initComplete: function() {
                            const $c = $(this.api().table().container());
                            $c.find('.dt-search .dt-input, .dataTables_filter input').css({
                                width: '280px',
                                padding: '8px 12px',
                                border: '2px solid #e5e7eb',
                                borderRadius: '8px',
                                fontSize: '14px'
                            }).focus();
                            $c.find('.dt-length select, .dataTables_length select').css({
                                padding: '6px 32px 6px 10px',
                                border: '2px solid #e5e7eb',
                                borderRadius: '8px',
                                fontSize: '14px'
                            });
                        }
                    });
                    $('#warehouseTable').off('click.whpick').on('click.whpick', '.btn-choose', (e) => {
                        const data = this.table.row($(e.target).closest('tr')).data();
                        if (data) this.choose(data);
                    });
                },
                openModal() {
                    this.open = true;
                    this.$nextTick(() => setTimeout(() => this.initDataTable(), 50));
                },
                close() {
                    this.open = false;
                    if (this.table) {
                        this.table.destroy();
                        this.table = null;
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
            };
        };

        window.addEventListener('warehouse-picked', (ev) => {
            const { fwhcode } = ev.detail || {};

            const sel = document.getElementById('warehouseSelect');
            const hidFrom = document.getElementById('warehouseCodeHidden');

            if (sel) sel.value = fwhcode || '';
            if (hidFrom) hidFrom.value = fwhcode || '';
        });

        // ─── productBrowser — identik create ─────────────────────────────────────
        function productBrowser() {
            return {
                open: false,
                table: null,
                initDataTable() {
                    if (this.table) {
                        this.table.destroy();
                        this.table = null;
                    }
                    this.table = $('#productTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "{{ route('products.browse') }}",
                            type: 'GET',
                            data: d => ({
                                draw: d.draw,
                                start: d.start,
                                length: d.length,
                                search: d.search.value,
                                order_column: d.columns[d.order[0].column].data,
                                order_dir: d.order[0].dir
                            })
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
                                render: d => d || '-'
                            },
                            {
                                data: 'fmerekname',
                                name: 'fmerekname',
                                className: 'text-center text-sm',
                                render: d => d || '-'
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
                                render: () =>
                                    '<button type="button" class="btn-choose px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 text-white">Pilih</button>'
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
                            const $c = $(this.api().table().container());
                            $c.find('.dt-search .dt-input, .dataTables_filter input').css({
                                width: '280px',
                                padding: '8px 12px',
                                border: '2px solid #e5e7eb',
                                borderRadius: '8px',
                                fontSize: '14px'
                            }).focus();
                            $c.find('.dt-length select, .dataTables_length select').css({
                                padding: '6px 32px 6px 10px',
                                border: '2px solid #e5e7eb',
                                borderRadius: '8px',
                                fontSize: '14px'
                            });
                        }
                    });
                    $('#productTable').off('click.prodpick').on('click.prodpick', '.btn-choose', (e) => {
                        const data = this.table.row($(e.target).closest('tr')).data();
                        if (data) this.choose(data);
                    });
                },
                close() {
                    this.open = false;
                    if (this.table) {
                        this.table.destroy();
                        this.table = null;
                    }
                },
                choose(product) {
                    window.dispatchEvent(new CustomEvent('product-chosen', {
                        detail: {
                            product
                        }
                    }));
                    this.close();
                },
                init() {
                    window.addEventListener('browse-open', () => {
                        this.open = true;
                        this.$nextTick(() => setTimeout(() => this.initDataTable(), 50));
                    }, {
                        passive: true
                    });
                }
            };
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
