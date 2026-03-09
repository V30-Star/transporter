<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Listing Penerimaan Barang (TER)</title>
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
            page-break-after: always;
            position: relative;
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

        /* Grid Header (6 Kolom) */
        .grid-labels,
        .grid-row-mt {
            display: grid;
            grid-template-columns: 35mm 22mm 30mm 40mm 30mm 15mm;
            gap: 2px;
            font-size: 9px;
            padding: 8px 5px;
        }

        .grid-labels {
            font-weight: bold;
            background-color: #f0f0f0 !important;
            border: 1px solid #000;
            -webkit-print-color-adjust: exact;
        }

        .grid-row-mt {
            font-weight: bold;
            border-left: 1px solid #ccc;
            border-right: 1px solid #ccc;
            background-color: #fff;
        }

        /* Grid Detail (6 Kolom) */
        .detail-labels,
        .grid-row-dt {
            display: grid;
            grid-template-columns: 32mm 48mm 25mm 18mm 25mm 25mm;
            gap: 3px;
            font-size: 8px;
            padding: 4px 5px;
        }

        .detail-labels {
            font-weight: bold;
            color: #c00;
            background-color: #ffe6e6 !important;
            border: 1px solid #ccc;
            -webkit-print-color-adjust: exact;
        }

        .grid-row-dt {
            color: #c00;
            border-left: 1px solid #ccc;
            border-right: 1px solid #ccc;
            background-color: #fff;
        }

        .grid-row-dt>div:first-child {
            padding-left: 5mm;
        }

        .text-right {
            text-align: right;
        }

        .separator {
            border-bottom: 1px solid #ccc;
            margin: 8px 0;
        }

        .grand-total {
            border-top: 2px solid #000;
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
            cursor: pointer;
            border: none;
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
            .btn-excel {
                display: none !important;
            }
        }
        .btn-excel:hover {
            background-color: #0e630e !important;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

    </style>
</head>

<body>
<div class="no-print">
    <button class="btn-print" onclick="window.print()">🖨️ CETAK PENERIMAAN BARANG</button>

    <a href="{{ route('listingpenerimaan.excel', request()->all()) }}" 
       class="btn-excel" 
       style="background: #107c10; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold; margin-left: 10px; display: inline-block;">
        📊 EXCEL (FAST EXPORT)
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
                    <h2>Listing Penerimaan Barang</h2>
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

                <div class="grid-labels">
                    <div>No. Transaksi</div>
                    <div>Tanggal</div>
                    <div>Gudang</div>
                    <div>Nama Supplier</div>
                    <div class="text-right">Total Harga</div>
                    <div class="text-right">User-id</div>
                </div>

                <div class="detail-labels">
                    <div>Kode Barang</div>
                    <div>Nama Barang</div>
                    <div>Ref. PO</div>
                    <div class="text-right">Quantity</div>
                    <div class="text-right">@ Harga</div>
                    <div class="text-right">Total Harga</div>
                </div>

                @foreach ($pageData as $fstockmtno => $details)
                    @php $h = $details->first(); @endphp
                    <div class="grid-row-mt">
                        <div class="truncate">{{ $h->fstockmtno }}</div>
                        <div>{{ \Carbon\Carbon::parse($h->fstockmtdate)->format('d/m/Y') }}</div>
                        <div class="truncate">{{ $h->fwhname }}</div>
                        <div class="truncate">{{ $h->fsuppliername }}</div>
                        <div class="text-right">{{ number_format($h->famountmt, 0, ',', '.') }}</div>
                        <div class="text-right">{{ trim($h->fusercreate) }}</div>
                    </div>

                    @foreach ($details as $d)
                        <div class="grid-row-dt">
                            <div class="truncate">{{ $d->fprdcode }}</div>
                            <div class="truncate">{{ $d->fprdname }}</div>
                            <div class="truncate">{{ $d->frefpo }}</div>
                            <div class="text-right">{{ number_format($d->fqty, 2, ',', '.') }}</div>
                            <div class="text-right">{{ number_format($d->fprice, 0, ',', '.') }}</div>
                            <div class="text-right">{{ number_format($d->ftotprice, 0, ',', '.') }}</div>
                        </div>
                    @endforeach
                    <div class="separator"></div>
                @endforeach

                @if ($loop->last)
                    <div class="grand-total">
                        <span>TOTAL KESELURUHAN PENERIMAAN</span>
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
