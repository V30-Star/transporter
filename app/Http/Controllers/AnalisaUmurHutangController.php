<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalisaUmurHutangController extends Controller
{
    public function index()
    {
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get();
        $suppliers = DB::table('mssupplier')
            ->orderBy('fsuppliercode')
            ->get(['fsuppliercode', 'fsuppliername']);
        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('analisaumurhutang.index', compact('branches', 'suppliers', 'isAuthorized', 'userBranchCode'));
    }

    public function print(Request $request)
    {
        $rows = $this->rows($request);

        return view('analisaumurhutang.print', [
            'rows' => $rows,
            'request' => $request,
            'title' => 'Analisa Umur Hutang',
        ]);
    }

    private function rows(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $dueDateTo = $request->input('due_date_to');
        $cutoffDate = $request->input('mode') === 'due' && $dueDateTo ? $dueDateTo : $dateTo;

        $base = DB::table('trstockmt as h')
            ->join('mssupplier as s', 'h.fsupplier', '=', 's.fsuppliercode')
            ->selectRaw("h.fbranchcode, h.fstockmtno AS fsono, h.fstockmtcode, h.fstockmtdate AS fsodate, h.fjatuhtempo, s.fsuppliername AS fcustname, s.fsuppliername AS fsuppliername, h.fsupplier AS fcustno, h.fsupplier AS fsuppliercode, h.fcurrency, h.frate, h.fusercreate AS fuserid, h.frefno, CASE WHEN h.fstockmtcode = 'BUY' THEN h.famountmt ELSE h.famountmt * -1 END AS famountso, CASE WHEN h.fstockmtcode = 'BUY' THEN h.famountmt_rp ELSE h.famountmt_rp * -1 END AS famountmt_rp, h.famountmt AS fnilainota, h.famountmt_rp AS fnilainota_rp")
            ->whereIn('h.fstockmtcode', ['BUY', 'REB'])
            ->where('h.fstockmtdate', '>=', $dateFrom)
            ->where('h.fstockmtdate', '<=', $dateTo . ' 23:59:59');

        $this->applyBaseFilters($base, $request);

        if ($request->input('mode') === 'due' && $dueDateTo) {
            $base->where('h.fjatuhtempo', '<=', $dueDateTo);
        }

        $paidKas = DB::table('trkasmt as m')
            ->join('trkasdt as d', 'm.fkasmtid', '=', 'd.fkasmtid')
            ->where('m.ftrancode', 'PAY')
            ->where('m.fkasmtdate', '<=', $cutoffDate)
            ->selectRaw('d.frefno, SUM(d.fkasdtvalue + COALESCE(d.fdiscount, 0)) AS ftotalbayar, SUM(d.fvalue_rp + COALESCE(d.fdiscountrp, 0)) AS ftotalbayar_rp')
            ->groupBy('d.frefno');

        $paidJurnal = DB::table('jurnalmt as m')
            ->join('jurnaldt as d', 'm.fjurnalno', '=', 'd.fjurnalno')
            ->join('account as a', 'a.faccount', '=', 'd.faccount')
            ->where('m.fjurnaldate', '<=', $cutoffDate)
            ->where(function ($q) {
                $q->where(function ($qq) {
                    $qq->whereIn('a.faccupline', function ($sub) {
                        $sub->select('faccount')
                            ->from('set_account')
                            ->where('faccount_name', 'HUTANGDAGANG');
                    })->where('d.fdk', 'D');
                })->orWhere(function ($qq) {
                    $qq->whereIn('a.faccount', function ($sub) {
                        $sub->select('faccount')
                            ->from('set_account')
                            ->where('faccount_name', 'RETURPEMBELIAN');
                    })->where('d.fdk', 'K');
                });
            })
            ->selectRaw('d.frefno, SUM(d.famount) AS ftotalsju, SUM(d.famount_rp) AS ftotalsju_rp')
            ->groupBy('d.frefno');

        return DB::query()
            ->fromSub($base, 'a')
            ->leftJoinSub($paidKas, 'b', 'a.fsono', '=', 'b.frefno')
            ->leftJoinSub($paidJurnal, 'c', 'a.fsono', '=', 'c.frefno')
            ->selectRaw("a.*, CASE WHEN a.fstockmtcode = 'REB' THEN (ABS(a.fnilainota) - (COALESCE(ABS(b.ftotalbayar), 0) + COALESCE(ABS(c.ftotalsju), 0))) * -1 ELSE a.fnilainota - (COALESCE(ABS(b.ftotalbayar), 0) + COALESCE(ABS(c.ftotalsju), 0)) END AS famountremain, CASE WHEN a.fstockmtcode = 'REB' THEN (a.fnilainota_rp - (COALESCE(ABS(b.ftotalbayar_rp), 0) + COALESCE(ABS(c.ftotalsju_rp), 0))) * -1 ELSE a.fnilainota_rp - (COALESCE(b.ftotalbayar_rp, 0) + COALESCE(c.ftotalsju_rp, 0)) END AS famountremain_rp, (?::date - a.fjatuhtempo::date) AS umur", [$cutoffDate])
            ->whereRaw("a.fnilainota - (COALESCE(ABS(b.ftotalbayar), 0) + COALESCE(ABS(c.ftotalsju), 0)) > 0")
            ->orderBy('a.fcurrency')
            ->orderBy('a.fcustno')
            ->orderBy('a.fsodate')
            ->orderBy('a.fsono')
            ->get()
            ->map(function ($row) {
                $umur = (int) ($row->umur ?? 0);
                $amount = (float) ($row->famountremain ?? 0);
                $row->mu = $umur;
                $row->fsisapiu = $amount;
                $row->varundue = $umur < 0 ? $amount : 0;
                $row->var30hari = $umur >= 0 && $umur <= 30 ? $amount : 0;
                $row->var60hari = $umur >= 31 && $umur <= 60 ? $amount : 0;
                $row->ppvar90hari = $umur >= 61 && $umur <= 90 ? $amount : 0;
                $row->ppvar91hari = $umur >= 91 && $umur <= 365 ? $amount : 0;
                $row->ppvar1tahun = $umur > 365 ? $amount : 0;
                return $row;
            });
    }

    private function applyBaseFilters($query, Request $request): void
    {
        $this->applyBranchVisibilityScope($query, 'h.fbranchcode');
        if ($request->filled('branch_codes')) {
            $query->whereIn('h.fbranchcode', (array) $request->input('branch_codes'));
        }
        if ($request->filled('supplier')) {
            $query->where('h.fsupplier', $request->input('supplier'));
        }
    }
}
