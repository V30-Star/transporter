<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listing Jurnal Transaksi</title>
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

        .header-section {
            position: relative;
            margin-bottom: 15px;
            text-align: center;
            padding-bottom: 25px;
        }

        .header-section h2 {
            font-size: 18px;
            margin-bottom: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #c00; /* Red style matching sales order */
        }

        .filter-info {
            font-size: 10px;
            color: #333;
            margin-bottom: 5px;
        }

        /* --- JOURNAL HEADER STYLES (7 Kolom) --- */
        .po-header-labels,
        .po-header {
            display: grid;
            grid-template-columns: 60mm 40mm 55mm 15mm;
            gap: 1px;
            font-size: 9px;
            padding: 4px 3px;
        }

        .po-header-labels {
            background-color: #f0f0f0;
            border: 1px solid #000;
            margin-bottom: 1px;
        }

        .po-header {
            background-color: #fff;
            padding: 3px 3px;
        }

        /* --- JOURNAL DETAIL STYLES (9 Kolom) --- */
        .po-detail-labels,
        .po-detail {
            display: grid;
            grid-template-columns: 18mm 30mm 50mm 25mm 25mm 25mm;
            gap: 1px;
            font-size: 8px;
            padding: 2px 3px;
        }

        .po-detail-labels {
            font-weight: bold;
            color: #c00;
            background-color: #fff;
            border: 1px solid #000000;
            margin-top: 1px;
            padding: 3px 3px;
        }

        .po-detail {
            color: #c00;
            background-color: #fff;
        }

        .po-detail>div:first-child {
            padding-left: 2mm;
        }

        /* Alignment */
        .po-header-labels>div:nth-child(6),
        .po-header-labels>div:nth-child(7),
        .po-header>div:nth-child(6),
        .po-header>div:nth-child(7) {
            text-align: right;
        }

        .po-detail-labels>div:nth-child(6),
        .po-detail>div:nth-child(6) {
            text-align: center;
        }

        .po-detail-labels>div:nth-child(7),
        .po-detail-labels>div:nth-child(8),
        .po-detail-labels>div:nth-child(9),
        .po-detail>div:nth-child(7),
        .po-detail>div:nth-child(8),
        .po-detail>div:nth-child(9) {
            text-align: right;
        }

        .separator {
            border-bottom: 1px solid #000000;
            margin: 4px 0;
            clear: both;
        }

        .grand-total-section {
            margin-top: 20px;
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

        .info-tambahan {
            position: absolute;
            top: 0;
            right: 0;
            font-size: 10px;
            color: #333;
            text-align: left;
            line-height: 1.4;
        }

        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 45px;
        }

        .supplier-info-kiri {
            position: absolute;
            top: 12mm;
            left: 0mm;
            font-size: 10px;
            color: #333;
            text-align: left;
        }

        .truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
        <a href="{{ route('listingjurnal.exportExcel', request()->query()) }}"
            style="padding: 7px 12px; background-color: #22c55e; color: white; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; transition: background-color 0.2s;"
            onmouseover="this.style.backgroundColor='#16a34a'"
            onmouseout="this.style.backgroundColor='#22c55e'">
            📊 Excel
        </a>
    </div>

    @php
        $grandTotalDebet = 0;
        $grandTotalKredit = 0;
    @endphp

    {{-- Hidden Raw Data Container --}}
    <div id="raw-source" style="display: none;">
        <div class="header-section">
            <div class="supplier-info-kiri">
                Type Jurnal: {{ !empty($selectedTypes) ? implode(', ', $selectedTypes) : 'Semua' }}
                <br>Cabang: {{ !empty($selectedBranches) ? implode(', ', $selectedBranches) : 'Semua' }}
            </div>
            <h2>Listing Jurnal Transaksi</h2>
            <div class="filter-info">
                Periode:
                {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : '...' }}
                s/d
                {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y') : '...' }}
            </div>
            <div class="info-tambahan">
                <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                <div><span class="info-label">Opr</span>: {{ $user_session->fname ?? 'User' }}</div>
            </div>
        </div>

        {{-- Header Labels --}}
        <div class="po-header-labels">
            <div>No. Jurnal</div>
            <div>Tanggal</div>
            <div>Keterangan</div>
            <div>User-id</div>
        </div>

        {{-- Detail Labels --}}
        <div class="po-detail-labels">
            <div>Account</div>
            <div>Account Name</div>
            <div>Uraian</div>
            <div>Sub Account</div>
            <div>Debet</div>
            <div>Kredit</div>
        </div>

        @foreach ($groupedData as $jurnalNo => $lines)
            @php
                $firstLine = $lines->first();
                $jurnalDateFormatted = !empty($firstLine->fjurnaldate) ? \Carbon\Carbon::parse($firstLine->fjurnaldate)->format('d/m/Y') : '';
            @endphp
            <div class="journal-block">
                <div class="po-header" style="margin-top: 5px;">
                    <div class="truncate">{{ $jurnalNo }}</div>
                    <div>{{ $jurnalDateFormatted }}</div>
                    <div class="truncate" title="{{ $firstLine->fjurnalnote }}">{{ $firstLine->fjurnalnote }}</div>
                    <div>{{ $firstLine->fuserid }}</div>
                </div>

                @foreach ($lines as $dt)
                    @php
                        $grandTotalDebet += (float) $dt->debet;
                        $grandTotalKredit += (float) $dt->kredit;
                    @endphp
                    <div class="po-detail">
                        <div>{{ $dt->faccount }}</div>
                        <div class="truncate" title="{{ $dt->faccname }}">{{ $dt->faccname }}</div>
                        <div class="truncate" title="{{ $dt->fnote }}">{{ $dt->fnote }}</div>
                        <div class="truncate" title="{{ $dt->fsubaccount }}">{{ $dt->fsubaccount }}</div>
                        <div>{{ $dt->debet !== null ? number_format((float) $dt->debet, 2, ',', '.') : '' }}</div>
                        <div>{{ $dt->kredit !== null ? number_format((float) $dt->kredit, 2, ',', '.') : '' }}</div>
                    </div>
                @endforeach

                @if (!$loop->last)
                    <div class="separator"></div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Screen Render Target --}}
    <div class="report-wrapper" id="reportWrapper">
        @if ($groupedData->isEmpty())
            <div class="page-a4 page-a4-strict">
                <div class="header-section">
                    <div class="supplier-info-kiri" style="top: 12mm;">
                        Type Jurnal: {{ !empty($selectedTypes) ? implode(', ', $selectedTypes) : 'Semua' }}
                        <br>Cabang: {{ !empty($selectedBranches) ? implode(', ', $selectedBranches) : 'Semua' }}
                    </div>
                    <h2>Listing Jurnal Transaksi</h2>
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
        const poHeaderLabelsHtml = rawSource.querySelector(".po-header-labels").outerHTML;
        const poDetailLabelsHtml = rawSource.querySelector(".po-detail-labels").outerHTML;
        const grandTotalSectionHtml = rawSource.querySelector(".grand-total-section")?.outerHTML;

        function createNewPage() {
            const page = document.createElement("div");
            page.className = "page-a4";
            page.innerHTML = `
                <div class="page-header-container">
                    ${headerSectionHtml}
                    ${poHeaderLabelsHtml}
                    ${poDetailLabelsHtml}
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
            const poHeader = journal.querySelector(".po-header");
            const poDetails = Array.from(journal.querySelectorAll(".po-detail"));
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
                // Only move to a new page if this is not the only journal on the page
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
                    const detailCount = currentJournalBlock.querySelectorAll(".po-detail").length;
                    const blockCount = currentContent.querySelectorAll(".journal-block").length;

                    // Only split and move to next page if there's more than 1 detail in this block OR more than 1 block on this page.
                    // This prevents infinite loops on exceptionally tall single rows.
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
                        const journalNoDiv = headerClone.querySelector(".truncate");
                        if (journalNoDiv) {
                            journalNoDiv.textContent = journalNoDiv.textContent + " (Lanjutan)";
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

        // Add grand total section
        if (grandTotalSectionHtml) {
            const tempDiv = document.createElement("div");
            tempDiv.innerHTML = grandTotalSectionHtml;
            const grandTotalEl = tempDiv.firstElementChild;

            currentPage.appendChild(grandTotalEl);

            if (currentPage.offsetHeight > maxPageHeight) {
                // If there are other elements on this page, move the grand total to a new page
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
