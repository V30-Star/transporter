<?php

namespace App\Http\Controllers;

use App\Models\Tr_prh;
use App\Models\Tr_prd;
use App\Models\Tr_poh;
use App\Models\Tr_pod;
use App\Models\Supplier;
use App\Models\PenerimaanPembelianHeader;
use App\Models\PenerimaanPembelianDetail;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Mail\ApprovalEmailPo;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;
use Illuminate\Validation\ValidationException;

class FakturPembelianController extends Controller
{
  public function index(Request $request)
  {
    // --- 1. PERMISSIONS ---
    $canCreate = in_array('createFakturPembelian', explode(',', session('user_restricted_permissions', '')));
    $canEdit   = in_array('updateFakturPembelian', explode(',', session('user_restricted_permissions', '')));
    $canDelete = in_array('deleteFakturPembelian', explode(',', session('user_restricted_permissions', '')));
    $canPrint  = in_array('printFakturPembelian', explode(',', session('user_restricted_permissions', '')));
    $showActionsColumn = $canEdit || $canDelete || $canPrint;

    $year = $request->query('year');
    $month = $request->query('month');

    // Ambil tahun-tahun yang tersedia dari data
    $availableYears = PenerimaanPembelianHeader::selectRaw('DISTINCT EXTRACT(YEAR FROM fdatetime) as year')
      ->where('fstockmtcode', 'TER')
      ->whereNotNull('fdatetime')
      ->orderByRaw('EXTRACT(YEAR FROM fdatetime) DESC')
      ->pluck('year');

    // --- 2. Handle Request AJAX dari DataTables ---
    if ($request->ajax()) {

      // Query dasar HANYA untuk 'TER' (Faktur)
      $query = PenerimaanPembelianHeader::where('fstockmtcode', 'TER');

      // Total records (dengan filter 'TER')
      $totalRecords = PenerimaanPembelianHeader::where('fstockmtcode', 'TER')->count();

      // Handle Search (cari di No. Faktur)
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

      // Total records setelah filter search
      $filteredRecords = (clone $query)->count();

      // Handle Sorting
      $orderColIdx = $request->input('order.0.column', 0);
      $orderDir = $request->input('order.0.dir', 'desc');

      $sortableColumns = ['fstockmtno', 'fstockmtdate', 'ftypebuy'];

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
        ->get(['fstockmtid', 'fstockmtno', 'fstockmtdate', 'ftypebuy']);

      // Format Data - HANYA RETURN DATA MENTAH
      $data = $records->map(function ($row) {
        return [
          'fstockmtid'   => $row->fstockmtid,
          'fstockmtno'   => $row->fstockmtno,
          'fstockmtdate' => Carbon::parse($row->fstockmtdate)->format('d/m/Y'),
          'ftypebuy'     => $row->ftypebuy
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
    return view('fakturpembelian.index', compact(
      'canCreate',
      'canEdit',
      'canDelete',
      'canPrint',
      'showActionsColumn',
      'availableYears',
      'year',
      'month'
    ));
  }

  public function pickable(Request $request)
  {
    $search   = trim((string) $request->get('search', ''));
    $perPage  = (int) $request->get('per_page', 10);

    // Ambil dari tr_prh dengan kondisi yang kamu minta
    $query = Tr_prh::query()
      ->select([
        'tr_prh.fprid',
        'tr_prh.fprno',
        'tr_prh.fsupplier',
        'tr_prh.fprdate',
      ])
      ->where('tr_prh.fapproval', 2)
      ->where('tr_prh.fprdin', 0);

    // Optional search: fprno / fsupplier / tanggal
    if ($search !== '') {
      // PostgreSQL -> ILIKE, MySQL -> LIKE (ganti sesuai DB)
      $likeOp = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
      $query->where(function ($q) use ($search, $likeOp) {
        $q->where('tr_prh.fprno', $likeOp, "%{$search}%")
          ->orWhere('tr_prh.fsupplier', $likeOp, "%{$search}%")
          ->orWhereRaw("TO_CHAR(tr_prh.fprdate, 'YYYY-MM-DD HH24:MI:SS') {$likeOp} ?", ["%{$search}%"]);
      });
    }

    // Urutan paling baru
    $query->orderByDesc('tr_prh.fprdate')
      ->orderByDesc('tr_prh.fprid');

    $paginated = $query->paginate($perPage)->withQueryString();

    // Format JSON agar cocok dengan kode Alpine kamu
    $rows = collect($paginated->items())->map(function ($t) {
      return [
        'fprid'     => $t->fprid,
        'fprno'     => $t->fprno,
        'fsupplier' => trim($t->fsupplier ?? ''),
        'fprdate'   => $t->fprdate ? \Carbon\Carbon::parse($t->fprdate)->format('Y-m-d H:i:s') : 'No Date',
        // siapkan URL jika dibutuhkan
        'items_url' => route('tr_poh.items', $t->fprid),
      ];
    });

    return response()->json([
      'data'  => $rows,
      'links' => [
        'prev'         => $paginated->previousPageUrl(),
        'next'         => $paginated->nextPageUrl(),
        'current_page' => $paginated->currentPage(),
        'last_page'    => $paginated->lastPage(),
        'total'        => $paginated->total(),
      ],
      // compat untuk key yang sudah kamu baca di frontend
      'current_page' => $paginated->currentPage(),
      'last_page'    => $paginated->lastPage(),
      'total'        => $paginated->total(),
    ]);
  }

  public function items($id)
  {
    // Ambil data header PR berdasarkan fprid
    $header = Tr_prh::where('fprid', $id)->firstOrFail();

    // PERBAIKAN: Gunakan fprid (integer) bukan fprno (varchar)
    $items = Tr_prd::where('tr_prd.fprnoid', $header->fprid) // <- Gunakan fprid
      ->leftJoin('msprd as m', 'm.fprdid', '=', 'tr_prd.fprdcode')
      ->select([
        'tr_prd.fprdid as frefdtno',
        'tr_prd.fprnoid as fnouref',
        'tr_prd.fprdcode as fitemcode',
        'm.fprdname as fitemname',
        'tr_prd.fqty',
        'tr_prd.fsatuan as fsatuan',
        'tr_prd.fprnoid',
        'tr_prd.ftotprice as fharga',
        DB::raw('0::numeric as fdiskon')
      ])
      ->orderBy('tr_prd.fprdcode')
      ->get();

    return response()->json([
      'header' => [
        'fprid'     => $header->fprid,
        'fprno'     => $header->fprno,
        'fsupplier' => trim($header->fsupplier ?? ''),
        'fprdate'   => optional($header->fprdate)->format('Y-m-d H:i:s'),
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

    return view('fakturpembelian.print', [
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
      ->where('fnonactive', '0')
      ->orderBy('fwhcode')
      ->get();

    $accounts = DB::table('account')
      ->select('faccid', 'faccount', 'faccname', 'fnonactive')
      ->where('fnonactive', '0')
      ->orderBy('account')
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

    return view('fakturpembelian.create', [
      'newtr_prh_code' => $newtr_prh_code,
      'perms' => ['can_approval' => $canApproval],
      'warehouses' => $warehouses,
      'accounts' => $accounts,
      'suppliers'      => $suppliers,
      'fcabang' => $fcabang,
      'fbranchcode' => $fbranchcode,
      'products' => $products,
      'filterSupplierId' => $request->query('filter_supplier_id'),
    ]);
  }

  public function store(Request $request)
  {
    try {
      // VALIDATION
      $request->validate([
        'fstockmtno' => ['nullable', 'string', 'max:100'],
        'fstockmtdate' => ['required', 'date'],
        'fsupplier' => ['required', 'string', 'max:30'],
        'ffrom' => ['nullable', 'string', 'max:10'],
        'fket' => ['nullable', 'string', 'max:50'],
        'fbranchcode' => ['nullable', 'string', 'max:20'],
        'faccid' => ['nullable', 'integer'],
        'fitemcode' => ['required', 'array', 'min:1'],
        'fitemcode.*' => ['required', 'string', 'max:50'],
        'fsatuan' => ['nullable', 'array'],
        'fsatuan.*' => ['nullable', 'string', 'max:5'],
        'frefdtno' => ['nullable', 'array'],
        'frefdtno.*' => ['nullable', 'string', 'max:20'],
        'fnouref' => ['nullable', 'array'],
        'fnouref.*' => ['nullable', 'integer'],
        'fqty' => ['required', 'array'],
        'fqty.*' => ['numeric', 'min:0'],
        'fprice' => ['required', 'array'],
        'fprice.*' => ['numeric', 'min:0'],
        'fdiscpersen' => ['nullable', 'array'],
        'fdiscpersen.*' => ['numeric', 'min:0'],
        'fbiaya' => ['required', 'array'],
        'fbiaya.*' => ['numeric', 'min:0'],
        'fdesc' => ['nullable', 'array'],
        'fdesc.*' => ['nullable', 'string', 'max:500'],
        'ftempohr' => ['nullable', 'integer'],
        'ftypebuy' => ['nullable', 'integer'],
        'frefno' => ['nullable', 'integer'],
        'frefpo' => ['nullable', 'integer'],
        'fcurrency' => ['nullable', 'string', 'max:5'],
        'frate' => ['nullable', 'numeric', 'min:0'],
        'famountpopajak' => ['nullable', 'numeric', 'min:0'],
      ], [
        'fstockmtdate.required' => 'Tanggal transaksi wajib diisi.',
        'fsupplier.required' => 'Supplier wajib diisi.',
        'fitemcode.required' => 'Minimal 1 item.',
        'fsatuan.*.max' => 'Satuan di salah satu baris tidak boleh lebih dari 5 karakter.',
      ]);

      // HEADER FIELDS
      $fstockmtno = trim((string)$request->input('fstockmtno'));
      $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
      $fsupplier = trim((string)$request->input('fsupplier'));
      $ffrom = $request->input('fwhid');
      $fket = trim((string)$request->input('fket', ''));
      $fbranchcode = $request->input('fbranchcode');
      $faccid = $request->input('faccid');
      $ftempohr = $request->input('ftempohr');
      $ftypebuy = $request->input('ftypebuy');
      $frefno = $request->input('frefno');
      $frefpo = $request->input('frefpo');

      $fcurrency = $request->input('fcurrency', 'IDR');
      $frate = (float)$request->input('frate', 1);
      if ($frate <= 0) $frate = 1;

      $userid = auth('sysuser')->user()->fsysuserid ?? 'admin';
      $now = now();

      // DETAIL ARRAYS
      $codes = $request->input('fitemcode', []);
      $satuans = $request->input('fsatuan', []);
      $refdtno = $request->input('frefdtno', []);
      $nourefs = $request->input('fnouref', []);
      $qtys = $request->input('fqty', []);
      $prices = $request->input('fprice', []);
      $biayas = $request->input('fbiaya', []);
      $discs = $request->input('fdiscpersen', []);
      $descs = $request->input('fdesc', []);

      $subtotal = (float) $request->input('famount', 0);
      $ppnAmount = (float) $request->input('famountpajak', 0);
      $grandTotal = (float) $request->input('famountmt', 0);

      // LOAD PRODUCT METADATA
      $uniqueCodes = array_values(array_unique(array_filter(array_map(fn($c) => trim((string)$c), $codes))));

      $prodMeta = DB::table('msprd')
        ->whereIn('fprdcode', $uniqueCodes)
        ->get(['fprdid', 'fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'])
        ->keyBy('fprdcode');

      $pickDefaultSat = function (?object $meta): string {
        if (!$meta) return '';
        foreach (['fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'] as $k) {
          $v = trim((string)($meta->$k ?? ''));
          if ($v !== '') return mb_substr($v, 0, 5);
        }
        return '';
      };

      // BUILD DETAIL ROWS
      $rowsDt = [];
      $subtotal = 0.0;

      for ($i = 0; $i < count($codes); $i++) {
        $code = trim((string)($codes[$i] ?? ''));
        $sat = trim((string)($satuans[$i] ?? ''));
        $rref = trim((string)($refdtno[$i] ?? ''));
        $rnour = $nourefs[$i] ?? null;
        $qty = (float)($qtys[$i] ?? 0);
        $price = (float)($prices[$i] ?? 0);
        $biaya = (float)($biayas[$i] ?? 0);
        $discP = (float)($discs[$i] ?? 0);
        $desc = (string)($descs[$i] ?? '');

        if ($code === '' || $qty <= 0) continue;

        $meta = $prodMeta[$code] ?? null;
        if (!$meta) continue;

        $prdId = $meta->fprdid;

        if ($sat === '') {
          $sat = $pickDefaultSat($meta);
        }
        $sat = mb_substr($sat, 0, 5);

        if ($sat === '') continue;

        $priceGross = $price;
        $priceNet = $priceGross * (1 - ($discP / 100));
        $amount = $qty * $priceNet;
        $subtotal += $amount;

        $rowsDt[] = [
          'fprdcode' => $prdId,
          'frefdtno' => $rref,
          'fqty' => $qty,
          'fqtyremain' => $qty,
          'fprice' => $price,
          'fbiaya' => $biaya,
          'fprice_rp' => $price * $frate,
          'ftotprice' => $amount,
          'ftotprice_rp' => $amount * $frate,
          'fuserid' => $userid,
          'fdatetime' => $now,
          'fketdt' => '',
          'fcode' => '0',
          'fnouref' => $rnour !== null ? (int)$rnour : null,
          'frefso' => null,
          'fdesc' => $desc,
          'fdiscpersen' => (string)$discP,
          'fsatuan' => $sat,
          'fqtykecil' => $qty,
          'fclosedt' => '0',
        ];
      }

      if (empty($rowsDt)) {
        return back()->withInput()->withErrors([
          'detail' => 'Minimal satu item valid (Kode, Satuan, Qty > 0).'
        ]);
      }

      $grandTotal = $subtotal + $ppnAmount;

      // DATABASE TRANSACTION
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
        $ftempohr,
        $ftypebuy,
        $frefno,
        $frefpo,
        &$fstockmtno,
        &$rowsDt,
        $subtotal,
        $ppnAmount,
        $grandTotal,
        $faccid
      ) {
        // BRANCH CODE RESOLUTION
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

        // GENERATE DOCUMENT NUMBER
        $yy = $fstockmtdate->format('y');
        $mm = $fstockmtdate->format('m');
        $fstockmtcode = 'TER';

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

        // INSERT HEADER
        $masterData = [
          'fstockmtno' => $fstockmtno,
          'fstockmtcode' => $fstockmtcode,
          'fstockmtdate' => $fstockmtdate,
          'fprdout' => '0',
          'fsupplier' => $fsupplier,
          'fcurrency' => $fcurrency,
          'frate' => $frate,
          'famount' => round($subtotal, 2),
          'famount_rp' => round($subtotal * $frate, 2),
          'famountpajak' => round($ppnAmount, 2),
          'famountpajak_rp' => round($ppnAmount * $frate, 2),
          'famountmt' => round($grandTotal, 2),
          'famountmt_rp' => round($grandTotal * $frate, 2),
          'famountremain' => round($grandTotal, 2),
          'famountremain_rp' => round($grandTotal * $frate, 2),
          'frefno' => $frefno,
          'frefpo' => $frefpo,
          'ftrancode' => null,
          'ffrom' => $ffrom,
          'fto' => null,
          'fkirim' => null,
          'fprdjadi' => $faccid,
          'fqtyjadi' => null,
          'fket' => $fket,
          'fuserid' => $userid,
          'fdatetime' => $now,
          'fsalesman' => null,
          'fjatuhtempo' => null,
          'fprint' => 0,
          'fsudahtagih' => '0',
          'fbranchcode' => $kodeCabang,
          'fdiscount' => 0,
          'ftempohr' => $ftempohr,
          'ftypebuy' => $ftypebuy,
        ];

        $newStockMasterId = DB::table('trstockmt')->insertGetId($masterData, 'fstockmtid');

        // INSERT DETAILS
        $lastNouRef = (int) DB::table('trstockdt')
          ->where('fstockmtid', $newStockMasterId)
          ->max('fnouref');
        $nextNouRef = $lastNouRef + 1;

        foreach ($rowsDt as &$r) {
          $r['fstockmtid'] = $newStockMasterId;
          $r['fstockmtcode'] = $fstockmtcode;
          $r['fstockmtno'] = $fstockmtno;

          if (!isset($r['fnouref']) || $r['fnouref'] === null) {
            $r['fnouref'] = $nextNouRef++;
          }
        }
        unset($r);

        DB::table('trstockdt')->insert($rowsDt);
      });

      return redirect()
        ->route('fakturpembelian.create')
        ->with('success', "Faktur Pembelian {$fstockmtno} berhasil disimpan.");
    } catch (\Exception $e) {
      return back()
        ->withInput()
        ->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
    }
  }

  public function edit(Request $request, $fstockmtid)
  {
    $suppliers = Supplier::orderBy('fsuppliername', 'asc')
      ->get(['fsupplierid', 'fsuppliername']);

    // 1. PINDAHKAN INI KE ATAS
    // Ambil data Header (trstockmt) DULU
    $fakturpembelian = PenerimaanPembelianHeader::with([
      'details' => function ($query) {
        $query
          ->join('msprd', 'msprd.fprdid', '=', 'trstockdt.fprdcode')
          ->select(
            'trstockdt.*',
            'msprd.fprdname',
            'msprd.fprdcode as fitemcode_text'
          )
          ->orderBy('trstockdt.fstockdtid', 'asc');
      }
    ])
      ->findOrFail($fstockmtid); // Temukan header berdasarkan $fstockmtid

    // 2. Ambil kode akun yang tersimpan dari faktur
    $savedAccountCode = $fakturpembelian->fprdjadi;

    // 3. UBAH QUERY INI: Gunakan $savedAccountCode
    $accounts = DB::table('account')
      ->select('faccid', 'faccount', 'faccname', 'fnonactive')
      ->where('fnonactive', '0') // Ambil semua yang aktif
      ->orderBy('faccount') // <-- Perbaikan nama kolom
      ->get();

    // --- Sisa kode Anda ---
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

    $fcabang = $branch->fcabangname ?? (string) $raw;
    $fbranchcode = $branch->fcabangkode ?? (string) $raw;

    // (Query $fakturpembelian sudah dipindah ke atas)

    // 4. Map the data for savedItems
    $savedItems = $fakturpembelian->details->map(function ($d) {
      return [
        'uid' => $d->fstockdtid,
        'fitemcode' => $d->fitemcode_text ?? '',
        'fitemname' => $d->fprdname ?? '',
        'fsatuan' => $d->fsatuan ?? '',
        'fprno' => $d->frefpr ?? '-',
        'frefpr' => $d->frefpr ?? null,
        'fpono' => $d->fpono ?? null,
        'famountponet' => $d->famountponet ?? null,
        'famountpo' => $d->famountpo ?? null,
        'frefdtno' => $d->frefdtno ?? null,
        'fnouref' => $d->fnouref ?? null,
        'fqty' => (float)($d->fqty ?? 0),
        'fterima' => (float)($d->fterima ?? 0),
        'fprice' => (float)($d->fprice ?? 0),
        'fdiscpersen' => (float)($d->fdiscpersen ?? 0),
        'fbiaya' => (float)($d->fbiaya ?? 0),
        'ftotprice' => (float)($d->ftotprice ?? 0),
        'ftotal' => (float)($d->ftotprice ?? 0),
        'fdesc' => is_array($d->fdesc) ? implode(', ', $d->fdesc) : ($d->fdesc ?? ''),
        'fketdt' => $d->fketdt ?? '',
        'units' => [],
      ];
    })->values();

    $selectedSupplierCode = $fakturpembelian->fsupplier;

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
          'name' => $p->fprdname,
          'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
          'stock' => $p->fminstock ?? 0,
        ],
      ];
    })->toArray();

    return view('fakturpembelian.edit', [
      'suppliers' => $suppliers,
      'selectedSupplierCode' => $selectedSupplierCode,
      'fcabang' => $fcabang,
      'fbranchcode' => $fbranchcode,
      'warehouses' => $warehouses,
      'products' => $products,
      'accounts' => $accounts,
      'productMap' => $productMap,
      'fakturpembelian' => $fakturpembelian,
      'savedItems' => $savedItems,
      'ppnAmount' => (float) ($fakturpembelian->famountpopajak ?? 0),
      'famountponet' => (float) ($fakturpembelian->famountponet ?? 0),
      'famountpo' => (float) ($fakturpembelian->famountpo ?? 0),
      'filterSupplierId' => $request->query('filter_supplier_id'),
      'action' => 'edit'
    ]);
  }

  public function update(Request $request, $fstockmtid)
  {
    try {
      // VALIDASI
      $request->validate([
        'fstockmtno' => ['nullable', 'string', 'max:100'],
        'fstockmtdate' => ['required', 'date'],
        'fsupplier' => ['required', 'string', 'max:30'],
        'ffrom'          => ['nullable', 'integer', 'exists:mswh,fwhid'],
        'fket' => ['nullable', 'string', 'max:50'],
        'fbranchcode' => ['nullable', 'string', 'max:20'],
        'faccid' => ['nullable', 'integer'],
        'fitemcode' => ['required', 'array', 'min:1'],
        'fitemcode.*' => ['required', 'string', 'max:50'],
        'fsatuan' => ['nullable', 'array'],
        'fsatuan.*' => ['nullable', 'string', 'max:5'],
        'frefdtno' => ['nullable', 'array'],
        'frefdtno.*' => ['nullable', 'string', 'max:20'],
        'fnouref' => ['nullable', 'array'],
        'fnouref.*' => ['nullable', 'integer'],
        'fqty' => ['required', 'array'],
        'fqty.*' => ['numeric', 'min:0'],
        'fprice' => ['required', 'array'],
        'fprice.*' => ['numeric', 'min:0'],
        'fdiscpersen' => ['nullable', 'array'],
        'fdiscpersen.*' => ['nullable', 'numeric', 'min:0'],
        'fbiaya' => ['required', 'array'],
        'fbiaya.*' => ['nullable', 'numeric', 'min:0'],
        'fdesc' => ['nullable', 'array'],
        'fdesc.*' => ['nullable', 'string', 'max:500'],
        'fcurrency' => ['nullable', 'string', 'max:5'],
        'frate' => ['nullable', 'numeric', 'min:0'],
        'famountpopajak' => ['nullable', 'numeric', 'min:0'],
        'fjatuhtempo' => ['nullable', 'date'],
        'ftempohr' => ['nullable', 'integer'],
        'ftypebuy' => ['nullable', 'integer'],
        'frefno' => ['nullable', 'string'],
        'frefpo' => ['nullable', 'string'],
      ], [
        'fstockmtdate.required' => 'Tanggal transaksi wajib diisi.',
        'fsupplier.required' => 'Supplier wajib diisi.',
        'fitemcode.required' => 'Minimal 1 item.',
        'fsatuan.*.max' => 'Satuan di salah satu baris tidak boleh lebih dari 5 karakter.',
        'faccid.required_if' => 'Account wajib dipilih untuk tipe Non Stok.',
      ]);

      // 1. Muat header yang ada
      $header = PenerimaanPembelianHeader::findOrFail($fstockmtid);

      // HEADER FIELDS
      $fstockmtno = $header->fstockmtno;
      $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
      $fsupplier = trim((string)$request->input('fsupplier'));
      $ffrom        = $request->input('ffrom');
      $fket = trim((string)$request->input('fket', ''));
      $fbranchcode = $request->input('fbranchcode');
      $faccid = $request->input('faccid');
      $ftempohr = $request->input('ftempohr');
      $ftypebuy = $request->input('ftypebuy');

      $fcurrency = $request->input('fcurrency', 'IDR');
      $frate = (float)$request->input('frate', 1);
      if ($frate <= 0) $frate = 1;

      $ppnAmount = (float)$request->input('famountpajak', 0); // Ambil dari input 'famountpajak'
      $userid = auth('sysuser')->user()->fsysuserid ?? 'admin';
      $now = now();

      // DETAIL ARRAYS
      $codes = $request->input('fitemcode', []);
      $satuans = $request->input('fsatuan', []);
      $refdtno = $request->input('frefdtno', []);
      $nourefs = $request->input('fnouref', []);
      $qtys = $request->input('fqty', []);
      $prices = $request->input('fprice', []);
      $biayas = $request->input('fbiaya', []);
      $discs = $request->input('fdiscpersen', []);
      $descs = $request->input('fdesc', []);

      // LOAD PRODUCT METADATA
      $uniqueCodes = array_values(array_unique(array_filter(array_map(fn($c) => trim((string)$c), $codes))));

      $prodMeta = DB::table('msprd')
        ->whereIn('fprdcode', $uniqueCodes)
        ->get(['fprdid', 'fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'])
        ->keyBy('fprdcode');

      $pickDefaultSat = function (?object $meta): string {
        if (!$meta) return '';
        foreach (['fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'] as $k) {
          $v = trim((string)($meta->$k ?? ''));
          if ($v !== '') return mb_substr($v, 0, 5);
        }
        return '';
      };

      // BUILD DETAIL ROWS
      $rowsDt = [];
      $subtotal = 0.0;
      $rowCount = count($codes);

      for ($i = 0; $i < $rowCount; $i++) {
        $code = trim((string)($codes[$i] ?? ''));
        $sat = trim((string)($satuans[$i] ?? ''));
        $rref = trim((string)($refdtno[$i] ?? ''));
        $rnour = $nourefs[$i] ?? null;
        $qty = (float)($qtys[$i] ?? 0);
        $price = (float)($prices[$i] ?? 0);
        $biaya = (float)($biayas[$i] ?? 0);
        $discP = (float)($discs[$i] ?? 0);
        $desc = (string)($descs[$i] ?? '');

        if ($code === '' || $qty <= 0) {
          continue;
        }
        $meta = $prodMeta[$code] ?? null;
        if (!$meta) {
          continue;
        }
        $prdId = $meta->fprdid;
        if ($sat === '') {
          $sat = $pickDefaultSat($meta);
        }
        $sat = mb_substr($sat, 0, 5);
        if ($sat === '') {
          continue;
        }

        $priceGross = $price;
        $priceNet = $priceGross * (1 - ($discP / 100));
        $amount = $qty * $priceNet;
        $subtotal += $amount;

        $rowsDt[] = [
          'fprdcode' => $prdId,
          'frefdtno' => $rref,
          'fqty' => $qty,
          'fqtyremain' => $qty,
          'fprice' => $price,
          'fbiaya' => $biaya,
          'fprice_rp' => $price * $frate,
          'ftotprice' => $amount,
          'ftotprice_rp' => $amount * $frate,
          'fuserid' => $userid,
          'fdatetime' => $now,
          'fketdt' => '',
          'fcode' => '0',
          'fnouref' => $rnour !== null ? (int)$rnour : null,
          'frefso' => null,
          'fdesc' => $desc,
          'fdiscpersen' => (string)$discP,
          'fsatuan' => $sat,
          'fqtykecil' => $qty,
          'fclosedt' => '0',
        ];
      }

      if (empty($rowsDt)) {
        return back()->withInput()->withErrors([
          'detail' => 'Minimal satu item valid (Kode, Satuan, Qty > 0).'
        ]);
      }

      // Hitung ulang grand total berdasarkan data yang valid
      $grandTotal = $subtotal + $ppnAmount;

      // DATABASE TRANSACTION
      DB::transaction(function () use (
        $request, // <-- Tambahkan request untuk ambil field lain
        $header,  // <-- Model header yang ada
        $fstockmtdate,
        $fsupplier,
        $ffrom,
        $fket,
        $fbranchcode,
        $fcurrency,
        $frate,
        $userid,
        $now,
        $ftempohr,
        $ftypebuy,
        &$fstockmtno,
        &$rowsDt,
        $subtotal,
        $ppnAmount,
        $grandTotal,
        $faccid
      ) {

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

        $fstockmtcode = 'TER';

        if (empty($fstockmtno)) {
          $fstockmtno = $header->fstockmtno; // <-- Ambil dari record yang ada
        }

        // 1. UPDATE HEADER
        $masterData = [
          'fstockmtno' => $fstockmtno,
          'fstockmtcode' => $fstockmtcode,
          'fstockmtdate' => $fstockmtdate,
          'fprdout' => '0',
          'fsupplier' => $fsupplier,
          'fcurrency' => $fcurrency,
          'frate' => $frate,
          'famount' => round($subtotal, 2),
          'famount_rp' => round($subtotal * $frate, 2),
          'famountpajak' => round($ppnAmount, 2),
          'famountpajak_rp' => round($ppnAmount * $frate, 2),
          'famountmt' => round($grandTotal, 2),
          'famountmt_rp' => round($grandTotal * $frate, 2),
          'famountremain' => round($grandTotal, 2),
          'famountremain_rp' => round($grandTotal * $frate, 2),
          'frefno' => $request->input('frefno'),
          'frefpo' => $request->input('frefpo'),
          'ftrancode' => null,
          'ffrom' => $ffrom,
          'fto' => null,
          'fkirim' => null,
          'fprdjadi' => $faccid,
          'fqtyjadi' => null,
          'fket' => $fket,
          'fuserid' => $userid,
          'fdatetime' => $now,
          'fsalesman' => null,
          'fprint' => 0,
          'fsudahtagih' => '0',
          'fbranchcode' => $kodeCabang,
          'fdiscount' => 0,
          'ftempohr' => $ftempohr,
          'ftypebuy' => $ftypebuy,
          'fjatuhtempo' => $request->input('fjatuhtempo') ? Carbon::parse($request->input('fjatuhtempo'))->startOfDay() : null,
        ];

        $header->update($masterData);

        $header->details()->delete();

        $nextNouRef = 1;

        foreach ($rowsDt as &$r) {
          $r['fstockmtid'] = $header->fstockmtid;
          $r['fstockmtcode'] = $fstockmtcode;
          $r['fstockmtno'] = $fstockmtno;

          if (!isset($r['fnouref']) || $r['fnouref'] === null) {
            $r['fnouref'] = $nextNouRef++;
          }
        }
        unset($r);

        DB::table('trstockdt')->insert($rowsDt);
      });

      return redirect()
        ->route('fakturpembelian.index') // <-- Redirect kembali ke halaman edit
        ->with('success', "Faktur Pembelian {$fstockmtno} berhasil di-update.");
    } catch (\Exception $e) {
      return back()
        ->withInput()
        ->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
    }
  }

  public function delete(Request $request, $fstockmtid)
  {
    $suppliers = Supplier::orderBy('fsuppliername', 'asc')
      ->get(['fsupplierid', 'fsuppliername']);

    // 1. PINDAHKAN INI KE ATAS
    // Ambil data Header (trstockmt) DULU
    $fakturpembelian = PenerimaanPembelianHeader::with([
      'details' => function ($query) {
        $query
          ->join('msprd', 'msprd.fprdid', '=', 'trstockdt.fprdcode')
          ->select(
            'trstockdt.*',
            'msprd.fprdname',
            'msprd.fprdcode as fitemcode_text'
          )
          ->orderBy('trstockdt.fstockdtid', 'asc');
      }
    ])
      ->findOrFail($fstockmtid); // Temukan header berdasarkan $fstockmtid

    // 2. Ambil kode akun yang tersimpan dari faktur
    $savedAccountCode = $fakturpembelian->fprdjadi;

    // 3. UBAH QUERY INI: Gunakan $savedAccountCode
    $accounts = DB::table('account')
      ->select('faccid', 'faccount', 'faccname', 'fnonactive')
      ->where('fnonactive', '0') // Ambil semua yang aktif
      ->orderBy('faccount') // <-- Perbaikan nama kolom
      ->get();

    // --- Sisa kode Anda ---
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

    $fcabang = $branch->fcabangname ?? (string) $raw;
    $fbranchcode = $branch->fcabangkode ?? (string) $raw;

    // (Query $fakturpembelian sudah dipindah ke atas)

    // 4. Map the data for savedItems
    $savedItems = $fakturpembelian->details->map(function ($d) {
      return [
        'uid' => $d->fstockdtid,
        'fitemcode' => $d->fitemcode_text ?? '',
        'fitemname' => $d->fprdname ?? '',
        'fsatuan' => $d->fsatuan ?? '',
        'fprno' => $d->frefpr ?? '-',
        'frefpr' => $d->frefpr ?? null,
        'fpono' => $d->fpono ?? null,
        'famountponet' => $d->famountponet ?? null,
        'famountpo' => $d->famountpo ?? null,
        'frefdtno' => $d->frefdtno ?? null,
        'fnouref' => $d->fnouref ?? null,
        'fqty' => (float)($d->fqty ?? 0),
        'fterima' => (float)($d->fterima ?? 0),
        'fprice' => (float)($d->fprice ?? 0),
        'fdiscpersen' => (float)($d->fdiscpersen ?? 0),
        'fbiaya' => (float)($d->fbiaya ?? 0),
        'ftotprice' => (float)($d->ftotprice ?? 0),
        'ftotal' => (float)($d->ftotprice ?? 0),
        'fdesc' => is_array($d->fdesc) ? implode(', ', $d->fdesc) : ($d->fdesc ?? ''),
        'fketdt' => $d->fketdt ?? '',
        'units' => [],
      ];
    })->values();

    $selectedSupplierCode = $fakturpembelian->fsupplier;

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
          'name' => $p->fprdname,
          'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
          'stock' => $p->fminstock ?? 0,
        ],
      ];
    })->toArray();

    return view('fakturpembelian.edit', [
      'suppliers' => $suppliers,
      'selectedSupplierCode' => $selectedSupplierCode,
      'fcabang' => $fcabang,
      'fbranchcode' => $fbranchcode,
      'warehouses' => $warehouses,
      'products' => $products,
      'accounts' => $accounts,
      'productMap' => $productMap,
      'fakturpembelian' => $fakturpembelian,
      'savedItems' => $savedItems,
      'ppnAmount' => (float) ($fakturpembelian->famountpopajak ?? 0),
      'famountponet' => (float) ($fakturpembelian->famountponet ?? 0),
      'famountpo' => (float) ($fakturpembelian->famountpo ?? 0),
      'filterSupplierId' => $request->query('filter_supplier_id'),
      'action' => 'delete'
    ]);
  }

  public function destroy($fstockmtid)
  {
    try {

      $fakturpembelian = PenerimaanPembelianHeader::findOrFail($fstockmtid);
      $fakturpembelian->details()->delete();
      $fakturpembelian->delete();

      return response()->json([
        'success' => true,
        'message' => 'Data Faktur Pembelian berhasil dihapus'
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Gagal menghapus data: ' . $e->getMessage()
      ], 500);
    }
  }
}
