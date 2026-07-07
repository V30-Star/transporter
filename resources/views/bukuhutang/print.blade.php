<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Hutang</title>
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
            margin-bottom: 10px;
            text-align: center;
            padding-bottom: 20px;
        }

        .header-section h2 {
            font-family: 'Source Serif 4', Georgia, "Times New Roman", serif;
            font-size: 20px;
            margin-bottom: 2px;
            font-weight: 600;
            text-transform: uppercase;
            color: #cc0000; /* Dark Red matching Listing PO */
            letter-spacing: 0.5px;
        }

        .filter-info {
            font-size: 10px;
            color: #475569; /* Slate 600 */
            margin-bottom: 0px;
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

        /* --- LEDGER COLUMN STYLES (6 Kolom) --- */
        .ledger-labels,
        .ledger-row,
        .ledger-total {
            display: grid;
            grid-template-columns: 25mm 32mm 38mm 25mm 25mm 30mm;
            gap: 1px;
            font-size: 8px;
            padding: 2px 8px; /* Reduced vertical padding matching PO */
            align-items: center;
        }

        .ledger-labels {
            background-color: transparent;
            color: #000000;
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
            margin-bottom: 0px; /* Reduced spacing */
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .ledger-row {
            background-color: transparent;
            color: #cc0000;
        }

        .group-heading {
            display: block;
            background-color: transparent;
            color: #000000;
            font-size: 8.5px;
            padding: 0px 8px;
            /* border-bottom: 1px solid #000000; */
            margin-top: 10px;
            margin-bottom: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .customer-heading {
            display: block;
            background-color: transparent;
            color: #000000;
            font-size: 8px;
            padding: 0px 8px;
            /* border-bottom: 1px dashed #000000; */
            margin-top: 6px;
            margin-bottom: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .ledger-total {
            background-color: transparent;
            font-weight: bold;
            /* border-top: 1px dashed #000000; Subtle dashed separator */
            /* border-bottom: 1px solid #000000; */
        }

        .account-total {
            border-bottom: 1px double #000000; /* Double border for account totals */
        }

        /* Alignment & Monospace Fonts */
        .ledger-labels > div.right,
        .ledger-row > div.right,
        .ledger-total > div.right {
            text-align: right;
        }

        .ledger-row > div:nth-child(1),
        .ledger-row > div:nth-child(2),
        .ledger-row > div:nth-child(3),
        .ledger-row > div:nth-child(4),
        .ledger-row > div:nth-child(5),
        .ledger-row > div:nth-child(6),
        .ledger-total > div.right {
            font-family: 'IBM Plex Mono', Courier, monospace;
            font-variant-numeric: tabular-nums;
        }

        .ledger-row > div:nth-child(1),
        .ledger-row > div:nth-child(2),
        .ledger-row > div:nth-child(3),
        .ledger-row > div:nth-child(4),
        .ledger-row > div:nth-child(5),
        .ledger-row > div:nth-child(6) {
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
            width: 100%;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .end-of-report-inline {
            font-family: 'IBM Plex Sans', sans-serif;
            font-size: 8px;
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-top: 1px solid #000000;
            padding-top: 15px;
            margin-top: 10px;
            text-align: center;
            width: 100%;
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

        <a href="{{ route('bukuhutang.excel', request()->query()) }}"
            style="padding: 7px 12px; background-color: #22c55e; color: white; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; transition: background-color 0.2s;"
            onmouseover="this.style.backgroundColor='#16a34a'"
            onmouseout="this.style.backgroundColor='#22c55e'">
            📊 Excel
        </a>
    </div>

    @php
        $branchText = request()->has('branch_codes') ? implode(', ', (array) request()->input('branch_codes')) : 'Semua';
        $supplierText = trim((string) request('supplier_from')) !== '' || trim((string) request('supplier_to')) !== ''
            ? (request('supplier_from') ?: 'Awal') . ' s/d ' . (request('supplier_to') ?: 'Akhir')
            : 'Semua';
    @endphp

    {{-- Hidden Raw Data Container --}}
    <div id="raw-source" style="display: none;">
        <div class="header-section">
            <div class="supplier-info-kiri">
                Supplier: {{ $supplierText }}
                <br>Cabang: {{ $branchText }}
            </div>
            <h2>Buku Hutang</h2>
            <div class="filter-info">
                Periode:
                {{ request('date_from') ? \Carbon\Carbon::parse(request('date_from'))->format('d/m/Y') : '...' }}
                s/d
                {{ request('date_to') ? \Carbon\Carbon::parse(request('date_to'))->format('d/m/Y') : '...' }}
                | Urut Berdasarkan: No.Invoice
            </div>
            <div class="info-tambahan">
                <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                <div><span class="info-label">Opr</span>: {{ auth()->user()->fname ?? 'User' }}</div>
            </div>
        </div>

        <div class="ledger-labels">
            <div>Tanggal</div>
            <div>Jurnal</div>
            <div>No.Invoice</div>
            <div class="right">Debet</div>
            <div class="right">Kredit</div>
            <div class="right">Saldo</div>
        </div>

        @forelse ($rows->groupBy('faccount') as $account => $accountRows)
            @php
                $accountName = $accountRows->first()->faccname ?: $account;
                $accountDebit = 0;
                $accountCredit = 0;
                $accountSaldo = 0;
            @endphp

            <div class="journal-block group-heading">
                {{ $account }} - {{ $accountName }}
            </div>

            @foreach ($accountRows->groupBy('fsupplier') as $supplier => $items)
                @php
                    $supplierName = $items->first()->fsuppliername ?: $supplier;
                    $debit = $items->sum('famountdb');
                    $credit = $items->sum('famountcr');
                    $saldo = (float) ($items->last()->plasaldosubacc ?? 0);
                    $accountDebit += $debit;
                    $accountCredit += $credit;
                    $accountSaldo += $saldo;
                @endphp

                <div class="journal-block customer-heading">
                    {{ $supplier }} - {{ $supplierName }}
                </div>

                @foreach ($items as $row)
                    <div class="journal-block">
                        <div class="ledger-row">
                            <div>{{ $row->fjurnaltgl ? \Carbon\Carbon::parse($row->fjurnaltgl)->format('d/m/Y') : '' }}</div>
                            <div class="truncate" title="{{ $row->fjurnalno }}">{{ $row->fjurnalno }}</div>
                            <div class="truncate" title="{{ $row->faccountno }}">{{ $row->faccountno }}</div>
                            <div class="right">{{ number_format((float) $row->famountdb, 2, ',', '.') }}</div>
                            <div class="right">{{ number_format((float) $row->famountcr, 2, ',', '.') }}</div>
                            <div class="right">{{ number_format((float) $row->plasaldosubacc, 2, ',', '.') }}</div>
                        </div>
                    </div>
                @endforeach

                <div class="journal-block">
                    <div class="ledger-total">
                        <div style="grid-column: span 3;" class="right">Saldo Akhir {{ $supplierName }}</div>
                        <div class="right">{{ number_format((float) $debit, 2, ',', '.') }}</div>
                        <div class="right">{{ number_format((float) $credit, 2, ',', '.') }}</div>
                        <div class="right">{{ number_format((float) $saldo, 2, ',', '.') }}</div>
                    </div>
                </div>
            @endforeach

            <div class="journal-block">
                <div class="ledger-total account-total">
                    <div style="grid-column: span 3;" class="right">Saldo Akhir {{ $accountName }}</div>
                    <div class="right">{{ number_format((float) $accountDebit, 2, ',', '.') }}</div>
                    <div class="right">{{ number_format((float) $accountCredit, 2, ',', '.') }}</div>
                    <div class="right">{{ number_format((float) $accountSaldo, 2, ',', '.') }}</div>
                </div>
            </div>
            <div class="journal-block separator"></div>
        @empty
            <div class="journal-block" style="text-align: center; padding: 20px; font-size: 11px; color: #666;">Tidak ada data ditemukan.</div>
        @endforelse
    </div>

    {{-- Hidden Totals Panel Container --}}
    <div id="po-totals-panel-raw" style="display: none;">
        <div class="po-totals-panel-wrapper">
            <div class="end-of-report-inline">** END OF REPORT **</div>
        </div>
    </div>

    {{-- Screen Render Target --}}
    <div class="report-wrapper" id="reportWrapper">
        @if ($rows->isEmpty())
            <div class="page-a4 page-a4-strict">
                <div class="header-section">
                    <div class="supplier-info-kiri" style="top: 15px;">
                        Supplier: {{ $supplierText }}
                        <br>Cabang: {{ $branchText }}
                    </div>
                    <h2>Buku Hutang</h2>
                    <div class="info-tambahan">
                        <div><span class="info-label">Hal</span>: 1 / 1</div>
                        <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                        <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                        <div><span class="info-label">Opr</span>: {{ auth()->user()->fname ?? 'User' }}</div>
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
        const poHeaderLabelsHtml = rawSource.querySelector(".ledger-labels").outerHTML;

        function createNewPage() {
            const page = document.createElement("div");
            page.className = "page-a4";
            page.innerHTML = `
                <div class="page-header-container">
                    ${headerSectionHtml}
                    ${poHeaderLabelsHtml}
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
            const journalClone = journal.cloneNode(true);
            currentContent.appendChild(journalClone);

            // Check overflow
            if (currentPage.offsetHeight > maxPageHeight) {
                const blockCount = currentContent.querySelectorAll(".journal-block").length;
                if (blockCount > 1) {
                    currentContent.removeChild(journalClone);

                    // Create new page
                    currentPage = createNewPage();
                    currentContent = currentPage.querySelector(".page-content");

                    // Append the block to the new page
                    currentContent.appendChild(journalClone);
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
