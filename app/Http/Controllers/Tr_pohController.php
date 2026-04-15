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

    // Ambil tahun-tahun yang tersedia
    $availableYears = Tr_poh::selectRaw('DISTINCT EXTRACT(YEAR FROM fdatetime) as year')
      ->whereNotNull('fdatetime')
      ->orderByRaw('EXTRACT(YEAR FROM fdatetime) DESC')
      ->pluck('year');

    // --- Handle Request AJAX dari DataTables ---
    if ($request->ajax()) {

      // Gunakan prefix tabel 'tr_poh.*' untuk menghindari kolom ganda (ambiguous)
      $query = Tr_poh::query()
        ->select([
          'tr_poh.fpohid',
          'tr_poh.fpono',
          'tr_poh.fsupplier',
          'tr_poh.fpodate',
          'tr_poh.fclose',
          'tr_poh.fusercreate',
          'tr_poh.fapproval',
          'tr_poh.fdatetime',
          'mssupplier.fsuppliername',
          'tr_prd.fprno',
          DB::raw('STRING_AGG(DISTINCT tr_pod.frefdtno, \', \') as frefdtno'),
        ])
        ->leftJoin('mssupplier', 'tr_poh.fsupplier', '=', 'mssupplier.fsupplierid')
        ->leftJoin('tr_pod', 'tr_poh.fpohid', '=', 'tr_pod.fpohid')
        ->leftJoin('tr_prd', function ($join) {
          $join->whereRaw('tr_prd.fprdid = CAST(tr_pod.frefdtid AS INTEGER)');
        });

      $totalRecords = Tr_poh::count();

      // Handle Search - Beri prefix tabel agar tidak bingung
      if ($search = $request->input('search.value')) {
        $query->where(function ($q) use ($search) {
          $q->where('tr_poh.fpono',       'ILIKE', "%{$search}%")
            ->orWhere('tr_poh.fsupplier', 'ILIKE', "%{$search}%")
            ->orWhere('mssupplier.fsuppliername', 'ILIKE', "%{$search}%")
            ->orWhere('tr_pod.frefdtno',  'ILIKE', "%{$search}%");
        });
      }

      // Filter status
      $statusFilter = $request->query('status', 'active');
      if ($statusFilter === 'active') {
        $query->where('tr_poh.fclose', '0');
      } elseif ($statusFilter === 'nonactive') {
        $query->where('tr_poh.fclose', '1');
      }

      // Filter tahun & bulan
      if ($year) {
        $query->whereRaw('EXTRACT(YEAR FROM tr_poh.fdatetime) = ?', [$year]);
      }
      if ($month) {
        $query->whereRaw('EXTRACT(MONTH FROM tr_poh.fdatetime) = ?', [$month]);
      }

      // Karena join ke child (tr_pod), gunakan groupBy agar baris tidak double di index
      $query->groupBy(
        'tr_poh.fpohid',
        'tr_poh.fpono',
        'tr_poh.fsupplier',
        'tr_poh.fpodate',
        'tr_poh.fclose',
        'tr_poh.fusercreate',
        'tr_poh.fapproval',
        'tr_poh.fdatetime', // Pastikan semua kolom tr_poh masuk atau gunakan agregat
        'mssupplier.fsuppliername',
        'tr_prd.fprno'
      );

      $filteredRecords = DB::table(DB::raw("({$query->toSql()}) as sub"))
        ->mergeBindings($query->getQuery())
        ->count();

      // Sorting
      $orderColIdx = $request->input('order.0.column', 0);
      $orderDir = $request->input('order.0.dir', 'asc');
      $sortableColumns = ['fpono', 'fsupplier', 'fpodate', 'fclose', 'fusercreate', 'fapproval', 'fsuppliername'];

      if (isset($sortableColumns[$orderColIdx])) {
        $query->orderBy($sortableColumns[$orderColIdx], $orderDir);
      }

      // Paginasi
      $start = $request->input('start', 0);
      $length = $request->input('length', 10);
      $records = $query->skip($start)->take($length)->get();

      // Format Data
      $data = $records->map(function ($row) {
        return [
          'fpono'         => $row->fpono,
          'fpohid'        => $row->fpohid,
          'fsupplier'     => $row->fsupplier,
          'fpodate'       => $row->fpodate,
          'fclose'        => $row->fclose == '1' ? 'Close' : 'Open',
          'fusercreate'   => $row->fusercreate,
          'fapproval'     => $row->fapproval,
          'fsuppliername' => $row->fsuppliername,
          'fprno'         => $row->fprno, // Kolom dari tr_prd
          'frefdtno'      => $row->frefdtno, // tambah ini
        ];
      });

      return response()->json([
        'draw'            => intval($request->input('draw')),
        'recordsTotal'    => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data'            => $data
      ]);
    }

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
      )
      ->whereIn('tr_prh.fclose', ['0', ''])
      ->whereIn('tr_prh.fprdin', ['0', '']);

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
    $header = Tr_prh::where('fprhid', $id)->firstOrFail();
    $fprhid = (int) $header->fprhid;

    $items = DB::table('tr_prd as d')
      ->leftJoin('msprd as m', 'm.fprdid', '=', 'd.fprdcodeid')
      ->leftJoin(DB::raw('(
            SELECT
                CAST(frefdtid AS BIGINT) AS fprdlineid,
                SUM(fqtykecil) AS fqtypo
            FROM tr_pod
            WHERE frefdtid IS NOT NULL
            GROUP BY CAST(frefdtid AS BIGINT)
        ) as o'), 'o.fprdlineid', '=', 'd.fprdid')
      ->where('d.fprhid', $fprhid)
      ->select([
        DB::raw('d.fprdcodeid::text as frefdtno'),
        'm.fprdcode as fitemcode',
        'm.fprdname as fitemname',
        'd.fqty',
        'd.fsatuan',
        'd.fdesc',
        'd.fketdt',
        'd.fprhid',
        'd.fqtyremain',
        DB::raw('COALESCE(d.fprice, 0) as fprice'),
        DB::raw('0::numeric as fdisc'),
        DB::raw('d.fprhid::text as fnouref'),
        DB::raw('d.fprdid::text as frefdtid'),
        'm.fsatuankecil',
        'm.fsatuanbesar',
        'm.fsatuanbesar2',
        'm.fqtykecil',
        'm.fqtykecil2',
        DB::raw('COALESCE(o.fqtypo, 0) AS fqtypo'),
      ])
      ->orderBy('d.fprdcodeid')
      ->get()
      ->map(function ($item) {
        $qty       = (float) $item->fqty;
        $fqtypo    = (float) ($item->fqtypo ?? 0);
        $satuan    = trim((string) $item->fsatuan);
        $satKecil  = trim((string) ($item->fsatuankecil  ?? ''));
        $satBesar  = trim((string) ($item->fsatuanbesar  ?? ''));
        $satBesar2 = trim((string) ($item->fsatuanbesar2 ?? ''));
        $rasio     = (float) ($item->fqtykecil  ?? 0);
        $rasio2    = (float) ($item->fqtykecil2 ?? 0);

        // Sisa dalam satuan kecil: gunakan kolom tr_prd.fqtyremain (dipelihara saat PO)
        $sisaKecil = max(0, (float) ($item->fqtyremain ?? 0));

        return [
          'frefdtno'      => $item->frefdtno,
          'fitemcode'     => $item->fitemcode,
          'fitemname'     => $item->fitemname,
          'fqty'          => $qty,
          'maxqty'        => $sisaKecil,
          'maxqty_satuan' => $satKecil,
          'fsatuan'       => $satuan,
          'fdesc'         => $item->fdesc ?? '',
          'fketdt'        => $item->fketdt ?? '',
          'fprhid'        => $item->fprhid,
          'fprice'        => (float) $item->fprice,
          'fdisc'         => 0,
          'fnouref'       => $item->fnouref,
          'frefdtid'      => $item->frefdtid,
          'fqtypo'        => $fqtypo,
          'fqtyremain'    => (float) ($item->fqtyremain ?? 0),
          'fqtypr'        => $qty,
          'fsatuankecil'  => $satKecil,
          'fsatuanbesar'  => $satBesar,
          'fsatuanbesar2' => $satBesar2,
          'fqtykecil'     => $rasio,
          'fqtykecil2'    => $rasio2,
        ];
      });

    return response()->json([
      'header' => [
        'fprhid'    => $header->fprhid,
        'fprno'     => $header->fprno,
        'fsupplier' => trim($header->fsupplier ?? ''),
        'fprdate'   => optional($header->fprdate)->format('Y-m-d H:i:s'),
      ],
      'items' => $items,
    ]);
  }

  /**
   * Konversi qty baris PO ke satuan kecil (sesuai logika PR).
   */
  private function qtyPoToKecil(?object $product, string $sat, float $qty): float
  {
    if (!$product) {
      return $qty;
    }
    $sat = trim($sat);
    $besar = trim((string) ($product->fsatuanbesar ?? ''));
    $besar2 = trim((string) ($product->fsatuanbesar2 ?? ''));
    $rasio = (float) ($product->fqtykecil ?? 0);
    $rasio2 = (float) ($product->fqtykecil2 ?? 0);
    if ($sat !== '' && $besar !== '' && strcasecmp($sat, $besar) === 0 && $rasio > 0) {
      return $qty * $rasio;
    }
    if ($sat !== '' && $besar2 !== '' && strcasecmp($sat, $besar2) === 0 && $rasio2 > 0) {
      return $qty * $rasio2;
    }

    return $qty;
  }

  /**
   * @param  array<int, array<string, mixed>>  $rowsPod
   * @return array<int, float>
   */
  private function aggregatePrdUsageByPrd(array $rowsPod): array
  {
    $agg = [];
    foreach ($rowsPod as $r) {
      $fid = (int) ($r['frefdtid'] ?? 0);
      if ($fid <= 0) {
        continue;
      }
      $agg[$fid] = ($agg[$fid] ?? 0) + (float) ($r['fqtykecil'] ?? 0);
    }

    return $agg;
  }

  /**
   * Kurangi tr_prd.fqtyremain per baris PR (satuan kecil), dengan kunci baris.
   *
   * @param  array<int, float>  $aggregateByPrd  fprdid => total fqtykecil
   */
  private function validateAndDeductPrdRemain(array $aggregateByPrd): void
  {
    foreach ($aggregateByPrd as $fprdid => $need) {
      $fprdid = (int) $fprdid;
      if ($fprdid <= 0 || $need <= 0) {
        continue;
      }
      $row = DB::table('tr_prd')->where('fprdid', $fprdid)->lockForUpdate()->first();
      if (!$row) {
        throw new \RuntimeException('Baris detail PR tidak ditemukan.');
      }
      $remain = (float) ($row->fqtyremain ?? 0);
      if ($need > $remain + 1e-4) {
        throw new \RuntimeException('Qty PO melebihi sisa PR (fqtyremain). Tidak dapat menyimpan.');
      }
      $newRemain = max(0, round($remain - $need, 6));
      DB::table('tr_prd')->where('fprdid', $fprdid)->update(['fqtyremain' => $newRemain]);
    }
  }

  /**
   * Kembalikan fqtyremain saat baris PO dihapus/diganti (nilai = fqtykecil yang pernah dipakai).
   */
  private function restorePrdRemainFromPodRows($pods): void
  {
    foreach ($pods as $pod) {
      $fid = (int) ($pod->frefdtid ?? 0);
      if ($fid <= 0) {
        continue;
      }
      $q = (float) ($pod->fqtykecil ?? 0);
      if ($q <= 0) {
        continue;
      }
      DB::table('tr_prd')->where('fprdid', $fid)->update([
        'fqtyremain' => DB::raw('COALESCE(fqtyremain,0) + ' . $q),
      ]);
    }
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
      ->whereRaw("trim(d.fprdcode) = ?", [$fprdcode])
      ->whereRaw("trim(m.fsupplier::text) = ?", [$fsupplier])
      ->whereRaw("trim(d.fsatuan) = ?", [$fsatuan])
      ->orderBy('m.fpodate', 'desc')
      ->select('d.fprice', 'd.fdisc')
      ->first();

    return response()->json([
      'found'  => (bool) $row,
      'fprice' => $row ? (float) $row->fprice : 0,
      'fdisc'  => $row ? (float) $row->fdisc  : 0,
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
      'fqtykecil',
      'fqtykecil2',
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
      'fqty.*'       => ['numeric', 'gt:0', 'min:0'],

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
      'fqty.*.gt' => 'Harap hapus data atau isi qty data pada detail item (Qty tidak boleh 0).',
    ]);

    // HEADER VALUES
    $fpodate     = Carbon::parse($request->fpodate)->startOfDay();
    $fkirimdate  = $request->filled('fkirimdate') ? Carbon::parse($request->fkirimdate)->startOfDay() : null;
    $fpohid       = $request->input('fpohid'); // can be null; we will generate if empty
    $fincludeppn = $request->boolean('fincludeppn') ? 1 : 0;
    $fapplyppn = $request->boolean('fapplyppn') ? 1 : 0;
    $userid      = auth('sysuser')->user()->fname ?? 'admin';
    $now         = now();

    // DETAIL ARRAYS
    $codes   = $request->input('fitemcode', []);
    $satuans = $request->input('fsatuan', []);
    $refdtno = $request->input('frefdtno', []);
    $frefdtids = $request->input('frefdtid', []);
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
      $frefdtid = (int) ($frefdtids[$i] ?? 0);

      if ($code === '' || $qty <= 0) continue;

      if ($sat === '') $sat = $pickDefaultSat($code);
      $sat = mb_substr($sat, 0, 20);
      if ($sat === '') continue;

      $productId = (int) (($prodMeta[$code]->fprdid ?? null) ?? 0);
      if ($productId === 0) continue;

      $product = DB::table('msprd')
        ->where('fprdcode', $code)
        ->select('fprdid', 'fprdcode', 'fsatuanbesar', 'fsatuanbesar2', 'fqtykecil', 'fqtykecil2')
        ->first();

      $qtyKecil = $this->qtyPoToKecil($product, $sat, $qty);

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
        'fdesc'       => $desc,
        'fqtykecil'   => $qtyKecil,
        'fqtyremain'  => $qtyKecil,
        'frefdtid'    => $frefdtid ?: null,
      ];
    }

    if (empty($rowsPod)) {
      return back()->withInput()->withErrors(['detail' => 'Minimal satu item valid (Kode, Satuan, Qty > 0).']);
    }

    $prdAgg = $this->aggregatePrdUsageByPrd($rowsPod);

    // TRANSACTION
    $fpono = null;
    try {
    DB::transaction(function () use (
      $request,
      $fpodate,
      $fkirimdate,
      $fincludeppn,
      $fapplyppn,
      $userid,
      $now,
      $rowsPod,
      $fpohid,
      $totalHarga,
      $ppnAmount,
      $grandTotal,
      $prdAgg,
      &$fpono
    ) {
      $this->validateAndDeductPrdRemain($prdAgg);

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
        'fapplyppn'      => $fapplyppn,
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

      // UPDATE STOK - gunakan qtyKecil hasil konversi, bukan qty mentah
      foreach ($rowsPod as $row) {
        DB::table('msprd')
          ->where('fprdcode', $row['fprdcode'])
          ->update([
            'fminstock'  => DB::raw("CAST(fminstock AS NUMERIC) - " . $row['fqtyremain']),
            'fupdatedat' => now(),
          ]);
      }

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
        $r['frefdtno'] = $fpono;    // ✅ string → tr_poh.fpono  (misal: "PO.BG.26.03.0008")
      }
      unset($r);

      DB::table('tr_pod')->insert($rowsPod);
    });
    } catch (\RuntimeException $e) {
      return back()->withInput()->withErrors(['detail' => $e->getMessage()]);
    }

    return redirect()
      ->route('tr_poh.create')
      ->with('success', "Data sudah tersimpan. No {$fpono}");
  }

  public function edit(Request $request, $fpohid)
  {
    $suppliers = Supplier::orderBy('fsuppliername', 'asc')
      ->get(['fsupplierid', 'fsuppliername']);

    $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

    $currencies = DB::table('mscurrency')
      ->where(function ($q) {
        $q->whereNull('fnonactive')
          ->orWhere('fnonactive', '0')
          ->orWhere('fnonactive', '');
      })
      ->orderBy('fcurrname')
      ->get(['fcurrid', 'fcurrcode', 'fcurrname', 'frate']);

    $branch = DB::table('mscabang')
      ->when(is_numeric($raw), fn($q) => $q->where('fcabangid', (int) $raw))
      ->when(!is_numeric($raw), fn($q) => $q
        ->where('fcabangkode', $raw)
        ->orWhere('fcabangname', $raw))
      ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

    $fcabang     = $branch->fcabangname ?? (string) $raw;
    $fbranchcode = $branch->fcabangkode ?? (string) $raw;

    $tr_poh = Tr_poh::with(['details' => function ($q) {
      $q->leftJoin('msprd as m', 'm.fprdid', '=', 'tr_pod.fprdid')
        ->leftJoin(DB::raw('(
        SELECT
            fprdcode,
            frefdtno,
            SUM(fqty) AS total_terima
        FROM trstockdt
        WHERE
            (fstockmtcode = \'TER\' OR (fcode = \'P\' AND fstockmtcode = \'BUY\'))
        GROUP BY
            fprdcode,
            frefdtno
    ) as r'), function ($join) {
          $join->on('r.frefdtno', '=', 'tr_pod.frefdtno')   // ← join via frefdtno
            ->on('r.fprdcode', '=', 'm.fprdcode');
        })
        ->select(
          'tr_pod.*',
          'm.fprdcode as fitemcode',
          'm.fprdname',
          'm.fsatuankecil',
          'm.fsatuanbesar',
          'm.fsatuanbesar2',
          'm.fqtykecil',
          'm.fqtykecil2',
          DB::raw("COALESCE((SELECT pr.fqty FROM tr_prd pr WHERE tr_pod.frefdtid IS NOT NULL AND pr.fprdid = CAST(tr_pod.frefdtid AS INTEGER) LIMIT 1), 0) as fqtypr"),
          DB::raw("COALESCE((SELECT pr.fsatuan FROM tr_prd pr WHERE tr_pod.frefdtid IS NOT NULL AND pr.fprdid = CAST(tr_pod.frefdtid AS INTEGER) LIMIT 1), '') as fqtypr_satuan"),
          DB::raw("COALESCE((SELECT pr.fqtyremain FROM tr_prd pr WHERE tr_pod.frefdtid IS NOT NULL AND pr.fprdid = CAST(tr_pod.frefdtid AS INTEGER) LIMIT 1), 0) as fqtyremain_pr"),
          DB::raw('COALESCE(r.total_terima, 0) AS fqtyterima'),
        );
    }])->findOrFail($fpohid);

    $existingTerima = DB::table('trstockdt')
      ->where('frefdtno', $tr_poh->fpono)
      ->select(
        'fstockmtno',
        'fdatetime',
        DB::raw('SUM(fqty) as total_qty')
      )
      ->groupBy('fstockmtno', 'fdatetime')
      ->orderBy('fdatetime', 'desc')
      ->get();

    $blockedByTerima = $existingTerima->isNotEmpty();

    $fpohidInt = (int) $tr_poh->fpohid;

    $prQtyMap = DB::table('tr_prd as d')
      ->join('tr_pod as pod', function ($join) use ($fpohidInt) {
        $join->on('pod.frefdtid', '=', 'd.fprhid')
          ->on('pod.fprdid', '=', 'd.fprdcodeid')
          ->where('pod.fpohid', '=', $fpohidInt);
      })
      ->select('pod.fpodid', 'd.fqty as qty_pr')
      ->get()
      ->keyBy('fpodid');

    // Lookup currency berdasarkan fcurrency (integer ID) di tr_poh
    $currentCurrency = DB::table('mscurrency')
      ->where('fcurrid', $tr_poh->fcurrency)
      ->first(['fcurrid', 'fcurrcode', 'fcurrname', 'frate']);

    $products = Product::select(
      'fprdid',
      'fprdcode',
      'fprdname',
      'fsatuankecil',
      'fsatuanbesar',
      'fsatuanbesar2',
      'fqtykecil',
      'fqtykecil2',
      'fminstock'
    )->orderBy('fprdname')->get();

    $productMap = $products->mapWithKeys(function ($p) {
      return [
        $p->fprdid => [
          'id'    => $p->fprdid,
          'name'  => $p->fprdname,
          'units' => array_values(array_filter([
            $p->fsatuankecil,
            $p->fsatuanbesar,
            $p->fsatuanbesar2,
          ])),
          'stock' => $p->fminstock ?? 0,
        ],
      ];
    })->toArray();

    $savedItems = $tr_poh->details->map(function ($d) use ($products, $prQtyMap) {
      $qtyPR    = (float) $d->fqtypr;
      $satPR    = trim((string) $d->fqtypr_satuan);
      $satKecil = trim((string) $d->fsatuankecil);
      $satBesar = trim((string) $d->fsatuanbesar);
      $satBesar2 = trim((string) $d->fsatuanbesar2);
      $rasio    = (float) $d->fqtykecil;
      $rasio2   = (float) $d->fqtykecil2;

      $prod  = $products->firstWhere('fprdcode', $d->fitemcode);

      // Sisa PR dalam satuan kecil dari tr_prd.fqtyremain (+ pemakaian baris PO ini saat edit)
      $fqtyremainDb = (float) ($d->fqtyremain_pr ?? 0);
      $qtyLineKecil = (float) ($d->fqtykecil ?? 0);
      $sisaKecil = max(0, $fqtyremainDb + $qtyLineKecil);

      // Siapkan units untuk dropdown
      $units = array_values(array_filter(array_map('trim', [$satKecil, $satBesar, $satBesar2])));
      $fsatuan = trim((string)$d->fsatuan);
      if ($fsatuan !== '' && !in_array($fsatuan, $units)) {
        array_unshift($units, $fsatuan);
      }
      // Pastikan fsatuan ada di units
      $matchedSatuan = $fsatuan;
      foreach ($units as $u) {
        if (strtolower(trim($u)) === strtolower($fsatuan)) {
          $matchedSatuan = trim($u); // ← pakai versi dari master (sudah di-trim, case benar)
          break;
        }
      }

      $fsatuan = $matchedSatuan;

      if ($fsatuan !== '' && !in_array($fsatuan, $units)) {
        array_unshift($units, $fsatuan);  // ← tetap ada walau tidak di master
      }

      return [
        'uid'       => (string)($d->fpodid   ?? \Illuminate\Support\Str::uuid()),
        'fitemcode' => (string)($d->fitemcode ?? ''),
        'fitemname' => (string)($d->fprdname  ?? ''),
        'fsatuan'   => $fsatuan,   // ← pakai yang sudah di-trim
        'units'     => $units,     // ← sudah include fsatuan
        'frefdtno'  => (string)($d->frefdtno  ?? ''),
        'fnouref'   => (string)($d->fnouref   ?? ''),
        'frefpr'    => (string)($d->frefdtno  ?? ''),
        'fprhid'    => (string)($d->fprhid    ?? ''),
        'fprno'     => (string)($d->frefdtno  ?? ''),
        'fqty'      => (float)($d->fqty    ?? 0),
        'fqtyterima' => (float)($d->fqtyterima ?? 0),
        'fterima'   => (float)($d->fterima ?? 0),
        'fprice'    => (float)($d->fprice  ?? 0),
        'fdisc'     => (float)($d->fdisc   ?? 0),
        'ftotal'    => (float)($d->famount ?? 0),
        'fdesc'     => (string)($d->fdesc  ?? ''),
        'fketdt'    => (string)($d->fketdt ?? ''),
        'frefdtid'  => (string)($d->frefdtid ?? ''), // PASTIKAN INI ADA
        // Data konversi untuk JavaScript
        'fqtypo'    => (float)($d->fqtypo ?? 0),
        'fqtyremain' => $sisaKecil,
        'fqtypr'        => $qtyPR,
        'fqtypr_satuan' => $satPR,
        'fsatuankecil'  => $satKecil,
        'fsatuanbesar'  => $satBesar,
        'fsatuanbesar2' => $satBesar2,
        'fqtykecil'     => $rasio,
        'fqtykecil2'    => $rasio2,

        // maxqty dikirim dalam satuan terkecil (Base)
        'maxqty'        => $sisaKecil,
        'maxqty_satuan' => $satKecil,
      ];
    })->values();

    return view('tr_poh.edit', [
      'suppliers'           => $suppliers,
      'selectedSupplierCode' => $tr_poh->fsupplier,
      'fcabang'             => $fcabang,
      'fbranchcode'         => $fbranchcode,
      'products'            => $products,
      'existingTerima'  => $existingTerima,
      'blockedByTerima' => $blockedByTerima,
      'productMap'          => $productMap,
      'tr_poh'              => $tr_poh,
      'savedItems'          => $savedItems,
      'currencies'          => $currencies,
      'currentCurrency'     => $currentCurrency,   // <-- currency aktif dari join
      'ppnAmount'           => (float)($tr_poh->famountpopajak ?? 0),
      'famountponet'        => (float)($tr_poh->famountponet   ?? 0),
      'famountpo'           => (float)($tr_poh->famountpo      ?? 0),
      'filterSupplierId'    => $request->query('filter_supplier_id'),
      'action'              => 'edit',
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

    $fcabang     = $branch->fcabangname ?? (string) $raw;
    $fbranchcode = $branch->fcabangkode ?? (string) $raw;

    $tr_poh = Tr_poh::with(['details' => function ($q) {
      $q->leftJoin('msprd', function ($j) {
        $j->on('msprd.fprdid', '=', 'tr_pod.fprdid');
      })->select(
        'tr_pod.*',
        'msprd.fprdcode as fitemcode',
        'msprd.fprdname'
      );
    }])->findOrFail($fpohid);

    $currencies = DB::table('mscurrency')
      ->where(function ($q) {
        $q->whereNull('fnonactive')
          ->orWhere('fnonactive', '0')
          ->orWhere('fnonactive', '');
      })
      ->orderBy('fcurrname')
      ->get(['fcurrid', 'fcurrcode', 'fcurrname', 'frate']);

    // Lookup currency aktif dari mscurrency berdasarkan fcurrency (integer ID)
    $currentCurrency = DB::table('mscurrency')
      ->where('fcurrid', $tr_poh->fcurrency)
      ->first(['fcurrid', 'fcurrcode', 'fcurrname', 'frate']);

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
        (string)$p->fprdcode => [
          'id'    => $p->fprdid,
          'name'  => $p->fprdname,
          'units' => array_values(array_filter([
            $p->fsatuankecil,
            $p->fsatuanbesar,
            $p->fsatuanbesar2,
          ])),
          'stock' => $p->fminstock ?? 0,
        ],
      ];
    })->toArray();

    $savedItems = $tr_poh->details->map(function ($d) {
      return [
        'uid'       => (string)($d->fpodid   ?? \Illuminate\Support\Str::uuid()),
        'fitemcode' => (string)($d->fitemcode ?? ''),
        'fitemname' => (string)($d->fprdname  ?? ''),
        'fsatuan'   => (string)($d->fsatuan   ?? ''),
        'frefdtno'  => (string)($d->frefdtno  ?? ''),
        'fprno'     => (string)($d->frefdtno  ?? ''),
        'fqty'      => (float)($d->fqty    ?? 0),
        'fterima'   => (float)($d->fterima ?? 0),
        'fprice'    => (float)($d->fprice  ?? 0),
        'fdisc'     => (float)($d->fdisc   ?? 0),
        'ftotal'    => (float)($d->famount ?? 0),
        'fdesc'     => (string)($d->fdesc  ?? ''),
        'fketdt'    => (string)($d->fketdt ?? ''),
      ];
    })->values();

    return view('tr_poh.view', [
      'suppliers'           => $suppliers,
      'selectedSupplierCode' => $tr_poh->fsupplier,
      'fcabang'             => $fcabang,
      'fbranchcode'         => $fbranchcode,
      'products'            => $products,
      'productMap'          => $productMap,
      'tr_poh'              => $tr_poh,
      'savedItems'          => $savedItems,
      'currencies'          => $currencies,
      'currentCurrency'     => $currentCurrency,
      'ppnAmount'           => (float)($tr_poh->famountpopajak ?? 0),
      'famountponet'        => (float)($tr_poh->famountponet   ?? 0),
      'famountpo'           => (float)($tr_poh->famountpo      ?? 0),
      'filterSupplierId'    => $request->query('filter_supplier_id'),
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
      'fqty.*'       => ['numeric', 'min:0', 'gt:0'],
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
      'fqty.*.gt' => 'Harap hapus data atau isi qty data pada detail item (Qty tidak boleh 0).',
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
    $frefdtids = $request->input('frefdtid', []);
    $qtys    = $request->input('fqty', []);
    $prices  = $request->input('fprice', []);
    $discs   = $request->input('fdisc', []);
    $refprs  = $request->input('frefpr', []);
    $descs   = $request->input('fdesc', []);
    $fapplyppn = $request->boolean('fapplyppn') ? 1 : 0;

    $ppnRate = (float) $request->input('ppn_rate', 0);
    $ppnRate = max(0, min(100, $ppnRate));

    $uniqueCodes = array_values(array_unique(
      array_filter(array_map(fn($c) => trim((string)$c), $codes))
    ));

    $prodMeta = DB::table('msprd')
      ->whereIn('fprdcode', $uniqueCodes)
      ->get(['fprdid', 'fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'])
      ->keyBy('fprdcode');

    // ===== STOCK VALIDATION =====
    $errors = new \Illuminate\Support\MessageBag();
    foreach ($codes as $i => $codeRaw) {
      $code = trim($codeRaw ?? '');
      $sat  = trim($sats[$i] ?? '');
      if ($code === '') continue;

      $qty = is_numeric($qtys[$i] ?? null) ? (int) $qtys[$i] : 0;
      if ($qty < 1) {
        $errors->add("fqty.$i", 'Qty minimal 1.');  // ← ganti
        continue;
      }

      $product      = $productMap[$code] ?? null;
      $stokTersedia = $product ? (float) ($product->fminstock ?? 0) : 0;

      $qtyKecil = $qty;
      if ($product) {
        if ($sat === $product->fsatuanbesar) {
          $rasio    = is_numeric($product->fqtykecil) ? (float) $product->fqtykecil : 1;
          $qtyKecil = $qty * $rasio;
        } elseif (!empty($product->fsatuanbesar2) && $sat === $product->fsatuanbesar2) {
          $rasio2   = is_numeric($product->fqtykecil2) ? (float) $product->fqtykecil2 : 1;
          $qtyKecil = $qty * $rasio2;
        }
      }

      if ($stokTersedia <= 0) {
        $errors->add(
          "fqty.$i",
          "Produk \"$code\" tidak dapat dipesan karena stok habis atau minus. (Stok saat ini: $stokTersedia)"
        );
      } elseif ($qtyKecil > $stokTersedia) {
        $errors->add(
          "fqty.$i",
          "Qty produk \"$code\" melebihi stok tersedia. (Diminta: $qtyKecil, Stok: $stokTersedia)"
        );
      }
    }

    if ($errors->isNotEmpty()) {
      return back()->withErrors($errors)->withInput();
    }

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
      $frefdtid = (int)($frefdtids[$i] ?? 0);

      if ($code === '' || $qty <= 0) continue;

      $product = DB::table('msprd')
        ->where('fprdcode', $code)
        ->select('fprdid', 'fprdcode', 'fsatuanbesar', 'fsatuanbesar2', 'fqtykecil', 'fqtykecil2')
        ->first();

      if (!$product) continue;

      $productId = (int) $product->fprdid;
      if ($productId === 0) continue;

      if ($sat === '') $sat = $pickDefaultSat($code);
      $sat = mb_substr($sat, 0, 20);
      if ($sat === '') continue;

      $qtyKecil = $this->qtyPoToKecil($product, $sat, $qty);

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
        'fdesc'       => $desc,
        'fqtykecil'   => $qtyKecil,
        'fqtyremain'  => $qtyKecil,
        'frefdtno'    => $refdt,
        'frefdtid'    => $frefdtid ?: null,
      ];
    }

    if (empty($rowsPod)) {
      return back()->withInput()->withErrors([
        'detail' => 'Minimal satu item valid (Kode Produk ada di master, Satuan tidak kosong, Qty > 0).'
      ]);
    }

    $prdAgg = $this->aggregatePrdUsageByPrd($rowsPod);
    $oldPods = DB::table('tr_pod')->where('fpohid', $fponoId)->get(['frefdtid', 'fqtykecil']);

    $ppnAmount  = $fincludeppn ? round($totalHarga * ($ppnRate / 100), 2) : 0.0;
    $grandTotal = round($totalHarga + $ppnAmount, 2);

    try {
      DB::transaction(function () use (
        $request,
        $header,
        $fpodate,
        $fkirimdate,
        $userid,
        $fapplyppn,
        $now,
        $rowsPod,
        $fponoId,
        $totalHarga,
        $ppnAmount,
        $grandTotal,
        $fincludeppn,
        $oldPods,
        $prdAgg
      ) {
        $this->restorePrdRemainFromPodRows($oldPods);
        $this->validateAndDeductPrdRemain($prdAgg);

        $fpohid = DB::table('tr_poh')
          ->where('fpohid', $fponoId)
          ->update([
            'fpodate'        => $fpodate,
            'fkirimdate'     => $fkirimdate,
            'fcurrency'      => $request->input('fcurrency', 'IDR'),
            'ftempohr'       => $request->input('ftempohr', 0),
            'frate'          => $request->input('frate', 15500),
            'fsupplier'      => $request->input('fsupplier'),
            'fincludeppn'    => $fincludeppn,
            'fket'           => $request->input('fket'),
            'fuserupdate'    => $userid,
            'fupdatedat'     => now(),
            'famountponet'   => round($totalHarga, 2),
            'famountpopajak' => $ppnAmount,
            'famountpo'      => $grandTotal,
            'fapplyppn'      => $fapplyppn,
            'fppnpersen'      => $request->input('ppn_rate', 0),
            'fclose'         => '0',
          ]);
        $fpono = DB::table('tr_poh')->where('fpohid', $fpohid)->value('fpono');

        DB::table('tr_pod')->where('fpohid', $fponoId)->delete();
        // UPDATE STOK - gunakan qtyKecil hasil konversi, bukan qty mentah
        foreach ($rowsPod as $row) {
          DB::table('msprd')
            ->where('fprdcode', $row['fprdcode'])
            ->update([
              'fminstock'  => DB::raw("CAST(fminstock AS NUMERIC) - " . $row['fqtyremain']),
              'fupdatedat' => now(),
            ]);
        }

        $nextNou = 1;
        foreach ($rowsPod as &$r) {
          $r['fpohid'] = $fponoId;
          $r['fnou']   = $nextNou++;
        }
        unset($r);

        DB::table('tr_pod')->insert($rowsPod);
      });
    } catch (\RuntimeException $e) {
      return back()->withInput()->withErrors(['detail' => $e->getMessage()]);
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

    $currencies = DB::table('mscurrency')
      ->where(function ($q) {
        $q->whereNull('fnonactive')
          ->orWhere('fnonactive', '0')
          ->orWhere('fnonactive', '');
      })
      ->orderBy('fcurrname')
      ->get(['fcurrid', 'fcurrcode', 'fcurrname', 'frate']);

    $branch = DB::table('mscabang')
      ->when(is_numeric($raw), fn($q) => $q->where('fcabangid', (int) $raw))
      ->when(!is_numeric($raw), fn($q) => $q
        ->where('fcabangkode', $raw)
        ->orWhere('fcabangname', $raw))
      ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

    $fcabang     = $branch->fcabangname ?? (string) $raw;   // tampilan
    $fbranchcode = $branch->fcabangkode ?? (string) $raw;   // hidden post

    $tr_poh = Tr_poh::with(['details' => function ($q) {
      $q->leftJoin('msprd as m', 'm.fprdid', '=', 'tr_pod.fprdid')
        ->leftJoin(DB::raw('(
        SELECT
            fprdcode,
            frefdtno,
            SUM(fqty) AS total_terima
        FROM trstockdt
        WHERE
            (fstockmtcode = \'TER\' OR (fcode = \'P\' AND fstockmtcode = \'BUY\'))
        GROUP BY
            fprdcode,
            frefdtno
    ) as r'), function ($join) {
          $join->on('r.frefdtno', '=', 'tr_pod.frefdtno')   // ← join via frefdtno
            ->on('r.fprdcode', '=', 'm.fprdcode');
        })
        ->select(
          'tr_pod.*',
          'm.fprdcode as fitemcode',
          'm.fprdname',
          'm.fsatuankecil',
          'm.fsatuanbesar',
          'm.fsatuanbesar2',
          'm.fqtykecil',
          'm.fqtykecil2',
          DB::raw("COALESCE((SELECT pr.fqty FROM tr_prd pr WHERE tr_pod.frefdtid IS NOT NULL AND pr.fprdid = CAST(tr_pod.frefdtid AS INTEGER) LIMIT 1), 0) as fqtypr"),
          DB::raw("COALESCE((SELECT pr.fsatuan FROM tr_prd pr WHERE tr_pod.frefdtid IS NOT NULL AND pr.fprdid = CAST(tr_pod.frefdtid AS INTEGER) LIMIT 1), '') as fqtypr_satuan"),
          DB::raw("COALESCE((SELECT pr.fqtyremain FROM tr_prd pr WHERE tr_pod.frefdtid IS NOT NULL AND pr.fprdid = CAST(tr_pod.frefdtid AS INTEGER) LIMIT 1), 0) as fqtyremain_pr"),
          DB::raw('COALESCE(r.total_terima, 0) AS fqtyterima'),
        );
    }])->findOrFail($fpohid);

    // Cek apakah PO sudah ada penerimaan barang
    $existingTerima = DB::table('trstockdt')
      ->where('frefdtno', $tr_poh->fpono)
      ->select('fstockmtno', 'fdatetime', DB::raw('SUM(fqty) as total_qty'))
      ->groupBy('fstockmtno', 'fdatetime')
      ->get();

    $blockedByTerima = $existingTerima->isNotEmpty();

    // Lookup currency berdasarkan fcurrency (integer ID) di tr_poh
    $currentCurrency = DB::table('mscurrency')
      ->where('fcurrid', $tr_poh->fcurrency)
      ->first(['fcurrid', 'fcurrcode', 'fcurrname', 'frate']);

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
          'name'  => $p->fprdname,
          'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
          'stock' => $p->fminstock ?? 0,
        ],
      ];
    })->toArray();

    $savedItems = $tr_poh->details->map(function ($d) use ($products) {
      $prod  = $products->firstWhere('fprdcode', $d->fitemcode);
      $units = $prod
        ? array_values(array_filter([
          $prod->fsatuankecil,
          $prod->fsatuanbesar,
          $prod->fsatuanbesar2,
        ]))
        : ($d->fsatuan ? [$d->fsatuan] : []);

      // Pastikan fsatuan ada di units
      if ($d->fsatuan && !in_array($d->fsatuan, $units)) {
        array_unshift($units, $d->fsatuan);
      }

      return [
        'uid'       => (string)($d->fpodid   ?? \Illuminate\Support\Str::uuid()),
        'fitemcode' => (string)($d->fitemcode ?? ''),
        'fitemname' => (string)($d->fprdname  ?? ''),
        'fsatuan'   => (string)($d->fsatuan   ?? ''),
        'units'     => $units,
        'frefdtno'  => (string)($d->frefdtno  ?? ''),
        'frefdtid'  => (string)($d->frefdtid  ?? ''),
        'fnouref'   => (string)($d->fnouref   ?? ''),
        'fqtyterima' => (float)($d->fqtyterima ?? 0),
        'frefpr'    => (string)($d->frefdtno  ?? ''),
        'fprhid'    => (string)($d->fprhid    ?? ''),
        'fprno'     => (string)($d->frefdtno  ?? ''),
        'fqty'      => (float)($d->fqty    ?? 0),
        'fterima'   => (float)($d->fterima ?? 0),
        'fprice'    => (float)($d->fprice  ?? 0),
        'fdisc'     => (float)($d->fdisc   ?? 0),
        'ftotal'    => (float)($d->famount ?? 0),
        'fdesc'     => (string)($d->fdesc  ?? ''),
        'fketdt'    => (string)($d->fketdt ?? ''),
        'maxqty'    => 0,
      ];
    })->values();

    // Pass the data to the view
    return view('tr_poh.edit', [
      'suppliers'           => $suppliers,
      'selectedSupplierCode' => $tr_poh->fsupplier,
      'fcabang'             => $fcabang,
      'fbranchcode'         => $fbranchcode,
      'existingTerima'  => $existingTerima,
      'blockedByTerima' => $blockedByTerima,
      'products'            => $products,
      'productMap'          => $productMap,
      'tr_poh'              => $tr_poh,
      'savedItems'          => $savedItems,
      'currencies'          => $currencies,
      'currentCurrency'     => $currentCurrency,   // <-- currency aktif dari join
      'ppnAmount'           => (float)($tr_poh->famountpopajak ?? 0),
      'famountponet'        => (float)($tr_poh->famountponet   ?? 0),
      'famountpo'           => (float)($tr_poh->famountpo      ?? 0),
      'filterSupplierId'    => $request->query('filter_supplier_id'),
      'action' => 'delete'
    ]);
  }

  public function destroy($fpohid)
  {
    try {
      $tr_poh = Tr_poh::findOrFail($fpohid);

      // 1. Cek apakah sudah ada penerimaan barang (Stock In) untuk PO ini
      // Kita cek berdasarkan frefdtno (Nomor PO) di tabel detail stok
      $existingTerima = DB::table('trstockdt')
        ->where('frefdtno', $tr_poh->fpono)
        ->select('fstockmtno')
        ->distinct()
        ->get();

      if ($existingTerima->isNotEmpty()) {
        $noTransaksi = $existingTerima->pluck('fstockmtno')->implode(', ');
        return redirect()->back()->with(
          'error',
          "Order Pembelian {$tr_poh->fpono} tidak bisa dihapus karena sudah memiliki transaksi Penerimaan Barang: ({$noTransaksi})."
        );
      }

      DB::transaction(function () use ($tr_poh) {
        $oldPods = DB::table('tr_pod')->where('fpohid', $tr_poh->fpohid)->get(['frefdtid', 'fqtykecil']);
        $this->restorePrdRemainFromPodRows($oldPods);
        $tr_poh->details()->delete();
        $tr_poh->delete();
      });

      return redirect()->route('tr_poh.index')
        ->with('success', "Data Order Pembelian {$tr_poh->fpono} berhasil dihapus.");
    } catch (\Exception $e) {
      return redirect()->back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
    }
  }
}
