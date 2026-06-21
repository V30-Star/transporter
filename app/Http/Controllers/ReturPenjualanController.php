<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Salesman;
use App\Models\Supplier;
use App\Models\Tr_prd;
use App\Models\Tr_prh;
use App\Models\Tranmt;
use App\Support\ApprovalState;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // sekalian biar aman untuk tanggal
use Illuminate\Validation\ValidationException;

class ReturPenjualanController extends Controller
{
    private const MEMO_DEBIT_ACCOUNT = '11300';
    private const MEMO_CREDIT_ACCOUNT = '41000';

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
                trim((string) $product->fprdcode) => [
                    'fprdid' => $product->fprdid,
                    'name' => $product->fprdname,
                    'default_unit' => $defaultUnit,
                    'units' => $units,
                    'stock' => $product->fminstock ?? 0,
                    'unit_names' => [
                        'satuankecil' => $product->fsatuankecil,
                        'satuanbesar' => $product->fsatuanbesar,
                        'satuanbesar2' => $product->fsatuanbesar2,
                    ],
                    'unit_ratios' => [
                        'satuankecil' => 1,
                        'satuanbesar' => (float) ($product->fqtykecil ?? 1),
                        'satuanbesar2' => (float) ($product->fqtykecil2 ?? 1),
                    ],
                ],
            ];
        })->toArray();
    }

    private function formatDisplayTransactionNumber(?string $number, bool $useSlash = false): string
    {
        $normalized = trim((string) $number);
        if ($normalized === '') {
            return '-';
        }

        $separator = $useSlash ? '/' : '.';

        return (string) preg_replace('/[.\/](\d+)$/', $separator.'$1', $normalized, 1);
    }

    private function ensureNoDuplicateDetailCodes(
        array $codes,
        array $referenceCodes = [],
        array $referenceSo = [],
        array $referenceSrj = [],
        array $referenceNoAcak = []
    ): void
    {
        $seen = [];
        $duplicates = [];

        foreach ($codes as $index => $rawCode) {
            $code = strtoupper(trim((string) $rawCode));
            if ($code === '') {
                continue;
            }

            $refCode = strtoupper(trim((string) ($referenceCodes[$index] ?? '')));
            $refSo = strtoupper(trim((string) ($referenceSo[$index] ?? '')));
            $refSrj = strtoupper(trim((string) ($referenceSrj[$index] ?? '')));
            $refNoAcak = $this->normalizeReferenceRandomNumbers($referenceNoAcak[$index] ?? null);

            $hasReference = $refCode !== '' || $refSo !== '' || $refSrj !== '' || $refNoAcak !== '';
            $key = $hasReference
                ? implode('|', [$code, $refCode, $refSo, $refSrj, $refNoAcak])
                : $code;

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
            $messages["fitemcode.$index"] = "Kode produk {$code} tidak boleh sama dalam satu Retur Penjualan.";
        }

        throw ValidationException::withMessages($messages);
    }

    public function index(Request $request)
    {
        // Ambil izin (permissions)
        $canCreate = in_array('createReturPenjualan', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updateReturPenjualan', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteReturPenjualan', explode(',', session('user_restricted_permissions', '')));
        $showActionsColumn = $canEdit || $canDelete;

        // $status = $request->query('status');
        $year = $request->query('year');
        $month = $request->query('month');

        // Ambil tahun-tahun yang tersedia dari data
        $availableYearsQuery = Tranmt::query()
            ->selectRaw('DISTINCT EXTRACT(YEAR FROM fsodate) as year')
            ->where('ftrcode', 'REJ')
            ->whereNotNull('fsodate');
        $this->applyBranchVisibilityScope($availableYearsQuery, 'tranmt.fbranchcode');
        $availableYears = $availableYearsQuery
            ->orderByRaw('EXTRACT(YEAR FROM fsodate) DESC')
            ->pluck('year');

        // --- Handle Request AJAX dari DataTables ---
        if ($request->ajax()) {

            $query = Tranmt::query()
                ->leftJoin('mscustomer as c', 'c.fcustomercode', '=', 'tranmt.fcustno')
                ->leftJoin('mscabang as b', 'b.fcabangkode', '=', 'tranmt.fbranchcode')
                ->where('tranmt.ftrcode', 'REJ')
                ->select(
                    'tranmt.ftranmtid',
                    'tranmt.fbranchcode',
                    'tranmt.ffrom',
                    'tranmt.fsono',
                    'tranmt.fincludeppn',
                    'tranmt.fsodate',
                    'tranmt.frefno',
                    'tranmt.fcustno',
                    'c.fcustomername',
                    'b.fcabangname',
                    'tranmt.famountso',
                    'tranmt.fket',
                    'tranmt.fuserid',
                    'tranmt.fneedacc'
                );
            $this->applyBranchVisibilityScope($query, 'tranmt.fbranchcode');

            $totalRecords = (clone $query)->count();

            if ($search = $request->input('search.value')) {
                $query->where(function ($q) use ($search) {
                    $q->where('tranmt.fsono', 'ilike', "%{$search}%")
                        ->orWhere('tranmt.frefno', 'ilike', "%{$search}%")
                        ->orWhere('tranmt.fcustno', 'ilike', "%{$search}%")
                        ->orWhere('c.fcustomername', 'ilike', "%{$search}%")
                        ->orWhere('tranmt.fket', 'ilike', "%{$search}%");
                });
            }

            if ($year) {
                $query->whereRaw('EXTRACT(YEAR FROM tranmt.fsodate) = ?', [$year]);
            }

            if ($month) {
                $query->whereRaw('EXTRACT(MONTH FROM tranmt.fsodate) = ?', [$month]);
            }

            $columnSearches = collect($request->input('columns', []))
                ->mapWithKeys(function ($column) {
                    $name = trim((string) ($column['name'] ?? ''));
                    $value = trim((string) data_get($column, 'search.value', ''));

                    return $name !== '' ? [$name => $value] : [];
                });

            $customerSearch = trim((string) ($columnSearches->get('fcustomername', '')));
            if ($customerSearch !== '') {
                $query->where('c.fcustomername', 'ilike', "%{$customerSearch}%");
            }

            $filteredRecords = (clone $query)->count();

            $orderColIdx = $request->input('order.0.column');
            $orderDir = $request->input('order.0.dir', 'desc');

            $orderColumn = null;
            if ($orderColIdx !== null) {
                $colName = $request->input("columns.{$orderColIdx}.name") ?: $request->input("columns.{$orderColIdx}.data");
                if ($colName === 'fbranchcode') {
                    $orderColumn = 'tranmt.fbranchcode';
                } elseif ($colName === 'fsono' || $colName === 'fsono_display') {
                    $orderColumn = 'tranmt.fsono';
                } elseif ($colName === 'fsodate') {
                    $orderColumn = 'tranmt.fsodate';
                } elseif ($colName === 'ffrom') {
                    $orderColumn = 'tranmt.ffrom';
                } elseif ($colName === 'fcustomername') {
                    $orderColumn = 'c.fcustomername';
                } elseif ($colName === 'famountso') {
                    $orderColumn = 'tranmt.famountso';
                } elseif ($colName === 'fket') {
                    $orderColumn = 'tranmt.fket';
                } elseif ($colName === 'fusercreate') {
                    $orderColumn = 'tranmt.fuserid';
                } elseif ($colName === 'fclose') {
                    $orderColumn = 'tranmt.fclose';
                }
            }

            if ($orderColumn) {
                $query->orderBy($orderColumn, $orderDir);
            } else {
                $query->orderBy('tranmt.fsodate', 'desc')->orderBy('tranmt.fsono', 'desc');
            }

            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            $records = $query->skip($start)
                ->take($length)
                ->get();

            $data = $records->map(function ($row) {
                return [
                    'ftranmtid' => $row->ftranmtid,
                    'fbranchcode' => $row->fbranchcode,
                    'fsono' => $row->fsono,
                    'fsono_display' => $this->formatDisplayTransactionNumber($row->fsono ?? null, (string) ($row->fincludeppn ?? '0') === '1'),
                    'fsodate' => $row->fsodate
                        ? ($row->fsodate instanceof \Carbon\Carbon ? $row->fsodate : \Carbon\Carbon::parse($row->fsodate))->format('d-m-Y')
                        : '',
                    'frefno' => $row->frefno ?? '',
                    'ffrom' => $row->ffrom ?? '',
                    'fcustomername' => $row->fcustomername ?? '',
                    'famountso' => (float) ($row->famountso ?? 0),
                    'fket' => $row->fket ?? '',
                    'fusercreate' => $row->fuserid ?? '',
                    'fclose' => $row->fclose ?? '0',
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
        return view('returpenjualan.index', compact(
            'canCreate',
            'canEdit',
            'canDelete',
            'showActionsColumn',
            // 'status',
            'availableYears',
            'year',
            'month'
        ));
    }

    public function pickable(Request $request)
    {
        $customerCode = trim((string) $request->input('fcustno', ''));
        $onlyRemaining = $request->boolean('only_remaining');

        $query = DB::table('tranmt as mt')
            ->leftJoin('mscustomer as cust', 'mt.fcustno', '=', 'cust.fcustomercode')
            ->select(
                'mt.ftranmtid',
                'mt.fsono',
                'mt.frefno',
                'mt.fsodate',
                'mt.fcustno',
                'cust.fcustomername'
            );
        $query->where('mt.fsono', 'like', 'INV.%');
        ApprovalState::applyApprovedFilter($query, 'mt.');

        if ($customerCode !== '') {
            $query->whereRaw('TRIM(COALESCE(mt.fcustno, \'\')) = ?', [$customerCode]);
        }

        if ($onlyRemaining) {
            $query->whereExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('trandt as d')
                    ->whereColumn('d.fsono', 'mt.fsono')
                    ->whereRaw('COALESCE(d.fqtyremain, 0) > 0');
            });
        }

        $recordsTotal = DB::table('tranmt as mt')
            ->where('mt.fsono', 'like', 'INV.%')
            ->whereRaw(ApprovalState::approvedSql('mt.'))
            ->when($customerCode !== '', function ($q) use ($customerCode) {
                $q->whereRaw('TRIM(COALESCE(mt.fcustno, \'\')) = ?', [$customerCode]);
            })
            ->when($onlyRemaining, function ($query) {
                $query->whereExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('trandt as d')
                        ->whereColumn('d.fsono', 'mt.fsono')
                        ->whereRaw('COALESCE(d.fqtyremain, 0) > 0');
                });
            })
            ->count();

        if ($request->filled('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('mt.fsono', 'ilike', "%{$search}%")
                    ->orWhere('mt.frefno', 'ilike', "%{$search}%")
                    ->orWhere('cust.fcustomername', 'ilike', "%{$search}%")
                    ->orWhere('mt.fcustno', 'ilike', "%{$search}%");
            });
        }

        $recordsFiltered = $query->count();

        $orderColumn = $request->input('order_column', 'fsodate');
        $orderDir = $request->input('order_dir', 'desc');

        $allowedColumns = ['fsono', 'frefno', 'fsodate', 'fcustomername'];
        if (in_array($orderColumn, $allowedColumns)) {
            if ($orderColumn === 'fcustomername') {
                $query->orderBy('cust.fcustomername', $orderDir);
            } else {
                $query->orderBy('mt.' . $orderColumn, $orderDir);
            }
        } else {
            $query->orderBy('mt.fsodate', 'desc');
        }

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

    public function productHistory(Request $request)
    {
        $customerCode = trim((string) $request->input('fcustno', ''));
        $productCode = trim((string) $request->input('fprdcode', ''));

        if ($customerCode === '' || $productCode === '') {
            return response()->json([
                'message' => 'Customer dan produk wajib dipilih terlebih dahulu.',
                'data' => [],
            ], 422);
        }

        $rows = DB::table('trandt as d')
            ->join('tranmt as h', 'h.fsono', '=', 'd.fsono')
            ->leftJoin('mscustomer as c', 'c.fcustomercode', '=', 'h.fcustno')
            ->where('h.fcustno', $customerCode)
            ->where('d.fprdcode', $productCode)
            ->where('h.fsono', 'like', 'INV.%')
            ->orderByDesc('h.fsodate')
            ->orderByDesc('h.fsono')
            ->get([
                'h.fsono',
                'h.fsodate',
                'c.fcustomername',
                'd.fqty',
                'd.fsatuan',
                'd.fprice',
                'd.famount',
                'd.fdesc',
            ]);

        return response()->json([
            'data' => $rows->map(function ($row) {
                return [
                    'fsono' => (string) ($row->fsono ?? ''),
                    'fsodate' => ! empty($row->fsodate)
                        ? Carbon::parse($row->fsodate)->format('d/m/Y')
                        : '-',
                    'fcustomername' => (string) ($row->fcustomername ?? ''),
                    'fqty' => (float) ($row->fqty ?? 0),
                    'fsatuan' => (string) ($row->fsatuan ?? ''),
                    'fprice' => (float) ($row->fprice ?? 0),
                    'famount' => (float) ($row->famount ?? 0),
                    'fdesc' => (string) ($row->fdesc ?? ''),
                ];
            })->values(),
        ]);
    }

    public function items($id)
    {
        $header = DB::table('tranmt')
            ->leftJoin('mscustomer', 'mscustomer.fcustomercode', '=', 'tranmt.fcustno')
            ->where('tranmt.ftranmtid', $id)
            ->where('tranmt.fsono', 'like', 'INV.%')
            ->select('tranmt.*', 'mscustomer.fcustomername')
            ->firstOrFail();

        abort_if(! ApprovalState::isApprovedRecord($header), 404);

        $items = DB::table('trandt')
            ->where('trandt.fsono', $header->fsono)
            ->leftJoin('msprd as m', 'm.fprdcode', '=', 'trandt.fprdcode')
            ->select([
                'trandt.ftrandtid as frefdtno',
                'trandt.fprdcode as fitemcode',
                'm.fprdname as fitemname',
                'trandt.fqty',
                'trandt.fqtyremain',
                'trandt.fsatuan as fsatuan',
                'trandt.fprice as fprice',
                DB::raw("COALESCE(NULLIF(TRIM(trandt.fdisc), ''), '0') as fdisc"),
                'trandt.fdesc',
                'trandt.frefso',
                'trandt.frefsrj',
                DB::raw("COALESCE(NULLIF(TRIM(trandt.fnoacak::text), ''), '') as frefnoacak"),
                'm.fsatuankecil',
                'm.fsatuanbesar',
                'm.fsatuanbesar2',
            ])
            ->orderBy('trandt.fnou')
            ->get();

        return response()->json([
            'header' => [
                'ftranmtid' => $header->ftranmtid,
                'fsono' => $header->fsono,
                'frefno' => trim((string) ($header->frefno ?? '')),
                'fdisplayref' => trim((string) ($header->frefno ?? '')) !== ''
                    ? trim((string) ($header->frefno ?? ''))
                    : trim((string) ($header->fsono ?? '')),
                'fcustno' => trim((string) ($header->fcustno ?? '')),
                'fsodate' => optional($header->fsodate)->format('Y-m-d H:i:s'),
            ],
            'items' => $items->map(function ($item) use ($header) {
                $units = array_values(array_filter(array_map(
                    fn ($value) => trim((string) $value),
                    [
                        $item->fsatuankecil ?? '',
                        $item->fsatuanbesar ?? '',
                        $item->fsatuanbesar2 ?? '',
                    ]
                )));

                return [
                    'frefdtno' => $item->frefdtno,
                    'fitemcode' => trim((string) ($item->fitemcode ?? '')),
                    'fitemname' => trim((string) ($item->fitemname ?? '')),
                    'fqty' => (float) ($item->fqty ?? 0),
                    'fqtyremain' => max(0, (float) ($item->fqtyremain ?? 0)),
                    'maxqty' => max(0, (float) ($item->fqtyremain ?? 0)),
                    'fsatuan' => trim((string) ($item->fsatuan ?? '')),
                    'fdisplayunit' => trim((string) ($item->fsatuan ?? '')),
                    'fprice' => (float) ($item->fprice ?? 0),
                    'fdisc' => $this->normalizeDiscountInput($item->fdisc ?? 0),
                    'fdesc' => (string) ($item->fdesc ?? ''),
                    'fnouref' => trim((string) ($header->fsono ?? '')),
                    'frefpr' => trim((string) ($header->frefno ?? '')) !== ''
                        ? trim((string) ($header->frefno ?? ''))
                        : trim((string) ($header->fsono ?? '')),
                    'frefso' => trim((string) ($item->frefso ?? '')),
                    'frefsrj' => trim((string) ($item->frefsrj ?? '')),
                    'frefnoacak' => trim((string) ($item->frefnoacak ?? '')),
                    'units' => $units,
                ];
            })->values(),
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

    private function normalizeReferenceRandomNumbers($value): ?string
    {
        $parts = preg_split('/\s*,\s*/', trim((string) ($value ?? ''))) ?: [];

        foreach ($parts as $part) {
            $candidate = trim((string) $part);
            if (preg_match('/^\d{3}$/', $candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function buildReferenceRandomNumberColumns(?string $sourceCode, $value): array
    {
        $normalized = $this->normalizeReferenceRandomNumbers($value);
        $sourceCode = strtoupper(trim((string) ($sourceCode ?? '')));

        if (in_array($sourceCode, ['S', 'SO', 'INV'], true)) {
            return [
                'frefnoacak' => $normalized,
                'frefnosoacak' => $normalized,
            ];
        }

        if (in_array($sourceCode, ['R', 'SRJ'], true)) {
            return [
                'frefnoacak' => $normalized,
                'frefnosoacak' => null,
            ];
        }

        return [
            'frefnoacak' => $normalized,
            'frefnosoacak' => null,
        ];
    }

    private function sanitizeReturReferences(array &$frefso, array $frefsrj): void
    {
        foreach ($frefsrj as $index => $srjDocNo) {
            if (trim((string) $srjDocNo) !== '') {
                $frefso[$index] = '';
            }
        }
    }

    private function resolveReturReferenceSourceDetail(string $sourceCode, string $docNo, string $productCode, $refNoAcak = null): ?object
    {
        $sourceCode = strtoupper(trim($sourceCode));
        $docNo = trim($docNo);
        $productCode = trim($productCode);
        $normalizedRefNoAcak = $this->normalizeReferenceRandomNumbers($refNoAcak);

        if ($docNo === '' || $productCode === '') {
            return null;
        }

        if (in_array($sourceCode, ['R', 'SRJ'], true)) {
            return DB::table('trstockdt')
                ->where('fstockmtno', $docNo)
                ->where('fprdcode', $productCode)
                ->when($normalizedRefNoAcak !== null, fn ($query) => $query->where('fnoacak', $normalizedRefNoAcak))
                ->orderBy('fstockdtid')
                ->first(['fsatuan', 'fqty', 'fqtykecil', 'fnoacak']);
        }

        if (in_array($sourceCode, ['S', 'SO', 'INV'], true)) {
            return DB::table('trandt')
                ->where('fsono', $docNo)
                ->where('fprdcode', $productCode)
                ->when($normalizedRefNoAcak !== null, fn ($query) => $query->where('fnoacak', $normalizedRefNoAcak))
                ->orderBy('ftrandtid')
                ->first(['fsatuan', 'fqty', 'fqtykecil', 'fnoacak']);
        }

        return null;
    }

    private function generateInvoiceCode(?Carbon $onDate = null, ?string $branchCode = null): string
    {
        $date = $onDate ?: now();
        $branchCode = trim((string) ($branchCode ?: 'NA')) ?: 'NA';
        $prefix = sprintf('REJ.%s.%s.%s.', $branchCode, $date->format('Y'), $date->format('m'));

        $last = DB::table('tranmt')
            ->where('fsono', 'like', $prefix . '%')
            ->selectRaw("MAX(CAST(split_part(fsono, '.', 5) AS int)) AS lastno")
            ->value('lastno');

        $next = (int) $last + 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function print(string $fsono)
    {
        // Header: find by SO code (string)
        $hdr = DB::table('tranmt')
            ->leftJoin('mscustomer as c', 'c.fcustomercode', '=', 'tranmt.fcustno')
            ->leftJoin('mssalesman as s', 's.fsalesmancode', '=', 'tranmt.fsalesman')
            ->where('tranmt.fsono', $fsono)
            ->first([
                'tranmt.*',
                'c.fcustomername as customer_name',
                's.fsalesmanname as salesman_name',
            ]);

        if (! $hdr) {
            return redirect()->back()->with('error', 'Retur penjualan tidak ada.');
        }

        DB::table('tranmt')->where('fsono', $hdr->fsono)->update(['fprint' => 1]);

        // Use header ID (integer) for detail FK
        $ftranmtid = (int) $hdr->ftranmtid;

        // Detail: join dengan product
        $dt = DB::table('trandt')
            ->leftJoin('msprd as p', 'p.fprdcode', '=', 'trandt.fprdcode')
            ->where('trandt.fsono', $fsono) // Gunakan variabel $fsono dari parameter fungsi
            ->orderBy('trandt.fnou', 'asc') // Urutkan berdasarkan nomor urut baris
            ->get([
                'trandt.*',
                'p.fprdcode as product_code',
                'p.fprdname as product_name',
                'p.fminstock as stock',
            ]);

        // Format date helper
        $fmt = fn($d) => $d
            ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d F Y')
            : '-';

        return view('returpenjualan.print', [
            'hdr' => $hdr,
            'dt' => $dt,
            'displayFsono' => $this->formatDisplayTransactionNumber($hdr->fsono ?? null, (string) ($hdr->fincludeppn ?? '0') === '1'),
            'fmt' => $fmt,
            'company_name' => config('app.company_name', 'PT. DEMO VERSION'),
            'company_city' => config('app.company_city', 'Tangerang'),
        ]);
    }

    public function create(Request $request)
    {
        $customers = Customer::orderBy('fcustomername', 'asc')
            ->get(['fcustomerid', 'fcustomername', 'fcustomercode']);

        $salesmans = Salesman::orderBy('fsalesmanname', 'asc')
            ->get(['fsalesmanid', 'fsalesmanname', 'fsalesmancode']);

        $warehouses = DB::table('mswh')
            ->select('fwhid', 'fwhcode', 'fwhname', 'fbranchcode', 'fnonactive')
            ->where('fnonactive', '0')
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

        $newtr_prh_code = $this->generateInvoiceCode(now(), $fbranchcode);

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

        return view('returpenjualan.create', [
            'newtr_prh_code' => $newtr_prh_code,
            'customers' => $customers,
            'salesmans' => $salesmans,
            'warehouses' => $warehouses,
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
        try {
            $request->validate([
                'fsodate' => ['required', 'date'],
                'fcustno' => ['required', 'string', 'max:10'],
                'ffrom' => ['nullable', 'string', 'max:30'],
                'fitemcode' => ['required', 'array', 'min:1'],
                'fitemcode.*' => ['nullable', 'string', 'max:30'],
                'fqty' => ['required', 'array'],
                'fqty.*' => ['numeric', 'min:0'],
                'fprice' => ['required', 'array'],
                'fprice.*' => ['numeric', 'min:0'],
                'frefcode' => ['nullable', 'array'],
                'frefcode.*' => ['nullable', 'string'],
                'frefcode_global' => ['nullable', 'string', 'in:SO,SRJ,UM,INV,REJ'],
                'frefso' => ['nullable'],
                'frefsrj' => ['nullable'],
                'fnoacak' => ['nullable', 'array'],
                'fnoacak.*' => ['nullable', 'regex:/^[1-9]{3}$/'],
                'frefnoacak' => ['nullable', 'array'],
                'frefnoacak.*' => ['nullable', 'regex:/^\d{3}$/'],
            ]);
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        // 2. INISIALISASI
        $fsodate = Carbon::parse($request->fsodate);
        $this->ensureCreateDateWithinEditPeriod($fsodate);
        $fincludeppn = $request->boolean('fincludeppn') ? '1' : '0';
        $fapplyppn = $request->input('fapplyppn', '0');
        $ppnPersen = (float) $request->input('fppnpersen', 11);
        $userid = mb_substr(auth('sysuser')->user()->fname ?? 'admin', 0, 10);
        $now = now();
        $fcurrency = $request->input('fcurrency', 'IDR');
        $frate = (float) $request->input('frate', 1);
        $typeSales = (int) $request->input('ftypesales', 0);

        // 3. ARRAY INPUT
        $itemCodes = $request->input('fitemcode', []);
        $itemDescs = $request->input('fitemname', []);
        $satuans = $request->input('fsatuan', []);
        $qtys = $request->input('fqty', []);
        $prices = $request->input('fprice', []);
        $discs = $request->input('fdisc', []);

        // FREFCODE & REFERENCES
        $frefcodes = $request->input('frefcode', []);
        $frefso = $request->input('frefso', []);
        $frefsrj = $request->input('frefsrj', []);
        $this->sanitizeReturReferences($frefso, $frefsrj);
        $fnoacaks = $request->input('fnoacak', []);
        $frefnoacaks = $request->input('frefnoacak', []);

        $this->ensureNoDuplicateDetailCodes(
            $itemCodes,
            $frefcodes,
            $frefso,
            $frefsrj,
            $frefnoacaks
        );

        if ($typeSales === 1) {
            $frefcode = 'UM';
        } else {
            $frefcode = $request->input('frefcode_global');
        }

        // CEK UM
        $hasUM = in_array('UM', $itemCodes);

        if ($hasUM && $typeSales === 0) {
            return back()->withInput()->with('error', 'Produk UM hanya untuk tipe Uang Muka.');
        }
        if (! $hasUM && $typeSales === 1) {
            return back()->withInput()->with('error', 'Transaksi Uang Muka wajib menggunakan produk UM.');
        }

        // QUERY PRODUK
        $filteredCodes = array_values(array_filter($itemCodes));

        $products = DB::table('msprd')
            ->whereIn('fprdcode', $filteredCodes)
            ->get([
                'fprdid',
                'fprdcode',
                'fprdname',
                'fnonactive',
                'fsatuankecil',
                'fsatuanbesar',
                'fsatuanbesar2',
                'fqtykecil',
                'fqtykecil2',
            ])
            ->keyBy('fprdcode');

        // LOOP ITEM
        $detailRows = [];
        $totalGross = 0;
        $totalDisc = 0;
        $totalSalesNet = 0.0;
        $nouCounter = 1;
        $usedNoAcaks = [];

        foreach ($itemCodes as $i => $code) {
            $qty = (float) ($qtys[$i] ?? 0);
            $price = (float) ($prices[$i] ?? 0);

            if (empty($code) || $qty <= 0) {
                continue;
            }

            $product = $products->get($code);

            if ($product && $product->fnonactive == '1') {
                return back()->withInput()->with('error', "Produk [{$code}] {$product->fprdname} sudah discontinue.");
            }

            // --- OVERRIDE unit dari referensi (SRJ / Invoice) ---
            $refSrjDoc = trim((string) ($frefsrj[$i] ?? ''));
            $refSoDoc = trim((string) ($frefso[$i] ?? ''));
            if ($refSrjDoc !== '') {
                $price = 0.0;
                $discs[$i] = 0;
            }
            $refNoAcak = $this->normalizeReferenceRandomNumbers($frefnoacaks[$i] ?? null);
            $referenceRatio = null;
            $referenceDetail = null;
            if ($refSrjDoc !== '') {
                $referenceDetail = $this->resolveReturReferenceSourceDetail('SRJ', $refSrjDoc, $code, $frefnoacaks[$i] ?? null);
            } elseif ($refSoDoc !== '') {
                $referenceDetail = $this->resolveReturReferenceSourceDetail('INV', $refSoDoc, $code, $frefnoacaks[$i] ?? null);
            }
            if ($referenceDetail && ! empty($referenceDetail->fnoacak)) {
                $refNoAcak = trim((string) $referenceDetail->fnoacak);
            }
            if ($referenceDetail && trim((string) ($referenceDetail->fsatuan ?? '')) !== '') {
                $satuans[$i] = trim((string) $referenceDetail->fsatuan);
            }
            if ($referenceDetail) {
                $referenceQty = (float) ($referenceDetail->fqty ?? 0);
                $referenceQtyKecil = (float) ($referenceDetail->fqtykecil ?? 0);
                if ($referenceQty > 0 && $referenceQtyKecil > 0) {
                    $referenceRatio = $referenceQtyKecil / $referenceQty;
                }
            }
            // --- END override ---

            $qtyKecil = $qty;
            $selectedUnit = trim((string) ($satuans[$i] ?? ''));
            if ($referenceRatio !== null && $referenceRatio > 0) {
                $qtyKecil = $qty * $referenceRatio;
            } elseif (
                $product
                && $selectedUnit !== ''
                && $selectedUnit === trim((string) ($product->fsatuanbesar ?? ''))
                && (float) ($product->fqtykecil ?? 0) > 0
            ) {
                $qtyKecil = $qty * (float) $product->fqtykecil;
            } elseif (
                $product
                && $selectedUnit !== ''
                && $selectedUnit === trim((string) ($product->fsatuanbesar2 ?? ''))
                && (float) ($product->fqtykecil2 ?? 0) > 0
            ) {
                $qtyKecil = $qty * (float) $product->fqtykecil2;
            }

            $discRaw = $this->normalizeDiscountInput($discs[$i] ?? 0);
            $discPersen = $this->parseDiscount($discRaw);
            $subtotal = $qty * $price;
            $discAmount = $subtotal * ($discPersen / 100);
            $netPrice = $price - ($price * ($discPersen / 100));
            $amountRow = $subtotal - $discAmount;

            $totalGross += $subtotal;
            $totalDisc += $discAmount;

            if ($fincludeppn == 1 && $fapplyppn == 1) {
                $fsalesnet = (100 / (100 + $ppnPersen)) * $netPrice;
            } else {
                $fsalesnet = $netPrice;
            }
            $totalSalesNet += $qty * $fsalesnet;

            $detailRows[] = array_merge([
                'fnou' => $nouCounter,
                'fprdcode' => mb_substr($code, 0, 30),
                'fdesc' => $itemDescs[$i] ?? '',
                'fqty' => $qty,
                'fqtykecil' => $qtyKecil,
                'fqtyremain' => $qty,
                'fprice' => $price,
                'fprice_rp' => $price * $frate,
                'fdisc' => $discRaw,
                'fpricenet' => $netPrice,
                'fpricenet_rp' => $netPrice * $frate,
                'fsalesnet' => $fsalesnet,
                'famount' => $amountRow,
                'famount_rp' => $amountRow * $frate,
                'fsatuan' => mb_substr($satuans[$i] ?? '', 0, 5),
                'fuserid' => $userid,
                'fdatetime' => $now,
                'frefcode' => 'REJ',
                'frefso' => $refSoDoc,
                'frefsrj' => $refSrjDoc,
                'fnoacak' => $this->normalizeRandomNumber($fnoacaks[$i] ?? null, $usedNoAcaks),
            ], $this->buildReferenceRandomNumberColumns($refSrjDoc !== '' ? 'SRJ' : ($frefcode ?? ''), $refNoAcak));

            $stockDetailRows[] = [
                'fprdcode' => mb_substr($code, 0, 30),
                'fdesc' => $itemDescs[$i] ?? '',
                'fqty' => $qty,
                'fqtykecil' => $qtyKecil,
                'fqtyremain' => $qtyKecil,
                'fprice' => $price,
                'fprice_rp' => $price * $frate,
                'ftotprice' => $amountRow,
                'fusercreate' => $userid,
                'fdatetime' => $now,
                'fsatuan' => mb_substr($satuans[$i] ?? '', 0, 5),
                'fcode' => '0',
            ];
        }

        // GUARD: detailRows kosong
        if (empty($detailRows)) {
            return back()->withInput()->with('error', 'Tidak ada item valid. Periksa kode produk dan qty.');
        }

        [$soUsageByReference, $srjUsageByReference] = $this->buildReturReferenceUsageMaps($detailRows);

        if ($validationMessage = $this->validateReferenceUsage($soUsageByReference, $srjUsageByReference)) {
            return back()->withInput()->with('error', $validationMessage);
        }

        // KALKULASI TOTAL
        $fapplyppn = $request->input('fapplyppn', '0'); // 0: Exclude, 1: Include
        $amountNet = $totalGross - $totalDisc;
        $ppnPersen = (float) $request->input('fppnpersen', 11);

        if ($fincludeppn === '1') {
            if ($fapplyppn === '1') {
                // INCLUDE: amountNet is current base, we extract
                $ppnAmount = $amountNet * ($ppnPersen / (100 + $ppnPersen));
                $amountNet = $amountNet - $ppnAmount;
                $grandTotal = $amountNet + $ppnAmount;
            } else {
                // EXCLUDE: amountNet is base, we add
                $ppnAmount = $amountNet * ($ppnPersen / 100);
                $grandTotal = $amountNet + $ppnAmount;
            }
        } else {
            $ppnAmount = 0;
            $grandTotal = $amountNet;
        }

        // DATABASE TRANSACTION
        try {
            $savedFsono = null;
            DB::transaction(function () use (
                $request,
                $fsodate,
                $fincludeppn,
                $userid,
                $now,
                $detailRows,
                $stockDetailRows,
                $totalGross,
                $totalDisc,
                $amountNet,
                $ppnAmount,
                $grandTotal,
                $fcurrency,
                $frate,
                $ppnPersen,
                $typeSales,
                $fapplyppn,
                &$savedFsono,
                $totalSalesNet
            ) {

                $fsono = $request->input('fsono');

                if (empty($fsono)) {
                    $branchCode = trim((string) ($request->input('fbranchcode') ?: 'NA')) ?: 'NA';
                    $prefix = sprintf('REJ.%s.%s.%s.', $branchCode, $fsodate->format('Y'), $fsodate->format('m'));

                    $lastRecord = DB::table('tranmt')
                        ->where('fsono', 'like', $prefix . '%')
                        ->orderByRaw("CAST(split_part(fsono, '.', 5) AS int) DESC")
                        ->lockForUpdate()
                        ->first();

                    $nextNumber = $lastRecord
                        ? ((int) substr(trim($lastRecord->fsono), -4)) + 1
                        : 1;

                    $fsono = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
                }
                $savedFsono = $fsono;

                $headerData = [
                    'fsono' => $fsono,
                    'fsodate' => $fsodate,
                    'fcustno' => mb_substr($request->fcustno, 0, 10),
                    'fsalesman' => mb_substr((string) ($request->fsalesman ?? ''), 0, 30),
                    'ffrom' => mb_substr((string) ($request->ffrom ?? ''), 0, 10),
                    'fcurrency' => $fcurrency,
                    'frate' => $frate,
                    'fdiscount' => $totalDisc,
                    'fdiscount_rp' => $totalDisc * $frate,
                    'famountgross' => $totalGross,
                    'famountgross_rp' => $totalGross * $frate,
                    'famountsonet' => $amountNet,
                    'famountsonet_rp' => $amountNet * $frate,
                    'famountpajak' => $ppnAmount,
                    'famountpajak_rp' => $ppnAmount * $frate,
                    'famountso' => $grandTotal,
                    'famountso_rp' => $grandTotal * $frate,
                    'ftotalsalesnet' => $totalSalesNet,
                    'famountremain' => $grandTotal,
                    'famountremain_rp' => $grandTotal * $frate,
                    'fket' => $request->fket ?? '',
                    'fuserid' => $userid,
                    'fdatetime' => $now,
                    'fincludeppn' => $fincludeppn,
                    'fapplyppn' => $fapplyppn,
                    'fppnpersen' => $ppnPersen,
                    'ftypesales' => $typeSales,
                    'ftrcode' => 'REJ',
                    'fprdout' => '0',
                    'ftaxno' => $request->ftaxno ?? '0',
                    'fprint' => 0,
                    'fbranchcode' => $request->fbranchcode,
                ];

                $ftranmtid = DB::table('tranmt')->insertGetId($headerData, 'ftranmtid');

                foreach ($detailRows as &$row) {
                    $row['fsono'] = $fsono;
                }
                unset($row);

                DB::table('trandt')->insert($detailRows);

                // ==== STOCK RECORDS ====
                $fstockmtno = str_replace('REJ.', 'REB.', $fsono);
                $masterStockData = [
                    'fstockmtno' => $fstockmtno,
                    'fstockmtcode' => 'REB',
                    'fstockmtdate' => $fsodate,
                    'fprdout' => '0',
                    'fsupplier' => mb_substr($request->fcustno, 0, 10),
                    'ffrom' => mb_substr((string) ($request->ffrom ?? ''), 0, 10),
                    'famount' => $amountNet,
                    'famount_rp' => $amountNet * $frate,
                    'famountpajak' => $ppnAmount,
                    'famountpajak_rp' => $ppnAmount * $frate,
                    'famountmt' => $grandTotal,
                    'famountmt_rp' => $grandTotal * $frate,
                    'famountremain' => $grandTotal,
                    'famountremain_rp' => $grandTotal * $frate,
                    'fket' => $request->fket ?? '',
                    'fusercreate' => $userid,
                    'fdatetime' => $now,
                    'fbranchcode' => $request->fbranchcode ?? 'BG', // Use request branch
                ];

                $newStockId = DB::table('trstockmt')->insertGetId($masterStockData, 'fstockmtid');

                foreach ($stockDetailRows as &$srow) {
                    $srow['fstockmtno'] = $fstockmtno;
                    $srow['fstockmtcode'] = 'REB';
                }
                unset($srow);

                DB::table('trstockdt')->insert($stockDetailRows);

                $this->syncReturPenjualanJournalEntries(
                    (string) $fsono,
                    $fsodate,
                    (string) ($request->input('fbranchcode') ?: 'BG'),
                    (string) $request->fcustno,
                    (float) $amountNet,
                    (float) $ppnAmount,
                    (float) $grandTotal,
                    (string) $userid
                );

                // Validasi sisa SO/SRJ berdasarkan fqtykecil dinonaktifkan.
            });

            return redirect()->route('returpenjualan.index')->with('success', 'Retur penjualan '.$this->formatDisplayTransactionNumber($savedFsono, $fincludeppn === '1').' berhasil disimpan.');
        } catch (\Exception $e) {

            return back()->withInput()->with('error', 'Retur penjualan belum bisa disimpan. Cek data transaksi.');
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

    private function getSoRemainByIds(array $soDetailIds): array
    {
        $ids = collect($soDetailIds)->map(fn($id) => (int) $id)->filter(fn($id) => $id > 0)->unique()->values()->all();
        if (empty($ids)) {
            return [];
        }

        return DB::table('trsodt as d')
            ->whereIn('d.ftrsodtid', $ids)
            ->selectRaw('d.ftrsodtid, GREATEST(COALESCE(d.fqtykecil, 0), 0) AS remain_kecil')
            ->pluck('remain_kecil', 'd.ftrsodtid')
            ->map(fn($value) => (float) $value)
            ->all();
    }

    private function getReferenceSummaryByTranNo(string $fsono): array
    {
        $rows = DB::table('trandt as d')
            ->leftJoin('msprd as p', 'p.fprdcode', '=', 'd.fprdcode')
            ->where('d.fsono', $fsono)
            ->get([
                'd.ftrandtid',
                'd.frefcode',
                'd.frefso',
                'd.frefsrj',
                'd.frefnoacak',
                'd.fprdcode',
                'd.fsatuan',
                'p.fqtykecil',
                'p.fqtykecil2',
                'p.fsatuanbesar',
                'p.fsatuanbesar2',
            ]);

        $soStats = $this->getReturReferenceStats(
            'SO',
            $rows->pluck('frefso')->filter()->map(fn($value) => trim((string) $value))->unique()->values()->all()
        );
        $srjStats = $this->getReturReferenceStats(
            'SRJ',
            $rows->pluck('frefsrj')->filter()->map(fn($value) => trim((string) $value))->unique()->values()->all()
        );

        return $rows->keyBy('ftrandtid')->map(function ($row) use ($soStats, $srjStats) {
            $refCode = strtoupper(trim((string) ($row->frefcode ?? '')));
            $isSrj = $refCode === 'SRJ' || trim((string) ($row->frefsrj ?? '')) !== '';
            $docNo = trim((string) ($isSrj ? ($row->frefsrj ?? '') : ($row->frefso ?? '')));
            $refNoAcak = $this->normalizeReferenceRandomNumbers($row->frefnoacak ?? null) ?? '';
            $key = $this->buildReferenceUsageKey($docNo, (string) ($row->fprdcode ?? ''), $refNoAcak);
            $stat = $isSrj ? ($srjStats[$key] ?? null) : ($soStats[$key] ?? null);
            $usedQty = (float) ($stat['used_qty_kecil'] ?? 0);
            $remainQty = (float) ($stat['remain_qty_kecil'] ?? 0);

            return [
                'fqtyterinvoice' => $this->convertQtyKecilToUnit($usedQty, (string) ($row->fsatuan ?? ''), $row),
                'fqtysisa_ref' => $this->convertQtyKecilToUnit($remainQty, (string) ($row->fsatuan ?? ''), $row),
            ];
        })->all();
    }

    private function validateReferenceUsage(array $soUsageByReference, array $srjUsageByReference, ?string $exceptFsono = null): ?string
    {
        if (! empty($soUsageByReference)) {
            $soStats = $this->getReturReferenceStats('SO', $this->extractReferenceDocsFromUsageKeys(array_keys($soUsageByReference)), $exceptFsono);

            foreach ($soUsageByReference as $referenceKey => $qtyKecil) {
                $stat = $this->resolveReferenceStatWithFallback($soStats, (string) $referenceKey);
                $available = max(0, (float) ($stat['remain_qty_kecil'] ?? 0));
                if ((float) $qtyKecil - $available > 0.000001) {
                    $label = trim((string) ($stat['product_name'] ?? $stat['product_code'] ?? $referenceKey));
                    $refno = trim((string) ($stat['ref_doc'] ?? ''));
                    $unit = trim((string) ($stat['source_unit'] ?? 'Qty'));
                    return "Warning\nProduk {$label} @" . number_format((float) $qtyKecil, 2, ',', '.') . " {$unit}\nMelebihi Qty Faktur Penjualan" . ($refno !== '' ? " ({$refno})" : '') . " !!!";
                }
            }
        }

        if (! empty($srjUsageByReference)) {
            $srjStats = $this->getReturReferenceStats('SRJ', $this->extractReferenceDocsFromUsageKeys(array_keys($srjUsageByReference)), $exceptFsono);

            foreach ($srjUsageByReference as $referenceKey => $qtyKecil) {
                $stat = $this->resolveReferenceStatWithFallback($srjStats, (string) $referenceKey);
                $available = max(0, (float) ($stat['remain_qty_kecil'] ?? 0));
                if ((float) $qtyKecil - $available > 0.000001) {
                    $label = trim((string) ($stat['product_name'] ?? $stat['product_code'] ?? $referenceKey));
                    $refno = trim((string) ($stat['ref_doc'] ?? ''));
                    $unit = trim((string) ($stat['source_unit'] ?? 'Qty'));
                    return "Warning\nProduk {$label} @" . number_format((float) $qtyKecil, 2, ',', '.') . " {$unit}\nMelebihi Qty Surat Jalan" . ($refno !== '' ? " ({$refno})" : '') . " !!!";
                }
            }
        }

        return null;
    }

    private function validateUniqueReferenceTransaction(array $soUsageByReference, array $srjUsageByReference, ?string $exceptFsono = null): ?string
    {
        if (! empty($soUsageByReference)) {
            $soStats = $this->getReturReferenceStats('SO', $this->extractReferenceDocsFromUsageKeys(array_keys($soUsageByReference)), $exceptFsono);
            foreach ($soUsageByReference as $referenceKey => $qtyKecil) {
                if ((float) ($soStats[$referenceKey]['used_qty_kecil'] ?? 0) > 0) {
                    $refNo = trim((string) ($soStats[$referenceKey]['ref_doc'] ?? ''));
                    $transactionNo = trim((string) ($soStats[$referenceKey]['used_by_transaction'] ?? ''));
                    return 'No. referensi ' . strtoupper((string) $refNo) . ' sudah ada di transaksi ' . strtoupper((string) $transactionNo) . '.';
                }
            }
        }

        if (! empty($srjUsageByReference)) {
            $srjStats = $this->getReturReferenceStats('SRJ', $this->extractReferenceDocsFromUsageKeys(array_keys($srjUsageByReference)), $exceptFsono);
            foreach ($srjUsageByReference as $referenceKey => $qtyKecil) {
                if ((float) ($srjStats[$referenceKey]['used_qty_kecil'] ?? 0) > 0) {
                    $refNo = trim((string) ($srjStats[$referenceKey]['ref_doc'] ?? ''));
                    $transactionNo = trim((string) ($srjStats[$referenceKey]['used_by_transaction'] ?? ''));
                    return 'No. referensi ' . strtoupper((string) $refNo) . ' sudah ada di transaksi ' . strtoupper((string) $transactionNo) . '.';
                }
            }
        }

        return null;
    }

    private function buildReferenceUsageKey(?string $docNo, ?string $productCode, ?string $refNoAcak = null): string
    {
        return implode('|', [
            trim((string) ($docNo ?? '')),
            trim((string) ($productCode ?? '')),
            trim((string) ($refNoAcak ?? '')),
        ]);
    }

    private function extractReferenceDocsFromUsageKeys(array $keys): array
    {
        return collect($keys)
            ->map(function ($key) {
                return explode('|', (string) $key)[0] ?? '';
            })
            ->filter(fn($value) => trim((string) $value) !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function resolveReferenceStatWithFallback(array $stats, string $referenceKey): ?array
    {
        if (isset($stats[$referenceKey])) {
            return $stats[$referenceKey];
        }

        [$docNo, $productCode, $refNoAcak] = array_pad(explode('|', $referenceKey), 3, '');
        if (trim($refNoAcak) !== '') {
            return null;
        }

        $matching = collect($stats)->filter(function ($value, $key) use ($docNo, $productCode) {
            [$keyDocNo, $keyProductCode] = array_pad(explode('|', (string) $key), 2, '');

            return trim($keyDocNo) === trim($docNo) && trim($keyProductCode) === trim($productCode);
        })->values();

        if ($matching->isEmpty()) {
            return null;
        }

        $first = (array) $matching->first();

        return [
            'ref_doc' => trim((string) ($first['ref_doc'] ?? $docNo)),
            'product_code' => trim((string) ($first['product_code'] ?? $productCode)),
            'product_name' => trim((string) ($first['product_name'] ?? $productCode)),
            'source_unit' => trim((string) ($first['source_unit'] ?? 'Qty')),
            'source_qty_kecil' => (float) $matching->sum(fn($row) => (float) ($row['source_qty_kecil'] ?? 0)),
            'used_qty_kecil' => (float) $matching->sum(fn($row) => (float) ($row['used_qty_kecil'] ?? 0)),
            'remain_qty_kecil' => max(0, (float) $matching->sum(fn($row) => (float) ($row['remain_qty_kecil'] ?? 0))),
            'used_by_transaction' => trim((string) ($first['used_by_transaction'] ?? '')),
        ];
    }

    private function buildReturReferenceUsageMaps(array $detailRows): array
    {
        $soUsage = [];
        $srjUsage = [];

        foreach ($detailRows as $row) {
            $qtyKecil = (float) ($row['fqtykecil'] ?? 0);
            if ($qtyKecil <= 0) {
                continue;
            }

            $productCode = trim((string) ($row['fprdcode'] ?? ''));
            $soDocNo = trim((string) ($row['frefso'] ?? ''));
            $srjDocNo = trim((string) ($row['frefsrj'] ?? ''));
            $refNoAcak = $this->normalizeReferenceRandomNumbers($row['frefnoacak'] ?? null) ?? '';

            if ($srjDocNo !== '') {
                $soDocNo = '';
            }

            if ($soDocNo !== '') {
                $key = $this->buildReferenceUsageKey($soDocNo, $productCode, $refNoAcak);
                $soUsage[$key] = ($soUsage[$key] ?? 0) + $qtyKecil;
            }

            if ($srjDocNo !== '') {
                $key = $this->buildReferenceUsageKey($srjDocNo, $productCode, $refNoAcak);
                $srjUsage[$key] = ($srjUsage[$key] ?? 0) + $qtyKecil;
            }
        }

        return [$soUsage, $srjUsage];
    }

    private function buildReturReferenceRestoreMaps(string $fsono): array
    {
        $rows = DB::table('trandt as d')
            ->where('d.fsono', $fsono)
            ->get([
                'd.frefso',
                'd.frefsrj',
                'd.fprdcode',
                'd.frefnoacak',
                'd.fqtykecil',
            ]);

        $soRestore = [];
        $srjRestore = [];

        foreach ($rows as $row) {
            $qtyKecil = (float) ($row->fqtykecil ?? 0);
            if ($qtyKecil <= 0) {
                continue;
            }

            $productCode = trim((string) ($row->fprdcode ?? ''));
            $refNoAcak = $this->normalizeReferenceRandomNumbers($row->frefnoacak ?? null) ?? '';
            $srjDocNo = trim((string) ($row->frefsrj ?? ''));
            $soDocNo = $srjDocNo !== '' ? '' : trim((string) ($row->frefso ?? ''));

            if ($soDocNo !== '') {
                $key = $this->buildReferenceUsageKey($soDocNo, $productCode, $refNoAcak);
                $soRestore[$key] = ($soRestore[$key] ?? 0) + $qtyKecil;
            }

            if ($srjDocNo !== '') {
                $key = $this->buildReferenceUsageKey($srjDocNo, $productCode, $refNoAcak);
                $srjRestore[$key] = ($srjRestore[$key] ?? 0) + $qtyKecil;
            }
        }

        return [$soRestore, $srjRestore];
    }

    private function getReturReferenceStats(string $type, array $docNos, ?string $exceptFsono = null): array
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

        if ($type === 'SO') {
            $sourceRows = DB::table('trandt as d')
                ->join('tranmt as h', 'h.fsono', '=', 'd.fsono')
                ->leftJoin('msprd as p', 'p.fprdcode', '=', 'd.fprdcode')
                ->whereIn('d.fsono', $docNos)
                ->where('h.fsono', 'like', 'INV.%')
                ->selectRaw("
                    TRIM(d.fsono) as ref_doc,
                    TRIM(d.fprdcode) as product_code,
                    COALESCE(d.fnoacak::text, '') as ref_noacak,
                    MAX(COALESCE(p.fprdname, d.fprdcode)) as product_name,
                    MAX(COALESCE(d.fsatuan, '')) as source_unit,
                    SUM(COALESCE(d.fqtykecil, 0)) as source_qty_kecil,
                    SUM(COALESCE(d.fqtyremain, 0)) as remain_qty_kecil
                ")
                ->groupByRaw("TRIM(d.fsono), TRIM(d.fprdcode), COALESCE(d.fnoacak::text, '')")
                ->get();

            $usageRows = collect();
        } else {
            $sourceRows = DB::table('trstockdt as d')
                ->leftJoin('msprd as p', 'p.fprdcode', '=', 'd.fprdcode')
                ->whereIn('d.fstockmtno', $docNos)
                ->selectRaw("
                    TRIM(d.fstockmtno) as ref_doc,
                    TRIM(d.fprdcode) as product_code,
                    COALESCE(d.fnoacak::text, '') as ref_noacak,
                    MAX(COALESCE(p.fprdname, d.fprdcode)) as product_name,
                    MAX(COALESCE(d.fsatuan, '')) as source_unit,
                    SUM(COALESCE(d.fqtykecil, 0)) as source_qty_kecil,
                    SUM(COALESCE(d.fqtyremain, 0)) as remain_qty_kecil
                ")
                ->groupByRaw("TRIM(d.fstockmtno), TRIM(d.fprdcode), COALESCE(d.fnoacak::text, '')")
                ->get();

            $usageRows = collect();
        }

        $stats = [];

        foreach ($sourceRows as $row) {
            $normalizedRefNoAcak = $this->normalizeReferenceRandomNumbers($row->ref_noacak ?? null) ?? '';
            $key = $this->buildReferenceUsageKey($row->ref_doc ?? '', $row->product_code ?? '', $normalizedRefNoAcak);
            $stats[$key] = [
                'ref_doc' => trim((string) ($row->ref_doc ?? '')),
                'product_code' => trim((string) ($row->product_code ?? '')),
                'product_name' => trim((string) ($row->product_name ?? '')),
                'source_unit' => trim((string) ($row->source_unit ?? '')),
                'ref_noacak' => $normalizedRefNoAcak,
                'source_qty_kecil' => (float) ($row->source_qty_kecil ?? 0),
                'used_qty_kecil' => 0.0,
                'remain_qty_kecil' => (float) ($row->remain_qty_kecil ?? $row->source_qty_kecil ?? 0),
                'used_by_transaction' => '',
            ];
        }

        foreach ($usageRows as $row) {
            $normalizedRefNoAcak = $this->normalizeReferenceRandomNumbers($row->ref_noacak ?? null) ?? '';
            $key = $this->buildReferenceUsageKey($row->ref_doc ?? '', $row->product_code ?? '', $normalizedRefNoAcak);
            if (! isset($stats[$key])) {
                $stats[$key] = [
                    'ref_doc' => trim((string) ($row->ref_doc ?? '')),
                    'product_code' => trim((string) ($row->product_code ?? '')),
                    'product_name' => trim((string) ($row->product_code ?? '')),
                    'source_unit' => '',
                    'ref_noacak' => $normalizedRefNoAcak,
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

    private function restoreReturReferenceUsage(array $soRestoreByReference, array $srjRestoreByReference): void
    {
        if (! empty($soRestoreByReference)) {
            $docNos = $this->extractReferenceDocsFromUsageKeys(array_keys($soRestoreByReference));
            $sourceRows = DB::table('trsodt as d')
                ->whereIn('d.fsono', $docNos)
                ->selectRaw("
                    d.ftrsodtid,
                    TRIM(d.fsono) as ref_doc,
                    TRIM(d.fprdcode) as product_code,
                    COALESCE(d.fnoacak::text, '') as ref_noacak
                ")
                ->get();

            foreach ($sourceRows as $row) {
                $key = $this->buildReferenceUsageKey($row->ref_doc ?? '', $row->product_code ?? '', $row->ref_noacak ?? '');
                $qtyKecil = (float) ($soRestoreByReference[$key] ?? 0);
                if ($qtyKecil <= 0) {
                    continue;
                }

                DB::table('trsodt')
                    ->where('ftrsodtid', $row->ftrsodtid)
                    ->update([
                        'fqtykecil' => (float) ($row->source_qty_kecil ?? 0),
                    ]);
            }
        }

        if (! empty($srjRestoreByReference)) {
            $docNos = $this->extractReferenceDocsFromUsageKeys(array_keys($srjRestoreByReference));
            $sourceRows = DB::table('trstockdt as d')
                ->whereIn('d.fstockmtno', $docNos)
                ->selectRaw("
                    d.fstockdtid,
                    COALESCE(d.fqtykecil, 0) as source_qty_kecil,
                    TRIM(d.fstockmtno) as ref_doc,
                    TRIM(d.fprdcode) as product_code,
                    COALESCE(d.fnoacak::text, '') as ref_noacak
                ")
                ->get();

            foreach ($sourceRows as $row) {
                $key = $this->buildReferenceUsageKey($row->ref_doc ?? '', $row->product_code ?? '', $row->ref_noacak ?? '');
                $qtyKecil = (float) ($srjRestoreByReference[$key] ?? 0);
                if ($qtyKecil <= 0) {
                    continue;
                }

                DB::table('trstockdt')
                    ->where('fstockdtid', $row->fstockdtid)
                    ->update([
                        'fqtyremain' => (float) ($row->source_qty_kecil ?? 0),
                    ]);
            }
        }
    }

    private function convertQtyKecilToUnit(float $qtyKecil, string $unit, $productRow): float
    {
        $unit = trim((string) $unit);
        $ratio1 = (float) ($productRow->fqtykecil ?? 0);
        $ratio2 = (float) ($productRow->fqtykecil2 ?? 0);

        if ($unit !== '' && $unit === trim((string) ($productRow->fsatuanbesar2 ?? '')) && $ratio2 > 0) {
            return $qtyKecil / $ratio2;
        }

        if ($unit !== '' && $unit === trim((string) ($productRow->fsatuanbesar ?? '')) && $ratio1 > 0) {
            return $qtyKecil / $ratio1;
        }

        return $qtyKecil;
    }

    public function edit(Request $request, $ftranmtid)
    {
        $customers = Customer::orderBy('fcustomername', 'asc')
            ->get(['fcustomerid', 'fcustomername', 'fcustomercode']);

        $salesmans = Salesman::orderBy('fsalesmanname', 'asc')
            ->get(['fsalesmanid', 'fsalesmanname', 'fsalesmancode']);

        $warehouses = DB::table('mswh')
            ->select('fwhid', 'fwhcode', 'fwhname', 'fbranchcode', 'fnonactive')
            ->where('fnonactive', '0')
            ->orderBy('fwhcode')
            ->get();

        $returpenjualan = Tranmt::with(['customer', 'details' => function ($q) {
            $q->leftJoin('msprd', function ($j) {
                $j->on('msprd.fprdcode', '=', 'trandt.fprdcode');
            })
                ->select(
                    'trandt.*',
                    'msprd.fprdcode as fitemcode',
                    'msprd.fprdname'
                )
                // Ubah order ke ftrandtid (Primary Key detail) karena ftranmtid tidak ada
                ->orderBy('trandt.ftrandtid', 'asc');
        }])->findOrFail($ftranmtid);

        if ($message = $this->getPostedPeriodLockMessage($returpenjualan->fsodate, 'Retur ini')) {
            return redirect()->route('returpenjualan.view', $returpenjualan->ftranmtid)->with('error', $message);
        }

        if (! $returpenjualan->customer) {
            $returpenjualan->setRelation('customer', Customer::where('fcustomercode', trim((string) $returpenjualan->fcustno))->first());
        }

        ['fcabang' => $fcabang, 'fbranchcode' => $fbranchcode] = $this->resolveBranchContext($returpenjualan->fbranchcode ?? null);

        $usageLockMessage = $this->getUsageLockMessage($returpenjualan);

        if (! empty($usageLockMessage)) {
            return redirect()
                ->route('returpenjualan.view', $returpenjualan->ftranmtid)
                ->with('error', $usageLockMessage);
        }

        $referenceSummary = $this->getReferenceSummaryByTranNo((string) $returpenjualan->fsono);

        $savedItems = $returpenjualan->details->map(function ($d) use ($referenceSummary) {
            $refCode = strtoupper(trim($d->frefcode ?? ''));
            $valSo = trim($d->frefso ?? '');
            $valSrj = trim($d->frefsrj ?? '');
            // 2. Logika Prioritas Tampilan
            $displayRef = '-';

            // Jika ada SRJ, tampilkan SRJ (biasanya SRJ lebih spesifik untuk retur)
            if ($valSrj !== '') {
                $displayRef = $valSrj;
                $refCode = 'SRJ'; // Paksa refcode jadi SRJ jika ada nilainya
            }
            // Jika tidak ada SRJ tapi ada SO
            elseif ($valSo !== '') {
                $displayRef = $valSo;
                $refCode = 'SO';
            }

            $summary = $referenceSummary[(int) ($d->ftrandtid ?? 0)] ?? ['fqtyterinvoice' => 0, 'fqtysisa_ref' => 0];
            $maxqty = max(0.0, (float) ($d->fqty ?? 0) + (float) ($summary['fqtysisa_ref'] ?? 0));

            return [
                'uid' => $d->ftrandtid,
                'fitemcode' => (string) ($d->fitemcode ?? ''),
                'fitemname' => (string) ($d->fprdname ?? ''),
                'fsatuan' => (string) ($d->fsatuan ?? ''),
                'frefdtno' => (string) ($d->frefdtno ?? ''),
                'fqty' => (float) ($d->fqty ?? 0),
                'fterima' => (float) ($d->fterima ?? 0),
                'fqtyremain' => $maxqty,
                'maxqty' => $maxqty,
                'fprice' => (float) ($d->fprice ?? 0),
                'fdisc' => $this->normalizeDiscountInput($d->fdisc ?? 0),
                'ftotal' => (float) ($d->famount ?? 0),
                'fdesc' => (string) ($d->fdesc ?? ''),
                'frefcode' => $refCode,
                'frefpr' => $displayRef, // Kolom ini yang akan ditampilkan di Blade
                'frefso' => $valSo,
                'frefsrj' => $valSrj,
                'fnoacak' => (string) ($d->fnoacak ?? ''),
                'frefnoacak' => (string) ($d->frefnoacak ?? ''),
                'fqtyterinvoice' => (float) ($summary['fqtyterinvoice'] ?? 0),
                'fqtysisa_ref' => (float) ($summary['fqtysisa_ref'] ?? 0),
            ];
        })->values();
        $selectedSupplierCode = $returpenjualan->fsupplier;

        // Fetch all products for product mapping
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

        // Prepare the product map for frontend
        $productMap = $this->buildProductMap($products);

        // Pass the data to the view
        return view('returpenjualan.edit', [
            'customers' => $customers,
            'salesmans' => $salesmans,
            'warehouses' => $warehouses,
            'selectedSupplierCode' => $selectedSupplierCode, // Kirim kode supplier ke view
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'products' => $products,
            'productMap' => $productMap,
            'returpenjualan' => $returpenjualan,
            'displayFsono' => $this->formatDisplayTransactionNumber($returpenjualan->fsono ?? null, (string) ($returpenjualan->fincludeppn ?? '0') === '1'),
            'savedItems' => $savedItems,
            'ppnAmount' => (float) ($returpenjualan->famountpopajak ?? 0), // total PPN from DB
            'famountgross' => (float) ($returpenjualan->famountgross ?? 0),  // nilai Grand Total dari DB
            'famountso' => (float) ($returpenjualan->famountso ?? 0),  // nilai Grand Total dari DB
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'filterSalesmanId' => $request->query('filter_salesman_id'),
            'isUsageLocked' => ! empty($usageLockMessage),
            'usageLockMessage' => $usageLockMessage,
            'action' => 'edit',
        ]);
    }

    public function view(Request $request, $ftranmtid)
    {
        $customers = Customer::orderBy('fcustomername', 'asc')
            ->get(['fcustomerid', 'fcustomername', 'fcustomercode']);

        $salesmans = Salesman::orderBy('fsalesmanname', 'asc')
            ->get(['fsalesmanid', 'fsalesmanname', 'fsalesmancode']);

        $warehouses = DB::table('mswh')
            ->select('fwhid', 'fwhcode', 'fwhname', 'fbranchcode', 'fnonactive')
            ->where('fnonactive', '0')
            ->orderBy('fwhcode')
            ->get();

        $returpenjualan = Tranmt::with(['customer', 'details' => function ($q) {
            $q->leftJoin('msprd', function ($j) {
                $j->on('msprd.fprdcode', '=', 'trandt.fprdcode');
            })
                ->select(
                    'trandt.*',
                    'msprd.fprdcode as fitemcode',
                    'msprd.fprdname'
                )
                // Ubah order ke ftrandtid (Primary Key detail) karena ftranmtid tidak ada
                ->orderBy('trandt.ftrandtid', 'asc');
        }])->findOrFail($ftranmtid);

        if ($message = $this->getPostedPeriodLockMessage($returpenjualan->fsodate, 'Retur ini')) {
            return redirect()->route('returpenjualan.view', $returpenjualan->ftranmtid)->with('error', $message);
        }

        if (! $returpenjualan->customer) {
            $returpenjualan->setRelation('customer', Customer::where('fcustomercode', trim((string) $returpenjualan->fcustno))->first());
        }

        ['fcabang' => $fcabang, 'fbranchcode' => $fbranchcode] = $this->resolveBranchContext($returpenjualan->fbranchcode ?? null);

        $referenceSummary = $this->getReferenceSummaryByTranNo((string) $returpenjualan->fsono);

        $savedItems = $returpenjualan->details->map(function ($d) use ($referenceSummary) {
            $refCode = strtoupper(trim($d->frefcode ?? ''));
            $valSo = trim($d->frefso ?? '');
            $valSrj = trim($d->frefsrj ?? '');
            // 2. Logika Prioritas Tampilan
            $displayRef = '-';

            // Jika ada SRJ, tampilkan SRJ (biasanya SRJ lebih spesifik untuk retur)
            if ($valSrj !== '') {
                $displayRef = $valSrj;
                $refCode = 'SRJ'; // Paksa refcode jadi SRJ jika ada nilainya
            }
            // Jika tidak ada SRJ tapi ada SO
            elseif ($valSo !== '') {
                $displayRef = $valSo;
                $refCode = 'SO';
            }

            $summary = $referenceSummary[(int) ($d->ftrandtid ?? 0)] ?? ['fqtyterinvoice' => 0, 'fqtysisa_ref' => 0];

            return [
                'uid' => $d->ftrandtid,
                'fitemcode' => (string) ($d->fitemcode ?? ''),
                'fitemname' => (string) ($d->fprdname ?? ''),
                'fsatuan' => (string) ($d->fsatuan ?? ''),
                'frefdtno' => (string) ($d->frefdtno ?? ''),
                'fqty' => (float) ($d->fqty ?? 0),
                'fterima' => (float) ($d->fterima ?? 0),
                'fqtyremain' => (float) ($d->fqtyremain ?? 0),
                'fprice' => (float) ($d->fprice ?? 0),
                'fdisc' => $this->normalizeDiscountInput($d->fdisc ?? 0),
                'ftotal' => (float) ($d->famount ?? 0),
                'fdesc' => (string) ($d->fdesc ?? ''),
                'fketdt' => (string) ($d->fketdt ?? ''),
                'frefcode' => $refCode,
                'frefpr' => $displayRef,
                'fnoacak' => (string) ($d->fnoacak ?? ''),
                'frefnoacak' => (string) ($d->frefnoacak ?? ''),
                'fqtyterinvoice' => (float) ($summary['fqtyterinvoice'] ?? 0),
                'fqtysisa_ref' => (float) ($summary['fqtysisa_ref'] ?? 0),
            ];
        })->values();
        $selectedSupplierCode = $returpenjualan->fsupplier;

        // Fetch all products for product mapping
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

        // Prepare the product map for frontend
        $productMap = $this->buildProductMap($products);

        // Pass the data to the view
        return view('returpenjualan.edit', [
            'customers' => $customers,
            'salesmans' => $salesmans,
            'warehouses' => $warehouses,
            'selectedSupplierCode' => $selectedSupplierCode, // Kirim kode supplier ke view
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'products' => $products,
            'productMap' => $productMap,
            'returpenjualan' => $returpenjualan,
            'displayFsono' => $this->formatDisplayTransactionNumber($returpenjualan->fsono ?? null, (string) ($returpenjualan->fincludeppn ?? '0') === '1'),
            'savedItems' => $savedItems,
            'ppnAmount' => (float) ($returpenjualan->famountpopajak ?? 0), // total PPN from DB
            'famountgross' => (float) ($returpenjualan->famountgross ?? 0),  // nilai Grand Total dari DB
            'famountso' => (float) ($returpenjualan->famountso ?? 0),  // nilai Grand Total dari DB
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'filterSalesmanId' => $request->query('filter_salesman_id'),
            'isUsageLocked' => false,
            'usageLockMessage' => null,
            'action' => 'view',
        ]);
    }

    public function update(Request $request, $ftranmtid)
    {
        // 1. VALIDASI
        $request->validate([
            'fsodate' => ['required', 'date'],
            'fcustno' => ['required', 'string', 'max:10'],
            'ffrom' => ['required', 'string', 'max:10'],
            'fitemcode' => ['required', 'array', 'min:1'],
            'fitemcode.*' => ['required', 'string', 'max:30'],
            'fqty' => ['required', 'array'],
            'fqty.*' => ['numeric', 'min:0.01'],
            'fprice' => ['required', 'array'],
            'fprice.*' => ['numeric', 'min:0'],
            'fdisc' => ['nullable', 'array'],
            'frefso' => ['nullable'],
            'frefsrj' => ['nullable'],
            'fnoacak' => ['nullable', 'array'],
            'fnoacak.*' => ['nullable', 'regex:/^[1-9]{3}$/'],
            'frefnoacak' => ['nullable', 'array'],
            'frefnoacak.*' => ['nullable', 'regex:/^\d{3}$/'],
        ]);

        // 2. LOAD HEADER
        $header = DB::table('tranmt')->where('ftranmtid', $ftranmtid)->first();
        if (! $header) {
            return abort(404, 'Faktur penjualan tidak ada.');
        }

        if ($message = $this->getPostedPeriodLockMessage($header->fsodate, 'Retur ini')) {
            return redirect()->route('returpenjualan.view', $ftranmtid)->with('error', $message);
        }

        if ($message = $this->getUsageLockMessage((object) $header)) {
            return redirect()->route('returpenjualan.index')->with('error', $message);
        }

        // 3. INISIALISASI DATA
        $fsodate = Carbon::parse($request->fsodate);
        $this->ensureCreateDateWithinEditPeriod($fsodate, $header->fsodate);
        $fincludeppn = $request->boolean('fincludeppn') ? '1' : '0';
        $fapplyppn = $request->input('fapplyppn', '0');
        $ppnPersen = (float) $request->input('fppnpersen', 11);
        $userid = mb_substr(auth('sysuser')->user()->fname ?? 'admin', 0, 10);
        $now = now();
        $frate = (float) $request->input('frate', $header->frate ?? 1);

        $itemCodes = $request->input('fitemcode', []);
        $typeSales = (int) $request->input('ftypesales', 0); // 0: Penjualan, 1: Uang Muka
        $itemDescs = $request->input('fitemname', []);
        $satuans = $request->input('fsatuan', []);
        $qtys = $request->input('fqty', []);
        $prices = $request->input('fprice', []);
        $discs = $request->input('fdisc', []);

        $frefcodes = $request->input('frefcode', []);   // per baris, jika array
        $frefso = $request->input('frefso', []);
        $frefsrj = $request->input('frefsrj', []);
        $this->sanitizeReturReferences($frefso, $frefsrj);
        $fnoacaks = $request->input('fnoacak', []);
        $frefnoacaks = $request->input('frefnoacak', []);

        $this->ensureNoDuplicateDetailCodes(
            $itemCodes,
            $frefcodes,
            $frefso,
            $frefsrj,
            $frefnoacaks
        );

        if ($typeSales === 1) {
            $frefcode = 'UM';
        } else {
            $frefcode = $request->input('frefcode_global')
                ?: ($header->frefcode ?? '');  // fallback dari DB jika kosong
        }

        // Ambil mapping produk untuk mendapatkan rasio satuan
        $products = DB::table('msprd')
            ->whereIn('fprdcode', array_filter($itemCodes))
            ->get([
                'fprdid',
                'fprdcode',
                'fsatuankecil',
                'fsatuanbesar',
                'fsatuanbesar2',
                'fqtykecil',
                'fqtykecil2',
            ])
            ->keyBy('fprdcode');

        // 4. BUILD DETAIL ROWS
        $detailRows = [];
        $totalGross = 0;
        $totalDisc = 0;
        $totalSalesNet = 0.0;
        $usedNoAcaks = [];

        $hasUM = in_array('UM', $itemCodes);

        if ($hasUM && $typeSales === 0) {
            // Jika ada "UM" tapi Type adalah Penjualan (0) -> ERROR
            return back()->withInput()->with('error', 'Produk Uang Muka (UM) hanya diperbolehkan untuk tipe transaksi Uang Muka.');
        }

        if (! $hasUM && $typeSales === 1) {
            // Tambahan: Jika tipe Uang Muka (1) tapi tidak ada item "UM" -> ERROR (Opsional)
            return back()->withInput()->with('error', 'Transaksi Uang Muka wajib menggunakan produk dengan kode UM.');
        }

        $stockDetailRows = [];
        foreach ($itemCodes as $i => $code) {
            $qty = (float) ($qtys[$i] ?? 0);
            $price = (float) ($prices[$i] ?? 0);

            if (empty($code) || $qty <= 0) {
                continue;
            }

            $product = $products->get($code);

            // --- OVERRIDE unit dari referensi (SRJ / Invoice) ---
            $refSrjDoc = trim((string) ($frefsrj[$i] ?? ''));
            $refSoDoc = trim((string) ($frefso[$i] ?? ''));
            if ($refSrjDoc !== '') {
                $price = 0.0;
                $discs[$i] = 0;
            }
            $refNoAcak = $this->normalizeReferenceRandomNumbers($frefnoacaks[$i] ?? null);
            $referenceRatio = null;
            $referenceDetail = null;
            if ($refSrjDoc !== '') {
                $referenceDetail = $this->resolveReturReferenceSourceDetail('SRJ', $refSrjDoc, $code, $frefnoacaks[$i] ?? null);
            } elseif ($refSoDoc !== '') {
                $referenceDetail = $this->resolveReturReferenceSourceDetail('INV', $refSoDoc, $code, $frefnoacaks[$i] ?? null);
            }
            if ($referenceDetail && ! empty($referenceDetail->fnoacak)) {
                $refNoAcak = trim((string) $referenceDetail->fnoacak);
            }
            if ($referenceDetail && trim((string) ($referenceDetail->fsatuan ?? '')) !== '') {
                $satuans[$i] = trim((string) $referenceDetail->fsatuan);
            }
            if ($referenceDetail) {
                $referenceQty = (float) ($referenceDetail->fqty ?? 0);
                $referenceQtyKecil = (float) ($referenceDetail->fqtykecil ?? 0);
                if ($referenceQty > 0 && $referenceQtyKecil > 0) {
                    $referenceRatio = $referenceQtyKecil / $referenceQty;
                }
            }
            // --- END override ---

            $qtyKecil = $qty;
            $selectedUnit = trim((string) ($satuans[$i] ?? ''));
            if ($referenceRatio !== null && $referenceRatio > 0) {
                $qtyKecil = $qty * $referenceRatio;
            } elseif (
                $product
                && $selectedUnit !== ''
                && $selectedUnit === trim((string) ($product->fsatuanbesar ?? ''))
                && (float) ($product->fqtykecil ?? 0) > 0
            ) {
                $qtyKecil = $qty * (float) $product->fqtykecil;
            } elseif (
                $product
                && $selectedUnit !== ''
                && $selectedUnit === trim((string) ($product->fsatuanbesar2 ?? ''))
                && (float) ($product->fqtykecil2 ?? 0) > 0
            ) {
                $qtyKecil = $qty * (float) $product->fqtykecil2;
            }

            $discRaw = $this->normalizeDiscountInput($discs[$i] ?? 0);
            $discPersen = $this->parseDiscount($discRaw);
            $subtotal = $qty * $price;
            $discAmount = $subtotal * ($discPersen / 100);
            $netPrice = $price - ($price * ($discPersen / 100));
            $amountRow = $subtotal - $discAmount;

            $totalGross += $subtotal;
            $totalDisc += $discAmount;

            if ($fincludeppn == 1 && $fapplyppn == 1) {
                $fsalesnet = (100 / (100 + $ppnPersen)) * $netPrice;
            } else {
                $fsalesnet = $netPrice;
            }
            $totalSalesNet += $qty * $fsalesnet;

            $detailRows[] = array_merge([
                'fsono' => $header->fsono,
                'fnou' => $i + 1,
                'fprdcode' => mb_substr($code, 0, 30),
                'fdesc' => $itemDescs[$i] ?? '',
                'fqty' => $qty,
                'fqtykecil' => $qtyKecil,
                'fqtyremain' => $qty,
                'fprice' => $price,
                'fprice_rp' => $price * $frate,
                'fdisc' => $discRaw,
                'fpricenet' => $netPrice,
                'fpricenet_rp' => $netPrice * $frate,
                'fsalesnet' => $fsalesnet,
                'famount' => $amountRow,
                'famount_rp' => $amountRow * $frate,
                'fsatuan' => mb_substr($satuans[$i] ?? '', 0, 5),
                'fuserid' => $userid,
                'fdatetime' => $now,
                'frefcode' => 'REJ',
                'frefso' => $refSoDoc,
                'frefsrj' => $refSrjDoc,
                'fnoacak' => $this->normalizeRandomNumber($fnoacaks[$i] ?? null, $usedNoAcaks),
            ], $this->buildReferenceRandomNumberColumns($refSrjDoc !== '' ? 'SRJ' : ($frefcode ?? ''), $refNoAcak));

            $stockDetailRows[] = [
                'fprdcode' => mb_substr($code, 0, 30),
                'fdesc' => $itemDescs[$i] ?? '',
                'fqty' => $qty,
                'fqtykecil' => $qtyKecil,
                'fqtyremain' => $qtyKecil,
                'fprice' => $price,
                'fprice_rp' => $price * $frate,
                'ftotprice' => $amountRow,
                'fusercreate' => $userid,
                'fdatetime' => $now,
                'fsatuan' => mb_substr($satuans[$i] ?? '', 0, 5),
                'fcode' => '0',
            ];
        }

        [$oldSoRestoreByReference, $oldSrjRestoreByReference] = $this->buildReturReferenceRestoreMaps($header->fsono);

        [$soUsageByReference, $srjUsageByReference] = $this->buildReturReferenceUsageMaps($detailRows);

        if ($validationMessage = $this->validateReferenceUsage(
            $soUsageByReference,
            $srjUsageByReference,
            $header->fsono
        )) {
            return back()->withInput()->with('error', $validationMessage);
        }

        // 5. KALKULASI TOTAL
        $fapplyppn = $request->input('fapplyppn', '0');
        $amountNet = $totalGross - $totalDisc;
        $ppnPersen = (float) $request->input('fppnpersen', 11);

        if ($fincludeppn === '1') {
            if ($fapplyppn === '1') {
                // INCLUDE
                $ppnAmount = $amountNet * ($ppnPersen / (100 + $ppnPersen));
                $amountNet = $amountNet - $ppnAmount;
                $grandTotal = $amountNet + $ppnAmount;
            } else {
                // EXCLUDE
                $ppnAmount = $amountNet * ($ppnPersen / 100);
                $grandTotal = $amountNet + $ppnAmount;
            }
        } else {
            $ppnAmount = 0;
            $grandTotal = $amountNet;
        }

        $ftypesales = $request->input('ftypesales', 0);

        // 6. TRANSACTION
        try {
            DB::transaction(function () use (
                $request,
                $ftranmtid,
                $header,
                $fsodate,
                $fincludeppn,
                $userid,
                $now,
                $ftypesales,
                $detailRows,
                $stockDetailRows,
                $oldSoRestoreByReference,
                $oldSrjRestoreByReference,
                $totalGross,
                $totalDisc,
                $amountNet,
                $ppnAmount,
                $grandTotal,
                $frate,
                $ppnPersen,
                $fapplyppn,
                $totalSalesNet
            ) {
                // Update Header (tranmt)
                DB::table('tranmt')->where('ftranmtid', $ftranmtid)->update([
                    'fsodate' => $fsodate,
                    'fcustno' => mb_substr($request->fcustno, 0, 10),
                    'fsalesman' => mb_substr((string) ($request->fsalesman ?? ''), 0, 30),
                    'ffrom' => mb_substr((string) ($request->ffrom ?? ''), 0, 10),
                    'fdiscount' => $totalDisc,
                    'fdiscount_rp' => $totalDisc * $frate,
                    'famountgross' => $totalGross,
                    'famountgross_rp' => $totalGross * $frate,
                    'famountsonet' => $amountNet,
                    'famountsonet_rp' => $amountNet * $frate,
                    'famountpajak' => $ppnAmount,
                    'famountpajak_rp' => $ppnAmount * $frate,
                    'famountso' => $grandTotal,
                    'famountso_rp' => $grandTotal * $frate,
                    'ftotalsalesnet' => $totalSalesNet,
                    'fket' => $request->fket ?? '',
                    'fuserid' => $userid,
                    'fdatetime' => $now,
                    'fincludeppn' => $fincludeppn,
                    'fapplyppn' => $fapplyppn,
                    'ftypesales' => $ftypesales,
                    'fppnpersen' => $ppnPersen,
                    'ftaxno' => $request->ftaxno ?? '0',
                ]);

                // Delete detail lama agar tidak duplikat saat update
                DB::table('trandt')->where('fsono', $header->fsono)->delete();

                $this->restoreReturReferenceUsage($oldSoRestoreByReference, $oldSrjRestoreByReference);

                // Insert detail baru
                if (! empty($detailRows)) {
                    DB::table('trandt')->insert($detailRows);
                }

                // ==== SYNC STOCK RECORDS ====
                $fstockmtno = str_replace('REJ.', 'REB.', $header->fsono);
                $stockHeader = DB::table('trstockmt')->where('fstockmtno', $fstockmtno)->first();

                if ($stockHeader) {
                    // Update Stock Header
                    DB::table('trstockmt')->where('fstockmtid', $stockHeader->fstockmtid)->update([
                        'fstockmtdate' => $fsodate,
                        'fsupplier' => mb_substr($request->fcustno, 0, 10),
                        'ffrom' => mb_substr((string) ($request->ffrom ?? ''), 0, 10),
                        'famount' => $amountNet,
                        'famount_rp' => $amountNet * $frate,
                        'famountpajak' => $ppnAmount,
                        'famountpajak_rp' => $ppnAmount * $frate,
                        'famountmt' => $grandTotal,
                        'famountmt_rp' => $grandTotal * $frate,
                        'famountremain' => $grandTotal,
                        'famountremain_rp' => $grandTotal * $frate,
                        'fket' => $request->fket ?? '',
                        'fusercreate' => $userid,
                        'fdatetime' => $now,
                        'fbranchcode' => $request->fbranchcode ?? $stockHeader->fbranchcode ?? 'BG',
                        'fincludeppn' => $fincludeppn,
                    ]);

                    // Sync Stock Details
                    DB::table('trstockdt')->where('fstockmtno', $fstockmtno)->delete();
                    foreach ($stockDetailRows as &$srow) {
                        $srow['fstockmtno'] = $fstockmtno;
                        $srow['fstockmtcode'] = 'REB';
                    }
                    unset($srow);
                    DB::table('trstockdt')->insert($stockDetailRows);
                }

                $this->syncReturPenjualanJournalEntries(
                    (string) $header->fsono,
                    $fsodate,
                    (string) ($request->input('fbranchcode') ?: $header->fbranchcode ?: 'BG'),
                    (string) $request->fcustno,
                    (float) $amountNet,
                    (float) $ppnAmount,
                    (float) $grandTotal,
                    (string) $userid
                );

                // Validasi sisa SO/SRJ berdasarkan fqtykecil dinonaktifkan.
            });

            return redirect()->route('returpenjualan.index')->with('success', 'Retur penjualan '.$this->formatDisplayTransactionNumber($header->fsono, $fincludeppn === '1').' berhasil diupdate.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Retur penjualan belum bisa diupdate. Cek data transaksi.');
        }
    }

    public function delete(Request $request, $ftranmtid)
    {
        $customers = Customer::orderBy('fcustomername', 'asc')
            ->get(['fcustomerid', 'fcustomername', 'fcustomercode']);

        $salesmans = Salesman::orderBy('fsalesmanname', 'asc')
            ->get(['fsalesmanid', 'fsalesmanname', 'fsalesmancode']);

        $warehouses = DB::table('mswh')
            ->select('fwhid', 'fwhcode', 'fwhname', 'fbranchcode', 'fnonactive')
            ->where('fnonactive', '0')
            ->orderBy('fwhcode')
            ->get();

        $returpenjualan = Tranmt::with(['customer', 'details' => function ($q) {
            $q->leftJoin('msprd', function ($j) {
                $j->on('msprd.fprdcode', '=', 'trandt.fprdcode');
            })
                ->select(
                    'trandt.*',
                    'msprd.fprdcode as fitemcode',
                    'msprd.fprdname'
                )
                // Ubah order ke ftrandtid (Primary Key detail) karena ftranmtid tidak ada
                ->orderBy('trandt.ftrandtid', 'asc');
        }])->findOrFail($ftranmtid);

        if ($message = $this->getPostedPeriodLockMessage($returpenjualan->fsodate, 'Retur ini')) {
            return redirect()->route('returpenjualan.view', $returpenjualan->ftranmtid)->with('error', $message);
        }

        if (! $returpenjualan->customer) {
            $returpenjualan->setRelation('customer', Customer::where('fcustomercode', trim((string) $returpenjualan->fcustno))->first());
        }

        ['fcabang' => $fcabang, 'fbranchcode' => $fbranchcode] = $this->resolveBranchContext($returpenjualan->fbranchcode ?? null);

        $usageLockMessage = $this->getUsageLockMessage($returpenjualan);

        if (! empty($usageLockMessage)) {
            return redirect()
                ->route('returpenjualan.view', $returpenjualan->ftranmtid)
                ->with('error', $usageLockMessage);
        }

        $referenceSummary = $this->getReferenceSummaryByTranNo((string) $returpenjualan->fsono);

        $savedItems = $returpenjualan->details->map(function ($d) use ($referenceSummary) {
            $refCode = strtoupper(trim($d->frefcode ?? ''));
            $valSo = trim($d->frefso ?? '');
            $valSrj = trim($d->frefsrj ?? '');
            // 2. Logika Prioritas Tampilan
            $displayRef = '-';

            // Jika ada SRJ, tampilkan SRJ (biasanya SRJ lebih spesifik untuk retur)
            if ($valSrj !== '') {
                $displayRef = $valSrj;
                $refCode = 'SRJ'; // Paksa refcode jadi SRJ jika ada nilainya
            }
            // Jika tidak ada SRJ tapi ada SO
            elseif ($valSo !== '') {
                $displayRef = $valSo;
                $refCode = 'SO';
            }

            $summary = $referenceSummary[(int) ($d->ftrandtid ?? 0)] ?? ['fqtyterinvoice' => 0, 'fqtysisa_ref' => 0];

            return [
                'uid' => $d->ftrandtid,
                'fitemcode' => (string) ($d->fitemcode ?? ''),
                'fitemname' => (string) ($d->fprdname ?? ''),   // dari msprd.fprdname
                'fsatuan' => (string) ($d->fsatuan ?? ''),
                'frefdtno' => (string) ($d->frefdtno ?? ''),
                'fqty' => (float) ($d->fqty ?? 0),
                'fterima' => (float) ($d->fterima ?? 0),
                'fqtyremain' => (float) ($d->fqtyremain ?? 0),
                'fprice' => (float) ($d->fprice ?? 0),
                'fdisc' => $this->normalizeDiscountInput($d->fdisc ?? 0),
                'ftotal' => (float) ($d->famount ?? 0),
                'fdesc' => (string) ($d->fdesc ?? ''),
                'fketdt' => (string) ($d->fketdt ?? ''),
                'frefcode' => $refCode,
                'frefpr' => $displayRef,
                'fqtyterinvoice' => (float) ($summary['fqtyterinvoice'] ?? 0),
                'fqtysisa_ref' => (float) ($summary['fqtysisa_ref'] ?? 0),
            ];
        })->values();
        $selectedSupplierCode = $returpenjualan->fsupplier;

        // Fetch all products for product mapping
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

        // Prepare the product map for frontend
        $productMap = $this->buildProductMap($products);

        // Pass the data to the view
        return view('returpenjualan.edit', [
            'customers' => $customers,
            'salesmans' => $salesmans,
            'warehouses' => $warehouses,
            'selectedSupplierCode' => $selectedSupplierCode, // Kirim kode supplier ke view
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'products' => $products,
            'productMap' => $productMap,
            'returpenjualan' => $returpenjualan,
            'displayFsono' => $this->formatDisplayTransactionNumber($returpenjualan->fsono ?? null, (string) ($returpenjualan->fincludeppn ?? '0') === '1'),
            'savedItems' => $savedItems,
            'ppnAmount' => (float) ($returpenjualan->famountpopajak ?? 0), // total PPN from DB
            'famountgross' => (float) ($returpenjualan->famountgross ?? 0),  // nilai Grand Total dari DB
            'famountso' => (float) ($returpenjualan->famountso ?? 0),  // nilai Grand Total dari DB
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'filterSalesmanId' => $request->query('filter_salesman_id'),
            'isUsageLocked' => ! empty($usageLockMessage),
            'usageLockMessage' => $usageLockMessage,
            'action' => 'delete',
        ]);
    }

    public function destroy($ftranmtid)
    {
        try {
            $deletedHeader = null;
            DB::transaction(function () use ($ftranmtid, &$deletedHeader) {
                $returpenjualan = Tranmt::findOrFail($ftranmtid);
                $deletedHeader = $returpenjualan;

                if ($message = $this->getPostedPeriodLockMessage($returpenjualan->fsodate, 'Retur ini')) {
                    throw new \RuntimeException($message);
                }

                if ($message = $this->getUsageLockMessage($returpenjualan)) {
                    throw new \RuntimeException($message);
                }

                $fsono = $returpenjualan->fsono;

                [$oldSoRestoreByReference, $oldSrjRestoreByReference] = $this->buildReturReferenceRestoreMaps($fsono);
                $this->restoreReturReferenceUsage($oldSoRestoreByReference, $oldSrjRestoreByReference);

                // 1. Delete details (trandt)
                DB::table('trandt')
                    ->where('fsono', $fsono)
                    ->delete();

                // 2. Delete stock records (trstockmt & trstockdt)
                $fstockmtno = str_replace('REJ.', 'REB.', $fsono);
                $stockHeader = DB::table('trstockmt')->where('fstockmtno', $fstockmtno)->first();

                if ($stockHeader) {
                    DB::table('trstockdt')
                        ->where('fstockmtno', $fstockmtno)
                        ->delete();
                    DB::table('trstockmt')->where('fstockmtid', $stockHeader->fstockmtid)->delete();
                }

                $this->deleteReturPenjualanJournalEntries($fsono);

                // 3. Delete header (tranmt)
                $returpenjualan->delete();
            });

            $displayNo = $this->formatDisplayTransactionNumber((string) ($deletedHeader->fsono ?? ''), (string) ($deletedHeader->fincludeppn ?? '0') === '1');
            return redirect()->route('returpenjualan.index')->with('success', 'Retur penjualan '.$displayNo.' berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->route('returpenjualan.index')->with('error', 'Retur penjualan belum bisa dihapus. Coba lagi.');
        }
    }



    private function getUsageLockMessage($header): ?string
    {
        return null;
    }

    private function normalizeDiscountInput($discInput): string
    {
        $value = trim((string) ($discInput ?? ''));
        if ($value === '') {
            return '0';
        }

        $value = preg_replace('/\s+/', '', $value) ?? '0';

        return $value === '' ? '0' : mb_substr($value, 0, 50);
    }

    private function syncReturPenjualanJournalEntries(
        string $fsono,
        Carbon $fsodate,
        string $kodeCabang,
        string $fcustno,
        float $subtotal,
        float $ppnAmount,
        float $grandTotal,
        string $userid
    ): void {
        $this->deleteReturPenjualanJournalEntries($fsono);

        $fjurnaltype = 'JRJ';
        $jurnalPrefix = sprintf('JV.REJ.%s.%s.', $kodeCabang, $fsodate->format('ym'));

        if (DB::getDriverName() === 'pgsql') {
            $lockKey = crc32('JURNAL|' . $fjurnaltype . '|' . $kodeCabang . '|' . $fsodate->format('Y-m'));
            DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);
            $lastJ = DB::table('jurnalmt')->where('fjurnalno', 'like', $jurnalPrefix . '%')
                ->selectRaw("MAX(CAST(split_part(fjurnalno, '.', 5) AS int)) AS lastno")->value('lastno');
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
        $now = now();

        $jurnalId = DB::table('jurnalmt')->insertGetId([
            'fbranchcode' => $kodeCabang,
            'fjurnalno' => $fjurnalno,
            'fjurnaltype' => $fjurnaltype,
            'fjurnaldate' => $fsodate,
            'fjurnalnote' => "Retur Penjualan $fsono kepada $fcustno",
            'fbalance' => round($grandTotal, 2),
            'fbalance_rp' => round($grandTotal, 2),
            'fdatetime' => $now,
            'fuserid' => $userid,
        ], 'fjurnalmtid');

        $jurnalDt = [
            [
                'fjurnalmtid' => $jurnalId,
                'fbranchcode' => $kodeCabang,
                'fjurnaltype' => $fjurnaltype,
                'fjurnalno' => $fjurnalno,
                'flineno' => 1,
                'faccount' => '41100',
                'fdk' => 'D',
                'fsubaccount' => $fcustno,
                'frefno' => $fsono,
                'frate' => 1.0,
                'famount' => round($subtotal, 2),
                'famount_rp' => round($subtotal, 2),
                'faccountnote' => 'Retur Penjualan',
                'fusercreate' => $userid,
                'fdatetime' => $now
            ],
            [
                'fjurnalmtid' => $jurnalId,
                'fbranchcode' => $kodeCabang,
                'fjurnaltype' => $fjurnaltype,
                'fjurnalno' => $fjurnalno,
                'flineno' => ($ppnAmount > 0 ? 3 : 2),
                'faccount' => '11300',
                'fdk' => 'K',
                'fsubaccount' => $fcustno,
                'frefno' => $fsono,
                'frate' => 1.0,
                'famount' => round($grandTotal, 2),
                'famount_rp' => round($grandTotal, 2),
                'faccountnote' => 'Piutang Usaha',
                'fusercreate' => $userid,
                'fdatetime' => $now
            ],
        ];

        if ($ppnAmount > 0) {
            $jurnalDt[] = [
                'fjurnalmtid' => $jurnalId,
                'fbranchcode' => $kodeCabang,
                'fjurnaltype' => $fjurnaltype,
                'fjurnalno' => $fjurnalno,
                'flineno' => 2,
                'faccount' => '21160',
                'fdk' => 'D',
                'fsubaccount' => $fcustno,
                'frefno' => $fsono,
                'frate' => 1.0,
                'famount' => round($ppnAmount, 2),
                'famount_rp' => round($ppnAmount, 2),
                'faccountnote' => 'PPN',
                'fusercreate' => $userid,
                'fdatetime' => $now
            ];
        }

        DB::table('jurnaldt')->insert($jurnalDt);
    }

    private function deleteReturPenjualanJournalEntries(string $fsono): void
    {
        $jurnalIds = DB::table('jurnaldt')
            ->where('frefno', $fsono)
            ->where('fjurnaltype', 'REJ')
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
