@extends('layouts.app')

@section('title', 'Bayar Supplier')

@section('content')
    <div class="bg-white rounded shadow p-4">
        <div class="flex justify-end items-center mb-4">
            <a href="{{ route('bayarsupplier.create') }}"
                class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                <x-heroicon-o-plus class="w-4 h-4 mr-1" /> {{ 'Tambah Baru' }}
            </a>
        </div>

        <table id="bayarSupplierTable" class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-2">No.Voucher</th>
                    <th class="border px-2 py-2">Tanggal</th>
                    <th class="border px-2 py-2">Account</th>
                    <th class="border px-2 py-2">No.Giro</th>
                    <th class="border px-2 py-2">No.PBL</th>
                    <th class="border px-2 py-2">Nama Supplier</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($records as $record)
                    <tr>
                        <td class="border px-2 py-2">{{ $record->fkasmtno }}</td>
                        <td class="border px-2 py-2">{{ \Carbon\Carbon::parse($record->fkasmtdate)->format('d/m/Y') }}</td>
                        <td class="border px-2 py-2">{{ $record->account_summary }}</td>
                        <td class="border px-2 py-2">{{ $record->fnogiro ?: '-' }}</td>
                        <td class="border px-2 py-2">{{ $record->pbl_summary }}</td>
                        <td class="border px-2 py-2">{{ $record->supplier_name }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.6/css/dataTables.dataTables.min.css">
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/2.1.6/js/dataTables.min.js"></script>
    <script>
        $(function() {
            $('#bayarSupplierTable').DataTable({
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [[1, 'desc']]
            });
        });
    </script>
@endpush
