<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Penjualan Sales By Customer</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&family=IBM+Plex+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&family=Source+Serif+4:ital,opsz,wght@0,8..60,200..900;1,8..60,200..900&display=swap"
        rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Monitor Screen Layout */
        body {
            font-family: 'IBM Plex Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 10px;
            color: #000000;
            background-color: #f1f5f9;
            counter-reset: page;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Screen Simulation Styles for A4 Pages (LANDSCAPE) */
        .page-a4 {
            width: 297mm;
            margin: 20px auto;
            background: white;
            padding: 15mm;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            position: relative;
            box-sizing: border-box;
            height: auto;
            min-height: 0;
        }

        .page-a4-strict {
            height: 210mm !important;
            min-height: 210mm !important;
            overflow: hidden !important;
        }

        .header-section {
            position: relative;
            margin-bottom: 75px;
            text-align: center;
            padding-bottom: 15px;
        }

        .header-section h2 {
            font-family: 'Source Serif 4', Georgia, "Times New Roman", serif;
            font-size: 20px;
            margin-bottom: 6px;
            font-weight: 600;
            text-transform: uppercase;
            color: #cc0000;
            letter-spacing: 0.5px;
        }

        .filter-info {
            font-size: 10px;
            color: #475569;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .supplier-info-kiri {
            position: absolute;
            top: 15px;
            left: 0mm;
            font-size: 10px;
            color: #334155;
            text-align: left;
            line-height: 1.5;
        }

        .info-tambahan {
            position: absolute;
            top: 0;
            right: 0;
            font-size: 10px;
            color: #334155;
            text-align: left;
            line-height: 1.5;
        }

        .info-label {
            font-weight: 600;
            display: inline-block;
            width: 50px;
            color: #475569;
        }

        /* --- TABLE HEADER STYLES --- */
        .sales-header-labels,
        .sales-header {
            display: grid;
            grid-template-columns: 40mm 145mm 82mm;
            gap: 1px;
            font-size: 8.5px;
            padding: 3px 8px;
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
            letter-spacing: 0.5px;
        }

        .sales-header {
            background-color: transparent;
            color: #000000;
        }

        /* --- TABLE DETAIL STYLES --- */
        .sales-detail-labels,
        .sales-detail {
            display: grid;
            grid-template-columns: 40mm 145mm 82mm;
            gap: 1px;
            font-size: 8.5px;
            padding: 2.5px 8px;
            align-items: center;
        }

        .sales-detail {
            color: #334155;
            background-color: transparent;
        }

        .salesman-header-row {
            font-weight: bold;
            font-size: 9px;
            color: #000000;
            background-color: #f1f5f9;
            padding: 4px 8px;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #cbd5e1;
            margin-top: 4px;
        }

        .subtotal-row {
            font-weight: bold;
            font-size: 8.5px;
            color: #cc0000;
            border-top: 1px dashed #cbd5e1;
            border-bottom: 1px solid #000000;
            padding: 3px 8px;
            display: grid;
            grid-template-columns: 40mm 145mm 82mm;
            gap: 1px;
            align-items: center;
        }

        /* Alignment & Fonts */
        .sales-header-labels > div:nth-child(3),
        .sales-detail > div:nth-child(3),
        .subtotal-row > div:nth-child(3) {
            text-align: right;
        }

        .sales-detail > div:nth-child(1),
        .sales-detail > div:nth-child(3),
        .subtotal-row > div:nth-child(3) {
            font-family: 'IBM Plex Mono', Courier, monospace;
            font-variant-numeric: tabular-nums;
        }

        .separator {
            border-bottom: 1px dashed #cbd5e1;
            margin: 2px 0;
        }

        /* Floating Actions Toolbar */
        .no-print {
            position: fixed;
            top: 15px;
            right: 20px;
            z-index: 10000;
            display: flex;
            gap: 8px;
            background: white;
            padding: 8px 14px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            align-items: center;
        }

        .print-button {
            padding: 7px 14px;
            background-color: #1e293b;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background-color 0.2s;
        }
        .print-button:hover {
            background-color: #0f172a;
        }

        /* Totals Panel style */
        .po-totals-panel-wrapper {
            margin-top: 15px;
            width: 267mm;
            border-top: 1px solid #000000;
            padding-top: 8px;
            position: relative;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .end-of-report-inline {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: 5px;
            font-family: 'IBM Plex Sans', sans-serif;
            font-size: 8px;
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .po-totals-container {
            margin-left: auto;
            width: 80mm;
            font-family: 'IBM Plex Mono', Courier, monospace;
            font-size: 9px;
        }

        .po-total-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            color: #000000;
        }

        .po-total-row span:nth-child(2) {
            font-weight: bold;
        }

        .grand-total-row {
            color: #304ee7;
            border-bottom: 1px double #000000;
            padding: 5px 0;
            font-weight: bold;
        }

        /* Print Media CSS Overrides */
        @media print {
            body {
                background-color: white !important;
                margin: 0;
                padding: 0;
            }

            .page-a4 {
                width: 297mm;
                height: 210mm !important;
                margin: 0 auto !important;
                padding: 15mm !important;
                box-shadow: none !important;
                page-break-after: always;
                break-after: always;
                box-sizing: border-box;
                overflow: hidden !important;
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
        <a href="{{ route('reportingrekappenjualansalescustomer.excel', request()->query()) }}"
            style="padding: 7px 12px; background-color: #22c55e; color: white; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; transition: background-color 0.2s;"
            onmouseover="this.style.backgroundColor='#16a34a'" onmouseout="this.style.backgroundColor='#22c55e'">
            📊 Excel
        </a>
    </div>

    {{-- Hidden Raw Data Container --}}
    <div id="raw-source" style="display: none;">
        <div class="header-section">
            <div class="supplier-info-kiri">
                <div style="font-weight: bold; text-transform: uppercase;">{{ $company_name }}</div>
                @if(!empty($company_city))<div>{{ $company_city }}</div>@endif
                @if(!empty($company_address1))<div>{{ $company_address1 }}</div>@endif
                @if(!empty($company_address2))<div>{{ $company_address2 }}</div>@endif
                Cabang: {{ $filters['branch_label'] }}
                <br>Salesman: {{ $filters['salesman_label'] }}
                <br>Customer: {{ $filters['customer_label'] }}
            </div>
            <h2>Rekap Penjualan Sales By Customer</h2>
            <div class="filter-info">
                Periode: {{ $filters['date_from'] }} s/d {{ $filters['date_to'] }}
            </div>
            <div class="info-tambahan">
                <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                <div><span class="info-label">Opr</span>: {{ $user_session->fname ?? $user_session->username ?? $user_session->fuserid ?? 'User' }}</div>
            </div>
        </div>

        {{-- Table Header Labels --}}
        <div class="sales-header-labels">
            <div>No. Customer</div>
            <div>Nama Customer</div>
            <div class="text-right">Jumlah (Rp)</div>
        </div>

        @php $grandTotalNota = 0; @endphp

        @foreach ($rows->groupBy('fsalesman') as $salesCode => $salesRows)
            @php
                $salesTotal = 0;
                $salesName = $salesRows->first()->salesman_name ?? $salesCode;
            @endphp
            <div class="journal-block">
                <div class="salesman-header-row">
                    Salesman: {{ $salesCode }} - {{ $salesName }}
                </div>

                @foreach ($salesRows as $row)
                    @php
                        $salesTotal += (float) $row->totalnota;
                    @endphp
                    <div class="sales-detail">
                        <div class="truncate">{{ $row->fcustno }}</div>
                        <div class="truncate" title="{{ $row->customer }}">{{ $row->customer }}</div>
                        <div>{{ number_format((float) $row->totalnota, 2, ',', '.') }}</div>
                    </div>
                @endforeach

                <div class="subtotal-row">
                    <div></div>
                    <div>TOTAL {{ $salesName }}</div>
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
                    <span>GRAND TOTAL</span>
                    <span>Rp {{ number_format($grandTotalNota, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Screen Render Target --}}
    <div class="report-wrapper" id="reportWrapper">
        @if ($rows->isEmpty())
            <div class="page-a4 page-a4-strict">
                <div class="header-section">
                    <div class="supplier-info-kiri">
                        <div style="font-weight: bold; text-transform: uppercase;">{{ $company_name }}</div>
                        @if(!empty($company_city))<div>{{ $company_city }}</div>@endif
                        @if(!empty($company_address1))<div>{{ $company_address1 }}</div>@endif
                        @if(!empty($company_address2))<div>{{ $company_address2 }}</div>@endif
                        Cabang: {{ $filters['branch_label'] }}
                        <br>Salesman: {{ $filters['salesman_label'] }}
                        <br>Customer: {{ $filters['customer_label'] }}
                    </div>
                    <h2>Rekap Penjualan Sales By Customer</h2>
                    <div class="info-tambahan">
                        <div><span class="info-label">Hal</span>: 1 / 1</div>
                        <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                        <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                        <div><span class="info-label">Opr</span>: {{ $user_session->fname ?? $user_session->username ?? $user_session->fuserid ?? 'User' }}</div>
                    </div>
                    <div style="margin-top: 30px; text-align: center; font-size: 12px; color: #666;">Tidak ada data ditemukan.</div>
                </div>
            </div>
        @endif
    </div>
</body>

</html>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const rawSource = document.getElementById("raw-source");
        const reportWrapper = document.getElementById("reportWrapper");
        if (!rawSource || !reportWrapper) return;

        const journals = Array.from(rawSource.querySelectorAll(".journal-block"));
        if (journals.length === 0) return;

        // Measure actual 210mm landscape page height on the screen dynamically in pixels
        const tempDiv = document.createElement("div");
        tempDiv.style.height = "210mm";
        tempDiv.style.position = "absolute";
        tempDiv.style.visibility = "hidden";
        document.body.appendChild(tempDiv);
        const pageHeightPx = tempDiv.offsetHeight;
        document.body.removeChild(tempDiv);

        // Leave a safety margin to prevent overlapping footers and sub-pixel rounding errors
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
                <div class="page-content" style="margin-top: 5px;"></div>
            `;
            const infoTambahan = page.querySelector(".info-tambahan");
            if (infoTambahan) {
                const halDiv = document.createElement("div");
                halDiv.innerHTML =
                    `<span class="info-label">Hal</span>: <span class="page-number-current"></span> / <span class="page-number-total"></span>`;
                infoTambahan.prepend(halDiv);
            }
            reportWrapper.appendChild(page);
            return page;
        }

        let currentPage = createNewPage();
        let currentContent = currentPage.querySelector(".page-content");

        journals.forEach((journal) => {
            if (journal.classList.contains("force-new-page-before") && currentContent.children.length > 0) {
                currentPage = createNewPage();
                currentContent = currentPage.querySelector(".page-content");
            }

            let currentJournalBlock = document.createElement("div");
            currentJournalBlock.className = "journal-block";
            currentContent.appendChild(currentJournalBlock);

            Array.from(journal.children).forEach((child) => {
                const childClone = child.cloneNode(true);
                currentJournalBlock.appendChild(childClone);

                if (currentPage.offsetHeight > maxPageHeight) {
                    currentJournalBlock.removeChild(childClone);

                    currentPage = createNewPage();
                    currentContent = currentPage.querySelector(".page-content");

                    currentJournalBlock = document.createElement("div");
                    currentJournalBlock.className = "journal-block";
                    currentContent.appendChild(currentJournalBlock);

                    currentJournalBlock.appendChild(childClone);
                }
            });
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

        // Apply strict height class to lock A4 size and hide overflows
        const allPages = reportWrapper.querySelectorAll(".page-a4");
        allPages.forEach((page, index) => {
            page.classList.add("page-a4-strict");
            const currentEl = page.querySelector(".page-number-current");
            const totalEl = page.querySelector(".page-number-total");
            if (currentEl) currentEl.textContent = index + 1;
            if (totalEl) totalEl.textContent = allPages.length;
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
