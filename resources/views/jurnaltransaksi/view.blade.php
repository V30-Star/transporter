@extends('layouts.app')

@section('title', $pageTitle ?? 'View Jurnal Transaksi')

@section('content')
    @php
        $totalDebit = collect($savedItems)->where('fdk', 'D')->sum(fn($item) => (float) ($item['famount'] ?? 0));
        $totalKredit = collect($savedItems)->where('fdk', 'K')->sum(fn($item) => (float) ($item['famount'] ?? 0));
    @endphp

    <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1800px] w-full mx-auto space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
            <div class="lg:col-span-4">
                <label class="block text-sm font-medium mb-1">Cabang</label>
                <input type="text" value="{{ $fbranchcode }}"
                    class="w-full border rounded px-3 py-2 bg-gray-100 cursor-not-allowed" disabled>
            </div>

            <div class="lg:col-span-4">
                <label class="block text-sm font-medium mb-1">No. Jurnal</label>
                <input type="text" value="{{ $jurnaltransaksi->fjurnalno }}"
                    class="w-full border rounded px-3 py-2 bg-gray-100 cursor-not-allowed" disabled>
            </div>

            <div class="lg:col-span-2">
                <label class="block text-sm font-medium mb-1">Tipe Jurnal</label>
                <select name="fjurnaltype" class="w-full border rounded px-3 py-2 bg-gray-100 cursor-not-allowed" disabled>
                    @foreach ($journalTypes as $type)
                        <option value="{{ $type->fmastercode }}" @selected(trim($jurnaltransaksi->fjurnaltype) === $type->fmastercode)>
                            {{ $type->fmastercode }} - {{ $type->fmastername }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-2">
                <label class="block text-sm font-medium mb-1">Tanggal</label>
                <input type="text" value="{{ \Carbon\Carbon::parse($jurnaltransaksi->fjurnaldate)->format('d/m/Y') }}"
                    class="w-full border rounded px-3 py-2 bg-gray-100 cursor-not-allowed" disabled>
            </div>

            <div class="lg:col-span-12">
                <label class="block text-sm font-medium mb-1">Keterangan Jurnal</label>
                <textarea rows="3" class="w-full border rounded px-3 py-2 bg-gray-100 cursor-not-allowed" disabled>{{ $jurnaltransaksi->fjurnalnote }}</textarea>
            </div>
        </div>

        <div class="space-y-2">
            <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                <h3 class="text-base font-semibold text-gray-800">Detail Jurnal</h3>
                <div class="flex flex-col gap-1 text-sm sm:flex-row sm:gap-6">
                    <span>Total Debit:
                        <strong class="text-blue-700">{{ number_format($totalDebit, 2, ',', '.') }}</strong>
                    </span>
                    <span>Total Kredit:
                        <strong class="text-green-700">{{ number_format($totalKredit, 2, ',', '.') }}</strong>
                    </span>
                </div>
            </div>

            <div class="overflow-auto border rounded">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-2 text-left w-12">#</th>
                            <th class="p-2 text-left w-44">Kode Account</th>
                            <th class="p-2 text-left w-56">Nama Account</th>
                            <th class="p-2 text-left w-56">Sub Account</th>
                            <th class="p-2 text-left w-48">Ref No</th>
                            <th class="p-2 text-left w-20">D/K</th>
                            <th class="p-2 text-left w-[28rem]">Keterangan</th>
                            <th class="p-2 text-right w-44">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($savedItems as $index => $item)
                            <tr class="border-t align-top">
                                <td class="p-2 text-gray-500">{{ $index + 1 }}</td>
                                <td class="p-2 text-gray-700 font-mono">{{ $item['faccount'] ?: '-' }}</td>
                                <td class="p-2">
                                    <div class="font-medium text-gray-800">{{ $item['faccname'] ?: '-' }}</div>
                                </td>
                                <td class="p-2 text-gray-700">{{ $item['fsubaccountname'] ?: '-' }}</td>
                                <td class="p-2 text-gray-600">{{ $item['frefno'] ?: '-' }}</td>
                                <td class="p-2">
                                    <span
                                        class="px-2 py-0.5 rounded text-xs font-semibold {{ ($item['fdk'] ?? '') === 'D' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }}">
                                        {{ ($item['fdk'] ?? '') === 'D' ? 'Debit' : 'Kredit' }}
                                    </span>
                                </td>
                                <td class="p-2 text-gray-700">{{ $item['faccountnote'] ?: '-' }}</td>
                                <td class="p-2 text-right font-medium">
                                    {{ number_format((float) ($item['famount'] ?? 0), 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-4 text-center text-gray-500">Detail jurnal belum ada.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-center gap-4">
            <a href="{{ route('jurnaltransaksi.print', ['fjurnalno' => $jurnaltransaksi->fjurnalno]) }}" target="_blank"
                class="inline-flex items-center bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                <x-heroicon-o-printer class="w-5 h-5 mr-2" />
                Print
            </a>
            <a href="{{ $indexUrl ?? route('jurnaltransaksi.index') }}"
                class="inline-flex items-center bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">
                <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                Kembali
            </a>
        </div>
    </div>
@endsection
