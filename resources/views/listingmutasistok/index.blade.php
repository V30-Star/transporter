@extends('layouts.app')

@section('title', 'Listing Mutasi Stok')

@section('content')
    <div id="filterModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-black bg-opacity-50" onclick="toggleModal(false)"></div>
            <div class="relative bg-white rounded-xl shadow-2xl max-w-2xl w-full p-6">
                <div class="flex justify-between items-center border-b pb-4 mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Listing Mutasi Stok</h3>
                    <button onclick="toggleModal(false)" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
                </div>

                <form method="GET" action="{{ route('listingmutasistok.print') }}" target="_blank">
                    <input type="hidden" name="selected_products" id="selected_products_input">
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <label class="block text-xs font-bold uppercase">Cabang / Branch</label>
                                @if ($isAuthorized)
                                    <div class="flex space-x-2">
                                        <button type="button" onclick="selectAllBranches(true)" class="text-xs bg-blue-100 text-blue-700 px-3 py-1 rounded hover:bg-blue-200">Select All</button>
                                        <button type="button" onclick="selectAllBranches(false)" class="text-xs bg-gray-100 text-gray-700 px-3 py-1 rounded hover:bg-gray-200">Unselect All</button>
                                    </div>
                                @endif
                            </div>
                            <div id="branchCheckboxesArea" class="border rounded-lg p-3 bg-gray-50 max-h-40 overflow-y-auto">
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach ($branches as $b)
                                        @php $isChecked = $isAuthorized || ($userBranchCode === $b->fcabangkode); @endphp
                                        <label class="flex items-center text-sm cursor-pointer select-none">
                                            @if (!$isAuthorized && $userBranchCode === $b->fcabangkode)
                                                <input type="hidden" name="branch_codes[]" value="{{ $b->fcabangkode }}">
                                            @endif
                                            <input type="checkbox" name="branch_codes[]" value="{{ $b->fcabangkode }}" class="branch-checkbox mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4" {{ $isChecked ? 'checked' : '' }} {{ !$isAuthorized ? 'disabled' : '' }}>
                                            <span class="text-gray-700 font-medium">{{ $b->fcabangkode }} - {{ $b->fcabangname }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase mb-1">Tanggal Dari</label>
                                <input type="date" name="date_from" value="{{ date('Y-01-01') }}" class="w-full border rounded px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase mb-1">Tanggal Sampai</label>
                                <input type="date" name="date_to" value="{{ date('Y-12-31') }}" class="w-full border rounded px-3 py-2 text-sm">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase mb-1">Gudang Asal</label>
                                <select name="warehouse_from" class="select2 w-full border rounded px-3 py-2 text-sm">
                                    <option value="">Semua Gudang</option>
                                    @foreach ($warehouses as $warehouse)
                                        <option value="{{ $warehouse->fwhcode }}">{{ $warehouse->fwhcode }} - {{ $warehouse->fwhname }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase mb-1">Gudang Tujuan</label>
                                <select name="warehouse_to" class="select2 w-full border rounded px-3 py-2 text-sm">
                                    <option value="">Semua Gudang</option>
                                    @foreach ($warehouses as $warehouse)
                                        <option value="{{ $warehouse->fwhcode }}">{{ $warehouse->fwhcode }} - {{ $warehouse->fwhname }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Filter Produk</p>
                            <div class="border border-gray-200 rounded-lg p-3 bg-gray-50">
                                <div class="flex gap-2 mb-2">
                                    <select id="prd_selector" class="select2 flex-1 border border-gray-300 rounded-lg text-sm">
                                        <option value="">-- Pilih Produk --</option>
                                        @foreach ($products as $p)
                                            <option value="{{ $p->fprdcode }}">{{ $p->fprdcode }} - {{ $p->fprdname }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" onclick="addProduct()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold">+ Add</button>
                                </div>
                                <div id="prd_list" class="flex flex-wrap gap-2 min-h-[38px] p-2 bg-white border border-gray-200 rounded-lg">
                                    <span class="text-xs text-gray-400 italic self-center">Belum ada produk dipilih</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 p-3 rounded border text-center">
                            <div class="flex justify-center gap-6">
                                <label class="flex items-center text-sm font-semibold cursor-pointer">
                                    <input type="radio" name="display_type" value="detail" checked class="mr-2 w-4 h-4 text-blue-600 focus:ring-blue-500"> Detail
                                </label>
                                <label class="flex items-center text-sm font-semibold cursor-pointer">
                                    <input type="radio" name="display_type" value="rekap" class="mr-2 w-4 h-4 text-blue-600 focus:ring-blue-500"> Rekap
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700">Cetak</button>
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
        let selectedProducts = [];

        $(document).ready(function() {
            $('.select2').select2({ width: '100%' });
            toggleModal(true);
        });

        function toggleModal(show) {
            if (!show) {
                window.location.href = "{{ route('dashboard') }}";
                return;
            }
            $('#filterModal').removeClass('hidden');
        }

        function selectAllBranches(status) {
            document.querySelectorAll('#branchCheckboxesArea .branch-checkbox:not(:disabled)').forEach(checkbox => checkbox.checked = status);
        }

        function addProduct() {
            const sel = document.getElementById('prd_selector');
            const code = sel.value;
            if (code && !selectedProducts.includes(code)) {
                selectedProducts.push(code);
                renderPrdList();
            }
        }

        function removePrd(code) {
            selectedProducts = selectedProducts.filter(c => c !== code);
            renderPrdList();
        }

        function renderPrdList() {
            const container = document.getElementById('prd_list');
            if (selectedProducts.length === 0) {
                container.innerHTML = '<span class="text-xs text-gray-400 italic self-center">Belum ada produk dipilih</span>';
            } else {
                container.innerHTML = selectedProducts.map(c =>
                    `<span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-md flex items-center gap-1 border border-blue-200">${c}<button type="button" onclick="removePrd('${c}')" class="text-red-500 font-bold leading-none">&times;</button></span>`
                ).join('');
            }
            document.getElementById('selected_products_input').value = selectedProducts.join(',');
        }
    </script>
@endsection
