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
            }
            .label-item {
                outline: 1px dashed #cbd5e1;
            }
        }

        /* Print media styling */
        @media print {
            .no-print-bar {
                display: none !important;
            }
            body {
                background: transparent !important;
            }
            .print-container {
                margin: 0;
                padding: 0;
            }
            .label-item {
                outline: none !important;
            }
        }

        .label-row {
            width: {{ $pageWidth }}mm;
            height: {{ $labelHeight }}mm;
            display: flex;
            flex-direction: row;
            page-break-after: always;
            break-after: page;
            overflow: hidden;
            margin-bottom: {{ $gapY }}mm;
        }

        .label-item {
            width: {{ $labelWidth }}mm;
            height: {{ $labelHeight }}mm;
            margin-right: {{ $gapX }}mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            text-align: center;
            overflow: hidden;
            padding: 0.8mm 1mm;
            line-height: 1.1;
        }

        .label-item:last-child {
            margin-right: 0 !important;
        }

        .item-company {
            font-size: {{ $fontSize * 0.95 }}pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: -0.2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
        }

        .item-name {
            font-size: {{ $fontSize * 0.9 }}pt;
            font-weight: bold;
            color: #000;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
            word-break: break-word;
        }

        .item-barcode {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .item-barcode svg {
            max-width: 100%;
            height: auto;
            shape-rendering: crispEdges;
        }

        .item-price {
            font-size: {{ $fontSize * 1.05 }}pt;
            font-weight: 900;
            color: #000;
            letter-spacing: -0.2px;
            white-space: nowrap;
            overflow: hidden;
            width: 100%;
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
                        @if ($showCompany && !empty($companyName))
                            <div class="item-company">{{ $companyName }}</div>
                        @endif

                        @if ($showName && !empty($label['name']))
                            <div class="item-name">{{ $label['name'] }}</div>
                        @endif

                        <div class="item-barcode">
                            <svg class="barcode"
                                jsbarcode-format="CODE128"
                                jsbarcode-value="{{ $label['barcode'] ?: $label['code'] }}"
                                jsbarcode-text="{{ $showCode ? ($label['barcode'] ?: $label['code']) : '' }}"
                                jsbarcode-displayvalue="{{ $showCode ? 'true' : 'false' }}"
                                jsbarcode-width="1.2"
                                jsbarcode-height="{{ $barcodeHeight }}"
                                jsbarcode-fontsize="{{ $fontSize * 1.1 }}"
                                jsbarcode-margin="0"
                                jsbarcode-textmargin="1">
                            </svg>
                        </div>

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
