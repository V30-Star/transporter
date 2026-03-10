<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Tree Report</title>
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

        /* Container A4 */
        .a4-container {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: white;
            padding: 15mm;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            position: relative;
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
            /* Merah sesuai laporan lainnya */
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

        /* ── Grid Table Styles ── */
        .grid-header-labels {
            display: grid;
            /* Kolom: Account (Tree), D/K, Sub Account */
            grid-template-columns: 140mm 20mm 20mm;
            gap: 2px;
            font-weight: bold;
            background-color: #f0f0f0 !important;
            border: 1px solid #000;
            border-bottom: 2px solid #000;
            padding: 8px 5px;
            -webkit-print-color-adjust: exact;
        }

        .grid-row {
            display: grid;
            grid-template-columns: 140mm 20mm 20mm;
            gap: 2px;
            padding: 4px 5px;
            border-left: 1px solid #ccc;
            border-right: 1px solid #ccc;
            border-bottom: 1px dashed #aaa;
            background-color: #fff;
            align-items: center;
        }

        /* Baris Header Akun (fend == 0) diberi warna merah muda seperti detail laporan lain */
        .row-header-akun {
            background-color: #ffe6e6 !important;
            font-weight: bold;
            -webkit-print-color-adjust: exact;
        }

        /* ── Tree Glyph Icons (SVG) ── */
        .ti {
            display: inline-block;
            width: 16px;
            height: 16px;
            vertical-align: middle;
        }

        .ti-line {
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16'%3E%3Cline x1='8' y1='0' x2='8' y2='16' stroke='%23555' stroke-width='1.5'/%3E%3C/svg%3E") no-repeat center;
        }

        .ti-joinbot {
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16'%3E%3Cline x1='8' y1='0' x2='8' y2='16' stroke='%23555' stroke-width='1.5'/%3E%3Cline x1='8' y1='8' x2='16' y2='8' stroke='%23555' stroke-width='1.5'/%3E%3C/svg%3E") no-repeat center;
        }

        .ti-join {
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16'%3E%3Cline x1='8' y1='0' x2='8' y2='8' stroke='%23555' stroke-width='1.5'/%3E%3Cline x1='8' y1='8' x2='16' y2='8' stroke='%23555' stroke-width='1.5'/%3E%3C/svg%3E") no-repeat center;
        }

        .text-center {
            text-align: center;
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
        <button class="print-button" onclick="window.print()">🖨️ Cetak Laporan</button>
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

        <a href="{{ route('reportingaccount.excel', request()->query()) }}"
            style="padding: 6px 14px; background: #1d6f42; color: white; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: bold;">
            ⬇ Export Excel
        </a>
    </div>

    @php
        $rows = $data->values();
        $nrows = $rows->count();
        $nBegin = $nrows > 0 ? $rows[0]->flevel : 1;
        $lPreviousLeafEnd = true;
        $nPreviousLevel = $nBegin;
        $cTree = '';
    @endphp

    <div class="a4-container">
        <div class="header-section">
            <h2>Account Tree Report</h2>
            <div class="info-tambahan">
                <div><span class="info-label">Tanggal</span>: {{ date('d/m/Y') }}</div>
                <div><span class="info-label">Jam</span>: {{ date('H:i') }}</div>
                <div><span class="info-label">Hal</span>: 1 / 1</div>
                <div><span class="info-label">Opr</span>: {{ $user_session->fname ?? 'admin' }}</div>
            </div>
        </div>

        {{-- Table Labels --}}
        <div class="grid-header-labels">
            <div>Account Name</div>
            <div class="text-center">D/K</div>
            <div class="text-center">Sub Acc</div>
        </div>

        {{-- Loop Data --}}
        @foreach ($rows as $i => $row)
            @php
                $nEnd = $row->flevel;
                $cetak = '';

                if ($i == 0) {
                    $nBegin = $nEnd;
                    $cTree = '';
                } else {
                    if ($nEnd > $nPreviousLevel) {
                        $cTree .= $lPreviousLeafEnd ? '$8' : '$9';
                    } elseif ($nEnd < $nPreviousLevel) {
                        $cTree = substr($cTree, 0, ($nEnd - $nBegin) * 2);
                    }
                    $symbol = $row->fleafend == '1' ? '$2' : '$1';
                    $cetak = $cTree . $symbol;
                }

                $nPreviousLevel = $nEnd;
                $lPreviousLeafEnd = $nEnd === $nBegin ? true : $row->fleafend == '1';

                $cetakHtml = str_replace(
                    ['$9', '$8', '$1', '$2'],
                    [
                        '<span class="ti ti-line"></span>',
                        '<span class="ti ti-empty"></span>',
                        '<span class="ti ti-joinbot"></span>',
                        '<span class="ti ti-join"></span>',
                    ],
                    $cetak,
                );
            @endphp

            <div class="grid-row {{ $row->fend == 0 ? 'row-header-akun' : '' }}">
                <div style="display: flex; align-items: center;">
                    {!! $cetakHtml !!}
                    <span style="font-family:'Courier New', monospace; font-size:11px; margin-left: 5px;">
                        {{ trim($row->faccount) }} - {{ trim($row->faccname) }}
                    </span>
                </div>
                <div class="text-center">{{ $row->fnormal == 'D' ? 'Debit' : 'Kredit' }}</div>
                <div class="text-center">{{ $row->fhavesubaccount == 1 ? 'Yes' : 'No' }}</div>
            </div>
        @endforeach

        <div
            style="margin-top: 20px; text-align: center; font-weight: bold; border-top: 1px solid #000; padding-top: 10px;">
            *** end of report ***
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
