@extends('layouts.app')
@section('title', 'Dashboard Wewenang User')

@section('content')
<div class="bg-white rounded shadow p-4 space-y-3">

    <!-- Top Compact Header & Quick Actions -->
    <div class="flex flex-wrap items-center justify-between gap-2 pb-2 border-b border-gray-200 text-xs">
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 px-2.5 py-1 rounded border border-blue-100 font-medium">
                <i class="fa-solid fa-users text-[10px]"></i> Total User: <strong>{{ $totalUsers }}</strong>
            </span>
            <span class="inline-flex items-center gap-1 bg-green-50 text-green-700 px-2.5 py-1 rounded border border-green-100 font-medium">
                <i class="fa-solid fa-layer-group text-[10px]"></i> Total Baris Data: <strong>{{ count($rows) }}</strong>
            </span>
            <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-700 px-2.5 py-1 rounded border border-gray-200">
                Menu Terpilih: <strong>{{ $selectedMenuKey === 'ALL' ? 'Semua Menu' : $selectedMenuKey }}</strong>
            </span>
        </div>

        <div class="flex items-center gap-1.5">
            <a href="{{ route('dashboardwewenang.excel', request()->query()) }}"
                class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white px-2.5 py-1 text-xs rounded transition">
                <i class="fa-solid fa-file-excel mr-1"></i> Excel
            </a>
            <a href="{{ route('dashboardwewenang.print', request()->query()) }}" target="_blank"
                class="inline-flex items-center bg-gray-800 hover:bg-black text-white px-2.5 py-1 text-xs rounded transition">
                <i class="fa-solid fa-print mr-1"></i> Cetak / PDF
            </a>
        </div>
    </div>

    <!-- Filter Form -->
    <form method="GET" action="{{ route('dashboardwewenang.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-2 items-end text-xs">
        <!-- Parameter Menu Utama -->
        <div>
            <label for="menu" class="block font-semibold text-gray-700 mb-0.5">Parameter Menu</label>
            <select id="menu" name="menu" onchange="this.form.submit()"
                class="w-full border-gray-300 rounded px-2 py-1 text-xs font-medium focus:ring-1 focus:ring-blue-500 bg-blue-50/40">
                <option value="ALL" {{ $selectedMenuKey === 'ALL' ? 'selected' : '' }}>-- Semua Menu --</option>
                @php
                    $groupedMenus = collect($allMenus)->groupBy('group');
                @endphp
                @foreach ($groupedMenus as $grp => $items)
                    <optgroup label="📂 {{ $grp }}">
                        @foreach ($items as $mName => $mDef)
                            <option value="{{ $mName }}" {{ $selectedMenuKey === $mName ? 'selected' : '' }}>
                                {{ $mName }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>

        <!-- Filter User -->
        <div>
            <label for="user_id" class="block text-gray-600 mb-0.5">Filter User</label>
            <select id="user_id" name="user_id" onchange="this.form.submit()"
                class="w-full border-gray-300 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500">
                <option value="">-- Semua User --</option>
                @foreach ($users as $u)
                    <option value="{{ $u->fuid }}" {{ (string)$selectedUserId === (string)$u->fuid ? 'selected' : '' }}>
                        {{ $u->fsysuserid }} ({{ $u->fname ?? '-' }})
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Cari Nama/ID -->
        <div>
            <label for="search" class="block text-gray-600 mb-0.5">Cari User / ID</label>
            <input type="text" id="search" name="search" value="{{ $search }}" placeholder="Ketik ID atau nama..."
                class="w-full border-gray-300 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500">
        </div>

        <!-- Tombol Aksi Filter -->
        <div class="flex items-center gap-1.5">
            <button type="submit"
                class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 text-xs rounded transition flex-1">
                <i class="fa-solid fa-filter mr-1"></i> Filter
            </button>
            <a href="{{ route('dashboardwewenang.index') }}"
                class="inline-flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-gray-700 px-2.5 py-1 text-xs rounded transition">
                Reset
            </a>
        </div>
    </form>

    <!-- Table Wewenang User -->
    <div class="overflow-x-auto border-t border-gray-100 pt-2">
        <table id="wewenangTable" class="min-w-full border text-xs">
            <thead class="bg-gray-100 text-gray-700 font-semibold">
                <tr>
                    <th class="border px-2 py-1.5 text-center w-10">No</th>
                    <th class="border px-2 py-1.5 text-left w-24">Id</th>
                    <th class="border px-2 py-1.5 text-left">Nama Lengkap</th>
                    <th class="border px-2 py-1.5 text-left">List Menu</th>
                    <th class="border px-2 py-1.5 text-center w-14" title="Hak Akses / Lihat Menu">View</th>
                    <th class="border px-2 py-1.5 text-center w-14" title="Hak Tambah Data">Tambah</th>
                    <th class="border px-2 py-1.5 text-center w-14" title="Hak Edit Data">Edit</th>
                    <th class="border px-2 py-1.5 text-center w-14" title="Hak Hapus Data">Delete</th>
                    <th class="border px-2 py-1.5 text-center w-14" title="Hak Cetak / Listing / Laporan">Print</th>
                    <th class="border px-2 py-1.5 text-center w-14" title="Hak Approval Persetujuan">Approve</th>
                    <th class="border px-2 py-1.5 text-center w-16" title="Hak Khusus / Lain-lain">Lain-lain</th>
                    <th class="border px-2 py-1.5 text-center w-16">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $index => $row)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="border px-2 py-1 text-center font-mono text-gray-500">{{ $index + 1 }}</td>
                        <td class="border px-2 py-1 font-semibold text-gray-900">{{ $row['user_id'] }}</td>
                        <td class="border px-2 py-1 text-gray-800">{{ $row['user_name'] }}</td>
                        <td class="border px-2 py-1">
                            <span class="font-medium text-gray-900">{{ $row['menu_name'] }}</span>
                            <span class="text-[10px] text-gray-400 ml-1">({{ $row['menu_group'] }})</span>
                        </td>

                        <!-- View -->
                        <td class="border px-2 py-1 text-center">
                            @if ($row['can_view'])
                                <span class="text-green-600 font-bold text-sm" title="Diberikan">&#10003;</span>
                            @else
                                <span class="text-gray-300 text-xs">-</span>
                            @endif
                        </td>

                        <!-- Tambah -->
                        <td class="border px-2 py-1 text-center">
                            @if ($row['can_create'])
                                <span class="text-green-600 font-bold text-sm" title="Diberikan">&#10003;</span>
                            @else
                                <span class="text-gray-300 text-xs">-</span>
                            @endif
                        </td>

                        <!-- Edit -->
                        <td class="border px-2 py-1 text-center">
                            @if ($row['can_update'])
                                <span class="text-green-600 font-bold text-sm" title="Diberikan">&#10003;</span>
                            @else
                                <span class="text-gray-300 text-xs">-</span>
                            @endif
                        </td>

                        <!-- Delete -->
                        <td class="border px-2 py-1 text-center">
                            @if ($row['can_delete'])
                                <span class="text-green-600 font-bold text-sm" title="Diberikan">&#10003;</span>
                            @else
                                <span class="text-gray-300 text-xs">-</span>
                            @endif
                        </td>

                        <!-- Print -->
                        <td class="border px-2 py-1 text-center">
                            @if ($row['can_print'])
                                <span class="text-green-600 font-bold text-sm" title="Diberikan">&#10003;</span>
                            @else
                                <span class="text-gray-300 text-xs">-</span>
                            @endif
                        </td>

                        <!-- Approve -->
                        <td class="border px-2 py-1 text-center">
                            @if ($row['can_approve'])
                                <span class="text-green-600 font-bold text-sm" title="Diberikan">&#10003;</span>
                            @else
                                <span class="text-gray-300 text-xs">-</span>
                            @endif
                        </td>

                        <!-- Lain-lain -->
                        <td class="border px-2 py-1 text-center">
                            @if ($row['can_other'])
                                <span class="text-green-600 font-bold text-sm" title="Diberikan">&#10003;</span>
                            @else
                                <span class="text-gray-300 text-xs">-</span>
                            @endif
                        </td>

                        <!-- Aksi -->
                        <td class="border px-2 py-1 text-center whitespace-nowrap">
                            <a href="{{ route('roleaccess.index', $row['user_fuid']) }}"
                                class="text-blue-600 hover:text-blue-800 font-semibold text-[11px] underline"
                                title="Buka Set Menu User">
                                Set Menu
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center py-4 text-gray-400">Tidak ada data wewenang yang sesuai filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.6/css/dataTables.dataTables.min.css">
    <style>
        #wewenangTable th, #wewenangTable td {
            vertical-align: middle;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/2.1.6/js/dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            if ($('#wewenangTable tbody tr').length > 0 && !$('#wewenangTable tbody tr td[colspan]').length) {
                $('#wewenangTable').DataTable({
                    pageLength: 25,
                    lengthMenu: [10, 25, 50, 100],
                    order: [[1, 'asc'], [3, 'asc']],
                    language: {
                        search: "Cari:",
                        lengthMenu: "Baris: _MENU_",
                        info: "_START_-_END_ dari _TOTAL_",
                        infoEmpty: "0 data",
                        infoFiltered: "(dari _MAX_)",
                        paginate: {
                            first: "«",
                            last: "»",
                            next: "›",
                            previous: "‹"
                        }
                    }
                });
            }
        });
    </script>
@endpush
