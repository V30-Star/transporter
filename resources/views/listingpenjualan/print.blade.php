<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listing Penjualan</title>
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
            padding: 12mm 18mm;
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
            width: 90px;
        }

        /* --- JOURNAL HEADER STYLES (11 Kolom) --- */
        .sales-header-labels,
        .sales-header {
            display: grid;
            grid-template-columns: 10mm 26mm 18mm 1fr 22mm 24mm 20mm 24mm 20mm 20mm 26mm;
            gap: 1px;
            font-size: 8.5px;
            padding: 2px 6px;
            align-items: center;
        }

        .sales-header-labels {
            background-color: transparent;
            color: #000000;
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
            margin-bottom: 0px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .sales-header {
            background-color: transparent;
            color: #0f172a;
            font-weight: normal;
        }

        .text-rej,
        .text-rej div,
        .text-rej span {
            color: #ff0000 !important;
        }

        /* --- JOURNAL DETAIL STYLES (9 Kolom) --- */
        .sales-detail-labels,
        .sales-detail {
            display: grid;
            grid-template-columns: 24mm 1fr 25mm 25mm 18mm 14mm 22mm 14mm 26mm;
            gap: 1px;
            font-size: 8px;
            padding: 2px 6px;
            align-items: center;
        }

        .sales-detail-labels {
            font-weight: bold;
            color: #cc0000;
            background-color: transparent;
            border-bottom: 1px solid #000000;
            margin-top: 0px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .sales-detail {
            color: #cc0000;
            background-color: transparent;
        }

        /* Alignment for Header Columns */
        .sales-header-labels > div:nth-child(1),
        .sales-header > div:nth-child(1),
        .sales-header-labels > div:nth-child(3),
        .sales-header > div:nth-child(3) {
            text-align: center;
        }

        .sales-header-labels > div:nth-child(n+6),
        .sales-header > div:nth-child(n+6) {
            text-align: right;
        }

        /* Alignment for Detail Columns */
        .sales-detail-labels > div:nth-child(5),
        .sales-detail-labels > div:nth-child(7),
        .sales-detail-labels > div:nth-child(9),
        .sales-detail > div:nth-child(5),
        .sales-detail > div:nth-child(7),
        .sales-detail > div:nth-child(9) {
            text-align: right;
        }

        .sales-detail-labels > div:nth-child(6),
        .sales-detail-labels > div:nth-child(8),
        .sales-detail > div:nth-child(6),
        .sales-detail > div:nth-child(8) {
            text-align: center;
        }

        /* Fonts for Numbers & System Codes */
        .sales-header > div:nth-child(1),
        .sales-header > div:nth-child(2),
        .sales-header > div:nth-child(3),
        .sales-header > div:nth-child(n+6) {
            font-family: 'IBM Plex Mono', Courier, monospace;
            font-variant-numeric: tabular-nums;
        }

        .sales-detail > div:nth-child(1),
        .sales-detail > div:nth-child(3),
        .sales-detail > div:nth-child(4),
        .sales-detail > div:nth-child(5),
        .sales-detail > div:nth-child(7),
        .sales-detail > div:nth-child(8),
        .sales-detail > div:nth-child(9) {
            font-family: 'IBM Plex Mono', Courier, monospace;
            font-variant-numeric: tabular-nums;
        }

        .separator {
            border-bottom: 1px dotted #cbd5e1;
            margin: 3px 0;
            clear: both;
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
        }

        .po-totals-container {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            margin-top: 5px;
            padding-right: 5px;
        }

        .po-total-row {
            display: flex;
            justify-content: space-between;
            width: 320px;
            font-size: 9px;
            padding: 2px 0;
        }

        .grand-total-row {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 2px double #000;
            margin-top: 2px;
            padding-top: 4px;
            padding-bottom: 4px;
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
                padding: 12mm 18mm !important;
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

        $branchText = request()->has('branch_codes')
            ? implode(', ', (array) request()->input('branch_codes'))
            : 'Semua';
        $customerText = request('cust_from')
            ? '[' . request('cust_from') . ']' . (request('cust_to') ? ' s/d [' . request('cust_to') . ']' : '')
            : 'Semua';
        $productText = request('selected_products')
            ? str_replace(',', ', ', (string) request('selected_products'))
            : 'Semua';
        $groupText = request('group_code') ?: 'Semua';
        $merekText = request('merek_code') ?: 'Semua';
        $salesmanText = request('salesman') ?: 'Semua';
        $salesTypeText = match ((string) request('ftypesales', '')) {
            '1' => 'Uang Muka (UM)',
            '0' => 'Penjualan',
            default => 'Semua',
        };
        $fakturText = request()->has('belum_kirim') ? 'Belum Kirim' : 'Semua Faktur';
        $displayText = request('display_type') === 'rekap' ? 'Rekap' : 'Detail';
        $returText = request()->boolean('include_retur_penjualan') ? 'Ya' : 'Tidak';

        $dateFromFmt = request('date_from') ? date('d-m-Y', strtotime(request('date_from'))) : '...';
        $dateToFmt = request('date_to') ? date('d-m-Y', strtotime(request('date_to'))) : '...';
        $period = $dateFromFmt . ' s/d ' . $dateToFmt;
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

        {{-- Excel Download --}}
        <a href="{{ route('listingpenjualan.excel', request()->all()) }}"
            style="padding: 7px 12px; background-color: #22c55e; color: white; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; transition: background-color 0.2s;"
            onmouseover="this.style.backgroundColor='#16a34a'" onmouseout="this.style.backgroundColor='#22c55e'">
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
                    <div class="title-so">Listing Penjualan</div>
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
                                <td class="info-col-label">Customer</td>
                                <td>:</td>
                                <td>{{ $customerText }}</td>
                            </tr>
                            <tr>
                                <td class="info-col-label">Produk</td>
                                <td>:</td>
                                <td>{{ $productText }}</td>
                            </tr>
                            <tr>
                                <td class="info-col-label">Group Produk</td>
                                <td>:</td>
                                <td>{{ $groupText }}</td>
                            </tr>
                            <tr>
                                <td class="info-col-label">Merek</td>
                                <td>:</td>
                                <td>{{ $merekText }}</td>
                            </tr>
                            <tr>
                                <td class="info-col-label">Salesman</td>
                                <td>:</td>
                                <td>{{ $salesmanText }}</td>
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
                                <td class="info-col-label">Tipe</td>
                                <td>:</td>
                                <td>{{ $salesTypeText }}</td>
                            </tr>
                            <tr>
                                <td class="info-col-label">Faktur</td>
                                <td>:</td>
                                <td>{{ $fakturText }}</td>
                            </tr>
                            <tr>
                                <td class="info-col-label">Display</td>
                                <td>:</td>
                                <td>{{ $displayText }}</td>
                            </tr>
                            <tr>
                                <td class="info-col-label">Retur Penjualan</td>
                                <td>:</td>
                                <td>{{ $returText }}</td>
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

        {{-- Header Labels --}}
        <div class="sales-header-labels">
            <div>Cab.</div>
            <div>No.Faktur</div>
            <div>Tanggal</div>
            <div>Customer</div>
            <div>Salesman</div>
            <div class="text-right">Total Harga</div>
            <div class="text-right">Disc</div>
            <div class="text-right">Netto</div>
            <div class="text-right">PPN</div>
            <div class="text-right">Ongkos</div>
            <div class="text-right">Nilai Faktur</div>
        </div>

        {{-- Detail Labels --}}
        @if ($type == 'detail')
            <div class="sales-detail-labels">
                <div>Kode Barang</div>
                <div>Nama Barang</div>
                <div>No. SO</div>
                <div>No.Ref</div>
                <div class="text-right">Qty</div>
                <div class="text-center">Satuan</div>
                <div class="text-right">@Harga</div>
                <div class="text-center">Disc%</div>
                <div class="text-right">Jumlah</div>
            </div>
        @endif

        @php
            $totGross = 0;
            $totDisc = 0;
            $totNet = 0;
            $totPajak = 0;
            $totOngkos = 0;
            $totGrand = 0;
        @endphp

        @foreach ($groupedData as $fsono => $details)
            @php
                $h = $details->first();
                $ftrcode = strtoupper(trim((string) ($h->ftrcode ?? '')));
                $ftype = strtoupper(trim((string) ($h->ftype ?? $h->transaction_type ?? '')));
                $isReturn = in_array($ftrcode, ['REJ', 'RUJ'], true) || $ftrcode === 'RETUR PENJUALAN' || $ftype === 'RETUR PENJUALAN';
                $sign = $isReturn ? -1 : 1;
                $viewUrl = $isReturn ? route('returpenjualan.view', $h->ftranmtid) : route('invoice.view', $h->ftranmtid);
                $editUrl = $isReturn ? route('returpenjualan.edit', $h->ftranmtid) : route('invoice.edit', $h->ftranmtid);

                $totGross += (float) $h->famountgross * $sign;
                $totDisc += (float) $h->fdiscount * $sign;
                $totNet += (float) $h->famountsonet * $sign;
                $totPajak += (float) $h->famountpajak * $sign;
                $totOngkos += (float) $h->fongkosangkut * $sign;
                $totGrand += (float) $h->famountso * $sign;
            @endphp
            <div class="journal-block">
                <div class="sales-header {{ $isReturn ? 'text-rej' : '' }}">
                    <div class="truncate">{{ $h->fbranchcode }}</div>
                    <div class="truncate {{ $isReturn ? 'text-rej' : '' }}" title="{{ $h->fsono }}">
                        <span class="trx-action-trigger" onclick="openTrxActionModal(event, '{{ $h->fsono }}', '{{ $viewUrl }}', '{{ $editUrl }}')">{{ $h->fsono }}</span>
                    </div>
                    <div>{{ date('d-m-Y', strtotime($h->fsodate)) }}</div>
                    <div class="truncate" title="{{ $h->fcustomername }}">{{ $h->fcustomername }}</div>
                    <div class="truncate" title="{{ $h->fsalesmanname ?? '-' }}">{{ $h->fsalesmanname ?? '-' }}</div>
                    <div>{{ number_format(abs((float) $h->famountgross) * $sign, 2, ',', '.') }}</div>
                    <div>{{ number_format(abs((float) $h->fdiscount) * $sign, 2, ',', '.') }}</div>
                    <div>{{ number_format(abs((float) $h->famountsonet) * $sign, 2, ',', '.') }}</div>
                    <div>{{ number_format(abs((float) $h->famountpajak) * $sign, 2, ',', '.') }}</div>
                    <div>{{ number_format(abs((float) $h->fongkosangkut) * $sign, 2, ',', '.') }}</div>
                    <div>{{ number_format(abs((float) $h->famountso) * $sign, 2, ',', '.') }}</div>
                </div>

                @if ($type == 'detail')
                    @foreach ($details as $d)
                        <div class="sales-detail {{ $isReturn ? 'text-rej' : '' }}">
                            <div class="truncate">{{ $d->fprdcode }}</div>
                            <div class="truncate" title="{{ $d->fprdname }}">{{ $d->fprdname }}</div>
                            <div class="truncate">{{ $d->frefso ?? '-' }}</div>
                            <div class="truncate">{{ $d->frefsrj ?? '-' }}</div>
                            <div>{{ number_format((float) $d->fqty, 2, ',', '.') }}</div>
                            <div>{{ $d->fsatuan }}</div>
                            <div>{{ number_format((float) $d->fprice, 2, ',', '.') }}</div>
                            <div>{{ $d->fdisc }}</div>
                            <div>{{ number_format(abs((float) $d->famount) * $sign, 2, ',', '.') }}</div>
                        </div>
                    @endforeach
                @endif

                @if (!$loop->last)
                    <div class="separator"></div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Hidden Totals Panel Container --}}
    <div id="po-totals-panel-raw" style="display: none;">
        <div class="po-totals-panel-wrapper">
            <div class="end-of-report-inline">** END OF REPORT **</div>
            <div class="po-totals-container">
                <div class="po-total-row grand-total-row">
                    <span>GRAND TOTAL NILAI FAKTUR</span>
                    <span>Rp {{ number_format((float) $totGrand, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Screen Render Target --}}
    <div class="report-wrapper" id="reportWrapper">
        @if ($groupedData->isEmpty())
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
                            <div class="title-so">Listing Penjualan</div>
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
                                        <td class="info-col-label">Customer</td>
                                        <td>:</td>
                                        <td>{{ $customerText }}</td>
                                    </tr>
                                    <tr>
                                        <td class="info-col-label">Produk</td>
                                        <td>:</td>
                                        <td>{{ $productText }}</td>
                                    </tr>
                                    <tr>
                                        <td class="info-col-label">Group Produk</td>
                                        <td>:</td>
                                        <td>{{ $groupText }}</td>
                                    </tr>
                                    <tr>
                                        <td class="info-col-label">Merek</td>
                                        <td>:</td>
                                        <td>{{ $merekText }}</td>
                                    </tr>
                                    <tr>
                                        <td class="info-col-label">Salesman</td>
                                        <td>:</td>
                                        <td>{{ $salesmanText }}</td>
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
                                        <td class="info-col-label">Tipe</td>
                                        <td>:</td>
                                        <td>{{ $salesTypeText }}</td>
                                    </tr>
                                    <tr>
                                        <td class="info-col-label">Faktur</td>
                                        <td>:</td>
                                        <td>{{ $fakturText }}</td>
                                    </tr>
                                    <tr>
                                        <td class="info-col-label">Display</td>
                                        <td>:</td>
                                        <td>{{ $displayText }}</td>
                                    </tr>
                                    <tr>
                                        <td class="info-col-label">Retur Penjualan</td>
                                        <td>:</td>
                                        <td>{{ $returText }}</td>
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
        const salesHeaderLabelsHtml = rawSource.querySelector(".sales-header-labels").outerHTML;
        const salesDetailLabelsHtml = rawSource.querySelector(".sales-detail-labels")?.outerHTML || '';

        function createNewPage() {
            const page = document.createElement("div");
            page.className = "page-a4";
            page.innerHTML = `
                <div class="page-header-container">
                    ${headerSectionHtml}
                    ${salesHeaderLabelsHtml}
                    ${salesDetailLabelsHtml}
                </div>
                <div class="page-content" style="margin-top: 3px;"></div>
            `;
            reportWrapper.appendChild(page);
            return page;
        }

        let currentPage = createNewPage();
        let currentContent = currentPage.querySelector(".page-content");

        journals.forEach((journal) => {
            const salesHeader = journal.querySelector(".sales-header");
            const salesDetails = Array.from(journal.querySelectorAll(".sales-detail"));
            const separator = journal.querySelector(".separator");

            let currentJournalBlock = document.createElement("div");
            currentJournalBlock.className = "journal-block";
            currentContent.appendChild(currentJournalBlock);
            currentJournalBlock.appendChild(salesHeader.cloneNode(true));

            if (currentPage.offsetHeight > maxPageHeight) {
                const blockCount = currentContent.querySelectorAll(".journal-block").length;
                if (blockCount > 1) {
                    currentContent.removeChild(currentJournalBlock);
                    currentPage = createNewPage();
                    currentContent = currentPage.querySelector(".page-content");

                    currentJournalBlock = document.createElement("div");
                    currentJournalBlock.className = "journal-block";
                    currentContent.appendChild(currentJournalBlock);
                    currentJournalBlock.appendChild(salesHeader.cloneNode(true));
                }
            }

            salesDetails.forEach((detail) => {
                const detailClone = detail.cloneNode(true);
                currentJournalBlock.appendChild(detailClone);

                if (currentPage.offsetHeight > maxPageHeight) {
                    const detailCount = currentJournalBlock.querySelectorAll(".sales-detail").length;
                    const blockCount = currentContent.querySelectorAll(".journal-block").length;

                    if (blockCount > 1 || detailCount > 1) {
                        currentJournalBlock.removeChild(detailClone);

                        currentPage = createNewPage();
                        currentContent = currentPage.querySelector(".page-content");

                        currentJournalBlock = document.createElement("div");
                        currentJournalBlock.className = "journal-block";
                        currentContent.appendChild(currentJournalBlock);

                        const headerClone = salesHeader.cloneNode(true);
                        const firstChildDiv = headerClone.firstElementChild;
                        if (firstChildDiv) {
                            firstChildDiv.textContent = firstChildDiv.textContent + " (Lanjutan)";
                        }
                        currentJournalBlock.appendChild(headerClone);
                        currentJournalBlock.appendChild(detailClone);
                    }
                }
            });

            if (separator) {
                const separatorClone = separator.cloneNode(true);
                currentJournalBlock.appendChild(separatorClone);

                if (currentPage.offsetHeight > maxPageHeight) {
                    currentJournalBlock.removeChild(separatorClone);
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