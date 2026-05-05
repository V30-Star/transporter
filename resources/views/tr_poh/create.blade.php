@extends('layouts.app')

@section('title', 'Order Pembelian')

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
        $oldItemCodes = old('fitemcode', []);
        $oldItemNames = old('fitemname', []);
        $oldSatuans = old('fsatuan', []);
        $oldRefDtNos = old('frefdtno', []);
        $oldRefDtIds = old('frefdtid', []);
        $oldNoUrefs = old('fnouref', []);
        $oldNoAcaks = old('fnoacak', []);
        $oldRefNoAcaks = old('frefnoacak', []);
        $oldRefPrs = old('frefpr', []);
        $oldPrhIds = old('fprhid', []);
        $oldQtys = old('fqty', []);
        $oldPrices = old('fprice', []);
        $oldDiscs = old('fdisc', []);
        $oldTotals = old('ftotal', []);
        $oldDescs = old('fdesc', []);
        $oldKetdts = old('fketdt', []);
        $initialPoItems = [];

        foreach ($oldItemCodes as $index => $itemCode) {
            $initialPoItems[] = [
                'fitemcode' => $itemCode,
                'fitemname' => $oldItemNames[$index] ?? '',
                'fsatuan' => $oldSatuans[$index] ?? '',
                'frefdtno' => $oldRefDtNos[$index] ?? '',
                'frefdtid' => $oldRefDtIds[$index] ?? '',
                'fnouref' => $oldNoUrefs[$index] ?? '',
                'fnoacak' => $oldNoAcaks[$index] ?? '',
                'frefnoacak' => $oldRefNoAcaks[$index] ?? '',
                'frefpr' => $oldRefPrs[$index] ?? '',
                'fprhid' => $oldPrhIds[$index] ?? '',
                'fqty' => $oldQtys[$index] ?? 0,
                'fprice' => $oldPrices[$index] ?? 0,
                'fdisc' => $oldDiscs[$index] ?? 0,
                'ftotal' => $oldTotals[$index] ?? 0,
                'fdesc' => $oldDescs[$index] ?? '',
                'fketdt' => $oldKetdts[$index] ?? '',
            ];
        }
    @endphp
    <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1600px] w-full mx-auto">
        <form action="{{ route('tr_poh.store') }}" method="POST" class="mt-6" data-form-draft="true"
            data-draft-key="tr_poh:create" x-data="mainForm()"
            x-init="syncSupplierDisplay(@js(old('fsupplier', ''))); restoreSavedItems(@js($initialPoItems)); init()"
            @submit.prevent="submitForm($el)">
            @csrf

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
            @if (session('error'))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            {{-- HEADER FORM --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

                {{-- Cabang --}}
                <div class="lg:col-span-4">
                    <label class="block text-sm font-medium">Cabang</label>
                    <input type="text" class="w-full border rounded px-3 py-2 bg-gray-200 cursor-not-allowed"
                        value="{{ $fcabang }}" disabled>
                    <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                </div>

                {{-- PO# --}}
                <div class="lg:col-span-4">
                    <label class="block text-sm font-medium mb-1">PO#</label>
                    <div class="flex items-center gap-3">
                        <input type="text" name="fpohid" class="w-full border rounded px-3 py-2" :disabled="autoCode"
                            :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'">
                        <label class="inline-flex items-center select-none">
                            <input type="checkbox" x-model="autoCode" checked>
                            <span class="ml-2 text-sm text-gray-700">Auto</span>
                        </label>
                    </div>
                </div>

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
                                        {{ old('fsupplier', $filterSupplierId) == $supplier->fsuppliercode ? 'selected' : '' }}>
                                        {{ $supplier->fsuppliername }} ({{ $supplier->fsuppliercode }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="absolute inset-0" role="button"
                                @click="window.dispatchEvent(new CustomEvent('supplier-browse-open'))"></div>
                        </div>
                        <input type="hidden" name="fsupplier" id="supplierCodeHidden" value="{{ old('fsupplier') }}">
                        <button type="button" @click="window.dispatchEvent(new CustomEvent('supplier-browse-open'))"
                            class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                            title="Browse Supplier">
                            <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                        </button>
                        <a href="{{ route('supplier.create') }}" target="_blank" rel="noopener"
                            class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50" title="Tambah Supplier">
                            <x-heroicon-o-plus class="w-5 h-5" />
                        </a>
                    </div>
                    @error('fsupplier')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tanggal --}}
                <div class="lg:col-span-4">
                    <label class="block text-sm font-medium">Tanggal</label>
                    <input type="date" name="fpodate" value="{{ old('fpodate') ?? date('Y-m-d') }}"
                        class="w-full border rounded px-3 py-2 @error('fpodate') border-red-500 @enderror">
                    @error('fpodate')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tgl. Kirim --}}
                <div class="lg:col-span-4">
                    <label class="block text-sm font-medium">Tgl. Kirim</label>
                    <input type="date" name="fkirimdate" value="{{ old('fkirimdate', '') }}"
                        class="w-full border rounded px-3 py-2 @error('fkirimdate') border-red-500 @enderror">
                    @error('fkirimdate')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tempo --}}
                <div class="lg:col-span-4">
                    <label class="block text-sm font-medium mb-1">Tempo</label>
                    <div class="flex items-center">
                        <input type="number" id="ftempohr" name="ftempohr" value="{{ old('ftempohr', 0) }}"
                            class="w-full border rounded px-3 py-2">
                        <span class="ml-2">Hari</span>
                    </div>
                </div>

                {{-- Currency --}}
                <div class="lg:col-span-4">
                    <label class="block text-sm font-medium">Currency</label>
                    <select name="fcurrency" id="currencySelect" x-model="selectedCurrId" @change="onCurrencyChange()"
                        class="w-full border rounded px-3 py-2 @error('fcurrency') border-red-500 @enderror">
                        <option value="">-- Pilih Currency --</option>
                        @foreach ($currencies as $cur)
                            <option value="{{ $cur->fcurrid }}" data-code="{{ $cur->fcurrcode }}"
                                data-rate="{{ $cur->frate }}"
                                {{ ($cur->fcurrcode === 'IDR' && !old('fcurrencyid')) || old('fcurrencyid') == $cur->fcurrid ? 'selected' : '' }}>
                                {{ $cur->fcurrname }} ({{ $cur->fcurrcode }})
                            </option>
                        @endforeach
                    </select>
                    <input type="hidden" name="fcurrencyid" :value="selectedCurrId">
                    <input type="hidden" name="fcurrcode" :value="selectedCurrCode">
                    @error('fcurrency')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Rate --}}
                <div class="lg:col-span-4">
                    <label class="block text-sm font-medium">Rate</label>
                    <input type="number" step="0.01" min="0" name="frate" x-model.number="rateValue"
                        class="w-full border rounded px-3 py-2 @error('frate') border-red-500 @enderror"
                        placeholder="Rate akan terisi otomatis">
                    @error('frate')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Keterangan --}}
                <div class="lg:col-span-12">
                    <label class="block text-sm font-medium">Keterangan</label>
                    <textarea name="fket" rows="3"
                        class="w-full border rounded px-3 py-2 @error('fket') border-red-500 @enderror"
                        placeholder="Tulis keterangan tambahan di sini...">{{ old('fket') }}</textarea>
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
                                <th class="p-2 text-left w-36">Ref.PR#</th>
                                <th class="p-2 text-right w-24 whitespace-nowrap">Qty</th>
                                <th class="p-2 text-right w-32 whitespace-nowrap">@ Harga</th>
                                <th class="p-2 text-right w-24 whitespace-nowrap">Disc. %</th>
                                <th class="p-2 text-right w-36 whitespace-nowrap">Total Harga</th>
                                <th class="p-2 text-right w-36 whitespace-nowrap">Total Rp.</th>
                                <th class="p-2 text-center w-20">Aksi</th>
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
                                                class="flex-1 border rounded-l px-2 py-1 font-mono text-sm min-w-0"
                                                x-model.trim="it.fitemcode" @focus="activeRow = it.uid"
                                                @blur="activeRow = null" @input="onCodeTypedSaved(it)"
                                                @keydown.enter.prevent="focusSavedUnit(it, i)">
                                            <button type="button" @click="openBrowseFor('saved', i)"
                                                class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50"
                                                title="Cari Produk">
                                                <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>

                                    {{-- Nama Produk + Deskripsi --}}
                                    <td class="p-2 relative overflow-visible">
                                        <input type="text"
                                            class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                            :value="it.fitemname" disabled>
                                        <textarea x-model="it.fdesc" rows="2" class="border rounded px-2 py-1 text-xs text-gray-600 mt-1 relative z-10"
                                            style="width: calc(100% + 8rem);" placeholder="Deskripsi (opsional)" @focus="activeRow = it.uid"
                                            @blur="activeRow = null"></textarea>
                                    </td>

                                    {{-- Satuan --}}
                                    <td class="p-2 align-top">
                                        <select class="w-full border rounded px-2 py-1 text-sm"
                                            x-show="it.units.length > 1" :id="'unit_saved_' + i"
                                            @focus="activeRow = it.uid" @blur="activeRow = null"
                                            @keydown.enter.prevent="focusSavedQty(i)"
                                            @change="it.fsatuan = $event.target.value; it.maxqty = calcMaxQty(it);"
                                            x-effect="
                                                const sel = $el;
                                                sel.innerHTML = '';
                                                it.units.forEach(u => {
                                                    const opt = document.createElement('option');
                                                    opt.value = u;
                                                    opt.textContent = u;
                                                    if (u === it.fsatuan) opt.selected = true;
                                                    sel.appendChild(opt);
                                                });
                                                it.maxqty = calcMaxQty(it);
                                            ">
                                        </select>
                                        <input type="text" x-show="it.units.length <= 1"
                                            class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                            :value="it.fsatuan || '-'" disabled>
                                    </td>

                                    {{-- Ref.PR# --}}
                                    <td class="p-2">
                                        <input type="text"
                                            class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                            :value="it.fprno || '-'" disabled>
                                    </td>

                                    {{-- Qty --}}
                                    <td class="p-2 text-right">
                                        <input type="number" class="border rounded px-2 py-1 w-20 text-right text-sm"
                                            x-model.number="it.fqty" :id="'qty_saved_' + i"
                                            @focus="activeRow = it.uid; $event.target.select()" @blur="activeRow = null; enforcePrQtyRow(it);"
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
                                            x-show="it.frefdtid && formatPrRemainHint(it)"
                                            x-html="formatPrRemainHint(it)">
                                        </div>
                                    </td>

                                    {{-- @ Harga --}}
                                    <td class="p-2 text-right">
                                        <input type="number" class="border rounded px-2 py-1 w-28 text-right text-sm"
                                            min="0" step="0.01" x-model.number="it.fprice"
                                            :id="'price_saved_' + i" @focus="activeRow = it.uid; $event.target.select()"
                                            @blur="activeRow = null" @input="recalc(it)" @change="recalc(it)"
                                            @keydown.enter.prevent="focusSavedDisc(i)">
                                    </td>

                                    {{-- Disc. % --}}
                                    <td class="p-2 text-right">
                                        <input type="number" class="border rounded px-2 py-1 w-20 text-right text-sm"
                                            min="0" max="100" step="0.01" x-model.number="it.fdisc"
                                            :id="'disc_saved_' + i" @focus="activeRow = it.uid; $event.target.select()"
                                            @blur="activeRow = null" @input="recalc(it)" @change="recalc(it)"
                                            @keydown.enter.prevent="focusDraftCode()">
                                    </td>

                                    {{-- Total Harga --}}
                                    <td class="p-2 text-right text-sm font-medium" x-text="fmtCurr(it.ftotal)"></td>
                                    <td class="p-2 text-right text-sm font-medium" x-text="rupiah(itemTotalRp(it.ftotal))"></td>

                                    {{-- Aksi --}}
                                    <td class="p-2 text-center">
                                        <button type="button" @click="removeSaved(i)"
                                            class="px-3 py-1 rounded text-xs bg-red-100 text-red-600 hover:bg-red-200 whitespace-nowrap">
                                            Hapus
                                        </button>
                                    </td>

                                    {{-- Hidden inputs --}}
                                    <td class="hidden">
                                        <input type="hidden" name="fitemcode[]" :value="it.fitemcode">
                                        <input type="hidden" name="fitemname[]" :value="it.fitemname">
                                        <input type="hidden" name="fsatuan[]" :value="it.fsatuan">
                                        <input type="hidden" name="frefdtno[]" :value="it.frefdtno">
                                        <input type="hidden" name="frefdtid[]" :value="it.frefdtid">
                                        <input type="hidden" name="fnouref[]" :value="it.fnouref">
                                        <input type="hidden" name="fnoacak[]" :value="it.fnoacak">
                                        <input type="hidden" name="frefnoacak[]" :value="it.frefnoacak">
                                        <input type="hidden" name="frefpr[]" :value="it.frefpr">
                                        <input type="hidden" name="fprhid[]" :value="it.fprhid">
                                        <input type="hidden" name="fqty[]" :value="it.fqty">
                                        <input type="hidden" name="fprice[]" :value="it.fprice">
                                        <input type="hidden" name="fdisc[]" :value="it.fdisc">
                                        <input type="hidden" name="ftotal[]" :value="it.ftotal">
                                        <input type="hidden" name="fdesc[]" :value="it.fdesc">
                                        <input type="hidden" name="fketdt[]" :value="it.fketdt">
                                    </td>
                                </tr>
                            </template>

                            {{-- BARIS DRAFT --}}
                            <tr class="border-t bg-green-50 align-top">
                                <td class="p-2 text-gray-400" x-text="savedItems.length + 1"></td>

                                <td class="p-2">
                                    <div class="flex">
                                        <input type="text"
                                            class="flex-1 border rounded-l px-2 py-1 font-mono text-sm min-w-0"
                                            x-ref="draftCode" x-model.trim="draft.fitemcode"
                                            @input="onCodeTypedRow(draft)" @keydown.enter.prevent="handleEnterOnCode()">
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
                                        x-show="draft.units.length > 1" @keydown.enter.prevent="$refs.draftQty?.focus()">
                                    </select>
                                    <input type="text" x-show="draft.units.length <= 1"
                                        class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                        :value="draft.fsatuan || '-'" disabled>
                                </td>

                                <td class="p-2">
                                    <input type="text"
                                        class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                        :value="draft.fprno || ''" disabled placeholder="Ref PR">
                                </td>
                                <td class="p-2 text-right">
                                    <input type="number" class="border rounded px-2 py-1 w-20 text-right text-sm"
                                        min="0" step="1" x-ref="draftQty" x-model.number="draft.fqty"
                                        @input="recalc(draft);" @blur="enforcePrQtyRow(draft);" @keydown.enter.prevent="$refs.draftPrice?.focus()">
                                    <div class="text-[10px] text-amber-700 font-medium text-right mt-0.5"
                                        x-show="draft.frefdtid && formatPrRemainHint(draft)"
                                        x-html="formatPrRemainHint(draft)">
                                    </div>
                                </td>

                                <td class="p-2 text-right">
                                    <input type="number" class="border rounded px-2 py-1 w-28 text-right text-sm"
                                        min="0" step="0.01" x-ref="draftPrice" x-model.number="draft.fprice"
                                        @input="recalc(draft)" @keydown.enter.prevent="$refs.draftDisc?.focus()">
                                </td>

                                <td class="p-2 text-right">
                                    <input type="number" class="border rounded px-2 py-1 w-20 text-right text-sm"
                                        min="0" max="100" step="0.01" x-ref="draftDisc"
                                        x-model.number="draft.fdisc" @input="recalc(draft)"
                                        @keydown.enter.prevent="addIfComplete()">
                                </td>

                                <td class="p-2 text-right text-sm font-medium" x-text="fmtCurr(draft.ftotal)"></td>
                                <td class="p-2 text-right text-sm font-medium" x-text="rupiah(itemTotalRp(draft.ftotal))"></td>

                                <td class="p-2 text-center">
                                    <button type="button" @click="addIfComplete()"
                                        class="px-3 py-1 rounded text-xs bg-emerald-600 text-white hover:bg-emerald-700 whitespace-nowrap">
                                        Tambah
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Add PR + Panel Totals --}}
                <div x-data="prhFormModal()">
                    <div class="mt-3 flex justify-between items-start gap-4">
                        <div class="flex justify-start">
                            <button type="button" @click="openModal()"
                                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Add PR
                            </button>
                        </div>

                        {{-- Panel Totals --}}
                        <div class="w-[480px] shrink-0">
                            <div class="rounded-lg border bg-gray-50 p-3 space-y-2 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Total Harga</span>
                                    <span class="font-medium" x-text="fmtCurr(totalHarga)"></span>
                                </div>
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <label class="flex items-center gap-1.5 cursor-pointer select-none">
                                            <input type="checkbox" name="fapplyppn" value="1" x-model="includePPN"
                                                class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                            <span class="font-bold">PPN</span>
                                        </label>
                                        <select name="fincludeppn" x-model.number="ppnMode" :disabled="!includePPN"
                                            class="w-28 h-10 px-2 text-sm border rounded appearance-none disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed">
                                            <option value="0">Exclude</option>
                                            <option value="1">Include</option>
                                        </select>
                                        <input type="number" name="ppn_rate" min="0" max="100"
                                            step="0.01" x-model.number="ppnRate" :disabled="!includePPN"
                                            class="w-16 h-10 px-2 text-sm text-right border rounded [appearance:textfield] disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed">
                                        <span class="text-gray-500">%</span>
                                        <span class="flex-1"></span>
                                        <span class="font-medium" x-text="fmtCurr(ppnNominal)"></span>
                                    </div>
                                </div>
                                <div class="border-t"></div>
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-gray-800">
                                        Grand Total
                                        <span class="text-xs font-normal text-gray-500"
                                            x-text="selectedCurrCode ? '(' + selectedCurrCode + ')' : ''"></span>
                                    </span>
                                    <span class="font-bold text-blue-700" x-text="fmtCurr(grandTotal)"></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-gray-800">Grand Total (RP)</span>
                                    <span class="font-bold text-emerald-700" x-text="rupiah(grandTotalRp)"></span>
                                </div>
                            </div>
                            <input type="hidden" name="famountponet" :value="totalHarga">
                            <input type="hidden" name="famountpopajak" :value="ppnNominal">
                            <input type="hidden" name="famountpo" :value="grandTotal">
                            <input type="hidden" name="famountpo_rp" :value="grandTotalRp">
                        </div>
                    </div>

                    {{-- Modal backdrop --}}
                    <div x-show="show" x-transition.opacity class="fixed inset-0 z-40 bg-black/50"
                        @keydown.escape.window="closeModal()"></div>

                    {{-- MODAL PR --}}
                    <div x-show="show" x-cloak x-transition.opacity
                        class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-8" aria-modal="true"
                        role="dialog">
                        <div class="relative w-full max-w-5xl rounded-xl bg-white shadow-2xl flex flex-col"
                            style="height: 600px;">
                            <div
                                class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
                                <h3 class="text-xl font-bold text-gray-800">Pilih Purchase Request (PR)</h3>
                                <button type="button" @click="closeModal()"
                                    class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 font-medium text-gray-700 text-sm">
                                    Tutup
                                </button>
                            </div>
                            <div class="flex-1 overflow-y-auto p-6" style="min-height: 0;">
                                <table id="prTable" class="min-w-full text-sm display nowrap stripe hover"
                                    style="width:100%">
                                    <thead class="sticky top-0 z-10">
                                        <tr class="bg-gray-50 border-b-2 border-gray-200">
                                            <th class="p-3 text-left font-semibold text-gray-700">PR No</th>
                                            <th class="p-3 text-left font-semibold text-gray-700">Supplier</th>
                                            <th class="p-3 text-left font-semibold text-gray-700">Tanggal</th>
                                            <th class="p-3 text-center font-semibold text-gray-700">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                            <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50"></div>
                        </div>
                    </div>

                    {{-- Modal Duplikasi --}}
                    <div x-show="showDupModal" x-cloak x-transition.opacity
                        class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                        <div class="absolute inset-0 bg-black/40" @click="closeDupModal()"></div>
                        <div class="relative bg-white rounded-xl shadow-xl max-w-2xl w-full p-6">
                            <h3 class="text-lg font-semibold mb-4">Peringatan Duplikasi</h3>
                            <p class="mb-4">Ditemukan <strong x-text="dupCount"></strong> item yang sudah ada dalam
                                daftar.</p>
                            <div class="mb-4 max-h-48 overflow-auto border rounded p-2 bg-gray-50"
                                x-show="dupSample.length > 0">
                                <p class="text-sm font-medium mb-2">Contoh item duplikat:</p>
                                <template x-for="(item, idx) in dupSample" :key="idx">
                                    <div class="text-xs py-1">• <span x-text="item.fitemcode"></span></div>
                                </template>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button type="button" @click="closeDupModal()"
                                    class="rounded bg-gray-200 px-4 py-2 text-sm font-medium hover:bg-gray-300">Batal</button>
                            </div>
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

            {{-- MODAL: supplier belum dipilih --}}
            <div x-show="showNoSupplier" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center"
                x-transition.opacity>
                <div class="absolute inset-0 bg-black/50" @click="showNoSupplier=false"></div>
                <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden">
                    <div class="px-5 py-4 border-b flex items-center">
                        <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-amber-500 mr-2" />
                        <h3 class="text-lg font-semibold text-gray-800">Supplier Belum Dipilih</h3>
                    </div>
                    <div class="px-5 py-4">
                        <p class="text-sm text-gray-700">Silakan pilih <strong>Supplier</strong> terlebih dahulu sebelum
                            menambahkan item.</p>
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
                        <p class="text-sm text-gray-500">Satu produk dengan satuan yang sama hanya boleh ditambahkan satu
                            kali.</p>
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
                    <div class="flex-1 overflow-y-auto px-6" style="min-height: 0;">
                        <table id="supplierBrowseTable" class="min-w-full text-sm display nowrap stripe hover"
                            style="width:100%">
                            <thead class="sticky top-0 z-10">
                                <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                    <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Kode
                                    </th>
                                    <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Nama
                                        Supplier</th>
                                    <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Alamat
                                    </th>
                                    <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                        Telepon</th>
                                    <th class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Aksi
                                    </th>
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
                    <div class="flex-1 overflow-y-auto px-6" style="min-height: 0;">
                        <table id="productTable" class="min-w-full text-sm display nowrap stripe hover"
                            style="width:100%">
                            <thead class="sticky top-0 z-10">
                                <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                    <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Kode
                                    </th>
                                    <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Nama
                                        Produk</th>
                                    <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Satuan
                                    </th>
                                    <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Merek
                                    </th>
                                    <th class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                        Stock</th>
                                    <th class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50"></div>
                </div>
            </div>

            @php
                $canApproval = in_array('approvalpr', explode(',', session('user_restricted_permissions', '')));
            @endphp

            <div class="flex justify-center items-center space-x-2 mt-6">
                @if ($canApproval)
                    <label class="block text-sm font-medium">Approval</label>
                    <input type="hidden" name="fapproval" value="0">
                    <label class="switch">
                        <input type="checkbox" name="fapproval" id="approvalToggle" value="1"
                            {{ old('fapproval', 0) ? 'checked' : '' }}>
                        <span class="slider"></span>
                    </label>
                @endif
            </div>

            <div class="mt-8 flex justify-center gap-4">
                <button type="submit"
                    class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                    <x-heroicon-o-check class="w-5 h-5 mr-2" /> Simpan
                </button>
                <button type="button" onclick="window.location.href='{{ route('tr_poh.index') }}'"
                    class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" /> Keluar
                </button>
            </div>
        </form>
    </div>

@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
@endpush

<style>
    div#productTable_length select,
    .dataTables_wrapper #productTable_length select,
    div#supplierBrowseTable_length select,
    .dataTables_wrapper #supplierBrowseTable_length select,
    div#prTable_length select,
    .dataTables_wrapper #prTable_length select {
        min-width: 140px !important;
        width: auto !important;
        padding: 8px 45px 8px 16px !important;
        font-size: 14px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.375rem !important;
    }

    div#productTable_length,
    .dataTables_wrapper #productTable_length,
    div#supplierBrowseTable_length,
    .dataTables_wrapper #supplierBrowseTable_length,
    div#prTable_length,
    .dataTables_wrapper #prTable_length {
        min-width: 250px !important;
    }

    div#productTable_length label,
    .dataTables_wrapper #productTable_length label,
    div#supplierBrowseTable_length label,
    .dataTables_wrapper #supplierBrowseTable_length label,
    div#prTable_length label,
    .dataTables_wrapper #prTable_length label {
        font-size: 14px !important;
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
    }
</style>

<script>
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

    window.CURRENCY_MAP = {
        @foreach ($currencies as $cur)
            {{ $cur->fcurrid }}: {
                id: {{ $cur->fcurrid }},
                code: @json($cur->fcurrcode),
                name: @json($cur->fcurrname),
                rate: {{ $cur->frate ?? 0 }}
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

    function mainForm() {
        function newRow() {
            return {
                uid: null,
                fitemcode: '',
                fitemname: '',
                units: [],
                fsatuan: '',
                frefdtno: '',
                fnouref: '',
                fnoacak: '',
                frefnoacak: '',
                frefpr: '',
                fprhid: '',
                fprno: '',
                fqty: 0,
                fprice: 0,
                fdisc: 0,
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
                unit_ratios: {
                    satuankecil: 1,
                    satuanbesar: 1,
                    satuanbesar2: 1
                },
                maxqty_satuan: '',
                frefdtid: '',
                fqtypo: 0,
                fqtysisapr: 0,
                fqtydipo: 0,
                fqtykecil_ref: 0,
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
            savedItems: [],
            draft: newRow(),
            activeRow: null,
            browseTarget: 'draft',
            showNoItems: false,
            showNoSupplier: false,
            showDupItemModal: false,
            dupItemName: '',
            dupItemSatuan: '',

            normalizeNoAcak(value) {
                return (value || '').toString().replace(/\D/g, '').slice(0, 3);
            },

            generateUniqueNoAcak() {
                const used = new Set(this.savedItems.map(item => this.normalizeNoAcak(item.fnoacak)).filter(Boolean));
                let candidate = '';

                do {
                    candidate = Array.from({ length: 3 }, () => '123456789'[Math.floor(Math.random() * 9)]).join('');
                } while (used.has(candidate));

                return candidate;
            },

            get totalHarga() {
                return this.savedItems.reduce((s, it) => s + (it.ftotal || 0), 0);
            },
            get ppnNominal() {
                if (!this.includePPN) return 0;
                const total = this.totalHarga,
                    rate = +this.ppnRate || 0;
                return this.ppnMode === 1 ? Math.round(total * rate / (100 + rate)) : Math.round(total * rate /
                    100);
            },
            get grandTotal() {
                if (!this.includePPN) return this.totalHarga;
                return this.ppnMode === 1 ? this.totalHarga : this.totalHarga + this.ppnNominal;
            },
            get grandTotalRp() {
                if (!this.selectedCurrCode || this.selectedCurrCode === 'IDR') return this.grandTotal;
                return +(this.grandTotal * (+this.rateValue || 1)).toFixed(2);
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
            itemTotalRp(value) {
                const total = Number(value || 0);
                if (!Number.isFinite(total)) return 0;
                if (!this.selectedCurrCode || this.selectedCurrCode === 'IDR') return total;
                return +(total * (+this.rateValue || 1)).toFixed(2);
            },

            onCurrencyChange() {
                const id = parseInt(this.selectedCurrId);
                const cur = window.CURRENCY_MAP[id];
                if (cur) {
                    this.selectedCurrCode = cur.code;
                    this.rateValue = cur.rate;
                } else {
                    this.selectedCurrCode = '';
                    this.rateValue = 0;
                }
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

            formatPrRemainHint(row) {
                return '';
            },

            enforcePrQtyRow(row) {
                const n = +row.fqty;
                if (!Number.isFinite(n)) {
                    row.fqty = 1;
                    return;
                }
                if (n < 1) row.fqty = 1;
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

            syncSupplierDisplay(code) {
                const supplierCode = (code || '').toString().trim();
                const sel = document.getElementById('modal_filter_supplier_id');
                const hid = document.getElementById('supplierCodeHidden');
                if (hid) {
                    hid.value = supplierCode;
                }
                if (!sel) {
                    return;
                }

                let found = false;
                Array.from(sel.options).forEach((option) => {
                    const selected = String(option.value) === supplierCode;
                    option.selected = selected;
                    if (selected) {
                        found = true;
                    }
                });

                if (!found && supplierCode) {
                    sel.add(new Option(supplierCode, supplierCode, true, true));
                }

                sel.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            },

            buildSavedItem(source = {}) {
                const row = {
                    ...newRow(),
                    ...source
                };
                const meta = this.productMeta(row.fitemcode);
                const units = [...new Set((meta.units || []).map((unit) => (unit ?? '').toString().trim()).filter(Boolean))];
                const currentSatuan = (row.fsatuan || '').toString().trim();

                if (currentSatuan && !units.includes(currentSatuan)) {
                    units.unshift(currentSatuan);
                }

                row.uid = row.uid || cryptoRandom();
                row.fitemcode = (row.fitemcode || '').toString().trim();
                row.fitemname = (row.fitemname || meta.name || '').toString();
                row.units = units;
                row.fsatuan = currentSatuan || units[0] || '';
                row.frefdtno = (row.frefdtno || '').toString();
                row.frefdtid = (row.frefdtid || '').toString();
                row.fnouref = (row.fnouref || '').toString();
                row.fnoacak = this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak();
                row.frefnoacak = this.normalizeNoAcak(row.frefnoacak);
                row.frefpr = (row.frefpr || '').toString();
                row.fprhid = (row.fprhid || '').toString();
                row.fqty = Number(row.fqty || 0);
                row.fprice = Number(row.fprice || 0);
                row.fdisc = Number(row.fdisc || 0);
                row.ftotal = Number(row.ftotal || 0);
                row.fdesc = (row.fdesc || '').toString();
                row.fketdt = (row.fketdt || '').toString();
                row.fsatuankecil = row.fsatuankecil || meta.fsatuankecil || '';
                row.fsatuanbesar = row.fsatuanbesar || meta.fsatuanbesar || '';
                row.fsatuanbesar2 = row.fsatuanbesar2 || meta.fsatuanbesar2 || '';
                row.fqtykecil = Number(row.fqtykecil || meta.fqtykecil || 0);
                row.fqtykecil2 = Number(row.fqtykecil2 || meta.fqtykecil2 || 0);
                row.unit_ratios = meta.unit_ratios || row.unit_ratios || {
                    satuankecil: 1,
                    satuanbesar: 1,
                    satuanbesar2: 1
                };
                row.maxqty = this.calcMaxQty(row);

                if (!row.ftotal && row.fqty && row.fprice) {
                    row.ftotal = +(row.fqty * row.fprice * (1 - row.fdisc / 100)).toFixed(2);
                }

                return row;
            },

            restoreSavedItems(items = []) {
                if (!Array.isArray(items) || items.length === 0) {
                    return;
                }

                this.savedItems = items
                    .filter((item) => (item?.fitemcode || '').toString().trim() !== '')
                    .map((item) => this.buildSavedItem(item));

                if (this.savedItems.length > 0) {
                    this.showNoItems = false;
                }
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

            // Sisa PR (tr_prd.fqtykecil) disimpan di satuan kecil (mis. pcs). Konversi ke satuan PO (pcs/ctn/coll) pakai rasio master.
            calcMaxQty(row) {
                const eq = (a, b) => (a || '').trim().toLowerCase() === (b || '').trim().toLowerCase();
                const satuanPO = (row.fsatuan || '').trim();
                const satuanPR = (row.fqtypr_satuan || '').trim();
                const satKecil = (row.fsatuankecil || '').trim();
                const satBesar = (row.fsatuanbesar || '').trim();
                const satBesar2 = (row.fsatuanbesar2 || '').trim();
                const rasio = Number(row.fqtykecil || 0);
                const rasio2 = Number(row.fqtykecil2 || 0);
                const sisaPrBaris = Number(row.fqtysisapr ?? 0);

                if (sisaPrBaris > 0 && (!satuanPR || eq(satuanPO, satuanPR))) {
                    return sisaPrBaris;
                }

                const hasRemainField = row.fqtykecil_ref !== undefined && row.fqtykecil_ref !== null && row.fqtykecil_ref !== '';

                let sisaKecil = 0;
                if (hasRemainField) {
                    sisaKecil = Math.max(0, Number(row.fqtykecil_ref) || 0);
                } else {
                    const qtyPR = Number(row.fqtypr) || 0;
                    const fqtypo = Number(row.fqtypo) || 0;
                    const satuanPR = (row.fqtypr_satuan || '').trim();
                    if (!satuanPR || !(qtyPR > 0)) return 0;
                    let qtyPRInKecil = qtyPR;
                    if (eq(satuanPR, satBesar) && rasio > 0) {
                        qtyPRInKecil = qtyPR * rasio;
                    } else if (eq(satuanPR, satBesar2) && rasio2 > 0) {
                        qtyPRInKecil = qtyPR * rasio2;
                    }
                    sisaKecil = Math.max(0, qtyPRInKecil - fqtypo);
                }

                if (!satuanPO || eq(satuanPO, satKecil)) {
                    return sisaKecil;
                }
                if (eq(satuanPO, satBesar) && rasio > 0) {
                    return Math.floor(sisaKecil / rasio);
                }
                if (eq(satuanPO, satBesar2) && rasio2 > 0) {
                    return Math.floor(sisaKecil / rasio2);
                }
                return sisaKecil;
            },

            isDupeItem(candidate) {
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
                        fsatuan
                    };
                    if (this.isDupeItem(candidate)) {
                        skipped.push(src);
                        return;
                    }
                    const units = meta ? [...new Set((meta.units || []).map(u => (u ?? '').toString().trim())
                            .filter(Boolean))] :
                        (Array.isArray(src.units) && src.units.length ? src.units : [fsatuan].filter(Boolean));
                    if (fsatuan && !units.includes(fsatuan)) units.unshift(fsatuan);

                    // Ambil data konversi: prioritas dari src (data PR), fallback ke PRODUCT_MAP
                    const fsatuankecil = src.fsatuankecil || meta?.fsatuankecil || '';
                    const fsatuanbesar = src.fsatuanbesar || meta?.fsatuanbesar || '';
                    const fsatuanbesar2 = src.fsatuanbesar2 || meta?.fsatuanbesar2 || '';
                    const fqtykecil = Number(src.fqtykecil ?? meta?.fqtykecil ?? 0);
                    const fqtykecil2 = Number(src.fqtykecil2 ?? meta?.fqtykecil2 ?? 0);

                    const row = {
                        uid: cryptoRandom(),
                        fitemcode: src.fitemcode ?? '',
                        fitemname,
                        units,
                        fsatuan: fsatuan || units[0] || '',
                        frefdtno: src.frefdtno ?? '',
                        fnouref: src.fnouref ?? '',
                        fnoacak: this.generateUniqueNoAcak(),
                        frefnoacak: this.normalizeNoAcak(src.frefnoacak ?? src.fnoacak ?? ''),
                        frefpr: String(header?.fprhid ?? src.fprhid ?? ''),
                        fprhid: String(src.fprhid ?? header?.fprhid ?? ''),
                        fprno: String(header?.fprno ?? src.fprno ?? ''),
                        fqty: (src.fqty !== null && src.fqty !== undefined && Number(src.fqty) > 0) ? Number(src.fqty) : 1,
                        fqtypo: Number(src.fqtypo ?? 0),
                        fqtysisapr: Number(src.fqtysisapr ?? 0),
                        fqtydipo: Number(src.fqtydipo ?? 0),
                        fqtykecil_ref: Number(src.fqtykecil_ref ?? src.fqtyremain ?? 0),
                        fqtypr: Number(src.fqtypr ?? src.fqty ?? 0),
                        fqtypr_satuan: (src.fqtypr_satuan ?? src.fsatuan ?? '').trim(),
                        frefdtid: src.frefdtid ?? '',
                        fsatuankecil: src.fsatuankecil || meta?.fsatuankecil || '',
                        fsatuanbesar: src.fsatuanbesar || meta?.fsatuanbesar || '',
                        fsatuanbesar2: src.fsatuanbesar2 || meta?.fsatuanbesar2 || '',
                        fqtykecil: Number(src.fqtykecil ?? meta?.fqtykecil ?? 0),
                        fqtykecil2: Number(src.fqtykecil2 ?? meta?.fqtykecil2 ?? 0),
                        maxqty_satuan: src.maxqty_satuan ?? '',
                        fprice: Number(src.fprice ?? 0),
                        fdisc: Number(src.fdisc ?? 0),
                        ftotal: Number(src.ftotal ?? 0),
                        fdesc: src.fdesc ?? src.fketdt ?? '',
                        fketdt: src.fketdt ?? '',
                    };

                    row.maxqty = this.calcMaxQty(row);
                    if (!(Number(row.maxqty) > 0)) return;
                    if (Number(row.maxqty) > 0) {
                        row.fqty = Number(row.maxqty);
                    }

                    if (!row.ftotal && row.fqty && row.fprice)
                        row.ftotal = +(row.fqty * row.fprice * (1 - row.fdisc / 100)).toFixed(2);
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
                return `${(it.fitemcode??'').toString().trim()}::${(it.fsatuan??'').toString().trim()}`;
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
                const idrEntry = Object.values(window.CURRENCY_MAP).find(c => c.code === 'IDR');
                if (idrEntry && !this.selectedCurrId) {
                    this.selectedCurrId = String(idrEntry.id);
                    this.selectedCurrCode = idrEntry.code;
                    this.rateValue = 1;
                }
                this.draft.fnoacak = this.generateUniqueNoAcak();
                this.syncSupplierDisplay(@js(old('fsupplier', '')));
                this.restoreSavedItems(@js($initialPoItems));
                window.getCurrentItemKeys = () => this.getCurrentItemKeys();
                window.isDupeItem = (candidate) => this.isDupeItem(candidate);
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

    function supplierBrowser() {
        return {
            open: false,
            dataTable: null,
            initDataTable() {
                if (this.dataTable) this.dataTable.destroy();
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
                            width: '300px',
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
                $('#supplierBrowseTable').on('click', '.btn-choose', (e) => {
                    this.chooseSupplier(this.dataTable.row($(e.target).closest('tr')).data());
                });
            },
            openBrowse() {
                this.open = true;
                this.$nextTick(() => this.initDataTable());
            },
            close() {
                this.open = false;
                if (this.dataTable) this.dataTable.search('').draw();
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

<script>
    window.prhFormModal = function() {
        return {
            show: false,
            table: null,
            showDupModal: false,
            dupCount: 0,
            dupSample: [],
            pendingHeader: null,
            pendingUniques: [],
            initDataTable() {
                if (this.table) this.table.destroy();
                this.table = $('#prTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('tr_poh.pickable') }}",
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
                            data: 'fprno',
                            name: 'fprno',
                            className: 'font-mono text-sm'
                        },
                        {
                            data: 'fsuppliername',
                            name: 'fsuppliername',
                            className: 'text-sm',
                            render: d => d || '-'
                        },
                        {
                            data: 'fprdate',
                            name: 'fprdate',
                            className: 'text-sm',
                            render: d => formatDate(d)
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center',
                            render: () =>
                                '<button type="button" class="btn-pick px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 text-white">Pilih</button>'
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
                        [2, 'desc']
                    ],
                    autoWidth: false,
                    initComplete: function() {
                        const $c = $(this.api().table().container());
                        $c.find('.dt-search .dt-input, .dataTables_filter input').css({
                            width: '300px',
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
                $('#prTable').off('click', '.btn-pick').on('click', '.btn-pick', function() {
                    self.pick(self.table.row($(this).closest('tr')).data());
                });
            },
            openModal() {
                if (!(document.getElementById('supplierCodeHidden')?.value || '').trim()) {
                    window.dispatchEvent(new CustomEvent('show-no-supplier'));
                    return;
                }
                this.show = true;
                this.$nextTick(() => this.initDataTable());
            },
            closeModal() {
                this.show = false;
                if (this.table) this.table.search('').draw();
            },
            openDupModal(header, duplicates, uniques) {
                window.transactionReferenceModalHelper.openDupModal(this, header, duplicates, uniques);
            },
            closeDupModal() {
                window.transactionReferenceModalHelper.closeDupModal(this);
            },
            confirmAddUniques() {
                window.transactionReferenceModalHelper.confirmAddUniques(this, 'pr-picked');
            },
            async pick(row) {
                try {
                    const url = `{{ route('tr_poh.items', ['id' => 'PR_ID_PLACEHOLDER']) }}`.replace(
                        'PR_ID_PLACEHOLDER', row.fprhid);
                    const res = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const json = await res.json();
                    const items = json.items || [];
                    const checkDupe = window.isDupeItem ?
                        (src) => window.isDupeItem({
                            fitemcode: src.fitemcode ?? '',
                            fitemname: src.fitemname ?? '',
                            fsatuan: src.fsatuan ?? ''
                        }) :
                        () => false;
                    const duplicates = items.filter(src => checkDupe(src));
                    const uniques = items.filter(src => !checkDupe(src));
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
                }
            }
        };
    };

    function formatDate(s) {
        if (!s || s === 'No Date') return '-';
        const d = new Date(s);
        if (isNaN(d.getTime())) return '-';
        const pad = n => n.toString().padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }
</script>

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
        function productBrowser() {
            return {
                open: false,
                table: null,
                initDataTable() {
                    if (this.table) this.table.destroy();
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
                                width: '300px',
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
                    $('#productTable').on('click', '.btn-choose', (e) => {
                        this.choose(this.table.row($(e.target).closest('tr')).data());
                    });
                },
                close() {
                    this.open = false;
                    if (this.table) this.table.search('').draw();
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
                        this.$nextTick(() => this.initDataTable());
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
