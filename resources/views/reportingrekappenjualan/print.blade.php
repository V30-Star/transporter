<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #111827; }
        h2 { text-align: center; margin: 0 0 4px; }
        .meta { text-align: center; margin-bottom: 16px; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; }
        th { background: #f3f4f6; }
        .right { text-align: right; }
        .group-total { font-weight: bold; background: #f9fafb; }
        .grand { font-weight: bold; background: #e5e7eb; }
    </style>
</head>
<body>
    <h2>{{ $title }}</h2>
    <div class="meta">
        Periode {{ $request->input('date_from') }} s/d {{ $request->input('date_to') }} | {{ $groupBy === 'group' ? 'By Group Produk' : 'By Merek' }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:40px">No.</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th class="right">Quantity</th>
                <th class="right">Total Penjualan</th>
            </tr>
        </thead>
        <tbody>
            @php $grandTotal = 0; @endphp
            @foreach ($rows->groupBy('fmerek') as $groupCode => $items)
                @php $groupName = $items->first()->fgroupname ?: $groupCode; $groupTotal = $items->sum('famount'); $grandTotal += $groupTotal; @endphp
                <tr class="group-total">
                    <td colspan="5">{{ $groupCode }} - {{ $groupName }}</td>
                </tr>
                @foreach ($items as $index => $row)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $row->fprdcode }}</td>
                        <td>{{ $row->fprdname }}</td>
                        <td class="right">{{ number_format((float) $row->fqty, 2, ',', '.') }}</td>
                        <td class="right">{{ number_format((float) $row->famount, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr class="group-total">
                    <td colspan="4" class="right">Total {{ $groupName }}</td>
                    <td class="right">{{ number_format((float) $groupTotal, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="grand">
                <td colspan="4" class="right">Grand Total</td>
                <td class="right">{{ number_format((float) $grandTotal, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
