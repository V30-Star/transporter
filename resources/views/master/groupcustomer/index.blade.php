@extends('layouts.app')

@section('title', 'Daftar Group Customer')

@section('content')
    @php
        $canCreate = in_array('createGroupCustomer', explode(',', session('user_restricted_permissions', '')));
        $canEdit   = in_array('updateGroupCustomer', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteGroupCustomer', explode(',', session('user_restricted_permissions', '')));
        $showActionsColumn = $canEdit || $canDelete;
    @endphp

    <div x-data="modalDelete()" class="bg-white rounded shadow p-4">

        {{-- Tombol Tambah Baru --}}
        <div class="flex justify-end mb-4">
            @if ($canCreate)
                <a href="{{ route('groupcustomer.create') }}"
                    class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    <x-heroicon-o-plus class="w-4 h-4 mr-1" /> Tambah Baru
                </a>
            @endif
        </div>

        {{-- Tabel --}}
        <table id="groupcustomerTable" class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-2">Kode Group Customer</th>
                    <th class="border px-2 py-2">Nama Group Customer</th>
                    <th class="border px-2 py-2">Status</th>
                    {{-- Kolom hidden untuk filter, berisi nilai mentah 0/1 --}}
                    <th data-col="statusRaw" class="border px-2 py-2">StatusRaw</th>
                    @if ($showActionsColumn)
                        <th class="border px-2 py-2 col-aksi">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($groupcustomers as $gc)
                    @php $isActive = (string) $gc->fnonactive === '0'; @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="border px-2 py-1">{{ $gc->fgroupcode }}</td>
                        <td class="border px-2 py-1">{{ $gc->fgroupname }}</td>

                        {{-- Kolom Status: tampilan badge --}}
                        <td class="border px-2 py-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs
                                {{ $isActive ? 'bg-green-100 text-green-700' : 'bg-red-200 text-red-700' }}">
                                {{ $isActive ? 'Active' : 'Non Active' }}
                            </span>
                        </td>

                        {{-- Kolom StatusRaw: nilai mentah 0 atau 1, akan disembunyikan DataTables --}}
                        <td class="border px-2 py-1">{{ $gc->fnonactive }}</td>

                        @if ($showActionsColumn)
                            <td class="border px-2 py-1 text-center space-x-1">
                                <a href="{{ route('groupcustomer.view', $gc->fgroupid) }}">
                                    <button class="inline-flex items-center bg-slate-500 text-white px-3 py-1 rounded hover:bg-slate-600 text-xs">
                                        <x-heroicon-o-eye class="w-3 h-3 mr-1" /> View
                                    </button>
                                </a>
                                @if ($canEdit)
                                    <a href="{{ route('groupcustomer.edit', $gc->fgroupid) }}"
                                        class="inline-flex items-center bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600 text-xs">
                                        <x-heroicon-o-pencil-square class="w-3 h-3 mr-1" /> Edit
                                    </a>
                                @endif
                                @if ($canDelete)
                                    <button
                                        @click="openDelete('{{ route('groupcustomer.destroy', $gc->fgroupid) }}', $event)"
                                        class="inline-flex items-center bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 text-xs">
                                        <x-heroicon-o-trash class="w-3 h-3 mr-1" /> Hapus
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
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Konfirmasi Hapus</h3>
                <p class="mb-6">Apakah Anda yakin ingin menghapus data ini?</p>
                <div class="flex justify-end space-x-2">
                    <button @click="closeDelete()"
                        class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400"
                        :disabled="isDeleting">Batal</button>
                    <button @click="confirmDelete()"
                        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-50"
                        :disabled="isDeleting">
                        <span x-show="!isDeleting">Hapus</span>
                        <span x-show="isDeleting">Menghapus...</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Toast Notifikasi --}}
        <div x-show="showNotification" x-cloak x-transition
            class="fixed top-4 right-4 z-50 max-w-sm">
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
        #groupcustomerTable { width: 100% !important; }
        #groupcustomerTable th,
        #groupcustomerTable td { vertical-align: middle; }
        .dt-container .dt-search {
            display: flex;
            align-items: center;
            gap: .75rem;
            flex-wrap: wrap;
        }

        #statusFilterWrap {
            display: flex;
            align-items: center;
            gap: .5rem;
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
            Alpine.data('modalDelete', () => ({
                showDeleteModal : false,
                deleteUrl       : '',
                isDeleting      : false,
                currentRow      : null,
                showNotification    : false,
                notificationMessage : '',
                notificationType    : 'success',

                openDelete(url, event) {
                    this.deleteUrl  = url;
                    this.currentRow = event.target.closest('tr');
                    this.showDeleteModal = true;
                    this.isDeleting = false;
                },

                closeDelete() {
                    if (!this.isDeleting) {
                        this.showDeleteModal = false;
                        this.deleteUrl  = '';
                        this.currentRow = null;
                    }
                },

                confirmDelete() {
                    this.isDeleting = true;
                    const row = this.currentRow;

                    fetch(this.deleteUrl, {
                        method  : 'DELETE',
                        headers : {
                            'X-CSRF-TOKEN' : document.querySelector('meta[name="csrf-token"]').content,
                            'Accept'       : 'application/json',
                            'Content-Type' : 'application/json',
                        }
                    })
                    .then(res => res.json().then(data => ({ ok: res.ok, data })))
                    .then(result => {
                        this.showDeleteModal = false;
                        this.isDeleting      = false;
                        this.currentRow      = null;

                        if (result.ok) {
                            // Hapus baris dari DataTables tanpa reload halaman
                            $('#groupcustomerTable').DataTable().row($(row)).remove().draw(false);
                            this.notify('success', result.data.message || 'Data berhasil dihapus');
                        } else {
                            this.notify('error', result.data.message || 'Gagal menghapus data');
                        }
                    })
                    .catch(() => {
                        this.showDeleteModal = false;
                        this.isDeleting      = false;
                        this.notify('error', 'Terjadi kesalahan. Silakan coba lagi.');
                    });
                },

                notify(type, message) {
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

            const table = $('#groupcustomerTable').DataTable({
                autoWidth  : false,
                pageLength : 10,
                lengthMenu : [10, 25, 50, 100],
                order      : [[0, 'asc']],
                layout: {
                    topStart  : 'search',
                    topEnd    : 'pageLength',
                    bottomStart: 'info',
                    bottomEnd : 'paging',
                },
                columnDefs: [
                    // Kolom Aksi — tidak bisa diurutkan/dicari
                    { targets: 'col-aksi',   orderable: false, searchable: false },
                    // Kolom StatusRaw — sembunyikan, tapi tetap bisa difilter
                    { targets: 'no-sort',    orderable: false },
                ],
                language: {
                    lengthMenu: "Show _MENU_ entries",
                },
            });

            // ------------------------------------------
            // Setelah tabel siap: sembunyikan kolom StatusRaw
            // dan tambahkan dropdown filter Status
            // ------------------------------------------
            const statusRawIdx = table.columns().indexes().toArray()
                .find(i => $(table.column(i).header()).data('col') === 'statusRaw');

            if (statusRawIdx === undefined) {
                console.warn('Kolom statusRaw tidak ditemukan di thead.');
                return;
            }

            // Sembunyikan kolom statusRaw dari tampilan
            table.column(statusRawIdx).visible(false);

            // Set default filter: tampilkan hanya yang Active (fnonactive = 0)
            table.column(statusRawIdx).search('^0$', true, false).draw();

            // ------------------------------------------
            // Buat dropdown filter Status dan sisipkan
            // di sebelah kanan kotak Search
            // ------------------------------------------
            const $filterHtml = $(`
                <div class="flex items-center gap-2 ml-2">
                    <span class="text-sm text-gray-700">Status</span>
                    <select id="statusFilter" class="border rounded px-2 py-1 text-sm">
                        <option value="all">All</option>
                        <option value="active" selected>Active</option>
                        <option value="nonactive">Non Active</option>
                    </select>
                </div>
            `);

            // Sisipkan setelah kotak Search bawaan DataTables
            $(table.table().container()).find('.dt-search').append($filterHtml);

            // Event: saat dropdown berubah, ubah filter kolom statusRaw
            $('#statusFilter').on('change', function () {
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
            // Paksa input Search jadi UPPERCASE
            // ------------------------------------------
            $(table.table().container()).on('input', '.dt-search input', function () {
                const start = this.selectionStart;
                const end   = this.selectionEnd;
                this.value  = this.value.toUpperCase();
                this.setSelectionRange(start, end);      // jaga posisi kursor
                table.search(this.value).draw();
            });

            // Style tambahan untuk input Search
            $(table.table().container()).find('.dt-search input').css({
                width           : '300px',
                maxWidth        : '100%',
                textTransform   : 'uppercase',
            });

        });
    </script>
@endpush