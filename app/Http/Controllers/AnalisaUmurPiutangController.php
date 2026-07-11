<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class AnalisaUmurPiutangController extends Controller
{
    public function index()
    {
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get();
        $salesmans = DB::table('mssalesman')->orderBy('fsalesmancode')->get();
        $customers = DB::table('mscustomer')->orderBy('fcustomercode')->get(['fcustomercode', 'fcustomername']);
        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('analisaumurpiutang.index', compact('branches', 'salesmans', 'customers', 'isAuthorized', 'userBranchCode'));
    }

    public function print(Request $request)
    {
        $rows = $this->rows($request);

        return view('analisaumurpiutang.print', [
            'rows' => $rows,
            'request' => $request,
            'title' => 'Analisa Umur Piutang',
            'user_session' => auth('sysuser')->user() ?? auth()->user(),
        ]);
    }

    private function rows(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $dueDateTo = $request->input('due_date_to');
        $cutoffDate = $request->input('mode') === 'due' && $dueDateTo ? $dueDateTo : $dateTo;

        $invoice = DB::table('tranmt as m')
            ->join('mscustomer as c', 'm.fcustno', '=', 'c.fcustomercode')
            // PERUBAHAN DI SINI: m.fsalesman di-cast ke ::text
            ->selectRaw("m.fbranchcode, m.fsono, 'INV' AS fstockmtcode, m.fsodate, m.fcurrency, m.frate, m.fjatuhtempo, m.frefno, m.fcustno, c.fcustomername AS fcustname, m.fsalesman::text AS fsalesman, m.famountso, m.famountso_rp, m.famountso AS fnilainota, m.famountso_rp AS fnilainota_rp")
            ->where('m.ftrcode', 'INV')
            ->where('m.fsodate', '>=', $dateFrom)
            ->where('m.fsodate', '<=', $dateTo . ' 23:59:59');

        $this->applyBaseFilters($invoice, $request, 'm', 'fcustno', true);

        $retur = DB::table('tranmt as m')
            ->join('mscustomer as c', 'm.fcustno', '=', 'c.fcustomercode')
            // PERUBAHAN DI SINI: m.fsalesman di-cast ke ::text
            ->selectRaw("m.fbranchcode, m.fsono, 'INV' AS fstockmtcode, m.fsodate, m.fcurrency, m.frate, m.fjatuhtempo, m.frefno, m.fcustno, c.fcustomername AS fcustname, m.fsalesman::text AS fsalesman, m.famountso, m.famountso_rp, m.famountso AS fnilainota, m.famountso_rp AS fnilainota_rp")
            ->where('m.ftrcode', 'REJ')
            ->where('m.fsodate', '>=', $dateFrom)
            ->where('m.fsodate', '<=', $dateTo . ' 23:59:59');

        $this->applyBaseFilters($retur, $request, 'm', 'fsupplier', true);

        if ($request->input('mode') === 'due' && $dueDateTo) {
            $invoice->where('m.fjatuhtempo', '<=', $dueDateTo);
            $retur->where('m.fstockmtdate', '<=', $dueDateTo);
        }

        $base = $invoice->unionAll($retur);

        $paidKas = DB::table('trkasmt as m')
            ->join('trkasdt as d', 'm.fkasmtid', '=', 'd.fkasmtid')
            ->where('m.ftrancode', 'RCP')
            ->where('m.fkasmtdate', '<=', $cutoffDate)
            ->selectRaw('d.frefno, SUM(d.fkasdtvalue + COALESCE(d.fdiscount, 0)) AS ftotalbayar, SUM(d.fvalue_rp + COALESCE(d.fdiscountrp, 0)) AS ftotalbayar_rp')
            ->groupBy('d.frefno');

        $paidJurnal = DB::table('jurnalmt as m')
            ->join('jurnaldt as d', 'm.fjurnalno', '=', 'd.fjurnalno')
            ->join('account as a', 'a.faccount', '=', 'd.faccount')
            ->where('m.fjurnaldate', '<=', $cutoffDate)
            ->where(function ($q) {
                $q->where(function ($qq) {
                    // Mengganti facc_piutang dengan check ke set_account menggunakan faccount '11401'
                    $qq->whereIn('a.faccupline', function ($sub) {
                        $sub->select('faccupline') // sesuaikan nama kolom target di set_account jika bukan faccupline
                            ->from('set_account')
                            ->where('faccount', '11401');
                    })
                        ->where('d.fdk', 'K');
                })->orWhere(function ($qq) {
                    // Mengganti facc_returpenjualan dengan check ke set_account menggunakan faccount '21181'
                    $qq->whereIn('a.faccount', function ($sub) {
                        $sub->select('faccount')
                            ->from('set_account')
                            ->where('faccount', '21181');
                    })
                        ->where('d.fdk', 'D');
                });
            })
            ->selectRaw('d.frefno, SUM(d.famount) AS ftotalsju, SUM(d.famount_rp) AS ftotalsju_rp')
            ->groupBy('d.frefno');

        return DB::query()
            ->fromSub($base, 'a')
            ->leftJoinSub($paidKas, 'b', 'a.fsono', '=', 'b.frefno')
            ->leftJoinSub($paidJurnal, 'c', 'a.fsono', '=', 'c.frefno')
            ->selectRaw("a.*, CASE WHEN a.fstockmtcode = 'REJ' THEN (ABS(a.fnilainota) - (COALESCE(ABS(b.ftotalbayar), 0) + COALESCE(ABS(c.ftotalsju), 0))) * -1 ELSE a.fnilainota - (COALESCE(ABS(b.ftotalbayar), 0) + COALESCE(ABS(c.ftotalsju), 0)) END AS famountremain, 
            CASE WHEN a.fstockmtcode = 'REJ' THEN (a.fnilainota_rp - (COALESCE(ABS(b.ftotalbayar_rp), 0) + COALESCE(ABS(c.ftotalsju_rp), 0))) * -1 ELSE a.fnilainota_rp - (COALESCE(b.ftotalbayar_rp, 0) + COALESCE(c.ftotalsju_rp, 0)) END AS famountremain_rp, 
            (?::date - a.fjatuhtempo::date) AS umur", [$cutoffDate])
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
                $row->varundue = $umur < 0 ? $amount : 0;
                $row->var30hari = $umur >= 0 && $umur <= 30 ? $amount : 0;
                $row->var60hari = $umur >= 31 && $umur <= 60 ? $amount : 0;
                $row->ppvar90hari = $umur >= 61 && $umur <= 90 ? $amount : 0;
                $row->ppvar91hari = $umur >= 91 && $umur <= 365 ? $amount : 0;
                $row->ppvar1tahun = $umur > 365 ? $amount : 0;
                return $row;
            });
    }

    private function applyBaseFilters($query, Request $request, string $alias, string $customerColumn, bool $salesman): void
    {
        $this->applyBranchVisibilityScope($query, "{$alias}.fbranchcode");
        if ($request->filled('branch_codes')) {
            $query->whereIn("{$alias}.fbranchcode", (array) $request->input('branch_codes'));
        }
        if ($salesman && $request->filled('salesman')) {
            $query->where("{$alias}.fsalesman", $request->input('salesman'));
        }
        if ($request->filled('cust_from')) {
            $query->where("{$alias}.{$customerColumn}", '>=', $request->input('cust_from'));
        }
        if ($request->filled('cust_to')) {
            $query->where("{$alias}.{$customerColumn}", '<=', $request->input('cust_to'));
        }
    }

    public function exportExcel(Request $request)
    {
        $rows = $this->rows($request);
        $groupedData = $rows->groupBy('fcustno');

        $filename = 'Analisa_Umur_Piutang_'.date('YmdHis').'.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');

        $writer = new Writer;
        $writer->openToFile($tempFile);

        $styleTitle = new Style(fontBold: true, fontSize: 14);
        $styleHeader = new Style(fontBold: true, backgroundColor: 'D3D3D3');
        $styleGroup = new Style(fontBold: true, backgroundColor: 'FFE6E6');
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
        $writer->addRow($makeRow(['ANALISA UMUR PIUTANG'], $styleTitle));
        $writer->addRow($makeRow(['Tanggal:', date('d/m/Y').'  Jam: '.date('H:i')]));
        $writer->addRow($makeRow(['Periode:', $request->date_from.' s/d '.$request->date_to]));
        $writer->addRow($makeRow(['Operator:', auth('sysuser')->user()->fname ?? auth()->user()->fname ?? 'User']));
        $writer->addRow($makeRow([]));

        // Header Kolom
        $writer->addRow($makeRow([
            'No.', 'Cab.', 'No. Faktur', 'Tanggal', 'Jatuh Tempo', 'Umur', 'Nilai Faktur', 
            'Un Due', '0-30 Hr', '31-60 Hr', '61-90 Hr', '91-1 Th', '>1 Th'
        ], $styleHeader));

        $grand = ['famountso' => 0, 'undue' => 0, 'd30' => 0, 'd60' => 0, 'd90' => 0, 'd91' => 0, 'd1y' => 0];

        foreach ($groupedData as $custCode => $items) {
            $name = $items->first()->fcustname ?: $custCode;
            
            $tot = [
                'famountso' => $items->sum('famountso'),
                'undue' => $items->sum('varundue'),
                'd30' => $items->sum('var30hari'),
                'd60' => $items->sum('var60hari'),
                'd90' => $items->sum('ppvar90hari'),
                'd91' => $items->sum('ppvar91hari'),
                'd1y' => $items->sum('ppvar1tahun'),
            ];

            foreach ($tot as $key => $value) {
                $grand[$key] += $value;
            }

            // Group Row
            $writer->addRow($makeRow([
                'Customer: '.$custCode.' - '.$name, '', '', '', '', '', '', '', '', '', '', '', ''
            ], $styleGroup));

            foreach ($items as $index => $row) {
                $writer->addRow($makeRow([
                    $index + 1,
                    $row->fbranchcode,
                    $row->fsono,
                    $row->fsodate ? date('d/m/Y', strtotime($row->fsodate)) : '',
                    $row->fjatuhtempo ? date('d/m/Y', strtotime($row->fjatuhtempo)) : '',
                    $row->mu,
                    (float) $row->famountso,
                    (float) $row->varundue,
                    (float) $row->var30hari,
                    (float) $row->var60hari,
                    (float) $row->ppvar90hari,
                    (float) $row->ppvar91hari,
                    (float) $row->ppvar1tahun
                ]));
            }

            // Subtotal Row
            $writer->addRow($makeRow([
                'Subtotal '.$name, '', '', '', '', '',
                (float) $tot['famountso'],
                (float) $tot['undue'],
                (float) $tot['d30'],
                (float) $tot['d60'],
                (float) $tot['d90'],
                (float) $tot['d91'],
                (float) $tot['d1y']
            ], $styleSubtotal));
        }

        // Grand Total Row
        $writer->addRow($makeRow([]));
        $writer->addRow($makeRow([
            'GRAND TOTAL', '', '', '', '', '',
            (float) $grand['famountso'],
            (float) $grand['undue'],
            (float) $grand['d30'],
            (float) $grand['d60'],
            (float) $grand['d90'],
            (float) $grand['d91'],
            (float) $grand['d1y']
        ], $styleGrandTotal));

        $writer->close();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
