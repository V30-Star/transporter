@extends('layouts.app')

@section('title', 'Permintaan Pembelian')

@section('content')
    <style>
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
        }
    </style>

    <div x-data="{ open: true, selected: 'surat' }">
        <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1200px] mx-auto">
            <h2 class="text-2xl font-semibold text-gray-800 flex items-center space-x-2">
                <x-heroicon-o-scale class="w-8 h-8 text-blue-600" />
                <span>Edit - Permintaan Pembelian</span>
            </h2>
            <form action="{{ route('tr_prh.update', $tr_prh->fprno) }}" method="POST">
                @csrf
                @method('PATCH')
                @php
                    $isApproved = !empty($product->fapproval);
                @endphp
                <div class="space-y-4 mt-4">
                    <div class="mt-2 w-1/3">
                        <label class="block text-sm font-medium">Cabang User</label>
                        <input type="text" name="fcabang"
                            class="w-full border rounded px-3 py-2 bg-gray-200 cursor-not-allowed"
                            value="{{ $fcabang }}" disabled>
                        <input type="hidden" name="fbranchcode" value="{{ $fcabang }}">
                    </div>

                    <div x-data="{ autoCode: true }" class="flex items-center gap-4">
                        <div class="mt-2 w-1/3">
                            <label class="block text-sm font-medium">PR#</label>
                            <input type="text" name="fprnoid" class="w-full border rounded px-3 py-2"
                                placeholder="Masukkan nilai PR#" :disabled="autoCode"
                                :value="autoCode ? '' : '{{ old('fprnoid', $tr_prh->fprnoid) }}'"
                                :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'">
                        </div>

                        <label class="inline-flex items-center mt-6">
                            <input type="checkbox" x-model="autoCode" class="form-checkbox text-indigo-600" checked>
                            <span class="ml-2 text-sm text-gray-700">Auto</span>
                        </label>
                    </div>

                    <div class="mt-4 w-1/4">
                        <label class="block text-sm font-medium">Supplier</label>
                        <select name="fsupplier"
                            class="w-full border rounded px-3 py-2 @error('fsupplier') border-red-500 @enderror"
                            id="groupSelect">
                            <option value="">-- Pilih Supplier --</option>
                            @foreach ($supplier as $suppliers)
                                <option value="{{ $suppliers->fsupplierid }}"
                                    {{ old('fsupplier', $tr_prh->fsupplier) == $suppliers->fsupplierid ? 'selected' : '' }}>
                                    {{ $suppliers->fsuppliername }}
                                </option>
                            @endforeach
                        </select>
                        @error('fsupplier')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-4 w-1/3">
                        <label class="block text-sm font-medium">Tanggal</label>
                        <input type="date" name="fprdate" value="{{ old('fprdate', $tr_prh->fprdate) }}"
                            class="w-full border rounded px-3 py-2 @error('fprdate') border-red-500 @enderror">
                        @error('fprdate')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-4 w-1/3">
                        <label class="block text-sm font-medium">Tanggal Dibutuhkan</label>
                        <input type="date" name="fneeddate" value="{{ old('fneeddate', $tr_prh->fneeddate) }}"
                            class="w-full border rounded px-3 py-2 @error('fneeddate') border-red-500 @enderror">
                        @error('fneeddate')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-4 w-1/3">
                        <label class="block text-sm font-medium">Tanggal Paling Lambat</label>
                        <input type="date" name="fduedate" value="{{ old('fduedate', $tr_prh->fduedate) }}"
                            class="w-full border rounded px-3 py-2 @error('fduedate') border-red-500 @enderror">
                        @error('fduedate')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium">Keterangan</label>
                        <textarea name="fket" rows="4" class="w-full border rounded px-3 py-2 @error('fket') border-red-500 @enderror"
                            placeholder="Tulis keterangan tambahan di sini...">{{ old('fket', $tr_prh->fket) }}</textarea>
                        @error('fket')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    @foreach ($tr_prh->details as $detail)
                        <div x-data="detailProduk()" class="mt-6 border rounded">
                            <div class="grid grid-cols-12 bg-gray-100 border-b text-sm font-semibold">
                                <div class="col-span-1 p-2 text-center">No.</div>
                                <div class="col-span-2 p-2">Kode Produk</div>
                                <div class="col-span-3 p-2">Nama Produk</div>
                                <div class="col-span-1 p-2">Desc.</div>
                                <div class="col-span-1 p-2">Sat.</div>
                                <div class="col-span-1 p-2 text-right">Qty.</div>
                                <div class="col-span-2 p-2">Keterangan</div>
                                <div class="col-span-1 p-2 text-center">Action</div>
                            </div>

                            <div class="max-h-64 overflow-y-auto">
                                <template x-for="(row, idx) in items" :key="row.uid">
                                    <div class="grid grid-cols-12 border-b text-sm">
                                        <div class="col-span-1 p-2 text-center" x-text="idx + 1"></div>

                                        <div class="col-span-2 p-1">
                                            <select class="w-full border rounded px-2 py-1 product-code-select"
                                                :id="`code-${row.uid}`" x-model="row.selectedCode"
                                                @change="onProductChange(row, $event.target.value)">
                                                <option value="">-- pilih kode --</option>
                                                @foreach ($products as $p)
                                                    <option value="{{ $p->fproductcode }}"
                                                        {{ old('fitemcode', $detail->fprdcode) == $p->fproductcode ? 'selected' : '' }}>
                                                        {{ $p->fproductcode }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="fitemcode[]" :value="row.fitemcode">
                                        </div>

                                        <div class="col-span-3 p-1">
                                            <select class="w-full border rounded px-2 py-1 product-name-select"
                                                :id="`name-${row.uid}`" x-model="row.selectedCode"
                                                @change="onProductChange(row, $event.target.value)">
                                                <option value="">-- pilih produk --</option>
                                                @foreach ($products as $product)
                                                    <option value="{{ $product->fproductcode }}"
                                                        {{ old('fitemcode', $detail->fprdcode) == $product->fproductcode ? 'selected' : '' }}>
                                                        {{ $product->fproductname }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="fitemname[]" :value="row.fitemname">
                                        </div>

                                        <div class="col-span-1 p-1">
                                            <input type="text" class="w-full border rounded px-2 py-1"
                                                x-model="row.fdesc" name="fdesc[]" placeholder="(Memo)"
                                                value="{{ old('fdesc', $detail->fdesc) }}" @input="checkAutoAdd(idx)">
                                        </div>

                                        <!-- Satuan (fsatuan) -->
                                        <div class="col-span-1 p-1">
                                            <select name="fsatuan[]" class="w-full border rounded px-2 py-1"
                                                x-model="row.fsatuan">
                                                <option value="">-- Pilih Satuan --</option>
                                                <template x-for="unit in row.units" :key="unit">
                                                    <option :value="unit" :selected="row.fsatuan === unit"
                                                        x-text="unit"></option>
                                                </template>
                                            </select>
                                        </div>

                                        <div class="col-span-1 p-1">
                                            <input type="number" min="1" step="1"
                                                class="w-full border rounded px-2 py-1 text-right"
                                                x-model.number="row.fqty" name="fqty[]" placeholder="0"
                                                value="{{ old('fqty', $detail->fqty) }}">
                                        </div>

                                        <div class="col-span-2 p-1">
                                            <input type="text" class="w-full border rounded px-2 py-1"
                                                x-model="row.fketdt" name="fketdt[]" placeholder="Keterangan"
                                                value="{{ old('fketdt', $detail->fketdt) }}" @input="checkAutoAdd(idx)">
                                        </div>
                                        <div class="col-span-1 p-1 flex items-center justify-center">
                                            <button type="button" @click="delAt(idx)" :disabled="idx === 0"
                                                :class="idx === 0 ?
                                                    'px-2 py-1 rounded text-xs flex items-center gap-1 bg-gray-100 text-gray-400 cursor-not-allowed' :
                                                    'px-2 py-1 rounded text-xs flex items-center gap-1 bg-red-100 hover:bg-red-200 text-red-600'">
                                                <x-heroicon-o-trash class="w-4 h-4" />
                                                Delete Item
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    @endforeach

                    <!-- Ensure that fclose gets a value of 0 when not checked -->
                    <input type="hidden" name="fclose" value="0">

                    <div class="md:col-span-2 flex justify-center items-center space-x-2">
                        <input type="checkbox" name="fclose" id="statusToggle"
                            class="form-checkbox h-5 w-5 text-indigo-600"
                            {{ old('fclose', $tr_prh->fclose) == '1' ? 'checked' : '' }}>
                        <label class="block text-sm font-medium">Closed</label>
                    </div>
                    
                    <div class="md:col-span-2 flex justify-center items-center space-x-2">
                        <fieldset {{ $isApproved ? 'disabled' : '' }}>
                            <div class="flex items-center space-x-2">
                                <label class="text-sm font-medium">Approval</label>
                                <label class="switch">
                                    <input type="checkbox" name="approve_now" id="approvalToggle"
                                        {{ !empty($product->fapproval) ? 'checked' : '' }}>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </fieldset>
                    </div>

                </div>

                <div class="mt-6 flex justify-center space-x-4">
                    <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                        <x-heroicon-o-check class="w-5 h-5 mr-2" />
                        Simpan
                    </button>

                    <button type="button" @click="window.location.href='{{ route('tr_prh.index') }}'"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                        Keluar
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection


<script>
    window.PRODUCT_MAP = {
        @foreach ($products as $p)
            "{{ $p->fproductcode }}": {
                name: @json($p->fproductname),
                units: @json(array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2]))),
                stock: @json($p->fminstock ?? 0)
            },
        @endforeach
    };
</script>

<!-- Helper UID -->
<script>
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
</script>
<script>
    function detailProduk() {
        return {
            items: [
                @foreach ($tr_prh->details as $detail)
                    {
                        uid: cryptoRandom(),
                        selectedCode: "{{ $detail->fprdcode }}",
                        fitemcode: "{{ $detail->fprdcode }}",
                        fitemname: "{{ $detail->fprdname }}",
                        units: @json($productMap[$detail->fprdcode]['units'] ?? []), // Dynamically pass units
                        fsatuan: "{{ $detail->fsatuan }}",
                        fdesc: "{{ $detail->fdesc }}",
                        fqty: {{ $detail->fqty }},
                        fketdt: "{{ $detail->fketdt }}",
                        maxqty: {{ $detail->fqty }},
                    },
                @endforeach
            ],

            addItem() {
                const newRow = {
                    uid: cryptoRandom(),
                    selectedCode: '',
                    fitemcode: '',
                    fitemname: '',
                    units: [], // Make sure to initialize as an empty array
                    fdesc: '',
                    fsatuan: 'PCS', // Default value
                    fqty: '',
                    fketdt: '',
                    maxqty: 0
                };
                this.items.push(newRow);
                this.$nextTick(() => this.initSelect2ForRow(newRow));
            },

            delAt(i) {
                if (i === 0) return;
                const row = this.items[i];
                this.destroySelect2ForRow(row);
                this.items.splice(i, 1);
            },

            onProductChange(row, code) {
                const key = (code ?? '').toString().trim();
                row.selectedCode = key;

                const meta = window.PRODUCT_MAP[key] || {
                    name: '',
                    units: [],
                    stock: 0
                };

                row.fitemcode = key;
                row.fitemname = meta.name || '';
                row.maxqty = Number.isFinite(+meta.stock) && +meta.stock > 0 ? +meta.stock : 0;

                // Update the available units based on the selected product
                row.units = meta.units || [];

                // Set the default unit (fsatuan) if not already selected
                if (!row.units.includes(row.fsatuan)) {
                    row.fsatuan = row.units[0] || ''; // Default to first unit
                }

                // Adjust quantity if stock is available
                if (row.maxqty > 0) {
                    if (!row.fqty || +row.fqty < 1) row.fqty = 1;
                    if (+row.fqty > row.maxqty) row.fqty = row.maxqty;
                } else {
                    row.fqty = '';
                }
            },

            // <-- PENTING: method TERPISAH, bukan di dalam onProductChange
            enforceQty(row) {
                const n = +row.fqty;
                if (!Number.isFinite(n)) {
                    row.fqty = '';
                    return;
                }
                if (n < 1) row.fqty = 1;
                if (row.maxqty > 0 && n > row.maxqty) row.fqty = row.maxqty;
            },

            checkAutoAdd(idx) {
                if (idx === this.items.length - 1) {
                    const r = this.items[idx];
                    if (r.fitemcode && r.fsatuan && r.fqty) this.addItem();
                }
            },

            // ===== Select2 Helpers (aman kalau Select2 belum ada) =====
            select2Ready() {
                return !!(window.jQuery && $.fn && $.fn.select2);
            },

            initSelect2ForRow(row) {
                if (!this.select2Ready()) return; // fallback native select

                const codeSel = `#code-${row.uid}`;
                const nameSel = `#name-${row.uid}`;

                $(codeSel).select2({
                    width: '100%',
                    dropdownParent: $(codeSel).closest('.p-1'),
                    minimumResultsForSearch: 8
                });
                $(nameSel).select2({
                    width: '100%',
                    dropdownParent: $(nameSel).closest('.p-1'),
                    minimumResultsForSearch: 8
                });

                $(codeSel).val(row.selectedCode).trigger('change.select2');
                $(nameSel).val(row.selectedCode).trigger('change.select2');

                $(codeSel).on('change.select2', () => {
                    const val = ($(codeSel).val() || '').toString();
                    if (row.selectedCode !== val) {
                        this.onProductChange(row, val);
                        $(nameSel).val(val).trigger('change.select2');
                    }
                });

                $(nameSel).on('change.select2', () => {
                    const val = ($(nameSel).val() || '').toString();
                    if (row.selectedCode !== val) {
                        this.onProductChange(row, val);
                        $(codeSel).val(val).trigger('change.select2');
                    }
                });

                this.$watch(() => row.selectedCode, (val) => {
                    const v = (val || '').toString();
                    $(codeSel).val(v).trigger('change.select2');
                    $(nameSel).val(v).trigger('change.select2');
                });
            },

            destroySelect2ForRow(row) {
                if (!(window.jQuery && $.fn)) return;
                const codeSel = `#code-${row.uid}`;
                const nameSel = `#name-${row.uid}`;
                if ($(codeSel).data('select2')) $(codeSel).off().select2('destroy');
                if ($(nameSel).data('select2')) $(nameSel).off().select2('destroy');
            },

            init() {
                this.$nextTick(() => this.items.forEach(r => this.initSelect2ForRow(r)));
                window.addEventListener('keydown', (e) => {
                    if (e.key === 'F2') {
                        e.preventDefault();
                        this.addItem();
                    }
                });
            }
        }
    }
</script>


<style>
    .select2-container {
        width: 100% !important;
    }

    .select2-container--default .select2-selection--single {
        height: 36px !important;
        min-height: 36px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.375rem !important;
        display: flex !important;
        align-items: center !important;
        padding: 0 0.5rem !important;
        font-size: 0.875rem !important;
        line-height: 1.25rem !important;
        background-color: #fff !important;
    }


    .select2-container--default .select2-selection--single .select2-selection__rendered {
        margin: 0 !important;
        padding: 0 !important;
        color: #111827 !important;
        line-height: 1.25rem !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #6b7280 !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        right: 0.5rem !important;
    }

    .select2-container--default .select2-selection--single:hover {
        border-color: #9ca3af !important;
    }

    .select2-container--default.select2-container--focus .select2-selection--single,
    .select2-container--default .select2-selection--single:focus {
        border-color: #2563eb !important;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2) !important;
        outline: none !important;
    }

    .select2-dropdown {
        border: 1px solid #d1d5db !important;
        border-radius: 0.375rem !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
    }

    .select2-results__option--highlighted {
        background-color: #2563eb !important;
        color: #fff !important;
    }

    .p-1>.select2,
    .p-1>.select2-container {
        width: 100% !important;
    }

    .switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: 0.4s;
        border-radius: 34px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        border-radius: 50%;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: 0.4s;
    }

    input:checked+.slider {
        background-color: #4CAF50;
    }

    input:checked+.slider:before {
        transform: translateX(26px);
    }

    .slider.round {
        border-radius: 34px;
    }

    .slider.round:before {
        border-radius: 50%;
    }
</style>

<style>
    input:focus,
    select:focus,
    textarea:focus,
    .select2-container--default .select2-selection--single:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
    }

    .select2-container--default .select2-selection--single {
        border: 1px solid #000000 !important;
        border-radius: 0.375rem;
        height: 42px;
        padding: 0.5rem 0.75rem;
        width: 100% !important;
        background-color: white;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px;
    }

    .select2-dropdown {
        border: 1px solid #000000 !important;
        border-radius: 0.375rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .select2-results__option {
        padding: 8px 12px;
    }

    .select2-results__option--highlighted {
        background-color: #2563eb !important;
        color: white !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #000000 !important;
    }
</style>
