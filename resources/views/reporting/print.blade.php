<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listing Order Pembelian / PO</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            padding: 20px;
            color: #000;
        }

        .header-section {
            margin-bottom: 20px;
            text-align: center;
        }

        .header-section h2 {
            font-size: 16px;
            margin-bottom: 10px;
        }

        .filter-info {
            font-size: 10px;
            color: #666;
            margin-bottom: 15px;
        }

        .po-header {
            margin-bottom: 5px;
            font-weight: bold;
            display: grid;
            grid-template-columns: 120px 100px 200px 120px 120px 100px 120px;
            gap: 10px;
        }

        .po-header-labels {
            display: grid;
            grid-template-columns: 120px 100px 200px 120px 120px 100px 120px;
            gap: 10px;
            font-weight: bold;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        .po-detail-labels {
            display: grid;
            grid-template-columns: 100px 250px 80px 80px 80px 100px 120px;
            gap: 10px;
            font-weight: bold;
            color: #c00;
            padding-left: 20px;
            margin-top: 5px;
            margin-bottom: 3px;
        }

        .po-detail {
            display: grid;
            grid-template-columns: 100px 250px 80px 80px 80px 100px 120px;
            gap: 10px;
            padding-left: 20px;
            color: #c00;
            margin-bottom: 2px;
        }

        .separator {
            border-bottom: 1px dashed #999;
            margin: 15px 0;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        @media print {
            body {
                padding: 10px;
            }

            .no-print {
                display: none;
            }

            @page {
                margin: 1cm;
            }
        }

        .print-button {
            margin-bottom: 20px;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 14px;
            border-radius: 4px;
        }

        .print-button:hover {
            background-color: #45a049;
        }
    </style> 
</head>

<body>
    <div class="no-print">
        <button class="print-button" onclick="window.print()">🖨️ Cetak Laporan</button>
    </div>

    <div class="header-section">
        <h2>Listing Order Pembelian / PO</h2>
        @if (request('filter_date_from') || request('filter_date_to'))
            <div class="filter-info">
                Periode:
                @if (request('filter_date_from'))
                    Dari {{ \Carbon\Carbon::parse(request('filter_date_from'))->format('d/m/Y') }}
                @endif
                @if (request('filter_date_to'))
                    s/d {{ \Carbon\Carbon::parse(request('filter_date_to'))->format('d/m/Y') }}
                @endif
            </div>
        @endif
        @if ($activeSupplierName)
            <div class="filter-info">
                Supplier: {{ $activeSupplierName }}
            </div>
        @endif
    </div>

    {{-- Header Labels --}}
    <div class="po-header-labels">
        <div>No.PO</div>
        <div>Tanggal</div>
        <div>Nama Supplier</div>
        <div>Keterangan</div>
        <div class="text-right">Total Harga</div>
        <div class="text-right">PPN</div>
        <div class="text-right">Total PO</div>
        <div class="po-detail-labels">
            <div>Produk#</div>
            <div>Nama Produk</div>
            <div>Satuan</div>
            <div class="text-right">Qty Order</div>
            <div class="text-right">Qty Terima</div>
            <div class="text-right">@ Harga</div>
            <div class="text-right">Total Harga</div>
        </div>
    </div>

    @forelse($pohData as $index => $poh)
        {{-- PO Header (Parent) - Hitam --}}
        <div class="po-header">
            <div>{{ $poh->fpono }}</div>
            <div>{{ \Carbon\Carbon::parse($poh->fpodate)->format('d/m/Y') }}</div>
            <div>{{ $poh->supplier_name ?? $poh->fsupplier }}</div>
            <div>{{ $poh->fket ?? 'LOCO BL' }}</div>
            <div class="text-right">{{ number_format($poh->total_harga ?? 0, 2, ',', '.') }}</div>
            <div class="text-right">{{ number_format($poh->fppn ?? 0, 2, ',', '.') }}</div>
            <div class="text-right">{{ number_format($poh->famountpo, 2, ',', '.') }}</div>
        </div>

        {{-- PO Detail Rows (Child) - Merah --}}
        @if ($poh->details && $poh->details->count() > 0)
            {{-- PO Detail Rows (Child) - Merah --}}
            @foreach ($poh->details as $detail)
                <div class="po-detail">
                    <div>{{ $detail->fprdcode }}</div>
                    <div>{{ $detail->product_name ?? $detail->fprdcode }}</div>
                    <div>{{ $detail->funit ?? 'PCS' }}</div>
                    <div class="text-right">{{ number_format($detail->fqty, 2, ',', '.') }}</div>
                    <div class="text-right">{{ number_format($detail->fqty_receive ?? 0, 2, ',', '.') }}</div>
                    <div class="text-right">{{ number_format($detail->fprice, 0, ',', '.') }}</div>
                    <div class="text-right">{{ number_format($detail->famount, 0, ',', '.') }}</div>
                </div>
            @endforeach
        @endif

        {{-- Separator jika bukan data terakhir --}}
        @if (!$loop->last)
            <div class="separator"></div>
        @endif

    @empty
        <div style="text-align: center; padding: 20px; color: #999;">
            Tidak ada data Purchase Order yang ditemukan.
        </div>
    @endforelse

    <script>
        // Auto print saat halaman dibuka (opsional)
        // window.onload = function() { window.print(); }
    </script>
</body>

</html>
