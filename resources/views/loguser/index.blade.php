@extends('layouts.app')
@section('title', 'Log User Login')

@section('content')
<div class="bg-white rounded shadow p-4 space-y-3">

    <!-- Compact Stat Summary Badges -->
    <div class="flex flex-wrap items-center justify-between gap-2 pb-2 border-b border-gray-200 text-xs">
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-1.5 bg-blue-50 text-blue-700 px-2.5 py-1 rounded border border-blue-100 font-medium">
                <i class="fa-solid fa-arrow-right-to-bracket text-[10px]"></i> Total Login: <strong>{{ number_format($totalLogin) }}</strong>
            </span>
            <span class="inline-flex items-center gap-1.5 bg-green-50 text-green-700 px-2.5 py-1 rounded border border-green-100 font-medium">
                <span class="h-2 w-2 rounded-full bg-green-500 animate-pulse"></span> Online: <strong>{{ number_format($onlineNow) }}</strong>
            </span>
            <span class="inline-flex items-center gap-1.5 bg-purple-50 text-purple-700 px-2.5 py-1 rounded border border-purple-100 font-medium">
                <i class="fa-solid fa-users text-[10px]"></i> User Unik: <strong>{{ number_format($uniqueUsers) }}</strong>
            </span>
            <span class="inline-flex items-center gap-1.5 bg-amber-50 text-amber-700 px-2.5 py-1 rounded border border-amber-100 font-medium">
                <i class="fa-solid fa-calendar-day text-[10px]"></i> Hari Ini: <strong>{{ number_format($todayLogin) }}</strong>
            </span>
        </div>

        <div class="flex items-center gap-1.5">
            <a href="{{ route('loguser.excel', request()->query()) }}"
                class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white px-2.5 py-1 text-xs rounded transition">
                <i class="fa-solid fa-file-excel mr-1"></i> Excel
            </a>
            <a href="{{ route('loguser.print', request()->query()) }}" target="_blank"
                class="inline-flex items-center bg-gray-800 hover:bg-black text-white px-2.5 py-1 text-xs rounded transition">
                <i class="fa-solid fa-print mr-1"></i> Cetak / PDF
            </a>
        </div>
    </div>

    <!-- Compact Filter Form -->
    <form method="GET" action="{{ route('loguser.index') }}" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2 items-end text-xs">
        <div>
            <label for="date_from" class="block text-gray-600 mb-0.5">Dari</label>
            <input type="date" id="date_from" name="date_from" value="{{ $dateFrom }}"
                class="w-full border-gray-300 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500">
        </div>

        <div>
            <label for="date_to" class="block text-gray-600 mb-0.5">Sampai</label>
            <input type="date" id="date_to" name="date_to" value="{{ $dateTo }}"
                class="w-full border-gray-300 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500">
        </div>

        <div>
            <label for="user" class="block text-gray-600 mb-0.5">User</label>
            <select id="user" name="user" class="w-full border-gray-300 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500">
                <option value="">-- Semua --</option>
                @foreach ($users as $u)
                    <option value="{{ $u->fsysuserid }}" {{ $userId === $u->fsysuserid ? 'selected' : '' }}>
                        {{ $u->fsysuserid }} ({{ $u->fname ?? '-' }})
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="status" class="block text-gray-600 mb-0.5">Status</label>
            <select id="status" name="status" class="w-full border-gray-300 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500">
                <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Semua</option>
                <option value="online" {{ $status === 'online' ? 'selected' : '' }}>Online</option>
                <option value="offline" {{ $status === 'offline' ? 'selected' : '' }}>Logout</option>
            </select>
        </div>

        <div class="col-span-2 flex items-center gap-1.5">
            <button type="submit"
                class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 text-xs rounded transition flex-1 sm:flex-none">
                <i class="fa-solid fa-filter mr-1"></i> Filter
            </button>
            <a href="{{ route('loguser.index') }}"
                class="inline-flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-gray-700 px-2.5 py-1 text-xs rounded transition">
                Reset
            </a>
        </div>
    </form>

    <!-- Compact Data Table -->
    <div class="overflow-x-auto border-t border-gray-100 pt-2">
        <table id="logUserTable" class="min-w-full border text-xs">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-1.5 text-center w-10">No</th>
                    <th class="border px-2 py-1.5 text-left">User ID</th>
                    <th class="border px-2 py-1.5 text-left">Nama User</th>
                    <th class="border px-2 py-1.5 text-left">IP</th>
                    <th class="border px-2 py-1.5 text-left">Komputer</th>
                    <th class="border px-2 py-1.5 text-left">Login</th>
                    <th class="border px-2 py-1.5 text-left">Logout</th>
                    <th class="border px-2 py-1.5 text-left">Durasi</th>
                    <th class="border px-2 py-1.5 text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $index => $log)
                    @php
                        $loginAt = $log->login_date ? \Carbon\Carbon::parse($log->login_date) : null;
                        $logoutAt = $log->log_out_date ? \Carbon\Carbon::parse($log->log_out_date) : null;
                        $isOnline = is_null($log->log_out_date);

                        $duration = '-';
                        if ($loginAt && $logoutAt) {
                            $duration = $loginAt->diffForHumans($logoutAt, ['syntax' => \Carbon\Carbon::DIFF_ABSOLUTE, 'parts' => 2]);
                        } elseif ($loginAt && !$logoutAt) {
                            $duration = $loginAt->diffForHumans(now(), ['syntax' => \Carbon\Carbon::DIFF_ABSOLUTE, 'parts' => 2]);
                        }
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="border px-2 py-1 text-center font-mono text-gray-500">{{ $index + 1 }}</td>
                        <td class="border px-2 py-1 font-semibold text-gray-900">{{ $log->akun }}</td>
                        <td class="border px-2 py-1">{{ $log->fname ?? '-' }}</td>
                        <td class="border px-2 py-1 font-mono text-gray-600">{{ $log->ip ?? '-' }}</td>
                        <td class="border px-2 py-1 text-gray-600">{{ $log->komp ?? '-' }}</td>
                        <td class="border px-2 py-1 font-mono">
                            {{ $loginAt ? $loginAt->format('d/m/y H:i') : '-' }}
                        </td>
                        <td class="border px-2 py-1 font-mono">
                            {{ $logoutAt ? $logoutAt->format('d/m/y H:i') : '-' }}
                        </td>
                        <td class="border px-2 py-1 text-gray-600">{{ $duration }}</td>
                        <td class="border px-2 py-1 text-center font-semibold text-[11px]">
                            @if ($isOnline)
                                <span class="text-green-600 inline-flex items-center gap-1">
                                    <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span> Online
                                </span>
                            @else
                                <span class="text-gray-500">Logout</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-gray-400">Tidak ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.6/css/dataTables.dataTables.min.css">
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/2.1.6/js/dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            if ($('#logUserTable tbody tr').length > 0 && !$('#logUserTable tbody tr td[colspan]').length) {
                $('#logUserTable').DataTable({
                    pageLength: 25,
                    lengthMenu: [10, 25, 50, 100],
                    order: [[5, 'desc']],
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
