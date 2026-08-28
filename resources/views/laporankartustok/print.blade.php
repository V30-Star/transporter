<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Stok</title>
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
            width: 85px;
        }

        .wh-header-title {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            margin: 4px 0 3px 0;
            padding-left: 2px;
        }

        /* --- TABLE HEADERS & ROWS (REKAP) --- */
        .rekap-header-labels,
        .rekap-row {
            display: grid;
            grid-template-columns: 8mm 28mm 1fr 18mm 24mm 24mm 30mm;
            gap: 1px;
            font-size: 8.5px;
            padding: 2px 6px;
            align-items: center;
        }

        /* --- TABLE HEADERS & ROWS (DETAIL) --- */
        .detail-header-labels,
        .detail-row {
            display: grid;
            grid-template-columns: 36mm 18mm 20mm 26mm 1fr 16mm 22mm 22mm 28mm;
            gap: 1px;
            font-size: 8.5px;
            padding: 2px 6px;
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
            letter-spacing: 0.3px;
            grid-template-rows: auto auto;
            align-items: center;
        }

        .header-span-rows {
            grid-row: 1 / span 2;
            display: flex;
            align-items: center;
        }

        .header-mutasi {
            grid-row: 1;
            text-align: center !important;
            padding: 1px 0;
            line-height: 1.2;
        }

        .rekap-mutasi { grid-column: 5 / 7; }
        .rekap-saldo-akhir { grid-column: 7; grid-row: 1 / span 2; justify-content: flex-end; text-align: right; }
        .rekap-masuk { grid-column: 5; grid-row: 2; text-align: center; }
        .rekap-keluar { grid-column: 6; grid-row: 2; text-align: center; }
        .rekap-col-1 { grid-column: 1; justify-content: center; text-align: center; }
        .rekap-col-2 { grid-column: 2; justify-content: flex-start; text-align: left; }
        .rekap-col-3 { grid-column: 3; justify-content: flex-start; text-align: left; }
        .rekap-col-satuan { grid-column: 4; grid-row: 1 / span 2; justify-content: flex-start; text-align: left; }

        .detail-mutasi { grid-column: 7 / 9; }
        .detail-saldo-akhir { grid-column: 9; grid-row: 1 / span 2; justify-content: flex-end; text-align: right; }
        .detail-masuk { grid-column: 7; grid-row: 2; text-align: center; }
        .detail-keluar { grid-column: 8; grid-row: 2; text-align: center; }
        .detail-col-1 { grid-column: 1; justify-content: flex-start; text-align: left; }
        .detail-col-2 { grid-column: 2; justify-content: center; text-align: center; }
        .detail-col-3 { grid-column: 3; justify-content: center; text-align: center; }
        .detail-col-4 { grid-column: 4; justify-content: flex-start; text-align: left; }
        .detail-col-5 { grid-column: 5; justify-content: flex-start; text-align: left; }
        .detail-col-satuan { grid-column: 6; grid-row: 1 / span 2; justify-content: flex-start; text-align: left; }

        .rekap-row,
        .detail-row {
            background-color: transparent;
            margin-bottom: 0px;
            color: #0f172a;
            padding: 2px 6px;
        }

        /* Alignment for Rekap Rows */
        .rekap-row > div:nth-child(1) { text-align: center; }
        .rekap-row > div:nth-child(2) { text-align: left; }
        .rekap-row > div:nth-child(3) { text-align: left; }
        .rekap-row > div:nth-child(4) { text-align: left; }
        .rekap-row > div:nth-child(5) { text-align: right; }
        .rekap-row > div:nth-child(6) { text-align: right; }
        .rekap-row > div:nth-child(7) { text-align: right; }

        /* Alignment for Detail Rows */
        .detail-row > div:nth-child(1) { text-align: left; }
        .detail-row > div:nth-child(2) { text-align: center; }
        .detail-row > div:nth-child(3) { text-align: center; }
        .detail-row > div:nth-child(4) { text-align: left; }
        .detail-row > div:nth-child(5) { text-align: left; }
        .detail-row > div:nth-child(6) { text-align: left; }
        .detail-row > div:nth-child(7) { text-align: right; }
        .detail-row > div:nth-child(8) { text-align: right; }
        .detail-row > div:nth-child(9) { text-align: right; }

        /* Fonts for Numbers & System Codes */
        .rekap-row > div:nth-child(2),
        .rekap-row > div:nth-child(5),
        .rekap-row > div:nth-child(6),
        .rekap-row > div:nth-child(7) {
            font-family: 'IBM Plex Mono', Courier, monospace;
            font-variant-numeric: tabular-nums;
        }

        .detail-row > div:nth-child(1),
        .detail-row > div:nth-child(2),
        .detail-row > div:nth-child(3),
        .detail-row > div:nth-child(4),
        .detail-row > div:nth-child(6),
        .detail-row > div:nth-child(7),
        .detail-row > div:nth-child(8) {
            font-family: 'IBM Plex Mono', Courier, monospace;
            font-variant-numeric: tabular-nums;
        }

        .group-title {
            font-weight: bold;
            margin-top: 3px;
            padding: 2px 6px;
            font-size: 8.5px;
            background-color: #f8fafc;
            border-bottom: 1px dashed #cbd5e1;
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
            margin-bottom: 1px;
        }

        .po-totals-panel-wrapper {
            margin-top: 15px;
            width: 100%;
            border-top: 1px solid #000000;
            padding-top: 10px;
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

        $branchText = request()->has('branch_codes') ? implode(', ', (array) request('branch_codes')) : 'Semua';
        
        $dateFromRaw = request('date_from');
        $dateToRaw = request('date_to');
        $dateFromFmt = $dateFromRaw ? date('d-m-Y', strtotime($dateFromRaw)) : date('01-01-Y');
        $dateToFmt = $dateToRaw ? date('d-m-Y', strtotime($dateToRaw)) : date('31-12-Y');
        $period = $dateFromFmt . ' s/d ' . $dateToFmt;

        $whText = request('warehouse') ?: 'Semua';
        $groupText = request('group_code') ?: 'Semua';
        $merekText = request('merek') ?: 'Semua';
        $selectedProductsRaw = trim((string) request('selected_products', ''));
        if ($selectedProductsRaw !== '') {
            $productRange = $selectedProductsRaw;
        } elseif (request('product_from') || request('product_to')) {
            $productRange = (request('product_from') ?: 'Awal') . ' s/d ' . (request('product_to') ?: 'Akhir');
        } else {
            $productRange = 'Semua';
        }

        $stockStatusText = match (request('stock_status', 'all')) {
            'not_zero' => 'Stok <> 0',
            'positive' => 'Stok > 0',
            'negative' => 'Stok < 0',
            'zero' => 'Stok = 0',
            'below_min' => 'Stok < Min Stok',
            default => 'Semua',
        };
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

        <a href="{{ route('laporankartustok.excel', request()->query()) }}"
            style="padding: 7px 12px; background-color: #22c55e; color: white; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; transition: background-color 0.2s;"
            onmouseover="this.style.backgroundColor='#16a34a'"
            onmouseout="this.style.backgroundColor='#22c55e'">
            📊 Excel
        </a>
    </div>

    {{-- Hidden Raw Data Container --}}
    <div id="raw-source" style="display: none;">
        {{-- Header Template --}}
        <div class="header-section">
            <div class="header-row">
                <div>
                    <div class="comp-name">{{ strtoupper($companyProject) }}</div>
                    @if(!empty($companyCity))
                        <div class="comp-city">{{ $companyCity }}</div>
                    @endif
                </div>
                <div>
                    <div class="title-so">Laporan Kartu Stok</div>
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
                                <td class="info-col-label">Gudang</td>
                                <td>:</td>
                                <td>{{ $whText }}</td>
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
                                <td class="info-col-label">Produk</td>
                                <td>:</td>
                                <td>{{ $productRange }}</td>
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
                                <td class="info-col-label">Status Stok</td>
                                <td>:</td>
                                <td>{{ $stockStatusText }}</td>
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

        @if ($mode === 'rekap')
            <div class="rekap-header-labels">
                <div class="header-span-rows rekap-col-1">No.</div>
                <div class="header-span-rows rekap-col-2">Kode Prd</div>
                <div class="header-span-rows rekap-col-3">Nama Produk</div>
                <div class="header-span-rows rekap-col-satuan">Satuan</div>
                <div class="header-mutasi rekap-mutasi">Mutasi</div>
                <div class="header-span-rows rekap-saldo-akhir">Saldo Akhir</div>
                <div class="rekap-masuk">Masuk</div>
                <div class="rekap-keluar">Keluar</div>
            </div>
        @else
            <div class="detail-header-labels">
                <div class="header-span-rows detail-col-1">Transaksi</div>
                <div class="header-span-rows detail-col-2">Kode Trans</div>
                <div class="header-span-rows detail-col-3">Tanggal</div>
                <div class="header-span-rows detail-col-4">No.Ref</div>
                <div class="header-span-rows detail-col-5">Supplier/Customer</div>
                <div class="header-span-rows detail-col-satuan">Satuan</div>
                <div class="header-mutasi detail-mutasi">Mutasi</div>
                <div class="header-span-rows detail-saldo-akhir">Saldo Akhir</div>
                <div class="detail-masuk">Masuk</div>
                <div class="detail-keluar">Keluar</div>
            </div>
        @endif

        @forelse ($rows->groupBy('fwhcode') as $whcode => $whRows)
            <div class="warehouse-group" data-whcode="{{ $whcode }}">
                @if ($mode === 'rekap')
                    @foreach ($whRows->groupBy(request('grouping', 'group') === 'merek' ? 'fmerekname' : 'fgroupname') as $groupName => $items)
                        <div class="journal-block group-title">
                            {{ $groupName ?: '-' }}
                        </div>

                        @foreach ($items as $row)
                            <div class="journal-block">
                                <div class="rekap-row">
                                    <div>{{ $loop->iteration }}</div>
                                    <div class="truncate" title="{{ $row->fprdcode }}">{{ $row->fprdcode }}</div>
                                    <div class="truncate" title="{{ $row->fprdname }}">{{ $row->fprdname }}</div>
                                    <div class="truncate">{{ $row->fsatuan }}</div>
                                    <div>{{ number_format((float) $row->qtymasukkecil, 2, ',', '.') }}</div>
                                    <div>{{ number_format((float) $row->qtykeluarkecil, 2, ',', '.') }}</div>
                                    <div>{{ number_format((float) $row->qtysaldokecil, 2, ',', '.') }} {{ $row->fsatuan }}</div>
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                @else
                    @foreach ($whRows->groupBy('fprdcode') as $prdcode => $items)
                        <div class="journal-block group-title">
                            {{ $prdcode }} - {{ $items->first()->fprdname ?? '' }}
                        </div>

                        @foreach ($items as $row)
                            @php
                                $code = strtoupper(trim((string) ($row->fstockmtcode ?? '')));
                                $id = $row->fstockmtid ?? null;
                                $isSaldoAwal = ($row->fstockmt === 'Saldo Awal') || in_array($code, ['SALDO AWAL', 'AWAL', 'SALDO']);
                            @endphp
                            @if (!$isSaldoAwal)
                                @php
                                    $hasTrx = !empty($id);
                                    $routeMap = [
                                        'SO' => ['view' => 'salesorder.view', 'edit' => 'salesorder.edit'],
                                        'SJ' => ['view' => 'suratjalan.view', 'edit' => 'suratjalan.edit'],
                                        'SRJ' => ['view' => 'suratjalan.view', 'edit' => 'suratjalan.edit'],
                                        'INV' => ['view' => 'invoice.view', 'edit' => 'invoice.edit'],
                                        'REJ' => ['view' => 'returpenjualan.view', 'edit' => 'returpenjualan.edit'],
                                        'RUJ' => ['view' => 'returpenjualan.view', 'edit' => 'returpenjualan.edit'],
                                        'PR' => ['view' => 'tr_prh.view', 'edit' => 'tr_prh.edit'],
                                        'PO' => ['view' => 'tr_poh.view', 'edit' => 'tr_poh.edit'],
                                        'PB' => ['view' => 'penerimaanbarang.view', 'edit' => 'penerimaanbarang.edit'],
                                        'BUY' => ['view' => 'fakturpembelian.view', 'edit' => 'fakturpembelian.edit'],
                                        'REB' => ['view' => 'returpembelian.view', 'edit' => 'returpembelian.edit'],
                                        'RUB' => ['view' => 'returpembelian.view', 'edit' => 'returpembelian.edit'],
                                        'ADJ' => ['view' => 'adjstock.view', 'edit' => 'adjstock.edit'],
                                        'MUT' => ['view' => 'mutasi.view', 'edit' => 'mutasi.edit'],
                                        'PBR' => ['view' => 'pemakaianbarang.view', 'edit' => 'pemakaianbarang.edit'],
                                        'PRD' => ['view' => 'assembling.view', 'edit' => 'assembling.edit'],
                                    ];
                                    $canClick = $hasTrx && isset($routeMap[$code]);
                                    $viewUrl = $canClick ? route($routeMap[$code]['view'], $id) : '#';
                                    $editUrl = $canClick ? route($routeMap[$code]['edit'], $id) : '#';
                                @endphp
                                <div class="journal-block">
                                    <div class="detail-row">
                                        <div class="truncate {{ in_array($code, ['REJ', 'RUJ', 'REB', 'RUB']) ? 'text-rej' : '' }}" title="{{ $row->fstockmt }}">
                                            @if ($canClick)
                                                <span class="trx-action-trigger" onclick="openTrxActionModal(event, '{{ $row->fstockmt }}', '{{ $viewUrl }}', '{{ $editUrl }}')">{{ $row->fstockmt }}</span>
                                            @else
                                                {{ $row->fstockmt }}
                                            @endif
                                        </div>
                                        <div>{{ $row->fstockmtcode }}</div>
                                        <div>{{ $row->fstockdate ? \Carbon\Carbon::parse($row->fstockdate)->format('d-m-Y') : '' }}</div>
                                        <div class="truncate" title="{{ $row->frefno }}">{{ $row->frefno }}</div>
                                        <div class="truncate" title="{{ $row->fsuppliername }}">{{ $row->fsuppliername }}</div>
                                        <div class="truncate">{{ $row->fsatuan }}</div>
                                        <div>{{ number_format((float) $row->qtymasukkecil, 2, ',', '.') }}</div>
                                        <div>{{ number_format((float) $row->qtykeluarkecil, 2, ',', '.') }}</div>
                                        <div>{{ number_format((float) $row->qtysaldokecil, 2, ',', '.') }} {{ $row->fsatuan }}</div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @endforeach
                @endif
            </div>
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
                    <div class="header-row">
                        <div>
                            <div class="comp-name">{{ strtoupper($companyProject) }}</div>
                            @if(!empty($companyCity))
                                <div class="comp-city">{{ $companyCity }}</div>
                            @endif
                        </div>
                        <div>
                            <div class="title-so">Laporan Kartu Stok</div>
                        </div>
                    </div>
                    <div class="customer-container">
                        <span class="customer-label">Parameter</span>
                        <div style="display: flex; justify-content: space-between; align-items: stretch; gap: 15px;">
                            <div style="flex: 1; padding-right: 15px; border-right: 1px solid #000;">
                                <table class="info-col-table">
                                    <tr>
                                        <td class="info-col-label">Cabang</td>
                                        <td style="width: 8px;">:</td>
                                        <td>{{ $branchText }}</td>
                                    </tr>
                                    <tr>
                                        <td class="info-col-label">Gudang</td>
                                        <td>:</td>
                                        <td>{{ $whText }}</td>
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
                                        <td class="info-col-label">Produk</td>
                                        <td>:</td>
                                        <td>{{ $productRange }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div style="flex: 1; padding-left: 5px;">
                                <table class="info-col-table">
                                    <tr>
                                        <td class="info-col-label">Periode</td>
                                        <td style="width: 8px;">:</td>
                                        <td>{{ $period }}</td>
                                    </tr>
                                    <tr>
                                        <td class="info-col-label">Status Stok</td>
                                        <td>:</td>
                                        <td>{{ $stockStatusText }}</td>
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

        const warehouseGroups = Array.from(rawSource.querySelectorAll(".warehouse-group"));
        if (warehouseGroups.length === 0) return;

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
        const rekapHeaderHtml = rawSource.querySelector(".rekap-header-labels")?.outerHTML || '';
        const detailHeaderHtml = rawSource.querySelector(".detail-header-labels")?.outerHTML || '';
        const poHeaderLabelsHtml = rekapHeaderHtml || detailHeaderHtml;

        function createNewPage(whcode) {
            const page = document.createElement("div");
            page.className = "page-a4";
            page.innerHTML = `
                <div class="page-header-container">
                    ${headerSectionHtml}
                    <div class="wh-header-title">GUDANG: ${whcode}</div>
                    ${poHeaderLabelsHtml}
                </div>
                <div class="page-content" style="margin-top: 3px;"></div>
            `;
            reportWrapper.appendChild(page);
            return page;
        }

        warehouseGroups.forEach((whGroup) => {
            const whcode = whGroup.getAttribute("data-whcode") || '';
            const whJournals = Array.from(whGroup.querySelectorAll(".journal-block"));
            if (whJournals.length === 0) return;

            let currentPage = createNewPage(whcode);
            let currentContent = currentPage.querySelector(".page-content");

            whJournals.forEach((journal) => {
                const journalClone = journal.cloneNode(true);
                currentContent.appendChild(journalClone);

                if (currentPage.offsetHeight > maxPageHeight) {
                    const blockCount = currentContent.querySelectorAll(".journal-block").length;
                    if (blockCount > 1) {
                        currentContent.removeChild(journalClone);
                        currentPage = createNewPage(whcode);
                        currentContent = currentPage.querySelector(".page-content");
                        currentContent.appendChild(journalClone);
                    }
                }
            });
        });

        // Add Totals Panel dynamically at the end of report
        const totalsPanelRaw = document.getElementById("po-totals-panel-raw");
        const allPagesInitial = reportWrapper.querySelectorAll(".page-a4");
        if (totalsPanelRaw && allPagesInitial.length > 0) {
            let lastPage = allPagesInitial[allPagesInitial.length - 1];
            const totalsClone = totalsPanelRaw.cloneNode(true);
            totalsClone.style.display = "block";
            totalsClone.removeAttribute("id");
            lastPage.appendChild(totalsClone);

            if (lastPage.offsetHeight > maxPageHeight) {
                lastPage.removeChild(totalsClone);
                const lastWhcode = warehouseGroups[warehouseGroups.length - 1]?.getAttribute("data-whcode") || '';
                lastPage = createNewPage(lastWhcode);
                lastPage.appendChild(totalsClone);
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
