@extends('layouts.app')

@section('title', 'Master Subaccount')

@section('content')
    <div x-data="subaccountData()" class="bg-white rounded shadow p-4">

        @php
            $canCreate = in_array('createSubAccount', explode(',', session('user_restricted_permissions', '')));
            $canEdit = in_array('updateSubAccount', explode(',', session('user_restricted_permissions', '')));
            $canDelete = in_array('deleteSubAccount', explode(',', session('user_restricted_permissions', '')));
            $showActionsColumn = $canEdit || $canDelete;
        @endphp

        <div class="flex justify-end items-center mb-4">
            <div></div>

            @if ($canCreate)
                <a href="{{ route('subaccount.create') }}"
                    class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    <x-heroicon-o-plus class="w-4 h-4 mr-1" /> Tambah Baru
                </a>
            @endif
        </div>

        {{-- Filter Status (Hidden Template) --}}
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
        <table id="subaccountTable" class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-2">Kode Subaccount</th>
                    <th class="border px-2 py-2">Nama Subaccount</th>
                    <th class="border px-2 py-2">Status</th>
                    <th class="border px-2 py-2" style="display:none;">StatusRaw</th>
                    @if ($showActionsColumn)
                        <th class="border px-2 py-2">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($subaccounts as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="border px-2 py-2">{{ $item->fsubaccountcode }}</td>
                        <td class="border px-2 py-2">{{ $item->fsubaccountname }}</td>
                        <td class="border px-2 py-2">
                            @php $isActive = (string)$item->fnonactive === '0'; @endphp
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs {{ $isActive ? 'bg-green-100 text-green-700' : 'bg-red-200 text-red-700' }}">
                                {{ $isActive ? 'Active' : 'Non Active' }}
                            </span>
                        </td>
                        <td style="display:none;">{{ $item->fnonactive }}</td>
                        @if ($showActionsColumn)
                            <td class="border px-2 py-1 space-x-2">
                                @if ($canEdit)
                                    <a href="{{ route('subaccount.edit', $item->fsubaccountid) }}">
                                        <button
                                            class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                                            <x-heroicon-o-pencil-square class="w-4 h-4 mr-1" /> Edit
                                        </button>
                                    </a>
                                @endif
                                @if ($canDelete)
                                    <button
                                        @click="openDelete('{{ route('subaccount.destroy', $item->fsubaccountid) }}', $event)"
                                        class="inline-flex items-center bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                                        <x-heroicon-o-trash class="w-4 h-4 mr-1" /> Hapus
                                    </button>
                                @endif
                            </td>
                        @endif
                    </tr>
                @endforeach
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
        #subaccountTable {
            width: 100% !important;
        }

        #subaccountTable th,
        #subaccountTable td {
            text-align: left !important;
            vertical-align: middle;
        }

        /* Kolom Aksi: jangan mepet, tapi tetap ringkas */
        #subaccountTable th:last-child,
        #subaccountTable td:last-child {
            white-space: nowrap;
            text-align: center;
        }

        #subaccountTable td:last-child {
            padding: .25rem .5rem;
        }

        .btn-aksi {
            padding: .25rem .5rem;
            font-size: .825rem;
        }

        #subaccountTable th,
        #subaccountTable td {
            text-align: left !important;
            vertical-align: middle;
        }

        #subaccountTable th:last-child,
        #subaccountTable td:last-child {
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
    {{-- Load jQuery dan DataTables --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.6/js/dataTables.min.js"></script>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('subaccountData', () => ({
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
                                const table = $('#subaccountTable').DataTable();
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

        $(document).ready(function() {
            // ========================================
            // STEP 1: Inisialisasi DataTables
            // ========================================
            const table = $('#subaccountTable').DataTable({
                pageLength: 10, // Tampilkan 10 data per halaman
                lengthMenu: [10, 25, 50, 100], // Pilihan jumlah data per halaman
                order: [
                    [0, 'asc']
                ], // Urutkan berdasarkan kolom pertama (Kode)
                layout: {
                    topStart: 'search', // Search pindah ke kiri
                    topEnd: 'pageLength', // Length menu pindah ke kanan
                    bottomStart: 'info',
                    bottomEnd: 'paging'
                },
                // Pengaturan kolom
                columnDefs: [{
                        targets: 2, // Kolom Status (index 2)
                        orderable: false // Tidak bisa diurutkan
                    },
                    {
                        targets: 3, // Kolom StatusRaw (index 3)
                        visible: false // Disembunyikan
                    },
                    @if ($showActionsColumn)
                        {
                            targets: 4, // Kolom Aksi (index 4)
                            orderable: false, // Tidak bisa diurutkan
                            searchable: false // Tidak bisa dicari
                        }
                    @endif
                ],

                // Terjemahan Bahasa Indonesia
                language: {
                    lengthMenu: "Show _MENU_ entries",
                    search: "Search:",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                    infoEmpty: "Tidak ada data",
                    infoFiltered: "(difilter dari _MAX_ total data)",
                    paginate: {
                        first: "Pertama",
                        last: "Terakhir",
                        next: "Selanjutnya",
                        previous: "Sebelumnya"
                    },
                    emptyTable: "Tidak ada data yang tersedia"
                }
            });

            // ========================================
            // STEP 2: Tambahkan Filter Status
            // ========================================

            // Ambil template filter dan clone
            const filterHtml = $('#statusFilterTemplate #statusFilterWrap').clone();

            // Tambahkan filter ke sebelah kotak pencarian
            $('.dt-search').append(filterHtml);

            // Perbesar kotak pencarian
            $('.dt-search .dt-input').css('width', '400px');

            // ========================================
            // STEP 3: Filter Default ke "Active"
            // ========================================

            // Filter otomatis menampilkan hanya data Active saat pertama load
            table.column(3).search('^0$', true, false).draw();

            // ========================================
            // STEP 4: Event Handler untuk Filter Status
            // ========================================

            $('select[data-role="status-filter"]').on('change', function() {
                const selectedValue = $(this).val();

                // Kolom index 3 adalah StatusRaw (0 = Active, 1 = Non Active)
                if (selectedValue === 'active') {
                    // Filter hanya yang bernilai '0' (Active)
                    table.column(3).search('^0$', true, false).draw();
                } else if (selectedValue === 'nonactive') {
                    // Filter hanya yang bernilai '1' (Non Active)
                    table.column(3).search('^1$', true, false).draw();
                } else {
                    // Tampilkan semua (kosongkan filter)
                    table.column(3).search('').draw();
                }
            });
        });
    </script>
@endpush
