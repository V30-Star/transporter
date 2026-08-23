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
            min-height: 148.5mm;
            margin: 20px auto;
            background: white;
            padding: 8mm 10mm;
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
            font-size: 20px;
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
            color: #c00;
            font-weight: bold;
            font-size: 15px;
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
            font-size: 12px;
            margin-top: 4px;
            margin-left: auto;
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

        .text-right, .tb th.text-right {
            text-align: right;
        }

        .text-center, .tb th.text-center {
            text-align: center;
        }

        /* Footer Section */
        .footer-line {
            border-top: 1.5px solid #000;
            margin-top: 2px;
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
            margin-top: 18px;
            clear: both;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
        }

        .sign-table {
            border-collapse: collapse;
            width: 350px;
        }

        .sign-table td {
            border: 1px solid #000;
            width: 50%;
            height: 25px;
            text-align: center;
            font-size: 9px;
            font-weight: bold;
            background-color: #f9f9f9;
        }

        .sign-table .box-content {
            height: 70px;
            vertical-align: bottom;
            padding-bottom: 5px;
            font-weight: normal;
            background-color: #fff;
        }

        .meta-right {
            font-size: 10px;
            text-align: right;
            white-space: nowrap;
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
                height: 148.5mm;
                max-height: 148.5mm;
                margin: 0 auto !important;
                padding: 6mm 8mm !important;
                box-shadow: none !important;
                box-sizing: border-box;
                page-break-after: always;
            }

            .page-a4:last-child {
                page-break-after: auto;
            }

            .no-print, .print-hide, #raw-templates {
                display: none !important;
            }

            @page {
                size: 210mm 148.5mm;
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
        <div id="print-container"></div>
    </div>

    <div id="raw-templates" style="display: none;">
        {{-- Header Template --}}
        <div id="tpl-header">
            <div class="header-row">
                <div>
                    <div class="comp-name">{{ strtoupper($company_name) }}</div>
                    @if(!empty($company_address1))<div style="font-size: 12px;">{{ $company_address1 }}</div>@endif
                    @if(!empty($company_address2))<div style="font-size: 12px;">{{ $company_address2 }}</div>@endif
                    <div class="customer-container">
                        <span class="customer-label">Supplier</span>
                        <div style="font-weight: bold; font-size: 11px;">{{ $hdr->supplier_name ?? '-' }}</div>
                        <div style="font-size: 10px; margin-top: 4px; color: #333;">
                            Gudang : {{ $hdr->fwhnamen ?? '-' }}
                        </div>
                    </div>
                </div>
                <div style="min-width: 260px;">
                    <div class="title-so">Pemakaian Barang</div>
                    <div class="so-no" style="font-size: 14px;">No. {{ $hdr->fstockmtno ?? '-' }}</div>
                    <table class="info-table" style="width: 100%;">
                        <tr>
                            <td style="width: 75px;">Tanggal</td>
                            <td style="width: 10px;">:</td>
                            <td>{{ $fmt($hdr->fstockmtdate) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- Table Head Template --}}
        <table id="tpl-table">
            <thead id="tpl-thead">
                <tr>
                    <th style="width: 8%; text-align: center;" class="text-center">No.</th>
                    <th style="width: 22%;">Kode Barang</th>
                    <th style="width: 45%;">Nama Barang</th>
                    <th style="width: 12%; text-align: right;" class="text-right">Qty.</th>
                    <th style="width: 13%; text-align: center;" class="text-center">Satuan</th>
                </tr>
            </thead>
            <tbody id="raw-rows">
                @foreach ($dt as $i => $r)
                    <tr class="item-row">
                        <td class="text-center row-no">{{ $i + 1 }}</td>
                        <td>{{ $r->product_code ?? '-' }}</td>
                        <td>
                            <div style="font-weight: bold;">{{ !empty(trim((string) ($r->fdesc ?? ''))) ? $r->fdesc : ($r->product_name ?? '-') }}</div>
                        </td>
                        <td class="text-right">{{ number_format((float) ($r->fqty ?? 0), 2, ',', '.') }}</td>
                        <td class="text-center">{{ $r->fsatuan ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Summary Template (Last Page) --}}
        <div id="tpl-summary">
            <div class="footer-line"></div>

            <div class="terbilang-box">
                <strong>Keterangan / Note :</strong><br>
                <span style="font-size: 10px; color: #333;">{{ $hdr->fket ?? '-' }}</span>
            </div>
        </div>

        {{-- Sign Template (Last Page) --}}
        <div id="tpl-sign">
            <div class="sign-container">
                <div style="display: flex; align-items: flex-start; gap: 40px;">
                    <div style="width: 160px; min-width: 140px; text-align: center;">
                        <div style="font-size: 11px;">Dibuat Oleh,</div>
                        <div style="margin-top: 55px; font-size: 11px; font-weight: bold; white-space: nowrap;">
                            ( {!! !empty($namattdpo) ? strtoupper($namattdpo) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!} )
                        </div>
                    </div>
                    <div style="width: 160px; min-width: 140px; text-align: center;">
                        <div style="font-size: 11px;">Disetujui,</div>
                        <div style="margin-top: 55px; font-size: 11px; font-weight: bold; white-space: nowrap;">
                            ( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )
                        </div>
                    </div>
                </div>
                <div class="meta-right">
                    <div>Dicetak: {{ now()->format('d-m-Y H:i') }} <span class="page-counter">Hal : 1 / 1</span></div>
                </div>
            </div>
        </div>

        {{-- Continued Template (Non-last Page) --}}
        <div id="tpl-continued">
            <div class="footer-line"></div>
            <div style="margin-top: 8px; text-align: right; font-style: italic; font-weight: bold; font-size: 11px;">
                Bersambung ke halaman <span class="next-page-num">2</span>
            </div>
            <div class="sign-container" style="margin-top: 20px;">
                <div></div>
                <div class="meta-right">
                    <div>Dicetak: {{ now()->format('d-m-Y H:i') }} <span class="page-counter">Hal : 1 / 1</span></div>
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
            const tplSign = document.getElementById('tpl-sign');
            const tplContinued = document.getElementById('tpl-continued');
            const rawRows = Array.from(document.querySelectorAll('#raw-rows tr'));

            printContainer.innerHTML = '';

            // Usable content height for 210mm x 148.5mm (~560px - 48px padding = 512px)
            const MAX_SHEET_CONTENT_HEIGHT = 490;

            function getContentHeight(sheet) {
                let total = 0;
                for (let i = 0; i < sheet.children.length; i++) {
                    total += sheet.children[i].offsetHeight;
                }
                return total;
            }

            function createSheet() {
                const sheet = document.createElement('div');
                sheet.className = 'page-a4';

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

            let currentSheet = createSheet();
            let sheets = [currentSheet];

            for (let idx = 0; idx < rawRows.length; idx++) {
                const row = rawRows[idx].cloneNode(true);
                currentSheet.tbody.appendChild(row);

                const isLastItem = (idx === rawRows.length - 1);

                if (isLastItem) {
                    // Test if summary & sign also fit on this sheet
                    currentSheet.footerSlot.innerHTML = '';
                    const summaryClone = tplSummary.cloneNode(true);
                    summaryClone.removeAttribute('id');
                    const signClone = tplSign.cloneNode(true);
                    signClone.removeAttribute('id');

                    currentSheet.footerSlot.appendChild(summaryClone);
                    currentSheet.footerSlot.appendChild(signClone);

                    if (getContentHeight(currentSheet.sheet) > MAX_SHEET_CONTENT_HEIGHT && currentSheet.tbody.children.length > 1) {
                        // Move row and summary to next sheet
                        currentSheet.tbody.removeChild(row);
                        currentSheet.footerSlot.innerHTML = '';

                        // Set continuation on current sheet
                        const contClone = tplContinued.cloneNode(true);
                        contClone.removeAttribute('id');
                        currentSheet.footerSlot.appendChild(contClone);

                        // New sheet for the remaining item + summary
                        currentSheet = createSheet();
                        sheets.push(currentSheet);

                        currentSheet.tbody.appendChild(row);
                        currentSheet.footerSlot.appendChild(summaryClone);
                        currentSheet.footerSlot.appendChild(signClone);
                    }
                } else {
                    // Test with continuation footer
                    currentSheet.footerSlot.innerHTML = '';
                    const contTest = tplContinued.cloneNode(true);
                    contTest.removeAttribute('id');
                    currentSheet.footerSlot.appendChild(contTest);

                    if (getContentHeight(currentSheet.sheet) > MAX_SHEET_CONTENT_HEIGHT && currentSheet.tbody.children.length > 1) {
                        // Overflow! Move row to next sheet
                        currentSheet.tbody.removeChild(row);

                        currentSheet = createSheet();
                        sheets.push(currentSheet);

                        currentSheet.tbody.appendChild(row);
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
                    const signClone = tplSign.cloneNode(true);
                    signClone.removeAttribute('id');
                    s.footerSlot.appendChild(summaryClone);
                    s.footerSlot.appendChild(signClone);
                }

                s.sheet.querySelectorAll('.page-counter').forEach(el => {
                    el.innerText = `Hal : ${pageNum} / ${totalPages}`;
                });
            });

            // Re-index all rows globally 1..N
            let globalRow = 1;
            document.querySelectorAll('#print-container tbody tr').forEach(tr => {
                const cell = tr.querySelector('.row-no');
                if (cell) cell.innerText = globalRow++;
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', runResponsivePagination);
        } else {
            runResponsivePagination();
        }

        let currentZoom = 1.0;
        function adjustZoom(delta) {
            currentZoom = Math.min(2.0, Math.max(0.3, currentZoom + delta));
            document.querySelectorAll('.page-a4').forEach(target => {
                target.style.transform = `scale(${currentZoom})`;
                target.style.transformOrigin = "top center";
            });
            document.getElementById('zoomLabel').textContent = Math.round(currentZoom * 100) + '%';
        }
    </script>
</body>

</html>
