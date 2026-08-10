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
// Pastikan ini ada jika menggunakan throw new \Exception
use Illuminate\Support\Facades\DB; // sekalian biar aman untuk tanggal

class JurnalTransaksiController extends Controller
{
    private const DAILY_CREATE_LIMIT = 15;

    private function todayCreateCount(): int
    {
        return DB::table('jurnalmt')
            ->whereBetween('fdatetime', [now()->startOfDay(), now()->endOfDay()])
            ->count();
    }

    private function hasReachedDailyCreateLimit(): bool
    {
        return $this->todayCreateCount() >= self::DAILY_CREATE_LIMIT;
    }

    private const GENERAL_JOURNAL_TYPE = 'SJU';
    private const PURCHASE_JOURNAL_TYPE = 'JBL';
    private const SALES_JOURNAL_TYPE = 'SLS';
    private const REFERENCE_ALLOWED_ACCOUNT_NAMES = [
        'HUTANGDAGANG',
        'PIUTANGDAGANG',
        'RETJUALBLMPOTPIUTANG',
        'RETBELIBLMPOTHUTANG',
    ];
    private const REFERENCE_SOURCE_ACCOUNT_NAMES = [
        'purchase' => ['HUTANGDAGANG'],
        'sales' => ['PIUTANGDAGANG'],
        'sales_return' => ['RETJUALBLMPOTPIUTANG'],
        'purchase_return' => ['RETBELIBLMPOTHUTANG'],
    ];

    private function normalizeDecimal($value, int $scale = 2): float
    {
        if (is_numeric($value)) {
            return round((float) $value, $scale);
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return 0.0;
        }

        $normalized = preg_replace('/\s+/', '', $normalized);

        $commaPos = strrpos($normalized, ',');
        $dotPos = strrpos($normalized, '.');

        if ($commaPos !== false && $dotPos !== false) {
            if ($commaPos > $dotPos) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif ($commaPos !== false) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } else {
            $normalized = str_replace(',', '', $normalized);
        }

        $normalized = preg_replace('/[^0-9.\-]/', '', $normalized);

        if ($normalized === '' || $normalized === '-' || $normalized === '.') {
            return 0.0;
        }

        return round((float) $normalized, $scale);
    }

    private function resolveReferenceAllowedAccountCodes(): array
    {
        return DB::table('set_account')
            ->whereIn('faccount_name', self::REFERENCE_ALLOWED_ACCOUNT_NAMES)
            ->pluck('faccount')
            ->filter()
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->values()
            ->all();
    }

    private function resolveReferenceSourceAccountCodes(): array
    {
        $rows = DB::table('set_account')
            ->whereIn('faccount_name', collect(self::REFERENCE_SOURCE_ACCOUNT_NAMES)->flatten()->all())
            ->get(['faccount_name', 'faccount']);

        return collect(self::REFERENCE_SOURCE_ACCOUNT_NAMES)->mapWithKeys(function ($names, $source) use ($rows) {
            $codes = $rows
                ->whereIn('faccount_name', $names)
                ->pluck('faccount')
                ->filter()
                ->map(fn ($value) => strtoupper(trim((string) $value)))
                ->filter(fn ($value) => $value !== '')
                ->values()
                ->all();

            return [$source => $codes];
        })->all();
    }

    public function referenceBrowse(Request $request)
    {
        $source = trim((string) $request->input('source', ''));
        $search = trim((string) $request->input('search', ''));
        $limit = min(max((int) $request->input('limit', 20), 1), 50);

        if (! in_array($source, ['purchase', 'sales', 'sales_return', 'purchase_return'], true)) {
            return response()->json(['data' => []]);
        }

        if (in_array($source, ['purchase', 'purchase_return'], true)) {
            $query = DB::table('trstockmt as m')
                ->leftJoin('mssupplier as s', 's.fsuppliercode', '=', 'm.fsupplier')
                ->where('m.fstockmtcode', $source === 'purchase' ? 'BUY' : 'REB')
                ->whereRaw('ABS(COALESCE(m.famountremain, 0)) > 0')
                ->selectRaw("m.fstockmtno AS ref_no, m.fstockmtdate AS ref_date, m.fsupplier AS party_code, COALESCE(s.fsuppliername, m.fsupplier) AS party_name, m.famountremain AS amount_remain");

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('m.fstockmtno', 'ilike', "%{$search}%")
                        ->orWhere('m.fsupplier', 'ilike', "%{$search}%")
                        ->orWhere('s.fsuppliername', 'ilike', "%{$search}%");
                });
            }

            $rows = $query->orderByDesc('m.fstockmtdate')->orderBy('m.fstockmtno')->limit($limit)->get();
        } else {
            $query = DB::table('tranmt as m')
                ->leftJoin('mscustomer as c', 'c.fcustomercode', '=', 'm.fcustno')
                ->where('m.ftrcode', $source === 'sales' ? 'INV' : 'REJ')
                ->whereRaw('ABS(COALESCE(m.famountremain, 0)) > 0')
                ->selectRaw("m.fsono AS ref_no, m.fsodate AS ref_date, m.fcustno AS party_code, COALESCE(c.fcustomername, m.fcustno) AS party_name, m.famountremain AS amount_remain");

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('m.fsono', 'ilike', "%{$search}%")
                        ->orWhere('m.fcustno', 'ilike', "%{$search}%")
                        ->orWhere('c.fcustomername', 'ilike', "%{$search}%");
                });
            }

            $rows = $query->orderByDesc('m.fsodate')->orderBy('m.fsono')->limit($limit)->get();
        }

        return response()->json([
            'data' => $rows->map(fn ($row) => [
                'ref_no' => trim((string) ($row->ref_no ?? '')),
                'ref_date' => ! empty($row->ref_date) ? Carbon::parse($row->ref_date)->format('Y-m-d') : '',
                'party_code' => trim((string) ($row->party_code ?? '')),
                'party_name' => trim((string) ($row->party_name ?? '')),
                'amount_remain' => (float) ($row->amount_remain ?? 0),
            ])->values(),
        ]);
    }

    private function resolveJournalPageMeta(?string $journalType = null): array
    {
        $type = strtoupper(trim((string) $journalType));
        $isPurchaseJournal = $type === self::PURCHASE_JOURNAL_TYPE;
        $isSalesJournal = $type === self::SALES_JOURNAL_TYPE;

        return [
            'journalType' => $type,
            'isPurchaseJournal' => $isPurchaseJournal,
            'isSalesJournal' => $isSalesJournal,
            'pageTitle' => $this->resolveJournalTitle($type),
        ];
    }

    private function resolveJournalTitle(?string $journalType = null, string $action = ''): string
    {
        $type = strtoupper(trim((string) $journalType));
        $baseTitle = match ($type) {
            self::PURCHASE_JOURNAL_TYPE => 'Jurnal Faktur Pembelian',
            self::SALES_JOURNAL_TYPE => 'Jurnal Penjualan',
            default => 'Jurnal Transaksi',
        };

        $actionStr = trim($action);
        if ($actionStr !== '') {
            return $baseTitle . ' - ' . $actionStr;
        }

        return $baseTitle;
    }

    private function resolveJournalSuccessName(?string $journalType = null): string
    {
        return match (strtoupper(trim((string) $journalType))) {
            self::PURCHASE_JOURNAL_TYPE => 'jurnal faktur pembelian',
            self::SALES_JOURNAL_TYPE => 'jurnal penjualan',
            default => 'jurnal transaksi',
        };
    }

    private function resolveJournalIndexRouteParams(?string $journalType = null): array
    {
        $type = strtoupper(trim((string) $journalType));

        return in_array($type, [self::PURCHASE_JOURNAL_TYPE, self::SALES_JOURNAL_TYPE], true)
            ? ['journal_type' => $type]
            : [];
    }

    private function getJournalTypes()
    {
        $defaults = collect([
            (object) ['fmastercode' => self::GENERAL_JOURNAL_TYPE, 'fmastername' => 'Jurnal Umum'],
            (object) ['fmastercode' => self::PURCHASE_JOURNAL_TYPE, 'fmastername' => 'Jurnal Pembelian'],
            (object) ['fmastercode' => self::SALES_JOURNAL_TYPE, 'fmastername' => 'Jurnal Penjualan'],
        ]);

        $configured = DB::table('tbmaster')
            ->whereRaw('TRIM(ftblcode) = ?', ['JURNAL'])
            ->orderBy('fmasternum', 'asc')
            ->get()
            ->map(function ($item) {
                $item->fmastercode = trim($item->fmastercode);
                $item->fmastername = trim($item->fmastername);
                return $item;
            });

        return $defaults
            ->merge($configured)
            ->unique('fmastercode')
            ->values();
    }

    public function index(Request $request)
    {
        $canCreate = in_array('createjurnaltransaksi', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updatejurnaltransaksi', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deletejurnaltransaksi', explode(',', session('user_restricted_permissions', '')));
        $showActionsColumn = $canEdit || $canDelete;
        $year = trim((string) $request->query('year', ''));
        $month = trim((string) $request->query('month', ''));
        $journalType = strtoupper(trim((string) $request->query('journal_type', self::GENERAL_JOURNAL_TYPE)));
        $pageMeta = $this->resolveJournalPageMeta($journalType);
        $createLimitReached = $this->hasReachedDailyCreateLimit();

        $availableYearsQuery = DB::table('jurnalmt')
            ->when($journalType !== '', fn ($query) => $query->where('fjurnaltype', $journalType))
            ->whereNotNull('fjurnaldate')
            ->selectRaw('DISTINCT EXTRACT(YEAR FROM fjurnaldate) as year');
        $this->applyBranchVisibilityScope($availableYearsQuery, 'jurnalmt.fbranchcode');
        $availableYears = $availableYearsQuery
            ->orderByRaw('EXTRACT(YEAR FROM fjurnaldate) DESC')
            ->pluck('year');

        if ($request->ajax()) {
            $query = DB::table('jurnalmt');
            $this->applyBranchVisibilityScope($query, 'jurnalmt.fbranchcode');

            if ($journalType !== '') {
                $query->where('fjurnaltype', $journalType);
            }

            $totalRecords = (clone $query)->count();

            if ($search = trim((string) $request->input('search.value', ''))) {
                $query->where(function ($q) use ($search) {
                    $q->where('fjurnalno', 'like', "%{$search}%")
                        ->orWhere('fjurnalnote', 'like', "%{$search}%");
                });
            }

            if ($year !== '') {
                $query->whereRaw('EXTRACT(YEAR FROM fjurnaldate) = ?', [$year]);
            }

            if ($month !== '') {
                $query->whereRaw('EXTRACT(MONTH FROM fjurnaldate) = ?', [$month]);
            }

            $filteredRecords = (clone $query)->count();

            $orderColIdx = $request->input('order.0.column');
            $orderDir = $request->input('order.0.dir', 'desc');

            $orderColumn = null;
            if ($orderColIdx !== null) {
                $colName = $request->input("columns.{$orderColIdx}.name") ?: $request->input("columns.{$orderColIdx}.data");
                if ($colName === 'fbranchcode') {
                    $orderColumn = 'fbranchcode';
                } elseif ($colName === 'fjurnalno') {
                    $orderColumn = 'fjurnalno';
                } elseif ($colName === 'fjurnaldate') {
                    $orderColumn = 'fjurnaldate';
                } elseif ($colName === 'fjurnalnote') {
                    $orderColumn = 'fjurnalnote';
                } elseif ($colName === 'fbalance_rp') {
                    $orderColumn = 'fbalance_rp';
                } elseif ($colName === 'fuserid') {
                    $orderColumn = 'fuserid';
                }
            }

            if ($orderColumn) {
                $query->orderBy($orderColumn, $orderDir);
            } else {
                $query->orderBy('fjurnaldate', 'desc');
            }

            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            $records = $query->skip($start)
                ->take($length)
                ->get([
                    'fjurnalmtid',
                    'fjurnalno',
                    'fjurnaldate',
                    'fbranchcode',
                    'fbalance',
                    'fbalance_rp',
                    'fjurnalnote',
                    'fuserid',
                ]);

            $data = $records->map(function ($row) use ($journalType) {
                $actions = '';
                $routeParams = array_merge(
                    ['fcurrid' => $row->fjurnalmtid],
                    $this->resolveJournalIndexRouteParams($journalType)
                );

                $viewUrl = route('jurnaltransaksi.view', $routeParams);
                $actions .= ' <a href="'.$viewUrl.'" class="inline-flex items-center bg-slate-500 text-white px-3 py-1.5 text-xs rounded hover:bg-slate-600">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg> View
                                </a>';

                $editUrl = route('jurnaltransaksi.edit', $routeParams);
                $actions .= ' <a href="'.$editUrl.'" class="inline-flex items-center bg-yellow-500 text-white px-3 py-1.5 text-xs rounded hover:bg-yellow-600">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg> Edit
                                </a>';

                $deleteUrl = route('jurnaltransaksi.delete', $routeParams);
                $actions .= '<a href="'.$deleteUrl.'">
                <button class="inline-flex items-center bg-red-600 text-white px-3 py-1.5 text-xs rounded hover:bg-red-700">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Hapus
                </button>
            </a>';

                return [
                    'fjurnalno' => $row->fjurnalno,
                    'fjurnaldate' => $row->fjurnaldate
                        ? ($row->fjurnaldate instanceof \Carbon\Carbon ? $row->fjurnaldate : \Carbon\Carbon::parse($row->fjurnaldate))->format('d-m-Y')
                        : '',
                    'fbranchcode' => (string) ($row->fbranchcode ?? ''),
                    'fbalance_rp' => number_format((float) ($row->fbalance_rp ?? $row->fbalance ?? 0), 2, ',', '.'),
                    'fjurnalnote' => $row->fjurnalnote,
                    'fuserid' => $row->fuserid,
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

        return view('jurnaltransaksi.index', compact(
            'canCreate',
            'canEdit',
            'canDelete',
            'showActionsColumn',
            'availableYears',
            'year',
            'month',
            'journalType',
            'pageMeta',
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
        $header = Tr_poh::where('fpohid', $id)->firstOrFail();

        // Mengambil detail dari tr_pod
        $items = DB::table('tr_pod')
          // Detail PO sekarang dihubungkan lewat fpono
            ->where('tr_pod.fpono', $header->fpono)

          // PERBAIKAN JOIN: tr_pod.fprdcode (sekarang integer) di-join ke msprd.fprdid (integer)
            ->leftJoin('msprd as m', 'm.fprdid', '=', 'tr_pod.fprdcode')
            ->select([
                DB::raw("COALESCE(NULLIF(tr_pod.frefdtno, ''), tr_pod.fpodid::text) as frefdtno"),
                'tr_pod.fnouref as fnouref',
                'm.fprdcode as fitemcode', // <-- Ambil kode string dari master produk
                'm.fprdname as fitemname', // <-- Mengambil fprdname dari tabel msprd
                'tr_pod.fqty',
                'tr_pod.fsatuan as fsatuan',
                'tr_pod.fpono',
                'tr_pod.fprice as fharga',
                DB::raw("COALESCE(NULLIF(regexp_replace(COALESCE(tr_pod.fdisc, ''), '[^0-9\\.]', '', 'g'), '')::numeric, 0) as fdiskon"),
            ])
            ->orderBy('tr_pod.fpodid') // Urutkan berdasarkan urutan detail PO (fprdid ASC)
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

        $prefix = sprintf('PBR.%s.%s%s.', $kodeCabang, $date->format('y'), $date->format('m'));

        // kunci per (branch, tahun-bulan) — TANPA bikin tabel baru
        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            $lockKey = crc32('PBR|'.$kodeCabang.'|'.$date->format('Y-m'));
            DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

            $last = DB::table('tr_poh')
                ->where('fpono', 'like', $prefix.'%')
                ->selectRaw("MAX(CAST(split_part(fpono, '.', 4) AS int)) AS lastno")
                ->value('lastno');
        } else {
            $last = DB::table('tr_poh')
                ->where('fpono', 'like', $prefix.'%')
                ->get()
                ->map(function ($row) {
                    $parts = explode('.', $row->fpono);
                    return isset($parts[4]) ? (int) $parts[4] : 0;
                })
                ->max();
        }

        $next = (int) $last + 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function print(string $fjurnalno)
    {
        $hdr = DB::table('jurnalmt')
            ->leftJoin('mscabang as c', 'c.fcabangkode', '=', 'jurnalmt.fbranchcode')
            ->where('jurnalmt.fjurnalno', $fjurnalno)
            ->first([
                'jurnalmt.*',
                'c.fcabangname as cabang_name',
            ]);

        if (! $hdr) {
            return redirect()->back()->with('error', 'Jurnal tidak ada.');
        }

        DB::table('jurnalmt')->where('fjurnalno', $hdr->fjurnalno)->update(['fprint' => 1]);

        $dt = DB::table('jurnaldt')
            ->leftJoin('account as a', 'a.faccount', '=', 'jurnaldt.faccount')
            ->leftJoin('mssubaccount as sa', 'sa.fsubaccountcode', '=', 'jurnaldt.fsubaccount')
            ->leftJoin('mscustomer as c', 'c.fcustomercode', '=', 'jurnaldt.fsubaccount')
            ->leftJoin('mssupplier as p', 'p.fsuppliercode', '=', 'jurnaldt.fsubaccount')
            ->where('jurnaldt.fjurnalno', $fjurnalno)
            ->orderBy('jurnaldt.flineno')
            ->get([
                'jurnaldt.*',
                'a.faccname as account_name',
                DB::raw("COALESCE(
                    CASE 
                        WHEN UPPER(TRIM(COALESCE(a.ftypesubaccount, ''))) IN ('C', 'CUSTOMER') THEN c.fcustomername 
                        WHEN UPPER(TRIM(COALESCE(a.ftypesubaccount, ''))) IN ('P', 'SUPPLIER') THEN p.fsuppliername 
                        ELSE sa.fsubaccountname 
                    END,
                    sa.fsubaccountname, c.fcustomername, p.fsuppliername
                ) as subaccount_name"),
            ]);

        $fmt = fn ($d) => $d
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
        if ($this->hasReachedDailyCreateLimit()) {
            $requestedJournalType = strtoupper(trim((string) request()->query('journal_type', '')));
            return redirect()
                ->route('jurnaltransaksi.index', $this->resolveJournalIndexRouteParams($requestedJournalType))
                ->with('create_limit_exceeded', true);
        }

        $supplier = Supplier::all();

        $accounts = $this->getAccountsData();
        $subaccounts = $this->getSubaccountsData();
        $customers = $this->getCustomersData();
        $suppliers = $this->getSuppliersData();

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

        $requestedJournalType = strtoupper(trim((string) request()->query('journal_type', '')));
        $pageMeta = $this->resolveJournalPageMeta($requestedJournalType);
        $fixedJournalType = null;
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

        $referenceAllowedAccountCodes = $this->resolveReferenceAllowedAccountCodes();
        $referenceSourceAccountCodes = $this->resolveReferenceSourceAccountCodes();

        $journalTypes = $this->getJournalTypes();

        return view('jurnaltransaksi.create', [
            'newtr_prh_code' => $newtr_prh_code,
            'accounts' => $accounts,
            'subaccounts' => $subaccounts,
            'customers' => $customers,
            'suppliers' => $suppliers,
            'supplier' => $supplier,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'products' => $products,
            'referenceAllowedAccountCodes' => $referenceAllowedAccountCodes,
            'referenceSourceAccountCodes' => $referenceSourceAccountCodes,
            'pageTitle' => $pageMeta['pageTitle'],
            'fixedJournalType' => $fixedJournalType,
            'journalType' => $pageMeta['journalType'],
            'journalTypes' => $journalTypes,
            'indexUrl' => route('jurnaltransaksi.index', $this->resolveJournalIndexRouteParams($pageMeta['journalType'])),
        ]);
    }

    public function store(Request $request)
    {
        if ($this->hasReachedDailyCreateLimit()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Batas membuat data sudah terlampaui.',
                    'redirect_url' => route('jurnaltransaksi.index', $this->resolveJournalIndexRouteParams($request->input('fjurnaltype'))),
                ], 422);
            }

            return redirect()
                ->route('jurnaltransaksi.index', $this->resolveJournalIndexRouteParams($request->input('fjurnaltype')))
                ->with('create_limit_exceeded', true);
        }
        // =========================================================
        // 1) VALIDASI — field sesuai kolom jurnalmt & jurnaldt
        // =========================================================
        $request->validate([
            // jurnalmt
            'fjurnalno' => ['nullable', 'string', 'max:100'],
            'fjurnaltype' => ['required', 'string', 'max:10'],
            'fjurnaldate' => ['required', 'date'],
            'fjurnalnote' => ['nullable', 'string', 'max:500'],
            'fbranchcode' => ['nullable', 'string', 'max:20'],

            // jurnaldt (array)
            'faccount' => ['required', 'array', 'min:1'],
            'faccount.*' => ['nullable', 'string', 'max:50'],

            'fsubaccount' => ['nullable', 'array'],
            'fsubaccount.*' => ['nullable', 'string', 'max:50'],

            'fdk' => ['required', 'array'],
            'fdk.*' => ['nullable', 'string', 'in:D,K'],

            'faccountnote' => ['nullable', 'array'],
            'faccountnote.*' => ['nullable', 'string', 'max:255'],

            'frefno' => ['nullable', 'array'],
            'frefno.*' => ['nullable', 'string', 'max:100'],

            'famount' => ['required', 'array'],
            'famount.*' => ['nullable', 'numeric', 'min:0'],

            'frate' => ['nullable', 'array'],
            'frate.*' => ['nullable', 'numeric', 'min:0'],
        ], [
            'fjurnaldate.required' => 'Tanggal jurnal wajib diisi.',
            'fjurnaltype.required' => 'Tipe jurnal wajib diisi.',
            'faccount.required' => 'Minimal harus ada 1 baris jurnal.',
            'fdk.*.in' => 'Pilihan D/K harus Debit atau Kredit.',
            'famount.*.min' => 'Jumlah jurnal tidak boleh minus.',
        ]);

        // =========================================================
        // 2) AMBIL DATA HEADER
        // =========================================================
        $fjurnaldate = Carbon::parse($request->fjurnaldate)->startOfDay();
        $this->ensureCreateDateWithinEditPeriod($fjurnaldate);
        $fjurnaltype = strtoupper(trim((string) $request->input('fjurnaltype', 'SJU')));
        $fjurnalnote = trim((string) $request->input('fjurnalnote', ''));
        $fbranchcode = $request->input('fbranchcode');
        $now = now();
        $fuserid = Auth::user()->fname ?? Auth::user()->name ?? 'system';

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

                    if (! $kodeCabang) {
                        $kodeCabang = DB::table('mscabang')
                            ->whereRaw('LOWER(fcabangname) = LOWER(?)', [$needle])
                            ->value('fcabangkode');
                    }
                }
            }
        }
        if (! $kodeCabang) {
            $kodeCabang = 'NA';
        }

        $yy = $fjurnaldate->format('y');
        $mm = $fjurnaldate->format('m');

        // =========================================================
        // 3) RAKIT BARIS DETAIL — field = kolom jurnaldt
        // =========================================================
        $accounts = $request->input('faccount', []);
        $subaccounts = $request->input('fsubaccount', []);
        $dks = $request->input('fdk', []);
        $notes = $request->input('faccountnote', []);
        $refnos = $request->input('frefno', []);
        $amounts = $request->input('famount', []);
        $rates = $request->input('frate', []);
        $referenceAllowedAccountCodes = collect($this->resolveReferenceAllowedAccountCodes())
            ->map(fn ($code) => strtoupper(trim((string) $code)))
            ->flip()
            ->all();

        $rowsDt = [];
        $totalDebit = 0.0;
        $totalKredit = 0.0;
        $rowCount = count($accounts);

        for ($i = 0; $i < $rowCount; $i++) {
            $faccount = trim((string) ($accounts[$i] ?? ''));
            $fsubaccount = trim((string) ($subaccounts[$i] ?? '')) ?: null;
            $fdk = strtoupper(trim((string) ($dks[$i] ?? '')));
            $fnote = trim((string) ($notes[$i] ?? '')) ?: null;
            $frefno = trim((string) ($refnos[$i] ?? '')) ?: null;
            $famount = $this->normalizeDecimal($amounts[$i] ?? 0, 2);
            $frate = $this->normalizeDecimal($rates[$i] ?? 1, 4);
            if ($frate <= 0) {
                $frate = 1;
            }

            if ($faccount === '' && $famount <= 0 && $fsubaccount === null && $fnote === null && $frefno === null) {
                continue;
            }

            if ($frefno !== null && ! isset($referenceAllowedAccountCodes[strtoupper($faccount)])) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => "Ref No di baris ".($i + 1)." tidak boleh diisi untuk account ini."
                    ], 422);
                }
                return back()->withInput()->withErrors([
                    'detail' => "Ref No di baris ".($i + 1)." tidak boleh diisi untuk account ini.",
                ]);
            }

            // Skip baris tidak valid
            if ($faccount === '' || $famount <= 0 || ! in_array($fdk, ['D', 'K'])) {
                continue;
            }

            if ($fdk === 'D') {
                $totalDebit += $famount;
            } else {
                $totalKredit += $famount;
            }

            $rowsDt[] = [
                // ── Kolom jurnaldt ──
                'fbranchcode' => $kodeCabang,
                'fjurnaltype' => $fjurnaltype,
                'faccount' => $faccount,       // kode akun string (misal: "11400")
                'fsubaccount' => $fsubaccount,    // kode sub akun string | null
                'fdk' => $fdk,            // 'D' | 'K'
                'faccountnote' => $fnote,          // keterangan baris
                'frefno' => $frefno,         // no referensi
                'famount' => $famount,        // jumlah (currency)
                'famount_rp' => round($famount * $frate, 2),
                'frate' => $frate,
                'fusercreate' => $fuserid,
                'fdatetime' => $now,
            ];
        }

        if (empty($rowsDt)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Minimal harus ada 1 baris jurnal yang lengkap dan jumlahnya lebih dari 0.'
                ], 422);
            }
            return back()->withInput()->withErrors([
                'detail' => 'Minimal harus ada 1 baris jurnal yang lengkap dan jumlahnya lebih dari 0.',
            ]);
        }

        // ── Validasi balance debit = kredit ──
        if ($validationMessage = $this->validateUniqueJournalReferenceUsage($rowsDt)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $validationMessage
                ], 422);
            }
            return back()->withInput()->withErrors([
                'detail' => $validationMessage,
            ]);
        }

        if ($validationMessage = $this->validateJournalReferenceSources($rowsDt)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $validationMessage
                ], 422);
            }
            return back()->withInput()->withErrors([
                'detail' => $validationMessage,
            ]);
        }

        if ($validationMessage = $this->validateJournalSubaccounts($rowsDt)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $validationMessage
                ], 422);
            }
            return back()->withInput()->withErrors([
                'detail' => $validationMessage,
            ]);
        }

        if (round($totalDebit, 2) !== round($totalKredit, 2)) {
            $msg = sprintf(
                'Jurnal belum seimbang. Total Debit Rp %s dan Total Kredit Rp %s.',
                number_format($totalDebit, 2, ',', '.'),
                number_format($totalKredit, 2, ',', '.')
            );
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $msg
                ], 422);
            }
            return back()->withInput()->withErrors([
                'detail' => $msg,
            ]);
        }

        // =========================================================
        // 4) TRANSAKSI DB
        // =========================================================
        $newJurnalMtId = null;
        $fjurnalno = null;

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
            &$rowsDt,
            &$newJurnalMtId,
            &$fjurnalno
        ) {
            // ── 4.1. Generate / ambil fjurnalno ──
            $fjurnalno = strtoupper(trim((string) $request->input('fjurnalno', '')));

            if (empty($fjurnalno)) {
                $prefix = sprintf('JV.%s.%s.%s%s.', $fjurnaltype, $kodeCabang, $yy, $mm);
                $driver = DB::getDriverName();
                if ($driver === 'pgsql') {
                    $lockKey = crc32('JURNAL|'.$fjurnaltype.'|'.$kodeCabang.'|'.$fjurnaldate->format('y-m'));
                    DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

                    $lastNo = DB::table('jurnalmt')
                        ->where('fjurnalno', 'like', $prefix.'%')
                        ->selectRaw("MAX(CAST(SUBSTRING(fjurnalno FROM '([0-9]+)$') AS integer)) AS lastno")
                        ->value('lastno');
                } else {
                    $lastNo = DB::table('jurnalmt')
                        ->where('fjurnalno', 'like', $prefix.'%')
                        ->get()
                        ->map(function ($row) {
                            $parts = explode('.', $row->fjurnalno);
                            return isset($parts[5]) ? (int) $parts[5] : 0;
                        })
                        ->max();
                }

                $fjurnalno = $prefix.str_pad((string) ((int) $lastNo + 1), 4, '0', STR_PAD_LEFT);
            }

            // ── 4.2. INSERT jurnalmt ──
            // Sesuai kolom: fjurnalmtid(serial), fbranchcode, fjurnalno, fjurnaltype,
            //               fjurnaldate, fjurnalnote, fbalance, fbalance_rp,
            //               fdatetime, fuserid, fuserupdate
            $newJurnalMtId = DB::table('jurnalmt')->insertGetId([
                'fbranchcode' => $kodeCabang,
                'fjurnalno' => $fjurnalno,
                'fjurnaltype' => $fjurnaltype,
                'fjurnaldate' => $fjurnaldate,
                'fjurnalnote' => $fjurnalnote ?: ('Jurnal '.$fjurnalno),
                'fbalance' => $totalDebit,   // total = debit = kredit
                'fbalance_rp' => $totalDebit,
                'fdatetime' => $now,
                'fuserid' => $fuserid,
                'fuserupdate' => null,
                'fprint' => 0,
            ], 'fjurnalmtid');

            if (! $newJurnalMtId) {
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
                    'fjurnalmtid' => $newJurnalMtId,
                    'fbranchcode' => $r['fbranchcode'],
                    'fjurnalno' => $fjurnalno,
                    'fjurnaltype' => $r['fjurnaltype'],
                    'flineno' => $flineno++,
                    'faccount' => $r['faccount'],
                    'fsubaccount' => $r['fsubaccount'],
                    'fdk' => $r['fdk'],
                    'faccountnote' => $r['faccountnote'],
                    'frefno' => $r['frefno'],
                    'famount' => $r['famount'],
                    'famount_rp' => $r['famount_rp'],
                    'frate' => $r['frate'],
                    'fusercreate' => $r['fusercreate'],
                    'fdatetime' => $r['fdatetime'],
                ];
            }

            DB::table('jurnaldt')->insert($details);
        });

        $printUrl = route('jurnaltransaksi.print', ['fjurnalno' => $fjurnalno]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Jurnal Transaksi {$fjurnalno} berhasil disimpan.",
                'redirect_url' => route('jurnaltransaksi.create', array_merge(
                    ['fcurrid' => $newJurnalMtId],
                    $this->resolveJournalIndexRouteParams($fjurnaltype)
                )),
                'success_prompt' => [
                    'type'         => 'jurnaltransaksi_create',
                    'redirect_url' => $printUrl,
                ]
            ]);
        }

        return redirect()
            ->route('jurnaltransaksi.create', array_merge(
                ['fcurrid' => $newJurnalMtId],
                $this->resolveJournalIndexRouteParams($fjurnaltype)
            ))
            ->with('success', "Jurnal Transaksi {$fjurnalno} berhasil disimpan.")
            ->with('success_prompt', [
                'type'         => 'jurnaltransaksi_create',
                'redirect_url' => $printUrl,
            ]);
    }

    public function edit($fstockmtid)
    {
        $supplier = Supplier::all();

        $accounts = $this->getAccountsData();
        $subaccounts = $this->getSubaccountsData();
        $customers = $this->getCustomersData();
        $suppliers = $this->getSuppliersData();

        $warehouses = collect();

        [$jurnaltransaksi, $savedItems] = $this->getJournalTransactionFormData($fstockmtid);
        ['fcabang' => $fcabang, 'fbranchcode' => $fbranchcode] = $this->resolveBranchContext($jurnaltransaksi->fbranchcode ?? null);
        if ($message = $this->getPostedPeriodLockMessage($jurnaltransaksi->fjurnaldate, 'Jurnal ini')) {
            return redirect()->route('jurnaltransaksi.edit', ['fcurrid' => $fstockmtid] + $this->resolveJournalIndexRouteParams($jurnaltransaksi->fjurnaltype))->with('error', $message);
        }
        $selectedSupplierCode = null;

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

        $referenceAllowedAccountCodes = $this->resolveReferenceAllowedAccountCodes();
        $referenceSourceAccountCodes = $this->resolveReferenceSourceAccountCodes();
        $indexUrl = route('jurnaltransaksi.index', $this->resolveJournalIndexRouteParams($jurnaltransaksi->fjurnaltype));

        $journalTypes = $this->getJournalTypes();

        return view('jurnaltransaksi.edit', [
            'supplier' => $supplier,
            'selectedSupplierCode' => $selectedSupplierCode,
            'fcabang' => $fcabang,
            'accounts' => $accounts,
            'subaccounts' => $subaccounts,
            'customers' => $customers,
            'suppliers' => $suppliers,
            'fbranchcode' => $fbranchcode,
            'warehouses' => $warehouses,
            'products' => $products,
            'productMap' => $productMap,
            'jurnaltransaksi' => $jurnaltransaksi,
            'pemakaianbarang' => $jurnaltransaksi,
            'savedItems' => $savedItems,
            'referenceAllowedAccountCodes' => $referenceAllowedAccountCodes,
            'referenceSourceAccountCodes' => $referenceSourceAccountCodes,
            'ppnAmount' => 0,
            'famountponet' => 0,
            'famountpo' => 0,
            'action' => 'edit',
            'pageTitle' => $this->resolveJournalTitle($jurnaltransaksi->fjurnaltype, 'Edit'),
            'lockJournalType' => false,
            'journalTypes' => $journalTypes,
            'indexUrl' => $indexUrl,
        ]);
    }

    public function view($fstockmtid)
    {
        $supplier = Supplier::all();

        $accounts = $this->getAccountsData();
        $subaccounts = $this->getSubaccountsData();
        $customers = $this->getCustomersData();
        $suppliers = $this->getSuppliersData();

        $warehouses = collect();

        [$jurnaltransaksi, $savedItems] = $this->getJournalTransactionFormData($fstockmtid);
        ['fcabang' => $fcabang, 'fbranchcode' => $fbranchcode] = $this->resolveBranchContext($jurnaltransaksi->fbranchcode ?? null);
        $selectedSupplierCode = null;

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

        $referenceAllowedAccountCodes = $this->resolveReferenceAllowedAccountCodes();
        $indexUrl = route('jurnaltransaksi.index', $this->resolveJournalIndexRouteParams($jurnaltransaksi->fjurnaltype));

        $journalTypes = $this->getJournalTypes();

        $currentType = trim($jurnaltransaksi->fjurnaltype);
        $hasCurrentType = $journalTypes->contains(function ($item) use ($currentType) {
            return $item->fmastercode === $currentType;
        });

        if (!$hasCurrentType && $currentType !== '') {
            $journalTypes->push((object)[
                'fmastercode' => $currentType,
                'fmastername' => match ($currentType) {
                    self::PURCHASE_JOURNAL_TYPE => 'JURNAL PEMBELIAN (JBL)',
                    self::SALES_JOURNAL_TYPE => 'JURNAL PENJUALAN (SLS)',
                    default => $currentType,
                },
            ]);
        }

        return view('jurnaltransaksi.view', [
            'supplier' => $supplier,
            'selectedSupplierCode' => $selectedSupplierCode,
            'fcabang' => $fcabang,
            'accounts' => $accounts,
            'subaccounts' => $subaccounts,
            'customers' => $customers,
            'suppliers' => $suppliers,
            'fbranchcode' => $fbranchcode,
            'warehouses' => $warehouses,
            'products' => $products,
            'productMap' => $productMap,
            'jurnaltransaksi' => $jurnaltransaksi,
            'pemakaianbarang' => $jurnaltransaksi,
            'savedItems' => $savedItems,
            'referenceAllowedAccountCodes' => $referenceAllowedAccountCodes,
            'ppnAmount' => 0,
            'famountponet' => 0,
            'famountpo' => 0,
            'journalTypes' => $journalTypes,
            'pageTitle' => $this->resolveJournalTitle($jurnaltransaksi->fjurnaltype, 'View'),
            'indexUrl' => $indexUrl,
        ]);
    }

    public function update(Request $request, $fstockmtid)
    {
        try {
            $request->validate([
            'fjurnalno' => ['required', 'string', 'max:100'],
            'fjurnaltype' => ['required', 'string', 'max:10'],
            'fjurnaldate' => ['required', 'date'],
            'fjurnalnote' => ['nullable', 'string', 'max:500'],
            'fbranchcode' => ['nullable', 'string', 'max:20'],

            'faccount' => ['required', 'array', 'min:1'],
            'faccount.*' => ['nullable', 'string', 'max:50'],

            'fsubaccount' => ['nullable', 'array'],
            'fsubaccount.*' => ['nullable', 'string', 'max:50'],

            'fdk' => ['required', 'array'],
            'fdk.*' => ['nullable', 'string', 'in:D,K'],

            'faccountnote' => ['nullable', 'array'],
            'faccountnote.*' => ['nullable', 'string', 'max:255'],

            'frefno' => ['nullable', 'array'],
            'frefno.*' => ['nullable', 'string', 'max:100'],

            'famount' => ['required', 'array'],
            'famount.*' => ['nullable', 'numeric', 'min:0'],

            'frate' => ['nullable', 'array'],
            'frate.*' => ['nullable', 'numeric', 'min:0'],
        ], [
            'fjurnaldate.required' => 'Tanggal jurnal wajib diisi.',
            'fjurnaltype.required' => 'Tipe jurnal wajib diisi.',
            'faccount.required' => 'Minimal harus ada 1 baris jurnal.',
            'fdk.*.in' => 'Pilihan D/K harus Debit atau Kredit.',
            'famount.*.min' => 'Jumlah jurnal tidak boleh minus.',
        ]);

        $header = DB::table('jurnalmt')
            ->where('fjurnalmtid', $fstockmtid)
            ->first();

        if (! $header) {
            abort(404);
        }

        if ($message = $this->getPostedPeriodLockMessage($header->fjurnaldate, 'Jurnal ini')) {
            return redirect()->route('jurnaltransaksi.edit', ['fcurrid' => $fstockmtid] + $this->resolveJournalIndexRouteParams($header->fjurnaltype))->with('error', $message);
        }

        $fjurnaldate = Carbon::parse($request->fjurnaldate)->startOfDay();
        $this->ensureCreateDateWithinEditPeriod($fjurnaldate, $header->fjurnaldate);
        $fjurnaltype = strtoupper(trim((string) $request->input('fjurnaltype', 'SJU')));
        $fjurnalnote = trim((string) $request->input('fjurnalnote', ''));
        $fbranchcode = $request->input('fbranchcode');
        $now = now();
        $fuserid = Auth::user()->fname ?? Auth::user()->name ?? 'system';

        $accounts = $request->input('faccount', []);
        $subaccounts = $request->input('fsubaccount', []);
        $dks = $request->input('fdk', []);
        $notes = $request->input('faccountnote', []);
        $refnos = $request->input('frefno', []);
        $amounts = $request->input('famount', []);
        $rates = $request->input('frate', []);
        $referenceAllowedAccountCodes = collect($this->resolveReferenceAllowedAccountCodes())
            ->map(fn ($code) => strtoupper(trim((string) $code)))
            ->flip()
            ->all();

        $rowsDt = [];
        $totalDebit = 0.0;
        $totalKredit = 0.0;
        $rowCount = count($accounts);

        for ($i = 0; $i < $rowCount; $i++) {
            $faccount = trim((string) ($accounts[$i] ?? ''));
            $fsubaccount = trim((string) ($subaccounts[$i] ?? '')) ?: null;
            $fdk = strtoupper(trim((string) ($dks[$i] ?? '')));
            $fnote = trim((string) ($notes[$i] ?? '')) ?: null;
            $frefno = trim((string) ($refnos[$i] ?? '')) ?: null;
            $famount = $this->normalizeDecimal($amounts[$i] ?? 0, 2);
            $frate = $this->normalizeDecimal($rates[$i] ?? 1, 4);
            if ($frate <= 0) {
                $frate = 1;
            }

            if ($faccount === '' && $famount <= 0 && $fsubaccount === null && $fnote === null && $frefno === null) {
                continue;
            }

            if ($frefno !== null && ! isset($referenceAllowedAccountCodes[strtoupper($faccount)])) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => "Ref No di baris ".($i + 1)." tidak boleh diisi untuk account ini."
                    ], 422);
                }
                return back()->withInput()->withErrors([
                    'detail' => "Ref No di baris ".($i + 1)." tidak boleh diisi untuk account ini.",
                ]);
            }

            if ($faccount === '' || $famount <= 0 || ! in_array($fdk, ['D', 'K'])) {
                continue;
            }

            if ($fdk === 'D') {
                $totalDebit += $famount;
            } else {
                $totalKredit += $famount;
            }

            $rowsDt[] = [
                'fbranchcode' => $header->fbranchcode,
                'fjurnaltype' => $fjurnaltype,
                'faccount' => $faccount,
                'fsubaccount' => $fsubaccount,
                'fdk' => $fdk,
                'faccountnote' => $fnote,
                'frefno' => $frefno,
                'famount' => $famount,
                'famount_rp' => round($famount * $frate, 2),
                'frate' => $frate,
                'fusercreate' => $fuserid,
                'fdatetime' => $now,
            ];
        }

        if (empty($rowsDt)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Minimal harus ada 1 baris jurnal yang lengkap dan jumlahnya lebih dari 0.'
                ], 422);
            }
            return back()->withInput()->withErrors([
                'detail' => 'Minimal harus ada 1 baris jurnal yang lengkap dan jumlahnya lebih dari 0.',
            ]);
        }

        if ($validationMessage = $this->validateUniqueJournalReferenceUsage($rowsDt, $header->fjurnalno)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $validationMessage
                ], 422);
            }
            return back()->withInput()->withErrors([
                'detail' => $validationMessage,
            ]);
        }

        if ($validationMessage = $this->validateJournalReferenceSources($rowsDt)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $validationMessage
                ], 422);
            }
            return back()->withInput()->withErrors([
                'detail' => $validationMessage,
            ]);
        }

        if ($validationMessage = $this->validateJournalSubaccounts($rowsDt)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $validationMessage
                ], 422);
            }
            return back()->withInput()->withErrors([
                'detail' => $validationMessage,
            ]);
        }

        if (round($totalDebit, 2) !== round($totalKredit, 2)) {
            $msg = sprintf(
                'Jurnal belum seimbang. Total Debit Rp %s dan Total Kredit Rp %s.',
                number_format($totalDebit, 2, ',', '.'),
                number_format($totalKredit, 2, ',', '.')
            );
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $msg
                ], 422);
            }
            return back()->withInput()->withErrors([
                'detail' => $msg,
            ]);
        }

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

                    if (! $kodeCabang) {
                        $kodeCabang = DB::table('mscabang')
                            ->whereRaw('LOWER(fcabangname) = LOWER(?)', [$needle])
                            ->value('fcabangkode');
                    }
                }
            }
        }
        if (! $kodeCabang) {
            $kodeCabang = $header->fbranchcode ?: 'NA';
        }

        DB::transaction(function () use ($fstockmtid, $header, $kodeCabang, $fjurnaldate, $fjurnaltype, $fjurnalnote, $now, $fuserid, $totalDebit, &$rowsDt) {
            DB::table('jurnalmt')
                ->where('fjurnalmtid', $fstockmtid)
                ->update([
                    'fbranchcode' => $kodeCabang,
                    'fjurnaltype' => $fjurnaltype,
                    'fjurnaldate' => $fjurnaldate,
                    'fjurnalnote' => $fjurnalnote ?: ('Jurnal '.$header->fjurnalno),
                    'fbalance' => $totalDebit,
                    'fbalance_rp' => $totalDebit,
                    'fdatetime' => $now,
                    'fuserid' => $fuserid,
                    'fuserupdate' => $fuserid,
                ]);

            DB::table('jurnaldt')->where('fjurnalmtid', $fstockmtid)->delete();

            $details = [];
            $flineno = 1;
            foreach ($rowsDt as $row) {
                $details[] = [
                    'fjurnalmtid' => $fstockmtid,
                    'fbranchcode' => $kodeCabang,
                    'fjurnalno' => $header->fjurnalno,
                    'fjurnaltype' => $fjurnaltype,
                    'flineno' => $flineno++,
                    'faccount' => $row['faccount'],
                    'fsubaccount' => $row['fsubaccount'],
                    'fdk' => $row['fdk'],
                    'faccountnote' => $row['faccountnote'],
                    'frefno' => $row['frefno'],
                    'famount' => $row['famount'],
                    'famount_rp' => $row['famount_rp'],
                    'frate' => $row['frate'],
                    'fusercreate' => $fuserid,
                    'fdatetime' => $now,
                ];
            }

            DB::table('jurnaldt')->insert($details);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Jurnal Transaksi {$header->fjurnalno} berhasil diupdate.",
                'redirect_url' => route('jurnaltransaksi.edit', array_merge(
                    ['fcurrid' => $fstockmtid],
                    $this->resolveJournalIndexRouteParams($fjurnaltype)
                )),
            ]);
        }

        return redirect()
            ->route('jurnaltransaksi.edit', array_merge(
                ['fcurrid' => $fstockmtid],
                $this->resolveJournalIndexRouteParams($fjurnaltype)
            ))
            ->with('success', "Jurnal Transaksi {$header->fjurnalno} berhasil diupdate.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();

            if ($request->expectsJson()) {
                return response()->json(['message' => $firstError ?: 'Gagal update jurnal transaksi.'], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('error', $firstError ?: 'Gagal mengupdate jurnal transaksi. Cek data.');
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Gagal mengupdate jurnal transaksi: ' . $e->getMessage()], 500);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal mengupdate jurnal transaksi: ' . $e->getMessage());
        }
    }

    public function delete($fstockmtid)
    {
        $supplier = Supplier::all();

        $accounts = $this->getAccountsData();
        $subaccounts = $this->getSubaccountsData();
        $customers = $this->getCustomersData();
        $suppliers = $this->getSuppliersData();

        $warehouses = collect();

        [$jurnaltransaksi, $savedItems] = $this->getJournalTransactionFormData($fstockmtid);
        ['fcabang' => $fcabang, 'fbranchcode' => $fbranchcode] = $this->resolveBranchContext($jurnaltransaksi->fbranchcode ?? null);
        if ($message = $this->getPostedPeriodLockMessage($jurnaltransaksi->fjurnaldate, 'Jurnal ini')) {
            return redirect()->route('jurnaltransaksi.edit', ['fcurrid' => $fstockmtid] + $this->resolveJournalIndexRouteParams($jurnaltransaksi->fjurnaltype))->with('error', $message);
        }
        $selectedSupplierCode = null;

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

        $referenceAllowedAccountCodes = $this->resolveReferenceAllowedAccountCodes();
        $referenceSourceAccountCodes = $this->resolveReferenceSourceAccountCodes();
        $indexUrl = route('jurnaltransaksi.index', $this->resolveJournalIndexRouteParams($jurnaltransaksi->fjurnaltype));

        $journalTypes = $this->getJournalTypes();

        return view('jurnaltransaksi.edit', [
            'supplier' => $supplier,
            'selectedSupplierCode' => $selectedSupplierCode,
            'fcabang' => $fcabang,
            'accounts' => $accounts,
            'subaccounts' => $subaccounts,
            'customers' => $customers,
            'suppliers' => $suppliers,
            'fbranchcode' => $fbranchcode,
            'warehouses' => $warehouses,
            'products' => $products,
            'productMap' => $productMap,
            'jurnaltransaksi' => $jurnaltransaksi,
            'pemakaianbarang' => $jurnaltransaksi,
            'savedItems' => $savedItems,
            'referenceAllowedAccountCodes' => $referenceAllowedAccountCodes,
            'referenceSourceAccountCodes' => $referenceSourceAccountCodes,
            'ppnAmount' => 0,
            'famountponet' => 0,
            'famountpo' => 0,
            'action' => 'delete',
            'pageTitle' => $this->resolveJournalTitle($jurnaltransaksi->fjurnaltype, 'Hapus'),
            'lockJournalType' => true,
            'journalTypes' => $journalTypes,
            'indexUrl' => $indexUrl,
        ]);
    }

    public function destroy($fstockmtid)
    {
        try {
            $jurnaltransaksi = DB::table('jurnalmt')
                ->where('fjurnalmtid', $fstockmtid)
                ->first();

            if (! $jurnaltransaksi) {
                abort(404);
            }

            if ($message = $this->getPostedPeriodLockMessage($jurnaltransaksi->fjurnaldate, 'Jurnal ini')) {
                return redirect()->route('jurnaltransaksi.edit', ['fcurrid' => $fstockmtid] + $this->resolveJournalIndexRouteParams($jurnaltransaksi->fjurnaltype))->with('error', $message);
            }

            DB::transaction(function () use ($fstockmtid) {
                DB::table('jurnaldt')->where('fjurnalmtid', $fstockmtid)->delete();
                DB::table('jurnalmt')->where('fjurnalmtid', $fstockmtid)->delete();
            });

            $redirectUrl = route('jurnaltransaksi.index', $this->resolveJournalIndexRouteParams($jurnaltransaksi->fjurnaltype ?? null));
            $message = 'Data '.$this->resolveJournalSuccessName($jurnaltransaksi->fjurnaltype ?? null).' '.trim((string) $jurnaltransaksi->fjurnalno).' berhasil dihapus.';

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'redirect_url' => $redirectUrl,
                ]);
            }

            return redirect($redirectUrl)->with('success', $message);
        } catch (\Exception $e) {
            $message = 'Jurnal belum bisa dihapus. Coba lagi.';

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => $message,
                ], 500);
            }

            return redirect()->route('jurnaltransaksi.delete', $fstockmtid)->with('error', $message);
        }
    }

    private function getJournalTransactionFormData($journalId): array
    {
        $header = DB::table('jurnalmt')
            ->when(is_numeric($journalId), function ($query) use ($journalId) {
                $query->where('fjurnalmtid', (int) $journalId);
            }, function ($query) use ($journalId) {
                $query->where('fjurnalno', $journalId);
            })
            ->first();

        if (! $header) {
            abort(404);
        }

        $details = DB::table('jurnaldt as d')
            ->leftJoin('account as a', 'a.faccount', '=', 'd.faccount')
            ->leftJoin('mssubaccount as s', 's.fsubaccountcode', '=', 'd.fsubaccount')
            ->leftJoin('mscustomer as c', 'c.fcustomercode', '=', 'd.fsubaccount')
            ->leftJoin('mssupplier as supp', 'supp.fsuppliercode', '=', 'd.fsubaccount')
            ->where('d.fjurnalmtid', $header->fjurnalmtid)
            ->orderBy('d.flineno')
            ->get([
                'd.flineno',
                'd.faccount',
                'd.fsubaccount',
                'd.fdk',
                'd.faccountnote',
                'd.frefno',
                'd.famount',
                'd.famount_rp',
                'd.frate',
                'a.faccid',
                'a.faccname',
                'a.fhavesubaccount',
                'a.ftypesubaccount',
                's.fsubaccountid',
                DB::raw("COALESCE(
                    CASE 
                        WHEN UPPER(TRIM(COALESCE(a.ftypesubaccount, ''))) IN ('C', 'CUSTOMER') THEN c.fcustomername 
                        WHEN UPPER(TRIM(COALESCE(a.ftypesubaccount, ''))) IN ('P', 'SUPPLIER') THEN supp.fsuppliername 
                        ELSE s.fsubaccountname 
                    END,
                    s.fsubaccountname, c.fcustomername, supp.fsuppliername
                ) as fsubaccountname"),
            ]);

        $journalViewModel = (object) [
            'fjurnalmtid' => $header->fjurnalmtid,
            'fjurnalno' => $header->fjurnalno,
            'fjurnaldate' => $header->fjurnaldate,
            'fjurnaltype' => $header->fjurnaltype,
            'fjurnalnote' => $header->fjurnalnote,
            'fbalance' => $header->fbalance,
            'fbalance_rp' => $header->fbalance_rp,
            'fstockmtid' => $header->fjurnalmtid,
            'fstockmtno' => $header->fjurnalno,
            'fstockmtdate' => $header->fjurnaldate ? Carbon::parse($header->fjurnaldate)->format('Y-m-d') : null,
            'ffrom' => null,
            'fket' => $header->fjurnalnote,
            'fsupplier' => null,
            'famountpopajak' => 0,
            'famountponet' => 0,
            'famountpo' => 0,
            'fcreatedat' => $header->fcreatedat ?? null,
            'fupdatedat' => $header->fupdatedat ?? null,
            'fusercreate' => $header->fusercreate ?? null,
            'fuserupdate' => $header->fuserupdate ?? null,
            'fcreatedby' => $header->fcreatedby ?? null,
            'fupdatedby' => $header->fupdatedby ?? null,
        ];

        $savedItems = $details->map(function ($row) {
            $label = trim((string) ($row->faccount ?? ''));
            $name = trim((string) ($row->faccname ?? ''));
            $subName = trim((string) ($row->fsubaccountname ?? ''));
            if ($subName === '' && ! empty($row->fsubaccount)) {
                $subName = trim((string) $row->fsubaccount);
            }

            return [
                'uid' => (int) ($row->flineno ?? 0),
                'fitemcode' => trim((string) ($row->faccount ?? '')),
                'fitemname' => $name !== '' ? $name : trim((string) ($row->faccountnote ?? '')),
                'fsatuan' => trim((string) ($row->fdk ?? '')),
                'fprno' => trim((string) ($row->frefno ?? '')),
                'frefpr' => trim((string) ($row->frefno ?? '')),
                'frefso' => trim((string) ($row->fsubaccount ?? '')),
                'fpono' => null,
                'famountponet' => (float) ($row->famount ?? 0),
                'famountpo' => (float) ($row->famount_rp ?? 0),
                'frefdtno' => trim((string) ($row->faccount ?? '')),
                'fnouref' => (int) ($row->flineno ?? 0),
                'fqty' => (float) ($row->famount ?? 0),
                'fterima' => 0,
                'fdisc' => 0,
                'ftotal' => (float) ($row->famount_rp ?? $row->famount ?? 0),
                'fdesc' => trim((string) ($row->faccountnote ?? '')),
                'fketdt' => trim((string) ($row->frefno ?? '')),
                'units' => [],
                'faccid' => $row->faccid,
                'faccount' => $label,
                'faccname' => $name !== '' ? $name : $label,
                'fhavesubaccount' => (int) ($row->fhavesubaccount ?? 0),
                'ftypesubaccount' => (string) ($row->ftypesubaccount ?? 'S'),
                'fsubaccountid' => $row->fsubaccountid,
                'fsubaccountcode' => trim((string) ($row->fsubaccount ?? '')),
                'fsubaccountname' => $subName,
                'fdk' => trim((string) ($row->fdk ?? '')),
                'faccountnote' => trim((string) ($row->faccountnote ?? '')),
                'frefno' => trim((string) ($row->frefno ?? '')),
                'famount' => (float) ($row->famount ?? 0),
                'frate' => (float) ($row->frate ?? 1),
            ];
        })->values();

        return [$journalViewModel, $savedItems];
    }

    private function validateUniqueJournalReferenceUsage(array $rowsDt, ?string $exceptJurnalNo = null): ?string
    {
        $referenceNos = collect($rowsDt)
            ->pluck('frefno')
            ->map(fn ($value) => trim((string) ($value ?? '')))
            ->filter(fn ($value) => $value !== '')
            ->unique()
            ->values()
            ->all();

        if (empty($referenceNos)) {
            return null;
        }

        foreach ($referenceNos as $referenceNo) {
            $query = DB::table('jurnaldt as d')
                ->join('jurnalmt as h', 'h.fjurnalmtid', '=', 'd.fjurnalmtid')
                ->whereRaw('TRIM(COALESCE(d.frefno, \'\')) = ?', [$referenceNo]);

            if (! empty($exceptJurnalNo)) {
                $query->where('h.fjurnalno', '<>', $exceptJurnalNo);
            }

            $existing = $query
                ->orderBy('h.fjurnalno')
                ->select('h.fjurnalno as transaction_no')
                ->first();

            if ($existing) {
                return 'No. referensi ' . $referenceNo . ' sudah ada di transaksi ' . trim((string) ($existing->transaction_no ?? '')) . '.';
            }
        }

        return null;
    }

    private function validateJournalReferenceSources(array $rowsDt): ?string
    {
        $sourceCodes = $this->resolveReferenceSourceAccountCodes();
        $accountSources = [];
        foreach ($sourceCodes as $source => $codes) {
            foreach ($codes as $code) {
                $accountSources[strtoupper(trim((string) $code))] = $source;
            }
        }

        foreach ($rowsDt as $index => $row) {
            $account = strtoupper(trim((string) ($row['faccount'] ?? '')));
            $refNo = trim((string) ($row['frefno'] ?? ''));
            if ($account === '' || ! isset($accountSources[$account])) {
                continue;
            }

            if ($refNo === '') {
                return "Tidak boleh input disini.\nAccount ini memiliki No.Ref\nPenyimpanan dibatalkan";
            }

            $source = $accountSources[$account];
            $exists = match ($source) {
                'purchase' => DB::table('trstockmt')->where('fstockmtcode', 'BUY')->whereRaw('TRIM(fstockmtno) = ?', [$refNo])->exists(),
                'purchase_return' => DB::table('trstockmt')->where('fstockmtcode', 'REB')->whereRaw('TRIM(fstockmtno) = ?', [$refNo])->exists(),
                'sales' => DB::table('tranmt')->where('ftrcode', 'INV')->whereRaw('TRIM(fsono) = ?', [$refNo])->exists(),
                'sales_return' => DB::table('tranmt')->where('ftrcode', 'REJ')->whereRaw('TRIM(fsono) = ?', [$refNo])->exists(),
                default => false,
            };

            if (! $exists) {
                $label = [
                    'purchase' => 'pembelian',
                    'sales' => 'penjualan',
                    'sales_return' => 'retur penjualan',
                    'purchase_return' => 'retur pembelian',
                ][$source] ?? 'referensi';

                return "Tidak boleh input disini.\nAccount ini memiliki No.Ref\nPenyimpanan dibatalkan\nRef No baris " . ($index + 1) . ' harus berasal dari browse ' . $label . '.';
            }
        }

        return null;
    }

    private function validateJournalSubaccounts(array $rowsDt): ?string
    {
        $accountCodes = collect($rowsDt)->pluck('faccount')->filter()->unique()->values()->all();

        $accounts = DB::table('account')
            ->whereIn('faccount', $accountCodes)
            ->get(['faccount', 'fhavesubaccount', 'ftypesubaccount'])
            ->keyBy('faccount');

        $subaccountCodes = collect($rowsDt)->pluck('fsubaccount')->filter()->unique()->values()->all();

        $subaccounts = DB::table('mssubaccount')
            ->whereIn('fsubaccountcode', $subaccountCodes)
            ->pluck('fsubaccountcode')
            ->flip()
            ->all();

        $customers = DB::table('mscustomer')
            ->whereIn('fcustomercode', $subaccountCodes)
            ->pluck('fcustomercode')
            ->flip()
            ->all();

        $suppliers = DB::table('mssupplier')
            ->whereIn('fsuppliercode', $subaccountCodes)
            ->pluck('fsuppliercode')
            ->flip()
            ->all();

        foreach ($rowsDt as $index => $row) {
            $accountCode = trim((string) ($row['faccount'] ?? ''));
            $subaccountCode = trim((string) ($row['fsubaccount'] ?? ''));
            $lineNo = $index + 1;

            if ($accountCode === '') {
                continue;
            }

            $account = $accounts->get($accountCode);
            $hasSub = (int) ($account->fhavesubaccount ?? 0) === 1;
            $rawType = strtoupper(trim((string) ($account->ftypesubaccount ?? 'S')));
            $subType = match ($rawType) {
                'C', 'CUSTOMER' => 'C',
                'P', 'SUPPLIER' => 'P',
                default => 'S',
            };
            $label = match ($subType) {
                'C' => 'Customer',
                'P' => 'Supplier',
                default => 'Sub Account',
            };

            if ($hasSub && $subaccountCode === '') {
                return "{$label} wajib dipilih untuk account {$accountCode} (baris {$lineNo}).";
            }

            if (! $hasSub && $subaccountCode !== '') {
                return "Sub Account tidak boleh diisi untuk account {$accountCode} (baris {$lineNo}).";
            }

            if ($hasSub && $subaccountCode !== '') {
                if ($subType === 'C' && ! isset($customers[$subaccountCode])) {
                    return "Customer {$subaccountCode} yang dipilih pada baris {$lineNo} tidak ditemukan.";
                } elseif ($subType === 'P' && ! isset($suppliers[$subaccountCode])) {
                    return "Supplier {$subaccountCode} yang dipilih pada baris {$lineNo} tidak ditemukan.";
                } elseif ($subType === 'S' && ! isset($subaccounts[$subaccountCode])) {
                    return "Sub Account {$subaccountCode} yang dipilih pada baris {$lineNo} tidak ditemukan.";
                }
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

    private function getAccountsData()
    {
        return DB::table('account')
            ->select('faccid', 'faccount', 'faccname', 'fnonactive', 'fhavesubaccount', 'ftypesubaccount')
            ->where('fnonactive', '0')
            ->where('fend', '1')
            ->orderBy('account')
            ->get();
    }

    private function getSubaccountsData()
    {
        return DB::table('mssubaccount')
            ->select('fsubaccountid', 'fsubaccountcode', 'fsubaccountname')
            ->where('fnonactive', '0')
            ->orderBy('fsubaccountcode')
            ->get();
    }

    private function getCustomersData()
    {
        return DB::table('mscustomer')
            ->select('fcustomerid as fsubaccountid', 'fcustomercode as fsubaccountcode', 'fcustomername as fsubaccountname')
            ->whereRaw("COALESCE(TRIM(CAST(fnonactive AS TEXT)), '0') != '1'")
            ->orderBy('fcustomercode')
            ->get();
    }

    private function getSuppliersData()
    {
        return DB::table('mssupplier')
            ->select('fsupplierid as fsubaccountid', 'fsuppliercode as fsubaccountcode', 'fsuppliername as fsubaccountname')
            ->whereRaw("COALESCE(TRIM(CAST(fnonactive AS TEXT)), '0') != '1'")
            ->orderBy('fsuppliercode')
            ->get();
    }
}
