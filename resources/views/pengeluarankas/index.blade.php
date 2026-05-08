@extends('layouts.app')

@section('title', "Pengeluaran Kas")

@section('content')
        <div class="bg-white rounded shadow p-4">
        <div class="flex justify-end items-center mb-4">
            <div></div>
            <a href="{{ route('pengeluarankas.create') }}"
                class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                <x-heroicon-o-plus class="w-4 h-4 mr-1" /> {{ "Tambah Baru" }}
            </a>
        </div>

        <table id="pengeluaranKasTable" class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-2">{{ "Voucher No." }}</th>
                    <th class="border px-2 py-2">{{ "Tanggal" }}</th>
                    <th class="border px-2 py-2">{{ "No.Giro/Cek" }}</th>
                    <th class="border px-2 py-2">{{ "Account" }}</th>
                    <th class="border px-2 py-2">{{ "Keterangan" }}</th>
                    <th class="border px-2 py-2 text-right">{{ "Nilai Bayar" }}</th>
                    <th class="border px-2 py-2 no-sort">{{ "Aksi" }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($records as $record)
                    <tr>
                        <td class="border px-2 py-2">{{ $record->fkasmtno }}</td>
                        <td class="border px-2 py-2">
                            {{ optional($record->fkasmtdate)->format('d/m/Y') ?? \Carbon\Carbon::parse($record->fkasmtdate)->format('d/m/Y') }}
                        </td>
                        <td class="border px-2 py-2">{{ $record->fnogiro ?: '-' }}</td>
                        <td class="border px-2 py-2">{{ $record->account_summary }}</td>
                        <td class="border px-2 py-2">{{ $record->description_summary }}</td>
                        <td class="border px-2 py-2 text-right">{{ number_format((float) $record->payment_amount, 2, ',', '.') }}</td>
                        <td class="border px-2 py-2 text-center whitespace-nowrap">
                            <div class="flex items-center justify-center gap-2 flex-wrap">
                                <a href="{{ route('pengeluarankas.view', $record->fkasmtno) }}"
                                    class="inline-flex items-center bg-slate-500 text-white px-4 py-2 rounded hover:bg-slate-600">
                                    <x-heroicon-o-eye class="w-4 h-4 mr-1" /> {{ "View" }}
                                </a>
                                <a href="{{ route('pengeluarankas.edit', $record->fkasmtno) }}"
                                    class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                                    <x-heroicon-o-pencil-square class="w-4 h-4 mr-1" /> {{ "Edit" }}
                                </a>
                                <a href="{{ route('pengeluarankas.delete', $record->fkasmtno) }}"
                                    class="inline-flex items-center bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                                    <x-heroicon-o-trash class="w-4 h-4 mr-1" /> {{ "Hapus" }}
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.6/css/dataTables.dataTables.min.css">
    <style>
        #pengeluaranKasTable {
            width: 100% !important;
        }

        #pengeluaranKasTable th,
        #pengeluaranKasTable td {
            vertical-align: middle;
        }

        #pengeluaranKasTable th:last-child,
        #pengeluaranKasTable td:last-child {
            text-align: center;
            white-space: nowrap;
        }

        .dt-container .dt-search,
        .dt-container .dt-length {
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .dt-container .dt-search .dt-input,
        .dataTables_wrapper .dt-search .dt-input {
            width: 28rem !important;
            min-width: 28rem !important;
            max-width: 100%;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.6/js/dataTables.min.js"></script>
    <script>
        $(function() {
            $('#pengeluaranKasTable').DataTable({
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [
                    [1, 'desc'],
                    [0, 'desc']
                ],
                layout: {
                    topStart: 'search',
                    topEnd: 'pageLength',
                    bottomStart: 'info',
                    bottomEnd: 'paging',
                },
                columnDefs: [{
                        targets: 'no-sort',
                        orderable: false,
                        searchable: false
                    },
                    {
                        targets: 5,
                        className: 'text-right'
                    }
                ],
            });
        });
    </script>
@endpush

