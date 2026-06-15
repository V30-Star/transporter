<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListingJurnalController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = now()->startOfMonth()->toDateString();
        $dateTo = now()->toDateString();

        $typeOptions = DB::table('tbmaster')
            ->whereRaw('TRIM(ftblcode) = ?', ['JURNAL'])
            ->orderBy('fmastercode')
            ->get()
            ->map(function ($item) {
                $item->fmastercode = trim((string) $item->fmastercode);
                $item->fmastername = trim((string) $item->fmastername);
                return $item;
            });

        return view('listingjurnal.index', [
            'typeOptions' => $typeOptions,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'sortOptions' => $this->sortOptions(),
        ]);
    }

    public function print(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $journalTypes = $request->input('journal_types', []);
        $journalTypes = array_values(array_filter(array_map('trim', (array) $journalTypes)));
        $sortBy = $request->input('sort_by', 'terlama');

        $results = $this->buildQuery($dateFrom, $dateTo, $journalTypes, $sortBy)->get();
        $groupedData = $results->groupBy('fjurnalno');
        $chunkedData = $groupedData->chunk(5);

        return view('listingjurnal.print', [
            'chunkedData' => $chunkedData,
            'totalPages' => $chunkedData->count(),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'selectedTypes' => $journalTypes,
            'user_session' => auth()->user(),
        ]);
    }

    private function buildQuery(string $dateFrom, string $dateTo, array $journalTypes, string $sortBy)
    {
        $jurnalUmum = DB::table('jurnalmt as m')
            ->leftJoin('jurnaldt as d', 'm.fjurnalno', '=', 'd.fjurnalno')
            ->leftJoin('account as acc', 'd.faccount', '=', 'acc.faccount')
            ->selectRaw("m.fjurnalno, m.fjurnaldate, m.fjurnaltype, m.fjurnalnote, m.fbalance, m.fbalance_rp, m.fdatetime, m.fuserid, d.flineno, d.faccount, acc.faccname, d.frefno, d.fsubaccount, d.fdk, d.frate, d.famount, d.famount_rp, d.faccountnote, NULL::integer AS fkasdtid");

        $kasHeader = DB::table('trkasmt as m')
            ->leftJoin('account as acc', 'm.faccountheader', '=', 'acc.faccount')
            ->selectRaw("m.fkasmtno AS fjurnalno, m.fkasmtdate AS fjurnaldate, m.ftrancode AS fjurnaltype, m.fket AS fjurnalnote, m.famountpay AS fbalance, m.famountpay_rp AS fbalance_rp, m.fdatetime, m.fuserid, 1 AS flineno, m.faccountheader AS faccount, acc.faccname, '' AS frefno, '' AS fsubaccount, m.fdkheader AS fdk, m.frate, ABS(m.famountpay) AS famount, ABS(m.famountpay_rp) AS famount_rp, m.fket AS faccountnote, NULL::integer AS fkasdtid");

        $kasDetail = DB::table('trkasmt as m')
            ->leftJoin('trkasdt as d', 'm.fkasmtno', '=', 'd.fkasmtno')
            ->leftJoin('account as acc', 'd.faccount', '=', 'acc.faccount')
            ->selectRaw("m.fkasmtno AS fjurnalno, m.fkasmtdate AS fjurnaldate, m.ftrancode AS fjurnaltype, d.fnote AS fjurnalnote, d.fjurnal AS fbalance, d.fjurnal_rp AS fbalance_rp, m.fdatetime, m.fuserid, COALESCE(d.fnou, 1) + 1 AS flineno, d.faccount, acc.faccname, d.frefno, d.fsubaccount, d.fdk, m.frate, d.fjurnal AS famount, d.fjurnal_rp AS famount_rp, d.fnote AS faccountnote, d.fkasdtid");

        $union = $jurnalUmum->unionAll($kasHeader)->unionAll($kasDetail);

        $query = DB::query()
            ->fromSub($union, 'a')
            ->selectRaw("a.fjurnalno, a.fjurnaldate, a.fjurnaltype, a.fjurnalnote, a.fbalance, a.fbalance_rp, a.fdatetime, a.fuserid, a.flineno, a.faccount, a.faccname, a.frefno, a.fsubaccount, a.fdk, a.frate, a.famount, a.famount_rp, a.faccountnote, CASE WHEN a.fdk = 'D' THEN a.famount END AS debet, CASE WHEN a.fdk = 'K' THEN a.famount END AS kredit")
            ->whereDate('a.fjurnaldate', '>=', $dateFrom)
            ->whereDate('a.fjurnaldate', '<=', $dateTo);

        if ($journalTypes !== []) {
            $query->whereIn('a.fjurnaltype', $journalTypes);
        }

        foreach ($this->sortColumns($sortBy) as $sort) {
            [$column, $direction] = $sort;
            $query->orderBy($column, $direction);
        }

        return $query;
    }

    private function sortOptions(): array
    {
        return [
            'terlama' => 'Terlama ke Terbaru',
            'terbaru' => 'Terbaru ke Terlama',
        ];
    }

    private function sortColumns(string $sortBy): array
    {
        return match ($sortBy) {
            'terbaru' => [
                ['a.fjurnalno', 'desc'],
                ['a.fkasdtid', 'desc'],
            ],
            default => [
                ['a.fjurnalno', 'asc'],
                ['a.fkasdtid', 'asc'],
            ],
        };
    }
}
