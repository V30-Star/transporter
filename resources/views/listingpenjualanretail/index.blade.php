@extends('layouts.app')

@section('title', 'Listing Penjualan Retail')

@section('content')
    <div id="filterModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-black bg-opacity-50" onclick="toggleModal(false)"></div>
            <div class="relative bg-white rounded-xl shadow-2xl max-w-3xl w-full p-6">
                <div class="flex justify-between items-center border-b pb-4 mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Listing Penjualan Retail</h3>
                    <button onclick="toggleModal(false)"
                        class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
                </div>

                <form method="GET" action="{{ route('listingpenjualanretail.print') }}" target="_blank">
                    <div class="space-y-4">
                        {{-- Cabang checkboxes --}}
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <label class="block text-xs font-bold uppercase">Cabang</label>
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
                            <div id="branchCheckboxesArea"
                                class="border rounded-lg p-3 bg-gray-50 max-h-40 overflow-y-auto">
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach ($branches as $b)
                                        @php
                                            $isChecked = $isAuthorized || $userBranchCode === $b->fcabangkode;
                                        @endphp
                                        <label class="flex items-center text-sm cursor-pointer select-none">
                                            @if (!$isAuthorized && $userBranchCode === $b->fcabangkode)
                                                <input type="hidden" name="branch_codes[]" value="{{ $b->fcabangkode }}">
                                            @endif
                                            <input type="checkbox" name="branch_codes[]" value="{{ $b->fcabangkode }}"
                                                class="branch-checkbox mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4"
                                                {{ $isChecked ? 'checked' : '' }} {{ !$isAuthorized ? 'disabled' : '' }}>
                                            <span class="text-gray-700 font-medium">{{ $b->fcabangkode }} -
                                                {{ $b->fcabangname }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2">
                                <label class="block text-xs font-bold uppercase mb-1">Tanggal</label>
                                <div class="flex items-center gap-2">
                                    <input type="date" name="date_from" value="{{ now()->subDays(7)->format('Y-m-d') }}"
                                        class="flex-1 border rounded px-2 py-2 text-sm">
                                    <span class="text-xs text-gray-500 whitespace-nowrap">s/d</span>
                                    <input type="date" name="date_to" value="{{ now()->format('Y-m-d') }}"
                                        class="flex-1 border rounded px-2 py-2 text-sm">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase mb-1 text-gray-700">Pembayaran</label>
                                <select name="fpembayaran" class="w-full border rounded px-3 py-2 text-sm">
                                    <option value="">-- Semua Pembayaran --</option>
                                    @foreach ($typePembayarans as $tp)
                                        <option value="{{ $tp }}">{{ $tp }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase mb-1 text-gray-700">Kasir</label>
                                <input type="text" name="kasir" class="w-full border rounded px-3 py-2 text-sm" placeholder="Cari kasir...">
                            </div>
                        </div>

                        <!-- Options Row (Inline) -->
                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                            <div class="flex gap-6">
                                <label class="flex items-center text-sm font-semibold text-blue-700 cursor-pointer select-none">
                                    <input type="radio" name="display_type" value="detail" checked class="mr-2 w-4 h-4 text-blue-600 focus:ring-blue-500"> DETAIL
                                </label>
                                <label class="flex items-center text-sm font-semibold text-blue-700 cursor-pointer select-none">
                                    <input type="radio" name="display_type" value="rekap" class="mr-2 w-4 h-4 text-blue-600 focus:ring-blue-500"> REKAP
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                        <button type="submit"
                            class="px-6 py-2 bg-blue-600 text-white font-bold rounded-lg shadow-md hover:bg-blue-700">🖨️
                            Cetak</button>
                        <button type="button" onclick="window.location.href='{{ route('dashboard') }}'"
                            class="px-5 py-2 bg-gray-100 text-gray-600 rounded-lg">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleModal(show) {
            document.getElementById('filterModal').classList.toggle('hidden', !show);
        }

        function selectAllBranches(status) {
            document.querySelectorAll('#branchCheckboxesArea .branch-checkbox').forEach(checkbox => {
                checkbox.checked = status;
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            toggleModal(true);
        });
    </script>
@endsection
