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
            color: #0f172a; /* Navy-Ink */
            background-color: #f1f5f9; /* Modern light slate background on monitor */
            counter-reset: page;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Screen Simulation Styles for A4 Pages (LANDSCAPE) */
        .page-a4 {
            width: 297mm;
            margin: 30px auto;
            background: white;
            padding: 15mm 20mm 15mm 20mm; /* Page margins */
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
            position: relative;
            box-sizing: border-box;
            height: auto;
            min-height: 0;
            border-radius: 4px;
        }

        /* Strict height applied after pagination (LANDSCAPE height is 210mm) */
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
            color: #cc0000; /* Dark Red matching Listing PR */
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
            top: 15px;
            left: 0mm;
            font-size: 10px;
            color: #334155; /* Slate 700 */
            text-align: left;
            line-height: 1.5;
            max-width: 80mm;
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

        /* --- TABLE HEADERS & ROWS (REKAP: 10 Kolom) --- */
        .rekap-header-labels,
        .rekap-row {
            display: grid;
            grid-template-columns: 8mm 24mm 1fr 15mm 20mm 24mm 22mm 22mm 24mm 18mm;
            gap: 1px;
            font-size: 8px;
            padding: 2px 8px;
            align-items: center;
        }

        /* --- TABLE HEADERS & ROWS (DETAIL: 10 Kolom) --- */
        .detail-header-labels,
        .detail-row {
            display: grid;
            grid-template-columns: 35mm 18mm 18mm 25mm 1fr 20mm 22mm 22mm 22mm 24mm;
            gap: 1px;
            font-size: 8px;
            padding: 2px 8px;
            align-items: center;
        }

        .rekap-header-labels,
        .detail-header-labels {
            background-color: transparent;
            color: #000000;
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
            margin-bottom: 0px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .rekap-row,
        .detail-row {
            background-color: transparent;
            margin-bottom: 0px;
            color: #0f172a;
            padding: 2px 8px;
        }

        /* Alignment for Rekap */
        .rekap-header-labels > div:nth-child(4),
        .rekap-header-labels > div:nth-child(6),
        .rekap-header-labels > div:nth-child(7),
        .rekap-header-labels > div:nth-child(8),
        .rekap-header-labels > div:nth-child(9),
        .rekap-row > div:nth-child(4),
        .rekap-row > div:nth-child(6),
        .rekap-row > div:nth-child(7),
        .rekap-row > div:nth-child(8),
        .rekap-row > div:nth-child(9) {
            text-align: right;
        }

        .rekap-header-labels > div:nth-child(1),
        .rekap-row > div:nth-child(1) {
            text-align: center;
        }

        /* Alignment for Detail */
        .detail-header-labels > div:nth-child(7),
        .detail-header-labels > div:nth-child(8),
        .detail-header-labels > div:nth-child(9),
        .detail-header-labels > div:nth-child(10),
        .detail-row > div:nth-child(7),
        .detail-row > div:nth-child(8),
        .detail-row > div:nth-child(9),
        .detail-row > div:nth-child(10) {
            text-align: right;
        }

        /* Fonts for Numbers & System Codes */
        .rekap-row > div:nth-child(2),
        .rekap-row > div:nth-child(4),
        .rekap-row > div:nth-child(6),
        .rekap-row > div:nth-child(7),
        .rekap-row > div:nth-child(8),
        .rekap-row > div:nth-child(9),
        .rekap-row > div:nth-child(10) {
            font-family: 'IBM Plex Mono', Courier, monospace;
            font-variant-numeric: tabular-nums;
        }

        .detail-row > div:nth-child(1),
        .detail-row > div:nth-child(2),
        .detail-row > div:nth-child(3),
        .detail-row > div:nth-child(4),
        .detail-row > div:nth-child(7),
        .detail-row > div:nth-child(8),
        .detail-row > div:nth-child(9),
        .detail-row > div:nth-child(10) {
            font-family: 'IBM Plex Mono', Courier, monospace;
            font-variant-numeric: tabular-nums;
        }

        .wh-title {
            font-weight: bold;
            background-color: #f3f4f6;
            margin-top: 6px;
            padding: 2px 8px;
            font-size: 8px;
            text-transform: uppercase;
        }

        .group-title {
            font-weight: bold;
            background-color: #ffe6e6;
            margin-top: 3px;
            padding: 2px 8px;
            font-size: 8px;
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
            margin-bottom: 2px;
        }

        /* Totals Panel style */
        .po-totals-panel-wrapper {
            margin-top: 15px;
            width: 257mm; /* Full printable width for landscape */
            border-top: 1px solid #000000; /* Long line above totals */
            padding-top: 15px;
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
    @php
        $branchText = request()->has('branch_codes') ? implode(', ', (array) request('branch_codes')) : 'Semua';
        $period = (request('date_from') ?: date('Y-01-01')) . ' s/d ' . (request('date_to') ?: date('Y-12-31'));
        $groupBy = request('grouping', 'group') === 'merek' ? 'Merek' : 'Group Produk';
        $pagePerWh = request()->boolean('page_per_warehouse');
    @endphp

    <div class="no-print">
        <button class="print-button" onclick="window.print()">🖨️ Cetak Laporan</button>
        <button onclick="adjustZoom(-0.1)"
            style="padding: 6px 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold;">-</button>
        <span id="zoomLabel"
            style="min-width: 48px; text-align: center; font-size: 13px; font-weight: bold; color: #333; align-self: center;">100%</span>
        <button onclick="adjustZoom(0.1)"
            style="padding: 6px 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold;">+</button>
    </div>

    {{-- Hidden Raw Data Container --}}
    <div id="raw-source" style="display: none;">
        <div class="header-section">
            <div class="supplier-info-kiri">
                Cabang: {{ $branchText }}
                <br>Periode: {{ $period }}
                <br>Mode: {{ strtoupper($mode) }}
            </div>
            <h2>{{ $title }}</h2>
            <div class="filter-info">
                Grouping: {{ $groupBy }}
            </div>
            <div class="info-tambahan">
                <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                <div><span class="info-label">Opr</span>: {{ $user_session->fname ?? 'admin' }}</div>
            </div>
        </div>

        @if ($mode === 'rekap')
            <div class="rekap-header-labels">
                <div>No.</div>
                <div>Kode Prd</div>
                <div>Nama Produk</div>
                <div>Isi</div>
                <div>Satuan</div>
                <div>Saldo Awal</div>
                <div>Masuk</div>
                <div>Keluar</div>
                <div>Saldo Akhir</div>
                <div>Gudang</div>
            </div>
        @else
            <div class="detail-header-labels">
                <div>Transaksi</div>
                <div>Kode Trans</div>
                <div>Tanggal</div>
                <div>No.Ref</div>
                <div>Supplier/Customer</div>
                <div>Satuan</div>
                <div>Saldo Awal</div>
                <div>Masuk</div>
                <div>Keluar</div>
                <div>Saldo Akhir</div>
            </div>
        @endif

        @forelse ($rows->groupBy('fwhcode') as $whcode => $whRows)
            {{-- Warehouse header --}}
            <div class="journal-block wh-title">
                Gudang: {{ $whcode }}
            </div>

            @if ($mode === 'rekap')
                @foreach ($whRows->groupBy(request('grouping', 'group') === 'merek' ? 'fmerekname' : 'fgroupname') as $groupName => $items)
                    {{-- Group title header --}}
                    <div class="journal-block group-title">
                        {{ $groupName ?: '-' }}
                    </div>

                    @foreach ($items as $row)
                        <div class="journal-block">
                            <div class="rekap-row">
                                <div>{{ $loop->iteration }}</div>
                                <div class="truncate" title="{{ $row->fprdcode }}">{{ $row->fprdcode }}</div>
                                <div class="truncate" title="{{ $row->fprdname }}">{{ $row->fprdname }}</div>
                                <div>{{ number_format((float) $row->qtykecil, 2, ',', '.') }}</div>
                                <div>{{ $row->fsatuan }}</div>
                                <div>{{ number_format((float) $row->qtyawalkecil, 2, ',', '.') }}</div>
                                <div>{{ number_format((float) $row->qtymasukkecil, 2, ',', '.') }}</div>
                                <div>{{ number_format((float) $row->qtykeluarkecil, 2, ',', '.') }}</div>
                                <div>{{ number_format((float) $row->qtysaldokecil, 2, ',', '.') }}</div>
                                <div>{{ $row->fwhcode }}</div>
                            </div>
                        </div>
                    @endforeach
                @endforeach
            @else
                @foreach ($whRows->groupBy('fprdcode') as $prdcode => $items)
                    {{-- Product group title header --}}
                    <div class="journal-block group-title">
                        {{ $prdcode }} - {{ $items->first()->fprdname ?? '' }}
                    </div>

                    @foreach ($items as $row)
                        <div class="journal-block">
                            <div class="detail-row">
                                <div class="truncate" title="{{ $row->fstockmt }}">{{ $row->fstockmt }}</div>
                                <div>{{ $row->fstockmtcode }}</div>
                                <div>{{ $row->fstockdate ? \Carbon\Carbon::parse($row->fstockdate)->format('d/m/Y') : '' }}</div>
                                <div class="truncate" title="{{ $row->frefno }}">{{ $row->frefno }}</div>
                                <div class="truncate" title="{{ $row->fsuppliername }}">{{ $row->fsuppliername }}</div>
                                <div>{{ $row->fsatuan ?? '' }}</div>
                                <div>{{ number_format((float) $row->qtyawalkecil, 2, ',', '.') }}</div>
                                <div>{{ number_format((float) $row->qtymasukkecil, 2, ',', '.') }}</div>
                                <div>{{ number_format((float) $row->qtykeluarkecil, 2, ',', '.') }}</div>
                                <div>{{ number_format((float) $row->qtysaldokecil, 2, ',', '.') }}</div>
                            </div>
                        </div>
                    @endforeach
                @endforeach
            @endif
        @empty
            <div class="journal-block" style="padding:20px;text-align:center;color:#666; font-size:11px;">
                Tidak ada data ditemukan.
            </div>
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
                        Cabang: {{ $branchText }}
                        <br>Periode: {{ $period }}
                        <br>Mode: {{ strtoupper($mode) }}
                    </div>
                    <h2>{{ $title }}</h2>
                    <div class="filter-info">
                        Grouping: {{ $groupBy }}
                    </div>
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

        // Measure actual 210mm page height on the screen dynamically in pixels (A4 Landscape)
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
        const rekapHeaderHtml = rawSource.querySelector(".rekap-header-labels")?.outerHTML || '';
        const detailHeaderHtml = rawSource.querySelector(".detail-header-labels")?.outerHTML || '';
        const poHeaderLabelsHtml = rekapHeaderHtml || detailHeaderHtml;

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
