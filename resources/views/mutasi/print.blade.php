<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Mutasi Stock - {{ $hdr->fstockmtno ?? '-' }}</title>
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
            font-size: 18px;
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
            font-size: 11px;
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
            float: right;
            font-size: 12px;
            margin-top: -60px;
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

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* Footer Section */
        .footer-line {
            border-top: 1.5px solid #000;
            margin-top: 40px;
            /* Jarak disesuaikan untuk Mutasi Stock */
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
            margin-top: 30px;
            clear: both;
            display: flex;
            align-items: flex-end;
        }

        .sign-table {
            border-collapse: collapse;
            width: 450px;
            /* Lebar ditambah untuk 3 kolom */
        }

        .sign-table td {
            border: 1px solid #000;
            width: 33.33%;
            height: 25px;
            text-align: center;
        }

        .sign-table .box-content {
            height: 70px;
            vertical-align: bottom;
            padding-bottom: 5px;
        }

        .timestamp {
            font-size: 10px;
            margin-left: 10px;
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

            .print-hide {
                display: none;
            }

            @page {
                size: A4;
                margin: 0;
            }
        }
    </style>
</head>

<body>
    <div class="print-hide" style="position:fixed; top:10px; left:10px; z-index:999;">
        <button onclick="window.print()" style="padding:10px 20px; cursor:pointer;">PRINT</button>
    </div>

    <div class="sheet">
        <div class="header-row">
            <div>
                <div class="comp-name">{{ strtoupper($company_name) }}</div>
                <div>{{ $company_city }}</div>
            </div>
            <div>
                <div class="title-so">Mutasi Stock</div>
                <div class="so-no">No. {{ $hdr->fstockmtno ?? '-' }}</div>
            </div>
        </div>

        <div style="overflow: hidden; margin-top: 10px;">
            <div class="customer-container">
                <span class="customer-label">Supplier</span>
                <div style="font-weight: bold;">{{ $hdr->supplier_name ?? '-' }}</div>
                <div style="font-size: 11px;">
                    Gudang : {{ $hdr->fwhnamen ?? '-' }}
                </div>
            </div>

            <table class="info-table">
                <tr>
                    <td>Tanggal</td>
                    <td>:</td>
                    <td>{{ $fmt($hdr->fstockmtdate) }}</td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: right; font-size: 10px; padding-top: 20px;">Hal : 1 / 1</td>
                </tr>
            </table>
        </div>

        <table class="tb">
            <thead>
                <tr>
                    <th style="width: 5%;">No.</th>
                    <th style="width: 20%;">Kode Barang</th>
                    <th style="width: 50%;">Nama Barang</th>
                    <th style="width: 10%; text-align: center;">Qty.</th>
                    <th style="width: 15%; text-align: center;">Satuan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($dt as $i => $r)
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td>{{ $r->product_code ?? '-' }}</td>
                        <td>
                            <div>{{ $r->product_name ?? '-' }}</div>
                            @if (!empty($r->fdesc))
                                <div style="font-size: 10px; color: #555;">({{ $r->fdesc }})</div>
                            @endif
                        </td>
                        <td class="text-center">{{ number_format((float) ($r->fqty ?? 0), 0, ',', '.') }}</td>
                        <td class="text-center">{{ $r->fsatuan ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="footer-line"></div>

        <div class="terbilang-box">
            Note : <br>
            <span
                style="font-weight: normal; text-decoration: none; font-style: normal;">{{ $hdr->fket ?? '-' }}</span>
        </div>

        <div class="summary-box">
            <div style="text-align: right; font-style: italic; font-size: 10px; color: #555;">
                * Dokumen ini sah sebagai bukti penerimaan stok.
            </div>
        </div>

        <div class="sign-container">
            <table class="sign-table">
                <tr>
                    <td>Dibuat</td>
                    <td>User</td>
                    <td>Plant Manager</td>
                </tr>
                <tr>
                    <td class="box-content">{{ strtoupper($hdr->fusercreate ?? '') }}</td>
                    <td class="box-content"></td>
                    <td class="box-content"></td>
                </tr>
            </table>
            <div class="timestamp">
                {{ date('d/m/Y g:i:s A') }}
            </div>
        </div>
    </div>
</body>

</html>
