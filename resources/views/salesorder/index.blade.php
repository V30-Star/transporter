@extends('layouts.app')

@section('title', 'Sales Order')

@section('content')
    <div x-data class="bg-white rounded shadow p-4">

        @php
            $canCreate = in_array('createTr_poh', explode(',', session('user_restricted_permissions', '')));
            $canView = in_array('viewTr_poh', explode(',', session('user_restricted_permissions', '')));
            $canEdit = in_array('updateTr_poh', explode(',', session('user_restricted_permissions', '')));
            $canDelete = in_array('deleteTr_poh', explode(',', session('user_restricted_permissions', '')));
            $showActionsColumn = $canEdit || $canDelete;
        @endphp

        <div x-data="{
            showDeleteModal: false,
            deleteUrl: '',
        
            openDelete(url) {
                this.deleteUrl = url;
                this.showDeleteModal = true;
            },
        
            closeDelete() {
                this.showDeleteModal = false;
                this.deleteUrl = '';
            }
        }" @open-delete.window="openDelete($event.detail)"></div>

        <div class="flex justify-end items-center mb-4">
            <div></div>
            {{-- @if ($canCreate) --}}
            <a href="{{ route('salesorder.create') }}"
                class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                <x-heroicon-o-plus class="w-4 h-4 mr-1" /> Tambah Baru
            </a>
            {{-- @endif --}}
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

        <table id="salesorderTable" class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-1">Cab.</th>
                    <th class="border px-2 py-1">No.SO</th> {{-- Sesuai data simpel (bukan nama) --}}
                    <th class="border px-2 py-1">Tanggal</th>
                    <th class="border px-2 py-1">No.Ref</th>
                    <th class="border px-2 py-1">Nama Customer</th>
                    <th class="border px-2 py-1">Nilai SO</th>
                    <th class="border px-2 py-1">User Create</th>
                    <th class="border px-2 py-1">Status</th>
                    <th class="border px-2 py-1 col-aksi">Aksi</th>
                </tr>
            </thead>
            <tbody>
                {{-- KOSONGKAN BAGIAN INI --}}
                {{-- DataTables akan mengisinya secara otomatis --}}
            </tbody>
        </table>

        {{-- Modal Delete --}}
        <div x-show="$store.trsomtStore.showDeleteModal" x-cloak
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-transition>
            <div @click.away="!$store.trsomtStore.isDeleting && $store.trsomtStore.closeDelete()"
                class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Konfirmasi Hapus</h3>
                <p class="mb-6">Apakah Anda yakin ingin menghapus data ini?</p>
                <div class="flex justify-end space-x-2">
                    <button @click="$store.trsomtStore.closeDelete()"
                        class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400" :disabled="$store.trsomtStore.isDeleting">
                        Batal
                    </button>
                    <button @click="$store.trsomtStore.confirmDelete()"
                        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="$store.trsomtStore.isDeleting">
                        <span x-show="!$store.trsomtStore.isDeleting">Hapus</span>
                        <span x-show="$store.trsomtStore.isDeleting">Menghapus...</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Toast Notification --}}
        <div x-show="$store.trsomtStore.showNotification" x-cloak x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed top-4 right-4 z-50 max-w-sm">
            <div :class="$store.trsomtStore.notificationType === 'success' ? 'bg-green-500' : 'bg-red-500'"
                class="text-white px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3">
                <span x-text="$store.trsomtStore.notificationMessage"></span>
                <button @click="$store.trsomtStore.showNotification = false" class="ml-4 text-white hover:text-gray-200">
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
        #salesorderTable {
            width: 100% !important;
        }

        #salesorderTable th,
        #salesorderTable td {
            text-align: left !important;
            vertical-align: middle;
        }

        /* Kolom Aksi: jangan mepet, tapi tetap ringkas */
        #salesorderTable th:last-child,
        #salesorderTable td:last-child {
            white-space: nowrap;
            text-align: center;
        }

        #salesorderTable td:last-child {
            padding: .25rem .5rem;
        }

        .btn-aksi {
            padding: .25rem .5rem;
            font-size: .825rem;
        }

        #salesorderTable th,
        #salesorderTable td {
            text-align: left !important;
            vertical-align: middle;
        }

        #salesorderTable th:last-child,
        #salesorderTable td:last-child {
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
            Alpine.store('trsomtStore', {
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
                                const table = $('#salesorderTable').DataTable();
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
            // const canView = {{ $canView ? 'true' : 'false' }};
            // const canEdit = {{ $canEdit ? 'true' : 'false' }};
            // const canDelete = {{ $canDelete ? 'true' : 'false' }};

            // 1. Definisi Kolom
            // 1. Definisi Kolom
            const columns = [{
                    data: 'fbranchcode',
                    name: 'fbranchcode'
                },
                {
                    data: 'fsono',
                    name: 'fsono'
                },
                {
                    data: 'fsodate',
                    name: 'fsodate'
                },
                {
                    data: 'frefno', // ← UBAH DARI fsono JADI frefno
                    name: 'frefno'
                },
                {
                    data: 'fcustno',
                    name: 'fcustno'
                },
                {
                    data: 'famountso',
                    name: 'famountso',
                    render: function(data) {
                        // Format currency jika perlu
                        return new Intl.NumberFormat('id-ID').format(data);
                    }
                },
                {
                    data: 'fusercreate',
                    name: 'fusercreate'
                },
                {
                    data: 'fclose',
                    name: 'fclose',
                    visible: false,
                    searchable: true,
                    render: function(data) {
                        // Render status sebagai badge
                        if (data == '0') {
                            return '<span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">Active</span>';
                        } else {
                            return '<span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs">Closed</span>';
                        }
                    }
                }
            ];

            // Tambahkan kolom actions jika ada permission
            // if (hasActions) {
            columns.push({
                data: 'ftrsomtid',
                name: 'actions',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    let html = '<div class="flex gap-2">';

                    // if (canView) {
                    html += `<a href="salesorder/${data}/view">
                        <button class="inline-flex items-center bg-slate-500 text-white px-4 py-2 rounded hover:bg-slate-600">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            View
                        </button>
                    </a>`;
                    // }

                    // Edit Button
                    // if (canEdit) {
                    html += `<a href="salesorder/${data}/edit" class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Edit
                        </a>`;
                    // }

                    // Delete Button
                    // if (canDelete) {
                    let deleteUrl = '{{ route('salesorder.index') }}/' + data + '/delete';
                    html += `<a href="${deleteUrl}">
                                <button class="inline-flex items-center bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Hapus
                                </button>
                            </a>`;
                    // }

                    html += '</div>';
                    return html;
                }
            });
            // }

            // 2. Definisi columnDefs
            const columnDefs = [];
            // if (hasActions) {
            columnDefs.push({
                targets: -1,
                orderable: false,
                searchable: false,
                width: '280px'
            });
            // }

            // 3. Inisialisasi DataTables
            $('#salesorderTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('salesorder.index') }}',
                    type: 'GET',
                    data: function(d) {
                        const urlParams = new URLSearchParams(window.location.search);
                        d.year = urlParams.get('year') || '';
                        d.month = urlParams.get('month') || '';
                        d.status = urlParams.get('status') || 'active';
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

                    // Clone filters
                    const $statusFilter = $('#statusFilterTemplate #statusFilterWrap').clone(true,
                        true);
                    const $statusSelect = $statusFilter.find('select[data-role="status-filter"]');
                    $statusSelect.attr('id', 'statusFilterDT');
                    $toolbarSearch.append($statusFilter);

                    const $yearFilter = $('#yearFilterTemplate #yearFilterWrap').clone(true, true);
                    const $yearSelect = $yearFilter.find('select[data-role="year-filter"]');
                    $yearSelect.attr('id', 'yearFilterDT');
                    $toolbarSearch.append($yearFilter);

                    const $monthFilter = $('#monthFilterTemplate #monthFilterWrap').clone(true, true);
                    const $monthSelect = $monthFilter.find('select[data-role="month-filter"]');
                    $monthSelect.attr('id', 'monthFilterDT');
                    $toolbarSearch.append($monthFilter);

                    // Cari kolom fclose
                    const statusColIdx = api.columns().indexes().toArray()
                        .find(i => api.column(i).dataSrc() === 'fclose');

                    if (statusColIdx === undefined) {
                        console.warn('Kolom fclose tidak ditemukan.');
                        return;
                    }

                    // Baca status dari URL, default 'active'
                    const urlParams = new URLSearchParams(window.location.search);
                    const currentStatus = urlParams.get('status') || 'active';

                    // Set selected option sesuai URL
                    $statusSelect.val(currentStatus);

                    // Apply filter sesuai status dari URL
                    if (currentStatus === 'active') {
                        api.column(statusColIdx).search('^0$', true, false).draw();
                    } else if (currentStatus === 'nonactive') {
                        api.column(statusColIdx).search('^1$', true, false).draw();
                    } else {
                        api.column(statusColIdx).search('', true, false).draw();
                    }

                    const $searchInput = $toolbarSearch.find('.dt-input');
                    $searchInput.css({
                        width: '400px',
                        maxWidth: '100%'
                    });

                    // Event handler untuk Status Filter
                    $statusSelect.on('change', function() {
                        const v = this.value;
                        if (v === 'active') {
                            api.column(statusColIdx).search('^0$', true, false).draw();
                        } else if (v === 'nonactive') {
                            api.column(statusColIdx).search('^1$', true, false).draw();
                        } else {
                            api.column(statusColIdx).search('', true, false).draw();
                        }

                        updateUrlParams();
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
                        const status = $statusSelect.val();
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

                        if (status && status !== 'all') {
                            url.searchParams.set('status', status);
                        } else {
                            url.searchParams.delete('status');
                        }

                        window.history.pushState({}, '', url.toString());
                    }
                }
            });
        });
    </script>
@endpush
