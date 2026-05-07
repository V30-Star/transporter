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

class AdjstockController extends Controller
{
    public function index(Request $request)
    {
        // --- 1. PERMISSIONS ---
        $canCreate = in_array('createPenerimaanBarang', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updatePenerimaanBarang', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deletePenerimaanBarang', explode(',', session('user_restricted_permissions', '')));
        $showActionsColumn = $canEdit || $canDelete;

        // --- 2. Handle Request AJAX dari DataTables ---
        if ($request->ajax()) {

            // Query dasar HANYA untuk 'ADJ' (Adjustment)
            $query = PenerimaanPembelianHeader::where('fstockmtcode', 'ADJ');

            // Total records (dengan filter 'ADJ')
            $totalRecords = PenerimaanPembelianHeader::where('fstockmtcode', 'ADJ')->count();

            // Handle Search (cari di No. Adjustment)
            if ($search = $request->input('search.value')) {
                $query->where('fstockmtno', 'like', "%{$search}%");
            }

            // Total records setelah filter search
            $filteredRecords = (clone $query)->count();

            // Handle Sorting
            $orderColIdx = $request->input('order.0.column', 0);
            $orderDir = $request->input('order.0.dir', 'desc');

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
        return view('adjstock.index', compact(
            'canCreate',
            'canEdit',
            'canDelete',
            'showActionsColumn'
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

        $prefix = sprintf('PO.%s.%s.%s.', $kodeCabang, $date->format('y'), $date->format('m'));

        // kunci per (branch, tahun-bulan) — TANPA bikin tabel baru
        $lockKey = crc32('PO|'.$kodeCabang.'|'.$date->format('Y-m'));
        DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

        $last = DB::table('tr_poh')
            ->where('fpono', 'like', $prefix.'%')
            ->selectRaw("MAX(CAST(split_part(fpono, '.', 5) AS int)) AS lastno")
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
            return redirect()->back()->with('error', 'Adjustment Stock tidak ditemukan.');
        }

        $dt = PenerimaanPembelianDetail::query()
            ->leftJoin('msprd as p', 'p.fprdid', '=', 'trstockdt.fprdcodeid')
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

        $canApproval = in_array('approvalpr', explode(',', session('user_restricted_permissions', '')));

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
        )->orderBy('fprdname')->get();

        return view('adjstock.create', [
            'newtr_prh_code' => $newtr_prh_code,
            'perms' => ['can_approval' => $canApproval],
            'warehouses' => $warehouses,
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
            $allowNegativeStockQty = (string) env('STOCKBOLEHMINUS', '0') === '1';
            // =========================
            // TAHAP 1: VALIDASI INPUT
            // =========================
            $request->validate([
                'fstockmtno' => ['nullable', 'string', 'max:100'],
                'fstockmtdate' => ['required', 'date'],
                'ffrom' => ['nullable', 'string', 'max:10'],
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
                            $fail($allowNegativeStockQty ? 'Qty tidak boleh 0.' : 'Qty harus lebih besar dari 0.');
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
            ]);

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
                    ->get(['fprdid', 'fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'])
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

                $price = (float) ($prices[$i] ?? 0);
                $amount = $qty * $price;
                $subtotal += $amount;

                $rowsDt[] = [
                    'fprdcode' => $meta->fprdcode,
                    'fprdcodeid' => $meta->fprdid,
                    'fnoacak' => $this->normalizeRandomNumber(null, $usedNoAcaks),
                    'frefdtno' => trim((string) ($refdtno[$i] ?? '')) ?: null,
                    'fqty' => $qty,
                    'fqtyremain' => $qty,
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
                    'fqtykecil' => $qty,
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
                        ? 'Minimal satu item valid harus diisi dengan qty tidak sama dengan 0.'
                        : 'Minimal satu item valid harus diisi.',
                ]);
            }

            if ($validationMessage = $this->validateUniqueReferenceUsage($rowsDt)) {
                return back()->withInput()->withErrors(['detail' => $validationMessage]);
            }

            // =========================
            // TAHAP 4: PERSIAPAN DATA HEADER
            // =========================
            $fstockmtdate = \Carbon\Carbon::parse($request->fstockmtdate)->startOfDay();
            $ppnAmount = (float) $request->input('famountpopajak', 0);
            $grandTotal = $subtotal + $ppnAmount;

            $headerData = [
                'fstockmtno' => trim((string) $request->input('fstockmtno')),
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

                    $prefix = sprintf('%s.%s.%s.%s.', $headerData['fstockmtcode'], $kodeCabang, $headerData['fstockmtdate']->format('y'), $headerData['fstockmtdate']->format('m'));

                    $lockKey = crc32('STOCKMT|'.$headerData['fstockmtcode'].'|'.$kodeCabang.'|'.$headerData['fstockmtdate']->format('Y-m'));
                    DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

                    $last = DB::table('trstockmt')
                        ->where('fstockmtno', 'like', $prefix.'%')
                        ->selectRaw("MAX(CAST(split_part(fstockmtno, '.', 5) AS int)) AS lastno")
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

            return redirect()
                ->route('adjstock.create')
                ->with('success', "Transaksi {$finalNo} berhasil disimpan.");
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['fatal' => 'Terjadi error: '.$e->getMessage()]);
        }
    }

    public function edit($fstockmtid)
    {
        $supplier = Supplier::all();

        $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

        $branch = DB::table('mscabang')
            ->when(is_numeric($raw), fn ($q) => $q->where('fcabangid', (int) $raw))
            ->when(! is_numeric($raw), fn ($q) => $q
                ->where('fcabangkode', $raw)
                ->orWhere('fcabangname', $raw))
            ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

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

        $fcabang = $branch->fcabangname ?? (string) $raw;
        $fbranchcode = $branch->fcabangkode ?? (string) $raw;

        // 1. Ambil data Header (trstockmt) DAN relasi Details (trstockdt)
        // Biarkan query ini. Sekarang $fstockmtid di sini adalah integer (misal: 8)
        $adjstock = PenerimaanPembelianHeader::with([
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
            ->findOrFail($fstockmtid); // Temukan header berdasarkan $fstockmtid dari URL

        $usageLockMessage = $this->getUsageLockMessage($adjstock);

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

        $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

        $branch = DB::table('mscabang')
            ->when(is_numeric($raw), fn ($q) => $q->where('fcabangid', (int) $raw))
            ->when(! is_numeric($raw), fn ($q) => $q
                ->where('fcabangkode', $raw)
                ->orWhere('fcabangname', $raw))
            ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

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

        $fcabang = $branch->fcabangname ?? (string) $raw;
        $fbranchcode = $branch->fcabangkode ?? (string) $raw;

        // 1. Ambil data Header (trstockmt) DAN relasi Details (trstockdt)
        // Biarkan query ini. Sekarang $fstockmtid di sini adalah integer (misal: 8)
        $adjstock = PenerimaanPembelianHeader::with([
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
            ->findOrFail($fstockmtid); // Temukan header berdasarkan $fstockmtid dari URL

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

        return view('adjstock.view', [
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
        ]);
    }

    public function update(Request $request, $fstockmtid)
    {
        $allowNegativeStockQty = (string) env('STOCKBOLEHMINUS', '0') === '1';
        // =========================
        // 1) VALIDASI INPUT
        // =========================
        $validated = $request->validate([
            'fstockmtno' => ['nullable', 'string', 'max:100'],
            'fstockmtdate' => ['required', 'date'],
            'ffrom' => ['nullable', 'string', 'max:10'],
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
                        $fail($allowNegativeStockQty ? 'Qty tidak boleh 0.' : 'Qty harus lebih besar dari 0.');
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
        ]);
        // =========================
        // 2) AMBIL DATA MASTER & HEADER
        // =========================
        $header = PenerimaanPembelianHeader::findOrFail($fstockmtid);
        if ($message = $this->getUsageLockMessage($header)) {
            return redirect()->route('adjstock.index')->with('error', $message);
        }

        $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
        $ffrom = $request->input('ffrom');
        $fprdjadi = $request->input('fprdjadi');
        $ftrancode = $request->input('ftrancode');
        $fket = trim((string) $request->input('fket', ''));
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

        // =========================
        // 4) LOGIC PROD META & RAKIT DETAIL
        // =========================
        $uniqueCodes = array_values(array_unique(array_filter(array_map(fn ($c) => trim((string) $c), $codes))));
        $prodMeta = DB::table('msprd')
            ->whereIn('fprdcode', $uniqueCodes)
            ->get(['fprdid', 'fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'])
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

        $rowsDt = [];
        $usedNoAcaks = [];
        $subtotal = 0.0;
        $rowCount = count($codes);

        for ($i = 0; $i < $rowCount; $i++) {
            $code = trim((string) ($codes[$i] ?? ''));
            $sat = trim((string) ($satuans[$i] ?? ''));
            $rref = trim((string) ($refdtno[$i] ?? ''));
            $rnour = $nourefs[$i] ?? null;
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

            $prdId = $meta->fprdid;

            if ($sat === '') {
                $sat = $pickDefaultSat($meta);
            }
            $sat = mb_substr($sat, 0, 5);
            if ($sat === '') {
                continue;
            }

            $amount = $qty * $price;
            $subtotal += $amount;

            $rowsDt[] = [
                'fprdcode' => $code,
                'fprdcodeid' => $prdId,
                'fnoacak' => $this->normalizeRandomNumber(null, $usedNoAcaks),
                'frefdtno' => $rref,
                'fqty' => $qty,
                'fqtyremain' => $qty,
                'fprice' => $price,
                'fprice_rp' => $price * $frate,
                'ftotprice' => $amount,
                'ftotprice_rp' => $amount * $frate,
                'fuserupdate' => (Auth::user()->fname ?? 'system'),
                'fdatetime' => $now, // Tetap gunakan fdatetime
                'fketdt' => '',
                'fcode' => '0',
                'frefso' => null,
                'fdesc' => $desc,
                'fsatuan' => $sat,
                'fqtykecil' => $qty,
                'fclosedt' => '0',
                'fdiscpersen' => 0,
                'fbiaya' => 0,
                'fstockmtcode' => null, // Akan diisi di Tahap 5
                'fstockmtno' => null, // Akan diisi di Tahap 5
            ];
        }

        if (empty($rowsDt)) {
            return back()->withInput()->withErrors([
                'detail' => $allowNegativeStockQty
                    ? 'Minimal satu item valid (Kode, Satuan, Qty tidak boleh 0).'
                    : 'Minimal satu item valid (Kode, Satuan, Qty > 0).',
            ]);
        }

        if ($validationMessage = $this->validateUniqueReferenceUsage($rowsDt, $header->fstockmtno)) {
            return back()->withInput()->withErrors([
                'detail' => $validationMessage,
            ]);
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
            $fbranchcode,
            $fcurrency,
            $frate,
            &$rowsDt,
            $subtotal,
            $ppnAmount,
            $grandTotal
        ) {

            // ---- 5.1. Cek Kode Cabang ----
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
                if (! $kodeCabang) {
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
                'fuserupdate' => (Auth::user()->fname ?? 'system'),
                'fbranchcode' => $kodeCabang,
            ];

            $header->update($masterData);

            // ---- 5.3. HAPUS DETAIL LAMA ----
            DB::table('trstockdt')->where('fstockmtno', $header->fstockmtno)->delete();

            // ---- 5.4. INSERT DETAIL BARU ----
            $fstockmtcode = $header->fstockmtcode;
            $fstockmtno = $header->fstockmtno;
            $nextNouRef = 1;

            foreach ($rowsDt as &$r) {
                $r['fstockmtcode'] = $fstockmtcode;
                $r['fstockmtno'] = $fstockmtno;
            }
            unset($r);

            DB::table('trstockdt')->insert($rowsDt);
        });

        return redirect()
            ->route('adjstock.index')
            ->with('success', "Transaksi {$header->fstockmtno} berhasil diperbarui.");
    }

    public function delete($fstockmtid)
    {
        $supplier = Supplier::all();

        $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

        $branch = DB::table('mscabang')
            ->when(is_numeric($raw), fn ($q) => $q->where('fcabangid', (int) $raw))
            ->when(! is_numeric($raw), fn ($q) => $q
                ->where('fcabangkode', $raw)
                ->orWhere('fcabangname', $raw))
            ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

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

        $fcabang = $branch->fcabangname ?? (string) $raw;
        $fbranchcode = $branch->fcabangkode ?? (string) $raw;

        // 1. Ambil data Header (trstockmt) DAN relasi Details (trstockdt)
        // Biarkan query ini. Sekarang $fstockmtid di sini adalah integer (misal: 8)
        $adjstock = PenerimaanPembelianHeader::with([
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
            ->findOrFail($fstockmtid); // Temukan header berdasarkan $fstockmtid dari URL

        $usageLockMessage = $this->getUsageLockMessage($adjstock);

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
            if ($message = $this->getUsageLockMessage($adjstock)) {
                return redirect()->route('adjstock.index')->with('error', $message);
            }
            DB::transaction(function () use ($adjstock) {
                DB::table('trstockdt')
                    ->where('fstockmtno', $adjstock->fstockmtno)
                    ->delete();

                $adjstock->delete();
            });

            return redirect()->route('adjstock.index')->with('success', 'Data Adjustment Stock '.$adjstock->fstockmtno.' berhasil dihapus.');
        } catch (\Exception $e) {
            // Jika terjadi kesalahan saat menghapus, kembali ke halaman delete dengan pesan error
            return redirect()->route('adjstock.delete', $fstockmtid)->with('error', 'Gagal menghapus data: '.$e->getMessage());
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

        return 'Adjustment Stock '.$header->fstockmtno.' tidak dapat diubah atau dihapus karena sudah digunakan pada transaksi lain: '.$usedBy->implode(', ').'.';
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
                return 'Nomor referensi '.$referenceNo.' sudah pernah dibuat di transaksi nomor '.trim((string) ($existing->transaction_no ?? '')).'.';
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
