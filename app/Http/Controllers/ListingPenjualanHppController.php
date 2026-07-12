<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class ListingPenjualanHppController extends Controller
{
    public function index()
    {
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get();
        $salesmans = DB::table('mssalesman')->orderBy('fsalesmancode')->get();
        $customers = DB::table('mscustomer')->orderBy('fcustomercode')->get(['fcustomercode', 'fcustomername']);
        $products = DB::table('msprd')->orderBy('fprdcode')->get(['fprdcode', 'fprdname']);
        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('listingpenjualanhpp.index', compact('branches', 'salesmans', 'customers', 'products', 'isAuthorized', 'userBranchCode'));
    }

    public function print(Request $request)
    {
        $rows = $this->buildRows($request);

        return view('listingpenjualanhpp.print', [
            'groupedData' => $rows->groupBy('fsono'),
            'rows' => $rows,
            'request' => $request,
            'user_session' => auth('sysuser')->user() ?? auth()->user(),
        ]);
    }

    private function buildRows(Request $request)
    {
        $invoice = DB::table('tranmt as m')
            ->leftJoin('trandt as d', 'm.fsono', '=', 'd.fsono')
            ->leftJoin('mscustomer as c', 'm.fcustno', '=', 'c.fcustomercode')
            ->leftJoin('msprd as p', 'd.fprdcode', '=', 'p.fprdcode')
            ->selectRaw("m.fbranchcode::varchar AS fbranchcode, m.fsono::varchar AS fsono, m.fsodate, 
            m.fcustno::varchar AS fcustno, c.fcustomername AS fcustname, 
            COALESCE(m.fdiscpersen, 0) AS fdiscpersen, ROUND(m.fdiscount, 0) AS fdiscount, 
            ROUND(m.ftotalsalesnet, 0) AS famountgross, 
            ROUND(m.famountsonet, 0) AS famountsonet, m.fsalesman::varchar AS fsalesman, p.fprdname, 
            d.fsatuan, ROUND(m.famountpajak, 0) AS famountpajak, ROUND(m.famountso, 0) AS famountso, 
            d.fprdcode::varchar AS fprdcode, ROUND(d.fqty, 0) AS fqty, ROUND(d.fprice, 0) AS fprice, COALESCE(d.fdisc, '0') AS fdisc, 
            ROUND(d.fqtykecil, 0) * ROUND(d.fhpp, 0) AS famounthpp, 
            (ROUND(m.famountgross, 0) * ROUND(d.fqty, 0)) - (ROUND(d.fqtykecil, 0) * ROUND(d.fhpp, 0)) AS flabarugi, 
            CASE WHEN COALESCE(m.fincludeppn, '0') = '1' THEN (100 / (100 + COALESCE(NULLIF(m.fppnpersen, 0), 11))) * COALESCE(d.fpricenet, 0) ELSE COALESCE(d.fpricenet, 0) END AS fpricenet, 
            CASE WHEN COALESCE(m.fincludeppn, '0') = '1' THEN (100 / (100 + COALESCE(NULLIF(m.fppnpersen, 0), 11))) * COALESCE(m.famountgross, 0) * ROUND(d.fqty, 0) ELSE ROUND(d.fqty, 0) * COALESCE(m.famountgross, 0) END AS famountsales, 
            ROUND(d.famount, 0) AS famount, ROUND(d.fhpp, 0) AS fhpp, 
            m.fuserid::varchar AS fuserid, 'INV' AS fsource")
            ->where('m.ftrcode', 'INV');

        $this->applyInvoiceFilters($invoice, $request, 'm', 'd');

        if (! $request->boolean('include_retur')) {
            return $invoice->orderBy('m.fbranchcode')->orderBy('m.fsono')->orderBy('d.fprdcode')->get();
        }

        $invoiceHpp = DB::table('trandt')
            ->selectRaw('fsono, fprdcode, MAX(fhpp) AS fhpp')
            ->groupBy('fsono', 'fprdcode');

        $retur = DB::table('tranmt as m')
           ->leftJoin('trandt as d', 'm.fsono', '=', 'd.fsono')
            ->leftJoin('mscustomer as c', 'm.fcustno', '=', 'c.fcustomercode')
            ->leftJoin('msprd as p', 'd.fprdcode', '=', 'p.fprdcode')
            ->selectRaw("m.fbranchcode::varchar AS fbranchcode, m.fsono::varchar AS fsono, m.fsodate, 
            m.fcustno::varchar AS fcustno, c.fcustomername AS fcustname, 
            COALESCE(m.fdiscpersen, 0) AS fdiscpersen, ROUND(m.fdiscount, 0) AS fdiscount, 
            ROUND(m.ftotalsalesnet, 0) AS famountgross, 
            ROUND(m.famountsonet, 0) AS famountsonet, m.fsalesman::varchar AS fsalesman, p.fprdname, 
            d.fsatuan, ROUND(m.famountpajak, 0) AS famountpajak, ROUND(m.famountso, 0) AS famountso, 
            d.fprdcode::varchar AS fprdcode, ROUND(d.fqty, 0) AS fqty, ROUND(d.fprice, 0) AS fprice, COALESCE(d.fdisc, '0') AS fdisc, 
            ROUND(d.fqtykecil, 0) * ROUND(d.fhpp, 0) AS famounthpp, 
            (ROUND(m.famountgross, 0) * ROUND(d.fqty, 0)) - (ROUND(d.fqtykecil, 0) * ROUND(d.fhpp, 0)) AS flabarugi, 
            CASE WHEN COALESCE(m.fincludeppn, '0') = '1' THEN (100 / (100 + COALESCE(NULLIF(m.fppnpersen, 0), 11))) * COALESCE(d.fpricenet, 0) ELSE COALESCE(d.fpricenet, 0) END AS fpricenet, 
            CASE WHEN COALESCE(m.fincludeppn, '0') = '1' THEN (100 / (100 + COALESCE(NULLIF(m.fppnpersen, 0), 11))) * COALESCE(m.famountgross, 0) * ROUND(d.fqty, 0) ELSE ROUND(d.fqty, 0) * COALESCE(m.famountgross, 0) END AS famountsales, 
            ROUND(d.famount, 0) AS famount, ROUND(d.fhpp, 0) AS fhpp, 
            m.fuserid::varchar AS fuserid, 'REJ' AS fsource")
            ->where('m.ftrcode', 'REJ');

        $this->applyReturFilters($retur, $request, 'm', 'd');

        return DB::query()
            ->fromSub($invoice->unionAll($retur), 'x')
            ->orderByRaw("CASE WHEN x.fsource = 'REJ' THEN 1 ELSE 0 END")
            ->orderBy('x.fbranchcode')
            ->orderBy('x.fsono')
            ->orderBy('x.fprdcode')
            ->get();
    }

    private function applyInvoiceFilters($query, Request $request, string $headerAlias, string $detailAlias): void
    {
        $this->applyBranchVisibilityScope($query, "{$headerAlias}.fbranchcode");
        if ($request->filled('branch_codes')) {
            $query->whereIn("{$headerAlias}.fbranchcode", (array) $request->input('branch_codes'));
        }
        if ($request->filled('date_from')) {
            $query->where("{$headerAlias}.fsodate", '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where("{$headerAlias}.fsodate", '<=', $request->input('date_to') . ' 23:59:59');
        }
        $this->applyCommonFilters($query, $request, $headerAlias, $detailAlias, 'fcustno');
    }

    private function applyReturFilters($query, Request $request, string $headerAlias, string $detailAlias): void
    {
        $this->applyBranchVisibilityScope($query, "{$headerAlias}.fbranchcode");
        if ($request->filled('branch_codes')) {
            $query->whereIn("{$headerAlias}.fbranchcode", (array) $request->input('branch_codes'));
        }
        if ($request->filled('date_from')) {
            $query->where("{$headerAlias}.fsodate", '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where("{$headerAlias}.fsodate", '<=', $request->input('date_to') . ' 23:59:59');
        }
        $this->applyCommonFilters($query, $request, $headerAlias, $detailAlias, 'fsupplier');
    }

    private function applyCommonFilters($query, Request $request, string $headerAlias, string $detailAlias, string $customerColumn): void
    {
        if ($request->filled('salesman')) {
            $query->where("{$headerAlias}.fsalesman", $request->input('salesman'));
        }
        if ($request->filled('cust_from')) {
            $query->where("{$headerAlias}.{$customerColumn}", '>=', $request->input('cust_from'));
        }
        if ($request->filled('cust_to')) {
            $query->where("{$headerAlias}.{$customerColumn}", '<=', $request->input('cust_to'));
        }
        if ($request->filled('prd_from')) {
            $query->where("{$detailAlias}.fprdcode", '>=', $request->input('prd_from'));
        }
        if ($request->filled('prd_to')) {
            $query->where("{$detailAlias}.fprdcode", '<=', $request->input('prd_to'));
        }
    }

    public function exportExcel(Request $request)
    {
        $rows = $this->buildRows($request);
        $groupedData = $rows->groupBy('fsono');

        $filename = 'Listing_Penjualan_HPP_'.date('YmdHis').'.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');

        $writer = new Writer;
        $writer->openToFile($tempFile);

        $styleTitle = new Style(fontBold: true, fontSize: 14);
        $styleHeader = new Style(fontBold: true, backgroundColor: 'D3D3D3');
        $styleInvoice = new Style(fontBold: true, backgroundColor: 'E2E8F0');
        $styleReturInvoice = new Style(fontBold: true, fontColor: 'C00000', backgroundColor: 'E2E8F0');
        $styleDetail = new Style(fontBold: false);
        $styleGrandTotal = new Style(fontBold: true, backgroundColor: '333333', fontColor: 'FFFFFF');

        $makeRow = function (array $values, ?Style $style = null): Row {
            $cells = array_map(
                fn ($value) => $style ? Cell::fromValue($value, $style) : Cell::fromValue($value),
                $values
            );
            return new Row($cells);
        };

        // Header Informasi
        $writer->addRow($makeRow(['LISTING PENJUALAN DENGAN HPP'], $styleTitle));
        $writer->addRow($makeRow(['Tanggal:', date('d/m/Y').'  Jam: '.date('H:i')]));
        $writer->addRow($makeRow(['Periode:', $request->date_from.' s/d '.$request->date_to]));
        $writer->addRow($makeRow(['Operator:', auth('sysuser')->user()->fname ?? auth()->user()->fname ?? 'User']));
        $writer->addRow($makeRow([]));

        // Invoice Header Row
        $writer->addRow($makeRow([
            'Cab.', 'No. Faktur', 'Tanggal', 'Nama Customer', 'Salesman', 
            'Total Harga', '% Disc', 'Discount', 'Tot.Setelah Disc', 'PPN', 'Nilai Faktur'
        ], $styleHeader));

        // Invoice Detail Header Row (to demarcate columns)
        $writer->addRow($makeRow([
            '', 'Kode Barang', 'Nama Barang', 'Quantity', 'Satuan', 
            '@ Harga Net', '@ HPP', 'Tot.Harga Jual', 'Total HPP', 'Laba/Rugi'
        ], $styleHeader));

        $grandTotalSales = $groupedData->sum(fn($items) => abs((float) ($items->first()->famountso ?? 0)) * (($items->first()->fsource ?? '') === 'REJ' ? -1 : 1));
        $grandTotalDiscount = $groupedData->sum(fn($items) => abs((float) ($items->first()->fdiscount ?? 0)) * (($items->first()->fsource ?? '') === 'REJ' ? -1 : 1));
        $grandTotalHpp = $rows->sum(fn($row) => abs((float) ($row->famounthpp ?? 0)) * (($row->fsource ?? '') === 'REJ' ? -1 : 1));
        $grandTotalLaba = $rows->sum(fn($row) => abs((float) ($row->flabarugi ?? 0)) * (($row->fsource ?? '') === 'REJ' ? -1 : 1));

        foreach ($groupedData as $fsono => $items) {
            $h = $items->first();
            $sign = ($h->fsource ?? '') === 'REJ' ? -1 : 1;
            $invoiceStyle = ($h->fsource ?? '') === 'REJ' ? $styleReturInvoice : $styleInvoice;
            // Write Invoice Header
            $writer->addRow($makeRow([
                $h->fbranchcode,
                $h->fsono,
                $h->fsodate ? date('d/m/Y', strtotime($h->fsodate)) : '',
                $h->fcustname,
                $h->fsalesman,
                abs((float) $h->famountgross) * $sign,
                (float) $h->fdiscpersen,
                abs((float) $h->fdiscount) * $sign,
                abs((float) $h->famountsonet) * $sign,
                abs((float) $h->famountpajak) * $sign,
                abs((float) $h->famountso) * $sign
            ], $invoiceStyle));

            // Write Details
            foreach ($items as $row) {
                $writer->addRow($makeRow([
                    '',
                    $row->fprdcode,
                    $row->fprdname,
                    (float) $row->fqty,
                    $row->fsatuan,
                    (float) $row->fpricenet,
                    (float) $row->fhpp,
                    abs((float) $row->famountsales) * $sign,
                    abs((float) $row->famounthpp) * $sign,
                    abs((float) $row->flabarugi) * $sign
                ], $styleDetail));
            }
        }

        // Grand Total Row
        $writer->addRow($makeRow([]));
        $writer->addRow($makeRow([
            'GRAND TOTAL',
            '',
            '',
            '',
            '',
            '',
            '',
            $grandTotalDiscount,
            $grandTotalSales,
            $grandTotalHpp,
            $grandTotalLaba
        ], $styleGrandTotal));

        $writer->close();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
