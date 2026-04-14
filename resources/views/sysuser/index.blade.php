@extends('layouts.app')
@section('title', 'Master Wewenang User')

@section('content')
    <div class="bg-white rounded shadow p-4">

        @php
            $canCreate = in_array('createSysuser', explode(',', session('user_restricted_permissions', '')));
            $canEdit = in_array('updateSysuser', explode(',', session('user_restricted_permissions', '')));
            $canDelete = in_array('deleteSysuser', explode(',', session('user_restricted_permissions', '')));
            $canRoleAccess = in_array('roleaccess', explode(',', session('user_restricted_permissions', '')));
            $showActionsColumn = $canEdit || $canDelete || $canRoleAccess;
        @endphp

        <div class="flex justify-end items-center mb-4">
            @if ($canCreate)
                <a href="{{ route('sysuser.create') }}"
                    class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    <x-heroicon-o-plus class="w-4 h-4 mr-1" /> Tambah Baru
                </a>
            @endif
        </div>

        <table id="sysuserTable" class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-2">Cabang</th>
                    <th class="border px-2 py-2">User Id</th>
                    <th class="border px-2 py-2">Nama User</th>
                    <th class="border px-2 py-2">Salesman</th>
                    @if ($showActionsColumn)
                        <th class="border px-2 py-2">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody id="tableBody">
                @forelse ($sysusers as $sysuser)
                    <tr class="hover:bg-gray-50">
                        <td>{{ $sysuser->fcabang }}</td>
                        <td>{{ $sysuser->fsysuserid }}</td>
                        <td>{{ $sysuser->fname }}</td>
                        <td>{{ $sysuser->salesman_name }}</td>
                        @if ($showActionsColumn)
                            <td class="border px-2 py-1">
                                @if ($canEdit)
                                    <a href="{{ route('sysuser.edit', $sysuser->fuid) }}">
                                        <button
                                            class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                                            <x-heroicon-o-pencil-square class="w-4 h-4 mr-1" />
                                            Edit
                                        </button>
                                    </a>
                                @endif

                                @if ($canDelete)
                                    <a href="{{ route('sysuser.delete', $sysuser->fuid) }}">
                                        <button
                                            class="inline-flex items-center bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                                            <x-heroicon-o-trash class="w-4 h-4 mr-1" />
                                            Hapus
                                        </button>
                                    </a>
                                @endif

                                @if ($canRoleAccess)
                                    <a href="{{ route('roleaccess.index', $sysuser->fuid) }}">
                                        <button
                                            class="inline-flex items-center bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                                            <x-heroicon-o-key class="w-4 h-4 mr-1" />
                                            Set Menu
                                        </button>
                                    </a>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4">Tidak ada data.</td>
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
        #sysuserTable {
            width: 100% !important;
        }

        #sysuserTable th,
        #sysuserTable td {
            text-align: left !important;
            vertical-align: middle;
        }

        /* Kolom Aksi: jangan mepet, tapi tetap ringkas */
        #sysuserTable th:last-child,
        #sysuserTable td:last-child {
            white-space: nowrap;
            text-align: center;
        }

        #sysuserTable td:last-child {
            padding: .25rem .5rem;
        }

        .btn-aksi {
            padding: .25rem .5rem;
            font-size: .825rem;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.6/js/dataTables.min.js"></script>
    <script>
        $(function() {
            $('#sysuserTable').DataTable({
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
                    targets: -1,
                    orderable: false,
                    searchable: false,
                    width: 120
                }],
                initComplete: function() {
                    const api = this.api();
                    const $len = $(api.table().container()).find('.dt-length .dt-input');
                    $len.addClass('focus:outline-none focus:ring focus:ring-blue-100');

                    const $search = $(api.table().container()).find('.dt-search .dt-input');
                    $search.css({
                        width: '400px',
                        'max-width': '100%'
                    });
                }
            });
        });
    </script>
@endpush
