<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListingReturPenjualanController extends Controller
{
    public function index()
    {
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get();
        $customers = DB::table('mscustomer')->orderBy('fcustomercode')->get();
        $salesmen = DB::table('mssalesman')->orderBy('fsalesmancode')->get();
        $products = DB::table('msprd')->orderBy('fprdcode')->get();

        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('listingreturpenjualan.index', compact(
            'branches', 'customers', 'salesmen', 'products', 'isAuthorized', 'userBranchCode'
        ));
    }

    public function print(Request $request)
    {
        $results = $this->getRawData($request);

        return view('listingreturpenjualan.print', [
            'results' => $results,
            'detailMode' => $request->boolean('detail', true),
            'rekapMode' => $request->boolean('rekap', false),
            'user_session' => auth('sysuser')->user() ?? auth()->user(),
        ]);
    }

    private function getRawData(Request $request)
    {
        $query = DB::table('tranmt as m')
            ->join('trandt as d', 'm.fsono', '=', 'd.fsono')
            ->join('msprd as p', 'd.fprdcode', '=', 'p.fprdcode')
            ->join('mscustomer as c', 'm.fcustno', '=', 'c.fcustomercode')
            ->leftJoin('mswh as w', 'm.ffrom', '=', 'w.fwhcode')
            ->where('m.ftrcode', 'REJ')
            ->select(
                'm.ftranmtid as fstockmtid',
                'm.fsono as fstockmtno',
                'm.fsodate as fstockmtdate',
                'm.fcustno as fsupplier',
                'c.fcustomername as fcustname',
                DB::raw("'' as fcity"),
                'm.fket',
                'm.frefno',
                DB::raw("CAST(CONCAT(TRIM(m.ffrom), ' - ', TRIM(w.fwhname)) AS VARCHAR(50)) as gudang"),
                'm.fuserid',
                'd.fprdcode',
                'p.fprdname',
                'd.frefcode as frefdtno',
                DB::raw('COALESCE(d.fqty, 0) as fqty'),
                'd.fsatuan',
                DB::raw('COALESCE(d.fprice, 0) as fprice'),
                DB::raw('COALESCE(d.famount, 0) as ftotprice'),
                DB::raw('COALESCE(m.famountso, 0) as famountmt'),
                DB::raw('COALESCE(m.famountgross, 0) as famount'),
                DB::raw('COALESCE(m.famountpajak, 0) as famountpajak')
            );

        $this->applyBranchVisibilityScope($query, 'm.fbranchcode');

        $branches = $request->input('branch_codes', []);
        if (!empty($branches)) {
            $query->whereIn('m.fbranchcode', (array) $branches);
        }

        if ($request->date_from) {
            $query->where('m.fsodate', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('m.fsodate', '<=', $request->date_to);
        }

        if ($request->cust_from && $request->cust_to) {
            $query->whereBetween(DB::raw('TRIM(m.fcustno)'), [$request->cust_from, $request->cust_to]);
        }

        if ($request->salesman_code) {
            $query->where('m.fsalesman', $request->salesman_code);
        }

        $selectedProducts = $request->input('selected_products', '');
        if (!empty($selectedProducts)) {
            $query->whereIn('d.fprdcode', explode(',', $selectedProducts));
        }

        return $query
            ->orderBy('m.fsodate', 'asc')
            ->orderBy('m.fsono', 'asc')
            ->orderBy('d.fprdcode', 'asc')
            ->get();
    }
}
