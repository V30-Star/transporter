<?php

namespace App\Http\Controllers;

use App\Models\PenerimaanPembelianDetail;
use App\Models\PenerimaanPembelianHeader;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Tr_pod;
use App\Models\Tr_poh;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // Pastikan ini ada jika menggunakan throw new \Exception
use Illuminate\Support\Facades\Log; // sekalian biar aman untuk tanggal

class PemakaianbarangController extends Controller
{
    public function index(Request $request)
    {
        // --- 1. PERBAIKAN PERMISSIONS ---
        // Saya asumsikan ini nama permission yang benar untuk modul ini
        $canCreate = in_array('createPemakaianbarang', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updatePemakaianBarang', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deletePemakaianBarang', explode(',', session('user_restricted_permissions', '')));
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
            $data = $records->map(function ($row) {

                $actions = '';

                // --- Tombol view ---
                // if ($canView) {
                // Asumsi route edit Anda: pemakaianbarang.edit
                $viewUrl = route('pemakaianbarang.view', $row->fstockmtid);
                $actions .= ' <a href="'.$viewUrl.'" class="inline-flex items-center bg-slate-500 text-white px-4 py-2 rounded hover:bg-slate-600">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg> View
                                </a>';
                // }

                // --- Tombol Edit ---
                // if ($canEdit) {
                // Asumsi route edit Anda: pemakaianbarang.edit
                $editUrl = route('pemakaianbarang.edit', $row->fstockmtid);
                $actions .= ' <a href="'.$editUrl.'" class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg> Edit
                                </a>';
                // }

                // --- Tombol Delete ---
                // if ($canDelete) {
                // Asumsi route destroy Anda: pemakaianbarang.destroy
                $deleteUrl = route('pemakaianbarang.delete', $row->fstockmtid);
                $actions .= '<a href="'.$deleteUrl.'">
                <button class="inline-flex items-center bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Hapus
                </button>
            </a>';
                // }

                return [
                    'fstockmtno' => $row->fstockmtno,
                    // Format tanggal agar rapi di tabel
                    'fstockmtdate' => Carbon::parse($row->fstockmtdate)->format('d/m/Y'),
                    'actions' => $actions,
                ];
            });

            // 9. Kirim Response JSON
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        }

        // --- 3. Handle Request non-AJAX (Saat load halaman) ---
        return view('pemakaianbarang.index', compact(
            'canCreate',
            'canEdit',
            'canDelete',
            'showActionsColumn'
        ));
    }

    public function pickable(Request $request)
    {
        $search = trim($request->get('search', ''));
        $perPage = (int) ($request->get('per_page', 10));
        $perPage = $perPage > 0 ? $perPage : 10;

        $q = \App\Models\Tr_poh::query()
            ->select([
                'fpohid as fprhid',     // FE expects fprhid
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

        $page = (int) $request->get('page', 1);
        $data = $q->paginate($perPage, ['*'], 'page', $page);

        // Kembalikan struktur yang sudah diantisipasi FE-mu (data, current_page, last_page, total)
        return response()->json([
            'data' => $data->items(),
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'total' => $data->total(),
        ]);
    }

    public function items($id)
    {
        // Langkah ini sudah benar: mendapatkan header berdasarkan Primary Key (ID)
        $header = Tr_poh::where('fpohid', $id)->firstOrFail();

        // Mengambil detail dari tr_pod
        $items = DB::table('tr_pod')
          // Detail PO sekarang dihubungkan lewat fpono
            ->where('tr_pod.fpono', $header->fpono)

          // PERBAIKAN JOIN: tr_pod.fprdcode (sekarang integer) di-join ke msprd.fprdid (integer)
            ->leftJoin('msprd as m', 'm.fprdid', '=', 'tr_pod.fprdcode')
            ->select([
                DB::raw("COALESCE(NULLIF(tr_pod.frefdtno, ''), tr_pod.fpodid::text) as frefdtno"),
                'tr_pod.fprdcode as fitemid', // <-- ID (integer) dari tr_pod
                'm.fprdcode as fitemcode', // <-- Ambil kode string dari master produk
                'm.fprdname as fitemname', // <-- Mengambil fprdname dari tabel msprd
                'tr_pod.fqty',
                'tr_pod.fsatuan as fsatuan',
                'tr_pod.fpono',
                'tr_pod.fprice as fharga',
                DB::raw("COALESCE(NULLIF(regexp_replace(COALESCE(tr_pod.fdisc, ''), '[^0-9\\.]', '', 'g'), '')::numeric, 0) as fdiskon"),
            ])
            ->orderBy('m.fprdcode') // Urutkan berdasarkan kode produk string
            ->get();

        // Mengembalikan data dalam format JSON
        return response()->json([
            'header' => [
                'fprhid' => $header->fpohid,
                'fprno' => $header->fpono,
                'fsupplier' => trim($header->fsupplier ?? ''),
                'fprdate' => optional($header->fpodate)->format('Y-m-d H-i-s'),
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
            $needle = trim((string) $branch);
            if (is_numeric($needle)) {
                $kodeCabang = DB::table('mscabang')->where('fcabangid', (int) $needle)->value('fcabangkode');
            } else {
                $kodeCabang = DB::table('mscabang')->whereRaw('LOWER(fcabangkode)=LOWER(?)', [$needle])->value('fcabangkode')
                  ?: DB::table('mscabang')->whereRaw('LOWER(fcabangname)=LOWER(?)', [$needle])->value('fcabangkode');
            }
        }
        if (! $kodeCabang) {
            $kodeCabang = 'NA';
        }

        $prefix = sprintf('PBR.%s.%s.%s.', $kodeCabang, $date->format('y'), $date->format('m'));

        // kunci per (branch, tahun-bulan) — TANPA bikin tabel baru
        $lockKey = crc32('PBR|'.$kodeCabang.'|'.$date->format('Y-m'));
        DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

        $last = DB::table('tr_poh')
            ->where('fpono', 'like', $prefix.'%')
            ->selectRaw("MAX(CAST(split_part(fpono, '.', 5) AS int)) AS lastno")
            ->value('lastno');

        $next = (int) $last + 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function print(string $fstockmtno)
    {
        $supplierSub = Supplier::select('fsuppliercode', 'fsuppliername');

        $hdr = PenerimaanPembelianHeader::query()
            ->leftJoinSub($supplierSub, 's', function ($join) {
                $join->on('s.fsuppliercode', '=', 'trstockmt.fsupplier');
            })
            ->leftJoin('mscabang as c', 'c.fcabangkode', '=', 'trstockmt.fbranchcode')
            ->leftJoin('mswh as w', 'w.fwhcode', '=', 'trstockmt.ffrom')
            ->where('trstockmt.fstockmtno', $fstockmtno)
            ->first([
                'trstockmt.*',
                's.fsuppliername as supplier_name',
                'c.fcabangname as cabang_name',
                'w.fwhname as fwhnamen',
            ]);

        if (! $hdr) {
            return redirect()->back()->with('error', 'Pemakaian Barang tidak ditemukan.');
        }

        $dt = PenerimaanPembelianDetail::query()
            ->leftJoin('msprd as p', 'p.fprdid', '=', 'trstockdt.fprdcodeid')
            ->where('trstockdt.fstockmtno', $fstockmtno)
            ->orderBy('trstockdt.fstockdtid')
            ->get([
                'trstockdt.*',
                'p.fprdname as product_name',
                'p.fprdcode as product_code',
                'p.fminstock as stock',
                'trstockdt.fqtyremain',
            ]);

        $fmt = fn ($d) => $d
          ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d F Y')
          : '-';

        return view('pemakaianbarang.print', [
            'hdr' => $hdr,
            'dt' => $dt,
            'fmt' => $fmt,
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
            ->where('fhavesubaccount', '1')
            ->orderBy('account')
            ->get();

        $subaccounts = DB::table('mssubaccount')
            ->select('fsubaccountid', 'fsubaccountcode', 'fsubaccountname')
            ->where('fnonactive', '0')
            ->orderBy('fsubaccountcode')
            ->get();

        $warehouses = DB::table('mswh')
            ->select('fwhid', 'fwhcode', 'fwhname', 'fbranchcode', 'fnonactive')
            ->where('fnonactive', '0')              // hanya yang aktif
            ->orderBy('fwhcode')
            ->get();

        $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

        $branch = DB::table('mscabang')
            ->when(is_numeric($raw), fn ($q) => $q->where('fcabangid', (int) $raw))
            ->when(
                ! is_numeric($raw),
                fn ($q) => $q->where('fcabangkode', $raw)->orWhere('fcabangname', $raw)
            )
            ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

        $canApproval = in_array('approvalpr', explode(',', session('user_restricted_permissions', '')));

        $fcabang = $branch->fcabangname ?? (string) $raw;
        $fbranchcode = $branch->fcabangkode ?? (string) $raw;

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

        return view('pemakaianbarang.create', [
            'newtr_prh_code' => $newtr_prh_code,
            'warehouses' => $warehouses,
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
        // =========================
        // 1) VALIDASI INPUT
        // =========================
        $request->validate([
            'fstockmtno' => ['nullable', 'string', 'max:100'],
            'fstockmtdate' => ['required', 'date'],
            'ffrom' => ['nullable', 'string', 'max:10'],
            'fket' => ['nullable', 'string', 'max:50'],
            'fbranchcode' => ['nullable', 'string', 'max:20'],

            'fitemcode' => ['required', 'array', 'min:1'],
            'fitemcode.*' => ['required', 'string', 'max:50'],

            'fsatuan' => ['nullable', 'array'],
            'fsatuan.*' => ['nullable', 'string', 'max:20'],

            'frefdtno' => ['nullable', 'array'],
            'frefdtno.*' => ['nullable', 'string', 'max:20'],

            'frefso' => ['nullable', 'array'],
            'frefso.*' => ['nullable', 'string', 'max:20'],

            'fqty' => ['required', 'array'],
            'fqty.*' => ['numeric', 'min:0'],

            'fdesc' => ['nullable', 'array'],
            'fdesc.*' => ['nullable', 'string', 'max:500'],

            'fcurrency' => ['nullable', 'string', 'max:5'],
            'frate' => ['nullable', 'numeric', 'min:0'],
            'famountpopajak' => ['nullable', 'numeric', 'min:0'], // PPN nominal
        ], [
            'fstockmtdate.required' => 'Tanggal transaksi wajib diisi.',
            'fsupplier.required' => 'Supplier wajib diisi.',
            'fitemcode.required' => 'Minimal 1 item.',
            'fsatuan.*.max' => 'Satuan di salah satu baris tidak boleh lebih dari 5 karakter.',
        ]);

        // =========================
        // 2) HEADER FIELDS
        // =========================
        $fstockmtno = trim((string) $request->input('fstockmtno'));
        $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
        $ffrom = $request->input('ffrom');
        $fket = trim((string) $request->input('fket', ''));
        $fbranchcode = $request->input('fbranchcode');

        $userid = auth('sysuser')->user()->fsysuserid ?? 'admin';
        $now = now();

        // =========================
        // 3) & 4) DETAIL ARRAYS & RAKIT DETAIL + HITUNG SUBTOTAL
        // =========================
        $codes = $request->input('fitemcode', []);
        $satuans = $request->input('fsatuan', []);
        $accountCodes = $request->input('frefdtno', []);
        $subAccountCodes = $request->input('frefso', []);
        $qtys = $request->input('fqty', []);
        $descs = $request->input('fdesc', []);

        $rowsDt = [];
        $subtotal = 0.0;
        $rowCount = count($codes);
        $usedNoAcaks = [];

        // Ambil referensi master produk untuk fallback satuan
        $uniqueCodes = array_values(array_unique(array_filter(array_map(fn ($c) => trim((string) $c), $codes))));
        $prodMeta = DB::table('msprd')
            ->whereIn('fprdcode', $uniqueCodes)
            ->get(['fprdid', 'fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'])
            ->keyBy('fprdcode');

        $pickDefaultSat = function ($meta) {
            if (! $meta) {
                return '';
            }
            foreach (['fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'] as $k) {
                $v = trim((string) ($meta->$k ?? ''));
                if ($v !== '') {
                    return mb_substr($v, 0, 5);
                }
            }

            return '';
        };

        for ($i = 0; $i < $rowCount; $i++) {
            $code = trim((string) ($codes[$i] ?? ''));
            $sat = trim((string) ($satuans[$i] ?? ''));
            $accountCode = trim((string) ($accountCodes[$i] ?? ''));
            $subAccountCode = trim((string) ($subAccountCodes[$i] ?? ''));
            $rnour = $nourefs[$i] ?? null;
            $qty = (float) ($qtys[$i] ?? 0);
            $desc = (string) ($descs[$i] ?? '');

            if ($code === '' || $qty <= 0) {
                continue;
            }

            $produk = DB::table('msprd')
                ->where('fprdcode', $code)
                ->select('fprdid', 'fsatuanbesar', 'fqtykecil as rasio_konversi')
                ->first();

            $itemeId = $produk ? $produk->fprdid : $itemeId;

            $qtyKecil = $qty;
            if ($produk && $sat === $produk->fsatuanbesar) {
                $qtyKecil = $qty * (float) $produk->rasio_konversi;
            }

            $meta = $prodMeta[$code] ?? null;
            if (! $meta) {
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

            $rowsDt[] = [
                'fprdcode' => $code,
                'fprdcodeid' => $prdId,
                'frefdtno' => $accountCode !== '' ? $accountCode : null,
                'frefso' => $subAccountCode !== '' ? $subAccountCode : null,
                'fnoacak' => $this->normalizeRandomNumber(null, $usedNoAcaks),
                'fqty' => $qty,
                'fusercreate' => (Auth::user()->fname ?? 'system'),
                'fdatetime' => $now,
                'fketdt' => '',
                'fcode' => '0',
                'fdesc' => $desc,
                'fsatuan' => $sat,
                'fclosedt' => '0',
                'fdiscpersen' => 0,
                'fbiaya' => 0,
                'fqtykecil' => $qtyKecil,
                'fqtyremain' => $qtyKecil,
            ];
        }

        if (empty($rowsDt)) {
            return back()->withInput()->withErrors([
                'detail' => 'Minimal satu item valid (Kode, Satuan, Qty > 0).',
            ]);
        }

        // =========================
        // 5) TRANSAKSI DB
        // =========================
        DB::transaction(function () use (
            $fstockmtdate,
            $ffrom,
            $fket,
            $fbranchcode,
            $now,
            &$fstockmtno,
            &$rowsDt

        ) {
            // ---- 5.1. Generate fstockmtno dan kodeCabang ----
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
            if (! $kodeCabang) {
                $kodeCabang = 'NA';
            }

            $yy = $fstockmtdate->format('y');
            $mm = $fstockmtdate->format('m');
            $fstockmtcode = 'PBR';

            if (empty($fstockmtno)) {
                $prefix = sprintf('%s.%s.%s.%s.', $fstockmtcode, $kodeCabang, $yy, $mm);

                $lockKey = crc32('STOCKMT|'.$fstockmtcode.'|'.$kodeCabang.'|'.$fstockmtdate->format('Y-m'));
                DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

                $last = DB::table('trstockmt')
                    ->where('fstockmtno', 'like', $prefix.'%')
                    ->selectRaw("MAX(CAST(split_part(fstockmtno, '.', 5) AS int)) AS lastno")
                    ->value('lastno');

                $next = (int) $last + 1;
                $fstockmtno = $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            }

            // ---- 5.2. INSERT HEADER & DETAIL INVENTORY (trstockmt & trstockdt) ----
            $masterData = [
                'fstockmtno' => $fstockmtno,
                'fstockmtcode' => $fstockmtcode,
                'fstockmtdate' => $fstockmtdate,
                'fprdout' => '0',
                'frefno' => null, // Diisi jika ada referensi lain
                'frefpo' => null,
                'ftrancode' => null,
                'ffrom' => $ffrom,
                'fto' => null,
                'fkirim' => null,
                'fprdjadi' => null,
                'fqtyjadi' => null,
                'fket' => $fket,
                'fusercreate' => (Auth::user()->fname ?? 'system'),
                'fdatetime' => $now,
                'fsalesman' => null,
                'fjatuhtempo' => null,
                'fprint' => 0,
                'fsudahtagih' => '0',
                'fbranchcode' => $kodeCabang,
                'fdiscount' => 0,
            ];

            $newStockMasterId = DB::table('trstockmt')->insertGetId($masterData, 'fstockmtid');

            if (! $newStockMasterId) {
                throw new Exception('Gagal menyimpan data master (header).');
            }

            foreach ($rowsDt as &$r) {
                $r['fstockmtcode'] = $fstockmtcode;
                $r['fstockmtno'] = $fstockmtno;

            }
            unset($r);

            DB::table('trstockdt')->insert($rowsDt);

            // =================================================================
            // ---- 5.3. AKUNTANSI JURNAL (jurnalmt & jurnaldt) ----
            // =================================================================

            // 1. Definisikan Kode Akun (GANTI DENGAN KODE AKUN GL ASLI ANDA)
            $INVENTORY_ACCOUNT_CODE = '11400'; // Contoh: Persediaan Barang Dagang

            // 2. Generate Nomor Jurnal
            $fjurnaltype = 'JV';
            $jurnalPrefix = sprintf('%s.%s.%s.%s.', $fjurnaltype, $kodeCabang, $yy, $mm);

            $jurnalLockKey = crc32('JURNAL|'.$fjurnaltype.'|'.$kodeCabang.'|'.$fstockmtdate->format('Y-m'));
            DB::statement('SELECT pg_advisory_xact_lock(?)', [$jurnalLockKey]);

            $lastJurnalNo = DB::table('jurnalmt')
                ->where('fjurnalno', 'like', $jurnalPrefix.'%')
                ->selectRaw("MAX(CAST(split_part(fjurnalno, '.', 5) AS int)) AS lastno")
                ->value('lastno');

            $nextJurnalNo = (int) $lastJurnalNo + 1;
            $fjurnalno = $jurnalPrefix.str_pad((string) $nextJurnalNo, 4, '0', STR_PAD_LEFT);

            // 3. INSERT JURNAL HEADER (jurnalmt)
            $jurnalHeader = [
                'fbranchcode' => $kodeCabang,
                'fjurnalno' => $fjurnalno,
                'fjurnaltype' => $fjurnaltype,
                'fjurnaldate' => $fstockmtdate,
                'fjurnalnote' => 'Jurnal Penerimaan Barang '.$fstockmtno,
                'fdatetime' => $now,
                'fuserid' => (Auth::user()->fname ?? 'system'),
            ];

            Log::debug('JURNAL HEADER INSERT:', $jurnalHeader); // Debugging

            $newJurnalMasterId = DB::table('jurnalmt')->insertGetId($jurnalHeader, 'fjurnalmtid');

            if (! $newJurnalMasterId) {
                throw new Exception('Gagal menyimpan data jurnal header.');
            }

            // 4. INSERT JURNAL DETAIL (jurnaldt)
            $jurnalDetails = [];
            $flineno = 1;

            // --- DEBIT: Persediaan (Nilai Subtotal) ---
            $jurnalDetails[] = [
                'fjurnalmtid' => $newJurnalMasterId,
                'fbranchcode' => $kodeCabang,
                'fjurnaltype' => $fjurnaltype,
                'fjurnalno' => $fjurnalno,
                'flineno' => $flineno++,
                'faccount' => $INVENTORY_ACCOUNT_CODE,
                'fdk' => 'D',
                'frefno' => $fstockmtno,
                'faccountnote' => 'Persediaan Barang Dagang '.$fstockmtno,
                'fusercreate' => (Auth::user()->fname ?? 'system'),
                'fdatetime' => $now,
            ];

            DB::table('jurnaldt')->insert($jurnalDetails);
        });

        // =================================================================

        return redirect()
            ->route('pemakaianbarang.create')
            ->with('success', "Transaksi {$fstockmtno} tersimpan.");
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
            ->when(is_numeric($raw), fn ($q) => $q->where('fcabangid', (int) $raw))
            ->when(! is_numeric($raw), fn ($q) => $q
                ->where('fcabangkode', $raw)
                ->orWhere('fcabangname', $raw))
            ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

        $warehouses = DB::table('mswh')
            ->select('fwhid', 'fwhcode', 'fwhname', 'fbranchcode', 'fnonactive')
            ->where('fnonactive', '0') // hanya yang aktif
            ->orderBy('fwhcode')
            ->get();

        $fcabang = $branch->fcabangname ?? (string) $raw;
        $fbranchcode = $branch->fcabangkode ?? (string) $raw;

        // 1. Ambil data Header (trstockmt) DAN relasi Details (trstockdt)
        // Biarkan query ini. Sekarang $fstockmtid di sini adalah integer (misal: 8)
        $pemakaianbarang = PenerimaanPembelianHeader::with([
            'details' => function ($query) {
                $query
                    ->leftJoin('msprd', 'msprd.fprdid', '=', 'trstockdt.fprdcodeid')
                    ->leftJoin('account', function ($join) {
                        $join->on(DB::raw('TRIM(account.faccount)'), '=', DB::raw('TRIM(trstockdt.frefdtno)'));
                    })
                    ->leftJoin('mssubaccount', function ($join) {
                        $join->on(DB::raw('TRIM(mssubaccount.fsubaccountcode)'), '=', DB::raw('TRIM(trstockdt.frefso)'));
                    })
                    ->select(
                        'trstockdt.*',
                        'msprd.fprdname',
                        'msprd.fprdcode as fitemcode_text',
                        'account.faccname as account_name_text',
                        'mssubaccount.fsubaccountname as subaccount_name_text'
                    )
                    ->orderBy('trstockdt.fstockdtid', 'asc');
            },
        ])->findOrFail($fstockmtid);

        $usageLockMessage = $this->getUsageLockMessage($pemakaianbarang);

        // 4. Map the data for savedItems (sudah menggunakan data yang benar)
        $savedItems = $pemakaianbarang->details->map(function ($d) {
            $accountCode = trim((string) ($d->frefdtno ?? '')) ?: null;
            $subaccountCode = trim((string) ($d->frefso ?? '')) ?: null;

            return [
                'uid' => $d->fstockdtid,
                'fitemid' => $d->fprdcodeid,
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
                'fqty' => (float) ($d->fqty ?? 0),
                'fterima' => (float) ($d->fterima ?? 0),
                'fdisc' => (float) ($d->fdiscpersen ?? 0),
                'ftotal' => (float) ($d->ftotprice ?? 0),
                'fdesc' => is_array($d->fdesc) ? implode(', ', $d->fdesc) : ($d->fdesc ?? ''),
                'fketdt' => $d->fketdt ?? '',
                'units' => [],

                'account_code' => $accountCode,
                'account_name' => $d->account_name_text ?? null,
                'account_label' => $accountCode
                  ? trim($accountCode.' - '.($d->account_name_text ?? $accountCode))
                  : null,
                'subaccount_code' => $subaccountCode,
                'subaccount_name' => $d->subaccount_name_text ?? null,
                'subaccount_label' => $subaccountCode
                  ? trim($subaccountCode.' - '.($d->subaccount_name_text ?? $subaccountCode))
                  : null,
            ];
        })->values();

        // Sisa kode Anda sudah benar
        $selectedSupplierCode = $pemakaianbarang->fsupplier;

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
                    'name' => $p->fprdname,
                    'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
                    'stock' => $p->fminstock ?? 0,
                ],
            ];
        })->toArray();

        return view('pemakaianbarang.edit', [
            'supplier' => $supplier,
            'selectedSupplierCode' => $selectedSupplierCode,
            'fcabang' => $fcabang,
            'accounts' => $accounts,
            'subaccounts' => $subaccounts,
            'fbranchcode' => $fbranchcode,
            'warehouses' => $warehouses,
            'products' => $products,
            'productMap' => $productMap,
            'pemakaianbarang' => $pemakaianbarang,
            'savedItems' => $savedItems,
            'ppnAmount' => (float) ($pemakaianbarang->famountpopajak ?? 0),
            'famountponet' => (float) ($pemakaianbarang->famountponet ?? 0),
            'famountpo' => (float) ($pemakaianbarang->famountpo ?? 0),
            'isUsageLocked' => ! empty($usageLockMessage),
            'usageLockMessage' => $usageLockMessage,
            'action' => 'edit',
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
            ->when(is_numeric($raw), fn ($q) => $q->where('fcabangid', (int) $raw))
            ->when(! is_numeric($raw), fn ($q) => $q
                ->where('fcabangkode', $raw)
                ->orWhere('fcabangname', $raw))
            ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

        $warehouses = DB::table('mswh')
            ->select('fwhid', 'fwhcode', 'fwhname', 'fbranchcode', 'fnonactive')
            ->where('fnonactive', '0') // hanya yang aktif
            ->orderBy('fwhcode')
            ->get();

        $fcabang = $branch->fcabangname ?? (string) $raw;
        $fbranchcode = $branch->fcabangkode ?? (string) $raw;

        // 1. Ambil data Header (trstockmt) DAN relasi Details (trstockdt)
        // Biarkan query ini. Sekarang $fstockmtid di sini adalah integer (misal: 8)
        $pemakaianbarang = PenerimaanPembelianHeader::with([
            'details' => function ($query) {
                $query
                    ->join('msprd', 'msprd.fprdid', '=', 'trstockdt.fprdcodeid')
                    ->leftJoin('account', function ($join) {
                        $join->on(DB::raw('TRIM(account.faccount)'), '=', DB::raw('TRIM(trstockdt.frefdtno)'));
                    })
                    ->leftJoin('mssubaccount', function ($join) {
                        $join->on(DB::raw('TRIM(mssubaccount.fsubaccountcode)'), '=', DB::raw('TRIM(trstockdt.frefso)'));
                    })
                    ->select(
                        'trstockdt.*',
                        'msprd.fprdname',
                        'msprd.fprdcode as fitemcode_text',
                        'account.faccname as account_name_text',
                        'mssubaccount.fsubaccountname as subaccount_name_text'
                    )
                    ->orderBy('trstockdt.fstockdtid', 'asc');
            },
        ])->findOrFail($fstockmtid);

        // 4. Map the data for savedItems (sudah menggunakan data yang benar)
        $savedItems = $pemakaianbarang->details->map(function ($d) {
            $accountCode = trim((string) ($d->frefdtno ?? '')) ?: null;
            $subaccountCode = trim((string) ($d->frefso ?? '')) ?: null;

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
                'fqty' => (float) ($d->fqty ?? 0),
                'fterima' => (float) ($d->fterima ?? 0),
                'fdisc' => (float) ($d->fdiscpersen ?? 0),
                'ftotal' => (float) ($d->ftotprice ?? 0),
                'fdesc' => is_array($d->fdesc) ? implode(', ', $d->fdesc) : ($d->fdesc ?? ''),
                'fketdt' => $d->fketdt ?? '',
                'units' => [],

                // TAMBAHKAN INI - untuk JavaScript
                'account_code' => $accountCode,
                'account_name' => $d->account_name_text ?? null,
                'account_label' => $accountCode
                  ? trim($accountCode.' - '.($d->account_name_text ?? $accountCode))
                  : null,
                'subaccount_code' => $subaccountCode,
                'subaccount_name' => $d->subaccount_name_text ?? null,
                'subaccount_label' => $subaccountCode
                  ? trim($subaccountCode.' - '.($d->subaccount_name_text ?? $subaccountCode))
                  : null,
            ];
        })->values();

        // Sisa kode Anda sudah benar
        $selectedSupplierCode = $pemakaianbarang->fsupplier;

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
                    'name' => $p->fprdname,
                    'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
                    'stock' => $p->fminstock ?? 0,
                ],
            ];
        })->toArray();

        return view('pemakaianbarang.view', [
            'supplier' => $supplier,
            'selectedSupplierCode' => $selectedSupplierCode,
            'fcabang' => $fcabang,
            'accounts' => $accounts,
            'subaccounts' => $subaccounts,
            'fbranchcode' => $fbranchcode,
            'warehouses' => $warehouses,
            'products' => $products,
            'productMap' => $productMap,
            'pemakaianbarang' => $pemakaianbarang,
            'savedItems' => $savedItems,
            'ppnAmount' => (float) ($pemakaianbarang->famountpopajak ?? 0),
            'famountponet' => (float) ($pemakaianbarang->famountponet ?? 0),
            'famountpo' => (float) ($pemakaianbarang->famountpo ?? 0),
        ]);
    }

    public function update(Request $request, $fstockmtid)
    {
        // =========================
        // 1) VALIDASI INPUT
        // =========================
        $request->validate([
            'fstockmtno' => ['nullable', 'string', 'max:100'],
            'fstockmtdate' => ['required', 'date'],
            'ffrom' => ['nullable', 'string', 'max:10'],
            'fket' => ['nullable', 'string', 'max:50'],
            'fbranchcode' => ['nullable', 'string', 'max:20'],
            'fitemcode' => ['required', 'array', 'min:1'],
            'fitemcode.*' => ['required', 'string', 'max:50'],
            'fsatuan' => ['nullable', 'array'],
            'fsatuan.*' => ['nullable', 'string', 'max:20'],
            'frefdtno' => ['nullable', 'array'],
            'frefdtno.*' => ['nullable', 'string', 'max:20'],
            'frefso' => ['nullable', 'array'],
            'frefso.*' => ['nullable', 'string', 'max:20'],
            'fdesc' => ['nullable', 'array'],
            'fdesc.*' => ['nullable', 'string', 'max:500'],
        ], [
            'fstockmtdate.required' => 'Tanggal transaksi wajib diisi.',
            'fitemcode.required' => 'Minimal 1 item.',
            'fqty.*.min' => 'Qty tidak boleh 0.',
            'ffrom.max' => 'Gudang tidak boleh lebih dari 10 karakter.',
        ]);

        // =========================
        // 2) AMBIL DATA MASTER & HEADER
        // =========================
        $header = PenerimaanPembelianHeader::findOrFail($fstockmtid);
        if ($message = $this->getUsageLockMessage($header)) {
            return redirect()->route('pemakaianbarang.index')->with('error', $message);
        }

        $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
        $ffrom = $request->input('ffrom');
        $fket = trim((string) $request->input('fket', ''));
        $fbranchcode = $request->input('fbranchcode');

        $userid = auth('sysuser')->user()->fsysuserid ?? 'admin';
        $now = now();

        // =========================
        // 3) DETAIL ARRAYS
        // =========================
        $codes = $request->input('fitemcode', []);
        $satuans = $request->input('fsatuan', []);
        $accountCodes = $request->input('frefdtno', []);
        $subAccountCodes = $request->input('frefso', []);
        $qtys = $request->input('fqty', []);
        $descs = $request->input('fdesc', []);

        // =========================
        // 4) LOGIC PROD META & RAKIT DETAIL
        // =========================
        $uniqueCodes = array_values(array_unique(array_filter(array_map(fn ($c) => trim((string) $c), $codes))));
        $prodMeta = DB::table('msprd')
            ->whereIn('fprdcode', $uniqueCodes)
            ->get(['fprdid', 'fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'])
            ->keyBy('fprdcode');

        $pickDefaultSat = function (?object $meta): string {
            if (! $meta) {
                return '';
            }
            foreach (['fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'] as $k) {
                $v = trim((string) ($meta->$k ?? ''));
                if ($v !== '') {
                    return mb_substr($v, 0, 5);
                }
            }

            return '';
        };

        $rowsDt = [];
        $subtotal = 0.0;
        $rowCount = count($codes);
        $usedNoAcaks = [];

        for ($i = 0; $i < $rowCount; $i++) {
            $code = trim((string) ($codes[$i] ?? ''));
            $sat = trim((string) ($satuans[$i] ?? ''));
            $accountCode = trim((string) ($accountCodes[$i] ?? ''));
            $subAccountCode = trim((string) ($subAccountCodes[$i] ?? ''));
            $rnour = $nourefs[$i] ?? null;
            $qty = (float) ($qtys[$i] ?? 0);
            $price = (float) ($prices[$i] ?? 0);
            $desc = (string) ($descs[$i] ?? '');

            if ($code === '' || $qty <= 0) {
                continue;
            }

            $produk = DB::table('msprd')
                ->where('fprdcode', $code)
                ->select('fprdid', 'fsatuanbesar', 'fqtykecil as rasio_konversi')
                ->first();

            $itemeId = $produk ? $produk->fprdid : $itemeId;

            $qtyKecil = $qty;
            if ($produk && $sat === $produk->fsatuanbesar) {
                $qtyKecil = $qty * (float) $produk->rasio_konversi;
            }

            $meta = $prodMeta[$code] ?? null;
            if (! $meta) {
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

            $amount = $qty * $price;
            $subtotal += $amount;

            $rowsDt[] = [
                'fprdcode' => $code,
                'fprdcodeid' => $prdId,
                'frefdtno' => $accountCode !== '' ? $accountCode : null,
                'frefso' => $subAccountCode !== '' ? $subAccountCode : null,
                'fnoacak' => $this->normalizeRandomNumber(null, $usedNoAcaks),
                'fqty' => $qty,
                'fuserupdate' => (Auth::user()->fname ?? 'system'),
                'fdatetime' => $now, // Tetap gunakan fdatetime
                'fketdt' => '',
                'fcode' => '0',
                'fdesc' => $desc,
                'fsatuan' => $sat,
                'fclosedt' => '0',
                'fdiscpersen' => 0,
                'fbiaya' => 0,
                'fqtykecil' => $qtyKecil,
                'fqtyremain' => $qtyKecil,
            ];
        }

        if (empty($rowsDt)) {
            return back()->withInput()->withErrors([
                'detail' => 'Minimal satu item valid (Kode, Satuan, Qty > 0).',
            ]);
        }

        // =========================
        // 5) TRANSAKSI DB
        // =========================
        DB::transaction(function () use (
            $header,
            $fstockmtdate,
            $ffrom,
            $fket,
            $fbranchcode,
            &$rowsDt

        ) {

            // ---- 5.1. Cek Kode Cabang ----
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
            if (! $kodeCabang) {
                $kodeCabang = 'NA';
            }

            $fstockmtno = $header->fstockmtno;
            $fstockmtcode = $header->fstockmtcode;

            // ---- 5.2. UPDATE HEADER: trstockmt ----
            $masterData = [
                'fstockmtno' => $fstockmtno,
                'fstockmtcode' => $fstockmtcode,
                'fstockmtdate' => $fstockmtdate,
                'ffrom' => $ffrom,
                'fket' => $fket,
                'fuserupdate' => (Auth::user()->fname ?? 'system'),
                'fbranchcode' => $kodeCabang,
            ];

            $header->update($masterData);

            // ---- 5.3. HAPUS DETAIL LAMA ----
            DB::table('trstockdt')->where('fstockmtno', $header->fstockmtno)->delete();

            // ---- 5.4. INSERT DETAIL BARU ----
            $fstockmtcode = $header->fstockmtcode;
            $fstockmtno = $header->fstockmtno;
            $nextNouRef = 1;

            foreach ($rowsDt as &$r) {
                $r['fstockmtcode'] = $fstockmtcode;
                $r['fstockmtno'] = $fstockmtno;

            }
            unset($r);

            DB::table('trstockdt')->insert($rowsDt);
        });

        return redirect()
            ->route('pemakaianbarang.index')
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
            ->when(is_numeric($raw), fn ($q) => $q->where('fcabangid', (int) $raw))
            ->when(! is_numeric($raw), fn ($q) => $q
                ->where('fcabangkode', $raw)
                ->orWhere('fcabangname', $raw))
            ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

        $warehouses = DB::table('mswh')
            ->select('fwhid', 'fwhcode', 'fwhname', 'fbranchcode', 'fnonactive')
            ->where('fnonactive', '0') // hanya yang aktif
            ->orderBy('fwhcode')
            ->get();

        $fcabang = $branch->fcabangname ?? (string) $raw;
        $fbranchcode = $branch->fcabangkode ?? (string) $raw;

        // 1. Ambil data Header (trstockmt) DAN relasi Details (trstockdt)
        // Biarkan query ini. Sekarang $fstockmtid di sini adalah integer (misal: 8)
        $pemakaianbarang = PenerimaanPembelianHeader::with([
            'details' => function ($query) {
                $query
                    ->join('msprd', 'msprd.fprdid', '=', 'trstockdt.fprdcodeid')
                    ->leftJoin('account', function ($join) {
                        $join->on(DB::raw('TRIM(account.faccount)'), '=', DB::raw('TRIM(trstockdt.frefdtno)'));
                    })
                    ->leftJoin('mssubaccount', function ($join) {
                        $join->on(DB::raw('TRIM(mssubaccount.fsubaccountcode)'), '=', DB::raw('TRIM(trstockdt.frefso)'));
                    })
                    ->select(
                        'trstockdt.*',
                        'msprd.fprdname',
                        'msprd.fprdcode as fitemcode_text',
                        'account.faccname as account_name_text',
                        'mssubaccount.fsubaccountname as subaccount_name_text'
                    )
                    ->orderBy('trstockdt.fstockdtid', 'asc');
            },
        ])->findOrFail($fstockmtid);

        $usageLockMessage = $this->getUsageLockMessage($pemakaianbarang);

        // 4. Map the data for savedItems (sudah menggunakan data yang benar)
        $savedItems = $pemakaianbarang->details->map(function ($d) {
            $accountCode = trim((string) ($d->frefdtno ?? '')) ?: null;
            $subaccountCode = trim((string) ($d->frefso ?? '')) ?: null;

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
                'fqty' => (float) ($d->fqty ?? 0),
                'fterima' => (float) ($d->fterima ?? 0),
                'fdisc' => (float) ($d->fdiscpersen ?? 0),
                'ftotal' => (float) ($d->ftotprice ?? 0),
                'fdesc' => is_array($d->fdesc) ? implode(', ', $d->fdesc) : ($d->fdesc ?? ''),
                'fketdt' => $d->fketdt ?? '',
                'units' => [],

                // TAMBAHKAN INI - untuk JavaScript
                'account_code' => $accountCode,
                'account_name' => $d->account_name_text ?? null,
                'account_label' => $accountCode
                  ? trim($accountCode.' - '.($d->account_name_text ?? $accountCode))
                  : null,
                'subaccount_code' => $subaccountCode,
                'subaccount_name' => $d->subaccount_name_text ?? null,
                'subaccount_label' => $subaccountCode
                  ? trim($subaccountCode.' - '.($d->subaccount_name_text ?? $subaccountCode))
                  : null,
            ];
        })->values();

        // Sisa kode Anda sudah benar
        $selectedSupplierCode = $pemakaianbarang->fsupplier;

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
                    'name' => $p->fprdname,
                    'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
                    'stock' => $p->fminstock ?? 0,
                ],
            ];
        })->toArray();

        return view('pemakaianbarang.edit', [
            'supplier' => $supplier,
            'selectedSupplierCode' => $selectedSupplierCode,
            'fcabang' => $fcabang,
            'accounts' => $accounts,
            'subaccounts' => $subaccounts,
            'fbranchcode' => $fbranchcode,
            'warehouses' => $warehouses,
            'products' => $products,
            'productMap' => $productMap,
            'pemakaianbarang' => $pemakaianbarang,
            'savedItems' => $savedItems,
            'ppnAmount' => (float) ($pemakaianbarang->famountpopajak ?? 0),
            'famountponet' => (float) ($pemakaianbarang->famountponet ?? 0),
            'famountpo' => (float) ($pemakaianbarang->famountpo ?? 0),
            'isUsageLocked' => ! empty($usageLockMessage),
            'usageLockMessage' => $usageLockMessage,
            'action' => 'delete',
        ]);
    }

    public function destroy($fstockmtid)
    {
        try {
            $pemakaianbarang = PenerimaanPembelianHeader::findOrFail($fstockmtid);
            if ($message = $this->getUsageLockMessage($pemakaianbarang)) {
                return redirect()->route('pemakaianbarang.index')->with('error', $message);
            }
            DB::transaction(function () use ($pemakaianbarang) {
                DB::table('trstockdt')
                    ->where('fstockmtno', $pemakaianbarang->fstockmtno)
                    ->delete();

                $pemakaianbarang->delete();
            });

            return redirect()->route('pemakaianbarang.index')->with('success', 'Data pemakaianbarang '.$pemakaianbarang->fstockmtno.' berhasil dihapus.');
        } catch (\Exception $e) {
            // Jika terjadi kesalahan saat menghapus, kembali ke halaman delete dengan pesan error
            return redirect()->route('pemakaianbarang.delete', $fstockmtid)->with('error', 'Gagal menghapus data: '.$e->getMessage());
        }
    }

    private function getUsageLockMessage(PenerimaanPembelianHeader $header): ?string
    {
        $usedBy = DB::table('trstockdt')
            ->where('fstockmtno', '<>', $header->fstockmtno)
            ->where(function ($query) use ($header) {
                $query->where('frefdtno', $header->fstockmtno)
                    ->orWhere('frefso', $header->fstockmtno);
            })
            ->select('fstockmtno')
            ->distinct()
            ->orderBy('fstockmtno')
            ->pluck('fstockmtno');

        if ($usedBy->isEmpty()) {
            return null;
        }

        return 'Pemakaian Barang '.$header->fstockmtno.' tidak dapat diubah atau dihapus karena sudah digunakan pada transaksi lain: '.$usedBy->implode(', ').'.';
    }

    private function normalizeRandomNumber($value, array &$usedNumbers): string
    {
        $value = trim((string) ($value ?? ''));
        $candidate = preg_match('/^[1-9]{3}$/', $value) ? $value : null;

        if ($candidate !== null && ! in_array($candidate, $usedNumbers, true)) {
            $usedNumbers[] = $candidate;

            return $candidate;
        }

        do {
            $candidate = (string) random_int(1, 9).random_int(1, 9).random_int(1, 9);
        } while (in_array($candidate, $usedNumbers, true));

        $usedNumbers[] = $candidate;

        return $candidate;
    }
}
