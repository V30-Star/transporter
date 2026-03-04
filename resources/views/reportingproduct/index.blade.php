@extends('layouts.app')

@section('content')
    <div id="filterModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" onclick="toggleModal(false)"></div>

            <div class="relative bg-white rounded-xl shadow-2xl max-w-lg w-full p-6 overflow-hidden">
                <div class="flex justify-between items-center border-b pb-4 mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Product Inventory Report</h3>
                    <button onclick="toggleModal(false)"
                        class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
                </div>

                <form method="GET" action="{{ route('reportingproduct.print') }}" target="_blank">
                    <div class="space-y-4">
                        {{-- Kode From - To --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase">Product From</label>
                                <select name="prd_from" id="prd_from" class="w-full border rounded-lg px-3 py-2 text-sm">
                                    <option value="">-- All --</option>
                                    @foreach ($products as $p)
                                        <option value="{{ $p->fprdcode }}">{{ $p->fprdcode }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase">Product To</label>
                                <select name="prd_to" id="prd_to" class="w-full border rounded-lg px-3 py-2 text-sm">
                                    <option value="">-- All --</option>
                                    @foreach ($products as $p)
                                        <option value="{{ $p->fprdcode }}">{{ $p->fprdcode }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Group & Merek --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase">Group</label>
                                <select name="group" class="w-full border rounded-lg px-3 py-2 text-sm">
                                    <option value="">-- All Group --</option>
                                    @foreach ($groups as $g)
                                        <option value="{{ $g->fgroupcode }}">{{ $g->fgroupcode }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase">Merek</label>
                                <select name="merek" class="w-full border rounded-lg px-3 py-2 text-sm">
                                    <option value="">-- All Merek --</option>
                                    @foreach ($mereks as $m)
                                        <option value="{{ $m->fmerek }}">{{ $m->fmerek }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        {{-- Baris Filter Gudang --}}
                        <div class="flex flex-col">
                            <label class="text-xs font-bold text-gray-700 uppercase mb-1">Lokasi Gudang</label>
                            <select name="warehouse" class="w-full border rounded-lg px-3 py-2 text-sm">
                                <option value="">-- Semua Gudang --</option>
                                @foreach ($warehouses as $wh)
                                    <option value="{{ $wh->fwhcode }}">{{ $wh->fwhcode }} - {{ $wh->fwhname }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Baris Checkbox Kolom Harga --}}
                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-2">Kolom Harga yg
                                Dicetak:</label>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="flex items-center text-sm">
                                    <input type="checkbox" name="show_hpp" checked class="mr-2"> Harga Pokok
                                </label>
                                <label class="flex items-center text-sm">
                                    <input type="checkbox" name="show_price1" checked class="mr-2"> Harga Jual 1
                                </label>
                                <label class="flex items-center text-sm">
                                    <input type="checkbox" name="show_price2" class="mr-2"> Harga Jual 2
                                </label>
                                <label class="flex items-center text-sm">
                                    <input type="checkbox" name="show_price3" class="mr-2"> Harga Jual 3
                                </label>
                            </div>
                        </div>

                        {{-- Checkbox Stok --}}
                        <div class="flex items-center space-x-2 py-2 border-y border-gray-100">
                            <input type="checkbox" name="only_stock" id="only_stock" value="1"
                                class="w-4 h-4 text-blue-600">
                            <label for="only_stock" class="text-sm font-semibold text-gray-700">Hanya tampilkan produk yang
                                ada stok</label>
                        </div>

                        {{-- Sort By --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-2">Urutkan Berdasarkan:</label>
                            <div class="flex space-x-6">
                                <label class="inline-flex items-center text-sm">
                                    <input type="radio" name="sort_by" value="code" checked class="mr-2"> Kode Produk
                                </label>
                                <label class="inline-flex items-center text-sm">
                                    <input type="radio" name="sort_by" value="name" class="mr-2"> Nama Produk
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                        <button type="submit"
                            class="px-6 py-2 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 flex items-center shadow-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Print Preview
                        </button>
                        <button type="button" onclick="window.location.href='{{ route('dashboard') }}'"
                            class="px-5 py-2 bg-gray-100 text-gray-600 font-semibold rounded-lg hover:bg-gray-200">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#prd_from, #prd_to').select2({
                width: '100%',
                placeholder: '-- All --',
                allowClear: true
            });
            $('#prd_from').on('select2:select', function() {
                $('#prd_to').val($(this).val()).trigger('change');
            });
            toggleModal(true);
        });

        function toggleModal(show) {
            const modal = document.getElementById('filterModal');
            show ? modal.classList.remove('hidden') : modal.classList.add('hidden');
        }
    </script>
@endsection
