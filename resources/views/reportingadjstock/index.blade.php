@extends('layouts.app')

@section('title', 'Laporan Adjustment Stock')

@section('content')
    <div class="p-6 bg-white shadow-md rounded-lg">
        <h2 class="text-xl font-bold mb-4">Laporan Adjustment Stock</h2>

        <div class="flex flex-wrap items-center gap-4 mb-6">
            {{-- Tombol Pemicu Modal --}}
            <button onclick="toggleModal(true)"
                style="padding: 6px 16px; background-color: #3b82f6; color: white; font-size: 0.875rem; border-radius: 0.25rem; display: inline-flex; align-items: center;"
                class="hover:bg-blue-600 transition-colors"> Search Data
            </button>
        </div>

        {{-- --- MODAL FILTER POP-UP --- --}}
        <div id="filterModal" class="fixed inset-0 z-50 bg-black bg-opacity-50 hidden flex items-center justify-center">
            <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full p-6" onclick="event.stopPropagation()">
                <div class="flex justify-between items-center border-b pb-3 mb-4">
                    <h3 class="text-lg font-semibold">Laporan Adjustment Stock</h3>
                    <button onclick="toggleModal(false)"
                        class="text-gray-500 hover:text-gray-800 text-xl font-bold">&times;</button>
                </div>

                <form method="GET" action="{{ route('reportingadjstock.printAdjStock') }}">
                    <div class="grid grid-cols-2 gap-4">
                        {{-- Cabang / Branch checkboxes --}}
                        <div class="col-span-2">
                            <div class="flex justify-between items-center mb-2">
                                <label class="block text-sm font-medium text-gray-700">Cabang / Branch</label>
                                @if ($isAuthorized)
                                    <div class="flex space-x-2">
                                        <button type="button" onclick="selectAllBranches(true)"
                                            class="text-xs bg-blue-100 text-blue-700 px-3 py-1 rounded hover:bg-blue-200">
                                            Select All
                                        </button>
                                        <button type="button" onclick="selectAllBranches(false)"
                                            class="text-xs bg-gray-100 text-gray-700 px-3 py-1 rounded hover:bg-gray-200">
                                            Unselect All
                                        </button>
                                    </div>
                                @endif
                            </div>
                            <div id="branchCheckboxesArea" class="border rounded-lg p-3 bg-gray-50 max-h-40 overflow-y-auto">
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach ($branches as $b)
                                        @php
                                            $isChecked = $isAuthorized || ($userBranchCode === $b->fcabangkode);
                                        @endphp
                                        <label class="flex items-center text-sm cursor-pointer select-none">
                                            @if (!$isAuthorized && $userBranchCode === $b->fcabangkode)
                                                <input type="hidden" name="branch_codes[]" value="{{ $b->fcabangkode }}">
                                            @endif
                                            <input type="checkbox" name="branch_codes[]" value="{{ $b->fcabangkode }}"
                                                class="branch-checkbox mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4"
                                                {{ $isChecked ? 'checked' : '' }}
                                                {{ !$isAuthorized ? 'disabled' : '' }}>
                                            <span class="text-gray-700 font-medium">{{ $b->fcabangkode }} - {{ $b->fcabangname }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Filter Tanggal Dari --}}
                        <div>
                            <label for="modal_filter_date_from" class="block text-sm font-medium text-gray-700">Tanggal
                                Dari</label>
                            <input type="date" name="filter_date_from" id="modal_filter_date_from"
                                value="{{ $filterDateFrom }}"
                                class="mt-1 block w-full border rounded px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        {{-- Filter Tanggal Sampai --}}
                        <div>
                            <label for="modal_filter_date_to" class="block text-sm font-medium text-gray-700">Tanggal
                                Sampai</label>
                            <input type="date" name="filter_date_to" id="modal_filter_date_to"
                                value="{{ $filterDateTo }}"
                                class="mt-1 block w-full border rounded px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        {{-- Filter Gudang --}}
                        <div class="col-span-2">
                            <label class="block text-sm font-medium mb-1">Gudang</label>
                            <div class="flex">
                                <div class="relative flex-1" for="modal_filter_warehouse_id">
                                    <select id="modal_filter_warehouse_id" name="filter_warehouse_id"
                                        class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
                                        disabled>
                                        <option value=""></option>
                                        @foreach ($warehouses as $warehouse)
                                            <option value="{{ $warehouse->fwhcode }}"
                                                {{ $filterWarehouseId == $warehouse->fwhcode ? 'selected' : '' }}>
                                                {{ $warehouse->fwhcode }} - {{ $warehouse->fwhname }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-0" role="button" aria-label="Browse gudang"
                                        @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open'))"></div>
                                </div>
                                <input type="hidden" name="filter_warehouse_id" id="warehouseCodeHidden"
                                    value="{{ $filterWarehouseId }}">
                                <button type="button"
                                    @click="window.dispatchEvent(new CustomEvent('warehouse-browse-open'))"
                                    class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                                    title="Browse Gudang">
                                    <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                </button>
                            </div>
                            @error('filter_warehouse_id')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex justify-end space-x-2 mt-6">
                        {{-- Tombol Reset --}}
                        <a href="{{ route('reportingadjstock.index') }}"
                            class="px-4 py-2 bg-gray-300 text-gray-800 text-sm rounded hover:bg-gray-400 transition-colors">
                            Reset
                        </a>
                        {{-- Tombol Terapkan Filter --}}
                        <button type="submit" formaction="{{ route('reportingadjstock.printAdjStock') }}" formtarget="_blank"
                            class="px-4 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition-colors">
                            Preview & Print
                        </button>
                    </div>
                </form>
            </div>

        </div>
        {{-- --- END MODAL FILTER POP-UP --- --}}
    </div>

    <x-transaction.browse-warehouse-modal />

@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
@endpush
<x-transaction.datatables-length-styles :tables="['warehouseTable']" />
@push('scripts')
    {{-- jQuery + DataTables JS (CDN) --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    @include('components.transaction.browse-warehouse-script', [
        'routeName' => 'wh.browse',
        'branchScope' => $isAuthorized ? 'all' : null,
    ])
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

        function selectAllBranches(status) {
            document.querySelectorAll('#branchCheckboxesArea .branch-checkbox').forEach(checkbox => {
                checkbox.checked = status;
            });
        }

        $(function() {
            // Tampilkan modal otomatis jika belum ada filter
            @if (!$hasFilter)
                toggleModal(true);
            @endif

            // Hanya inisialisasi DataTables jika ada data (bukan placeholder)
            @if ($hasFilter && $pohData->count() > 0)
                $('#pohReportTable').DataTable({
                    autoWidth: false,
                    pageLength: 10,
                    lengthMenu: [10, 25, 50, 100],
                    order: [
                        [2, 'desc'] // Urutkan berdasarkan Tanggal PO (kolom index 2) secara descending
                    ],
                    layout: {
                        topStart: 'pageLength',
                        topEnd: 'search',
                        bottomStart: 'info',
                        bottomEnd: 'paging'
                    },
                });
            @endif
        });

    </script>
@endpush
