<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanUangKasirController extends Controller
{
    public function index()
    {
        return view('laporanuangkasir.index', [
            'branches' => DB::table('mscabang')->orderBy('fcabangkode')->get(),
            'isAuthorized' => $this->canAccessAllBranches(),
            'userBranchCode' => $this->getCurrentBranchCode(),
            'date' => now()->format('Y-m-d'),
        ]);
    }

    public function print(Request $request)
    {
        $date = $request->input('date') ?: Carbon::now()->format('Y-m-d');
        $kasir = trim((string) $request->input('kasir', ''));
        $onlyCash = $request->boolean('only_cash');
        $selectedBranches = array_filter((array) $request->input('branch_codes', []));

        $salesQuery = DB::table('tranmt as m')
            ->where('m.ftrcode', 'INV')
            ->whereDate('m.fsodate', $date)
            ->select([
                'm.fbranchcode',
                'm.fuserid',
                'm.famountso',
                'm.ftunai',
            ]);

        $this->applyBranchVisibilityScope($salesQuery, 'm.fbranchcode');

        if ($selectedBranches) {
            $salesQuery->whereIn('m.fbranchcode', $selectedBranches);
        }

        if ($kasir !== '') {
            $salesQuery->whereRaw('TRIM(m.fuserid) ILIKE ?', ['%' . $kasir . '%']);
        }

        if ($onlyCash) {
            $salesQuery->where(function ($q) {
                $q->where('m.ftunai', '1')
                    ->orWhere('m.ftunai', 1);
            });
        }

        $sales = $salesQuery->get();

        $settlementQuery = DB::table('trkasmt as k')
            ->join('trkasdt as d', 'd.fkasmtid', '=', 'k.fkasmtid')
            ->leftJoin('account as a', 'a.faccount', '=', 'k.faccountno')
            ->whereIn('k.ftrancode', ['RCP', 'BKM'])
            ->whereRaw("TRIM(COALESCE(d.freftype, '')) = 'INV'")
            ->whereDate('k.fkasmtdate', $date)
            ->selectRaw("TRIM(k.fbranchcode) AS branch_code, COALESCE(NULLIF(TRIM(a.faccname), ''), NULLIF(TRIM(k.faccountno), ''), '-') AS account_name, SUM(COALESCE(d.fkasdtvalue, 0)) AS amount")
            ->groupByRaw("TRIM(k.fbranchcode), COALESCE(NULLIF(TRIM(a.faccname), ''), NULLIF(TRIM(k.faccountno), ''), '-')");

        $this->applyBranchVisibilityScope($settlementQuery, 'k.fbranchcode');
        if ($selectedBranches) {
            $settlementQuery->whereIn('k.fbranchcode', $selectedBranches);
        }

        $expenseQuery = DB::table('trkasmt as k')
            ->leftJoin('account as a', 'a.faccount', '=', 'k.faccountno')
            ->where('k.ftrancode', 'BKK')
            ->whereDate('k.fkasmtdate', $date)
            ->selectRaw("TRIM(k.fbranchcode) AS branch_code, COALESCE(NULLIF(TRIM(a.faccname), ''), NULLIF(TRIM(k.faccountno), ''), '-') AS account_name, SUM(COALESCE(k.famountpay, 0)) AS amount")
            ->groupByRaw("TRIM(k.fbranchcode), COALESCE(NULLIF(TRIM(a.faccname), ''), NULLIF(TRIM(k.faccountno), ''), '-')");

        $this->applyBranchVisibilityScope($expenseQuery, 'k.fbranchcode');
        if ($selectedBranches) {
            $expenseQuery->whereIn('k.fbranchcode', $selectedBranches);
        }

        $branchNames = DB::table('mscabang')
            ->pluck('fcabangname', 'fcabangkode')
            ->mapWithKeys(fn ($name, $code) => [trim((string) $code) => $name]);

        $reports = collect();
        $branchCodes = $sales->pluck('fbranchcode')
            ->merge($settlementQuery->pluck('branch_code'))
            ->merge($expenseQuery->pluck('branch_code'))
            ->map(fn ($code) => trim((string) $code))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $settlements = $settlementQuery->get()->groupBy('branch_code');
        $expenses = $expenseQuery->get()->groupBy('branch_code');

        foreach ($branchCodes as $branchCode) {
            $branchSales = $sales->filter(fn ($row) => trim((string) $row->fbranchcode) === $branchCode);
            $credit = $branchSales
                ->filter(fn ($row) => (string) $row->ftunai !== '1')
                ->sum(fn ($row) => (float) $row->famountso);
            $cash = $branchSales
                ->filter(fn ($row) => (string) $row->ftunai === '1')
                ->sum(fn ($row) => (float) $row->famountso);

            $accountRows = collect();
            foreach ($settlements->get($branchCode, collect()) as $row) {
                $accountRows->push([
                    'account' => $row->account_name,
                    'pelunasan' => (float) $row->amount,
                    'pengeluaran' => 0,
                ]);
            }
            foreach ($expenses->get($branchCode, collect()) as $row) {
                $existing = $accountRows->firstWhere('account', $row->account_name);
                if ($existing) {
                    $existing['pengeluaran'] = (float) $row->amount;
                    $accountRows = $accountRows->map(fn ($item) => $item['account'] === $row->account_name ? $existing : $item);
                } else {
                    $accountRows->push([
                        'account' => $row->account_name,
                        'pelunasan' => 0,
                        'pengeluaran' => (float) $row->amount,
                    ]);
                }
            }

            $accountRows = $accountRows->map(function ($row) {
                $row['saldo'] = $row['pelunasan'] - $row['pengeluaran'];
                return $row;
            })->values();

            $reports->push([
                'branch_code' => $branchCode,
                'branch_name' => $branchNames->get($branchCode, $branchCode),
                'credit' => $credit,
                'cash' => $cash,
                'total_sales' => $credit + $cash,
                'accounts' => $accountRows,
                'total_pelunasan' => $accountRows->sum('pelunasan'),
                'total_pengeluaran' => $accountRows->sum('pengeluaran'),
                'total_saldo' => $accountRows->sum('saldo'),
            ]);
        }

        $company = company_setting() ?: (object) [];

        return view('laporanuangkasir.print', compact(
            'reports', 'date', 'kasir', 'onlyCash', 'company'
        ) + [
            'operator' => auth('sysuser')->user()?->fname ?? auth()->user()?->fname ?? 'User',
            'printedAt' => now(),
        ]);
    }
}
