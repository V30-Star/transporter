@extends('layouts.app')

@section('content')
    <div id="filterModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="window.location.href='/dashboard'"></div>
        <div class="relative bg-white rounded-xl shadow-2xl max-w-2xl w-full p-6 overflow-y-auto max-h-[90vh]">

            <h3 class="text-xl font-bold text-gray-800 border-b pb-3 mb-5">Rekap SO Belum Dikirim</h3>

            <form method="GET" action="" target="_blank" id="filterForm">
                <input type="hidden" name="selected_products" id="selected_products_input">

                {{-- Periode Tanggal --}}
                <div class="mb-4">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Periode</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Dari Tanggal</label>
                            <input type="date" name="date_from" value="{{ date('Y-m-d') }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Sampai Tanggal</label>
                            <input type="date" name="date_to" value="{{ date('Y-m-d') }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                        </div>
                    </div>
                </div>

                {{-- Customer --}}
                <div class="mb-4">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Customer</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Dari</label>
                            <select name="cust_from" class="select2 w-full border border-gray-300 rounded-lg text-sm">
                                <option value="">-- All --</option>
                                @foreach ($customers as $c)
                                    <option value="{{ $c->fcustomercode }}">{{ $c->fcustomercode }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Sampai</label>
                            <select name="cust_to" class="select2 w-full border border-gray-300 rounded-lg text-sm">
                                <option value="">-- All --</option>
                                @foreach ($customers as $c)
                                    <option value="{{ $c->fcustomercode }}">{{ $c->fcustomercode }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Filter Produk --}}
                <div class="mb-4">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Filter Produk</p>
                    <div class="border border-gray-200 rounded-lg p-3 bg-gray-50">
                        <div class="flex gap-2 mb-2">
                            <select id="prd_selector" class="select2 flex-1 border border-gray-300 rounded-lg text-sm">
                                <option value="">-- Pilih Produk --</option>
                                @foreach ($products as $p)
                                    <option value="{{ $p->fprdcode }}">{{ $p->fprdcode }} - {{ $p->fprdname }}</option>
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

                {{-- Group Produk & Grouping --}}
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Group Produk</label>
                        <select name="group_prd" class="select2 w-full border border-gray-300 rounded-lg text-sm">
                            <option value="">-- Semua Group --</option>
                            @foreach ($groupPrd as $g)
                                <option value="{{ $g->fgroupcode }}">{{ $g->fgroupname }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Kelompokkan</label>
                        <div class="flex flex-col gap-1 mt-1">
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="radio" name="grouping" value="customer" checked> By Customer
                            </label>
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="radio" name="grouping" value="produk"> By Produk
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Opsi Cetak --}}
                <div class="mb-5 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Opsi Cetak</p>
                    <div class="flex gap-5">
                        <label class="flex items-center gap-2 text-sm font-semibold cursor-pointer">
                            <input type="checkbox" name="is_detail" checked> Detail
                        </label>
                        <label class="flex items-center gap-2 text-sm font-semibold cursor-pointer">
                            <input type="checkbox" name="is_rekap"> Rekap
                        </label>
                        <label class="flex items-center gap-2 text-sm font-semibold cursor-pointer">
                            <input type="checkbox" name="only_stok"> Ada Stok Saja
                        </label>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex justify-end gap-2 border-t pt-4">
                    <button type="submit"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-bold shadow">
                        🖨️ Cetak Laporan
                    </button>
                    <button type="button" onclick="window.location.href='{{ route('dashboard') }}'"
                        class="px-5 py-2 bg-gray-100 text-gray-600 rounded-lg">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let selectedProducts = [];
        $('.select2').select2({
            width: '100%'
        });

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

        function clearAll() {
            selectedProducts = [];
            renderPrdList();
            document.getElementById('filterForm').reset();
            $('.select2').val(null).trigger('change');
        }

        document.getElementById('filterForm').addEventListener('submit', function(e) {
            const grouping = document.querySelector('input[name="grouping"]:checked').value;
            this.action = grouping === 'produk' ?
                "{{ route('listingsobelum.printProduct') }}" :
                "{{ route('listingsobelum.printCustomer') }}";
        });
    </script>
@endsection
