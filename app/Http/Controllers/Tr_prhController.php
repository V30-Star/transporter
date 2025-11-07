<?php

namespace App\Http\Controllers;

use App\Models\Tr_prh;
use App\Models\Tr_prd;
use App\Models\Supplier;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Mail\ApprovalEmail;
use App\Models\Tr_poh;
use Illuminate\Support\Facades\Mail;


class Tr_prhController extends Controller
{
  public function index(Request $request)
  {
    // Ambil izin (permissions) di awal
    $canCreate = in_array('createTr_prh', explode(',', session('user_restricted_permissions', '')));
    $canEdit   = in_array('updateTr_prh', explode(',', session('user_restricted_permissions', '')));
    $canDelete = in_array('deleteTr_prh', explode(',', session('user_restricted_permissions', '')));
    $showActionsColumn = $canEdit || $canDelete;

    $status = $request->query('status');
    $year = $request->query('year');
    $month = $request->query('month');

    // Ambil tahun-tahun yang tersedia dari data
    $availableYears = Tr_prh::selectRaw('DISTINCT EXTRACT(YEAR FROM fcreatedat) as year')
      ->whereNotNull('fcreatedat')
      ->orderByRaw('EXTRACT(YEAR FROM fcreatedat) DESC')
      ->pluck('year');

    // --- Handle Request AJAX dari DataTables ---
    if ($request->ajax()) {

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

      // Filter status berdasarkan query parameter atau default ke active
      $statusFilter = $request->query('status', 'active');
      if ($statusFilter === 'active') {
        $query->where('fclose', '0');
      } elseif ($statusFilter === 'nonactive') {
        $query->where('fclose', '1');
      }
      // Jika 'all', tidak ada filter

      // Filter tahun (PostgreSQL syntax)
      if ($year) {
        $query->whereRaw('EXTRACT(YEAR FROM fcreatedat) = ?', [$year]);
      }

      // Filter bulan (PostgreSQL syntax)
      if ($month) {
        $query->whereRaw('EXTRACT(MONTH FROM fcreatedat) = ?', [$month]);
      }

      $filteredRecords = (clone $query)->count();

      // Sorting
      $orderColumnIndex = $request->input('order.0.column', 0);
      $orderDir = $request->input('order.0.dir', 'asc');

      // Sesuaikan dengan urutan columns di DataTables
      $columns = [
        0 => 'fprno',
        1 => 'fprdin',
        2 => 'fclose',  // Ganti ke fclose
        3 => null // Kolom 'Actions'
      ];

      if (isset($columns[$orderColumnIndex]) && $columns[$orderColumnIndex] !== null) {
        $query->orderBy($columns[$orderColumnIndex], $orderDir);
      } else {
        $query->orderBy('fprid', 'desc');
      }

      // Pagination
      $start = $request->input('start', 0);
      $length = $request->input('length', 10);
      if ($length != -1) {
        $query->skip($start)->take($length);
      }

      // Select kolom yang dibutuhkan - PASTIKAN fclose ADA
      $records = $query->get(['fprid', 'fprno', 'fprdin', 'fcreatedat', 'fclose']);

      // Format data untuk DataTables
      $data = $records->map(function ($record) {
        return [
          'fprno'    => $record->fprno,
          'fprdin'   => $record->fprdin,
          'fclose'   => $record->fclose, // Ganti ke fclose
          'fprid'    => $record->fprid,
          'DT_RowId' => 'row_' . $record->fprid
        ];
      });

      // Kirim response JSON
      return response()->json([
        'draw'            => intval($request->input('draw')),
        'recordsTotal'    => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data'            => $data
      ]);
    }

    // --- Handle Request non-AJAX ---
    return view('tr_prh.index', compact(
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
  
  private function generatetr_prh_Code(?Carbon $onDate = null, $branch = null): string
  {
    $date = $onDate ?: now();

    $branch = $branch
      ?? Auth::guard('sysuser')->user()?->fcabang
      ?? Auth::user()?->fcabang
      ?? null;

    $kodeCabang = null;

    if ($branch !== null) {
      $needle = trim((string)$branch);

      if (is_numeric($needle)) {
        $kodeCabang = DB::table('mscabang')
          ->where('fcabangid', (int)$needle)
          ->value('fcabangkode');
      } else {
        // cocokkan case-insensitive
        $kodeCabang = DB::table('mscabang')
          ->whereRaw('LOWER(fcabangkode) = LOWER(?)', [$needle])
          ->value('fcabangkode');

        if (!$kodeCabang) {
          $kodeCabang = DB::table('mscabang')
            ->whereRaw('LOWER(fcabangname) = LOWER(?)', [$needle])
            ->value('fcabangkode');
        }
      }
    }

    if (!$kodeCabang) {
      $kodeCabang = 'NA'; // fallback
    }

    $prefix = sprintf('PR.%s.%s.%s.', trim($kodeCabang), $date->format('y'), $date->format('m'));

    return DB::transaction(function () use ($prefix) {
      $last = \App\Models\Tr_prh::where('fprno', 'like', $prefix . '%')
        ->lockForUpdate()
        ->orderByDesc('fprno')
        ->first();

      $lastNum = 0;
      if ($last && ($pos = strrpos($last->fprno, '.')) !== false) {
        $lastNum = (int)substr($last->fprno, $pos + 1);
      }

      $next = str_pad((string)($lastNum + 1), 4, '0', STR_PAD_LEFT);
      return $prefix . $next; // PR.JK.25.08.0001
    });
  }

  public function print(string $fprno)
  {
    // subquery aman mengikuti $table dari model Supplier
    $supplierSub = (new Supplier)->getTable(); // e.g. ms_supplier

    $hdr = Tr_prh::query()
      ->leftJoin("{$supplierSub} as s", 's.fsupplierid', '=', 'tr_prh.fsupplier') // integer ↔ integer
      ->leftJoin('mscabang as c', 'c.fcabangkode', '=', 'tr_prh.fbranchcode')
      ->where('tr_prh.fprno', $fprno)
      ->first([
        'tr_prh.*',
        's.fsuppliername as supplier_name',
        'c.fcabangname as cabang_name',
      ]);

    abort_if(!$hdr, 404);

    $dt = Tr_prd::query()
      ->leftJoin('msprd as p', 'p.fprdid', '=', 'tr_prd.fprdcode')
      ->where('tr_prd.fprnoid', $hdr->fprid)
      ->orderBy('tr_prd.fprdcode')
      ->get([
        'tr_prd.*',
        'p.fprdname as product_name',
        'p.fminstock as stock',
      ]);

    $fmt = fn($d) => $d ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d F Y') : '-';

    return view('tr_prh.print', [
      'hdr' => $hdr,
      'dt'  => $dt,
      'fmt' => $fmt,
      'company_name' => config('app.company_name', 'PT.DEMO VERSION'),
      'company_city' => config('app.company_city', 'Tangerang'),
    ]);
  }

  public function create()
  {
    $supplier        = Supplier::all();

    $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

    $branch = DB::table('mscabang')
      ->when(is_numeric($raw), fn($q) => $q->where('fcabangid', (int)$raw))
      ->when(
        !is_numeric($raw),
        fn($q) => $q
          ->where('fcabangkode', $raw)
          ->orWhere('fcabangname', $raw)
      )
      ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

    $canApproval = in_array('approvalpr', explode(',', session('user_restricted_permissions', '')));

    $fcabang       = $branch->fcabangname ?? (string)$raw;   // untuk tampilan
    $fbranchcode   = $branch->fcabangkode ?? (string)$raw;   // untuk hidden post (JK)

    $newtr_prh_code = $this->generatetr_prh_Code(now(), $fbranchcode);

    $products = Product::select(
      'fprdid',
      'fprdcode',
      'fprdname',
      'fsatuankecil',
      'fsatuanbesar',
      'fsatuanbesar2',
      'fminstock'
    )->orderBy('fprdname')
      ->get();

    return view('tr_prh.create', [
      'newtr_prh_code' => $newtr_prh_code,
      'perms' => ['can_approval' => $canApproval],
      'supplier'       => $supplier,
      'fcabang'        => $fcabang,
      'fbranchcode'    => $fbranchcode,
      'products'       => $products,
    ]);
  }

  public function store(Request $request)
  {
    // VALIDATION
    $request->validate([
      'fprdate'      => ['nullable', 'date'],
      'fsupplier'    => ['nullable', 'string', 'max:10'],
      'fneeddate'    => ['nullable', 'date'],
      'fduedate'     => ['nullable', 'date'],
      'fket'         => ['nullable', 'string', 'max:300'],
      'fbranchcode'  => ['nullable', 'string', 'max:20'],

      'fitemcode'    => ['array'],
      'fitemcode.*'  => ['nullable', 'string', 'max:50'],

      'fsatuan'      => ['array'],
      'fsatuan.*'    => ['nullable', 'string', 'max:20'],

      'fqty'         => ['array'],
      'fqty.*'       => ['nullable'],

      'fqtypo'       => ['array'],
      'fqtypo.*'     => ['nullable'],

      'fdesc'        => ['array'],
      'fdesc.*'      => ['nullable', 'string'],

      'fketdt'       => ['array'],
      'fketdt.*'     => ['nullable', 'string', 'max:50'],

      'fapproval'    => ['nullable'],
    ]);

    // HEADER DATE + CODE
    $fprdate = $request->filled('fprdate')
      ? Carbon::parse($request->fprdate)->startOfDay()
      : now()->startOfDay();

    $branchFromForm = $request->input('fbranchcode');
    $fprno = $request->filled('fprno')
      ? $request->fprno
      : $this->generatetr_prh_Code($fprdate, $branchFromForm);

    $fneeddate = $request->filled('fneeddate') ? Carbon::parse($request->fneeddate)->startOfDay() : null;
    $fduedate  = $request->filled('fduedate')  ? Carbon::parse($request->fduedate)->startOfDay()  : null;

    $authUser = auth('sysuser')->user();
    $userName = $authUser->fname ?? null;

    // ARRAYS
    $codes   = $request->input('fitemcode', []);
    $sats    = $request->input('fsatuan', []);
    $qtys    = $request->input('fqty', []);
    $qtypos  = $request->input('fqtypo', []);
    $descs   = $request->input('fdesc', []);
    $ketdts  = $request->input('fketdt', []);

    // PRODUCT MAP: code -> (id, stock)
    $productMap = Product::whereIn('fprdcode', array_filter($codes))
      ->get(['fprdid', 'fprdcode', 'fminstock'])
      ->keyBy('fprdcode');

    // STOCK VALIDATION
    $validator = Validator::make([], []);
    foreach ($codes as $i => $codeRaw) {
      $code = trim($codeRaw ?? '');
      if ($code === '') continue;

      $max = (int)($productMap[$code]->fminstock ?? 0);
      $qty = (int)($qtys[$i] ?? 0);

      if ($max > 0 && $qty > $max) {
        $validator->errors()->add("fqty.$i", "Qty untuk produk $code tidak boleh melebihi stok ($max).");
      }
      if ($qty < 1) {
        $validator->errors()->add("fqty.$i", "Qty minimal 1.");
      }
    }
    if ($validator->fails()) {
      return back()->withErrors($validator)->withInput();
    }

    // CHECK DETAIL EXISTENCE
    $hasValidDetail = false;
    $rowCount = max(count($codes), count($sats), count($qtys), count($descs), count($ketdts), count($qtypos));
    for ($i = 0; $i < $rowCount; $i++) {
      $code = trim($codes[$i] ?? '');
      $sat  = trim($sats[$i] ?? '');
      $qty  = is_numeric($qtys[$i] ?? null) ? (int)$qtys[$i] : null;

      if ($code !== '' && $sat !== '' && is_numeric($qty) && $qty >= 1) {
        $hasValidDetail = true;
        break;
      }
    }
    if (!$hasValidDetail) {
      return back()->withInput()
        ->withErrors(['detail' => 'Minimal satu item detail dengan Kode, Satuan, dan Qty ≥ 1.']);
    }

    // TRANSACTION
    DB::transaction(function () use (
      $request,
      $fprno,
      $fprdate,
      $fneeddate,
      $fduedate,
      $userName,
      $codes,
      $sats,
      $qtys,
      $descs,
      $ketdts,
      $qtypos,
      $productMap
    ) {
      $isApproval = (int)($request->input('fapproval', 0));

      // CREATE HEADER
      $tr_prh = Tr_prh::create([
        'fprno'         => $fprno,
        'fprdate'       => $fprdate,
        'fsupplier'     => $request->fsupplier,
        'fprdin'        => '0',
        'fclose'        => '0',
        'fket'          => $request->fket,
        'fbranchcode'   => $request->fbranchcode,
        'fcreatedat'    => now(),
        'fneeddate'     => $fneeddate,
        'fduedate'      => $fduedate,
        'fuserid'       => $userName,
        'fuserapproved' => $request->has('fuserapproved') ? $userName : null,
        'fdateapproved' => $request->has('fuserapproved') ? now() : null,
        'fupdatedat'    => null,
        'fapproval'     => $isApproval,
      ]);

      // CREATE DETAILS
      $detailRows = [];
      $now = now();
      $rowCount = max(count($codes), count($sats), count($qtys), count($descs), count($ketdts), count($qtypos));

      for ($i = 0; $i < $rowCount; $i++) {
        $code   = trim($codes[$i] ?? '');
        $sat    = trim($sats[$i] ?? '');
        $qty    = is_numeric($qtys[$i] ?? null) ? (int)$qtys[$i] : null;

        $qtypoi = is_numeric($qtypos[$i] ?? null) ? (int)$qtypos[$i] : 0;
        if ($qty !== null && $qtypoi > $qty) $qtypoi = $qty;

        $desc   = $descs[$i]  ?? null;
        $ketdt  = $ketdts[$i] ?? null;

        if ($code !== '' && $sat !== '' && is_numeric($qty) && $qty >= 1) {
          $productId = (int)($productMap[$code]->fprdid ?? 0); // <-- use msprd.fprdid
          if ($productId === 0) continue;

          $detailRows[] = [
            'fprnoid'    => $tr_prh->fprid,   // header link
            'fprdcode'   => $productId,       // store msprd.fprdid
            'fqty'       => (int)$qty,
            'fqtypo'     => (int)$qtypoi,
            'fqtyremain' => (int)$qty,
            'fprice'     => 0,
            'fketdt'     => $ketdt,
            'fcreatedat' => $now,
            'fsatuan'    => $sat,
            'fdesc'      => $desc,
            'fuserid'    => $userName,
          ];
        }
      }

      Tr_prd::insert($detailRows);

      foreach ($codes as $i => $codeRaw) {
        $code = trim($codeRaw ?? '');
        $qty  = (int)($qtys[$i] ?? 0);
        if ($code !== '' && $qty > 0) {
          DB::table('msprd')
            ->where('fprdcode', $code)
            ->update([
              'fminstock'  => DB::raw("CAST(fminstock AS INTEGER) - $qty"),
              'fupdatedat' => now(),
            ]);
        }
      }

      if ($isApproval === 1) {
        $dt = Tr_prd::query()
          ->leftJoin('msprd as p', 'p.fprdid', '=', 'tr_prd.fprdcode') // join by product ID
          ->where('tr_prd.fprnoid', $tr_prh->fprid)
          ->orderBy('p.fprdname')
          ->get([
            'tr_prd.*',
            'p.fprdname as product_name',
            'p.fprdcode as product_code',
            'p.fminstock as stock',
          ]);

        $productNameList = $dt->pluck('product_name')->implode(', ');
        $approver = auth('sysuser')->user()->fname ?? $tr_prh->fuserid ?? 'System';

        Mail::to('vierybiliam8@gmail.com')
          ->send(new ApprovalEmail($tr_prh, $dt, $productNameList, $approver, 'Permintaan Pembelian (PR)'));
      }
    });

    return redirect()->route('tr_prh.create')
      ->with('success', 'Permintaan pembelian berhasil ditambahkan.');
  }

  public function edit($fprid)
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

    $tr_prh = Tr_prh::with(['details' => function ($q) {
      $q->leftJoin('msprd as p', 'p.fprdid', '=', 'tr_prd.fprdcode') // tr_prd.fprdcode = ID produk
        ->orderBy('p.fprdname')
        ->select(
          'tr_prd.*',
          'p.fprdcode as product_code',   // <- kode untuk tampilan
          'p.fprdname  as product_name'   // <- nama untuk tampilan
        );
    }])->findOrFail($fprid);

    // Map ke savedItems (agar cocok dengan table di Blade yang biasa kamu pakai)
    $savedItems = $tr_prh->details->map(function ($d) {
      return [
        'uid'        => $d->fprdid,                    // PK detail untuk :key
        'fitemcode'  => (string)($d->product_code ?? ''),    // KODE PRODUK (string)
        'fitemname'  => (string)($d->product_name ?? ''),    // NAMA PRODUK
        'fsatuan'    => (string)($d->fsatuan ?? ''),
        'frefdtno'   => (string)($d->frefdtno ?? ''),
        'fnouref'    => (string)($d->fnouref ?? ''),
        'fqty'       => (float)($d->fqty ?? 0),
        'fterima'    => (float)($d->fterima ?? 0),
        'fprice'     => (float)($d->fprice ?? 0),
        'fdisc'      => (float)($d->fdisc ?? 0),
        'ftotal'     => (float)($d->famount ?? 0),
        'fdesc'      => (string)($d->fdesc ?? ''),
        'fketdt'     => (string)($d->fketdt ?? ''),
        // kalau perlu kirim ID produk buat update:
        'fprdid'     => (int)($d->fprdcode ?? 0),       // ini ID produk di kolom detail
      ];
    })->values();

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
          'id'    => $p->fprdid,  // ⬅️ penting
          'name'  => $p->fprdname,
          'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
          'stock' => $p->fminstock ?? 0,
        ],
      ];
    })->toArray();

    return view('tr_prh.edit', [
      'supplier'     => $supplier,
      'fcabang'      => $fcabang,
      'fbranchcode'  => $fbranchcode,
      'products'     => $products,
      'productMap'   => $productMap, // jika dipakai di Blade
      'tr_prh'       => $tr_prh,     // <<— PENTING
      'savedItems'  => $savedItems,   // <— tambahkan ini
    ]);
  }

  public function update(Request $request, int $fprid)
  {
    // ===== 1) AMBIL HEADER DULU =====
    $header = Tr_prh::where('fprid', $fprid)->firstOrFail();
    $fprId  = (int) $header->fprid;   // FK integer utk detail

    // ===== 2) VALIDASI INPUT HEADER & DETAIL =====
    $request->validate([
      'fprdate'      => ['nullable', 'date'],
      'fsupplier'    => ['required', 'integer'],   // kolom fsupplier di DB bertipe integer
      'fneeddate'    => ['nullable', 'date'],
      'fduedate'     => ['nullable', 'date'],
      'fket'         => ['nullable', 'string', 'max:300'],
      'fbranchcode'  => ['nullable', 'string', 'max:20'],

      'fitemcode'    => ['array'],                 // kode produk (string)
      'fitemcode.*'  => ['nullable', 'string', 'max:50'],
      'fprdid'       => ['array'],                 // ID produk (integer) hidden dari form
      'fprdid.*'     => ['nullable', 'integer', 'min:1'],
      'fsatuan'      => ['array'],
      'fsatuan.*'    => ['nullable', 'string', 'max:20'],
      'fqty'         => ['array'],
      'fqty.*'       => ['nullable', 'numeric', 'min:1'],
      'fqtypo'       => ['array'],
      'fqtypo.*'     => ['nullable', 'numeric', 'min:0'],
      'fdesc'        => ['array'],
      'fdesc.*'      => ['nullable', 'string'],
      'fketdt'       => ['array'],
      'fketdt.*'     => ['nullable', 'string', 'max:50'],

      'fapproval'    => ['nullable', 'boolean'],
    ]);

    // ===== 3) PARSE TANGGAL (fallback ke nilai header bila kosong) =====
    $fprdate   = $request->filled('fprdate')
      ? \Carbon\Carbon::parse($request->fprdate)->startOfDay()
      : $header->fprdate;

    $fneeddate = $request->filled('fneeddate')
      ? \Carbon\Carbon::parse($request->fneeddate)->startOfDay()
      : $header->fneeddate;

    $fduedate  = $request->filled('fduedate')
      ? \Carbon\Carbon::parse($request->fduedate)->startOfDay()
      : $header->fduedate;

    // ===== 4) KUMPULKAN ARRAY DETAIL DARI FORM =====
    $codes   = $request->input('fitemcode', []);   // kode produk (string) untuk display
    $idsIn   = $request->input('fprdid', []);      // ID produk (integer) hidden dari form
    $sats    = $request->input('fsatuan', []);
    $qtys    = $request->input('fqty', []);
    $qtypos  = $request->input('fqtypo', []);
    $descs   = $request->input('fdesc', []);
    $ketdts  = $request->input('fketdt', []);

    // ===== 5) MAP KODE → ID produk (untuk baris yang punya kode valid) =====
    $codeIdMap = DB::table('msprd')
      ->whereIn('fprdcode', array_values(array_filter($codes)))
      ->pluck('fprdid', 'fprdcode');   // contoh: ['C-14' => 123]

    // ===== 6) VALIDASI STOK sederhana (opsional – bisa kamu sesuaikan) =====
    $stocks = DB::table('msprd')
      ->whereIn('fprdcode', array_values(array_filter($codes)))
      ->pluck('fminstock', 'fprdcode'); // map by kode (string)

    foreach ($codes as $i => $codeStr) {
      $qty = (int)($qtys[$i] ?? 0);
      if ($qty < 1) {
        return back()->withInput()->withErrors(["fqty.$i" => "Qty minimal 1."]);
      }
      $max = (int)($stocks[$codeStr] ?? 0);
      if ($max > 0 && $qty > $max) {
        return back()->withInput()->withErrors(["fqty.$i" => "Qty untuk produk $codeStr tidak boleh melebihi stok ($max)."]);
      }
    }

    // ===== 7) SUSUN BATCH DETAIL (PAKAI ID PRODUK!) =====
    $detailRows = [];
    $now = now();
    $rowCount = max(count($codes), count($idsIn), count($sats), count($qtys), count($qtypos), count($descs), count($ketdts));

    for ($i = 0; $i < $rowCount; $i++) {
      $codeStr = trim((string)($codes[$i] ?? ''));      // contoh: "C-14" (string)
      $idForm  = (int)($idsIn[$i]    ?? 0);             // contoh: 123 (integer)
      $prodId  = (int)($codeIdMap[$codeStr] ?? 0);      // mapping kode → ID

      // fallback: kalau mapping dari kode gagal, pakai ID dari hidden input
      if ($prodId === 0 && $idForm > 0) {
        $prodId = $idForm;
      }

      $sat   = trim((string)($sats[$i]  ?? ''));
      $qty   = (int)($qtys[$i]   ?? 0);
      $qtypo = (int)($qtypos[$i] ?? 0);
      $desc  = $descs[$i]  ?? null;
      $ket   = $ketdts[$i] ?? null;

      // Hanya terima baris valid: ada ID produk, satuan, qty >= 1
      if ($prodId > 0 && $sat !== '' && $qty >= 1) {
        $detailRows[] = [
          'fprnoid'     => $fprId,     // FK integer → tr_prh.fprid
          'fprdcode'    => $prodId,    // FK integer → msprd.fprdid (bukan kode string!)
          'fqty'        => $qty,
          'fqtypo'      => $qtypo,
          'fqtyremain'  => $qty,
          'fprice'      => 0,
          'fketdt'      => $ket,
          'fcreatedat'  => $now,
          'fsatuan'     => $sat,
          'fdesc'       => $desc,
          'fuserid'     => (Auth::user()->fname ?? 'system'),
        ];
      }
    }

    if (empty($detailRows)) {
      return back()->withInput()->withErrors([
        'detail' => 'Minimal satu item detail valid (ID Produk, Satuan, Qty ≥ 1).'
      ]);
    }

    // ===== 8) APPROVAL (opsional – set kalau diminta) =====
    $approveNow  = $request->boolean('fapproval');
    $setApproval = [];
    if ($approveNow && (empty($header->fuserapproved) && (int)$header->fapproval !== 1)) {
      $setApproval = [
        'fapproval'      => 1,
        'fuserapproved'  => (auth('sysuser')->user()->fname ?? (Auth::user()->fname ?? 'system')),
        'fdateapproved'  => now(),
      ];
    }

    // ===== 9) TRANSAKSI: update header, hapus detail lama, insert ulang =====
    DB::transaction(function () use (
      $request,
      $header,
      $fprId,
      $fprdate,
      $fneeddate,
      $fduedate,
      $detailRows,
      $codes,
      $qtys,
      $setApproval
    ) {
      // Update header via ID
      Tr_prh::where('fprid', $header->fprid)->update(array_merge([
        'fprdate'     => $fprdate,
        'fsupplier'   => (int)$request->fsupplier,
        'fprdin'      => '0',
        'fclose'      => $request->has('fclose') ? '1' : '0',
        'fket'        => $request->fket,
        'fbranchcode' => $request->fbranchcode,
        'fneeddate'   => $fneeddate,
        'fduedate'    => $fduedate,
        'fuserid'     => (Auth::user()->fname ?? 'system'),
        'fupdatedat'  => now(),
      ], $setApproval));

      // Hapus semua detail lama PR ini (berdasarkan FK integer)
      DB::table('tr_prd')->where('fprnoid', $fprId)->delete();

      // Insert ulang detail
      DB::table('tr_prd')->insert($detailRows);

      // (Opsional) Update stok berdasarkan KODE (msprd.fprdcode = kode string)
      // NOTE: ini sekadar contoh koreksi stok sederhana. Sesuaikan dengan bisnis proses kamu.
      foreach ($codes as $i => $codeStr) {
        $qty = (int)($qtys[$i] ?? 0);
        if ($qty > 0 && $codeStr !== '') {
          DB::table('msprd')
            ->where('fprdcode', $codeStr)   // berdasarkan kode string
            ->update([
              'fminstock'  => DB::raw("CAST(fminstock AS INTEGER) - {$qty}"),
              'fupdatedat' => now(),
            ]);
        }
      }
    });

    // ===== 10) SELESAI =====
    return redirect()
      ->route('tr_prh.edit', $fprid)
      ->with('success', 'Permintaan pembelian berhasil diperbarui.');
  }

  public function destroy($fsatuanid)
  {
    $satuan = Tr_prh::findOrFail($fsatuanid);
    $satuan->delete();

    return redirect()
      ->route('tr_prh.index')
      ->with('success', 'Satuan berhasil dihapus.');
  }
}
