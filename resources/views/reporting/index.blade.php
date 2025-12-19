@extends('layouts.app')

@section('title', 'Laporan Pembelian / PO')

@section('content')
    <div class="p-6 bg-white shadow-md rounded-lg">
        <h2 class="text-xl font-bold mb-4">Laporan Pembelian / PO</h2>

        <div class="flex flex-wrap items-center gap-4 mb-6">
            {{-- Tombol Pemicu Modal --}}
            <button onclick="toggleModal(true)"
                style="padding: 6px 16px; background-color: #3b82f6; color: white; font-size: 0.875rem; border-radius: 0.25rem; display: inline-flex; align-items: center;"
                class="hover:bg-blue-600 transition-colors"> Search Data
            </button>
        </div>

        {{-- --- MODAL FILTER POP-UP --- --}}
        <div id="filterModal" class="fixed inset-0 z-50 bg-black bg-opacity-50 hidden flex items-center justify-center">
            <div class="bg-white rounded-lg shadow-2xl max-w-xl w-full p-6" onclick="event.stopPropagation()">
                <div class="flex justify-between items-center border-b pb-3 mb-4">
                    <h3 class="text-lg font-semibold">Laporan Pembelian / PO</h3>
                    <button onclick="toggleModal(false)"
                        class="text-gray-500 hover:text-gray-800 text-xl font-bold">&times;</button>
                </div>

                <form method="GET" action="{{ route('reporting.printPoh') }}">
                    <div class="grid grid-cols-2 gap-4">
                        {{-- Filter Tanggal Dari --}}
                        <div>
                            <label for="modal_filter_date_from" class="block text-sm font-medium text-gray-700">Tanggal
                                Dari</label>
                            <input type="date" name="filter_date_from" id="modal_filter_date_from"
                                value="{{ $filterDateFrom }}"
                                class="mt-1 block w-full border rounded px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        {{-- Filter Tanggal Sampai --}}
                        <div>
                            <label for="modal_filter_date_to" class="block text-sm font-medium text-gray-700">Tanggal
                                Sampai</label>
                            <input type="date" name="filter_date_to" id="modal_filter_date_to"
                                value="{{ $filterDateTo }}"
                                class="mt-1 block w-full border rounded px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        {{-- Filter Supplier --}}
                        <div class="col-span-2">
                            <label class="block text-sm font-medium mb-1">Supplier</label>
                            <div class="flex">
                                <div class="relative flex-1" for="modal_filter_supplier_id">
                                    <select id="modal_filter_supplier_id" name="filter_supplier_id"
                                        class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
                                        disabled>
                                        <option value=""></option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->fsupplierid }}"
                                                {{ $filterSupplierId == $supplier->fsupplierid ? 'selected' : '' }}>
                                                {{ $supplier->fsuppliername }} ({{ $supplier->fsupplierid }})
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
                            </div>
                            @error('fsupplier')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex justify-end space-x-2 mt-6">
                        {{-- Tombol Reset --}}
                        <a href="{{ route('reporting.index') }}"
                            class="px-4 py-2 bg-gray-300 text-gray-800 text-sm rounded hover:bg-gray-400 transition-colors">
                            Reset
                        </a>
                        {{-- Tombol Terapkan Filter --}}
                        <button type="submit" formaction="{{ route('reporting.printPoh') }}" formtarget="_blank"
                            class="px-4 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition-colors">
                            Preview & Print
                        </button>
                    </div>
                </form>
            </div>

        </div>
        {{-- --- END MODAL FILTER POP-UP --- --}}
    </div>

    {{-- MODAL SUPPLIER --}}
    <div x-data="supplierBrowser()" x-show="open" x-cloak x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="close()"></div>
        <div class="relative bg-white rounded-2xl shadow-xl w-[90vw] **max-w-7xl** max-h-[90vh] flex flex-col">
            <div class="p-4 border-b flex items-center gap-3">
                <h3 class="text-lg font-semibold">Browse Supplier</h3>
                <button type="button" @click="close()"
                    class="ml-auto px-3 py-1 rounded border bg-gray-100 hover:bg-gray-200">Close</button>
            </div>
            <div class="p-4 overflow-auto flex-1">
                <table id="supplierBrowseTable" class="min-w-full text-sm display">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="text-left p-2">Kode</th>
                            <th class="text-left p-2">Nama Supplier</th>
                            <th class="text-left p-2">Alamat</th>
                            <th class="text-left p-2">Telepon</th>
                            <th class="text-center p-2">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- DataTables akan mengisi ini -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection

@push('styles')
    <style>
        /* DataTables Custom Styling */
        #supplierBrowseTable_wrapper .dataTables_length select {
            @apply border rounded px-2 py-1 text-sm;
        }

        #supplierBrowseTable_wrapper .dataTables_filter input {
            @apply border rounded px-3 py-2 text-sm w-64;
        }

        #supplierBrowseTable_wrapper .dataTables_info {
            @apply text-sm text-gray-600;
        }

        #supplierBrowseTable_wrapper .dataTables_paginate {
            @apply flex items-center gap-1;
        }

        #supplierBrowseTable_wrapper .dataTables_paginate .paginate_button {
            @apply px-3 py-2 border rounded text-sm cursor-pointer transition-colors inline-flex items-center justify-center min-w-[36px];
        }

        #supplierBrowseTable_wrapper .dataTables_paginate .paginate_button:hover:not(.disabled) {
            @apply bg-gray-100;
        }

        #supplierBrowseTable_wrapper .dataTables_paginate .paginate_button.current {
            @apply bg-blue-600 text-white border-blue-600 font-semibold;
        }

        #supplierBrowseTable_wrapper .dataTables_paginate .paginate_button.current:hover {
            @apply bg-blue-700;
        }

        #supplierBrowseTable_wrapper .dataTables_paginate .paginate_button.disabled {
            @apply opacity-30 cursor-not-allowed;
        }

        #supplierBrowseTable_wrapper .dataTables_paginate .paginate_button.disabled:hover {
            @apply bg-transparent;
        }

        /* Icon buttons styling */
        #supplierBrowseTable_wrapper .dataTables_paginate .paginate_button svg {
            @apply w-4 h-4;
        }

        #supplierBrowseTable_wrapper .dataTables_processing {
            @apply bg-white/90 flex items-center justify-center;
        }

        /* Table styling */
        #supplierBrowseTable thead th {
            @apply bg-gray-100 font-semibold text-left p-3 border-b-2;
        }

        #supplierBrowseTable tbody td {
            @apply p-3 border-b;
        }

        #supplierBrowseTable tbody tr:hover {
            @apply bg-gray-50;
        }

        /* Ellipsis styling */
        #supplierBrowseTable_wrapper .dataTables_paginate .ellipsis {
            @apply px-2 py-2 text-gray-400;
        }
    </style>
@endpush
@push('scripts')
    {{-- jQuery + DataTables JS (CDN) --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.6/js/dataTables.min.js"></script>
    <script>
        // Fungsi untuk mengontrol modal
        function toggleModal(show) {
            const modal = document.getElementById('filterModal');
            if (show) {
                modal.classList.remove('hidden');
            } else {
                modal.classList.add('hidden');
            }
        }

        $(function() {
            // Tampilkan modal otomatis jika belum ada filter
            @if (!$hasFilter)
                toggleModal(true);
            @endif

            // Hanya inisialisasi DataTables jika ada data (bukan placeholder)
            @if ($hasFilter && $pohData->count() > 0)
                $('#pohReportTable').DataTable({
                    autoWidth: false,
                    pageLength: 10,
                    lengthMenu: [10, 25, 50, 100],
                    order: [
                        [2, 'desc'] // Urutkan berdasarkan Tanggal PO (kolom index 2) secara descending
                    ],
                    layout: {
                        topStart: 'pageLength',
                        topEnd: 'search',
                        bottomStart: 'info',
                        bottomEnd: 'paging'
                    },
                });
            @endif
        });

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
                                    q: d.search.value,
                                    page: (d.start / d.length) + 1,
                                    per_page: d.length,
                                    draw: d.draw // ✅ Tambahkan draw
                                };
                            },
                            dataSrc: function(json) {
                                // ✅ PERBAIKAN: Return json langsung, bukan hanya data
                                return json.data || [];
                            }
                        },
                        columns: [{
                                data: 'fsuppliercode',
                                name: 'fsuppliercode',
                                width: '15%'
                            },
                            {
                                data: 'fsuppliername',
                                name: 'fsuppliername',
                                width: '20%'
                            },
                            {
                                data: 'faddress',
                                name: 'faddress',
                                defaultContent: '-',
                                orderable: false,
                                width: '30%'
                            },
                            {
                                data: 'ftelp',
                                name: 'ftelp',
                                defaultContent: '-',
                                orderable: false,
                                width: '20%'
                            },
                            {
                                data: null,
                                orderable: false,
                                searchable: false,
                                className: 'text-center',
                                width: '20%',
                                render: function(data, type, row) {
                                    // ✅ PERBAIKAN: Escape quotes untuk mencegah error
                                    const code = (row.fsuppliercode || '').replace(/'/g, "\\'");
                                    const name = (row.fsuppliername || '').replace(/'/g, "\\'");
                                    const address = (row.faddress || '').replace(/'/g, "\\'");
                                    const telp = (row.ftelp || '').replace(/'/g, "\\'");

                                    return `<button type="button" 
                                onclick="window.chooseSupplier('${row.fsupplierid}', '${code}', '${name}', '${address}', '${telp}')" 
                                class="px-3 py-1 rounded text-xs bg-emerald-600 hover:bg-emerald-700 text-white">
                                Pilih
                            </button>`;
                                }
                            }
                        ],
                        pageLength: 10,
                        lengthMenu: [10, 25, 50, 100],
                        order: [
                            [1, 'asc']
                        ],
                        dom: '<"flex items-center justify-between mb-4"<"flex items-center gap-2"l><"flex-1"><"flex items-center"f>>' +
                            '<"overflow-x-auto"t>' +
                            '<"flex items-center justify-between mt-4"<"text-sm text-gray-600"i><"flex items-center gap-2"p>>',
                        language: {
                            search: "_INPUT_",
                            searchPlaceholder: "Cari kode atau nama supplier...",
                            lengthMenu: "Tampilkan _MENU_",
                            info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                            infoEmpty: "Tidak ada data",
                            infoFiltered: "(difilter dari _MAX_ total data)",
                            paginate: {
                                first: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>',
                                last: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>',
                                next: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>',
                                previous: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>'
                            },
                            processing: '<div class="flex items-center justify-center"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div></div>',
                            zeroRecords: "Tidak ada data yang ditemukan"
                        },
                        drawCallback: function() {
                            $('.dataTables_paginate .paginate_button').addClass(
                                'px-3 py-2 border rounded mx-0.5 hover:bg-gray-100 transition-colors inline-flex items-center justify-center'
                            );
                            $('.dataTables_paginate .paginate_button.current').addClass(
                                'bg-blue-600 text-white border-blue-600 hover:bg-blue-700');
                            $('.dataTables_paginate .paginate_button.disabled').addClass(
                                'opacity-50 cursor-not-allowed hover:bg-transparent');

                            $('.dataTables_paginate .paginate_button.first, .dataTables_paginate .paginate_button.last, .dataTables_paginate .paginate_button.previous, .dataTables_paginate .paginate_button.next')
                                .css('min-width', '36px');
                        }
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
                        this.dataTable.destroy();
                        this.dataTable = null;
                    }
                },

                init() {
                    window.chooseSupplier = (id, code, name, address, telp) => {
                        const sel = document.getElementById('modal_filter_supplier_id');
                        const hid = document.getElementById('supplierCodeHidden');

                        if (!sel) {
                            this.close();
                            return;
                        }

                        let opt = [...sel.options].find(o => o.value == String(id));
                        const label = `${name} (${code})`;

                        if (!opt) {
                            opt = new Option(label, id, true, true);
                            sel.add(opt);
                        } else {
                            opt.text = label;
                            opt.selected = true;
                        }

                        sel.dispatchEvent(new Event('change'));
                        if (hid) hid.value = id;
                        this.close();
                    };
                    $container.find('.dt-search .dt-input, .dataTables_filter input').focus();


                    window.addEventListener('supplier-browse-open', () => this.openBrowse(), {
                        passive: true
                    });
                }
            }
        }
    </script>
@endpush
