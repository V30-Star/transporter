<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportingCustomerController extends Controller
{
    public function index()
    {
        $customers = DB::table('mscustomer')->orderBy('fcustomercode', 'asc')->get();
        $salesmen = DB::table('mssalesman')->where('fnonactive', '0')->orderBy('fsalesmanname', 'asc')->get();

        return view('reportingcustomer.index', compact('customers', 'salesmen'));
    }

    public function print(Request $request)
    {
        $query = DB::table('mscustomer as c')
            ->leftJoin('mssalesman as s', 'c.fsalesman', '=', 's.fsalesmancode')
            ->select('c.*', 's.fsalesmanname');

        // Filter From - To
        if ($request->cust_from && $request->cust_to) {
            $query->whereBetween('c.fcustomercode', [$request->cust_from, $request->cust_to]);
        }

        // Filter Salesman
        if ($request->salesman) {
            $query->where('c.fsalesman', $request->salesman);
        }

        // Filter Limit (Jika diisi > 0)
        if ($request->limit > 0) {
            $query->where('c.flimit', '>=', $request->limit);
        }

        // Filter Blokir (Checkbox)
        if ($request->has('is_blocked')) {
            $query->where('c.fblokir', '1');
        }

        $data = $query->orderBy('c.fcustomercode', 'asc')->get();
        $printDate = Carbon::now()->format('d/m/Y H:i');

        return view('reportingcustomer.print', compact('data', 'printDate'));
    }
}