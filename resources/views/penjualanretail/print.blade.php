<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Penjualan Retail - {{ $displayFsono ?? ($hdr->fsono ?? '-') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root {
            --fg: #000;
            --bd: #000;
            --blue: #000;
            --red: #000;
        }

        * {
            box-sizing: border-box;
        }

        /* Sans reguler kayak versi Delphi; bold/monospace kecil numpuk di dot matrix */
        body {
            margin: 0;
            background: #ececec;
            font: 12px Arial, Helvetica, sans-serif;
            color: var(--fg);
        }

        .sheet td {
            font-weight: normal !important;
        }

        .sheet tr.gt td {
            font-weight: bold !important;
        }

        /* Ukuran kertas: A5 portrait. Kalau form fisik beda, ganti di sini + .sheet (layar & print) */
        @page {
            size: 5.5in 5in;
            margin: 0;
        }

        /* Layar = print, biar pagination ngukur di lebar yang sama */
        .sheet {
            width: 5.5in;
            height: 5in;
            overflow: hidden;
            margin: 0.2in auto;
            /* Kanan 1.90in: area aman kertas ~3.68in (5.83 - 0.2 - 1.95). Sesuaikan kalau kertas beda */
            padding: 0.3in 1.90in 0.2in 0.2in;
            background: #fff;
            border: 1px solid #cfcfcf;
            box-shadow: 0 6px 18px rgba(0, 0, 0, .12);
            position: relative;
            box-sizing: border-box;
            font-weight: normal;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 5px;
        }

        .comp-name {
            font-size: 17px;
            font-weight: bold;
            font-style: italic;
            text-align: left;
        }

        .comp-city {
            font-size: 11px;
            font-weight: bold;
            text-align: left;
            margin-top: 1px;
        }

        .title-so {
            font-size: 17px;
            color: var(--blue);
            text-decoration: underline;
            font-weight: bold;
            text-align: right;
        }

        .so-no {
            color: var(--red);
            font-weight: bold;
            font-size: 13px;
            text-align: right;
        }

        .customer-container {
            border: 1.5px solid #000;
            border-radius: 0;
            padding: 9px 5px 3px;
            width: 100%;
            position: relative;
            margin-top: 4px;
            box-sizing: border-box;
            font-weight: normal;
        }

        .customer-label {
            position: absolute;
            top: -7px;
            left: 8px;
            background: #fff;
            padding: 0 4px;
            font-size: 11.5px;
            font-weight: normal;
            line-height: 1;
            z-index: 2;
        }

        .info-table {
            font-size: 12px;
            font-weight: bold;
            margin-top: 0px;
            margin-left: auto;
        }

        .info-table td {
            padding: 0.5px 1px;
            vertical-align: top;
            font-weight: bold;
        }

        .tb {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            border-bottom: 1px solid #000;
            font-weight: bold;
        }

        .tb th {
            border-top: 1.5px solid #000;
            border-bottom: 1.5px solid #000;
            padding: 3px 2px;
            text-align: left;
            font-weight: bold;
            font-size: 12px;
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
            font-size: 11.5px;
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
            border-top: 1px solid #000;
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
            font-size: 9.5px;
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
            html, body {
                width: 5.5in !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            * {
                color: #000 !important;
                -webkit-text-fill-color: #000 !important;
                background: transparent !important;
                box-shadow: none !important;
                text-shadow: none !important;
            }

            .sheet {
                margin: 0 !important;
                border: none !important;
                box-shadow: none !important;
                transform: none !important;
                width: 5.5in !important;
                max-width: 5.5in !important;
                height: 5in !important;
                max-height: 5in !important;
                padding: 0.3in 1.90in 0.2in 0.2in !important;
                box-sizing: border-box !important;
                overflow: hidden !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                page-break-after: always !important;
                break-after: page !important;
            }

            .sheet:last-child,
            .sheet:only-child {
                page-break-after: auto !important;
                break-after: auto !important;
            }

            .customer-label {
                background: #fff !important;
            }

            .no-print, .print-hide, #raw-templates {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="no-print">
        <button class="print-button" onclick="window.print()">🖨️ Cetak Dokumen</button>

        {{-- Button Print Mode Text (Baru) --}}
        <button type="button" id="btnTextPrint" class="print-button" onclick="printModeText()"
            style="background: #198754;" title="Cetak langsung ke Epson LX-310 (ESC/P Text Mode)">
            ⚡ Print Mode Text
        </button>
        <span id="textPrintStatus" style="font-size: 12px; font-weight: bold; margin-left: 4px; display: none;"></span>

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
    @endphp

    <div id="print-container"></div>

    <div id="raw-templates" style="display: none;">
        {{-- Header Template --}}
        <div id="tpl-header">
            <div class="header-row">
                <div>
                    <div class="comp-name">{{ strtoupper($company_name) }}</div>
                    @if(!empty($company_city))<div class="comp-city">{{ $company_city }}</div>@endif
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
                        <div style="font-size: 11.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            {{ !empty(trim((string) ($hdr->customer_name ?? ''))) ? $hdr->customer_name : ($hdr->fcustno ?: '-') }}
                        </div>
                        <div style="font-size: 11px; margin-top: 1px; white-space: pre-line; line-height: 1.15; max-height: 28px; overflow: hidden;">
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
                            <tr>
                                <td>Hal</td>
                                <td>:</td>
                                <td><span class="page-counter">1 / 1</span></td>
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
                    <th style="width: 27%; text-align: left !important; font-weight: bold;" class="table-header-detail">Kode Produk</th>
                    <th style="width: 18%; text-align: right !important; font-weight: bold;" class="table-header-detail">Qty</th>
                    <th style="width: 25%; text-align: right !important; font-weight: bold;" class="table-header-detail">@ Harga</th>
                    <th style="width: 30%; text-align: right !important; font-weight: bold;" class="table-header-detail" colspan="3">Total Harga</th>
                </tr>
            </thead>
            <tbody id="raw-rows">
                @foreach ($dt as $i => $r)
                    <tr class="item-row">
                        <td colspan="6" style="color: #000; text-align: left !important; padding: 2px 2px; font-weight: bold;">
                            <div style="display: flex; align-items: flex-start; gap: 4px;">
                                <span class="row-no" style="min-width: 20px; text-align: left !important;">{{ $i + 1 }}</span>
                                <span style="flex: 1; text-align: left !important; white-space: pre-line;">{{ format_product_name($r->product_name ?? '', $r->fspecification ?? $r->product_specification ?? '') ?: (trim((string) ($r->fdesc ?? '')) ?: '-') }}</span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 27%; color: #000; text-align: left !important; font-weight: bold;">{{ $r->fprdcode ?? '-' }}</td>
                        <td class="text-right" style="width: 18%; color: #000; white-space: nowrap; font-weight: bold;">{{ number_format($r->fqty ?? 0, 0, ',', '.') }} {{ $r->fsatuan }}</td>
                        <td class="text-right" style="width: 25%; color: #000; font-weight: bold;">{{ number_format($r->fprice ?? 0, 0, ',', '.') }}</td>
                        <td class="text-right" style="width: 30%; color: #000; font-weight: bold;" colspan="3">{{ number_format($r->famount ?? 0, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Summary & Signature Template (Last Page) --}}
        <div id="tpl-summary">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-top: 4px; gap: 4px;">
                {{-- Kolom Kiri: Dibuat Oleh --}}
                <div style="flex: 0 0 20%; text-align: center;">
                    <div style="font-size: 11.5px; font-weight: normal;">Dibuat Oleh,</div>
                    <div style="margin-top: 26px; font-size: 11.5px; font-weight: normal; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        ( {!! !empty($namattdfakturpenjualan) ? strtoupper($namattdfakturpenjualan) : (!empty($namattdpo) ? strtoupper($namattdpo) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;') !!} )
                    </div>
                </div>

                {{-- Kolom Tengah: Total Qty & Total Isi --}}
                <div style="flex: 0 0 24%; margin-top: 2px;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 11.5px; font-weight: bold;">
                        <tr>
                            <td style="padding: 1px 0; white-space: nowrap;">Tot.Qty</td>
                            <td style="width: 4px; text-align: center; padding: 1px 0;">:</td>
                            <td style="text-align: right; padding: 1px 0; font-weight: bold;">{{ number_format($totalQty, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 1px 0; white-space: nowrap;">Tot.Isi</td>
                            <td style="width: 4px; text-align: center; padding: 1px 0;">:</td>
                            <td style="text-align: right; padding: 1px 0; font-weight: bold;">{{ number_format($totalQtyKecil, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </div>

                {{-- Kolom Kanan: Summary Total & Metadata --}}
                <div style="flex: 0 0 52%;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 12px; font-weight: bold;">
                        <tr>
                            <td style="padding: 1px 0; white-space: nowrap; width: 60px;">Total</td>
                            <td style="width: 5px; text-align: center; padding: 1px 0;">:</td>
                            <td style="text-align: right; padding: 1px 0;">{{ number_format($famountgross, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 1px 0; white-space: nowrap;">Discount</td>
                            <td style="width: 5px; text-align: center; padding: 1px 0;">:</td>
                            <td style="text-align: right; padding: 1px 0;">{{ number_format($fdiscount, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 1px 0; white-space: nowrap;">Biaya/Charge</td>
                            <td style="width: 5px; text-align: center; padding: 1px 0;">:</td>
                            <td style="text-align: right; padding: 1px 0;">{{ number_format($fongkosangkut, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="gt" style="font-weight: bold; font-size: 12.5px;">
                            <td style="border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 1.5px 0; white-space: nowrap;">G.Total</td>
                            <td style="border-top: 1px solid #000; border-bottom: 1px solid #000; width: 5px; text-align: center; padding: 1.5px 0;">:</td>
                            <td style="border-top: 1px solid #000; border-bottom: 1px solid #000; text-align: right; padding: 1.5px 0;">{{ number_format($famountso, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                    <div class="meta-right" style="margin-top: 3px; font-size: 11.5px; font-weight: normal;">
                        <div>Dicetak: {{ now()->format('d/m/y H:i') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Continued Template (Non-last Page) --}}
        <div id="tpl-continued">
            <div class="footer-line"></div>
            <div style="margin-top: 6px; text-align: right; font-style: italic; font-weight: bold; font-size: 11.5px;">
                Bersambung ke halaman <span class="next-page-num">2</span>
            </div>
            <div class="sign-container" style="margin-top: 10px;">
                <div></div>
                <div class="meta-right">
                    <div>Dicetak: {{ now()->format('d/m/y H:i') }}</div>
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

            // Batas tinggi diukur dari .sheet asli (ikut ukuran CSS), sisakan 6px buat jaga-jaga
            const SAFETY_PX = 6;

            function getMaxHeight(sheet) {
                const cs = getComputedStyle(sheet);
                return sheet.clientHeight - parseFloat(cs.paddingTop) - parseFloat(cs.paddingBottom) - SAFETY_PX;
            }

            // Tinggi terpakai = bawah elemen terakhir (margin ikut kehitung, beda dgn jumlah offsetHeight)
            function getContentHeight(sheet) {
                const last = sheet.lastElementChild;
                if (!last) return 0;
                return last.offsetTop + last.offsetHeight - parseFloat(getComputedStyle(sheet).paddingTop);
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

                    if (getContentHeight(currentSheet.sheet) > getMaxHeight(currentSheet.sheet)) {
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

                    if (getContentHeight(currentSheet.sheet) > getMaxHeight(currentSheet.sheet) && currentSheet.tbody.children.length > clonedPair.length) {
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
                    el.innerText = `${pageNum} / ${totalPages}`;
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
            document.querySelectorAll('.sheet').forEach(target => {
                target.style.transform = `scale(${currentZoom})`;
                target.style.transformOrigin = "top center";
            });
            document.getElementById("zoomLabel").innerText = `${Math.round(currentZoom * 100)}%`;
        }

        async function printModeText() {
            const btn = document.getElementById('btnTextPrint');
            const status = document.getElementById('textPrintStatus');
            const originalText = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '⏳ Mengirim...';
            status.style.display = 'inline';
            status.style.color = '#0d6efd';
            status.textContent = 'Mengirim ke printer...';

            try {
                const res = await fetch(@json(route('penjualanretail.print-text', $hdr->fsono ?? ($displayFsono ?? ''))), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });

                const data = await res.json().catch(() => ({}));
                if (res.ok && data.success) {
                    status.style.color = '#15803d';
                    status.textContent = '✅ ' + (data.message || 'Terkirim ke printer');
                } else {
                    status.style.color = '#b91c1c';
                    status.textContent = '❌ ' + (data.message || 'Gagal mencetak');
                    alert(data.message || 'Gagal mengirim ke printer');
                }
            } catch (err) {
                status.style.color = '#b91c1c';
                status.textContent = '❌ Gagal: ' + err.message;
                alert('Gagal mengirim ke printer: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
                setTimeout(() => {
                    status.style.display = 'none';
                }, 8000);
            }
        }
    </script>
</body>

</html>
