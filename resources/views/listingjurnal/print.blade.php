<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listing Jurnal Transaksi</title>
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
            color: #c00; /* Red style matching sales order */
        }

        .filter-info {
            font-size: 10px;
            color: #333;
            margin-bottom: 5px;
        }

        /* --- JOURNAL HEADER STYLES (7 Kolom) --- */
        .po-header-labels,
        .po-header {
            display: grid;
            grid-template-columns: 32mm 18mm 15mm 55mm 15mm 15mm 20mm;
            gap: 2px;
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

        /* --- JOURNAL DETAIL STYLES (9 Kolom) --- */
        .po-detail-labels,
        .po-detail {
            display: grid;
            grid-template-columns: 8mm 18mm 40mm 20mm 18mm 8mm 15mm 20mm 20mm;
            gap: 3px;
            font-size: 8px;
            padding: 4px 5px;
        }

        .po-detail-labels {
            font-weight: bold;
            color: #c00;
            background-color: #ffe6e6;
            border: 1px solid #ccc;
            border-bottom: 1px solid #ccc;
            margin-top: 2px;
            padding: 6px 5px;
        }

        .po-detail {
            color: #c00;
            border-left: 1px solid #ccc;
            border-right: 1px solid #ccc;
            background-color: #fff;
        }

        .po-detail>div:first-child {
            padding-left: 2mm;
        }

        /* Alignment */
        .po-header-labels>div:nth-child(6),
        .po-header-labels>div:nth-child(7),
        .po-header>div:nth-child(6),
        .po-header>div:nth-child(7) {
            text-align: right;
        }

        .po-detail-labels>div:nth-child(6),
        .po-detail>div:nth-child(6) {
            text-align: center;
        }

        .po-detail-labels>div:nth-child(7),
        .po-detail-labels>div:nth-child(8),
        .po-detail-labels>div:nth-child(9),
        .po-detail>div:nth-child(7),
        .po-detail>div:nth-child(8),
        .po-detail>div:nth-child(9) {
            text-align: right;
        }

        .separator {
            border-bottom: 1px solid #ccc;
            margin: 8px 0;
            clear: both;
        }

        .grand-total-section {
            margin-top: 20px;
            border-top: 2px solid #000;
            padding-top: 10px;
        }

        .grand-total-header {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            font-weight: bold;
            padding: 8px 15px;
            background-color: #333;
            color: white;
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

        .supplier-info-kiri {
            position: absolute;
            top: 10mm;
            left: 0mm;
            font-size: 10px;
            color: #333;
            font-weight: bold;
            text-align: left;
        }

        .truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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

        @media print {
            body {
                background-color: white !important;
            }

            .a4-container {
                width: 100%;
                margin: 0;
                padding: 10mm;
                box-shadow: none;
                page-break-after: always;
            }

            .no-print {
                display: none !important;
            }

            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }
    </style>
</head>

<body>
    <div class="no-print">
        <button class="print-button" onclick="window.print()">🖨️ Cetak Laporan</button>

        {{-- Zoom Out --}}
        <button onclick="adjustZoom(-0.1)"
            style="padding: 6px 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold;">
            −
        </button>

        {{-- Zoom Level --}}
        <span id="zoomLabel"
            style="min-width: 48px; text-align: center; font-size: 13px; font-weight: bold; color: #333; align-self: center;">
            100%
        </span>

        {{-- Zoom In --}}
        <button onclick="adjustZoom(0.1)"
            style="padding: 6px 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold;">
            +
        </button>
    </div>

    @php
        $grandTotalDebet = 0;
        $grandTotalKredit = 0;
    @endphp

    <div class="report-wrapper" id="reportWrapper">
        @if ($chunkedData->isEmpty())
            <div class="a4-container">
                <div class="header-section">
                    <h2>Listing Jurnal Transaksi</h2>
                    <div style="margin-top: 30px; text-align: center; font-size: 12px; color: #666;">Tidak ada data ditemukan.</div>
                </div>
            </div>
        @else
            @foreach ($chunkedData as $pageIndex => $pageData)
                <div class="a4-container">
                    <div class="header-section">
                        <div class="supplier-info-kiri">
                            Type Jurnal: {{ !empty($selectedTypes) ? implode(', ', $selectedTypes) : 'Semua' }}
                        </div>
                        <h2>Listing Jurnal Transaksi</h2>
                        <div class="filter-info">
                            Periode:
                            {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : '...' }}
                            s/d
                            {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y') : '...' }}
                        </div>
                        <div class="info-tambahan">
                            <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                            <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                            <div><span class="info-label">Hal</span>: {{ $pageIndex + 1 }} / {{ $totalPages }}</div>
                            <div><span class="info-label">Opr</span>: {{ $user_session->fname ?? 'User' }}</div>
                        </div>
                    </div>

                    {{-- Header Labels --}}
                    <div class="po-header-labels">
                        <div>No. Jurnal</div>
                        <div>Tanggal</div>
                        <div>Type</div>
                        <div>Note / Keterangan</div>
                        <div>User-id</div>
                        <div>Balance</div>
                        <div>Balance Rp</div>
                    </div>

                    {{-- Detail Labels --}}
                    <div class="po-detail-labels">
                        <div>Line</div>
                        <div>Account</div>
                        <div>Account Name</div>
                        <div>Ref No</div>
                        <div>Sub Account</div>
                        <div>D/K</div>
                        <div>Rate</div>
                        <div>Debet</div>
                        <div>Kredit</div>
                    </div>

                    @foreach ($pageData as $jurnalNo => $lines)
                        @php
                            $firstLine = $lines->first();
                            $jurnalDateFormatted = !empty($firstLine->fjurnaldate) ? \Carbon\Carbon::parse($firstLine->fjurnaldate)->format('d/m/Y') : '';
                        @endphp
                        <div class="po-header" style="border-top: 1px solid #000; margin-top: 5px;">
                            <div class="truncate">{{ $jurnalNo }}</div>
                            <div>{{ $jurnalDateFormatted }}</div>
                            <div>{{ $firstLine->fjurnaltype }}</div>
                            <div class="truncate" title="{{ $firstLine->fjurnalnote }}">{{ $firstLine->fjurnalnote }}</div>
                            <div>{{ $firstLine->fuserid }}</div>
                            <div>{{ number_format((float) $firstLine->fbalance, 2, ',', '.') }}</div>
                            <div>{{ number_format((float) $firstLine->fbalance_rp, 2, ',', '.') }}</div>
                        </div>

                        @foreach ($lines as $dt)
                            @php
                                $grandTotalDebet += (float) $dt->debet;
                                $grandTotalKredit += (float) $dt->kredit;
                            @endphp
                            <div class="po-detail">
                                <div>{{ $dt->flineno }}</div>
                                <div>{{ $dt->faccount }}</div>
                                <div class="truncate" title="{{ $dt->faccname }}">{{ $dt->faccname }}</div>
                                <div class="truncate" title="{{ $dt->frefno }}">{{ $dt->frefno }}</div>
                                <div class="truncate" title="{{ $dt->fsubaccount }}">{{ $dt->fsubaccount }}</div>
                                <div>{{ $dt->fdk }}</div>
                                <div>{{ number_format((float) $dt->frate, 2, ',', '.') }}</div>
                                <div>{{ $dt->debet !== null ? number_format((float) $dt->debet, 2, ',', '.') : '' }}</div>
                                <div>{{ $dt->kredit !== null ? number_format((float) $dt->kredit, 2, ',', '.') : '' }}</div>
                            </div>
                        @endforeach

                        @if (!$loop->last)
                            <div class="separator"></div>
                        @endif
                    @endforeach

                    @if ($loop->last)
                        <div class="grand-total-section">
                            <div class="grand-total-header">
                                <span>GRAND TOTAL LISTING JURNAL TRANSACTION</span>
                                <span>Rp {{ number_format($grandTotalDebet, 2, ',', '.') }} &nbsp;|&nbsp; Rp {{ number_format($grandTotalKredit, 2, ',', '.') }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        @endif
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