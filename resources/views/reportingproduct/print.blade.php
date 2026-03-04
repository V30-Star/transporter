<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Product List Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 10px;
            background: #fff;
            padding: 15px;
        }

        .header-wrap {
            width: 100%;
            border-bottom: 2px solid #000;
            margin-bottom: 10px;
        }

        .header-wrap table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-date {
            font-family: Verdana, sans-serif;
            font-size: 11px;
            text-align: right;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-table thead tr {
            background-color: #000099;
            color: #fff;
            height: 22px;
        }

        .report-table thead th {
            font-family: Verdana, sans-serif;
            border: 1px dashed #7777cc;
            padding: 4px;
            text-align: left;
        }

        .report-table tbody td {
            font-family: Verdana, sans-serif;
            border: 1px dashed #aaa;
            padding: 4px;
        }

        .text-center {
            text-align: center !important;
        }

        .text-right {
            text-align: right !important;
        }

        .no-print {
            margin-bottom: 15px;
        }

        .footer-text {
            text-align: center;
            font-family: Verdana, sans-serif;
            font-size: 10px;
            font-weight: bold;
            padding: 15px 0;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                padding: 0;
            }
        }
    </style>
</head>

<body>

    <div class="no-print">
        <button onclick="window.print()" style="padding: 6px 12px; cursor: pointer;">&#128438; Cetak Laporan</button>
        <button onclick="window.close()" style="padding: 6px 12px; margin-left:10px; cursor: pointer;">Tutup</button>
    </div>

    <div class="header-wrap">
        <table>
            <tr>
                <td style="width:100px;">
                    @if (file_exists(public_path('images/logo.jpg')))
                        <img src="{{ asset('images/logo.jpg') }}" style="max-height:45px;" alt="Logo">
                    @endif
                </td>
                <td class="header-date">
                    Tanggal: {{ date('d/m/Y H:i') }}<br>
                    <strong>MASTER PRODUCT LIST</strong>
                </td>
            </tr>
        </table>
    </div>

    <table class="report-table">
        <thead>
            <tr>
                <th width="3%" class="text-center">No</th>
                <th>Nama Barang</th>
                <th width="5%" class="text-center">Sat</th>
                <th width="8%" class="text-center">Stok</th>
                @if ($showCols['hpp'])
                    <th width="10%" class="text-right">HPP</th>
                @endif
                @if ($showCols['price1'])
                    <th width="10%" class="text-right">Jual 1</th>
                @endif
                @if ($showCols['price2'])
                    <th width="10%" class="text-right">Jual 2</th>
                @endif
                @if ($showCols['price3'])
                    <th width="10%" class="text-right">Jual 3</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $i => $row)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td><strong>{{ $row->fprdname }}</strong> <br><small>[{{ $row->fprdcode }}]</small></td>
                    <td class="text-center">{{ $row->fsatuankecil }}</td>
                    <td class="text-center">{{ number_format((float) $row->fstock, 0) }}</td>

                    @if ($showCols['hpp'])
                        <td class="text-right">{{ number_format((float) $row->fhpp, 2, ',', '.') }}</td>
                    @endif
                    @if ($showCols['price1'])
                        <td class="text-right">{{ number_format((float) $row->fhargajuallevel1, 2, ',', '.') }}</td>
                    @endif
                    @if ($showCols['price2'])
                        <td class="text-right">{{ number_format((float) $row->fhargajuallevel2, 2, ',', '.') }}</td>
                    @endif
                    @if ($showCols['price3'])
                        <td class="text-right">{{ number_format((float) $row->fhargajuallevel3, 2, ',', '.') }}</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="footer-text">*** end of report ***</div>
</body>

</html>
