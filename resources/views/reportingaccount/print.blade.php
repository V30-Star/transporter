<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Account Tree Report</title>
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

        /* ── Tree Table ── */
        .tree-table {
            width: 100%;
            border-collapse: collapse;
        }

        .tree-table thead tr {
            background-color: #000099;
            color: #fff;
            height: 22px;
        }

        .tree-table thead th {
            font-family: Verdana, sans-serif;
            font-size: 12px;
            font-weight: normal;
            border: 1px dashed #7777cc;
            padding: 2px 6px;
            white-space: nowrap;
        }

        .tree-table tbody tr:hover {
            background-color: #f0f4ff;
        }

        .tree-table tbody td {
            padding: 0px 4px;
            /* Kecilkan padding vertikal agar garis menyambung */
            line-height: 1;
        }

        .ti {
            width: 20px;
            /* Sesuaikan lebar langkah identasi */
            height: 20px;
        }

        .tree-table tbody td {
            font-family: Verdana, sans-serif;
            font-size: 11px;
            border: 1px dashed #aaa;
            padding: 2px 4px;
            white-space: nowrap;
        }

        .tree-table tfoot td {
            border-top: 1px dashed #aaa;
            padding: 4px;
        }

        /* ── Tree glyph images (inline SVG data-URI pengganti gif) ── */
        .ti {
            display: inline-block;
            width: 16px;
            height: 16px;
            vertical-align: middle;
        }

        /* garis tegak lurus  │  */
        .ti-line {
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16'%3E%3Cline x1='8' y1='0' x2='8' y2='16' stroke='%23555' stroke-width='1.5'/%3E%3C/svg%3E") no-repeat center;
        }

        /* ├── joinbottom */
        .ti-joinbot {
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16'%3E%3Cline x1='8' y1='0' x2='8' y2='16' stroke='%23555' stroke-width='1.5'/%3E%3Cline x1='8' y1='8' x2='16' y2='8' stroke='%23555' stroke-width='1.5'/%3E%3C/svg%3E") no-repeat center;
        }

        /* └── join (corner) */
        .ti-join {
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16'%3E%3Cline x1='8' y1='0' x2='8' y2='8' stroke='%23555' stroke-width='1.5'/%3E%3Cline x1='8' y1='8' x2='16' y2='8' stroke='%23555' stroke-width='1.5'/%3E%3C/svg%3E") no-repeat center;
        }

        /* spasi kosong */
        .ti-empty {
            background: none;
        }

        /* ── Footer ── */
        .footer {
            text-align: center;
            font-family: Verdana, sans-serif;
            font-size: 13px;
            font-weight: bold;
            padding: 10px 0;
        }

        /* ── No-print button ── */
        .no-print {
            margin: 10px;
        }

        .no-print button {
            padding: 8px 18px;
            cursor: pointer;
            font-size: 13px;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                margin: 0;
            }
        }
    </style>
</head>

<body onload="window.print()">

    {{-- Tombol cetak (hilang saat print) --}}
    <div class="no-print">
        <button onclick="window.print()">&#128438; Cetak Laporan</button>
    </div>

    @php
        /* ────────────────────────────────────────────────────
         * Persiapan data untuk render tree ala legacy PHP
         * ──────────────────────────────────────────────────── */
        $rows = $data->values(); // reindex 0-based
        $nrows = $rows->count();
        $nBegin = $nrows > 0 ? $rows[0]->flevel : 1;

        $lPreviousLeafEnd = true;
        $nPreviousLevel = $nBegin;

        /**
         * cTree adalah string "token" — sama persis pola legacy:
         *   $8 = kosong/spasi
         *   $9 = garis tegak │
         *   $1 = ├── (joinbottom)
         *   $2 = └── (join/corner)
         *
         * Setelah dirakit, token diganti jadi HTML span.
         */
        $cTree = '';
    @endphp

    {{-- ── Header ── --}}
    <div class="header-wrap">
        <table>
            <tr>
                <td rowspan="2" style="width:120px; padding:4px;">
                    {{-- Logo (ganti path sesuai project) --}}
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
                <td class="header-date">Account Tree</td>
            </tr>
            <tr>
                <td colspan="2" style="height:6px;"></td>
            </tr>
            <tr>
                <td colspan="2" style="padding-bottom:4px;">

                    {{-- ── Tree Table ── --}}
                    <table class="tree-table">
                        <thead>
                            <tr>
                                <th style="text-align:left;">&nbsp;Account&nbsp;</th>
                                <th>&nbsp;D/K&nbsp;</th>
                                <th>&nbsp;Sub Account&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $i => $row)
                                @php
                                    $nEnd = $row->flevel;
                                    $cetak = '';

                                    if ($i == 0) {
                                        $nBegin = $nEnd;
                                        $cTree = '';
                                    } else {
                                        if ($nEnd > $nPreviousLevel) {
                                            $cTree .= $lPreviousLeafEnd ? '$8' : '$9';
                                        } elseif ($nEnd < $nPreviousLevel) {
                                            $cTree = substr($cTree, 0, ($nEnd - $nBegin) * 2);
                                        }

                                        $symbol = $row->fleafend == '1' ? '$2' : '$1';
                                        $cetak = $cTree . $symbol;
                                    }

                                    // Set state — hanya di sini, tidak ada duplikasi
                                    $nPreviousLevel = $nEnd;
                                    $lPreviousLeafEnd = $nEnd === $nBegin ? true : $row->fleafend == '1';

                                    /* Ganti token → HTML */
                                    $cetak = str_replace(
                                        ['$9', '$8', '$1', '$2'],
                                        [
                                            '<span class="ti ti-line"></span>',
                                            '<span class="ti ti-empty"></span>',
                                            '<span class="ti ti-join"></span>',
                                            '<span class="ti ti-join"></span>',
                                        ],
                                        $cetak,
                                    );
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td>
                                        <div style="display: flex; align-items: center;">
                                            {!! $cetak !!}
                                            <span
                                                style="font-family:'Courier New', monospace; font-size:11px; margin-left: 5px;">
                                                @if ($row->fend == 0)
                                                    <strong style="font-weight: 900;">
                                                        {{ trim($row->faccount) }} {{ trim($row->faccname) }}
                                                    </strong>
                                                @else
                                                    {{ trim($row->faccount) }} {{ trim($row->faccname) }}
                                                @endif
                                            </span>
                                        </div>
                                    </td>
                                    <td style="text-align:center;">{{ $row->fnormal == 'D' ? 'Debit' : 'Kredit' }}</td>
                                    <td style="text-align:center;">{{ $row->fhavesubaccount == 1 ? 'Yes' : 'No' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6">&nbsp;</td>
                            </tr>
                        </tfoot>
                    </table>

                </td>
            </tr>
            <tr>
                <td colspan="2" style="height:30px; vertical-align:bottom; text-align:center; padding-bottom:6px;">
                    <span class="footer">*** end of report ***</span>
                </td>
            </tr>
        </table>
    </div>

</body>

</html>
