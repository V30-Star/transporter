<?php

namespace App\Http\Controllers;

use App\Models\Wilayah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Tr_prh; // Sesuaikan dengan path Model Purchase Request Anda
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session; // Digunakan untuk 'session()'

class ReportingController extends Controller
{
  // protected $permission = [];

  public function __construct()
  {
    $restrictedPermissions = session('user_restricted_permissions', []);

    $this->restrictedPermission = $restrictedPermissions ? explode(',', $restrictedPermissions) : [];
  }
  public function index(Request $request)
  {
    // --- Handle Request AJAX dari DataTables ---
    if ($request->ajax()) {
      try {
        $query = Tr_prh::query();
        $totalRecords = Tr_prh::count();

        // Kolom yang bisa dicari
        $searchableColumns = ['fprno', 'fprdin'];

        // Handle Search
        if ($search = $request->input('search.value')) {
          $query->where(function ($q) use ($search, $searchableColumns) {
            foreach ($searchableColumns as $column) {
              $q->orWhere($column, 'like', "%{$search}%");
            }
          });
        }

        // Filter status
        $statusFilter = $request->input('status', 'active');
        if ($statusFilter === 'active') {
          $query->where('fclose', '0');
        } elseif ($statusFilter === 'nonactive') {
          $query->where('fclose', '1');
        }
        // Jika 'all', tidak perlu filter

        // Filter tahun
        if ($year = $request->input('year')) {
          $query->whereRaw('EXTRACT(YEAR FROM fcreatedat) = ?', [$year]);
        }

        // Filter bulan
        if ($month = $request->input('month')) {
          $query->whereRaw('EXTRACT(MONTH FROM fcreatedat) = ?', [$month]);
        }

        $filteredRecords = (clone $query)->count();

        // Sorting
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'desc');

        $columns = [
          0 => 'fprno',
          1 => 'fprdin',
          2 => 'fclose',
        ];

        if (isset($columns[$orderColumnIndex])) {
          $query->orderBy($columns[$orderColumnIndex], $orderDir);
        } else {
          $query->orderBy('fprdin', 'desc');
        }

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);

        if ($length != -1) {
          $query->skip($start)->take($length);
        }

        // Get data
        $records = $query->get(['fprid', 'fprno', 'fprdin', 'fcreatedat', 'fclose']);

        // Format data untuk DataTables
        $data = $records->map(function ($record) {
          return [
            'fprno'    => $record->fprno ?? '-',
            'fprdin'   => $record->fprdin ?? '-',
            'fclose'   => $record->fclose ?? '0',
            'fprid'    => $record->fprid,
            'DT_RowId' => 'row_' . $record->fprid
          ];
        });

        // Response JSON
        return response()->json([
          'draw'            => intval($request->input('draw')),
          'recordsTotal'    => $totalRecords,
          'recordsFiltered' => $filteredRecords,
          'data'            => $data,
          // DEBUG info (hapus setelah testing)
          'debug' => [
            'status_filter' => $statusFilter,
            'year' => $year,
            'month' => $month,
            'query_count' => $records->count()
          ]
        ]);
      } catch (\Exception $e) {
        // Log error
        Log::error('DataTables Error: ' . $e->getMessage());

        return response()->json([
          'draw'            => intval($request->input('draw')),
          'recordsTotal'    => 0,
          'recordsFiltered' => 0,
          'data'            => [],
          'error'           => $e->getMessage()
        ]);
      }
    }

    abort(404);
  }
  /**
   * FUNGSI 2: Menampilkan halaman reporting (Mengembalikan View)
   * Route: reports.pr.view akan diarahkan ke sini.
   */
  public function reportIndex(Request $request)
  {
    // Ambil tahun-tahun yang tersedia
    $availableYears = Tr_prh::selectRaw('DISTINCT EXTRACT(YEAR FROM fcreatedat) as year')
      ->whereNotNull('fcreatedat')
      ->orderByRaw('EXTRACT(YEAR FROM fcreatedat) DESC')
      ->pluck('year');

    // Untuk menampilkan filter yang sedang aktif di view
    $status = $request->query('status', 'active');
    $year = $request->query('year');
    $month = $request->query('month');

    // Daftar bulan untuk dropdown
    $months = [
      1 => 'Januari',
      2 => 'Februari',
      3 => 'Maret',
      4 => 'April',
      5 => 'Mei',
      6 => 'Juni',
      7 => 'Juli',
      8 => 'Agustus',
      9 => 'September',
      10 => 'Oktober',
      11 => 'November',
      12 => 'Desember'
    ];

    // Memanggil view yang sudah dikoreksi menjadi 'reporting.index'
    return view('reporting.index', compact('availableYears', 'status', 'year', 'month', 'months'));
  }

  /**
   * FUNGSI 3: Mengunduh data yang difilter ke format Excel (Mengembalikan File)
   * Route: reports.pr.export akan diarahkan ke sini.
   */
  public function exportExcel(Request $request)
  {
    $query = Tr_prh::query();

    // --- Terapkan Filter (menggunakan $request->query() karena ini GET Request dari link Excel) ---

    // Filter status
    $statusFilter = $request->query('status', 'active');
    if ($statusFilter === 'active') {
      $query->where('fclose', '0');
    } elseif ($statusFilter === 'nonactive') {
      $query->where('fclose', '1');
    }

    // Filter tahun (PostgreSQL syntax)
    if ($year = $request->query('year')) {
      $query->whereRaw('EXTRACT(YEAR FROM fcreatedat) = ?', [$year]);
    }

    // Filter bulan (PostgreSQL syntax)
    if ($month = $request->query('month')) {
      $query->whereRaw('EXTRACT(MONTH FROM fcreatedat) = ?', [$month]);
    }

    // Urutkan data
    $query->orderBy('fprdin', 'asc');

    // Ambil data
    $records = $query->get(['fprno', 'fprdin', 'fcreatedat', 'fclose']);

    // --- Generate Excel menggunakan PhpSpreadsheet ---

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Header Kolom
    $sheet->setCellValue('A1', 'Nomor PR');
    $sheet->setCellValue('B1', 'Tanggal PR');
    $sheet->setCellValue('C1', 'Status');
    $sheet->setCellValue('D1', 'Dibuat Pada');

    // Data Baris
    $row = 2;
    foreach ($records as $record) {
      $statusText = $record->fclose == '0' ? 'Aktif' : 'Non-Aktif/Ditutup';
      $sheet->setCellValue('A' . $row, $record->fprno);
      $sheet->setCellValue('B' . $row, $record->fprdin);
      $sheet->setCellValue('C' . $row, $statusText);
      $sheet->setCellValue('D' . $row, $record->fcreatedat);
      $row++;
    }

    // --- Pengaturan Response Download ---

    $writer = new Xlsx($spreadsheet);
    $filename = 'Laporan_Tr_prh_' . date('Ymd_His') . '.xlsx';

    $response = new StreamedResponse(
      function () use ($writer) {
        $writer->save('php://output');
      }
    );

    $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
    $response->headers->set('Cache-Control', 'max-age=0');

    return $response;
  }

  public function create()
  {
    return view('master.wilayah.create');
  }

  public function store(Request $request)
  {
    $validated = $request->validate([
      'fwilayahcode' => 'required|string|unique:mswilayah,fwilayahcode',
      'fwilayahname' => 'required|string',
    ], [
      'fwilayahcode.required' => 'Kode wilayah harus diisi.',
      'fwilayahname.required' => 'Nama wilayah harus diisi.',
      'fwilayahcode.unique' => 'Kode wilayah sudah ada, silakan gunakan kode lain.',
    ]);

    $validated['fcreatedby'] = auth('sysuser')->user()->fname ?? null;
    $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? 'system';  // Fallback jika tidak ada
    $validated['fcreatedat'] = now();

    $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

    Wilayah::create($validated);

    return redirect()
      ->route('wilayah.create')
      ->with('success', 'Wilayah berhasil ditambahkan.');
  }

  public function edit($fwilayahid)
  {
    $wilayah = Wilayah::findOrFail($fwilayahid);

    return view('master.wilayah.edit', compact('wilayah'));
  }

  public function update(Request $request, $fwilayahid)
  {
    $validated = $request->validate([
      'fwilayahcode' => "required|string|unique:mswilayah,fwilayahcode,{$fwilayahid},fwilayahid",
      'fwilayahname' => 'required|string',
    ], [
      'fwilayahcode.required' => 'Kode wilayah harus diisi.',
      'fwilayahname.required' => 'Nama wilayah harus diisi.',
      'fwilayahcode.unique' => 'Kode wilayah sudah ada, silakan gunakan kode lain.',
    ]);

    $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';
    $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null;
    $validated['fupdatedat'] = now();

    $wilayah = Wilayah::findOrFail($fwilayahid);
    $wilayah->update($validated);

    return redirect()
      ->route('wilayah.index')
      ->with('success', 'Wilayah berhasil di-update.');
  }

  public function destroy($fwilayahid)
  {
    $wilayah = Wilayah::findOrFail($fwilayahid);
    $wilayah->delete();

    return redirect()
      ->route('wilayah.index')
      ->with('success', 'Wilayah berhasil dihapus.');
  }
}
