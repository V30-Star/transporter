<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trial Balance</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;700&family=IBM+Plex+Sans:wght@400;500;700&family=Source+Serif+4:opsz,wght@8..60,700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'IBM Plex Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 10px;
            color: #0f172a;
            background-color: #f1f5f9;
            counter-reset: page;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .page-a4 {
            width: 210mm;
            margin: 30px auto;
            background: white;
            padding: 15mm 15mm 15mm 15mm;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
            position: relative;
            box-sizing: border-box;
            height: auto;
            min-height: 0;
            border-radius: 4px;
        }

        .page-a4-strict {
            height: 297mm !important;
            min-height: 297mm !important;
            overflow: hidden !important;
        }

        .header-section {
            position: relative;
            margin-bottom: 8px;
            text-align: center;
            padding-bottom: 8px;
        }

        .header-section .company-title {
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .header-section h2 {
            font-family: 'Source Serif 4', Georgia, "Times New Roman", serif;
            font-size: 18px;
            margin-bottom: 4px;
            font-weight: 700;
            text-transform: uppercase;
            color: #cc0000;
            letter-spacing: 0.5px;
        }

        .filter-info {
            font-size: 10px;
            color: #475569;
            font-weight: 600;
        }

        .info-kiri {
            position: absolute;
            top: 0;
            left: 0;
            font-size: 9px;
            color: #334155;
            text-align: left;
            line-height: 1.4;
        }

        .info-tambahan {
            position: absolute;
            top: 0;
            right: 0;
            font-size: 9px;
            color: #334155;
            text-align: left;
            line-height: 1.4;
        }

        .info-label {
            font-weight: 600;
            display: inline-block;
            width: 45px;
            color: #475569;
        }

        .tb-header-labels,
        .tb-row,
        .tb-total-row,
        .tb-footer-row {
            display: grid;
            grid-template-columns: 22mm 1fr 28mm 28mm 28mm 28mm;
            gap: 1px;
            font-size: 9px;
            padding: 3px 6px;
            align-items: center;
        }

        .tb-header-labels {
            background-color: transparent;
            color: #000000;
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
            margin-bottom: 2px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .tb-row {
            background-color: transparent;
            color: #0f172a;
            border-bottom: 1px dotted #e2e8f0;
        }

        .tb-row:hover {
            background-color: #f8fafc;
        }

        .tb-total-row {
            font-weight: bold;
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
            color: #000000;
            margin-top: 4px;
            margin-bottom: 8px;
        }

        .tb-footer-row {
            font-weight: bold;
            border-top: 1px dashed #64748b;
            border-bottom: 1px dashed #64748b;
            background-color: #f8fafc;
            color: #0f172a;
            margin-top: 4px;
            padding: 4px 6px;
        }

        .tb-row > div:nth-child(3),
        .tb-row > div:nth-child(4),
        .tb-row > div:nth-child(5),
        .tb-row > div:nth-child(6),
        .tb-header-labels > div:nth-child(3),
        .tb-header-labels > div:nth-child(4),
        .tb-header-labels > div:nth-child(5),
        .tb-header-labels > div:nth-child(6),
        .tb-total-row > div:not(:first-child):not(:nth-child(2)),
        .tb-footer-row > div:last-child {
            text-align: right;
        }

        .tb-row > div:nth-child(1),
        .tb-row > div:nth-child(3),
        .tb-row > div:nth-child(4),
        .tb-row > div:nth-child(5),
        .tb-row > div:nth-child(6),
        .tb-total-row > div:not(:first-child):not(:nth-child(2)),
        .tb-footer-row > div:last-child {
            font-family: 'IBM Plex Mono', Courier, monospace;
            font-variant-numeric: tabular-nums;
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
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            padding: 8px 16px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.15);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }

        .print-button {
            background-color: #0f172a;
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
        }

        .print-button:hover {
            background-color: #000000;
            transform: translateY(-1px);
        }

        .journal-block {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .end-of-report-inline {
            text-align: center;
            font-family: 'IBM Plex Sans', sans-serif;
            font-size: 8px;
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 15px;
            padding-top: 5px;
        }

        @media print {
            body {
                background-color: white !important;
                color: #0f172a !important;
                margin: 0;
                padding: 0;
            }

            .page-a4 {
                width: 210mm;
                height: 297mm !important;
                margin: 0 auto !important;
                padding: 15mm 15mm 15mm 15mm !important;
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
                size: A4 portrait;
                margin: 0;
            }
        }
    </style>
</head>

<body>
    @php
        if (!function_exists('formatTbNumber')) {
            function formatTbNumber($val) {
                $num = (float) $val;
                if (abs($num) < 0.000001) {
                    return '0,00';
                }
                if ($num < 0) {
                    return '(' . number_format(abs($num), 2, ',', '.') . ')';
                }
                return number_format($num, 2, ',', '.');
            }
        }

        $accountText = 'Semua Account';
        if (filled($accountFrom) || filled($accountTo)) {
            $fromLabel = $accountFrom ? "({$accountFrom}) " . ($accountFromObj->faccname ?? '') : 'Awal';
            $toLabel = $accountTo ? "({$accountTo}) " . ($accountToObj->faccname ?? '') : 'Akhir';
            $accountText = "{$fromLabel} s.d {$toLabel}";
        }
    @endphp

    <div class="no-print">
        <button class="print-button" onclick="window.print()">🖨️ Cetak Laporan</button>

        <button onclick="adjustZoom(-0.1)"
            style="padding: 6px 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold;">
            −
        </button>

        <span id="zoomLabel"
            style="min-width: 48px; text-align: center; font-size: 13px; font-weight: bold; color: #333; align-self: center;">
            100%
        </span>

        <button onclick="adjustZoom(0.1)"
            style="padding: 6px 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold;">
            +
        </button>

        <a href="{{ route('trialbalance.excel', request()->query()) }}"
            style="padding: 7px 12px; background-color: #22c55e; color: white; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 12px; display: inline-flex; align-items: center; justify-content: center;">
            📊 Excel
        </a>
    </div>

    {{-- Source DOM for dynamic pagination --}}
    <div id="raw-source" style="display: none;">
        <div class="header-section">
            <div class="info-kiri">
                <div><strong>{{ company_name() }}</strong></div>
                <div>Kota: {{ company_setting()->fcity ?? '-' }}</div>
                <div>Account: {{ $accountText }}</div>
            </div>
            <h2>{{ $title }}</h2>
            <div class="filter-info">
                Periode: {{ $periodFromText }} s.d {{ $periodToText }}
            </div>
            <div class="info-tambahan">
                <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                <div><span class="info-label">Opr</span>: {{ $user_session->fname ?? 'admin' }}</div>
            </div>
        </div>

        <div class="tb-header-labels">
            <div>Account</div>
            <div>Nama Account</div>
            <div>Saldo Awal</div>
            <div>Mutasi Debet</div>
            <div>Mutasi Kredit</div>
            <div>Saldo Akhir</div>
        </div>

        @forelse ($rows as $row)
            <div class="journal-block">
                <div class="tb-row">
                    <div class="truncate" title="{{ $row->faccount }}">{{ $row->faccount }}</div>
                    <div class="truncate" title="{{ $row->faccname }}">{{ $row->faccname }}</div>
                    <div>{{ formatTbNumber($row->fsaldoawal) }}</div>
                    <div>{{ formatTbNumber($row->fmutasidebet) }}</div>
                    <div>{{ formatTbNumber($row->fmutasicredit) }}</div>
                    <div>{{ formatTbNumber($row->fsaldoakhir) }}</div>
                </div>
            </div>
        @empty
            <div class="journal-block" style="text-align: center; padding: 25px; font-size: 11px; color: #64748b;">
                Tidak ada data neraca saldo pada periode ini.
            </div>
        @endforelse

        {{-- Grand Total Block --}}
        @if (count($rows) > 0)
            <div class="journal-block">
                <div class="tb-total-row">
                    <div>TOTAL</div>
                    <div></div>
                    <div>{{ formatTbNumber($totalSaldoAwal) }}</div>
                    <div>{{ formatTbNumber($totalMutasiDebet) }}</div>
                    <div>{{ formatTbNumber($totalMutasiKredit) }}</div>
                    <div>{{ formatTbNumber($totalSaldoAkhir) }}</div>
                </div>
            </div>

            {{-- Saldo Laba Ditahan / Laba Tahun Berjalan --}}
            <div class="journal-block">
                <div class="tb-footer-row">
                    <div style="grid-column: span 5;">
                        Saldo Laba Tahun Berjalan ({{ $labaBerjalanCode }})
                    </div>
                    <div>{{ formatTbNumber($saldoLabaBerjalan) }}</div>
                </div>
            </div>
        @endif

        <div class="journal-block">
            <div class="end-of-report-inline">** END OF REPORT **</div>
        </div>
    </div>

    {{-- Rendered Paginated Output Container --}}
    <div class="report-wrapper" id="reportWrapper">
        @if (count($rows) === 0)
            <div class="page-a4 page-a4-strict">
                <div class="header-section">
                    <div class="info-kiri">
                        <div><strong>{{ company_name() }}</strong></div>
                        <div>Kota: {{ company_setting()->fcity ?? '-' }}</div>
                        <div>Account: {{ $accountText }}</div>
                    </div>
                    <h2>{{ $title }}</h2>
                    <div class="filter-info">
                        Periode: {{ $periodFromText }} s.d {{ $periodToText }}
                    </div>
                    <div class="info-tambahan">
                        <div><span class="info-label">Hal</span>: 1 / 1</div>
                        <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                        <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                        <div><span class="info-label">Opr</span>: {{ $user_session->fname ?? 'admin' }}</div>
                    </div>
                </div>
                <div class="tb-header-labels">
                    <div>Account</div>
                    <div>Nama Account</div>
                    <div>Saldo Awal</div>
                    <div>Mutasi Debet</div>
                    <div>Mutasi Kredit</div>
                    <div>Saldo Akhir</div>
                </div>
                <div style="margin-top: 40px; text-align: center; font-size: 11px; color: #64748b;">
                    Tidak ada data neraca saldo pada periode ini.
                </div>
                <div class="end-of-report-inline">** END OF REPORT **</div>
            </div>
        @endif
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const rawSource = document.getElementById("raw-source");
            const reportWrapper = document.getElementById("reportWrapper");
            if (!rawSource || !reportWrapper) return;

            const items = Array.from(rawSource.querySelectorAll(".journal-block"));
            if (items.length === 0) return;

            // Measure actual 297mm height on screen
            const tempDiv = document.createElement("div");
            tempDiv.style.height = "297mm";
            tempDiv.style.position = "absolute";
            tempDiv.style.visibility = "hidden";
            document.body.appendChild(tempDiv);
            const pageHeightPx = tempDiv.offsetHeight;
            document.body.removeChild(tempDiv);

            const maxPageHeight = pageHeightPx - 20;

            const headerSectionHtml = rawSource.querySelector(".header-section").outerHTML;
            const tbHeaderLabelsHtml = rawSource.querySelector(".tb-header-labels").outerHTML;

            function createNewPage() {
                const page = document.createElement("div");
                page.className = "page-a4";
                page.innerHTML = `
                    <div class="page-header-container">
                        ${headerSectionHtml}
                        ${tbHeaderLabelsHtml}
                    </div>
                    <div class="page-content" style="margin-top: 2px;"></div>
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

            items.forEach((item) => {
                const clone = item.cloneNode(true);
                currentContent.appendChild(clone);

                if (currentPage.offsetHeight > maxPageHeight) {
                    const blockCount = currentContent.querySelectorAll(".journal-block").length;
                    if (blockCount > 1) {
                        currentContent.removeChild(clone);
                        currentPage = createNewPage();
                        currentContent = currentPage.querySelector(".page-content");
                        currentContent.appendChild(clone);
                    }
                }
            });

            // Lock A4 size and update page numbers
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
</body>

</html>
