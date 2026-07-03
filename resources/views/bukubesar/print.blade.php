<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; color: #000; background-color: #eee; }
        .page-a4 { width: 210mm; min-height: 297mm; margin: 20px auto; background: white; padding: 15mm; box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); position: relative; }
        .header-section { position: relative; margin-bottom: 15px; text-align: center; padding-bottom: 25px; }
        .header-section h2 { font-size: 18px; margin-bottom: 8px; font-weight: bold; text-transform: uppercase; color: #c00; }
        .filter-info { font-size: 10px; color: #333; margin-bottom: 5px; }
        .info-tambahan { position: absolute; top: 0; right: 0; font-size: 10px; color: #333; text-align: left; line-height: 1.4; }
        .info-label { font-weight: bold; display: inline-block; width: 45px; }
        .supplier-info-kiri { position: absolute; top: 1mm; left: 0mm; font-size: 10px; color: #333; text-align: left; line-height: 1.4; }
        .no-print { position: fixed; top: 10px; left: 10px; display: flex; gap: 8px; z-index: 1000; }
        .print-button { background-color: #3b82f6; color: white; padding: 10px 20px; border-radius: 5px; cursor: pointer; border: none; font-weight: bold; }
        .zoom-button { padding: 6px 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; }
        #zoomLabel { min-width: 48px; text-align: center; font-size: 13px; font-weight: bold; color: #333; align-self: center; }
        .ledger-labels, .ledger-row, .ledger-total { display: grid; grid-template-columns: 25mm 32mm 38mm 25mm 25mm 28mm; gap: 1px; font-size: 8px; padding: 3px; }
        .ledger-labels { background-color: #f0f0f0; border: 1px solid #000; font-weight: bold; }
        .ledger-row { background-color: #fff; }
        .ledger-total { font-weight: bold; background-color: #fff; border-top: 1px solid #000; color: #c00; }
        .group-heading { font-weight: bold; color: #000; background-color: #f0f0f0; border: 1px solid #000; margin-top: 6px; padding: 3px; font-size: 8px; }
        .separator { border-bottom: 1px solid #000; margin: 4px 0; clear: both; }
        .right { text-align: right; }
        .truncate { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        @media print {
            body { background-color: white !important; margin: 0; padding: 0; }
            .page-a4 { width: 210mm; min-height: 297mm; margin: 0 auto !important; padding: 15mm !important; box-shadow: none !important; page-break-after: always; }
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
        $branchText = request()->has('branch_codes') ? implode(', ', (array) request()->input('branch_codes')) : 'Semua';
        $accountText = trim((string) request('account_from')) !== '' || trim((string) request('account_to')) !== ''
            ? (request('account_from') ?: 'Awal') . ' s/d ' . (request('account_to') ?: 'Akhir')
            : 'Semua';
    @endphp

    <div class="report-wrapper" id="reportWrapper">
        <div class="page-a4">
            <div class="header-section">
                <div class="supplier-info-kiri">
                    Account: {{ $accountText }}
                    <br>Cabang: {{ $branchText }}
                </div>
                <h2>{{ $title }}</h2>
                <div class="filter-info">
                    Periode:
                    {{ request('date_from') ? \Carbon\Carbon::parse(request('date_from'))->format('d/m/Y') : '...' }}
                    s/d
                    {{ request('date_to') ? \Carbon\Carbon::parse(request('date_to'))->format('d/m/Y') : '...' }}
                    | Urut Berdasarkan: No.Invoice
                </div>
                <div class="info-tambahan">
                    <div><span class="info-label">Hal</span>: 1 / 1</div>
                    <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                    <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                    <div><span class="info-label">Opr</span>: {{ auth()->user()->fname ?? 'User' }}</div>
                </div>
            </div>

            <div class="ledger-labels">
                <div>Tanggal</div>
                <div>Jurnal</div>
                <div>No.Invoice</div>
                <div class="right">Debet</div>
                <div class="right">Kredit</div>
                <div class="right">Saldo</div>
            </div>

            @forelse ($rows->groupBy('faccount') as $account => $items)
                @php
                    $accountName = $items->first()->faccname ?: $account;
                    $debit = $items->sum('famountdb');
                    $credit = $items->sum('famountcr');
                    $saldo = (float) ($items->last()->pplasaldoakhir ?? 0);
                @endphp
                <div class="group-heading">{{ $account }} - {{ $accountName }}</div>
                @foreach ($items as $row)
                    <div class="ledger-row">
                        <div>{{ $row->fjurnaltgl ? \Carbon\Carbon::parse($row->fjurnaltgl)->format('d/m/Y') : '' }}</div>
                        <div class="truncate">{{ $row->fjurnalno }}</div>
                        <div class="truncate">{{ $row->faccountno }}</div>
                        <div class="right">{{ number_format((float) $row->famountdb, 2, ',', '.') }}</div>
                        <div class="right">{{ number_format((float) $row->famountcr, 2, ',', '.') }}</div>
                        <div class="right">{{ number_format((float) $row->plasaldosubacc, 2, ',', '.') }}</div>
                    </div>
                @endforeach
                <div class="ledger-total">
                    <div style="grid-column: span 3;" class="right">Saldo Akhir {{ $accountName }}</div>
                    <div class="right">{{ number_format((float) $debit, 2, ',', '.') }}</div>
                    <div class="right">{{ number_format((float) $credit, 2, ',', '.') }}</div>
                    <div class="right">{{ number_format((float) $saldo, 2, ',', '.') }}</div>
                </div>
                <div class="separator"></div>
            @empty
                <div style="margin-top: 30px; text-align: center; font-size: 12px; color: #666;">Tidak ada data ditemukan.</div>
            @endforelse

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
