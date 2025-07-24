@extends('layouts.app')

@section('title', 'Master Rute')

@section('content')
<div class="bg-white rounded shadow p-4">
    <!-- Search + Order -->
    <div class="flex justify-between items-center mb-4">
        <div class="flex items-center space-x-2">
            <label class="font-semibold">Search:</label>
            <input type="text" class="border rounded px-2 py-1" placeholder="Cari...">
        </div>
        <div>
            <select class="border rounded px-2 py-1">
                <option>Filter by: Kode Rute</option>
            </select>
        </div>
    </div>

    <!-- Table -->
    <table class="min-w-full border text-sm">
        <thead class="bg-gray-100">
            <tr>
                <th class="border px-2 py-1">Kode Rute</th>
                <th class="border px-2 py-1">Nama Rute</th>
                <th class="border px-2 py-1">Max ETD (hari)</th>
                <th class="border px-2 py-1">Uang Jalan (Rp)</th>
                <th class="border px-2 py-1">Status</th>
            </tr>
        </thead>
        <tbody>
            @for($i = 1; $i <= 10; $i++)
            <tr class="hover:bg-gray-50">
                <td class="border px-2 py-1">R{{ str_pad($i, 3, '0', STR_PAD_LEFT) }}</td>
                <td class="border px-2 py-1">Rute {{ $i }}</td>
                <td class="border px-2 py-1">{{ rand(1, 5) }}</td>
                <td class="border px-2 py-1">Rp {{ number_format(rand(100000, 300000), 0, ',', '.') }}</td>
                <td class="border px-2 py-1">
                    <span class="px-2 py-1 rounded text-xs {{ $i % 2 === 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $i % 2 === 0 ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </td>
            </tr>
            @endfor
        </tbody>
    </table>

    <!-- Bottom Actions -->
    <div class="mt-4 flex justify-between items-center">
        <!-- Left Buttons -->
        <div class="space-x-2">
            <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Baru</button>
            <button class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">Edit</button>
            <button class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Hapus</button>
        </div>

        <!-- Pagination -->
        <div class="flex items-center space-x-2">
            <button class="px-3 py-1 rounded border hover:bg-gray-100 disabled:opacity-50" disabled>&larr;</button>
            <span class="text-sm">Page 1 of 5</span>
            <button class="px-3 py-1 rounded border hover:bg-gray-100">&rarr;</button>
        </div>
    </div>
</div>
@endsection
