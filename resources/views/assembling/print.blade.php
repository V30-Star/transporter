@if(function_exists('is_retail_the') && is_retail_the())
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Assembling - {{ $hdr->fstockmtno ?? '-' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --fg: #000;
            --bd: #000;
            --blue: #0000FF;
            --red: #FF0000;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #ececec;
            font: 11px Arial, Helvetica, sans-serif;
            color: var(--fg);
        }

        .sheet {
            width: 5.5in;
            height: 5in;
            overflow: hidden;
            margin: 0.2in auto;
            padding: 0.2in 1.90in 0.15in 0.2in;
            background: #fff;
            border: 1px solid #cfcfcf;
            box-shadow: 0 6px 18px rgba(0, 0, 0, .12);
            position: relative;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }

        .sheet.paginated .footer-slot {
            margin-top: auto;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 2px;
        }

        .comp-name {
            font-size: 14px;
            font-weight: bold;
            font-style: italic;
        }

        .title-so {
            font-size: 15px;
            color: var(--blue);
            text-decoration: underline;
            font-weight: bold;
            text-align: right;
        }

        .so-no {
            color: var(--red);
            font-weight: bold;
            font-size: 12px;
            text-align: right;
        }

        .customer-container {
            border: 1px solid #000;
            border-radius: 8px;
            padding: 5px 12px;
            width: 100%;
            position: relative;
            margin-top: 6px;
        }

        .customer-label {
            position: absolute;
            top: -8px;
            left: 15px;
            background: #fff;
            padding: 0 5px;
            font-size: 10px;
        }

        .info-table {
            font-size: 10px;
            margin-top: 0;
            margin-left: auto;
        }

        .info-table td {
            padding: 1px 2px;
            vertical-align: top;
        }

        .tb {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .tb th {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 4px 2px;
            text-align: left;
            font-weight: normal;
            font-size: 10px;
        }

        .tb td {
            padding: 2px 2px;
            vertical-align: top;
            font-size: 10px;
        }

        .text-center, .tb th.text-center {
            text-align: center;
        }

        .text-right, .tb th.text-right {
            text-align: right;
        }

        .footer-line {
            border-top: 1.5px solid #000;
            margin-top: 2px;
        }

        .summary-container {
            display: flex;
            align-items: flex-start;
            margin-top: 2px;
            font-size: 10px;
        }

        .sign-table {
            border-collapse: collapse;
            width: 100%;
        }

        .sign-table td {
            border: 1px solid #000;
            width: 50%;
            height: 20px;
            text-align: center;
            padding: 2px;
            font-size: 10px;
        }

        .sign-table .box-content {
            height: 48px;
            vertical-align: bottom;
            padding-bottom: 3px;
        }

        .meta-right {
            font-size: 9px;
            text-align: right;
            white-space: nowrap;
        }

        .no-print {
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
                height: 5in;
                max-height: 5in;
            }

        .sheet.paginated .footer-slot {
            margin-top: auto;
        }

            .sheet:last-child {
                page-break-after: auto;
            }

            .no-print, #raw-templates {
                display: none !important;
            }

            @page {
                size: 5.5in 5in;
                margin: 0;
            }
        }
    </style>
    @include('partials.print-the-retail')
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

    @php
        $bahanBaku = $dt->filter(fn($r) => strtoupper(trim((string)($r->fcode ?? 'B'))) !== 'J');
        $barangJadi = $dt->filter(fn($r) => strtoupper(trim((string)($r->fcode ?? ''))) === 'J');
    @endphp

    <div id="print-container"></div>

    <div id="raw-templates" style="display: none;">
        {{-- Header Template --}}
        <div id="tpl-header">
            <div class="header-row">
                <div>
                    <div class="comp-name">{{ strtoupper($company_name) }}</div>
                    @if(!empty($company_city))<div style="font-size: 10px;">{{ $company_city }}</div>@endif
                </div>
                <div>
                    <div class="title-so">ASSEMBLING</div>
                    <div class="so-no">No. {{ $hdr->fstockmtno ?? '-' }}</div>
                </div>
            </div>

            <div class="customer-container">
                <span class="customer-label">Informasi Assembling</span>
                <div style="display: flex; justify-content: space-between; align-items: stretch; gap: 10px;">
                    <div style="flex: 1; padding-right: 10px; border-right: 1px solid #000;">
                        <div style="font-size: 10px;">
                            <strong>Gudang:</strong> {{ !empty($hdr->fwhnamen) ? $hdr->fwhnamen : ($hdr->ffrom ?? '-') }}
                        </div>
                        @if(!empty($hdr->supplier_name) || !empty($hdr->fsupplier))
                            <div style="font-size: 10px; margin-top: 2px;">
                                <strong>Supplier:</strong> {{ !empty($hdr->supplier_name) ? $hdr->supplier_name : $hdr->fsupplier }}
                            </div>
                        @endif
                    </div>
                    <div style="width: 175px;">
                        <table class="info-table" style="width: 100%;">
                            <tr>
                                <td style="width: 48px;">Tanggal</td>
                                <td style="width: 6px;">:</td>
                                <td>{{ $fmt($hdr->fstockmtdate) }}</td>
                            </tr>
                            <tr>
                                <td>Cabang</td>
                                <td>:</td>
                                <td>{{ $hdr->cabang_name ?? ($hdr->fcabang ?? '-') }}</td>
                            </tr>
                            <tr>
                                <td>Keterangan</td>
                                <td>:</td>
                                <td>{{ $hdr->fket ?? '-' }}</td>
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
                    <th style="width: 25px;" class="text-center">No.</th>
                    <th style="width: 95px;">Kode Barang</th>
                    <th class="text-right" style="width: 65px;">Qty.</th>
                    <th style="width: 55px;">Satuan</th>
                </tr>
            </thead>
            <tbody id="raw-rows">
                @if($barangJadi->isNotEmpty())
                    <tr class="item-block-single">
                        <td colspan="4" style="font-weight: bold; background: #f0f0f0; padding: 3px 4px; border-top: 1px solid #000; border-bottom: 1px solid #000;">
                            [ BARANG JADI / HASIL ]
                        </td>
                    </tr>
                    @foreach ($barangJadi as $i => $r)
                        @php
                            $productDisplayName = trim((string)($r->fdesc ?? '')) !== '' ? $r->fdesc : (format_product_name($r->product_name ?? '', $r->fspecification ?? $r->product_specification ?? '') ?: '-');
                        @endphp
                        <tr class="item-block-pair-1">
                            <td class="text-center row-no">{{ $loop->iteration }}</td>
                            <td colspan="3" style="font-weight: bold;">{{ $productDisplayName }}</td>
                        </tr>
                        <tr class="item-block-pair-2">
                            <td></td>
                            <td style="font-family: monospace; font-size: 9px;">{{ $r->fprdcode ?? ($r->product_code ?? '-') }}</td>
                            <td class="text-right">{{ number_format((float) ($r->fqty ?? 0), 2, ',', '.') }}</td>
                            <td>{{ $r->fsatuan ?? '-' }}</td>
                        </tr>
                    @endforeach
                @endif

                @if($bahanBaku->isNotEmpty())
                    <tr class="item-block-single">
                        <td colspan="4" style="font-weight: bold; background: #f0f0f0; padding: 3px 4px; border-top: 1px solid #000; border-bottom: 1px solid #000;">
                            [ BAHAN BAKU / KOMPONEN ]
                        </td>
                    </tr>
                    @foreach ($bahanBaku as $i => $r)
                        @php
                            $productDisplayName = trim((string)($r->fdesc ?? '')) !== '' ? $r->fdesc : (format_product_name($r->product_name ?? '', $r->fspecification ?? $r->product_specification ?? '') ?: '-');
                        @endphp
                        <tr class="item-block-pair-1">
                            <td class="text-center row-no">{{ $loop->iteration }}</td>
                            <td colspan="3" style="font-weight: bold;">{{ $productDisplayName }}</td>
                        </tr>
                        <tr class="item-block-pair-2">
                            <td></td>
                            <td style="font-family: monospace; font-size: 9px;">{{ $r->fprdcode ?? ($r->product_code ?? '-') }}</td>
                            <td class="text-right">{{ number_format((float) ($r->fqty ?? 0), 2, ',', '.') }}</td>
                            <td>{{ $r->fsatuan ?? '-' }}</td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>

        {{-- Summary Template (Last Page) --}}
        <div id="tpl-summary">
            <div class="footer-line"></div>
            
            <div class="summary-container">
                {{-- Left: Signatures --}}
                <div style="width: 32%;">
                    <table class="sign-table">
                        <tr>
                            <td>Dibuat Oleh</td>
                            <td>Disetujui</td>
                        </tr>
                        <tr>
                            <td class="box-content">{!! !empty($namattdpo) ? strtoupper($namattdpo) : '&nbsp;' !!}</td>
                            <td class="box-content">&nbsp;</td>
                        </tr>
                    </table>
                </div>

                {{-- Middle: Tot Qty --}}
                <div style="width: 36%; padding: 0 8px;">
                    <div style="font-size: 10px; font-weight: bold;">
                        Tot. Qty Bahan: {{ number_format((float) $bahanBaku->sum('fqty'), 2, ',', '.') }}
                    </div>
                </div>

                {{-- Right: Metadata --}}
                <div style="width: 32%; text-align: right;" class="meta-right">
                    <div>Dicetak: {{ now()->format('d-m-Y H:i') }}</div>
                    <div><span class="page-counter">Hal : 1 / 1</span></div>
                </div>
            </div>
        </div>

        {{-- Continued Template (Non-last Page) --}}
        <div id="tpl-continued">
            <div class="footer-line"></div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 2px;">
                <div style="font-style: italic; font-weight: bold; font-size: 10px;">
                    Bersambung ke halaman <span class="next-page-num">2</span>
                </div>
                <div class="meta-right">
                    <div>Dicetak: {{ now()->format('d-m-Y H:i') }}</div>
                    <div><span class="page-counter">Hal : 1 / 1</span></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const TARGET_PAGE_HEIGHT = 5 * 96; // 793.92px
            const printContainer = document.getElementById('print-container');
            const tplHeader = document.getElementById('tpl-header');
            const tplThead = document.getElementById('tpl-thead');
            const tplSummary = document.getElementById('tpl-summary');
            const tplContinued = document.getElementById('tpl-continued');
            const rawRowsContainer = document.getElementById('raw-rows');

            // Group rows into atomic blocks
            const blocks = [];
            const rows = Array.from(rawRowsContainer.children);
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                if (row.classList.contains('item-block-single')) {
                    // Check if next row is pair-1, keep them together to avoid orphan section header
                    if (rows[i + 1] && rows[i + 1].classList.contains('item-block-pair-1')) {
                        blocks.push([row, rows[i + 1], rows[i + 2]]);
                        i += 2;
                    } else {
                        blocks.push([row]);
                    }
                } else if (row.classList.contains('item-block-pair-1')) {
                    const row2 = rows[i + 1];
                    blocks.push(row2 ? [row, row2] : [row]);
                    if (row2) i++;
                } else {
                    blocks.push([row]);
                }
            }

            let pages = [];
            let currentPage = null;
            let currentTbody = null;

            function createNewPage() {
                const sheet = document.createElement('div');
                sheet.className = 'sheet';

                const headerClone = tplHeader.cloneNode(true);
                headerClone.removeAttribute('id');
                sheet.appendChild(headerClone);

                const table = document.createElement('table');
                table.className = 'tb';

                const theadClone = tplThead.cloneNode(true);
                theadClone.removeAttribute('id');
                table.appendChild(theadClone);

                const tbody = document.createElement('tbody');
                table.appendChild(tbody);
                sheet.appendChild(table);

                printContainer.appendChild(sheet);

                currentPage = sheet;
                currentTbody = tbody;
                pages.push(sheet);
            }

            createNewPage();

            for (const block of blocks) {
                block.forEach(r => currentTbody.appendChild(r));

                if (currentPage.scrollHeight > TARGET_PAGE_HEIGHT) {
                    block.forEach(r => currentTbody.removeChild(r));

                    const contClone = tplContinued.cloneNode(true);
                    contClone.removeAttribute('id');
                    currentPage.appendChild(contClone);

                    createNewPage();

                    block.forEach(r => currentTbody.appendChild(r));
                }
            }

            const summaryClone = tplSummary.cloneNode(true);
            summaryClone.removeAttribute('id');
            currentPage.appendChild(summaryClone);

            if (currentPage.scrollHeight > TARGET_PAGE_HEIGHT) {
                currentPage.removeChild(summaryClone);

                const contClone = tplContinued.cloneNode(true);
                contClone.removeAttribute('id');
                currentPage.appendChild(contClone);

                createNewPage();
                currentPage.appendChild(summaryClone);
            }

            const totalPages = pages.length;
            pages.forEach((page, index) => {
                const pageNum = index + 1;
                const counter = page.querySelector('.page-counter');
                if (counter) {
                    counter.innerText = `Hal : ${pageNum} / ${totalPages}`;
                }
                const nextNum = page.querySelector('.next-page-num');
                if (nextNum) {
                    nextNum.innerText = `${pageNum + 1}`;
                }
            });
        });
    </script>
</body>

</html>

@else
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Assembling - {{ $hdr->fstockmtno ?? '-' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --fg: #000;
            --bd: #000;
            --blue: #0000FF;
            --red: #FF0000;
        }

        * {
            box-sizing: border-box
        }

        body {
            margin: 0;
            background: #ececec;
            font: 12px Arial, Helvetica, sans-serif;
            color: var(--fg)
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

        /* Header Styles */
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

        /* Box Container (Supplier/Info) */
        .customer-container {
            border: 1px solid #000;
            border-radius: 8px;
            padding: 5px 12px;
            width: 100%;
            position: relative;
            margin-top: 6px;
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
        }

        /* Table Item */
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
        }

        .sign-table .box-content {
            height: 70px;
            vertical-align: bottom;
            padding-bottom: 5px;
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

    @php
        $bahanBaku = $dt->filter(fn($r) => strtoupper(trim((string)($r->fcode ?? 'B'))) !== 'J');
        $barangJadi = $dt->filter(fn($r) => strtoupper(trim((string)($r->fcode ?? ''))) === 'J');
    @endphp

    <div id="print-container"></div>

    <div id="raw-templates" style="display: none;">
        {{-- Header Template --}}
        <div id="tpl-header">
            <div class="header-row">
                <div>
                    <div class="comp-name">{{ strtoupper($company_name) }}</div>
                    @if(!empty($company_city))<div style="font-size: 12px;">{{ $company_city }}</div>@endif
                </div>
                <div>
                    <div class="title-so">Assembling</div>
                    <div class="so-no">No. {{ $hdr->fstockmtno ?? '-' }}</div>
                </div>
            </div>

            <div class="customer-container">
                <span class="customer-label">Supplier</span>
                <div style="display: flex; justify-content: space-between; align-items: stretch; gap: 15px;">
                    <div style="flex: 1; padding-right: 15px; border-right: 1px solid #000;">
                        <div style="font-weight: bold;">{{ !empty($hdr->supplier_name) ? $hdr->supplier_name . (!empty($hdr->fsupplier) && $hdr->fsupplier !== '0' ? ' (' . $hdr->fsupplier . ')' : '') : ($hdr->fsupplier && $hdr->fsupplier !== '0' ? $hdr->fsupplier : '-') }}</div>
                        <div style="font-size: 11px; margin-top: 2px; white-space: pre-line;">
                            {{ $hdr->supplier_address ?? '-' }}
                        </div>
                    </div>
                    <div style="width: 290px;">
                        <table class="info-table" style="margin-top: 0; width: 100%;">
                            <tr>
                                <td style="width: 80px;">Tanggal</td>
                                <td style="width: 10px;">:</td>
                                <td>{{ $fmt($hdr->fstockmtdate) }}</td>
                            </tr>
                            <tr>
                                <td>Gudang</td>
                                <td>:</td>
                                <td>{{ !empty($hdr->fwhnamen) ? $hdr->fwhnamen : ($hdr->ffrom ?: '-') }}</td>
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
                    <th style="width: 5%; text-align: center;" class="text-center">No.</th>
                    <th style="width: 20%;">Kode Barang</th>
                    <th style="width: 50%;">Nama Barang</th>
                    <th style="width: 10%; text-align: right;" class="text-right">Qty.</th>
                    <th style="width: 15%; text-align: center;" class="text-center">Satuan</th>
                </tr>
            </thead>
            <tbody id="raw-rows">
                @if($bahanBaku->isNotEmpty())
                    <tr class="section-row">
                        <td colspan="5" style="font-weight: bold; background: #f0f0f0; padding: 4px 6px; border-top: 1px solid #000; border-bottom: 1px solid #000;">
                            Bahan Baku
                        </td>
                    </tr>
                    @foreach ($bahanBaku as $i => $r)
                        <tr class="item-row">
                            <td class="text-center row-no">{{ $loop->iteration }}</td>
                            <td>{{ $r->product_code ?? '-' }}</td>
                            <td>
                                <div style="white-space: pre-line;">{{ !empty(trim((string) ($r->fdesc ?? ''))) ? $r->fdesc : (format_product_name($r->product_name ?? '', $r->fspecification ?? $r->product_specification ?? '') ?: '-') }}</div>
                            </td>
                            <td class="text-right">{{ number_format((float) ($r->fqty ?? 0), 2, ',', '.') }}</td>
                            <td class="text-center">{{ $r->fsatuan ?? '-' }}</td>
                        </tr>
                    @endforeach
                @endif

                @if($barangJadi->isNotEmpty())
                    <tr class="section-row">
                        <td colspan="5" style="font-weight: bold; background: #f0f0f0; padding: 4px 6px; border-top: 1px solid #000; border-bottom: 1px solid #000;">
                            Barang Jadi
                        </td>
                    </tr>
                    @foreach ($barangJadi as $i => $r)
                        <tr class="item-row">
                            <td class="text-center row-no">{{ $loop->iteration }}</td>
                            <td>{{ $r->product_code ?? '-' }}</td>
                            <td>
                                <div style="white-space: pre-line;">{{ !empty(trim((string) ($r->fdesc ?? ''))) ? $r->fdesc : (format_product_name($r->product_name ?? '', $r->fspecification ?? $r->product_specification ?? '') ?: '-') }}</div>
                            </td>
                            <td class="text-right">{{ number_format((float) ($r->fqty ?? 0), 2, ',', '.') }}</td>
                            <td class="text-center">{{ $r->fsatuan ?? '-' }}</td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>

        {{-- Summary Template (Last Page) --}}
        <div id="tpl-summary">
            <div class="footer-line"></div>
        </div>

        {{-- Sign Template (Last Page) --}}
        <div id="tpl-sign">
            <div class="sign-container">
                <div style="display: flex; align-items: flex-start; gap: 40px;">
                    <div style="width: 160px; min-width: 140px; text-align: center;">
                        <div style="font-size: 11px;">Dibuat Oleh,</div>
                        <div style="margin-top: 55px; font-size: 11px; font-weight: bold; white-space: nowrap;">
                            ( {!! !empty($namattdpo) ? strtoupper($namattdpo) : (!empty($namattdfakturpenjualan) ? strtoupper($namattdfakturpenjualan) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;') !!} )
                        </div>
                    </div>
                    <div style="width: 160px; min-width: 140px; text-align: center;">
                        <div style="font-size: 11px;">Disetujui,</div>
                        <div style="margin-top: 55px; font-size: 11px; font-weight: bold; white-space: nowrap;">
                            ( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )
                        </div>
                    </div>
                    @if(!empty(trim((string)($hdr->fket ?? ''))))
                        <div style="font-size: 11px; max-width: 250px;">
                            <div>Keterangan:</div>
                            <div style="white-space: pre-line;">{{ $hdr->fket }}</div>
                        </div>
                    @endif
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

            // Usable content height for 5.83in at 96dpi (560px - 48px padding = 512px)
            const MAX_SHEET_CONTENT_HEIGHT = (typeof window.IS_RETAIL_THE !== 'undefined' && window.IS_RETAIL_THE) ? 720 : 490;

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
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', runResponsivePagination);
        } else {
            runResponsivePagination();
        }

        let currentZoom = 1.0;
        function adjustZoom(delta) {
            currentZoom = Math.min(Math.max(currentZoom + delta, 0.5), 2.0);
            const target = document.querySelector('.sheet') || document.body;
            target.style.transform = `scale(${currentZoom})`;
            target.style.transformOrigin = "top center";
            document.getElementById("zoomLabel").innerText = `${Math.round(currentZoom * 100)}%`;
        }
    </script>
</body>

</html>
@endif
