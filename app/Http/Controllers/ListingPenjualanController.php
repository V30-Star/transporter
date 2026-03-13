<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListingPenjualanController extends Controller
{
    public function index()
    {
        // Ambil data untuk dropdown filter
        $groups = DB::table('ms_groupprd')->get();
        $mereks = DB::table('msmerek')->get();
        $salesmans = DB::table('mssalesman')->get();

        return view('listingpenjualan.index', compact('groups', 'mereks', 'salesmans'));
    }

    public function print(Request $request)
    {
        $query = DB::table('tranmt as m')
            ->join('trandt as d', 'm.fsono', '=', 'd.fsono')
            ->join('mscustomer as c', 'm.fcustno', '=', 'c.fcustomerid')
            ->join('msprd as p', 'd.fprdcode', '=', 'p.fprdcode')
            ->select(
                'm.fsono', 'm.ftaxno', 'm.fsodate', 'm.fcustno', 'c.fcustname', 
                'm.fdiscpersen', 'm.fsalesman', 'm.famountpajak', 'm.famountso', 
                'm.fket', 'm.fuserid', 'm.fincludeppn', 'm.fppnpersen', 'm.fdiscount',
                'd.fprdcode', 'd.fqty', 'd.fsalesnet as fprice', 'd.fdisc', 'd.fsatuan', 
                'd.fdesc', 'd.fnou', 'd.fpricenet', 'd.frefsrj', 'p.fprdname',
                DB::raw('ROUND(m.ftotalsalesnet) as famountgross'),
                DB::raw('d.fsalesnet * d.fqty as famount')
            )
            ->whereRaw('coalesce(m.fdppersen, 0) = 0');

        // Filter Tanggal
        if ($request->from_date) $query->where('m.fsodate', '>=', $request->from_date);
        if ($request->to_date) $query->where('m.fsodate', '<=', $request->to_date);

        // Filter Produk
        if ($request->prd_from) $query->where('d.fprdcode', '>=', $request->prd_from);
        if ($request->prd_to) $query->where('d.fprdcode', '<=', $request->prd_to);

        // Filter Customer
        if ($request->cust_from) $query->where('m.fcustno', '>=', $request->cust_from);
        if ($request->cust_to) $query->where('m.fcustno', '<=', $request->cust_to);

        // Filter Master Lainnya
        if ($request->group_code) $query->where('p.fgroupcode', $request->group_code);
        if ($request->merek_code) $query->where('p.fmerek', $request->merek_code);
        if ($request->salesman) $query->where('m.fsalesman', $request->salesman);

        // Checkbox Belum Dikirim
        if ($request->has('belum_kirim')) {
            $query->where('d.fqtyremain', '>', 0);
        }

        $results = $query->orderBy('m.fsono')->orderBy('d.fnou')->get();
        $type = $request->display_type; // 'detail' atau 'rekap'

        return view('listingpenjualan.print', compact('results', 'type', 'request'));
    }
}