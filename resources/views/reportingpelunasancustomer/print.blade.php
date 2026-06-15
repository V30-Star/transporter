<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pelunasan Customer</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #000;
            background-color: #f5f5f5;
        }

        /* A4 Container (Untuk Tampilan Layar) */
        .a4-container {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: white;
            padding: 15mm;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header-section {
            position: relative;
            margin-bottom: 15px;
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .header-section h2 {
            font-size: 18px;
            margin-bottom: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #1e3a8a;
        }

        .filter-info {
            font-size: 10px;
            color: #333;
            margin-bottom: 5px;
        }

        /* --- VOUCHER HEADER STYLES (8 Kolom) --- */
        .voucher-header-labels,
        .voucher-header {
            display: grid;
            grid-template-columns: 26mm 18mm 32mm 46mm 18mm 18mm 22mm 10mm;
            gap: 3px;
            font-size: 9px;
            padding: 8px 5px;
        }

        .voucher-header-labels {
            font-weight: bold;
            background-color: #f0f0f0;
            border: 1px solid #000;
            border-bottom: 2px solid #000;
            margin-bottom: 2px;
        }

        .voucher-header {
            font-weight: bold;
            border-left: 1px solid #ccc;
            border-right: 1px solid #ccc;
            background-color: #fff;
            padding: 6px 5px;
        }

        /* --- VOUCHER DETAIL STYLES (10 Kolom) --- */
        .voucher-detail-labels,
        .voucher-detail {
            display: grid;
            grid-template-columns: 5mm 28mm 12mm 18mm 25mm 18mm 22mm 22mm 22mm 18mm;
            gap: 3px;
            font-size: 8px;
            padding: 4px 5px;
        }

        .voucher-detail-labels {
            font-weight: bold;
            color: #1e3a8a;
            background-color: #eff6ff;
            border: 1px solid #ccc;
            border-bottom: 1px solid #ccc;
            margin-top: 2px;
            padding: 6px 5px;
        }

        /* Indent untuk kolom pertama pada data detail */
        .voucher-detail>div:first-child {
            padding-left: 5mm;
        }

        .voucher-detail {
            color: #1e3a8a;
            border-left: 1px solid #ccc;
            border-right: 1px solid #ccc;
            background-color: #fff;
        }

        /* Text alignment untuk header parent */
        .voucher-header-labels>div:nth-child(5),
        .voucher-header-labels>div:nth-child(6),
        .voucher-header-labels>div:nth-child(7),
        .voucher-header>div:nth-child(5),
        .voucher-header>div:nth-child(6),
        .voucher-header>div:nth-child(7) {
            text-align: right;
        }

        /* Text alignment untuk detail child */
        .voucher-detail-labels>div:nth-child(6),
        .voucher-detail-labels>div:nth-child(7),
        .voucher-detail-labels>div:nth-child(8),
        .voucher-detail-labels>div:nth-child(9),
        .voucher-detail-labels>div:nth-child(10),
        .voucher-detail>div:nth-child(6),
        .voucher-detail>div:nth-child(7),
        .voucher-detail>div:nth-child(8),
        .voucher-detail>div:nth-child(9),
        .voucher-detail>div:nth-child(10) {
            text-align: right;
        }

        /* Wrap text untuk kolom yang panjang */
        .voucher-header>div:nth-child(4),
        .voucher-detail>div:nth-child(5) {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .separator {
            border-bottom: 1px solid #ccc;
            margin: 8px 0;
            clear: both;
        }

        /* Grand Total Styles */
        .grand-total-section {
            margin-top: 20px;
            border-top: 2px solid #000;
            padding-top: 10px;
        }

        .grand-total-header {
            display: grid;
            grid-template-columns: 26mm 18mm 32mm 46mm 18mm 18mm 22mm 10mm;
            gap: 3px;
            font-size: 10px;
            font-weight: bold;
            padding: 8px 5px;
            background-color: #333;
            color: white;
        }

        .grand-total-detail {
            display: grid;
            grid-template-columns: 5mm 28mm 12mm 18mm 25mm 18mm 22mm 22mm 22mm 18mm;
            gap: 3px;
            font-size: 9px;
            font-weight: bold;
            padding: 8px 5px;
            background-color: #f0f0f0;
            border: 1px solid #333;
        }

        /* Account Summary styling matching theme */
        .summary-account-section {
            margin-top: 25px;
            border-top: 2px solid #1e3a8a;
            padding-top: 15px;
        }

        .summary-account-section h3 {
            font-size: 12px;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table th, 
        .summary-table td {
            border: 1px solid #ccc;
            padding: 6px 8px;
            font-size: 9px;
        }

        .summary-table th {
            background-color: #eff6ff;
            color: #1e3a8a;
            font-weight: bold;
            text-align: left;
        }

        .summary-table th.right,
        .summary-table td.right {
            text-align: right;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            font-style: italic;
        }

        .info-tambahan {
            position: absolute;
            top: 0;
            right: 0;
            font-size: 10px;
            color: #333;
            margin-top: 0;
            text-align: left;
            display: block;
            line-height: 1.4;
        }

        .info-tambahan .info-label {
            font-weight: bold;
            display: inline-block;
            width: 40px;
            text-align: left;
        }

        .meta-info-kiri {
            position: absolute;
            top: 10mm;
            left: 0mm;
            font-size: 10px;
            color: #333;
            font-weight: bold;
            text-align: left;
            line-height: 1.4;
        }

        /* --- PRINT STYLES --- */
        @media print {
            body {
                background-color: white !important;
                margin: 0;
                padding: 0;
            }

            .a4-container {
                width: 100%;
                margin: 0;
                padding: 10mm;
                box-shadow: none;
                min-height: auto;
                position: relative;
                page-break-after: always;
            }

            .a4-container:last-child {
                page-break-after: avoid;
            }

            .voucher-header-labels,
            .voucher-detail-labels {
                background-color: transparent !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }

            @page {
                size: A4 portrait;
                margin: 10mm;
            }

            .voucher-header,
            .voucher-detail {
                page-break-inside: avoid;
            }

            .separator {
                border-bottom: 1px dashed #666;
            }
        }

        .no-print {
            position: fixed;
            top: 10px;
            left: 10px;
            display: flex;
            gap: 8px;
            z-index: 1000;
        }

        .print-button,
        .excel-button {
            padding: 10px 20px;
            font-size: 14px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .print-button {
            background-color: #3b82f6;
            color: white;
        }

        .print-button:hover {
            background-color: #2563eb;
        }

        .excel-button {
            background-color: #22c55e;
            color: white;
        }

        .excel-button:hover {
            background-color: #16a34a;
        }

        /* Zoom Controls */
        .zoom-controls {
            display: flex;
            align-items: center;
            gap: 5px;
            background: #fff;
            padding: 5px 10px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .zoom-btn {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 4px;
            background: #6b7280;
            color: white;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .zoom-btn:hover {
            background: #4b5563;
        }

        .zoom-level {
            min-width: 50px;
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            color: #333;
        }

        /* Report Container untuk Zoom */
        .report-wrapper {
            transform-origin: top center;
            transition: transform 0.2s ease;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .report-wrapper {
                transform: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="no-print">
        <button class="print-button" onclick="window.print()">🖨️ Cetak Laporan</button>
        <a href="{{ route('reportingpelunasancustomer.exportExcel', request()->query()) }}" class="excel-button">
            📊 Download Excel
        </a>
        <div class="zoom-controls">
            <button class="zoom-btn" onclick="zoomOut()">➖</button>
            <span class="zoom-level" id="zoomLevel">100%</span>
            <button class="zoom-btn" onclick="zoomIn()">➕</button>
            <button class="zoom-btn" onclick="resetZoom()">↺</button>
        </div>
    </div>

    <div class="report-wrapper" id="reportWrapper">
        @if ($chunkedData->isEmpty())
            {{-- Jika tidak ada data --}}
            <div class="a4-container">
                <div class="header-section">
                    <div class="meta-info-kiri">
                        Account: {{ $filters['account_no'] !== '' ? $filters['account_no'] : 'Semua' }}<br>
                        Customer: {{ $filters['customer_from'] ?: 'Awal' }} s/d {{ $filters['customer_to'] ?: 'Akhir' }}
                    </div>
                    <h2>Laporan Pelunasan Customer</h2>
                    <div class="info-tambahan">
                        <div>
                            <span class="info-label">Tanggal:</span>
                            {{ \Carbon\Carbon::now()->setTimezone('Asia/Jakarta')->format('d/m/Y') }}
                        </div>
                        <div>
                            <span class="info-label">Jam:</span>
                            {{ \Carbon\Carbon::now()->setTimezone('Asia/Jakarta')->format('H:i') }}
                        </div>
                        <div>
                            <span class="info-label">Hal:</span> 1 / 1
                        </div>
                        <div>
                            <span class="info-label">Opr:</span>
                            {{ $user_session->fname ?? 'Guest' }}
                        </div>
                    </div>
                </div>
                <div class="no-data">
                    Tidak ada data pelunasan customer yang ditemukan.
                </div>
            </div>
        @else
            {{-- Loop setiap chunk = 1 halaman kertas --}}
            @foreach ($chunkedData as $pageIndex => $pageData)
                <div class="a4-container">
                    <div class="header-section">
                        <div class="meta-info-kiri">
                            Account: {{ $filters['account_no'] !== '' ? $filters['account_no'] : 'Semua' }}<br>
                            Customer: {{ $filters['customer_from'] ?: 'Awal' }} s/d {{ $filters['customer_to'] ?: 'Akhir' }}<br>
                            Cabang: {{ request()->has('branch_codes') ? implode(', ', (array) request()->input('branch_codes')) : 'Semua' }}
                        </div>
                        <h2>Laporan Pelunasan Customer</h2>
                        @if ($filters['date_from'] || $filters['date_to'])
                            <div class="filter-info">
                                Periode:
                                @if ($filters['date_from'])
                                    Dari {{ \Carbon\Carbon::parse($filters['date_from'])->format('d/m/Y') }}
                                @endif
                                @if ($filters['date_to'])
                                    s/d {{ \Carbon\Carbon::parse($filters['date_to'])->format('d/m/Y') }}
                                @endif
                            </div>
                        @endif
                        <div class="info-tambahan">
                            <div class="row-info">
                                <span class="info-label">Tanggal</span>
                                <span class="info-separator">:</span>
                                <span>{{ \Carbon\Carbon::now()->setTimezone('Asia/Jakarta')->format('d/m/Y') }}</span>
                            </div>
                            <div class="row-info">
                                <span class="info-label">Jam</span>
                                <span class="info-separator">:</span>
                                <span>{{ \Carbon\Carbon::now()->setTimezone('Asia/Jakarta')->format('H:i') }}</span>
                            </div>
                            <div class="row-info">
                                <span class="info-label">Hal</span>
                                <span class="info-separator">:</span>
                                <span>{{ $pageIndex + 1 }} / {{ $totalPages }}</span>
                            </div>
                            <div class="row-info">
                                <span class="info-label">Opr</span>
                                <span class="info-separator">:</span>
                                <span>{{ $user_session->fname ?? 'Guest' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Parent Header Labels --}}
                    <div class="voucher-header-labels">
                        <div>No. Voucher</div>
                        <div>Tanggal</div>
                        <div>Account Header</div>
                        <div>Customer</div>
                        <div class="text-right">Admin Bank</div>
                        <div class="text-right">Adjustment</div>
                        <div class="text-right">Total Bayar</div>
                        <div>User</div>
                    </div>

                    {{-- Child Detail Labels --}}
                    <div class="voucher-detail-labels">
                        <div></div> {{-- Spacer --}}
                        <div>No. Ref / Faktur</div>
                        <div>Type</div>
                        <div>Tanggal Ref</div>
                        <div>Salesman</div>
                        <div class="text-right">Quantity</div>
                        <div class="text-right">Net Nota</div>
                        <div class="text-right">Discount</div>
                        <div class="text-right">Bayar</div>
                        <div class="text-right">Sisa Piutang</div>
                    </div>

                    @foreach ($pageData as $voucherNo => $voucherRecords)
                        @php
                            $first = $voucherRecords->first();
                            $adminFee = (float) ($first->fadminbank ?? 0);
                            $adjustment = (float) ($first->fadjustment ?? 0);
                            $totalVoucherPayment = (float) $voucherRecords->sum('fkasdtvalue');
                        @endphp

                        {{-- Parent row --}}
                        <div class="voucher-header">
                            <div>{{ $voucherNo }}</div>
                            <div>{{ $first->fkasmtdate ? \Carbon\Carbon::parse($first->fkasmtdate)->format('d/m/Y') : '' }}</div>
                            <div>{{ $first->account }}</div>
                            <div>{{ ($first->fcustomer ? $first->fcustomer . ' - ' : '') . $first->fcustname }}</div>
                            <div class="text-right">{{ number_format($adminFee, 2, ',', '.') }}</div>
                            <div class="text-right">{{ number_format($adjustment, 2, ',', '.') }}</div>
                            <div class="text-right">{{ number_format($totalVoucherPayment, 2, ',', '.') }}</div>
                            <div>{{ $first->fuserid }}</div>
                        </div>

                        {{-- Child details list --}}
                        @foreach ($voucherRecords as $record)
                            <div class="voucher-detail">
                                <div></div> {{-- Spacer --}}
                                <div>{{ $record->frefno }}</div>
                                <div>{{ $record->freftype }}</div>
                                <div>{{ $record->fdate_ref ? \Carbon\Carbon::parse($record->fdate_ref)->format('d/m/Y') : '' }}</div>
                                <div>{{ $record->fsalesman ?: '-' }}</div>
                                <div class="text-right">{{ number_format((float) $record->fqty, 2, ',', '.') }}</div>
                                <div class="text-right">{{ number_format((float) $record->fnetnota, 2, ',', '.') }}</div>
                                <div class="text-right">{{ number_format((float) $record->fdiscount, 2, ',', '.') }}</div>
                                <div class="text-right">{{ number_format((float) $record->fkasdtvalue, 2, ',', '.') }}</div>
                                <div class="text-right">{{ number_format((float) $record->famountremain, 2, ',', '.') }}</div>
                            </div>
                        @endforeach

                        {{-- Separator jika bukan data terakhir di halaman --}}
                        @if (!$loop->last)
                            <div class="separator"></div>
                        @endif
                    @endforeach

                    {{-- Grand Total & Account summary on the very last page --}}
                    @if ($loop->last)
                        <div class="grand-total-section">
                            {{-- Total Parent Headers --}}
                            <div class="grand-total-header">
                                <div>GRAND TOTAL</div>
                                <div></div>
                                <div></div>
                                <div></div>
                                <div class="text-right">{{ number_format($grandTotal['admin'], 2, ',', '.') }}</div>
                                <div class="text-right">{{ number_format($grandTotal['adjustment'], 2, ',', '.') }}</div>
                                <div class="text-right">{{ number_format($grandTotal['bayar'], 2, ',', '.') }}</div>
                                <div></div>
                            </div>

                            {{-- Total Details --}}
                            <div class="grand-total-detail">
                                <div>TOTAL DETAIL</div>
                                <div></div>
                                <div></div>
                                <div></div>
                                <div></div>
                                <div class="text-right">{{ number_format($grandTotal['qty'], 2, ',', '.') }}</div>
                                <div class="text-right">{{ number_format($grandTotal['net_nota'], 2, ',', '.') }}</div>
                                <div class="text-right">{{ number_format($grandTotal['discount'], 2, ',', '.') }}</div>
                                <div class="text-right">{{ number_format($grandTotal['bayar'], 2, ',', '.') }}</div>
                                <div class="text-right">{{ number_format($grandTotal['sisa'], 2, ',', '.') }}</div>
                            </div>
                        </div>

                        {{-- Account Summary Table --}}
                        <div class="summary-account-section">
                            <h3>Summary Account</h3>
                            <table class="summary-table">
                                <thead>
                                    <tr>
                                        <th>No Account</th>
                                        <th>Account</th>
                                        <th class="center">Giro Mundur</th>
                                        <th class="right">Total Bayar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($summaryRows as $row)
                                        <tr>
                                            <td>{{ $row->faccountno }}</td>
                                            <td>{{ $row->faccname }}</td>
                                            <td class="center">{{ $row->fgiromundur === '1' ? 'Ya' : 'Tidak' }}</td>
                                            <td class="right">{{ number_format((float) $row->famountpay, 2, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="center">Tidak ada data.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endforeach
        @endif
    </div>

    <script>
        // Zoom functionality
        let currentZoom = 100;
        const minZoom = 50;
        const maxZoom = 150;
        const zoomStep = 10;

        function updateZoom() {
            const wrapper = document.getElementById('reportWrapper');
            const zoomLabel = document.getElementById('zoomLevel');
            wrapper.style.transform = `scale(${currentZoom / 100})`;
            zoomLabel.textContent = currentZoom + '%';
        }

        function zoomIn() {
            if (currentZoom < maxZoom) {
                currentZoom += zoomStep;
                updateZoom();
            }
        }

        function zoomOut() {
            if (currentZoom > minZoom) {
                currentZoom -= zoomStep;
                updateZoom();
            }
        }

        function resetZoom() {
            currentZoom = 100;
            updateZoom();
        }

        // Keyboard shortcuts: Ctrl + Plus/Minus/Zero
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && (e.key === '=' || e.key === '+')) {
                e.preventDefault();
                zoomIn();
            } else if (e.ctrlKey && e.key === '-') {
                e.preventDefault();
                zoomOut();
            } else if (e.ctrlKey && e.key === '0') {
                e.preventDefault();
                resetZoom();
            }
        });
    </script>
</body>

</html>
