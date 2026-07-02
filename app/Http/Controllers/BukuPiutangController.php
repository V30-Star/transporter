<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BukuPiutangController extends Controller
{
    private const PIUTANG_ACCOUNTS = ['11130.01', '21120.01'];

    public function index()
    {
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get();
        $customers = DB::table('mscustomer')->orderBy('fcustomercode')->get(['fcustomercode', 'fcustomername']);
        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('bukupiutang.index', compact('branches', 'customers', 'isAuthorized', 'userBranchCode'));
    }

    public function print(Request $request)
    {
        return view('bukupiutang.print', [
            'rows' => $this->rows($request),
            'request' => $request,
            'title' => 'Buku Piutang',
        ]);
    }

    private function rows(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $saldoKas = DB::table('trkasmt as m')
            ->join('trkasdt as d', 'm.fkasmtid', '=', 'd.fkasmtid')
            ->join('account as a', 'a.faccount', '=', 'd.faccount')
            ->where('m.fkasmtdate', '<', $dateFrom)
            ->whereIn('a.faccount', self::PIUTANG_ACCOUNTS)
            ->selectRaw("d.faccount, MIN(a.faccname) AS faccname, d.fsubaccount AS fsubaccoun, 'Saldo Awal' AS fjurnalno, CAST(? AS DATE) - 1 AS fjurnaltgl, 'Saldo Awal' AS fjurnalref, MIN(a.fnormal) AS fnormal, 'D' AS fdk, 0 AS famountdb, 0 AS famountcr, SUM(CASE WHEN a.fnormal = d.fdk THEN d.fjurnal ELSE d.fjurnal * -1 END) AS fsaldo_awal, '0' AS fpriority", [$dateFrom])
            ->groupBy('d.faccount', 'd.fsubaccount');

        $this->applyFilters($saldoKas, $request, 'm', 'd');

        $saldoJurnal = DB::table('jurnalmt as m')
            ->join('jurnaldt as d', 'm.fjurnalno', '=', 'd.fjurnalno')
            ->join('account as a', 'a.faccount', '=', 'd.faccount')
            ->where('m.fjurnaldate', '<', $dateFrom)
            ->where('d.fsubaccount', '<>', '')
            ->whereIn('a.faccount', self::PIUTANG_ACCOUNTS)
            ->selectRaw("d.faccount, MIN(a.faccname) AS faccname, d.fsubaccount AS fsubaccoun, 'Saldo Awal' AS fjurnalno, CAST(? AS DATE) - 1 AS fjurnaltgl, 'Saldo Awal' AS fjurnalref, MIN(a.fnormal) AS fnormal, 'D' AS fdk, 0 AS famountdb, 0 AS famountcr, SUM(CASE WHEN a.fnormal = d.fdk THEN d.famount ELSE d.famount * -1 END) AS fsaldo_awal, '0' AS fpriority", [$dateFrom])
            ->groupBy('d.faccount', 'd.fsubaccount');

        $this->applyFilters($saldoJurnal, $request, 'm', 'd');

        $mutasiKas = DB::table('trkasmt as m')
            ->join('trkasdt as d', 'm.fkasmtid', '=', 'd.fkasmtid')
            ->join('account as a', 'a.faccount', '=', 'd.faccount')
            ->where('m.fkasmtdate', '>=', $dateFrom)
            ->where('m.fkasmtdate', '<=', $dateTo . ' 23:59:59')
            ->whereIn('a.faccount', self::PIUTANG_ACCOUNTS)
            ->selectRaw("d.faccount, a.faccname, d.fsubaccount AS fsubaccoun, m.fkasmtno AS fjurnalno, m.fkasmtdate AS fjurnaltgl, TRIM(d.frefno) AS fjurnalref, a.fnormal, d.fdk, CASE WHEN d.fdk = 'D' THEN d.fjurnal ELSE 0 END AS famountdb, CASE WHEN d.fdk = 'K' THEN d.fjurnal ELSE 0 END AS famountcr, CASE WHEN a.fnormal = d.fdk THEN d.fjurnal ELSE d.fjurnal * -1 END AS fsaldo_awal, CASE WHEN a.fnormal = d.fdk THEN '0' ELSE '1' END AS fpriority");

        $this->applyFilters($mutasiKas, $request, 'm', 'd');

        $mutasiJurnal = DB::table('jurnalmt as m')
            ->join('jurnaldt as d', 'm.fjurnalno', '=', 'd.fjurnalno')
            ->join('account as a', 'a.faccount', '=', 'd.faccount')
            ->where('m.fjurnaldate', '>=', $dateFrom)
            ->where('m.fjurnaldate', '<=', $dateTo . ' 23:59:59')
            ->where('d.fsubaccount', '<>', '')
            ->whereIn('a.faccount', self::PIUTANG_ACCOUNTS)
            ->selectRaw("d.faccount, a.faccname, d.fsubaccount AS fsubaccoun, m.fjurnalno AS fjurnalno, m.fjurnaldate AS fjurnaltgl, TRIM(COALESCE(NULLIF(d.frefno, ''))) AS fjurnalref, a.fnormal, d.fdk, CASE WHEN d.fdk = 'D' THEN d.famount ELSE 0 END AS famountdb, CASE WHEN d.fdk = 'K' THEN d.famount ELSE 0 END AS famountcr, CASE WHEN a.fnormal = d.fdk THEN d.famount ELSE d.famount * -1 END AS fsaldo_awal, CASE WHEN a.fnormal = d.fdk THEN '0' ELSE '1' END AS fpriority");

        $this->applyFilters($mutasiJurnal, $request, 'm', 'd');

        $ledger = $saldoKas->unionAll($saldoJurnal)->unionAll($mutasiKas)->unionAll($mutasiJurnal);

        return DB::query()
            ->fromSub($ledger, 'l')
            ->leftJoin('mscustomer as c', 'l.fsubaccoun', '=', 'c.fcustomercode')
            ->selectRaw("l.faccount, l.faccname, l.fsubaccoun AS fcustno, c.fcustomername AS fcustname, CONCAT(COALESCE(c.fcustomername, l.fsubaccoun), ' (', TRIM(l.fsubaccoun), ')') AS fcust_profile, l.fjurnalno, l.fjurnaltgl, l.fjurnalref AS faccountno, l.fdk, l.famountdb, l.famountcr, SUM(l.fsaldo_awal) OVER(PARTITION BY l.faccount, l.fsubaccoun ORDER BY l.faccount, l.fsubaccoun, l.fjurnalref, l.fjurnaltgl, l.fpriority) AS ppvarsaldo")
            ->orderBy('l.faccount')
            ->orderBy('l.fsubaccoun')
            ->orderBy('l.fjurnalref')
            ->orderBy('l.fjurnaltgl')
            ->orderBy('l.fpriority')
            ->get();
    }

    private function applyFilters($query, Request $request, string $headerAlias, string $detailAlias): void
    {
        $this->applyBranchVisibilityScope($query, "{$headerAlias}.fbranchcode");

        if ($request->filled('branch_codes')) {
            $query->whereIn("{$headerAlias}.fbranchcode", (array) $request->input('branch_codes'));
        }
        if ($request->filled('cust_from')) {
            $query->where("{$detailAlias}.fsubaccount", '>=', $request->input('cust_from'));
        }
        if ($request->filled('cust_to')) {
            $query->where("{$detailAlias}.fsubaccount", '<=', $request->input('cust_to'));
        }
    }
}
