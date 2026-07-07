<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class AnalisaUmurHutangController extends Controller
{
    public function index()
    {
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get();
        $suppliers = DB::table('mssupplier')
            ->orderBy('fsuppliercode')
            ->get(['fsuppliercode', 'fsuppliername']);
        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('analisaumurhutang.index', compact('branches', 'suppliers', 'isAuthorized', 'userBranchCode'));
    }

    public function print(Request $request)
    {
        $rows = $this->rows($request);

        return view('analisaumurhutang.print', [
            'rows' => $rows,
            'request' => $request,
            'title' => 'Analisa Umur Hutang',
        ]);
    }

    private function rows(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $dueDateTo = $request->input('due_date_to');
        $cutoffDate = $request->input('mode') === 'due' && $dueDateTo ? $dueDateTo : $dateTo;

        $base = DB::table('trstockmt as h')
            ->join('mssupplier as s', 'h.fsupplier', '=', 's.fsuppliercode')
            ->selectRaw("h.fbranchcode, h.fstockmtno AS fsono, h.fstockmtcode, h.fstockmtdate AS fsodate, h.fjatuhtempo, s.fsuppliername AS fcustname, s.fsuppliername AS fsuppliername, h.fsupplier AS fcustno, h.fsupplier AS fsuppliercode, h.fcurrency, h.frate, h.fusercreate AS fuserid, h.frefno, CASE WHEN h.fstockmtcode = 'BUY' THEN h.famountmt ELSE h.famountmt * -1 END AS famountso, CASE WHEN h.fstockmtcode = 'BUY' THEN h.famountmt_rp ELSE h.famountmt_rp * -1 END AS famountmt_rp, h.famountmt AS fnilainota, h.famountmt_rp AS fnilainota_rp")
            ->whereIn('h.fstockmtcode', ['BUY', 'REB'])
            ->where('h.fstockmtdate', '>=', $dateFrom)
            ->where('h.fstockmtdate', '<=', $dateTo . ' 23:59:59');

        $this->applyBaseFilters($base, $request);

        if ($request->input('mode') === 'due' && $dueDateTo) {
            $base->where('h.fjatuhtempo', '<=', $dueDateTo);
        }

        $paidKas = DB::table('trkasmt as m')
            ->join('trkasdt as d', 'm.fkasmtid', '=', 'd.fkasmtid')
            ->where('m.ftrancode', 'PAY')
            ->where('m.fkasmtdate', '<=', $cutoffDate)
            ->selectRaw('d.frefno, SUM(d.fkasdtvalue + COALESCE(d.fdiscount, 0)) AS ftotalbayar, SUM(d.fvalue_rp + COALESCE(d.fdiscountrp, 0)) AS ftotalbayar_rp')
            ->groupBy('d.frefno');

        $paidJurnal = DB::table('jurnalmt as m')
            ->join('jurnaldt as d', 'm.fjurnalno', '=', 'd.fjurnalno')
            ->join('account as a', 'a.faccount', '=', 'd.faccount')
            ->where('m.fjurnaldate', '<=', $cutoffDate)
            ->where(function ($q) {
                $q->where(function ($qq) {
                    $qq->whereIn('a.faccupline', function ($sub) {
                        $sub->select('faccount')
                            ->from('set_account')
                            ->where('faccount_name', 'HUTANGDAGANG');
                    })->where('d.fdk', 'D');
                })->orWhere(function ($qq) {
                    $qq->whereIn('a.faccount', function ($sub) {
                        $sub->select('faccount')
                            ->from('set_account')
                            ->where('faccount_name', 'RETURPEMBELIAN');
                    })->where('d.fdk', 'K');
                });
            })
            ->selectRaw('d.frefno, SUM(d.famount) AS ftotalsju, SUM(d.famount_rp) AS ftotalsju_rp')
            ->groupBy('d.frefno');

        return DB::query()
            ->fromSub($base, 'a')
            ->leftJoinSub($paidKas, 'b', 'a.fsono', '=', 'b.frefno')
            ->leftJoinSub($paidJurnal, 'c', 'a.fsono', '=', 'c.frefno')
            ->selectRaw("a.*, CASE WHEN a.fstockmtcode = 'REB' THEN (ABS(a.fnilainota) - (COALESCE(ABS(b.ftotalbayar), 0) + COALESCE(ABS(c.ftotalsju), 0))) * -1 ELSE a.fnilainota - (COALESCE(ABS(b.ftotalbayar), 0) + COALESCE(ABS(c.ftotalsju), 0)) END AS famountremain, CASE WHEN a.fstockmtcode = 'REB' THEN (a.fnilainota_rp - (COALESCE(ABS(b.ftotalbayar_rp), 0) + COALESCE(ABS(c.ftotalsju_rp), 0))) * -1 ELSE a.fnilainota_rp - (COALESCE(b.ftotalbayar_rp, 0) + COALESCE(c.ftotalsju_rp, 0)) END AS famountremain_rp, (?::date - a.fjatuhtempo::date) AS umur", [$cutoffDate])
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
                $row->fsisapiu = $amount;
                $row->varundue = $umur < 0 ? $amount : 0;
                $row->var30hari = $umur >= 0 && $umur <= 30 ? $amount : 0;
                $row->var60hari = $umur >= 31 && $umur <= 60 ? $amount : 0;
                $row->ppvar90hari = $umur >= 61 && $umur <= 90 ? $amount : 0;
                $row->ppvar91hari = $umur >= 91 && $umur <= 365 ? $amount : 0;
                $row->ppvar1tahun = $umur > 365 ? $amount : 0;
                return $row;
            });
    }

    private function applyBaseFilters($query, Request $request): void
    {
        $this->applyBranchVisibilityScope($query, 'h.fbranchcode');
        if ($request->filled('branch_codes')) {
            $query->whereIn('h.fbranchcode', (array) $request->input('branch_codes'));
        }
        if ($request->filled('supplier')) {
            $query->where('h.fsupplier', $request->input('supplier'));
        }
    }

    public function exportExcel(Request $request)
    {
        $rows = $this->rows($request);
        $groupedData = $rows->groupBy('fcustno');

        $filename = 'Analisa_Umur_Hutang_'.date('YmdHis').'.xlsx';
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
        $writer->addRow($makeRow(['ANALISA UMUR HUTANG'], $styleTitle));
        $writer->addRow($makeRow(['Tanggal:', date('d/m/Y').'  Jam: '.date('H:i')]));
        $writer->addRow($makeRow(['Periode:', $request->date_from.' s/d '.$request->date_to]));
        $writer->addRow($makeRow(['Operator:', auth('sysuser')->user()->fname ?? auth()->user()->fname ?? 'User']));
        $writer->addRow($makeRow([]));

        // Header Kolom
        $writer->addRow($makeRow([
            'No.', 'Cab.', 'No. Faktur', 'Tanggal', 'Jatuh Tempo', 'Umur(Hr)', 'Nilai Faktur', 
            'Un Due', '0-30 Hari', '31-60 Hari', '61-90 Hari', '91-1 Tahun', '>1 Tahun'
        ], $styleHeader));

        $grand = ['faktur' => 0, 'undue' => 0, 'd30' => 0, 'd60' => 0, 'd90' => 0, 'd91' => 0, 'd1y' => 0];

        foreach ($groupedData as $supplierCode => $items) {
            $name = $items->first()->fsuppliername ?: $supplierCode;
            
            $tot = [
                'faktur' => $items->sum('famountso'),
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
                'Supplier: '.$supplierCode.' - '.$name, '', '', '', '', '', '', '', '', '', '', '', ''
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
                'Total '.$name, '', '', '', '', '',
                (float) $tot['faktur'],
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
            (float) $grand['faktur'],
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
