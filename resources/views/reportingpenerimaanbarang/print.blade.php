<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listing Penerimaan Barang</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #000;
            background-color: #f5f5f5;
        }

        /* A4 Container (Untuk Tampilan Layar) */
        .a4-container {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: white;
            padding: 15mm;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header-section {
            position: relative;
            margin-bottom: 15px;
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .header-section h2 {
            font-size: 18px;
            margin-bottom: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #c00;
        }

        .filter-info {
            font-size: 10px;
            color: #333;
            margin-bottom: 5px;
        }

        /* --- PO HEADER STYLES (4 Kolom) --- */
        .po-header-labels,
        .po-header {
            display: grid;
            grid-template-columns: 35mm 35mm 80mm 40mm;
            gap: 5px;
            font-size: 9px;
            padding: 8px 5px;
        }

        .po-header-labels {
            font-weight: bold;
            background-color: #f0f0f0;
            border: 1px solid #000;
            border-bottom: 2px solid #000;
            margin-bottom: 2px;
        }

        .po-header {
            font-weight: bold;
            border-left: 1px solid #ccc;
            border-right: 1px solid #ccc;
            background-color: #fff;
            padding: 6px 5px;
        }

        /* --- PO DETAIL STYLES (4 Kolom) --- */
        .po-detail-labels,
        .po-detail {
            display: grid;
            grid-template-columns: 25mm 1fr 25mm 30mm;
            gap: 3px;
            font-size: 8px;
            padding: 4px 5px;
        }

        .po-detail-labels {
            font-weight: bold;
            color: #c00;
            background-color: #ffe6e6;
            border: 1px solid #ccc;
            border-bottom: none;
            margin-top: 2px;
            padding: 6px 5px;
        }

        /* Indent untuk kolom pertama (Produk#) pada data */
        .po-detail>div:first-child {
            padding-left: 5mm;
        }

        .po-detail {
            color: #c00;
            border-left: 1px solid #ccc;
            border-right: 1px solid #ccc;
            background-color: #fff;
        }

        /* Text alignment untuk detail */
        .po-detail-labels>div:nth-child(3),
        .po-detail>div:nth-child(3) {
            text-align: center;
        }

        .po-detail-labels>div:nth-child(4),
        .po-detail>div:nth-child(4) {
            text-align: right;
        }

        /* Wrap text untuk kolom Nama Produk */
        .po-detail>div:nth-child(2) {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .separator {
            border-bottom: 1px solid #ccc;
            margin: 8px 0;
            clear: both;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            font-style: italic;
        }

        .info-tambahan {
            position: absolute;
            top: 0;
            right: 0;
            font-size: 10px;
            color: #333;
            margin-top: 0;
            text-align: left;
            display: block;
            line-height: 1.4;
        }

        .info-tambahan span {
            display: inline-block;
        }

        .info-tambahan .info-label {
            font-weight: bold;
            display: inline-block;
            width: 40px;
            text-align: left;
        }

        .supplier-info-kiri {
            position: absolute;
            top: 10mm;
            left: 0mm;
            font-size: 10px;
            color: #333;
            font-weight: bold;
        }

        /* --- PRINT STYLES --- */
        @media print {
            body {
                background-color: white !important;
                margin: 0;
                padding: 0;
            }

            .a4-container {
                width: 100%;
                margin: 0;
                padding: 10mm;
                box-shadow: none;
                min-height: auto;
                position: relative;
                page-break-after: always;
            }

            .a4-container:last-child {
                page-break-after: avoid;
            }

            .po-header-labels,
            .po-detail-labels {
                background-color: transparent !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }

            @page {
                size: A4 portrait;
                margin: 10mm;
            }

            .po-header,
            .po-detail {
                page-break-inside: avoid;
            }

            .separator {
                border-bottom: 1px dashed #666;
            }
        }

        .no-print {
            position: fixed;
            top: 10px;
            left: 10px;
            display: flex;
            gap: 8px;
            z-index: 1000;
        }

        .print-button,
        .excel-button {
            padding: 10px 20px;
            font-size: 14px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .print-button {
            background-color: #3b82f6;
            color: white;
        }

        .print-button:hover {
            background-color: #2563eb;
        }

        .excel-button {
            background-color: #22c55e;
            color: white;
        }

        .excel-button:hover {
            background-color: #16a34a;
        }

        /* Zoom Controls */
        .zoom-controls {
            display: flex;
            align-items: center;
            gap: 5px;
            background: #fff;
            padding: 5px 10px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .zoom-btn {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 4px;
            background: #6b7280;
            color: white;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .zoom-btn:hover {
            background: #4b5563;
        }

        .zoom-level {
            min-width: 50px;
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            color: #333;
        }

        /* Report Container untuk Zoom */
        .report-wrapper {
            transform-origin: top center;
            transition: transform 0.2s ease;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .report-wrapper {
                transform: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="no-print">
        <button class="print-button" onclick="window.print()">🖨️ Cetak Laporan</button>
        <a href="{{ route('reporting.exportExcel', request()->query()) }}" class="excel-button">
            📊 Download Excel
        </a>
        <div class="zoom-controls">
            <button class="zoom-btn" onclick="zoomOut()">➖</button>
            <span class="zoom-level" id="zoomLevel">100%</span>
            <button class="zoom-btn" onclick="zoomIn()">➕</button>
            <button class="zoom-btn" onclick="resetZoom()">↺</button>
        </div>
    </div>

    <div class="report-wrapper" id="reportWrapper">
        @if ($chunkedData->isEmpty())
            {{-- Jika tidak ada data --}}
            <div class="a4-container">
                <div class="header-section">
                    <div class="supplier-info-kiri">
                        Supplier: {{ $activeSupplierName ?? 'Semua' }}
                    </div>
                    <h2>Listing Penerimaan Barang</h2>
                    <div class="info-tambahan">
                        <div>
                            <span class="info-label">Tanggal:</span>
                            {{ \Carbon\Carbon::now()->setTimezone('Asia/Jakarta')->format('d/m/Y') }}
                        </div>
                        <div>
                            <span class="info-label">Jam:</span>
                            {{ \Carbon\Carbon::now()->setTimezone('Asia/Jakarta')->format('H:i') }}
                        </div>
                        <div>
                            <span class="info-label">Hal:</span> 1 / 1
                        </div>
                        <div>
                            <span class="info-label">Opr:</span>
                            {{ $user_session->fname ?? 'Guest' }}
                        </div>
                    </div>
                </div>
                <div class="no-data">
                    Tidak ada data Purchase Order yang ditemukan.
                </div>
            </div>
        @else
            {{-- Loop setiap chunk = 1 halaman kertas --}}
            @foreach ($chunkedData as $pageIndex => $pageData)
                <div class="a4-container">
                    <div class="header-section">
                        <div class="supplier-info-kiri">
                            @if ($activeSupplierName)
                                Supplier: {{ $activeSupplierName }}
                            @else
                                Supplier: Semua
                            @endif
                        </div>
                        <h2>Listing Penerimaan Barang</h2>
                        @if (request('filter_date_from') || request('filter_date_to'))
                            <div class="filter-info">
                                Periode:
                                @if (request('filter_date_from'))
                                    Dari {{ \Carbon\Carbon::parse(request('filter_date_from'))->format('d/m/Y') }}
                                @endif
                                @if (request('filter_date_to'))
                                    s/d {{ \Carbon\Carbon::parse(request('filter_date_to'))->format('d/m/Y') }}
                                @endif
                            </div>
                        @endif
                        <div class="info-tambahan">
                            <div class="row-info">
                                <span class="info-label">Tanggal</span>
                                <span class="info-separator">:</span>
                                <span>{{ \Carbon\Carbon::now()->setTimezone('Asia/Jakarta')->format('d/m/Y') }}</span>
                            </div>
                            <div class="row-info">
                                <span class="info-label">Jam</span>
                                <span class="info-separator">:</span>
                                <span>{{ \Carbon\Carbon::now()->setTimezone('Asia/Jakarta')->format('H:i') }}</span>
                            </div>
                            <div class="row-info">
                                <span class="info-label">Hal</span>
                                <span class="info-separator">:</span>
                                <span>{{ $pageIndex + 1 }} / {{ $totalPages }}</span>
                            </div>
                            <div class="row-info">
                                <span class="info-label">Opr</span>
                                <span class="info-separator">:</span>
                                <span>{{ $user_session->fname ?? 'Guest' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Header Labels --}}
                    <div class="po-header-labels">
                        <div>No.PO</div>
                        <div>Tanggal</div>
                        <div>Nama Supplier</div>
                        <div>Keterangan</div>
                    </div>

                    <div class="po-detail-labels">
                        <div>Produk#</div>
                        <div>Nama Produk</div>
                        <div class="text-right">Qty</div>
                        <div class="text-right">Satuan</div>
                    </div>

                    @foreach ($pageData as $index => $penerimaanbarang)
                        {{-- PO Header (Parent) - Hitam --}}
                        <div class="po-header">
                            <div>{{ $penerimaanbarang->fstockmtno }}</div>
                            <div>{{ \Carbon\Carbon::parse($penerimaanbarang->fstockmtdate)->format('d/m/Y') }}</div>
                            <div>{{ $penerimaanbarang->supplier_name ?? $penerimaanbarang->fsupplier }}</div>
                            <div>{{ $penerimaanbarang->fket ?? 'LOCO BL' }}</div>
                        </div>

                        {{-- PO Detail Rows (Child) - Merah --}}
                        @if ($penerimaanbarang->details && $penerimaanbarang->details->count() > 0)
                            @foreach ($penerimaanbarang->details as $detail)
                                <div class="po-detail">
                                    <div>{{ $detail->fprdcode }}</div>
                                    <div>{{ $detail->product_name ?? $detail->fprdcode }}</div>
                                    <div class="text-right">{{ number_format($detail->fqty ?? 0, 2, ',', '.') }}</div>
                                    <div>{{ $detail->funit ?? 'PCS' }}</div>
                                </div>
                            @endforeach
                        @endif

                        @if (!$loop->last)
                            <div class="separator"></div>
                        @endif
                    @endforeach
                </div>
            @endforeach
        @endif
    </div>

    <script>
        // Zoom functionality
        let currentZoom = 100;
        const minZoom = 50;
        const maxZoom = 150;
        const zoomStep = 10;

        function updateZoom() {
            const wrapper = document.getElementById('reportWrapper');
            const zoomLabel = document.getElementById('zoomLevel');
            wrapper.style.transform = `scale(${currentZoom / 100})`;
            zoomLabel.textContent = currentZoom + '%';
        }

        function zoomIn() {
            if (currentZoom < maxZoom) {
                currentZoom += zoomStep;
                updateZoom();
            }
        }

        function zoomOut() {
            if (currentZoom > minZoom) {
                currentZoom -= zoomStep;
                updateZoom();
            }
        }

        function resetZoom() {
            currentZoom = 100;
            updateZoom();
        }

        // Keyboard shortcuts: Ctrl + Plus/Minus
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && (e.key === '=' || e.key === '+')) {
                e.preventDefault();
                zoomIn();
            } else if (e.ctrlKey && e.key === '-') {
                e.preventDefault();
                zoomOut();
            } else if (e.ctrlKey && e.key === '0') {
                e.preventDefault();
                resetZoom();
            }
        });

        // Auto print saat halaman dibuka (opsional)
        // window.onload = function() { window.print(); }
    </script>
</body>

</html>
