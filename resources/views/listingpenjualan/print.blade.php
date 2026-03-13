<!DOCTYPE html>
<html>
<head>
    <title>Listing Penjualan</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 4px; text-align: left; }
        .text-right { text-align: right; }
        .header { margin-bottom: 20px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h2>LISTING PENJUALAN ({{ strtoupper($type) }})</h2>
        <p>Periode: {{ $request->from_date }} s/d {{ $request->to_date }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No. Faktur</th>
                <th>Tanggal</th>
                <th>Customer</th>
                @if($type == 'detail')
                    <th>Kode Produk</th>
                    <th>Nama Produk</th>
                    <th>Qty</th>
                    <th>Harga</th>
                @endif
                <th>Total Net</th>
            </tr>
        </thead>
        <tbody>
            @php $grandTotal = 0; @endphp
            @foreach($results as $row)
                @php $grandTotal += $row->famount; @endphp
                <tr>
                    <td>{{ $row->fsono }}</td>
                    <td>{{ date('d/m/Y', strtotime($row->fsodate)) }}</td>
                    <td>{{ $row->fcustname }}</td>
                    @if($type == 'detail')
                        <td>{{ $row->fprdcode }}</td>
                        <td>{{ $row->fprdname }}</td>
                        <td>{{ number_format($row->fqty, 2) }}</td>
                        <td class="text-right">{{ number_format($row->fprice, 2) }}</td>
                    @endif
                    <td class="text-right">{{ number_format($row->famount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="{{ $type == 'detail' ? 7 : 3 }}" class="text-right">GRAND TOTAL</th>
                <th class="text-right">{{ number_format($grandTotal, 2) }}</th>
            </tr>
        </tfoot>
    </table>
</body>
</html>