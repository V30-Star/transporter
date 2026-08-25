<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Penjualan Sales By Produk</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&family=IBM+Plex+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&family=Source+Serif+4:ital,opsz,wght@0,8..60,200..900;1,8..60,200..900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'IBM Plex Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 10px;
            color: #0f172a;
            background-color: #f1f5f9;
            counter-reset: page;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Screen Simulation Styles for A4 Pages (LANDSCAPE) */
        .page-a4 {
            width: 297mm;
            margin: 30px auto;
            background: white;
            padding: 12mm 18mm;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
            position: relative;
            box-sizing: border-box;
            height: auto;
            min-height: 0;
            border-radius: 4px;
        }

        /* Strict height applied after pagination (LANDSCAPE height is 210mm) */
        .page-a4-strict {
            height: 210mm !important;
            min-height: 210mm !important;
            overflow: hidden !important;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 6px;
        }

        .comp-name {
            font-size: 18px;
            font-weight: bold;
            font-style: italic;
            color: #0f172a;
        }

        .comp-city {
            font-size: 11px;
            color: #475569;
            margin-top: 1px;
        }

        .title-so {
            font-size: 18px;
            color: #0000ff;
            text-decoration: underline;
            font-weight: bold;
            text-align: right;
            text-transform: uppercase;
        }

        .customer-container {
            border: 1px solid #000;
            border-radius: 8px;
            padding: 6px 12px;
            width: 100%;
            position: relative;
            margin-top: 4px;
            margin-bottom: 6px;
        }

        .info-col-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .info-col-table td {
            padding: 1px 2px;
            vertical-align: top;
            line-height: 1.4;
        }

        .info-col-label {
            font-weight: 600;
            color: #334155;
            width: 95px;
        }

        /* --- TABLE HEADER STYLES --- */
        .sales-header-labels,
        .sales-detail,
        .subtotal-row {
            display: grid;
            grid-template-columns: 26mm 1fr 28mm 28mm 32mm;
            gap: 1px;
            font-size: 8.5px;
            padding: 2px 6px;
            align-items: center;
        }

        .sales-header-labels {
            background-color: transparent;
            color: #000000;
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
            margin-bottom: 0px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .sales-detail {
            color: #0f172a;
            background-color: transparent;
            font-weight: normal;
        }

        .salesman-header-row {
            font-weight: bold;
            font-size: 9px;
            color: #0f172a;
            background-color: #f8fafc;
            padding: 3px 6px;
            border-top: 1px solid #cbd5e1;
            border-bottom: 1px solid #cbd5e1;
            margin-top: 4px;
            margin-bottom: 2px;
            text-transform: uppercase;
        }

        .group-header-row {
            font-weight: 600;
            font-size: 8.5px;
            color: #475569;
            padding: 2px 6px 2px 14px;
            margin-top: 2px;
            margin-bottom: 1px;
            font-style: italic;
        }

        .subtotal-row {
            font-weight: bold;
            border-top: 1px dashed #cbd5e1;
            padding: 3px 6px;
            margin-bottom: 2px;
            color: #0f172a;
        }

        .subtotal-salesman-row {
            font-weight: bold;
            background-color: transparent;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 3px 6px;
            margin-bottom: 4px;
            color: #0f172a;
        }

        /* Alignment */
        .sales-header-labels > div:nth-child(3),
        .sales-header-labels > div:nth-child(4),
        .sales-header-labels > div:nth-child(5),
        .sales-detail > div:nth-child(3),
        .sales-detail > div:nth-child(4),
        .sales-detail > div:nth-child(5),
        .subtotal-row > div:nth-child(3),
        .subtotal-row > div:nth-child(4),
        .subtotal-row > div:nth-child(5) {
            text-align: right;
        }

        /* Fonts for Numbers & System Codes */
        .sales-detail > div:nth-child(1),
        .sales-detail > div:nth-child(3),
        .sales-detail > div:nth-child(4),
        .sales-detail > div:nth-child(5),
        .subtotal-row > div:nth-child(3),
        .subtotal-row > div:nth-child(4),
        .subtotal-row > div:nth-child(5) {
            font-family: 'IBM Plex Mono', Courier, monospace;
            font-variant-numeric: tabular-nums;
        }

        .truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .no-print {
            position: fixed;
            top: 15px;
            left: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
            padding: 8px 16px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.15);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }

        .print-button {
            background-color: #0f172a;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            border: none;
            font-weight: 600;
            font-family: 'IBM Plex Sans', sans-serif;
            font-size: 12px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 4px rgba(15, 23, 42, 0.2);
        }

        .print-button:hover {
            background-color: #000000;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(15, 23, 42, 0.3);
        }

        .journal-block {
            page-break-inside: avoid;
            break-inside: avoid;
            margin-bottom: 0px;
        }

        .po-totals-panel-wrapper {
            margin-top: 5px;
            width: 100%;
            border-top: 1px solid #000000;
            padding-top: 5px;
            position: relative;
            page-break-inside: avoid;
            break-inside: avoid;
            text-align: center;
        }

        .end-of-report-inline {
            font-family: 'IBM Plex Sans', sans-serif;
            font-size: 8px;
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .po-totals-container {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            margin-top: 5px;
            padding-right: 5px;
        }

        .po-total-row {
            display: flex;
            justify-content: space-between;
            width: 320px;
            font-size: 9px;
            padding: 2px 0;
        }

        .grand-total-row {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 2px double #000;
            margin-top: 2px;
            padding-top: 4px;
            padding-bottom: 4px;
        }

        @media print {
            body {
                background-color: white !important;
                color: #0f172a !important;
                margin: 0;
                padding: 0;
            }

            .page-a4 {
                width: 297mm;
                height: 210mm !important;
                margin: 0 auto !important;
                padding: 12mm 18mm !important;
                box-shadow: none !important;
                page-break-after: always;
                break-after: always;
                page-break-inside: avoid;
                break-inside: avoid;
                box-sizing: border-box;
                overflow: hidden !important;
                border-radius: 0;
            }

            .no-print {
                display: none !important;
            }

            @page {
                size: A4 landscape;
                margin: 0;
            }
        }
    </style>
</head>

<body>
    @php
        $companySetting = company_setting();
        $companyProject = $companySetting->fproject ?? 'PT. M-Trade';
        $companyCity = $companySetting->fcity ?? '';

        $dateFromFmt = request('date_from') ? date('d-m-Y', strtotime(request('date_from'))) : ($filters['date_from'] ?? '...');
        $dateToFmt = request('date_to') ? date('d-m-Y', strtotime(request('date_to'))) : ($filters['date_to'] ?? '...');
        $period = $dateFromFmt . ' s/d ' . $dateToFmt;
    @endphp

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

        {{-- Excel Download --}}
        <a href="{{ route('reportingrekappenjualansalesproduk.excel', request()->query()) }}"
            style="padding: 7px 12px; background-color: #22c55e; color: white; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; transition: background-color 0.2s;"
            onmouseover="this.style.backgroundColor='#16a34a'" onmouseout="this.style.backgroundColor='#22c55e'">
            📊 Excel
        </a>
    </div>

    {{-- Hidden Raw Data Container --}}
    <div id="raw-source" style="display: none;">
        <div class="header-section">
            <div class="header-row">
                <div>
                    <div class="comp-name">{{ strtoupper($companyProject) }}</div>
                    @if(!empty($companyCity))
                        <div class="comp-city">{{ $companyCity }}</div>
                    @endif
                </div>
                <div>
                    <div class="title-so">Rekap Penjualan Sales By Produk</div>
                </div>
            </div>

            <div class="customer-container">
                <div style="display: flex; justify-content: space-between; align-items: stretch; gap: 15px;">
                    {{-- Kiri --}}
                    <div style="flex: 1; padding-right: 15px; border-right: 1px solid #000;">
                        <table class="info-col-table">
                            <tr>
                                <td class="info-col-label">Cabang</td>
                                <td style="width: 8px;">:</td>
                                <td>{{ $filters['branch_label'] }}</td>
                            </tr>
                            <tr>
                                <td class="info-col-label">Salesman</td>
                                <td>:</td>
                                <td>{{ $filters['salesman_label'] }}</td>
                            </tr>
                            <tr>
                                <td class="info-col-label">Merek</td>
                                <td>:</td>
                                <td>{{ $filters['merek_label'] }}</td>
                            </tr>
                            <tr>
                                <td class="info-col-label">Group Produk</td>
                                <td>:</td>
                                <td>{{ $filters['group_label'] }}</td>
                            </tr>
                        </table>
                    </div>
                    {{-- Kanan --}}
                    <div style="flex: 1; padding-left: 5px;">
                        <table class="info-col-table">
                            <tr>
                                <td class="info-col-label">Periode</td>
                                <td style="width: 8px;">:</td>
                                <td style="font-weight: bold;">{{ $period }}</td>
                            </tr>
                            <tr>
                                <td class="info-col-label">Grouping</td>
                                <td>:</td>
                                <td>{{ $filters['grouping_by'] === 'BY_MEREK' ? 'By Merek' : 'By Group Produk' }}</td>
                            </tr>
                            <tr>
                                <td class="info-col-label">Tanggal</td>
                                <td>:</td>
                                <td>{{ date('d-m-Y') }}</td>
                            </tr>
                            <tr>
                                <td class="info-col-label">Jam</td>
                                <td>:</td>
                                <td>{{ date('H:i') }}</td>
                            </tr>
                            <tr>
                                <td class="info-col-label">Operator</td>
                                <td>:</td>
                                <td>{{ $user_session->fname ?? ($user_session->username ?? 'admin') }}</td>
                            </tr>
                            <tr class="hal-row">
                                <td class="info-col-label">Hal</td>
                                <td>:</td>
                                <td><span class="page-number-current"></span> / <span class="page-number-total"></span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Table Header Labels --}}
        <div class="sales-header-labels">
            <div>Produk#</div>
            <div>Nama Produk</div>
            <div>Qty.Besar</div>
            <div>Qty.Kecil</div>
            <div>Jumlah</div>
        </div>

        @php $grandTotalNota = 0; @endphp

        @foreach ($rows->groupBy('fcustno') as $salesCode => $salesRows)
            @php
                $salesTotal = 0;
                $salesQtyBesar = 0;
                $salesQtyKecil = 0;
                $salesName = $salesRows->first()->salesman_name ?? $salesCode;
            @endphp
            <div class="journal-block">
                <div class="salesman-header-row">
                    Salesman: {{ $salesCode }} - {{ $salesName }}
                </div>

                @foreach ($salesRows->groupBy('fgroupcode') as $groupCode => $groupRows)
                    @php
                        $groupTotal = 0;
                        $groupQtyBesar = 0;
                        $groupQtyKecil = 0;
                    @endphp
                    <div class="group-header-row">
                        Group / Merek: {{ $groupCode }}
                    </div>

                    @foreach ($groupRows as $row)
                        @php
                            $groupTotal += (float) $row->totalnota;
                            $groupQtyBesar += (float) $row->fqtybesar;
                            $groupQtyKecil += (float) $row->fqtykecil;
                        @endphp
                        <div class="sales-detail">
                            <div class="truncate">{{ $row->fprdcode }}</div>
                            <div class="truncate" title="{{ $row->fprdname }}">{{ $row->fprdname }}</div>
                            <div>{{ number_format((float) $row->fqtybesar, 2, ',', '.') }} {{ $row->fsatuanbesar }}</div>
                            <div>{{ number_format((float) $row->fqtykecil, 2, ',', '.') }} {{ $row->fsatuankecil }}</div>
                            <div>{{ number_format((float) $row->totalnota, 2, ',', '.') }}</div>
                        </div>
                    @endforeach

                    <div class="subtotal-row">
                        <div></div>
                        <div>TOTAL {{ $groupCode }} :</div>
                        <div>{{ number_format($groupQtyBesar, 2, ',', '.') }}</div>
                        <div>{{ number_format($groupQtyKecil, 2, ',', '.') }}</div>
                        <div>{{ number_format($groupTotal, 2, ',', '.') }}</div>
                    </div>

                    @php
                        $salesTotal += $groupTotal;
                        $salesQtyBesar += $groupQtyBesar;
                        $salesQtyKecil += $groupQtyKecil;
                    @endphp
                @endforeach

                <div class="subtotal-row subtotal-salesman-row">
                    <div></div>
                    <div>TOTAL {{ $salesName }} :</div>
                    <div>{{ number_format($salesQtyBesar, 2, ',', '.') }}</div>
                    <div>{{ number_format($salesQtyKecil, 2, ',', '.') }}</div>
                    <div>{{ number_format($salesTotal, 2, ',', '.') }}</div>
                </div>

                @php $grandTotalNota += $salesTotal; @endphp
            </div>
        @endforeach
    </div>

    {{-- Hidden Totals Panel Container --}}
    <div id="po-totals-panel-raw" style="display: none;">
        <div class="po-totals-panel-wrapper">
            <div class="end-of-report-inline">** END OF REPORT **</div>
            <div class="po-totals-container">
                <div class="po-total-row grand-total-row">
                    <span>GRAND TOTAL NILAI NOTA</span>
                    <span>Rp {{ number_format((float) $grandTotalNota, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Screen Render Target --}}
    <div class="report-wrapper" id="reportWrapper">
        @if ($rows->isEmpty())
            <div class="page-a4 page-a4-strict">
                <div class="header-section">
                    <div class="header-row">
                        <div>
                            <div class="comp-name">{{ strtoupper($companyProject) }}</div>
                            @if(!empty($companyCity))
                                <div class="comp-city">{{ $companyCity }}</div>
                            @endif
                        </div>
                        <div>
                            <div class="title-so">Rekap Penjualan Sales By Produk</div>
                        </div>
                    </div>
                    <div class="customer-container">
                        <div style="display: flex; justify-content: space-between; align-items: stretch; gap: 15px;">
                            <div style="flex: 1; padding-right: 15px; border-right: 1px solid #000;">
                                <table class="info-col-table">
                                    <tr>
                                        <td class="info-col-label">Cabang</td>
                                        <td style="width: 8px;">:</td>
                                        <td>{{ $filters['branch_label'] }}</td>
                                    </tr>
                                    <tr>
                                        <td class="info-col-label">Salesman</td>
                                        <td>:</td>
                                        <td>{{ $filters['salesman_label'] }}</td>
                                    </tr>
                                    <tr>
                                        <td class="info-col-label">Merek</td>
                                        <td>:</td>
                                        <td>{{ $filters['merek_label'] }}</td>
                                    </tr>
                                    <tr>
                                        <td class="info-col-label">Group Produk</td>
                                        <td>:</td>
                                        <td>{{ $filters['group_label'] }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div style="flex: 1; padding-left: 5px;">
                                <table class="info-col-table">
                                    <tr>
                                        <td class="info-col-label">Periode</td>
                                        <td style="width: 8px;">:</td>
                                        <td style="font-weight: bold;">{{ $period }}</td>
                                    </tr>
                                    <tr>
                                        <td class="info-col-label">Grouping</td>
                                        <td>:</td>
                                        <td>{{ $filters['grouping_by'] === 'BY_MEREK' ? 'By Merek' : 'By Group Produk' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="info-col-label">Tanggal</td>
                                        <td>:</td>
                                        <td>{{ date('d-m-Y') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="info-col-label">Jam</td>
                                        <td>:</td>
                                        <td>{{ date('H:i') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="info-col-label">Operator</td>
                                        <td>:</td>
                                        <td>{{ $user_session->fname ?? ($user_session->username ?? 'admin') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="info-col-label">Hal</td>
                                        <td>:</td>
                                        <td>1 / 1</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div style="margin-top: 30px; text-align: center; font-size: 12px; color: #666;">Tidak ada data ditemukan.</div>
                </div>
            </div>
        @endif
    </div>
</body>

</html>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const rawSource = document.getElementById("raw-source");
        const reportWrapper = document.getElementById("reportWrapper");
        if (!rawSource || !reportWrapper) return;

        const journals = Array.from(rawSource.querySelectorAll(".journal-block"));
        if (journals.length === 0) return;

        // Measure actual 210mm page height on the screen dynamically in pixels (A4 Landscape)
        const tempDiv = document.createElement("div");
        tempDiv.style.height = "210mm";
        tempDiv.style.position = "absolute";
        tempDiv.style.visibility = "hidden";
        document.body.appendChild(tempDiv);
        const pageHeightPx = tempDiv.offsetHeight;
        document.body.removeChild(tempDiv);

        // Leave a safety margin to prevent overflowing A4 height
        const maxPageHeight = pageHeightPx - 20;

        const headerSectionHtml = rawSource.querySelector(".header-section").outerHTML;
        const salesHeaderLabelsHtml = rawSource.querySelector(".sales-header-labels").outerHTML;

        function createNewPage() {
            const page = document.createElement("div");
            page.className = "page-a4";
            page.innerHTML = `
                <div class="page-header-container">
                    ${headerSectionHtml}
                    ${salesHeaderLabelsHtml}
                </div>
                <div class="page-content" style="margin-top: 3px;"></div>
            `;
            reportWrapper.appendChild(page);
            return page;
        }

        let currentPage = createNewPage();
        let currentContent = currentPage.querySelector(".page-content");

        journals.forEach((journal) => {
            let currentJournalBlock = document.createElement("div");
            currentJournalBlock.className = journal.className;
            currentJournalBlock.innerHTML = journal.innerHTML;
            currentContent.appendChild(currentJournalBlock);

            if (currentPage.offsetHeight > maxPageHeight) {
                const blockCount = currentContent.querySelectorAll(".journal-block").length;
                if (blockCount > 1) {
                    currentContent.removeChild(currentJournalBlock);
                    currentPage = createNewPage();
                    currentContent = currentPage.querySelector(".page-content");

                    currentJournalBlock = document.createElement("div");
                    currentJournalBlock.className = journal.className;
                    currentJournalBlock.innerHTML = journal.innerHTML;
                    currentContent.appendChild(currentJournalBlock);
                }
            }
        });

        // Add Totals Panel dynamically right before end of report
        const totalsPanelRaw = document.getElementById("po-totals-panel-raw");
        if (totalsPanelRaw) {
            const totalsClone = totalsPanelRaw.cloneNode(true);
            totalsClone.style.display = "block";
            totalsClone.removeAttribute("id");
            currentPage.appendChild(totalsClone);

            if (currentPage.offsetHeight > maxPageHeight) {
                currentPage.removeChild(totalsClone);
                currentPage = createNewPage();
                currentPage.appendChild(totalsClone);
            }
        }

        // Apply strict height class to lock A4 size and set final page numbers
        const allPages = reportWrapper.querySelectorAll(".page-a4");
        allPages.forEach((page, index) => {
            page.classList.add("page-a4-strict");
            const currentEls = page.querySelectorAll(".page-number-current");
            const totalEls = page.querySelectorAll(".page-number-total");
            currentEls.forEach(el => el.textContent = index + 1);
            totalEls.forEach(el => el.textContent = allPages.length);
        });
    });

    let currentZoom = 1.0;

    function adjustZoom(delta) {
        currentZoom = Math.min(2.0, Math.max(0.3, currentZoom + delta));
        document.getElementById('reportWrapper').style.transform = `scale(${currentZoom})`;
        document.getElementById('reportWrapper').style.transformOrigin = 'top center';
        document.getElementById('zoomLabel').textContent = Math.round(currentZoom * 100) + '%';
    }
</script>
