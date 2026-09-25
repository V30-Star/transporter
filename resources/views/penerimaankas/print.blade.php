@if(function_exists('is_retail_the') && is_retail_the())
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <title>Penerimaan Kas/Bank - {{ $hdr->fkasmtno ?? '-' }}</title>
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

        .tpl-continued, #tpl-continued {
            margin-top: auto;
        }

        .sheet:last-child .tb {
            border-bottom: 1px solid #000;
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

        .tpl-continued, #tpl-continued {
            margin-top: auto;
        }

        .sheet:last-child .tb {
            border-bottom: 1px solid #000;
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
                    <div class="title-so">PENERIMAAN KAS/BANK</div>
                    <div class="so-no">No. {{ $hdr->fkasmtno ?? '-' }}</div>
                </div>
            </div>

            <div class="customer-container">
                <span class="customer-label">Informasi Penerimaan</span>
                <div style="display: flex; justify-content: space-between; align-items: stretch; gap: 10px;">
                    <div style="flex: 1; padding-right: 10px; border-right: 1px solid #000;">
                        <div style="font-size: 10px;">
                            <strong>Penerima:</strong> {{ $hdr->fwhom ?: '-' }}
                        </div>
                        <div style="font-size: 10px; margin-top: 3px;">
                            <strong>Cash / Bank:</strong> {{ trim(($hdr->faccountheader ?? '') . ' - ' . ($hdr->header_account_name ?? ''), ' -') ?: '-' }}
                        </div>
                        @if(!empty($hdr->fket))
                            <div style="font-size: 9px; margin-top: 3px; color: #333;">
                                <strong>Ket:</strong> {{ $hdr->fket }}
                            </div>
                        @endif
                    </div>
                    <div style="width: 175px;">
                        <table class="info-table" style="width: 100%;">
                            <tr>
                                <td style="width: 65px;">Tanggal</td>
                                <td style="width: 6px;">:</td>
                                <td>{{ $fmt($hdr->fkasmtdate) }}</td>
                            </tr>
                            <tr>
                                <td>No.Giro/Cek</td>
                                <td>:</td>
                                <td>{{ $hdr->fnogiro ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td>Tipe Header</td>
                                <td>:</td>
                                <td>{{ $hdr->fdkheader ?? '-' }}</td>
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
                    <th>No. Referensi / Uraian</th>
                    <th style="width: 35px;" class="text-center">D/K</th>
                    <th class="text-right" style="width: 80px;">Nilai Bayar</th>
                </tr>
            </thead>
            <tbody id="raw-rows">
                @forelse ($dt as $i => $row)
                    @php
                        $accText = trim(($row->faccount ?? '') . ' - ' . ($row->account_name ?? ''), ' -');
                        $subText = !empty($row->subaccount_name) ? ' [ ' . $row->subaccount_name . ' ]' : (!empty($row->fsubaccount) ? ' [ ' . $row->fsubaccount . ' ]' : '');
                        $descText = trim(($row->frefno ?? '') . ' ' . ($row->fnote ?? ''));
                    @endphp
                    {{-- Row 1: No & Account + SubAccount --}}
                    <tr class="item-row item-row-main">
                        <td class="text-center row-no">{{ $i + 1 }}</td>
                        <td colspan="3" style="font-weight: bold;">
                            {{ $accText ?: '-' }}
                            @if(!empty($subText))
                                <span style="font-weight: normal; font-size: 9px; color: #444;">{{ $subText }}</span>
                            @endif
                        </td>
                    </tr>
                    {{-- Row 2: Ref / Note, D/K, Nilai --}}
                    <tr class="item-row item-row-sub">
                        <td></td>
                        <td style="font-size: 9px; color: #333;">{{ $descText ?: '-' }}</td>
                        <td class="text-center">{{ $row->fdk ?? '-' }}</td>
                        <td class="text-right" style="font-weight: bold;">{{ number_format((float) ($row->fkasdtvalue ?? 0), 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr class="item-row">
                        <td colspan="4" class="text-center">Tidak ada detail transaksi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Summary Template (Last Page) --}}
        <div id="tpl-summary">
            <div class="footer-line"></div>
            
            <div class="summary-container">
                {{-- Left: Signatures --}}
                <div style="width: 28%;">
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

                {{-- Middle: Terbilang --}}
                <div style="width: 36%; padding: 0 6px;">
                    <div style="font-size: 9px; color: #333; font-style: italic;">
                        Terbilang:<br>
                        <strong># {{ strtoupper(terbilang($totalAmount ?? 0)) }} RUPIAH #</strong>
                    </div>
                </div>

                {{-- Right: Totals --}}
                <div style="width: 36%; text-align: right;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 10px;">
                        <tr style="font-weight: bold; font-size: 11px;">
                            <td style="text-align: right; padding: 2px 0;">Total Nilai:</td>
                            <td style="width: 80px; text-align: right; padding: 2px 0;">{{ number_format((float) ($totalAmount ?? ($hdr->ftotamount ?? 0)), 2, ',', '.') }}</td>
                        </tr>
                    </table>
                    <div class="meta-right" style="margin-top: 3px;">
                        <div>Dicetak: {{ now()->format('d-m-Y H:i') }} <span class="page-counter">Hal : 1 / 1</span></div>
                    </div>
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
            const rawRows = Array.from(document.querySelectorAll('#raw-rows .item-row'));

            let pages = [];
            let currentPage = null;
            let currentTbody = null;

            function createNewPage(isFirst = false) {
                const sheet = document.createElement('div');
                sheet.className = 'sheet';

                // Header hanya di halaman 1
                if (isFirst) {
                    const headerClone = tplHeader.cloneNode(true);
                    headerClone.removeAttribute('id');
                    sheet.appendChild(headerClone);
                }

                const table = document.createElement('table');
                table.className = 'tb';

                // Judul Kolom (thead) hanya di halaman 1
                if (isFirst) {
                    const theadClone = tplThead.cloneNode(true);
                    theadClone.removeAttribute('id');
                    table.appendChild(theadClone);
                }

                const tbody = document.createElement('tbody');
                table.appendChild(tbody);
                sheet.appendChild(table);

                printContainer.appendChild(sheet);

                currentPage = sheet;
                currentTbody = tbody;
                pages.push(sheet);
            }

            createNewPage(true);

            // Items are paired in 2 rows
            for (let i = 0; i < rawRows.length; i += 2) {
                const row1 = rawRows[i];
                const row2 = rawRows[i + 1];

                currentTbody.appendChild(row1);
                if (row2) currentTbody.appendChild(row2);

                if (currentPage.scrollHeight > TARGET_PAGE_HEIGHT) {
                    currentTbody.removeChild(row1);
                    if (row2) currentTbody.removeChild(row2);

                    const contClone = tplContinued.cloneNode(true);
                    contClone.removeAttribute('id');
                    contClone.classList.add('tpl-continued');
                    currentPage.appendChild(contClone);

                    createNewPage(false);

                    currentTbody.appendChild(row1);
                    if (row2) currentTbody.appendChild(row2);
                }
            }

            const summaryClone = tplSummary.cloneNode(true);
            summaryClone.removeAttribute('id');
            currentPage.appendChild(summaryClone);

            if (currentPage.scrollHeight > TARGET_PAGE_HEIGHT) {
                currentPage.removeChild(summaryClone);

                const contClone = tplContinued.cloneNode(true);
                contClone.removeAttribute('id');
                    contClone.classList.add('tpl-continued');
                currentPage.appendChild(contClone);

                createNewPage(false);
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
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <title>{{ 'Penerimaan Kas/Bank' }} - {{ $hdr->fkasmtno ?? '-' }}</title>
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
        }

        .print-hide {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 999;
        }

        .print-hide button {
            padding: 10px 20px;
            cursor: pointer;
            margin-right: 6px;
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

        .doc-title {
            font-size: 20px;
            color: var(--blue);
            text-decoration: underline;
            font-weight: bold;
            text-align: right;
        }

        .doc-no {
            color: var(--red);
            font-weight: bold;
            font-size: 15px;
            text-align: right;
        }

        .info-wrap {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            margin-top: 14px;
        }

        .party-box {
            border: 1px solid #000;
            border-radius: 10px;
            padding: 8px 12px;
            width: 450px;
            min-height: 78px;
            position: relative;
            margin-top: 10px;
        }

        .party-label {
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
            border-collapse: collapse;
        }

        .info-table td {
            padding: 1px 2px;
            vertical-align: top;
        }

        .tb {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        .tb th {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 6px 5px;
            text-align: left;
            font-weight: normal;
        }

        .tb td {
            padding: 6px 5px;
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

        .summary {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
        }

        .summary-box {
            width: 320px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
        }

        .grand-total {
            border-top: 1px solid #000;
            border-bottom: 3px double #000;
            margin-top: 4px;
            padding: 6px 0;
            font-weight: bold;
            color: var(--blue);
            font-size: 14px;
        }

        .footer-line {
            border-top: 1.5px solid #000;
            margin-top: 2px;
        }

        .sign-container {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
        }

        .sign-table {
            border-collapse: collapse;
            width: 360px;
        }

        .sign-table td {
            border: 1px solid #000;
            width: 50%;
            height: 26px;
            text-align: center;
            padding: 4px;
        }

        .sign-table .box-content {
            height: 74px;
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
        <button onclick="window.close()" style="padding: 7px 14px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 12px;">{{ 'Tutup' }}</button>
    </div>

    <div id="print-container"></div>

    <div id="raw-templates" style="display: none;">
        {{-- Header Template --}}
        <div id="tpl-header">
            <div class="header-row">
                <div>
                    <div class="comp-name">{{ strtoupper($company_name) }}</div>
                    @if(!empty($company_city))<div style="font-size: 12px; font-weight: bold;">{{ strtoupper($company_city) }}</div>@endif
                    <div class="party-box">
                        <span class="party-label">{{ 'Informasi' }}</span>
                        <div><strong>{{ 'Penerima' }}:</strong> {{ $hdr->fwhom ?: '-' }}</div>
                        <div style="margin-top: 4px;"><strong>{{ 'Cash / Bank' }}:</strong>
                            {{ trim(($hdr->faccountheader ?? '') . ' - ' . ($hdr->header_account_name ?? ''), ' -') ?: '-' }}
                        </div>
                        <div style="margin-top: 4px;"><strong>{{ 'Keterangan' }}:</strong> {{ $hdr->fket ?: '-' }}</div>
                    </div>
                </div>
                <div style="min-width: 260px;">
                    <div class="doc-title">{{ 'Penerimaan Kas/Bank' }}</div>
                    <div class="doc-no">{{ 'No' }}. {{ $hdr->fkasmtno ?? '-' }}</div>
                    <table class="info-table" style="width: 100%;">
                        <tr>
                            <td style="width: 85px;">{{ 'Tanggal' }}</td>
                            <td style="width: 10px;">:</td>
                            <td>{{ $fmt($hdr->fkasmtdate) }}</td>
                        </tr>
                        <tr>
                            <td>{{ 'No.Giro/Cek' }}</td>
                            <td>:</td>
                            <td>{{ $hdr->fnogiro ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td>{{ 'Tipe Header' }}</td>
                            <td>:</td>
                            <td>{{ $hdr->fdkheader ?: '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- Table Head Template --}}
        <table id="tpl-table">
            <thead id="tpl-thead">
                <tr>
                    <th style="width: 5%; text-align: center;" class="text-center">No</th>
                    <th style="width: 22%;">Account</th>
                    <th style="width: 18%;">Sub Account</th>
                    <th style="width: 15%;">No. Referensi</th>
                    <th>Uraian</th>
                    <th style="width: 8%; text-align: center;" class="text-center">D/K</th>
                    <th style="width: 12%; text-align: right;" class="text-right">Nilai Bayar</th>
                </tr>
            </thead>
            <tbody id="raw-rows">
                @forelse ($dt as $index => $row)
                    <tr class="item-row">
                        <td class="text-center row-no">{{ $index + 1 }}</td>
                        <td>
                            {{ trim(($row->faccount ?? '') . ' - ' . ($row->account_name ?? ''), ' -') ?: '-' }}
                        </td>
                        <td>
                            {{ trim(($row->fsubaccount ?? '') . ' - ' . ($row->subaccount_name ?? ''), ' -') ?: '-' }}
                        </td>
                        <td>{{ $row->frefno ?: '-' }}</td>
                        <td>{{ $row->fnote ?: '-' }}</td>
                        <td class="text-center">{{ $row->fdk ?: '-' }}</td>
                        <td class="text-right">{{ number_format((float) ($row->fkasdtvalue ?? 0), 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr class="item-row">
                        <td colspan="7" class="text-center muted">Tidak ada detail transaksi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Summary Template (Last Page) --}}
        <div id="tpl-summary">
            <div class="summary">
                <div class="summary-box">
                    <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                        <tr style="font-weight: bold; color: var(--blue); font-size: 13px;">
                            <td style="border-top: 1px solid #000; border-bottom: 3px double #000; padding: 4px 0; white-space: nowrap;">Total</td>
                            <td style="border-top: 1px solid #000; border-bottom: 3px double #000; width: 10px; text-align: center; padding: 4px 0;">:</td>
                            <td style="border-top: 1px solid #000; border-bottom: 3px double #000; text-align: right; padding: 4px 0;">{{ number_format($totalAmount, 2, ',', '.') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="footer-line"></div>
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
                        <div style="font-size: 11px;">Diketahui,</div>
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

                    if (getContentHeight(currentSheet.sheet) > MAX_SHEET_CONTENT_HEIGHT) {
                        // Summary & sign tidak muat bersama row ini.
                        // Uji apakah row ini masih muat jika footer hanya baris "Bersambung":
                        currentSheet.footerSlot.innerHTML = '';
                        const contTest = tplContinued.cloneNode(true);
                        contTest.removeAttribute('id');
                        currentSheet.footerSlot.appendChild(contTest);

                        if (getContentHeight(currentSheet.sheet) <= MAX_SHEET_CONTENT_HEIGHT) {
                            // Row muat di sheet ini! Buat sheet baru khusus summary & sign:
                            currentSheet = createSheet();
                            sheets.push(currentSheet);

                            currentSheet.footerSlot.appendChild(summaryClone);
                            currentSheet.footerSlot.appendChild(signClone);
                        } else if (currentSheet.tbody.children.length > 1) {
                            // Row tidak muat walau hanya dengan footer bersambung, pindahkan row ke sheet baru:
                            currentSheet.tbody.removeChild(row);
                            currentSheet.footerSlot.innerHTML = '';

                            const contClone = tplContinued.cloneNode(true);
                            contClone.removeAttribute('id');
                            currentSheet.footerSlot.appendChild(contClone);

                            currentSheet = createSheet();
                            sheets.push(currentSheet);

                            currentSheet.tbody.appendChild(row);
                            currentSheet.footerSlot.appendChild(summaryClone);
                            currentSheet.footerSlot.appendChild(signClone);
                        } else {
                            // Hanya ada 1 row di sheet ini, pindahkan summary & sign ke sheet baru:
                            currentSheet = createSheet();
                            sheets.push(currentSheet);

                            currentSheet.footerSlot.appendChild(summaryClone);
                            currentSheet.footerSlot.appendChild(signClone);
                        }
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

                if (s.tbody.children.length === 0) {
                    s.table.style.display = 'none';
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
@endif
