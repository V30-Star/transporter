<?php

namespace App\Http\Controllers;

use App\Models\Tr_prh;
use App\Models\Tr_prd;
use App\Models\Tr_poh;
use App\Models\Tr_pod;
use App\Models\Supplier;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException; // <-- TAMBAHKAN INI
use App\Models\PenerimaanPembelianDetail;
use App\Models\PenerimaanPembelianHeader;
use App\Mail\ApprovalEmailPo;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon; // sekalian biar aman untuk tanggal

class MutasiController extends Controller
{
    public function index(Request $request)
    {
        // --- 1. PERBAIKAN PERMISSIONS ---
        // Saya asumsikan ini nama permission yang benar untuk modul ini
        $canCreate = in_array('createPenerimaanBarang', explode(',', session('user_restricted_permissions', '')));
        $canEdit   = in_array('updatePenerimaanBarang', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deletePenerimaanBarang', explode(',', session('user_restricted_permissions', '')));
        $showActionsColumn = $canEdit || $canDelete; // Anda bisa tambahkan $canPrint jika ada

        // --- 2. Handle Request AJAX dari DataTables ---
        if ($request->ajax()) {

            // Query dasar HANYA untuk 'MUT' (Receiving)
            $query = PenerimaanPembelianHeader::where('fstockmtcode', 'MUT');

            // Total records (dengan filter 'MUT')
            $totalRecords = PenerimaanPembelianHeader::where('fstockmtcode', 'MUT')->count();

            // Handle Search (cari di No. Penerimaan)
            if ($search = $request->input('search.value')) {
                $query->where('fstockmtno', 'like', "%{$search}%");
            }

            // Total records setelah filter search
            $filteredRecords = (clone $query)->count();

            // Handle Sorting
            $orderColIdx = $request->input('order.0.column', 0);
            $orderDir = $request->input('order.0.dir', 'asc');
            // Kolom di tabel: 0 = fstockmtno, 1 = fstockmtdate
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
                ->get(['fstockmtid', 'fstockmtno', 'fstockmtdate']); // fstockmtcode tidak perlu, krn sudah pasti RCV

            // Format Data (Tombol dibuat di sini)
            $data = $records->map(function ($row) use ($canEdit, $canDelete) {

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
                    'fstockmtno'   => $row->fstockmtno,
                    // Format tanggal agar rapi di tabel
                    'fstockmtdate' => Carbon::parse($row->fstockmtdate)->format('d/m/Y'),
                    'actions'      => $actions
                ];
            });

            // 9. Kirim Response JSON
            return response()->json([
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data'            => $data
            ]);
        }

        // --- 3. Handle Request non-AJAX (Saat load halaman) ---
        return view('mutasi.index', compact(
            'canCreate',
            'canEdit',
            'canDelete',
            'showActionsColumn'
        ));
    }

    public function pickable(Request $request)
    {
        // Base query dengan JOIN
        $query = DB::table('tr_poh')
            ->leftJoin('mssupplier', 'tr_poh.fsupplier', '=', 'mssupplier.fsupplierid')
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
            'data' => $data
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
                'fprhid'     => $header->fpohid,
                'fprno'     => $header->fpono,
                'fsupplier' => trim($header->fsupplier ?? ''),
                'fprdate'   => optional($header->fpodate)->format('Y-m-d H-i-s'),
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
            $needle = trim((string)$branch);
            if (is_numeric($needle)) {
                $kodeCabang = DB::table('mscabang')->where('fcabangid', (int)$needle)->value('fcabangkode');
            } else {
                $kodeCabang = DB::table('mscabang')->whereRaw('LOWER(fcabangkode)=LOWER(?)', [$needle])->value('fcabangkode')
                    ?: DB::table('mscabang')->whereRaw('LOWER(fcabangname)=LOWER(?)', [$needle])->value('fcabangkode');
            }
        }
        if (!$kodeCabang) $kodeCabang = 'NA';

        $prefix = sprintf('PO.%s.%s.%s.', $kodeCabang, $date->format('y'), $date->format('m'));

        // kunci per (branch, tahun-bulan) — TANPA bikin tabel baru
        $lockKey = crc32('PO|' . $kodeCabang . '|' . $date->format('Y-m'));
        DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

        $last = DB::table('tr_poh')
            ->where('fpono', 'like', $prefix . '%')
            ->selectRaw("MAX(CAST(split_part(fpono, '.', 5) AS int)) AS lastno")
            ->value('lastno');

        $next = (int)$last + 1;
        return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    }

    public function print(string $fstockmtno)
    {
        $supplierSub = Supplier::select('fsupplierid', 'fsuppliercode', 'fsuppliername');

        $hdr = PenerimaanPembelianHeader::query()
            ->leftJoinSub($supplierSub, 's', function ($join) {
                $join->on('s.fsupplierid', '=', 'trstockmt.fsupplier');
            })
            ->leftJoin('mscabang as c', 'c.fcabangkode', '=', 'trstockmt.fbranchcode')
            ->leftJoin('mswh as w', 'w.fwhid', '=', 'trstockmt.ffrom')
            ->where('trstockmt.fstockmtno', $fstockmtno)
            ->first([
                'trstockmt.*',
                's.fsuppliername as supplier_name',
                'c.fcabangname as cabang_name',
                'w.fwhname as fwhnamen',
            ]);

        if (!$hdr) {
            return redirect()->back()->with('error', 'PO tidak ditemukan.');
        }

        $dt = PenerimaanPembelianDetail::query()
            ->leftJoin('msprd as p', function ($join) {
                $join->whereRaw(
                    'p.fprdid = COALESCE(trstockdt.fprdcodeid, CASE WHEN btrim(trstockdt.fprdcode::text) ~ ? THEN cast(btrim(trstockdt.fprdcode::text) as integer) ELSE NULL END)',
                    ['^[0-9]+$']
                );
            })
            ->where('trstockdt.fstockmtno', $fstockmtno)
            ->orderBy('trstockdt.fprdcode')
            ->get([
                'trstockdt.*',
                'p.fprdname as product_name',
                DB::raw('COALESCE(p.fprdcode, NULLIF(trim(trstockdt.fprdcode::text), \'\')) as product_code'),
                'p.fminstock as stock',
                'trstockdt.fqtyremain',
            ]);

        $fmt = fn($d) => $d
            ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d F Y')
            : '-';

        return view('mutasi.print', [
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
            ->when(is_numeric($raw), fn($q) => $q->where('fcabangid', (int)$raw))
            ->when(
                !is_numeric($raw),
                fn($q) => $q->where('fcabangkode', $raw)->orWhere('fcabangname', $raw)
            )
            ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

        $canApproval = in_array('approvalpr', explode(',', session('user_restricted_permissions', '')));

        $fcabang = $branch->fcabangname ?? (string)$raw;
        $fbranchcode = $branch->fcabangkode ?? (string)$raw;

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
            $validated = $request->validate([
                'fstockmtno'     => ['nullable', 'string', 'max:100'],
                'fstockmtdate'   => ['required', 'date'],
                'ffrom'          => ['required', 'integer'],  // ✅ TAMBAHKAN required
                'fto'            => ['required', 'integer'],  // ✅ TAMBAHKAN required
                'ftrancode'      => ['nullable', 'string', 'max:3'],
                'fket'           => ['nullable', 'string', 'max:50'],
                'fbranchcode'    => ['nullable', 'string', 'max:20'],
                'fitemcode'      => ['required', 'array', 'min:1'],
                'fitemcode.*'    => ['required', 'string', 'max:50'],
                'fsatuan'        => ['nullable', 'array'],
                'fsatuan.*'      => ['nullable', 'string', 'max:20'],
                'frefno.*'       => ['nullable', 'string', 'max:20'],
                'fsupplier'      => ['nullable', 'integer'],
                'fqty'           => ['required', 'array'],
                'fqty.*'         => ['required', 'numeric', 'min:0.01'],
                'fprice.*'       => ['numeric', 'min:0'],
                'fdesc'          => ['nullable', 'array'],
                'fdesc.*'        => ['nullable', 'string', 'max:500'],
                'fcurrency'      => ['nullable', 'string', 'max:5'],
                'frate'          => ['nullable', 'numeric', 'min:0'],
                'famountpopajak' => ['nullable', 'numeric', 'min:0'],
                'frefso'         => ['nullable', 'array'],
                'frefso.*'       => ['nullable', 'string', 'max:100'],
                'frefsoid'       => ['nullable', 'array'],
                'frefsoid.*'     => ['nullable', 'integer'],
            ]);

            $uniqueCodes = array_values(array_unique(
                array_filter(
                    array_map(fn($c) => trim((string)$c), $request->input('fitemcode', []))
                )
            ));

            $prodMeta = collect();
            if (!empty($uniqueCodes)) {
                $prodMeta = DB::table('msprd')
                    ->whereIn('fprdcode', $uniqueCodes)
                    ->get(['fprdid', 'fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'])
                    ->keyBy('fprdcode');

                if ($prodMeta->count() < count($uniqueCodes)) {
                    $notFound = array_diff($uniqueCodes, $prodMeta->keys()->toArray());
                }
            }

            $pickDefaultSat = function (?object $meta): string {
                if (!$meta) return '';
                foreach (['fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'] as $k) {
                    $v = trim((string)($meta->$k ?? ''));
                    if ($v !== '') {
                        return mb_substr($v, 0, 5);
                    }
                }
                return '';
            };

            $rowsDt   = [];
            $subtotal = 0.0;
            $userid   = auth('sysuser')->user()->fsysuserid ?? 'admin';
            $now      = now();
            $frate    = (float)$request->input('frate', 1);
            if ($frate <= 0) $frate = 1;

            $codes    = $request->input('fitemcode', []);
            $satuans  = $request->input('fsatuan', []);
            $refdtno  = $request->input('frefdtno', []);
            $frefso   = $request->input('frefso', []);
            $frefsoid = $request->input('frefsoid', []);
            $qtys     = $request->input('fqty', []);
            $prices   = $request->input('fprice', []);
            $descs    = $request->input('fdesc', []);

            $rowCount = count($codes);

            for ($i = 0; $i < $rowCount; $i++) {
                $code  = trim((string)($codes[$i]   ?? ''));
                $sat   = trim((string)($satuans[$i] ?? ''));
                $rref  = trim((string)($refdtno[$i] ?? ''));
                $qty   = (float)($qtys[$i]    ?? 0);
                $price = (float)($prices[$i]  ?? 0);
                $desc  = (string)($descs[$i]   ?? '');

                if ($code === '' || $qty <= 0) {
                    continue;
                }

                $meta = $prodMeta[$code] ?? null;
                if (! $meta) {
                    continue;
                }

                $prdId = (int) $meta->fprdid;
                $prdCodeStr = mb_substr((string) ($meta->fprdcode ?? $code), 0, 50);

                $produk = DB::table('msprd')
                    ->where('fprdcode', $code)
                    ->select('fprdid', 'fsatuanbesar', 'fqtykecil as rasio_konversi')
                    ->first();

                $qtyKecil = $qty;
                if ($produk && $sat === $produk->fsatuanbesar) {
                    $qtyKecil = $qty * (float) $produk->rasio_konversi;
                }

                $sat = mb_substr($sat, 0, 5);

                $refSo = trim((string) ($frefso[$i] ?? ''));
                $refSoId = $frefsoid[$i] ?? null;
                $refSoId = ($refSoId !== null && $refSoId !== '') ? (int) $refSoId : null;

                $amount = $qty * $price;
                $subtotal += $amount;

                $rowsDt[] = [
                    'fprdcodeid'     => $prdId,
                    'fprdcode'       => $prdCodeStr,
                    'frefdtno'       => $rref,
                    'fqty'           => $qty,
                    'fprice'         => $price,
                    'fprice_rp'      => $price * $frate,
                    'ftotprice'      => $amount,
                    'ftotprice_rp'   => $amount * $frate,
                    'fusercreate' => (Auth::user()->fname ?? 'system'),
                    'fdatetime'      => $now,
                    'fketdt'         => '',
                    'fcode'          => '0',
                    'frefso'         => $refSo !== '' ? mb_substr($refSo, 0, 100) : null,
                    'frefsoid'       => $refSoId,
                    'fdesc'          => $desc,
                    'fsatuan'        => $sat,
                    'fclosedt'       => '0',
                    'fdiscpersen'    => 0,
                    'fbiaya'         => 0,
                    'fstockmtid'     => null,
                    'fstockmtcode'   => null,
                    'fstockmtno'     => null,
                    'fqtykecil'   => $qtyKecil,
                    'fqtyremain'  => $qtyKecil,
                ];
            }

            if (empty($rowsDt)) {
                return back()->withInput()->withErrors([
                    'fitemcode' => 'Tidak ada baris item valid (periksa kode produk dan qty).',
                ]);
            }

            // =========================
            // TAHAP 4: PERSIAPAN DATA HEADER
            // =========================

            $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
            $ppnAmount    = (float)$request->input('famountpopajak', 0);
            $grandTotal   = $subtotal + $ppnAmount;

            $headerData = [
                'fstockmtno'       => trim((string)$request->input('fstockmtno')),
                'fstockmtcode'     => 'MUT',
                'fstockmtdate'     => $fstockmtdate,
                'fprdout'          => '0',
                'fsupplier'        => '0',
                'fcurrency'        => $request->input('fcurrency', 'IDR'),
                'frate'            => $frate,
                'famount'          => round($subtotal, 2),
                'famount_rp'       => round($subtotal * $frate, 2),
                'famountpajak'     => round($ppnAmount, 2),
                'famountpajak_rp'  => round($ppnAmount * $frate, 2),
                'famountmt'        => round($grandTotal, 2),
                'famountmt_rp'     => round($grandTotal * $frate, 2),
                'famountremain'    => round($grandTotal, 2),
                'famountremain_rp' => round($grandTotal * $frate, 2),
                'frefpo'           => null,
                'ftrancode'        => $request->input('ftrancode'),
                'ffrom'            => $request->input('ffrom'),  // ✅ PERBAIKAN
                'fto'              => $request->input('fto'),    // ✅ PERBAIKAN
                'fkirim'           => null,
                'fprdjadi'         => null,
                'fqtyjadi'         => null,
                'fket'             => trim((string)$request->input('fket', '')),
                'fusercreate' => (Auth::user()->fname ?? 'system'),
                'fdatetime'        => $now,
                'fsalesman'        => null,
                'fjatuhtempo'      => null,
                'fprint'           => 0,
                'fsudahtagih'      => '0',
                'fbranchcode'      => $request->input('fbranchcode'),
                'fdiscount'        => 0,
            ];

            $fstockmtno = DB::transaction(function () use (
                $headerData,
                &$rowsDt
            ) {

                $fstockmtno = trim((string) ($headerData['fstockmtno'] ?? ''));

                if ($fstockmtno === '') {

                    $kodeCabang = null;
                    $needle = trim((string)$headerData['fbranchcode']);


                    if ($needle !== '') {
                        if (is_numeric($needle)) {
                            $kodeCabang = DB::table('mscabang')->where('fcabangid', (int)$needle)->value('fcabangkode');
                        } else {
                            $kodeCabang = DB::table('mscabang')->whereRaw('LOWER(fcabangkode)=LOWER(?)', [$needle])->value('fcabangkode');
                            if (!$kodeCabang) {
                                $kodeCabang = DB::table('mscabang')->whereRaw('LOWER(fcabangname)=LOWER(?)', [$needle])->value('fcabangkode');
                            }
                        }
                    }
                    $kodeCabang = $kodeCabang ?: 'NA';

                    $fstockmtcode = $headerData['fstockmtcode'];
                    $date         = $headerData['fstockmtdate'];
                    $yy = $date->format('y');
                    $mm = $date->format('m');
                    $prefix = sprintf('%s.%s.%s.%s.', $fstockmtcode, $kodeCabang, $yy, $mm);

                    $lockKey = crc32('STOCKMT|' . $fstockmtcode . '|' . $kodeCabang . '|' . $date->format('Y-m'));
                    DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

                    $last = DB::table('trstockmt')
                        ->where('fstockmtno', 'like', $prefix . '%')
                        ->selectRaw("MAX(CAST(split_part(fstockmtno, '.', 5) AS int)) AS lastno")
                        ->value('lastno');

                    $next = (int)$last + 1;
                    $fstockmtno = $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);

                    $headerData['fbranchcode'] = $kodeCabang;
                    $headerData['fstockmtno']  = $fstockmtno;
                }

                $newStockMasterId = DB::table('trstockmt')->insertGetId(
                    $headerData,
                    'fstockmtid'
                );

                foreach ($rowsDt as $idx => &$r) {
                    $r['fstockmtid']   = $newStockMasterId;
                    $r['fstockmtcode'] = $headerData['fstockmtcode'];
                    $r['fstockmtno']   = $fstockmtno;
                }
                unset($r);

                DB::table('trstockdt')->insert($rowsDt);
                return $fstockmtno;
            });

            return redirect()
                ->route('mutasi.create')
                ->with('success', "Transaksi {$fstockmtno} berhasil disimpan.");
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {

            return back()->withInput()->withErrors([
                'fatal' => 'Terjadi error: ' . $e->getMessage()
            ]);
        }
    }

    public function edit($fstockmtid)
    {
        $supplier = Supplier::all();

        $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

        $branch = DB::table('mscabang')
            ->when(is_numeric($raw), fn($q) => $q->where('fcabangid', (int) $raw))
            ->when(!is_numeric($raw), fn($q) => $q
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

        $fcabang     = $branch->fcabangname ?? (string) $raw;
        $fbranchcode = $branch->fcabangkode ?? (string) $raw;

        $self = $this;
        $mutasi = PenerimaanPembelianHeader::with([
            'details' => function ($query) use ($self) {
                $self->appendMutasiDetailProductJoin($query);
            },
        ])
            ->findOrFail($fstockmtid);

        $savedItems = $mutasi->details->map(function ($d) {
            return [
                'uid'       => $d->fstockdtid,
                'fitemcode' => $d->fitemcode_text ?? '',
                'fitemname' => $d->fprdname ?? '',
                'fsatuan'   => $d->fsatuan ?? '',
                'fprno'     => $d->frefpr ?? '-',
                'frefpr'    => $d->frefpr ?? null,
                'fpono'     => $d->fpono ?? null,
                'famountponet' => $d->famountponet ?? null,
                'famountpo' => $d->famountpo ?? null,
                'frefdtno'  => $d->frefdtno ?? null,
                'frefso'    => trim((string) ($d->frefso ?? '')),
                'frefsoid'  => $d->frefsoid !== null && $d->frefsoid !== '' ? (string) (int) $d->frefsoid : '',
                'fqty'      => (float)($d->fqty ?? 0),
                'fterima'   => (float)($d->fterima ?? 0),
                'fprice'    => (float)($d->fprice ?? 0),
                'fdisc'     => (float)($d->fdiscpersen ?? 0),
                'ftotal'    => (float)($d->ftotprice ?? 0),
                'fdesc'     => is_array($d->fdesc) ? implode(', ', $d->fdesc) : ($d->fdesc ?? ''),
                'fketdt'    => $d->fketdt ?? '',
                'units'     => [],
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
                    'name'  => $p->fprdname,
                    'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
                    'stock' => $p->fminstock ?? 0,
                ],
            ];
        })->toArray();

        return view('mutasi.edit', [
            'supplier'           => $supplier,
            'selectedSupplierCode' => $selectedSupplierCode,
            'fcabang'            => $fcabang,
            'fbranchcode'        => $fbranchcode,
            'warehouses'         => $warehouses,
            'accounts'           => $accounts,
            'products'           => $products,
            'productMap'         => $productMap,
            'mutasi'             => $mutasi,
            'savedItems'         => $savedItems,
            'ppnAmount'          => (float) ($mutasi->famountpopajak ?? 0),
            'famountponet'       => (float) ($mutasi->famountponet ?? 0),
            'famountpo'          => (float) ($mutasi->famountpo ?? 0),
            'action' => 'edit',
        ]);
    }

    public function view($fstockmtid)
    {
        $supplier = Supplier::all();

        $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

        $branch = DB::table('mscabang')
            ->when(is_numeric($raw), fn($q) => $q->where('fcabangid', (int) $raw))
            ->when(!is_numeric($raw), fn($q) => $q
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

        $fcabang     = $branch->fcabangname ?? (string) $raw;
        $fbranchcode = $branch->fcabangkode ?? (string) $raw;

        $self = $this;
        $mutasi = PenerimaanPembelianHeader::with([
            'details' => function ($query) use ($self) {
                $self->appendMutasiDetailProductJoin($query);
            },
        ])
            ->findOrFail($fstockmtid);

        $savedItems = $mutasi->details->map(function ($d) {
            return [
                'uid'       => $d->fstockdtid,
                'fitemcode' => $d->fitemcode_text ?? '',
                'fitemname' => $d->fprdname ?? '',
                'fsatuan'   => $d->fsatuan ?? '',
                'fprno'     => $d->frefpr ?? '-',
                'frefpr'    => $d->frefpr ?? null,
                'fpono'     => $d->fpono ?? null,
                'famountponet' => $d->famountponet ?? null,
                'famountpo' => $d->famountpo ?? null,
                'frefdtno'  => $d->frefdtno ?? null,
                'frefso'    => trim((string) ($d->frefso ?? '')),
                'frefsoid'  => $d->frefsoid !== null && $d->frefsoid !== '' ? (string) (int) $d->frefsoid : '',
                'fqty'      => (float)($d->fqty ?? 0),
                'fterima'   => (float)($d->fterima ?? 0),
                'fprice'    => (float)($d->fprice ?? 0),
                'fdisc'     => (float)($d->fdiscpersen ?? 0),
                'ftotal'    => (float)($d->ftotprice ?? 0),
                'fdesc'     => is_array($d->fdesc) ? implode(', ', $d->fdesc) : ($d->fdesc ?? ''),
                'fketdt'    => $d->fketdt ?? '',
                'units'     => [],
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
                    'name'  => $p->fprdname,
                    'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
                    'stock' => $p->fminstock ?? 0,
                ],
            ];
        })->toArray();

        return view('mutasi.view', [
            'supplier'           => $supplier,
            'selectedSupplierCode' => $selectedSupplierCode,
            'fcabang'            => $fcabang,
            'fbranchcode'        => $fbranchcode,
            'warehouses'         => $warehouses,
            'accounts'           => $accounts,
            'products'           => $products,
            'productMap'         => $productMap,
            'mutasi'             => $mutasi,
            'savedItems'         => $savedItems,
            'ppnAmount'          => (float) ($mutasi->famountpopajak ?? 0),
            'famountponet'       => (float) ($mutasi->famountponet ?? 0),
            'famountpo'          => (float) ($mutasi->famountpo ?? 0),
        ]);
    }

    public function update(Request $request, $fstockmtid)
    {
        try {
            // =========================
            // 1) VALIDASI (Disamakan dengan store)
            // =========================
            $validated = $request->validate([
                'fstockmtno'     => ['nullable', 'string', 'max:100'],
                'fstockmtdate'   => ['required', 'date'],
                'ffrom'          => ['required', 'integer'], // Samakan dengan store
                'fto'            => ['required', 'integer'], // Samakan dengan store
                'ftrancode'      => ['nullable', 'string', 'max:3'],
                'fket'           => ['nullable', 'string', 'max:50'],
                'fbranchcode'    => ['nullable', 'string', 'max:20'],
                'fitemcode'      => ['required', 'array', 'min:1'],
                'fitemcode.*'    => ['required', 'string', 'max:50'],
                'fsatuan'        => ['nullable', 'array'],
                'fsatuan.*'      => ['nullable', 'string', 'max:20'],
                'fqty'           => ['required', 'array'],
                'fqty.*'         => ['required', 'numeric', 'min:0.01'],
                'fprice.*'       => ['numeric', 'min:0'],
                'fdesc'          => ['nullable', 'array'],
                'fdesc.*'        => ['nullable', 'string', 'max:500'],
                'fcurrency'      => ['nullable', 'string', 'max:5'],
                'frate'          => ['nullable', 'numeric', 'min:0'],
                'famountpopajak' => ['nullable', 'numeric', 'min:0'],
                'frefso'         => ['nullable', 'array'],
                'frefso.*'       => ['nullable', 'string', 'max:100'],
                'frefsoid'       => ['nullable', 'array'],
                'frefsoid.*'     => ['nullable', 'integer'],
            ]);

            // =========================
            // 2) AMBIL DATA MASTER
            // =========================
            // Pastikan nama model ini benar merujuk ke tabel trstockmt
            $header = PenerimaanPembelianHeader::findOrFail($fstockmtid);

            $uniqueCodes = array_values(array_unique(
                array_filter(array_map(fn($c) => trim((string)$c), $request->input('fitemcode', [])))
            ));

            $prodMeta = collect();
            if (!empty($uniqueCodes)) {
                $prodMeta = DB::table('msprd')
                    ->whereIn('fprdcode', $uniqueCodes)
                    ->get(['fprdid', 'fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'])
                    ->keyBy('fprdcode');
            }

            $rowsDt   = [];
            $subtotal = 0.0;
            $now      = now();
            $frate    = (float)$request->input('frate', 1);
            if ($frate <= 0) $frate = 1;

            $codes    = $request->input('fitemcode', []);
            $satuans  = $request->input('fsatuan', []);
            $refdtno  = $request->input('frefdtno', []);
            $frefso   = $request->input('frefso', []);
            $frefsoid = $request->input('frefsoid', []);
            $qtys     = $request->input('fqty', []);
            $prices   = $request->input('fprice', []);
            $descs    = $request->input('fdesc', []);

            $rowCount = count($codes);

            // =========================
            // 3) LOOP DETAIL (Sesuai Logika Store)
            // =========================
            for ($i = 0; $i < $rowCount; $i++) {
                $code  = trim((string)($codes[$i]   ?? ''));
                $sat   = trim((string)($satuans[$i] ?? ''));
                $rref  = trim((string)($refdtno[$i] ?? ''));
                $qty   = (float)($qtys[$i]    ?? 0);
                $price = (float)($prices[$i]  ?? 0);
                $desc  = (string)($descs[$i]   ?? '');

                if ($code === '' || $qty <= 0) continue;

                $meta = $prodMeta[$code] ?? null;
                if (!$meta) continue;

                // Logika Rasio Satuan (Sesuai Store)
                $produk = DB::table('msprd')
                    ->where('fprdcode', $code)
                    ->select('fprdid', 'fsatuanbesar', 'fqtykecil as rasio_konversi')
                    ->first();

                $qtyKecil = $qty;
                if ($produk && $sat === $produk->fsatuanbesar) {
                    $qtyKecil = $qty * (float) $produk->rasio_konversi;
                }

                $sat = mb_substr($sat, 0, 5);
                $refSo = trim((string) ($frefso[$i] ?? ''));
                $refSoId = isset($frefsoid[$i]) && $frefsoid[$i] !== '' ? (int)$frefsoid[$i] : null;

                $amount = $qty * $price;
                $subtotal += $amount;

                $rowsDt[] = [
                    'fprdcodeid'     => (int) $meta->fprdid,
                    'fprdcode'       => mb_substr((string) ($meta->fprdcode ?? $code), 0, 50),
                    'frefdtno'       => $rref,
                    'fqty'           => $qty,
                    'fprice'         => $price,
                    'fprice_rp'      => $price * $frate,
                    'ftotprice'      => $amount,
                    'ftotprice_rp'   => $amount * $frate,
                    'fuserupdate'    => (Auth::user()->fname ?? 'system'), // Gunakan fuserupdate untuk edit
                    'fdatetime'      => $now,
                    'fketdt'         => '',
                    'fcode'          => '0',
                    'frefso'         => $refSo !== '' ? mb_substr($refSo, 0, 100) : null,
                    'frefsoid'       => $refSoId,
                    'fdesc'          => $desc,
                    'fsatuan'        => $sat,
                    'fclosedt'       => '0',
                    'fdiscpersen'    => 0,
                    'fbiaya'         => 0,
                    'fstockmtid'     => $fstockmtid,
                    'fstockmtcode'   => $header->fstockmtcode,
                    'fstockmtno'     => $header->fstockmtno,
                    'fqtykecil'      => $qtyKecil,
                    'fqtyremain'     => $qtyKecil,
                ];
            }

            if (empty($rowsDt)) {
                return back()->withInput()->withErrors(['fitemcode' => 'Tidak ada baris item valid.']);
            }

            // =========================
            // 4) TAHAP UPDATE DB
            // =========================
            $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
            $ppnAmount    = (float)$request->input('famountpopajak', 0);
            $grandTotal   = $subtotal + $ppnAmount;

            DB::transaction(function () use ($header, $fstockmtid, $fstockmtdate, $frate, $subtotal, $ppnAmount, $grandTotal, $rowsDt, $request, $now) {

                // 4.1 Update Header
                $header->update([
                    'fstockmtdate'     => $fstockmtdate,
                    'fcurrency'        => $request->input('fcurrency', 'IDR'),
                    'frate'            => $frate,
                    'famount'          => round($subtotal, 2),
                    'famount_rp'       => round($subtotal * $frate, 2),
                    'famountpajak'     => round($ppnAmount, 2),
                    'famountpajak_rp'  => round($ppnAmount * $frate, 2),
                    'famountmt'        => round($grandTotal, 2),
                    'famountmt_rp'     => round($grandTotal * $frate, 2),
                    'famountremain'    => round($grandTotal, 2),
                    'famountremain_rp' => round($grandTotal * $frate, 2),
                    'ffrom'            => $request->input('ffrom'),
                    'fto'              => $request->input('fto'),
                    'ftrancode'        => $request->input('ftrancode'),
                    'fket'             => trim((string)$request->input('fket', '')),
                    'fuserupdate'      => (Auth::user()->fname ?? 'system'),
                    'fbranchcode'      => $request->input('fbranchcode'),
                ]);

                // 4.2 Sync Detail (Hapus lama, pasang baru)
                DB::table('trstockdt')->where('fstockmtid', $fstockmtid)->delete();
                DB::table('trstockdt')->insert($rowsDt);
            });

            return redirect()
                ->route('mutasi.index')
                ->with('success', "Transaksi {$header->fstockmtno} berhasil diperbarui.");
        } catch (\Exception $e) {
            return back()->withInput()->withErrors([
                'fatal' => 'Terjadi error saat update: ' . $e->getMessage()
            ]);
        }
    }

    public function delete($fstockmtid)
    {
        $supplier = Supplier::all();

        $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

        $branch = DB::table('mscabang')
            ->when(is_numeric($raw), fn($q) => $q->where('fcabangid', (int) $raw))
            ->when(!is_numeric($raw), fn($q) => $q
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

        $fcabang     = $branch->fcabangname ?? (string) $raw;
        $fbranchcode = $branch->fcabangkode ?? (string) $raw;

        $self = $this;
        $mutasi = PenerimaanPembelianHeader::with([
            'details' => function ($query) use ($self) {
                $self->appendMutasiDetailProductJoin($query);
            },
        ])
            ->findOrFail($fstockmtid);

        $savedItems = $mutasi->details->map(function ($d) {
            return [
                'uid'       => $d->fstockdtid,
                'fitemcode' => $d->fitemcode_text ?? '',
                'fitemname' => $d->fprdname ?? '',
                'fsatuan'   => $d->fsatuan ?? '',
                'fprno'     => $d->frefpr ?? '-',
                'frefpr'    => $d->frefpr ?? null,
                'fpono'     => $d->fpono ?? null,
                'famountponet' => $d->famountponet ?? null,
                'famountpo' => $d->famountpo ?? null,
                'frefdtno'  => $d->frefdtno ?? null,
                'frefso'    => trim((string) ($d->frefso ?? '')),
                'frefsoid'  => $d->frefsoid !== null && $d->frefsoid !== '' ? (string) (int) $d->frefsoid : '',
                'fqty'      => (float)($d->fqty ?? 0),
                'fterima'   => (float)($d->fterima ?? 0),
                'fprice'    => (float)($d->fprice ?? 0),
                'fdisc'     => (float)($d->fdiscpersen ?? 0),
                'ftotal'    => (float)($d->ftotprice ?? 0),
                'fdesc'     => is_array($d->fdesc) ? implode(', ', $d->fdesc) : ($d->fdesc ?? ''),
                'fketdt'    => $d->fketdt ?? '',
                'units'     => [],
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
                    'name'  => $p->fprdname,
                    'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
                    'stock' => $p->fminstock ?? 0,
                ],
            ];
        })->toArray();

        return view('mutasi.edit', [
            'supplier'           => $supplier,
            'selectedSupplierCode' => $selectedSupplierCode,
            'fcabang'            => $fcabang,
            'fbranchcode'        => $fbranchcode,
            'warehouses'         => $warehouses,
            'accounts'           => $accounts,
            'products'           => $products,
            'productMap'         => $productMap,
            'mutasi'             => $mutasi,
            'savedItems'         => $savedItems,
            'ppnAmount'          => (float) ($mutasi->famountpopajak ?? 0),
            'famountponet'       => (float) ($mutasi->famountponet ?? 0),
            'famountpo'          => (float) ($mutasi->famountpo ?? 0),
            'action' => 'delete'
        ]);
    }


    /**
     * Join trstockdt to msprd: fprdcodeid stores fprdid; fprdcode stores product code string.
     * Legacy rows may have stored only fprdid in fprdcode (numeric).
     */
    private function appendMutasiDetailProductJoin($query): void
    {
        $query->leftJoin('msprd', function ($join) {
            $join->whereRaw(
                'msprd.fprdid = COALESCE(trstockdt.fprdcodeid, CASE WHEN btrim(trstockdt.fprdcode::text) ~ ? THEN cast(btrim(trstockdt.fprdcode::text) as integer) ELSE NULL END)',
                ['^[0-9]+$']
            );
        })
            ->select(
                'trstockdt.*',
                'msprd.fprdname',
                DB::raw('COALESCE(msprd.fprdcode, NULLIF(trim(trstockdt.fprdcode::text), \'\')) as fitemcode_text')
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

            if (!$mutasi) {
                DB::rollBack();
                return redirect()->route('mutasi.index')->with('error', 'Data Mutasi tidak ditemukan.');
            }

            $docNo = $mutasi->fstockmtno;

            // 2. Hapus detail (trstockdt)
            DB::table('trstockdt')
                ->where('fstockmtid', $fstockmtid)
                ->delete();

            // 3. Hapus header (trstockmt)
            DB::table('trstockmt')
                ->where('fstockmtid', $fstockmtid)
                ->delete();

            DB::commit();

            return redirect()->route('mutasi.index')->with('success', 'Data Mutasi ' . $docNo . ' berhasil dihapus.');
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            return redirect()->route('mutasi.index')->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}
