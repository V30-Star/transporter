@extends('layouts.app')

@section('title', "Faktur Pembelian - New")

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

        .fpb-ket-biaya {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            align-items: start;
        }

        @media (min-width: 768px) {
            .fpb-ket-biaya {
                grid-template-columns: minmax(0, 72%) minmax(280px, 28%);
                gap: 1.5rem;
            }
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

        .hpp-box {
            border: 1px solid #e5e7eb;
            background-color: #f9fafb;
            transition: all 0.3s ease;
        }

        .hpp-box:hover {
            border-color: #2563eb;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .hpp-box button,
        .hpp-box button:hover,
        .hpp-box button:focus,
        .hpp-box button:disabled {
            background: #2563eb !important;
            background-color: #2563eb !important;
            color: #ffffff !important;
            border: 1px solid #2563eb !important;
            min-width: 120px;
            height: 42px;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            appearance: none !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            opacity: 1 !important;
            box-shadow: none !important;
        }
    </style>
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow p-0 overflow-hidden" role="alert">
            {{-- Header Strip --}}
            <div class="d-flex align-items-center px-4 py-3" style="background-color: #c0392b;">
                <i class="bi bi-exclamation-triangle-fill text-white me-2 fs-5"></i>
                <strong class="text-white fs-6">{{ "Gagal Menyimpan Data!" }}</strong>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert"
                    aria-label="Close"></button>
            </div>

            {{-- Body --}}
            <div class="px-4 py-3" style="background-color: #fdeded; border-left: 5px solid #c0392b;">
                <p class="mb-2 text-danger fw-semibold">
                    <i class="bi bi-info-circle me-1"></i>
                    {{ "Periksa kembali data berikut sebelum menyimpan:" }}
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
        $includePPN = old('fapplyppn', 0);
        $ppnMode    = old('fincludeppn', 0);
        $ppnRate    = old('ppn_rate', 11);
        $currentType = old('ftypebuy', '0');
        $currentAccount = old('fprdjadi', '');
        $currentAccountId = old('faccid', '');

        $oldCodes = old('fitemcode', []);
        $oldSatuans = old('fsatuan', []);
        $oldRefDtnos = old('frefdtno', []);
        $oldRefDtids = old('frefdtid', []);
        $oldRefNoAcaks = old('frefnoacak', []);
        $oldSources = old('fsource', []);
        $oldFnourefs = old('fnouref', []);
        $oldRefprs = old('frefpr', []);
        $oldQtys = old('fqty', []);
        $oldPrices = old('fprice', []);
        $oldBiayas = old('fbiaya', []);
        $oldDiscs = old('fdiscpersen', []);
        $oldTotals = old('ftotal', []);
        $oldDescs = old('fdesc', []);
        $oldKetdts = old('fketdt', []);

        $initialFakturItems = [];
        $oldRowCount = count($oldCodes);
        for ($i = 0; $i < $oldRowCount; $i++) {
            $code = trim((string) ($oldCodes[$i] ?? ''));
            if ($code === '') {
                continue;
            }

            $initialFakturItems[] = [
                'uid' => 'old-'.$i,
                'fitemcode' => $code,
                'fitemname' => '',
                'fsatuan' => (string) ($oldSatuans[$i] ?? ''),
                'frefdtno' => (string) ($oldRefDtnos[$i] ?? ''),
                'frefdtid' => $oldRefDtids[$i] ?? null,
                'frefnoacak' => (string) ($oldRefNoAcaks[$i] ?? ''),
                'fsource' => (string) ($oldSources[$i] ?? ''),
                'fnouref' => (string) ($oldFnourefs[$i] ?? ''),
                'frefpr' => (string) ($oldRefprs[$i] ?? ''),
                'fqty' => (float) ($oldQtys[$i] ?? 0),
                'fprice' => (float) ($oldPrices[$i] ?? 0),
                'fbiaya' => (float) ($oldBiayas[$i] ?? 0),
                'fdiscpersen' => (string) ($oldDiscs[$i] ?? 0),
                'ftotprice' => (float) ($oldTotals[$i] ?? 0),
                'fdesc' => (string) ($oldDescs[$i] ?? ''),
                'fketdt' => (string) ($oldKetdts[$i] ?? ''),
                'units' => [],
                'maxqty' => 0,
                'lockQty' => false,
                'hideQtyLimitHint' => false,
            ];
        }
    @endphp

    <div x-data="{ 
        open: true,
        selectedType: '{{ $currentType }}',
        selectedAccountCode: '{{ $currentAccount }}',
        selectedAccountId: '{{ $currentAccountId }}'
    }">
        <div class="lg:col-span-5">
            <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1600px] w-full mx-auto">
                <form action="{{ route('fakturpembelian.store') }}" method="POST" class="mt-6" data-form-draft="true"
                    data-draft-key="fakturpembelian:create" x-data="{ showNoItems: false }"
                    @submit.prevent="if (window.fakturPembelianItemsTable?.submitForm) { window.fakturPembelianItemsTable.submitForm($el); } else { const n = Number(document.getElementById('itemsCount')?.value || 0); if (n < 1) { showNoItems = true } else { $el.submit() } }">
                    @csrf

                    {{-- HEADER FORM --}}
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">{{ "Cabang" }}</label>
                            <input type="text" class="w-full border rounded px-3 py-2 bg-gray-200 cursor-not-allowed"
                                value="{{ $fcabang }}" disabled>
                            <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                        </div>

                        <div class="lg:col-span-4" x-data="{ autoCode: true }">
                            <label class="block text-sm font-medium mb-1">{{ "No.Transaksi" }}#</label>
                            <div class="flex items-center gap-3">
                                <input type="text" name="fstockmtno" class="w-full border rounded px-3 py-2"
                                    :disabled="autoCode"
                                    :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'">
                                <label class="inline-flex items-center select-none">
                                    <input type="checkbox" x-model="autoCode" checked>
                                    <span class="ml-2 text-sm text-gray-700">{{ "Auto" }}</span>
                                </label>
                            </div>
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">{{ "Type" }}</label>
                            <select name="ftypebuy" x-model="selectedType"
                                class="w-full border rounded px-3 py-2 @error('ftypebuy') border-red-500 @enderror">
                                <option value="0" {{ old('ftypebuy') == '0' ? 'selected' : '' }}>{{ "Stok" }}</option>
                                <option value="1" {{ old('ftypebuy') == '1' ? 'selected' : '' }}>{{ "Non Stok" }}</option>
                                <option value="2" {{ old('ftypebuy') == '2' ? 'selected' : '' }}>{{ "Uang Muka" }}</option>
                            </select>
                            @error('ftypebuy')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">{{ "Tanggal" }}</label>
                            <input type="date" id="fstockmtdate" name="fstockmtdate"
                                value="{{ old('fstockmtdate') ?? date('Y-m-d') }}"
                                class="w-full border rounded px-3 py-2 @error('fstockmtdate') border-red-500 @enderror">
                            @error('fstockmtdate')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium mb-1">{{ "Supplier" }}</label>
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
                                    <div id="supplierBrowseOverlay" class="absolute inset-0" role="button" aria-label="Browse supplier"
                                        @click="window.dispatchEvent(new CustomEvent('supplier-browse-open'))"></div>
                                </div>
                                <input type="hidden" name="fsupplier" id="supplierCodeHidden"
                                    value="{{ old('fsupplier') }}">
                                <button type="button" id="supplierBrowseButton"
                                    @click="window.dispatchEvent(new CustomEvent('supplier-browse-open'))"
                                    class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                                    title="Browse Supplier">
                                    <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                </button>
                                <a href="{{ route('supplier.create') }}" target="_blank" rel="noopener" id="supplierCreateButton"
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
                            <label class="block text-sm font-medium mb-1">{{ "Gudang" }}</label>
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
                                    @click="window.dispatchEvent(new CustomEvent('faktur-pembelian-warehouse-browse-open'))"></div>
                                </div>

                                <input type="hidden" name="ffrom" id="warehouseCodeHidden" value="{{ old('ffrom') }}">
                                <button type="button"
                                    @click="window.dispatchEvent(new CustomEvent('faktur-pembelian-warehouse-browse-open'))"
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
                            <label class="block text-sm font-medium mb-1">{{ "Account" }}</label>
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
                                            <option value="{{ $account->faccount }}"
                                                data-faccid="{{ $account->faccid }}"
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
                            <label class="block text-npsm font-medium">Faktur</label>
                            <div class="flex items-center gap-3">
                                <input type="number" name="frefno" required class="w-full border rounded px-3 py-2">
                                <label class="inline-flex items-center select-none">
                                </label>
                            </div>
                        </div>

                        <div class="lg:col-span-2">
                            <label class="block text-sm font-medium">TOP (Hari)</label>
                            <input type="number" id="ftempohr" name="ftempohr" value="{{ old('ftempohr', '0') }}"
                                class="w-full border rounded px-3 py-2 @error('ftempohr') border-red-500 @enderror"
                                placeholder="Masukkan jumlah hari">
                            @error('ftempohr')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="lg:col-span-2">
                            <label class="block text-sm font-medium">Tgl. Jatuh Tempo</label>
                            <input type="date" id="fjatuhtempo" name="fjatuhtempo"
                                value="{{ old('fjatuhtempo') ?? date('Y-m-d') }}" readonly
                                class="w-full border rounded px-3 py-2 bg-gray-100 @error('fjatuhtempo') border-red-500 @enderror">
                            @error('fjatuhtempo')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>

                    <script>
                        function calculateDueDate() {
                            const poDate = document.getElementById('fstockmtdate').value;
                            const tempoDays = parseInt(document.getElementById('ftempohr').value) || 0;

                            // --- LOGIKA DIPERBAIKI ---
                            if (poDate) {
                                // JIKA poDate ada, LAKUKAN kalkulasi
                                const date = new Date(poDate);
                                date.setMinutes(date.getMinutes() + date.getTimezoneOffset()); // Fix Timezone

                                // Menambah 0 hari tidak akan mengubah tanggal, dan itu benar
                                date.setDate(date.getDate() + tempoDays);

                                // Format ke YYYY-MM-DD
                                const year = date.getFullYear();
                                const month = String(date.getMonth() + 1).padStart(2, '0');
                                const day = String(date.getDate()).padStart(2, '0');

                                document.getElementById('fjatuhtempo').value = `${year}-${month}-${day}`;

                            } else {
                                // HANYA JIKA poDate kosong, baru kosongkan jatuh tempo
                                document.getElementById('fjatuhtempo').value = '';
                            }
                        }

                        // Event listener Anda (ini sudah benar)
                        document.getElementById('fstockmtdate').addEventListener('change', calculateDueDate);
                        document.getElementById('ftempohr').addEventListener('input', calculateDueDate);
                        document.addEventListener('DOMContentLoaded', calculateDueDate);
                    </script>

                    <div x-data="itemsTable()" x-init="init()" class="mt-6 space-y-2">
                        <div class="fpb-ket-biaya">
                            <div class="min-w-0">
                                <label class="block text-sm font-medium">Keterangan</label>
                                <textarea name="fket" rows="2"
                                    class="w-full h-[96px] resize-none border rounded px-3 py-2 @error('fket') border-red-500 @enderror"
                                    placeholder="Tulis keterangan tambahan di sini...">{{ old('fket') }}</textarea>
                                @error('fket')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="min-w-0 overflow-hidden">
                                <label class="block text-sm font-medium">Hitung Biaya</label>
                                <div
                                    class="hpp-box h-[96px] bg-gray-50 p-3 rounded-lg border border-gray-200 shadow-sm flex items-center gap-3">
                                    <input type="number" x-model.number="biayaGlobal" :disabled="hasTerSourceItems"
                                        placeholder="Masukkan Total Ongkir"
                                        :class="hasTerSourceItems ? 'flex-1 border rounded px-3 py-2 text-right font-mono bg-gray-100 cursor-not-allowed text-gray-600' : 'flex-1 border rounded px-3 py-2 text-right font-mono bg-white'">

                                    <button type="button" @click="alokasiBiaya()" :disabled="hasTerSourceItems"
                                        style="background-color: #2563eb; color: #ffffff;"
                                        :class="hasTerSourceItems ? 'shrink-0 min-w-[120px] text-white font-medium py-2 px-4 rounded transition flex items-center justify-center gap-2 cursor-not-allowed opacity-80' : 'shrink-0 min-w-[120px] hover:bg-blue-700 text-white font-medium py-2 px-4 rounded transition flex items-center justify-center gap-2'">
                                        Hitung
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- DETAIL ITEM (tabel input) --}}
                        <h3 class="text-base font-semibold text-gray-800">Detail Item</h3>

                        <div class="overflow-x-auto border rounded">
                            <table class="min-w-full text-sm balanced-detail-table fpb-detail-table" data-skip-auto-detail-style="true">
                                    <colgroup>
                                        <col style="width:3%;">
                                        <col style="width:17%;">
                                        <col style="width:20%;">
                                        <col style="width:14%;">
                                        <col style="width:6%;">
                                        <col style="width:6%;">
                                        <col style="width:9%;">
                                        <col style="width:6%;">
                                        <col style="width:6%;">
                                        <col style="width:9%;">
                                        <col style="width:6%;">
                                    </colgroup>
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-2 py-1 text-left w-10">#</th>
                                        <th class="px-2 py-1 text-left">Kode Produk</th>
                                        <th class="px-2 py-1 text-left">Nama Produk</th>
                                        <th class="px-2 py-1 text-left">No Refrensi</th>
                                        <th class="px-2 py-1 text-left">Satuan</th>
                                        <th class="px-2 py-1 text-right whitespace-nowrap">Qty.</th>
                                        <th class="px-2 py-1 text-right whitespace-nowrap">@ Harga</th>
                                        <th class="px-2 py-1 text-right whitespace-nowrap">@ Biaya</th>
                                        <th class="px-2 py-1 text-right whitespace-nowrap">Disc. %</th>
                                        <th class="px-2 py-1 text-right whitespace-nowrap">Total Harga</th>
                                        <th class="px-2 py-1 text-center">Aksi</th>
                                    </tr>
                                </thead>

                                    <template x-for="(it, i) in savedItems" :key="it.uid">
                                        <tbody>
                                        <!-- ROW UTAMA -->
                                        <tr class="border-t align-top transition-colors" :class="activeRow === it.uid ? 'bg-amber-50' : 'hover:bg-gray-50'">
                                            <td class="p-2 text-gray-500" x-text="i + 1"></td>
                                            
                                            <!-- Kode Produk -->
                                            <td class="p-2">
                                                <div class="flex w-full max-w-full">
                                                    <input type="text"
                                                        class="min-w-0 flex-1 border rounded-l px-2 py-1 font-mono text-sm"
                                                        x-model.trim="it.fitemcode" @focus="activeRow = it.uid"
                                                        @blur="activeRow = null" @input="onCodeTypedRow(it, i)"
                                                        @keydown.enter.prevent="$refs['qty_saved_' + i]?.focus()">
                                                    <button type="button" @click="openBrowseFor('saved', i)"
                                                        class="shrink-0 border border-l-0 px-2 py-1 bg-white hover:bg-gray-50"
                                                        title="Cari Produk">
                                                        <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </td>

                                            <!-- Nama Produk -->
                                            <td class="p-2">
                                                <div class="flex w-full max-w-full">
                                                    <div
                                                        class="min-w-0 flex-1 rounded-l border bg-gray-100 px-2 py-1 text-sm leading-5 text-gray-600 whitespace-normal break-words"
                                                        x-text="it.fitemname"></div>
                                                    <button type="button" @click="openDesc('saved', i)"
                                                        class="shrink-0 inline-flex items-center border border-l-0 rounded-r px-2 py-1 transition-colors"
                                                        :class="descButtonClass(it.fdesc)"
                                                        title="Deskripsi">
                                                        <x-heroicon-o-document-text class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </td>

                                            <!-- No Refrensi -->
                                            <td class="p-2">
                                                <input type="text"
                                                    class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                                    :value="it.frefdtno || '-'" disabled placeholder="No Ref">
                                            </td>

                                            <!-- Satuan -->
                                            <td class="p-2 align-top">
                                                <template x-if="it.units && it.units.length > 1 && !it.frefdtid">
                                                    <select class="w-full border rounded px-2 py-1 text-sm"
                                                        :id="'unit_saved_' + i"
                                                        x-model="it.fsatuan"
                                                        @focus="activeRow = it.uid" @blur="activeRow = null"
                                                        @keydown.enter.prevent="$refs['qty_saved_' + i]?.focus()">
                                                        <template x-for="u in it.units" :key="u">
                                                            <option :value="u" x-text="u"></option>
                                                        </template>
                                                    </select>
                                                </template>
                                                <input type="text" x-show="!it.units || it.units.length <= 1 || it.frefdtid"
                                                    class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                                    :value="it.fsatuan || '-'" disabled>
                                            </td>

                                            <!-- Qty -->
                                            <td class="p-2 text-right">
                                                <input type="number" class="border rounded px-2 py-1 w-full text-right"
                                                    x-model.number="it.fqty" :id="'qty_saved_' + i" step="any"
                                                    @focus="activeRow = it.uid; $event.target.select()" @blur="activeRow = null; enforceQtyRow(it);"
                                                    @input="onRowUpdated(i)"
                                                    @change="onRowUpdated(i)"
                                                    @keydown.enter.prevent="$refs['price_saved_' + i]?.focus()">
                                                <div class="text-[10px] text-slate-500 text-right mt-0.5"
                                                    x-show="it.fsource === 'PO' || it.fsource === 'PB'"
                                                    x-text="formatSourceSummary(it)"></div>
                                                <div class="text-[10px] text-orange-600 font-medium text-right mt-0.5" x-show="it.fitemcode && productMeta(it.fitemcode).stock > 0" x-html="formatStockLimit(it.fitemcode, it.fqty, it.fsatuan)">
                                                </div>
                                            </td>

                                            <!-- @ Harga -->
                                            <td class="p-2 text-right">
                                                <input type="number" class="border rounded px-2 py-1 w-full text-right"
                                                    :disabled="hasTerSourceItems"
                                                    :class="hasTerSourceItems ? 'border rounded px-2 py-1 w-full text-right bg-gray-100 cursor-not-allowed text-gray-600' : 'border rounded px-2 py-1 w-full text-right'"
                                                    min="0" step="0.01" :value="Number(it.fprice || 0).toFixed(2)"
                                                    :id="'price_saved_' + i" @focus="activeRow = it.uid; $event.target.select()"
                                                    @blur="activeRow = null; $event.target.value = (+it.fprice || 0).toFixed(2)"
                                                    @input="it.fprice = +$event.target.value; recalc(it)" @change="recalc(it)"
                                                    @keydown.enter.prevent="$refs['biaya_saved_' + i]?.focus()">
                                            </td>

                                            <!-- @ Biaya -->
                                            <td class="p-2 text-right">
                                                <input type="number" class="border rounded px-2 py-1 w-full text-right"
                                                    :disabled="hasTerSourceItems"
                                                    :class="hasTerSourceItems ? 'border rounded px-2 py-1 w-full text-right bg-gray-100 cursor-not-allowed text-gray-600' : 'border rounded px-2 py-1 w-full text-right'"
                                                    min="0" step="0.01" :value="Number(it.fbiaya || 0).toFixed(2)"
                                                    :id="'biaya_saved_' + i" @focus="activeRow = it.uid; $event.target.select()"
                                                    @blur="activeRow = null; $event.target.value = (+it.fbiaya || 0).toFixed(2)"
                                                    @input="it.fbiaya = +$event.target.value; recalc(it)" @change="recalc(it)"
                                                    @keydown.enter.prevent="$refs['disc_saved_' + i]?.focus()">
                                            </td>

                                            <!-- Disc.% -->
                                            <td class="p-2 text-right">
                                                <input type="text" class="border rounded px-2 py-1 w-20 text-right text-sm"
                                                    :disabled="hasTerSourceItems"
                                                    :class="hasTerSourceItems ? 'border rounded px-2 py-1 w-20 text-right text-sm bg-gray-100 cursor-not-allowed text-gray-600' : 'border rounded px-2 py-1 w-20 text-right text-sm'"
                                                    placeholder="10+2" :value="it.fdiscpersen"
                                                    :id="'disc_saved_' + i" @focus="activeRow = it.uid; $event.target.select()"
                                                    @blur="activeRow = null; normalizeDiscountInput($event, it)"
                                                    @input="it.fdiscpersen = $event.target.value; recalc(it)" @change="recalc(it)">
                                            </td>

                                            <!-- Total Harga -->
                                            <td class="p-2">
                                                <input type="text"
                                                    class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm text-right"
                                                    :value="formatTransactionAmount(it.ftotprice)" disabled>
                                            </td>

                                            <!-- Aksi -->
                                            <td class="p-2 text-center">
                                                <div class="flex items-center justify-center gap-2">
                                                    <button type="button" @click="removeSaved(i)"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded bg-red-100 text-red-600 hover:bg-red-200"
                                                        title="Hapus baris">-</button>
                                                </div>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </template>

                            </table>
                        </div>
                        <div class="hidden" data-detail-payload></div>

                        <!-- ===== Trigger: Add PO & PB dari panel kanan ===== -->
                        <div>
                            <div class="mt-3 flex justify-between items-start gap-4">
                                <div class="w-full flex justify-start mb-3 gap-2">
                                    
                                    <!-- Trigger: Add PO -->
                                    <div x-data="poFormModal()">
                                        <button type="button" @click="openModal()" :disabled="isLocked()"
                                            :class="isLocked() ? 'cursor-not-allowed opacity-50' : 'hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500'"
                                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-white focus:outline-none">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                            Add PO
                                        </button>

                                        <!-- PO Modal -->
                                        <div x-show="show" x-transition.opacity class="fixed inset-0 z-40 bg-black/50" @keydown.escape.window="closeModal()"></div>
                                        <div>
                                            <div x-show="show" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center overflow-hidden p-3 md:p-6" aria-modal="true" role="dialog">
                                                <div class="relative w-full max-w-7xl rounded-xl bg-white shadow-2xl flex flex-col overflow-hidden" style="height: min(760px, calc(100vh - 1.5rem));">
                                                    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-emerald-50 to-white">
                                                        <h3 class="text-xl font-bold text-gray-800">{{ "Pilih Purchase Order (PO)" }}</h3>
                                                        <button type="button" @click="closeModal()" class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">{{ "Tutup" }}</button>
                                                    </div>
                                                    <div class="flex-1 overflow-hidden p-6" style="min-height: 0;">
                                                        <table id="poTable" class="min-w-full text-sm display nowrap stripe hover" style="width:100%">
                                                            <thead class="sticky top-0 z-10">
                                                                <tr class="bg-gray-50 border-b-2 border-gray-200">
                                                                    <th class="p-3 text-left font-semibold text-gray-700">{{ "PO No" }}</th>
                                                                    <th class="p-3 text-left font-semibold text-gray-700">{{ "Supplier" }}</th>
                                                                    <th class="p-3 text-left font-semibold text-gray-700">{{ "Tanggal" }}</th>
                                                                    <th class="p-3 text-center font-semibold text-gray-700">{{ "Aksi" }}</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody></tbody>
                                                        </table>
                                                    </div>
                                                    <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50"></div>
                                                </div>
                                            </div>
                                            <!-- Duplicate modal -->
                                            <div x-show="showDupModal" x-cloak x-transition.opacity class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                                                <div class="absolute inset-0 bg-black/40" @click="closeDupModal()"></div>
                                                <div class="relative bg-white rounded-xl shadow-xl max-w-2xl w-full p-6">
                                                    <h3 class="text-lg font-semibold mb-4">{{ "Peringatan Duplikasi" }}</h3>
                                                    <p class="mb-4">
                                                        {{ Str::before("Ditemukan :count item yang sudah ada dalam daftar. Hanya item unik yang akan ditambahkan.", '__COUNT__') }}<strong x-text="dupCount"></strong>{{ Str::after("Ditemukan :count item yang sudah ada dalam daftar. Hanya item unik yang akan ditambahkan.", '__COUNT__') }}
                                                    </p>
                                                    <div class="flex justify-end gap-2">
                                                        <button type="button" @click="closeDupModal()" class="rounded bg-gray-200 px-4 py-2 text-sm font-medium hover:bg-gray-300">{{ "Batal" }}</button>
                                                        <button type="button" @click="confirmAddUniques()" class="rounded bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">{{ "Tambahkan Item Unik" }}</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Trigger: Add PB -->
                                    <div x-data="pbFormModal()">
                                        <button type="button" @click="openModal()" :disabled="isLocked()"
                                            :class="isLocked() ? 'cursor-not-allowed opacity-50' : 'hover:bg-blue-700 focus:ring-2 focus:ring-blue-500'"
                                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-white focus:outline-none">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                            Add TER
                                        </button>

                                        <!-- PB Modal -->
                                        <div x-show="show" x-transition.opacity class="fixed inset-0 z-40 bg-black/50" @keydown.escape.window="closeModal()"></div>
                                        <div>
                                            <div x-show="show" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center overflow-hidden p-3 md:p-6" aria-modal="true" role="dialog">
                                                <div class="relative w-full max-w-7xl rounded-xl bg-white shadow-2xl flex flex-col overflow-hidden" style="height: min(760px, calc(100vh - 1.5rem));">
                                                    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
                                                        <h3 class="text-xl font-bold text-gray-800">{{ "Pilih Penerimaan Barang" }}</h3>
                                                        <button type="button" @click="closeModal()" class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">{{ "Tutup" }}</button>
                                                    </div>
                                                    <div class="flex-1 overflow-hidden p-6" style="min-height: 0;">
                                                        <table id="pbTable" class="min-w-full text-sm display nowrap stripe hover" style="width:100%">
                                                            <thead class="sticky top-0 z-10">
                                                                <tr class="bg-gray-50 border-b-2 border-gray-200">
                                                                    <th class="p-3 text-left font-semibold text-gray-700">{{ "No.Transaksi" }}</th>
                                                                    <th class="p-3 text-left font-semibold text-gray-700">{{ "Supplier" }}</th>
                                                                    <th class="p-3 text-left font-semibold text-gray-700">{{ "Tanggal" }}</th>
                                                                    <th class="p-3 text-center font-semibold text-gray-700">{{ "Aksi" }}</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody></tbody>
                                                        </table>
                                                    </div>
                                                    <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50"></div>
                                                </div>
                                            </div>
                                            <!-- Duplicate modal -->
                                            <div x-show="showDupModal" x-cloak x-transition.opacity class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                                                <div class="absolute inset-0 bg-black/40" @click="closeDupModal()"></div>
                                                <div class="relative bg-white rounded-xl shadow-xl max-w-2xl w-full p-6">
                                                    <h3 class="text-lg font-semibold mb-4">{{ "Peringatan Duplikasi" }}</h3>
                                                    <p class="mb-4">
                                                        {{ Str::before("Ditemukan :count item yang sudah ada dalam daftar. Hanya item unik yang akan ditambahkan.", '__COUNT__') }}<strong x-text="dupCount"></strong>{{ Str::after("Ditemukan :count item yang sudah ada dalam daftar. Hanya item unik yang akan ditambahkan.", '__COUNT__') }}
                                                    </p>
                                                    <div class="flex justify-end gap-2">
                                                        <button type="button" @click="closeDupModal()" class="rounded bg-gray-200 px-4 py-2 text-sm font-medium hover:bg-gray-300">{{ "Batal" }}</button>
                                                        <button type="button" @click="confirmAddUniques()" class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">{{ "Tambahkan Item Unik" }}</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                                <!-- Kanan: Panel Totals -->
                                <div class="w-1/2">
                                    <div class="rounded-lg border bg-gray-50 p-3 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-700">Total Harga</span>
                                            <span class="min-w-[140px] text-right font-medium"
                                                x-text="formatTransactionAmount(totalHarga)"></span>
                                        </div>

                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-700">Total DPP</span>
                                            <span class="min-w-[140px] text-right font-medium"
                                                x-text="rupiah(totalDPP)"></span>
                                        </div>

                                        <div class="flex items-center">
                                            <!-- Checkbox -->
                                            <div class="flex items-center gap-1">
                                                <input id="fapplyppn" type="checkbox" name="fapplyppn" value="1"
                                                    x-model="includePPN"
                                                    class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                <label for="fapplyppn" class="text-sm font-medium text-gray-700">
                                                    <span class="font-bold">PPN</span>
                                                </label>
                                            </div>

                                            <!-- Input Rate + Nominal (kanan) -->
                                            <div class="ml-2 flex items-center gap-1">
                                                <input type="number" min="0" max="100" step="0.01" name="ppn_rate"
                                                    x-model.number="ppnRate" :disabled="!includePPN"
                                                    class="w-20 h-9 px-2 text-sm leading-tight text-right border rounded transition-opacity
                                                            [appearance:textfield]
                                                            [&::-webkit-outer-spin-button]:appearance-none
                                                            [&::-webkit-inner-spin-button]:appearance-none
                                                            disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed">
                                                <span class="text-sm">%</span>
                                            </div>

                                            <span class="ml-auto min-w-[140px] text-right font-medium"
                                                x-text="rupiah(ppnAmount)"></span>
                                        </div>

                                        <div class="border-t my-1"></div>

                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-semibold text-gray-800">Grand Total</span>
                                            <span class="min-w-[140px] text-right text-lg font-semibold"
                                                x-text="rupiah(grandTotal)"></span>
                                        </div>

                                        <div class="flex items-center justify-between bg-blue-50 p-2 rounded">
                                            <span class="text-sm font-bold text-blue-700">Total Biaya (HPP)</span>
                                            <span class="min-w-[140px] text-right font-bold text-blue-700"
                                                x-text="rupiah(biayaGlobal)"></span>
                                        </div>
                                    </div>

                                    <!-- Hidden inputs for submit -->
                                    <input type="hidden" name="famount" :value="totalHarga">
                                    <input type="hidden" name="famountpajak" :value="ppnAmount">
                                    <input type="hidden" name="famountmt" :value="grandTotal">
                                    <input type="hidden" name="fincludeppn" value="0">
                                    <input type="hidden" name="famountpopajak" :value="ppnRate">
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

                                <div class="px-5 py-4 space-y-4">
                                    <div>
                                        <div class="mb-1 flex items-center justify-between gap-3">
                                            <div class="text-sm text-gray-700">Nama Produk</div>
                                            <button x-show="!descReadonly" type="button" @click="copyDescPayload()"
                                                class="h-8 px-3 rounded-lg bg-blue-50 text-blue-700 text-sm font-medium hover:bg-blue-100">
                                                Copy
                                            </button>
                                        </div>
                                        <div class="rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800"
                                            x-text="descItemName || '-'"></div>
                                    </div>
                                    <label class="block text-sm text-gray-700">Deskripsi</label>
                                    <textarea x-model="descValue" rows="5" class="w-full border rounded px-3 py-2"
                                        :readonly="descReadonly"
                                        placeholder="Tulis deskripsi item di sini..."></textarea>
                                </div>

                                <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
                                    <button type="button" @click="closeDesc()"
                                        class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">
                                        Batal
                                    </button>
                                    <button x-show="!descReadonly" type="button" @click="applyDesc()"
                                        class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
                                        Simpan
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div x-show="showSupplierRequired" x-cloak class="fixed inset-0 z-[94] flex items-center justify-center"
                            x-transition.opacity>
                            <div class="absolute inset-0 bg-black/50" @click="showSupplierRequired = false"></div>

                            <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden"
                                x-transition.scale>
                                <div class="px-5 py-4 border-b flex items-center">
                                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-amber-500 mr-2" />
                                    <h3 class="text-lg font-semibold text-gray-800">Pilih Supplier Dulu</h3>
                                </div>

                                <div class="px-5 py-4">
                                    <p class="text-sm text-gray-700">
                                        Supplier wajib dipilih sebelum input produk manual. Untuk Add PO atau Add TER,
                                        supplier tidak wajib dipilih terlebih dahulu.
                                    </p>
                                </div>

                                <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
                                    <button type="button"
                                        @click="showSupplierRequired = false; document.getElementById('modal_filter_supplier_id')?.focus()"
                                        class="h-9 px-4 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                                        OK
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div x-show="showDescCodeRequired" x-cloak class="fixed inset-0 z-[94] flex items-center justify-center"
                            x-transition.opacity>
                            <div class="absolute inset-0 bg-black/50" @click="showDescCodeRequired = false"></div>

                            <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden"
                                x-transition.scale>
                                <div class="px-5 py-4 border-b flex items-center">
                                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-amber-500 mr-2" />
                                    <h3 class="text-lg font-semibold text-gray-800">Isi Kode Produk Dulu</h3>
                                </div>

                                <div class="px-5 py-4">
                                    <p class="text-sm text-gray-700">
                                        Isi atau pilih kode produk terlebih dahulu sebelum mengisi deskripsi item.
                                    </p>
                                </div>

                                <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
                                    <button type="button"
                                        @click="showDescCodeRequired = false"
                                        class="h-9 px-4 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                                        OK
                                    </button>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" id="itemsCount" :value="submitItems.length">

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

                    <div x-show="showWarningModal" x-cloak class="fixed inset-0 z-[96] flex items-center justify-center"
                        x-transition.opacity>
                        <div class="absolute inset-0 bg-black/50" @click="closeWarning()"></div>
                        <div class="relative bg-white w-[92vw] max-w-lg rounded-2xl shadow-2xl overflow-hidden"
                            x-transition.scale>
                            <div class="px-5 py-4 border-b flex items-center">
                                <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-amber-500 mr-2" />
                                <h3 class="text-lg font-semibold text-gray-800" x-text="warningTitle"></h3>
                            </div>

                            <div class="px-5 py-4 space-y-3">
                                <p class="text-sm text-gray-700" x-text="warningMessage"></p>
                                <template x-if="warningItems.length > 0">
                                    <ul class="list-disc pl-5 text-sm text-gray-700 space-y-1">
                                        <template x-for="item in warningItems" :key="item">
                                            <li x-text="item"></li>
                                        </template>
                                    </ul>
                                </template>
                            </div>

                            <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
                                <button type="button" @click="closeWarning()"
                                    class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">
                                    Tutup
                                </button>
                                <button type="button" x-show="warningCanProceed" @click="confirmWarningAndSubmit()"
                                    class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
                                    Lanjut Simpan
                                </button>
                            </div>
                        </div>
                    </div>
                    </div>  </div>


                    <x-transaction.browse-supplier-modal />
                    <x-transaction.browse-product-modal show-controls="true" show-pagination="true" />
                    <x-transaction.browse-warehouse-modal event-name="faktur-pembelian-warehouse-browse-open" />
                    <x-transaction.browse-account-modal />

                    <div class="mt-8 flex justify-center gap-4">
                        <button type="submit"
                            class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                            <x-heroicon-o-check class="w-5 h-5 mr-2" /> Simpan
                        </button>
                        <button type="button" @click="window.location.href='{{ route('fakturpembelian.index') }}'"
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
<style>
    /* Targeting lebih spesifik untuk length select */
    div#productTable_length select,
    .dataTables_wrapper #productTable_length select,
    table#productTable+.dataTables_wrapper .dataTables_length select {
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
    div#warehouseTable_length select,
    .dataTables_wrapper #warehouseTable_length select,
    table#warehouseTable+.dataTables_wrapper .dataTables_length select {
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
    div#prTable_length select,
    .dataTables_wrapper #prTable_length select,
    table#supplierBrowseTable+.dataTables_wrapper .dataTables_length select {
        min-width: 140px !important;
        width: auto !important;
        padding: 8px 45px 8px 16px !important;
        font-size: 14px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.375rem !important;
    }

    /* Wrapper length */
    div#prTable_length,
    .dataTables_wrapper #prTable_length,
    .dataTables_wrapper .dataTables_length {
        min-width: 250px !important;
    }

    /* Label wrapper */
    div#prTable_length label,
    .dataTables_wrapper #prTable_length label,
    .dataTables_wrapper .dataTables_length label {
        font-size: 14px !important;
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
    }
</style>
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
            savedItems: @js($initialFakturItems),
            activeRow: null,
            browseTarget: null,
            browseIndex: null,
            showWarningModal: false,
            warningTitle: 'Perhatian',
            warningMessage: '',
            warningItems: [],
            warningCanProceed: false,
            pendingSubmitForm: null,
            pendingValidRows: [],

            biayaGlobal: 0,
            totalHarga: 0,
            ppnRate: 11,

            initialGrandTotal: @json($famountmt ?? 0),
            initialPpnAmount: @json($famountpajak ?? 0),

            includePPN: @json($includePPN == 1),
            ppnMode: @json((int)$ppnMode),
            ppnRate: @json((float)$ppnRate),

            get ppnAmount() {
                if (!this.includePPN) return 0;
                const total = +this.totalHarga || 0;
                const rate = +this.ppnRate || 0;
                if (this.ppnMode === 1) {
                    // Include: Back-calc from GROSS
                    return Math.round((rate / (100 + rate)) * total);
                } else {
                    // Exclude: Add on top of base
                    return Math.round(total * (rate / 100));
                }
            },

            get grandTotal() {
                const total = +this.totalHarga || 0;
                if (!this.includePPN || this.ppnMode === 1) return total;
                return total + this.ppnAmount;
            },

            get hasTerSourceItems() {
                return (this.savedItems || []).some((item) => (item?.fsource || '').toString().trim().toUpperCase() === 'PB');
            },

            fmt(n) {
                if (n === null || n === undefined || n === '') return '-';
                const v = Number(n);
                if (!isFinite(v)) return '-';

                return v.toLocaleString('id-ID', {
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

            normalizeMoneyInput(event, row, field) {
                const rawValue = row && field ? row[field] : event?.target?.value;
                const normalized = Math.max(0, Number(rawValue || 0));
                const rounded = Number(normalized.toFixed(2));

                if (row && field) {
                    row[field] = rounded;
                    this.recalc(row);
                }

                if (event?.target) {
                    event.target.value = rounded.toFixed(2);
                }
            },

            parseDiscount(value) {
                if (value === null || value === undefined || value === '') return 0;
                const cleaned = String(value).replace(/\s+/g, '');
                if (!cleaned) return 0;
                const parts = cleaned.split('+').filter(Boolean);
                if (!parts.length) return 0;

                let total = 0;
                for (const part of parts) {
                    const parsed = Number(part);
                    if (!Number.isFinite(parsed)) return 0;
                    total += parsed;
                }

                return Math.min(100, Math.max(0, total));
            },

            normalizeDiscountValue(value) {
                const cleaned = String(value ?? '').replace(/\s+/g, '');
                if (cleaned === '') return '0.00';
                if (!cleaned.includes('+')) {
                    const num = Number(cleaned);
                    if (Number.isFinite(num)) {
                        return num.toFixed(2);
                    }
                }
                return cleaned;
            },

            normalizeDiscountInput(event, row) {
                const normalized = this.normalizeDiscountValue(row?.fdiscpersen);
                if (row) {
                    row.fdiscpersen = normalized;
                    this.recalc(row);
                }
                if (event?.target) {
                    event.target.value = normalized;
                }
            },

            recalc(row) {
                row.fqty = Math.max(0, +row.fqty || 0);
                row.fprice = Math.max(0, +row.fprice || 0);
                row.fbiaya = Math.max(0, +row.fbiaya || 0);
                row.fdiscpersen = this.normalizeDiscountValue(row.fdiscpersen);
                const discPercent = this.parseDiscount(row.fdiscpersen);

                const basePrice = (row.fprice + row.fbiaya) * row.fqty;
                const diskon = (row.fqty * row.fprice) * (discPercent / 100);

                row.ftotprice = +(basePrice - diskon).toFixed(2);

                this.recalcTotals();
            },

            get totalDPP() {
                return this.savedItems.reduce((sum, item) => {
                    const hargaBarang = (item.fqty * item.fprice);
                    const diskon = hargaBarang * (this.parseDiscount(item.fdiscpersen) / 100);
                    return sum + (hargaBarang - diskon);
                }, 0);
            },

            recalcTotals() {
                this.totalHarga = this.savedItems.reduce((sum, item) => sum + (item.ftotprice || 0), 0);
                this.biayaGlobal = this.savedItems.reduce((sum, item) => sum + (item.fbiaya * item.fqty || 0), 0);
            },

            productMeta(code) {
                const key = (code || '').trim();
                const meta = window.PRODUCT_MAP?.[key];
                if (!meta) {
                    return {
                        name: '',
                        default_unit: '',
                        units: [],
                        stock: 0,
                        unit_ratios: { satuankecil: 1, satuanbesar: 1, satuanbesar2: 1 }
                    };
                }
                return meta;
            },

            formatStockLimit(code, qty, satuan) {
                return '';
            },

            qtyToKecil(code, qty, satuan) {
                const meta = this.productMeta(code);
                const units = meta?.units || [];
                const ratios = meta?.unit_ratios || { satuankecil: 1, satuanbesar: 1, satuanbesar2: 1 };
                const satKecil = units[0] || '';
                const satBesar = units[1] || '';
                const satBesar2 = units[2] || '';
                const value = Number(qty || 0);

                if (satuan === satBesar2 && Number(ratios.satuanbesar2) > 0) {
                    return value * Number(ratios.satuanbesar2);
                }
                if (satuan === satBesar && Number(ratios.satuanbesar) > 0) {
                    return value * Number(ratios.satuanbesar);
                }
                if (satuan === satKecil) {
                    return value;
                }

                return value;
            },

            kecilToUnit(code, qtyKecil, satuan) {
                const meta = this.productMeta(code);
                const units = meta?.units || [];
                const ratios = meta?.unit_ratios || { satuankecil: 1, satuanbesar: 1, satuanbesar2: 1 };
                const satKecil = units[0] || '';
                const satBesar = units[1] || '';
                const satBesar2 = units[2] || '';
                const value = Number(qtyKecil || 0);

                if (satuan === satBesar2 && Number(ratios.satuanbesar2) > 0) {
                    return value / Number(ratios.satuanbesar2);
                }
                if (satuan === satBesar && Number(ratios.satuanbesar) > 0) {
                    return value / Number(ratios.satuanbesar);
                }
                if (satuan === satKecil) {
                    return value;
                }

                return value;
            },

            formatSourceSummary(row) {
                const baseTerimaKecil = Number(row?.fqtyterima ?? 0) || 0;
                const baseRemainKecil = Number(row?.fqtyremain_source ?? row?.fqtykecil ?? row?.maxqty ?? 0) || 0;
                const currentQtyKecil = this.qtyToKecil(row?.fitemcode, row?.fqty, row?.fsatuan);
                const terimaPreview = baseTerimaKecil + currentQtyKecil;
                const remainPreviewKecil = Math.max(0, baseRemainKecil - currentQtyKecil);
                const sisaPreview = this.kecilToUnit(row?.fitemcode, remainPreviewKecil, row?.fsatuan);
                return '';
            },

            enforceQtyRow(row) {
                if (row?.lockQty) return;
                const n = +row.fqty;
                const meta = this.productMeta(row.fitemcode);
                const units = meta?.units || [];
                const ratios = meta?.unit_ratios || { satuankecil: 1, satuanbesar: 1, satuanbesar2: 1 };
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
                    return;
                }
                row.fitemname = meta.name || '';
                const preferredUnit = (row.fsatuan || '').toString().trim();
                const units = [...new Set((meta.units || []).map(u => (u ?? '').toString().trim()).filter(Boolean))];
                const defaultUnit = (meta.default_unit || '').toString().trim();
                const resolvedDefaultUnit = defaultUnit && units.includes(defaultUnit) ? defaultUnit : (units[0] || '');
                const matchedUnit = preferredUnit === '' ? '' : (units.find(u => u.toLowerCase() === preferredUnit.toLowerCase()) || '');

                row.units = matchedUnit !== ''
                    ? [matchedUnit, ...units.filter(u => u.toLowerCase() !== matchedUnit.toLowerCase())]
                    : units;

                if (forceDefaultUnit) {
                    row.fsatuan = resolvedDefaultUnit;
                } else if (matchedUnit !== '') {
                    row.fsatuan = matchedUnit;
                } else if (!row.units.includes(row.fsatuan)) {
                    row.fsatuan = resolvedDefaultUnit;
                }

                if (meta.unit_ratios) row.unit_ratios = meta.unit_ratios;
                const stock = Number.isFinite(+meta.stock) && +meta.stock > 0 ? +meta.stock : 0;
                row.maxqty = stock;
            },

            getSelectedSupplierCode() {
                return (document.getElementById('supplierCodeHidden')?.value || document.getElementById('modal_filter_supplier_id')?.value || '').trim();
            },

            requireSupplierBeforeManualProduct() {
                if (this.getSelectedSupplierCode()) return true;
                this.showSupplierRequired = true;
                return false;
            },

            setSupplierFromReferenceHeader(header) {
                const supplierCode = (header?.fsupplier || header?.fsuppliercode || '').toString().trim();
                if (!supplierCode) return;

                const hiddenInput = document.getElementById('supplierCodeHidden');
                const selectInput = document.getElementById('modal_filter_supplier_id');
                const tempoInput = document.getElementById('ftempohr');

                if (hiddenInput) {
                    hiddenInput.value = supplierCode;
                }

                if (selectInput) {
                    selectInput.value = supplierCode;
                    selectInput.dispatchEvent(new Event('change', { bubbles: true }));

                    const option = Array.from(selectInput.options || []).find(opt => (opt.value || '').trim() === supplierCode);
                    const tempo = Number(option?.getAttribute('data-tempo') || 0);
                    if (tempoInput && Number.isFinite(tempo)) {
                        tempoInput.value = tempo;
                        tempoInput.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }
            },
            hasSourceLockedSupplier() {
                return (this.savedItems || []).some(item => ['PO', 'PB'].includes((item?.fsource || '').toString().trim().toUpperCase()));
            },
            syncSupplierLockState() {
                const locked = this.hasSourceLockedSupplier();
                window.fpbSupplierBrowseLocked = locked;

                const overlay = document.getElementById('supplierBrowseOverlay');
                const browseButton = document.getElementById('supplierBrowseButton');
                const createButton = document.getElementById('supplierCreateButton');
                const selectInput = document.getElementById('modal_filter_supplier_id');

                if (overlay) overlay.style.pointerEvents = locked ? 'none' : 'auto';
                if (selectInput) selectInput.dataset.lockedBySource = locked ? '1' : '0';

                if (browseButton) {
                    browseButton.disabled = locked;
                    browseButton.classList.toggle('opacity-50', locked);
                    browseButton.classList.toggle('cursor-not-allowed', locked);
                }

                if (createButton) {
                    createButton.style.pointerEvents = locked ? 'none' : 'auto';
                    createButton.classList.toggle('opacity-50', locked);
                    createButton.classList.toggle('cursor-not-allowed', locked);
                }
            },

            minimumVisibleRows: 5,

            rowHasContent(row) {
                if (!row) return false;
                return this.isRowFilled(row);
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
                this.syncOpeningBalanceMode();
                this.recalcTotals();
                this.ensureTrailingRow(index);
            },

            onCodeTypedRow(row, index = null) {
                const typedCode = (row.fitemcode || '').toString().trim().toUpperCase();

                if (typedCode !== '' && !this.requireSupplierBeforeManualProduct()) {
                    row.fitemcode = '';
                    this.hydrateRowFromMeta(row, null);
                    return;
                }
                if (typedCode === 'AWAL' && this.hasSourceReferenceRows()) {
                    row.fitemcode = '';
                    this.hydrateRowFromMeta(row, null);
                    this.showOpeningBalanceMixWarning();
                    return;
                }
                this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode), true);
                this.onRowUpdated(index);
            },

            isComplete(row) {
                return row.fitemcode && row.fitemname && row.fsatuan && Number(row.fqty) > 0;
            },

            onPoPicked(e) {
                const {
                    header,
                    items
                } = e.detail || {};
                if (!items || !Array.isArray(items)) return;

                this.setSupplierFromReferenceHeader(header);
                this.addManyFromSource(header, items, 'PO');
            },

            onPbPicked(e) {
                const {
                    header,
                    items
                } = e.detail || {};
                if (!items || !Array.isArray(items)) return;

                this.setSupplierFromReferenceHeader(header);
                this.addManyFromSource(header, items, 'PB');
            },

            normalizeRefNoAcak(value) {
                const parts = String(value ?? '').split(',').map(part => part.trim()).filter(part => /^\d{3}$/.test(part));
                return [...new Set(parts)].join(',');
            },

            addManyFromSource(header, items, sourceType) {
                if (this.hasOpeningBalanceRows()) {
                    this.showOpeningBalanceMixWarning();
                    return;
                }

                const existing = new Set(this.getCurrentItemKeys());
                const toAdd = [];

                items.forEach(src => {
                    let fnourefVal = src.fnouref ?? src.fnou ?? '';
                    let frefdtnoVal = src.frefdtno ?? '';
                    if (sourceType === 'PO') {
                       frefdtnoVal = header?.fpono ?? '';
                    } else if (sourceType === 'PB') {
                       frefdtnoVal = header?.fstockmtno ?? '';
                    }

                    const sourceQty = Math.max(0, +(src.fqty ?? 0) || 0);
                    const sourceQtyKecil = Math.max(0, +(src.fqtykecil ?? src.fqtyremain ?? src.fqty ?? 0) || 0);
                    const sourceLimit = sourceQty > 0 ? sourceQty : sourceQtyKecil;

                    const row = {
                        uid: cryptoRandom(),
                        fitemcode: src.fitemcode ?? '',
                        fitemname: src.fitemname ?? '',
                        fsatuan: src.fsatuan ?? '',
                        frefdtno: frefdtnoVal,
                        frefdtid: src.frefdtid ?? src.frefdtno ?? null,
                        frefnoacak: this.normalizeRefNoAcak(src.frefnoacak ?? ''),
                        fsource: sourceType,
                        fnouref: fnourefVal,
                        frefpr: src.fnouref ?? fnourefVal,
                        fqtyterima: +(src.fqtyterima || 0),
                        fqtysisa_source: Number(src.fqtysisa ?? sourceLimit ?? 0),
                        fqtyremain_source: Number(src.fqtyremain ?? sourceQtyKecil ?? 0),

                        // Data quantity
                        fqty: sourceLimit,
                        maxqty: sourceLimit,
                        lockQty: false,

                        // Financial
                        fprice: +(src.fprice || 0),
                        fdiscpersen: this.normalizeDiscountValue(src.fdiscpersen ?? src.fdisc ?? 0),
                        fbiaya: +(src.fbiaya || 0),
                        ftotprice: +(src.fharga || 0),

                        fdesc: src.fdesc || '',
                        units: Array.isArray(src.units) && src.units.length ? src.units : [src.fsatuan].filter(Boolean)
                    };

                    const rawMeta = window.PRODUCT_MAP?.[(row.fitemcode || '').trim()];
                    if (rawMeta) {
                        this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode));
                    }
                    row.maxqty = sourceLimit;
                    if (!(Number(row.fqtysisa_source) > 0 || Number(row.maxqty) > 0)) return;
                    if (Number(row.fqtysisa_source) > 0) {
                        row.fqty = Number(row.fqtysisa_source);
                    } else if (Number(row.maxqty) > 0) {
                        row.fqty = Number(row.maxqty);
                    }
                    this.enforceQtyRow(row);

                    const key = this.itemKey(row);

                    if (existing.has(key)) {
                        return;
                    }

                    toAdd.push(row);
                    existing.add(key);
                    this.recalc(row);
                });

                if (toAdd.length > 0) {
                    const shouldReplaceStarter = this.savedItems.every((row) => !this.isRowFilled(row));
                    if (shouldReplaceStarter) {
                        this.savedItems = toAdd;
                    } else {
                        this.savedItems.push(...toAdd);
                    }
                }

                this.recalcTotals();
                this.ensureMinimumRows();
                this.ensureTrailingRow();
                this.syncSupplierLockState();
                this.syncOpeningBalanceMode();
            },

            removeSaved(i) {
                if (this.savedItems.length === 1) {
                    this.savedItems.splice(0, 1, this.createRow());
                } else {
                    this.savedItems.splice(i, 1);
                }
                this.ensureMinimumRows();
                this.syncDescList?.();
                this.recalcTotals();
                this.syncSupplierLockState();
            },

            createRow(source = {}) {
                return {
                    ...newRow(),
                    ...source,
                    uid: source.uid || cryptoRandom(),
                    fdiscpersen: this.normalizeDiscountValue(source.fdiscpersen ?? '0'),
                    fdesc: (source.fdesc ?? '').toString(),
                    fketdt: (source.fketdt ?? '').toString(),
                    frefnoacak: this.normalizeRefNoAcak(source.frefnoacak),
                };
            },

            isRowSavable(row) {
                return !!((row.fitemcode || '').trim() && (row.fsatuan || '').trim() && Number(row.fqty) > 0);
            },

            get submitItems() {
                return this.savedItems.filter((row) => this.isRowSavable(row));
            },

            syncDetailPayload(form, rows = null) {
                const targetForm = form || document.querySelector('form[data-form-draft="true"]');
                if (!targetForm) return;

                const container = targetForm.querySelector('[data-detail-payload]');
                if (!container) return;

                const payloadRows = Array.isArray(rows) ? rows : this.submitItems;
                const fields = [
                    'fitemcode',
                    'fitemname',
                    'fsatuan',
                    'fqty',
                    'fprice',
                    'fbiaya',
                    'fdiscpersen',
                    'ftotprice',
                    'fdesc',
                    'fketdt',
                    'frefdtno',
                    'frefdtid',
                    'frefnoacak',
                    'fsource',
                    'fnouref',
                    'frefpr',
                ];

                container.innerHTML = '';

                payloadRows.forEach((row) => {
                    fields.forEach((field) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `${field}[]`;
                        input.value = row?.[field] ?? '';
                        container.appendChild(input);
                    });
                });
            },

            isRowFilled(row) {
                return [
                    row.fitemcode,
                    row.fitemname,
                    row.fsatuan,
                    row.frefdtno,
                    row.fsource,
                    row.fnouref,
                    row.frefpr,
                    row.fqty,
                    row.fprice,
                    row.fbiaya,
                    row.fdiscpersen,
                    row.fdesc,
                    row.fketdt
                ].some((value) => String(value ?? '').trim() !== '' && Number(value ?? 0) !== 0)
                    || Number(row.fqty || 0) > 0;
            },

            rowWarningLabel(row) {
                return `Data Produk ${row.fitemname || row.fitemcode || '(tanpa nama)'} qty masih 0, tidak akan tersimpan.`;
            },

            closeWarning() {
                this.showWarningModal = false;
                this.warningTitle = 'Perhatian';
                this.warningMessage = '';
                this.warningItems = [];
                this.warningCanProceed = false;
                this.pendingSubmitForm = null;
                this.pendingValidRows = [];
            },

            confirmWarningAndSubmit() {
                if (!this.warningCanProceed || !this.pendingSubmitForm || this.pendingValidRows.length < 1) {
                    this.closeWarning();
                    return;
                }

                this.savedItems = this.pendingValidRows.map((row) => ({ ...row }));
                this.recalcTotals();
                const form = this.pendingSubmitForm;
                this.closeWarning();
                this.$nextTick(() => {
                    this.syncDetailPayload(form, this.savedItems);
                    form.submit();
                });
            },

            onSubmit($event) {
                if (this.savedItems.length === 0) {
                    $event.preventDefault();
                    this.showNoItems = true;
                    return;
                }
            },

            showDescModal: false,
            descTarget: 'saved',
            descSavedIndex: null,
            descValue: '',
            descReadonly: false,
            showSupplierRequired: false,
            showDescCodeRequired: false,
            descItemCode: '',
            descItemName: '',
            descCopied: false,
            hasDesc(value) {
                return String(value ?? '').trim() !== '';
            },
            descButtonClass(value) {
                return this.hasDesc(value)
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                    : 'border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100';
            },
            getDescRow(target = 'saved', index = null) {
                if (target === 'saved' && index !== null) {
                    return this.savedItems[index] || null;
                }

                return null;
            },
            openDesc(target = 'saved', index = null, readonly = false) {
                const row = this.getDescRow(target, index);
                const itemCode = (row?.fitemcode || '').toString().trim();

                if (!itemCode) {
                    this.showDescCodeRequired = true;
                    return;
                }

                this.descTarget = target;
                this.descSavedIndex = index;
                this.descReadonly = readonly;
                this.descItemCode = itemCode;
                this.descItemName = (row?.fitemname || '').toString().trim();
                this.descCopied = false;

                this.descValue = (row?.fdesc || '').toString();

                this.showDescModal = true;
            },
            closeDesc() {
                this.showDescModal = false;
                this.descTarget = 'saved';
                this.descSavedIndex = null;
                this.descValue = '';
                this.descReadonly = false;
                this.descItemCode = '';
                this.descItemName = '';
                this.descCopied = false;
            },
            async copyDescPayload() {
                this.descValue = this.descItemName || '';
            },
            applyDesc() {
                if (this.descTarget === 'saved' && this.descSavedIndex !== null && this.savedItems[this.descSavedIndex]) {
                    this.savedItems[this.descSavedIndex].fdesc = this.descValue;
                    this.onRowUpdated(this.descSavedIndex);
                }

                this.closeDesc();
            },

            itemKey(it) {
                const code = (it.fitemcode ?? '').toString().trim();
                const refId = (it.frefdtid ?? '').toString().trim();
                const refNo = (it.frefdtno ?? '').toString().trim();
                const satuan = (it.fsatuan ?? '').toString().trim();
                return refId !== '' ? `${code}::${refId}` : `${code}::${refNo}::${satuan}`;
            },

            getCurrentItemKeys() {
                return this.savedItems.map(it => this.itemKey(it));
            },

            isOpeningBalanceCode(code) {
                return (code || '').toString().trim().toUpperCase() === 'AWAL';
            },

            hasOpeningBalanceRows() {
                return (this.savedItems || []).some((row) => this.isOpeningBalanceCode(row?.fitemcode));
            },

            hasSourceReferenceRows() {
                return (this.savedItems || []).some((row) => ['PO', 'PB'].includes((row?.fsource || '').toString().trim().toUpperCase()));
            },

            hasMixedOpeningBalanceAndSourceRows(rows = this.savedItems) {
                const activeRows = (rows || []).filter((row) => this.isRowSavable(row));
                const hasOpeningBalance = activeRows.some((row) => this.isOpeningBalanceCode(row?.fitemcode));
                const hasSourceReference = activeRows.some((row) => ['PO', 'PB'].includes((row?.fsource || '').toString().trim().toUpperCase()));
                return hasOpeningBalance && hasSourceReference;
            },

            showOpeningBalanceMixWarning() {
                const message = 'Item AWAL tidak boleh digabung dengan item referensi PO atau TER dalam satu faktur pembelian.';
                if (window.showTransactionErrorModal) {
                    window.showTransactionErrorModal(message, { title: 'Kombinasi Item Tidak Diizinkan' });
                    return;
                }
                window.showAppErrorAlert('TERJADI KESALAHAN', message);
            },

            syncOpeningBalanceMode() {
                const hasOpeningBalanceRows = this.hasOpeningBalanceRows();
                window.fpbOpeningBalanceLocked = hasOpeningBalanceRows;

                if (!hasOpeningBalanceRows) return;

                const typeSelect = document.querySelector('select[name="ftypebuy"]');
                if (typeSelect && typeSelect.value !== '1') {
                    typeSelect.value = '1';
                    typeSelect.dispatchEvent(new Event('input', { bubbles: true }));
                    typeSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }
            },

            alokasiBiaya() {
                if (this.hasTerSourceItems) {
                    return;
                }
                if (this.biayaGlobal <= 0 || this.totalHarga <= 0) {
                    window.showAppWarningAlert('WARNING', 'MASUKKAN TOTAL ONGKIR DAN PASTIKAN ITEM SUDAH ADA.');
                    return;
                }

                this.savedItems.forEach((item) => {
                    let proporsi = item.ftotprice / this.totalHarga;
                    let alokasiTotalBaris = this.biayaGlobal * proporsi;

                    if (item.fqty > 0) {
                        item.fbiaya = parseFloat((alokasiTotalBaris / item.fqty).toFixed(2));
                        // Jalankan ulang recalc agar ftotprice di baris ini terupdate otomatis
                        this.recalc(item);
                    }
                });
            },

            init() {
                this.$watch('includePPN', () => this.recalcTotals());
                this.$watch('fapplyppn', () => this.recalcTotals());
                this.$watch('ppnRate', () => this.recalcTotals());

                this.savedItems = (this.savedItems || []).map((item, index) => {
                    const row = this.createRow({
                        ...item,
                        uid: item.uid || `old-${index}`
                    });
                    this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode));
                    this.recalc(row);
                    return row;
                });
                if (this.savedItems.length === 0) {
                    this.savedItems = [this.createRow()];
                }
                this.ensureMinimumRows();
                this.ensureTrailingRow();
                this.recalcTotals();
                this.syncSupplierLockState();
                this.syncOpeningBalanceMode();

                // Listen for PO and PB picked
                window.fakturPembelianItemsTable = this;
                window.getCurrentItemKeys = () => this.getCurrentItemKeys();
                window.addEventListener('po-picked', this.onPoPicked.bind(this), {
                    passive: true
                });
                window.addEventListener('pb-picked', this.onPbPicked.bind(this), {
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
                        this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode), true);
                        if (row.fqty === null || row.fqty === undefined || row.fqty === '') row.fqty = 0;
                        this.recalc(row);
                        const index = this.savedItems.findIndex((item) => item.uid === row.uid);
                        this.onRowUpdated(index >= 0 ? index : null);
                    };
                    if (typeof this.browseIndex === 'number' && this.savedItems[this.browseIndex]) {
                        apply(this.savedItems[this.browseIndex]);
                        const i = this.browseIndex;
                        this.$nextTick(() => document.getElementById('qty_saved_' + i)?.focus());
                    }
                }, {
                    passive: true
                });
            },

            submitForm(form) {
                const validRows = this.savedItems.filter((row) => this.isRowSavable(row));
                const warningRows = this.savedItems.filter((row) => this.isRowFilled(row) && !this.isRowSavable(row));
                const seenCodes = new Set();

                for (const row of validRows) {
                    const code = (row.fitemcode || '').toString().trim().toUpperCase();
                    if (!code) continue;
                    if (seenCodes.has(code)) {
                        if (window.showTransactionErrorModal) {
                            window.showTransactionErrorModal(`Kode produk ${code} tidak boleh sama dalam satu Faktur Pembelian.`, {
                                title: 'Produk Duplikat'
                            });
                        } else {
                            window.showAppWarningAlert('WARNING', `KODE PRODUK ${code} TIDAK BOLEH SAMA DALAM SATU FAKTUR PEMBELIAN.`);
                        }
                        return;
                    }
                    seenCodes.add(code);
                }

                if (this.hasMixedOpeningBalanceAndSourceRows(validRows)) {
                    this.showOpeningBalanceMixWarning();
                    return;
                }

                if (warningRows.length > 0) {
                    this.warningTitle = 'Qty Belum Diisi';
                    this.warningMessage = validRows.length > 0
                        ? 'Beberapa item tidak akan disimpan karena qty masih 0.'
                        : 'Tidak ada item yang bisa disimpan karena qty masih 0 atau data belum lengkap.';
                    this.warningItems = warningRows.map((row) => this.rowWarningLabel(row));
                    this.warningCanProceed = validRows.length > 0;
                    this.pendingSubmitForm = form;
                    this.pendingValidRows = validRows;
                    this.showWarningModal = true;
                    return;
                }

                if (validRows.length < 1) {
                    this.showNoItems = true;
                    return;
                }

                this.savedItems = validRows.map((row) => ({ ...row }));
                this.recalcTotals();
                this.$nextTick(() => {
                    this.syncDetailPayload(form, this.savedItems);
                    form.submit();
                });
            },

            openBrowseFor(where, index = null) {
                if (!this.requireSupplierBeforeManualProduct()) {
                    return;
                }
                this.browseTarget = where;
                this.browseIndex = where === 'saved' ? index : null;
                window.dispatchEvent(new CustomEvent('browse-open', {
                    detail: {
                        forEdit: false,
                        productCodeFilter: document.querySelector('select[name="ftypebuy"]')?.value === '2' ? 'UM' : ''
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
                frefdtid: '',
                frefnoacak: '',
                fsource: '',
                fnouref: '',
                frefpr: '',
                fqty: 0,
                fterima: 0,
                fprice: 0,
                    fdiscpersen: '0',
                fbiaya: 0,
                ftotprice: 0,
                fdesc: '',
                fketdt: '',
                maxqty: 0,
                lockQty: false,
            };
        }

        function cryptoRandom() {
            return (window.crypto?.getRandomValues ? [...window.crypto.getRandomValues(new Uint32Array(2))].map(n => n
                .toString(16)).join('') : Math.random().toString(36).slice(2)) + Date.now();
        }
    }
</script>

<script>
    window.poFormModal = function() {
        return {
            show: false,
            table: null,

            showDupModal: false,
            dupCount: 0,
            dupSample: [],
            pendingHeader: null,
            pendingUniques: [],

            isLocked() {
                return !!window.fpbOpeningBalanceLocked;
            },

            showLockedWarning() {
                const message = 'Item AWAL untuk saldo awal tidak boleh ambil referensi PO atau TER.';
                if (window.showTransactionErrorModal) {
                    window.showTransactionErrorModal(message, { title: 'Referensi Tidak Bisa Dipakai' });
                    return;
                }
                window.showAppErrorAlert('TERJADI KESALAHAN', message);
            },

            initDataTable() {
                if (this.table) {
                    this.table.destroy();
                }

                this.table = $('#poTable').DataTable({
                    processing: true,
                    serverSide: true,
                    destroy: true,
                    scrollX: false,
                    scrollCollapse: true,
                    ajax: {
                        url: "{{ route('fakturpembelian.pickablePO') }}",
                        type: 'GET',
                        data: function(d) {
                            return {
                                supplier_code: (document.getElementById('supplierCodeHidden')?.value || document.getElementById('modal_filter_supplier_id')?.value || '').trim(),
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
                            data: 'fsupplier',
                            name: 'fsupplier',
                            className: 'text-sm',
                            render: function(data) {
                                return data || '-';
                            }
                        },
                        {
                            data: 'fpodate',
                            name: 'fpodate',
                            className: 'text-sm',
                            render: function(data) {
                                return formatDate(data);
                            }
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center',
                            render: function(data, type, row) {
                                return '<button type="button" class="btn-pick px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150">{{ "Pilih" }}</button>';
                            }
                        }
                    ],
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    dom: '<"flex justify-between items-center mb-4"f<"ml-auto"l>>rtip',
                    order: [[2, 'desc']],
                    autoWidth: false
                });

                const self = this;
                $('#poTable').off('click', '.btn-pick').on('click', '.btn-pick', function() {
                    const data = self.table.row($(this).closest('tr')).data();
                    self.pick(data);
                });
            },

            openModal() {
                if (this.isLocked()) {
                    this.showLockedWarning();
                    return;
                }
                this.show = true;
                this.$nextTick(() => {
                    this.initDataTable();
                });
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
                window.transactionReferenceModalHelper.confirmAddUniques(this, 'po-picked');
            },
            async pick(row) {
                try {
                    const url = `{{ route('fakturpembelian.itemsPO', ['id' => 'PO_ID_PLACEHOLDER']) }}`.replace('PO_ID_PLACEHOLDER', row.fpohid);
                    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    const json = await res.json();

                    const items = json.items || [];
                    const currentKeys = new Set((window.getCurrentItemKeys?.() || []).map(String));
                    const keyOf = (src) => {
                        const code = (src.fitemcode ?? '').toString().trim();
                        const refId = (src.frefdtid ?? '').toString().trim();
                        const refNo = ((row?.fpono ?? src.frefdtno) ?? '').toString().trim();
                        const satuan = (src.fsatuan ?? '').toString().trim();
                        return refId !== '' ? `${code}::${refId}` : `${code}::${refNo}::${satuan}`;
                    };

                    const duplicates = items.filter(src => currentKeys.has(keyOf(src)));
                    const uniques = items.filter(src => !currentKeys.has(keyOf(src)));

                    const header = json.header || row;

                    if (duplicates.length > 0) {
                        this.openDupModal(header, duplicates, uniques);
                        return;
                    }

                    window.dispatchEvent(new CustomEvent('po-picked', {
                        detail: { header, items }
                    }));
                    this.closeModal();
                } catch (e) {
                    console.error(e);
                    console.log(@json("Gagal mengambil detail PO. Lihat konsol untuk detail."));
                }
            }
        };
    };

    window.pbFormModal = function() {
        return {
            show: false,
            table: null,

            showDupModal: false,
            dupCount: 0,
            dupSample: [],
            pendingHeader: null,
            pendingUniques: [],

            isLocked() {
                return !!window.fpbOpeningBalanceLocked;
            },

            showLockedWarning() {
                const message = 'Item AWAL untuk saldo awal tidak boleh ambil referensi PO atau TER.';
                if (window.showTransactionErrorModal) {
                    window.showTransactionErrorModal(message, { title: 'Referensi Tidak Bisa Dipakai' });
                    return;
                }
                window.showAppErrorAlert('TERJADI KESALAHAN', message);
            },

            initDataTable() {
                if (this.table) {
                    this.table.destroy();
                }

                this.table = $('#pbTable').DataTable({
                    processing: true,
                    serverSide: true,
                    destroy: true,
                    scrollX: false,
                    scrollCollapse: true,
                    ajax: {
                        url: "{{ route('fakturpembelian.pickablePB') }}",
                        type: 'GET',
                        data: function(d) {
                            return {
                                supplier_code: (document.getElementById('supplierCodeHidden')?.value || document.getElementById('modal_filter_supplier_id')?.value || '').trim(),
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
                            data: 'fstockmtno',
                            name: 'fstockmtno',
                            className: 'font-mono text-sm'
                        },
                        {
                            data: 'fsupplier',
                            name: 'fsupplier',
                            className: 'text-sm',
                            render: function(data) {
                                return data || '-';
                            }
                        },
                        {
                            data: 'fstockmtdate',
                            name: 'fstockmtdate',
                            className: 'text-sm',
                            render: function(data) {
                                return formatDate(data);
                            }
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center',
                            render: function() {
                                return '<button type="button" class="btn-pick px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150">{{ "Pilih" }}</button>';
                            }
                        }
                    ],
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    dom: '<"flex justify-between items-center mb-4"f<"ml-auto"l>>rtip',
                    order: [[2, 'desc']],
                    autoWidth: false
                });

                const self = this;
                $('#pbTable').off('click', '.btn-pick').on('click', '.btn-pick', function() {
                    const data = self.table.row($(this).closest('tr')).data();
                    self.pick(data);
                });
            },

            openModal() {
                if (this.isLocked()) {
                    this.showLockedWarning();
                    return;
                }
                this.show = true;
                this.$nextTick(() => {
                    this.initDataTable();
                });
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
                window.transactionReferenceModalHelper.confirmAddUniques(this, 'pb-picked');
            },
            async pick(row) {
                try {
                    const url = `{{ route('fakturpembelian.itemsPB', ['id' => 'PB_ID_PLACEHOLDER']) }}`.replace('PB_ID_PLACEHOLDER', row.fstockmtid);
                    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    const json = await res.json();

                    const items = json.items || [];
                    const currentKeys = new Set((window.getCurrentItemKeys?.() || []).map(String));
                    const keyOf = (src) => {
                        const code = (src.fitemcode ?? '').toString().trim();
                        const refId = (src.frefdtid ?? '').toString().trim();
                        const refNo = ((row?.fstockmtno ?? src.frefdtno) ?? '').toString().trim();
                        const satuan = (src.fsatuan ?? '').toString().trim();
                        return refId !== '' ? `${code}::${refId}` : `${code}::${refNo}::${satuan}`;
                    };

                    const duplicates = items.filter(src => currentKeys.has(keyOf(src)));
                    const uniques = items.filter(src => !currentKeys.has(keyOf(src)));

                    const header = json.header || row;

                    if (duplicates.length > 0) {
                        this.openDupModal(header, duplicates, uniques);
                        return;
                    }

                    window.dispatchEvent(new CustomEvent('pb-picked', {
                        detail: { header, items }
                    }));
                    this.closeModal();
                } catch (e) {
                    console.error(e);
                    console.log(@json("Gagal mengambil detail PB. Lihat konsol untuk detail."));
                }
            }
        };
    };

    // Helper function untuk format tanggal (ditingkatkan sedikit)
    function formatDate(s) {
        if (!s || s === 'No Date') return '-';
        // Mencoba parsing format standar ISO 8601 atau yang didukung Date
        const d = new Date(s);
        if (isNaN(d.getTime())) return '-';

        // Format YYYY-MM-DD HH:MM
        const pad = n => n.toString().padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }
</script>

<script>
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
                            // Mengirim parameter standar DataTables untuk server-side processing
                                            return {
                                                draw: d.draw,
                                                start: d.start,
                                                length: d.length,
                                                search: d.search.value,
                                                fend: 1,
                                                // Menambahkan parameter order untuk sorting (diperlukan serverSide)
                                                order_column: d.columns[d.order[0].column].data,
                                                order_dir: d.order[0].dir
                            };
                        },
                        dataSrc: function(json) {
                            // Asumsi backend mengembalikan data di properti 'data' (seperti Laravel DataTables)
                            return json.data;
                        }
                    },
                    columns: [{
                            data: 'faccount',
                            name: 'faccount',
                            className: 'font-mono text-sm',
                            width: '30%'
                        },
                        {
                            data: 'faccname',
                            name: 'faccname',
                            className: 'text-sm',
                            width: '55%'
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center',
                            width: '15%',
                            render: function(data, type, row) {
                                // Menggunakan styling yang mirip dengan button 'Pilih' di Supplier
                                return '<button type="button" class="btn-choose px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150">{{ "Pilih" }}</button>';
                            }
                        }
                    ],
                    pageLength: 10,
                    lengthMenu: [
                        [10, 25, 50, 100],
                        [10, 25, 50, 100]
                    ],
                    // Menggunakan DOM custom untuk kontrol DataTables (sama seperti Supplier)
                    dom: '<"flex justify-between items-center mb-4"f<"ml-auto"l>>rtip',
                    language: {
                        processing: @json("Memuat data..."),
                        search: @json("Search" . ':'),
                        lengthMenu: @json("Tampilkan _MENU_"),
                        info: @json("Menampilkan _START_ - _END_ dari _TOTAL_ data"),
                        infoEmpty: @json("Tidak ada data"),
                        infoFiltered: @json("(disaring dari _MAX_ total data)"),
                        zeroRecords: @json("Tidak ada data yang ditemukan"),
                        emptyTable: @json("Tidak ada data tersedia"),
                        paginate: {
                            first: @json("Pertama"),
                            last: @json("Terakhir"),
                            next: @json("Selanjutnya"),
                            previous: @json("Sebelumnya")
                        }
                    },
                    order: [
                        [1, 'asc'] // Default order by Account Name
                    ],
                    autoWidth: false,
                    initComplete: function() {
                        const api = this.api();
                        const $container = $(api.table().container());

                        // Style search input (disamakan dengan Supplier)
                        $container.find('.dt-search .dt-input, .dataTables_filter input').css({
                            width: '300px',
                            padding: '8px 12px',
                            border: '2px solid #e5e7eb',
                            borderRadius: '8px',
                            fontSize: '14px'
                        }).focus();

                        // Style length select (disamakan dengan Supplier)
                        $container.find('.dt-length select, .dataTables_length select').css({
                            padding: '6px 32px 6px 10px',
                            border: '2px solid #e5e7eb',
                            borderRadius: '8px',
                            fontSize: '14px'
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
                this.$nextTick(() => {
                    this.initDataTable();
                });
            },

            close() {
                this.open = false;
                if (this.table) {
                    // Bersihkan pencarian saat ditutup (sama seperti Supplier)
                    this.table.search('').draw();
                }
            },

            choose(w) {
                // Dispatches event (tetap)
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
                window.addEventListener('account-browse-open', () => this.openModal(), {
                    passive: true
                });
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
                    const option = sel.querySelector('option[value="' + faccount + '"]');
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

<script>
    // Helper: update field saat warehouse-picked
    document.addEventListener('DOMContentLoaded', () => {
        window.addEventListener('warehouse-picked', (ev) => {
            const { fwhcode } = ev.detail || {};
            const sel = document.getElementById('warehouseSelect');
            const hid = document.getElementById('warehouseCodeHidden');
            if (sel) {
                sel.value = fwhcode || '';
                sel.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }
            if (hid) hid.value = fwhcode || '';
        });
    });
</script>

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    @include('components.transaction.browse-warehouse-script', ['eventName' => 'faktur-pembelian-warehouse-browse-open'])
    @include('components.transaction.browse-product-script', ['showControls' => true, 'showPagination' => true, 'supportsForEdit' => true])
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

        // Helper untuk update field saat account-picked
        document.addEventListener('DOMContentLoaded', () => {
            window.addEventListener('account-picked', (ev) => {
                const {
                    faccid,
                    faccount,
                    faccname
                } = ev.detail || {};
                const sel = document.getElementById('accountSelect');
                const hidId = document.getElementById('accountIdHidden');
                const hidCode = document.getElementById('accountCodeHidden');

                if (sel) {
                    // Cek apakah option sudah ada
                    let opt = [...sel.options].find(o => o.value == faccount);
                    const label = faccount + ' - ' + faccname;

                    if (!opt) {
                        opt = new Option(label, faccount, true, true);
                        sel.add(opt);
                    } else {
                        opt.text = label;
                        opt.selected = true;
                    }
                    sel.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                }

                if (hidId) hidId.value = faccid || '';
                if (hidCode) hidCode.value = faccount || '';
            });
        });
    </script>
@endpush
