@extends('layouts.app')

@section('title', 'Adjustment Stock')

@section('content')
    <div x-data="{
        showDeleteModal: false,
        deleteUrl: null,
        openDelete(url) {
            this.deleteUrl = url;
            this.showDeleteModal = true
        },
        closeDelete() {
            this.showDeleteModal = false;
            this.deleteUrl = null
        }
    }" class="bg-white rounded shadow p-4">

        @php
            $permissions = array_filter(array_map('trim', explode(',', session('user_restricted_permissions', ''))));
            $canCreate = in_array('createPenerimaanBarang', $permissions, true);
            $canEdit = in_array('updatePenerimaanBarang', $permissions, true);
            $canDelete = in_array('deletePenerimaanBarang', $permissions, true);
            $canView = $canCreate || $canEdit || $canDelete;
            $showActionsColumn = $canView || $canEdit || $canDelete;
        @endphp

        <div class="flex justify-end items-center mb-4">
            <div></div>

            @if ($canCreate)
                <a href="{{ route('adjstock.create') }}"
                    class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    <x-heroicon-o-plus class="w-4 h-4 mr-1" /> Tambah Baru
                </a>
            @endif
        </div>

        <div id="yearFilterTemplate" class="hidden">
            <div class="flex items-center gap-2" id="yearFilterWrap">
                <span class="text-sm text-gray-700">Tahun</span>
                <select data-role="year-filter" class="border rounded px-2 py-1 w-24">
                    <option value="">Semua</option>
                    @foreach ($availableYears as $yr)
                        <option value="{{ $yr }}" {{ (string) $year === (string) $yr ? 'selected' : '' }}>
                            {{ $yr }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div id="monthFilterTemplate" class="hidden">
            <div class="flex items-center gap-2" id="monthFilterWrap">
                <span class="text-sm text-gray-700">Bulan</span>
                <select data-role="month-filter" class="border rounded px-2 py-1 w-24">
                    <option value="">Semua</option>
                    <option value="1" {{ $month === '1' ? 'selected' : '' }}>Januari</option>
                    <option value="2" {{ $month === '2' ? 'selected' : '' }}>Februari</option>
                    <option value="3" {{ $month === '3' ? 'selected' : '' }}>Maret</option>
                    <option value="4" {{ $month === '4' ? 'selected' : '' }}>April</option>
                    <option value="5" {{ $month === '5' ? 'selected' : '' }}>Mei</option>
                    <option value="6" {{ $month === '6' ? 'selected' : '' }}>Juni</option>
                    <option value="7" {{ $month === '7' ? 'selected' : '' }}>Juli</option>
                    <option value="8" {{ $month === '8' ? 'selected' : '' }}>Agustus</option>
                    <option value="9" {{ $month === '9' ? 'selected' : '' }}>September</option>
                    <option value="10" {{ $month === '10' ? 'selected' : '' }}>Oktober</option>
                    <option value="11" {{ $month === '11' ? 'selected' : '' }}>November</option>
                    <option value="12" {{ $month === '12' ? 'selected' : '' }}>Desember</option>
                </select>
            </div>
        </div>

        <div id="warehouseColumnFilterTemplate" class="hidden">
            <input type="search" data-role="warehouse-column-filter"
                class="w-full border rounded px-3 py-2 text-sm" placeholder="Cari gudang...">
        </div>

        <table id="adjstockTable" class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-1">Cabang</th>
                    <th class="border px-2 py-1">No. Adj Stock</th>
                    <th class="border px-2 py-1">Tanggal</th>
                    <th class="border px-2 py-1">Tipe Adj</th>
                    <th class="border px-2 py-1">
                        <div class="column-filter-header-block" data-column-filter="gudang">
                            <div class="column-filter-header">
                                <span>Gudang</span>
                                <span class="column-filter-icons">
                                    <button type="button" class="column-filter-search-trigger"
                                        aria-label="Tampilkan pencarian Gudang">
                                        <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                    </button>
                                    <x-heroicon-o-arrows-up-down class="w-4 h-4" />
                                </span>
                            </div>
                            <div class="mt-2 hidden" id="gudangFilterHost"></div>
                        </div>
                    </th>
                    <th class="border px-2 py-1">Keterangan</th>

                    @if ($showActionsColumn)
                        <th class="border px-2 py-1 col-aksi">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                {{-- KOSONGKAN BAGIAN INI --}}
            </tbody>
        </table>

        {{-- Modal Delete --}}
        <div x-show="$store.adjstockStore.showDeleteModal" x-cloak
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-transition>
            <div @click.away="!$store.adjstockStore.isDeleting && $store.adjstockStore.closeDelete()"
                class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Konfirmasi Hapus</h3>
                <p class="mb-6">Apakah Anda yakin ingin menghapus data ini?</p>
                <div class="flex justify-end space-x-2">
                    <button @click="$store.adjstockStore.closeDelete()"
                        class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400" :disabled="$store.adjstockStore.isDeleting">
                        Batal
                    </button>
                    <button @click="$store.adjstockStore.confirmDelete()"
                        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="$store.adjstockStore.isDeleting">
                        <span x-show="!$store.adjstockStore.isDeleting">Hapus</span>
                        <span x-show="$store.adjstockStore.isDeleting">Menghapus...</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Toast Notification --}}
        <div x-show="$store.adjstockStore.showNotification" x-cloak x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed top-4 right-4 z-50 max-w-sm">
            <div :class="$store.adjstockStore.notificationType === 'success' ? 'bg-green-500' : 'bg-red-500'"
                class="text-white px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3">
                <span x-text="$store.adjstockStore.notificationMessage"></span>
                <button @click="$store.adjstockStore.showNotification = false" class="ml-4 text-white hover:text-gray-200">
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

        .dt-container .dt-search {
            width: 100%;
            justify-content: flex-start;
        }

        /* Stabilkan tabel */
        #tr_prhTable {
            width: 100% !important;
        }

        #tr_prhTable th,
        #tr_prhTable td {
            text-align: left !important;
            vertical-align: middle;
        }

        /* Kolom Aksi: jangan mepet, tapi tetap ringkas */
        #tr_prhTable th:last-child,
        #tr_prhTable td:last-child {
            white-space: nowrap;
            text-align: right;
        }

        #tr_prhTable td:last-child {
            padding: .25rem .5rem;
        }

        .btn-aksi {
            padding: .25rem .5rem;
            font-size: .825rem;
        }

        #tr_prhTable th,
        #tr_prhTable td {
            text-align: left !important;
            vertical-align: middle;
        }

        #tr_prhTable th:last-child,
        #tr_prhTable td:last-child {
            text-align: right;
            white-space: nowrap;
        }

        .dataTables_wrapper .dt-search {
            display: flex;
            align-items: center;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .dataTables_wrapper .dt-search .dt-input {
            width: 28rem;
            max-width: 100%;
        }

        .dataTables_wrapper .dt-search label,
        .dataTables_wrapper .dt-length label {
            margin-bottom: 0;
        }

        #yearFilterWrap,
        #monthFilterWrap {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: 0;
        }

        .column-filter-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
        }

        .column-filter-header-block {
            display: flex;
            flex-direction: column;
        }

        .column-filter-icons {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            color: #6b7280;
        }

        .column-filter-search-trigger {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            background: transparent;
            color: inherit;
            padding: 0;
            line-height: 1;
        }

        .column-filter-search-trigger:hover {
            color: #374151;
        }

        .column-filter-header-block input[type="search"] {
            width: 100%;
            min-width: 12rem;
        }
    </style>
@endpush

@push('scripts')
    {{-- jQuery + DataTables JS (CDN) --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.6/js/dataTables.min.js"></script>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('adjstockStore', {
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
                                const table = $('#adjstockTable').DataTable();
                                if (rowToDelete) {
                                    table.row($(rowToDelete)).remove().draw(false);
                                }
                                this.showNotificationMsg('success', result.data.message ||
                                    'Data berhasil dihapus.');
                            } else {
                                this.showNotificationMsg('error', result.data.message ||
                                    'Hapus data gagal.');
                            }

                            this.currentRow = null;
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            this.showDeleteModal = false;
                            this.isDeleting = false;
                            this.showNotificationMsg('error', 'Terjadi kesalahan. Coba lagi.');
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
            const canView = {{ $canView ? 'true' : 'false' }};
            const canEdit = {{ $canEdit ? 'true' : 'false' }};
            const canDelete = {{ $canDelete ? 'true' : 'false' }};

            // 1. Definisi Kolom - HARUS SELALU ADA 3 KOLOM (sesuai dengan <th> di HTML)
            const columns = [{
                    data: 'fcabang',
                    name: 'fcabang'
                },
                {
                    data: 'fstockmtno',
                    name: 'fstockmtno'
                },
                {
                    data: 'fstockmtdate',
                    name: 'fstockmtdate',
                    render: function(data) {
                        if (!data) return '';
                        const clean = data.split(' ')[0].replace(/\//g, '-');
                        if (clean.includes('-')) {
                            const parts = clean.split('-');
                            if (parts.length === 3 && parts[0].length === 4) {
                                return `${parts[2]}-${parts[1]}-${parts[0]}`;
                            }
                            return clean;
                        }
                        return data;
                    }
                },
                {
                    data: 'fadjtype',
                    name: 'fadjtype'
                },
                {
                    data: 'fgudang',
                    name: 'fgudang'
                },
                {
                    data: 'fket',
                    name: 'fket'
                },
                @if ($showActionsColumn)
                {
                    data: 'fstockmtid',
                    name: 'actions',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        // Jika tidak ada permission, return empty string
                        // if (!hasActions) {
                        //     return '';
                        // }

                        let html = '<div class="flex justify-end gap-1.5 flex-nowrap">';

                        if (canView) {
                            html += `<a href="adjstock/${data}/view">
                        <button class="inline-flex items-center bg-slate-500 text-white px-3 py-1.5 text-xs rounded hover:bg-slate-600">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            View
                        </button>
                    </a>`;
                        }

                        if (canEdit) {
                            html += `<a href="adjstock/${data}/edit" class="inline-flex items-center bg-yellow-500 text-white px-3 py-1.5 text-xs rounded hover:bg-yellow-600">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Edit
                        </a>`;
                        }

                        if (canDelete) {
                            let deleteUrl = '{{ route('adjstock.index') }}/' + data + '/delete';
                            html += `<a href="${deleteUrl}">
                                <button class="inline-flex items-center bg-red-600 text-white px-3 py-1.5 text-xs rounded hover:bg-red-700">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Hapus
                                </button>
                            </a>`;
                        }

                        html += '</div>';
                        return html;
                    }
                }
                @endif
            ];

            // 2. Definisi columnDefs
            const columnDefs = @if ($showActionsColumn) [{
                targets: -1,
                orderable: false,
                searchable: false,
                width: '280px'
            }] @else [] @endif;

            // 3. Inisialisasi DataTables
            $('#adjstockTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('adjstock.index') }}',
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
                    [2, 'desc']
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
                    const $searchInput = $toolbarSearch.find('.dt-input');
                    $searchInput.attr('placeholder', 'Cari...').css({
                        width: '500px',
                        maxWidth: '100%'
                    });

                    const $yearFilter = $('#yearFilterTemplate #yearFilterWrap').clone(true, true);
                    const $yearSelect = $yearFilter.find('select[data-role="year-filter"]');
                    $yearSelect.attr('id', 'yearFilterDT');
                    $toolbarSearch.append($yearFilter);

                    const $monthFilter = $('#monthFilterTemplate #monthFilterWrap').clone(true, true);
                    const $monthSelect = $monthFilter.find('select[data-role="month-filter"]');
                    $monthSelect.attr('id', 'monthFilterDT');
                    $toolbarSearch.append($monthFilter);

                    const gudangColumnIdx = api.columns().indexes().toArray()
                        .find(i => api.column(i).dataSrc() === 'fgudang');

                    if (gudangColumnIdx !== undefined) {
                        const $filterBlock = $('[data-column-filter="gudang"]');
                        const $filterHost = $('#gudangFilterHost');
                        const $filterTrigger = $filterBlock.find('.column-filter-search-trigger');
                        const $warehouseFilter = $('#warehouseColumnFilterTemplate input')
                            .clone(true, true);

                        $filterHost.empty().append($warehouseFilter);

                        $filterTrigger.on('click', function() {
                            const shouldShow = $filterHost.hasClass('hidden');
                            $('#gudangFilterHost').addClass('hidden');
                            if (shouldShow) {
                                $filterHost.removeClass('hidden');
                                $warehouseFilter.trigger('focus');
                            }
                        });

                        $warehouseFilter.on('input', function() {
                            api.column(gudangColumnIdx).search(this.value || '').draw();
                        });
                    }

                    $yearSelect.on('change', function() {
                        updateUrlParams();
                        api.ajax.reload();
                    });

                    $monthSelect.on('change', function() {
                        updateUrlParams();
                        api.ajax.reload();
                    });

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
