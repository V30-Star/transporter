<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListingPiutangPenjualanController extends Controller
{
    public function index()
    {
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get();
        $salesmen = DB::table('mssalesman')->orderBy('fsalesmancode')->get();
        $wilayahs = DB::table('mswilayah')->orderBy('fwilayahcode')->get();
        $customers = DB::table('mscustomer')->orderBy('fcustomercode')->get();

        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('listingpiutangpenjualan.index', compact(
            'branches', 'salesmen', 'wilayahs', 'customers', 'isAuthorized', 'userBranchCode'
        ));
    }

    public function print(Request $request)
    {
        $rows = $this->getRawData($request);

        return view('listingpiutangpenjualan.print', [
            'rows' => $rows,
            'mode' => $request->input('report_mode', 'detail') === 'rekap' ? 'rekap' : 'detail',
            'user_session' => auth()->user(),
        ]);
    }

    private function getRawData(Request $request)
    {
        $perTanggal = $request->input('per_tanggal', now()->toDateString());
        $tglPembayaran = $request->input('tgl_pembayaran_date', $perTanggal);
        $tglJatuhTempo = $request->input('due_date', $perTanggal);

        $base = DB::table('tranmt as m')
            ->join('mscustomer as c', 'm.fcustno', '=', 'c.fcustomercode')
            ->selectRaw("m.fbranchcode, m.fsono, m.ftrcode AS fstockmtcode, m.fsodate, m.fjatuhtempo, m.frefno, m.fcustno AS fcustomer, c.fcustomername AS fcustname, m.famountso AS fnilainota, CASE WHEN m.ftrcode = 'REJ' THEN m.famountso * -1 ELSE m.famountso END AS famountso, m.fuserid, m.fsalesman, c.fwilayah")
            ->whereIn('m.ftrcode', ['INV', 'REJ'])
            ->where('m.fsodate', '<=', $perTanggal)
            ->where(function ($q) {
                $q->whereNull('m.ftunai')->orWhere('m.ftunai', '0')->orWhere('m.ftunai', '');
            });

        $this->applyBranchVisibilityScope($base, 'm.fbranchcode');

        $branches = $request->input('branch_codes', []);
        if (!empty($branches)) {
            $base->whereIn('m.fbranchcode', (array) $branches);
        }
        if ($request->filled('salesman')) {
            $base->where('m.fsalesman', $request->input('salesman'));
        }
        if ($request->filled('wilayah')) {
            $base->where('c.fwilayah', $request->input('wilayah'));
        }
        if ($request->filled('cust_from') && $request->filled('cust_to')) {
            $base->whereBetween('m.fcustno', [$request->input('cust_from'), $request->input('cust_to')]);
        }
        if ($request->input('due_filter') === 'due') {
            $base->where('m.fjatuhtempo', '<=', $tglJatuhTempo);
        }

        $payments = DB::table('trkasmt as m')
            ->join('trkasdt as d', 'm.fkasmtid', '=', 'd.fkasmtid')
            ->selectRaw('d.frefno, SUM(COALESCE(d.fkasdtvalue, 0) + COALESCE(d.fdiscount, 0)) AS ftotalbayar')
            ->where('m.ftrancode', 'RCP')
            ->groupBy('d.frefno');

        $journals = DB::table('jurnalmt as m')
            ->join('jurnaldt as d', 'm.fjurnalno', '=', 'd.fjurnalno')
            ->selectRaw('d.frefno, SUM(d.famount) AS ftotalsju')
            ->where('d.faccount', '11130.01')
            ->where('d.fdk', 'K')
            ->groupBy('d.frefno');

        if ($request->boolean('tgl_pembayaran')) {
            $payments->where('m.fkasmtdate', '<=', $tglPembayaran);
            $journals->where('m.fjurnaldate', '<=', $tglPembayaran);
        }

        return DB::query()
            ->fromSub($base, 'a')
            ->leftJoinSub($payments, 'b', 'a.fsono', '=', 'b.frefno')
            ->leftJoinSub($journals, 'c', 'a.fsono', '=', 'c.frefno')
            ->selectRaw("a.fbranchcode, a.fsono, a.fstockmtcode, a.fsodate, a.fjatuhtempo, a.frefno, a.fcustomer, a.fcustname, a.fnilainota, a.famountso, a.fuserid, a.fsalesman, COALESCE(b.ftotalbayar, 0) AS ftotalbayar, COALESCE(c.ftotalsju, 0) AS ftotalsju, CASE WHEN a.fstockmtcode = 'REJ' THEN (a.fnilainota - (COALESCE(b.ftotalbayar, 0) + COALESCE(c.ftotalsju, 0))) * -1 ELSE a.fnilainota - (COALESCE(b.ftotalbayar, 0) + COALESCE(c.ftotalsju, 0)) END AS fsisapiu")
            ->whereRaw("(a.fnilainota - (COALESCE(ABS(b.ftotalbayar), 0) + COALESCE(ABS(c.ftotalsju), 0))) > 0")
            ->orderBy('a.fcustomer')
            ->orderBy('a.fsono')
            ->get();
    }
}
