@extends('layouts.app')
@section('title', 'Otorisasi Komputer')

@section('content')
<div class="bg-white rounded shadow p-4 space-y-3">
    @if (session('success'))
        <div class="bg-green-50 text-green-700 border border-green-200 px-3 py-2 text-xs rounded">{{ session('success') }}</div>
    @endif

    <div class="overflow-x-auto">
        <table class="min-w-full border text-xs">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-1.5 text-left">User ID</th>
                    <th class="border px-2 py-1.5 text-left">Nama User</th>
                    <th class="border px-2 py-1.5 text-left">IP</th>
                    <th class="border px-2 py-1.5 text-left">Browser</th>
                    <th class="border px-2 py-1.5 text-left">Waktu Minta</th>
                    <th class="border px-2 py-1.5 text-center">Status</th>
                    <th class="border px-2 py-1.5 text-left">Disetujui Oleh</th>
                    <th class="border px-2 py-1.5 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($devices as $d)
                    <tr class="hover:bg-gray-50">
                        <td class="border px-2 py-1 font-semibold">{{ $d->fsysuserid }}</td>
                        <td class="border px-2 py-1">{{ $d->fname ?? '-' }}</td>
                        <td class="border px-2 py-1 font-mono">{{ $d->fip ?? '-' }}</td>
                        <td class="border px-2 py-1 text-gray-600 max-w-xs truncate" title="{{ $d->fuseragent }}">{{ $d->fuseragent ?? '-' }}</td>
                        <td class="border px-2 py-1 font-mono">{{ $d->fcreated ? \Carbon\Carbon::parse($d->fcreated)->format('d/m/y H:i') : '-' }}</td>
                        <td class="border px-2 py-1 text-center font-semibold {{ $d->fstatus === 'approved' ? 'text-green-600' : 'text-amber-600' }}">
                            {{ $d->fstatus === 'approved' ? 'Disetujui' : 'Menunggu' }}
                        </td>
                        <td class="border px-2 py-1">{{ $d->fapprovedby ?? '-' }}</td>
                        <td class="border px-2 py-1 text-center whitespace-nowrap">
                            @if ($d->fstatus !== 'approved')
                                <form method="POST" action="{{ route('userdevice.approve', $d->fdeviceid) }}" class="inline">
                                    @csrf
                                    <button class="bg-green-600 hover:bg-green-700 text-white px-2 py-0.5 rounded">Setujui</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('userdevice.reject', $d->fdeviceid) }}" class="inline"
                                onsubmit="return confirm('{{ $d->fstatus === 'approved' ? 'Cabut' : 'Tolak' }} komputer ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="bg-red-600 hover:bg-red-700 text-white px-2 py-0.5 rounded">{{ $d->fstatus === 'approved' ? 'Cabut' : 'Tolak' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-4 text-gray-400">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
