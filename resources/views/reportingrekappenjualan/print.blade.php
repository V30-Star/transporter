<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; color: #000; background-color: #f5f5f5; }
        .a4-container { width: 210mm; min-height: 297mm; margin: 20px auto; background: white; padding: 15mm; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        .header-section { position: relative; margin-bottom: 15px; text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header-section h2 { font-size: 18px; margin-bottom: 8px; font-weight: bold; text-transform: uppercase; color: #c00; }
        .filter-info { font-size: 10px; color: #333; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; font-size: 9px; }
        th, td { border: 1px solid #ccc; padding: 5px 6px; }
        th { background-color: #f0f0f0; border-color: #000; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .group-row { font-weight: bold; background-color: #ffe6e6; color: #c00; }
        .group-total { font-weight: bold; background-color: #f9fafb; }
        .grand-total-section { margin-top: 20px; border-top: 2px solid #000; padding-top: 10px; display: flex; justify-content: flex-end; }
        .grand-total-panel { width: 70mm; border: 1px solid #000; font-size: 10px; font-weight: bold; }
        .grand-total-row { display: grid; grid-template-columns: 30mm 40mm; background-color: #333; color: white; }
        .grand-total-row div { padding: 6px 8px; }
        .grand-total-row div:last-child { text-align: right; }
        .info-tambahan { position: absolute; top: 0; right: 0; font-size: 10px; color: #333; text-align: left; line-height: 1.4; }
        .info-label { font-weight: bold; display: inline-block; width: 45px; }
        .supplier-info-kiri { position: absolute; top: 10mm; left: 0mm; font-size: 10px; color: #333; font-weight: bold; }
        .no-print { position: fixed; top: 10px; left: 10px; display: flex; gap: 8px; z-index: 1000; }
        .print-button { background-color: #3b82f6; color: white; padding: 10px 20px; border-radius: 5px; cursor: pointer; border: none; }
        @media print {
            body { background-color: white !important; }
            .a4-container { width: 100%; margin: 0; padding: 10mm; box-shadow: none; page-break-after: always; }
            .no-print { display: none !important; }
            @page { size: A4 portrait; margin: 10mm; }
        }
    </style>
</head>

<body>
    <div class="no-print">
        <button class="print-button" onclick="window.print()">🖨️ Cetak Laporan</button>
    </div>

    <div class="a4-container">
        <div class="header-section">
            <div class="supplier-info-kiri">
                Cabang: {{ request()->has('branch_codes') ? implode(', ', (array) request()->input('branch_codes')) : 'Semua' }}
            </div>
            <h2>{{ $title }}</h2>
            <div class="filter-info">
                Periode {{ $request->input('date_from') ?: '...' }} s/d {{ $request->input('date_to') ?: '...' }} | {{ $groupBy === 'group' ? 'By Group Produk' : 'By Merek' }}
            </div>
            <div class="info-tambahan">
                <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 10mm;">No.</th>
                    <th style="width: 30mm;">Kode Barang</th>
                    <th>Nama Barang</th>
                    <th style="width: 25mm;" class="text-right">Quantity</th>
                    <th style="width: 35mm;" class="text-right">Total Penjualan</th>
                </tr>
            </thead>
            <tbody>
                @php $grandTotal = 0; @endphp
                @forelse ($rows->groupBy('fmerek') as $groupCode => $items)
                    @php
                        $groupName = $items->first()->fgroupname ?: $groupCode;
                        $groupTotal = $items->sum('famount');
                        $grandTotal += $groupTotal;
                    @endphp
                    <tr class="group-row">
                        <td colspan="5">{{ $groupCode }} - {{ $groupName }}</td>
                    </tr>
                    @foreach ($items as $index => $row)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>{{ $row->fprdcode }}</td>
                            <td>{{ $row->fprdname }}</td>
                            <td class="text-right">{{ number_format((float) $row->fqty, 2, ',', '.') }}</td>
                            <td class="text-right">{{ number_format((float) $row->famount, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr class="group-total">
                        <td colspan="4" class="text-right">Total({{ $groupCode }})</td>
                        <td class="text-right">{{ number_format((float) $groupTotal, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">Tidak ada data ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="grand-total-section">
            <div class="grand-total-panel">
                <div class="grand-total-row">
                    <div>Grand Total:</div>
                    <div>{{ number_format((float) $grandTotal, 2, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
