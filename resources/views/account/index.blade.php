@extends('layouts.app')

@section('title', 'Master Account')

@section('content')
    <div x-data="accountData()" class="bg-white rounded shadow p-4">

        @php
            $canCreate = in_array('createAccount', explode(',', session('user_restricted_permissions', '')));
            $canEdit = in_array('updateAccount', explode(',', session('user_restricted_permissions', '')));
            $canDelete = in_array('deleteAccount', explode(',', session('user_restricted_permissions', '')));
            $showActionsColumn = $canEdit || $canDelete;
        @endphp

        <div class="flex items-center justify-between mb-4">
            <div></div>

            @if ($canCreate)
                <a href="{{ route('account.create') }}"
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

        {{-- Table --}}
        <table id="accountTable" class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-2">Account #</th>
                    <th class="border px-2 py-2">Nama Account</th>
                    <th class="border px-2 py-2 no-sort">Type</th>
                    <th class="border px-2 py-2 no-sort">Saldo Normal</th>
                    <th class="border px-2 py-2 no-sort">Status</th>
                    <th class="border px-2 py-2" data-col="statusRaw">StatusRaw</th>
                    @if ($showActionsColumn)
                        <th class="border px-2 py-2 col-aksi">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody id="tableBody">
                @forelse($accounts as $account)
                    <tr class="hover:bg-gray-50">
                        <td>{{ $account->faccount }}</td>
                        <td>{{ $account->faccname }}</td>
                        <td>{{ $account->fend == 1 ? 'Detil' : 'Header' }}</td>
                        <td>
                            {{ $account->fnormal == 'D' ? 'Debit' : ($account->fnormal == 'K' ? 'Kredit' : '-') }}
                        </td>
                        <td>
                            @php $isActive = (string)$account->fnonactive === '0'; @endphp
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs {{ $isActive ? 'bg-green-100 text-green-700' : 'bg-red-200 text-red-700' }}">
                                {{ $isActive ? 'Active' : 'Non Active' }}
                            </span>
                        </td>
                        <td>{{ (string) $account->fnonactive }}</td>
                        @if ($showActionsColumn)
                            <td class="border px-2 py-1 space-x-2">
                                @if ($canEdit)
                                    <a href="{{ route('account.edit', $account->faccid) }}">
                                        <button
                                            class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                                            <x-heroicon-o-pencil-square class="w-4 h-4 mr-1" /> Edit
                                        </button>
                                    </a>
                                @endif

                                @if ($canDelete)
                                    <a href="{{ route('account.delete', $account->faccid) }}">
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
        #accountTable {
            width: 100% !important;
        }

        #accountTable th,
        #accountTable td {
            text-align: left !important;
            vertical-align: middle;
        }

        /* Kolom Aksi: jangan mepet, tapi tetap ringkas */
        #accountTable th:last-child,
        #accountTable td:last-child {
            white-space: nowrap;
            text-align: center;
        }

        #accountTable td:last-child {
            padding: .25rem .5rem;
        }

        .btn-aksi {
            padding: .25rem .5rem;
            font-size: .825rem;
        }

        #accountTable th,
        #accountTable td {
            text-align: left !important;
            vertical-align: middle;
        }

        #accountTable th:last-child,
        #accountTable td:last-child {
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
            Alpine.data('accountData', () => ({
                showDeleteModal: false,
                deleteUrl: '',
                isDeleting: false,
                showNotification: false,
                notificationMessage: '',
                notificationType: 'success',
                currentRow: null, // Tambahkan ini untuk menyimpan row

                openDelete(url, event) {
                    this.deleteUrl = url;
                    this.showDeleteModal = true;
                    this.isDeleting = false;
                    // Simpan referensi row saat membuka modal
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
                    const rowToDelete = this.currentRow; // Simpan ke variable lokal

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
                                // Hapus row dari DataTable
                                const table = $('#accountTable').DataTable();
                                if (rowToDelete) {
                                    table.row($(rowToDelete)).remove().draw(false);
                                }

                                // Tampilkan notifikasi sukses
                                this.showNotificationMsg('success', result.data.message ||
                                    'Data berhasil dihapus');
                            } else {
                                // Tampilkan error dari server
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

                    // Auto hide setelah 3 detik
                    setTimeout(() => {
                        this.showNotification = false;
                    }, 3000);
                }
            }));
        });

        $(function() {
            // Inisialisasi DataTables
            const hasActions = {{ $showActionsColumn ? 'true' : 'false' }};
            const columns = hasActions ? [{
                    title: 'Account #'
                },
                {
                    title: 'Nama Account'
                },
                {
                    title: 'Type'
                },
                {
                    title: 'Saldo Normal'
                },
                {
                    title: 'Aksi',
                    orderable: false,
                    searchable: false
                }
            ] : [{
                    title: 'Account #'
                },
                {
                    title: 'Nama Account'
                },
                {
                    title: 'Type'
                },
                {
                    title: 'Saldo Normal'
                }
            ];

            $('#accountTable').DataTable({
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
                    const $toolbarSearch = $(api.table().container()).find('.dt-search');

                    // Clone dan append Status Filter
                    const $statusFilter = $('#statusFilterTemplate #statusFilterWrap').clone(true,
                        true);
                    const $statusSelect = $statusFilter.find('select[data-role="status-filter"]');
                    $statusSelect.attr('id', 'statusFilterDT');
                    $toolbarSearch.append($statusFilter);

                    // Clone dan append Year Filter
                    const $yearFilter = $('#yearFilterTemplate #yearFilterWrap').clone(true, true);
                    const $yearSelect = $yearFilter.find('select[data-role="year-filter"]');
                    $yearSelect.attr('id', 'yearFilterDT');
                    $toolbarSearch.append($yearFilter);

                    // Clone dan append Month Filter
                    const $monthFilter = $('#monthFilterTemplate #monthFilterWrap').clone(true, true);
                    const $monthSelect = $monthFilter.find('select[data-role="month-filter"]');
                    $monthSelect.attr('id', 'monthFilterDT');
                    $toolbarSearch.append($monthFilter);

                    // Cari index kolom StatusRaw
                    const statusRawIdx = api.columns().indexes().toArray()
                        .find(i => $(api.column(i).header()).attr('data-col') === 'statusRaw');

                    if (statusRawIdx === undefined) {
                        console.warn('Kolom StatusRaw tidak ditemukan.');
                        return;
                    }

                    api.column(statusRawIdx).visible(false);

                    // Perlebar search input
                    const $searchInput = $toolbarSearch.find('.dt-input');
                    $searchInput.css({
                        width: '400px',
                        maxWidth: '100%'
                    });

                    // Set filter status default
                    api.column(statusRawIdx).search('^0$', true, false).draw();

                    // Event handler untuk Status Filter
                    $statusSelect.on('change', function() {
                        const v = this.value;
                        if (v === 'active') {
                            api.column(statusRawIdx).search('^0$', true, false).draw();
                        } else if (v === 'nonactive') {
                            api.column(statusRawIdx).search('^1$', true, false).draw();
                        } else {
                            api.column(statusRawIdx).search('', true, false).draw();
                        }
                    });

                    // Event handler untuk Year dan Month Filter
                    // Menggunakan server-side filtering dengan reload halaman
                    $yearSelect.on('change', function() {
                        applyDateFilters();
                    });

                    $monthSelect.on('change', function() {
                        applyDateFilters();
                    });
                }
            });
        });
    </script>
@endpush
