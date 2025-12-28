<?php

namespace App\Http\Controllers;

use App\Models\Wilayah;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\Tr_prh; // Sesuaikan dengan path Model Purchase Request Anda
use App\Models\Groupcustomer; // Sesuaikan dengan path Model Purchase Request Anda
use App\Models\PenerimaanPembelianHeader;
use App\Models\Supplier;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session; // Digunakan untuk 'session()'
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReportingPenerimaanBarangController extends Controller
{
  protected function getPenerimaanBarangQuery(Request $request)
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
      ? $this->getPenerimaanBarangQuery($request)
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

    return view('reportingpenerimaanbarang.index', [
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
  public function printPenerimaanBarang(Request $request)
  {
    $user_session = auth('sysuser')->user();

    $filterSupplierId = $request->query('fsupplier');

    $query = DB::table('trstockmt')
      ->select('trstockmt.*')
      ->where('fstockmtcode', 'RCV');

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
    $prhData = $query->orderBy('fstockmtdate', 'desc')->get();

    $grandTotalQty = 0;
    $grandTotalQtyReceive = 0;
    $grandTotalHarga = 0;
    $grandTotalPrice = 0;
    $grandTotalPPN = 0;
    $grandTotalPO = 0;

    foreach ($prhData as $penerimaanbarang) {
      $penerimaanbarang->details = DB::table('trstockdt')
        ->where('fstockmtno', $penerimaanbarang->fstockmtno)
        ->get();

      $supplier = DB::table('mssupplier')
        ->where('fsupplierid', $penerimaanbarang->fsupplier)
        ->first();
      $penerimaanbarang->supplier_name = $supplier->fsuppliername ?? $penerimaanbarang->fsupplier;

      foreach ($penerimaanbarang->details as $detail) {
        $product = DB::table('msprd')
          ->where('fprdid', $detail->fprdcode)
          ->first();
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

    $activeSupplierName = null;
    if (!empty($filterSupplierId)) {
      $supplier = Supplier::where('fsupplierid', $filterSupplierId)
        ->select('fsuppliername')
        ->first();
      $activeSupplierName = $supplier ? $supplier->fsuppliername : 'N/A';
    }

    // Hitung pagination manual
    $perPage = 10;
    $totalData = $prhData->count();
    $totalPages = $totalData > 0 ? ceil($totalData / $perPage) : 1;
    $chunkedData = $prhData->chunk($perPage);

    return view('reportingpenerimaanbarang.print', compact(
      'prhData',
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
    // Ambil data utama dari query filter
    $dataToExport = $this->getPenerimaanBarangQuery($request)
      ->where('fstockmtcode', 'RCV')
      ->get();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Penerimaan Barang Report');

    // Header kolom sesuai urutan Blade (No.PO, Tanggal, Supplier, Ket, Produk, Nama Produk, Qty, Satuan)
    $headers = [
      'No. PO',
      'Tanggal',
      'Nama Supplier',
      'Keterangan',
      'Produk#',
      'Nama Produk',
      'Qty',
      'Satuan',
    ];

    // Tulis header
    $col = 'A';
    foreach ($headers as $header) {
      $sheet->setCellValue($col . '1', $header);
      $col++;
    }

    // Style header
    $sheet->getStyle('A1:H1')->applyFromArray([
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

    $row = 2;
    foreach ($dataToExport as $penerimaanbarang) {

      // 1. Join Manual Supplier
      $supplier = DB::table('mssupplier')
        ->where('fsupplierid', $penerimaanbarang->fsupplier)
        ->first();
      $supplier_name = $supplier->fsuppliername ?? $penerimaanbarang->fsupplier;

      // 2. Ambil Details dari table trstockdt
      $details = DB::table('trstockdt')
        ->where('fstockmtid', $penerimaanbarang->fstockmtid)
        ->get();

      if ($details->isNotEmpty()) {
        foreach ($details as $detail) {
          // 3. Join Manual Produk
          $product = DB::table('msprd')
            ->where('fprdid', $detail->fprdcode)
            ->first();
          $product_name = $product->fprdname ?? $detail->fprdcode;

          // Isi Baris Excel
          $sheet->setCellValue('A' . $row, $penerimaanbarang->fstockmtno);
          $sheet->setCellValue('B' . $row, \Carbon\Carbon::parse($penerimaanbarang->fstockmtdate)->format('d/m/Y'));
          $sheet->setCellValue('C' . $row, $supplier_name);
          $sheet->setCellValue('D' . $row, $penerimaanbarang->fket ?? 'LOCO BL');

          $sheet->setCellValue('E' . $row, $detail->fprdcode);
          $sheet->setCellValue('F' . $row, $product_name);
          $sheet->setCellValue('G' . $row, $detail->fqty ?? 0);
          $sheet->setCellValue('H' . $row, $detail->funit ?? 'PCS');

          $row++;
        }
      } else {
        // Jika header ada tapi detail kosong
        $sheet->setCellValue('A' . $row, $penerimaanbarang->fstockmtno);
        $sheet->setCellValue('B' . $row, \Carbon\Carbon::parse($penerimaanbarang->fstockmtdate)->format('d/m/Y'));
        $sheet->setCellValue('C' . $row, $supplier_name);
        $sheet->setCellValue('D' . $row, $penerimaanbarang->fket ?? 'LOCO BL');
        $row++;
      }
    }

    // Auto-size kolom A-H
    foreach (range('A', 'H') as $column) {
      $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    // Style Border & Number Format
    $lastDataRow = $row - 1;
    if ($lastDataRow >= 2) {
      $sheet->getStyle('A2:H' . $lastDataRow)->applyFromArray([
        'borders' => [
          'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
        ],
      ]);
      // Format angka Qty (Kolom G)
      $sheet->getStyle('G2:G' . $lastDataRow)->getNumberFormat()->setFormatCode('#,##0.00');
    }

    // Proses download
    $filename = 'Penerimaan_Barang_Report_' . now()->format('Ymd_His') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
  }
}
