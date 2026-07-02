<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #000;
            background-color: #eee;
        }
        .page-a4 {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: white;
            padding: 15mm;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            position: relative;
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
        .filter-info { font-size: 10px; color: #333; margin-bottom: 5px; }
        .info-tambahan {
            position: absolute;
            top: 0;
            right: 0;
            font-size: 10px;
            color: #333;
            text-align: left;
            line-height: 1.4;
        }
        .info-label { font-weight: bold; display: inline-block; width: 45px; }
        .supplier-info-kiri {
            position: absolute;
            top: 1mm;
            left: 0mm;
            font-size: 10px;
            color: #333;
            text-align: left;
            line-height: 1.4;
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
        .zoom-button {
            padding: 6px 12px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
        #zoomLabel {
            min-width: 48px;
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            color: #333;
            align-self: center;
        }
        .row-labels,
        .row-data,
        .row-total {
            display: grid;
            grid-template-columns: 7mm 10mm 24mm 15mm 15mm 12mm 18mm 13mm 12mm 12mm 12mm 12mm 12mm;
            gap: 1px;
            font-size: 7px;
            padding: 2px;
        }
        .row-labels {
            background-color: #f0f0f0;
            border: 1px solid #000;
            font-weight: bold;
        }
        .row-data { background-color: #fff; }
        .row-total {
            font-weight: bold;
            background-color: #fff;
            border-top: 1px solid #000;
            color: #c00;
        }
        .customer-heading {
            font-weight: bold;
            color: #c00;
            background-color: #fff;
            border: 1px solid #000;
            margin-top: 6px;
            padding: 3px;
            font-size: 8px;
        }
        .separator { border-bottom: 1px solid #000; margin: 4px 0; clear: both; }
        .right { text-align: right; }
        .center { text-align: center; }
        .truncate { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .grand-total-section { margin-top: 20px; padding-top: 10px; }
        .grand-total-header {
            display: grid;
            grid-template-columns: 96mm 13mm 13mm 13mm 13mm 13mm 14mm;
            gap: 1px;
            font-size: 7px;
            font-weight: bold;
            padding: 4px 2px;
            color: black;
            border-top: 1px solid #000;
        }
        @media print {
            body { background-color: white !important; margin: 0; padding: 0; }
            .page-a4 {
                width: 210mm;
                min-height: 297mm;
                margin: 0 auto !important;
                padding: 15mm !important;
                box-shadow: none !important;
                page-break-after: always;
            }
            .no-print { display: none !important; }
            @page { size: A4 portrait; margin: 0; }
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
        $grand = ['undue' => 0, 'd30' => 0, 'd60' => 0, 'd90' => 0, 'd91' => 0, 'd1y' => 0];
        $branchText = request()->has('branch_codes') ? implode(', ', (array) request()->input('branch_codes')) : 'Semua';
        $customerText = trim((string) request('cust_from')) !== '' || trim((string) request('cust_to')) !== ''
            ? (request('cust_from') ?: 'Awal') . ' s/d ' . (request('cust_to') ?: 'Akhir')
            : 'Semua';
    @endphp

    <div class="report-wrapper" id="reportWrapper">
        <div class="page-a4">
            <div class="header-section">
                <div class="supplier-info-kiri">
                    Customer: {{ $customerText }}
                    <br>Cabang: {{ $branchText }}
                </div>
                <h2>{{ $title }}</h2>
                <div class="filter-info">
                    Periode:
                    {{ request('date_from') ? \Carbon\Carbon::parse(request('date_from'))->format('d/m/Y') : '...' }}
                    s/d
                    {{ request('date_to') ? \Carbon\Carbon::parse(request('date_to'))->format('d/m/Y') : '...' }}
                    @if ($request->input('mode') === 'due')
                        | Jatuh Tempo s.d {{ \Carbon\Carbon::parse($request->input('due_date_to'))->format('d/m/Y') }}
                    @endif
                </div>
                <div class="info-tambahan">
                    <div><span class="info-label">Hal</span>: 1 / 1</div>
                    <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                    <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                    <div><span class="info-label">Opr</span>: {{ auth()->user()->fname ?? 'User' }}</div>
                </div>
            </div>

            <div class="row-labels">
                <div>No.</div>
                <div>Cab.</div>
                <div>No.Faktur</div>
                <div>Tanggal</div>
                <div>Jatuh Tempo</div>
                <div class="right">Umur(Hr)</div>
                <div class="right">Nilai Faktur</div>
                <div class="right">Un Due</div>
                <div class="right">0-30 Hari</div>
                <div class="right">31-60 Hari</div>
                <div class="right">61-90 Hari</div>
                <div class="right">91-1 Tahun</div>
                <div class="right">&gt;1 Tahun</div>
            </div>

            @forelse ($rows->groupBy('fcustno') as $custCode => $items)
                @php
                    $name = $items->first()->fcustname ?: $custCode;
                    $tot = [
                        'undue' => $items->sum('varundue'),
                        'd30' => $items->sum('var30hari'),
                        'd60' => $items->sum('var60hari'),
                        'd90' => $items->sum('ppvar90hari'),
                        'd91' => $items->sum('ppvar91hari'),
                        'd1y' => $items->sum('ppvar1tahun'),
                    ];
                    foreach ($tot as $key => $value) $grand[$key] += $value;
                @endphp

                <div class="customer-heading">{{ $custCode }} - {{ $name }}</div>
                @foreach ($items as $index => $row)
                    <div class="row-data">
                        <div class="center">{{ $index + 1 }}</div>
                        <div class="truncate">{{ $row->fbranchcode }}</div>
                        <div class="truncate">{{ $row->fsono }}</div>
                        <div>{{ $row->fsodate ? \Carbon\Carbon::parse($row->fsodate)->format('d/m/Y') : '' }}</div>
                        <div>{{ $row->fjatuhtempo ? \Carbon\Carbon::parse($row->fjatuhtempo)->format('d/m/Y') : '' }}</div>
                        <div class="right">{{ $row->mu }}</div>
                        <div class="right">{{ number_format((float) $row->famountso, 2, ',', '.') }}</div>
                        <div class="right">{{ number_format((float) $row->varundue, 2, ',', '.') }}</div>
                        <div class="right">{{ number_format((float) $row->var30hari, 2, ',', '.') }}</div>
                        <div class="right">{{ number_format((float) $row->var60hari, 2, ',', '.') }}</div>
                        <div class="right">{{ number_format((float) $row->ppvar90hari, 2, ',', '.') }}</div>
                        <div class="right">{{ number_format((float) $row->ppvar91hari, 2, ',', '.') }}</div>
                        <div class="right">{{ number_format((float) $row->ppvar1tahun, 2, ',', '.') }}</div>
                    </div>
                @endforeach
                <div class="row-total">
                    <div style="grid-column: span 7;" class="right">Total {{ $name }}</div>
                    <div class="right">{{ number_format((float) $tot['undue'], 2, ',', '.') }}</div>
                    <div class="right">{{ number_format((float) $tot['d30'], 2, ',', '.') }}</div>
                    <div class="right">{{ number_format((float) $tot['d60'], 2, ',', '.') }}</div>
                    <div class="right">{{ number_format((float) $tot['d90'], 2, ',', '.') }}</div>
                    <div class="right">{{ number_format((float) $tot['d91'], 2, ',', '.') }}</div>
                    <div class="right">{{ number_format((float) $tot['d1y'], 2, ',', '.') }}</div>
                </div>
                <div class="separator"></div>
            @empty
                <div style="margin-top: 30px; text-align: center; font-size: 12px; color: #666;">Tidak ada data ditemukan.</div>
            @endforelse

            <div class="grand-total-section">
                <div class="grand-total-header">
                    <div class="right">GRAND TOTAL</div>
                    <div class="right">{{ number_format((float) $grand['undue'], 2, ',', '.') }}</div>
                    <div class="right">{{ number_format((float) $grand['d30'], 2, ',', '.') }}</div>
                    <div class="right">{{ number_format((float) $grand['d60'], 2, ',', '.') }}</div>
                    <div class="right">{{ number_format((float) $grand['d90'], 2, ',', '.') }}</div>
                    <div class="right">{{ number_format((float) $grand['d91'], 2, ',', '.') }}</div>
                    <div class="right">{{ number_format((float) $grand['d1y'], 2, ',', '.') }}</div>
                </div>
            </div>

            <div style="text-align: center; margin-top: 10px; border-top: 1px solid #000; padding-top: 20px; font-weight: bold; font-size: 8px; color: #555; text-transform: uppercase; letter-spacing: 1px;">
                ** End of Report **
            </div>
        </div>
    </div>
</body>

</html>

<script>
    let currentZoom = 1.0;
    function adjustZoom(delta) {
        currentZoom = Math.min(2.0, Math.max(0.3, currentZoom + delta));
        document.getElementById('reportWrapper').style.transform = `scale(${currentZoom})`;
        document.getElementById('reportWrapper').style.transformOrigin = 'top center';
        document.getElementById('zoomLabel').textContent = Math.round(currentZoom * 100) + '%';
    }
</script>
