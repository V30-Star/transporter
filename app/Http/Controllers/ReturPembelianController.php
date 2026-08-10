<?php

namespace App\Http\Controllers;

use App\Models\PenerimaanPembelianDetail;
use App\Models\PenerimaanPembelianHeader;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Tr_prd;
use App\Models\Tr_prh;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReturPembelianController extends Controller
{
    private const DAILY_CREATE_LIMIT = 15;

    private function todayCreateCount(): int
    {
        return PenerimaanPembelianHeader::where('fstockmtcode', 'REB')
            ->whereBetween('fdatetime', [now()->startOfDay(), now()->endOfDay()])
            ->count();
    }

    private function hasReachedDailyCreateLimit(): bool
    {
        return $this->todayCreateCount() >= self::DAILY_CREATE_LIMIT;
    }

    private function formatDisplayTransactionNumber(?string $number, bool $useSlash = false): string
    {
        $normalized = trim((string) $number);
        if ($normalized === '') {
            return '-';
        }

        if ($useSlash) {
            return str_replace('.', '/', $normalized);
        }

        return str_replace('/', '.', $normalized);
    }

    private function getDefaultPpnTarif(): float
    {
        $val = DB::table('setini')->value('fppntarif');

        return ($val !== null && is_numeric($val) && (float) $val > 0) ? (float) $val : 11.0;
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
            $messages["fitemcode.$index"] = "Kode produk {$code} tidak boleh sama dalam satu Retur Pembelian.";
        }

        throw ValidationException::withMessages($messages);
    }

    public function index(Request $request)
    {
        // --- 1. PERMISSIONS ---
        $canCreate = in_array('createReturPembelian', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updateReturPembelian', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteReturPembelian', explode(',', session('user_restricted_permissions', '')));
        $canPrint = in_array('printReturPembelian', explode(',', session('user_restricted_permissions', '')));
        $showActionsColumn = $canEdit || $canDelete || $canPrint;

        $year = $request->query('year');
        $month = $request->query('month');
        $createLimitReached = $this->hasReachedDailyCreateLimit();

        // Ambil tahun-tahun yang tersedia dari data
        $availableYearsQuery = PenerimaanPembelianHeader::selectRaw('DISTINCT EXTRACT(YEAR FROM fdatetime) as year')
            ->where('fstockmtcode', 'REB')
            ->whereNotNull('fdatetime');
        $this->applyBranchVisibilityScope($availableYearsQuery, 'trstockmt.fbranchcode');
        $availableYears = $availableYearsQuery
            ->orderByRaw('EXTRACT(YEAR FROM fdatetime) DESC')
            ->pluck('year');

        // --- 2. Handle Request AJAX dari DataTables ---
        if ($request->ajax()) {
            $baseQuery = DB::table('trstockmt')
                ->leftJoin('mswh as warehouse', 'warehouse.fwhcode', '=', 'trstockmt.ffrom')
                ->leftJoin('mssupplier as supplier', 'supplier.fsuppliercode', '=', 'trstockmt.fsupplier')
                ->where('trstockmt.fstockmtcode', 'REB');
            $this->applyBranchVisibilityScope($baseQuery, 'trstockmt.fbranchcode');

            $query = clone $baseQuery;
            $totalRecords = (clone $baseQuery)->count('trstockmt.fstockmtid');

            if ($search = trim((string) $request->input('search.value'))) {
                $query->where(function ($q) use ($search) {
                    $q->where('trstockmt.fstockmtno', 'ilike', "%{$search}%")
                        ->orWhere('warehouse.fwhname', 'ilike', "%{$search}%")
                        ->orWhere('supplier.fsuppliername', 'ilike', "%{$search}%");
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

            // Total records setelah filter search
            $filteredRecords = (clone $query)->count();

            $orderColIdx = $request->input('order.0.column');
            $orderDir = $request->input('order.0.dir', 'desc');

            $orderColumn = null;
            if ($orderColIdx !== null) {
                $colName = $request->input("columns.{$orderColIdx}.name") ?: $request->input("columns.{$orderColIdx}.data");
                if ($colName === 'fbranchcode') {
                    $orderColumn = 'trstockmt.fbranchcode';
                } elseif ($colName === 'fstockmtno') {
                    $orderColumn = 'trstockmt.fstockmtno';
                } elseif ($colName === 'fstockmtdate') {
                    $orderColumn = 'trstockmt.fstockmtdate';
                } elseif ($colName === 'ffrom') {
                    $orderColumn = 'trstockmt.ffrom';
                } elseif ($colName === 'fsuppliername') {
                    $orderColumn = 'supplier.fsuppliername';
                } elseif ($colName === 'famountmt') {
                    $orderColumn = 'trstockmt.famountmt';
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
                    'trstockmt.fbranchcode',
                    'trstockmt.fstockmtid',
                    'trstockmt.fstockmtno',
                    'trstockmt.fincludeppn',
                    'trstockmt.fstockmtdate',
                    'trstockmt.fusercreate',
                    'trstockmt.famountmt',
                    'trstockmt.ffrom',
                    'warehouse.fwhname as warehouse_name',
                    'supplier.fsuppliername as supplier_name',
                ]);

            // Format Data dengan Actions Column
            $data = $records->map(function ($row) {
                $actions = '';

                // if ($showActionsColumn) {
                $actions = '<div class="flex gap-2">';

                // --- Tombol view ---
                // if ($canView) {
                // Asumsi route edit Anda: returpembelian.edit
                $viewUrl = route('returpembelian.view', $row->fstockmtid);
                $actions .= ' <a href="' . $viewUrl . '" class="inline-flex items-center bg-slate-500 text-white px-4 py-2 rounded hover:bg-slate-600">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg> View
                                </a>';
                // }

                // Edit Button
                // if ($canEdit) {
                $actions .= '<a href="' . route('returpembelian.edit', $row->fstockmtid) . '" class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
              </svg>
              Edit
            </a>';
                // }

                // Delete Button
                // if ($canDelete) {
                $deleteUrl = route('returpembelian.delete', $row->fstockmtid);
                $actions .= '<a href="' . $deleteUrl . '">
                <button class="inline-flex items-center bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Hapus
                </button>
            </a>';
                // }

                $actions .= '</div>';
                // }

                return [
                    'fstockmtid' => $row->fstockmtid,
                    'fstockmtno' => $row->fstockmtno,
                    'fstockmtno_display' => $this->formatDisplayTransactionNumber($row->fstockmtno ?? null, (string) ($row->fapplyppn ?? '0') === '0' && (string) ($row->fincludeppn ?? '0') === '0'),
                    'fstockmtdate' => $row->fstockmtdate
                        ? ($row->fstockmtdate instanceof \Carbon\Carbon ? $row->fstockmtdate : \Carbon\Carbon::parse($row->fstockmtdate))->format('d-m-Y')
                        : '',
                    'fwhname' => (string) ($row->warehouse_name ?? ''),
                    'ffrom' => $row->ffrom,
                    'fbranchcode' => $row->fbranchcode,
                    'fusercreate' => $row->fusercreate,
                    'fsuppliername' => (string) ($row->supplier_name ?? ''),
                    'famountmt' => number_format((float) ($row->famountmt ?? 0), 2, ',', '.'),
                    'actions' => $actions,
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
        return view('returpembelian.index', compact(
            'canCreate',
            'canEdit',
            'canDelete',
            'canPrint',
            'showActionsColumn',
            'availableYears',
            'year',
            'month',
            'createLimitReached'
        ));
    }

    private function getSupplierAdvanceWarningMap(): array
    {
        $documentsBySupplier = DB::table('trsisadp_pembelian')
            ->selectRaw('TRIM(COALESCE(fsupplier, \'\')) as fsupplier')
            ->addSelect(['fstockmtno', 'fstockmtdate', 'fsisadp', 'fsisadp_rp'])
            ->where('fsisadp', '>', 0)
            ->orderBy('fstockmtdate')
            ->orderBy('fstockmtno')
            ->get()
            ->map(fn ($doc) => [
                'fsupplier'    => trim((string) ($doc->fsupplier ?? '')),
                'fstockmtno'   => trim((string) ($doc->fstockmtno ?? '')),
                'fstockmtdate' => $doc->fstockmtdate,
                'fsisadp'      => (float) ($doc->fsisadp ?? 0),
                'fsisadp_rp'   => (float) ($doc->fsisadp_rp ?? 0),
            ])
            ->filter(fn ($doc) => $doc['fsupplier'] !== '' && $doc['fstockmtno'] !== '')
            ->groupBy('fsupplier');

        return DB::table('trsisadp_pembelian')
            ->selectRaw('TRIM(COALESCE(fsupplier, \'\')) as fsupplier')
            ->selectRaw('SUM(COALESCE(fsisadp, 0)) as total_remain')
            ->selectRaw('SUM(COALESCE(fsisadp_rp, 0)) as total_remain_rp')
            ->where('fsisadp', '>', 0)
            ->groupBy(DB::raw('TRIM(COALESCE(fsupplier, \'\'))'))
            ->get()
            ->filter(fn($row) => trim((string) ($row->fsupplier ?? '')) !== '')
            ->mapWithKeys(function ($row) use ($documentsBySupplier) {
                $supplierCode = trim((string) ($row->fsupplier ?? ''));
                $remainRp = (float) ($row->total_remain_rp ?? 0);

                return [
                    $supplierCode => [
                        'message' => $remainRp > 0
                            ? 'Supplier ini memiliki Uang Muka (UM) sebesar ' . number_format($remainRp, 2, ',', '.') . '.'
                            : 'Supplier ini memiliki Uang Muka (UM).',
                        'documents' => $documentsBySupplier->get($supplierCode, collect())->values()->all(),
                    ],
                ];
            })
            ->all();
    }

    private function getSupplierOutstandingDpDocument(string $supplierCode): ?object
    {
        $supplierCode = trim($supplierCode);
        if ($supplierCode === '') {
            return null;
        }

        return DB::table('trsisadp_pembelian')
            ->whereRaw('TRIM(COALESCE(fsupplier, \'\')) = ?', [$supplierCode])
            ->where('fsisadp', '>', 0)
            ->orderBy('fstockmtdate')
            ->orderBy('fstockmtno')
            ->first(['fstockmtno', 'fsisadp']);
    }

    public function pickable(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $perPage = (int) $request->get('per_page', 10);

        // Ambil dari tr_prh dengan kondisi yang kamu minta
        $query = Tr_prh::query()
            ->select([
                'tr_prh.fprhid',
                'tr_prh.fprno',
                'tr_prh.fsupplier',
                'tr_prh.fprdate',
            ])
            ->where('tr_prh.fapproval', 2)
            ->where('tr_prh.fprdin', 0);

        // Optional search: fprno / fsupplier / tanggal
        if ($search !== '') {
            // PostgreSQL -> ILIKE, MySQL -> LIKE (ganti sesuai DB)
            $likeOp = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
            $query->where(function ($q) use ($search, $likeOp) {
                $q->where('tr_prh.fprno', $likeOp, "%{$search}%")
                    ->orWhere('tr_prh.fsupplier', $likeOp, "%{$search}%")
                    ->orWhereRaw("TO_CHAR(tr_prh.fprdate, 'YYYY-MM-DD HH24:MI:SS') {$likeOp} ?", ["%{$search}%"]);
            });
        }

        // Urutan paling baru
        $query->orderByDesc('tr_prh.fprdate')
            ->orderByDesc('tr_prh.fprhid');

        $paginated = $query->paginate($perPage)->withQueryString();

        // Format JSON agar cocok dengan kode Alpine kamu
        $rows = collect($paginated->items())->map(function ($t) {
            return [
                'fprhid' => $t->fprhid,
                'fprno' => $t->fprno,
                'fsupplier' => trim($t->fsupplier ?? ''),
                'fprdate' => $t->fprdate ? \Carbon\Carbon::parse($t->fprdate)->format('Y-m-d H:i:s') : 'No Date',
                // siapkan URL jika dibutuhkan
                'items_url' => route('tr_poh.items', $t->fprhid),
            ];
        });

        return response()->json([
            'data' => $rows,
            'links' => [
                'prev' => $paginated->previousPageUrl(),
                'next' => $paginated->nextPageUrl(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'total' => $paginated->total(),
            ],
            // compat untuk key yang sudah kamu baca di frontend
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'total' => $paginated->total(),
        ]);
    }

    public function items($id)
    {
        // Ambil data header PR berdasarkan fprhid
        $header = Tr_prh::where('fprhid', $id)->firstOrFail();

        // Detail PR sekarang dihubungkan lewat fprno
        $items = Tr_prd::where('tr_prd.fprno', $header->fprno)
            ->leftJoin('msprd as m', 'm.fprdid', '=', 'tr_prd.fprdcode')
            ->select([
                'tr_prd.fprdid as frefdtno',
                'tr_prd.fprdcode as fitemcode',
                'm.fprdname as fitemname',
                'tr_prd.fqty',
                'tr_prd.fsatuan as fsatuan',
                'tr_prd.fprno',
                'tr_prd.ftotprice as fharga',
                DB::raw('0::numeric as fdiskon'),
            ])
            ->orderBy('tr_prd.fprdid')
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

    private function generatetr_poh_Code(?Carbon $onDate = null, $branch = null, bool $hasPpn = true): string
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

        $sep = $hasPpn ? '.' : '/';
        $prefix = sprintf('REB%s%s%s%s%s', $sep, $kodeCabang, $sep, $date->format('y') . $date->format('m'), $sep);

        $lockKey = crc32('STOCKMT|REB|' . $kodeCabang . '|' . $date->format('y-m'));
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

            $last = DB::table('trstockmt')
                ->where('fstockmtno', 'like', "{$prefix}%")
                ->selectRaw("MAX(CAST(SUBSTRING(fstockmtno FROM '([0-9]+)$') AS int)) AS lastno")
                ->value('lastno');

            $next = (int) $last + 1;
        } else {
            $lastCode = DB::table('trstockmt')
                ->where('fstockmtno', 'like', "{$prefix}%")
                ->orderByDesc('fstockmtno')
                ->value('fstockmtno');

            $next = 1;
            if ($lastCode && ($pos = max((int) strrpos($lastCode, '.'), (int) strrpos($lastCode, '/'))) !== false && $pos > 0) {
                $next = ((int) substr($lastCode, $pos + 1)) + 1;
            }
        }

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
            ->where(function ($q) use ($fstockmtno) {
                if (is_numeric($fstockmtno)) {
                    $q->where('trstockmt.fstockmtid', (int) $fstockmtno);
                }
                $slash = str_replace('.', '/', $fstockmtno);
                $dot = str_replace('/', '.', $fstockmtno);
                $q->orWhere('trstockmt.fstockmtno', $fstockmtno)
                  ->orWhere('trstockmt.fstockmtno', $slash)
                  ->orWhere('trstockmt.fstockmtno', $dot);
            })
            ->first([
                'trstockmt.*',
                's.fsuppliername as supplier_name',
                'c.fcabangname as cabang_name',
                'w.fwhname as fwhnamen',
            ]);

        if (! $hdr) {
            return redirect()->back()->with('error', 'Retur pembelian tidak ada.');
        }

        DB::table('trstockmt')->where('fstockmtno', $hdr->fstockmtno)->update(['fprint' => 1]);

        $dt = PenerimaanPembelianDetail::query()
            ->leftJoin('msprd as p', 'p.fprdcode', '=', 'trstockdt.fprdcode')
            ->where('trstockdt.fstockmtno', $hdr->fstockmtno)
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

        return view('returpembelian.print', [
            'hdr' => $hdr,
            'dt' => $dt,
            'displayFstockmtno' => $this->formatDisplayTransactionNumber($hdr->fstockmtno ?? null, (string) ($hdr->fapplyppn ?? '0') === '0' && (string) ($hdr->fincludeppn ?? '0') === '0'),
            'fmt' => $fmt,
            'company_name' => config('app.company_name', 'PT. DEMO VERSION'),
            'company_city' => config('app.company_city', 'Tangerang'),
        ]);
    }

    private function latestPurchaseHistory(string $supplierCode, string $productCode, string $unit): ?object
    {
        return DB::table('trstockmt as m')
            ->join('trstockdt as d', 'm.fstockmtno', '=', 'd.fstockmtno')
            ->where('m.fstockmtcode', 'BUY')
            ->whereRaw('TRIM(d.fprdcode) = ?', [$productCode])
            ->whereRaw('TRIM(m.fsupplier) = ?', [$supplierCode])
            ->whereRaw('TRIM(d.fsatuan) = ?', [$unit])
            ->orderByDesc('m.fstockmtdate')
            ->orderByDesc('m.fstockmtno')
            ->select('d.fprice', 'd.fpricenet', 'd.fbiaya', 'd.fsatuan', 'd.fdiscpersen')
            ->first();
    }

    public function productPrice(Request $request)
    {
        $supplierCode = trim((string) $request->input('fsupplier', ''));
        $productCode = trim((string) $request->input('fprdcode', ''));
        $unit = trim((string) $request->input('fsatuan', ''));

        if ($productCode === '') {
            return response()->json([
                'price' => 0,
                'discount' => '0',
                'source' => 'default',
            ]);
        }

        $history = ($supplierCode !== '' && $unit !== '')
            ? $this->latestPurchaseHistory($supplierCode, $productCode, $unit)
            : null;

        if (! $history && $supplierCode !== '') {
            $history = DB::table('trstockmt as m')
                ->join('trstockdt as d', 'm.fstockmtno', '=', 'd.fstockmtno')
                ->where('m.fstockmtcode', 'BUY')
                ->whereRaw('TRIM(d.fprdcode) = ?', [$productCode])
                ->whereRaw('TRIM(m.fsupplier) = ?', [$supplierCode])
                ->orderByDesc('m.fstockmtdate')
                ->orderByDesc('m.fstockmtno')
                ->select('d.fprice', 'd.fpricenet', 'd.fbiaya', 'd.fsatuan', 'd.fdiscpersen')
                ->first();
        }

        if ($history) {
            $effectivePrice = (float) (($history->fpricenet ?? 0) > 0 ? max(0, (float)$history->fpricenet - (float)($history->fbiaya ?? 0)) : ($history->fprice ?? 0));
            return response()->json([
                'price' => $effectivePrice,
                'unit' => trim((string) ($history->fsatuan ?? $unit)),
                'discount' => (string) ($history->fdiscpersen ?? '0'),
                'source' => 'history',
            ]);
        }

        $product = DB::table('msprd')->where('fprdcode', $productCode)->first();
        $price = 0.0;
        if ($product) {
            $price = (float) ($product->fhpp ?? 0);
        }

        return response()->json([
            'price' => $price,
            'discount' => '0',
            'source' => 'master',
        ]);
    }

    public function create(Request $request)
    {
        if ($this->hasReachedDailyCreateLimit()) {
            return redirect()
                ->route('returpembelian.index')
                ->with('create_limit_exceeded', true);
        }

        $suppliers = Supplier::orderBy('fsuppliername', 'asc')
            ->get(['fsupplierid', 'fsuppliercode', 'fsuppliername']);

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

        $newtr_prh_code = $this->generatetr_poh_Code(now(), $fbranchcode);

        $products = Product::select(
            'fprdid',
            'fprdcode',
            'fprdname',
            'fsatuankecil',
            'fsatuanbesar',
            'fsatuanbesar2',
            'fminstock'
        )
            ->whereRaw("COALESCE(TRIM(CAST(fnonactive AS TEXT)), '0') != '1'")
            ->orderBy('fprdname')
            ->get();

        return view('returpembelian.create', [
            'newtr_prh_code' => $newtr_prh_code,
            'warehouses' => $warehouses,
            'accounts' => $accounts,
            'suppliers' => $suppliers,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'products' => $products,
            'supplierAdvanceWarnings' => $this->getSupplierAdvanceWarningMap(),
            'defaultPpnTarif' => $this->getDefaultPpnTarif(),
            'filterSupplierId' => $request->query('filter_supplier_id'),
        ]);
    }

    public function store(Request $request)
    {
        try {
            if ($this->hasReachedDailyCreateLimit()) {
                return redirect()
                    ->route('returpembelian.index')
                    ->with('create_limit_exceeded', true);
            }

            $allowNegativeStockQty = stock_boleh_minus();
            // VALIDATION
            $request->validate([
                'fstockmtno' => [
                    'nullable',
                    'string',
                    'max:100',
                    function ($attribute, $value, $fail) use ($request) {
                        $inputNo = $value ?: $request->input('fpono');
                        if (! $request->boolean('auto_generate', true) && empty(trim((string) $inputNo))) {
                            $fail('No. Transaksi wajib diisi jika Auto tidak dicentang.');
                        }
                    },
                ],
                'fpono' => ['nullable', 'string', 'max:100'],
                'fstockmtdate' => ['required', 'date'],
                'fsupplier' => ['required', 'string', 'max:30'],
                'ffrom' => ['required', 'string', 'max:10'],
                'fket' => ['nullable', 'string', 'max:50'],
                'fbranchcode' => ['nullable', 'string', 'max:20'],
                'fitemcode' => ['required', 'array', 'min:1'],
                'fitemcode.*' => ['required', 'string', 'max:50'],
                'fsatuan' => ['nullable', 'array'],
                'fsatuan.*' => ['nullable', 'string', 'max:20'],
                'frefdtno' => ['nullable', 'array'],
                'frefdtno.*' => ['nullable', 'string', 'max:20'],
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
                'frefno' => ['nullable', 'string', 'max:100'],
                'frefpo' => ['nullable', 'string', 'max:100'],
            ], [
                'ffrom.required' => 'Gudang wajib di isi.',
                'fstockmtdate.required' => 'Tanggal transaksi wajib diisi.',
                'fsupplier.required' => 'Supplier wajib diisi.',
                'fitemcode.required' => 'Minimal 1 item.',
                'fsatuan.*.max' => 'Satuan maksimal 5 karakter.',
            ]);

            $this->ensureNoDuplicateDetailCodes($request->input('fitemcode', []));

            // HEADER FIELDS
            $fstockmtnoRaw = strtoupper(trim((string) $request->input('fstockmtno')));
            $fstockmtno = $fstockmtnoRaw !== '' ? $this->formatDisplayTransactionNumber($fstockmtnoRaw, (int) $request->input('fapplyppn', 0) === 0 && (int) $request->input('fincludeppn', 0) === 0) : '';
            $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
            $this->ensureCreateDateWithinEditPeriod($fstockmtdate);
            $fsupplier = trim((string) $request->input('fsupplier'));
            $ffrom = $request->input('ffrom');
            $fket = trim((string) $request->input('fket', ''));
            $fbranchcode = $request->input('fbranchcode');
            $frefno = $request->input('frefno');
            $frefpo = $request->input('frefpo');

            $userid = auth('sysuser')->user()->fsysuserid ?? 'admin';
            $now = now();

            // DETAIL ARRAYS
            $codes = $request->input('fitemcode', []);
            $satuans = $request->input('fsatuan', []);
            $refdtno = $request->input('frefdtno', []);
            $nourefs = $request->input('frefdtno', []);
            $qtys = $request->input('fqty', []);
            $prices = $request->input('fprice', []);
            $descs = $request->input('fdesc', []);
            $itemeId = null;

            $subtotal = (float) $request->input('famount', 0);
            $ppnAmount = (float) $request->input('famountpajak', 0);
            $grandTotal = (float) $request->input('famountmt', 0);

            $fincludeppn = 0; // PPN Retur Pembelian selalu Exclude
            $defaultPpnTarif = $this->getDefaultPpnTarif();
            $fppnpersen = (float) $request->input('famountpopajak', $defaultPpnTarif);
            if ($fppnpersen <= 0 && $fincludeppn === 1) {
                $fppnpersen = $defaultPpnTarif;
            }

            // LOAD PRODUCT METADATA
            $uniqueCodes = array_values(array_unique(array_filter(array_map(fn($c) => trim((string) $c), $codes))));

            $prodMeta = DB::table('msprd')
                ->whereIn('fprdcode', $uniqueCodes)
                ->get(['fprdid', 'fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2', 'fqtykecil', 'fqtykecil2'])
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

            if ($validationMessage = $this->validateUniqueHeaderReference($frefno, $frefpo)) {
                return back()->withInput()->withErrors([
                    'detail' => $validationMessage,
                ]);
            }

            $typeBuy = (int) $request->input('ftypebuy', 0);

            // BUILD DETAIL ROWS
            $hasUM = collect($codes)->map(fn($c) => strtoupper(trim((string) $c)))->contains('UM');
            $hasNonUM = collect($codes)
                ->map(fn($c) => strtoupper(trim((string) $c)))
                ->filter(fn($c) => $c !== '' && $c !== 'UM')
                ->isNotEmpty();
            $outstandingDpDoc = $this->getSupplierOutstandingDpDocument($fsupplier);
            $outstandingDpRef = trim((string) ($outstandingDpDoc->fstockmtno ?? ''));
            $outstandingDpAmount = (float) ($outstandingDpDoc->fsisadp ?? 0);

            if ($typeBuy === 0 && $hasUM) {
                $msg = 'Tipe Pembelian tidak boleh menginput Uang Muka (UM).';
                if (request()->expectsJson()) {
                    return response()->json(['message' => $msg], 422);
                }
                return back()->withInput()->with('error', $msg);
            }

            if ($typeBuy !== 0 && $hasNonUM) {
                $msg = 'Tipe Uang Muka hanya boleh menginput Uang Muka (UM).';
                if (request()->expectsJson()) {
                    return response()->json(['message' => $msg], 422);
                }
                return back()->withInput()->with('error', $msg);
            }

            $rowsDt = [];
            $usedNoAcaks = [];
            $subtotal = 0.0;

            for ($i = 0; $i < count($codes); $i++) {
                $code = trim((string) ($codes[$i] ?? ''));
                $sat = trim((string) ($satuans[$i] ?? ''));
                $rref = trim((string) ($refdtno[$i] ?? ''));
                $rnour = $nourefs[$i] ?? null;
                $qty = (float) ($qtys[$i] ?? 0);
                $price = (float) ($prices[$i] ?? 0);
                $desc = (string) ($descs[$i] ?? '');

                if ($code === '' || $qty <= 0) {
                    continue;
                }

                if (strtoupper(trim((string) $code)) === 'UM') {
                    $rref = $outstandingDpRef !== '' ? $outstandingDpRef : $rref;
                    $absPrice = $outstandingDpAmount > 0 ? $outstandingDpAmount : abs($price);
                    $price = $hasNonUM ? -$absPrice : $absPrice;
                }

                $meta = $prodMeta[$code] ?? null;
                if (! $meta) {
                    continue;
                }

                $prdId = $meta->fprdid;
                $itemeId = $prdId;

                if ($sat === '') {
                    $sat = $pickDefaultSat($meta);
                }
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

                $priceGross = $price;
                $amount = $qty * $priceGross;
                $subtotal += $amount;

                $rowsDt[] = [
                    'fprdcode' => $code,
                    'fnoacak' => $this->normalizeRandomNumber(null, $usedNoAcaks),
                    'frefdtno' => $rref,
                    'fqty' => $qty,
                    'fprice' => $price,
                    'ftotprice' => $amount,
                    'fusercreate' => (Auth::user()->fname ?? 'system'),
                    'fdatetime' => $now,
                    'fketdt' => '',
                    'fcode' => '0',
                    'frefso' => null,
                    'fdesc' => $desc,
                    'fsatuan' => $sat,
                    'fclosedt' => '0',
                    'fqtykecil' => $qtyKecil,
                    'fqtyremain' => $qtyKecil,
                ];
            }

            if (empty($rowsDt)) {
                return back()->withInput()->withErrors([
                    'detail' => 'Minimal 1 item valid (Kode, Satuan, Qty > 0).',
                ]);
            }

            if ($stockResponse = $this->validateStockMinusLines(
                $this->buildStockMinusLinesForOutChange($rowsDt, (string) $ffrom),
                $request->boolean('force_save')
            )) {
                return $stockResponse;
            }

            $grandTotal = $subtotal + $ppnAmount;

            // DATABASE TRANSACTION
            DB::transaction(function () use (
                $typeBuy,
                $fstockmtdate,
                $fsupplier,
                $ffrom,
                $fket,
                $fbranchcode,
                $now,
                $frefno,
                $frefpo,
                &$fstockmtno,
                &$rowsDt,
                $subtotal,
                $ppnAmount,
                $grandTotal,
                $userid,
                $fincludeppn,
                $fppnpersen
            ) {
                // BRANCH CODE RESOLUTION
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

                // GENERATE DOCUMENT NUMBER
                $yy = $fstockmtdate->format('y');
                $mm = $fstockmtdate->format('m');
                $fstockmtcode = 'REB';

                if (empty($fstockmtno)) {
                    $sep = $fincludeppn === 1 ? '.' : '/';
                    $prefix = sprintf('%s%s%s%s%s%s', $fstockmtcode, $sep, $kodeCabang, $sep, $yy . $mm, $sep);

                    $lockKey = crc32('STOCKMT|' . $fstockmtcode . '|' . $kodeCabang . '|' . $fstockmtdate->format('y-m'));
                    if (DB::getDriverName() === 'pgsql') {
                        DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

                        $last = DB::table('trstockmt')
                            ->where('fstockmtno', 'like', "{$prefix}%")
                            ->selectRaw("MAX(CAST(SUBSTRING(fstockmtno FROM '([0-9]+)$') AS int)) AS lastno")
                            ->value('lastno');

                        $next = (int) $last + 1;
                    } else {
                        $lastCode = DB::table('trstockmt')
                            ->where('fstockmtno', 'like', "{$prefix}%")
                            ->orderByDesc('fstockmtno')
                            ->value('fstockmtno');

                        $next = 1;
                        if ($lastCode && ($pos = max((int) strrpos($lastCode, '.'), (int) strrpos($lastCode, '/'))) !== false && $pos > 0) {
                            $next = ((int) substr($lastCode, $pos + 1)) + 1;
                        }
                    }

                    $fstockmtno = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
                }

                // INSERT HEADER
                $masterData = [
                    'fstockmtno' => $fstockmtno,
                    'fstockmtcode' => $fstockmtcode,
                    'fstockmtdate' => $fstockmtdate,
                    'fprdout' => '0',
                    'fsupplier' => $fsupplier,
                    'famount' => round($subtotal, 2),
                    'famountmt' => round($grandTotal, 2),
                    'frefno' => $frefno,
                    'frefpo' => $frefpo,
                    'ftrancode' => null,
                    'ffrom' => $ffrom,
                    'fto' => null,
                    'fkirim' => null,
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
                    'fincludeppn' => $fincludeppn,
                    'ftypebuy' => $typeBuy,
                    'fppnpersen' => $fppnpersen,
                    'famountpajak' => round($ppnAmount, 2),
                    'famountpajak_rp' => round($ppnAmount, 2),
                ];

                $newStockMasterId = DB::table('trstockmt')->insertGetId($masterData, 'fstockmtid');

                foreach ($rowsDt as &$r) {
                    $r['fstockmtcode'] = $fstockmtcode;
                    $r['fstockmtno'] = $fstockmtno;
                }
                unset($r);

                DB::table('trstockdt')->insert($rowsDt);

                $this->syncReturPembelianJournalEntries(
                    (string) $fstockmtno,
                    $fstockmtdate,
                    (string) $kodeCabang,
                    (string) $fsupplier,
                    (float) $subtotal,
                    (float) $ppnAmount,
                    (float) $grandTotal,
                    (string) $userid
                );
            });

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => "Retur pembelian {$fstockmtno} berhasil disimpan.",
                    'redirect_url' => route('returpembelian.create'),
                    'success_prompt' => [
                        'type' => 'returpembelian_create',
                        'redirect_url' => route('returpembelian.print', $fstockmtno),
                    ],
                ]);
            }

            return redirect()
                ->route('returpembelian.create')
                ->with('success', "Retur pembelian {$fstockmtno} berhasil disimpan.")
                ->with('success_prompt', [
                    'type' => 'returpembelian_create',
                    'redirect_url' => route('returpembelian.print', $fstockmtno),
                ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => $firstError ?: 'Retur pembelian belum bisa disimpan. Cek data.',
                    'errors' => $e->errors(),
                ], 422);
            }
            return back()->withInput()->withErrors($e->errors());
        } catch (\Throwable $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Retur pembelian belum bisa disimpan: ' . $e->getMessage(),
                ], 500);
            }
            return back()
                ->withInput()
                ->withErrors(['error' => 'Retur pembelian belum bisa disimpan: ' . $e->getMessage()]);
        }
    }

    public function edit(Request $request, $fstockmtid)
    {
        $suppliers = Supplier::orderBy('fsuppliername', 'asc')
            ->get(['fsupplierid', 'fsuppliercode', 'fsuppliername']);

        // 1. PINDAHKAN INI KE ATAS
        // Ambil data Header (trstockmt) DULU
        $returpembelian = PenerimaanPembelianHeader::with([
            'details' => function ($query) {
                $query
                    ->leftJoin('msprd', function ($join) {
                        $join->on('msprd.fprdcode', '=', 'trstockdt.fprdcode');
                    })
                    ->select(
                        'trstockdt.*',
                        'msprd.fprdname',
                        'msprd.fprdcode as fitemcode_text'
                    )
                    ->orderBy('trstockdt.fstockdtid', 'asc');
            },
        ])
            ->findOrFail($fstockmtid); // Temukan header berdasarkan $fstockmtid

        if ($message = $this->getPostedPeriodLockMessage($returpembelian->fstockmtdate, 'Retur Pembelian ini')) {
            return redirect()
                ->route('returpembelian.view', $returpembelian->fstockmtid)
                ->with('error', $message);
        }

        $usageLockMessage = $this->getUsageLockMessage($returpembelian);

        if (! empty($usageLockMessage)) {
            return redirect()
                ->route('returpembelian.view', $returpembelian->fstockmtid)
                ->with('error', $usageLockMessage);
        }

        // 2. Ambil kode akun yang tersimpan dari faktur
        $savedAccountCode = $returpembelian->fprdjadi;

        // 3. UBAH QUERY INI: Gunakan $savedAccountCode
        $accounts = DB::table('account')
            ->select('faccid', 'faccount', 'faccname', 'fnonactive')
            ->where('fnonactive', '0') // Ambil semua yang aktif
            ->orderBy('faccount') // <-- Perbaikan nama kolom
            ->get();

        // --- Sisa kode Anda ---
        $warehouses = DB::table('mswh')
            ->select('fwhid', 'fwhcode', 'fwhname', 'fbranchcode', 'fnonactive')
            ->where('fnonactive', '0') // hanya yang aktif
            ->orderBy('fwhcode')
            ->get();

        // (Query $returpembelian sudah dipindah ke atas)
        ['fcabang' => $fcabang, 'fbranchcode' => $fbranchcode] = $this->resolveBranchContext($returpembelian->fbranchcode ?? null);

        // 4. Map the data for savedItems
        $savedItems = $returpembelian->details->map(function ($d) {
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
                'fdiscpersen' => (float) ($d->fdiscpersen ?? 0),
                'fbiaya' => (float) ($d->fbiaya ?? 0),
                'ftotprice' => (float) ($d->ftotprice ?? 0),
                'ftotal' => (float) ($d->ftotprice ?? 0),
                'fdesc' => is_array($d->fdesc) ? implode(', ', $d->fdesc) : ($d->fdesc ?? ''),
                'fketdt' => $d->fketdt ?? '',
                'units' => [],
            ];
        })->values();

        $selectedSupplierCode = $returpembelian->fsupplier;

        $products = Product::select(
            'fprdid',
            'fprdcode',
            'fprdname',
            'fsatuankecil',
            'fsatuanbesar',
            'fsatuanbesar2',
            'fminstock'
        )
            ->whereRaw("COALESCE(TRIM(CAST(fnonactive AS TEXT)), '0') != '1'")
            ->orderBy('fprdname')
            ->get();

        $productMap = $products->mapWithKeys(function ($p) {
            return [
                $p->fprdcode => [
                    'name' => $p->fprdname,
                    'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
                    'stock' => $p->fminstock ?? 0,
                ],
            ];
        })->toArray();

        return view('returpembelian.edit', [
            'suppliers' => $suppliers,
            'selectedSupplierCode' => $selectedSupplierCode,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'warehouses' => $warehouses,
            'products' => $products,
            'supplierAdvanceWarnings' => $this->getSupplierAdvanceWarningMap(),
            'accounts' => $accounts,
            'productMap' => $productMap,
            'returpembelian' => $returpembelian,
            'displayFstockmtno' => $this->formatDisplayTransactionNumber($returpembelian->fstockmtno ?? null, (string) ($returpembelian->fapplyppn ?? '0') === '0' && (string) ($returpembelian->fincludeppn ?? '0') === '0'),
            'savedItems' => $savedItems,
            'ppnAmount' => (float) ($returpembelian->fppnpersen ?? 0),
            'famountponet' => (float) ($returpembelian->famountponet ?? 0),
            'famountpo' => (float) ($returpembelian->famountpo ?? 0),
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'defaultPpnTarif' => $this->getDefaultPpnTarif(),
            'isUsageLocked' => ! empty($usageLockMessage),
            'usageLockMessage' => $usageLockMessage,
            'action' => 'edit',
        ]);
    }

    public function view(Request $request, $fstockmtid)
    {
        $suppliers = Supplier::orderBy('fsuppliername', 'asc')
            ->get(['fsupplierid', 'fsuppliercode', 'fsuppliername']);

        // 1. PINDAHKAN INI KE ATAS
        // Ambil data Header (trstockmt) DULU
        $returpembelian = PenerimaanPembelianHeader::with([
            'details' => function ($query) {
                $query
                    ->leftJoin('msprd', function ($join) {
                        $join->on('msprd.fprdcode', '=', 'trstockdt.fprdcode');
                    })
                    ->select(
                        'trstockdt.*',
                        'msprd.fprdname',
                        'msprd.fprdcode as fitemcode_text'
                    )
                    ->orderBy('trstockdt.fstockdtid', 'asc');
            },
        ])
            ->findOrFail($fstockmtid); // Temukan header berdasarkan $fstockmtid

        // 2. Ambil kode akun yang tersimpan dari faktur
        $savedAccountCode = $returpembelian->fprdjadi;

        // 3. UBAH QUERY INI: Gunakan $savedAccountCode
        $accounts = DB::table('account')
            ->select('faccid', 'faccount', 'faccname', 'fnonactive')
            ->where('fnonactive', '0') // Ambil semua yang aktif
            ->orderBy('faccount') // <-- Perbaikan nama kolom
            ->get();

        // --- Sisa kode Anda ---
        $warehouses = DB::table('mswh')
            ->select('fwhid', 'fwhcode', 'fwhname', 'fbranchcode', 'fnonactive')
            ->where('fnonactive', '0') // hanya yang aktif
            ->orderBy('fwhcode')
            ->get();

        // (Query $returpembelian sudah dipindah ke atas)
        ['fcabang' => $fcabang, 'fbranchcode' => $fbranchcode] = $this->resolveBranchContext($returpembelian->fbranchcode ?? null);

        // 4. Map the data for savedItems
        $savedItems = $returpembelian->details->map(function ($d) {
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
                'fdiscpersen' => (float) ($d->fdiscpersen ?? 0),
                'fbiaya' => (float) ($d->fbiaya ?? 0),
                'ftotprice' => (float) ($d->ftotprice ?? 0),
                'ftotal' => (float) ($d->ftotprice ?? 0),
                'fdesc' => is_array($d->fdesc) ? implode(', ', $d->fdesc) : ($d->fdesc ?? ''),
                'fketdt' => $d->fketdt ?? '',
                'units' => [],
            ];
        })->values();

        $selectedSupplierCode = $returpembelian->fsupplier;

        $products = Product::select(
            'fprdid',
            'fprdcode',
            'fprdname',
            'fsatuankecil',
            'fsatuanbesar',
            'fsatuanbesar2',
            'fminstock'
        )
            ->whereRaw("COALESCE(TRIM(CAST(fnonactive AS TEXT)), '0') != '1'")
            ->orderBy('fprdname')
            ->get();

        $productMap = $products->mapWithKeys(function ($p) {
            return [
                $p->fprdcode => [
                    'name' => $p->fprdname,
                    'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
                    'stock' => $p->fminstock ?? 0,
                ],
            ];
        })->toArray();

        return view('returpembelian.view', [
            'suppliers' => $suppliers,
            'selectedSupplierCode' => $selectedSupplierCode,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'warehouses' => $warehouses,
            'products' => $products,
            'accounts' => $accounts,
            'productMap' => $productMap,
            'returpembelian' => $returpembelian,
            'displayFstockmtno' => $this->formatDisplayTransactionNumber($returpembelian->fstockmtno ?? null, (string) ($returpembelian->fapplyppn ?? '0') === '0' && (string) ($returpembelian->fincludeppn ?? '0') === '0'),
            'savedItems' => $savedItems,
            'ppnAmount' => (float) ($returpembelian->fppnpersen ?? 0),
            'famountponet' => (float) ($returpembelian->famountponet ?? 0),
            'famountpo' => (float) ($returpembelian->famountpo ?? 0),
            'filterSupplierId' => $request->query('filter_supplier_id'),
        ]);
    }

    public function update(Request $request, $fstockmtid)
    {
        try {
            $allowNegativeStockQty = stock_boleh_minus();
            // VALIDASI
            $request->validate([
                'fstockmtno' => ['nullable', 'string', 'max:100'],
                'fstockmtdate' => ['required', 'date'],
                'fsupplier' => ['required', 'string', 'max:30'],
                'ffrom' => ['required', 'string', 'max:10'],
                'fket' => ['nullable', 'string', 'max:50'],
                'fbranchcode' => ['nullable', 'string', 'max:20'],
                'fitemcode' => ['required', 'array', 'min:1'],
                'fitemcode.*' => ['required', 'string', 'max:50'],
                'fsatuan' => ['nullable', 'array'],
                'fsatuan.*' => ['nullable', 'string', 'max:20'],
                'frefdtno' => ['nullable', 'array'],
                'frefdtno.*' => ['nullable', 'string', 'max:20'],
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
                'frefno' => ['nullable', 'string'],
                'frefpo' => ['nullable', 'string'],
            ], [
                'ffrom.required' => 'Gudang wajib di isi.',
                'fstockmtdate.required' => 'Tanggal transaksi wajib diisi.',
                'fsupplier.required' => 'Supplier wajib diisi.',
                'fitemcode.required' => 'Minimal 1 item.',
                'fsatuan.*.max' => 'Satuan maksimal 5 karakter.',
                'faccid.required_if' => 'Account wajib dipilih.',
            ]);

            $this->ensureNoDuplicateDetailCodes($request->input('fitemcode', []));

            // 1. Muat header yang ada
            $header = PenerimaanPembelianHeader::findOrFail($fstockmtid);
            if ($message = $this->getPostedPeriodLockMessage($header->fstockmtdate, 'Retur Pembelian ini')) {
                if (request()->expectsJson()) {
                    return response()->json(['message' => $message], 422);
                }
                return redirect()->route('returpembelian.edit', $header->fstockmtid)->with('error', $message);
            }
            if ($message = $this->getUsageLockMessage($header)) {
                if (request()->expectsJson()) {
                    return response()->json(['message' => $message], 422);
                }
                return redirect()->route('returpembelian.index')->with('error', $message);
            }

            $userLogin = auth('sysuser')->user() ?? auth()->user();
            $userIdLog = $userLogin->fuserid ?? $userLogin->fsysuserid ?? 'admin';

            // HEADER FIELDS
            $fstockmtno = $header->fstockmtno;
            $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
            $this->ensureCreateDateWithinEditPeriod($fstockmtdate, $header->fstockmtdate);
            $fsupplier = trim((string) $request->input('fsupplier'));
            $ffrom = $request->input('ffrom');
            $fket = trim((string) $request->input('fket', ''));
            $fbranchcode = $request->input('fbranchcode');
            $frefno = $request->input('frefno');
            $frefpo = $request->input('frefpo');
            $userid = $userLogin->fsysuserid ?? 'admin';
            $now = now();

            // DETAIL ARRAYS
            $codes = $request->input('fitemcode', []);
            $satuans = $request->input('fsatuan', []);
            $refdtno = $request->input('frefdtno', []);
            $qtys = $request->input('fqty', []);
            $prices = $request->input('fprice', []);
            $descs = $request->input('fdesc', []);

            $subtotal = (float) $request->input('famount', 0);
            $ppnAmount = (float) $request->input('famountpajak', 0);
            $grandTotal = (float) $request->input('famountmt', 0);

            $fincludeppn = 0; // PPN Retur Pembelian selalu Exclude
            $defaultPpnTarif = $this->getDefaultPpnTarif();
            $fppnpersen = (float) $request->input('famountpopajak', $defaultPpnTarif);
            if ($fppnpersen <= 0 && $fincludeppn === 1) {
                $fppnpersen = $defaultPpnTarif;
            }

            // LOAD PRODUCT METADATA
            $uniqueCodes = array_values(array_unique(array_filter(array_map(fn($c) => trim((string) $c), $codes))));

            $prodMeta = DB::table('msprd')
                ->whereIn('fprdcode', $uniqueCodes)
                ->get(['fprdid', 'fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2', 'fqtykecil', 'fqtykecil2'])
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

            if ($validationMessage = $this->validateUniqueHeaderReference($frefno, $frefpo, $header->fstockmtno)) {
                return back()->withInput()->withErrors([
                    'detail' => $validationMessage,
                ]);
            }

            $typeBuy = (int) $request->input('ftypebuy', $header->ftypebuy ?? 0);

            // BUILD DETAIL ROWS
            $hasUM = collect($codes)->map(fn($c) => strtoupper(trim((string) $c)))->contains('UM');
            $hasNonUM = collect($codes)
                ->map(fn($c) => strtoupper(trim((string) $c)))
                ->filter(fn($c) => $c !== '' && $c !== 'UM')
                ->isNotEmpty();
            $outstandingDpDoc = $this->getSupplierOutstandingDpDocument($fsupplier);
            $outstandingDpRef = trim((string) ($outstandingDpDoc->fstockmtno ?? ''));
            $outstandingDpAmount = (float) ($outstandingDpDoc->fsisadp ?? 0);

            if ($typeBuy === 0 && $hasUM) {
                $msg = 'Tipe Pembelian tidak boleh menginput Uang Muka (UM).';
                if (request()->expectsJson()) {
                    return response()->json(['message' => $msg], 422);
                }
                return back()->withInput()->with('error', $msg);
            }

            if ($typeBuy !== 0 && $hasNonUM) {
                $msg = 'Tipe Uang Muka hanya boleh menginput Uang Muka (UM).';
                if (request()->expectsJson()) {
                    return response()->json(['message' => $msg], 422);
                }
                return back()->withInput()->with('error', $msg);
            }

            $rowsDt = [];
            $usedNoAcaks = [];
            $subtotal = 0.0;
            $rowCount = count($codes);

            for ($i = 0; $i < $rowCount; $i++) {
                $code = trim((string) ($codes[$i] ?? ''));
                $sat = trim((string) ($satuans[$i] ?? ''));
                $rref = trim((string) ($refdtno[$i] ?? ''));
                $qty = (float) ($qtys[$i] ?? 0);
                $price = (float) ($prices[$i] ?? 0);
                $desc = (string) ($descs[$i] ?? '');

                if ($code === '' || $qty <= 0) {
                    continue;
                }

                if (strtoupper(trim((string) $code)) === 'UM') {
                    $rref = $outstandingDpRef !== '' ? $outstandingDpRef : $rref;
                    $absPrice = $outstandingDpAmount > 0 ? $outstandingDpAmount : abs($price);
                    $price = $hasNonUM ? -$absPrice : $absPrice;
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

                $qtyKecil = $qty;
                if ($sat === trim((string) ($meta->fsatuanbesar ?? '')) && (float) ($meta->fqtykecil ?? 0) > 0) {
                    $qtyKecil = $qty * (float) $meta->fqtykecil;
                } elseif ($sat === trim((string) ($meta->fsatuanbesar2 ?? '')) && (float) ($meta->fqtykecil2 ?? 0) > 0) {
                    $qtyKecil = $qty * (float) $meta->fqtykecil2;
                }

                $priceGross = $price;
                $amount = $qty * $priceGross;
                $subtotal += $amount;

                $rowsDt[] = [
                    'fprdcode' => $code,
                    'fnoacak' => $this->normalizeRandomNumber(null, $usedNoAcaks),
                    'frefdtno' => $rref,
                    'fqty' => $qty,
                    'fprice' => $price,
                    'ftotprice' => $amount,
                    'fuserupdate' => (Auth::user()->fname ?? 'system'),
                    'fdatetime' => $now,
                    'fketdt' => '',
                    'fcode' => '0',
                    'frefso' => null,
                    'fdesc' => $desc,
                    'fsatuan' => $sat,
                    'fclosedt' => '0',
                    'fqtykecil' => $qtyKecil,
                    'fqtyremain' => $qtyKecil,
                ];
            }

            if (empty($rowsDt)) {
                return back()->withInput()->withErrors([
                    'detail' => 'Minimal 1 item valid (Kode, Satuan, Qty > 0).',
                ]);
            }

            if ($stockResponse = $this->validateStockMinusLines(
                $this->buildStockMinusLinesForOutChange($rowsDt, (string) $ffrom, $this->fetchStockDetailRows((string) $header->fstockmtno), (string) $header->ffrom),
                $request->boolean('force_save')
            )) {
                return $stockResponse;
            }

            // Hitung ulang grand total berdasarkan data yang valid
            $grandTotal = $subtotal + $ppnAmount;

            // DATABASE TRANSACTION
            DB::transaction(function () use (
                $typeBuy,
                $header,
                $fstockmtdate,
                $fsupplier,
                $ffrom,
                $fket,
                $fbranchcode,
                $now,
                $frefno,
                $frefpo,
                &$fstockmtno,
                &$rowsDt,
                $subtotal,
                $ppnAmount,
                $grandTotal,
                $userid,
                $userIdLog,
                $fincludeppn,
                $fppnpersen
            ) {
                $trxLogId = 'LOG' . $now->format('YmdHis') . rand(100, 999);

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

                $fstockmtcode = 'REB';

                if (empty($fstockmtno)) {
                    $fstockmtno = $header->fstockmtno;
                }

                // 1. UPDATE HEADER
                $masterData = [
                    'fstockmtno' => $fstockmtno,
                    'fstockmtcode' => $fstockmtcode,
                    'fstockmtdate' => $fstockmtdate,
                    'fprdout' => '0',
                    'fsupplier' => $fsupplier,
                    'famount' => round($subtotal, 2),
                    'famountmt' => round($grandTotal, 2),
                    'frefno' => $frefno,
                    'frefpo' => $frefpo,
                    'ftrancode' => null,
                    'ffrom' => $ffrom,
                    'fto' => null,
                    'fkirim' => null,
                    'fqtyjadi' => null,
                    'fket' => $fket,
                    'fuserupdate' => (Auth::user()->fname ?? 'system'),
                    'fdatetime' => $now,
                    'fsalesman' => null,
                    'fprint' => 0,
                    'fsudahtagih' => '0',
                    'fbranchcode' => $kodeCabang,
                    'fdiscount' => 0,
                    'fincludeppn' => $fincludeppn,
                    'ftypebuy' => $typeBuy,
                    'fppnpersen' => $fppnpersen,
                    'famountpajak' => round($ppnAmount, 2),
                    'famountpajak_rp' => round($ppnAmount, 2),
                ];

                $header->update($masterData);

                $updatedHeader = PenerimaanPembelianHeader::findOrFail($header->fstockmtid);

                // 2. INSERT Log Header (Update)
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
                    'fdiscount'        => $updatedHeader->fdiscount,
                    'fupdatedat'       => $updatedHeader->fupdatedat,
                    'famount'          => $updatedHeader->famount,
                    'famount_rp'       => $updatedHeader->famount_rp,
                    'famountpajak'     => $updatedHeader->famountpajak,
                    'famountpajak_rp'  => $updatedHeader->famountpajak_rp,
                    'famountmt'        => $updatedHeader->famountmt,
                    'famountmt_rp'     => $updatedHeader->famountmt_rp,
                    'famountremain'    => $updatedHeader->famountremain,
                    'famountremain_rp' => $updatedHeader->famountremain_rp,
                    'frefno'           => $updatedHeader->frefno,
                    'frefpo'           => $updatedHeader->frefpo,
                    'ffrom'            => $updatedHeader->ffrom,
                    'fto'              => $updatedHeader->fto,
                    'fkirim'           => $updatedHeader->fkirim,
                    'fprdjadi'         => $updatedHeader->fprdjadi,
                    'fqtyjadi'         => $updatedHeader->fqtyjadi,
                    'fket'             => $updatedHeader->fket,
                    'fincludeppn'      => $updatedHeader->fincludeppn,
                    'fppnpersen'       => $updatedHeader->fppnpersen,
                    'fapplyppn'        => $updatedHeader->fapplyppn,
                    'fketinternal'     => $updatedHeader->fketinternal,
                    'fusercreate'      => $updatedHeader->fusercreate,
                    'fdatetime'        => $updatedHeader->fdatetime,
                    'fuserupdate'      => $updatedHeader->fuserupdate,
                    'feditmode'        => 'U',
                    'fuseridlog'       => $userIdLog,
                    'fdatetimelog'     => $now,
                ]);

                $header->details()->delete();

                foreach ($rowsDt as &$r) {
                    $r['fstockmtcode'] = $fstockmtcode;
                    $r['fstockmtno'] = $fstockmtno;

                    $insertedDtId = DB::table('trstockdt')->insertGetId($r, 'fstockdtid');
                    $dtObj = DB::table('trstockdt')->where('fstockdtid', $insertedDtId)->first();

                    // 3. INSERT Log Detail (Update)
                    DB::table('log_trstockdt')->insert([
                        'ftrxlogid'     => $trxLogId,
                        'fstockdtid'    => $dtObj->fstockdtid,
                        'fstockmtcode'  => $dtObj->fstockmtcode,
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

                $this->syncReturPembelianJournalEntries(
                    (string) $fstockmtno,
                    $fstockmtdate,
                    (string) $kodeCabang,
                    (string) $fsupplier,
                    (float) $subtotal,
                    (float) $ppnAmount,
                    (float) $grandTotal,
                    (string) $userid
                );
            });

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => "Retur pembelian {$fstockmtno} berhasil diupdate.",
                    'redirect_url' => route('returpembelian.index'),
                ]);
            }

            return redirect()
                ->route('returpembelian.index')
                ->with('success', "Retur pembelian {$fstockmtno} berhasil diupdate.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();

            if (request()->expectsJson()) {
                return response()->json(['message' => $firstError ?: 'Gagal update retur pembelian.'], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('error', $firstError ?: 'Retur pembelian belum bisa diupdate. Cek data.');
        } catch (\Throwable $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Retur pembelian belum bisa diupdate: ' . $e->getMessage(),
                ], 500);
            }
            return back()
                ->withInput()
                ->with('error', 'Retur pembelian belum bisa diupdate: ' . $e->getMessage());
        }
    }

    public function delete(Request $request, $fstockmtid)
    {
        $suppliers = Supplier::orderBy('fsuppliername', 'asc')
            ->get(['fsupplierid', 'fsuppliercode', 'fsuppliername']);

        // 1. PINDAHKAN INI KE ATAS
        // Ambil data Header (trstockmt) DULU
        $returpembelian = PenerimaanPembelianHeader::with([
            'details' => function ($query) {
                $query
                    ->leftJoin('msprd', function ($join) {
                        $join->on('msprd.fprdcode', '=', 'trstockdt.fprdcode');
                    })
                    ->select(
                        'trstockdt.*',
                        'msprd.fprdname',
                        'msprd.fprdcode as fitemcode_text'
                    )
                    ->orderBy('trstockdt.fstockdtid', 'asc');
            },
        ])
            ->findOrFail($fstockmtid); // Temukan header berdasarkan $fstockmtid

        if ($message = $this->getPostedPeriodLockMessage($returpembelian->fstockmtdate, 'Retur Pembelian ini')) {
            return redirect()
                ->route('returpembelian.edit', $returpembelian->fstockmtid)
                ->with('error', $message);
        }

        $usageLockMessage = $this->getUsageLockMessage($returpembelian);

        if (! empty($usageLockMessage)) {
            return redirect()
                ->route('returpembelian.edit', $returpembelian->fstockmtid)
                ->with('error', $usageLockMessage);
        }

        // 2. Ambil kode akun yang tersimpan dari faktur
        $savedAccountCode = $returpembelian->fprdjadi;

        // 3. UBAH QUERY INI: Gunakan $savedAccountCode
        $accounts = DB::table('account')
            ->select('faccid', 'faccount', 'faccname', 'fnonactive')
            ->where('fnonactive', '0') // Ambil semua yang aktif
            ->orderBy('faccount') // <-- Perbaikan nama kolom
            ->get();

        // --- Sisa kode Anda ---
        $warehouses = DB::table('mswh')
            ->select('fwhid', 'fwhcode', 'fwhname', 'fbranchcode', 'fnonactive')
            ->where('fnonactive', '0') // hanya yang aktif
            ->orderBy('fwhcode')
            ->get();

        // (Query $returpembelian sudah dipindah ke atas)
        ['fcabang' => $fcabang, 'fbranchcode' => $fbranchcode] = $this->resolveBranchContext($returpembelian->fbranchcode ?? null);

        // 4. Map the data for savedItems
        $savedItems = $returpembelian->details->map(function ($d) {
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
                'fdiscpersen' => (float) ($d->fdiscpersen ?? 0),
                'fbiaya' => (float) ($d->fbiaya ?? 0),
                'ftotprice' => (float) ($d->ftotprice ?? 0),
                'ftotal' => (float) ($d->ftotprice ?? 0),
                'fdesc' => is_array($d->fdesc) ? implode(', ', $d->fdesc) : ($d->fdesc ?? ''),
                'fketdt' => $d->fketdt ?? '',
                'units' => [],
            ];
        })->values();

        $selectedSupplierCode = $returpembelian->fsupplier;

        $products = Product::select(
            'fprdid',
            'fprdcode',
            'fprdname',
            'fsatuankecil',
            'fsatuanbesar',
            'fsatuanbesar2',
            'fminstock'
        )
            ->whereRaw("COALESCE(TRIM(CAST(fnonactive AS TEXT)), '0') != '1'")
            ->orderBy('fprdname')
            ->get();

        $productMap = $products->mapWithKeys(function ($p) {
            return [
                $p->fprdcode => [
                    'name' => $p->fprdname,
                    'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
                    'stock' => $p->fminstock ?? 0,
                ],
            ];
        })->toArray();

        return view('returpembelian.edit', [
            'suppliers' => $suppliers,
            'selectedSupplierCode' => $selectedSupplierCode,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'warehouses' => $warehouses,
            'products' => $products,
            'accounts' => $accounts,
            'productMap' => $productMap,
            'returpembelian' => $returpembelian,
            'displayFstockmtno' => $this->formatDisplayTransactionNumber($returpembelian->fstockmtno ?? null, (string) ($returpembelian->fapplyppn ?? '0') === '0' && (string) ($returpembelian->fincludeppn ?? '0') === '0'),
            'savedItems' => $savedItems,
            'ppnAmount' => (float) ($returpembelian->fppnpersen ?? 0),
            'famountponet' => (float) ($returpembelian->famountponet ?? 0),
            'famountpo' => (float) ($returpembelian->famountpo ?? 0),
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'isUsageLocked' => ! empty($usageLockMessage),
            'usageLockMessage' => $usageLockMessage,
            'action' => 'delete',
        ]);
    }

    public function destroy($fstockmtid)
    {
        try {
            $returpembelian = PenerimaanPembelianHeader::findOrFail($fstockmtid);
            if ($message = $this->getPostedPeriodLockMessage($returpembelian->fstockmtdate, 'Retur Pembelian ini')) {
                return redirect()->route('returpembelian.edit', $returpembelian->fstockmtid)->with('error', $message);
            }
            if ($message = $this->getUsageLockMessage($returpembelian)) {
                return redirect()->route('returpembelian.index')->with('error', $message);
            }

            $userLogin = auth('sysuser')->user() ?? auth()->user();
            $userIdLog = $userLogin->fuserid ?? $userLogin->fsysuserid ?? 'admin';

            DB::transaction(function () use ($returpembelian, $userIdLog) {
                $now = now();
                $trxLogId = 'LOG' . $now->format('YmdHis') . rand(100, 999);

                // 1. INSERT Log Header (Delete)
                DB::table('log_trstockmt')->insert([
                    'ftrxlogid'        => $trxLogId,
                    'fstockmtid'       => $returpembelian->fstockmtid,
                    'fstockmtno'       => $returpembelian->fstockmtno,
                    'fbranchcode'      => $returpembelian->fbranchcode,
                    'fstockmtcode'     => $returpembelian->fstockmtcode,
                    'fstockmtdate'     => $returpembelian->fstockmtdate,
                    'fprdout'          => $returpembelian->fprdout,
                    'fsupplier'        => $returpembelian->fsupplier,
                    'fcurrency'        => $returpembelian->fcurrency,
                    'frate'            => $returpembelian->frate,
                    'ftypebuy'         => $returpembelian->ftypebuy,
                    'ftempohr'         => $returpembelian->ftempohr,
                    'ftrancode'        => $returpembelian->ftrancode,
                    'fsalesman'        => $returpembelian->fsalesman,
                    'fjatuhtempo'      => $returpembelian->fjatuhtempo,
                    'fprint'           => $returpembelian->fprint,
                    'fsudahtagih'      => $returpembelian->fsudahtagih,
                    'fdiscount'        => $returpembelian->fdiscount,
                    'fupdatedat'       => $returpembelian->fupdatedat,
                    'famount'          => $returpembelian->famount,
                    'famount_rp'       => $returpembelian->famount_rp,
                    'famountpajak'     => $returpembelian->famountpajak,
                    'famountpajak_rp'  => $returpembelian->famountpajak_rp,
                    'famountmt'        => $returpembelian->famountmt,
                    'famountmt_rp'     => $returpembelian->famountmt_rp,
                    'famountremain'    => $returpembelian->famountremain,
                    'famountremain_rp' => $returpembelian->famountremain_rp,
                    'frefno'           => $returpembelian->frefno,
                    'frefpo'           => $returpembelian->frefpo,
                    'ffrom'            => $returpembelian->ffrom,
                    'fto'              => $returpembelian->fto,
                    'fkirim'           => $returpembelian->fkirim,
                    'fprdjadi'         => $returpembelian->fprdjadi,
                    'fqtyjadi'         => $returpembelian->fqtyjadi,
                    'fket'             => $returpembelian->fket,
                    'fincludeppn'      => $returpembelian->fincludeppn,
                    'fppnpersen'       => $returpembelian->fppnpersen,
                    'fapplyppn'        => $returpembelian->fapplyppn,
                    'fketinternal'     => $returpembelian->fketinternal,
                    'fusercreate'      => $returpembelian->fusercreate,
                    'fdatetime'        => $returpembelian->fdatetime,
                    'fuserupdate'      => $returpembelian->fuserupdate,
                    'feditmode'        => 'D',
                    'fuseridlog'       => $userIdLog,
                    'fdatetimelog'     => $now,
                ]);

                // 2. Ambil seluruh detail lalu catat ke log_trstockdt (Delete)
                $details = DB::table('trstockdt')->where('fstockmtno', $returpembelian->fstockmtno)->get();
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

                DB::table('trstockdt')
                    ->where('fstockmtno', $returpembelian->fstockmtno)
                    ->delete();

                $this->deleteReturPembelianJournalEntries($returpembelian->fstockmtno);

                $returpembelian->delete();
            });

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Retur pembelian berhasil dihapus.',
                    'redirect_url' => route('returpembelian.index'),
                ]);
            }

            return redirect()->route('returpembelian.index')->with('success', 'Retur pembelian berhasil dihapus.');
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Retur pembelian belum bisa dihapus. Coba lagi: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->route('returpembelian.delete', $fstockmtid)->with('error', 'Retur pembelian belum bisa dihapus. Coba lagi.');
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

        return 'Retur pembelian ' . (string) $header->fstockmtno . ' sudah dipakai: ' . $usedBy->implode(', ') . '.';
    }

    private function validateUniqueHeaderReference($frefno, $frefpo, ?string $exceptStockMtNo = null): ?string
    {
        $references = collect([$frefno, $frefpo])
            ->map(fn($value) => trim((string) ($value ?? '')))
            ->filter(fn($value) => $value !== '')
            ->unique()
            ->values();

        if ($references->isEmpty()) {
            return null;
        }

        foreach ($references as $referenceNo) {
            $query = DB::table('trstockmt')
                ->where('fstockmtcode', 'REB')
                ->where(function ($inner) use ($referenceNo) {
                    $inner->whereRaw('TRIM(COALESCE(frefno, \'\')) = ?', [$referenceNo])
                        ->orWhereRaw('TRIM(COALESCE(frefpo, \'\')) = ?', [$referenceNo]);
                });

            if (! empty($exceptStockMtNo)) {
                $query->where('fstockmtno', '<>', $exceptStockMtNo);
            }

            $existing = $query
                ->orderBy('fstockmtno')
                ->select('fstockmtno')
                ->first();

            if ($existing) {
                return 'No. referensi ' . strtoupper((string) $referenceNo) . ' sudah ada di transaksi ' . strtoupper(trim((string) ($existing->fstockmtno ?? ''))) . '.';
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

    private function syncReturPembelianJournalEntries(
        string $fstockmtno,
        Carbon $fstockmtdate,
        string $kodeCabang,
        string $fsupplier,
        float $subtotal,
        float $ppnAmount,
        float $grandTotal,
        string $userid
    ): void {
        $this->deleteReturPembelianJournalEntries($fstockmtno);

        // --- Lookup accounts from set_account table ---
        $setAccounts = DB::table('set_account')
            ->whereIn('faccount_name', ['RETBELIBLMPOTHUTANG', 'PPNBELI', 'RETURPEMBELIAN', 'RETURUANGMUKA', 'UANGMUKAPEMBELIAN'])
            ->pluck('faccount', 'faccount_name');

        $accountHutang      = $setAccounts->get('RETBELIBLMPOTHUTANG');
        $accountPPNBeli     = $setAccounts->get('PPNBELI');
        $accountPersediaan  = $setAccounts->get('RETURPEMBELIAN');
        $accountReturnUM    = $setAccounts->get('RETURUANGMUKA') ?: $setAccounts->get('UANGMUKAPEMBELIAN');

        $isUangMuka = (int) ($returPembelian->ftypebuy ?? 0) !== 0;
        $targetKreditAccount = $isUangMuka ? ($accountReturnUM ?: $accountPersediaan) : $accountPersediaan;
        $accountNote = $isUangMuka ? 'Retur Uang Muka' : 'Kurangi Persediaan Barang';

        $fjurnaltype  = 'REB';
        $hasPpn = (string) ($returPembelian->fapplyppn ?? '0') === '1' || (string) ($returPembelian->fincludeppn ?? '0') === '1';
        $sep = $hasPpn ? '.' : '/';
        $jurnalPrefix = sprintf('JV%s%s%s%s%s%s%s', $sep, $fjurnaltype, $sep, $kodeCabang, $sep, $fstockmtdate->format('y') . $fstockmtdate->format('m'), $sep);

        if (DB::getDriverName() === 'pgsql') {
            $lockKey = crc32('JURNAL|' . $fjurnaltype . '|' . $kodeCabang . '|' . $fstockmtdate->format('y-m'));
            DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);
            $lastJ = DB::table('jurnalmt')->where('fjurnalno', 'like', $jurnalPrefix . '%')
                ->selectRaw("MAX(CAST(SUBSTRING(fjurnalno FROM '([0-9]+)$') AS integer)) AS lastno")->value('lastno');
            $nextJ = (int) $lastJ + 1;
        } else {
            $lastJurnalNo = DB::table('jurnalmt')
                ->where('fjurnalno', 'like', $jurnalPrefix . '%')
                ->orderByDesc('fjurnalno')
                ->value('fjurnalno');

            $nextJ = 1;
            if ($lastJurnalNo && ($pos = strrpos($lastJurnalNo, '.')) !== false) {
                $nextJ = ((int) substr($lastJurnalNo, $pos + 1)) + 1;
            }
        }

        $fjurnalno = $jurnalPrefix . str_pad((string) $nextJ, 4, '0', STR_PAD_LEFT);
        $now       = now();

        $jurnalId = DB::table('jurnalmt')->insertGetId([
            'fbranchcode' => $kodeCabang,
            'fjurnalno'   => $fjurnalno,
            'fjurnaltype' => $fjurnaltype,
            'fjurnaldate' => $fstockmtdate,
            'fjurnalnote' => "Retur Pembelian $fstockmtno dari $fsupplier",
            'fbalance'    => round($grandTotal, 2),
            'fbalance_rp' => round($grandTotal, 2),
            'fdatetime'   => $now,
            'fuserid'     => $userid,
        ], 'fjurnalmtid');

        // Line 1: Debit – Kurangi Hutang Supplier / Piutang Sementara (from set_account: ReturPembelianBlmPotHutang)
        $jurnalDt = [
            [
                'fjurnalmtid'  => $jurnalId,
                'fbranchcode'  => $kodeCabang,
                'fjurnaltype'  => $fjurnaltype,
                'fjurnalno'    => $fjurnalno,
                'flineno'      => 1,
                'faccount'     => (string) $accountHutang,
                'fdk'          => 'D',
                'fsubaccount'  => $fsupplier,
                'frefno'       => $fstockmtno,
                'frate'        => 1.0,
                'famount'      => round($grandTotal, 2),
                'famount_rp'   => round($grandTotal, 2),
                'faccountnote' => 'Retur Pembelian Blm Pot Hutang',
                'fusercreate'  => $userid,
                'fdatetime'    => $now,
            ],
            // Line 2 or 3: Kredit – Kurangi Persediaan Barang / Retur Uang Muka (from set_account)
            [
                'fjurnalmtid'  => $jurnalId,
                'fbranchcode'  => $kodeCabang,
                'fjurnaltype'  => $fjurnaltype,
                'fjurnalno'    => $fjurnalno,
                'flineno'      => ($ppnAmount > 0 ? 3 : 2),
                'faccount'     => (string) $targetKreditAccount,
                'fdk'          => 'K',
                'fsubaccount'  => $fsupplier,
                'frefno'       => $fstockmtno,
                'frate'        => 1.0,
                'famount'      => round($subtotal, 2),
                'famount_rp'   => round($subtotal, 2),
                'faccountnote' => $accountNote,
                'fusercreate'  => $userid,
                'fdatetime'    => $now,
            ],
        ];

        // Line 2: Kredit – Reverse PPN Masukan (only if tax > 0, from set_account: PPNBeli)
        if ($ppnAmount > 0) {
            $jurnalDt[] = [
                'fjurnalmtid'  => $jurnalId,
                'fbranchcode'  => $kodeCabang,
                'fjurnaltype'  => $fjurnaltype,
                'fjurnalno'    => $fjurnalno,
                'flineno'      => 2,
                'faccount'     => (string) $accountPPNBeli,
                'fdk'          => 'K',
                'fsubaccount'  => null,
                'frefno'       => $fstockmtno,
                'frate'        => 1.0,
                'famount'      => round($ppnAmount, 2),
                'famount_rp'   => round($ppnAmount, 2),
                'faccountnote' => 'Reverse PPN Masukan',
                'fusercreate'  => $userid,
                'fdatetime'    => $now,
            ];
        }

        DB::table('jurnaldt')->insert($jurnalDt);
    }

    private function deleteReturPembelianJournalEntries(string $fstockmtno): void
    {
        $jurnalIds = DB::table('jurnaldt')
            ->where('frefno', $fstockmtno)
            ->where('fjurnaltype', 'REB')
            ->pluck('fjurnalmtid')
            ->filter(fn($id) => ! is_null($id))
            ->unique()
            ->values();

        if ($jurnalIds->isEmpty()) {
            return;
        }

        DB::table('jurnaldt')->whereIn('fjurnalmtid', $jurnalIds->all())->delete();
        DB::table('jurnalmt')->whereIn('fjurnalmtid', $jurnalIds->all())->delete();
    }
}
