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
            margin-bottom: 30px;
            text-align: center;
            padding-bottom: 20px;
        }

        .header-section h2 {
            font-family: 'Source Serif 4', Georgia, "Times New Roman", serif;
            font-size: 20px;
            margin-bottom: 6px;
            font-weight: 600;
            text-transform: uppercase;
            color: #cc0000; /* Dark Red matching Listing PO */
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
            color: #333;
            text-align: left;
            line-height: 1.4;
        }

        .info-label {
            font-weight: 600;
            display: inline-block;
            width: 50px;
            color: #475569; /* Slate 600 */
        }

        .po-header-labels,
        .item-row,
        .group-total-row {
            display: grid;
            grid-template-columns: 8mm 12mm 28mm 20mm 22mm 30mm 30mm 30mm;
            gap: 4px;
            font-size: 8px;
            padding: 4px 3px;
        }

        .po-header-labels {
            border: 1px solid #000;
            margin-bottom: 1px;
            font-weight: bold;
        }

        .item-row {
            padding: 3px 3px;
        }

        .item-row>div:nth-child(6),
        .item-row>div:nth-child(7),
        .po-header-labels>div:nth-child(6),
        .po-header-labels>div:nth-child(7),
        .group-total-row>div:nth-child(2),
        .group-total-row>div:nth-child(3) {
            text-align: right;
        }

        .item-row>div:nth-child(7),
        .po-header-labels>div:nth-child(7) {
            padding-right: 6px;
        }

        .group-row {
            display: block;
            font-weight: bold;
            font-size: 8px;
            padding: 2px 6px;
            margin-bottom: 1px;
        }

        .group-total-row {
            font-weight: bold;
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

        @media print {
            .trx-action-trigger {
                color: inherit !important;
                text-decoration: none !important;
                cursor: default !important;
            }
            .trx-modal-backdrop {
                display: none !important;
            }
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
        }

        .grand-total-section {
            margin-top: 5px;
            border-top: 1px solid #919191;
            border-bottom: 1px solid #919191;
            padding-top: 1px;
        }

        .grand-total-panel {
            font-size: 10px;
        }

        .grand-total-row {
            display: grid;
            grid-template-columns: 8mm 12mm 28mm 20mm 22mm 30mm 30mm 30mm;
            gap: 4px;
            font-weight: bold;
            padding: 4px 3px;
        }

        .grand-total-row>div:nth-child(2),
        .grand-total-row>div:nth-child(3) {
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
            onmouseover="this.style.backgroundColor='#16a34a'" onmouseout="this.style.backgroundColor='#22c55e'">
            📊 Excel
        </a>
    </div>

    @php
        $grandFaktur = 0;
        $grandPiutang = 0;
        $branchText = request()->has('branch_codes')
            ? implode(', ', (array) request()->input('branch_codes'))
            : 'Semua';
        $salesmanText = request('salesman') ?: 'Semua';
        $wilayahText = request('wilayah') ?: 'Semua';
        $customerText = request('cust_from') || request('cust_to')
            ? (request('cust_from') ?: 'Awal') . ' s/d ' . (request('cust_to') ?: 'Akhir')
            : 'Semua';
        $returText = request()->boolean('include_retur_penjualan') ? 'Ya' : 'Tidak';
    @endphp

    <div id="raw-source" style="display: none;">
        <div class="header-section">
            <div class="supplier-info-kiri">
                Cabang: {{ $branchText }}<br>
                Mode: {{ $mode === 'rekap' ? 'Rekap' : 'Detail' }}<br>
                Salesman: {{ $salesmanText }}<br>
                Wilayah: {{ $wilayahText }}<br>
                Customer: {{ $customerText }}<br>
                Retur Penjualan: {{ $returText }}
            </div>
            <h2>Listing Piutang Penjualan</h2>
            <div class="filter-info">
                <div>Per Tanggal: {{ \Carbon\Carbon::parse(request('per_tanggal') ?: now())->format('d/m/Y') }}</div>
                @if (request()->boolean('tgl_pembayaran'))
                    <div>Tgl.Pembayaran s.d.: {{ request('tgl_pembayaran_date') ? \Carbon\Carbon::parse(request('tgl_pembayaran_date'))->format('d/m/Y') : \Carbon\Carbon::parse(request('per_tanggal') ?: now())->format('d/m/Y') }}</div>
                @else
                    <div>Tgl.Pembayaran: Semua</div>
                @endif
                @if (request('due_filter') === 'due')
                    <div>Jatuh Tempo s/d: {{ request('due_date') ?: request('per_tanggal') }}</div>
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
                    @php
                        $isReturn = ($row->fstockmtcode ?? '') === 'REJ';
                        $viewUrl = $isReturn ? route('returpenjualan.view', $row->ftranmtid) : route('invoice.view', $row->ftranmtid);
                        $editUrl = $isReturn ? route('returpenjualan.edit', $row->ftranmtid) : route('invoice.edit', $row->ftranmtid);
                    @endphp
                    <div class="journal-block">
                        <div class="item-row">
                            <div class="text-center">{{ $index + 1 }}</div>
                            <div>{{ $row->fbranchcode }}</div>
                            <div class="truncate {{ $isReturn ? 'text-rej' : '' }}" title="{{ $row->fsono }}">
                                <span class="trx-action-trigger" onclick="openTrxActionModal(event, '{{ $row->fsono }}', '{{ $viewUrl }}', '{{ $editUrl }}')">{{ $row->fsono }}</span>
                            </div>
                            <div>{{ $row->fsodate ? \Carbon\Carbon::parse($row->fsodate)->format('d/m/Y') : '' }}</div>
                            <div>
                                {{ $row->fjatuhtempo ? \Carbon\Carbon::parse($row->fjatuhtempo)->format('d/m/Y') : '' }}
                            </div>
                            <div>{{ number_format((float) $row->famountso, 2, ',', '.') }}</div>
                            <div>{{ number_format((float) $row->fsisapiu, 2, ',', '.') }}</div>
                            <div title="{{ $row->fsalesman }}">{{ $row->fsalesman }}</div>
                        </div>
                    </div>
                @endforeach
            @endif

            {{-- Group Total block --}}
            <div class="journal-block">
                <div class="group-total-row">
                    <div style="grid-column: span 5; text-align: right; padding-right: 8px;">
                        Total ({{ $first->fcustname }})</div>
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
                    <div style="grid-column: span 5; text-align: right; padding-right: 8px;">Grand Total:</div>
                    <div>{{ number_format((float) $grandFaktur, 2, ',', '.') }}</div>
                    <div>{{ number_format((float) $grandPiutang, 2, ',', '.') }}</div>
                    <div></div>
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
                        Mode: {{ $mode === 'rekap' ? 'Rekap' : 'Detail' }}<br>
                        Salesman: {{ $salesmanText }}<br>
                        Wilayah: {{ $wilayahText }}<br>
                        Customer: {{ $customerText }}<br>
                        Retur Penjualan: {{ $returText }}
                    </div>
                    <h2>Listing Piutang Penjualan</h2>
                    <div class="info-tambahan">
                        <div><span class="info-label">Hal</span>: 1 / 1</div>
                        <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                        <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                        <div><span class="info-label">Opr</span>: {{ $user_session->fname ?? 'User' }}</div>
                    </div>
                    <div style="margin-top: 30px; text-align: center; font-size: 12px; color: #666;">Tidak ada data
                        ditemukan.</div>
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
