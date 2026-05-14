@php
    $mode = $mode ?? 'create';
    $isCreateMode = $mode === 'create';
    $isEditMode = $mode === 'edit';
    $isViewMode = $mode === 'view';
    $isDeleteMode = $mode === 'delete';
    $isReadOnlyMode = $isViewMode || $isDeleteMode;
    $disabled = $isDeleteMode ? 'disabled' : '';
    $readonly = $isDeleteMode ? 'readonly' : '';
    $bgDisabled = $isDeleteMode ? 'bg-gray-100 cursor-not-allowed text-gray-500' : '';
    $fcabang = $fcabang ?? '';
    $fbranchcode = $fbranchcode ?? '';
    $suppliers = $suppliers ?? collect();
    $currencies = $currencies ?? collect();
    $currentCurrency = $currentCurrency ?? null;
    $tr_poh = $tr_poh ?? new stdClass();
    $filterSupplierId = $filterSupplierId ?? '';
    $fmtQty = $fmtQty ?? function ($value) {
        $num = (float) ($value ?? 0);
        return number_format($num, 2, ',', '.');
    };
@endphp

<div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
    <div class="lg:col-span-4">
        <label class="block text-sm font-medium">Cabang</label>
        <input type="text" class="w-full border rounded px-3 py-2 bg-gray-200 cursor-not-allowed"
            value="{{ $fcabang }}" disabled>
        <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
    </div>

    <div class="lg:col-span-4">
        <label class="block text-sm font-medium mb-1">PO#</label>
        @if ($isCreateMode)
            <div class="flex items-center gap-3">
                <input type="text" name="fpohid" class="w-full border rounded px-3 py-2" :disabled="autoCode"
                    :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'">
                <label class="inline-flex items-center select-none">
                    <input type="checkbox" x-model="autoCode" checked>
                    <span class="ml-2 text-sm text-gray-700">Auto</span>
                </label>
            </div>
        @else
            <input type="text" name="fpohid" value="{{ old('fpohid', $tr_poh->fpono ?? '') }}"
                class="w-full border rounded px-3 py-2 bg-gray-200 cursor-not-allowed" disabled>
        @endif
    </div>

    <div class="lg:col-span-4">
        <label class="block text-sm font-medium mb-1">Supplier</label>
        <div class="flex">
            <div class="relative flex-1">
                <select id="modal_filter_supplier_id" name="filter_supplier_id"
                    class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
                    {{ $isReadOnlyMode ? 'disabled' : '' }}>
                    <option value=""></option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->fsuppliercode }}"
                            {{ old('fsupplier', $isCreateMode ? $filterSupplierId : ($tr_poh->fsupplier ?? '')) == $supplier->fsuppliercode ? 'selected' : '' }}>
                            {{ $supplier->fsuppliername }} ({{ $supplier->fsuppliercode }})
                        </option>
                    @endforeach
                </select>
                @if (!$isReadOnlyMode)
                    <div class="absolute inset-0" role="button"
                        @click="window.dispatchEvent(new CustomEvent('{{ $isCreateMode ? 'tr-poh-supplier-browse-open' : 'supplier-browse-open' }}'))"></div>
                @endif
            </div>
            <input type="hidden" name="fsupplier" id="supplierCodeHidden"
                value="{{ old('fsupplier', $tr_poh->fsupplier ?? '') }}">
            @if (!$isReadOnlyMode)
                <button type="button"
                    @click="window.dispatchEvent(new CustomEvent('{{ $isCreateMode ? 'tr-poh-supplier-browse-open' : 'supplier-browse-open' }}'))"
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

    <div class="lg:col-span-4">
        <label class="block text-sm font-medium">Tanggal</label>
        <input type="date" name="fpodate"
            value="{{ old('fpodate', $isCreateMode ? date('Y-m-d') : substr($tr_poh->fpodate ?? '', 0, 10)) }}"
            {{ $isReadOnlyMode ? 'disabled' : '' }}
            class="w-full border rounded px-3 py-2 {{ $bgDisabled }} @error('fpodate') border-red-500 @enderror">
        @error('fpodate')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="lg:col-span-4">
        <label class="block text-sm font-medium">Tgl. Kirim</label>
        <input type="date" name="fkirimdate"
            value="{{ old('fkirimdate', $isCreateMode ? '' : substr($tr_poh->fkirimdate ?? '', 0, 10)) }}"
            {{ $isReadOnlyMode ? 'disabled' : '' }}
            class="w-full border rounded px-3 py-2 {{ $bgDisabled }} @error('fkirimdate') border-red-500 @enderror">
        @error('fkirimdate')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="lg:col-span-4">
        <label class="block text-sm font-medium mb-1">Tempo</label>
        <div class="flex items-center">
            <input type="number" id="ftempohr" name="ftempohr"
                value="{{ old('ftempohr', $isCreateMode ? 0 : ($tr_poh->ftempohr ?? 0)) }}"
                {{ $isReadOnlyMode ? 'disabled' : '' }}
                class="w-full border rounded px-3 py-2 {{ $bgDisabled }}">
            <span class="ml-2">Hari</span>
        </div>
    </div>

    <div class="lg:col-span-4">
        <label class="block text-sm font-medium">Currency</label>
        @if ($isReadOnlyMode)
            <input type="text" disabled value="{{ $currentCurrency->fcurrname ?? ($tr_poh->fcurrency ?? 'IDR') }}"
                class="w-full border rounded px-3 py-2 bg-gray-100 cursor-not-allowed text-gray-600">
        @else
            <select name="fcurrency" id="currencySelect" x-model="selectedCurrId" @change="onCurrencyChange()"
                class="w-full border rounded px-3 py-2 @error('fcurrency') border-red-500 @enderror">
                <option value="">-- Pilih Currency --</option>
                @foreach ($currencies as $cur)
                    <option value="{{ $cur->fcurrid }}" data-code="{{ $cur->fcurrcode }}"
                        data-rate="{{ $cur->frate }}"
                        {{ ($isCreateMode && (($cur->fcurrcode === 'IDR' && !old('fcurrencyid')) || old('fcurrencyid') == $cur->fcurrid)) || (!$isCreateMode && old('fcurrencyid', $currentCurrency->fcurrid ?? '') == $cur->fcurrid) ? 'selected' : '' }}>
                        {{ $cur->fcurrname }} ({{ $cur->fcurrcode }})
                    </option>
                @endforeach
            </select>
            <input type="hidden" name="fcurrencyid" :value="selectedCurrId">
            <input type="hidden" name="fcurrcode" :value="selectedCurrCode">
        @endif
        @error('fcurrency')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="lg:col-span-4">
        <label class="block text-sm font-medium">Rate</label>
        @if ($isReadOnlyMode)
            <input type="text" disabled value="{{ number_format($tr_poh->frate ?? 1, 2, ',', '.') }}"
                class="w-full border rounded px-3 py-2 bg-gray-100 cursor-not-allowed text-gray-600">
        @else
            <input type="number" step="0.01" min="0" name="frate" x-model.number="rateValue"
                class="w-full border rounded px-3 py-2 @error('frate') border-red-500 @enderror"
                placeholder="Rate akan terisi otomatis">
        @endif
        @error('frate')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="lg:col-span-12">
        <label class="block text-sm font-medium">Keterangan</label>
        <textarea name="fket" rows="3" maxlength="300" {{ $isReadOnlyMode ? 'disabled' : $readonly }}
            class="w-full border rounded px-3 py-2 {{ $bgDisabled }} @error('fket') border-red-500 @enderror"
            placeholder="Tulis keterangan tambahan di sini...">{{ old('fket', $tr_poh->fket ?? '') }}</textarea>
        @error('fket')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="mt-6 space-y-2">
    <h3 class="text-base font-semibold text-gray-800">Detail Item</h3>

    @if ($isCreateMode)
        <div class="overflow-x-auto border rounded">
            <table class="min-w-full text-sm po-detail-table" data-skip-auto-detail-style="true">
                <colgroup>
                    <col style="width:2%;">
                    <col style="width:12%;">
                    <col style="width:27%;">
                    <col style="width:7%;">
                    <col style="width:10%;">
                    <col style="width:8%;">
                    <col style="width:8%;">
                    <col style="width:6%;">
                    <col style="width:8%;">
                    <col style="width:8%;">
                    <col style="width:4%;">
                </colgroup>
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2 text-left w-10">#</th>
                        <th class="p-2 text-left w-20">Kode Produk</th>
                        <th class="p-2 text-left w-[31rem]">Nama Produk</th>
                        <th class="p-2 text-left w-24">Satuan</th>
                        <th class="p-2 text-left w-24">Ref.PR#</th>
                        <th class="p-2 text-right w-24 whitespace-nowrap">Qty</th>
                        <th class="p-2 text-right w-24 whitespace-nowrap">@ Harga</th>
                        <th class="p-2 text-right w-20 whitespace-nowrap">Disc. %</th>
                        <th class="p-2 text-right w-24 whitespace-nowrap">Total Harga</th>
                        <th class="p-2 text-right w-20 whitespace-nowrap">Total Harga (Rp.)</th>
                        <th class="p-2 text-center w-16">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(it, i) in savedItems" :key="it.uid">
                        <tr class="border-t align-top transition-colors"
                            :class="activeRow === it.uid ? 'bg-amber-50' : 'hover:bg-gray-50'">
                            <td class="p-2 text-gray-500" x-text="i + 1"></td>
                            <td class="p-2">
                                <div class="flex w-40">
                                    <input type="text"
                                        class="w-32 border rounded-l px-2 py-1 font-mono text-sm min-w-0"
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
                            <td class="p-2 align-top overflow-visible">
                                <div class="flex w-full max-w-full">
                                    <div class="min-w-0 flex-1 rounded-l border bg-gray-100 px-2 py-1 text-sm leading-5 text-gray-600 whitespace-normal break-words"
                                        x-text="it.fitemname"></div>
                                    <button type="button" @click="openDesc(it)"
                                        class="shrink-0 inline-flex items-center border border-l-0 rounded-r px-2 py-1 transition-colors"
                                        :class="it.fdesc ? 'border-emerald-300 bg-emerald-50 text-emerald-600 hover:bg-emerald-100' : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-50'"
                                        title="Deskripsi item">
                                        <x-heroicon-o-document-text class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                            <td class="p-2 align-top">
                                <select class="w-full border rounded px-2 py-1 text-sm" x-show="it.units.length > 1"
                                    :id="'unit_saved_' + i" @focus="activeRow = it.uid" @blur="activeRow = null"
                                    @keydown.enter.prevent="focusSavedQty(i)"
                                    @change="it.fsatuan = $event.target.value; it.maxqty = calcMaxQty(it);"
                                    x-effect="const sel = $el; sel.innerHTML = ''; it.units.forEach(u => { const opt = document.createElement('option'); opt.value = u; opt.textContent = u; if (u === it.fsatuan) opt.selected = true; sel.appendChild(opt); }); it.maxqty = calcMaxQty(it);">
                                </select>
                                <input type="text" x-show="it.units.length <= 1"
                                    class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                    :value="it.fsatuan || '-'" disabled>
                            </td>
                            <td class="p-2">
                                <input type="text" class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                    :value="it.fprno || '-'" disabled>
                            </td>
                            <td class="p-2 text-right">
                                <input type="number" class="border rounded px-2 py-1 w-20 text-right text-sm"
                                    x-model.number="it.fqty" :id="'qty_saved_' + i"
                                    @focus="activeRow = it.uid; $event.target.select()" @blur="activeRow = null; enforcePrQtyRow(it);"
                                    @input="recalc(it); calcMaxQty(it);" @change="recalc(it); calcMaxQty(it);"
                                    @keydown.enter.prevent="focusSavedPrice(i)">
                                <div class="text-[10px] text-amber-700 font-medium text-right mt-0.5"
                                    x-show="it.frefdtid && formatPrRemainHint(it)" x-html="formatPrRemainHint(it)"></div>
                            </td>
                            <td class="p-2 text-right">
                                <input type="number" class="border rounded px-2 py-1 w-24 text-right text-sm"
                                    min="0" step="0.01" x-model.number="it.fprice" :id="'price_saved_' + i"
                                    @focus="activeRow = it.uid; $event.target.select()" @blur="activeRow = null"
                                    @input="recalc(it)" @change="recalc(it)"
                                    @keydown.enter.prevent="focusSavedDisc(i)">
                            </td>
                            <td class="p-2 text-right">
                                <input type="number" class="border rounded px-2 py-1 w-20 text-right text-sm"
                                    min="0" max="100" step="0.01" x-model.number="it.fdisc"
                                    :id="'disc_saved_' + i" @focus="activeRow = it.uid; $event.target.select()"
                                    @blur="activeRow = null" @input="recalc(it)" @change="recalc(it)"
                                    @keydown.enter.prevent="focusDraftCode()">
                            </td>
                            <td class="p-2">
                                <input type="text" class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm text-right"
                                    :value="fmtCurr(it.ftotal)" disabled>
                            </td>
                            <td class="p-2">
                                <input type="text" class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm text-right"
                                    :value="rupiah(itemTotalRp(it.ftotal))" disabled>
                            </td>
                            <td class="p-2 text-center">
                                <button type="button" @click="removeSaved(i)"
                                    class="px-3 py-1 rounded text-xs bg-red-100 text-red-600 hover:bg-red-200 whitespace-nowrap">
                                    Hapus
                                </button>
                            </td>
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
                    <tr class="border-t bg-green-50 align-top">
                        <td class="p-2 text-gray-400" x-text="savedItems.length + 1"></td>
                        <td class="p-2"><div class="flex w-40"><input type="text" class="w-32 border rounded-l px-2 py-1 font-mono text-sm min-w-0" x-ref="draftCode" x-model.trim="draft.fitemcode" @input="onCodeTypedRow(draft)" @keydown.enter.prevent="handleEnterOnCode()"><button type="button" @click="openBrowseFor('draft')" class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50" title="Cari Produk"><x-heroicon-o-magnifying-glass class="w-4 h-4" /></button></div></td>
                        <td class="p-2 align-top overflow-visible"><div class="flex w-full max-w-full"><div class="min-w-0 flex-1 rounded-l border bg-gray-100 px-2 py-1 text-sm leading-5 text-gray-600 whitespace-normal break-words" x-text="draft.fitemname"></div><button type="button" @click="openDesc(draft)" class="shrink-0 inline-flex items-center border border-l-0 rounded-r px-2 py-1 transition-colors" :class="draft.fdesc ? 'border-emerald-300 bg-emerald-50 text-emerald-600 hover:bg-emerald-100' : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-50'" title="Deskripsi item"><x-heroicon-o-document-text class="h-4 w-4" /></button></div></td>
                        <td class="p-2 align-top"><select id="draftUnitSelect" class="w-full border rounded px-2 py-1 text-sm" x-show="draft.units.length > 1" @keydown.enter.prevent="$refs.draftQty?.focus()"></select><input type="text" x-show="draft.units.length <= 1" class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm" :value="draft.fsatuan || '-'" disabled></td>
                        <td class="p-2"><input type="text" class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm" :value="draft.fprno || ''" disabled placeholder="Ref PR"></td>
                        <td class="p-2 text-right"><input type="number" class="border rounded px-2 py-1 w-20 text-right text-sm" min="0" step="0.01" x-ref="draftQty" x-model.number="draft.fqty" @input="recalc(draft);" @blur="enforcePrQtyRow(draft);" @keydown.enter.prevent="$refs.draftPrice?.focus()"><div class="text-[10px] text-amber-700 font-medium text-right mt-0.5" x-show="draft.frefdtid && formatPrRemainHint(draft)" x-html="formatPrRemainHint(draft)"></div></td>
                        <td class="p-2 text-right"><input type="number" class="border rounded px-2 py-1 w-24 text-right text-sm" min="0" step="0.01" x-ref="draftPrice" x-model.number="draft.fprice" @input="recalc(draft)" @keydown.enter.prevent="$refs.draftDisc?.focus()"></td>
                        <td class="p-2 text-right"><input type="number" class="border rounded px-2 py-1 w-20 text-right text-sm" min="0" max="100" step="0.01" x-ref="draftDisc" x-model.number="draft.fdisc" @input="recalc(draft)" @keydown.enter.prevent="addIfComplete()"></td>
                        <td class="p-2"><input type="text" class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm text-right" :value="fmtCurr(draft.ftotal)" disabled></td>
                        <td class="p-2"><input type="text" class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm text-right" :value="rupiah(itemTotalRp(draft.ftotal))" disabled></td>
                        <td class="p-2 text-center"><button type="button" @click="addIfComplete()" class="px-3 py-1 rounded text-xs bg-emerald-600 text-white hover:bg-emerald-700 whitespace-nowrap">Tambah</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div x-show="showDescModal" x-cloak class="fixed inset-0 z-[95] flex items-center justify-center" x-transition.opacity>
            <div class="absolute inset-0 bg-black/50" @click="closeDesc()"></div>
            <div class="relative bg-white w-[92vw] max-w-lg rounded-2xl shadow-2xl overflow-hidden" x-transition.scale>
                <div class="px-5 py-4 border-b flex items-center">
                    <x-heroicon-o-document-text class="w-6 h-6 text-blue-600 mr-2" />
                    <h3 class="text-lg font-semibold text-gray-800">Isi Deskripsi Item</h3>
                </div>
                <div class="px-5 py-4 space-y-2">
                    <label class="block text-sm text-gray-700">Deskripsi</label>
                    <textarea x-model="descValue" rows="5" class="w-full border rounded px-3 py-2" placeholder="Tulis deskripsi item di sini..."></textarea>
                </div>
                <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
                    <button type="button" @click="closeDesc()" class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">Batal</button>
                    <button type="button" @click="applyDesc()" class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Simpan</button>
                </div>
            </div>
        </div>
        <div x-show="showNoItems" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center" x-transition.opacity>
            <div class="absolute inset-0 bg-black/50" @click="showNoItems=false"></div>
            <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden">
                <div class="px-5 py-4 border-b flex items-center">
                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-500 mr-2" />
                    <h3 class="text-lg font-semibold text-gray-800">Tidak Ada Item</h3>
                </div>
                <div class="px-5 py-4">
                    <p class="text-sm text-gray-700">Anda belum menambahkan item. Silakan isi baris "Detail Item" terlebih dahulu.</p>
                </div>
                <div class="px-5 py-3 border-t flex justify-end">
                    <button type="button" @click="showNoItems=false" class="h-9 px-4 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">OK</button>
                </div>
            </div>
        </div>
    @elseif ($isEdit)
        <div class="overflow-x-auto border rounded">
            <table class="min-w-full text-sm po-detail-table" data-skip-auto-detail-style="true">
                <colgroup>
                    @if ($isEdit && (empty($blockedByTerima) || !$blockedByTerima))
                        <col style="width:2%;"><col style="width:12%;"><col style="width:23%;"><col style="width:7%;"><col style="width:9%;"><col style="width:7%;"><col style="width:8%;"><col style="width:7%;"><col style="width:5%;"><col style="width:7%;"><col style="width:8%;"><col style="width:5%;">
                    @else
                        <col style="width:2%;"><col style="width:12%;"><col style="width:25%;"><col style="width:7%;"><col style="width:10%;"><col style="width:7%;"><col style="width:8%;"><col style="width:7%;"><col style="width:5%;"><col style="width:8%;"><col style="width:9%;">
                    @endif
                </colgroup>
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2 text-left w-10">#</th>
                        <th class="p-2 text-left w-20">Kode Produk</th>
                        <th class="p-2 text-left w-[30rem]">Nama Produk</th>
                        <th class="p-2 text-left w-24">Satuan</th>
                        <th class="p-2 text-left w-24">Ref.PR#</th>
                        <th class="p-2 text-right w-24 whitespace-nowrap">Qty</th>
                        <th class="p-2 text-right w-24 whitespace-nowrap">Qty Terima</th>
                        <th class="p-2 text-right w-24 whitespace-nowrap">@ Harga</th>
                        <th class="p-2 text-right w-20 whitespace-nowrap">Disc. %</th>
                        <th class="p-2 text-right w-24 whitespace-nowrap">Total Harga</th>
                        <th class="p-2 text-right w-20 whitespace-nowrap">Total Harga (Rp)</th>
                        @if ($isEdit && (empty($blockedByTerima) || !$blockedByTerima))
                            <th class="p-2 text-center w-16">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(it, i) in savedItems" :key="it.uid">
                        <tr class="border-t align-top transition-colors" :class="activeRow === it.uid ? 'bg-amber-50' : 'hover:bg-gray-50'">
                            <td class="p-2 text-gray-500" x-text="i + 1"></td>
                            <td class="p-2">
                                @if ($isEdit)
                                    <div class="flex w-40"><input type="text" class="w-32 border rounded-l px-2 py-1 font-mono text-sm min-w-0" x-model.trim="it.fitemcode" @focus="activeRow = it.uid" @blur="activeRow = null" @input="onCodeTypedSaved(it)" @keydown.enter.prevent="focusSavedUnit(it, i)"><button type="button" @click="openBrowseFor('saved', i)" class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50" title="Cari Produk"><x-heroicon-o-magnifying-glass class="w-4 h-4" /></button></div>
                                @else
                                    <span class="font-mono text-sm" x-text="it.fitemcode"></span>
                                @endif
                            </td>
                            <td class="p-2 align-top overflow-visible">
                                <div class="flex w-full max-w-full"><div class="min-w-0 flex-1 rounded-l border bg-gray-100 px-2 py-1 text-sm leading-5 text-gray-600 whitespace-normal break-words" x-text="it.fitemname"></div><button type="button" @click="openDesc(it, false)" class="shrink-0 inline-flex items-center border border-l-0 rounded-r px-2 py-1 transition-colors" :class="it.fdesc ? 'border-emerald-300 bg-emerald-50 text-emerald-600 hover:bg-emerald-100' : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-50'" title="Deskripsi item"><x-heroicon-o-document-text class="h-4 w-4" /></button></div>
                            </td>
                            <td class="p-2 align-top">
                                @if ($isEdit)
                                    <template x-if="it.units.length > 1"><select class="w-full border rounded px-2 py-1 text-sm" :id="'unit_saved_' + i" x-model="it.fsatuan" @focus="activeRow = it.uid" @blur="activeRow = null" @keydown.enter.prevent="focusSavedQty(i)" @change="it.fsatuan = $event.target.value; it.maxqty = calcMaxQty(it);" x-effect="const sel=$el; sel.innerHTML=''; it.units.forEach(u=>{ const opt=document.createElement('option'); opt.value=u; opt.textContent=u; if(u===it.fsatuan) opt.selected=true; sel.appendChild(opt);}); it.maxqty = calcMaxQty(it);"><template x-for="u in it.units" :key="u"><option :value="u" x-text="u"></option></template></select></template>
                                    <input type="text" x-show="it.units.length <= 1" class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm" :value="it.fsatuan || '-'" disabled>
                                @else
                                    <span class="text-sm" x-text="it.fsatuan || '-'"></span>
                                @endif
                            </td>
                            <td class="p-2"><span class="text-sm text-gray-600" x-text="it.fprno || it.frefpr || '-'"></span></td>
                            <td class="p-2 text-right">
                                @if ($isEdit)
                                    <input type="number" class="border rounded px-2 py-1 w-20 text-right text-sm" x-model.number="it.fqty" :id="'qty_saved_' + i" @focus="activeRow = it.uid; $event.target.select()" @blur="activeRow = null; enforcePrQtyRow(it);" @input="recalc(it); calcMaxQty(it);" @change="recalc(it); calcMaxQty(it);" @keydown.enter.prevent="focusSavedPrice(i)"><div class="text-[10px] text-amber-700 font-medium text-right mt-0.5" x-show="it.frefdtid && formatPrRemainHint(it)" x-html="formatPrRemainHint(it)"></div>
                                @else
                                    <span class="text-sm" x-text="formatQtyValue(it.fqty)"></span>
                                @endif
                            </td>
                            <td class="p-2 text-right"><input type="text" class="w-20 border rounded px-2 py-1 bg-gray-100 text-right text-sm text-gray-500" :value="formatQtyValue(it.fqtyterima)" disabled></td>
                            <td class="p-2 text-right">
                                @if ($isEdit)
                                    <input type="number" class="border rounded px-2 py-1 w-24 text-right text-sm" min="0" step="0.01" x-model.number="it.fprice" :id="'price_saved_' + i" @focus="activeRow = it.uid; $event.target.select()" @blur="activeRow = null" @input="recalc(it)" @change="recalc(it)" @keydown.enter.prevent="focusSavedDisc(i)">
                                @else
                                    <span class="text-sm" x-text="fmtCurr(it.fprice)"></span>
                                @endif
                            </td>
                            <td class="p-2 text-right">
                                @if ($isEdit)
                                    <input type="number" class="border rounded px-2 py-1 w-20 text-right text-sm" min="0" max="100" step="0.01" x-model.number="it.fdisc" :id="'disc_saved_' + i" @focus="activeRow = it.uid; $event.target.select()" @blur="activeRow = null" @input="recalc(it)" @change="recalc(it)" @keydown.enter.prevent="focusDraftCode()">
                                @else
                                    <span class="text-sm" x-text="it.fdisc"></span>
                                @endif
                            </td>
                            <td class="p-2"><input type="text" class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm text-right" :value="fmtCurr(it.ftotal)" disabled></td>
                            <td class="p-2"><input type="text" class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm text-right" :value="rupiah(itemTotalRp(it.ftotal))" disabled></td>
                            @if ($isEdit && (empty($blockedByTerima) || !$blockedByTerima))
                                <td class="p-2 text-center"><button type="button" @click="removeSaved(i)" class="px-3 py-1 rounded text-xs bg-red-100 text-red-600 hover:bg-red-200 whitespace-nowrap">Hapus</button></td>
                            @endif
                            @if ($isEdit)
                                <td class="hidden">
                                    <input type="hidden" name="fitemcode[]" :value="it.fitemcode"><input type="hidden" name="fitemname[]" :value="it.fitemname"><input type="hidden" name="fsatuan[]" :value="it.fsatuan"><input type="hidden" name="frefdtno[]" :value="it.frefdtno"><input type="hidden" name="frefdtid[]" :value="it.frefdtid"><input type="hidden" name="fnouref[]" :value="it.fnouref"><input type="hidden" name="fnoacak[]" :value="it.fnoacak"><input type="hidden" name="frefnoacak[]" :value="it.frefnoacak"><input type="hidden" name="frefpr[]" :value="it.frefpr"><input type="hidden" name="fprhid[]" :value="it.fprhid"><input type="hidden" name="fqty[]" :value="it.fqty"><input type="hidden" name="fprice[]" :value="it.fprice"><input type="hidden" name="fdisc[]" :value="it.fdisc"><input type="hidden" name="ftotal[]" :value="it.ftotal"><input type="hidden" name="fdesc[]" :value="it.fdesc"><input type="hidden" name="fketdt[]" :value="it.fketdt">
                                </td>
                            @endif
                        </tr>
                    </template>
                    @if ($isEdit)
                        <tr class="border-t bg-green-50 align-top">
                            <td class="p-2 text-gray-400" x-text="savedItems.length + 1"></td>
                            <td class="p-2"><div class="flex w-40"><input type="text" class="w-32 border rounded-l px-2 py-1 font-mono text-sm min-w-0" x-ref="draftCode" x-model.trim="draft.fitemcode" @input="onCodeTypedRow(draft)" @keydown.enter.prevent="handleEnterOnCode()"><button type="button" @click="openBrowseFor('draft')" class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50" title="Cari Produk"><x-heroicon-o-magnifying-glass class="w-4 h-4" /></button></div></td>
                            <td class="p-2 align-top overflow-visible"><div class="flex w-full max-w-full"><div class="min-w-0 flex-1 rounded-l border bg-gray-100 px-2 py-1 text-sm leading-5 text-gray-600 whitespace-normal break-words" x-text="draft.fitemname"></div><button type="button" @click="openDesc(draft)" class="shrink-0 inline-flex items-center border border-l-0 rounded-r px-2 py-1 transition-colors" :class="draft.fdesc ? 'border-emerald-300 bg-emerald-50 text-emerald-600 hover:bg-emerald-100' : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-50'" title="Deskripsi item"><x-heroicon-o-document-text class="h-4 w-4" /></button></div></td>
                            <td class="p-2 align-top"><select id="draftUnitSelect" class="w-full border rounded px-2 py-1 text-sm" x-show="draft.units.length > 1" @keydown.enter.prevent="$refs.draftQty?.focus()"></select><input type="text" x-show="draft.units.length <= 1" class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm" :value="draft.fsatuan || '-'" disabled></td>
                            <td class="p-2"><input type="text" class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm" :value="draft.fprno || ''" disabled placeholder="Ref PR"></td>
                            <td class="p-2 text-right"><input type="number" class="border rounded px-2 py-1 w-20 text-right text-sm" min="0" step="0.01" x-ref="draftQty" x-model.number="draft.fqty" @input="recalc(draft);" @blur="enforcePrQtyRow(draft);" @keydown.enter.prevent="$refs.draftPrice?.focus()"><div class="text-[10px] text-amber-700 font-medium text-right mt-0.5" x-show="draft.frefdtid && formatPrRemainHint(draft)" x-html="formatPrRemainHint(draft)"></div></td>
                            <td class="p-2 text-right"><input type="number" class="border rounded px-2 py-1 w-24 text-right text-sm" min="0" step="0.01" x-ref="draftPrice" x-model.number="draft.fprice" @input="recalc(draft)" @keydown.enter.prevent="$refs.draftDisc?.focus()"></td>
                            <td class="p-2 text-right"><input type="number" class="border rounded px-2 py-1 w-20 text-right text-sm" min="0" max="100" step="0.01" x-ref="draftDisc" x-model.number="draft.fdisc" @input="recalc(draft)" @keydown.enter.prevent="addIfComplete()"></td>
                            <td class="p-2"><input type="text" class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm text-right" :value="fmtCurr(draft.ftotal)" disabled></td>
                            <td class="p-2"><input type="text" class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm text-right" :value="rupiah(itemTotalRp(draft.ftotal))" disabled></td>
                            <td class="p-2 text-center"><button type="button" @click="addIfComplete()" class="px-3 py-1 rounded text-xs bg-emerald-600 text-white hover:bg-emerald-700 whitespace-nowrap">Tambah</button></td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div x-show="showDescModal" x-cloak class="fixed inset-0 z-[95] flex items-center justify-center" x-transition.opacity>
            <div class="absolute inset-0 bg-black/50" @click="closeDesc()"></div>
            <div class="relative bg-white w-[92vw] max-w-lg rounded-2xl shadow-2xl overflow-hidden" x-transition.scale>
                <div class="px-5 py-4 border-b flex items-center"><x-heroicon-o-document-text class="w-6 h-6 text-blue-600 mr-2" /><h3 class="text-lg font-semibold text-gray-800" x-text="$mode==='view' ? 'Deskripsi Item' : 'Isi Deskripsi Item'"></h3></div>
                <div class="px-5 py-4 space-y-2"><label class="block text-sm text-gray-700">Deskripsi</label><textarea x-model="descValue" rows="5" class="w-full border rounded px-3 py-2" :readonly="$mode==='view'" :class="$mode==='view' ? 'bg-gray-100 cursor-not-allowed text-gray-600' : ''" placeholder="Tulis deskripsi item di sini..."></textarea></div>
                <div class="px-5 py-3 border-t flex items-center justify-end gap-2"><button type="button" @click="closeDesc()" class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">Tutup</button><button type="button" @click="applyDesc()" x-show="$mode!=='view'" class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Simpan</button></div>
            </div>
        </div>
        <input type="hidden" id="itemsCount" :value="savedItems.length">
        @if ($isCreateMode)
            {{-- Add PR + Panel Totals --}}
            <div x-data="prhFormModal()">
                <div class="mt-3 flex justify-between items-start gap-4">
                    <div class="flex justify-start">
                        <button type="button" @click="openModal()" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            Add PR
                        </button>
                    </div>
                    <div class="w-[480px] shrink-0">
                        <div class="rounded-lg border bg-gray-50 p-3 space-y-2 text-sm">
                            <div class="flex items-center justify-between"><span class="text-gray-600">Total Harga</span><span class="font-medium" x-text="fmtCurr(totalHarga)"></span></div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <label class="flex items-center gap-1.5 cursor-pointer select-none"><input type="checkbox" name="fapplyppn" value="1" x-model="includePPN" class="h-4 w-4 text-blue-600 border-gray-300 rounded"><span class="font-bold">PPN</span></label>
                                    <select name="fincludeppn" x-model.number="ppnMode" :disabled="!includePPN" class="w-28 h-10 px-2 text-sm border rounded appearance-none disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed"><option value="0">Exclude</option><option value="1">Include</option></select>
                                    <input type="number" name="ppn_rate" min="0" max="100" step="0.01" x-model.number="ppnRate" :disabled="!includePPN" class="w-16 h-10 px-2 text-sm text-right border rounded [appearance:textfield] disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed">
                                    <span class="text-gray-500">%</span><span class="flex-1"></span><span class="font-medium" x-text="fmtCurr(ppnNominal)"></span>
                                </div>
                            </div>
                            <div class="border-t"></div>
                            <div class="flex items-center justify-between"><span class="font-semibold text-gray-800">Grand Total <span class="text-xs font-normal text-gray-500" x-text="selectedCurrCode ? '(' + selectedCurrCode + ')' : ''"></span></span><span class="font-bold text-blue-700" x-text="fmtCurr(grandTotal)"></span></div>
                            <div class="flex items-center justify-between"><span class="font-semibold text-gray-800">Grand Total (RP)</span><span class="font-bold text-emerald-700" x-text="rupiah(grandTotalRp)"></span></div>
                        </div>
                        <input type="hidden" name="famountponet" :value="totalHarga"><input type="hidden" name="famountpopajak" :value="ppnNominal"><input type="hidden" name="famountpo" :value="grandTotal"><input type="hidden" name="famountpo_rp" :value="grandTotalRp">
                    </div>
                </div>
                <div x-show="show" x-transition.opacity class="fixed inset-0 z-40 bg-black/50" @keydown.escape.window="closeModal()"></div>
                <div x-show="show" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-8" aria-modal="true" role="dialog">
                    <div class="relative w-full max-w-5xl rounded-xl bg-white shadow-2xl flex flex-col" style="height: 600px;">
                        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white"><h3 class="text-xl font-bold text-gray-800">Pilih Purchase Request (PR)</h3><button type="button" @click="closeModal()" class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 font-medium text-gray-700 text-sm">Tutup</button></div>
                        <div class="flex-1 overflow-y-auto p-6" style="min-height: 0;"><table id="prTable" class="min-w-full text-sm display nowrap stripe hover" style="width:100%"><thead class="sticky top-0 z-10"><tr class="bg-gray-50 border-b-2 border-gray-200"><th class="p-3 text-left font-semibold text-gray-700">PR No</th><th class="p-3 text-left font-semibold text-gray-700">Supplier</th><th class="p-3 text-left font-semibold text-gray-700">Tanggal</th><th class="p-3 text-center font-semibold text-gray-700">Aksi</th></tr></thead><tbody></tbody></table></div>
                        <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50"></div>
                    </div>
                </div>
                <div x-show="showDupModal" x-cloak x-transition.opacity class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/40" @click="closeDupModal()"></div>
                    <div class="relative bg-white rounded-xl shadow-xl max-w-2xl w-full p-6"><h3 class="text-lg font-semibold mb-4">Peringatan Duplikasi</h3><p class="mb-4">Ditemukan <strong x-text="dupCount"></strong> item yang sudah ada. Hanya item unik yang akan ditambahkan.</p><div class="mb-4 max-h-48 overflow-auto border rounded p-2 bg-gray-50" x-show="dupSample.length > 0"><p class="text-sm font-medium mb-2">Contoh item duplikat:</p><template x-for="(item, idx) in dupSample" :key="idx"><div class="text-xs py-1">• <span x-text="item.fitemcode"></span></div></template></div><div class="flex justify-end gap-2"><button type="button" @click="closeDupModal()" class="rounded bg-gray-200 px-4 py-2 text-sm font-medium hover:bg-gray-300">Batal</button><button type="button" @click="confirmAddUniques()" class="rounded bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Tambahkan Item Unik</button></div></div>
                </div>
            </div>
            <div x-show="showNoItems" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center" x-transition.opacity>
                <div class="absolute inset-0 bg-black/50" @click="showNoItems=false"></div>
                <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden"><div class="px-5 py-4 border-b flex items-center"><x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-500 mr-2" /><h3 class="text-lg font-semibold text-gray-800">Tidak Ada Item</h3></div><div class="px-5 py-4"><p class="text-sm text-gray-700">Anda belum menambahkan item. Silakan isi baris "Detail Item" terlebih dahulu.</p></div><div class="px-5 py-3 border-t flex justify-end"><button type="button" @click="showNoItems=false" class="h-9 px-4 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">OK</button></div></div>
            </div>
            <div x-show="showNoSupplier" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center" x-transition.opacity>
                <div class="absolute inset-0 bg-black/50" @click="showNoSupplier=false"></div>
                <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden"><div class="px-5 py-4 border-b flex items-center"><x-heroicon-o-exclamation-triangle class="w-6 h-6 text-amber-500 mr-2" /><h3 class="text-lg font-semibold text-gray-800">Supplier Belum Dipilih</h3></div><div class="px-5 py-4"><p class="text-sm text-gray-700">Silakan pilih <strong>Supplier</strong> terlebih dahulu sebelum menambahkan item.</p></div><div class="px-5 py-3 border-t flex items-center justify-end gap-2"><button type="button" @click="showNoSupplier=false" class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">Tutup</button><button type="button" @click="showNoSupplier=false; window.dispatchEvent(new CustomEvent('supplier-browse-open'))" class="h-9 px-4 rounded-lg bg-amber-500 text-white text-sm font-medium hover:bg-amber-600">Pilih Supplier</button></div></div>
            </div>
            <div x-show="showDupItemModal" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center" x-transition.opacity>
                <div class="absolute inset-0 bg-black/50" @click="showDupItemModal=false"></div>
                <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden"><div class="px-5 py-4 border-b flex items-center"><x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-500 mr-2" /><h3 class="text-lg font-semibold text-gray-800">Produk Sudah Ada</h3></div><div class="px-5 py-4 space-y-1"><p class="text-sm text-gray-700">Produk <strong x-text="dupItemName"></strong><template x-if="dupItemSatuan"><span> (<span x-text="dupItemSatuan"></span>)</span></template> sudah ada di daftar item.</p><p class="text-sm text-gray-500">Satu produk dengan satuan yang sama hanya boleh ditambahkan satu kali.</p></div><div class="px-5 py-3 border-t flex justify-end"><button type="button" @click="showDupItemModal=false" class="h-9 px-4 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700">OK</button></div></div>
            </div>
        @else
            <div class="overflow-x-auto border rounded">
                <table class="min-w-full text-sm po-detail-table" data-skip-auto-detail-style="true">
                    <colgroup><col style="width:2%;"><col style="width:13%;"><col style="width:24%;"><col style="width:7%;"><col style="width:10%;"><col style="width:7%;"><col style="width:8%;"><col style="width:7%;"><col style="width:5%;"><col style="width:8%;"><col style="width:9%;"></colgroup>
                    <thead class="bg-gray-100"><tr><th class="p-2 text-left w-10">#</th><th class="p-2 text-left w-32">Kode Produk</th><th class="p-2 text-left w-[26rem]">Nama Produk</th><th class="p-2 text-left w-24">Satuan</th><th class="p-2 text-left w-24">Ref.PR#</th><th class="p-2 text-right w-24 whitespace-nowrap">Qty</th><th class="p-2 text-right w-28 whitespace-nowrap">Qty Terima</th><th class="p-2 text-right w-24 whitespace-nowrap">@ Harga</th><th class="p-2 text-right w-20 whitespace-nowrap">Disc. %</th><th class="p-2 text-right w-24 whitespace-nowrap">Total Harga</th><th class="p-2 text-right w-20 whitespace-nowrap">Total Harga (Rp)</th></tr></thead>
                    <tbody>
                        <template x-for="(it, i) in savedItems" :key="it.uid">
                            <tr class="border-t align-top hover:bg-gray-50">
                                <td class="p-2 text-gray-500" x-text="i + 1"></td>
                                <td class="p-2 font-mono text-sm" x-text="it.fitemcode"></td>
                                <td class="p-2"><div class="flex w-full max-w-full"><div class="min-w-0 flex-1 rounded-l border bg-gray-100 px-2 py-1 text-sm leading-5 text-gray-600 whitespace-normal break-words" x-text="it.fitemname"></div><button type="button" @click="openDesc(it)" class="shrink-0 inline-flex items-center border border-l-0 rounded-r px-2 py-1 transition-colors" :class="it.fdesc ? 'border-emerald-300 bg-emerald-50 text-emerald-600 hover:bg-emerald-100' : 'bg-white text-gray-500 hover:bg-gray-50'" title="Deskripsi item"><x-heroicon-o-document-text class="h-4 w-4" /></button></div></td>
                                <td class="p-2 text-sm" x-text="it.fsatuan || '-'"></td>
                                <td class="p-2 text-sm text-gray-600" x-text="it.fprno || it.frefdtno || '-'"></td>
                                <td class="p-2 text-right text-sm"><div x-text="formatQtyValue(it.fqty)"></div></td>
                                <td class="p-2 text-right text-sm"><div x-text="formatQtyValue(it.fqtyterima ?? 0)"></div></td>
                                <td class="p-2 text-right text-sm" x-text="fmtCurr(it.fprice)"></td>
                                <td class="p-2 text-right text-sm" x-text="it.fdisc"></td>
                                <td class="p-2"><input type="text" class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm text-right" :value="fmtCurr(it.ftotal)" disabled></td>
                                <td class="p-2"><input type="text" class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm text-right" :value="rupiah(itemTotalRp(it.ftotal))" disabled></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="mt-3 flex justify-end"><div class="w-[480px] shrink-0"><div class="rounded-lg border bg-gray-50 p-3 space-y-2 text-sm"><div class="flex items-center justify-between"><span class="text-gray-600">Total Harga</span><span class="font-medium" x-text="fmtCurr(totalHarga)"></span></div><div class="flex items-center gap-2"><label class="flex items-center gap-1.5 select-none"><input type="checkbox" disabled x-model="includePPN" class="h-4 w-4 text-blue-600 border-gray-300 rounded cursor-not-allowed"><span class="font-bold">PPN</span></label><input type="text" disabled :value="ppnMode === 1 ? 'Include' : 'Exclude'" class="w-28 h-8 px-2 text-xs border rounded bg-gray-100 cursor-not-allowed text-gray-600"><input type="number" disabled x-model.number="ppnRate" class="w-16 h-8 px-2 text-xs text-right border rounded bg-gray-100 cursor-not-allowed text-gray-600"><span class="text-xs text-gray-500">%</span><span class="flex-1"></span><span class="font-medium text-xs" x-text="fmtCurr(ppnNominal)"></span></div><div class="border-t"></div><div class="flex items-center justify-between"><span class="font-semibold text-gray-800">Grand Total <span class="text-xs font-normal text-gray-500" x-text="selectedCurrCode ? '(' + selectedCurrCode + ')' : ''"></span></span><span class="font-bold text-blue-700" x-text="fmtCurr(grandTotal)"></span></div><div class="flex items-center justify-between bg-blue-50 rounded px-2 py-1"><span class="font-semibold text-gray-800">Grand Total (RP)</span><span class="font-bold text-emerald-700" x-text="rupiah(grandTotalRp)"></span></div></div></div></div>
        @endif
</div>
