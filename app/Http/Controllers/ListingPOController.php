<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ListingPOController extends Controller
{
    public function index()
    {
        $suppliers = DB::table('mssupplier')->orderBy('fsuppliercode')->get();
        return view('listingpo.index', compact('suppliers'));
    }

    public function print(Request $request)
    {
        // 1. Subquery untuk mendapatkan akumulasi Qty yang sudah diterima (TER)
        $subTerima = DB::table('trstockdt')
            ->select('frefdtno', 'fprdcode', 'fnouref', DB::raw('sum(fqtykecil) as fqtyterima'))
            ->where(function ($q) {
                $q->where('fstockmtcode', 'TER')
                    ->orWhere(function ($sq) {
                        $sq->where('fcode', 'P')->where('fstockmtcode', 'BUY');
                    });
            })
            ->groupBy('frefdtno', 'fprdcode', 'fnouref');

        // 2. Query Utama
        $query = DB::table('tr_poh as h')
            ->leftJoin('tr_pod as d', 'h.fpohdid', '=', 'd.fpono')
            ->leftJoin('mssupplier as s', 'h.fsupplier', '=', 's.fsupplierid')
            ->leftJoin('msprd as p', 'd.fprdcode', '=', 'p.fprdid')
            ->leftJoinSub($subTerima, 'ter', function ($join) {
                $join->on('h.fpohdid', '=', 'ter.frefdtno')
                    ->on(DB::raw('ter.fprdcode'), '=', DB::raw('p.fprdid'))
                    ->on(DB::raw('ter.fnouref'), '=', DB::raw('d.fnou'));
            })
            ->select(
                'h.fpohdid',
                'h.fpono',
                'h.fpodate',
                'h.fusercreate',
                'h.fclose',
                's.fsuppliername',
                'p.fprdcode',
                'p.fprdname',
                'p.fqtykecil as p_qtykecil',
                'd.fqty',
                'd.fsatuan',
                'd.fprice',
                'd.famount',
                'd.fnou',
                DB::raw('COALESCE(ter.fqtyterima, 0) as fqtyterima')
            );

        // Filter Tanggal
        if ($request->date_from) $query->where('h.fpodate', '>=', $request->date_from);
        if ($request->date_to) $query->where('h.fpodate', '<=', $request->date_to);

        // Filter Supplier
        if ($request->sup_from && $request->sup_to) {
            $query->whereBetween('s.fsuppliercode', [$request->sup_from, $request->sup_to]);
        }

        // Filter Belum Diterima Semua
        if (!$request->has('all_po') && $request->has('only_pending')) {
            $query->whereRaw('d.fqty > COALESCE(ter.fqtyterima, 0)')->where('h.fclose', '0');
        }

        // Urutan
        if ($request->sort_by == 'name') {
            $query->orderBy('h.fpodate', 'asc')->orderBy('h.fpono', 'asc');
        } else {
            $query->orderBy('h.fpono', 'asc');
        }

        $results = $query->get();

        // Grouping Data
        $groupedData = $results->groupBy('fpono');
        $chunkedData = $groupedData->chunk(4);

        return view('listingpo.print', [
            'chunkedData' => $chunkedData,
            'totalPages' => $chunkedData->count(),
            'user_session' => auth()->user()
        ]);
    }
}
