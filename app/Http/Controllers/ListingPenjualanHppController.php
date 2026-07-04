<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'user_session' => auth()->user(),
        ]);
    }

    private function buildRows(Request $request)
    {
        $invoice = DB::table('tranmt as m')
            ->leftJoin('trandt as d', 'm.fsono', '=', 'd.fsono')
            ->leftJoin('mscustomer as c', 'm.fcustno', '=', 'c.fcustomercode')
            ->leftJoin('msprd as p', 'd.fprdcode', '=', 'p.fprdcode')
            ->selectRaw("m.fbranchcode, m.fsono, m.fsodate, m.fcustno, c.fcustomername AS fcustname, COALESCE(m.fdiscpersen, 0) AS fdiscpersen, COALESCE(m.fdiscount, 0) AS fdiscount, COALESCE(m.ftotalsalesnet, 0) AS famountgross, COALESCE(m.famountsonet, 0) AS famountsonet, m.fsalesman, p.fprdname, d.fsatuan, COALESCE(m.famountpajak, 0) AS famountpajak, COALESCE(m.famountso, 0) AS famountso, d.fprdcode, COALESCE(d.fqty, 0) AS fqty, COALESCE(d.fprice, 0) AS fprice, COALESCE(d.fdisc, '0') AS fdisc, COALESCE(d.fqty, 0) * COALESCE(d.fhpp, 0) AS famounthpp, CASE WHEN COALESCE(m.fincludeppn, '0') = '1' THEN (100 / (100 + COALESCE(NULLIF(m.fppnpersen, 0), 11))) * COALESCE(d.fpricenet, 0) ELSE COALESCE(d.fpricenet, 0) END AS fpricenet, CASE WHEN COALESCE(m.fincludeppn, '0') = '1' THEN (100 / (100 + COALESCE(NULLIF(m.fppnpersen, 0), 11))) * COALESCE(d.fpricenet, 0) * COALESCE(d.fqty, 0) ELSE COALESCE(d.fqty, 0) * COALESCE(d.fpricenet, 0) END AS famountsales, COALESCE(d.famount, 0) AS famount, COALESCE(d.fhpp, 0) AS fhpp, ((CASE WHEN COALESCE(m.fincludeppn, '0') = '1' THEN (100 / (100 + COALESCE(NULLIF(m.fppnpersen, 0), 11))) * COALESCE(d.fpricenet, 0) ELSE COALESCE(d.fpricenet, 0) END * COALESCE(d.fqty, 0)) - (COALESCE(d.fqty, 0) * COALESCE(d.fhpp, 0))) AS flabarugi, m.fuserid, 'INV' AS fsource")
            ->where('m.ftrcode', 'INV');

        $this->applyInvoiceFilters($invoice, $request, 'm', 'd');

        if (! $request->boolean('include_retur')) {
            return $invoice->orderBy('m.fbranchcode')->orderBy('m.fsono')->orderBy('d.fprdcode')->get();
        }

        $invoiceHpp = DB::table('trandt')
            ->selectRaw('fsono, fprdcode, MAX(fhpp) AS fhpp')
            ->groupBy('fsono', 'fprdcode');

        $retur = DB::table('trstockmt as m')
            ->leftJoin('trstockdt as d', 'm.fstockmtno', '=', 'd.fstockmtno')
            ->leftJoin('mscustomer as c', 'm.fsupplier', '=', 'c.fcustomercode')
            ->leftJoin('msprd as p', 'd.fprdcode', '=', 'p.fprdcode')
            ->leftJoinSub($invoiceHpp, 'n', function ($join) {
                $join->on('m.frefno', '=', 'n.fsono')
                    ->on('d.fprdcode', '=', 'n.fprdcode');
            })
            ->selectRaw("m.fbranchcode, m.fstockmtno AS fsono, m.fstockmtdate AS fsodate, m.fsupplier AS fcustno, c.fcustomername AS fcustname, CAST(0 AS NUMERIC) AS fdiscpersen, CAST(0 AS NUMERIC) AS fdiscount, COALESCE(m.famount, 0) * -1 AS famountgross, COALESCE(m.famount, 0) * -1 AS famountsonet, m.fsalesman, p.fprdname, d.fsatuan, COALESCE(m.famountpajak, 0) * -1 AS famountpajak, COALESCE(m.famountmt, 0) * -1 AS famountso, d.fprdcode, COALESCE(d.fqty, 0) AS fqty, COALESCE(d.fprice, 0) AS fprice, CAST('0' AS VARCHAR) AS fdisc, COALESCE(d.fqty, 0) * CASE WHEN d.fprdcode = 'AWAL' THEN COALESCE(d.fprice, 0) * -1 ELSE COALESCE(n.fhpp, 0) * -1 END AS famounthpp, COALESCE(d.fprice, 0) AS fpricenet, COALESCE(d.fqty, 0) * COALESCE(d.fprice, 0) AS famountsales, COALESCE(d.ftotprice, 0) AS famount, CASE WHEN d.fprdcode = 'AWAL' THEN COALESCE(d.fprice, 0) * -1 ELSE COALESCE(n.fhpp, 0) END AS fhpp, ((COALESCE(d.fqty, 0) * CASE WHEN d.fprdcode = 'AWAL' THEN 0 ELSE COALESCE(n.fhpp, 0) END) - (COALESCE(d.fqty, 0) * COALESCE(d.fprice, 0))) AS flabarugi, m.fusercreate AS fuserid, 'REJ' AS fsource")
            ->where('m.fstockmtcode', 'REJ');

        $this->applyReturFilters($retur, $request, 'm', 'd');

        return DB::query()
            ->fromSub($invoice->unionAll($retur), 'x')
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
            $query->where("{$headerAlias}.fstockmtdate", '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where("{$headerAlias}.fstockmtdate", '<=', $request->input('date_to') . ' 23:59:59');
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
}
