<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Sales Order - {{ $hdr->fsono ?? '-' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --fg: #000;
            --bd: #000;
            --blue: #0000ff;
            --red: #ff0000;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #ececec;
            font: 12px Arial, Helvetica, sans-serif;
            color: var(--fg);
        }

        .sheet {
            width: 8.27in;
            min-height: 5.83in;
            margin: 0.2in auto;
            padding: 0.25in 0.4in;
            background: #fff;
            border: 1px solid #cfcfcf;
            box-shadow: 0 6px 18px rgba(0, 0, 0, .12);
            position: relative;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 5px;
        }

        .comp-name {
            font-size: 20px;
            font-weight: bold;
            font-style: italic;
        }

        .title-so {
            font-size: 20px;
            color: var(--blue);
            text-decoration: underline;
            font-weight: bold;
            text-align: right;
        }

        .so-no {
            color: var(--red);
            font-weight: bold;
            font-size: 15px;
            text-align: right;
        }

        .customer-container {
            border: 1px solid #000;
            border-radius: 8px;
            padding: 5px 12px;
            width: 100%;
            position: relative;
            margin-top: 6px;
        }

        .customer-label {
            position: absolute;
            top: -8px;
            left: 15px;
            background: #fff;
            padding: 0 5px;
            font-size: 11px;
        }

        .info-table {
            font-size: 12px;
            margin-top: 4px;
            margin-left: auto;
        }

        .info-table td {
            padding: 1px 2px;
            vertical-align: top;
        }

        .tb {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .tb th {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 5px;
            text-align: left;
            font-weight: normal;
        }

        .tb td {
            padding: 5px;
            vertical-align: top;
        }

        .text-center, .tb th.text-center {
            text-align: center;
        }

        .text-right, .tb th.text-right {
            text-align: right;
        }

        .muted {
            color: #444;
            font-size: 11px;
        }

        .note-block {
            margin-top: 24px;
            min-height: 48px;
        }

        .note-title {
            font-weight: bold;
            margin-bottom: 4px;
        }

        .footer-line {
            border-top: 1.5px solid #000;
            margin-top: 28px;
        }

        .terbilang-box {
            float: left;
            width: 60%;
            font-style: italic;
            font-weight: bold;
            text-decoration: underline;
            font-size: 11px;
            margin-top: 5px;
        }

        .summary-box {
            float: right;
            width: 35%;
            margin-top: 5px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 1px 0;
        }

        .grand-total {
            border-top: 1px solid #000;
            border-bottom: 3px double #000;
            margin-top: 5px;
            padding: 4px 0;
            font-weight: bold;
            color: var(--blue);
            font-size: 14px;
        }

        .sign-container {
            margin-top: 15px;
            clear: both;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
        }

        .sign-table {
            border-collapse: collapse;
            width: 400px;
        }

        .sign-table td {
            border: 1px solid #000;
            width: 50%;
            height: 26px;
            text-align: center;
            padding: 4px;
        }

        .sign-table .box-content {
            height: 78px;
            vertical-align: bottom;
            padding-bottom: 6px;
        }

        .meta-right {
            font-size: 10px;
            text-align: right;
            white-space: nowrap;
        }

        .print-hide {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 999;
        }

        .print-hide button {
            background: #fff;
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            z-index: 9999;
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .print-button {
            background: #007bff;
            color: white;
            border: none;
            padding: 7px 14px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 12px;
        }

        .print-button:hover {
            background: #0056b3;
        }

        @media print {
            body {
                background: #fff;
            }

            .sheet {
                margin: 0;
                border: none;
                box-shadow: none;
            }

            .no-print, .print-hide {
                display: none !important;
            }

            @page {
                size: 8.27in 5.83in;
                margin: 0;
            }
        }
    </style>
</head>

<body>
    <div class="no-print">
        <button class="print-button" onclick="window.print()">🖨️ Cetak Dokumen</button>

        {{-- Zoom Out --}}
        <button onclick="adjustZoom(-0.1)"
            style="padding: 6px 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; line-height: 1;">
            −
        </button>

        {{-- Zoom Level --}}
        <span id="zoomLabel"
            style="min-width: 48px; text-align: center; font-size: 13px; font-weight: bold; color: #333; align-self: center;">
            100%
        </span>

        {{-- Zoom In --}}
        <button onclick="adjustZoom(0.1)"
            style="padding: 6px 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; line-height: 1;">
            +
        </button>
    </div>
    @php
        $fmtQty = function ($value) {
            $num = (float) $value;
            return number_format($num, 2, ',', '.');
        };
    @endphp

    </div>

    <div class="sheet">
        <div class="header-row">
            <div>
                <div class="comp-name">{{ strtoupper($company_name) }}</div>
                @if(!empty($company_city))<div style="font-size: 12px;">{{ $company_city }}</div>@endif
            </div>
            <div>
                <div class="title-so">Sales Order</div>
                <div class="so-no">No. {{ $displayFsono ?? ($hdr->fsono ?? '-') }}</div>
            </div>
        </div>

        <div class="customer-container">
            <span class="customer-label">Customer</span>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px;">
                <div style="flex: 1;">
                    <div style="font-weight: bold;">
                        {{ trim(($hdr->fcustno ?? '') . ' - ' . ($hdr->customer_name ?? ''), ' -') ?: '-' }}
                    </div>
                    <div style="font-size: 11px; margin-top: 2px;">
                        {{ $hdr->customer_address ?? '-' }}
                    </div>
                </div>
                <div style="width: 250px;">
                    <table class="info-table" style="margin-top: 0;">
                        <tr>
                            <td style="width: 60px;">Tanggal</td>
                            <td style="width: 10px;">:</td>
                            <td>{{ $fmt($hdr->fsodate) }}</td>
                        </tr>
                        <tr>
                            <td>Tempo</td>
                            <td>:</td>
                            <td>{{ $hdr->ftempohr ?? '0' }} Hari</td>
                        </tr>
                        <tr>
                            <td>Sales</td>
                            <td>:</td>
                            <td>{{ $hdr->salesman_name ?? ($hdr->fsalesname ?? '-') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <table class="tb">
            <thead>
                <tr>
                    <th style="width: 5%; text-align: center;" class="text-center">No.</th>
                    <th style="width: 45%;">Nama Produk</th>
                    <th style="width: 13%; text-align: right;" class="text-right">Jumlah</th>
                    <th style="width: 13%; text-align: right;" class="text-right">@ Harga</th>
                    <th style="width: 8%; text-align: center;" class="text-center">Disc.%</th>
                    <th style="width: 16%; text-align: right;" class="text-right">Total Harga</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($dt as $i => $r)
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td>
                            <div style="white-space: pre-line;">{{ !empty(trim((string) ($r->fdesc ?? ''))) ? $r->fdesc : ($r->product_name ?? '-') }}</div>
                        </td>
                        <td class="text-right">{{ $fmtQty($r->fqty ?? 0) }} {{ $r->funit ?? ($r->fsatuan ?? '') }}</td>
                        <td class="text-right">{{ number_format($r->fprice ?? 0, 2, ',', '.') }}</td>
                        <td class="text-center">
                            @if (is_numeric($r->fdiscpersen))
                                {{ (float)$r->fdiscpersen == (int)$r->fdiscpersen ? (int)$r->fdiscpersen : number_format((float)$r->fdiscpersen, 2, ',', '.') }}
                            @else
                                {{ $r->fdiscpersen }}
                            @endif
                        </td>
                        <td class="text-right">{{ number_format($r->famount ?? 0, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="footer-line"></div>

        <div style="overflow: hidden;">
            <div class="terbilang-box">
                Terbilang : <br>
                # {{ strtoupper(terbilang($hdr->famountso ?? 0)) }} RUPIAH #
            </div>

            @php
                $famountgross = (float) ($hdr->famountgross ?? 0);
                $fdiscount = (float) ($hdr->fdiscount ?? 0);
                if ($famountgross <= 0) {
                    $famountgross = (float) ($hdr->famountsonet ?? 0);
                }
                $totalSetelahDisc = $famountgross - $fdiscount;
            @endphp
            <div class="summary-box">
                <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                    <tr>
                        <td style="padding: 1px 0; white-space: nowrap;">Total Harga</td>
                        <td style="width: 10px; text-align: center; padding: 1px 0;">:</td>
                        <td style="text-align: right; padding: 1px 0;">{{ number_format($famountgross, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 1px 0; white-space: nowrap; border-bottom: 1px solid #000;">Discount</td>
                        <td style="width: 10px; text-align: center; padding: 1px 0; border-bottom: 1px solid #000;">:</td>
                        <td style="text-align: right; padding: 1px 0; border-bottom: 1px solid #000;">{{ number_format($fdiscount, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 1px 0; white-space: nowrap;">Total Setelah Disc</td>
                        <td style="width: 10px; text-align: center; padding: 1px 0;">:</td>
                        <td style="text-align: right; padding: 1px 0;">{{ number_format($totalSetelahDisc, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 1px 0; white-space: nowrap;">PPN</td>
                        <td style="width: 10px; text-align: center; padding: 1px 0;">:</td>
                        <td style="text-align: right; padding: 1px 0;">{{ number_format($hdr->famountpajak ?? 0, 2, ',', '.') }}</td>
                    </tr>
                    <tr style="font-weight: bold; color: var(--blue); font-size: 13px;">
                        <td style="border-top: 1px solid #000; border-bottom: 3px double #000; padding: 4px 0; white-space: nowrap;">Grand Total</td>
                        <td style="border-top: 1px solid #000; border-bottom: 3px double #000; width: 10px; text-align: center; padding: 4px 0;">:</td>
                        <td style="border-top: 1px solid #000; border-bottom: 3px double #000; text-align: right; padding: 4px 0;">{{ number_format($hdr->famountso ?? 0, 2, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="sign-container">
            <div style="display: flex; align-items: flex-start; gap: 40px;">
                <div style="width: 160px; min-width: 140px;">
                    <div style="font-size: 11px;">Dibuat Oleh,</div>
                    <div style="margin-top: 55px; font-size: 11px; font-weight: bold; white-space: nowrap;">
                        ( {{ strtoupper($namattdfakturpenjualan ?: ($namattdpo ?: '-')) }} )
                    </div>
                </div>
                <div style="width: 160px; min-width: 140px;">
                    <div style="font-size: 11px;">Disetujui,</div>
                    <div style="margin-top: 55px; font-size: 11px; font-weight: bold; white-space: nowrap;">
                        ( {{ strtoupper($hdr->fuserapproved ?? '-') }} )
                    </div>
                </div>
            </div>

            <div class="meta-right">
                <div>Dicetak {{ strtoupper(auth('sysuser')->user()->fname ?? Auth::user()->fname ?? 'SYSTEM') }}: {{ now()->format('d-m-Y H:i') }} Hal : 1 / 1</div>
            </div>
        </div>
    </div>

    <script>
        let currentZoom = 1.0;
        function adjustZoom(delta) {
            currentZoom = Math.min(Math.max(currentZoom + delta, 0.5), 2.0);
            const target = document.querySelector('.sheet') || document.body;
            target.style.transform = `scale(${currentZoom})`;
            target.style.transformOrigin = "top center";
            document.getElementById("zoomLabel").innerText = `${Math.round(currentZoom * 100)}%`;
        }
    </script>
</body>

</html>
