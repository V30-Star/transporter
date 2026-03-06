<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Customer Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', Courier, monospace; font-size: 10px; background: #fff; padding: 15px; }
        .header-wrap { width: 100%; border-bottom: 2px solid #000; margin-bottom: 10px; }
        .report-table { width: 100%; border-collapse: collapse; }
        .report-table thead tr { background-color: #000099; color: #fff; height: 22px; }
        .report-table thead th { font-family: Verdana, sans-serif; border: 1px dashed #7777cc; padding: 4px; text-align: left; font-weight: normal; }
        .report-table tbody td { font-family: Verdana, sans-serif; border: 1px dashed #aaa; padding: 4px; vertical-align: top; }
        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; }
        .no-print { margin-bottom: 15px; }
        @media print { .no-print { display: none; } body { padding: 0; } }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()" style="padding: 6px 12px; cursor: pointer;">&#128438; Cetak Laporan</button>
        <button onclick="window.close()" style="padding: 6px 12px; margin-left:10px; cursor: pointer;">Tutup</button>
    </div>

    <div class="header-wrap">
        <table style="width: 100%">
            <tr>
                <td style="font-family: Verdana; font-size: 14px; font-weight: bold;">LIST OF MASTER CUSTOMER</td>
                <td style="text-align: right; font-family: Verdana; font-size: 10px;">Tanggal: {{ date('d/m/Y H:i') }}</td>
            </tr>
        </table>
    </div>

    <table class="report-table">
        <thead>
            <tr>
                <th width="3%" class="text-center">No</th>
                <th width="8%">Cust#</th>
                <th width="15%">Nama Customer</th>
                <th width="20%">Alamat</th>
                <th width="10%">Telp</th>
                <th width="10%">Salesman</th>
                <th width="17%">Kontak/Email</th>
                <th width="17%" class="text-right">Limit/Tempo</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $i => $row)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td><strong>{{ $row->fcustomercode }}</strong></td>
                <td>{{ $row->fcustomername }}</td>
                <td>{{ $row->faddress }}</td>
                <td>{{ $row->ftelp }}</td>
                <td>{{ $row->fsalesmanname ?? $row->fsalesman }}</td>
                <td>
                    {{ $row->fkontakperson }} <br>
                    <small>{{ $row->femail }}</small>
                </td>
                <td class="text-right">
                    L: {{ number_format($row->flimit, 0, ',', '.') }} <br>
                    T: {{ $row->ftempo }} Hari
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center">Data tidak ditemukan</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div style="text-align: center; margin-top: 20px; font-family: Verdana; font-size: 10px; font-weight: bold;">
        *** end of report ***
    </div>
</body>
</html>