<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 9px; background: #eee; color: #000; }
        .page-a4 { width: 297mm; min-height: 210mm; margin: 15px auto; background: #fff; padding: 12mm; box-shadow: 0 0 8px rgba(0,0,0,.15); }
        .header { position: relative; text-align: center; margin-bottom: 12px; padding-bottom: 18px; }
        .header h2 { color: #c00; font-size: 18px; text-transform: uppercase; margin-bottom: 4px; }
        .left-info { position: absolute; left: 0; top: 0; text-align: left; line-height: 1.5; }
        .right-info { position: absolute; right: 0; top: 0; text-align: left; line-height: 1.5; }
        .no-print { position: fixed; top: 10px; left: 10px; display: flex; gap: 8px; z-index: 10; }
        .no-print button { background: #2563eb; color: #fff; border: 0; border-radius: 4px; padding: 8px 14px; cursor: pointer; }
        .group-title { margin-top: 8px; padding: 4px 6px; border: 1px solid #999; background: #ffe6e6; color: #c00; font-weight: bold; }
        .wh-title { margin-top: 8px; padding: 4px 6px; border: 1px solid #aaa; background: #f3f4f6; font-weight: bold; }
        .rekap-header, .rekap-row { display: grid; grid-template-columns: 8mm 24mm 58mm 15mm 20mm 24mm 22mm 22mm 24mm 18mm; gap: 1px; padding: 3px; align-items: center; }
        .detail-header, .detail-row { display: grid; grid-template-columns: 35mm 18mm 18mm 25mm 40mm 20mm 22mm 22mm 22mm 24mm; gap: 1px; padding: 3px; align-items: center; }
        .rekap-header, .detail-header { background: #f0f0f0; border: 1px solid #000; font-weight: bold; }
        .rekap-row, .detail-row { border-bottom: 1px solid #e5e7eb; }
        .right { text-align: right; }
        .center { text-align: center; }
        .truncate { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .page-break { page-break-before: always; }
        @media print {
            body { background: #fff; }
            .page-a4 { width: 297mm; min-height: 210mm; margin: 0; box-shadow: none; page-break-after: always; }
            .no-print { display: none; }
            @page { size: A4 landscape; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print"><button onclick="window.print()">Cetak Laporan</button></div>

    @php
        $branchText = request()->has('branch_codes') ? implode(', ', (array) request('branch_codes')) : 'Semua';
        $period = (request('date_from') ?: date('Y-01-01')) . ' s/d ' . (request('date_to') ?: date('Y-12-31'));
        $groupBy = request('grouping', 'group') === 'merek' ? 'Merek' : 'Group Produk';
        $pagePerWh = request()->boolean('page_per_warehouse');
    @endphp

    <div class="page-a4">
        <div class="header">
            <div class="left-info">Cabang: {{ $branchText }}<br>Periode: {{ $period }}<br>Mode: {{ strtoupper($mode) }}</div>
            <h2>{{ $title }}</h2>
            <div>Grouping: {{ $groupBy }}</div>
            <div class="right-info">Tanggal: {{ date('d/m/Y') }}<br>Jam: {{ date('H:i') }}<br>Opr: {{ $user_session->fname ?? 'User' }}</div>
        </div>

        @if ($mode === 'rekap')
            <div class="rekap-header">
                <div>No.</div><div>Kode Prd</div><div>Nama Produk</div><div class="right">Isi</div><div>Satuan</div><div class="right">Saldo Awal</div><div class="right">Masuk</div><div class="right">Keluar</div><div class="right">Saldo Akhir</div><div>Gudang</div>
            </div>
            @forelse ($rows->groupBy('fwhcode') as $whcode => $whRows)
                @if (!$loop->first && $pagePerWh)<div class="page-break"></div>@endif
                <div class="wh-title">Gudang: {{ $whcode }}</div>
                @foreach ($whRows->groupBy(request('grouping', 'group') === 'merek' ? 'fmerekname' : 'fgroupname') as $groupName => $items)
                    <div class="group-title">{{ $groupName ?: '-' }}</div>
                    @foreach ($items as $row)
                        <div class="rekap-row">
                            <div class="center">{{ $loop->iteration }}</div>
                            <div class="truncate">{{ $row->fprdcode }}</div>
                            <div class="truncate">{{ $row->fprdname }}</div>
                            <div class="right">{{ number_format((float) $row->qtykecil, 2, ',', '.') }}</div>
                            <div>{{ $row->fsatuan }}</div>
                            <div class="right">{{ number_format((float) $row->qtyawalkecil, 2, ',', '.') }}</div>
                            <div class="right">{{ number_format((float) $row->qtymasukkecil, 2, ',', '.') }}</div>
                            <div class="right">{{ number_format((float) $row->qtykeluarkecil, 2, ',', '.') }}</div>
                            <div class="right">{{ number_format((float) $row->qtysaldokecil, 2, ',', '.') }}</div>
                            <div>{{ $row->fwhcode }}</div>
                        </div>
                    @endforeach
                @endforeach
            @empty
                <div style="padding:20px;text-align:center;color:#666;">Tidak ada data ditemukan.</div>
            @endforelse
        @else
            <div class="detail-header">
                <div>Transaksi</div><div>Kode Trans</div><div>Tanggal</div><div>No.Ref</div><div>Supplier/Customer</div><div>Satuan</div><div class="right">Saldo Awal</div><div class="right">Masuk</div><div class="right">Keluar</div><div class="right">Saldo Akhir</div>
            </div>
            @forelse ($rows->groupBy('fwhcode') as $whcode => $whRows)
                @if (!$loop->first && $pagePerWh)<div class="page-break"></div>@endif
                <div class="wh-title">Gudang: {{ $whcode }}</div>
                @foreach ($whRows->groupBy('fprdcode') as $prdcode => $items)
                    <div class="group-title">{{ $prdcode }} - {{ $items->first()->fprdname ?? '' }}</div>
                    @foreach ($items as $row)
                        <div class="detail-row">
                            <div class="truncate">{{ $row->fstockmt }}</div>
                            <div>{{ $row->fstockmtcode }}</div>
                            <div>{{ $row->fstockdate ? \Carbon\Carbon::parse($row->fstockdate)->format('d/m/Y') : '' }}</div>
                            <div class="truncate">{{ $row->frefno }}</div>
                            <div class="truncate">{{ $row->fsuppliername }}</div>
                            <div>{{ $row->fsatuan ?? '' }}</div>
                            <div class="right">{{ number_format((float) $row->qtyawalkecil, 2, ',', '.') }}</div>
                            <div class="right">{{ number_format((float) $row->qtymasukkecil, 2, ',', '.') }}</div>
                            <div class="right">{{ number_format((float) $row->qtykeluarkecil, 2, ',', '.') }}</div>
                            <div class="right">{{ number_format((float) $row->qtysaldokecil, 2, ',', '.') }}</div>
                        </div>
                    @endforeach
                @endforeach
            @empty
                <div style="padding:20px;text-align:center;color:#666;">Tidak ada data ditemukan.</div>
            @endforelse
        @endif

        <div style="text-align:center;margin-top:16px;border-top:1px solid #000;padding-top:12px;font-weight:bold;font-size:8px;color:#555;">** End of Report **</div>
    </div>
</body>
</html>
