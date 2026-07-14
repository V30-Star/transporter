@extends('layouts.app')

@section('title', $pageTitle ?? 'View Jurnal Transaksi')

@section('content')
    @php
        $totalDebit = collect($savedItems)->where('fdk', 'D')->sum(fn($item) => (float) ($item['famount'] ?? 0));
        $totalKredit = collect($savedItems)->where('fdk', 'K')->sum(fn($item) => (float) ($item['famount'] ?? 0));
    @endphp

    <div>
        <div class="border border-gray-200 rounded-xl bg-white p-6 mb-6">
<div class="flex items-center gap-2 px-4 pt-3 pb-0 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Identitas Jurnal</p>
                    </div>
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
            <div class="lg:col-span-4">
                <label class="block text-xs font-bold text-gray-600 mb-1">Cabang</label>
                <input type="text" value="{{ $fbranchcode }}"
                    class="w-full border-gray-300 rounded-lg px-3 py-2 bg-gray-100 cursor-not-allowed" disabled>
            </div>

            <div class="lg:col-span-4">
                <label class="block text-xs font-bold text-gray-600 mb-1">No. Jurnal</label>
                <input type="text" value="{{ $jurnaltransaksi->fjurnalno }}"
                    class="w-full border-gray-300 rounded-lg px-3 py-2 bg-gray-100 cursor-not-allowed" disabled>
            </div>

            <div class="lg:col-span-2">
                <label class="block text-xs font-bold text-gray-600 mb-1">Tipe Jurnal</label>
                <select name="fjurnaltype" class="w-full border-gray-300 rounded-lg px-3 py-2 bg-gray-100 cursor-not-allowed" disabled>
                    @foreach ($journalTypes as $type)
                        <option value="{{ $type->fmastercode }}" @selected(trim($jurnaltransaksi->fjurnaltype) === $type->fmastercode)>
                            {{ $type->fmastercode }} - {{ $type->fmastername }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-2">
                <label class="block text-xs font-bold text-gray-600 mb-1">Tanggal</label>
                <input type="text" value="{{ \Carbon\Carbon::parse($jurnaltransaksi->fjurnaldate)->format('d/m/Y') }}"
                    class="w-full border-gray-300 rounded-lg px-3 py-2 bg-gray-100 cursor-not-allowed" disabled>
            </div>

            <div class="lg:col-span-12">
                <label class="block text-xs font-bold text-gray-600 mb-1">Keterangan Jurnal</label>
                <textarea rows="3" class="w-full border-gray-300 rounded-lg px-3 py-2 bg-gray-100 cursor-not-allowed" disabled>{{ $jurnaltransaksi->fjurnalnote }}</textarea>
            </div>
        </div> {{-- end header grid --}}
        </div> {{-- end header card --}}

        <div class="border border-gray-200 rounded-xl bg-white p-6 mb-6">
 <div class="flex items-center gap-2 px-4 pt-3 pb-0 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Detail Jurnal</p>
                    </div>            <div class="flex items-center justify-between mb-4">
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
                <table class="pr-detail-table min-w-full text-sm balanced-detail-table"
                    data-skip-auto-detail-style="true">
                    <colgroup>
                        <col style="width:2%;">
                        <col style="width:12%;">
                        <col style="width:23%;">
                        <col style="width:18%;">
                        <col style="width:12%;">
                        <col style="width:8%;">
                        <col style="width:15%;">
                        <col style="width:10%;">
                    </colgroup>
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-2 text-left w-12">#</th>
                            <th class="p-2 text-left w-44">Kode Account</th>
                            <th class="p-2 text-left w-56">Nama Account</th>
                            <th class="p-2 text-left w-56">Sub Account</th>
                            <th class="p-2 text-left w-48">Ref No</th>
                            <th class="p-2 text-left w-20">D/K</th>
                            <th class="p-2 text-left w-[28rem]">Keterangan</th>
                            <th class="p-2 text-right w-44 whitespace-nowrap">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($savedItems as $index => $item)
                            <tr class="border-t align-top hover:bg-gray-55">
                                <td class="p-2 text-gray-400">{{ $index + 1 }}</td>
                                <td class="p-2">
                                    <div class="px-2 py-1 text-sm text-gray-655 bg-gray-50 border rounded font-mono">{{ $item['faccount'] ?: '-' }}</div>
                                </td>
                                <td class="p-2">
                                    <div class="px-2 py-1 text-sm text-gray-655 bg-gray-50 border rounded">{{ $item['faccname'] ?: '-' }}</div>
                                </td>
                                <td class="p-2">
                                    <div class="px-2 py-1 text-sm text-gray-655 bg-gray-50 border rounded">{{ $item['fsubaccountname'] ?: '-' }}</div>
                                </td>
                                <td class="p-2">
                                    <div class="px-2 py-1 text-sm text-gray-650 bg-gray-50 border rounded">{{ $item['frefno'] ?: '-' }}</div>
                                </td>
                                <td class="p-2 text-center">
                                    <div class="px-2 py-1 text-sm text-center border rounded font-medium {{ ($item['fdk'] ?? '') === 'D' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-green-50 text-green-700 border-green-200' }}">
                                        {{ ($item['fdk'] ?? '') === 'D' ? 'Debit' : 'Kredit' }}
                                    </div>
                                </td>
                                <td class="p-2">
                                    <div class="px-2 py-1 text-sm text-gray-655 bg-gray-50 border rounded">{{ $item['faccountnote'] ?: '-' }}</div>
                                </td>
                                <td class="p-2 text-right">
                                    <div class="px-2 py-1 text-sm text-gray-700 bg-gray-50 border rounded text-right font-medium">{{ number_format((float) ($item['famount'] ?? 0), 2, ',', '.') }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-3 text-center text-gray-500">Detail jurnal belum ada.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="border border-gray-200 rounded-xl bg-white p-6 mt-6">
            <div class="flex justify-end gap-3">
                <a href="{{ route('jurnaltransaksi.print', ['fjurnalno' => $jurnaltransaksi->fjurnalno]) }}" target="_blank"
                    class="inline-flex items-center bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <x-heroicon-o-printer class="w-5 h-5 mr-2" />
                    Print
                </a>
                <a href="{{ $indexUrl ?? route('jurnaltransaksi.index') }}"
                    class="inline-flex items-center bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                    Kembali
                </a>
            </div>
        </div>
    </div>
@endsection
