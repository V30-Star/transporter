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
use Illuminate\Validation\ValidationException; // <-- TAMBAHKAN INI
use App\Models\PenerimaanPembelianDetail;
use App\Models\PenerimaanPembelianHeader;
use App\Mail\ApprovalEmailPo;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon; // sekalian biar aman untuk tanggal

class AdjstockController extends Controller
{
  public function index(Request $request)
  {
    // --- 1. PERMISSIONS ---
    $canCreate = in_array('createPenerimaanBarang', explode(',', session('user_restricted_permissions', '')));
    $canEdit   = in_array('updatePenerimaanBarang', explode(',', session('user_restricted_permissions', '')));
    $canDelete = in_array('deletePenerimaanBarang', explode(',', session('user_restricted_permissions', '')));
    $showActionsColumn = $canEdit || $canDelete;

    // --- 2. Handle Request AJAX dari DataTables ---
    if ($request->ajax()) {

      // Query dasar HANYA untuk 'ADJ' (Adjustment)
      $query = PenerimaanPembelianHeader::where('fstockmtcode', 'ADJ');

      // Total records (dengan filter 'ADJ')
      $totalRecords = PenerimaanPembelianHeader::where('fstockmtcode', 'ADJ')->count();

      // Handle Search (cari di No. Adjustment)
      if ($search = $request->input('search.value')) {
        $query->where('fstockmtno', 'like', "%{$search}%");
      }

      // Total records setelah filter search
      $filteredRecords = (clone $query)->count();

      // Handle Sorting
      $orderColIdx = $request->input('order.0.column', 0);
      $orderDir = $request->input('order.0.dir', 'desc');

      $sortableColumns = ['fstockmtno', 'fstockmtdate'];

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
        ->get(['fstockmtid', 'fstockmtno', 'fstockmtdate']);

      // Format Data - HANYA RETURN DATA MENTAH
      $data = $records->map(function ($row) {
        return [
          'fstockmtid'   => $row->fstockmtid,
          'fstockmtno'   => $row->fstockmtno,
          'fstockmtdate' => Carbon::parse($row->fstockmtdate)->format('d/m/Y')
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
    return view('adjstock.index', compact(
      'canCreate',
      'canEdit',
      'canDelete',
      'showActionsColumn'
    ));
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

    return view('adjstock.print', [
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
      'fmerek',
      'fsatuankecil',
      'fsatuanbesar',
      'fsatuanbesar2',
      'fminstock'
    )->orderBy('fprdname')->get();

    return view('adjstock.create', [
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
      // =========================
      // TAHAP 1: VALIDASI INPUT
      // =========================
      $validated = $request->validate([
        'fstockmtno'     => ['nullable', 'string', 'max:100'],
        'fstockmtdate'   => ['required', 'date'],
        'ffrom'          => ['nullable', 'string', 'max:10'], // Sepertinya ini fwhid?
        'ftrancode'      => ['nullable', 'string', 'max:3'],
        'fket'           => ['nullable', 'string', 'max:50'],
        'fbranchcode'    => ['nullable', 'string', 'max:20'],
        'fitemcode'      => ['required', 'array', 'min:1'],
        'fitemcode.*'    => ['required', 'string', 'max:50'],
        'fsatuan'        => ['nullable', 'array'],
        'fsatuan.*'      => ['nullable', 'string', 'max:5'],
        'frefno.*'       => ['nullable', 'string', 'max:20'],
        'fsupplier'      => ['nullable', 'integer'],
        'fnouref'        => ['nullable', 'array'],
        'fnouref.*'      => ['nullable', 'integer'],
        'fqty'           => ['required', 'array'],
        'fqty.*'         => ['required', 'numeric', 'min:0.01'], // Minimal 0.01
        'fprice'         => ['required', 'array'],
        'fprice.*'       => ['numeric', 'min:0'],
        'fdesc'          => ['nullable', 'array'],
        'fdesc.*'        => ['nullable', 'string', 'max:500'],
        'fcurrency'      => ['nullable', 'string', 'max:5'],
        'frate'          => ['nullable', 'numeric', 'min:0'],
        'famountpopajak' => ['nullable', 'numeric', 'min:0'],
      ]);

      // =========================
      // TAHAP 2: AMBIL DATA MASTER PRODUK
      // =========================
      // Ambil semua kode item yang unik dari form
      $uniqueCodes = array_values(array_unique(
        array_filter(
          array_map(fn($c) => trim((string)$c), $request->input('fitemcode', []))
        )
      ));

      // Query ke database sekali saja untuk semua data produk
      $prodMeta = collect();
      if (!empty($uniqueCodes)) {
        $prodMeta = DB::table('msprd')
          ->whereIn('fprdcode', $uniqueCodes)
          ->get(['fprdid', 'fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'])
          ->keyBy('fprdcode'); // Di-index berdasarkan fprdcode agar mudah dicari
      }

      // =========================
      // TAHAP 3: RAKIT DETAIL & HITUNG SUBTOTAL
      // =========================

      // --- Helper lokal untuk cari satuan default ---
      // Ini adalah 'closure' atau fungsi anonim, berguna untuk logika
      // yang dipakai berulang kali di dalam satu fungsi.
      $pickDefaultSat = function (?object $meta): string {
        if (!$meta) return '';
        // Cek urutan prioritas: kecil, besar, besar2
        foreach (['fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'] as $k) {
          $v = trim((string)($meta->$k ?? ''));
          if ($v !== '') {
            return mb_substr($v, 0, 5);
          }
        }
        return '';
      };
      // --- Akhir helper lokal ---

      $rowsDt   = [];
      $subtotal = 0.0;
      $userid   = auth('sysuser')->user()->fsysuserid ?? 'admin';
      $now      = now();
      $frate    = (float)$request->input('frate', 1);
      if ($frate <= 0) $frate = 1;

      // Ambil semua array input dari form
      $codes   = $request->input('fitemcode', []);
      $satuans = $request->input('fsatuan', []);
      $refdtno = $request->input('frefdtno', []);
      $nourefs = $request->input('fnouref', []);
      $qtys    = $request->input('fqty', []);
      $prices  = $request->input('fprice', []);
      $descs   = $request->input('fdesc', []);

      $rowCount = count($codes);

      // Looping sebanyak baris item di form
      for ($i = 0; $i < $rowCount; $i++) {
        $code  = trim((string)($codes[$i]   ?? ''));
        $sat   = trim((string)($satuans[$i] ?? ''));
        $rref  = trim((string)($refdtno[$i] ?? ''));
        $rnour = $nourefs[$i] ?? null;
        $qty   = (float)($qtys[$i]    ?? 0);
        $price = (float)($prices[$i]  ?? 0);
        $desc  = (string)($descs[$i]   ?? '');

        // Lewati (skip) jika kode kosong atau qty 0
        if ($code === '' || $qty <= 0) {
          continue;
        }

        // Ambil data master produk
        $meta = $prodMeta[$code] ?? null;

        // Lewati jika data master produk tidak ditemukan
        if (!$meta) {
          continue;
        }

        $prdId = $meta->fprdid;

        // Jika satuan di form kosong, cari satuan default
        if ($sat === '') {
          $sat = $pickDefaultSat($meta); // <-- Menggunakan helper lokal
        }
        $sat = mb_substr($sat, 0, 5); // Pastikan tidak lebih dari 5 karakter

        // Lewati jika satuan tetap kosong
        if ($sat === '') {
          continue;
        }

        $amount = $qty * $price;
        $subtotal += $amount; // Tambahkan ke subtotal transaksi

        // Masukkan data baris ini ke array $rowsDt
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
          'fdatetime'      => $now,
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
          'fstockmtid'     => null, // Akan diisi di Tahap 5
          'fstockmtcode'   => null, // Akan diisi di Tahap 5
          'fstockmtno'     => null, // Akan diisi di Tahap 5
        ];
      }

      // Jika setelah diproses tidak ada item yang valid
      if (empty($rowsDt)) {
        return back()->withInput()->withErrors([
          'detail' => 'Minimal satu item valid (Kode, Satuan, Qty > 0) harus diisi.'
        ]);
      }

      // =========================
      // TAHAP 4: PERSIAPAN DATA HEADER
      // =========================
      $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
      $ppnAmount    = (float)$request->input('famountpopajak', 0);
      $grandTotal   = $subtotal + $ppnAmount;

      // Siapkan array data untuk tabel 'trstockmt'
      $headerData = [
        'fstockmtno'       => trim((string)$request->input('fstockmtno')),
        'fstockmtcode'     => 'ADJ',
        'fstockmtdate'     => $fstockmtdate,
        'fprdout'          => '0',
        'fsupplier'        => '0',
        'fcurrency'        => $request->input('fcurrency', 'IDR'),
        'frate'            => $frate,
        'famount'          => round($subtotal, 2),
        'famount_rp'       => round($subtotal * $frate, 2),
        'famountpajak'     => round($ppnAmount, 2),
        'famountpajak_rp'  => round($ppnAmount * $frate, 2),
        'famountmt'        => round($grandTotal, 2),
        'famountmt_rp'     => round($grandTotal * $frate, 2),
        'famountremain'    => round($grandTotal, 2),
        'famountremain_rp' => round($grandTotal * $frate, 2),
        'frefpo'           => null,
        'ftrancode'        => $request->input('ftrancode'),
        'ffrom'            => $request->input('fwhid'),
        'frefno'           => $request->input('faccid'),
        'fto'              => null,
        'fkirim'           => null,
        'fprdjadi'         => null,
        'fqtyjadi'         => null,
        'fket'             => trim((string)$request->input('fket', '')),
        'fuserid'          => $userid,
        'fdatetime'        => $now,
        'fsalesman'        => null,
        'fjatuhtempo'      => null,
        'fprint'           => 0,
        'fsudahtagih'      => '0',
        'fbranchcode'      => $request->input('fbranchcode'), // <-- Input mentah
        'fdiscount'        => 0,
      ];

      // =========================
      // TAHAP 5: TRANSAKSI DATABASE
      // =========================
      // DB::transaction memastikan semua query di dalamnya berhasil,
      // atau jika ada 1 saja yang gagal, semua akan dibatalkan (rollback).

      $fstockmtno = DB::transaction(function () use (
        $headerData, // <-- $headerData sudah lengkap
        &$rowsDt     // <-- &$rowsDt (array detail)
      ) {

        // ---- 5.1. Generate Nomor Transaksi (jika kosong) ----
        $fstockmtno = trim((string)$headerData['fstockmtno']);

        if (empty($fstockmtno)) {

          // --- Logika mencari kode cabang (prefix) ---
          $kodeCabang = null;
          $needle = trim((string)$headerData['fbranchcode']); // Ambil dari $headerData

          if ($needle !== '') {
            if (is_numeric($needle)) {
              $kodeCabang = DB::table('mscabang')->where('fcabangid', (int)$needle)->value('fcabangkode');
            } else {
              $kodeCabang = DB::table('mscabang')->whereRaw('LOWER(fcabangkode)=LOWER(?)', [$needle])->value('fcabangkode');
              if (!$kodeCabang) {
                $kodeCabang = DB::table('mscabang')->whereRaw('LOWER(fcabangname)=LOWER(?)', [$needle])->value('fcabangkode');
              }
            }
          }
          $kodeCabang = $kodeCabang ?: 'NA';
          // --- Akhir Logika kode cabang ---

          // --- Logika Generate Nomor (Auto-numbering) ---
          $fstockmtcode = $headerData['fstockmtcode']; // 'RCV'
          $date         = $headerData['fstockmtdate']; // Objek Carbon
          $yy = $date->format('y');
          $mm = $date->format('m');
          $prefix = sprintf('%s.%s.%s.%s.', $fstockmtcode, $kodeCabang, $yy, $mm);

          // Kunci tabel (PostgreSQL Advisory Lock)
          $lockKey = crc32('STOCKMT|' . $fstockmtcode . '|' . $kodeCabang . '|' . $date->format('Y-m'));
          DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

          // Cari nomor terakhir
          $last = DB::table('trstockmt')
            ->where('fstockmtno', 'like', $prefix . '%')
            ->selectRaw("MAX(CAST(split_part(fstockmtno, '.', 5) AS int)) AS lastno")
            ->value('lastno');

          $next = (int)$last + 1;
          $fstockmtno = $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
          // --- Akhir Logika Generate Nomor ---

          // Update $headerData dengan kode cabang & nomor baru
          $headerData['fbranchcode'] = $kodeCabang;
          $headerData['fstockmtno']  = $fstockmtno;
        }

        // ---- 5.2. Insert Data Header (trstockmt) ----
        $newStockMasterId = DB::table('trstockmt')->insertGetId(
          $headerData,
          'fstockmtid' // Kolom auto-increment
        );

        if (!$newStockMasterId) {
          throw new \Exception("Gagal menyimpan data header.");
        }

        // ---- 5.3. Insert Data Detail (trstockdt) ----

        // Cari nomor urut (fnouref) terakhir
        $lastNouRef = (int) DB::table('trstockdt')
          ->where('fstockmtid', $newStockMasterId)
          ->max('fnouref');
        $nextNouRef = $lastNouRef + 1;

        // Loop untuk melengkapi ID dan nomor urut di data detail
        foreach ($rowsDt as &$r) {
          $r['fstockmtid']   = $newStockMasterId;
          $r['fstockmtcode'] = $headerData['fstockmtcode'];
          $r['fstockmtno']   = $fstockmtno;

          if (!isset($r['fnouref']) || $r['fnouref'] === null) {
            $r['fnouref'] = $nextNouRef++;
          }
        }
        unset($r); // Wajib di-unset setelah loop by reference

        // Insert semua data detail sekaligus (Bulk Insert)
        DB::table('trstockdt')->insert($rowsDt);

        // Kembalikan nomor transaksi untuk ditampilkan di pesan sukses
        return $fstockmtno;
      }); // Akhir dari DB::transaction

      // =========================
      // TAHAP 6: SUKSES
      // =========================
      return redirect()
        ->route('adjstock.create') // <-- Ganti ke route yang sesuai
        ->with('success', "Transaksi {$fstockmtno} berhasil disimpan.");
    } catch (ValidationException $e) {
      // Jika validasi gagal, lempar error agar Laravel otomatis
      // redirect-back-with-errors
      throw $e;
    } catch (\Exception $e) {
      // Untuk semua error lainnya
      return back()->withInput()->withErrors([
        'fatal' => 'Terjadi error: ' . $e->getMessage()
      ]);
    }
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

    $accounts = DB::table('account')
      ->select('faccid', 'faccount', 'faccname', 'fnonactive')
      ->where('fnonactive', '0')
      ->orderBy('account')
      ->get();

    $fcabang     = $branch->fcabangname ?? (string) $raw;
    $fbranchcode = $branch->fcabangkode ?? (string) $raw;

    // 1. Ambil data Header (trstockmt) DAN relasi Details (trstockdt)
    // Biarkan query ini. Sekarang $fstockmtid di sini adalah integer (misal: 8)
    $adjstock = PenerimaanPembelianHeader::with([
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
    $savedItems = $adjstock->details->map(function ($d) {
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
    $selectedSupplierCode = $adjstock->fsupplier;

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

    return view('adjstock.edit', [
      'supplier'           => $supplier,
      'selectedSupplierCode' => $selectedSupplierCode,
      'fcabang'            => $fcabang,
      'fbranchcode'        => $fbranchcode,
      'warehouses'         => $warehouses,
      'accounts'           => $accounts,
      'products'           => $products,
      'productMap'         => $productMap,
      'adjstock'    => $adjstock,
      'savedItems'         => $savedItems,
      'ppnAmount'          => (float) ($adjstock->famountpopajak ?? 0),
      'famountponet'       => (float) ($adjstock->famountponet ?? 0),
      'famountpo'          => (float) ($adjstock->famountpo ?? 0),
      'action' => 'edit'
    ]);
  }

  public function update(Request $request, $fstockmtid)
  {
    // =========================
    // 1) VALIDASI INPUT
    // =========================
    $validated = $request->validate([
      'fstockmtno'     => ['nullable', 'string', 'max:100'],
      'fstockmtdate'   => ['required', 'date'],
      'ffrom'          => ['nullable', 'string', 'max:10'], // Sepertinya ini fwhid?
      'ftrancode'      => ['nullable', 'string', 'max:3'],
      'fket'           => ['nullable', 'string', 'max:50'],
      'fbranchcode'    => ['nullable', 'string', 'max:20'],
      'fitemcode'      => ['required', 'array', 'min:1'],
      'fitemcode.*'    => ['required', 'string', 'max:50'],
      'fsatuan'        => ['nullable', 'array'],
      'fsatuan.*'      => ['nullable', 'string', 'max:5'],
      'frefno' => ['nullable', 'string'],
      'fnouref'        => ['nullable', 'array'],
      'fnouref.*'      => ['nullable', 'integer'],
      'fqty'           => ['required', 'array'],
      'fqty.*'         => ['required', 'numeric', 'min:0.01'], // Minimal 0.01
      'fprice'         => ['required', 'array'],
      'fprice.*'       => ['numeric', 'min:0'],
      'fdesc'          => ['nullable', 'array'],
      'fdesc.*'        => ['nullable', 'string', 'max:500'],
      'fcurrency'      => ['nullable', 'string', 'max:5'],
      'frate'          => ['nullable', 'numeric', 'min:0'],
      'famountpopajak' => ['nullable', 'numeric', 'min:0'],
    ]);
    // =========================
    // 2) AMBIL DATA MASTER & HEADER
    // =========================
    $header = PenerimaanPembelianHeader::findOrFail($fstockmtid);

    $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
    $ffrom        = $request->input('ffrom');
    $frefno        = $request->input('frefno');
    $ftrancode        = $request->input('ftrancode');
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
        'fstockmtid'     => null, // Akan diisi di Tahap 5
        'fstockmtcode'   => null, // Akan diisi di Tahap 5
        'fstockmtno'     => null, // Akan diisi di Tahap 5
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
      $ffrom,
      $frefno,
      $ftrancode,
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
        'ftrancode'            => $ftrancode,
        'frefno'           => $frefno,
        'fket'             => $fket,
        'fuserid'          => $userid,
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
      ->route('adjstock.index')
      ->with('success', "Transaksi {$header->fstockmtno} berhasil diperbarui.");
  }

  public function delete($fstockmtid)
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

    $accounts = DB::table('account')
      ->select('faccid', 'faccount', 'faccname', 'fnonactive')
      ->where('fnonactive', '0')
      ->orderBy('account')
      ->get();

    $fcabang     = $branch->fcabangname ?? (string) $raw;
    $fbranchcode = $branch->fcabangkode ?? (string) $raw;

    // 1. Ambil data Header (trstockmt) DAN relasi Details (trstockdt)
    // Biarkan query ini. Sekarang $fstockmtid di sini adalah integer (misal: 8)
    $adjstock = PenerimaanPembelianHeader::with([
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
    $savedItems = $adjstock->details->map(function ($d) {
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
    $selectedSupplierCode = $adjstock->fsupplier;

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

    return view('adjstock.edit', [
      'supplier'           => $supplier,
      'selectedSupplierCode' => $selectedSupplierCode,
      'fcabang'            => $fcabang,
      'fbranchcode'        => $fbranchcode,
      'warehouses'         => $warehouses,
      'accounts'           => $accounts,
      'products'           => $products,
      'productMap'         => $productMap,
      'adjstock'    => $adjstock,
      'savedItems'         => $savedItems,
      'ppnAmount'          => (float) ($adjstock->famountpopajak ?? 0),
      'famountponet'       => (float) ($adjstock->famountponet ?? 0),
      'famountpo'          => (float) ($adjstock->famountpo ?? 0),
      'action' => 'delete'
    ]);
  }

  public function destroy($fstockmtid)
  {
    try {
      $adjstock = PenerimaanPembelianHeader::findOrFail($fstockmtid);
      $adjstock->details()->delete();
      $adjstock->delete();

      return response()->json([
        'success' => true,
        'message' => 'Data Adjustment Stock berhasil dihapus'
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Gagal menghapus data: ' . $e->getMessage()
      ], 500);
    }
  }
}
