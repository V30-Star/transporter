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
                ->leftJoin('mssupplier', 'tr_prh.fsupplier', '=', 'mssupplier.fsuppliercode');
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
            ->leftJoin("{$supplierSub} as s", 's.fsuppliercode', '=', 'tr_prh.fsupplier')
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
            ->where('tr_prd.fprno', $hdr->fprno)
            ->orderBy('p.fprdname')
            ->get([
                'tr_prd.*',
                'p.fprdname as product_name',
                'p.fprdcode as product_code',
                'p.fminstock as stock',
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
        $branchInfo = $this->getCurrentBranchInfo();
        $canApproval = in_array('approvalpr', explode(',', session('user_restricted_permissions', '')));
        $suppliers = $this->getSuppliers();
        $fbranchcode = $branchInfo['fbranchcode'];

        $newtr_prh_code = $this->generatetr_prh_Code(now(), $fbranchcode);
        $products = $this->getProducts();
        $productMap = $this->buildProductMap($products);

        return view('tr_prh.create', [
            'newtr_prh_code' => $newtr_prh_code,
            'perms' => ['can_approval' => $canApproval],
            'suppliers' => $suppliers,
            'fcabang' => $branchInfo['fcabang'],
            'fbranchcode' => $fbranchcode,
            'products' => $products,
            'productMap' => $productMap,
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
                'fqtykecil',
                'fsatuanbesar2',
                'fqtykecil2',
            )
            ->get()
            ->keyBy('fprdcode');

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
                    $qtyKecil = $this->convertQtyToSmallUnit($product, $sat, $qty);

                    $detailRows[] = [
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

            // KIRIM EMAIL JIKA APPROVAL
            if ($isApproval === 1) {
                $dt = Tr_prd::query()
                    ->leftJoin('msprd as p', 'p.fprdid', '=', 'tr_prd.fprdcodeid')
                    ->where('tr_prd.fprno', $tr_prh->fprno)
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

    public function view(Request $request, $fprhid)
    {
        $branchInfo = $this->getCurrentBranchInfo();

        $tr_prh = Tr_prh::with(['details' => function ($q) {
            $q->leftJoin('msprd as p', 'p.fprdid', '=', 'tr_prd.fprdcodeid')
                ->orderBy('p.fprdname')
                ->select(
                    'tr_prd.*',
                    'p.fprdcode as product_code',
                    'p.fprdname as product_name'
                );
        }])
            // PINDAHKAN KE SINI (Query Header)
            ->leftJoin('mssupplier as s', 's.fsuppliercode', '=', 'tr_prh.fsupplier')
            ->select('tr_prh.*', 's.fsuppliername')
            ->findOrFail($fprhid);

        $suppliers = $this->getSuppliers();
        $existingPO = $this->getExistingPurchaseOrders($tr_prh->fprno);
        $blockedByPO = $existingPO->isNotEmpty();

        $details = $this->getPrDetailsWithPoUsage($tr_prh->fprno);
        $savedItems = $this->buildSavedItems($details, true);
        $products = $this->getProducts();
        $productMap = $this->buildProductMap($products);

        return view('tr_prh.view', [
            'suppliers' => $suppliers,
            'fcabang' => $branchInfo['fcabang'],
            'fbranchcode' => $branchInfo['fbranchcode'],
            'products' => $products,
            'productMap' => $productMap,
            'tr_prh' => $tr_prh,
            'savedItems' => $savedItems,
            'blockedByPO' => $blockedByPO,
            'existingPO' => $existingPO,
            'filterSupplierId' => $request->query('filter_supplier_id'),
        ]);
    }

    public function edit(Request $request, $fprhid)
    {
        $branchInfo = $this->getCurrentBranchInfo();

        $tr_prh = Tr_prh::with(['details' => function ($q) {
            $q->leftJoin('msprd as p', 'p.fprdid', '=', 'tr_prd.fprdcodeid')
                ->orderBy('p.fprdname')
                ->select(
                    'tr_prd.*',
                    'p.fprdcode as product_code',
                    'p.fprdname as product_name'
                );
        }])
            // PINDAHKAN KE SINI (Query Header)
            ->leftJoin('mssupplier as s', 's.fsuppliercode', '=', 'tr_prh.fsupplier')
            ->select('tr_prh.*', 's.fsuppliername')
            ->findOrFail($fprhid);

        $suppliers = $this->getSuppliers();
        $existingPO = $this->getExistingPurchaseOrders($tr_prh->fprno);
        $blockedByPO = $existingPO->isNotEmpty();

        $details = $this->getPrDetailsWithPoUsage($tr_prh->fprno);
        $savedItems = $this->buildSavedItems($details, true);
        $products = $this->getProducts();
        $productMap = $this->buildProductMap($products);

        return view('tr_prh.edit', [
            'suppliers' => $suppliers,
            'fcabang' => $branchInfo['fcabang'],
            'fbranchcode' => $branchInfo['fbranchcode'],
            'products' => $products,
            'productMap' => $productMap,
            'tr_prh' => $tr_prh,
            'savedItems' => $savedItems,
            'blockedByPO' => $blockedByPO,
            'existingPO' => $existingPO,
            'usageLockMessage' => $blockedByPO ? $this->getUsageLockMessage($tr_prh) : null,
            'action' => 'edit',
            'filterSupplierId' => $request->query('filter_supplier_id'),
        ]);
    }

    public function update(Request $request, int $fprhid)
    {
        // ===== 1) AMBIL HEADER DULU =====
        $header = Tr_prh::where('fprhid', $fprhid)->firstOrFail();

        if ($message = $this->getUsageLockMessage($header)) {
            return redirect()->route('tr_prh.index')->with('error', $message);
        }

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

        // ===== 7) AMBIL DATA LAMA UNTUK VALDASI QTY-PO & STOK =====
        $oldDetails = DB::table('tr_prd')->where('fprno', $header->fprno)->get()->keyBy('fprdid');

        // Hitung QTY PO per detail ID
        $poUsage = DB::table('tr_pod')
            ->whereIn('frefdtid', $oldDetails->keys())
            ->select('frefdtid', DB::raw('SUM(fqtykecil) as total_used'))
            ->groupBy('frefdtid')
            ->pluck('total_used', 'frefdtid');

        // ===== 8) VALIDASI QTY TERHADAP PO & STOK =====
        $errors = new \Illuminate\Support\MessageBag;

        foreach ($codes as $i => $codeStr) {
            $code = trim($codeStr);
            $qty = (int) ($qtys[$i] ?? 0);
            $did = (int) ($idsIn[$i] ?? 0);
            $sat = trim($sats[$i] ?? '');

            if ($did > 0 && isset($oldDetails[$did])) {
                $old = $oldDetails[$did];
                $used = (float) ($poUsage[$did] ?? 0);

                $product = $productMap[$code] ?? null;
                $qtyKecil = $this->convertQtyToSmallUnit($product, $sat, $qty);

                if ($used > 0) {
                    if (trim($old->fprdcode) !== $code) {
                        $errors->add("fitemcode.$i", "Produk \"$code\" tidak boleh diubah karena sudah ada PO terkait.");
                    }
                    if (trim($old->fsatuan) !== $sat) {
                        $errors->add("fsatuan.$i", 'Satuan tidak boleh diubah karena sudah ada PO terkait.');
                    }
                    if ($qtyKecil < $used) {
                        $errors->add("fqty.$i", "Qty tidak boleh kurang dari yang sudah diproses ke PO ($used).");
                    }
                }
            }

            $stockChanges[$code] = ($stockChanges[$code] ?? 0) - $qty;
        }

        if ($errors->isNotEmpty()) {
            return back()->withErrors($errors)->withInput();
        }

        // ===== 9) SUSUN BATCH DETAIL & TRANSACTION =====
        DB::transaction(function () use (
            $request,
            $header,
            $fprdate,
            $fneeddate,
            $fduedate,
            $codes,
            $idsIn,
            $sats,
            $qtys,
            $descs,
            $ketdts,
            $productMap,
            $oldDetails

        ) {
            $now = now();
            $userName = (auth('sysuser')->user()->fname ?? Auth::user()->fname ?? 'system');

            // 1. Update Header
            $approveNow = $request->boolean('fapproval');
            $headerUpdate = [
                'fprdate' => $fprdate,
                'fsupplier' => $request->filled('fsupplier') ? trim((string) $request->fsupplier) : $header->fsupplier,
                'fket' => $request->fket,
                'fbranchcode' => $request->fbranchcode,
                'fneeddate' => $fneeddate,
                'fduedate' => $fduedate,
                'fuserupdate' => $userName,
                'fupdatedat' => $now,
                'fclose' => $request->has('fclose') ? '1' : '0',
            ];
            if ($approveNow && (empty($header->fuserapproved) && (int) $header->fapproval !== 1)) {
                $headerUpdate['fapproval'] = 1;
                $headerUpdate['fuserapproved'] = $userName;
                $headerUpdate['fdateapproved'] = $now;
            }
            Tr_prh::where('fprhid', $header->fprhid)->update($headerUpdate);

            // 2. Update/Insert Details
            $submittedIds = array_filter($idsIn);

            // Hapus detail yang tidak ada di form (ORPHANS)
            // Pastikan tidak menghapus yang sudah ada PO (seharusnya sudah dicek di frontend/validasi)
            DB::table('tr_prd')
                ->where('fprno', $header->fprno)
                ->whereNotIn('fprdid', $submittedIds)
                ->delete();

            foreach ($codes as $i => $codeStr) {
                $code = trim($codeStr);
                if ($code === '') {
                    continue;
                }

                $did = (int) ($idsIn[$i] ?? 0);
                $sat = trim($sats[$i] ?? '');
                $qty = (int) ($qtys[$i] ?? 0);
                $desc = $descs[$i] ?? null;
                $ket = $ketdts[$i] ?? null;
                $product = $productMap[$code] ?? null;
                $prodId = (int) ($product->fprdid ?? 0);

                // Konversi fqtyremain
                $qtyKecil = $this->convertQtyToSmallUnit($product, $sat, $qty);

                $data = [
                    'fprdcodeid' => $prodId,
                    'fprdcode' => $code,
                    'fqty' => $qty,
                    'fqtyremain' => $qtyKecil,
                    'fketdt' => $ket,
                    'fsatuan' => $sat,
                    'fdesc' => $desc,
                    'fuserupdate' => $userName,
                    'fupdatedat' => $now,
                    'fprno' => $header->fprno,
                ];

                if ($did > 0 && isset($oldDetails[$did])) {
                    DB::table('tr_prd')->where('fprdid', $did)->update($data);
                } else {
                    $data['fcreatedat'] = $now;
                    $data['fusercreate'] = $userName;
                    DB::table('tr_prd')->insert($data);
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
        $branchInfo = $this->getCurrentBranchInfo();

        $tr_prh = Tr_prh::with(['details' => function ($q) {
            $q->leftJoin('msprd as p', 'p.fprdid', '=', 'tr_prd.fprdcodeid')
                ->orderBy('p.fprdname')
                ->select(
                    'tr_prd.*',
                    'p.fprdcode as product_code',
                    'p.fprdname as product_name'
                );
        }])
            // PINDAHKAN KE SINI (Query Header)
            ->leftJoin('mssupplier as s', 's.fsuppliercode', '=', 'tr_prh.fsupplier')
            ->select('tr_prh.*', 's.fsuppliername', 's.fsuppliercode')
            ->findOrFail($fprhid);

        $suppliers = $this->getSuppliers();
        $details = $this->getPrDetailsWithPoUsage($tr_prh->fprno);
        $savedItems = $this->buildSavedItems($details);
        $products = $this->getProducts();
        $productMap = $this->buildProductMap($products);
        $existingPO = $this->getExistingPurchaseOrders($tr_prh->fprno);

        return view('tr_prh.edit', [
            'suppliers' => $suppliers,
            'fcabang' => $branchInfo['fcabang'],
            'fbranchcode' => $branchInfo['fbranchcode'],
            'products' => $products,
            'productMap' => $productMap,
            'tr_prh' => $tr_prh,
            'savedItems' => $savedItems,
            'existingPO' => $existingPO,
            'blockedByPO' => DB::table('tr_pod')->where('frefdtno', $tr_prh->fprno)->exists(),
            'usageLockMessage' => $this->getUsageLockMessage($tr_prh),
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'action' => 'delete',
        ]);
    }

    public function destroy($fprhid)
    {
        try {
            $tr_prh = Tr_prh::findOrFail($fprhid);

            if ($message = $this->getUsageLockMessage($tr_prh)) {
                return redirect()->route('tr_prh.index')->with('error', $message);
            }

            DB::transaction(function () use ($tr_prh) {
                DB::table('tr_prd')
                    ->where('fprno', $tr_prh->fprno)
                    ->delete();
                $tr_prh->delete();
            });

            return redirect()->route('tr_prh.index')->with('success', 'Data Permintaan Pembelian '.$tr_prh->fprno.' berhasil dihapus.');
        } catch (\Exception $e) {
            // Jika terjadi kesalahan saat menghapus, kembali ke halaman delete dengan pesan error
            return redirect()->route('tr_prh.delete', $fprhid)->with('error', 'Gakey: gal menghapus data: '.$e->getMessage());
        }
    }

    private function getUsageLockMessage(Tr_prh $header): ?string
    {
        $usedBy = DB::table('tr_pod as pod')
            ->join('tr_poh as poh', 'poh.fpono', '=', 'pod.fpono')
            ->where('pod.frefdtno', $header->fprno)
            ->select('poh.fpono')
            ->distinct()
            ->orderBy('poh.fpono')
            ->pluck('poh.fpono');

        if ($usedBy->isEmpty()) {
            return null;
        }

        return 'Permintaan Pembelian '.$header->fprno.' tidak dapat diubah atau dihapus karena sudah digunakan pada Order Pembelian: '.$usedBy->implode(', ').'.';
    }

    private function getSuppliers()
    {
        return Supplier::orderBy('fsuppliername', 'asc')
            ->get(['fsupplierid', 'fsuppliercode', 'fsuppliername']);
    }

    private function getProducts()
    {
        return Product::select(
            'fprdid',
            'fprdcode',
            'fprdname',
            'fsatuankecil',
            'fsatuanbesar',
            'fsatuanbesar2',
            'fqtykecil',
            'fqtykecil2',
            'fminstock'
        )
            ->orderBy('fprdname')
            ->get();
    }

    private function buildProductMap($products): array
    {
        return $products->mapWithKeys(function ($product) {
            return [
                trim($product->fprdcode) => [
                    'id' => $product->fprdid ?? null,
                    'name' => $product->fprdname,
                    'units' => array_values(array_filter([
                        $product->fsatuankecil,
                        $product->fsatuanbesar,
                        $product->fsatuanbesar2,
                    ])),
                    'stock' => $product->fminstock ?? 0,
                    'unit_ratios' => [
                        'satuankecil' => 1,
                        'satuanbesar' => (float) ($product->fqtykecil ?? 1),
                        'satuanbesar2' => (float) ($product->fqtykecil2 ?? 1),
                    ],
                ],
            ];
        })->toArray();
    }

    private function getCurrentBranchInfo(): array
    {
        $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

        $branch = DB::table('mscabang')
            ->when(is_numeric($raw), fn ($query) => $query->where('fcabangid', (int) $raw))
            ->when(
                ! is_numeric($raw),
                fn ($query) => $query
                    ->where('fcabangkode', $raw)
                    ->orWhere('fcabangname', $raw)
            )
            ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

        return [
            'raw' => $raw,
            'branch' => $branch,
            'fcabang' => $branch->fcabangname ?? (string) $raw,
            'fbranchcode' => $branch->fcabangkode ?? (string) $raw,
        ];
    }

    private function getExistingPurchaseOrders(string $fprno)
    {
        return DB::table('tr_pod as pod')
            ->join('tr_poh as poh', 'poh.fpono', '=', 'pod.fpono')
            ->leftJoin('mssupplier as s', 's.fsuppliercode', '=', 'poh.fsupplier')
            ->where('pod.frefdtno', $fprno)
            ->select('poh.fpono', 'poh.fpodate', 's.fsuppliername')
            ->distinct()
            ->orderBy('poh.fpodate', 'desc')
            ->get();
    }

    private function buildSavedItems($details, bool $includePricing = false)
    {
        return $details->map(function ($detail) use ($includePricing) {
            $item = [
                'uid' => (string) \Illuminate\Support\Str::uuid(),
                'fprdid' => (int) ($detail->fprdcodeid ?? 0),
                'fitemcode' => (string) ($detail->fprdcode_master ?? ''),
                'fitemname' => (string) ($detail->fprdname ?? ''),
                'fsatuan' => (string) ($detail->fsatuan ?? ''),
                'fqty' => (float) ($detail->fqty ?? 0),
                'fqtypo' => (float) ($detail->fqtypo ?? 0),
                'fdesc' => (string) ($detail->fdesc ?? ''),
                'fketdt' => (string) ($detail->fketdt ?? ''),
            ];

            if ($includePricing) {
                $item['fprice'] = (float) ($detail->fprice ?? 0);
                $item['fdisc'] = (float) ($detail->fdisc ?? 0);
            }

            return $item;
        })->values();
    }

    private function convertQtyToSmallUnit($product, string $unit, int $qty): float|int
    {
        if (! $product) {
            return $qty;
        }

        if ($unit === $product->fsatuanbesar) {
            $ratio = is_numeric($product->fqtykecil) ? (float) $product->fqtykecil : 1;

            return $qty * $ratio;
        }

        if (! empty($product->fsatuanbesar2) && $unit === $product->fsatuanbesar2) {
            $ratio = is_numeric($product->fqtykecil2) ? (float) $product->fqtykecil2 : 1;

            return $qty * $ratio;
        }

        return $qty;
    }

    private function getPrDetailsWithPoUsage(string $fprno)
    {
        return DB::table('tr_prd as d')
            ->leftJoin('msprd as p', 'p.fprdcode', '=', 'd.fprdcode')
            ->leftJoin(
                DB::raw('(
                    SELECT frefdtno, fprdcode, frefnoacak, SUM(fqtykecil) AS fqtykecilpo
                    FROM tr_pod
                    GROUP BY frefdtno, fprdcode, frefnoacak
                ) as po'),
                function ($join) {
                    $join->on('po.frefdtno', '=', 'd.fprno')
                        ->on('po.fprdcode', '=', 'd.fprdcode')
                        ->on('po.frefnoacak', '=', 'd.fnoacak');
                }
            )
            ->where('d.fprno', $fprno)
            ->select([
                'd.*',
                'p.fprdname',
                'p.fprdcode as fprdcode_master',
                DB::raw('COALESCE(
                    CASE 
                        WHEN d.fsatuan=p.fsatuanbesar 
                            THEN (coalesce(fqtykecilpo,0))/p.fqtykecil
                        WHEN d.fsatuan=p.fsatuanbesar2 
                            THEN (coalesce(fqtykecilpo,0))/p.fqtykecil2
                        ELSE coalesce(fqtykecilpo,0) END,0) AS fqtypo'),
            ])
            ->get();
    }
}
