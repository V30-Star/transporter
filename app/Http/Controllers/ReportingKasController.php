<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportingKasController extends Controller
{
    public function pengeluaranKasIndex(Request $request)
    {
        return $this->renderIndex($request, 'BKK');
    }

    public function penerimaanKasIndex(Request $request)
    {
        return $this->renderIndex($request, 'BKM');
    }

    public function printPengeluaranKas(Request $request)
    {
        return $this->renderPrint($request, 'BKK');
    }

    public function printPenerimaanKas(Request $request)
    {
        return $this->renderPrint($request, 'BKM');
    }

    private function renderIndex(Request $request, string $tranCode)
    {
        $filterDateFrom = $request->query('filter_date_from') ?: Carbon::now('Asia/Bangkok')->startOfMonth()->format('Y-m-d');
        $filterDateTo = $request->query('filter_date_to') ?: Carbon::now('Asia/Bangkok')->format('Y-m-d');
        $filterAccount = (string) $request->query('filter_account', '');
        $onlyGiroMundur = $request->boolean('only_giro_mundur');

        $accounts = Account::query()
            ->where('fnonactive', '0')
            ->orderBy('faccount')
            ->get(['faccount', 'faccname']);

        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get();
        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('reportingkas.index', [
            'pageTitle' => $tranCode === 'BKK' ? 'Laporan Pengeluaran Kas/Bank' : 'Laporan Penerimaan Kas/Bank',
            'printRoute' => $tranCode === 'BKK'
                ? route('reportingkas.pengeluaran.print')
                : route('reportingkas.penerimaan.print'),
            'resetRoute' => $tranCode === 'BKK'
                ? route('reportingkas.pengeluaran.index')
                : route('reportingkas.penerimaan.index'),
            'filterDateFrom' => $filterDateFrom,
            'filterDateTo' => $filterDateTo,
            'filterAccount' => $filterAccount,
            'onlyGiroMundur' => $onlyGiroMundur,
            'accounts' => $accounts,
            'branches' => $branches,
            'isAuthorized' => $isAuthorized,
            'userBranchCode' => $userBranchCode,
        ]);
    }

    private function renderPrint(Request $request, string $tranCode)
    {
        $filterDateFrom = $request->query('filter_date_from');
        $filterDateTo = $request->query('filter_date_to');
        $filterAccount = trim((string) $request->query('filter_account', ''));
        $onlyGiroMundur = $request->boolean('only_giro_mundur');

        $query = DB::table('trkasmt')
            ->leftJoin('account as acc', 'acc.faccount', '=', 'trkasmt.faccountheader')
            ->where('trkasmt.ftrancode', $tranCode)
            ->select([
                'trkasmt.*',
                'acc.faccname as header_account_name',
            ]);
        $this->applyBranchVisibilityScope($query, 'trkasmt.fbranchcode');

        $selectedBranches = $request->input('branch_codes', []);
        if (! empty($selectedBranches)) {
            $query->whereIn('trkasmt.fbranchcode', (array) $selectedBranches);
        }

        if (! empty($filterDateFrom)) {
            $query->whereDate('trkasmt.fkasmtdate', '>=', $filterDateFrom);
        }

        if (! empty($filterDateTo)) {
            $query->whereDate('trkasmt.fkasmtdate', '<=', $filterDateTo);
        }

        if ($filterAccount !== '') {
            $query->where('trkasmt.faccountheader', $filterAccount);
        }

        if ($onlyGiroMundur) {
            $query->where('trkasmt.fgiromundur', '1');
        }

        $records = $query
            ->orderBy('trkasmt.fkasmtdate')
            ->orderBy('trkasmt.fkasmtno')
            ->get();

        $headerIds = $records->pluck('fkasmtid')->filter()->all();

        $detailsByHeader = DB::table('trkasdt as dt')
            ->leftJoin('account as acc', 'acc.faccount', '=', 'dt.faccount')
            ->leftJoin('mssubaccount as sub', 'sub.fsubaccountcode', '=', 'dt.fsubaccount')
            ->whereIn('dt.fkasmtid', $headerIds)
            ->orderBy('dt.fkasmtid')
            ->orderBy('dt.fnou')
            ->get([
                'dt.*',
                'acc.faccname as account_name',
                'sub.fsubaccountname as subaccount_name',
            ])
            ->groupBy('fkasmtid');

        $accountName = null;
        if ($filterAccount !== '') {
            $accountName = Account::query()
                ->where('faccount', $filterAccount)
                ->value('faccname');
        }

        $grandTotal = (float) $records->sum(fn($row) => (float) ($row->famountpay ?? 0));

        return view('reportingkas.print', [
            'pageTitle' => $tranCode === 'BKK' ? 'Laporan Pengeluaran Kas/Bank' : 'Laporan Penerimaan Kas/Bank',
            'records' => $records,
            'detailsByHeader' => $detailsByHeader,
            'filterDateFrom' => $filterDateFrom,
            'filterDateTo' => $filterDateTo,
            'filterAccount' => $filterAccount,
            'filterAccountName' => $accountName,
            'onlyGiroMundur' => $onlyGiroMundur,
            'grandTotal' => $grandTotal,
            'printDate' => Carbon::now()->format('d/m/Y H:i:s'),
            'user_session' => auth('sysuser')->user(),
        ]);
    }
}
