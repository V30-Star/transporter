<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Pemakaian Barang - {{ $hdr->fstockmtno ?? '-' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Monitor Screen Layout */
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #000;
            background-color: #eee; /* Grayscale background on monitor */
        }

        /* Screen Simulation Styles for A4 Pages */
        .page-a4 {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: white;
            padding: 15mm;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            position: relative;
            box-sizing: border-box;
        }

        /* Header Styles */
        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .comp-name {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .comp-city {
            font-size: 10px;
            color: #333;
        }

        .title-section {
            text-align: right;
        }

        .title-so {
            font-size: 18px;
            color: #c00; /* Crimson Red accents */
            font-weight: bold;
            text-transform: uppercase;
        }

        .so-no {
            font-weight: bold;
            font-size: 11px;
            margin-top: 2px;
        }

        /* Box Container (Supplier/Info) */
        .customer-container {
            border: 1px solid #000;
            border-radius: 6px;
            padding: 8px 12px;
            width: 380px;
            min-height: 60px;
            position: relative;
            margin-top: 10px;
            float: left;
        }

        .customer-label {
            position: absolute;
            top: -8px;
            left: 10px;
            background: #fff;
            padding: 0 5px;
            font-size: 9px;
            font-weight: bold;
        }

        .info-table {
            float: right;
            font-size: 10px;
            margin-top: 10px;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 2px 4px;
        }

        .info-table .info-label {
            font-weight: bold;
            width: 50px;
        }

        /* Table Item */
        .tb {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            clear: both;
        }

        .tb th {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 6px 5px;
            font-weight: bold;
            font-size: 9px;
            text-align: left;
            background-color: #f9f9f9;
        }

        .tb td {
            padding: 6px 5px;
            vertical-align: top;
            border-bottom: 1px solid #ccc; /* Clean bottom borders only */
            font-size: 9px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* Footer Section */
        .footer-line {
            border-top: 1.5px solid #000;
            margin-top: 30px;
            clear: both;
        }

        .terbilang-box {
            float: left;
            width: 60%;
            font-size: 10px;
            margin-top: 10px;
        }

        .terbilang-box strong {
            display: inline-block;
            margin-bottom: 3px;
        }

        /* Signature */
        .sign-container {
            margin-top: 30px;
            clear: both;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .sign-table {
            border-collapse: collapse;
            width: 420px;
        }

        .sign-table td {
            border: 1px solid #000;
            width: 33.33%;
            height: 20px;
            text-align: center;
            font-size: 9px;
            font-weight: bold;
            background-color: #f9f9f9;
        }

        .sign-table .box-content {
            height: 60px;
            vertical-align: bottom;
            padding-bottom: 5px;
            font-weight: normal;
            background-color: #fff;
        }

        .timestamp {
            font-size: 9px;
            color: #555;
            font-style: italic;
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

        /* Zoom controls style */
        .no-print button {
            transition: background-color 0.2s;
        }

        /* Report wrapper for zoom scale */
        .report-wrapper {
            transform-origin: top center;
            transition: transform 0.2s ease;
        }

        /* Print Media CSS Overrides */
        @media print {
            body {
                background-color: white !important;
                margin: 0;
                padding: 0;
            }

            .page-a4 {
                width: 210mm;
                height: 297mm;
                margin: 0 auto !important;
                padding: 15mm !important;
                box-shadow: none !important;
                box-sizing: border-box;
            }

            .no-print {
                display: none !important;
            }

            @page {
                size: A4 portrait;
                margin: 0;
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

        {{-- Zoom Out --}}
        <button onclick="adjustZoom(-0.1)"
            style="padding: 6px 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold;">
            −
        </button>

        {{-- Zoom Level --}}
        <span id="zoomLabel"
            style="min-width: 48px; text-align: center; font-size: 13px; font-weight: bold; color: #333; align-self: center;">
            100%
        </span>

        {{-- Zoom In --}}
        <button onclick="adjustZoom(0.1)"
            style="padding: 6px 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold;">
            +
        </button>
    </div>

    <div class="report-wrapper" id="reportWrapper">
        <div class="page-a4">
            <div class="header-row">
                <div>
                    <div class="comp-name">{{ strtoupper($company_name) }}</div>
                    <div class="comp-city">{{ $company_city }}</div>
                </div>
                <div class="title-section">
                    <div class="title-so">Pemakaian Barang</div>
                    <div class="so-no">No. {{ $hdr->fstockmtno ?? '-' }}</div>
                </div>
            </div>

            <div style="overflow: hidden; margin-top: 10px;">
                <div class="customer-container">
                    <span class="customer-label">Supplier</span>
                    <div style="font-weight: bold; font-size: 11px;">{{ $hdr->supplier_name ?? '-' }}</div>
                    <div style="font-size: 10px; margin-top: 4px; color: #333;">
                        Gudang : {{ $hdr->fwhnamen ?? '-' }}
                    </div>
                </div>

                <table class="info-table">
                    <tr>
                        <td class="info-label">Tanggal</td>
                        <td>:</td>
                        <td>{{ $fmt($hdr->fstockmtdate) }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Hal</td>
                        <td>:</td>
                        <td>1 / 1</td>
                    </tr>
                </table>
            </div>

            <table class="tb">
                <thead>
                    <tr>
                        <th style="width: 8%;" class="text-center">No.</th>
                        <th style="width: 22%;">Kode Barang</th>
                        <th style="width: 45%;">Nama Barang</th>
                        <th style="width: 12%; text-align: right;">Qty.</th>
                        <th style="width: 13%; text-align: center;">Satuan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dt as $i => $r)
                        <tr>
                            <td class="text-center">{{ $i + 1 }}</td>
                            <td>{{ $r->product_code ?? '-' }}</td>
                            <td>
                                <div style="font-weight: bold;">{{ $r->product_name ?? '-' }}</div>
                                @if (!empty($r->fdesc))
                                    <div style="font-size: 8px; color: #555; margin-top: 2px;">({{ $r->fdesc }})</div>
                                @endif
                            </td>
                            <td class="text-right">{{ number_format((float) ($r->fqty ?? 0), 2, ',', '.') }}</td>
                            <td class="text-center">{{ $r->fsatuan ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="footer-line"></div>

            <div class="terbilang-box">
                <strong>Keterangan / Note :</strong><br>
                <span style="font-size: 10px; color: #333;">{{ $hdr->fket ?? '-' }}</span>
            </div>

            <div class="sign-container">
                <table class="sign-table">
                    <tr>
                        <td>Dibuat</td>
                        <td>User</td>
                        <td>Plant Manager</td>
                    </tr>
                    <tr>
                        <td class="box-content">{{ strtoupper($hdr->fusercreate ?? '') }}</td>
                        <td class="box-content"></td>
                        <td class="box-content"></td>
                    </tr>
                </table>
                <div class="timestamp">
                    Dicetak: {{ date('d/m/Y g:i:s A') }}
                </div>
            </div>
        </div>
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
