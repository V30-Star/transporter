<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Uang Kasir</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;700&family=IBM+Plex+Sans:wght@400;500;700&family=Source+Serif+4:opsz,wght@8..60,700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'IBM Plex Sans', Arial, sans-serif;
            font-size: 10px;
            color: #0f172a;
            background: #f1f5f9;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .page-a4 {
            width: 297mm;
            min-height: 210mm;
            margin: 30px auto;
            padding: 12mm 18mm;
            background: #fff;
            box-shadow: 0 10px 25px rgba(15, 23, 42, .08);
            border-radius: 4px;
            position: relative;
        }
        .page-a4-strict { height: 210mm; overflow: hidden; }
        .header-row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px; }
        .comp-name { font-size: 18px; font-weight: 700; font-style: italic; }
        .comp-city { margin-top: 1px; font-size: 11px; color: #475569; }
        .title-so { color: #cc0000; font: 700 18px 'Source Serif 4', Georgia, serif; text-align: right; text-transform: uppercase; }
        .customer-container { border: 1px solid #000; border-radius: 8px; padding: 6px 12px; margin: 4px 0 8px; }
        .info-columns { display: flex; align-items: stretch; gap: 15px; }
        .info-column { flex: 1; }
        .info-column:first-child { padding-right: 15px; border-right: 1px solid #000; }
        .info-column:last-child { padding-left: 5px; }
        .info-col-table { width: 100%; border-collapse: collapse; font-size: 10px; }
        .info-col-table td { padding: 1px 2px; vertical-align: top; line-height: 1.4; }
        .info-col-label { width: 100px; font-weight: 600; color: #334155; }
        .report-section { margin-top: 8px; }
        .section-title { margin-bottom: 3px; font-weight: 700; text-transform: uppercase; }
        .branch-title { margin: 2px 0 4px; color: #0000cc; font-weight: 700; }
        .report-table { width: 100%; border-collapse: collapse; font-size: 8.5px; }
        .report-table th, .report-table td { padding: 3px 6px; vertical-align: middle; }
        .report-table thead th { border-top: 1px solid #000; border-bottom: 1px solid #000; text-align: left; text-transform: uppercase; }
        .report-table td:not(:first-child), .report-table th:not(:first-child) { text-align: right; font-family: 'IBM Plex Mono', monospace; font-variant-numeric: tabular-nums; }
        .report-table .label { font-family: 'IBM Plex Sans', Arial, sans-serif; text-align: left !important; }
        .sub-total { font-weight: 700; border-bottom: 3px double #000; }
        .grand-total { color: #0000cc; font-weight: 700; border-bottom: 3px double #000; }
        .grand-total td { padding-top: 5px; padding-bottom: 5px; }
        .empty { padding: 12px; text-align: center !important; color: #64748b; font-family: 'IBM Plex Sans', Arial, sans-serif !important; }
        .no-print {
            position: fixed; top: 15px; left: 15px; z-index: 1000;
            display: flex; align-items: center; gap: 10px;
            padding: 8px 16px; border: 1px solid rgba(226, 232, 240, .8); border-radius: 10px;
            background: rgba(255, 255, 255, .9); box-shadow: 0 4px 20px rgba(15, 23, 42, .15);
            backdrop-filter: blur(8px);
        }
        .print-button { padding: 8px 16px; border: 0; border-radius: 6px; background: #0f172a; color: #fff; cursor: pointer; font: 600 12px 'IBM Plex Sans', sans-serif; }
        .zoom-button { padding: 6px 12px; border: 0; border-radius: 4px; background: #6c757d; color: #fff; cursor: pointer; font-size: 16px; font-weight: 700; }
        #zoomLabel { min-width: 48px; color: #333; text-align: center; font-size: 13px; font-weight: 700; }
        .report-wrapper { transform-origin: top center; }
        @media print {
            body { background: #fff; margin: 0; }
            .no-print { display: none !important; }
            .page-a4 { width: 297mm; height: 210mm; min-height: 210mm; margin: 0 auto; padding: 12mm 18mm; box-shadow: none; border-radius: 0; page-break-after: always; overflow: hidden; }
            @page { size: A4 landscape; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="print-button" onclick="window.print()">Cetak Laporan</button>
        <button class="zoom-button" onclick="adjustZoom(-0.1)">−</button>
        <span id="zoomLabel">100%</span>
        <button class="zoom-button" onclick="adjustZoom(0.1)">+</button>
    </div>

    @php
        $companyProject = $company->fproject ?? 'THE GROSIR';
        $companyCity = $company->fcity ?? 'Pangkalpinang - Babel';
        $branchText = request()->has('branch_codes') ? implode(', ', (array) request('branch_codes')) : 'Semua';
    @endphp

    <div class="report-wrapper" id="reportWrapper">
        @forelse ($reports as $report)
            <section class="page-a4 page-a4-strict">
                <div class="header-row">
                    <div>
                        <div class="comp-name">{{ strtoupper($companyProject) }}</div>
                        <div class="comp-city">{{ $companyCity }}</div>
                    </div>
                    <div class="title-so">Laporan Uang Kasir<br><span style="font-size: 11px; color: #111;">Tanggal : {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</span></div>
                </div>

                <div class="customer-container">
                    <div class="info-columns">
                        <div class="info-column">
                            <table class="info-col-table">
                                <tr><td class="info-col-label">Kasir</td><td>:</td><td>{{ $kasir ?: 'Semua' }}</td></tr>
                                <tr><td class="info-col-label">Cabang</td><td>:</td><td>{{ $branchText }}</td></tr>
                                <tr><td class="info-col-label">Uang Tunai</td><td>:</td><td>{{ $onlyCash ? 'Ya' : 'Tidak' }}</td></tr>
                            </table>
                        </div>
                        <div class="info-column">
                            <table class="info-col-table">
                                <tr><td class="info-col-label">Tgl</td><td>:</td><td>{{ $printedAt->format('d/m/Y') }}</td></tr>
                                <tr><td class="info-col-label">Jam</td><td>:</td><td>{{ $printedAt->format('g:i:s A') }}</td></tr>
                                <tr><td class="info-col-label">Hal</td><td>:</td><td>{{ $loop->iteration }} / {{ $reports->count() }}</td></tr>
                                <tr><td class="info-col-label">Opr</td><td>:</td><td>{{ $operator }}</td></tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="report-section">
                    <div class="section-title">Rincian Transaksi Cabang</div>
                    <div class="branch-title">Cabang : {{ $report['branch_name'] }}</div>
                    <table class="report-table">
                        <tbody>
                            <tr><td class="label">Uang Penjualan :</td><td></td></tr>
                            <tr><td class="label">Kredit :</td><td>{{ number_format($report['credit'], 2, '.', ',') }}</td></tr>
                            <tr><td class="label">TUNAI :</td><td>{{ number_format($report['cash'], 2, '.', ',') }}</td></tr>
                            <tr class="sub-total"><td class="label">Total Uang :</td><td>{{ number_format($report['total_sales'], 2, '.', ',') }}</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="report-section">
                    <div class="section-title">Pelunasan :</div>
                    <table class="report-table">
                        <thead><tr><th>Account</th><th>Pelunasan Faktur</th><th>Pengeluaran Kas</th><th>Saldo</th></tr></thead>
                        <tbody>
                            @forelse ($report['accounts'] as $account)
                                <tr>
                                    <td class="label">{{ $account['account'] }}</td>
                                    <td>{{ number_format($account['pelunasan'], 2, '.', ',') }}</td>
                                    <td>{{ number_format($account['pengeluaran'], 2, '.', ',') }}</td>
                                    <td>{{ number_format($account['saldo'], 2, '.', ',') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="empty">Tidak ada data pelunasan.</td></tr>
                            @endforelse
                            <tr class="sub-total">
                                <td class="label">Total Pelunasan:</td>
                                <td>{{ number_format($report['total_pelunasan'], 2, '.', ',') }}</td>
                                <td>{{ number_format($report['total_pengeluaran'], 2, '.', ',') }}</td>
                                <td>{{ number_format($report['total_saldo'], 2, '.', ',') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="report-section">
                    <div class="section-title">Ringkasan Akhir (Grand Total)</div>
                    <table class="report-table">
                        <tbody>
                            <tr><td class="label">Grand Total Pelunasan :</td><td>{{ number_format($report['total_pelunasan'], 2, '.', ',') }} | {{ number_format($report['total_pengeluaran'], 2, '.', ',') }} | {{ number_format($report['total_saldo'], 2, '.', ',') }}</td></tr>
                            <tr><td class="label">GT. Penjualan Tunai :</td><td>{{ number_format($report['cash'], 2, '.', ',') }}</td></tr>
                            <tr class="grand-total"><td class="label">Grand Total {{ $report['branch_name'] }} :</td><td>{{ number_format($report['total_sales'], 2, '.', ',') }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
        @empty
            <section class="page-a4 page-a4-strict"><div class="empty">Tidak ada data ditemukan.</div></section>
        @endforelse
    </div>

    <script>
        let currentZoom = 1;
        function adjustZoom(delta) {
            currentZoom = Math.min(2, Math.max(.3, currentZoom + delta));
            document.getElementById('reportWrapper').style.transform = `scale(${currentZoom})`;
            document.getElementById('zoomLabel').textContent = Math.round(currentZoom * 100) + '%';
        }
    </script>
</body>
</html>
