<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listing Faktur Pajak Penjualan</title>
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
            top: 1mm;
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
            font-weight: bold;
            display: inline-block;
            width: 45px;
        }

        .po-header-labels,
        .po-header {
            display: grid;
            grid-template-columns: 18mm 18mm 16mm 25mm 19mm 18mm 19mm 18mm 20mm;
            border-bottom: 1px solid #000
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

        .po-header {
            background-color: #fff;
            padding: 3px 3px;
            font-weight: bold;
        }

        .po-header-labels>div:nth-child(5),
        .po-header-labels>div:nth-child(6),
        .po-header-labels>div:nth-child(7),
        .po-header-labels>div:nth-child(8),
        .po-header-labels>div:nth-child(9),
        .po-header>div:nth-child(5),
        .po-header>div:nth-child(6),
        .po-header>div:nth-child(7),
        .po-header>div:nth-child(8), 
        .po-header>div:nth-child(9) {
            text-align: right;
        }

        .separator {
            border-bottom: 1px solid #000000;
            margin: 4px 0;
            clear: both;
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

        .no-print button {
            transition: background-color 0.2s;
        }

        .journal-block {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .grand-total-section {
            margin-top: 20px;
            border-top: 2px solid #000;
            padding-top: 10px;
            display: flex;
            justify-content: flex-end;
        }

        .grand-total-panel {
            width: 80mm;
            border: 1px solid #000;
            font-size: 10px;
            font-weight: bold;
        }

        .grand-total-row {
            display: grid;
            grid-template-columns: 35mm 45mm;
            border-bottom: 1px solid #ccc;
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
        $totalHarga = 0;
        $totalDiscount = 0;
        $totalDpp = 0;
        $totalPpn = 0;
        $branchText = request()->has('branch_codes') ? implode(', ', (array) request()->input('branch_codes')) : 'Semua';
    @endphp

    <div id="raw-source" style="display: none;">
        <div class="header-section">
            <div class="supplier-info-kiri">
                Customer: {{ request('customer') ?: 'Semua' }}
                <br>Cabang: {{ $branchText }}
            </div>
            <h2>Listing Faktur Pajak Penjualan</h2>
            <div class="filter-info">
                Periode:
                {{ request('date_from') ? \Carbon\Carbon::parse(request('date_from'))->format('d/m/Y') : '...' }}
                s/d
                {{ request('date_to') ? \Carbon\Carbon::parse(request('date_to'))->format('d/m/Y') : '...' }}
            </div>
            <div class="info-tambahan">
                <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                <div><span class="info-label">Opr</span>: {{ $user_session->fname ?? 'admin' }}</div>
            </div>
        </div>

        <div class="po-header-labels">
            <div>Faktur Pajak</div>
            <div>No.Faktur</div>
            <div>Tanggal</div>
            <div>Nama Customer</div>
            <div>NPWP</div>
            <div>Harga Jual</div>
            <div>Discount</div>
            <div>DPP</div>
            <div>PPN</div>
        </div>

        @foreach ($results as $row)
            @php
                $hargaJual = (float) $row->famountgross;
                $discount = (float) $row->fdiscount;
                $dpp = (float) $row->famountsonet;
                $ppn = (float) $row->famountpajak;
                $totalHarga += $hargaJual;
                $totalDiscount += $discount;
                $totalDpp += $dpp;
                $totalPpn += $ppn;
            @endphp
            <div class="journal-block">
                <div class="po-header">
                    <div class="truncate">{{ $row->ftaxno }}</div>
                    <div class="truncate">{{ $row->fsono }}</div>
                    <div>{{ $row->fsodate ? \Carbon\Carbon::parse($row->fsodate)->format('d/m/Y') : '' }}</div>
                    <div class="truncate" title="{{ $row->fcustname }}">{{ $row->fcustname }}</div>
                    <div class="truncate" title="{{ $row->fnpwp }}">{{ $row->fnpwp }}</div>
                    <div>{{ number_format($hargaJual, 2, ',', '.') }}</div>
                    <div>{{ number_format($discount, 2, ',', '.') }}</div>
                    <div>{{ number_format($dpp, 2, ',', '.') }}</div>
                    <div>{{ number_format($ppn, 2, ',', '.') }}</div>
                </div>
                @if (!$loop->last)
                    <div class="separator"></div>
                @endif
            </div>
        @endforeach

        <div class="grand-total-section">
            <div class="grand-total-panel">
                <div class="grand-total-row">
                    <div>Total Harga</div>
                    <div>{{ number_format($totalHarga, 2, ',', '.') }}</div>
                </div>
                <div class="grand-total-row">
                    <div>Discount</div>
                    <div>{{ number_format($totalDiscount, 2, ',', '.') }}</div>
                </div>
                <div class="grand-total-row">
                    <div>DPP</div>
                    <div>{{ number_format($totalDpp, 2, ',', '.') }}</div>
                </div>
                <div class="grand-total-row">
                    <div>PPN</div>
                    <div>{{ number_format($totalPpn, 2, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="report-wrapper" id="reportWrapper">
        @if ($results->isEmpty())
            <div class="page-a4 page-a4-strict">
                <div class="header-section">
                    <div class="supplier-info-kiri" style="top: 1mm;">
                        Customer: {{ request('customer') ?: 'Semua' }}
                        <br>Cabang: {{ $branchText }}
                    </div>
                    <h2>Listing Faktur Pajak Penjualan</h2>
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
