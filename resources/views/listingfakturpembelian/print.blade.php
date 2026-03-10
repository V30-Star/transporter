<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listing Faktur Pembelian</title>
    <style>
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

        .header-section {
            position: relative;
            margin-bottom: 15px;
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }

        .header-section h2 {
            font-size: 18px;
            margin-bottom: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #c00;
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

        /* --- Header Table (8 Kolom) --- */
        .grid-header {
            display: grid;
            grid-template-columns: 30mm 20mm 18mm 32mm 25mm 15mm 15mm 27mm;
            gap: 2px;
            padding: 8px 5px;
            font-size: 9px;
        }

        .labels {
            font-weight: bold;
            background-color: #f0f0f0 !important;
            border: 1px solid #000;
            border-bottom: 2px solid #000;
            margin-bottom: 2px;
            -webkit-print-color-adjust: exact;
        }

        .po-header {
            font-weight: bold;
            border-left: 1px solid #ccc;
            border-right: 1px solid #ccc;
            background-color: #fff;
        }

        /* --- Detail Table (8 Kolom) --- */
        .grid-detail {
            display: grid;
            grid-template-columns: 20mm 35mm 22mm 18mm 15mm 22mm 22mm 20mm;
            gap: 2px;
            padding: 4px 5px;
            font-size: 8px;
            color: #c00;
        }

        .detail-labels {
            font-weight: bold;
            background-color: #ffe6e6 !important;
            border: 1px solid #ccc;
            border-bottom: 1px solid #ccc;
            margin-top: 2px;
            -webkit-print-color-adjust: exact;
        }

        .po-detail {
            border-left: 1px solid #ccc;
            border-right: 1px solid #ccc;
            background-color: #fff;
        }

        .po-detail>div:first-child {
            padding-left: 5mm;
        }

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

        .grand-total {
            border: 2px solid #000;
            margin-top: 20px;
            padding: 10px;
            font-weight: bold;
            background: #333;
            color: #fff;
            display: flex;
            justify-content: space-between;
        }

        .no-print {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1000;
        }

        .btn-print {
            background: #3b82f6;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .a4-container {
                width: 100%;
                margin: 0;
                padding: 10mm;
                box-shadow: none;
            }
        }
    </style>
</head>

<body>
    <div class="no-print">
        <button class="btn-print" onclick="window.print()">🖨️ CETAK LAPORAN</button>
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

        <a href="{{ route('listingfakturpembelian.excel', request()->query()) }}"
            style="padding: 6px 14px; background: #1d6f42; color: white; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: bold;">
            ⬇ Export Excel
        </a>
    </div>
    @if ($chunkedData->isEmpty())
        <div class="a4-container">
            <div class="header-section">
                <h2>Listing Purchase Request</h2>
                <div style="padding: 50px; text-align: center; color: #999;">Tidak ada data ditemukan.</div>
            </div>
        </div>
    @else
        @foreach ($chunkedData as $pageIndex => $pageData)
            <div class="a4-container">
                <div class="header-section">
                    <div class="supplier-info-kiri">
                        Supplier: {{ request('sup_from') ? '[' . request('sup_from') . ']' : 'Semua' }}
                    </div>
                    <h2>Listing Faktur Pembelian</h2>
                    <div class="filter-info">
                        Periode:
                        {{ request('date_from') ? \Carbon\Carbon::parse(request('date_from'))->format('d/m/Y') : '...' }}
                        s/d
                        {{ request('date_to') ? \Carbon\Carbon::parse(request('date_to'))->format('d/m/Y') : '...' }}
                    </div>
                    <div class="info-tambahan">
                        <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                        <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                        <div><span class="info-label">Hal</span>: {{ $pageIndex + 1 }} / {{ $totalPages }}</div>
                        <div><span class="info-label">Opr</span>: {{ $user_session->fname ?? 'admin' }}</div>
                    </div>
                </div>

                <div class="grid-header labels">
                    <div>No. Transaksi</div>
                    <div>Tanggal</div>
                    <div>Type</div>
                    <div>Nama Supplier</div>
                    <div class="text-right">Total Harga</div>
                    <div class="text-right">PPN</div>
                    <div class="text-right">Total Faktur</div>
                </div>

                <div class="grid-detail detail-labels">
                    <div>Kode Barang</div>
                    <div>Nama Barang</div>
                    <div>No.Ref</div>
                    <div class="text-right">Quantity</div>
                    <div class="text-center">Adj</div>
                    <div class="text-right">@ Harga</div>
                    <div class="text-right">@ Biaya</div>
                    <div class="text-right">Jumlah</div>
                </div>

                @foreach ($pageData as $fnotransaksi => $details)
                    @php $h = $details->first(); @endphp
                    <div class="po-header grid-header">
                        <div class="truncate">{{ $h->fstockmtno }}</div>
                        <div>{{ \Carbon\Carbon::parse($h->fstockmtdate)->format('d/m/Y') }}</div>
                        <div>{{ $h->ftype }}</div>
                        <div class="truncate">{{ $h->fsuppliername }}</div>
                        <div class="text-right">{{ number_format($h->famount, 0, ',', '.') }}</div>
                        <div class="text-right">{{ number_format($h->famountpajak, 0, ',', '.') }}</div>
                        <div class="text-right" style="color: blue;">{{ number_format($h->famountmt, 0, ',', '.') }}
                        </div>
                    </div>

                    @foreach ($details as $d)
                        <div class="po-detail grid-detail">
                            <div class="truncate">{{ $d->fprdcode }}</div>
                            <div class="truncate">{{ $d->fprdname }}</div>
                            <div class="truncate">{{ $d->frefdtno }}</div>
                            <div class="text-right">{{ number_format($d->fqty, 2, ',', '.') }}</div>
                            <div class="text-center">{{ number_format($d->fqtyremain, 0) }}</div>
                            <div class="text-right">{{ number_format($d->fprice, 0, ',', '.') }}</div>
                            <div class="text-right">{{ number_format($d->fbiaya, 0, ',', '.') }}</div>
                            <div class="text-right">{{ number_format($d->ftotprice, 0, ',', '.') }}</div>
                        </div>
                    @endforeach
                    <div class="separator"></div>
                @endforeach

                @if ($loop->last)
                    <div class="grand-total">
                        <span>TOTAL KESELURUHAN FAKTUR PEMBELIAN</span>
                        <span>Rp {{ number_format($totalLaporan, 0, ',', '.') }}</span>
                    </div>
                @endif
                @if ($loop->last)
                    <div
                        style="margin-top: 20px; text-align: center; font-weight: bold; border-top: 1px solid #000; padding-top: 5px;">
                        *** Akhir Laporan ***
                    </div>
                @endif
            </div>
        @endforeach
    @endif
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
