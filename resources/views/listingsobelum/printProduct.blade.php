<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SO Belum Dikirim By Produk</title>
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
            counter-reset: page;
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

        /* Header */
        .header-section {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .header-left {
            font-size: 10px;
            color: #333;
            font-weight: bold;
            min-width: 80px;
            align-self: flex-end;
            white-space: nowrap;
        }

        .header-center {
            text-align: center;
            flex: 1;
        }

        .header-center h2 {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            color: #c00;
            margin-bottom: 4px;
        }

        .header-center p {
            font-size: 10px;
            color: #333;
        }

        .header-right {
            font-size: 10px;
            color: #333;
            text-align: left;
            line-height: 1.6;
            min-width: 80px;
        }

        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 45px;
        }

        /* Grid Header Kolom */
        .grid-header {
            display: grid;
            grid-template-columns: 35mm 20mm 25mm 20mm 25mm 20mm 25mm;
            gap: 1px;
            padding: 4px 3px;
            font-weight: bold;
            font-size: 9px;
            background-color: #f0f0f0;
            border: 1px solid #000;
            margin-bottom: 1px;
        }

        /* Group Product */
        .prd-group {
            background: #ffe6e6;
            padding: 4px 5px;
            font-weight: bold;
            font-size: 9px;
            border: 1px solid #000;
            margin-top: 5px;
        }

        /* Row Data */
        .grid-row {
            display: grid;
            grid-template-columns: 35mm 20mm 25mm 20mm 25mm 20mm 25mm;
            gap: 1px;
            padding: 3px 3px;
            font-size: 8px;
            background-color: #fff;
            color: #c00;
        }

        /* Subtotal per product */
        .prd-subtotal {
            display: grid;
            grid-template-columns: 35mm 20mm 25mm 20mm 25mm 20mm 25mm;
            gap: 1px;
            padding: 4px 5px;
            font-size: 9px;
            font-weight: bold;
            background: #fff0f0;
            border: 1px solid #000;
            margin-bottom: 2px;
        }

        /* Grand Total */
        .grand-total-section {
            margin-top: 20px;
            border-top: 2px solid #000;
            padding-top: 10px;
        }

        .grand-total-header {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            font-weight: bold;
            padding: 5px 10px;
            background-color: #333;
            color: white;
        }

        /* Alignment */
        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-red {
            color: red;
            font-weight: bold;
        }

        .text-blue {
            color: blue;
        }

        .truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .separator {
            border-bottom: 1px solid #000000;
            margin: 4px 0;
            clear: both;
        }

        /* No Print Toolbar */
        .no-print {
            position: fixed;
            top: 10px;
            left: 10px;
            display: flex;
            gap: 8px;
            z-index: 1000;
            align-items: center;
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

        .journal-block {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Zoom Out Button Style */
        .no-print button {
            transition: background-color 0.2s;
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

    {{-- Toolbar --}}
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

        <a href="{{ route('listingsobelum.excelProduct', request()->query()) }}"
            style="padding: 7px 12px; background-color: #22c55e; color: white; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; transition: background-color 0.2s;"
            onmouseover="this.style.backgroundColor='#16a34a'"
            onmouseout="this.style.backgroundColor='#22c55e'">
            📊 Excel
        </a>
    </div>

    {{-- Hidden Raw Data Container --}}
    <div id="raw-source" style="display: none;">
        <div class="header-section">
            <div class="header-left">
                Customer: {{ request('cust_from') ?? 'Semua' }}
                @if (request('cust_to'))
                    s/d {{ request('cust_to') }}
                @endif
                <br>
                Cabang: {{ request()->has('branch_codes') ? implode(', ', (array) request()->input('branch_codes')) : 'Semua' }}
            </div>
            <div class="header-center">
                <h2>SO Yang Belum Dikirim (By Produk)</h2>
                <p>Periode: {{ $request->date_from }} s/d {{ $request->date_to }}</p>
            </div>
            <div class="header-right">
                <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                <div><span class="info-label">Opr</span>: {{ $user_session->fname ?? 'User' }}</div>
            </div>
        </div>

        {{-- Grid Header --}}
        <div class="grid-header">
            <div>No. SO</div>
            <div>Tanggal</div>
            <div>Nama Customer</div>
            <div class="text-center">Satuan</div>
            <div class="text-right">@ Harga</div>
            <div class="text-right">Qty. Sisa</div>
            <div class="text-right">Qty. Stok</div>
        </div>

        @foreach ($soData as $prdCode => $rows)
            <div class="journal-block">
                <div class="prd-group">
                    Produk: [{{ $prdCode }}] {{ $rows->first()->fprdname }}
                </div>

                @foreach ($rows as $row)
                    <div class="grid-row">
                        <div class="truncate">{{ $row->fsono }}</div>
                        <div>{{ date('d/m/Y', strtotime($row->fsodate)) }}</div>
                        <div class="truncate" style="font-size:8.5px;" title="{{ $row->fcustomername }}">{{ $row->fcustomername }}</div>
                        <div class="text-center">{{ $row->fsatuan }}</div>
                        <div class="text-right">{{ number_format($row->fpricenet, 2, ',', '.') }}</div>
                        <div class="text-right text-red">{{ number_format($row->fqty, 2, ',', '.') }}</div>
                        <div class="text-right text-blue">{{ number_format($row->fstock, 2, ',', '.') }}</div>
                    </div>
                @endforeach

                {{-- Subtotal per Product --}}
                <div class="prd-subtotal">
                    <div style="grid-column: span 5; text-align:right; color:#c00;">
                        Total [{{ $prdCode }}] {{ $rows->first()->fprdname }}
                    </div>
                    <div class="text-right text-red">{{ number_format($rows->sum('fqty'), 2, ',', '.') }}</div>
                    <div></div>
                </div>
            </div>
        @endforeach

        @php
            $grandTotalQty = 0;
            foreach ($soData as $rows) {
                $grandTotalQty += $rows->sum('fqty');
            }
        @endphp

        <div class="grand-total-section">
            <div class="grand-total-header">
                <span>GRAND TOTAL QTY SO BELUM DIKIRIM</span>
                <span>{{ number_format($grandTotalQty, 2, ',', '.') }}</span>
            </div>
        </div>
    </div>

    {{-- Screen Render Target --}}
    <div class="report-wrapper" id="reportWrapper">
        @if ($soData->isEmpty())
            <div class="page-a4 page-a4-strict">
                <div class="header-section">
                    <div class="header-left" style="align-self: flex-start; margin-top: 12mm;">
                        Customer: {{ request('cust_from') ?? 'Semua' }}
                        @if (request('cust_to'))
                            s/d {{ request('cust_to') }}
                        @endif
                        <br>Cabang: {{ request()->has('branch_codes') ? implode(', ', (array) request()->input('branch_codes')) : 'Semua' }}
                    </div>
                    <div class="header-center">
                        <h2>SO Yang Belum Dikirim (By Produk)</h2>
                    </div>
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
        const grandTotalSectionHtml = rawSource.querySelector(".grand-total-section")?.outerHTML;

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
            const prdGroup = journal.querySelector(".prd-group");
            const gridRows = Array.from(journal.querySelectorAll(".grid-row"));
            const prdSubtotal = journal.querySelector(".prd-subtotal");

            // Create a new journal-block container on the current page
            let currentJournalBlock = document.createElement("div");
            currentJournalBlock.className = "journal-block";
            currentContent.appendChild(currentJournalBlock);

            // Append group header
            currentJournalBlock.appendChild(prdGroup.cloneNode(true));

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
                    currentJournalBlock.appendChild(prdGroup.cloneNode(true));
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
                        const headerClone = prdGroup.cloneNode(true);
                        headerClone.textContent = headerClone.textContent + " (Lanjutan)";
                        currentJournalBlock.appendChild(headerClone);

                        // Append the detail row
                        currentJournalBlock.appendChild(rowClone);
                    }
                }
            });

            // Append subtotal if present
            if (prdSubtotal) {
                const subtotalClone = prdSubtotal.cloneNode(true);
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
                    const headerClone = prdGroup.cloneNode(true);
                    headerClone.textContent = headerClone.textContent + " (Lanjutan)";
                    currentJournalBlock.appendChild(headerClone);

                    currentJournalBlock.appendChild(subtotalClone);
                }
            }
        });

        // Add grand total section
        if (grandTotalSectionHtml) {
            const tempDiv = document.createElement("div");
            tempDiv.innerHTML = grandTotalSectionHtml;
            const grandTotalEl = tempDiv.firstElementChild;

            currentPage.appendChild(grandTotalEl);

            if (currentPage.offsetHeight > maxPageHeight) {
                if (currentPage.children.length > 2) {
                    currentPage.removeChild(grandTotalEl);
                    currentPage = createNewPage();
                    currentPage.appendChild(grandTotalEl);
                }
            }
        }

        // Add End of Report text
        const endOfReportEl = document.createElement("div");
        endOfReportEl.className = "end-of-report";
        endOfReportEl.style.textAlign = "center";
        endOfReportEl.style.marginTop = "10px";
        endOfReportEl.style.borderTop = "1px solid #000";
        endOfReportEl.style.paddingTop = "20px";
        endOfReportEl.style.fontWeight = "bold";
        endOfReportEl.style.fontSize = "8px";
        endOfReportEl.style.color = "#555";
        endOfReportEl.style.textTransform = "uppercase";
        endOfReportEl.style.letterSpacing = "1px";
        endOfReportEl.textContent = "** End of Report **";

        currentPage.appendChild(endOfReportEl);

        if (currentPage.offsetHeight > maxPageHeight) {
            currentPage.removeChild(endOfReportEl);
            currentPage = createNewPage();
            currentPage.appendChild(endOfReportEl);
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
