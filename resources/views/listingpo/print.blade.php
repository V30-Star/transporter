<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Listing Order Pembelian (PO)</title>
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
        .po-header-labels,
        .po-header {
            display: grid;
            grid-template-columns: 35mm 20mm 50mm 25mm 30mm 40mm;
            gap: 2px;
            font-size: 9px;
            padding: 8px 5px;
        }

        .po-header-labels {
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
            padding: 6px 5px;
        }

        /* Grid Detail (4 Kolom Sesuai Request: Produk#, Nama, Satuan, Qty.PO) */
        .po-detail-labels,
        .po-detail {
            display: grid;
            grid-template-columns: 35mm 80mm 25mm 35mm;
            gap: 3px;
            font-size: 8px;
            padding: 4px 5px;
        }

        .po-detail-labels {
            font-weight: bold;
            color: #c00;
            background-color: #ffe6e6 !important;
            border: 1px solid #ccc;
            border-bottom: 1px solid #ccc;
            margin-top: 2px;
            padding: 6px 5px;
            -webkit-print-color-adjust: exact;
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
        }

        .no-print {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1000;
        }

        .print-button {
            background-color: #3b82f6;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            border: none;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="no-print"><button class="print-button" onclick="window.print()">🖨️ CETAK LAPORAN PO</button></div>

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
                    <h2>Listing Order Pembelian</h2>
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

                <div class="po-header-labels">
                    <div>No. PO</div>
                    <div>Tanggal</div>
                    <div>Nama Supplier</div>
                    <div>Tgl.Dibutuhkan</div>
                    <div>Tgl.Paling Lambat</div>
                    <div>User-id</div>
                </div>

                <div class="po-detail-labels">
                    <div>Produk#</div>
                    <div>Nama Produk</div>
                    <div class="text-center">Satuan</div>
                    <div class="text-right">Qty. PO</div>
                </div>

                @foreach ($pageData as $fpono => $details)
                    @php $h = $details->first(); @endphp
                    <div class="po-header">
                        <div class="truncate">{{ $h->fpono }}</div>
                        <div>{{ \Carbon\Carbon::parse($h->fpodate)->format('d/m/Y') }}</div>
                        <div class="truncate">{{ $h->fsuppliername }}</div>
                        <div>-</div> {{-- Isi jika ada kolom tgl dibutuhkan di tr_poh --}}
                        <div>-</div> {{-- Isi jika ada kolom deadline di tr_poh --}}
                        <div>{{ trim($h->fusercreate) }}</div>
                    </div>

                    @foreach ($details as $d)
                        <div class="po-detail">
                            <div class="truncate">{{ $d->fprdcode }}</div>
                            <div class="truncate">{{ $d->fprdname }}</div>
                            <div class="text-center">{{ $d->fsatuan }}</div>
                            <div class="text-right">{{ number_format($d->fqty, 2, ',', '.') }}</div>
                        </div>
                    @endforeach

                    @if (!$loop->last)
                        <div class="separator"></div>
                    @endif
                @endforeach
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
