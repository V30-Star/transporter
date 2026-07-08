<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SO Belum Dikirim By Customer</title>
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
            width: 210mm;
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
            height: 297mm !important;
            min-height: 297mm !important;
            overflow: hidden !important;
        }

        .header-section {
            position: relative;
            margin-bottom: 0px;
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

        /* --- COLUMN STYLES (7 Kolom) --- */
        .grid-header,
        .grid-row,
        .cust-subtotal {
            display: grid;
            grid-template-columns: 25mm 18mm 45mm 15mm 24mm 24mm 24mm;
            gap: 1px;
            font-size: 8px;
            padding: 2px 8px; /* Reduced vertical padding matching PO */
            align-items: center;
        }

        .grid-header {
            background-color: transparent;
            color: #000000;
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
            margin-bottom: 0px; /* Reduced spacing */
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .grid-row {
            background-color: transparent;
            color: #000000;
        }

        .cust-group {
            display: block;
            background-color: transparent;
            color: #000000;
            font-weight: bold;
            font-size: 8.5px;
            padding: 2px 8px;
            margin-top: 2px;
            margin-bottom: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .cust-subtotal {
            background-color: transparent;
            font-weight: bold;
        }

        /* Alignment & Monospace Fonts */
        .grid-header > div.text-right,
        .grid-row > div.text-right,
        .cust-subtotal > div.text-right {
            text-align: right;
        }

        .grid-header > div.text-center,
        .grid-row > div.text-center,
        .cust-subtotal > div.text-center {
            text-align: center;
        }

        .grid-row > div:nth-child(1),
        .grid-row > div:nth-child(2),
        .grid-row > div:nth-child(4),
        .grid-row > div:nth-child(5),
        .grid-row > div:nth-child(6),
        .grid-row > div:nth-child(7),
        .cust-subtotal > div.text-right {
            font-family: 'IBM Plex Mono', Courier, monospace;
            font-variant-numeric: tabular-nums;
        }

        .grid-row > div:nth-child(1),
        .grid-row > div:nth-child(2),
        .grid-row > div:nth-child(4),
        .grid-row > div:nth-child(5),
        .grid-row > div:nth-child(6),
        .grid-row > div:nth-child(7) {
            font-weight: normal; /* Normal weight for detail items */
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
            margin-top: 5px;
            border-top: 1px solid #000000;
            width: 180mm; /* Full printable width */
            padding-top: 5px;
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
            width: 70mm;
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
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
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
                width: 210mm;
                height: 297mm !important;
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
                size: A4 portrait;
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

        <a href="{{ route('listingsobelum.excelCustomer', request()->query()) }}"
            style="padding: 7px 12px; background-color: #22c55e; color: white; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; transition: background-color 0.2s;"
            onmouseover="this.style.backgroundColor='#16a34a'"
            onmouseout="this.style.backgroundColor='#22c55e'">
            📊 Excel
        </a>
    </div>

    @php
        $branchText = request()->has('branch_codes') ? implode(', ', (array) request()->input('branch_codes')) : 'Semua';
        $customerText = request('cust_from') ? request('cust_from') . (request('cust_to') ? ' s/d ' . request('cust_to') : '') : 'Semua';
        $isRekap = ($reportType ?? request('report_type', 'detail')) === 'rekap';
    @endphp

    {{-- Hidden Raw Data Container --}}
    <div id="raw-source" style="display: none;">
        <div class="header-section">
            <div class="supplier-info-kiri">
                Customer: {{ $customerText }}
                <br>Cabang: {{ $branchText }}
            </div>
            <h2>SO Yang Belum Dikirim</h2>
            <div class="filter-info">
                Periode: {{ $request->date_from ? \Carbon\Carbon::parse($request->date_from)->format('d/m/Y') : '...' }} s/d {{ $request->date_to ? \Carbon\Carbon::parse($request->date_to)->format('d/m/Y') : '...' }}
                | Pengelompokan: Customer | Jenis: {{ $isRekap ? 'Rekap' : 'Detail' }}
            </div>
            <div class="info-tambahan">
                <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                <div><span class="info-label">Opr</span>: {{ $user_session->fname ?? 'User' }}</div>
            </div>
        </div>

        @if ($isRekap)
            <div class="grid-header">
                <div style="grid-column: span 5;">Customer</div>
                <div class="text-right">Qty. Sisa</div>
                <div class="text-right">Qty. Stok</div>
            </div>

            @foreach ($soData as $row)
                <div class="journal-block">
                    <div class="cust-group">Customer: {{ $row->fcustomername }}</div>
                    <div class="grid-row">
                        <div style="grid-column: span 5;" class="truncate">{{ $row->fcustno }} - {{ $row->fcustomername }}</div>
                        <div class="text-right" style="font-weight: bold;">{{ number_format((float) $row->fqty, 2, ',', '.') }}</div>
                        <div class="text-right">{{ number_format((float) $row->fstock, 2, ',', '.') }}</div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="grid-header">
                <div>No. SO</div>
                <div>Tanggal</div>
                <div>Nama Barang</div>
                <div class="text-center">Satuan</div>
                <div class="text-right">@ Harga</div>
                <div class="text-right">Qty. Sisa</div>
                <div class="text-right">Qty. Stok</div>
            </div>

            @foreach ($soData as $custId => $rows)
                <div class="journal-block">
                    <div class="cust-group">
                        Customer: {{ $rows->first()->fcustomername }}
                    </div>

                    @foreach ($rows as $row)
                        <div class="grid-row">
                            <div class="truncate">{{ $row->fsono }}</div>
                            <div>{{ date('d/m/Y', strtotime($row->fsodate)) }}</div>
                            <div class="truncate" title="{{ $row->fprdname }}">{{ $row->fprdname }}</div>
                            <div class="text-center">{{ $row->fsatuan }}</div>
                            <div class="text-right">{{ number_format((float) $row->fpricenet, 2, ',', '.') }}</div>
                            <div class="text-right" style="font-weight: bold;">{{ number_format((float) $row->fqty, 2, ',', '.') }}</div>
                            <div class="text-right">{{ number_format((float) $row->fstock, 2, ',', '.') }}</div>
                        </div>
                    @endforeach

                    <div class="cust-subtotal">
                        <div style="grid-column: span 5; text-align:right;">
                            Total {{ $rows->first()->fcustomername }}
                        </div>
                        <div class="text-right">{{ number_format((float) $rows->sum('fqty'), 2, ',', '.') }}</div>
                        <div></div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    @php
        $grandTotalQty = 0;
        $grandTotalQty = $isRekap ? $soData->sum('fqty') : $soData->sum(fn ($rows) => $rows->sum('fqty'));
    @endphp

    {{-- Hidden Totals Panel Container --}}
    <div id="po-totals-panel-raw" style="display: none;">
        <div class="po-totals-panel-wrapper">
            <div class="end-of-report-inline">** END OF REPORT **</div>
            <div class="po-totals-container">
                <div class="po-total-row grand-total-row">
                    <span>GRAND TOTAL QTY</span>
                    <span>{{ number_format((float) $grandTotalQty, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Screen Render Target --}}
    <div class="report-wrapper" id="reportWrapper">
        @if ($soData->isEmpty())
            <div class="page-a4 page-a4-strict">
                <div class="header-section">
                    <div class="supplier-info-kiri" style="top: 15px;">
                        Customer: {{ $customerText }}
                        <br>Cabang: {{ $branchText }}
                    </div>
                    <h2>SO Yang Belum Dikirim</h2>
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

        // Measure actual 297mm page height on the screen dynamically in pixels
        const tempDiv = document.createElement("div");
        tempDiv.style.height = "297mm";
        tempDiv.style.position = "absolute";
        tempDiv.style.visibility = "hidden";
        document.body.appendChild(tempDiv);
        const pageHeightPx = tempDiv.offsetHeight;
        document.body.removeChild(tempDiv);

        // Leave a safety margin (e.g. 20px) to prevent overlapping footers and sub-pixel rounding errors
        const maxPageHeight = pageHeightPx - 20;

        const headerSectionHtml = rawSource.querySelector(".header-section").outerHTML;
        const gridHeaderHtml = rawSource.querySelector(".grid-header").outerHTML;

        function createNewPage() {
            const page = document.createElement("div");
            page.className = "page-a4";
            page.innerHTML = `
                <div class="page-header-container">
                    ${headerSectionHtml}
                    ${gridHeaderHtml}
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
            const custGroup = journal.querySelector(".cust-group");
            const gridRows = Array.from(journal.querySelectorAll(".grid-row"));
            const custSubtotal = journal.querySelector(".cust-subtotal");

            // Create a new journal-block container on the current page
            let currentJournalBlock = document.createElement("div");
            currentJournalBlock.className = "journal-block";
            currentContent.appendChild(currentJournalBlock);

            // Append group header
            currentJournalBlock.appendChild(custGroup.cloneNode(true));

            // Check if page overflowed after adding group header
            if (currentPage.offsetHeight > maxPageHeight) {
                const blockCount = currentContent.querySelectorAll(".journal-block").length;
                if (blockCount > 1) {
                    currentContent.removeChild(currentJournalBlock);
                    currentPage = createNewPage();
                    currentContent = currentPage.querySelector(".page-content");

                    currentJournalBlock = document.createElement("div");
                    currentJournalBlock.className = "journal-block";
                    currentContent.appendChild(currentJournalBlock);
                    currentJournalBlock.appendChild(custGroup.cloneNode(true));
                }
            }

            // Append details one by one
            gridRows.forEach((row) => {
                const rowClone = row.cloneNode(true);
                currentJournalBlock.appendChild(rowClone);

                // Check overflow
                if (currentPage.offsetHeight > maxPageHeight) {
                    const rowCount = currentJournalBlock.querySelectorAll(".grid-row").length;
                    const blockCount = currentContent.querySelectorAll(".journal-block").length;

                    if (blockCount > 1 || rowCount > 1) {
                        currentJournalBlock.removeChild(rowClone);

                        // Create new page
                        currentPage = createNewPage();
                        currentContent = currentPage.querySelector(".page-content");

                        // Create a new journal block on the new page
                        currentJournalBlock = document.createElement("div");
                        currentJournalBlock.className = "journal-block";
                        currentContent.appendChild(currentJournalBlock);

                        // Append header clone with "(Lanjutan)" suffix
                        const headerClone = custGroup.cloneNode(true);
                        headerClone.textContent = headerClone.textContent + " (Lanjutan)";
                        currentJournalBlock.appendChild(headerClone);

                        // Append the detail row
                        currentJournalBlock.appendChild(rowClone);
                    }
                }
            });

            // Append subtotal if present
            if (custSubtotal) {
                const subtotalClone = custSubtotal.cloneNode(true);
                currentJournalBlock.appendChild(subtotalClone);

                // If subtotal overflows, move to next page
                if (currentPage.offsetHeight > maxPageHeight) {
                    currentJournalBlock.removeChild(subtotalClone);

                    currentPage = createNewPage();
                    currentContent = currentPage.querySelector(".page-content");

                    currentJournalBlock = document.createElement("div");
                    currentJournalBlock.className = "journal-block";
                    currentContent.appendChild(currentJournalBlock);

                    // Add group header clone with "(Lanjutan)"
                    const headerClone = custGroup.cloneNode(true);
                    headerClone.textContent = headerClone.textContent + " (Lanjutan)";
                    currentJournalBlock.appendChild(headerClone);

                    currentJournalBlock.appendChild(subtotalClone);
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
