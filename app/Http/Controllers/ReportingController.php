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
    $filterSupplierId = $request->query('fsupplier');

    $query = DB::table('tr_poh')
      ->select('tr_poh.*');

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

    // Ambil data PO Header
    $pohData = $query->orderBy('fpodate', 'desc')->get();

    // Untuk setiap PO Header, ambil detail-nya
    foreach ($pohData as $poh) {
      // Ambil detail berdasarkan fpono yang sesuai dengan fpohdid
      $poh->details = DB::table('tr_pod')
        ->where('fpono', $poh->fpohdid)  // fpono di tr_pod = fpohdid di tr_poh
        ->orderBy('fnou')  // urutkan berdasarkan nomor urut
        ->get();
        
      // Hitung total harga dari detail
      $poh->total_harga = $poh->details->sum('famount');

      // Ambil nama supplier jika ada relasi
      $supplier = DB::table('mssupplier')
        ->where('fsupplierid', $poh->fsupplier)
        ->first();
      $poh->supplier_name = $supplier->fsuppliername ?? $poh->fsupplier;

      // Ambil nama produk untuk setiap detail
      foreach ($poh->details as $detail) {
        $product = DB::table('msprd')
          ->where('fprdcode', $detail->fprdcode)
          ->first();
        $detail->product_name = $product->fitemname ?? $detail->fprdcode;
      }
    }
    $activeSupplierName = null;
    if (!empty($filterSupplierId)) {
      $supplier = Supplier::where('fsupplierid', $filterSupplierId)
        ->select('fsuppliername')
        ->first();

      // Cek dulu apakah $supplier bukan null
      $activeSupplierName = $supplier ? $supplier->fsuppliername : 'N/A';
    }
    // dd($activeSupplierName);

    return view('reporting.print', compact('pohData', 'activeSupplierName'));
  }
  /**
   * Mengekspor data TR_POH (Header) dan TR_POD (Detail) ke CSV/Excel dalam format Datar (Flattened).
   */
  public function exportExcel(Request $request)
  {
    // 1. Ambil data yang sudah difilter dan Eager Load Detail
    $dataToExport = $this->getPohQuery($request)
      ->with('details')
      ->get();

    $filename = 'PO_Report_Flattened_' . now()->format('Ymd_His') . '.csv';

    $headers = [
      'Content-Type' => 'text/csv',
      'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ];

    $callback = function () use ($dataToExport) {
      $file = fopen('php://output', 'w');

      // --- PENAMBAHAN PENTING UNTUK EXCEL ---
      // Baris ini memaksa Excel mengenali semicolon (;) sebagai delimiter.
      fputs($file, "sep=;\n"); // Hapus sep=; jika Excel Anda menggunakan koma sebagai delimiter default

      // --- HEADER FORMAT BARU (Datar) ---
      $header = [
        // --- KOLOM PARENT (TR_POH) ---
        'ID PO',
        'NOMOR PO',
        'TANGGAL PO',
        'SUPPLIER ID',
        'MATA UANG',
        'TOTAL PO',
        'STATUS CLOSE',
        'STATUS APPROVAL',

        // --- KOLOM CHILD (TR_POD) ---
        'NO. URUT ITEM',
        'KODE PRODUK',
        'DESKRIPSI ITEM',
        'QTY',
        'SATUAN',
        'HARGA SATUAN',
        'JUMLAH ITEM',
      ];
      fputcsv($file, $header, ';');

      // --- TULIS DATA BARIS ---
      foreach ($dataToExport as $poh) {

        // 1. Siapkan data Master (Header) yang akan diulang di setiap baris Detail
        $pohBaseRow = [
          $poh->fpohdid,
          $poh->fpono,
          Carbon::parse($poh->fpodate)->format('d-m-Y'),
          $poh->fsupplier,
          $poh->fcurrency,
          number_format($poh->famountpo, 2, '.', ''),
          $poh->fclose === '1' ? 'Closed' : 'Open',
          $poh->fapproval ?? '-',
        ];

        // 2. Handle PO dengan Detail (output satu baris per item Detail)
        if ($poh->details->isNotEmpty()) {
          foreach ($poh->details as $pod) {
            $podRow = [
              // Kolom Detail diisi
              $pod->fnou,
              $pod->fprdcode,
              $pod->fdesc ?? '-',
              number_format($pod->fqty, 2, '.', ''),
              $pod->fsatuan,
              number_format($pod->fprice, 2, '.', ''),
              number_format($pod->famount, 2, '.', ''),
            ];

            // Gabungkan Header dan Detail
            fputcsv($file, array_merge($pohBaseRow, $podRow), ';');
          }
        } else {
          // 3. Handle PO tanpa Detail (output satu baris dengan kolom Detail kosong)
          $emptyDetailRow = ['', '', '', '', '', '', '']; // 7 kolom detail kosong

          fputcsv($file, array_merge($pohBaseRow, $emptyDetailRow), ';');
        }
      }

      fclose($file);
    };

    // 4. Streaming response ke browser
    return response()->stream($callback, 200, $headers);
  }
}
