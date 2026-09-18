@extends('layouts.app')

@section('title', 'Master Type Pembayaran')

@section('content')
    <div class="bg-white rounded shadow p-4">

        {{-- Tombol Tambah Baru --}}
        <div class="flex justify-end items-center mb-4">
            @if ($canCreate)
                <a href="{{ route('typepembayaran.create') }}"
                    class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    <x-heroicon-o-plus class="w-4 h-4 mr-1" /> Tambah Baru
                </a>
            @endif
        </div>

        {{-- Tabel --}}
        <div class="overflow-x-auto">
            <table id="typePembayaranTable" class="min-w-full border text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border px-2 py-2">Kode Type Pembayaran</th>
                        <th class="border px-2 py-2">Nama Type Pembayaran</th>
                        <th class="border px-2 py-2">Account Kas/Bank</th>
                        <th class="border px-2 py-2 col-aksi text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($typePembayarans as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="border px-2 py-1 font-semibold">{{ $item->ftypepembayarankode }}</td>
                            <td class="border px-2 py-1">{{ $item->ftypepembayaranname }}</td>
                            <td class="border px-2 py-1">
                                @if ($item->account)
                                    <span class="font-mono text-xs bg-slate-100 px-1.5 py-0.5 rounded mr-1">{{ $item->account->faccount }}</span>
                                    <span>{{ $item->account->faccname }}</span>
                                @else
                                    <span class="text-gray-400">{{ $item->faccount ?: '-' }}</span>
                                @endif
                            </td>
                            <td class="border px-2 py-1 space-x-1.5 text-right whitespace-nowrap">
                                <a href="{{ route('typepembayaran.view', $item->ftypepembayaranid) }}">
                                    <button class="inline-flex items-center bg-slate-500 text-white px-3 py-1.5 text-xs rounded hover:bg-slate-600">
                                        <x-heroicon-o-pencil-square class="w-3.5 h-3.5 mr-1" /> View
                                    </button>
                                </a>
                                @if ($canEdit)
                                    <a href="{{ route('typepembayaran.edit', $item->ftypepembayaranid) }}">
                                        <button class="inline-flex items-center bg-yellow-500 text-white px-3 py-1.5 text-xs rounded hover:bg-yellow-600">
                                            <x-heroicon-o-pencil-square class="w-3.5 h-3.5 mr-1" /> Edit
                                        </button>
                                    </a>
                                @endif
                                @if ($canDelete)
                                    <a href="{{ route('typepembayaran.delete', $item->ftypepembayaranid) }}">
                                        <button class="inline-flex items-center bg-red-600 text-white px-3 py-1.5 text-xs rounded hover:bg-red-700">
                                            <x-heroicon-o-trash class="w-3.5 h-3.5 mr-1" /> Hapus
                                        </button>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
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

        #typePembayaranTable {
            width: auto !important;
            min-width: 100%;
        }

        #typePembayaranTable th,
        #typePembayaranTable td {
            text-align: left !important;
            vertical-align: middle;
        }

        #typePembayaranTable th:last-child,
        #typePembayaranTable td:last-child {
            text-align: right !important;
            white-space: nowrap;
        }

        #typePembayaranTable td:last-child {
            padding: .25rem .5rem;
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
        $(function () {
            const table = $('#typePembayaranTable').DataTable({
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
                ],
                language: {
                    lengthMenu: "Show _MENU_ entries",
                },
            });

            const $container = $(table.table().container());
            const $toolbarSearch = $container.find('.dt-search');
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
                this.setSelectionRange(start, end);
                table.search(this.value).draw();
            });
        });
    </script>
@endpush
