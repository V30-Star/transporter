<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listing Master Supplier</title>
    <style>
        /* Pengaturan Dasar Kertas A4 */
        @page {
            size: A4 portrait;
            margin: 10mm;
        }

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
            line-height: 1.2;
        }

        /* Container A4 agar tampilan layar sama dengan hasil cetak */
        .a4-container {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: white;
            padding: 15mm;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            position: relative;
            page-break-after: always;
        }

        /* ── Header Section ── */
        .header-section {
            position: relative;
            margin-bottom: 20px;
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 30px;
        }

        .header-section h2 {
            font-size: 18px;
            margin-bottom: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #c00;
            /* Warna Merah khas laporan Anda */
        }

        .info-tambahan {
            position: absolute;
            top: 0;
            right: 0;
            font-size: 10px;
            color: #333;
            text-align: left;
            line-height: 1.4;
        }

        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 45px;
        }

        /* ── Grid Table Styles (7 Kolom) ── */
        .grid-header-labels {
            display: grid;
            /* Kolom: No, Supplier#, Nama Supplier, Alamat, Telp, Kontak Person, Limit/Tempo */
            grid-template-columns: 8mm 25mm 25mm 50mm 25mm 25mm 20mm;
            gap: 2px;
            font-weight: bold;
            background-color: #f0f0f0 !important;
            /* Abu-abu header */
            border: 1px solid #000;
            border-bottom: 2px solid #000;
            padding: 8px 5px;
            -webkit-print-color-adjust: exact;
        }

        .grid-row {
            display: grid;
            grid-template-columns: 8mm 25mm 25mm 50mm 25mm 25mm 20mm;
            gap: 2px;
            padding: 6px 5px;
            border-left: 1px solid #ccc;
            border-right: 1px solid #ccc;
            border-bottom: 1px dashed #aaa;
            background-color: #fff;
            align-items: start;
        }

        .truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        /* ── Footer / Grand Total ── */
        .grand-total {
            border: 2px solid #000;
            margin-top: 20px;
            padding: 10px;
            font-weight: bold;
            background-color: #333 !important;
            color: #fff;
            display: flex;
            justify-content: space-between;
            -webkit-print-color-adjust: exact;
        }

        /* ── No Print UI ── */
        .no-print {
            position: fixed;
            top: 10px;
            left: 10px;
            display: flex;
            gap: 8px;
            z-index: 1000;
        }

        .print-button {
            background-color: #3b82f6;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            border: none;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        @media print {
            body {
                background-color: white !important;
            }

            .no-print {
                display: none !important;
            }

            .a4-container {
                width: 100%;
                margin: 0;
                padding: 10mm;
                box-shadow: none;
            }
        }
    </style>
</head>

<body>

    <div class="no-print">
        <button class="print-button" onclick="window.print()">🖨️ CETAK LAPORAN SUPPLIER</button>
        <button onclick="adjustZoom(-0.1)"
            style="padding: 6px 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;">
            −
        </button>

        <span id="zoomLabel"
            style="min-width: 48px; text-align: center; font-size: 13px; font-weight: bold; color: #333;">
            100%
        </span>

        <button onclick="adjustZoom(0.1)"
            style="padding: 6px 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;">
            +
        </button>

        <a href="{{ route('reportingsupplier.excel', request()->query()) }}"
            style="padding: 6px 14px; background: #1d6f42; color: white; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: bold;">
            ⬇ Export Excel
        </a>
    </div>

    <div class="a4-container">
        <div class="header-section">
            <h2>List of Master Supplier</h2>
            <div class="info-tambahan">
                <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                <div><span class="info-label">Hal</span>: 1 / 1</div>
                <div><span class="info-label">Opr</span>: {{ $user_session->fname ?? 'admin' }}</div>
            </div>
        </div>

        {{-- Header Labels --}}
        <div class="grid-header-labels">
            <div class="text-center">No</div>
            <div>Supplier#</div>
            <div>Nama Supplier</div>
            <div>Alamat</div>
            <div>Telp</div>
            <div>Kontak Person</div>
            <div class="text-center">Tempo</div>
        </div>

        {{-- Data Rows --}}
        @forelse ($data as $i => $row)
            <div class="grid-row">
                <div class="text-center">{{ $i + 1 }}</div>
                <div style="font-weight: bold;">{{ $row->fsuppliercode }}</div>
                <div class="truncate" title="{{ $row->fsuppliername }}">{{ $row->fsuppliername }}</div>
                <div class="truncate" title="{{ $row->faddress }}">{{ $row->faddress }}</div>
                <div class="truncate">{{ $row->ftelp }}</div>
                <div class="truncate">{{ $row->fkontakperson }}</div>
                <div class="text-center">
                    {{ $row->ftempo ?? '0' }} Hari
                </div>
            </div>
        @empty
            <div style="padding: 20px; text-align: center; border: 1px solid #ccc;">
                Data supplier tidak ditemukan.
            </div>
        @endforelse

        {{-- Grand Total Penutup --}}
        <div class="grand-total">
            <span>TOTAL KESELURUHAN DATA SUPPLIER</span>
            <span>{{ count($data) }} Records</span>
        </div>
    </div>

</body>

</html>

<script>
    let currentZoom = 1.0;

    function adjustZoom(delta) {
        currentZoom = Math.min(2.0, Math.max(0.3, currentZoom + delta));
        document.querySelector('.a4-container').style.transform = `scale(${currentZoom})`;
        document.querySelector('.a4-container').style.transformOrigin = 'top center';
        document.getElementById('zoomLabel').textContent = Math.round(currentZoom * 100) + '%';
    }
</script>
