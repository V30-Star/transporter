@extends('layouts.app')

@section('title', '')

@section('content')
    <div id="filterModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-black bg-opacity-50" onclick="toggleModal(false)"></div>
            <div class="relative bg-white rounded-xl shadow-2xl max-w-2xl w-full p-6">
                <div class="flex justify-between items-center border-b pb-4 mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Analisa Umur Piutang</h3>
                    <button onclick="toggleModal(false)"
                        class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
                </div>

                <form method="GET" action="{{ route('analisaumurpiutang.print') }}" target="_blank">
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <label class="block text-xs font-bold uppercase">Cabang</label>
                                @if ($isAuthorized)
                                    <div class="flex space-x-2">
                                        <button type="button" onclick="selectAllBranches(true)"
                                            class="text-xs bg-blue-100 text-blue-700 px-3 py-1 rounded hover:bg-blue-200">Select
                                            All</button>
                                        <button type="button" onclick="selectAllBranches(false)"
                                            class="text-xs bg-gray-100 text-gray-700 px-3 py-1 rounded hover:bg-gray-200">Unselect
                                            All</button>
                                    </div>
                                @endif
                            </div>
                            <div id="branchCheckboxesArea"
                                class="border rounded-lg p-3 bg-gray-50 max-h-40 overflow-y-auto">
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach ($branches as $branch)
                                        @php $checked = $isAuthorized || $userBranchCode === $branch->fcabangkode; @endphp
                                        <label class="flex items-center text-sm cursor-pointer select-none">
                                            @if (!$isAuthorized && $checked)
                                                <input type="hidden" name="branch_codes[]"
                                                    value="{{ $branch->fcabangkode }}">
                                            @endif
                                            <input type="checkbox" name="branch_codes[]" value="{{ $branch->fcabangkode }}"
                                                class="branch-checkbox mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4"
                                                {{ $checked ? 'checked' : '' }} {{ !$isAuthorized ? 'disabled' : '' }}>
                                            <span class="text-gray-700 font-medium">{{ $branch->fcabangkode }} -
                                                {{ $branch->fcabangname }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase mb-1">Tanggal Dari</label>
                                <input type="date" name="date_from" value="{{ date('Y-m-01') }}"
                                    class="w-full border rounded px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase mb-1">Sampai</label>
                                <input type="date" name="date_to" value="{{ date('Y-m-d') }}"
                                    class="w-full border rounded px-3 py-2 text-sm">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">Salesman</label>
                            <select name="salesman" class="select2 w-full">
                                <option value="">-- Semua Salesman --</option>
                                @foreach ($salesmans as $salesman)
                                    <option value="{{ $salesman->fsalesmancode }}">{{ $salesman->fsalesmancode }} -
                                        {{ $salesman->fsalesmanname }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase mb-1">Customer Dari</label>
                                <select name="cust_from" class="select2 w-full">
                                    <option value="">-- Awal --</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->fcustomercode }}">{{ $customer->fcustomercode }} -
                                            {{ $customer->fcustomername }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase mb-1">Sampai</label>
                                <select name="cust_to" class="select2 w-full">
                                    <option value="">-- Akhir --</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->fcustomercode }}">{{ $customer->fcustomercode }} -
                                            {{ $customer->fcustomername }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 bg-gray-50 p-3 rounded border">
                            <label class="flex items-center text-sm font-semibold cursor-pointer">
                                <input type="radio" name="mode" value="all" checked onchange="toggleDueDate()"
                                    class="mr-2 w-4 h-4 text-blue-600 focus:ring-blue-500"> Semua
                            </label>
                            <label class="flex items-center text-sm font-semibold cursor-pointer">
                                <input type="radio" name="mode" value="due" onchange="toggleDueDate()"
                                    class="mr-2 w-4 h-4 text-blue-600 focus:ring-blue-500"> Tanggal Jatuh Tempo s.d
                            </label>
                        </div>

                        <div>
                            <input type="date" name="due_date_to" id="due_date_to" value="{{ date('Y-m-d') }}"
                                class="w-full border rounded px-3 py-2 text-sm" disabled>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                        <button type="submit"
                            class="px-6 py-2 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700">Cetak</button>
                        <button type="button" onclick="window.location.href='{{ route('dashboard') }}'"
                            class="px-5 py-2 bg-gray-100 text-gray-600 rounded-lg">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('.select2').select2({
                width: '100%'
            });
            toggleModal(true);
            toggleDueDate();
        });

        function toggleModal(show) {
            $('#filterModal').toggleClass('hidden', !show);
        }

        function selectAllBranches(status) {
            document.querySelectorAll('#branchCheckboxesArea .branch-checkbox').forEach(checkbox => {
                checkbox.checked = status;
            });
        }

        function toggleDueDate() {
            document.getElementById('due_date_to').disabled = document.querySelector('[name="mode"]:checked')?.value !==
                'due';
        }
    </script>
@endsection
