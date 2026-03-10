<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Product List Report</title>
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

        /* Container A4 agar tampilan layar sama dengan hasil cetak */
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

        /* ── Header Section ── */
        .header-section {
            position: relative;
            margin-bottom: 20px;
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 30px;
        }

        .header-section h2 {
            font-size: 18px;
            margin-bottom: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #c00;
            /* Merah khas laporan Anda */
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

        /* ── Dynamic Grid Table Styles ── */
        /* Kita hitung grid-template-columns secara dinamis berdasarkan kolom yang tampil */
        @php $colCount =4; // Default: No, Nama, Sat, Stok
        $gridCols = "10mm auto 15mm 20mm";

        if ($showCols['hpp']) {
            $gridCols .=" 25mm";
            $colCount++;
        }

        if ($showCols['price1']) {
            $gridCols .=" 25mm";
            $colCount++;
        }

        if ($showCols['price2']) {
            $gridCols .=" 25mm";
            $colCount++;
        }

        if ($showCols['price3']) {
            $gridCols .=" 25mm";
            $colCount++;
        }
        @endphp

        .grid-container {
            display: grid;
            grid-template-columns: {{ $gridCols }};
            gap: 2px;
            align-items: center;
        }

        .grid-header-labels {
            font-weight: bold;
            background-color: #f0f0f0 !important;
            border: 1px solid #000;
            border-bottom: 2px solid #000;
            padding: 8px 5px;
            -webkit-print-color-adjust: exact;
        }

        .grid-row {
            padding: 6px 5px;
            border-left: 1px solid #ccc;
            border-right: 1px solid #ccc;
            border-bottom: 1px dashed #aaa;
            background-color: #fff;
        }

        .truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        /* ── Footer / Grand Total ── */
        .grand-total {
            border: 2px solid #000;
            margin-top: 20px;
            padding: 10px;
            font-weight: bold;
            background-color: #333 !important;
            color: #fff;
            display: flex;
            justify-content: space-between;
            -webkit-print-color-adjust: exact;
        }

        /* ── No Print UI ── */
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
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
    </style>
</head>

<body>

    <div class="no-print">
        <button class="print-button" onclick="window.print()">🖨️ CETAK MASTER PRODUCT</button>

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

        <a href="{{ route('reportingproduct.excel', request()->query()) }}"
            style="padding: 6px 14px; background: #1d6f42; color: white; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: bold;">
            ⬇ Export Excel
        </a>

    </div>

    <div class="a4-container">
        <div class="header-section">
            <h2>Master Product List</h2>
            <div class="info-tambahan">
                <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                <div><span class="info-label">Hal</span>: 1 / 1</div>
                <div><span class="info-label">Opr</span>: {{ $user_session->fname ?? 'admin' }}</div>
            </div>
        </div>

        {{-- Header Labels --}}
        <div class="grid-container grid-header-labels">
            <div class="text-center">No</div>
            <div>Nama Barang / Kode</div>
            <div class="text-center">Sat</div>
            <div class="text-center">Stok</div>
            @if ($showCols['hpp'])
                <div class="text-right">HPP</div>
            @endif
            @if ($showCols['price1'])
                <div class="text-right">Jual 1</div>
            @endif
            @if ($showCols['price2'])
                <div class="text-right">Jual 2</div>
            @endif
            @if ($showCols['price3'])
                <div class="text-right">Jual 3</div>
            @endif
        </div>

        {{-- Data Rows --}}
        @foreach ($data as $i => $row)
            <div class="grid-container grid-row">
                <div class="text-center">{{ $i + 1 }}</div>
                <div class="truncate">
                    <span style="font-weight: bold;">{{ $row->fprdname }}</span><br>
                    <small style="color: #666;">[{{ $row->fprdcode }}]</small>
                </div>
                <div class="text-center">{{ $row->fsatuankecil }}</div>
                <div class="text-center">{{ number_format((float) $row->fstock, 0) }}</div>

                @if ($showCols['hpp'])
                    <div class="text-right">{{ number_format((float) $row->fhpp, 2, ',', '.') }}</div>
                @endif
                @if ($showCols['price1'])
                    <div class="text-right">{{ number_format((float) $row->fhargajuallevel1, 2, ',', '.') }}</div>
                @endif
                @if ($showCols['price2'])
                    <div class="text-right">{{ number_format((float) $row->fhargajuallevel2, 2, ',', '.') }}</div>
                @endif
                @if ($showCols['price3'])
                    <div class="text-right">{{ number_format((float) $row->fhargajuallevel3, 2, ',', '.') }}</div>
                @endif
            </div>
        @endforeach

        {{-- Penutup Laporan --}}
        <div class="grand-total">
            <span>TOTAL JENIS BARANG</span>
            <span>{{ count($data) }} Items</span>
        </div>
    </div>

</body>

</html>
