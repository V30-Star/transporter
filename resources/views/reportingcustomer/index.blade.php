@extends('layouts.app')

@section('title', '')

@section('content')
<div id="filterModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" onclick="toggleModal(false)"></div>

        <div class="relative bg-white rounded-xl shadow-2xl max-w-lg w-full p-6 overflow-hidden">
            <div class="flex justify-between items-center border-b pb-4 mb-4">
                <h3 class="text-xl font-bold text-gray-800">Customer Report Filter</h3>
                <button onclick="toggleModal(false)" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
            </div>

            <form method="GET" action="{{ route('reportingcustomer.print') }}" target="_blank">
                <div class="space-y-4">
                    {{-- Range Customer --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase">From</label>
                            <select name="cust_from" id="cust_from" class="w-full border rounded-lg px-3 py-2 text-sm select2">
                                <option value="">-- All --</option>
                                @foreach ($customers as $c)
                                    <option value="{{ $c->fcustomercode }}">{{ $c->fcustomercode }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase">To</label>
                            <select name="cust_to" id="cust_to" class="w-full border rounded-lg px-3 py-2 text-sm select2">
                                <option value="">-- All --</option>
                                @foreach ($customers as $c)
                                    <option value="{{ $c->fcustomercode }}">{{ $c->fcustomercode }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Salesman --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Salesman</label>
                        <select name="salesman" class="w-full border rounded-lg px-3 py-2 text-sm select2">
                            <option value="">-- All Salesman --</option>
                            @foreach ($salesmen as $s)
                                <option value="{{ $s->fsalesmancode }}">{{ $s->fsalesmanname }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Limit & Keterangan --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Limit Saldo</label>
                        <div class="flex items-center space-x-3">
                            <input type="number" name="limit" value="0" class="w-1/2 border rounded-lg px-3 py-2 text-sm">
                            <span class="text-xs font-bold text-red-600 italic">* 0 = Tampilkan Semua</span>
                        </div>
                    </div>

                    {{-- Checkbox Blokir --}}
                    <div class="flex items-center space-x-2 py-2">
                        <input type="checkbox" name="is_blocked" id="is_blocked" value="1" class="w-4 h-4">
                        <label for="is_blocked" class="text-sm font-semibold text-gray-700">Hanya Tampilkan Customer Terblokir</label>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print Preview
                    </button>
                    <button type="button" onclick="window.location.href='{{ route('dashboard') }}'" class="px-5 py-2 bg-gray-100 text-gray-600 font-semibold rounded-lg hover:bg-gray-200">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Pastikan jQuery dan Select2 JS sudah di-load di layout utama atau di sini --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    // 1. Definisikan fungsi toggleModal terlebih dahulu agar bisa dibaca global
    function toggleModal(show) {
        const modal = document.getElementById('filterModal');
        if (show) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Kunci scroll layar belakang
        } else {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto'; // Aktifkan scroll kembali
        }
    }

    $(document).ready(function() {
        // 2. Inisialisasi Select2
        $('.select2').select2({ 
            width: '100%',
            placeholder: '-- Pilih --',
            allowClear: true 
        });

        // 3. Panggil fungsi untuk memunculkan modal setelah halaman siap
        toggleModal(true);

        // Tambahan: Auto-fill 'To' saat 'From' dipilih
        $('#cust_from').on('select2:select', function(e) {
            $('#cust_to').val($(this).val()).trigger('change');
        });
    });
</script>
@endsection