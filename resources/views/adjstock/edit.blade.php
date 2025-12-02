@extends('layouts.app')

@section('title', 'Adjsustment Stock')

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

    @php
        // Definisikan semua variabel Anda di sini
        $currentType = old('ftypebuy', $adjstock->ftypebuy);
        $currentAccount = trim((string) old('frefno', $adjstock->frefno));
        $currentAccountId = old('faccid', $adjstock->faccid);
        $currentPpnAmount = old('famountpajak', $adjstock->famountpajak ?? 0);
        $currentSubtotal = old('famount', $adjstock->famount ?? 0);
    @endphp

    <div x-data="{ open: true, adjtype: '{{ old('ftrancode', 'm') }}' }">
        <div x-data="{
            open: true,
            adjtype: '{{ old('ftrancode', 'm') }}',
        
            includePPN: false,
            ppnRate: 0,
            ppnAmount: 0,
            totalHarga: 100000,
        
            showNoItems: false,
        
            savedItems: []
        }" class="lg:col-span-5">
            <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1600px] w-full mx-auto">
                <form action="{{ route('adjstock.update', $adjstock->fstockmtid) }}" method="POST" class="mt-6"
                    @submit="onSubmit($event)">
                    @csrf
                    @method('PATCH')

                    {{-- HEADER FORM --}}
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">Cabang</label>
                            <input type="text" class="w-full border rounded px-3 py-2 bg-gray-200 cursor-not-allowed"
                                value="{{ $fcabang }}" disabled>
                            <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                        </div>
                        <div class="lg:col-span-4" x-data="{ autoCode: true }">
                            <label class="block text-sm font-medium mb-1">Transaksi#</label>
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
                            <label class="block text-sm font-medium">Adj. Type</label>

                            <select name="ftrancode" x-model="adjtype"
                                class="w-full border rounded px-3 py-2 @error('ftrancode') border-red-500 @enderror">

                                <option value="m"
                                    {{ old('ftrancode', $adjstock->ftrancode ?? 'm') === 'm' ? 'selected' : '' }}>Masuk
                                </option>
                                <option value="k"
                                    {{ old('ftrancode', $adjstock->ftrancode ?? 'k') === 'k' ? 'selected' : '' }}>Keluar
                                </option>
                            </select>

                            @error('ftrancode')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium mb-1">Account</label>
                            <div class="flex">
                                <div class="relative flex-1">
                                    <select id="accountSelect" class="w-full border rounded-l px-3 py-2" disabled>
                                        <option value=""></option>
                                        @foreach ($accounts as $account)
                                            <option value="{{ $account->faccid }}" data-id="{{ $account->faccid }}"
                                                data-branch="{{ $account->faccount }}"
                                                {{ old('frefno', $adjstock->frefno ?? '') == $account->faccid ? 'selected' : '' }}>
                                                {{ $account->faccount }} - {{ $account->faccname }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <div class="absolute inset-0 cursor-pointer" role="button" aria-label="Browse account"
                                        @click="window.dispatchEvent(new CustomEvent('account-browse-open'))"></div>
                                </div>

                                <!-- Hidden input yang akan dikirim ke server -->
                                <input type="hidden" name="frefno" id="accountIdHidden"
                                    value="{{ old('frefno', $adjstock->frefno ?? '') }}">

                                <button type="button" @click="window.dispatchEvent(new CustomEvent('account-browse-open'))"
                                    class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                                    title="Browse Account">
                                    <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                </button>

                                <a href="{{ route('account.create') }}" target="_blank" rel="noopener"
                                    class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50"
                                    title="Tambah Account">
                                    <x-heroicon-o-plus class="w-5 h-5" />
                                </a>
                            </div>

                            @error('frefno')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium mb-1">Gudang</label>
                            <div class="flex">
                                <div class="relative flex-1">
                                    <select id="warehouseSelect"
                                        class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
                                        disabled>
                                        <option value=""></option>
                                        @foreach ($warehouses as $wh)
                                            <option value="{{ $wh->fwhid }}" data-id="{{ $wh->fwhid }}"
                                                data-branch="{{ $wh->fbranchcode }}"
                                                {{ old('ffrom', $adjstock->ffrom) == $wh->fwhid ? 'selected' : '' }}>
                                                {{ $wh->fwhcode }} - {{ $wh->fwhname }}
                                            </option>
                                        @endforeach
                                    </select>

                                    {{-- Overlay untuk buka browser gudang --}}
                                    <div class="absolute inset-0" role="button" aria-label="Browse warehouse"
                                        @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open'))"></div>
                                </div>

                                <input type="hidden" name="ffrom" id="warehouseIdHidden"
                                    value="{{ old('ffrom', $adjstock->ffrom) }}">

                                <button type="button"
                                    @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open'))"
                                    class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                                    title="Browse Gudang">
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
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">Tanggal</label>
                            <input type="date" name="fstockmtdate"
                                value="{{ old('fstockmtdate', $adjstock->fstockmtdate->format('Y-m-d')) }}"
                                class="w-full border rounded px-3 py-2 @error('fstockmtdate') border-red-500 @enderror">
                            @error('fstockmtdate')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="lg:col-span-12">
                            <label class="block text-sm font-medium">Keterangan</label>
                            <textarea name="fket" rows="3"
                                class="w-full border rounded px-3 py-2 @error('fket') border-red-500 @enderror"
                                placeholder="Tulis keterangan tambahan di sini...">{{ old('fket', $adjstock->fket) }}</textarea>
                            @error('fket')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <template x-if="adjtype === 'm'">
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
                                            <th class="p-2 text-left w-24">Sat</th>
                                            <th class="p-2 text-right w-36">Qty Masuk</th>
                                            <th class="p-2 text-right w-32">@ Harga</th>
                                            <th class="p-2 text-right w-36">Total Harga</th>
                                            <th class="p-2 text-center w-36">Aksi</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <template x-for="(it, i) in savedItems" :key="it.uid">
                                            <!-- ROW UTAMA -->
                                            <tr class="border-t align-top">
                                                <td class="p-2" x-text="i + 1"></td>
                                                <td class="p-2 font-mono" x-text="it.fitemcode"></td>
                                                <td class="p-2 text-gray-800" x-text="it.fitemname"></td>
                                                <td class="p-2 text-left" x-text="it.fsatuan"></td>
                                                <td class="p-2 text-right" x-text="fmt(it.fqty)"></td>
                                                <td class="p-2 text-right" x-text="fmt(it.fprice)"></td>
                                                <td class="p-2 text-right" x-text="fmt(it.ftotal)"></td>
                                                <td class="p-2 text-center">
                                                    <div class="flex items-center justify-center gap-2 flex-wrap">
                                                        <button type="button" @click="edit(i)"
                                                            class="px-3 py-1 rounded text-xs bg-amber-100 text-amber-700 hover:bg-amber-200">Edit</button>
                                                        <button type="button" @click="removeSaved(i)"
                                                            class="px-3 py-1 rounded text-xs bg-red-100 text-red-600 hover:bg-red-200">Hapus</button>
                                                    </div>
                                                </td>

                                                <!-- hidden inputs -->
                                                <td class="hidden">
                                                    <input type="hidden" name="fitemcode[]" :value="it.fitemcode">
                                                    <input type="hidden" name="fitemname[]" :value="it.fitemname">
                                                    <input type="hidden" name="fsatuan[]" :value="it.fsatuan">
                                                    <input type="hidden" name="frefdtno[]" :value="it.frefdtno">
                                                    <input type="hidden" name="frefpr[]" :value="it.frefpr">
                                                    <input type="hidden" name="fqty[]" :value="it.fqty">
                                                    <input type="hidden" name="fprice[]" :value="it.fprice">
                                                    <input type="hidden" name="ftotal[]" :value="it.ftotal">
                                                    <input type="hidden" name="fketdt[]" :value="it.fketdt">
                                                </td>
                                            </tr>
                                        </template>

                                        <!-- ROW EDIT UTAMA -->
                                        <tr x-show="editingIndex !== null" class="border-t align-top" x-cloak>
                                            <!-- # -->
                                            <td class="p-2" x-text="(editingIndex ?? 0) + 1"></td>

                                            <!-- Kode Produk -->
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
                                                    <a href="{{ route('product.create') }}" target="_blank"
                                                        rel="noopener"
                                                        class="border border-l-0 rounded-r px-2 py-1 bg-white hover:bg-gray-50"
                                                        title="Tambah Produk">
                                                        <x-heroicon-o-plus class="w-4 h-4" />
                                                    </a>
                                                </div>
                                            </td>

                                            <!-- Nama Produk (readonly) -->
                                            <td class="p-2">
                                                <input type="text"
                                                    class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                    :value="editRow.fitemname" disabled>
                                            </td>

                                            <!-- Satuan -->
                                            <td class="p-2">
                                                <template x-if="editRow.units.length > 1">
                                                    <select class="w-full border rounded px-2 py-1" x-ref="editUnit"
                                                        x-model="editRow.fsatuan"
                                                        @keydown.enter.prevent="$refs.editRefPr?.focus()">
                                                        <template x-for="u in editRow.units" :key="u">
                                                            <option :value="u" x-text="u"></option>
                                                        </template>
                                                    </select>
                                                </template>
                                                <template x-if="editRow.units.length <= 1">
                                                    <input type="text"
                                                        class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                        :value="editRow.fsatuan || '-'" disabled>
                                                </template>
                                            </td>

                                            <!-- Qty -->
                                            <td class="p-2 text-right">
                                                <input type="number" class="border rounded px-2 py-1 w-24 text-right"
                                                    min="0" step="1" x-ref="editQty"
                                                    x-model.number="editRow.fqty" @change="recalc(editRow)"
                                                    @blur="recalc(editRow)"
                                                    @keydown.enter.prevent="$refs.editPrice?.focus()">
                                            </td>

                                            <!-- @ Harga -->
                                            <td class="p-2 text-right">
                                                <input type="number" class="border rounded px-2 py-1 w-28 text-right"
                                                    min="0" step="0.01" x-ref="editPrice"
                                                    x-model.number="editRow.fprice" @change="recalc(editRow)"
                                                    @blur="recalc(editRow)"
                                                    @keydown.enter.prevent="handleEnterOnPrice('edit')">
                                            </td>

                                            <!-- Total Harga (readonly) -->
                                            <td class="p-2 text-right font-semibold" x-text="rupiah(editRow.ftotal)"></td>

                                            <!-- Aksi -->
                                            <td class="p-2 text-center">
                                                <div class="flex items-center justify-center gap-2 flex-wrap">
                                                    <button type="button" @click="applyEdit()"
                                                        class="px-3 py-1 rounded text-xs bg-emerald-600 text-white">Simpan</button>
                                                    <button type="button" @click="cancelEdit()"
                                                        class="px-3 py-1 rounded text-xs bg-gray-100">Batal</button>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- ROW DRAFT UTAMA -->
                                        <tr class="border-t align-top">
                                            <!-- # -->
                                            <td class="p-2" x-text="savedItems.length + 1"></td>

                                            <!-- Kode Produk -->
                                            <td class="p-2">
                                                <div class="flex">
                                                    <input type="text"
                                                        class="flex-1 border rounded-l px-2 py-1 font-mono"
                                                        x-ref="draftCode" x-model.trim="draft.fitemcode"
                                                        @input="onCodeTypedRow(draft)"
                                                        @keydown.enter.prevent="handleEnterOnCode('draft')">
                                                    <button type="button" @click="openBrowseFor('draft')"
                                                        class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50"
                                                        title="Cari Produk">
                                                        <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                    </button>
                                                    <a href="{{ route('product.create') }}" target="_blank"
                                                        rel="noopener"
                                                        class="border border-l-0 rounded-r px-2 py-1 bg-white hover:bg-gray-50"
                                                        title="Tambah Produk">
                                                        <x-heroicon-o-plus class="w-4 h-4" />
                                                    </a>
                                                </div>
                                            </td>

                                            <!-- Nama Produk (readonly) -->
                                            <td class="p-2">
                                                <input type="text"
                                                    class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                    :value="draft.fitemname" disabled>
                                            </td>

                                            <!-- Satuan -->
                                            <td class="p-2">
                                                <template x-if="draft.units.length > 1">
                                                    <select class="w-full border rounded px-2 py-1" x-ref="draftUnit"
                                                        x-model="draft.fsatuan"
                                                        @keydown.enter.prevent="$refs.draftRefPr?.focus()">
                                                        <template x-for="u in draft.units" :key="u">
                                                            <option :value="u" x-text="u"></option>
                                                        </template>
                                                    </select>
                                                </template>
                                                <template x-if="draft.units.length <= 1">
                                                    <input type="text"
                                                        class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                        :value="draft.fsatuan || '-'" disabled>
                                                </template>
                                            </td>

                                            <!-- Qty -->
                                            <td class="p-2 text-right">
                                                <input type="number" class="border rounded px-2 py-1 w-24 text-right"
                                                    min="0" step="1" x-ref="draftQty"
                                                    x-model.number="draft.fqty" @change="recalc(draft)"
                                                    @blur="recalc(draft)"
                                                    @keydown.enter.prevent="$refs.draftPrice?.focus()">
                                            </td>

                                            <!-- @ Harga -->
                                            <td class="p-2 text-right">
                                                <input type="number" class="border rounded px-2 py-1 w-28 text-right"
                                                    min="0" step="0.01" x-ref="draftPrice"
                                                    x-model.number="draft.fprice" @change="recalc(draft)"
                                                    @blur="recalc(draft)"
                                                    @keydown.enter.prevent="handleEnterOnPrice('draft')">
                                            </td>

                                            <!-- Total Harga (readonly) -->
                                            <td class="p-2 text-right font-semibold" x-text="rupiah(draft.ftotal)"></td>

                                            <!-- Aksi -->
                                            <td class="p-2 text-center">
                                                <div class="flex items-center justify-center gap-2 flex-wrap">
                                                    <button type="button" @click="addIfComplete()"
                                                        class="px-3 py-1 rounded text-xs bg-emerald-600 text-white">Tambah</button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="w-1/2 ml-auto">
                                <div class="rounded-lg border bg-gray-50 p-3 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-700">Total Harga</span>
                                        <span class="min-w-[140px] text-right font-medium"
                                            x-text="rupiah(totalHarga)"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="adjtype === 'k'">
                        <div x-data="itemsTableKeluar()" x-init="init()" class="mt-6 space-y-2">

                            {{-- DETAIL ITEM (tabel input) --}}
                            <h3 class="text-base font-semibold text-gray-800">Detail Item Keluar</h3>

                            <div class="overflow-auto border rounded">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="p-2 text-left w-10">#</th>
                                            <th class="p-2 text-left w-40">Kode Produk</th>
                                            <th class="p-2 text-left w-102">Nama Produk</th>
                                            <th class="p-2 text-left w-24">Sat</th>
                                            <th class="p-2 text-right w-36">Qty Keluar</th>
                                            <th class="p-2 text-center w-36">Aksi</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <template x-for="(it, i) in savedItems" :key="it.uid">
                                            <!-- ROW UTAMA -->
                                            <tr class="border-t align-top">
                                                <td class="p-2" x-text="i + 1"></td>
                                                <td class="p-2 font-mono" x-text="it.fitemcode"></td>
                                                <td class="p-2 text-gray-800" x-text="it.fitemname"></td>
                                                <td class="p-2 text-left" x-text="it.fsatuan"></td>
                                                <td class="p-2 text-right" x-text="fmt(it.fqty)"></td>
                                                <td class="p-2 text-center">
                                                    <div class="flex items-center justify-center gap-2 flex-wrap">
                                                        <button type="button" @click="edit(i)"
                                                            class="px-3 py-1 rounded text-xs bg-amber-100 text-amber-700 hover:bg-amber-200">Edit</button>
                                                        <button type="button" @click="removeSaved(i)"
                                                            class="px-3 py-1 rounded text-xs bg-red-100 text-red-600 hover:bg-red-200">Hapus</button>
                                                    </div>
                                                </td>

                                                <!-- hidden inputs -->
                                                <td class="hidden">
                                                    <input type="hidden" name="fitemcode[]" :value="it.fitemcode">
                                                    <input type="hidden" name="fitemname[]" :value="it.fitemname">
                                                    <input type="hidden" name="fsatuan[]" :value="it.fsatuan">
                                                    <input type="hidden" name="frefdtno[]" :value="it.frefdtno">
                                                    <input type="hidden" name="frefpr[]" :value="it.frefpr">
                                                    <input type="hidden" name="fqty[]" :value="it.fqty">
                                                    <input type="hidden" name="fprice[]" :value="it.fprice">
                                                    <input type="hidden" name="ftotal[]" :value="it.ftotal">
                                                    <input type="hidden" name="fketdt[]" :value="it.fketdt">
                                                </td>
                                            </tr>
                                        </template>

                                        <!-- ROW EDIT UTAMA -->
                                        <tr x-show="editingIndex !== null" class="border-t align-top" x-cloak>
                                            <!-- # -->
                                            <td class="p-2" x-text="(editingIndex ?? 0) + 1"></td>

                                            <!-- Kode Produk -->
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
                                                    <a href="{{ route('product.create') }}" target="_blank"
                                                        rel="noopener"
                                                        class="border border-l-0 rounded-r px-2 py-1 bg-white hover:bg-gray-50"
                                                        title="Tambah Produk">
                                                        <x-heroicon-o-plus class="w-4 h-4" />
                                                    </a>
                                                </div>
                                            </td>

                                            <!-- Nama Produk (readonly) -->
                                            <td class="p-2">
                                                <input type="text"
                                                    class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                    :value="editRow.fitemname" disabled>
                                            </td>

                                            <!-- Satuan -->
                                            <td class="p-2">
                                                <template x-if="editRow.units.length > 1">
                                                    <select class="w-full border rounded px-2 py-1" x-ref="editUnit"
                                                        x-model="editRow.fsatuan"
                                                        @keydown.enter.prevent="$refs.editRefPr?.focus()">
                                                        <template x-for="u in editRow.units" :key="u">
                                                            <option :value="u" x-text="u">
                                                            </option>
                                                        </template>
                                                    </select>
                                                </template>
                                                <template x-if="editRow.units.length <= 1">
                                                    <input type="text"
                                                        class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                        :value="editRow.fsatuan || '-'" disabled>
                                                </template>
                                            </td>

                                            <!-- Qty -->
                                            <td class="p-2 text-right">
                                                <input type="number" class="border rounded px-2 py-1 w-24 text-right"
                                                    min="0" step="1" x-ref="editQty"
                                                    x-model.number="editRow.fqty" @change="recalc(editRow)"
                                                    @blur="recalc(editRow)"
                                                    @keydown.enter.prevent="$refs.editPrice?.focus()">
                                            </td>

                                            <!-- Aksi -->
                                            <td class="p-2 text-center">
                                                <div class="flex items-center justify-center gap-2 flex-wrap">
                                                    <button type="button" @click="applyEdit()"
                                                        class="px-3 py-1 rounded text-xs bg-emerald-600 text-white">Simpan</button>
                                                    <button type="button" @click="cancelEdit()"
                                                        class="px-3 py-1 rounded text-xs bg-gray-100">Batal</button>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- ROW DRAFT UTAMA -->
                                        <tr class="border-t align-top">
                                            <!-- # -->
                                            <td class="p-2" x-text="savedItems.length + 1"></td>

                                            <!-- Kode Produk -->
                                            <td class="p-2">
                                                <div class="flex">
                                                    <input type="text"
                                                        class="flex-1 border rounded-l px-2 py-1 font-mono"
                                                        x-ref="draftCode" x-model.trim="draft.fitemcode"
                                                        @input="onCodeTypedRow(draft)"
                                                        @keydown.enter.prevent="handleEnterOnCode('draft')">
                                                    <button type="button" @click="openBrowseFor('draft')"
                                                        class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50"
                                                        title="Cari Produk">
                                                        <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                    </button>
                                                    <a href="{{ route('product.create') }}" target="_blank"
                                                        rel="noopener"
                                                        class="border border-l-0 rounded-r px-2 py-1 bg-white hover:bg-gray-50"
                                                        title="Tambah Produk">
                                                        <x-heroicon-o-plus class="w-4 h-4" />
                                                    </a>
                                                </div>
                                            </td>

                                            <!-- Nama Produk (readonly) -->
                                            <td class="p-2">
                                                <input type="text"
                                                    class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                    :value="draft.fitemname" disabled>
                                            </td>

                                            <!-- Satuan -->
                                            <td class="p-2">
                                                <template x-if="draft.units.length > 1">
                                                    <select class="w-full border rounded px-2 py-1" x-ref="draftUnit"
                                                        x-model="draft.fsatuan"
                                                        @keydown.enter.prevent="$refs.draftRefPr?.focus()">
                                                        <template x-for="u in draft.units" :key="u">
                                                            <option :value="u" x-text="u">
                                                            </option>
                                                        </template>
                                                    </select>
                                                </template>
                                                <template x-if="draft.units.length <= 1">
                                                    <input type="text"
                                                        class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                        :value="draft.fsatuan || '-'" disabled>
                                                </template>
                                            </td>

                                            <!-- Qty -->
                                            <td class="p-2 text-right">
                                                <input type="number" class="border rounded px-2 py-1 w-24 text-right"
                                                    min="0" step="1" x-ref="draftQty"
                                                    x-model.number="draft.fqty" @change="recalc(draft)"
                                                    @blur="recalc(draft)"
                                                    @keydown.enter.prevent="$refs.draftPrice?.focus()">
                                            </td>

                                            <!-- Aksi -->
                                            <td class="p-2 text-center">
                                                <div class="flex items-center justify-center gap-2 flex-wrap">
                                                    <button type="button" @click="addIfComplete()"
                                                        class="px-3 py-1 rounded text-xs bg-emerald-600 text-white">Tambah</button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>

                    <!-- ===== Trigger: Add tr_prh dari panel kanan ===== -->
                    <div x-data="prhFormModal()" class="mt-3">
                        <div class="mt-3 flex justify-between items-start gap-4">
                            <div class="w-full flex justify-start mb-3">
                                {{-- <button type="button" @click="openModal()"
                                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    Add PO
                                </button> --}}
                            </div>
                            <!-- Modal backdrop -->
                            <div x-show="show" x-transition.opacity class="fixed inset-0 z-40 bg-black/50"
                                @keydown.escape.window="closeModal()"></div>

                            <!-- Modal panel PR-->
                            <div x-show="show" x-transition
                                class="fixed inset-0 z-50 flex items-start justify-center p-4 md:p-8" aria-modal="true"
                                role="dialog">
                                <div class="w-full max-w-3xl rounded-xl bg-white shadow-xl">
                                    <div class="flex items-center justify-between border-b px-4 py-3">
                                        <h3 class="text-lg font-semibold">Add PO</h3>
                                    </div>

                                    <div class="px-4 py-3 space-y-3">
                                        <!-- Search -->
                                        <div class="flex items-center gap-2">
                                            <input type="text" x-model.debounce.400ms="search" @input="goToPage(1)"
                                                class="w-full rounded-lg border px-3 py-2"
                                                placeholder="Cari fpono / fsupplier / tanggal...">
                                            <div class="relative">
                                                <select x-model.number="perPage" @change="goToPage(1)"
                                                    class="h-10 w-24 rounded-lg border border-gray-300 bg-white pl-3 pr-8 text-sm
                                                    focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500
                                                    hover:border-gray-400
                                                    appearance-none [background:none]">
                                                    <option value="10">10</option>
                                                    <option value="25">25</option>
                                                    <option value="50">50</option>
                                                </select>

                                                <!-- Chevron custom -->
                                                <svg class="pointer-events-none absolute right-2 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500"
                                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                                    fill="currentColor">
                                                    <path fill-rule="evenodd"
                                                        d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </div>

                                        </div>

                                        <!-- Table -->
                                        <div class="overflow-auto border rounded">
                                            <table class="min-w-full text-sm">
                                                <thead class="bg-gray-100">
                                                    <tr>
                                                        <th class="p-2 text-left w-48">PO No</th>
                                                        <th class="p-2 text-left w-48">Ref No PO</th>
                                                        <th class="p-2 text-left w-48">Supplier</th>
                                                        <th class="p-2 text-left w-48">Tanggal</th>
                                                        <th class="p-2 text-right w-24">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template x-for="row in rows" :key="row.fprid">
                                                        <tr class="border-t">
                                                            <td class="p-2" x-text="row.fprno"></td>
                                                            <td class="p-2" x-text="row.fprno"></td>
                                                            <td class="p-2" x-text="row.fsupplier || '-'"></td>
                                                            <td class="p-2" x-text="formatDate(row.fprdate)">
                                                            </td>
                                                            <td class="p-2 text-right">
                                                                <button @click.prevent="pick(row)"
                                                                    class="inline-flex items-center gap-1 rounded bg-emerald-600 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-emerald-700">
                                                                    Pilih
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    </template>

                                                    <tr x-show="loading">
                                                        <td colspan="4" class="p-4 text-center text-gray-500">
                                                            Loading...
                                                        </td>
                                                    </tr>
                                                    <tr x-show="!loading && rows.length === 0">
                                                        <td colspan="4" class="p-4 text-center text-gray-500">
                                                            Tidak ada
                                                            data</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Pagination -->
                                        <div class="flex items-center justify-between">
                                            <div class="text-sm text-gray-600">
                                                <span x-text="`Page ${currentPage} / ${lastPage}`"></span>
                                                <span x-text="` • Total: ${total}`"></span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <button @click="goToPage(1)" :disabled="currentPage <= 1"
                                                    class="rounded border px-2 py-1 disabled:opacity-50">«
                                                    First</button>
                                                <button @click="goToPage(currentPage-1)" :disabled="currentPage <= 1"
                                                    class="rounded border px-2 py-1 disabled:opacity-50">‹
                                                    Prev</button>
                                                <button @click="goToPage(currentPage+1)"
                                                    :disabled="currentPage >= lastPage"
                                                    class="rounded border px-2 py-1 disabled:opacity-50">Next
                                                    ›</button>
                                                <button @click="goToPage(lastPage)" :disabled="currentPage >= lastPage"
                                                    class="rounded border px-2 py-1 disabled:opacity-50">Last
                                                    »</button>
                                            </div>
                                        </div>
                                        <div class="flex justify-end gap-2 border-t pt-3">
                                            <button type="button" @click="closeModal()"
                                                class="rounded bg-gray-200 px-4 py-2 text-sm font-medium hover:bg-gray-300">
                                                Kembali
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Duplicate Items (Simple) -->
                            <div x-show="showDupModal" x-cloak
                                class="fixed inset-0 z-[95] flex items-center justify-center" x-transition.opacity>
                                <!-- Backdrop -->
                                <div class="absolute inset-0 bg-black/50" @click="closeDupModal()"></div>

                                <!-- Panel -->
                                <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden"
                                    x-transition.scale>
                                    <!-- Header -->
                                    <div class="px-5 py-4 border-b flex items-center gap-2">
                                        <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-amber-500" />
                                        <h3 class="text-lg font-semibold text-gray-800">Item Duplikat</h3>
                                    </div>

                                    <!-- Body -->
                                    <div class="px-5 py-4 space-y-3">
                                        <p class="text-sm text-gray-700">
                                            Ada <span class="font-semibold" x-text="dupCount"></span> item
                                            duplikat.
                                            Duplikat <span class="font-semibold">tidak</span> akan ditambahkan.
                                        </p>

                                        <!-- Simple preview list -->
                                        <div class="rounded-xl border">
                                            <div class="px-3 py-2 border-b text-sm font-medium text-gray-800">
                                                Duplikat PR
                                            </div>
                                            <ul class="max-h-40 overflow-auto divide-y">
                                                <template x-for="d in dupSample" :key="`${d.fitemcode}::${d.fitemname}`">
                                                    <li class="px-3 py-2 text-sm flex items-center gap-2">
                                                        <span
                                                            class="inline-flex w-5 h-5 items-center justify-center rounded-full bg-amber-100 text-amber-700 text-xs">!</span>
                                                        <span class="font-medium" x-text="d.fitemcode || '-'"></span>
                                                        <span class="text-gray-500">/</span>
                                                        <span class="text-gray-600" x-text="d.fitemname || '-'"></span>
                                                    </li>
                                                </template>
                                                <template x-if="dupCount === 0">
                                                    <li class="px-3 py-2 text-sm text-gray-500">Tidak ada contoh.
                                                    </li>
                                                </template>
                                            </ul>
                                        </div>
                                    </div>

                                    <!-- Footer -->
                                    <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
                                        <button type="button" @click="closeDupModal()"
                                            class="h-9 px-4 rounded-lg border text-gray-700 text-sm font-medium hover:bg-gray-50">
                                            Batal
                                        </button>
                                    </div>
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
                                    class="h-9 px-4 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                                    OK
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- MODAL GUDANG dengan DataTables --}}
                    <div x-data="warehouseBrowser()" x-show="open" x-cloak x-transition.opacity
                        class="fixed inset-0 z-50 flex items-center justify-center">
                        <div class="absolute inset-0 bg-black/40" @click="close()"></div>

                        <div class="relative bg-white rounded-2xl shadow-xl w-[92vw] max-w-4xl max-h-[85vh] flex flex-col">
                            <div class="p-4 border-b flex items-center gap-3">
                                <h3 class="text-lg font-semibold">Browse Gudang</h3>
                                <button type="button" @click="close()"
                                    class="ml-auto px-3 py-1 rounded border bg-gray-100 hover:bg-gray-200">
                                    Close
                                </button>
                            </div>

                            <div class="p-4 overflow-auto flex-1">
                                <table id="warehouseTable" class="min-w-full text-sm display nowrap" style="width:100%">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="text-left p-2">Gudang (Kode - Nama)</th>
                                            <th class="text-left p-2">Branch</th>
                                            <th class="text-center p-2">Aksi</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- MODAL PRODUK --}}
                    <div x-data="productBrowser()" x-show="open" x-cloak x-transition.opacity
                        class="fixed inset-0 z-50 flex items-center justify-center">
                        <div class="absolute inset-0 bg-black/40" @click="close()"></div>
                        <div
                            class="relative bg-white rounded-2xl shadow-xl w-[90vw] **max-w-7xl** max-h-[90vh] flex flex-col">
                            <div class="p-4 border-b flex items-center gap-3">
                                <h3 class="text-lg font-semibold">Browse Produk</h3>
                                <button type="button" @click="close()"
                                    class="ml-auto px-3 py-1 rounded border bg-gray-100 hover:bg-gray-200">
                                    Close
                                </button>
                            </div>
                            <div class="p-4 overflow-auto flex-1">
                                <table id="productTable" class="min-w-full text-sm display nowrap" style="width:100%">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="text-left p-2">Kode</th>
                                            <th class="text-left p-2">Nama</th>
                                            <th class="text-left p-2">Satuan</th>
                                            <th class="text-center p-2">Stock</th>
                                            <th class="text-center p-2">Aksi</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- MODAL ACCOUNT dengan DataTables --}}
                    <div x-data="accountBrowser()" x-show="open" x-cloak x-transition.opacity
                        class="fixed inset-0 z-50 flex items-center justify-center">
                        <div class="absolute inset-0 bg-black/40" @click="close()"></div>

                        <div class="relative bg-white rounded-2xl shadow-xl w-[92vw] max-w-4xl max-h-[85vh] flex flex-col">
                            <div class="p-4 border-b flex items-center gap-3">
                                <h3 class="text-lg font-semibold">Browse Account</h3>
                                <button type="button" @click="close()"
                                    class="ml-auto px-3 py-1 rounded border bg-gray-100 hover:bg-gray-200">
                                    Close
                                </button>
                            </div>

                            <div class="p-4 overflow-auto flex-1">
                                <table id="accountTable" class="min-w-full text-sm display nowrap" style="width:100%">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="text-left p-2">Account (Kode - Nama)</th>
                                            <th class="text-center p-2">Aksi</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 border-t flex items-center gap-2">
                        <div class="text-sm text-gray-600">
                            <span x-text="`Page ${page} / ${lastPage} • Total ${total}`"></span>
                        </div>
                        <div class="ml-auto flex items-center gap-2">
                            <button type="button" @click="prev()" :disabled="page <= 1"
                                class="px-3 py-1 rounded border"
                                :class="page <= 1 ? 'bg-gray-200 text-gray-400 cursor-not-allowed' :
                                    'bg-gray-100 hover:bg-gray-200'">Prev</button>
                            <button type="button" @click="next()" :disabled="page >= lastPage"
                                class="px-3 py-1 rounded border"
                                :class="page >= lastPage ? 'bg-gray-200 text-gray-400 cursor-not-allowed' :
                                    'bg-gray-100 hover:bg-gray-200'">Next</button>
                            <button type="button" @click="close()"
                                class="px-3 py-1 rounded border bg-gray-100 hover:bg-gray-200">Close</button>
                        </div>
                    </div>
            </div>
        </div>

        <div class="mt-8 flex justify-center gap-4">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                <x-heroicon-o-check class="w-5 h-5 mr-2" /> Simpan
            </button>
            <button type="button" @click="window.location.href='{{ route('adjstock.index') }}'"
                class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" /> Keluar
            </button>
        </div>
        </form>
    </div>
@endsection
@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <style>
        /* Targeting lebih spesifik untuk length select */
        div#warehouseTable_length select,
        .dataTables_wrapper #warehouseTable_length select,
        table#supplierBrowseTable+.dataTables_wrapper .dataTables_length select {
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
        div#accountTable_length select,
        .dataTables_wrapper #accountTable_length select,
        table#supplierBrowseTable+.dataTables_wrapper .dataTables_length select {
            min-width: 140px !important;
            width: auto !important;
            padding: 8px 45px 8px 16px !important;
            font-size: 14px !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
        }

        /* Wrapper length */
        div#accountTable_length,
        .dataTables_wrapper #accountTable_length,
        .dataTables_wrapper .dataTables_length {
            min-width: 250px !important;
        }

        /* Label wrapper */
        div#accountTable_length label,
        .dataTables_wrapper #accountTable_length label,
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
                    row.fqty = Math.max(0, Number(row.fqty) || 0);
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

            onCodeTypedRow(row) {
                this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode));
            },

            isComplete(row) {
                return row.fitemcode && row.fitemname && row.fsatuan && Number(row.fqty) > 0;
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
                this.$nextTick(() => this.$refs.draftCode?.focus());
            },

            addManyFromPR(header, items) {
                const existing = new Set(this.getCurrentItemKeys());

                let added = 0,
                    duplicates = [];

                items.forEach(src => {
                    const row = {
                        uid: cryptoRandom(),
                        fitemcode: src.fitemcode ?? '',
                        fitemname: src.fitemname ?? '',
                        fsatuan: src.fsatuan ?? '',
                        frefpr: src.frefpr ?? (header?.fpono ?? ''),
                        fqty: Number(src.fqty ?? 0),
                        fprice: Number(src.fprice ?? 0),
                        ftotal: Number(src.ftotal ?? 0),
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

                    this.savedItems.push(row);
                    existing.add(key);
                    added++;
                });

                this.recalcTotals();
            },

            addIfComplete() {
                const r = this.draft;
                if (!this.isComplete(r)) {
                    if (!r.fitemcode) return this.$refs.draftCode?.focus();
                    if (!r.fitemname) return this.$refs.draftCode?.focus();
                    if (!r.fsatuan) return (r.units.length > 1 ? this.$refs.draftUnit?.focus() : this.$refs.draftCode
                        ?.focus());
                    if (!(Number(r.fqty) > 0)) return this.$refs.draftQty?.focus();
                    return;
                }

                this.recalc(r);

                const dupe = this.savedItems.find(it =>
                    it.fitemcode === r.fitemcode &&
                    it.fsatuan === r.fsatuan &&
                    (it.frefpr || '') === (r.frefpr || '')
                );

                if (dupe) {
                    alert('Item sama sudah ada.');
                    return;
                }

                this.savedItems.push({
                    ...r,
                    uid: cryptoRandom()
                });

                this.showNoItems = false;
                this.resetDraft();
                this.$nextTick(() => this.$refs.draftCode?.focus());
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
                    ...r
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
                        this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode));
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
                frefpr: '',
                fqty: 0,
                fprice: 0,
                ftotal: 0,
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

    function itemsTableKeluar() {
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
                    row.fqty = Math.max(0, Number(row.fqty) || 0);
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

            onCodeTypedRow(row) {
                this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode));
            },

            isComplete(row) {
                return row.fitemcode && row.fitemname && row.fsatuan && Number(row.fqty) > 0;
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
                this.$nextTick(() => this.$refs.draftCode?.focus());
            },

            addManyFromPR(header, items) {
                const existing = new Set(this.getCurrentItemKeys());

                let added = 0,
                    duplicates = [];

                items.forEach(src => {
                    const row = {
                        uid: cryptoRandom(),
                        fitemcode: src.fitemcode ?? '',
                        fitemname: src.fitemname ?? '',
                        fsatuan: src.fsatuan ?? '',
                        frefpr: src.frefpr ?? (header?.fpono ?? ''),
                        fqty: Number(src.fqty ?? 0),
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

                    this.savedItems.push(row);
                    existing.add(key);
                    added++;
                });

                this.recalcTotals();
            },

            addIfComplete() {
                const r = this.draft;
                if (!this.isComplete(r)) {
                    if (!r.fitemcode) return this.$refs.draftCode?.focus();
                    if (!r.fitemname) return this.$refs.draftCode?.focus();
                    if (!r.fsatuan) return (r.units.length > 1 ? this.$refs.draftUnit?.focus() : this.$refs.draftCode
                        ?.focus());
                    if (!(Number(r.fqty) > 0)) return this.$refs.draftQty?.focus();
                    return;
                }

                this.recalc(r);

                const dupe = this.savedItems.find(it =>
                    it.fitemcode === r.fitemcode &&
                    it.fsatuan === r.fsatuan &&
                    (it.frefpr || '') === (r.frefpr || '')
                );

                if (dupe) {
                    alert('Item sama sudah ada.');
                    return;
                }

                this.savedItems.push({
                    ...r,
                    uid: cryptoRandom()
                });

                this.showNoItems = false;
                this.resetDraft();
                this.$nextTick(() => this.$refs.draftCode?.focus());
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
                    ...r
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
                        this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode));
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
                frefpr: '',
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

<script>
    window.prhFormModal = function() {
        return {
            show: false,
            rows: [],
            search: '',
            perPage: 10,
            currentPage: 1,
            lastPage: 1,
            total: 0,
            loading: false,

            showDupModal: false,
            dupCount: 0,
            dupSample: [],
            pendingHeader: null,
            pendingUniques: [],

            openDupModal(header, duplicates, uniques) {
                this.dupCount = duplicates.length;
                this.dupSample = duplicates.slice(0, 6); // simple preview (max 6 baris)
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
                // kirim hanya item unik
                window.dispatchEvent(new CustomEvent('pr-picked', {
                    detail: {
                        header: this.pendingHeader,
                        items: this.pendingUniques
                    }
                }));
                this.closeDupModal();
                this.closeModal?.();
            },

            openModal() {
                this.show = true;
                this.goToPage(1);
            },
            closeModal() {
                this.show = false;
            },

            async fetchData() {
                this.loading = true;
                try {
                    const params = new URLSearchParams({
                        search: this.search ?? '',
                        per_page: this.perPage,
                        page: this.currentPage,
                    });

                    const res = await fetch(`{{ route('penerimaanbarang.pickable') }}?` + params.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const json = await res.json();

                    this.rows = json.data ?? [];
                    this.currentPage = (json.current_page ?? json.links?.current_page) ?? 1;
                    this.lastPage = (json.last_page ?? json.links?.last_page) ?? 1;
                    this.total = (json.total ?? json.links?.total) ?? (json.data_total ?? 0);
                } catch (e) {
                    console.error(e);
                    this.rows = [];
                } finally {
                    this.loading = false;
                }
            },

            goToPage(p) {
                if (p < 1) p = 1;
                this.currentPage = p;
                this.fetchData();
            },

            formatDate(s) {
                if (!s || s === 'No Date') return '-';
                const d = new Date(s);
                if (isNaN(d)) return '-';
                const pad = n => n.toString().padStart(2, '0');
                return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
            },

            async pick(row) {
                try {
                    const url = `{{ route('penerimaanbarang.items', ['id' => 'PR_ID_PLACEHOLDER']) }}`
                        .replace('PR_ID_PLACEHOLDER', row.fprid);

                    const res = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const json = await res.json();

                    const items = json.items || [];
                    const currentKeys = new Set((window.getCurrentItemKeys?.() || []).map(String));

                    const keyOf = (src) =>
                        `${(src.fitemcode ?? '').toString().trim()}::${(src.frefdtno ?? '').toString().trim()}`;

                    const duplicates = items.filter(src => currentKeys.has(keyOf(src)));
                    const uniques = items.filter(src => !currentKeys.has(keyOf(src)));

                    if (duplicates.length > 0) {
                        this.openDupModal(row, duplicates, uniques);
                        return; // tunggu aksi user di modal
                    }

                    // tidak ada duplikat → langsung kirim semua item yang unik (atau 'items' kalau mau semua)
                    window.dispatchEvent(new CustomEvent('pr-picked', {
                        detail: {
                            header: row,
                            items
                        } // jika ingin hanya unik, ganti 'items' → 'uniques'
                    }));
                    this.closeModal();

                    window.dispatchEvent(new CustomEvent('pr-picked', {
                        detail: {
                            header: row,
                            items
                        }
                    }));

                    this.closeModal();
                } catch (e) {
                    console.error(e);
                    alert('Gagal mengambil detail PR');
                }
            },
        };
    };

    window.warehouseBrowser = function() {
        return {
            open: false,
            table: null,

            initDataTable() {
                if (this.table) {
                    this.table.destroy();
                }

                this.table = $('#warehouseTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('gudang.browse') }}",
                        type: 'GET',
                        data: function(d) {
                            return {
                                draw: d.draw,
                                page: (d.start / d.length) + 1,
                                per_page: d.length,
                                search: d.search.value
                            };
                        },
                        dataSrc: function(json) {
                            return json.data;
                        }
                    },
                    columns: [{
                            data: null,
                            name: 'fwhcode',
                            render: function(data, type, row) {
                                return `${row.fwhcode} - ${row.fwhname}`;
                            }
                        },
                        {
                            data: 'fbranchcode',
                            name: 'fbranchcode',
                            render: function(data) {
                                return data || '-';
                            }
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center',
                            render: function(data, type, row) {
                                return '<button type="button" class="btn-choose px-3 py-1 rounded text-xs bg-emerald-600 hover:bg-emerald-700 text-white">Pilih</button>';
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
                        processing: "Memuat...",
                        search: "Cari:",
                        lengthMenu: "Tampilkan _MENU_ data",
                        info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                        infoEmpty: "Menampilkan 0 data",
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
                    ], // Sort by kode gudang
                    autoWidth: false,
                    initComplete: function() {
                        const api = this.api();
                        const $container = $(api.table().container());

                        // Lebarkan search input
                        $container.find('.dt-search .dt-input, .dataTables_filter input').css({
                            width: '400px',
                            maxWidth: '100%',
                            minWidth: '300px'
                        });

                        // Opsional: lebarkan wrapper search juga
                        $container.find('.dt-search, .dataTables_filter').css({
                            minWidth: '420px'
                        });
                    }
                });

                // Handle button click
                $('#warehouseTable').on('click', '.btn-choose', (e) => {
                    const data = this.table.row($(e.target).closest('tr')).data();
                    this.choose(data);
                });
            },

            openModal() {
                this.open = true;
                // Initialize DataTable setelah modal terbuka
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
                // Kirim event ke halaman utama
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
                // Buka modal saat event dipanggil
                window.addEventListener('warehouse-browse-open', () => this.openModal());
            }
        }
    };

    window.accountBrowser = function() {
        return {
            open: false,
            table: null,

            initDataTable() {
                if (this.table) {
                    this.table.destroy();
                }

                this.table = $('#accountTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('account.browse') }}",
                        type: 'GET',
                        data: function(d) {
                            return {
                                draw: d.draw,
                                page: (d.start / d.length) + 1,
                                per_page: d.length,
                                search: d.search.value
                            };
                        },
                        dataSrc: function(json) {
                            return json.data;
                        }
                    },
                    columns: [{
                            data: null,
                            name: 'faccount',
                            render: function(data, type, row) {
                                return `${row.faccount} - ${row.faccname}`;
                            }
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center',
                            render: function(data, type, row) {
                                return '<button type="button" class="btn-choose px-3 py-1 rounded text-xs bg-emerald-600 hover:bg-emerald-700 text-white">Pilih</button>';
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
                        processing: "Memuat...",
                        search: "Cari:",
                        lengthMenu: "Tampilkan _MENU_ data",
                        info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                        infoEmpty: "Menampilkan 0 data",
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
                    ], // Sort by nama
                    autoWidth: false,
                    initComplete: function() {
                        const api = this.api();
                        const $container = $(api.table().container());

                        // Lebarkan search input
                        $container.find('.dt-search .dt-input, .dataTables_filter input').css({
                            width: '400px',
                            maxWidth: '100%',
                            minWidth: '300px'
                        });

                        // Opsional: lebarkan wrapper search juga
                        $container.find('.dt-search, .dataTables_filter').css({
                            minWidth: '420px'
                        });
                    }
                });

                // Handle button click
                $('#accountTable').on('click', '.btn-choose', (e) => {
                    const data = this.table.row($(e.target).closest('tr')).data();
                    this.choose(data);
                });
            },

            openModal() {
                this.open = true;
                // Initialize DataTable setelah modal terbuka
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
                window.dispatchEvent(new CustomEvent('account-picked', {
                    detail: {
                        faccid: w.faccid,
                        faccount: w.faccount,
                        faccname: w.faccname,
                    }
                }));
                this.close();
            },

            init() {
                window.addEventListener('account-browse-open', () => this.openModal());
            }
        }

    };
    // Helper: update field saat account-picked
    document.addEventListener('DOMContentLoaded', () => {
        window.addEventListener('account-picked', (ev) => {
            let {
                faccount,
                faccid
            } = ev.detail || {};

            // Fallback untuk mencari faccid dari option jika tidak ada
            if (!faccid && faccount) {
                const sel = document.getElementById('accountSelect');
                if (sel) {
                    const option = sel.querySelector(`option[value="${faccount}"]`);
                    if (option) {
                        faccid = option.getAttribute('data-faccid');
                    }
                }
            }

            const sel = document.getElementById('accountSelect');
            const hidId = document.getElementById('accountIdHidden');
            const hidCode = document.getElementById('accountCodeHidden');

            if (sel) {
                sel.value = faccount || '';
                sel.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }

            if (hidId) {
                hidId.value = faccid || '';
            }

            if (hidCode) {
                hidCode.value = faccount || '';
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
                                // DataTables mengirim parameter search[value] untuk pencarian
                                return {
                                    draw: d.draw,
                                    page: (d.start / d.length) + 1,
                                    per_page: d.length,
                                    q: d.search.value
                                };
                            },
                            dataSrc: function(json) {
                                // Mapping response ke format DataTables
                                return json.data;
                            }
                        },
                        columns: [{
                                data: 'fprdcode',
                                name: 'fprdcode',
                                className: 'font-mono'
                            },
                            {
                                data: 'fprdname',
                                name: 'fprdname'
                            },
                            {
                                data: 'fsatuanbesar',
                                name: 'fsatuanbesar',
                                render: function(data) {
                                    return data || '-';
                                }
                            },
                            {
                                data: 'fminstock',
                                name: 'fminstock',
                                className: 'text-center'
                            },
                            {
                                data: null,
                                orderable: false,
                                searchable: false,
                                className: 'text-center',
                                render: function(data, type, row) {
                                    return '<button type="button" class="btn-choose px-3 py-1 rounded text-xs bg-emerald-600 hover:bg-emerald-700 text-white">Pilih</button>';
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
                            processing: "Memuat...",
                            search: "Cari:",
                            lengthMenu: "Tampilkan _MENU_ data",
                            info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                            infoEmpty: "Menampilkan 0 data",
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
                        ], // Sort by nama
                        autoWidth: false,
                        initComplete: function() {
                            const api = this.api();
                            const $container = $(api.table().container());

                            // Lebarkan search input
                            $container.find('.dt-search .dt-input, .dataTables_filter input').css({
                                width: '400px',
                                maxWidth: '100%',
                                minWidth: '300px'
                            });

                            // Opsional: lebarkan wrapper search juga
                            $container.find('.dt-search, .dataTables_filter').css({
                                minWidth: '420px'
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
    </script>
@endpush
