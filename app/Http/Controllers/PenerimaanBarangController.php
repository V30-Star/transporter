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
use Exception;
use Carbon\Carbon;

class PenerimaanBarangController extends Controller
{
  public function index(Request $request)
  {
    $canCreate = in_array('createPenerimaanBarang', explode(',', session('user_restricted_permissions', '')));
    $canEdit   = in_array('updatePenerimaanBarang', explode(',', session('user_restricted_permissions', '')));
    $canDelete = in_array('deletePenerimaanBarang', explode(',', session('user_restricted_permissions', '')));
    $showActionsColumn = $canEdit || $canDelete;

    $year  = $request->query('year');
    $month = $request->query('month');

    $availableYears = PenerimaanPembelianHeader::selectRaw('DISTINCT EXTRACT(YEAR FROM fdatetime) as year')
      ->where('fstockmtcode', 'TER')
      ->whereNotNull('fdatetime')
      ->orderByRaw('EXTRACT(YEAR FROM fdatetime) DESC')
      ->pluck('year');

    if ($request->ajax()) {
      $query = PenerimaanPembelianHeader::where('fstockmtcode', 'TER');

      $totalRecords = PenerimaanPembelianHeader::where('fstockmtcode', 'TER')->count();

      // For searching, handle it on the header level (like fstockmtno) or if you want supplier name, 
      // you could do a subquery but for now keep it simple to fix the 500 error.
      if ($search = $request->input('search.value')) {
        $query->where('fstockmtno', 'like', "%{$search}%");
      }
      if ($year)  $query->whereRaw('EXTRACT(YEAR FROM fdatetime) = ?',  [$year]);
      if ($month) $query->whereRaw('EXTRACT(MONTH FROM fdatetime) = ?', [$month]);

      $filteredRecords = (clone $query)->count();

      $orderColIdx    = $request->input('order.0.column', 0);
      $orderDir       = $request->input('order.0.dir', 'desc');

      // Match exactly with the blade columns array
      $sortableColumns = [
        'fstockmtno',
        'fstockmtdate',
        'fstockmtdate', // dummy for fwhname
        'fstockmtdate', // dummy for fsuppliername
        'fket',
        'fstockmtdate', // dummy for frefpo
        'famountmt'
      ];

      if (isset($sortableColumns[$orderColIdx])) {
        $query->orderBy($sortableColumns[$orderColIdx], $orderDir);
      } else {
        $query->orderBy('fstockmtid', 'desc');
      }

      $start   = $request->input('start', 0);
      $length  = $request->input('length', 10);
      $records = $query->skip($start)->take($length)->get(['fstockmtid', 'fstockmtno', 'fstockmtdate', 'ffrom', 'fsupplier', 'fket', 'famountmt']);

      // Collect related data efficiently without breaking Postgres Types
      $warehouseCodes = $records->pluck('ffrom')->filter()->unique();
      $warehouses = DB::table('mswh')->whereIn('fwhcode', $warehouseCodes)->pluck('fwhname', 'fwhcode');

      $supplierCodes = $records->pluck('fsupplier')->filter()->unique();
      $suppliers = DB::table('mssupplier')->whereIn('fsuppliercode', $supplierCodes)->pluck('fsuppliername', 'fsuppliercode');

      $stockMtNos = $records->pluck('fstockmtno');
      $trstockdts = DB::table('trstockdt')
        ->whereIn('fstockmtno', $stockMtNos)
        ->select('fstockmtno', DB::raw("MAX(frefdtno) as frefpo"))
        ->groupBy('fstockmtno')
        ->get()
        ->pluck('frefpo', 'fstockmtno');

      $data = $records->map(fn($row) => [
        'fstockmtid'   => $row->fstockmtid,
        'fstockmtno'   => $row->fstockmtno,
        'fstockmtdate' => Carbon::parse($row->fstockmtdate)->format('d/m/Y'),
        'fwhname'      => $warehouses[$row->ffrom] ?? '-',
        'fsuppliername' => $suppliers[$row->fsupplier] ?? '-',
        'fket'         => $row->fket ?? '-',
        'frefpo'       => $trstockdts[$row->fstockmtno] ?? '-',
        'famountmt'    => 'Rp ' . number_format((float)$row->famountmt, 0, ',', '.'),
      ]);

      return response()->json([
        'draw'            => intval($request->input('draw')),
        'recordsTotal'    => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data'            => $data,
      ]);
    }

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

  public function pickable(Request $request)
  {
    $query = DB::table('tr_poh')
      ->leftJoin('mssupplier', 'tr_poh.fsupplier', '=', 'mssupplier.fsuppliercode')
      ->select('tr_poh.*', 'mssupplier.fsuppliername', 'mssupplier.fsuppliercode');

    $recordsTotal = DB::table('tr_poh')->count();

    if ($request->filled('search') && $request->search != '') {
      $search = $request->search;
      $query->where(function ($q) use ($search) {
        $q->where('tr_poh.fpono', 'ilike', "%{$search}%")
          ->orWhere('mssupplier.fsuppliername', 'ilike', "%{$search}%")
          ->orWhere('mssupplier.fsuppliercode', 'ilike', "%{$search}%");
      });
    }

    $recordsFiltered = $query->count();

    $orderColumn  = $request->input('order_column', 'fpodate');
    $orderDir     = $request->input('order_dir', 'desc');
    $allowedCols  = ['fpono', 'fsupplier', 'fpodate'];

    if (in_array($orderColumn, $allowedCols)) {
      if (in_array($orderColumn, ['fpono', 'fpodate'])) {
        $query->orderBy('tr_poh.' . $orderColumn, $orderDir);
      } else {
        $query->orderBy('mssupplier.fsuppliername', $orderDir);
      }
    } else {
      $query->orderBy('tr_poh.fpodate', 'desc');
    }

    $start  = (int) $request->input('start', 0);
    $length = (int) $request->input('length', 10);
    $data   = $query->skip($start)->take($length)->get();

    return response()->json([
      'draw'            => (int) $request->input('draw', 1),
      'recordsTotal'    => (int) $recordsTotal,
      'recordsFiltered' => (int) $recordsFiltered,
      'data'            => $data,
    ]);
  }

  public function items($id)
  {
    $header = DB::table('tr_poh')->where('fpohid', $id)->first();

    if (!$header) {
      return response()->json(['message' => 'PO tidak ditemukan'], 404);
    }

    // Items PO: fqtyremain di tr_pod = satuan kecil; unit & rasio dari msprd untuk UI
    $items = DB::table('tr_pod')
      ->where('tr_pod.fpono', $header->fpono)
      ->leftJoin('msprd as m', 'm.fprdid', '=', 'tr_pod.fprdid')
      ->select([
        'tr_pod.fpodid as frefdtid',
        'm.fprdid as fprdcodeid',
        'm.fprdcode as fitemcode',
        'm.fprdname as fitemname',
        'tr_pod.fqty',
        'tr_pod.fqtyremain',
        'tr_pod.fsatuan as fsatuan',
        'tr_pod.fpohid',
        'tr_pod.fprice as fprice',
        'tr_pod.fprice_rp as fprice_rp',
        'tr_pod.famount as ftotal',
        'tr_pod.fdesc as fdesc',
        'tr_pod.frefdtno',
        'm.fsatuankecil',
        'm.fsatuanbesar',
        'm.fsatuanbesar2',
        'm.fqtykecil',
        'm.fqtykecil2',
        DB::raw('0::numeric as fterima'),
      ])
      ->orderBy('tr_pod.fnou')
      ->get()
      ->map(function ($item) use ($header) {
        $item->frefdtno = (string) $header->fpono;
        $remainKecil = (float) ($item->fqtyremain ?? 0);
        $item->fqtyremain = $remainKecil;
        $item->maxqty = $remainKecil;

        return $item;
      });

    return response()->json([
      'header' => [
        'fpohid'    => $header->fpohid,
        'fpono'     => $header->fpono,
        'fsupplier' => trim($header->fsupplier ?? ''),
        'fpodate'   => $header->fpodate ? date('Y-m-d H:i:s', strtotime($header->fpodate)) : null,
      ],
      'items' => $items,
    ]);
  }

  /**
   * Qty baris penerimaan ke satuan kecil (sama seperti PO).
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
   * @param  array<int, array<string, mixed>>  $rows  baris sementara dengan frefdtid (fpodid) & fqtykecil
   * @return array<int, float>
   */
  private function aggregatePodReceiptByPod(array $rows): array
  {
    $agg = [];
    foreach ($rows as $r) {
      $fid = (int) ($r['frefdtid'] ?? 0);
      if ($fid <= 0) {
        continue;
      }
      $agg[$fid] = ($agg[$fid] ?? 0) + (float) ($r['fqtykecil'] ?? 0);
    }

    return $agg;
  }

  private function validateAndDeductTrPodRemain(array $aggregateByPod): void
  {
    foreach ($aggregateByPod as $fpodid => $need) {
      $fpodid = (int) $fpodid;
      if ($fpodid <= 0 || $need <= 0) {
        continue;
      }
      $row = DB::table('tr_pod')->where('fpodid', $fpodid)->lockForUpdate()->first();
      if (!$row) {
        throw new \RuntimeException('Baris detail PO tidak ditemukan.');
      }
      $remain = (float) ($row->fqtyremain ?? 0);
      if ($need > $remain + 1e-4) {
        throw new \RuntimeException('Qty penerimaan melebihi sisa PO (tr_pod.fqtyremain). Tidak dapat menyimpan.');
      }
      $newRemain = max(0, round($remain - $need, 6));
      DB::table('tr_pod')->where('fpodid', $fpodid)->update(['fqtyremain' => $newRemain]);
    }
  }

  private function restoreTrPodRemainFromReceiptLines($lines): void
  {
    foreach ($lines as $line) {
      $fid = (int) ($line->frefdtid ?? 0);
      if ($fid <= 0) {
        continue;
      }
      $q = (float) ($line->fqtykecil ?? 0);
      if ($q <= 0) {
        continue;
      }
      DB::table('tr_pod')->where('fpodid', $fid)->update([
        'fqtyremain' => DB::raw('COALESCE(fqtyremain,0) + ' . $q),
      ]);
    }
  }

  private function generateStockMtCode(?Carbon $onDate = null, $branch = null, string $prefix = 'TER'): string
  {
    $date = $onDate ?: now();

    $branch = $branch
      ?? Auth::guard('sysuser')->user()?->fcabang
      ?? Auth::user()?->fcabang
      ?? null;

    $kodeCabang = null;
    if ($branch !== null) {
      $needle = trim((string) $branch);
      if (is_numeric($needle)) {
        $kodeCabang = DB::table('mscabang')->where('fcabangid', (int) $needle)->value('fcabangkode');
      } else {
        $kodeCabang = DB::table('mscabang')->whereRaw('LOWER(fcabangkode)=LOWER(?)', [$needle])->value('fcabangkode')
          ?: DB::table('mscabang')->whereRaw('LOWER(fcabangname)=LOWER(?)', [$needle])->value('fcabangkode');
      }
    }
    if (!$kodeCabang) $kodeCabang = 'NA';

    $lockKey = crc32("STOCKMT|{$prefix}|{$kodeCabang}|" . $date->format('Y-m'));
    DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

    $noPrefix = sprintf('%s.%s.%s.%s.', $prefix, $kodeCabang, $date->format('y'), $date->format('m'));

    $last = DB::table('trstockmt')
      ->where('fstockmtno', 'like', $noPrefix . '%')
      ->selectRaw("MAX(CAST(split_part(fstockmtno, '.', 5) AS int)) AS lastno")
      ->value('lastno');

    $next = (int) $last + 1;
    return $noPrefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
  }

  public function print(string $fstockmtno)
  {
    $supplierSub = Supplier::select('fsuppliercode', 'fsuppliername');

    $hdr = PenerimaanPembelianHeader::query()
      ->leftJoinSub($supplierSub, 's', fn($j) => $j->on('s.fsuppliercode', '=', 'trstockmt.fsupplier'))
      ->leftJoin('mscabang as c', 'c.fcabangkode', '=', 'trstockmt.fbranchcode')
      ->leftJoin('mswh as w', 'w.fwhcode', '=', 'trstockmt.ffrom')
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
      ->leftJoin('msprd as p', 'p.fprdcode', '=', 'trstockdt.fprdcode') // join by varchar code
      ->where('trstockdt.fstockmtno', $fstockmtno)
      ->orderBy('trstockdt.fprdcode')
      ->get([
        'trstockdt.*',
        'p.fprdname as product_name',
        'p.fprdcode as product_code',
        'p.fminstock as stock',
        'trstockdt.fqtyremain',
      ]);

    $fmt = fn($d) => $d ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d F Y') : '-';

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
    $suppliers = Supplier::orderBy('fsuppliername', 'asc')->get(['fsuppliercode', 'fsuppliername']);

    $warehouses = DB::table('mswh')
      ->select('fwhid', 'fwhcode', 'fwhname', 'fbranchcode', 'fnonactive')
      ->where('fnonactive', '0')
      ->orderBy('fwhcode')
      ->get();

    $raw    = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;
    $branch = DB::table('mscabang')
      ->when(is_numeric($raw), fn($q) => $q->where('fcabangid', (int) $raw))
      ->when(!is_numeric($raw), fn($q) => $q->where('fcabangkode', $raw)->orWhere('fcabangname', $raw))
      ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

    $canApproval = in_array('approvalpr', explode(',', session('user_restricted_permissions', '')));
    $fcabang     = $branch->fcabangname ?? (string) $raw;
    $fbranchcode = $branch->fcabangkode ?? (string) $raw;

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

    $productMap = $products->mapWithKeys(fn($p) => [
      trim($p->fprdcode) => [
        'id'    => $p->fprdid,
        'name'  => $p->fprdname,
        'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
        'stock' => $p->fminstock ?? 0,
      ],
    ])->toArray();

    return view('penerimaanbarang.create', [
      'warehouses'       => $warehouses,
      'perms'            => ['can_approval' => $canApproval],
      'suppliers'        => $suppliers,
      'fcabang'          => $fcabang,
      'fbranchcode'      => $fbranchcode,
      'products'         => $products,
      'productMap'       => $productMap,
      'filterSupplierId' => $request->query('filter_supplier_id'),
    ]);
  }

  public function store(Request $request)
  {
    // 1) VALIDASI
    $request->validate([
      'fstockmtno'     => ['nullable', 'string', 'max:100'],
      'fstockmtdate'   => ['required', 'date'],
      'fsupplier'      => ['required', 'string', 'max:30'],
      'ffrom'          => ['nullable', 'string', 'max:30'],
      'fket'           => ['nullable', 'string', 'max:500'],
      'fbranchcode'    => ['nullable', 'string', 'max:20'],
      'fitemcode'      => ['required', 'array', 'min:1'],
      'fitemcode.*'    => ['required', 'string', 'max:50'],
      'fqty'           => ['required', 'array'],
      'fqty.*'         => ['numeric', 'min:0.001'],
      'fprice'         => ['required', 'array'],
      'fprice.*'       => ['numeric', 'min:0'],
    ]);

    // 2) HEADER FIELDS
    $fstockmtno   = trim((string) $request->input('fstockmtno', ''));
    $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
    $fsupplier    = trim((string) $request->input('fsupplier'));
    $ffrom        = trim((string) $request->input('ffrom'));
    $fket         = trim((string) $request->input('fket', ''));
    $fbranchcode  = $request->input('fbranchcode');
    $fcurrency    = $request->input('fcurrency', 'IDR');
    $frate        = max(1, (float) $request->input('frate', 1));
    $ppnAmount    = (float) $request->input('famountpopajak', 0);
    $userid       = auth('sysuser')->user()->fsysuserid ?? 'admin';
    $now          = now();

    // 3) DETAIL ARRAYS
    $codes      = $request->input('fitemcode', []);
    $prdIds     = $request->input('fprdcodeid', []);
    $satuans    = $request->input('fsatuan', []);
    $fponos     = $request->input('fpono', []);
    $refdtids   = $request->input('frefdtid', []);
    $qtys       = $request->input('fqty', []);
    $prices     = $request->input('fprice', []);
    $descs      = $request->input('fdesc', []);

    // 4) BUILD ROWS
    $uniqueCodes = array_values(array_unique(array_filter(array_map(fn($c) => trim((string)$c), $codes))));
    $prodMeta    = DB::table('msprd')->whereIn('fprdcode', $uniqueCodes)->get()->keyBy('fprdcode');

    $rowsDt   = [];
    $subtotal = 0.0;
    $errors   = [];

    for ($i = 0, $cnt = count($codes); $i < $cnt; $i++) {
      $code   = trim((string) ($codes[$i]   ?? ''));
      $qty    = (float) ($qtys[$i]   ?? 0);

      if ($code === '' || $qty <= 0) continue;

      $meta = $prodMeta[$code] ?? null;
      if (!$meta) continue;

      $sat = trim((string) ($satuans[$i] ?? ''));
      if ($sat === '') {
        $sat = mb_substr($meta->fsatuankecil ?? $meta->fsatuanbesar ?? '', 0, 5);
      }

      $qtyKecil = $this->qtyPoToKecil($meta, $sat, $qty);

      $price  = (float) ($prices[$i]  ?? 0);
      $amount = $qty * $price;
      $subtotal += $amount;

      $rowsDt[] = [
        'fprdcode'     => $code,
        'fprdcodeid'   => isset($prdIds[$i]) ? (int) $prdIds[$i] : (int) $meta->fprdid,
        'frefdtno'     => trim((string) ($fponos[$i] ?? '')),
        'frefdtid'     => isset($refdtids[$i]) ? (int) $refdtids[$i] : null,
        'fqty'         => $qty,
        'fqtykecil'    => $qtyKecil,
        'fqtyremain'   => $qtyKecil,
        'fprice'       => $price,
        'fprice_rp'    => $price * $frate,
        'ftotprice'    => $amount,
        'ftotprice_rp' => $amount * $frate,
        'fusercreate'  => $userid,
        'fdatetime'    => $now,
        'fcode'        => 'R',
        'fdesc'        => trim((string) ($descs[$i] ?? '')),
        'fsatuan'      => mb_substr($sat, 0, 5),
        'fclosedt'     => 0,
      ];
    }

    if (empty($rowsDt)) {
      return back()->withInput()->withErrors(['detail' => 'Minimal satu item valid diperlukan.']);
    }

    $podAgg = $this->aggregatePodReceiptByPod($rowsDt);

    $grandTotal = $subtotal + $ppnAmount;

    // 5) TRANSACTION
    try {
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
        $podAgg
      ) {
        $this->validateAndDeductTrPodRemain($podAgg);

        // A. Resolve Cabang
        $rawBranch = trim((string)$fbranchcode);
        $kodeCabang = DB::table('mscabang')
          ->where('fcabangid', is_numeric($rawBranch) ? (int)$rawBranch : -1)
          ->orWhere('fcabangkode', $rawBranch)
          ->value('fcabangkode') ?? 'NA';

        $yy = $fstockmtdate->format('y');
        $mm = $fstockmtdate->format('m');
        $fstockmtcode = 'TER';

        // B. Penomoran Otomatis
        if (empty($fstockmtno)) {
          $prefix  = sprintf('%s.%s.%s.%s.', $fstockmtcode, $kodeCabang, $yy, $mm);
          $lockKey = crc32("STOCKMT|{$fstockmtcode}|{$kodeCabang}|" . $fstockmtdate->format('Y-m'));
          DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

          $last = DB::table('trstockmt')
            ->where('fstockmtno', 'like', $prefix . '%')
            ->selectRaw("MAX(CAST(split_part(fstockmtno, '.', 5) AS int)) AS lastno")
            ->value('lastno');

          $fstockmtno = $prefix . str_pad((string)((int)$last + 1), 4, '0', STR_PAD_LEFT);
        }

        // C. Insert Header
        $masterId = DB::table('trstockmt')->insertGetId([
          'fstockmtno'       => $fstockmtno,
          'fstockmtcode'     => $fstockmtcode,
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
          'fusercreate'      => $userid,
          'fdatetime'        => $now,
          'fbranchcode'      => $kodeCabang,
          'fprdout'          => '0',
          'fsudahtagih'      => '0',
          'fprint'           => 0,
        ], 'fstockmtid');

        // D. Insert Details
        foreach ($rowsDt as &$r) {
          $r['fstockmtid']   = $masterId;
          $r['fstockmtcode'] = $fstockmtcode;
          $r['fstockmtno']   = $fstockmtno;
        }
        DB::table('trstockdt')->insert($rowsDt);

        // UPDATE STOK - gunakan qtyKecil hasil konversi, bukan qty mentah
        foreach ($rowsDt as $row) {
          DB::table('msprd')
            ->where('fprdcode', $row['fprdcode'])
            ->update([
              'fminstock'  => DB::raw("CAST(fminstock AS NUMERIC) - " . $row['fqtyremain']),
              'fupdatedat' => now(),
            ]);
        }

        // E. Jurnal
        $fjurnaltype  = 'JTB';
        $jurnalPrefix = sprintf('%s.%s.%s.%s.', $fjurnaltype, $kodeCabang, $yy, $mm);
        $lastJ = DB::table('jurnalmt')->where('fjurnalno', 'like', $jurnalPrefix . '%')
          ->selectRaw("MAX(CAST(split_part(fjurnalno, '.', 5) AS int)) AS lastno")->value('lastno');
        $fjurnalno = $jurnalPrefix . str_pad((string)((int)$lastJ + 1), 4, '0', STR_PAD_LEFT);

        $jurnalId = DB::table('jurnalmt')->insertGetId([
          'fbranchcode' => $kodeCabang,
          'fjurnalno'   => $fjurnalno,
          'fjurnaltype' => $fjurnaltype,
          'fjurnaldate' => $fstockmtdate,
          'fjurnalnote' => "Penerimaan $fstockmtno dari $fsupplier",
          'fbalance'    => round($grandTotal, 2),
          'fbalance_rp' => round($grandTotal * $frate, 2),
          'fdatetime'   => $now,
          'fuserid'     => $userid,
        ], 'fjurnalmtid');

        $jurnalDt = [
          ['fjurnalmtid' => $jurnalId, 'fbranchcode' => $kodeCabang, 'fjurnaltype' => $fjurnaltype, 'fjurnalno' => $fjurnalno, 'flineno' => 1, 'faccount' => '11400', 'fdk' => 'D', 'fsubaccount' => $fsupplier, 'frefno' => $fstockmtno, 'frate' => $frate, 'famount' => round($subtotal, 2), 'famount_rp' => round($subtotal * $frate, 2), 'faccountnote' => 'Persediaan', 'fusercreate' => $userid, 'fdatetime' => $now],
          ['fjurnalmtid' => $jurnalId, 'fbranchcode' => $kodeCabang, 'fjurnaltype' => $fjurnaltype, 'fjurnalno' => $fjurnalno, 'flineno' => ($ppnAmount > 0 ? 3 : 2), 'faccount' => '21100', 'fdk' => 'K', 'fsubaccount' => $fsupplier, 'frefno' => $fstockmtno, 'frate' => $frate, 'famount' => round($grandTotal, 2), 'famount_rp' => round($grandTotal * $frate, 2), 'faccountnote' => 'Hutang Dagang', 'fusercreate' => $userid, 'fdatetime' => $now]
        ];

        if ($ppnAmount > 0) {
          $jurnalDt[] = ['fjurnalmtid' => $jurnalId, 'fbranchcode' => $kodeCabang, 'fjurnaltype' => $fjurnaltype, 'fjurnalno' => $fjurnalno, 'flineno' => 2, 'faccount' => '11500', 'fdk' => 'D', 'fsubaccount' => null, 'frefno' => $fstockmtno, 'frate' => $frate, 'famount' => round($ppnAmount, 2), 'famount_rp' => round($ppnAmount * $frate, 2), 'faccountnote' => 'PPN Masukan', 'fusercreate' => $userid, 'fdatetime' => $now];
        }
        DB::table('jurnaldt')->insert($jurnalDt);
      });
    } catch (\RuntimeException $e) {
      return back()->withInput()->withErrors(['detail' => $e->getMessage()]);
    } catch (Exception $e) {
      return back()->withInput()->withErrors(['detail' => 'Gagal simpan: ' . $e->getMessage()]);
    }

    return redirect()->route('penerimaanbarang.create')->with('success', "Transaksi {$fstockmtno} berhasil disimpan.");
  }

  public function edit(Request $request, $fstockmtid)
  {
    return $this->loadFormView($request, $fstockmtid, 'penerimaanbarang.edit', 'edit');
  }

  public function view(Request $request, $fstockmtid)
  {
    return $this->loadFormView($request, $fstockmtid, 'penerimaanbarang.view', 'view');
  }

  public function delete(Request $request, $fstockmtid)
  {
    return $this->loadFormView($request, $fstockmtid, 'penerimaanbarang.edit', 'delete');
  }

  /**
   * Shared loader untuk edit / view / delete.
   */
  private function loadFormView(Request $request, $fstockmtid, string $viewName, string $action)
  {
    $suppliers = Supplier::orderBy('fsuppliername', 'asc')->get(['fsuppliercode', 'fsuppliername']);

    $raw    = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;
    $branch = DB::table('mscabang')
      ->when(is_numeric($raw), fn($q) => $q->where('fcabangid', (int) $raw))
      ->when(!is_numeric($raw), fn($q) => $q->where('fcabangkode', $raw)->orWhere('fcabangname', $raw))
      ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

    $warehouses = DB::table('mswh')
      ->select('fwhid', 'fwhcode', 'fwhname', 'fbranchcode', 'fnonactive')
      ->where('fnonactive', '0')
      ->orderBy('fwhcode')
      ->get();

    $defaultCabangName = $branch->fcabangname ?? (string) $raw;
    $defaultBranchCode = $branch->fcabangkode ?? (string) $raw;

    $penerimaanbarang = PenerimaanPembelianHeader::with([
      'details' => function ($q) {
        $q->leftJoin('msprd', 'msprd.fprdcode', '=', 'trstockdt.fprdcode')
          ->leftJoin('trstockmt', 'trstockmt.fstockmtno', '=', 'trstockdt.fstockmtno')
          ->leftJoin('mswh', 'mswh.fwhcode', '=', 'trstockmt.ffrom')
          ->leftJoin('tr_pod as pod', 'pod.fpodid', '=', 'trstockdt.frefdtid')
          ->select(
            'trstockdt.*',
            'msprd.fprdname',
            'msprd.fprdcode as fitemcode_text',
            'msprd.fsatuankecil',
            'msprd.fsatuanbesar',
            'msprd.fsatuanbesar2',
            'msprd.fqtykecil',
            'msprd.fqtykecil2',
            'mswh.fwhname as fwhname',
            DB::raw('COALESCE(pod.fqtyremain, 0) + COALESCE(trstockdt.fqtykecil, 0) as fqtyremain_hint'),
          )
          ->orderBy('trstockdt.fstockdtid');
      }
    ])->findOrFail($fstockmtid);

    $selectedBranchCode = trim((string) ($penerimaanbarang->fbranchcode ?? ''));
    $selectedBranchName = $selectedBranchCode !== ''
      ? DB::table('mscabang')->where('fcabangkode', $selectedBranchCode)->value('fcabangname')
      : null;
    $usageLockMessage = $action === 'view' ? null : $this->getUsageLockMessage($penerimaanbarang);

    $savedItems = $penerimaanbarang->details->map(function ($d) {
      return [
        'uid'        => $d->fstockdtid,
        'fitemcode'  => $d->fitemcode_text ?? $d->fprdcode ?? '',
        'fitemname'  => $d->fprdname ?? '',
        'fprdcodeid' => $d->fprdcodeid ?? null,
        'fsatuan'    => $d->fsatuan ?? '',
        'fprno'      => $d->frefpr ?? '-',
        'frefdtno'   => $d->frefdtno ?? null,
        'frefdtid'   => $d->frefdtid ?? null,
        'fqty'       => (float) ($d->fqty ?? 0),
        'fterima'    => (float) ($d->fterima ?? 0),
        'fprice'     => (float) ($d->fprice ?? 0),
        'famount'     => (float) ($d->famount ?? 0),
        'fdisc'      => (float) ($d->fdiscpersen ?? 0),
        'ftotal'     => (float) ($d->ftotprice ?? 0),
        'fdesc'      => is_array($d->fdesc) ? implode(', ', $d->fdesc) : ($d->fdesc ?? ''),
        'fketdt'     => $d->fketdt ?? '',
        'fqtyremain' => $d->frefdtid ? (float) ($d->fqtyremain_hint ?? 0) : 0,
        'fsatuankecil' => $d->fsatuankecil ?? '',
        'fsatuanbesar' => $d->fsatuanbesar ?? '',
        'fsatuanbesar2' => $d->fsatuanbesar2 ?? '',
        'fqtykecil' => (float) ($d->fqtykecil ?? 0),
        'fqtykecil2' => (float) ($d->fqtykecil2 ?? 0),
        'maxqty'     => 0,
        'units'      => [],
      ];
    })->values();

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

    $productMap = $products->mapWithKeys(fn($p) => [
      $p->fprdcode => [
        'id'    => $p->fprdid,
        'name'  => $p->fprdname,
        'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
        'stock' => $p->fminstock ?? 0,
      ],
    ])->toArray();

    return view($viewName, [
      'suppliers'            => $suppliers,
      'selectedSupplierCode' => $penerimaanbarang->fsupplier,
      'fcabang'              => $selectedBranchName ?? $defaultCabangName,
      'fbranchcode'          => $selectedBranchCode ?: $defaultBranchCode,
      'warehouses'           => $warehouses,
      'products'             => $products,
      'productMap'           => $productMap,
      'penerimaanbarang'     => $penerimaanbarang,
      'savedItems'           => $savedItems,
      'ppnAmount'            => (float) ($penerimaanbarang->famountpopajak ?? 0),
      'famountponet'         => (float) ($penerimaanbarang->famountponet ?? 0),
      'famountpo'            => (float) ($penerimaanbarang->famountpo ?? 0),
      'filterSupplierId'     => $request->query('filter_supplier_id'),
      'isUsageLocked'        => !empty($usageLockMessage),
      'usageLockMessage'     => $usageLockMessage,
      'action'               => $action,
    ]);
  }

  public function update(Request $request, $fstockmtid)
  {
    $request->validate([
      'fstockmtno'     => ['nullable', 'string', 'max:100'],
      'fstockmtdate'   => ['required', 'date'],
      'fsupplier'      => ['required', 'string', 'max:30'],
      'ffrom'          => ['nullable', 'string', 'max:30'],
      'fket'           => ['nullable', 'string', 'max:500'],
      'fbranchcode'    => ['nullable', 'string', 'max:20'],
      'fitemcode'      => ['required', 'array', 'min:1'],
      'fitemcode.*'    => ['required', 'string', 'max:50'],
      'fprdcodeid'     => ['nullable', 'array'],
      'fprdcodeid.*'   => ['nullable', 'integer'],
      'frefdtno'       => ['nullable', 'array'],
      'frefdtno.*'     => ['nullable', 'string', 'max:50'],
      'frefdtid'       => ['nullable', 'array'],
      'frefdtid.*'     => ['nullable', 'integer'],
      'fsatuan'        => ['nullable', 'array'],
      'fsatuan.*'      => ['nullable', 'string', 'max:5'],
      'fqty'           => ['required', 'array'],
      'fqty.*'         => ['numeric', 'min:0.001'],
      'fprice'         => ['required', 'array'],
      'fprice.*'       => ['numeric', 'min:0'],
      'fdesc'          => ['nullable', 'array'],
      'fdesc.*'        => ['nullable', 'string', 'max:500'],
      'fcurrency'      => ['nullable', 'string', 'max:5'],
      'frate'          => ['nullable', 'numeric', 'min:0'],
      'famountpopajak' => ['nullable', 'numeric', 'min:0'],
    ]);

    $header       = PenerimaanPembelianHeader::findOrFail($fstockmtid);

    if ($message = $this->getUsageLockMessage($header)) {
      return redirect()->route('penerimaanbarang.index')->with('error', $message);
    }

    $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
    $fsupplier    = trim((string) $request->input('fsupplier'));
    $ffrom        = trim((string) $request->input('ffrom'));
    $fket         = trim((string) $request->input('fket', ''));
    $fbranchcode  = $request->input('fbranchcode');
    $fcurrency    = $request->input('fcurrency', 'IDR');
    $frate        = max(1, (float) $request->input('frate', 1));
    $ppnAmount    = (float) $request->input('famountpopajak', 0);
    $now          = now();

    $codes    = $request->input('fitemcode', []);
    $prdIds   = $request->input('fprdcodeid', []);
    $satuans  = $request->input('fsatuan', []);
    $refdtnos = $request->input('frefdtno', []);
    $refdtids = $request->input('frefdtid', []);
    $qtys     = $request->input('fqty', []);
    $prices   = $request->input('fprice', []);
    $descs    = $request->input('fdesc', []);

    $uniqueCodes = array_values(array_unique(array_filter(array_map(fn($c) => trim((string)$c), $codes))));
    $prodMeta    = DB::table('msprd')
      ->whereIn('fprdcode', $uniqueCodes)
      ->get()
      ->keyBy('fprdcode');

    $pickDefaultSat = function ($meta) {
      if (!$meta) return '';
      foreach (['fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'] as $k) {
        $v = trim((string) ($meta->$k ?? ''));
        if ($v !== '') return mb_substr($v, 0, 5);
      }
      return '';
    };

    $rowsDt   = [];
    $subtotal = 0.0;
    // NOTE:
    // Pada edit penerimaan barang berbasis PO, batas qty mengikuti sisa referensi PO (tr_pod.fqtyremain),
    // bukan berdasarkan nilai stok master msprd.fminstock.

    for ($i = 0, $cnt = count($codes); $i < $cnt; $i++) {
      $code   = trim((string) ($codes[$i]  ?? ''));
      $sat    = trim((string) ($satuans[$i] ?? ''));
      $rno    = trim((string) ($refdtnos[$i] ?? ''));
      $rid    = isset($refdtids[$i]) ? (int) $refdtids[$i] : null;
      $qty    = (float) ($qtys[$i]   ?? 0);
      $price  = (float) ($prices[$i]  ?? 0);
      $desc   = trim((string) ($descs[$i]   ?? ''));

      if ($code === '' || $qty <= 0) continue;

      $meta = $prodMeta[$code] ?? null;
      if (!$meta) continue;

      $prdCodeId = isset($prdIds[$i]) && $prdIds[$i] !== '' ? (int) $prdIds[$i] : (int) $meta->fprdid;

      if ($sat === '') $sat = $pickDefaultSat($meta);
      $sat = mb_substr($sat, 0, 5);
      if ($sat === '') continue;

      $qtyKecil = $this->qtyPoToKecil($meta, $sat, $qty);

      $amount    = $qty * $price;
      $subtotal += $amount;

      $rowsDt[] = [
        'fprdcode'     => $code,
        'fprdcodeid'   => $prdCodeId,
        'frefdtno'     => $rno ?: null,
        'frefdtid'     => $rid,
        'frefso'       => null,
        'frefsoid'     => null,
        'fqty'         => $qty,
        'fqtykecil'    => $qtyKecil,
        'fqtyremain'   => $qtyKecil,
        'fprice'       => $price,
        'fprice_rp'    => $price * $frate,
        'ftotprice'    => $amount,
        'ftotprice_rp' => $amount * $frate,
        'fuserupdate'  => Auth::user()->fname ?? 'system',
        'fdatetime'    => $now,
        'fketdt'       => '',
        'fcode'        => '0',
        'fdesc'        => $desc,
        'fsatuan'      => $sat,
        'fclosedt'     => '0',
        'fdiscpersen'  => 0,
        'fbiaya'       => 0,
      ];
    }

    if (empty($rowsDt)) {
      return back()->withInput()->withErrors(['detail' => 'Minimal satu item valid (Kode, Satuan, Qty > 0).']);
    }

    $podAgg = $this->aggregatePodReceiptByPod($rowsDt);
    $oldReceiptLines = DB::table('trstockdt')->where('fstockmtno', $header->fstockmtno)->get(['frefdtid', 'fqtykecil']);

    $grandTotal = $subtotal + $ppnAmount;

    try {
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
      $now,
      &$rowsDt,
      $subtotal,
      $ppnAmount,
      $grandTotal,
      $podAgg,
      $oldReceiptLines
    ) {
      $this->restoreTrPodRemainFromReceiptLines($oldReceiptLines);
      $this->validateAndDeductTrPodRemain($podAgg);

      $kodeCabang = $header->fbranchcode;
      if ($fbranchcode && $fbranchcode !== $header->fbranchcode) {
        $kodeCabang = $this->resolveKodeCabang($fbranchcode) ?: $kodeCabang;
      }

      $header->update([
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
        'fuserupdate'      => Auth::user()->fname ?? 'system',
        'fbranchcode'      => $kodeCabang,
      ]);

      DB::table('trstockdt')->where('fstockmtno', $header->fstockmtno)->delete();
      // UPDATE STOK - gunakan qtyKecil hasil konversi, bukan qty mentah
      foreach ($rowsDt as $row) {
        DB::table('msprd')
          ->where('fprdcode', $row['fprdcode'])
          ->update([
            'fminstock'  => DB::raw("CAST(fminstock AS NUMERIC) - " . $row['fqtyremain']),
            'fupdatedat' => now(),
          ]);
      }

      $nextNouRef = 1;
      foreach ($rowsDt as &$r) {
        $r['fstockmtid']   = $fstockmtid;
        $r['fstockmtcode'] = $header->fstockmtcode;
        $r['fstockmtno']   = $header->fstockmtno;
      }
      unset($r);

      DB::table('trstockdt')->insert($rowsDt);
    });
    } catch (\RuntimeException $e) {
      return back()->withInput()->withErrors(['detail' => $e->getMessage()]);
    }

    return redirect()->route('penerimaanbarang.index')
      ->with('success', "Transaksi {$header->fstockmtno} berhasil diperbarui.");
  }

  public function destroy($fstockmtid)
  {
    try {
      $penerimaanbarang = PenerimaanPembelianHeader::findOrFail($fstockmtid);

      if ($message = $this->getUsageLockMessage($penerimaanbarang)) {
        return redirect()->route('penerimaanbarang.index')->with('error', $message);
      }

      $oldLines = DB::table('trstockdt')->where('fstockmtno', $penerimaanbarang->fstockmtno)->get(['frefdtid', 'fqtykecil']);

      DB::transaction(function () use ($penerimaanbarang, $oldLines) {
        $this->restoreTrPodRemainFromReceiptLines($oldLines);
        $penerimaanbarang->details()->delete();
        $penerimaanbarang->delete();
      });

      return redirect()->route('penerimaanbarang.index')
        ->with('success', 'Data Penerimaan Barang ' . $penerimaanbarang->fstockmtno . ' berhasil dihapus.');
    } catch (\Exception $e) {
      return redirect()->route('penerimaanbarang.delete', $fstockmtid)
        ->with('error', 'Gagal menghapus data: ' . $e->getMessage());
    }
  }

  // ─── Helpers ─────────────────────────────────────────────────────────────

  private function resolveKodeCabang($fbranchcode): string
  {
    $kodeCabang = null;
    if ($fbranchcode !== null) {
      $needle = trim((string) $fbranchcode);
      if ($needle !== '') {
        if (is_numeric($needle)) {
          $kodeCabang = DB::table('mscabang')->where('fcabangid', (int) $needle)->value('fcabangkode');
        } else {
          $kodeCabang = DB::table('mscabang')->whereRaw('LOWER(fcabangkode)=LOWER(?)', [$needle])->value('fcabangkode')
            ?: DB::table('mscabang')->whereRaw('LOWER(fcabangname)=LOWER(?)', [$needle])->value('fcabangkode');
        }
      }
    }
    return $kodeCabang ?: 'NA';
  }

  private function getUsageLockMessage(PenerimaanPembelianHeader $header): ?string
  {
    $detailIds = DB::table('trstockdt')
      ->where('fstockmtno', $header->fstockmtno)
      ->pluck('fstockdtid')
      ->map(fn($id) => (int) $id)
      ->filter(fn($id) => $id > 0)
      ->values();

    if ($detailIds->isEmpty()) {
      return null;
    }

    $usedBy = DB::table('trstockdt')
      ->where('fstockmtcode', 'BUY')
      ->whereIn('frefdtid', $detailIds->all())
      ->select('fstockmtno')
      ->distinct()
      ->orderBy('fstockmtno')
      ->pluck('fstockmtno');

    if ($usedBy->isEmpty()) {
      return null;
    }

    return 'Penerimaan Barang ' . $header->fstockmtno . ' tidak dapat diubah atau dihapus karena sudah digunakan pada Faktur Pembelian: ' . $usedBy->implode(', ') . '.';
  }
}
