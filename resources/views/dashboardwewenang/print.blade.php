<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Wewenang User</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;700&family=IBM+Plex+Sans:wght@400;500;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'IBM Plex Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 9px;
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
            padding: 12mm 12mm;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
            position: relative;
            box-sizing: border-box;
            border-radius: 4px;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 6px;
        }

        .comp-name {
            font-size: 16px;
            font-weight: bold;
            font-style: italic;
            color: #0f172a;
        }

        .comp-city {
            font-size: 10px;
            color: #475569;
            margin-top: 1px;
        }

        .title-doc {
            font-size: 15px;
            color: #0000ff;
            text-decoration: underline;
            font-weight: bold;
            text-align: right;
            text-transform: uppercase;
        }

        .customer-container {
            border: 1px solid #000;
            border-radius: 6px;
            padding: 6px 10px;
            width: 100%;
            position: relative;
            margin-top: 4px;
            margin-bottom: 8px;
        }

        .info-col-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
        }

        .info-col-table td {
            padding: 1px 2px;
            vertical-align: top;
            line-height: 1.35;
        }

        .info-col-label {
            font-weight: 600;
            color: #334155;
            width: 90px;
        }

        /* --- TABLE STYLES --- */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
            margin-top: 5px;
        }

        .report-table thead tr {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            background-color: transparent;
        }

        .report-table th {
            padding: 4px 4px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            text-align: left;
        }

        .report-table td {
            padding: 3px 4px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .font-mono {
            font-family: 'IBM Plex Mono', Courier, monospace;
            font-variant-numeric: tabular-nums;
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
            padding: 7px 14px;
            border-radius: 6px;
            cursor: pointer;
            border: none;
            font-weight: 600;
            font-family: 'IBM Plex Sans', sans-serif;
            font-size: 11px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .print-button:hover {
            background-color: #000000;
        }

        .end-of-report {
            margin-top: 10px;
            border-top: 1px solid #000;
            padding-top: 6px;
            text-align: center;
            font-weight: bold;
            font-size: 8.5px;
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
                width: 100%;
                margin: 0 auto !important;
                padding: 10mm 10mm !important;
                box-shadow: none !important;
                border-radius: 0;
            }

            .no-print {
                display: none !important;
            }

            @page {
                size: A4 portrait;
                margin: 8mm;
            }
        }
    </style>
</head>

<body>
    @php
        $companySetting = company_setting();
        $companyProject = $companySetting->fproject ?? 'PT. M-Trade';
        $companyCity = $companySetting->fcity ?? '';
    @endphp

    <div class="no-print">
        <button class="print-button" onclick="window.print()">🖨️ Cetak / PDF</button>
        <button onclick="adjustZoom(-0.1)"
            style="padding: 5px 10px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: bold;">
            −
        </button>
        <span id="zoomLabel"
            style="min-width: 44px; text-align: center; font-size: 12px; font-weight: bold; color: #333;">
            100%
        </span>
        <button onclick="adjustZoom(0.1)"
            style="padding: 5px 10px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: bold;">
            +
        </button>
        <a href="{{ route('dashboardwewenang.excel', request()->query()) }}"
            style="padding: 6px 12px; background-color: #10b981; color: white; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 11px; display: inline-flex; align-items: center; justify-content: center;">
            📊 Export Excel
        </a>
    </div>

    <div class="page-a4" id="reportWrapper">
        <div class="header-row">
            <div>
                <div class="comp-name">{{ strtoupper($companyProject) }}</div>
                @if(!empty($companyCity))
                    <div class="comp-city">{{ $companyCity }}</div>
                @endif
            </div>
            <div>
                <div class="title-doc">Laporan Wewenang User</div>
            </div>
        </div>

        <div class="customer-container">
            <div style="display: flex; justify-content: space-between; align-items: stretch; gap: 15px;">
                <div style="flex: 1; padding-right: 10px; border-right: 1px solid #000;">
                    <table class="info-col-table">
                        <tr>
                            <td class="info-col-label">Parameter Menu</td>
                            <td style="width: 8px;">:</td>
                            <td><strong>{{ $selectedMenuKey === 'ALL' ? 'Semua Menu' : $selectedMenuKey }}</strong></td>
                        </tr>
                    </table>
                </div>
                <div style="flex: 1; padding-left: 5px;">
                    <table class="info-col-table">
                        <tr>
                            <td class="info-col-label">Tanggal Cetak</td>
                            <td style="width: 8px;">:</td>
                            <td>{{ date('d-m-Y') }} Jam: {{ date('H:i') }}</td>
                        </tr>
                        <tr>
                            <td class="info-col-label">Operator</td>
                            <td>:</td>
                            <td>{{ $user_session->fname ?? ($user_session->username ?? 'admin') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 20px; text-align: center;">No</th>
                    <th style="width: 70px;">Id</th>
                    <th style="width: 110px;">Nama Lengkap</th>
                    <th>List Menu</th>
                    <th style="width: 38px; text-align: center;">View</th>
                    <th style="width: 42px; text-align: center;">Tambah</th>
                    <th style="width: 38px; text-align: center;">Edit</th>
                    <th style="width: 40px; text-align: center;">Delete</th>
                    <th style="width: 38px; text-align: center;">Print</th>
                    <th style="width: 44px; text-align: center;">Approve</th>
                    <th style="width: 45px; text-align: center;">Lain-lain</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $i => $row)
                    <tr>
                        <td style="text-align: center;" class="font-mono">{{ $i + 1 }}</td>
                        <td style="font-weight: bold;">{{ $row['user_id'] }}</td>
                        <td>{{ $row['user_name'] }}</td>
                        <td>{{ $row['menu_name'] }}</td>

                        <td style="text-align: center; font-weight: bold; color: {{ $row['can_view'] ? '#16a34a' : '#cbd5e1' }};">
                            {{ $row['can_view'] ? 'V' : '-' }}
                        </td>
                        <td style="text-align: center; font-weight: bold; color: {{ $row['can_create'] ? '#16a34a' : '#cbd5e1' }};">
                            {{ $row['can_create'] ? 'V' : '-' }}
                        </td>
                        <td style="text-align: center; font-weight: bold; color: {{ $row['can_update'] ? '#16a34a' : '#cbd5e1' }};">
                            {{ $row['can_update'] ? 'V' : '-' }}
                        </td>
                        <td style="text-align: center; font-weight: bold; color: {{ $row['can_delete'] ? '#16a34a' : '#cbd5e1' }};">
                            {{ $row['can_delete'] ? 'V' : '-' }}
                        </td>
                        <td style="text-align: center; font-weight: bold; color: {{ $row['can_print'] ? '#16a34a' : '#cbd5e1' }};">
                            {{ $row['can_print'] ? 'V' : '-' }}
                        </td>
                        <td style="text-align: center; font-weight: bold; color: {{ $row['can_approve'] ? '#16a34a' : '#cbd5e1' }};">
                            {{ $row['can_approve'] ? 'V' : '-' }}
                        </td>
                        <td style="text-align: center; font-weight: bold; color: {{ $row['can_other'] ? '#16a34a' : '#cbd5e1' }};">
                            {{ $row['can_other'] ? 'V' : '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" style="text-align: center; padding: 15px; color: #64748b;">
                            Tidak ada data wewenang user.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="end-of-report">
            Total Baris: {{ count($rows) }} | ** END OF REPORT **
        </div>
    </div>

    <script>
        let currentZoom = 1.0;
        function adjustZoom(delta) {
            currentZoom = Math.min(2.0, Math.max(0.4, currentZoom + delta));
            const el = document.getElementById('reportWrapper');
            el.style.transform = `scale(${currentZoom})`;
            el.style.transformOrigin = 'top center';
            document.getElementById('zoomLabel').textContent = Math.round(currentZoom * 100) + '%';
        }
    </script>
</body>

</html>
