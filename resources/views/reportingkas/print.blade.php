<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle }}</title>
    <style>
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

        .a4-container:last-child {
            page-break-after: avoid;
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

        .truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .po-header-labels,
        .po-header {
            display: grid;
            grid-template-columns: 30mm 22mm 22mm 35mm 1fr 28mm;
            gap: 4px;
            font-size: 9px;
            padding: 6px 5px;
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

        .po-detail-labels,
        .po-detail {
            display: grid;
            grid-template-columns: 35mm 1fr 35mm 35mm 30mm;
            gap: 3px;
            font-size: 8px;
            padding: 4px 5px;
        }

        .po-detail-labels {
            font-weight: bold;
            color: #c00;
            background-color: #ffe6e6;
            border: 1px solid #ccc;
            border-bottom: none;
            margin-top: 2px;
            padding: 6px 5px;
        }

        .po-detail {
            color: #c00;
            border-left: 1px solid #ccc;
            border-right: 1px solid #ccc;
            background-color: #fff;
        }

        .po-detail>div:nth-child(2) {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .separator {
            border-bottom: 1px solid #ccc;
            margin: 8px 0;
            clear: both;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            font-style: italic;
        }

        .grand-total-section {
            margin-top: 20px;
            border-top: 2px solid #000;
            padding-top: 10px;
        }

        .grand-total-header,
        .grand-total-detail {
            display: grid;
            grid-template-columns: 1fr 40mm 35mm;
            gap: 5px;
            font-size: 9px;
            padding: 8px 5px;
        }

        .grand-total-header {
            font-weight: bold;
            background-color: #333;
            color: white;
        }

        .grand-total-detail {
            font-weight: bold;
            background-color: #f0f0f0;
            border: 1px solid #333;
        }

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
        }

        .no-print .secondary {
            background: #6c757d;
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

            .po-header-labels,
            .po-detail-labels,
            .grand-total-header {
                background-color: transparent !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>
    <div class="no-print">
        <button class="print-button" onclick="window.print()">CETAK LAPORAN</button>
        <button class="print-button secondary" onclick="window.close()">Tutup</button>
    </div>

    <div class="a4-container">
        <div class="header-section">
            <h2>{{ $pageTitle }}</h2>
            <div class="filter-info">
                Dari {{ $filterDateFrom ?: '-' }} sampai {{ $filterDateTo ?: '-' }}
                | Account: {{ $filterAccount ? ($filterAccount.' - '.($filterAccountName ?: '-')) : 'Semua Account' }}
                | Giro Mundur: {{ $onlyGiroMundur ? 'Ya' : 'Tidak' }}
            </div>
            <div class="info-tambahan">
                <div><span class="info-label">Cetak</span>: {{ $printDate }}</div>
                <div><span class="info-label">User</span>: {{ $user_session->fname ?? $user_session->name ?? '-' }}</div>
            </div>
        </div>

        <div class="po-header-labels">
            <div>No.Voucher</div>
            <div>Tanggal</div>
            <div>No.Giro</div>
            <div>Account</div>
            <div>Uraian</div>
            <div class="text-right">Nilai Bayar</div>
        </div>

        @forelse ($records as $row)
            <div class="po-header">
                <div>{{ $row->fkasmtno }}</div>
                <div>{{ optional($row->fkasmtdate)->format('d/m/Y') ?? \Carbon\Carbon::parse($row->fkasmtdate)->format('d/m/Y') }}</div>
                <div>{{ $row->fnogiro ?: '-' }}</div>
                <div class="truncate">{{ trim(($row->faccountheader ?? '') . ' - ' . ($row->header_account_name ?? ''), ' -') ?: '-' }}</div>
                <div class="truncate">{{ $row->fket ?: '-' }}</div>
                <div class="text-right">{{ number_format((float) ($row->famountpay ?? 0), 2, ',', '.') }}</div>
            </div>

            @if (!empty($detailsByHeader[$row->fkasmtid]) && $detailsByHeader[$row->fkasmtid]->count())
                <div class="po-detail-labels">
                    <div>Account Detail</div>
                    <div>Sub Account</div>
                    <div>Uraian</div>
                    <div class="text-right">Nilai</div>
                    <div class="text-right">DK</div>
                </div>
                @foreach ($detailsByHeader[$row->fkasmtid] as $detail)
                    <div class="po-detail">
                        <div class="truncate">{{ trim(($detail->faccount ?? '') . ' - ' . ($detail->account_name ?? ''), ' -') ?: '-' }}</div>
                        <div class="truncate">{{ trim(($detail->fsubaccount ?? '') . ' - ' . ($detail->subaccount_name ?? ''), ' -') ?: '-' }}</div>
                        <div class="truncate">{{ $detail->fnote ?: '-' }}</div>
                        <div class="text-right">{{ number_format((float) ($detail->fkasdtvalue ?? 0), 2, ',', '.') }}</div>
                        <div class="text-right">{{ $detail->fdk ?: '-' }}</div>
                    </div>
                @endforeach
            @endif

            <div class="separator"></div>
        @empty
            <div class="no-data">Tidak ada data ditemukan.</div>
        @endforelse

        <div class="grand-total-section">
            <div class="grand-total-header">
                <div>Total Transaksi</div>
                <div class="text-right">{{ number_format($grandTotal, 2, ',', '.') }}</div>
                <div></div>
            </div>
            <div class="grand-total-detail">
                <div>Jumlah voucher</div>
                <div class="text-right">{{ $records->count() }}</div>
                <div></div>
            </div>
        </div>
    </div>
</body>

</html>
