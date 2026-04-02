<?php

namespace App\Http\Controllers;

use App\Mail\ApprovalEmail;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Tr_prd;
use App\Models\Tr_prh;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class Tr_prhController extends Controller
{
    public function index(Request $request)
    {
        // Ambil izin (permissions) di awal
        $canCreate = in_array('createTr_prh', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updateTr_prh', explode(',', session('user_restricted_permissions', '')));
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

            $query = Tr_prh::query()
                ->leftJoin('mssupplier', 'tr_prh.fsupplier', '=', 'mssupplier.fsupplierid');
            $totalRecords = Tr_prh::count();

            // Kolom yang bisa dicari
            $searchableColumns = ['tr_prh.fprno', 'tr_prh.fprdin', 'mssupplier.fsuppliername'];

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
                1 => 'mssupplier.fsuppliername', // Update kolom sorting ke nama supplier        2 => 'fusercreate',
                2 => 'fprdate',
                3 => 'fusercreate',
                4 => 'fuserupdate',
                5 => 'fclose',  // Ganti ke fclose
                6 => '', // Kolom 'Actions'
            ];

            if (isset($columns[$orderColumnIndex]) && $columns[$orderColumnIndex] !== null) {
                $query->orderBy($columns[$orderColumnIndex], $orderDir);
            } else {
                $query->orderBy('fprhid', 'desc');
            }

            // Pagination
            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            if ($length != -1) {
                $query->skip($start)->take($length);
            }

            // Select kolom yang dibutuhkan - PASTIKAN fclose ADA
            $records = $query->get(['fprhid', 'fprno', 'fprdate', 'fsupplier', 'fusercreate', 'fuserupdate', 'fclose', 'mssupplier.fsuppliername']);

            // Format data untuk DataTables
            $data = $records->map(function ($record) {
                return [
                    'fprno' => $record->fprno,
                    'fprdate' => $record->fprdate,
                    'fsuppliername' => $record->fsuppliername,
                    'display_user' => $record->fuserupdate ?: $record->fusercreate,
                    'fuserupdate' => $record->fuserupdate,
                    'fclose' => $record->fclose == '1' ? 'Done' : 'Not Done',
                    'fprhid' => $record->fprhid,
                    'DT_RowId' => 'row_'.$record->fprhid,
                ];
            });

            // Kirim response JSON
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
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
            $needle = trim((string) $branch);

            if (is_numeric($needle)) {
                $kodeCabang = DB::table('mscabang')
                    ->where('fcabangid', (int) $needle)
                    ->value('fcabangkode');
            } else {
                // cocokkan case-insensitive
                $kodeCabang = DB::table('mscabang')
                    ->whereRaw('LOWER(fcabangkode) = LOWER(?)', [$needle])
                    ->value('fcabangkode');

                if (! $kodeCabang) {
                    $kodeCabang = DB::table('mscabang')
                        ->whereRaw('LOWER(fcabangname) = LOWER(?)', [$needle])
                        ->value('fcabangkode');
                }
            }
        }

        if (! $kodeCabang) {
            $kodeCabang = 'NA'; // fallback
        }

        $prefix = sprintf('PR.%s.%s.%s.', trim($kodeCabang), $date->format('y'), $date->format('m'));

        return DB::transaction(function () use ($prefix) {
            $last = \App\Models\Tr_prh::where('fprno', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('fprno')
                ->first();

            $lastNum = 0;
            if ($last && ($pos = strrpos($last->fprno, '.')) !== false) {
                $lastNum = (int) substr($last->fprno, $pos + 1);
            }

            $next = str_pad((string) ($lastNum + 1), 4, '0', STR_PAD_LEFT);

            return $prefix.$next; // PR.JK.25.08.0001
        });
    }

    public function print(string $fprno)
    {
        $supplierSub = (new Supplier)->getTable();

        $hdr = Tr_prh::query()
            ->leftJoin("{$supplierSub} as s", 's.fsupplierid', '=', 'tr_prh.fsupplier')
            ->leftJoin('mscabang as c', 'c.fcabangkode', '=', 'tr_prh.fbranchcode')
            ->where('tr_prh.fprno', $fprno)
            ->first([
                'tr_prh.*',
                's.fsuppliername as supplier_name',
                'c.fcabangname as cabang_name',
            ]);

        abort_if(! $hdr, 404);

        $dt = Tr_prd::query()
            ->leftJoin('msprd as p', 'p.fprdid', '=', 'tr_prd.fprdcodeid')
            ->where('tr_prd.fprhcode', $hdr->fprno)  // ✅ fprhcode bukan fprhid
            ->orderBy('p.fprdname')
            ->get([
                'tr_prd.*',
                'p.fprdname as product_name',
                'p.fprdcode as product_code',
            ]);

        $fmt = fn ($d) => $d
          ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d F Y')
          : '-';

        return view('tr_prh.print', [
            'hdr' => $hdr,
            'dt' => $dt,
            'fmt' => $fmt,
            'company_name' => config('app.company_name', 'PT. DEMO VERSION'),
            'company_city' => config('app.company_city', 'Tangerang'),
        ]);
    }

    public function create(Request $request)
    {

        // Ambil SEMUA Supplier untuk dropdown filter
        $suppliers = Supplier::orderBy('fsuppliername', 'asc')
            ->get(['fsupplierid', 'fsuppliername']);

        $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

        $branch = DB::table('mscabang')
            ->when(is_numeric($raw), fn ($q) => $q->where('fcabangid', (int) $raw))
            ->when(
                ! is_numeric($raw),
                fn ($q) => $q
                    ->where('fcabangkode', $raw)
                    ->orWhere('fcabangname', $raw)
            )
            ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

        $canApproval = in_array('approvalpr', explode(',', session('user_restricted_permissions', '')));

        $fcabang = $branch->fcabangname ?? (string) $raw;   // untuk tampilan
        $fbranchcode = $branch->fcabangkode ?? (string) $raw;   // untuk hidden post (JK)

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
            'suppliers' => $suppliers,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'products' => $products,
            'filterSupplierId' => $request->query('filter_supplier_id'),
        ]);
    }

    public function store(Request $request)
    {
        // ===== VALIDATION =====
        $request->validate([
            'fprdate' => ['nullable', 'date'],
            'fneeddate' => ['nullable', 'date'],
            'fduedate' => ['nullable', 'date'],
            'fket' => ['nullable', 'string', 'max:300'],
            'fbranchcode' => ['nullable', 'string', 'max:20'],

            'fitemcode' => ['array'],
            'fitemcode.*' => ['nullable', 'string', 'max:50'],

            'fsatuan' => ['array'],
            'fsatuan.*' => ['nullable', 'string', 'max:20'],

            'fqty' => ['array'],
            'fqty.*' => ['nullable'],

            'fdesc' => ['array'],
            'fdesc.*' => ['nullable', 'string'],

            'fketdt' => ['array'],
            'fketdt.*' => ['nullable', 'string', 'max:50'],

            'fapproval' => ['nullable'],
        ], [
            'fitemcode.*.max' => 'Panjang kode produk maksimal 50 karakter.',
            'fsatuan.*.max' => 'Panjang satuan maksimal 20 karakter.',
            'fdesc.*.max' => 'Panjang deskripsi maksimal 300 karakter.',
            'fketdt.*.max' => 'Panjang keterangan detail maksimal 50 karakter.',
        ]);

        // ===== HEADER DATE + CODE =====
        $fprdate = $request->filled('fprdate')
          ? Carbon::parse($request->fprdate)->startOfDay()
          : now()->startOfDay();

        $branchFromForm = $request->input('fbranchcode');
        $fprno = $request->filled('fprno')
          ? $request->fprno
          : $this->generatetr_prh_Code($fprdate, $branchFromForm);

        $fneeddate = $request->filled('fneeddate') ? Carbon::parse($request->fneeddate)->startOfDay() : null;
        $fduedate = $request->filled('fduedate') ? Carbon::parse($request->fduedate)->startOfDay() : null;

        $authUser = auth('sysuser')->user();
        $userName = $authUser->fname ?? null;

        // ===== ARRAYS =====
        $codes = $request->input('fitemcode', []);
        $sats = $request->input('fsatuan', []);
        $qtys = $request->input('fqty', []);
        $descs = $request->input('fdesc', []);
        $ketdts = $request->input('fketdt', []);

        // ===== PRODUCT MAP =====
        $productMap = DB::table('msprd')
            ->whereIn('fprdcode', array_values(array_filter($codes)))
            ->select(
                'fprdid',
                'fprdcode',
                'fminstock',
                'fsatuankecil',
                'fsatuanbesar',
                'fqtykecil',     // rasio: 1 satuanbesar  = N satuankecil
                'fsatuanbesar2',
                'fqtykecil2',    // rasio: 1 satuanbesar2 = N satuankecil (varchar!)
            )
            ->get()
            ->keyBy('fprdcode');

        // ===== STOCK VALIDATION =====
        $validator = Validator::make([], []);
        foreach ($codes as $i => $codeRaw) {
            $code = trim($codeRaw ?? '');
            if ($code === '') {
                continue;
            }

            $max = (int) ($productMap[$code]->fminstock ?? 0);
            $qty = (int) ($qtys[$i] ?? 0);

            if ($max > 0 && $qty > $max) {
                $validator->errors()->add("fqty.$i", "Qty untuk produk $code tidak boleh melebihi stok ($max).");
            }
            if ($qty < 1) {
                $validator->errors()->add("fqty.$i", 'Qty minimal 1.');
            }
        }
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // ===== CHECK DETAIL EXISTENCE =====
        $hasValidDetail = false;
        $rowCount = max(count($codes), count($sats), count($qtys), count($descs), count($ketdts));
        for ($i = 0; $i < $rowCount; $i++) {
            $code = trim($codes[$i] ?? '');
            $sat = trim($sats[$i] ?? '');
            $qty = is_numeric($qtys[$i] ?? null) ? (int) $qtys[$i] : null;

            if ($code !== '' && $sat !== '' && is_numeric($qty) && $qty >= 1) {
                $hasValidDetail = true;
                break;
            }
        }
        if (! $hasValidDetail) {
            return back()->withInput()
                ->withErrors(['detail' => 'Minimal satu item detail dengan Kode, Satuan, dan Qty ≥ 1.']);
        }

        // ===== TRANSACTION =====
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
            $productMap
        ) {
            $isApproval = (int) ($request->input('fapproval', 0));

            // CREATE HEADER
            $tr_prh = Tr_prh::create([
                'fprno' => $fprno,
                'fprdate' => $fprdate,
                'fsupplier' => $request->fsupplier,
                'fprdin' => '0',
                'fclose' => '0',
                'fket' => $request->fket,
                'fbranchcode' => $request->fbranchcode,
                'fcreatedat' => now(),
                'fneeddate' => $fneeddate,
                'fduedate' => $fduedate,
                'fusercreate' => $userName,
                'fuserapproved' => $request->has('fuserapproved') ? $userName : null,
                'fdateapproved' => $request->has('fuserapproved') ? now() : null,
                'fupdatedat' => null,
                'fapproval' => $isApproval,
            ]);

            // CREATE DETAILS
            $detailRows = [];
            $now = now();
            $rowCount = max(count($codes), count($sats), count($qtys), count($descs), count($ketdts));

            for ($i = 0; $i < $rowCount; $i++) {
                $code = trim($codes[$i] ?? '');
                $sat = trim($sats[$i] ?? '');
                $qty = is_numeric($qtys[$i] ?? null) ? (int) $qtys[$i] : null;
                $desc = $descs[$i] ?? null;
                $ketdt = $ketdts[$i] ?? null;

                if ($code !== '' && $sat !== '' && is_numeric($qty) && $qty >= 1) {
                    $product = $productMap[$code] ?? null;
                    $productId = (int) ($product->fprdid ?? 0);
                    if ($productId === 0) {
                        continue;
                    }

                    // ===== KONVERSI SATUAN → SATUAN KECIL =====
                    $qtyKecil = $qty; // default: sudah satuan kecil

                    if ($product) {
                        if ($sat === $product->fsatuanbesar) {
                            // Contoh: 1 CTN = 10 PCS
                            $rasio = is_numeric($product->fqtykecil) ? (float) $product->fqtykecil : 1;
                            $qtyKecil = $qty * $rasio;
                        } elseif (! empty($product->fsatuanbesar2) && $sat === $product->fsatuanbesar2) {
                            // Contoh: 1 ROLL = 20 PCS (fqtykecil2 adalah varchar, harus is_numeric)
                            $rasio2 = is_numeric($product->fqtykecil2) ? (float) $product->fqtykecil2 : 1;
                            $qtyKecil = $qty * $rasio2;
                        }
                        // Kalau sat === fsatuankecil → tidak perlu konversi
                    }

                    $detailRows[] = [
                        'fprhid' => $tr_prh->fprhid,
                        'fprdcodeid' => $productId,
                        'fprdcode' => $product->fprdcode ?? '', // nama produk dari msprd.fprdname
                        'fqty' => (int) $qty,
                        'fqtyremain' => $qtyKecil,
                        'fprice' => 0,
                        'fketdt' => $ketdt,
                        'fcreatedat' => $now,
                        'fsatuan' => $sat,
                        'fdesc' => $desc,
                        'fusercreate' => $userName,
                        'fprno' => $tr_prh->fprno,
                    ];
                }
            }

            Tr_prd::insert($detailRows);

            // UPDATE STOK
            foreach ($codes as $i => $codeRaw) {
                $code = trim($codeRaw ?? '');
                $qty = (int) ($qtys[$i] ?? 0);
                if ($code !== '' && $qty > 0) {
                    DB::table('msprd')
                        ->where('fprdcode', $code)
                        ->update([
                            'fminstock' => DB::raw("CAST(fminstock AS INTEGER) - $qty"),
                            'fupdatedat' => now(),
                        ]);
                }
            }

            // KIRIM EMAIL JIKA APPROVAL
            if ($isApproval === 1) {
                $dt = Tr_prd::query()
                    ->leftJoin('msprd as p', 'p.fprdid', '=', 'tr_prd.fprdcodeid')
                    ->where('tr_prd.fprhid', $tr_prh->fprhid)
                    ->orderBy('p.fprdname')
                    ->get([
                        'tr_prd.*',
                        'p.fprdname as product_name',
                        'p.fprdcode as product_code',
                        'p.fminstock as stock',
                    ]);

                $productNameList = $dt->pluck('product_name')->implode(', ');
                $approver = auth('sysuser')->user()->fname ?? $tr_prh->fusercreate ?? 'System';

                Mail::to('vierybiliam8@gmail.com')
                    ->send(new ApprovalEmail($tr_prh, $dt, $productNameList, $approver, 'Permintaan Pembelian (PR)'));
            }
        });

        return redirect()->route('tr_prh.create')
            ->with('success', 'Permintaan pembelian berhasil ditambahkan.');
    }

    public function edit(Request $request, $fprhid)
    {
        $suppliers = Supplier::orderBy('fsuppliername', 'asc')
            ->get(['fsupplierid', 'fsuppliername']);

        $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

        $branch = DB::table('mscabang')
            ->when(is_numeric($raw), fn ($q) => $q->where('fcabangid', (int) $raw))
            ->when(! is_numeric($raw), fn ($q) => $q
                ->where('fcabangkode', $raw)
                ->orWhere('fcabangname', $raw))
            ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

        $fcabang = $branch->fcabangname ?? (string) $raw;   // tampilan
        $fbranchcode = $branch->fcabangkode ?? (string) $raw;   // hidden post

        $tr_prh = Tr_prh::with(['details' => function ($q) {
            $q->leftJoin('msprd as p', 'p.fprdid', '=', 'tr_prd.fprdcodeid') // tr_prd.fprdcodeid = ID produk
                ->orderBy('p.fprdname')
                ->select(
                    'tr_prd.*',
                    'p.fprdcode as product_code',   // <- kode untuk tampilan
                    'p.fprdname  as product_name'   // <- nama untuk tampilan
                );
        }])->findOrFail($fprhid);

        $fprhid = (int) $tr_prh->fprhid;

        $blockedByPO = DB::table('tr_pod')
            ->where('fnou', $fprhid)
            ->whereNotNull('fnou')
            ->exists();

        $details = DB::table('tr_prd as d')
            ->leftJoin('msprd as p', 'p.fprdid', '=', 'd.fprdcodeid')
            ->leftJoin(DB::raw('(
        SELECT frefdtid, fprdid, SUM(fqtykecil) AS fqtypo
        FROM tr_pod
        WHERE frefdtid IS NOT NULL AND frefdtid > 0
        GROUP BY frefdtid, fprdid
    ) as o'), function ($join) use ($fprhid) {
                $join->whereRaw('o.frefdtid = ?', [$fprhid])   // ← pakai whereRaw
                    ->on('o.fprdid', '=', 'p.fprdid');
            })
            ->where('d.fprhid', $fprhid)
            ->select([
                'd.*',
                'p.fprdname',
                'p.fprdcode as fprdcode_master',
                DB::raw('COALESCE(
            CASE
                WHEN d.fsatuan = p.fsatuanbesar
                THEN o.fqtypo / NULLIF(p.fqtykecil::numeric, 0)
                ELSE o.fqtypo
            END, 0
        ) AS fqtypo'),
            ])
            ->get();

        // Map ke savedItems (agar cocok dengan table di Blade yang biasa kamu pakai)
        $savedItems = $details->map(function ($d) {
            return [
                'uid' => (string) \Illuminate\Support\Str::uuid(),
                'fprdid' => (int) ($d->fprdcodeid ?? 0),
                'fitemcode' => (string) ($d->fprdcode_master ?? ''),  // dari fprdcode_master
                'fitemname' => (string) ($d->fprdname ?? ''),
                'fsatuan' => (string) ($d->fsatuan ?? ''),
                'fqty' => (float) ($d->fqty ?? 0),
                'fqtypo' => (float) ($d->fqtypo ?? 0),   // ← sekarang dari $details
                'fdesc' => (string) ($d->fdesc ?? ''),
                'fketdt' => (string) ($d->fketdt ?? ''),
                'fprice' => (float) ($d->fprice ?? 0),
                'fdisc' => (float) ($d->fdisc ?? 0),
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
                $p->fprdcodeid => [
                    'id' => $p->fprdid,  // ⬅️ penting
                    'name' => $p->fprdname,
                    'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
                    'stock' => $p->fminstock ?? 0,
                ],
            ];
        })->toArray();

        return view('tr_prh.edit', [
            'suppliers' => $suppliers,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'products' => $products,
            'productMap' => $productMap, // jika dipakai di Blade
            'tr_prh' => $tr_prh,     // <<— PENTING
            'savedItems' => $savedItems,   // <— tambahkan ini
            'blockedByPO' => $blockedByPO,
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'action' => 'edit',
        ]);
    }

    public function view(Request $request, $fprhid)
    {
        $suppliers = Supplier::orderBy('fsuppliername', 'asc')
            ->get(['fsupplierid', 'fsuppliername']);

        $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

        $branch = DB::table('mscabang')
            ->when(is_numeric($raw), fn ($q) => $q->where('fcabangid', (int) $raw))
            ->when(! is_numeric($raw), fn ($q) => $q
                ->where('fcabangkode', $raw)
                ->orWhere('fcabangname', $raw))
            ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

        $fcabang = $branch->fcabangname ?? (string) $raw;   // tampilan
        $fbranchcode = $branch->fcabangkode ?? (string) $raw;   // hidden post

        $tr_prh = Tr_prh::with(['details' => function ($q) {
            $q->leftJoin('msprd as p', 'p.fprdid', '=', 'tr_prd.fprdcodeid') // tr_prd.fprdcodeid = ID produk
                ->orderBy('p.fprdname')
                ->select(
                    'tr_prd.*',
                    'p.fprdcode as product_code',   // <- kode untuk tampilan
                    'p.fprdname  as product_name'   // <- nama untuk tampilan
                );
        }])->findOrFail($fprhid);

        $fprhid = (int) $tr_prh->fprhid;

        $details = DB::table('tr_prd as d')
            ->leftJoin('msprd as p', 'p.fprdid', '=', 'd.fprdcodeid')
            ->leftJoin(DB::raw('(
        SELECT frefdtid, fprdid, SUM(fqtykecil) AS fqtypo
        FROM tr_pod
        WHERE frefdtid IS NOT NULL AND frefdtid > 0
        GROUP BY frefdtid, fprdid
    ) as o'), function ($join) use ($fprhid) {
                $join->whereRaw('o.frefdtid = ?', [$fprhid])
                    ->on('o.fprdid', '=', 'p.fprdid');
            })
            ->where('d.fprhid', $fprhid)
            ->select([
                'd.*',
                'p.fprdname',
                'p.fprdcode as fprdcode_master',
                DB::raw('COALESCE(
            CASE
                WHEN d.fsatuan = p.fsatuanbesar
                THEN o.fqtypo / NULLIF(p.fqtykecil::numeric, 0)
                ELSE o.fqtypo
            END, 0
        ) AS fqtypo'),
            ])
            ->get();

        // Map ke savedItems (agar cocok dengan table di Blade yang biasa kamu pakai)
        $savedItems = $details->map(function ($d) {
            return [
                'uid' => (string) \Illuminate\Support\Str::uuid(),
                'fprdid' => (int) ($d->fprdcodeid ?? 0),
                'fitemcode' => (string) ($d->fprdcode_master ?? ''),  // dari fprdcode_master
                'fitemname' => (string) ($d->fprdname ?? ''),
                'fsatuan' => (string) ($d->fsatuan ?? ''),
                'fqty' => (float) ($d->fqty ?? 0),
                'fqtypo' => (float) ($d->fqtypo ?? 0),   // ← sekarang dari $details
                'fdesc' => (string) ($d->fdesc ?? ''),
                'fketdt' => (string) ($d->fketdt ?? ''),
                'fprice' => (float) ($d->fprice ?? 0),
                'fdisc' => (float) ($d->fdisc ?? 0),
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
                $p->fprdcodeid => [
                    'id' => $p->fprdid,  // ⬅️ penting
                    'name' => $p->fprdname,
                    'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
                    'stock' => $p->fminstock ?? 0,
                ],
            ];
        })->toArray();

        return view('tr_prh.view', [
            'suppliers' => $suppliers,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'products' => $products,
            'productMap' => $productMap, // jika dipakai di Blade
            'tr_prh' => $tr_prh,     // <<— PENTING
            'savedItems' => $savedItems,   // <— tambahkan ini
            'filterSupplierId' => $request->query('filter_supplier_id'),
        ]);
    }

    public function update(Request $request, int $fprhid)
    {
        // ===== 1) AMBIL HEADER DULU =====
        $header = Tr_prh::where('fprhid', $fprhid)->firstOrFail();
        $fprhid = (int) $header->fprhid;

        // ===== 2) VALIDASI INPUT =====
        $request->validate([
            'fprdate' => ['nullable', 'date'],
            'fneeddate' => ['nullable', 'date'],
            'fduedate' => ['nullable', 'date'],
            'fket' => ['nullable', 'string', 'max:300'],
            'fbranchcode' => ['nullable', 'string', 'max:20'],

            'fitemcode' => ['array'],
            'fitemcode.*' => ['nullable', 'string', 'max:50'],
            'fprdid' => ['array'],
            'fprdid.*' => ['nullable', 'integer', 'min:1'],
            'fsatuan' => ['array'],
            'fsatuan.*' => ['nullable', 'string', 'max:20'],
            'fqty' => ['array'],
            'fqty.*' => ['nullable', 'numeric', 'min:1'],
            'fdesc' => ['array'],
            'fdesc.*' => ['nullable', 'string'],
            'fketdt' => ['array'],
            'fketdt.*' => ['nullable', 'string', 'max:50'],

            'fapproval' => ['nullable', 'boolean'],
        ], [
            'fitemcode.*.max' => 'Panjang kode produk maksimal 50 karakter.',
            'fprdid.*.integer' => 'ID produk tidak valid.',
            'fprdid.*.min' => 'ID produk harus lebih besar dari 0.',
            'fsatuan.*.max' => 'Panjang satuan maksimal 20 karakter.',
            'fdesc.*.max' => 'Panjang deskripsi maksimal 300 karakter.',
            'fketdt.*.max' => 'Panjang keterangan detail maksimal 50 karakter.',
        ]);

        // ===== 3) PARSE TANGGAL =====
        $fprdate = $request->filled('fprdate')
          ? \Carbon\Carbon::parse($request->fprdate)->startOfDay()
          : $header->fprdate;

        $fneeddate = $request->filled('fneeddate')
          ? \Carbon\Carbon::parse($request->fneeddate)->startOfDay()
          : $header->fneeddate;

        $fduedate = $request->filled('fduedate')
          ? \Carbon\Carbon::parse($request->fduedate)->startOfDay()
          : $header->fduedate;

        // ===== 4) KUMPULKAN ARRAY DETAIL DARI FORM =====
        $codes = $request->input('fitemcode', []);
        $idsIn = $request->input('fprdid', []);
        $sats = $request->input('fsatuan', []);
        $qtys = $request->input('fqty', []);
        $descs = $request->input('fdesc', []);
        $ketdts = $request->input('fketdt', []);

        // ===== 5) MAP KODE → DETAIL PRODUK =====
        $products = DB::table('msprd')
            ->whereIn('fprdcode', array_values(array_filter($codes)))
            ->select(
                'fprdcode',
                'fprdid',
                'fminstock',
                'fsatuankecil',
                'fsatuanbesar',
                'fqtykecil',     // rasio: 1 satuanbesar  = N satuankecil (numeric)
                'fsatuanbesar2',
                'fqtykecil2',    // rasio: 1 satuanbesar2 = N satuankecil (varchar!)
            )
            ->get()
            ->keyBy('fprdcode');

        $codeIdMap = $products->pluck('fprdid', 'fprdcode');
        $productMap = $products;

        // ===== 6) VALIDASI STOK =====
        foreach ($codes as $i => $codeStr) {
            $qty = (int) ($qtys[$i] ?? 0);
            if ($qty < 1) {
                return back()->withInput()->withErrors([
                    "fqty.$i" => 'Qty minimal 1.',
                ]);
            }
            $product = $productMap[$codeStr] ?? null;
            $max = $product ? (int) $product->fminstock : 0;
            if ($max > 0 && $qty > $max) {
                return back()->withInput()->withErrors([
                    "fqty.$i" => "Qty untuk produk $codeStr tidak boleh melebihi stok ($max).",
                ]);
            }
        }

        // ===== 7) SUSUN BATCH DETAIL =====
        $detailRows = [];
        $now = now();
        $rowCount = max(
            count($codes),
            count($idsIn),
            count($sats),
            count($qtys),
            count($descs),
            count($ketdts)
        );

        for ($i = 0; $i < $rowCount; $i++) {
            $codeStr = trim((string) ($codes[$i] ?? ''));
            $idForm = (int) ($idsIn[$i] ?? 0);
            $prodId = (int) ($codeIdMap[$codeStr] ?? 0);

            // Fallback: kalau mapping dari kode gagal, pakai ID dari hidden input
            if ($prodId === 0 && $idForm > 0) {
                $prodId = $idForm;
            }

            $sat = trim((string) ($sats[$i] ?? ''));
            $qty = (int) ($qtys[$i] ?? 0);
            $desc = $descs[$i] ?? null;
            $ket = $ketdts[$i] ?? null;

            // ===== KONVERSI SATUAN → SATUAN KECIL =====
            $qtyKecil = $qty; // default: sudah satuan kecil

            $product = $productMap[$codeStr] ?? null;
            if ($product) {
                if ($sat === $product->fsatuanbesar) {
                    // Contoh: 1 CTN = 10 PCS
                    $rasio = is_numeric($product->fqtykecil) ? (float) $product->fqtykecil : 1;
                    $qtyKecil = $qty * $rasio;
                } elseif (! empty($product->fsatuanbesar2) && $sat === $product->fsatuanbesar2) {
                    // Contoh: 1 ROLL = 20 PCS (fqtykecil2 adalah varchar, harus is_numeric)
                    $rasio2 = is_numeric($product->fqtykecil2) ? (float) $product->fqtykecil2 : 1;
                    $qtyKecil = $qty * $rasio2;
                }
                // Kalau sat === fsatuankecil → tidak perlu konversi
            }

            // Hanya terima baris valid
            if ($prodId > 0 && $sat !== '' && $qty >= 1) {
                $detailRows[] = [
                    'fprhid' => $fprhid,
                    'fprdcodeid' => $prodId,
                    'fprdcode' => $product->fprdcode ?? '', // nama produk dari msprd.fprdname
                    'fqty' => $qty,
                    'fqtyremain' => $qtyKecil,
                    'fprice' => 0,
                    'fketdt' => $ket,
                    'fcreatedat' => $now,
                    'fsatuan' => $sat,
                    'fdesc' => $desc,
                    'fuserupdate' => (auth('sysuser')->user()->fname ?? Auth::user()->fname ?? 'system'),
                    'fprno' => $header->fprno,
                ];
            }
        }

        if (empty($detailRows)) {
            return back()->withInput()->withErrors([
                'detail' => 'Minimal satu item detail valid (ID Produk, Satuan, Qty ≥ 1).',
            ]);
        }

        // ===== 8) APPROVAL =====
        $approveNow = $request->boolean('fapproval');
        $setApproval = [];
        if ($approveNow && (empty($header->fuserapproved) && (int) $header->fapproval !== 1)) {
            $setApproval = [
                'fapproval' => 1,
                'fuserapproved' => (auth('sysuser')->user()->fname ?? Auth::user()->fname ?? 'system'),
                'fdateapproved' => now(),
            ];
        }

        // ===== 9) TRANSAKSI =====
        DB::transaction(function () use (
            $request,
            $header,
            $fprhid,
            $fprdate,
            $fneeddate,
            $fduedate,
            $detailRows,
            $codes,
            $qtys,
            $setApproval
        ) {
            // Update header
            Tr_prh::where('fprhid', $header->fprhid)->update(array_merge([
                'fprdate' => $fprdate,
                'fsupplier' => $request->filled('fsupplier') ? (int) $request->fsupplier : $header->fsupplier,
                'fprdin' => '0',
                'fclose' => $request->has('fclose') ? '1' : '0',
                'fket' => $request->fket,
                'fbranchcode' => $request->fbranchcode,
                'fneeddate' => $fneeddate,
                'fduedate' => $fduedate,
                'fuserupdate' => (auth('sysuser')->user()->fname ?? Auth::user()->fname ?? 'system'),
                'fupdatedat' => now(),
            ], $setApproval));

            // Hapus semua detail lama
            DB::table('tr_prd')->where('fprhid', $fprhid)->delete();

            // Insert ulang detail baru
            DB::table('tr_prd')->insert($detailRows);

            // Update stok
            foreach ($codes as $i => $codeStr) {
                $qty = (int) ($qtys[$i] ?? 0);
                if ($qty > 0 && $codeStr !== '') {
                    DB::table('msprd')
                        ->where('fprdcode', $codeStr)
                        ->update([
                            'fminstock' => DB::raw("CAST(fminstock AS INTEGER) - {$qty}"),
                            'fupdatedat' => now(),
                        ]);
                }
            }
        });

        // ===== 10) SELESAI =====
        return redirect()
            ->route('tr_prh.index')
            ->with('success', 'Permintaan pembelian berhasil diperbarui.');
    }

    public function delete(Request $request, $fprhid)
    {
        $suppliers = Supplier::orderBy('fsuppliername', 'asc')
            ->get(['fsupplierid', 'fsuppliername']);

        $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

        $branch = DB::table('mscabang')
            ->when(is_numeric($raw), fn ($q) => $q->where('fcabangid', (int) $raw))
            ->when(! is_numeric($raw), fn ($q) => $q
                ->where('fcabangkode', $raw)
                ->orWhere('fcabangname', $raw))
            ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

        $fcabang = $branch->fcabangname ?? (string) $raw;   // tampilan
        $fbranchcode = $branch->fcabangkode ?? (string) $raw;   // hidden post

        $tr_prh = Tr_prh::with(['details' => function ($q) {
            $q->leftJoin('msprd as p', 'p.fprdid', '=', 'tr_prd.fprdcodeid') // tr_prd.fprdcodeid = ID produk
                ->orderBy('p.fprdname')
                ->select(
                    'tr_prd.*',
                    'p.fprdcode as product_code',   // <- kode untuk tampilan
                    'p.fprdname  as product_name'   // <- nama untuk tampilan
                );
        }])->findOrFail($fprhid);

        $fprhid = (int) $tr_prh->fprhid;

        $details = DB::table('tr_prd as d')
            ->leftJoin('msprd as p', 'p.fprdid', '=', 'd.fprdcodeid')
            ->leftJoin(DB::raw('(
        SELECT frefdtid, fprdid, SUM(fqtykecil) AS fqtypo
        FROM tr_pod
        WHERE frefdtid IS NOT NULL AND frefdtid > 0
        GROUP BY frefdtid, fprdid
    ) as o'), function ($join) use ($fprhid) {
                $join->whereRaw('o.frefdtid = ?', [$fprhid])
                    ->on('o.fprdid', '=', 'p.fprdid');
            })
            ->where('d.fprhid', $fprhid)
            ->select([
                'd.*',
                'p.fprdname',
                'p.fprdcode as fprdcode_master',
                DB::raw('COALESCE(
            CASE
                WHEN d.fsatuan = p.fsatuanbesar
                THEN o.fqtypo / NULLIF(p.fqtykecil::numeric, 0)
                ELSE o.fqtypo
            END, 0
        ) AS fqtypo'),
            ])
            ->get();

        $savedItems = $details->map(function ($d) {
            return [
                'uid' => (string) \Illuminate\Support\Str::uuid(),
                'fprdid' => (int) ($d->fprdcodeid ?? 0),
                'fitemcode' => (string) ($d->fprdcode_master ?? ''),
                'fitemname' => (string) ($d->fprdname ?? ''),
                'fsatuan' => (string) ($d->fsatuan ?? ''),
                'fqty' => (float) ($d->fqty ?? 0),
                'fqtypo' => (float) ($d->fqtypo ?? 0),   // ← sekarang terisi
                'fdesc' => (string) ($d->fdesc ?? ''),
                'fketdt' => (string) ($d->fketdt ?? ''),
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
                $p->fprdcodeid => [
                    'id' => $p->fprdid,  // ⬅️ penting
                    'name' => $p->fprdname,
                    'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
                    'stock' => $p->fminstock ?? 0,
                ],
            ];
        })->toArray();

        return view('tr_prh.edit', [
            'suppliers' => $suppliers,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'products' => $products,
            'productMap' => $productMap, // jika dipakai di Blade
            'tr_prh' => $tr_prh,     // <<— PENTING
            'savedItems' => $savedItems,   // <— tambahkan ini
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'action' => 'delete',
        ]);
    }

    public function destroy($fprhid)
    {
        try {
            $tr_prh = Tr_prh::findOrFail($fprhid);
            $tr_prh->delete();

            return redirect()->route('tr_prh.index')->with('success', 'Data Permintaan Pembelian '.$tr_prh->fprno.' berhasil dihapus.');
        } catch (\Exception $e) {
            // Jika terjadi kesalahan saat menghapus, kembali ke halaman delete dengan pesan error
            return redirect()->route('tr_prh.delete', $fprhid)->with('error', 'Gakey: gal menghapus data: '.$e->getMessage());
        }
    }
}
