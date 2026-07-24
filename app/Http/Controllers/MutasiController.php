<?php

namespace App\Http\Controllers;

use App\Models\PenerimaanPembelianDetail;
use App\Models\PenerimaanPembelianHeader;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Tr_pod;
use App\Models\Tr_poh;
use Carbon\Carbon;
// <-- TAMBAHKAN INI
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // sekalian biar aman untuk tanggal
use Illuminate\Validation\ValidationException;

class MutasiController extends Controller
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
            $messages["fitemcode.$index"] = "Kode produk {$code} tidak boleh sama dalam satu Mutasi.";
        }

        throw ValidationException::withMessages($messages);
    }

    public function index(Request $request)
    {
        // --- 1. PERBAIKAN PERMISSIONS ---
        // Saya asumsikan ini nama permission yang benar untuk modul ini
        $canCreate = in_array('createPenerimaanBarang', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updatePenerimaanBarang', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deletePenerimaanBarang', explode(',', session('user_restricted_permissions', '')));
        $showActionsColumn = $canEdit || $canDelete; // Anda bisa tambahkan $canPrint jika ada
        $year = trim((string) $request->query('year', ''));
        $month = trim((string) $request->query('month', ''));
        $availableYearsQuery = DB::table('trstockmt')
            ->where('fstockmtcode', 'MUT')
            ->whereNotNull('fstockmtdate')
            ->selectRaw('DISTINCT EXTRACT(YEAR FROM fstockmtdate) as year');
        $this->applyBranchVisibilityScope($availableYearsQuery, 'trstockmt.fbranchcode');
        $availableYears = $availableYearsQuery
            ->orderByRaw('EXTRACT(YEAR FROM fstockmtdate) DESC')
            ->pluck('year');

        // --- 2. Handle Request AJAX dari DataTables ---
        if ($request->ajax()) {

            // Query dasar HANYA untuk 'MUT' (Receiving)
            $query = PenerimaanPembelianHeader::query()
                ->leftJoin('mscabang as c', 'c.fcabangkode', '=', 'trstockmt.fbranchcode')
                ->leftJoin('mswh as wf', 'wf.fwhcode', '=', 'trstockmt.ffrom')
                ->leftJoin('mswh as wt', 'wt.fwhcode', '=', 'trstockmt.fto')
                ->where('trstockmt.fstockmtcode', 'MUT');
            $this->applyBranchVisibilityScope($query, 'trstockmt.fbranchcode');

            // Total records (dengan filter 'MUT')
            $totalRecords = (clone $query)->count();

            // Handle Search
            if ($search = trim((string) $request->input('search.value'))) {
                $query->where(function ($q) use ($search) {
                    $q->where('trstockmt.fstockmtno', 'ilike', "%{$search}%")
                        ->orWhere('trstockmt.fket', 'ilike', "%{$search}%");
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

            $fromWarehouseSearch = trim((string) ($columnSearches->get('fgudang_dari', '')));
            if ($fromWarehouseSearch !== '') {
                $query->where(function ($warehouseQuery) use ($fromWarehouseSearch) {
                    $warehouseQuery
                        ->whereRaw('LOWER(TRIM(COALESCE(wf.fwhname, \'\'))) LIKE LOWER(?)', ['%' . $fromWarehouseSearch . '%'])
                        ->orWhereRaw('LOWER(TRIM(COALESCE(trstockmt.ffrom, \'\'))) LIKE LOWER(?)', ['%' . $fromWarehouseSearch . '%']);
                });
            }

            $toWarehouseSearch = trim((string) ($columnSearches->get('fgudang_ke', '')));
            if ($toWarehouseSearch !== '') {
                $query->where(function ($warehouseQuery) use ($toWarehouseSearch) {
                    $warehouseQuery
                        ->whereRaw('LOWER(TRIM(COALESCE(wt.fwhname, \'\'))) LIKE LOWER(?)', ['%' . $toWarehouseSearch . '%'])
                        ->orWhereRaw('LOWER(TRIM(COALESCE(trstockmt.fto, \'\'))) LIKE LOWER(?)', ['%' . $toWarehouseSearch . '%']);
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
                if ($colName === 'fbranchcode') {
                    $orderColumn = 'c.fcabangname';
                } elseif ($colName === 'fstockmtno') {
                    $orderColumn = 'trstockmt.fstockmtno';
                } elseif ($colName === 'fstockmtdate') {
                    $orderColumn = 'trstockmt.fstockmtdate';
                } elseif ($colName === 'fgudang_dari') {
                    $orderColumn = 'wf.fwhname';
                } elseif ($colName === 'fgudang_ke') {
                    $orderColumn = 'wt.fwhname';
                } elseif ($colName === 'fket') {
                    $orderColumn = 'trstockmt.fket';
                } elseif ($colName === 'fusercreate') {
                    $orderColumn = 'trstockmt.fusercreate';
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
                    'trstockmt.fket',
                    'trstockmt.fbranchcode',
                    'trstockmt.fusercreate',
                    'c.fcabangname',
                    'trstockmt.ffrom',
                    'trstockmt.fto',
                    'wf.fwhname as from_warehouse_name',
                    'wt.fwhname as to_warehouse_name',
                ]);

            // Format Data (Tombol dibuat di sini)
            $data = $records->map(function ($row) {

                $actions = '';

                // --- Tombol view ---
                // if ($canView) {
                // Asumsi route edit Anda: mutasi.edit
                $viewUrl = route('mutasi.view', $row->fstockmtid);
                $actions .= ' <a href="' . $viewUrl . '" class="inline-flex items-center bg-slate-500 text-white px-4 py-2 rounded hover:bg-slate-600">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg> View
                                </a>';
                // }

                // --- Tombol Edit ---
                // if ($canEdit) {
                // Asumsi route edit Anda: mutasi.edit
                $editUrl = route('mutasi.edit', $row->fstockmtid);
                $actions .= ' <a href="' . $editUrl . '" class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg> Edit
                                </a>';
                // }

                // --- Tombol Delete ---
                // if ($canDelete) {
                // Asumsi route destroy Anda: mutasi.destroy
                $deleteUrl = route('mutasi.delete', $row->fstockmtid);
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
                    'fbranchcode' =>$row->fbranchcode,
                    'fstockmtno' => $row->fstockmtno,
                    'fstockmtdate' => $row->fstockmtdate
                        ? ($row->fstockmtdate instanceof \Carbon\Carbon ? $row->fstockmtdate : \Carbon\Carbon::parse($row->fstockmtdate))->format('d-m-Y')
                        : '',
                    'fgudang_dari' => $this->formatWarehouseLabel($row->ffrom ?? '', $row->from_warehouse_name ?? ''),
                    'fgudang_ke' => $this->formatWarehouseLabel($row->fto ?? '', $row->to_warehouse_name ?? ''),
                    'fket' => trim((string) ($row->fket ?? '')),
                    'fusercreate' => trim((string) ($row->fusercreate ?? '')),
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
        return view('mutasi.index', compact(
            'canCreate',
            'canEdit',
            'canDelete',
            'showActionsColumn',
            'availableYears',
            'year',
            'month'
        ));
    }

    private function formatWarehouseLabel($code, $name): string
    {
        $warehouseCode = trim((string) $code);
        $warehouseName = trim((string) $name);

        if ($warehouseCode !== '' && $warehouseName !== '') {
            return $warehouseCode . ' - ' . $warehouseName;
        }

        return $warehouseCode !== '' ? $warehouseCode : $warehouseName;
    }

    private function resolveCurrentUserBranchCode(): string
    {
        $raw = trim((string) ((Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang ?? ''));

        if ($raw === '') {
            return '';
        }

        if (is_numeric($raw)) {
            return trim((string) DB::table('mscabang')->where('fcabangid', (int) $raw)->value('fcabangkode'));
        }

        return trim((string) (DB::table('mscabang')
            ->whereRaw('LOWER(fcabangkode)=LOWER(?)', [$raw])
            ->orWhereRaw('LOWER(fcabangname)=LOWER(?)', [$raw])
            ->value('fcabangkode') ?: $raw));
    }

    private function mutasiWarehouseErrors(Request $request): array
    {
        $from = trim((string) $request->input('ffrom', ''));
        $to = trim((string) $request->input('fto', ''));
        $userBranch = $this->resolveCurrentUserBranchCode();
        $errors = [];

        if ($userBranch === '' || ! DB::table('mswh')
            ->whereRaw('TRIM(COALESCE(fwhcode, \'\')) = ?', [$from])
            ->whereRaw('TRIM(COALESCE(fbranchcode, \'\')) = ?', [$userBranch])
            ->where('fnonactive', '0')
            ->exists()) {
            $errors['ffrom'] = 'Gudang (Dari) harus gudang cabang user login.';
        }

        if (! DB::table('mswh')
            ->whereRaw('TRIM(COALESCE(fwhcode, \'\')) = ?', [$to])
            ->where('fnonactive', '0')
            ->exists()) {
            $errors['fto'] = 'Gudang (Tujuan) tidak valid.';
        }

        return $errors;
    }

    private function normalizeReferenceRandomNumber($value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return preg_match('/^\d{3}$/', $value) ? $value : null;
    }

    public function pickable(Request $request)
    {
        // Base query dengan JOIN
        $query = DB::table('tr_poh')
            ->leftJoin('mssupplier', 'tr_poh.fsupplier', '=', 'mssupplier.fsuppliercode')
            ->select(
                'tr_poh.*',
                'mssupplier.fsuppliername',
                'mssupplier.fsuppliercode'
            );

        // Total records tanpa filter
        $recordsTotal = DB::table('tr_poh')->count();

        // Search
        if ($request->filled('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tr_poh.fpono', 'ilike', "%{$search}%")
                    ->orWhere('mssupplier.fsuppliername', 'ilike', "%{$search}%")
                    ->orWhere('mssupplier.fsuppliercode', 'ilike', "%{$search}%");
            });
        }

        // Total records setelah filter
        $recordsFiltered = $query->count();

        // Sorting
        $orderColumn = $request->input('order_column', 'fpodate');
        $orderDir = $request->input('order_dir', 'desc');

        $allowedColumns = ['fpono', 'fsupplier', 'fpodate'];
        if (in_array($orderColumn, $allowedColumns)) {
            // Prefix table name untuk kolom di tr_poh
            if (in_array($orderColumn, ['fpono', 'fpodate'])) {
                $query->orderBy('tr_poh.' . $orderColumn, $orderDir);
            } else {
                $query->orderBy('mssupplier.fsuppliername', $orderDir);
            }
        } else {
            $query->orderBy('tr_poh.fpodate', 'desc');
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

        $prefix = sprintf('MUT.%s.%s%s.', $kodeCabang, $date->format('y'), $date->format('m'));

        // kunci per (branch, tahun-bulan) — TANPA bikin tabel baru
        $lockKey = crc32('MUT|' . $kodeCabang . '|' . $date->format('Y-m'));
        DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

        $last = DB::table('tr_poh')
            ->where('fpono', 'like', $prefix . '%')
            ->selectRaw("MAX(CAST(split_part(fpono, '.', 4) AS int)) AS lastno")
            ->value('lastno');

        $next = (int) $last + 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
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
            return redirect()->back()->with('error', 'Mutasi stock tidak ada.');
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

        $fmt = fn($d) => $d
            ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d F Y')
            : '-';

        return view('mutasi.print', [
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
            ->when(is_numeric($raw), fn($q) => $q->where('fcabangid', (int) $raw))
            ->when(
                ! is_numeric($raw),
                fn($q) => $q->where('fcabangkode', $raw)->orWhere('fcabangname', $raw)
            )
            ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

        $fcabang = $branch->fcabangname ?? (string) $raw;
        $fbranchcode = $branch->fcabangkode ?? (string) $raw;
        $fromWarehouseBranch = $this->resolveCurrentUserBranchCode();
        $fromWarehouses = $warehouses
            ->filter(fn($wh) => trim((string) ($wh->fbranchcode ?? '')) === $fromWarehouseBranch)
            ->values();

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

        return view('mutasi.create', [
            'newtr_prh_code' => $newtr_prh_code,
            'warehouses' => $warehouses,
            'fromWarehouses' => $fromWarehouses,
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
            $allowNegativeStockQty = stock_boleh_minus();
            // =========================
            // TAHAP 1: VALIDASI INPUT
            // =========================
            $request->validate([
                'fstockmtno' => ['nullable', 'string', 'max:100'],
                'fstockmtdate' => ['required', 'date'],
                'ffrom' => ['required', 'string', 'max:10'],
                'fto' => ['required', 'string', 'max:10'],
                'ftrancode' => ['nullable', 'string', 'max:3'],
                'fket' => ['nullable', 'string', 'max:50'],
                'fbranchcode' => ['nullable', 'string', 'max:20'],
                'fitemcode' => ['required', 'array', 'min:1'],
                'fitemcode.*' => ['required', 'string', 'max:50'],
                'fsatuan' => ['nullable', 'array'],
                'fsatuan.*' => ['nullable', 'string', 'max:20'],
                'frefno' => ['nullable', 'string', 'max:20'],
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
                'fprice.*' => ['numeric', 'min:0'],
                'fdesc' => ['nullable', 'array'],
                'fdesc.*' => ['nullable', 'string', 'max:500'],
                'fcurrency' => ['nullable', 'string', 'max:5'],
                'frate' => ['nullable', 'numeric', 'min:0'],
                'famountpopajak' => ['nullable', 'numeric', 'min:0'],
                'frefso' => ['nullable', 'array'],
                'frefso.*' => ['nullable', 'string', 'max:100'],
                'frefnoacak' => ['nullable', 'array'],
                'frefnoacak.*' => ['nullable', 'regex:/^\d{3}$/'],
            ]);

            $this->ensureNoDuplicateDetailCodes($request->input('fitemcode', []));
            if ($errors = $this->mutasiWarehouseErrors($request)) {
                return back()->withInput()->withErrors($errors);
            }

            // =========================
            // TAHAP 2: AMBIL DATA MASTER PRODUK
            // =========================
            $uniqueCodes = array_values(array_unique(
                array_filter(
                    array_map(fn($c) => trim((string) $c), $request->input('fitemcode', []))
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
            // TAHAP 3: RAKIT DETAIL & KONVERSI QTY
            // =========================
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
            $frefso = $request->input('frefso', []);
            $frefnoacaks = $request->input('frefnoacak', []);
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

                $sat = trim((string) ($satuans[$i] ?? ''));
                if ($sat === '') {
                    $sat = trim((string) ($meta->fsatuankecil ?? ''));
                }
                $sat = mb_substr($sat, 0, 5);

                // Konversi ke Qty Kecil
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
                    'fprdcode' => mb_substr((string) $meta->fprdcode, 0, 50),
                    'frefdtno' => trim((string) ($refdtno[$i] ?? '')) ?: null,
                    'fnoacak' => $this->normalizeRandomNumber(null, $usedNoAcaks),
                    'fqty' => $qty,
                    'fprice' => $price,
                    'fprice_rp' => $price * $frate,
                    'ftotprice' => $amount,
                    'ftotprice_rp' => $amount * $frate,
                    'fusercreate' => (Auth::user()->fname ?? 'system'),
                    'fdatetime' => $now,
                    'fketdt' => '',
                    'fcode' => '0',
                    'frefso' => trim((string) ($frefso[$i] ?? '')) ?: null,
                    'frefnoacak' => $this->normalizeReferenceRandomNumber($frefnoacaks[$i] ?? null),
                    'fdesc' => $descs[$i] ?? '',
                    'fsatuan' => $sat,
                    'fclosedt' => '0',
                    'fdiscpersen' => 0,
                    'fbiaya' => 0,
                    'fstockmtcode' => null,
                    'fstockmtno' => null,
                    'fqtykecil' => $qtyKecil,
                    'fqtyremain' => $qtyKecil,
                ];
            }

            if (empty($rowsDt)) {
                return back()->withInput()->withErrors(['fitemcode' => 'Tidak ada baris item valid.']);
            }

            if ($validationMessage = $this->validateUniqueReferenceUsage($rowsDt)) {
                return back()->withInput()->withErrors(['detail' => $validationMessage]);
            }

            if ($stockResponse = $this->validateStockMinusLines(
                $this->buildStockMinusLinesForOutChange($rowsDt, (string) $request->input('ffrom')),
                $request->boolean('force_save')
            )) {
                return $stockResponse;
            }

            // =========================
            // TAHAP 4: PERSIAPAN HEADER
            // =========================
            $fstockmtdate = \Carbon\Carbon::parse($request->fstockmtdate)->startOfDay();
            $this->ensureCreateDateWithinEditPeriod($fstockmtdate);
            $ppnAmount = (float) $request->input('famountpopajak', 0);
            $grandTotal = $subtotal + $ppnAmount;

            $headerData = [
                'fstockmtno' => trim((string) $request->input('fstockmtno')),
                'fstockmtcode' => 'MUT',
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
                'ftrancode' => $request->input('ftrancode'),
                'ffrom' => $request->input('ffrom'),
                'fto' => $request->input('fto'),
                'fket' => trim((string) $request->input('fket', '')),
                'fusercreate' => (Auth::user()->fname ?? 'system'),
                'fdatetime' => $now,
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

                if ($fstockmtno === '') {
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

                    $prefix = sprintf('MUT.%s.%s%s.', $kodeCabang, $headerData['fstockmtdate']->format('y'), $headerData['fstockmtdate']->format('m'));

                    $lockKey = crc32('STOCKMT|MUT|' . $kodeCabang . '|' . $headerData['fstockmtdate']->format('y-m'));
                    DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

                    $last = DB::table('trstockmt')
                        ->where('fstockmtno', 'like', $prefix . '%')
                        ->selectRaw("MAX(CAST(split_part(fstockmtno, '.', 4) AS int)) AS lastno")
                        ->value('lastno');

                    $fstockmtno = $prefix . str_pad((string) ((int) $last + 1), 4, '0', STR_PAD_LEFT);
                    $headerData['fbranchcode'] = $kodeCabang;
                    $headerData['fstockmtno'] = $fstockmtno;
                }

                $newId = DB::table('trstockmt')->insertGetId($headerData, 'fstockmtid');

                foreach ($rowsDt as &$r) {
                    $r['fstockmtcode'] = 'MUT';
                    $r['fstockmtno'] = $fstockmtno;
                }
                unset($r);

                DB::table('trstockdt')->insert($rowsDt);

                return $fstockmtno;
            });

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Mutasi {$finalNo} berhasil disimpan.",
                    'redirect_url' => route('mutasi.create'),
                ]);
            }

            return redirect()
                ->route('mutasi.create')
                ->with('success', "Mutasi {$finalNo} berhasil disimpan.");
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Mutasi belum bisa disimpan: ' . $e->getMessage()], 500);
            }
            return back()->withInput()->withErrors(['fatal' => 'Mutasi belum bisa disimpan. Cek data transaksi.']);
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

        $self = $this;
        $mutasi = PenerimaanPembelianHeader::with([
            'details' => function ($query) use ($self) {
                $self->appendMutasiDetailProductJoin($query);
            },
        ])
            ->findOrFail($fstockmtid);

        if ($message = $this->getPostedPeriodLockMessage($mutasi->fstockmtdate, 'Mutasi ini')) {
            return redirect()
                ->route('mutasi.view', $mutasi->fstockmtid)
                ->with('error', $message);
        }
        ['fcabang' => $fcabang, 'fbranchcode' => $fbranchcode] = $this->resolveBranchContext($mutasi->fbranchcode ?? null);
        $fromWarehouseBranch = $this->resolveCurrentUserBranchCode();
        $fromWarehouses = $warehouses
            ->filter(fn($wh) => trim((string) ($wh->fbranchcode ?? '')) === $fromWarehouseBranch)
            ->values();

        $usageLockMessage = $this->getUsageLockMessage($mutasi);

        if (! empty($usageLockMessage)) {
            return redirect()
                ->route('mutasi.view', $mutasi->fstockmtid)
                ->with('error', $usageLockMessage);
        }

        $savedItems = $mutasi->details->map(function ($d) {
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
                'frefso' => trim((string) ($d->frefso ?? '')),
                'frefnoacak' => trim((string) ($d->frefnoacak ?? '')),
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
        $selectedSupplierCode = $mutasi->fsupplier;

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

        return view('mutasi.edit', [
            'supplier' => $supplier,
            'selectedSupplierCode' => $selectedSupplierCode,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'warehouses' => $warehouses,
            'fromWarehouses' => $fromWarehouses,
            'accounts' => $accounts,
            'products' => $products,
            'productMap' => $productMap,
            'mutasi' => $mutasi,
            'savedItems' => $savedItems,
            'ppnAmount' => (float) ($mutasi->famountpopajak ?? 0),
            'famountponet' => (float) ($mutasi->famountponet ?? 0),
            'famountpo' => (float) ($mutasi->famountpo ?? 0),
            'isUsageLocked' => ! empty($usageLockMessage),
            'usageLockMessage' => $usageLockMessage,
            'action' => 'edit',
        ]);
    }

    public function view($fstockmtid)
    {
        return $this->edit($fstockmtid)->with([
            'action' => 'view',
            'isUsageLocked' => false,
            'usageLockMessage' => null,
        ]);
    }

    public function update(Request $request, $fstockmtid)
    {
        try {
            $allowNegativeStockQty = stock_boleh_minus();
            // =========================
            // 1) VALIDASI (Disamakan dengan store)
            // =========================
            $validated = $request->validate([
                'fstockmtno' => ['nullable', 'string', 'max:100'],
                'fstockmtdate' => ['required', 'date'],
                'ffrom' => ['required', 'string', 'max:10'],
                'fto' => ['required', 'string', 'max:10'],
                'ftrancode' => ['nullable', 'string', 'max:3'],
                'fket' => ['nullable', 'string', 'max:50'],
                'fbranchcode' => ['nullable', 'string', 'max:20'],
                'fitemcode' => ['required', 'array', 'min:1'],
                'fitemcode.*' => ['required', 'string', 'max:50'],
                'fsatuan' => ['nullable', 'array'],
                'fsatuan.*' => ['nullable', 'string', 'max:20'],
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
                'fprice.*' => ['numeric', 'min:0'],
                'fdesc' => ['nullable', 'array'],
                'fdesc.*' => ['nullable', 'string', 'max:500'],
                'fcurrency' => ['nullable', 'string', 'max:5'],
                'frate' => ['nullable', 'numeric', 'min:0'],
                'famountpopajak' => ['nullable', 'numeric', 'min:0'],
                'frefso' => ['nullable', 'array'],
                'frefso.*' => ['nullable', 'string', 'max:100'],
                'frefnoacak' => ['nullable', 'array'],
                'frefnoacak.*' => ['nullable', 'regex:/^\d{3}$/'],
            ]);

            $this->ensureNoDuplicateDetailCodes($request->input('fitemcode', []));
            if ($errors = $this->mutasiWarehouseErrors($request)) {
                return back()->withInput()->withErrors($errors);
            }

            // =========================
            // 2) AMBIL DATA MASTER
            // =========================
            // Pastikan nama model ini benar merujuk ke tabel trstockmt
            $header = PenerimaanPembelianHeader::findOrFail($fstockmtid);
            if ($message = $this->getPostedPeriodLockMessage($header->fstockmtdate, 'Mutasi ini')) {
                return redirect()->route('mutasi.view', $header->fstockmtid)->with('error', $message);
            }
            if ($message = $this->getUsageLockMessage($header)) {
                return redirect()->route('mutasi.index')->with('error', $message);
            }

            $uniqueCodes = array_values(array_unique(
                array_filter(array_map(fn($c) => trim((string) $c), $request->input('fitemcode', [])))
            ));

            $prodMeta = collect();
            if (! empty($uniqueCodes)) {
                $prodMeta = DB::table('msprd')
                    ->whereIn('fprdcode', $uniqueCodes)
                    ->get(['fprdid', 'fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2', 'fqtykecil', 'fqtykecil2'])
                    ->keyBy('fprdcode');
            }

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
            $frefso = $request->input('frefso', []);
            $frefnoacaks = $request->input('frefnoacak', []);
            $qtys = $request->input('fqty', []);
            $prices = $request->input('fprice', []);
            $descs = $request->input('fdesc', []);

            $rowCount = count($codes);

            // =========================
            // 3) LOOP DETAIL (Sesuai Logika Store)
            // =========================
            for ($i = 0; $i < $rowCount; $i++) {
                $code = trim((string) ($codes[$i] ?? ''));
                $sat = trim((string) ($satuans[$i] ?? ''));
                $rref = trim((string) ($refdtno[$i] ?? ''));
                $qty = (float) ($qtys[$i] ?? 0);
                $price = (float) ($prices[$i] ?? 0);
                $desc = (string) ($descs[$i] ?? '');

                if ($code === '' || ($allowNegativeStockQty ? abs($qty) < 0.000001 : $qty <= 0)) {
                    continue;
                }

                $meta = $prodMeta[$code] ?? null;
                if (! $meta) {
                    continue;
                }

                if ($sat === '') {
                    $sat = trim((string) ($meta->fsatuankecil ?? ''));
                }
                $sat = mb_substr($sat, 0, 5);

                $qtyKecil = $qty;
                if ($sat === trim((string) ($meta->fsatuanbesar ?? '')) && (float) ($meta->fqtykecil ?? 0) > 0) {
                    $qtyKecil = $qty * (float) $meta->fqtykecil;
                } elseif ($sat === trim((string) ($meta->fsatuanbesar2 ?? '')) && (float) ($meta->fqtykecil2 ?? 0) > 0) {
                    $qtyKecil = $qty * (float) $meta->fqtykecil2;
                }

                $refSo = trim((string) ($frefso[$i] ?? ''));

                $amount = $qty * $price;
                $subtotal += $amount;

                $rowsDt[] = [
                    'fprdcode' => mb_substr((string) ($meta->fprdcode ?? $code), 0, 50),
                    'frefdtno' => $rref,
                    'fnoacak' => $this->normalizeRandomNumber(null, $usedNoAcaks),
                    'fqty' => $qty,
                    'fprice' => $price,
                    'fprice_rp' => $price * $frate,
                    'ftotprice' => $amount,
                    'ftotprice_rp' => $amount * $frate,
                    'fuserupdate' => (Auth::user()->fname ?? 'system'), // Gunakan fuserupdate untuk edit
                    'fdatetime' => $now,
                    'fketdt' => '',
                    'fcode' => '0',
                    'frefso' => $refSo !== '' ? mb_substr($refSo, 0, 100) : null,
                    'frefnoacak' => $this->normalizeReferenceRandomNumber($frefnoacaks[$i] ?? null),
                    'fdesc' => $desc,
                    'fsatuan' => $sat,
                    'fclosedt' => '0',
                    'fdiscpersen' => 0,
                    'fbiaya' => 0,
                    'fstockmtcode' => $header->fstockmtcode,
                    'fstockmtno' => $header->fstockmtno,
                    'fqtykecil' => $qtyKecil,
                    'fqtyremain' => $qtyKecil,
                ];
            }

            if (empty($rowsDt)) {
                return back()->withInput()->withErrors(['fitemcode' => 'Tidak ada baris item valid.']);
            }

            if ($validationMessage = $this->validateUniqueReferenceUsage($rowsDt, $header->fstockmtno)) {
                return back()->withInput()->withErrors(['detail' => $validationMessage]);
            }

            // =========================
            // 4) TAHAP UPDATE DB
            // =========================
            $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
            $this->ensureCreateDateWithinEditPeriod($fstockmtdate, $header->fstockmtdate);
            $ppnAmount = (float) $request->input('famountpopajak', 0);
            $grandTotal = $subtotal + $ppnAmount;

            if ($stockResponse = $this->validateStockMinusLines(
                $this->buildStockMinusLinesForOutChange($rowsDt, (string) $request->input('ffrom'), $this->fetchStockDetailRows((string) $header->fstockmtno), (string) $header->ffrom),
                $request->boolean('force_save')
            )) {
                return $stockResponse;
            }

            DB::transaction(function () use ($header, $fstockmtdate, $frate, $subtotal, $ppnAmount, $grandTotal, $rowsDt, $request) {

                // 4.1 Update Header
                $header->update([
                    'fstockmtdate' => $fstockmtdate,
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
                    'ffrom' => $request->input('ffrom'),
                    'fto' => $request->input('fto'),
                    'ftrancode' => $request->input('ftrancode'),
                    'fket' => trim((string) $request->input('fket', '')),
                    'fuserupdate' => (Auth::user()->fname ?? 'system'),
                    'fbranchcode' => $request->input('fbranchcode'),
                ]);

                // 4.2 Sync Detail (Hapus lama, pasang baru)
                DB::table('trstockdt')->where('fstockmtno', $header->fstockmtno)->delete();
                DB::table('trstockdt')->insert($rowsDt);
            });

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Mutasi {$header->fstockmtno} berhasil diupdate.",
                    'redirect_url' => route('mutasi.index'),
                ]);
            }

            return redirect()
                ->route('mutasi.index')
                ->with('success', "Mutasi {$header->fstockmtno} berhasil diupdate.");
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Mutasi belum bisa diupdate: ' . $e->getMessage()], 500);
            }
            return back()->withInput()->withErrors([
                'fatal' => 'Mutasi belum bisa diupdate. Cek data transaksi.',
            ]);
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

        $self = $this;
        $mutasi = PenerimaanPembelianHeader::with([
            'details' => function ($query) use ($self) {
                $self->appendMutasiDetailProductJoin($query);
            },
        ])
            ->findOrFail($fstockmtid);

        if ($message = $this->getPostedPeriodLockMessage($mutasi->fstockmtdate, 'Mutasi ini')) {
            return redirect()
                ->route('mutasi.view', $mutasi->fstockmtid)
                ->with('error', $message);
        }
        ['fcabang' => $fcabang, 'fbranchcode' => $fbranchcode] = $this->resolveBranchContext($mutasi->fbranchcode ?? null);
        $fromWarehouseBranch = $this->resolveCurrentUserBranchCode();
        $fromWarehouses = $warehouses
            ->filter(fn($wh) => trim((string) ($wh->fbranchcode ?? '')) === $fromWarehouseBranch)
            ->values();

        $usageLockMessage = $this->getUsageLockMessage($mutasi);

        if (! empty($usageLockMessage)) {
            return redirect()
                ->route('mutasi.view', $mutasi->fstockmtid)
                ->with('error', $usageLockMessage);
        }

        $savedItems = $mutasi->details->map(function ($d) {
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
                'frefso' => trim((string) ($d->frefso ?? '')),
                'frefnoacak' => trim((string) ($d->frefnoacak ?? '')),
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
        $selectedSupplierCode = $mutasi->fsupplier;

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

        return view('mutasi.edit', [
            'supplier' => $supplier,
            'selectedSupplierCode' => $selectedSupplierCode,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'warehouses' => $warehouses,
            'fromWarehouses' => $fromWarehouses,
            'accounts' => $accounts,
            'products' => $products,
            'productMap' => $productMap,
            'mutasi' => $mutasi,
            'savedItems' => $savedItems,
            'ppnAmount' => (float) ($mutasi->famountpopajak ?? 0),
            'famountponet' => (float) ($mutasi->famountponet ?? 0),
            'famountpo' => (float) ($mutasi->famountpo ?? 0),
            'isUsageLocked' => ! empty($usageLockMessage),
            'usageLockMessage' => $usageLockMessage,
            'action' => 'delete',
        ]);
    }

    private function appendMutasiDetailProductJoin($query): void
    {
        $query->leftJoin('msprd', 'msprd.fprdcode', '=', 'trstockdt.fprdcode')
            ->select(
                'trstockdt.*',
                'msprd.fprdname',
                'msprd.fprdcode as fitemcode_text'
            )
            ->orderBy('trstockdt.fstockdtid', 'asc');
    }

    public function destroy($fstockmtid)
    {
        try {
            DB::beginTransaction();

            $mutasi = DB::table('trstockmt')
                ->where('fstockmtid', $fstockmtid)
                ->first();

            if (! $mutasi) {
                DB::rollBack();

                return redirect()->route('mutasi.index')->with('error', 'Mutasi tidak ada.');
            }

            if ($message = $this->getPostedPeriodLockMessage($mutasi->fstockmtdate, 'Mutasi ini')) {
                DB::rollBack();

                return redirect()->route('mutasi.view', $fstockmtid)->with('error', $message);
            }

            if ($message = $this->getUsageLockMessage(PenerimaanPembelianHeader::findOrFail($fstockmtid))) {
                DB::rollBack();

                return redirect()->route('mutasi.index')->with('error', $message);
            }

            $docNo = $mutasi->fstockmtno;

            if ($stockResponse = $this->validateStockMinusLines(
                $this->buildStockMinusLinesFromNetChange([], (string) $mutasi->fto, $this->fetchStockDetailRows((string) $docNo), (string) $mutasi->fto),
                request()->boolean('force_save')
            )) {
                DB::rollBack();

                return $stockResponse;
            }

            // 2. Hapus detail (trstockdt)
            DB::table('trstockdt')
                ->where('fstockmtno', $docNo)
                ->delete();

            // 3. Hapus header (trstockmt)
            DB::table('trstockmt')
                ->where('fstockmtid', $fstockmtid)
                ->delete();

            DB::commit();

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Mutasi ' . $docNo . ' berhasil dihapus.',
                    'redirect_url' => route('mutasi.index'),
                ]);
            }

            return redirect()->route('mutasi.index')->with('success', 'Mutasi ' . $docNo . ' berhasil dihapus.');
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Mutasi belum bisa dihapus. Coba lagi: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->route('mutasi.index')->with('error', 'Mutasi belum bisa dihapus. Coba lagi.');
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

        return 'Mutasi stock ' . $header->fstockmtno . ' sudah dipakai: ' . $usedBy->implode(', ') . '.';
    }

    private function buildMutasiReferenceUsageKey(?string $docNo, ?string $productCode, ?string $refNoAcak = null): string
    {
        return implode('|', [
            trim((string) ($docNo ?? '')),
            trim((string) ($productCode ?? '')),
            trim((string) ($refNoAcak ?? '')),
        ]);
    }

    private function buildMutasiReferenceUsageMap(array $rowsDt): array
    {
        $usage = [];

        foreach ($rowsDt as $row) {
            $docNo = trim((string) ($row['frefso'] ?? ''));
            $productCode = trim((string) ($row['fprdcode'] ?? ''));
            $refNoAcak = trim((string) ($row['frefnoacak'] ?? ''));

            if ($docNo === '' || $productCode === '') {
                continue;
            }

            $key = $this->buildMutasiReferenceUsageKey($docNo, $productCode, $refNoAcak);
            $usage[$key] = [
                'doc_no' => $docNo,
                'product_code' => $productCode,
                'ref_noacak' => $refNoAcak,
            ];
        }

        return $usage;
    }

    private function validateUniqueReferenceUsage(array $rowsDt, ?string $exceptStockMtNo = null): ?string
    {
        $referenceUsage = $this->buildMutasiReferenceUsageMap($rowsDt);
        $referenceDocNos = collect($referenceUsage)
            ->pluck('doc_no')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (! empty($referenceDocNos)) {
            $existingRows = DB::table('trstockdt as d')
                ->join('trstockmt as h', 'h.fstockmtno', '=', 'd.fstockmtno')
                ->where('h.fstockmtcode', 'MUT')
                ->whereIn('d.frefso', $referenceDocNos)
                ->when(! empty($exceptStockMtNo), fn($query) => $query->where('h.fstockmtno', '<>', $exceptStockMtNo))
                ->selectRaw("
                    h.fstockmtno as transaction_no,
                    TRIM(COALESCE(d.frefso, '')) as ref_no,
                    TRIM(COALESCE(d.fprdcode::text, '')) as product_code,
                    TRIM(COALESCE(d.frefnoacak::text, '')) as ref_noacak
                ")
                ->orderBy('h.fstockmtno')
                ->get();

            foreach ($existingRows as $existing) {
                $key = $this->buildMutasiReferenceUsageKey(
                    $existing->ref_no ?? '',
                    $existing->product_code ?? '',
                    $existing->ref_noacak ?? ''
                );

                if (isset($referenceUsage[$key])) {
                    return 'No. referensi ' . trim((string) ($existing->ref_no ?? '')) . ' sudah ada di transaksi ' . trim((string) ($existing->transaction_no ?? '')) . '.';
                }
            }
        }

        $referenceNos = collect($rowsDt)
            ->pluck('frefdtno')
            ->map(fn($value) => trim((string) ($value ?? '')))
            ->filter(fn($value) => $value !== '' && $value !== '0')
            ->unique()
            ->values()
            ->all();

        if (empty($referenceNos)) {
            return null;
        }

        foreach ($referenceNos as $referenceNo) {
            $query = DB::table('trstockdt as d')
                ->join('trstockmt as h', 'h.fstockmtno', '=', 'd.fstockmtno')
                ->where('h.fstockmtcode', 'MUT')
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
            $candidate = (string) random_int(1, 9) . random_int(1, 9) . random_int(1, 9);
        } while (in_array($candidate, $usedNumbers, true));

        $usedNumbers[] = $candidate;

        return $candidate;
    }
}
