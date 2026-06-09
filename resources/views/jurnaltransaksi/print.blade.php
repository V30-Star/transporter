<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Slip Jurnal Umum - {{ $hdr->fjurnalno ?? '-' }}</title>
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

        .info-table {
            width: 100%;
            font-size: 12px;
            margin-top: 10px;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 2px 4px;
            vertical-align: top;
        }

        /* Table Journal */
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
            font-weight: bold;
            background: #f5f5f5;
        }

        .tb td {
            padding: 5px;
            vertical-align: top;
            border-bottom: 1px solid #eee;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .debit-row td {
            background: #fff;
        }

        .kredit-row td {
            background: #fafafa;
        }

        .total-row td {
            border-top: 2px solid #000;
            font-weight: bold;
        }

        .footer-line {
            border-top: 1.5px solid #000;
            margin-top: 30px;
        }

        .note-box {
            font-size: 11px;
            margin-top: 8px;
            font-style: italic;
            color: #444;
        }

        .sign-container {
            margin-top: 40px;
            clear: both;
            display: flex;
            align-items: flex-end;
        }

        .sign-table {
            border-collapse: collapse;
            width: 400px;
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
        {{-- Header --}}
        <div class="header-row">
            <div>
                <div class="comp-name">{{ strtoupper($company_name) }}</div>
                <div>{{ $company_city }}</div>
            </div>
            <div>
                <div class="title-so">Voucher Jurnal</div>
                <div class="so-no">No. {{ $hdr->fjurnalno ?? '-' }}</div>
            </div>
        </div>

        {{-- Info --}}
        <table class="info-table" style="width:auto; margin-top:12px;">
            <tr>
                <td style="width:90px;">Tanggal</td>
                <td>:</td>
                <td>{{ $fmt($hdr->fjurnaldate) }}</td>
            </tr>
            <tr>
                <td>Tipe</td>
                <td>:</td>
                <td>{{ $hdr->fjurnaltype ?? '-' }}</td>
            </tr>
            <tr>
                <td>Cabang</td>
                <td>:</td>
                <td>{{ $hdr->cabang_name ?? $hdr->fbranchcode ?? '-' }}</td>
            </tr>
            <tr>
                <td>Keterangan</td>
                <td>:</td>
                <td>{{ $hdr->fjurnalnote ?? '-' }}</td>
            </tr>
        </table>

        {{-- Detail Table --}}
        @php
            $totalDebit  = 0;
            $totalKredit = 0;
        @endphp

        <table class="tb">
            <thead>
                <tr>
                    <th style="width:5%;">No.</th>
                    <th style="width:14%;">Kode Akun</th>
                    <th style="width:30%;">Nama Akun</th>
                    <th style="width:16%;">Sub Akun</th>
                    <th style="width:5%; text-align:center;">D/K</th>
                    <th style="width:15%; text-align:right;">Jumlah (Rp)</th>
                    <th style="width:15%;">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($dt as $i => $r)
                    @php
                        $isDebit = strtoupper($r->fdk ?? '') === 'D';
                        if ($isDebit) {
                            $totalDebit += (float) ($r->famount_rp ?? $r->famount ?? 0);
                        } else {
                            $totalKredit += (float) ($r->famount_rp ?? $r->famount ?? 0);
                        }
                    @endphp
                    <tr class="{{ $isDebit ? 'debit-row' : 'kredit-row' }}">
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td>{{ $r->faccount ?? '-' }}</td>
                        <td>{{ $r->account_name ?? '-' }}</td>
                        <td>{{ $r->subaccount_name ?? ($r->fsubaccount ?? '-') }}</td>
                        <td class="text-center" style="font-weight:bold; color:{{ $isDebit ? '#1d4ed8' : '#dc2626' }};">
                            {{ $isDebit ? 'D' : 'K' }}
                        </td>
                        <td class="text-right">
                            {{ number_format((float) ($r->famount_rp ?? $r->famount ?? 0), 2, ',', '.') }}
                        </td>
                        <td>{{ $r->faccountnote ?? '-' }}</td>
                    </tr>
                @endforeach
                {{-- Totals --}}
                <tr class="total-row">
                    <td colspan="5" class="text-right">Total Debit</td>
                    <td class="text-right" style="color:#1d4ed8;">
                        {{ number_format($totalDebit, 2, ',', '.') }}
                    </td>
                    <td></td>
                </tr>
                <tr class="total-row">
                    <td colspan="5" class="text-right">Total Kredit</td>
                    <td class="text-right" style="color:#dc2626;">
                        {{ number_format($totalKredit, 2, ',', '.') }}
                    </td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <div class="footer-line"></div>

        {{-- Signature --}}
        <div class="sign-container">
            <table class="sign-table">
                <tr>
                    <td>Dibuat</td>
                    <td>Diperiksa</td>
                    <td>Disetujui</td>
                </tr>
                <tr>
                    <td class="box-content">{{ strtoupper($hdr->fuserid ?? '') }}</td>
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
