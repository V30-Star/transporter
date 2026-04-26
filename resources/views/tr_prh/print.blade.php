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
            float: right;
            font-size: 12px;
            margin-top: -68px;
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

        .text-center {
            text-align: center;
        }

        .text-right {
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
            width: 33.33%;
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
    <div class="print-hide">
        <button onclick="window.print()">PRINT</button>
        <button onclick="window.close()">CLOSE</button>
    </div>

    <div class="sheet">
        <div class="header-row">
            <div>
                <div class="comp-name">{{ strtoupper($company_name ?? 'PT. DEMO VERSION') }}</div>
                <div>{{ $company_city ?? 'Tangerang' }}</div>
            </div>
            <div>
                <div class="title-so">Permintaan Pembelian</div>
                <div class="so-no">No. {{ $hdr->fprno ?? '-' }}</div>
            </div>
        </div>

        <div style="overflow: hidden; margin-top: 10px;">
            <div class="customer-container">
                <span class="customer-label">Supplier</span>
                <div style="font-weight: bold;">
                    {{ trim(($hdr->fsupplier ?? '') . ' - ' . ($hdr->supplier_name ?? ''), ' -') ?: '-' }}
                </div>
                <div style="font-size: 11px;">
                    Cabang : {{ $hdr->cabang_name ?? ($hdr->fbranchcode ?? '-') }}
                </div>
                <div style="font-size: 11px;">
                    Keterangan : {{ $hdr->fket ?: '-' }}
                </div>
            </div>

            <table class="info-table">
                <tr>
                    <td>Tanggal</td>
                    <td>:</td>
                    <td>{{ $fmt($hdr->fprdate) }}</td>
                </tr>
                <tr>
                    <td>Tgl. Dibutuhkan</td>
                    <td>:</td>
                    <td>{{ $fmt($hdr->fneeddate) }}</td>
                </tr>
                <tr>
                    <td>Tgl. Jatuh Tempo</td>
                    <td>:</td>
                    <td>{{ $fmt($hdr->fduedate) }}</td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: right; font-size: 10px; padding-top: 12px;">Hal : 1 / 1</td>
                </tr>
            </table>
        </div>

        <table class="tb">
            <thead>
                <tr>
                    <th style="width: 5%;">No.</th>
                    <th style="width: 18%;">Kode Produk</th>
                    <th style="width: 32%;">Nama Produk</th>
                    <th style="width: 13%;" class="text-right">Qty</th>
                    <th style="width: 12%;" class="text-right">Stok</th>
                    <th style="width: 20%;">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($dt as $i => $r)
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td>{{ $r->product_code ?? '-' }}</td>
                        <td>
                            <div>{{ $r->product_name ?? '-' }}</div>
                            @if (!empty($r->fdesc))
                                <div class="muted">{{ $r->fdesc }}</div>
                            @endif
                        </td>
                        <td class="text-right">{{ number_format((float) $r->fqty, 0, ',', '.') }} {{ $r->fsatuan }}</td>
                        <td class="text-right">{{ number_format((float) ($r->stock ?? 0), 0, ',', '.') }}</td>
                        <td>{{ $r->fketdt ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">Tidak ada detail item.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="note-block">
            <div class="note-title">Catatan</div>
            <div>{{ $hdr->fket ?: '-' }}</div>
        </div>

        <div class="footer-line"></div>

        <div class="sign-container">
            <table class="sign-table">
                <tr>
                    <td>Dibuat Oleh</td>
                    <td>Diperiksa</td>
                    <td>Disetujui</td>
                </tr>
                <tr>
                    <td class="box-content">{{ strtoupper($hdr->fusercreate ?? '-') }}</td>
                    <td class="box-content">{{ strtoupper($hdr->fuserupdate ?? '-') }}</td>
                    <td class="box-content">{{ strtoupper($hdr->fuserapproved ?? '-') }}</td>
                </tr>
            </table>

            <div class="meta-right">
                <div>Dicetak: {{ now()->format('d-m-Y H:i') }}</div>
                <div>User: {{ strtoupper(auth('sysuser')->user()->fname ?? Auth::user()->fname ?? 'SYSTEM') }}</div>
            </div>
        </div>
    </div>
</body>

</html>
