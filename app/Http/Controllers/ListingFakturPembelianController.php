<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ListingFakturPembelianController extends Controller
{
    public function index()
    {
        $suppliers = DB::table('mssupplier')->orderBy('fsuppliercode')->get();
        return view('listingfakturpembelian.index', compact('suppliers'));
    }

    public function print(Request $request)
    {
        $query = DB::table('trstockmt as m')
            ->leftJoin('trstockdt as d', 'm.fstockmtno', '=', 'd.fstockmtno')
            ->leftJoin('msprd as p', 'd.fprdcode', '=', 'p.fprdid')
            ->leftJoin('mssupplier as s', 'm.fsupplier', '=', 's.fsupplierid')
            ->select(
                'm.fstockmtno',
                'm.fstockmtdate',
                'm.fsupplier',
                's.fsuppliername',
                'm.fusercreate',
                'm.famount',
                'm.famountpajak',
                'm.famountmt',
                'd.fprdcode',
                'p.fprdname',
                'd.frefdtno',
                'd.fqty',
                'd.fqtyremain',
                'd.fprice',
                'd.fbiaya',
                'd.ftotprice',
                'd.fsatuan',
                DB::raw("case when m.ftrancode='0' then 'Trade' else 'Non Trade' end as ftype")
            )
            ->where('m.fstockmtcode', 'BUY');

        // Filter Tanggal
        if ($request->date_from) $query->where('m.fstockmtdate', '>=', $request->date_from);
        if ($request->date_to) $query->where('m.fstockmtdate', '<=', $request->date_to . ' 23:59:59');

        // Filter Type Transaksi
        if ($request->type_transaksi == '1') {
            $query->where('m.ftrancode', '0');
        } elseif ($request->type_transaksi == '2') {
            $query->where('m.ftrancode', '1');
        }

        // Filter Supplier Range
        if ($request->sup_from && $request->sup_to) {
            $query->whereBetween('s.fsuppliercode', [$request->sup_from, $request->sup_to]);
        }

        // Urutan
        if ($request->sort_by == '1') {
            $query->orderBy('m.fstockmtdate', 'asc')->orderBy('m.fstockmtno', 'asc')->orderBy('d.fstockdtid', 'asc');
        } elseif ($request->sort_by == '2') {
            $query->orderBy('s.fsuppliername', 'asc')->orderBy('m.fstockmtdate', 'asc');
        } else {
            $query->orderBy('m.fstockmtno', 'asc')->orderBy('d.fstockdtid', 'asc');
        }

        $results = $query->get();

        // Grouping data untuk tampilan Parent-Child
        $groupedData = $results->groupBy('fstockmtno');
        $chunkedData = $groupedData->chunk(4); // 4 Faktur per halaman

        $totalLaporan = $results->unique('fstockmtno')->sum('famountmt');

        return view('listingfakturpembelian.print', [
            'chunkedData' => $chunkedData,
            'totalPages' => $chunkedData->count(),
            'totalLaporan' => $totalLaporan,
            'user_session' => auth()->user()
        ]);
    }
}
