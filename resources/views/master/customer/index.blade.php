@extends('layouts.app')

@section('title', 'Master Customer')

@section('content')
    <div x-data class="bg-white rounded shadow p-4">

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
        <div x-show="$store.customerStore.showDeleteModal" x-cloak
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-transition>
            <div @click.away="!$store.customerStore.isDeleting && $store.customerStore.closeDelete()"
                class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Konfirmasi Hapus</h3>
                <p class="mb-6">Apakah Anda yakin ingin menghapus data ini?</p>
                <div class="flex justify-end space-x-2">
                    <button @click="$store.customerStore.closeDelete()"
                        class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400" :disabled="$store.customerStore.isDeleting">
                        Batal
                    </button>
                    <button @click="$store.customerStore.confirmDelete()"
                        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="$store.customerStore.isDeleting">
                        <span x-show="!$store.customerStore.isDeleting">Hapus</span>
                        <span x-show="$store.customerStore.isDeleting">Menghapus...</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Toast Notification --}}
        <div x-show="$store.customerStore.showNotification" x-cloak x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed top-4 right-4 z-50 max-w-sm">
            <div :class="$store.customerStore.notificationType === 'success' ? 'bg-green-500' : 'bg-red-500'"
                class="text-white px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3">
                <span x-text="$store.customerStore.notificationMessage"></span>
                <button @click="$store.customerStore.showNotification = false" class="ml-4 text-white hover:text-gray-200">
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

    {{-- Script inisialisasi DataTables --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('customerStore', {
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
                                const table = $('#customerTable').DataTable();
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
                            this.showNotificationMsg('error', 'Terjadi kesalahan. Silakan coba lagi.');
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
            });
        });

        $(function() {
            const hasActions = {{ $showActionsColumn ? 'true' : 'false' }};
            const canEdit = {{ $canEdit ? 'true' : 'false' }};
            const canDelete = {{ $canDelete ? 'true' : 'false' }};

            // Targetkan kolom yg tidak bisa di-sort (index 2, 3, 4, 5)
            const columnDefs = [{
                targets: [2, 3, 4, 5],
                orderable: false
            }];

            // 'data' HARUS cocok dengan key JSON dari Controller BARU
            const columns = [{
                    data: 'fcustomercode'
                    // Hapus 'title' karena sudah ada di <thead> HTML
                },
                {
                    data: 'fcustomername'
                },
                {
                    data: 'wilayah_name'
                },
                {
                    data: 'faddress'
                },
                {
                    data: 'ftempo'
                },
                {
                    data: 'status',
                    searchable: false
                }
            ];

            // Tambahkan kolom 'Aksi' secara kondisional
            if (hasActions) {
                columns.push({
                    // --- PERUBAHAN DI SINI ---
                    data: 'fcustomerid', // HARUS 'fcustomerid', BUKAN 'fprdid'
                    name: 'actions',
                    orderable: false,
                    searchable: false,
                    className: 'col-aksi', // Untuk styling
                    render: function(data, type, row) {
                        // 'data' sekarang berisi fcustomerid
                        let html = '<div class="space-x-2">';

                        if (canEdit) {
                            // Ganti route('customer.edit') jika perlu
                            let editUrl = '{{ route('customer.index') }}/' + data + '/edit';
                            html += `<a href="${editUrl}" class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg> Edit
                            </a>`;
                        }

                        if (canDelete) {
                            html += `<button onclick="event.stopPropagation(); Alpine.store('customerStore').openDelete('customer/${data}', event)" 
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

                // Tambahkan definisi untuk kolom Aksi
                columnDefs.push({
                    targets: -1, // Kolom terakhir
                    orderable: false,
                    searchable: false
                });
            }

            // Inisialisasi DataTables
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
                columnDefs: columnDefs, // Terapkan columnDefs
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
                    // --- Logika Filter Status (Sudah Benar) ---
                    const api = this.api();
                    const $toolbarSearch = $(api.table().container()).find('.dt-search');
                    const $filter = $('#statusFilterTemplate #statusFilterWrap').clone(true, true);
                    const $select = $filter.find('select[data-role="status-filter"]');

                    $select.attr('id', 'statusFilterDT');
                    $toolbarSearch.append($filter);

                    // Atur nilai default dropdown
                    $select.val('{{ $status ?? 'active' }}');

                    const $searchInput = $toolbarSearch.find('.dt-input');
                    $searchInput.css({
                        width: '400px',
                        maxWidth: '100%'
                    });

                    $select.on('change', function() {
                        // table.ajax.reload() lebih cepat
                        table.ajax.reload();
                    });

                    // Panggil draw() untuk memuat data awal
                    table.draw();
                }
            });
        });
    </script>
@endpush
