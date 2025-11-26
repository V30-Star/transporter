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


        {{-- @php
            $canCreate = in_array('createTr_prh', explode(',', session('user_restricted_permissions', '')));
            $canEdit = in_array('updateTr_prh', explode(',', session('user_restricted_permissions', '')));
            $canDelete = in_array('deleteTr_prh', explode(',', session('user_restricted_permissions', '')));
            $showActionsColumn = $canEdit || $canDelete;
        @endphp --}}

        <div class="flex justify-end items-center mb-4">
            <div></div>

            {{-- @if ($canCreate) --}}
            <a href="{{ route('mutasi.create') }}"
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

        <table id="mutasiTable" class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-1">No. Mutasi</th>
                    <th class="border px-2 py-1">Tanggal</th>

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
            ];

            // 2. Tambah Kolom Aksi (jika ada izin)
            // if (hasActions) {
            columns.push({
                data: 'actions', // data dari 'actions'
                orderable: false, // tidak bisa di-sort
                searchable: false // tidak bisa di-search
            });
            // }

            // 3. Inisialisasi DataTables
            // Pastikan ID tabel Anda adalah 'mutasiTable'
            $('#mutasiTable').DataTable({
                // --- KUNCI SERVER-SIDE ---
                processing: true, // Tampilkan 'Loading...'
                serverSide: true, // Aktifkan mode SSP

                // Ambil data dari route ini
                ajax: '{{ route('mutasi.index') }}',
                // -------------------------

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

                // Hapus filter status (initComplete) karena tidak dipakai
                // di controller ini (hanya filter 'RCV')
            });
        });
    </script>
@endpush
