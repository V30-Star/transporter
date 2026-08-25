<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class ReportingRekapPenjualanSalesProdukController extends Controller
{
    public function index()
    {
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get();
        $groups = DB::table('msprd')->select('fgroupcode')->whereNotNull('fgroupcode')->distinct()->orderBy('fgroupcode')->get();
        $mereks = DB::table('msprd')->select('fmerek')->whereNotNull('fmerek')->distinct()->orderBy('fmerek')->get();
        $salesmans = DB::table('mssalesman')->orderBy('fsalesmancode')->get(['fsalesmancode', 'fsalesmanname']);
        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('reportingrekappenjualansalesproduk.index', compact('branches', 'groups', 'mereks', 'salesmans', 'isAuthorized', 'userBranchCode'));
    }

    public function print(Request $request)
    {
        [$rows, $filters] = $this->rowsAndFilters($request);

        return view('reportingrekappenjualansalesproduk.print', [
            'rows' => $rows,
            'filters' => $filters,
            'user_session' => auth('sysuser')->user() ?? auth()->user(),
        ]);
    }

    public function exportExcel(Request $request)
    {
        [$rows, $filters] = $this->rowsAndFilters($request);
        $filename = 'Rekap_Penjualan_Sales_By_Produk_'.date('YmdHis').'.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $writer = new Writer;
        $writer->openToFile($tempFile);

        $bold = new Style(fontBold: true);
        $header = new Style(fontBold: true, backgroundColor: 'D9EAF7');
        $grand = new Style(fontBold: true, backgroundColor: '111827', fontColor: 'FFFFFF');
        $makeRow = fn (array $values, ?Style $style = null) => new Row(array_map(fn ($value) => $style ? Cell::fromValue($value, $style) : Cell::fromValue($value), $values));

        $writer->addRow($makeRow(['PT. UTALIYA'], $bold));
        $writer->addRow($makeRow(['Rekap Penjualan Sales By Produk'], $bold));
        $writer->addRow($makeRow(['Periode', $filters['date_from'].' s/d '.$filters['date_to']]));
        $writer->addRow($makeRow(['Cabang', $filters['branch_label']]));
        $writer->addRow($makeRow(['Salesman', $filters['salesman_label']]));
        $writer->addRow($makeRow(['Merek', $filters['merek_label']]));
        $writer->addRow($makeRow(['Group Produk', $filters['group_label']]));
        $writer->addRow($makeRow([]));
        $writer->addRow($makeRow(['Salesman', 'Group/Merek', 'Produk#', 'Nama Produk', 'Qty.Besar', 'Satuan Besar', 'Qty.Kecil', 'Satuan Kecil', 'Jumlah'], $header));

        foreach ($rows as $row) {
            $writer->addRow($makeRow([
                $row->fcustno.' - '.$row->salesman_name,
                $row->fgroupcode,
                $row->fprdcode,
                $row->fprdname,
                (float) $row->fqtybesar,
                $row->fsatuanbesar,
                (float) $row->fqtykecil,
                $row->fsatuankecil,
                (float) $row->totalnota,
            ]));
        }

        $writer->addRow($makeRow(['GRAND TOTAL', '', '', '', '', '', '', '', (float) $rows->sum('totalnota')], $grand));
        $writer->close();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function rowsAndFilters(Request $request): array
    {
        $filters = $this->filters($request);
        $groupExpr = $filters['grouping_by'] === 'BY_MEREK' ? 'TRIM(p.fmerek)' : 'TRIM(p.fgroupcode)';

        $so = DB::table('tranmt as m')
            ->join('trandt as d', 'm.fsono', '=', 'd.fsono')
            ->join('mscustomer as c', 'm.fcustno', '=', 'c.fcustomercode')
            ->join('msprd as p', 'd.fprdcode', '=', 'p.fprdcode')
            ->leftJoin('mssalesman as s', DB::raw('m.fsalesman::text'), '=', 's.fsalesmancode')
            ->selectRaw("m.fsalesman::text AS fcustno, MIN(s.fsalesmanname) AS salesman_name, CAST(MIN({$groupExpr}) AS CHAR(15)) AS fgroupcode")
            ->selectRaw("d.fprdcode, MIN(p.fprdname) AS fprdname, MIN(p.fsatuanbesar) AS fsatuanbesar, MIN(p.fsatuankecil) AS fsatuankecil")
            ->selectRaw('SUM(CAST(d.fqtykecil AS Numeric) / NULLIF(CAST(p.fqtykecil AS Numeric), 0)) AS fqtybesar')
            ->selectRaw('SUM(CAST(d.fqtykecil AS Numeric)) AS fqtykecil')
            ->selectRaw("SUM((d.fsalesnet * d.fqty) - ((d.fsalesnet * d.fqty) * (COALESCE(CAST(NULLIF(d.fdisc, '') AS NUMERIC), 0) / 100))) AS totalnota")
            ->where('m.fsodate', '>=', $filters['date_from'])
            ->where('m.fsodate', '<=', $filters['date_to'].' 23:59:59')
            ->groupBy('m.fsalesman', 'd.fprdcode');

        $rej = DB::table('trstockmt as m')
            ->join('trstockdt as d', 'm.fstockmtno', '=', 'd.fstockmtno')
            ->join('mscustomer as c', 'm.fsupplier', '=', 'c.fcustomercode')
            ->join('msprd as p', 'd.fprdcode', '=', 'p.fprdcode')
            ->leftJoin('mssalesman as s', DB::raw('m.fsalesman::text'), '=', 's.fsalesmancode')
            ->selectRaw("m.fsalesman::text AS fcustno, MIN(s.fsalesmanname) AS salesman_name, CAST(MIN({$groupExpr}) AS CHAR(15)) AS fgroupcode")
            ->selectRaw("d.fprdcode, MIN(p.fprdname) AS fprdname, MIN(p.fsatuanbesar) AS fsatuanbesar, MIN(p.fsatuankecil) AS fsatuankecil")
            ->selectRaw('SUM(CAST(d.fqtykecil AS Numeric) / NULLIF(CAST(p.fqtykecil AS Numeric), 0)) * -1 AS fqtybesar')
            ->selectRaw('SUM(CAST(d.fqtykecil AS Numeric)) * -1 AS fqtykecil')
            ->selectRaw('SUM(d.ftotprice) * -1 AS totalnota')
            ->where('m.fstockmtcode', 'REJ')
            ->where('m.fstockmtdate', '>=', $filters['date_from'])
            ->where('m.fstockmtdate', '<=', $filters['date_to'].' 23:59:59')
            ->groupBy('m.fsalesman', 'd.fprdcode');

        $this->applyFilters($so, $filters, 'm', 'p');
        $this->applyFilters($rej, $filters, 'm', 'p');

        $rows = DB::query()
            ->fromSub($so->unionAll($rej), 'x')
            ->selectRaw('fcustno, MIN(salesman_name) AS salesman_name, fgroupcode, fprdcode, MIN(fprdname) AS fprdname')
            ->selectRaw('MIN(fsatuanbesar) AS fsatuanbesar, MIN(fsatuankecil) AS fsatuankecil')
            ->selectRaw('SUM(fqtybesar) AS fqtybesar, SUM(fqtykecil) AS fqtykecil, SUM(totalnota) AS totalnota')
            ->groupBy('fcustno', 'fgroupcode', 'fprdcode')
            ->orderBy('fcustno')
            ->orderBy('fgroupcode')
            ->orderBy('fprdcode')
            ->get();

        return [$rows, $filters];
    }

    private function applyFilters($query, array $filters, string $m, string $p): void
    {
        $this->applyBranchVisibilityScope($query, "{$m}.fbranchcode");
        if ($filters['branch_codes']) {
            $query->whereIn("{$m}.fbranchcode", $filters['branch_codes']);
        }
        if ($filters['group_produk_in']) {
            $query->whereIn("{$p}.fgroupcode", $filters['group_produk_in']);
        }
        if ($filters['salesman_id'] !== '') {
            $query->whereRaw("CAST({$m}.fsalesman AS text) = ?", [$filters['salesman_id']]);
        }
        if ($filters['merek_id'] !== '') {
            $query->whereRaw("TRIM({$p}.fmerek) = ?", [$filters['merek_id']]);
        }
    }

    private function filters(Request $request): array
    {
        $branches = array_values(array_filter((array) $request->input('branch_codes', [])));
        $groups = array_values(array_filter((array) $request->input('group_produk_in', [])));

        return [
            'date_from' => $request->input('date_from', now()->startOfMonth()->toDateString()),
            'date_to' => $request->input('date_to', now()->toDateString()),
            'branch_codes' => $branches,
            'group_produk_in' => $groups,
            'salesman_id' => trim((string) $request->input('salesman_id', '')),
            'merek_id' => trim((string) $request->input('merek_id', '')),
            'grouping_by' => $request->input('grouping_by') === 'BY_MEREK' ? 'BY_MEREK' : 'BY_GROUP_PRODUK',
            'branch_label' => $branches ? implode(', ', $branches) : 'Semua Cabang',
            'group_label' => $groups ? implode(', ', $groups) : 'Semua',
            'salesman_label' => $request->input('salesman_id') ?: 'Semua Salesman',
            'merek_label' => $request->input('merek_id') ?: 'Semua Merek',
        ];
    }
}
