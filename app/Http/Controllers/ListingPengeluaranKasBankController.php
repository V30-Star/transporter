<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListingPengeluaranKasBankController extends Controller
{
    public function index()
    {
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get();
        $accounts = DB::table('account')->orderBy('faccount')->get();

        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('listingpengeluarankasbank.index', compact(
            'branches', 'accounts', 'isAuthorized', 'userBranchCode'
        ));
    }

    public function print(Request $request)
    {
        $results = $this->getRawData($request);

        return view('listingpengeluarankasbank.print', [
            'results' => $results,
            'user_session' => auth('sysuser')->user() ?? auth()->user(),
        ]);
    }

    private function getRawData(Request $request)
    {
        $query = DB::table('trkasmt as m')
            ->join('trkasdt as d', 'm.fkasmtid', '=', 'd.fkasmtid')
            ->leftJoin('account as h', 'm.faccountno', '=', 'h.faccount')
            ->leftJoin('account as a', 'd.faccount', '=', 'a.faccount')
            ->where('m.ftrancode', 'BKK')
            ->select(
                'm.fkasmtid',
                'm.fkasmtno',
                'm.fkasmtdate',
                'm.fwhom',
                'h.faccname as accounth',
                'm.fket',
                DB::raw('COALESCE(m.famountpay, 0) as famountpay'),
                'm.fuserid',
                'a.faccname as accountd',
                'd.fnote',
                DB::raw('COALESCE(d.fkasdtvalue, 0) as fkasdtvalue'),
                'd.faccount',
                'm.faccountno',
                'm.ftrancode'
            );

        $this->applyBranchVisibilityScope($query, 'm.fbranchcode');

        $branches = $request->input('branch_codes', []);
        if (!empty($branches)) {
            $query->whereIn('m.fbranchcode', (array) $branches);
        }

        if ($request->date_from) {
            $query->where('m.fkasmtdate', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('m.fkasmtdate', '<=', $request->date_to);
        }
        if ($request->account_no) {
            $query->where('m.faccountno', $request->account_no);
        }
        if ($request->boolean('giro_mundur')) {
            $query->where('m.fgiromundur', '1');
        }

        return $query
            ->orderBy('m.fkasmtdate', 'asc')
            ->orderBy('m.fkasmtno', 'asc')
            ->orderBy('d.faccount', 'asc')
            ->get();
    }
}
