<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listing Penjualan</title>
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

        /* --- JOURNAL HEADER STYLES (11 Kolom) --- */
        .sales-header-labels,
        .sales-header {
            display: grid;
            grid-template-columns: 8mm 18mm 15mm 20mm 19mm 18mm 14mm 20mm 14mm 14mm 15mm;
            gap: 1px;
            font-size: 8px;
            padding: 2px 8px; /* Reduced vertical padding matching PO */
            align-items: center;
        }

        .sales-header-labels {
            background-color: transparent;
            color: #000000;
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
            margin-bottom: 0px; /* Reduced spacing */
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sales-header {
            background-color: transparent;
            color: #000000;
        }

        /* --- JOURNAL DETAIL STYLES (9 Kolom) --- */
        .sales-detail-labels,
        .sales-detail {
            display: grid;
            grid-template-columns: 20mm 40mm 20mm 15mm 15mm 12mm 18mm 12mm 23mm;
            gap: 1px;
            font-size: 8px;
            padding: 2px 8px;
            align-items: center;
        }

        .sales-detail-labels {
            font-weight: bold;
            color: #cc0000;
            background-color: transparent;
            border-bottom: 1px solid #000000;
            margin-top: 0px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sales-detail {
            color: #cc0000;
            background-color: transparent;
        }

        /* Alignment & Monospace Fonts */
        .sales-header-labels > div:nth-child(7),
        .sales-header-labels > div:nth-child(8),
        .sales-header-labels > div:nth-child(9),
        .sales-header-labels > div:nth-child(10),
        .sales-header-labels > div:nth-child(11),
        .sales-header > div:nth-child(7),
        .sales-header > div:nth-child(8),
        .sales-header > div:nth-child(9),
        .sales-header > div:nth-child(10),
        .sales-header > div:nth-child(11),
        .sales-detail-labels > div:nth-child(4),
        .sales-detail-labels > div:nth-child(5),
        .sales-detail-labels > div:nth-child(7),
        .sales-detail-labels > div:nth-child(9),
        .sales-detail > div:nth-child(4),
        .sales-detail > div:nth-child(5),
        .sales-detail > div:nth-child(7),
        .sales-detail > div:nth-child(9) {
            text-align: right;
        }

        .sales-header-labels > div:nth-child(1),
        .sales-header-labels > div:nth-child(4),
        .sales-header > div:nth-child(1),
        .sales-header > div:nth-child(4),
        .sales-detail-labels > div:nth-child(6),
        .sales-detail-labels > div:nth-child(8),
        .sales-detail > div:nth-child(6),
        .sales-detail > div:nth-child(8) {
            text-align: center;
        }

        .sales-header > div:nth-child(1),
        .sales-header > div:nth-child(2),
        .sales-header > div:nth-child(3),
        .sales-header > div:nth-child(4),
        .sales-header > div:nth-child(7),
        .sales-header > div:nth-child(8),
        .sales-header > div:nth-child(9),
        .sales-header > div:nth-child(10),
        .sales-header > div:nth-child(11),
        .sales-detail > div:nth-child(1),
        .sales-detail > div:nth-child(3),
        .sales-detail > div:nth-child(4),
        .sales-detail > div:nth-child(5),
        .sales-detail > div:nth-child(6),
        .sales-detail > div:nth-child(7),
        .sales-detail > div:nth-child(8),
        .sales-detail > div:nth-child(9) {
            font-family: 'IBM Plex Mono', Courier, monospace;
            font-variant-numeric: tabular-nums;
        }

        .sales-header > div:nth-child(1),
        .sales-header > div:nth-child(2),
        .sales-header > div:nth-child(3),
        .sales-header > div:nth-child(4),
        .sales-header > div:nth-child(7),
        .sales-header > div:nth-child(8),
        .sales-header > div:nth-child(9),
        .sales-header > div:nth-child(10),
        .sales-header > div:nth-child(11),
        .sales-detail > div:nth-child(1),
        .sales-detail > div:nth-child(3),
        .sales-detail > div:nth-child(4),
        .sales-detail > div:nth-child(5),
        .sales-detail > div:nth-child(6),
        .sales-detail > div:nth-child(7),
        .sales-detail > div:nth-child(8),
        .sales-detail > div:nth-child(9) {
            font-weight: normal;
        }

        .separator {
            margin: 4px 0;
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
        }

        .print-button {
            background-color: #0f172a; /* Navy slate default */
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            border: none;
            font-weight: 600;
            font-size: 11px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 4px rgba(15, 23, 42, 0.15);
            transition: background-color 0.2s, transform 0.2s;
        }

        .print-button:hover {
            background-color: #000000;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(15, 23, 42, 0.3);
        }

        .journal-block {
            page-break-inside: avoid;
            break-inside: avoid;
            margin-bottom: 3px;
        }

        /* Zoom Out Button Style */
        .no-print button {
            transition: background-color 0.2s;
        }

        /* Totals Panel style */
        .po-totals-panel-wrapper {
            margin-top: 15px;
            width: 180mm; /* Full printable width */
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

        {{-- Excel Download --}}
        <a href="{{ route('listingpenjualan.excel', request()->all()) }}"
            style="padding: 7px 12px; background-color: #22c55e; color: white; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; transition: background-color 0.2s;"
            onmouseover="this.style.backgroundColor='#16a34a'"
            onmouseout="this.style.backgroundColor='#22c55e'">
            📊 Excel
        </a>
    </div>

    @php
        $branchText = request()->has('branch_codes') ? implode(', ', (array) request()->input('branch_codes')) : 'Semua';
        $customerText = request('cust_from') ? '[' . request('cust_from') . ']' : 'Semua';
    @endphp

    {{-- Hidden Raw Data Container --}}
    <div id="raw-source" style="display: none;">
        <div class="header-section">
            <div class="supplier-info-kiri">
                Customer: {{ $customerText }}
                <br>Cabang: {{ $branchText }}
            </div>
            <h2>Listing Penjualan</h2>
            <div class="filter-info">
                Periode: {{ request('date_from') ?? '...' }} s/d {{ request('date_to') ?? '...' }}
            </div>
            <div class="info-tambahan">
                <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                <div><span class="info-label">Opr</span>: {{ $user_session->fname ?? 'admin' }}</div>
            </div>
        </div>

        {{-- Header Labels --}}
        <div class="sales-header-labels">
            <div>Cab.</div>
            <div>No.Faktur</div>
            <div>Tanggal</div>
            <div>Customer</div>
            <div>Salesman</div>
            <div class="text-right">Total Harga</div>
            <div class="text-right">Disc</div>
            <div class="text-right">Netto</div>
            <div class="text-right">PPN</div>
            <div class="text-right">Ongkos</div>
            <div class="text-right">Nilai Faktur</div>
        </div>

        {{-- Detail Labels --}}
        @if ($type == 'detail')
            <div class="sales-detail-labels">
                <div>Kode Barang</div>
                <div>Nama Barang</div>
                <div>No.Ref</div>
                <div class="text-right">Qty.Kirim</div>
                <div class="text-right">Qty.Jual</div>
                <div class="text-center">Satuan</div>
                <div class="text-right">@Harga</div>
                <div class="text-center">Disc%</div>
                <div class="text-right">Jumlah</div>
            </div>
        @endif

        @foreach ($groupedData as $fsono => $details)
            @php $h = $details->first(); @endphp
            <div class="journal-block">
                <div class="sales-header">
                    <div class="truncate">{{ $h->fbranchcode }}</div>
                    <div class="truncate" title="{{ $h->fsono }}">{{ $h->fsono }}</div>
                    <div>{{ date('d/m/y', strtotime($h->fsodate)) }}</div>
                    <div class="truncate" title="{{ $h->fcustomername }}">{{ $h->fcustomername }}</div>
                    <div class="truncate" title="{{ $h->fsalesmanname ?? '-' }}">{{ $h->fsalesmanname ?? '-' }}</div>
                    <div>{{ number_format((float) $h->famountgross, 2, ',', '.') }}</div>
                    <div>{{ number_format((float) $h->fdiscount, 2, ',', '.') }}</div>
                    <div>{{ number_format((float) $h->famountsonet, 2, ',', '.') }}</div>
                    <div>{{ number_format((float) $h->famountpajak, 2, ',', '.') }}</div>
                    <div>{{ number_format((float) $h->fongkosangkut, 2, ',', '.') }}</div>
                    <div>{{ number_format((float) $h->famountso, 2, ',', '.') }}</div>
                </div>

                @if ($type == 'detail')
                    @foreach ($details as $d)
                        <div class="sales-detail">
                            <div class="truncate">{{ $d->fprdcode }}</div>
                            <div class="truncate" title="{{ $d->fprdname }}">{{ $d->fprdname }}</div>
                            <div class="truncate">{{ $d->frefso ?? '-' }}</div>
                            <div>{{ number_format((float) $d->fqtyremain, 2, ',', '.') }}</div>
                            <div>{{ number_format((float) $d->fqty, 2, ',', '.') }}</div>
                            <div>{{ $d->fsatuan }}</div>
                            <div>{{ number_format((float) $d->fprice, 2, ',', '.') }}</div>
                            <div>{{ $d->fdisc }}</div>
                            <div>{{ number_format((float) $d->famount, 2, ',', '.') }}</div>
                        </div>
                    @endforeach
                @endif

                @if (!$loop->last)
                    <div class="separator"></div>
                @endif
            </div>
        @endforeach
    </div>

    @php
        $grandDiscount = 0;
        $grandNetto = 0;
        $grandPPN = 0;
        $grandOngkos = 0;
        $grandTotal = 0;

        foreach ($groupedData as $details) {
            $h = $details->first();
            $grandDiscount += (float) $h->fdiscount;
            $grandNetto += (float) $h->famountsonet;
            $grandPPN += (float) $h->famountpajak;
            $grandOngkos += (float) $h->fongkosangkut;
            $grandTotal += (float) $h->famountso;
        }
    @endphp

    {{-- Hidden Totals Panel Container --}}
    <div id="po-totals-panel-raw" style="display: none;">
        <div class="po-totals-panel-wrapper">
            <div class="end-of-report-inline">** END OF REPORT **</div>
            <div class="po-totals-container">
                <div class="po-total-row">
                    <span>TOTAL DISCOUNT</span>
                    <span>{{ number_format((float) $grandDiscount, 2, ',', '.') }}</span>
                </div>
                <div class="po-total-row">
                    <span>TOTAL NETTO</span>
                    <span>{{ number_format((float) $grandNetto, 2, ',', '.') }}</span>
                </div>
                <div class="po-total-row">
                    <span>TOTAL PPN</span>
                    <span>{{ number_format((float) $grandPPN, 2, ',', '.') }}</span>
                </div>
                <div class="po-total-row">
                    <span>TOTAL ONGKOS</span>
                    <span>{{ number_format((float) $grandOngkos, 2, ',', '.') }}</span>
                </div>
                <div class="po-total-row grand-total-row">
                    <span>GRAND TOTAL</span>
                    <span>Rp {{ number_format((float) $grandTotal, 2, ',', '.') }}</span>
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
                    <h2>Listing Penjualan</h2>
                    <div class="info-tambahan">
                        <div><span class="info-label">Hal</span>: 1 / 1</div>
                        <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                        <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                        <div><span class="info-label">Opr</span>: {{ $user_session->fname ?? 'admin' }}</div>
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
        const salesHeaderLabelsHtml = rawSource.querySelector(".sales-header-labels").outerHTML;
        const salesDetailLabelsHtml = rawSource.querySelector(".sales-detail-labels")?.outerHTML || "";

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
            const poHeader = journal.querySelector(".sales-header");
            const poDetails = Array.from(journal.querySelectorAll(".sales-detail"));
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
                    const detailCount = currentJournalBlock.querySelectorAll(".sales-detail").length;
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
                            firstChildDiv.textContent = firstChildDiv.textContent + " (Lanjutan)";
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
