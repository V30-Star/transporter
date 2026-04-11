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
      ->where('fstockmtcode', 'BUY')
      ->whereNotNull('fdatetime')
      ->orderByRaw('EXTRACT(YEAR FROM fdatetime) DESC')
      ->pluck('year');

    // --- 2. Handle Request AJAX dari DataTables ---
    if ($request->ajax()) {

      // Query dasar HANYA untuk 'BUY' (Faktur)
      $query = PenerimaanPembelianHeader::where('fstockmtcode', 'BUY');

      // Total records (dengan filter 'BUY')
      $totalRecords = PenerimaanPembelianHeader::where('fstockmtcode', 'BUY')->count();

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

  public function pickablePO(Request $request)
  {
    $search   = trim((string) $request->get('search', ''));
    $perPage  = (int) $request->get('per_page', 10);

    $query = Tr_poh::query()
      ->leftJoin('mssupplier', 'tr_poh.fsupplier', '=', 'mssupplier.fsupplierid')
      ->select([
        'tr_poh.fpohid',
        'tr_poh.fpono',
        'mssupplier.fsuppliername',
        'tr_poh.fpodate',
      ]);

    if ($search !== '') {
      $likeOp = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
      $query->where(function ($q) use ($search, $likeOp) {
        $q->where('tr_poh.fpono', $likeOp, "%{$search}%")
          ->orWhere('mssupplier.fsuppliername', $likeOp, "%{$search}%")
          ->orWhereRaw("TO_CHAR(tr_poh.fpodate, 'YYYY-MM-DD HH24:MI:SS') {$likeOp} ?", ["%{$search}%"]);
      });
    }

    $query->orderByDesc('tr_poh.fpodate')
      ->orderByDesc('tr_poh.fpohid');

    $paginated = $query->paginate($perPage)->withQueryString();

    $rows = collect($paginated->items())->map(function ($t) {
      return [
        'fpohid'     => $t->fpohid,
        'fpono'     => $t->fpono,
        'fsupplier' => trim($t->fsuppliername ?? ''),
        'fpodate'   => $t->fpodate ? \Carbon\Carbon::parse($t->fpodate)->format('Y-m-d H:i:s') : 'No Date',
        'items_url' => route('fakturpembelian.itemsPO', $t->fpohid),
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
      'current_page' => $paginated->currentPage(),
      'last_page'    => $paginated->lastPage(),
      'total'        => $paginated->total(),
    ]);
  }

  public function itemsPO($id)
  {
    $header = Tr_poh::where('fpohid', $id)->firstOrFail();

    $items = Tr_pod::where('tr_pod.fpohid', $header->fpohid)
      ->leftJoin('msprd as m', 'm.fprdid', '=', 'tr_pod.fprdid')
      ->select([
        'tr_pod.fpodid as frefdtno',
        'tr_pod.fprdcode as fitemcode',
        'm.fprdname as fitemname',
        'tr_pod.fqty',
        'tr_pod.fsatuan as fsatuan',
        'tr_pod.fprice',
        'tr_pod.fdisc',
        'tr_pod.famount as fbiaya',
        'tr_pod.fpricenet as fharga',
        DB::raw('0::numeric as fdiskon')
      ])
      ->orderBy('tr_pod.fprdcode')
      ->get();

    return response()->json([
      'header' => [
        'fpohid'     => $header->fpohid,
        'fpono'     => $header->fpono,
        'fsupplier' => trim($header->fsupplier ?? ''),
        'fpodate'   => optional($header->fpodate)->format('Y-m-d H:i:s'),
      ],
      'items'  => $items,
    ]);
  }

  public function pickablePB(Request $request)
  {
    $search   = trim((string) $request->get('search', ''));
    $perPage  = (int) $request->get('per_page', 10);

    $query = PenerimaanPembelianHeader::query()
      ->leftJoin('mssupplier', 'trstockmt.fsupplier', '=', 'mssupplier.fsupplierid')
      ->select([
        'trstockmt.fstockmtid',
        'trstockmt.fstockmtno',
        'mssupplier.fsuppliername',
        'trstockmt.fstockmtdate',
      ])
      ->where('trstockmt.fstockmtcode', 'TER');

    if ($search !== '') {
      $likeOp = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
      $query->where(function ($q) use ($search, $likeOp) {
        $q->where('trstockmt.fstockmtno', $likeOp, "%{$search}%")
          ->orWhere('mssupplier.fsuppliername', $likeOp, "%{$search}%")
          ->orWhereRaw("TO_CHAR(trstockmt.fstockmtdate, 'YYYY-MM-DD HH24:MI:SS') {$likeOp} ?", ["%{$search}%"]);
      });
    }

    $query->orderByDesc('trstockmt.fstockmtdate')
      ->orderByDesc('trstockmt.fstockmtid');

    $paginated = $query->paginate($perPage)->withQueryString();

    $rows = collect($paginated->items())->map(function ($t) {
      return [
        'fstockmtid'     => $t->fstockmtid,
        'fstockmtno'     => $t->fstockmtno,
        'fsupplier' => trim($t->fsuppliername ?? ''),
        'fstockmtdate'   => $t->fstockmtdate ? \Carbon\Carbon::parse($t->fstockmtdate)->format('Y-m-d H:i:s') : 'No Date',
        'items_url' => route('fakturpembelian.itemsPB', $t->fstockmtid),
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
      'current_page' => $paginated->currentPage(),
      'last_page'    => $paginated->lastPage(),
      'total'        => $paginated->total(),
    ]);
  }

  public function itemsPB($id)
  {
    $header = PenerimaanPembelianHeader::where('fstockmtid', $id)->firstOrFail();

    $items = PenerimaanPembelianDetail::where('trstockdt.fstockmtid', $header->fstockmtid)
      ->leftJoin('msprd as m', 'm.fprdid', '=', 'trstockdt.fprdcodeid')
      ->select([
        'trstockdt.fstockdtid as frefdtno',
        'trstockdt.fprdcode as fitemcode',
        'm.fprdname as fitemname',
        'trstockdt.fqty',
        'trstockdt.fsatuan as fsatuan',
        'trstockdt.fprice',
        'trstockdt.fdiscpersen',
        'trstockdt.fbiaya',
        'trstockdt.ftotprice as fharga',
        DB::raw('0::numeric as fdiskon')
      ])
      ->orderBy('trstockdt.fprdcode')
      ->get();

    return response()->json([
      'header' => [
        'fstockmtid'     => $header->fstockmtid,
        'fstockmtno'     => $header->fstockmtno,
        'fsupplier' => trim($header->fsupplier ?? ''),
        'fstockmtdate'   => optional($header->fstockmtdate)->format('Y-m-d H:i:s'),
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
      ->leftJoin('msprd as p', 'p.fprdid', '=', 'trstockdt.fprdcodeid')
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
    Log::info("FakturPembelian@store: Memulai proses simpan.", ['payload' => $request->all()]);

    try {
      // 1) VALIDASI
      $request->validate([
        'fstockmtdate' => ['required', 'date'],
        'fsupplier'    => ['required', 'string', 'max:30'],
        'ftypebuy'     => ['nullable', 'integer'],
        'fprdjadi'     => ['required_if:ftypebuy,1'],
        'fitemcode'    => ['required', 'array', 'min:1'],
        'fitemcode.*'  => ['required', 'string', 'max:50'],
        'fqty'         => ['required', 'array'],
        'fqty.*'       => ['numeric', 'min:0.001'],
        'fprice'       => ['required', 'array'],
        'fprice.*'     => ['numeric', 'min:0'],
      ], [
        'fprdjadi.required_if' => 'Account wajib diisi ketika tipe pembelian adalah Non Stok.',
      ]);

      // 2) HEADER FIELDS
      $fstockmtno   = trim((string)$request->input('fstockmtno'));
      $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
      $fsupplier    = trim((string)$request->input('fsupplier'));
      $ffrom        = $request->input('fwhid');
      $fket         = trim((string)$request->input('fket', ''));
      $fbranchcode  = $request->input('fbranchcode');
      $faccid       = $request->input('faccid');
      $fprdjadi     = $request->input('fprdjadi');
      $ftempohr     = $request->input('ftempohr');
      $ftypebuy     = $request->input('ftypebuy');
      $frefno       = $request->input('frefno');
      $frefpo       = $request->input('frefpo');
      $frate        = max(1, (float)$request->input('frate', 1));
      $userid       = auth('sysuser')->user()->fsysuserid ?? 'admin';
      $now          = now();
      $fincludeppn = $request->boolean('fincludeppn') ? 1 : 0;
      $fapplyppn = $request->boolean('fapplyppn') ? 1 : 0;

      // 3) DETAIL ARRAYS
      $codes   = $request->input('fitemcode', []);
      $satuans = $request->input('fsatuan', []);
      $refdtnos = $request->input('frefdtno', []);
      $refdtids = $request->input('frefdtid', []);
      $qtys    = $request->input('fqty', []);
      $prices  = $request->input('fprice', []);
      $biayas  = $request->input('fbiaya', []);
      $discs   = $request->input('fdiscpersen', []);
      $descs   = $request->input('fdesc', []);

      // 4) BUILD ROWS
      $uniqueCodes = array_values(array_unique(array_filter(array_map(fn($c) => trim((string)$c), $codes))));
      $prodMeta    = DB::table('msprd')->whereIn('fprdcode', $uniqueCodes)->get()->keyBy('fprdcode');

      $rowsDt   = [];
      $subtotal = 0.0;

      $lineCounter = 1;

      for ($i = 0; $i < count($codes); $i++) {
        $code = trim((string)($codes[$i] ?? ''));
        $qty  = (float)($qtys[$i] ?? 0);
        if ($code === '' || $qty <= 0) continue;

        $meta = $prodMeta[$code] ?? null;
        if (!$meta) continue;

        $sat = mb_substr(trim((string)($satuans[$i] ?? $meta->fsatuankecil ?? '')), 0, 5);
        $qtyKecil = ($sat === $meta->fsatuanbesar && ($meta->fqtykecil ?? 0) > 0) ? $qty * (float)$meta->fqtykecil : $qty;

        $price = (float)($prices[$i] ?? 0);
        $biaya = (float)($biayas[$i] ?? 0);
        $discP = (float)($discs[$i] ?? 0);

        $priceNet = ($price + $biaya) * (1 - ($discP / 100));
        $amount   = $qty * $priceNet;
        $subtotal += $amount;

        $rowsDt[] = [
          'fprdcode'     => $code,
          'fprdcodeid'   => $meta->fprdid,
          'frefdtno'     => trim((string)($refdtnos[$i] ?? '')) ?: null,
          'frefdtid'     => isset($refdtids[$i]) ? (int)$refdtids[$i] : null,
          'fqty'         => $qty,
          'fqtykecil'    => $qtyKecil,
          'fqtyremain'   => $qtyKecil,
          'fprice'       => $price,
          'fbiaya'       => $biaya,
          'fpricenet'    => $priceNet,
          'fprice_rp'    => $price * $frate,
          'ftotprice'    => $amount,
          'ftotprice_rp' => $amount * $frate,
          'fusercreate'  => $userid,
          'fdatetime'    => $now,
          'fcode'        => '0',
          'fdesc'        => trim((string)($descs[$i] ?? '')) ?: null,
          'fdiscpersen'  => (string)$discP,
          'fsatuan'      => $sat,
          'fclosedt'     => '0',
        ];
      }

      $ppnAmount  = (float) $request->input('famountpopajak', 0);
      $grandTotal = $subtotal + $ppnAmount;

      // 5) TRANSACTION
      DB::transaction(function () use (
        $request,
        $fstockmtdate,
        $fsupplier,
        $ffrom,
        $fket,
        $fbranchcode,
        $frate,
        $userid,
        $now,
        $ftempohr,
        $fincludeppn,
        $fapplyppn,
        $ftypebuy,
        $frefno,
        $frefpo,
        $faccid,
        $fprdjadi,
        &$fstockmtno,
        &$rowsDt,
        $subtotal,
        $ppnAmount,
        $grandTotal
      ) {
        // A. Resolve Cabang
        $rawBranch = trim((string)$fbranchcode);
        $kodeCabang = DB::table('mscabang')
          ->where('fcabangid', is_numeric($rawBranch) ? (int)$rawBranch : -1)
          ->orWhere('fcabangkode', $rawBranch)
          ->value('fcabangkode') ?? 'NA';

        $yy = $fstockmtdate->format('y');
        $mm = $fstockmtdate->format('m');
        $fstockmtcode = 'BUY';

        // B. Penomoran
        if (empty($fstockmtno)) {
          $prefix = "$fstockmtcode.$kodeCabang.$yy.$mm.";
          $lockKey = crc32("STOCKMT|$fstockmtcode|$kodeCabang|" . $fstockmtdate->format('Y-m'));
          DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

          $last = DB::table('trstockmt')
            ->where('fstockmtno', 'like', "$prefix%")
            ->selectRaw("MAX(CAST(split_part(fstockmtno, '.', 5) AS int)) AS lastno")
            ->value('lastno');

          $fstockmtno = $prefix . str_pad((string)((int)$last + 1), 4, '0', STR_PAD_LEFT);
        }

        // C. Insert Header
        $masterId = DB::table('trstockmt')->insertGetId([
          'fstockmtno'      => $fstockmtno,
          'fstockmtcode'    => $fstockmtcode,
          'fstockmtdate'    => $fstockmtdate,
          'fsupplier'       => $fsupplier,
          'frate'           => $frate,
          'famount'         => round($subtotal, 2),
          'famount_rp'      => round($subtotal * $frate, 2),
          'famountpajak'    => round($ppnAmount, 2),
          'famountpajak_rp' => round($ppnAmount * $frate, 2),
          'famountmt'       => round($grandTotal, 2),
          'famountmt_rp'    => round($grandTotal * $frate, 2),
          'famountremain'   => round($grandTotal, 2),
          'famountremain_rp' => round($grandTotal * $frate, 2),
          'frefno'          => $frefno,
          'frefpo'          => $frefpo,
          'ffrom'           => $ffrom,
          'fprdjadi'        => $fprdjadi,
          'fprdjadiid'      => $faccid,
          'fket'            => $fket,
          'fusercreate'     => $userid,
          'fdatetime'       => $now,
          'fbranchcode'     => $kodeCabang,
          'fincludeppn'    => $fincludeppn,
          'fapplyppn'      => $fapplyppn,
          'fppnpersen'      => $request->input('ppn_rate', 0),
          'ftempohr'        => $ftempohr,
          'ftypebuy'        => $ftypebuy,
          'fprdout'         => '0',
          'fsudahtagih'     => '0',
          'fprint'          => 0,
        ], 'fstockmtid');

        // D. Insert Details
        foreach ($rowsDt as &$r) {
          $r['fstockmtid']   = $masterId;
          $r['fstockmtno']   = $fstockmtno;
          $r['fstockmtcode'] = $fstockmtcode;
        }
        DB::table('trstockdt')->insert($rowsDt);
      });

      return redirect()->route('fakturpembelian.create')
        ->with('success', "Faktur Pembelian $fstockmtno berhasil disimpan.");
    } catch (\Exception $e) {
      Log::error("FakturPembelian@store ERROR: " . $e->getMessage());
      return back()->withInput()->withErrors(['error' => 'Gagal simpan: ' . $e->getMessage()]);
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
          ->join('msprd', 'msprd.fprdid', '=', 'trstockdt.fprdcodeid')
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
    $currentAccount = trim($fakturpembelian->fprdjadi ?? '');
    $currentAccountRecord = $accounts->firstWhere('faccount', trim($fakturpembelian->fprdjadi ?? ''));
    $currentAccountId = $currentAccountRecord?->faccid ?? '';
    $currentAccountName = $currentAccountRecord?->faccname ?? ''; // ← TAMBAH INI

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
      'currentAccount'   => $currentAccount,
      'currentAccountId' => $currentAccountId,
      'currentAccountName' => $currentAccountName,
      'fakturpembelian' => $fakturpembelian,
      'savedItems' => $savedItems,
      'ppnAmount' => (float) ($fakturpembelian->famountpopajak ?? 0),
      'famountponet' => (float) ($fakturpembelian->famountponet ?? 0),
      'famountpo' => (float) ($fakturpembelian->famountpo ?? 0),
      'filterSupplierId' => $request->query('filter_supplier_id'),
      'action' => 'edit'
    ]);
  }
  public function view(Request $request, $fstockmtid)
  {
    $suppliers = Supplier::orderBy('fsuppliername', 'asc')
      ->get(['fsupplierid', 'fsuppliername']);

    // 1. PINDAHKAN INI KE ATAS
    // Ambil data Header (trstockmt) DULU
    $fakturpembelian = PenerimaanPembelianHeader::with([
      'details' => function ($query) {
        $query
          ->join('msprd', 'msprd.fprdid', '=', 'trstockdt.fprdcodeid')
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
    $currentAccount = trim($fakturpembelian->fprdjadi ?? '');
    $currentAccountRecord = $accounts->firstWhere('faccount', trim($fakturpembelian->fprdjadi ?? ''));
    $currentAccountId = $currentAccountRecord?->faccid ?? '';
    $currentAccountName = $currentAccountRecord?->faccname ?? ''; // ← TAMBAH INI

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

    return view('fakturpembelian.view', [
      'suppliers' => $suppliers,
      'selectedSupplierCode' => $selectedSupplierCode,
      'fcabang' => $fcabang,
      'fbranchcode' => $fbranchcode,
      'warehouses' => $warehouses,
      'products' => $products,
      'accounts' => $accounts,
      'productMap' => $productMap,
      'currentAccount'   => $currentAccount,
      'currentAccountId' => $currentAccountId,
      'currentAccountName' => $currentAccountName,
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
      $validatedData = $request->validate([
        'fstockmtno' => ['nullable', 'string', 'max:100'],
        'fstockmtdate' => ['required', 'date'],
        'fsupplier' => ['required', 'string', 'max:30'],
        'ffrom' => ['nullable', 'integer', 'exists:mswh,fwhid'],
        'fket' => ['nullable', 'string', 'max:50'],
        'fbranchcode' => ['nullable', 'string', 'max:20'],
        'faccid' => ['nullable', 'integer'],
        'fitemcode' => ['required', 'array', 'min:1'],
        'fitemcode.*' => ['required', 'string', 'max:50'],
        'fsatuan' => ['nullable', 'array'],
        'fsatuan.*' => ['nullable', 'string', 'max:20'],
        'frefdtno' => ['nullable', 'array'],
        'frefdtno.*' => ['nullable', 'string', 'max:20'],
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
        'famount' => ['nullable', 'numeric', 'min:0'],
        'famountpajak' => ['nullable', 'numeric', 'min:0'],
        'famountmt' => ['nullable', 'numeric', 'min:0'],
        'fincludeppn' => ['nullable', 'boolean'],
        'fapplyppn' => ['nullable', 'boolean'],
        'ppn_rate' => ['nullable', 'numeric', 'min:0'],
        'fjatuhtempo' => ['nullable', 'date'],
        'ftempohr' => ['nullable', 'integer'],
        'ftypebuy' => ['nullable', 'integer'],
        'frefno' => ['nullable', 'string'],
        'frefpo' => ['nullable', 'string'],
        'fprdjadi' => ['required_if:ftypebuy,1'],
      ], [
        'fstockmtdate.required' => 'Tanggal transaksi wajib diisi.',
        'fsupplier.required' => 'Supplier wajib diisi.',
        'fitemcode.required' => 'Minimal 1 item.',
        'fsatuan.*.max' => 'Satuan di salah satu baris tidak boleh lebih dari 5 karakter.',
        'fprdjadi.required_if' => 'Account wajib diisi ketika tipe pembelian adalah Non Stok.',
      ]);

      // 2. Muat header yang ada
      $header = PenerimaanPembelianHeader::findOrFail($fstockmtid);

      // HEADER FIELDS
      $fstockmtno = $header->fstockmtno;
      $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
      $fsupplier = trim((string)$request->input('fsupplier'));
      $ffrom = $request->input('ffrom');
      $fket = trim((string)$request->input('fket', ''));
      $fbranchcode = $request->input('fbranchcode');
      $faccid = $request->input('faccid');
      $fprdjadi = $request->input('fprdjadi');
      $ftempohr = $request->input('ftempohr');
      $ftypebuy = $request->input('ftypebuy');
      $fcurrency = $request->input('fcurrency', 'IDR');
      $frate = (float)$request->input('frate', 1);
      if ($frate <= 0) $frate = 1;
      $fincludeppn = $request->boolean('fincludeppn') ? 1 : 0;
      $fapplyppn = $request->boolean('fapplyppn') ? 1 : 0;
      $fppnpersen = (float)$request->input('ppn_rate', 0);

      $ppnAmount = (float)$request->input('famountpajak', 0);
      $now = now();

      // DETAIL ARRAYS
      $codes = $request->input('fitemcode', []);
      $satuans = $request->input('fsatuan', []);
      $refdtno = $request->input('frefdtno', []);
      $qtys = $request->input('fqty', []);
      $prices = $request->input('fprice', []);
      $biayas = $request->input('fbiaya', []);
      $discs = $request->input('fdiscpersen', []);
      $descs = $request->input('fdesc', []);

      // LOAD PRODUCT METADATA
      $uniqueCodes = array_values(array_unique(array_filter(array_map(fn($c) => trim((string)$c), $codes))));
      $prodMeta = DB::table('msprd')
        ->whereIn('fprdcode', $uniqueCodes)
        ->get(['fprdid', 'fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2', 'fqtykecil'])
        ->keyBy('fprdcode');

      // BUILD DETAIL ROWS
      $rowsDt = [];
      $subtotal = 0.0;
      $rowCount = count($codes);

      for ($i = 0; $i < $rowCount; $i++) {
        $code = trim((string)($codes[$i] ?? ''));
        $qty = (float)($qtys[$i] ?? 0);

        if ($code === '' || $qty <= 0) continue;

        $meta = $prodMeta[$code] ?? null;
        if (!$meta) {
          continue;
        }

        $sat = trim((string)($satuans[$i] ?? ''));
        $price = (float)($prices[$i] ?? 0);
        $biaya = (float)($biayas[$i] ?? 0);
        $discP = (float)($discs[$i] ?? 0);
        $desc = trim((string)($descs[$i] ?? ''));

        // Konversi Satuan & Qty Kecil
        $qtyKecil = $qty;
        if ($sat === $meta->fsatuanbesar) {
          $qtyKecil = $qty * (float)($meta->fqtykecil ?? 1);
        }

        $priceNet = $price * (1 - ($discP / 100));
        $amount = $qty * $priceNet;
        $subtotal += $amount;

        $rnour = $nourefs[$i] ?? null;
        $finalNour = is_numeric($rnour) ? (int)$rnour : null;

        $rowsDt[] = [
          'fprdcode'    => $code,
          'fprdcodeid'  => $meta->fprdid,
          'frefdtno'    => !empty($refdtno[$i]) ? $refdtno[$i] : null,
          'fqty'        => $qty,
          'fqtyremain'  => $qtyKecil,
          'fprice'      => $price,
          'fbiaya'      => $biaya,
          'fpricenet'   => $price + $biaya,
          'fprice_rp'   => $price * $frate,
          'ftotprice'   => $amount,
          'ftotprice_rp' => $amount * $frate,
          'fuserupdate' => (Auth::user()->fname ?? 'system'),
          'fdatetime'   => $now,
          'fketdt'      => $desc ?: null,
          'fcode'       => '0',
          'fdiscpersen' => (string)$discP,
          'fsatuan'     => $sat,
          'fqtykecil'   => $qtyKecil,
          'fclosedt'    => '0',
        ];
      }

      if (empty($rowsDt)) {
        return back()->withInput()->withErrors(['detail' => 'Minimal satu item valid (Kode, Satuan, Qty > 0).']);
      }

      $grandTotal = $subtotal + $ppnAmount;

      // DATABASE TRANSACTION
      DB::transaction(function () use (
        $request,
        $header,
        $fstockmtdate,
        $fsupplier,
        $ffrom,
        $fket,
        $fcurrency,
        $frate,
        $fincludeppn,
        $fapplyppn,
        $fppnpersen,
        $now,
        $ftempohr,
        $ftypebuy,
        &$fstockmtno,
        &$rowsDt,
        $subtotal,
        $ppnAmount,
        $grandTotal,
        $faccid,
        $fprdjadi
      ) {

        // Logika Branch yang diperbaiki untuk PostgreSQL
        $kodeCabang = 'NA';
        if (!empty($fbranchcode)) {
          $qCabang = DB::table('mscabang');

          if (is_numeric($fbranchcode)) {
            // Jika angka, cari ke ID
            $qCabang->where('fcabangid', (int)$fbranchcode);
          } else {
            // Jika huruf (seperti 'BG'), langsung cari ke Kode
            $qCabang->where('fcabangkode', $fbranchcode);
          }

          $cabang = $qCabang->first();
          $kodeCabang = $cabang ? $cabang->fcabangkode : 'NA';
        }

        // Update Header
        $header->update([
          'fstockmtdate' => $fstockmtdate,
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
          'ffrom' => $ffrom,
          'fprdjadi' => $fprdjadi,
          'fprdjadiid' => $faccid,
          'fket' => $fket,
          'fuserupdate' => (Auth::user()->fname ?? 'system'),
          'fdatetime' => $now,
          'fbranchcode' => $kodeCabang, // Menggunakan hasil pencarian aman tadi
          'ftempohr' => $ftempohr,
          'fincludeppn'    => $fincludeppn,
          'fapplyppn'      => $fapplyppn,
          'fppnpersen'      => $fppnpersen,
          'ftypebuy' => $ftypebuy,
          'fjatuhtempo' => $request->input('fjatuhtempo') ? \Carbon\Carbon::parse($request->input('fjatuhtempo'))->startOfDay() : null,
        ]);

        // Hapus detail lama dan masukkan yang baru
        $header->details()->delete();

        $nextNouRef = 1;
        foreach ($rowsDt as &$r) {
          $r['fstockmtid'] = $header->fstockmtid;
          $r['fstockmtcode'] = 'BUY';
          $r['fstockmtno'] = $fstockmtno;
        }

        DB::table('trstockdt')->insert($rowsDt);
      });

      return redirect()
        ->route('fakturpembelian.index')
        ->with('success', "Faktur Pembelian {$fstockmtno} berhasil di-update.");
    } catch (\Exception $e) {

      return back()
        ->withInput()
        ->withErrors(['error' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
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
          ->join('msprd', 'msprd.fprdid', '=', 'trstockdt.fprdcodeid')
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
    $currentAccount = trim($fakturpembelian->fprdjadi ?? '');
    $currentAccountRecord = $accounts->firstWhere('faccount', trim($fakturpembelian->fprdjadi ?? ''));
    $currentAccountId = $currentAccountRecord?->faccid ?? '';
    $currentAccountName = $currentAccountRecord?->faccname ?? ''; // ← TAMBAH INI
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
      'currentAccount'   => $currentAccount,
      'currentAccountId' => $currentAccountId,
      'currentAccountName' => $currentAccountName,
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

      return redirect()->route('fakturpembelian.index')->with('success', 'Data Faktur Pembelian ' . $fakturpembelian->fpono . ' berhasil dihapus.');
    } catch (\Exception $e) {
      // Jika terjadi kesalahan saat menghapus, kembali ke halaman delete dengan pesan error
      return redirect()->route('fakturpembelian.delete', $fstockmtid)->with('error', 'Gagal menghapus data: ' . $e->getMessage());
    }
  }
}
