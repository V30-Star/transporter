<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Sub Account Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            background: #fff;
            padding: 20px;
        }

        /* ── Header ── */
        .header-wrap {
            width: 100%;
            border-bottom: 2px solid #000;
            margin-bottom: 6px;
        }

        .header-wrap table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-title {
            font-family: Verdana, sans-serif;
            font-size: 18px;
            font-weight: bold;
            color: #000;
        }

        .header-date {
            font-family: Verdana, sans-serif;
            font-size: 13px;
            color: #333;
            text-align: right;
            vertical-align: top;
            padding-bottom: 4px;
        }

        /* ── Table Style ── */
        .report-table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-table thead tr {
            background-color: #000099;
            /* Biru Gelap sesuai COA */
            color: #fff;
            height: 25px;
        }

        .report-table thead th {
            font-family: Verdana, sans-serif;
            font-size: 12px;
            font-weight: normal;
            border: 1px dashed #7777cc;
            padding: 4px 8px;
            text-align: left;
        }

        .report-table tbody td {
            font-family: Verdana, sans-serif;
            font-size: 11px;
            border: 1px dashed #aaa;
            padding: 6px 8px;
            white-space: nowrap;
        }

        .report-table tbody tr:hover {
            background-color: #f0f4ff;
        }

        .text-center {
            text-align: center !important;
        }

        /* ── Footer ── */
        .footer-text {
            text-align: center;
            font-family: Verdana, sans-serif;
            font-size: 12px;
            font-weight: bold;
            padding: 20px 0;
            color: #000;
        }

        /* ── No-print button ── */
        .no-print {
            margin-bottom: 20px;
        }

        .no-print button {
            padding: 8px 18px;
            cursor: pointer;
            font-size: 13px;
            background: #f3f4f6;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .no-print button:hover {
            background: #e5e7eb;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>

<body>

    {{-- Tombol cetak (Muncul di layar, hilang saat print) --}}
    <div class="no-print">
        <button onclick="window.print()">&#128438; Cetak Laporan</button>
        <button onclick="window.close()" style="margin-left: 10px;">Tutup</button>
    </div>

    {{-- ── Header ── --}}
    <div class="header-wrap">
        <table>
            <tr>
                <td rowspan="2" style="width:120px; padding:4px;">
                    @if (file_exists(public_path('images/logo.jpg')))
                        <img src="{{ asset('images/logo.jpg') }}" style="max-height:60px;" alt="Logo">
                    @else
                        <div
                            style="width:100px;height:50px;background:#eee;display:flex;align-items:center;justify-content:center;font-size:10px;color:#888;">
                            LOGO</div>
                    @endif
                </td>
                <td class="header-date">Tanggal : {{ date('j M Y') }}</td>
            </tr>
            <tr>
                <td class="header-date" style="font-weight: bold; font-size: 16px;">Master Sub Account Report</td>
            </tr>
        </table>
    </div>

    {{-- ── Data Table ── --}}
    <table class="report-table">
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="15%">Sub Account Code</th>
                <th>Sub Account Name</th>
                <th width="10%" class="text-center">Status</th>
                <th width="20%">Created By</th>
                <th width="15%">Created At</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $i => $row)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td style="font-weight: bold;">{{ $row->fsubaccountcode }}</td>
                    <td>{{ $row->fsubaccountname }}</td>
                    <td class="text-center">
                        {{ $row->fnonactive == 'N' ? 'Active' : 'Inactive' }}
                    </td>
                    <td>{{ $row->fcreatedby }}</td>
                    <td>{{ $row->fcreatedat ? \Carbon\Carbon::parse($row->fcreatedat)->format('d/m/Y') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 20px;">No data found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-text">
        *** end of report ***
    </div>

</body>

</html>
