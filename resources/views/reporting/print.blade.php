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
            font-size: 10px;
            color: #000;
            background-color: #f5f5f5;
        }

        /* A4 Container (Untuk Tampilan Layar) */
        .a4-container {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: white;
            padding: 15mm;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header-section {
            position: relative;
            margin-bottom: 15px;
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .header-section h2 {
            font-size: 18px;
            margin-bottom: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #c00;
        }

        .filter-info {
            font-size: 10px;
            color: #333;
            margin-bottom: 5px;
        }

        /* --- PO HEADER STYLES (7 Kolom) --- */
        .po-header-labels,
        .po-header {
            display: grid;
            /* Kolom: No.PO | Tanggal | Nama Supplier (Flex) | Keterangan | Total Harga | PPN | Total PO */
            grid-template-columns: 30mm 20mm 1fr 30mm 25mm 20mm 25mm;
            gap: 5px;
            font-size: 9px;
            padding: 8px 5px;
        }

        .po-header-labels {
            font-weight: bold;
            background-color: #f0f0f0;
            border: 1px solid #000;
            border-bottom: 2px solid #000;
            margin-bottom: 2px;
        }

        .po-header {
            font-weight: bold;
            border-left: 1px solid #ccc;
            border-right: 1px solid #ccc;
            background-color: #fff;
            padding: 6px 5px;
        }

        /* --- PO DETAIL STYLES (7 Kolom) --- */
        .po-detail-labels,
        .po-detail {
            display: grid;
            /* Kolom: Produk# | Nama Produk (Flex) | Satuan | Qty Order | Qty Terima | @ Harga | Total Harga */
            grid-template-columns: 25mm 1fr 15mm 20mm 20mm 25mm 30mm;
            gap: 5px;
            font-size: 8px;

            /* Indentasi detail agar sejajar dengan kolom data PO Header */
            padding: 4px 5px 4px 15mm;
        }

        .po-detail-labels {
            font-weight: bold;
            color: #c00;
            margin-top: 2px;
            padding-top: 5px;
            padding-bottom: 5px;
        }

        .po-detail {
            color: #c00;
            border-left: 1px solid #ccc;
            border-right: 1px solid #ccc;
            background-color: #fff;
        }

        .separator {
            border-bottom: 1px solid #ccc;
            margin: 8px 0;
            clear: both;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            font-style: italic;
        }

        .info-tambahan {
            /* Menempatkan div ini di kanan atas header */
            position: absolute;
            top: 0;
            /* Jarak dari atas */
            right: 0;
            /* Jarak dari kanan, di dalam padding 15mm A4 container */

            font-size: 10px;
            color: #333;
            margin-top: 0;
            text-align: right;

            /* Mengatur setiap item menjadi blok agar tersusun vertikal */
            display: block;
            line-height: 1.4;
            /* Jarak antar baris */
        }

        .info-tambahan span {
            display: inline-block;
            /* Memastikan label dan nilainya dalam satu baris */
        }

        .info-tambahan .info-label {
            font-weight: bold;
            /* Memberi lebar tetap agar semua label sejajar */
            display: inline-block;
            width: 40px;
            text-align: left;
        }

        .supplier-info-kiri {
            position: absolute;
            top: 10mm;
            /* Sesuaikan jarak dari batas atas kertas (di dalam padding 15mm) */
            left: 0mm;
            /* Sejajar dengan batas kiri padding A4 container */
            font-size: 10px;
            color: #333;
            font-weight: bold;
        }

        /* --- PRINT STYLES --- */
        @media print {
            body {
                background-color: white !important;
                margin: 0;
                padding: 0;
            }

            .a4-container {
                width: 100%;
                margin: 0;
                padding: 0;
                /* Margin diatur oleh @page */
                box-shadow: none;
                min-height: auto;
                position: relative;
                /* PENTING! */
            }

            .po-header-labels,
            .po-detail-labels {
                background-color: transparent !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }

            @page {
                size: A4 portrait;
                margin: 10mm;
            }

            .po-header,
            .po-detail {
                page-break-inside: avoid;
            }

            .separator {
                border-bottom: 1px dashed #666;
            }
        }
    </style>
    <style>
        .no-print {
            position: fixed;
            top: 10px;
            left: 10px;
            display: flex;
            gap: 8px;
            z-index: 1000;
        }

        .print-button,
        .excel-button {
            padding: 10px 20px;
            font-size: 14px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .print-button {
            background-color: #3b82f6;
            color: white;
        }

        .print-button:hover {
            background-color: #2563eb;
        }

        .excel-button {
            background-color: #22c55e;
            color: white;
        }

        .excel-button:hover {
            background-color: #16a34a;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="no-print">
        <button class="print-button" onclick="window.print()">🖨️ Cetak Laporan</button>
        <a href="{{ route('reporting.exportExcel', request()->query()) }}" class="excel-button">
            📊 Download Excel
        </a>
    </div>

    <div class="a4-container">
        <div class="header-section">
            <div class="supplier-info-kiri">
                @if ($activeSupplierName)
                    Supplier: {{ $activeSupplierName }}
                @else
                    Supplier: Semua
                @endif
            </div>
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
            <div class="info-tambahan">
                <div>
                    <span class="info-label">Tanggal:</span>
                    {{ \Carbon\Carbon::now()->setTimezone('Asia/Jakarta')->format('d/m/Y') }}
                </div>
                <div>
                    <span class="info-label">Waktu:</span>
                    {{ \Carbon\Carbon::now()->setTimezone('Asia/Jakarta')->format('H:i:s') }}
                </div>
                <div>
                    <span class="info-label">Opr:</span>
                    {{ session('user_name', 'Guest') }}
                </div>
                <div class="filter-info">
                    @if ($pohData instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
                        Hal: {{ $pohData->currentPage() }} / {{ $pohData->lastPage() }}
                    @else
                        Hal: 1 / 1
                    @endif
                </div>
            </div>
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
                <div class="text-right">Satuan</div>
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

            {{-- PO Detail Labels --}}
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
            <div class="no-data">
                Tidak ada data Purchase Order yang ditemukan.
            </div>
        @endforelse
    </div>

    <script>
        // Auto print saat halaman dibuka (opsional)
        // window.onload = function() { window.print(); }
    </script>
</body>

</html>
