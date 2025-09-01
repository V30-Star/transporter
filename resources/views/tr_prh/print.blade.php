<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Permintaan Pembelian - {{ $hdr->fprno }}</title>
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

        /* >>> Preview (layar) punya kanvas putih dengan bayangan */
        body {
            margin: 0;
            background: #ececec;
            font: 13px/1.45 Arial, Helvetica, sans-serif;
            color: var(--fg)
        }

        .sheet {
            width: 5.5in;
            min-height: 11in;
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

        .sign .note {
            vertical-align: top
        }

        .sign .small {
            font-size: 12px;
            color: var(--muted)
        }

        .footer-wrap {
            display: grid;
            grid-template-columns: 60% 40%;
            column-gap: 18px;
            margin-top: 14px;
            min-height: 120px;
            /* bikin area footer agak tinggi */
            align-items: start;
        }


        .footer-left {
            flex: 0 0 60%
        }

        .footer-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding-bottom: 10px;
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
        }

        .note-title {
            font-weight: 700;
            margin-bottom: 6px
        }

        .hal {
            text-align: right;
            margin-top: 12px;
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
            margin-right: 6px
        }

        @media print {
            body {
                background: #fff
            }

            .sheet {
                margin: 0;
                border: none;
                box-shadow: none;
                width: 5.5in;
                min-height: 11in;
                padding: 0.4in 0.5in;
            }

            .print-hide {
                display: none !important
            }

            @page {
                size: 5.5in 11in;
                margin: 0;
            }
        }
    </style>
</head>

<body>
    <div class="print-hide">
        <button onclick="window.print()">Print</button>
        <button onclick="window.close()">Close</button>
    </div>

    <div class="sheet"><!-- <<< mulai kanvas -->

        <!-- Header -->
        <div class="row">
            <div class="left">
                <div style="font-weight:700">{{ $company_name }}</div>
                <div class="muted">{{ $company_city }}</div>
            </div>
            <div class="right">
                <div class="title">PERMINTAAN PEMBELIAN</div>
                <div>No. <span class="mono">{{ $hdr->fprno }}</span></div>
            </div>
        </div>

        <hr>

        <!-- Info supplier dan tanggal -->
        <table style="width:100%;border-collapse:collapse;margin-bottom:8px">
            <tr>
                <td style="border:0;padding:0 0 4px 0">
                    <strong>Supplier</strong> :
                    {{ $hdr->fsupplier ?? '-' }}{{ $hdr->supplier_name ? ' — ' . $hdr->supplier_name : '' }}
                </td>
                <td style="border:0;padding:0;text-align:right">
                    <div><strong>Tanggal</strong> : {{ $fmt($hdr->fprdate) }}</div>
                    <div><strong>Tgl.dibutuhkan</strong> : {{ $fmt($hdr->fneeddate) }}</div>
                    <div><strong>Tgl.Paling Lambat</strong> : {{ $fmt($hdr->fduedate) }}</div>
                </td>
            </tr>
        </table>

        <!-- Tabel item -->
        <table class="tb">
            <thead>
                <tr>
                    <th style="width:36px">No.</th>
                    <th>Nama Barang</th>
                    <th style="width:100px">Q t y.</th>
                    <th style="width:70px">Stok</th>
                    <th style="width:240px;text-align:left">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($dt as $i => $r)
                    <tr>
                        <td class="center">{{ $i + 1 }}</td>
                        <td>
                            <div class="mono">({{ $r->fprdcode }})</div>
                            <div>{{ $r->product_name ?? '-' }}</div>
                            @if (!empty($r->fdesc))
                                <div class="muted">({{ $r->fdesc }})</div>
                            @endif
                        </td>
                        <td class="center">{{ number_format($r->fqty, 0, ',', '.') }} {{ $r->fsatuan }}</td>
                        <td class="center">{{ (int) $r->stock }}</td>
                        <td>{{ $r->fketdt ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <hr class="hr-strong">

        <div class="footer-wrap">
            <!-- Kolom kiri: tanda tangan -->
            <div>
                <table class="sign">
                    <tr>
                        <td class="head">Dibuat,</td>
                        <td class="head">User,</td>
                        <td class="head">Plant Manager,</td>
                    </tr>
                    <tr>
                        <td class="center" style="vertical-align:bottom">{{ strtoupper($hdr->fuserid ?? '') }}</td>
                        <td></td>
                        <td></td>
                    </tr>
                </table>
            </div>

            <!-- Kolom kanan: Note + Hal -->
            <div class="note-box">
                <div>
                    <div class="note-top">Note :</div>
                    <div>{{ $hdr->fket ?? '' }}</div>
                </div>
                <div class="hal">Hal : 1 / 1</div>
            </div>
        </div>


    </div><!-- <<< akhir kanvas -->
</body>

</html>
