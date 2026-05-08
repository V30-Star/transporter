@extends('layouts.app')

@section('content')
    <div id="filterModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-black bg-opacity-50" onclick="toggleModal(false)"></div>
            <div class="relative bg-white rounded-xl shadow-2xl max-w-lg w-full p-6">
                <div class="flex justify-between items-center border-b pb-4 mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Filter {{ $pageTitle }}</h3>
                    <button onclick="toggleModal(false)"
                        class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
                </div>

                <form method="GET" action="{{ $printRoute }}" target="_blank">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase mb-1">Dari Tanggal</label>
                                <input type="date" name="filter_date_from" value="{{ $filterDateFrom }}"
                                    class="w-full border rounded px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase mb-1">Sampai Tanggal</label>
                                <input type="date" name="filter_date_to" value="{{ $filterDateTo }}"
                                    class="w-full border rounded px-3 py-2 text-sm">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">No. Account</label>
                            <select name="filter_account" class="select2 w-full">
                                <option value="">-- All --</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->faccount }}"
                                        {{ $filterAccount === (string) $account->faccount ? 'selected' : '' }}>
                                        {{ $account->faccount }} - {{ $account->faccname }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="bg-gray-50 p-3 rounded border">
                            <label class="flex items-center text-sm font-semibold">
                                <input type="checkbox" name="only_giro_mundur" value="1"
                                    {{ $onlyGiroMundur ? 'checked' : '' }} class="mr-2 w-4 h-4">
                                Hanya Giro Mundur
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                        <a href="{{ $resetRoute }}"
                            class="px-5 py-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200">Reset</a>
                        <button type="submit"
                            class="px-6 py-2 bg-blue-600 text-white font-bold rounded-lg shadow-md hover:bg-blue-700">
                            Cetak
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        function toggleModal(show) {
            $('#filterModal').toggleClass('hidden', !show);
        }

        $(document).ready(function() {
            $('.select2').select2({
                width: '100%'
            });
            toggleModal(true);
        });
    </script>
@endsection
