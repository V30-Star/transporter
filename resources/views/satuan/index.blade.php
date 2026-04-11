@extends('layouts.app')

@section('title', 'Master Satuan')

@section('content')
    @php
        $canCreate = in_array('createSatuan', explode(',', session('user_restricted_permissions', '')));
        $canEdit   = in_array('updateSatuan', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteSatuan', explode(',', session('user_restricted_permissions', '')));
        $showActionsColumn = $canEdit || $canDelete;
    @endphp

    <div class="bg-white rounded shadow p-4">

        {{-- Tombol Tambah Baru --}}
        <div class="flex justify-end items-center mb-4">
            @if ($canCreate)
                <a href="{{ route('satuan.create') }}"
                    class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    <x-heroicon-o-plus class="w-4 h-4 mr-1" /> Tambah Baru
                </a>
            @endif
        </div>

        {{-- Template Filter Status (hidden, akan di-clone ke toolbar DataTables) --}}
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
        <table id="satuanTable" class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-2">Kode Satuan</th>
                    <th class="border px-2 py-2">Nama Satuan</th>
                    <th class="border px-2 py-2 no-sort">Status</th>
                    {{-- Kolom hidden untuk filter client-side, berisi nilai mentah 0/1 --}}
                    <th data-col="statusRaw" class="border px-2 py-2">StatusRaw</th>
                    @if ($showActionsColumn)
                        <th class="border px-2 py-2 col-aksi">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($satuans as $item)
                    @php $isActive = (string) $item->fnonactive === '0'; @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="border px-2 py-1">{{ $item->fsatuancode }}</td>
                        <td class="border px-2 py-1">{{ $item->fsatuanname }}</td>

                        {{-- Tampilan badge Status --}}
                        <td class="border px-2 py-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs
                                {{ $isActive ? 'bg-green-100 text-green-700' : 'bg-red-200 text-red-700' }}">
                                {{ $isActive ? 'Active' : 'Non Active' }}
                            </span>
                        </td>

                        {{-- Nilai mentah fnonactive (0 atau 1) — disembunyikan oleh DataTables --}}
                        <td class="border px-2 py-1">{{ $item->fnonactive }}</td>

                        @if ($showActionsColumn)
                            <td class="border px-2 py-1 space-x-2 text-center">
                                @if ($canEdit)
                                    <a href="{{ route('satuan.edit', $item->fsatuanid) }}"
                                        class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                                        Edit
                                    </a>
                                @endif
                                @if ($canDelete)
                                    <a href="{{ route('satuan.delete', $item->fsatuanid) }}"
                                        class="inline-flex items-center bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                                        Hapus
                                    </a>
                                @endif
                            </td>
                        @endif
                    </tr>
                @endforeach
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
        #satuanTable {
            width: 100% !important;
        }

        #satuanTable th,
        #satuanTable td {
            text-align: left !important;
            vertical-align: middle;
        }

        #satuanTable th:last-child,
        #satuanTable td:last-child {
            text-align: center;
            white-space: nowrap;
        }

        #satuanTable td:last-child {
            padding: .25rem .5rem;
        }

        .dataTables_wrapper .dt-search {
            display: flex;
            align-items: center;
            gap: .75rem;
            flex-wrap: wrap;
        }

        #statusFilterWrap {
            margin-right: .25rem;
        }

        /* Column Search Icon */
        .column-search-wrapper {
            position: relative;
            display: inline-block;
            margin-left: 8px;
        }

        .column-search-icon {
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: background-color 0.2s;
            display: inline-flex;
            width: 20px;
            height: 20px;
        }

        .column-search-icon:hover { background-color: #e5e7eb; }
        .column-search-icon.active { background-color: #3b82f6; color: white; }

        .column-search-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 4px;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 8px;
            min-width: 200px;
            z-index: 1000;
            display: none;
        }

        .column-search-dropdown.show { display: block; }

        .column-search-input {
            width: 100%;
            padding: 6px 10px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 0.875rem;
        }

        .column-search-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }

        .column-search-clear {
            margin-top: 6px;
            width: 100%;
            padding: 4px 8px;
            background-color: #f3f4f6;
            border: none;
            border-radius: 4px;
            font-size: 0.75rem;
            cursor: pointer;
        }

        .column-search-clear:hover { background-color: #e5e7eb; }
        .search-icon { width: 14px; height: 14px; }
        #satuanTable thead th { position: relative; }
    </style>
@endpush


@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.6/js/dataTables.min.js"></script>

    <script>
        // =============================================
        // jQuery — Inisialisasi DataTables
        // =============================================
        $(function () {

            const table = $('#satuanTable').DataTable({
                autoWidth  : false,
                pageLength : 10,
                lengthMenu : [10, 25, 50, 100],
                order      : [[0, 'asc']],
                layout: {
                    topStart   : 'search',
                    topEnd     : 'pageLength',
                    bottomStart: 'info',
                    bottomEnd  : 'paging',
                },
                columnDefs: [
                    { targets: 'col-aksi', orderable: false, searchable: false, width: 120 },
                    { targets: 'no-sort',  orderable: false },
                ],
                language: {
                    lengthMenu: "Show _MENU_ entries",
                },
            });

            // ------------------------------------------
            // 1. Cari index kolom statusRaw & sembunyikan
            // ------------------------------------------
            const statusRawIdx = table.columns().indexes().toArray()
                .find(i => $(table.column(i).header()).attr('data-col') === 'statusRaw');

            if (statusRawIdx === undefined) {
                console.warn('Kolom statusRaw tidak ditemukan.');
                return;
            }

            table.column(statusRawIdx).visible(false);

            // Default: tampilkan hanya Active (fnonactive = 0)
            table.column(statusRawIdx).search('^0$', true, false).draw();

            // ------------------------------------------
            // 2. Clone template filter Status ke toolbar Search
            // ------------------------------------------
            const $container     = $(table.table().container());
            const $toolbarSearch = $container.find('.dt-search');

            const $filter = $('#statusFilterTemplate #statusFilterWrap').clone(true, true);
            const $select = $filter.find('select[data-role="status-filter"]');
            $select.attr('id', 'statusFilterDT');
            $toolbarSearch.append($filter); // sebelah kanan kotak search

            // Event: dropdown filter Status berubah
            $select.on('change', function () {
                const val = this.value;
                if (val === 'active') {
                    table.column(statusRawIdx).search('^0$', true, false).draw();
                } else if (val === 'nonactive') {
                    table.column(statusRawIdx).search('^1$', true, false).draw();
                } else {
                    table.column(statusRawIdx).search('', true, false).draw();
                }
            });

            // ------------------------------------------
            // 3. Paksa input Search Global jadi UPPERCASE
            // ------------------------------------------
            const $searchInput = $toolbarSearch.find('.dt-input');
            $searchInput.css({
                'width'          : '400px',
                'maxWidth'       : '100%',
                'text-transform' : 'uppercase',
            });

            $container.on('input', '.dt-search .dt-input', function () {
                const start = this.selectionStart;
                const end   = this.selectionEnd;
                this.value  = this.value.toUpperCase();
                this.setSelectionRange(start, end);
                table.search(this.value).draw();
            });

            // ------------------------------------------
            // 4. Column Search (per kolom) — dari kode asli
            // ------------------------------------------
            table.columns().every(function (index) {
                const column  = this;
                const header  = $(column.header());

                // Skip kolom yang tidak bisa diurutkan (status, aksi, statusRaw)
                if (!column.orderable() || header.hasClass('col-aksi') || header.hasClass('no-sort')) {
                    return;
                }

                setTimeout(() => {
                    const searchWrapper = $(`
                        <span class="column-search-wrapper">
                            <span class="column-search-icon" data-column="${index}">
                                <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </span>
                            <div class="column-search-dropdown" data-column="${index}">
                                <input type="text" class="column-search-input" placeholder="CARI..."
                                    style="text-transform: uppercase;" />
                                <button class="column-search-clear">Clear</button>
                            </div>
                        </span>
                    `);

                    const sortIcon = header.find('.dt-column-order');
                    if (sortIcon.length > 0) {
                        sortIcon.after(searchWrapper);
                    } else {
                        header.append(searchWrapper);
                    }

                    const icon     = searchWrapper.find('.column-search-icon');
                    const dropdown = searchWrapper.find('.column-search-dropdown');
                    const input    = searchWrapper.find('.column-search-input');
                    const clearBtn = searchWrapper.find('.column-search-clear');

                    icon.on('click', function (e) {
                        e.stopPropagation();
                        $('.column-search-dropdown').not(dropdown).removeClass('show');
                        $('.column-search-icon').not(icon).removeClass('active');
                        dropdown.toggleClass('show');
                        icon.toggleClass('active');
                        if (dropdown.hasClass('show')) input.focus();
                    });

                    // Force uppercase untuk search per kolom
                    input.on('input', function () {
                        const start = this.selectionStart;
                        const end   = this.selectionEnd;
                        this.value  = this.value.toUpperCase();
                        this.setSelectionRange(start, end);

                        const value = this.value;
                        if (column.search() !== value) {
                            column.search(value).draw();
                        }

                        icon.toggleClass('active', !!value);
                    });

                    clearBtn.on('click', function () {
                        input.val('');
                        column.search('').draw();
                        icon.removeClass('active');
                        dropdown.removeClass('show');
                    });

                    dropdown.on('click', function (e) { e.stopPropagation(); });

                }, 50);
            });

            // Tutup semua dropdown saat klik di luar
            $(document).on('click', function () {
                $('.column-search-dropdown').removeClass('show');
                $('.column-search-icon').each(function () {
                    const $icon = $(this);
                    if (!table.column($icon.data('column')).search()) {
                        $icon.removeClass('active');
                    }
                });
            });

        });
    </script>
@endpush