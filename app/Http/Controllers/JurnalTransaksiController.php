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
    private const GENERAL_JOURNAL_TYPE = 'SJU';
    private const PURCHASE_JOURNAL_TYPE = 'JBL';
    private const REFERENCE_ALLOWED_ACCOUNT_NAMES = [
        'HUTANGDAGANG',
        'PIUTANGDAGANG',
        'RETJUALBLMPOTPIUTANG',
        'RETBELIBLMPOTHUTANG',
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

    private function resolveJournalPageMeta(?string $journalType = null): array
    {
        $type = strtoupper(trim((string) $journalType));
        $isPurchaseJournal = $type === self::PURCHASE_JOURNAL_TYPE;

        return [
            'journalType' => $type,
            'isPurchaseJournal' => $isPurchaseJournal,
            'pageTitle' => $isPurchaseJournal ? 'Jurnal Faktur Pembelian' : 'Jurnal Transaksi',
        ];
    }

    private function resolveJournalIndexRouteParams(?string $journalType = null): array
    {
        return strtoupper(trim((string) $journalType)) === self::PURCHASE_JOURNAL_TYPE
            ? ['journal_type' => self::PURCHASE_JOURNAL_TYPE]
            : [];
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

            $orderColIdx = $request->input('order.0.column', 0);
            $orderDir = $request->input('order.0.dir', 'asc');
            $sortableColumns = ['fjurnalno', 'fjurnaldate', 'fbranchcode', 'fbalance_rp', 'fjurnalnote'];

            if (isset($sortableColumns[$orderColIdx])) {
                $query->orderBy($sortableColumns[$orderColIdx], $orderDir);
            } else {
                $query->orderBy('fjurnalmtid', 'desc');
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
                    'fjurnaldate' => Carbon::parse($row->fjurnaldate)->format('d/m/Y'),
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
            'pageMeta'
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
                'tr_pod.fnouref as fnouref',
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
        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            $lockKey = crc32('PBR|'.$kodeCabang.'|'.$date->format('Y-m'));
            DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

            $last = DB::table('tr_poh')
                ->where('fpono', 'like', $prefix.'%')
                ->selectRaw("MAX(CAST(split_part(fpono, '.', 5) AS int)) AS lastno")
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

        $dt = DB::table('jurnaldt')
            ->leftJoin('account as a', 'a.faccount', '=', 'jurnaldt.faccount')
            ->leftJoin('mssubaccount as sa', 'sa.fsubaccountcode', '=', 'jurnaldt.fsubaccount')
            ->where('jurnaldt.fjurnalno', $fjurnalno)
            ->orderBy('jurnaldt.flineno')
            ->get([
                'jurnaldt.*',
                'a.faccname as account_name',
                'sa.fsubaccountname as subaccount_name',
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
        $supplier = Supplier::all();

        $accounts = DB::table('account')
            ->select('faccid', 'faccount', 'faccname', 'fnonactive', 'fhavesubaccount')
            ->where('fnonactive', '0')
            ->where('fend', '1')
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
            ->when(
                ! is_numeric($raw),
                fn ($q) => $q->where('fcabangkode', $raw)->orWhere('fcabangname', $raw)
            )
            ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

        $fcabang = $branch->fcabangname ?? (string) $raw;
        $fbranchcode = $branch->fcabangkode ?? (string) $raw;

        $requestedJournalType = strtoupper(trim((string) request()->query('journal_type', '')));
        $pageMeta = $this->resolveJournalPageMeta($requestedJournalType);
        $fixedJournalType = $pageMeta['isPurchaseJournal'] ? self::PURCHASE_JOURNAL_TYPE : null;
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

        $referenceAllowedAccountCodes = $this->resolveReferenceAllowedAccountCodes();

        $journalTypes = DB::table('tbmaster')
            ->whereRaw('TRIM(ftblcode) = ?', ['JURNAL'])
            ->orderBy('fmastercode')
            ->get()
            ->map(function ($item) {
                $item->fmastercode = trim($item->fmastercode);
                $item->fmastername = trim($item->fmastername);
                return $item;
            });

        return view('jurnaltransaksi.create', [
            'newtr_prh_code' => $newtr_prh_code,
            'accounts' => $accounts,
            'subaccounts' => $subaccounts,
            'supplier' => $supplier,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'products' => $products,
            'referenceAllowedAccountCodes' => $referenceAllowedAccountCodes,
            'pageTitle' => $pageMeta['pageTitle'],
            'fixedJournalType' => $fixedJournalType,
            'journalType' => $pageMeta['journalType'],
            'journalTypes' => $journalTypes,
            'indexUrl' => route('jurnaltransaksi.index', $this->resolveJournalIndexRouteParams($pageMeta['journalType'])),
        ]);
    }

    public function store(Request $request)
    {
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
            return back()->withInput()->withErrors([
                'detail' => 'Minimal harus ada 1 baris jurnal yang lengkap dan jumlahnya lebih dari 0.',
            ]);
        }

        // ── Validasi balance debit = kredit ──
        if ($validationMessage = $this->validateUniqueJournalReferenceUsage($rowsDt)) {
            return back()->withInput()->withErrors([
                'detail' => $validationMessage,
            ]);
        }

        if (round($totalDebit, 2) !== round($totalKredit, 2)) {
            return back()->withInput()->withErrors([
                'detail' => sprintf(
                    'Jurnal belum seimbang. Total Debit Rp %s dan Total Kredit Rp %s.',
                    number_format($totalDebit, 2, ',', '.'),
                    number_format($totalKredit, 2, ',', '.')
                ),
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
            $fjurnalno = trim((string) $request->input('fjurnalno', ''));

            if (empty($fjurnalno)) {
                $prefix = sprintf('%s.%s.%s.%s.', $fjurnaltype, $kodeCabang, $yy, $mm);
                $driver = DB::getDriverName();
                if ($driver === 'pgsql') {
                    $lockKey = crc32('JURNAL|'.$fjurnaltype.'|'.$kodeCabang.'|'.$fjurnaldate->format('Y-m'));
                    DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

                    $lastNo = DB::table('jurnalmt')
                        ->where('fjurnalno', 'like', $prefix.'%')
                        ->selectRaw("MAX(CAST(split_part(fjurnalno, '.', 5) AS int)) AS lastno")
                        ->value('lastno');
                } else {
                    $lastNo = DB::table('jurnalmt')
                        ->where('fjurnalno', 'like', $prefix.'%')
                        ->get()
                        ->map(function ($row) {
                            $parts = explode('.', $row->fjurnalno);
                            return isset($parts[4]) ? (int) $parts[4] : 0;
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

        return redirect()
            ->route('jurnaltransaksi.create', array_merge(
                ['fcurrid' => $newJurnalMtId],
                $this->resolveJournalIndexRouteParams($fjurnaltype)
            ))
            ->with('success', $fjurnaltype === self::PURCHASE_JOURNAL_TYPE ? 'Jurnal faktur pembelian berhasil disimpan.' : 'Jurnal transaksi berhasil disimpan.')
            ->with('success_prompt', [
                'type'         => 'jurnaltransaksi_create',
                'redirect_url' => $printUrl,
            ]);
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

        $warehouses = collect();

        [$jurnaltransaksi, $savedItems] = $this->getJournalTransactionFormData($fstockmtid);
        ['fcabang' => $fcabang, 'fbranchcode' => $fbranchcode] = $this->resolveBranchContext($jurnaltransaksi->fbranchcode ?? null);
        if ($message = $this->getPostedPeriodLockMessage($jurnaltransaksi->fjurnaldate, 'Jurnal ini')) {
            return redirect()->route('jurnaltransaksi.view', ['fcurrid' => $fstockmtid] + $this->resolveJournalIndexRouteParams($jurnaltransaksi->fjurnaltype))->with('error', $message);
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

        $referenceAllowedAccountCodes = $this->resolveReferenceAllowedAccountCodes();
        $indexUrl = route('jurnaltransaksi.index', $this->resolveJournalIndexRouteParams($jurnaltransaksi->fjurnaltype));

        $journalTypes = DB::table('tbmaster')
            ->whereRaw('TRIM(ftblcode) = ?', ['JURNAL'])
            ->orderBy('fmastercode')
            ->get()
            ->map(function ($item) {
                $item->fmastercode = trim($item->fmastercode);
                $item->fmastername = trim($item->fmastername);
                return $item;
            });

        return view('jurnaltransaksi.edit', [
            'supplier' => $supplier,
            'selectedSupplierCode' => $selectedSupplierCode,
            'fcabang' => $fcabang,
            'accounts' => $accounts,
            'subaccounts' => $subaccounts,
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
            'action' => 'edit',
            'pageTitle' => $jurnaltransaksi->fjurnaltype === self::PURCHASE_JOURNAL_TYPE ? 'Edit Jurnal Faktur Pembelian' : 'Edit Jurnal Transaksi',
            'lockJournalType' => $jurnaltransaksi->fjurnaltype === self::PURCHASE_JOURNAL_TYPE,
            'journalTypes' => $journalTypes,
            'indexUrl' => $indexUrl,
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

        $referenceAllowedAccountCodes = $this->resolveReferenceAllowedAccountCodes();
        $indexUrl = route('jurnaltransaksi.index', $this->resolveJournalIndexRouteParams($jurnaltransaksi->fjurnaltype));

        $journalTypes = DB::table('tbmaster')
            ->whereRaw('TRIM(ftblcode) = ?', ['JURNAL'])
            ->orderBy('fmastercode')
            ->get()
            ->map(function ($item) {
                $item->fmastercode = trim($item->fmastercode);
                $item->fmastername = trim($item->fmastername);
                return $item;
            });

        $currentType = trim($jurnaltransaksi->fjurnaltype);
        $hasCurrentType = $journalTypes->contains(function ($item) use ($currentType) {
            return $item->fmastercode === $currentType;
        });

        if (!$hasCurrentType && $currentType !== '') {
            $journalTypes->push((object)[
                'fmastercode' => $currentType,
                'fmastername' => $currentType === self::PURCHASE_JOURNAL_TYPE ? 'JURNAL PEMBELIAN (JBL)' : $currentType,
            ]);
        }

        return view('jurnaltransaksi.view', [
            'supplier' => $supplier,
            'selectedSupplierCode' => $selectedSupplierCode,
            'fcabang' => $fcabang,
            'accounts' => $accounts,
            'subaccounts' => $subaccounts,
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
            'pageTitle' => $jurnaltransaksi->fjurnaltype === self::PURCHASE_JOURNAL_TYPE ? 'View Jurnal Faktur Pembelian' : 'View Jurnal Transaksi',
            'indexUrl' => $indexUrl,
        ]);
    }

    public function update(Request $request, $fstockmtid)
    {
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
            return redirect()->route('jurnaltransaksi.view', ['fcurrid' => $fstockmtid] + $this->resolveJournalIndexRouteParams($header->fjurnaltype))->with('error', $message);
        }

        $fjurnaldate = Carbon::parse($request->fjurnaldate)->startOfDay();
        $this->ensureCreateDateWithinEditPeriod($fjurnaldate, $header->fjurnaldate);
        $fjurnaltype = $header->fjurnaltype === self::PURCHASE_JOURNAL_TYPE
            ? self::PURCHASE_JOURNAL_TYPE
            : strtoupper(trim((string) $request->input('fjurnaltype', 'SJU')));
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
            return back()->withInput()->withErrors([
                'detail' => 'Minimal harus ada 1 baris jurnal yang lengkap dan jumlahnya lebih dari 0.',
            ]);
        }

        if ($validationMessage = $this->validateUniqueJournalReferenceUsage($rowsDt, $header->fjurnalno)) {
            return back()->withInput()->withErrors([
                'detail' => $validationMessage,
            ]);
        }

        if (round($totalDebit, 2) !== round($totalKredit, 2)) {
            return back()->withInput()->withErrors([
                'detail' => sprintf(
                    'Jurnal belum seimbang. Total Debit Rp %s dan Total Kredit Rp %s.',
                    number_format($totalDebit, 2, ',', '.'),
                    number_format($totalKredit, 2, ',', '.')
                ),
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

        return redirect()
            ->route('jurnaltransaksi.edit', array_merge(
                ['fcurrid' => $fstockmtid],
                $this->resolveJournalIndexRouteParams($fjurnaltype)
            ))
            ->with('success', $fjurnaltype === self::PURCHASE_JOURNAL_TYPE ? 'Jurnal faktur pembelian ' . trim((string) $header->fjurnalno) . ' berhasil diupdate.' : 'Jurnal transaksi ' . trim((string) $header->fjurnalno) . ' berhasil diupdate.');
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

        $warehouses = collect();

        [$jurnaltransaksi, $savedItems] = $this->getJournalTransactionFormData($fstockmtid);
        ['fcabang' => $fcabang, 'fbranchcode' => $fbranchcode] = $this->resolveBranchContext($jurnaltransaksi->fbranchcode ?? null);
        if ($message = $this->getPostedPeriodLockMessage($jurnaltransaksi->fjurnaldate, 'Jurnal ini')) {
            return redirect()->route('jurnaltransaksi.view', ['fcurrid' => $fstockmtid] + $this->resolveJournalIndexRouteParams($jurnaltransaksi->fjurnaltype))->with('error', $message);
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

        return view('jurnaltransaksi.edit', [
            'supplier' => $supplier,
            'selectedSupplierCode' => $selectedSupplierCode,
            'fcabang' => $fcabang,
            'accounts' => $accounts,
            'subaccounts' => $subaccounts,
            'fbranchcode' => $fbranchcode,
            'warehouses' => $warehouses,
            'products' => $products,
            'productMap' => $productMap,
            'jurnaltransaksi' => $jurnaltransaksi,
            'pemakaianbarang' => $jurnaltransaksi,
            'savedItems' => $savedItems,
            'ppnAmount' => 0,
            'famountponet' => 0,
            'famountpo' => 0,
            'action' => 'delete',
            'pageTitle' => $jurnaltransaksi->fjurnaltype === self::PURCHASE_JOURNAL_TYPE ? 'Hapus Jurnal Faktur Pembelian' : 'Hapus Jurnal Transaksi',
            'lockJournalType' => true,
            'indexUrl' => route('jurnaltransaksi.index', $this->resolveJournalIndexRouteParams($jurnaltransaksi->fjurnaltype)),
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
                return redirect()->route('jurnaltransaksi.view', ['fcurrid' => $fstockmtid] + $this->resolveJournalIndexRouteParams($jurnaltransaksi->fjurnaltype))->with('error', $message);
            }

            DB::transaction(function () use ($fstockmtid) {
                DB::table('jurnaldt')->where('fjurnalmtid', $fstockmtid)->delete();
                DB::table('jurnalmt')->where('fjurnalmtid', $fstockmtid)->delete();
            });

            $redirectUrl = route('jurnaltransaksi.index', $this->resolveJournalIndexRouteParams($jurnaltransaksi->fjurnaltype ?? null));
            $message = (($jurnaltransaksi->fjurnaltype ?? null) === self::PURCHASE_JOURNAL_TYPE ? 'Data jurnal faktur pembelian ' : 'Data jurnal transaksi ').trim((string) $jurnaltransaksi->fjurnalno).' berhasil dihapus.';

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'redirect' => $redirectUrl,
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
            ->where('fjurnalmtid', $journalId)
            ->first();

        if (! $header) {
            abort(404);
        }

        $details = DB::table('jurnaldt as d')
            ->leftJoin('account as a', 'a.faccount', '=', 'd.faccount')
            ->leftJoin('mssubaccount as s', 's.fsubaccountcode', '=', 'd.fsubaccount')
            ->where('d.fjurnalmtid', $journalId)
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
                's.fsubaccountid',
                's.fsubaccountname',
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
        ];

        $savedItems = $details->map(function ($row) {
            $label = trim((string) ($row->faccount ?? ''));
            $name = trim((string) ($row->faccname ?? ''));
            $subName = trim((string) ($row->fsubaccountname ?? ''));

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
