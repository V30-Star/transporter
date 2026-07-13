@extends('layouts.app')

@section('title', 'Faktur Pembelian - New')

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

        input::placeholder,
        textarea::placeholder {
            color: #9ca3af !important;
            font-weight: normal !important;
        }

        input:disabled::placeholder,
        textarea:disabled::placeholder {
            color: #9ca3af !important;
            -webkit-text-fill-color: #9ca3af !important;
            font-weight: normal !important;
        }
    </style>
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow p-0 overflow-hidden" role="alert">
            {{-- Header Strip --}}
            <div class="d-flex align-items-center px-4 py-3" style="background-color: #c0392b;">
                <i class="bi bi-exclamation-triangle-fill text-white me-2 fs-5"></i>
                <strong class="text-white fs-6">{{ 'Gagal Menyimpan Data!' }}</strong>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert"
                    aria-label="Close"></button>
            </div>

            {{-- Body --}}
            <div class="px-4 py-3" style="background-color: #fdeded; border-left: 5px solid #c0392b;">
                <p class="mb-2 text-danger fw-semibold">
                    <i class="bi bi-info-circle me-1"></i>
                    {{ 'Periksa kembali data berikut sebelum menyimpan:' }}
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
        $ppnMode = old('fincludeppn', 0);
        $ppnRate = old('ppn_rate', 11);
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
                'uid' => 'old-' . $i,
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
                'fdiscpersen' => (string) ($oldDiscs[$i] ?? '0'),
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
                        <div>
                <form action="{{ route('fakturpembelian.store') }}" method="POST" data-form-draft="true"
                    data-draft-key="fakturpembelian:create" x-data="{ showNoItems: false }"
                    @submit.prevent="if (window.fakturPembelianItemsTable?.submitForm) { window.fakturPembelianItemsTable.submitForm($el); } else { const n = Number(document.getElementById('itemsCount')?.value || 0); if (n < 1) { showNoItems = true } else { window.submitFormWithStockMinusConfirmation?.($el) } }">
                    @csrf

                    {{-- ─── CARD 1: Identitas Faktur Pembelian ────────────────────── --}}
                    <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                         <div class="flex items-center gap-2 px-4 pt-3 pb-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Identitas Faktur Pembelian</p>
                        </div>
                        <div class="p-4 space-y-3">
                            <div class="grid grid-cols-3 gap-3">
                                {{-- Cabang --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Cabang</label>
                                    <input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                                        value="{{ trim(($fbranchcode ?? '') . ($fcabang ?? '' ? ' - ' . $fcabang : '')) }}" disabled>
                                    <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                                </div>

                                {{-- Transaksi# --}}
                                <div x-data="{ autoCode: true }">
                                    <label class="block text-xs font-bold text-gray-600 mb-1">No.Transaksi#</label>
                                    <div class="flex items-center gap-2">
                                        <input type="text" name="fstockmtno" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                            :disabled="autoCode" :class="autoCode ? 'bg-gray-100 text-gray-500 border-gray-200 cursor-not-allowed' : 'bg-white'"
                                            :placeholder="autoCode ? 'Auto Generated' : ''">
                                        <label class="inline-flex items-center select-none font-medium text-sm text-gray-600 cursor-pointer">
                                            <input type="checkbox" x-model="autoCode" checked class="rounded text-blue-600 border-gray-300 focus:ring-blue-500">
                                            <span class="ml-1.5">Auto</span>
                                        </label>
                                    </div>
                                </div>

                                {{-- Tanggal --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Tanggal <span class="text-red-500">*</span></label>
                                    <input type="date" id="fstockmtdate" name="fstockmtdate"
                                        value="{{ old('fstockmtdate') ?? date('Y-m-d') }}"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fstockmtdate') border-red-400 @enderror">
                                    @error('fstockmtdate')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-3">
                                {{-- Type --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Type <span class="text-red-500">*</span></label>
                                    <select name="ftypebuy" x-model="selectedType"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('ftypebuy') border-red-400 @enderror">
                                        <option value="0" {{ old('ftypebuy') == '0' ? 'selected' : '' }}>Stok</option>
                                        <option value="1" {{ old('ftypebuy') == '1' ? 'selected' : '' }}>Non Stok</option>
                                        <option value="2" {{ old('ftypebuy') == '2' ? 'selected' : '' }}>Uang Muka</option>
                                    </select>
                                    @error('ftypebuy')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Supplier --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Supplier <span class="text-red-500">*</span></label>
                                    <div class="flex">
                                        <div class="relative flex-1">
                                            <select id="modal_filter_supplier_id" name="filter_supplier_id"
                                                class="w-full border border-gray-300 rounded-l-lg px-3 py-2 text-sm bg-gray-50 text-gray-700 cursor-pointer focus:outline-none focus:border-blue-500"
                                                disabled>
                                                <option value=""></option>
                                                @foreach ($suppliers as $supplier)
                                                    <option value="{{ $supplier->fsuppliercode }}"
                                                        {{ $filterSupplierId == $supplier->fsuppliercode ? 'selected' : '' }}>
                                                        {{ $supplier->fsuppliername }} ({{ $supplier->fsuppliercode }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div id="supplierBrowseOverlay" class="absolute inset-0 cursor-pointer" role="button"
                                                aria-label="Browse supplier"
                                                @click="window.dispatchEvent(new CustomEvent('supplier-browse-open'))"></div>
                                        </div>
                                        <input type="hidden" name="fsupplier" id="supplierCodeHidden" value="{{ old('fsupplier') }}">
                                        <button type="button" id="supplierBrowseButton"
                                            @click="window.dispatchEvent(new CustomEvent('supplier-browse-open'))"
                                            class="border border-l-0 border-gray-300 px-3 py-2 bg-white hover:bg-gray-50 text-gray-500 transition-colors"
                                            title="Browse Supplier">
                                            <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                        </button>
                                        @if (in_array('createSupplier', explode(',', session('user_restricted_permissions', '')), true))
                                            <a href="{{ route('supplier.create') }}" target="_blank" rel="noopener"
                                                id="supplierCreateButton"
                                                class="border border-l-0 border-gray-300 rounded-r-lg px-3 py-2 bg-white hover:bg-gray-50 text-gray-500 transition-colors"
                                                title="Tambah Supplier">
                                                <x-heroicon-o-plus class="w-4 h-4" />
                                            </a>
                                        @endif
                                    </div>
                                    @error('fsupplier')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Gudang --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Gudang <span class="text-red-500">*</span></label>
                                    <div class="flex">
                                        <div class="relative flex-1">
                                            <select id="warehouseSelect"
                                                class="w-full border border-gray-300 rounded-l-lg px-3 py-2 text-sm bg-gray-50 text-gray-700 cursor-pointer focus:outline-none focus:border-blue-500"
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
                                            <div class="absolute inset-0 cursor-pointer" role="button" aria-label="Browse warehouse"
                                                @click="window.dispatchEvent(new CustomEvent('faktur-pembelian-warehouse-browse-open'))"></div>
                                        </div>
                                        <input type="hidden" name="ffrom" id="warehouseCodeHidden" value="{{ old('ffrom') }}">
                                        <button type="button" @click="window.dispatchEvent(new CustomEvent('faktur-pembelian-warehouse-browse-open'))"
                                            class="border border-l-0 border-gray-300 px-3 py-2 bg-white hover:bg-gray-50 text-gray-500 transition-colors"
                                            title="Browse Gudang">
                                            <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                        </button>
                                        <a href="{{ route('gudang.create') }}" target="_blank" rel="noopener"
                                            class="border border-l-0 border-gray-300 rounded-r-lg px-3 py-2 bg-white hover:bg-gray-50 text-gray-500 transition-colors"
                                            title="Tambah Gudang">
                                            <x-heroicon-o-plus class="w-4 h-4" />
                                        </a>
                                    </div>
                                    @error('ffrom')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div id="supplierAdvanceWarningBox" class="hidden my-2">
                                <div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-amber-800">
                                    <div class="flex items-start gap-2">
                                        <x-heroicon-o-exclamation-triangle class="w-5 h-5 mt-0.5 flex-shrink-0" />
                                        <p class="text-sm font-medium" id="supplierAdvanceWarningText"></p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-3">
                                {{-- Account --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Account</label>
                                    <div class="flex">
                                        <div class="relative flex-1">
                                            <select id="accountSelect" class="w-full border border-gray-300 rounded-l-lg px-3 py-2 text-sm focus:outline-none"
                                                :class="{
                                                    'bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200': selectedType != '1',
                                                    'bg-gray-50 text-gray-700 cursor-pointer focus:border-blue-500': selectedType == '1'
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
                                            <div class="absolute inset-0 cursor-pointer" role="button" aria-label="Browse account"
                                                @click="window.dispatchEvent(new CustomEvent('account-browse-open'))"
                                                x-show="selectedType == '1'"></div>
                                        </div>
                                        <input type="hidden" name="fprdjadi" id="accountCodeHidden" value="{{ old('fprdjadi') }}">
                                        <input type="hidden" name="faccid" id="accountIdHidden" value="{{ old('faccid') }}">
                                        <button type="button" @click="window.dispatchEvent(new CustomEvent('account-browse-open'))"
                                            class="border border-l-0 border-gray-300 px-3 py-2 bg-white hover:bg-gray-50 text-gray-500 transition-colors"
                                            :disabled="selectedType != '1'"
                                            :class="{ 'opacity-50 cursor-not-allowed': selectedType != '1' }"
                                            title="Browse Account">
                                            <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                        </button>
                                        <a href="{{ route('account.create') }}" target="_blank" rel="noopener"
                                            class="border border-l-0 border-gray-300 rounded-r-lg px-3 py-2 bg-white hover:bg-gray-50 text-gray-500 transition-colors"
                                            :class="{ 'opacity-50 cursor-not-allowed pointer-events-none': selectedType != '1' }"
                                            @click="selectedType != '1' && $event.preventDefault()" title="Tambah Account">
                                            <x-heroicon-o-plus class="w-4 h-4" />
                                        </a>
                                    </div>
                                    @error('fprdjadi')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Faktur --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Faktur <span class="text-red-500">*</span></label>
                                    <input type="number" name="frefno" required
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                </div>

                                {{-- TOP --}}
                                <div class="flex gap-2">
                                    <div class="w-1/2">
                                        <label class="block text-xs font-bold text-gray-600 mb-1">TOP (Hari)</label>
                                        <input type="number" id="ftempohr" name="ftempohr" value="{{ old('ftempohr', '0') }}"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('ftempohr') border-red-400 @enderror">
                                        @error('ftempohr')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="w-1/2">
                                        <label class="block text-xs font-bold text-gray-600 mb-1">Jatuh Tempo</label>
                                        <input type="date" id="fjatuhtempo" name="fjatuhtempo"
                                            value="{{ old('fjatuhtempo') ?? date('Y-m-d') }}" readonly
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ─── CARD 2: Detail Item ────────────────────── --}}
                    <div x-data="itemsTable()" x-init="init()" class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                         <div class="flex items-center gap-2 px-4 pt-3 pb-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Detail Item</p>
                        </div>
                        <div class="p-4 space-y-3">
                            <div class="grid grid-cols-3 gap-3">
                                <div class="col-span-2">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Keterangan</label>
                                    <textarea name="fket" rows="2"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fket') border-red-400 @enderror"
                                        placeholder="Tulis keterangan tambahan di sini...">{{ old('fket') }}</textarea>
                                    @error('fket')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Hitung Biaya</label>
                                    <div class="bg-gray-55 p-3 rounded-lg border border-gray-200 flex items-center gap-3">
                                        <input type="text" :value="Number(biayaGlobal || 0).toFixed(2)" readonly
                                            class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm text-right font-mono bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200">
                                        <button type="button"
                                            class="shrink-0 min-w-[120px] text-white font-medium py-2 px-4 rounded-lg text-sm transition flex items-center justify-center gap-2 opacity-60 cursor-not-allowed"
                                            style="background-color: #2563eb; color: #ffffff;" disabled
                                            title="Isi biaya manual pada kolom @ Biaya detail item">
                                            Hitung
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="overflow-auto border border-gray-200 rounded-lg">
                                <table class="fpb-detail-table min-w-full text-sm">
                                    <thead class="bg-gray-50 border-b border-gray-200">
                                        <tr>
                                            <th class="p-2 text-left w-10 text-xs font-semibold text-gray-500 uppercase">#</th>
                                            <th class="p-2 text-left w-36 text-xs font-semibold text-gray-500 uppercase">Kode Produk</th>
                                            <th class="p-2 text-left text-xs font-semibold text-gray-500 uppercase" style="width: 20rem; min-width: 20rem;">Nama Produk</th>
                                            <th class="p-2 text-left w-28 text-xs font-semibold text-gray-500 uppercase">No Refrensi</th>
                                            <th class="p-2 text-left w-20 text-xs font-semibold text-gray-500 uppercase">Satuan</th>
                                            <th class="p-2 text-right w-20 text-xs font-semibold text-gray-500 uppercase">Qty</th>
                                            <th class="p-2 text-right w-24 text-xs font-semibold text-gray-500 uppercase">@ Harga</th>
                                            <th class="p-2 text-right w-24 text-xs font-semibold text-gray-500 uppercase">@ Biaya</th>
                                            <th class="p-2 text-right w-20 text-xs font-semibold text-gray-500 uppercase">Disc. %</th>
                                            <th class="p-2 text-right w-28 text-xs font-semibold text-gray-500 uppercase">Total Harga</th>
                                            <th class="p-2 text-center w-16 text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                                        </tr>
                                    </thead>
                                    <template x-for="(it, i) in savedItems" :key="it.uid">
                                        <tbody>
                                            <tr class="border-t border-gray-150 align-top hover:bg-gray-50">
                                                <td class="p-2 text-gray-400" x-text="i + 1"></td>

                                                {{-- Kode Produk --}}
                                                <td class="p-2">
                                                    <div class="flex">
                                                        <input type="text"
                                                            class="w-full border border-gray-300 rounded-l-lg px-2 py-1 font-mono text-sm focus:outline-none focus:border-blue-500"
                                                            x-model.trim="it.fitemcode" @focus="activeRow = it.uid"
                                                            @blur="activeRow = null" @input="onCodeTypedRow(it, i)"
                                                            @keydown.enter.prevent="$refs['qty_saved_' + i]?.focus()">
                                                        <button type="button" @click="openBrowseFor('saved', i)"
                                                            class="shrink-0 border border-l-0 border-gray-300 rounded-r-lg px-2 py-1 bg-white hover:bg-gray-50 text-gray-500 transition-colors"
                                                            title="Cari Produk">
                                                            <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                </td>

                                                {{-- Nama Produk --}}
                                                <td class="p-2" style="width: 20rem; min-width: 20rem;">
                                                    <div class="desc-inline-field flex w-full min-w-0 flex-nowrap items-stretch">
                                                        <div class="desc-inline-field__text min-w-0 flex-1 rounded-l-lg border border-gray-300 bg-gray-50 px-2 py-1 text-sm leading-5 text-gray-600 whitespace-normal break-words"
                                                            style="flex:1 1 auto !important; min-width:0 !important;"
                                                            x-text="it.fitemname"></div>
                                                        <button type="button" @click="openDesc('saved', i)"
                                                            class="desc-inline-field__button inline-flex w-10 shrink-0 items-center justify-center border border-l-0 border-gray-300 rounded-r-lg px-2 py-1 transition-colors"
                                                            style="display:inline-flex !important; flex:0 0 2rem !important; width:2rem !important; justify-content:center !important; align-items:center !important;"
                                                            :class="descButtonClass(it.fdesc)" title="Deskripsi">
                                                            <x-heroicon-o-document-text class="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                </td>

                                                {{-- No Refrensi --}}
                                                <td class="p-2">
                                                    <input type="text"
                                                        class="w-full border border-gray-200 rounded-lg px-2 py-1 bg-gray-50 text-gray-500 text-sm cursor-not-allowed"
                                                        :value="it.frefdtno || '-'" disabled placeholder="No Ref">
                                                </td>

                                                {{-- Satuan --}}
                                                <td class="p-2">
                                                    <template x-if="it.units && it.units.length > 1">
                                                        <select class="w-full border border-gray-300 rounded-lg px-2 py-1 text-sm focus:outline-none focus:border-blue-500"
                                                            :id="'unit_saved_' + i" x-model="it.fsatuan"
                                                            @change="onRowUpdated(i)"
                                                            @focus="activeRow = it.uid" @blur="activeRow = null"
                                                            @keydown.enter.prevent="$refs['qty_saved_' + i]?.focus()">
                                                            <template x-for="u in it.units" :key="u">
                                                                <option :value="u" :selected="u === it.fsatuan" x-text="u"></option>
                                                            </template>
                                                        </select>
                                                    </template>
                                                    <template x-if="!(it.units && it.units.length > 1)">
                                                        <input type="text"
                                                            class="w-full border border-gray-200 rounded-lg px-2 py-1 bg-gray-50 text-gray-500 text-sm cursor-not-allowed"
                                                            :value="it.fsatuan || '-'" disabled>
                                                    </template>
                                                </td>

                                                {{-- Qty --}}
                                                <td class="p-2 text-right">
                                                    <input type="number" class="w-full border border-gray-300 rounded-lg px-2 py-1 text-right text-sm focus:outline-none focus:border-blue-500"
                                                        x-model.number="it.fqty" :id="'qty_saved_' + i" step="any"
                                                        @focus="activeRow = it.uid; $event.target.select()"
                                                        @blur="activeRow = null; enforceQtyRow(it);" @input="onRowUpdated(i)"
                                                        @change="onRowUpdated(i)"
                                                        @keydown.enter.prevent="$refs['price_saved_' + i]?.focus()">
                                                    <div class="text-[10px] text-slate-400 text-right mt-0.5"
                                                        x-show="it.fsource === 'PO' || it.fsource === 'PB'"
                                                        x-text="formatSourceSummary(it)"></div>
                                                    <div class="text-[10px] text-orange-600 font-medium text-right mt-0.5"
                                                        x-show="it.fitemcode && productMeta(it.fitemcode).stock > 0"
                                                        x-html="formatStockLimit(it.fitemcode, it.fqty, it.fsatuan)">
                                                    </div>
                                                </td>

                                                {{-- @ Harga --}}
                                                <td class="p-2 text-right">
                                                    <input type="text" inputmode="decimal"
                                                        class="w-full border border-gray-300 rounded-lg px-2 py-1 text-right text-sm focus:outline-none focus:border-blue-500"
                                                        :disabled="hasTerSourceItems"
                                                        :class="hasTerSourceItems ? 'bg-gray-50 text-gray-500 cursor-not-allowed border-gray-250' : ''"
                                                        x-model="it.fpriceInput" :id="'price_saved_' + i"
                                                        @focus="activeRow = it.uid; focusPriceInput(it); $event.target.select()"
                                                        @blur="activeRow = null; blurPriceInput(it)" @input="onPriceInput(it)"
                                                        @change="recalc(it)"
                                                        @keydown.enter.prevent="$refs['biaya_saved_' + i]?.focus()">
                                                </td>

                                                {{-- @ Biaya --}}
                                                <td class="p-2 text-right">
                                                    <input type="number"
                                                        class="w-full border border-gray-300 rounded-lg px-2 py-1 text-right text-sm focus:outline-none focus:border-blue-500"
                                                        :disabled="(it.fsource || '').toString().trim().toUpperCase() === 'PB'"
                                                        :class="(it.fsource || '').toString().trim().toUpperCase() === 'PB' ? 'bg-gray-50 text-gray-500 cursor-not-allowed border-gray-250' : ''"
                                                        min="0" step="0.01"
                                                        :id="'biaya_saved_' + i"
                                                        :value="it.fbiaya" 
                                                        x-init="it.fbiaya = (+it.fbiaya || 0).toFixed(2)"
                                                        @focus="activeRow = it.uid; $event.target.select()"
                                                        @blur="activeRow = null; it.fbiaya = (+it.fbiaya || 0).toFixed(2)"
                                                        @input="it.fbiaya = $event.target.value; recalc(it)"
                                                        @change="recalc(it)"
                                                        @keydown.enter.prevent="$refs['disc_saved_' + i]?.focus()">
                                                </td>

                                                {{-- Disc. % --}}
                                                <td class="p-2 text-right">
                                                    <input type="text"
                                                        class="w-full border border-gray-300 rounded-lg px-2 py-1 text-right text-sm focus:outline-none focus:border-blue-500"
                                                        :disabled="hasTerSourceItems"
                                                        :class="hasTerSourceItems ? 'bg-gray-50 text-gray-500 cursor-not-allowed border-gray-250' : ''"
                                                        placeholder="10+2" :value="it.fdiscpersen" :id="'disc_saved_' + i"
                                                        @focus="activeRow = it.uid; $event.target.select()"
                                                        @blur="activeRow = null; normalizeDiscountInput($event, it)"
                                                        @input="it.fdiscpersen = $event.target.value; recalc(it)"
                                                        @change="recalc(it)">
                                                </td>

                                                {{-- Total Harga --}}
                                                <td class="p-2">
                                                    <input type="text"
                                                        class="w-full border border-gray-250 rounded-lg px-2 py-1 bg-gray-50 text-gray-500 text-sm text-right cursor-not-allowed"
                                                        :value="formatTransactionAmount(it.ftotprice)" disabled>
                                                </td>

                                                {{-- Aksi --}}
                                                <td class="p-2 text-center">
                                                    <div class="flex items-center justify-center">
                                                        <button type="button" @click="removeSaved(i)"
                                                            class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-red-50 text-red-500 hover:bg-red-100 transition-colors border border-red-200"
                                                            title="Hapus baris">
                                                            <x-heroicon-o-minus class="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </template>
                                </table>
                            </div>
                            <div class="hidden" data-detail-payload></div>

                            {{-- MODAL DESC (di dalam itemsTable) --}}
                            <div x-show="showDescModal" x-cloak class="fixed inset-0 z-[95] flex items-center justify-center bg-black/50"
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
                                                <button type="button" @click="copyDescName()"
                                                    class="h-8 px-3 rounded-lg bg-blue-50 text-blue-700 text-sm font-medium hover:bg-blue-100 transition-colors">
                                                    Copy
                                                </button>
                                            </div>
                                            <div class="rounded-lg border bg-gray-55 px-3 py-2 text-sm text-gray-800"
                                                x-text="descItemName || '-'"></div>
                                        </div>
                                        <label class="block text-sm text-gray-700 font-bold">Deskripsi</label>
                                        <textarea x-model="descValue" rows="5" class="w-full border rounded px-3 py-2 focus:outline-none focus:border-blue-500"
                                            placeholder="Tulis deskripsi item di sini..."></textarea>
                                    </div>
                                    <div class="px-5 py-3 border-t flex items-center justify-end gap-2 bg-gray-50">
                                        <button type="button" @click="closeDesc()"
                                            class="h-9 px-4 rounded-lg bg-white border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors">
                                            Batal
                                        </button>
                                        <button type="button" @click="applyDesc()"
                                            class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition-colors">
                                            Simpan
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- MODAL: belum ada item --}}
                            <div x-show="showNoItems && savedItems.length === 0" x-cloak
                                class="fixed inset-0 z-[90] flex items-center justify-center bg-black/55" x-transition.opacity>
                                <div class="absolute inset-0 bg-black/50" @click="showNoItems=false"></div>
                                <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden"
                                    x-transition.scale>
                                    <div class="px-5 py-4 border-b flex items-center bg-red-50 text-red-700">
                                        <x-heroicon-o-exclamation-triangle class="w-6 h-6 mr-2" />
                                        <h3 class="text-lg font-semibold">Tidak Ada Item</h3>
                                    </div>
                                    <div class="px-5 py-4">
                                        <p class="text-sm text-gray-700">
                                            Anda belum menambahkan item apa pun pada tabel. Silakan isi baris “Detail Item”
                                            terlebih dahulu.
                                        </p>
                                    </div>
                                    <div class="px-5 py-3 border-t flex items-center justify-end gap-2 bg-gray-50">
                                        <button type="button" @click="showNoItems=false"
                                            class="h-9 px-4 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors">
                                            OK
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- MODAL: warning modal --}}
                            <div x-show="showWarningModal" x-cloak
                                class="fixed inset-0 z-[96] flex items-center justify-center bg-black/50" x-transition.opacity>
                                <div class="absolute inset-0 bg-black/50" @click="closeWarning()"></div>
                                <div class="relative bg-white w-[92vw] max-w-lg rounded-2xl shadow-2xl overflow-hidden"
                                    x-transition.scale>
                                    <div class="px-5 py-4 border-b flex items-center bg-amber-50 text-amber-700">
                                        <x-heroicon-o-exclamation-triangle class="w-6 h-6 mr-2" />
                                        <h3 class="text-lg font-semibold" x-text="warningTitle"></h3>
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
                                    <div class="px-5 py-3 border-t flex items-center justify-end gap-2 bg-gray-50">
                                        <button type="button" @click="closeWarning()"
                                            class="h-9 px-4 rounded-lg bg-white border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors">
                                            Tutup
                                        </button>
                                        <button type="button" x-show="warningCanProceed" @click="confirmWarningAndSubmit()"
                                            class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition-colors">
                                            Lanjut Simpan
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- MODAL SELECTORS & TOTALS --}}
                            <div class="mt-3 flex justify-between items-start gap-4 flex-wrap">
                                <div class="flex justify-start gap-2">
                                    {{-- Add PO button & modal --}}
                                    <div x-data="poFormModal()">
                                        <button type="button" @click="openModal()" :disabled="isLocked()"
                                            :class="isLocked() ? 'cursor-not-allowed opacity-50' : 'hover:bg-emerald-700'"
                                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-white font-medium text-sm transition-colors focus:outline-none">
                                            <x-heroicon-o-plus class="h-4 w-4" />
                                            Add PO
                                        </button>

                                        <!-- PO Modal backdrop -->
                                        <div x-show="show" x-transition.opacity class="fixed inset-0 z-40 bg-black/55 backdrop-blur-sm"
                                            @keydown.escape.window="closeModal()"></div>

                                        <!-- PO Modal -->
                                        <div x-show="show" x-cloak x-transition.opacity
                                            class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-8"
                                            aria-modal="true" role="dialog">
                                            <div class="relative w-full max-w-5xl rounded-2xl bg-white shadow-2xl flex flex-col overflow-hidden"
                                                style="height: 600px;">
                                                <div
                                                    class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-emerald-50 to-white">
                                                    <h3 class="text-lg font-bold text-gray-800">Pilih Purchase Order (PO)</h3>
                                                    <button type="button" @click="closeModal()"
                                                        class="h-9 px-4 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 font-medium text-gray-700 text-sm transition-colors">Tutup</button>
                                                </div>
                                                <div class="flex-1 overflow-y-auto p-6" style="min-height: 0;">
                                                    <table id="poTable"
                                                        class="min-w-full text-sm display nowrap stripe hover"
                                                        style="width:100%">
                                                        <thead class="sticky top-0 z-10">
                                                            <tr class="bg-gray-50 border-b-2 border-gray-200">
                                                                <th class="p-3 text-left font-semibold text-gray-700">PO No</th>
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

                                        <!-- Duplicate modal -->
                                        <div x-show="showDupModal" x-cloak x-transition.opacity
                                            class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/40">
                                            <div class="absolute inset-0" @click="closeDupModal()"></div>
                                            <div class="relative bg-white rounded-2xl shadow-xl max-w-xl w-full p-6">
                                                <h3 class="text-lg font-semibold mb-4 text-gray-800">Peringatan Duplikasi</h3>
                                                <p class="mb-4 text-gray-600">
                                                    Ditemukan <strong x-text="dupCount"></strong> item yang sudah ada dalam daftar. Hanya item unik yang akan ditambahkan.
                                                </p>
                                                <div class="flex justify-end gap-2">
                                                    <button type="button" @click="closeDupModal()"
                                                        class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200 transition-colors">Batal</button>
                                                    <button type="button" @click="confirmAddUniques()"
                                                        class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition-colors">Tambahkan Item Unik</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Add PB button & modal --}}
                                    <div x-data="pbFormModal()">
                                        <button type="button" @click="openModal()" :disabled="isLocked()"
                                            :class="isLocked() ? 'cursor-not-allowed opacity-50' : 'hover:bg-blue-700'"
                                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-white font-medium text-sm transition-colors focus:outline-none">
                                            <x-heroicon-o-plus class="h-4 w-4" />
                                            Add TER
                                        </button>

                                        <!-- PB Modal backdrop -->
                                        <div x-show="show" x-transition.opacity class="fixed inset-0 z-40 bg-black/55 backdrop-blur-sm"
                                            @keydown.escape.window="closeModal()"></div>

                                        <!-- PB Modal -->
                                        <div x-show="show" x-cloak x-transition.opacity
                                            class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-8"
                                            aria-modal="true" role="dialog">
                                            <div class="relative w-full max-w-5xl rounded-2xl bg-white shadow-2xl flex flex-col overflow-hidden"
                                                style="height: 600px;">
                                                <div
                                                    class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
                                                    <h3 class="text-lg font-bold text-gray-800">Pilih Penerimaan Barang</h3>
                                                    <button type="button" @click="closeModal()"
                                                        class="h-9 px-4 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 font-medium text-gray-700 text-sm transition-colors">Tutup</button>
                                                </div>
                                                <div class="flex-1 overflow-y-auto p-6" style="min-height: 0;">
                                                    <table id="pbTable"
                                                        class="min-w-full text-sm display nowrap stripe hover"
                                                        style="width:100%">
                                                        <thead class="sticky top-0 z-10">
                                                            <tr class="bg-gray-55 border-b-2 border-gray-200">
                                                                <th class="p-3 text-left font-semibold text-gray-700">Cabang</th>
                                                                <th class="p-3 text-left font-semibold text-gray-700">No.Transaksi</th>
                                                                <th class="p-3 text-left font-semibold text-gray-700">Tanggal</th>
                                                                <th class="p-3 text-left font-semibold text-gray-700">Supplier</th>
                                                                <th class="p-3 text-left font-semibold text-gray-700">Gudang</th>
                                                                <th class="p-3 text-center font-semibold text-gray-700">Aksi</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody></tbody>
                                                    </table>
                                                </div>
                                                <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50"></div>
                                            </div>
                                        </div>

                                        <!-- Duplicate modal -->
                                        <div x-show="showDupModal" x-cloak x-transition.opacity
                                            class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/40">
                                            <div class="absolute inset-0" @click="closeDupModal()"></div>
                                            <div class="relative bg-white rounded-2xl shadow-xl max-w-xl w-full p-6">
                                                <h3 class="text-lg font-semibold mb-4 text-gray-800">Peringatan Duplikasi</h3>
                                                <p class="mb-4 text-gray-600">
                                                    Ditemukan <strong x-text="dupCount"></strong> item yang sudah ada dalam daftar. Hanya item unik yang akan ditambahkan.
                                                </p>
                                                <div class="flex justify-end gap-2">
                                                    <button type="button" @click="closeDupModal()"
                                                        class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200 transition-colors">Batal</button>
                                                    <button type="button" @click="confirmAddUniques()"
                                                        class="h-9 px-4 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors">Tambahkan Item Unik</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Totals Panel --}}
                                <div class="w-[480px] shrink-0 max-w-full">
                                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 space-y-3 text-sm">
                                        <div class="flex items-center justify-between">
                                            <span class="text-gray-600">Total Harga</span>
                                            <span class="font-bold text-gray-850" x-text="formatTransactionAmount(totalHarga)"></span>
                                        </div>

                                        <div class="flex items-center justify-between">
                                            <span class="text-gray-600">Total DPP</span>
                                            <span class="font-bold text-gray-850" x-text="rupiah(totalDPP)"></span>
                                        </div>

                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-1.5">
                                                <input id="fapplyppn" type="checkbox" name="fapplyppn" value="1"
                                                    x-model="includePPN"
                                                    class="rounded text-blue-600 border-gray-300 focus:ring-blue-500 h-4 w-4">
                                                <label for="fapplyppn" class="font-bold text-gray-700 cursor-pointer">PPN</label>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <div class="relative flex items-center">
                                                    <input type="number" min="0" max="100" step="0.01"
                                                        name="ppn_rate" x-model.number="ppnRate" :disabled="!includePPN"
                                                        class="w-16 h-8 px-2 text-right text-xs border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 disabled:bg-gray-100 disabled:opacity-60 disabled:cursor-not-allowed">
                                                    <span class="absolute right-2 text-xs text-gray-400 select-none">%</span>
                                                </div>
                                                <span class="font-bold text-gray-850" x-text="rupiah(ppnAmount)"></span>
                                            </div>
                                        </div>

                                        <div class="border-t border-gray-200 my-1"></div>

                                        <div class="flex items-center justify-between">
                                            <span class="font-bold text-gray-800 text-base">Grand Total</span>
                                            <span class="font-bold text-gray-900 text-lg" x-text="rupiah(grandTotal)"></span>
                                        </div>

                                        <div class="flex items-center justify-between bg-blue-50 p-2.5 rounded-lg border border-blue-100">
                                            <span class="font-bold text-blue-700">Total Biaya (HPP)</span>
                                            <span class="font-bold text-blue-800" x-text="rupiah(totalBiayaHPP)"></span>
                                        </div>
                                    </div>

                                    <input type="hidden" name="famount" :value="totalHarga">
                                    <input type="hidden" name="famountpajak" :value="ppnAmount">
                                    <input type="hidden" name="famountmt" :value="grandTotal">
                                    <input type="hidden" name="fincludeppn" :value="includePPN ? 1 : 0">
                                    <input type="hidden" name="famountpopajak" :value="ppnRate">
                                </div>
                            </div>

                            <input type="hidden" id="itemsCount" :value="submitItems.length">
                        </div>
                    </div>

                    {{-- ─── CARD 3: Aksi / Footer ────────────────────── --}}
                    <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                        <div class="flex items-center justify-end gap-3 px-4 py-3 bg-gray-50">
                            <button type="button"
                                onclick="window.location.href='{{ route('fakturpembelian.index') }}'"
                                class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 bg-white text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-colors">
                                <x-heroicon-o-arrow-left class="w-4 h-4" />
                                Keluar
                            </button>
                            <button type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                <x-heroicon-o-check class="w-4 h-4" />
                                Simpan
                            </button>
                        </div>
                    </div>
                </form>
            </div>

        <x-transaction.browse-warehouse-modal />
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
            @php
                $smallUnit = trim((string) ($p->fsatuankecil ?? ''));
                $largeUnit = trim((string) ($p->fsatuanbesar ?? ''));
                $largeUnit2 = trim((string) ($p->fsatuanbesar2 ?? ''));
                $defaultKey = trim((string) ($p->fsatuandefault ?? ''));
                $resolvedDefaultUnit = match ($defaultKey) {
                    '1' => $smallUnit,
                    '2' => $largeUnit,
                    '3' => $largeUnit2,
                    default => in_array(strtoupper($defaultKey), [
                        strtoupper($smallUnit),
                        strtoupper($largeUnit),
                        strtoupper($largeUnit2),
                    ], true)
                        ? $defaultKey
                        : ($smallUnit ?: $largeUnit ?: $largeUnit2),
                };
                $orderedUnits = array_values(array_unique(array_filter([
                    $resolvedDefaultUnit,
                    $smallUnit,
                    $largeUnit,
                    $largeUnit2,
                ])));
            @endphp
            "{{ $p->fprdcode }}": {
                name: @json($p->fprdname),
                default_unit: @json($resolvedDefaultUnit),
                units: @json($orderedUnits),
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
            totalBiayaHPP: 0,
            totalHarga: 0,
            ppnRate: 11,

            initialGrandTotal: @json($famountmt ?? 0),
            initialPpnAmount: @json($famountpajak ?? 0),

            includePPN: @json($includePPN == 1),
            ppnMode: @json((int) $ppnMode),
            ppnRate: @json((float) $ppnRate),

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
                const activeRows = (this.savedItems || []).filter((item) => {
                    const code = (item?.fitemcode || '').toString().trim();
                    const qty = Number(item?.fqty || 0);
                    return code !== '' || qty > 0;
                });

                if (!activeRows.length) {
                    return false;
                }

                return activeRows.every((item) => (item?.fsource || '').toString().trim().toUpperCase() === 'PB');
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

            sanitizePriceValue(value) {
                let str = (value ?? '').toString().trim();
                if (str === '') return '';
                if (str.includes(',')) {
                    str = str.replace(/\./g, '').replace(',', '.');
                }
                const raw = str.replace(/[^0-9.]/g, '');
                const parts = raw.split('.');
                if (parts.length <= 1) return raw;
                return `${parts.shift()}.${parts.join('')}`;
            },

            focusPriceInput(row) {
                const price = Math.max(0, +row.fprice || 0);
                row.fpriceInput = price > 0 ? this.fmt(price) : '';
            },

            onPriceInput(row) {
                row.fpriceInput = this.sanitizePriceValue(row.fpriceInput);
                row.fprice = Math.max(0, +(row.fpriceInput || 0));
                this.recalc(row);
            },

            blurPriceInput(row) {
                row.fprice = Math.max(0, +(this.sanitizePriceValue(row.fpriceInput) || 0));
                row.fpriceInput = this.fmt(row.fprice);
                this.recalc(row);
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
                if (cleaned === '') return '0';
                if (!cleaned.includes('+')) {
                    const num = Number(cleaned);
                    if (Number.isFinite(num)) {
                        // Remove unnecessary trailing zeros/decimals
                        return String(parseFloat(num.toFixed(10)));
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
                if (typeof row.fpriceInput === 'undefined') {
                    row.fpriceInput = this.fmt(row.fprice);
                }
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
                this.totalBiayaHPP = this.savedItems.reduce((sum, item) => sum + (item.fbiaya * item.fqty || 0), 0);
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
                return '';
            },

            qtyToKecil(code, qty, satuan) {
                const meta = this.productMeta(code);
                const units = meta?.units || [];
                const ratios = meta?.unit_ratios || {
                    satuankecil: 1,
                    satuanbesar: 1,
                    satuanbesar2: 1
                };
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
                const ratios = meta?.unit_ratios || {
                    satuankecil: 1,
                    satuanbesar: 1,
                    satuanbesar2: 1
                };
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
                const matchedUnit = preferredUnit === '' ? '' : (units.find(u => u.toLowerCase() === preferredUnit
                    .toLowerCase()) || '');

                row.units = matchedUnit !== '' ?
                    [matchedUnit, ...units.filter(u => u.toLowerCase() !== matchedUnit.toLowerCase())] :
                    units;

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
                return (document.getElementById('supplierCodeHidden')?.value || document.getElementById(
                    'modal_filter_supplier_id')?.value || '').trim();
            },

            requireSupplierBeforeManualProduct() {
                if (this.getSelectedSupplierCode()) return true;
                this.showSupplierRequired = true;
                return false;
            },

            setSupplierFromReferenceHeader(header) {
                const supplierCode = (header?.fsupplier || header?.fsuppliercode || '').toString().trim();
                if (!supplierCode) return;
                const supplierName = (header?.fsuppliername || header?.supplier_name || '').toString().trim();
                const supplierLabel = supplierName ? `${supplierName} (${supplierCode})` : supplierCode;

                const hiddenInput = document.getElementById('supplierCodeHidden');
                const selectInput = document.getElementById('modal_filter_supplier_id');
                const tempoInput = document.getElementById('ftempohr');
                const headerTempo = Number(header?.ftempohr ?? '');
                let tempoApplied = false;

                if (hiddenInput) {
                    hiddenInput.value = supplierCode;
                    hiddenInput.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                }

                if (selectInput) {
                    let option = Array.from(selectInput.options || []).find(opt => (opt.value || '').trim() ===
                        supplierCode);
                    if (!option) {
                        option = new Option(supplierLabel, supplierCode, true, true);
                        selectInput.add(option);
                    } else {
                        option.text = supplierLabel;
                        option.selected = true;
                    }

                    selectInput.value = supplierCode;
                    selectInput.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));

                    if (tempoInput && Number.isFinite(headerTempo)) {
                        tempoInput.value = headerTempo;
                        tempoInput.dispatchEvent(new Event('input', {
                            bubbles: true
                        }));
                        tempoApplied = true;
                    }

                    const tempo = Number(option?.getAttribute('data-tempo') || 0);
                    if (!tempoApplied && tempoInput && Number.isFinite(tempo)) {
                        tempoInput.value = tempo;
                        tempoInput.dispatchEvent(new Event('input', {
                            bubbles: true
                        }));
                    }
                }
            },
            setPpnFromReferenceHeader(header) {
                const hasApplyFlag = header && header.fapplyppn !== undefined && header.fapplyppn !== null;
                const hasRate = header && header.fppnpersen !== undefined && header.fppnpersen !== null;
                const hasMode = header && header.fincludeppn !== undefined && header.fincludeppn !== null;

                if (!hasApplyFlag && !hasRate && !hasMode) {
                    return;
                }

                this.includePPN = Number(header?.fapplyppn || 0) === 1;
                this.ppnMode = Number(header?.fincludeppn || 0) === 1 ? 1 : 0;

                const rate = Number(header?.fppnpersen ?? 0);
                this.ppnRate = Number.isFinite(rate) && rate >= 0 ? rate : 0;
                this.recalcTotals();
            },
            getNormalizedReferencePpnConfig(header = null) {
                const apply = Number(header?.fapplyppn || 0) === 1 ? 1 : 0;
                const mode = Number(header?.fincludeppn || 0) === 1 ? 1 : 0;
                const rate = Number(header?.fppnpersen ?? 0);

                return {
                    apply,
                    mode,
                    rate: Number.isFinite(rate) && rate >= 0 ? Number(rate.toFixed(2)) : 0
                };
            },
            getExistingSourcePpnConfig() {
                const activeSourceRows = (this.savedItems || []).filter((item) => {
                    const sourceType = (item?.fsource || '').toString().trim().toUpperCase();
                    const code = (item?.fitemcode || '').toString().trim();
                    const qty = Number(item?.fqty || 0);
                    return ['PO', 'PB'].includes(sourceType) && (code !== '' || qty > 0);
                });

                if (!activeSourceRows.length) {
                    return null;
                }

                return {
                    apply: this.includePPN ? 1 : 0,
                    mode: Number(this.ppnMode || 0) === 1 ? 1 : 0,
                    rate: Number.isFinite(Number(this.ppnRate)) ? Number(Number(this.ppnRate).toFixed(2)) : 0
                };
            },
            hasConflictingReferencePpn(header) {
                const existing = this.getExistingSourcePpnConfig();
                if (!existing) {
                    return false;
                }

                const incoming = this.getNormalizedReferencePpnConfig(header);
                return existing.apply !== incoming.apply || existing.mode !== incoming.mode || existing.rate !==
                    incoming.rate;
            },
            showConflictingReferencePpnWarning() {
                window.showAppWarningAlert(
                    'Perhatian',
                    'Setting PPN referensi berbeda. Dalam satu faktur pembelian, referensi PO atau TER harus memakai setting PPN yang sama.'
                );
            },
            hasSourceLockedSupplier() {
                return (this.savedItems || []).some(item => ['PO', 'PB'].includes((item?.fsource || '').toString()
                .trim().toUpperCase()));
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
                if (this.hasConflictingReferencePpn(header)) {
                    this.showConflictingReferencePpnWarning();
                    return;
                }

                this.setSupplierFromReferenceHeader(header);
                this.setPpnFromReferenceHeader(header);
                this.addManyFromSource(header, items, 'PO');
            },

            onPbPicked(e) {
                const {
                    header,
                    items
                } = e.detail || {};
                if (!items || !Array.isArray(items)) return;
                if (this.hasConflictingReferencePpn(header)) {
                    this.showConflictingReferencePpnWarning();
                    return;
                }

                this.setSupplierFromReferenceHeader(header);
                this.setPpnFromReferenceHeader(header);

                // Automatically fill in the warehouse field
                if (header && (header.fgudang || header.ffrom)) {
                    const whCode = header.fgudang || header.ffrom;
                    const sel = document.getElementById('warehouseSelect');
                    const hid = document.getElementById('warehouseCodeHidden');
                    if (sel) {
                        sel.value = whCode;
                        sel.dispatchEvent(new Event('change', {
                            bubbles: true
                        }));
                    }
                    if (hid) {
                        hid.value = whCode;
                    }
                }

                this.addManyFromSource(header, items, 'PB');
            },

            normalizeRefNoAcak(value) {
                const parts = String(value ?? '').split(',').map(part => part.trim()).filter(part => /^\d{3}$/.test(
                    part));
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
                    const sourceQtyKecil = Math.max(0, +(src.fqtykecil ?? src.fqtyremain ?? src.fqty ?? 0) ||
                    0);
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
                        fbiaya: sourceType === 'PB' ? +(src.fbiaya || 0) : 0,
                        ftotprice: +(src.fharga || 0),

                        fdesc: src.fdesc || '',
                        units: Array.isArray(src.units) && src.units.length ? src.units : [src.fsatuan]
                            .filter(Boolean)
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
                    ].some((value) => String(value ?? '').trim() !== '' && Number(value ?? 0) !== 0) ||
                    Number(row.fqty || 0) > 0;
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

                this.savedItems = this.pendingValidRows.map((row) => ({
                    ...row
                }));
                this.recalcTotals();
                const form = this.pendingSubmitForm;
                this.closeWarning();
                this.$nextTick(() => {
                    this.syncDetailPayload(form, this.savedItems);
                    window.submitFormWithStockMinusConfirmation?.(form);
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
                return this.hasDesc(value) ?
                    'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' :
                    'border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100';
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
                if (this.descTarget === 'saved' && this.descSavedIndex !== null && this.savedItems[this
                    .descSavedIndex]) {
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
                return (this.savedItems || []).some((row) => ['PO', 'PB'].includes((row?.fsource || '').toString()
                .trim().toUpperCase()));
            },

            hasMixedOpeningBalanceAndSourceRows(rows = this.savedItems) {
                const activeRows = (rows || []).filter((row) => this.isRowSavable(row));
                const hasOpeningBalance = activeRows.some((row) => this.isOpeningBalanceCode(row?.fitemcode));
                const hasSourceReference = activeRows.some((row) => ['PO', 'PB'].includes((row?.fsource || '')
                .toString().trim().toUpperCase()));
                return hasOpeningBalance && hasSourceReference;
            },

            showOpeningBalanceMixWarning() {
                const message =
                    'Item AWAL tidak boleh digabung dengan item referensi PO atau TER dalam satu faktur pembelian.';
                if (window.showTransactionErrorModal) {
                    window.showTransactionErrorModal(message, {
                        title: 'Kombinasi Item Tidak Diizinkan'
                    });
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
                    typeSelect.dispatchEvent(new Event('input', {
                        bubbles: true
                    }));
                    typeSelect.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
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
                this.biayaGlobal = this.totalBiayaHPP;
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
                        this.rows.splice(this.browseTarget, 1, {
                            ...this.rows[this.browseTarget]
                        });
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
                const warehouseCode = (document.getElementById('warehouseCodeHidden')?.value || '').toString().trim();
                if (!warehouseCode) {
                    if (window.showTransactionErrorModal) {
                        window.showTransactionErrorModal('Gudang wajib diisi sebelum menyimpan data.', {
                            title: 'Gudang Kosong'
                        });
                    } else if (window.Swal) {
                        window.Swal.fire({
                            icon: 'warning',
                            title: 'Gudang Kosong',
                            text: 'Gudang wajib diisi sebelum menyimpan data.',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        window.showAppWarningAlert('WARNING', 'Gudang wajib diisi sebelum menyimpan data.');
                    }
                    return;
                }

                const validRows = this.savedItems.filter((row) => this.isRowSavable(row));
                const warningRows = this.savedItems.filter((row) => this.isRowFilled(row) && !this.isRowSavable(row));
                const seenCodes = new Set();

                for (const row of validRows) {
                    const code = (row.fitemcode || '').toString().trim().toUpperCase();
                    if (!code) continue;
                    if (seenCodes.has(code)) {
                        if (window.showTransactionErrorModal) {
                            window.showTransactionErrorModal(
                                `Kode produk ${code} tidak boleh sama dalam satu Faktur Pembelian.`, {
                                    title: 'Produk Duplikat'
                                });
                        } else {
                            window.showAppWarningAlert('WARNING',
                                `KODE PRODUK ${code} TIDAK BOLEH SAMA DALAM SATU FAKTUR PEMBELIAN.`);
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
                    this.warningMessage = validRows.length > 0 ?
                        'Beberapa item tidak akan disimpan karena qty masih 0.' :
                        'Tidak ada item yang bisa disimpan karena qty masih 0 atau data belum lengkap.';
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

                this.savedItems = validRows.map((row) => ({
                    ...row
                }));
                this.recalcTotals();
                this.$nextTick(() => {
                    this.syncDetailPayload(form, this.savedItems);
                    window.submitFormWithStockMinusConfirmation?.(form);
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
                        productCodeFilter: document.querySelector('select[name="ftypebuy"]')?.value ===
                            '2' ? 'UM' : ''
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
                fpriceInput: undefined,
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
                    window.showTransactionErrorModal(message, {
                        title: 'Referensi Tidak Bisa Dipakai'
                    });
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
                                supplier_code: (document.getElementById('supplierCodeHidden')?.value ||
                                    document.getElementById('modal_filter_supplier_id')?.value || ''
                                    ).trim(),
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
                                return '<button type="button" class="btn-pick px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150">{{ 'Pilih' }}</button>';
                            }
                        }
                    ],
                    pageLength: 10,
                    lengthMenu: [
                        [10, 25, 50, 100],
                        [10, 25, 50, 100]
                    ],
                    dom: '<"flex justify-between items-center mb-4"f<"ml-auto"l>>rtip',
                    order: [
                        [2, 'desc']
                    ],
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
                    const url = `{{ route('fakturpembelian.itemsPO', ['id' => 'PO_ID_PLACEHOLDER']) }}`
                        .replace('PO_ID_PLACEHOLDER', row.fpohid);
                    const res = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
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
                        detail: {
                            header,
                            items
                        }
                    }));
                    this.closeModal();
                } catch (e) {
                    console.error(e);
                    console.log(@json('Gagal mengambil detail PO. Lihat konsol untuk detail.'));
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
                    window.showTransactionErrorModal(message, {
                        title: 'Referensi Tidak Bisa Dipakai'
                    });
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
                                supplier_code: (document.getElementById('supplierCodeHidden')?.value ||
                                    document.getElementById('modal_filter_supplier_id')?.value || ''
                                    ).trim(),
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
                                data: 'fbranchcode',
                                name: 'fbranchcode',
                                className: 'text-sm',
                                render: function(data) {
                                    return data || '-';
                                }
                            },
                            {
                                data: 'fstockmtno',
                                name: 'fstockmtno',
                                className: 'font-mono text-sm'
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
                                data: 'fsupplier',
                                name: 'fsupplier',
                                className: 'text-sm',
                                render: function(data) {
                                    return data || '-';
                                }
                            },
                            {
                                data: 'fgudang',
                                name: 'fgudang',
                                className: 'text-sm',
                                render: function(data) {
                                    return data || '-';
                                }
                            },
                            {
                                data: 'frefpo',
                                name: 'frefpo',
                                className: 'font-mono text-sm',
                                render: function(data) {
                                    return data || '-';
                                }
                            },
                            {
                                data: null,
                                orderable: false,
                                searchable: false,
                                className: 'text-center',
                                render: function() {
                                    return '<button type="button" class="btn-pick px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150">{{ 'Pilih' }}</button>';
                                }
                            }
                        ],
                        pageLength: 10,
                        lengthMenu: [
                            [10, 25, 50, 100],
                            [10, 25, 50, 100]
                        ],
                        dom: '<"flex justify-between items-center mb-4"f<"ml-auto"l>>rtip',
                        order: [
                            [2, 'desc']
                        ],
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
                    const url = `{{ route('fakturpembelian.itemsPB', ['id' => 'PB_ID_PLACEHOLDER']) }}`
                        .replace('PB_ID_PLACEHOLDER', row.fstockmtid);
                    const res = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
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
                        detail: {
                            header,
                            items
                        }
                    }));
                    this.closeModal();
                } catch (e) {
                    console.error(e);
                    console.log(@json('Gagal mengambil detail PB. Lihat konsol untuk detail.'));
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
        return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
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
                                return '<button type="button" class="btn-choose px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150">{{ 'Pilih' }}</button>';
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
                        processing: @json('Memuat data...'),
                        search: @json('Search' . ':'),
                        lengthMenu: @json('Tampilkan _MENU_'),
                        info: @json('Menampilkan _START_ - _END_ dari _TOTAL_ data'),
                        infoEmpty: @json('Tidak ada data'),
                        infoFiltered: @json('(disaring dari _MAX_ total data)'),
                        zeroRecords: @json('Tidak ada data yang ditemukan'),
                        emptyTable: @json('Tidak ada data tersedia'),
                        paginate: {
                            first: @json('Pertama'),
                            last: @json('Terakhir'),
                            next: @json('Selanjutnya'),
                            previous: @json('Sebelumnya')
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
                faccid,
                faccname
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
                const code = String(faccount || '').trim();
                const label = faccname ? `${code} - ${faccname}` : code;
                let opt = [...sel.options].find((o) => String(o.value).trim() === code);
                if (code && !opt) {
                    opt = new Option(label, code, true, true);
                    sel.add(opt);
                } else if (opt) {
                    opt.text = label;
                    opt.selected = true;
                }
                sel.value = opt ? opt.value : code;
                sel.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }

            if (hidId) {
                hidId.value = faccid || '';
                hidId.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }

            if (hidCode) {
                hidCode.value = faccount || '';
                hidCode.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }
        });
    });
</script>

<script>
    // Helper: update field saat warehouse-picked
    document.addEventListener('DOMContentLoaded', () => {
        window.addEventListener('warehouse-picked', (ev) => {
            const {
                fwhcode,
                fwhname
            } = ev.detail || {};
            const sel = document.getElementById('warehouseSelect');
            const hid = document.getElementById('warehouseCodeHidden');
            if (sel) {
                const code = String(fwhcode || '').trim();
                let opt = [...sel.options].find((o) => String(o.value).trim() === code);
                if (code && !opt) {
                    opt = new Option(fwhname ? `${fwhname} (${code})` : code, code, true, true);
                    sel.add(opt);
                }
                sel.value = opt ? opt.value : code;
                sel.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }
            if (hid) {
                hid.value = fwhcode || '';
                hid.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const supplierAdvanceWarnings = @json($supplierAdvanceWarnings ?? []);
        const warningBox = document.getElementById('supplierAdvanceWarningBox');
        const warningText = document.getElementById('supplierAdvanceWarningText');
        const hiddenInput = document.getElementById('supplierCodeHidden');
        const selectInput = document.getElementById('modal_filter_supplier_id');
        let lastPopupSupplierCode = '';

        const showSupplierAdvancePopup = (message) => {
            if (!message) {
                return;
            }

            if (typeof window.showAppWarningAlert === 'function') {
                window.showAppWarningAlert('Perhatian', message, {
                    confirmButtonText: 'Ok'
                });
                return;
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Perhatian',
                    text: message,
                    confirmButtonText: 'Ok'
                });
            }
        };

        const updateSupplierAdvanceWarning = (supplierCode = null, shouldPopup = false) => {
            if (!warningBox || !warningText) {
                return;
            }

            const code = (supplierCode ?? hiddenInput?.value ?? selectInput?.value ?? '').toString().trim();
            const warning = supplierAdvanceWarnings[code] ?? null;

            if (!warning || !warning.message) {
                warningBox.classList.add('hidden');
                warningText.textContent = '';
                if (code !== lastPopupSupplierCode) {
                    lastPopupSupplierCode = '';
                }
                return;
            }

            warningText.textContent = warning.message;
            warningBox.classList.remove('hidden');

            if (shouldPopup && code !== '' && code !== lastPopupSupplierCode) {
                lastPopupSupplierCode = code;
                showSupplierAdvancePopup(warning.message);
            }
        };

        hiddenInput?.addEventListener('change', () => updateSupplierAdvanceWarning(null, true));
        selectInput?.addEventListener('change', () => updateSupplierAdvanceWarning(null, true));
        window.addEventListener('supplier-picked', (event) => {
            updateSupplierAdvanceWarning(event.detail?.fsuppliercode ?? '', true);
        });

        updateSupplierAdvanceWarning();
    });
</script>

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    @include('components.transaction.browse-warehouse-script', [
        'eventName' => 'faktur-pembelian-warehouse-browse-open',
    ])
    @include('components.transaction.browse-product-script', [
        'showControls' => true,
        'showPagination' => true,
        'supportsForEdit' => true,
    ])
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
                    const code = String(faccount || '').trim();
                    let opt = [...sel.options].find(o => String(o.value).trim() === code);
                    const label = faccname ? `${code} - ${faccname}` : code;

                    if (code && !opt) {
                        opt = new Option(label, code, true, true);
                        sel.add(opt);
                    } else if (opt) {
                        opt.text = label;
                        opt.selected = true;
                    }
                    sel.value = opt ? opt.value : code;
                    sel.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                }

                if (hidId) {
                    hidId.value = faccid || '';
                    hidId.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                }
                if (hidCode) {
                    hidCode.value = faccount || '';
                    hidCode.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                }
            });
        });
    </script>
@endpush
