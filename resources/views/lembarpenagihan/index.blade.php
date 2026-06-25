@extends('layouts.app')

@section('title', 'Lembar Penagihan')

@section('content')
    <div class="bg-white rounded shadow p-4">
        <div class="flex justify-end mb-4">
            @if ($canCreate)
                <a href="{{ route('lembarpenagihan.create') }}" class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    <x-heroicon-o-plus class="w-4 h-4 mr-1" /> Tambah Baru
                </a>
            @endif
        </div>

        <table id="tagihanTable" class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-1">Tagihan#</th>
                    <th class="border px-2 py-1">Tanggal</th>
                    <th class="border px-2 py-1">No.Faktur</th>
                    <th class="border px-2 py-1">Nama Customer</th>
                    <th class="border px-2 py-1 text-right">Total Tagihan</th>
                    <th class="border px-2 py-1">Keterangan</th>
                    <th class="border px-2 py-1 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.6/css/dataTables.dataTables.min.css">
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.6/js/dataTables.min.js"></script>
    <script>
        $(function() {
            $('#tagihanTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('lembarpenagihan.index') }}',
                columns: [
                    { data: 'ftagihanno' },
                    { data: 'ftagihandate' },
                    { data: 'invoice_refs' },
                    { data: 'fcustomername' },
                    { data: 'famounttagihan', className: 'text-right', render: data => Number(data || 0).toLocaleString('id-ID', { minimumFractionDigits: 2 }) },
                    { data: 'fnote' },
                    { data: 'actions', orderable: false, searchable: false, className: 'text-right' }
                ],
                order: [[1, 'desc']]
            });
        });
    </script>
@endpush
