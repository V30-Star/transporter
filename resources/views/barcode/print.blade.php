<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Barcode Label</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        @page {
            size: {{ $pageWidth }}mm {{ $pageHeight }}mm;
            margin: 0mm;
        }

        body {
            background-color: #f3f4f6;
            margin: 0;
            padding: 0;
        }

        /* Screen only toolbar */
        @media screen {
            .no-print-bar {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                background: #1e293b;
                color: #ffffff;
                padding: 10px 20px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                z-index: 9999;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            }
            .no-print-bar button {
                background: #2563eb;
                color: #ffffff;
                border: none;
                padding: 8px 16px;
                border-radius: 6px;
                font-weight: bold;
                cursor: pointer;
                font-size: 14px;
            }
            .no-print-bar button:hover {
                background: #1d4ed8;
            }
            .print-container {
                margin-top: 60px;
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 20px;
                gap: 4px;
            }
            .label-row {
                background: #ffffff;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                margin-bottom: {{ $gapY ?: 2 }}mm;
            }
            .label-item {
                outline: 1px dashed #cbd5e1;
            }
        }

        /* Print media styling */
        @media print {
            html, body {
                width: {{ $pageWidth }}mm !important;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                background: transparent !important;
                overflow: hidden !important;
            }
            .no-print-bar {
                display: none !important;
            }
            .print-container {
                margin: 0 !important;
                padding: 0 !important;
                display: block !important;
            }
            .label-row {
                width: {{ $pageWidth }}mm !important;
                height: {{ $pageHeight }}mm !important;
                max-height: {{ $pageHeight }}mm !important;
                margin: 0 !important;
                margin-bottom: 0 !important;
                padding: 0 !important;
                page-break-after: auto !important;
                break-after: auto !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
            }
            .label-row:last-child {
                page-break-after: auto !important;
                break-after: auto !important;
            }
            .label-item {
                outline: none !important;
            }
        }

        .label-row {
            width: {{ $pageWidth }}mm;
            height: {{ $labelHeight }}mm;
            max-height: {{ $labelHeight }}mm;
            display: flex;
            flex-direction: row;
            page-break-after: auto;
            break-after: auto;
            page-break-inside: avoid;
            break-inside: avoid;
            overflow: hidden;
            box-sizing: border-box;
        }

        .label-row:last-child {
            page-break-after: auto;
            break-after: auto;
        }

        .label-item {
            width: {{ $labelWidth }}mm;
            max-width: {{ $labelWidth }}mm;
            height: {{ $labelHeight }}mm;
            max-height: {{ $labelHeight }}mm;
            margin-right: {{ $gapX }}mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            text-align: center;
            overflow: hidden;
            padding: {{ $labelHeight <= 18 ? '0.3mm 0.6mm' : '0.6mm 0.8mm' }};
            line-height: 1.05;
            box-sizing: border-box;
        }

        .label-item:last-child {
            margin-right: 0 !important;
        }

        .item-company {
            font-size: {{ $fontSize * 0.9 }}pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: -0.2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
            line-height: 1;
            flex-shrink: 0;
        }

        .item-name {
            font-size: {{ $fontSize * 0.85 }}pt;
            font-weight: bold;
            color: #000;
            display: -webkit-box;
            -webkit-line-clamp: {{ $labelHeight <= 18 ? 1 : 2 }};
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
            word-break: break-word;
            line-height: 1.05;
            margin: 0.5px 0;
            flex-shrink: 0;
        }

        .item-barcode {
            width: 100%;
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            margin: 0.5px 0;
        }

        .item-barcode svg {
            max-width: 100%;
            max-height: 100%;
            height: auto;
            shape-rendering: crispEdges;
        }

        .item-price {
            font-size: {{ $fontSize * 0.95 }}pt;
            font-weight: 900;
            color: #000;
            letter-spacing: -0.2px;
            white-space: nowrap;
            overflow: hidden;
            width: 100%;
            line-height: 1;
            flex-shrink: 0;
        }
    </style>
</head>
<body>

    <div class="no-print-bar">
        <div>
            <strong>Preview Barcode Label</strong> — {{ $labelWidth }}×{{ $labelHeight }} mm ({{ $columns }} Kolom)
            <span style="font-size: 12px; opacity: 0.8; margin-left: 10px;">
                (Setel Margin: <strong>None</strong> di dialog printer)
            </span>
        </div>
        <div>
            <button onclick="window.print()">
                &#128438; Cetak Sekarang
            </button>
        </div>
    </div>

    <div class="print-container">
        @foreach ($rows as $row)
            <div class="label-row">
                @foreach ($row as $label)
                    <div class="label-item">
                        {{-- Header Toko (uncomment jika ingin digunakan)
                        @if ($showCompany && !empty($companyName))
                            <div class="item-company">{{ $companyName }}</div>
                        @endif
                        --}}

                        @if ($showName && !empty($label['name']))
                            <div class="item-name">{{ $label['name'] }}</div>
                        @endif

                        @if ($showCode)
                            <div class="item-barcode">
                                <svg class="barcode"
                                    jsbarcode-format="CODE128"
                                    jsbarcode-value="{{ $label['barcode'] ?: $label['code'] }}"
                                    jsbarcode-text="{{ $label['barcode'] ?: $label['code'] }}"
                                    jsbarcode-displayvalue="true"
                                    jsbarcode-width="1.1"
                                    jsbarcode-height="{{ $labelHeight <= 18 ? min(16, (int)$barcodeHeight) : $barcodeHeight }}"
                                    jsbarcode-font="Arial"
                                    jsbarcode-fontoptions="bold"
                                    jsbarcode-fontsize="{{ max(10, min(12, (int) round($fontSize * 1.4))) }}"
                                    jsbarcode-margin="0"
                                    jsbarcode-textmargin="1">
                                </svg>
                            </div>
                        @endif

                        @if ($showPrice && $label['price'] > 0)
                            <div class="item-price">Rp {{ number_format($label['price'], 0, ',', '.') }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            try {
                JsBarcode(".barcode").init();
            } catch (e) {
                console.error("JsBarcode init error:", e);
            }

            // Auto trigger print dialog after barcodes render
            setTimeout(() => {
                window.print();
            }, 600);
        });
    </script>
</body>
</html>
