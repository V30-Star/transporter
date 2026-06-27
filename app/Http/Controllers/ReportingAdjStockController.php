<?php

namespace App\Http\Controllers;

use App\Models\PenerimaanPembelianHeader;
// Sesuaikan dengan path Model Purchase Request Anda
// Sesuaikan dengan path Model Purchase Request Anda
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session; // Digunakan untuk 'session()'
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ReportingAdjStockController extends Controller
{
    protected function getAdjStockQuery(Request $request)
    {
        // Mengambil parameter filter
        $filterDateFrom = $request->query('filter_date_from');
        $filterDateTo = $request->query('filter_date_to');
        $filterWarehouseId = $request->query('filter_warehouse_id'); // berisi kode gudang

        $query = PenerimaanPembelianHeader::query();
        $this->applyBranchVisibilityScope($query, 'trstockmt.fbranchcode');

        $selectedBranches = $request->input('branch_codes', []);
        if (!empty($selectedBranches)) {
            $query->whereIn('trstockmt.fbranchcode', (array) $selectedBranches);
        }

        // Terapkan Filter Tanggal Mulai (fpodate >= filterDateFrom)
        if (! empty($filterDateFrom)) {
            $query->where('fstockmtdate', '>=', $filterDateFrom);
        }

        // Terapkan Filter Tanggal Sampai (fpodate <= filterDateTo [End of Day])
        if (! empty($filterDateTo)) {
            // Menggunakan Carbon::parse($filterDateTo)->endOfDay() untuk memastikan inklusif hingga akhir hari
            $query->where('fstockmtdate', '<=', Carbon::parse($filterDateTo)->endOfDay());
        }

        if (! empty($filterWarehouseId)) {
            $query->where('trstockmt.ffrom', $filterWarehouseId);
        }

        return $query->orderBy('fstockmtdate', 'desc');
    }

    public function index(Request $request)
    {
        // Set default tanggal jika belum ada filter
        $filterDateFrom = $request->query('filter_date_from');
        $filterDateTo = $request->query('filter_date_to');

        // Jika belum ada tanggal, set default
        if (! $filterDateFrom) {
            // Tanggal pertama bulan ini (timezone Jakarta)
            $filterDateFrom = \Carbon\Carbon::now('Asia/Jakarta')->startOfMonth()->format('Y-m-d');
        }

        if (! $filterDateTo) {
            // Tanggal hari ini (timezone Jakarta)
            $filterDateTo = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d');
        }

        // Cek apakah ada filter yang dijalankan
        $hasFilter = $request->has('filter_date_from') ||
          $request->has('filter_date_to') ||
          $request->has('filter_warehouse_id');

        $pohData = collect();

        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();

        $warehouses = DB::table('mswh')
            ->where('fnonactive', '0')
            ->when(! $isAuthorized && $userBranchCode, function ($query) use ($userBranchCode) {
                $query->where('fbranchcode', $userBranchCode);
            })
            ->orderBy('fwhcode')
            ->get(['fwhcode', 'fwhname']);

        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get();

        return view('reportingadjstock.index', [
            'pohData' => $pohData,
            'warehouses' => $warehouses,
            'hasFilter' => $hasFilter,
            'filterDateFrom' => $filterDateFrom,
            'filterDateTo' => $filterDateTo,
            'filterWarehouseId' => $request->query('filter_warehouse_id'),
            'branches' => $branches,
            'isAuthorized' => $isAuthorized,
            'userBranchCode' => $userBranchCode,
        ]);
    }

    /**
     * Metode baru untuk menampilkan data Master-Detail dalam format cetak.
     */
    public function printAdjStock(Request $request)
    {
        $user_session = auth('sysuser')->user();

        $filterWarehouseId = $request->query('filter_warehouse_id');

        $query = DB::table('trstockmt')
            ->select('trstockmt.*', 'account.faccname')
            ->leftJoin('account', 'trstockmt.fto', '=', 'account.faccount')
            ->where('fstockmtcode', 'ADJ');
        $this->applyBranchVisibilityScope($query, 'trstockmt.fbranchcode');

        $selectedBranches = $request->input('branch_codes', []);
        if (!empty($selectedBranches)) {
            $query->whereIn('trstockmt.fbranchcode', (array) $selectedBranches);
        }

        // Filter berdasarkan tanggal jika ada
        if ($request->filled('filter_date_from')) {
            $query->whereDate('fstockmtdate', '>=', $request->filter_date_from);
        }

        if ($request->filled('filter_date_to')) {
            $query->whereDate('fstockmtdate', '<=', $request->filter_date_to);
        }

        if (! empty($filterWarehouseId)) {
            $query->where('trstockmt.ffrom', $filterWarehouseId);
        }

        // Ambil SEMUA data PR Header (tetap pakai get)
        $fakturpembelianData = $query->orderBy('fstockmtdate', 'desc')->get();

        $grandTotalQty = 0;
        $grandTotalQtyReceive = 0;
        $grandTotalHarga = 0;
        $grandTotalPrice = 0;
        $grandTotalPPN = 0;
        $grandTotalPO = 0;

        foreach ($fakturpembelianData as $fakturpembelian) {
            $fakturpembelian->details = DB::table('trstockdt')
                ->where('fstockmtno', $fakturpembelian->fstockmtno)
                ->get();

            $grandTotalHarga += $fakturpembelian->famountremain ?? 0;
            $grandTotalPPN += $fakturpembelian->famountpajak ?? 0;
            $grandTotalPO += $fakturpembelian->famountmt ?? 0;

            $fakturpembelian->famountremain = $fakturpembelian->details->sum('famountmt');

            $warehouse = DB::table('mswh')
                ->where('fwhcode', $fakturpembelian->ffrom)
                ->first();
            $fakturpembelian->warehouse_name = $warehouse->fwhname ?? $fakturpembelian->ffrom;

            foreach ($fakturpembelian->details as $detail) {
                $product = DB::table('msprd')
                    ->where('fprdcode', $detail->fprdcode)
                    ->first();

                $grandTotalQty += $detail->fqty ?? 0;
                $grandTotalQtyReceive += $detail->fqty_receive ?? 0;
                $grandTotalPrice += $detail->fprice ?? 0;

                $detail->product_name = $product->fprdname ?? $detail->fprdcode;
            }
        }

        $grandTotal = [
            'qty' => $grandTotalQty,
            'qty_receive' => $grandTotalQtyReceive,
            'price' => $grandTotalPrice,
            'harga' => $grandTotalHarga,
            'ppn' => $grandTotalPPN,
            'total_po' => $grandTotalPO,
        ];

        $activeWarehouseName = null;
        if (! empty($filterWarehouseId)) {
            $warehouse = DB::table('mswh')
                ->where('fwhcode', $filterWarehouseId)
                ->select('fwhname')
                ->first();
            $activeWarehouseName = $warehouse ? $warehouse->fwhname : 'N/A';
        }

        // Hitung pagination manual
        $perPage = 10;
        $totalData = $fakturpembelianData->count();
        $totalPages = $totalData > 0 ? ceil($totalData / $perPage) : 1;
        $chunkedData = $fakturpembelianData->chunk($perPage);

        return view('reportingadjstock.print', compact(
            'fakturpembelianData',
            'activeWarehouseName',
            'chunkedData',
            'totalPages',
            'perPage',
            'user_session',
            'grandTotal'
        ));
    }

    public function exportExcel(Request $request)
    {
        // Ambil data utama dengan filter 'ADJ'
        $dataToExport = $this->getAdjStockQuery($request)
            ->where('fstockmtcode', 'ADJ')
            ->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Adjustment Stock Report');

        // Header kolom
        $headers = [
            'No. Transaksi',
            'Tanggal',
            'Adj.Type',
            'Account',
            'Nama Account',
            'Total ADJ',
            'Keterangan',
            'User-id',
            'Kode Barang',
            'Nama Barang',
            'Quantity',
            '@ Harga',
            'Total Harga',
        ];

        // Tulis header
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col.'1', $header);
            $col++;
        }

        // Style header
        $sheet->getStyle('A1:M1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
            ],
        ]);

        // Variabel Penampung Grand Total
        $gtTotalAdj = 0;    // Kolom F
        $gtQty = 0;         // Kolom K
        $gtHargaSatuan = 0; // Kolom L (Baru ditambahkan)
        $gtTotalHarga = 0;  // Kolom M

        $row = 2;
        foreach ($dataToExport as $adj) {
            // Akumulasi Total ADJ (Header)
            $gtTotalAdj += $adj->famount ?? 0;

            $adjType = '-';
            if (($adj->ftrancode ?? '') == 'm') {
                $adjType = 'Masuk';
            } elseif (($adj->ftrancode ?? '') == 'k') {
                $adjType = 'Keluar';
            }

            $details = DB::table('trstockdt')
                ->where('fstockmtno', $adj->fstockmtno)
                ->get();

            if ($details->isNotEmpty()) {
                foreach ($details as $detail) {
                    $product = DB::table('msprd')
                        ->where('fprdcode', $detail->fprdcode)
                        ->first();
                    $product_name = $product->fprdname ?? $detail->fprdcode;

                    // Akumulasi Detail
                    $gtQty += $detail->fqty ?? 0;
                    $gtHargaSatuan += $detail->fprice ?? 0; // Tambah hitungan harga satuan
                    $gtTotalHarga += $detail->ftotprice ?? 0;

                    $sheet->setCellValue('A'.$row, $adj->fstockmtno);
                    $sheet->setCellValue('B'.$row, \Carbon\Carbon::parse($adj->fstockmtdate)->format('d/m/Y'));
                    $sheet->setCellValue('C'.$row, $adjType);
                    $sheet->setCellValue('D'.$row, $adj->frefno);
                    $sheet->setCellValue('E'.$row, $adj->faccname);
                    $sheet->setCellValue('F'.$row, $adj->famount ?? 0);
                    $sheet->setCellValue('G'.$row, $adj->fket);
                    $sheet->setCellValue('H'.$row, $adj->fusercreate);
                    $sheet->setCellValue('I'.$row, $detail->fprdcode);
                    $sheet->setCellValue('J'.$row, $product_name);
                    $sheet->setCellValue('K'.$row, $detail->fqty ?? 0);
                    $sheet->setCellValue('L'.$row, $detail->fprice ?? 0);
                    $sheet->setCellValue('M'.$row, $detail->ftotprice ?? 0);
                    $row++;
                }
            } else {
                $sheet->setCellValue('A'.$row, $adj->fstockmtno);
                $sheet->setCellValue('B'.$row, \Carbon\Carbon::parse($adj->fstockmtdate)->format('d/m/Y'));
                $sheet->setCellValue('C'.$row, $adjType);
                $sheet->setCellValue('D'.$row, $adj->frefno);
                $sheet->setCellValue('E'.$row, $adj->faccname);
                $sheet->setCellValue('F'.$row, $adj->famount ?? 0);
                $sheet->setCellValue('G'.$row, $adj->fket);
                $sheet->setCellValue('H'.$row, $adj->fusercreate);
                $row++;
            }
        }

        // --- PENULISAN GRAND TOTAL ---
        $sheet->setCellValue('A'.$row, 'GRAND TOTAL');
        $sheet->mergeCells("A$row:E$row");

        $sheet->setCellValue('F'.$row, $gtTotalAdj);
        $sheet->setCellValue('K'.$row, $gtQty);
        $sheet->setCellValue('L'.$row, $gtHargaSatuan); // Tampilkan total harga satuan
        $sheet->setCellValue('M'.$row, $gtTotalHarga);

        // Style Grand Total
        $sheet->getStyle("A$row:M$row")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '333333'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
            ],
        ]);

        // Formatting & Auto-size
        foreach (range('A', 'M') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $numFormat = '#,##0.00';
        $sheet->getStyle('F2:F'.$row)->getNumberFormat()->setFormatCode($numFormat);
        $sheet->getStyle('K2:M'.$row)->getNumberFormat()->setFormatCode($numFormat);

        // Border untuk semua data sebelum baris Grand Total
        $sheet->getStyle('A2:M'.($row - 1))->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
            ],
        ]);

        $filename = 'Adj_Stock_Report_'.now()->format('Ymd_His').'.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
