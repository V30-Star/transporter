<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ListingPRController extends Controller
{
    public function index()
    {
        $suppliers = DB::table('mssupplier')->orderBy('fsuppliercode')->get();
        return view('listingpr.index', compact('suppliers'));
    }

    public function print(Request $request)
    {
        // 1. Subquery untuk mendapatkan akumulasi Qty PO dari tabel tr_pod
        $subPO = DB::table('tr_pod')
            ->select('frefdtno', 'fprdcode', 'fnouref', DB::raw('sum(fqtykecil) as fqtypo'))
            ->groupBy('frefdtno', 'fprdcode', 'fnouref');

        // 2. Query Utama
        $query = DB::table('tr_prh as h')
            ->leftJoin('tr_prd as d', 'h.fprid', '=', 'd.fprnoid')
            ->leftJoin('mssupplier as s', 'h.fsupplier', '=', 's.fsupplierid')
            ->leftJoin('msprd as p', 'd.fprdcode', '=', 'p.fprdid')
            // Join dengan subquery PO
            ->leftJoinSub($subPO, 'o', function ($join) {
                $join->on('o.frefdtno', '=', 'h.fprno')
                    ->on('o.fprdcode', '=', 'p.fprdid');
            })
            ->select(
                'h.fprid',
                'h.fprno',
                'h.fprdate',
                'h.fneeddate',
                'h.fduedate',
                'h.fusercreate',
                's.fsuppliername',
                'd.fprdcode as prd_code_id',
                'p.fprdcode',
                'p.fprdname',
                'd.fqty',
                'd.fsatuan',
                DB::raw('COALESCE(o.fqtypo, 0) as fqtypo')
            );

        // Filter Tanggal
        if ($request->date_from) $query->where('h.fprdate', '>=', $request->date_from);
        if ($request->date_to) $query->where('h.fprdate', '<=', $request->date_to);

        // Filter Supplier
        if ($request->sup_from && $request->sup_to) {
            $query->whereBetween('s.fsuppliercode', [$request->sup_from, $request->sup_to]);
        }

        // Filter Hanya PR belum diorder semua (Logic sisa > 0)
        if (!$request->has('all_pr') && $request->has('only_pending')) {
            $query->whereRaw('(CASE WHEN d.fsatuan = p.fsatuanbesar THEN d.fqty * p.fqtykecil ELSE d.fqty END - COALESCE(o.fqtypo, 0)) > 0');
        }

        // Urutan
        if ($request->sort_by == 'name') {
            $query->orderBy('h.fprdate', 'asc')->orderBy('h.fprno', 'asc');
        } else {
            $query->orderBy('h.fprno', 'asc');
        }

        $results = $query->get();

        // Grouping data berdasarkan Header untuk tampilan Parent-Child
        $groupedData = $results->groupBy('fprno');
        $chunkedData = $groupedData->chunk(4); // 4 header per halaman

        return view('listingpr.print', [
            'chunkedData' => $chunkedData,
            'totalPages' => $chunkedData->count(),
            'user_session' => auth()->user(),
            'request' => $request
        ]);
    }
}
