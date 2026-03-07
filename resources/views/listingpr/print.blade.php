<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listing Purchase Request</title>
    <style>
        /* Pengaturan Dasar Kertas A4 */
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

        .supplier-info-kiri {
            position: absolute;
            top: 10mm;
            left: 0mm;
            font-size: 10px;
            color: #333;
            font-weight: bold;
        }

        /* Truncate agar teks panjang tidak merusak tabel */
        .truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* --- PR HEADER STYLES (6 Kolom) --- */
        .po-header-labels,
        .po-header {
            display: grid;
            /* Kolom: No.PR, Tanggal, Supplier, Dibutuhkan, Deadline, User */
            grid-template-columns: 35mm 30mm 50mm 25mm 25mm 30mm;
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

        /* --- PR DETAIL STYLES (5 Kolom) --- */
        .po-detail-labels,
        .po-detail {
            display: grid;
            /* Kolom: Produk#, Nama Produk, Satuan, Qty.PR, Qty.PO */
            grid-template-columns: 35mm 45mm 15mm 25mm 25mm;
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

        /* Alignment */
        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .po-detail-labels>div:nth-child(3),
        .po-detail>div:nth-child(3) {
            text-align: center;
        }

        .po-detail-labels>div:nth-child(4),
        .po-detail-labels>div:nth-child(5),
        .po-detail>div:nth-child(4),
        .po-detail>div:nth-child(5) {
            text-align: right;
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

            @page {
                size: A4 portrait;
                margin: 10mm;
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
    <div class="no-print">
        <button class="print-button" onclick="window.print()">🖨️ CETAK LAPORAN PR</button>
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
                    <h2>Listing Purchase Request</h2>
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

                {{-- Header Labels --}}
                <div class="po-header-labels">
                    <div>No. PR</div>
                    <div>Tanggal</div>
                    <div>Nama Supplier</div>
                    <div>Tgl.Dibutuhkan</div>
                    <div>Tgl.Paling Lambat</div>
                    <div>User-id</div>
                </div>

                {{-- Detail Labels --}}
                <div class="po-detail-labels">
                    <div>Produk#</div>
                    <div>Nama Produk</div>
                    <div>Satuan</div>
                    <div class="text-right">Qty. PR</div>
                    <div class="text-right">Qty. PO</div>
                </div>

                @foreach ($pageData as $fprno => $details)
                    @php $h = $details->first(); @endphp
                    {{-- Baris Header --}}
                    <div class="po-header">
                        <div class="truncate">{{ $h->fprno }}</div>
                        <div>{{ \Carbon\Carbon::parse($h->fprdate)->format('d/m/Y') }}</div>
                        <div class="truncate">{{ $h->fsuppliername }}</div>
                        <div>{{ $h->fneeddate ? \Carbon\Carbon::parse($h->fneeddate)->format('d/m/Y') : '-' }}</div>
                        <div>{{ $h->fduedate ? \Carbon\Carbon::parse($h->fduedate)->format('d/m/Y') : '-' }}</div>
                        <div class="truncate">{{ trim($h->fusercreate) }}</div>
                    </div>

                    {{-- Baris Detail --}}
                    @foreach ($details as $d)
                        <div class="po-detail">
                            <div class="truncate">{{ $d->fprdcode }}</div>
                            <div class="truncate">{{ $d->fprdname }}</div>
                            <div class="text-center">{{ $d->fsatuan }}</div>
                            <div class="text-right">{{ number_format($d->fqty, 2, ',', '.') }}</div>
                            <div class="text-right">{{ number_format($d->fqtypo, 2, ',', '.') }}</div>
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
