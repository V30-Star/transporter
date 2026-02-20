
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Tree Report</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background-color: #f2f2f2; border: 1px solid #000; padding: 8px; }
        td { border: 1px solid #ccc; padding: 6px; }
        .text-center { text-align: center; }
        .level-indent { display: inline-block; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
        .header { text-align: center; margin-bottom: 30px; }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">
            Cetak Laporan
        </button>
    </div>

    <div class="header">
        <h2>LAPORAN STRUKTUR AKUN (ACCOUNT TREE)</h2>
        <p>Tanggal Cetak: {{ date('d-m-Y H:i:s') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Akun</th>
                <th>Level</th>
                <th>Upline</th>
                <th>Urutan (Order)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @foreach($data as $row)
                <tr>
                    <td class="text-center">{{ $no++ }}</td>
                    <td>
                        {{-- Membuat indentasi berdasarkan level --}}
                        <span class="level-indent" style="margin-left: {{ ($row->flevel - 1) * 20 }}px;">
                            @if($row->flevel > 1) └── @endif 
                            <strong>{{ trim($row->faccount) }}</strong>
                        </span>
                    </td>
                    <td class="text-center">{{ $row->flevel }}</td>
                    <td class="text-center">{{ trim($row->faccupline) ?: '-' }}</td>
                    <td class="text-center">{{ $row->forder }}</td>
                    <td class="text-center">
                        {{ $row->fleafend == '1' ? 'Leaf (Ujung)' : 'Parent' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>