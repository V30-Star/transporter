<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListingHutangDagangController extends Controller
{
    public function index()
    {
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get();
        $suppliers = DB::table('mssupplier')->orderBy('fsuppliercode')->get(['fsuppliercode', 'fsuppliername']);
        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('listinghutangdagang.index', compact('branches', 'suppliers', 'isAuthorized', 'userBranchCode'));
    }

    public function print(Request $request)
    {
        $rows = $this->getRawData($request);

        return view('listinghutangdagang.print', [
            'rows' => $rows,
            'mode' => $request->input('report_mode', 'detail') === 'rekap' ? 'rekap' : 'detail',
            'user_session' => auth()->user(),
        ]);
    }

    private function getRawData(Request $request)
    {
        $perTanggal = $request->input('per_tanggal', now()->toDateString());
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $tglPembayaran = $request->input('payment_date', $perTanggal);
        $tglJatuhTempo = $request->input('due_date', $perTanggal);

        $base = DB::table('trstockmt as h')
            ->join('mssupplier as s', 'h.fsupplier', '=', 's.fsuppliercode')
            ->selectRaw("h.fbranchcode, h.fstockmtno, h.fstockmtdate, h.fjatuhtempo, h.fstockmtcode, s.fsuppliername, h.fsupplier, h.fcurrency, h.frate, h.famountmt AS fnilainota, CASE WHEN h.fstockmtcode = 'BUY' THEN h.famountmt ELSE h.famountmt * -1 END AS famountmt, h.fusercreate AS fuserid, h.frefno AS fnofaktur")
            ->whereIn('h.fstockmtcode', ['BUY', 'REB']);

        if ($request->input('date_mode', 'per_tanggal') === 'periode') {
            $base->where('h.fstockmtdate', '>=', $dateFrom)
                ->where('h.fstockmtdate', '<=', $dateTo . ' 23:59:59');
        } else {
            $base->where('h.fstockmtdate', '<=', $perTanggal . ' 23:59:59');
        }

        $this->applyBranchVisibilityScope($base, 'h.fbranchcode');

        $branches = $request->input('branch_codes', []);
        if (!empty($branches)) {
            $base->whereIn('h.fbranchcode', (array) $branches);
        }
        if ($request->input('due_filter') === 'due') {
            $base->where('h.fjatuhtempo', '<=', $tglJatuhTempo);
        }
        if ($request->filled('supplier_from')) {
            $base->where('h.fsupplier', '>=', $request->input('supplier_from'));
        }
        if ($request->filled('supplier_to')) {
            $base->where('h.fsupplier', '<=', $request->input('supplier_to'));
        }

        $payments = DB::table('trkasmt as m')
            ->join('trkasdt as d', 'm.fkasmtid', '=', 'd.fkasmtid')
            ->selectRaw('d.frefno, SUM(COALESCE(d.fkasdtvalue, 0) + COALESCE(d.fdiscount, 0)) AS ftotalbayar')
            ->where('m.ftrancode', 'PAY')
            ->groupBy('d.frefno');

        $journals = DB::table('jurnalmt as m')
            ->join('jurnaldt as d', 'm.fjurnalno', '=', 'd.fjurnalno')
            ->join('account as a', 'a.faccount', '=', 'd.faccount')
            ->selectRaw('d.frefno, SUM(d.famount) AS ftotalsju')
            ->whereIn('a.faccupline', function ($sub) {
                $sub->select('faccount')
                    ->from('set_account')
                    ->where('faccount_name', 'HUTANGDAGANG');
            })
            ->where('d.fdk', 'D')
            ->groupBy('d.frefno');

        if ($request->input('payment_filter') === 'payment') {
            $payments->where('m.fkasmtdate', '<=', $tglPembayaran);
            $journals->where('m.fjurnaldate', '<=', $tglPembayaran);
        }

        return DB::query()
            ->fromSub($base, 'a')
            ->leftJoinSub($payments, 'b', 'a.fstockmtno', '=', 'b.frefno')
            ->leftJoinSub($journals, 'c', 'a.fstockmtno', '=', 'c.frefno')
            ->selectRaw("a.*, COALESCE(b.ftotalbayar, 0) AS ftotalbayar, COALESCE(c.ftotalsju, 0) AS ftotalsju, CASE WHEN a.fstockmtcode = 'REB' THEN (a.fnilainota - (COALESCE(b.ftotalbayar, 0) + COALESCE(c.ftotalsju, 0))) * -1 ELSE a.fnilainota - (COALESCE(b.ftotalbayar, 0) + COALESCE(c.ftotalsju, 0)) END AS famountremain")
            ->whereRaw("(a.fnilainota - (COALESCE(ABS(b.ftotalbayar), 0) + COALESCE(ABS(c.ftotalsju), 0))) > 0")
            ->orderBy('a.fsupplier')
            ->orderBy('a.fstockmtdate')
            ->orderBy('a.fstockmtno')
            ->get();
    }
}
