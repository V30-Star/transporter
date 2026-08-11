<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Wewenang User / Permissions
        $userPermsRaw = session('user_restricted_permissions');
        $hasPermissionScope = ($userPermsRaw !== null);
        $userPermissions = array_filter(array_map('trim', explode(',', (string) $userPermsRaw)));

        $checkPerm = function (string $key) use ($hasPermissionScope, $userPermissions) {
            if (!$hasPermissionScope) {
                return true;
            }
            return in_array($key, $userPermissions, true);
        };

        $canViewTotalPiutangUsaha = $checkPerm('dashboardTotalPiutangUsaha');
        $canViewBelumJatuhTempo = $checkPerm('dashboardBelumJatuhTempo');
        $canViewLewatJatuhTempo = $checkPerm('dashboardLewatJatuhTempo');
        $canViewOmsetYtd = $checkPerm('dashboardOmsetYtd');
        $canViewOmsetPenjualanBulan = $checkPerm('dashboardOmsetPenjualanBulan');
        $canViewTopPiutangLewatJatuhTempo = $checkPerm('dashboardTopPiutangLewatJatuhTempo');
        $canViewAnalisaUmurPiutang = $checkPerm('printAnalisaUmurPiutang');

        $selectedYear = (int) $request->input('year', date('Y'));

        // Available years for Omset filter
        $availableYearsQuery = DB::table('tranmt')
            ->where('ftrcode', 'INV')
            ->whereNotNull('fsodate');
        $this->applyBranchVisibilityScope($availableYearsQuery, 'fbranchcode');
        $availableYears = $availableYearsQuery
            ->selectRaw('EXTRACT(YEAR FROM fsodate) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->map(fn($y) => (int) $y)
            ->toArray();

        if (empty($availableYears)) {
            $availableYears = [(int) date('Y')];
        }
        if (!in_array($selectedYear, $availableYears, true)) {
            $selectedYear = $availableYears[0];
        }

        // 1. Omset Penjualan per bulan (Bar Chart) - famountsonet
        $chartLabels = [];
        $chartData = [];
        $totalOmsetTahunIni = 0;

        if ($canViewOmsetPenjualanBulan || $canViewOmsetYtd) {
            $omsetQuery = DB::table('tranmt')
                ->where('ftrcode', 'INV')
                ->whereRaw('EXTRACT(YEAR FROM fsodate) = ?', [$selectedYear]);
            $this->applyBranchVisibilityScope($omsetQuery, 'fbranchcode');

            $rawMonthlyOmset = $omsetQuery
                ->selectRaw('EXTRACT(MONTH FROM fsodate) as month_num, SUM(COALESCE(famountsonet, 0)) as total_omset')
                ->groupByRaw('EXTRACT(MONTH FROM fsodate)')
                ->pluck('total_omset', 'month_num')
                ->toArray();

            $monthsLabels = [
                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agt',
                9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
            ];

            foreach ($monthsLabels as $monthNum => $monthName) {
                $chartLabels[] = $monthName;
                $val = (float) ($rawMonthlyOmset[$monthNum] ?? 0);
                $chartData[] = $val;
                $totalOmsetTahunIni += $val;
            }
        }

        // 2. Total Piutang Usaha (famountremain)
        $piutangBelumJatuhTempo = 0;
        $piutangLewatJatuhTempo = 0;
        $totalPiutangUsaha = 0;

        if ($canViewTotalPiutangUsaha || $canViewBelumJatuhTempo || $canViewLewatJatuhTempo) {
            $piutangBaseQuery = DB::table('tranmt')
                ->where('ftrcode', 'INV')
                ->whereRaw('COALESCE(famountremain, 0) > 0');
            $this->applyBranchVisibilityScope($piutangBaseQuery, 'fbranchcode');

            if ($canViewBelumJatuhTempo || $canViewTotalPiutangUsaha) {
                $piutangBelumJatuhTempoQuery = (clone $piutangBaseQuery)
                    ->where(function ($q) {
                        $q->whereNull('fjatuhtempo')
                          ->orWhereRaw('CAST(fjatuhtempo AS DATE) >= CURRENT_DATE');
                    });
                $piutangBelumJatuhTempo = (float) $piutangBelumJatuhTempoQuery->sum('famountremain');
            }

            if ($canViewLewatJatuhTempo || $canViewTotalPiutangUsaha) {
                $piutangLewatJatuhTempoQuery = (clone $piutangBaseQuery)
                    ->whereNotNull('fjatuhtempo')
                    ->whereRaw('CAST(fjatuhtempo AS DATE) < CURRENT_DATE');
                $piutangLewatJatuhTempo = (float) $piutangLewatJatuhTempoQuery->sum('famountremain');
            }

            $totalPiutangUsaha = $piutangBelumJatuhTempo + $piutangLewatJatuhTempo;
        }

        // Top 5 Overdue Invoices
        $topOverdueList = collect();
        if ($canViewTopPiutangLewatJatuhTempo) {
            $topOverdueQuery = DB::table('tranmt as m')
                ->leftJoin('mscustomer as c', 'm.fcustno', '=', 'c.fcustomercode')
                ->where('m.ftrcode', 'INV')
                ->whereRaw('COALESCE(m.famountremain, 0) > 0')
                ->whereNotNull('m.fjatuhtempo')
                ->whereRaw('CAST(m.fjatuhtempo AS DATE) < CURRENT_DATE')
                ->selectRaw('m.fsono, m.fsodate, m.fjatuhtempo, m.fcustno, c.fcustomername, m.famountremain, (CURRENT_DATE - CAST(m.fjatuhtempo AS DATE)) as days_overdue');
            $this->applyBranchVisibilityScope($topOverdueQuery, 'm.fbranchcode');
            $topOverdueList = $topOverdueQuery->orderBy('days_overdue', 'desc')->limit(5)->get();
        }

        // Last login timestamp (sysuser.updated_at)
        $authUser = auth('sysuser')->user() ?? auth()->user();
        $lastLogin = null;
        if ($authUser) {
            $lastLogin = DB::table('sysuser')
                ->where('fuid', $authUser->fuid ?? null)
                ->value('updated_at') ?? $authUser->updated_at ?? null;
        }

        return view('dashboard', compact(
            'selectedYear',
            'availableYears',
            'chartLabels',
            'chartData',
            'totalOmsetTahunIni',
            'totalPiutangUsaha',
            'piutangBelumJatuhTempo',
            'piutangLewatJatuhTempo',
            'topOverdueList',
            'canViewTotalPiutangUsaha',
            'canViewBelumJatuhTempo',
            'canViewLewatJatuhTempo',
            'canViewOmsetYtd',
            'canViewOmsetPenjualanBulan',
            'canViewTopPiutangLewatJatuhTempo',
            'canViewAnalisaUmurPiutang',
            'lastLogin'
        ));
    }
}
