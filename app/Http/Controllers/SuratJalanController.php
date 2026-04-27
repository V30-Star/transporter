<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PenerimaanPembelianDetail;
use App\Models\PenerimaanPembelianHeader;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
// Pastikan ini ada jika menggunakan throw new \Exception
use Illuminate\Support\Facades\DB; // sekalian biar aman untuk tanggal
use Illuminate\Validation\ValidationException;

class SuratJalanController extends Controller
{
    public function index(Request $request)
    {
        // --- 1. PERMISSIONS ---
        $canCreate = in_array('createSuratJalan', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updateSuratJalan', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteSuratJalan', explode(',', session('user_restricted_permissions', '')));
        $showActionsColumn = $canEdit || $canDelete;

        $year = $request->query('year');
        $month = $request->query('month');

        // Ambil tahun-tahun yang tersedia dari data
        $availableYears = PenerimaanPembelianHeader::selectRaw('DISTINCT EXTRACT(YEAR FROM fdatetime) as year')
            ->where('fstockmtcode', 'SRJ')
            ->whereNotNull('fdatetime')
            ->orderByRaw('EXTRACT(YEAR FROM fdatetime) DESC')
            ->pluck('year');

        // --- 2. Handle Request AJAX dari DataTables ---
        if ($request->ajax()) {

            // Query dasar HANYA untuk 'SRJ' (Receiving)
            $query = PenerimaanPembelianHeader::where('fstockmtcode', 'SRJ');

            // Total records (dengan filter 'SRJ')
            $totalRecords = PenerimaanPembelianHeader::where('fstockmtcode', 'SRJ')->count();

            // Handle Search (cari di No. Penerimaan)
            if ($search = $request->input('search.value')) {
                $query->where('fstockmtno', 'like', "%{$search}%");
            }

            // Filter tahun
            if ($year) {
                $query->whereRaw('EXTRACT(YEAR FROM fdatetime) = ?', [$year]);
            }

            // Filter bulan
            if ($month) {
                $query->whereRaw('EXTRACT(MONTH FROM fdatetime) = ?', [$month]);
            }

            // Total records setelah filter
            $filteredRecords = (clone $query)->count();

            // Handle Sorting
            $orderColIdx = $request->input('order.0.column', 0);
            $orderDir = $request->input('order.0.dir', 'desc');

            // Kolom di tabel: 0 = fstockmtno, 1 = fstockmtdate, 2 = actions
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
                    'fstockmtid' => $row->fstockmtid,
                    'fstockmtno' => $row->fstockmtno,
                    'fstockmtdate' => Carbon::parse($row->fstockmtdate)->format('d/m/Y'),
                ];
            });

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        }

        // --- 3. Handle Request non-AJAX ---
        return view('suratjalan.index', compact(
            'canCreate',
            'canEdit',
            'canDelete',
            'showActionsColumn',
            'availableYears',
            'year',
            'month'
        ));
    }

    // Di PenerimaanBarangController
    public function pickable(Request $request)
    {
        $query = DB::table('trstockmt')
            ->leftJoin('mscustomer', 'trstockmt.fsupplier', '=', 'mscustomer.fcustomercode')
            ->where('trstockmt.fstockmtcode', 'SRJ')
            ->select(
                'trstockmt.fstockmtid',
                'trstockmt.fstockmtno',
                'trstockmt.frefpo',
                'trstockmt.fstockmtdate',
                'mscustomer.fcustomername as fsuppliername'
            );

        // Filter Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('trstockmt.fstockmtno', 'ilike', "%{$search}%")
                    ->orWhere('trstockmt.frefpo', 'ilike', "%{$search}%")
                    ->orWhere('fsuppliername', 'ilike', "%{$search}%");
            });
        }

        $recordsTotal = DB::table('trstockmt')->count();
        $recordsFiltered = $query->count();

        $data = $query->orderBy('trstockmt.fstockmtdate', 'desc')
            ->skip($request->start)
            ->take($request->length)
            ->get();

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function items($id)
    {
        $header = DB::table('trstockmt')
            ->where('fstockmtid', $id)
            ->where('fstockmtcode', 'SRJ')
            ->first();

        if (! $header) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $remainMap = $this->getSrjRemainByIds(
            DB::table('trstockdt')->where('fstockmtno', $header->fstockmtno)->pluck('fstockdtid')->all()
        );

        $items = DB::table('trstockdt')
            ->where('trstockdt.fstockmtno', $header->fstockmtno)
            ->leftJoin('msprd', 'msprd.fprdid', '=', 'trstockdt.fprdcodeid')
            ->select(
                'trstockdt.fstockdtid as frefdtno',
                // UBAH BAGIAN INI: Ambil kolom kode dari msprd (misal: fprdcode_string)
                // atau pastikan kolom ini memang yang berisi kode produk
                'msprd.fprdcode as fitemcode',
                'msprd.fprdname as fitemname',
                'trstockdt.fqty',
                'trstockdt.fsatuan',
                'trstockdt.fprice',
                'trstockdt.ftotprice as ftotal'
            )
            ->get()
            ->map(function ($item) use ($remainMap) {
                $remain = (float) ($remainMap[(int) ($item->frefdtno ?? 0)] ?? 0);
                $item->fqty = $remain;
                $item->fqtyremain = $remain;

                return $item;
            });

        return response()->json([
            'header' => $header,
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

        $prefix = sprintf('PO.%s.%s.%s.', $kodeCabang, $date->format('y'), $date->format('m'));

        // kunci per (branch, tahun-bulan) — TANPA bikin tabel baru
        $lockKey = crc32('PO|' . $kodeCabang . '|' . $date->format('Y-m'));
        DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

        $last = DB::table('trstockmt')
            ->where('fstockmtno', 'like', $prefix . '%')
            ->selectRaw("MAX(CAST(split_part(fstockmtno, '.', 5) AS int)) AS lastno")
            ->value('lastno');

        $next = (int) $last + 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Sisa SO dinamis dalam satuan kecil.
     *
     * @param  array<int, int|string>  $soDetailIds
     * @return array<int, float>
     */
    private function getSoRemainByIds(array $soDetailIds): array
    {
        $ids = collect($soDetailIds)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            return [];
        }

        $srjUsed = DB::table('trstockdt')
            ->selectRaw('CAST(frefsoid AS BIGINT) AS detail_id, SUM(COALESCE(fqtykecil, 0)) AS used_kecil')
            ->whereNotNull('frefsoid')
            ->whereIn(DB::raw('CAST(frefsoid AS BIGINT)'), $ids)
            ->groupBy(DB::raw('CAST(frefsoid AS BIGINT)'));

        $salesUsed = DB::table('trandt')
            ->selectRaw('CAST(frefsoid AS BIGINT) AS detail_id, SUM(COALESCE(fqtykecil, 0)) AS used_kecil')
            ->whereNotNull('frefsoid')
            ->whereIn(DB::raw('CAST(frefsoid AS BIGINT)'), $ids)
            ->groupBy(DB::raw('CAST(frefsoid AS BIGINT)'));

        return DB::table('trsodt as d')
            ->leftJoinSub($srjUsed, 'srj', fn($join) => $join->on('srj.detail_id', '=', 'd.ftrsodtid'))
            ->leftJoinSub($salesUsed, 'sale', fn($join) => $join->on('sale.detail_id', '=', 'd.ftrsodtid'))
            ->whereIn('d.ftrsodtid', $ids)
            ->selectRaw('d.ftrsodtid, GREATEST(COALESCE(d.fqtykecil, 0) - COALESCE(srj.used_kecil, 0) - COALESCE(sale.used_kecil, 0), 0) AS remain_kecil')
            ->pluck('remain_kecil', 'd.ftrsodtid')
            ->map(fn($value) => (float) $value)
            ->all();
    }

    /**
     * Sisa SRJ dinamis dalam satuan kecil.
     *
     * @param  array<int, int|string>  $srjDetailIds
     * @return array<int, float>
     */
    private function getSrjRemainByIds(array $srjDetailIds): array
    {
        $ids = collect($srjDetailIds)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            return [];
        }

        $salesUsed = DB::table('trandt')
            ->selectRaw('CAST(frefsrjid AS BIGINT) AS detail_id, SUM(COALESCE(fqtykecil, 0)) AS used_kecil')
            ->whereNotNull('frefsrjid')
            ->whereIn(DB::raw('CAST(frefsrjid AS BIGINT)'), $ids)
            ->groupBy(DB::raw('CAST(frefsrjid AS BIGINT)'));

        return DB::table('trstockdt as d')
            ->leftJoinSub($salesUsed, 'sale', fn($join) => $join->on('sale.detail_id', '=', 'd.fstockdtid'))
            ->whereIn('d.fstockdtid', $ids)
            ->selectRaw('d.fstockdtid, GREATEST(COALESCE(d.fqtykecil, 0) - COALESCE(sale.used_kecil, 0), 0) AS remain_kecil')
            ->pluck('remain_kecil', 'd.fstockdtid')
            ->map(fn($value) => (float) $value)
            ->all();
    }

    public function print(string $fstockmtno)
    {
        // 1. Ambil query sub untuk customer
        $customerSub = Customer::select('fcustomerid', 'fcustomercode', 'fcustomername');

        $hdr = PenerimaanPembelianHeader::query()
            // Gunakan alias 'cust' untuk customer
            ->leftJoinSub($customerSub, 'cust', function ($join) {
                $join->on('cust.fcustomercode', '=', 'trstockmt.fsupplier');
            })
            // Gunakan alias 'cb' untuk cabang
            ->leftJoin('mscabang as cb', 'cb.fcabangkode', '=', 'trstockmt.fbranchcode')
            ->leftJoin('mswh as w', 'w.fwhcode', '=', 'trstockmt.ffrom')
            ->where('trstockmt.fstockmtno', $fstockmtno)
            ->first([
                'trstockmt.*',
                'cust.fcustomername as customer_name', // Ambil dari alias cust
                'cb.fcabangname as cabang_name',      // Ambil dari alias cb
                'w.fwhname as fwhnamen',
            ]);

        if (! $hdr) {
            return redirect()->back()->with('error', 'PO tidak ditemukan.');
        }

        // Bagian detail (sudah benar, tidak ada duplikasi alias)
        $dt = PenerimaanPembelianDetail::query()
            ->leftJoin('msprd as p', 'p.fprdcode', '=', 'trstockdt.fprdcode')
            ->where('trstockdt.fstockmtno', $fstockmtno)
            ->orderBy('trstockdt.fprdcode')
            ->get([
                'trstockdt.*',
                'p.fprdname as product_name',
                'p.fprdcode as product_code',
            ]);

        $fmt = fn($d) => $d
            ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d F Y')
            : '-';

        return view('suratjalan.print', [
            'hdr' => $hdr,
            'dt' => $dt,
            'fmt' => $fmt,
            'company_name' => config('app.company_name', 'PT. DEMO VERSION'),
            'company_city' => config('app.company_city', 'Tangerang'),
        ]);
    }

    public function create(Request $request)
    {
        $customers = Customer::orderBy('fcustomername', 'asc')
            ->get(['fcustomercode', 'fcustomername']);

        $warehouses = DB::table('mswh')
            ->select('fwhid', 'fwhcode', 'fwhname', 'fbranchcode', 'fnonactive')
            ->where('fnonactive', '0')              // hanya yang aktif
            ->orderBy('fwhcode')
            ->get();

        $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

        $branch = DB::table('mscabang')
            ->when(is_numeric($raw), fn($q) => $q->where('fcabangid', (int) $raw))
            ->when(
                ! is_numeric($raw),
                fn($q) => $q->where('fcabangkode', $raw)->orWhere('fcabangname', $raw)
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
            'fqtykecil',
            'fqtykecil2',
            'fminstock'
        )->orderBy('fprdname')->get();

        $productMap = $products->mapWithKeys(function ($p) {
            return [
                $p->fprdcode => [
                    'name'  => $p->fprdname,
                    'units' => array_values(array_filter([
                        $p->fsatuankecil,
                        $p->fsatuanbesar,
                        $p->fsatuanbesar2,
                    ])),
                    'stock' => $p->fminstock ?? 0,
                    'unit_ratios' => [           // ← TAMBAH INI
                        'satuankecil'  => 1,
                        'satuanbesar'  => (float) ($p->fqtykecil  ?? 1),
                        'satuanbesar2' => (float) ($p->fqtykecil2 ?? 1),
                    ],
                ],
            ];
        })->toArray();

        return view('suratjalan.create', [
            'newtr_prh_code' => $newtr_prh_code,
            'warehouses' => $warehouses,
            'perms' => ['can_approval' => $canApproval],
            'customers' => $customers,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'products' => $products,
            'productMap' => $productMap,
            'filterSupplierId' => $request->query('filter_supplier_id'),
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
            'fsupplier' => ['required', 'string', 'max:30'],
            'ffrom' => ['nullable', 'string', 'max:10'],
            'fket' => ['nullable', 'string', 'max:50'],
            'fkirim' => ['nullable', 'string', 'max:300'],
            'fbranchcode' => ['nullable', 'string', 'max:20'],
            'fitemcode' => ['required', 'array', 'min:1'],
            'fitemcode.*' => ['required', 'string', 'max:50'],
            'fsatuan' => ['nullable', 'array'],
            'fsatuan.*' => ['nullable', 'string', 'max:20'],
            'frefdtno' => ['nullable', 'array'],
            'frefdtno.*' => ['nullable', 'integer'],
            'fqty' => ['required', 'array'],
            'fqty.*' => ['numeric', 'min:0'],
            'fprice' => ['required', 'array'],
            'fprice.*' => ['numeric', 'min:0'],
            'fdesc' => ['nullable', 'array'],
            'fdesc.*' => ['nullable', 'string', 'max:500'],
            'fcurrency' => ['nullable', 'string', 'max:5'],
            'frate' => ['nullable', 'numeric', 'min:0'],
            'famountpopajak' => ['nullable', 'numeric', 'min:0'],
            'frefso' => ['nullable', 'array'],
            'frefso.*' => ['nullable', 'string', 'max:100'],
            'frefsoid' => ['nullable', 'array'],
            'frefsoid.*' => ['nullable', 'integer'],
        ]);

        // =========================
        // 2) HEADER FIELDS
        // =========================
        $fstockmtno = trim((string) $request->input('fstockmtno'));
        $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
        $fsupplier = trim((string) $request->input('fsupplier'));
        $ffrom = trim((string) $request->input('ffrom'));
        $fket = trim((string) $request->input('fket', ''));
        $fkirim = trim((string) $request->input('fkirim', ''));
        $fbranchcode = $request->input('fbranchcode');
        $fcurrency = $request->input('fcurrency', 'IDR');
        $frate = (float) $request->input('frate', 1);
        if ($frate <= 0) {
            $frate = 1;
        }
        $ppnAmount = (float) $request->input('famountpopajak', 0);
        $userid = auth('sysuser')->user()->fsysuserid ?? 'admin';
        $now = now();

        // =========================
        // 3) DETAIL ARRAYS
        // =========================
        $codes = $request->input('fitemcode', []);
        $satuans = $request->input('fsatuan', []);
        $refdtno = $request->input('frefdtno', []);
        $qtys = $request->input('fqty', []);
        $prices = $request->input('fprice', []);
        $descs = $request->input('fdesc', []);
        $frefso = $request->input('frefso', []);
        $frefsoid = $request->input('frefsoid', []);

        $rowCount = count($codes);
        $uniqueCodes = array_values(array_unique(
            array_filter(array_map(fn($c) => trim((string) $c), $codes))
        ));

        // =========================
        // 4) PRELOAD MASTER PRODUK
        // =========================
        $prodMeta = DB::table('msprd')
            ->whereIn('fprdcode', $uniqueCodes)
            ->get(['fprdid', 'fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2', 'fqtykecil'])
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

        // =========================
        // 6) RAKIT DETAIL + HITUNG SUBTOTAL
        // =========================
        $rowsDt = [];
        $subtotal = 0.0;

        for ($i = 0; $i < $rowCount; $i++) {
            $code = trim((string) ($codes[$i] ?? ''));
            $sat = trim((string) ($satuans[$i] ?? ''));
            $rref = $refdtno[$i] ?? null;
            $rnour = $nourefs[$i] ?? null;
            $qty = (float) ($qtys[$i] ?? 0);
            $price = (float) ($prices[$i] ?? 0);
            $desc = (string) ($descs[$i] ?? '');

            $meta = $prodMeta[$code] ?? null;

            $qtyKecil = $qty;
            if ($sat !== '' && $sat === trim((string) ($meta->fsatuanbesar ?? '')) && (float) $meta->fqtykecil > 0) {
                $qtyKecil = $qty * (float) $meta->fqtykecil;
            }

            if ($sat === '') {
                $sat = $pickDefaultSat($meta);
            }
            $sat = mb_substr($sat, 0, 5);
            if ($sat === '') {
                continue;
            }

            $frefdtnoValue = ($rref !== null && $rref !== '') ? (int) $rref : null;
            $amount = $qty * $price;
            $subtotal += $amount;

            $row = [
                'fprdcode' => $code,
                'fprdcodeid' => $meta->fprdid ?? null,
                'frefdtno' => $frefdtnoValue,
                'fqty' => $qty,
                'fprice' => $price,
                'fprice_rp' => $price * $frate,
                'ftotprice' => $amount,
                'ftotprice_rp' => $amount * $frate,
                'fusercreate' => Auth::user()->fname ?? 'system',
                'fdatetime' => $now,
                'fketdt' => '',
                'fcode' => '0',
                'frefso' => $frefso[$i] ?? null,
                'frefsoid' => isset($frefsoid[$i]) ? (int) $frefsoid[$i] : null,
                'fdesc' => $desc,
                'fsatuan' => $sat,
                'fclosedt' => '0',
                'fdiscpersen' => 0,
                'fbiaya' => 0,
                'fqtykecil' => $qtyKecil,
                'fqtyremain' => $qtyKecil,
            ];

            $rowsDt[] = $row;
        }

        if (empty($rowsDt)) {
            return back()->withInput()->withErrors([
                'detail' => 'Minimal satu item valid (Kode, Satuan, Qty > 0).',
            ]);
        }

        // =========================
        // 6.5) VALIDASI QTY REMAIN SO
        // =========================
        $soUsageByDetailId = [];
        foreach ($rowsDt as $row) {
            $soDetailId = isset($row['frefsoid']) ? (int) $row['frefsoid'] : 0;
            $qtyKecil = (float) ($row['fqtykecil'] ?? 0);
            if ($soDetailId > 0 && $qtyKecil > 0) {
                $soUsageByDetailId[$soDetailId] = ($soUsageByDetailId[$soDetailId] ?? 0) + $qtyKecil;
            }
        }

        if (!empty($soUsageByDetailId)) {
            $soRemainRows = $this->getSoRemainByIds(array_keys($soUsageByDetailId));

            $soRemainErrors = [];
            foreach ($soUsageByDetailId as $soDetailId => $usedQtyKecil) {
                $remain = (float) ($soRemainRows[$soDetailId] ?? 0);
                if ($usedQtyKecil - $remain > 0.00001) {
                    $soRemainErrors[] = "Qty SO detail #{$soDetailId} melebihi sisa. Input: {$usedQtyKecil}, sisa: {$remain}.";
                }
            }

            if (!empty($soRemainErrors)) {
                throw ValidationException::withMessages(['detail' => $soRemainErrors]);
            }
        }

        // =========================
        // 7) TRANSAKSI DB
        // =========================
        try {
            DB::transaction(function () use (
                $fstockmtdate,
                $fsupplier,
                $ffrom,
                $fket,
                $fkirim,
                $fbranchcode,
                $fcurrency,
                $frate,
                $userid,
                $now,
                &$fstockmtno,
                &$rowsDt,
                $subtotal,
                $ppnAmount,
                $soUsageByDetailId
            ) {
                // ---- 7.1. kodeCabang ----
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
                $fstockmtcode = 'SRJ';

                // ---- 7.2. Generate nomor transaksi ----
                if (empty($fstockmtno)) {
                    $prefix = sprintf('%s.%s.%s.%s.', $fstockmtcode, $kodeCabang, $yy, $mm);
                    $lockKey = crc32('STOCKMT|' . $fstockmtcode . '|' . $kodeCabang . '|' . $fstockmtdate->format('Y-m'));
                    DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

                    $last = DB::table('trstockmt')
                        ->where('fstockmtno', 'like', $prefix . '%')
                        ->selectRaw("MAX(CAST(split_part(fstockmtno, '.', 5) AS int)) AS lastno")
                        ->value('lastno');
                    $next = (int) $last + 1;
                    $fstockmtno = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
                }

                // ---- 7.3. INSERT HEADER ----
                $subtotalRp = $subtotal * $frate;
                $masterData = [
                    'fstockmtno' => $fstockmtno,
                    'fstockmtcode' => $fstockmtcode,
                    'fstockmtdate' => $fstockmtdate,
                    'fprdout' => '0',
                    'fsupplier' => $fsupplier,
                    'fcurrency' => $fcurrency,
                    'frate' => $frate,
                    'famount' => $subtotal,
                    'famount_rp' => $subtotalRp,
                    'famountpajak' => $ppnAmount,
                    'famountpajak_rp' => $ppnAmount * $frate,
                    'famountmt' => $subtotal + $ppnAmount,
                    'famountmt_rp' => ($subtotal + $ppnAmount) * $frate,
                    'famountremain' => $subtotal + $ppnAmount,
                    'famountremain_rp' => ($subtotal + $ppnAmount) * $frate,
                    'frefno' => null,
                    'frefpo' => null,
                    'ftrancode' => null,
                    'ffrom' => $ffrom,
                    'fto' => null,
                    'fkirim' => $fkirim,
                    'fprdjadi' => null,
                    'fqtyjadi' => null,
                    'fket' => $fket,
                    'fusercreate' => Auth::user()->fname ?? 'system',
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
                    throw new \Exception('Gagal menyimpan data master (header).');
                }

                foreach ($rowsDt as &$r) {
                    $r['fstockmtcode'] = $fstockmtcode;
                    $r['fstockmtno'] = $fstockmtno;
                }
                unset($r);

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

                if (!empty($soUsageByDetailId)) {
                    $remainRows = $this->getSoRemainByIds(array_keys($soUsageByDetailId));
                    foreach ($soUsageByDetailId as $soDetailId => $usedQtyKecil) {
                        $remain = (float) ($remainRows[$soDetailId] ?? 0);
                        if ($usedQtyKecil - $remain > 0.00001) {
                            throw new \RuntimeException("Qty SO detail #{$soDetailId} melebihi sisa.");
                        }
                    }
                }

                // ---- 7.5. JURNAL ----
                $INVENTORY_ACCOUNT_CODE = '11400';
                $PPN_IN_ACCOUNT_CODE = '11500';
                $PAYABLE_ACCOUNT_CODE = '21100';

                $fjurnaltype = 'JV';
                $jurnalPrefix = sprintf('%s.%s.%s.%s.', $fjurnaltype, $kodeCabang, $yy, $mm);

                $jurnalLockKey = crc32('JURNAL|' . $fjurnaltype . '|' . $kodeCabang . '|' . $fstockmtdate->format('Y-m'));
                DB::statement('SELECT pg_advisory_xact_lock(?)', [$jurnalLockKey]);

                $lastJurnalNo = DB::table('jurnalmt')
                    ->where('fjurnalno', 'like', $jurnalPrefix . '%')
                    ->selectRaw("MAX(CAST(split_part(fjurnalno, '.', 5) AS int)) AS lastno")
                    ->value('lastno');

                $nextJurnalNo = (int) $lastJurnalNo + 1;
                $fjurnalno = $jurnalPrefix . str_pad((string) $nextJurnalNo, 4, '0', STR_PAD_LEFT);

                $jurnalHeader = [
                    'fbranchcode' => $kodeCabang,
                    'fjurnalno' => $fjurnalno,
                    'fjurnaltype' => $fjurnaltype,
                    'fjurnaldate' => $fstockmtdate,
                    'fjurnalnote' => 'Jurnal Penerimaan Barang ' . $fstockmtno . ' dari Customer: ' . $fsupplier,
                    'fbalance' => $subtotal + $ppnAmount,
                    'fbalance_rp' => ($subtotal + $ppnAmount) * $frate,
                    'fdatetime' => $now,
                    'fuserid' => $userid,
                ];

                $newJurnalMasterId = DB::table('jurnalmt')->insertGetId($jurnalHeader, 'fjurnalmtid');

                if (! $newJurnalMasterId) {
                    throw new \Exception('Gagal menyimpan data jurnal header.');
                }

                $jurnalDetails = [];
                $flineno = 1;

                $jurnalDetails[] = [
                    'fjurnalmtid' => $newJurnalMasterId,
                    'fbranchcode' => $kodeCabang,
                    'fjurnaltype' => $fjurnaltype,
                    'fjurnalno' => $fjurnalno,
                    'flineno' => $flineno++,
                    'faccount' => $INVENTORY_ACCOUNT_CODE,
                    'fdk' => 'D',
                    'fsubaccount' => $fsupplier,
                    'frefno' => $fstockmtno,
                    'frate' => $frate,
                    'famount' => $subtotal,
                    'famount_rp' => $subtotalRp,
                    'faccountnote' => 'Persediaan Barang Dagang ' . $fstockmtno,
                    'fusercreate' => $userid,
                    'fdatetime' => $now,
                ];

                if ($ppnAmount > 0) {
                    $jurnalDetails[] = [
                        'fjurnalmtid' => $newJurnalMasterId,
                        'fbranchcode' => $kodeCabang,
                        'fjurnaltype' => $fjurnaltype,
                        'fjurnalno' => $fjurnalno,
                        'flineno' => $flineno++,
                        'faccount' => $PPN_IN_ACCOUNT_CODE,
                        'fdk' => 'D',
                        'fsubaccount' => null,
                        'frefno' => $fstockmtno,
                        'frate' => $frate,
                        'famount' => $ppnAmount,
                        'famount_rp' => $ppnAmount * $frate,
                        'faccountnote' => 'PPN Masukan ' . $fstockmtno,
                        'fusercreate' => $userid,
                        'fdatetime' => $now,
                    ];
                }

                $totalHutang = $subtotal + $ppnAmount;
                $jurnalDetails[] = [
                    'fjurnalmtid' => $newJurnalMasterId,
                    'fbranchcode' => $kodeCabang,
                    'fjurnaltype' => $fjurnaltype,
                    'fjurnalno' => $fjurnalno,
                    'flineno' => $flineno++,
                    'faccount' => $PAYABLE_ACCOUNT_CODE,
                    'fdk' => 'K',
                    'fsubaccount' => $fsupplier,
                    'frefno' => $fstockmtno,
                    'frate' => $frate,
                    'famount' => $totalHutang,
                    'famount_rp' => $totalHutang * $frate,
                    'faccountnote' => 'Hutang Dagang Customer ' . $fsupplier . ' (Total Pembelian)',
                    'fusercreate' => $userid,
                    'fdatetime' => $now,
                ];

                DB::table('jurnaldt')->insert($jurnalDetails);
            });
        } catch (\Throwable $e) {

            return back()->withInput()->withErrors([
                'detail' => 'Transaksi gagal disimpan: ' . $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('suratjalan.create')
            ->with('success', "Transaksi {$fstockmtno} tersimpan.");
    }

    public function edit(Request $request, $fstockmtid)
    {
        $customers = Customer::orderBy('fcustomername', 'asc')
            ->get(['fcustomercode', 'fcustomername']);

        $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

        $branch = DB::table('mscabang')
            ->when(is_numeric($raw), fn($q) => $q->where('fcabangid', (int) $raw))
            ->when(! is_numeric($raw), fn($q) => $q
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
        $suratjalan = PenerimaanPembelianHeader::with([
            'details' => function ($query) {
                $query
                    // 2. Join ke msprd berdasarkan ID
                    ->join('msprd', 'msprd.fprdid', '=', 'trstockdt.fprdcodeid')
                    // 3. Select kolom yang dibutuhkan
                    ->select(
                        'trstockdt.*', // Ambil semua kolom dari tabel detail
                        'msprd.fprdname', // Ambil nama produk
                        'msprd.fprdcode as fitemcode_text' // Ambil KODE string produk
                    )
                    ->orderBy('trstockdt.fstockdtid', 'asc');
            },
        ])
            ->leftJoin('mswh', 'mswh.fwhcode', '=', 'trstockmt.ffrom')
            ->select('trstockmt.*', 'mswh.fwhcode as ffrom_code')
            ->findOrFail($fstockmtid); // Temukan header berdasarkan $fstockmtid dari URL

        // 4. Map the data for savedItems (sudah menggunakan data yang benar)
        $usageLockMessage = $this->getUsageLockMessage($suratjalan);
        $oldUsageBySo = $suratjalan->details
            ->groupBy(fn($d) => (int) ($d->frefsoid ?? 0))
            ->map(fn($rows) => (float) $rows->sum(fn($r) => (float) ($r->fqtykecil ?? 0)))
            ->all();
        $soRemainMap = $this->getSoRemainByIds($suratjalan->details->pluck('frefsoid')->all());

        $savedItems = $suratjalan->details->map(function ($d) use ($oldUsageBySo, $soRemainMap) {
            return [
                'uid' => $d->fstockdtid,
                'fitemcode' => $d->fitemcode_text ?? '',
                'fitemname' => $d->fprdname ?? '',
                'fsatuan' => $d->fsatuan ?? '',
                'fprno' => $d->frefpr ?? '-',
                'frefpr' => $d->frefpr ?? null,
                'fpono' => $d->fpono ?? null,
                'famountponet' => $d->famountponet ?? null,
                'famountpo' => $d->famountpo ?? null,
                'frefdtno' => $d->frefdtno ?? null,
                'fqty' => (float) ($d->fqty ?? 0),
                'fterima' => (float) ($d->fterima ?? 0),
                'fprice' => (float) ($d->fprice ?? 0),
                'fdisc' => (float) ($d->fdiscpersen ?? 0),
                'ftotal' => (float) ($d->ftotprice ?? 0),
                'fdesc' => is_array($d->fdesc) ? implode(', ', $d->fdesc) : ($d->fdesc ?? ''),
                'frefno_display' => $d->frefso ?? $d->frefpr ?? $d->fpono ?? '-',
                'frefso' => $d->frefso ?? null,
                'frefsoid' => $d->frefsoid ?? null,
                'fqtyremain' => $d->frefsoid ? max(0, (float) ($soRemainMap[(int) $d->frefsoid] ?? 0) + (float) ($oldUsageBySo[(int) $d->frefsoid] ?? 0)) : 0,
                'fketdt' => $d->fketdt ?? '',
                'units' => [],
            ];
        })->values();

        // Sisa kode Anda sudah benar
        $selectedSupplierCode = $suratjalan->fsupplier;

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

        $productMap = $products->mapWithKeys(function ($p) {
            return [
                $p->fprdcode => [
                    'name'  => $p->fprdname,
                    'units' => array_values(array_filter([
                        $p->fsatuankecil,
                        $p->fsatuanbesar,
                        $p->fsatuanbesar2,
                    ])),
                    'stock' => $p->fminstock ?? 0,
                    'unit_ratios' => [           // ← TAMBAH INI
                        'satuankecil'  => 1,
                        'satuanbesar'  => (float) ($p->fqtykecil  ?? 1),
                        'satuanbesar2' => (float) ($p->fqtykecil2 ?? 1),
                    ],
                ],
            ];
        })->toArray();

        return view('suratjalan.edit', [
            'customers' => $customers,
            'selectedSupplierCode' => $selectedSupplierCode,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'warehouses' => $warehouses,
            'products' => $products,
            'productMap' => $productMap,
            'suratjalan' => $suratjalan,
            'savedItems' => $savedItems,
            'ppnAmount' => (float) ($suratjalan->famountpopajak ?? 0),
            'famountponet' => (float) ($suratjalan->famountponet ?? 0),
            'famountpo' => (float) ($suratjalan->famountpo ?? 0),
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'isUsageLocked' => !empty($usageLockMessage),
            'usageLockMessage' => $usageLockMessage,
            'action' => 'edit',
        ]);
    }

    public function view(Request $request, $fstockmtid)
    {
        $customers = Customer::orderBy('fcustomername', 'asc')
            ->get(['fcustomercode', 'fcustomername']);

        $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

        $branch = DB::table('mscabang')
            ->when(is_numeric($raw), fn($q) => $q->where('fcabangid', (int) $raw))
            ->when(! is_numeric($raw), fn($q) => $q
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
        $suratjalan = PenerimaanPembelianHeader::with([
            'details' => function ($query) {
                $query
                    // 2. Join ke msprd berdasarkan ID
                    ->join('msprd', 'msprd.fprdid', '=', 'trstockdt.fprdcodeid')
                    // 3. Select kolom yang dibutuhkan
                    ->select(
                        'trstockdt.*', // Ambil semua kolom dari tabel detail
                        'msprd.fprdname', // Ambil nama produk
                        'msprd.fprdcode as fitemcode_text' // Ambil KODE string produk
                    )
                    ->orderBy('trstockdt.fstockdtid', 'asc');
            },
        ])
            ->leftJoin('mswh', 'mswh.fwhcode', '=', 'trstockmt.ffrom')
            ->select('trstockmt.*', 'mswh.fwhcode as ffrom_code')
            ->findOrFail($fstockmtid); // Temukan header berdasarkan $fstockmtid dari URL

        // 4. Map the data for savedItems (sudah menggunakan data yang benar)
        $oldUsageBySo = $suratjalan->details
            ->groupBy(fn($d) => (int) ($d->frefsoid ?? 0))
            ->map(fn($rows) => (float) $rows->sum(fn($r) => (float) ($r->fqtykecil ?? 0)))
            ->all();
        $soRemainMap = $this->getSoRemainByIds($suratjalan->details->pluck('frefsoid')->all());

        $savedItems = $suratjalan->details->map(function ($d) use ($oldUsageBySo, $soRemainMap) {
            return [
                'uid' => $d->fstockdtid,
                'fitemcode' => $d->fitemcode_text ?? '',
                'fitemname' => $d->fprdname ?? '',
                'fsatuan' => $d->fsatuan ?? '',
                'fprno' => $d->frefpr ?? '-',
                'frefpr' => $d->frefpr ?? null,
                'fpono' => $d->fpono ?? null,
                'famountponet' => $d->famountponet ?? null,
                'famountpo' => $d->famountpo ?? null,
                'frefdtno' => $d->frefdtno ?? null,
                'fqty' => (float) ($d->fqty ?? 0),
                'fterima' => (float) ($d->fterima ?? 0),
                'fprice' => (float) ($d->fprice ?? 0),
                'fdisc' => (float) ($d->fdiscpersen ?? 0),
                'ftotal' => (float) ($d->ftotprice ?? 0),
                'fdesc' => is_array($d->fdesc) ? implode(', ', $d->fdesc) : ($d->fdesc ?? ''),
                'frefno_display' => $d->frefso ?? $d->frefpr ?? $d->fpono ?? '-',
                'frefso' => $d->frefso ?? null,
                'frefsoid' => $d->frefsoid ?? null,
                'fqtyremain' => $d->frefsoid ? max(0, (float) ($soRemainMap[(int) $d->frefsoid] ?? 0) + (float) ($oldUsageBySo[(int) $d->frefsoid] ?? 0)) : 0,
                'fketdt' => $d->fketdt ?? '',
                'units' => [],
            ];
        })->values();

        // Sisa kode Anda sudah benar
        $selectedSupplierCode = $suratjalan->fsupplier;

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

        return view('suratjalan.view', [
            'customers' => $customers,
            'selectedSupplierCode' => $selectedSupplierCode,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'warehouses' => $warehouses,
            'products' => $products,
            'productMap' => $productMap,
            'suratjalan' => $suratjalan,
            'savedItems' => $savedItems,
            'ppnAmount' => (float) ($suratjalan->famountpopajak ?? 0),
            'famountponet' => (float) ($suratjalan->famountponet ?? 0),
            'famountpo' => (float) ($suratjalan->famountpo ?? 0),
            'filterSupplierId' => $request->query('filter_supplier_id'),
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
            'fsupplier' => ['required', 'string', 'max:30'],
            'ffrom' => ['nullable', 'string', 'max:10'],
            'fket' => ['nullable', 'string', 'max:50'],
            'fkirim' => ['nullable', 'string', 'max:300'],
            'fbranchcode' => ['nullable', 'string', 'max:20'],
            'fitemcode' => ['required', 'array', 'min:1'],
            'fitemcode.*' => ['required', 'string', 'max:50'],
            'fsatuan' => ['nullable', 'array'],
            'fsatuan.*' => ['nullable', 'string', 'max:20'],
            'frefdtno' => ['nullable', 'array'],
            'frefdtno.*' => ['nullable', 'integer'],
            'fqty' => ['required', 'array'],
            'fqty.*' => ['numeric', 'min:0'],
            'fprice' => ['required', 'array'],
            'fprice.*' => ['numeric', 'min:0'],
            'fdesc' => ['nullable', 'array'],
            'fdesc.*' => ['nullable', 'string', 'max:500'],
            'fcurrency' => ['nullable', 'string', 'max:5'],
            'frate' => ['nullable', 'numeric', 'min:0'],
            'famountpopajak' => ['nullable', 'numeric', 'min:0'],
            'frefso' => ['nullable', 'array'],
            'frefso.*' => ['nullable', 'string', 'max:100'],
            'frefsoid' => ['nullable', 'array'],
            'frefsoid.*' => ['nullable', 'integer'],
        ]);

        // =========================
        // 2) AMBIL DATA HEADER
        // =========================
        $header = PenerimaanPembelianHeader::findOrFail($fstockmtid);

        if ($message = $this->getUsageLockMessage($header)) {
            return redirect()->route('suratjalan.index')->with('error', $message);
        }

        $fstockmtno = $header->fstockmtno;
        $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
        $fsupplier = trim((string) $request->input('fsupplier'));
        $ffrom = trim((string) $request->input('ffrom'));
        $fket = trim((string) $request->input('fket', ''));
        $fkirim = trim((string) $request->input('fkirim', ''));
        $fbranchcode = $request->input('fbranchcode');
        $fcurrency = $request->input('fcurrency', 'IDR');
        $frate = (float) $request->input('frate', 1);
        if ($frate <= 0) {
            $frate = 1;
        }
        $ppnAmount = (float) $request->input('famountpopajak', 0);
        $userid = auth('sysuser')->user()->fsysuserid ?? 'admin';
        $now = now();

        // =========================
        // 3) DETAIL ARRAYS
        // =========================
        $codes = $request->input('fitemcode', []);
        $satuans = $request->input('fsatuan', []);
        $refdtno = $request->input('frefdtno', []);
        $qtys = $request->input('fqty', []);
        $prices = $request->input('fprice', []);
        $descs = $request->input('fdesc', []);
        $frefso = $request->input('frefso', []);
        $frefsoid = $request->input('frefsoid', []);

        $oldSoUsageRows = DB::table('trstockdt')
            ->where('fstockmtno', $header->fstockmtno)
            ->whereNotNull('frefsoid')
            ->select('frefsoid', DB::raw('SUM(COALESCE(fqtykecil, 0)) as used_qty_kecil'))
            ->groupBy('frefsoid')
            ->get();
        $oldSoUsageByDetailId = [];
        foreach ($oldSoUsageRows as $oldUsageRow) {
            $detailId = (int) ($oldUsageRow->frefsoid ?? 0);
            $qtyKecil = (float) ($oldUsageRow->used_qty_kecil ?? 0);
            if ($detailId > 0 && $qtyKecil > 0) {
                $oldSoUsageByDetailId[$detailId] = ($oldSoUsageByDetailId[$detailId] ?? 0) + $qtyKecil;
            }
        }

        $rowCount = count($codes);
        $uniqueCodes = array_values(array_unique(
            array_filter(array_map(fn($c) => trim((string) $c), $codes))
        ));

        // =========================
        // 4) PRELOAD MASTER PRODUK
        // =========================
        $prodMeta = DB::table('msprd')
            ->whereIn('fprdcode', $uniqueCodes)
            ->get(['fprdid', 'fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2', 'fqtykecil'])
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

        // =========================
        // 5) RAKIT DETAIL + HITUNG SUBTOTAL
        // =========================
        $rowsDt = [];
        $subtotal = 0.0;

        for ($i = 0; $i < $rowCount; $i++) {
            $code = trim((string) ($codes[$i] ?? ''));
            $sat = trim((string) ($satuans[$i] ?? ''));
            $rref = $refdtno[$i] ?? null;
            $rnour = $nourefs[$i] ?? null;
            $qty = (float) ($qtys[$i] ?? 0);
            $price = (float) ($prices[$i] ?? 0);
            $desc = (string) ($descs[$i] ?? '');

            $meta = $prodMeta[$code] ?? null;

            $qtyKecil = $qty;
            if ($meta && $sat !== '' && $sat === trim((string) ($meta->fsatuanbesar ?? '')) && (float) $meta->fqtykecil > 0) {
                $qtyKecil = $qty * (float) $meta->fqtykecil;
            }

            if ($sat === '') {
                $sat = $pickDefaultSat($meta);
            }
            $sat = mb_substr($sat, 0, 5);
            if ($sat === '') {
                continue;
            }

            $frefdtnoValue = ($rref !== null && $rref !== '') ? (int) $rref : null;
            $amount = $qty * $price;
            $subtotal += $amount;

            $row = [
                'fprdcode' => $code,
                'fprdcodeid' => $meta->fprdid ?? null,
                'frefdtno' => $frefdtnoValue,
                'fqty' => $qty,
                'fprice' => $price,
                'fprice_rp' => $price * $frate,
                'ftotprice' => $amount,
                'ftotprice_rp' => $amount * $frate,
                'fusercreate' => $header->fusercreate, // Tetap gunakan creator asli
                'fuserupdate' => Auth::user()->fname ?? 'system',
                'fdatetime' => $now,
                'fketdt' => '',
                'fcode' => '0',
                'frefso' => $frefso[$i] ?? null,
                'frefsoid' => isset($frefsoid[$i]) ? (int) $frefsoid[$i] : null,
                'fdesc' => $desc,
                'fsatuan' => $sat,
                'fclosedt' => '0',
                'fdiscpersen' => 0,
                'fbiaya' => 0,
                'fqtykecil' => $qtyKecil,
                'fqtyremain' => $qtyKecil,
            ];

            $rowsDt[] = $row;
        }

        if (empty($rowsDt)) {
            return back()->withInput()->withErrors([
                'detail' => 'Minimal satu item valid (Kode, Satuan, Qty > 0).',
            ]);
        }

        // =========================
        // 5.5) VALIDASI QTY REMAIN SO
        // =========================
        $soUsageByDetailId = [];
        foreach ($rowsDt as $row) {
            $soDetailId = isset($row['frefsoid']) ? (int) $row['frefsoid'] : 0;
            $qtyKecil = (float) ($row['fqtykecil'] ?? 0);
            if ($soDetailId > 0 && $qtyKecil > 0) {
                $soUsageByDetailId[$soDetailId] = ($soUsageByDetailId[$soDetailId] ?? 0) + $qtyKecil;
            }
        }

        $soIdsToCheck = array_values(array_unique(array_merge(
            array_keys($soUsageByDetailId),
            array_keys($oldSoUsageByDetailId)
        )));

        if (!empty($soIdsToCheck)) {
            $soRemainRows = $this->getSoRemainByIds($soIdsToCheck);

            $soRemainErrors = [];
            foreach ($soUsageByDetailId as $soDetailId => $usedQtyKecil) {
                $currentRemain = (float) ($soRemainRows[$soDetailId] ?? 0);
                $restoredRemain = $currentRemain + (float) ($oldSoUsageByDetailId[$soDetailId] ?? 0);
                if ($usedQtyKecil - $restoredRemain > 0.00001) {
                    $soRemainErrors[] = "Qty SO detail #{$soDetailId} melebihi sisa. Input: {$usedQtyKecil}, sisa: {$restoredRemain}.";
                }
            }

            if (!empty($soRemainErrors)) {
                throw ValidationException::withMessages(['detail' => $soRemainErrors]);
            }
        }

        // =========================
        // 6) TRANSAKSI DB
        // =========================
        try {
            DB::transaction(function () use (
                $header,
                $fstockmtno,
                $fstockmtdate,
                $fsupplier,
                $ffrom,
                $fket,
                $fkirim,
                $fbranchcode,
                $fcurrency,
                $frate,
                $userid,
                $now,
                &$rowsDt,
                $subtotal,
                $ppnAmount,
                $oldSoUsageByDetailId,
                $soUsageByDetailId
            ) {
                // ---- 6.1. kodeCabang ----
                $kodeCabang = $header->fbranchcode;
                if ($fbranchcode !== null && $fbranchcode !== $header->fbranchcode) {
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
                $fstockmtcode = $header->fstockmtcode;

                // ---- 6.2. UPDATE HEADER ----
                $subtotalRp = $subtotal * $frate;
                $masterData = [
                    'fstockmtdate' => $fstockmtdate,
                    'fsupplier' => $fsupplier,
                    'fcurrency' => $fcurrency,
                    'frate' => $frate,
                    'famount' => $subtotal,
                    'famount_rp' => $subtotalRp,
                    'famountpajak' => $ppnAmount,
                    'famountpajak_rp' => $ppnAmount * $frate,
                    'famountmt' => $subtotal + $ppnAmount,
                    'famountmt_rp' => ($subtotal + $ppnAmount) * $frate,
                    'famountremain' => $subtotal + $ppnAmount,
                    'famountremain_rp' => ($subtotal + $ppnAmount) * $frate,
                    'ffrom' => $ffrom,
                    'fkirim' => $fkirim,
                    'fket' => $fket,
                    'fuserupdate' => Auth::user()->fname ?? 'system',
                    'fdatetime' => $now,
                    'fbranchcode' => $kodeCabang,
                ];

                $header->update($masterData);

                // ---- 6.3. UPDATE DETAIL (Refresh) ----
                DB::table('trstockdt')->where('fstockmtno', $header->fstockmtno)->delete();

                $nextNouRef = 1;
                foreach ($rowsDt as &$r) {
                    $r['fstockmtcode'] = $fstockmtcode;
                    $r['fstockmtno'] = $fstockmtno;
                }
                unset($r);

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

                if (!empty($soUsageByDetailId)) {
                    $dynamicRemainRows = $this->getSoRemainByIds(array_keys($soUsageByDetailId));

                    foreach ($soUsageByDetailId as $soDetailId => $usedQtyKecil) {
                        $remain = (float) ($dynamicRemainRows[$soDetailId] ?? 0) + (float) ($oldSoUsageByDetailId[$soDetailId] ?? 0);
                        if ($usedQtyKecil - $remain > 0.00001) {
                            throw new \RuntimeException("Qty SO detail #{$soDetailId} melebihi sisa.");
                        }
                    }
                }

                // ---- 6.4. JURNAL ----
                $INVENTORY_ACCOUNT_CODE = '11400';
                $PPN_IN_ACCOUNT_CODE = '11500';
                $PAYABLE_ACCOUNT_CODE = '21100';

                $fjurnaltype = 'JV';

                // Cari jurnalmt yang sudah ada lewat jurnaldt frefno
                $jurnalmtId = DB::table('jurnaldt')
                    ->where('frefno', $fstockmtno)
                    ->where('fjurnaltype', $fjurnaltype)
                    ->value('fjurnalmtid');

                if ($jurnalmtId) {
                    // Update jurnalmt
                    DB::table('jurnalmt')->where('fjurnalmtid', $jurnalmtId)->update([
                        'fjurnaldate' => $fstockmtdate,
                        'fjurnalnote' => 'Jurnal Penerimaan Barang ' . $fstockmtno . ' dari Customer: ' . $fsupplier,
                        'fbalance' => $subtotal + $ppnAmount,
                        'fbalance_rp' => ($subtotal + $ppnAmount) * $frate,
                        'fdatetime' => $now,
                        'fuserid' => $userid,
                        'fbranchcode' => $kodeCabang,
                    ]);
                    // Hapus jurnaldt lama
                    DB::table('jurnaldt')->where('fjurnalmtid', $jurnalmtId)->delete();

                    $newJurnalMasterId = $jurnalmtId;
                    $fjurnalno = DB::table('jurnalmt')->where('fjurnalmtid', $jurnalmtId)->value('fjurnalno');
                } else {
                    // Buat Jurnal Baru jika belum ada (fallback)
                    $jurnalPrefix = sprintf('%s.%s.%s.%s.', $fjurnaltype, $kodeCabang, $yy, $mm);
                    $jurnalLockKey = crc32('JURNAL|' . $fjurnaltype . '|' . $kodeCabang . '|' . $fstockmtdate->format('Y-m'));
                    DB::statement('SELECT pg_advisory_xact_lock(?)', [$jurnalLockKey]);

                    $lastJurnalNo = DB::table('jurnalmt')
                        ->where('fjurnalno', 'like', $jurnalPrefix . '%')
                        ->selectRaw("MAX(CAST(split_part(fjurnalno, '.', 5) AS int)) AS lastno")
                        ->value('lastno');

                    $nextJurnalNo = (int) $lastJurnalNo + 1;
                    $fjurnalno = $jurnalPrefix . str_pad((string) $nextJurnalNo, 4, '0', STR_PAD_LEFT);

                    $jurnalHeader = [
                        'fbranchcode' => $kodeCabang,
                        'fjurnalno' => $fjurnalno,
                        'fjurnaltype' => $fjurnaltype,
                        'fjurnaldate' => $fstockmtdate,
                        'fjurnalnote' => 'Jurnal Penerimaan Barang ' . $fstockmtno . ' dari Customer: ' . $fsupplier,
                        'fbalance' => $subtotal + $ppnAmount,
                        'fbalance_rp' => ($subtotal + $ppnAmount) * $frate,
                        'fdatetime' => $now,
                        'fuserid' => $userid,
                    ];

                    $newJurnalMasterId = DB::table('jurnalmt')->insertGetId($jurnalHeader, 'fjurnalmtid');
                }

                if ($newJurnalMasterId) {
                    $jurnalDetails = [];
                    $flineno = 1;

                    $jurnalDetails[] = [
                        'fjurnalmtid' => $newJurnalMasterId,
                        'fbranchcode' => $kodeCabang,
                        'fjurnaltype' => $fjurnaltype,
                        'fjurnalno' => $fjurnalno,
                        'flineno' => $flineno++,
                        'faccount' => $INVENTORY_ACCOUNT_CODE,
                        'fdk' => 'D',
                        'fsubaccount' => $fsupplier,
                        'frefno' => $fstockmtno,
                        'frate' => $frate,
                        'famount' => $subtotal,
                        'famount_rp' => $subtotalRp,
                        'faccountnote' => 'Persediaan Barang Dagang ' . $fstockmtno,
                        'fusercreate' => $userid,
                        'fdatetime' => $now,
                    ];

                    if ($ppnAmount > 0) {
                        $jurnalDetails[] = [
                            'fjurnalmtid' => $newJurnalMasterId,
                            'fbranchcode' => $kodeCabang,
                            'fjurnaltype' => $fjurnaltype,
                            'fjurnalno' => $fjurnalno,
                            'flineno' => $flineno++,
                            'faccount' => $PPN_IN_ACCOUNT_CODE,
                            'fdk' => 'D',
                            'fsubaccount' => null,
                            'frefno' => $fstockmtno,
                            'frate' => $frate,
                            'famount' => $ppnAmount,
                            'famount_rp' => $ppnAmount * $frate,
                            'faccountnote' => 'PPN Masukan ' . $fstockmtno,
                            'fusercreate' => $userid,
                            'fdatetime' => $now,
                        ];
                    }

                    $totalHutang = $subtotal + $ppnAmount;
                    $jurnalDetails[] = [
                        'fjurnalmtid' => $newJurnalMasterId,
                        'fbranchcode' => $kodeCabang,
                        'fjurnaltype' => $fjurnaltype,
                        'fjurnalno' => $fjurnalno,
                        'flineno' => $flineno++,
                        'faccount' => $PAYABLE_ACCOUNT_CODE,
                        'fdk' => 'K',
                        'fsubaccount' => $fsupplier,
                        'frefno' => $fstockmtno,
                        'frate' => $frate,
                        'famount' => $totalHutang,
                        'famount_rp' => $totalHutang * $frate,
                        'faccountnote' => 'Hutang Dagang Customer ' . $fsupplier . ' (Total Pembelian)',
                        'fusercreate' => $userid,
                        'fdatetime' => $now,
                    ];

                    DB::table('jurnaldt')->insert($jurnalDetails);
                }
            });
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors([
                'detail' => 'Transaksi gagal diperbarui: ' . $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('suratjalan.index')
            ->with('success', "Transaksi {$fstockmtno} berhasil diperbarui.");
    }

    public function delete(Request $request, $fstockmtid)
    {
        $customers = Customer::orderBy('fcustomername', 'asc')
            ->get(['fcustomercode', 'fcustomername']);

        $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

        $branch = DB::table('mscabang')
            ->when(is_numeric($raw), fn($q) => $q->where('fcabangid', (int) $raw))
            ->when(! is_numeric($raw), fn($q) => $q
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
        $suratjalan = PenerimaanPembelianHeader::with([
            'details' => function ($query) {
                $query
                    // 2. Join ke msprd berdasarkan ID
                    ->join('msprd', 'msprd.fprdid', '=', 'trstockdt.fprdcodeid')
                    // 3. Select kolom yang dibutuhkan
                    ->select(
                        'trstockdt.*', // Ambil semua kolom dari tabel detail
                        'msprd.fprdname', // Ambil nama produk
                        'msprd.fprdcode as fitemcode_text' // Ambil KODE string produk
                    )
                    ->orderBy('trstockdt.fstockdtid', 'asc');
            },
        ])
            ->leftJoin('mswh', 'mswh.fwhcode', '=', 'trstockmt.ffrom')
            ->select('trstockmt.*', 'mswh.fwhcode as ffrom_code')
            ->findOrFail($fstockmtid); // Temukan header berdasarkan $fstockmtid dari URL

        // 4. Map the data for savedItems (sudah menggunakan data yang benar)
        $usageLockMessage = $this->getUsageLockMessage($suratjalan);
        $oldUsageBySo = $suratjalan->details
            ->groupBy(fn($d) => (int) ($d->frefsoid ?? 0))
            ->map(fn($rows) => (float) $rows->sum(fn($r) => (float) ($r->fqtykecil ?? 0)))
            ->all();
        $soRemainMap = $this->getSoRemainByIds($suratjalan->details->pluck('frefsoid')->all());

        $savedItems = $suratjalan->details->map(function ($d) use ($oldUsageBySo, $soRemainMap) {
            return [
                'uid' => $d->fstockdtid,
                'fitemcode' => $d->fitemcode_text ?? '',
                'fitemname' => $d->fprdname ?? '',
                'fsatuan' => $d->fsatuan ?? '',
                'fprno' => $d->frefpr ?? '-',
                'frefpr' => $d->frefpr ?? null,
                'fpono' => $d->fpono ?? null,
                'famountponet' => $d->famountponet ?? null,
                'famountpo' => $d->famountpo ?? null,
                'frefdtno' => $d->frefdtno ?? null,
                'fqty' => (float) ($d->fqty ?? 0),
                'fterima' => (float) ($d->fterima ?? 0),
                'fprice' => (float) ($d->fprice ?? 0),
                'fdisc' => (float) ($d->fdiscpersen ?? 0),
                'ftotal' => (float) ($d->ftotprice ?? 0),
                'fdesc' => is_array($d->fdesc) ? implode(', ', $d->fdesc) : ($d->fdesc ?? ''),
                'frefno_display' => $d->frefso ?? $d->frefpr ?? $d->fpono ?? '-',
                'frefso' => $d->frefso ?? null,
                'frefsoid' => $d->frefsoid ?? null,
                'fqtyremain' => $d->frefsoid ? max(0, (float) ($soRemainMap[(int) $d->frefsoid] ?? 0) + (float) ($oldUsageBySo[(int) $d->frefsoid] ?? 0)) : 0,
                'fketdt' => $d->fketdt ?? '',
                'units' => [],
            ];
        })->values();

        // Sisa kode Anda sudah benar
        $selectedSupplierCode = $suratjalan->fsupplier;

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

        return view('suratjalan.edit', [
            'customers' => $customers,
            'selectedSupplierCode' => $selectedSupplierCode,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'warehouses' => $warehouses,
            'products' => $products,
            'productMap' => $productMap,
            'suratjalan' => $suratjalan,
            'savedItems' => $savedItems,
            'ppnAmount' => (float) ($suratjalan->famountpopajak ?? 0),
            'famountponet' => (float) ($suratjalan->famountponet ?? 0),
            'famountpo' => (float) ($suratjalan->famountpo ?? 0),
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'isUsageLocked' => !empty($usageLockMessage),
            'usageLockMessage' => $usageLockMessage,
            'action' => 'delete',
        ]);
    }

    public function destroy($fstockmtid)
    {
        try {
            $suratjalan = PenerimaanPembelianHeader::findOrFail($fstockmtid);

            if ($message = $this->getUsageLockMessage($suratjalan)) {
                return redirect()->route('suratjalan.index')->with('error', $message);
            }

            DB::transaction(function () use ($suratjalan) {
                DB::table('trstockdt')
                    ->where('fstockmtno', $suratjalan->fstockmtno)
                    ->delete();

                $suratjalan->delete();
            });

            return redirect()->route('suratjalan.index')->with('success', 'Data Surat Jalan ' . $suratjalan->fstockmtno . ' berhasil dihapus.');
        } catch (\Exception $e) {
            // Jika terjadi kesalahan saat menghapus, kembali ke halaman delete dengan pesan error
            return redirect()->route('suratjalan.delete', $fstockmtid)->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    private function getUsageLockMessage(PenerimaanPembelianHeader $header): ?string
    {
        $fstockmtno = trim((string) ($header->fstockmtno ?? ''));
        if ($fstockmtno === '') {
            return null;
        }

        $usedBySalesDocs = DB::table('trandt as dt')
            ->join('tranmt as mt', 'mt.fsono', '=', 'dt.fsono')
            ->where('dt.frefsrj', $fstockmtno)
            ->select('mt.fsono')
            ->distinct()
            ->orderBy('mt.fsono')
            ->pluck('mt.fsono');

        $parts = [];
        $usedByInvoice = $usedBySalesDocs->filter(fn($no) => str_starts_with((string) $no, 'INV.'));
        if ($usedByInvoice->isNotEmpty()) {
            $parts[] = 'Faktur Penjualan: ' . $usedByInvoice->implode(', ');
        }

        $usedByRetur = $usedBySalesDocs->filter(fn($no) => str_starts_with((string) $no, 'REJ.'));
        if ($usedByRetur->isNotEmpty()) {
            $parts[] = 'Retur Penjualan: ' . $usedByRetur->implode(', ');
        }

        if (empty($parts)) {
            return null;
        }

        return 'Surat Jalan ' . $fstockmtno . ' tidak dapat diubah atau dihapus karena sudah digunakan pada ' . implode('; ', $parts) . '.';
    }
}
