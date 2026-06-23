<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listing Faktur Pajak Penjualan</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; font-size: 11px; background: #eee; color: #000; }
        .page-a4 { width: 297mm; min-height: 210mm; margin: 20px auto; padding: 12mm; background: #fff; box-shadow: 0 0 10px rgba(0,0,0,.15); }
        .no-print { position: fixed; top: 10px; left: 10px; display: flex; gap: 8px; z-index: 10; }
        .no-print button { border: 0; border-radius: 4px; padding: 8px 14px; color: #fff; background: #2563eb; font-weight: bold; cursor: pointer; }
        h2 { margin: 0 0 4px; text-align: center; font-size: 18px; text-transform: uppercase; }
        .meta { display: flex; justify-content: space-between; margin-bottom: 12px; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 4px 5px; vertical-align: top; }
        th { background: #f0f0f0; text-align: left; }
        .right { text-align: right; }
        .center { text-align: center; }
        tfoot td { font-weight: bold; background: #f5f5f5; }
        @media print {
            body { background: #fff; }
            .no-print { display: none; }
            .page-a4 { width: auto; min-height: auto; margin: 0; padding: 8mm; box-shadow: none; }
            @page { size: A4 landscape; margin: 8mm; }
        }
    </style>
</head>

<body>
    <div class="no-print">
        <button onclick="window.print()">Cetak</button>
    </div>

    @php
        $branchText = request()->has('branch_codes') ? implode(', ', (array) request('branch_codes')) : 'Semua';
        $grandGross = 0;
        $grandDiscount = 0;
        $grandNet = 0;
        $grandTax = 0;
    @endphp

    <div class="page-a4">
        <h2>Listing Faktur Pajak Penjualan</h2>
        <div class="meta">
            <div>
                Periode: {{ request('date_from') ?? '...' }} s/d {{ request('date_to') ?? '...' }}<br>
                Cabang: {{ $branchText }}<br>
                Customer: {{ request('customer') ?: 'Semua' }}
            </div>
            <div>
                Tanggal: {{ date('d/m/Y') }}<br>
                Jam: {{ date('H:i') }}<br>
                Opr: {{ $user_session->fname ?? 'admin' }}
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode FP</th>
                    <th>No. Pajak</th>
                    <th>No. Faktur</th>
                    <th>Tanggal</th>
                    <th>NPWP</th>
                    <th>Customer</th>
                    <th class="right">Gross</th>
                    <th class="right">Diskon</th>
                    <th class="right">Net</th>
                    <th class="right">Pajak</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($results as $row)
                    @php
                        $grandGross += (float) $row->famountgross;
                        $grandDiscount += (float) $row->fdiscount;
                        $grandNet += (float) $row->famountsonet;
                        $grandTax += (float) $row->famountpajak;
                    @endphp
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td>{{ $row->fkodefp }}</td>
                        <td>{{ $row->ftaxno }}</td>
                        <td>{{ $row->fsono }}</td>
                        <td>{{ $row->fsodate ? date('d/m/Y', strtotime($row->fsodate)) : '' }}</td>
                        <td>{{ $row->fnpwp }}</td>
                        <td>{{ $row->fcustno }} - {{ $row->fcustname }}</td>
                        <td class="right">{{ number_format((float) $row->famountgross, 2, ',', '.') }}</td>
                        <td class="right">{{ number_format((float) $row->fdiscount, 2, ',', '.') }}</td>
                        <td class="right">{{ number_format((float) $row->famountsonet, 2, ',', '.') }}</td>
                        <td class="right">{{ number_format((float) $row->famountpajak, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="center">Tidak ada data ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="7" class="right">Grand Total</td>
                    <td class="right">{{ number_format($grandGross, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format($grandDiscount, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format($grandNet, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format($grandTax, 2, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</body>

</html>
