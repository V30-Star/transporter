<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <title>{{ "Penerimaan Kas" }} - {{ $hdr->fkasmtno ?? '-' }}</title>
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

        .doc-title {
            font-size: 20px;
            color: var(--blue);
            text-decoration: underline;
            font-weight: bold;
            text-align: right;
        }

        .doc-no {
            color: var(--red);
            font-weight: bold;
            font-size: 11px;
            text-align: right;
        }

        .info-wrap {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            margin-top: 14px;
        }

        .party-box {
            border: 1px solid #000;
            border-radius: 10px;
            padding: 8px 12px;
            width: 58%;
            min-height: 92px;
            position: relative;
        }

        .party-label {
            position: absolute;
            top: -8px;
            left: 15px;
            background: #fff;
            padding: 0 5px;
            font-size: 11px;
        }

        .info-table {
            width: 38%;
            font-size: 12px;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        .tb {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        .tb th {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 6px 5px;
            text-align: left;
            font-weight: normal;
        }

        .tb td {
            padding: 6px 5px;
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

        .summary {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
        }

        .summary-box {
            width: 320px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
        }

        .grand-total {
            border-top: 1px solid #000;
            border-bottom: 3px double #000;
            margin-top: 4px;
            padding: 6px 0;
            font-weight: bold;
            color: var(--blue);
            font-size: 14px;
        }

        .footer-line {
            border-top: 1.5px solid #000;
            margin-top: 28px;
        }

        .sign-container {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
        }

        .sign-table {
            border-collapse: collapse;
            width: 360px;
        }

        .sign-table td {
            border: 1px solid #000;
            width: 50%;
            height: 26px;
            text-align: center;
            padding: 4px;
        }

        .sign-table .box-content {
            height: 74px;
            vertical-align: bottom;
            padding-bottom: 6px;
        }

        .meta-right {
            font-size: 10px;
            text-align: right;
            white-space: nowrap;
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
        <button onclick="window.print()">{{ "Print" }}</button>
        <button onclick="window.close()">{{ "Tutup" }}</button>
    </div>

    <div class="sheet">
        <div class="header-row">
            <div>
                <div class="comp-name">{{ strtoupper($company_name ?? 'PT. DEMO VERSION') }}</div>
                <div>{{ $company_city ?? 'Tangerang' }}</div>
            </div>
            <div>
                <div class="doc-title">{{ "Penerimaan Kas" }}</div>
                <div class="doc-no">{{ "No" }}. {{ $hdr->fkasmtno ?? '-' }}</div>
            </div>
        </div>

        <div class="info-wrap">
            <div class="party-box">
                <span class="party-label">{{ "Informasi" }}</span>
                <div><strong>{{ "Penerima" }}:</strong> {{ $hdr->fwhom ?: '-' }}</div>
                <div style="margin-top: 4px;"><strong>{{ "Cash / Bank" }}:</strong>
                    {{ trim(($hdr->faccountheader ?? '') . ' - ' . ($hdr->header_account_name ?? ''), ' -') ?: '-' }}
                </div>
                <div style="margin-top: 4px;"><strong>{{ "Keterangan" }}:</strong> {{ $hdr->fket ?: '-' }}</div>
            </div>

            <table class="info-table">
                <tr>
                    <td style="width: 40%;"><strong>{{ "Tanggal" }}</strong></td>
                    <td>: {{ $fmt($hdr->fkasmtdate ?? null) }}</td>
                </tr>
                <tr>
                    <td><strong>{{ "No.Giro/Cek" }}</strong></td>
                    <td>: {{ $hdr->fnogiro ?: '-' }}</td>
                </tr>
                <tr>
                    <td><strong>{{ "Kode Transaksi" }}</strong></td>
                    <td>: {{ $hdr->ftrancode ?: '-' }}</td>
                </tr>
            </table>
        </div>

        <table class="tb">
            <thead>
                <tr>
                    <th style="width: 5%;" class="text-center">No</th>
                    <th style="width: 22%;">Account</th>
                    <th style="width: 18%;">Sub Account</th>
                    <th style="width: 15%;">No. Referensi</th>
                    <th>Uraian</th>
                    <th style="width: 8%;" class="text-center">D/K</th>
                    <th style="width: 12%;" class="text-right">Nilai Bayar</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($dt as $index => $row)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            {{ trim(($row->faccount ?? '') . ' - ' . ($row->account_name ?? ''), ' -') ?: '-' }}
                        </td>
                        <td>
                            {{ trim(($row->fsubaccount ?? '') . ' - ' . ($row->subaccount_name ?? ''), ' -') ?: '-' }}
                        </td>
                        <td>{{ $row->frefno ?: '-' }}</td>
                        <td>{{ $row->fnote ?: '-' }}</td>
                        <td class="text-center">{{ $row->fdk ?: '-' }}</td>
                        <td class="text-right">{{ number_format(abs((float) ($row->fkasdtvalue ?? 0)), 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center muted">Tidak ada detail transaksi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="summary">
            <div class="summary-box">
                <div class="summary-row grand-total">
                    <span>{{ "Total" }}</span>
                    <span>{{ number_format($totalAmount, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <div class="footer-line"></div>

        <div class="sign-container">
            <table class="sign-table">
                <tr>
                    <td>{{ "Dibuat" }}</td>
                    <td>{{ "Disetujui" }}</td>
                </tr>
                <tr>
                    <td class="box-content"></td>
                    <td class="box-content"></td>
                </tr>
            </table>

            <div class="meta-right">
                <div>{{ "Dicetak" }}: {{ now()->format('d/m/Y H:i:s') }}</div>
                <div>{{ "User" }}: {{ auth()->user()->fname ?? auth()->user()->name ?? '-' }}</div>
            </div>
        </div>
    </div>
</body>

</html>
