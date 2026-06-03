@extends('layouts.app')

@section('title', "Faktur Pembelian")

@section('content')
    <div x-data class="bg-white rounded shadow p-4">

        @php
            $permissions = array_filter(array_map('trim', explode(',', session('user_restricted_permissions', ''))));
            $canCreate = in_array('createFakturPembelian', $permissions, true);
            $canEdit = in_array('updateFakturPembelian', $permissions, true);
            $canDelete = in_array('deleteFakturPembelian', $permissions, true);
            $canPrint = in_array('printFakturPembelian', $permissions, true);
            $canView = in_array('viewTr_prh', $permissions, true) || $canCreate || $canEdit || $canDelete || $canPrint;
            $showActionsColumn = $canView || $canEdit || $canDelete;
        @endphp

        <div class="flex justify-end items-center mb-4">
            <div></div>

            @if ($canCreate)
                <a href="{{ route('fakturpembelian.create') }}"
                    class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    <x-heroicon-o-plus class="w-4 h-4 mr-1" /> {{ "Tambah Baru" }}
                </a>
            @endif
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
                <span class="text-sm text-gray-700">{{ "Tahun" }}</span>
                <select data-role="year-filter" class="border rounded px-2 py-1 w-24">
                    <option value="">{{ "Semua" }}</option>
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
                <span class="text-sm text-gray-700">{{ "Bulan" }}</span>
                <select data-role="month-filter" class="border rounded px-2 py-1">
                    <option value="">{{ "Semua" }}</option>
                    <option value="1" {{ $month == '1' ? 'selected' : '' }}>{{ "Januari" }}</option>
                    <option value="2" {{ $month == '2' ? 'selected' : '' }}>{{ "Februari" }}</option>
                    <option value="3" {{ $month == '3' ? 'selected' : '' }}>{{ "Maret" }}</option>
                    <option value="4" {{ $month == '4' ? 'selected' : '' }}>{{ "April" }}</option>
                    <option value="5" {{ $month == '5' ? 'selected' : '' }}>{{ "Mei" }}</option>
                    <option value="6" {{ $month == '6' ? 'selected' : '' }}>{{ "Juni" }}</option>
                    <option value="7" {{ $month == '7' ? 'selected' : '' }}>{{ "Juli" }}</option>
                    <option value="8" {{ $month == '8' ? 'selected' : '' }}>{{ "Agustus" }}</option>
                    <option value="9" {{ $month == '9' ? 'selected' : '' }}>{{ "September" }}</option>
                    <option value="10" {{ $month == '10' ? 'selected' : '' }}>{{ "Oktober" }}</option>
                    <option value="11" {{ $month == '11' ? 'selected' : '' }}>{{ "November" }}</option>
                    <option value="12" {{ $month == '12' ? 'selected' : '' }}>{{ "Desember" }}</option>
                </select>
            </div>
        </div>

        {{-- Table --}}
        {{-- GANTI TABEL ANDA DENGAN INI --}}
        <table id="fakturpembelianTable" class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-1">{{ "Cabang" }}</th>
                    <th class="border px-2 py-1">{{ "No.Transaksi" }}</th>
                    <th class="border px-2 py-1">{{ "Tanggal" }}</th>
                    <th class="border px-2 py-1">{{ "Tipe Pembelian" }}</th>
                    <th class="border px-2 py-1">{{ "Faktur#" }}</th>
                    <th class="border px-2 py-1">
                        <div class="flex items-center justify-between">
                            <span>{{ "Gudang" }}</span>
                            <button type="button" class="col-search-btn p-1 hover:bg-gray-200 rounded"
                                data-column="5" title="Filter Gudang">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="col-search-input mt-2 hidden">
                            <input type="text"
                                class="dt-column-search w-full px-2 py-1 border border-gray-300 rounded text-sm uppercase focus:outline-none focus:ring-1 focus:ring-blue-500"
                                data-column="5" placeholder="Cari Gudang...">
                        </div>
                    </th>
                    <th class="border px-2 py-1">{{ "Nama Supplier" }}</th>
                    <th class="border px-2 py-1">{{ "Referensi#" }}</th>
                    <th class="border px-2 py-1 text-right">{{ "Total Harga" }}</th>

                    @if ($showActionsColumn)
                        <th class="border px-2 py-1 col-aksi">{{ "Aksi" }}</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                {{-- KOSONGKAN BAGIAN INI --}}
            </tbody>
        </table>

        {{-- Modal Delete --}}
        <div x-show="$store.fakturpembelianStore.showDeleteModal" x-cloak
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-transition>
            <div @click.away="!$store.fakturpembelianStore.isDeleting && $store.fakturpembelianStore.closeDelete()"
                class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold mb-4">{{ "Konfirmasi Hapus" }}</h3>
                <p class="mb-6">{{ "Apakah Anda yakin ingin menghapus data ini?" }}</p>
                <div class="flex justify-end space-x-2">
                    <button @click="$store.fakturpembelianStore.closeDelete()"
                        class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400"
                        :disabled="$store.fakturpembelianStore.isDeleting">
                        {{ "Batal" }}
                    </button>
                    <button @click="$store.fakturpembelianStore.confirmDelete()"
                        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-50"
                        :disabled="$store.fakturpembelianStore.isDeleting">
                        <span x-show="!$store.fakturpembelianStore.isDeleting">{{ "Hapus" }}</span>
                        <span x-show="$store.fakturpembelianStore.isDeleting">{{ "Menghapus..." }}</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Toast Notification --}}
        <div x-show="$store.fakturpembelianStore.showNotification" x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed top-4 right-4 z-50 max-w-sm">
            <div :class="$store.fakturpembelianStore.notificationType === 'success' ? 'bg-green-500' : 'bg-red-500'"
                class="text-white px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3">
                <span x-text="$store.fakturpembelianStore.notificationMessage"></span>
                <button @click="$store.fakturpembelianStore.showNotification = false"
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
        #fakturpembelianTable {
            width: 100% !important;
        }

        #fakturpembelianTable th,
        #fakturpembelianTable td {
            text-align: left !important;
            vertical-align: middle;
        }

        #fakturpembelianTable th.text-right,
        #fakturpembelianTable td.text-right,
        #fakturpembelianTable th.dt-right,
        #fakturpembelianTable td.dt-right,
        #fakturpembelianTable th.dt-body-right,
        #fakturpembelianTable td.dt-body-right {
            text-align: right !important;
        }

        /* Kolom Aksi: jangan mepet, tapi tetap ringkas */
        #fakturpembelianTable th:last-child,
        #fakturpembelianTable td:last-child {
            white-space: nowrap;
            text-align: center;
        }

        #fakturpembelianTable td:last-child {
            padding: .25rem .5rem;
        }

        .btn-aksi {
            padding: .25rem .5rem;
            font-size: .825rem;
        }

        #fakturpembelianTable th,
        #fakturpembelianTable td {
            text-align: left !important;
            vertical-align: middle;
        }

        #fakturpembelianTable th.text-right,
        #fakturpembelianTable td.text-right,
        #fakturpembelianTable th.dt-right,
        #fakturpembelianTable td.dt-right,
        #fakturpembelianTable th.dt-body-right,
        #fakturpembelianTable td.dt-body-right {
            text-align: right !important;
        }

        #fakturpembelianTable th:last-child,
        #fakturpembelianTable td:last-child {
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

        .col-search-btn {
            line-height: 1;
        }
    </style>
@endpush

@push('scripts')
    {{-- jQuery + DataTables JS (CDN) --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.6/js/dataTables.min.js"></script>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('fakturpembelianStore', {
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
                                const table = $('#fakturpembelianTable').DataTable();
                                if (rowToDelete) {
                                    table.row($(rowToDelete)).remove().draw(false);
                                }
                                this.showNotificationMsg('success', result.data.message ||
                                    @json("Data berhasil dihapus."));
                            } else {
                                this.showNotificationMsg('error', result.data.message ||
                                    @json("Hapus data gagal."));
                            }

                            this.currentRow = null;
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            this.showDeleteModal = false;
                            this.isDeleting = false;
                            this.showNotificationMsg('error', @json("Terjadi kesalahan. Coba lagi."));
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
            const $fakturpembelianTable = $('#fakturpembelianTable');
            let activeColumnSearch = null;

            function syncColumnSearchVisibility() {
                const $dtContainer = $fakturpembelianTable.closest('.dt-container');
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
                const $input = $fakturpembelianTable.closest('.dt-container')
                    .find(`.dt-column-search[data-column="${columnIndex}"]`);

                return $input.length && String($input.val() || '').trim() !== '';
            }

            const hasActions = {{ $showActionsColumn ? 'true' : 'false' }};
            const canView = {{ $canView ? 'true' : 'false' }};
            const canEdit = {{ $canEdit ? 'true' : 'false' }};
            const canDelete = {{ $canDelete ? 'true' : 'false' }};

            // 1. Definisi Kolom
            const columns = [{
                    data: 'fbranchcode',
                    name: 'fbranchcode',
                    defaultContent: '-'
                },
                {
                    data: 'fstockmtno_display',
                    name: 'fstockmtno'
                },
                {
                    data: 'fstockmtdate',
                    name: 'fstockmtdate'
                },
                {
                    data: 'ftypebuy',
                    name: 'ftypebuy',
                    defaultContent: '-',
                    render: function(data, type) {
                        if (type === 'display' || type === 'filter') {
                            const val = String(data);
                            if (val === '0') return 'Stock';
                            if (val === '1') return 'Non-Stock';
                            if (val === '2') return 'Down Payment';
                            return data || '-';
                        }
                        return data;
                    }
                },
                {
                    data: 'ffakturno',
                    name: 'ffakturno',
                    defaultContent: '-'
                },
                {
                    data: 'fgudang',
                    name: 'fgudang',
                    defaultContent: '-'
                },
                {
                    data: 'fsuppliername',
                    name: 'fsuppliername',
                    defaultContent: '-'
                },
                {
                    data: 'freferensi',
                    name: 'freferensi',
                    defaultContent: '-'
                },
                {
                    data: 'famountmt',
                    name: 'famountmt',
                    className: 'text-right dt-body-right',
                    render: function(data, type) {
                        if (type === 'display' || type === 'filter') {
                            return Number(data || 0).toLocaleString('id-ID', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }

                        return data;
                    }
                },
                @if ($showActionsColumn)
                {
                    data: 'fstockmtid',
                    name: 'actions',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        let html = '<div class="flex gap-2">';

                        if (canView) {
                            html += `<a href="fakturpembelian/${data}/view">
                        <button class="inline-flex items-center bg-slate-500 text-white px-4 py-2 rounded hover:bg-slate-600">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            {{ "View" }}
                        </button>
                    </a>`;
                        }

                        if (canEdit) {
                            html += `<a href="fakturpembelian/${data}/edit" class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            {{ "Edit" }}
                        </a>`;
                        }

                        if (canDelete) {
                            let deleteUrl = '{{ route('fakturpembelian.index') }}/' + data + '/delete';
                            html += `<a href="${deleteUrl}">
                                <button class="inline-flex items-center bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    {{ "Hapus" }}
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
            const table = $('#fakturpembelianTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('fakturpembelian.index') }}',
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
                    [1, 'desc']
                ],
                layout: {
                    topStart: 'search',
                    topEnd: 'pageLength',
                    bottomStart: 'info',
                    bottomEnd: 'paging'
                },
                drawCallback: function() {
                    syncColumnSearchVisibility();
                },
                language: {
                    search: @json("Search" . ':'),
                    lengthMenu: '_MENU_',
                    info: '_START_ - _END_ / _TOTAL_',
                    infoEmpty: '0 / 0',
                    zeroRecords: @json("Tidak ada detail item."),
                    emptyTable: @json("Tidak ada detail item."),
                    paginate: {
                        first: '<<',
                        last: '>>',
                        next: '>',
                        previous: '<'
                    }
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
                    $searchInput.attr('placeholder', 'Cari...');

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

            // Column search events
            const $container = $($fakturpembelianTable.closest('.dt-container'));
            const $searchInput = $container.find('.dt-search .dt-input');

            // Prevent sort trigger on column search interaction
            $container.on('click', '.col-search-btn, .col-search-input', function(e) {
                e.stopPropagation();
            });

            $container.on('click', '.col-search-btn', function(e) {
                e.stopPropagation();
                const columnIndex = Number($(this).data('column'));
                const $th = $(this).closest('th');
                const $colSearchInput = $th.find('.col-search-input');

                if (activeColumnSearch === columnIndex && !$colSearchInput.hasClass('hidden')) {
                    activeColumnSearch = null;
                    $colSearchInput.addClass('hidden');
                    return;
                }

                activeColumnSearch = columnIndex;
                syncColumnSearchVisibility();

                if (!$colSearchInput.hasClass('hidden')) {
                    $colSearchInput.find('input').focus();
                }
            });

            // Close search inputs when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.col-search-btn').length && !$(e.target).closest('.col-search-input').length) {
                    if (activeColumnSearch !== null && hasColumnSearchValue(activeColumnSearch)) {
                        syncColumnSearchVisibility();
                        return;
                    }

                    activeColumnSearch = null;
                    syncColumnSearchVisibility();
                }
            });

            $container.on('input', '.dt-column-search', function() {
                const columnIndex = Number($(this).data('column'));
                const start = this.selectionStart;
                const end = this.selectionEnd;
                this.value = this.value.toUpperCase();
                this.setSelectionRange(start, end);
                table.column(columnIndex).search(this.value).draw();
            });

            $container.on('keydown', '.dt-column-search', function(e) {
                if (e.key === 'Escape') {
                    this.value = '';
                    table.column(Number($(this).data('column'))).search('').draw();
                    activeColumnSearch = null;
                    syncColumnSearchVisibility();
                    $searchInput.trigger('focus');
                }
            });
        });
    </script>
@endpush
