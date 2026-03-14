<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listing Penjualan</title>
    <style>
        /* Pengaturan Dasar Kertas A4 Identik dengan Listing PR */
        @page {
            size: A4 portrait;
            margin: 10mm;
        }

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
            line-height: 1.2;
        }

        /* Container A4 */
        .a4-container {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: white;
            padding: 15mm;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            position: relative;
            page-break-after: always;
        }

        /* Zoom wrapper */
        #zoom-wrapper {
            transform-origin: top center;
            transition: transform 0.2s ease;
        }

        /* Header Section */
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

        .info-tambahan {
            position: absolute;
            top: 0;
            right: 0;
            font-size: 10px;
            color: #333;
            text-align: left;
            line-height: 1.4;
        }

        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 45px;
        }

        .customer-info-kiri {
            position: absolute;
            top: 10mm;
            left: 0mm;
            font-size: 10px;
            color: #333;
            font-weight: bold;
        }

        .truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* --- SALES HEADER STYLES --- */
        .sales-header-labels,
        .sales-header {
            display: grid;
            grid-template-columns: 25mm 25mm 18mm 25mm 20mm 10mm 10mm 12mm 10mm 15mm;
            gap: 2px;
            font-size: 8px;
            padding: 8px 5px;
        }

        .sales-header-labels {
            font-weight: bold;
            background-color: #f0f0f0 !important;
            border: 1px solid #000;
            border-bottom: 2px solid #000;
            margin-bottom: 2px;
            -webkit-print-color-adjust: exact;
        }

        .sales-header {
            font-weight: bold;
            border-left: 1px solid #ccc;
            border-right: 1px solid #ccc;
            background-color: #fff;
            padding: 6px 5px;
        }

        /* --- SALES DETAIL STYLES --- */
        .sales-detail-labels,
        .sales-detail {
            display: grid;
            grid-template-columns: 25mm 40mm 25mm 15mm 15mm 18mm 12mm 20mm;
            gap: 3px;
            font-size: 8px;
            padding: 4px 5px;
        }

        .sales-detail-labels {
            font-weight: bold;
            color: #c00;
            background-color: #ffe6e6 !important;
            border: 1px solid #ccc;
            border-bottom: 1px solid #ccc;
            margin-top: 2px;
            padding: 6px 5px;
            -webkit-print-color-adjust: exact;
        }

        .sales-detail {
            color: #c00;
            border-left: 1px solid #ccc;
            border-right: 1px solid #ccc;
            background-color: #fff;
        }

        .sales-detail>div:first-child {
            padding-left: 5mm;
        }

        /* Alignment */
        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .separator {
            border-bottom: 1px solid #ccc;
            margin: 8px 0;
            clear: both;
        }

        /* Mode Cetak */
        @media print {
            body {
                background-color: white !important;
            }

            .no-print {
                display: none !important;
            }

            .a4-container {
                width: 100%;
                margin: 0;
                padding: 10mm;
                box-shadow: none;
            }

            #zoom-wrapper {
                transform: none !important;
            }
        }

        /* ===================== TOOLBAR ===================== */
        .no-print {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-print {
            background: #3b82f6;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            border: none;
            font-weight: bold;
            font-family: Arial, sans-serif;
            font-size: 13px;
        }

        .btn-print:hover {
            background: #2563eb;
        }

        .btn-excel {
            background: #107c10;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            font-size: 13px;
            display: inline-block;
            font-family: Arial, sans-serif;
        }

        .btn-excel:hover {
            background: #0e630e;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-zoom {
            background: #e5e7eb;
            color: #111;
            border: 1px solid #ccc;
            padding: 8px 13px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            line-height: 1;
            font-family: Arial, sans-serif;
        }

        .btn-zoom:hover {
            background: #d1d5db;
        }

        .zoom-display {
            font-size: 13px;
            font-family: Arial, sans-serif;
            min-width: 42px;
            text-align: center;
            color: #374151;
            font-weight: bold;
        }
    </style>
</head>

<body>
    {{-- ===================== TOOLBAR ===================== --}}
    <div class="no-print">
        <button class="btn-print" onclick="window.print()">🖨️ CETAK LAPORAN PENJUALAN</button>

        <button class="btn-zoom" onclick="zoomOut()" title="Zoom Out">−</button>
        <span class="zoom-display" id="zoom-label">100%</span>
        <button class="btn-zoom" onclick="zoomIn()" title="Zoom In">+</button>
        <button class="btn-zoom" onclick="zoomReset()" title="Reset" style="font-size:12px;">↺</button>

        <a href="{{ route('listingpenjualan.excel', request()->all()) }}" class="btn-excel">
            📊 EXCEL (FAST EXPORT)
        </a>

    </div>

    {{-- ===================== KONTEN LAPORAN ===================== --}}
    <div id="zoom-wrapper">

        @if ($chunkedData->isEmpty())
            <div class="a4-container">
                <div class="header-section">
                    <h2>Listing Penjualan</h2>
                    <div style="padding: 50px; text-align: center; color: #999;">Tidak ada data ditemukan.</div>
                </div>
            </div>
        @else
            @foreach ($chunkedData as $pageIndex => $pageData)
                <div class="a4-container">
                    <div class="header-section">
                        <div class="customer-info-kiri">
                            Customer: {{ request('cust_from') ? '[' . request('cust_from') . ']' : 'Semua' }}
                        </div>
                        <h2>Listing Penjualan</h2>
                        <div class="filter-info">
                            Periode: {{ request('date_from') ?? '...' }} s/d {{ request('date_to') ?? '...' }}
                        </div>
                        <div class="info-tambahan">
                            <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                            <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                            <div><span class="info-label">Hal</span>: {{ $pageIndex + 1 }} / {{ $totalPages }}</div>
                            <div><span class="info-label">Opr</span>: {{ $user_session->fname ?? 'admin' }}</div>
                        </div>
                    </div>

                    {{-- Header Labels --}}
                    <div class="sales-header-labels">
                        <div>No.Faktur</div>
                        <div>No.Pajak</div>
                        <div>Tanggal</div>
                        <div>Customer</div>
                        <div>Salesman</div>
                        <div class="text-right">Bruto</div>
                        <div class="text-right">Netto</div>
                        <div class="text-right">PPN</div>
                        <div class="text-right">Ongkos</div>
                        <div class="text-right">Total</div>
                    </div>

                    {{-- Detail Labels --}}
                    @if ($type == 'detail')
                        <div class="sales-detail-labels">
                            <div>Kode Barang</div>
                            <div>Nama Barang</div>
                            <div>No.Ref</div>
                            <div class="text-right">Qty.Kirim</div>
                            <div class="text-right">Qty.Jual</div>
                            <div class="text-right">@Harga</div>
                            <div class="text-center">Disc%</div>
                            <div class="text-right">Jumlah</div>
                        </div>
                    @endif

                    @foreach ($pageData as $fsono => $details)
                        @php $h = $details->first(); @endphp
                        {{-- Baris Header --}}
                        <div class="sales-header">
                            <div class="truncate">{{ $h->fsono }}</div>
                            <div class="truncate">{{ $h->ftaxno ?? '-' }}</div>
                            <div>{{ date('d/m/y', strtotime($h->fsodate)) }}</div>
                            <div class="truncate">{{ $h->fcustomername }}</div>
                            <div class="truncate">{{ $h->fsalesmanname ?? '-' }}</div>
                            <div class="text-right">{{ number_format($h->famountgross, 0, ',', '.') }}</div>
                            <div class="text-right">{{ number_format($h->famountsonet, 0, ',', '.') }}</div>
                            <div class="text-right">{{ number_format($h->famountpajak, 0, ',', '.') }}</div>
                            <div class="text-right">{{ number_format($h->fongkosangkut, 0, ',', '.') }}</div>
                            <div class="text-right">{{ number_format($h->famountso, 0, ',', '.') }}</div>
                        </div>

                        {{-- Baris Detail --}}
                        @if ($type == 'detail')
                            @foreach ($details as $d)
                                <div class="sales-detail">
                                    <div class="truncate">{{ $d->fprdcode }}</div>
                                    <div class="truncate">{{ $d->fprdname }}</div>
                                    <div class="truncate">{{ $d->frefso ?? '-' }}</div>
                                    <div class="text-right">{{ number_format($d->fqtydeliver, 2, ',', '.') }}</div>
                                    <div class="text-right">{{ number_format($d->fqty, 2, ',', '.') }}</div>
                                    <div class="text-right">{{ number_format($d->fprice, 0, ',', '.') }}</div>
                                    <div class="text-center">{{ $d->fdisc }}</div>
                                    <div class="text-right">{{ number_format($d->famount, 0, ',', '.') }}</div>
                                </div>
                            @endforeach
                        @endif

                        @if (!$loop->last)
                            <div class="separator"></div>
                        @endif
                    @endforeach

                    @if ($loop->last)
                        <div
                            style="margin-top: 20px; text-align: center; font-weight: bold; border-top: 1px solid #000; padding-top: 5px;">
                            *** Akhir Laporan Penjualan ***
                        </div>
                    @endif
                </div>
            @endforeach
        @endif

    </div>{{-- end #zoom-wrapper --}}

    {{-- ===================== ZOOM SCRIPT ===================== --}}
    <script>
        const MIN_ZOOM = 0.4;
        const MAX_ZOOM = 2.0;
        const STEP = 0.1;

        let currentZoom = 1.0;
        const wrapper = document.getElementById('zoom-wrapper');
        const label = document.getElementById('zoom-label');

        function applyZoom() {
            wrapper.style.transform = `scale(${currentZoom})`;
            label.textContent = Math.round(currentZoom * 100) + '%';

            // Adjust body height so scroll works correctly after scaling
            const scaled = wrapper.scrollHeight * currentZoom;
            document.body.style.minHeight = scaled + 'px';
        }

        function zoomIn() {
            if (currentZoom < MAX_ZOOM) {
                currentZoom = Math.min(MAX_ZOOM, +(currentZoom + STEP).toFixed(1));
                applyZoom();
            }
        }

        function zoomOut() {
            if (currentZoom > MIN_ZOOM) {
                currentZoom = Math.max(MIN_ZOOM, +(currentZoom - STEP).toFixed(1));
                applyZoom();
            }
        }

        function zoomReset() {
            currentZoom = 1.0;
            applyZoom();
        }

        // Ctrl+Scroll zoom
        document.addEventListener('wheel', function(e) {
            if (e.ctrlKey) {
                e.preventDefault();
                e.deltaY < 0 ? zoomIn() : zoomOut();
            }
        }, {
            passive: false
        });
    </script>
</body>

</html>
