<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan DP</title>
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
            grid-template-columns: 20mm 45mm 25mm 80mm 30mm 30mm 37mm;
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
            grid-template-columns: 20mm 45mm 25mm 80mm 30mm 30mm 37mm;
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

        /* Alignment & Fonts */
        .sales-header-labels > div:nth-child(5),
        .sales-header-labels > div:nth-child(6),
        .sales-header-labels > div:nth-child(7),
        .sales-header > div:nth-child(5),
        .sales-header > div:nth-child(6),
        .sales-header > div:nth-child(7),
        .sales-detail-labels > div:nth-child(6),
        .sales-detail > div:nth-child(6) {
            text-align: right;
        }

        .sales-header-labels > div:nth-child(1),
        .sales-header-labels > div:nth-child(3),
        .sales-header > div:nth-child(1),
        .sales-header > div:nth-child(3),
        .sales-detail > div:nth-child(3) {
            text-align: center;
        }

        .sales-header > div:nth-child(1),
        .sales-header > div:nth-child(2),
        .sales-header > div:nth-child(3),
        .sales-header > div:nth-child(5),
        .sales-header > div:nth-child(6),
        .sales-header > div:nth-child(7),
        .sales-detail > div:nth-child(2),
        .sales-detail > div:nth-child(3),
        .sales-detail > div:nth-child(6) {
            font-family: 'IBM Plex Mono', Courier, monospace;
            font-variant-numeric: tabular-nums;
        }

        .separator {
            border-bottom: 1px dashed #cbd5e1;
            margin: 2px 0;
        }

        .trx-action-trigger {
            color: #2563eb;
            text-decoration: underline;
            text-decoration-style: dotted;
            cursor: pointer;
            font-weight: bold;
            transition: color 0.15s ease-in-out;
        }
        .trx-action-trigger:hover {
            color: #1d4ed8;
            text-decoration: underline;
        }

        .trx-modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            animation: trxModalFadeIn 0.15s ease-out;
        }
        .trx-modal-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15), 0 10px 10px -5px rgba(0,0,0,0.04);
            width: 330px;
            max-width: 90vw;
            padding: 20px;
            border: 1px solid #e2e8f0;
            transform: scale(0.95);
            animation: trxModalPopIn 0.15s ease-out forwards;
        }
        @keyframes trxModalFadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes trxModalPopIn { from { transform: scale(0.95); } to { transform: scale(1); } }

        .trx-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f1f5f9;
        }
        .trx-modal-title {
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
        }
        .trx-modal-close {
            background: none;
            border: none;
            font-size: 20px;
            color: #64748b;
            cursor: pointer;
            line-height: 1;
            padding: 0 4px;
        }
        .trx-modal-close:hover { color: #0f172a; }
        .trx-modal-desc {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 16px;
        }
        .trx-action-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .trx-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .trx-btn-view {
            background-color: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .trx-btn-view:hover {
            background-color: #dcfce7;
            color: #14532d;
            transform: translateY(-1px);
        }
        .trx-btn-edit {
            background-color: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        .trx-btn-edit:hover {
            background-color: #dbeafe;
            color: #1e3a8a;
            transform: translateY(-1px);
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
        <a href="{{ route('reportingpenjualandp.excel', request()->query()) }}"
            style="padding: 7px 12px; background-color: #22c55e; color: white; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; transition: background-color 0.2s;"
            onmouseover="this.style.backgroundColor='#16a34a'" onmouseout="this.style.backgroundColor='#22c55e'">
            📊 Excel
        </a>
    </div>

    {{-- Hidden Raw Data Container --}}
    <div id="raw-source" style="display: none;">
        <div class="header-section">
            <div class="supplier-info-kiri">
                <div style="font-weight: bold; font-size: 20px; text-transform: uppercase;">{{ $company_name }}</div>
                @if(!empty($company_address1))<div>{{ $company_address1 }}</div>@endif
                @if(!empty($company_address2))<div>{{ $company_address2 }}</div>@endif
                Cabang: {{ $filters['branch_label'] }}
                <br>Customer: {{ $filters['customer_label'] }}
                <br>Sisa DP: {{ $filters['sisa_dp_label'] }}
                <br>Tipe: {{ $filters['report_type'] }}
            </div>
            <h2>Laporan Penjualan DP</h2>
            <div class="filter-info">
                Periode: {{ $filters['date_from'] }} s/d {{ $filters['date_to'] }}
            </div>
            <div class="info-tambahan">
                <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                <div><span class="info-label">Opr</span>: {{ $user_session->fname ?? $user_session->username ?? $user_session->fuserid ?? 'User' }}</div>
            </div>
        </div>

        {{-- Master Header Labels --}}
        <div class="sales-header-labels">
            <div>Cab.</div>
            <div>Faktur DP</div>
            <div>Tanggal</div>
            <div>Customer</div>
            <div>Nilai DP</div>
            <div>Potong DP</div>
            <div>Sisa DP</div>
        </div>

        @php
            $grandNilaiDp = 0;
            $grandPotongDp = 0;
            $grandSisaDp = 0;
        @endphp

        @foreach ($masters as $row)
            @php
                $nilaiDp = (float) $row->famountsonet;
                $potongDp = (float) $row->ftotaldp;
                $sisaDp = (float) $row->fsisadp;
                $grandNilaiDp += $nilaiDp;
                $grandPotongDp += $potongDp;
                $grandSisaDp += $sisaDp;

                $viewUrl = !empty($row->ftranmtid) ? route('invoice.view', $row->ftranmtid) : '#';
                $editUrl = !empty($row->ftranmtid) ? route('invoice.edit', $row->ftranmtid) : '#';
            @endphp
            <div class="journal-block">
                <div class="sales-header">
                    <div class="truncate">{{ $row->fbranchcode }}</div>
                    <div class="truncate" title="{{ $row->fsono }}">
                        @if(!empty($row->ftranmtid))
                            <span class="trx-action-trigger" onclick="openTrxActionModal(event, '{{ $row->fsono }}', '{{ $viewUrl }}', '{{ $editUrl }}')">{{ $row->fsono }}</span>
                        @else
                            {{ $row->fsono }}
                        @endif
                    </div>
                    <div>{{ date('d/m/y', strtotime($row->fsodate)) }}</div>
                    <div class="truncate" title="{{ $row->fcustno }} - {{ $row->fcustname }}">{{ $row->fcustno }} - {{ $row->fcustname }}</div>
                    <div>{{ number_format($nilaiDp, 2, ',', '.') }}</div>
                    <div>{{ number_format($potongDp, 2, ',', '.') }}</div>
                    <div>{{ number_format($sisaDp, 2, ',', '.') }}</div>
                </div>

                @if ($filters['report_type'] === 'DETAIL')
                    @foreach ($detailsByDp->get(trim((string) $row->fsono), collect()) as $detail)
                        <div class="sales-detail">
                            <div></div>
                            <div class="truncate">{{ $detail->fsono }}</div>
                            <div>{{ date('d/m/y', strtotime($detail->fsodate)) }}</div>
                            <div class="truncate">{{ $detail->tipe_label ?? 'Pemakaian DP' }}</div>
                            @php
                                $detailAmt = (float) $detail->famount;
                                $isPemakaian = ($detail->tipe_label ?? 'Pemakaian DP') === 'Pemakaian DP';
                                $val = $isPemakaian ? -abs($detailAmt) : abs($detailAmt);
                            @endphp
                            <div>{{ number_format($val, 2, ',', '.') }}</div>
                            <div></div>
                        </div>
                    @endforeach
                @endif

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
                    <span>TOTAL NILAI DP</span>
                    <span>{{ number_format($grandNilaiDp, 2, ',', '.') }}</span>
                </div>
                <div class="po-total-row">
                    <span>TOTAL POTONG DP</span>
                    <span>{{ number_format($grandPotongDp, 2, ',', '.') }}</span>
                </div>
                <div class="po-total-row grand-total-row">
                    <span>TOTAL SISA DP</span>
                    <span>Rp {{ number_format($grandSisaDp, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Screen Render Target --}}
    <div class="report-wrapper" id="reportWrapper">
        @if ($masters->isEmpty())
            <div class="page-a4 page-a4-strict">
                <div class="header-section">
                    <div class="supplier-info-kiri">
                        <div style="font-weight: bold; font-size: 20px; text-transform: uppercase;">{{ $company_name }}</div>
                        @if(!empty($company_address1))<div>{{ $company_address1 }}</div>@endif
                        @if(!empty($company_address2))<div>{{ $company_address2 }}</div>@endif
                        Cabang: {{ $filters['branch_label'] }}
                        <br>Customer: {{ $filters['customer_label'] }}
                        <br>Sisa DP: {{ $filters['sisa_dp_label'] }}
                        <br>Tipe: {{ $filters['report_type'] }}
                    </div>
                    <h2>Laporan Penjualan DP</h2>
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

    <!-- Action Modal -->
    <div id="trxActionModal" class="trx-modal-backdrop no-print" style="display: none;" onclick="closeTrxActionModal(event)">
        <div class="trx-modal-card" onclick="event.stopPropagation()">
            <div class="trx-modal-header">
                <div class="trx-modal-title">
                    📄 Transaksi <strong id="modalTrxNo"></strong>
                </div>
                <button type="button" class="trx-modal-close" onclick="closeTrxActionModal()">&times;</button>
            </div>
            <div class="trx-modal-body">
                <p class="trx-modal-desc">Pilih tindakan untuk data transaksi ini:</p>
                <div class="trx-action-buttons">
                    <a id="btnViewTrx" href="#" target="_blank" class="trx-btn trx-btn-view" onclick="closeTrxActionModal()">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <span>View Page</span>
                    </a>
                    <a id="btnEditTrx" href="#" target="_blank" class="trx-btn trx-btn-edit" onclick="closeTrxActionModal()">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        <span>Edit Page</span>
                    </a>
                </div>
            </div>
        </div>
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

    function openTrxActionModal(event, sono, viewUrl, editUrl) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        document.getElementById('modalTrxNo').textContent = sono;
        document.getElementById('btnViewTrx').href = viewUrl;
        document.getElementById('btnEditTrx').href = editUrl;
        const modal = document.getElementById('trxActionModal');
        modal.style.display = 'flex';
    }

    function closeTrxActionModal(event) {
        if (!event || event.target === document.getElementById('trxActionModal') || event.target.closest('.trx-modal-close') || event.target.closest('.trx-btn')) {
            const modal = document.getElementById('trxActionModal');
            modal.style.display = 'none';
        }
    }
</script>
