<?php

namespace App\Http\Controllers;

use App\Models\Tr_prh;
use App\Models\Tr_prd;
use App\Models\SalesOrderHeader;
use App\Models\SalesOrderDetail;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\Salesman;
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

class SalesOrderController extends Controller
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
    $availableYears = SalesOrderHeader::selectRaw('DISTINCT EXTRACT(YEAR FROM fdatetime) as year')
      ->whereNotNull('fdatetime')
      ->orderByRaw('EXTRACT(YEAR FROM fdatetime) DESC')
      ->pluck('year');

    // --- Handle Request AJAX dari DataTables ---
    if ($request->ajax()) {

      $query = SalesOrderHeader::query();

      // DEBUG: Cek total data di tabel
      $totalRecords = SalesOrderHeader::count();
      Log::info('Total Records in DB: ' . $totalRecords);

      // Handle Search
      if ($search = $request->input('search.value')) {
        $query->where('fsono', 'like', "%{$search}%");
        Log::info('Search filter applied: ' . $search);
      }

      // Filter status - DEFAULT ke 'active' jika tidak ada
      $statusFilter = $request->query('status', 'active');
      Log::info('Status Filter: ' . $statusFilter);

      if ($statusFilter === 'active') {
        $query->where('fclose', '0');
      } elseif ($statusFilter === 'nonactive') {
        $query->where('fclose', '1');
      }
      // Jika 'all', tidak ada filter fclose

      // Filter tahun
      if ($year) {
        $query->whereRaw('EXTRACT(YEAR FROM fdatetime) = ?', [$year]);
        Log::info('Year filter applied: ' . $year);
      }

      // Filter bulan
      if ($month) {
        $query->whereRaw('EXTRACT(MONTH FROM fdatetime) = ?', [$month]);
        Log::info('Month filter applied: ' . $month);
      }

      // DEBUG: Print SQL Query
      Log::info('SQL Query: ' . $query->toSql());
      Log::info('Query Bindings: ' . json_encode($query->getBindings()));

      $filteredRecords = (clone $query)->count();
      Log::info('Filtered Records: ' . $filteredRecords);

      // Sorting
      $orderColIdx = $request->input('order.0.column', 0);
      $orderDir = $request->input('order.0.dir', 'asc');

      $sortableColumns = ['fsono', 'fsodate', 'fclose'];

      if (isset($sortableColumns[$orderColIdx])) {
        $query->orderBy($sortableColumns[$orderColIdx], $orderDir);
      }

      // Paginasi
      $start = $request->input('start', 0);
      $length = $request->input('length', 10);
      $records = $query->skip($start)
        ->take($length)
        ->get();

      Log::info('Records found: ' . $records->count());

      // Format Data
      $data = $records->map(function ($row) {
        return [
          'ftrsomtid'     => $row->ftrsomtid,
          'fbranchcode'   => $row->fbranchcode,
          'fsono'         => $row->fsono,
          'fsodate'       => $row->fsodate instanceof \Carbon\Carbon
            ? $row->fsodate->format('Y-m-d')
            : $row->fsodate,
          'frefno'        => $row->frefno ?? '',
          'fcustno'       => $row->fcustno ?? '',
          'fsalesman'     => $row->fsalesman,
          'fdiscpersen'   => $row->fdiscpersen,
          'fdiscount'     => $row->fdiscount,
          'famountgross'  => $row->famountgross,
          'famountsonet'  => $row->famountsonet,
          'famountpajak'  => $row->famountpajak,
          'famountso'     => $row->famountso,
          'fket'          => $row->fket,
          'falamatkirim'  => $row->falamatkirim,
          'fprdout'       => $row->fprdout,
          'fusercreate'   => $row->fusercreate,
          'fuserupdate'   => $row->fuserupdate,
          'fdatetime'     => $row->fdatetime,
          'fclose'        => $row->fclose,
          'fincludeppn'   => $row->fincludeppn,
          'fuseracc'      => $row->fuseracc,
          'fneedacc'      => $row->fneedacc,
          'ftempohr'      => $row->ftempohr,
          'fprint'        => $row->fprint,
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
    return view('salesorder.index', compact(
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

    $allowedColumns = ['fprno', 'fprdate'];
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
        'tr_prd.fprice as fharga',
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

    $last = DB::table('trsomt')
      ->where('fsono', 'like', $prefix . '%')
      ->selectRaw("MAX(CAST(split_part(fsono, '.', 5) AS int)) AS lastno")
      ->value('lastno');

    $next = (int)$last + 1;
    return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
  }

  public function print(string $fsono)
  {
    // Use the model’s actual table name
    $supplierTable = (new Supplier)->getTable(); // e.g. ms_supplier

    // Header: find by PO code (string)
    $hdr = SalesOrderHeader::query()
      ->leftJoin("{$supplierTable} as s", 's.fsupplierid', '=', 'salesorder.fsupplier') // integer ↔ integer
      ->leftJoin('mscabang as c', 'c.fcabangkode', '=', 'salesorder.fbranchcode')
      ->where('salesorder.fsono', $fsono)
      ->first([
        'salesorder.*',
        's.fsuppliername as supplier_name',
        'c.fcabangname as cabang_name',
      ]);

    if (!$hdr) {
      return redirect()->back()->with('error', 'PO tidak ditemukan.');
    }

    // Use header ID (integer) for detail FK
    $ftrsomtid = (int) $hdr->ftrsomtid;

    $dt = DB::table('trsodt')
      ->leftJoin('msprd as p', function ($j) {
        $j->on('p.fprdid', '=', DB::raw('CAST(trsodt.fprdcode AS INTEGER)'));
      })->where('trsodt.fsono', $ftrsomtid)                            // detail FK = header ID
      ->orderBy('trsodt.fprdcode')
      ->get([
        'trsodt.*',
        'p.fprdcode as product_code',
        'p.fprdname as product_name',
        'p.fminstock as stock',
        'trsodt.fqtyremain',
      ]);

    $fmt = fn($d) => $d
      ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d F Y')
      : '-';

    return view('salesorder.print', [
      'hdr'          => $hdr,
      'dt'           => $dt,
      'fmt'          => $fmt,
      'company_name' => config('app.company_name', 'PT. DEMO VERSION'),
      'company_city' => config('app.company_city', 'Tangerang'),
    ]);
  }

  public function create(Request $request)
  {
    $customers = Customer::orderBy('fcustomername', 'asc')
      ->get(['fcustomerid', 'fcustomername']);

    $salesmans = Salesman::orderBy('fsalesmanname', 'asc')
      ->get(['fsalesmanid', 'fsalesmanname']);

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

    return view('salesorder.create', [
      'newtr_prh_code' => $newtr_prh_code,
      'perms' => ['can_approval' => $canApproval],
      'customers' => $customers,
      'salesmans' => $salesmans,
      'fcabang' => $fcabang,
      'fbranchcode' => $fbranchcode,
      'products' => $products,
      'filterSupplierId' => $request->query('filter_supplier_id'),
      'filterSalesmanId' => $request->query('filter_salesman_id'),
    ]);
  }

  public function store(Request $request)
  {
    // VALIDATION
    $request->validate([
      'fsono'        => ['nullable', 'string', 'max:25'],
      'fsodate'      => ['required', 'date'],
      'fkirimdate'   => ['nullable', 'date'],
      'fcustno'      => ['required', 'string', 'max:10'],
      'fsalesman'    => ['nullable', 'string', 'max:15'],
      'fincludeppn'  => ['nullable'],
      'fket'         => ['nullable', 'string', 'max:300'],
      'falamatkirim' => ['nullable', 'string', 'max:300'],
      'fbranchcode'  => ['nullable', 'string', 'max:2'],
      'ftempohr'     => ['nullable', 'string', 'max:3'],

      'fitemcode'    => ['required', 'array', 'min:1'],
      'fitemcode.*'  => ['required', 'string', 'max:20'],

      'fsatuan'      => ['nullable', 'array'],
      'fsatuan.*'    => ['nullable', 'string', 'max:10'],

      'fitemname'    => ['nullable', 'array'],
      'fitemname.*'  => ['nullable', 'string', 'max:200'],

      'fqty'         => ['required', 'array'],
      'fqty.*'       => ['numeric', 'min:0'],

      'fprice'       => ['nullable', 'array'],
      'fprice.*'     => ['numeric', 'min:0'],

      'fdisc'        => ['nullable', 'array'],
      'fdisc.*'      => ['nullable'], // ✅ UBAH: hapus validasi numeric, karena bisa string "10+2"
    ], [
      'fsodate.required'   => 'Tanggal SO wajib diisi.',
      'fcustno.required'   => 'Customer wajib diisi.',
      'fitemcode.required' => 'Minimal 1 item.',
    ]);

    // HEADER VALUES
    $fsodate      = Carbon::parse($request->fsodate)->startOfDay();
    $fsono        = $request->input('fsono');
    $fincludeppn  = $request->boolean('fincludeppn') ? '1' : '0';
    $userid       = auth('sysuser')->user()->fname ?? 'admin';
    $now          = now();

    // DETAIL ARRAYS
    $itemCodes    = $request->input('fitemcode', []);
    $itemNames    = $request->input('fitemname', []);
    $satuans      = $request->input('fsatuan', []);
    $qtys         = $request->input('fqty', []);
    $prices       = $request->input('fprice', []);
    $discs        = $request->input('fdisc', []);

    // BUILD DETAIL ROWS
    $rowsSodt     = [];
    $totalGross   = 0.0;
    $totalDisc    = 0.0;
    $rowCount     = max(
      count($itemCodes),
      count($satuans),
      count($qtys),
      count($prices),
      count($discs),
      count($itemNames)
    );

    for ($i = 0; $i < $rowCount; $i++) {
      $itemCode   = trim($itemCodes[$i] ?? '');
      $itemName   = trim((string)($itemNames[$i] ?? ''));
      $satuan     = trim((string)($satuans[$i] ?? ''));
      $qty        = (float)($qtys[$i] ?? 0);
      $price      = (float)($prices[$i] ?? 0);
      $discInput  = $discs[$i] ?? 0; // ✅ Simpan input asli dulu

      // ✅ PARSE DISCOUNT: support format "10+2"
      $discPersen = $this->parseDiscount($discInput);

      // Calculate amount
      $subtotal = $qty * $price;

      // Apply discount percentage
      $discount = $subtotal * ($discPersen / 100);
      $amount = $subtotal - $discount;

      $totalGross += $subtotal;
      $totalDisc += $discount;

      $rowsSodt[] = [
        'fitemno'     => mb_substr($itemCode, 0, 20),
        'fitemdesc'   => mb_substr($itemName, 0, 200),
        'funit'       => mb_substr($satuan, 0, 10),
        'fqty'        => $qty,
        'fprice'      => $price,
        'fdiscpersen' => $discPersen,
        'fdiscount'   => round($discount, 2),
        'famount'     => round($amount, 2),
      ];
    }

    // Calculate totals
    $amountNet = $totalGross - $totalDisc;
    $ppnRate   = $fincludeppn === '1' ? (float)$request->input('ppn_rate', 11) : 0;
    $ppnAmount = $amountNet * ($ppnRate / 100);
    $grandTotal = $amountNet + $ppnAmount;

    // TRANSACTION
    DB::transaction(function () use (
      $request,
      $fsodate,
      $fincludeppn,
      $userid,
      $now,
      $rowsSodt,
      $fsono,
      $totalGross,
      $totalDisc,
      $amountNet,
      $ppnAmount,
      $grandTotal
    ) {
      // Generate fsono if not provided
      if (empty($fsono)) {
        $rawBranch = $request->input('fbranchcode');
        $kodeCabang = null;

        if ($rawBranch !== null) {
          $needle = trim((string)$rawBranch);
          if (strlen($needle) <= 2) {
            $kodeCabang = $needle;
          } else {
            $kodeCabang = DB::table('mscabang')
              ->whereRaw('LOWER(fcabangcode)=LOWER(?)', [$needle])
              ->value('fcabangcode')
              ?: DB::table('mscabang')
              ->whereRaw('LOWER(fcabangname)=LOWER(?)', [$needle])
              ->value('fcabangcode');
          }
        }

        if (!$kodeCabang) $kodeCabang = 'NA';

        $yy = $fsodate->format('y');
        $mm = $fsodate->format('m');
        $prefix = sprintf('SO.%s.%s.%s.', $kodeCabang, $yy, $mm);

        // Advisory lock per (branch, y-m)
        $lockKey = crc32('SO|' . $kodeCabang . '|' . $fsodate->format('Y-m'));
        DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

        // Get last sequence under this prefix
        $last = DB::table('trsomt')
          ->where('fsono', 'like', $prefix . '%')
          ->selectRaw("MAX(CAST(split_part(fsono, '.', 5) AS int)) AS lastno")
          ->value('lastno');

        $next = (int)$last + 1;
        $fsono = $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
      }

      $ftempohr = $request->input('ftempohr', '0');

      // INSERT HEADER and GET ftrsomtid
      $ftrsomtid = DB::table('trsomt')->insertGetId([
        'fsono'         => $fsono,
        'fsodate'       => $fsodate,
        'fbranchcode'   => mb_substr($request->input('fbranchcode', ''), 0, 2),
        'fcustno'       => mb_substr($request->input('fcustno', ''), 0, 10),
        'fsalesman'     => mb_substr($request->input('fsalesman', ''), 0, 15),
        'ftempohr'      => mb_substr($ftempohr, 0, 3),
        'fincludeppn'   => $fincludeppn,
        'fket'          => mb_substr($request->input('fket', ''), 0, 300),
        'fketinternal'  => mb_substr($request->input('fketinternal', ''), 0, 300),
        'falamatkirim'  => mb_substr($request->input('falamatkirim', ''), 0, 300),
        'fusercreate'   => mb_substr($userid, 0, 10),
        'fdatetime'     => $now,
        'famountgross'  => round($totalGross, 2),
        'fdiscount'     => round($totalDisc, 2),
        'famountsonet'  => round($amountNet, 2),
        'famountpajak'  => round($ppnAmount, 2),
        'famountso'     => round($grandTotal, 2),
        'fprdout'       => '0',
        'fclose'        => '0',
        'fneedacc'      => '0',
        'fuseracc'      => '0',
        'fprint'        => 0,
      ], 'ftrsomtid');

      // INSERT DETAILS with ftrsomtid FK
      foreach ($rowsSodt as &$r) {
        $r['fsono'] = $fsono;
        $r['ftrsomtid'] = $ftrsomtid;
      }
      unset($r);

      DB::table('trsodt')->insert($rowsSodt);
    });

    return redirect()
      ->route('salesorder.index')
      ->with('success', "Sales Order {$fsono} berhasil disimpan.");
  }

  // ✅ TAMBAHKAN METHOD HELPER UNTUK PARSE DISCOUNT
  private function parseDiscount($discInput)
  {
    if ($discInput === null || $discInput === '') return 0;

    // Jika sudah berupa angka
    if (is_numeric($discInput)) {
      return (float)$discInput;
    }

    // Jika string, parse ekspresi matematika
    $str = trim((string)$discInput);

    if ($str === '') return 0;

    // Jika angka biasa
    if (is_numeric($str)) {
      return (float)$str;
    }

    // Parse ekspresi seperti "10+2"
    try {
      // Hapus spasi
      $cleaned = preg_replace('/\s+/', '', $str);

      // Evaluasi ekspresi
      $result = eval("return {$cleaned};");

      // Batasi 0-100%
      $final = max(0, min(100, (float)$result));

      return $final;
    } catch (\Throwable $e) {
      return 0;
    }
  }
  public function edit(Request $request, $ftrsomtid)
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

    $salesorder = SalesOrderHeader::with(['details' => function ($q) {
      $q->orderBy('fpodid')
        ->leftJoin('msprd', function ($j) {
          $j->on('msprd.fprdid', '=', DB::raw('CAST(trsodt.fprdcode AS INTEGER)'));
        })
        ->select(
          'trsodt.*',
          'msprd.fprdcode as fitemcode',
          'msprd.fprdname'
        );
    }])->findOrFail($ftrsomtid);

    $savedItems = $salesorder->details->map(function ($d) {
      return [
        'uid'        => $d->fpodid,
        'fitemcode'  => (string)($d->fitemcode ?? ''),  // dari alias msprd.fprdcode
        'fitemname'  => (string)($d->fprdname ?? ''),   // dari msprd.fprdname
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
      ];
    })->values();
    $selectedSupplierCode = $salesorder->fsupplier;

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
    return view('salesorder.edit', [
      'suppliers'      => $suppliers,
      'selectedSupplierCode' => $selectedSupplierCode, // Kirim kode supplier ke view
      'fcabang'      => $fcabang,
      'fbranchcode'  => $fbranchcode,
      'products'     => $products,
      'productMap'   => $productMap,
      'salesorder'       => $salesorder,
      'savedItems'   => $savedItems,
      'ppnAmount'    => (float) ($salesorder->famountpopajak ?? 0), // total PPN from DB
      'famountgross'    => (float) ($salesorder->famountgross ?? 0),  // nilai Grand Total dari DB
      'famountso'    => (float) ($salesorder->famountso ?? 0),  // nilai Grand Total dari DB
      'filterSupplierId' => $request->query('filter_supplier_id'),
      'action' => 'edit'
    ]);
  }

  public function view(Request $request, $ftrsomtid)
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

    $salesorder = SalesOrderHeader::with(['details' => function ($q) {
      $q->orderBy('fpodid')
        ->leftJoin('msprd', function ($j) {
          $j->on('msprd.fprdid', '=', DB::raw('CAST(trsodt.fprdcode AS INTEGER)'));
        })
        ->select(
          'trsodt.*',
          'msprd.fprdcode as fitemcode',
          'msprd.fprdname'
        );
    }])->findOrFail($ftrsomtid);

    $savedItems = $salesorder->details->map(function ($d) {
      return [
        'uid'        => $d->fpodid,
        'fitemcode'  => (string)($d->fitemcode ?? ''),  // dari alias msprd.fprdcode
        'fitemname'  => (string)($d->fprdname ?? ''),   // dari msprd.fprdname
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
      ];
    })->values();
    $selectedSupplierCode = $salesorder->fsupplier;

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
    return view('salesorder.view', [
      'suppliers'      => $suppliers,
      'selectedSupplierCode' => $selectedSupplierCode, // Kirim kode supplier ke view
      'fcabang'      => $fcabang,
      'fbranchcode'  => $fbranchcode,
      'products'     => $products,
      'productMap'   => $productMap,
      'salesorder'       => $salesorder,
      'savedItems'   => $savedItems,
      'ppnAmount'    => (float) ($salesorder->famountpopajak ?? 0), // total PPN from DB
      'famountgross'    => (float) ($salesorder->famountgross ?? 0),  // nilai Grand Total dari DB
      'famountso'    => (float) ($salesorder->famountso ?? 0),  // nilai Grand Total dari DB
      'filterSupplierId' => $request->query('filter_supplier_id'),
    ]);
  }

  public function update(Request $request, $ftrsomtid)
  {
    // VALIDASI
    $request->validate([
      'fsono'        => ['nullable', 'string', 'max:25'],
      'fsodate'      => ['required', 'date'],
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
      'fsodate.required'   => 'Tanggal PO wajib diisi.',
      'fsupplier.required' => 'Supplier wajib diisi.',
      'fitemcode.required' => 'Minimal 1 item.',
    ]);

    $header  = SalesOrderHeader::where('ftrsomtid', $ftrsomtid)->firstOrFail();
    $fponoId = (int) $header->ftrsomtid;   // ← PAKAI INI, BUKAN $header->fsono (string)

    // HEADER DATA
    $fsodate    = \Carbon\Carbon::parse($request->fsodate)->startOfDay();
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

    $totalHarga  = (float) $request->input('famountgross', 0);
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
        'fdisc'       => (string)$discP,
        'fprice'      => $price,
        'fprice_rp'   => $price,
        'fpricegross' => $priceGross,
        'fpricenet'   => $priceNet,
        'famount'     => $amount,
        'famount_rp'  => $amount,
        'fuserupdate'     => (Auth::user()->fname ?? 'system'),
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
    DB::transaction(function () use ($request, $header, $fsodate, $fkirimdate, $userid, $now, $rowsPod, $fponoId, $totalHarga, $ppnAmount, $grandTotal) {

      $fcurrency = $request->input('fcurrency', 'IDR');
      $frate     = $request->input('frate', 15500);
      $fincludeppn = $request->boolean('fincludeppn') ? 1 : 0;

      // Update header
      $header->update([
        'fsodate'        => $fsodate,
        'fkirimdate'     => $fkirimdate,
        'fcurrency'      => $fcurrency,
        'frate'          => $frate,
        'fsupplier'      => $request->input('fsupplier'),
        'fincludeppn'    => $fincludeppn,
        'fket'           => $request->input('fket'),
        'fuserupdate'     => (Auth::user()->fname ?? 'system'),
        'fdatetime'      => $now,
        'famountgross'   => round($totalHarga, 2),
        'famountpopajak' => $ppnAmount,
        'famountso'      => $grandTotal,
      ]);

      // Hapus detail lama berdasarkan ID header
      DB::table('trsodt')->where('fsono', $fponoId)->delete();

      // Isi FK detail dengan ID header
      $nextNou = 1;
      foreach ($rowsPod as &$r) {
        $r['fsono'] = $fponoId;  // ← integer FK
        $r['fnou']  = $nextNou++;
      }
      unset($r);

      DB::table('trsodt')->insert($rowsPod);
    });

    // Pesan sukses tetap pakai nomor PO string untuk tampilan
    return redirect()
      ->route('salesorder.index')
      ->with('success', "PO {$header->fsono} berhasil diperbarui.");
  }

  public function delete(Request $request, $ftrsomtid)
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

    $salesorder = SalesOrderHeader::with(['details' => function ($q) {
      $q->orderBy('fpodid')
        ->leftJoin('msprd', function ($j) {
          $j->on('msprd.fprdid', '=', DB::raw('CAST(trsodt.fprdcode AS INTEGER)'));
        })
        ->select(
          'trsodt.*',
          'msprd.fprdcode as fitemcode',
          'msprd.fprdname'
        );
    }])->findOrFail($ftrsomtid);

    $savedItems = $salesorder->details->map(function ($d) {
      return [
        'uid'        => $d->fpodid,
        'fitemcode'  => (string)($d->fitemcode ?? ''),  // dari alias msprd.fprdcode
        'fitemname'  => (string)($d->fprdname ?? ''),   // dari msprd.fprdname
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
      ];
    })->values();
    $selectedSupplierCode = $salesorder->fsupplier;

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
    return view('salesorder.edit', [
      'suppliers'      => $suppliers,
      'selectedSupplierCode' => $selectedSupplierCode, // Kirim kode supplier ke view
      'fcabang'      => $fcabang,
      'fbranchcode'  => $fbranchcode,
      'products'     => $products,
      'productMap'   => $productMap,
      'salesorder'       => $salesorder,
      'savedItems'   => $savedItems,
      'ppnAmount'    => (float) ($salesorder->famountpopajak ?? 0), // total PPN from DB
      'famountgross'    => (float) ($salesorder->famountgross ?? 0),  // nilai Grand Total dari DB
      'famountso'    => (float) ($salesorder->famountso ?? 0),  // nilai Grand Total dari DB
      'filterSupplierId' => $request->query('filter_supplier_id'),
      'action' => 'delete'
    ]);
  }

  public function destroy($ftrsomtid)
  {
    try {
      $salesorder = SalesOrderHeader::findOrFail($ftrsomtid);
      $salesorder->delete();

      return redirect()->route('salesorder.index')->with('success', 'Data Order Pembelian ' . $salesorder->fsono . ' berhasil dihapus.');
    } catch (\Exception $e) {
      // Jika terjadi kesalahan saat menghapus, kembali ke halaman delete dengan pesan error
      return redirect()->route('salesorder.delete', $ftrsomtid)->with('error', 'Gakey: gal menghapus data: ' . $e->getMessage());
    }
  }
}
