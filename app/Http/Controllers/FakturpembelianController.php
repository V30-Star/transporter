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
use Exception;

class FakturPembelianController extends Controller
{
  public function index(Request $request)
  {
    // Sorting
    $allowedSorts = ['fpohdid', 'fpono', 'fsupplier', 'fpodate'];
    $sortBy  = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'fpohdid';
    $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

    $query = Tr_poh::query();

    $tr_poh = Tr_poh::orderBy($sortBy, $sortDir)->get(['fpohdid', 'fpono', 'fsupplier', 'fpodate']);

    $canCreate = in_array('createTr_prh', explode(',', session('user_restricted_permissions', '')));
    $canEdit   = in_array('updateTr_prh', explode(',', session('user_restricted_permissions', '')));
    $canDelete = in_array('deleteTr_prh', explode(',', session('user_restricted_permissions', '')));

    return view('fakturpembelian.index', compact('tr_poh', 'canCreate', 'canEdit', 'canDelete'));
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

  public function print(string $fpono)
  {
    // Use the model’s actual table name
    $supplierTable = (new Supplier)->getTable(); // e.g. ms_supplier

    // Header: find by PO code (string)
    $hdr = Tr_poh::query()
      ->leftJoin("{$supplierTable} as s", 's.fsupplierid', '=', 'tr_poh.fsupplier') // integer ↔ integer
      ->leftJoin('mscabang as c', 'c.fcabangkode', '=', 'tr_poh.fbranchcode')
      ->where('tr_poh.fpono', $fpono)
      ->first([
        'tr_poh.*',
        's.fsuppliername as supplier_name',
        'c.fcabangname as cabang_name',
      ]);

    if (!$hdr) {
      return redirect()->back()->with('error', 'PO tidak ditemukan.');
    }

    // Use header ID (integer) for detail FK
    $fpohdid = (int) $hdr->fpohdid;

    $dt = DB::table('tr_pod')
      ->leftJoin('msprd as p', function ($j) {
        $j->on('p.fprdid', '=', DB::raw('CAST(tr_pod.fprdcode AS INTEGER)'));
      })->where('tr_pod.fpono', $fpohdid)                            // detail FK = header ID
      ->orderBy('tr_pod.fprdcode')
      ->get([
        'tr_pod.*',
        'p.fprdcode as product_code',
        'p.fprdname as product_name',
        'p.fminstock as stock',
        'tr_pod.fqtyremain',
      ]);

    $fmt = fn($d) => $d
      ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d F Y')
      : '-';

    return view('tr_poh.print', [
      'hdr'          => $hdr,
      'dt'           => $dt,
      'fmt'          => $fmt,
      'company_name' => config('app.company_name', 'PT. DEMO VERSION'),
      'company_city' => config('app.company_city', 'Tangerang'),
    ]);
  }

  public function create()
  {
    $supplier = Supplier::all();

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
      'supplier' => $supplier,
      'fcabang' => $fcabang,
      'fbranchcode' => $fbranchcode,
      'products' => $products,
    ]);
  }

  public function store(Request $request)
  {
    try {
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
        'fcurrency' => ['nullable', 'string', 'max:5'],
        'frate' => ['nullable', 'numeric', 'min:0'],
        'famountpopajak' => ['nullable', 'numeric', 'min:0'],
      ], [
        'fstockmtdate.required' => 'Tanggal transaksi wajib diisi.',
        'fsupplier.required' => 'Supplier wajib diisi.',
        'fitemcode.required' => 'Minimal 1 item.',
        'fsatuan.*.max' => 'Satuan di salah satu baris tidak boleh lebih dari 5 karakter.',
        'faccid.required_if' => 'Account wajib dipilih untuk tipe Non Stok.',
      ]);

      // HEADER FIELDS
      $fstockmtno = trim((string)$request->input('fstockmtno'));
      $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
      $fsupplier = trim((string)$request->input('fsupplier'));
      $ffrom = $request->input('fwhid');
      $fket = trim((string)$request->input('fket', ''));
      $fbranchcode = $request->input('fbranchcode');
      $faccid = $request->input('faccid'); 

      $fcurrency = $request->input('fcurrency', 'IDR');
      $frate = (float)$request->input('frate', 1);
      if ($frate <= 0) $frate = 1;

      $ppnAmount = (float)$request->input('famountpopajak', 0);
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

        // INSERT HEADER ke trstockmt
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
          'frefno' => null,
          'frefpo' => null,
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
        ];

        $newStockMasterId = DB::table('trstockmt')->insertGetId($masterData, 'fstockmtid');

        // INSERT DETAILS ke trstockdt
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
        ->with('success', "Faktur Pembelian {$fstockmtno} tersimpan, detail masuk ke trstockdt.");
    } catch (Exception $e) {
      return back()
        ->withInput()
        ->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
    }
  }

  public function edit($fpohdid)
  {
    $supplier = Supplier::all();

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
          $j->on('msprd.fprdid', '=', DB::raw('CAST(tr_pod.fprdcode AS INTEGER)'));
        })
        ->select(
          'tr_pod.*',
          'msprd.fprdcode as fitemcode',
          'msprd.fprdname'
        );
    }])->findOrFail($fpohdid);

    $savedItems = $tr_poh->details->map(function ($d) {
      return [
        'uid'        => $d->fpodid,
        'fitemcode'  => (string)($d->fitemcode ?? ''),  // dari alias msprd.fprdcode
        'fitemname'  => (string)($d->fprdname ?? ''),   // dari msprd.fprdname
        'fsatuan'    => (string)($d->fsatuan ?? ''),
        'frefdtno'   => (string)($d->frefdtno ?? ''),
        'fnouref'    => (string)($d->fnouref ?? ''),
        'fqty'       => (float)($d->fqty ?? 0),
        'fterima'    => (float)($d->fterima ?? 0),
        'ftotprice'     => (float)($d->ftotprice ?? 0),
        'fdiscpersen'      => (float)($d->fdiscpersen ?? 0),
        'ftotal'     => (float)($d->famount ?? 0),
        'fdesc'      => (string)($d->fdesc ?? ''),
        'fketdt'     => (string)($d->fketdt ?? ''),
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
        $p->fprdcode => [
          'name'  => $p->fprdname,
          'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
          'stock' => $p->fminstock ?? 0,
        ],
      ];
    })->toArray();

    // Pass the data to the view
    return view('tr_poh.edit', [
      'supplier'     => $supplier,
      'selectedSupplierCode' => $selectedSupplierCode, // Kirim kode supplier ke view
      'fcabang'      => $fcabang,
      'fbranchcode'  => $fbranchcode,
      'products'     => $products,
      'productMap'   => $productMap,
      'tr_poh'       => $tr_poh,
      'savedItems'   => $savedItems,
      'ppnAmount'    => (float) ($tr_poh->famountpajak ?? 0), // total PPN from DB
      'famount'    => (float) ($tr_poh->famount ?? 0),  // nilai Grand Total dari DB
      'famountmt'    => (float) ($tr_poh->famountmt ?? 0),  // nilai Grand Total dari DB
    ]);
  }

  public function update(Request $request, $fpohdid)
  {
    // VALIDASI
    $request->validate([
      'fpono'        => ['nullable', 'string', 'max:25'],
      'fpodate'      => ['required', 'date'],
      'fkirimdate'   => ['nullable', 'date'],
      'fsupplier'    => ['required', 'string', 'max:30'],
      'fincludeppn'  => ['nullable'],
      'fket'         => ['nullable', 'string', 'max:300'],
      'fbranchcode'  => ['nullable', 'string', 'max:20'],

      'fitemcode'    => ['required', 'array', 'min:1'],
      'fitemcode.*'  => ['required', 'string', 'max:50'],

      'fsatuan'      => ['nullable', 'array'],
      'fsatuan.*'    => ['nullable', 'string', 'max:5'],
      'frefdtno'     => ['nullable'],
      'frefdtno.*'   => ['nullable'],
      'fnouref'      => ['nullable'],
      'fnouref.*'    => ['nullable'],

      'fqty'         => ['required', 'array'],
      'fqty.*'       => ['numeric', 'min:0'],
      'ftotprice'       => ['nullable', 'array'],
      'ftotprice.*'     => ['numeric', 'min:0'],
      'fdiscpersen'        => ['nullable', 'array'],
      'fdiscpersen.*'      => ['numeric', 'min:0'],
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

    $header  = Tr_poh::where('fpohdid', $fpohdid)->firstOrFail();
    $fponoId = (int) $header->fpohdid;   // ← PAKAI INI, BUKAN $header->fpono (string)

    // HEADER DATA
    $fpodate    = \Carbon\Carbon::parse($request->fpodate)->startOfDay();
    $fkirimdate = $request->filled('fkirimdate') ? \Carbon\Carbon::parse($request->fkirimdate)->startOfDay() : null;
    $fincludeppn = $request->boolean('fincludeppn') ? 1 : 0;
    $userid = 'admin';
    $now = now();

    // DETAIL ARRAYS
    $codes   = $request->input('fitemcode', []);
    $satuan  = $request->input('fsatuan', []);
    $refdtno = $request->input('frefdtno', []);
    $nouref  = $request->input('fnouref', []);
    $qtys    = $request->input('fqty', []);
    $prices  = $request->input('ftotprice', []);
    $discs   = $request->input('fdiscpersen', []);
    $refprs  = $request->input('frefpr', []);
    $descs   = $request->input('fdesc', []);

    $ppnRate = (float) $request->input('ppn_rate', $request->input('famountpajak', 0));
    $ppnRate = max(0, min(100, $ppnRate));

    $totalHarga  = (float) $request->input('famount', 0);
    $ppnAmount  = $request->boolean('fincludeppn') ? round($totalHarga * ($ppnRate / 100), 2) : 0.0;
    $grandTotal = round($totalHarga + $ppnAmount, 2);

    // Get product metadata
    $uniqueCodes = array_values(array_unique(array_filter(array_map(fn($c) => trim((string)$c), $codes))));
    $prodMeta = DB::table('msprd')
      ->whereIn('fprdcode', $uniqueCodes)
      ->get(['fprdid', 'fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'])
      ->keyBy('fprdcode');

    $pickDefaultSat = function ($code) use ($prodMeta) {
      $m = $prodMeta[$code] ?? null;
      if (!$m) return '';
      foreach (['fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'] as $k) {
        $v = trim((string)($m->$k ?? ''));
        if ($v !== '') return mb_substr($v, 0, 5); // patuhi varchar(5)
      }
      return '';
    };

    // RAKIT DETAIL
    $rowsPod = [];
    $totalHarga = 0.0; // subtotal sebelum PPN
    $rowCount = max(count($codes), count($satuan), count($refdtno), count($nouref), count($qtys), count($prices), count($discs), count($refprs), count($descs));

    for ($i = 0; $i < $rowCount; $i++) {
      $code    = trim((string)($codes[$i]  ?? ''));
      $sat     = trim((string)($satuan[$i] ?? ''));
      $refdtno = trim((string)($refdtno[$i] ?? ''));
      $nouref  = trim((string)($nouref[$i] ?? ''));
      $qty     = (float)($qtys[$i]   ?? 0);
      $price   = (float)($prices[$i] ?? 0);
      $discP   = (float)($discs[$i]  ?? 0);
      $desc    = (string)($descs[$i]  ?? '');

      if ($code === '' || $qty <= 0) continue;

      if ($sat === '') $sat = $pickDefaultSat($code);
      $sat = mb_substr($sat, 0, 5);
      if ($sat === '') continue;

      $productId = (int) (($prodMeta[$code]->fprdid ?? null) ?? 0);
      if ($productId === 0) continue;

      $priceGross = $price;
      $priceNet   = $priceGross * (1 - ($discP / 100));
      $amount     = $qty * $priceNet;

      $totalHarga += $amount;

      $rowsPod[] = [
        'fprdcode'    => $productId,
        'fqty'        => $qty,
        'fqtyremain'  => $qty,
        'fdiscpersen'       => (string)$discP,
        'ftotprice'      => $price,
        'fprice_rp'   => $price,
        'fpricegross' => $priceGross,
        'fpricenet'   => $priceNet,
        'famount'     => $amount,
        'famount_rp'  => $amount,
        'fuserid'     => $userid,
        'fdatetime'   => $now,
        'fsatuan'     => $sat,
        'fqtykecil'   => $qty,
        'frefdtno'    => $refdtno,
        'fnouref'     => $nouref,
        'fdesc'       => $desc,
      ];
    }

    if (empty($rowsPod)) {
      return back()->withInput()->withErrors(['detail' => 'Minimal satu item valid (Kode, Satuan, Qty > 0).']);
    }

    // TRANSACTION UPDATE
    DB::transaction(function () use ($request, $header, $fpodate, $fkirimdate, $userid, $now, $rowsPod, $fponoId, $totalHarga, $ppnAmount, $grandTotal) {

      $fcurrency = $request->input('fcurrency', 'IDR');
      $frate     = $request->input('frate', 15500);
      $fincludeppn = $request->boolean('fincludeppn') ? 1 : 0;

      // Update header
      $header->update([
        'fpodate'        => $fpodate,
        'fkirimdate'     => $fkirimdate,
        'fcurrency'      => $fcurrency,
        'frate'          => $frate,
        'fsupplier'      => $request->input('fsupplier'),
        'fincludeppn'    => $fincludeppn,
        'fket'           => $request->input('fket'),
        'fuserid'        => $userid,
        'fdatetime'      => $now,
        'famount'   => round($totalHarga, 2),
        'famountpajak' => $ppnAmount,
        'famountmt'      => $grandTotal,
      ]);

      // Hapus detail lama berdasarkan ID header
      DB::table('tr_pod')->where('fpono', $fponoId)->delete();

      // Isi FK detail dengan ID header
      $nextNou = 1;
      foreach ($rowsPod as &$r) {
        $r['fpono'] = $fponoId;  // ← integer FK
        $r['fnou']  = $nextNou++;
      }
      unset($r);

      DB::table('tr_pod')->insert($rowsPod);
    });

    // Pesan sukses tetap pakai nomor PO string untuk tampilan
    return redirect()
      ->route('tr_poh.edit', ['fpohdid' => $fpohdid])
      ->with('success', "PO {$header->fpono} berhasil diperbarui.");
  }

  public function destroy($fpohdid)
  {
    $tr_poh = Tr_poh::findOrFail($fpohdid);
    $tr_poh->delete();

    return redirect()
      ->route('tr_poh.index')
      ->with('success', 'Tr_Poh berhasil dihapus.');
  }
}
