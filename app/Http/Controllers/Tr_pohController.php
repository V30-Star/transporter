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
    $search   = trim((string) $request->search);

    // Tetap dukung nilai lama dari UI: fprno -> dipetakan ke fpono
    $rawFilter = $request->filter_by ?? 'all'; // all | fprno | fpono | fsupplier
    $filterBy  = match ($rawFilter) {
      'fprno' => 'fpono',
      default => $rawFilter, // 'all' | 'fpono' | 'fsupplier'
    };

    // Subquery untuk hitung jumlah detail per fpono (opsional; aman meski tidak dipakai di UI)
    $detailAgg = DB::table('tr_pod')
      ->select('fpono', DB::raw('COUNT(*)::int AS item_count'))
      ->groupBy('fpono');

    // Ambil header dari tr_poh, join dengan agregasi detail
    $tr_poh = DB::table('tr_poh')
      ->leftJoinSub($detailAgg, 'd', 'd.fpono', '=', 'tr_poh.fpono')
      ->leftJoin('mssupplier', 'mssupplier.fsuppliercode', '=', 'tr_poh.fsupplier') // JOIN dengan mssupplier
      ->when($search !== '', function ($q) use ($search, $filterBy) {
        $q->where(function ($qq) use ($search, $filterBy) {
          if ($filterBy === 'fpono') {
            $qq->where('tr_poh.fpono', 'ILIKE', "%{$search}%");
          } elseif ($filterBy === 'fsupplier') {
            $qq->where('tr_poh.fsupplier', 'ILIKE', "%{$search}%");
          } else { // all
            $qq->where('tr_poh.fpono', 'ILIKE', "%{$search}%")
              ->orWhere('tr_poh.fsupplier', 'ILIKE', "%{$search}%");
          }
        });
      })
      ->orderByDesc('tr_poh.fdatetime')   // kalau tidak ada fdatetime, ganti ke fpodate
      ->orderByDesc('tr_poh.fpodate')
      ->select([
        'tr_poh.fpohdid',
        'tr_poh.fpono',
        'tr_poh.fsupplier',
        'mssupplier.fsuppliername',
        'tr_poh.fpodate',
        'tr_poh.famountpo',
        'tr_poh.fuserid',
        DB::raw('COALESCE(d.item_count, 0) AS item_count'),
      ])
      ->paginate(10)
      ->withQueryString();

    // Permissions (biarin pakai session key yang sama)
    $canCreate = in_array('createTr_prh', explode(',', session('user_restricted_permissions', '')));
    $canEdit   = in_array('updateTr_prh', explode(',', session('user_restricted_permissions', '')));
    $canDelete = in_array('deleteTr_prh', explode(',', session('user_restricted_permissions', '')));

    // AJAX response untuk modal "Pilih Permintaan (PR)"
    if ($request->ajax()) {
      $rows = collect($tr_poh->items())->map(function ($t) {
        // Gunakan fpono sebagai "id" agar kompatibel dengan frontend (alias ke fprid)
        $fpono    = $t->fpono;
        $fpodate  = $t->fpodate ? \Carbon\Carbon::parse($t->fpodate)->format('Y-m-d H:i:s') : 'No Date';

        return [
          'fprid'      => $fpono,           // <- alias id agar :key="row.fprid" tetap aman
          'fpono'      => $fpono,
          'fsupplier'  => trim($t->fsupplier ?? ''),
          'fsuppliername' => $t->fsuppliername ?? '',
          'fpodate'    => $fpodate,
          'item_count' => (int)($t->item_count ?? 0),

          // pastikan rute-rutenya menerima fpono sebagai parameter
          'edit_url'    => route('tr_poh.edit',    $fpono),
          'destroy_url' => route('tr_poh.destroy', $fpono),
          'print_url'   => route('tr_poh.print',   $fpono),
          // endpoint untuk mengambil detail (dipakai saat klik "Pilih")
          'items_url'   => route('tr_poh.items',   $fpono),
        ];
      });

      return response()->json([
        'data'  => $rows,
        'perms' => [
          'can_create' => $canCreate,
          'can_edit'   => $canEdit,
          'can_delete' => $canDelete,
        ],
        'links' => [
          'prev'         => $tr_poh->previousPageUrl(),
          'next'         => $tr_poh->nextPageUrl(),
          'current_page' => $tr_poh->currentPage(),
          'last_page'    => $tr_poh->lastPage(),
        ],
      ]);
    }

    // Render view index
    return view('tr_poh.index', [
      'tr_poh'    => $tr_poh,
      'filterBy'  => $rawFilter, // tampilkan nilai asli yang dipilih user
      'search'    => $search,
      'canCreate' => $canCreate,
      'canEdit'   => $canEdit,
      'canDelete' => $canDelete,
    ]);
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

    // Ambil data items dari tabel tr_prd berdasarkan fprnoid
    $items = Tr_prd::where('tr_prd.fprnoid', $header->fprno)
      ->leftJoin('msprd as m', 'm.fprdcode', '=', 'tr_prd.fprdcode')
      ->select([
        'tr_prd.fprdid as frefdtno',      // PK detail PR
        'tr_prd.fprnoid as fnouref',      // Ref PR#
        'tr_prd.fprdcode as fitemcode',   // Kode produk
        'm.fprdname as fitemname',        // Nama produk
        'tr_prd.fqty',                    // Quantity
        'tr_prd.fsatuan as fsatuan',         // Satuan
        'tr_prd.fprnoid',            // <-- WAJIB DIAMBIL
        'tr_prd.fprice as fharga',        // Harga produk
        DB::raw('0::numeric as fdiskon')  // Default diskon
      ])
      ->orderBy('tr_prd.fprdcode')  // Menurut kode produk
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
    $supplierSub = Supplier::select('fsuppliercode', 'fsuppliername');

    $hdr = Tr_poh::query()
      ->leftJoinSub($supplierSub, 's', function ($join) {
        $join->on('s.fsuppliercode', '=', 'tr_poh.fsupplier');
      })
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

    $dt = Tr_pod::query()
      ->leftJoin('msprd as p', 'p.fprdcode', '=', 'tr_pod.fprdcode')
      ->where('tr_pod.fpono', $fpono)
      ->orderBy('tr_pod.fprdcode')
      ->get([
        'tr_pod.*',
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

    return view('tr_poh.create', [
      'newtr_prh_code' => $newtr_prh_code,
      'perms' => ['can_approval' => $canApproval],
      'supplier' => $supplier,
      'fcabang' => $fcabang,
      'fbranchcode' => $fbranchcode,
      'products' => $products,
    ]);
  }

  public function store(Request $request)
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
      'ftempohr'     => ['nullable', 'string', 'max:3'],
      'fitemcode'    => ['required', 'array', 'min:1'],
      'fitemcode.*'  => ['required', 'string', 'max:50'],
      'fsatuan'      => ['nullable', 'array'],
      'fsatuan.*'    => ['nullable', 'string', 'max:5'],
      'fapproval' => ['nullable'],
      'frefdtno'      => ['nullable'],
      'frefdtno.*'    => ['nullable'],
      'fnouref'      => ['nullable'],
      'fnouref.*'    => ['nullable'],
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

    // HEADER
    $fpodate    = \Carbon\Carbon::parse($request->fpodate)->startOfDay();
    $fkirimdate = $request->filled('fkirimdate') ? \Carbon\Carbon::parse($request->fkirimdate)->startOfDay() : null;
    $fpono      = $request->input('fpono'); // biarkan null dulu, akan digenerate dalam transaksi
    $fincludeppn = $request->boolean('fincludeppn') ? 1 : 0;
    $userid = 'admin';
    $now = now();

    // DETAIL ARRAYS
    $codes  = $request->input('fitemcode', []);
    $satuan = $request->input('fsatuan', []);
    $refdtno = $request->input('frefdtno', []);
    $nouref = $request->input('fnouref', []);
    $qtys   = $request->input('fqty', []);
    $prices = $request->input('fprice', []);
    $discs  = $request->input('fdisc', []);
    $refprs = $request->input('frefpr', []);
    $descs  = $request->input('fdesc', []);

    $totalHarga  = (float) $request->input('famountponet', 0);
    $ppnRate     = (float) $request->input('ppn_rate', 0);
    $ppnAmount   = (float) $request->input('famountpopajak', 0);
    $grandTotal  = (float) $request->input('famountpo', 0);

    $uniqueCodes = array_values(array_unique(array_filter(array_map(fn($c) => trim((string)$c), $codes))));
    $prodMeta = DB::table('msprd')
      ->whereIn('fprdcode', $uniqueCodes)
      ->get(['fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'])
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
      $code  = trim((string)($codes[$i]  ?? ''));
      $sat   = trim((string)($satuan[$i] ?? ''));
      $refdtno   = trim((string)($refdtno[$i] ?? ''));
      $nouref   = trim((string)($nouref[$i] ?? ''));
      $qty   = (float)($qtys[$i]   ?? 0);
      $price = (float)($prices[$i] ?? 0);
      $discP = (float)($discs[$i]  ?? 0);
      $desc  = (string)($descs[$i]  ?? '');

      if ($code === '' || $qty <= 0) continue;

      if ($sat === '') {
        $sat = $pickDefaultSat($code);
      }
      $sat = mb_substr($sat, 0, 5);
      if ($sat === '') continue;

      $priceGross = $price;
      $priceNet   = $priceGross * (1 - ($discP / 100));
      $amount     = $qty * $priceNet;

      $totalHarga += $amount;

      $rowsPod[] = [
        'fprdcode'    => $code,
        'fqty'        => $qty,
        'fqtyremain'  => $qty,
        'fdisc'       => (string)$discP,
        'fprice'      => $price,
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

    $ppnAmount  = $request->input('famountpopajak', 0);

    DB::transaction(function () use ($request, $fpodate, $fkirimdate, $fincludeppn, $userid, $now, &$rowsPod, &$fpono, $totalHarga, $ppnAmount, $grandTotal) {
      if (empty($fpono)) {
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

        $yy = $fpodate->format('y'); // 25
        $mm = $fpodate->format('m'); // 09
        $prefix = sprintf('PO.%s.%s.%s.', $kodeCabang, $yy, $mm);

        // advisory lock per (branch, tahun-bulan)
        $lockKey = crc32('PO|' . $kodeCabang . '|' . $fpodate->format('Y-m'));
        DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

        // ambil nomor terakhir dgn prefix yg sama lalu +1
        $last = DB::table('tr_poh')
          ->where('fpono', 'like', $prefix . '%')
          ->selectRaw("MAX(CAST(split_part(fpono, '.', 5) AS int)) AS lastno")
          ->value('lastno');

        $next = (int)$last + 1;
        $fpono = $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
      }

      $fcurrency = $request->input('fcurrency', 'IDR');   // default IDR
      $frate     = $request->input('frate', 15500);       // default 15500 kalau IDR
      $ftempohr     = $request->input('ftempohr', 0);       // default 15500 kalau IDR
      $isApproval = (int)($request->input('fapproval', 0)); // 1 jika dicentang, 0 jika tidak

      DB::table('tr_poh')->insert([
        'fpono'          => $fpono,
        'fpodate'        => $fpodate,
        'fkirimdate'     => $fkirimdate,
        'fcurrency'      => $fcurrency,
        'ftempohr'       => $ftempohr,
        'frate'          => $frate,
        'fsupplier'      => $request->input('fsupplier'),
        'fincludeppn'    => $fincludeppn,
        'fket'           => $request->input('fket'),
        'fuserid'        => $userid,
        'fdatetime'      => $now,
        'famountponet'   => round($totalHarga, 2),
        'famountpopajak' => $ppnAmount,
        'famountpo'      => $grandTotal,
        'fapproval'     => $isApproval,
      ]);

      if ($isApproval === 1) {
        $hdr = Tr_poh::where('fpono', $fpono)->first();
        $dt = Tr_pod::query()
          ->leftJoin('msprd as p', 'p.fprdcode', '=', 'tr_pod.fprdcode')
          ->where('tr_pod.fpono', $hdr->fpono)
          ->orderBy('tr_pod.fprdcode')
          ->get([
            'tr_pod.*',
            'p.fprdname as product_name',
            'p.fminstock as stock',
          ]);

        $productName = $dt->pluck('fprdcode')->implode(', ');
        $approver = auth('sysuser')->user()->fname;

        Mail::to('vierybiliam8@gmail.com')->send(new ApprovalEmailPo($hdr, $dt, $productName, $approver, 'Order Pembelian (PO)'));
      }

      // === siapkan fnou berurutan & insert detail ===
      $lastNou = (int) DB::table('tr_pod')->where('fpono', $fpono)->max('fnou');
      $nextNou = $lastNou + 1;
      foreach ($rowsPod as &$r) {
        $r['fpono'] = $fpono;
        $r['fnou']  = $nextNou++;
      }
      unset($r);

      DB::table('tr_pod')->insert($rowsPod);
    });

    return redirect()
      ->route('tr_poh.create')
      ->with('success', "PO {$fpono} tersimpan, detail masuk ke TR_POD.");
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
        ->join('msprd', 'msprd.fprdcode', '=', 'tr_pod.fprdcode')
        ->select('tr_pod.*', 'msprd.fprdname', DB::raw('tr_pod.fpono as fpono'), 'tr_pod.famount'); // Select fpono from tr_pod
    }])
      ->where('fpohdid', $fpohdid)
      ->firstOrFail();

    // Map the data for savedItems
    $savedItems = $tr_poh->details->map(function ($d) {
      return [
        'uid'       => $d->fpodid,                   // untuk :key
        'fitemcode' => $d->fprdcode ?? '',
        'fitemname' => $d->fprdname ?? '',  // Now fprdname will be available here
        'fsatuan'   => $d->fsatuan ?? '',
        'fprno'     => $d->frefpr ?? '-',   // ini PR#, biarkan di sini kalau kamu butuh
        'frefpr'    => $d->frefpr ?? null,  // referensi PR (jika perlu)
        'fpono'     => $d->fpono ?? null,   // <-- TAMBAH INI: untuk ditampilkan di tabel
        'famountponet' => $d->famountponet ?? null,   // <-- TAMBAH INI: untuk ditampilkan di tabel
        'famountpo' => $d->famountpo ?? null,   // <-- TAMBAH INI: untuk ditampilkan di tabel
        'frefdtno'  => $d->frefdtno ?? null,
        'fnouref'   => $d->fnouref ?? null,
        'fqty'      => (int)($d->fqty ?? 0),
        'fterima'   => (int)($d->fterima ?? 0),
        'fprice'    => (float)($d->fprice ?? 0),
        'fdisc'     => (float)($d->fdisc ?? 0),
        'ftotal'    => (float)($d->famount ?? 0),
        'fdesc'     => $d->fdesc ?? '',
        'fketdt'    => $d->fketdt ?? '',
        'units'     => [],                           // opsional; bisa diisi dari PRODUCT_MAP
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
      'ppnAmount'    => (float) ($tr_poh->famountpopajak ?? 0), // total PPN from DB
      'famountponet'    => (float) ($tr_poh->famountponet ?? 0),  // nilai Grand Total dari DB
      'famountpo'    => (float) ($tr_poh->famountpo ?? 0),  // nilai Grand Total dari DB
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

    // Ambil header berdasar fpohdid
    $header = Tr_poh::where('fpohdid', $fpohdid)->firstOrFail();
    $fpono = $header->fpono; // dipakai untuk detail

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
    $prices  = $request->input('fprice', []);
    $discs   = $request->input('fdisc', []);
    $refprs  = $request->input('frefpr', []);
    $descs   = $request->input('fdesc', []);

    $ppnRate = (float) $request->input('ppn_rate', $request->input('famountpajak', 0));
    $ppnRate = max(0, min(100, $ppnRate));

    $totalHarga  = (float) $request->input('famountponet', 0);
    $ppnAmount  = $request->boolean('fincludeppn') ? round($totalHarga * ($ppnRate / 100), 2) : 0.0;
    $grandTotal = round($totalHarga + $ppnAmount, 2);

    // Get product metadata
    $uniqueCodes = array_values(array_unique(array_filter(array_map(fn($c) => trim((string)$c), $codes))));
    $prodMeta = DB::table('msprd')
      ->whereIn('fprdcode', $uniqueCodes)
      ->get(['fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'])
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

      if ($sat === '') {
        $sat = $pickDefaultSat($code);
      }
      $sat = mb_substr($sat, 0, 5);
      if ($sat === '') continue;

      $priceGross = $price;
      $priceNet   = $priceGross * (1 - ($discP / 100));
      $amount     = $qty * $priceNet;

      $totalHarga += $amount;

      $rowsPod[] = [
        'fprdcode'    => $code,
        'fqty'        => $qty,
        'fqtyremain'  => $qty,
        'fdisc'       => (string)$discP,
        'fprice'      => $price,
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
    DB::transaction(function () use ($request, $header, $fpodate, $fkirimdate, $fincludeppn, $userid, $now, $rowsPod, $fpono, $totalHarga, $ppnAmount, $grandTotal) {

      $fcurrency = $request->input('fcurrency', 'IDR');   // default IDR
      $frate     = $request->input('frate', 15500);       // default 15500 kalau IDR

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
        'famountponet'   => round($totalHarga, 2),
        'famountpopajak' => $ppnAmount,
        'famountpo'      => $grandTotal,
      ]);

      // Delete existing details
      DB::table('tr_pod')->where('fpono', $fpono)->delete();

      // Insert new details
      $lastNou = 0; // reset karena sudah dihapus
      $nextNou = 1;
      foreach ($rowsPod as &$r) {
        $r['fpono'] = $fpono;
        $r['fnou']  = $nextNou++;
      }
      unset($r);

      DB::table('tr_pod')->insert($rowsPod);
    });

    return redirect()
      ->route('tr_poh.index')
      ->with('success', "PO {$fpono} berhasil diperbarui.");
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
