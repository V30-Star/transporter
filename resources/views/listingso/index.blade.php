@extends('layouts.app')

@section('title', '')

@section('content')
<div id="filterModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="toggleModal(false)"></div>
        <div class="relative bg-white rounded-xl shadow-2xl max-w-lg w-full p-6">
            <div class="flex justify-between items-center border-b pb-4 mb-4">
                <h3 class="text-xl font-bold text-gray-800">Listing Sales Order</h3>
                <button onclick="toggleModal(false)" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
            </div>

            <form method="GET" action="{{ route('listingso.print') }}" target="_blank">
                <div class="space-y-4">
                    {{-- Tanggal --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">Tanggal Dari</label>
                            <input type="date" name="date_from" value="{{ date('Y-m-d') }}" class="w-full border rounded px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">Sampai</label>
                            <input type="date" name="date_to" value="{{ date('Y-m-d') }}" class="w-full border rounded px-3 py-2 text-sm">
                        </div>
                    </div>

                    {{-- Customer --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">Cust From</label>
                            <select name="cust_from" id="cust_from" class="select2 w-full">
                                <option value="">-- All --</option>
                                @foreach($customers as $c) <option value="{{$c->fcustomercode}}">{{$c->fcustomercode}} - {{$c->fcustomername}}</option> @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">Cust To</label>
                            <select name="cust_to" id="cust_to" class="select2 w-full">
                                <option value="">-- All --</option>
                                @foreach($customers as $c) <option value="{{$c->fcustomercode}}">{{$c->fcustomercode}} - {{$c->fcustomername}}</option> @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Produk --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">Prd From</label>
                            <select name="prd_from" id="prd_from" class="select2 w-full">
                                <option value="">-- All --</option>
                                @foreach($products as $p) <option value="{{$p->fprdcode}}">{{$p->fprdcode}}</option> @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">Prd To</label>
                            <select name="prd_to" id="prd_to" class="select2 w-full">
                                <option value="">-- All --</option>
                                @foreach($products as $p) <option value="{{$p->fprdcode}}">{{$p->fprdcode}}</option> @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 bg-gray-50 p-3 rounded border">
                        <label class="flex items-center text-sm font-semibold">
                            <input type="checkbox" name="all_so" id="all_so" checked class="mr-2 w-4 h-4"> Semua SO
                        </label>
                        <label class="flex items-center text-sm font-semibold">
                            <input type="checkbox" name="only_pending" id="only_pending" class="mr-2 w-4 h-4"> Hanya SO yg Belum Kirim
                        </label>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700">🖨️ Cetak</button>
                    <button type="button" onclick="window.location.href='{{ route('dashboard') }}'" class="px-5 py-2 bg-gray-100 text-gray-600 rounded-lg">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.select2').select2({ width: '100%' });
        toggleModal(true);

        $('#all_so').change(function() {
            if($(this).is(':checked')) {
                $('#only_pending').prop('checked', false).prop('disabled', true);
            } else {
                $('#only_pending').prop('disabled', false);
            }
        }).trigger('change');
    });
    function toggleModal(show) { $('#filterModal').toggleClass('hidden', !show); }
</script>
@endsection