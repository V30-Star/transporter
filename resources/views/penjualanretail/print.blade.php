<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Penjualan Retail - {{ $displayFsono ?? ($hdr->fsono ?? '-') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --fg: #000;
            --bd: #000;
            --blue: #000;
            --red: #000;
        }

        * {
            box-sizing: border-box;
            -webkit-font-smoothing: none;
            text-rendering: geometricPrecision;
        }

        body {
            margin: 0;
            background: #ececec;
            font: 10px 'Courier New', Consolas, 'Lucida Console', monospace;
            color: var(--fg);
        }

        @page {
            size: 105mm 148mm;
            margin: 0;
        }

        .sheet {
            width: 105mm;
            min-height: 148mm;
            margin: 0.2in auto;
            padding: 4mm 5mm;
            background: #fff;
            border: 1px solid #cfcfcf;
            box-shadow: 0 6px 18px rgba(0, 0, 0, .12);
            position: relative;
            box-sizing: border-box;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0px;
        }

        .comp-name {
            font-size: 13px;
            font-weight: bold;
            font-style: italic;
        }

        .title-so {
            font-size: 13px;
            color: var(--blue);
            text-decoration: underline;
            font-weight: bold;
            text-align: right;
        }

        .so-no {
            color: var(--red);
            font-weight: bold;
            font-size: 11px;
            text-align: right;
        }

        .customer-container {
            border: 1px solid #000;
            border-radius: 6px;
            padding: 4px 6px;
            width: 100%;
            position: relative;
            margin-top: 4px;
            box-sizing: border-box;
        }

        .customer-label {
            position: absolute;
            top: -7px;
            left: 10px;
            background: #fff;
            padding: 0 4px;
            font-size: 9px;
        }

        .info-table {
            font-size: 9.5px;
            margin-top: 0px;
            margin-left: auto;
        }

        .info-table td {
            padding: 0.5px 1px;
            vertical-align: top;
        }

        .tb {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            border-bottom: 1.5px solid #000;
        }

        .tb th {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 3px 2px;
            text-align: left;
            font-weight: normal;
            font-size: 9.5px;
        }

        .product-header-row {
            color: #1d4ed8;
            font-weight: bold;
        }

        .product-detail-row {
            color: #dc2626;
        }

        .table-header-main {
            color: #000;
            font-weight: bold;
            text-align: left !important;
        }

        .table-header-detail {
            color: #000;
            font-weight: bold;
            text-align: left !important;
        }

        .row-no {
            text-align: left !important;
        }

        .tb td {
            padding: 2.5px 2px;
            vertical-align: top;
            font-size: 9.5px;
        }

        .text-center, .tb th.text-center {
            text-align: center;
        }

        .text-right, .tb th.text-right {
            text-align: right;
        }

        .muted {
            color: #444;
            font-size: 11px;
        }

        .note-block {
            margin-top: 24px;
            min-height: 48px;
        }

        .note-title {
            font-weight: bold;
            margin-bottom: 4px;
        }

        .footer-line {
            border-top: 1.5px solid #000;
            margin-top: 2px;
        }

        .terbilang-box {
            float: left;
            width: 60%;
            font-style: italic;
            font-weight: bold;
            text-decoration: underline;
            font-size: 11px;
            margin-top: 5px;
        }

        .summary-box {
            float: right;
            width: 35%;
            margin-top: 5px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 1px 0;
        }

        .grand-total {
            border-top: 1px solid #000;
            border-bottom: 3px double #000;
            margin-top: 5px;
            padding: 4px 0;
            font-weight: bold;
            color: var(--blue);
            font-size: 14px;
        }

        .sign-container {
            margin-top: -50px;
            clear: both;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
        }

        .sign-table {
            border-collapse: collapse;
            width: 400px;
        }

        .sign-table td {
            border: 1px solid #000;
            width: 50%;
            height: 26px;
            text-align: center;
            padding: 4px;
        }

        .sign-table .box-content {
            height: 78px;
            vertical-align: bottom;
            padding-bottom: 6px;
        }

        .meta-right {
            font-size: 7.5px;
            text-align: right;
            line-height: 1.2;
        }

        .no-print, .print-hide {
            position: fixed;
            top: 10px;
            left: 10px;
            background: #fff;
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            z-index: 9999;
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .print-button {
            background: #007bff;
            color: white;
            border: none;
            padding: 7px 14px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 12px;
        }

        .print-button:hover {
            background: #0056b3;
        }

        @media print {
            body {
                background: #fff;
            }

            .sheet, .plain-sheet {
                margin: 0 auto;
                border: none;
                box-shadow: none;
                transform: none !important;
                page-break-after: always;
                width: 105mm;
                height: 148mm;
                max-height: 148mm;
                padding: 4mm 5mm;
                box-sizing: border-box;
                overflow: hidden;
            }

            .sheet:last-child, .plain-sheet:last-child {
                page-break-after: auto;
            }

            .no-print, .print-hide, #raw-templates {
                display: none !important;
            }

            @page {
                size: 105mm 148mm;
                margin: 0;
            }
        }

        .plain-sheet {
            font-family: 'Courier New', Consolas, 'Lucida Console', monospace;
            font-size: 10px;
            line-height: 1.25;
            white-space: pre;
            margin: 0.2in auto;
            width: 105mm;
            min-height: 148mm;
            padding: 4mm 5mm;
            background: #fff;
            border: 1px solid #cfcfcf;
            box-shadow: 0 6px 18px rgba(0, 0, 0, .12);
            box-sizing: border-box;
            color: #000;
        }
    </style>
</head>

<body>
    <div class="no-print">
        <button class="print-button" onclick="window.print()">🖨️ Cetak Dokumen</button>

        {{-- Toggle Mode Dot Matrix / Grafis --}}
        <button id="btnToggleMode" onclick="togglePrintMode()"
            style="padding: 6px 12px; background: #1e293b; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 11px; font-weight: bold;">
            📄 Mode Teks Dot Matrix
        </button>

        {{-- Salin Teks --}}
        <button id="btnCopyText" onclick="copyPlainText()"
            style="display: none; padding: 6px 10px; background: #059669; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 11px; font-weight: bold;">
            📋 Salin Teks Nota
        </button>

        {{-- Zoom Out --}}
        <button onclick="adjustZoom(-0.1)"
            style="padding: 6px 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; line-height: 1;">
            −
        </button>

        {{-- Zoom Level --}}
        <span id="zoomLabel"
            style="min-width: 48px; text-align: center; font-size: 13px; font-weight: bold; color: #333; align-self: center;">
            100%
        </span>

        {{-- Zoom In --}}
        <button onclick="adjustZoom(0.1)"
            style="padding: 6px 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; line-height: 1;">
            +
        </button>
    </div>

    @php
        $famountso = (float) ($hdr->famountso ?? 0);
        $famountgross = (float) ($hdr->famountgross ?? 0);
        $fdiscount = (float) ($hdr->fdiscount ?? 0);
        $fongkosangkut = (float) ($hdr->fongkosangkut ?? 0);
        $totalQty = collect($dt)->sum(fn ($row) => (float) ($row->fqty ?? 0));
        $totalQtyKecil = collect($dt)->sum(fn ($row) => (float) ($row->fqtykecil ?? 0));

        // Generator Plain-Text Nota 40 Kolom (Standar Dot Matrix ESC/P & POS)
        $lineWidth = 40;
        $pad = fn($str, $len, $dir = STR_PAD_RIGHT) => mb_substr(str_pad((string)$str, $len, ' ', $dir), 0, $len);
        $fmtNum = fn($n) => number_format((float)$n, 0, ',', '.');

        $compLine = strtoupper(substr($company_name ?? 'THE GROSIR', 0, $lineWidth));
        $compCentered = str_pad($compLine, $lineWidth, ' ', STR_PAD_BOTH);
        $cityCentered = str_pad(substr($company_city ?? '', 0, $lineWidth), $lineWidth, ' ', STR_PAD_BOTH);

        $dividerEqual = str_repeat('=', $lineWidth);
        $dividerDash = str_repeat('-', $lineWidth);

        $plainLines = [];
        $plainLines[] = $dividerEqual;
        $plainLines[] = $compCentered;
        if (!empty($company_city)) {
            $plainLines[] = $cityCentered;
        }
        $plainLines[] = $dividerEqual;
        $plainLines[] = $pad("FAKTUR: " . ($displayFsono ?? ($hdr->fsono ?? '-')), 24) . $pad("TGL: " . ($hdr->fsodate ? \Carbon\Carbon::parse($hdr->fsodate)->format('d/m/y') : '-'), 16, STR_PAD_LEFT);
        $custName = trim(!empty($hdr->customer_name) ? $hdr->customer_name : ($hdr->fcustno ?: 'UMUM'));
        $plainLines[] = $pad("CUST  : " . substr($custName, 0, 16), 24) . $pad("SLS: " . substr($hdr->salesman_name ?? ($hdr->fsalesname ?? '-'), 0, 10), 16, STR_PAD_LEFT);
        $plainLines[] = $dividerDash;
        $plainLines[] = "NO NAMA BARANG";
        $plainLines[] = $pad("KODE", 14) . " " . $pad("QTY", 5, STR_PAD_LEFT) . " " . $pad("@HARGA", 9, STR_PAD_LEFT) . " " . $pad("TOTAL", 9, STR_PAD_LEFT);
        $plainLines[] = $dividerDash;

        foreach ($dt as $idx => $r) {
            $no = str_pad($idx + 1, 2, ' ', STR_PAD_RIGHT);
            $pName = trim(format_product_name($r->product_name ?? '', $r->fspecification ?? $r->product_specification ?? '') ?: ($r->fdesc ?? '-'));
            $plainLines[] = $no . " " . substr($pName, 0, 37);

            $code = $pad(substr($r->fprdcode ?? '-', 0, 14), 14);
            $qty = $pad($fmtNum($r->fqty ?? 0), 5, STR_PAD_LEFT);
            $price = $pad($fmtNum($r->fprice ?? 0), 9, STR_PAD_LEFT);
            $amt = $pad($fmtNum($r->famount ?? 0), 9, STR_PAD_LEFT);
            $plainLines[] = $code . " " . $qty . " " . $price . " " . $amt;
        }

        $plainLines[] = $dividerDash;
        $plainLines[] = $pad("TOTAL QTY: " . $fmtNum($totalQty), 20) . $pad("TOTAL : " . $pad($fmtNum($famountgross), 11, STR_PAD_LEFT), 20, STR_PAD_LEFT);
        $plainLines[] = $pad("TOTAL ISI: " . $fmtNum($totalQtyKecil), 20) . $pad("DISC  : " . $pad($fmtNum($fdiscount), 11, STR_PAD_LEFT), 20, STR_PAD_LEFT);
        $plainLines[] = $pad("", 20) . $pad("BIAYA : " . $pad($fmtNum($fongkosangkut), 11, STR_PAD_LEFT), 20, STR_PAD_LEFT);
        $plainLines[] = $pad("", 20) . $pad("--------------------", 20, STR_PAD_LEFT);
        $plainLines[] = $pad("Dibuat Oleh,", 20) . $pad("G.TOT : " . $pad($fmtNum($famountso), 11, STR_PAD_LEFT), 20, STR_PAD_LEFT);
        $plainLines[] = "";
        $plainLines[] = "( " . str_pad(!empty($namattdfakturpenjualan) ? strtoupper($namattdfakturpenjualan) : (!empty($namattdpo) ? strtoupper($namattdpo) : '              '), 16, ' ', STR_PAD_BOTH) . " )";
        $plainLines[] = $dividerEqual;
        $plainLines[] = $pad("Dicetak: " . now()->format('d/m/y H:i'), 40, STR_PAD_BOTH);

        $plainTextNota = implode("\n", $plainLines);
    @endphp

    <div id="print-container"></div>
    <div id="plaintext-wrapper" style="display: none;">
        <pre class="plain-sheet" id="plaintextContent">{{ $plainTextNota }}</pre>
    </div>

    <div id="raw-templates" style="display: none;">
        {{-- Header Template --}}
        <div id="tpl-header">
            <div class="header-row">
                <div>
                    <div class="comp-name">{{ strtoupper($company_name) }}</div>
                    @if(!empty($company_city))<div style="font-size: 9.5px;">{{ $company_city }}</div>@endif
                </div>
                <div>
                    <div class="title-so">Faktur Penjualan</div>
                    <div class="so-no">No. {{ $displayFsono ?? ($hdr->fsono ?? '-') }}</div>
                </div>
            </div>

            <div class="customer-container">
                <span class="customer-label">Customer</span>
                <div style="display: flex; justify-content: space-between; align-items: stretch;">
                    <div style="width: 50%; box-sizing: border-box; padding-right: 6px; border-right: 1px solid #000;">
                        <div style="font-weight: bold; font-size: 9px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            {{ !empty(trim((string) ($hdr->customer_name ?? ''))) ? $hdr->customer_name : ($hdr->fcustno ?: '-') }}
                        </div>
                        <div style="font-size: 8.5px; margin-top: 1px; white-space: pre-line; line-height: 1.15; max-height: 28px; overflow: hidden;">
                            {{ !empty(trim((string) ($hdr->falamatkirim ?? ''))) ? $hdr->falamatkirim : ($hdr->customer_address ?? '-') }}
                        </div>
                    </div>
                    <div style="width: 50%; box-sizing: border-box; padding-left: 6px;">
                        <table class="info-table" style="margin-top: 0; width: 100%;">
                            <tr>
                                <td style="width: 38px;">Tanggal</td>
                                <td style="width: 5px;">:</td>
                                <td>{{ $fmt($hdr->fsodate) }}</td>
                            </tr>
                            <tr>
                                <td>Sales</td>
                                <td>:</td>
                                <td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $hdr->salesman_name ?? ($hdr->fsalesname ?? '-') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Table Head Template --}}
        <table id="tpl-table">
            <thead id="tpl-thead">
                <tr>
                    <th colspan="6" style="padding: 2.5px 2px; text-align: left !important;" class="table-header-main">
                        <div style="display: flex; align-items: flex-start; gap: 4px;">
                            <span style="min-width: 20px; text-align: left !important;">No.</span>
                            <span style="flex: 1; text-align: left !important;">Nama Produk</span>
                        </div>
                    </th>
                </tr>
                <tr>
                    <th style="width: 26%; text-align: left !important;" class="table-header-detail">Kode Produk</th>
                    <th style="width: 14%; text-align: right !important;" class="table-header-detail">Qty</th>
                    <th style="width: 26%; text-align: right !important;" class="table-header-detail">@ Harga</th>
                    <th style="width: 34%; text-align: right !important;" class="table-header-detail" colspan="3">Total Harga</th>
                </tr>
            </thead>
            <tbody id="raw-rows">
                @foreach ($dt as $i => $r)
                    <tr class="item-row">
                        <td colspan="6" style="color: #000; text-align: left !important; padding: 2px 2px;">
                            <div style="display: flex; align-items: flex-start; gap: 4px;">
                                <span class="row-no" style="min-width: 20px; text-align: left !important;">{{ $i + 1 }}</span>
                                <span style="flex: 1; text-align: left !important; white-space: pre-line;">{{ format_product_name($r->product_name ?? '', $r->fspecification ?? $r->product_specification ?? '') ?: (trim((string) ($r->fdesc ?? '')) ?: '-') }}</span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 26%; color: #000; text-align: left !important;">{{ $r->fprdcode ?? '-' }}</td>
                        <td class="text-right" style="width: 14%; color: #000;">{{ number_format($r->fqty ?? 0, 2, ',', '.') }}</td>
                        <td class="text-right" style="width: 26%; color: #000;">{{ number_format($r->fprice ?? 0, 2, ',', '.') }}</td>
                        <td class="text-right" style="width: 34%; color: #000;" colspan="3">{{ number_format($r->famount ?? 0, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Summary & Signature Template (Last Page) --}}
        <div id="tpl-summary">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-top: 4px; gap: 4px;">
                {{-- Kolom Kiri: Dibuat Oleh --}}
                <div style="flex: 0 0 20%; text-align: center;">
                    <div style="font-size: 8.5px;">Dibuat Oleh,</div>
                    <div style="margin-top: 30px; font-size: 8.5px; font-weight: bold; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        ( {!! !empty($namattdfakturpenjualan) ? strtoupper($namattdfakturpenjualan) : (!empty($namattdpo) ? strtoupper($namattdpo) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;') !!} )
                    </div>
                </div>

                {{-- Kolom Tengah: Total Qty & Total Isi --}}
                <div style="flex: 0 0 24%; margin-top: 2px;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 8px;">
                        <tr>
                            <td style="padding: 1px 0; white-space: nowrap;">Tot.Qty</td>
                            <td style="width: 4px; text-align: center; padding: 1px 0;">:</td>
                            <td style="text-align: right; padding: 1px 0; font-weight: bold;">{{ number_format($totalQty, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 1px 0; white-space: nowrap;">Tot.Isi</td>
                            <td style="width: 4px; text-align: center; padding: 1px 0;">:</td>
                            <td style="text-align: right; padding: 1px 0; font-weight: bold;">{{ number_format($totalQtyKecil, 2, ',', '.') }}</td>
                        </tr>
                    </table>
                </div>

                {{-- Kolom Kanan: Summary Total & Metadata --}}
                <div style="flex: 0 0 52%;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 8.5px;">
                        <tr>
                            <td style="padding: 1px 0; white-space: nowrap; width: 60px;">Total</td>
                            <td style="width: 5px; text-align: center; padding: 1px 0;">:</td>
                            <td style="text-align: right; padding: 1px 0;">{{ number_format($famountgross, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 1px 0; white-space: nowrap;">Discount</td>
                            <td style="width: 5px; text-align: center; padding: 1px 0;">:</td>
                            <td style="text-align: right; padding: 1px 0;">{{ number_format($fdiscount, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 1px 0; white-space: nowrap;">Biaya/Charge</td>
                            <td style="width: 5px; text-align: center; padding: 1px 0;">:</td>
                            <td style="text-align: right; padding: 1px 0;">{{ number_format($fongkosangkut, 2, ',', '.') }}</td>
                        </tr>
                        <tr style="font-weight: bold; font-size: 9.5px;">
                            <td style="border-top: 1px solid #000; border-bottom: 2px solid #000; padding: 1.5px 0; white-space: nowrap;">G.Total</td>
                            <td style="border-top: 1px solid #000; border-bottom: 2px solid #000; width: 5px; text-align: center; padding: 1.5px 0;">:</td>
                            <td style="border-top: 1px solid #000; border-bottom: 2px solid #000; text-align: right; padding: 1.5px 0;">{{ number_format($famountso, 2, ',', '.') }}</td>
                        </tr>
                    </table>
                    <div class="meta-right" style="margin-top: 3px;">
                        <div>Dicetak: {{ now()->format('d/m/y H:i') }} &nbsp;<span class="page-counter">Hal : 1 / 1</span></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Continued Template (Non-last Page) --}}
        <div id="tpl-continued">
            <div class="footer-line"></div>
            <div style="margin-top: 6px; text-align: right; font-style: italic; font-weight: bold; font-size: 9.5px;">
                Bersambung ke halaman <span class="next-page-num">2</span>
            </div>
            <div class="sign-container" style="margin-top: 10px;">
                <div></div>
                <div class="meta-right">
                    <div>Dicetak: {{ now()->format('d/m/y H:i') }} &nbsp;<span class="page-counter">Hal : 1 / 2</span></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function runResponsivePagination() {
            const printContainer = document.getElementById('print-container');
            const tplHeader = document.getElementById('tpl-header');
            const tplThead = document.getElementById('tpl-thead');
            const tplSummary = document.getElementById('tpl-summary');
            const tplContinued = document.getElementById('tpl-continued');
            const rawRows = Array.from(document.querySelectorAll('#raw-rows tr'));

            printContainer.innerHTML = '';

            // Usable content height for A6 portrait sheet (148mm = ~560px at 96dpi - padding/margins)
            const MAX_SHEET_CONTENT_HEIGHT = 455;

            function getContentHeight(sheet) {
                let total = 0;
                for (let i = 0; i < sheet.children.length; i++) {
                    total += sheet.children[i].offsetHeight;
                }
                return total;
            }

            function createSheet() {
                const sheet = document.createElement('div');
                sheet.className = 'sheet';

                const header = tplHeader.cloneNode(true);
                header.removeAttribute('id');
                sheet.appendChild(header);

                const table = document.createElement('table');
                table.className = 'tb';
                table.appendChild(tplThead.cloneNode(true));

                const tbody = document.createElement('tbody');
                table.appendChild(tbody);
                sheet.appendChild(table);

                const footerSlot = document.createElement('div');
                footerSlot.className = 'footer-slot';
                sheet.appendChild(footerSlot);

                printContainer.appendChild(sheet);
                return { sheet, header, table, tbody, footerSlot };
            }

            // Pair rows atomically per product item (name row + detail row)
            const itemPairs = [];
            for (let i = 0; i < rawRows.length; i += 2) {
                const pair = [rawRows[i]];
                if (i + 1 < rawRows.length) pair.push(rawRows[i + 1]);
                itemPairs.push(pair);
            }

            let currentSheet = createSheet();
            let sheets = [currentSheet];

            for (let itemIdx = 0; itemIdx < itemPairs.length; itemIdx++) {
                const pair = itemPairs[itemIdx];
                const clonedPair = pair.map(tr => tr.cloneNode(true));
                clonedPair.forEach(tr => currentSheet.tbody.appendChild(tr));

                const isLastItem = (itemIdx === itemPairs.length - 1);

                if (isLastItem) {
                    // Test if summary also fits on this sheet
                    currentSheet.footerSlot.innerHTML = '';
                    const summaryClone = tplSummary.cloneNode(true);
                    summaryClone.removeAttribute('id');
                    currentSheet.footerSlot.appendChild(summaryClone);

                    if (getContentHeight(currentSheet.sheet) > MAX_SHEET_CONTENT_HEIGHT) {
                        if (currentSheet.tbody.children.length > clonedPair.length) {
                            // Move this whole item pair to next sheet along with summary
                            clonedPair.forEach(tr => currentSheet.tbody.removeChild(tr));
                            currentSheet.footerSlot.innerHTML = '';

                            const contClone = tplContinued.cloneNode(true);
                            contClone.removeAttribute('id');
                            currentSheet.footerSlot.appendChild(contClone);

                            currentSheet = createSheet();
                            sheets.push(currentSheet);

                            clonedPair.forEach(tr => currentSheet.tbody.appendChild(tr));
                            currentSheet.footerSlot.appendChild(summaryClone);
                        } else {
                            // Only 1 item on this sheet, move summary to next sheet
                            currentSheet.footerSlot.innerHTML = '';
                            const contClone = tplContinued.cloneNode(true);
                            contClone.removeAttribute('id');
                            currentSheet.footerSlot.appendChild(contClone);

                            currentSheet = createSheet();
                            sheets.push(currentSheet);

                            currentSheet.footerSlot.appendChild(summaryClone);
                        }
                    }
                } else {
                    // Test with continuation footer
                    currentSheet.footerSlot.innerHTML = '';
                    const contTest = tplContinued.cloneNode(true);
                    contTest.removeAttribute('id');
                    currentSheet.footerSlot.appendChild(contTest);

                    if (getContentHeight(currentSheet.sheet) > MAX_SHEET_CONTENT_HEIGHT && currentSheet.tbody.children.length > clonedPair.length) {
                        // Overflow! Move this whole item pair to next sheet
                        clonedPair.forEach(tr => currentSheet.tbody.removeChild(tr));

                        currentSheet = createSheet();
                        sheets.push(currentSheet);

                        clonedPair.forEach(tr => currentSheet.tbody.appendChild(tr));
                    }
                }
            }

            // Finalize sheets footer & page numbers
            const totalPages = sheets.length;
            sheets.forEach((s, i) => {
                const pageNum = i + 1;
                const isLast = (pageNum === totalPages);

                s.footerSlot.innerHTML = '';
                if (!isLast) {
                    const cont = tplContinued.cloneNode(true);
                    cont.removeAttribute('id');
                    const nextNum = cont.querySelector('.next-page-num');
                    if (nextNum) nextNum.innerText = (pageNum + 1);
                    s.footerSlot.appendChild(cont);
                } else {
                    const summaryClone = tplSummary.cloneNode(true);
                    summaryClone.removeAttribute('id');
                    s.footerSlot.appendChild(summaryClone);
                }

                s.sheet.querySelectorAll('.page-counter').forEach(el => {
                    el.innerText = `Hal : ${pageNum} / ${totalPages}`;
                });
            });

            // Re-index all rows globally 1..N
            let globalRow = 1;
            document.querySelectorAll('#print-container tbody tr.item-row').forEach(tr => {
                const cell = tr.querySelector('.row-no');
                if (cell) cell.innerText = globalRow++;
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', runResponsivePagination);
        } else {
            runResponsivePagination();
        }

        window.addEventListener('beforeprint', runResponsivePagination);

        let currentZoom = 1.0;
        function adjustZoom(delta) {
            currentZoom = Math.min(Math.max(currentZoom + delta, 0.5), 2.0);
            document.querySelectorAll('.sheet, .plain-sheet').forEach(target => {
                target.style.transform = `scale(${currentZoom})`;
                target.style.transformOrigin = "top center";
            });
            document.getElementById("zoomLabel").innerText = `${Math.round(currentZoom * 100)}%`;
        }

        let isTextMode = false;
        function togglePrintMode() {
            isTextMode = !isTextMode;
            const printContainer = document.getElementById('print-container');
            const plaintextWrapper = document.getElementById('plaintext-wrapper');
            const btnToggle = document.getElementById('btnToggleMode');
            const btnCopy = document.getElementById('btnCopyText');

            if (isTextMode) {
                printContainer.style.display = 'none';
                plaintextWrapper.style.display = 'block';
                btnToggle.innerText = '📋 Mode Format Tabel';
                btnToggle.style.background = '#475569';
                btnCopy.style.display = 'inline-block';
            } else {
                printContainer.style.display = 'block';
                plaintextWrapper.style.display = 'none';
                btnToggle.innerText = '📄 Mode Teks Dot Matrix';
                btnToggle.style.background = '#1e293b';
                btnCopy.style.display = 'none';
            }
        }

        function copyPlainText() {
            const text = document.getElementById('plaintextContent').innerText;
            navigator.clipboard.writeText(text).then(() => {
                alert('Teks nota berhasil disalin ke clipboard!');
            }).catch(() => {
                const ta = document.createElement('textarea');
                ta.value = text;
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                alert('Teks nota berhasil disalin!');
            });
        }
    </script>
</body>

</html>
