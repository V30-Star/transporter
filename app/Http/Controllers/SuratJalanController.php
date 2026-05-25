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
    private function ensureNoDuplicateDetailCodes(array $codes): void
    {
        $seen = [];
        $duplicates = [];

        foreach ($codes as $index => $rawCode) {
            $code = strtoupper(trim((string) $rawCode));
            if ($code === '') {
                continue;
            }

            if (isset($seen[$code])) {
                $duplicates[$index] = $code;
                continue;
            }

            $seen[$code] = true;
        }

        if ($duplicates === []) {
            return;
        }

        $messages = [];
        foreach ($duplicates as $index => $code) {
            $messages["fitemcode.$index"] = "Kode produk {$code} tidak boleh sama dalam satu Surat Jalan.";
        }

        throw ValidationException::withMessages($messages);
    }

    public function index(Request $request)
    {
        // --- 1. PERMISSIONS ---
        $canCreate = in_array('createSuratJalan', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updateSuratJalan', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteSuratJalan', explode(',', session('user_restricted_permissions', '')));
        $showActionsColumn = $canEdit || $canDelete;

        $year = $request->query('year');
        $month = $request->query('month');
        $availableWarehouses = DB::table('mswh')
            ->where(function ($query) {
                $query->whereNull('fnonactive')
                    ->orWhere('fnonactive', '0')
                    ->orWhere('fnonactive', '');
            })
            ->orderBy('fwhname')
            ->pluck('fwhname')
            ->filter()
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->unique()
            ->values();

        // Ambil tahun-tahun yang tersedia dari data
        $availableYearsQuery = PenerimaanPembelianHeader::selectRaw('DISTINCT EXTRACT(YEAR FROM fdatetime) as year')
            ->where('fstockmtcode', 'SRJ')
            ->whereNotNull('fdatetime');
        $this->applyBranchVisibilityScope($availableYearsQuery, 'trstockmt.fbranchcode');
        $availableYears = $availableYearsQuery
            ->orderByRaw('EXTRACT(YEAR FROM fdatetime) DESC')
            ->pluck('year');

        // --- 2. Handle Request AJAX dari DataTables ---
        if ($request->ajax()) {
            $soRefSubquery = DB::table('trstockdt')
                ->selectRaw("
                    fstockmtno,
                    STRING_AGG(DISTINCT NULLIF(TRIM(COALESCE(frefso, '')), ''), ', ' ORDER BY NULLIF(TRIM(COALESCE(frefso, '')), '')) as so_refs
                ")
                ->whereNotNull('frefso')
                ->groupBy('fstockmtno');

            $baseQuery = DB::table('trstockmt')
                ->leftJoin('mscustomer as customer', 'customer.fcustomercode', '=', 'trstockmt.fsupplier')
                ->leftJoin('mswh as warehouse', 'warehouse.fwhcode', '=', 'trstockmt.ffrom')
                ->leftJoinSub($soRefSubquery, 'so_refs', function ($join) {
                    $join->on('so_refs.fstockmtno', '=', 'trstockmt.fstockmtno');
                })
                ->where('trstockmt.fstockmtcode', 'SRJ');
            $this->applyBranchVisibilityScope($baseQuery, 'trstockmt.fbranchcode');

            $query = clone $baseQuery;
            $totalRecords = (clone $baseQuery)->count('trstockmt.fstockmtid');

            if ($search = trim((string) $request->input('search.value'))) {
                $query->where(function ($q) use ($search) {
                    $q->where('trstockmt.fstockmtno', 'ilike', "%{$search}%")
                        ->orWhere('trstockmt.frefpo', 'ilike', "%{$search}%")
                        ->orWhere('so_refs.so_refs', 'ilike', "%{$search}%")
                        ->orWhere('trstockmt.ffrom', 'ilike', "%{$search}%")
                        ->orWhere('warehouse.fwhname', 'ilike', "%{$search}%")
                        ->orWhere('customer.fcustomername', 'ilike', "%{$search}%");
                });
            }

            // Filter tahun
            if ($year) {
                $query->whereRaw('EXTRACT(YEAR FROM fdatetime) = ?', [$year]);
            }

            // Filter bulan
            if ($month) {
                $query->whereRaw('EXTRACT(MONTH FROM fdatetime) = ?', [$month]);
            }

            $columnSearches = collect($request->input('columns', []))
                ->mapWithKeys(function ($column) {
                    $name = trim((string) ($column['name'] ?? ''));
                    $value = trim((string) data_get($column, 'search.value', ''));

                    return $name !== '' ? [$name => $value] : [];
                });

            $warehouseSearch = trim((string) ($columnSearches->get('fgudang', '')));
            if ($warehouseSearch !== '') {
                $query->whereRaw('LOWER(TRIM(COALESCE(warehouse.fwhname, \'\'))) = LOWER(?)', [$warehouseSearch]);
            }

            // Total records setelah filter
            $filteredRecords = (clone $query)->count();

            // Handle Sorting
            $orderColIdx = $request->input('order.0.column', 0);
            $orderDir = $request->input('order.0.dir', 'desc');

            $sortableColumns = [
                'trstockmt.fstockmtno',
                'trstockmt.fstockmtdate',
                'trstockmt.frefpo',
                'so_refs.so_refs',
                'trstockmt.ffrom',
                'customer.fcustomername',
            ];

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
                ->get([
                    'trstockmt.fstockmtid',
                    'trstockmt.fstockmtno',
                    'trstockmt.fstockmtdate',
                    'trstockmt.frefpo',
                    'trstockmt.ffrom',
                    'warehouse.fwhname as warehouse_name',
                    'customer.fcustomername as customer_name',
                    DB::raw("COALESCE(so_refs.so_refs, '') as so_refs"),
                ]);

            $data = $records->map(function ($row) {
                $warehouseLabel = trim((string) ($row->ffrom ?? ''));
                $warehouseName = trim((string) ($row->warehouse_name ?? ''));

                if ($warehouseLabel !== '' && $warehouseName !== '') {
                    $warehouseLabel .= ' - ' . $warehouseName;
                } elseif ($warehouseLabel === '') {
                    $warehouseLabel = $warehouseName;
                }

                return [
                    'fstockmtid' => $row->fstockmtid,
                    'fstockmtno' => $row->fstockmtno,
                    'fstockmtdate' => Carbon::parse($row->fstockmtdate)->format('d/m/Y'),
                    'frefno' => (string) ($row->frefpo ?? ''),
                    'fsono' => (string) ($row->so_refs ?? ''),
                    'fgudang' => $warehouseLabel,
                    'fcustomername' => (string) ($row->customer_name ?? ''),
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
            'availableWarehouses',
            'availableYears',
            'year',
            'month'
        ));
    }

    // Di PenerimaanBarangController
    public function pickable(Request $request)
    {
        $customerCode = trim((string) $request->input('customer_code', $request->input('fcustno', $request->input('fsupplier', ''))));

        $query = DB::table('trstockmt')
            ->leftJoin('mscustomer', 'trstockmt.fsupplier', '=', 'mscustomer.fcustomercode')
            ->where('trstockmt.fstockmtcode', 'SRJ')
            ->where('trstockmt.fprdout', '0')
            ->whereNotExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('trstockdt as srj_dt')
                    ->join('trsomt as so_hdr', 'so_hdr.fsono', '=', 'srj_dt.frefso')
                    ->whereColumn('srj_dt.fstockmtno', 'trstockmt.fstockmtno')
                    ->whereRaw("COALESCE(TRIM(CAST(so_hdr.fneedacc AS TEXT)), '0') = '1'");
            })
            ->select(
                'trstockmt.fstockmtid',
                'trstockmt.fstockmtno',
                'trstockmt.frefpo',
                'trstockmt.fstockmtdate',
                'mscustomer.fcustomercode',
                'mscustomer.fcustomername as fsuppliername',
                'mscustomer.faddress'
            );

        if ($customerCode !== '') {
            $query->whereRaw('TRIM(COALESCE(trstockmt.fsupplier, \'\')) = ?', [$customerCode]);
        }

        // Filter Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('trstockmt.fstockmtno', 'ilike', "%{$search}%")
                    ->orWhere('trstockmt.frefpo', 'ilike', "%{$search}%")
                    ->orWhere('mscustomer.fcustomercode', 'ilike', "%{$search}%")
                    ->orWhere('mscustomer.fcustomername', 'ilike', "%{$search}%")
                    ->orWhere('mscustomer.faddress', 'ilike', "%{$search}%");
            });
        }

        $recordsTotal = DB::table('trstockmt')
            ->where('trstockmt.fstockmtcode', 'SRJ')
            ->where('trstockmt.fprdout', '0')
            ->when($customerCode !== '', function ($query) use ($customerCode) {
                $query->whereRaw('TRIM(COALESCE(trstockmt.fsupplier, \'\')) = ?', [$customerCode]);
            })
            ->whereNotExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('trstockdt as srj_dt')
                    ->join('trsomt as so_hdr', 'so_hdr.fsono', '=', 'srj_dt.frefso')
                    ->whereColumn('srj_dt.fstockmtno', 'trstockmt.fstockmtno')
                    ->whereRaw("COALESCE(TRIM(CAST(so_hdr.fneedacc AS TEXT)), '0') = '1'");
            })
            ->count();
        $recordsFiltered = $query->count();

        $allowedColumns = ['fstockmtno', 'fstockmtdate', 'fcustomercode', 'fsuppliername', 'faddress', 'frefpo'];
        $orderColumn = (string) $request->input('order_column', 'fstockmtdate');
        $orderDir = strtolower((string) $request->input('order_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (in_array($orderColumn, $allowedColumns, true)) {
            if (in_array($orderColumn, ['fcustomercode', 'fsuppliername', 'faddress'], true)) {
                $query->orderBy('mscustomer.' . $orderColumn, $orderDir);
            } else {
                $query->orderBy('trstockmt.' . $orderColumn, $orderDir);
            }
        } else {
            $query->orderBy('trstockmt.fstockmtdate', 'desc');
        }

        $data = $query
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
            ->leftJoin('mscustomer', 'mscustomer.fcustomercode', '=', 'trstockmt.fsupplier')
            ->where('trstockmt.fstockmtid', $id)
            ->where('trstockmt.fstockmtcode', 'SRJ')
            ->select('trstockmt.*', 'mscustomer.fcustomername as fsuppliername')
            ->first();

        if (! $header) {
            return response()->json(['message' => 'Data tidak ada.'], 404);
        }

        $hasBlockedSoReference = DB::table('trstockdt as srj_dt')
            ->join('trsomt as so_hdr', 'so_hdr.fsono', '=', 'srj_dt.frefso')
            ->where('srj_dt.fstockmtno', $header->fstockmtno)
            ->whereRaw("COALESCE(TRIM(CAST(so_hdr.fneedacc AS TEXT)), '0') = '1'")
            ->exists();

        if ($hasBlockedSoReference) {
            return response()->json(['message' => 'Data SRJ belum bisa dipakai. Referensi sales order masih menunggu approval.'], 403);
        }

        $remainMap = $this->getSrjRemainByStockNo($header->fstockmtno);

        $items = DB::table('trstockdt')
            ->where('trstockdt.fstockmtno', $header->fstockmtno)
            ->leftJoin('msprd', 'msprd.fprdcode', '=', 'trstockdt.fprdcode')
            ->select(
                'trstockdt.fstockdtid as frefdtno',
                DB::raw("TRIM(BOTH ', ' FROM CONCAT_WS(', ', NULLIF(TRIM(COALESCE(trstockdt.frefnoacak::text, '')), ''), NULLIF(TRIM(COALESCE(trstockdt.fnoacak::text, '')), ''))) as frefnoacak"),
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

    private function normalizeRandomNumber($value, array &$usedNumbers): string
    {
        $value = trim((string) ($value ?? ''));
        $candidate = preg_match('/^[1-9]{3}$/', $value) ? $value : null;

        if ($candidate !== null && ! in_array($candidate, $usedNumbers, true)) {
            $usedNumbers[] = $candidate;

            return $candidate;
        }

        do {
            $candidate = (string) random_int(1, 9) . random_int(1, 9) . random_int(1, 9);
        } while (in_array($candidate, $usedNumbers, true));

        $usedNumbers[] = $candidate;

        return $candidate;
    }

    private function normalizeReferenceRandomNumber($value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return preg_match('/^\d{3}$/', $value) ? $value : null;
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
            return redirect()->back()->with('error', 'PO tidak ada.');
        }

        DB::table('trstockmt')->where('fstockmtno', $hdr->fstockmtno)->update(['fprint' => 1]);

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

        return view('suratjalan.create', [
            'newtr_prh_code' => $newtr_prh_code,
            'warehouses' => $warehouses,
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
            'fnoacak' => ['nullable', 'array'],
            'fnoacak.*' => ['nullable', 'regex:/^[1-9]{3}$/'],
            'frefnoacak' => ['nullable', 'array'],
            'frefnoacak.*' => ['nullable', 'regex:/^\d{3}$/'],
        ]);

        $this->ensureNoDuplicateDetailCodes($request->input('fitemcode', []));

        // =========================
        // 2) HEADER FIELDS
        // =========================
        $fstockmtno = trim((string) $request->input('fstockmtno'));
        $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
        $this->ensureCreateDateWithinEditPeriod($fstockmtdate);
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
        $fnoacaks = $request->input('fnoacak', []);
        $frefnoacaks = $request->input('frefnoacak', []);

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
        $usedNoAcaks = [];

        for ($i = 0; $i < $rowCount; $i++) {
            $code = trim((string) ($codes[$i] ?? ''));
            $sat = trim((string) ($satuans[$i] ?? ''));
            $rref = $refdtno[$i] ?? null;
            $qty = (float) ($qtys[$i] ?? 0);
            $price = (float) ($prices[$i] ?? 0);
            $desc = (string) ($descs[$i] ?? '');

            $meta = $prodMeta[$code] ?? null;

            $frefdtnoValue = ($rref !== null && $rref !== '') ? (int) $rref : null;
            $refDoc = trim((string) ($frefso[$i] ?? ''));

            if ($frefdtnoValue > 0 && $refDoc !== '') {
                if ($this->isInvoiceReferenceDoc($refDoc)) {
                    $refSat = DB::table('trstockdt')
                        ->where('fstockdtid', $frefdtnoValue)
                        ->value('fsatuan');
                    if ($refSat) {
                        $sat = trim($refSat);
                    }
                } else {
                    $refSat = DB::table('trsodt')
                        ->where('ftrsodtid', $frefdtnoValue)
                        ->value('fsatuan');
                    if ($refSat) {
                        $sat = trim($refSat);
                    }
                }
            }

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
                'frefdtno' => $frefdtnoValue,
                'fqty' => $qty,
                'fprice' => $price,
                'fprice_rp' => $price * $frate,
                'ftotprice' => $amount,
                'ftotprice_rp' => $amount * $frate,
                'fusercreate' => Auth::user()->fname ?? 'system',
                'fdatetime' => $now,
                'fketdt' => '',
                'fcode' => $this->resolveSuratJalanFcode([
                    'frefso' => $frefso[$i] ?? null,
                ]),
                'frefso' => $frefso[$i] ?? null,
                'fnoacak' => $this->normalizeRandomNumber($fnoacaks[$i] ?? null, $usedNoAcaks),
                'frefnoacak' => trim((string) ($frefso[$i] ?? '')) !== ''
                    ? $this->normalizeReferenceRandomNumber($frefnoacaks[$i] ?? null)
                    : null,
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

        $soUsageByReference = $this->buildSuratJalanReferenceUsageMap($rowsDt);
        $invoiceReferenceDocs = $this->extractInvoiceReferenceDocs($rowsDt);

        if ($validationMessage = $this->validateUniqueReferenceUsage($soUsageByReference)) {
            return back()->withInput()->withErrors([
                'detail' => $validationMessage,
            ]);
        }

        // =========================
        // 6.5) VALIDASI QTY REMAIN SO
        // =========================
        if ($validationMessage = $this->validateSoUsageRequest($soUsageByReference)) {
            return back()->withInput()->withErrors([
                'detail' => $validationMessage,
            ]);
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
                $ppnAmount
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
                'detail' => 'Data belum berhasil disimpan. Cek isian transaksi.',
            ]);
        }

        $this->syncInvoiceOutFlags($invoiceReferenceDocs);

        return redirect()
            ->route('suratjalan.create')
            ->with('success', "Surat jalan {$fstockmtno} berhasil disimpan.");
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
                    ->join('msprd', 'msprd.fprdcode', '=', 'trstockdt.fprdcode')
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

        if ($message = $this->getPostedPeriodLockMessage($suratjalan->fstockmtdate, 'Surat Jalan ini')) {
            return redirect()
                ->route('suratjalan.view', $suratjalan->fstockmtid)
                ->with('error', $message);
        }

        // 4. Map the data for savedItems (sudah menggunakan data yang benar)
        $usageLockMessage = $this->getUsageLockMessage($suratjalan);

        if (! empty($usageLockMessage)) {
            return redirect()
                ->route('suratjalan.view', $suratjalan->fstockmtid)
                ->with('error', $usageLockMessage);
        }
        $soReferenceStats = $this->getSoReferenceStats(
            $suratjalan->details->pluck('frefso')->filter()->map(fn($value) => trim((string) $value))->unique()->values()->all(),
            $suratjalan->fstockmtno
        );

        $savedItems = $suratjalan->details->map(function ($d) use ($soReferenceStats) {
            $referenceKey = $this->buildSoReferenceUsageKey($d->frefso ?? '', $d->fprdcode ?? '', $d->frefnoacak ?? '');
            $stat = $soReferenceStats[$referenceKey] ?? null;
            $maxqty = max(0, (float) ($d->fqty ?? 0) + (float) ($stat['remain_qty_kecil'] ?? 0));
            return [
                'uid' => $d->fstockdtid,
                'fitemcode' => $d->fitemcode_text ?? '',
                'fitemname' => $d->fprdname ?? '',
                'fsatuan' => $d->fsatuan ?? '',
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
                'frefno_display' => $d->frefso ?? $d->fpono ?? '-',
                'frefso' => $d->frefso ?? null,
                'fnoacak' => (string) ($d->fnoacak ?? ''),
                'frefnoacak' => (string) ($d->frefnoacak ?? ''),
                'fqtyremain' => $maxqty,
                'maxqty' => $maxqty,
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
            'isUsageLocked' => ! empty($usageLockMessage),
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
                    ->join('msprd', 'msprd.fprdcode', '=', 'trstockdt.fprdcode')
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
        $soReferenceStats = $this->getSoReferenceStats(
            $suratjalan->details->pluck('frefso')->filter()->map(fn($value) => trim((string) $value))->unique()->values()->all(),
            $suratjalan->fstockmtno
        );

        $savedItems = $suratjalan->details->map(function ($d) use ($soReferenceStats) {
            $referenceKey = $this->buildSoReferenceUsageKey($d->frefso ?? '', $d->fprdcode ?? '', $d->frefnoacak ?? '');
            $stat = $soReferenceStats[$referenceKey] ?? null;
            $maxqty = max(0, (float) ($d->fqty ?? 0) + (float) ($stat['remain_qty_kecil'] ?? 0));
            return [
                'uid' => $d->fstockdtid,
                'fitemcode' => $d->fitemcode_text ?? '',
                'fitemname' => $d->fprdname ?? '',
                'fsatuan' => $d->fsatuan ?? '',
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
                'frefno_display' => $d->frefso ?? $d->fpono ?? '-',
                'frefso' => $d->frefso ?? null,
                'fnoacak' => (string) ($d->fnoacak ?? ''),
                'frefnoacak' => (string) ($d->frefnoacak ?? ''),
                'fqtyremain' => $maxqty,
                'maxqty' => $maxqty,
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
            'isUsageLocked' => false,
            'usageLockMessage' => null,
            'action' => 'view',
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
            'fnoacak' => ['nullable', 'array'],
            'fnoacak.*' => ['nullable', 'regex:/^[1-9]{3}$/'],
            'frefnoacak' => ['nullable', 'array'],
            'frefnoacak.*' => ['nullable', 'regex:/^\d{3}$/'],
        ]);

        $this->ensureNoDuplicateDetailCodes($request->input('fitemcode', []));

        // =========================
        // 2) AMBIL DATA HEADER
        // =========================
        $header = PenerimaanPembelianHeader::findOrFail($fstockmtid);

        if ($message = $this->getPostedPeriodLockMessage($header->fstockmtdate, 'Surat Jalan ini')) {
            return redirect()->route('suratjalan.view', $header->fstockmtid)->with('error', $message);
        }

        if ($message = $this->getUsageLockMessage($header)) {
            return redirect()->route('suratjalan.index')->with('error', $message);
        }

        $fstockmtno = $header->fstockmtno;
        $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
        $this->ensureCreateDateWithinEditPeriod($fstockmtdate, $header->fstockmtdate);
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
        $fnoacaks = $request->input('fnoacak', []);
        $frefnoacaks = $request->input('frefnoacak', []);

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
        $usedNoAcaks = [];

        for ($i = 0; $i < $rowCount; $i++) {
            $code = trim((string) ($codes[$i] ?? ''));
            $sat = trim((string) ($satuans[$i] ?? ''));
            $rref = $refdtno[$i] ?? null;
            $qty = (float) ($qtys[$i] ?? 0);
            $price = (float) ($prices[$i] ?? 0);
            $desc = (string) ($descs[$i] ?? '');

            $meta = $prodMeta[$code] ?? null;

            $frefdtnoValue = ($rref !== null && $rref !== '') ? (int) $rref : null;
            $refDoc = trim((string) ($frefso[$i] ?? ''));

            if ($frefdtnoValue > 0 && $refDoc !== '') {
                if ($this->isInvoiceReferenceDoc($refDoc)) {
                    $refSat = DB::table('trstockdt')
                        ->where('fstockdtid', $frefdtnoValue)
                        ->value('fsatuan');
                    if ($refSat) {
                        $sat = trim($refSat);
                    }
                } else {
                    $refSat = DB::table('trsodt')
                        ->where('ftrsodtid', $frefdtnoValue)
                        ->value('fsatuan');
                    if ($refSat) {
                        $sat = trim($refSat);
                    }
                }
            }

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
                'fcode' => $this->resolveSuratJalanFcode([
                    'frefso' => $frefso[$i] ?? null,
                ]),
                'frefso' => $frefso[$i] ?? null,
                'fnoacak' => $this->normalizeRandomNumber($fnoacaks[$i] ?? null, $usedNoAcaks),
                'frefnoacak' => trim((string) ($frefso[$i] ?? '')) !== ''
                    ? $this->normalizeReferenceRandomNumber($frefnoacaks[$i] ?? null)
                    : null,
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

        $soUsageByReference = $this->buildSuratJalanReferenceUsageMap($rowsDt);
        $oldInvoiceReferenceDocs = DB::table('trstockdt')
            ->where('fstockmtno', $header->fstockmtno)
            ->pluck('frefso')
            ->filter(fn($value) => $this->isInvoiceReferenceDoc((string) $value))
            ->values()
            ->all();
        $newInvoiceReferenceDocs = $this->extractInvoiceReferenceDocs($rowsDt);

        if ($validationMessage = $this->validateUniqueReferenceUsage($soUsageByReference, $header->fstockmtno)) {
            return back()->withInput()->withErrors([
                'detail' => $validationMessage,
            ]);
        }

        // =========================
        // 5.5) VALIDASI QTY REMAIN SO
        // =========================
        if ($validationMessage = $this->validateSoUsageRequest($soUsageByReference, $header->fstockmtno)) {
            return back()->withInput()->withErrors([
                'detail' => $validationMessage,
            ]);
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
                'detail' => 'Data belum berhasil diperbarui. Cek isian transaksi.',
            ]);
        }

        $this->syncInvoiceOutFlags(array_merge($oldInvoiceReferenceDocs, $newInvoiceReferenceDocs));

        return redirect()
            ->route('suratjalan.index')
            ->with('success', "Surat jalan {$fstockmtno} berhasil diupdate.");
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
                    ->join('msprd', 'msprd.fprdcode', '=', 'trstockdt.fprdcode')
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

        if ($message = $this->getPostedPeriodLockMessage($suratjalan->fstockmtdate, 'Surat Jalan ini')) {
            return redirect()
                ->route('suratjalan.view', $suratjalan->fstockmtid)
                ->with('error', $message);
        }

        // 4. Map the data for savedItems (sudah menggunakan data yang benar)
        $usageLockMessage = $this->getUsageLockMessage($suratjalan);

        if (! empty($usageLockMessage)) {
            return redirect()
                ->route('suratjalan.view', $suratjalan->fstockmtid)
                ->with('error', $usageLockMessage);
        }
        $soReferenceStats = $this->getSoReferenceStats(
            $suratjalan->details->pluck('frefso')->filter()->map(fn($value) => trim((string) $value))->unique()->values()->all(),
            $suratjalan->fstockmtno
        );

        $savedItems = $suratjalan->details->map(function ($d) use ($soReferenceStats) {
            $referenceKey = $this->buildSoReferenceUsageKey($d->frefso ?? '', $d->fprdcode ?? '', $d->frefnoacak ?? '');
            $stat = $soReferenceStats[$referenceKey] ?? null;
            $maxqty = max(0, (float) ($d->fqty ?? 0) + (float) ($stat['remain_qty_kecil'] ?? 0));
            return [
                'uid' => $d->fstockdtid,
                'fitemcode' => $d->fitemcode_text ?? '',
                'fitemname' => $d->fprdname ?? '',
                'fsatuan' => $d->fsatuan ?? '',
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
                'frefno_display' => $d->frefso ?? $d->fpono ?? '-',
                'frefso' => $d->frefso ?? null,
                'fnoacak' => (string) ($d->fnoacak ?? ''),
                'frefnoacak' => (string) ($d->frefnoacak ?? ''),
                'fqtyremain' => $maxqty,
                'maxqty' => $maxqty,
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
            'isUsageLocked' => ! empty($usageLockMessage),
            'usageLockMessage' => $usageLockMessage,
            'action' => 'delete',
        ]);
    }

    public function destroy($fstockmtid)
    {
        try {
            $suratjalan = PenerimaanPembelianHeader::findOrFail($fstockmtid);

            if ($message = $this->getPostedPeriodLockMessage($suratjalan->fstockmtdate, 'Surat Jalan ini')) {
                return redirect()->route('suratjalan.view', $suratjalan->fstockmtid)->with('error', $message);
            }

            $invoiceReferenceDocs = DB::table('trstockdt')
                ->where('fstockmtno', $suratjalan->fstockmtno)
                ->pluck('frefso')
                ->filter(fn($value) => $this->isInvoiceReferenceDoc((string) $value))
                ->values()
                ->all();

            if ($message = $this->getUsageLockMessage($suratjalan)) {
                return redirect()->route('suratjalan.index')->with('error', $message);
            }

            DB::transaction(function () use ($suratjalan) {
                DB::table('trstockdt')
                    ->where('fstockmtno', $suratjalan->fstockmtno)
                    ->delete();

                $suratjalan->delete();
            });

            $this->syncInvoiceOutFlags($invoiceReferenceDocs);

            return redirect()->route('suratjalan.index')->with('success', 'Surat jalan ' . $suratjalan->fstockmtno . ' berhasil dihapus.');
        } catch (\Exception $e) {
            // Jika terjadi kesalahan saat menghapus, kembali ke halaman delete dengan pesan error
            return redirect()->route('suratjalan.delete', $fstockmtid)->with('error', 'Surat jalan belum bisa dihapus. Coba lagi.');
        }
    }

    private function buildSoReferenceUsageKey(?string $docNo, ?string $productCode, ?string $refNoAcak = null): string
    {
        return implode('|', [
            trim((string) ($docNo ?? '')),
            trim((string) ($productCode ?? '')),
            trim((string) ($refNoAcak ?? '')),
        ]);
    }

    private function isInvoiceReferenceDoc(string $docNo): bool
    {
        return str_starts_with(strtoupper(trim($docNo)), 'INV.');
    }

    private function extractSoReferenceDocsFromKeys(array $keys): array
    {
        return collect($keys)
            ->map(fn($key) => explode('|', (string) $key)[0] ?? '')
            ->filter(fn($value) => trim((string) $value) !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function buildSuratJalanReferenceUsageMap(array $rowsDt): array
    {
        $usage = [];

        foreach ($rowsDt as $row) {
            $qtyKecil = (float) ($row['fqtykecil'] ?? 0);
            $docNo = trim((string) ($row['frefso'] ?? ''));
            $productCode = trim((string) ($row['fprdcode'] ?? ''));
            $refNoAcak = trim((string) ($row['frefnoacak'] ?? ''));

            if ($qtyKecil <= 0 || $docNo === '' || $productCode === '') {
                continue;
            }

            $key = $this->buildSoReferenceUsageKey($docNo, $productCode, $refNoAcak);
            $usage[$key] = ($usage[$key] ?? 0) + $qtyKecil;
        }

        return $usage;
    }

    private function getSoReferenceStats(array $docNos, ?string $exceptStockMtNo = null): array
    {
        $docNos = collect($docNos)
            ->map(fn($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($docNos)) {
            return [];
        }

        $invoiceDocNos = array_values(array_filter($docNos, fn($docNo) => $this->isInvoiceReferenceDoc((string) $docNo)));
        $soDocNos = array_values(array_filter($docNos, fn($docNo) => ! $this->isInvoiceReferenceDoc((string) $docNo)));

        $sourceRows = collect();

        if (! empty($soDocNos)) {
            $sourceRows = $sourceRows->merge(
                DB::table('trsodt as d')
                    ->leftJoin('msprd as p', 'p.fprdcode', '=', 'd.fprdcode')
                    ->whereIn('d.fsono', $soDocNos)
                    ->selectRaw("
                        TRIM(d.fsono) as ref_doc,
                        TRIM(d.fprdcode) as product_code,
                        MAX(COALESCE(p.fprdname, d.fprdcode)) as product_name,
                        SUM(COALESCE(d.fqtykecil, 0)) as source_qty_kecil
                    ")
                    ->groupByRaw("TRIM(d.fsono), TRIM(d.fprdcode)")
                    ->get()
            );
        }

        if (! empty($invoiceDocNos)) {
            $sourceRows = $sourceRows->merge(
                DB::table('trandt as d')
                    ->leftJoin('msprd as p', 'p.fprdcode', '=', 'd.fprdcode')
                    ->join('tranmt as h', 'h.fsono', '=', 'd.fsono')
                    ->where('h.ftrcode', 'INV')
                    ->whereIn('d.fsono', $invoiceDocNos)
                    ->selectRaw("
                        TRIM(d.fsono) as ref_doc,
                        TRIM(d.fprdcode) as product_code,
                        COALESCE(d.fnoacak::text, '') as ref_noacak,
                        MAX(COALESCE(p.fprdname, d.fprdcode)) as product_name,
                        SUM(COALESCE(d.fqtykecil, 0)) as source_qty_kecil
                    ")
                    ->groupByRaw("TRIM(d.fsono), TRIM(d.fprdcode), COALESCE(d.fnoacak::text, '')")
                    ->get()
            );
        }

        $usageRows = DB::table('trstockdt as d')
            ->join('trstockmt as h', 'h.fstockmtno', '=', 'd.fstockmtno')
            ->where('h.fstockmtcode', 'SRJ')
            ->whereIn('d.frefso', $docNos)
            ->when($exceptStockMtNo, fn($query) => $query->where('h.fstockmtno', '<>', $exceptStockMtNo))
            ->selectRaw("
                TRIM(d.frefso) as ref_doc,
                TRIM(d.fprdcode) as product_code,
                COALESCE(d.frefnoacak::text, '') as ref_noacak,
                SUM(COALESCE(d.fqtykecil, 0)) as used_qty_kecil,
                MIN(h.fstockmtno) as used_by_transaction
            ")
            ->groupByRaw("TRIM(d.frefso), TRIM(d.fprdcode), COALESCE(d.frefnoacak::text, '')")
            ->get();

        $stats = [];

        foreach ($sourceRows as $row) {
            $key = $this->buildSoReferenceUsageKey($row->ref_doc ?? '', $row->product_code ?? '', $row->ref_noacak ?? '');
            $stats[$key] = [
                'ref_doc' => trim((string) ($row->ref_doc ?? '')),
                'product_code' => trim((string) ($row->product_code ?? '')),
                'product_name' => trim((string) ($row->product_name ?? '')),
                'source_qty_kecil' => (float) ($row->source_qty_kecil ?? 0),
                'used_qty_kecil' => 0.0,
                'remain_qty_kecil' => (float) ($row->source_qty_kecil ?? 0),
                'used_by_transaction' => '',
            ];
        }

        foreach ($usageRows as $row) {
            $key = $this->buildSoReferenceUsageKey($row->ref_doc ?? '', $row->product_code ?? '', $row->ref_noacak ?? '');
            if (! isset($stats[$key])) {
                $stats[$key] = [
                    'ref_doc' => trim((string) ($row->ref_doc ?? '')),
                    'product_code' => trim((string) ($row->product_code ?? '')),
                    'product_name' => trim((string) ($row->product_code ?? '')),
                    'source_qty_kecil' => 0.0,
                    'used_qty_kecil' => 0.0,
                    'remain_qty_kecil' => 0.0,
                    'used_by_transaction' => '',
                ];
            }

            $stats[$key]['used_qty_kecil'] = (float) ($row->used_qty_kecil ?? 0);
            $stats[$key]['remain_qty_kecil'] = max(0, (float) $stats[$key]['source_qty_kecil'] - (float) $stats[$key]['used_qty_kecil']);
            $stats[$key]['used_by_transaction'] = trim((string) ($row->used_by_transaction ?? ''));
        }

        return $stats;
    }

    private function validateSoUsageRequest(array $requestedUsageByReference, ?string $exceptStockMtNo = null): ?string
    {
        if (empty($requestedUsageByReference)) {
            return null;
        }

        $stats = $this->getSoReferenceStats(
            $this->extractSoReferenceDocsFromKeys(array_keys($requestedUsageByReference)),
            $exceptStockMtNo
        );

        foreach ($requestedUsageByReference as $referenceKey => $requestedQtyKecil) {
            $stat = $stats[$referenceKey] ?? null;
            $availableQtyKecil = max(0, (float) ($stat['remain_qty_kecil'] ?? 0));
            $docNo = trim((string) ($stat['ref_doc'] ?? ''));
            $docLabel = $this->isInvoiceReferenceDoc($docNo) ? 'Faktur Penjualan' : 'SO';
            if ($availableQtyKecil <= 0) {
                $product = trim((string) ($stat['product_name'] ?? $stat['product_code'] ?? $referenceKey));
                return 'Qty Surat Jalan untuk item ' . $product . ($docNo !== '' ? ' pada ' . $docLabel . ' ' . $docNo : '') . ' sudah habis atau sudah dipakai.';
            }

            if ((float) $requestedQtyKecil - $availableQtyKecil > 0.000001) {
                $product = trim((string) ($stat['product_name'] ?? $stat['product_code'] ?? $referenceKey));
                return 'Qty Surat Jalan untuk item ' . $product . ($docNo !== '' ? ' pada ' . $docLabel . ' ' . $docNo : '') . ' melebihi sisa qty yang tersedia.';
            }
        }

        return null;
    }

    private function validateUniqueReferenceUsage(array $usageByReference, ?string $exceptStockMtNo = null): ?string
    {
        if (empty($usageByReference)) {
            return null;
        }

        $stats = $this->getSoReferenceStats(
            $this->extractSoReferenceDocsFromKeys(array_keys($usageByReference)),
            $exceptStockMtNo
        );

        foreach ($usageByReference as $referenceKey => $qtyKecil) {
            if ((float) ($stats[$referenceKey]['used_qty_kecil'] ?? 0) > 0) {
                $refNo = trim((string) ($stats[$referenceKey]['ref_doc'] ?? ''));
                $transactionNo = trim((string) ($stats[$referenceKey]['used_by_transaction'] ?? ''));
                return 'No. referensi ' . $refNo . ' sudah ada di transaksi ' . $transactionNo . '.';
            }
        }

        return null;
    }

    /**
     * Hitung sisa qty SRJ dinamis dalam satuan kecil per detail SRJ.
     *
     * @param  array<int, int|string>  $srjDetailIds
     * @return array<int, float>
     */
    private function getSrjRemainByStockNo(string $stockMtNo): array
    {
        $stockMtNo = trim($stockMtNo);
        if ($stockMtNo === '') {
            return [];
        }

        $sourceRows = DB::table('trstockdt as d')
            ->where('d.fstockmtno', $stockMtNo)
            ->selectRaw("
                d.fstockdtid,
                TRIM(COALESCE(d.fprdcode::text, '')) as product_code,
                COALESCE(d.frefnoacak::text, '') as ref_noacak,
                COALESCE(d.fqtykecil, 0) as source_qty_kecil
            ")
            ->get();

        if ($sourceRows->isEmpty()) {
            return [];
        }

        $usageRows = DB::table('trandt as d')
            ->where('d.frefsrj', $stockMtNo)
            ->selectRaw("
                TRIM(COALESCE(d.fprdcode::text, '')) as product_code,
                COALESCE(d.frefnosrjacak::text, '') as ref_noacak,
                SUM(COALESCE(d.fqtykecil, 0)) as used_kecil
            ")
            ->groupByRaw("TRIM(COALESCE(d.fprdcode::text, '')), COALESCE(d.frefnosrjacak::text, '')")
            ->get()
            ->keyBy(fn($row) => $this->buildSrjRemainKey($row->product_code ?? '', $row->ref_noacak ?? ''));

        $result = [];
        foreach ($sourceRows as $row) {
            $key = $this->buildSrjRemainKey($row->product_code ?? '', $row->ref_noacak ?? '');
            $used = (float) ($usageRows[$key]->used_kecil ?? 0);
            $result[(int) $row->fstockdtid] = max(0, (float) ($row->source_qty_kecil ?? 0) - $used);
        }

        return $result;
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

        return 'Surat Jalan ' . $fstockmtno . ' sudah dipakai: ' . implode('; ', $parts) . '.';
    }

    private function resolveSuratJalanFcode(array $row): string
    {
        $docNo = trim((string) ($row['frefso'] ?? ''));
        if ($docNo === '') {
            return '0';
        }

        return $this->isInvoiceReferenceDoc($docNo) ? 'I' : 'S';
    }

    private function buildSrjRemainKey(?string $productCode, ?string $refNoAcak): string
    {
        return trim((string) ($productCode ?? '')) . '|' . trim((string) ($refNoAcak ?? ''));
    }

    private function extractInvoiceReferenceDocs(array $rowsDt): array
    {
        return collect($rowsDt)
            ->map(fn($row) => trim((string) ($row['frefso'] ?? '')))
            ->filter(fn($docNo) => $this->isInvoiceReferenceDoc((string) $docNo))
            ->unique()
            ->values()
            ->all();
    }

    private function syncInvoiceOutFlags(array $invoiceNos): void
    {
        $invoiceNos = collect($invoiceNos)
            ->map(fn($value) => trim((string) $value))
            ->filter(fn($value) => $this->isInvoiceReferenceDoc((string) $value))
            ->unique()
            ->values()
            ->all();

        if (empty($invoiceNos)) {
            return;
        }

        foreach ($invoiceNos as $invoiceNo) {
            $hasUsage = DB::table('trstockdt')
                ->where('fcode', 'I')
                ->where('frefso', $invoiceNo)
                ->exists();

            DB::table('tranmt')
                ->where('ftrcode', 'INV')
                ->where('fsono', $invoiceNo)
                ->update([
                    'fprdout' => $hasUsage ? '1' : '0',
                ]);
        }
    }
}
