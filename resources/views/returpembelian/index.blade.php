@extends('layouts.app')

@section('title', 'Retur Pembelian')

@section('content')
    <div x-data class="bg-white rounded shadow p-4">

        {{-- @php
            $canCreate = in_array('createTr_prh', explode(',', session('user_restricted_permissions', '')));
            $canEdit = in_array('updateTr_prh', explode(',', session('user_restricted_permissions', '')));
            $canDelete = in_array('deleteTr_prh', explode(',', session('user_restricted_permissions', '')));
            $showActionsColumn = $canEdit || $canDelete;
        @endphp --}}

        <div class="flex justify-end items-center mb-4">
            <div></div>

            {{-- @if ($canCreate) --}}
            <a href="{{ route('returpembelian.create') }}"
                class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                <x-heroicon-o-plus class="w-4 h-4 mr-1" /> Tambah Baru
            </a>
            {{-- @endif --}}
        </div>
        {{-- 
        <div id="statusFilterTemplate" class="hidden">
            <div class="flex items-center gap-2" id="statusFilterWrap">
                <span class="text-sm text-gray-700">Status</span>
                <select data-role="status-filter" class="border rounded px-2 py-1">
                    <option value="all">All</option>
                    <option value="active" selected>Active</option>
                    <option value="nonactive">Non Active</option>
                </select>
            </div>
        </div> --}}

        <div id="yearFilterTemplate" class="hidden">
            <div class="flex items-center gap-2" id="yearFilterWrap">
                <span class="text-sm text-gray-700">Tahun</span>
                <select data-role="year-filter" class="border rounded px-2 py-1 w-24">
                    <option value="">Semua</option>
                    @foreach ($availableYears as $yr)
                        <option value="{{ $yr }}" {{ $year == $yr ? 'selected' : '' }}>{{ $yr }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Template untuk filter Bulan --}}
        <div id="monthFilterTemplate" class="hidden">
            <div class="flex items-center gap-2" id="monthFilterWrap">
                <span class="text-sm text-gray-700">Bulan</span>
                <select data-role="month-filter" class="border rounded px-2 py-1">
                    <option value="">Semua</option>
                    <option value="1" {{ $month == '1' ? 'selected' : '' }}>Januari</option>
                    <option value="2" {{ $month == '2' ? 'selected' : '' }}>Februari</option>
                    <option value="3" {{ $month == '3' ? 'selected' : '' }}>Maret</option>
                    <option value="4" {{ $month == '4' ? 'selected' : '' }}>April</option>
                    <option value="5" {{ $month == '5' ? 'selected' : '' }}>Mei</option>
                    <option value="6" {{ $month == '6' ? 'selected' : '' }}>Juni</option>
                    <option value="7" {{ $month == '7' ? 'selected' : '' }}>Juli</option>
                    <option value="8" {{ $month == '8' ? 'selected' : '' }}>Agustus</option>
                    <option value="9" {{ $month == '9' ? 'selected' : '' }}>September</option>
                    <option value="10" {{ $month == '10' ? 'selected' : '' }}>Oktober</option>
                    <option value="11" {{ $month == '11' ? 'selected' : '' }}>November</option>
                    <option value="12" {{ $month == '12' ? 'selected' : '' }}>Desember</option>
                </select>
            </div>
        </div>

        {{-- Table --}}
        {{-- GANTI TABEL ANDA DENGAN INI --}}
        <table id="returpembelianTable" class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-1">No. Faktur</th>
                    <th class="border px-2 py-1">Tanggal</th>
                    <th class="border px-2 py-1">Tipe Beli</th>

                    {{-- @if ($showActionsColumn) --}}
                    <th class="border px-2 py-1 col-aksi">Aksi</th>
                    {{-- @endif --}}
                </tr>
            </thead>
            <tbody>
                {{-- KOSONGKAN BAGIAN INI --}}
            </tbody>
        </table>

        {{-- Modal Delete --}}
        <div x-show="$store.returpembelianStore.showDeleteModal" x-cloak
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-transition>
            <div @click.away="!$store.returpembelianStore.isDeleting && $store.returpembelianStore.closeDelete()"
                class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Konfirmasi Hapus</h3>
                <p class="mb-6">Apakah Anda yakin ingin menghapus data ini?</p>
                <div class="flex justify-end space-x-2">
                    <button @click="$store.returpembelianStore.closeDelete()"
                        class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400"
                        :disabled="$store.returpembelianStore.isDeleting">
                        Batal
                    </button>
                    <button @click="$store.returpembelianStore.confirmDelete()"
                        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-50"
                        :disabled="$store.returpembelianStore.isDeleting">
                        <span x-show="!$store.returpembelianStore.isDeleting">Hapus</span>
                        <span x-show="$store.returpembelianStore.isDeleting">Menghapus...</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Toast Notification --}}
        <div x-show="$store.returpembelianStore.showNotification" x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed top-4 right-4 z-50 max-w-sm">
            <div :class="$store.returpembelianStore.notificationType === 'success' ? 'bg-green-500' : 'bg-red-500'"
                class="text-white px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3">
                <span x-text="$store.returpembelianStore.notificationMessage"></span>
                <button @click="$store.returpembelianStore.showNotification = false"
                    class="ml-4 text-white hover:text-gray-200">×</button>
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
        #returpembelianTable {
            width: 100% !important;
        }

        #returpembelianTable th,
        #returpembelianTable td {
            text-align: left !important;
            vertical-align: middle;
        }

        /* Kolom Aksi: jangan mepet, tapi tetap ringkas */
        #returpembelianTable th:last-child,
        #returpembelianTable td:last-child {
            white-space: nowrap;
            text-align: center;
        }

        #returpembelianTable td:last-child {
            padding: .25rem .5rem;
        }

        .btn-aksi {
            padding: .25rem .5rem;
            font-size: .825rem;
        }

        #returpembelianTable th,
        #returpembelianTable td {
            text-align: left !important;
            vertical-align: middle;
        }

        #returpembelianTable th:last-child,
        #returpembelianTable td:last-child {
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
            Alpine.store('returpembelianStore', {
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
                                const table = $('#returpembelianTable').DataTable();
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
            // const hasActions = {{ $showActionsColumn ? 'true' : 'false' }};
            // const canEdit = {{ $canEdit ? 'true' : 'false' }};
            // const canDelete = {{ $canDelete ? 'true' : 'false' }};
            // const canPrint = {{ $canPrint ? 'true' : 'false' }};

            // 1. Definisi Kolom - HARUS SELALU ADA 4 KOLOM (sesuai dengan <th> di HTML)
            const columns = [{
                    data: 'fstockmtno',
                    name: 'fstockmtno'
                },
                {
                    data: 'fstockmtdate',
                    name: 'fstockmtdate'
                },
                {
                    data: 'ftypebuy',
                    name: 'ftypebuy'
                },
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false
                }
            ];

            // 2. Definisi columnDefs
            const columnDefs = [{
                targets: -1,
                orderable: false,
                searchable: false,
                width: '280px'
            }];

            // 3. Inisialisasi DataTables
            $('#returpembelianTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('returpembelian.index') }}',
                    type: 'GET',
                    data: function(d) {
                        const urlParams = new URLSearchParams(window.location.search);
                        d.year = urlParams.get('year') || '';
                        d.month = urlParams.get('month') || '';
                    }
                },
                columns: columns,
                columnDefs: columnDefs,
                order: [
                    [0, 'desc']
                ],
                layout: {
                    topStart: 'search',
                    topEnd: 'pageLength',
                    bottomStart: 'info',
                    bottomEnd: 'paging'
                },
                initComplete: function() {
                    const api = this.api();
                    const $toolbarSearch = $(api.table().container()).find('.dt-search');

                    // Clone year filter
                    const $yearFilter = $('#yearFilterTemplate #yearFilterWrap').clone(true, true);
                    const $yearSelect = $yearFilter.find('select[data-role="year-filter"]');
                    $yearSelect.attr('id', 'yearFilterDT');
                    $toolbarSearch.append($yearFilter);

                    // Clone month filter
                    const $monthFilter = $('#monthFilterTemplate #monthFilterWrap').clone(true, true);
                    const $monthSelect = $monthFilter.find('select[data-role="month-filter"]');
                    $monthSelect.attr('id', 'monthFilterDT');
                    $toolbarSearch.append($monthFilter);

                    const $searchInput = $toolbarSearch.find('.dt-input');
                    $searchInput.css({
                        width: '400px',
                        maxWidth: '100%'
                    });

                    // Event handlers untuk Year dan Month
                    $yearSelect.on('change', function() {
                        updateUrlParams();
                        api.ajax.reload();
                    });

                    $monthSelect.on('change', function() {
                        updateUrlParams();
                        api.ajax.reload();
                    });

                    // Fungsi untuk update URL params tanpa reload halaman
                    function updateUrlParams() {
                        const year = $yearSelect.val();
                        const month = $monthSelect.val();
                        const url = new URL(window.location.href);

                        if (year) {
                            url.searchParams.set('year', year);
                        } else {
                            url.searchParams.delete('year');
                        }

                        if (month) {
                            url.searchParams.set('month', month);
                        } else {
                            url.searchParams.delete('month');
                        }

                        window.history.pushState({}, '', url.toString());
                    }
                }
            });
        });
    </script>
@endpush
