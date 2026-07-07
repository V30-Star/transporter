<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listing Penjualan Dengan HPP</title>
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

        /* Monitor Screen Layout */
        body {
            font-family: 'IBM Plex Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 10px;
            color: #000000; /* Solid Black text */
            background-color: #f1f5f9; /* Modern light slate background on monitor */
            counter-reset: page;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Screen Simulation Styles for A4 Pages */
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

        /* Strict height applied after pagination */
        .page-a4-strict {
            height: 210mm !important;
            min-height: 210mm !important;
            overflow: hidden !important;
        }

        .header-section {
            position: relative;
            margin-bottom: 10px;
            text-align: center;
            padding-bottom: 20px;
        }

        .header-section h2 {
            font-family: 'Source Serif 4', Georgia, "Times New Roman", serif;
            font-size: 20px;
            margin-bottom: 6px;
            font-weight: 600;
            text-transform: uppercase;
            color: #cc0000; /* Dark Red matching Listing PO */
            letter-spacing: 0.5px;
        }

        .filter-info {
            font-size: 10px;
            color: #475569; /* Slate 600 */
            margin-bottom: 5px;
            font-weight: 500;
        }

        .supplier-info-kiri {
            position: absolute;
            top: 15px; /* Shifted one line up inline with right side metadata */
            left: 0mm;
            font-size: 10px;
            color: #334155; /* Slate 700 */
            text-align: left;
            line-height: 1.5;
        }

        .info-tambahan {
            position: absolute;
            top: 0;
            right: 0;
            font-size: 10px;
            color: #334155; /* Slate 700 */
            text-align: left;
            line-height: 1.5;
        }

        .info-label {
            font-weight: 600;
            display: inline-block;
            width: 50px;
            color: #475569; /* Slate 600 */
        }

        /* --- JOURNAL HEADER STYLES (11 Kolom) --- */
        .invoice-labels,
        .invoice-row {
            display: grid;
            grid-template-columns: 10mm 28mm 20mm 45mm 22mm 23mm 13mm 22mm 25mm 22mm 30mm;
            gap: 1px;
            font-size: 8px;
            padding: 2px 8px; /* Reduced vertical padding matching PO */
            align-items: center;
        }

        .invoice-labels {
            background-color: transparent;
            color: #000000;
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
            margin-bottom: 0px; /* Reduced spacing */
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .invoice-row {
            background-color: transparent;
            color: #000000;
        }

        /* --- JOURNAL DETAIL STYLES (8 Kolom) --- */
        .detail-labels,
        .detail-row {
            display: grid;
            grid-template-columns: 30mm 60mm 25mm 25mm 25mm 30mm 30mm 35mm;
            gap: 1px;
            font-size: 8px;
            padding: 2px 8px;
            align-items: center;
        }

        .detail-labels {
            font-weight: bold;
            color: #cc0000;
            background-color: transparent;
            border-bottom: 1px solid #000000;
            margin-top: 0px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-row {
            color: #cc0000;
            background-color: transparent;
        }

        /* Alignment & Monospace Fonts */
        .invoice-labels > div.right,
        .invoice-row > div.right,
        .detail-labels > div.right,
        .detail-row > div.right {
            text-align: right;
        }

        .invoice-labels > div.center,
        .invoice-row > div.center,
        .detail-labels > div.center,
        .detail-row > div.center {
            text-align: center;
        }

        .invoice-row > div:nth-child(1),
        .invoice-row > div:nth-child(2),
        .invoice-row > div:nth-child(3),
        .invoice-row > div:nth-child(5),
        .invoice-row > div:nth-child(6),
        .invoice-row > div:nth-child(7),
        .invoice-row > div:nth-child(8),
        .invoice-row > div:nth-child(9),
        .invoice-row > div:nth-child(10),
        .invoice-row > div:nth-child(11),
        .detail-row > div:nth-child(1),
        .detail-row > div:nth-child(3),
        .detail-row > div:nth-child(4),
        .detail-row > div:nth-child(5),
        .detail-row > div:nth-child(6),
        .detail-row > div:nth-child(7),
        .detail-row > div:nth-child(8) {
            font-family: 'IBM Plex Mono', Courier, monospace;
            font-variant-numeric: tabular-nums;
        }

        .invoice-row > div:nth-child(1),
        .invoice-row > div:nth-child(2),
        .invoice-row > div:nth-child(3),
        .invoice-row > div:nth-child(5),
        .invoice-row > div:nth-child(6),
        .invoice-row > div:nth-child(7),
        .invoice-row > div:nth-child(8),
        .invoice-row > div:nth-child(9),
        .invoice-row > div:nth-child(10),
        .invoice-row > div:nth-child(11),
        .detail-row > div:nth-child(1),
        .detail-row > div:nth-child(3),
        .detail-row > div:nth-child(4),
        .detail-row > div:nth-child(5),
        .detail-row > div:nth-child(6),
        .detail-row > div:nth-child(7),
        .detail-row > div:nth-child(8) {
            font-weight: normal;
        }

        .separator {
            /* border-bottom: 1px solid #000000; */
            margin: 0px 0;
            clear: both;
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
            background-color: #0f172a; /* Navy-Ink background */
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
            background-color: #000000; /* Black background on hover */
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(15, 23, 42, 0.3);
        }

        .journal-block {
            page-break-inside: avoid;
            break-inside: avoid;
            margin-bottom: 0px;
        }

        /* Zoom Out Button Style */
        .no-print button {
            transition: background-color 0.2s;
        }

        /* Totals Panel style */
        .po-totals-panel-wrapper {
            margin-top: 15px;
            width: 268mm; /* Full printable width */
            border-top: 1px solid #000000; /* Long line above totals */
            padding-top: 8px;
            position: relative; /* Position context for centering */
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .end-of-report-inline {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: 5px; /* Vertically inline with bottom row */
            font-family: 'IBM Plex Sans', sans-serif;
            font-size: 8px;
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .po-totals-container {
            margin-left: auto; /* Push to the right side */
            width: 75mm;
            font-family: 'IBM Plex Mono', Courier, monospace;
            font-size: 8.5px;
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

        .truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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

        <a href="{{ route('listingpenjualanhpp.excel', request()->query()) }}"
            style="padding: 7px 12px; background-color: #22c55e; color: white; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; transition: background-color 0.2s;"
            onmouseover="this.style.backgroundColor='#16a34a'"
            onmouseout="this.style.backgroundColor='#22c55e'">
            📊 Excel
        </a>
    </div>

    @php
        $totalSales = $groupedData->sum(fn($items) => (float) ($items->first()->famountso ?? 0));
        $totalDiscount = $groupedData->sum(fn($items) => (float) ($items->first()->fdiscount ?? 0));
        $totalHpp = $rows->sum('famounthpp');
        $totalLaba = $rows->sum('flabarugi');
        $branchText = request()->has('branch_codes') ? implode(', ', (array) request()->input('branch_codes')) : 'Semua';
        $customerText = request('cust_from') || request('cust_to') ? (request('cust_from') ?: 'Awal') . ' s/d ' . (request('cust_to') ?: 'Akhir') : 'Semua';
    @endphp

    {{-- Hidden Raw Data Container --}}
    <div id="raw-source" style="display: none;">
        <div class="header-section">
            <div class="supplier-info-kiri">
                Customer: {{ $customerText }}
                <br>Cabang: {{ $branchText }}
            </div>
            <h2>Listing Penjualan Dengan HPP</h2>
            <div class="filter-info">
                Periode: {{ request('date_from') ? \Carbon\Carbon::parse(request('date_from'))->format('d/m/Y') : '...' }} s/d {{ request('date_to') ? \Carbon\Carbon::parse(request('date_to'))->format('d/m/Y') : '...' }}
            </div>
            <div class="info-tambahan">
                <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                <div><span class="info-label">Opr</span>: {{ $user_session->fname ?? 'User' }}</div>
            </div>
        </div>

        {{-- Header Labels --}}
        <div class="invoice-labels">
            <div>Cab.</div>
            <div>No.Faktur</div>
            <div>Tanggal</div>
            <div>Nama Customer</div>
            <div>Salesman</div>
            <div class="right">Total Harga</div>
            <div class="right">% Disc</div>
            <div class="right">Discount</div>
            <div class="right">Tot.Setelah Disc</div>
            <div class="right">PPN</div>
            <div class="right">Nilai Faktur</div>
        </div>

        {{-- Detail Labels --}}
        <div class="detail-labels">
            <div>Kode Barang</div>
            <div>Nama Barang</div>
            <div class="right">Quantity</div>
            <div class="right">@ Harga Net</div>
            <div class="right">@ HPP</div>
            <div class="right">Tot.Harga Jual</div>
            <div class="right">Total HPP</div>
            <div class="right">Laba/Rugi</div>
        </div>

        @foreach ($groupedData as $fsono => $items)
            @php $h = $items->first(); @endphp
            <div class="journal-block">
                <div class="invoice-row" style="margin-top: 5px;">
                    <div>{{ $h->fbranchcode }}</div>
                    <div class="truncate" title="{{ $h->fsono }}">{{ $h->fsono }}</div>
                    <div>{{ $h->fsodate ? \Carbon\Carbon::parse($h->fsodate)->format('d/m/Y') : '' }}</div>
                    <div class="truncate" title="{{ $h->fcustname }}">{{ $h->fcustname }}</div>
                    <div class="truncate" title="{{ $h->fsalesman }}">{{ $h->fsalesman }}</div>
                    <div>{{ number_format((float) $h->famountgross, 2, ',', '.') }}</div>
                    <div>{{ number_format((float) $h->fdiscpersen, 2, ',', '.') }}</div>
                    <div>{{ number_format((float) $h->fdiscount, 2, ',', '.') }}</div>
                    <div>{{ number_format((float) $h->famountsonet, 2, ',', '.') }}</div>
                    <div>{{ number_format((float) $h->famountpajak, 2, ',', '.') }}</div>
                    <div>{{ number_format((float) $h->famountso, 2, ',', '.') }}</div>
                </div>

                @foreach ($items as $row)
                    <div class="detail-row">
                        <div class="truncate">{{ $row->fprdcode }}</div>
                        <div class="truncate" title="{{ $row->fprdname }}">{{ $row->fprdname }}</div>
                        <div>{{ number_format((float) $row->fqty, 2, ',', '.') }} {{ $row->fsatuan }}</div>
                        <div>{{ number_format((float) $row->fpricenet, 2, ',', '.') }}</div>
                        <div>{{ number_format((float) $row->fhpp, 2, ',', '.') }}</div>
                        <div>{{ number_format((float) $row->famountsales, 2, ',', '.') }}</div>
                        <div>{{ number_format((float) $row->famounthpp, 2, ',', '.') }}</div>
                        <div>{{ number_format((float) $row->flabarugi, 2, ',', '.') }}</div>
                    </div>
                @endforeach

                <div class="detail-row" style="font-weight: bold;">
                    <div style="grid-column: span 7;" class="right">Total Laba/Rugi {{ $h->fsono }}</div>
                    <div>{{ number_format((float) $items->sum('flabarugi'), 2, ',', '.') }}</div>
                </div>

                @if (!$loop->last)
                    <div class="separator"></div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Hidden Totals Panel Container --}}
    <div id="po-totals-panel-raw" style="display: none;">
        <div class="po-totals-panel-wrapper">
            <div class="end-of-report-inline">** END OF REPORT **</div>
            <div class="po-totals-container">
                <div class="po-total-row">
                    <span>TOTAL PENJUALAN</span>
                    <span>{{ number_format((float) $totalSales, 2, ',', '.') }}</span>
                </div>
                <div class="po-total-row">
                    <span>TOTAL DISCOUNT</span>
                    <span>{{ number_format((float) $totalDiscount, 2, ',', '.') }}</span>
                </div>
                <div class="po-total-row">
                    <span>TOTAL HPP</span>
                    <span>{{ number_format((float) $totalHpp, 2, ',', '.') }}</span>
                </div>
                <div class="po-total-row grand-total-row">
                    <span>TOTAL LABA/RUGI</span>
                    <span>Rp {{ number_format((float) $totalLaba, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Screen Render Target --}}
    <div class="report-wrapper" id="reportWrapper">
        @if ($groupedData->isEmpty())
            <div class="page-a4 page-a4-strict">
                <div class="header-section">
                    <div class="supplier-info-kiri" style="top: 15px;">
                        Customer: {{ $customerText }}
                        <br>Cabang: {{ $branchText }}
                    </div>
                    <h2>Listing Penjualan Dengan HPP</h2>
                    <div class="info-tambahan">
                        <div><span class="info-label">Hal</span>: 1 / 1</div>
                        <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                        <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                        <div><span class="info-label">Opr</span>: {{ $user_session->fname ?? 'User' }}</div>
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

        // Measure actual 210mm landscape page height on the screen dynamically in pixels
        const tempDiv = document.createElement("div");
        tempDiv.style.height = "210mm";
        tempDiv.style.position = "absolute";
        tempDiv.style.visibility = "hidden";
        document.body.appendChild(tempDiv);
        const pageHeightPx = tempDiv.offsetHeight;
        document.body.removeChild(tempDiv);

        // Leave a safety margin (e.g. 20px) to prevent overlapping footers and sub-pixel rounding errors
        const maxPageHeight = pageHeightPx - 20;

        const headerSectionHtml = rawSource.querySelector(".header-section").outerHTML;
        const salesHeaderLabelsHtml = rawSource.querySelector(".invoice-labels").outerHTML;
        const salesDetailLabelsHtml = rawSource.querySelector(".detail-labels")?.outerHTML || "";

        function createNewPage() {
            const page = document.createElement("div");
            page.className = "page-a4";
            page.innerHTML = `
                <div class="page-header-container">
                    ${headerSectionHtml}
                    ${salesHeaderLabelsHtml}
                    ${salesDetailLabelsHtml}
                </div>
                <div class="page-content" style="margin-top: 5px;"></div>
            `;
            const infoTambahan = page.querySelector(".info-tambahan");
            if (infoTambahan) {
                const halDiv = document.createElement("div");
                halDiv.innerHTML = `<span class="info-label">Hal</span>: <span class="page-number-current"></span> / <span class="page-number-total"></span>`;
                infoTambahan.prepend(halDiv);
            }
            reportWrapper.appendChild(page);
            return page;
        }

        let currentPage = createNewPage();
        let currentContent = currentPage.querySelector(".page-content");

        journals.forEach((journal) => {
            const poHeader = journal.querySelector(".invoice-row");
            const poDetails = Array.from(journal.querySelectorAll(".detail-row"));
            const separator = journal.querySelector(".separator");

            // Create a new journal-block container on the current page
            let currentJournalBlock = document.createElement("div");
            currentJournalBlock.className = "journal-block";
            currentContent.appendChild(currentJournalBlock);

            // Append header
            currentJournalBlock.appendChild(poHeader.cloneNode(true));

            // Check if page overflowed after adding header
            if (currentPage.offsetHeight > maxPageHeight) {
                const blockCount = currentContent.querySelectorAll(".journal-block").length;
                if (blockCount > 1) {
                    currentContent.removeChild(currentJournalBlock);
                    currentPage = createNewPage();
                    currentContent = currentPage.querySelector(".page-content");

                    currentJournalBlock = document.createElement("div");
                    currentJournalBlock.className = "journal-block";
                    currentContent.appendChild(currentJournalBlock);
                    currentJournalBlock.appendChild(poHeader.cloneNode(true));
                }
            }

            // Append details one by one
            poDetails.forEach((detail) => {
                const detailClone = detail.cloneNode(true);
                currentJournalBlock.appendChild(detailClone);

                // Check overflow
                if (currentPage.offsetHeight > maxPageHeight) {
                    const detailCount = currentJournalBlock.querySelectorAll(".detail-row").length;
                    const blockCount = currentContent.querySelectorAll(".journal-block").length;

                    if (blockCount > 1 || detailCount > 1) {
                        currentJournalBlock.removeChild(detailClone);

                        // Create new page
                        currentPage = createNewPage();
                        currentContent = currentPage.querySelector(".page-content");

                        // Create a new journal block on the new page
                        currentJournalBlock = document.createElement("div");
                        currentJournalBlock.className = "journal-block";
                        currentContent.appendChild(currentJournalBlock);

                        // Append header clone with "(Lanjutan)" suffix
                        const headerClone = poHeader.cloneNode(true);
                        const firstChildDiv = headerClone.firstElementChild;
                        if (firstChildDiv) {
                            // First child is branch, next is fsono
                            const fsonoDiv = headerClone.children[1];
                            if (fsonoDiv) {
                                fsonoDiv.textContent = fsonoDiv.textContent + " (Lanjutan)";
                            }
                        }
                        currentJournalBlock.appendChild(headerClone);

                        // Append the detail row
                        currentJournalBlock.appendChild(detailClone);
                    }
                }
            });

            // Append separator if present
            if (separator) {
                const separatorClone = separator.cloneNode(true);
                currentJournalBlock.appendChild(separatorClone);

                // If separator overflows, remove it since a page break is happening anyway
                if (currentPage.offsetHeight > maxPageHeight) {
                    currentJournalBlock.removeChild(separatorClone);
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
