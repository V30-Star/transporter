@extends('layouts.app')

@section('title', 'Permintaan Pembelian')

@section('content')
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

                @include('tr_prh._form', [
                    'isReadOnly' => true,
                    'isDeleteMode' => true,
                    'detailMode' => 'view',
                    'allowDocumentNoEdit' => false,
                    'tr_prh' => $tr_prh,
                    'fcabang' => $fcabang,
                    'fbranchcode' => $fbranchcode,
                    'suppliers' => $suppliers,
                    'filterSupplierId' => old('fsupplier', $tr_prh->fsupplier ?? ''),
                ])

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
        @endsection
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
