<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subjectLine }}</title>
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
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        h1 {
            margin: 0 0 16px;
            font-size: 22px;
            text-decoration: underline;
        }

        table {
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
            font-weight: 700;
        }

        .muted {
            color: #666;
            font-size: 12px;
        }

        .btn-wrap {
            margin-top: 24px;
            text-align: center;
        }

        .btn {
            display: inline-block;
            padding: 12px 28px;
            background: #16a34a;
            color: #fff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 700;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>{{ $title }}</h1>

        <table>
            <tbody>
                <tr>
                    <td style="width: 220px;"><strong>No. Dokumen</strong></td>
                    <td>{{ $documentNo }}</td>
                </tr>
                <tr>
                    <td><strong>Approver</strong></td>
                    <td>{{ $approver }}</td>
                </tr>
                @foreach ($fields as $field)
                    <tr>
                        <td><strong>{{ $field['label'] ?? '-' }}</strong></td>
                        <td>{{ $field['value'] ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if ($items !== [])
            <table>
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama / Deskripsi</th>
                        <th>Qty</th>
                        <th>Harga</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td>{{ $item['code'] ?? '-' }}</td>
                            <td>{{ $item['name'] ?? '-' }}</td>
                            <td>{{ $item['qty'] ?? '-' }}</td>
                            <td>{{ $item['price'] ?? '-' }}</td>
                            <td>{{ $item['total'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if (!empty($actionUrl))
            <div class="btn-wrap">
                <a class="btn" href="{{ $actionUrl }}">Approve</a>
            </div>
        @endif

        <p class="muted" style="margin-top:16px;">Email notifikasi approval dari sistem.</p>
    </div>
</body>

</html>
