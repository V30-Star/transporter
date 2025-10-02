@extends('layouts.app')

@section('title', 'Master Gudang')

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
    }" x-on:open-delete.window="openDelete($event.detail)" class="bg-white rounded shadow p-4">

        @php
            $canCreate = in_array('createGudang', explode(',', session('user_restricted_permissions', '')));
            $canEdit = in_array('updateGudang', explode(',', session('user_restricted_permissions', '')));
            $canDelete = in_array('deleteGudang', explode(',', session('user_restricted_permissions', '')));
            $showActionsColumn = $canEdit || $canDelete;
        @endphp

        <div class="flex justify-end items-center mb-4">
            @if ($canCreate)
                <a href="{{ route('gudang.create') }}"
                    class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    <x-heroicon-o-plus class="w-4 h-4 mr-1" /> Tambah Baru
                </a>
            @endif
        </div>

        {{-- Table --}}
        <table id="gudangTable" class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-2">Cabang</th>
                    <th class="border px-2 py-2">Kode Gudang</th>
                    <th class="border px-2 py-2">Nama Gudang</th>
                    <th class="border px-2 py-2">Alamat</th>
                    @if ($showActionsColumn)
                        <th class="border px-2 py-2">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody id="tableBody">
                @forelse($gudangs as $item)
                    <tr class="hover:bg-gray-50">
                        <td>{{ $item->cabang->fcabangname ?? 'N/A' }}</td>
                        <td>{{ $item->fwhcode }}</td>
                        <td>{{ $item->fwhname }}</td>
                        <td>{{ $item->faddress }}</td>
                        @if ($showActionsColumn)
                            <td class="border px-2 py-1 space-x-2">
                                @if ($canEdit)
                                    <a href="{{ route('gudang.edit', $item->fwhid) }}">
                                        <button
                                            class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                                            <x-heroicon-o-pencil-square class="w-4 h-4 mr-1" /> Edit
                                        </button>
                                    </a>
                                @endif
                                @if ($canDelete)
                                    <button @click="$dispatch('open-delete', '{{ route('gudang.destroy', $item->fwhid) }}')"
                                        class="inline-flex items-center bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                                        <x-heroicon-o-trash class="w-4 h-4 mr-1" /> Hapus
                                    </button>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $showActionsColumn ? 5 : 4 }}" class="text-center py-4">Tidak ada data.</td>
                    </tr>
                @endforelse
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
        #gudangTable {
            width: 100% !important;
        }

        #gudangTable th,
        #gudangTable td {
            vertical-align: middle;
        }

        /* Kolom Aksi: jangan mepet, tapi tetap ringkas */
        #gudangTable th:last-child,
        #gudangTable td:last-child {
            white-space: nowrap;
            text-align: center;
        }

        #gudangTable td:last-child {
            padding: .25rem .5rem;
        }

        .btn-aksi {
            padding: .25rem .5rem;
            font-size: .825rem;
        }
    </style>
@endpush

@push('scripts')
    {{-- jQuery + DataTables JS (CDN) --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.6/js/dataTables.min.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            /* no-op */
        });

        $(function() {
            // Inisialisasi DataTables
            const hasActions = {{ $showActionsColumn ? 'true' : 'false' }};
            const columns = hasActions ? [{
                    title: 'Cabang'
                },
                {
                    title: 'Kode Gudang'
                },
                {
                    title: 'Nama Gudang'
                },
                {
                    title: 'Aksi',
                    orderable: false,
                    searchable: false
                }
            ] : [{
                    title: 'Cabang'
                },
                {
                    title: 'Kode Gudang'
                },
                {
                    title: 'Nama Gudang'
                }
            ];

            $('#gudangTable').DataTable({
                autoWidth: false,
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
                order: [
                    [0, 'asc']
                ],
                layout: {
                    topStart: 'search', // Search pindah ke kiri
                    topEnd: 'pageLength', // Length menu pindah ke kanan
                    bottomStart: 'info',
                    bottomEnd: 'paging'
                },
                columnDefs: [{
                    targets: -1,
                    orderable: false,
                    searchable: false,
                    width: 120
                }],
                initComplete: function() {
                    const api = this.api();
                    const $len = $(api.table().container()).find('.dt-length .dt-input');
                    $len.addClass('focus:outline-none focus:ring focus:ring-blue-100');
                }
            });

        });
    </script>
@endpush
