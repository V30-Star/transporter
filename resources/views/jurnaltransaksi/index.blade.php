@extends('layouts.app')

@section('title', 'Jurnal Transaksi')

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

        <div class="flex justify-end items-center mb-4">
            <div></div>

            <a href="{{ route('jurnaltransaksi.create') }}"
                class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                <x-heroicon-o-plus class="w-4 h-4 mr-1" /> Tambah Baru
            </a>
        </div>

        <table id="mutasiTable" class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-1">No.Jurnal</th>
                    <th class="border px-2 py-1">Tanggal</th>
                    <th class="border px-2 py-1">Tipe</th>
                    <th class="border px-2 py-1 text-right">Saldo</th>
                    <th class="border px-2 py-1">Keterangan</th>
                    <th class="border px-2 py-1 col-aksi">Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>

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

        #mutasiTable {
            width: 100% !important;
        }

        #mutasiTable th,
        #mutasiTable td {
            text-align: left !important;
            vertical-align: middle;
        }

        #mutasiTable th:last-child,
        #mutasiTable td:last-child {
            white-space: nowrap;
            text-align: right;
        }

        #mutasiTable td:last-child {
            padding: .25rem .5rem;
        }

        .btn-aksi {
            padding: .25rem .5rem;
            font-size: .825rem;
        }

        .dataTables_wrapper .dt-search {
            display: flex;
            align-items: center;
            gap: .75rem;
            flex-wrap: wrap;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.6/js/dataTables.min.js"></script>

    <script>
        $(function() {
            $('#mutasiTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('jurnaltransaksi.index') }}',
                columns: [{
                        data: 'fjurnalno'
                    },
                    {
                        data: 'fjurnaldate'
                    },
                    {
                        data: 'fjurnaltype'
                    },
                    {
                        data: 'fbalance_rp',
                        className: 'text-right'
                    },
                    {
                        data: 'fjurnalnote'
                    },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [0, 'desc']
                ],
                layout: {
                    topStart: 'search',
                    topEnd: 'pageLength',
                    bottomStart: 'info',
                    bottomEnd: 'paging'
                }
            });
        });
    </script>
@endpush
