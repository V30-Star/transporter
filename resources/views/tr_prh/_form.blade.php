@php
    $isDeleteMode = $isDeleteMode ?? false;
    $isReadOnly = $isReadOnly ?? false;
    $formAction = $formAction ?? '#';
    $formMethod = $formMethod ?? 'POST';
    $tr_prh = $tr_prh ?? new stdClass();
    $trPrhGet = fn(string $key, $default = '') => data_get($tr_prh, $key, $default);
    $formatDateValue = function ($value, $default = '') {
        if (empty($value)) {
            return $default;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return $default;
        }
    };
    $fcabang = $fcabang ?? '';
    $fbranchcode = $fbranchcode ?? '';
    $suppliers = $suppliers ?? collect();
    $products = $products ?? collect();
    $productMap = $productMap ?? [];
    $filterSupplierId = $filterSupplierId ?? '';
    $allowDocumentNoEdit = $allowDocumentNoEdit ?? !($isReadOnly || $isDeleteMode);
    $detailMode = $detailMode ?? ($isDeleteMode ? 'delete' : ($isReadOnly ? 'view' : 'create'));
    $showNoItemsModal = $showNoItemsModal ?? ($detailMode === 'create' || $detailMode === 'edit');
@endphp

    <div class="tr-prh-form">
        {{-- Shared form shell for Permintaan Pembelian --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        <div class="lg:col-span-4">
            <label class="block text-sm font-medium">Cabang</label>
            <input type="text" class="w-full border rounded px-3 py-2 bg-gray-200 cursor-not-allowed"
                value="{{ $fcabang }}" disabled>
            <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
        </div>

        <div class="lg:col-span-4" x-data="{ autoCode: true }">
            <label class="block text-sm font-medium mb-1">PR#</label>
            <div class="flex items-center gap-3">
                @if (!$allowDocumentNoEdit || $isReadOnly || $isDeleteMode)
                    <input type="text" class="w-full border rounded px-3 py-2 bg-gray-200 cursor-not-allowed"
                        value="{{ old('fprno', $trPrhGet('fprno', '')) }}" disabled>
                @else
                    <input type="text" name="fprno" class="w-full border rounded px-3 py-2"
                        :disabled="autoCode" :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'">
                    <label class="inline-flex items-center select-none">
                        <input type="checkbox" x-model="autoCode" checked>
                        <span class="ml-2 text-sm text-gray-700">Auto</span>
                    </label>
                @endif
            </div>
        </div>

        <div class="lg:col-span-4">
            <label class="block text-sm font-medium mb-1">Supplier</label>
            <div class="flex">
                <div class="relative flex-1" for="modal_filter_supplier_id">
                    <select id="modal_filter_supplier_id" name="filter_supplier_id"
                        class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
                        disabled>
                        <option value=""></option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->fsuppliercode }}"
                                {{ $filterSupplierId == $supplier->fsuppliercode ? 'selected' : '' }}>
                                {{ $supplier->fsuppliername }} ({{ $supplier->fsuppliercode }})
                            </option>
                        @endforeach
                    </select>
                    <div class="absolute inset-0" role="button" aria-label="Browse supplier"
                        @click="window.dispatchEvent(new CustomEvent('supplier-browse-open'))"></div>
                </div>
                @if ($isReadOnly || $isDeleteMode)
                    <input type="hidden" name="fsupplier" id="supplierCodeHidden" value="{{ $trPrhGet('fsupplier', old('fsupplier')) }}">
                @else
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
                @endif
            </div>
        </div>

        <div class="lg:col-span-4">
            <label class="block text-sm font-medium">Tanggal</label>
            <input type="date" name="fprdate" value="{{ old('fprdate', $formatDateValue($trPrhGet('fprdate'), date('Y-m-d'))) }}"
                class="w-full border rounded px-3 py-2 @error('fprdate') border-red-500 @enderror" {{ $isReadOnly || $isDeleteMode ? 'disabled' : '' }}>
        </div>

        <div class="lg:col-span-4">
            <label class="block text-sm font-medium">Tanggal Dibutuhkan</label>
            <input type="date" name="fneeddate" value="{{ old('fneeddate', $formatDateValue($trPrhGet('fneeddate'), '')) }}"
                class="w-full border rounded px-3 py-2 @error('fneeddate') border-red-500 @enderror" {{ $isReadOnly || $isDeleteMode ? 'disabled' : '' }}>
        </div>

        <div class="lg:col-span-4">
            <label class="block text-sm font-medium">Tanggal Paling Lambat</label>
            <input type="date" name="fduedate" value="{{ old('fduedate', $formatDateValue($trPrhGet('fduedate'), '')) }}"
                class="w-full border rounded px-3 py-2 @error('fduedate') border-red-500 @enderror" {{ $isReadOnly || $isDeleteMode ? 'disabled' : '' }}>
        </div>

        <div class="lg:col-span-12">
            <label class="block text-sm font-medium">Keterangan</label>
            <textarea name="fket" rows="3" maxlength="300"
                class="w-full border rounded px-3 py-2 @error('fket') border-red-500 @enderror"
                placeholder="Tulis keterangan tambahan di sini..." {{ $isReadOnly || $isDeleteMode ? 'disabled' : '' }}>{{ old('fket', $trPrhGet('fket', '')) }}</textarea>
        </div>

        <div class="mt-6">
            @if ($detailMode === 'delete')
                <div x-data="itemsTable()" x-init="init()" class="mt-6 space-y-2">
                    <h3 class="text-base font-semibold text-gray-800">Detail Item</h3>
                    <div class="overflow-auto border rounded">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="p-2 text-left w-10">#</th>
                                    <th class="p-2 text-left w-44">Kode Produk</th>
                                    <th class="p-2 text-left" style="width: 20rem; min-width: 20rem;">Nama Produk</th>
                                    <th class="p-2 text-left w-40">Satuan</th>
                                    <th class="p-2 text-right w-28">Qty</th>
                                    <th class="p-2 text-right w-28">Qty PO</th>
                                    <th class="p-2 text-left w-56">Ket Item</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(it, i) in savedItems" :key="it.uid">
                                    <tr class="border-t align-top">
                                        <td class="p-2" x-text="i + 1"></td>
                                        <td class="p-2 font-mono" x-text="it.fitemcode"></td>
                                        <td class="p-2 text-gray-800" style="width: 20rem; min-width: 20rem;">
                                            <div class="desc-inline-field">
                                                <div class="desc-inline-field__text rounded-l border bg-gray-100 px-2 py-1 text-sm leading-5 text-gray-600 whitespace-normal break-words"
                                                    x-text="it.fitemname"></div>
                                                <button type="button" @click="openDesc('saved', i, true)"
                                                    class="desc-inline-field__button inline-flex items-center border border-l-0 rounded-r px-2 py-1 transition-colors"
                                                    :class="descButtonClass(it.fdesc)"
                                                    title="Deskripsi">
                                                    <x-heroicon-o-document-text class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                        <td class="p-2" x-text="it.fsatuan"></td>
                                        <td class="p-2 text-right">
                                            <div x-text="formatQtyValue(it.fqty)"></div>
                                        </td>
                                        <td class="p-2 text-right" x-text="formatQtyValue(it.fqtypo)"></td>
                                        <td class="p-2" x-text="it.fketdt || '-'"></td>
                                        <td class="hidden">
                                            <input type="hidden" name="fitemcode[]" x-model="it.fitemcode">
                                            <input type="hidden" name="fitemname[]" x-model="it.fitemname">
                                            <input type="hidden" name="fsatuan[]" x-model="it.fsatuan">
                                            <input type="hidden" name="fqty[]" x-model="it.fqty">
                                            <input type="hidden" name="fqtypo[]" x-model="it.fqtypo">
                                            <input type="hidden" name="fprdid[]" :value="it.fprdid ?? ''">
                                            <input type="hidden" name="fprdid[]" x-model="it.fprdid">
                                            <input type="hidden" name="fdesc[]" x-model="it.fdesc">
                                            <input type="hidden" name="fketdt[]" x-model="it.fketdt">
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            @elseif ($detailMode === 'view')
                <div x-data="itemsTable()" x-init="init()" class="mt-6 space-y-2">
                    <h3 class="text-base font-semibold text-gray-800">Detail Item</h3>
                    <div class="overflow-auto border rounded">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="p-2 text-left w-10">#</th>
                                    <th class="p-2 text-left w-44">Kode Produk</th>
                                    <th class="p-2 text-left" style="width: 20rem; min-width: 20rem;">Nama Produk</th>
                                    <th class="p-2 text-left w-40">Satuan</th>
                                    <th class="p-2 text-right w-28">Qty</th>
                                    <th class="p-2 text-right w-28">Qty PO</th>
                                    <th class="p-2 text-left w-56">Ket Item</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(it, i) in savedItems" :key="it.uid">
                                    <tr class="border-t align-top">
                                        <td class="p-2" x-text="i + 1"></td>
                                        <td class="p-2 font-mono" x-text="it.fitemcode"></td>
                                        <td class="p-2 text-gray-800" style="width: 20rem; min-width: 20rem;">
                                            <div class="desc-inline-field">
                                                <div class="desc-inline-field__text rounded-l border bg-gray-100 px-2 py-1 text-sm leading-5 text-gray-600 whitespace-normal break-words"
                                                    x-text="it.fitemname"></div>
                                                <button type="button" @click="openDesc('saved', i, true)"
                                                    class="desc-inline-field__button inline-flex items-center border border-l-0 rounded-r px-2 py-1 transition-colors"
                                                    :class="descButtonClass(it.fdesc)"
                                                    title="Deskripsi">
                                                    <x-heroicon-o-document-text class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                        <td class="p-2" x-text="it.fsatuan"></td>
                                        <td class="p-2 text-right">
                                            <div x-text="formatQtyValue(it.fqty)"></div>
                                        </td>
                                        <td class="p-2 text-right" x-text="formatQtyValue(it.fqtypo)"></td>
                                        <td class="p-2" x-text="it.fketdt || '-'"></td>
                                        <td class="hidden">
                                            <input type="hidden" name="fitemcode[]" x-model="it.fitemcode">
                                            <input type="hidden" name="fitemname[]" x-model="it.fitemname">
                                            <input type="hidden" name="fsatuan[]" x-model="it.fsatuan">
                                            <input type="hidden" name="fqty[]" x-model="it.fqty">
                                            <input type="hidden" name="fqtypo[]" x-model="it.fqtypo">
                                            <input type="hidden" name="fprdid[]" :value="it.fprdid ?? ''">
                                            <input type="hidden" name="fprdid[]" x-model="it.fprdid">
                                            <input type="hidden" name="fdesc[]" x-model="it.fdesc">
                                            <input type="hidden" name="fketdt[]" x-model="it.fketdt">
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="editingIndex !== null" class="border-t bg-amber-50 align-top" x-cloak>
                                    <td class="p-2" x-text="(editingIndex ?? 0) + 1"></td>
                                    <td class="p-2">
                                        <div class="flex">
                                            <input type="text" class="flex-1 border rounded-l px-2 py-1 font-mono"
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
                                    <td class="p-2" style="width: 20rem; min-width: 20rem;">
                                        <div class="desc-inline-field">
                                            <div class="desc-inline-field__text rounded-l border bg-gray-100 px-2 py-1 text-sm leading-5 text-gray-600 whitespace-normal break-words"
                                                x-text="editRow.fitemname"></div>
                                            <button type="button" @click="openDesc('edit', null)"
                                                class="desc-inline-field__button inline-flex items-center border border-l-0 rounded-r px-2 py-1 transition-colors"
                                                :class="descButtonClass(editRow.fdesc)"
                                                title="Deskripsi">
                                                <x-heroicon-o-document-text class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                    <td class="p-2">
                                        <template x-if="editRow.units.length > 1">
                                            <select class="w-full border rounded px-2 py-1" x-ref="editUnit"
                                                x-model="editRow.fsatuan" @keydown.enter.prevent="$refs.editQty?.focus()">
                                                <template x-for="u in editRow.units" :key="u">
                                                    <option :value="u" x-text="u"></option>
                                                </template>
                                            </select>
                                        </template>
                                        <template x-if="editRow.units.length <= 1">
                                            <input type="text" class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                :value="editRow.fsatuan || '-'" disabled>
                                        </template>
                                    </td>
                                    <td class="p-2 text-right">
                                        <input type="number" class="border rounded px-2 py-1 w-24 text-right"
                                            min="1" step="0.01" x-model.number="editRow.fqty" x-ref="editQty"
                                            @focus="$event.target.select()" @keydown.enter.prevent="$refs.editKet?.focus()">
                                        <div class="text-xs text-gray-400 mt-0.5 flex justify-between items-center" x-show="editRow.fitemcode">
                                            <div>(<span x-text="productMeta(editRow.fitemcode).stock"></span>) in stock</div>
                                        </div>
                                    </td>
                                    <td class="p-2 text-right" x-text="it.fqtypo > 0 ? formatQtyValue(it.fqtypo) : '-'"></td>
                                    <td class="p-2">
                                        <input type="text" class="border rounded px-2 py-1 w-full"
                                            x-model="editRow.fketdt" x-ref="editKet"
                                            @keydown.enter.prevent="applyEdit()">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
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
                                    class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">Batal</button>
                                <button type="button" @click="applyDesc()"
                                    class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Simpan</button>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="itemsCount" :value="savedItems.length">
                </div>
            @else
                <div x-data="itemsTable()" x-init="init()" class="mt-6 space-y-2">
                    <h3 class="text-base font-semibold text-gray-800">Detail Item</h3>
                    <div class="overflow-auto border rounded">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="p-2 text-left w-10">#</th>
                                    <th class="p-2 text-left w-48">Kode Produk</th>
                                    <th class="p-2 text-left" style="width: 20rem; min-width: 20rem;">Nama Produk</th>
                                    <th class="p-2 text-left w-36">Satuan</th>
                                    <th class="p-2 text-right w-24">Qty</th>
                                    <th class="p-2 text-right w-24">Qty PO</th>
                                    <th class="p-2 text-left w-48">Ket Item</th>
                                    <th class="p-2 text-center w-20">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(it, i) in savedItems" :key="it.uid">
                                    <tr class="border-t align-top transition-colors"
                                        :class="activeRow === it.uid ? 'bg-amber-50' : 'hover:bg-gray-50'">
                                        <td class="p-2 text-gray-400" x-text="i + 1"></td>
                                        <td class="p-2">
                                            <div class="flex">
                                                <input type="text"
                                                    class="flex-1 border rounded-l px-2 py-1 font-mono text-sm min-w-0"
                                                    x-model.trim="it.fitemcode" @input="onCodeTypedSaved(it)"
                                                    @focus="activeRow = it.uid" @blur="activeRow = null"
                                                    :disabled="blockedByPO">
                                                <button type="button" @click="openBrowseFor('saved', i)"
                                                    class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50"
                                                    :disabled="blockedByPO">
                                                    <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                        <td class="p-2" style="width: 20rem; min-width: 20rem;">
                                            <div class="desc-inline-field">
                                                <div class="desc-inline-field__text rounded-l border bg-gray-100 px-2 py-1 text-sm leading-5 text-gray-600 whitespace-normal break-words"
                                                    x-text="it.fitemname"></div>
                                                <button type="button" @click="openDesc('saved', i)"
                                                    class="desc-inline-field__button inline-flex items-center border border-l-0 rounded-r px-2 py-1 transition-colors"
                                                    :class="descButtonClass(it.fdesc)" :disabled="blockedByPO"
                                                    :title="blockedByPO ? 'Deskripsi' : 'Deskripsi'">
                                                    <x-heroicon-o-document-text class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                        <td class="p-2">
                                            <template x-if="(it.units?.length || 0) > 1">
                                                <select class="w-full border rounded px-2 py-1 text-sm" :id="'unit_saved_' + i"
                                                    x-effect="$nextTick(() => { const el = document.getElementById('unit_saved_' + i); if (el) el.value = it.fsatuan; })"
                                                    @change="it.fsatuan = $event.target.value" :disabled="blockedByPO"
                                                    @focus="activeRow = it.uid" @blur="activeRow = null">
                                                    <template x-for="u in it.units" :key="u">
                                                        <option :value="u" x-text="u"></option>
                                                    </template>
                                                </select>
                                            </template>
                                            <template x-if="(it.units?.length || 0) <= 1">
                                                <input type="text" class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                                    :value="it.fsatuan || '-'" disabled>
                                            </template>
                                        </td>
                                        <td class="p-2 text-right">
                                            <input type="number" class="w-full border rounded px-2 py-1 text-right"
                                                x-model.number="it.fqty" min="1" :disabled="blockedByPO"
                                                @focus="activeRow = it.uid; $event.target.select()"
                                                @blur="activeRow = null">
                                        </td>
                                        <td class="p-2 text-right">
                                            <input type="text" class="w-full border rounded px-2 py-1 text-right bg-gray-100 text-gray-500"
                                                :value="formatQtyValue(it.fqtypo)" disabled>
                                        </td>
                                        <td class="p-2">
                                            <input type="text" class="w-full border rounded px-2 py-1" maxlength="50"
                                                x-model="it.fketdt" :disabled="blockedByPO" @focus="activeRow = it.uid"
                                                @blur="activeRow = null">
                                        </td>
                                        <td class="p-2 text-center">
                                            <button type="button" @click="removeSaved(i)"
                                                class="px-3 py-1 rounded text-xs bg-red-100 text-red-600 hover:bg-red-200 whitespace-nowrap"
                                                :disabled="blockedByPO">
                                                Hapus
                                            </button>
                                        </td>
                                        <td class="hidden">
                                            <input type="hidden" name="fprdid[]" :value="it.fprdid">
                                            <input type="hidden" name="fitemcode[]" :value="it.fitemcode">
                                            <input type="hidden" name="fitemname[]" :value="it.fitemname">
                                            <input type="hidden" name="fnoacak[]" :value="it.fnoacak">
                                            <input type="hidden" name="fsatuan[]" :value="it.fsatuan">
                                            <input type="hidden" name="fqty[]" :value="it.fqty">
                                            <input type="hidden" name="fqtypo[]" :value="it.fqtypo">
                                            <input type="hidden" name="fdesc[]" :value="it.fdesc">
                                            <input type="hidden" name="fketdt[]" :value="it.fketdt">
                                        </td>
                                    </tr>
                                </template>

                                <tr class="border-t bg-green-50 align-top" x-show="!blockedByPO">
                                    <td class="p-2 text-gray-400" x-text="savedItems.length + 1"></td>
                                    <td class="p-2">
                                        <div class="flex">
                                            <input type="text"
                                                class="flex-1 border rounded-l px-2 py-1 font-mono text-sm min-w-0"
                                                x-ref="draftCode" x-model.trim="draft.fitemcode"
                                                @input="onCodeTypedDraft()"
                                                @keydown.enter.prevent="handleEnterOnDraftCode()">
                                            <button type="button" @click="openBrowseFor('draft')"
                                                class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50">
                                                <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                    <td class="p-2" style="width: 20rem; min-width: 20rem;">
                                        <div class="desc-inline-field">
                                            <div class="desc-inline-field__text rounded-l border bg-gray-100 px-2 py-1 text-sm leading-5 text-gray-600 whitespace-normal break-words"
                                                x-text="draft.fitemname"></div>
                                            <button type="button" @click="openDesc('draft')"
                                                class="desc-inline-field__button inline-flex items-center border border-l-0 rounded-r px-2 py-1 transition-colors"
                                                :class="descButtonClass(draft.fdesc)" title="Deskripsi">
                                                <x-heroicon-o-document-text class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                    <td class="p-2">
                                        <template x-if="draft.units.length > 1">
                                            <select class="w-full border rounded px-2 py-1" x-model="draft.fsatuan"
                                                x-ref="draftUnit">
                                                <template x-for="u in draft.units" :key="u">
                                                    <option :value="u" x-text="u"></option>
                                                </template>
                                            </select>
                                        </template>
                                        <template x-if="draft.units.length <= 1">
                                            <input type="text" class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                :value="draft.fsatuan || '-'" disabled>
                                        </template>
                                    </td>
                                    <td class="p-2">
                                        <input type="number" class="w-full border rounded px-2 py-1 text-right"
                                            x-model.number="draft.fqty" min="1" x-ref="draftQty"
                                            @keydown.enter.prevent="addIfComplete()">
                                    </td>
                                    <td class="p-2 text-right">-</td>
                                    <td class="p-2">
                                        <input type="text" class="w-full border rounded px-2 py-1" maxlength="50"
                                            x-model="draft.fketdt" @keydown.enter.prevent="addIfComplete()">
                                    </td>
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
                    <input type="hidden" id="itemsCount" :value="savedItems.length">
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
                                    placeholder="Tulis deskripsi item di sini..." :readonly="blockedByPO"></textarea>
                            </div>
                            <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
                                <button type="button" @click="closeDesc()"
                                    class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">Tutup</button>
                                <button type="button" @click="applyDesc()" x-show="!blockedByPO"
                                    class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Simpan</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        @if ($showNoItemsModal)
            <div x-show="showNoItems" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center"
                x-transition.opacity>
                <div class="absolute inset-0 bg-black/50" @click="showNoItems=false"></div>
                <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden"
                    x-transition.scale>
                    <div class="px-5 py-4 border-b flex items-center">
                        <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-500 mr-2" />
                        <h3 class="text-lg font-semibold text-gray-800">Tidak Ada Item</h3>
                    </div>
                    <div class="px-5 py-4">
                        <p class="text-sm text-gray-700">
                            Anda belum menambahkan item apa pun pada tabel. Silakan isi baris "Detail Item" terlebih
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
        @endif
    </div>
</div>
