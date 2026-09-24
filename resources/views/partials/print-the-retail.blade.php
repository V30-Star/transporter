@if(function_exists('is_retail_the') && is_retail_the())
<style id="the-retail-print-style">
    :root {
        --fg: #000 !important;
        --bd: #000 !important;
        --blue: #000 !important;
        --red: #000 !important;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        background: #ececec;
        font: 12px Arial, Helvetica, sans-serif !important;
        color: var(--fg) !important;
    }

    .sheet td {
        font-weight: normal !important;
    }

    .sheet tr.gt td {
        font-weight: bold !important;
    }

    /* Ukuran kertas A5 Portrait sesuai retail */
    @page {
        size: 5.83in 8.27in !important;
        margin: 0 !important;
    }

    .sheet {
        width: 5.83in !important;
        height: 8.27in !important;
        overflow: hidden !important;
        margin: 0.2in auto !important;
        padding: 0.3in 1.90in 0.2in 0.2in !important;
        background: #fff;
        border: 1px solid #cfcfcf;
        box-shadow: 0 6px 18px rgba(0, 0, 0, .12);
        position: relative;
        box-sizing: border-box !important;
        font-weight: normal !important;
    }

    .header-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 5px;
    }

    .comp-name {
        font-size: 17px !important;
        font-weight: bold !important;
        font-style: italic !important;
        text-align: left;
    }

    .comp-city {
        font-size: 11px !important;
        font-weight: bold !important;
        text-align: left;
        margin-top: 1px;
    }

    .title-so, .title-doc, [class*="title-"] {
        font-size: 17px !important;
        color: var(--blue) !important;
        text-decoration: underline !important;
        font-weight: bold !important;
        text-align: right;
    }

    .so-no, .doc-no, [class*="-no"] {
        color: var(--red) !important;
        font-weight: bold !important;
        font-size: 13px !important;
        text-align: right;
    }

    .customer-container {
        border: 1.5px solid #000 !important;
        border-radius: 0 !important;
        padding: 9px 5px 3px !important;
        width: 100% !important;
        position: relative;
        margin-top: 4px;
        box-sizing: border-box !important;
        font-weight: normal !important;
    }

    .customer-label {
        position: absolute;
        top: -7px;
        left: 8px;
        background: #fff !important;
        padding: 0 4px;
        font-size: 11.5px !important;
        font-weight: normal !important;
        line-height: 1;
        z-index: 2;
    }

    .info-table {
        font-size: 12px !important;
        font-weight: bold !important;
        margin-top: 0px;
        margin-left: auto;
    }

    .info-table td {
        padding: 0.5px 1px !important;
        vertical-align: top;
        font-weight: bold !important;
    }

    .tb {
        width: 100% !important;
        border-collapse: collapse;
        margin-top: 4px;
        border-bottom: 1px solid #000 !important;
        font-weight: bold !important;
    }

    .tb th {
        border-top: 1.5px solid #000 !important;
        border-bottom: 1.5px solid #000 !important;
        padding: 3px 2px !important;
        text-align: left;
        font-weight: bold !important;
        font-size: 12px !important;
    }

    .tb td {
        padding: 2.5px 2px !important;
        vertical-align: top;
        font-size: 11.5px !important;
    }

    .grand-total, .gt, tr.gt td {
        border-top: 1px solid #000 !important;
        border-bottom: 3px double #000 !important;
        margin-top: 5px;
        padding: 4px 0 !important;
        font-weight: bold !important;
        color: var(--blue) !important;
        font-size: 12.5px !important;
    }

    .sign-container {
        font-size: 11.5px !important;
    }

    .meta-right {
        font-size: 11.5px !important;
        text-align: right;
        line-height: 1.2;
    }

    /* Penyesuaian layout sempit A5 Portrait (area cetak ~3.73in) */
    .summary-box, .terbilang-box {
        float: none !important;
        width: 100% !important;
    }

    .sign-table {
        width: 100% !important;
    }

    @media print {
        html, body {
            width: 5.83in !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        * {
            color: #000 !important;
            -webkit-text-fill-color: #000 !important;
            background: transparent !important;
            box-shadow: none !important;
            text-shadow: none !important;
        }

        .sheet {
            margin: 0 !important;
            border: none !important;
            box-shadow: none !important;
            transform: none !important;
            width: 5.83in !important;
            max-width: 5.83in !important;
            height: 8.27in !important;
            max-height: 8.27in !important;
            padding: 0.3in 1.90in 0.2in 0.2in !important;
            box-sizing: border-box !important;
            overflow: hidden !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            page-break-after: always !important;
            break-after: page !important;
        }

        .sheet:last-child,
        .sheet:only-child {
            page-break-after: auto !important;
            break-after: auto !important;
        }

        .customer-label {
            background: #fff !important;
        }

        .no-print, .print-hide, #raw-templates {
            display: none !important;
        }
    }
</style>
<script>
    window.IS_RETAIL_THE = true;
</script>
@endif
