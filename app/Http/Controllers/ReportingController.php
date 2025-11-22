<?php

namespace App\Http\Controllers;

use App\Models\Wilayah;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\Tr_poh; // Sesuaikan dengan path Model Purchase Request Anda
use App\Models\Groupcustomer; // Sesuaikan dengan path Model Purchase Request Anda
use App\Models\Supplier;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session; // Digunakan untuk 'session()'
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReportingController extends Controller
{
  protected function getPohQuery(Request $request)
  {
    // Mengambil parameter filter
    $filterDateFrom = $request->query('filter_date_from');
    $filterDateTo  = $request->query('filter_date_to');
    $filterSupplierId = $request->query('filter_supplier_id'); // Parameter Supplier baru

    $query = Tr_poh::query();

    // Terapkan Filter Tanggal Mulai (fpodate >= filterDateFrom)
    if (!empty($filterDateFrom)) {
      $query->where('fpodate', '>=', $filterDateFrom);
    }

    // Terapkan Filter Tanggal Sampai (fpodate <= filterDateTo [End of Day])
    if (!empty($filterDateTo)) {
      // Menggunakan Carbon::parse($filterDateTo)->endOfDay() untuk memastikan inklusif hingga akhir hari
      $query->where('fpodate', '<=', Carbon::parse($filterDateTo)->endOfDay());
    }

    // --- FILTER SUPPLIER BARU ---
    if (!empty($filterSupplierId)) {
      $query->where('fsupplier', $filterSupplierId);
    }

    return $query->orderBy('fpodate', 'desc');
  }

  public function index(Request $request)
  {
    // Set default tanggal jika belum ada filter
    $filterDateFrom = $request->query('filter_date_from');
    $filterDateTo = $request->query('filter_date_to');

    // Jika belum ada tanggal, set default
    if (!$filterDateFrom) {
      // Tanggal pertama bulan ini (timezone Jakarta)
      $filterDateFrom = \Carbon\Carbon::now('Asia/Jakarta')->startOfMonth()->format('Y-m-d');
    }

    if (!$filterDateTo) {
      // Tanggal hari ini (timezone Jakarta)
      $filterDateTo = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d');
    }

    // Cek apakah ada filter yang dijalankan
    $hasFilter = $request->has('filter_date_from') ||
      $request->has('filter_date_to') ||
      $request->has('filter_supplier_id');

    // Hanya ambil data jika ada filter
    $pohData = $hasFilter
      ? $this->getPohQuery($request)
      ->with('supplier:fsupplierid,fsuppliername')
      ->get([
        'fpohdid',
        'fpono',
        'fpodate',
        'fsupplier',
        'famountpo',
        'fcurrency',
        'fclose',
        'fapproval'
      ])
      : collect();

    // Ambil SEMUA Supplier untuk dropdown filter
    $suppliers = Supplier::orderBy('fsuppliername', 'asc')
      ->get(['fsupplierid', 'fsuppliername']);

    return view('reporting.index', [
      'pohData' => $pohData,
      'suppliers' => $suppliers,
      'hasFilter' => $hasFilter,
      'filterDateFrom' => $filterDateFrom,
      'filterDateTo' => $filterDateTo,
      'filterSupplierId' => $request->query('filter_supplier_id'),
    ]);
  }

  /**
   * Metode baru untuk menampilkan data Master-Detail dalam format cetak.
   */
  public function printPoh(Request $request)
  {
    $user_session = auth('sysuser')->user();

    $filterSupplierId = $request->query('fsupplier');

    $query = DB::table('tr_poh')->select('tr_poh.*');

    // Filter berdasarkan tanggal jika ada
    if ($request->filled('filter_date_from')) {
      $query->whereDate('fpodate', '>=', $request->filter_date_from);
    }

    if ($request->filled('filter_date_to')) {
      $query->whereDate('fpodate', '<=', $request->filter_date_to);
    }

    if (!empty($filterSupplierId)) {
      $query->where('fsupplier', $filterSupplierId);
    }

    // Ambil SEMUA data PO Header (tetap pakai get)
    $pohData = $query->orderBy('fpodate', 'desc')->get();

    $grandTotalQty = 0;
    $grandTotalQtyReceive = 0;
    $grandTotalHarga = 0;
    $grandTotalPrice = 0;
    $grandTotalPPN = 0;
    $grandTotalPO = 0;

    foreach ($pohData as $poh) {
      $poh->details = DB::table('tr_pod')
        ->where('fpono', $poh->fpohdid)
        ->orderBy('fnou')
        ->get();
      $grandTotalHarga += $poh->total_harga ?? 0;
      $grandTotalPPN += $poh->fppn ?? 0;
      $grandTotalPO += $poh->famountpo ?? 0;

      $poh->total_harga = $poh->details->sum('famount');

      $supplier = DB::table('mssupplier')
        ->where('fsupplierid', $poh->fsupplier)
        ->first();
      $poh->supplier_name = $supplier->fsuppliername ?? $poh->fsupplier;

      foreach ($poh->details as $detail) {
        $product = DB::table('msprd')
          ->where('fprdcode', $detail->fprdcode)
          ->first();

        $grandTotalQty += $detail->fqty ?? 0;
        $grandTotalQtyReceive += $detail->fqty_receive ?? 0;
        $grandTotalPrice += $detail->fprice ?? 0;

        $detail->product_name = $product->fitemname ?? $detail->fprdcode;
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

    $activeSupplierName = null;
    if (!empty($filterSupplierId)) {
      $supplier = Supplier::where('fsupplierid', $filterSupplierId)
        ->select('fsuppliername')
        ->first();
      $activeSupplierName = $supplier ? $supplier->fsuppliername : 'N/A';
    }

    // Hitung pagination manual
    $perPage = 10;
    $totalData = $pohData->count();
    $totalPages = $totalData > 0 ? ceil($totalData / $perPage) : 1;
    $chunkedData = $pohData->chunk($perPage);

    return view('reporting.print', compact(
      'pohData',
      'activeSupplierName',
      'chunkedData',
      'totalPages',
      'perPage',
      'user_session',
      'grandTotal'
    ));
  }

  public function exportExcel(Request $request)
  {
    $dataToExport = $this->getPohQuery($request)
      ->with('details')
      ->get();

    // Buat Spreadsheet baru
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('PO Report');

    // Header kolom
    $headers = [
      'ID PO',
      'NOMOR PO',
      'TANGGAL PO',
      'SUPPLIER ID',
      'MATA UANG',
      'TOTAL PO',
      'STATUS CLOSE',
      'STATUS APPROVAL',
      'NO. URUT ITEM',
      'KODE PRODUK',
      'DESKRIPSI ITEM',
      'QTY',
      'SATUAN',
      'HARGA SATUAN',
      'JUMLAH ITEM',
    ];

    // Tulis header di baris 1
    $col = 'A';
    foreach ($headers as $header) {
      $sheet->setCellValue($col . '1', $header);
      $col++;
    }

    // Style header
    $headerRange = 'A1:O1';
    $sheet->getStyle($headerRange)->applyFromArray([
      'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
      ],
      'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4472C4'],
      ],
      'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
      ],
      'borders' => [
        'allBorders' => [
          'borderStyle' => Border::BORDER_THIN,
        ],
      ],
    ]);

    // Tulis data mulai baris 2
    $row = 2;
    foreach ($dataToExport as $poh) {
      $pohBaseData = [
        $poh->fpohdid,
        $poh->fpono,
        Carbon::parse($poh->fpodate)->format('d-m-Y'),
        $poh->fsupplier,
        $poh->fcurrency,
        $poh->famountpo,
        $poh->fclose === '1' ? 'Closed' : 'Open',
        $poh->fapproval ?? '-',
      ];

      if ($poh->details->isNotEmpty()) {
        foreach ($poh->details as $pod) {
          $col = 'A';

          // Tulis data header PO
          foreach ($pohBaseData as $value) {
            $sheet->setCellValue($col . $row, $value);
            $col++;
          }

          // Tulis data detail
          $sheet->setCellValue('I' . $row, $pod->fnou);
          $sheet->setCellValue('J' . $row, $pod->fprdcode);
          $sheet->setCellValue('K' . $row, $pod->fdesc ?? '-');
          $sheet->setCellValue('L' . $row, $pod->fqty);
          $sheet->setCellValue('M' . $row, $pod->fsatuan);
          $sheet->setCellValue('N' . $row, $pod->fprice);
          $sheet->setCellValue('O' . $row, $pod->famount);

          $row++;
        }
      } else {
        $col = 'A';
        foreach ($pohBaseData as $value) {
          $sheet->setCellValue($col . $row, $value);
          $col++;
        }
        $row++;
      }
    }

    // Auto-size kolom
    foreach (range('A', 'O') as $column) {
      $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    // Style untuk data (border)
    $lastDataRow = $row - 1;
    if ($lastDataRow >= 2) {
      $sheet->getStyle('A2:O' . $lastDataRow)->applyFromArray([
        'borders' => [
          'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
          ],
        ],
      ]);

      // Format angka untuk kolom numerik
      $sheet->getStyle('F2:F' . $lastDataRow)->getNumberFormat()->setFormatCode('#,##0.00');
      $sheet->getStyle('L2:L' . $lastDataRow)->getNumberFormat()->setFormatCode('#,##0.00');
      $sheet->getStyle('N2:O' . $lastDataRow)->getNumberFormat()->setFormatCode('#,##0.00');
    }

    // ===============================
    // GRAND TOTAL
    // ===============================
    $row++; // Baris kosong
    $grandTotalRow = $row;

    // Hitung Grand Total
    $grandTotalPO = 0;
    $grandTotalQty = 0;
    $grandTotalPrice = 0;
    $grandTotalAmount = 0;

    foreach ($dataToExport as $poh) {
      $grandTotalPO += $poh->famountpo ?? 0;
      foreach ($poh->details as $pod) {
        $grandTotalQty += $pod->fqty ?? 0;
        $grandTotalPrice += $pod->fprice ?? 0;
        $grandTotalAmount += $pod->famount ?? 0;
      }
    }

    // Tulis Grand Total
    $sheet->setCellValue('A' . $grandTotalRow, 'GRAND TOTAL');
    $sheet->mergeCells('A' . $grandTotalRow . ':E' . $grandTotalRow);
    $sheet->setCellValue('F' . $grandTotalRow, $grandTotalPO);
    $sheet->setCellValue('L' . $grandTotalRow, $grandTotalQty);
    $sheet->setCellValue('N' . $grandTotalRow, $grandTotalPrice);
    $sheet->setCellValue('O' . $grandTotalRow, $grandTotalAmount);

    // Style Grand Total
    $sheet->getStyle('A' . $grandTotalRow . ':O' . $grandTotalRow)->applyFromArray([
      'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
      ],
      'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '333333'],
      ],
      'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
      ],
      'borders' => [
        'allBorders' => [
          'borderStyle' => Border::BORDER_THIN,
        ],
      ],
    ]);

    // Alignment kiri untuk label GRAND TOTAL
    $sheet->getStyle('A' . $grandTotalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

    // Format angka untuk grand total
    $sheet->getStyle('F' . $grandTotalRow)->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet->getStyle('L' . $grandTotalRow)->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet->getStyle('N' . $grandTotalRow . ':O' . $grandTotalRow)->getNumberFormat()->setFormatCode('#,##0.00');

    // Generate filename
    $filename = 'PO_Report_' . now()->format('Ymd_His') . '.xlsx';

    // Set headers untuk download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    // Output file
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
  }
}
