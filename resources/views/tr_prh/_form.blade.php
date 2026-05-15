@php
    $routeName = request()->route()?->getName();
@endphp

@if ($routeName === 'tr_prh.create')
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

        input[type=number] {
            -moz-appearance: textfield;
        }

        .item-row-active {
            background-color: #f0fdf4;
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
    <div x-data="{ open: true }">
        <div class="bg-white rounded shadow p-6 md:p-8 max-w-[96rem] mx-auto">
            <form action="{{ route('tr_prh.store') }}" method="POST" class="mt-6" x-data="{ showNoItems: false }"
                @submit.prevent="
                    const n = Number(document.getElementById('itemsCount')?.value || 0);
                    if (n < 1) { showNoItems = true } else { $el.submit() }
                ">
                @csrf
                                @php
                    $__embeddedFormData = [
                    'isReadOnly' => false,
                    'detailMode' => 'create',
                    'tr_prh' => $tr_prh ?? null,
                    'fcabang' => $fcabang,
                    'fbranchcode' => $fbranchcode,
                    'suppliers' => $suppliers,
                    'products' => $products,
                    'productMap' => $productMap,
                    'filterSupplierId' => $filterSupplierId ?? '',
                ];
                    extract($__embeddedFormData, EXTR_SKIP);
                @endphp
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

                        <div class="lg:col-span-12 mt-6">
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

                {{-- MODAL SUPPLIER --}}
                <div x-data="supplierBrowser()" x-init="init()" x-show="open" x-cloak x-transition.opacity
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
                                class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                                Tutup
                            </button>
                        </div>
                        <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                            <div id="supplierTableControls"></div>
                        </div>
                        <div class="flex-1 overflow-y-auto px-6" style="min-height: 0;">
                            <div class="bg-white">
                                <table id="supplierBrowseTable" class="min-w-full text-sm display nowrap stripe hover"
                                    style="width:100%">
                                    <thead class="sticky top-0 z-10">
                                        <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                            <th
                                                class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                Kode</th>
                                            <th
                                                class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                Nama Supplier</th>
                                            <th
                                                class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                Alamat</th>
                                            <th
                                                class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                Telepon</th>
                                            <th
                                                class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
                            <div id="supplierTablePagination"></div>
                        </div>
                    </div>
                </div>

                {{-- MODAL PRODUK --}}
                <div x-data="productBrowser()" x-init="init()" x-show="open" x-cloak x-transition.opacity
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
                                class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                                Tutup
                            </button>
                        </div>
                        <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                            <div id="productTableControls"></div>
                        </div>
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
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
                            <div id="productTablePagination"></div>
                        </div>
                    </div>
                </div>

                @php
                    $canApproval = in_array('approvePR', explode(',', session('user_restricted_permissions', '')));
                @endphp

                {{-- APPROVAL & ACTIONS --}}
                <div class="md:col-span-2 flex justify-center items-center space-x-2 mt-6">
                    @if ($canApproval)
                        <label class="block text-sm font-medium">Approve</label>
                        <input type="hidden" name="fapproval" value="0">
                        <label class="switch">
                            <input type="checkbox" name="fapproval" id="approvalToggle" value="1"
                                {{ old('fapproval', session('fapproval') ? 1 : 0) ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    @endif
                </div>

                <div class="mt-8 flex justify-center gap-4">
                    <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                        <x-heroicon-o-check class="w-5 h-5 mr-2" /> Simpan
                    </button>
                    <button type="button" @click="window.location.href='{{ route('tr_prh.index') }}'"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" /> Keluar
                    </button>
                </div>
            </form>
        </div>
    </div>


@push('styles')
    <style>
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

        div#productTable_length,
        .dataTables_wrapper #productTable_length,
        .dataTables_wrapper .dataTables_length {
            min-width: 250px !important;
        }

        div#productTable_length label,
        .dataTables_wrapper #productTable_length label,
        .dataTables_wrapper .dataTables_length label {
            font-size: 14px !important;
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
        }

        div#supplierTable_length select,
        .dataTables_wrapper #supplierTable_length select,
        table#supplierBrowseTable+.dataTables_wrapper .dataTables_length select {
            min-width: 140px !important;
            width: auto !important;
            padding: 8px 45px 8px 16px !important;
            font-size: 14px !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
        }

        div#supplierTable_length,
        .dataTables_wrapper #supplierTable_length,
        .dataTables_wrapper .dataTables_length {
            min-width: 250px !important;
        }

        div#supplierTable_length label,
        .dataTables_wrapper #supplierTable_length label,
        .dataTables_wrapper .dataTables_length label {
            font-size: 14px !important;
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
        }
    </style>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
@endpush

{{-- DATA & SCRIPTS --}}
<script>
    window.PRODUCT_MAP = @json($productMap ?? []);

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

    // ── Pure DOM helpers untuk draft unit select ──────────────────────────────
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

    function getDraftUnitValue() {
        const sel = getDraftUnitSelect();
        return sel ? sel.value : '';
    }

    function clearDraftUnitSelect() {
        const sel = getDraftUnitSelect();
        if (sel) sel.innerHTML = '';
    }

    // ── supplierBrowser ───────────────────────────────────────────────────────
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
        }
    }

    // ── itemsTable ────────────────────────────────────────────────────────────
    function itemsTable() {
        const oldDetailPayload = {
            codes: @json(old('fitemcode', [])),
            units: @json(old('fsatuan', [])),
            qtys: @json(old('fqty', [])),
            noacaks: @json(old('fnoacak', [])),
            descs: @json(old('fdesc', [])),
            ketdts: @json(old('fketdt', []))
        };

        const oldSavedItems = (oldDetailPayload.codes || []).map((code, idx) => ({
            uid: cryptoRandom(),
            fitemcode: (code || '').toString(),
            fitemname: '',
            fnoacak: (oldDetailPayload.noacaks?.[idx] || '').toString(),
            units: [],
            fsatuan: (oldDetailPayload.units?.[idx] || '').toString(),
            fqty: Number(oldDetailPayload.qtys?.[idx] || 0),
            fdesc: (oldDetailPayload.descs?.[idx] || '').toString(),
            fketdt: (oldDetailPayload.ketdts?.[idx] || '').toString(),
            maxqty: 0,
            fqtypo: 0,
        })).filter(row =>
            row.fitemcode.trim() !== '' ||
            row.fsatuan.trim() !== '' ||
            Number(row.fqty) > 0 ||
            row.fdesc.trim() !== '' ||
            row.fketdt.trim() !== ''
        );

        return {
            savedItems: oldSavedItems,
            activeRow: null,
            blockedByPO: false,
            browseTarget: 'draft',
            showDescModal: false,
            descTarget: 'draft',
            descSavedIndex: null,
            descValue: '',
            descItemCode: '',
            descItemName: '',
            descCopied: false,

            draft: {
                fitemcode: '',
                fitemname: '',
                fnoacak: '',
                units: [],
                fsatuan: '',
                fqty: '',
                fdesc: '',
                fketdt: '',
                maxqty: 0
            },

            resetDraft() {
                this.draft = {
                    fitemcode: '',
                    fitemname: '',
                    fnoacak: this.generateUniqueNoAcak(),
                    units: [],
                    fsatuan: '',
                    fqty: '',
                    fdesc: '',
                    fketdt: '',
                    maxqty: 0
                };
                clearDraftUnitSelect();
            },

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

            productMeta(code) {
                const key = (code || '').trim();
                return window.PRODUCT_MAP?.[key] || {
                    name: '',
                    units: [],
                    stock: 0,
                    unit_ratios: {
                        satuankecil: 1,
                        satuanbesar: 1,
                        satuanbesar2: 1
                    }
                };
            },

            formatStockLimit(code, qty, satuan) {
                const meta = this.productMeta(code);
                if (!code || !meta.stock) return '';

                const entered = Number(qty) || 0;
                const remaining = Math.max(0, meta.stock - entered);
                const units = meta.units || [];
                const ratios = meta.unit_ratios || {
                    satuankecil: 1,
                    satuanbesar: 1,
                    satuanbesar2: 1
                };

                if (!units.length || !satuan) return '';

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

                const limitValue = Math.floor(remaining / ratio);
                return '<span class="font-medium">limit:</span> ' + limitValue + ' ' + satuan;
            },

            // Hydrate baris TERSIMPAN — Alpine reaktif
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
                const prev = row.fsatuan;
                row.units = units;
                row.fsatuan = units.includes(prev) ? prev : (units[0] || '');
                row.maxqty = Number.isFinite(+meta.stock) && +meta.stock > 0 ? +meta.stock : 0;
                row.fnoacak = this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak();
            },

            // Hydrate baris DRAFT — pure DOM untuk select
            hydrateDraftFromMeta(meta) {
                const row = this.draft;
                if (!meta) {
                    row.fitemname = '';
                    row.units = [];
                    row.fsatuan = '';
                    row.maxqty = 0;
                    clearDraftUnitSelect();
                    return;
                }
                row.fitemname = meta.name || '';
                const units = [...new Set((meta.units || []).map(u => (u ?? '').toString().trim()).filter(Boolean))];
                row.units = units;
                row.maxqty = Number.isFinite(+meta.stock) && +meta.stock > 0 ? +meta.stock : 0;
                if (units.length > 1) {
                    populateDraftUnitSelect(units);
                    row.fsatuan = units[0]; // hanya fallback
                } else {
                    clearDraftUnitSelect();
                    row.fsatuan = units[0] || '';
                }
                row.fnoacak = this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak();
            },

            onCodeTypedRow(row) {
                this.hydrateDraftFromMeta(this.productMeta(row.fitemcode));
            },
            onCodeTypedSaved(item) {
                this.hydrateRowFromMeta(item, this.productMeta(item.fitemcode));
            },
            hasDesc(value) {
                return String(value ?? '').trim() !== '';
            },
            descButtonClass(value) {
                return this.hasDesc(value)
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                    : 'border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100';
            },
            getDescRow(target = 'draft', index = null) {
                if (target === 'saved' && index !== null) {
                    return this.savedItems[index] || null;
                }
                return this.draft || null;
            },
            openDesc(target = 'draft', index = null) {
                const row = this.getDescRow(target, index);
                const itemCode = (row?.fitemcode || '').toString().trim();
                if (!itemCode) return;
                this.descTarget = target;
                this.descSavedIndex = index;
                this.descItemCode = itemCode;
                this.descItemName = (row?.fitemname || '').toString().trim();
                this.descValue = (row?.fdesc || '').toString();
                this.showDescModal = true;
            },
            closeDesc() {
                this.showDescModal = false;
                this.descTarget = 'draft';
                this.descSavedIndex = null;
                this.descValue = '';
                this.descItemCode = '';
                this.descItemName = '';
                this.descCopied = false;
            },
            applyDesc() {
                const val = (this.descValue || '').trim();
                if (this.descTarget === 'saved' && this.descSavedIndex !== null) {
                    this.savedItems[this.descSavedIndex].fdesc = val;
                } else {
                    this.draft.fdesc = val;
                }
                this.closeDesc();
            },

            enforceQtyRow(row) {
                // max qty validation dihapus: qty tidak lagi dipaksa mengikuti stok maksimum.
                // HTML/server tetap menangani minimal qty.
                return;
            },

            focusUnitOrQty(item, i) {
                if (item.units.length > 1) this.$nextTick(() => document.getElementById('unit_saved_' + i)?.focus());
                else this.$nextTick(() => document.getElementById('qty_saved_' + i)?.focus());
            },
            focusSavedQty(i) {
                this.$nextTick(() => document.getElementById('qty_saved_' + i)?.focus());
            },
            focusSavedKet(i) {
                this.$nextTick(() => document.getElementById('ket_saved_' + i)?.focus());
            },
            focusDraftCode() {
                this.$nextTick(() => this.$refs.draftCode?.focus());
            },

            handleEnterOnCode() {
                if (this.draft.units.length > 1) getDraftUnitSelect()?.focus();
                else this.$refs.draftQty?.focus();
            },

            addIfComplete() {
                const r = this.draft;

                // Baca satuan langsung dari DOM — sumber kebenaran tunggal
                const satuanFinal = r.units.length > 1 ?
                    (getDraftUnitValue() || r.fsatuan) :
                    r.fsatuan;

                if (!r.fitemcode || !r.fitemname || !satuanFinal || !(Number(r.fqty) > 0)) {
                    if (!r.fitemcode) return this.$refs.draftCode?.focus();
                    if (!r.fitemname) return this.$refs.draftCode?.focus();
                    if (!satuanFinal) return (r.units.length > 1 ? getDraftUnitSelect()?.focus() : this.$refs.draftCode
                        ?.focus());
                    if (!(Number(r.fqty) > 0)) return this.$refs.draftQty?.focus();
                    return;
                }

                this.savedItems.push({
                    uid: cryptoRandom(),
                    fitemcode: r.fitemcode,
                    fitemname: r.fitemname,
                    fnoacak: this.normalizeNoAcak(r.fnoacak) || this.generateUniqueNoAcak(),
                    units: [...r.units],
                    fsatuan: satuanFinal, // dari DOM, bukan Alpine proxy
                    fqty: +r.fqty,
                    fdesc: r.fdesc || '',
                    fketdt: r.fketdt || '',
                    maxqty: r.maxqty,
                    fqtypo: 0,
                });

                this.resetDraft();
                this.$nextTick(() => this.$refs.draftCode?.focus());
            },

            removeSaved(i) {
                this.savedItems.splice(i, 1);
            },

            openBrowseFor(where, idx = null) {
                this.browseTarget = (where === 'saved' && idx !== null) ? idx : 'draft';
                window.dispatchEvent(new CustomEvent('browse-open', {
                    detail: {
                        forEdit: false
                    }
                }));
            },

            init() {
                this.resetDraft();

                window.addEventListener('product-chosen', (e) => {
                    const {
                        product
                    } = e.detail || {};
                    if (!product) return;

                    if (this.browseTarget === 'draft') {
                        this.draft.fitemcode = (product.fprdcode || '').toString();
                        this.hydrateDraftFromMeta(this.productMeta(this.draft.fitemcode));
                        this.draft.fqty = (+this.draft.fqty || 1);
                        this.$nextTick(() => {
                            if (this.draft.units.length > 1) getDraftUnitSelect()?.focus();
                            else this.$refs.draftQty?.focus();
                        });
                    } else {
                        const item = this.savedItems[this.browseTarget];
                        if (item) {
                            item.fitemcode = (product.fprdcode || '').toString();
                            this.hydrateRowFromMeta(item, this.productMeta(item.fitemcode));
                            item.fqty = (+item.fqty || 1);
                            const i = this.browseTarget;
                            this.$nextTick(() => document.getElementById('qty_saved_' + i)?.focus());
                        }
                    }
                }, {
                    passive: true
                });

                document.addEventListener('change', (e) => {
                    if (e.target && e.target.id === 'draftUnitSelect') {
                        this.draft.fsatuan = e.target.value;
                    }
                });
            }
        }
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
                    $('#productTable').off('click', '.btn-choose').on('click', '.btn-choose', (e) => {
                        const product = this.table.row($(e.target).closest('tr')).data();
                        if (product) this.choose(product);
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
            }
        }
    </script>
@endpush
@endif

@if (in_array($routeName, ['tr_prh.edit', 'tr_prh.delete'], true))
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
            margin: 0
        }

        input[type=number] {
            -moz-appearance: textfield
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
                <strong class="text-white fs-6">Gagal Memperbarui Data!</strong>
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
        $permissions = explode(',', session('user_restricted_permissions', ''));
        $canEditPermission = in_array('updateTr_prh', $permissions, true);
        $canDeletePermission = in_array('deleteTr_prh', $permissions, true);
    @endphp
    @php
        $isUsageLocked = !empty($blockedByPO) && $blockedByPO;
        $canClosePr = $action === 'edit' && $tr_prh->fclose != '1' && $isUsageLocked && (string) ($tr_prh->fprdin ?? '') === '0';
    @endphp
    {{-- ═══════════════════════════════════════════════════════════════════
     MODAL BLOCKED BY PO
════════════════════════════════════════════════════════════════════ --}}
    @if ((!empty($blockedByPO) && $blockedByPO) || session('blocked_by_po'))
        <div x-data="{ open: true }" x-show="open" x-cloak class="fixed inset-0 z-[99] flex items-center justify-center"
            x-transition.opacity>
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

            <div class="relative bg-white w-[92vw] max-w-2xl rounded-2xl shadow-2xl overflow-hidden">

                {{-- Header --}}
                <div class="px-6 py-4 border-b border-red-100 bg-red-50 flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                        <x-heroicon-o-lock-closed class="w-5 h-5 text-red-600" />
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-bold text-red-700">
                            PR Tidak Dapat {{ $action === 'delete' ? 'Dihapus' : 'Diedit' }}
                        </h3>
                        <p class="text-sm text-red-500 mt-0.5">
                            PR <strong>{{ $tr_prh->fprno }}</strong> sudah terikat dengan Purchase Order berikut:
                        </p>
                    </div>
                    {{-- Tombol X tutup modal --}}
                    <button type="button" @click="open = false"
                        class="flex-shrink-0 w-8 h-8 rounded-full bg-red-100 hover:bg-red-200 flex items-center justify-center transition-colors"
                        title="Tutup">
                        <x-heroicon-o-x-mark class="w-4 h-4 text-red-600" />
                    </button>
                </div>

                {{-- Body: tabel daftar PO --}}
                <div class="px-6 py-4 max-h-72 overflow-y-auto">
                    @if (!empty($existingPO) && $existingPO->isNotEmpty())
                        <table class="w-full text-sm border rounded overflow-hidden">
                            <thead>
                                <tr class="bg-gray-100 text-gray-700">
                                    <th class="px-3 py-2 text-left font-semibold">#</th>
                                    <th class="px-3 py-2 text-left font-semibold">No. PO</th>
                                    <th class="px-3 py-2 text-left font-semibold">Tanggal PO</th>
                                    <th class="px-3 py-2 text-left font-semibold">Supplier</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($existingPO as $idx => $po)
                                    <tr class="border-t hover:bg-gray-50">
                                        <td class="px-3 py-2 text-gray-400 text-xs">{{ $idx + 1 }}</td>
                                        <td class="px-3 py-2 font-mono font-medium text-blue-700">
                                            {{ $po->fpohno ?? $po->fpono }}
                                        </td>
                                        <td class="px-3 py-2 text-gray-600">
                                            {{ $po->fpodate ? \Carbon\Carbon::parse($po->fpodate)->format('d/m/Y') : '-' }}
                                        </td>
                                        <td class="px-3 py-2 text-gray-600">
                                            {{ $po->fsuppliername ?? '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-sm text-gray-600">PR ini sudah memiliki Purchase Order terkait.</p>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t bg-gray-50 flex justify-between items-center gap-3">
                    <p class="text-xs text-gray-500">
                        Batalkan PO terkait terlebih dahulu sebelum {{ $action === 'delete' ? 'menghapus' : 'mengedit' }} PR
                        ini.
                    </p>
                    <button type="button" @click="open = false"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center gap-2">
                        <x-heroicon-o-arrow-left class="w-5 h-5" />
                        Kembali
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div x-data="{ open: true }">
        <div class="bg-white rounded shadow p-6 md:p-8 max-w-[96rem] mx-auto">
            @if ($action === 'delete')
                <div class="space-y-4">
                    @php
                        $fmt = fn($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('Y-m-d') : '';
                        $isApproved = \App\Support\ApprovalState::isApprovedRecord($tr_prh);
                    @endphp

                                        @php
                        $__embeddedFormData = [
                        'isReadOnly' => true,
                        'isDeleteMode' => true,
                        'detailMode' => 'delete',
                        'allowDocumentNoEdit' => false,
                        'tr_prh' => $tr_prh,
                        'fcabang' => $fcabang,
                        'fbranchcode' => $fbranchcode,
                        'suppliers' => $suppliers ?? collect(),
                        'filterSupplierId' => old('fsupplier', $tr_prh->fsupplier ?? ''),
                    ];
                        extract($__embeddedFormData, EXTR_SKIP);
                    @endphp
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

                    <div class="mt-6 flex justify-center space-x-4">
                        @if ($canDeletePermission)
                            @if (!empty($blockedByPO) && $blockedByPO)
                                <button type="button" disabled
                                    class="bg-red-300 text-white px-6 py-2 rounded cursor-not-allowed flex items-center gap-2">
                                    <x-heroicon-o-lock-closed class="w-5 h-5" />
                                    Hapus (Terkunci)
                                </button>
                            @else
                                <button type="button" onclick="showDeleteModal()"
                                    class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 flex items-center">
                                    <x-heroicon-o-trash class="w-5 h-5 mr-2" /> Hapus
                                </button>
                            @endif
                        @endif
                        <button type="button" onclick="window.location.href='{{ route('tr_prh.index') }}'"
                            class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                            <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" /> Kembali
                        </button>
                    </div>
                </div>

                {{-- Modal Konfirmasi Delete --}}
                @if ($canDeletePermission)
                    <div id="deleteModal"
                        class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                        <div class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                            <h3 class="text-lg font-semibold mb-4">Konfirmasi hapus Permintaan Pembelian ini?</h3>
                            <form action="{{ route('tr_prh.destroy', $tr_prh->fprhid) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <div class="flex justify-end space-x-2">
                                    <button onclick="closeDeleteModal()" type="button"
                                        class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Tidak</button>
                                    <button type="submit"
                                        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Ya, Hapus</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <script>
                        function showDeleteModal() {
                            document.getElementById('deleteModal')?.classList.remove('hidden');
                        }

                        function closeDeleteModal() {
                            document.getElementById('deleteModal')?.classList.add('hidden');
                        }
                    </script>
                @endif
            @else
                {{-- MODE EDIT --}}
                <form action="{{ route('tr_prh.update', $tr_prh->fprhid) }}" method="POST" x-data="{ showNoItems: false, blockedByPO: {{ $blockedByPO ? 'true' : 'false' }} }"
                    @submit.prevent="
                        const n = Number(document.getElementById('itemsCount')?.value || 0);
                        if (n < 1) { showNoItems = true; return; }
                        $el.submit();
                    ">
                    @csrf
                    @method('PATCH')

                    @if (!empty($blockedByPO) && $blockedByPO)
                        <div class="mb-4 flex items-center gap-3 px-4 py-3 rounded-lg bg-amber-50 border border-amber-200">
                            <x-heroicon-o-lock-closed class="w-5 h-5 text-amber-500 flex-shrink-0" />
                            <p class="text-sm text-amber-700">
                                <strong>Mode hanya baca.</strong> PR ini tidak dapat diedit karena sudah memiliki Purchase
                                Order terkait.
                            </p>
                        </div>
                    @endif

                    @php
                        $fmt = fn($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('Y-m-d') : '';
                        $isApproved = \App\Support\ApprovalState::isApprovedRecord($tr_prh);
                    @endphp

                    <div :class="blockedByPO ? 'opacity-60 pointer-events-none' : ''">
                                                @php
                            $__embeddedFormData = [
                            'isReadOnly' => false,
                            'detailMode' => 'edit',
                            'allowDocumentNoEdit' => false,
                            'tr_prh' => $tr_prh,
                            'fcabang' => $fcabang,
                            'fbranchcode' => $fbranchcode,
                            'suppliers' => $suppliers ?? collect(),
                            'products' => $products ?? collect(),
                            'productMap' => $productMap ?? [],
                            'filterSupplierId' => old('fsupplier', $tr_prh->fsupplier ?? ''),
                        ];
                            extract($__embeddedFormData, EXTR_SKIP);
                        @endphp
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

                                <div class="lg:col-span-12 mt-6">
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
                    </div>

                    <div class="mt-8 flex justify-center gap-4">
                        @if ($canEditPermission)
                            @if ($isUsageLocked)
                                <button type="button" disabled
                                    class="bg-blue-300 text-white px-8 py-2.5 rounded shadow flex items-center transition cursor-not-allowed opacity-70"
                                    title="{{ $usageLockMessage ?? 'Data ini sudah digunakan.' }}">
                                    <x-heroicon-o-lock-closed class="w-5 h-5 mr-2" /> Simpan Perubahan
                                </button>
                            @else
                                <button type="submit"
                                    class="bg-blue-600 text-white px-8 py-2.5 rounded shadow hover:bg-blue-700 flex items-center transition">
                                    <x-heroicon-o-check class="w-5 h-5 mr-2" /> Simpan Perubahan
                                </button>
                            @endif
                            @if ($canClosePr)
                                <button type="button" onclick="showClosePrModal()"
                                    class="bg-amber-500 text-white px-8 py-2.5 rounded shadow hover:bg-amber-600 flex items-center transition">
                                    <x-heroicon-o-lock-closed class="w-5 h-5 mr-2" /> Close
                                </button>
                            @endif
                        @endif
                        <button type="button" @click="window.location.href='{{ route('tr_prh.index') }}'"
                            class="bg-gray-500 text-white px-8 py-2.5 rounded shadow hover:bg-gray-600 flex items-center transition">
                            <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" /> Kembali
                        </button>
                    </div>

                </form>
                @if ($canClosePr)
                    <form id="closePrForm" action="{{ route('tr_prh.update', $tr_prh->fprhid) }}" method="POST" class="hidden">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="close_only" value="1">
                        <input type="hidden" name="fclose" value="1">
                    </form>
                    <div id="closePrModal"
                        class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                        <div class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                            <h3 class="text-lg font-semibold mb-2">Konfirmasi Close</h3>
                            <p class="text-sm text-gray-600 mb-4">Apakah anda yakin mau close PR
                                <strong>{{ $tr_prh->fprno }}</strong>?
                            </p>
                            <div class="flex justify-end gap-2">
                                <button type="button" onclick="closeClosePrModal()"
                                    class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 text-sm font-medium">No</button>
                                <button type="submit" form="closePrForm"
                                    class="px-4 py-2 bg-amber-500 text-white rounded hover:bg-amber-600 text-sm font-medium">Yes</button>
                            </div>
                        </div>
                    </div>
                    <script>
                        function showClosePrModal() {
                            document.getElementById('closePrModal')?.classList.remove('hidden');
                        }

                        function closeClosePrModal() {
                            document.getElementById('closePrModal')?.classList.add('hidden');
                        }
                    </script>
                @endif
            @endif
        </div>
    </div>

    {{-- MODAL SUPPLIER --}}
    <div x-data="supplierBrowser()" x-init="init()" x-show="open" x-cloak x-transition.opacity
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
                    class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                    Tutup
                </button>
            </div>
            <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                <div id="supplierTableControls"></div>
            </div>
            <div class="flex-1 overflow-y-auto px-6" style="min-height: 0;">
                <div class="bg-white">
                    <table id="supplierBrowseTable" class="min-w-full text-sm display nowrap stripe hover"
                        style="width:100%">
                        <thead class="sticky top-0 z-10">
                            <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Kode</th>
                                <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Nama
                                    Supplier</th>
                                <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Alamat
                                </th>
                                <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Telepon
                                </th>
                                <th class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
                <div id="supplierTablePagination"></div>
            </div>
        </div>
    </div>

    {{-- MODAL PRODUK --}}
    <div x-data="productBrowser()" x-init="init()" x-show="open" x-cloak x-transition.opacity
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
                    class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                    Tutup
                </button>
            </div>
            <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                <div id="productTableControls"></div>
            </div>
            <div class="flex-1 overflow-y-auto px-6" style="min-height: 0;">
                <div class="bg-white">
                    <table id="productTable" class="min-w-full text-sm display nowrap stripe hover" style="width:100%">
                        <thead class="sticky top-0 z-10">
                            <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Kode</th>
                                <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Nama
                                    Produk</th>
                                <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Satuan
                                </th>
                                <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Merek</th>
                                <th class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Stock
                                </th>
                                <th class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
                <div id="productTablePagination"></div>
            </div>
        </div>
    </div>


@push('styles')
    <style>
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

        div#productTable_length,
        .dataTables_wrapper #productTable_length,
        .dataTables_wrapper .dataTables_length {
            min-width: 250px !important;
        }

        div#productTable_length label,
        .dataTables_wrapper #productTable_length label,
        .dataTables_wrapper .dataTables_length label {
            font-size: 14px !important;
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
        }

        div#supplierTable_length select,
        .dataTables_wrapper #supplierTable_length select,
        table#supplierBrowseTable+.dataTables_wrapper .dataTables_length select {
            min-width: 140px !important;
            width: auto !important;
            padding: 8px 45px 8px 16px !important;
            font-size: 14px !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
        }

        div#supplierTable_length,
        .dataTables_wrapper #supplierTable_length,
        .dataTables_wrapper .dataTables_length {
            min-width: 250px !important;
        }

        div#supplierTable_length label,
        .dataTables_wrapper #supplierTable_length label,
        .dataTables_wrapper .dataTables_length label {
            font-size: 14px !important;
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
        }
    </style>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
        window.PRODUCT_MAP = @json($productMap ?? []);

        window.cryptoRandom = () => 'r' + Math.random().toString(16).slice(2, 10);

        function supplierBrowser() {
            return {
                open: false,
                supplierId: "{{ old('fsupplier', $tr_prh->fsupplier) }}",
                supplierDisplay: "{{ $tr_prh->fsuppliername }} ({{ $tr_prh->fsupplier }})",
                dataTable: null,
                init() {
                    window.addEventListener('browse-supplier', () => {
                        this.openModal();
                    });
                },
                openModal() {
                    this.open = true;
                    this.$nextTick(() => this.initDT());
                },
                close() {
                    this.open = false;
                },
                initDT() {
                    if (this.dataTable) this.dataTable.destroy();
                    this.dataTable = $('#supplierBrowseTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "{{ route('suppliers.browse') }}",
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
                    $('#supplierBrowseTable').off('click', '.btn-choose').on('click', '.btn-choose', (e) => {
                        const data = this.dataTable.row($(e.target).closest('tr')).data();
                        if (!data) return;
                        window.dispatchEvent(new CustomEvent('supplier-chosen', {
                            detail: data
                        }));
                        this.close();
                    });
                },
                onSupplierChosen(d) {
                    this.supplierId = d.fsuppliercode;
                    this.supplierDisplay = `${d.fsuppliername} (${d.fsuppliercode})`;
                }
            }
        }

        function productBrowser() {
            return {
                open: false,
                dataTable: null,
                target: 'draft',
                targetIdx: null,
                init() {
                    window.addEventListener('browse-product', (e) => {
                        this.target = e.detail.target;
                        this.targetIdx = e.detail.index;
                        this.open = true;
                        this.$nextTick(() => this.initDT());
                    });
                },
                close() {
                    this.open = false;
                },
                initDT() {
                    if (this.dataTable) this.dataTable.destroy();
                    this.dataTable = $('#productTable').DataTable({
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
                    $('#productTable').off('click', '.btn-choose').on('click', '.btn-choose', (e) => {
                        const productData = this.dataTable.row($(e.target).closest('tr')).data();
                        if (!productData) return;
                        window.dispatchEvent(new CustomEvent('product-chosen', {
                            detail: {
                                code: productData.fprdcode,
                                target: this.target,
                                index: this.targetIdx
                            }
                        }));
                        this.close();
                    });
                }
            }
        }

        function itemsTable() {
            // Hydrate savedItems BEFORE passing to Alpine
            const oldDetailPayload = {
                ids: @json(old('fprdid', [])),
                codes: @json(old('fitemcode', [])),
                units: @json(old('fsatuan', [])),
                qtys: @json(old('fqty', [])),
                qtypos: @json(old('fqtypo', [])),
                noacaks: @json(old('fnoacak', [])),
                descs: @json(old('fdesc', [])),
                ketdts: @json(old('fketdt', []))
            };

            const oldItems = (oldDetailPayload.codes || []).map((code, idx) => ({
                uid: cryptoRandom(),
                fprdid: Number(oldDetailPayload.ids?.[idx] || 0),
                fitemcode: (code || '').toString(),
                fitemname: '',
                fnoacak: (oldDetailPayload.noacaks?.[idx] || '').toString(),
                units: [],
                fsatuan: (oldDetailPayload.units?.[idx] || '').toString(),
                fqty: Number(oldDetailPayload.qtys?.[idx] || 0),
                fdesc: (oldDetailPayload.descs?.[idx] || '').toString(),
                fketdt: (oldDetailPayload.ketdts?.[idx] || '').toString(),
                fqtypo: Number(oldDetailPayload.qtypos?.[idx] || 0)
            })).filter(row =>
                row.fitemcode.trim() !== '' ||
                row.fsatuan.trim() !== '' ||
                Number(row.fqty) > 0 ||
                row.fdesc.trim() !== '' ||
                row.fketdt.trim() !== ''
            );

            const rawItems = oldItems.length > 0 ? oldItems : @json($savedItems ?? []);

            const hydratedItems = rawItems.map(it => {
                const code = (it.fitemcode || '').trim();
                let meta = window.PRODUCT_MAP[code];

                if (!meta) {
                    const keys = Object.keys(window.PRODUCT_MAP || {});
                    meta = keys.reduce((found, key) => {
                        if (found) return found;
                        if (key.includes(code) || code.includes(key)) return window.PRODUCT_MAP[key];
                        return null;
                    }, null);
                }

                let units = [];
                if (meta && meta.units && meta.units.length > 0) {
                    units = meta.units;
                } else if (it.fsatuan) {
                    units = [it.fsatuan];
                }

                return {
                    ...it,
                    uid: it.uid || cryptoRandom(),
                    units: units,
                    fsatuan: it.fsatuan || (units[0] || '')
                };
            });

            return {
                savedItems: hydratedItems,
                activeRow: null,
                blockedByPO: false,
                showDescModal: false,
                descTarget: 'draft',
                descSavedIndex: null,
                descValue: '',
                descItemCode: '',
                descItemName: '',
                draft: {
                    fitemcode: '',
                    fitemname: '',
                    fnoacak: '',
                    units: [],
                    fsatuan: '',
                    fqty: 1,
                    fdesc: '',
                    fketdt: ''
                },
                normalizeNoAcak(value) {
                    return (value || '').toString().replace(/\D/g, '').slice(0, 3);
                },
                formatQtyValue(value) {
                    const num = Number(value);
                    if (!Number.isFinite(num)) return '0,00';
                    return new Intl.NumberFormat('id-ID', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(num);
                },
                generateUniqueNoAcak() {
                    const used = new Set(this.savedItems.map(item => this.normalizeNoAcak(item.fnoacak)).filter(Boolean));
                    let candidate = '';

                    do {
                        candidate = Array.from({ length: 3 }, () => '123456789'[Math.floor(Math.random() * 9)]).join('');
                    } while (used.has(candidate));

                    return candidate;
                },
                onCodeTypedDraft() {
                    const meta = window.PRODUCT_MAP[this.draft.fitemcode];
                    if (meta) {
                        this.draft.fprdid = meta.id;
                        this.draft.fitemname = meta.name;
                        this.draft.units = meta.units;
                        this.draft.fsatuan = meta.units[0] || '';
                        this.draft.fnoacak = this.normalizeNoAcak(this.draft.fnoacak) || this.generateUniqueNoAcak();
                    } else {
                        this.draft.fprdid = 0;
                        this.draft.fitemname = '';
                        this.draft.units = [];
                        this.draft.fsatuan = '';
                    }
                },
                onCodeTypedSaved(it) {
                    const meta = window.PRODUCT_MAP[it.fitemcode];
                    if (meta) {
                        it.fprdid = meta.id;
                        it.fitemname = meta.name;
                        it.units = meta.units;
                        if (!it.units.includes(it.fsatuan)) it.fsatuan = it.units[0] || '';
                        it.fnoacak = this.normalizeNoAcak(it.fnoacak) || this.generateUniqueNoAcak();
                    }
                },
                hasDesc(value) {
                    return String(value ?? '').trim() !== '';
                },
                descButtonClass(value) {
                    return this.hasDesc(value)
                        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                        : 'border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100';
                },
                getDescRow(target = 'draft', index = null) {
                    if (target === 'saved' && index !== null) {
                        return this.savedItems[index] || null;
                    }
                    return this.draft || null;
                },
                openDesc(target = 'draft', index = null) {
                    const row = this.getDescRow(target, index);
                    const itemCode = (row?.fitemcode || '').toString().trim();
                    if (!itemCode) return;
                    this.descTarget = target;
                    this.descSavedIndex = index;
                    this.descItemCode = itemCode;
                    this.descItemName = (row?.fitemname || '').toString().trim();
                    this.descValue = (row?.fdesc || '').toString();
                    this.showDescModal = true;
                },
                closeDesc() {
                    this.showDescModal = false;
                    this.descTarget = 'draft';
                    this.descSavedIndex = null;
                    this.descValue = '';
                    this.descItemCode = '';
                    this.descItemName = '';
                },
                applyDesc() {
                    const val = (this.descValue || '').trim();
                    if (this.descTarget === 'saved' && this.descSavedIndex !== null) {
                        this.savedItems[this.descSavedIndex].fdesc = val;
                    } else {
                        this.draft.fdesc = val;
                    }
                    this.closeDesc();
                },
                handleEnterOnDraftCode() {
                    if (this.draft.units.length > 1) this.$refs.draftUnit?.focus();
                    else this.$refs.draftQty?.focus();
                },
                addIfComplete() {
                    if (!this.draft.fitemcode || !this.draft.fitemname || !this.draft.fqty) return;
                    this.savedItems.push({
                        ...this.draft,
                        uid: cryptoRandom(),
                        fnoacak: this.normalizeNoAcak(this.draft.fnoacak) || this.generateUniqueNoAcak(),
                        fqtypo: 0
                    });
                    this.draft = {
                        fitemcode: '',
                        fitemname: '',
                        fnoacak: this.generateUniqueNoAcak(),
                        units: [],
                        fsatuan: '',
                        fqty: 1,
                        fdesc: '',
                        fketdt: ''
                    };
                    this.$nextTick(() => this.$refs.draftCode.focus());
                },
                removeSaved(i) {
                    this.savedItems.splice(i, 1);
                },
                openBrowseFor(target, index = null) {
                    window.dispatchEvent(new CustomEvent('browse-product', {
                        detail: {
                            target,
                            index
                        }
                    }));
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

                formatStockLimit(code, qty, satuan) {
                    const meta = this.productMeta(code);
                    if (!code || !meta.stock) return '';

                    const entered = Number(qty) || 0;
                    const remaining = Math.max(0, meta.stock - entered);
                    const units = meta.units || [];
                    const ratios = meta.unit_ratios || {
                        satuankecil: 1,
                        satuanbesar: 1,
                        satuanbesar2: 1
                    };

                    if (!units.length || !satuan) return '';

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

                    const limitValue = Math.floor(remaining / ratio);
                    return '<span class="font-medium">limit:</span> ' + limitValue + ' ' + satuan;
                },

                enforceQtyRow(row) {
                    // max qty validation removed (qty tidak lagi dibatasi mengikuti stok maksimum)
                    return;
                },

                init() {
                    this.savedItems = this.savedItems.map(it => ({
                        ...it,
                        fnoacak: this.normalizeNoAcak(it.fnoacak) || this.generateUniqueNoAcak()
                    }));
                    this.draft.fnoacak = this.generateUniqueNoAcak();

                    window.addEventListener('product-chosen', (e) => {
                        const {
                            code,
                            target,
                            index
                        } = e.detail;
                        if (target === 'saved') {
                            const it = this.savedItems[index];
                            it.fitemcode = code;
                            this.onCodeTypedSaved(it);
                        } else {
                            this.draft.fitemcode = code;
                            this.onCodeTypedDraft();
                        }
                    });
                }
            }
        }
    </script>
@endpush
@endif

@if ($routeName === 'tr_prh.view')
@php
        $permissions = explode(',', session('user_restricted_permissions', ''));
        $canPrint = in_array('viewTr_prh', $permissions, true) || in_array('updateTr_prh', $permissions, true) || in_array('deleteTr_prh', $permissions, true) || in_array('createTr_prh', $permissions, true);
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

        /* select supplier tanpa caret (view-only select) */
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
            margin: 0
        }

        input[type=number] {
            -moz-appearance: textfield
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

    <div x-data="{ open: true }">
        <div class="bg-white rounded shadow p-6 md:p-8 max-w-[96rem] mx-auto">
            @if (!empty($approvalLockMessage))
                <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    {{ $approvalLockMessage }}
                </div>
            @endif
            <div class="space-y-4">
                @php
                    $fmt = fn($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('Y-m-d') : '';
                @endphp
                @php
                    $isApproved = \App\Support\ApprovalState::isApprovedRecord($tr_prh);
                @endphp

                                @php
                    $__embeddedFormData = [
                    'isReadOnly' => true,
                    'isDeleteMode' => true,
                    'detailMode' => 'view',
                    'allowDocumentNoEdit' => false,
                    'tr_prh' => $tr_prh,
                    'fcabang' => $fcabang,
                    'fbranchcode' => $fbranchcode,
                    'suppliers' => $suppliers,
                    'filterSupplierId' => old('fsupplier', $tr_prh->fsupplier ?? ''),
                ];
                    extract($__embeddedFormData, EXTR_SKIP);
                @endphp
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

                        <div class="lg:col-span-12 mt-6">
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

                {{-- MODAL SUPPLIER --}}
                <div x-data="supplierBrowser()" x-init="init()" x-show="open" x-cloak x-transition.opacity
                    class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-6xl flex flex-col overflow-hidden"
                        style="height: 650px;">
                        <!-- Header -->
                        <div
                            class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
                            <div>
                                <h3 class="text-xl font-bold text-gray-800">Browse Supplier</h3>
                                <p class="text-sm text-gray-500 mt-0.5">Pilih supplier yang diinginkan</p>
                            </div>
                            <button type="button" @click="close()"
                                class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                                Tutup
                            </button>
                        </div>

                        <!-- Search & Length Menu -->
                        <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                            <div id="supplierTableControls"></div>
                        </div>

                        <!-- Table with fixed height and scroll -->
                        <div class="flex-1 overflow-y-auto px-6" style="min-height: 0;">
                            <div class="bg-white">
                                <table id="supplierBrowseTable" class="min-w-full text-sm display nowrap stripe hover"
                                    style="width:100%">
                                    <thead class="sticky top-0 z-10">
                                        <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                            <th
                                                class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                Kode</th>
                                            <th
                                                class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                Nama Supplier</th>
                                            <th
                                                class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                Alamat</th>
                                            <th
                                                class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                Telepon</th>
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
                            <div id="supplierTablePagination"></div>
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

                {{-- STATUS & ACTIONS --}}
                <div class="md:col-span-2 flex justify-center items-center space-x-2 mt-6">
                    <label for="statusToggle"
                        class="flex items-center justify-between w-40 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                        <span class="text-sm font-medium">Tutup</span>
                        <input disabled type="checkbox" name="fnonactive" id="statusToggle"
                            class="h-5 w-5 text-green-600 rounded focus:ring-green-500"
                            {{ old('fnonactive') == '1' ? 'checked' : '' }}>
                    </label>
                </div>

                @php
                    $canApproval = in_array('approvePR', explode(',', session('user_restricted_permissions', '')));
                @endphp
                @if ($canApproval)
                    <fieldset {{ $isApproved ? 'disabled' : '' }}>
                        <div class="md:col-span-2 flex justify-center items-center space-x-2 mt-6">
                            <label class="text-sm font-medium">Status Persetujuan</label>

                            <input type="hidden" name="fapproval" value="0">

                            <label class="switch">
                                <input disabled type="checkbox" name="fapproval" id="approvalToggle" value="1"
                                    {{ $isApproved ? 'checked' : '' }}>
                                <span class="slider round"></span>
                            </label>
                        </div>

                        @if ($isApproved)
                            <div class="text-xs text-gray-600 text-center mt-2">
                            Disetujui oleh:
                            <strong>{{ $tr_prh->fuserapproved ?: ($tr_prh->fuserapproved2 ?: '-') }}</strong>
                                @if (!empty($tr_prh->fdateapproved))
                                    pada {{ \Carbon\Carbon::parse($tr_prh->fdateapproved)->format('d-m-Y H:i') }}
                                @endif
                            </div>
                        @endif
                    </fieldset>
                @endif
            </div>

            <div class="mt-6 flex justify-center space-x-4">
                @if ($canPrint)
                    <a href="{{ route('tr_prh.print', $tr_prh->fprno) }}" target="_blank"
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m10 0v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5m10 0v5H7v-5">
                            </path>
                        </svg>
                        Print
                    </a>
                @endif
                <button type="button" onclick="window.location.href='{{ route('tr_prh.index') }}'"
                    class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                    Kembali
                </button>
            </div>
        
        @push('styles')
            <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
        @endpush
        <style>
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
            div#supplierTable_length select,
            .dataTables_wrapper #supplierTable_length select,
            table#supplierBrowseTable+.dataTables_wrapper .dataTables_length select {
                min-width: 140px !important;
                width: auto !important;
                padding: 8px 45px 8px 16px !important;
                font-size: 14px !important;
                border: 1px solid #d1d5db !important;
                border-radius: 0.375rem !important;
            }

            /* Wrapper length */
            div#supplierTable_length,
            .dataTables_wrapper #supplierTable_length,
            .dataTables_wrapper .dataTables_length {
                min-width: 250px !important;
            }

            /* Label wrapper */
            div#supplierTable_length label,
            .dataTables_wrapper #supplierTable_length label,
            .dataTables_wrapper .dataTables_length label {
                font-size: 14px !important;
                display: flex !important;
                align-items: center !important;
                gap: 8px !important;
            }
        </style>
        {{-- DATA & SCRIPTS --}}
        <script>
            // Map produk untuk auto-fill tabel
            window.PRODUCT_MAP = @json($productMap ?? []);

            // Seed items dari server (details)
            window.INIT_ITEMS = @json($savedItems ?? []);

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

            // Modal supplier (sama dengan create)
            function supplierBrowser() {
                return {
                    open: false,
                    dataTable: null,

                    initDataTable() {
                        if (this.dataTable) {
                            this.dataTable.destroy();
                        }

                        this.dataTable = $('#supplierBrowseTable').DataTable({
                            processing: true,
                            serverSide: true,
                            ajax: {
                                url: "{{ route('suppliers.browse') }}",
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
                        $('#supplierBrowseTable').on('click', '.btn-choose', (e) => {
                            const data = this.dataTable.row($(e.target).closest('tr')).data();
                            this.chooseSupplier(data);
                        });
                    },

                    openBrowse() {
                        this.open = true;
                        this.$nextTick(() => {
                            this.initDataTable();
                        });
                    },

                    close() {
                        this.open = false;
                        if (this.dataTable) {
                            this.dataTable.search('').draw();
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
                }
            }

            // Alpine store untuk desc (optional, sama seperti create)
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

            // Tabel inline (re-use dari create, plus initFromServer)
            function itemsTable() {
                return {
                    savedItems: @json($savedItems ?? []),
                    blockedByPO: false,
                    draft: {
                        fitemcode: '',
                        fitemname: '',
                        units: [],
                        fsatuan: '',
                        fqty: 1,
                        fqtypo: 0,
                        fdesc: '',
                        fketdt: '',
                        maxqty: 0
                    },
                    editingIndex: null,
                    editRow: {
                        fitemcode: '',
                        fitemname: '',
                        units: [],
                        fsatuan: '',
                        fqty: 1,
                        fdesc: '',
                        fqtypo: 0,
                        fketdt: '',
                        maxqty: 0
                    },

                    resetDraft() {
                        this.draft = {
                            fitemcode: '',
                            fitemname: '',
                            units: [],
                            fsatuan: '',
                            fqty: 1,
                            fqtypo: 0,
                            fdesc: '',
                            fketdt: '',
                            maxqty: 0
                        };
                    },
                    productMeta(code) {
                        const key = (code || '').trim();
                        return window.PRODUCT_MAP?.[key] || {
                            name: '',
                            units: [],
                            stock: 0
                        };
                    },
                    hydrateRowFromMeta(row, meta) {
                        if (!meta) {
                            row.fprdid = null; // ⬅️ reset
                            row.fitemname = '';
                            row.units = [];
                            row.fsatuan = '';
                            row.maxqty = 0;
                            return;
                        }
                        row.fprdid = meta.id || null; // ⬅️ ambil ID
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
                    sanitizeNumber(v, d = 0) {
                        const n = +v;
                        return Number.isFinite(n) ? n : d;
                    },
                    formatQtyValue(value) {
                        const num = Number(value);
                        if (!Number.isFinite(num)) return '0,00';
                        return new Intl.NumberFormat('id-ID', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }).format(num);
                    },
                    enforceQtyRow(row) {
                        // max qty validation dihapus: qty tidak lagi dibatasi mengikuti stok maksimum.
                        // (validasi min qty tetap dilakukan oleh server)
                        return;
                    },
                    isComplete(row) {
                        return row.fitemcode && row.fitemname && row.fsatuan && Number(row.fqty) > 0;
                    },

                    addIfComplete() {
                        const r = this.draft;
                        if (!this.isComplete(r)) {
                            /* ... */
                            return;
                        }

                        r.fqtypo = Number.isFinite(+r.fqtypo) ? +r.fqtypo : 0;
                        r.fqty = this.sanitizeNumber(r.fqty, 1);

                        // pastikan ada ID:
                        if (!r.fprdid) {
                            const meta = this.productMeta(r.fitemcode);
                            r.fprdid = meta?.id ?? null;
                        }
                        if (!r.fprdid) {
                            alert('Produk tidak valid / ID produk tidak ditemukan.');
                            return;
                        }

                        const dupe = this.savedItems.find(it =>
                            it.fitemcode === r.fitemcode && it.fsatuan === r.fsatuan &&
                            (it.fdesc || '') === (r.fdesc || '') && (it.fketdt || '') === (r.fketdt || '')
                        );
                        if (dupe) {
                            alert('Item sama sudah ada.');
                            return;
                        }

                        this.savedItems.push({
                            uid: cryptoRandom(),
                            fprdid: r.fprdid, // ⬅️ simpan ID
                            fitemcode: r.fitemcode,
                            fitemname: r.fitemname,
                            fsatuan: r.fsatuan,
                            fqtypo: r.fqtypo,
                            fqty: +r.fqty,
                            fdesc: r.fdesc || '',
                            fketdt: r.fketdt || ''
                        });

                        this.resetDraft();
                        this.$nextTick(() => this.$refs.draftCode?.focus());
                        this.syncDescList();
                    },

                    initFromServer() {
                        // 1) Ambil data detail dari server (dibawa blade ke window.INIT_ITEMS)
                        const rows = Array.isArray(window.INIT_ITEMS) ? window.INIT_ITEMS : [];

                        this.savedItems = rows.map((r) => {
                            const it = {
                                uid: cryptoRandom(),
                                fitemcode: (r.fitemcode || '').toString(),
                                fitemname: r.fitemname || '',
                                fsatuan: r.fsatuan || '',
                                fqty: Number(r.fqty) || 0,
                                fqtypo: Number(r.fqtypo) || 0, // <-- ADD THIS
                                fdesc: r.fdesc || '',
                                fketdt: r.fketdt || '',
                                units: [],
                                maxqty: 0,
                            };

                            // 2) Hydrate dari PRODUCT_MAP (seperti saat create)
                            const meta = this.productMeta(it.fitemcode);
                            this.hydrateRowFromMeta(it, meta);

                            // 3) Pastikan qty sesuai rules (min 1, max stok kalau ada)
                            // max qty validation dihapus

                            return it;
                        });

                        // 4) Sinkron daftar deskripsi untuk preview/list
                        this.syncDescList();
                        this.init();
                    },

                    // === Deskripsi via Modal ===
                    showDescModal: false,
                    descTarget: 'draft', // 'draft' | 'edit' | 'saved'
                    descSavedIndex: null, // index untuk 'saved'
                    descValue: '',
                    descPreview: '', // untuk ditampilkan di luar card

                    openDesc(where, idx = null, currentVal = '') {
                        this.descTarget = where;
                        this.descSavedIndex = (where === 'saved' ? idx : null);
                        this.descValue = currentVal || '';
                        this.showDescModal = true;

                        // set preview sementara (sebelum disimpan) biar user tahu baris mana
                        let meta = {
                            uid: null,
                            index: null,
                            label: '',
                            text: this.descValue
                        };
                        if (where === 'saved' && idx !== null) {
                            const it = this.savedItems[idx];
                            meta = {
                                uid: it.uid,
                                index: idx + 1,
                                label: this.labelOf(it),
                                text: this.descValue
                            };
                        } else if (where === 'edit') {
                            meta = {
                                uid: 'editing',
                                index: (this.editingIndex ?? 0) + 1,
                                label: this.labelOf(this.editRow),
                                text: this.descValue
                            };
                        } else {
                            meta = {
                                uid: 'draft',
                                index: this.savedItems.length + 1,
                                label: this.labelOf(this.draft),
                                text: this.descValue
                            };
                        }
                        Alpine.store('prh').descPreview = meta;
                    },
                    closeDesc() {
                        this.showDescModal = false;
                    },
                    applyDesc() {
                        const val = (this.descValue || '').trim();

                        if (this.descTarget === 'draft') {
                            this.draft.fdesc = val;
                            Alpine.store('prh').descPreview = {
                                uid: 'draft',
                                index: this.savedItems.length + 1,
                                label: this.labelOf(this.draft),
                                text: val
                            };
                        } else if (this.descTarget === 'edit') {
                            this.editRow.fdesc = val;
                            Alpine.store('prh').descPreview = {
                                uid: 'editing',
                                index: (this.editingIndex ?? 0) + 1,
                                label: this.labelOf(this.editRow),
                                text: val
                            };
                        } else if (this.descTarget === 'saved' && this.descSavedIndex !== null) {
                            const it = this.savedItems[this.descSavedIndex];
                            it.fdesc = val;
                            Alpine.store('prh').descPreview = {
                                uid: it.uid,
                                index: this.descSavedIndex + 1,
                                label: this.labelOf(it),
                                text: val
                            };
                        }

                        this.showDescModal = false;
                        this.syncDescList(); // update daftar semua desc
                    },

                    labelOf(row) {
                        // bebas: pakai kode - nama atau apa pun
                        return [row.fitemcode, row.fitemname].filter(Boolean).join(' — ');
                    },
                    syncDescList() {
                        Alpine.store('prh').descList = this.savedItems
                            .map((it, i) => ({
                                uid: it.uid,
                                index: i + 1,
                                label: this.labelOf(it),
                                text: it.fdesc || ''
                            }))
                            .filter(x => x.text); // hanya yang ada deskripsi
                    },

                    handleEnterOnCode(where) {
                        // Pindah fokus dari Kode -> (Unit jika >1) else -> Qty
                        if (where === 'edit') {
                            if (this.editRow.units.length > 1) this.$refs.editUnit?.focus();
                            else this.$refs.editQty?.focus();
                        } else {
                            if (this.draft.units.length > 1) this.$refs.draftUnit?.focus();
                            else this.$refs.draftQty?.focus();
                        }
                    },

                    edit(i) {
                        const it = this.savedItems[i];
                        this.editingIndex = i;
                        this.editRow = {
                            fprdid: it.fprdid || null, // ⬅️ penting
                            fitemcode: it.fitemcode,
                            fitemname: it.fitemname,
                            units: [],
                            fsatuan: it.fsatuan,
                            fqty: it.fqty,
                            fdesc: it.fdesc,
                            fqtypo: it.fqtypo,
                            fketdt: it.fketdt,
                            maxqty: 0
                        };
                        this.hydrateRowFromMeta(this.editRow, this.productMeta(this.editRow.fitemcode));
                        this.$nextTick(() => this.$refs.editQty?.focus());
                    },
                    cancelEdit() {
                        this.editingIndex = null;
                    },
                    applyEdit() {
                        const r = this.editRow;
                        if (!this.isComplete(r)) {
                            alert('Lengkapi data item.');
                            return;
                        }

                        // pastikan ada ID untuk produk hasil edit
                        if (!r.fprdid) {
                            const meta = this.productMeta(r.fitemcode);
                            r.fprdid = meta?.id ?? null;
                        }
                        if (!r.fprdid) {
                            alert('Produk tidak valid / ID produk tidak ditemukan.');
                            return;
                        }

                        const it = this.savedItems[this.editingIndex];
                        it.fprdid = r.fprdid; // ⬅️ copy ID
                        it.fitemcode = r.fitemcode;
                        it.fitemname = r.fitemname;
                        it.fsatuan = r.fsatuan;
                        it.fqty = this.sanitizeNumber(r.fqty, 1);
                        it.fdesc = r.fdesc || '';
                        it.fqtypo = Math.max(0, this.sanitizeNumber(r.fqtypo, 0));
                        it.fketdt = r.fketdt || '';

                        this.cancelEdit();
                        this.syncDescList();
                    },

                    removeSaved(i) {
                        this.savedItems.splice(i, 1);
                        this.syncDescList(); // <= tambahkan ini
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

                    init() {
                        window.addEventListener('product-chosen', (e) => {
                            const {
                                product
                            } = e.detail || {};
                            if (!product) return;

                            const apply = (row) => {
                                row.fitemcode = (product.fprdcode || '').toString();
                                row.fprdid = product.fprdid ?? (window.PRODUCT_INDEX_BY_CODE?.[row.fitemcode]?.id ??
                                    null); // ⬅️ set ID
                                // kalau browse nggak kirim name/units, hydrasi lewat PRODUCT_MAP:
                                const meta = this.productMeta(row.fitemcode);
                                if (meta) {
                                    this.hydrateRowFromMeta(row,
                                        meta); // ini juga akan set fprdid, name, units, stock, dll
                                } else {
                                    // fallback kalau meta kosong, set minimal name
                                    row.fitemname = product.fprdname || row.fitemname || '';
                                }
                                // perbaiki qty
                                row.fqtypo = Math.max(0, this.sanitizeNumber(row.fqtypo, 0));
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

                        // ... (listener prh-before-submit tetap)
                    },

                }
            }
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
@endif
