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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon; // sekalian biar aman untuk tanggal

class Tr_pohController extends Controller
{
  public function index(Request $request)
  {
    // Ambil izin (permissions)
    $canCreate = in_array('createTr_poh', explode(',', session('user_restricted_permissions', '')));
    $canEdit   = in_array('updateTr_poh', explode(',', session('user_restricted_permissions', '')));
    $canDelete = in_array('deleteTr_poh', explode(',', session('user_restricted_permissions', '')));
    $showActionsColumn = $canEdit || $canDelete;

    $status = $request->query('status');
    $year = $request->query('year');
    $month = $request->query('month');

    // Ambil tahun-tahun yang tersedia dari data
    $availableYears = Tr_poh::selectRaw('DISTINCT EXTRACT(YEAR FROM fdatetime) as year')
      ->whereNotNull('fdatetime')
      ->orderByRaw('EXTRACT(YEAR FROM fdatetime) DESC')
      ->pluck('year');

    // --- Handle Request AJAX dari DataTables ---
    if ($request->ajax()) {

      $query = Tr_poh::query()
        ->leftJoin('mssupplier', 'tr_poh.fsupplier', '=', 'mssupplier.fsupplierid');
      $totalRecords = Tr_poh::count();

      // Handle Search
      if ($search = $request->input('search.value')) {
        $query->where('fpono', 'like', "%{$search}%");
      }

      // Filter status
      $statusFilter = $request->query('status', 'active');
      if ($statusFilter === 'active') {
        $query->where('fclose', '0');
      } elseif ($statusFilter === 'nonactive') {
        $query->where('fclose', '1');
      }

      // Filter tahun
      if ($year) {
        $query->whereRaw('EXTRACT(YEAR FROM fdatetime) = ?', [$year]);
      }

      // Filter bulan
      if ($month) {
        $query->whereRaw('EXTRACT(MONTH FROM fdatetime) = ?', [$month]);
      }

      $filteredRecords = (clone $query)->count();

      // Sorting
      $orderColIdx = $request->input('order.0.column', 0);
      $orderDir = $request->input('order.0.dir', 'asc');

      $sortableColumns = ['fpono', 'fsupplier', 'fpodate', 'fclose', 'fusercreate', 'fapproval', 'mssupplier.fsuppliername'];

      if (isset($sortableColumns[$orderColIdx])) {
        $query->orderBy($sortableColumns[$orderColIdx], $orderDir);
      }

      // Paginasi
      $start = $request->input('start', 0);
      $length = $request->input('length', 10);
      $records = $query->skip($start)
        ->take($length)
        ->get(['fpono', 'fpohid', 'fsupplier', 'fpodate', 'fclose', 'fusercreate', 'fapproval', 'mssupplier.fsuppliername']);

      // Format Data - HANYA RETURN DATA MENTAH
      $data = $records->map(function ($row) {
        return [
          'fpono'     => $row->fpono,
          'fpohid'    => $row->fpohid,
          'fsupplier' => $row->fsupplier,
          'fpodate'   => $row->fpodate,
          'fclose'    => $row->fclose == '1' ? 'Done' : 'Not Done',
          'fusercreate'    => $row->fusercreate,
          'fapproval'    => $row->fapproval,
          'fsuppliername' => $row->fsuppliername
        ];
      });

      return response()->json([
        'draw'            => intval($request->input('draw')),
        'recordsTotal'    => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data'            => $data
      ]);
    }

    // --- Handle Request non-AJAX ---
    return view('tr_poh.index', compact(
      'canCreate',
      'canEdit',
      'canDelete',
      'showActionsColumn',
      'status',
      'availableYears',
      'year',
      'month'
    ));
  }

  public function pickable(Request $request)
  {
    // Base query dengan JOIN
    $query = Tr_prh::leftJoin('mssupplier', 'tr_prh.fsupplier', '=', 'mssupplier.fsupplierid')
      ->select(
        'tr_prh.*',
        'mssupplier.fsuppliername',
        'mssupplier.fsuppliercode'
      );

    // Total records tanpa filter
    $recordsTotal = Tr_prh::count();

    // Search
    if ($request->filled('search') && $request->search != '') {
      $search = $request->search;
      $query->where(function ($q) use ($search) {
        $q->where('tr_prh.fprno', 'ilike', "%{$search}%")
          ->orWhere('mssupplier.fsuppliername', 'ilike', "%{$search}%")
          ->orWhere('mssupplier.fsuppliercode', 'ilike', "%{$search}%");
      });
    }

    // Total records setelah filter
    $recordsFiltered = $query->count();

    // Sorting
    $orderColumn = $request->input('order_column', 'fprdate');
    $orderDir = $request->input('order_dir', 'desc');

    $allowedColumns = ['fprno', 'fsupplier', 'fprdate'];
    if (in_array($orderColumn, $allowedColumns)) {
      if (in_array($orderColumn, ['fprno', 'fprdate'])) {
        $query->orderBy('tr_prh.' . $orderColumn, $orderDir);
      } else {
        $query->orderBy('mssupplier.fsuppliername', $orderDir);
      }
    } else {
      $query->orderBy('tr_prh.fprdate', 'desc');
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
    // Ambil data header PR berdasarkan fprhid
    $header = Tr_prh::where('fprhid', $id)->firstOrFail();

    // PERBAIKAN: Gunakan fprhid (integer) bukan fprno (varchar)
    $items = Tr_prd::where('tr_prd.fprhid', $header->fprhid) // <- Gunakan fprhid
      ->leftJoin('msprd as m', 'm.fprdid', '=', 'tr_prd.fprdid')
      ->select([
        'tr_prd.fprdid as frefdtno',
        'tr_prd.fprhid as fnouref',
        // 'tr_prd.fprdid as fitemcode',
        'm.fprdcode as fitemcode',
        'm.fprdname as fitemname',
        'tr_prd.fqty',
        'tr_prd.fsatuan as fsatuan',
        'tr_prd.fprhid',
        'tr_prd.fprice as fharga',
        DB::raw('0::numeric as fdiskon')
      ])
      ->orderBy('tr_prd.fprdid')
      ->get();

    return response()->json([
      'header' => [
        'fprhid'     => $header->fprhid,
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

  public function print(string $fpohid)
  {
    $supplierTable = (new Supplier)->getTable();

    $hdr = Tr_poh::query()
      ->leftJoin("{$supplierTable} as s", 's.fsupplierid', '=', 'tr_poh.fsupplier')
      ->leftJoin('mscabang as c', 'c.fcabangkode', '=', 'tr_poh.fbranchcode')
      ->where('tr_poh.fpohid', $fpohid)
      ->first([
        'tr_poh.*',
        's.fsuppliername as supplier_name',
        's.faddress as supplier_address',
        'c.fcabangname as cabang_name',
      ]);

    if (!$hdr) {
      return redirect()->back()->with('error', 'PO tidak ditemukan.');
    }

    $fpohid = (int) $hdr->fpohid;

    $dt = DB::table('tr_pod')
      ->leftJoin('msprd as p', function ($j) {
        $j->on('p.fprdid', '=', DB::raw('CAST(tr_pod.fprdid AS INTEGER)'));
      })
      ->where('tr_pod.fpohid', $fpohid)
      ->orderBy('tr_pod.fnou')
      ->get([
        'tr_pod.*',
        'p.fprdcode as product_code',
        'p.fprdname as product_name',
      ]);

    // Hitung totals — jika fincludeppn = 1 baru tambah PPN
    $subtotal  = $dt->sum('famount');
    $ppnPersen = (float) ($hdr->fppnpersen ?? 11);
    $ppnAmount = $hdr->fincludeppn == '1' ? round($subtotal * $ppnPersen / 100, 2) : 0;
    $grandTotal = round($subtotal + $ppnAmount, 2);

    $fmt = fn($d) => $d
      ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d F Y')
      : '-';

    return view('tr_poh.print', [
      'hdr'          => $hdr,
      'dt'           => $dt,
      'fmt'          => $fmt,
      'subtotal'     => $subtotal,
      'ppnPersen'    => $ppnPersen,
      'ppnAmount'    => $ppnAmount,
      'grandTotal'   => $grandTotal,
      'company_name' => config('app.company_name', 'PT. DEMO VERSION'),
      'company_city' => config('app.company_city', 'Tangerang'),
    ]);
  }

  public function lastPrice(Request $request)
  {
    $fprdcode  = trim($request->input('fprdcode', ''));
    $fsupplier = trim($request->input('fsupplier', ''));
    $fsatuan   = trim($request->input('fsatuan', ''));

    if (!$fprdcode || !$fsupplier || !$fsatuan) {
      return response()->json(['fprice' => 0, 'fdisc' => 0]);
    }

    $row = DB::table('tr_poh as m')
      ->join('tr_pod as d', 'm.fpohid', '=', 'd.fpohid')
      ->where(DB::raw('trim(d.fprdcode)'), $fprdcode)
      ->where(DB::raw('trim(m.fsupplier)'), $fsupplier)
      ->where(DB::raw('trim(d.fsatuan)'), $fsatuan)
      ->orderBy('m.fpodate', 'desc')
      ->select('d.fprice', 'd.fdisc')
      ->first();

    return response()->json([
      'fprice' => $row ? (float)$row->fprice : 0,
      'fdisc'  => $row ? (float)$row->fdisc  : 0,
    ]);
  }

  public function create(Request $request)
  {
    $suppliers = Supplier::orderBy('fsuppliername', 'asc')
      ->get(['fsupplierid', 'fsuppliername']);

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

    $currencies = DB::table('mscurrency')
      ->where(function ($q) {
        $q->whereNull('fnonactive')->orWhere('fnonactive', '0')->orWhere('fnonactive', '');
      })
      ->orderBy('fcurrname')
      ->get(['fcurrid', 'fcurrcode', 'fcurrname', 'frate']);

    $products = Product::select(
      'fprdid',
      'fprdcode',
      'fprdname',
      'fsatuankecil',
      'fsatuanbesar',
      'fsatuanbesar2',
      'fminstock'
    )->orderBy('fprdname')->get();

    return view('tr_poh.create', [
      'newtr_prh_code' => $newtr_prh_code,
      'perms' => ['can_approval' => $canApproval],
      'suppliers' => $suppliers,
      'fcabang' => $fcabang,
      'fbranchcode' => $fbranchcode,
      'products' => $products,
      'currencies'       => $currencies,
      'filterSupplierId' => $request->query('filter_supplier_id'),
    ]);
  }

  public function store(Request $request)
  {
    // VALIDATION
    $request->validate([
      'fpohid'        => ['nullable', 'string', 'max:25'],
      'fpodate'      => ['required', 'date'],
      'fkirimdate'   => ['nullable', 'date'],
      'fincludeppn'  => ['nullable'],
      'fket'         => ['nullable', 'string', 'max:300'],
      'fbranchcode'  => ['nullable', 'string', 'max:20'],
      'ftempohr'     => ['nullable', 'string', 'max:3'],

      'fitemcode'    => ['required', 'array', 'min:1'],
      'fitemcode.*'  => ['required', 'string', 'max:50'],

      'fsatuan'      => ['nullable', 'array'],
      'fsatuan.*'    => ['nullable', 'string', 'max:20'],

      'fapproval'    => ['nullable'],

      'frefdtno'     => ['nullable'],
      'frefdtno.*'   => ['nullable'],

      'fqty'         => ['required', 'array'],
      'fqty.*'       => ['numeric', 'min:0'],

      'fprice'       => ['nullable', 'array'],
      'fprice.*'     => ['numeric', 'min:0'],

      'fdisc'        => ['nullable', 'array'],
      'fdisc.*'      => ['numeric', 'min:0'],

      'frefpr'       => ['nullable', 'array'],
      'frefpr.*'     => ['nullable', 'string', 'max:30'],

      'fdesc'        => ['nullable', 'array'],
      'fdesc.*'      => ['nullable', 'string', 'max:500'],

      'ppn_rate'     => ['nullable', 'numeric', 'min:0', 'max:100'],
    ], [
      'fpodate.required'   => 'Tanggal PO wajib diisi.',
      'fsupplier.required' => 'Supplier wajib diisi.',
      'fitemcode.required' => 'Minimal 1 item.',
    ]);

    // HEADER VALUES
    $fpodate     = Carbon::parse($request->fpodate)->startOfDay();
    $fkirimdate  = $request->filled('fkirimdate') ? Carbon::parse($request->fkirimdate)->startOfDay() : null;
    $fpohid       = $request->input('fpohid'); // can be null; we will generate if empty
    $fincludeppn = $request->boolean('fincludeppn') ? 1 : 0;
    $userid      = auth('sysuser')->user()->fname ?? 'admin';
    $now         = now();

    // DETAIL ARRAYS
    $codes   = $request->input('fitemcode', []);
    $satuans = $request->input('fsatuan', []);
    $refdtno = $request->input('frefdtno', []);
    $qtys    = $request->input('fqty', []);
    $prices  = $request->input('fprice', []);
    $discs   = $request->input('fdisc', []);
    $refprs  = $request->input('frefpr', []);
    $descs   = $request->input('fdesc', []);

    // TOTALS (from frontend)
    $totalHarga = (float) $request->input('famountponet', 0);
    $ppnAmount  = (float) $request->input('famountpopajak', 0);
    $grandTotal = (float) $request->input('famountpo', 0);

    // Load product metadata: map code -> (fprdid, satuans)
    $uniqueCodes = array_values(array_unique(array_filter(array_map(fn($c) => trim((string)$c), $codes))));
    $prodMeta = DB::table('msprd')
      ->whereIn('fprdcode', $uniqueCodes)
      ->get(['fprdid', 'fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'])
      ->keyBy('fprdcode');

    $pickDefaultSat = function (string $code) use ($prodMeta): string {
      $m = $prodMeta[$code] ?? null;
      if (!$m) return '';
      foreach (['fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'] as $k) {
        $v = trim((string)($m->$k ?? ''));
        if ($v !== '') return mb_substr($v, 0, 5);
      }
      return '';
    };

    // BUILD DETAIL ROWS (use fprdid, not fprdid)
    $rowsPod    = [];
    $totalHarga = 0.0; // recompute to be safe
    $rowCount   = max(count($codes), count($satuans), count($refdtno), count($qtys), count($prices), count($discs), count($refprs), count($descs));

    for ($i = 0; $i < $rowCount; $i++) {
      $code   = trim($codes[$i] ?? '');
      $sat    = trim((string)($satuans[$i] ?? ''));
      $refdt  = trim((string)($refdtno[$i] ?? ''));
      $qty    = (float)($qtys[$i]    ?? 0);
      $price  = (float)($prices[$i]  ?? 0);
      $discP  = (float)($discs[$i]   ?? 0);
      $desc   = (string)($descs[$i]  ?? '');

      if ($code === '' || $qty <= 0) continue;

      if ($sat === '') $sat = $pickDefaultSat($code);
      $sat = mb_substr($sat, 0, 20);
      if ($sat === '') continue;

      $productId = (int) (($prodMeta[$code]->fprdid ?? null) ?? 0);
      if ($productId === 0) continue;

      $product = DB::table('msprd')
        ->where('fprdcode', $code)
        ->select('fprdid', 'fprdcode', 'fsatuanbesar', 'fqtykecil as rasio_konversi')
        ->first();

      $itemeId = $product ? $product->fprdid : $itemeId;

      $qtyKecil = $qty;
      if ($product && $sat === $product->fsatuanbesar) {
        $qtyKecil = $qty * (float)$product->rasio_konversi;
      }

      $priceGross = $price;
      $priceNet   = $priceGross * (1 - ($discP / 100));
      $amount     = $qty * $priceNet;

      $totalHarga += $amount;

      $rowsPod[] = [
        'fprdid'      => $productId,   // <-- integer FK to msprd.fprdid
        'fprdcode'      => $product->fprdcode ?? '',   // <-- integer FK to msprd.fprdid
        'fqty'        => $qty,
        'fdisc'       => (string)$discP,
        'fprice'      => $price,
        'fprice_rp'   => $price,
        'fpricegross' => $priceGross,
        'fpricenet'   => $priceNet,
        'famount'     => $amount,
        'famount_rp'  => $amount,
        'fusercreate'     => $userid,
        'fdatetime'   => $now,
        'fsatuan'     => $sat,
        'frefdtno'    => $refdt,
        'frefdtid'    => $refdt, // referensi ke header (integer FK)
        'fdesc'       => $desc,
        'fqtykecil'   => $qtyKecil,
        'fqtyremain'  => $qtyKecil,
      ];
    }

    if (empty($rowsPod)) {
      return back()->withInput()->withErrors(['detail' => 'Minimal satu item valid (Kode, Satuan, Qty > 0).']);
    }

    // TRANSACTION
    $fpono = null;
    DB::transaction(function () use (
      $request,
      $fpodate,
      $fkirimdate,
      $fincludeppn,
      $userid,
      $now,
      $rowsPod,
      $fpohid,
      $totalHarga,
      $ppnAmount,
      $grandTotal,
      &$fpono
    ) {
      // Generate human code if not provided
      if (empty($fpohid)) {
        $rawBranch = $request->input('fbranchcode');
        $kodeCabang = null;
        if ($rawBranch !== null) {
          $needle = trim((string)$rawBranch);
          if (is_numeric($needle)) {
            $kodeCabang = DB::table('mscabang')->where('fcabangid', (int)$needle)->value('fcabangkode');
          } else {
            $kodeCabang = DB::table('mscabang')->whereRaw('LOWER(fcabangkode)=LOWER(?)', [$needle])->value('fcabangkode')
              ?: DB::table('mscabang')->whereRaw('LOWER(fcabangname)=LOWER(?)', [$needle])->value('fcabangkode');
          }
        }
        if (!$kodeCabang) $kodeCabang = 'NA';

        $yy = $fpodate->format('y');
        $mm = $fpodate->format('m');
        $prefix = sprintf('PO.%s.%s.%s.', $kodeCabang, $yy, $mm);

        // advisory lock per (branch, y-m)
        $lockKey = crc32('PO|' . $kodeCabang . '|' . $fpodate->format('Y-m'));
        DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

        // get last sequence under this prefix
        $last = DB::table('tr_poh')
          ->where('fpono', 'like', $prefix . '%')
          ->selectRaw("MAX(CAST(split_part(fpono, '.', 5) AS int)) AS lastno")
          ->value('lastno');

        $next = (int)$last + 1;
        $fpohid = $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
      }

      $fcurrency = $request->input('fcurrency', 'IDR');
      $frate     = $request->input('frate', 15500);
      $ftempohr  = $request->input('ftempohr', 0);
      $isApproval = (int)($request->input('fapproval', 0));

      // INSERT HEADER and GET fpohid
      $fpohid = DB::table('tr_poh')->insertGetId([
        'fpono'          => $fpohid,     // human-readable code stays in header
        'fpodate'        => $fpodate,
        'fkirimdate'     => $fkirimdate,
        'fcurrency'      => $fcurrency,
        'ftempohr'       => $ftempohr,
        'frate'          => $frate,
        'fsupplier'      => $request->input('fsupplier'),
        'fincludeppn'    => $fincludeppn,
        'fket'           => $request->input('fket'),
        'fusercreate'    => $userid,
        'fdatetime'      => $now,
        'famountponet'   => round($totalHarga, 2),
        'famountpopajak' => $ppnAmount,
        'famountpo'      => $grandTotal,
        'fapproval'      => $isApproval,
        'fppnpersen'      => $request->input('ppn_rate', 0),
        'fclose'      => '0',
      ], 'fpohid');

      $fpono = DB::table('tr_poh')->where('fpohid', $fpohid)->value('fpono');

      // EMAIL after commit — use fpohid and fprdid
      if ($isApproval === 1) {
        DB::afterCommit(function () use ($fpohid) {
          $hdr = Tr_poh::where('fpohid', $fpohid)->first();

          $dt  = Tr_pod::from('tr_pod as d')
            ->leftJoin('msprd as p', 'p.fprdid', '=', 'd.fprdid')  // <-- FK to msprd.fprdid
            ->where('d.fpohid', $fpohid) // integer FK now
            ->orderBy('p.fprdname')
            ->get([
              'd.*',
              'p.fprdname as product_name',
              'p.fminstock as stock',
            ]);

          $productName = $dt->pluck('product_name')->implode(', ');
          $approver    = auth('sysuser')->user()->fname ?? '-';

          Mail::to('vierybiliam8@gmail.com')
            ->send(new ApprovalEmailPo($hdr, $dt, $productName, $approver, 'Order Pembelian (PO)'));
        });
      }

      // numbering + insert details — use fpohid
      $lastNou = (int) DB::table('tr_pod')->where('fpohid', $fpohid)->max('fnou');
      $nextNou = $lastNou + 1;

      foreach ($rowsPod as &$r) {
        $r['fpohid'] = $fpohid;  // FK → tr_poh.fpohid (integer)
        $r['fnou']  = $nextNou++;
        // $r['fprdid'] already set above
        $r['frefdtno'] = $fpono;    // ✅ string → tr_poh.fpono  (misal: "PO.BG.26.03.0008")
        $r['frefdtid'] = $fpohid;
      }
      unset($r);

      DB::table('tr_pod')->insert($rowsPod);
    });

    return redirect()
      ->route('tr_poh.create')
      ->with('success', "Data sudah tersimpan. No {$fpono}");
  }

  public function edit(Request $request, $fpohid)
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

    $fcabang     = $branch->fcabangname ?? (string) $raw;   // tampilan
    $fbranchcode = $branch->fcabangkode ?? (string) $raw;   // hidden post

    $tr_poh = Tr_poh::with(['details' => function ($q) {
      $q->leftJoin('msprd', function ($j) {
        $j->on('msprd.fprdid', '=', 'tr_pod.fprdid');
      })
        ->select(
          'tr_pod.*',
          'msprd.fprdcode as fitemcode',
          'msprd.fprdname'
        );
    }])->findOrFail($fpohid);

    $savedItems = $tr_poh->details->map(function ($d) {
      return [
        'uid'        => $d->fpodid,
        'fitemcode'  => (string)($d->fitemcode ?? ''),  // dari alias msprd.fprdid
        'fitemname'  => (string)($d->fprdname ?? ''),   // dari msprd.fprdname
        'fsatuan'    => (string)($d->fsatuan ?? ''),
        'frefdtno'   => (string)($d->frefdtno ?? ''),
        'fqty'       => (float)($d->fqty ?? 0),
        'fterima'    => (float)($d->fterima ?? 0),
        'fprice'     => (float)($d->fprice ?? 0),
        'fdisc'      => (float)($d->fdisc ?? 0),
        'ftotal'     => (float)($d->famount ?? 0),
        'fdesc'      => (string)($d->fdesc ?? ''),
        'fketdt'     => (string)($d->fketdt ?? ''),
        'fprdid'     => (int)($d->fprdcodeid ?? 0),
      ];
    })->values();
    $selectedSupplierCode = $tr_poh->fsupplier;

    // Fetch all products for product mapping
    $products = Product::select(
      'fprdid',
      'fprdcode',
      'fprdname',
      'fsatuankecil',
      'fsatuanbesar',
      'fsatuanbesar2',
      'fminstock'
    )->orderBy('fprdname')->get();

    // Prepare the product map for frontend
    $productMap = $products->mapWithKeys(function ($p) {
      return [
        $p->fprdid => [
          'id'    => $p->fprdid,
          'name'  => $p->fprdname,
          'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
          'stock' => $p->fminstock ?? 0,
        ],
      ];
    })->toArray();

    // Pass the data to the view
    return view('tr_poh.edit', [
      'suppliers'      => $suppliers,
      'selectedSupplierCode' => $selectedSupplierCode, // Kirim kode supplier ke view
      'fcabang'      => $fcabang,
      'fbranchcode'  => $fbranchcode,
      'products'     => $products,
      'productMap'   => $productMap,
      'tr_poh'       => $tr_poh,
      'savedItems'   => $savedItems,
      'ppnAmount'    => (float) ($tr_poh->famountpopajak ?? 0), // total PPN from DB
      'famountponet'    => (float) ($tr_poh->famountponet ?? 0),  // nilai Grand Total dari DB
      'famountpo'    => (float) ($tr_poh->famountpo ?? 0),  // nilai Grand Total dari DB
      'filterSupplierId' => $request->query('filter_supplier_id'),
      'action' => 'edit'
    ]);
  }

  public function view(Request $request, $fpohid)
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

    $fcabang     = $branch->fcabangname ?? (string) $raw;   // tampilan
    $fbranchcode = $branch->fcabangkode ?? (string) $raw;   // hidden post

    $tr_poh = Tr_poh::with(['details' => function ($q) {
      $q->orderBy('fpodid')
        ->leftJoin('msprd', function ($j) {
          $j->on('msprd.fprdid', '=', DB::raw('CAST(tr_pod.fprdid AS INTEGER)'));
        })
        ->select(
          'tr_pod.*',
          'msprd.fprdid as fitemcode',
          'msprd.fprdname'
        );
    }])->findOrFail($fpohid);

    $savedItems = $tr_poh->details->map(function ($d) {
      return [
        'uid'        => $d->fpodid,
        'fitemcode'  => (string)($d->fitemcode ?? ''),  // dari alias msprd.fprdid
        'fitemname'  => (string)($d->fprdname ?? ''),   // dari msprd.fprdname
        'fsatuan'    => (string)($d->fsatuan ?? ''),
        'frefdtno'   => (string)($d->frefdtno ?? ''),
        'fqty'       => (float)($d->fqty ?? 0),
        'fterima'    => (float)($d->fterima ?? 0),
        'fprice'     => (float)($d->fprice ?? 0),
        'fdisc'      => (float)($d->fdisc ?? 0),
        'ftotal'     => (float)($d->famount ?? 0),
        'fdesc'      => (string)($d->fdesc ?? ''),
        'fketdt'     => (string)($d->fketdt ?? ''),
      ];
    })->values();
    $selectedSupplierCode = $tr_poh->fsupplier;

    // Fetch all products for product mapping
    $products = Product::select(
      'fprdid',
      'fprdid',
      'fprdname',
      'fsatuankecil',
      'fsatuanbesar',
      'fsatuanbesar2',
      'fminstock'
    )->orderBy('fprdname')->get();

    // Prepare the product map for frontend
    $productMap = $products->mapWithKeys(function ($p) {
      return [
        $p->fprdid => [
          'name'  => $p->fprdname,
          'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
          'stock' => $p->fminstock ?? 0,
        ],
      ];
    })->toArray();

    // Pass the data to the view
    return view('tr_poh.view', [
      'suppliers'      => $suppliers,
      'selectedSupplierCode' => $selectedSupplierCode, // Kirim kode supplier ke view
      'fcabang'      => $fcabang,
      'fbranchcode'  => $fbranchcode,
      'products'     => $products,
      'productMap'   => $productMap,
      'tr_poh'       => $tr_poh,
      'savedItems'   => $savedItems,
      'ppnAmount'    => (float) ($tr_poh->famountpopajak ?? 0), // total PPN from DB
      'famountponet'    => (float) ($tr_poh->famountponet ?? 0),  // nilai Grand Total dari DB
      'famountpo'    => (float) ($tr_poh->famountpo ?? 0),  // nilai Grand Total dari DB
      'filterSupplierId' => $request->query('filter_supplier_id'),
    ]);
  }

  public function update(Request $request, $fpohid)
  {
    $request->validate([
      'fpodate'      => ['required', 'date'],
      'fkirimdate'   => ['nullable', 'date'],
      'fsupplier'    => ['required', 'string', 'max:30'],
      'fincludeppn'  => ['nullable'],
      'fket'         => ['nullable', 'string', 'max:300'],
      'fbranchcode'  => ['nullable', 'string', 'max:20'],
      'fitemcode'    => ['required', 'array', 'min:1'],
      'fitemcode.*'  => ['required', 'string', 'max:50'],
      'fsatuan'      => ['nullable', 'array'],
      'fsatuan.*'    => ['nullable', 'string', 'max:20'],
      'frefdtno'     => ['nullable'],
      'frefdtno.*'   => ['nullable'],
      'fqty'         => ['required', 'array'],
      'fqty.*'       => ['numeric', 'min:0'],
      'fprice'       => ['nullable', 'array'],
      'fprice.*'     => ['numeric', 'min:0'],
      'fdisc'        => ['nullable', 'array'],
      'fdisc.*'      => ['numeric', 'min:0'],
      'frefpr'       => ['nullable', 'array'],
      'frefpr.*'     => ['nullable', 'string', 'max:30'],
      'fdesc'        => ['nullable', 'array'],
      'fdesc.*'      => ['nullable', 'string', 'max:500'],
      'ppn_rate'     => ['nullable', 'numeric', 'min:0', 'max:100'],
    ], [
      'fpodate.required'   => 'Tanggal PO wajib diisi.',
      'fsupplier.required' => 'Supplier wajib diisi.',
      'fitemcode.required' => 'Minimal 1 item.',
    ]);

    $header  = Tr_poh::where('fpohid', $fpohid)->firstOrFail();
    $fponoId = (int) $header->fpohid;

    $fpodate     = \Carbon\Carbon::parse($request->fpodate)->startOfDay();
    $fkirimdate  = $request->filled('fkirimdate')
      ? \Carbon\Carbon::parse($request->fkirimdate)->startOfDay()
      : null;
    $fincludeppn = $request->boolean('fincludeppn') ? 1 : 0;
    $userid      = Auth::guard('sysuser')->user()?->fname
      ?? Auth::user()?->fname
      ?? 'system';
    $now = now();

    $codes   = $request->input('fitemcode', []);
    $satuans = $request->input('fsatuan', []);
    $refdtns = $request->input('frefdtno', []);
    $qtys    = $request->input('fqty', []);
    $prices  = $request->input('fprice', []);
    $discs   = $request->input('fdisc', []);
    $refprs  = $request->input('frefpr', []);
    $descs   = $request->input('fdesc', []);

    $ppnRate = (float) $request->input('ppn_rate', 0);
    $ppnRate = max(0, min(100, $ppnRate));

    $uniqueCodes = array_values(array_unique(
      array_filter(array_map(fn($c) => trim((string)$c), $codes))
    ));

    $prodMeta = DB::table('msprd')
      ->whereIn('fprdcode', $uniqueCodes)
      ->get(['fprdid', 'fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'])
      ->keyBy('fprdcode');

    $pickDefaultSat = function ($code) use ($prodMeta) {
      $m = $prodMeta[$code] ?? null;
      if (!$m) return '';
      foreach (['fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'] as $k) {
        $v = trim((string)($m->$k ?? ''));
        if ($v !== '') return mb_substr($v, 0, 20);
      }
      return '';
    };

    $rowsPod    = [];
    $totalHarga = 0.0;
    $rowCount   = max(
      count($codes),
      count($satuans),
      count($refdtns),
      count($qtys),
      count($prices),
      count($discs),
      count($refprs),
      count($descs)
    );

    for ($i = 0; $i < $rowCount; $i++) {
      $code  = trim((string)($codes[$i]   ?? ''));
      $sat   = trim((string)($satuans[$i] ?? ''));
      $refdt = trim((string)($refdtns[$i] ?? ''));
      $qty   = (float)($qtys[$i]   ?? 0);
      $price = (float)($prices[$i] ?? 0);
      $discP = (float)($discs[$i]  ?? 0);
      $desc  = (string)($descs[$i] ?? '');

      if ($code === '' || $qty <= 0) continue;

      $product = DB::table('msprd')
        ->where('fprdcode', $code)
        ->select('fprdid', 'fprdcode', 'fsatuanbesar', 'fqtykecil as rasio_konversi')
        ->first();

      if (!$product) continue;

      $productId = (int) $product->fprdid;
      if ($productId === 0) continue;

      $qtyKecil = $qty;
      if ($sat === $product->fsatuanbesar && (float)$product->rasio_konversi > 0) {
        $qtyKecil = $qty * (float)$product->rasio_konversi;
      }

      if ($sat === '') $sat = $pickDefaultSat($code);
      $sat = mb_substr($sat, 0, 20);
      if ($sat === '') continue;

      $priceGross = $price;
      $priceNet   = $priceGross * (1 - ($discP / 100));
      $amount     = $qty * $priceNet;
      $totalHarga += $amount;

      $rowsPod[] = [
        'fprdid'      => $productId,
        'fprdcode'    => $product->fprdcode ?? '',
        'fqty'        => $qty,
        'fdisc'       => (string)$discP,
        'fprice'      => $price,
        'fprice_rp'   => $price,
        'fpricegross' => $priceGross,
        'fpricenet'   => $priceNet,
        'famount'     => $amount,
        'famount_rp'  => $amount,
        'fuserupdate' => $userid,
        'fdatetime'   => $now,
        'fsatuan'     => $sat,
        'frefdtno'    => $refdt,
        'fdesc'       => $desc,
        'fqtykecil'   => $qtyKecil,
        'fqtyremain'  => $qtyKecil,
      ];
    }

    if (empty($rowsPod)) {
      return back()->withInput()->withErrors([
        'detail' => 'Minimal satu item valid (Kode Produk ada di master, Satuan tidak kosong, Qty > 0).'
      ]);
    }

    $ppnAmount  = $fincludeppn ? round($totalHarga * ($ppnRate / 100), 2) : 0.0;
    $grandTotal = round($totalHarga + $ppnAmount, 2);

    try {
      DB::transaction(function () use (
        $request,
        $header,
        $fpodate,
        $fkirimdate,
        $userid,
        $now,
        $rowsPod,
        $fponoId,
        $totalHarga,
        $ppnAmount,
        $grandTotal,
        $fincludeppn
      ) {
        $fpohid = DB::table('tr_poh')
          ->where('fpohid', $fponoId)
          ->update([
            'fpodate'        => $fpodate,
            'fkirimdate'     => $fkirimdate,
            'fcurrency'      => $request->input('fcurrency', 'IDR'),
            'frate'          => $request->input('frate', 15500),
            'fsupplier'      => $request->input('fsupplier'),
            'fincludeppn'    => $fincludeppn,
            'fket'           => $request->input('fket'),
            'fuserupdate'    => $userid,
            'fupdatedat'     => now(),
            'famountponet'   => round($totalHarga, 2),
            'famountpopajak' => $ppnAmount,
            'famountpo'      => $grandTotal,
            'fclose'         => '0',
          ]);
        $fpono = DB::table('tr_poh')->where('fpohid', $fpohid)->value('fpono');

        DB::table('tr_pod')->where('fpohid', $fponoId)->delete();

        $nextNou = 1;
        foreach ($rowsPod as &$r) {
          $r['fpohid'] = $fponoId;
          $r['fnou']   = $nextNou++;
          $r['frefdtno'] = $fpono;    // ✅ string → tr_poh.fpono  (misal: "PO.BG.26.03.0008")
          $r['frefdtid'] = $fpohid;
        }
        unset($r);

        DB::table('tr_pod')->insert($rowsPod);
      });
    } catch (\Throwable $e) {
      return back()->withInput()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
    }

    return redirect()
      ->route('tr_poh.index')
      ->with('success', "Data PO {$header->fpono} berhasil diperbarui.");
  }

  public function delete(Request $request, $fpohid)
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

    $fcabang     = $branch->fcabangname ?? (string) $raw;   // tampilan
    $fbranchcode = $branch->fcabangkode ?? (string) $raw;   // hidden post

    $tr_poh = Tr_poh::with(['details' => function ($q) {
      $q->orderBy('fpodid')
        ->leftJoin('msprd', function ($j) {
          $j->on('msprd.fprdid', '=', DB::raw('CAST(tr_pod.fprdid AS INTEGER)'));
        })
        ->select(
          'tr_pod.*',
          'msprd.fprdid as fitemcode',
          'msprd.fprdname'
        );
    }])->findOrFail($fpohid);

    $savedItems = $tr_poh->details->map(function ($d) {
      return [
        'uid'        => $d->fpodid,
        'fitemcode'  => (string)($d->fitemcode ?? ''),  // dari alias msprd.fprdid
        'fitemname'  => (string)($d->fprdname ?? ''),   // dari msprd.fprdname
        'fsatuan'    => (string)($d->fsatuan ?? ''),
        'frefdtno'   => (string)($d->frefdtno ?? ''),
        'fqty'       => (float)($d->fqty ?? 0),
        'fterima'    => (float)($d->fterima ?? 0),
        'fprice'     => (float)($d->fprice ?? 0),
        'fdisc'      => (float)($d->fdisc ?? 0),
        'ftotal'     => (float)($d->famount ?? 0),
        'fdesc'      => (string)($d->fdesc ?? ''),
        'fketdt'     => (string)($d->fketdt ?? ''),
      ];
    })->values();
    $selectedSupplierCode = $tr_poh->fsupplier;

    // Fetch all products for product mapping
    $products = Product::select(
      'fprdid',
      'fprdid',
      'fprdname',
      'fsatuankecil',
      'fsatuanbesar',
      'fsatuanbesar2',
      'fminstock'
    )->orderBy('fprdname')->get();

    // Prepare the product map for frontend
    $productMap = $products->mapWithKeys(function ($p) {
      return [
        $p->fprdid => [
          'name'  => $p->fprdname,
          'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
          'stock' => $p->fminstock ?? 0,
        ],
      ];
    })->toArray();

    // Pass the data to the view
    return view('tr_poh.edit', [
      'suppliers'      => $suppliers,
      'selectedSupplierCode' => $selectedSupplierCode, // Kirim kode supplier ke view
      'fcabang'      => $fcabang,
      'fbranchcode'  => $fbranchcode,
      'products'     => $products,
      'productMap'   => $productMap,
      'tr_poh'       => $tr_poh,
      'savedItems'   => $savedItems,
      'ppnAmount'    => (float) ($tr_poh->famountpopajak ?? 0), // total PPN from DB
      'famountponet'    => (float) ($tr_poh->famountponet ?? 0),  // nilai Grand Total dari DB
      'famountpo'    => (float) ($tr_poh->famountpo ?? 0),  // nilai Grand Total dari DB
      'filterSupplierId' => $request->query('filter_supplier_id'),
      'action' => 'delete'
    ]);
  }

  public function destroy($fpohid)
  {
    try {
      $tr_poh = Tr_poh::findOrFail($fpohid);
      $tr_poh->delete();

      return redirect()->route('tr_poh.index')->with('success', 'Data Order Pembelian ' . $tr_poh->fpono . ' berhasil dihapus.');
    } catch (\Exception $e) {
      // Jika terjadi kesalahan saat menghapus, kembali ke halaman delete dengan pesan error
      return redirect()->route('tr_poh.delete', $fpohid)->with('error', 'Gakey: gal menghapus data: ' . $e->getMessage());
    }
  }
}
