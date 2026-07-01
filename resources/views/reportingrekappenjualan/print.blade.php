<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #000;
            background-color: #eee;
            counter-reset: page;
        }

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
            color: #c00;
        }

        .filter-info {
            font-size: 10px;
            color: #333;
            margin-bottom: 5px;
        }

        .supplier-info-kiri {
            position: absolute;
            top: 15mm;
            left: 0mm;
            font-size: 10px;
            color: #333;
            text-align: left;
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
            display: inline-block;
            width: 45px;
        }

        .po-header-labels,
        .item-row,
        .group-total-row {
            display: grid;
            grid-template-columns: 12mm 35mm 1fr 25mm 35mm;
            border-bottom: 1px solid #000;
            gap: 1px;
            font-size: 8px;
            padding: 4px 3px;
        }

        .po-header-labels {
            background-color: #f0f0f0;
            border: 1px solid #000;
            margin-bottom: 1px;
            font-weight: bold;
        }

        .item-row {
            background-color: #fff;
            padding: 3px 3px;
            border-bottom: 1px solid #eee;
        }

        .item-row > div:nth-child(4),
        .item-row > div:nth-child(5),
        .po-header-labels > div:nth-child(4),
        .po-header-labels > div:nth-child(5),
        .group-total-row > div:nth-child(2) {
            text-align: right;
        }

        .group-row {
            display: block;
            background-color: #ffe6e6;
            color: #c00;
            font-weight: bold;
            font-size: 8px;
            padding: 4px 6px;
            border: 1px solid #ccc;
            margin-bottom: 1px;
        }

        .group-total-row {
            background-color: #f9fafb;
            font-weight: bold;
            border-top: 1px solid #ccc;
            border-bottom: 1px solid #919191;
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
        }

        .no-print button {
            transition: background-color 0.2s;
        }

        .journal-block {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .grand-total-section {
            margin-top: 20px;
            padding-top: 10px;
            display: flex;
            justify-content: flex-end;
        }

        .grand-total-panel {
            width: 70mm;
            border: 1px solid #919191;
            font-size: 10px;
        }

        .grand-total-row {
            display: grid;
            grid-template-columns: 30mm 40mm;
            border-bottom: 1px solid #ccc;
            font-weight: bold;
        }

        .grand-total-row:last-child {
            border-bottom: none;
            color: black;
        }

        .grand-total-row div {
            padding: 6px 8px;
        }

        .grand-total-row div:last-child {
            text-align: right;
        }

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
        <button class="print-button" onclick="window.print()">Cetak Laporan</button>
        <button onclick="adjustZoom(-0.1)"
            style="padding: 6px 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold;">-</button>
        <span id="zoomLabel"
            style="min-width: 48px; text-align: center; font-size: 13px; font-weight: bold; color: #333; align-self: center;">100%</span>
        <button onclick="adjustZoom(0.1)"
            style="padding: 6px 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold;">+</button>
    </div>

    @php
        $grandTotal = 0;
        $branchText = request()->has('branch_codes') ? implode(', ', (array) request()->input('branch_codes')) : 'Semua';
    @endphp

    <div id="raw-source" style="display: none;">
        <div class="header-section">
            <div class="supplier-info-kiri">
                Cabang: {{ $branchText }}
            </div>
            <h2>{{ $title }}</h2>
            <div class="filter-info">
                Periode:
                {{ request('date_from') ? \Carbon\Carbon::parse(request('date_from'))->format('d/m/Y') : '...' }}
                s/d
                {{ request('date_to') ? \Carbon\Carbon::parse(request('date_to'))->format('d/m/Y') : '...' }}
                | {{ $groupBy === 'group' ? 'By Group Produk' : 'By Merek' }}
            </div>
            <div class="info-tambahan">
                <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                <div><span class="info-label">Opr</span>: {{ $user_session->fname ?? 'admin' }}</div>
            </div>
        </div>

        <div class="po-header-labels">
            <div>No.</div>
            <div>Kode Barang</div>
            <div>Nama Barang</div>
            <div>Quantity</div>
            <div>Total Penjualan</div>
        </div>

        @forelse ($rows->groupBy('fmerek') as $groupCode => $items)
            @php
                $groupName = $items->first()->fgroupname ?: $groupCode;
                $groupTotal = $items->sum('famount');
                $grandTotal += $groupTotal;
            @endphp
            
            {{-- Group Header block --}}
            <div class="journal-block group-row">
                {{ $groupCode }} - {{ $groupName }}
            </div>

            @foreach ($items as $index => $row)
                <div class="journal-block">
                    <div class="item-row">
                        <div class="text-center">{{ $index + 1 }}</div>
                        <div class="font-mono">{{ $row->fprdcode }}</div>
                        <div class="truncate" title="{{ $row->fprdname }}">{{ $row->fprdname }}</div>
                        <div>{{ number_format((float) $row->fqty, 2, ',', '.') }}</div>
                        <div>{{ number_format((float) $row->famount, 2, ',', '.') }}</div>
                    </div>
                </div>
            @endforeach

            {{-- Group Total block --}}
            <div class="journal-block">
                <div class="group-total-row">
                    <div style="grid-column: span 4; text-align: right; padding-right: 8px;">Total({{ $groupCode }})</div>
                    <div>{{ number_format((float) $groupTotal, 2, ',', '.') }}</div>
                </div>
            </div>
        @empty
            <div class="journal-block" style="text-align: center; padding: 20px; font-size: 11px; color: #666;">
                Tidak ada data ditemukan.
            </div>
        @endforelse

        <div class="grand-total-section">
            <div class="grand-total-panel">
                <div class="grand-total-row">
                    <div>Grand Total:</div>
                    <div>{{ number_format((float) $grandTotal, 2, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="report-wrapper" id="reportWrapper">
        @if ($rows->isEmpty())
            <div class="page-a4 page-a4-strict">
                <div class="header-section">
                    <div class="supplier-info-kiri" style="top: 15mm;">
                        Cabang: {{ $branchText }}
                    </div>
                    <h2>{{ $title }}</h2>
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

        const tempDiv = document.createElement("div");
        tempDiv.style.height = "297mm";
        tempDiv.style.position = "absolute";
        tempDiv.style.visibility = "hidden";
        document.body.appendChild(tempDiv);
        const pageHeightPx = tempDiv.offsetHeight;
        document.body.removeChild(tempDiv);

        const maxPageHeight = pageHeightPx - 20;
        const headerSectionHtml = rawSource.querySelector(".header-section").outerHTML;
        const poHeaderLabelsHtml = rawSource.querySelector(".po-header-labels").outerHTML;
        const grandTotalSectionHtml = rawSource.querySelector(".grand-total-section")?.outerHTML;

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

        if (grandTotalSectionHtml) {
            const tempTotal = document.createElement("div");
            tempTotal.innerHTML = grandTotalSectionHtml;
            const grandTotalEl = tempTotal.firstElementChild;
            currentPage.appendChild(grandTotalEl);

            if (currentPage.offsetHeight > maxPageHeight && currentContent.children.length > 0) {
                currentPage.removeChild(grandTotalEl);
                currentPage = createNewPage();
                currentPage.appendChild(grandTotalEl);
            }
        }

        const endOfReportEl = document.createElement("div");
        endOfReportEl.className = "end-of-report";
        endOfReportEl.style.textAlign = "center";
        endOfReportEl.style.marginTop = "10px";
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
