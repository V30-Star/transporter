<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <title>{{ "Faktur Pembelian" }} - {{ $displayFstockmtno ?? ($hdr->fstockmtno ?? '-') }}</title>
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
            min-height: 5.83in;
            margin: 0.2in auto;
            padding: 0.25in 0.4in;
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

        /* Box Container (Supplier/Info) */
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

        .text-right, .tb th.text-right {
            text-align: right;
        }

        .text-center, .tb th.text-center {
            text-align: center;
        }

        /* Footer Section */
        .footer-line {
            border-top: 1.5px solid #000;
            margin-top: 40px;
            /* Jarak disesuaikan untuk Faktur Pembelian */
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

    <div class="sheet">
        <div class="header-row">
            <div>
                <div class="comp-name">{{ strtoupper($company_name) }}</div>
                @if(!empty($company_address1))<div style="font-size: 12px;">{{ $company_address1 }}</div>@endif
                @if(!empty($company_address2))<div style="font-size: 12px;">{{ $company_address2 }}</div>@endif
                <div class="customer-container">
                    <span class="customer-label">{{ "Supplier" }}</span>
                    <div style="font-weight: bold;">{{ $hdr->supplier_name ?? '-' }}</div>
                    <div style="font-size: 11px;">
                        {{ "Gudang" }} : {{ $hdr->fwhnamen ?? '-' }}
                    </div>
                </div>
            </div>
            <div>
                <div class="title-so">{{ "Faktur Pembelian" }}</div>
                <div class="so-no">{{ "No" }}. {{ $displayFstockmtno ?? ($hdr->fstockmtno ?? '-') }}</div>
                <table class="info-table">
                    <tr>
                        <td>{{ "Tanggal" }}</td>
                        <td>:</td>
                        <td>{{ $fmt($hdr->fstockmtdate) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <table class="tb">
            <thead>
                <tr>
                    <th style="width: 5%; text-align: center;" class="text-center">{{ "No" }}.</th>
                    <th style="width: 20%;">{{ "Kode Barang" }}</th>
                    <th style="width: 50%;">{{ "Nama Barang" }}</th>
                    <th style="width: 10%; text-align: center;" class="text-center">{{ "Qty." }}</th>
                    <th style="width: 15%; text-align: center;" class="text-center">{{ "Satuan" }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($dt as $i => $r)
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td>{{ $r->product_code ?? '-' }}</td>
                        <td>
                            <div style="white-space: pre-line;">{{ !empty(trim((string) ($r->fdesc ?? ''))) ? $r->fdesc : ($r->product_name ?? '-') }}</div>
                        </td>
                        <td class="text-center">{{ number_format((float) ($r->fqty ?? 0), 2, ',', '.') }}</td>
                        <td class="text-center">{{ $r->fsatuan ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="footer-line"></div>

        <div class="terbilang-box">
            {{ "Note" }} : <br>
            <span
                style="font-weight: normal; text-decoration: none; font-style: normal;">{{ $hdr->fket ?? '-' }}</span>
        </div>

        <div class="summary-box">
            <div style="text-align: right; font-style: italic; font-size: 10px; color: #555;">
                * {{ "Dokumen ini sah sebagai bukti penerimaan stok." }}
            </div>
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
                    <div style="font-size: 11px;">Disetujui,</div>
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

