<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <title>{{ 'Pelunasan Customer' }} - {{ $hdr->fkasmtno ?? '-' }}</title>
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
            font-size: 20px;
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
            font-size: 15px;
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
            width: 450px;
            min-height: 78px;
            position: relative;
            margin-top: 10px;
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
            font-size: 12px;
            margin-top: 4px;
            margin-left: auto;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 1px 2px;
            vertical-align: top;
        }

        .tb {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
            font-size: 11px;
        }

        .tb th {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 6px 4px;
            text-align: left;
            font-weight: bold;
        }

        .tb td {
            padding: 5px 4px;
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
        <button onclick="window.close()" style="padding: 7px 14px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 12px;">{{ 'Tutup' }}</button>
    </div>

    <div class="sheet">
        <div class="header-row">
            <div>
                <div class="comp-name">{{ strtoupper($company_name) }}</div>
                @if(!empty($company_address1))<div style="font-size: 12px;">{{ $company_address1 }}</div>@endif
                @if(!empty($company_address2))<div style="font-size: 12px;">{{ $company_address2 }}</div>@endif
                <div class="party-box">
                    <span class="party-label">{{ 'Informasi' }}</span>
                    <div><strong>{{ 'Customer / Dari' }}:</strong> {{ $hdr->fwhom ?: '-' }}</div>
                    <div style="margin-top: 4px;"><strong>{{ 'Cash / Bank' }}:</strong>
                        {{ trim(($hdr->faccountheader ?? '') . ' - ' . ($hdr->header_account_name ?? ''), ' -') ?: '-' }}
                    </div>
                    <div style="margin-top: 4px;"><strong>{{ 'Keterangan' }}:</strong> {{ $hdr->fket ?: '-' }}</div>
                </div>
            </div>
            <div>
                <div class="doc-title">{{ 'Pelunasan Customer' }}</div>
                <div class="doc-no">{{ 'No' }}. {{ $hdr->fkasmtno ?? '-' }}</div>
                <table class="info-table">
                    <tr>
                        <td><strong>{{ 'Tanggal' }}</strong></td>
                        <td>:</td>
                        <td>{{ $fmt($hdr->fkasmtdate ?? null) }}</td>
                    </tr>
                    <tr>
                        <td><strong>{{ 'No.Giro/Cek' }}</strong></td>
                        <td>:</td>
                        <td>{{ $hdr->fnogiro ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>{{ 'Kode Transaksi' }}</strong></td>
                        <td>:</td>
                        <td>{{ $hdr->ftrancode ?: '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <table class="tb">
            <thead>
                <tr>
                    <th style="width: 4%; text-align: center;" class="text-center">No.</th>
                    <th style="width: 16%;">No. Faktur</th>
                    <th style="width: 12%; text-align: center;" class="text-center">Tgl. Faktur</th>
                    <th style="width: 17%; text-align: right;" class="text-right">Nilai Faktur</th>
                    <th style="width: 17%; text-align: right;" class="text-right">Sisa Piutang</th>
                    <th style="width: 17%; text-align: right;" class="text-right">Discount</th>
                    <th style="width: 17%; text-align: right;" class="text-right">Nilai Bayar</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($dt as $index => $row)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $row->frefno ?: '-' }}</td>
                        <td class="text-center">{{ $row->tgl_faktur ?? '-' }}</td>
                        <td class="text-right">{{ number_format((float) ($row->nilai_nota ?? 0), 2, ',', '.') }}</td>
                        <td class="text-right">{{ number_format((float) ($row->sisa_piutang ?? 0), 2, ',', '.') }}</td>
                        <td class="text-right">{{ number_format((float) ($row->fdiscount ?? 0), 2, ',', '.') }}</td>
                        <td class="text-right">{{ number_format((float) ($row->fkasdtvalue ?? 0), 2, ',', '.') }}</td>
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
                <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                    @php
                        $adminBank = (float) ($hdr->fadminbank ?? 0);
                        $adjustment = ((float) ($hdr->fadjustment ?? 0)) + ((float) ($hdr->fadjustment2 ?? 0));
                        $totalTerima = (float) ($hdr->famountpay ?? ($totalAmount - $adminBank - $adjustment));
                    @endphp
                    <tr>
                        <td style="padding: 2px 0; white-space: nowrap;">By.Admin Bank +/-</td>
                        <td style="width: 10px; text-align: center; padding: 2px 0;">:</td>
                        <td style="text-align: right; padding: 2px 0;">{{ number_format($adminBank, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 2px 0; white-space: nowrap;">Selisih/Adjust +/-</td>
                        <td style="width: 10px; text-align: center; padding: 2px 0;">:</td>
                        <td style="text-align: right; padding: 2px 0;">{{ number_format($adjustment, 2, ',', '.') }}</td>
                    </tr>
                    <tr style="font-weight: bold; color: var(--blue); font-size: 13px;">
                        <td style="border-top: 1px solid #000; border-bottom: 3px double #000; padding: 4px 0; white-space: nowrap;">Total Terima</td>
                        <td style="border-top: 1px solid #000; border-bottom: 3px double #000; width: 10px; text-align: center; padding: 4px 0;">:</td>
                        <td style="border-top: 1px solid #000; border-bottom: 3px double #000; text-align: right; padding: 4px 0;">{{ number_format($totalTerima, 2, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="footer-line"></div>

        <div class="sign-container">
            <div style="display: flex; align-items: flex-start; gap: 40px;">
                <div style="width: 160px; min-width: 140px;">
                    <div style="font-size: 11px;">Hormat Kami,</div>
                    <div style="margin-top: 55px; font-size: 11px; font-weight: bold; white-space: nowrap;">
                        ( {{ strtoupper($namattdpo ?? '-') }} )
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
