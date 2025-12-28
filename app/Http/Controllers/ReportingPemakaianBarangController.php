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
      ->select('trstockmt.*', 'account.faccname', 'mswh.fwhname')
      ->leftJoin('account', 'trstockmt.frefno', '=', 'account.faccid')
      ->leftJoin('mswh', 'trstockmt.ffrom', '=', 'mswh.fwhid')
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
    // 1. Ambil data Header menggunakan join account (sesuai query Anda)
    $dataToExport = $this->getPemakaianBarangQuery($request)
      ->leftJoin('mswh', 'trstockmt.ffrom', '=', 'mswh.fwhid')
      ->where('fstockmtcode', 'PBR')
      ->get();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Pemakaian Barang Report');

    // Header kolom Excel
    $headers = [
      'No. Transaksi',
      'Tanggal',
      'Gudang',
      'Keterangan Header',
      'Total Harga Header',
      'User-id',
      'Kode Produk',
      'Nama Barang',
      'Sub Account',
      'Nama Account',
      'Quantity',
      '@ Harga',
      'Total Harga Detail',
      'Keterangan Detail'
    ];

    $col = 'A';
    foreach ($headers as $header) {
      $sheet->setCellValue($col . '1', $header);
      $col++;
    }

    // Style Header
    $sheet->getStyle('A1:N1')->applyFromArray([
      'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
      'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4472C4'],
      ],
      'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
      'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
    ]);

    $row = 2;
    $gtAmountHeader = 0;
    $gtQty = 0;
    $gtHargaSatuan = 0;
    $gtTotalHargaDetail = 0;

    foreach ($dataToExport as $fakturpembelian) {
      // Akumulasi Grand Total Header
      $gtAmountHeader += $fakturpembelian->famount ?? 0;

      // 2. Ambil data Detail menggunakan join sesuai struktur Anda
      $details = DB::table('trstockdt')
        ->select('trstockdt.*', 'account.faccname', 'mssubaccount.fsubaccountname')
        ->leftJoin('account', 'trstockdt.frefdtno', '=', 'account.faccid')
        ->leftJoin('mssubaccount', 'trstockdt.frefso', '=', 'mssubaccount.fsubaccountid')
        ->where('fstockmtno', $fakturpembelian->fstockmtno)
        ->get();

      if ($details->isNotEmpty()) {
        foreach ($details as $detail) {
          // Ambil Nama Produk (Manual Join)
          $product = DB::table('msprd')->where('fprdid', $detail->fprdcode)->first();
          $product_name = $product->fprdname ?? $detail->fprdcode;

          // Akumulasi Grand Total Detail
          $gtQty += $detail->fqty ?? 0;
          $gtHargaSatuan += $detail->fprice ?? 0;
          $gtTotalHargaDetail += $detail->ftotprice ?? 0;

          // Isi data ke baris Excel
          $sheet->setCellValue('A' . $row, $fakturpembelian->fstockmtno);
          $sheet->setCellValue('B' . $row, \Carbon\Carbon::parse($fakturpembelian->fstockmtdate)->format('d/m/Y'));
          $sheet->setCellValue('C' . $row, $fakturpembelian->fwhname);
          $sheet->setCellValue('D' . $row, $fakturpembelian->fket);
          $sheet->setCellValue('E' . $row, $fakturpembelian->famount ?? 0);
          $sheet->setCellValue('F' . $row, $fakturpembelian->fusercreate);

          $sheet->setCellValue('G' . $row, $detail->fprdcode);
          $sheet->setCellValue('H' . $row, $product_name);
          $sheet->setCellValue('I' . $row, $detail->fsubaccountname ?? '-'); // Hasil Join
          $sheet->setCellValue('J' . $row, $detail->faccname ?? '-');        // Hasil Join
          $sheet->setCellValue('K' . $row, $detail->fqty ?? 0);
          $sheet->setCellValue('L' . $row, $detail->fprice ?? 0);
          $sheet->setCellValue('M' . $row, $detail->ftotprice ?? 0);
          $sheet->setCellValue('N' . $row, $detail->fketdt);

          $row++;
        }
      }
    }

    // --- GRAND TOTAL ---
    $sheet->setCellValue('A' . $row, 'GRAND TOTAL');
    $sheet->mergeCells("A$row:D$row");

    $sheet->setCellValue('E' . $row, $gtAmountHeader);
    $sheet->setCellValue('K' . $row, $gtQty);
    $sheet->setCellValue('L' . $row, $gtHargaSatuan);
    $sheet->setCellValue('M' . $row, $gtTotalHargaDetail);

    // Style Grand Total
    $sheet->getStyle("A$row:N$row")->applyFromArray([
      'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
      'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => '333333'],
      ],
      'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
    ]);

    // Final Touch: Formatting
    foreach (range('A', 'N') as $column) {
      $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    $numFormat = '#,##0.00';
    $sheet->getStyle('E2:E' . $row)->getNumberFormat()->setFormatCode($numFormat);
    $sheet->getStyle('K2:M' . $row)->getNumberFormat()->setFormatCode($numFormat);
    $sheet->getStyle('A2:N' . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

    // Download File
    $filename = 'Pemakaian_Barang_Report_' . now()->format('Ymd_His') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
  }
}
