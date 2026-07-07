<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listing Piutang Penjualan</title>
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
            grid-template-columns: 10mm 15mm 1fr 20mm 22mm 28mm 28mm 22mm;
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

        .item-row > div:nth-child(6),
        .item-row > div:nth-child(7),
        .po-header-labels > div:nth-child(6),
        .po-header-labels > div:nth-child(7),
        .group-total-row > div:nth-child(2),
        .group-total-row > div:nth-child(3) {
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
            border-bottom: 1px solid #000;
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
        }

        .grand-total-section {
            margin-top: 20px;
            border-top: 1px solid #919191;
            padding-top: 10px;
            display: flex;
            justify-content: flex-end;
        }

        .grand-total-panel {
            width: 85mm;
            border: 1px solid #919191;
            font-size: 10px;
        }

        .grand-total-row {
            display: grid;
            grid-template-columns: 25mm 30mm 30mm;
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

        .grand-total-row div:not(:first-child) {
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

        <a href="{{ route('listingpiutangpenjualan.excel', request()->query()) }}"
            style="padding: 7px 12px; background-color: #22c55e; color: white; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; transition: background-color 0.2s;"
            onmouseover="this.style.backgroundColor='#16a34a'"
            onmouseout="this.style.backgroundColor='#22c55e'">
            📊 Excel
        </a>
    </div>

    @php
        $grandFaktur = 0;
        $grandPiutang = 0;
        $branchText = request()->has('branch_codes') ? implode(', ', (array) request()->input('branch_codes')) : 'Semua';
    @endphp

    <div id="raw-source" style="display: none;">
        <div class="header-section">
            <div class="supplier-info-kiri">
                Cabang: {{ $branchText }}<br>
                Mode: {{ $mode === 'rekap' ? 'Rekap' : 'Detail' }}
            </div>
            <h2>Listing Piutang Penjualan</h2>
            <div class="filter-info">
                Per Tanggal: {{ request('per_tanggal') ?: date('Y-m-d') }}
                @if (request()->boolean('tgl_pembayaran'))
                    | Tgl. Pembayaran: {{ request('tgl_pembayaran_date') ?: request('per_tanggal') }}
                @endif
                @if (request('due_filter') === 'due')
                    | Jatuh Tempo s/d: {{ request('due_date') ?: request('per_tanggal') }}
                @endif
            </div>
            <div class="info-tambahan">
                <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                <div><span class="info-label">Opr</span>: {{ $user_session->fname ?? 'User' }}</div>
            </div>
        </div>

        <div class="po-header-labels">
            <div>No.</div>
            <div>Cab.</div>
            <div>No.Faktur</div>
            <div>Tanggal</div>
            <div>Jatuh Tempo</div>
            <div>Nilai Faktur</div>
            <div>Nilai Piutang</div>
            <div>Salesman</div>
        </div>

        @forelse ($rows->groupBy('fcustomer') as $customer => $items)
            @php
                $first = $items->first();
                $customerFaktur = $items->sum('famountso');
                $customerPiutang = $items->sum('fsisapiu');
                $grandFaktur += $customerFaktur;
                $grandPiutang += $customerPiutang;
            @endphp
            
            {{-- Group Header block --}}
            <div class="journal-block group-row">
                {{ $customer }} - {{ $first->fcustname }}
            </div>

            @if ($mode === 'detail')
                @foreach ($items as $index => $row)
                    <div class="journal-block">
                        <div class="item-row">
                            <div class="text-center">{{ $index + 1 }}</div>
                            <div>{{ $row->fbranchcode }}</div>
                            <div class="truncate" title="{{ $row->fsono }}">{{ $row->fsono }}</div>
                            <div>{{ $row->fsodate ? \Carbon\Carbon::parse($row->fsodate)->format('d/m/Y') : '' }}</div>
                            <div>{{ $row->fjatuhtempo ? \Carbon\Carbon::parse($row->fjatuhtempo)->format('d/m/Y') : '' }}</div>
                            <div>{{ number_format((float) $row->famountso, 2, ',', '.') }}</div>
                            <div>{{ number_format((float) $row->fsisapiu, 2, ',', '.') }}</div>
                            <div class="truncate" title="{{ $row->fsalesman }}">{{ $row->fsalesman }}</div>
                        </div>
                    </div>
                @endforeach
            @endif

            {{-- Group Total block --}}
            <div class="journal-block">
                <div class="group-total-row">
                    <div style="grid-column: span 5; text-align: right; padding-right: 8px;">Total({{ $first->fcustname }})</div>
                    <div>{{ number_format((float) $customerFaktur, 2, ',', '.') }}</div>
                    <div>{{ number_format((float) $customerPiutang, 2, ',', '.') }}</div>
                    <div></div>
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
                    <div>{{ number_format((float) $grandFaktur, 2, ',', '.') }}</div>
                    <div>{{ number_format((float) $grandPiutang, 2, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="report-wrapper" id="reportWrapper">
        @if ($rows->isEmpty())
            <div class="page-a4 page-a4-strict">
                <div class="header-section">
                    <div class="supplier-info-kiri" style="top: 15mm;">
                        Cabang: {{ $branchText }}<br>
                        Mode: {{ $mode === 'rekap' ? 'Rekap' : 'Detail' }}
                    </div>
                    <h2>Listing Piutang Penjualan</h2>
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
