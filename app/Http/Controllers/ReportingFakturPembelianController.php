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

class ReportingFakturPembelianController extends Controller
{
  protected function getFakturPembelianQuery(Request $request)
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
      ? $this->getFakturPembelianQuery($request)
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

    return view('reportingfakturpembelian.index', [
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
  public function printFakturPembelian(Request $request)
  {
    $user_session = auth('sysuser')->user();

    $filterSupplierId = $request->query('fsupplier');

    $query = DB::table('trstockmt')
      ->select('trstockmt.*')
      ->where('fstockmtcode', 'TER');

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
      $fakturpembelian->details = DB::table('trstockdt')
        ->where('fstockmtno', $fakturpembelian->fstockmtno)
        ->get();

      $grandTotalHarga += $fakturpembelian->famountremain ?? 0;
      $grandTotalPPN += $fakturpembelian->famountpajak ?? 0;
      $grandTotalPO += $fakturpembelian->famountmt ?? 0;

      $fakturpembelian->famountremain = $fakturpembelian->details->sum('famountmt');

      $supplier = DB::table('mssupplier')
        ->where('fsupplierid', $fakturpembelian->fsupplier)
        ->first();
      $fakturpembelian->supplier_name = $supplier->fsuppliername ?? $fakturpembelian->fsupplier;

      foreach ($fakturpembelian->details as $detail) {
        $product = DB::table('msprd')
          ->where('fprdid', $detail->fprdcode)
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

    return view('reportingfakturpembelian.print', compact(
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
    // Ambil data utama dengan filter 'TER'
    $dataToExport = $this->getFakturPembelianQuery($request)
      ->where('fstockmtcode', 'TER')
      ->get();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Faktur Pembelian Report');

    // Header Kolom (A - O)
    $headers = [
      'No. Transaksi',
      'Tanggal',
      'Type',
      'Nama Supplier',
      'Total Harga',
      'PPN',
      'Total Faktur',
      'Kode Barang',
      'Nama Barang',
      'No.Ref',
      'Quantity',
      'Qty.Ad',
      '@ Harga',
      '@ Biaya',
      'Jumlah'
    ];

    $col = 'A';
    foreach ($headers as $header) {
      $sheet->setCellValue($col . '1', $header);
      $col++;
    }

    // Style Header
    $sheet->getStyle('A1:O1')->applyFromArray([
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
    $gtTotalHarga = 0;
    $gtPPN = 0;
    $gtTotalFaktur = 0;
    $gtQty = 0;
    $gtQtyAd = 0;
    $gtJumlahDetail = 0;

    $row = 2;
    foreach ($dataToExport as $faktur) {
      // Akumulasi Grand Total Header (Hanya sekali per No. Transaksi)
      $gtTotalHarga += $faktur->famount ?? 0;
      $gtPPN += $faktur->famountpajak ?? 0;
      $gtTotalFaktur += $faktur->famountmt ?? 0;

      $supplier = DB::table('mssupplier')->where('fsupplierid', $faktur->fsupplier)->first();
      $supplier_name = $supplier->fsuppliername ?? $faktur->fsupplier;

      $typeLabel = '-';
      if (($faktur->ftypebuy ?? '') == '0') $typeLabel = 'Stok';
      elseif (($faktur->ftypebuy ?? '') == '1') $typeLabel = 'Non Stok';
      elseif (($faktur->ftypebuy ?? '') == '2') $typeLabel = 'Uang Muka';

      $details = DB::table('trstockdt')->where('fstockmtid', $faktur->fstockmtid)->get();

      if ($details->isNotEmpty()) {
        foreach ($details as $detail) {
          $product = DB::table('msprd')->where('fprdid', $detail->fprdcode)->first();
          $product_name = $product->fprdname ?? $detail->fprdcode;

          // Akumulasi Grand Total Detail
          $gtQty += $detail->fqty ?? 0;
          $gtQtyAd += $detail->fqtyremain ?? 0;
          $gtJumlahDetail += $detail->ftotprice ?? 0;

          $sheet->setCellValue('A' . $row, $faktur->fstockmtno);
          $sheet->setCellValue('B' . $row, \Carbon\Carbon::parse($faktur->fstockmtdate)->format('d/m/Y'));
          $sheet->setCellValue('C' . $row, $typeLabel);
          $sheet->setCellValue('D' . $row, $supplier_name);
          $sheet->setCellValue('E' . $row, $faktur->famount ?? 0);
          $sheet->setCellValue('F' . $row, $faktur->famountpajak ?? 0);
          $sheet->setCellValue('G' . $row, $faktur->famountmt ?? 0);
          $sheet->setCellValue('H' . $row, $detail->fprdcode);
          $sheet->setCellValue('I' . $row, $product_name);
          $sheet->setCellValue('J' . $row, blank($detail->frefdtno) ? '-' : $detail->frefdtno);
          $sheet->setCellValue('K' . $row, $detail->fqty ?? 0);
          $sheet->setCellValue('L' . $row, $detail->fqtyremain ?? 0);
          $sheet->setCellValue('M' . $row, $detail->fprice ?? 0);
          $sheet->setCellValue('N' . $row, $detail->fbiaya ?? 0);
          $sheet->setCellValue('O' . $row, $detail->ftotprice ?? 0);
          $row++;
        }
      } else {
        $sheet->setCellValue('A' . $row, $faktur->fstockmtno);
        $sheet->setCellValue('B' . $row, \Carbon\Carbon::parse($faktur->fstockmtdate)->format('d/m/Y'));
        $sheet->setCellValue('C' . $row, $typeLabel);
        $sheet->setCellValue('D' . $row, $supplier_name);
        $sheet->setCellValue('E' . $row, $faktur->famount ?? 0);
        $sheet->setCellValue('F' . $row, $faktur->famountpajak ?? 0);
        $sheet->setCellValue('G' . $row, $faktur->famountmt ?? 0);
        $row++;
      }
    }

    // --- PENULISAN GRAND TOTAL ---
    $sheet->setCellValue('A' . $row, 'GRAND TOTAL');
    $sheet->mergeCells("A$row:D$row");

    $sheet->setCellValue('E' . $row, $gtTotalHarga);
    $sheet->setCellValue('F' . $row, $gtPPN);
    $sheet->setCellValue('G' . $row, $gtTotalFaktur);
    $sheet->setCellValue('K' . $row, $gtQty);
    $sheet->setCellValue('L' . $row, $gtQtyAd);
    $sheet->setCellValue('O' . $row, $gtJumlahDetail);

    // Style Grand Total
    $sheet->getStyle("A$row:O$row")->applyFromArray([
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
    foreach (range('A', 'O') as $column) {
      $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    $lastDataRow = $row; // Termasuk baris Grand Total
    $currencyFormat = '#,##0.00';
    $sheet->getStyle('E2:G' . $lastDataRow)->getNumberFormat()->setFormatCode($currencyFormat);
    $sheet->getStyle('K2:O' . $lastDataRow)->getNumberFormat()->setFormatCode($currencyFormat);
    $sheet->getStyle('A2:O' . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

    $filename = 'Faktur_Pembelian_Report_' . now()->format('Ymd_His') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
  }
}
