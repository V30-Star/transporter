@extends('layouts.app')

@section('title', 'Rekap Penjualan Sales By Customer')

@section('content')
<div id="filterModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="toggleModal(false)"></div>
        <div class="relative bg-white rounded-xl shadow-2xl max-w-2xl w-full p-6">
            <div class="flex justify-between items-center border-b pb-4 mb-4">
                <h3 class="text-xl font-bold text-gray-800">Rekap Penjualan Sales By Customer</h3>
                <button onclick="toggleModal(false)" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
            </div>

            <form id="reportForm" method="GET" action="{{ route('reportingrekappenjualansalescustomer.print') }}" target="_blank">
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-xs font-bold uppercase">Cabang</label>
                            @if ($isAuthorized)
                                <div class="flex gap-2">
                                    <button type="button" onclick="selectAllBranches(true)" class="text-xs bg-blue-100 text-blue-700 px-3 py-1 rounded">Select All</button>
                                    <button type="button" onclick="selectAllBranches(false)" class="text-xs bg-gray-100 text-gray-700 px-3 py-1 rounded">Unselect All</button>
                                </div>
                            @endif
                        </div>
                        <div id="branchCheckboxesArea" class="border rounded-lg p-3 bg-gray-50 max-h-40 overflow-y-auto">
                            <div class="grid grid-cols-2 gap-2">
                                @foreach ($branches as $branch)
                                    @php $checked = $isAuthorized || $userBranchCode === $branch->fcabangkode; @endphp
                                    <label class="flex items-center text-sm cursor-pointer select-none">
                                        @if (!$isAuthorized && $checked)
                                            <input type="hidden" name="branch_codes[]" value="{{ $branch->fcabangkode }}">
                                        @endif
                                        <input type="checkbox" name="branch_codes[]" value="{{ $branch->fcabangkode }}" class="branch-checkbox mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4" {{ $checked ? 'checked' : '' }} {{ !$isAuthorized ? 'disabled' : '' }}>
                                        <span class="text-gray-700 font-medium">{{ $branch->fcabangkode }} - {{ $branch->fcabangname }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">Tanggal Dari</label>
                            <input type="date" name="date_from" value="{{ date('Y-m-01') }}" required class="w-full border rounded px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">Sd</label>
                            <input type="date" name="date_to" value="{{ date('Y-m-d') }}" required class="w-full border rounded px-3 py-2 text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">Customer Dari</label>
                            <select name="customer_from" class="select2 w-full">
                                <option value="">-- Semua --</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->fcustomercode }}">{{ $customer->fcustomername }} ({{ $customer->fcustomercode }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">Customer Sampai</label>
                            <select name="customer_to" class="select2 w-full">
                                <option value="">-- Semua --</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->fcustomercode }}">{{ $customer->fcustomername }} ({{ $customer->fcustomercode }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase mb-1">Salesman</label>
                        <select name="salesman_id" class="select2 w-full">
                            <option value="">-- Semua Salesman --</option>
                            @foreach ($salesmans as $salesman)
                                <option value="{{ $salesman->fsalesmancode }}">{{ $salesman->fsalesmancode }} - {{ $salesman->fsalesmanname }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                    <button type="submit" onclick="setAction('{{ route('reportingrekappenjualansalescustomer.print') }}')" class="px-5 py-2 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700">Preview</button>
                    <button type="submit" onclick="setAction('{{ route('reportingrekappenjualansalescustomer.print') }}')" class="px-5 py-2 bg-slate-800 text-white font-bold rounded-lg hover:bg-slate-900">Print / Cetak</button>
                    <button type="submit" onclick="setAction('{{ route('reportingrekappenjualansalescustomer.excel') }}')" class="px-5 py-2 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700">Export Excel</button>
                    <button type="reset" class="px-5 py-2 bg-gray-100 text-gray-600 rounded-lg">Keluar/Reset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.select2').select2({ width: '100%', allowClear: true });
        toggleModal(true);
    });
    function toggleModal(show) { $('#filterModal').toggleClass('hidden', !show); }
    function selectAllBranches(status) {
        document.querySelectorAll('#branchCheckboxesArea .branch-checkbox').forEach(checkbox => checkbox.checked = status);
    }
    function setAction(action) {
        document.getElementById('reportForm').action = action;
    }
</script>
@endsection
