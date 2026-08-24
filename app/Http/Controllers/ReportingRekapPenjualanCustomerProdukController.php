<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class ReportingRekapPenjualanCustomerProdukController extends Controller
{
    public function index()
    {
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get();
        $groups = DB::table('msprd')->select('fgroupcode')->whereNotNull('fgroupcode')->distinct()->orderBy('fgroupcode')->get();
        $customers = DB::table('mscustomer')->orderBy('fcustomercode')->get(['fcustomercode', 'fcustomername']);
        $salesmans = DB::table('mssalesman')->orderBy('fsalesmancode')->get(['fsalesmancode', 'fsalesmanname']);
        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('reportingrekappenjualancustomerproduk.index', compact('branches', 'groups', 'customers', 'salesmans', 'isAuthorized', 'userBranchCode'));
    }

    public function print(Request $request)
    {
        [$rows, $filters] = $this->rowsAndFilters($request);

        return view('reportingrekappenjualancustomerproduk.print', [
            'rows' => $rows,
            'filters' => $filters,
            'user_session' => auth('sysuser')->user() ?? auth()->user(),
        ]);
    }

    public function exportExcel(Request $request)
    {
        [$rows, $filters] = $this->rowsAndFilters($request);
        $filename = 'Rekap_Penjualan_Customer_Per_Produk_'.date('YmdHis').'.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $writer = new Writer;
        $writer->openToFile($tempFile);

        $bold = new Style(fontBold: true);
        $header = new Style(fontBold: true, backgroundColor: 'D9EAF7');
        $subtotal = new Style(fontBold: true, backgroundColor: 'F3F4F6');
        $grand = new Style(fontBold: true, backgroundColor: '111827', fontColor: 'FFFFFF');
        $makeRow = fn (array $values, ?Style $style = null) => new Row(array_map(fn ($value) => $style ? Cell::fromValue($value, $style) : Cell::fromValue($value), $values));

        $writer->addRow($makeRow(['PT. UTALIYA'], $bold));
        $writer->addRow($makeRow(['Rekap Penjualan Customer By Produk'], $bold));
        $writer->addRow($makeRow(['Periode', $filters['date_from'].' s/d '.$filters['date_to']]));
        $writer->addRow($makeRow(['Cabang', $filters['branch_label']]));
        $writer->addRow($makeRow(['Salesman', $filters['salesman_label']]));
        $writer->addRow($makeRow(['Customer', $filters['customer_label']]));
        $writer->addRow($makeRow(['Group/Merek', $filters['group_label']]));
        $writer->addRow($makeRow([]));
        $writer->addRow($makeRow(['Customer', 'Group/Merek', 'Produk#', 'Nama Produk', 'Qty.Besar', 'Satuan Besar', 'Qty.Kecil', 'Satuan Kecil', 'Jumlah'], $header));

        if ($filters['report_type'] === 'REKAP') {
            foreach ($rows->groupBy(fn ($row) => $row->fcustno.'|'.$row->fgroupcode) as $groupRows) {
                $first = $groupRows->first();
                $custLabel = $first->customer_name ? $first->customer_name . ' (' . $first->fcustno . ')' : $first->fcustno;
                $writer->addRow($makeRow([
                    $custLabel,
                    $first->fgroupcode,
                    '',
                    'TOTAL '.$first->fgroupcode,
                    (float) $groupRows->sum('fqtybesar'),
                    '',
                    (float) $groupRows->sum('fqtykecil'),
                    '',
                    (float) $groupRows->sum('totalnota'),
                ], $subtotal));
            }
        } else {
            foreach ($rows as $row) {
                $custLabel = $row->customer_name ? $row->customer_name . ' (' . $row->fcustno . ')' : $row->fcustno;
                $writer->addRow($makeRow([
                    $custLabel,
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

        $query = DB::table('tranmt as m')
            ->join('trandt as d', 'm.fsono', '=', 'd.fsono')
            ->join('mscustomer as c', 'm.fcustno', '=', 'c.fcustomercode')
            ->join('msprd as p', 'd.fprdcode', '=', 'p.fprdcode')
            ->selectRaw("m.fcustno, MIN(c.fcustomername) AS customer_name, CAST(MIN({$groupExpr}) AS CHAR(15)) AS fgroupcode")
            ->selectRaw("d.fprdcode, MIN(p.fprdname) AS fprdname, MIN(p.fsatuanbesar) AS fsatuanbesar, MIN(p.fsatuankecil) AS fsatuankecil")
            ->selectRaw('SUM(CAST(d.fqtykecil AS Numeric) / NULLIF(CAST(p.fqtykecil AS Numeric), 0)) AS fqtybesar')
            ->selectRaw('SUM(CAST(d.fqtykecil AS Numeric)) AS fqtykecil')
            ->selectRaw("SUM((d.fsalesnet * d.fqty) - ((d.fsalesnet * d.fqty) * (COALESCE(CAST(NULLIF(d.fdisc, '') AS NUMERIC), 0) / 100))) AS totalnota")
            ->where('m.fsodate', '>=', $filters['date_from'])
            ->where('m.fsodate', '<=', $filters['date_to'].' 23:59:59');

        $this->applyBranchVisibilityScope($query, 'm.fbranchcode');
        if (! empty($filters['branch_codes'])) {
            $query->whereIn('m.fbranchcode', $filters['branch_codes']);
        }
        if (! empty($filters['group_produk_in'])) {
            $query->whereIn('p.fgroupcode', $filters['group_produk_in']);
        }
        if ($filters['customer_from'] !== '') {
            $query->where('m.fcustno', '>=', $filters['customer_from']);
        }
        if ($filters['customer_to'] !== '') {
            $query->where('m.fcustno', '<=', $filters['customer_to']);
        }
        if ($filters['salesman_id'] !== '') {
            $query->where('m.fsalesman', $filters['salesman_id']);
        }

        $query->groupBy('m.fcustno', 'd.fprdcode')
            ->orderBy('m.fcustno')
            ->orderBy('fgroupcode')
            ->orderBy('d.fprdcode');

        return [$query->get(), $filters];
    }

    private function filters(Request $request): array
    {
        $branches = array_values(array_filter((array) $request->input('branch_codes', [])));

        return [
            'date_from' => $request->input('date_from', now()->startOfMonth()->toDateString()),
            'date_to' => $request->input('date_to', now()->toDateString()),
            'branch_codes' => $branches,
            'group_produk_in' => array_values(array_filter((array) $request->input('group_produk_in', []))),
            'customer_from' => trim((string) $request->input('customer_from', '')),
            'customer_to' => trim((string) $request->input('customer_to', '')),
            'salesman_id' => trim((string) $request->input('salesman_id', '')),
            'grouping_by' => $request->input('grouping_by') === 'BY_MEREK' ? 'BY_MEREK' : 'BY_GROUP_PRODUK',
            'report_type' => $request->input('report_type') === 'REKAP' ? 'REKAP' : 'DETAIL',
            'branch_label' => $branches ? implode(', ', $branches) : 'Semua Cabang',
            'group_label' => $request->input('group_produk_in') ? implode(', ', (array) $request->input('group_produk_in')) : 'Semua',
            'salesman_label' => $request->input('salesman_id') ?: 'Semua Salesman',
            'customer_label' => trim((string) $request->input('customer_from', '')) || trim((string) $request->input('customer_to', ''))
                ? trim((string) $request->input('customer_from', '')).' s/d '.trim((string) $request->input('customer_to', ''))
                : 'Semua Customer',
        ];
    }
}
