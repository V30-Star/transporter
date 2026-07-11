<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;700&family=IBM+Plex+Sans:wght@400;500;700&family=Source+Serif+4:opsz,wght@8..60,700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Monitor Screen Layout */
        body {
            font-family: 'IBM Plex Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 10px;
            color: #0f172a;
            /* Navy-Ink */
            background-color: #f1f5f9;
            /* Modern light slate background on monitor */
            counter-reset: page;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Screen Simulation Styles for A4 Pages */
        .page-a4 {
            width: 297mm;
            margin: 30px auto;
            background: white;
            padding: 15mm 20mm 15mm 20mm;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
            position: relative;
            box-sizing: border-box;
            height: auto;
            min-height: 0;
            border-radius: 4px;
        }

        /* Strict height applied after pagination */
        .page-a4-strict {
            height: 210mm !important;
            min-height: 210mm !important;
            overflow: hidden !important;
        }

        .header-section {
            position: relative;
            margin-bottom: 1px;
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
            /* Dark Red matching Listing PR */
            letter-spacing: 0.5px;
        }

        .filter-info {
            font-size: 10px;
            color: #475569;
            /* Slate 600 */
            margin-bottom: 5px;
            font-weight: 500;
        }

        .supplier-info-kiri {
            position: absolute;
            top: 15px;
            left: 0mm;
            font-size: 10px;
            color: #334155;
            /* Slate 700 */
            text-align: left;
            line-height: 1.5;
        }

        .info-tambahan {
            position: absolute;
            top: 0;
            right: 0;
            font-size: 10px;
            color: #334155;
            /* Slate 700 */
            text-align: left;
            line-height: 1.5;
        }

        .info-label {
            font-weight: 600;
            display: inline-block;
            width: 50px;
            color: #475569;
            /* Slate 600 */
        }

        /* --- TABLE HEADERS & ROWS (13 Kolom) --- */
        .po-header-labels,
        .row-data,
        .group-total-row,
        .grand-total-row {
            display: grid;
            grid-template-columns: 8mm 12mm 1fr 20mm 20mm 15mm 22mm 17mm 17mm 16mm 16mm 16mm 16mm;
            gap: 1px;
            font-size: 7px;
            padding: 2px 8px;
            align-items: center;
        }

        .po-header-labels {
            background-color: transparent;
            color: #000000;
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
            margin-bottom: 0px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .row-data {
            background-color: transparent;
            margin-bottom: 0px;
            color: #0f172a;
            /* border-bottom: 1px solid #edf2f7; */
            padding: 3px 8px;
        }

        .row-data>div:nth-child(1) {
            text-align: center;
        }

        .row-data>div:nth-child(6),
        .row-data>div:nth-child(7),
        .row-data>div:nth-child(8),
        .row-data>div:nth-child(9),
        .row-data>div:nth-child(10),
        .row-data>div:nth-child(11),
        .row-data>div:nth-child(12),
        .row-data>div:nth-child(13),
        .po-header-labels>div:nth-child(6),
        .po-header-labels>div:nth-child(7),
        .po-header-labels>div:nth-child(8),
        .po-header-labels>div:nth-child(9),
        .po-header-labels>div:nth-child(10),
        .po-header-labels>div:nth-child(11),
        .po-header-labels>div:nth-child(12),
        .po-header-labels>div:nth-child(13),
        .group-total-row>div:not(:first-child),
        .grand-total-row>div:not(:first-child) {
            text-align: right;
        }

        /* Fonts for Numbers & System Codes */
        .row-data>div:nth-child(3),
        .row-data>div:nth-child(6),
        .row-data>div:nth-child(7),
        .row-data>div:nth-child(8),
        .row-data>div:nth-child(9),
        .row-data>div:nth-child(10),
        .row-data>div:nth-child(11),
        .row-data>div:nth-child(12),
        .row-data>div:nth-child(13),
        .group-total-row>div:not(:first-child),
        .grand-total-row>div:not(:first-child) {
            font-family: 'IBM Plex Mono', Courier, monospace;
            font-variant-numeric: tabular-nums;
        }

        .group-row {
            display: block;
            font-weight: bold;
            font-size: 8px;
            padding: 2px 8px;
            /* border: 1px solid #ccc; */
            margin-bottom: 1px;
        }

        .group-total-row {
            background-color: transparent;
            font-weight: bold;
            /* border-top: 1px solid #000000; */
            /* border-bottom: 1px solid #000000; */
            color: #0f172a;
        }

        .separator {
            margin: 0px;
            clear: both;
        }

        .truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Floating zoom bar style from Listing PO */
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
            /* Navy-Ink background */
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
            /* Black background on hover */
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(15, 23, 42, 0.3);
        }

        .journal-block {
            page-break-inside: avoid;
            break-inside: avoid;
            margin-bottom: 3px;
        }

        .po-totals-panel-wrapper {
            margin-top: 5px;
            width: 257mm;
            padding-top: 0px;
            position: relative;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .end-of-report-inline {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: -15px;
            font-family: 'IBM Plex Sans', sans-serif;
            font-size: 8px;
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .grand-total-row {
            background-color: transparent;
            font-weight: bold;
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
            color: #304ee7;
        }

        /* Print Media CSS Overrides */
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
                padding: 15mm 20mm 15mm 20mm !important;
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

        <a href="{{ route('analisaumurpiutang.excel', request()->query()) }}"
            style="padding: 7px 12px; background-color: #22c55e; color: white; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; transition: background-color 0.2s;"
            onmouseover="this.style.backgroundColor='#16a34a'" onmouseout="this.style.backgroundColor='#22c55e'">
            📊 Excel
        </a>
    </div>

    @php
        $grand = ['undue' => 0, 'd30' => 0, 'd60' => 0, 'd90' => 0, 'd91' => 0, 'd1y' => 0];
        $branchText = request()->has('branch_codes')
            ? implode(', ', (array) request()->input('branch_codes'))
            : 'Semua';
        $customerText =
            trim((string) request('cust_from')) !== '' || trim((string) request('cust_to')) !== ''
                ? (request('cust_from') ?: 'Awal') . ' s/d ' . (request('cust_to') ?: 'Akhir')
                : 'Semua';
    @endphp

    <div id="raw-source" style="display: none;">
        <div class="header-section">
            <div class="supplier-info-kiri">
                Customer: {{ $customerText }}
                <br>Cabang: {{ $branchText }}
            </div>
            <h2>{{ $title }}</h2>
            <div class="filter-info">
                Periode:
                {{ request('date_from') ? \Carbon\Carbon::parse(request('date_from'))->format('d/m/Y') : '...' }}
                s/d
                {{ request('date_to') ? \Carbon\Carbon::parse(request('date_to'))->format('d/m/Y') : '...' }}
                @if ($request->input('mode') === 'due')
                    | Jatuh Tempo s.d {{ \Carbon\Carbon::parse($request->input('due_date_to'))->format('d/m/Y') }}
                @endif
            </div>
            <div class="info-tambahan">
                <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                <div><span class="info-label">Opr</span>: {{ $user_session->fname ?? 'admin' }}</div>
            </div>
        </div>

        <div class="po-header-labels">
            <div>No.</div>
            <div>Cab.</div>
            <div>No.Faktur</div>
            <div>Tanggal</div>
            <div>Jatuh Tempo</div>
            <div>Umur</div>
            <div>Nilai Faktur</div>
            <div>Un Due</div>
            <div>0-30 Hr</div>
            <div>31-60 Hr</div>
            <div>61-90 Hr</div>
            <div>91-1 Th</div>
            <div>&gt;1 Th</div>
        </div>

        @forelse ($rows->groupBy('fcustno') as $custCode => $items)
            @php
                $name = $items->first()->fcustname ?: $custCode;
                $tot = [
                    'undue' => $items->sum('varundue'),
                    'd30' => $items->sum('var30hari'),
                    'd60' => $items->sum('var60hari'),
                    'd90' => $items->sum('ppvar90hari'),
                    'd91' => $items->sum('ppvar91hari'),
                    'd1y' => $items->sum('ppvar1tahun'),
                ];
                foreach ($tot as $key => $value) {
                    $grand[$key] += $value;
                }
            @endphp

            {{-- Group Header block --}}
            <div class="journal-block group-row">
                {{ $custCode }} - {{ $name }}
            </div>

            @foreach ($items as $index => $row)
                <div class="journal-block">
                    <div class="row-data">
                        <div>{{ $index + 1 }}</div>
                        <div class="truncate">{{ $row->fbranchcode }}</div>
                        <div class="truncate" title="{{ $row->fsono }}">{{ $row->fsono }}</div>
                        <div>{{ $row->fsodate ? \Carbon\Carbon::parse($row->fsodate)->format('d/m/Y') : '' }}</div>
                        <div>{{ $row->fjatuhtempo ? \Carbon\Carbon::parse($row->fjatuhtempo)->format('d/m/Y') : '' }}
                        </div>
                        <div>{{ $row->mu }}</div>
                        <div>{{ number_format((float) $row->famountso, 2, ',', '.') }}</div>
                        <div>{{ number_format((float) $row->varundue, 2, ',', '.') }}</div>
                        <div>{{ number_format((float) $row->var30hari, 2, ',', '.') }}</div>
                        <div>{{ number_format((float) $row->var60hari, 2, ',', '.') }}</div>
                        <div>{{ number_format((float) $row->ppvar90hari, 2, ',', '.') }}</div>
                        <div>{{ number_format((float) $row->ppvar91hari, 2, ',', '.') }}</div>
                        <div>{{ number_format((float) $row->ppvar1tahun, 2, ',', '.') }}</div>
                    </div>
                </div>
            @endforeach

            {{-- Group Total block --}}
            <div class="journal-block">
                <div class="group-total-row">
                    <div style="grid-column: span 7; text-align: right; padding-right: 8px;">Total {{ $name }}
                    </div>
                    <div>{{ number_format((float) $tot['undue'], 2, ',', '.') }}</div>
                    <div>{{ number_format((float) $tot['d30'], 2, ',', '.') }}</div>
                    <div>{{ number_format((float) $tot['d60'], 2, ',', '.') }}</div>
                    <div>{{ number_format((float) $tot['d90'], 2, ',', '.') }}</div>
                    <div>{{ number_format((float) $tot['d91'], 2, ',', '.') }}</div>
                    <div>{{ number_format((float) $tot['d1y'], 2, ',', '.') }}</div>
                </div>
            </div>
        @empty
            <div class="journal-block" style="text-align: center; padding: 20px; font-size: 11px; color: #666;">
                Tidak ada data ditemukan.
            </div>
        @endforelse
    </div>

    {{-- Hidden Totals Panel Container --}}
    <div id="po-totals-panel-raw" style="display: none;">
        <div class="po-totals-panel-wrapper">
            <div class="end-of-report-inline">** END OF REPORT **</div>
            <div class="grand-total-row">
                <div style="grid-column: span 7; text-align: right; padding-right: 8px;">GRAND TOTAL</div>
                <div>{{ number_format((float) $grand['undue'], 2, ',', '.') }}</div>
                <div>{{ number_format((float) $grand['d30'], 2, ',', '.') }}</div>
                <div>{{ number_format((float) $grand['d60'], 2, ',', '.') }}</div>
                <div>{{ number_format((float) $grand['d90'], 2, ',', '.') }}</div>
                <div>{{ number_format((float) $grand['d91'], 2, ',', '.') }}</div>
                <div>{{ number_format((float) $grand['d1y'], 2, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="report-wrapper" id="reportWrapper">
        @if ($rows->isEmpty())
            <div class="page-a4 page-a4-strict">
                <div class="header-section">
                    <div class="supplier-info-kiri" style="top: 15px;">
                        Customer: {{ $customerText }}
                        <br>Cabang: {{ $branchText }}
                    </div>
                    <h2>{{ $title }}</h2>
                    <div class="info-tambahan">
                        <div><span class="info-label">Hal</span>: 1 / 1</div>
                        <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                        <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                        <div><span class="info-label">Opr</span>: {{ $user_session->fname ?? 'admin' }}</div>
                    </div>
                    <div style="margin-top: 30px; text-align: center; font-size: 12px; color: #666;">Tidak ada data
                        ditemukan.</div>
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

        // Measure actual 297mm page height on the screen dynamically in pixels
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
        const poHeaderLabelsHtml = rawSource.querySelector(".po-header-labels").outerHTML;

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
            const journalClone = journal.cloneNode(true);
            currentContent.appendChild(journalClone);

            if (currentPage.offsetHeight > maxPageHeight) {
                const blockCount = currentContent.querySelectorAll(".journal-block").length;
                if (blockCount > 1) {
                    currentContent.removeChild(journalClone);
                    currentPage = createNewPage();
                    currentContent = currentPage.querySelector(".page-content");
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
