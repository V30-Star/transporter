<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Notification</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #333;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }

        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #333;
            font-weight: 700;
            text-decoration: underline;
        }

        .info-table,
        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f8f8f8;
            font-weight: bold;
        }

        .footer {
            margin-top: 20px;
            font-size: 14px;
            color: #777;
        }

        .footer a {
            color: #007BFF;
            text-decoration: none;
        }

        .note-box {
            margin-top: 20px;
            font-size: 14px;
        }

        .note-top {
            font-weight: 700;
            margin-bottom: 6px;
        }

        .signature-table {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
        }

        .signature-table td {
            padding: 8px;
            border: 1px solid #ddd;
        }

        .signature-table .head {
            font-weight: 700;
            text-align: center;
        }

        .signature-table .center {
            text-align: center;
        }

        .hal {
            text-align: right;
            margin-top: 12px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Permintaan Pembelian Approval</h1>

        <!-- Header Information -->
        <table class="info-table">
            <tr>
                <td><strong>Supplier</strong>: {{ $hdr->fsupplier }} — {{ $hdr->supplier_name ?? '-' }}</td>
                <td><strong>Tanggal</strong>:
                    {{ \Carbon\Carbon::parse($hdr->fprdate)->locale('id')->translatedFormat('d F Y') }}</td>
            </tr>
            <tr>
                <td><strong>Tanggal Dibutuhkan</strong>:
                    {{ \Carbon\Carbon::parse($hdr->fneeddate)->locale('id')->translatedFormat('d F Y') }}</td>
                <td><strong>Tanggal Paling Lambat</strong>:
                    {{ \Carbon\Carbon::parse($hdr->fduedate)->locale('id')->translatedFormat('d F Y') }}</td>
            </tr>
        </table>

        <h2>Details:</h2>
        <table class="product-table">
            <thead>
                <tr>
                    <th>Nama Produk</th>
                    <th>Qty.</th>
                    <th>Unit</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($dt as $item)
                    <tr>
                        <td>{{ $item->fprdcode }} || {{ $item->product_name }}</td>
                        <td>{{ $item->fqty }} {{ $item->fsatuan }}</td>
                        <td>{{ $item->fsatuan }}</td>
                        <td>{{ $item->fdesc ?? 'No description' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Footer and Signature Section -->
        <div class="note-box">
            <div class="note-top">Note:</div>
            <div>{{ $hdr->fket ?? '-' }}</div>
        </div>

        <div class="signature-table">
            <tr>
                <td class="head">Dibuat,</td>
                <td class="head">User,</td>
                <td class="head">Plant Manager,</td>
            </tr>
            <tr>
                <td class="center">{{ strtoupper($hdr->fusercreate ?? '-') }}</td>
                <td></td>
                <td></td>
            </tr>
        </div>

        @php
            $fprno = $hdr->fprno ?? null;
        @endphp

        @if ($fprno)
            <div style="margin-top:24px; text-align:center;">
                <!-- Approve Button -->
                <a href="{{ route('approval.page', ['fprno' => $fprno]) }}"
                    style="display:inline-block;
                  padding:12px 28px;
                  margin:4px;
                  font-size:14px;
                  font-weight:bold;
                  color:#ffffff;
                  background-color:#28a745;
                  text-decoration:none;
                  border-radius:6px;
                  font-family:Arial, Helvetica, sans-serif;">
                    ✅ Approve
                </a>
            </div>
        @else
            <p style="color:#dc3545; font-size:14px;">PR number tidak tersedia.</p>
        @endif

        <p class="hal">Hal : 1 / 1</p>
    </div>
</body>

</html>
