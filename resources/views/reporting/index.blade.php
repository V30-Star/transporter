@extends('layouts.app')

@section('title', 'Listing Order Pembelian / PO')

@section('content')
    <div class="p-6 bg-white shadow-md rounded-lg">
        <h2 class="text-xl font-bold mb-4">Listing Order Pembelian / PO</h2>

        <div class="flex flex-wrap items-center gap-4 mb-6">
            {{-- Tombol Pemicu Modal --}}
            <button onclick="toggleModal(true)"
                style="padding: 6px 16px; background-color: #3b82f6; color: white; font-size: 0.875rem; border-radius: 0.25rem; display: inline-flex; align-items: center;"
                class="hover:bg-blue-600 transition-colors"> Search Data
            </button>
            {{-- Tombol Export Excel di Kanan --}}
            <div class="flex gap-2 ml-auto">
                @php
                    // Ambil semua parameter query saat ini (termasuk filter)
                    $exportUrl = route('reporting.exportExcel', request()->query());
                @endphp
                <a href="{{ $exportUrl }}"
                    class="px-4 py-1.5 bg-green-600 text-white text-sm rounded hover:bg-green-700 transition-colors flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L10 11.586l2.293-2.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                            clip-rule="evenodd" />
                    </svg>
                    Export Excel
                </a>
            </div>
        </div>

        {{-- --- MODAL FILTER POP-UP --- --}}
        <div id="filterModal" class="fixed inset-0 z-50 bg-black bg-opacity-50 hidden flex items-center justify-center">
            <div class="bg-white rounded-lg shadow-2xl max-w-xl w-full p-6" onclick="event.stopPropagation()">
                <div class="flex justify-between items-center border-b pb-3 mb-4">
                    <h3 class="text-lg font-semibold">Filter Listing Order Pembelian / PO</h3>
                    <button onclick="toggleModal(false)"
                        class="text-gray-500 hover:text-gray-800 text-xl font-bold">&times;</button>
                </div>

                <form method="GET" action="{{ route('reporting.index') }}">
                    <div class="grid grid-cols-2 gap-4">
                        {{-- Filter Tanggal Dari --}}
                        <div>
                            <label for="modal_filter_date_from" class="block text-sm font-medium text-gray-700">Tanggal
                                Dari</label>
                            <input type="date" name="filter_date_from" id="modal_filter_date_from"
                                value="{{ request('filter_date_from') }}"
                                class="mt-1 block w-full border rounded px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        {{-- Filter Tanggal Sampai --}}
                        <div>
                            <label for="modal_filter_date_to" class="block text-sm font-medium text-gray-700">Tanggal
                                Sampai</label>
                            <input type="date" name="filter_date_to" id="modal_filter_date_to"
                                value="{{ request('filter_date_to') }}"
                                class="mt-1 block w-full border rounded px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        {{-- Filter Supplier --}}
                        <div class="col-span-2">
                            <label for="modal_filter_supplier_id"
                                class="block text-sm font-medium text-gray-700">Supplier</label>
                            <select name="filter_supplier_id" id="modal_filter_supplier_id"
                                class="mt-1 block w-full border rounded px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">-- Semua Supplier --</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->fsupplierid }}"
                                        {{ request('filter_supplier_id') == $supplier->fsupplierid ? 'selected' : '' }}>
                                        {{ $supplier->fsuppliername }} ({{ $supplier->fsupplierid }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-2 mt-6">
                        {{-- Tombol Reset --}}
                        <a href="{{ route('reporting.index') }}"
                            class="px-4 py-2 bg-gray-300 text-gray-800 text-sm rounded hover:bg-gray-400 transition-colors">
                            Reset Filter
                        </a>
                        {{-- Tombol Terapkan Filter --}}
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition-colors">
                            Terapkan Filter
                        </button>
                    </div>
                </form>
            </div>


        </div>
        {{-- --- END MODAL FILTER POP-UP --- --}}

        <p class="mt-8 text-gray-700">
            @php
                // Ambil semua parameter query yang ada di URL saat ini
                $printUrl = route('reporting.printPoh', request()->query());
            @endphp
            <a href="{{ $printUrl }}" target="_blank"
                class="px-3 py-1 bg-gray-200 text-sm rounded hover:bg-gray-300 inline-flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-1" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2z" />
                </svg>
                Cetak Laporan Master-Detail
            </a>
        </p>

        {{-- --- BAGIAN TABEL DATA TR_POH (HEADER) DIKEMBALIKAN --- --}}
        <div class="mt-6 overflow-x-auto">
            <table id="pohReportTable" class="min-w-full border text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border px-2 py-2">ID PO</th>
                        <th class="border px-2 py-2">Nomor PO</th>
                        <th class="border px-2 py-2">Tanggal PO</th>
                        <th class="border px-2 py-2">Supplier</th>
                        <th class="border px-2 py-2">Mata Uang</th>
                        <th class="border px-2 py-2 text-right">Total PO</th>
                        <th class="border px-2 py-2">Status Close</th>
                        <th class="border px-2 py-2">Status Approval</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pohData as $data)
                        <tr class="hover:bg-gray-50">
                            <td class="border px-2 py-1">{{ $data->fpohdid }}</td>
                            <td class="border px-2 py-1">{{ $data->fpono }}</td>
                            <td class="border px-2 py-1">{{ \Carbon\Carbon::parse($data->fpodate)->format('d-m-Y') }}</td>
                            <td class="border px-2 py-1">{{ $data->supplier->fsuppliername ?? 'N/A' }}</td>
                            <td class="border px-2 py-1">{{ $data->fcurrency }}</td>
                            <td class="border px-2 py-1 text-right">
                                {{ number_format($data->famountpo, 2, ',', '.') }}
                            </td>
                            <td class="border px-2 py-1 text-center">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs 
                        {{ $data->fclose === '1' ? 'bg-red-200 text-red-700' : 'bg-green-100 text-green-700' }}">
                                    {{ $data->fclose === '1' ? 'Closed' : 'Open' }}
                                </span>
                            </td>
                            <td class="border px-2 py-1 text-center">
                                {{ $data->fapproval ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">Tidak ada data Purchase Order Header yang
                                ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{-- --- BAGIAN TABEL DATA TR_POH SELESAI --- --}}
    </div>


@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.6/css/dataTables.dataTables.min.css">
    {{-- Menghilangkan elemen non-esensial saat mencetak --}}
    <style>
        @media print {

            /* Sembunyikan tombol Export dan tombol Filter di hasil cetakan /
                                                        .flex.gap-2.ml-auto,
                                                        .flex.gap-2:has(button[type="submit"]) {
                                                        display: none !important;
                                                        }
                                                        / Pastikan form filter terlihat rapi */
            .bg-gray-50 {
                background-color: #ffffff !important;
                border: none !important;
            }
        }
    </style>
@endpush

@push('scripts')
    {{-- jQuery + DataTables JS (CDN) --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.6/js/dataTables.min.js"></script>
    <script>
        // Fungsi untuk mengontrol modal
        function toggleModal(show) {
            const modal = document.getElementById('filterModal');
            if (show) {
                modal.classList.remove('hidden');
            } else {
                modal.classList.add('hidden');
            }
        }
        $(function() {
            // Inisialisasi DataTables
            $('#pohReportTable').DataTable({
                // Konfigurasi minimal untuk DataTables
                autoWidth: false,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [
                    [2, 'desc'] // Urutkan berdasarkan Tanggal PO (kolom index 2) secara descending
                ],
                // Gunakan layout DataTables 2.x
                layout: {
                    topStart: 'pageLength',
                    topEnd: 'search',
                    bottomStart: 'info',
                    bottomEnd: 'paging'
                },
            });
        });
    </script>
@endpush
