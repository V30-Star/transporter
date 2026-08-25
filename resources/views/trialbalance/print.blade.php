<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Trial Balance</title>
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
            padding: 12mm 15mm;
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

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 6px;
        }

        .comp-name {
            font-size: 18px;
            font-weight: bold;
            font-style: italic;
            color: #0f172a;
        }

        .comp-city {
            font-size: 11px;
            color: #475569;
            margin-top: 1px;
        }

        .title-so {
            font-size: 18px;
            color: #0000ff;
            text-decoration: underline;
            font-weight: bold;
            text-align: right;
            text-transform: uppercase;
        }

        .customer-container {
            border: 1px solid #000;
            border-radius: 8px;
            padding: 6px 12px;
            width: 100%;
            position: relative;
            margin-top: 4px;
            margin-bottom: 6px;
        }

        .info-col-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .info-col-table td {
            padding: 1px 2px;
            vertical-align: top;
            line-height: 1.4;
        }

        .info-col-label {
            font-weight: 600;
            color: #334155;
            width: 95px;
        }

        .tb-header-labels,
        .tb-row,
        .tb-total-row,
        .tb-footer-row {
            display: grid;
            grid-template-columns: 24mm 1fr 28mm 28mm 28mm 28mm;
            gap: 1px;
            font-size: 8.5px;
            padding: 2.5px 6px;
            align-items: center;
        }

        .tb-header-labels {
            background-color: transparent;
            color: #000000;
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
            margin-bottom: 0px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .tb-row {
            background-color: transparent;
            color: #0f172a;
            border-bottom: 1px dotted #e2e8f0;
        }

        .tb-total-row {
            font-weight: bold;
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
            color: #000000;
            margin-top: 4px;
            margin-bottom: 4px;
        }

        .tb-footer-row {
            font-weight: bold;
            border-top: 1px dashed #000000;
            border-bottom: 1px dashed #000000;
            background-color: transparent;
            color: #000000;
            margin-top: 4px;
            padding: 3px 6px;
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
            background: rgba(255, 255, 255, 0.9);
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
            box-shadow: 0 2px 4px rgba(15, 23, 42, 0.2);
        }

        .print-button:hover {
            background-color: #000000;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(15, 23, 42, 0.3);
        }

        .journal-block {
            page-break-inside: avoid;
            break-inside: avoid;
            margin-bottom: 0px;
        }

        .end-of-report-inline {
            font-family: 'IBM Plex Sans', sans-serif;
            font-size: 8px;
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: center;
            margin-top: 15px;
            border-top: 1px solid #000000;
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
                padding: 12mm 15mm !important;
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
        $companySetting = company_setting();
        $companyProject = $companySetting->fproject ?? 'PT. M-Trade';
        $companyCity = $companySetting->fcity ?? '';

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

        $accountText = 'Semua';
        if (filled($accountFrom) || filled($accountTo)) {
            $fromLabel = $accountFrom ? "({$accountFrom}) " . ($accountFromObj->faccname ?? '') : 'Awal';
            $toLabel = $accountTo ? "({$accountTo}) " . ($accountToObj->faccname ?? '') : 'Akhir';
            $accountText = "{$fromLabel} s.d {$toLabel}";
        }
        $branchText = !empty(request('branch_codes')) ? implode(', ', (array) request('branch_codes')) : 'Semua';
        $periodText = "{$periodFromText} s.d {$periodToText}";
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
            style="padding: 7px 12px; background-color: #22c55e; color: white; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; transition: background-color .2s;"
            onmouseover="this.style.backgroundColor='#16a34a'"
            onmouseout="this.style.backgroundColor='#22c55e'">
            📊 Excel
        </a>
    </div>

    {{-- Source DOM for dynamic pagination --}}
    <div id="raw-source" style="display: none;">
        <div class="header-section">
            <div class="header-row">
                <div>
                    <div class="comp-name">{{ strtoupper($companyProject) }}</div>
                    @if(!empty($companyCity))
                        <div class="comp-city">{{ $companyCity }}</div>
                    @endif
                </div>
                <div>
                    <div class="title-so">Laporan Trial Balance</div>
                </div>
            </div>

            <div class="customer-container">
                <div style="display: flex; justify-content: space-between; align-items: stretch; gap: 15px;">
                    {{-- Kiri --}}
                    <div style="flex: 1; padding-right: 15px; border-right: 1px solid #000;">
                        <table class="info-col-table">
                            <tr>
                                <td class="info-col-label">No. Account</td>
                                <td style="width: 8px;">:</td>
                                <td>{{ $accountText }}</td>
                            </tr>
                            <tr>
                                <td class="info-col-label">Cabang</td>
                                <td>:</td>
                                <td>{{ $branchText }}</td>
                            </tr>
                        </table>
                    </div>
                    {{-- Kanan --}}
                    <div style="flex: 1; padding-left: 5px;">
                        <table class="info-col-table">
                            <tr>
                                <td class="info-col-label">Periode</td>
                                <td style="width: 8px;">:</td>
                                <td style="font-weight: bold;">{{ $periodText }}</td>
                            </tr>
                            <tr>
                                <td class="info-col-label">Tanggal</td>
                                <td>:</td>
                                <td>{{ date('d-m-Y') }}</td>
                            </tr>
                            <tr>
                                <td class="info-col-label">Jam</td>
                                <td>:</td>
                                <td>{{ date('H:i') }}</td>
                            </tr>
                            <tr>
                                <td class="info-col-label">Operator</td>
                                <td>:</td>
                                <td>{{ $user_session->fname ?? ($user_session->username ?? 'admin') }}</td>
                            </tr>
                            <tr class="hal-row">
                                <td class="info-col-label">Hal</td>
                                <td>:</td>
                                <td><span class="page-number-current"></span> / <span class="page-number-total"></span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tb-header-labels">
            <div>Account</div>
            <div>Nama Account</div>
            <div style="text-align: right;">Saldo Awal</div>
            <div style="text-align: right;">Mutasi Debet</div>
            <div style="text-align: right;">Mutasi Kredit</div>
            <div style="text-align: right;">Saldo Akhir</div>
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
                    <div class="header-row">
                        <div>
                            <div class="comp-name">{{ strtoupper($companyProject) }}</div>
                            @if(!empty($companyCity))
                                <div class="comp-city">{{ $companyCity }}</div>
                            @endif
                        </div>
                        <div>
                            <div class="title-so">Laporan Trial Balance</div>
                        </div>
                    </div>
                    <div class="customer-container">
                        <div style="display: flex; justify-content: space-between; align-items: stretch; gap: 15px;">
                            <div style="flex: 1; padding-right: 15px; border-right: 1px solid #000;">
                                <table class="info-col-table">
                                    <tr>
                                        <td class="info-col-label">No. Account</td>
                                        <td style="width: 8px;">:</td>
                                        <td>{{ $accountText }}</td>
                                    </tr>
                                    <tr>
                                        <td class="info-col-label">Cabang</td>
                                        <td>:</td>
                                        <td>{{ $branchText }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div style="flex: 1; padding-left: 5px;">
                                <table class="info-col-table">
                                    <tr>
                                        <td class="info-col-label">Periode</td>
                                        <td style="width: 8px;">:</td>
                                        <td style="font-weight: bold;">{{ $periodText }}</td>
                                    </tr>
                                    <tr>
                                        <td class="info-col-label">Tanggal</td>
                                        <td>:</td>
                                        <td>{{ date('d-m-Y') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="info-col-label">Jam</td>
                                        <td>:</td>
                                        <td>{{ date('H:i') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="info-col-label">Operator</td>
                                        <td>:</td>
                                        <td>{{ $user_session->fname ?? ($user_session->username ?? 'admin') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="info-col-label">Hal</td>
                                        <td>:</td>
                                        <td>1 / 1</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tb-header-labels">
                    <div>Account</div>
                    <div>Nama Account</div>
                    <div style="text-align: right;">Saldo Awal</div>
                    <div style="text-align: right;">Mutasi Debet</div>
                    <div style="text-align: right;">Mutasi Kredit</div>
                    <div style="text-align: right;">Saldo Akhir</div>
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
                    <div class="page-content" style="margin-top: 3px;"></div>
                `;
                reportWrapper.appendChild(page);
                return page;
            }

            let currentPage = createNewPage();
            let currentContent = currentPage.querySelector(".page-content");

            items.forEach((item) => {
                let currentItemClone = document.createElement("div");
                currentItemClone.className = item.className;
                currentItemClone.innerHTML = item.innerHTML;
                currentContent.appendChild(currentItemClone);

                if (currentPage.offsetHeight > maxPageHeight) {
                    const count = currentContent.children.length;
                    if (count > 1) {
                        currentContent.removeChild(currentItemClone);
                        currentPage = createNewPage();
                        currentContent = currentPage.querySelector(".page-content");

                        currentItemClone = document.createElement("div");
                        currentItemClone.className = item.className;
                        currentItemClone.innerHTML = item.innerHTML;
                        currentContent.appendChild(currentItemClone);
                    }
                }
            });

            // Set final page numbers and apply strict size
            const allPages = reportWrapper.querySelectorAll(".page-a4");
            allPages.forEach((page, index) => {
                page.classList.add("page-a4-strict");
                const currentEls = page.querySelectorAll(".page-number-current");
                const totalEls = page.querySelectorAll(".page-number-total");
                currentEls.forEach(el => el.textContent = index + 1);
                totalEls.forEach(el => el.textContent = allPages.length);
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
