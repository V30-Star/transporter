@extends('layouts.app')

@section('content')
    <div id="filterModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-black bg-opacity-50" onclick="toggleModal(false)"></div>
            <div class="relative bg-white rounded-xl shadow-2xl max-w-lg w-full p-6">
                <div class="flex justify-between items-center border-b pb-4 mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Filter Listing Order Pembelian</h3>
                    <button onclick="toggleModal(false)"
                        class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
                </div>

                <form method="GET" action="{{ route('listingpo.print') }}" target="_blank">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase mb-1">Dari Tanggal</label>
                                <input type="date" name="date_from" value="{{ date('Y-m-d') }}"
                                    class="w-full border rounded px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase mb-1">Sampai Tanggal</label>
                                <input type="date" name="date_to" value="{{ date('Y-m-d') }}"
                                    class="w-full border rounded px-3 py-2 text-sm">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase mb-1">Supplier From</label>
                                <select name="sup_from" class="select2 w-full">
                                    <option value="">-- All --</option>
                                    @foreach ($suppliers as $s)
                                        <option value="{{ $s->fsuppliercode }}">{{ $s->fsuppliercode }} -
                                            {{ $s->fsuppliername }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase mb-1">Supplier To</label>
                                <select name="sup_to" class="select2 w-full">
                                    <option value="">-- All --</option>
                                    @foreach ($suppliers as $s)
                                        <option value="{{ $s->fsuppliercode }}">{{ $s->fsuppliercode }} -
                                            {{ $s->fsuppliername }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">Urut Berdasarkan</label>
                            <select name="sort_by" class="w-full border rounded px-3 py-2 text-sm">
                                <option value="no">No. PO</option>
                                <option value="name">Tanggal PO</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 gap-2 bg-gray-50 p-3 rounded border">
                            <label class="flex items-center text-sm font-semibold">
                                <input type="checkbox" name="all_po" id="all_po" checked class="mr-2 w-4 h-4"> Semua PO
                            </label>
                            <label class="flex items-center text-sm font-semibold">
                                <input type="checkbox" name="only_pending" id="only_pending" class="mr-2 w-4 h-4"> Hanya PO
                                Belum Diterima Semua
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-bold rounded-lg shadow-md">🖨️
                            Cetak</button>
                        <button type="button" onclick="window.location.href='{{ route('dashboard') }}'"
                            class="px-5 py-2 bg-gray-100 text-gray-600 rounded-lg">Cancel</button>
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
            $('#all_po').change(function() {
                if ($(this).is(':checked')) $('#only_pending').prop('checked', false).prop('disabled',
                true);
                else $('#only_pending').prop('disabled', false);
            }).trigger('change');
        });
    </script>
@endsection
