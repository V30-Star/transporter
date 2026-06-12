@extends('layouts.app')

@section('title', 'Mutasi Stock')

@section('content')
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
    }" class="bg-white rounded shadow p-4" @open-delete.window="openDelete($event.detail)">

        @php
            $permissions = array_filter(array_map('trim', explode(',', session('user_restricted_permissions', ''))));
            $canCreate = in_array('createPenerimaanBarang', $permissions, true);
            $canEdit = in_array('updatePenerimaanBarang', $permissions, true);
            $canDelete = in_array('deletePenerimaanBarang', $permissions, true);
            $showActionsColumn = $canEdit || $canDelete;
        @endphp

        <div class="flex justify-end items-center mb-4">
            <div></div>

            @if ($canCreate)
                <a href="{{ route('mutasi.create') }}"
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
                <select data-role="month-filter" class="border rounded px-2 py-1">
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

        <div id="fromWarehouseColumnFilterTemplate" class="hidden">
            <input type="search" data-role="from-warehouse-column-filter"
                class="w-full border rounded px-3 py-2 text-sm" placeholder="Cari gudang dari...">
        </div>

        <div id="toWarehouseColumnFilterTemplate" class="hidden">
            <input type="search" data-role="to-warehouse-column-filter"
                class="w-full border rounded px-3 py-2 text-sm" placeholder="Cari gudang ke...">
        </div>

        <table id="mutasiTable" class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-1">Cab.</th>
                    <th class="border px-2 py-1">No. Mutasi</th>
                    <th class="border px-2 py-1">Tanggal</th>
                    <th class="border px-2 py-1">
                        <div class="column-filter-header-block" data-column-filter="gudang-dari">
                            <div class="column-filter-header">
                                <span>Gudang Dari</span>
                                <span class="column-filter-icons">
                                    <button type="button" class="column-filter-search-trigger"
                                        aria-label="Tampilkan pencarian Gudang Dari">
                                        <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                    </button>
                                    <x-heroicon-o-arrows-up-down class="w-4 h-4" />
                                </span>
                            </div>
                            <div class="mt-2 hidden" id="gudangDariFilterHost"></div>
                        </div>
                    </th>
                    <th class="border px-2 py-1">
                        <div class="column-filter-header-block" data-column-filter="gudang-ke">
                            <div class="column-filter-header">
                                <span>Gudang Ke</span>
                                <span class="column-filter-icons">
                                    <button type="button" class="column-filter-search-trigger"
                                        aria-label="Tampilkan pencarian Gudang Ke">
                                        <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                    </button>
                                    <x-heroicon-o-arrows-up-down class="w-4 h-4" />
                                </span>
                            </div>
                            <div class="mt-2 hidden" id="gudangKeFilterHost"></div>
                        </div>
                    </th>
                    <th class="border px-2 py-1">Keterangan</th>
                    <th class="border px-2 py-1">User Id</th>

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
        <div x-show="showDeleteModal" x-cloak @keydown.escape.window="closeDelete()"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div @click.away="closeDelete()" class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Konfirmasi Hapus</h3>
                <p class="mb-6">Apakah Anda yakin ingin menghapus data ini?</p>

                <div class="flex justify-end space-x-2">
                    <button @click="closeDelete()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">
                        Batal
                    </button>
                    <form :action="deleteUrl" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                            Hapus
                        </button>
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
        $(function() {
            // Ambil dari Blade untuk menentukan jumlah kolom
            const hasActions = {{ $showActionsColumn ? 'true' : 'false' }};

            // 1. Definisi Kolom (Sangat Penting)
            // 'data' harus cocok dengan key JSON dari Controller
            const columns = [{
                    data: 'fbranchcode',
                    name: 'fbranchcode'
                },
                {
                    data: 'fstockmtno',
                    name: 'fstockmtno'
                }, // data dari 'fstockmtno'
                {
                    data: 'fstockmtdate',
                    name: 'fstockmtdate'
                },
                {
                    data: 'fgudang_dari',
                    name: 'fgudang_dari'
                },
                {
                    data: 'fgudang_ke',
                    name: 'fgudang_ke'
                },
                {
                    data: 'fket',
                    name: 'fket'
                },
                {
                    data: 'fusercreate',
                    name: 'fusercreate'
                },
            ];

            // 2. Tambah Kolom Aksi (jika ada izin)
            // if (hasActions) {
            if (hasActions) {
                columns.push({
                    data: 'actions',
                    orderable: false,
                    searchable: false
                });
            }
            // }

            // 3. Inisialisasi DataTables
            // Pastikan ID tabel Anda adalah 'mutasiTable'
            $('#mutasiTable').DataTable({
                // --- KUNCI SERVER-SIDE ---
                processing: true, // Tampilkan 'Loading...'
                serverSide: true, // Aktifkan mode SSP

                ajax: {
                    url: '{{ route('mutasi.index') }}',
                    type: 'GET',
                    data: function(d) {
                        const urlParams = new URLSearchParams(window.location.search);
                        d.year = urlParams.get('year') || '';
                        d.month = urlParams.get('month') || '';
                    }
                },

                // Terapkan kolom dari langkah 1 & 2
                columns: columns,

                // Urutkan berdasarkan kolom pertama (No. Penerimaan)
                order: [
                    [0, 'desc']
                ],

                // Tampilkan elemen standar
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

                    const gudangDariColumnIdx = api.columns().indexes().toArray()
                        .find(i => api.column(i).dataSrc() === 'fgudang_dari');

                    if (gudangDariColumnIdx !== undefined) {
                        const $filterBlock = $('[data-column-filter="gudang-dari"]');
                        const $filterHost = $('#gudangDariFilterHost');
                        const $filterTrigger = $filterBlock.find('.column-filter-search-trigger');
                        const $warehouseFilter = $('#fromWarehouseColumnFilterTemplate input')
                            .clone(true, true);

                        $filterHost.empty().append($warehouseFilter);

                        $filterTrigger.on('click', function() {
                            const shouldShow = $filterHost.hasClass('hidden');
                            $('[id="gudangDariFilterHost"], [id="gudangKeFilterHost"]').addClass('hidden');
                            if (shouldShow) {
                                $filterHost.removeClass('hidden');
                                $warehouseFilter.trigger('focus');
                            }
                        });

                        $warehouseFilter.on('input', function() {
                            const value = this.value || '';
                            api.column(gudangDariColumnIdx).search(value).draw();
                        });
                    }

                    const gudangKeColumnIdx = api.columns().indexes().toArray()
                        .find(i => api.column(i).dataSrc() === 'fgudang_ke');

                    if (gudangKeColumnIdx !== undefined) {
                        const $filterBlock = $('[data-column-filter="gudang-ke"]');
                        const $filterHost = $('#gudangKeFilterHost');
                        const $filterTrigger = $filterBlock.find('.column-filter-search-trigger');
                        const $warehouseFilter = $('#toWarehouseColumnFilterTemplate input')
                            .clone(true, true);

                        $filterHost.empty().append($warehouseFilter);

                        $filterTrigger.on('click', function() {
                            const shouldShow = $filterHost.hasClass('hidden');
                            $('[id="gudangDariFilterHost"], [id="gudangKeFilterHost"]').addClass('hidden');
                            if (shouldShow) {
                                $filterHost.removeClass('hidden');
                                $warehouseFilter.trigger('focus');
                            }
                        });

                        $warehouseFilter.on('input', function() {
                            const value = this.value || '';
                            api.column(gudangKeColumnIdx).search(value).draw();
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
