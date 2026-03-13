@extends('layouts.app')

@section('content')
    <div id="filterModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-black bg-opacity-50" onclick="toggleModal(false)"></div>
            <div class="relative bg-white rounded-xl shadow-2xl max-w-2xl w-full p-6">
                <div class="flex justify-between items-center border-b pb-4 mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Filter Listing Penjualan</h3>
                    <button onclick="toggleModal(false)" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
                </div>

                <form method="GET" action="{{ route('listingpenjualan.print') }}" target="_blank">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase mb-1">Dari Tanggal</label>
                                <input type="date" name="date_from" value="{{ date('Y-m-01') }}" class="w-full border rounded px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase mb-1">Sampai Tanggal</label>
                                <input type="date" name="date_to" value="{{ date('Y-m-d') }}" class="w-full border rounded px-3 py-2 text-sm">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase mb-1">Customer From</label>
                                <input type="text" name="cust_from" class="w-full border rounded px-3 py-2 text-sm" placeholder="Mulai...">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase mb-1">Customer To</label>
                                <input type="text" name="cust_to" class="w-full border rounded px-3 py-2 text-sm" placeholder="Sampai...">
                            </div>
                        </div>

                       <div class="grid grid-cols-3 gap-4">
    <div>
        <label class="block text-xs font-bold uppercase mb-1 text-gray-700">Group Produk</label>
        <select name="group_code" class="select2 w-full border rounded px-3 py-2 text-sm">
            <option value="">-- Semua Group --</option>
            @foreach($groups as $g) 
                <option value="{{ $g->fgroupcode }}">{{ $g->fgroupname }}</option> 
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-xs font-bold uppercase mb-1 text-gray-700">Merek</label>
        <select name="merek_code" class="select2 w-full border rounded px-3 py-2 text-sm">
            <option value="">-- Semua Merek --</option>
            @foreach($mereks as $m) 
                <option value="{{ $m->fmerekcode }}">{{ $m->fmerekname }}</option> 
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-xs font-bold uppercase mb-1 text-gray-700">Salesman</label>
        <select name="salesman" class="select2 w-full border rounded px-3 py-2 text-sm">
            <option value="">-- Semua Salesman --</option>
            @foreach($salesmans as $s) 
                <option value="{{ $s->fsalesmancode }}">{{ $s->fsalesmanname }}</option> 
            @endforeach
        </select>
    </div>
</div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase mb-1">Produk From</label>
                                <input type="text" name="prd_from" class="w-full border rounded px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase mb-1">Produk To</label>
                                <input type="text" name="prd_to" class="w-full border rounded px-3 py-2 text-sm">
                            </div>
                        </div>

                        <div class="bg-gray-50 p-3 rounded border space-y-3">
                            <div class="flex gap-6">
                                <label class="flex items-center text-sm font-semibold">
                                    <input type="checkbox" name="semua_faktur" checked class="mr-2"> Semua Faktur
                                </label>
                                <label class="flex items-center text-sm font-semibold">
                                    <input type="checkbox" name="belum_kirim" class="mr-2"> Belum Di Kirim
                                </label>
                            </div>
                            <hr>
                            <div class="flex gap-6">
                                <label class="flex items-center text-sm font-semibold text-blue-700">
                                    <input type="radio" name="display_type" value="detail" checked class="mr-2"> DETAIL
                                </label>
                                <label class="flex items-center text-sm font-semibold text-blue-700">
                                    <input type="radio" name="display_type" value="rekap" class="mr-2"> REKAP
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-bold rounded-lg shadow-md hover:bg-blue-700">🖨️ Cetak</button>
                        <button type="button" onclick="window.location.href='{{ route('dashboard') }}'" class="px-5 py-2 bg-gray-100 text-gray-600 rounded-lg">Cancel</button>
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
            $('.select2').select2({ width: '100%' });
            toggleModal(true);
        });
    </script>
@endsection