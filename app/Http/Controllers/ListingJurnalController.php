<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

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
        $branches = DB::table('mscabang')
            ->orderBy('fcabangkode')
            ->get(['fcabangkode', 'fcabangname'])
            ->map(function ($item) {
                $item->fcabangkode = trim((string) $item->fcabangkode);
                $item->fcabangname = trim((string) $item->fcabangname);
                return $item;
            });
        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        return view('listingjurnal.index', [
            'typeOptions' => $typeOptions,
            'branches' => $branches,
            'isAuthorized' => $isAuthorized,
            'userBranchCode' => $userBranchCode,
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
        $branchCodes = $request->input('branch_codes', []);
        $branchCodes = array_values(array_filter(array_map('trim', (array) $branchCodes)));
        $sortBy = $request->input('sort_by', 'terlama');

        $results = $this->buildQuery($dateFrom, $dateTo, $journalTypes, $branchCodes, $sortBy)->get();
        $groupedData = $results->groupBy('fjurnalno');

        return view('listingjurnal.print', [
            'groupedData' => $groupedData,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'selectedTypes' => $journalTypes,
            'selectedBranches' => $branchCodes,
            'user_session' => auth()->user(),
        ]);
    }

    public function exportExcel(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $journalTypes = $request->input('journal_types', []);
        $journalTypes = array_values(array_filter(array_map('trim', (array) $journalTypes)));
        $branchCodes = $request->input('branch_codes', []);
        $branchCodes = array_values(array_filter(array_map('trim', (array) $branchCodes)));
        $sortBy = $request->input('sort_by', 'terlama');

        $results = $this->buildQuery($dateFrom, $dateTo, $journalTypes, $branchCodes, $sortBy)->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Listing Jurnal Transaksi');

        $headers = [
            'No. Jurnal',
            'Tanggal',
            'Type',
            'Note / Keterangan',
            'User-id',
            'Balance',
            'Balance Rp',
            'Line',
            'Account',
            'Account Name',
            'Ref No',
            'Sub Account',
            'D/K',
            'Rate',
            'Debet',
            'Kredit',
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        $sheet->getStyle('A1:P1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'C00000'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
            ],
        ]);

        $row = 2;
        $grandTotalDebet = 0;
        $grandTotalKredit = 0;

        $groupedData = $results->groupBy('fjurnalno');
        foreach ($groupedData as $jurnalNo => $lines) {
            $firstLine = $lines->first();
            $jurnalDateFormatted = !empty($firstLine->fjurnaldate) ? \Carbon\Carbon::parse($firstLine->fjurnaldate)->format('d/m/Y') : '';
            $balance = (float) $firstLine->fbalance;
            $balanceRp = (float) $firstLine->fbalance_rp;

            foreach ($lines as $dt) {
                $debet = $dt->debet !== null ? (float) $dt->debet : 0;
                $kredit = $dt->kredit !== null ? (float) $dt->kredit : 0;

                $grandTotalDebet += $debet;
                $grandTotalKredit += $kredit;

                $sheet->setCellValue('A' . $row, $jurnalNo);
                $sheet->setCellValue('B' . $row, $jurnalDateFormatted);
                $sheet->setCellValue('C' . $row, $firstLine->fjurnaltype);
                $sheet->setCellValue('D' . $row, $firstLine->fjurnalnote);
                $sheet->setCellValue('E' . $row, $firstLine->fuserid);
                $sheet->setCellValue('F' . $row, $balance);
                $sheet->setCellValue('G' . $row, $balanceRp);
                $sheet->setCellValue('H' . $row, $dt->flineno);
                $sheet->setCellValue('I' . $row, $dt->faccount);
                $sheet->setCellValue('J' . $row, $dt->faccname);
                $sheet->setCellValue('K' . $row, $dt->frefno);
                $sheet->setCellValue('L' . $row, $dt->fsubaccount);
                $sheet->setCellValue('M' . $row, $dt->fdk);
                $sheet->setCellValue('N' . $row, (float) $dt->frate);
                $sheet->setCellValue('O' . $row, $debet > 0 ? $debet : '');
                $sheet->setCellValue('P' . $row, $kredit > 0 ? $kredit : '');

                $row++;
            }
        }

        // Grand Total Row
        $sheet->setCellValue('A' . $row, 'GRAND TOTAL');
        $sheet->mergeCells("A{$row}:N{$row}");
        $sheet->setCellValue('O' . $row, $grandTotalDebet);
        $sheet->setCellValue('P' . $row, $grandTotalKredit);

        $sheet->getStyle("A{$row}:P{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '333333'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
            ],
        ]);

        foreach (range('A', 'P') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $numFormat = '#,##0.00';
        $sheet->getStyle('F2:G' . $row)->getNumberFormat()->setFormatCode($numFormat);
        $sheet->getStyle('N2:P' . $row)->getNumberFormat()->setFormatCode($numFormat);

        $sheet->getStyle('A2:P' . ($row - 1))->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
            ],
        ]);

        $filename = 'Listing_Jurnal_Transaksi_' . now()->format('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function buildQuery(string $dateFrom, string $dateTo, array $journalTypes, array $branchCodes, string $sortBy)
    {
        // 1. Tambah NULL atau string kosong sebagai placeholder fnote di Jurnal Umum
        $jurnalUmum = DB::table('jurnalmt as m')
            ->leftJoin('jurnaldt as d', 'm.fjurnalno', '=', 'd.fjurnalno')
            ->leftJoin('account as acc', 'd.faccount', '=', 'acc.faccount')
            ->selectRaw("m.fjurnalno, m.fjurnaldate, m.fjurnaltype, m.fjurnalnote, m.fbalance, m.fbalance_rp, m.fdatetime, m.fuserid, d.flineno, d.faccount, acc.faccname, d.frefno, d.fsubaccount, d.fdk, d.frate, d.famount, d.famount_rp, d.faccountnote, NULL::integer AS fkasdtid, m.fbranchcode, '' AS fnote"); // <--- Tambah ini

        // 2. Tambah placeholder fnote di Kas Header
        $kasHeader = DB::table('trkasmt as m')
            ->leftJoin('account as acc', 'm.faccountheader', '=', 'acc.faccount')
            ->selectRaw("m.fkasmtno AS fjurnalno, m.fkasmtdate AS fjurnaldate, m.ftrancode AS fjurnaltype, m.fket AS fjurnalnote, m.famountpay AS fbalance, m.famountpay_rp AS fbalance_rp, m.fdatetime, m.fuserid, 1 AS flineno, m.faccountheader AS faccount, acc.faccname, '' AS frefno, '' AS fsubaccount, m.fdkheader AS fdk, m.frate, ABS(m.famountpay) AS famount, ABS(m.famountpay_rp) AS famount_rp, m.fket AS faccountnote, NULL::integer AS fkasdtid, m.fbranchcode, '' AS fnote"); // <--- Tambah ini

        // 3. Ambil fnote asli dari trkasdt (alias d) di Kas Detail
        $kasDetail = DB::table('trkasmt as m')
            ->leftJoin('trkasdt as d', 'm.fkasmtno', '=', 'd.fkasmtno')
            ->leftJoin('account as acc', 'd.faccount', '=', 'acc.faccount')
            ->selectRaw("m.fkasmtno AS fjurnalno, m.fkasmtdate AS fjurnaldate, m.ftrancode AS fjurnaltype, d.fnote AS fjurnalnote, d.fjurnal AS fbalance, d.fjurnal_rp AS fbalance_rp, m.fdatetime, m.fuserid, COALESCE(d.fnou, 1) + 1 AS flineno, d.faccount, acc.faccname, d.frefno, d.fsubaccount, d.fdk, m.frate, d.fjurnal AS famount, d.fjurnal_rp AS famount_rp, d.fnote AS faccountnote, d.fkasdtid, m.fbranchcode, d.fnote AS fnote"); // <--- Tambah ini

        $union = $jurnalUmum->unionAll($kasHeader)->unionAll($kasDetail);

        // 4. Panggil a.fnote di query select pembungkusnya
        $query = DB::query()
            ->fromSub($union, 'a')
            ->selectRaw("a.fjurnalno, a.fjurnaldate, a.fjurnaltype, a.fjurnalnote, a.fbalance, a.fbalance_rp, a.fdatetime, a.fuserid, a.flineno, a.faccount, a.faccname, a.frefno, a.fsubaccount, a.fdk, a.frate, a.famount, a.famount_rp, a.faccountnote, a.fbranchcode, a.fnote, CASE WHEN a.fdk = 'D' THEN a.famount END AS debet, CASE WHEN a.fdk = 'K' THEN a.famount END AS kredit") // <--- Tambah a.fnote di sini
            ->whereDate('a.fjurnaldate', '>=', $dateFrom)
            ->whereDate('a.fjurnaldate', '<=', $dateTo);

        $this->applyBranchVisibilityScope($query, 'a.fbranchcode');

        if ($journalTypes !== []) {
            $query->whereIn('a.fjurnaltype', $journalTypes);
        }

        if ($branchCodes !== []) {
            $query->whereIn('a.fbranchcode', $branchCodes);
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
