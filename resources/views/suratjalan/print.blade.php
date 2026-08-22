<!doctype html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <title>Surat Jalan - {{ $displayFstockmtno ?? ($hdr->fstockmtno ?? '-') }}</title>
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
            min-height: 11.69in;
            margin: 0.4in auto;
            padding: 0.4in 0.5in;
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
            border-radius: 10px;
            padding: 5px 12px;
            width: 450px;
            min-height: 78px;
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
            width: 450px;
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
            padding: 10px 20px;
            cursor: pointer;
            margin-right: 6px;
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
                size: A4;
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
                    <span class="customer-label">Customer</span>
                    <div style="font-weight: bold;">
                        {{ trim(($hdr->fsupplier ?? '') . ' - ' . ($hdr->customer_name ?? ''), ' -') ?: '-' }}
                    </div>
                    <div style="font-size: 11px;">
                        Alamat : {{ $hdr->customer_address ?? '-' }}
                    </div>
                    <div style="font-size: 11px;">
                        Cabang : {{ $hdr->cabang_name ?? ($hdr->fbranchcode ?? '-') }}
                    </div>
                    <div style="font-size: 11px;">
                        Keterangan : {{ $hdr->fket ?: '-' }}
                    </div>
                </div>
            </div>
            <div>
                <div class="title-so">Surat Jalan</div>
                <div class="so-no">No. {{ $displayFstockmtno ?? ($hdr->fstockmtno ?? '-') }}</div>
                <table class="info-table">
                    <tr>
                        <td>Tanggal</td>
                        <td>:</td>
                        <td>{{ $fmt($hdr->fstockmtdate) }}</td>
                    </tr>
                    <tr>
                        <td>Tempo</td>
                        <td>:</td>
                        <td>{{ $hdr->ftempohr ?? '0' }} Hari</td>
                    </tr>
                    <tr>
                        <td>No. Ref</td>
                        <td>:</td>
                        <td>{{ $hdr->frefno ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Sales</td>
                        <td>:</td>
                        <td>{{ $hdr->fsalesname ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <table class="tb">
            <thead>
                <tr>
                    <th style="width: 5%; text-align: center;" class="text-center">No.</th>
                    <th style="width: 50%;">Nama Produk</th>
                    <th style="width: 15%; text-align: right;" class="text-right">Qty</th>
                    <th style="width: 30%;">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($dt as $i => $r)
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td>{{ $r->product_name ?? '-' }}</td>
                        <td class="text-right">{{ number_format((float) $r->fqty, 2, ',', '.') }} {{ $r->fsatuan ?? '' }}</td>
                        <td>{{ $r->fdesc ?: ($r->fket ?: '-') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="note-block">
            <div class="note-title">Catatan</div>
            <div>{{ $hdr->fket ?: '-' }}</div>
        </div>

        <div class="footer-line"></div>

        <div class="sign-container">
            <div style="display: flex; align-items: flex-start; gap: 40px;">
                <div style="width: 160px; min-width: 140px;">
                    <div style="font-size: 11px;">Hormat Kami,</div>
                    <div style="margin-top: 55px; font-size: 11px; font-weight: bold; white-space: nowrap;">
                        ( {{ strtoupper($namattdfakturpenjualan ?: ($namattdpo ?: '-')) }} )
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
