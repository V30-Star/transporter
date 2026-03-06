<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listing Sales Order</title>
    <style>
        /* CSS Sama Persis dengan Template Faktur Pembelian */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; color: #000; background-color: #f5f5f5; }
        .a4-container { width: 210mm; min-height: 297mm; margin: 20px auto; background: white; padding: 15mm; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        .header-section { position: relative; margin-bottom: 15px; text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header-section h2 { font-size: 18px; margin-bottom: 8px; font-weight: bold; text-transform: uppercase; color: #c00; }
        .filter-info { font-size: 10px; color: #333; margin-bottom: 5px; }

        /* --- SO HEADER STYLES (9 Kolom) --- */
        .po-header-labels, .po-header {
            display: grid;
            /* Kolom: NoTrans, Tgl, Cust, Sls, Gross, Disc, PPN, Total, Cls */
            grid-template-columns: 32mm 18mm 35mm 15mm 15mm 15mm 10mm 15mm 20mm;
            gap: 2px;
            font-size: 9px;
            padding: 8px 5px;
        }
        .po-header-labels { font-weight: bold; background-color: #f0f0f0; border: 1px solid #000; border-bottom: 2px solid #000; margin-bottom: 2px; }
        .po-header { font-weight: bold; border-left: 1px solid #ccc; border-right: 1px solid #ccc; background-color: #fff; padding: 6px 5px; }

        /* --- SO DETAIL STYLES (6 Kolom) --- */
        .po-detail-labels, .po-detail {
            display: grid;
            /* Kolom: Kode, Nama, Qty, QtySisa, Harga, Total */
            grid-template-columns: 30mm 65mm 20mm 20mm 15mm 20mm;
            gap: 3px;
            font-size: 8px;
            padding: 4px 5px;
        }
        .po-detail-labels { font-weight: bold; color: #c00; background-color: #ffe6e6; border: 1px solid #ccc; border-bottom: 1px solid #ccc; margin-top: 2px; padding: 6px 5px; }
        .po-detail { color: #c00; border-left: 1px solid #ccc; border-right: 1px solid #ccc; background-color: #fff; }
        .po-detail>div:first-child { padding-left: 5mm; }

        /* Alignment */
        .po-header-labels>div:nth-child(5), .po-header-labels>div:nth-child(6), .po-header-labels>div:nth-child(7), .po-header-labels>div:nth-child(8),
        .po-header>div:nth-child(5), .po-header>div:nth-child(6), .po-header>div:nth-child(7), .po-header>div:nth-child(8) { text-align: right; }
        .po-header-labels>div:nth-child(9), .po-header>div:nth-child(9) { text-align: center; }
        
        .po-detail-labels>div:nth-child(3), .po-detail-labels>div:nth-child(4), .po-detail-labels>div:nth-child(5), .po-detail-labels>div:nth-child(6),
        .po-detail>div:nth-child(3), .po-detail>div:nth-child(4), .po-detail>div:nth-child(5), .po-detail>div:nth-child(6) { text-align: right; }

        .separator { border-bottom: 1px solid #ccc; margin: 8px 0; clear: both; }
        .grand-total-section { margin-top: 20px; border-top: 2px solid #000; padding-top: 10px; }
        .grand-total-header { display: flex; justify-content: space-between; font-size: 10px; font-weight: bold; padding: 8px 15px; background-color: #333; color: white; }

        .info-tambahan { position: absolute; top: 0; right: 0; font-size: 10px; color: #333; text-align: left; line-height: 1.4; }
        .info-label { font-weight: bold; display: inline-block; width: 45px; }
        .supplier-info-kiri { position: absolute; top: 10mm; left: 0mm; font-size: 10px; color: #333; font-weight: bold; }

        /* Utility */
        .truncate { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .no-print { position: fixed; top: 10px; left: 10px; display: flex; gap: 8px; z-index: 1000; }
        .print-button { background-color: #3b82f6; color: white; padding: 10px 20px; border-radius: 5px; cursor: pointer; border:none; }
        
        @media print {
            body { background-color: white !important; }
            .a4-container { width: 100%; margin: 0; padding: 10mm; box-shadow: none; page-break-after: always; }
            .no-print { display: none !important; }
            @page { size: A4 portrait; margin: 10mm; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="print-button" onclick="window.print()">🖨️ Cetak Laporan</button>
    </div>

    <div class="report-wrapper" id="reportWrapper">
        @if ($chunkedData->isEmpty())
            <div class="a4-container">
                <div class="header-section"><h2>Listing Sales Order</h2><div class="no-data">Tidak ada data ditemukan.</div></div>
            </div>
        @else
            @foreach ($chunkedData as $pageIndex => $pageData)
                <div class="a4-container">
                    <div class="header-section">
                        <div class="supplier-info-kiri">Customer: {{ request('cust_from') ?? 'Semua' }}</div>
                        <h2>Listing Sales Order</h2>
                        <div class="filter-info">
                            Periode: {{ request('date_from') ? \Carbon\Carbon::parse(request('date_from'))->format('d/m/Y') : '...' }} 
                            s/d {{ request('date_to') ? \Carbon\Carbon::parse(request('date_to'))->format('d/m/Y') : '...' }}
                        </div>
                        <div class="info-tambahan">
                            <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                            <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                            <div><span class="info-label">Hal</span>: {{ $pageIndex + 1 }} / {{ $totalPages }}</div>
                            <div><span class="info-label">Opr</span>: {{ $user_session->fname ?? 'User' }}</div>
                        </div>
                    </div>

                    {{-- Header Labels --}}
                    <div class="po-header-labels">
                        <div>No. Transaksi</div>
                        <div>Tanggal</div>
                        <div>Nama Customer</div>
                        <div>Salesman</div>
                        <div>Total Harga</div>
                        <div>Disc</div>
                        <div>PPN</div>
                        <div>Total SO</div>
                        <div>Clsose?</div>
                    </div>

                    {{-- Detail Labels --}}
                    <div class="po-detail-labels">
                        <div>Kode Barang</div>
                        <div>Nama Barang</div>
                        <div>Quantity</div>
                        <div>Qty. Sisa</div>
                        <div>@ Harga</div>
                        <div>Total Harga</div>
                    </div>

                    @foreach ($pageData as $mt)
                        <div class="po-header">
                            <div class="truncate">{{ $mt->fsono }}</div>
                            <div>{{ \Carbon\Carbon::parse($mt->fsodate)->format('d/m/Y') }}</div>
                            <div class="truncate">{{ $mt->fcustomername }}</div>
                            <div class="truncate">{{ $mt->fsalesmanname }}</div>
                            <div>{{ number_format($mt->famountgross, 0, ',', '.') }}</div>
                            <div>{{ number_format($mt->fdiscount, 0, ',', '.') }}</div>
                            <div>{{ number_format($mt->famountpajak, 0, ',', '.') }}</div>
                            <div style="color: blue;">{{ number_format($mt->famountso, 0, ',', '.') }}</div>
                            <div>{{ $mt->fclose == '1' ? 'Y' : 'N' }}</div>
                        </div>

                        @foreach ($mt->details as $dt)
                            <div class="po-detail">
                                <div class="truncate">{{ $dt->fitemno }}</div>
                                <div class="truncate">{{ $dt->fitemdesc }}</div>
                                <div>{{ number_format($dt->fqty, 2, ',', '.') }}</div>
                                <div>0.00</div>
                                <div>{{ number_format($dt->fprice, 0, ',', '.') }}</div>
                                <div>{{ number_format($dt->famount, 0, ',', '.') }}</div>
                            </div>
                        @endforeach
                        
                        @if (!$loop->last)
                            <div class="separator"></div>
                        @endif
                    @endforeach

                    @if ($loop->last)
                        <div class="grand-total-section">
                            <div class="grand-total-header">
                                <span>GRAND TOTAL LISTING SALES ORDER</span>
                                <span>Rp {{ number_format($totalFaktur, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        @endif
    </div>
</body>
</html>