<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Salesman;
use App\Models\Supplier;
use App\Models\Tr_prd;
use App\Models\Tr_prh;
use App\Models\Tranmt;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // sekalian biar aman untuk tanggal
use Illuminate\Validation\ValidationException;

class ReturPenjualanController extends Controller
{
    public function index(Request $request)
    {
        // Ambil izin (permissions)
        $canCreate = in_array('createReturPenjualan', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updateReturPenjualan', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteReturPenjualan', explode(',', session('user_restricted_permissions', '')));
        $showActionsColumn = $canEdit || $canDelete;

        // $status = $request->query('status');
        $year = $request->query('year');
        $month = $request->query('month');

        // Ambil tahun-tahun yang tersedia dari data
        $availableYears = Tranmt::selectRaw('DISTINCT EXTRACT(YEAR FROM fdatetime) as year')
            ->whereNotNull('fdatetime')
            ->orderByRaw('EXTRACT(YEAR FROM fdatetime) DESC')
            ->pluck('year');

        // --- Handle Request AJAX dari DataTables ---
        if ($request->ajax()) {

            $query = Tranmt::query();

            // DEBUG: Cek total data di tabel
            $totalRecords = Tranmt::count();

            // Handle Search
            if ($search = $request->input('search.value')) {
                $query->where('fsono', 'like', "%{$search}%");
            }

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

            $sortableColumns = ['fsono', 'fsodate'];

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
                    'ftranmtid' => $row->ftranmtid,
                    'fbranchcode' => $row->fbranchcode,
                    'fsono' => $row->fsono,
                    'fsodate' => $row->fsodate instanceof \Carbon\Carbon
                        ? $row->fsodate->format('Y-m-d')
                        : $row->fsodate,
                    'frefno' => $row->frefno ?? '',
                    'fcustno' => $row->fcustno ?? '',
                    'fsalesman' => $row->fsalesman,
                    'fdiscpersen' => $row->fdiscpersen,
                    'fdiscount' => $row->fdiscount,
                    'famountgross' => $row->famountgross,
                    'famountsonet' => $row->famountsonet,
                    'famountpajak' => $row->famountpajak,
                    'famountso' => $row->famountso,
                    'fket' => $row->fket,
                    'falamatkirim' => $row->falamatkirim,
                    'fprdout' => $row->fprdout,
                    'fusercreate' => $row->fusercreate,
                    'fuserupdate' => $row->fuserupdate,
                    'fdatetime' => $row->fdatetime,
                    'fclose' => $row->fclose,
                    'fincludeppn' => $row->fincludeppn,
                    'fuseracc' => $row->fuseracc,
                    'fneedacc' => $row->fneedacc,
                    'ftempohr' => $row->ftempohr,
                    'fprint' => $row->fprint,
                ];
            });

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        }

        // --- Handle Request non-AJAX ---
        return view('returpenjualan.index', compact(
            'canCreate',
            'canEdit',
            'canDelete',
            'showActionsColumn',
            // 'status',
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
                $query->orderBy('tr_prh.'.$orderColumn, $orderDir);
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
            'data' => $data,
        ]);
    }

    public function items($id)
    {
        // Ambil data header PR berdasarkan fprhid
        $header = Tr_prh::where('fprhid', $id)->firstOrFail();

        // Detail PR sekarang dihubungkan lewat fprno
        $items = Tr_prd::where('tr_prd.fprno', $header->fprno)
            ->leftJoin('msprd as m', 'm.fprdcodeid', '=', 'tr_prd.fitemid')
            ->select([
                'tr_prd.fprdcodeid as frefdtno',
                'm.fprdcode as fitemcode',
                'm.fprdname as fitemname',
                'tr_prd.fqty',
                'tr_prd.fsatuan as fsatuan',
                'tr_prd.fprno',
                'tr_prd.fprice as fprice',
                DB::raw('0::numeric as fdisc'),
            ])
            ->orderBy('tr_prd.fitemid')
            ->get();

        return response()->json([
            'header' => [
                'fprhid' => $header->fprhid,
                'fprno' => $header->fprno,
                'fsupplier' => trim($header->fsupplier ?? ''),
                'fprdate' => optional($header->fprdate)->format('Y-m-d H:i:s'),
            ],
            'items' => $items,
        ]);
    }

    private function normalizeRandomNumber($value, array &$usedNumbers): string
    {
        $value = trim((string) ($value ?? ''));
        $candidate = preg_match('/^\d{3}$/', $value) ? $value : null;

        if ($candidate !== null && ! in_array($candidate, $usedNumbers, true)) {
            $usedNumbers[] = $candidate;

            return $candidate;
        }

        do {
            $candidate = str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT);
        } while (in_array($candidate, $usedNumbers, true));

        $usedNumbers[] = $candidate;

        return $candidate;
    }

    private function normalizeReferenceRandomNumbers($value): ?string
    {
        $parts = preg_split('/\s*,\s*/', trim((string) ($value ?? ''))) ?: [];
        $normalized = [];

        foreach ($parts as $part) {
            $candidate = trim((string) $part);
            if (! preg_match('/^\d{3}$/', $candidate)) {
                continue;
            }
            if (! in_array($candidate, $normalized, true)) {
                $normalized[] = $candidate;
            }
        }

        return empty($normalized) ? null : implode(',', $normalized);
    }

    private function generateInvoiceCode(?Carbon $onDate = null): string
    {
        $date = $onDate ?: now();
        $prefix = 'REJ.'.$date->format('ym').'.';

        $last = DB::table('tranmt')
            ->where('fsono', 'like', $prefix.'%')
            ->selectRaw("MAX(CAST(substr(trim(fsono), length('".$prefix."') + 1) AS int)) AS lastno")
            ->value('lastno');

        $next = (int) $last + 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function print(string $fsono)
    {
        // Header: find by SO code (string)
        $hdr = DB::table('tranmt')
            ->leftJoin('mscustomer as c', 'c.fcustomercode', '=', 'tranmt.fcustno')
            ->leftJoin('mssalesman as s', 's.fsalesmancode', '=', 'tranmt.fsalesman')
            ->where('tranmt.fsono', $fsono)
            ->first([
                'tranmt.*',
                'c.fcustomername as customer_name',
                's.fsalesmanname as salesman_name',
            ]);

        if (! $hdr) {
            return redirect()->back()->with('error', 'Sales Order tidak ditemukan.');
        }

        // Use header ID (integer) for detail FK
        $ftranmtid = (int) $hdr->ftranmtid;

        // Detail: join dengan product
        $dt = DB::table('trandt')
            ->leftJoin('msprd as p', 'p.fprdid', '=', 'trandt.fprdcodeid')
            ->where('trandt.fsono', $fsono) // Gunakan variabel $fsono dari parameter fungsi
            ->orderBy('trandt.fnou', 'asc') // Urutkan berdasarkan nomor urut baris
            ->get([
                'trandt.*',
                'p.fprdcode as product_code',
                'p.fprdname as product_name',
                'p.fminstock as stock',
            ]);

        // Format date helper
        $fmt = fn ($d) => $d
            ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d F Y')
            : '-';

        return view('returpenjualan.print', [
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
            ->get(['fcustomerid', 'fcustomername', 'fcustomercode']);

        $salesmans = Salesman::orderBy('fsalesmanname', 'asc')
            ->get(['fsalesmanid', 'fsalesmanname', 'fsalesmancode']);

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

        $newtr_prh_code = $this->generateInvoiceCode(now());

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
                    'name' => $p->fprdname,
                    'units' => array_values(array_filter([
                        $p->fsatuankecil,
                        $p->fsatuanbesar,
                        $p->fsatuanbesar2,
                    ])),
                    'stock' => $p->fminstock ?? 0,
                    'unit_ratios' => [           // ← TAMBAH INI
                        'satuankecil' => 1,
                        'satuanbesar' => (float) ($p->fqtykecil ?? 1),
                        'satuanbesar2' => (float) ($p->fqtykecil2 ?? 1),
                    ],
                ],
            ];
        })->toArray();

        return view('returpenjualan.create', [
            'newtr_prh_code' => $newtr_prh_code,
            'perms' => ['can_approval' => $canApproval],
            'customers' => $customers,
            'salesmans' => $salesmans,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'products' => $products,
            'productMap' => $productMap,
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'filterSalesmanId' => $request->query('filter_salesman_id'),
        ]);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'fsodate' => ['required', 'date'],
                'fcustno' => ['required', 'string', 'max:10'],
                'ftypesales' => ['required', 'in:0,1'],
                'fitemcode' => ['required', 'array', 'min:1'],
                'fitemcode.*' => ['nullable', 'string', 'max:30'],
                'fqty' => ['required', 'array'],
                'fqty.*' => ['numeric', 'min:0'],
                'fprice' => ['required', 'array'],
                'fprice.*' => ['numeric', 'min:0'],
                'fdisc' => ['nullable', 'array'],
                'frefcode' => ['nullable', 'string', 'in:SO,SRJ,UM'],
                'frefso' => ['nullable'],
                'frefsrj' => ['nullable'],
                'fnoacak' => ['nullable', 'array'],
                'fnoacak.*' => ['nullable', 'regex:/^\d{3}$/'],
                'frefnoacak' => ['nullable', 'array'],
                'frefnoacak.*' => ['nullable', 'regex:/^\d{3}(,\s*\d{3})*$/'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e; // tetap lempar agar Laravel handle redirect
        }

        // 2. INISIALISASI
        $fsodate = Carbon::parse($request->fsodate);
        $fincludeppn = $request->boolean('fincludeppn') ? '1' : '0';
        $userid = mb_substr(auth('sysuser')->user()->fname ?? 'admin', 0, 10);
        $now = now();
        $fcurrency = $request->input('fcurrency', 'IDR');
        $frate = (float) $request->input('frate', 1);
        $typeSales = (int) $request->input('ftypesales');

        // 3. ARRAY INPUT
        $itemCodes = $request->input('fitemcode', []);
        $itemDescs = $request->input('fitemname', []);
        $satuans = $request->input('fsatuan', []);
        $qtys = $request->input('fqty', []);
        $prices = $request->input('fprice', []);
        $discs = $request->input('fdisc', []);

        // FREFCODE & REFERENCES
        $frefcodes = $request->input('frefcode', []);
        $frefso_codes = $request->input('frefso', []);
        $frefso_ids = $request->input('frefsoid', []);
        $frefsrj_codes = $request->input('frefsrj', []);
        $frefsrjid_ids = $request->input('frefsrjid', []);
        $frefpr_codes = $request->input('frefpr', []);
        $fnoacaks = $request->input('fnoacak', []);
        $frefnoacaks = $request->input('frefnoacak', []);

        if ($typeSales === 1) {
            $frefcode = 'UM';
        } else {
            $frefcode = $request->input('frefcode');
        }

        // CEK UM
        $hasUM = in_array('UM', $itemCodes);

        if ($hasUM && $typeSales === 0) {
            return back()->withInput()->with('error', 'Produk UM hanya untuk tipe Uang Muka.');
        }
        if (! $hasUM && $typeSales === 1) {
            return back()->withInput()->with('error', 'Transaksi Uang Muka wajib menggunakan produk UM.');
        }

        // QUERY PRODUK
        $filteredCodes = array_values(array_filter($itemCodes));

        $products = DB::table('msprd')
            ->whereIn('fprdcode', $filteredCodes)
            ->get(['fprdid', 'fprdcode', 'fprdname', 'fdiscontinue', 'fsatuanbesar', 'fqtykecil as rasio_konversi'])
            ->keyBy('fprdcode');

        // LOOP ITEM
        $detailRows = [];
        $totalGross = 0;
        $totalDisc = 0;
        $nouCounter = 1;
        $usedNoAcaks = [];

        foreach ($itemCodes as $i => $code) {
            $qty = (float) ($qtys[$i] ?? 0);
            $price = (float) ($prices[$i] ?? 0);

            if (empty($code) || $qty <= 0) {
                continue;
            }

            $product = $products->get($code);

            if ($product && $product->fdiscontinue == '1') {
                return back()->withInput()->with('error', "Produk [{$code}] {$product->fprdname} Sudah Discontinue.");
            }

            $fprdcodeid = $product?->fprdid;
            $qtyKecil = $qty;
            if ($product && isset($satuans[$i]) && $satuans[$i] === $product->fsatuanbesar) {
                $qtyKecil = $qty * (float) $product->rasio_konversi;
            }

            $discPersen = $this->parseDiscount($discs[$i] ?? 0);
            $subtotal = $qty * $price;
            $discAmount = $subtotal * ($discPersen / 100);
            $netPrice = $price * (1 - $discPersen / 100);
            $amountRow = $subtotal - $discAmount;

            $totalGross += $subtotal;
            $totalDisc += $discAmount;

            $detailRows[] = [
                'fnou' => $nouCounter,
                'fprdcodeid' => $fprdcodeid,
                'fprdcode' => mb_substr($code, 0, 30),
                'fdesc' => $itemDescs[$i] ?? '',
                'fqty' => $qty,
                'fqtykecil' => $qtyKecil,
                'fqtyremain' => $qty,
                'fprice' => $price,
                'fprice_rp' => $price * $frate,
                'fdisc' => mb_substr((string) ($discs[$i] ?? '0'), 0, 10),
                'fpricenet' => $netPrice,
                'fpricenet_rp' => $netPrice * $frate,
                'famount' => $amountRow,
                'famount_rp' => $amountRow * $frate,
                'fsatuan' => mb_substr($satuans[$i] ?? '', 0, 5),
                'fuserid' => $userid,
                'fdatetime' => $now,
                'frefcode' => $frefcode ?? '',
                'frefso' => $frefso_ids[$i] ? ($frefpr_codes[$i] ?? '') : '',
                'frefsoid' => $frefso_ids[$i] ?? null,
                'frefsrj' => $frefsrjid_ids[$i] ? ($frefpr_codes[$i] ?? '') : '',
                'frefsrjid' => $frefsrjid_ids[$i] ?? null,
                'fnoacak' => $this->normalizeRandomNumber($fnoacaks[$i] ?? null, $usedNoAcaks),
                'frefnoacak' => $this->normalizeReferenceRandomNumbers($frefnoacaks[$i] ?? null),
            ];

            $stockDetailRows[] = [
                'fprdcodeid' => $fprdcodeid,
                'fprdcode' => mb_substr($code, 0, 30),
                'fdesc' => $itemDescs[$i] ?? '',
                'fqty' => $qty,
                'fqtykecil' => $qtyKecil,
                'fqtyremain' => $qtyKecil,
                'fprice' => $price,
                'fprice_rp' => $price * $frate,
                'ftotprice' => $amountRow,
                'fusercreate' => $userid,
                'fdatetime' => $now,
                'fsatuan' => mb_substr($satuans[$i] ?? '', 0, 5),
                'fcode' => '0',
            ];
        }

        // GUARD: detailRows kosong
        if (empty($detailRows)) {
            return back()->withInput()->with('error', 'Tidak ada item valid. Periksa kode produk dan qty.');
        }

        $soUsageByDetailId = [];
        $srjUsageByDetailId = [];
        foreach ($detailRows as $row) {
            $qtyKecil = (float) ($row['fqtykecil'] ?? 0);
            $soDetailId = (int) ($row['frefsoid'] ?? 0);
            $srjDetailId = (int) ($row['frefsrjid'] ?? 0);
            if ($qtyKecil <= 0) {
                continue;
            }
            if ($soDetailId > 0) {
                $soUsageByDetailId[$soDetailId] = ($soUsageByDetailId[$soDetailId] ?? 0) + $qtyKecil;
            }
            if ($srjDetailId > 0) {
                $srjUsageByDetailId[$srjDetailId] = ($srjUsageByDetailId[$srjDetailId] ?? 0) + $qtyKecil;
            }
        }

        $remainErrors = [];
        if (! empty($soUsageByDetailId)) {
            $soRemainRows = $this->getSoRemainByIds(array_keys($soUsageByDetailId));
            foreach ($soUsageByDetailId as $detailId => $usedQty) {
                $remain = (float) ($soRemainRows[$detailId] ?? 0);
                if ($usedQty - $remain > 0.00001) {
                    $remainErrors[] = "Qty SO detail #{$detailId} melebihi sisa. Input: {$usedQty}, sisa: {$remain}.";
                }
            }
        }
        if (! empty($srjUsageByDetailId)) {
            $srjRemainRows = $this->getSrjRemainByIds(array_keys($srjUsageByDetailId));
            foreach ($srjUsageByDetailId as $detailId => $usedQty) {
                $remain = (float) ($srjRemainRows[$detailId] ?? 0);
                if ($usedQty - $remain > 0.00001) {
                    $remainErrors[] = "Qty SRJ detail #{$detailId} melebihi sisa. Input: {$usedQty}, sisa: {$remain}.";
                }
            }
        }
        if (! empty($remainErrors)) {
            throw ValidationException::withMessages(['detail' => $remainErrors]);
        }

        // KALKULASI TOTAL
        $fapplyppn = $request->input('fapplyppn', '0'); // 0: Exclude, 1: Include
        $amountNet = $totalGross - $totalDisc;
        $ppnPersen = (float) $request->input('fppnpersen', 11);

        if ($fincludeppn === '1') {
            if ($fapplyppn === '1') {
                // INCLUDE: amountNet is current base, we extract
                $ppnAmount = $amountNet * ($ppnPersen / (100 + $ppnPersen));
                $amountNet = $amountNet - $ppnAmount;
                $grandTotal = $amountNet + $ppnAmount;
            } else {
                // EXCLUDE: amountNet is base, we add
                $ppnAmount = $amountNet * ($ppnPersen / 100);
                $grandTotal = $amountNet + $ppnAmount;
            }
        } else {
            $ppnAmount = 0;
            $grandTotal = $amountNet;
        }

        // DATABASE TRANSACTION
        try {
            DB::transaction(function () use (
                $request,
                $fsodate,
                $fincludeppn,
                $userid,
                $now,
                $detailRows,
                $stockDetailRows,
                $totalGross,
                $totalDisc,
                $amountNet,
                $ppnAmount,
                $grandTotal,
                $fcurrency,
                $frate,
                $ppnPersen,
                $typeSales,
                $fapplyppn,
                $soUsageByDetailId,
                $srjUsageByDetailId
            ) {

                $fsono = $request->input('fsono');

                if (empty($fsono)) {
                    $prefix = 'REJ.'.$fsodate->format('ym').'.';

                    $lastRecord = DB::table('tranmt')
                        ->where('fsono', 'like', $prefix.'%')
                        ->orderBy('fsono', 'desc')
                        ->lockForUpdate()
                        ->first();

                    $nextNumber = $lastRecord
                        ? ((int) substr(trim($lastRecord->fsono), -4)) + 1
                        : 1;

                    $fsono = $prefix.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
                }

                $headerData = [
                    'fsono' => $fsono,
                    'fsodate' => $fsodate,
                    'fcustno' => mb_substr($request->fcustno, 0, 10),
                    'fsalesman' => mb_substr((string) ($request->fsalesman ?? ''), 0, 30),
                    'fcurrency' => $fcurrency,
                    'frate' => $frate,
                    'fdiscount' => $totalDisc,
                    'fdiscount_rp' => $totalDisc * $frate,
                    'famountgross' => $totalGross,
                    'famountgross_rp' => $totalGross * $frate,
                    'famountsonet' => $amountNet,
                    'famountsonet_rp' => $amountNet * $frate,
                    'famountpajak' => $ppnAmount,
                    'famountpajak_rp' => $ppnAmount * $frate,
                    'famountso' => $grandTotal,
                    'famountso_rp' => $grandTotal * $frate,
                    'famountremain' => $grandTotal,
                    'famountremain_rp' => $grandTotal * $frate,
                    'fket' => $request->fket ?? '',
                    'fuserid' => $userid,
                    'fdatetime' => $now,
                    'fincludeppn' => $fincludeppn,
                    'fapplyppn' => $fapplyppn,
                    'fppnpersen' => $ppnPersen,
                    'ftypesales' => $typeSales,
                    'ftrcode' => 'I',
                    'fprdout' => '0',
                    'ftaxno' => $request->ftaxno ?? '0',
                ];

                $ftranmtid = DB::table('tranmt')->insertGetId($headerData, 'ftranmtid');

                foreach ($detailRows as &$row) {
                    $row['fsono'] = $fsono;
                }
                unset($row);

                DB::table('trandt')->insert($detailRows);

                // ==== STOCK RECORDS ====
                $fstockmtno = str_replace('REJ.', 'REB.', $fsono);
                $masterStockData = [
                    'fstockmtno' => $fstockmtno,
                    'fstockmtcode' => 'REB',
                    'fstockmtdate' => $fsodate,
                    'fprdout' => '0',
                    'fsupplier' => mb_substr($request->fcustno, 0, 10),
                    'famount' => $amountNet,
                    'famount_rp' => $amountNet * $frate,
                    'famountpajak' => $ppnAmount,
                    'famountpajak_rp' => $ppnAmount * $frate,
                    'famountmt' => $grandTotal,
                    'famountmt_rp' => $grandTotal * $frate,
                    'famountremain' => $grandTotal,
                    'famountremain_rp' => $grandTotal * $frate,
                    'fket' => $request->fket ?? '',
                    'fusercreate' => $userid,
                    'fdatetime' => $now,
                    'fbranchcode' => $request->fbranchcode ?? 'BG', // Use request branch
                ];

                $newStockId = DB::table('trstockmt')->insertGetId($masterStockData, 'fstockmtid');

                foreach ($stockDetailRows as &$srow) {
                    $srow['fstockmtno'] = $fstockmtno;
                    $srow['fstockmtcode'] = 'REB';
                }
                unset($srow);

                DB::table('trstockdt')->insert($stockDetailRows);

                if (! empty($soUsageByDetailId)) {
                    $remainRows = $this->getSoRemainByIds(array_keys($soUsageByDetailId));
                    foreach ($soUsageByDetailId as $detailId => $usedQty) {
                        $remain = (float) ($remainRows[$detailId] ?? 0);
                        if ($usedQty - $remain > 0.00001) {
                            throw new \RuntimeException("Qty SO detail #{$detailId} melebihi sisa.");
                        }
                    }
                }
                if (! empty($srjUsageByDetailId)) {
                    $remainRows = $this->getSrjRemainByIds(array_keys($srjUsageByDetailId));
                    foreach ($srjUsageByDetailId as $detailId => $usedQty) {
                        $remain = (float) ($remainRows[$detailId] ?? 0);
                        if ($usedQty - $remain > 0.00001) {
                            throw new \RuntimeException("Qty SRJ detail #{$detailId} melebihi sisa.");
                        }
                    }
                }
            });

            // UPDATE STOK - gunakan qtyKecil hasil konversi, bukan qty mentah
            foreach ($detailRows as $row) {
                DB::table('msprd')
                    ->where('fprdcode', $row['fprdcode'])
                    ->update([
                        'fminstock' => DB::raw('CAST(fminstock AS NUMERIC) - '.$row['fqtyremain']),
                        'fupdatedat' => now(),
                    ]);
            }

            return redirect()->route('returpenjualan.index')->with('success', 'Retur Penjualan berhasil disimpan.');
        } catch (\Exception $e) {

            return back()->withInput()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    // ✅ TAMBAHKAN METHOD HELPER UNTUK PARSE DISCOUNT
    private function parseDiscount($discInput)
    {
        if ($discInput === null || $discInput === '') {
            return 0;
        }

        // Jika sudah berupa angka
        if (is_numeric($discInput)) {
            return (float) $discInput;
        }

        // Jika string, parse ekspresi matematika
        $str = trim((string) $discInput);

        if ($str === '') {
            return 0;
        }

        // Jika angka biasa
        if (is_numeric($str)) {
            return (float) $str;
        }

        // Parse ekspresi seperti "10+2"
        try {
            // Hapus spasi
            $cleaned = preg_replace('/\s+/', '', $str);

            // Evaluasi ekspresi
            $result = eval("return {$cleaned};");

            // Batasi 0-100%
            $final = max(0, min(100, (float) $result));

            return $final;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function getSoRemainByIds(array $soDetailIds): array
    {
        $ids = collect($soDetailIds)->map(fn($id) => (int) $id)->filter(fn($id) => $id > 0)->unique()->values()->all();
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

    private function getSrjRemainByIds(array $srjDetailIds): array
    {
        $ids = collect($srjDetailIds)->map(fn($id) => (int) $id)->filter(fn($id) => $id > 0)->unique()->values()->all();
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

    public function edit(Request $request, $ftranmtid)
    {
        $customers = Customer::orderBy('fcustomername', 'asc')
            ->get(['fcustomerid', 'fcustomername', 'fcustomercode']);

        $salesmans = Salesman::orderBy('fsalesmanname', 'asc')
            ->get(['fsalesmanid', 'fsalesmanname', 'fsalesmancode']);

        $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

        $branch = DB::table('mscabang')
            ->when(is_numeric($raw), fn ($q) => $q->where('fcabangid', (int) $raw))
            ->when(! is_numeric($raw), fn ($q) => $q
                ->where('fcabangkode', $raw)
                ->orWhere('fcabangname', $raw))
            ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

        $fcabang = $branch->fcabangname ?? (string) $raw;   // tampilan
        $fbranchcode = $branch->fcabangkode ?? (string) $raw;   // hidden post

        $returpenjualan = Tranmt::with(['customer', 'details' => function ($q) {
            $q->leftJoin('msprd', function ($j) {
                // Gunakan trandt.fprdcodeid karena sudah integer (tidak perlu CAST lagi)
                $j->on('msprd.fprdid', '=', 'trandt.fprdcodeid');
            })
                ->select(
                    'trandt.*',
                    'msprd.fprdcode as fitemcode',
                    'msprd.fprdname'
                )
                // Ubah order ke ftrandtid (Primary Key detail) karena ftranmtid tidak ada
                ->orderBy('trandt.ftrandtid', 'asc');
        }])->findOrFail($ftranmtid);

        if (! $returpenjualan->customer) {
            $returpenjualan->setRelation('customer', Customer::where('fcustomercode', trim((string) $returpenjualan->fcustno))->first());
        }

        $usageLockMessage = $this->getUsageLockMessage($returpenjualan);

        $soDetailIds = $returpenjualan->details
            ->pluck('frefsoid')
            ->filter(fn ($v) => is_numeric($v) && (int) $v > 0)
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        $srjDetailIds = $returpenjualan->details
            ->pluck('frefsrjid')
            ->filter(fn ($v) => is_numeric($v) && (int) $v > 0)
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        $soRemainRows = $this->getSoRemainByIds($soDetailIds);
        $srjRemainRows = $this->getSrjRemainByIds($srjDetailIds);

        $savedItems = $returpenjualan->details->map(function ($d) use ($soRemainRows, $srjRemainRows) {
            $refCode = strtoupper(trim($d->frefcode ?? ''));
            $valSo = trim($d->frefso ?? '');
            $valSrj = trim($d->frefsrj ?? '');
            // 2. Logika Prioritas Tampilan
            $displayRef = '-';

            // Jika ada SRJ, tampilkan SRJ (biasanya SRJ lebih spesifik untuk retur)
            if ($valSrj !== '') {
                $displayRef = $valSrj;
                $refCode = 'SRJ'; // Paksa refcode jadi SRJ jika ada nilainya
            }
            // Jika tidak ada SRJ tapi ada SO
            elseif ($valSo !== '') {
                $displayRef = $valSo;
                $refCode = 'SO';
            }

            $usedQtyKecil = (float) ($d->fqtykecil ?? 0);
            $remainDb = 0.0;
            if (is_numeric($d->frefsoid) && (int) $d->frefsoid > 0) {
                $remainDb = (float) ($soRemainRows[(int) $d->frefsoid] ?? 0);
            } elseif (is_numeric($d->frefsrjid) && (int) $d->frefsrjid > 0) {
                $remainDb = (float) ($srjRemainRows[(int) $d->frefsrjid] ?? 0);
            }

            $fqtyremain = max(0.0, $remainDb + $usedQtyKecil);

            return [
                'uid' => $d->ftrandtid,
                'fitemcode' => (string) ($d->fitemcode ?? ''),
                'fitemname' => (string) ($d->fprdname ?? ''),
                'fsatuan' => (string) ($d->fsatuan ?? ''),
                'frefdtno' => (string) ($d->frefdtno ?? ''),
                'frefsoid' => (string) ($d->frefsoid ?? ''),
                'frefsrjid' => (string) ($d->frefsrjid ?? ''),
                'fqty' => (float) ($d->fqty ?? 0),
                'fterima' => (float) ($d->fterima ?? 0),
                'fqtyremain' => $fqtyremain,
                'fprice' => (float) ($d->fprice ?? 0),
                'fdisc' => (string) ($d->fdisc ?? '0'),
                'ftotal' => (float) ($d->famount ?? 0),
                'fdesc' => (string) ($d->fdesc ?? ''),
                'frefcode' => $refCode,
                'frefpr' => $displayRef, // Kolom ini yang akan ditampilkan di Blade
                'frefso' => $valSo,
                'frefsrj' => $valSrj,
                'fnoacak' => (string) ($d->fnoacak ?? ''),
                'frefnoacak' => (string) ($d->frefnoacak ?? ''),
            ];
        })->values();
        $selectedSupplierCode = $returpenjualan->fsupplier;

        // Fetch all products for product mapping
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

        // Prepare the product map for frontend
        $productMap = $products->mapWithKeys(function ($p) {
            return [
                $p->fprdcode => [
                    'name' => $p->fprdname,
                    'units' => array_values(array_filter([
                        $p->fsatuankecil,
                        $p->fsatuanbesar,
                        $p->fsatuanbesar2,
                    ])),
                    'stock' => $p->fminstock ?? 0,
                    'unit_ratios' => [           // ← TAMBAH INI
                        'satuankecil' => 1,
                        'satuanbesar' => (float) ($p->fqtykecil ?? 1),
                        'satuanbesar2' => (float) ($p->fqtykecil2 ?? 1),
                    ],
                ],
            ];
        })->toArray();

        // Pass the data to the view
        return view('returpenjualan.edit', [
            'customers' => $customers,
            'salesmans' => $salesmans,
            'selectedSupplierCode' => $selectedSupplierCode, // Kirim kode supplier ke view
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'products' => $products,
            'productMap' => $productMap,
            'returpenjualan' => $returpenjualan,
            'savedItems' => $savedItems,
            'ppnAmount' => (float) ($returpenjualan->famountpopajak ?? 0), // total PPN from DB
            'famountgross' => (float) ($returpenjualan->famountgross ?? 0),  // nilai Grand Total dari DB
            'famountso' => (float) ($returpenjualan->famountso ?? 0),  // nilai Grand Total dari DB
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'filterSalesmanId' => $request->query('filter_salesman_id'),
            'isUsageLocked' => ! empty($usageLockMessage),
            'usageLockMessage' => $usageLockMessage,
            'action' => 'edit',
        ]);
    }

    public function view(Request $request, $ftranmtid)
    {
        $customers = Customer::orderBy('fcustomername', 'asc')
            ->get(['fcustomerid', 'fcustomername', 'fcustomercode']);

        $salesmans = Salesman::orderBy('fsalesmanname', 'asc')
            ->get(['fsalesmanid', 'fsalesmanname', 'fsalesmancode']);

        $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

        $branch = DB::table('mscabang')
            ->when(is_numeric($raw), fn ($q) => $q->where('fcabangid', (int) $raw))
            ->when(! is_numeric($raw), fn ($q) => $q
                ->where('fcabangkode', $raw)
                ->orWhere('fcabangname', $raw))
            ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

        $fcabang = $branch->fcabangname ?? (string) $raw;   // tampilan
        $fbranchcode = $branch->fcabangkode ?? (string) $raw;   // hidden post

        $returpenjualan = Tranmt::with(['customer', 'details' => function ($q) {
            $q->leftJoin('msprd', function ($j) {
                // Gunakan trandt.fprdcodeid karena sudah integer (tidak perlu CAST lagi)
                $j->on('msprd.fprdid', '=', 'trandt.fprdcodeid');
            })
                ->select(
                    'trandt.*',
                    'msprd.fprdcode as fitemcode',
                    'msprd.fprdname'
                )
                // Ubah order ke ftrandtid (Primary Key detail) karena ftranmtid tidak ada
                ->orderBy('trandt.ftrandtid', 'asc');
        }])->findOrFail($ftranmtid);

        if (! $returpenjualan->customer) {
            $returpenjualan->setRelation('customer', Customer::where('fcustomercode', trim((string) $returpenjualan->fcustno))->first());
        }

        $savedItems = $returpenjualan->details->map(function ($d) {
            $refCode = strtoupper(trim($d->frefcode ?? ''));
            $valSo = trim($d->frefso ?? '');
            $valSrj = trim($d->frefsrj ?? '');
            // 2. Logika Prioritas Tampilan
            $displayRef = '-';

            // Jika ada SRJ, tampilkan SRJ (biasanya SRJ lebih spesifik untuk retur)
            if ($valSrj !== '') {
                $displayRef = $valSrj;
                $refCode = 'SRJ'; // Paksa refcode jadi SRJ jika ada nilainya
            }
            // Jika tidak ada SRJ tapi ada SO
            elseif ($valSo !== '') {
                $displayRef = $valSo;
                $refCode = 'SO';
            }

            return [
                'uid' => $d->ftrandtid,
                'fitemcode' => (string) ($d->fitemcode ?? ''),  // dari alias msprd.fprdcodeid
                'fitemname' => (string) ($d->fprdname ?? ''),   // dari msprd.fprdname
                'fsatuan' => (string) ($d->fsatuan ?? ''),
                'frefdtno' => (string) ($d->frefdtno ?? ''),
                'fqty' => (float) ($d->fqty ?? 0),
                'fterima' => (float) ($d->fterima ?? 0),
                'fqtyremain' => (float) ($d->fqtyremain ?? 0),
                'fprice' => (float) ($d->fprice ?? 0),
                'fdisc' => (float) ($d->fdisc ?? 0),
                'ftotal' => (float) ($d->famount ?? 0),
                'fdesc' => (string) ($d->fdesc ?? ''),
                'fketdt' => (string) ($d->fketdt ?? ''),
                'frefcode' => $refCode,
                'frefpr' => $displayRef,
                'fnoacak' => (string) ($d->fnoacak ?? ''),
                'frefnoacak' => (string) ($d->frefnoacak ?? ''),
            ];
        })->values();
        $selectedSupplierCode = $returpenjualan->fsupplier;

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
                trim($p->fprdcode) => [
                    'name' => $p->fprdname,
                    'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
                    'stock' => $p->fminstock ?? 0,
                ],
            ];
        })->toArray();

        // Pass the data to the view
        return view('returpenjualan.view', [
            'customers' => $customers,
            'salesmans' => $salesmans,
            'selectedSupplierCode' => $selectedSupplierCode, // Kirim kode supplier ke view
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'products' => $products,
            'productMap' => $productMap,
            'returpenjualan' => $returpenjualan,
            'savedItems' => $savedItems,
            'ppnAmount' => (float) ($returpenjualan->famountpopajak ?? 0), // total PPN from DB
            'famountgross' => (float) ($returpenjualan->famountgross ?? 0),  // nilai Grand Total dari DB
            'famountso' => (float) ($returpenjualan->famountso ?? 0),  // nilai Grand Total dari DB
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'filterSalesmanId' => $request->query('filter_salesman_id'),
        ]);
    }

    public function update(Request $request, $ftranmtid)
    {
        // 1. VALIDASI
        $request->validate([
            'fsodate' => ['required', 'date'],
            'fcustno' => ['required', 'string', 'max:10'],
            'ftypesales' => ['required', 'in:0,1'], // Pastikan ftypesales divalidasi
            'fitemcode' => ['required', 'array', 'min:1'],
            'fitemcode.*' => ['required', 'string', 'max:30'],
            'fqty' => ['required', 'array'],
            'fqty.*' => ['numeric', 'min:0.01'],
            'fprice' => ['required', 'array'],
            'fprice.*' => ['numeric', 'min:0'],
            'fdisc' => ['nullable', 'array'],
            'frefso' => ['nullable'],
            'frefsrj' => ['nullable'],
            'fnoacak' => ['nullable', 'array'],
            'fnoacak.*' => ['nullable', 'regex:/^\d{3}$/'],
            'frefnoacak' => ['nullable', 'array'],
            'frefnoacak.*' => ['nullable', 'regex:/^\d{3}(,\s*\d{3})*$/'],
        ]);

        // 2. LOAD HEADER
        $header = DB::table('tranmt')->where('ftranmtid', $ftranmtid)->first();
        if (! $header) {
            return abort(404, 'Faktur Penjualan tidak ditemukan.');
        }

        if ($message = $this->getUsageLockMessage((object) $header)) {
            return redirect()->route('returpenjualan.index')->with('error', $message);
        }

        // 3. INISIALISASI DATA
        $fsodate = Carbon::parse($request->fsodate);
        $fincludeppn = $request->boolean('fincludeppn') ? '1' : '0';
        $userid = mb_substr(auth('sysuser')->user()->fname ?? 'admin', 0, 10);
        $now = now();
        $frate = (float) $request->input('frate', $header->frate ?? 1);

        $itemCodes = $request->input('fitemcode', []);
        $typeSales = (int) $request->input('ftypesales'); // 0: Penjualan, 1: Uang Muka
        $itemDescs = $request->input('fitemname', []);
        $satuans = $request->input('fsatuan', []);
        $qtys = $request->input('fqty', []);
        $prices = $request->input('fprice', []);
        $discs = $request->input('fdisc', []);

        $frefcodes = $request->input('frefcode', []);   // per baris, jika array
        $frefpr_codes = $request->input('frefpr', []);
        $frefso_ids = $request->input('frefsoid', []);
        $frefsrjid_ids = $request->input('frefsrjid', []);
        $fnoacaks = $request->input('fnoacak', []);
        $frefnoacaks = $request->input('frefnoacak', []);

        if ($typeSales === 1) {
            $frefcode = 'UM';
        } else {
            $frefcode = $request->input('frefcode_global')
                ?: ($header->frefcode ?? '');  // fallback dari DB jika kosong
        }

        // Ambil mapping produk untuk mendapatkan fprdcodeid dan rasio
        $products = DB::table('msprd')
            ->whereIn('fprdcode', array_filter($itemCodes))
            ->get(['fprdid', 'fprdcode', 'fsatuanbesar', 'fqtykecil as rasio_konversi'])
            ->keyBy('fprdcode');

        // 4. BUILD DETAIL ROWS
        $detailRows = [];
        $totalGross = 0;
        $totalDisc = 0;
        $usedNoAcaks = [];

        $hasUM = in_array('UM', $itemCodes);

        if ($hasUM && $typeSales === 0) {
            // Jika ada "UM" tapi Type adalah Penjualan (0) -> ERROR
            return back()->withInput()->with('error', 'Produk Uang Muka (UM) hanya diperbolehkan untuk tipe transaksi Uang Muka.');
        }

        if (! $hasUM && $typeSales === 1) {
            // Tambahan: Jika tipe Uang Muka (1) tapi tidak ada item "UM" -> ERROR (Opsional)
            return back()->withInput()->with('error', 'Transaksi Uang Muka wajib menggunakan produk dengan kode UM.');
        }

        $stockDetailRows = [];
        foreach ($itemCodes as $i => $code) {
            $qty = (float) ($qtys[$i] ?? 0);
            $price = (float) ($prices[$i] ?? 0);

            if (empty($code) || $qty <= 0) {
                continue;
            }

            $product = $products->get($code);
            $fprdcodeid = $product?->fprdid;

            $qtyKecil = $qty;
            if ($product && isset($satuans[$i]) && $satuans[$i] === $product->fsatuanbesar) {
                $qtyKecil = $qty * (float) $product->rasio_konversi;
            }

            $discPersen = $this->parseDiscount($discs[$i] ?? 0);
            $subtotal = $qty * $price;
            $discAmount = $subtotal * ($discPersen / 100);
            $netPrice = $price - ($price * ($discPersen / 100));
            $amountRow = $subtotal - $discAmount;

            $totalGross += $subtotal;
            $totalDisc += $discAmount;

            $detailRows[] = [
                'fsono' => $header->fsono,
                'fnou' => $i + 1,
                'fprdcodeid' => $fprdcodeid,
                'fprdcode' => mb_substr($code, 0, 30),
                'fdesc' => $itemDescs[$i] ?? '',
                'fqty' => $qty,
                'fqtykecil' => $qtyKecil,
                'fqtyremain' => $qty,
                'fprice' => $price,
                'fprice_rp' => $price * $frate,
                'fdisc' => mb_substr((string) ($discs[$i] ?? '0'), 0, 10),
                'fpricenet' => $netPrice,
                'fpricenet_rp' => $netPrice * $frate,
                'famount' => $amountRow,
                'famount_rp' => $amountRow * $frate,
                'fsatuan' => mb_substr($satuans[$i] ?? '', 0, 5),
                'fuserid' => $userid,
                'fdatetime' => $now,
                'frefcode' => $frefcode ?? '',
                'frefsoid' => (! empty($request->frefsoid[$i])) ? (int) $request->frefsoid[$i] : null,
                'frefsrjid' => (! empty($request->frefsrjid[$i])) ? (int) $request->frefsrjid[$i] : null,
                'frefso' => $frefso_ids[$i] ? ($frefpr_codes[$i] ?? '') : '',
                'frefsrj' => $frefsrjid_ids[$i] ? ($frefpr_codes[$i] ?? '') : '',
                'fnoacak' => $this->normalizeRandomNumber($fnoacaks[$i] ?? null, $usedNoAcaks),
                'frefnoacak' => $this->normalizeReferenceRandomNumbers($frefnoacaks[$i] ?? null),
            ];

            $stockDetailRows[] = [
                'fprdcodeid' => $fprdcodeid,
                'fprdcode' => mb_substr($code, 0, 30),
                'fdesc' => $itemDescs[$i] ?? '',
                'fqty' => $qty,
                'fqtykecil' => $qtyKecil,
                'fqtyremain' => $qtyKecil,
                'fprice' => $price,
                'fprice_rp' => $price * $frate,
                'ftotprice' => $amountRow,
                'fusercreate' => $userid,
                'fdatetime' => $now,
                'fsatuan' => mb_substr($satuans[$i] ?? '', 0, 5),
                'fcode' => '0',
            ];
        }

        $oldSoUsageRows = DB::table('trandt')
            ->where('fsono', $header->fsono)
            ->whereNotNull('frefsoid')
            ->select('frefsoid', DB::raw('SUM(COALESCE(fqtykecil, 0)) as used_qty_kecil'))
            ->groupBy('frefsoid')
            ->get();
        $oldSrjUsageRows = DB::table('trandt')
            ->where('fsono', $header->fsono)
            ->whereNotNull('frefsrjid')
            ->select('frefsrjid', DB::raw('SUM(COALESCE(fqtykecil, 0)) as used_qty_kecil'))
            ->groupBy('frefsrjid')
            ->get();
        $oldSoUsageByDetailId = [];
        $oldSrjUsageByDetailId = [];
        foreach ($oldSoUsageRows as $row) {
            $detailId = (int) ($row->frefsoid ?? 0);
            $qtyKecil = (float) ($row->used_qty_kecil ?? 0);
            if ($detailId > 0 && $qtyKecil > 0) {
                $oldSoUsageByDetailId[$detailId] = $qtyKecil;
            }
        }
        foreach ($oldSrjUsageRows as $row) {
            $detailId = (int) ($row->frefsrjid ?? 0);
            $qtyKecil = (float) ($row->used_qty_kecil ?? 0);
            if ($detailId > 0 && $qtyKecil > 0) {
                $oldSrjUsageByDetailId[$detailId] = $qtyKecil;
            }
        }

        $soUsageByDetailId = [];
        $srjUsageByDetailId = [];
        foreach ($detailRows as $row) {
            $qtyKecil = (float) ($row['fqtykecil'] ?? 0);
            $soDetailId = (int) ($row['frefsoid'] ?? 0);
            $srjDetailId = (int) ($row['frefsrjid'] ?? 0);
            if ($qtyKecil <= 0) {
                continue;
            }
            if ($soDetailId > 0) {
                $soUsageByDetailId[$soDetailId] = ($soUsageByDetailId[$soDetailId] ?? 0) + $qtyKecil;
            }
            if ($srjDetailId > 0) {
                $srjUsageByDetailId[$srjDetailId] = ($srjUsageByDetailId[$srjDetailId] ?? 0) + $qtyKecil;
            }
        }

        $remainErrors = [];
        $soIdsToCheck = array_values(array_unique(array_merge(array_keys($soUsageByDetailId), array_keys($oldSoUsageByDetailId))));
        if (! empty($soIdsToCheck)) {
            $soRemainRows = $this->getSoRemainByIds($soIdsToCheck);
            foreach ($soUsageByDetailId as $detailId => $usedQty) {
                $available = (float) ($soRemainRows[$detailId] ?? 0) + (float) ($oldSoUsageByDetailId[$detailId] ?? 0);
                if ($usedQty - $available > 0.00001) {
                    $remainErrors[] = "Qty SO detail #{$detailId} melebihi sisa. Input: {$usedQty}, sisa: {$available}.";
                }
            }
        }
        $srjIdsToCheck = array_values(array_unique(array_merge(array_keys($srjUsageByDetailId), array_keys($oldSrjUsageByDetailId))));
        if (! empty($srjIdsToCheck)) {
            $srjRemainRows = $this->getSrjRemainByIds($srjIdsToCheck);
            foreach ($srjUsageByDetailId as $detailId => $usedQty) {
                $available = (float) ($srjRemainRows[$detailId] ?? 0) + (float) ($oldSrjUsageByDetailId[$detailId] ?? 0);
                if ($usedQty - $available > 0.00001) {
                    $remainErrors[] = "Qty SRJ detail #{$detailId} melebihi sisa. Input: {$usedQty}, sisa: {$available}.";
                }
            }
        }
        if (! empty($remainErrors)) {
            throw ValidationException::withMessages(['detail' => $remainErrors]);
        }

        // 5. KALKULASI TOTAL
        $fapplyppn = $request->input('fapplyppn', '0');
        $amountNet = $totalGross - $totalDisc;
        $ppnPersen = (float) $request->input('fppnpersen', 11);

        if ($fincludeppn === '1') {
            if ($fapplyppn === '1') {
                // INCLUDE
                $ppnAmount = $amountNet * ($ppnPersen / (100 + $ppnPersen));
                $amountNet = $amountNet - $ppnAmount;
                $grandTotal = $amountNet + $ppnAmount;
            } else {
                // EXCLUDE
                $ppnAmount = $amountNet * ($ppnPersen / 100);
                $grandTotal = $amountNet + $ppnAmount;
            }
        } else {
            $ppnAmount = 0;
            $grandTotal = $amountNet;
        }

        $ftypesales = $request->input('ftypesales', 0);

        // 6. TRANSACTION
        try {
            DB::transaction(function () use (
                $request,
                $ftranmtid,
                $header,
                $fsodate,
                $fincludeppn,
                $userid,
                $now,
                $ftypesales,
                $detailRows,
                $stockDetailRows,
                $oldSoUsageByDetailId,
                $oldSrjUsageByDetailId,
                $soUsageByDetailId,
                $srjUsageByDetailId,
                $totalGross,
                $totalDisc,
                $amountNet,
                $ppnAmount,
                $grandTotal,
                $frate,
                $ppnPersen,
                $fapplyppn
            ) {
                // Update Header (tranmt)
                DB::table('tranmt')->where('ftranmtid', $ftranmtid)->update([
                    'fsodate' => $fsodate,
                    'fcustno' => mb_substr($request->fcustno, 0, 10),
                    'fsalesman' => mb_substr((string) ($request->fsalesman ?? ''), 0, 30),
                    'fdiscount' => $totalDisc,
                    'fdiscount_rp' => $totalDisc * $frate,
                    'famountgross' => $totalGross,
                    'famountgross_rp' => $totalGross * $frate,
                    'famountsonet' => $amountNet,
                    'famountsonet_rp' => $amountNet * $frate,
                    'famountpajak' => $ppnAmount,
                    'famountpajak_rp' => $ppnAmount * $frate,
                    'famountso' => $grandTotal,
                    'famountso_rp' => $grandTotal * $frate,
                    'fket' => $request->fket ?? '',
                    'fuserid' => $userid,
                    'fdatetime' => $now,
                    'fincludeppn' => $fincludeppn,
                    'fapplyppn' => $fapplyppn,
                    'ftypesales' => $ftypesales,
                    'fppnpersen' => $ppnPersen,
                    'ftaxno' => $request->ftaxno ?? '0',
                ]);

                // Delete detail lama agar tidak duplikat saat update
                DB::table('trandt')->where('fsono', $header->fsono)->delete();

                foreach ($oldSoUsageByDetailId as $detailId => $oldQty) {
                    DB::table('trsodt')->where('ftrsodtid', $detailId)->update([
                        'fqtyremain' => DB::raw('COALESCE(fqtyremain,0) + '.(float) $oldQty),
                    ]);
                }
                foreach ($oldSrjUsageByDetailId as $detailId => $oldQty) {
                    DB::table('trstockdt')->where('fstockdtid', $detailId)->update([
                        'fqtyremain' => DB::raw('COALESCE(fqtyremain,0) + '.(float) $oldQty),
                    ]);
                }

                // Insert detail baru
                if (! empty($detailRows)) {
                    DB::table('trandt')->insert($detailRows);
                }

                // ==== SYNC STOCK RECORDS ====
                $fstockmtno = str_replace('REJ.', 'REB.', $header->fsono);
                $stockHeader = DB::table('trstockmt')->where('fstockmtno', $fstockmtno)->first();

                if ($stockHeader) {
                    // Update Stock Header
                    DB::table('trstockmt')->where('fstockmtid', $stockHeader->fstockmtid)->update([
                        'fstockmtdate' => $fsodate,
                        'fsupplier' => mb_substr($request->fcustno, 0, 10),
                        'famount' => $amountNet,
                        'famount_rp' => $amountNet * $frate,
                        'famountpajak' => $ppnAmount,
                        'famountpajak_rp' => $ppnAmount * $frate,
                        'famountmt' => $grandTotal,
                        'famountmt_rp' => $grandTotal * $frate,
                        'famountremain' => $grandTotal,
                        'famountremain_rp' => $grandTotal * $frate,
                        'fket' => $request->fket ?? '',
                        'fusercreate' => $userid,
                        'fdatetime' => $now,
                        'fbranchcode' => $request->fbranchcode ?? $stockHeader->fbranchcode ?? 'BG',
                        'fincludeppn' => $fincludeppn,
                    ]);

                    // Sync Stock Details
                    DB::table('trstockdt')->where('fstockmtno', $fstockmtno)->delete();
                    foreach ($stockDetailRows as &$srow) {
                        $srow['fstockmtno'] = $fstockmtno;
                        $srow['fstockmtcode'] = 'REB';
                    }
                    unset($srow);
                    DB::table('trstockdt')->insert($stockDetailRows);
                }

                if (! empty($soUsageByDetailId)) {
                    $dynamicRemainRows = $this->getSoRemainByIds(array_keys($soUsageByDetailId));
                    foreach ($soUsageByDetailId as $detailId => $usedQty) {
                        $remain = (float) ($dynamicRemainRows[$detailId] ?? 0) + (float) ($oldSoUsageByDetailId[$detailId] ?? 0);
                        if ($usedQty - $remain > 0.00001) {
                            throw new \RuntimeException("Qty SO detail #{$detailId} melebihi sisa.");
                        }
                    }
                }
                if (! empty($srjUsageByDetailId)) {
                    $dynamicRemainRows = $this->getSrjRemainByIds(array_keys($srjUsageByDetailId));
                    foreach ($srjUsageByDetailId as $detailId => $usedQty) {
                        $remain = (float) ($dynamicRemainRows[$detailId] ?? 0) + (float) ($oldSrjUsageByDetailId[$detailId] ?? 0);
                        if ($usedQty - $remain > 0.00001) {
                            throw new \RuntimeException("Qty SRJ detail #{$detailId} melebihi sisa.");
                        }
                    }
                }
            });

            // UPDATE STOK - gunakan qtyKecil hasil konversi, bukan qty mentah
            foreach ($detailRows as $row) {
                DB::table('msprd')
                    ->where('fprdcode', $row['fprdcode'])
                    ->update([
                        'fminstock' => DB::raw('CAST(fminstock AS NUMERIC) - '.$row['fqtyremain']),
                        'fupdatedat' => now(),
                    ]);
            }

            return redirect()->route('returpenjualan.index')->with('success', "Retur Penjualan {$header->fsono} berhasil diperbarui.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal update: '.$e->getMessage());
        }
    }

    public function delete(Request $request, $ftranmtid)
    {
        $customers = Customer::orderBy('fcustomername', 'asc')
            ->get(['fcustomerid', 'fcustomername', 'fcustomercode']);

        $salesmans = Salesman::orderBy('fsalesmanname', 'asc')
            ->get(['fsalesmanid', 'fsalesmanname', 'fsalesmancode']);

        $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

        $branch = DB::table('mscabang')
            ->when(is_numeric($raw), fn ($q) => $q->where('fcabangid', (int) $raw))
            ->when(! is_numeric($raw), fn ($q) => $q
                ->where('fcabangkode', $raw)
                ->orWhere('fcabangname', $raw))
            ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

        $fcabang = $branch->fcabangname ?? (string) $raw;   // tampilan
        $fbranchcode = $branch->fcabangkode ?? (string) $raw;   // hidden post

        $returpenjualan = Tranmt::with(['customer', 'details' => function ($q) {
            $q->leftJoin('msprd', function ($j) {
                // Gunakan trandt.fprdcodeid karena sudah integer (tidak perlu CAST lagi)
                $j->on('msprd.fprdid', '=', 'trandt.fprdcodeid');
            })
                ->select(
                    'trandt.*',
                    'msprd.fprdcode as fitemcode',
                    'msprd.fprdname'
                )
                // Ubah order ke ftrandtid (Primary Key detail) karena ftranmtid tidak ada
                ->orderBy('trandt.ftrandtid', 'asc');
        }])->findOrFail($ftranmtid);

        if (! $returpenjualan->customer) {
            $returpenjualan->setRelation('customer', Customer::where('fcustomercode', trim((string) $returpenjualan->fcustno))->first());
        }

        $usageLockMessage = $this->getUsageLockMessage($returpenjualan);

        $savedItems = $returpenjualan->details->map(function ($d) {
            $refCode = strtoupper(trim($d->frefcode ?? ''));
            $valSo = trim($d->frefso ?? '');
            $valSrj = trim($d->frefsrj ?? '');
            // 2. Logika Prioritas Tampilan
            $displayRef = '-';

            // Jika ada SRJ, tampilkan SRJ (biasanya SRJ lebih spesifik untuk retur)
            if ($valSrj !== '') {
                $displayRef = $valSrj;
                $refCode = 'SRJ'; // Paksa refcode jadi SRJ jika ada nilainya
            }
            // Jika tidak ada SRJ tapi ada SO
            elseif ($valSo !== '') {
                $displayRef = $valSo;
                $refCode = 'SO';
            }

            return [
                'uid' => $d->ftrandtid,
                'fitemcode' => (string) ($d->fitemcode ?? ''),  // dari alias msprd.fprdcodeid
                'fitemname' => (string) ($d->fprdname ?? ''),   // dari msprd.fprdname
                'fsatuan' => (string) ($d->fsatuan ?? ''),
                'frefdtno' => (string) ($d->frefdtno ?? ''),
                'fqty' => (float) ($d->fqty ?? 0),
                'fterima' => (float) ($d->fterima ?? 0),
                'fqtyremain' => (float) ($d->fqtyremain ?? 0),
                'fprice' => (float) ($d->fprice ?? 0),
                'fdisc' => (float) ($d->fdisc ?? 0),
                'ftotal' => (float) ($d->famount ?? 0),
                'fdesc' => (string) ($d->fdesc ?? ''),
                'fketdt' => (string) ($d->fketdt ?? ''),
                'frefcode' => $refCode,
                'frefpr' => $displayRef,
            ];
        })->values();
        $selectedSupplierCode = $returpenjualan->fsupplier;

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
                trim($p->fprdcode) => [
                    'name' => $p->fprdname,
                    'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
                    'stock' => $p->fminstock ?? 0,
                ],
            ];
        })->toArray();

        // Pass the data to the view
        return view('returpenjualan.edit', [
            'customers' => $customers,
            'salesmans' => $salesmans,
            'selectedSupplierCode' => $selectedSupplierCode, // Kirim kode supplier ke view
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'products' => $products,
            'productMap' => $productMap,
            'returpenjualan' => $returpenjualan,
            'savedItems' => $savedItems,
            'ppnAmount' => (float) ($returpenjualan->famountpopajak ?? 0), // total PPN from DB
            'famountgross' => (float) ($returpenjualan->famountgross ?? 0),  // nilai Grand Total dari DB
            'famountso' => (float) ($returpenjualan->famountso ?? 0),  // nilai Grand Total dari DB
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'filterSalesmanId' => $request->query('filter_salesman_id'),
            'isUsageLocked' => ! empty($usageLockMessage),
            'usageLockMessage' => $usageLockMessage,
            'action' => 'delete',
        ]);
    }

    public function destroy($ftranmtid)
    {
        try {
            DB::transaction(function () use ($ftranmtid) {
                $returpenjualan = Tranmt::findOrFail($ftranmtid);

                if ($message = $this->getUsageLockMessage($returpenjualan)) {
                    throw new \RuntimeException($message);
                }

                $fsono = $returpenjualan->fsono;

                $oldSoUsageRows = DB::table('trandt')
                    ->where('fsono', $fsono)
                    ->whereNotNull('frefsoid')
                    ->select('frefsoid', DB::raw('SUM(COALESCE(fqtykecil, 0)) as used_qty_kecil'))
                    ->groupBy('frefsoid')
                    ->get();

                $oldSrjUsageRows = DB::table('trandt')
                    ->where('fsono', $fsono)
                    ->whereNotNull('frefsrjid')
                    ->select('frefsrjid', DB::raw('SUM(COALESCE(fqtykecil, 0)) as used_qty_kecil'))
                    ->groupBy('frefsrjid')
                    ->get();

                foreach ($oldSoUsageRows as $row) {
                    $detailId = (int) ($row->frefsoid ?? 0);
                    $qtyKecil = (float) ($row->used_qty_kecil ?? 0);
                    if ($detailId <= 0 || $qtyKecil <= 0) {
                        continue;
                    }

                    DB::table('trsodt')
                        ->where('ftrsodtid', $detailId)
                        ->update([
                            'fqtyremain' => DB::raw('COALESCE(fqtyremain,0) + '.$qtyKecil),
                        ]);
                }

                foreach ($oldSrjUsageRows as $row) {
                    $detailId = (int) ($row->frefsrjid ?? 0);
                    $qtyKecil = (float) ($row->used_qty_kecil ?? 0);
                    if ($detailId <= 0 || $qtyKecil <= 0) {
                        continue;
                    }

                    DB::table('trstockdt')
                        ->where('fstockdtid', $detailId)
                        ->update([
                            'fqtyremain' => DB::raw('COALESCE(fqtyremain,0) + '.$qtyKecil),
                        ]);
                }

                // 1. Delete details (trandt)
                DB::table('trandt')
                    ->where('fsono', $fsono)
                    ->delete();

                // 2. Delete stock records (trstockmt & trstockdt)
                $fstockmtno = str_replace('REJ.', 'REB.', $fsono);
                $stockHeader = DB::table('trstockmt')->where('fstockmtno', $fstockmtno)->first();

                if ($stockHeader) {
                    DB::table('trstockdt')
                        ->where('fstockmtno', $fstockmtno)
                        ->delete();
                    DB::table('trstockmt')->where('fstockmtid', $stockHeader->fstockmtid)->delete();
                }

                // 3. Delete header (tranmt)
                $returpenjualan->delete();
            });

            return redirect()->route('returpenjualan.index')->with('success', 'Data Retur Penjualan berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->route('returpenjualan.index')->with('error', 'Gagal menghapus data: '.$e->getMessage());
        }
    }

    private function getUsageLockMessage($header): ?string
    {
        return null;
    }
}
