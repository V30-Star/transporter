@extends('layouts.app')

@section('title', 'Master Currency')

@section('content')
    <div x-data="currencyTable()" class="bg-white rounded shadow p-4">

        @php
            $canCreate = in_array('createCurrency', explode(',', session('user_restricted_permissions', '')));
            $canEdit = in_array('updateCurrency', explode(',', session('user_restricted_permissions', '')));
            $canDelete = in_array('deleteCurrency', explode(',', session('user_restricted_permissions', '')));
            $showActionsColumn = $canEdit || $canDelete;
        @endphp

        <div class="flex justify-end items-center mb-4">
            <div></div>

            {{-- @if ($canCreate) --}}
            <a href="{{ route('currency.create') }}"
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

        {{-- Table --}}
        <table id="currencyTable" class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-2">Kode Currency</th>
                    <th class="border px-2 py-2">Nama Currency</th>
                    <th class="border px-2 py-2 no-sort">Status</th>
                    {{-- @if ($showActionsColumn) --}}
                    <th class="border px-2 py-2 col-aksi">Aksi</th>
                    {{-- @endif --}}
                </tr>
            </thead>
            <tbody id="tableBody">
                @forelse($currencys as $item)
                    <tr class="hover:bg-gray-50">
                        <td>{{ $item->fcurrcode }}</td>
                        <td>{{ $item->fcurrname }}</td>
                        <td>
                            @php $isActive = (string)$item->fnonactive === '0'; @endphp
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs {{ $isActive ? 'bg-green-100 text-green-700' : 'bg-red-200 text-red-700' }}">
                                {{ $isActive ? 'Active' : 'Non Active' }}
                            </span>
                        </td>
                        {{-- @if ($showActionsColumn) --}}
                        <td class="border px-2 py-1 space-x-2">
                            {{-- @if ($canEdit) --}}
                            <a href="{{ route('currency.view', $item->fcurrid) }}">
                                <button
                                    class="inline-flex items-center bg-slate-500 text-white px-4 py-2 rounded hover:bg-slate-600">
                                    <x-heroicon-o-pencil-square class="w-4 h-4 mr-1" /> View
                                </button>
                            </a>
                            {{-- @endif --}}
                            {{-- @if ($canEdit) --}}
                            <a href="{{ route('currency.edit', $item->fcurrid) }}">
                                <button
                                    class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                                    <x-heroicon-o-pencil-square class="w-4 h-4 mr-1" /> Edit
                                </button>
                            </a>
                            {{-- @endif --}}
                            {{-- @if ($canDelete) --}}
                            <a href="{{ route('currency.delete', $item->fcurrid) }}">
                                <button
                                    class="inline-flex items-center bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                                    <x-heroicon-o-trash class="w-4 h-4 mr-1" />
                                    Hapus
                                </button>
                            </a>
                            {{-- @endif --}}
                        </td>
                        {{-- @endif --}}
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $showActionsColumn ? 3 : 2 }}" class="text-center py-4">Tidak ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

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
        #currencyTable {
            width: 100% !important;
        }

        #currencyTable th,
        #currencyTable td {
            text-align: left !important;
            vertical-align: middle;
        }

        /* Kolom Aksi: jangan mepet, tapi tetap ringkas */
        #currencyTable th:last-child,
        #currencyTable td:last-child {
            white-space: nowrap;
            text-align: center;
        }

        #currencyTable td:last-child {
            padding: .25rem .5rem;
        }

        .btn-aksi {
            padding: .25rem .5rem;
            font-size: .825rem;
        }

        #currencyTable th,
        #currencyTable td {
            text-align: left !important;
            vertical-align: middle;
        }

        #currencyTable th:last-child,
        #currencyTable td:last-child {
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
            // Inisialisasi DataTables
            const hasActions = {{ $showActionsColumn ? 'true' : 'false' }};
            const columns = hasActions ? [{
                    title: 'Kode Currency'
                },
                {
                    title: 'Nama Currency'
                },
                {
                    title: 'Status'
                },
                {
                    title: 'Aksi',
                    orderable: false,
                    searchable: false
                }
            ] : [{
                    title: 'Kode Currency'
                },
                {
                    title: 'Nama Currency'
                },
                {
                    title: 'Status'
                }
            ];

            $('#currencyTable').DataTable({
                autoWidth: false,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [
                    [0, 'asc']
                ],
                layout: {
                    topStart: 'search',
                    topEnd: 'pageLength',
                    bottomStart: 'info',
                    bottomEnd: 'paging'
                },
                columnDefs: [{
                        targets: 'col-aksi',
                        orderable: false,
                        searchable: false,
                        width: 120
                    },
                    {
                        targets: 'no-sort',
                        orderable: false
                    }
                ],
                language: {
                    lengthMenu: "Show _MENU_ entries"
                },

            });
        });
    </script>
@endpush
