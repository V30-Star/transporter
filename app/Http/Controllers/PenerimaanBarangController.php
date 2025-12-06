<?php

namespace App\Http\Controllers;

use App\Models\Tr_prh;
use App\Models\Tr_prd;
use App\Models\Tr_poh;
use App\Models\Tr_pod;
use App\Models\Supplier;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Mail\ApprovalEmailPo;
use App\Models\PenerimaanPembelianDetail;
use App\Models\PenerimaanPembelianHeader;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Exception; // Pastikan ini ada jika menggunakan throw new \Exception
use Carbon\Carbon; // sekalian biar aman untuk tanggal

class PenerimaanBarangController extends Controller
{
  public function index(Request $request)
  {
    // --- 1. PERMISSIONS ---
    $canCreate = in_array('createPenerimaanBarang', explode(',', session('user_restricted_permissions', '')));
    $canEdit   = in_array('updatePenerimaanBarang', explode(',', session('user_restricted_permissions', '')));
    $canDelete = in_array('deletePenerimaanBarang', explode(',', session('user_restricted_permissions', '')));
    $showActionsColumn = $canEdit || $canDelete;

    $year = $request->query('year');
    $month = $request->query('month');

    // Ambil tahun-tahun yang tersedia dari data
    $availableYears = PenerimaanPembelianHeader::selectRaw('DISTINCT EXTRACT(YEAR FROM fdatetime) as year')
      ->where('fstockmtcode', 'RCV')
      ->whereNotNull('fdatetime')
      ->orderByRaw('EXTRACT(YEAR FROM fdatetime) DESC')
      ->pluck('year');

    // --- 2. Handle Request AJAX dari DataTables ---
    if ($request->ajax()) {

      // Query dasar HANYA untuk 'RCV' (Receiving)
      $query = PenerimaanPembelianHeader::where('fstockmtcode', 'RCV');

      // Total records (dengan filter 'RCV')
      $totalRecords = PenerimaanPembelianHeader::where('fstockmtcode', 'RCV')->count();

      // Handle Search (cari di No. Penerimaan)
      if ($search = $request->input('search.value')) {
        $query->where('fstockmtno', 'like', "%{$search}%");
      }

      // Filter tahun
      if ($year) {
        $query->whereRaw('EXTRACT(YEAR FROM fdatetime) = ?', [$year]);
      }

      // Filter bulan
      if ($month) {
        $query->whereRaw('EXTRACT(MONTH FROM fdatetime) = ?', [$month]);
      }

      // Total records setelah filter
      $filteredRecords = (clone $query)->count();

      // Handle Sorting
      $orderColIdx = $request->input('order.0.column', 0);
      $orderDir = $request->input('order.0.dir', 'desc');

      // Kolom di tabel: 0 = fstockmtno, 1 = fstockmtdate, 2 = actions
      $sortableColumns = ['fstockmtno', 'fstockmtdate'];

      if (isset($sortableColumns[$orderColIdx])) {
        $query->orderBy($sortableColumns[$orderColIdx], $orderDir);
      } else {
        $query->orderBy('fstockmtid', 'desc'); // Default sort
      }

      // Handle Paginasi
      $start = $request->input('start', 0);
      $length = $request->input('length', 10);
      $records = $query->skip($start)
        ->take($length)
        ->get(['fstockmtid', 'fstockmtno', 'fstockmtdate']);

      // Format Data - HANYA RETURN DATA MENTAH
      $data = $records->map(function ($row) {
        return [
          'fstockmtid'   => $row->fstockmtid,
          'fstockmtno'   => $row->fstockmtno,
          'fstockmtdate' => Carbon::parse($row->fstockmtdate)->format('d/m/Y')
        ];
      });

      return response()->json([
        'draw'            => intval($request->input('draw')),
        'recordsTotal'    => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data'            => $data
      ]);
    }

    // --- 3. Handle Request non-AJAX ---
    return view('penerimaanbarang.index', compact(
      'canCreate',
      'canEdit',
      'canDelete',
      'showActionsColumn',
      'availableYears',
      'year',
      'month'
    ));
  }

  // Di PenerimaanBarangController
  public function pickable(Request $request)
  {
    // Base query dengan JOIN
    $query = DB::table('tr_poh')
      ->leftJoin('mssupplier', 'tr_poh.fsupplier', '=', 'mssupplier.fsupplierid')
      ->select(
        'tr_poh.*',
        'mssupplier.fsuppliername',
        'mssupplier.fsuppliercode'
      );

    // Total records tanpa filter
    $recordsTotal = DB::table('tr_poh')->count();

    // Search
    if ($request->filled('search') && $request->search != '') {
      $search = $request->search;
      $query->where(function ($q) use ($search) {
        $q->where('tr_poh.fpono', 'ilike', "%{$search}%")
          ->orWhere('mssupplier.fsuppliername', 'ilike', "%{$search}%")
          ->orWhere('mssupplier.fsuppliercode', 'ilike', "%{$search}%");
      });
    }

    // Total records setelah filter
    $recordsFiltered = $query->count();

    // Sorting
    $orderColumn = $request->input('order_column', 'fpodate');
    $orderDir = $request->input('order_dir', 'desc');

    $allowedColumns = ['fpono', 'fsupplier', 'fpodate'];
    if (in_array($orderColumn, $allowedColumns)) {
      // Prefix table name untuk kolom di tr_poh
      if (in_array($orderColumn, ['fpono', 'fpodate'])) {
        $query->orderBy('tr_poh.' . $orderColumn, $orderDir);
      } else {
        $query->orderBy('mssupplier.fsuppliername', $orderDir);
      }
    } else {
      $query->orderBy('tr_poh.fpodate', 'desc');
    }

    // Pagination
    $start = (int) $request->input('start', 0);
    $length = (int) $request->input('length', 10);

    $data = $query->skip($start)
      ->take($length)
      ->get();

    // Response format untuk DataTables
    return response()->json([
      'draw' => (int) $request->input('draw', 1),
      'recordsTotal' => (int) $recordsTotal,
      'recordsFiltered' => (int) $recordsFiltered,
      'data' => $data
    ]);
  }

  public function items($id)
  {
    // Ambil data header PO berdasarkan fpohdid
    $header = DB::table('tr_poh')
      ->where('fpohdid', $id)
      ->first();

    if (!$header) {
      return response()->json([
        'message' => 'PO tidak ditemukan'
      ], 404);
    }

    // Ambil items dari tr_pod dengan join ke msprd
    $items = DB::table('tr_pod')
      ->where('tr_pod.fpono', $header->fpohdid) // fpono (FK) = fpohdid (PK di tr_poh)
      ->leftJoin('msprd as m', 'm.fprdid', '=', 'tr_pod.fprdcode')
      ->select([
        'tr_pod.fpodid as frefdtno',        // ID detail sebagai referensi
        'tr_pod.fnou as fnouref',            // Nomor urut
        'tr_pod.fprdcode as fitemcode',      // Kode produk
        'm.fprdname as fitemname',           // Nama produk dari master
        'tr_pod.fqty',                       // Qty
        'tr_pod.fqtyremain',                 // Qty sisa (jika diperlukan)
        'tr_pod.fsatuan as fsatuan',         // Satuan
        'tr_pod.fpono',                      // FK ke PO header
        'tr_pod.fprice as fprice',           // Harga
        'tr_pod.fprice_rp as fprice_rp',     // Harga Rupiah (jika ada)
        'tr_pod.famount as ftotal',          // Total amount
        'tr_pod.fdesc as fdesc',             // Deskripsi
        'tr_pod.frefdtno',                   // Referensi detail eksternal
        DB::raw('0::numeric as fterima'),    // Default terima = 0
      ])
      ->orderBy('tr_pod.fnou')
      ->get();

    return response()->json([
      'header' => [
        'fpohdid'   => $header->fpohdid,
        'fpono'     => $header->fpono,
        'fsupplier' => trim($header->fsupplier ?? ''),
        'fpodate'   => $header->fpodate ? date('Y-m-d H:i:s', strtotime($header->fpodate)) : null,
      ],
      'items'  => $items,
    ]);
  }

  private function generatetr_poh_Code(?Carbon $onDate = null, $branch = null): string
  {
    $date = $onDate ?: now();

    $branch = $branch
      ?? Auth::guard('sysuser')->user()?->fcabang
      ?? Auth::user()?->fcabang
      ?? null;

    // resolve kode cabang
    $kodeCabang = null;
    if ($branch !== null) {
      $needle = trim((string)$branch);
      if (is_numeric($needle)) {
        $kodeCabang = DB::table('mscabang')->where('fcabangid', (int)$needle)->value('fcabangkode');
      } else {
        $kodeCabang = DB::table('mscabang')->whereRaw('LOWER(fcabangkode)=LOWER(?)', [$needle])->value('fcabangkode')
          ?: DB::table('mscabang')->whereRaw('LOWER(fcabangname)=LOWER(?)', [$needle])->value('fcabangkode');
      }
    }
    if (!$kodeCabang) $kodeCabang = 'NA';

    $prefix = sprintf('PO.%s.%s.%s.', $kodeCabang, $date->format('y'), $date->format('m'));

    // kunci per (branch, tahun-bulan) — TANPA bikin tabel baru
    $lockKey = crc32('PO|' . $kodeCabang . '|' . $date->format('Y-m'));
    DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

    $last = DB::table('tr_poh')
      ->where('fpono', 'like', $prefix . '%')
      ->selectRaw("MAX(CAST(split_part(fpono, '.', 5) AS int)) AS lastno")
      ->value('lastno');

    $next = (int)$last + 1;
    return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
  }

  public function print(string $fstockmtno)
  {
    $supplierSub = Supplier::select('fsupplierid', 'fsuppliercode', 'fsuppliername');

    $hdr = PenerimaanPembelianHeader::query()
      ->leftJoinSub($supplierSub, 's', function ($join) {
        $join->on('s.fsupplierid', '=', 'trstockmt.fsupplier');
      })
      ->leftJoin('mscabang as c', 'c.fcabangkode', '=', 'trstockmt.fbranchcode')
      ->leftJoin('mswh as w', 'w.fwhid', '=', 'trstockmt.ffrom')
      ->where('trstockmt.fstockmtno', $fstockmtno)
      ->first([
        'trstockmt.*',
        's.fsuppliername as supplier_name',
        'c.fcabangname as cabang_name',
        'w.fwhname as fwhnamen',
      ]);

    if (!$hdr) {
      return redirect()->back()->with('error', 'PO tidak ditemukan.');
    }

    $dt = PenerimaanPembelianDetail::query()
      ->leftJoin('msprd as p', 'p.fprdid', '=', 'trstockdt.fprdcode')
      ->where('trstockdt.fstockmtno', $fstockmtno)
      ->orderBy('trstockdt.fprdcode')
      ->get([
        'trstockdt.*',
        'p.fprdname as product_name',
        'p.fprdcode as product_code',
        'p.fminstock as stock',
        'trstockdt.fqtyremain',
      ]);

    $fmt = fn($d) => $d
      ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d F Y')
      : '-';

    return view('penerimaanbarang.print', [
      'hdr'          => $hdr,
      'dt'           => $dt,
      'fmt'          => $fmt,
      'company_name' => config('app.company_name', 'PT. DEMO VERSION'),
      'company_city' => config('app.company_city', 'Tangerang'),
    ]);
  }

  public function create(Request $request)
  {
    $suppliers = Supplier::orderBy('fsuppliername', 'asc')
      ->get(['fsupplierid', 'fsuppliername']);

    $warehouses = DB::table('mswh')
      ->select('fwhid', 'fwhcode', 'fwhname', 'fbranchcode', 'fnonactive')
      ->where('fnonactive', '0')              // hanya yang aktif
      ->orderBy('fwhcode')
      ->get();

    $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

    $branch = DB::table('mscabang')
      ->when(is_numeric($raw), fn($q) => $q->where('fcabangid', (int)$raw))
      ->when(
        !is_numeric($raw),
        fn($q) => $q->where('fcabangkode', $raw)->orWhere('fcabangname', $raw)
      )
      ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

    $canApproval = in_array('approvalpr', explode(',', session('user_restricted_permissions', '')));

    $fcabang = $branch->fcabangname ?? (string)$raw;
    $fbranchcode = $branch->fcabangkode ?? (string)$raw;

    $newtr_prh_code = $this->generatetr_poh_Code(now(), $fbranchcode);

    $products = Product::select(
      'fprdid',
      'fprdcode',
      'fprdname',
      'fsatuankecil',
      'fsatuanbesar',
      'fsatuanbesar2',
      'fminstock'
    )->orderBy('fprdname')->get();

    return view('penerimaanbarang.create', [
      'newtr_prh_code' => $newtr_prh_code,
      'warehouses' => $warehouses,
      'perms' => ['can_approval' => $canApproval],
      'suppliers' => $suppliers,
      'fcabang' => $fcabang,
      'fbranchcode' => $fbranchcode,
      'products' => $products,
      'filterSupplierId' => $request->query('filter_supplier_id'),
    ]);
  }

  public function store(Request $request)
  {
    // =========================
    // 1) VALIDASI INPUT
    // =========================
    $request->validate([
      'fstockmtno'      => ['nullable', 'string', 'max:100'],
      'fstockmtdate'    => ['required', 'date'],
      'fsupplier'       => ['required', 'string', 'max:30'],
      'ffrom'           => ['nullable', 'string', 'max:10'], // gudang ID
      'fket'            => ['nullable', 'string', 'max:50'],
      'fbranchcode'     => ['nullable', 'string', 'max:20'],

      'fitemcode'       => ['required', 'array', 'min:1'],
      'fitemcode.*'     => ['required', 'string', 'max:50'],

      'fsatuan'         => ['nullable', 'array'],
      'fsatuan.*'       => ['nullable', 'string', 'max:5'],

      'frefdtno'        => ['nullable', 'array'],
      'frefdtno.*'      => ['nullable', 'string', 'max:20'],

      'fnouref'         => ['nullable', 'array'],
      'fnouref.*'       => ['nullable', 'integer'],

      'fqty'            => ['required', 'array'],
      'fqty.*'          => ['numeric', 'min:0'],

      'fprice'          => ['required', 'array'],
      'fprice.*'        => ['numeric', 'min:0'],

      'fdesc'           => ['nullable', 'array'],
      'fdesc.*'         => ['nullable', 'string', 'max:500'],

      'fcurrency'       => ['nullable', 'string', 'max:5'],
      'frate'           => ['nullable', 'numeric', 'min:0'],
      'famountpopajak'  => ['nullable', 'numeric', 'min:0'], // PPN nominal
    ], [
      'fstockmtdate.required' => 'Tanggal transaksi wajib diisi.',
      'fsupplier.required'    => 'Supplier wajib diisi.',
      'fitemcode.required'    => 'Minimal 1 item.',
      'fsatuan.*.max'         => 'Satuan di salah satu baris tidak boleh lebih dari 5 karakter.'
    ]);

    // =========================
    // 2) HEADER FIELDS
    // =========================
    $fstockmtno   = trim((string)$request->input('fstockmtno'));
    $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
    $fsupplier    = trim((string)$request->input('fsupplier'));
    $ffrom        = $request->input('fwhid'); // Gudang ID
    $fket         = trim((string)$request->input('fket', ''));
    $fbranchcode  = $request->input('fbranchcode');

    $fcurrency    = $request->input('fcurrency', 'IDR');
    $frate        = (float)$request->input('frate', 1);
    if ($frate <= 0) $frate = 1;

    $ppnAmount    = (float)$request->input('famountpopajak', 0); // PPN Nominal

    $userid       = auth('sysuser')->user()->fsysuserid ?? 'admin';
    $now          = now();

    // =========================
    // 3) & 4) DETAIL ARRAYS & RAKIT DETAIL + HITUNG SUBTOTAL
    // =========================
    $codes        = $request->input('fitemcode', []);
    $satuans      = $request->input('fsatuan', []);
    $refdtno      = $request->input('frefdtno', []);
    $nourefs      = $request->input('fnouref', []);
    $qtys         = $request->input('fqty', []);
    $prices       = $request->input('fprice', []);
    $descs        = $request->input('fdesc', []);

    $rowsDt       = [];
    $subtotal     = 0.0;
    $rowCount     = count($codes);

    // Ambil referensi master produk untuk fallback satuan
    $uniqueCodes = array_values(array_unique(array_filter(array_map(fn($c) => trim((string)$c), $codes))));
    $prodMeta = DB::table('msprd')
      ->whereIn('fprdcode', $uniqueCodes)
      ->get(['fprdid', 'fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'])
      ->keyBy('fprdcode');

    $pickDefaultSat = function ($meta) {
      if (!$meta) return '';
      foreach (['fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'] as $k) {
        $v = trim((string)($meta->$k ?? ''));
        if ($v !== '') return mb_substr($v, 0, 5);
      }
      return '';
    };

    for ($i = 0; $i < $rowCount; $i++) {
      $code  = trim((string)($codes[$i]   ?? ''));
      $sat   = trim((string)($satuans[$i] ?? ''));
      $rref  = trim((string)($refdtno[$i] ?? ''));
      $rnour = $nourefs[$i] ?? null;
      $qty   = (float)($qtys[$i]   ?? 0);
      $price = (float)($prices[$i] ?? 0);
      $desc  = (string)($descs[$i] ?? '');

      if ($code === '' || $qty <= 0) continue;

      $meta = $prodMeta[$code] ?? null;
      if (!$meta) continue;

      $prdId = $meta->fprdid;

      if ($sat === '') {
        $sat = $pickDefaultSat($meta);
      }
      $sat = mb_substr($sat, 0, 5);
      if ($sat === '') continue;

      $amount = $qty * $price;
      $subtotal += $amount;

      $rowsDt[] = [
        'fprdcode'       => $prdId,
        'frefdtno'       => $rref,
        'fqty'           => $qty,
        'fqtyremain'     => $qty,
        'fprice'         => $price,
        'fprice_rp'      => $price * $frate,
        'ftotprice'      => $amount,
        'ftotprice_rp'   => $amount * $frate,
        'fuserid'        => $userid,
        'fdatetime'      => $now,
        'fketdt'         => '',
        'fcode'          => '0',
        'fnouref'        => $rnour !== null ? (int)$rnour : null,
        'frefso'         => null,
        'fdesc'          => $desc,
        'fsatuan'        => $sat,
        'fqtykecil'      => $qty,
        'fclosedt'       => '0',
        'fdiscpersen'    => 0,
        'fbiaya'         => 0,
        // Kolom header akan diisi di dalam transaksi
      ];
    }

    if (empty($rowsDt)) {
      return back()->withInput()->withErrors([
        'detail' => 'Minimal satu item valid (Kode, Satuan, Qty > 0).'
      ]);
    }

    $grandTotal = $subtotal + $ppnAmount;

    // =========================
    // 5) TRANSAKSI DB
    // =========================
    DB::transaction(function () use (
      $fstockmtdate,
      $fsupplier,
      $ffrom,
      $fket,
      $fbranchcode,
      $fcurrency,
      $frate,
      $userid,
      $now,
      &$fstockmtno,
      &$rowsDt,
      $subtotal,
      $ppnAmount,
      $grandTotal
    ) {
      // ---- 5.1. Generate fstockmtno dan kodeCabang ----
      $kodeCabang = null;
      if ($fbranchcode !== null) {
        $needle = trim((string)$fbranchcode);
        if ($needle !== '') {
          if (is_numeric($needle)) {
            $kodeCabang = DB::table('mscabang')->where('fcabangid', (int)$needle)->value('fcabangkode');
          } else {
            $kodeCabang = DB::table('mscabang')->whereRaw('LOWER(fcabangkode)=LOWER(?)', [$needle])->value('fcabangkode')
              ?: DB::table('mscabang')->whereRaw('LOWER(fcabangname)=LOWER(?)', [$needle])->value('fcabangkode');
          }
        }
      }
      if (!$kodeCabang) $kodeCabang = 'NA';

      $yy = $fstockmtdate->format('y');
      $mm = $fstockmtdate->format('m');
      $fstockmtcode = 'RCV';

      if (empty($fstockmtno)) {
        $prefix = sprintf('%s.%s.%s.%s.', $fstockmtcode, $kodeCabang, $yy, $mm);

        $lockKey = crc32('STOCKMT|' . $fstockmtcode . '|' . $kodeCabang . '|' . $fstockmtdate->format('Y-m'));
        DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

        $last = DB::table('trstockmt')
          ->where('fstockmtno', 'like', $prefix . '%')
          ->selectRaw("MAX(CAST(split_part(fstockmtno, '.', 5) AS int)) AS lastno")
          ->value('lastno');

        $next = (int)$last + 1;
        $fstockmtno = $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
      }

      // ---- 5.2. INSERT HEADER & DETAIL INVENTORY (trstockmt & trstockdt) ----
      $masterData = [
        'fstockmtno'       => $fstockmtno,
        'fstockmtcode'     => $fstockmtcode,
        'fstockmtdate'     => $fstockmtdate,
        'fprdout'          => '0',
        'fsupplier'        => $fsupplier,
        'fcurrency'        => $fcurrency,
        'frate'            => $frate,
        'famount'          => round($subtotal, 2),
        'famount_rp'       => round($subtotal * $frate, 2),
        'famountpajak'     => round($ppnAmount, 2),
        'famountpajak_rp'  => round($ppnAmount * $frate, 2),
        'famountmt'        => round($grandTotal, 2),
        'famountmt_rp'     => round($grandTotal * $frate, 2),
        'famountremain'    => round($grandTotal, 2),
        'famountremain_rp' => round($grandTotal * $frate, 2),
        'frefno'           => null, // Diisi jika ada referensi lain
        'frefpo'           => null,
        'ftrancode'        => null,
        'ffrom'            => $ffrom,
        'fto'              => null,
        'fkirim'           => null,
        'fprdjadi'         => null,
        'fqtyjadi'         => null,
        'fket'             => $fket,
        'fuserid'          => $userid,
        'fdatetime'        => $now,
        'fsalesman'        => null,
        'fjatuhtempo'      => null,
        'fprint'           => 0,
        'fsudahtagih'      => '0',
        'fbranchcode'      => $kodeCabang,
        'fdiscount'        => 0,
      ];

      $newStockMasterId = DB::table('trstockmt')->insertGetId($masterData, 'fstockmtid');

      if (!$newStockMasterId) {
        throw new Exception("Gagal menyimpan data master (header).");
      }

      // INSERT DETAIL (trstockdt)
      $lastNouRef = (int) DB::table('trstockdt')
        ->where('fstockmtid', $newStockMasterId)
        ->max('fnouref');
      $nextNouRef = $lastNouRef + 1;

      foreach ($rowsDt as &$r) {
        $r['fstockmtid']   = $newStockMasterId;
        $r['fstockmtcode'] = $fstockmtcode;
        $r['fstockmtno']   = $fstockmtno;

        if (!isset($r['fnouref']) || $r['fnouref'] === null) {
          $r['fnouref'] = $nextNouRef++;
        }
      }
      unset($r);

      DB::table('trstockdt')->insert($rowsDt);

      // =================================================================
      // ---- 5.3. AKUNTANSI JURNAL (jurnalmt & jurnaldt) ----
      // =================================================================

      // 1. Definisikan Kode Akun (GANTI DENGAN KODE AKUN GL ASLI ANDA)
      $INVENTORY_ACCOUNT_CODE = '11400'; // Contoh: Persediaan Barang Dagang
      $PPN_IN_ACCOUNT_CODE    = '11500'; // Contoh: PPN Masukan
      $PAYABLE_ACCOUNT_CODE   = '21100'; // Contoh: Hutang Dagang

      // 2. Generate Nomor Jurnal
      $fjurnaltype = 'JV';
      $jurnalPrefix = sprintf('%s.%s.%s.%s.', $fjurnaltype, $kodeCabang, $yy, $mm);

      $jurnalLockKey = crc32('JURNAL|' . $fjurnaltype . '|' . $kodeCabang . '|' . $fstockmtdate->format('Y-m'));
      DB::statement('SELECT pg_advisory_xact_lock(?)', [$jurnalLockKey]);

      $lastJurnalNo = DB::table('jurnalmt')
        ->where('fjurnalno', 'like', $jurnalPrefix . '%')
        ->selectRaw("MAX(CAST(split_part(fjurnalno, '.', 5) AS int)) AS lastno")
        ->value('lastno');

      $nextJurnalNo = (int)$lastJurnalNo + 1;
      $fjurnalno = $jurnalPrefix . str_pad((string)$nextJurnalNo, 4, '0', STR_PAD_LEFT);

      // 3. INSERT JURNAL HEADER (jurnalmt)
      $jurnalHeader = [
        'fbranchcode' => $kodeCabang,
        'fjurnalno'   => $fjurnalno,
        'fjurnaltype' => $fjurnaltype,
        'fjurnaldate' => $fstockmtdate,
        'fjurnalnote' => 'Jurnal Penerimaan Barang ' . $fstockmtno . ' dari Supplier: ' . $fsupplier,
        'fbalance'    => round($grandTotal, 2),
        'fbalance_rp' => round($grandTotal * $frate, 2),
        'fdatetime'   => $now,
        'fuserid'     => $userid,
      ];

      Log::debug('JURNAL HEADER INSERT:', $jurnalHeader); // Debugging

      $newJurnalMasterId = DB::table('jurnalmt')->insertGetId($jurnalHeader, 'fjurnalmtid');

      if (!$newJurnalMasterId) {
        throw new Exception("Gagal menyimpan data jurnal header.");
      }

      // 4. INSERT JURNAL DETAIL (jurnaldt)
      $jurnalDetails = [];
      $flineno = 1;

      // --- DEBIT: Persediaan (Nilai Subtotal) ---
      $jurnalDetails[] = [
        'fjurnalmtid'  => $newJurnalMasterId,
        'fbranchcode'  => $kodeCabang,
        'fjurnaltype'  => $fjurnaltype,
        'fjurnalno'    => $fjurnalno,
        'flineno'      => $flineno++,
        'faccount'     => $INVENTORY_ACCOUNT_CODE,
        'fdk'          => 'D',
        'fsubaccount'  => $fsupplier,
        'frefno'       => $fstockmtno,
        'frate'        => $frate,
        'famount'      => round($subtotal, 2),
        'famount_rp'   => round($subtotal * $frate, 2),
        'faccountnote' => 'Persediaan Barang Dagang ' . $fstockmtno,
        'fuserid'      => $userid,
        'fdatetime'    => $now,
      ];

      // --- DEBIT: PPN Masukan (Jika ada PPN) ---
      if ($ppnAmount > 0) {
        $jurnalDetails[] = [
          'fjurnalmtid'  => $newJurnalMasterId,
          'fbranchcode'  => $kodeCabang,
          'fjurnaltype'  => $fjurnaltype,
          'fjurnalno'    => $fjurnalno,
          'flineno'      => $flineno++,
          'faccount'     => $PPN_IN_ACCOUNT_CODE,
          'fdk'          => 'D',
          'fsubaccount'  => null,
          'frefno'       => $fstockmtno,
          'frate'        => $frate,
          'famount'      => round($ppnAmount, 2),
          'famount_rp'   => round($ppnAmount * $frate, 2),
          'faccountnote' => 'PPN Masukan ' . $fstockmtno,
          'fuserid'      => $userid,
          'fdatetime'    => $now,
        ];
      }

      // --- KREDIT: Hutang Dagang (Nilai Grand Total) ---
      $jurnalDetails[] = [
        'fjurnalmtid'  => $newJurnalMasterId,
        'fbranchcode'  => $kodeCabang,
        'fjurnaltype'  => $fjurnaltype,
        'fjurnalno'    => $fjurnalno,
        'flineno'      => $flineno++,
        'faccount'     => $PAYABLE_ACCOUNT_CODE,
        'fdk'          => 'K',
        'fsubaccount'  => $fsupplier,
        'frefno'       => $fstockmtno,
        'frate'        => $frate,
        'famount'      => round($grandTotal, 2),
        'famount_rp'   => round($grandTotal * $frate, 2),
        'faccountnote' => 'Hutang Dagang Supplier ' . $fsupplier . ' (Total Pembelian)',
        'fuserid'      => $userid,
        'fdatetime'    => $now,
      ];

      Log::debug('JURNAL DETAIL INSERT:', $jurnalDetails); // Debugging

      DB::table('jurnaldt')->insert($jurnalDetails);
    });

    // =================================================================

    return redirect()
      ->route('penerimaanbarang.create')
      ->with('success', "Transaksi {$fstockmtno} tersimpan.");
  }

  public function edit(Request $request, $fstockmtid)
  {
    $suppliers = Supplier::orderBy('fsuppliername', 'asc')
      ->get(['fsupplierid', 'fsuppliername']);

    $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

    $branch = DB::table('mscabang')
      ->when(is_numeric($raw), fn($q) => $q->where('fcabangid', (int) $raw))
      ->when(!is_numeric($raw), fn($q) => $q
        ->where('fcabangkode', $raw)
        ->orWhere('fcabangname', $raw))
      ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

    $warehouses = DB::table('mswh')
      ->select('fwhid', 'fwhcode', 'fwhname', 'fbranchcode', 'fnonactive')
      ->where('fnonactive', '0') // hanya yang aktif
      ->orderBy('fwhcode')
      ->get();

    $fcabang     = $branch->fcabangname ?? (string) $raw;
    $fbranchcode = $branch->fcabangkode ?? (string) $raw;

    // 1. Ambil data Header (trstockmt) DAN relasi Details (trstockdt)
    // Biarkan query ini. Sekarang $fstockmtid di sini adalah integer (misal: 8)
    $penerimaanbarang = PenerimaanPembelianHeader::with([
      'details' => function ($query) {
        $query
          // 2. Join ke msprd berdasarkan ID
          ->join('msprd', 'msprd.fprdid', '=', 'trstockdt.fprdcode')
          // 3. Select kolom yang dibutuhkan
          ->select(
            'trstockdt.*', // Ambil semua kolom dari tabel detail
            'msprd.fprdname', // Ambil nama produk
            'msprd.fprdcode as fitemcode_text' // Ambil KODE string produk
          )
          ->orderBy('trstockdt.fstockdtid', 'asc');
      }
    ])
      ->findOrFail($fstockmtid); // Temukan header berdasarkan $fstockmtid dari URL


    // 4. Map the data for savedItems (sudah menggunakan data yang benar)
    $savedItems = $penerimaanbarang->details->map(function ($d) {
      return [
        'uid'       => $d->fstockdtid,
        'fitemcode' => $d->fitemcode_text ?? '',
        'fitemname' => $d->fprdname ?? '',
        'fsatuan'   => $d->fsatuan ?? '',
        'fprno'     => $d->frefpr ?? '-',
        'frefpr'    => $d->frefpr ?? null,
        'fpono'     => $d->fpono ?? null,
        'famountponet' => $d->famountponet ?? null,
        'famountpo' => $d->famountpo ?? null,
        'frefdtno'  => $d->frefdtno ?? null,
        'fnouref'   => $d->fnouref ?? null,
        'fqty'      => (float)($d->fqty ?? 0),
        'fterima'   => (float)($d->fterima ?? 0),
        'fprice'    => (float)($d->fprice ?? 0),
        'fdisc'     => (float)($d->fdiscpersen ?? 0),
        'ftotal'    => (float)($d->ftotprice ?? 0),
        'fdesc'     => is_array($d->fdesc) ? implode(', ', $d->fdesc) : ($d->fdesc ?? ''),
        'fketdt'    => $d->fketdt ?? '',
        'units'     => [],
      ];
    })->values();

    // Sisa kode Anda sudah benar
    $selectedSupplierCode = $penerimaanbarang->fsupplier;

    $products = Product::select(
      'fprdid',
      'fprdcode',
      'fprdname',
      'fsatuankecil',
      'fsatuanbesar',
      'fsatuanbesar2',
      'fminstock'
    )->orderBy('fprdname')->get();

    $productMap = $products->mapWithKeys(function ($p) {
      return [
        $p->fprdcode => [
          'name'  => $p->fprdname,
          'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
          'stock' => $p->fminstock ?? 0,
        ],
      ];
    })->toArray();

    return view('penerimaanbarang.edit', [
      'suppliers'           => $suppliers,
      'selectedSupplierCode' => $selectedSupplierCode,
      'fcabang'            => $fcabang,
      'fbranchcode'        => $fbranchcode,
      'warehouses'         => $warehouses,
      'products'           => $products,
      'productMap'         => $productMap,
      'penerimaanbarang'    => $penerimaanbarang,
      'savedItems'         => $savedItems,
      'ppnAmount'          => (float) ($penerimaanbarang->famountpopajak ?? 0),
      'famountponet'       => (float) ($penerimaanbarang->famountponet ?? 0),
      'famountpo'          => (float) ($penerimaanbarang->famountpo ?? 0),
      'filterSupplierId' => $request->query('filter_supplier_id'),
    ]);
  }

  public function update(Request $request, $fstockmtid)
  {
    // =========================
    // 1) VALIDASI INPUT
    // =========================
    $request->validate([
      'fstockmtno'     => ['nullable', 'string', 'max:100'],
      'fstockmtdate'   => ['required', 'date'],
      'fsupplier'      => ['required', 'string', 'max:30'],
      'ffrom'          => ['nullable', 'integer', 'exists:mswh,fwhid'],
      'fket'           => ['nullable', 'string', 'max:50'],
      'fbranchcode'    => ['nullable', 'string', 'max:20'],
      'fitemcode'      => ['required', 'array', 'min:1'],
      'fitemcode.*'    => ['required', 'string', 'max:50'],
      'fsatuan'        => ['nullable', 'array'],
      'fsatuan.*'      => ['nullable', 'string', 'max:5'],
      'frefdtno'       => ['nullable', 'array'],
      'frefdtno.*'     => ['nullable', 'string', 'max:20'],
      'fnouref'        => ['nullable', 'array'],
      'fnouref.*'      => ['nullable', 'integer'],
      'fqty'           => ['required', 'array'],
      'fqty.*'         => ['numeric', 'min:0.01'],
      'fprice'         => ['required', 'array'],
      'fprice.*'       => ['numeric', 'min:0'],
      'fdesc'          => ['nullable', 'array'],
      'fdesc.*'        => ['nullable', 'string', 'max:500'],
      'fcurrency'      => ['nullable', 'string', 'max:5'],
      'frate'          => ['nullable', 'numeric', 'min:0'],
      'famountpopajak' => ['nullable', 'numeric', 'min:0'],
    ], [
      'fstockmtdate.required' => 'Tanggal transaksi wajib diisi.',
      'fsupplier.required'    => 'Supplier wajib diisi.',
      'fitemcode.required'    => 'Minimal 1 item.',
      'fqty.*.min'            => 'Qty tidak boleh 0.',
      'ffrom.exists'          => 'Gudang (ffrom/fwhid) tidak valid.',
      'ffrom.integer'         => 'Gudang (ffrom/fwhid) harus berupa angka ID.',
    ]);

    // =========================
    // 2) AMBIL DATA MASTER & HEADER
    // =========================
    $header = PenerimaanPembelianHeader::findOrFail($fstockmtid);

    $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
    $fsupplier    = trim((string)$request->input('fsupplier'));
    $ffrom        = $request->input('ffrom');
    $fket         = trim((string)$request->input('fket', ''));
    $fbranchcode  = $request->input('fbranchcode');
    $fcurrency    = $request->input('fcurrency', 'IDR');
    $frate        = (float)$request->input('frate', 1);
    if ($frate <= 0) $frate = 1;
    $ppnAmount    = (float)$request->input('famountpopajak', 0);
    $userid       = auth('sysuser')->user()->fsysuserid ?? 'admin';
    $now          = now();

    // =========================
    // 3) DETAIL ARRAYS
    // =========================
    $codes   = $request->input('fitemcode', []);
    $satuans = $request->input('fsatuan', []);
    $refdtno = $request->input('frefdtno', []);
    $nourefs = $request->input('fnouref', []);
    $qtys    = $request->input('fqty', []);
    $prices  = $request->input('fprice', []);
    $descs   = $request->input('fdesc', []);

    // =========================
    // 4) LOGIC PROD META & RAKIT DETAIL
    // =========================
    $uniqueCodes = array_values(array_unique(array_filter(array_map(fn($c) => trim((string)$c), $codes))));
    $prodMeta = DB::table('msprd')
      ->whereIn('fprdcode', $uniqueCodes)
      ->get(['fprdid', 'fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'])
      ->keyBy('fprdcode');

    $pickDefaultSat = function (?object $meta) use ($prodMeta): string {
      if (!$meta) return '';
      foreach (['fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'] as $k) {
        $v = trim((string)($meta->$k ?? ''));
        if ($v !== '') return mb_substr($v, 0, 5);
      }
      return '';
    };

    $rowsDt   = [];
    $subtotal = 0.0;
    $rowCount = count($codes);

    for ($i = 0; $i < $rowCount; $i++) {
      $code  = trim((string)($codes[$i]   ?? ''));
      $sat   = trim((string)($satuans[$i] ?? ''));
      $rref  = trim((string)($refdtno[$i] ?? ''));
      $rnour = $nourefs[$i] ?? null;
      $qty   = (float)($qtys[$i]   ?? 0);
      $price = (float)($prices[$i] ?? 0);
      $desc  = (string)($descs[$i]  ?? '');

      if ($code === '' || $qty <= 0) continue;

      $meta = $prodMeta[$code] ?? null;
      if (!$meta) continue;

      $prdId = $meta->fprdid;

      if ($sat === '') {
        $sat = $pickDefaultSat($meta);
      }
      $sat = mb_substr($sat, 0, 5);
      if ($sat === '') continue;

      $amount = $qty * $price;
      $subtotal += $amount;

      $rowsDt[] = [
        'fprdcode'       => $prdId,
        'frefdtno'       => $rref,
        'fqty'           => $qty,
        'fqtyremain'     => $qty,
        'fprice'         => $price,
        'fprice_rp'      => $price * $frate,
        'ftotprice'      => $amount,
        'ftotprice_rp'   => $amount * $frate,
        'fuserid'        => $userid,
        'fdatetime'      => $now, // Tetap gunakan fdatetime
        'fketdt'         => '',
        'fcode'          => '0',
        'fnouref'        => $rnour !== null ? (int)$rnour : null,
        'frefso'         => null,
        'fdesc'          => $desc,
        'fsatuan'        => $sat,
        'fqtykecil'      => $qty,
        'fclosedt'       => '0',
        'fdiscpersen'    => 0,
        'fbiaya'         => 0,
      ];
    }

    if (empty($rowsDt)) {
      return back()->withInput()->withErrors([
        'detail' => 'Minimal satu item valid (Kode, Satuan, Qty > 0).'
      ]);
    }

    $grandTotal = $subtotal + $ppnAmount;

    // =========================
    // 5) TRANSAKSI DB
    // =========================
    DB::transaction(function () use (
      $header,
      $fstockmtid,
      $fstockmtdate,
      $fsupplier,
      $ffrom,
      $fket,
      $fbranchcode,
      $fcurrency,
      $frate,
      $userid,
      $now,
      &$rowsDt,
      $subtotal,
      $ppnAmount,
      $grandTotal
    ) {

      // ---- 5.1. Cek Kode Cabang ----
      $kodeCabang = $header->fbranchcode;
      if ($fbranchcode !== null && $fbranchcode !== $header->fbranchcode) {
        $needle = trim((string)$fbranchcode);
        if ($needle !== '') {
          if (is_numeric($needle)) {
            $kodeCabang = DB::table('mscabang')->where('fcabangid', (int)$needle)->value('fcabangkode');
          } else {
            $kodeCabang = DB::table('mscabang')->whereRaw('LOWER(fcabangkode)=LOWER(?)', [$needle])->value('fcabangkode')
              ?: DB::table('mscabang')->whereRaw('LOWER(fcabangname)=LOWER(?)', [$needle])->value('fcabangkode');
          }
        }
        if (!$kodeCabang) $kodeCabang = 'NA';
      }

      // ---- 5.2. UPDATE HEADER: trstockmt ----
      $masterData = [
        'fstockmtdate'     => $fstockmtdate,
        'fsupplier'        => $fsupplier,
        'fcurrency'        => $fcurrency,
        'frate'            => $frate,
        'famount'          => round($subtotal, 2),
        'famount_rp'       => round($subtotal * $frate, 2),
        'famountpajak'     => round($ppnAmount, 2),
        'famountpajak_rp'  => round($ppnAmount * $frate, 2),
        'famountmt'        => round($grandTotal, 2),
        'famountmt_rp'     => round($grandTotal * $frate, 2),
        'famountremain'    => round($grandTotal, 2),
        'famountremain_rp' => round($grandTotal * $frate, 2),
        'ffrom'            => $ffrom,
        'fket'             => $fket,
        'fuserid'          => $userid,
        'fbranchcode'      => $kodeCabang,
      ];

      $header->update($masterData);

      // ---- 5.3. HAPUS DETAIL LAMA ----
      DB::table('trstockdt')->where('fstockmtid', $fstockmtid)->delete();

      // ---- 5.4. INSERT DETAIL BARU ----
      $fstockmtcode = $header->fstockmtcode;
      $fstockmtno   = $header->fstockmtno;
      $nextNouRef = 1;

      foreach ($rowsDt as &$r) {
        $r['fstockmtid']   = $fstockmtid;
        $r['fstockmtcode'] = $fstockmtcode;
        $r['fstockmtno']   = $fstockmtno;

        if (!isset($r['fnouref']) || $r['fnouref'] === null) {
          $r['fnouref'] = $nextNouRef++;
        }
      }
      unset($r);

      DB::table('trstockdt')->insert($rowsDt);
    });

    return redirect()
      ->route('penerimaanbarang.index')
      ->with('success', "Transaksi {$header->fstockmtno} berhasil diperbarui.");
  }

  public function destroy($fstockmtid)
  {
    $penerimaanbarang = PenerimaanPembelianHeader::findOrFail($fstockmtid);
    $penerimaanbarang->details()->delete();

    // 2. Baru hapus header
    $penerimaanbarang->delete();
    if (request()->wantsJson()) {
      return response()->json([
        'success' => true,
        'message' => 'Penerimaan Barang berhasil dihapus.'
      ]);
    }

    return redirect()
      ->route('penerimaanbarang.index')
      ->with('success', 'Penerimaan Barang Berhasil Dihapus.');
  }
}
