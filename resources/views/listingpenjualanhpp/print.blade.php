<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listing Penjualan Dengan HPP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; color: #000; background-color: #eee; }
        .page-a4 { width: 297mm; min-height: 210mm; margin: 20px auto; background: white; padding: 12mm; box-shadow: 0 0 10px rgba(0,0,0,.15); }
        .header-section { position: relative; margin-bottom: 15px; text-align: center; padding-bottom: 25px; }
        .header-section h2 { font-size: 18px; margin-bottom: 8px; font-weight: bold; text-transform: uppercase; color: #c00; }
        .filter-info { font-size: 10px; color: #333; margin-bottom: 5px; }
        .info-tambahan { position: absolute; top: 0; right: 0; font-size: 10px; color: #333; text-align: left; line-height: 1.4; }
        .info-label { font-weight: bold; display: inline-block; width: 45px; }
        .supplier-info-kiri { position: absolute; top: 1mm; left: 0; font-size: 10px; color: #333; text-align: left; line-height: 1.4; }
        .no-print { position: fixed; top: 10px; left: 10px; display: flex; gap: 8px; z-index: 1000; }
        .print-button { background-color: #3b82f6; color: white; padding: 10px 20px; border-radius: 5px; cursor: pointer; border: none; font-weight: bold; }
        .zoom-button { padding: 6px 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; }
        #zoomLabel { min-width: 48px; text-align: center; font-size: 13px; font-weight: bold; color: #333; align-self: center; }
        .invoice-labels, .invoice-row { display: grid; grid-template-columns: 10mm 26mm 18mm 40mm 20mm 22mm 12mm 20mm 24mm 20mm 24mm; gap: 1px; font-size: 7px; padding: 2px; }
        .detail-labels, .detail-row { display: grid; grid-template-columns: 24mm 48mm 22mm 22mm 18mm 24mm 24mm 24mm; gap: 1px; font-size: 7px; padding: 2px; }
        .invoice-labels { background: #f0f0f0; border: 1px solid #000; font-weight: bold; }
        .detail-labels { font-weight: bold; color: #c00; background: #fff; border: 1px solid #000; margin-top: 1px; }
        .detail-row { color: #c00; }
        .invoice-row { margin-top: 5px; }
        .right { text-align: right; }
        .center { text-align: center; }
        .truncate { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .separator { border-bottom: 1px solid #000; margin: 4px 0; clear: both; }
        .totals { margin-top: 18px; border-top: 1px solid #000; padding-top: 8px; display: grid; grid-template-columns: 45mm 35mm; gap: 3px; font-size: 10px; font-weight: bold; justify-content: end; }
        @media print {
            body { background-color: white !important; margin: 0; padding: 0; }
            .page-a4 { width: 297mm; min-height: 210mm; margin: 0 auto !important; padding: 12mm !important; box-shadow: none !important; page-break-after: always; }
            .no-print { display: none !important; }
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
        $totalSales = $groupedData->sum(fn($items) => (float) ($items->first()->famountso ?? 0));
        $totalDiscount = $groupedData->sum(fn($items) => (float) ($items->first()->fdiscount ?? 0));
        $totalHpp = $rows->sum('famounthpp');
        $totalLaba = $rows->sum('flabarugi');
        $branchText = request()->has('branch_codes') ? implode(', ', (array) request()->input('branch_codes')) : 'Semua';
        $customerText = request('cust_from') || request('cust_to') ? (request('cust_from') ?: 'Awal') . ' s/d ' . (request('cust_to') ?: 'Akhir') : 'Semua';
    @endphp

    <div class="report-wrapper" id="reportWrapper">
        <div class="page-a4">
            <div class="header-section">
                <div class="supplier-info-kiri">Customer: {{ $customerText }}<br>Cabang: {{ $branchText }}</div>
                <h2>Listing Penjualan Dengan HPP</h2>
                <div class="filter-info">Periode: {{ request('date_from') ? \Carbon\Carbon::parse(request('date_from'))->format('d/m/Y') : '...' }} s/d {{ request('date_to') ? \Carbon\Carbon::parse(request('date_to'))->format('d/m/Y') : '...' }}</div>
                <div class="info-tambahan">
                    <div><span class="info-label">Hal</span>: 1 / 1</div>
                    <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                    <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                    <div><span class="info-label">Opr</span>: {{ $user_session->fname ?? 'User' }}</div>
                </div>
            </div>

            <div class="invoice-labels">
                <div>Cab.</div><div>No.Faktur</div><div>Tanggal</div><div>Nama Customer</div><div>Salesman</div><div class="right">Total Harga</div><div class="right">% Disc</div><div class="right">Discount</div><div class="right">Tot.Setelah Disc</div><div class="right">PPN</div><div class="right">Nilai Faktur</div>
            </div>
            <div class="detail-labels">
                <div>Kode Barang</div><div>Nama Barang</div><div class="right">Quantity</div><div class="right">@ Harga Net</div><div class="right">@ HPP</div><div class="right">Tot.Harga Jual</div><div class="right">Total HPP</div><div class="right">Laba/Rugi</div>
            </div>

            @forelse ($groupedData as $fsono => $items)
                @php $h = $items->first(); @endphp
                <div class="invoice-row">
                    <div>{{ $h->fbranchcode }}</div>
                    <div class="truncate">{{ $h->fsono }}</div>
                    <div>{{ $h->fsodate ? \Carbon\Carbon::parse($h->fsodate)->format('d/m/Y') : '' }}</div>
                    <div class="truncate">{{ $h->fcustname }}</div>
                    <div class="truncate">{{ $h->fsalesman }}</div>
                    <div class="right">{{ number_format((float) $h->famountgross, 2, ',', '.') }}</div>
                    <div class="right">{{ number_format((float) $h->fdiscpersen, 2, ',', '.') }}</div>
                    <div class="right">{{ number_format((float) $h->fdiscount, 2, ',', '.') }}</div>
                    <div class="right">{{ number_format((float) $h->famountsonet, 2, ',', '.') }}</div>
                    <div class="right">{{ number_format((float) $h->famountpajak, 2, ',', '.') }}</div>
                    <div class="right">{{ number_format((float) $h->famountso, 2, ',', '.') }}</div>
                </div>
                @foreach ($items as $row)
                    <div class="detail-row">
                        <div class="truncate">{{ $row->fprdcode }}</div>
                        <div class="truncate">{{ $row->fprdname }}</div>
                        <div class="right">{{ number_format((float) $row->fqty, 2, ',', '.') }} {{ $row->fsatuan }}</div>
                        <div class="right">{{ number_format((float) $row->fpricenet, 2, ',', '.') }}</div>
                        <div class="right">{{ number_format((float) $row->fhpp, 2, ',', '.') }}</div>
                        <div class="right">{{ number_format((float) $row->famountsales, 2, ',', '.') }}</div>
                        <div class="right">{{ number_format((float) $row->famounthpp, 2, ',', '.') }}</div>
                        <div class="right">{{ number_format((float) $row->flabarugi, 2, ',', '.') }}</div>
                    </div>
                @endforeach
                <div class="detail-row" style="font-weight:bold; border-top:1px solid #000;">
                    <div style="grid-column: span 7;" class="right">Total Laba/Rugi {{ $h->fsono }}</div>
                    <div class="right">{{ number_format((float) $items->sum('flabarugi'), 2, ',', '.') }}</div>
                </div>
                <div class="separator"></div>
            @empty
                <div style="margin-top: 30px; text-align: center; font-size: 12px; color: #666;">Tidak ada data ditemukan.</div>
            @endforelse

            <div class="totals">
                <div>Total Penjualan:</div><div class="right">{{ number_format((float) $totalSales, 2, ',', '.') }}</div>
                <div>Total Discount:</div><div class="right">{{ number_format((float) $totalDiscount, 2, ',', '.') }}</div>
                <div>Total HPP:</div><div class="right">{{ number_format((float) $totalHpp, 2, ',', '.') }}</div>
                <div>Total Laba/Rugi:</div><div class="right">{{ number_format((float) $totalLaba, 2, ',', '.') }}</div>
            </div>

            <div style="text-align: center; margin-top: 10px; border-top: 1px solid #000; padding-top: 20px; font-weight: bold; font-size: 8px; color: #555; text-transform: uppercase; letter-spacing: 1px;">** End of Report **</div>
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
