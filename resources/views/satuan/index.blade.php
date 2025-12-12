@extends('layouts.app')

@section('title', 'Master Satuan')

@section('content')
    <div x-data="satuanData()" class="bg-white rounded shadow p-4">

        @php
            $canCreate = in_array('createSatuan', explode(',', session('user_restricted_permissions', '')));
            $canEdit = in_array('updateSatuan', explode(',', session('user_restricted_permissions', '')));
            $canDelete = in_array('deleteSatuan', explode(',', session('user_restricted_permissions', '')));
            $showActionsColumn = $canEdit || $canDelete;
        @endphp

        <div class="flex justify-end items-center mb-4">
            <div></div>

            @if ($canCreate)
                <a href="{{ route('satuan.create') }}"
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

        {{-- TABEL DataTables --}}
        <table id="satuanTable" class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th>Kode Satuan</th>
                    <th>Nama Satuan</th>
                    <th class="border px-2 py-2 no-sort">Status</th>
                    <th class="border px-2 py-2" data-col="statusRaw">StatusRaw</th>
                    @if ($showActionsColumn)
                        <th class="border px-2 py-2 col-aksi">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($satuans as $item)
                    <tr>
                        <td>{{ $item->fsatuancode }}</td>
                        <td>{{ $item->fsatuanname }}</td>
                        <td>
                            @php $isActive = (string)$item->fnonactive === '0'; @endphp
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs {{ $isActive ? 'bg-green-100 text-green-700' : 'bg-red-200 text-red-700' }}">
                                {{ $isActive ? 'Active' : 'Non Active' }}
                            </span>
                        </td>
                        <td>{{ (string) $item->fnonactive }}</td>
                        @if ($showActionsColumn)
                            <td class="border px-2 py-1 space-x-2">
                                @if ($canEdit)
                                    <a href="{{ route('satuan.edit', $item->fsatuanid) }}"
                                        class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                                        <x-heroicon-o-pencil-square class="w-4 h-4 mr-1" /> Edit
                                    </a>
                                @endif
                                @if ($canDelete)
                                    <a href="{{ route('satuan.delete', $item->fsatuanid) }}">
                                        <button
                                            class="inline-flex items-center bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                                            <x-heroicon-o-trash class="w-4 h-4 mr-1" />
                                            Hapus
                                        </button>
                                    </a>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $showActionsColumn ? 5 : 3 }}" class="text-center py-4">Tidak ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Modal Delete --}}
        <div x-show="showDeleteModal" x-cloak
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-transition>
            <div @click.away="!isDeleting && closeDelete()" class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Konfirmasi Hapus</h3>
                <p class="mb-6">Apakah Anda yakin ingin menghapus data ini?</p>
                <div class="flex justify-end space-x-2">
                    <button @click="closeDelete()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400"
                        :disabled="isDeleting">
                        Batal
                    </button>
                    <button @click="confirmDelete()"
                        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="isDeleting">
                        <span x-show="!isDeleting">Hapus</span>
                        <span x-show="isDeleting">Menghapus...</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Toast Notification --}}
        <div x-show="showNotification" x-cloak x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed top-4 right-4 z-50 max-w-sm">
            <div :class="notificationType === 'success' ? 'bg-green-500' : 'bg-red-500'"
                class="text-white px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3">
                <span x-text="notificationMessage"></span>
                <button @click="showNotification = false" class="ml-4 text-white hover:text-gray-200">
                    ×
                </button>
            </div>
        </div>

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

        /* Kolom Aksi: jangan mepet, tapi tetap ringkas */
        #satuanTable th:last-child,
        #satuanTable td:last-child {
            white-space: nowrap;
            text-align: center;
        }

        #satuanTable td:last-child {
            padding: .25rem .5rem;
        }

        .btn-aksi {
            padding: .25rem .5rem;
            font-size: .825rem;
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

        /* Column Search Dropdown Styles */
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
            align-items: center;
            justify-content: center;
        }

        .column-search-icon:hover {
            background-color: #e5e7eb;
        }

        .column-search-icon.active {
            background-color: #3b82f6;
            color: white;
        }

        .column-search-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            margin-top: 4px;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 8px;
            min-width: 200px;
            z-index: 1000;
            display: none;
        }

        .column-search-dropdown.show {
            display: block;
        }

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
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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
            transition: background-color 0.2s;
        }

        .column-search-clear:hover {
            background-color: #e5e7eb;
        }

        /* Icon SVG */
        .search-icon {
            width: 16px;
            height: 16px;
        }

        /* Adjust header position */
        #satuanTable thead th {
            position: relative;
        }

        .dt-column-order {
            display: inline-flex;
            align-items: center;
        }

        /* Tambahkan di bawah style yang sudah ada */

        /* Header layout untuk sort + search icon */
        #satuanTable thead th {
            position: relative;
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }

        .header-icons {
            display: flex;
            align-items: center;
            gap: 4px;
            margin-left: 8px;
        }

        /* Column Search Icon */
        .column-search-icon {
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: background-color 0.2s;
            display: inline-flex;
            width: 20px;
            height: 20px;
        }

        .column-search-icon:hover {
            background-color: #e5e7eb;
        }

        .column-search-icon.active {
            background-color: #3b82f6;
            color: white;
        }

        .column-search-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 4px;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 8px;
            min-width: 200px;
            z-index: 1000;
            display: none;
        }

        .column-search-dropdown.show {
            display: block;
        }

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
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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

        .column-search-clear:hover {
            background-color: #e5e7eb;
        }

        .search-icon {
            width: 14px;
            height: 14px;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.6/js/dataTables.min.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('satuanData', () => ({
                showDeleteModal: false,
                deleteUrl: '',
                isDeleting: false,
                showNotification: false,
                notificationMessage: '',
                notificationType: 'success',
                currentRow: null,

                openDelete(url, event) {
                    this.deleteUrl = url;
                    this.showDeleteModal = true;
                    this.isDeleting = false;
                    this.currentRow = event.target.closest('tr');
                },

                closeDelete() {
                    if (!this.isDeleting) {
                        this.showDeleteModal = false;
                        this.deleteUrl = '';
                        this.currentRow = null;
                    }
                },

                confirmDelete() {
                    this.isDeleting = true;
                    const rowToDelete = this.currentRow;

                    fetch(this.deleteUrl, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .content,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            }
                        })
                        .then(response => {
                            return response.json().then(data => ({
                                ok: response.ok,
                                status: response.status,
                                data: data
                            }));
                        })
                        .then(result => {
                            this.showDeleteModal = false;
                            this.isDeleting = false;

                            if (result.ok) {
                                const table = $('#satuanTable').DataTable();
                                if (rowToDelete) {
                                    table.row($(rowToDelete)).remove().draw(false);
                                }

                                this.showNotificationMsg('success', result.data.message ||
                                    'Data berhasil dihapus');
                            } else {
                                this.showNotificationMsg('error', result.data.message ||
                                    'Gagal menghapus data');
                            }

                            this.currentRow = null;
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            this.showDeleteModal = false;
                            this.isDeleting = false;
                            this.showNotificationMsg('error',
                                'Terjadi kesalahan. Silakan coba lagi.');
                            this.currentRow = null;
                        });
                },

                showNotificationMsg(type, message) {
                    this.notificationType = type;
                    this.notificationMessage = message;
                    this.showNotification = true;

                    setTimeout(() => {
                        this.showNotification = false;
                    }, 3000);
                }
            }));
        });

        $(function() {
            const hasActions = {{ $showActionsColumn ? 'true' : 'false' }};

            const table = $('#satuanTable').DataTable({
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
                columnDefs: [{
                        targets: 'col-aksi',
                        orderable: false,
                        searchable: false,
                        width: 120
                    },
                    {
                        targets: 'no-sort',
                        orderable: false
                    }
                ],
                language: {
                    lengthMenu: "Show _MENU_ entries"
                },
                initComplete: function() {
                    const api = this.api();

                    // Add search icon to searchable columns
                    api.columns().every(function(index) {
                        const column = this;
                        const header = $(column.header());

                        // Skip non-searchable columns
                        if (!column.orderable() || header.hasClass('col-aksi') || header
                            .hasClass('no-sort')) {
                            return;
                        }

                        // Tunggu DataTables selesai render sorting icon
                        setTimeout(() => {
                            // Create search icon wrapper
                            const searchWrapper = $(`
                <span class="column-search-wrapper">
                    <span class="column-search-icon" data-column="${index}">
                        <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </span>
                    <div class="column-search-dropdown" data-column="${index}">
                        <input type="text" class="column-search-input" placeholder="Cari..." />
                        <button class="column-search-clear">Clear</button>
                    </div>
                </span>
            `);

                            // Cari dan letakkan di sebelah sort icon
                            const sortIcon = header.find('.dt-column-order');
                            if (sortIcon.length > 0) {
                                sortIcon.after(searchWrapper);
                            } else {
                                header.append(searchWrapper);
                            }

                            // Get elements
                            const icon = searchWrapper.find('.column-search-icon');
                            const dropdown = searchWrapper.find(
                                '.column-search-dropdown');
                            const input = searchWrapper.find('.column-search-input');
                            const clearBtn = searchWrapper.find('.column-search-clear');

                            // Toggle dropdown
                            icon.on('click', function(e) {
                                e.stopPropagation();

                                // Close other dropdowns
                                $('.column-search-dropdown').not(dropdown)
                                    .removeClass('show');
                                $('.column-search-icon').not(icon).removeClass(
                                    'active');

                                // Toggle current dropdown
                                dropdown.toggleClass('show');
                                icon.toggleClass('active');

                                if (dropdown.hasClass('show')) {
                                    input.focus();
                                }
                            });

                            // Search on input
                            input.on('keyup change', function() {
                                const value = this.value;
                                if (column.search() !== value) {
                                    column.search(value).draw();
                                }

                                // Update icon state
                                if (value) {
                                    icon.addClass('active');
                                } else {
                                    icon.removeClass('active');
                                }
                            });

                            // Clear search
                            clearBtn.on('click', function() {
                                input.val('');
                                column.search('').draw();
                                icon.removeClass('active');
                                dropdown.removeClass('show');
                            });

                            // Prevent dropdown close when clicking inside
                            dropdown.on('click', function(e) {
                                e.stopPropagation();
                            });
                        }, 50);
                    });

                    // Close dropdowns when clicking outside
                    $(document).on('click', function() {
                        $('.column-search-dropdown').removeClass('show');
                        $('.column-search-icon').each(function() {
                            const $icon = $(this);
                            const columnIndex = $icon.data('column');
                            const columnValue = api.column(columnIndex).search();

                            if (!columnValue) {
                                $icon.removeClass('active');
                            }
                        });
                    });

                    // Setup status filter
                    const $toolbarSearch = $(api.table().container()).find('.dt-search');
                    const $filter = $('#statusFilterTemplate #statusFilterWrap').clone(true, true);

                    const $select = $filter.find('select[data-role="status-filter"]');
                    $select.attr('id', 'statusFilterDT');

                    $toolbarSearch.append($filter);

                    const statusRawIdx = api.columns().indexes().toArray()
                        .find(i => $(api.column(i).header()).attr('data-col') === 'statusRaw');

                    if (statusRawIdx === undefined) {
                        console.warn('Kolom StatusRaw tidak ditemukan.');
                        return;
                    }

                    api.column(statusRawIdx).visible(false);

                    const $searchInput = $toolbarSearch.find('.dt-input');
                    $searchInput.css({
                        width: '400px',
                        maxWidth: '100%'
                    });

                    // Set default filter ke Active
                    api.column(statusRawIdx).search('^0$', true, false).draw();

                    $select.on('change', function() {
                        const v = this.value;
                        if (v === 'active') {
                            api.column(statusRawIdx).search('^0$', true, false).draw();
                        } else if (v === 'nonactive') {
                            api.column(statusRawIdx).search('^1$', true, false).draw();
                        } else {
                            api.column(statusRawIdx).search('', true, false).draw();
                        }
                    });
                }
            });
        });
    </script>
@endpush
