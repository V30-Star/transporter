@extends('layouts.app')

@section('title', 'Master Customer')

@section('content')
    <div x-data="{
        showDeleteModal: false,
        deleteUrl: null,
        openDelete(url) {
            this.deleteUrl = url;
            this.showDeleteModal = true
        },
        closeDelete() {
            this.deleteUrl = null;
            this.showDeleteModal = false
        }
    }" x-on:open-delete.window="openDelete($event.detail)" class="bg-white rounded shadow p-4">

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
                    <th class="border px-2 py-2 no-sort">Wilayah</th>
                    <th class="border px-2 py-2 no-sort">Alamat</th>
                    <th class="border px-2 py-2 no-sort">Tempo</th> {{-- Diganti dari 'Kota' --}}
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

        {{-- Modal Delete --}}
        <div x-show="showDeleteModal" x-cloak
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div @click.away="closeDelete()" class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Konfirmasi Hapus</h3>
                <p class="mb-6">Apakah Anda yakin ingin menghapus data ini?</p>
                <div class="flex justify-end space-x-2">
                    <button @click="closeDelete()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Batal</button>
                    <form :action="deleteUrl" method="POST" class="inline">
                        @csrf @method('DELETE')
                        <button type="submit"
                            class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Hapus</button>
                    </form>
                </div>
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

        .dataTables_wrapper .dt-search {
            display: flex;
            align-items: center;
            gap: .75rem;
            flex-wrap: wrap;
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
    <script>
        document.addEventListener('alpine:init', () => {
            /* no-op */
        });

        $(function() {
            const hasActions = {{ $showActionsColumn ? 'true' : 'false' }};
            const canEdit = {{ $canEdit ? 'true' : 'false' }};
            const canDelete = {{ $canDelete ? 'true' : 'false' }};

            const columnDefs = [{
                targets: [2, 3, 4], // Satuan, Stok, Status
                orderable: false
            }];

            // --- PERUBAHAN 1: Definisi Kolom ---
            // 'data' HARUS cocok dengan key JSON yang dikirim oleh Controller
            const columns = [{
                    title: 'Kode Customer',
                    data: 'fcustomercode'
                },
                {
                    title: 'Nama Customer',
                    data: 'fcustomername'
                },
                {
                    title: 'Wilayah',
                    data: 'wilayah_name'
                },
                {
                    title: 'Alamat',
                    data: 'faddress'
                },
                {
                    title: 'Tempo',
                    data: 'ftempo'
                },
                {
                    title: 'Status',
                    data: 'status',
                    orderable: false,
                    searchable: false
                }
            ];

            // Tambahkan kolom 'Aksi' secara kondisional
            if (hasActions) {
                columns.push({
                    data: 'fprdid',
                    name: 'actions',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        let html = '<div class="space-x-2">';

                        if (canEdit) {
                            html += `<a href="/customer/${data}/edit">
                        <button class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Edit
                        </button>
                    </a>`;
                        }

                        if (canDelete) {
                            html += `<button onclick="openDeleteModal('/customer/${data}')" 
                        class="inline-flex items-center bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Hapus
                    </button>`;
                        }

                        html += '</div>';
                        return html;
                    }
                });
            }

            // --- PERUBAHAN 2: Inisialisasi DataTables ---
            const table = $('#customerTable').DataTable({
                // Aktifkan Server-Side Processing
                processing: true,
                serverSide: true,
                // --- PERUBAHAN 3: AJAX (Sumber Data) ---
                ajax: {
                    url: "{{ route('customer.index') }}", // Arahkan ke route controller
                    type: "GET",
                    // Kirim data tambahan (filter status) ke server
                    data: function(d) {
                        // 'd' adalah data default DataTables (search, order, paging)
                        // Kita tambahkan 'status' ke dalamnya
                        d.status = $('#statusFilterDT').val() || 'active';
                    }
                },

                // Gunakan 'columns' yang sudah kita definisikan di atas
                columns: columns,
                autoWidth: false,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [
                    [0, 'asc'] // Default order
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
                // --- PERUBAHAN 4: initComplete (Filter) ---
                initComplete: function() {
                    const api = this.api();
                    const $toolbarSearch = $(api.table().container()).find('.dt-search');
                    const $filter = $('#statusFilterTemplate #statusFilterWrap').clone(true, true);
                    const $select = $filter.find('select[data-role="status-filter"]');

                    $select.attr('id', 'statusFilterDT');
                    $toolbarSearch.append($filter);

                    const $searchInput = $toolbarSearch.find('.dt-input');
                    $searchInput.css({
                        width: '400px',
                        maxWidth: '100%'
                    });

                    $select.on('change', function() {
                        table.ajax.reload();
                    });
                }
            });
        });

        function openDeleteModal(url) {
            window.dispatchEvent(new CustomEvent('open-delete', {
                detail: url
            }));
        }
    </script>
@endpush
