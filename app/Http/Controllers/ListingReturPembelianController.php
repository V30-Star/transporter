<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListingReturPembelianController extends Controller
{
    public function index()
    {
        $products = DB::table('msprd')->orderBy('fprdcode')->get();
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get();

        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('listingreturpembelian.index', compact(
            'products', 'branches', 'isAuthorized', 'userBranchCode'
        ));
    }

    public function print(Request $request)
    {
        $results = $this->getRawData($request);

        return view('listingreturpembelian.print', [
            'results' => $results,
            'detailMode' => $request->boolean('detail', true),
            'rekapMode' => $request->boolean('rekap', false),
            'user_session' => auth()->user(),
        ]);
    }

    private function getRawData(Request $request)
    {
        $query = DB::table('trstockmt as m')
            ->join('trstockdt as d', 'm.fstockmtno', '=', 'd.fstockmtno')
            ->join('msprd as p', 'd.fprdcode', '=', 'p.fprdcode')
            ->leftJoin('mssupplier as s', 'm.fsupplier', '=', 's.fsuppliercode')
            ->leftJoin('mswh as wh', 'm.ffrom', '=', 'wh.fwhcode')
            ->where('m.fstockmtcode', 'REB')
            ->select(
                'm.fstockmtid',
                'm.fstockmtno',
                'm.fstockmtdate',
                'm.fbranchcode',
                'm.fsupplier',
                's.fsuppliername',
                'm.ffrom',
                'm.fket',
                'm.fusercreate',
                DB::raw('COALESCE(m.famount, 0) as header_amount'),
                DB::raw('COALESCE(m.famountpajak, 0) as header_ppn'),
                DB::raw('COALESCE(m.famountmt, 0) as header_total'),
                'wh.fwhname',
                'd.fprdcode',
                DB::raw('COALESCE(d.fqtykecil, 0) as fqtykecil'),
                DB::raw('COALESCE(d.fqty, 0) as fqty'),
                DB::raw('COALESCE(d.fprice, 0) as fprice'),
                DB::raw('COALESCE(d.ftotprice, 0) as famount'),
                'd.fsatuan',
                'p.fprdcode',
                'p.fprdname'
            );

        $this->applyBranchVisibilityScope($query, 'm.fbranchcode');

        $branches = $request->input('branch_codes', []);
        if (!empty($branches)) {
            $query->whereIn('m.fbranchcode', (array) $branches);
        }

        if ($request->date_from) {
            $query->where('m.fstockmtdate', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('m.fstockmtdate', '<=', $request->date_to);
        }

        $selectedProducts = $request->input('selected_products', '');
        if (!empty($selectedProducts)) {
            $productCodes = explode(',', $selectedProducts);
            $query->whereIn('p.fprdcode', $productCodes);
        }

        $query->orderBy('m.fstockmtno', 'asc');

        return $query->get();
    }
}
