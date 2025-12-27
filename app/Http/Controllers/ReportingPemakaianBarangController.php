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
use App\Models\PenerimaanPembelianHeader;

class ReportingPemakaianBarangController extends Controller
{
  protected function getPemakaianBarangQuery(Request $request)
  {
    // Mengambil parameter filter
    $filterDateFrom = $request->query('filter_date_from');
    $filterDateTo  = $request->query('filter_date_to');
    $filterSupplierId = $request->query('filter_supplier_id'); // Parameter Supplier baru

    $query = PenerimaanPembelianHeader::query();

    // Terapkan Filter Tanggal Mulai (fpodate >= filterDateFrom)
    if (!empty($filterDateFrom)) {
      $query->where('fstockmtdate', '>=', $filterDateFrom);
    }

    // Terapkan Filter Tanggal Sampai (fpodate <= filterDateTo [End of Day])
    if (!empty($filterDateTo)) {
      // Menggunakan Carbon::parse($filterDateTo)->endOfDay() untuk memastikan inklusif hingga akhir hari
      $query->where('fstockmtdate', '<=', Carbon::parse($filterDateTo)->endOfDay());
    }

    // --- FILTER SUPPLIER BARU ---
    if (!empty($filterSupplierId)) {
      $query->where('fsupplier', $filterSupplierId);
    }

    return $query->orderBy('fstockmtdate', 'desc');
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
    $prdData = $hasFilter
      ? $this->getPemakaianBarangQuery($request)
      ->with('supplier:fsupplierid,fsuppliername')
      ->get([
        'fpohdid',
        'fpono',
        'fstockmtdate',
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

    return view('reportingpemakaianbarang.index', [
      'prdData' => $prdData,
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
  public function printPemakaianBarang(Request $request)
  {
    $user_session = auth('sysuser')->user();

    $filterSupplierId = $request->query('fsupplier');

    $query = DB::table('trstockmt')
      ->select('trstockmt.*', 'account.faccname')
      ->leftJoin('account', 'trstockmt.frefno', '=', 'account.faccid')
      ->where('fstockmtcode', 'PBR');

    // Filter berdasarkan tanggal jika ada
    if ($request->filled('filter_date_from')) {
      $query->whereDate('fstockmtdate', '>=', $request->filter_date_from);
    }

    if ($request->filled('filter_date_to')) {
      $query->whereDate('fstockmtdate', '<=', $request->filter_date_to);
    }

    if (!empty($filterSupplierId)) {
      $query->where('fsupplier', $filterSupplierId);
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
      // 1. Ambil detail dengan Join ke tabel Account
      $fakturpembelian->details = DB::table('trstockdt')
        ->select('trstockdt.*', 'account.faccname', 'mssubaccount.fsubaccountname')
        ->leftJoin('account', 'trstockdt.frefdtno', '=', 'account.faccid')
        ->leftJoin('mssubaccount', 'trstockdt.frefso', '=', 'mssubaccount.fsubaccountid')
        ->where('fstockmtno', $fakturpembelian->fstockmtno)
        ->get();

      // Kalkulasi Grand Total (Header)
      $grandTotalHarga += $fakturpembelian->famountremain ?? 0;
      $grandTotalPPN += $fakturpembelian->famountpajak ?? 0;
      $grandTotalPO += $fakturpembelian->famountmt ?? 0;

      $fakturpembelian->famountremain = $fakturpembelian->details->sum('famountmt');

      // 2. Ambil data Supplier
      $supplier = DB::table('mssupplier')
        ->where('fsupplierid', $fakturpembelian->fsupplier)
        ->first();
      $fakturpembelian->supplier_name = $supplier->fsuppliername ?? $fakturpembelian->fsupplier;

      foreach ($fakturpembelian->details as $detail) {
        // 3. Ambil data Product
        $product = DB::table('msprd')
          ->where('fprdid', $detail->fprdcode)
          ->first();

        $grandTotalQty += $detail->fqty ?? 0;
        $grandTotalQtyReceive += $detail->fqty_receive ?? 0;
        $grandTotalPrice += $detail->fprice ?? 0;

        $detail->product_name = $product->fprdname ?? $detail->fprdcode;

        // Sekarang kamu bisa mengakses $detail->account_name hasil dari join di atas
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
    $totalData = $fakturpembelianData->count();
    $totalPages = $totalData > 0 ? ceil($totalData / $perPage) : 1;
    $chunkedData = $fakturpembelianData->chunk($perPage);

    return view('reportingpemakaianbarang.print', compact(
      'fakturpembelianData',
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
    $dataToExport = $this->getPemakaianBarangQuery($request)
      ->with('details')
      ->get();

    // Buat Spreadsheet baru
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('PR Report');

    // Header kolom
    $headers = [
      'ID PR',
      'NOMOR PR',
      'TANGGAL PR',
      'SUPPLIER ID',
      'MATA UANG',
      'TOTAL PR',
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
    foreach ($dataToExport as $fakturpembelian) {
      $pohBaseData = [
        $fakturpembelian->fpohdid,
        $fakturpembelian->fpono,
        Carbon::parse($fakturpembelian->fpodate)->format('d-m-Y'),
        $fakturpembelian->fsupplier,
        $fakturpembelian->fcurrency,
        $fakturpembelian->famountpo,
        $fakturpembelian->fclose === '1' ? 'Closed' : 'Open',
        $fakturpembelian->fapproval ?? '-',
      ];

      if ($fakturpembelian->details->isNotEmpty()) {
        foreach ($fakturpembelian->details as $pod) {
          $col = 'A';

          // Tulis data header PR
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

    foreach ($dataToExport as $fakturpembelian) {
      $grandTotalPO += $fakturpembelian->famountpo ?? 0;
      foreach ($fakturpembelian->details as $pod) {
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
