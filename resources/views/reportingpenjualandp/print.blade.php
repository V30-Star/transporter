<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan DP</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #111827; background: #f3f4f6; }
        .page { width: 210mm; margin: 20px auto; background: white; padding: 15mm; box-shadow: 0 8px 20px rgba(0,0,0,.08); }
        .actions { position: fixed; top: 12px; left: 12px; display: flex; gap: 8px; }
        .actions button, .actions a { border: 0; border-radius: 6px; padding: 8px 12px; background: #111827; color: white; text-decoration: none; cursor: pointer; font-size: 12px; }
        h1, h2 { margin: 0; text-align: center; }
        h1 { font-size: 16px; }
        h2 { font-size: 14px; margin-bottom: 10px; }
        .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 20px; margin: 12px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 4px 6px; border-bottom: 1px solid #e5e7eb; }
        th { border-top: 1px solid #111827; border-bottom: 1px solid #111827; text-align: left; }
        .num { text-align: right; font-variant-numeric: tabular-nums; }
        .detail { background: #f9fafb; color: #dc2626; }
        .grand { font-weight: bold; background: #111827; color: white; }
        .end { text-align: center; margin-top: 18px; font-weight: bold; }
        @media print { body { background: white; } .page { width: auto; margin: 0; padding: 0; box-shadow: none; } .actions { display: none; } }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print()">Print / PDF</button>
        <a href="{{ route('reportingpenjualandp.excel', request()->query()) }}">Export Excel</a>
    </div>

    <div class="page">
        <h1>PT. UTALIYA</h1>
        <h2>Laporan Penjualan DP</h2>

        <div class="meta">
            <div>Cabang: {{ $filters['branch_label'] }}</div>
            <div>Tg: {{ date('d/m/Y') }} Jam: {{ date('H:i') }} Hal: 1</div>
            <div>Periode: {{ $filters['date_from'] }} s/d {{ $filters['date_to'] }}</div>
            <div>Opr: {{ $user_session->fuserid ?? $user_session->username ?? $user_session->name ?? 'User' }}</div>
            <div>Customer: {{ $filters['customer_label'] }}</div>
            <div>Sisa DP: {{ $filters['sisa_dp_label'] }} | Tipe: {{ $filters['report_type'] }}</div>
        </div>

        @php $totalDp = 0; $totalUsed = 0; $totalRemain = 0; @endphp
        <table>
            <thead>
                <tr>
                    <th>Cabang</th>
                    <th>Faktur DP</th>
                    <th>Tanggal</th>
                    <th>Customer</th>
                    <th class="num">Nilai DP</th>
                    <th class="num">Potong DP</th>
                    <th class="num">Sisa DP</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($masters as $row)
                    @php
                        $totalDp += (float) $row->famountsonet;
                        $totalUsed += (float) $row->ftotaldp;
                        $totalRemain += (float) $row->fsisadp;
                    @endphp
                    <tr>
                        <td>{{ $row->fbranchcode }}</td>
                        <td>{{ $row->fsono }}</td>
                        <td>{{ $row->fsodate }}</td>
                        <td>{{ $row->fcustno }} - {{ $row->fcustname }}</td>
                        <td class="num">{{ number_format((float) $row->famountsonet, 2, ',', '.') }}</td>
                        <td class="num">{{ number_format((float) $row->ftotaldp, 2, ',', '.') }}</td>
                        <td class="num">{{ number_format((float) $row->fsisadp, 2, ',', '.') }}</td>
                    </tr>
                    @if ($filters['report_type'] === 'DETAIL')
                        @foreach ($detailsByDp->get(trim((string) $row->fsono), collect()) as $detail)
                            <tr class="detail">
                                <td></td>
                                <td>{{ $detail->tipe_label ?? 'Pemakaian DP' }}</td>
                                <td>{{ $detail->fsodate }}</td>
                                <td>{{ $detail->fsono }}</td>
                                <td></td>
                                <td class="num">{{ number_format(abs((float) $detail->famount), 2, ',', '.') }}</td>
                                <td></td>
                            </tr>
                        @endforeach
                    @endif
                @endforeach
                <tr class="grand">
                    <td colspan="4">GRAND TOTAL</td>
                    <td class="num">{{ number_format($totalDp, 2, ',', '.') }}</td>
                    <td class="num">{{ number_format($totalUsed, 2, ',', '.') }}</td>
                    <td class="num">{{ number_format($totalRemain, 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
        <div class="end">*** end of report ***</div>
    </div>
</body>
</html>
