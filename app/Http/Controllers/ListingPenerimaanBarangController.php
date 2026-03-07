<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ListingPenerimaanBarangController extends Controller
{
    public function index()
    {
        $suppliers = DB::table('mssupplier')->orderBy('fsuppliercode')->get();
        $warehouses = DB::table('mswh')->where('fnonactive', '0')->orderBy('fwhcode')->get();
        return view('listingpenerimaanbarang.index', compact('suppliers', 'warehouses'));
    }

    public function print(Request $request)
    {
        // 1. Subquery untuk mengecek Qty yang sudah ditagih (BUY)
        $subBuy = DB::table('trstockdt')
            ->select(
                DB::raw('frefdtno::text as frefdtno_text'),
                DB::raw('fprdcode::text as fprdcode_text'),
                DB::raw('fnouref::text as fnouref_text'),
                DB::raw('sum(fqtykecil) as fqtybuy')
            )
            ->where('fstockmtcode', 'BUY')
            ->groupBy('frefdtno', 'fprdcode', 'fnouref');

        // 2. Query Utama
        $query = DB::table('trstockmt as m')
            ->leftJoin('trstockdt as d', 'm.fstockmtid', '=', 'd.fstockmtid')
            ->leftJoin('msprd as p', 'd.fprdcode', '=', 'p.fprdid')
            ->leftJoin('mssupplier as s', 'm.fsupplier', '=', 's.fsupplierid')
            ->leftJoin('mswh as w', 'm.ffrom', '=', 'w.fwhid')
            ->leftJoinSub($subBuy, 'buy', function ($join) {
                $join->on(DB::raw('m.fstockmtno::text'), '=', DB::raw('buy.frefdtno_text'))
                    ->on(DB::raw('p.fprdcode::text'), '=', DB::raw('buy.fprdcode_text'))
                    ->on(DB::raw('d.frefdtno::text'), '=', DB::raw('buy.fnouref_text'));
            })
            ->select(
                'm.fstockmtno',
                'm.fstockmtdate',
                'm.fusercreate',
                'm.famountmt',
                's.fsuppliername',
                'w.fwhname',
                'p.fprdcode',
                'p.fprdname',
                'm.frefpo',
                'd.fqty',
                'd.fprice',
                'd.ftotprice',
                'd.fsatuan',
                DB::raw('COALESCE(buy.fqtybuy, 0) as fqtybuy')
            )
            ->where('m.fstockmtcode', 'TER');

        // Filter Tanggal
        if ($request->date_from) $query->where('m.fstockmtdate', '>=', $request->date_from);
        if ($request->date_to) $query->where('m.fstockmtdate', '<=', $request->date_to . ' 23:59:59');

        // Filter Gudang
        if ($request->warehouse) $query->where('m.ffrom', $request->warehouse);

        // Filter Supplier
        if ($request->sup_from && $request->sup_to) {
            $query->whereBetween('s.fsuppliercode', [$request->sup_from, $request->sup_to]);
        }

        // Filter Status Tagihan
        if ($request->status == '1') { // Sudah ditagih
            $query->whereRaw('d.fqtykecil - COALESCE(buy.fqtybuy, 0) = 0');
        } elseif ($request->status == '2') { // Belum ditagih
            $query->whereRaw('d.fqtykecil - COALESCE(buy.fqtybuy, 0) > 0');
        }

        // Urutan
        if ($request->sort_by == 'name') {
            $query->orderBy('m.fstockmtdate', 'asc')->orderBy('m.fstockmtno', 'asc');
        } else {
            $query->orderBy('m.fstockmtno', 'asc');
        }

        $results = $query->get();
        $totalLaporan = $results->unique('fstockmtno')->sum('famountmt');
        $chunkedData = $results->groupBy('fstockmtno')->chunk(5);

        return view('listingpenerimaanbarang.print', [
            'chunkedData' => $chunkedData,
            'totalPages' => $chunkedData->count(),
            'totalLaporan' => $totalLaporan,
            'user_session' => auth()->user()
        ]);
    }
}
