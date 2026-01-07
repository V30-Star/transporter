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

      // Handle Search
      if ($search = $request->input('search.value')) {
        $query->where('fsono', 'like', "%{$search}%");
      }

      // Filter status - DEFAULT ke 'active' jika tidak ada
      $statusFilter = $request->query('status', 'active');

      if ($statusFilter === 'active') {
        $query->where('fclose', '0');
      } elseif ($statusFilter === 'nonactive') {
        $query->where('fclose', '1');
      }
      // Jika 'all', tidak ada filter fclose

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
      ->leftJoin('msprd as m', 'm.fprdid', '=', 'tr_prd.fitemid')
      ->select([
        'tr_prd.fprdid as frefdtno',
        'tr_prd.fprnoid as fnouref',
        'tr_prd.fitemid as fitemcode',
        'm.fprdname as fitemname',
        'tr_prd.fqty',
        'tr_prd.fsatuan as fsatuan',
        'tr_prd.fprnoid',
        'tr_prd.fprice as fharga',
        DB::raw('0::numeric as fdiskon')
      ])
      ->orderBy('tr_prd.fitemid')
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
    // Header: find by SO code (string)
    $hdr = DB::table('trsomt')
      ->leftJoin('mscustomer as c', 'c.fcustomerid', '=', DB::raw('CAST(trsomt.fcustno AS INTEGER)'))
      ->leftJoin('mscabang as b', 'b.fcabangkode', '=', 'trsomt.fbranchcode')
      ->leftJoin('mssalesman as s', 's.fsalesmanid', '=', DB::raw('CAST(trsomt.fsalesman AS INTEGER)'))
      ->where('trsomt.fsono', $fsono)
      ->first([
        'trsomt.*',
        'c.fcustomername as customer_name',
        'b.fcabangname as cabang_name',
        's.fsalesmanname as salesman_name',
      ]);

    if (!$hdr) {
      return redirect()->back()->with('error', 'Sales Order tidak ditemukan.');
    }

    // Use header ID (integer) for detail FK
    $ftrsomtid = (int) $hdr->ftrsomtid;

    // Detail: join dengan product
    $dt = DB::table('trsodt')
      ->leftJoin('msprd as p', function ($j) {
        $j->on('p.fprdid', '=', 'trsodt.fitemid');
      })
      ->where('trsodt.ftrsomtid', $ftrsomtid)
      ->orderBy('trsodt.ftrsodtid')
      ->get([
        'trsodt.*',
        'p.fprdcode as product_code',
        'p.fprdname as product_name',
        'p.fminstock as stock',
      ]);

    // Format date helper
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
    $itemId       = $request->input('fitemid', []);
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
      $itemeId = trim($itemId[$i] ?? '');
      $itemCode   = trim($itemCodes[$i] ?? '');
      $itemName   = trim((string)($itemNames[$i] ?? ''));
      $satuan     = trim((string)($satuans[$i] ?? ''));
      $qty        = (float)($qtys[$i] ?? 0);
      $price      = (float)($prices[$i] ?? 0);
      $discInput  = $discs[$i] ?? 0; // ✅ Simpan input asli dulu

      if (empty($itemCode) || $qty <= 0) {
        continue;
      }

      // ✅ PARSE DISCOUNT: support format "10+2"
      $discPersen = $this->parseDiscount($discInput);

      // Calculate amount
      $subtotal = $qty * $price;

      // Apply discount percentage
      $discount = $subtotal * ($discPersen / 100);
      $amount = $subtotal - $discount;

      $totalGross += $subtotal;
      $totalDisc += $discount;

      if (empty($itemeId) && !empty($itemCode)) {
        $itemeId = DB::table('msprd')
          ->where('fprdcode', '=', $itemCode) // ✅ Gunakan binding parameter
          ->value('fprdid');
      }

      $rowsSodt[] = [
        'fitemid'     => mb_substr($itemeId ?: $itemCode, 0, 20), // Fallback ke itemCode
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
    $customers = Customer::orderBy('fcustomername', 'asc')
      ->get(['fcustomerid', 'fcustomername']);

    $salesmans = Salesman::orderBy('fsalesmanname', 'asc')
      ->get(['fsalesmanid', 'fsalesmanname']);

    $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

    $branch = DB::table('mscabang')
      ->when(is_numeric($raw), fn($q) => $q->where('fcabangid', (int) $raw))
      ->when(!is_numeric($raw), fn($q) => $q
        ->where('fcabangkode', $raw)
        ->orWhere('fcabangname', $raw))
      ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

    $fcabang     = $branch->fcabangname ?? (string) $raw;   // tampilan
    $fbranchcode = $branch->fcabangkode ?? (string) $raw;   // hidden post

    $salesorder = SalesOrderHeader::with(['customer', 'details' => function ($q) { // TAMBAHKAN 'customer' di sini
      $q->orderBy('ftrsomtid')
        ->leftJoin('msprd', function ($j) {
          $j->on('msprd.fprdid', '=', DB::raw('CAST(trsodt.fitemid AS INTEGER)'));
        })
        ->select(
          'trsodt.*',
          'msprd.fprdcode as fitemcode',
          'msprd.fprdname'
        );
    }])->findOrFail($ftrsomtid);

    if (!$salesorder->customer) {
      $salesorder->setRelation('customer', Customer::find(trim($salesorder->fcustno)));
    }

    $savedItems = $salesorder->details->map(function ($d) {
      return [
        'uid'        => $d->fpodid,
        'fitemcode'  => (string)($d->fitemcode ?? ''),  // dari alias msprd.fprdid
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
        $p->fitemid => [
          'name'  => $p->fprdname,
          'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
          'stock' => $p->fminstock ?? 0,
        ],
      ];
    })->toArray();

    // Pass the data to the view
    return view('salesorder.edit', [
      'customers' => $customers,
      'salesmans' => $salesmans,
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
      'filterSalesmanId' => $request->query('filter_salesman_id'),
      'action' => 'edit'
    ]);
  }

  public function view(Request $request, $ftrsomtid)
  {
    $customers = Customer::orderBy('fcustomername', 'asc')
      ->get(['fcustomerid', 'fcustomername']);

    $salesmans = Salesman::orderBy('fsalesmanname', 'asc')
      ->get(['fsalesmanid', 'fsalesmanname']);
    $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

    $branch = DB::table('mscabang')
      ->when(is_numeric($raw), fn($q) => $q->where('fcabangid', (int) $raw))
      ->when(!is_numeric($raw), fn($q) => $q
        ->where('fcabangkode', $raw)
        ->orWhere('fcabangname', $raw))
      ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

    $fcabang     = $branch->fcabangname ?? (string) $raw;   // tampilan
    $fbranchcode = $branch->fcabangkode ?? (string) $raw;   // hidden post

    $salesorder = SalesOrderHeader::with(['customer', 'details' => function ($q) { // TAMBAHKAN 'customer' di sini
      $q->orderBy('ftrsomtid')
        ->leftJoin('msprd', function ($j) {
          $j->on('msprd.fprdid', '=', DB::raw('CAST(trsodt.fitemid AS INTEGER)'));
        })
        ->select(
          'trsodt.*',
          'msprd.fprdcode as fitemcode',
          'msprd.fprdname'
        );
    }])->findOrFail($ftrsomtid);

    if (!$salesorder->customer) {
      $salesorder->setRelation('customer', Customer::find(trim($salesorder->fcustno)));
    }

    $savedItems = $salesorder->details->map(function ($d) {
      return [
        'uid'        => $d->fpodid,
        'fitemcode'  => (string)($d->fitemcode ?? ''),  // dari alias msprd.fprdid
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
        $p->fitemid => [
          'name'  => $p->fprdname,
          'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
          'stock' => $p->fminstock ?? 0,
        ],
      ];
    })->toArray();

    // Pass the data to the view
    return view('salesorder.view', [
      'customers' => $customers,
      'salesmans' => $salesmans,
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
      'filterSalesmanId' => $request->query('filter_salesman_id'),
    ]);
  }

  public function update(Request $request, $ftrsomtid)
  {
    // 1. VALIDATION (Sama seperti store)
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
      'fdisc.*'      => ['nullable'], // Support "10+2"
    ], [
      'fsodate.required'   => 'Tanggal SO wajib diisi.',
      'fcustno.required'   => 'Customer wajib diisi.',
      'fitemcode.required' => 'Minimal 1 item.',
    ]);

    // 2. LOAD HEADER
    $header = DB::table('trsomt')->where('ftrsomtid', $ftrsomtid)->first();
    if (!$header) {
      return abort(404, 'Sales Order tidak ditemukan.');
    }

    // 3. HEADER VALUES
    $fsodate     = Carbon::parse($request->fsodate)->startOfDay();
    $fincludeppn = $request->boolean('fincludeppn') ? '1' : '0';
    $userid      = auth('sysuser')->user()->fname ?? 'admin';
    $now         = now();

    // 4. DETAIL ARRAYS
    $itemId    = $request->input('fitemid', []);
    $itemCodes = $request->input('fitemcode', []);
    $itemNames = $request->input('fitemname', []);
    $satuans   = $request->input('fsatuan', []);
    $qtys      = $request->input('fqty', []);
    $prices    = $request->input('fprice', []);
    $discs     = $request->input('fdisc', []);

    // 5. BUILD DETAIL ROWS (Logika sama dengan store)
    $rowsSodt   = [];
    $totalGross = 0.0;
    $totalDisc  = 0.0;
    $rowCount   = max(
      count($itemCodes),
      count($satuans),
      count($qtys),
      count($prices),
      count($discs),
      count($itemNames)
    );

    for ($i = 0; $i < $rowCount; $i++) {
      $itemeId   = trim($itemId[$i] ?? '');
      $itemCode  = trim($itemCodes[$i] ?? '');
      $itemName  = trim((string)($itemNames[$i] ?? ''));
      $satuan    = trim((string)($satuans[$i] ?? ''));
      $qty       = (float)($qtys[$i] ?? 0);
      $price     = (float)($prices[$i] ?? 0);
      $discInput = $discs[$i] ?? 0;

      if (empty($itemCode) || $qty <= 0) {
        continue;
      }

      // Parse discount (support "10+2")
      $discPersen = $this->parseDiscount($discInput);

      // Calculate amounts
      $subtotal = $qty * $price;
      $discount = $subtotal * ($discPersen / 100);
      $amount   = $subtotal - $discount;

      $totalGross += $subtotal;
      $totalDisc  += $discount;

      if (empty($itemeId) && !empty($itemCode)) {
        $itemeId = DB::table('msprd')
          ->where('fprdcode', $itemCode) // ✅ Gunakan fprdcode, bukan fprdid
          ->value('fprdid'); // Return fprdid (integer)
      }

      $rowsSodt[] = [
        'ftrsomtid'   => $ftrsomtid, // Foreign Key
        'fsono'       => $header->fsono, // Gunakan fsono yang sudah ada
        'fitemid'     => !empty($itemeId) && is_numeric($itemeId) ? (int)$itemeId : null,
        'fitemno'     => mb_substr($itemCode, 0, 20), // Kode produk (string)
        'fitemdesc'   => mb_substr($itemName, 0, 200),
        'funit'       => mb_substr($satuan, 0, 10),
        'fqty'        => $qty,
        'fprice'      => $price,
        'fdiscpersen' => $discPersen,
        'fdiscount'   => round($discount, 2),
        'famount'     => round($amount, 2),
      ];
    }

    // 6. CALCULATE TOTALS
    $amountNet  = $totalGross - $totalDisc;
    $ppnRate    = $fincludeppn === '1' ? (float)$request->input('ppn_rate', 11) : 0;
    $ppnAmount  = $amountNet * ($ppnRate / 100);
    $grandTotal = $amountNet + $ppnAmount;

    // 7. TRANSACTION
    DB::transaction(function () use (
      $request,
      $ftrsomtid,
      $header,
      $fsodate,
      $fincludeppn,
      $userid,
      $now,
      $rowsSodt,
      $totalGross,
      $totalDisc,
      $amountNet,
      $ppnAmount,
      $grandTotal
    ) {
      // Update Header
      DB::table('trsomt')->where('ftrsomtid', $ftrsomtid)->update([
        'fsodate'       => $fsodate,
        'fbranchcode'   => mb_substr($request->input('fbranchcode', ''), 0, 2),
        'fcustno'       => mb_substr($request->input('fcustno', ''), 0, 10),
        'fsalesman'     => mb_substr($request->input('fsalesman', ''), 0, 15),
        'ftempohr'      => mb_substr($request->input('ftempohr', '0'), 0, 3),
        'fincludeppn'   => $fincludeppn,
        'fket'          => mb_substr($request->input('fket', ''), 0, 300),
        'fketinternal'  => mb_substr($request->input('fketinternal', ''), 0, 300),
        'falamatkirim'  => mb_substr($request->input('falamatkirim', ''), 0, 300),
        'fuserupdate'   => mb_substr($userid, 0, 10),
        'fdatetime'     => $now,
        'famountgross'  => round($totalGross, 2),
        'fdiscount'     => round($totalDisc, 2),
        'famountsonet'  => round($amountNet, 2),
        'famountpajak'  => round($ppnAmount, 2),
        'famountso'     => round($grandTotal, 2),
      ]);

      // Delete old details and insert new ones
      DB::table('trsodt')->where('ftrsomtid', $ftrsomtid)->delete();

      if (!empty($rowsSodt)) {
        DB::table('trsodt')->insert($rowsSodt);
      }
    });

    return redirect()
      ->route('salesorder.index')
      ->with('success', "Sales Order {$header->fsono} berhasil diperbarui.");
  }

  public function delete(Request $request, $ftrsomtid)
  {
    $customers = Customer::orderBy('fcustomername', 'asc')
      ->get(['fcustomerid', 'fcustomername']);

    $salesmans = Salesman::orderBy('fsalesmanname', 'asc')
      ->get(['fsalesmanid', 'fsalesmanname']);

    $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

    $branch = DB::table('mscabang')
      ->when(is_numeric($raw), fn($q) => $q->where('fcabangid', (int) $raw))
      ->when(!is_numeric($raw), fn($q) => $q
        ->where('fcabangkode', $raw)
        ->orWhere('fcabangname', $raw))
      ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

    $fcabang     = $branch->fcabangname ?? (string) $raw;   // tampilan
    $fbranchcode = $branch->fcabangkode ?? (string) $raw;   // hidden post

    $salesorder = SalesOrderHeader::with(['customer', 'details' => function ($q) { // TAMBAHKAN 'customer' di sini
      $q->orderBy('ftrsomtid')
        ->leftJoin('msprd', function ($j) {
          $j->on('msprd.fprdid', '=', DB::raw('CAST(trsodt.fitemid AS INTEGER)'));
        })
        ->select(
          'trsodt.*',
          'msprd.fprdcode as fitemcode',
          'msprd.fprdname'
        );
    }])->findOrFail($ftrsomtid);

    if (!$salesorder->customer) {
      $salesorder->setRelation('customer', Customer::find(trim($salesorder->fcustno)));
    }

    $savedItems = $salesorder->details->map(function ($d) {
      return [
        'uid'        => $d->fpodid,
        'fitemcode'  => (string)($d->fitemcode ?? ''),  // dari alias msprd.fprdid
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
        $p->fitemid => [
          'name'  => $p->fprdname,
          'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
          'stock' => $p->fminstock ?? 0,
        ],
      ];
    })->toArray();

    // Pass the data to the view
    return view('salesorder.edit', [
      'customers' => $customers,
      'salesmans' => $salesmans,
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
      'filterSalesmanId' => $request->query('filter_salesman_id'),
      'action' => 'delete'
    ]);
  }

  public function destroy($ftrsomtid)
  {
    try {
      $salesorder = SalesOrderHeader::findOrFail($ftrsomtid);
      $salesorder->delete();

      return redirect()->route('salesorder.index')->with('success', 'Data Sales Order ' . $salesorder->fsono . ' berhasil dihapus.');
    } catch (\Exception $e) {
      // Jika terjadi kesalahan saat menghapus, kembali ke halaman delete dengan pesan error
      return redirect()->route('salesorder.delete', $ftrsomtid)->with('error', 'Gakey: gal menghapus data: ' . $e->getMessage());
    }
  }
}
