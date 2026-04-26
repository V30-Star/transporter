@extends('layouts.app')

@section('title', 'Master Customer')

@section('content')
    <div class="bg-white rounded shadow p-4">

        @php
            $canCreate = in_array('createCustomer', explode(',', session('user_restricted_permissions', '')));
            $canEdit = in_array('updateCustomer', explode(',', session('user_restricted_permissions', '')));
            $canDelete = in_array('deleteCustomer', explode(',', session('user_restricted_permissions', '')));
            $showActionsColumn = $canEdit || $canDelete;
        @endphp

        <div class="flex justify-end items-center mb-4">
            <div></div>

            @if ($canCreate)
                <a href="{{ route('customer.create') }}"
                    class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    <x-heroicon-o-plus class="w-4 h-4 mr-1" /> Tambah Baru
                </a>
            @endif
        </div>

        <div id="statusFilterTemplate" class="hidden">
            <div class="flex items-center gap-2" id="statusFilterWrap">
                <span class="text-sm text-gray-700">Status</span>
                <select data-role="status-filter" class="border rounded px-2 py-1">
                    <option value="all">All</option>
                    <option value="active" selected>Active</option>
                    <option value="nonactive">Non Active</option>
                </select>
            </div>
        </div>

        {{-- Tabel --}}
        <table id="customerTable" class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-2">Kode Customer</th>
                    <th class="border px-2 py-2">Nama Customer</th>
                    <th class="border px-2 py-2 no-sort">
                        <div class="flex items-center justify-between">
                            <span>Wilayah</span>
                            <div class="flex items-center gap-1">
                                <button type="button" class="col-search-btn p-1 hover:bg-gray-200 rounded"
                                    data-column="2" title="Filter Kolom">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="col-search-input mt-2 hidden">
                            <input type="text"
                                class="dt-column-search w-full px-2 py-1.5 border border-gray-300 rounded text-sm uppercase focus:outline-none focus:ring-1 focus:ring-blue-500"
                                data-column="2" placeholder="Cari...">
                        </div>
                    </th>
                    <th class="border px-2 py-2 no-sort">Alamat</th>
                    <th class="border px-2 py-2 no-sort">Status</th>
                    {{-- Kolom StatusRaw Dihapus --}}
                    @if ($showActionsColumn)
                        <th class="border px-2 py-2 col-aksi">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>

    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.6/css/dataTables.dataTables.min.css">
    <style>
        /* Tata letak kontrol */
        .dt-container .dt-length,
        .dt-container .dt-search {
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .dt-container .dt-length .dt-input {
            width: 4.5rem;
            padding: .35rem .5rem;
        }

        /* Stabilkan tabel */
        #customerTable {
            width: 100% !important;
        }

        #customerTable th,
        #customerTable td {
            text-align: left !important;
            vertical-align: middle;
        }

        /* Kolom Aksi: jangan mepet, tapi tetap ringkas */
        #customerTable th:last-child,
        #customerTable td:last-child {
            white-space: nowrap;
            text-align: center;
        }

        #customerTable td:last-child {
            padding: .25rem .5rem;
        }

        .btn-aksi {
            padding: .25rem .5rem;
            font-size: .825rem;
        }

        #customerTable th,
        #customerTable td {
            text-align: left !important;
            vertical-align: middle;
        }

        #customerTable th:last-child,
        #customerTable td:last-child {
            text-align: center;
            white-space: nowrap;
        }

        #customerTable td.customer-address-cell {
            white-space: normal !important;
            word-break: break-word;
            overflow-wrap: anywhere;
            vertical-align: top;
        }

        .dataTables_wrapper .dt-search {
            display: flex;
            align-items: center;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .col-search-btn {
            line-height: 1;
        }

        #statusFilterWrap {
            margin-right: .25rem;
        }
    </style>
@endpush

@push('scripts')
    {{-- jQuery + DataTables JS (CDN) --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.6/js/dataTables.min.js"></script>

    {{-- Script inisialisasi DataTables --}}
    <script>
        $(function() {
            // Inisialisasi DataTables
            const hasActions = {{ $showActionsColumn ? 'true' : 'false' }};
            const canEdit = {{ $canEdit ? 'true' : 'false' }};
            const canDelete = {{ $canDelete ? 'true' : 'false' }};
            const $customerTable = $('#customerTable');
            let activeColumnSearch = null;

            function syncColumnSearchVisibility() {
                const $dtContainer = $customerTable.closest('.dt-container');

                $dtContainer.find('.col-search-input').addClass('hidden');

                if (activeColumnSearch === null) {
                    return;
                }

                const $activeInputWrap = $dtContainer.find(`.dt-column-search[data-column="${activeColumnSearch}"]`)
                    .closest('.col-search-input');

                if ($activeInputWrap.length) {
                    $activeInputWrap.removeClass('hidden');
                }
            }

            function hasColumnSearchValue(columnIndex) {
                const $input = $customerTable.closest('.dt-container')
                    .find(`.dt-column-search[data-column="${columnIndex}"]`);

                return $input.length && String($input.val() || '').trim() !== '';
            }

            // Targetkan kolom yg tidak bisa di-sort
            const columnDefs = [{
                targets: [2, 3, 4, 5],
                orderable: false
            }];

            const columns = [{
                    data: 'fcustomercode'
                },
                {
                    data: 'fcustomername'
                },
                {
                    data: 'wilayah_name'
                },
                {
                    data: 'faddress',
                    className: 'customer-address-cell',
                    render: function(data, type) {
                        const value = data || '-';

                        if (type !== 'display') {
                            return value;
                        }

                        const escaped = String(value)
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;')
                            .replace(/"/g, '&quot;')
                            .replace(/'/g, '&#039;')
                            .replace(/\r?\n/g, '<br>');

                        return `<div class="whitespace-normal break-words leading-5">${escaped}</div>`;
                    }
                },
                {
                    data: 'status',
                    searchable: false
                }
            ];

            if (hasActions) {
                columns.push({
                    data: 'fcustomerid',
                    name: 'actions',
                    orderable: false,
                    searchable: false,
                    className: 'col-aksi',
                    render: function(data, type, row) {
                        let html = '<div class="space-x-2">';
                        let viewUrl = '{{ route('customer.index') }}/' + data + '/view';

                        html += `<a href="${viewUrl}" class="inline-flex items-center bg-slate-500 text-white px-4 py-2 rounded hover:bg-slate-600">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg> View
                </a>`;

                        if (canEdit) {
                            let editUrl = '{{ route('customer.index') }}/' + data + '/edit';
                            html += `<a href="${editUrl}" class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg> Edit
                    </a>`;
                        }

                        if (canDelete) {
                            let deleteUrl = '{{ route('customer.index') }}/' + data + '/delete';
                            html += `<a href="${deleteUrl}">
                        <button class="inline-flex items-center bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg> Hapus
                        </button>
                    </a>`;
                        }
                        html += '</div>';
                        return html;
                    }
                });

                columnDefs.push({
                    targets: -1,
                    orderable: false,
                    searchable: false
                });
            }

            const table = $('#customerTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('customer.index') }}",
                    type: "GET",
                    data: function(d) {
                        d.status = $('#statusFilterDT').val() || 'active';
                    }
                },
                columns: columns,
                columnDefs: columnDefs,
                autoWidth: false,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [
                    [0, 'asc']
                ],
                layout: {
                    topStart: 'search',
                    topEnd: 'pageLength',
                    bottomStart: 'info',
                    bottomEnd: 'paging'
                },
                language: {
                    lengthMenu: "Show _MENU_ entries",
                    processing: '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>'
                },
                initComplete: function() {
                    const api = this.api();
                    const $container = $(api.table().container());
                    const $toolbarSearch = $container.find('.dt-search');

                    // 1. Setup Status Filter
                    const $filter = $('#statusFilterTemplate #statusFilterWrap').clone(true, true);
                    const $select = $filter.find('select[data-role="status-filter"]');
                    $select.attr('id', 'statusFilterDT');
                    $toolbarSearch.append($filter);
                    $select.val('{{ $status ?? 'active' }}');

                    // 2. FORCE UPPERCASE & STYLING SEARCH
                    const $searchInput = $toolbarSearch.find('.dt-input');
                    $searchInput.css({
                        'width': '400px',
                        'maxWidth': '100%',
                        'text-transform': 'uppercase'
                    });

                    // Handler input dengan delegasi agar kursor tidak meloncat
                    $container.on('input', '.dt-search .dt-input', function() {
                        const start = this.selectionStart;
                        const end = this.selectionEnd;
                        this.value = this.value.toUpperCase();
                        this.setSelectionRange(start, end);

                        // Pada server-side, draw() akan memicu request ajax baru dengan search term yang baru
                        api.search(this.value).draw();
                    });

                    // 3. Event Handler Change Filter
                    $select.on('change', function() {
                        table.ajax.reload();
                    });
                },
                drawCallback: function() {
                    syncColumnSearchVisibility();
                }
            });

            const $container = $(table.table().container());

            $container.on('click', '.col-search-btn', function(e) {
                e.stopPropagation();
                const columnIndex = $(this).data('column');
                const $th = $(this).closest('th');
                const $searchInput = $th.find('.col-search-input');

                if (activeColumnSearch === columnIndex && !$searchInput.hasClass('hidden')) {
                    activeColumnSearch = null;
                    $searchInput.addClass('hidden');
                    return;
                }

                activeColumnSearch = columnIndex;
                syncColumnSearchVisibility();

                if (!$searchInput.hasClass('hidden')) {
                    $searchInput.find('input').focus();
                }
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('.col-search-btn').length && !$(e.target).closest(
                        '.col-search-input').length) {
                    if (activeColumnSearch !== null && hasColumnSearchValue(activeColumnSearch)) {
                        syncColumnSearchVisibility();
                        return;
                    }

                    activeColumnSearch = null;
                    syncColumnSearchVisibility();
                }
            });

            $container.on('input', '.dt-column-search', function() {
                const columnIndex = $(this).data('column');
                const cursorStart = this.selectionStart;
                const cursorEnd = this.selectionEnd;
                this.value = this.value.toUpperCase();
                this.setSelectionRange(cursorStart, cursorEnd);
                table.column(columnIndex).search(this.value).draw();
            });
        });
    </script>
@endpush
