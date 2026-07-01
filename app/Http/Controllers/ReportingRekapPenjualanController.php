<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportingRekapPenjualanController extends Controller
{
    public function index()
    {
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get();
        $salesmans = DB::table('mssalesman')->orderBy('fsalesmancode')->get();
        $groups = DB::table('ms_groupprd')->orderBy('fgroupcode')->get();
        $mereks = DB::table('msmerek')->orderBy('fmerekcode')->get();
        $products = DB::table('msprd')->orderBy('fprdcode')->get(['fprdcode', 'fprdname']);
        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('reportingrekappenjualan.index', compact('branches', 'salesmans', 'groups', 'mereks', 'products', 'isAuthorized', 'userBranchCode'));
    }

    public function print(Request $request)
    {
        $groupBy = $request->input('group_by') === 'group' ? 'group' : 'merek';
        $rows = $this->buildRows($request, $groupBy);

        return view('reportingrekappenjualan.print', [
            'rows' => $rows,
            'request' => $request,
            'groupBy' => $groupBy,
            'title' => 'Laporan Rekap Penjualan',
        ]);
    }

    private function buildRows(Request $request, string $groupBy)
    {
        $groupCodeExpr = $groupBy === 'group' ? 'p.fgroupcode' : 'p.fmerek';
        $groupNameExpr = $groupBy === 'group' ? 'CAST(MIN(g.fgroupname) AS VARCHAR(50))' : 'CAST(MIN(merek.fmerekname) AS VARCHAR(50))';
        $qtyExpr = config('app.laporan_sales_satuan_besar', env('LaporanSalesSatuanBesar', '0')) === '1'
            ? 'SUM(CAST(d.fqtykecil AS NUMERIC) / NULLIF(CAST(p.fqtykecil AS NUMERIC), 0))'
            : 'SUM(d.fqtykecil)';
        $unitExpr = config('app.laporan_sales_satuan_besar', env('LaporanSalesSatuanBesar', '0')) === '1'
            ? 'CAST(MIN(p.fsatuanbesar) AS VARCHAR(10))'
            : 'CAST(MIN(p.fsatuankecil) AS VARCHAR(10))';

        $query = DB::table('tranmt as m')
            ->leftJoin('trandt as d', 'm.fsono', '=', 'd.fsono')
            ->leftJoin('msprd as p', 'd.fprdcode', '=', 'p.fprdcode')
            ->leftJoin('ms_groupprd as g', 'g.fgroupcode', '=', 'p.fgroupcode')
            ->leftJoin('msmerek as merek', 'p.fmerek', '=', 'merek.fmerekcode')
            ->selectRaw("{$groupCodeExpr} AS fmerek, {$groupNameExpr} AS fgroupname, {$qtyExpr} AS fqty, {$unitExpr} AS fsatuan, SUM(CASE WHEN m.ftrcode = 'INV' THEN (d.fsalesnet * d.fqty) - ((d.fsalesnet * d.fqty) * (COALESCE(CAST(NULLIF(d.fdisc, '') AS NUMERIC), 0) / 100)) WHEN m.ftrcode = 'REJ' THEN (d.fprice * d.fqty * -1) ELSE 0 END) AS famount, d.fprdcode, p.fprdname")
            ->whereIn('m.ftrcode', ['INV', 'REJ'])
            ->where('m.fsodate', '>=', $request->input('date_from', now()->startOfMonth()->toDateString()))
            ->where('m.fsodate', '<=', $request->input('date_to', now()->toDateString()) . ' 23:59:59');

        $this->applyCommonFilters($query, $request, 'm', 'd', 'p');

        return $query
            ->groupByRaw("{$groupCodeExpr}, d.fprdcode, p.fprdname")
            ->orderBy('fmerek')
            ->orderBy('d.fprdcode')
            ->get();
    }

    private function applyCommonFilters($query, Request $request, string $m, string $d, string $p, bool $withSalesman = true): void
    {
        $this->applyBranchVisibilityScope($query, "{$m}.fbranchcode");

        if ($request->filled('branch_codes')) {
            $query->whereIn("{$m}.fbranchcode", (array) $request->input('branch_codes'));
        }
        if ($withSalesman && $request->filled('salesman')) {
            $query->where("{$m}.fsalesman", $request->input('salesman'));
        }
        if ($request->filled('group_code')) {
            $query->whereRaw("TRIM({$p}.fgroupcode) = ?", [$request->input('group_code')]);
        }
        if ($request->filled('merek_code')) {
            $query->whereRaw("TRIM({$p}.fmerek) = ?", [$request->input('merek_code')]);
        }
        if ($request->filled('prd_from')) {
            $query->where("{$d}.fprdcode", '>=', $request->input('prd_from'));
        }
        if ($request->filled('prd_to')) {
            $query->where("{$d}.fprdcode", '<=', $request->input('prd_to'));
        }
    }
}
