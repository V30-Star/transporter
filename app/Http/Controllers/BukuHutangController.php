<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class BukuHutangController extends Controller
{
    private const DEFAULT_HUTANG_ACCOUNTS = ['2101', '21100', '21110', '21111', '21112', '21113', '21114'];

    public function index()
    {
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get();
        $suppliers = DB::table('mssupplier')->orderBy('fsuppliercode')->get(['fsuppliercode', 'fsuppliername']);
        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('bukuhutang.index', compact('branches', 'suppliers', 'isAuthorized', 'userBranchCode'));
    }

    public function print(Request $request)
    {
        return view('bukuhutang.print', [
            'rows' => $this->rows($request),
            'request' => $request,
            'title' => 'Buku Hutang',
            'user_session' => auth('sysuser')->user() ?? auth()->user(),
        ]);
    }

    private function rows(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $accounts = $this->hutangAccounts();

        $saldoKas = DB::table('trkasmt as m')
            ->join('trkasdt as d', 'm.fkasmtid', '=', 'd.fkasmtid')
            ->join('account as a', 'a.faccount', '=', 'd.faccount')
            ->where('m.fkasmtdate', '<', $dateFrom)
            ->whereIn('a.faccount', $accounts)
            ->selectRaw("d.faccount, MIN(a.faccname) AS faccname, d.fsubaccount AS fsubaccoun, 'Saldo Awal' AS fjurnalno, CAST(? AS DATE) - 1 AS fjurnaltgl, 'Saldo Awal' AS fjurnalref, MIN(a.fnormal) AS fnormal, 'D' AS fdk, 0 AS famountdb, 0 AS famountcr, SUM(CASE WHEN a.fnormal = d.fdk THEN d.fjurnal ELSE d.fjurnal * -1 END) AS fsaldo_awal, '0' AS fpriority", [$dateFrom])
            ->groupBy('d.faccount', 'd.fsubaccount', 'd.fdk', 'a.fnormal');

        $this->applyFilters($saldoKas, $request, 'm', 'd');

        $saldoJurnal = DB::table('jurnalmt as m')
            ->join('jurnaldt as d', 'm.fjurnalno', '=', 'd.fjurnalno')
            ->join('account as a', 'a.faccount', '=', 'd.faccount')
            ->where('m.fjurnaldate', '<', $dateFrom)
            ->where('d.fsubaccount', '<>', '')
            ->whereIn('a.faccount', $accounts)
            ->selectRaw("d.faccount, MIN(a.faccname) AS faccname, d.fsubaccount AS fsubaccoun, 'Saldo Awal' AS fjurnalno, CAST(? AS DATE) - 1 AS fjurnaltgl, 'Saldo Awal' AS fjurnalref, MIN(a.fnormal) AS fnormal, 'D' AS fdk, 0 AS famountdb, 0 AS famountcr, SUM(CASE WHEN a.fnormal = d.fdk THEN d.famount ELSE d.famount * -1 END) AS fsaldo_awal, '0' AS fpriority", [$dateFrom])
            ->groupBy('d.faccount', 'd.fsubaccount', 'd.fdk', 'a.fnormal');

        $this->applyFilters($saldoJurnal, $request, 'm', 'd');

        $mutasiKas = DB::table('trkasmt as m')
            ->join('trkasdt as d', 'm.fkasmtid', '=', 'd.fkasmtid')
            ->join('account as a', 'a.faccount', '=', 'd.faccount')
            ->where('m.fkasmtdate', '>=', $dateFrom)
            ->where('m.fkasmtdate', '<=', $dateTo . ' 23:59:59')
            ->whereIn('a.faccount', $accounts)
            ->selectRaw("d.faccount, a.faccname, d.fsubaccount AS fsubaccoun, m.fkasmtno AS fjurnalno, m.fkasmtdate AS fjurnaltgl, TRIM(d.frefno) AS fjurnalref, a.fnormal, d.fdk, CASE WHEN d.fdk = 'D' THEN d.fjurnal ELSE 0 END AS famountdb, CASE WHEN d.fdk = 'K' THEN d.fjurnal ELSE 0 END AS famountcr, CASE WHEN a.fnormal = d.fdk THEN d.fjurnal ELSE d.fjurnal * -1 END AS fsaldo_awal, CASE WHEN a.fnormal = d.fdk THEN '0' ELSE '1' END AS fpriority");

        $this->applyFilters($mutasiKas, $request, 'm', 'd');

        $mutasiJurnal = DB::table('jurnalmt as m')
            ->join('jurnaldt as d', 'm.fjurnalno', '=', 'd.fjurnalno')
            ->join('account as a', 'a.faccount', '=', 'd.faccount')
            ->where('m.fjurnaldate', '>=', $dateFrom)
            ->where('m.fjurnaldate', '<=', $dateTo . ' 23:59:59')
            ->where('d.fsubaccount', '<>', '')
            ->whereIn('a.faccount', $accounts)
            ->selectRaw("d.faccount, a.faccname, d.fsubaccount AS fsubaccoun, m.fjurnalno AS fjurnalno, m.fjurnaldate AS fjurnaltgl, TRIM(COALESCE(NULLIF(d.frefno, ''), NULLIF(d.frefno, ''))) AS fjurnalref, a.fnormal, d.fdk, CASE WHEN d.fdk = 'D' THEN d.famount ELSE 0 END AS famountdb, CASE WHEN d.fdk = 'K' THEN d.famount ELSE 0 END AS famountcr, CASE WHEN a.fnormal = d.fdk THEN d.famount ELSE d.famount * -1 END AS fsaldo_awal, CASE WHEN a.fnormal = d.fdk THEN '0' ELSE '1' END AS fpriority");

        $this->applyFilters($mutasiJurnal, $request, 'm', 'd');

        $ledger = $saldoKas->unionAll($saldoJurnal)->unionAll($mutasiKas)->unionAll($mutasiJurnal);

        $rows = DB::query()
            ->fromSub($ledger, 'l')
            ->leftJoin('mssupplier as s', 'l.fsubaccoun', '=', 's.fsuppliercode')
            ->selectRaw("l.faccount, l.faccname, l.fsubaccoun AS fsupplier, s.fsuppliername, CONCAT(COALESCE(s.fsuppliername, l.fsubaccoun), ' (', TRIM(l.fsubaccoun), ')') AS fsupplier_profile, l.fjurnalno, l.fjurnaltgl, l.fjurnalref AS faccountno, l.fdk, l.famountdb, l.famountcr, l.fsaldo_awal, l.fpriority")
            ->orderBy('l.faccount')
            ->orderBy('l.fsubaccoun')
            ->orderBy('l.fjurnalref')
            ->orderBy('l.fjurnaltgl')
            ->orderBy('l.fpriority')
            ->get();

        return $this->attachRunningBalance($rows);
    }

    private function hutangAccounts(): array
    {
        $configured = DB::table('set_account')
            ->where('faccount_name', 'HUTANGDAGANG')
            ->whereNotNull('faccount')
            ->pluck('faccount')
            ->filter()
            ->values()
            ->all();

        return array_values(array_unique(array_merge(self::DEFAULT_HUTANG_ACCOUNTS, $configured)));
    }

    private function attachRunningBalance($rows)
    {
        return $rows
            ->groupBy(fn($row) => trim((string) $row->faccount) . '|' . trim((string) $row->fsupplier))
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

    private function applyFilters($query, Request $request, string $headerAlias, string $detailAlias): void
    {
        $this->applyBranchVisibilityScope($query, "{$headerAlias}.fbranchcode");

        if ($request->filled('branch_codes')) {
            $query->whereIn("{$headerAlias}.fbranchcode", (array) $request->input('branch_codes'));
        }
        if ($request->filled('supplier_from')) {
            $query->where("{$detailAlias}.fsubaccount", '>=', $request->input('supplier_from'));
        }
        if ($request->filled('supplier_to')) {
            $query->where("{$detailAlias}.fsubaccount", '<=', $request->input('supplier_to'));
        }
    }

    public function exportExcel(Request $request)
    {
        $rows = $this->rows($request);
        $groupedData = $rows->groupBy('faccount');

        $filename = 'Buku_Hutang_'.date('YmdHis').'.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');

        $writer = new Writer;
        $writer->openToFile($tempFile);

        $styleTitle = new Style(fontBold: true, fontSize: 14);
        $styleHeader = new Style(fontBold: true, backgroundColor: 'D3D3D3');
        $styleGroup = new Style(fontBold: true, backgroundColor: 'E2E8F0');
        $styleSubgroup = new Style(fontBold: true, backgroundColor: 'FFE6E6');
        $styleSubtotal = new Style(fontBold: true, backgroundColor: 'FFF0F0');
        $styleGrandTotal = new Style(fontBold: true, backgroundColor: '333333', fontColor: 'FFFFFF');

        $makeRow = function (array $values, ?Style $style = null): Row {
            $cells = array_map(
                fn ($value) => $style ? Cell::fromValue($value, $style) : Cell::fromValue($value),
                $values
            );
            return new Row($cells);
        };

        // Header Informasi
        $writer->addRow($makeRow(['BUKU HUTANG'], $styleTitle));
        $writer->addRow($makeRow(['Tanggal:', date('d/m/Y').'  Jam: '.date('H:i')]));
        $writer->addRow($makeRow(['Periode:', $request->date_from.' s/d '.$request->date_to]));
        $writer->addRow($makeRow(['Operator:', auth('sysuser')->user()->fname ?? auth()->user()->fname ?? 'User']));
        $writer->addRow($makeRow([]));

        // Header Kolom
        $writer->addRow($makeRow([
            'Tanggal', 'Jurnal', 'No. Invoice', 'Debet', 'Kredit', 'Saldo'
        ], $styleHeader));

        foreach ($groupedData as $account => $accountRows) {
            $accountName = $accountRows->first()->faccname ?: $account;
            $accountDebit = 0;
            $accountCredit = 0;
            $accountSaldo = 0;

            // Account Row
            $writer->addRow($makeRow([
                'Account: '.$account.' - '.$accountName, '', '', '', '', ''
            ], $styleGroup));

            foreach ($accountRows->groupBy('fsupplier') as $supplier => $items) {
                $supplierName = $items->first()->fsuppliername ?: $supplier;
                $debit = $items->sum('famountdb');
                $credit = $items->sum('famountcr');
                $saldo = (float) ($items->last()->ppvarsaldo ?? 0);
                $accountDebit += $debit;
                $accountCredit += $credit;
                $accountSaldo += $saldo;

                // Supplier Row
                $writer->addRow($makeRow([
                    '  Supplier: '.$supplier.' - '.$supplierName, '', '', '', '', ''
                ], $styleSubgroup));

                foreach ($items as $row) {
                    $writer->addRow($makeRow([
                        $row->fjurnaltgl ? date('d/m/Y', strtotime($row->fjurnaltgl)) : '',
                        $row->fjurnalno,
                        $row->faccountno,
                        (float) $row->famountdb,
                        (float) $row->famountcr,
                        (float) $row->ppvarsaldo
                    ]));
                }

                // Supplier Total Row
                $writer->addRow($makeRow([
                    '  Saldo Akhir '.$supplierName, '', '',
                    (float) $debit,
                    (float) $credit,
                    (float) $saldo
                ], $styleSubtotal));
            }

            // Account Total Row
            $writer->addRow($makeRow([
                'Saldo Akhir '.$accountName, '', '',
                (float) $accountDebit,
                (float) $accountCredit,
                (float) $accountSaldo
            ], $styleGrandTotal));
        }

        $writer->close();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
