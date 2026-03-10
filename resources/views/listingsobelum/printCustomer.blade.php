<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SO Belum Dikirim By Customer</title>
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

        .a4-container {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: white;
            padding: 15mm;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        /* Header */
        .header-section {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .header-left {
            font-size: 10px;
            color: #333;
            font-weight: bold;
            min-width: 80px;
            align-self: flex-end;
            white-space: nowrap;
        }

        .header-center {
            text-align: center;
            flex: 1;
        }

        .header-center h2 {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            color: #c00;
            margin-bottom: 4px;
        }

        .header-center p {
            font-size: 10px;
            color: #333;
        }

        .header-right {
            font-size: 10px;
            color: #333;
            text-align: left;
            line-height: 1.6;
            min-width: 80px;
        }

        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 45px;
        }

        /* Grid Header Kolom */
        .grid-header {
            display: grid;
            grid-template-columns: 35mm 20mm 25mm 20mm 25mm 20mm 25mm;
            gap: 2px;
            padding: 6px 5px;
            font-weight: bold;
            font-size: 9px;
            background-color: #f0f0f0;
            border: 1px solid #000;
            border-bottom: 2px solid #000;
            margin-bottom: 2px;
        }

        /* Group Customer */
        .cust-group {
            background: #ffe6e6;
            padding: 5px 7px;
            font-weight: bold;
            font-size: 10px;
            border: 1px solid #000;
            margin-top: 10px;
        }

        /* Row Data */
        .grid-row {
            display: grid;
            grid-template-columns: 35mm 20mm 25mm 20mm 25mm 20mm 25mm;
            gap: 2px;
            padding: 4px 5px;
            font-size: 9px;
            border-left: 1px solid #ccc;
            border-right: 1px solid #ccc;
            border-bottom: 1px dashed #ccc;
            align-items: center;
        }

        /* Subtotal per customer */
        .cust-subtotal {
            display: grid;
            grid-template-columns: 35mm 20mm 25mm 20mm 25mm 20mm 25mm;
            gap: 2px;
            padding: 5px;
            font-size: 9px;
            font-weight: bold;
            background: #fff0f0;
            border: 1px solid #ccc;
            border-top: 1px solid #999;
            margin-bottom: 4px;
        }

        /* Grand Total */
        .grand-total-section {
            margin-top: 20px;
            border-top: 2px solid #000;
            padding-top: 10px;
        }

        .grand-total-header {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            font-weight: bold;
            padding: 8px 15px;
            background-color: #333;
            color: white;
        }

        /* Alignment */
        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-red {
            color: red;
            font-weight: bold;
        }

        .text-blue {
            color: blue;
        }

        .truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .separator {
            border-bottom: 1px solid #ccc;
            margin: 8px 0;
        }

        /* No Print Toolbar */
        .no-print {
            position: fixed;
            top: 10px;
            left: 10px;
            display: flex;
            gap: 8px;
            z-index: 1000;
            align-items: center;
        }

        .btn-toolbar {
            padding: 6px 14px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: bold;
            color: white;
        }

        @media print {
            body {
                background-color: white !important;
            }

            .a4-container {
                width: 100%;
                margin: 0;
                padding: 10mm;
                box-shadow: none;
                page-break-after: always;
            }

            .no-print {
                display: none !important;
            }

            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }
    </style>
</head>

<body>

    {{-- Toolbar --}}
    <div class="no-print">
        <button class="btn-toolbar" style="background:#3b82f6;" onclick="window.print()">🖨️ Cetak</button>

        <button onclick="adjustZoom(-0.1)"
            style="padding:6px 12px;background:#6c757d;color:white;border:none;border-radius:4px;cursor:pointer;font-size:16px;">−</button>

        <span id="zoomLabel"
            style="min-width:48px;text-align:center;font-size:13px;font-weight:bold;color:#333;">100%</span>

        <button onclick="adjustZoom(0.1)"
            style="padding:6px 12px;background:#6c757d;color:white;border:none;border-radius:4px;cursor:pointer;font-size:16px;">+</button>

        <a href="{{ route('listingsobelum.excelCustomer', request()->query()) }}"
            style="padding:6px 14px;background:#1d6f42;color:white;border-radius:4px;text-decoration:none;font-size:13px;font-weight:bold;">
            ⬇ Export Excel
        </a>
    </div>

    <div class="report-wrapper" id="reportWrapper">
        @if ($chunkedData->isEmpty())
            <div class="a4-container">
                <div class="header-section">
                    <div class="header-center">
                        <h2>SO Belum Dikirim By Customer</h2>
                        <p>Tidak ada data ditemukan.</p>
                    </div>
                </div>
            </div>
        @else
            @php $grandTotalQty = 0; @endphp

            @foreach ($chunkedData as $pageIndex => $pageData)
                <div class="a4-container">

                    {{-- Header --}}
                    <div class="header-section">
                        <div class="header-left">
                            Customer: {{ request('cust_from') ?? 'Semua' }}
                            @if (request('cust_to'))
                                s/d {{ request('cust_to') }}
                            @endif
                        </div>
                        <div class="header-center">
                            <h2>SO Yang Belum Dikirim (By Customer)</h2>
                            <p>Periode: {{ $request->date_from }} s/d {{ $request->date_to }}</p>
                        </div>
                        <div class="header-right">
                            <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                            <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                            <div><span class="info-label">Hal</span>: {{ $pageIndex + 1 }} / {{ $totalPages }}</div>
                            <div><span class="info-label">Opr</span>: {{ $user_session->fname ?? 'User' }}</div>
                        </div>
                    </div>

                    {{-- Grid Header --}}
                    <div class="grid-header">
                        <div>No. SO</div>
                        <div>Tanggal</div>
                        <div>Nama Barang</div>
                        <div class="text-center">Satuan</div>
                        <div class="text-right">@ Harga</div>
                        <div class="text-right">Qty. Sisa</div>
                        <div class="text-right">Qty. Stok</div>
                    </div>

                    {{-- Data --}}
                    @foreach ($pageData as $custId => $rows)
                        @php $custQty = 0; @endphp

                        <div class="cust-group">
                            Customer: {{ $rows->first()->fcustomername }}
                        </div>

                        @foreach ($rows as $row)
                            @php $custQty += $row->fqty; @endphp
                            <div class="grid-row">
                                <div class="truncate">{{ $row->fsono }}</div>
                                <div>{{ date('d/m/Y', strtotime($row->fsodate)) }}</div>
                                <div class="truncate" style="font-size:8.5px;">{{ $row->fprdname }}</div>
                                <div class="text-center">{{ $row->fsatuan }}</div>
                                <div class="text-right">{{ number_format($row->fpricenet, 0, ',', '.') }}</div>
                                <div class="text-right text-red">{{ number_format($row->fqty, 2, ',', '.') }}</div>
                                <div class="text-right text-blue">{{ number_format($row->fstock, 2, ',', '.') }}</div>
                            </div>
                        @endforeach

                        {{-- Subtotal per Customer --}}
                        <div class="cust-subtotal">
                            <div colspan="5" style="grid-column: span 5; text-align:right; color:#c00;">
                                Total {{ $rows->first()->fcustomername }}
                            </div>
                            <div class="text-right text-red">{{ number_format($custQty, 2, ',', '.') }}</div>
                            <div></div>
                        </div>

                        @php $grandTotalQty += $custQty; @endphp
                    @endforeach

                    {{-- Grand Total hanya di halaman terakhir --}}
                    @if ($loop->last)
                        <div class="grand-total-section">
                            <div class="grand-total-header">
                                <span>GRAND TOTAL QTY SO BELUM DIKIRIM</span>
                                <span>{{ number_format($grandTotalQty, 2, ',', '.') }}</span>
                            </div>
                        </div>
                    @endif

                </div>
            @endforeach
        @endif
    </div>

    <script>
        let currentZoom = 1.0;

        function adjustZoom(delta) {
            currentZoom = Math.min(2.0, Math.max(0.3, currentZoom + delta));
            const wrapper = document.getElementById('reportWrapper');
            wrapper.style.transform = `scale(${currentZoom})`;
            wrapper.style.transformOrigin = 'top center';
            document.getElementById('zoomLabel').textContent = Math.round(currentZoom * 100) + '%';
        }
    </script>

</body>

</html>
