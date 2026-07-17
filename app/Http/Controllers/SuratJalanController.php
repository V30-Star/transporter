<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PenerimaanPembelianDetail;
use App\Models\PenerimaanPembelianHeader;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // sekalian biar aman untuk tanggal
use Illuminate\Support\Facades\Log; // sekalian biar aman untuk tanggal
use Illuminate\Validation\ValidationException;

class SuratJalanController extends Controller
{
    private function resolveProductDefaultUnit($product): string
    {
        $defaultKey = trim((string) ($product->fsatuandefault ?? ''));
        $smallUnit = trim((string) ($product->fsatuankecil ?? ''));
        $largeUnit = trim((string) ($product->fsatuanbesar ?? ''));
        $largeUnit2 = trim((string) ($product->fsatuanbesar2 ?? ''));

        return match ($defaultKey) {
            '1' => $smallUnit,
            '2' => $largeUnit,
            '3' => $largeUnit2,
            default => in_array(strtoupper($defaultKey), [
                strtoupper($smallUnit),
                strtoupper($largeUnit),
                strtoupper($largeUnit2),
            ], true)
                ? $defaultKey
                : ($smallUnit ?: $largeUnit ?: $largeUnit2),
        };
    }

    private function buildProductMap($products): array
    {
        return $products->mapWithKeys(function ($product) {
            $defaultUnit = $this->resolveProductDefaultUnit($product);
            $units = array_values(array_unique(array_filter([
                $defaultUnit,
                $product->fsatuankecil,
                $product->fsatuanbesar,
                $product->fsatuanbesar2,
            ])));

            return [
                $product->fprdcode => [
                    'fprdid' => $product->fprdid,
                    'name' => $product->fprdname,
                    'default_unit' => $defaultUnit,
                    'units' => $units,
                    'stock' => $product->fminstock ?? 0,
                    'unit_ratios' => [
                        'satuankecil' => 1,
                        'satuanbesar' => (float) ($product->fqtykecil ?? 1),
                        'satuanbesar2' => (float) ($product->fqtykecil2 ?? 1),
                    ],
                ],
            ];
        })->toArray();
    }

    private function isSkippedSuratJalanProductCode(?string $code): bool
    {
        return in_array(strtoupper(trim((string) $code)), ['UM', 'AWAL'], true);
    }

    private function formatDisplayTransactionNumber(?string $number, bool $useSlash = false): string
    {
        $normalized = trim((string) $number);
        if ($normalized === '') {
            return '-';
        }

        $separator = $useSlash ? '/' : '.';

        return (string) preg_replace('/[.\/](\d+)$/', $separator . '$1', $normalized, 1);
    }

    private function ensureNoDuplicateDetailCodes(array $codes, array $refs = [], array $qtys = []): void
    {
        $seen = [];
        $duplicates = [];
        foreach ($codes as $index => $rawCode) {
            $rawCode = $codes[$index] ?? '';
            $code = strtoupper(trim((string) $rawCode));
            if ($code === '') {
                continue;
            }

            if ($this->isSkippedSuratJalanProductCode($code)) {
                continue;
            }

            $qty = (float) ($qtys[$index] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $ref = strtoupper(trim((string) ($refs[$index] ?? '')));
            $key = $code . '|' . $ref;

            if (isset($seen[$key])) {
                $duplicates[$index] = $code;
                continue;
            }

            $seen[$key] = true;
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
            ->map(fn($value) => trim((string) $value))
            ->filter(fn($value) => $value !== '')
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
                    fstockmtno, frefdtno,
                    STRING_AGG(DISTINCT NULLIF(TRIM(COALESCE(frefso, '')), ''), ', ' ORDER BY NULLIF(TRIM(COALESCE(frefso, '')), '')) as so_refs
                ")
                ->whereNotNull('frefso')
                ->groupBy('fstockmtno', 'frefdtno');

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
                        ->orWhere('trstockmt.fbranchcode', 'ilike', "%{$search}%")
                        ->orWhere('trstockmt.frefpo', 'ilike', "%{$search}%")
                        ->orWhere('so_refs.frefdtno', 'ilike', "%{$search}%")
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
                $query->where(function ($q) use ($warehouseSearch) {
                    $q->where('trstockmt.ffrom', 'ilike', "%{$warehouseSearch}%")
                        ->orWhere('warehouse.fwhname', 'ilike', "%{$warehouseSearch}%");
                });
            }

            $customerSearch = trim((string) ($columnSearches->get('fcustomername', '')));
            if ($customerSearch !== '') {
                $query->where('customer.fcustomername', 'ilike', "%{$customerSearch}%");
            }

            // Total records setelah filter
            $filteredRecords = (clone $query)->count();

            $orderColIdx = $request->input('order.0.column');
            $orderDir = $request->input('order.0.dir', 'desc');

            $orderColumn = null;
            if ($orderColIdx !== null) {
                $colName = $request->input("columns.{$orderColIdx}.name") ?: $request->input("columns.{$orderColIdx}.data");
                if ($colName === 'fbranchcode') {
                    $orderColumn = 'trstockmt.fbranchcode';
                } elseif ($colName === 'fstockmtno' || $colName === 'fstockmtno_display') {
                    $orderColumn = 'trstockmt.fstockmtno';
                } elseif ($colName === 'fstockmtdate') {
                    $orderColumn = 'trstockmt.fstockmtdate';
                } elseif ($colName === 'fcustomername') {
                    $orderColumn = 'customer.fcustomername';
                } elseif ($colName === 'fgudang') {
                    $orderColumn = 'trstockmt.ffrom';
                } elseif ($colName === 'frefdtno') {
                    $orderColumn = 'trstockmt.frefpo';
                } elseif ($colName === 'fsono') {
                    $orderColumn = 'so_refs.so_refs';
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
                    'trstockmt.fbranchcode',
                    'trstockmt.fstockmtdate',
                    'trstockmt.frefpo',
                    'trstockmt.ffrom',
                    'trstockmt.fusercreate',
                    'so_refs.frefdtno as frefdtno',
                    'warehouse.fwhname as warehouse_name',
                    'customer.fcustomername as customer_name',
                    DB::raw("COALESCE(so_refs.so_refs, '') as so_refs"),
                ]);

            $data = $records->map(function ($row) {
                $warehouseCode = trim((string) ($row->ffrom ?? ''));

                return [
                    'fstockmtid' => $row->fstockmtid,
                    'fstockmtno' => $row->fstockmtno,
                    'fstockmtno_display' => $this->formatDisplayTransactionNumber($row->fstockmtno ?? null, false),
                    'fbranchcode' => $row->fbranchcode,
                    'fstockmtdate' => Carbon::parse($row->fstockmtdate)->format('d/m/Y'),
                    'frefdtno' => (string) ($row->frefdtno ?? ''),
                    'fsono' => (string) ($row->so_refs ?? ''),
                    'fgudang' => $warehouseCode,
                    'fcustomername' => (string) ($row->customer_name ?? ''),
                    'fusercreate' => (string) ($row->fusercreate ?? ''),
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
        $onlyRemaining = $request->boolean('only_remaining');

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
                'trstockmt.fbranchcode',
                'trstockmt.ffrom',
                'mscustomer.fcustomercode',
                'mscustomer.fcustomername as fsuppliername',
                'mscustomer.faddress'
            );

        if ($customerCode !== '') {
            $query->whereRaw('TRIM(COALESCE(trstockmt.fsupplier, \'\')) = ?', [$customerCode]);
        }

        if ($onlyRemaining) {
            $query->whereExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('trstockdt as d')
                    ->whereColumn('d.fstockmtno', 'trstockmt.fstockmtno')
                    ->whereRaw('COALESCE(d.fqtyremain, 0) > 0');
            });
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
            ->when($onlyRemaining, function ($query) {
                $query->whereExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('trstockdt as d')
                        ->whereColumn('d.fstockmtno', 'trstockmt.fstockmtno')
                        ->whereRaw('COALESCE(d.fqtyremain, 0) > 0');
                });
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

        $header->has_so_reference = DB::table('trstockdt')
            ->where('fstockmtno', $header->fstockmtno)
            ->whereRaw("TRIM(COALESCE(frefso, '')) <> ''")
            ->exists();

        $sourceSoHeader = DB::table('trstockdt as dt')
            ->join('trsomt as so', 'so.fsono', '=', 'dt.frefso')
            ->where('dt.fstockmtno', $header->fstockmtno)
            ->whereRaw("TRIM(COALESCE(dt.frefso, '')) <> ''")
            ->orderBy('dt.fstockdtid')
            ->first(['so.fsalesman', 'so.ftempohr', 'so.fincludeppn', 'so.fapplyppn', 'so.fppnpersen']);

        if ($sourceSoHeader) {
            $header->fsalesman = trim((string) ($sourceSoHeader->fsalesman ?? ''));
            $header->ftempohr = (float) ($sourceSoHeader->ftempohr ?? 0);
            $header->fincludeppn = (int) ($sourceSoHeader->fincludeppn ?? 0);
            $header->fapplyppn = (int) ($sourceSoHeader->fapplyppn ?? 0);
            $header->fppnpersen = (float) ($sourceSoHeader->fppnpersen ?? 11);
        } else {
            $customerDefaults = DB::table('mscustomer')->where('fcustomercode', $header->fsupplier)->first(['ftempo', 'fsalesman']);
            $header->ftempohr = (float) ($customerDefaults->ftempo ?? 0);
            $header->fsalesman = trim((string) ($customerDefaults->fsalesman ?? ''));
        }

        $items = DB::table('trstockdt')
            ->where('trstockdt.fstockmtno', $header->fstockmtno)
            ->leftJoin('msprd', 'msprd.fprdcode', '=', 'trstockdt.fprdcode')
            ->select(
                'trstockdt.fstockmtno as frefdtno',
                'trstockdt.fstockdtid as frefdtid',
                DB::raw("COALESCE(trstockdt.fnoacak::text, '') as frefnoacak"),
                // UBAH BAGIAN INI: Ambil kolom kode dari msprd (misal: fprdcode_string)
                // atau pastikan kolom ini memang yang berisi kode produk
                'msprd.fprdcode as fitemcode',
                'msprd.fprdname as fitemname',
                'trstockdt.fqty',
                'trstockdt.fqtyremain',
                'trstockdt.fdiscpersen',
                'trstockdt.frefso',
                'trstockdt.fsatuan',
                'trstockdt.fprice',
                'trstockdt.fnoacak',
                'trstockdt.ftotprice as ftotal'
            )
            ->get()
            ->map(function ($item) {
                $remain = max(0, (float) ($item->fqtyremain ?? 0));
                $item->fqty_dokumen = (float) ($item->fqty ?? 0);
                $item->fqtyremain = $remain;
                $item->maxqty = $remain;

                return $item;
            });

        return response()->json([
            'header' => $header,
            'items' => $items,
        ]);
    }

    private function canCreateInvoice(): bool
    {
        return in_array('createInvoice', explode(',', session('user_restricted_permissions', '')), true);
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

        $prefix = sprintf('PO.%s.%s.%s.00.', $kodeCabang, $date->format('y'), $date->format('m'));

        // kunci per (branch, tahun-bulan) — TANPA bikin tabel baru
        $lockKey = crc32('PO|' . $kodeCabang . '|' . $date->format('Y-m'));
        DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

        $last = DB::table('trstockmt')
            ->where('fstockmtno', 'like', $prefix . '%')
            ->selectRaw("MAX(CAST(split_part(fstockmtno, '.', 6) AS int)) AS lastno")
            ->value('lastno');

        $next = (int) $last + 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function print(string $fstockmtno)
    {
        // 1. Ambil query sub untuk customer
        $customerSub = Customer::select('fcustomerid', 'fcustomercode', 'fcustomername', 'faddress');

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
                'cust.faddress as customer_address',   // Ambil alamat customer
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
            'displayFstockmtno' => $this->formatDisplayTransactionNumber($hdr->fstockmtno ?? null, false),
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
            'fsatuandefault',
            'fsatuankecil',
            'fsatuanbesar',
            'fsatuanbesar2',
            'fqtykecil',
            'fqtykecil2',
            'fminstock'
        )->orderBy('fprdname')->get();

        $productMap = $this->buildProductMap($products);

        return view('suratjalan.create', [
            'newtr_prh_code' => $newtr_prh_code,
            'warehouses' => $warehouses,
            'customers' => $customers,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'products' => $products,
            'productMap' => $productMap,
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'autoLoadSalesOrderId' => $request->query('sales_order_id'),
            'autoLoadInvoiceId' => $request->query('invoice_id'),
        ]);
    }

    public function store(Request $request)
    {
        $allowNegativeStockQty = stock_boleh_minus();
        $userid = auth('sysuser')->user()->fsysuserid ?? 'admin';

        // =========================
        // 1) VALIDASI INPUT
        // =========================
        try {
            $request->validate([
                'fstockmtno' => ['nullable', 'string', 'max:100'],
                'fstockmtdate' => ['required', 'date'],
                'fsupplier' => ['required', 'string', 'max:30'],
                'ffrom' => ['required', 'string', 'max:10'],
                'fket' => ['nullable', 'string', 'max:50'],
                'fketinternal' => ['nullable', 'string', 'max:300'],
                'fkirim' => ['nullable', 'string', 'max:300'],
                'fbranchcode' => ['nullable', 'string', 'max:20'],
                'fitemcode' => ['required', 'array', 'min:1'],
                'fitemcode.*' => ['required', 'string', 'max:50'],
                'fsatuan' => ['nullable', 'array'],
                'fsatuan.*' => ['nullable', 'string', 'max:20'],
                'frefdtno' => ['nullable', 'array'],
                'frefdtno.*' => ['nullable', 'string', 'max:100'],
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
                'frefso' => ['nullable', 'array'],
                'frefso.*' => ['nullable', 'string', 'max:100'],
                'fdiscpersen' => ['nullable', 'array'],
                'fdiscpersen.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'fnoacak' => ['nullable', 'array'],
                'fnoacak.*' => ['nullable', 'regex:/^[1-9]{3}$/'],
                'frefnoacak' => ['nullable', 'array'],
                'frefnoacak.*' => ['nullable', 'regex:/^\d{3}$/'],
            ], [
                'ffrom.required' => 'Gudang wajib diisi.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        }

        $this->ensureNoDuplicateDetailCodes(
            $request->input('fitemcode', []),
            $request->input('frefdtno', []),
            $request->input('fqty', [])
        );

        // =========================
        // 2) HEADER FIELDS
        // =========================
        $fstockmtno = trim((string) $request->input('fstockmtno'));
        $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
        $this->ensureCreateDateWithinEditPeriod($fstockmtdate);
        $fsupplier = trim((string) $request->input('fsupplier'));
        $ffrom = trim((string) $request->input('ffrom'));
        $fket = trim((string) $request->input('fket', ''));
        $fketinternal = trim((string) $request->input('fketinternal', ''));
        $fkirim = trim((string) $request->input('fkirim', ''));
        $fbranchcode = $request->input('fbranchcode');
        $fcurrency = $request->input('fcurrency', 'IDR');
        $frate = (float) $request->input('frate', 1);
        if ($frate <= 0) {
            $frate = 1;
        }
        $ppnAmount = (float) $request->input('famountpopajak', 0);
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
        $fdiscpersens = $request->input('fdiscpersen', []);
        $fnoacaks = $request->input('fnoacak', []);
        $frefnoacaks = $request->input('frefnoacak', []);

        $uniqueCodes = array_values(array_unique(
            array_filter(
                array_map(fn($c) => trim((string) $c), $codes),
                fn($code) => $code !== '' && ! $this->isSkippedSuratJalanProductCode($code)
            )
        ));

        // =========================
        // 4) PRELOAD MASTER PRODUK
        // =========================
        $prodMeta = DB::table('msprd')
            ->whereIn('fprdcode', $uniqueCodes)
            ->get(['fprdid', 'fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2', 'fqtykecil', 'fqtykecil2'])
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

        foreach ($codes as $i => $rawCode) {
            $code = trim((string) $rawCode);
            if ($this->isSkippedSuratJalanProductCode($code)) {
                continue;
            }

            $sat = trim((string) ($satuans[$i] ?? ''));
            $rref = $refdtno[$i] ?? null;
            $qty = (float) ($qtys[$i] ?? 0);
            $price = (float) ($prices[$i] ?? 0);
            $desc = (string) ($descs[$i] ?? '');

            $meta = $prodMeta[$code] ?? null;

            $frefdtnoValue = trim((string) ($rref ?? ''));
            $refDoc = trim((string) ($frefso[$i] ?? ''));
            $refNoAcak = $this->normalizeReferenceRandomNumber($frefnoacaks[$i] ?? null);

            $referenceRatio = null;
            if ($refDoc !== '') {
                $referenceDetail = $this->resolveSuratJalanReferenceDetail($refDoc, $code, $refNoAcak);
                if ($referenceDetail && ! empty($referenceDetail->fsatuan)) {
                    $sat = trim((string) $referenceDetail->fsatuan);
                }
                if ($referenceDetail) {
                    $referenceQty = (float) ($referenceDetail->fqty ?? 0);
                    $referenceQtyKecil = (float) ($referenceDetail->fqtykecil ?? 0);
                    if ($referenceQty > 0 && $referenceQtyKecil > 0) {
                        $referenceRatio = $referenceQtyKecil / $referenceQty;
                    }
                }
            }

            if ($sat === '') {
                $sat = $pickDefaultSat($meta);
            }
            $sat = mb_substr($sat, 0, 5);
            if ($sat === '') {
                // LOG 3: Deteksi item yang dilewati karena tidak punya satuan valid
                Log::debug("SuratJalan@store: Detail baris index-{$i} dilewati karena satuan kosong.", ['code' => $code]);
                continue;
            }

            $qtyKecil = $qty;
            if ($referenceRatio !== null && $referenceRatio > 0) {
                $qtyKecil = $qty * $referenceRatio;
            } elseif ($meta && $sat === trim((string) ($meta->fsatuanbesar ?? '')) && (float) $meta->fqtykecil > 0) {
                $qtyKecil = $qty * (float) $meta->fqtykecil;
            } elseif ($meta && $sat === trim((string) ($meta->fsatuanbesar2 ?? '')) && (float) ($meta->fqtykecil2 ?? 0) > 0) {
                $qtyKecil = $qty * (float) $meta->fqtykecil2;
            }

            $frefdtnoValue = $refDoc !== '' ? $refDoc : $frefdtnoValue;
            $amount = $qty * $price;
            $subtotal += $amount;

            $rowsDt[] = [
                'fprdcode' => $code,
                'frefdtno' => $frefdtnoValue !== '' ? mb_substr($frefdtnoValue, 0, 100) : null,
                'fqty' => $qty,
                'fprice' => $price,
                'fprice_rp' => $price * $frate,
                'ftotprice' => $amount,
                'ftotprice_rp' => $amount * $frate,
                'fusercreate' => Auth::user()->fname ?? 'system',
                'fdatetime' => $now,
                'fketdt' => '',
                'fcode' => $this->resolveSuratJalanFcode(['frefso' => $frefso[$i] ?? null]),
                'frefso' => $frefso[$i] ?? null,
                'fnoacak' => $this->normalizeRandomNumber($fnoacaks[$i] ?? null, $usedNoAcaks),
                'frefnoacak' => trim((string) ($frefso[$i] ?? '')) !== '' ? $refNoAcak : null,
                'fdesc' => $desc,
                'fsatuan' => $sat,
                'fclosedt' => '0',
                'fdiscpersen' => max(0, min(100, (float) ($fdiscpersens[$i] ?? 0))),
                'fbiaya' => 0,
                'fqtykecil' => $qtyKecil,
                'fqtyremain' => $qtyKecil,
            ];
        }

        if (empty($rowsDt)) {
            return back()->withInput()->withErrors([
                'detail' => 'Minimal satu item valid (Kode, Satuan, Qty > 0).',
            ]);
        }

        $soUsageByReference = $this->buildSuratJalanReferenceUsageMap($rowsDt);
        $invoiceReferenceDocs = $this->extractInvoiceReferenceDocs($rowsDt);

        // =========================
        // 6.5) VALIDASI QTY REMAIN SO
        // =========================
        if ($validationMessage = $this->validateSoUsageRequest($soUsageByReference)) {
            return back()->withInput()->withErrors(['detail' => $validationMessage]);
        }

        // =========================
        // 7) TRANSAKSI DB
        // =========================
        try {
            $newStockMasterId = null;

            DB::transaction(function () use (
                $fstockmtdate,
                $fsupplier,
                $ffrom,
                $fket,
                $fkirim,
                $fketinternal,
                $fbranchcode,
                $fcurrency,
                $frate,
                $userid,
                $now,
                &$fstockmtno,
                &$rowsDt,
                $subtotal,
                $ppnAmount,
                &$newStockMasterId
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
                    $prefix = sprintf('%s.%s.%s.%s.00.', $fstockmtcode, $kodeCabang, $yy, $mm);
                    $lockKey = crc32('STOCKMT|' . $fstockmtcode . '|' . $kodeCabang . '|' . $fstockmtdate->format('y-m'));
                    DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

                    $last = DB::table('trstockmt')
                        ->where('fstockmtno', 'like', $prefix . '%')
                        ->selectRaw("MAX(CAST(split_part(fstockmtno, '.', 6) AS int)) AS lastno")
                        ->value('lastno');
                    $next = (int) $last + 1;
                    $fstockmtno = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);

                    Log::debug("SuratJalan@store: Nomor SRJ di-generate otomatis [{$fstockmtno}].");
                } else {
                    Log::debug("SuratJalan@store: Menggunakan nomor SRJ manual dari request [{$fstockmtno}].");
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
                    'fketinternal' => $fketinternal,
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
                    throw new \Exception('Gagal menyimpan data master (header) Surat Jalan.');
                }

                foreach ($rowsDt as &$r) {
                    $r['fstockmtcode'] = $fstockmtcode;
                    $r['fstockmtno'] = $fstockmtno;
                }
                unset($r);

                DB::table('trstockdt')->insert($rowsDt);
            });
        $redirect = redirect()
            ->route('suratjalan.create')
            ->with('success', 'Surat jalan ' . $this->formatDisplayTransactionNumber($fstockmtno, false) . ' berhasil disimpan.');

        if (! $this->canCreateInvoice() || ! $newStockMasterId || ! empty($invoiceReferenceDocs)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Surat jalan ' . $this->formatDisplayTransactionNumber($fstockmtno, false) . ' berhasil disimpan.',
                    'redirect_url' => route('suratjalan.create'),
                ]);
            }
            return $redirect;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Surat jalan ' . $this->formatDisplayTransactionNumber($fstockmtno, false) . ' berhasil disimpan.',
                'redirect_url' => route('suratjalan.create'),
                'success_prompt' => [
                    'type' => 'suratjalan_create_invoice',
                    'redirect_url' => route('invoice.create', ['surat_jalan_id' => $newStockMasterId]),
                ]
            ]);
        }

        return $redirect->with('success_prompt', [
            'type' => 'suratjalan_create_invoice',
            'redirect_url' => route('invoice.create', ['surat_jalan_id' => $newStockMasterId]),
        ]);
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Data belum berhasil disimpan: ' . $e->getMessage()], 500);
            }
            return back()->withInput()->withErrors([
                'detail' => 'Data belum berhasil disimpan. Cek data log internal.',
            ]);
        }
    }

    public function edit(Request $request, $fstockmtid)
    {
        $customers = Customer::orderBy('fcustomername', 'asc')
            ->get(['fcustomercode', 'fcustomername']);

        $warehouses = DB::table('mswh')
            ->select('fwhid', 'fwhcode', 'fwhname', 'fbranchcode', 'fnonactive')
            ->where('fnonactive', '0') // hanya yang aktif
            ->orderBy('fwhcode')
            ->get();

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
                        'msprd.fprdcode as fitemcode_text', // Ambil KODE string produk
                        'msprd.fsatuankecil',
                        'msprd.fsatuanbesar',
                        'msprd.fsatuanbesar2',
                        'msprd.fqtykecil as fprd_qtykonversi',
                        'msprd.fqtykecil2 as fprd_qtykonversi2'
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

        ['fcabang' => $fcabang, 'fbranchcode' => $fbranchcode] = $this->resolveBranchContext($suratjalan->fbranchcode ?? null);

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
            $maxqty = max(0, (float) ($stat['remain_qty_kecil'] ?? 0));
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
            'fsatuandefault',
            'fsatuankecil',
            'fsatuanbesar',
            'fsatuanbesar2',
            'fqtykecil',
            'fqtykecil2',
            'fminstock'
        )->orderBy('fprdname')->get();

        $productMap = $this->buildProductMap($products);

        return view('suratjalan.edit', [
            'customers' => $customers,
            'selectedSupplierCode' => $selectedSupplierCode,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'warehouses' => $warehouses,
            'products' => $products,
            'productMap' => $productMap,
            'suratjalan' => $suratjalan,
            'displayFstockmtno' => $this->formatDisplayTransactionNumber($suratjalan->fstockmtno ?? null, false),
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

        $warehouses = DB::table('mswh')
            ->select('fwhid', 'fwhcode', 'fwhname', 'fbranchcode', 'fnonactive')
            ->where('fnonactive', '0') // hanya yang aktif
            ->orderBy('fwhcode')
            ->get();

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
                        'msprd.fprdcode as fitemcode_text', // Ambil KODE string produk
                        'msprd.fsatuankecil',
                        'msprd.fsatuanbesar',
                        'msprd.fsatuanbesar2',
                        'msprd.fqtykecil as fprd_qtykonversi',
                        'msprd.fqtykecil2 as fprd_qtykonversi2'
                    )
                    ->orderBy('trstockdt.fstockdtid', 'asc');
            },
        ])
            ->leftJoin('mswh', 'mswh.fwhcode', '=', 'trstockmt.ffrom')
            ->select('trstockmt.*', 'mswh.fwhcode as ffrom_code')
            ->findOrFail($fstockmtid); // Temukan header berdasarkan $fstockmtid dari URL

        ['fcabang' => $fcabang, 'fbranchcode' => $fbranchcode] = $this->resolveBranchContext($suratjalan->fbranchcode ?? null);

        // 4. Map the data for savedItems (sudah menggunakan data yang benar)
        $soReferenceStats = $this->getSoReferenceStats(
            $suratjalan->details->pluck('frefso')->filter()->map(fn($value) => trim((string) $value))->unique()->values()->all(),
            $suratjalan->fstockmtno
        );

        $savedItems = $suratjalan->details->map(function ($d) use ($soReferenceStats) {
            $referenceKey = $this->buildSoReferenceUsageKey($d->frefso ?? '', $d->fprdcode ?? '', $d->frefnoacak ?? '');
            $stat = $soReferenceStats[$referenceKey] ?? null;
            $maxqty = max(0, (float) ($stat['remain_qty_kecil'] ?? 0));
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
            'fsatuandefault',
            'fsatuankecil',
            'fsatuanbesar',
            'fsatuanbesar2',
            'fqtykecil',
            'fqtykecil2',
            'fminstock'
        )->orderBy('fprdname')->get();

        $productMap = $this->buildProductMap($products);

        return view('suratjalan.edit', [
            'customers' => $customers,
            'selectedSupplierCode' => $selectedSupplierCode,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'warehouses' => $warehouses,
            'products' => $products,
            'productMap' => $productMap,
            'suratjalan' => $suratjalan,
            'displayFstockmtno' => $this->formatDisplayTransactionNumber($suratjalan->fstockmtno ?? null, false),
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
        $allowNegativeStockQty = stock_boleh_minus();
        // =========================
        // 1) VALIDASI INPUT
        // =========================
        $request->validate([
            'fstockmtno' => ['nullable', 'string', 'max:100'],
            'fstockmtdate' => ['required', 'date'],
            'fsupplier' => ['required', 'string', 'max:30'],
            'ffrom' => ['required', 'string', 'max:10'],
            'fket' => ['nullable', 'string', 'max:50'],
            'fketinternal' => ['nullable', 'string', 'max:300'],
            'fkirim' => ['nullable', 'string', 'max:300'],
            'fbranchcode' => ['nullable', 'string', 'max:20'],
            'fitemcode' => ['required', 'array', 'min:1'],
            'fitemcode.*' => ['required', 'string', 'max:50'],
            'fsatuan' => ['nullable', 'array'],
            'fsatuan.*' => ['nullable', 'string', 'max:20'],
            'frefdtno' => ['nullable', 'array'],
            'frefdtno.*' => ['nullable', 'string', 'max:100'],
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
            'frefso' => ['nullable', 'array'],
            'frefso.*' => ['nullable', 'string', 'max:100'],
            'fdiscpersen' => ['nullable', 'array'],
            'fdiscpersen.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fnoacak' => ['nullable', 'array'],
            'fnoacak.*' => ['nullable', 'regex:/^[1-9]{3}$/'],
            'frefnoacak' => ['nullable', 'array'],
            'frefnoacak.*' => ['nullable', 'regex:/^\d{3}$/'],
        ], [
            'ffrom.required' => 'Gudang wajib diisi.',
        ]);

        $this->ensureNoDuplicateDetailCodes(
            $request->input('fitemcode', []),
            $request->input('frefdtno', []),
            $request->input('fqty', [])
        );

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
        $fketinternal = trim((string) $request->input('fketinternal', ''));
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
        $fdiscpersens = $request->input('fdiscpersen', []);
        $fnoacaks = $request->input('fnoacak', []);
        $frefnoacaks = $request->input('frefnoacak', []);

        $uniqueCodes = array_values(array_unique(
            array_filter(
                array_map(fn($c) => trim((string) $c), $codes),
                fn($code) => $code !== '' && ! $this->isSkippedSuratJalanProductCode($code)
            )
        ));

        // =========================
        // 4) PRELOAD MASTER PRODUK
        // =========================
        $prodMeta = DB::table('msprd')
            ->whereIn('fprdcode', $uniqueCodes)
            ->get(['fprdid', 'fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2', 'fqtykecil', 'fqtykecil2'])
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

        foreach ($codes as $i => $rawCode) {
            $code = trim((string) $rawCode);
            if ($this->isSkippedSuratJalanProductCode($code)) {
                continue;
            }

            $sat = trim((string) ($satuans[$i] ?? ''));
            $rref = $refdtno[$i] ?? null;
            $qty = (float) ($qtys[$i] ?? 0);
            $price = (float) ($prices[$i] ?? 0);
            $desc = (string) ($descs[$i] ?? '');

            $meta = $prodMeta[$code] ?? null;

            $frefdtnoValue = trim((string) ($rref ?? ''));
            $refDoc = trim((string) ($frefso[$i] ?? ''));
            $refNoAcak = $this->normalizeReferenceRandomNumber($frefnoacaks[$i] ?? null);

            $referenceRatio = null;
            if ($refDoc !== '') {
                $referenceDetail = $this->resolveSuratJalanReferenceDetail($refDoc, $code, $refNoAcak);
                if ($referenceDetail && ! empty($referenceDetail->fsatuan)) {
                    $sat = trim((string) $referenceDetail->fsatuan);
                }
                if ($referenceDetail) {
                    $referenceQty = (float) ($referenceDetail->fqty ?? 0);
                    $referenceQtyKecil = (float) ($referenceDetail->fqtykecil ?? 0);
                    if ($referenceQty > 0 && $referenceQtyKecil > 0) {
                        $referenceRatio = $referenceQtyKecil / $referenceQty;
                    }
                }
            }

            if ($sat === '') {
                $sat = $pickDefaultSat($meta);
            }
            $sat = mb_substr($sat, 0, 5);
            if ($sat === '') {
                continue;
            }

            $qtyKecil = $qty;
            if ($referenceRatio !== null && $referenceRatio > 0) {
                $qtyKecil = $qty * $referenceRatio;
            } elseif ($meta && $sat === trim((string) ($meta->fsatuanbesar ?? '')) && (float) $meta->fqtykecil > 0) {
                $qtyKecil = $qty * (float) $meta->fqtykecil;
            } elseif ($meta && $sat === trim((string) ($meta->fsatuanbesar2 ?? '')) && (float) ($meta->fqtykecil2 ?? 0) > 0) {
                $qtyKecil = $qty * (float) $meta->fqtykecil2;
            }

            $frefdtnoValue = $refDoc !== '' ? $refDoc : $frefdtnoValue;
            $amount = $qty * $price;
            $subtotal += $amount;

            $row = [
                'fprdcode' => $code,
                'frefdtno' => $frefdtnoValue !== '' ? mb_substr($frefdtnoValue, 0, 100) : null,
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
                    ? $refNoAcak
                    : null,
                'fdesc' => $desc,
                'fsatuan' => $sat,
                'fclosedt' => '0',
                'fdiscpersen' => max(0, min(100, (float) ($fdiscpersens[$i] ?? 0))),
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
                $fketinternal,
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

                $yy = $fstockmtdate->format('Y');
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
                    'fketinternal' => $fketinternal,
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
            });
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Data belum berhasil diperbarui: ' . $e->getMessage()], 500);
            }
            return back()->withInput()->withErrors([
                'detail' => 'Data belum berhasil diperbarui. Cek isian transaksi.',
            ]);
        }

        $this->syncInvoiceOutFlags(array_merge($oldInvoiceReferenceDocs, $newInvoiceReferenceDocs));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Surat jalan ' . $this->formatDisplayTransactionNumber($fstockmtno, false) . ' berhasil diupdate.',
                'redirect_url' => route('suratjalan.index'),
                'success_prompt' => ! $this->canCreateInvoice() ? null : [
                    'type' => 'suratjalan_create_invoice',
                    'redirect_url' => route('invoice.create', ['surat_jalan_id' => $fstockmtid]),
                ]
            ]);
        }

        $redirect = redirect()
            ->route('suratjalan.index')
            ->with('success', 'Surat jalan ' . $this->formatDisplayTransactionNumber($fstockmtno, false) . ' berhasil diupdate.');

        if (! $this->canCreateInvoice()) {
            return $redirect;
        }

        return $redirect->with('success_prompt', [
            'type' => 'suratjalan_create_invoice',
            'redirect_url' => route('invoice.create', ['surat_jalan_id' => $fstockmtid]),
        ]);
    }

    public function delete(Request $request, $fstockmtid)
    {
        $customers = Customer::orderBy('fcustomername', 'asc')
            ->get(['fcustomercode', 'fcustomername']);

        $warehouses = DB::table('mswh')
            ->select('fwhid', 'fwhcode', 'fwhname', 'fbranchcode', 'fnonactive')
            ->where('fnonactive', '0') // hanya yang aktif
            ->orderBy('fwhcode')
            ->get();

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
                        'msprd.fprdcode as fitemcode_text', // Ambil KODE string produk
                        'msprd.fsatuankecil',
                        'msprd.fsatuanbesar',
                        'msprd.fsatuanbesar2',
                        'msprd.fqtykecil as fprd_qtykonversi',
                        'msprd.fqtykecil2 as fprd_qtykonversi2'
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

        ['fcabang' => $fcabang, 'fbranchcode' => $fbranchcode] = $this->resolveBranchContext($suratjalan->fbranchcode ?? null);

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
            $maxqty = max(0, (float) ($stat['remain_qty_kecil'] ?? 0));
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
            'fsatuandefault',
            'fsatuankecil',
            'fsatuanbesar',
            'fsatuanbesar2',
            'fqtykecil',
            'fqtykecil2',
            'fminstock'
        )->orderBy('fprdname')->get();

        $productMap = $this->buildProductMap($products);

        return view('suratjalan.edit', [
            'customers' => $customers,
            'selectedSupplierCode' => $selectedSupplierCode,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'warehouses' => $warehouses,
            'products' => $products,
            'productMap' => $productMap,
            'suratjalan' => $suratjalan,
            'displayFstockmtno' => $this->formatDisplayTransactionNumber($suratjalan->fstockmtno ?? null, false),
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

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Surat jalan ' . $this->formatDisplayTransactionNumber($suratjalan->fstockmtno, false) . ' berhasil dihapus.',
                    'redirect_url' => route('suratjalan.index'),
                ]);
            }

            return redirect()->route('suratjalan.index')->with('success', 'Surat jalan ' . $this->formatDisplayTransactionNumber($suratjalan->fstockmtno, false) . ' berhasil dihapus.');
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Surat jalan belum bisa dihapus. Coba lagi: ' . $e->getMessage(),
                ], 500);
            }
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

    private function resolveSuratJalanReferenceDetail(string $docNo, string $productCode, ?string $refNoAcak = null): ?object
    {
        $docNo = trim($docNo);
        $productCode = trim($productCode);
        $refNoAcak = trim((string) ($refNoAcak ?? ''));

        if ($docNo === '' || $productCode === '') {
            return null;
        }

        if ($this->isInvoiceReferenceDoc($docNo)) {
            return DB::table('trandt as d')
                ->join('tranmt as h', 'h.fsono', '=', 'd.fsono')
                ->where('h.ftrcode', 'INV')
                ->where('d.fsono', $docNo)
                ->where('d.fprdcode', $productCode)
                ->when($refNoAcak !== '', function ($query) use ($refNoAcak) {
                    $query->whereRaw("COALESCE(d.fnoacak::text, '') = ?", [$refNoAcak]);
                })
                ->orderBy('d.ftrandtid')
                ->first(['d.ftrandtid as fstockdtid', 'd.fsatuan', 'd.fqty', 'd.fqtykecil']);
        }

        return DB::table('trsodt')
            ->where('fsono', $docNo)
            ->where('fprdcode', $productCode)
            ->when($refNoAcak !== '', function ($query) use ($refNoAcak) {
                $query->whereRaw("COALESCE(fnoacak::text, '') = ?", [$refNoAcak]);
            })
            ->orderBy('ftrsodtid')
            ->first(['ftrsodtid', 'fsatuan', 'fqty', 'fqtykecil']);
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
                        COALESCE(d.fnoacak::text, '') as ref_noacak,
                        MAX(COALESCE(p.fprdname, d.fprdcode)) as product_name,
                        SUM(COALESCE(d.fqtykecil, 0)) as source_qty_kecil,
                        SUM(COALESCE(d.fqtyremain, 0)) as remain_qty_kecil
                    ")
                    ->groupByRaw("TRIM(d.fsono), TRIM(d.fprdcode), COALESCE(d.fnoacak::text, '')")
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
                        SUM(COALESCE(d.fqtykecil, 0)) as source_qty_kecil,
                        SUM(COALESCE(d.fqtyremain, 0)) as remain_qty_kecil
                    ")
                    ->groupByRaw("TRIM(d.fsono), TRIM(d.fprdcode), COALESCE(d.fnoacak::text, '')")
                    ->get()
            );
        }

        $stats = [];

        foreach ($sourceRows as $row) {
            $key = $this->buildSoReferenceUsageKey($row->ref_doc ?? '', $row->product_code ?? '', $row->ref_noacak ?? '');
            $stats[$key] = [
                'ref_doc' => trim((string) ($row->ref_doc ?? '')),
                'product_code' => trim((string) ($row->product_code ?? '')),
                'product_name' => trim((string) ($row->product_name ?? '')),
                'source_qty_kecil' => (float) ($row->source_qty_kecil ?? 0),
                'used_qty_kecil' => 0.0,
                'remain_qty_kecil' => (float) ($row->remain_qty_kecil ?? 0),
                'used_by_transaction' => '',
            ];
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
                return 'Qty Surat Jalan untuk item ' . $product . ' sudah habis atau sudah dipakai.';
            }

            if ((float) $requestedQtyKecil - $availableQtyKecil > 0.000001) {
                $product = trim((string) ($stat['product_name'] ?? $stat['product_code'] ?? $referenceKey));
                return 'Qty Surat Jalan untuk item ' . $product . ' melebihi sisa qty yang tersedia.';
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
                COALESCE(d.fqtyremain, 0) as remain_qty_kecil
            ")
            ->get();

        if ($sourceRows->isEmpty()) {
            return [];
        }

        $result = [];
        foreach ($sourceRows as $row) {
            $result[(int) $row->fstockdtid] = max(0, (float) ($row->remain_qty_kecil ?? 0));
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
