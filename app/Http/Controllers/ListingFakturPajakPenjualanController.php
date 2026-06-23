<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListingFakturPajakPenjualanController extends Controller
{
    public function index()
    {
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get(['fcabangkode', 'fcabangname']);
        $customers = DB::table('mscustomer')->orderBy('fcustomercode')->get(['fcustomercode', 'fcustomername']);

        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('listingfakturpajakpenjualan.index', compact('branches', 'customers', 'isAuthorized', 'userBranchCode'));
    }

    public function print(Request $request)
    {
        $results = $this->buildQuery($request)->get();

        return view('listingfakturpajakpenjualan.print', [
            'results' => $results,
            'request' => $request,
            'user_session' => auth()->user(),
        ]);
    }

    private function buildQuery(Request $request)
    {
        $query = DB::table('tranmt as m')
            ->join('mscustomer as c', 'm.fcustno', '=', 'c.fcustomercode')
            ->selectRaw("\n                m.fkodefp,\n                m.ftaxno,\n                m.fsono,\n                m.fsodate,\n                m.fnpwp,\n                m.fcustno,\n                c.fcustomername as fcustname,\n                m.ftotalsalesnet AS famountgross,\n                CASE\n                    WHEN m.fincludeppn = '1' THEN (100 / (100 + m.fppnpersen)) * m.fdiscount\n                    ELSE m.fdiscount\n                END AS fdiscount,\n                m.ftotalsalesnet - (\n                    CASE\n                        WHEN m.fincludeppn = '1' THEN (100 / (100 + m.fppnpersen)) * m.fdiscount\n                        ELSE m.fdiscount\n                    END\n                ) AS famountsonet,\n                m.famountpajak\n            ")
            ->where('m.famountpajak', '>', 0);

        $this->applyBranchVisibilityScope($query, 'm.fbranchcode');

        $branchCodes = array_values(array_filter((array) $request->input('branch_codes', [])));
        if ($branchCodes !== []) {
            $query->whereIn('m.fbranchcode', $branchCodes);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('m.fsodate', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('m.fsodate', '<=', $request->date_to);
        }

        if ($request->filled('customer')) {
            $query->where('m.fcustno', $request->customer);
        }

        return $query->orderBy('m.ftaxno');
    }
}
