@extends('layouts.app')

@section('title', 'Listing Jurnal')

@section('content')
<div id="filterModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="toggleModal(false)"></div>
        <div class="relative bg-white rounded-xl shadow-2xl max-w-2xl w-full p-6">
            <div class="flex justify-between items-center border-b pb-4 mb-4">
                <h3 class="text-xl font-bold text-gray-800">Listing Jurnal</h3>
                <button onclick="toggleModal(false)" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
            </div>

            <form method="GET" action="{{ route('listingjurnal.print') }}" target="_blank">
                <div class="space-y-4">
                    {{-- Tanggal --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">Tanggal Dari</label>
                            <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full border rounded px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">Sampai</label>
                            <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full border rounded px-3 py-2 text-sm">
                        </div>
                    </div>

                    {{-- Jurnal Type --}}
                    <div>
                        <label class="block text-xs font-bold uppercase mb-1">Jurnal Type</label>
                        <select name="journal_types[]" id="journal_types" class="select2 w-full" multiple>
                            @foreach($typeOptions as $type)
                                <option value="{{ $type->fmastercode }}">{{ $type->fmastercode }} - {{ $type->fmastername }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Kosongkan untuk memilih semua type.</p>
                    </div>

                    {{-- Urut Berdasarkan --}}
                    <div>
                        <label class="block text-xs font-bold uppercase mb-1">Urut Berdasarkan</label>
                        <select name="sort_by" id="sort_by" class="select2 w-full">
                            @foreach($sortOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
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
        $('.select2').select2({
            width: '100%',
            placeholder: "-- Semua / All --"
        });
        toggleModal(true);
    });

    function toggleModal(show) {
        if (!show) {
            window.location.href = "{{ route('dashboard') }}";
        } else {
            $('#filterModal').removeClass('hidden');
        }
    }
</script>
@endsection
