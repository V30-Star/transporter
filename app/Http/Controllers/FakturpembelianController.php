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
use Carbon\Carbon; // sekalian biar aman untuk tanggal

class FakturpembelianController extends Controller
{
  public function index(Request $request)
  {
    // Sorting
    $allowedSorts = ['fstockmtid', 'fstockmtno', 'fstockmtcode', 'fstockmtdate'];
    $sortBy  = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'fstockmtid';
    $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

    $query = PenerimaanPembelianHeader::query();

    $penerimaanbarang = PenerimaanPembelianHeader::orderBy($sortBy, $sortDir)->get(['fstockmtid', 'fstockmtno', 'fstockmtcode', 'fstockmtdate']);

    $canCreate = in_array('createTr_prh', explode(',', session('user_restricted_permissions', '')));
    $canEdit   = in_array('updateTr_prh', explode(',', session('user_restricted_permissions', '')));
    $canDelete = in_array('deleteTr_prh', explode(',', session('user_restricted_permissions', '')));

    return view('penerimaanbarang.index', compact('penerimaanbarang', 'canCreate', 'canEdit', 'canDelete'));
  }

  public function pickable(Request $request)
  {
    $search   = trim($request->get('search', ''));
    $perPage  = (int)($request->get('per_page', 10));
    $perPage  = $perPage > 0 ? $perPage : 10;

    $q = \App\Models\Tr_poh::query()
      ->select([
        'fpohdid as fprid',     // FE expects fprid
        'fpono as fprno',       // FE expects fprno
        'fsupplier',
        'fpodate as fprdate',   // FE expects fprdate
      ]);

    if ($search !== '') {
      // cari di fpono / fsupplier / tanggal (yyyy-mm-dd)
      $q->where(function ($w) use ($search) {
        $w->where('fpono', 'ILIKE', "%{$search}%")
          ->orWhere('fsupplier', 'ILIKE', "%{$search}%");

        // coba parse tanggal
        $date = null;
        try {
          $date = \Carbon\Carbon::parse($search)->startOfDay();
        } catch (\Throwable $e) {
        }
        if ($date) {
          $w->orWhereBetween('fpodate', [
            $date->copy()->startOfDay(),
            $date->copy()->endOfDay(),
          ]);
        }
      });
    }

    $q->orderByDesc('fpodate')->orderBy('fpono');

    $page = (int)$request->get('page', 1);
    $data = $q->paginate($perPage, ['*'], 'page', $page);

    // Kembalikan struktur yang sudah diantisipasi FE-mu (data, current_page, last_page, total)
    return response()->json([
      'data'         => $data->items(),
      'current_page' => $data->currentPage(),
      'last_page'    => $data->lastPage(),
      'total'        => $data->total(),
    ]);
  }

  public function items($id)
  {
    // Langkah ini sudah benar: mendapatkan header berdasarkan Primary Key (ID)
    $header = Tr_poh::where('fpohdid', $id)->firstOrFail();

    // Mengambil detail dari tr_pod
    $items = DB::table('tr_pod')
      // =================================================================
      // PERBAIKAN: Gunakan ID dari header (fpohdid) untuk mencocokkan.
      // Kolom tr_pod.fpono (integer) dicocokkan dengan $header->fpohdid (integer).
      // =================================================================
      ->where('tr_pod.fpono', $header->fpohdid) // <-- DIUBAH DARI $header->fpono

      // PERBAIKAN JOIN: tr_pod.fprdcode (sekarang integer) di-join ke msprd.fprdid (integer)
      ->leftJoin('msprd as m', 'm.fprdid', '=', 'tr_pod.fprdcode')
      ->select([
        DB::raw("COALESCE(NULLIF(tr_pod.frefdtno, ''), tr_pod.fpodid::text) as frefdtno"),
        'tr_pod.fnouref as fnouref',
        'm.fprdcode as fitemcode', // <-- Ambil kode string dari master produk
        'm.fprdname as fitemname', // <-- Mengambil fprdname dari tabel msprd
        'tr_pod.fqty',
        'tr_pod.fsatuan as fsatuan',
        'tr_pod.fpono', // Ini adalah kolom ID (integer) dari tr_pod
        'tr_pod.fprice as fharga',
        DB::raw("COALESCE(NULLIF(regexp_replace(COALESCE(tr_pod.fdisc, ''), '[^0-9\\.]', '', 'g'), '')::numeric, 0) as fdiskon"),
      ])
      ->orderBy('m.fprdcode') // Urutkan berdasarkan kode produk string
      ->get();

    // Mengembalikan data dalam format JSON
    return response()->json([
      'header' => [
        'fprid'     => $header->fpohdid,
        'fprno'     => $header->fpono,
        'fsupplier' => trim($header->fsupplier ?? ''),
        'fprdate'   => optional($header->fpodate)->format('Y-m-d H-i-s'),
      ],
      'items' => $items,
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
      ->leftJoin('msprd as p', 'p.fprdid', '=', 'trstockdt.fprdcode')
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

    return view('penerimaanbarang.print', [
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
      ->where('fnonactive', '0')              // hanya yang aktif
      ->orderBy('fwhcode')
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

    return view('penerimaanbarang.create', [
      'newtr_prh_code' => $newtr_prh_code,
      'warehouses' => $warehouses,
      'perms' => ['can_approval' => $canApproval],
      'supplier' => $supplier,
      'fcabang' => $fcabang,
      'fbranchcode' => $fbranchcode,
      'products' => $products,
    ]);
  }

  public function store(Request $request)
  {
    // =========================
    // 1) VALIDASI INPUT
    // =========================
    $request->validate([
      'fstockmtno'     => ['nullable', 'string', 'max:100'], // boleh isi manual jika auto dimatikan
      'fstockmtdate'   => ['required', 'date'],
      'fsupplier'      => ['required', 'string', 'max:30'],
      'ffrom'          => ['nullable', 'string', 'max:10'], // gudang
      'fket'           => ['nullable', 'string', 'max:50'],
      'fbranchcode'    => ['nullable', 'string', 'max:20'],

      // Detail minimal: Kode, Qty>0, Harga >=0. Satuan opsional (akan diisi default dari master bila kosong)
      'fitemcode'      => ['required', 'array', 'min:1'],
      'fitemcode.*'    => ['required', 'string', 'max:50'],

      'fsatuan'        => ['nullable', 'array'],
      'fsatuan.*'      => ['nullable', 'string', 'max:5'], // Validasi max:5

      'frefdtno'       => ['nullable', 'array'],
      'frefdtno.*'     => ['nullable', 'string', 'max:20'],

      'fnouref'        => ['nullable', 'array'],
      'fnouref.*'      => ['nullable', 'integer'],

      'fqty'           => ['required', 'array'],
      'fqty.*'         => ['numeric', 'min:0'],

      'fprice'         => ['required', 'array'],
      'fprice.*'       => ['numeric', 'min:0'],

      'fdesc'          => ['nullable', 'array'],
      'fdesc.*'        => ['nullable', 'string', 'max:500'],

      // Kurs & pajak (opsional)
      'fcurrency'      => ['nullable', 'string', 'max:5'],
      'frate'          => ['nullable', 'numeric', 'min:0'],
      'famountpopajak' => ['nullable', 'numeric', 'min:0'], // PPN nominal (jika dikirim dari form)
    ], [
      'fstockmtdate.required' => 'Tanggal transaksi wajib diisi.',
      'fsupplier.required'    => 'Supplier wajib diisi.',
      'fitemcode.required'    => 'Minimal 1 item.',
      'fsatuan.*.max'         => 'Satuan di salah satu baris tidak boleh lebih dari 5 karakter.' // Pesan error kustom
    ]);

    // =========================
    // 2) HEADER FIELDS
    // =========================
    $fstockmtno   = trim((string)$request->input('fstockmtno')); // boleh null → akan di-generate
    $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
    $fsupplier    = trim((string)$request->input('fsupplier'));
    $ffrom        = $request->input('fwhid');
    $fket         = trim((string)$request->input('fket', ''));
    $fbranchcode  = $request->input('fbranchcode');            // bisa id / kode / nama cabang

    $fcurrency = $request->input('fcurrency', 'IDR');
    $frate     = (float)$request->input('frate', 1);        // default 1 (IDR)
    if ($frate <= 0) $frate = 1;

    // Jika form mengirim pajak/PPN, terima.
    $ppnAmount = (float)$request->input('famountpopajak', 0);

    $userid = auth('sysuser')->user()->fsysuserid ?? 'admin';
    $now    = now();

    // =========================
    // 3) DETAIL ARRAYS
    // =========================
    $codes   = $request->input('fitemcode', []);
    $satuans = $request->input('fsatuan', []);
    $refdtno = $request->input('frefdtno', []);
    $nourefs = $request->input('fnouref', []);
    $qtys    = $request->input('fqty', []);
    $prices  = $request->input('fprice', []);
    $descs   = $request->input('fdesc', []);

    // Ambil referensi master produk untuk fallback satuan
    $uniqueCodes = array_values(array_unique(array_filter(array_map(fn($c) => trim((string)$c), $codes))));
    $prodMeta = DB::table('msprd')
      ->whereIn('fprdcode', $uniqueCodes)
      ->get(['fprdid', 'fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'])
      ->keyBy('fprdcode');

    $pickDefaultSat = function (?object $meta) use ($prodMeta): string {
      if (!$meta) return ''; // Jika $meta null (produk tidak ketemu)
      foreach (['fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'] as $k) {
        $v = trim((string)($meta->$k ?? ''));
        if ($v !== '') return mb_substr($v, 0, 5); // patuhi varchar(5)
      }
      return '';
    };

    // =========================
    // 4) RAKIT DETAIL + HITUNG SUBTOTAL
    // =========================
    $rowsDt   = [];
    $subtotal = 0.0; // famount (sebelum pajak)
    $rowCount = count($codes); // Asumsi fitemcode adalah array utama

    for ($i = 0; $i < $rowCount; $i++) {
      $code  = trim((string)($codes[$i]   ?? ''));
      $sat   = trim((string)($satuans[$i] ?? ''));
      $rref  = trim((string)($refdtno[$i] ?? ''));
      $rnour = $nourefs[$i] ?? null;
      $qty   = (float)($qtys[$i]   ?? 0);
      $price = (float)($prices[$i] ?? 0);
      $desc  = (string)($descs[$i]  ?? '');

      if ($code === '' || $qty <= 0) {
        continue; // skip baris tidak valid
      }

      $meta = $prodMeta[$code] ?? null;

      if (!$meta) {
        continue;
      }

      $prdId = $meta->fprdid;

      if ($sat === '') {
        $sat = $pickDefaultSat($code);
      }
      $sat = mb_substr($sat, 0, 5);
      if ($sat === '') {
        continue; // baris tanpa satuan valid → skip
      }

      $amount = $qty * $price;
      $subtotal += $amount;

      $rowsDt[] = [
        // 'fstockmtno' dan 'fstockmtcode' akan diisi di dalam transaksi
        'fprdcode'       => $prdId,       // Dari fitemcode[]
        'frefdtno'       => $rref,       // Dari frefdtno[]
        'fqty'           => $qty,        // Dari fqty[]
        'fqtyremain'     => $qty,
        'fprice'         => $price,      // Dari fprice[]
        'fprice_rp'      => $price * $frate,
        'ftotprice'      => $amount,     // Dihitung ulang
        'ftotprice_rp'   => $amount * $frate,
        'fuserid'        => $userid,
        'fdatetime'      => $now,
        'fketdt'         => '',
        'fcode'          => '0',
        'fnouref'        => $rnour !== null ? (int)$rnour : null, // Dari fnouref[]
        'frefso'         => null,
        'fdesc'          => $desc,       // Dari fdesc[]
        'fsatuan'        => $sat,        // Dari fsatuan[]
        'fqtykecil'      => $qty,
        'fclosedt'       => '0',
        'fdiscpersen'    => 0,
        'fbiaya'         => 0,
      ];
    }

    if (empty($rowsDt)) {
      return back()->withInput()->withErrors([
        'detail' => 'Minimal satu item valid (Kode, Satuan, Qty > 0).'
      ]);
    }

    // Hitung grand total
    $grandTotal = $subtotal + $ppnAmount;

    // =========================
    // 5) TRANSAKSI DB
    // =========================
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
      &$fstockmtno, // Tanda & agar nilainya bisa di-update
      &$rowsDt,     // Tanda & agar bisa dimodifikasi
      $subtotal,
      $ppnAmount,
      $grandTotal
    ) {
      // ---- 5.1. Generate fstockmtno jika kosong ----
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

      $fstockmtcode = 'RCV'; // Kode transaksi

      if (empty($fstockmtno)) {
        $prefix = sprintf('%s.%s.%s.%s.', $fstockmtcode, $kodeCabang, $yy, $mm); // RCV.NA.25.10.

        // Lock untuk mencegah nomor duplikat
        $lockKey = crc32('STOCKMT|' . $fstockmtcode . '|' . $kodeCabang . '|' . $fstockmtdate->format('Y-m'));
        DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

        $last = DB::table('trstockmt')
          ->where('fstockmtno', 'like', $prefix . '%')
          ->selectRaw("MAX(CAST(split_part(fstockmtno, '.', 5) AS int)) AS lastno")
          ->value('lastno');

        $next = (int)$last + 1;
        $fstockmtno = $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
      }

      $masterData = [
        'fstockmtno'       => $fstockmtno, // Kode string yang tadi di-generate
        'fstockmtcode'     => $fstockmtcode,
        'fstockmtdate'     => $fstockmtdate,
        'fprdout'          => '0',
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
        'frefno'           => null,
        'frefpo'           => null,
        'ftrancode'        => null,
        'ffrom'            => $ffrom,
        'fto'              => null,
        'fkirim'           => null,
        'fprdjadi'         => null,
        'fqtyjadi'         => null,
        'fket'             => $fket,
        'fuserid'          => $userid,
        'fdatetime'        => $now,
        'fsalesman'        => null,
        'fjatuhtempo'      => null,
        'fprint'           => 0,
        'fsudahtagih'      => '0',
        'fbranchcode'      => $kodeCabang,
        'fdiscount'        => 0,
      ];

      $newStockMasterId = DB::table('trstockmt')->insertGetId($masterData, 'fstockmtid');

      if (!$newStockMasterId) {
        throw new \Exception("Gagal menyimpan data master (header).");
      }

      $lastNouRef = (int) DB::table('trstockdt')
        ->where('fstockmtid', $newStockMasterId)
        ->max('fnouref');
      $nextNouRef = $lastNouRef + 1;

      foreach ($rowsDt as &$r) {
        $r['fstockmtid']   = $newStockMasterId;
        $r['fstockmtcode'] = $fstockmtcode;
        $r['fstockmtno']   = $fstockmtno;

        if (!isset($r['fnouref']) || $r['fnouref'] === null) {
          $r['fnouref'] = $nextNouRef++;
        }
      }
      unset($r);

      DB::table('trstockdt')->insert($rowsDt);
    });

    return redirect()
      ->route('penerimaanbarang.create')
      ->with('success', "Transaksi {$fstockmtno} tersimpan.");
  }

  public function edit($fstockmtid)
  {
    $supplier = Supplier::all();

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

    $fcabang     = $branch->fcabangname ?? (string) $raw;
    $fbranchcode = $branch->fcabangkode ?? (string) $raw;

    // 1. Ambil data Header (trstockmt) DAN relasi Details (trstockdt)
    // Biarkan query ini. Sekarang $fstockmtid di sini adalah integer (misal: 8)
    $penerimaanbarang = PenerimaanPembelianHeader::with([
      'details' => function ($query) {
        $query
          // 2. Join ke msprd berdasarkan ID
          ->join('msprd', 'msprd.fprdid', '=', 'trstockdt.fprdcode')
          // 3. Select kolom yang dibutuhkan
          ->select(
            'trstockdt.*', // Ambil semua kolom dari tabel detail
            'msprd.fprdname', // Ambil nama produk
            'msprd.fprdcode as fitemcode_text' // Ambil KODE string produk
          )
          ->orderBy('trstockdt.fstockdtid', 'asc');
      }
    ])
      ->findOrFail($fstockmtid); // Temukan header berdasarkan $fstockmtid dari URL


    // 4. Map the data for savedItems (sudah menggunakan data yang benar)
    $savedItems = $penerimaanbarang->details->map(function ($d) {
      return [
        'uid'       => $d->fstockdtid,
        'fitemcode' => $d->fitemcode_text ?? '',
        'fitemname' => $d->fprdname ?? '',
        'fsatuan'   => $d->fsatuan ?? '',
        'fprno'     => $d->frefpr ?? '-',
        'frefpr'    => $d->frefpr ?? null,
        'fpono'     => $d->fpono ?? null,
        'famountponet' => $d->famountponet ?? null,
        'famountpo' => $d->famountpo ?? null,
        'frefdtno'  => $d->frefdtno ?? null,
        'fnouref'   => $d->fnouref ?? null,
        'fqty'      => (float)($d->fqty ?? 0),
        'fterima'   => (float)($d->fterima ?? 0),
        'fprice'    => (float)($d->fprice ?? 0),
        'fdisc'     => (float)($d->fdiscpersen ?? 0),
        'ftotal'    => (float)($d->ftotprice ?? 0),
        'fdesc'     => is_array($d->fdesc) ? implode(', ', $d->fdesc) : ($d->fdesc ?? ''),
        'fketdt'    => $d->fketdt ?? '',
        'units'     => [],
      ];
    })->values();

    // Sisa kode Anda sudah benar
    $selectedSupplierCode = $penerimaanbarang->fsupplier;

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
          'name'  => $p->fprdname,
          'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
          'stock' => $p->fminstock ?? 0,
        ],
      ];
    })->toArray();

    return view('penerimaanbarang.edit', [
      'supplier'           => $supplier,
      'selectedSupplierCode' => $selectedSupplierCode,
      'fcabang'            => $fcabang,
      'fbranchcode'        => $fbranchcode,
      'warehouses'         => $warehouses,
      'products'           => $products,
      'productMap'         => $productMap,
      'penerimaanbarang'    => $penerimaanbarang,
      'savedItems'         => $savedItems,
      'ppnAmount'          => (float) ($penerimaanbarang->famountpopajak ?? 0),
      'famountponet'       => (float) ($penerimaanbarang->famountponet ?? 0),
      'famountpo'          => (float) ($penerimaanbarang->famountpo ?? 0),
    ]);
  }

  public function update(Request $request, $fstockmtid)
  {
    // =========================
    // 1) VALIDASI INPUT
    // =========================
    $request->validate([
      'fstockmtno'     => ['nullable', 'string', 'max:100'],
      'fstockmtdate'   => ['required', 'date'],
      'fsupplier'      => ['required', 'string', 'max:30'],
      'ffrom'          => ['nullable', 'integer', 'exists:mswh,fwhid'],
      'fket'           => ['nullable', 'string', 'max:50'],
      'fbranchcode'    => ['nullable', 'string', 'max:20'],
      'fitemcode'      => ['required', 'array', 'min:1'],
      'fitemcode.*'    => ['required', 'string', 'max:50'],
      'fsatuan'        => ['nullable', 'array'],
      'fsatuan.*'      => ['nullable', 'string', 'max:5'],
      'frefdtno'       => ['nullable', 'array'],
      'frefdtno.*'     => ['nullable', 'string', 'max:20'],
      'fnouref'        => ['nullable', 'array'],
      'fnouref.*'      => ['nullable', 'integer'],
      'fqty'           => ['required', 'array'],
      'fqty.*'         => ['numeric', 'min:0.01'],
      'fprice'         => ['required', 'array'],
      'fprice.*'       => ['numeric', 'min:0'],
      'fdesc'          => ['nullable', 'array'],
      'fdesc.*'        => ['nullable', 'string', 'max:500'],
      'fcurrency'      => ['nullable', 'string', 'max:5'],
      'frate'          => ['nullable', 'numeric', 'min:0'],
      'famountpopajak' => ['nullable', 'numeric', 'min:0'],
    ], [
      'fstockmtdate.required' => 'Tanggal transaksi wajib diisi.',
      'fsupplier.required'    => 'Supplier wajib diisi.',
      'fitemcode.required'    => 'Minimal 1 item.',
      'fqty.*.min'            => 'Qty tidak boleh 0.',
      'ffrom.exists'          => 'Gudang (ffrom/fwhid) tidak valid.',
      'ffrom.integer'         => 'Gudang (ffrom/fwhid) harus berupa angka ID.',
    ]);

    // =========================
    // 2) AMBIL DATA MASTER & HEADER
    // =========================
    $header = PenerimaanPembelianHeader::findOrFail($fstockmtid);

    $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
    $fsupplier    = trim((string)$request->input('fsupplier'));
    $ffrom        = $request->input('ffrom');
    $fket         = trim((string)$request->input('fket', ''));
    $fbranchcode  = $request->input('fbranchcode');
    $fcurrency    = $request->input('fcurrency', 'IDR');
    $frate        = (float)$request->input('frate', 1);
    if ($frate <= 0) $frate = 1;
    $ppnAmount    = (float)$request->input('famountpopajak', 0);
    $userid       = auth('sysuser')->user()->fsysuserid ?? 'admin';
    $now          = now();

    // =========================
    // 3) DETAIL ARRAYS
    // =========================
    $codes   = $request->input('fitemcode', []);
    $satuans = $request->input('fsatuan', []);
    $refdtno = $request->input('frefdtno', []);
    $nourefs = $request->input('fnouref', []);
    $qtys    = $request->input('fqty', []);
    $prices  = $request->input('fprice', []);
    $descs   = $request->input('fdesc', []);

    // =========================
    // 4) LOGIC PROD META & RAKIT DETAIL
    // =========================
    $uniqueCodes = array_values(array_unique(array_filter(array_map(fn($c) => trim((string)$c), $codes))));
    $prodMeta = DB::table('msprd')
      ->whereIn('fprdcode', $uniqueCodes)
      ->get(['fprdid', 'fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'])
      ->keyBy('fprdcode');

    $pickDefaultSat = function (?object $meta) use ($prodMeta): string {
      if (!$meta) return '';
      foreach (['fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'] as $k) {
        $v = trim((string)($meta->$k ?? ''));
        if ($v !== '') return mb_substr($v, 0, 5);
      }
      return '';
    };

    $rowsDt   = [];
    $subtotal = 0.0;
    $rowCount = count($codes);

    for ($i = 0; $i < $rowCount; $i++) {
      $code  = trim((string)($codes[$i]   ?? ''));
      $sat   = trim((string)($satuans[$i] ?? ''));
      $rref  = trim((string)($refdtno[$i] ?? ''));
      $rnour = $nourefs[$i] ?? null;
      $qty   = (float)($qtys[$i]   ?? 0);
      $price = (float)($prices[$i] ?? 0);
      $desc  = (string)($descs[$i]  ?? '');

      if ($code === '' || $qty <= 0) continue;

      $meta = $prodMeta[$code] ?? null;
      if (!$meta) continue;

      $prdId = $meta->fprdid;

      if ($sat === '') {
        $sat = $pickDefaultSat($meta);
      }
      $sat = mb_substr($sat, 0, 5);
      if ($sat === '') continue;

      $amount = $qty * $price;
      $subtotal += $amount;

      $rowsDt[] = [
        'fprdcode'       => $prdId,
        'frefdtno'       => $rref,
        'fqty'           => $qty,
        'fqtyremain'     => $qty,
        'fprice'         => $price,
        'fprice_rp'      => $price * $frate,
        'ftotprice'      => $amount,
        'ftotprice_rp'   => $amount * $frate,
        'fuserid'        => $userid,
        'fdatetime'      => $now, // Tetap gunakan fdatetime
        'fketdt'         => '',
        'fcode'          => '0',
        'fnouref'        => $rnour !== null ? (int)$rnour : null,
        'frefso'         => null,
        'fdesc'          => $desc,
        'fsatuan'        => $sat,
        'fqtykecil'      => $qty,
        'fclosedt'       => '0',
        'fdiscpersen'    => 0,
        'fbiaya'         => 0,
      ];
    }

    if (empty($rowsDt)) {
      return back()->withInput()->withErrors([
        'detail' => 'Minimal satu item valid (Kode, Satuan, Qty > 0).'
      ]);
    }

    $grandTotal = $subtotal + $ppnAmount;

    // =========================
    // 5) TRANSAKSI DB
    // =========================
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
      $userid,
      $now,
      &$rowsDt,
      $subtotal,
      $ppnAmount,
      $grandTotal
    ) {

      // ---- 5.1. Cek Kode Cabang ----
      $kodeCabang = $header->fbranchcode;
      if ($fbranchcode !== null && $fbranchcode !== $header->fbranchcode) {
        $needle = trim((string)$fbranchcode);
        if ($needle !== '') {
          if (is_numeric($needle)) {
            $kodeCabang = DB::table('mscabang')->where('fcabangid', (int)$needle)->value('fcabangkode');
          } else {
            $kodeCabang = DB::table('mscabang')->whereRaw('LOWER(fcabangkode)=LOWER(?)', [$needle])->value('fcabangkode')
              ?: DB::table('mscabang')->whereRaw('LOWER(fcabangname)=LOWER(?)', [$needle])->value('fcabangkode');
          }
        }
        if (!$kodeCabang) $kodeCabang = 'NA';
      }

      // ---- 5.2. UPDATE HEADER: trstockmt ----
      $masterData = [
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
        'ffrom'            => $ffrom, // fwhid
        'fket'             => $fket,
        'fuserid'          => $userid,
        // Kolom 'fupdateat' akan diupdate otomatis oleh model
        'fbranchcode'      => $kodeCabang,
      ];

      $header->update($masterData);

      // ---- 5.3. HAPUS DETAIL LAMA ----
      DB::table('trstockdt')->where('fstockmtid', $fstockmtid)->delete();

      // ---- 5.4. INSERT DETAIL BARU ----
      $fstockmtcode = $header->fstockmtcode;
      $fstockmtno   = $header->fstockmtno;
      $nextNouRef = 1;

      foreach ($rowsDt as &$r) {
        $r['fstockmtid']   = $fstockmtid;
        $r['fstockmtcode'] = $fstockmtcode;
        $r['fstockmtno']   = $fstockmtno;

        if (!isset($r['fnouref']) || $r['fnouref'] === null) {
          $r['fnouref'] = $nextNouRef++;
        }
      }
      unset($r);

      DB::table('trstockdt')->insert($rowsDt);
    });

    return redirect()
      ->route('penerimaanbarang.edit', $fstockmtid)
      ->with('success', "Transaksi {$header->fstockmtno} berhasil diperbarui.");
  }

  public function destroy($fstockmtid)
  {
    $penerimaanbarang = PenerimaanPembelianHeader::findOrFail($fstockmtid);
    $penerimaanbarang->delete();

    return redirect()
      ->route('penerimaanbarang.index')
      ->with('success', 'Penerimaan Barang Berhasil Dihapus.');
  }
}
