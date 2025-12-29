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

class ReportingAssemblingController extends Controller
{
  protected function getAssemblingQuery(Request $request)
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
      ? $this->getAssemblingQuery($request)
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

    return view('reportingassembling.index', [
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
  public function printAssembling(Request $request)
  {
    $user_session = auth('sysuser')->user();

    $filterSupplierId = $request->query('fsupplier');

    $query = DB::table('trstockmt')
      ->select('trstockmt.*', 'mswh.fwhname')
      ->leftJoin('mswh', 'trstockmt.ffrom', '=', 'mswh.fwhid')
      ->where('fstockmtcode', 'LHP');

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
        $fhpp = $product->fhpp ?? 0;
        $qty = (float) ($detail->fqty ?? 0);
        $hpp = (float) ($fhpp);

        $detail->fhpp = $hpp;
        $detail->total_hpp = $qty * $hpp;
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

    return view('reportingassembling.print', compact(
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
    // Ambil data utama dengan filter 'LHP'
    $dataToExport = $this->getAssemblingQuery($request)
      ->where('fstockmtcode', 'LHP')
      ->get();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Assembling Report');

    // Header Baru: Gudang ditambahkan setelah Tanggal
    $headers = [
      'No. Transaksi',
      'Tanggal',
      'Gudang',
      'Keterangan',
      'Kode Produk',
      'Nama Produk',
      'Qty',
      'Qty. Rijeks',
      '@ HPP',
      'Total HPP',
    ];

    // Tulis header (A1 sampai J1)
    $col = 'A';
    foreach ($headers as $header) {
      $sheet->setCellValue($col . '1', $header);
      $col++;
    }

    // Style header
    $sheet->getStyle('A1:J1')->applyFromArray([
      'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
      'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4472C4'],
      ],
      'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
    ]);

    $row = 2;

    // Inisialisasi Grand Total menggunakan template yang Anda inginkan
    $grandTotal = [
      'qty' => 0,
      'qty_rijeks' => 0,
      'total_hpp' => 0,
    ];

    foreach ($dataToExport as $assembling) {
      // Ambil nama gudang (Join manual jika tidak ada di getAssemblingQuery)
      $warehouse = DB::table('mswh')->where('fwhid', $assembling->ffrom)->first();
      $wh_name = $warehouse->fwhname ?? '-';

      // Ambil Details
      $details = DB::table('trstockdt')
        ->where('fstockmtid', $assembling->fstockmtid)
        ->get();

      foreach ($details as $detail) {
        $product = DB::table('msprd')->where('fprdid', $detail->fprdcode)->first();

        $product_name = $product->fprdname ?? $detail->fprdcode;
        $hpp = (float) ($product->fhpp ?? 0);
        $qty = (float) ($detail->fqty ?? 0);
        $rijeks = (float) ($detail->fqtyremain ?? 0);
        $total_hpp = $qty * $hpp;

        // Akumulasi ke template grandTotal
        $grandTotal['qty'] += $qty;
        $grandTotal['qty_rijeks'] += $rijeks;
        $grandTotal['total_hpp'] += $total_hpp;

        // Isi Baris Excel (Kolom A-J)
        $sheet->setCellValue('A' . $row, $assembling->fstockmtno);
        $sheet->setCellValue('B' . $row, \Carbon\Carbon::parse($assembling->fstockmtdate)->format('d/m/Y'));
        $sheet->setCellValue('C' . $row, $wh_name); // Kolom Gudang Baru
        $sheet->setCellValue('D' . $row, $assembling->fket ?? 'LOCO BL');
        $sheet->setCellValue('E' . $row, $detail->fprdcode);
        $sheet->setCellValue('F' . $row, $product_name);
        $sheet->setCellValue('G' . $row, $qty);
        $sheet->setCellValue('H' . $row, $rijeks);
        $sheet->setCellValue('I' . $row, $hpp);
        $sheet->setCellValue('J' . $row, $total_hpp);

        $row++;
      }
    }

    // --- BARIS GRAND TOTAL ---
    $sheet->setCellValue('F' . $row, 'GRAND TOTAL');
    $sheet->setCellValue('G' . $row, $grandTotal['qty']);
    $sheet->setCellValue('H' . $row, $grandTotal['qty_rijeks']);
    $sheet->setCellValue('J' . $row, $grandTotal['total_hpp']);

    // Style Grand Total (Bold & Warna Kuning)
    $sheet->getStyle('F' . $row . ':J' . $row)->getFont()->setBold(true);
    $sheet->getStyle('F' . $row . ':J' . $row)->getFill()
      ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
      ->getStartColor()->setRGB('FFFF00');

    // Auto-size kolom A-J
    foreach (range('A', 'J') as $column) {
      $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    // Style Border & Format Angka 10.000,00
    $lastDataRow = $row;
    if ($lastDataRow >= 2) {
      $sheet->getStyle('A1:J' . $lastDataRow)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

      // G, H, I, J adalah kolom numerik (Qty, Rijeks, HPP, Total)
      $sheet->getStyle('G2:J' . $lastDataRow)->getNumberFormat()->setFormatCode('#,##0.00');
    }

    $filename = 'Assembling_Report_LHP_' . now()->format('Ymd_His') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
  }
}
