<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BukuBesarController extends Controller
{
    public function index()
    {
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get();
        $accounts = DB::table('account')->orderBy('faccount')->get(['faccount', 'faccname']);
        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('bukubesar.index', compact('branches', 'accounts', 'isAuthorized', 'userBranchCode'));
    }

    public function print(Request $request)
    {
        return view('bukubesar.print', [
            'rows' => $this->rows($request),
            'request' => $request,
            'title' => 'Buku Besar',
        ]);
    }

    private function rows(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $saldoKasDetail = DB::table('trkasmt as m')
            ->join('trkasdt as d', 'm.fkasmtid', '=', 'd.fkasmtid')
            ->join('account as a', 'a.faccount', '=', 'd.faccount')
            ->where('m.fkasmtdate', '<', $dateFrom)
            ->selectRaw("d.faccount, MIN(a.faccname) AS faccname, 'Saldo Awal' AS fjurnalno, CAST(? AS DATE) - 1 AS fjurnaltgl, 'Saldo Awal' AS fjurnalref, MIN(a.fnormal) AS fnormal, 'D' AS fdk, 0 AS famountdb, 0 AS famountcr, SUM(CASE WHEN a.fnormal = d.fdk THEN CASE WHEN a.fcurrency = 'IDR' THEN d.fjurnal_rp ELSE d.fjurnal END ELSE (CASE WHEN a.fcurrency = 'IDR' THEN d.fjurnal_rp ELSE d.fjurnal END) * -1 END) AS fsaldo_awal, '0' AS fpriority", [$dateFrom])
            ->groupBy('d.faccount', 'd.fdk', 'a.fnormal');

        $this->applyFilters($saldoKasDetail, $request, 'm', 'd.faccount');

        $saldoKasHeader = DB::table('trkasmt as m')
            ->join('account as a', 'a.faccount', '=', 'm.faccountheader')
            ->where('m.fkasmtdate', '<', $dateFrom)
            ->whereRaw('ABS(COALESCE(m.famountpay_rp, 0)) > 0')
            ->selectRaw("m.faccountheader AS faccount, MIN(a.faccname) AS faccname, 'Saldo Awal' AS fjurnalno, CAST(? AS DATE) - 1 AS fjurnaltgl, 'Saldo Awal' AS fjurnalref, MIN(a.fnormal) AS fnormal, 'D' AS fdk, 0 AS famountdb, 0 AS famountcr, SUM(CASE WHEN a.fnormal = m.fdkheader THEN CASE WHEN a.fcurrency = 'IDR' THEN ABS(m.famountpay_rp) ELSE ABS(m.famountpay) END ELSE (CASE WHEN a.fcurrency = 'IDR' THEN ABS(m.famountpay_rp) ELSE ABS(m.famountpay) END) * -1 END) AS fsaldo_awal, '0' AS fpriority", [$dateFrom])
            ->groupBy('m.faccountheader', 'm.fdkheader', 'a.fnormal');

        $this->applyFilters($saldoKasHeader, $request, 'm', 'm.faccountheader');

        $saldoJurnal = DB::table('jurnalmt as m')
            ->join('jurnaldt as d', 'm.fjurnalno', '=', 'd.fjurnalno')
            ->join('account as a', 'a.faccount', '=', 'd.faccount')
            ->where('m.fjurnaldate', '<', $dateFrom)
            ->selectRaw("d.faccount, MIN(a.faccname) AS faccname, 'Saldo Awal' AS fjurnalno, CAST(? AS DATE) - 1 AS fjurnaltgl, 'Saldo Awal' AS fjurnalref, MIN(a.fnormal) AS fnormal, 'D' AS fdk, 0 AS famountdb, 0 AS famountcr, SUM(CASE WHEN a.fnormal = d.fdk THEN d.famount ELSE d.famount * -1 END) AS fsaldo_awal, '0' AS fpriority", [$dateFrom])
            ->groupBy('d.faccount', 'd.fdk', 'a.fnormal');

        $this->applyFilters($saldoJurnal, $request, 'm', 'd.faccount');

        $mutasiKasDetail = DB::table('trkasmt as m')
            ->join('trkasdt as d', 'm.fkasmtid', '=', 'd.fkasmtid')
            ->join('account as a', 'a.faccount', '=', 'd.faccount')
            ->where('m.fkasmtdate', '>=', $dateFrom)
            ->where('m.fkasmtdate', '<=', $dateTo . ' 23:59:59')
            ->selectRaw("d.faccount, a.faccname, m.fkasmtno AS fjurnalno, m.fkasmtdate AS fjurnaltgl, TRIM(COALESCE(NULLIF(d.frefno, ''), NULLIF(d.fnote, ''), m.fket)) AS fjurnalref, a.fnormal, d.fdk, CASE WHEN d.fdk = 'D' THEN CASE WHEN a.fcurrency = 'IDR' THEN d.fjurnal_rp ELSE d.fjurnal END ELSE 0 END AS famountdb, CASE WHEN d.fdk = 'K' THEN CASE WHEN a.fcurrency = 'IDR' THEN d.fjurnal_rp ELSE d.fjurnal END ELSE 0 END AS famountcr, CASE WHEN a.fnormal = d.fdk THEN CASE WHEN a.fcurrency = 'IDR' THEN d.fjurnal_rp ELSE d.fjurnal END ELSE (CASE WHEN a.fcurrency = 'IDR' THEN d.fjurnal_rp ELSE d.fjurnal END) * -1 END AS fsaldo_awal, CASE WHEN a.fnormal = d.fdk THEN '0' ELSE '1' END AS fpriority");

        $this->applyFilters($mutasiKasDetail, $request, 'm', 'd.faccount');

        $mutasiKasHeader = DB::table('trkasmt as m')
            ->join('account as a', 'a.faccount', '=', 'm.faccountheader')
            ->where('m.fkasmtdate', '>=', $dateFrom)
            ->where('m.fkasmtdate', '<=', $dateTo . ' 23:59:59')
            ->whereRaw('ABS(COALESCE(m.famountpay_rp, 0)) > 0')
            ->selectRaw("m.faccountheader AS faccount, a.faccname, m.fkasmtno AS fjurnalno, m.fkasmtdate AS fjurnaltgl, TRIM(m.fket) AS fjurnalref, a.fnormal, m.fdkheader AS fdk, CASE WHEN m.fdkheader = 'D' THEN CASE WHEN a.fcurrency = 'IDR' THEN ABS(m.famountpay_rp) ELSE ABS(m.famountpay) END ELSE 0 END AS famountdb, CASE WHEN m.fdkheader = 'K' THEN CASE WHEN a.fcurrency = 'IDR' THEN ABS(m.famountpay_rp) ELSE ABS(m.famountpay) END ELSE 0 END AS famountcr, CASE WHEN a.fnormal = m.fdkheader THEN CASE WHEN a.fcurrency = 'IDR' THEN ABS(m.famountpay_rp) ELSE ABS(m.famountpay) END ELSE (CASE WHEN a.fcurrency = 'IDR' THEN ABS(m.famountpay_rp) ELSE ABS(m.famountpay) END) * -1 END AS fsaldo_awal, CASE WHEN a.fnormal = m.fdkheader THEN '0' ELSE '1' END AS fpriority");

        $this->applyFilters($mutasiKasHeader, $request, 'm', 'm.faccountheader');

        $mutasiJurnal = DB::table('jurnalmt as m')
            ->join('jurnaldt as d', 'm.fjurnalno', '=', 'd.fjurnalno')
            ->join('account as a', 'a.faccount', '=', 'd.faccount')
            ->where('m.fjurnaldate', '>=', $dateFrom)
            ->where('m.fjurnaldate', '<=', $dateTo . ' 23:59:59')
            ->selectRaw("d.faccount, a.faccname, m.fjurnalno AS fjurnalno, m.fjurnaldate AS fjurnaltgl, TRIM(COALESCE(NULLIF(d.frefno, ''), NULLIF(d.faccountnote, ''))) AS fjurnalref, a.fnormal, d.fdk, CASE WHEN d.fdk = 'D' THEN d.famount ELSE 0 END AS famountdb, CASE WHEN d.fdk = 'K' THEN d.famount ELSE 0 END AS famountcr, CASE WHEN a.fnormal = d.fdk THEN d.famount ELSE d.famount * -1 END AS fsaldo_awal, CASE WHEN a.fnormal = d.fdk THEN '0' ELSE '1' END AS fpriority");

        $this->applyFilters($mutasiJurnal, $request, 'm', 'd.faccount');

        $ledger = $saldoKasDetail
            ->unionAll($saldoKasHeader)
            ->unionAll($saldoJurnal)
            ->unionAll($mutasiKasDetail)
            ->unionAll($mutasiKasHeader)
            ->unionAll($mutasiJurnal);

        $rows = DB::query()
            ->fromSub($ledger, 'l')
            ->selectRaw("l.faccount, l.faccname, l.fjurnalno, l.fjurnaltgl, l.fjurnalref AS faccountno, l.fdk, l.famountdb, l.famountcr, l.fsaldo_awal, l.fpriority")
            ->orderBy('l.faccount')
            ->orderBy('l.fjurnalref')
            ->orderBy('l.fjurnaltgl')
            ->orderBy('l.fpriority')
            ->get();

        return $this->attachRunningBalance($rows);
    }

    private function attachRunningBalance($rows)
    {
        return $rows
            ->groupBy(fn($row) => trim((string) $row->faccount))
            ->flatMap(function ($group) {
                $openingRows = $group->filter(fn($row) => (string) $row->fjurnalno === 'Saldo Awal');
                $saldo = (float) $openingRows->sum('fsaldo_awal');
                $opening = $openingRows->first();
                $rows = collect();

                if ($opening && abs($saldo) > 0) {
                    $opening->famountdb = 0;
                    $opening->famountcr = 0;
                    $opening->ppvarsaldo = $saldo;
                    $opening->plasaldosubacc = $saldo;
                    $opening->pplasaldoakhir = $saldo;
                    $rows->push($opening);
                }

                return $rows->concat($group->reject(fn($row) => (string) $row->fjurnalno === 'Saldo Awal')
                    ->values()
                    ->map(function ($row) use (&$saldo) {
                        $saldo += (float) $row->fsaldo_awal;
                        $row->ppvarsaldo = $saldo;
                        $row->plasaldosubacc = $saldo;
                        $row->pplasaldoakhir = $saldo;
                        return $row;
                    }));
            })
            ->values();
    }

    private function applyFilters($query, Request $request, string $headerAlias, string $accountColumn): void
    {
        $this->applyBranchVisibilityScope($query, "{$headerAlias}.fbranchcode");

        if ($request->filled('branch_codes')) {
            $query->whereIn("{$headerAlias}.fbranchcode", (array) $request->input('branch_codes'));
        }
        if ($request->filled('account_from')) {
            $query->where($accountColumn, '>=', $request->input('account_from'));
        }
        if ($request->filled('account_to')) {
            $query->where($accountColumn, '<=', $request->input('account_to'));
        }
    }
}
