<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Voucher Jurnal - {{ $hdr->fjurnalno ?? '-' }}</title>
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

        .total-row td {
            border-top: 2px solid #000;
            font-weight: bold;
        }

        .footer-line {
            border-top: 1.5px solid #000;
            margin-top: 30px;
        }

        /* Terbilang box — same style as other print views */
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

        .caption-note {
            font-size: 10px;
            margin-left: 10px;
            font-style: italic;
            color: #444;
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
        <table style="width:auto; margin-top:12px; font-size:12px; border-collapse:collapse;">
            <tr>
                <td style="width:90px; padding:2px 4px;">Tanggal</td>
                <td style="padding:2px 4px;">:</td>
                <td style="padding:2px 4px;">{{ $fmt($hdr->fjurnaldate) }}</td>
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
                    <th style="width:15%;">Kode Akun</th>
                    <th style="width:30%;">Nama Akun</th>
                    <th style="width:30%;">Uraian</th>
                    <th style="width:5%; text-align:center;">D/K</th>
                    <th style="width:15%; text-align:right;">Jumlah (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($dt as $i => $r)
                    @php
                        $isDebit = strtoupper($r->fdk ?? '') === 'D';
                        $amount  = (float) ($r->famount_rp ?? $r->famount ?? 0);
                        if ($isDebit) {
                            $totalDebit += $amount;
                        } else {
                            $totalKredit += $amount;
                        }
                    @endphp
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td>{{ $r->faccount ?? '-' }}</td>
                        <td>{{ $r->account_name ?? '-' }}</td>
                        <td>{{ $r->faccountnote ?? '-' }}</td>
                        <td class="text-center" style="font-weight:bold; color:{{ $isDebit ? '#1d4ed8' : '#dc2626' }};">
                            {{ $isDebit ? 'D' : 'K' }}
                        </td>
                        <td class="text-right">
                            {{ number_format($amount, 2, ',', '.') }}
                        </td>
                    </tr>
                @endforeach

                {{-- Single "Total" row (Debit = Kredit by design) --}}
                <tr class="total-row">
                    <td colspan="5" class="text-right">Total</td>
                    <td class="text-right" style="color:#1d4ed8;">
                        {{ number_format($totalDebit, 2, ',', '.') }}
                    </td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <div class="footer-line"></div>

        {{-- Terbilang + summary --}}
        <div class="terbilang-box">
            Terbilang :<br>
            <span style="font-weight:normal; text-decoration:none; font-style:normal;">
                # {{ strtoupper(terbilang($totalDebit)) }} #
            </span>
        </div>

        <div class="summary-box">
            <div class="summary-row grand-total">
                <span>Total</span>
                <span>Rp {{ number_format($totalDebit, 2, ',', '.') }}</span>
            </div>
        </div>

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
            {{-- Caption = fjurnalnote --}}
            <div class="caption-note">
                Ket : {{ $hdr->fjurnalnote ?? '' }}
            </div>
        </div>
    </div>
</body>

</html>
