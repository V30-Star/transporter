@extends('layouts.app')

@section('title', 'Master Wilayah')

@section('content')
    @php
        $canCreate = in_array('createWilayah', explode(',', session('user_restricted_permissions', '')));
        $canEdit   = in_array('updateWilayah', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteWilayah', explode(',', session('user_restricted_permissions', '')));
        $showActionsColumn = $canEdit || $canDelete;
    @endphp

    <div x-data="wilayahData()" class="bg-white rounded shadow p-4">

        {{-- Tombol Tambah Baru --}}
        <div class="flex justify-end items-center mb-4">
            @if ($canCreate)
                <a href="{{ route('wilayah.create') }}"
                    class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    <x-heroicon-o-plus class="w-4 h-4 mr-1" /> Tambah Baru
                </a>
            @endif
        </div>

        {{-- Template Filter Status (hidden, akan di-clone ke toolbar DataTables) --}}
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
        <table id="wilayahTable" class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-2">Kode Wilayah</th>
                    <th class="border px-2 py-2">Nama Wilayah</th>
                    <th class="border px-2 py-2 no-sort">Status</th>
                    {{-- Kolom hidden untuk filter client-side, berisi nilai mentah 0/1 --}}
                    <th data-col="statusRaw" class="border px-2 py-2">StatusRaw</th>
                    @if ($showActionsColumn)
                        <th class="border px-2 py-2 col-aksi">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($wilayahs as $item)
                    @php $isActive = (string) $item->fnonactive === '0'; @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="border px-2 py-1">{{ $item->fwilayahcode }}</td>
                        <td class="border px-2 py-1">{{ $item->fwilayahname }}</td>

                        {{-- Tampilan badge Status --}}
                        <td class="border px-2 py-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs
                                {{ $isActive ? 'bg-green-100 text-green-700' : 'bg-red-200 text-red-700' }}">
                                {{ $isActive ? 'Active' : 'Non Active' }}
                            </span>
                        </td>

                        {{-- Nilai mentah fnonactive (0 atau 1) — disembunyikan oleh DataTables --}}
                        <td class="border px-2 py-1">{{ $item->fnonactive }}</td>

                        @if ($showActionsColumn)
                            <td class="border px-2 py-1 space-x-2 text-center">
                                <a href="{{ route('wilayah.view', $item->fwilayahid) }}">
                                    <button class="inline-flex items-center bg-slate-500 text-white px-4 py-2 rounded hover:bg-slate-600">
                                        <x-heroicon-o-pencil-square class="w-4 h-4 mr-1" /> View
                                    </button>
                                </a>
                                @if ($canEdit)
                                    <a href="{{ route('wilayah.edit', $item->fwilayahid) }}">
                                        <button class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                                            <x-heroicon-o-pencil-square class="w-4 h-4 mr-1" /> Edit
                                        </button>
                                    </a>
                                @endif
                                @if ($canDelete)
                                    <button
                                        @click="openDelete('{{ route('wilayah.destroy', $item->fwilayahid) }}', $event)"
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

        {{-- Modal Konfirmasi Hapus --}}
        <div x-show="showDeleteModal" x-cloak
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-transition>
            <div @click.away="!isDeleting && closeDelete()" class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Konfirmasi Hapus</h3>
                <p class="mb-6">Apakah Anda yakin ingin menghapus data ini?</p>
                <div class="flex justify-end space-x-2">
                    <button @click="closeDelete()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400"
                        :disabled="isDeleting">Batal</button>
                    <button @click="confirmDelete()"
                        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="isDeleting">
                        <span x-show="!isDeleting">Hapus</span>
                        <span x-show="isDeleting">Menghapus...</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Toast Notifikasi --}}
        <div x-show="showNotification" x-cloak x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed top-4 right-4 z-50 max-w-sm">
            <div :class="notificationType === 'success' ? 'bg-green-500' : 'bg-red-500'"
                class="text-white px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3">
                <span x-text="notificationMessage"></span>
                <button @click="showNotification = false" class="ml-4 text-white hover:text-gray-200">×</button>
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
        #wilayahTable {
            width: 100% !important;
        }

        #wilayahTable th,
        #wilayahTable td {
            text-align: left !important;
            vertical-align: middle;
        }

        #wilayahTable th:last-child,
        #wilayahTable td:last-child {
            text-align: center;
            white-space: nowrap;
        }

        #wilayahTable td:last-child {
            padding: .25rem .5rem;
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
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.6/js/dataTables.min.js"></script>

    <script>
        // =============================================
        // Alpine.js — Modal Delete
        // =============================================
        document.addEventListener('alpine:init', () => {
            Alpine.data('wilayahData', () => ({
                showDeleteModal     : false,
                deleteUrl           : '',
                isDeleting          : false,
                currentRow          : null,
                showNotification    : false,
                notificationMessage : '',
                notificationType    : 'success',

                openDelete(url, event) {
                    this.deleteUrl       = url;
                    this.currentRow      = event.target.closest('tr');
                    this.showDeleteModal = true;
                    this.isDeleting      = false;
                },

                closeDelete() {
                    if (!this.isDeleting) {
                        this.showDeleteModal = false;
                        this.deleteUrl       = '';
                        this.currentRow      = null;
                    }
                },

                confirmDelete() {
                    this.isDeleting = true;
                    const rowToDelete = this.currentRow;

                    fetch(this.deleteUrl, {
                        method  : 'DELETE',
                        headers : {
                            'X-CSRF-TOKEN' : document.querySelector('meta[name="csrf-token"]').content,
                            'Accept'       : 'application/json',
                            'Content-Type' : 'application/json',
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
                        this.isDeleting      = false;

                        if (result.ok) {
                            // Hapus baris dari DataTables tanpa reload halaman
                            const table = $('#wilayahTable').DataTable();
                            if (rowToDelete) {
                                table.row($(rowToDelete)).remove().draw(false);
                            }
                            this.showNotificationMsg('success', result.data.message || 'Data berhasil dihapus');
                        } else {
                            this.showNotificationMsg('error', result.data.message || 'Gagal menghapus data');
                        }

                        this.currentRow = null;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        this.showDeleteModal = false;
                        this.isDeleting      = false;
                        this.showNotificationMsg('error', 'Terjadi kesalahan. Silakan coba lagi.');
                        this.currentRow = null;
                    });
                },

                showNotificationMsg(type, message) {
                    this.notificationType    = type;
                    this.notificationMessage = message;
                    this.showNotification    = true;
                    setTimeout(() => { this.showNotification = false; }, 3000);
                }
            }));
        });


        // =============================================
        // jQuery — Inisialisasi DataTables
        // =============================================
        $(function () {

            const table = $('#wilayahTable').DataTable({
                autoWidth  : false,
                pageLength : 10,
                lengthMenu : [10, 25, 50, 100],
                order      : [[0, 'asc']],
                layout: {
                    topStart   : 'search',
                    topEnd     : 'pageLength',
                    bottomStart: 'info',
                    bottomEnd  : 'paging',
                },
                columnDefs: [
                    { targets: 'col-aksi', orderable: false, searchable: false, width: 120 },
                    { targets: 'no-sort',  orderable: false },
                ],
                language: {
                    lengthMenu: "Show _MENU_ entries",
                },
            });

            // ------------------------------------------
            // 1. Cari index kolom statusRaw
            // ------------------------------------------
            const statusRawIdx = table.columns().indexes().toArray()
                .find(i => $(table.column(i).header()).attr('data-col') === 'statusRaw');

            if (statusRawIdx === undefined) {
                console.warn('Kolom statusRaw tidak ditemukan.');
                return;
            }

            // Sembunyikan kolom statusRaw dari tampilan
            table.column(statusRawIdx).visible(false);

            // Default: tampilkan hanya Active (fnonactive = 0)
            table.column(statusRawIdx).search('^0$', true, false).draw();

            // ------------------------------------------
            // 2. Clone template filter Status ke toolbar Search
            // ------------------------------------------
            const $container     = $(table.table().container());
            const $toolbarSearch = $container.find('.dt-search');

            const $filter = $('#statusFilterTemplate #statusFilterWrap').clone(true, true);
            const $select = $filter.find('select[data-role="status-filter"]');
            $select.attr('id', 'statusFilterDT');
            $toolbarSearch.append($filter); // sebelah kanan kotak search

            // Event: dropdown filter Status berubah
            $select.on('change', function () {
                const val = this.value;
                if (val === 'active') {
                    table.column(statusRawIdx).search('^0$', true, false).draw();
                } else if (val === 'nonactive') {
                    table.column(statusRawIdx).search('^1$', true, false).draw();
                } else {
                    // "all" — hapus filter
                    table.column(statusRawIdx).search('', true, false).draw();
                }
            });

            // ------------------------------------------
            // 3. Paksa input Search jadi UPPERCASE
            // ------------------------------------------
            const $searchInput = $toolbarSearch.find('.dt-input');
            $searchInput.css({
                'width'          : '400px',
                'maxWidth'       : '100%',
                'text-transform' : 'uppercase',
            });

            $container.on('input', '.dt-search .dt-input', function () {
                const start = this.selectionStart;
                const end   = this.selectionEnd;
                this.value  = this.value.toUpperCase();
                this.setSelectionRange(start, end); // jaga posisi kursor
                table.search(this.value).draw();
            });

        });
    </script>
@endpush