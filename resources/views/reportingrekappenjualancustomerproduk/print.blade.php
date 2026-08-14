<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Penjualan Customer By Produk</title>
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
        .group { font-weight: bold; background: #f9fafb; }
        .subtotal { font-weight: bold; background: #f3f4f6; }
        .grand { font-weight: bold; background: #111827; color: white; }
        .end { text-align: center; margin-top: 18px; font-weight: bold; }
        @media print { body { background: white; } .page { width: auto; margin: 0; padding: 0; box-shadow: none; } .actions { display: none; } }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print()">Print / PDF</button>
        <a href="{{ route('reportingrekappenjualancustomerproduk.excel', request()->query()) }}">Export Excel</a>
    </div>

    <div class="page">
        <h1>PT. UTALIYA</h1>
        <h2>Rekap Penjualan Customer By Produk</h2>

        <div class="meta">
            <div>Cabang: {{ $filters['branch_label'] }}</div>
            <div>Tg: {{ date('d/m/Y') }} Jam: {{ date('H:i') }}</div>
            <div>Periode: {{ $filters['date_from'] }} s/d {{ $filters['date_to'] }}</div>
            <div>User ID: {{ $user_session->fuserid ?? $user_session->username ?? $user_session->name ?? 'User' }}</div>
            <div>Group/Merek: {{ $filters['group_label'] }}</div>
            <div>Grouping: {{ $filters['grouping_by'] === 'BY_MEREK' ? 'By Merek' : 'By Group Produk' }}</div>
            <div>Salesman: {{ $filters['salesman_label'] }}</div>
            <div>Customer: {{ $filters['customer_label'] }}</div>
        </div>

        @php $grand = 0; @endphp
        @foreach ($rows->groupBy('fcustno') as $custNo => $customerRows)
            @php $customerTotal = 0; $customerQtyBesar = 0; $customerQtyKecil = 0; @endphp
            <div class="group">Customer: {{ $custNo }} {{ $customerRows->first()->customer_name }}</div>
            @foreach ($customerRows->groupBy('fgroupcode') as $groupCode => $groupRows)
                @php $groupTotal = 0; $groupQtyBesar = 0; $groupQtyKecil = 0; @endphp
                <div class="group">Group / Merek: {{ $groupCode }}</div>
                <table>
                    <thead>
                        <tr>
                            <th>Produk#</th>
                            <th>Nama Produk</th>
                            <th class="num">Qty.Besar</th>
                            <th class="num">Qty.Kecil</th>
                            <th class="num">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($groupRows as $row)
                            @php
                                $groupTotal += (float) $row->totalnota;
                                $groupQtyBesar += (float) $row->fqtybesar;
                                $groupQtyKecil += (float) $row->fqtykecil;
                            @endphp
                            @if ($filters['report_type'] === 'DETAIL')
                                <tr>
                                    <td>{{ $row->fprdcode }}</td>
                                    <td>{{ $row->fprdname }}</td>
                                    <td class="num">{{ number_format((float) $row->fqtybesar, 2, ',', '.') }} {{ $row->fsatuanbesar }}</td>
                                    <td class="num">{{ number_format((float) $row->fqtykecil, 2, ',', '.') }} {{ $row->fsatuankecil }}</td>
                                    <td class="num">{{ number_format((float) $row->totalnota, 2, ',', '.') }}</td>
                                </tr>
                            @endif
                        @endforeach
                        <tr class="subtotal">
                            <td colspan="2">TOTAL {{ $groupCode }}</td>
                            <td class="num">{{ number_format($groupQtyBesar, 2, ',', '.') }}</td>
                            <td class="num">{{ number_format($groupQtyKecil, 2, ',', '.') }}</td>
                            <td class="num">{{ number_format($groupTotal, 2, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
                @php
                    $customerTotal += $groupTotal;
                    $customerQtyBesar += $groupQtyBesar;
                    $customerQtyKecil += $groupQtyKecil;
                @endphp
            @endforeach
            <table>
                <tr class="subtotal">
                    <td colspan="2">TOTAL {{ $customerRows->first()->customer_name }}</td>
                    <td class="num">{{ number_format($customerQtyBesar, 2, ',', '.') }}</td>
                    <td class="num">{{ number_format($customerQtyKecil, 2, ',', '.') }}</td>
                    <td class="num">{{ number_format($customerTotal, 2, ',', '.') }}</td>
                </tr>
            </table>
            @php $grand += $customerTotal; @endphp
        @endforeach

        <table>
            <tr class="grand">
                <td>GRAND TOTAL</td>
                <td class="num">{{ number_format($grand, 2, ',', '.') }}</td>
            </tr>
        </table>
        <div class="end">*** end of report ***</div>
    </div>
</body>
</html>
