<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listing Pelunasan Supplier</title>
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

        /* --- JOURNAL HEADER STYLES (8 Kolom) --- */
        .po-header-labels,
        .po-header {
            display: grid;
            grid-template-columns: 24mm 16mm 42mm 24mm 16mm 16mm 22mm 10mm;
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
            grid-template-columns: 4mm 26mm 18mm 24mm 16mm 26mm 14mm 20mm 22mm;
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
        .po-header-labels>div:nth-child(5),
        .po-header-labels>div:nth-child(6),
        .po-header-labels>div:nth-child(7),
        .po-header>div:nth-child(5),
        .po-header>div:nth-child(6),
        .po-header>div:nth-child(7) {
            text-align: right;
        }

        .po-detail-labels>div:nth-child(4),
        .po-detail-labels>div:nth-child(5),
        .po-detail-labels>div:nth-child(6),
        .po-detail-labels>div:nth-child(8),
        .po-detail-labels>div:nth-child(9),
        .po-detail>div:nth-child(4),
        .po-detail>div:nth-child(5),
        .po-detail>div:nth-child(6),
        .po-detail>div:nth-child(8),
        .po-detail>div:nth-child(9) {
            text-align: right;
        }

        .po-detail-labels>div:nth-child(7),
        .po-detail>div:nth-child(7) {
            text-align: center;
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
            top: 1mm;
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
        <a href="{{ route('reportingpelunasansupplier.exportExcel', request()->query()) }}"
            style="padding: 7px 12px; background-color: #22c55e; color: white; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; transition: background-color 0.2s;"
            onmouseover="this.style.backgroundColor='#16a34a'"
            onmouseout="this.style.backgroundColor='#22c55e'">
            📊 Excel
        </a>
    </div>

    @php
        // No total init needed
    @endphp

    {{-- Hidden Raw Data Container --}}
    <div id="raw-source" style="display: none;">
        <div class="header-section">
            <div class="supplier-info-kiri">
                Cabang: {{ !empty($filters['branch_codes']) ? implode(', ', (array) $filters['branch_codes']) : 'Semua' }}<br>
                No. Account: {{ $filters['account_no'] !== '' ? $filters['account_no'] : 'Semua' }}<br>
                Salesman: {{ !$filters['all_salesman'] && $filters['salesman'] !== '' ? $filters['salesman'] : 'Semua' }}<br>
                {{-- Dari Supplier: {{ $filters['supplier_from'] !== '' ? $filters['supplier_from'] : 'Awal' }}<br> --}}
                {{-- Sd Supplier: {{ $filters['supplier_to'] !== '' ? $filters['supplier_to'] : 'Akhir' }} --}}
            </div>
            <h2>Listing Pelunasan Supplier</h2>
            <div class="filter-info">
                Periode:
                {{ $filters['date_from'] ? \Carbon\Carbon::parse($filters['date_from'])->format('d/m/Y') : '...' }}
                s/d
                {{ $filters['date_to'] ? \Carbon\Carbon::parse($filters['date_to'])->format('d/m/Y') : '...' }}
            </div>
            <div class="info-tambahan">
                <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                <div><span class="info-label">Opr</span>: {{ $user_session->fname ?? 'User' }}</div>
            </div>
        </div>

        {{-- Header Labels --}}
        <div class="po-header-labels">
            <div>No. Voucher</div>
            <div>Tanggal</div>
            <div>Nama Supplier</div>
            <div>Account</div>
            <div>By.Bank(-/+)</div>
            <div>Adjust(-/+)</div>
            <div>Total Bayar</div>
            <div>Salesman</div>
        </div>

        {{-- Detail Labels --}}
        <div class="po-detail-labels">
            <div></div>
            <div>No. Ref / Faktur</div>
            <div>Tgl. Ref</div>
            <div>Nilai Faktur</div>
            <div>Total Qty</div>
            <div>Sisa Hutang</div>
            <div>Disc%</div>
            <div>Discount</div>
            <div>Nilai Bayar</div>
        </div>

        @foreach ($groupedRecords as $voucherNo => $voucherRecords)
            @php
                $first = $voucherRecords->first();
                $adminFee = (float) ($first->fadminbank ?? 0);
                $adjustment = (float) ($first->fadjustment ?? 0);
                $totalVoucherPayment = (float) $voucherRecords->sum('fkasdtvalue');
            @endphp
            <div class="journal-block">
                <div class="po-header" style="margin-top: 5px;">
                    <div>{{ $voucherNo }}</div>
                    <div>{{ $first->fkasmtdate ? \Carbon\Carbon::parse($first->fkasmtdate)->format('d/m/Y') : '' }}</div>
                    <div class="truncate" title="{{ ($first->fcustomer ? $first->fcustomer . ' - ' : '') . $first->fcustname }}">
                        {{ ($first->fcustomer ? $first->fcustomer . ' - ' : '') . $first->fcustname }}
                    </div>
                    <div>{{ $first->account }}</div>
                    <div>{{ number_format($adminFee, 2, ',', '.') }}</div>
                    <div>{{ number_format($adjustment, 2, ',', '.') }}</div>
                    <div>{{ number_format($totalVoucherPayment, 2, ',', '.') }}</div>
                    <div>{{ $first->fsalesman ?: '-' }}</div>
                </div>

                @foreach ($voucherRecords as $record)
                    <div class="po-detail">
                        <div></div>
                        <div>{{ $record->frefno }}</div>
                        <div>{{ $record->fdate_ref ? \Carbon\Carbon::parse($record->fdate_ref)->format('d/m/Y') : '' }}</div>
                        <div>{{ number_format((float) $record->fnetnota, 2, ',', '.') }}</div>
                        <div>{{ number_format((float) $record->fqty, 2, ',', '.') }}</div>
                        <div>{{ number_format((float) $record->famountremain, 2, ',', '.') }}</div>
                        <div>
                            @if (is_numeric($record->fdiscpersen))
                                {{ (float)$record->fdiscpersen == (int)$record->fdiscpersen ? (int)$record->fdiscpersen : number_format((float)$record->fdiscpersen, 2, ',', '.') }}%
                            @else
                                {{ $record->fdiscpersen ?? '0' }}%
                            @endif
                        </div>
                        <div>{{ number_format((float) $record->fdiscount, 2, ',', '.') }}</div>
                        <div>{{ number_format((float) $record->fkasdtvalue, 2, ',', '.') }}</div>
                    </div>
                @endforeach

                @if (!$loop->last)
                    <div class="separator"></div>
                @endif
            </div>
        @endforeach

        <div class="grand-total-section">
            <div style="overflow: hidden; clear: both; width: 100%;">
                <div class="grand-total-summary-box" style="float: right; width: 250px; font-size: 9px; border: 1px solid #000; background-color: #fff; margin-bottom: 20px;">
                    <div class="grand-total-row" style="display: flex; justify-content: space-between; padding: 5px 8px; border-bottom: 1px solid #ccc;">
                        <span>Total By.Bank :</span>
                        <span>{{ number_format($grandTotal['admin'], 2, ',', '.') }}</span>
                    </div>
                    <div class="grand-total-row" style="display: flex; justify-content: space-between; padding: 5px 8px; border-bottom: 1px solid #ccc;">
                        <span>Total Adjust :</span>
                        <span>{{ number_format($grandTotal['adjustment'], 2, ',', '.') }}</span>
                    </div>
                    <div class="grand-total-row highlight" style="display: flex; justify-content: space-between; padding: 5px 8px; font-weight: bold; color: #c00; background-color: #fff;">
                        <span>Total Bayar :</span>
                        <span>{{ number_format($grandTotal['bayar'], 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <div class="summary-account-section" style="margin-top: 25px; border-top: 2px solid rgb(0, 0, 0); padding-top: 15px; clear: both; width: 100%;">
                <h3 style="font-size: 12px; font-weight: bold; color: #c00; margin-bottom: 8px; text-transform: uppercase;">Summary Account</h3>
                <table class="summary-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="border: 1px solid #ccc; padding: 6px 8px; font-size: 9px; background-color: #fff; color: #c00; font-weight: bold; text-align: left;">No Account</th>
                            <th style="border: 1px solid #ccc; padding: 6px 8px; font-size: 9px; background-color: #fff; color: #c00; font-weight: bold; text-align: left;">Account</th>
                            <th style="border: 1px solid #ccc; padding: 6px 8px; font-size: 9px; background-color: #fff; color: #c00; font-weight: bold; text-align: center;">Giro Mundur</th>
                            <th style="border: 1px solid #ccc; padding: 6px 8px; font-size: 9px; background-color: #fff; color: #c00; font-weight: bold; text-align: right;">Total Bayar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($summaryRows as $row)
                            <tr>
                                <td style="border: 1px solid #ccc; padding: 6px 8px; font-size: 9px;">{{ $row->faccountno }}</td>
                                <td style="border: 1px solid #ccc; padding: 6px 8px; font-size: 9px;">{{ $row->faccname }}</td>
                                <td style="border: 1px solid #ccc; padding: 6px 8px; font-size: 9px; text-align: center;">{{ $row->fgiromundur === '1' ? 'Ya' : 'Tidak' }}</td>
                                <td style="border: 1px solid #ccc; padding: 6px 8px; font-size: 9px; text-align: right;">{{ number_format((float) $row->famountpay, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="border: 1px solid #ccc; padding: 6px 8px; font-size: 9px; text-align: center;">Tidak ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>

    {{-- Screen Render Target --}}
    <div class="report-wrapper" id="reportWrapper">
        @if ($groupedRecords->isEmpty())
            <div class="page-a4 page-a4-strict">
                <div class="header-section">
                    <div class="supplier-info-kiri" style="top: 12mm;">
                        Cabang: {{ !empty($filters['branch_codes']) ? implode(', ', (array) $filters['branch_codes']) : 'Semua' }}<br>
                        No. Account: {{ $filters['account_no'] !== '' ? $filters['account_no'] : 'Semua' }}<br>
                        Salesman: {{ !$filters['all_salesman'] && $filters['salesman'] !== '' ? $filters['salesman'] : 'Semua' }}<br>
                        {{-- Dari Supplier: {{ $filters['supplier_from'] !== '' ? $filters['supplier_from'] : 'Awal' }}<br> --}}
                        {{-- Sd Supplier: {{ $filters['supplier_to'] !== '' ? $filters['supplier_to'] : 'Akhir' }} --}}
                    </div>
                    <h2>Listing Pelunasan Supplier</h2>
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
