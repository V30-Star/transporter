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

      if ($search = $request->input('search.value')) {
        $query->where('fstockmtno', 'like', "%{$search}%");
      }
      if ($year)  $query->whereRaw('EXTRACT(YEAR FROM fdatetime) = ?',  [$year]);
      if ($month) $query->whereRaw('EXTRACT(MONTH FROM fdatetime) = ?', [$month]);

      $filteredRecords = (clone $query)->count();

      $orderColIdx    = $request->input('order.0.column', 0);
      $orderDir       = $request->input('order.0.dir', 'desc');
      $sortableColumns = ['fstockmtno', 'fstockmtdate'];

      if (isset($sortableColumns[$orderColIdx])) {
        $query->orderBy($sortableColumns[$orderColIdx], $orderDir);
      } else {
        $query->orderBy('fstockmtid', 'desc');
      }

      $start   = $request->input('start', 0);
      $length  = $request->input('length', 10);
      $records = $query->skip($start)->take($length)->get(['fstockmtid', 'fstockmtno', 'fstockmtdate']);

      $data = $records->map(fn($row) => [
        'fstockmtid'   => $row->fstockmtid,
        'fstockmtno'   => $row->fstockmtno,
        'fstockmtdate' => Carbon::parse($row->fstockmtdate)->format('d/m/Y'),
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
      ->leftJoin('mssupplier', 'tr_poh.fsupplier', '=', 'mssupplier.fsupplierid')
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

    // Ambil items dari tr_pod + join msprd, sertakan qty sisa (fqtyremain) sbg max qty
    $items = DB::table('tr_pod')
      ->where('tr_pod.fpohid', $header->fpohid)
      ->leftJoin('msprd as m', 'm.fprdid', '=', 'tr_pod.fprdid')
      ->select([
        'tr_pod.fpodid as frefdtid',          // ID detail PO (integer) → frefdtid
        'tr_pod.fpodid as frefdtno_raw',      // Akan kita cast ke string untuk frefdtno
        'm.fprdid as fprdcodeid',             // ID produk (integer) → fprdcodeid
        'm.fprdcode as fitemcode',            // Kode produk string → fprdcode (varchar)
        'm.fprdname as fitemname',
        'tr_pod.fqty',
        'tr_pod.fqtyremain',                  // Max qty yang boleh diterima
        'tr_pod.fsatuan as fsatuan',
        'tr_pod.fpohid',
        'tr_pod.fprice as fprice',
        'tr_pod.fprice_rp as fprice_rp',
        'tr_pod.famount as ftotal',
        'tr_pod.fdesc as fdesc',
        'tr_pod.frefdtno',
        DB::raw('0::numeric as fterima'),
      ])
      ->orderBy('tr_pod.fnou')
      ->get()
      ->map(function ($item) {
        // frefdtno sebagai varchar (string dari ID PO detail)
        $item->frefdtno     = (string) $item->frefdtid;
        $item->frefdtno_raw = null; // cleanup
        // maxqty = qty yang masih bisa diterima
        $item->maxqty = (float) ($item->fqtyremain ?? $item->fqty ?? 0);
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
    $supplierSub = Supplier::select('fsupplierid', 'fsuppliercode', 'fsuppliername');

    $hdr = PenerimaanPembelianHeader::query()
      ->leftJoinSub($supplierSub, 's', fn($j) => $j->on('s.fsupplierid', '=', 'trstockmt.fsupplier'))
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
    $suppliers = Supplier::orderBy('fsuppliername', 'asc')->get(['fsupplierid', 'fsuppliername']);

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
      'fwhid'          => ['nullable'],
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
    $ffrom        = $request->input('fwhid');
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
    $maxqtys    = $request->input('fmaxqty', []);
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
      $maxqty = isset($maxqtys[$i]) && $maxqtys[$i] !== '' ? (float) $maxqtys[$i] : null;

      if ($code === '' || $qty <= 0) continue;

      if ($maxqty !== null && $maxqty > 0 && $qty > ($maxqty + 0.001)) {
        $errors[] = "Baris " . ($i + 1) . ": Qty ({$qty}) melebihi sisa PO ({$maxqty}) untuk item {$code}.";
        continue;
      }

      $meta = $prodMeta[$code] ?? null;
      if (!$meta) continue;

      $sat = trim((string) ($satuans[$i] ?? ''));
      if ($sat === '') {
        $sat = mb_substr($meta->fsatuankecil ?? $meta->fsatuanbesar ?? '', 0, 5);
      }

      $qtyKecil = $qty;
      if ($sat === $meta->fsatuanbesar && ($meta->fqtykecil ?? 0) > 0) {
        $qtyKecil = $qty * (float) $meta->fqtykecil;
      }

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

    if (!empty($errors)) {
      return back()->withInput()->withErrors(['detail' => implode(" ", $errors)]);
    }

    if (empty($rowsDt)) {
      return back()->withInput()->withErrors(['detail' => 'Minimal satu item valid diperlukan.']);
    }

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
    } catch (Exception $e) {
      return back()->withInput()->withErrors(['error' => 'Gagal simpan: ' . $e->getMessage()]);
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
    $suppliers = Supplier::orderBy('fsuppliername', 'asc')->get(['fsupplierid', 'fsuppliername']);

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

    $fcabang     = $branch->fcabangname ?? (string) $raw;
    $fbranchcode = $branch->fcabangkode ?? (string) $raw;

    $penerimaanbarang = PenerimaanPembelianHeader::with([
      'details' => function ($q) {
        $q->leftJoin('msprd', 'msprd.fprdcode', '=', 'trstockdt.fprdcode')
          // Join dulu ke header agar kolom ffrom bisa diakses
          ->leftJoin('trstockmt', 'trstockmt.fstockmtid', '=', 'trstockdt.fstockmtid')
          ->leftJoin('mswh', 'mswh.fwhid', '=', 'trstockmt.ffrom')
          ->select(
            'trstockdt.*',
            'msprd.fprdname',
            'msprd.fprdcode as fitemcode_text',
            'mswh.fwhname as fwhname'
          )
          ->orderBy('trstockdt.fstockdtid');
      }
    ])->findOrFail($fstockmtid);

    $savedItems = $penerimaanbarang->details->map(function ($d) {
      return [
        'uid'        => $d->fstockdtid,
        'fitemcode'  => $d->fitemcode_text ?? $d->fprdcode ?? '',
        'fitemname'  => $d->fprdname ?? '',
        'fprdcodeid' => $d->fprdcodeid ?? null,   // integer id produk
        'fsatuan'    => $d->fsatuan ?? '',
        'fprno'      => $d->frefpr ?? '-',
        'frefdtno'   => $d->frefdtno ?? null,     // varchar ref PO
        'frefdtid'   => $d->frefdtid ?? null,     // integer ref PO detail
        'fqty'       => (float) ($d->fqty ?? 0),
        'fterima'    => (float) ($d->fterima ?? 0),
        'fprice'     => (float) ($d->fprice ?? 0),
        'famount'     => (float) ($d->famount ?? 0),
        'fdisc'      => (float) ($d->fdiscpersen ?? 0),
        'ftotal'     => (float) ($d->ftotprice ?? 0),
        'fdesc'      => is_array($d->fdesc) ? implode(', ', $d->fdesc) : ($d->fdesc ?? ''),
        'fketdt'     => $d->fketdt ?? '',
        // maxqty: jika dari PO ambil fqtyremain, else 0 (tidak dibatasi → set besar)
        'maxqty'     => $d->frefdtid ? (float) ($d->fqtyremain ?? 0) : 0,
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
      'fcabang'              => $fcabang,
      'fbranchcode'          => $fbranchcode,
      'warehouses'           => $warehouses,
      'products'             => $products,
      'productMap'           => $productMap,
      'penerimaanbarang'     => $penerimaanbarang,
      'savedItems'           => $savedItems,
      'ppnAmount'            => (float) ($penerimaanbarang->famountpopajak ?? 0),
      'famountponet'         => (float) ($penerimaanbarang->famountponet ?? 0),
      'famountpo'            => (float) ($penerimaanbarang->famountpo ?? 0),
      'filterSupplierId'     => $request->query('filter_supplier_id'),
      'action'               => $action,
    ]);
  }

  public function update(Request $request, $fstockmtid)
  {
    $request->validate([
      'fstockmtno'     => ['nullable', 'string', 'max:100'],
      'fstockmtdate'   => ['required', 'date'],
      'fsupplier'      => ['required', 'string', 'max:30'],
      'ffrom'          => ['nullable'],
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
      'fmaxqty'        => ['nullable', 'array'],
      'fmaxqty.*'      => ['nullable', 'numeric', 'min:0'],
      'fprice'         => ['required', 'array'],
      'fprice.*'       => ['numeric', 'min:0'],
      'fdesc'          => ['nullable', 'array'],
      'fdesc.*'        => ['nullable', 'string', 'max:500'],
      'fcurrency'      => ['nullable', 'string', 'max:5'],
      'frate'          => ['nullable', 'numeric', 'min:0'],
      'famountpopajak' => ['nullable', 'numeric', 'min:0'],
    ]);

    $header       = PenerimaanPembelianHeader::findOrFail($fstockmtid);
    $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
    $fsupplier    = trim((string) $request->input('fsupplier'));
    $ffrom        = $request->input('ffrom');
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
    $maxqtys  = $request->input('fmaxqty', []);
    $prices   = $request->input('fprice', []);
    $descs    = $request->input('fdesc', []);

    $uniqueCodes = array_values(array_unique(array_filter(array_map(fn($c) => trim((string)$c), $codes))));
    $prodMeta    = DB::table('msprd')
      ->whereIn('fprdcode', $uniqueCodes)
      ->get(['fprdid', 'fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'])
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
    $errors   = [];

    for ($i = 0, $cnt = count($codes); $i < $cnt; $i++) {
      $code   = trim((string) ($codes[$i]  ?? ''));
      $sat    = trim((string) ($satuans[$i] ?? ''));
      $rno    = trim((string) ($refdtnos[$i] ?? ''));
      $rid    = isset($refdtids[$i]) ? (int) $refdtids[$i] : null;
      $rnour  = $nourefs[$i] ?? null;
      $qty    = (float) ($qtys[$i]   ?? 0);
      $maxqty = isset($maxqtys[$i]) && $maxqtys[$i] !== '' ? (float) $maxqtys[$i] : null;
      $price  = (float) ($prices[$i]  ?? 0);
      $desc   = trim((string) ($descs[$i]   ?? ''));

      if ($code === '' || $qty <= 0) continue;

      if ($maxqty !== null && $maxqty > 0 && $qty > $maxqty) {
        $errors[] = "Baris " . ($i + 1) . ": Qty ({$qty}) melebihi sisa qty PO ({$maxqty}) untuk item {$code}.";
        continue;
      }

      $meta = $prodMeta[$code] ?? null;
      if (!$meta) continue;

      $prdCodeId = isset($prdIds[$i]) && $prdIds[$i] !== '' ? (int) $prdIds[$i] : (int) $meta->fprdid;

      $produk = DB::table('msprd')
        ->where('fprdcode', $code)
        ->select('fprdid', 'fsatuanbesar', 'fqtykecil as rasio_konversi')
        ->first();

      $qtyKecil = $qty;
      if ($produk && $sat === $produk->fsatuanbesar && $produk->rasio_konversi > 0) {
        $qtyKecil = $qty * (float) $produk->rasio_konversi;
      }

      if ($sat === '') $sat = $pickDefaultSat($meta);
      $sat = mb_substr($sat, 0, 5);
      if ($sat === '') continue;

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

    if (!empty($errors)) {
      return back()->withInput()->withErrors(['detail' => implode("\n", $errors)]);
    }

    if (empty($rowsDt)) {
      return back()->withInput()->withErrors(['detail' => 'Minimal satu item valid (Kode, Satuan, Qty > 0).']);
    }

    $grandTotal = $subtotal + $ppnAmount;

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
      $grandTotal
    ) {
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

      DB::table('trstockdt')->where('fstockmtid', $fstockmtid)->delete();

      $nextNouRef = 1;
      foreach ($rowsDt as &$r) {
        $r['fstockmtid']   = $fstockmtid;
        $r['fstockmtcode'] = $header->fstockmtcode;
        $r['fstockmtno']   = $header->fstockmtno;
      }
      unset($r);

      DB::table('trstockdt')->insert($rowsDt);
    });

    return redirect()->route('penerimaanbarang.index')
      ->with('success', "Transaksi {$header->fstockmtno} berhasil diperbarui.");
  }

  public function destroy($fstockmtid)
  {
    try {
      $penerimaanbarang = PenerimaanPembelianHeader::findOrFail($fstockmtid);
      $penerimaanbarang->details()->delete();
      $penerimaanbarang->delete();

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
}
