<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class ReportingRekapPenjualanSalesCustomerController extends Controller
{
    public function index()
    {
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get();
        $customers = DB::table('mscustomer')->orderBy('fcustomercode')->get(['fcustomercode', 'fcustomername']);
        $salesmans = DB::table('mssalesman')->orderBy('fsalesmancode')->get(['fsalesmancode', 'fsalesmanname']);
        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('reportingrekappenjualansalescustomer.index', compact('branches', 'customers', 'salesmans', 'isAuthorized', 'userBranchCode'));
    }

    public function print(Request $request)
    {
        [$rows, $filters] = $this->rowsAndFilters($request);

        return view('reportingrekappenjualansalescustomer.print', [
            'rows' => $rows,
            'filters' => $filters,
            'user_session' => auth('sysuser')->user() ?? auth()->user(),
        ]);
    }

    public function exportExcel(Request $request)
    {
        [$rows, $filters] = $this->rowsAndFilters($request);
        $filename = 'Rekap_Penjualan_Sales_By_Customer_'.date('YmdHis').'.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $writer = new Writer;
        $writer->openToFile($tempFile);

        $bold = new Style(fontBold: true);
        $header = new Style(fontBold: true, backgroundColor: 'D9EAF7');
        $grand = new Style(fontBold: true, backgroundColor: '111827', fontColor: 'FFFFFF');
        $makeRow = fn (array $values, ?Style $style = null) => new Row(array_map(fn ($value) => $style ? Cell::fromValue($value, $style) : Cell::fromValue($value), $values));

        $writer->addRow($makeRow(['PT. UTALIYA'], $bold));
        $writer->addRow($makeRow(['Rekap Penjualan Sales By Customer'], $bold));
        $writer->addRow($makeRow(['Periode', $filters['date_from'].' s/d '.$filters['date_to']]));
        $writer->addRow($makeRow(['Cabang', $filters['branch_label']]));
        $writer->addRow($makeRow(['Salesman', $filters['salesman_label']]));
        $writer->addRow($makeRow(['Customer', $filters['customer_label']]));
        $writer->addRow($makeRow([]));
        $writer->addRow($makeRow(['Salesman', 'Nama Customer', 'Jumlah'], $header));

        foreach ($rows as $row) {
            $writer->addRow($makeRow([
                $row->fsalesman.' - '.$row->salesman_name,
                $row->customer,
                (float) $row->totalnota,
            ]));
        }
        $writer->addRow($makeRow(['GRAND TOTAL', '', (float) $rows->sum('totalnota')], $grand));
        $writer->close();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function rowsAndFilters(Request $request): array
    {
        $filters = $this->filters($request);

        $query = DB::table('tranmt as m')
            ->join('mscustomer as c', 'm.fcustno', '=', 'c.fcustomercode')
            ->leftJoin('mssalesman as s', DB::raw('m.fsalesman::text'), '=', 's.fsalesmancode')
            ->selectRaw("m.fsalesman::text AS fsalesman, MIN(s.fsalesmanname) AS salesman_name")
            ->selectRaw("m.fcustno, CAST(TRIM(MIN(c.fcustomername)) || '   (' || TRIM(m.fcustno) || ')' AS CHAR(90)) AS customer")
            ->selectRaw('SUM(m.ftotalsalesnet - (m.ftotalsalesnet * (COALESCE(m.fdiscpersen, 0) / 100))) AS totalnota')
            ->where('m.fsodate', '>=', $filters['date_from'])
            ->where('m.fsodate', '<=', $filters['date_to'].' 23:59:59');

        $this->applyBranchVisibilityScope($query, 'm.fbranchcode');
        if ($filters['branch_codes']) {
            $query->whereIn('m.fbranchcode', $filters['branch_codes']);
        }
        if ($filters['salesman_id'] !== '') {
            $query->whereRaw('CAST(m.fsalesman AS text) = ?', [$filters['salesman_id']]);
        }
        if ($filters['customer_from'] !== '') {
            $query->where('m.fcustno', '>=', $filters['customer_from']);
        }
        if ($filters['customer_to'] !== '') {
            $query->where('m.fcustno', '<=', $filters['customer_to']);
        }

        return [$query->groupBy('m.fsalesman', 'm.fcustno')->orderBy('m.fsalesman')->orderBy('m.fcustno')->get(), $filters];
    }

    private function filters(Request $request): array
    {
        $branches = array_values(array_filter((array) $request->input('branch_codes', [])));
        $customerFrom = trim((string) $request->input('customer_from', ''));
        $customerTo = trim((string) $request->input('customer_to', ''));

        return [
            'date_from' => $request->input('date_from', now()->startOfMonth()->toDateString()),
            'date_to' => $request->input('date_to', now()->toDateString()),
            'branch_codes' => $branches,
            'customer_from' => $customerFrom,
            'customer_to' => $customerTo,
            'salesman_id' => trim((string) $request->input('salesman_id', '')),
            'branch_label' => $branches ? implode(', ', $branches) : 'Semua Cabang',
            'salesman_label' => $request->input('salesman_id') ?: 'Semua Salesman',
            'customer_label' => $customerFrom || $customerTo ? $customerFrom.' s/d '.$customerTo : 'Semua Customer',
        ];
    }
}
