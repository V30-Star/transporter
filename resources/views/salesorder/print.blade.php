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
            --muted: #555;
        }

        * {
            box-sizing: border-box
        }

        body {
            margin: 0;
            background: #ececec;
            font: 13px/1.45 Arial, Helvetica, sans-serif;
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
        }

        .row {
            display: flex;
            gap: 16px;
            align-items: flex-start
        }

        .left {
            flex: 1
        }

        .right {
            text-align: right
        }

        .title {
            font-weight: 700;
            font-size: 20px;
            text-decoration: underline
        }

        .mono {
            font-family: "Courier New", monospace
        }

        .muted {
            color: var(--muted)
        }

        hr {
            border: 0;
            border-top: 1px solid var(--bd);
            margin: 10px 0 8px
        }

        table.tb {
            width: 100%;
            border-collapse: collapse
        }

        .tb th,
        .tb td {
            border: 1px solid var(--bd);
            padding: 6px 8px;
            vertical-align: top
        }

        .tb th {
            background: #fff;
            font-weight: 700;
            text-align: center
        }

        .tb td.center {
            text-align: center
        }

        .tb td.right {
            text-align: right
        }

        table.sign {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px
        }

        .sign td {
            border: 1px solid var(--bd);
            height: 55px;
            vertical-align: bottom;
            padding: 6px 8px;
        }

        .sign .head {
            vertical-align: top;
            height: auto;
            font-weight: 700;
            text-align: center
        }

        .sign .small {
            font-size: 12px;
            color: var(--muted)
        }

        .footer-wrap {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .note-top {
            font-weight: 700;
            margin: 6px 0 4px;
        }

        .note-box {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
            margin-top: 10px;
            font-size: 11px;
            width: 50%;
        }

        .hr-strong {
            border: 0;
            border-top: 2px solid var(--bd);
            margin: 16px 0 10px
        }

        .print-hide {
            position: fixed;
            left: 16px;
            top: 10px;
            z-index: 10
        }

        .print-hide button {
            margin-right: 6px;
            padding: 8px 16px;
            cursor: pointer;
        }

        @media print {
            body {
                background: #fff
            }

            .sheet {
                margin: 0;
                border: none;
                box-shadow: none;
                width: 8.27in;
                min-height: 11.69in;
                padding: 0.4in 0.5in;
            }

            .print-hide {
                display: none !important
            }

            @page {
                size: A4;
                margin: 0;
            }
        }

        .footer-left {
            display: flex;
            flex-direction: column;
            width: 60%;
        }

        .footer-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            width: 40%;
            margin-left: 18px;
        }

        .total-section {
            display: flex;
            justify-content: space-between;
            margin-top: 5px;
            width: 100%;
        }

        .label {
            font-weight: 550;
        }

        .value {
            font-weight: 550;
            text-align: right;
        }

        .grand-total {
            border-top: 1px solid #000000;
            padding-top: 8px;
            margin-top: 8px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="print-hide">
        <button onclick="window.print()">Print</button>
        <button onclick="window.close()">Close</button>
    </div>

    <div class="sheet">

        <div class="row">
            <div class="left">
                <div style="font-weight:700">{{ $company_name }}</div>
                <div class="muted">{{ $company_city }}</div>
            </div>
            <div class="right">
                <div class="title">SALES ORDER</div>
                <div>No. <span class="mono">{{ $hdr->fsono ?? '-' }}</span></div>
            </div>
        </div>

        <hr>

        <table style="width:100%;border-collapse:collapse;margin-bottom:8px">
            <tr>
                <td style="border:0;padding:0 0 4px 0">
                    <strong>Kepada</strong> : {{ !empty($hdr->customer_name) ? $hdr->customer_name : '-' }}
                </td>
                <td style="border:0;padding:0;text-align:right">
                    <div><strong>Tanggal</strong> : {{ $fmt($hdr->fsodate) }}</div>
                    <div><strong>Tempo</strong> : {{ $hdr->ftempohr ?? '0' }} Hari</div>
                </td>
            </tr>
        </table>

        <br>

        <table class="tb">
            <thead>
                <tr>
                    <th style="width:30px">No.</th>
                    <th style="width:200px">Nama Barang</th>
                    <th style="width:80px">Qty.</th>
                    <th style="width:100px">Harga</th>
                    <th style="width:60px">Disc%</th>
                    <th style="width:120px">Total Harga</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($dt as $i => $r)
                    <tr>
                        <td class="center">{{ $i + 1 }}</td>
                        <td>
                            <div>{{ $r->product_name ?? ($r->fitemdesc ?? '-') }}</div>
                            @if (!empty($r->fitemno))
                                <div class="muted" style="font-size:11px">({{ $r->fitemno }})</div>
                            @endif
                        </td>
                        <td class="center">
                            {{ number_format((float) ($r->fqty ?? 0), 0, ',', '.') }}
                            {{ $r->funit ?? '' }}
                        </td>
                        <td class="right">
                            {{ number_format((float) ($r->fprice ?? 0), 0, ',', '.') }}
                        </td>
                        <td class="center">{{ number_format((float) ($r->fdiscpersen ?? 0), 2, ',', '.') }}%</td>
                        <td class="right">{{ number_format((float) ($r->famount ?? 0), 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <br>
        <br>
        <hr class="hr-strong">

        <div class="footer-wrap">
            <div class="footer-left">
                <div class="note-box">
                    <div class="note-top">Terbilang :</div>
                    <div>#{{ ucwords(trim(terbilang($hdr->famountso ?? 0))) }} Rupiah#</div>
                </div>
                @if (!empty($hdr->fket))
                    <div style="margin-top:10px">
                        <div class="note-top">Keterangan :</div>
                        <div style="font-size:11px">{{ $hdr->fket }}</div>
                    </div>
                @endif
            </div>

            <div class="footer-right">
                <div class="total-section">
                    <div class="label">TOTAL HARGA :</div>
                    <div class="value">Rp {{ number_format((float) ($hdr->famountsonet ?? 0), 0, ',', '.') }}</div>
                </div>

                <div class="total-section">
                    <div class="label">PPN :</div>
                    <div class="value">Rp {{ number_format((float) ($hdr->famountpajak ?? 0), 0, ',', '.') }}</div>
                </div>

                <div class="total-section grand-total">
                    <div class="label">GRAND TOTAL :</div>
                    <div class="value">Rp {{ number_format((float) ($hdr->famountso ?? 0), 0, ',', '.') }}</div>
                </div>

                <table class="sign">
                    <tr>
                        <td class="head">Dibuat,</td>
                        <td class="head">Disetujui,</td>
                        <td class="head">Diterima,</td>
                    </tr>
                    <tr>
                        <td class="center" style="vertical-align:bottom">
                            {{ strtoupper($hdr->fusercreate ?? '') }}
                        </td>
                        <td></td>
                        <td></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</body>

</html>
