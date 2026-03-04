<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Supplier Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            background: #fff;
            padding: 20px;
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
            font-size: 12px;
            color: #333;
            text-align: right;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-table thead tr {
            background-color: #000099;
            color: #fff;
            height: 25px;
        }

        .report-table thead th {
            font-family: Verdana, sans-serif;
            font-size: 11px;
            font-weight: normal;
            border: 1px dashed #7777cc;
            padding: 4px;
            text-align: left;
        }

        .report-table tbody td {
            font-family: Verdana, sans-serif;
            font-size: 10px;
            border: 1px dashed #aaa;
            padding: 5px 4px;
        }

        .text-center {
            text-align: center !important;
        }

        .no-print {
            margin-bottom: 20px;
        }

        .no-print button {
            padding: 8px 15px;
            cursor: pointer;
        }

        .footer-text {
            text-align: center;
            font-family: Verdana, sans-serif;
            font-size: 11px;
            font-weight: bold;
            padding: 20px 0;
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
        <button onclick="window.print()">&#128438; Cetak Laporan</button>
        <button onclick="window.close()" style="margin-left:10px;">Tutup</button>
    </div>

    <div class="header-wrap">
        <table>
            <tr>
                <td rowspan="2" style="width:120px;">
                    @if (file_exists(public_path('images/logo.jpg')))
                        <img src="{{ asset('images/logo.jpg') }}" style="max-height:50px;" alt="Logo">
                    @endif
                </td>
                <td class="header-date">Tanggal Cetak: {{ date('d/m/Y') }}</td>
            </tr>
            <tr>
                <td class="header-date" style="font-weight: bold; font-size: 15px;">LIST OF MASTER SUPPLIER</td>
            </tr>
        </table>
    </div>

    <table class="report-table">
        <thead>
            <tr>
                <th width="3%" class="text-center">No</th>
                <th width="10%">Supplier#</th>
                <th width="20%">Nama Supplier</th>
                <th width="25%">Alamat</th>
                <th width="12%">Telp</th>
                <th width="15%">Kontak Person</th>
                <th width="10%" class="text-center">Limit/Tempo</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $i => $row)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td style="font-weight: bold;">{{ $row->fsuppliercode }}</td>
                    <td>{{ $row->fsuppliername }}</td>
                    <td>{{ $row->faddress }} {{ $row->fcity ? ', ' . $row->fcity : '' }}</td>
                    <td>{{ $row->ftelp }}</td>
                    <td>{{ $row->fkontakperson }}</td>
                    <td class="text-center">{{ $row->ftempo ?? '0' }} Hari</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px;">Data supplier tidak ditemukan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-text">
        *** end of report ***
    </div>

</body>

</html>
