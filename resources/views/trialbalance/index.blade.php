@extends('layouts.app')

@section('title', 'Trial Balance')

@section('content')
    <div id="filterModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="window.location.href='{{ route('dashboard') }}'"></div>
        <div class="relative bg-white rounded-xl shadow-2xl max-w-xl w-full p-6 m-4 z-10">
            <div class="flex justify-between items-center border-b pb-4 mb-4">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-scale-unbalanced-flip text-blue-600 text-xl"></i>
                    <h3 class="text-xl font-bold text-gray-800">Laporan Trial Balance</h3>
                </div>
                <button type="button" onclick="window.location.href='{{ route('dashboard') }}'"
                    class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
            </div>

            @if ($errors->any())
                <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-lg">
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="trialBalanceForm" method="GET" action="{{ route('trialbalance.print') }}" target="_blank">
                <div class="space-y-4">
                    {{-- Periode Inputs --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">
                                Periode Dari <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="period_from" id="period_from"
                                value="{{ old('period_from', $currentYm) }}"
                                placeholder="YYYYMM (cth: 202601)" maxlength="6" pattern="[0-9]{6}" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <span class="text-[10px] text-gray-500">Format: YYYYMM</span>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">
                                Periode Sampai <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="period_to" id="period_to"
                                value="{{ old('period_to', $currentYm) }}"
                                placeholder="YYYYMM (cth: 202601)" maxlength="6" pattern="[0-9]{6}" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <span class="text-[10px] text-gray-500">Format: YYYYMM</span>
                        </div>
                    </div>

                    {{-- Account Range Inputs --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Account Dari</label>
                            <select name="account_from" id="account_from" class="select2 w-full">
                                <option value="">-- Semua / Awal --</option>
                                @foreach ($accounts as $acc)
                                    <option value="{{ $acc->faccount }}" {{ old('account_from') == $acc->faccount ? 'selected' : '' }}>
                                        {{ $acc->faccount }} - {{ $acc->faccname }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Account Sampai</label>
                            <select name="account_to" id="account_to" class="select2 w-full">
                                <option value="">-- Semua / Akhir --</option>
                                @foreach ($accounts as $acc)
                                    <option value="{{ $acc->faccount }}" {{ old('account_to') == $acc->faccount ? 'selected' : '' }}>
                                        {{ $acc->faccount }} - {{ $acc->faccname }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-2 mt-6 pt-4 border-t">
                    <button type="button" onclick="window.location.href='{{ route('dashboard') }}'"
                        class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
                        Keluar
                    </button>
                    <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white text-sm font-bold rounded-lg hover:bg-blue-700 transition shadow-sm flex items-center gap-2">
                        <i class="fa-solid fa-print"></i> Preview / Cetak
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('.select2').select2({
                width: '100%',
                placeholder: function() {
                    return $(this).data('placeholder');
                },
                allowClear: true
            });
            $('#account_from').on('change', function() {
                $('#account_to').val($(this).val()).trigger('change');
            });
            $('#period_from').on('input change', function() {
                $('#period_to').val($(this).val());
            });
        });
    </script>
@endsection
