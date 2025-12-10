<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Assembling - {{ $hdr->fstockmtno ?? '-' }}</title>
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

        /* --- PERUBAHAN UTAMA UNTUK A4 --- */
        .sheet {
            width: 8.27in;
            /* Lebar A4 */
            min-height: 11.69in;
            /* Tinggi A4 */
            margin: 0.4in auto;
            padding: 0.4in 0.5in;
            background: #fff;
            border: 1px solid #cfcfcf;
            box-shadow: 0 6px 18px rgba(0, 0, 0, .12);
        }

        /* -------------------------------- */

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
            /* Mengembalikan lebar relatif */
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
                width: 8.27in;
                /* A4 Print Width */
                min-height: 11.69in;
                /* A4 Print Height */
                padding: 0.4in 0.5in;
            }

            .print-hide {
                display: none !important
            }

            @page {
                size: A4;
                /* Mengatur halaman cetak ke A4 */
                margin: 0;
            }
        }

        .footer-left {
            display: flex;
            flex-direction: column;
            width: 60%;
            /* Disesuaikan untuk A4 */
        }

        .footer-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            width: 40%;
            /* Disesuaikan untuk A4 */
            margin-left: 18px;
            /* Menggunakan gap yang lebih konsisten */
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

        .sign td {
            padding: 8px 10px;
            border: 1px solid var(--bd);
        }

        .sign .head {
            font-weight: 700;
            text-align: center;
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

        <!-- Header -->
        <div class="row">
            <div class="left">
                <div style="font-weight:700">{{ $company_name }}</div>
                <div class="muted">{{ $company_city }}</div>
            </div>
            <div class="right">
                <div class="title">ASSEMBLING</div>
                <div>No. <span class="mono">{{ $hdr->fstockmtno ?? '-' }}</span></div>
            </div>
        </div>

        <hr>

        <table style="width:100%;border-collapse:collapse;margin-bottom:8px">
            <tr>
                <td colspan="2" style="border:0;padding:0 0 4px 0">

                    <div>
                        <strong>Supplier</strong> :
                        {{ !empty($hdr->supplier_name) ? $hdr->supplier_name : '' }}
                    </div>

                    <div>
                        <strong>Gudang</strong> :
                        {{ !empty($hdr->fwhnamen) ? $hdr->fwhnamen : '' }}
                    </div>

                </td>
                <td style="border:0;padding:0;text-align:right">
                    <div><strong>Tanggal</strong> : {{ $fmt($hdr->fstockmtdate) }}</div>
                </td>
            </tr>
        </table>

        <!-- Tabel item -->
        <table class="tb">
            <thead>
                <tr>
                    <th style="width:5px">No.</th>
                    <th style="width:50px">Kode Barang</th>
                    <th style="width:200px">Nama Barang</th>
                    <th style="width:50px">Qty.</th>
                    <th style="width:50px">Satuan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($dt as $i => $r)
                    <tr>
                        <td class="center">{{ $i + 1 }}</td>
                        <td>{{ $r->product_code ?? '' }}</td>
                        <td>
                            <div>{{ $r->product_name ?? '-' }}</div>
                            @if (!empty($r->fdesc))
                                <div class="muted">({{ $r->fdesc }})</div>
                            @endif
                        </td>
                        <td class="center">{{ number_format((float) ($r->fqty ?? 0), 0, ',', '.') }}
                        </td>
                        <td class="center">{{ $r->fsatuan }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <br>
        <br>
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
        <style>
            .grand-total {
                border-top: 1px solid #000000;
                padding-top: 8px;
                margin-top: 8px;
                font-weight: bold;
                /* Optional: untuk menekankan grand total */
            }
        </style>
    </div>
</body>

</html>
