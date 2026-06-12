@extends('layouts.app')

@section('title', 'Listing Jurnal')

@section('content')
    <div class="bg-white rounded shadow p-4 space-y-4">
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-xl font-semibold text-gray-800">Listing Jurnal</h1>
        </div>

        <form method="GET" action="{{ route('listingjurnal.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Dari</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}"
                    class="w-full border rounded px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Sampai</label>
                <input type="date" name="date_to" value="{{ $dateTo }}"
                    class="w-full border rounded px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jurnal Type</label>
                <select name="journal_types[]" multiple size="4" class="w-full border rounded px-3 py-2 text-sm">
                    @foreach ($typeOptions as $type)
                        <option value="{{ $type->fmastercode }}" @selected(in_array($type->fmastercode, $selectedTypes, true))>
                            {{ $type->fmastercode }} - {{ $type->fmastername }}
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">Kosong = semua type.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Urut Berdasarkan</label>
                <select name="sort_by" class="w-full border rounded px-3 py-2 text-sm">
                    @foreach ($sortOptions as $value => $label)
                        <option value="{{ $value }}" @selected($sortBy === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 text-sm">
                    Tampilkan
                </button>
                <a href="{{ route('listingjurnal.index') }}" class="px-4 py-2 rounded bg-gray-100 text-gray-700 hover:bg-gray-200 text-sm">
                    Reset
                </a>
            </div>
        </form>

        <div class="overflow-x-auto border rounded">
            <table class="min-w-full text-xs border-collapse">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="border px-2 py-1 text-left">No.Jurnal</th>
                        <th class="border px-2 py-1 text-left">Tanggal</th>
                        <th class="border px-2 py-1 text-left">Type</th>
                        <th class="border px-2 py-1 text-left">Note</th>
                        <th class="border px-2 py-1 text-right">Balance</th>
                        <th class="border px-2 py-1 text-right">Balance Rp</th>
                        <th class="border px-2 py-1 text-left">User</th>
                        <th class="border px-2 py-1 text-right">Line</th>
                        <th class="border px-2 py-1 text-left">Account</th>
                        <th class="border px-2 py-1 text-left">Account Name</th>
                        <th class="border px-2 py-1 text-left">Ref No</th>
                        <th class="border px-2 py-1 text-left">Sub Account</th>
                        <th class="border px-2 py-1 text-left">Project</th>
                        <th class="border px-2 py-1 text-center">D/K</th>
                        <th class="border px-2 py-1 text-right">Rate</th>
                        <th class="border px-2 py-1 text-right">Amount</th>
                        <th class="border px-2 py-1 text-right">Amount Rp</th>
                        <th class="border px-2 py-1 text-left">Account Note</th>
                        <th class="border px-2 py-1 text-right">Debet</th>
                        <th class="border px-2 py-1 text-right">Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="border px-2 py-1 whitespace-nowrap">{{ $row->fjurnalno }}</td>
                            <td class="border px-2 py-1 whitespace-nowrap">{{ optional(\Carbon\Carbon::parse($row->fjurnaldate))->format('d/m/Y') }}</td>
                            <td class="border px-2 py-1 whitespace-nowrap">{{ $row->fjurnaltype }}</td>
                            <td class="border px-2 py-1 min-w-48">{{ $row->fjurnalnote }}</td>
                            <td class="border px-2 py-1 text-right">{{ number_format((float) $row->fbalance, 2) }}</td>
                            <td class="border px-2 py-1 text-right">{{ number_format((float) $row->fbalance_rp, 2) }}</td>
                            <td class="border px-2 py-1 whitespace-nowrap">{{ $row->fuserid }}</td>
                            <td class="border px-2 py-1 text-right">{{ $row->flineno }}</td>
                            <td class="border px-2 py-1 whitespace-nowrap">{{ $row->faccount }}</td>
                            <td class="border px-2 py-1 min-w-40">{{ $row->faccname }}</td>
                            <td class="border px-2 py-1 whitespace-nowrap">{{ $row->frefno }}</td>
                            <td class="border px-2 py-1 whitespace-nowrap">{{ $row->fsubaccount }}</td>
                            <td class="border px-2 py-1 whitespace-nowrap">{{ $row->fproject }}</td>
                            <td class="border px-2 py-1 text-center">{{ $row->fdk }}</td>
                            <td class="border px-2 py-1 text-right">{{ number_format((float) $row->frate, 2) }}</td>
                            <td class="border px-2 py-1 text-right">{{ number_format((float) $row->famount, 2) }}</td>
                            <td class="border px-2 py-1 text-right">{{ number_format((float) $row->famount_rp, 2) }}</td>
                            <td class="border px-2 py-1 min-w-48">{{ $row->faccountnote }}</td>
                            <td class="border px-2 py-1 text-right">{{ $row->debet !== null ? number_format((float) $row->debet, 2) : '' }}</td>
                            <td class="border px-2 py-1 text-right">{{ $row->kredit !== null ? number_format((float) $row->kredit, 2) : '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="20" class="border px-3 py-6 text-center text-gray-500">Data tidak ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $rows->links() }}
        </div>
    </div>
@endsection
