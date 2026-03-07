<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ListingSOBelumController extends Controller
{
    public function index()
    {
        $customers = DB::table('mscustomer')->orderBy('fcustomercode')->get();
        $products = DB::table('msprd')->orderBy('fprdcode')->get();

        return view('listingso.index', compact('customers', 'products'));
    }

    public function print(Request $request)
    {
        $query = DB::table('public.trsomt as mt')
            ->leftJoin('public.mscustomer as cust', 'mt.fcustno', '=', 'cust.fcustomerid')
            ->leftJoin('public.mssalesman as sls', 'mt.fsalesman', '=', 'sls.fsalesmanid')
            ->select('mt.*', 'cust.fcustomercode', 'cust.fcustomername', 'sls.fsalesmanname');

        // Filter Tanggal
        if ($request->date_from) $query->where('mt.fsodate', '>=', $request->date_from);
        if ($request->date_to) $query->where('mt.fsodate', '<=', $request->date_to . ' 23:59:59');

        // Filter Customer Range
        if ($request->cust_from && $request->cust_to) {
            $query->whereBetween('cust.fcustomercode', [$request->cust_from, $request->cust_to]);
        }

        // Filter Produk Range
        if ($request->prd_from && $request->prd_to) {
            $query->whereExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                    ->from('public.trsodt as dt')
                    ->whereRaw('dt.fsono = mt.fsono')
                    ->whereBetween('dt.fitemno', [$request->prd_from, $request->prd_to]);
            });
        }

        // Logic Checkbox
        if (!$request->has('all_so') && $request->has('only_pending')) {
            $query->where('mt.fclose', '0');
        }

        $results = $query->orderBy('mt.fsodate', 'asc')->orderBy('mt.fsono', 'asc')->get();

        $totalFaktur = 0;
        foreach ($results as $row) {
            $row->details = DB::table('public.trsodt as dt')->where('dt.fsono', $row->fsono)->get();
            $totalFaktur += (float)$row->famountso;
        }

        $chunkedData = $results->chunk(5);

        return view('listingso.print', [
            'chunkedData' => $chunkedData,
            'totalPages' => $chunkedData->count(),
            'totalFaktur' => $totalFaktur,
            'user_session' => auth()->user()
        ]);
    }
}
