<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Salesman;
use App\Models\SalesOrderDetail;
use App\Models\SalesOrderHeader;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // sekalian biar aman untuk tanggal

// sekalian biar aman untuk tanggal

class SalesOrderController extends Controller
{
    public function index(Request $request)
    {
        // Ambil izin (permissions)
        $canCreate = in_array('createTr_poh', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updateTr_poh', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteTr_poh', explode(',', session('user_restricted_permissions', '')));
        $showActionsColumn = $canEdit || $canDelete;

        $status = $request->query('status');
        $year = $request->query('year');
        $month = $request->query('month');

        // Ambil tahun-tahun yang tersedia dari data
        $availableYears = SalesOrderHeader::selectRaw('DISTINCT EXTRACT(YEAR FROM fdatetime) as year')
            ->whereNotNull('fdatetime')
            ->orderByRaw('EXTRACT(YEAR FROM fdatetime) DESC')
            ->pluck('year');

        // --- Handle Request AJAX dari DataTables ---
        if ($request->ajax()) {

            $query = SalesOrderHeader::query()
                ->leftJoin('mscustomer', 'trsomt.fcustno', '=', 'mscustomer.fcustomercode')
                ->select('trsomt.*', 'mscustomer.fcustomername');

            // DEBUG: Cek total data di tabel
            $totalRecords = SalesOrderHeader::count();

            // Handle Search
            if ($search = $request->input('search.value')) {
                $query->where(function ($q) use ($search) {
                    $q->where('trsomt.fsono', 'like', "%{$search}%")
                        ->orWhere('mscustomer.fcustomername', 'like', "%{$search}%");
                });
            }

            // Filter status - DEFAULT ke 'active' jika tidak ada
            $statusFilter = $request->query('status', 'active');

            if ($statusFilter === 'active') {
                $query->where('fclose', '0');
            } elseif ($statusFilter === 'nonactive') {
                $query->where('fclose', '1');
            }
            // Jika 'all', tidak ada filter fclose

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

            $sortableColumns = ['fsono', 'fsodate', 'fclose'];

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
                    'ftrsomtid' => $row->ftrsomtid,
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
                    'fcustomername' => $row->fcustomername,
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
        return view('salesorder.index', compact(
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

    public function pickable(Request $request)
    {
        // Base query dari SalesOrderHeader (trsomt) TANPA join ke detail
        $query = SalesOrderHeader::leftJoin('mscustomer', 'trsomt.fcustno', '=', 'mscustomer.fcustomercode')
            ->select(
                'trsomt.ftrsomtid',
                'trsomt.frefno',
                'trsomt.fsono',
                'trsomt.fcustno',
                'trsomt.fsodate',
                'mscustomer.fcustomername'
            );

        // Total records tanpa filter
        $recordsTotal = SalesOrderHeader::count();

        // Search
        if ($request->filled('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('trsomt.fsono', 'ilike', "%{$search}%")
                    ->orWhere('mscustomer.fcustomername', 'ilike', "%{$search}%");
            });
        }

        // Total records setelah filter
        $recordsFiltered = $query->count();

        // Sorting
        $orderColumn = $request->input('order_column', 'fsodate');
        $orderDir = $request->input('order_dir', 'desc');

        // Mapping kolom yang bisa di-sort
        $allowedColumns = ['fsono', 'fsodate', 'fcustomername'];

        if (in_array($orderColumn, $allowedColumns)) {
            if ($orderColumn == 'fcustomername') {
                $query->orderBy('mscustomer.fcustomername', $orderDir);
            } else {
                $query->orderBy('trsomt.'.$orderColumn, $orderDir);
            }
        } else {
            $query->orderBy('trsomt.fsodate', 'desc');
        }

        // Pagination
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);

        $data = $query->skip($start)
            ->take($length)
            ->get();

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => (int) $recordsTotal,
            'recordsFiltered' => (int) $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function items($id)
    {
        // 1. Ambil data header SO berdasarkan ftrsomtid (Primary Key dari trsomt)
        $header = SalesOrderHeader::where('ftrsomtid', $id)->firstOrFail();
        $remainMap = $this->getSoRemainByIds(
            DB::table('trsodt')->where('fsono', $header->fsono)->pluck('ftrsodtid')->all()
        );

        // 2. Ambil data detail dari trsodt menggunakan nomor SO
        $items = SalesOrderDetail::where('trsodt.fsono', $header->fsono)
            ->leftJoin('msprd as m', 'm.fprdcode', '=', 'trsodt.fprdcode') // Join ke Master Produk
            ->select([
                'trsodt.ftrsodtid as frefdtno',  // ID Detail sebagai referensi unik
                'trsodt.fsono as fnouref',       // Nomor Header
                DB::raw("COALESCE(trsodt.fnoacak::text, '') as frefnoacak"),
                'trsodt.fprdcode as fitemcode',  // Kode Produk (pake alias fitemcode buat frontend)
                'm.fprdname as fitemname',       // Nama Produk dari master
                'trsodt.fsatuan',                // Satuan
                'trsodt.fqty',
                'trsodt.fprice as fprice',       // Harga jual (alias fprice)
                'trsodt.fprice as fharga',       // Legacy alias fharga
            ])
            ->orderBy('trsodt.ftrsodtid')
            ->get()
            ->map(function ($item) use ($remainMap) {
                $remain = (float) ($remainMap[(int) ($item->frefdtno ?? 0)] ?? 0);
                $item->fqty = $remain;
                $item->fqtyremain = $remain;

                return $item;
            });

        return response()->json([
            'header' => [
                'ftrsomtid' => $header->ftrsomtid,
                'fsono' => $header->fsono,
                'fcustno' => $header->fcustno,
                'fsodate' => $header->fsodate,
            ],
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
            $candidate = (string) random_int(1, 9).random_int(1, 9).random_int(1, 9);
        } while (in_array($candidate, $usedNumbers, true));

        $usedNumbers[] = $candidate;

        return $candidate;
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

        $last = DB::table('trsomt')
            ->where('fsono', 'like', $prefix.'%')
            ->selectRaw("MAX(CAST(split_part(fsono, '.', 5) AS int)) AS lastno")
            ->value('lastno');

        $next = (int) $last + 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function print(string $fsono)
    {
        // Header: find by SO code (string)
        $hdr = DB::table('trsomt')
            ->leftJoin('mscustomer as c', 'c.fcustomercode', '=', 'trsomt.fcustno')
            ->leftJoin('mscabang as b', 'b.fcabangkode', '=', 'trsomt.fbranchcode')
            ->leftJoin('mssalesman as s', 's.fsalesmancode', '=', 'trsomt.fsalesman')
            ->where('trsomt.fsono', $fsono)
            ->first([
                'trsomt.*',
                'c.fcustomername as customer_name',
                'b.fcabangname as cabang_name',
                's.fsalesmanname as salesman_name',
            ]);

        if (! $hdr) {
            return redirect()->back()->with('error', 'Sales Order tidak ditemukan.');
        }

        // Detail: join dengan product
        $dt = DB::table('trsodt')
            ->leftJoin('msprd as p', function ($j) {
                $j->on('p.fprdid', '=', 'trsodt.fprdcodeid');
            })
            ->where('trsodt.fsono', $hdr->fsono)
            ->orderBy('trsodt.ftrsodtid')
            ->get([
                'trsodt.*',
                'p.fprdcode as product_code',
                'p.fprdname as product_name',
                'p.fminstock as stock',
            ]);

        // Format date helper
        $fmt = fn ($d) => $d
            ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d F Y')
            : '-';

        return view('salesorder.print', [
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

        $salesmans = Salesman::orderBy('fsalesmanname', 'asc')
            ->get(['fsalesmancode', 'fsalesmanname']);

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

        return view('salesorder.create', [
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
        // VALIDATION
        $request->validate([
            'fsono' => ['nullable', 'string', 'max:25'],
            'fsodate' => ['required', 'date'],
            'fkirimdate' => ['nullable', 'date'],
            'fcustno' => ['required', 'string', 'max:20'],
            'fsalesman' => ['nullable', 'string', 'max:20'],
            'fincludeppn' => ['nullable'],
            'fket' => ['nullable', 'string', 'max:300'],
            'falamatkirim' => ['nullable', 'string', 'max:300'],
            'fbranchcode' => ['nullable', 'string', 'max:2'],
            'ftempohr' => ['nullable', 'string', 'max:3'],
            'fprdcode' => ['required', 'array', 'min:1'],
            'fprdcode.*' => ['required', 'string', 'max:20'],
            'fsatuan' => ['nullable', 'array'],
            'fsatuan.*' => ['nullable', 'string', 'max:10'],
            'fqty' => ['required', 'array'],
            'fqty.*' => ['numeric', 'min:0'],
            'fprice' => ['nullable', 'array'],
            'fprice.*' => ['numeric', 'min:0'],
            'fdisc' => ['nullable', 'array'],
            'fnoacak' => ['nullable', 'array'],
            'fnoacak.*' => ['nullable', 'regex:/^[1-9]{3}$/'],
        ], [
            'fsodate.required' => 'Tanggal SO wajib diisi.',
            'fcustno.required' => 'Customer wajib diisi.',
            'fprdcode.required' => 'Minimal 1 item.',
        ]);

        // HEADER VALUES
        $fsodate = Carbon::parse($request->fsodate)->startOfDay();
        $fsono = $request->input('fsono');
        $fincludeppn = $request->boolean('fincludeppn') ? '1' : '0';
        $userid = auth('sysuser')->user()->fname ?? 'admin';
        $now = now();
        $fincludeppn = $request->boolean('fincludeppn') ? 1 : 0;
        $fapplyppn = $request->boolean('fapplyppn') ? 1 : 0;

        // DETAIL ARRAYS
        $itemId = $request->input('fprdcodeid', []);
        $itemCodes = $request->input('fprdcode', []);
        $satuans = $request->input('fsatuan', []);
        $qtys = $request->input('fqty', []);
        $prices = $request->input('fprice', []);
        $discs = $request->input('fdisc', []);
        $descs = $request->input('fdesc', []);
        $fnoacaks = $request->input('fnoacak', []);

        // BUILD DETAIL ROWS
        $rowsSodt = [];
        $totalGross = 0.0;
        $totalDisc = 0.0;
        $usedNoAcaks = [];

        $rowCount = count($itemCodes);

        for ($i = 0; $i < $rowCount; $i++) {
            $itemCode = trim($itemCodes[$i] ?? '');
            $qty = (float) ($qtys[$i] ?? 0);
            $price = (float) ($prices[$i] ?? 0);
            $discInput = $discs[$i] ?? 0;

            if (empty($itemCode) || $qty <= 0) {
                continue;
            }

            $produk = DB::table('msprd')
                ->where('fprdcode', $itemCode)
                ->select('fprdid', 'fsatuanbesar', 'fqtykecil as rasio_konversi')
                ->first();

            $itemeId = $produk ? $produk->fprdid : ($itemId[$i] ?? null);
            $satuan = trim((string) ($satuans[$i] ?? ''));

            // Konversi Qty Kecil
            $qtyKecil = $qty;
            if ($produk && $satuan === $produk->fsatuanbesar) {
                $qtyKecil = $qty * (float) $produk->rasio_konversi;
            }

            // Hitung Diskon
            $discPersen = $this->parseDiscount($discInput);
            $subtotal = $qty * $price;
            $discount = $subtotal * ($discPersen / 100);
            $amount = $subtotal - $discount;

            $totalGross += $subtotal;
            $totalDisc += $discount;

            $rowsSodt[] = [
                'fprdcodeid' => $itemeId,
                'fprdcode' => mb_substr($itemCode, 0, 20),
                'fnoacak' => $this->normalizeRandomNumber($fnoacaks[$i] ?? null, $usedNoAcaks),
                'fsatuan' => mb_substr($satuan, 0, 20),
                'fdesc' => $descs[$i] ?? '',
                'fqty' => $qty,
                'fprice' => $price,
                'fdiscpersen' => $discPersen,
                'fdiscount' => round($discount, 2),
                'famount' => round($amount, 2),
                'fqtykecil' => $qtyKecil,
                'fqtyremain' => $qtyKecil,
            ];
        }

        $amountNet = $totalGross - $totalDisc;
        $fppn = $request->boolean('fppn') ? '1' : '0';
        $fppnpersen = $fppn === '1' ? (float) $request->input('fppnpersen', 11) : 0;
        $ppnAmount = $amountNet * ($fppnpersen / 100);
        $grandTotal = $amountNet + $ppnAmount;

        // TRANSACTION
        try {
            DB::transaction(function () use (
                $request,
                $fsodate,
                $fincludeppn,
                $fapplyppn,
                $userid,
                $now,
                $rowsSodt,
                &$fsono,
                $totalGross,
                $totalDisc,
                $amountNet,
                $ppnAmount

            ) {
                // A. Generate fsono (Auto Numbering)
                if (empty($fsono)) {
                    $rawBranch = $request->input('fbranchcode');
                    $kodeCabang = 'NA';

                    if ($rawBranch !== null) {
                        $needle = trim((string) $rawBranch);
                        $kodeCabang = (strlen($needle) <= 2) ? $needle : (DB::table('mscabang')
                            ->whereRaw('LOWER(fcabangcode)=LOWER(?)', [$needle])
                            ->value('fcabangcode') ?: 'NA');
                    }

                    $yy = $fsodate->format('y');
                    $mm = $fsodate->format('m');
                    $prefix = sprintf('SO.%s.%s.%s.', $kodeCabang, $yy, $mm);

                    $lockKey = crc32('SO|'.$kodeCabang.'|'.$fsodate->format('Y-m'));
                    DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

                    $last = DB::table('trsomt')
                        ->where('fsono', 'like', $prefix.'%')
                        ->selectRaw("MAX(CAST(split_part(fsono, '.', 5) AS int)) AS lastno")
                        ->value('lastno');

                    $fsono = $prefix.str_pad((string) ((int) $last + 1), 4, '0', STR_PAD_LEFT);
                }

                $lastRefNo = DB::table('trsomt')
                    ->selectRaw("MAX(NULLIF(frefno, '')::int) as max_no")
                    ->value('max_no') ?? 0;
                $nextRefNo = $lastRefNo + 1;

                // C. Insert Header
                $ftrsomtid = DB::table('trsomt')->insertGetId([
                    'fsono' => $fsono,
                    'frefno' => (string) $nextRefNo,
                    'fsodate' => $fsodate,
                    'fbranchcode' => mb_substr($request->input('fbranchcode', ''), 0, 2),
                    'fcustno' => mb_substr($request->input('fcustno', ''), 0, 20),
                    'fsalesman' => mb_substr((string) $request->input('fsalesman', ''), 0, 20) ?: null,
                    'ftempohr' => mb_substr($request->input('ftempohr', '0'), 0, 3),
                    'fket' => mb_substr($request->input('fket', ''), 0, 300),
                    'falamatkirim' => mb_substr($request->input('falamatkirim', ''), 0, 300),
                    'fusercreate' => mb_substr($userid, 0, 10),
                    'fdatetime' => $now,
                    'famountgross' => round($totalGross, 2),
                    'fdiscount' => round($totalDisc, 2),
                    'fincludeppn' => $fincludeppn,
                    'fapplyppn' => $fapplyppn,
                    'fppnpersen' => $request->input('ppn_rate', 0),
                    'famountsonet' => round($amountNet, 2),
                    'famountpajak' => round($ppnAmount, 2),
                    'famountso' => 0,
                    'fprdout' => '0',
                    'fclose' => '0',
                    'fprint' => 0,
                    'fketinternal' => mb_substr($request->input('fketinternal', ''), 0, 300),
                ], 'ftrsomtid');

                // D. Insert Details
                foreach ($rowsSodt as &$r) {
                    $r['fsono'] = $fsono;
                }
                DB::table('trsodt')->insert($rowsSodt);

                // UPDATE STOK - gunakan qtyKecil hasil konversi, bukan qty mentah
                foreach ($rowsSodt as $row) {
                    DB::table('msprd')
                        ->where('fprdcode', $row['fprdcode'])
                        ->update([
                            'fminstock' => DB::raw('CAST(fminstock AS NUMERIC) - '.$row['fqtyremain']),
                            'fupdatedat' => now(),
                        ]);
                }
                // E. Final Total Update
                $totalAmountSo = DB::table('trsodt')->where('fsono', $fsono)->sum('famount');
                DB::table('trsomt')->where('ftrsomtid', $ftrsomtid)->update([
                    'famountso' => round($totalAmountSo, 2),
                ]);
            });

            return redirect()->route('salesorder.create')->with('success', "Sales Order {$fsono} berhasil disimpan.");
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Gagal simpan: '.$e->getMessage()]);
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

    public function edit(Request $request, $ftrsomtid)
    {
        $customers = Customer::orderBy('fcustomername', 'asc')
            ->get(['fcustomercode', 'fcustomername']);

        $salesmans = Salesman::orderBy('fsalesmanname', 'asc')
            ->get(['fsalesmancode', 'fsalesmanname']);

        $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

        $branch = DB::table('mscabang')
            ->when(is_numeric($raw), fn ($q) => $q->where('fcabangid', (int) $raw))
            ->when(! is_numeric($raw), fn ($q) => $q
                ->where('fcabangkode', $raw)
                ->orWhere('fcabangname', $raw))
            ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

        $fcabang = $branch->fcabangname ?? (string) $raw;   // tampilan
        $fbranchcode = $branch->fcabangkode ?? (string) $raw;   // hidden post

        $salesorder = SalesOrderHeader::with(['customer', 'details' => function ($q) {
            $q->orderBy('trsodt.ftrsodtid')
                ->leftJoin('msprd', function ($j) {
                    $j->on('msprd.fprdid', '=', DB::raw('CAST(trsodt.fprdcodeid AS INTEGER)'));
                })
                ->select(
                    'trsodt.*',
                    'msprd.fprdcode as fprdcode',
                    'msprd.fprdname',
                    'msprd.fsatuanbesar',
                    'msprd.fqtykecil  as fprd_qtykonversi'
                );
        }])->findOrFail($ftrsomtid);

        if (! $salesorder->customer) {
            $salesorder->setRelation('customer', Customer::where('fcustomercode', trim((string) $salesorder->fcustno))->first());
        }

        $usageLockMessage = $this->getUsageLockMessage($salesorder);

        $soRemainMap = $this->getSoRemainByIds($salesorder->details->pluck('ftrsodtid')->all());

        $savedItems = $salesorder->details->map(function ($d) use ($soRemainMap) {
            return [
                'uid' => $d->ftrsodtid,
                'fprdcode' => (string) ($d->fprdcode ?? ''),
                'fitemname' => (string) ($d->fprdname ?? ''),
                'fsatuan' => (string) ($d->fsatuan ?? ''),
                'fnoacak' => (string) ($d->fnoacak ?? ''),
                'frefdtno' => (string) ($d->ftrsodtid ?? ''),
                'fnouref' => (string) ($d->fnouref ?? ''),
                'fqty' => (float) ($d->fqty ?? 0),
                'fqtyremain' => (float) ($soRemainMap[(int) ($d->ftrsodtid ?? 0)] ?? 0),
                'fterima' => (float) ($d->fterima ?? 0),
                'fprice' => (float) ($d->fprice ?? 0),
                'fdisc' => (float) ($d->fdiscpersen ?? 0),
                'ftotal' => (float) ($d->famount ?? 0),
                'fdesc' => (string) ($d->fdesc ?? ''),
                'fketdt' => (string) ($d->fketdt ?? ''),
            ];
        })->values();
        $selectedSupplierCode = $salesorder->fsupplier;

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
        return view('salesorder.edit', [
            'customers' => $customers,
            'salesmans' => $salesmans,
            'selectedSupplierCode' => $selectedSupplierCode, // Kirim kode supplier ke view
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'fppnpersen' => (float) ($salesorder->fppnpersen ?? 11),
            'products' => $products,
            'productMap' => $productMap,
            'salesorder' => $salesorder,
            'savedItems' => $savedItems,
            'ppnAmount' => (float) ($salesorder->famountpopajak ?? 0), // total PPN from DB
            'famountgross' => (float) ($salesorder->famountgross ?? 0),  // nilai Grand Total dari DB
            'famountso' => (float) ($salesorder->famountso ?? 0),  // nilai Grand Total dari DB
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'filterSalesmanId' => $request->query('filter_salesman_id'),
            'isUsageLocked' => ! empty($usageLockMessage),
            'usageLockMessage' => $usageLockMessage,
            'action' => 'edit',
        ]);
    }

    public function view(Request $request, $ftrsomtid)
    {
        $customers = Customer::orderBy('fcustomername', 'asc')
            ->get(['fcustomercode', 'fcustomername']);

        $salesmans = Salesman::orderBy('fsalesmanname', 'asc')
            ->get(['fsalesmancode', 'fsalesmanname']);
        $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

        $branch = DB::table('mscabang')
            ->when(is_numeric($raw), fn ($q) => $q->where('fcabangid', (int) $raw))
            ->when(! is_numeric($raw), fn ($q) => $q
                ->where('fcabangkode', $raw)
                ->orWhere('fcabangname', $raw))
            ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

        $fcabang = $branch->fcabangname ?? (string) $raw;
        $fbranchcode = $branch->fcabangkode ?? (string) $raw;

        $salesorder = SalesOrderHeader::with(['customer', 'details' => function ($q) {
            $q->orderBy('trsodt.ftrsodtid')
                ->leftJoin('msprd', function ($j) {
                    $j->on('msprd.fprdid', '=', DB::raw('CAST(trsodt.fprdcodeid AS INTEGER)'));
                })
                ->select(
                    'trsodt.*',
                    'msprd.fprdcode      as fprdcode',
                    'msprd.fprdname',
                    'msprd.fsatuanbesar',
                    'msprd.fqtykecil     as fprd_qtykonversi'  // alias jelas, tidak konflik
                );
        }])->findOrFail($ftrsomtid);

        if (! $salesorder->customer) {
            $salesorder->setRelation('customer', Customer::where('fcustomercode', trim((string) $salesorder->fcustno))->first());
        }

        $soRemainMap = $this->getSoRemainByIds($salesorder->details->pluck('ftrsodtid')->all());

        $savedItems = $salesorder->details->map(function ($d) use ($soRemainMap) {
            return [
                'uid' => $d->ftrsodtid,
                'fprdcode' => (string) ($d->fprdcode ?? ''),
                'fitemname' => (string) ($d->fprdname ?? ''),
                'fsatuan' => (string) ($d->fsatuan ?? ''),
                'fnoacak' => (string) ($d->fnoacak ?? ''),
                'frefdtno' => (string) ($d->ftrsodtid ?? ''),
                'fqty' => (float) ($d->fqty ?? 0),
                'fqtyremain' => (float) ($soRemainMap[(int) ($d->ftrsodtid ?? 0)] ?? 0),
                'fterima' => (float) ($d->fterima ?? 0),
                'fprice' => (float) ($d->fprice ?? 0),
                'fdisc' => (float) ($d->fdiscpersen ?? 0),
                'ftotal' => (float) ($d->famount ?? 0),
                'fdesc' => (string) ($d->fdesc ?? ''),
                'fketdt' => (string) ($d->fketdt ?? ''),
            ];
        })->values();

        $selectedSupplierCode = $salesorder->fsupplier;

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
                (string) $p->fprdcode => [
                    'name' => $p->fprdname,
                    'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
                    'stock' => (float) ($p->fminstock ?? 0),
                ],
            ];
        })->toArray();

        return view('salesorder.view', [
            'customers' => $customers,
            'salesmans' => $salesmans,
            'selectedSupplierCode' => $selectedSupplierCode,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'products' => $products,
            'productMap' => $productMap,
            'fppnpersen' => (float) ($salesorder->fppnpersen ?? 11),
            'salesorder' => $salesorder,
            'savedItems' => $savedItems,
            'ppnAmount' => (float) ($salesorder->famountpopajak ?? 0),
            'famountgross' => (float) ($salesorder->famountgross ?? 0),
            'famountso' => (float) ($salesorder->famountso ?? 0),
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'filterSalesmanId' => $request->query('filter_salesman_id'),
        ]);
    }

    public function update(Request $request, $ftrsomtid)
    {
        // 1. VALIDATION (Sama seperti store)
        $request->validate([
            'fsono' => ['nullable', 'string', 'max:25'],
            'fsodate' => ['required', 'date'],
            'fkirimdate' => ['nullable', 'date'],
            'fcustno' => ['required', 'string', 'max:20'],
            'fsalesman' => ['nullable', 'string', 'max:20'],
            'fincludeppn' => ['nullable'],
            'fket' => ['nullable', 'string', 'max:300'],
            'falamatkirim' => ['nullable', 'string', 'max:300'],
            'fbranchcode' => ['nullable', 'string', 'max:2'],
            'ftempohr' => ['nullable', 'string', 'max:3'],

            'fprdcode' => ['required', 'array', 'min:1'],
            'fprdcode.*' => ['required', 'string', 'max:50'],

            'fsatuan' => ['nullable', 'array'],
            'fsatuan.*' => ['nullable', 'string', 'max:20'],

            'fitemname' => ['nullable', 'array'],
            'fitemname.*' => ['nullable', 'string', 'max:200'],

            'fqty' => ['required', 'array'],
            'fqty.*' => ['numeric', 'min:0'],
            'fapplyppn' => ['nullable'],
            'fppnpersen' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'fprice' => ['nullable', 'array'],
            'fprice.*' => ['numeric', 'min:0'],

            'fdisc' => ['nullable', 'array'],
            'fdisc.*' => ['nullable'], // Support "10+2"
            'fnoacak' => ['nullable', 'array'],
            'fnoacak.*' => ['nullable', 'regex:/^[1-9]{3}$/'],
        ], [
            'fsodate.required' => 'Tanggal SO wajib diisi.',
            'fcustno.required' => 'Customer wajib diisi.',
            'fprdcode.required' => 'Minimal 1 item.',
        ]);

        // 2. LOAD HEADER
        $header = DB::table('trsomt')->where('ftrsomtid', $ftrsomtid)->first();
        if (! $header) {
            return abort(404, 'Sales Order tidak ditemukan.');
        }

        if ($message = $this->getUsageLockMessage((object) $header)) {
            return redirect()->route('salesorder.index')->with('error', $message);
        }

        // 3. HEADER VALUES
        $fsodate = Carbon::parse($request->fsodate)->startOfDay();
        $fincludeppn = $request->input('fincludeppn', '0'); // 0: Exclude, 1: Include
        $fapplyppn = $request->input('fapplyppn') == '1' ? '1' : '0';
        $fppnpersen = (float) $request->input('fppnpersen', 11);
        $fclose = $request->input('fclose') ? '1' : '0';
        $userid = auth('sysuser')->user()->fname ?? 'admin';
        $now = now();

        // 4. DETAIL ARRAYS
        $itemId = $request->input('fprdcodeid', []);
        $itemCodes = $request->input('fprdcode', []);
        $itemNames = $request->input('fitemname', []);
        $satuans = $request->input('fsatuan', []);
        $qtys = $request->input('fqty', []);
        $prices = $request->input('fprice', []);
        $discs = $request->input('fdisc', []);
        $descs = $request->input('fdesc', []);
        $fnoacaks = $request->input('fnoacak', []);

        // 5. BUILD DETAIL ROWS (Logika sama dengan store)
        $rowsSodt = [];
        $totalGross = 0.0;
        $totalDisc = 0.0;
        $usedNoAcaks = [];
        $rowCount = max(
            count($itemCodes),
            count($satuans),
            count($qtys),
            count($prices),
            count($discs),
            count($descs),
            count($itemNames)
        );

        for ($i = 0; $i < $rowCount; $i++) {
            $itemeId = trim($itemId[$i] ?? '');
            $itemCode = trim($itemCodes[$i] ?? '');
            $itemName = trim((string) ($itemNames[$i] ?? ''));
            $satuan = trim((string) ($satuans[$i] ?? ''));
            $qty = (float) ($qtys[$i] ?? 0);
            $price = (float) ($prices[$i] ?? 0);
            $discInput = $discs[$i] ?? 0;
            $desc = (string) ($descs[$i] ?? '');

            if (empty($itemCode) || $qty <= 0) {
                continue;
            }

            $produk = DB::table('msprd')
                ->where('fprdcode', $itemCode)
                ->select('fprdid', 'fsatuanbesar', 'fqtykecil as rasio_konversi')
                ->first();

            $itemeId = $produk ? $produk->fprdid : $itemeId;

            $qtyKecil = $qty;
            if ($produk && $satuan === $produk->fsatuanbesar) {
                $qtyKecil = $qty * (float) $produk->rasio_konversi;
            }

            $discPersen = $this->parseDiscount($discInput);
            $subtotal = $qty * $price;
            $discount = $subtotal * ($discPersen / 100);
            $amount = $subtotal - $discount;

            $totalGross += $subtotal;
            $totalDisc += $discount;

            if (empty($itemeId) && ! empty($itemCode)) {
                $itemeId = DB::table('msprd')
                    ->where('fprdcode', $itemCode) // ✅ Gunakan fprdcode, bukan fprdid
                    ->value('fprdid'); // Return fprdid (integer)
            }

            $rowsSodt[] = [
                'fsono' => $header->fsono, // Gunakan fsono yang sudah ada
                'fprdcodeid' => ! empty($itemeId) && is_numeric($itemeId) ? (int) $itemeId : null,
                'fprdcode' => $itemCode,
                'fnoacak' => $this->normalizeRandomNumber($fnoacaks[$i] ?? null, $usedNoAcaks),
                'fsatuan' => mb_substr($satuan, 0, 20),
                'fdesc' => $desc,
                'fqty' => $qty,
                'fprice' => $price,
                'fpricenet' => $amount,
                'fdiscpersen' => $discPersen,
                'fdiscount' => round($discount, 2),
                'famount' => round($amount, 2),
                'fqtykecil' => $qtyKecil,
                'fqtyremain' => $qtyKecil,
            ];
        }

        // 6. CALCULATE TOTALS
        $amountNet = $totalGross - $totalDisc;

        if ($fapplyppn === '1') {
            if ($fincludeppn === '1') {
                // Include: amountNet sudah termasuk pajak
                $grandTotal = $amountNet;
                $ppnAmount = $grandTotal * ($fppnpersen / (100 + $fppnpersen));
                $amountNet = $grandTotal - $ppnAmount; // DPP dihitung mundur
            } else {
                // Exclude: amountNet + pajak
                $ppnAmount = $amountNet * ($fppnpersen / 100);
                $grandTotal = $amountNet + $ppnAmount;
            }
        } else {
            $ppnAmount = 0;
            $fppnpersen = 0;
            $grandTotal = $amountNet;
        }

        // 7. TRANSACTION
        DB::transaction(function () use (
            $request,
            $ftrsomtid,
            $header,
            $fsodate,
            $fincludeppn,
            $fclose,
            $userid,
            $now,
            $rowsSodt,
            $totalGross,
            $totalDisc,
            $amountNet,
            $grandTotal,
            $ppnAmount,
            $fapplyppn,
            $fppnpersen
        ) {
            // Update Header
            DB::table('trsomt')->where('ftrsomtid', $ftrsomtid)->update([
                'fsodate' => $fsodate,
                'fbranchcode' => mb_substr($request->input('fbranchcode', ''), 0, 2),
                'fcustno' => mb_substr($request->input('fcustno', ''), 0, 20),
                'fsalesman' => mb_substr((string) $request->input('fsalesman', ''), 0, 20) ?: null,
                'ftempohr' => mb_substr($request->input('ftempohr', '0'), 0, 3),
                'fincludeppn' => $fincludeppn,
                'fclose' => $fclose,
                'fket' => mb_substr($request->input('fket', ''), 0, 300),
                'fketinternal' => mb_substr($request->input('fketinternal', ''), 0, 300),
                'falamatkirim' => mb_substr($request->input('falamatkirim', ''), 0, 300),
                'fuserupdate' => mb_substr($userid, 0, 10),
                'fdatetime' => $now,
                'fapplyppn' => $fapplyppn,
                'fppnpersen' => $fppnpersen,
                'famountgross' => round($totalGross, 2),
                'fdiscount' => round($totalDisc, 2),
                'fdiscpersen' => ($totalGross > 0) ? round(($totalDisc / $totalGross) * 100, 2) : 0,
                'famountsonet' => round($amountNet, 2),
                'famountpajak' => round($ppnAmount, 2),
                'famountso' => round($grandTotal, 2),
            ]);

            // Delete old details and insert new ones
            DB::table('trsodt')->where('fsono', $header->fsono)->delete();
            // UPDATE STOK - gunakan qtyKecil hasil konversi, bukan qty mentah
            foreach ($rowsSodt as $row) {
                DB::table('msprd')
                    ->where('fprdcode', $row['fprdcode'])
                    ->update([
                        'fminstock' => DB::raw('CAST(fminstock AS NUMERIC) - '.$row['fqtyremain']),
                        'fupdatedat' => now(),
                    ]);
            }
            if (! empty($rowsSodt)) {
                DB::table('trsodt')->insert($rowsSodt);
            }
        });

        return redirect()
            ->route('salesorder.index')
            ->with('success', "Sales Order {$header->fsono} berhasil diperbarui.");
    }

    public function delete(Request $request, $ftrsomtid)
    {
        $customers = Customer::orderBy('fcustomername', 'asc')
            ->get(['fcustomercode', 'fcustomername']);

        $salesmans = Salesman::orderBy('fsalesmanname', 'asc')
            ->get(['fsalesmancode', 'fsalesmanname']);

        $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

        $branch = DB::table('mscabang')
            ->when(is_numeric($raw), fn ($q) => $q->where('fcabangid', (int) $raw))
            ->when(! is_numeric($raw), fn ($q) => $q
                ->where('fcabangkode', $raw)
                ->orWhere('fcabangname', $raw))
            ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

        $fcabang = $branch->fcabangname ?? (string) $raw;   // tampilan
        $fbranchcode = $branch->fcabangkode ?? (string) $raw;   // hidden post

        $salesorder = SalesOrderHeader::with(['customer', 'details' => function ($q) { // TAMBAHKAN 'customer' di sini
            $q->orderBy('trsodt.ftrsodtid')
                ->leftJoin('msprd', function ($j) {
                    $j->on('msprd.fprdid', '=', DB::raw('CAST(trsodt.fprdcodeid AS INTEGER)'));
                })
                ->select(
                    'trsodt.*',
                    'msprd.fprdcode as fprdcode',
                    'msprd.fprdname'
                );
        }])->findOrFail($ftrsomtid);

        if (! $salesorder->customer) {
            $salesorder->setRelation('customer', Customer::where('fcustomercode', trim((string) $salesorder->fcustno))->first());
        }

        $usageLockMessage = $this->getUsageLockMessage($salesorder);

        $soRemainMap = $this->getSoRemainByIds($salesorder->details->pluck('ftrsodtid')->all());

        $savedItems = $salesorder->details->map(function ($d) use ($soRemainMap) {
            return [
                'uid' => $d->ftrsodtid,
                'fprdcode' => (string) ($d->fprdcode ?? ''),
                'fitemname' => (string) ($d->fprdname ?? ''),
                'fsatuan' => (string) ($d->fsatuan ?? ''),
                'fnoacak' => (string) ($d->fnoacak ?? ''),
                'frefdtno' => (string) ($d->frefdtno ?? ''),
                'fnouref' => (string) ($d->fnouref ?? ''),
                'fqty' => (float) ($d->fqty ?? 0),
                'fqtyremain' => (float) ($soRemainMap[(int) ($d->ftrsodtid ?? 0)] ?? 0),
                'fterima' => (float) ($d->fterima ?? 0),
                'fprice' => (float) ($d->fprice ?? 0),
                'fdisc' => (float) ($d->fdiscpersen ?? 0),
                'ftotal' => (float) ($d->famount ?? 0),
                'fdesc' => (string) ($d->fdesc ?? ''),
                'fketdt' => (string) ($d->fketdt ?? ''),
            ];
        })->values();
        $selectedSupplierCode = $salesorder->fsupplier;

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
                $p->fprdcodeid => [
                    'name' => $p->fprdname,
                    'units' => array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2])),
                    'stock' => $p->fminstock ?? 0,
                ],
            ];
        })->toArray();

        // Pass the data to the view
        return view('salesorder.edit', [
            'customers' => $customers,
            'salesmans' => $salesmans,
            'selectedSupplierCode' => $selectedSupplierCode, // Kirim kode supplier ke view
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'products' => $products,
            'productMap' => $productMap,
            'salesorder' => $salesorder,
            'savedItems' => $savedItems,
            'fppnpersen' => (float) ($salesorder->fppnpersen ?? 11),
            'ppnAmount' => (float) ($salesorder->famountpopajak ?? 0), // total PPN from DB
            'famountgross' => (float) ($salesorder->famountgross ?? 0),  // nilai Grand Total dari DB
            'famountso' => (float) ($salesorder->famountso ?? 0),  // nilai Grand Total dari DB
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'filterSalesmanId' => $request->query('filter_salesman_id'),
            'isUsageLocked' => ! empty($usageLockMessage),
            'usageLockMessage' => $usageLockMessage,
            'action' => 'delete',
        ]);
    }

    public function destroy($ftrsomtid)
    {
        try {
            $salesorder = SalesOrderHeader::findOrFail($ftrsomtid);

            if ($message = $this->getUsageLockMessage($salesorder)) {
                return redirect()->route('salesorder.index')->with('error', $message);
            }

            DB::transaction(function () use ($salesorder) {
                DB::table('trsodt')
                    ->where('fsono', $salesorder->fsono)
                    ->delete();

                $salesorder->delete();
            });

            return redirect()->route('salesorder.index')->with('success', 'Data Sales Order '.$salesorder->fsono.' berhasil dihapus.');
        } catch (\Exception $e) {
            // Jika terjadi kesalahan saat menghapus, kembali ke halaman delete dengan pesan error
            return redirect()->route('salesorder.delete', $ftrsomtid)->with('error', 'Gakey: gal menghapus data: '.$e->getMessage());
        }
    }

    /**
     * Hitung sisa qty SO dinamis dalam satuan kecil per detail SO.
     *
     * @param  array<int, int|string>  $soDetailIds
     * @return array<int, float>
     */
    private function getSoRemainByIds(array $soDetailIds): array
    {
        $ids = collect($soDetailIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            return [];
        }

        return DB::table('trsodt as d')
            ->whereIn('d.ftrsodtid', $ids)
            ->selectRaw('d.ftrsodtid, GREATEST(COALESCE(d.fqtykecil, 0), 0) AS remain_kecil')
            ->pluck('remain_kecil', 'd.ftrsodtid')
            ->map(fn ($value) => (float) $value)
            ->all();
    }

    private function getUsageLockMessage($header): ?string
    {
        $fsono = trim((string) ($header->fsono ?? ''));
        if ($fsono === '') {
            return null;
        }

        $usedBySrj = DB::table('trstockdt as dt')
            ->join('trstockmt as mt', 'mt.fstockmtno', '=', 'dt.fstockmtno')
            ->where('mt.fstockmtcode', 'SRJ')
            ->where('dt.frefso', $fsono)
            ->select('mt.fstockmtno')
            ->distinct()
            ->orderBy('mt.fstockmtno')
            ->pluck('mt.fstockmtno');

        $usedBySalesDocs = DB::table('trandt as dt')
            ->join('tranmt as mt', 'mt.fsono', '=', 'dt.fsono')
            ->where('dt.frefso', $fsono)
            ->select('mt.fsono')
            ->distinct()
            ->orderBy('mt.fsono')
            ->pluck('mt.fsono');

        $parts = [];
        if ($usedBySrj->isNotEmpty()) {
            $parts[] = 'Surat Jalan: '.$usedBySrj->implode(', ');
        }

        $usedByInvoice = $usedBySalesDocs->filter(fn ($no) => str_starts_with((string) $no, 'INV.'));
        if ($usedByInvoice->isNotEmpty()) {
            $parts[] = 'Faktur Penjualan: '.$usedByInvoice->implode(', ');
        }

        $usedByRetur = $usedBySalesDocs->filter(fn ($no) => str_starts_with((string) $no, 'REJ.'));
        if ($usedByRetur->isNotEmpty()) {
            $parts[] = 'Retur Penjualan: '.$usedByRetur->implode(', ');
        }

        if (empty($parts)) {
            return null;
        }

        return 'Sales Order '.$fsono.' tidak dapat diubah atau dihapus karena sudah digunakan pada '.implode('; ', $parts).'.';
    }
}
