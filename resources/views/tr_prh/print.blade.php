<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Permintaan Pembelian - {{ $hdr->fprno }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --fg: #000;
            --bd: #000;
            --blue: #0000ff;
            --red: #ff0000;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #ececec;
            font: 12px Arial, Helvetica, sans-serif;
            color: var(--fg);
        }

        .sheet {
            width: 8.27in;
            min-height: 5.83in;
            margin: 0.2in auto;
            padding: 0.25in 0.4in;
            background: #fff;
            border: 1px solid #cfcfcf;
            box-shadow: 0 6px 18px rgba(0, 0, 0, .12);
            position: relative;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 5px;
        }

        .comp-name {
            font-size: 20px;
            font-weight: bold;
            font-style: italic;
        }

        .title-so {
            font-size: 20px;
            color: var(--blue);
            text-decoration: underline;
            font-weight: bold;
            text-align: right;
        }

        .so-no {
            color: var(--red);
            font-weight: bold;
            font-size: 15px;
            text-align: right;
        }

        .customer-container {
            border: 1px solid #000;
            border-radius: 10px;
            padding: 5px 12px;
            width: 450px;
            min-height: 78px;
            position: relative;
            margin-top: 10px;
        }

        .customer-label {
            position: absolute;
            top: -8px;
            left: 15px;
            background: #fff;
            padding: 0 5px;
            font-size: 11px;
        }

        .info-table {
            font-size: 12px;
            margin-top: 4px;
            margin-left: auto;
        }

        .info-table td {
            padding: 1px 2px;
            vertical-align: top;
        }

        .tb {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .tb th {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 5px;
            text-align: left;
            font-weight: normal;
        }

        .tb td {
            padding: 5px;
            vertical-align: top;
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
            margin-top: 28px;
        }

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
            width: 450px;
        }

        .sign-table td {
            border: 1px solid #000;
            width: 33.33%;
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
            font-size: 10px;
            text-align: right;
            white-space: nowrap;
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

            .sheet {
                margin: 0;
                border: none;
                box-shadow: none;
                transform: none !important;
                page-break-after: always;
                height: 5.83in;
                max-height: 5.83in;
            }

            .sheet:last-child {
                page-break-after: auto;
            }

            .no-print, .print-hide, #raw-templates {
                display: none !important;
            }

            @page {
                size: 8.27in 5.83in;
                margin: 0;
            }
        }
    </style>
</head>

<body>
    <div class="no-print">
        <button class="print-button" onclick="window.print()">🖨️ Cetak Dokumen</button>

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

    <div id="print-container"></div>

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
                        <div style="font-weight: bold;">
                            {{ trim(($hdr->fsupplier ?? '') . ' - ' . ($hdr->supplier_name ?? ''), ' -') ?: '-' }}
                        </div>
                        <div style="font-size: 11px;">
                            Cabang : {{ $hdr->cabang_name ?? ($hdr->fbranchcode ?? '-') }}
                        </div>
                        <div style="font-size: 11px;">
                            Keterangan : {{ $hdr->fket ?: '-' }}
                        </div>
                    </div>
                </div>
                <div>
                    <div class="title-so">Permintaan Pembelian</div>
                    <div class="so-no">No. {{ $hdr->fprno ?? '-' }}</div>
                    <table class="info-table">
                        <tr>
                            <td>Tanggal</td>
                            <td>:</td>
                            <td>{{ $fmt($hdr->fprdate) }}</td>
                        </tr>
                        <tr>
                            <td>Tgl. Dibutuhkan</td>
                            <td>:</td>
                            <td>{{ $fmt($hdr->fneeddate) }}</td>
                        </tr>
                        <tr>
                            <td>Tgl. Paling Lambat</td>
                            <td>:</td>
                            <td>{{ $fmt($hdr->fduedate) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- Table Head Template --}}
        <table id="tpl-table">
            <thead id="tpl-thead">
                <tr>
                    <th style="width: 5%; text-align: center;" class="text-center">No.</th>
                    <th style="width: 18%;">Kode Produk</th>
                    <th style="width: 44%;">Nama Produk</th>
                    <th style="width: 13%; text-align: right;" class="text-right">Qty</th>
                    <th style="width: 20%;">Keterangan</th>
                </tr>
            </thead>
            <tbody id="raw-rows">
                @foreach ($dt as $i => $r)
                    <tr class="item-row">
                        <td class="text-center row-no">{{ $i + 1 }}</td>
                        <td>{{ $r->product_code ?? '-' }}</td>
                        <td>
                            <div style="white-space: pre-line;">{{ !empty(trim((string) ($r->fdesc ?? ''))) ? $r->fdesc : ($r->product_name ?? '-') }}</div>
                        </td>
                        <td class="text-right">{{ number_format((float) $r->fqty, 2, ',', '.') }} {{ $r->fsatuan }}</td>
                        <td>{{ $r->fketdt ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Summary / Footer Template (Last Page) --}}
        <div id="tpl-summary">
            <div class="footer-line"></div>
        </div>

        {{-- Sign Template (Last Page) --}}
        <div id="tpl-sign">
            <div class="sign-container">
                <div style="display: flex; align-items: flex-start; gap: 40px;">
                    <div style="width: 160px; min-width: 140px;">
                        <div style="font-size: 11px;">Dibuat Oleh {{ strtoupper(sysuser_name($hdr->fusercreate ?? '') ?: ($hdr->fusercreate ?? '-')) }},</div>
                        <div style="margin-top: 55px; font-size: 11px; font-weight: bold; white-space: nowrap;">
                            ( {!! !empty($namattdpo) ? strtoupper($namattdpo) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!} )
                        </div>
                    </div>
                    <div style="width: 160px; min-width: 140px;">
                        <div style="font-size: 11px;">Disetujui,</div>
                        <div style="margin-top: 55px; font-size: 11px; font-weight: bold; white-space: nowrap;">
                            ( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )
                        </div>
                    </div>
                </div>

                <div class="meta-right">
                    <div>Dicetak {{ strtoupper(auth('sysuser')->user()->fname ?? Auth::user()->fname ?? 'SYSTEM') }}: {{ now()->format('d-m-Y H:i') }} <span class="page-counter">Hal : 1 / 1</span></div>
                </div>
            </div>
        </div>

        {{-- Continued Template (Non-last Page) --}}
        <div id="tpl-continued">
            <div style="margin-top: 15px; text-align: right; font-style: italic; font-weight: bold; font-size: 11px;">
                Bersambung ke halaman <span class="next-page-num">2</span>
            </div>
            <div class="sign-container" style="margin-top: 20px;">
                <div></div>
                <div class="meta-right">
                    <div>Dicetak {{ strtoupper(auth('sysuser')->user()->fname ?? Auth::user()->fname ?? 'SYSTEM') }}: {{ now()->format('d-m-Y H:i') }} <span class="page-counter">Hal : 1 / 1</span></div>
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

            // Usable content height for 5.83in at 96dpi (560px - 48px padding = 512px)
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
            currentZoom = Math.min(Math.max(currentZoom + delta, 0.5), 2.0);
            document.querySelectorAll('.sheet').forEach(target => {
                target.style.transform = `scale(${currentZoom})`;
                target.style.transformOrigin = "top center";
            });
            document.getElementById("zoomLabel").innerText = `${Math.round(currentZoom * 100)}%`;
        }
    </script>
</body>

</html>

