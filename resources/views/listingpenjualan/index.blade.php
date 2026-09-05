@extends('layouts.app')

@section('title', 'Listing Penjualan')

@section('content')
    <div id="filterModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-black bg-opacity-50" onclick="toggleModal(false)"></div>
            <div class="relative bg-white rounded-xl shadow-2xl max-w-3xl w-full p-6">
                <div class="flex justify-between items-center border-b pb-4 mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Listing Penjualan</h3>
                    <button onclick="toggleModal(false)"
                        class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
                </div>

                <form method="GET" action="{{ route('listingpenjualan.print') }}" target="_blank">
                    <input type="hidden" name="selected_products" id="selected_products_input">
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
                            <div>
                                <label class="block text-xs font-bold uppercase mb-1">Dari Tanggal</label>
                                <input type="date" name="date_from" value="{{ date('Y-m-01') }}"
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
                                <label class="block text-xs font-bold uppercase mb-1 text-gray-700">Customer From</label>
                                <select name="cust_from" id="cust_from" class="select2 w-full border rounded px-3 py-2 text-sm">
                                    <option value="">-- Semua / Mulai --</option>
                                    @foreach ($customers as $c)
                                        <option value="{{ $c->fcustomercode }}">{{ $c->fcustomername }} ({{ $c->fcustomercode }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase mb-1 text-gray-700">Customer To</label>
                                <select name="cust_to" id="cust_to" class="select2 w-full border rounded px-3 py-2 text-sm">
                                    <option value="">-- Semua / Sampai --</option>
                                    @foreach ($customers as $c)
                                        <option value="{{ $c->fcustomercode }}">{{ $c->fcustomername }} ({{ $c->fcustomercode }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Filter Produk (Multi-select like Listing SO) --}}
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Filter Produk</p>
                            <div class="border border-gray-200 rounded-lg p-3 bg-gray-50">
                                <div class="flex gap-2 mb-2">
                                    <select id="prd_selector"
                                        class="select2 flex-1 border border-gray-300 rounded-lg text-sm">
                                        <option value="">-- Pilih Produk --</option>
                                        @foreach ($products as $p)
                                            <option value="{{ $p->fprdcode }}">{{ $p->fprdcode }} - {{ $p->fprdname }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="button" onclick="addProduct()"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold">
                                        + Add
                                    </button>
                                </div>
                                <div id="prd_list"
                                    class="flex flex-wrap gap-2 min-h-[38px] p-2 bg-white border border-gray-200 rounded-lg">
                                    <span class="text-xs text-gray-400 italic self-center">Belum ada produk dipilih</span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase mb-1 text-gray-700">Group Produk</label>
                                <select name="group_code" class="select2 w-full border rounded px-3 py-2 text-sm">
                                    <option value="">-- Semua Group --</option>
                                    @foreach ($groups as $g)
                                        <option value="{{ $g->fgroupcode }}">{{ $g->fgroupname }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase mb-1 text-gray-700">Merek</label>
                                <select name="merek_code" class="select2 w-full border rounded px-3 py-2 text-sm">
                                    <option value="">-- Semua Merek --</option>
                                    @foreach ($mereks as $m)
                                        <option value="{{ $m->fmerekcode }}">{{ $m->fmerekname }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase mb-1 text-gray-700">Salesman</label>
                                <select name="salesman" class="select2 w-full border rounded px-3 py-2 text-sm">
                                    <option value="">-- Semua Salesman --</option>
                                    @foreach ($salesmans as $s)
                                        <option value="{{ $s->fsalesmancode }}">{{ $s->fsalesmanname }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase mb-1 text-gray-700">Tipe Penjualan</label>
                                <select name="ftypesales" class="w-full border rounded px-3 py-2 text-sm">
                                    <option value="">-- Semua Tipe --</option>
                                    <option value="1">Uang Muka (UM)</option>
                                    <option value="0">Penjualan</option>
                                </select>
                            </div>
                        </div>

                        <!-- Options Row (Inline) -->
                        <div class="flex items-center gap-3">
                            <div class="flex-1 bg-gray-50 p-3 rounded-lg border border-gray-200 space-y-3">
                                <div class="flex gap-6">
                                    <label class="flex items-center text-sm font-semibold cursor-pointer select-none">
                                        <input type="checkbox" name="semua_faktur" checked class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4"> Semua Faktur
                                    </label>
                                    <label class="flex items-center text-sm font-semibold cursor-pointer select-none">
                                        <input type="checkbox" name="belum_kirim" class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4"> Belum Di Kirim
                                    </label>
                                </div>
                                <hr class="border-gray-200">
                                <div class="flex gap-6">
                                    <label class="flex items-center text-sm font-semibold text-blue-700 cursor-pointer select-none">
                                        <input type="radio" name="display_type" value="detail" checked class="mr-2 w-4 h-4 text-blue-600 focus:ring-blue-500"> DETAIL
                                    </label>
                                    <label class="flex items-center text-sm font-semibold text-blue-700 cursor-pointer select-none">
                                        <input type="radio" name="display_type" value="rekap" class="mr-2 w-4 h-4 text-blue-600 focus:ring-blue-500"> REKAP
                                    </label>
                                </div>
                            </div>

                            <!-- Retur Penjualan Option Box (Separated, Red text) -->
                            <div class="bg-red-50 p-3 rounded-lg border border-red-200 whitespace-nowrap self-stretch flex items-center">
                                <label class="flex items-center text-sm font-bold text-red-600 cursor-pointer select-none">
                                    <input type="checkbox" name="include_retur_penjualan" value="1" class="mr-2 w-4 h-4 text-red-600 focus:ring-red-500 rounded border-red-300">
                                    <span class="text-red-600 font-bold">Termasuk Retur Penjualan</span>
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

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        let selectedProducts = [];

        function toggleModal(show) {
            $('#filterModal').toggleClass('hidden', !show);
        }

        function selectAllBranches(status) {
            document.querySelectorAll('#branchCheckboxesArea .branch-checkbox').forEach(checkbox => {
                checkbox.checked = status;
            });
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
                container.innerHTML =
                    '<span class="text-xs text-gray-400 italic self-center">Belum ada produk dipilih</span>';
            } else {
                container.innerHTML = selectedProducts.map(c =>
                    `<span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-md flex items-center gap-1 border border-blue-200">
                    ${c}
                    <button type="button" onclick="removePrd('${c}')" class="text-red-500 font-bold leading-none">&times;</button>
                </span>`
                ).join('');
            }
            document.getElementById('selected_products_input').value = selectedProducts.join(',');
        }

        $(document).ready(function() {
            $('.select2').select2({
                width: '100%'
            });
            $('#cust_from').on('change', function() {
                $('#cust_to').val($(this).val()).trigger('change');
            });
            toggleModal(true);
        });
    </script>
@endsection
