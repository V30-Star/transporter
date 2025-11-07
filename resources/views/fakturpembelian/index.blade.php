@extends('layouts.app')

@section('title', 'Faktur Pembelian')

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

        {{-- @php
            $canCreate = in_array('createTr_prh', explode(',', session('user_restricted_permissions', '')));
            $canEdit = in_array('updateTr_prh', explode(',', session('user_restricted_permissions', '')));
            $canDelete = in_array('deleteTr_prh', explode(',', session('user_restricted_permissions', '')));
            $showActionsColumn = $canEdit || $canDelete;
        @endphp --}}

        <div class="flex justify-end items-center mb-4">
            <div></div>

            {{-- @if ($canCreate) --}}
            <a href="{{ route('fakturpembelian.create') }}"
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
                <select data-role="year-filter" class="border rounded px-2 py-1">
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
        <table id="fakturpembelianTable" class="min-w-full border text-sm">
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
        <div x-show="showDeleteModal" x-cloak
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div @click.away="closeDelete()" class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Konfirmasi Hapus</h3>
                <p class="mb-6">Apakah Anda yakin ingin menghapus data ini?</p>

                <div class="flex justify-end space-x-2">
                    <button @click="closeDelete()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Batal</button>
                    <form :action="deleteUrl" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
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
            text-align: center;
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
        $(function() {
            // Ambil dari Blade untuk menentukan jumlah kolom
            const hasActions = {{ $showActionsColumn ? 'true' : 'false' }};

            // 1. Definisi Kolom (Sangat Penting)
            // 'data' harus cocok dengan key JSON dari Controller
            const columns = [{
                    data: 'fstockmtno'
                }, // data dari 'fstockmtno'
                {
                    data: 'fstockmtdate'
                }, // data dari 'fstockmtdate'
                {
                    data: 'ftypebuy'
                }, // data dari 'ftypebuy'
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false
                }
            ];

            // 2. Tambah Kolom Aksi (jika ada izin)
            // if (hasActions) {
            const columnDefs = [{
                targets: -1, // Kolom terakhir (actions)
                orderable: false,
                searchable: false,
                width: '200px'
            }];
            // }

            // 3. Inisialisasi DataTables
            // Ganti '#tr_prhTable' menjadi '#fakturpembelianTable'
            $('#fakturpembelianTable').DataTable({
                // --- KUNCI SERVER-SIDE ---
                processing: true, // Tampilkan 'Loading...'
                serverSide: true, // Aktifkan mode SSP
                ajax: {
                    url: '{{ route('fakturpembelian.index') }}',
                    type: 'GET',
                    data: function(d) {
                        const urlParams = new URLSearchParams(window.location.search);
                        d.year = urlParams.get('year') || '';
                        d.month = urlParams.get('month') || '';
                    }
                },
                // Terapkan kolom dari langkah 1 & 2
                columns: columns,
                columnDefs: columnDefs,

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

                    // Baca status dari URL, default 'active'
                    const urlParams = new URLSearchParams(window.location.search);

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

                        // Update URL tanpa reload
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
