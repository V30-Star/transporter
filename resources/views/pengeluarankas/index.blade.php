@extends('layouts.app')

@section('title', 'Pengeluaran Kas')

@section('content')
    <div x-data="pengeluaranKasIndex()" class="bg-white rounded shadow p-4">
        <div class="flex justify-end items-center mb-4">
            <div></div>
            <a href="{{ route('pengeluarankas.create') }}"
                class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                <x-heroicon-o-plus class="w-4 h-4 mr-1" /> Tambah Baru
            </a>
        </div>

        <table id="pengeluaranKasTable" class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-2">Voucher No.</th>
                    <th class="border px-2 py-2">Date</th>
                    <th class="border px-2 py-2">Check No.</th>
                    <th class="border px-2 py-2">Account</th>
                    <th class="border px-2 py-2">Description</th>
                    <th class="border px-2 py-2 text-right">Payment Amount</th>
                    <th class="border px-2 py-2 no-sort">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($records as $record)
                    <tr>
                        <td class="border px-2 py-2">{{ $record->fkasmtno }}</td>
                        <td class="border px-2 py-2">
                            {{ optional($record->fkasmtdate)->format('d/m/Y') ?? \Carbon\Carbon::parse($record->fkasmtdate)->format('d/m/Y') }}
                        </td>
                        <td class="border px-2 py-2">{{ $record->fnogiro ?: '-' }}</td>
                        <td class="border px-2 py-2">{{ $record->account_summary }}</td>
                        <td class="border px-2 py-2">{{ $record->description_summary }}</td>
                        <td class="border px-2 py-2 text-right">{{ number_format((float) $record->payment_amount, 2, ',', '.') }}</td>
                        <td class="border px-2 py-2 text-center whitespace-nowrap">
                            <a href="{{ route('pengeluarankas.view', $record->fkasmtno) }}"
                                class="inline-flex items-center bg-slate-500 text-white px-4 py-2 rounded hover:bg-slate-600">
                                <x-heroicon-o-eye class="w-4 h-4 mr-1" /> View
                            </a>
                            @if ($canEdit)
                                <a href="{{ route('pengeluarankas.edit', $record->fkasmtno) }}"
                                    class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                                    <x-heroicon-o-pencil-square class="w-4 h-4 mr-1" /> Edit
                                </a>
                            @endif
                            @if ($canDelete)
                                <button type="button"
                                    @click="openDelete('{{ route('pengeluarankas.destroy', $record->fkasmtno) }}')"
                                    class="inline-flex items-center bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                                    <x-heroicon-o-trash class="w-4 h-4 mr-1" /> Hapus
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div x-show="showDeleteModal" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div @click.away="closeDelete()" class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Konfirmasi Hapus</h3>
                <p class="mb-6">Apakah Anda yakin ingin menghapus data ini?</p>
                <div class="flex justify-end gap-2">
                    <button @click="closeDelete()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Batal</button>
                    <button @click="confirmDelete()"
                        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-50"
                        :disabled="isDeleting">
                        <span x-show="!isDeleting">Hapus</span>
                        <span x-show="isDeleting">Menghapus...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.6/css/dataTables.dataTables.min.css">
    <style>
        #pengeluaranKasTable {
            width: 100% !important;
        }

        #pengeluaranKasTable th,
        #pengeluaranKasTable td {
            vertical-align: middle;
        }

        .dt-container .dt-search,
        .dt-container .dt-length {
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
        function pengeluaranKasIndex() {
            return {
                showDeleteModal: false,
                deleteUrl: '',
                isDeleting: false,

                openDelete(url) {
                    this.deleteUrl = url;
                    this.showDeleteModal = true;
                },

                closeDelete() {
                    if (!this.isDeleting) {
                        this.showDeleteModal = false;
                        this.deleteUrl = '';
                    }
                },

                async confirmDelete() {
                    this.isDeleting = true;

                    try {
                        const response = await fetch(this.deleteUrl, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                        });

                        if (!response.ok) {
                            throw new Error('Gagal menghapus data.');
                        }

                        window.location.reload();
                    } catch (error) {
                        alert(error.message);
                        this.isDeleting = false;
                    }
                }
            }
        }

        $(function() {
            $('#pengeluaranKasTable').DataTable({
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [
                    [1, 'desc'],
                    [0, 'desc']
                ],
                layout: {
                    topStart: 'search',
                    topEnd: 'pageLength',
                    bottomStart: 'info',
                    bottomEnd: 'paging',
                },
                columnDefs: [{
                        targets: 'no-sort',
                        orderable: false,
                        searchable: false
                    },
                    {
                        targets: 5,
                        className: 'text-right'
                    }
                ],
            });
        });
    </script>
@endpush
