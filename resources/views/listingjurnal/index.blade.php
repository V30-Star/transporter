@extends('layouts.app')

@section('title', 'Listing Jurnal Transaksi')

@section('content')
<div id="filterModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="toggleModal(false)"></div>
        <div class="relative bg-white rounded-xl shadow-2xl max-w-2xl w-full p-6 overflow-y-auto max-h-[90vh]">
            <h3 class="text-xl font-bold text-gray-800 border-b pb-3 mb-5">Listing Jurnal Transaksi</h3>

            <form method="GET" action="{{ route('listingjurnal.print') }}" target="_blank">
                <div class="space-y-4">
                    {{-- Cabang / Branch --}}
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider">Cabang / Branch</label>
                            @if ($isAuthorized)
                                <div class="flex space-x-2">
                                    <button type="button" onclick="selectAllCheckboxes('branchCheckboxesArea', true)"
                                        class="text-xs bg-blue-100 text-blue-700 px-3 py-1 rounded hover:bg-blue-200">Select All</button>
                                    <button type="button" onclick="selectAllCheckboxes('branchCheckboxesArea', false)"
                                        class="text-xs bg-gray-100 text-gray-700 px-3 py-1 rounded hover:bg-gray-200">Unselect All</button>
                                </div>
                            @endif
                        </div>
                        <div id="branchCheckboxesArea" class="border rounded-lg p-3 bg-gray-50 max-h-40 overflow-y-auto">
                            <div class="grid grid-cols-2 gap-2">
                                @foreach($branches as $branch)
                                    @php $isChecked = $isAuthorized || ($userBranchCode === $branch->fcabangkode); @endphp
                                    <label class="flex items-center text-sm cursor-pointer select-none">
                                        @if (!$isAuthorized && $userBranchCode === $branch->fcabangkode)
                                            <input type="hidden" name="branch_codes[]" value="{{ $branch->fcabangkode }}">
                                        @endif
                                        <input type="checkbox" name="branch_codes[]" value="{{ $branch->fcabangkode }}"
                                            class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4"
                                            {{ $isChecked ? 'checked' : '' }} {{ !$isAuthorized ? 'disabled' : '' }}>
                                        <span class="text-gray-700 font-medium">{{ $branch->fcabangkode }} - {{ $branch->fcabangname }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Tanggal --}}
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Periode</p>
                        <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Dari Tanggal</label>
                            <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Sampai Tanggal</label>
                            <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                        </div>
                        </div>
                    </div>

                    {{-- Jurnal Type --}}
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider">Jurnal Type</label>
                            <div class="flex space-x-2">
                                <button type="button" onclick="selectAllCheckboxes('journalTypeCheckboxesArea', true)"
                                    class="text-xs bg-blue-100 text-blue-700 px-3 py-1 rounded hover:bg-blue-200">Select All</button>
                                <button type="button" onclick="selectAllCheckboxes('journalTypeCheckboxesArea', false)"
                                    class="text-xs bg-gray-100 text-gray-700 px-3 py-1 rounded hover:bg-gray-200">Unselect All</button>
                            </div>
                        </div>
                        <div id="journalTypeCheckboxesArea" class="border rounded-lg p-3 bg-gray-50">
                            <div class="grid grid-cols-2 gap-2">
                                @foreach($typeOptions as $type)
                                    <label class="flex items-center text-sm cursor-pointer select-none">
                                        <input type="checkbox" name="journal_types[]" value="{{ $type->fmastercode }}"
                                            class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4" checked>
                                        <span class="text-gray-700 font-medium">{{ $type->fmastercode }} - {{ $type->fmastername }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Urut Berdasarkan --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Urut Berdasarkan</label>
                        <select name="sort_by" id="sort_by" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                            @foreach($sortOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t mt-6">
                    <button type="button" onclick="window.location.href='{{ route('dashboard') }}'" class="px-5 py-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200">Cancel</button>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700">Cetak</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        toggleModal(true);
    });

    function selectAllCheckboxes(areaId, status) {
        document.querySelectorAll(`#${areaId} input[type="checkbox"]:not(:disabled)`).forEach(checkbox => {
            checkbox.checked = status;
        });
    }

    function toggleModal(show) {
        if (!show) {
            window.location.href = "{{ route('dashboard') }}";
        } else {
            $('#filterModal').removeClass('hidden');
        }
    }
</script>
@endsection
