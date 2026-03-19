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
use Exception; // Pastikan ini ada jika menggunakan throw new \Exception
use Carbon\Carbon; // sekalian biar aman untuk tanggal

class JurnalTransaksiController extends Controller
{
  public function index(Request $request)
  {
    // --- 1. PERBAIKAN PERMISSIONS ---
    // Saya asumsikan ini nama permission yang benar untuk modul ini
    $canCreate = in_array('createjurnaltransaksi', explode(',', session('user_restricted_permissions', '')));
    $canEdit   = in_array('updatejurnaltransaksi', explode(',', session('user_restricted_permissions', '')));
    $canDelete = in_array('deletejurnaltransaksi', explode(',', session('user_restricted_permissions', '')));
    $showActionsColumn = $canEdit || $canDelete; // Anda bisa tambahkan $canPrint jika ada

    // --- 2. Handle Request AJAX dari DataTables ---
    if ($request->ajax()) {

      // Query dasar HANYA untuk 'PBR' (Receiving)
      $query = PenerimaanPembelianHeader::where('fstockmtcode', 'PBR');

      // Total records (dengan filter 'PBR')
      $totalRecords = PenerimaanPembelianHeader::where('fstockmtcode', 'PBR')->count();

      // Handle Search (cari di No. Penerimaan)
      if ($search = $request->input('search.value')) {
        $query->where('fstockmtno', 'like', "%{$search}%");
      }

      // Total records setelah filter search
      $filteredRecords = (clone $query)->count();

      // Handle Sorting
      $orderColIdx = $request->input('order.0.column', 0);
      $orderDir = $request->input('order.0.dir', 'asc');
      // Kolom di tabel: 0 = fstockmtno, 1 = fstockmtdate
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
        ->get(['fstockmtid', 'fstockmtno', 'fstockmtdate']); // fstockmtcode tidak perlu, krn sudah pasti RCV

      // Format Data (Tombol dibuat di sini)
      $data = $records->map(function ($row) use ($canEdit, $canDelete) {

        $actions = '';

        // --- Tombol view ---
        // if ($canView) {
        // Asumsi route edit Anda: jurnaltransaksi.edit
        $viewUrl = route('jurnaltransaksi.view', $row->fstockmtid);
        $actions .= ' <a href="' . $viewUrl . '" class="inline-flex items-center bg-slate-500 text-white px-4 py-2 rounded hover:bg-slate-600">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg> View
                                </a>';
        // }

        // --- Tombol Edit ---
        // if ($canEdit) {
        // Asumsi route edit Anda: jurnaltransaksi.edit
        $editUrl = route('jurnaltransaksi.edit', $row->fstockmtid);
        $actions .= ' <a href="' . $editUrl . '" class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg> Edit
                                </a>';
        // }

        // --- Tombol Delete ---
        // if ($canDelete) {
        // Asumsi route destroy Anda: jurnaltransaksi.destroy
        $deleteUrl = route('jurnaltransaksi.delete', $row->fstockmtid);
        $actions .= '<a href="' . $deleteUrl . '">
                <button class="inline-flex items-center bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Hapus
                </button>
            </a>';
        // }

        return [
          'fstockmtno'   => $row->fstockmtno,
          'fstockmtdate' => Carbon::parse($row->fstockmtdate)->format('d/m/Y'),
          'actions'      => $actions
        ];
      });

      // 9. Kirim Response JSON
      return response()->json([
        'draw'            => intval($request->input('draw')),
        'recordsTotal'    => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data'            => $data
      ]);
    }

    // --- 3. Handle Request non-AJAX (Saat load halaman) ---
    return view('jurnaltransaksi.index', compact(
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
        'fpohid as fprid',     // FE expects fprid
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
    $header = Tr_poh::where('fpohid', $id)->firstOrFail();

    // Mengambil detail dari tr_pod
    $items = DB::table('tr_pod')
      // =================================================================
      // PERBAIKAN: Gunakan ID dari header (fpohid) untuk mencocokkan.
      // Kolom tr_pod.fpono (integer) dicocokkan dengan $header->fpohid (integer).
      // =================================================================
      ->where('tr_pod.fpono', $header->fpohid) // <-- DIUBAH DARI $header->fpono

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
        'fprid'     => $header->fpohid,
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

    $prefix = sprintf('PBR.%s.%s.%s.', $kodeCabang, $date->format('y'), $date->format('m'));

    // kunci per (branch, tahun-bulan) — TANPA bikin tabel baru
    $lockKey = crc32('PBR|' . $kodeCabang . '|' . $date->format('Y-m'));
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

    return view('jurnaltransaksi.print', [
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

    $accounts = DB::table('account')
      ->select('faccid', 'faccount', 'faccname', 'fnonactive', 'fhavesubaccount')
      ->where('fnonactive', '0')
      ->where('fend', '1')
      ->orderBy('account')
      ->get();

    $subaccounts = DB::table('mssubaccount')
      ->select('fsubaccountid', 'fsubaccountcode', 'fsubaccountname')
      ->where('fnonactive', '0')
      ->orderBy('fsubaccountcode')
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

    return view('jurnaltransaksi.create', [
      'newtr_prh_code' => $newtr_prh_code,
      'accounts' => $accounts,
      'subaccounts' => $subaccounts,
      'supplier' => $supplier,
      'fcabang' => $fcabang,
      'fbranchcode' => $fbranchcode,
      'products' => $products,
    ]);
  }

  public function store(Request $request)
  {
    // =========================================================
    // 1) VALIDASI — field sesuai kolom jurnalmt & jurnaldt
    // =========================================================
    $request->validate([
      // jurnalmt
      'fjurnalno'      => ['nullable', 'string', 'max:100'],
      'fjurnaltype'    => ['required', 'string', 'max:10'],
      'fjurnaldate'    => ['required', 'date'],
      'fjurnalnote'    => ['nullable', 'string', 'max:500'],
      'fbranchcode'    => ['nullable', 'string', 'max:20'],

      // jurnaldt (array)
      'faccount'       => ['required', 'array', 'min:1'],
      'faccount.*'     => ['required', 'string', 'max:50'],

      'fsubaccount'    => ['nullable', 'array'],
      'fsubaccount.*'  => ['nullable', 'string', 'max:50'],

      'fdk'            => ['required', 'array'],
      'fdk.*'          => ['required', 'string', 'in:D,K'],

      'faccountnote'   => ['nullable', 'array'],
      'faccountnote.*' => ['nullable', 'string', 'max:255'],

      'frefno'         => ['nullable', 'array'],
      'frefno.*'       => ['nullable', 'string', 'max:100'],

      'famount'        => ['required', 'array'],
      'famount.*'      => ['numeric', 'min:0'],

      'frate'          => ['nullable', 'array'],
      'frate.*'        => ['nullable', 'numeric', 'min:0'],
    ], [
      'fjurnaldate.required' => 'Tanggal jurnal wajib diisi.',
      'fjurnaltype.required' => 'Tipe jurnal wajib diisi.',
      'faccount.required'    => 'Minimal 1 baris jurnal.',
      'fdk.*.in'             => 'Nilai D/K harus Debit (D) atau Kredit (K).',
    ]);

    // =========================================================
    // 2) AMBIL DATA HEADER
    // =========================================================
    $fjurnaldate = Carbon::parse($request->fjurnaldate)->startOfDay();
    $fjurnaltype = $request->input('fjurnaltype', 'SJU');
    $fjurnalnote = trim((string) $request->input('fjurnalnote', ''));
    $fbranchcode = $request->input('fbranchcode');
    $now         = now();
    $fuserid     = Auth::user()->fname ?? Auth::user()->name ?? 'system';

    // ── Resolve kode cabang ──
    $kodeCabang = null;
    if ($fbranchcode) {
      $needle = trim((string) $fbranchcode);
      if ($needle !== '') {
        if (is_numeric($needle)) {
          $kodeCabang = DB::table('mscabang')
            ->where('fcabangid', (int) $needle)
            ->value('fcabangkode');
        } else {
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
    }
    if (!$kodeCabang) $kodeCabang = 'NA';

    $yy = $fjurnaldate->format('y');
    $mm = $fjurnaldate->format('m');

    // =========================================================
    // 3) RAKIT BARIS DETAIL — field = kolom jurnaldt
    // =========================================================
    $accounts    = $request->input('faccount',      []);
    $subaccounts = $request->input('fsubaccount',   []);
    $dks         = $request->input('fdk',           []);
    $notes       = $request->input('faccountnote',  []);
    $refnos      = $request->input('frefno',        []);
    $amounts     = $request->input('famount',       []);
    $rates       = $request->input('frate',         []);

    $rowsDt      = [];
    $totalDebit  = 0.0;
    $totalKredit = 0.0;
    $rowCount    = count($accounts);

    for ($i = 0; $i < $rowCount; $i++) {
      $faccount    = trim((string) ($accounts[$i]    ?? ''));
      $fsubaccount = trim((string) ($subaccounts[$i] ?? '')) ?: null;
      $fdk         = strtoupper(trim((string) ($dks[$i]  ?? '')));
      $fnote       = trim((string) ($notes[$i]  ?? '')) ?: null;
      $frefno      = trim((string) ($refnos[$i] ?? '')) ?: null;
      $famount     = round((float) ($amounts[$i] ?? 0), 2);
      $frate       = round((float) ($rates[$i]  ?? 1), 4);
      if ($frate <= 0) $frate = 1;

      // Skip baris tidak valid
      if ($faccount === '' || $famount <= 0 || !in_array($fdk, ['D', 'K'])) continue;

      if ($fdk === 'D') $totalDebit  += $famount;
      else              $totalKredit += $famount;

      $rowsDt[] = [
        // ── Kolom jurnaldt ──
        'fbranchcode'  => $kodeCabang,
        'fjurnaltype'  => $fjurnaltype,
        'faccount'     => $faccount,       // kode akun string (misal: "11400")
        'fsubaccount'  => $fsubaccount,    // kode sub akun string | null
        'fdk'          => $fdk,            // 'D' | 'K'
        'faccountnote' => $fnote,          // keterangan baris
        'frefno'       => $frefno,         // no referensi
        'famount'      => $famount,        // jumlah (currency)
        'famount_rp'   => round($famount * $frate, 2),
        'frate'        => $frate,
        'fusercreate'  => $fuserid,
        'fdatetime'    => $now,
        // fjurnalmtid, fjurnalno, flineno → diisi di dalam transaksi
      ];
    }

    if (empty($rowsDt)) {
      return back()->withInput()->withErrors([
        'detail' => 'Minimal satu baris jurnal valid (Akun, D/K, Jumlah > 0).'
      ]);
    }

    // ── Validasi balance debit = kredit ──
    if (round($totalDebit, 2) !== round($totalKredit, 2)) {
      return back()->withInput()->withErrors([
        'detail' => sprintf(
          'Jurnal tidak balance. Total Debit: Rp %s | Total Kredit: Rp %s',
          number_format($totalDebit,  2, ',', '.'),
          number_format($totalKredit, 2, ',', '.')
        )
      ]);
    }

    // =========================================================
    // 4) TRANSAKSI DB
    // =========================================================
    DB::transaction(function () use (
      $request,
      $fjurnaldate,
      $fjurnaltype,
      $fjurnalnote,
      $kodeCabang,
      $yy,
      $mm,
      $now,
      $fuserid,
      $totalDebit,
      &$rowsDt
    ) {
      // ── 4.1. Generate / ambil fjurnalno ──
      $fjurnalno = trim((string) $request->input('fjurnalno', ''));

      if (empty($fjurnalno)) {
        $prefix  = sprintf('%s.%s.%s.%s.', $fjurnaltype, $kodeCabang, $yy, $mm);
        $lockKey = crc32('JURNAL|' . $fjurnaltype . '|' . $kodeCabang . '|' . $fjurnaldate->format('Y-m'));
        DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

        $lastNo    = DB::table('jurnalmt')
          ->where('fjurnalno', 'like', $prefix . '%')
          ->selectRaw("MAX(CAST(split_part(fjurnalno, '.', 5) AS int)) AS lastno")
          ->value('lastno');

        $fjurnalno = $prefix . str_pad((string) ((int) $lastNo + 1), 4, '0', STR_PAD_LEFT);
      }

      // ── 4.2. INSERT jurnalmt ──
      // Sesuai kolom: fjurnalmtid(serial), fbranchcode, fjurnalno, fjurnaltype,
      //               fjurnaldate, fjurnalnote, fbalance, fbalance_rp,
      //               fdatetime, fuserid, fuserupdate
      $newJurnalMtId = DB::table('jurnalmt')->insertGetId([
        'fbranchcode'  => $kodeCabang,
        'fjurnalno'    => $fjurnalno,
        'fjurnaltype'  => $fjurnaltype,
        'fjurnaldate'  => $fjurnaldate,
        'fjurnalnote'  => $fjurnalnote ?: ('Jurnal ' . $fjurnalno),
        'fbalance'     => $totalDebit,   // total = debit = kredit
        'fbalance_rp'  => $totalDebit,
        'fdatetime'    => $now,
        'fuserid'      => $fuserid,
        'fuserupdate'  => null,
      ], 'fjurnalmtid');

      if (!$newJurnalMtId) {
        throw new \Exception('Gagal menyimpan jurnal header (jurnalmt).');
      }

      // ── 4.3. INSERT jurnaldt ──
      // Sesuai kolom: fjurnalmtid, fbranchcode, fjurnalno, flineno,
      //               faccount, fdk, fsubaccount, frefno,
      //               frate, famount, famount_rp,
      //               faccountnote, fusercreate, fdatetime, fjurnaltype
      $flineno = 1;
      $details = [];

      foreach ($rowsDt as $r) {
        $details[] = [
          'fjurnalmtid'  => $newJurnalMtId,
          'fbranchcode'  => $r['fbranchcode'],
          'fjurnalno'    => $fjurnalno,
          'fjurnaltype'  => $r['fjurnaltype'],
          'flineno'      => $flineno++,
          'faccount'     => $r['faccount'],
          'fsubaccount'  => $r['fsubaccount'],
          'fdk'          => $r['fdk'],
          'faccountnote' => $r['faccountnote'],
          'frefno'       => $r['frefno'],
          'famount'      => $r['famount'],
          'famount_rp'   => $r['famount_rp'],
          'frate'        => $r['frate'],
          'fusercreate'  => $r['fusercreate'],
          'fdatetime'    => $r['fdatetime'],
        ];
      }

      DB::table('jurnaldt')->insert($details);
    });

    return redirect()
      ->route('jurnaltransaksi.index')
      ->with('success', 'Jurnal transaksi berhasil disimpan.');
  }
  public function edit($fstockmtid)
  {
    $supplier = Supplier::all();

    $accounts = DB::table('account')
      ->select('faccid', 'faccount', 'faccname', 'fnonactive')
      ->where('fnonactive', '0')
      ->orderBy('account')
      ->get();

    $subaccounts = DB::table('mssubaccount')
      ->select('fsubaccountid', 'fsubaccountcode', 'fsubaccountname')
      ->where('fnonactive', '0')
      ->orderBy('fsubaccountcode')
      ->get();

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
    $jurnaltransaksi = PenerimaanPembelianHeader::with([
      'details' => function ($query) {
        $query
          ->join('msprd', 'msprd.fprdid', '=', 'trstockdt.fprdcode')
          ->with(['account', 'subaccount']) // Eager load relasi
          ->select(
            'trstockdt.*',
            'msprd.fprdname',
            'msprd.fprdcode as fitemcode_text'
          )
          ->orderBy('trstockdt.fstockdtid', 'asc');
      }
    ])->findOrFail($fstockmtid);

    // 4. Map the data for savedItems (sudah menggunakan data yang benar)
    $savedItems = $jurnaltransaksi->details->map(function ($d) {
      return [
        'uid' => $d->fstockdtid,
        'fitemcode' => $d->fitemcode_text ?? '',
        'fitemname' => $d->fprdname ?? '',
        'fsatuan' => $d->fsatuan ?? '',
        'fprno' => $d->frefpr ?? '-',
        'frefpr' => $d->frefpr ?? null,
        'frefso' => $d->frefso ?? null,
        'fpono' => $d->fpono ?? null,
        'famountponet' => $d->famountponet ?? null,
        'famountpo' => $d->famountpo ?? null,
        'frefdtno' => $d->frefdtno ?? null,
        'fnouref' => $d->fnouref ?? null,
        'fqty' => (float)($d->fqty ?? 0),
        'fterima' => (float)($d->fterima ?? 0),
        'fdisc' => (float)($d->fdiscpersen ?? 0),
        'ftotal' => (float)($d->ftotprice ?? 0),
        'fdesc' => is_array($d->fdesc) ? implode(', ', $d->fdesc) : ($d->fdesc ?? ''),
        'fketdt' => $d->fketdt ?? '',
        'units' => [],

        // TAMBAHKAN INI - untuk JavaScript
        'faccid' => $d->frefdtno ?? null,
        'faccname' => $d->account ? $d->account->faccname : null,
        'fsubaccountid' => $d->frefso ?? null,
        'fsubaccountname' => $d->subaccount ? $d->subaccount->fsubaccountname : null,
      ];
    })->values();

    // Sisa kode Anda sudah benar
    $selectedSupplierCode = $jurnaltransaksi->fsupplier;

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

    return view('jurnaltransaksi.edit', [
      'supplier'           => $supplier,
      'selectedSupplierCode' => $selectedSupplierCode,
      'fcabang'            => $fcabang,
      'accounts'           => $accounts,
      'subaccounts'        => $subaccounts,
      'fbranchcode'        => $fbranchcode,
      'warehouses'         => $warehouses,
      'products'           => $products,
      'productMap'         => $productMap,
      'jurnaltransaksi'    => $jurnaltransaksi,
      'savedItems'         => $savedItems,
      'ppnAmount'          => (float) ($jurnaltransaksi->famountpopajak ?? 0),
      'famountponet'       => (float) ($jurnaltransaksi->famountponet ?? 0),
      'famountpo'          => (float) ($jurnaltransaksi->famountpo ?? 0),
      'action' => 'edit'
    ]);
  }

  public function view($fstockmtid)
  {
    $supplier = Supplier::all();

    $accounts = DB::table('account')
      ->select('faccid', 'faccount', 'faccname', 'fnonactive')
      ->where('fnonactive', '0')
      ->orderBy('account')
      ->get();

    $subaccounts = DB::table('mssubaccount')
      ->select('fsubaccountid', 'fsubaccountcode', 'fsubaccountname')
      ->where('fnonactive', '0')
      ->orderBy('fsubaccountcode')
      ->get();

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
    $jurnaltransaksi = PenerimaanPembelianHeader::with([
      'details' => function ($query) {
        $query
          ->join('msprd', 'msprd.fprdid', '=', 'trstockdt.fprdcode')
          ->with(['account', 'subaccount']) // Eager load relasi
          ->select(
            'trstockdt.*',
            'msprd.fprdname',
            'msprd.fprdcode as fitemcode_text'
          )
          ->orderBy('trstockdt.fstockdtid', 'asc');
      }
    ])->findOrFail($fstockmtid);

    // 4. Map the data for savedItems (sudah menggunakan data yang benar)
    $savedItems = $jurnaltransaksi->details->map(function ($d) {
      return [
        'uid' => $d->fstockdtid,
        'fitemcode' => $d->fitemcode_text ?? '',
        'fitemname' => $d->fprdname ?? '',
        'fsatuan' => $d->fsatuan ?? '',
        'fprno' => $d->frefpr ?? '-',
        'frefpr' => $d->frefpr ?? null,
        'frefso' => $d->frefso ?? null,
        'fpono' => $d->fpono ?? null,
        'famountponet' => $d->famountponet ?? null,
        'famountpo' => $d->famountpo ?? null,
        'frefdtno' => $d->frefdtno ?? null,
        'fnouref' => $d->fnouref ?? null,
        'fqty' => (float)($d->fqty ?? 0),
        'fterima' => (float)($d->fterima ?? 0),
        'fdisc' => (float)($d->fdiscpersen ?? 0),
        'ftotal' => (float)($d->ftotprice ?? 0),
        'fdesc' => is_array($d->fdesc) ? implode(', ', $d->fdesc) : ($d->fdesc ?? ''),
        'fketdt' => $d->fketdt ?? '',
        'units' => [],

        // TAMBAHKAN INI - untuk JavaScript
        'faccid' => $d->frefdtno ?? null,
        'faccname' => $d->account ? $d->account->faccname : null,
        'fsubaccountid' => $d->frefso ?? null,
        'fsubaccountname' => $d->subaccount ? $d->subaccount->fsubaccountname : null,
      ];
    })->values();

    // Sisa kode Anda sudah benar
    $selectedSupplierCode = $jurnaltransaksi->fsupplier;

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

    return view('jurnaltransaksi.view', [
      'supplier'           => $supplier,
      'selectedSupplierCode' => $selectedSupplierCode,
      'fcabang'            => $fcabang,
      'accounts'           => $accounts,
      'subaccounts'        => $subaccounts,
      'fbranchcode'        => $fbranchcode,
      'warehouses'         => $warehouses,
      'products'           => $products,
      'productMap'         => $productMap,
      'jurnaltransaksi'    => $jurnaltransaksi,
      'savedItems'         => $savedItems,
      'ppnAmount'          => (float) ($jurnaltransaksi->famountpopajak ?? 0),
      'famountponet'       => (float) ($jurnaltransaksi->famountponet ?? 0),
      'famountpo'          => (float) ($jurnaltransaksi->famountpo ?? 0),
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
      'ffrom'          => ['nullable', 'integer', 'exists:mswh,fwhid'],
      'fket'           => ['nullable', 'string', 'max:50'],
      'fbranchcode'    => ['nullable', 'string', 'max:20'],
      'fitemcode'      => ['required', 'array', 'min:1'],
      'fitemcode.*'    => ['required', 'string', 'max:50'],
      'fsatuan'        => ['nullable', 'array'],
      'fsatuan.*'      => ['nullable', 'string', 'max:5'],
      'frefdtno'       => ['nullable', 'array'],
      'frefdtno.*'     => ['nullable', 'string', 'max:20'],
      'frefso'        => ['nullable', 'array'],
      'frefso.*'      => ['nullable', 'string', 'max:20'],
      'fnouref'        => ['nullable', 'array'],
      'fnouref.*'      => ['nullable', 'integer'],
      'fdesc'          => ['nullable', 'array'],
      'fdesc.*'        => ['nullable', 'string', 'max:500'],
    ], [
      'fstockmtdate.required' => 'Tanggal transaksi wajib diisi.',
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
    $ffrom        = $request->input('ffrom');
    $fket         = trim((string)$request->input('fket', ''));
    $fbranchcode  = $request->input('fbranchcode');

    $userid       = auth('sysuser')->user()->fsysuserid ?? 'admin';
    $now          = now();

    // =========================
    // 3) DETAIL ARRAYS
    // =========================
    $codes   = $request->input('fitemcode', []);
    $satuans = $request->input('fsatuan', []);
    $refdtno      = $request->input('frefdtno', []);
    $refso      = $request->input('frefso', []);
    $nourefs = $request->input('fnouref', []);
    $qtys    = $request->input('fqty', []);
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
      $rrso  = trim((string)($refso[$i] ?? ''));
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
        'frefso'         => $rrso,
        'fqty'           => $qty,
        'fuserupdate'     => (Auth::user()->fname ?? 'system'),
        'fdatetime'      => $now, // Tetap gunakan fdatetime
        'fketdt'         => '',
        'fcode'          => '0',
        'fnouref'        => $rnour !== null ? (int)$rnour : null,
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

    // =========================
    // 5) TRANSAKSI DB
    // =========================
    DB::transaction(function () use (
      $header,
      $fstockmtid,
      $fstockmtdate,
      $ffrom,
      $fket,
      $fbranchcode,
      $userid,
      $now,
      &$rowsDt,
      $subtotal
    ) {

      // ---- 5.1. Cek Kode Cabang ----
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
      $fstockmtcode = 'PBR';

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

      // ---- 5.2. UPDATE HEADER: trstockmt ----
      $masterData = [
        'fstockmtno'       => $fstockmtno,
        'fstockmtcode'     => $fstockmtcode,
        'fstockmtdate'     => $fstockmtdate,
        'ffrom'            => $ffrom,
        'fket'             => $fket,
        'fuserupdate'     => (Auth::user()->fname ?? 'system'),
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
      ->route('jurnaltransaksi.index')
      ->with('success', "Transaksi {$header->fstockmtno} berhasil diperbarui.");
  }

  public function delete($fstockmtid)
  {
    $supplier = Supplier::all();

    $accounts = DB::table('account')
      ->select('faccid', 'faccount', 'faccname', 'fnonactive')
      ->where('fnonactive', '0')
      ->orderBy('account')
      ->get();

    $subaccounts = DB::table('mssubaccount')
      ->select('fsubaccountid', 'fsubaccountcode', 'fsubaccountname')
      ->where('fnonactive', '0')
      ->orderBy('fsubaccountcode')
      ->get();

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
    $jurnaltransaksi = PenerimaanPembelianHeader::with([
      'details' => function ($query) {
        $query
          ->join('msprd', 'msprd.fprdid', '=', 'trstockdt.fprdcode')
          ->with(['account', 'subaccount']) // Eager load relasi
          ->select(
            'trstockdt.*',
            'msprd.fprdname',
            'msprd.fprdcode as fitemcode_text'
          )
          ->orderBy('trstockdt.fstockdtid', 'asc');
      }
    ])->findOrFail($fstockmtid);

    // 4. Map the data for savedItems (sudah menggunakan data yang benar)
    $savedItems = $jurnaltransaksi->details->map(function ($d) {
      return [
        'uid' => $d->fstockdtid,
        'fitemcode' => $d->fitemcode_text ?? '',
        'fitemname' => $d->fprdname ?? '',
        'fsatuan' => $d->fsatuan ?? '',
        'fprno' => $d->frefpr ?? '-',
        'frefpr' => $d->frefpr ?? null,
        'frefso' => $d->frefso ?? null,
        'fpono' => $d->fpono ?? null,
        'famountponet' => $d->famountponet ?? null,
        'famountpo' => $d->famountpo ?? null,
        'frefdtno' => $d->frefdtno ?? null,
        'fnouref' => $d->fnouref ?? null,
        'fqty' => (float)($d->fqty ?? 0),
        'fterima' => (float)($d->fterima ?? 0),
        'fdisc' => (float)($d->fdiscpersen ?? 0),
        'ftotal' => (float)($d->ftotprice ?? 0),
        'fdesc' => is_array($d->fdesc) ? implode(', ', $d->fdesc) : ($d->fdesc ?? ''),
        'fketdt' => $d->fketdt ?? '',
        'units' => [],

        // TAMBAHKAN INI - untuk JavaScript
        'faccid' => $d->frefdtno ?? null,
        'faccname' => $d->account ? $d->account->faccname : null,
        'fsubaccountid' => $d->frefso ?? null,
        'fsubaccountname' => $d->subaccount ? $d->subaccount->fsubaccountname : null,
      ];
    })->values();

    // Sisa kode Anda sudah benar
    $selectedSupplierCode = $jurnaltransaksi->fsupplier;

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

    return view('jurnaltransaksi.edit', [
      'supplier'           => $supplier,
      'selectedSupplierCode' => $selectedSupplierCode,
      'fcabang'            => $fcabang,
      'accounts'           => $accounts,
      'subaccounts'        => $subaccounts,
      'fbranchcode'        => $fbranchcode,
      'warehouses'         => $warehouses,
      'products'           => $products,
      'productMap'         => $productMap,
      'jurnaltransaksi'    => $jurnaltransaksi,
      'savedItems'         => $savedItems,
      'ppnAmount'          => (float) ($jurnaltransaksi->famountpopajak ?? 0),
      'famountponet'       => (float) ($jurnaltransaksi->famountponet ?? 0),
      'famountpo'          => (float) ($jurnaltransaksi->famountpo ?? 0),
      'action' => 'delete'
    ]);
  }

  public function destroy($fstockmtid)
  {
    try {
      $jurnaltransaksi = PenerimaanPembelianHeader::findOrFail($fstockmtid);
      $jurnaltransaksi->details()->delete();

      // 2. Baru hapus header
      $jurnaltransaksi->delete();
      return redirect()->route('jurnaltransaksi.index')->with('success', 'Data jurnaltransaksi ' . $jurnaltransaksi->fpono . ' berhasil dihapus.');
    } catch (\Exception $e) {
      // Jika terjadi kesalahan saat menghapus, kembali ke halaman delete dengan pesan error
      return redirect()->route('jurnaltransaksi.delete', $fstockmtid)->with('error', 'Gagal menghapus data: ' . $e->getMessage());
    }
  }
}
