<?php

namespace App\Http\Controllers;

use App\Models\PenerimaanPembelianDetail;
use App\Models\PenerimaanPembelianHeader;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Tr_pod;
use App\Models\Tr_poh;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Facades\Excel;

class AdjstockController extends Controller
{
    private const DAILY_CREATE_LIMIT = 15;

    private function todayCreateCount(): int
    {
        return PenerimaanPembelianHeader::where('fstockmtcode', 'ADJ')
            ->whereBetween('fdatetime', [now()->startOfDay(), now()->endOfDay()])
            ->count();
    }

    private function hasReachedDailyCreateLimit(): bool
    {
        return $this->todayCreateCount() >= self::DAILY_CREATE_LIMIT;
    }

    private function canApproveAdjustmentStock(): bool
    {
        $permissions = array_map(fn($p) => strtolower(trim((string) $p)), explode(',', (string) session('user_restricted_permissions', '')));

        return in_array('approveadjustmentstock', $permissions, true)
            || in_array('approveadjstock', $permissions, true)
            || in_array('approvetrstockmt', $permissions, true)
            || in_array('approvepenerimaanbarang', $permissions, true);
    }

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
            $messages["fitemcode.$index"] = "Kode produk {$code} tidak boleh sama dalam satu Adjustment Stock.";
        }

        throw ValidationException::withMessages($messages);
    }

    public function index(Request $request)
    {
        // --- 1. PERMISSIONS ---
        $canCreate = in_array('createPenerimaanBarang', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updatePenerimaanBarang', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deletePenerimaanBarang', explode(',', session('user_restricted_permissions', '')));
        $showActionsColumn = $canEdit || $canDelete;
        $year = trim((string) $request->query('year', ''));
        $month = trim((string) $request->query('month', ''));
        $createLimitReached = $this->hasReachedDailyCreateLimit();
        $availableYearsQuery = DB::table('trstockmt')
            ->where('fstockmtcode', 'ADJ')
            ->whereNotNull('fstockmtdate')
            ->selectRaw('DISTINCT EXTRACT(YEAR FROM fstockmtdate) as year');
        $this->applyBranchVisibilityScope($availableYearsQuery, 'trstockmt.fbranchcode');
        $availableYears = $availableYearsQuery
            ->orderByRaw('EXTRACT(YEAR FROM fstockmtdate) DESC')
            ->pluck('year');

        // --- 2. Handle Request AJAX dari DataTables ---
        if ($request->ajax()) {

            // Query dasar HANYA untuk 'ADJ' (Adjustment)
            $query = PenerimaanPembelianHeader::query()
                ->leftJoin('mscabang as c', 'c.fcabangkode', '=', 'trstockmt.fbranchcode')
                ->leftJoin('mswh as w', 'w.fwhcode', '=', 'trstockmt.ffrom')
                ->where('trstockmt.fstockmtcode', 'ADJ');
            $this->applyBranchVisibilityScope($query, 'trstockmt.fbranchcode');

            // Total records (dengan filter 'ADJ')
            $totalRecords = (clone $query)->count();

            // Handle Search (cari di No. Adjustment)
            if ($search = $request->input('search.value')) {
                $query->where(function ($q) use ($search) {
                    $q->where('trstockmt.fstockmtno', 'like', "%{$search}%")
                        ->orWhere('trstockmt.fket', 'like', "%{$search}%");
                });
            }

            if ($year !== '') {
                $query->whereRaw('EXTRACT(YEAR FROM trstockmt.fstockmtdate) = ?', [$year]);
            }

            if ($month !== '') {
                $query->whereRaw('EXTRACT(MONTH FROM trstockmt.fstockmtdate) = ?', [$month]);
            }

            $columnSearches = collect($request->input('columns', []))
                ->mapWithKeys(function ($column) {
                    $name = trim((string) ($column['name'] ?? ''));
                    $value = trim((string) data_get($column, 'search.value', ''));

                    return $name !== '' ? [$name => $value] : [];
                });

            $warehouseSearch = trim((string) ($columnSearches->get('fgudang', '')));
            if ($warehouseSearch !== '') {
                $query->where(function ($warehouseQuery) use ($warehouseSearch) {
                    $warehouseQuery
                        ->whereRaw('LOWER(TRIM(COALESCE(w.fwhname, \'\'))) LIKE LOWER(?)', ['%'.$warehouseSearch.'%'])
                        ->orWhereRaw('LOWER(TRIM(COALESCE(trstockmt.ffrom, \'\'))) LIKE LOWER(?)', ['%'.$warehouseSearch.'%']);
                });
            }

            // Total records setelah filter search
            $filteredRecords = (clone $query)->count();

            // Handle Sorting
            $orderColIdx = $request->input('order.0.column');
            $orderDir = $request->input('order.0.dir', 'desc');

            $orderColumn = null;
            if ($orderColIdx !== null) {
                $colName = $request->input("columns.{$orderColIdx}.name") ?: $request->input("columns.{$orderColIdx}.data");
                if ($colName === 'fcabang') {
                    $orderColumn = 'c.fcabangname';
                } elseif ($colName === 'fstockmtno') {
                    $orderColumn = 'trstockmt.fstockmtno';
                } elseif ($colName === 'fstockmtdate') {
                    $orderColumn = 'trstockmt.fstockmtdate';
                } elseif ($colName === 'fadjtype') {
                    $orderColumn = 'trstockmt.ftrancode';
                } elseif ($colName === 'fgudang') {
                    $orderColumn = 'w.fwhname';
                } elseif ($colName === 'fket') {
                    $orderColumn = 'trstockmt.fket';
                }
            }

            if ($orderColumn) {
                $query->orderBy($orderColumn, $orderDir);
            } else {
                $query->orderBy('trstockmt.fstockmtdate', 'desc');
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
                    'trstockmt.ftrancode',
                    'trstockmt.fket',
                    'trstockmt.fbranchcode',
                    'trstockmt.fapproval',
                    'c.fcabangname',
                    'w.fwhname',
                ]);

            // Format Data - HANYA RETURN DATA MENTAH
            $data = $records->map(function ($row) {
                return [
                    'fstockmtid' => $row->fstockmtid,
                    'fcabang' => $row->fbranchcode,
                    'fstockmtno' => $row->fstockmtno,
                    'fstockmtdate' => $row->fstockmtdate
                        ? ($row->fstockmtdate instanceof \Carbon\Carbon ? $row->fstockmtdate : \Carbon\Carbon::parse($row->fstockmtdate))->format('d-m-Y')
                        : '',
                    'fadjtype' => strtoupper(trim((string) ($row->ftrancode ?? ''))) === 'K' ? 'Keluar' : 'Masuk',
                    'fgudang' => trim((string) ($row->fwhname ?? '')),
                    'fket' => trim((string) ($row->fket ?? '')),
                    'fapproval' => trim((string) ($row->fapproval ?? '')),
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
        return view('adjstock.index', compact(
            'canCreate',
            'canEdit',
            'canDelete',
            'showActionsColumn',
            'availableYears',
            'year',
            'month',
            'createLimitReached'
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
            ])
            ->where('fapproval', 1);

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
        $header = Tr_poh::where('fpohid', $id)
            ->where('fapproval', 1)
            ->firstOrFail();

        // Mengambil detail dari tr_pod
        $items = DB::table('tr_pod')
          // Detail PO sekarang dihubungkan lewat fpono
            ->where('tr_pod.fpono', $header->fpono)

          // PERBAIKAN JOIN: tr_pod.fprdcode (sekarang integer) di-join ke msprd.fprdid (integer)
            ->leftJoin('msprd as m', 'm.fprdid', '=', 'tr_pod.fprdcode')
            ->select([
                DB::raw("COALESCE(NULLIF(tr_pod.frefdtno, ''), tr_pod.fpodid::text) as frefdtno"),
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

        $prefix = sprintf('PO.%s.%s%s.', $kodeCabang, $date->format('y'), $date->format('m'));

        // kunci per (branch, tahun-bulan) — TANPA bikin tabel baru
        $lockKey = crc32('PO|'.$kodeCabang.'|'.$date->format('Y-m'));
        DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

        $last = DB::table('tr_poh')
            ->where('fpono', 'like', $prefix.'%')
            ->selectRaw("MAX(CAST(split_part(fpono, '.', 4) AS int)) AS lastno")
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
            return redirect()->back()->with('error', 'Adjustment stock tidak ada.');
        }

        DB::table('trstockmt')->where('fstockmtno', $hdr->fstockmtno)->update(['fprint' => 1]);

        $dt = PenerimaanPembelianDetail::query()
            ->leftJoin('msprd as p', 'p.fprdcode', '=', 'trstockdt.fprdcode')
            ->where('trstockdt.fstockmtno', $fstockmtno)
            ->orderBy('trstockdt.fprdcode')
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

        return view('adjstock.print', [
            'hdr' => $hdr,
            'dt' => $dt,
            'fmt' => $fmt,
            'company_name' => config('app.company_name', 'PT. DEMO VERSION'),
            'company_city' => config('app.company_city', 'Tangerang'),
        ]);
    }

    public function create()
    {
        if ($this->hasReachedDailyCreateLimit()) {
            return redirect()
                ->route('adjstock.index')
                ->with('create_limit_exceeded', true);
        }

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
            ->when(is_numeric($raw), fn ($q) => $q->where('fcabangid', (int) $raw))
            ->when(
                ! is_numeric($raw),
                fn ($q) => $q->where('fcabangkode', $raw)->orWhere('fcabangname', $raw)
            )
            ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

        $fcabang = $branch->fcabangname ?? (string) $raw;
        $fbranchcode = $branch->fcabangkode ?? (string) $raw;

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
        )
            ->whereRaw("COALESCE(TRIM(CAST(fnonactive AS TEXT)), '0') != '1'")
            ->orderBy('fprdname')
            ->get();

        return view('adjstock.create', [
            'newtr_prh_code' => $newtr_prh_code,
            'warehouses' => $warehouses,
            'accounts' => $accounts,
            'supplier' => $supplier,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'products' => $products,
        ]);
    }

    public function downloadTemplate()
    {
        $path = public_path('Template Adjustment Stock.xlsx');

        if (! is_file($path)) {
            abort(404, 'Template Adjustment Stock.xlsx tidak ditemukan.');
        }

        return response()->download($path, 'Template Adjustment Stock.xlsx');
    }

    public function uploadExcel(Request $request)
    {
        $request->validate([
            'excel_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
            'ftrancode' => ['nullable', 'in:M,K'],
        ]);

        $transactionType = strtoupper((string) $request->input('ftrancode', 'K')) === 'M' ? 'M' : 'K';

        $sheets = Excel::toArray(new class implements ToArray {
            public function array(array $array) {}
        }, $request->file('excel_file'));
        $sheet = $sheets[0] ?? [];
        $rows = array_slice($sheet, 1);

        $parsedRows = [];
        $codes = [];
        foreach ($rows as $row) {
            $code = strtoupper(trim((string) ($row[0] ?? '')));
            $unit = strtoupper(trim((string) ($row[1] ?? '')));
            $qty = $this->parseExcelNumber($row[2] ?? 0);
            $price = $this->parseExcelNumber($row[3] ?? 0);

            if ($code === '' && $unit === '' && $qty == 0.0 && $price == 0.0) {
                continue;
            }

            if ($code !== '') {
                $codes[] = $code;
            }

            $parsedRows[] = compact('code', 'unit', 'qty', 'price');
        }

        if ($parsedRows === []) {
            return back()->withErrors(['excel_upload' => 'Upload excel failed. File tidak berisi data item.']);
        }

        $products = Product::query()
            ->whereIn(DB::raw('UPPER(TRIM(fprdcode))'), array_values(array_unique($codes)))
            ->get(['fprdcode', 'fprdname', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'])
            ->keyBy(fn ($product) => strtoupper(trim((string) $product->fprdcode)));

        $missingCodes = array_values(array_unique(array_filter($codes, fn ($code) => ! $products->has($code))));
        if ($missingCodes !== []) {
            return redirect()->route('adjstock.create')
                ->withInput($request->except('excel_file') + ['ftrancode' => $transactionType])
                ->with('adjstock_upload_missing_codes', $missingCodes);
        }

        $items = [];
        foreach ($parsedRows as $row) {
            $product = $products->get($row['code']);
            if (! $product) {
                continue;
            }

            $unit = $row['unit'] !== '' ? $row['unit'] : strtoupper(trim((string) ($product->fsatuankecil ?? '')));
            $items[] = [
                'fitemcode' => $row['code'],
                'fitemname' => trim((string) ($product->fprdname ?? '')),
                'fsatuan' => $unit,
                'fqty' => $row['qty'],
                'fprice' => $row['price'],
                'ftotal' => $row['qty'] * $row['price'],
            ];
        }

        return redirect()->route('adjstock.create')->withInput([
            'ftrancode' => $transactionType,
            'fitemcode' => array_column($items, 'fitemcode'),
            'fitemname' => array_column($items, 'fitemname'),
            'fsatuan' => array_column($items, 'fsatuan'),
            'fqty' => array_column($items, 'fqty'),
            'fprice' => array_column($items, 'fprice'),
            'ftotal' => array_column($items, 'ftotal'),
        ])->with('success', 'Upload excel berhasil.');
    }

    private function parseExcelNumber($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = str_replace(',', '.', trim((string) $value));
        $normalized = preg_replace('/[^0-9.\-]/', '', $normalized) ?: '0';

        return (float) $normalized;
    }

    public function store(Request $request)
    {
        if ($this->hasReachedDailyCreateLimit()) {
            return redirect()
                ->route('adjstock.index')
                ->with('create_limit_exceeded', true);
        }

        try {
            $allowNegativeStockQty = stock_boleh_minus();
            // =========================
            // TAHAP 1: VALIDASI INPUT
            // =========================
            $request->validate([
                'fstockmtno' => [
                    'nullable',
                    'string',
                    'max:100',
                    function ($attribute, $value, $fail) use ($request) {
                        if (! $request->boolean('auto_generate', true) && empty(trim((string) $value))) {
                            $fail('No. Transaksi Adjustment Stock wajib diisi jika Auto tidak dicentang.');
                        }
                    },
                ],
                'fstockmtdate' => ['required', 'date'],
                'ffrom' => ['required', 'string', 'max:10'],
                'ftrancode' => ['nullable', 'string', 'max:3'],
                'fket' => ['nullable', 'string', 'max:50'],
                'fbranchcode' => ['nullable', 'string', 'max:20'],
                'fitemcode' => ['required', 'array', 'min:1'],
                'fitemcode.*' => ['required', 'string', 'max:50'],
                'fsatuan' => ['nullable', 'array'],
                'fsatuan.*' => ['nullable', 'string', 'max:20'],
                'fprdjadi' => ['nullable', 'string', 'max:20'],
                'fqty' => ['required', 'array'],
                'fqty.*' => [
                    'required',
                    'numeric',
                    function ($attribute, $value, $fail) use ($allowNegativeStockQty) {
                        if ($allowNegativeStockQty ? (float) $value == 0.0 : (float) $value <= 0) {
                            $fail($allowNegativeStockQty ? 'Qty tidak boleh 0.' : 'Qty harus lebih dari 0.');
                        }
                    },
                ],
                'fprice' => ['required', 'array'],
                'fprice.*' => ['numeric', 'min:0'],
                'fdesc' => ['nullable', 'array'],
                'fdesc.*' => ['nullable', 'string', 'max:500'],
                'fcurrency' => ['nullable', 'string', 'max:5'],
                'frate' => ['nullable', 'numeric', 'min:0'],
                'famountpopajak' => ['nullable', 'numeric', 'min:0'],
            ], [
                'ffrom.required' => 'Gudang wajib di isi.',
            ]);

            $this->ensureNoDuplicateDetailCodes($request->input('fitemcode', []));

            // =========================
            // TAHAP 2: AMBIL DATA MASTER PRODUK
            // =========================
            $uniqueCodes = array_values(array_unique(
                array_filter(
                    array_map(fn ($c) => trim((string) $c), $request->input('fitemcode', []))
                )
            ));

            $prodMeta = collect();
            if (! empty($uniqueCodes)) {
                $prodMeta = DB::table('msprd')
                    ->whereIn('fprdcode', $uniqueCodes)
                    ->get(['fprdid', 'fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2', 'fqtykecil', 'fqtykecil2'])
                    ->keyBy('fprdcode');
            }

            // =========================
            // TAHAP 3: RAKIT DETAIL & HITUNG SUBTOTAL
            // =========================
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
            $usedNoAcaks = [];
            $subtotal = 0.0;
            $now = now();
            $frate = (float) $request->input('frate', 1);
            if ($frate <= 0) {
                $frate = 1;
            }

            $codes = $request->input('fitemcode', []);
            $satuans = $request->input('fsatuan', []);
            $refdtno = $request->input('frefdtno', []);
            $qtys = $request->input('fqty', []);
            $prices = $request->input('fprice', []);
            $descs = $request->input('fdesc', []);

            for ($i = 0; $i < count($codes); $i++) {
                $code = trim((string) ($codes[$i] ?? ''));
                $qty = (float) ($qtys[$i] ?? 0);

                if ($code === '' || ($allowNegativeStockQty ? abs($qty) < 0.000001 : $qty <= 0)) {
                    continue;
                }

                $meta = $prodMeta[$code] ?? null;
                if (! $meta) {
                    continue;
                }

                $sat = trim((string) ($satuans[$i] ?? '')) ?: $pickDefaultSat($meta);
                $sat = mb_substr($sat, 0, 5);
                if ($sat === '') {
                    continue;
                }

                $qtyKecil = $qty;
                if ($sat === trim((string) ($meta->fsatuanbesar ?? '')) && (float) ($meta->fqtykecil ?? 0) > 0) {
                    $qtyKecil = $qty * (float) $meta->fqtykecil;
                } elseif ($sat === trim((string) ($meta->fsatuanbesar2 ?? '')) && (float) ($meta->fqtykecil2 ?? 0) > 0) {
                    $qtyKecil = $qty * (float) $meta->fqtykecil2;
                }

                $price = (float) ($prices[$i] ?? 0);
                $amount = $qty * $price;
                $subtotal += $amount;

                $rowsDt[] = [
                    'fprdcode' => $meta->fprdcode,
                    'fnoacak' => $this->normalizeRandomNumber(null, $usedNoAcaks),
                    'frefdtno' => trim((string) ($refdtno[$i] ?? '')) ?: null,
                    'fqty' => $qty,
                    'fqtyremain' => $qtyKecil,
                    'fprice' => $price,
                    'fprice_rp' => $price * $frate,
                    'ftotprice' => $amount,
                    'ftotprice_rp' => $amount * $frate,
                    'fusercreate' => (Auth::user()->fname ?? 'system'),
                    'fdatetime' => $now,
                    'fketdt' => null,
                    'fcode' => '0',
                    'frefso' => null,
                    'fdesc' => ($descs[$i] ?? '') ?: null,
                    'fsatuan' => $sat,
                    'fqtykecil' => $qtyKecil,
                    'fclosedt' => '0',
                    'fdiscpersen' => 0,
                    'fbiaya' => 0,
                    'fstockmtcode' => null,
                    'fstockmtno' => null,
                ];
            }

            if (empty($rowsDt)) {
                $msg = $allowNegativeStockQty
                    ? 'Minimal 1 item valid harus diisi. Qty tidak boleh 0.'
                    : 'Minimal 1 item valid harus diisi.';
                if ($request->expectsJson()) {
                    return response()->json(['message' => $msg], 422);
                }
                return back()->withInput()->withErrors([
                    'detail' => $msg,
                ]);
            }

            if ($validationMessage = $this->validateUniqueReferenceUsage($rowsDt)) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => $validationMessage], 422);
                }
                return back()->withInput()->withErrors(['detail' => $validationMessage]);
            }

            if ($stockResponse = $this->validateStockMinusLines(
                $this->buildStockMinusLinesForSignedRows($rowsDt, (string) $request->input('ffrom')),
                $request->boolean('force_save')
            )) {
                return $stockResponse;
            }

            // =========================
            // TAHAP 4: PERSIAPAN DATA HEADER
            // =========================
            $fstockmtdate = \Carbon\Carbon::parse($request->fstockmtdate)->startOfDay();
            $this->ensureCreateDateWithinEditPeriod($fstockmtdate);
            $ppnAmount = (float) $request->input('famountpopajak', 0);
            $grandTotal = $subtotal + $ppnAmount;
            $userName = Auth::user()->fname ?? 'system';

            $headerData = [
                'fstockmtno' => strtoupper(trim((string) $request->input('fstockmtno'))),
                'fstockmtcode' => 'ADJ',
                'fstockmtdate' => $fstockmtdate,
                'fprdout' => '0',
                'fsupplier' => '0',
                'fcurrency' => $request->input('fcurrency', 'IDR'),
                'frate' => $frate,
                'famount' => round($subtotal, 2),
                'famount_rp' => round($subtotal * $frate, 2),
                'famountpajak' => round($ppnAmount, 2),
                'famountpajak_rp' => round($ppnAmount * $frate, 2),
                'famountmt' => round($grandTotal, 2),
                'famountmt_rp' => round($grandTotal * $frate, 2),
                'famountremain' => round($grandTotal, 2),
                'famountremain_rp' => round($grandTotal * $frate, 2),
                'ftrancode' => $request->input('ftrancode') ?: null,
                'ffrom' => $request->input('ffrom') ?: null,
                'fprdjadi' => $request->input('fprdjadi') ?: null,
                'fket' => trim((string) $request->input('fket', '')) ?: null,
                'fusercreate' => $userName,
                'fdatetime' => $now,
                'fapproval' => 1,
                'fuserapproved' => $userName,
                'fdateapproved' => $now,
                'fbranchcode' => $request->input('fbranchcode'),
                'fprint' => 0,
                'fsudahtagih' => '0',
                'fdiscount' => 0,
            ];

            // =========================
            // TAHAP 5: TRANSAKSI DATABASE
            // =========================
            $finalNo = DB::transaction(function () use ($headerData, &$rowsDt) {
                $fstockmtno = $headerData['fstockmtno'];

                if (empty($fstockmtno)) {
                    $needle = trim((string) $headerData['fbranchcode']);
                    $kodeCabang = null;

                    if ($needle !== '') {
                        if (is_numeric($needle)) {
                            $kodeCabang = DB::table('mscabang')->where('fcabangid', (int) $needle)->value('fcabangkode');
                        } else {
                            $kodeCabang = DB::table('mscabang')->whereRaw('LOWER(fcabangkode)=LOWER(?)', [$needle])->value('fcabangkode');
                            if (! $kodeCabang) {
                                $kodeCabang = DB::table('mscabang')->whereRaw('LOWER(fcabangname)=LOWER(?)', [$needle])->value('fcabangkode');
                            }
                        }
                    }
                    $kodeCabang = $kodeCabang ?: 'NA';

                    $prefix = sprintf('%s.%s.%s%s.', $headerData['fstockmtcode'], $kodeCabang, $headerData['fstockmtdate']->format('y'), $headerData['fstockmtdate']->format('m'));

                    $lockKey = crc32('STOCKMT|'.$headerData['fstockmtcode'].'|'.$kodeCabang.'|'.$headerData['fstockmtdate']->format('y-m'));
                    DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

                    $last = DB::table('trstockmt')
                        ->where('fstockmtno', 'like', $prefix.'%')
                        ->selectRaw("MAX(CAST(split_part(fstockmtno, '.', 4) AS int)) AS lastno")
                        ->value('lastno');

                    $fstockmtno = $prefix.str_pad((string) ((int) $last + 1), 4, '0', STR_PAD_LEFT);
                    $headerData['fbranchcode'] = $kodeCabang;
                    $headerData['fstockmtno'] = $fstockmtno;
                }

                $newId = DB::table('trstockmt')->insertGetId($headerData, 'fstockmtid');

                foreach ($rowsDt as &$r) {
                    $r['fstockmtcode'] = $headerData['fstockmtcode'];
                    $r['fstockmtno'] = $fstockmtno;
                }
                unset($r);

                DB::table('trstockdt')->insert($rowsDt);

                return $fstockmtno;
            });

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Adjustment stock {$finalNo} berhasil disimpan.",
                    'redirect_url' => route('adjstock.create'),
                    'success_prompt' => [
                        'type' => 'adjstock_create',
                        'redirect_url' => route('adjstock.print', $finalNo),
                    ],
                ]);
            }

            return redirect()
                ->route('adjstock.create')
                ->with('success', "Adjustment stock {$finalNo} berhasil disimpan.")
                ->with('success_prompt', [
                    'type' => 'adjstock_create',
                    'redirect_url' => route('adjstock.print', $finalNo),
                ]);
        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $firstError ?: 'Adjustment stock belum bisa disimpan. Cek data.',
                    'errors' => $e->errors(),
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            \Log::error('AdjstockController@store error: ' . $e->getMessage(), ['exception' => $e]);
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Adjustment stock belum bisa disimpan: ' . $e->getMessage()], 500);
            }
            return back()->withInput()->withErrors(['fatal' => 'Adjustment stock belum bisa disimpan: ' . $e->getMessage()]);
        }
    }

    public function edit($fstockmtid)
    {
        $supplier = Supplier::all();

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

        // 1. Ambil data Header (trstockmt) DAN relasi Details (trstockdt)
        // Biarkan query ini. Sekarang $fstockmtid di sini adalah integer (misal: 8)
        $adjstock = PenerimaanPembelianHeader::with([
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
            ->findOrFail($fstockmtid); // Temukan header berdasarkan $fstockmtid dari URL

        if ($message = $this->getPostedPeriodLockMessage($adjstock->fstockmtdate, 'Adjustment Stock ini')) {
            return redirect()
                ->route('adjstock.view', $adjstock->fstockmtid)
                ->with('error', $message);
        }
        ['fcabang' => $fcabang, 'fbranchcode' => $fbranchcode] = $this->resolveBranchContext($adjstock->fbranchcode ?? null);

        $usageLockMessage = $this->getUsageLockMessage($adjstock);

        if (! empty($usageLockMessage)) {
            return redirect()
                ->route('adjstock.view', $adjstock->fstockmtid)
                ->with('error', $usageLockMessage);
        }

        // 4. Map the data for savedItems (sudah menggunakan data yang benar)
        $savedItems = $adjstock->details->map(function ($d) {
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
                'fketdt' => $d->fketdt ?? '',
                'units' => [],
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
                    'name' => $p->fprdname,
                    'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
                    'stock' => $p->fminstock ?? 0,
                ],
            ];
        })->toArray();

        return view('adjstock.edit', [
            'supplier' => $supplier,
            'selectedSupplierCode' => $selectedSupplierCode,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'warehouses' => $warehouses,
            'accounts' => $accounts,
            'products' => $products,
            'productMap' => $productMap,
            'adjstock' => $adjstock,
            'savedItems' => $savedItems,
            'ppnAmount' => (float) ($adjstock->famountpopajak ?? 0),
            'famountponet' => (float) ($adjstock->famountponet ?? 0),
            'famountpo' => (float) ($adjstock->famountpo ?? 0),
            'isUsageLocked' => ! empty($usageLockMessage),
            'usageLockMessage' => $usageLockMessage,
            'action' => 'edit',
        ]);
    }

    public function view($fstockmtid)
    {
        $supplier = Supplier::all();

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

        // 1. Ambil data Header (trstockmt) DAN relasi Details (trstockdt)
        // Biarkan query ini. Sekarang $fstockmtid di sini adalah integer (misal: 8)
        $adjstock = PenerimaanPembelianHeader::with([
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
            ->findOrFail($fstockmtid); // Temukan header berdasarkan $fstockmtid dari URL
        ['fcabang' => $fcabang, 'fbranchcode' => $fbranchcode] = $this->resolveBranchContext($adjstock->fbranchcode ?? null);

        // 4. Map the data for savedItems (sudah menggunakan data yang benar)
        $savedItems = $adjstock->details->map(function ($d) {
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
                'fketdt' => $d->fketdt ?? '',
                'units' => [],
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
                    'name' => $p->fprdname,
                    'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
                    'stock' => $p->fminstock ?? 0,
                ],
            ];
        })->toArray();

        return view('adjstock.edit', [
            'supplier' => $supplier,
            'selectedSupplierCode' => $selectedSupplierCode,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'warehouses' => $warehouses,
            'accounts' => $accounts,
            'products' => $products,
            'productMap' => $productMap,
            'adjstock' => $adjstock,
            'savedItems' => $savedItems,
            'ppnAmount' => (float) ($adjstock->famountpopajak ?? 0),
            'famountponet' => (float) ($adjstock->famountponet ?? 0),
            'famountpo' => (float) ($adjstock->famountpo ?? 0),
        'isUsageLocked' => false,
            'usageLockMessage' => null,
            'action' => 'view',
        ]);
    }

    public function update(Request $request, $fstockmtid)
    {
        try {
            $allowNegativeStockQty = stock_boleh_minus();
            // =========================
            // 1) VALIDASI INPUT
            // =========================
            $validated = $request->validate([
                'fstockmtno' => ['nullable', 'string', 'max:100'],
                'fstockmtdate' => ['required', 'date'],
                'ffrom' => ['required', 'string', 'max:10'],
                'ftrancode' => ['nullable', 'string', 'max:3'],
                'fket' => ['nullable', 'string', 'max:50'],
                'fbranchcode' => ['nullable', 'string', 'max:20'],
                'fitemcode' => ['required', 'array', 'min:1'],
                'fitemcode.*' => ['required', 'string', 'max:50'],
                'fsatuan' => ['nullable', 'array'],
                'fsatuan.*' => ['nullable', 'string', 'max:20'],
                'fprdjadi' => ['nullable', 'string'],
                'fqty' => ['required', 'array'],
                'fqty.*' => [
                    'required',
                    'numeric',
                    function ($attribute, $value, $fail) use ($allowNegativeStockQty) {
                        if ($allowNegativeStockQty ? (float) $value == 0.0 : (float) $value <= 0) {
                            $fail($allowNegativeStockQty ? 'Qty tidak boleh 0.' : 'Qty harus lebih dari 0.');
                        }
                    },
                ],
                'fprice' => ['required', 'array'],
                'fprice.*' => ['numeric', 'min:0'],
                'fdesc' => ['nullable', 'array'],
                'fdesc.*' => ['nullable', 'string', 'max:500'],
                'fcurrency' => ['nullable', 'string', 'max:5'],
                'frate' => ['nullable', 'numeric', 'min:0'],
                'famountpopajak' => ['nullable', 'numeric', 'min:0'],
            ], [
                'ffrom.required' => 'Gudang wajib di isi.',
            ]);
            $this->ensureNoDuplicateDetailCodes($request->input('fitemcode', []));

            // =========================
            // 2) AMBIL DATA MASTER & HEADER
            // =========================
            $header = PenerimaanPembelianHeader::findOrFail($fstockmtid);
            if ($message = $this->getPostedPeriodLockMessage($header->fstockmtdate, 'Adjustment Stock ini')) {
                return redirect()->route('adjstock.edit', $header->fstockmtid)->with('error', $message);
            }
            if ($message = $this->getUsageLockMessage($header)) {
                return redirect()->route('adjstock.index')->with('error', $message);
            }

            $userLogin = auth('sysuser')->user() ?? auth()->user();
            $userName = Auth::user()->fname ?? $userLogin->fname ?? 'system';
            $userIdLog = $userLogin->fuserid ?? $userLogin->fsysuserid ?? 'admin';

            $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();

            // Note: keep exact inner logic intact
            $fcurrency = $request->input('fcurrency', 'IDR') ?: 'IDR';
            $frate = (float) $request->input('frate', 1);
            if ($frate <= 0) {
                $frate = 1.0;
            }
            $ffrom = $request->input('ffrom') ?: null;
            $ftrancode = $request->input('ftrancode') ?: null;
            $fprdjadi = $request->input('fprdjadi') ?: null;
            $fket = $request->input('fket') ?: null;
            $isApproved = true;

            $items = $request->input('fitemcode', []);
            $satuans = $request->input('fsatuan', []);
            $qtys = $request->input('fqty', []);
            $prices = $request->input('fprice', []);
            $descs = $request->input('fdesc', []);

            $rowsDt = [];
            $subtotal = 0;
            $ppnAmount = (float) $request->input('famountpopajak', 0);
            $now = now();

            foreach ($items as $idx => $code) {
                $code = trim((string) $code);
                if ($code === '') {
                    continue;
                }

                $sat = isset($satuans[$idx]) ? trim((string) $satuans[$idx]) : '';
                $qtyVal = isset($qtys[$idx]) ? (float) $qtys[$idx] : 0;
                $price = isset($prices[$idx]) ? (float) $prices[$idx] : 0;
                $desc = isset($descs[$idx]) ? trim((string) $descs[$idx]) : '';

                if ($allowNegativeStockQty ? $qtyVal == 0.0 : $qtyVal <= 0) {
                    continue;
                }

                $product = Product::where('fprdcode', $code)->first();
                if (! $product) {
                    continue;
                }

                $qtyKecil = $this->calculateQtyKecil($product, $sat, $qtyVal);
                $amount = round($qtyVal * $price, 2);
                $subtotal += $amount;

                $rowsDt[] = [
                    'fprdcode' => $code,
                    'fqty' => $qtyVal,
                    'fqtyremain' => $qtyVal,
                    'fprice' => $price,
                    'fprice_rp' => $price * $frate,
                    'ftotprice' => $amount,
                    'ftotprice_rp' => $amount * $frate,
                    'fuserupdate' => $userName,
                    'fdatetime' => $now,
                    'fketdt' => '',
                    'fcode' => '0',
                    'frefso' => null,
                    'fdesc' => $desc,
                    'fsatuan' => $sat,
                    'fqtykecil' => $qtyKecil,
                    'fclosedt' => '0',
                    'fdiscpersen' => 0,
                    'fbiaya' => 0,
                    'fstockmtcode' => null,
                    'fstockmtno' => null,
                ];
            }

            if (empty($rowsDt)) {
                return back()->withInput()->withErrors([
                    'detail' => $allowNegativeStockQty
                        ? 'Minimal 1 item valid (kode, satuan, qty tidak boleh 0).'
                        : 'Minimal 1 item valid (kode, satuan, qty > 0).',
                ]);
            }

            if ($validationMessage = $this->validateUniqueReferenceUsage($rowsDt, $header->fstockmtno)) {
                return back()->withInput()->withErrors([
                    'detail' => $validationMessage,
                ]);
            }

            if ($stockResponse = $this->validateStockMinusLines(
                $this->buildStockMinusLinesForSignedRows($rowsDt, (string) $ffrom, $this->fetchStockDetailRows((string) $header->fstockmtno), (string) $header->ffrom),
                $request->boolean('force_save')
            )) {
                return $stockResponse;
            }

            $grandTotal = $subtotal + $ppnAmount;

            // =========================
            // 5) TRANSAKSI DB
            // =========================
            DB::transaction(function () use (
                $header,
                $fstockmtdate,
                $ffrom,
                $fprdjadi,
                $ftrancode,
                $fket,
                $fcurrency,
                $frate,
                $subtotal,
                $ppnAmount,
                $grandTotal,
                $userName,
                $isApproved,
                $now,
                $rowsDt,
                $userIdLog
            ) {
                $kodeCabang = trim((string) $header->fbranchcode);
                if (empty($kodeCabang)) {
                    $firstWh = DB::table('mswh')->select('fbranchcode')->first();
                    if ($firstWh && ! empty($firstWh->fbranchcode)) {
                        $kodeCabang = $firstWh->fbranchcode;
                    } else {
                        $kodeCabang = 'NA';
                    }
                }

                // ---- 5.2. UPDATE HEADER: trstockmt ----
                $masterData = [
                    'fstockmtdate' => $fstockmtdate,
                    'fcurrency' => $fcurrency,
                    'frate' => $frate,
                    'famount' => round($subtotal, 2),
                    'famount_rp' => round($subtotal * $frate, 2),
                    'famountpajak' => round($ppnAmount, 2),
                    'famountpajak_rp' => round($ppnAmount * $frate, 2),
                    'famountmt' => round($grandTotal, 2),
                    'famountmt_rp' => round($grandTotal * $frate, 2),
                    'famountremain' => round($grandTotal, 2),
                    'famountremain_rp' => round($grandTotal * $frate, 2),
                    'ffrom' => $ffrom,
                    'ftrancode' => $ftrancode,
                    'fprdjadi' => $fprdjadi,
                    'fket' => $fket,
                    'fuserupdate' => $userName,
                    'fapproval' => 1,
                    'fuserapproved' => $header->fuserapproved ?: $userName,
                    'fdateapproved' => $header->fdateapproved ?: $now,
                    'fbranchcode' => $kodeCabang,
                ];

                $header->update($masterData);

                $updatedHeader = PenerimaanPembelianHeader::findOrFail($header->fstockmtid);

                $logSeqCount = DB::table('log_trstockmt')
                    ->where('fstockmtno', $updatedHeader->fstockmtno)
                    ->count();
                $logSeq = str_pad((string) ($logSeqCount + 1), 4, '0', STR_PAD_LEFT);
                $trxLogId = 'LOG' . $updatedHeader->fstockmtno . $logSeq;

                // 1. INSERT Log Header (Update)
                DB::table('log_trstockmt')->insert([
                    'ftrxlogid'        => $trxLogId,
                    'fstockmtid'       => $updatedHeader->fstockmtid,
                    'fstockmtno'       => $updatedHeader->fstockmtno,
                    'fbranchcode'      => $updatedHeader->fbranchcode,
                    'fstockmtcode'     => $updatedHeader->fstockmtcode,
                    'fstockmtdate'     => $updatedHeader->fstockmtdate,
                    'fprdout'          => $updatedHeader->fprdout,
                    'fsupplier'        => $updatedHeader->fsupplier,
                    'fcurrency'        => $updatedHeader->fcurrency,
                    'frate'            => $updatedHeader->frate,
                    'ftypebuy'         => $updatedHeader->ftypebuy,
                    'ftempohr'         => $updatedHeader->ftempohr,
                    'ftrancode'        => $updatedHeader->ftrancode,
                    'fsalesman'        => $updatedHeader->fsalesman,
                    'fjatuhtempo'      => $updatedHeader->fjatuhtempo,
                    'fprint'           => $updatedHeader->fprint,
                    'fsudahtagih'      => $updatedHeader->fsudahtagih,
                    'famount'          => $updatedHeader->famount,
                    'famountpajak'     => $updatedHeader->famountpajak,
                    'famountmt'        => $updatedHeader->famountmt,
                    'famountremain'    => $updatedHeader->famountremain,
                    'fket'             => $updatedHeader->fket,
                    'frefno'           => $updatedHeader->frefno,
                    'fusercreate'      => $updatedHeader->fusercreate,
                    'fdatetime'        => $updatedHeader->fdatetime,
                    'fuserupdate'      => $updatedHeader->fuserupdate,
                    'fupdatedat'       => $updatedHeader->fupdatedat,
                    'ffrom'            => $updatedHeader->ffrom,
                    'fto'              => $updatedHeader->fto,
                    'fprdjadi'         => $updatedHeader->fprdjadi,
                    'famountpajak_rp'  => $updatedHeader->famountpajak_rp,
                    'famountmt_rp'     => $updatedHeader->famountmt_rp,
                    'famountremain_rp' => $updatedHeader->famountremain_rp,
                    'feditmode'        => 'U',
                    'fuseridlog'       => $userIdLog,
                    'fdatetimelog'     => $now,
                ]);

                // ---- 5.3. RE-INSERT DETAIL: trstockdt ----
                DB::table('trstockdt')->where('fstockmtno', $header->fstockmtno)->delete();

                foreach ($rowsDt as &$r) {
                    $r['fstockmtcode'] = 'ADJ';
                    $r['fstockmtno'] = $header->fstockmtno;
                    $r['fusercreate'] = $userName;
                    $r['fuserupdate'] = $userName;
                    $r['fdatetime'] = $now;

                    // Insert detail baru ke DB
                    DB::table('trstockdt')->insert($r);

                    // Ambil row detail yang baru di-insert untuk dijadikan data Log
                    $dtObj = DB::table('trstockdt')
                        ->where('fstockmtno', $header->fstockmtno)
                        ->where('fprdcode', $r['fprdcode'])
                        ->first();

                    if (! $dtObj) {
                        continue;
                    }

                    // 2. INSERT Log Detail (Update)
                    DB::table('log_trstockdt')->insert([
                        'ftrxlogid'     => $trxLogId,
                        'fstockdtid'    => $dtObj->fstockdtid,
                        'fstockmtno'    => $dtObj->fstockmtno,
                        'fprdcode'      => $dtObj->fprdcode,
                        'frefdtno'      => $dtObj->frefdtno,
                        'fqty'          => $dtObj->fqty,
                        'fqtyremain'    => $dtObj->fqtyremain,
                        'fsatuan'       => $dtObj->fsatuan,
                        'fqtykecil'     => $dtObj->fqtykecil,
                        'fprice'        => $dtObj->fprice,
                        'fprice_rp'     => $dtObj->fprice_rp,
                        'ftotprice'     => $dtObj->ftotprice,
                        'ftotprice_rp'  => $dtObj->ftotprice_rp,
                        'fketdt'        => $dtObj->fketdt,
                        'fcode'         => $dtObj->fcode,
                        'frefso'        => $dtObj->frefso,
                        'fdesc'         => $dtObj->fdesc,
                        'fclosedt'      => $dtObj->fclosedt,
                        'fdiscpersen'   => $dtObj->fdiscpersen,
                        'fbiaya'        => $dtObj->fbiaya,
                        'fpricenet'     => $dtObj->fpricenet,
                        'fnoacak'       => $dtObj->fnoacak,
                        'frefnoacak'    => $dtObj->frefnoacak,
                        'frefnoacak_so' => $dtObj->frefnoacak_so,
                        'fusercreate'   => $dtObj->fusercreate,
                        'fdatetime'     => $dtObj->fdatetime,
                        'fupdatedat'    => $dtObj->fupdatedat,
                        'fuserupdate'   => $dtObj->fuserupdate,
                        'feditmode'     => 'U',
                        'fuseridlog'    => $userIdLog,
                        'fdatetimelog'  => $now,
                    ]);
                }
                unset($r);
            });

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Adjustment stock {$header->fstockmtno} berhasil diupdate.",
                    'redirect_url' => route('adjstock.index'),
                ]);
            }

            return redirect()
                ->route('adjstock.index')
                ->with('success', "Adjustment stock {$header->fstockmtno} berhasil diupdate.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();

            if ($request->expectsJson()) {
                return response()->json(['message' => $firstError ?: 'Gagal update adjustment stock.'], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('error', $firstError ?: 'Gagal mengupdate adjustment stock. Cek data.');
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Gagal mengupdate adjustment stock: ' . $e->getMessage()], 500);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal mengupdate adjustment stock: ' . $e->getMessage());
        }
    }

    public function delete($fstockmtid)
    {
        $supplier = Supplier::all();

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

        // 1. Ambil data Header (trstockmt) DAN relasi Details (trstockdt)
        // Biarkan query ini. Sekarang $fstockmtid di sini adalah integer (misal: 8)
        $adjstock = PenerimaanPembelianHeader::with([
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
            ->findOrFail($fstockmtid); // Temukan header berdasarkan $fstockmtid dari URL

        if ($message = $this->getPostedPeriodLockMessage($adjstock->fstockmtdate, 'Adjustment Stock ini')) {
            return redirect()
                ->route('adjstock.edit', $adjstock->fstockmtid)
                ->with('error', $message);
        }
        ['fcabang' => $fcabang, 'fbranchcode' => $fbranchcode] = $this->resolveBranchContext($adjstock->fbranchcode ?? null);

        $usageLockMessage = $this->getUsageLockMessage($adjstock);

        if (! empty($usageLockMessage)) {
            return redirect()
                ->route('adjstock.edit', $adjstock->fstockmtid)
                ->with('error', $usageLockMessage);
        }

        // 4. Map the data for savedItems (sudah menggunakan data yang benar)
        $savedItems = $adjstock->details->map(function ($d) {
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
                'fketdt' => $d->fketdt ?? '',
                'units' => [],
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
                    'name' => $p->fprdname,
                    'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
                    'stock' => $p->fminstock ?? 0,
                ],
            ];
        })->toArray();

        return view('adjstock.edit', [
            'supplier' => $supplier,
            'selectedSupplierCode' => $selectedSupplierCode,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'warehouses' => $warehouses,
            'accounts' => $accounts,
            'products' => $products,
            'productMap' => $productMap,
            'adjstock' => $adjstock,
            'savedItems' => $savedItems,
            'ppnAmount' => (float) ($adjstock->famountpopajak ?? 0),
            'famountponet' => (float) ($adjstock->famountponet ?? 0),
            'famountpo' => (float) ($adjstock->famountpo ?? 0),
            'isUsageLocked' => ! empty($usageLockMessage),
            'usageLockMessage' => $usageLockMessage,
            'action' => 'delete',
        ]);
    }

   public function destroy($fstockmtid)
    {
        try {
            $adjstock = PenerimaanPembelianHeader::findOrFail($fstockmtid);
            if ($message = $this->getPostedPeriodLockMessage($adjstock->fstockmtdate, 'Adjustment Stock ini')) {
                return redirect()->route('adjstock.edit', $adjstock->fstockmtid)->with('error', $message);
            }
            if ($message = $this->getUsageLockMessage($adjstock)) {
                return redirect()->route('adjstock.index')->with('error', $message);
            }

            $userLogin = auth('sysuser')->user() ?? auth()->user();
            $userIdLog = $userLogin->fuserid ?? $userLogin->fsysuserid ?? 'admin';

            DB::transaction(function () use ($adjstock, $userIdLog) {
                $now = now();

                $logSeqCount = DB::table('log_trstockmt')
                    ->where('fstockmtno', $adjstock->fstockmtno)
                    ->count();
                $logSeq = str_pad((string) ($logSeqCount + 1), 4, '0', STR_PAD_LEFT);
                $trxLogId = 'LOG' . $adjstock->fstockmtno . $logSeq;

                // 1. INSERT Log Header (Delete)
                DB::table('log_trstockmt')->insert([
                    'ftrxlogid'        => $trxLogId,
                    'fstockmtid'       => $adjstock->fstockmtid,
                    'fstockmtno'       => $adjstock->fstockmtno,
                    'fbranchcode'      => $adjstock->fbranchcode,
                    'fstockmtcode'     => $adjstock->fstockmtcode,
                    'fstockmtdate'     => $adjstock->fstockmtdate,
                    'fprdout'          => $adjstock->fprdout,
                    'fsupplier'        => $adjstock->fsupplier,
                    'fcurrency'        => $adjstock->fcurrency,
                    'frate'            => $adjstock->frate,
                    'ftypebuy'         => $adjstock->ftypebuy,
                    'ftempohr'         => $adjstock->ftempohr,
                    'ftrancode'        => $adjstock->ftrancode,
                    'fsalesman'        => $adjstock->fsalesman,
                    'fjatuhtempo'      => $adjstock->fjatuhtempo,
                    'fprint'           => $adjstock->fprint,
                    'fsudahtagih'      => $adjstock->fsudahtagih,
                    'fdiscount'        => $adjstock->fdiscount,
                    'fupdatedat'       => $adjstock->fupdatedat,
                    'famount'          => $adjstock->famount,
                    'famount_rp'       => $adjstock->famount_rp,
                    'famountpajak'     => $adjstock->famountpajak,
                    'famountpajak_rp'  => $adjstock->famountpajak_rp,
                    'famountmt'        => $adjstock->famountmt,
                    'famountmt_rp'     => $adjstock->famountmt_rp,
                    'famountremain'    => $adjstock->famountremain,
                    'famountremain_rp' => $adjstock->famountremain_rp,
                    'frefno'           => $adjstock->frefno,
                    'frefpo'           => $adjstock->frefpo,
                    'ffrom'            => $adjstock->ffrom,
                    'fto'              => $adjstock->fto,
                    'fkirim'           => $adjstock->fkirim,
                    'fprdjadi'         => $adjstock->fprdjadi,
                    'fqtyjadi'         => $adjstock->fqtyjadi,
                    'fket'             => $adjstock->fket,
                    'fincludeppn'      => $adjstock->fincludeppn,
                    'fppnpersen'       => $adjstock->fppnpersen,
                    'fapplyppn'        => $adjstock->fapplyppn,
                    'fketinternal'     => $adjstock->fketinternal,
                    'fusercreate'      => $adjstock->fusercreate,
                    'fdatetime'        => $adjstock->fdatetime,
                    'fuserupdate'      => $adjstock->fuserupdate,
                    'feditmode'        => 'D',
                    'fuseridlog'       => $userIdLog,
                    'fdatetimelog'     => $now,
                ]);

                // 2. Ambil seluruh detail lalu catat ke log_trstockdt (Delete)
                $details = DB::table('trstockdt')->where('fstockmtno', $adjstock->fstockmtno)->get();
                foreach ($details as $detail) {
                    DB::table('log_trstockdt')->insert([
                        'ftrxlogid'     => $trxLogId,
                        'fstockdtid'    => $detail->fstockdtid,
                        'fstockmtcode'  => $detail->fstockmtcode,
                        'fstockmtno'    => $detail->fstockmtno,
                        'fprdcode'      => $detail->fprdcode,
                        'frefdtno'      => $detail->frefdtno,
                        'fqty'          => $detail->fqty,
                        'fqtyremain'    => $detail->fqtyremain,
                        'fsatuan'       => $detail->fsatuan,
                        'fqtykecil'     => $detail->fqtykecil,
                        'fprice'        => $detail->fprice,
                        'fprice_rp'     => $detail->fprice_rp,
                        'ftotprice'     => $detail->ftotprice,
                        'ftotprice_rp'  => $detail->ftotprice_rp,
                        'fketdt'        => $detail->fketdt,
                        'fcode'         => $detail->fcode,
                        'frefso'        => $detail->frefso,
                        'fdesc'         => $detail->fdesc,
                        'fclosedt'      => $detail->fclosedt,
                        'fdiscpersen'   => $detail->fdiscpersen,
                        'fbiaya'        => $detail->fbiaya,
                        'fpricenet'     => $detail->fpricenet,
                        'fnoacak'       => $detail->fnoacak,
                        'frefnoacak'    => $detail->frefnoacak,
                        'frefnoacak_so' => $detail->frefnoacak_so,
                        'fusercreate'   => $detail->fusercreate,
                        'fdatetime'     => $detail->fdatetime,
                        'fupdatedat'    => $detail->fupdatedat,
                        'fuserupdate'   => $detail->fuserupdate,
                        'feditmode'     => 'D',
                        'fuseridlog'    => $userIdLog,
                        'fdatetimelog'  => $now,
                    ]);
                }

                // Hapus detail & header utama
                DB::table('trstockdt')
                    ->where('fstockmtno', $adjstock->fstockmtno)
                    ->delete();

                $adjstock->delete();
            });

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Adjustment stock '.$adjstock->fstockmtno.' berhasil dihapus.',
                    'redirect_url' => route('adjstock.index'),
                ]);
            }

            return redirect()->route('adjstock.index')->with('success', 'Adjustment stock '.$adjstock->fstockmtno.' berhasil dihapus.');
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Adjustment stock belum bisa dihapus. Coba lagi: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->route('adjstock.delete', $fstockmtid)->with('error', 'Adjustment stock belum bisa dihapus. Coba lagi.');
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

        return 'Adjustment stock ' . $header->fstockmtno . ' sudah dipakai: ' . $usedBy->implode(', ') . '.';
    }

    private function validateUniqueReferenceUsage(array $rowsDt, ?string $exceptStockMtNo = null): ?string
    {
        $referenceNos = collect($rowsDt)
            ->pluck('frefdtno')
            ->map(fn ($value) => trim((string) ($value ?? '')))
            ->filter(fn ($value) => $value !== '' && $value !== '0')
            ->unique()
            ->values()
            ->all();

        if (empty($referenceNos)) {
            return null;
        }

        foreach ($referenceNos as $referenceNo) {
            $query = DB::table('trstockdt as d')
                ->join('trstockmt as h', 'h.fstockmtno', '=', 'd.fstockmtno')
                ->where('h.fstockmtcode', 'ADJ')
                ->whereRaw('TRIM(COALESCE(d.frefdtno, \'\')) = ?', [$referenceNo]);

            if (! empty($exceptStockMtNo)) {
                $query->where('h.fstockmtno', '<>', $exceptStockMtNo);
            }

            $existing = $query
                ->orderBy('h.fstockmtno')
                ->select('h.fstockmtno as transaction_no')
                ->first();

            if ($existing) {
                return 'No. referensi ' . $referenceNo . ' sudah ada di transaksi ' . trim((string) ($existing->transaction_no ?? '')) . '.';
            }
        }

        return null;
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

    private function calculateQtyKecil($product, string $sat, float $qty): float
    {
        if (! $product) {
            return $qty;
        }

        $sat = trim($sat);
        $largeUnit = trim((string) ($product->fsatuanbesar ?? ''));
        $largeUnit2 = trim((string) ($product->fsatuanbesar2 ?? ''));
        $qtyKecilRatio1 = (float) ($product->fqtykecil ?? 0);
        $qtyKecilRatio2 = (float) ($product->fqtykecil2 ?? 0);

        if ($sat !== '' && $sat === $largeUnit && $qtyKecilRatio1 > 0) {
            return $qty * $qtyKecilRatio1;
        }

        if ($sat !== '' && $sat === $largeUnit2 && $qtyKecilRatio2 > 0) {
            return $qty * $qtyKecilRatio2;
        }

        return $qty;
    }
}
