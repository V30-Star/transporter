<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Approval Notification</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #333;
            margin: 0;
            padding: 20px;
            background: #f4f4f4;
        }

        .container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .1);
        }

        h1 {
            color: #333;
            font-weight: 700;
            text-decoration: underline;
            margin-top: 0;
        }

        .info-table,
        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }

        th {
            background: #f8f8f8;
            font-weight: bold;
        }

        .note-box {
            margin-top: 16px;
            font-size: 14px;
        }

        .note-top {
            font-weight: 700;
            margin-bottom: 6px;
        }

        .signature-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .signature-table td {
            padding: 8px;
            border: 1px solid #ddd;
            height: 55px;
        }

        .signature-table .head {
            font-weight: 700;
            text-align: center;
            height: auto;
        }

        .signature-table .center {
            text-align: center;
            vertical-align: bottom;
        }

        .btn-wrap {
            margin-top: 24px;
            text-align: center;
        }

        .btn {
            display: inline-block;
            padding: 12px 28px;
            margin: 4px;
            font-size: 14px;
            font-weight: bold;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
        }

        .btn-approve {
            background: #28a745;
        }

        .warning {
            color: #dc3545;
            font-size: 14px;
        }

        .muted {
            color: #777;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="container">

        <h1>Order Pembelian Approval</h1>

        <table class="info-table">
            <tr>
                <td>
                    <strong>No. PO</strong>:
                    {{ $hdr->fpono ?? '-' }}
                </td>
                <td>
                    <strong>Approver</strong>:
                    {{ $approver ?? '-' }}
                </td>
            </tr>
            <tr>
                <td>
                    <strong>Supplier</strong>:
                    {{ $hdr->fsupplier ?? '-' }}
                    @if (!empty($hdr->supplier_name))
                        — {{ $hdr->supplier_name }}
                    @endif
                </td>
                <td>
                    <strong>Tanggal</strong>:
                    @php
                        $podate = $hdr->fpodate ?? null;
                        echo $podate ? \Carbon\Carbon::parse($podate)->locale('id')->translatedFormat('d F Y') : '-';
                    @endphp
                </td>
            </tr>
            <tr>
                <td>
                    <strong>Tanggal Kirim</strong>:
                    @php
                        $kirim = $hdr->fkirimdate ?? null;
                        echo $kirim ? \Carbon\Carbon::parse($kirim)->locale('id')->translatedFormat('d F Y') : '-';
                    @endphp
                </td>
                <td>
                    <strong>Catatan</strong>:
                    {{ $hdr->fket ?? '-' }}
                </td>
            </tr>
        </table>

        <h2>Detail Produk</h2>
        <table class="product-table">
            <thead>
                <tr>
                    <th style="width:120px;">Kode</th>
                    <th>Nama Barang</th>
                    <th style="width:120px;">Qty</th>
                    <th style="width:120px;">Harga</th>
                    <th style="width:90px;">Disc%</th>
                    <th style="width:90px;">Total Harga</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($dt as $item)
                    <tr>
                        <td>{{ $item->fprdcode }}</td>
                        <td>
                            {{ $item->product_name ?? '-' }}
                            @if (!empty($item->fdesc))
                                <div class="muted">({{ $item->fdesc }})</div>
                            @endif
                        </td>
                        <td>{{ number_format((float) ($item->fqty ?? 0), 0, ',', '.') }}</td>
                        <td>{{ $item->fprice ?? '-' }}</td>
                        <td>{{ $item->fdisc ?? '-' }}</td>
                        <td>{{ $item->famount ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="muted">Tidak ada item pada PO ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="signature-table">
            <tr>
                <td class="head">Dibuat,</td>
                <td class="head">User,</td>
                <td class="head">Plant Manager,</td>
            </tr>
            <tr>
                <td class="center">{{ strtoupper($hdr->fuserid ?? '-') }}</td>
                <td></td>
                <td></td>
            </tr>
        </div>

        {{-- Approve button (opsional bila ada fprno) --}}
        @php $fpono = $hdr->fpono ?? null; @endphp
        @if ($fpono)
            <div class="btn-wrap">
                <a class="btn btn-approve" href="{{ route('approval.page.po', ['fpono' => $fpono]) }}">✅ Approve</a>
            </div>
        @else
            <p class="warning">PR number tidak tersedia.</p>
        @endif

        <p class="muted" style="text-align:right;">Hal : 1 / 1</p>
    </div>
</body>

</html>
