<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listing Retur Pembelian</title>
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

        .po-header-labels,
        .po-header {
            display: grid;
            grid-template-columns: 23mm 15mm 14mm 28mm 22mm 17mm 12mm 17mm 14mm;
            gap: 2px;
            font-size: 8px;
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

        .po-detail-labels,
        .po-detail {
            display: grid;
            grid-template-columns: 30mm 70mm 20mm 22mm 25mm;
            gap: 3px;
            font-size: 8px;
            padding: 4px 5px;
        }

        .po-detail-labels {
            font-weight: bold;
            color: #c00;
            background-color: #ffe6e6;
            border: 1px solid #ccc;
            border-bottom: 1px solid #ccc;
            margin-top: 2px;
            padding: 6px 5px;
        }

        .po-detail {
            color: #c00;
            border-left: 1px solid #ccc;
            border-right: 1px solid #ccc;
            background-color: #fff;
        }

        .po-detail>div:first-child {
            padding-left: 5mm;
        }

        .po-header-labels>div:nth-child(6),
        .po-header-labels>div:nth-child(7),
        .po-header-labels>div:nth-child(8),
        .po-header>div:nth-child(6),
        .po-header>div:nth-child(7),
        .po-header>div:nth-child(8),
        .po-detail-labels>div:nth-child(3),
        .po-detail-labels>div:nth-child(4),
        .po-detail-labels>div:nth-child(5),
        .po-detail>div:nth-child(3),
        .po-detail>div:nth-child(4),
        .po-detail>div:nth-child(5) {
            text-align: right;
        }

        .separator {
            border-bottom: 1px solid #ccc;
            margin: 8px 0;
            clear: both;
        }

        .grand-total-section {
            margin-top: 20px;
            border-top: 2px solid #000;
            padding-top: 10px;
            display: flex;
            justify-content: flex-end;
        }

        .grand-total-panel {
            width: 70mm;
            border: 1px solid #000;
            font-size: 10px;
            font-weight: bold;
        }

        .grand-total-row {
            display: grid;
            grid-template-columns: 30mm 40mm;
            border-bottom: 1px solid #ccc;
        }

        .grand-total-row:last-child {
            border-bottom: none;
            background-color: #333;
            color: white;
        }

        .grand-total-row div {
            padding: 6px 8px;
        }

        .grand-total-row div:last-child {
            text-align: right;
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

        .supplier-info-kiri {
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

        .no-print {
            position: fixed;
            top: 10px;
            left: 10px;
            display: flex;
            gap: 8px;
            z-index: 1000;
        }

        .print-button {
            background-color: #3b82f6;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            border: none;
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
    <div class="no-print">
        <button class="print-button" onclick="window.print()">🖨️ Cetak Laporan</button>
        <button onclick="adjustZoom(-0.1)"
            style="padding: 6px 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;">
            −
        </button>
        <span id="zoomLabel"
            style="min-width: 48px; text-align: center; font-size: 13px; font-weight: bold; color: #333;">
            100%
        </span>
        <button onclick="adjustZoom(0.1)"
            style="padding: 6px 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;">
            +
        </button>
    </div>

    <div class="report-wrapper" id="reportWrapper">
        @if ($results->isEmpty())
            <div class="a4-container">
                <div class="header-section">
                    <h2>Listing Retur Pembelian</h2>
                    <div style="padding: 50px; text-align: center; color: #999;">Tidak ada data ditemukan.</div>
                </div>
            </div>
        @else
            @php
                $grouped = $results->groupBy('fstockmtid');
                $grandHarga = 0;
                $grandPpn = 0;
                $grandRetur = 0;
            @endphp
            <div class="a4-container">
                <div class="header-section">
                    <h2>Listing Retur Pembelian</h2>
                    <div class="filter-info">
                        Periode:
                        {{ request('date_from') ? \Carbon\Carbon::parse(request('date_from'))->format('d/m/Y') : '...' }}
                        s/d
                        {{ request('date_to') ? \Carbon\Carbon::parse(request('date_to'))->format('d/m/Y') : '...' }}
                    </div>
                    <div class="info-tambahan">
                        <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                        <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                        <div><span class="info-label">Opr</span>: {{ $user_session->fname ?? 'User' }}</div>
                    </div>
                </div>

                <div class="po-header-labels">
                    <div>No.Transaksi</div>
                    <div>Tanggal</div>
                    <div>Supp#</div>
                    <div>Nama Supplier</div>
                    <div>Keterangan</div>
                    <div>Total Harga</div>
                    <div>PPN</div>
                    <div>Total Retur</div>
                    <div>User-Id</div>
                </div>

                <div class="po-detail-labels">
                    <div>Kode Barang</div>
                    <div>Nama Barang</div>
                    <div>Quantity</div>
                    <div>@ Harga</div>
                    <div>Total Harga</div>
                </div>

                @foreach ($grouped as $items)
                    @php
                        $h = $items->first();
                        $totalHarga = (float) $h->header_amount;
                        $ppn = (float) $h->header_ppn;
                        $totalRetur = (float) $h->header_total;
                        $grandHarga += $totalHarga;
                        $grandPpn += $ppn;
                        $grandRetur += $totalRetur;
                    @endphp
                    <div class="po-header">
                        <div class="truncate">{{ $h->fstockmtno }}</div>
                        <div>{{ \Carbon\Carbon::parse($h->fstockmtdate)->format('d/m/Y') }}</div>
                        <div class="truncate">{{ $h->fsupplier }}</div>
                        <div class="truncate">{{ $h->fsuppliername }}</div>
                        <div class="truncate">{{ $h->fket }}</div>
                        <div>{{ number_format($totalHarga, 2, ',', '.') }}</div>
                        <div>{{ number_format($ppn, 2, ',', '.') }}</div>
                        <div style="color: blue;">{{ number_format($totalRetur, 2, ',', '.') }}</div>
                        <div class="truncate">{{ trim($h->fusercreate) }}</div>
                    </div>

                    @if ($detailMode)
                        @foreach ($items as $d)
                            <div class="po-detail">
                                <div class="truncate">{{ $d->fprdcode }}</div>
                                <div class="truncate">{{ $d->fprdname }}</div>
                                <div>{{ number_format($d->fqtykecil, 2, ',', '.') }}</div>
                                <div>{{ number_format($d->fprice, 2, ',', '.') }}</div>
                                <div>{{ number_format($d->famount, 2, ',', '.') }}</div>
                            </div>
                        @endforeach
                    @endif

                    @if (!$loop->last)
                        <div class="separator"></div>
                    @endif
                @endforeach

                <div class="grand-total-section">
                    <div class="grand-total-panel">
                        <div class="grand-total-row">
                            <div>Total Harga:</div>
                            <div>{{ number_format($grandHarga, 2, ',', '.') }}</div>
                        </div>
                        <div class="grand-total-row">
                            <div>PPN:</div>
                            <div>{{ number_format($grandPpn, 2, ',', '.') }}</div>
                        </div>
                        <div class="grand-total-row">
                            <div>Grand Total:</div>
                            <div>{{ number_format($grandRetur, 2, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</body>

</html>

<script>
    let currentZoom = 1.0;

    function adjustZoom(delta) {
        currentZoom = Math.min(2.0, Math.max(0.3, currentZoom + delta));
        document.getElementById('reportWrapper').style.transform = `scale(${currentZoom})`;
        document.getElementById('reportWrapper').style.transformOrigin = 'top center';
        document.getElementById('zoomLabel').textContent = Math.round(currentZoom * 100) + '%';
    }
</script>
