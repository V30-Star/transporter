@extends('layouts.app')

@section('title', 'Faktur Pembelian')

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

        input[type=number] {
            -moz-appearance: textfield;
        }
    </style>

    <div x-data="{ open: true }">
        <div x-data="{ includePPN: false, ppnRate: 0, ppnAmount: 0, totalHarga: 100000, selectedType: 0 }" class="lg:col-span-5">
            <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1600px] w-full mx-auto">
                <form action="{{ route('fakturpembelian.store') }}" method="POST" class="mt-6" x-data="{ showNoItems: false }"
                    @submit.prevent="
        const n = Number(document.getElementById('itemsCount')?.value || 0);
        if (n < 1) { showNoItems = true } else { $el.submit() }
      ">
                    @csrf

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
                                <input type="text" name="fpono" class="w-full border rounded px-3 py-2"
                                    :disabled="autoCode"
                                    :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'">
                                <label class="inline-flex items-center select-none">
                                    <input type="checkbox" x-model="autoCode" checked>
                                    <span class="ml-2 text-sm text-gray-700">Auto</span>
                                </label>
                            </div>
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">Type</label>
                            <select name="ftypebuy" x-model="selectedType"
                                class="w-full border rounded px-3 py-2 @error('ftypebuy') border-red-500 @enderror">
                                <option value="0" {{ old('ftypebuy') == '0' ? 'selected' : '' }}>Trade</option>
                                <option value="1" {{ old('ftypebuy') == '1' ? 'selected' : '' }}>Non Stok</option>
                                <option value="2" {{ old('ftypebuy') == '2' ? 'selected' : '' }}>Uang Muka</option>
                            </select>
                            @error('ftypebuy')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">Tanggal</label>
                            <input type="date" id="fstockmtdate" name="fstockmtdate"
                                value="{{ old('fstockmtdate') ?? date('Y-m-d') }}"
                                class="w-full border rounded px-3 py-2 @error('fstockmtdate') border-red-500 @enderror">
                            @error('fstockmtdate')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">Tgl. Jatuh Tempo</label>
                            <input type="date" id="fjatuhtempo" name="fjatuhtempo" value="{{ old('fjatuhtempo', '') }}"
                                readonly
                                class="w-full border rounded px-3 py-2 bg-gray-100 @error('fjatuhtempo') border-red-500 @enderror">
                            @error('fjatuhtempo')
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
                                            <option value="{{ $wh->fwhcode }}" data-id="{{ $wh->fwhid }}"
                                                data-branch="{{ $wh->fbranchcode }}"
                                                {{ old('ffrom') == $wh->fwhcode ? 'selected' : '' }}>
                                                {{ $wh->fwhcode }} - {{ $wh->fwhname }}
                                            </option>
                                        @endforeach
                                    </select>

                                    {{-- Overlay untuk buka browser gudang --}}
                                    <div class="absolute inset-0" role="button" aria-label="Browse warehouse"
                                        @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open'))"></div>
                                </div>

                                <input type="hidden" name="ffrom" id="warehouseCodeHidden" value="{{ old('ffrom') }}">
                                <input type="hidden" name="fwhid" id="warehouseIdHidden" value="{{ old('fwhid') }}">

                                <button type="button"
                                    @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open'))"
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

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium mb-1">Account</label>
                            <div class="flex">
                                <div class="relative flex-1">
                                    <select id="accountSelect" class="w-full border rounded-l px-3 py-2"
                                        :class="{
                                            'bg-gray-100 text-gray-700 cursor-not-allowed': selectedType != '1',
                                            'bg-white cursor-pointer': selectedType == '1'
                                        }"
                                        disabled>
                                        <option value=""></option>
                                        @foreach ($accounts as $account)
                                            <option value="{{ $account->faccount }}" data-faccid="{{ $account->faccid }}"
                                                data-branch="{{ $account->faccount }}"
                                                {{ old('fprdjadi') == $account->faccount ? 'selected' : '' }}>
                                                {{ $account->faccount }} - {{ $account->faccname }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <div class="absolute inset-0" role="button" aria-label="Browse account"
                                        @click="window.dispatchEvent(new CustomEvent('account-browse-open'))"
                                        x-show="selectedType == '1'"></div>
                                </div>

                                <input type="hidden" name="fprdjadi" id="accountCodeHidden"
                                    value="{{ old('fprdjadi') }}">
                                <input type="hidden" name="faccid" id="accountIdHidden" value="{{ old('faccid') }}">

                                <button type="button"
                                    @click="window.dispatchEvent(new CustomEvent('account-browse-open'))"
                                    class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                                    :disabled="selectedType != '1'"
                                    :class="{ 'opacity-50 cursor-not-allowed': selectedType != '1' }"
                                    title="Browse Account">
                                    <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                </button>

                                <a href="{{ route('account.create') }}" target="_blank" rel="noopener"
                                    class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50"
                                    :class="{ 'opacity-50 cursor-not-allowed pointer-events-none': selectedType != '1' }"
                                    @click="selectedType != '1' && $event.preventDefault()" title="Tambah Account">
                                    <x-heroicon-o-plus class="w-5 h-5" />
                                </a>
                            </div>

                            @error('fprdjadi')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-npsm font-medium mb-1">Faktur</label>
                            <div class="flex items-center gap-3">
                                <input type="text" name="frefno" class="w-full border rounded px-3 py-2">
                                <label class="inline-flex items-center select-none">
                                </label>
                            </div>
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium mb-1">Faktur Pajak#</label>
                            <div class="flex items-center">
                                <input type="text" id="frefpo" name="frefpo"
                                    class="w-full border rounded px-3 py-2 @error('frefpo') border-red-500 @enderror">
                            </div>
                            @error('frefpo')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium mb-1">Supplier</label>
                            <div class="flex">
                                <div class="relative flex-1">
                                    <select id="supplierSelect" name="fsupplier"
                                        class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
                                        disabled onchange="updateTempo()">
                                        <option value=""></option>
                                        @foreach ($supplier as $suppliers)
                                            <option value="{{ $suppliers->fsupplierid }}"
                                                data-tempo="{{ $suppliers->ftempo }}"
                                                {{ old('fsupplier') == $suppliers->fsupplierid ? 'selected' : '' }}>
                                                {{ $suppliers->fsuppliercode }} - {{ $suppliers->fsuppliername }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-0" role="button" aria-label="Browse supplier"
                                        @click="window.dispatchEvent(new CustomEvent('supplier-browse-open'))"></div>
                                </div>
                                <input type="hidden" name="fsupplier" id="supplierCodeHidden"
                                    value="{{ old('fsupplier') }}">
                                <button type="button"
                                    @click="window.dispatchEvent(new CustomEvent('supplier-browse-open'))"
                                    class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                                    title="Browse Supplier">
                                    <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                </button>
                                <a href="{{ route('supplier.create') }}" target="_blank" rel="noopener"
                                    class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50"
                                    title="Tambah Supplier">
                                    <x-heroicon-o-plus class="w-5 h-5" />
                                </a>
                            </div>
                            @error('fsupplier')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>


                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">TOP (Hari)</label>
                            <input type="number" id="ftempohr" name="ftempohr" value="{{ old('ftempohr', '0') }}"
                                class="w-full border rounded px-3 py-2 @error('ftempohr') border-red-500 @enderror"
                                placeholder="Masukkan jumlah hari">
                            @error('ftempohr')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

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

                    <script>
                        function calculateDueDate() {
                            const poDate = document.getElementById('fstockmtdate').value;
                            const tempoDays = parseInt(document.getElementById('ftempohr').value) || 0;

                            if (poDate && tempoDays > 0) {
                                const date = new Date(poDate);
                                date.setDate(date.getDate() + tempoDays);

                                // Format ke YYYY-MM-DD
                                const year = date.getFullYear();
                                const month = String(date.getMonth() + 1).padStart(2, '0');
                                const day = String(date.getDate()).padStart(2, '0');

                                document.getElementById('fjatuhtempo').value = `${year}-${month}-${day}`;
                            } else {
                                document.getElementById('fjatuhtempo').value = '';
                            }
                        }

                        document.getElementById('fstockmtdate').addEventListener('change', calculateDueDate);
                        document.getElementById('ftempohr').addEventListener('input', calculateDueDate);

                        document.addEventListener('DOMContentLoaded', calculateDueDate);
                    </script>

                    <script>
                        function updateTempo() {
                            const supplierSelect = document.getElementById('supplierSelect');
                            const tempoInput = document.getElementById('ftempohr');

                            const selectedOption = supplierSelect.options[supplierSelect.selectedIndex];
                            const tempo = selectedOption.getAttribute('data-tempo');

                            tempoInput.value = tempo || 0;
                        }

                        document.addEventListener('DOMContentLoaded', function() {
                            updateTempo();
                        });
                    </script>

                    <div x-data="itemsTable()" x-init="init()" class="mt-6 space-y-2">

                        {{-- DETAIL ITEM (tabel input) --}}
                        <h3 class="text-base font-semibold text-gray-800">Detail Item</h3>

                        <div class="overflow-auto border rounded">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="p-2 text-left w-10">#</th>
                                        <th class="p-2 text-left w-40">Kode Produk</th>
                                        <th class="p-2 text-left w-72">Nama Produk</th>
                                        <th class="p-2 text-left w-72">No Refrensi</th>
                                        <th class="p-2 text-left w-28">Satuan</th>
                                        <th class="p-2 text-right w-24 whitespace-nowrap">Qty.</th>
                                        <th class="p-2 text-right w-32 whitespace-nowrap">@ Harga</th>
                                        <th class="p-2 text-right w-32 whitespace-nowrap">@ Biaya</th>
                                        <th class="p-2 text-right w-24 whitespace-nowrap">Disc. %</th>
                                        <th class="p-2 text-right w-36 whitespace-nowrap">Total Harga</th>
                                        <th class="p-2 text-center w-28">Aksi</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <template x-for="(it, i) in savedItems" :key="it.uid">
                                        <!-- ROW UTAMA -->
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
                                            <td class="p-2 text-right" x-text="it.frefdtno"></td>
                                            <td class="p-2 text-right" x-text="it.fsatuan"></td>
                                            <td class="p-2 text-right" x-text="fmt(it.fqty)"></td>
                                            <td class="p-2 text-right" x-text="fmt(it.fprice)"></td>
                                            <td class="p-2 text-right" x-text="fmt(it.fbiaya)"></td>
                                            <td class="p-2 text-right" x-text="fmt(it.fdiscpersen)"></td>
                                            <td class="p-2 text-right" x-text="fmt(it.ftotprice)"></td>
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
                                                <input type="hidden" name="fqty[]" :value="it.fqty">
                                                <input type="hidden" name="fprice[]" :value="it.fprice">
                                                <input type="hidden" name="fbiaya[]" :value="it.fbiaya">
                                                <input type="hidden" name="fdiscpersen[]" :value="it.fdiscpersen">
                                                <input type="hidden" name="ftotprice[]" :value="it.ftotprice">
                                                <input type="hidden" name="fdesc[]" :value="it.fdesc">
                                                <input type="hidden" name="fketdt[]" :value="it.fketdt">
                                                <input type="hidden" name="frefdtno[]" :value="it.frefdtno">
                                            </td>
                                        </tr>

                                        <!-- ROW DESC (di bawah Nama Produk) -->
                                        <tr class="border-b">
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                            <td class="p-2" colspan="3">
                                                <textarea x-model="draft.fdesc" rows="2" class="w-full border rounded px-4 py-1"
                                                    placeholder="Deskripsi (opsional)"></textarea>
                                            </td>
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                            <td class="p-0"></td>
                                        </tr>
                                    </template>

                                    <!-- ROW EDIT UTAMA -->
                                    <tr x-show="editingIndex !== null" class="border-t align-top" x-cloak>
                                        <!-- # -->
                                        <td class="p-2" x-text="(editingIndex ?? 0) + 1"></td>

                                        <!-- Kode Produk -->
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
                                                <a href="{{ route('product.create') }}" target="_blank" rel="noopener"
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

                                        <!-- Ref.PR# -->
                                        <td class="p-2">
                                            <input type="text"
                                                class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                :value="editRow.frefdtno" disabled placeholder="Ref PR">
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
                                                x-model.number="editRow.fqty" @input="recalc(editRow)"
                                                @keydown.enter.prevent="$refs.editTerima?.focus()">
                                        </td>

                                        <!-- @ Harga -->
                                        <td class="p-2 text-right">
                                            <input type="number" class="border rounded px-2 py-1 w-28 text-right"
                                                min="0" step="0.01" x-ref="editPrice"
                                                x-model.number="editRow.fprice" @input="recalc(editRow)"
                                                @keydown.enter.prevent="$refs.editDisc?.focus()">
                                        </td>

                                        <!-- @ Biaya -->
                                        <td class="p-2 text-right">
                                            <input type="number" class="border rounded px-2 py-1 w-28 text-right"
                                                min="0" step="0.01" x-ref="editBiaya"
                                                x-model.number="editRow.fbiaya" default="0"
                                                @keydown.enter.prevent="$refs.editDisc?.focus()">
                                        </td>

                                        <!-- Disc.% -->
                                        <td class="p-2 text-right">
                                            <input type="number" class="border rounded px-2 py-1 w-24 text-right"
                                                min="0" max="100" step="0.01" x-ref="editDisc"
                                                x-model.number="editRow.fdiscpersen" @input="recalc(editRow)"
                                                @keydown.enter.prevent="applyEdit()">
                                        </td>

                                        <!-- Total Harga (readonly) -->
                                        <td class="p-2 text-right" x-text="fmt(editRow.ftotprice)"></td>

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

                                    <!-- ROW EDIT DESC -->
                                    <tr x-show="editingIndex !== null" class="border-b" x-cloak>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                        <td class="p-2" colspan="3">
                                            <textarea x-model="draft.fdesc" rows="2" class="w-full border rounded px-4 py-1"
                                                placeholder="Deskripsi (opsional)"></textarea>
                                        </td>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                    </tr>

                                    <!-- ROW DRAFT UTAMA -->
                                    <tr class="border-t align-top">
                                        <!-- # -->
                                        <td class="p-2" x-text="savedItems.length + 1"></td>

                                        <!-- Kode Produk -->
                                        <td class="p-2">
                                            <div class="flex">
                                                <input type="text" class="flex-1 border rounded-l px-2 py-1 font-mono"
                                                    x-ref="draftCode" x-model.trim="draft.fitemcode"
                                                    @input="onCodeTypedRow(draft)"
                                                    @keydown.enter.prevent="handleEnterOnCode('draft')">
                                                <button type="button" @click="openBrowseFor('draft')"
                                                    class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50"
                                                    title="Cari Produk">
                                                    <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                </button>
                                                <a href="{{ route('product.create') }}" target="_blank" rel="noopener"
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

                                        <!-- Ref.PR# -->
                                        <td class="p-2">
                                            <input type="text"
                                                class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                :value="draft.frefdtno" disabled placeholder="Ref PR">
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
                                                x-model.number="draft.fqty" @input="recalc(draft)"
                                                @keydown.enter.prevent="$refs.draftTerima?.focus()">
                                        </td>

                                        <!-- @ Harga -->
                                        <td class="p-2 text-right">
                                            <input type="number" class="border rounded px-2 py-1 w-28 text-right"
                                                min="0" step="0.01" x-ref="draftPrice"
                                                x-model.number="draft.fprice" @input="recalc(draft)"
                                                @keydown.enter.prevent="$refs.draftDisc?.focus()">
                                        </td>

                                        <!-- @ Biaya -->
                                        <td class="p-2 text-right">
                                            <input type="number" class="border rounded px-2 py-1 w-28 text-right"
                                                min="0" step="0.01" x-ref="draftBiaya"
                                                x-model.number="draft.fbiaya" @input="recalc(draft)" default="0"
                                                @keydown.enter.prevent="$refs.draftBiaya?.focus()">
                                        </td>

                                        <!-- Disc.% -->
                                        <td class="p-2 text-right">
                                            <input type="number" class="border rounded px-2 py-1 w-24 text-right"
                                                min="0" max="100" step="0.01" x-ref="draftDisc"
                                                x-model.number="draft.fdiscpersen" @input="recalc(draft)"
                                                @keydown.enter.prevent="addIfComplete()">
                                        </td>

                                        <!-- Total Harga (readonly) -->
                                        <td class="p-2 text-right" x-text="fmt(draft.ftotprice)"></td>

                                        <!-- Aksi -->
                                        <td class="p-2 text-center">
                                            <div class="flex items-center justify-center gap-2 flex-wrap">
                                                <button type="button" @click="addIfComplete()"
                                                    class="px-3 py-1 rounded text-xs bg-emerald-600 text-white">Tambah</button>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- ROW DRAFT DESC -->
                                    <tr class="border-b">
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                        <td class="p-2" colspan="3">
                                            <textarea x-model="draft.fdesc" rows="2" class="w-full border rounded px-4 py-1"
                                                placeholder="Deskripsi (opsional)"></textarea>
                                        </td>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                        <td class="p-0"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- ===== Trigger: Add tr_prh dari panel kanan ===== -->
                        <div x-data="prhFormModal()" class="mt-3">
                            <div class="mt-3 flex justify-between items-start gap-4">
                                <div class="w-full flex justify-start mb-3">
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
                                <!-- Kanan: Panel Totals -->
                                <div class="w-1/2">
                                    <div class="rounded-lg border bg-gray-50 p-3 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-700">Total Harga</span>
                                            <span class="min-w-[140px] text-right font-medium"
                                                x-text="rupiah(totalHarga)"></span>
                                        </div>
                                        <div class="flex items-center justify-between gap-6">
                                            <!-- Checkbox -->
                                            <div class="flex items-center">
                                                <input id="fapplyppn" type="checkbox" name="fapplyppn" value="1"
                                                    x-model="includePPN"
                                                    class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                <label for="fapplyppn" class="ml-2 text-sm font-medium text-gray-700">
                                                    <span class="font-bold">PPN</span>
                                                </label>
                                            </div>

                                            <!-- Dropdown Include / Exclude (tengah) -->
                                            <div class="flex items-center gap-2">
                                                <select id="includePPN" name="includePPN" x-model.number="fapplyppn"
                                                    x-init="fapplyppn = 0" :disabled="!(includePPN || fapplyppn)"
                                                    class="w-28 h-9 px-2 text-sm leading-tight border rounded transition-opacity appearance-none
                                                           disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed">
                                                    <option value="0">Exclude</option>
                                                    <option value="1">Include</option>
                                                </select>
                                            </div>

                                            <!-- Input Rate + Nominal (kanan) -->
                                            <div class="flex items-center gap-2">
                                                <input type="number" min="0" max="100" step="0.01"
                                                    x-model.number="ppnRate" :disabled="!(includePPN || fapplyppn)"
                                                    class="w-20 h-9 px-2 text-sm leading-tight text-right border rounded transition-opacity
                                                            [appearance:textfield]
                                                            [&::-webkit-outer-spin-button]:appearance-none
                                                            [&::-webkit-inner-spin-button]:appearance-none
                                                            disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed">
                                                <span class="text-sm">%</span>
                                                <span class="min-w-[140px] text-right font-medium"
                                                    x-text="rupiah(ppnAmount)"></span>
                                            </div>

                                        </div>

                                        <div class="border-t my-1"></div>

                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-semibold text-gray-800">Grand Total</span>
                                            <span class="min-w-[140px] text-right text-lg font-semibold"
                                                x-text="rupiah(grandTotal)"></span>
                                        </div>

                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-semibold text-gray-800">Grand Total (RP)</span>
                                            <span class="min-w-[140px] text-right text-lg font-semibold"
                                                x-text="rupiah(grandTotal)"></span>
                                        </div>
                                    </div>

                                    <!-- Hidden inputs for submit -->
                                    <input type="hidden" name="famount" :value="totalHarga">
                                    <input type="hidden" name="" :value="ppnAmount">
                                    <input type="hidden" name="famountmt" :value="grandTotal">
                                    <input type="hidden" name="famountpajak" :value="ppnRate">
                                </div>
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
                                        <h3 class="text-lg font-semibold">Add PR</h3>
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

                                        <!-- Table PR-->
                                        <div class="overflow-auto border rounded">
                                            <table class="min-w-full text-sm">
                                                <thead class="bg-gray-100">
                                                    <tr>
                                                        <th class="p-2 text-left w-48">PR No</th>
                                                        <th class="p-2 text-left w-48">Supplier</th>
                                                        <th class="p-2 text-left w-48">Tanggal</th>
                                                        <th class="p-2 text-right w-24">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template x-for="row in rows" :key="row.fprid">
                                                        <tr class="border-t">
                                                            <td class="p-2" x-text="row.fprno"></td>
                                                            <td class="p-2" x-text="row.fsupplier || '-'"></td>
                                                            <td class="p-2" x-text="formatDate(row.fprdate)"></td>
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
                                                        <td colspan="4" class="p-4 text-center text-gray-500">Tidak ada
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
                                                    class="rounded border px-2 py-1 disabled:opacity-50">« First</button>
                                                <button @click="goToPage(currentPage-1)" :disabled="currentPage <= 1"
                                                    class="rounded border px-2 py-1 disabled:opacity-50">‹ Prev</button>
                                                <button @click="goToPage(currentPage+1)"
                                                    :disabled="currentPage >= lastPage"
                                                    class="rounded border px-2 py-1 disabled:opacity-50">Next ›</button>
                                                <button @click="goToPage(lastPage)" :disabled="currentPage >= lastPage"
                                                    class="rounded border px-2 py-1 disabled:opacity-50">Last »</button>
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
                                            Ada <span class="font-semibold" x-text="dupCount"></span> item duplikat.
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
                                                    <li class="px-3 py-2 text-sm text-gray-500">Tidak ada contoh.</li>
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

                        <!-- MODAL DESC (di dalam itemsTable) -->
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
                                    Anda belum menambahkan item apa pun pada tabel. Silakan isi baris “Detail Item” terlebih
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

                    {{-- MODAL SUPPLIER --}}
                    <div x-data="supplierBrowser()" x-show="open" x-cloak x-transition.opacity
                        class="fixed inset-0 z-50 flex items-center justify-center">
                        <div class="absolute inset-0 bg-black/40" @click="close()"></div>
                        <div class="relative bg-white rounded-2xl shadow-xl w-[92vw] max-w-4xl max-h-[85vh] flex flex-col">
                            <div class="p-4 border-b flex items-center gap-3">
                                <h3 class="text-lg font-semibold">Browse Supplier</h3>
                                <div class="ml-auto flex items-center gap-2">
                                    <input type="text" x-model="keyword" @keydown.enter.prevent="search()"
                                        placeholder="Cari kode / nama…" class="border rounded px-3 py-2 w-64">
                                    <button type="button" @click="search()"
                                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Search</button>
                                </div>
                            </div>
                            <div class="p-0 overflow-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-100 sticky top-0">
                                        <tr>
                                            <th class="text-left p-2">Supplier (Kode - Nama)</th>
                                            <th class="text-left p-2 w-40">Telepon</th>
                                            <th class="text-center p-2 w-28">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="s in rows" :key="s.fsupplierid">
                                            <tr class="border-b hover:bg-gray-50">
                                                <td class="p-2" x-text="`${s.fsuppliercode} - ${s.fsuppliername}`">
                                                </td>
                                                <td class="p-2" x-text="s.ftelp || '-'"></td>
                                                <td class="p-2 text-center">
                                                    <button type="button" @click="choose(s)"
                                                        class="px-3 py-1 rounded text-xs bg-emerald-600 hover:bg-emerald-700 text-white">Pilih</button>
                                                </td>
                                            </tr>
                                        </template>
                                        <tr x-show="rows.length === 0">
                                            <td colspan="3" class="p-4 text-center text-gray-500">Tidak ada data.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="p-3 border-t flex items-center gap-2">
                                <div class="text-sm text-gray-600"><span
                                        x-text="`Page ${page} / ${lastPage} • Total ${total}`"></span></div>
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

                    {{-- MODAL PRODUK --}}
                    <div x-data="productBrowser()" x-show="open" x-cloak x-transition.opacity
                        class="fixed inset-0 z-50 flex items-center justify-center">
                        <div class="absolute inset-0 bg-black/40" @click="close()"></div>
                        <div class="relative bg-white rounded-2xl shadow-xl w-[92vw] max-w-5xl max-h-[85vh] flex flex-col">
                            <div class="p-4 border-b flex items-center gap-3">
                                <h3 class="text-lg font-semibold">Browse Produk</h3>
                                <div class="ml-auto flex items-center gap-2">
                                    <input type="text" x-model="keyword" @keydown.enter.prevent="search()"
                                        placeholder="Cari kode / nama…" class="border rounded px-3 py-2 w-64">
                                    <button type="button" @click="search()"
                                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Search</button>
                                </div>
                            </div>
                            <div class="p-0 overflow-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-100 sticky top-0">
                                        <tr>
                                            <th class="text-left p-2 w-40">Kode</th>
                                            <th class="text-left p-2">Nama</th>
                                            <th class="text-left p-2 w-48">Satuan</th>
                                            <th class="text-center p-2 w-28">Stock</th>
                                            <th class="text-center p-2 w-28">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="p in rows" :key="p.fprdcode">
                                            <tr class="border-b hover:bg-gray-50">
                                                <td class="p-2 font-mono" x-text="p.fprdcode"></td>
                                                <td class="p-2" x-text="p.fprdname"></td>
                                                <td class="p-2">
                                                    <span x-text="p.fsatuanbesar || '-'"></span>
                                                </td>
                                                <td class="p-2 text-center" x-text="p.fminstock"></td>
                                                <td class="p-2 text-center">
                                                    <button type="button" @click="choose(p)"
                                                        class="px-3 py-1 rounded text-xs bg-emerald-600 hover:bg-emerald-700 text-white">Pilih</button>
                                                </td>
                                            </tr>
                                        </template>
                                        <tr x-show="rows.length === 0">
                                            <td colspan="5" class="p-4 text-center text-gray-500">Tidak ada data.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="p-3 border-t flex items-center gap-2">
                                <div class="text-sm text-gray-600"><span
                                        x-text="`Page ${page} / ${lastPage} • Total ${total}`"></span></div>
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

                    {{-- Modal Gudang --}}
                    <div x-data="warehouseBrowser()" x-show="open" x-cloak x-transition.opacity
                        class="fixed inset-0 z-50 flex items-center justify-center">
                        <div class="absolute inset-0 bg-black/40" @click="close()"></div>

                        <div class="relative bg-white rounded-2xl shadow-xl w-[92vw] max-w-4xl max-h-[85vh] flex flex-col">
                            <div class="p-4 border-b flex items-center gap-3">
                                <h3 class="text-lg font-semibold">Browse Gudang</h3>
                                <div class="ml-auto flex items-center gap-2">
                                    <input type="text" x-model="keyword" @keydown.enter.prevent="search()"
                                        placeholder="Cari kode / nama…" class="border rounded px-3 py-2 w-64">
                                    <button type="button" @click="search()"
                                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Search</button>
                                </div>
                            </div>

                            <div class="p-0 overflow-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-100 sticky top-0">
                                        <tr>
                                            <th class="text-left p-2">Gudang (Kode - Nama)</th>
                                            <th class="text-left p-2 w-40">Branch</th>
                                            <th class="text-center p-2 w-28">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="w in rows" :key="w.fwhid">
                                            <tr class="border-b hover:bg-gray-50">
                                                <td class="p-2" x-text="`${w.fwhcode} - ${w.fwhname}`"></td>
                                                <td class="p-2" x-text="w.fbranchcode || '-'"></td>
                                                <td class="p-2 text-center">
                                                    <button type="button" @click="choose(w)"
                                                        class="px-3 py-1 rounded text-xs bg-emerald-600 hover:bg-emerald-700 text-white">
                                                        Pilih
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                        <tr x-show="rows.length === 0">
                                            <td colspan="3" class="p-4 text-center text-gray-500">Tidak ada data.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
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

                    {{-- Modal Account --}}
                    <div x-data="accountBrowser()" x-show="open" x-cloak x-transition.opacity
                        class="fixed inset-0 z-50 flex items-center justify-center">
                        <div class="absolute inset-0 bg-black/40" @click="close()"></div>

                        <div class="relative bg-white rounded-2xl shadow-xl w-[92vw] max-w-4xl max-h-[85vh] flex flex-col">
                            <div class="p-4 border-b flex items-center gap-3">
                                <h3 class="text-lg font-semibold">Browse Account</h3>
                                <div class="ml-auto flex items-center gap-2">
                                    <input type="text" x-model="keyword" @keydown.enter.prevent="search()"
                                        placeholder="Cari kode / nama…" class="border rounded px-3 py-2 w-64">
                                    <button type="button" @click="search()"
                                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Search</button>
                                </div>
                            </div>

                            <div class="p-0 overflow-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-100 sticky top-0">
                                        <tr>
                                            <th class="text-left p-2">Account (Kode - Nama)</th>
                                            <th class="text-center p-2 w-28">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="w in rows" :key="w.faccount">
                                            <tr class="border-b hover:bg-gray-50">
                                                <td class="p-2" x-text="`${w.faccount} - ${w.faccname}`"></td>
                                                <td class="p-2 text-center">
                                                    <button type="button" @click="choose(w)"
                                                        class="px-3 py-1 rounded text-xs bg-emerald-600 hover:bg-emerald-700 text-white">
                                                        Pilih
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                        <tr x-show="rows.length === 0">
                                            <td colspan="3" class="p-4 text-center text-gray-500">Tidak ada data.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
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

                    @php
                        $canApproval = in_array('approvalpr', explode(',', session('user_restricted_permissions', '')));
                    @endphp

                    {{-- APPROVAL & ACTIONS --}}
                    <div class="md:col-span-2 flex justify-center items-center space-x-2 mt-6">
                        @if ($canApproval)
                            <label class="block text-sm font-medium">Approval</label>

                            {{-- fallback 0 saat checkbox tidak dicentang --}}
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
                        <button type="button" @click="window.location.href='{{ route('tr_poh.index') }}'"
                            class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                            <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" /> Keluar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

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

    // Modal supplier
    function supplierBrowser() {
        return {
            open: false,
            keyword: '',
            page: 1,
            lastPage: 1,
            perPage: 10,
            total: 0,
            rows: [],
            apiUrl() {
                const u = new URL("{{ route('suppliers.browse') }}", window.location.origin);
                u.searchParams.set('q', this.keyword || '');
                u.searchParams.set('per_page', this.perPage);
                u.searchParams.set('page', this.page);
                return u.toString();
            },
            async fetch() {
                try {
                    const res = await fetch(this.apiUrl(), {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const j = await res.json();
                    this.rows = j.data || [];
                    this.page = j.current_page || 1;
                    this.lastPage = j.last_page || 1;
                    this.total = j.total || 0;
                } catch (e) {
                    this.rows = [];
                    this.page = 1;
                    this.lastPage = 1;
                    this.total = 0;
                }
            },
            openBrowse() {
                this.open = true;
                this.page = 1;
                this.fetch();
            },
            close() {
                this.open = false;
                this.keyword = '';
                this.rows = [];
            },
            search() {
                this.page = 1;
                this.fetch();
            },
            prev() {
                if (this.page > 1) {
                    this.page--;
                    this.fetch();
                }
            },
            next() {
                if (this.page < this.lastPage) {
                    this.page++;
                    this.fetch();
                }
            },
            choose(s) {
                const sel = document.getElementById('supplierSelect');
                const hid = document.getElementById('supplierCodeHidden');
                if (!sel) {
                    this.close();
                    return;
                }
                let opt = [...sel.options].find(o => o.value == String(s.fsupplierid));
                const label = `${s.fsuppliercode} - ${s.fsuppliername}`;
                if (!opt) {
                    opt = new Option(label, s.fsupplierid, true, true);
                    sel.add(opt);
                } else {
                    opt.text = label;
                    opt.selected = true;
                }
                sel.dispatchEvent(new Event('change'));
                if (hid) hid.value = s.fsupplierid;
                this.close();
            },
            init() {
                window.addEventListener('supplier-browse-open', () => this.openBrowse(), {
                    passive: true
                });
            }
        }
    }

    // Modal produk
    function productBrowser() {
        return {
            open: false,
            forEdit: false,
            keyword: '',
            page: 1,
            lastPage: 1,
            perPage: 10,
            total: 0,
            rows: [],
            apiUrl() {
                const u = new URL("{{ route('products.browse') }}", window.location.origin);
                u.searchParams.set('q', this.keyword || '');
                u.searchParams.set('per_page', this.perPage);
                u.searchParams.set('page', this.page);
                return u.toString();
            },
            async fetch() {
                try {
                    const res = await fetch(this.apiUrl(), {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const j = await res.json();
                    this.rows = j.data || [];
                    this.page = j.current_page || 1;
                    this.lastPage = j.last_page || 1;
                    this.total = j.total || 0;
                } catch (e) {
                    this.rows = [];
                    this.page = 1;
                    this.lastPage = 1;
                    this.total = 0;
                }
            },
            close() {
                this.open = false;
                this.keyword = '';
                this.rows = [];
            },
            search() {
                this.page = 1;
                this.fetch();
            },
            prev() {
                if (this.page > 1) {
                    this.page--;
                    this.fetch();
                }
            },
            next() {
                if (this.page < this.lastPage) {
                    this.page++;
                    this.fetch();
                }
            },
            choose(p) {
                window.dispatchEvent(new CustomEvent('product-chosen', {
                    detail: {
                        product: p,
                        forEdit: this.forEdit
                    }
                }));
                this.close();
            },
            init() {
                window.addEventListener('browse-open', (e) => {
                    this.open = true;
                    this.forEdit = !!(e.detail && e.detail.forEdit);
                    this.page = 1;
                    this.fetch();
                }, {
                    passive: true
                });
            },
        }
    }

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
            savedItems: [],
            draft: newRow(),
            editingIndex: null,
            editRow: newRow(),

            totalHarga: 0,
            ppnRate: 11,

            initialGrandTotal: @json($famountmt ?? 0),
            initialPpnAmount: @json($famountpajak ?? 0),

            includePPN: false, // tambah PPN normal di luar total
            fapplyppn: false, // harga sudah termasuk PPN (back-calc)
            // PPN yang SUDAH termasuk (back-calc dari GROSS)
            get ppnIncluded() {
                const total = +this.totalHarga || 0;
                const rate = +this.ppnRate || 0;
                if (!this.fapplyppn) return 0;
                // back-calc from GROSS
                return Math.round((100 / (100 + rate)) * total * (rate / 100));
            },

            // NET dari GROSS jika fapplyppn aktif
            get netFromGross() {
                const total = +this.totalHarga || 0;
                return total - this.ppnIncluded;
            },

            // PPN tambahan (di luar total). Jika sudah include PPN, base = NET (tidak pajak atas pajak)
            get ppnAdded() {
                const rate = +this.ppnRate || 0;
                if (!this.includePPN) return 0;

                const total = +this.totalHarga || 0;

                // When both are ON, compute extra PPN on GROSS (not NET)
                const base = this.fapplyppn ? total : total; // <— effectively: always use total (GROSS)

                return Math.round(base * (rate / 100));
            },

            get ppnAmount() {
                // Jika dua checkbox aktif → tampilkan PPN tambahan saja (hindari double count)
                if (this.includePPN && this.fapplyppn) {
                    return this.ppnAdded;
                }
                // Kasus lain: gabungan PPN yang sudah termasuk + PPN tambahan
                return (this.ppnIncluded ?? 0) + (this.ppnAdded ?? 0);
            },

            get grandTotal() {
                const total = +this.totalHarga || 0;
                if (this.includePPN) return total + this.ppnAdded; // GROSS + extra PPN on GROSS
                if (this.includePPN) return total + this.ppnAdded; // NET + PPN
                if (this.fapplyppn) return total; // GROSS stays GROSS
                return total;
            },

            fmt(n) {
                if (n === null || n === undefined || n === '') return '-';
                const v = Number(n);
                if (!isFinite(v)) return '-';

                // Jika angka adalah bulat, hilangkan desimal
                if (Number.isInteger(v)) {
                    return v.toLocaleString('id-ID'); // Format sebagai angka bulat
                } else {
                    return v.toLocaleString('id-ID', {
                        style: 'currency',
                        currency: 'IDR'
                    }); // Jika angka desimal, tampilkan dalam format mata uang
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
                row.fqty = Math.max(0, +row.fqty || 0);
                row.fterima = Math.max(0, +row.fterima || 0);
                row.fprice = Math.max(0, +row.fprice || 0);
                row.fdiscpersen = Math.min(100, Math.max(0, +row.fdiscpersen || 0));
                row.ftotprice = +(row.fqty * row.fprice * (1 - row.fdiscpersen / 100)).toFixed(2);
                this.recalcTotals();
            },

            recalcTotals() {
                this.totalHarga = this.savedItems.reduce((sum, item) => sum + item.ftotprice, 0);
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
                row.fsatuan = row.fsatuan;
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
                const existing = new Set(this.getCurrentItemKeys()); // gunakan helper

                let added = 0,
                    duplicates = [];

                items.forEach(src => {
                    const row = {
                        uid: cryptoRandom(),
                        fitemcode: src.fitemcode ?? '',
                        fitemname: src.fitemname ?? '',
                        fsatuan: src.fsatuan ?? '',
                        frefdtno: src.frefdtno ?? '',
                        fnouref: src.fnouref ?? '',
                        frefpr: src.frefpr ?? (header?.fpono ?? ''),
                        fprnoid: src.fprnoid ?? header?.fprnoid ?? '',
                        fqty: Number(src.fqty ?? 0),
                        fterima: Number(src.fterima ?? 0),
                        fprice: Number(src.fprice ?? 0),
                        fdiscpersen: Number(src.fdiscpersen ?? 0),
                        ftotprice: Number(src.ftotprice ?? 0),
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

                const dupe = this.savedItems.find(it => it.fitemcode === r.fitemcode && it.fsatuan === r.fsatuan && (it
                    .frefpr || '') === (r.frefpr || ''));
                if (dupe) {
                    alert('Item sama sudah ada.');
                    return;
                }

                this.savedItems.push({
                    ...r,
                    uid: cryptoRandom()
                });
                this.showNoItems = false;
                this.resetDraft(); // Reset draft setelah item ditambahkan
                this.$nextTick(() => this.$refs.draftCode?.focus());
                this.syncDescList?.();
                this.showNoItems = false;

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

                // Recalculate totals after applying edit
                this.recalcTotals();
            },

            cancelEdit() {
                this.editingIndex = null;
                this.editRow = newRow();
            },

            removeSaved(i) {
                this.savedItems.splice(i, 1);
                this.syncDescList?.();
            },

            resetDraft() {
                this.draft = newRow();
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
                this.$watch('includePPN', () => this.recalcTotals());
                this.$watch('fapplyppn', () => this.recalcTotals());
                this.$watch('ppnRate', () => this.recalcTotals());

                // Listen for PR picked from modal PR
                window.getCurrentItemKeys = () => this.getCurrentItemKeys();
                window.addEventListener('pr-picked', this.onPrPicked.bind(this), {
                    passive: true
                });

                // Listen for product picked from product modal
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
                frefdtno: '',
                fnouref: '',
                frefpr: '',
                fqty: 0,
                fterima: 0,
                fprice: 0,
                fdiscpersen: 0,
                fbiaya: 0,
                ftotprice: 0,
                fdesc: '',
                fketdt: '',
                maxqty: 0,
            };
        }

        function cryptoRandom() {
            return (window.crypto?.getRandomValues ? [...window.crypto.getRandomValues(new Uint32Array(2))].map(n => n
                .toString(16)).join('') : Math.random().toString(36).slice(2)) + Date.now();
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

                    const res = await fetch(`{{ route('tr_poh.pickable') }}?` + params.toString(), {
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
                    const url = `{{ route('tr_poh.items', ['id' => 'PR_ID_PLACEHOLDER']) }}`
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
</script>

<script>
    window.warehouseBrowser = function() {
        return {
            open: false,
            keyword: '',
            rows: [],
            page: 1,
            lastPage: 1,
            total: 0,
            perPage: 10,
            loading: false,

            async fetch() {
                this.loading = true;
                try {
                    const params = new URLSearchParams({
                        search: this.keyword ?? '',
                        page: this.page,
                        per_page: this.perPage,
                    });
                    const res = await fetch(`{{ route('gudang.browse') }}?` + params.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const json = await res.json();
                    this.rows = json.data ?? [];
                    this.page = json.current_page ?? 1;
                    this.lastPage = json.last_page ?? 1;
                    this.total = json.total ?? (json.data_total ?? 0);
                } catch (e) {
                    console.error(e);
                    this.rows = [];
                } finally {
                    this.loading = false;
                }
            },

            search() {
                this.page = 1;
                this.fetch();
            },
            next() {
                if (this.page < this.lastPage) {
                    this.page++;
                    this.fetch();
                }
            },
            prev() {
                if (this.page > 1) {
                    this.page--;
                    this.fetch();
                }
            },

            openModal() {
                this.open = true;
                this.search();
            },
            close() {
                this.open = false;
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
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        window.addEventListener('warehouse-picked', (ev) => {
            const {
                fwhcode,
                fwhid
            } = ev.detail || {};
            const sel = document.getElementById('warehouseSelect');
            const hid = document.getElementById('warehouseIdHidden');
            if (sel) {
                sel.value = fwhcode || '';
                sel.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }
            if (hid) hid.value = fwhid || '';
        });
    });
</script>

<script>
    window.accountBrowser = function() {
        return {
            open: false,
            keyword: '',
            rows: [],
            page: 1,
            lastPage: 1,
            total: 0,
            perPage: 10,
            loading: false,

            async fetch() {
                this.loading = true;
                try {
                    const params = new URLSearchParams({
                        search: this.keyword ?? '',
                        page: this.page,
                        per_page: this.perPage,
                    });
                    const res = await fetch(`{{ route('account.browse') }}?` + params.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const json = await res.json();
                    this.rows = json.data ?? [];
                    this.page = json.current_page ?? 1;
                    this.lastPage = json.last_page ?? 1;
                    this.total = json.total ?? (json.data_total ?? 0);
                } catch (e) {
                    console.error(e);
                    this.rows = [];
                } finally {
                    this.loading = false;
                }
            },

            search() {
                this.page = 1;
                this.fetch();
            },
            next() {
                if (this.page < this.lastPage) {
                    this.page++;
                    this.fetch();
                }
            },
            prev() {
                if (this.page > 1) {
                    this.page--;
                    this.fetch();
                }
            },

            openModal() {
                this.open = true;
                this.search();
            },
            close() {
                this.open = false;
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

    document.addEventListener('DOMContentLoaded', () => {
        window.addEventListener('account-picked', (ev) => {
            let {
                faccount,
                faccid
            } = ev.detail || {};

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
