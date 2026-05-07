<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <title>{{ __('ui.cash_disbursement') }} - {{ $hdr->fkasmtno ?? '-' }}</title>
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
        <button onclick="window.print()">{{ __('ui.print') }}</button>
        <button onclick="window.close()">{{ __('ui.close') }}</button>
    </div>

    <div class="sheet">
        <div class="header-row">
            <div>
                <div class="comp-name">{{ strtoupper($company_name ?? 'PT. DEMO VERSION') }}</div>
                <div>{{ $company_city ?? 'Tangerang' }}</div>
            </div>
            <div>
                <div class="doc-title">{{ __('ui.cash_disbursement') }}</div>
                <div class="doc-no">{{ __('ui.number') }}. {{ $hdr->fkasmtno ?? '-' }}</div>
            </div>
        </div>

        <div class="info-wrap">
            <div class="party-box">
                <span class="party-label">{{ __('ui.information') }}</span>
                <div><strong>{{ __('ui.recipient') }}:</strong> {{ $hdr->fwhom ?: '-' }}</div>
                <div style="margin-top: 4px;"><strong>{{ __('ui.cash_bank') }}:</strong>
                    {{ trim(($hdr->faccountheader ?? '') . ' - ' . ($hdr->header_account_name ?? ''), ' -') ?: '-' }}
                </div>
                <div style="margin-top: 4px;"><strong>{{ __('ui.description') }}:</strong> {{ $hdr->fket ?: '-' }}</div>
            </div>

            <table class="info-table">
                <tr>
                    <td>{{ __('ui.date') }}</td>
                    <td>:</td>
                    <td>{{ $fmt($hdr->fkasmtdate) }}</td>
                </tr>
                <tr>
                    <td>{{ __('ui.check_no') }}</td>
                    <td>:</td>
                    <td>{{ $hdr->fnogiro ?: '-' }}</td>
                </tr>
                <tr>
                    <td>{{ __('ui.header_type') }}</td>
                    <td>:</td>
                    <td>{{ $hdr->fdkheader ?: '-' }}</td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: right; font-size: 10px; padding-top: 12px;">{{ __('ui.page') }} : 1 / 1</td>
                </tr>
            </table>
        </div>

        <table class="tb">
            <thead>
                <tr>
                    <th style="width: 5%;">{{ __('ui.number') }}.</th>
                    <th style="width: 28%;">{{ __('ui.account') }}</th>
                    <th style="width: 23%;">{{ __('ui.sub_account') }}</th>
                    <th style="width: 24%;">{{ __('ui.description') }}</th>
                    <th style="width: 8%;" class="text-center">D/K</th>
                    <th style="width: 12%;" class="text-right">{{ __('ui.payment_amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($dt as $i => $row)
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td>
                            <div>{{ trim(($row->faccount ?? '') . ' - ' . ($row->account_name ?? ''), ' -') ?: '-' }}</div>
                        </td>
                        <td>
                            <div>{{ trim(($row->fsubaccount ?? '') . ' - ' . ($row->subaccount_name ?? ''), ' -') ?: '-' }}</div>
                        </td>
                        <td>
                            <div>{{ $row->fnote ?: '-' }}</div>
                        </td>
                        <td class="text-center">{{ $row->fdk ?: '-' }}</td>
                        <td class="text-right">{{ number_format((float) ($row->fkasdtvalue ?? 0), 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">{{ __('ui.no_detail_items') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="summary">
            <div class="summary-box">
                <div class="summary-row">
                    <span>{{ __('ui.total_payment') }}</span>
                    <span>{{ number_format($totalAmount, 2, ',', '.') }}</span>
                </div>
                <div class="summary-row grand-total">
                    <span>{{ __('ui.grand_total') }}</span>
                    <span>{{ number_format($totalAmount, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <div class="footer-line"></div>

        <div class="sign-container">
            <table class="sign-table">
                <tr>
                    <td>{{ __('ui.created_by') }}</td>
                    <td>{{ __('ui.approved') }}</td>
                </tr>
                <tr>
                    <td class="box-content">{{ strtoupper(trim((string) ($hdr->fuserid ?? '-'))) }}</td>
                    <td class="box-content"></td>
                </tr>
            </table>

            <div class="meta-right">
                <div>{{ __('ui.printed_at') }}: {{ now()->format('d-m-Y H:i') }}</div>
                <div>{{ __('ui.user') }}: {{ strtoupper(auth('sysuser')->user()->fname ?? auth()->user()->fname ?? auth()->user()->name ?? 'SYSTEM') }}</div>
            </div>
        </div>
    </div>
</body>

</html>
