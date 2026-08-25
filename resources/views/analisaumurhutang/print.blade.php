<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisa Umur Hutang</title>
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

        /* Screen Simulation Styles for A4 Pages (LANDSCAPE) */
        .page-a4 {
            width: 297mm;
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

        /* Strict height applied after pagination (LANDSCAPE height is 210mm) */
        .page-a4-strict {
            height: 210mm !important;
            min-height: 210mm !important;
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

        /* --- AGING COLUMN STYLES (13 Kolom) --- */
        .row-labels,
        .row-data,
        .row-total,
        .row-grand-total {
            display: grid;
            grid-template-columns: 7mm 11mm 26mm 18mm 18mm 13mm 23mm 19mm 19mm 19mm 19mm 19mm 19mm;
            gap: 1px;
            font-size: 8px;
            padding: 2px 4px;
            align-items: center;
        }

        .row-labels {
            background-color: transparent;
            color: #000000;
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
            margin-bottom: 0px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .row-data {
            background-color: transparent;
            color: #cc0000;
        }

        .customer-heading {
            display: block;
            background-color: #f8fafc;
            color: #0f172a;
            font-weight: bold;
            font-size: 8.5px;
            padding: 3px 6px;
            margin-top: 4px;
            margin-bottom: 2px;
            border-bottom: 1px dashed #cbd5e1;
            text-transform: uppercase;
        }

        .row-total {
            background-color: transparent;
            font-weight: bold;
            border-top: 1px dashed #cbd5e1;
            border-bottom: 1px solid #000;
            padding: 3px 4px;
            margin-bottom: 4px;
            color: #0f172a;
        }

        .row-grand-total {
            background-color: transparent;
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 2px double #000;
            padding: 4px;
            margin-top: 4px;
            color: #0f172a;
        }

        .text-rej,
        .text-rej div,
        .text-rej span {
            color: #ff0000 !important;
        }

        /* Alignment */
        .row-labels > div:nth-child(1),
        .row-labels > div:nth-child(2),
        .row-labels > div:nth-child(4),
        .row-labels > div:nth-child(5),
        .row-data > div:nth-child(1),
        .row-data > div:nth-child(2),
        .row-data > div:nth-child(4),
        .row-data > div:nth-child(5) {
            text-align: center;
        }

        .row-labels > div:nth-child(6),
        .row-labels > div:nth-child(7),
        .row-labels > div:nth-child(8),
        .row-labels > div:nth-child(9),
        .row-labels > div:nth-child(10),
        .row-labels > div:nth-child(11),
        .row-labels > div:nth-child(12),
        .row-labels > div:nth-child(13),
        .row-data > div:nth-child(6),
        .row-data > div:nth-child(7),
        .row-data > div:nth-child(8),
        .row-data > div:nth-child(9),
        .row-data > div:nth-child(10),
        .row-data > div:nth-child(11),
        .row-data > div:nth-child(12),
        .row-data > div:nth-child(13),
        .row-total > div:nth-child(2),
        .row-total > div:nth-child(3),
        .row-total > div:nth-child(4),
        .row-total > div:nth-child(5),
        .row-total > div:nth-child(6),
        .row-total > div:nth-child(7),
        .row-total > div:nth-child(8),
        .row-grand-total > div:nth-child(2),
        .row-grand-total > div:nth-child(3),
        .row-grand-total > div:nth-child(4),
        .row-grand-total > div:nth-child(5),
        .row-grand-total > div:nth-child(6),
        .row-grand-total > div:nth-child(7),
        .row-grand-total > div:nth-child(8) {
            text-align: right;
        }

        /* Fonts for Numbers & System Codes */
        .row-data > div:nth-child(1),
        .row-data > div:nth-child(2),
        .row-data > div:nth-child(3),
        .row-data > div:nth-child(4),
        .row-data > div:nth-child(5),
        .row-data > div:nth-child(6),
        .row-data > div:nth-child(7),
        .row-data > div:nth-child(8),
        .row-data > div:nth-child(9),
        .row-data > div:nth-child(10),
        .row-data > div:nth-child(11),
        .row-data > div:nth-child(12),
        .row-data > div:nth-child(13),
        .row-total > div:nth-child(2),
        .row-total > div:nth-child(3),
        .row-total > div:nth-child(4),
        .row-total > div:nth-child(5),
        .row-total > div:nth-child(6),
        .row-total > div:nth-child(7),
        .row-total > div:nth-child(8),
        .row-grand-total > div:nth-child(2),
        .row-grand-total > div:nth-child(3),
        .row-grand-total > div:nth-child(4),
        .row-grand-total > div:nth-child(5),
        .row-grand-total > div:nth-child(6),
        .row-grand-total > div:nth-child(7),
        .row-grand-total > div:nth-child(8) {
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

        .po-totals-panel-wrapper {
            margin-top: 5px;
            width: 100%;
            border-top: 1px solid #000000;
            padding-top: 5px;
            position: relative;
            page-break-inside: avoid;
            break-inside: avoid;
            text-align: center;
        }

        .end-of-report-inline {
            font-family: 'IBM Plex Sans', sans-serif;
            font-size: 8px;
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

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
                size: A4 landscape;
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

        $grand = ['faktur' => 0, 'sisa' => 0, 'undue' => 0, 'd30' => 0, 'd60' => 0, 'd90' => 0, 'd91' => 0, 'd1y' => 0];
        $branchText = request()->has('branch_codes') ? implode(', ', (array) request()->input('branch_codes')) : 'Semua';
        $supplierText = trim((string) request('supplier')) !== '' ? request('supplier') : 'Semua';

        $dateFromFmt = request('date_from') ? date('d-m-Y', strtotime(request('date_from'))) : '...';
        $dateToFmt = request('date_to') ? date('d-m-Y', strtotime(request('date_to'))) : '...';
        $period = $dateFromFmt . ' s/d ' . $dateToFmt;
        if (request('mode') === 'due' && request('due_date_to')) {
            $period .= ' | JT s/d ' . date('d-m-Y', strtotime(request('due_date_to')));
        }
    @endphp

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

        <a href="{{ route('analisaumurhutang.excel', request()->query()) }}"
            style="padding: 7px 12px; background-color: #22c55e; color: white; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; transition: background-color 0.2s;"
            onmouseover="this.style.backgroundColor='#16a34a'"
            onmouseout="this.style.backgroundColor='#22c55e'">
            📊 Excel
        </a>
    </div>

    {{-- Hidden Raw Data Container --}}
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
                    <div class="title-so">Analisa Umur Hutang</div>
                </div>
            </div>

            <div class="customer-container">
                <div style="display: flex; justify-content: space-between; align-items: stretch; gap: 15px;">
                    {{-- Kiri --}}
                    <div style="flex: 1; padding-right: 15px; border-right: 1px solid #000;">
                        <table class="info-col-table">
                            <tr>
                                <td class="info-col-label">Cabang</td>
                                <td style="width: 8px;">:</td>
                                <td>{{ $branchText }}</td>
                            </tr>
                            <tr>
                                <td class="info-col-label">Supplier</td>
                                <td>:</td>
                                <td>{{ $supplierText }}</td>
                            </tr>
                        </table>
                    </div>
                    {{-- Kanan --}}
                    <div style="flex: 1; padding-left: 5px;">
                        <table class="info-col-table">
                            <tr>
                                <td class="info-col-label">Periode</td>
                                <td style="width: 8px;">:</td>
                                <td style="font-weight: bold;">{{ $period }}</td>
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
                                <td>{{ $user_session->fname ?? ($user_session->username ?? (auth()->user()->fname ?? 'User')) }}</td>
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

        <div class="row-labels">
            <div>No.</div>
            <div>Cab.</div>
            <div>No.Faktur</div>
            <div>Tanggal</div>
            <div>Jatuh Tempo</div>
            <div style="text-align: right;">Umur(Hr)</div>
            <div style="text-align: right;">Nilai Faktur</div>
            <div style="text-align: right;">Un Due</div>
            <div style="text-align: right;">0-30 Hari</div>
            <div style="text-align: right;">31-60 Hari</div>
            <div style="text-align: right;">61-90 Hari</div>
            <div style="text-align: right;">91-1 Tahun</div>
            <div style="text-align: right;">&gt;1 Tahun</div>
        </div>

        @forelse ($rows->groupBy('fcustno') as $supplierCode => $items)
            @php
                $name = $items->first()->fsuppliername ?: $supplierCode;
                $tot = [
                    'faktur' => $items->sum('famountso'),
                    'sisa' => $items->sum('fsisapiu'),
                    'undue' => $items->sum('varundue'),
                    'd30' => $items->sum('var30hari'),
                    'd60' => $items->sum('var60hari'),
                    'd90' => $items->sum('ppvar90hari'),
                    'd91' => $items->sum('ppvar91hari'),
                    'd1y' => $items->sum('ppvar1tahun'),
                ];
                foreach ($tot as $key => $value) $grand[$key] += $value;
            @endphp

            <div class="journal-block customer-heading">
                {{ $supplierCode }} - {{ $name }}
            </div>

            @foreach ($items as $index => $row)
                @php
                    $isReturn = in_array($row->fstockmtcode ?? '', ['REB', 'RUB'], true);
                    $viewUrl = $isReturn ? route('returpembelian.view', $row->fstockmtid) : route('fakturpembelian.view', $row->fstockmtid);
                    $editUrl = $isReturn ? route('returpembelian.edit', $row->fstockmtid) : route('fakturpembelian.edit', $row->fstockmtid);
                @endphp
                <div class="journal-block">
                    <div class="row-data">
                        <div>{{ $index + 1 }}</div>
                        <div class="truncate">{{ $row->fbranchcode }}</div>
                        <div class="truncate {{ $isReturn ? 'text-rej' : '' }}" title="{{ $row->fsono }}">
                            <span class="trx-action-trigger" onclick="openTrxActionModal(event, '{{ $row->fsono }}', '{{ $viewUrl }}', '{{ $editUrl }}')">{{ $row->fsono }}</span>
                        </div>
                        <div>{{ $row->fsodate ? \Carbon\Carbon::parse($row->fsodate)->format('d-m-Y') : '' }}</div>
                        <div>{{ $row->fjatuhtempo ? \Carbon\Carbon::parse($row->fjatuhtempo)->format('d-m-Y') : '' }}</div>
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

            <div class="journal-block">
                <div class="row-total">
                    <div style="grid-column: span 6; text-align: right; padding-right: 6px;">Total {{ $name }}</div>
                    <div>{{ number_format((float) $tot['faktur'], 2, ',', '.') }}</div>
                    <div>{{ number_format((float) $tot['undue'], 2, ',', '.') }}</div>
                    <div>{{ number_format((float) $tot['d30'], 2, ',', '.') }}</div>
                    <div>{{ number_format((float) $tot['d60'], 2, ',', '.') }}</div>
                    <div>{{ number_format((float) $tot['d90'], 2, ',', '.') }}</div>
                    <div>{{ number_format((float) $tot['d91'], 2, ',', '.') }}</div>
                    <div>{{ number_format((float) $tot['d1y'], 2, ',', '.') }}</div>
                </div>
            </div>
        @empty
            <div class="journal-block" style="text-align: center; padding: 20px; font-size: 11px; color: #666;">Tidak ada data ditemukan.</div>
        @endforelse
    </div>

    {{-- Hidden Totals Panel Container --}}
    <div id="po-totals-panel-raw" style="display: none;">
        <div class="po-totals-panel-wrapper">
            <div class="end-of-report-inline">** END OF REPORT **</div>
            <div class="row-grand-total">
                <div style="grid-column: span 6; text-align: right; padding-right: 6px;">GRAND TOTAL</div>
                <div>{{ number_format((float) $grand['faktur'], 2, ',', '.') }}</div>
                <div>{{ number_format((float) $grand['undue'], 2, ',', '.') }}</div>
                <div>{{ number_format((float) $grand['d30'], 2, ',', '.') }}</div>
                <div>{{ number_format((float) $grand['d60'], 2, ',', '.') }}</div>
                <div>{{ number_format((float) $grand['d90'], 2, ',', '.') }}</div>
                <div>{{ number_format((float) $grand['d91'], 2, ',', '.') }}</div>
                <div>{{ number_format((float) $grand['d1y'], 2, ',', '.') }}</div>
            </div>
        </div>
    </div>

    {{-- Screen Render Target --}}
    <div class="report-wrapper" id="reportWrapper">
        @if ($rows->isEmpty())
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
                            <div class="title-so">Analisa Umur Hutang</div>
                        </div>
                    </div>
                    <div class="customer-container">
                        <div style="display: flex; justify-content: space-between; align-items: stretch; gap: 15px;">
                            <div style="flex: 1; padding-right: 15px; border-right: 1px solid #000;">
                                <table class="info-col-table">
                                    <tr>
                                        <td class="info-col-label">Cabang</td>
                                        <td style="width: 8px;">:</td>
                                        <td>{{ $branchText }}</td>
                                    </tr>
                                    <tr>
                                        <td class="info-col-label">Supplier</td>
                                        <td>:</td>
                                        <td>{{ $supplierText }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div style="flex: 1; padding-left: 5px;">
                                <table class="info-col-table">
                                    <tr>
                                        <td class="info-col-label">Periode</td>
                                        <td style="width: 8px;">:</td>
                                        <td style="font-weight: bold;">{{ $period }}</td>
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
                                        <td>{{ $user_session->fname ?? ($user_session->username ?? (auth()->user()->fname ?? 'User')) }}</td>
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

        // Leave a safety margin to prevent overflowing A4 height
        const maxPageHeight = pageHeightPx - 20;

        const headerSectionHtml = rawSource.querySelector(".header-section").outerHTML;
        const rowLabelsHtml = rawSource.querySelector(".row-labels").outerHTML;

        function createNewPage() {
            const page = document.createElement("div");
            page.className = "page-a4";
            page.innerHTML = `
                <div class="page-header-container">
                    ${headerSectionHtml}
                    ${rowLabelsHtml}
                </div>
                <div class="page-content" style="margin-top: 3px;"></div>
            `;
            reportWrapper.appendChild(page);
            return page;
        }

        let currentPage = createNewPage();
        let currentContent = currentPage.querySelector(".page-content");

        journals.forEach((journal) => {
            let currentJournalBlock = document.createElement("div");
            currentJournalBlock.className = journal.className;
            currentJournalBlock.innerHTML = journal.innerHTML;
            currentContent.appendChild(currentJournalBlock);

            if (currentPage.offsetHeight > maxPageHeight) {
                const blockCount = currentContent.querySelectorAll(".journal-block").length;
                if (blockCount > 1) {
                    currentContent.removeChild(currentJournalBlock);
                    currentPage = createNewPage();
                    currentContent = currentPage.querySelector(".page-content");

                    currentJournalBlock = document.createElement("div");
                    currentJournalBlock.className = journal.className;
                    currentJournalBlock.innerHTML = journal.innerHTML;
                    currentContent.appendChild(currentJournalBlock);
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

        // Apply strict height class to lock A4 size and set final page numbers
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
