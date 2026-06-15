<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ReportingPelunasanCustomerController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->query('date_from') ?: $request->query('filter_date_from');
        $dateTo = $request->query('date_to') ?: $request->query('filter_date_to');

        if (!$dateFrom) {
            $dateFrom = Carbon::now('Asia/Jakarta')->startOfMonth()->format('Y-m-d');
        }
        if (!$dateTo) {
            $dateTo = Carbon::now('Asia/Jakarta')->format('Y-m-d');
        }

        $hasFilter = $request->has('date_from') || $request->has('date_to') || $request->has('filter_date_from') || $request->has('filter_date_to');

        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get();
        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('reportingpelunasancustomer.index', [
            'hasFilter' => $hasFilter,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'branches' => $branches,
            'isAuthorized' => $isAuthorized,
            'userBranchCode' => $userBranchCode,
        ]);
    }

    public function print(Request $request)
    {
        $filters = $this->filters($request);
        $detailQuery = $this->detailQuery($filters);
        $records = $detailQuery->get();

        // Group records by fkasmtno (voucher number)
        $grouped = $records->groupBy('fkasmtno');
        $voucherNos = $grouped->keys();

        $summaryRows = $voucherNos->isEmpty()
            ? collect()
            : DB::table('trkasmt as m')
                ->join('account as a', 'm.faccountno', '=', 'a.faccount')
                ->whereIn('m.ftrancode', ['RCP', 'BKM'])
                ->whereIn('m.fkasmtno', $voucherNos)
                ->selectRaw('m.faccountno, a.faccname, m.fgiromundur, SUM(m.famountpay) AS famountpay')
                ->groupBy('m.faccountno', 'a.faccname', 'm.fgiromundur')
                ->orderBy('m.faccountno')
                ->orderBy('a.faccname')
                ->orderBy('m.fgiromundur')
                ->get();

        // Compute Grand Totals
        $grandTotalQty = (float) $records->sum('fqty');
        $grandTotalNetNota = (float) $records->sum('fnetnota');
        $grandTotalDiscount = (float) $records->sum('fdiscount');
        $grandTotalBayar = (float) $records->sum('fkasdtvalue');
        $grandTotalSisa = (float) $records->sum('famountremain');

        // Sum values unique to voucher header
        $uniqueHeaders = $records->unique('fkasmtno');
        $grandTotalAdmin = (float) $uniqueHeaders->sum('fadminbank');
        $grandTotalAdjustment = (float) $uniqueHeaders->sum('fadjustment');

        $grandTotal = [
            'qty' => $grandTotalQty,
            'net_nota' => $grandTotalNetNota,
            'discount' => $grandTotalDiscount,
            'bayar' => $grandTotalBayar,
            'sisa' => $grandTotalSisa,
            'admin' => $grandTotalAdmin,
            'adjustment' => $grandTotalAdjustment,
        ];

        // Pagination for printing (e.g. 10 grouped vouchers per page)
        $perPage = 10;
        $totalData = $grouped->count();
        $totalPages = $totalData > 0 ? ceil($totalData / $perPage) : 1;
        $chunkedData = $grouped->chunk($perPage);

        return view('reportingpelunasancustomer.print', [
            'groupedRecords' => $grouped,
            'chunkedData' => $chunkedData,
            'totalPages' => $totalPages,
            'perPage' => $perPage,
            'summaryRows' => $summaryRows,
            'filters' => $filters,
            'grandTotal' => $grandTotal,
            'printDate' => Carbon::now('Asia/Bangkok')->format('d/m/Y H:i'),
            'user_session' => auth('sysuser')->user() ?? auth()->user(),
        ]);
    }

    public function exportExcel(Request $request)
    {
        $filters = $this->filters($request);
        $detailQuery = $this->detailQuery($filters);
        $records = $detailQuery->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Pelunasan Customer');

        $headers = [
            'No. Voucher',
            'Tanggal',
            'Account Header',
            'Customer',
            'Admin Bank',
            'Adjustment',
            'User-id',
            'Type',
            'No. Ref / Faktur',
            'Tanggal Ref',
            'Salesman',
            'Quantity',
            'Net Nota',
            'Discount',
            'Bayar',
            'Sisa Piutang',
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        $sheet->getStyle('A1:P1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);

        $row = 2;
        $gtQty = 0;
        $gtNetNota = 0;
        $gtDiscount = 0;
        $gtBayar = 0;
        $gtSisa = 0;
        $gtAdmin = 0;
        $gtAdjustment = 0;

        $grouped = $records->groupBy('fkasmtno');
        foreach ($grouped as $voucherNo => $voucherRecords) {
            $first = $voucherRecords->first();
            $adminFee = (float) ($first->fadminbank ?? 0);
            $adjustment = (float) ($first->fadjustment ?? 0);

            $gtAdmin += $adminFee;
            $gtAdjustment += $adjustment;

            foreach ($voucherRecords as $record) {
                $qty = (float) ($record->fqty ?? 0);
                $netNota = (float) ($record->fnetnota ?? 0);
                $discount = (float) ($record->fdiscount ?? 0);
                $bayar = (float) ($record->fkasdtvalue ?? 0);
                $sisa = (float) ($record->famountremain ?? 0);

                $gtQty += $qty;
                $gtNetNota += $netNota;
                $gtDiscount += $discount;
                $gtBayar += $bayar;
                $gtSisa += $sisa;

                $sheet->setCellValue('A' . $row, $record->fkasmtno);
                $sheet->setCellValue('B' . $row, $record->fkasmtdate ? Carbon::parse($record->fkasmtdate)->format('d/m/Y') : '');
                $sheet->setCellValue('C' . $row, $record->account);
                $sheet->setCellValue('D' . $row, ($record->fcustomer ? $record->fcustomer . ' - ' : '') . $record->fcustname);
                $sheet->setCellValue('E' . $row, $adminFee);
                $sheet->setCellValue('F' . $row, $adjustment);
                $sheet->setCellValue('G' . $row, $record->fuserid);
                $sheet->setCellValue('H' . $row, $record->freftype);
                $sheet->setCellValue('I' . $row, $record->frefno);
                $sheet->setCellValue('J' . $row, $record->fdate_ref ? Carbon::parse($record->fdate_ref)->format('d/m/Y') : '');
                $sheet->setCellValue('K' . $row, $record->fsalesman ?: '-');
                $sheet->setCellValue('L' . $row, $qty);
                $sheet->setCellValue('M' . $row, $netNota);
                $sheet->setCellValue('N' . $row, $discount);
                $sheet->setCellValue('O' . $row, $bayar);
                $sheet->setCellValue('P' . $row, $sisa);
                $row++;
            }
        }

        // Grand Total row
        $sheet->setCellValue('A' . $row, 'GRAND TOTAL');
        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->setCellValue('E' . $row, $gtAdmin);
        $sheet->setCellValue('F' . $row, $gtAdjustment);
        $sheet->setCellValue('L' . $row, $gtQty);
        $sheet->setCellValue('M' . $row, $gtNetNota);
        $sheet->setCellValue('N' . $row, $gtDiscount);
        $sheet->setCellValue('O' . $row, $gtBayar);
        $sheet->setCellValue('P' . $row, $gtSisa);

        $sheet->getStyle("A{$row}:P{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '333333'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);

        foreach (range('A', 'P') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $numFormat = '#,##0.00';
        $sheet->getStyle('E2:F' . $row)->getNumberFormat()->setFormatCode($numFormat);
        $sheet->getStyle('L2:P' . $row)->getNumberFormat()->setFormatCode($numFormat);

        $sheet->getStyle('A2:P' . ($row - 1))->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);

        $filename = 'Laporan_Pelunasan_Customer_' . now()->format('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function filters(Request $request): array
    {
        return [
            'date_from' => $request->query('date_from') ?: $request->query('filter_date_from') ?: Carbon::now('Asia/Jakarta')->startOfMonth()->format('Y-m-d'),
            'date_to' => $request->query('date_to') ?: $request->query('filter_date_to') ?: Carbon::now('Asia/Jakarta')->format('Y-m-d'),
            'account_no' => trim((string) $request->query('account_no', '')),
            'only_giro_mundur' => $request->boolean('only_giro_mundur'),
            'all_salesman' => $request->has('all_salesman') ? $request->boolean('all_salesman') : true,
            'salesman' => trim((string) $request->query('salesman', '')),
            'customer_from' => trim((string) $request->query('customer_from', '')),
            'customer_to' => trim((string) $request->query('customer_to', '')),
            'branch_codes' => $request->input('branch_codes', []),
        ];
    }

    private function detailQuery(array $filters)
    {
        $invoice = DB::table('trkasmt as m')
            ->leftJoin('trkasdt as d', 'm.fkasmtno', '=', 'd.fkasmtno')
            ->leftJoin('account as t', 't.faccount', '=', 'm.faccountheader')
            ->leftJoin('mscustomer as c', function ($join) {
                $join->whereRaw("CAST(c.fcustomerid AS text) = TRIM(CAST(m.fcustomer AS text))")
                    ->orWhereRaw("TRIM(c.fcustomercode) = TRIM(CAST(m.fcustomer AS text))");
            })
            ->leftJoin('tranmt as n', 'd.frefno', '=', 'n.fsono')
            ->leftJoinSub(
                DB::table('trandt')->selectRaw('fsono, SUM(fqty) AS fqty')->groupBy('fsono'),
                'dt',
                'd.frefno',
                '=',
                'dt.fsono'
            )
            ->whereIn('m.ftrancode', ['RCP', 'BKM'])
            ->whereRaw("TRIM(COALESCE(d.frefno, '')) <> ''")
            ->whereRaw("TRIM(COALESCE(d.freftype, '')) = 'INV'")
            ->selectRaw("m.fkasmtno, d.freftype, m.fkasmtdate, t.faccname AS account, COALESCE(c.fcustomercode, CAST(m.fcustomer AS text)) AS fcustomer, c.fcustomername AS fcustname, d.frefno, d.fdiscpersen, d.fdiscount, d.fkasdtvalue, m.famountpay, m.famountpay_rp, m.fuserid, COALESCE(n.famountremain, 0) AS famountremain, n.fsodate AS fdate_ref, n.famountso, n.fongkosangkut, (n.famountso - n.fongkosangkut) AS fnetnota, n.fsalesman, m.fadminbank, m.fadjustment, dt.fqty");

        $reject = DB::table('trkasmt as m')
            ->leftJoin('trkasdt as d', 'm.fkasmtno', '=', 'd.fkasmtno')
            ->leftJoin('account as t', 't.faccount', '=', 'm.faccountheader')
            ->leftJoin('mscustomer as c', function ($join) {
                $join->whereRaw("CAST(c.fcustomerid AS text) = TRIM(CAST(m.fcustomer AS text))")
                    ->orWhereRaw("TRIM(c.fcustomercode) = TRIM(CAST(m.fcustomer AS text))");
            })
            ->leftJoin('trstockmt as n', 'd.frefno', '=', 'n.fstockmtno')
            ->leftJoinSub(
                DB::table('trstockdt')->selectRaw('fstockmtno, SUM(fqtykecil) AS fqty')->groupBy('fstockmtno'),
                'dt',
                'd.frefno',
                '=',
                'dt.fstockmtno'
            )
            ->whereIn('m.ftrancode', ['RCP', 'BKM'])
            ->whereRaw("TRIM(COALESCE(d.frefno, '')) <> ''")
            ->whereRaw("TRIM(COALESCE(d.freftype, '')) = 'REJ'")
            ->selectRaw("m.fkasmtno, d.freftype, m.fkasmtdate, t.faccname AS account, COALESCE(c.fcustomercode, CAST(m.fcustomer AS text)) AS fcustomer, c.fcustomername AS fcustname, d.frefno, d.fdiscpersen, d.fdiscount, d.fkasdtvalue, m.famountpay, m.famountpay_rp, m.fuserid, COALESCE(n.famountremain, 0) AS famountremain, n.fstockmtdate AS fdate_ref, n.famountmt AS famountso, CAST(0 AS numeric) AS fongkosangkut, n.famountmt AS fnetnota, CAST(n.fsalesman AS text) AS fsalesman, m.fadminbank, m.fadjustment, dt.fqty");

        $this->applyFilters($invoice, $filters, 'n.fsalesman');
        $this->applyFilters($reject, $filters, 'n.fsalesman');

        return DB::query()
            ->fromSub($invoice->unionAll($reject), 'x')
            ->orderBy('x.fkasmtdate')
            ->orderBy('x.fkasmtno')
            ->orderBy('x.frefno');
    }

    private function applyFilters($query, array $filters, string $salesmanColumn): void
    {
        $query->whereDate('m.fkasmtdate', '>=', $filters['date_from'])
            ->whereDate('m.fkasmtdate', '<=', $filters['date_to']);

        $this->applyBranchVisibilityScope($query, 'm.fbranchcode');

        if (!empty($filters['branch_codes'])) {
            $query->whereIn('m.fbranchcode', (array) $filters['branch_codes']);
        }

        if ($filters['account_no'] !== '') {
            $query->where('m.faccountno', $filters['account_no']);
        }

        if ($filters['only_giro_mundur']) {
            $query->where('m.fgiromundur', '1');
        }

        if (! $filters['all_salesman'] && $filters['salesman'] !== '') {
            $query->whereRaw('TRIM(COALESCE('.$salesmanColumn.", '')) = ?", [$filters['salesman']]);
        }

        if ($filters['customer_from'] !== '') {
            $query->whereRaw("TRIM(COALESCE(c.fcustomercode, CAST(m.fcustomer AS text))) >= ?", [$filters['customer_from']]);
        }

        if ($filters['customer_to'] !== '') {
            $query->whereRaw("TRIM(COALESCE(c.fcustomercode, CAST(m.fcustomer AS text))) <= ?", [$filters['customer_to']]);
        }
    }
}
