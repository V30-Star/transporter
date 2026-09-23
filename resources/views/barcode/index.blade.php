@extends('layouts.app')

@section('title', 'Cetak Barcode Label')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 pb-4 border-b border-gray-200">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fa-solid fa-barcode text-blue-600"></i>
                Cetak Barcode Label
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Mendukung semua printer thermal/barcode (Zebra, Argox, Honeywell, TSC, Xprinter, dll.) dengan ukuran label dinamis.
            </p>
        </div>
        <div class="mt-4 md:mt-0 flex gap-2">
            <a href="{{ route('product.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fa-solid fa-arrow-left mr-2"></i> Ke Master Produk
            </a>
        </div>
    </div>

    <form id="barcodeForm" action="{{ route('barcode.print') }}" method="POST" target="_blank">
        @csrf
        <input type="hidden" name="items" id="itemsInput" value="[]">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {{-- KOLOM KIRI: Pengaturan Kertas & Printer (Dinamis) --}}
            <div class="lg:col-span-5 space-y-6">
                {{-- Box Pengaturan Ukuran --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <h2 class="text-base font-semibold text-gray-800 flex items-center gap-2 mb-4 pb-2 border-b border-gray-100">
                        <i class="fa-solid fa-sliders text-blue-500"></i> Ukuran Kertas / Stiker
                    </h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Preset Ukuran Label</label>
                            <select id="presetSelect" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="33x15_3col">33 × 15 mm (3 Kolom) — Standar Retail Minimarket</option>
                                <option value="40x30_1col">40 × 30 mm (1 Kolom) — Standar Label Tunggal</option>
                                <option value="50x20_1col">50 × 20 mm (1 Kolom) — Price Tag / Label Rak</option>
                                <option value="50x30_1col">50 × 30 mm (1 Kolom) — Label Standar Sedang</option>
                                <option value="70x50_1col">70 × 50 mm (1 Kolom) — Label Box / Ekspedisi</option>
                                <option value="100x50_1col">100 × 50 mm (1 Kolom) — Label Besar Pengiriman</option>
                                <option value="custom">Custom (Bebas Atur Ukuran)</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Lebar Label (mm)</label>
                                <input type="number" step="0.1" min="10" max="250" name="label_width" id="labelWidth" value="33"
                                    class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Tinggi Label (mm)</label>
                                <input type="number" step="0.1" min="8" max="250" name="label_height" id="labelHeight" value="15"
                                    class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Jumlah Kolom</label>
                                <input type="number" min="1" max="5" name="columns" id="labelColumns" value="3"
                                    class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Jarak Antar Label / Gap (mm)</label>
                                <input type="number" step="0.5" min="0" max="20" name="gap_x" id="gapX" value="2"
                                    class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Box Opsi Konten Label --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <h2 class="text-base font-semibold text-gray-800 flex items-center gap-2 mb-4 pb-2 border-b border-gray-100">
                        <i class="fa-solid fa-list-check text-blue-500"></i> Konten Pada Stiker
                    </h2>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="flex items-center text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" name="show_company" id="showCompany" value="1" checked
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 mr-2">
                                Nama Toko / Header
                            </label>
                            <input type="text" name="company_name" id="companyName" value="{{ $companyName }}"
                                class="text-xs w-48 rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <label class="flex items-center text-sm text-gray-700 cursor-pointer">
                            <input type="checkbox" name="show_name" id="showName" value="1" checked
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 mr-2">
                            Nama Produk
                        </label>

                        <label class="flex items-center text-sm text-gray-700 cursor-pointer">
                            <input type="checkbox" name="show_code" id="showCode" value="1" checked
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 mr-2">
                            Barcode & Kode Angka
                        </label>

                        <label class="flex items-center text-sm text-gray-700 cursor-pointer">
                            <input type="checkbox" name="show_price" id="showPrice" value="1" checked
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 mr-2">
                            Harga Jual Produk
                        </label>

                        <div class="pt-3 border-t border-gray-100 grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Tinggi Barcode (px)</label>
                                <input type="number" min="15" max="80" name="barcode_height" id="barcodeHeight" value="22"
                                    class="w-full text-xs rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Ukuran Font (pt)</label>
                                <input type="number" step="0.5" min="6" max="14" name="font_size" id="fontSize" value="7.5"
                                    class="w-full text-xs rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Live Preview Box --}}
                <div class="bg-slate-50 rounded-xl shadow-sm border border-slate-200 p-5">
                    <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                        <i class="fa-solid fa-eye"></i> Simulasi Tampilan 1 Stiker
                    </h2>
                    <div class="flex justify-center p-4 bg-slate-200 rounded-lg overflow-auto border border-dashed border-slate-300">
                        <div id="previewCard" class="bg-white border border-gray-400 shadow-sm p-1 flex flex-col justify-between items-center text-center transition-all overflow-hidden"
                            style="width: 140px; min-height: 80px; box-sizing: border-box;">
                            <div id="prevCompany" class="font-bold uppercase tracking-tight text-gray-800 leading-none truncate w-full" style="font-size: 7.5pt;">
                                {{ $companyName }}
                            </div>
                            <div id="prevName" class="font-semibold text-gray-900 leading-tight line-clamp-2 w-full mt-0.5" style="font-size: 7pt;">
                                CONTOH NAMA PRODUK
                            </div>
                            <div class="w-full flex justify-center my-0.5">
                                <svg id="previewBarcodeSvg" class="max-w-full"></svg>
                            </div>
                            <div id="prevPrice" class="font-bold text-gray-900 leading-none w-full" style="font-size: 8pt;">
                                Rp 15.000
                            </div>
                        </div>
                    </div>
                    <p class="text-center text-xs text-gray-400 mt-2">
                        * Skala disesuaikan layar. Hasil cetak aktual pas 100% dengan mm stiker Anda.
                    </p>
                </div>
            </div>

            {{-- KOLOM KANAN: Pemilihan Produk & Antrean Cetak --}}
            <div class="lg:col-span-7 space-y-6">
                {{-- Box Pilih Produk --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <h2 class="text-base font-semibold text-gray-800 flex items-center gap-2 mb-3">
                        <i class="fa-solid fa-cart-plus text-blue-500"></i> Pilih Produk yang Ingin Dicetak
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                        <div class="sm:col-span-8">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Cari Produk (Nama / Kode / Barcode)</label>
                            <select id="productSearchSelect" class="w-full text-sm" style="width: 100%;">
                                <option value="">Ketik nama atau kode produk...</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Qty Stiker</label>
                            <input type="number" id="productAddQty" value="1" min="1" max="9999"
                                class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-center font-bold">
                        </div>
                        <div class="sm:col-span-2">
                            <button type="button" id="btnAddProduct"
                                class="w-full py-2 px-3 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition flex items-center justify-center gap-1">
                                <i class="fa-solid fa-plus"></i> Tambah
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Box Antrean Cetak --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-gray-800">Daftar Antrean Cetak</h2>
                            <span class="text-xs text-gray-500" id="queueSummaryText">0 produk dipilih (Total 0 label)</span>
                        </div>
                        <button type="button" id="btnClearQueue" class="text-xs text-red-600 hover:text-red-800 font-medium">
                            <i class="fa-solid fa-trash-can mr-1"></i> Kosongkan Daftar
                        </button>
                    </div>

                    <div class="overflow-x-auto max-h-96 overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-100 text-gray-600 text-xs uppercase sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-2 text-left">Kode / Barcode</th>
                                    <th class="px-3 py-2 text-left">Nama Produk</th>
                                    <th class="px-3 py-2 text-right">Harga</th>
                                    <th class="px-3 py-2 text-center" style="width: 100px;">Qty Label</th>
                                    <th class="px-3 py-2 text-center" style="width: 50px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="queueTableBody" class="divide-y divide-gray-100">
                                <tr id="emptyQueueRow">
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-400">
                                        <i class="fa-solid fa-inbox text-3xl mb-2 block text-gray-300"></i>
                                        Belum ada produk di antrean cetak.<br>
                                        Gunakan form di atas untuk menambahkan produk.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Bottom Action Bar --}}
                    <div class="p-4 bg-slate-50 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-gray-600">
                            Tips: Setel margin browser menjadi <strong>None</strong> saat dialog cetak muncul.
                        </div>
                        <button type="submit" id="btnPrintSubmit" disabled
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-base font-bold rounded-xl shadow-md transition">
                            <i class="fa-solid fa-print text-lg"></i>
                            Cetak Barcode Sekarang
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
$(document).ready(function() {
    let queueItems = [];
    let selectedSearchData = null;

    // Preloaded product from query params if any
    const preloadedProduct = @json($preloaded ?? null);
    if (preloadedProduct) {
        queueItems.push(preloadedProduct);
        renderQueue();
    }

    // Presets Definition
    const presets = {
        '33x15_3col': { width: 33, height: 15, cols: 3, gapX: 2, barcodeH: 20, fontS: 7 },
        '40x30_1col': { width: 40, height: 30, cols: 1, gapX: 0, barcodeH: 30, fontS: 8 },
        '50x20_1col': { width: 50, height: 20, cols: 1, gapX: 0, barcodeH: 24, fontS: 8 },
        '50x30_1col': { width: 50, height: 30, cols: 1, gapX: 0, barcodeH: 32, fontS: 8.5 },
        '70x50_1col': { width: 70, height: 50, cols: 1, gapX: 0, barcodeH: 45, fontS: 10 },
        '100x50_1col': { width: 100, height: 50, cols: 1, gapX: 0, barcodeH: 48, fontS: 11 },
    };

    $('#presetSelect').on('change', function() {
        const val = $(this).val();
        if (presets[val]) {
            const p = presets[val];
            $('#labelWidth').val(p.width);
            $('#labelHeight').val(p.height);
            $('#labelColumns').val(p.cols);
            $('#gapX').val(p.gapX);
            $('#barcodeHeight').val(p.barcodeH);
            $('#fontSize').val(p.fontS);
            updatePreview();
        }
    });

    // Inputs change listeners for live preview
    $('#labelWidth, #labelHeight, #labelColumns, #gapX, #barcodeHeight, #fontSize, #showCompany, #companyName, #showName, #showCode, #showPrice').on('input change', function() {
        updatePreview();
    });

    // Initialize Select2 Product Search
    $('#productSearchSelect').select2({
        ajax: {
            url: '{{ route('barcode.search-products') }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term };
            },
            processResults: function (data) {
                return {
                    results: data.map(function(item) {
                        return {
                            id: item.id,
                            text: item.fprdcode + ' - ' + item.fprdname + (item.fbarcode ? ' [' + item.fbarcode + ']' : ''),
                            product: item
                        };
                    })
                };
            },
            cache: true
        },
        placeholder: 'Ketik nama / kode / barcode...',
        minimumInputLength: 1
    }).on('select2:select', function(e) {
        selectedSearchData = e.params.data.product;
        $('#productAddQty').focus();
    });

    // Add to Queue
    $('#btnAddProduct').on('click', function() {
        if (!selectedSearchData) {
            Swal.fire({
                icon: 'warning',
                title: 'Pilih Produk',
                text: 'Silakan pilih produk terlebih dahulu.',
                confirmButtonColor: '#2563eb'
            });
            return;
        }

        const qty = parseInt($('#productAddQty').val(), 10) || 1;
        const existingIndex = queueItems.findIndex(it => it.fprdcode === selectedSearchData.fprdcode);

        if (existingIndex >= 0) {
            queueItems[existingIndex].qty += qty;
        } else {
            queueItems.push({
                fprdid: selectedSearchData.id,
                fprdcode: selectedSearchData.fprdcode,
                fprdname: selectedSearchData.fprdname,
                fbarcode: selectedSearchData.fbarcode || selectedSearchData.fprdcode,
                price: selectedSearchData.price || 0,
                satuan: selectedSearchData.satuan || '',
                qty: qty
            });
        }

        // Reset search
        $('#productSearchSelect').val(null).trigger('change');
        $('#productAddQty').val(1);
        selectedSearchData = null;

        renderQueue();
    });

    // Clear Queue
    $('#btnClearQueue').on('click', function() {
        if (queueItems.length === 0) return;
        queueItems = [];
        renderQueue();
    });

    // Render Queue Table
    function renderQueue() {
        const tbody = $('#queueTableBody');
        tbody.empty();

        if (queueItems.length === 0) {
            tbody.append(`
                <tr id="emptyQueueRow">
                    <td colspan="5" class="px-4 py-8 text-center text-gray-400">
                        <i class="fa-solid fa-inbox text-3xl mb-2 block text-gray-300"></i>
                        Belum ada produk di antrean cetak.<br>
                        Gunakan form di atas untuk menambahkan produk.
                    </td>
                </tr>
            `);
            $('#btnPrintSubmit').prop('disabled', true);
            $('#queueSummaryText').text('0 produk dipilih (Total 0 label)');
            $('#itemsInput').val('[]');
            updatePreview();
            return;
        }

        let totalLabels = 0;

        queueItems.forEach(function(item, idx) {
            totalLabels += item.qty;
            const formattedPrice = 'Rp ' + Number(item.price).toLocaleString('id-ID');

            tbody.append(`
                <tr class="hover:bg-blue-50/40">
                    <td class="px-3 py-2">
                        <div class="font-mono text-xs font-semibold text-gray-900">${item.fbarcode || item.fprdcode}</div>
                        <div class="text-[11px] text-gray-500">${item.fprdcode}</div>
                    </td>
                    <td class="px-3 py-2 font-medium text-gray-800 text-xs">${item.fprdname}</td>
                    <td class="px-3 py-2 text-right text-xs font-mono text-gray-700">${formattedPrice}</td>
                    <td class="px-3 py-2 text-center">
                        <input type="number" min="1" max="9999" value="${item.qty}" data-index="${idx}"
                            class="item-qty-input w-20 text-xs py-1 px-2 border border-gray-300 rounded text-center font-bold focus:ring-blue-500">
                    </td>
                    <td class="px-3 py-2 text-center">
                        <button type="button" data-index="${idx}" class="btn-remove-item text-red-500 hover:text-red-700 p-1">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </td>
                </tr>
            `);
        });

        $('#btnPrintSubmit').prop('disabled', false);
        $('#queueSummaryText').text(`${queueItems.length} produk dipilih (Total ${totalLabels} label)`);
        $('#itemsInput').val(JSON.stringify(queueItems));

        // Update live preview with first item in queue
        updatePreview();
    }

    // Change Qty in Table
    $(document).on('change input', '.item-qty-input', function() {
        const idx = $(this).data('index');
        const newQty = parseInt($(this).val(), 10) || 1;
        if (queueItems[idx]) {
            queueItems[idx].qty = newQty;
            let totalLabels = queueItems.reduce((acc, it) => acc + it.qty, 0);
            $('#queueSummaryText').text(`${queueItems.length} produk dipilih (Total ${totalLabels} label)`);
            $('#itemsInput').val(JSON.stringify(queueItems));
        }
    });

    // Remove single item
    $(document).on('click', '.btn-remove-item', function() {
        const idx = $(this).data('index');
        queueItems.splice(idx, 1);
        renderQueue();
    });

    // Live Preview Rendering
    function updatePreview() {
        const sampleItem = queueItems.length > 0 ? queueItems[0] : {
            fprdname: 'CONTOH NAMA PRODUK',
            fbarcode: '8991234567890',
            fprdcode: 'PRD-001',
            price: 15000
        };

        const showCompany = $('#showCompany').is(':checked');
        const companyName = $('#companyName').val() || '';
        const showName = $('#showName').is(':checked');
        const showCode = $('#showCode').is(':checked');
        const showPrice = $('#showPrice').is(':checked');
        const barcodeH = parseInt($('#barcodeHeight').val(), 10) || 22;
        const fontS = parseFloat($('#fontSize').val()) || 7.5;

        // Toggle visibility
        $('#prevCompany').toggle(showCompany).text(companyName).css('font-size', (fontS * 0.95) + 'pt');
        $('#prevName').toggle(showName).text(sampleItem.fprdname).css('font-size', (fontS * 0.9) + 'pt');
        $('#prevPrice').toggle(showPrice).text('Rp ' + Number(sampleItem.price).toLocaleString('id-ID')).css('font-size', (fontS * 1.05) + 'pt');

        if (showCode) {
            $('#previewBarcodeSvg').show();
            try {
                JsBarcode("#previewBarcodeSvg", sampleItem.fbarcode || sampleItem.fprdcode, {
                    format: "CODE128",
                    width: 1.2,
                    height: barcodeH,
                    displayValue: true,
                    fontSize: 9,
                    margin: 0,
                    textMargin: 1
                });
            } catch (err) {
                // If invalid characters for Code 128
                JsBarcode("#previewBarcodeSvg", "12345678", {
                    format: "CODE128",
                    width: 1.2,
                    height: barcodeH,
                    displayValue: true,
                    fontSize: 9,
                    margin: 0
                });
            }
        } else {
            $('#previewBarcodeSvg').hide();
        }
    }

    // Initial Preview
    updatePreview();
});
</script>
@endpush
