<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Penerimaan Barang - {{ $displayFstockmtno ?? ($hdr->fstockmtno ?? '-') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --fg: #000;
            --bd: #000;
            --blue: #0000FF;
            --red: #FF0000;
        }

        * {
            box-sizing: border-box
        }

        body {
            margin: 0;
            background: #ececec;
            font: 12px Arial, Helvetica, sans-serif;
            color: var(--fg)
        }

        .sheet {
            width: 8.27in;
            min-height: 11.69in;
            margin: 0.4in auto;
            padding: 0.4in 0.5in;
            background: #fff;
            border: 1px solid #cfcfcf;
            box-shadow: 0 6px 18px rgba(0, 0, 0, .12);
            position: relative;
        }

        /* Header Styles */
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

        /* Customer Box */
        .customer-container {
            border: 1px solid #000;
            border-radius: 10px;
            padding: 5px 12px;
            width: 450px;
            min-height: 70px;
            position: relative;
            margin-top: 10px;
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
        }

        /* Table Item */
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

        .text-right, .tb th.text-right { text-align: right; }
        .text-center, .tb th.text-center { text-align: center; }

        /* Footer Section */
        .footer-line {
            border-top: 1.5px solid #000;
            margin-top: 150px; /* Adjust based on content */
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

        /* Signature */
        .sign-container {
            margin-top: 18px;
            clear: both;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
        }

        .sign-table {
            border-collapse: collapse;
            width: 350px;
        }

        .sign-table td {
            border: 1px solid #000;
            width: 50%;
            height: 25px;
            text-align: center;
        }

        .sign-table .box-content {
            height: 70px;
            vertical-align: bottom;
            padding-bottom: 5px;
        }

        .meta-right {
            font-size: 10px;
            text-align: right;
            white-space: nowrap;
        }

        .no-print, .print-hide {
            position: fixed;
            top: 10px;
            left: 10px;
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
            body { background: #fff; }
            .sheet { margin: 0; border: none; box-shadow: none; }
            .no-print, .print-hide { display: none !important; }
            @page { size: A4; margin: 0; }
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

    <div class="sheet">
        <div class="header-row">
            <div>
                <div class="comp-name">{{ strtoupper($company_name) }}</div>
                @if(!empty($company_address1))<div style="font-size: 12px;">{{ $company_address1 }}</div>@endif
                @if(!empty($company_address2))<div style="font-size: 12px;">{{ $company_address2 }}</div>@endif
                <div class="customer-container">
                    <span class="customer-label">Customer</span>
                    <div style="font-weight: bold;">{{ $hdr->customer_name ?? 'PT. DWIBROS MULTI ENERGI' }}</div>
                    <div style="font-size: 11px; width: 350px;">
                        {{ $hdr->customer_address ?? 'MENARA CAKRAWALA LT 12, UNIT 1205A, JL. M. H. THAMRIN NO. 1 KOTA ADM. JAKARTA PUSAT' }}
                    </div>
                </div>
            </div>
            <div>
                <div class="title-so">Penerimaan Barang</div>
                <div class="so-no">No. {{ $displayFstockmtno ?? ($hdr->fstockmtno ?? '-') }}</div>
                <table class="info-table">
                    <tr>
                        <td>Tanggal</td>
                        <td>:</td>
                        <td>{{ $fmt($hdr->fsodate) ?? '21 Januari 2026' }}</td>
                    </tr>
                    <tr>
                        <td>Tempo</td>
                        <td>:</td>
                        <td>{{ $hdr->ftempohr ?? '0' }} Hari</td>
                    </tr>
                    <tr>
                        <td>Ref.PO</td>
                        <td>:</td>
                        <td>{{ $hdr->frefno ?? '001/SRI/-DME-PKS/I/' }}</td>
                    </tr>
                    <tr>
                        <td>Sales</td>
                        <td>:</td>
                        <td>{{ $hdr->fsalesname ?? '' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <table class="tb">
            <thead>
                <tr>
                    <th style="width: 5%; text-align: center;" class="text-center">No.</th>
                    <th style="width: 45%;">Nama Produk</th>
                    <th style="width: 15%; text-align: right;" class="text-right">Quantity</th>
                    <th style="width: 15%; text-align: right;" class="text-right">@ Harga</th>
                    <th style="width: 5%; text-align: center;" class="text-center">Disc.%</th>
                    <th style="width: 15%; text-align: right;" class="text-right">Total Harga</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($dt as $i => $r)
                <tr>
                    
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td style="white-space: pre-line;">{{ !empty(trim((string) ($r->fdesc ?? ''))) ? $r->fdesc : ($r->product_name ?? '-') }}</td>
                    <td class="text-right">{{ number_format($r->fqty ?? 100000, 2, ',', '.') }} {{ $r->funit ?? 'KG' }}</td>
                    <td class="text-right">{{ number_format($r->fprice ?? 1115, 2, ',', '.') }}</td>
                    <td class="text-center">{{ number_format((float)($r->fdiscpersen ?? 0), 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($r->ftotprice ?? 0, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="footer-line"></div>
        
        <div class="terbilang-box">
            Terbilang : <br>
            # {{ strtoupper(terbilang($hdr->famountmt ?? 0)) }} RUPIAH #
        </div>

        <div class="summary-box">
            <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                <tr>
                    <td style="padding: 1px 0; white-space: nowrap;">Total Harga</td>
                    <td style="width: 10px; text-align: center; padding: 1px 0;">:</td>
                    <td style="text-align: right; padding: 1px 0;">{{ number_format($hdr->famount ?? 0, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="padding: 1px 0; white-space: nowrap;">Discount</td>
                    <td style="width: 10px; text-align: center; padding: 1px 0;">:</td>
                    <td style="text-align: right; padding: 1px 0;">0,00</td>
                </tr>
                <tr>
                    <td style="padding: 1px 0; white-space: nowrap;">Total Setelah Disc</td>
                    <td style="width: 10px; text-align: center; padding: 1px 0;">:</td>
                    <td style="text-align: right; padding: 1px 0;">{{ number_format($hdr->famount ?? 0, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="padding: 1px 0; white-space: nowrap;">PPN</td>
                    <td style="width: 10px; text-align: center; padding: 1px 0;">:</td>
                    <td style="text-align: right; padding: 1px 0;">{{ number_format($hdr->famountpajak ?? 0, 2, ',', '.') }}</td>
                </tr>
                <tr style="font-weight: bold; color: var(--blue); font-size: 13px;">
                    <td style="border-top: 1px solid #000; border-bottom: 3px double #000; padding: 4px 0; white-space: nowrap;">Grand Total</td>
                    <td style="border-top: 1px solid #000; border-bottom: 3px double #000; width: 10px; text-align: center; padding: 4px 0;">:</td>
                    <td style="border-top: 1px solid #000; border-bottom: 3px double #000; text-align: right; padding: 4px 0;">{{ number_format($hdr->famountmt ?? 0, 2, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        <div class="sign-container">
            <div style="display: flex; align-items: flex-start; gap: 40px;">
                <div style="width: 160px; min-width: 140px;">
                    <div style="font-size: 11px;">Hormat Kami,</div>
                    <div style="margin-top: 55px; font-size: 11px; font-weight: bold; white-space: nowrap;">
                        ( {{ strtoupper($namattdpo ?: '-') }} )
                    </div>
                </div>
                <div style="width: 160px; min-width: 140px;">
                    <div style="font-size: 11px;">Mengetahui,</div>
                    <div style="margin-top: 55px; font-size: 11px; font-weight: bold; white-space: nowrap;">
                        ( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )
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
