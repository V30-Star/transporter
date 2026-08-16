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
    private const DAILY_CREATE_LIMIT = 15;

    private function todayCreateCount(): int
    {
        return Tranmt::whereIn('ftrcode', ['REJ', 'RUJ'])
            ->whereBetween('fdatetime', [now()->startOfDay(), now()->endOfDay()])
            ->count();
    }

    private function hasReachedDailyCreateLimit(): bool
    {
        return $this->todayCreateCount() >= self::DAILY_CREATE_LIMIT;
    }

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

    private function getDefaultPpnTarif(): float
    {
        $val = DB::table('setini')->value('fppntarif');

        return ($val !== null && is_numeric($val) && (float) $val > 0) ? (float) $val : 11.0;
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

        if ($useSlash) {
            return str_replace('.', '/', $normalized);
        }

        return str_replace('/', '.', $normalized);
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
        $createLimitReached = $this->hasReachedDailyCreateLimit();

        // Ambil tahun-tahun yang tersedia dari data
        $availableYearsQuery = Tranmt::query()
            ->selectRaw('DISTINCT EXTRACT(YEAR FROM fsodate) as year')
            ->whereIn('ftrcode', ['REJ', 'RUJ'])
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
                ->whereIn('tranmt.ftrcode', ['REJ', 'RUJ'])
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
                    'fsono_display' => $this->formatDisplayTransactionNumber($row->fsono ?? null, (string) ($row->fapplyppn ?? '0') === '0' && (string) ($row->fincludeppn ?? '0') === '0'),
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
            'month',
            'createLimitReached'
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
        $query->where(function ($q) {
            $q->where('mt.fsono', 'like', 'INV.%')
              ->orWhere('mt.fsono', 'like', 'INV/%');
        });
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
            ->where(function ($q) {
                $q->where('mt.fsono', 'like', 'INV.%')
                  ->orWhere('mt.fsono', 'like', 'INV/%');
            })
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

    public function browse(Request $request)
    {
        $customerCode = trim((string) $request->input('customer_code', ''));

        $query = DB::table('tranmt as mt')
            ->leftJoin('mscustomer as cust', 'mt.fcustno', '=', 'cust.fcustomercode')
            ->whereIn('mt.ftrcode', ['REJ', 'RUJ'])
            ->select(
                'mt.ftranmtid',
                'mt.fbranchcode',
                'mt.fsono',
                'mt.frefno',
                'mt.fsodate',
                'mt.fcustno',
                'cust.fcustomername',
                'mt.famountso',
                'mt.fket'
            );

        $this->applyBranchVisibilityScope($query, 'mt.fbranchcode');

        if ($customerCode !== '') {
            $query->whereRaw("TRIM(COALESCE(mt.fcustno, '')) = ?", [$customerCode]);
        }

        $recordsTotal = (clone $query)->count();

        if ($request->filled('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('mt.fsono', 'ilike', "%{$search}%")
                    ->orWhere('mt.frefno', 'ilike', "%{$search}%")
                    ->orWhere('cust.fcustomername', 'ilike', "%{$search}%")
                    ->orWhere('mt.fcustno', 'ilike', "%{$search}%")
                    ->orWhere('mt.fket', 'ilike', "%{$search}%");
            });
        }

        $recordsFiltered = $query->count();

        $orderColumn = $request->input('order_column', 'fsodate');
        $orderDir    = $request->input('order_dir', 'desc');

        $allowedColumns = ['fsono', 'frefno', 'fsodate', 'fcustomername', 'famountso'];
        if (in_array($orderColumn, $allowedColumns)) {
            if ($orderColumn === 'fcustomername') {
                $query->orderBy('cust.fcustomername', $orderDir);
            } else {
                $query->orderBy('mt.' . $orderColumn, $orderDir);
            }
        } else {
            $query->orderBy('mt.fsodate', 'desc')->orderBy('mt.fsono', 'desc');
        }

        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);

        $data = $query->skip($start)->take($length)->get()->map(function ($row) {
            return [
                'ftranmtid'     => $row->ftranmtid,
                'fbranchcode'   => trim((string) ($row->fbranchcode ?? '')),
                'fsono'         => trim((string) ($row->fsono ?? '')),
                'frefno'        => trim((string) ($row->frefno ?? '')),
                'fsodate'       => $row->fsodate
                    ? \Carbon\Carbon::parse($row->fsodate)->format('d-m-Y')
                    : '',
                'fcustno'       => trim((string) ($row->fcustno ?? '')),
                'fcustomername' => trim((string) ($row->fcustomername ?? '')),
                'famountso'     => (float) ($row->famountso ?? 0),
                'fket'          => trim((string) ($row->fket ?? '')),
            ];
        });

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => (int) $recordsTotal,
            'recordsFiltered' => (int) $recordsFiltered,
            'data'            => $data,
        ]);
    }

    public function productHistory(Request $request)
    {
        $customerCode = trim((string) $request->input('fcustno', ''));
        $productCode = trim((string) $request->input('fprdcode', ''));

        if ($customerCode === '') {
            return response()->json([
                'message' => 'Customer wajib dipilih terlebih dahulu.',
                'data' => [],
            ], 422);
        }

        if ($productCode === '' || str_starts_with(strtoupper($productCode), 'UM')) {
            $rows = DB::table('trsisadp_penjualan as s')
                ->join('tranmt as m', 'm.fsono', '=', 's.fsono')
                ->leftJoin('trandt as d', function ($j) {
                    $j->on('d.fsono', '=', 's.fsono')
                      ->where('d.fprdcode', '=', 'UM');
                })
                ->where('m.ftrcode', '=', 'INV')
                ->whereRaw('TRIM(COALESCE(s.fcustno, \'\')) = ?', [$customerCode])
                ->where('s.fsisadp', '>', 0)
                ->orderByDesc('s.fsodate')
                ->orderByDesc('s.fsono')
                ->select(
                    's.*',
                    'd.ftrandtid as detail_id',
                    DB::raw('COALESCE(d.fqty, 1) as ref_qty'),
                    DB::raw('COALESCE(d.fqtyremain, d.fqty, 1) as remain_qty'),
                    DB::raw("COALESCE(d.fsatuan, 'PCS') as ref_satuan"),
                    DB::raw("COALESCE(d.fnoacak::text, '') as ref_noacak")
                )
                ->get();

            return response()->json([
                'data' => $rows->map(function ($row) {
                    $docQty = (float) ($row->remain_qty ?? $row->ref_qty ?? 1);
                    return [
                        'fsono' => (string) ($row->fsono ?? ''),
                        'fsodate' => ! empty($row->fsodate)
                            ? Carbon::parse($row->fsodate)->format('d/m/Y')
                            : '-',
                        'fcustomername' => (string) ($row->fcustomername ?? ''),
                        'fqty' => $docQty,
                        'frefdtno' => (string) ($row->fsono ?? ''),
                        'frefnoacak' => trim((string) ($row->ref_noacak ?? '')),
                        'faktur_qty' => $docQty,
                        'qty_faktur' => $docQty,
                        'qty_asal' => (float) ($row->ref_qty ?? 1),
                        'fqtyremain' => $docQty,
                        'maxqty' => $docQty,
                        'fsatuan' => (string) ($row->ref_satuan ?? 'PCS'),
                        'fprice' => (float) ($row->fsisadp ?? $row->famountsonet ?? 0),
                        'famount' => (float) ($row->fsisadp ?? $row->famountsonet ?? 0),
                        'ftotprice' => (float) ($row->fsisadp ?? $row->famountsonet ?? 0),
                        'fsisadp' => (float) ($row->fsisadp ?? 0),
                        'fdesc' => '',
                    ];
                })->values(),
            ]);
        }

        $rows = DB::table('trandt as d')
            ->join('tranmt as h', 'h.fsono', '=', 'd.fsono')
            ->leftJoin('mscustomer as c', 'c.fcustomercode', '=', 'h.fcustno')
            ->where('h.fcustno', $customerCode)
            ->where('d.fprdcode', $productCode)
            ->where(function ($q) {
                $q->where('h.fsono', 'like', 'INV.%')
                  ->orWhere('h.fsono', 'like', 'INV/%');
            })
            ->orderByDesc('h.fsodate')
            ->orderByDesc('h.fsono')
            ->get([
                'h.fsono',
                'h.fsodate',
                'c.fcustomername',
                'd.fqty',
                'd.ftrandtid as frefdtno',
                DB::raw("COALESCE(NULLIF(TRIM(d.fnoacak::text), ''), '') as frefnoacak"),
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
                    'frefdtno' => $row->frefdtno,
                    'frefnoacak' => trim((string) ($row->frefnoacak ?? '')),
                    'faktur_qty' => (float) ($row->fqty ?? 0),
                    'qty_faktur' => (float) ($row->fqty ?? 0),
                    'qty_asal' => (float) ($row->fqty ?? 0),
                    'fsatuan' => (string) ($row->fsatuan ?? ''),
                    'fprice' => (float) ($row->fprice ?? 0),
                    'famount' => (float) ($row->famount ?? 0),
                    'fdesc' => (string) ($row->fdesc ?? ''),
                ];
            })->values(),
        ]);
    }

    private function latestSalesHistory(string $customerCode, string $productCode, string $unit): ?object
    {
        return DB::table('tranmt as m')
            ->join('trandt as d', 'm.fsono', '=', 'd.fsono')
            ->where(function ($q) {
                $q->where('m.fsono', 'like', 'INV.%')
                  ->orWhere('m.fsono', 'like', 'INV/%');
            })
            ->whereRaw('TRIM(d.fprdcode) = ?', [$productCode])
            ->whereRaw('TRIM(m.fcustno) = ?', [$customerCode])
            ->whereRaw('TRIM(d.fsatuan) = ?', [$unit])
            ->orderByDesc('m.fsodate')
            ->orderByDesc('m.fsono')
            ->select('d.fprice', 'd.fsalesnet', 'd.fpricenet', 'd.fsatuan', 'd.fdisc')
            ->first();
    }

    public function productPrice(Request $request)
    {
        $customerCode = trim((string) $request->input('fcustno', ''));
        $productCode = trim((string) $request->input('fprdcode', ''));
        $unit = trim((string) $request->input('fsatuan', ''));

        if ($customerCode === '' || $productCode === '') {
            return response()->json([
                'price' => 0,
                'discount' => '0',
                'source' => 'default',
            ]);
        }

        $history = $unit !== ''
            ? $this->latestSalesHistory($customerCode, $productCode, $unit)
            : null;

        if (! $history) {
            $history = DB::table('tranmt as m')
                ->join('trandt as d', 'm.fsono', '=', 'd.fsono')
                ->where(function ($q) {
                    $q->where('m.fsono', 'like', 'INV.%')
                      ->orWhere('m.fsono', 'like', 'INV/%');
                })
                ->whereRaw('TRIM(d.fprdcode) = ?', [$productCode])
                ->whereRaw('TRIM(m.fcustno) = ?', [$customerCode])
                ->orderByDesc('m.fsodate')
                ->orderByDesc('m.fsono')
                ->select('d.fprice', 'd.fsalesnet', 'd.fpricenet', 'd.fsatuan', 'd.fdisc')
                ->first();
        }

        if ($history) {
            $effectivePrice = (float) (($history->fsalesnet ?? 0) > 0 ? $history->fsalesnet : ($history->fprice ?? 0));
            return response()->json([
                'price' => $effectivePrice,
                'unit' => trim((string) ($history->fsatuan ?? $unit)),
                'discount' => (string) ($history->fdisc ?? '0'),
                'source' => 'history',
            ]);
        }

        $product = DB::table('msprd')->where('fprdcode', $productCode)->first();
        $price = 0.0;
        if ($product) {
            $price = (float) ($product->fhargajuallevel1 ?? $product->fhpp ?? 0);
        }

        return response()->json([
            'price' => $price,
            'discount' => '0',
            'source' => 'master',
        ]);
    }

    public function items($id)
    {
        $header = DB::table('tranmt')
            ->leftJoin('mscustomer', 'mscustomer.fcustomercode', '=', 'tranmt.fcustno')
            ->where('tranmt.ftranmtid', $id)
            ->where(function ($q) {
                $q->where('tranmt.fsono', 'like', 'INV.%')
                  ->orWhere('tranmt.fsono', 'like', 'INV/%');
            })
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
                'trandt.fsalesnet as fsalesnet',
                'trandt.fpricenet as fpricenet',
                DB::raw("COALESCE(NULLIF(TRIM(trandt.fdisc), ''), '0') as fdisc"),
                'trandt.fdesc',
                'trandt.frefso',
                'trandt.frefsrj',
                DB::raw("COALESCE(NULLIF(TRIM(trandt.fnoacak::text), ''), '') as frefnoacak"),
                'm.fsatuankecil',
                'm.fsatuanbesar',
                'm.fsatuanbesar2',
            ])
            ->orderBy('trandt.ftrandtid')
            ->get();

        return response()->json([
            'header' => [
                'ftranmtid' => $header->ftranmtid,
                'fsono' => $header->fsono,
                'frefno' => trim((string) ($header->frefno ?? '')),
                'fdisplayref' => trim((string) ($header->fsono ?? '')),
                'fcustno' => trim((string) ($header->fcustno ?? '')),
                'fcustomername' => trim((string) ($header->fcustomername ?? '')),
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
                    'faktur_qty' => (float) ($item->fqty ?? 0),
                    'qty_faktur' => (float) ($item->fqty ?? 0),
                    'qty_asal' => (float) ($item->fqty ?? 0),
                    'fqtyremain' => max(0, (float) ($item->fqtyremain ?? 0)),
                    'maxqty' => max(0, (float) ($item->fqtyremain ?? 0)),
                    'fsatuan' => trim((string) ($item->fsatuan ?? '')),
                    'fdisplayunit' => trim((string) ($item->fsatuan ?? '')),
                    'fprice' => (float) ($item->fprice ?? 0),
                    'fdisc' => $this->normalizeDiscountInput($item->fdisc ?? 0),
                    'fdesc' => (string) ($item->fdesc ?? ''),
                    'fnouref' => trim((string) ($header->fsono ?? '')),
                    'frefpr' => trim((string) ($header->fsono ?? '')),
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

    private function getCustomerAdvanceWarningMap(): array
    {
        $documentsByCustomer = DB::table('trsisadp_penjualan as s')
            ->join('tranmt as m', 'm.fsono', '=', 's.fsono')
            ->leftJoin('trandt as d', function ($j) {
                $j->on('d.fsono', '=', 's.fsono')
                  ->where('d.fprdcode', '=', 'UM');
            })
            ->where('m.ftrcode', '=', 'INV')
            ->selectRaw('TRIM(COALESCE(s.fcustno, \'\')) as fcustno')
            ->addSelect([
                's.fsono',
                's.fsodate',
                's.fsisadp',
                DB::raw('COALESCE(d.fqty, 1) as fqty'),
                DB::raw('COALESCE(d.fqty, 1) as qty_asal'),
                DB::raw("COALESCE(d.fsatuan, 'PCS') as fsatuan"),
            ])
            ->where('s.fsisadp', '>', 0)
            ->orderBy('s.fsodate')
            ->orderBy('s.fsono')
            ->get()
            ->map(fn ($doc) => [
                'fcustno'  => trim((string) ($doc->fcustno ?? '')),
                'fsono'    => trim((string) ($doc->fsono ?? '')),
                'fsodate'  => $doc->fsodate,
                'fsisadp'  => (float) ($doc->fsisadp ?? 0),
                'fqty'     => (float) ($doc->fqty ?? 1),
                'qty_asal' => (float) ($doc->qty_asal ?? 1),
                'fsatuan'  => (string) ($doc->fsatuan ?? 'PCS'),
            ])
            ->filter(fn ($doc) => $doc['fcustno'] !== '' && $doc['fsono'] !== '')
            ->groupBy('fcustno');

        return DB::table('trsisadp_penjualan as s')
            ->join('tranmt as m', 'm.fsono', '=', 's.fsono')
            ->where('m.ftrcode', '=', 'INV')
            ->selectRaw('TRIM(COALESCE(s.fcustno, \'\')) as fcustno')
            ->selectRaw('SUM(COALESCE(s.fsisadp, 0)) as total_remain')
            ->where('s.fsisadp', '>', 0)
            ->groupBy(DB::raw('TRIM(COALESCE(s.fcustno, \'\'))'))
            ->get()
            ->filter(fn($row) => trim((string) ($row->fcustno ?? '')) !== '')
            ->mapWithKeys(function ($row) use ($documentsByCustomer) {
                $customerCode = trim((string) ($row->fcustno ?? ''));
                $remainRp = (float) ($row->total_remain ?? 0);

                return [
                    $customerCode => [
                        'message' => $remainRp > 0
                            ? 'Customer ini memiliki Uang Muka (UM) sisa ' . number_format($remainRp, 2, ',', '.') . '.'
                            : 'Customer ini memiliki Uang Muka (UM).',
                        'documents' => $documentsByCustomer->get($customerCode, collect())->values()->all(),
                    ],
                ];
            })
            ->all();
    }

    private function getCustomerOutstandingDpDocument(string $customerCode): ?object
    {
        $customerCode = trim($customerCode);
        if ($customerCode === '') {
            return null;
        }

        return DB::table('trsisadp_penjualan as s')
            ->join('tranmt as m', 'm.fsono', '=', 's.fsono')
            ->leftJoin('trandt as d', function ($j) {
                $j->on('d.fsono', '=', 's.fsono')
                  ->where('d.fprdcode', '=', 'UM');
            })
            ->where('m.ftrcode', '=', 'INV')
            ->whereRaw('TRIM(COALESCE(s.fcustno, \'\')) = ?', [$customerCode])
            ->where('s.fsisadp', '>', 0)
            ->orderBy('s.fsodate')
            ->orderBy('s.fsono')
            ->select([
                's.*',
                DB::raw('COALESCE(d.fqty, 1) as fqty'),
                DB::raw('COALESCE(d.fqty, 1) as qty_asal'),
                DB::raw("COALESCE(d.fsatuan, 'PCS') as fsatuan"),
            ])
            ->first();
    }

    private function sanitizeReturReferences(array &$frefso, array $frefsrj): void
    {
        foreach ($frefsrj as $index => $srjDocNo) {
            if (trim((string) $srjDocNo) !== '') {
                $frefso[$index] = '';
            }
        }
    }

    private function validateReturProductReferences(array $itemCodes, array $frefso, array $frefsrj, array $frefdtno = [], array $frefpr = []): void
    {
        foreach ($itemCodes as $index => $code) {
            $code = strtoupper(trim((string) $code));
            if ($code === '' || $code === 'UM') {
                continue;
            }

            $refSo = trim((string) ($frefso[$index] ?? ''));
            $refSrj = trim((string) ($frefsrj[$index] ?? ''));
            $refDtNo = trim((string) ($frefdtno[$index] ?? ''));
            $refPr = trim((string) ($frefpr[$index] ?? ''));

            if ($refSo === '' && $refSrj === '' && $refDtNo === '' && $refPr === '') {
                throw ValidationException::withMessages([
                    "fitemcode.{$index}" => "Produk {$code} wajib memiliki no. referensi SRJ atau Faktur Penjualan.",
                ]);
            }
        }
    }

    private function validateSubmittedReturReferenceQty(array $itemCodes, array $qtys, array $frefso, array $frefsrj, array $frefnoacaks = []): void
    {
        foreach ($itemCodes as $index => $code) {
            $code = trim((string) $code);
            $returnQty = (float) ($qtys[$index] ?? 0);
            if ($code === '' || strtoupper($code) === 'UM' || $returnQty <= 0) {
                continue;
            }

            $srjNo = trim((string) ($frefsrj[$index] ?? ''));
            $fakturNo = trim((string) ($frefso[$index] ?? ''));
            $source = $srjNo !== '' ? 'SRJ' : ($fakturNo !== '' ? 'INV' : '');
            $docNo = $srjNo !== '' ? $srjNo : $fakturNo;
            if ($source === '') {
                continue;
            }

            $referenceDetail = $this->resolveReturReferenceSourceDetail($source, $docNo, $code, $frefnoacaks[$index] ?? null);
            if (! $referenceDetail) {
                throw ValidationException::withMessages([
                    "fqty.{$index}" => "Row " . ($index + 1) . ": Referensi Faktur/SRJ tidak ditemukan.",
                ]);
            }

            $refQty = (float) ($referenceDetail->fqty ?? 0);
            $returnQtyForCompare = $returnQty;
            $refQtyKecil = (float) ($referenceDetail->fqtykecil ?? 0);
            if ($refQty > 0 && $refQtyKecil > 0) {
                $returnQtyForCompare = $returnQty * ($refQtyKecil / $refQty);
                $refQty = $refQtyKecil;
            }

            if ($returnQtyForCompare - $refQty > 0.000001) {
                throw ValidationException::withMessages([
                    "fqty.{$index}" => "Row " . ($index + 1) . ": Qty Retur ({$returnQty}) melebihi Qty Referensi (" . (float) ($referenceDetail->fqty ?? 0) . ").",
                ]);
            }
        }
    }

    private function validateAdvancePaymentPriceAgainstReference(array $codes, array $frefsrj, array $frefso, array $frefdtno, array $prices, string $customerCode): ?string
    {
        $customerCode = trim($customerCode);
        foreach ($codes as $i => $code) {
            $c = trim((string) $code);
            if (! str_starts_with(strtoupper($c), 'UM')) {
                continue;
            }

            $refno = trim((string) ($frefsrj[$i] ?? $frefso[$i] ?? $frefdtno[$i] ?? ''));
            $inputPrice = abs((float) ($prices[$i] ?? 0));

            if ($refno !== '') {
                $dp = DB::table('trsisadp_penjualan')
                    ->whereRaw('TRIM(fsono) = ?', [$refno])
                    ->when($customerCode !== '', fn($q) => $q->whereRaw('TRIM(COALESCE(fcustno, \'\')) = ?', [$customerCode]))
                    ->first();

                if ($dp) {
                    $maxAllowed = (float) ($dp->fsisadp ?? $dp->famountsonet ?? 0);
                    if ($inputPrice - $maxAllowed > 0.0001) {
                        $formattedInput = number_format($inputPrice, 2, ',', '.');
                        $formattedMax = number_format($maxAllowed, 2, ',', '.');
                        $refType = str_starts_with(strtoupper($refno), 'RUJ') ? 'RUJ' : 'UMJ';
                        return "Harga Uang Muka (Rp {$formattedInput}) tidak boleh melebihi sisa Uang Muka pada referensi {$refType} {$refno} (Rp {$formattedMax}).";
                    }
                }
            }
        }

        return null;
    }

    private function validateAdvancePaymentQtyAgainstReference(
        array $codes,
        array $frefsrj,
        array $frefso,
        array $frefdtno,
        array $qtys,
        string $customerCode,
        ?string $exceptFsono = null
    ): ?string {
        $customerCode = trim($customerCode);
        $usageByRef = [];

        foreach ($codes as $i => $code) {
            $c = trim((string) $code);
            if (! str_starts_with(strtoupper($c), 'UM')) {
                continue;
            }

            $refno = trim((string) ($frefsrj[$i] ?? $frefso[$i] ?? $frefdtno[$i] ?? ''));
            if ($refno === '') {
                continue;
            }

            $inputQty = abs((float) ($qtys[$i] ?? 0));
            $usageByRef[$refno] = ($usageByRef[$refno] ?? 0.0) + $inputQty;
        }

        foreach ($usageByRef as $refno => $totalInputQty) {
            $refDetail = DB::table('trandt')
                ->whereRaw('TRIM(fsono) = ?', [$refno])
                ->where('fprdcode', 'UM')
                ->first();

            $maxQty = null;
            if ($refDetail) {
                $maxQty = (float) ($refDetail->fqtyremain ?? $refDetail->fqty ?? 0);
            } else {
                $dp = DB::table('trsisadp_penjualan')
                    ->whereRaw('TRIM(fsono) = ?', [$refno])
                    ->first();
                if ($dp) {
                    $maxQty = 1.0;
                }
            }

            if (! empty($exceptFsono)) {
                $existingUsage = (float) DB::table('trandt')
                    ->where('fsono', $exceptFsono)
                    ->where('fprdcode', 'UM')
                    ->where(function ($q) use ($refno) {
                        $q->whereRaw('TRIM(frefsrj) = ?', [$refno])
                          ->orWhereRaw('TRIM(frefso) = ?', [$refno])
                          ->orWhereRaw('TRIM(frefdtno) = ?', [$refno]);
                    })
                    ->sum('fqty');
                if ($maxQty !== null) {
                    $maxQty += $existingUsage;
                }
            }

            if ($maxQty !== null && $maxQty > 0 && ($totalInputQty - $maxQty > 0.00001)) {
                $formattedInput = number_format($totalInputQty, 2, ',', '.');
                $formattedMax = number_format($maxQty, 2, ',', '.');
                $refType = str_starts_with(strtoupper($refno), 'RUJ') ? 'RUJ' : 'UMJ';
                return "Qty Uang Muka ({$formattedInput}) tidak boleh melebihi sisa qty pada referensi {$refType} {$refno} (Maksimal {$formattedMax}).";
            }
        }

        return null;
    }

    private function findReturReferenceStat(array $stats, string $doc, string $code, string $refNoAcak): ?array
    {
        $key = $this->buildReferenceUsageKey($doc, $code, $refNoAcak);
        if (isset($stats[$key])) {
            return $stats[$key];
        }
        $doc = trim($doc);
        $code = trim($code);
        foreach ($stats as $stat) {
            if (trim((string) ($stat['ref_doc'] ?? '')) === $doc && trim((string) ($stat['product_code'] ?? '')) === $code) {
                return $stat;
            }
        }
        return null;
    }

    private function validateSourcePriceForRows(
        array $codes,
        array $prices,
        array $refSos,
        array $refSrjs,
        array $refDtNos = [],
        array $refNoAcaks = []
    ): \Illuminate\Support\MessageBag {
        $errors = new \Illuminate\Support\MessageBag;
        $tolerance = 0.01;

        $cleanCodes = array_values(array_unique(array_filter(array_map(fn($code) => trim((string) $code), $codes))));
        if (empty($cleanCodes)) {
            return $errors;
        }

        $products = DB::table('msprd')
            ->whereIn('fprdcode', $cleanCodes)
            ->pluck('fprdname', 'fprdcode');

        $soDocs = [];
        $srjDocs = [];

        foreach ($codes as $i => $codeRaw) {
            $code = trim((string) ($codeRaw ?? ''));
            if ($code === '' || str_starts_with(strtoupper($code), 'UM')) {
                continue;
            }
            $soDoc = trim((string) ($refSos[$i] ?? ''));
            $srjDoc = trim((string) ($refSrjs[$i] ?? ''));
            $refDtNo = trim((string) ($refDtNos[$i] ?? ''));
            if ($srjDoc === '' && $this->isDocumentSrj($refDtNo)) {
                $srjDoc = $refDtNo;
            } elseif ($soDoc === '' && ($this->isDocumentFaktur($refDtNo) || (!str_starts_with($refDtNo, 'SRJ') && $refDtNo !== ''))) {
                $soDoc = $refDtNo;
            }

            if ($srjDoc !== '') {
                $srjDocs[] = $srjDoc;
            } elseif ($soDoc !== '') {
                $soDocs[] = $soDoc;
            }
        }

        $soDocs = array_values(array_unique(array_filter($soDocs)));
        $srjDocs = array_values(array_unique(array_filter($srjDocs)));

        $soStats = empty($soDocs) ? [] : $this->getReturReferenceStats('SO', $soDocs);
        $srjStats = empty($srjDocs) ? [] : $this->getReturReferenceStats('SRJ', $srjDocs);

        foreach ($codes as $i => $codeRaw) {
            $code = trim((string) ($codeRaw ?? ''));
            $inputPrice = abs((float) ($prices[$i] ?? 0));
            if ($code === '' || str_starts_with(strtoupper($code), 'UM')) {
                continue;
            }

            $soDoc = trim((string) ($refSos[$i] ?? ''));
            $srjDoc = trim((string) ($refSrjs[$i] ?? ''));
            $refDtNo = trim((string) ($refDtNos[$i] ?? ''));
            $refNoAcak = $this->normalizeReferenceRandomNumbers($refNoAcaks[$i] ?? null) ?? '';

            if ($srjDoc === '' && $this->isDocumentSrj($refDtNo)) {
                $srjDoc = $refDtNo;
            } elseif ($soDoc === '' && ($this->isDocumentFaktur($refDtNo) || (!str_starts_with($refDtNo, 'SRJ') && $refDtNo !== ''))) {
                $soDoc = $refDtNo;
            }

            $maxPrice = null;
            $refType = '';
            $refDoc = '';
            if ($srjDoc !== '') {
                $stat = $this->findReturReferenceStat($srjStats, $srjDoc, $code, $refNoAcak);
                if ($stat) {
                    $maxPrice = (float) ($stat['source_price'] ?? 0);
                    $refType = 'SRJ';
                    $refDoc = $srjDoc;
                }
            } elseif ($soDoc !== '') {
                $stat = $this->findReturReferenceStat($soStats, $soDoc, $code, $refNoAcak);
                if ($stat) {
                    $maxPrice = (float) ($stat['source_price'] ?? 0);
                    $refType = 'Faktur';
                    $refDoc = $soDoc;
                }
            }

            if ($maxPrice !== null && $maxPrice > 0 && $inputPrice > $maxPrice + $tolerance) {
                $formattedMax = number_format($maxPrice, 2, ',', '.');
                $prodName = $products[$code] ?? $code;
                $errors->add("fprice.$i", "Harga item {$prodName} melebihi harga referensi {$refType} ({$refDoc}). Maksimal {$formattedMax}.");
            }
        }

        return $errors;
    }

    private function isDocumentSrj(?string $docNo): bool
    {
        $docNo = strtoupper(trim((string) ($docNo ?? '')));
        return strpos($docNo, 'SRJ.') === 0 || strpos($docNo, 'SJ.') === 0 || strpos($docNo, 'SRJ/') === 0 || strpos($docNo, 'SJ/') === 0;
    }

    private function isDocumentFaktur(?string $docNo): bool
    {
        $docNo = strtoupper(trim((string) ($docNo ?? '')));
        return strpos($docNo, 'INV.') === 0 || strpos($docNo, 'INV/') === 0 || strpos($docNo, 'SO.') === 0 || strpos($docNo, 'SO/') === 0;
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
                ->first(['fsatuan', 'fqty', 'fqtykecil', 'fnoacak', 'fprice']);
        }

        if (in_array($sourceCode, ['S', 'SO', 'INV'], true)) {
            return DB::table('trandt')
                ->where('fsono', $docNo)
                ->where('fprdcode', $productCode)
                ->when($normalizedRefNoAcak !== null, fn ($query) => $query->where('fnoacak', $normalizedRefNoAcak))
                ->orderBy('ftrandtid')
                ->first(['fsatuan', 'fqty', 'fqtykecil', 'fnoacak', 'fprice']);
        }

        return null;
    }

    private function resolveReturFjatuhtempo(array $detailRows): ?string
    {
        $invRefs = collect($detailRows)
            ->map(fn ($row) => trim((string) ($row['frefso'] ?? '')))
            ->filter()
            ->unique()
            ->values();
        $srjRefs = collect($detailRows)
            ->map(fn ($row) => trim((string) ($row['frefsrj'] ?? '')))
            ->filter()
            ->unique()
            ->values();

        if ($invRefs->isEmpty() && $srjRefs->isEmpty()) {
            return null;
        }

        $query = DB::table('tranmt as m')
            ->where('m.ftrcode', 'INV')
            ->whereNotNull('m.fjatuhtempo');

        if ($invRefs->isNotEmpty()) {
            $query->whereIn('m.fsono', $invRefs->all());
        }

        if ($srjRefs->isNotEmpty()) {
            $query->orWhereIn('m.fsono', function ($q) use ($srjRefs) {
                $q->select('d.fsono')
                    ->from('trandt as d')
                    ->whereIn('d.frefsrj', $srjRefs->all());
            });
        }

        return $query->max('m.fjatuhtempo');
    }

    private function generateInvoiceCode(?Carbon $onDate = null, ?string $branchCode = null, bool $hasPpn = true, bool $isUm = false): string
    {
        $date = $onDate ?: now();
        $branchCode = trim((string) ($branchCode ?: 'NA')) ?: 'NA';
        $trCode = $isUm ? 'RUJ' : 'REJ';
        $sep = $hasPpn ? '.' : '/';
        $prefix = sprintf('%s%s%s%s%s%s', $trCode, $sep, $branchCode, $sep, $date->format('y') . $date->format('m'), $sep);

        if (DB::getDriverName() === 'pgsql') {
            $last = DB::table('tranmt')
                ->where('fsono', 'like', "{$prefix}%")
                ->selectRaw("MAX(CAST(SUBSTRING(fsono FROM '([0-9]+)$') AS int)) AS lastno")
                ->value('lastno');

            $next = (int) $last + 1;
        } else {
            $lastCode = DB::table('tranmt')
                ->where('fsono', 'like', "{$prefix}%")
                ->orderByDesc('fsono')
                ->value('fsono');

            $next = 1;
            if ($lastCode && ($pos = max((int) strrpos($lastCode, '.'), (int) strrpos($lastCode, '/'))) !== false && $pos > 0) {
                $next = ((int) substr($lastCode, $pos + 1)) + 1;
            }
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function print(string $fsono)
    {
        $fsono = trim($fsono);

        // Header: find by SO code (string)
        $hdr = DB::table('tranmt')
            ->leftJoin('mscustomer as c', 'c.fcustomercode', '=', 'tranmt.fcustno')
            ->leftJoin('mssalesman as s', 's.fsalesmancode', '=', 'tranmt.fsalesman')
            ->leftJoin('mscabang as b', 'b.fcabangkode', '=', 'tranmt.fbranchcode')
            ->where(function ($q) use ($fsono) {
                if (is_numeric($fsono)) {
                    $q->where('tranmt.ftranmtid', (int) $fsono);
                }
                $slash = str_replace('.', '/', $fsono);
                $dot = str_replace('/', '.', $fsono);
                $q->orWhere('tranmt.fsono', $fsono)
                  ->orWhere('tranmt.fsono', $slash)
                  ->orWhere('tranmt.fsono', $dot);
            })
            ->first([
                'tranmt.*',
                'c.fcustomername as customer_name',
                'c.faddress as customer_address',
                'b.fcabangname as cabang_name',
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
            ->where('trandt.fsono', $hdr->fsono)
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
            'displayFsono' => $this->formatDisplayTransactionNumber($hdr->fsono ?? null, (string) ($hdr->fapplyppn ?? '0') === '0' && (string) ($hdr->fincludeppn ?? '0') === '0'),
            'fmt' => $fmt,
            'company_name' => config('app.company_name', 'PT. DEMO VERSION'),
            'company_city' => config('app.company_city', 'Tangerang'),
        ]);
    }

    public function create(Request $request)
    {
        if ($this->hasReachedDailyCreateLimit()) {
            return redirect()
                ->route('returpenjualan.index')
                ->with('create_limit_exceeded', true);
        }

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
        )
            ->whereRaw("COALESCE(TRIM(CAST(fnonactive AS TEXT)), '0') != '1'")
            ->orderBy('fprdname')
            ->get();

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
            'customerAdvanceWarnings' => $this->getCustomerAdvanceWarningMap(),
            'defaultPpnTarif' => $this->getDefaultPpnTarif(),
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'filterSalesmanId' => $request->query('filter_salesman_id'),
        ]);
    }

    public function store(Request $request)
    {
        if ($this->hasReachedDailyCreateLimit()) {
            return redirect()
                ->route('returpenjualan.index')
                ->with('create_limit_exceeded', true);
        }

        try {
            $request->validate([
                'fsono' => [
                    'nullable',
                    'string',
                    'max:50',
                    function ($attribute, $value, $fail) use ($request) {
                        if (! $request->boolean('auto_generate', true) && empty(trim((string) $value))) {
                            $fail('No. Transaksi Retur Penjualan wajib diisi jika Auto tidak dicentang.');
                        }
                    },
                ],
                'fsodate' => ['required', 'date'],
                'fcustno' => ['required', 'string', 'max:10'],
                'ffrom' => ['required', 'string', 'max:30'],
                'fitemcode' => ['required', 'array', 'min:1'],
                'fitemcode.*' => ['nullable', 'string', 'max:30'],
                'fqty' => ['required', 'array'],
                'fqty.*' => ['numeric', 'min:0'],
                'fprice' => ['required', 'array'],
                'fprice.*' => ['numeric', 'min:0'],
                'frefcode' => ['nullable', 'array'],
                'frefcode.*' => ['nullable', 'string'],
                'frefcode_global' => ['nullable', 'string', 'in:SO,SRJ,UM,INV,REJ,RUJ'],
                'frefso' => ['nullable'],
                'frefsrj' => ['nullable'],
                'fnoacak' => ['nullable', 'array'],
                'fnoacak.*' => ['nullable', 'regex:/^[1-9]{3}$/'],
                'frefnoacak' => ['nullable', 'array'],
                'frefnoacak.*' => ['nullable', 'regex:/^\d{3}$/'],
            ], [
                'ffrom.required' => 'Gudang wajib diisi.',
                'fcustno.required' => 'Customer wajib diisi.',
                'fsodate.required' => 'Tanggal transaksi wajib diisi.',
                'fitemcode.required' => 'Minimal 1 item.',
            ]);

            $fsodate = Carbon::parse($request->fsodate);
            $this->ensureCreateDateWithinEditPeriod($fsodate);
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                $firstError = collect($e->errors())->flatten()->first();
                return response()->json([
                    'message' => $firstError ?: 'Retur penjualan belum bisa disimpan. Cek data.',
                    'errors' => $e->errors(),
                ], 422);
            }
            return back()->withInput()->withErrors($e->errors());
        }

        // 2. INISIALISASI
        $fsodate = Carbon::parse($request->fsodate);
        $fincludeppn = $request->has('fincludeppn') || $request->input('fincludeppn') == '1' ? '1' : '0';
        $fapplyppn = '0'; // PPN Retur Penjualan selalu Exclude (0)
        $defaultPpnTarif = $this->getDefaultPpnTarif();
        $ppnPersen = (float) $request->input('fppnpersen', $defaultPpnTarif);
        if ($ppnPersen <= 0) {
            $ppnPersen = $defaultPpnTarif;
        }
        $userid = mb_substr(auth('sysuser')->user()->fname ?? 'admin', 0, 10);
        $now = now();
        $fcurrency = $request->input('fcurrency', 'IDR');
        $frate = (float) $request->input('frate', 1);
        $typeSales = (int) $request->input('ftypesales', 0);

        // 3. ARRAY INPUT
        $itemCodes = $request->input('fitemcode', []);
        $itemDescs = $request->input('fdesc', []);
        $satuans = $request->input('fsatuan', []);
        $qtys = $request->input('fqty', []);
        $prices = $request->input('fprice', []);
        $discs = $request->input('fdisc', []);

        // FREFCODE & REFERENCES
        $frefcodes = $request->input('frefcode', []);
        $frefso = $request->input('frefso', []);
        $frefsrj = $request->input('frefsrj', []);
        $frefdtno = $request->input('frefdtno', []);
        $frefpr = $request->input('frefpr', []);
        $this->sanitizeReturReferences($frefso, $frefsrj);
        $this->validateReturProductReferences($itemCodes, $frefso, $frefsrj, $frefdtno, $frefpr);
        $this->validateSubmittedReturReferenceQty($itemCodes, $qtys, $frefso, $frefsrj, $request->input('frefnoacak', []));
        $fnoacaks = $request->input('fnoacak', []);
        $frefnoacaks = $request->input('frefnoacak', []);

        $this->ensureNoDuplicateDetailCodes(
            $itemCodes,
            $frefcodes,
            $frefso,
            $frefsrj,
            $frefnoacaks
        );

        if ($umPriceValidation = $this->validateAdvancePaymentPriceAgainstReference($itemCodes, $frefsrj, $frefso, $frefdtno, $prices, (string) $request->input('fcustno'))) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $umPriceValidation], 422);
            }
            return back()->withInput()->with('error', $umPriceValidation);
        }

        if ($umQtyValidation = $this->validateAdvancePaymentQtyAgainstReference($itemCodes, $frefsrj, $frefso, $frefdtno, $qtys, (string) $request->input('fcustno'))) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $umQtyValidation], 422);
            }
            return back()->withInput()->with('error', $umQtyValidation);
        }

        $priceErrors = $this->validateSourcePriceForRows(
            $itemCodes,
            $prices,
            $frefso,
            $frefsrj,
            $frefdtno,
            $frefnoacaks
        );
        if ($priceErrors->any()) {
            $firstMessage = $priceErrors->first();
            if ($request->expectsJson()) {
                return response()->json(['message' => $firstMessage, 'errors' => $priceErrors->toArray()], 422);
            }
            return back()->withInput()->with('error', $firstMessage)->withErrors($priceErrors);
        }

        if ($typeSales === 1) {
            $frefcode = 'UM';
        } else {
            $frefcode = $request->input('frefcode_global');
        }

        // CEK UM & non-UM items
        $hasUM = in_array('UM', $itemCodes);
        $hasNonUM = collect($itemCodes)
            ->map(fn($c) => strtoupper(trim((string) $c)))
            ->filter(fn($c) => $c !== '' && $c !== 'UM')
            ->isNotEmpty();
        $outstandingDpDoc = $this->getCustomerOutstandingDpDocument((string) $request->input('fcustno'));
        $outstandingDpRef = trim((string) ($outstandingDpDoc->fsono ?? ''));
        $outstandingDpAmount = (float) ($outstandingDpDoc->fsisadp ?? 0);

        if ($typeSales === 0 && $hasUM) {
            $msg = 'Tipe Penjualan tidak boleh menginput Uang Muka (UM).';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return back()->withInput()->with('error', $msg);
        }

        if ($typeSales !== 0 && $hasNonUM) {
            $msg = 'Tipe Uang Muka hanya boleh menginput Uang Muka (UM).';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return back()->withInput()->with('error', $msg);
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

            $isUM = strtoupper(trim((string) $code)) === 'UM';
            $refSrjDoc = trim((string) ($frefsrj[$i] ?? ''));
            $refSoDoc = trim((string) ($frefso[$i] ?? ''));

            if ($isUM) {
                $refSrjDoc = $outstandingDpRef !== '' ? $outstandingDpRef : $refSrjDoc;
                $absPrice = $outstandingDpAmount > 0 ? $outstandingDpAmount : abs($price);
                $price = $hasNonUM ? -$absPrice : $absPrice;
            }

            $product = $products->get($code);

            if ($product && $product->fnonactive == '1') {
                $msg = "Produk [{$code}] {$product->fprdname} sudah discontinue.";
                if ($request->expectsJson()) {
                    return response()->json(['message' => $msg], 422);
                }
                return back()->withInput()->with('error', $msg);
            }

            // --- OVERRIDE unit dari referensi (SRJ / Invoice) ---
            if ($refSrjDoc !== '' && ! $isUM) {
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

            $selectedUnit = trim((string) ($satuans[$i] ?? ''));
            if ($selectedUnit === '' && $product) {
                foreach (['fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'] as $k) {
                    $v = trim((string) ($product->$k ?? ''));
                    if ($v !== '') {
                        $selectedUnit = mb_substr($v, 0, 5);
                        break;
                    }
                }
            }

            $qtyKecil = $qty;
            if ($referenceRatio !== null && $referenceRatio > 0) {
                $qtyKecil = $qty * $referenceRatio;
            } elseif (
                $product
                && $selectedUnit === trim((string) ($product->fsatuanbesar ?? ''))
                && (float) ($product->fqtykecil ?? 0) > 0
            ) {
                $qtyKecil = $qty * (float) $product->fqtykecil;
            } elseif (
                $product
                && $selectedUnit === trim((string) ($product->fsatuanbesar2 ?? ''))
                && (float) ($product->fqtykecil2 ?? 0) > 0
            ) {
                $qtyKecil = $qty * (float) $product->fqtykecil2;
            }

            if ($referenceDetail && $qtyKecil - (float) ($referenceDetail->fqtykecil ?? 0) > 0.000001) {
                $refLabel = $refSrjDoc !== '' ? 'SRJ' : 'Faktur';
                $refNo = $refSrjDoc !== '' ? $refSrjDoc : $refSoDoc;
                $msg = "Qty retur produk {$code} melebihi qty {$refLabel} {$refNo}.";
                if ($request->expectsJson()) {
                    return response()->json(['message' => $msg], 422);
                }
                return back()->withInput()->with('error', $msg);
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
                'fqtyremain' => $qtyKecil,
                'fprice' => $price,
                'fprice_rp' => $price * $frate,
                'fdisc' => $discRaw,
                'fpricenet' => $netPrice,
                'fpricenet_rp' => $netPrice * $frate,
                'fsalesnet' => $fsalesnet,
                'famount' => $amountRow,
                'famount_rp' => $amountRow * $frate,
                'fsatuan' => mb_substr($selectedUnit, 0, 5),
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
                'fsatuan' => mb_substr($selectedUnit, 0, 5),
                'fcode' => '0',
            ];
        }

        if (empty($detailRows)) {
            $msg = 'Tidak ada item valid. Periksa kode produk dan qty.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return back()->withInput()->with('error', $msg);
        }

        [$soUsageByReference, $srjUsageByReference] = $this->buildReturReferenceUsageMaps($detailRows);

        // if ($validationMessage = $this->validateReferenceUsage($soUsageByReference, $srjUsageByReference)) {
        //     if ($request->expectsJson()) {
        //         return response()->json(['message' => $validationMessage], 422);
        //     }
        //     return back()->withInput()->with('error', $validationMessage);
        // }

        // KALKULASI TOTAL
        $fapplyppn = '0'; // 0: Exclude
        $amountNet = $totalGross - $totalDisc;
        $ppnPersen = (float) $request->input('fppnpersen', 11);

        if ($fincludeppn === '1') {
            if ($fapplyppn === '1') {
                // INCLUDE: amountNet is current base, we extract
                $ppnAmount = $amountNet * ($ppnPersen / 100);
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

                $itemCodes = (array) $request->input('fprdcode', $request->input('fitemcode', []));
                $hasUMItem = (int) $typeSales !== 0 || collect($itemCodes)->contains(fn ($c) => strtoupper(trim((string) $c)) === 'UM');
                $trCode = $hasUMItem ? 'RUJ' : 'REJ';

                $fsonoRaw = strtoupper(trim((string) $request->input('fsono')));
                $fsono = $fsonoRaw !== '' ? $this->formatDisplayTransactionNumber($fsonoRaw, $fapplyppn === '0' && $fincludeppn === '0') : '';

                if (empty($fsono)) {
                    $branchCode = trim((string) ($request->input('fbranchcode') ?: 'NA')) ?: 'NA';
                    $hasPpn = $fapplyppn === '1' || $fincludeppn === '1';
                    $sep = $hasPpn ? '.' : '/';
                    $prefix = sprintf('%s%s%s%s%s%s', $trCode, $sep, $branchCode, $sep, $fsodate->format('y') . $fsodate->format('m'), $sep);

                    if (DB::getDriverName() === 'pgsql') {
                        $last = DB::table('tranmt')
                            ->where('fsono', 'like', "{$prefix}%")
                            ->selectRaw("MAX(CAST(SUBSTRING(fsono FROM '([0-9]+)$') AS int)) AS lastno")
                            ->value('lastno');

                        $nextNumber = (int) $last + 1;
                    } else {
                        $lastCode = DB::table('tranmt')
                            ->where('fsono', 'like', "{$prefix}%")
                            ->orderByDesc('fsono')
                            ->value('fsono');

                        $nextNumber = 1;
                        if ($lastCode && ($pos = max((int) strrpos($lastCode, '.'), (int) strrpos($lastCode, '/'))) !== false && $pos > 0) {
                            $nextNumber = ((int) substr($lastCode, $pos + 1)) + 1;
                        }
                    }

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
                    'fjatuhtempo' => $this->resolveReturFjatuhtempo($detailRows),
                    'fbranchcode' => $request->fbranchcode,
                ];

                $ftranmtid = DB::table('tranmt')->insertGetId($headerData, 'ftranmtid');

                foreach ($detailRows as &$row) {
                    $row['fsono'] = $fsono;
                    $row['frefcode'] = 'REJ';
                }
                unset($row);

                DB::table('trandt')->insert($detailRows);

                // ==== STOCK RECORDS ====
                $fstockmtno = $fsono;
                $masterStockData = [
                    'fstockmtno' => $fstockmtno,
                    'fstockmtcode' => 'REJ',
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
                    $srow['fstockmtcode'] = 'REJ';
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

            });

            $lastHeader = [
                'fsodate' => $fsodate->format('Y-m-d'),
                'fcustno' => $request->input('fcustno'),
                'fsalesman' => $request->input('fsalesman'),
                'ffrom' => $request->input('ffrom'),
                'fket' => $request->input('fket'),
            ];
            session()->flash('last_header', $lastHeader);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Retur Penjualan {$savedFsono} berhasil disimpan.",
                    'redirect_url' => route('returpenjualan.create'),
                    'success_prompt' => [
                        'type' => 'returpenjualan_create',
                        'redirect_url' => route('returpenjualan.print', $savedFsono),
                    ]
                ]);
            }

            return redirect()->route('returpenjualan.create')
                ->with('last_header', $lastHeader)
                ->with('success', "Retur Penjualan {$savedFsono} berhasil disimpan.")
                ->with('success_prompt', [
                    'type' => 'returpenjualan_create',
                    'redirect_url' => route('returpenjualan.print', $savedFsono),
                ]);
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Retur penjualan belum bisa disimpan: ' . $e->getMessage(),
                ], 500);
            }
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
                $sourceQty = max(0, (float) ($stat['source_qty_kecil'] ?? 0));
                if ((float) $qtyKecil - $sourceQty > 0.000001) {
                    $label = trim((string) ($stat['product_name'] ?? $stat['product_code'] ?? $referenceKey));
                    $refno = trim((string) ($stat['ref_doc'] ?? ''));
                    return 'Jumlah retur tidak boleh melebihi qty Faktur' . ($refno !== '' ? " ({$refno})" : '') . ". Produk {$label}.";
                }
            }
        }

        if (! empty($srjUsageByReference)) {
            $srjStats = $this->getReturReferenceStats('SRJ', $this->extractReferenceDocsFromUsageKeys(array_keys($srjUsageByReference)), $exceptFsono);

            foreach ($srjUsageByReference as $referenceKey => $qtyKecil) {
                $stat = $this->resolveReferenceStatWithFallback($srjStats, (string) $referenceKey);
                $sourceQty = max(0, (float) ($stat['source_qty_kecil'] ?? 0));
                if ((float) $qtyKecil - $sourceQty > 0.000001) {
                    $label = trim((string) ($stat['product_name'] ?? $stat['product_code'] ?? $referenceKey));
                    $refno = trim((string) ($stat['ref_doc'] ?? ''));
                    return 'Jumlah retur tidak boleh melebihi qty SRJ' . ($refno !== '' ? " ({$refno})" : '') . ". Produk {$label}.";
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

        if ($type === 'SO' || $type === 'INV') {
            $sourceRows = DB::table('trandt as d')
                ->join('tranmt as h', 'h.fsono', '=', 'd.fsono')
                ->leftJoin('msprd as p', 'p.fprdcode', '=', 'd.fprdcode')
                ->whereIn('d.fsono', $docNos)
                ->where(function ($q) {
                    $q->where('h.fsono', 'like', 'INV.%')
                      ->orWhere('h.fsono', 'like', 'INV/%')
                      ->orWhere('h.ftrcode', 'INV');
                })
                ->selectRaw("
                    TRIM(d.fsono) as ref_doc,
                    TRIM(d.fprdcode) as product_code,
                    COALESCE(d.fnoacak::text, '') as ref_noacak,
                    MAX(COALESCE(p.fprdname, d.fprdcode)) as product_name,
                    MAX(COALESCE(d.fsatuan, '')) as source_unit,
                    MAX(COALESCE(d.fprice, 0)) as source_price,
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
                    MAX(COALESCE(d.fprice, 0)) as source_price,
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
                'source_price' => (float) ($row->source_price ?? 0),
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

    private function returPenjualanKeyQuery($key)
    {
        $key = trim((string) $key);

        return Tranmt::query()
            ->whereIn('ftrcode', ['REJ', 'RUJ'])
            ->where(function ($q) use ($key) {
                if (is_numeric($key)) {
                    $q->where('ftranmtid', (int) $key);
                }

                $q->orWhere('fsono', $key)
                    ->orWhere('fsono', str_replace('.', '/', $key))
                    ->orWhere('fsono', str_replace('/', '.', $key));
            });
    }

    private function resolveReturPenjualanId($key): int
    {
        return (int) $this->returPenjualanKeyQuery($key)->value('ftranmtid') ?: abort(404);
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

        $returpenjualan = $this->returPenjualanKeyQuery($ftranmtid)->with(['customer', 'details' => function ($q) {
            $q->leftJoin('msprd', function ($j) {
                $j->on('msprd.fprdcode', '=', 'trandt.fprdcode');
            })
                ->leftJoin('trstockdt as sj_dt', function ($join) {
                    $join->on('sj_dt.fstockmtno', '=', 'trandt.frefsrj')
                        ->on('sj_dt.fprdcode', '=', 'trandt.fprdcode');
                })
                ->leftJoin('trandt as inv_dt', function ($join) {
                    $join->on('inv_dt.fsono', '=', 'trandt.frefso')
                        ->on('inv_dt.fprdcode', '=', 'trandt.fprdcode');
                })
                ->select(
                    'trandt.*',
                    'msprd.fprdcode as fitemcode',
                    'msprd.fprdname',
                    DB::raw("COALESCE(sj_dt.fprice, inv_dt.fprice, trandt.fprice) as ref_price")
                )
                // Ubah order ke ftrandtid (Primary Key detail) karena ftranmtid tidak ada
                ->orderBy('trandt.ftrandtid', 'asc');
        }])->firstOrFail();

        if ($message = $this->getPostedPeriodLockMessage($returpenjualan->fsodate, 'Retur ini')) {
            return redirect()->route('returpenjualan.edit', $returpenjualan->ftranmtid)->with('error', $message);
        }

        if (! $returpenjualan->customer) {
            $returpenjualan->setRelation('customer', Customer::where('fcustomercode', trim((string) $returpenjualan->fcustno))->first());
        }

        ['fcabang' => $fcabang, 'fbranchcode' => $fbranchcode] = $this->resolveBranchContext($returpenjualan->fbranchcode ?? null);

        $usageLockMessage = $this->getUsageLockMessage($returpenjualan);

        if (! empty($usageLockMessage)) {
            return redirect()
                ->route('returpenjualan.edit', $returpenjualan->ftranmtid)
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
            $refPrice = (float) ($d->ref_price ?? $d->fprice ?? 0);

            $detailRef = $valSrj !== '' ? $valSrj : ($valSo !== '' ? $valSo : trim((string) ($d->frefdtno ?? '')));
            if (str_starts_with(strtoupper(trim($d->fitemcode ?? '')), 'UM') && $detailRef !== '') {
                $refDetail = DB::table('trandt')->whereRaw('TRIM(fsono) = ?', [$detailRef])->where('fprdcode', 'UM')->first();
                if ($refDetail) {
                    $maxqty = max(0.0, (float) ($d->fqty ?? 0) + (float) ($refDetail->fqtyremain ?? 0));
                    $refPrice = (float) ($refDetail->fprice ?? $refPrice);
                } else {
                    $dp = DB::table('trsisadp_penjualan')->whereRaw('TRIM(fsono) = ?', [$detailRef])->first();
                    if ($dp) {
                        $maxqty = max(0.0, (float) ($d->fqty ?? 0) + 1.0);
                        $refPrice = (float) ($dp->fsisadp ?? $dp->famountsonet ?? $refPrice);
                    }
                }
            }

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
                'maxqty_unit' => '',
                'fprice' => (float) ($d->fprice ?? 0),
                'ref_price' => $refPrice,
                'maxprice' => $refPrice,
                'source_price' => $refPrice,
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
            'fhargajual',
            'fhargajual2',
            'fhargajual3',
            'fhargabeli',
            'fminstock'
        )
            ->whereRaw("COALESCE(TRIM(CAST(fnonactive AS TEXT)), '0') != '1'")
            ->orderBy('fprdname')
            ->get();

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
            'customerAdvanceWarnings' => $this->getCustomerAdvanceWarningMap(),
            'defaultPpnTarif' => $this->getDefaultPpnTarif(),
            'returpenjualan' => $returpenjualan,
            'displayFsono' => $this->formatDisplayTransactionNumber($returpenjualan->fsono ?? null, (string) ($returpenjualan->fapplyppn ?? '0') === '0' && (string) ($returpenjualan->fincludeppn ?? '0') === '0'),
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

        $returpenjualan = $this->returPenjualanKeyQuery($ftranmtid)->with(['customer', 'details' => function ($q) {
            $q->leftJoin('msprd', function ($j) {
                $j->on('msprd.fprdcode', '=', 'trandt.fprdcode');
            })
                ->leftJoin('trstockdt as sj_dt', function ($join) {
                    $join->on('sj_dt.fstockmtno', '=', 'trandt.frefsrj')
                        ->on('sj_dt.fprdcode', '=', 'trandt.fprdcode');
                })
                ->leftJoin('trandt as inv_dt', function ($join) {
                    $join->on('inv_dt.fsono', '=', 'trandt.frefso')
                        ->on('inv_dt.fprdcode', '=', 'trandt.fprdcode');
                })
                ->select(
                    'trandt.*',
                    'msprd.fprdcode as fitemcode',
                    'msprd.fprdname',
                    DB::raw("COALESCE(sj_dt.fprice, inv_dt.fprice, trandt.fprice) as ref_price")
                )
                // Ubah order ke ftrandtid (Primary Key detail) karena ftranmtid tidak ada
                ->orderBy('trandt.ftrandtid', 'asc');
        }])->firstOrFail();

        if ($message = $this->getPostedPeriodLockMessage($returpenjualan->fsodate, 'Retur ini')) {
            return redirect()->route('returpenjualan.edit', $returpenjualan->ftranmtid)->with('error', $message);
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
            $refPrice = (float) ($d->ref_price ?? $d->fprice ?? 0);

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
                'ref_price' => $refPrice,
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
        )
            ->whereRaw("COALESCE(TRIM(CAST(fnonactive AS TEXT)), '0') != '1'")
            ->orderBy('fprdname')
            ->get();

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
            'customerAdvanceWarnings' => $this->getCustomerAdvanceWarningMap(),
            'returpenjualan' => $returpenjualan,
            'displayFsono' => $this->formatDisplayTransactionNumber($returpenjualan->fsono ?? null, (string) ($returpenjualan->fapplyppn ?? '0') === '0' && (string) ($returpenjualan->fincludeppn ?? '0') === '0'),
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
        $allowNegativeStockQty = stock_boleh_minus();
        try {
            $request->validate([
                'fsodate' => ['required', 'date'],
                'fcustno' => ['required', 'string', 'max:10'],
                'ffrom' => ['required', 'string', 'max:10'],
                'fitemcode' => ['required', 'array', 'min:1'],
                'fitemcode.*' => ['required', 'string', 'max:30'],
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
                'fdisc' => ['nullable', 'array'],
                'frefso' => ['nullable'],
                'frefsrj' => ['nullable'],
                'fnoacak' => ['nullable', 'array'],
                'fnoacak.*' => ['nullable', 'regex:/^[1-9]{3}$/'],
                'frefnoacak' => ['nullable', 'array'],
                'frefnoacak.*' => ['nullable', 'regex:/^\d{3}$/'],
            ], [
                'ffrom.required' => 'Gudang wajib diisi.',
                'fcustno.required' => 'Customer wajib diisi.',
                'fsodate.required' => 'Tanggal transaksi wajib diisi.',
                'fitemcode.required' => 'Minimal 1 item.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                $firstError = collect($e->errors())->flatten()->first();
                return response()->json([
                    'message' => $firstError ?: 'Retur penjualan belum bisa diupdate. Cek data.',
                    'errors' => $e->errors(),
                ], 422);
            }
            throw $e;
        }

        $ftranmtid = $this->resolveReturPenjualanId($ftranmtid);

        // 2. LOAD HEADER
        $header = DB::table('tranmt')->where('ftranmtid', $ftranmtid)->first();
        if (! $header) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Faktur penjualan tidak ada.'], 404);
            }
            return abort(404, 'Faktur penjualan tidak ada.');
        }

        if ($message = $this->getPostedPeriodLockMessage($header->fsodate, 'Retur ini')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }
            return redirect()->route('returpenjualan.edit', $ftranmtid)->with('error', $message);
        }

        if ($message = $this->getUsageLockMessage((object) $header)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }
            return redirect()->route('returpenjualan.index')->with('error', $message);
        }

        $userLogin = auth('sysuser')->user() ?? auth()->user();
        $userName = mb_substr($userLogin->fname ?? 'admin', 0, 10);
        $userIdLog = $userLogin->fuserid ?? $userLogin->fsysuserid ?? 'ADMIN';

        // 3. INISIALISASI DATA
        $fsodate = Carbon::parse($request->fsodate);
        try {
            $this->ensureCreateDateWithinEditPeriod($fsodate, $header->fsodate);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                $firstError = collect($e->errors())->flatten()->first();
                return response()->json([
                    'message' => $firstError ?: 'Retur penjualan belum bisa diupdate. Cek tanggal.',
                    'errors' => $e->errors(),
                ], 422);
            }
            throw $e;
        }
        $fincludeppn = $request->has('fincludeppn') || $request->input('fincludeppn') == '1' ? '1' : '0';
        $fapplyppn = '0'; // PPN Retur Penjualan selalu Exclude (0)
        $defaultPpnTarif = $this->getDefaultPpnTarif();
        $ppnPersen = (float) $request->input('fppnpersen', $defaultPpnTarif);
        if ($ppnPersen <= 0) {
            $ppnPersen = $defaultPpnTarif;
        }
        $userid = $userName;
        $now = now();
        $frate = (float) $request->input('frate', $header->frate ?? 1);

        $itemCodes = $request->input('fitemcode', []);
        $typeSales = (int) $request->input('ftypesales', 0); // 0: Penjualan, 1: Uang Muka
        $itemDescs = $request->input('fdesc', []);
        $satuans = $request->input('fsatuan', []);
        $qtys = $request->input('fqty', []);
        $prices = $request->input('fprice', []);
        $discs = $request->input('fdisc', []);

        $frefcodes = $request->input('frefcode', []);
        $frefso = $request->input('frefso', []);
        $frefsrj = $request->input('frefsrj', []);
        $frefdtno = $request->input('frefdtno', []);
        $frefpr = $request->input('frefpr', []);
        $this->sanitizeReturReferences($frefso, $frefsrj);
        $this->validateReturProductReferences($itemCodes, $frefso, $frefsrj, $frefdtno, $frefpr);
        $this->validateSubmittedReturReferenceQty($itemCodes, $qtys, $frefso, $frefsrj, $request->input('frefnoacak', []));
        $fnoacaks = $request->input('fnoacak', []);
        $frefnoacaks = $request->input('frefnoacak', []);

        $this->ensureNoDuplicateDetailCodes(
            $itemCodes,
            $frefcodes,
            $frefso,
            $frefsrj,
            $frefnoacaks
        );

        if ($umPriceValidation = $this->validateAdvancePaymentPriceAgainstReference($itemCodes, $frefsrj, $frefso, $frefdtno, $prices, (string) $request->input('fcustno'))) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $umPriceValidation], 422);
            }
            return back()->withInput()->with('error', $umPriceValidation);
        }

        if ($umQtyValidation = $this->validateAdvancePaymentQtyAgainstReference($itemCodes, $frefsrj, $frefso, $frefdtno, $qtys, (string) $request->input('fcustno'), $returpenjualan->fsono)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $umQtyValidation], 422);
            }
            return back()->withInput()->with('error', $umQtyValidation);
        }

        $priceErrors = $this->validateSourcePriceForRows(
            $itemCodes,
            $prices,
            $frefso,
            $frefsrj,
            $frefdtno,
            $frefnoacaks
        );
        if ($priceErrors->any()) {
            $firstMessage = $priceErrors->first();
            if ($request->expectsJson()) {
                return response()->json(['message' => $firstMessage, 'errors' => $priceErrors->toArray()], 422);
            }
            return back()->withInput()->with('error', $firstMessage)->withErrors($priceErrors);
        }

        if ($typeSales === 1) {
            $frefcode = 'UM';
        } else {
            $frefcode = $request->input('frefcode_global')
                ?: ($header->frefcode ?? '');
        }

        $hasUM = in_array('UM', $itemCodes);
        $hasNonUM = collect($itemCodes)
            ->map(fn($c) => strtoupper(trim((string) $c)))
            ->filter(fn($c) => $c !== '' && $c !== 'UM')
            ->isNotEmpty();
        $outstandingDpDoc = $this->getCustomerOutstandingDpDocument((string) $request->input('fcustno'));
        $outstandingDpRef = trim((string) ($outstandingDpDoc->fsono ?? ''));
        $outstandingDpAmount = (float) ($outstandingDpDoc->fsisadp ?? 0);

        if ($typeSales === 0 && $hasUM) {
            $msg = 'Tipe Penjualan tidak boleh menginput Uang Muka (UM).';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return back()->withInput()->with('error', $msg);
        }

        if ($typeSales !== 0 && $hasNonUM) {
            $msg = 'Tipe Uang Muka hanya boleh menginput Uang Muka (UM).';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return back()->withInput()->with('error', $msg);
        }

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
        $hasNonUM = collect($itemCodes)
            ->map(fn($c) => strtoupper(trim((string) $c)))
            ->filter(fn($c) => $c !== '' && $c !== 'UM')
            ->isNotEmpty();

        $stockDetailRows = [];
        foreach ($itemCodes as $i => $code) {
            $qty = (float) ($qtys[$i] ?? 0);
            $price = (float) ($prices[$i] ?? 0);

            if (empty($code) || $qty <= 0) {
                continue;
            }

            $isUM = strtoupper(trim((string) $code)) === 'UM';
            $refSrjDoc = trim((string) ($frefsrj[$i] ?? ''));
            $refSoDoc = trim((string) ($frefso[$i] ?? ''));

            if ($isUM) {
                $refSrjDoc = $outstandingDpRef !== '' ? $outstandingDpRef : $refSrjDoc;
                $absPrice = $outstandingDpAmount > 0 ? $outstandingDpAmount : abs($price);
                $price = $hasNonUM ? -$absPrice : $absPrice;
            }

            $product = $products->get($code);

            if ($refSrjDoc !== '' && ! $isUM) {
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

            $selectedUnit = trim((string) ($satuans[$i] ?? ''));
            if ($selectedUnit === '' && $product) {
                foreach (['fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'] as $k) {
                    $v = trim((string) ($product->$k ?? ''));
                    if ($v !== '') {
                        $selectedUnit = mb_substr($v, 0, 5);
                        break;
                    }
                }
            }

            $qtyKecil = $qty;
            if ($referenceRatio !== null && $referenceRatio > 0) {
                $qtyKecil = $qty * $referenceRatio;
            } elseif (
                $product
                && $selectedUnit === trim((string) ($product->fsatuanbesar ?? ''))
                && (float) ($product->fqtykecil ?? 0) > 0
            ) {
                $qtyKecil = $qty * (float) $product->fqtykecil;
            } elseif (
                $product
                && $selectedUnit === trim((string) ($product->fsatuanbesar2 ?? ''))
                && (float) ($product->fqtykecil2 ?? 0) > 0
            ) {
                $qtyKecil = $qty * (float) $product->fqtykecil2;
            }

            if ($referenceDetail && $qtyKecil - (float) ($referenceDetail->fqtykecil ?? 0) > 0.000001) {
                $refLabel = $refSrjDoc !== '' ? 'SRJ' : 'Faktur';
                $refNo = $refSrjDoc !== '' ? $refSrjDoc : $refSoDoc;
                $msg = "Qty retur produk {$code} melebihi qty {$refLabel} {$refNo}.";
                if ($request->expectsJson()) {
                    return response()->json(['message' => $msg], 422);
                }
                return back()->withInput()->with('error', $msg);
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
                'fqtyremain' => $qtyKecil,
                'fprice' => $price,
                'fprice_rp' => $price * $frate,
                'fdisc' => $discRaw,
                'fpricenet' => $netPrice,
                'fpricenet_rp' => $netPrice * $frate,
                'fsalesnet' => $fsalesnet,
                'famount' => $amountRow,
                'famount_rp' => $amountRow * $frate,
                'fsatuan' => mb_substr($selectedUnit, 0, 5),
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
                'fsatuan' => mb_substr($selectedUnit, 0, 5),
                'fcode' => '0',
            ];
        }

        [$oldSoRestoreByReference, $oldSrjRestoreByReference] = $this->buildReturReferenceRestoreMaps($header->fsono);

        [$soUsageByReference, $srjUsageByReference] = $this->buildReturReferenceUsageMaps($detailRows);

        // if ($validationMessage = $this->validateReferenceUsage(
        //     $soUsageByReference,
        //     $srjUsageByReference,
        //     $header->fsono
        // )) {
        //     if ($request->expectsJson()) {
        //         return response()->json(['message' => $validationMessage], 422);
        //     }
        //     return back()->withInput()->with('error', $validationMessage);
        // }

        // 5. KALKULASI TOTAL
        $fapplyppn = '0';
        $amountNet = $totalGross - $totalDisc;
        $defaultPpnTarif = $this->getDefaultPpnTarif();
        $ppnPersen = (float) $request->input('fppnpersen', $defaultPpnTarif);
        if ($ppnPersen <= 0) {
            $ppnPersen = $defaultPpnTarif;
        }

        if ($fincludeppn === '1') {
            if ($fapplyppn === '1') {
                $ppnAmount = $amountNet * ($ppnPersen / 100);
                $amountNet = $amountNet - $ppnAmount;
                $grandTotal = $amountNet + $ppnAmount;
            } else {
                $ppnAmount = $amountNet * ($ppnPersen / 100);
                $grandTotal = $amountNet + $ppnAmount;
            }
        } else {
            $ppnAmount = 0;
            $grandTotal = $amountNet;
        }

        $ftypesales = $request->input('ftypesales', 0);

        $stockMtNo = preg_replace('/^(REJ|RUJ)\./i', 'REB.', (string) $header->fsono);
        $oldStockHeader = DB::table('trstockmt')->where('fstockmtno', $stockMtNo)->first();
        if ($stockResponse = $this->validateStockMinusLines(
            $this->buildStockMinusLinesFromNetChange($stockDetailRows, (string) $request->input('ffrom'), $this->fetchStockDetailRows($stockMtNo), (string) ($oldStockHeader->ffrom ?? $request->input('ffrom'))),
            $request->boolean('force_save')
        )) {
            return $stockResponse;
        }

        // 6. TRANSACTION
        try {
            DB::transaction(function () use (
                $request,
                $ftranmtid,
                $header,
                $fsodate,
                $fincludeppn,
                $userid,
                $userIdLog,
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
                $trxLogId = 'LOG' . $now->format('YmdHis') . rand(100, 999);

                // Update Header (tranmt)
                DB::table('tranmt')->where('ftranmtid', $ftranmtid)->update([
                    'fsodate'          => $fsodate,
                    'fcustno'          => mb_substr($request->fcustno, 0, 10),
                    'fsalesman'        => mb_substr((string) ($request->fsalesman ?? ''), 0, 30),
                    'ffrom'            => mb_substr((string) ($request->ffrom ?? ''), 0, 10),
                    'fdiscount'        => $totalDisc,
                    'fdiscount_rp'     => $totalDisc * $frate,
                    'famountgross'     => $totalGross,
                    'famountgross_rp'  => $totalGross * $frate,
                    'famountsonet'     => $amountNet,
                    'famountsonet_rp'  => $amountNet * $frate,
                    'famountpajak'     => $ppnAmount,
                    'famountpajak_rp'  => $ppnAmount * $frate,
                    'famountso'        => $grandTotal,
                    'famountso_rp'     => $grandTotal * $frate,
                    'ftotalsalesnet'   => $totalSalesNet,
                    'fket'             => $request->fket ?? '',
                    'fuserid'          => $userid,
                    'fdatetime'        => $now,
                    'fincludeppn'      => $fincludeppn,
                    'fapplyppn'        => $fapplyppn,
                    'ftypesales'       => $ftypesales,
                    'ftrcode'          => 'REJ',
                    'fppnpersen'       => $ppnPersen,
                    'ftaxno'           => $request->ftaxno ?? '0',
                    'fjatuhtempo'      => $this->resolveReturFjatuhtempo($detailRows),
                ]);

                $updatedHeader = DB::table('tranmt')->where('ftranmtid', $ftranmtid)->first();

                // 1. INSERT Log Header tranmt (Update)
                DB::table('log_tranmt')->insert([
                    'ftrxlogid'        => $trxLogId,
                    'ftranmtid'       => $updatedHeader->ftranmtid,
                    'fsono'            => $updatedHeader->fsono,
                    'fbranchcode'      => $updatedHeader->fbranchcode,
                    'ftaxno'           => $updatedHeader->ftaxno,
                    'fsodate'          => $updatedHeader->fsodate,
                    'ftypesales'       => $updatedHeader->ftypesales,
                    'ftrcode'          => $updatedHeader->ftrcode,
                    'frefno'           => $updatedHeader->frefno,
                    'fcustno'          => $updatedHeader->fcustno,
                    'fsalesman'        => $updatedHeader->fsalesman,
                    'fcurrency'        => $updatedHeader->fcurrency,
                    'frate'            => $updatedHeader->frate,
                    'fdiscpersen'      => $updatedHeader->fdiscpersen,
                    'fdiscount'        => $updatedHeader->fdiscount,
                    'fdiscount_rp'     => $updatedHeader->fdiscount_rp,
                    'famountgross'     => $updatedHeader->famountgross,
                    'famountgross_rp'  => $updatedHeader->famountgross_rp,
                    'famountsonet'     => $updatedHeader->famountsonet,
                    'famountsonet_rp'  => $updatedHeader->famountsonet_rp,
                    'famountpajak'     => $updatedHeader->famountpajak,
                    'famountpajak_rp'  => $updatedHeader->famountpajak_rp,
                    'famountso'        => $updatedHeader->famountso,
                    'famountso_rp'     => $updatedHeader->famountso_rp,
                    'famountremain'    => $updatedHeader->famountremain,
                    'famountremain_rp' => $updatedHeader->famountremain_rp,
                    'fket'             => $updatedHeader->fket,
                    'fprdout'          => $updatedHeader->fprdout,
                    'fuserid'          => $updatedHeader->fuserid,
                    'fdatetime'        => $updatedHeader->fdatetime,
                    'fjatuhtempo'      => $updatedHeader->fjatuhtempo,
                    'fongkosangkut'    => $updatedHeader->fongkosangkut,
                    'fincludeppn'      => $updatedHeader->fincludeppn,
                    'ftotalsalesnet'   => $updatedHeader->ftotalsalesnet,
                    'fkodefp'          => $updatedHeader->fkodefp,
                    'fprint'           => $updatedHeader->fprint,
                    'fsudahtagih'      => $updatedHeader->fsudahtagih,
                    'fppnpersen'       => $updatedHeader->fppnpersen,
                    'fapplyppn'        => $updatedHeader->fapplyppn,
                    'fneedacc'         => $updatedHeader->fneedacc,
                    'fgrosir'          => $updatedHeader->fgrosir,
                    'ftunai'           => $updatedHeader->ftunai,
                    'fketinternal'     => $updatedHeader->fketinternal ?? null,
                    'ffrom'            => $updatedHeader->ffrom,
                    'fuseracc'         => $updatedHeader->fuseracc,
                    'fapproval'        => $updatedHeader->fapproval,
                    'fuserapproved'    => $updatedHeader->fuserapproved,
                    'fdateapproved'    => $updatedHeader->fdateapproved,
                    'feditmode'        => 'U',
                    'fuseridlog'       => $userIdLog,
                    'fdatetimelog'     => $now,
                ]);

                // Hapus detail lama trandt & restore usage
                DB::table('trandt')->where('fsono', $header->fsono)->delete();
                $this->restoreReturReferenceUsage($oldSoRestoreByReference, $oldSrjRestoreByReference);

                // Insert detail baru trandt & log
                if (! empty($detailRows)) {
                    foreach ($detailRows as $row) {
                        $insertedTrandtid = DB::table('trandt')->insertGetId($row, 'ftrandtid');
                        $dtObj = DB::table('trandt')->where('ftrandtid', $insertedTrandtid)->first();

                        // 2. INSERT Log Detail trandt (Update)
                        DB::table('log_trandt')->insert([
                            'ftrxlogid'        => $trxLogId,
                            'ftrandtid'       => $dtObj->ftrandtid,
                            'fsono'            => $dtObj->fsono,
                            'fnou'             => $dtObj->fnou,
                            'fprdcode'         => $dtObj->fprdcode,
                            'fdesc'            => $dtObj->fdesc,
                            'fqty'             => $dtObj->fqty,
                            'fqtyremain'       => $dtObj->fqtyremain,
                            'fsalesnet'        => $dtObj->fsalesnet,
                            'fsatuan'          => $dtObj->fsatuan,
                            'fqtykecil'        => $dtObj->fqtykecil,
                            'fhpp'             => $dtObj->fhpp,
                            'fprice'           => $dtObj->fprice,
                            'fprice_rp'        => $dtObj->fprice_rp,
                            'fdisc'            => $dtObj->fdisc,
                            'fpricenet'        => $dtObj->fpricenet,
                            'fpricenet_rp'     => $dtObj->fpricenet_rp,
                            'frefsrj'          => $dtObj->frefsrj,
                            'frefcode'         => $dtObj->frefcode,
                            'frefso'           => $dtObj->frefso,
                            'fnoacak'          => $dtObj->fnoacak,
                            'frefnosoacak'     => $dtObj->frefnosoacak,
                            'frefnoacak'       => $dtObj->frefnoacak,
                            'famount'          => $dtObj->famount,
                            'famount_rp'       => $dtObj->famount_rp,
                            'fuserid'          => $dtObj->fuserid,
                            'fdatetime'        => $dtObj->fdatetime,
                            'feditmode'        => 'U',
                            'fuseridlog'       => $userIdLog,
                            'fdatetimelog'     => $now,
                        ]);
                    }
                }

                // ==== SYNC STOCK RECORDS ====
                $fstockmtno = (string) $header->fsono;
                $stockHeader = DB::table('trstockmt')->where('fstockmtno', $fstockmtno)->first();
                if (! $stockHeader) {
                    $fstockmtno = preg_replace('/^(REJ|RUJ)\./i', 'REB.', (string) $header->fsono);
                    $stockHeader = DB::table('trstockmt')->where('fstockmtno', $fstockmtno)->first();
                }

                if ($stockHeader) {
                    // Update Stock Header
                    DB::table('trstockmt')->where('fstockmtid', $stockHeader->fstockmtid)->update([
                        'fstockmtcode'     => 'REJ',
                        'fstockmtdate'     => $fsodate,
                        'fsupplier'        => mb_substr($request->fcustno, 0, 10),
                        'ffrom'            => mb_substr((string) ($request->ffrom ?? ''), 0, 10),
                        'famount'          => $amountNet,
                        'famount_rp'       => $amountNet * $frate,
                        'famountpajak'     => $ppnAmount,
                        'famountpajak_rp'  => $ppnAmount * $frate,
                        'famountmt'        => $grandTotal,
                        'famountmt_rp'     => $grandTotal * $frate,
                        'famountremain'    => $grandTotal,
                        'famountremain_rp' => $grandTotal * $frate,
                        'fket'             => $request->fket ?? '',
                        'fusercreate'      => $userid,
                        'fdatetime'        => $now,
                        'fbranchcode'      => $request->fbranchcode ?? $stockHeader->fbranchcode ?? 'BG',
                        'fincludeppn'      => $fincludeppn,
                    ]);

                    $updatedStockHeader = DB::table('trstockmt')->where('fstockmtid', $stockHeader->fstockmtid)->first();

                    // 3. INSERT Log Header trstockmt (Update)
                    DB::table('log_trstockmt')->insert([
                        'ftrxlogid'        => $trxLogId,
                        'fstockmtid'       => $updatedStockHeader->fstockmtid,
                        'fstockmtno'       => $updatedStockHeader->fstockmtno,
                        'fbranchcode'      => $updatedStockHeader->fbranchcode,
                        'fstockmtcode'     => $updatedStockHeader->fstockmtcode,
                        'fstockmtdate'     => $updatedStockHeader->fstockmtdate,
                        'fprdout'          => $updatedStockHeader->fprdout,
                        'fsupplier'        => $updatedStockHeader->fsupplier,
                        'fcurrency'        => $updatedStockHeader->fcurrency,
                        'frate'            => $updatedStockHeader->frate,
                        'ftypebuy'         => $updatedStockHeader->ftypebuy,
                        'ftempohr'         => $updatedStockHeader->ftempohr,
                        'ftrancode'        => $updatedStockHeader->ftrancode,
                        'fsalesman'        => $updatedStockHeader->fsalesman,
                        'fjatuhtempo'      => $updatedStockHeader->fjatuhtempo,
                        'fprint'           => $updatedStockHeader->fprint,
                        'fsudahtagih'      => $updatedStockHeader->fsudahtagih,
                        'fdiscount'        => $updatedStockHeader->fdiscount,
                        'fupdatedat'       => $updatedStockHeader->fupdatedat,
                        'famount'          => $updatedStockHeader->famount,
                        'famount_rp'       => $updatedStockHeader->famount_rp,
                        'famountpajak'     => $updatedStockHeader->famountpajak,
                        'famountpajak_rp'  => $updatedStockHeader->famountpajak_rp,
                        'famountmt'        => $updatedStockHeader->famountmt,
                        'famountmt_rp'     => $updatedStockHeader->famountmt_rp,
                        'famountremain'    => $updatedStockHeader->famountremain,
                        'famountremain_rp' => $updatedStockHeader->famountremain_rp,
                        'frefno'           => $updatedStockHeader->frefno,
                        'frefpo'           => $updatedStockHeader->frefpo,
                        'ffrom'            => $updatedStockHeader->ffrom,
                        'fto'              => $updatedStockHeader->fto,
                        'fkirim'           => $updatedStockHeader->fkirim,
                        'fprdjadi'         => $updatedStockHeader->fprdjadi,
                        'fqtyjadi'         => $updatedStockHeader->fqtyjadi,
                        'fket'             => $updatedStockHeader->fket,
                        'fincludeppn'      => $updatedStockHeader->fincludeppn,
                        'fppnpersen'       => $updatedStockHeader->fppnpersen,
                        'fapplyppn'        => $updatedStockHeader->fapplyppn,
                        'fketinternal'     => $updatedStockHeader->fketinternal,
                        'fusercreate'      => $updatedStockHeader->fusercreate,
                        'fdatetime'        => $updatedStockHeader->fdatetime,
                        'fuserupdate'      => $updatedStockHeader->fuserupdate,
                        'feditmode'        => 'U',
                        'fuseridlog'       => $userIdLog,
                        'fdatetimelog'     => $now,
                    ]);

                    // Sync Stock Details
                    DB::table('trstockdt')->where('fstockmtno', $fstockmtno)->delete();
                    foreach ($stockDetailRows as &$srow) {
                        $srow['fstockmtno'] = $fstockmtno;
                        $srow['fstockmtcode'] = 'REJ';

                        $insertedStockDtId = DB::table('trstockdt')->insertGetId($srow, 'fstockdtid');
                        $sdtObj = DB::table('trstockdt')->where('fstockdtid', $insertedStockDtId)->first();

                        // 4. INSERT Log Detail trstockdt (Update)
                        DB::table('log_trstockdt')->insert([
                            'ftrxlogid'     => $trxLogId,
                            'fstockdtid'    => $sdtObj->fstockdtid,
                            'fstockmtcode'  => $sdtObj->fstockmtcode,
                            'fstockmtno'    => $sdtObj->fstockmtno,
                            'fprdcode'      => $sdtObj->fprdcode,
                            'frefdtno'      => $sdtObj->frefdtno,
                            'fqty'          => $sdtObj->fqty,
                            'fqtyremain'    => $sdtObj->fqtyremain,
                            'fsatuan'       => $sdtObj->fsatuan,
                            'fqtykecil'     => $sdtObj->fqtykecil,
                            'fprice'        => $sdtObj->fprice,
                            'fprice_rp'     => $sdtObj->fprice_rp,
                            'ftotprice'     => $sdtObj->ftotprice,
                            'ftotprice_rp'  => $sdtObj->ftotprice_rp,
                            'fketdt'        => $sdtObj->fketdt,
                            'fcode'         => $sdtObj->fcode,
                            'frefso'        => $sdtObj->frefso,
                            'fdesc'         => $sdtObj->fdesc,
                            'fclosedt'      => $sdtObj->fclosedt,
                            'fdiscpersen'   => $sdtObj->fdiscpersen,
                            'fbiaya'        => $sdtObj->fbiaya,
                            'fpricenet'     => $sdtObj->fpricenet,
                            'fnoacak'       => $sdtObj->fnoacak,
                            'frefnoacak'    => $sdtObj->frefnoacak,
                            'frefnoacak_so' => $sdtObj->frefnoacak_so,
                            'fusercreate'   => $sdtObj->fusercreate,
                            'fdatetime'     => $sdtObj->fdatetime,
                            'fupdatedat'    => $sdtObj->fupdatedat,
                            'fuserupdate'   => $sdtObj->fuserupdate,
                            'feditmode'     => 'U',
                            'fuseridlog'    => $userIdLog,
                            'fdatetimelog'  => $now,
                        ]);
                    }
                    unset($srow);
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
            });

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Retur Penjualan {$fstockmtno} berhasil diupdate.",
                    'redirect_url' => route('returpenjualan.index'),
                ]);
            }

            return redirect()->route('returpenjualan.index')->with('success', "Retur Penjualan {$fstockmtno} berhasil diupdate.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();

            if ($request->expectsJson()) {
                return response()->json(['message' => $firstError ?: 'Gagal update retur penjualan.'], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('error', $firstError ?: 'Retur penjualan belum bisa diupdate. Cek data.');
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Retur penjualan belum bisa diupdate: ' . $e->getMessage(),
                ], 500);
            }
            return back()->withInput()->with('error', 'Retur penjualan belum bisa diupdate: ' . $e->getMessage());
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

        $returpenjualan = $this->returPenjualanKeyQuery($ftranmtid)->with(['customer', 'details' => function ($q) {
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
        }])->firstOrFail();

        if ($message = $this->getPostedPeriodLockMessage($returpenjualan->fsodate, 'Retur ini')) {
            return redirect()->route('returpenjualan.edit', $returpenjualan->ftranmtid)->with('error', $message);
        }

        if (! $returpenjualan->customer) {
            $returpenjualan->setRelation('customer', Customer::where('fcustomercode', trim((string) $returpenjualan->fcustno))->first());
        }

        ['fcabang' => $fcabang, 'fbranchcode' => $fbranchcode] = $this->resolveBranchContext($returpenjualan->fbranchcode ?? null);

        $usageLockMessage = $this->getUsageLockMessage($returpenjualan);

        if (! empty($usageLockMessage)) {
            return redirect()
                ->route('returpenjualan.edit', $returpenjualan->ftranmtid)
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
        )
            ->whereRaw("COALESCE(TRIM(CAST(fnonactive AS TEXT)), '0') != '1'")
            ->orderBy('fprdname')
            ->get();

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
            'displayFsono' => $this->formatDisplayTransactionNumber($returpenjualan->fsono ?? null, (string) ($returpenjualan->fapplyppn ?? '0') === '0' && (string) ($returpenjualan->fincludeppn ?? '0') === '0'),
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
            $ftranmtid = $this->resolveReturPenjualanId($ftranmtid);
            $deletedHeader = null;
            $returHeader = Tranmt::findOrFail($ftranmtid);
            if ($message = $this->getPostedPeriodLockMessage($returHeader->fsodate, 'Retur ini')) {
                return redirect()->route('returpenjualan.index')->with('error', $message);
            }
            if ($message = $this->getUsageLockMessage($returHeader)) {
                return redirect()->route('returpenjualan.index')->with('error', $message);
            }
            $stockMtNo = (string) $returHeader->fsono;
            $stockHeader = DB::table('trstockmt')->where('fstockmtno', $stockMtNo)->first();
            if (! $stockHeader) {
                $stockMtNo = preg_replace('/^(REJ|RUJ)\./i', 'REB.', (string) $returHeader->fsono);
                $stockHeader = DB::table('trstockmt')->where('fstockmtno', $stockMtNo)->first();
            }
            if ($stockHeader && ($stockResponse = $this->validateStockMinusLines(
                $this->buildStockMinusLinesFromNetChange([], (string) $stockHeader->ffrom, $this->fetchStockDetailRows($stockMtNo), (string) $stockHeader->ffrom),
                request()->boolean('force_save')
            ))) {
                return $stockResponse;
            }

            $userLogin = auth('sysuser')->user() ?? auth()->user();
            $userIdLog = $userLogin->fuserid ?? $userLogin->fsysuserid ?? 'ADMIN';

            DB::transaction(function () use ($ftranmtid, &$deletedHeader, $userIdLog) {
                $now = now();
                $trxLogId = 'LOG' . $now->format('YmdHis') . rand(100, 999);

                $returpenjualan = Tranmt::findOrFail($ftranmtid);
                $deletedHeader = $returpenjualan;

                if ($message = $this->getPostedPeriodLockMessage($returpenjualan->fsodate, 'Retur ini')) {
                    throw new \RuntimeException($message);
                }

                if ($message = $this->getUsageLockMessage($returpenjualan)) {
                    throw new \RuntimeException($message);
                }

                $fsono = $returpenjualan->fsono;

                // 1. Log Header tranmt (Delete)
                DB::table('log_tranmt')->insert([
                    'ftrxlogid'        => $trxLogId,
                    'ftranmtid'       => $returpenjualan->ftranmtid,
                    'fsono'            => $returpenjualan->fsono,
                    'fbranchcode'      => $returpenjualan->fbranchcode,
                    'ftaxno'           => $returpenjualan->ftaxno,
                    'fsodate'          => $returpenjualan->fsodate,
                    'ftypesales'       => $returpenjualan->ftypesales,
                    'ftrcode'          => $returpenjualan->ftrcode,
                    'frefno'           => $returpenjualan->frefno,
                    'fcustno'          => $returpenjualan->fcustno,
                    'fsalesman'        => $returpenjualan->fsalesman,
                    'fcurrency'        => $returpenjualan->fcurrency,
                    'frate'            => $returpenjualan->frate,
                    'fdiscpersen'      => $returpenjualan->fdiscpersen,
                    'fdiscount'        => $returpenjualan->fdiscount,
                    'fdiscount_rp'     => $returpenjualan->fdiscount_rp,
                    'famountgross'     => $returpenjualan->famountgross,
                    'famountgross_rp'  => $returpenjualan->famountgross_rp,
                    'famountsonet'     => $returpenjualan->famountsonet,
                    'famountsonet_rp'  => $returpenjualan->famountsonet_rp,
                    'famountpajak'     => $returpenjualan->famountpajak,
                    'famountpajak_rp'  => $returpenjualan->famountpajak_rp,
                    'famountso'        => $returpenjualan->famountso,
                    'famountso_rp'     => $returpenjualan->famountso_rp,
                    'famountremain'    => $returpenjualan->famountremain,
                    'famountremain_rp' => $returpenjualan->famountremain_rp,
                    'fket'             => $returpenjualan->fket,
                    'fprdout'          => $returpenjualan->fprdout,
                    'fuserid'          => $returpenjualan->fuserid,
                    'fdatetime'        => $returpenjualan->fdatetime,
                    'fjatuhtempo'      => $returpenjualan->fjatuhtempo,
                    'fongkosangkut'    => $returpenjualan->fongkosangkut,
                    'fincludeppn'      => $returpenjualan->fincludeppn,
                    'ftotalsalesnet'   => $returpenjualan->ftotalsalesnet,
                    'fkodefp'          => $returpenjualan->fkodefp,
                    'fprint'           => $returpenjualan->fprint,
                    'fsudahtagih'      => $returpenjualan->fsudahtagih,
                    'fppnpersen'       => $returpenjualan->fppnpersen,
                    'fapplyppn'        => $returpenjualan->fapplyppn,
                    'fneedacc'         => $returpenjualan->fneedacc,
                    'fgrosir'          => $returpenjualan->fgrosir,
                    'ftunai'           => $returpenjualan->ftunai,
                    'fketinternal'     => $returpenjualan->fketinternal ?? null,
                    'ffrom'            => $returpenjualan->ffrom,
                    'fuseracc'         => $returpenjualan->fuseracc,
                    'fapproval'        => $returpenjualan->fapproval,
                    'fuserapproved'    => $returpenjualan->fuserapproved,
                    'fdateapproved'    => $returpenjualan->fdateapproved,
                    'feditmode'        => 'D',
                    'fuseridlog'       => $userIdLog,
                    'fdatetimelog'     => $now,
                ]);

                // 2. Log Detail trandt (Delete)
                $details = DB::table('trandt')->where('fsono', $fsono)->get();
                foreach ($details as $detail) {
                    DB::table('log_trandt')->insert([
                        'ftrxlogid'        => $trxLogId,
                        'ftrandtid'       => $detail->ftrandtid,
                        'fsono'            => $detail->fsono,
                        'fnou'             => $detail->fnou,
                        'fprdcode'         => $detail->fprdcode,
                        'fdesc'            => $detail->fdesc,
                        'fqty'             => $detail->fqty,
                        'fqtyremain'       => $detail->fqtyremain,
                        'fsalesnet'        => $detail->fsalesnet,
                        'fsatuan'          => $detail->fsatuan,
                        'fqtykecil'        => $detail->fqtykecil,
                        'fhpp'             => $detail->fhpp,
                        'fprice'           => $detail->fprice,
                        'fprice_rp'        => $detail->fprice_rp,
                        'fdisc'            => $detail->fdisc,
                        'fpricenet'        => $detail->fpricenet,
                        'fpricenet_rp'     => $detail->fpricenet_rp,
                        'frefsrj'          => $detail->frefsrj,
                        'frefcode'         => $detail->frefcode,
                        'frefso'           => $detail->frefso,
                        'fnoacak'          => $detail->fnoacak,
                        'frefnosoacak'     => $detail->frefnosoacak,
                        'frefnoacak'       => $detail->frefnoacak,
                        'famount'          => $detail->famount,
                        'famount_rp'       => $detail->famount_rp,
                        'fuserid'          => $detail->fuserid,
                        'fdatetime'        => $detail->fdatetime,
                        'feditmode'        => 'D',
                        'fuseridlog'       => $userIdLog,
                        'fdatetimelog'     => $now,
                    ]);
                }

                [$oldSoRestoreByReference, $oldSrjRestoreByReference] = $this->buildReturReferenceRestoreMaps($fsono);
                $this->restoreReturReferenceUsage($oldSoRestoreByReference, $oldSrjRestoreByReference);

                // Delete details (trandt)
                DB::table('trandt')
                    ->where('fsono', $fsono)
                    ->delete();

                // 3. Delete & Log stock records (trstockmt & trstockdt)
                $fstockmtno = (string) $fsono;
                $stockHeader = DB::table('trstockmt')->where('fstockmtno', $fstockmtno)->first();
                if (! $stockHeader) {
                    $fstockmtno = preg_replace('/^(REJ|RUJ)\./i', 'REB.', $fsono);
                    $stockHeader = DB::table('trstockmt')->where('fstockmtno', $fstockmtno)->first();
                }

                if ($stockHeader) {
                    // Log trstockmt (Delete)
                    DB::table('log_trstockmt')->insert([
                        'ftrxlogid'        => $trxLogId,
                        'fstockmtid'       => $stockHeader->fstockmtid,
                        'fstockmtno'       => $stockHeader->fstockmtno,
                        'fbranchcode'      => $stockHeader->fbranchcode,
                        'fstockmtcode'     => $stockHeader->fstockmtcode,
                        'fstockmtdate'     => $stockHeader->fstockmtdate,
                        'fprdout'          => $stockHeader->fprdout,
                        'fsupplier'        => $stockHeader->fsupplier,
                        'fcurrency'        => $stockHeader->fcurrency,
                        'frate'            => $stockHeader->frate,
                        'ftypebuy'         => $stockHeader->ftypebuy,
                        'ftempohr'         => $stockHeader->ftempohr,
                        'ftrancode'        => $stockHeader->ftrancode,
                        'fsalesman'        => $stockHeader->fsalesman,
                        'fjatuhtempo'      => $stockHeader->fjatuhtempo,
                        'fprint'           => $stockHeader->fprint,
                        'fsudahtagih'      => $stockHeader->fsudahtagih,
                        'fdiscount'        => $stockHeader->fdiscount,
                        'fupdatedat'       => $stockHeader->fupdatedat,
                        'famount'          => $stockHeader->famount,
                        'famount_rp'       => $stockHeader->famount_rp,
                        'famountpajak'     => $stockHeader->famountpajak,
                        'famountpajak_rp'  => $stockHeader->famountpajak_rp,
                        'famountmt'        => $stockHeader->famountmt,
                        'famountmt_rp'     => $stockHeader->famountmt_rp,
                        'famountremain'    => $stockHeader->famountremain,
                        'famountremain_rp' => $stockHeader->famountremain_rp,
                        'frefno'           => $stockHeader->frefno,
                        'frefpo'           => $stockHeader->frefpo,
                        'ffrom'            => $stockHeader->ffrom,
                        'fto'              => $stockHeader->fto,
                        'fkirim'           => $stockHeader->fkirim,
                        'fprdjadi'         => $stockHeader->fprdjadi,
                        'fqtyjadi'         => $stockHeader->fqtyjadi,
                        'fket'             => $stockHeader->fket,
                        'fincludeppn'      => $stockHeader->fincludeppn,
                        'fppnpersen'       => $stockHeader->fppnpersen,
                        'fapplyppn'        => $stockHeader->fapplyppn,
                        'fketinternal'     => $stockHeader->fketinternal,
                        'fusercreate'      => $stockHeader->fusercreate,
                        'fdatetime'        => $stockHeader->fdatetime,
                        'fuserupdate'      => $stockHeader->fuserupdate,
                        'feditmode'        => 'D',
                        'fuseridlog'       => $userIdLog,
                        'fdatetimelog'     => $now,
                    ]);

                    // Log trstockdt (Delete)
                    $stockDetails = DB::table('trstockdt')->where('fstockmtno', $fstockmtno)->get();
                    foreach ($stockDetails as $sdt) {
                        DB::table('log_trstockdt')->insert([
                            'ftrxlogid'     => $trxLogId,
                            'fstockdtid'    => $sdt->fstockdtid,
                            'fstockmtcode'  => $sdt->fstockmtcode,
                            'fstockmtno'    => $sdt->fstockmtno,
                            'fprdcode'      => $sdt->fprdcode,
                            'frefdtno'      => $sdt->frefdtno,
                            'fqty'          => $sdt->fqty,
                            'fqtyremain'    => $sdt->fqtyremain,
                            'fsatuan'       => $sdt->fsatuan,
                            'fqtykecil'     => $sdt->fqtykecil,
                            'fprice'        => $sdt->fprice,
                            'fprice_rp'     => $sdt->fprice_rp,
                            'ftotprice'     => $sdt->ftotprice,
                            'ftotprice_rp'  => $sdt->ftotprice_rp,
                            'fketdt'        => $sdt->fketdt,
                            'fcode'         => $sdt->fcode,
                            'frefso'        => $sdt->frefso,
                            'fdesc'         => $sdt->fdesc,
                            'fclosedt'      => $sdt->fclosedt,
                            'fdiscpersen'   => $sdt->fdiscpersen,
                            'fbiaya'        => $sdt->fbiaya,
                            'fpricenet'     => $sdt->fpricenet,
                            'fnoacak'       => $sdt->fnoacak,
                            'frefnoacak'    => $sdt->frefnoacak,
                            'frefnoacak_so' => $sdt->frefnoacak_so,
                            'fusercreate'   => $sdt->fusercreate,
                            'fdatetime'     => $sdt->fdatetime,
                            'fupdatedat'    => $sdt->fupdatedat,
                            'fuserupdate'   => $sdt->fuserupdate,
                            'feditmode'     => 'D',
                            'fuseridlog'    => $userIdLog,
                            'fdatetimelog'  => $now,
                        ]);
                    }

                    DB::table('trstockdt')
                        ->where('fstockmtno', $fstockmtno)
                        ->delete();
                    DB::table('trstockmt')->where('fstockmtid', $stockHeader->fstockmtid)->delete();
                }

                $this->deleteReturPenjualanJournalEntries($fsono);

                // 4. Delete header (tranmt)
                $returpenjualan->delete();
            });

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Retur penjualan berhasil dihapus.',
                    'redirect_url' => route('returpenjualan.index'),
                ]);
            }

            return redirect()->route('returpenjualan.index')->with('success', 'Retur penjualan berhasil dihapus.');
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Retur penjualan belum bisa dihapus. Coba lagi: ' . $e->getMessage(),
                ], 500);
            }
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

        if (round($grandTotal, 2) <= 0) {
            return;
        }

        // Jika transaksi berasal dari SRJ (Surat Retur Penjualan), tidak usah dibuatkan jurnal
        $hasSrjReference = DB::table('trandt')
            ->where('fsono', $fsono)
            ->whereNotNull('frefsrj')
            ->whereRaw("TRIM(COALESCE(frefsrj, '')) != ''")
            ->exists();

        if (! $hasSrjReference && request()->filled('frefcode_global')) {
            $hasSrjReference = strtoupper(trim((string) request()->input('frefcode_global'))) === 'SRJ';
        }

        if (! $hasSrjReference && request()->has('frefsrj')) {
            $srjInput = (array) request()->input('frefsrj', []);
            $hasSrjReference = collect($srjInput)->contains(fn ($val) => trim((string) $val) !== '');
        }

        if ($hasSrjReference) {
            return;
        }

        // --- Lookup accounts from set_account table ---
        $setAccounts = DB::table('set_account')
            ->whereIn('faccount_name', ['RETURPENJUALAN', 'PPNJUAL', 'RETJUALBLMPOTPIUTANG', 'RETURUANGMUKA', 'UANGMUKAPENJUALAN'])
            ->pluck('faccount', 'faccount_name');

        $accountReturnSales           = $setAccounts->get('RETURPENJUALAN');
        $accountReturnUM              = $setAccounts->get('RETURUANGMUKA') ?: $setAccounts->get('UANGMUKAPENJUALAN');
        $accountPPNSales              = $setAccounts->get('PPNJUAL');
        $accountReturnSalesPiutang    = $setAccounts->get('RETJUALBLMPOTPIUTANG');

        $returPenjualan = DB::table('tranmt')->where('fsono', $fsono)->first();
        $isUangMuka = (int) ($returPenjualan->ftypesales ?? 0) !== 0
            || DB::table('trandt')->where('fsono', $fsono)->whereRaw("UPPER(TRIM(COALESCE(fprdcode, ''))) = 'UM'")->exists();
        $targetDebitAccount = $isUangMuka ? ($accountReturnUM ?: $accountReturnSales) : $accountReturnSales;
        $accountNote = $isUangMuka ? 'Retur Uang Muka' : 'Retur Penjualan';

        $trCode = $isUangMuka ? 'RUJ' : 'REJ';
        $fjurnaltype = 'JRJ';
        $hasPpn = (string) ($returPenjualan->fapplyppn ?? '0') === '1' || (string) ($returPenjualan->fincludeppn ?? '0') === '1';
        $sep = $hasPpn ? '.' : '/';
        $jurnalPrefix = sprintf('JV%s%s%s%s%s%s%s', $sep, $trCode, $sep, $kodeCabang, $sep, $fsodate->format('y') . $fsodate->format('m'), $sep);

        if (DB::getDriverName() === 'pgsql') {
            $lockKey = crc32('JURNAL|' . $trCode . '|' . $kodeCabang . '|' . $fsodate->format('y-m'));
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
        $now = now();
        $customerName = Customer::where('fcustomercode', $fcustno)->value('fcustomername') ?: $fcustno;

        $jurnalId = DB::table('jurnalmt')->insertGetId([
            'fbranchcode' => $kodeCabang,
            'fjurnalno'   => $fjurnalno,
            'fjurnaltype' => $fjurnaltype,
            'fjurnaldate' => $fsodate,
            'fjurnalnote' => $customerName,
            'fbalance'    => round($grandTotal, 2),
            'fbalance_rp' => round($grandTotal, 2),
            'fdatetime'   => $now,
            'fuserid'     => $userid,
        ], 'fjurnalmtid');

        // Line 1: Debit – Retur Penjualan / Retur Uang Muka (net price before tax, from set_account)
        $jurnalDt = [
            [
                'fjurnalmtid'  => $jurnalId,
                'fbranchcode'  => $kodeCabang,
                'fjurnaltype'  => $fjurnaltype,
                'fjurnalno'    => $fjurnalno,
                'flineno'      => 1,
                'faccount'     => (string) $targetDebitAccount,
                'fdk'          => 'D',
                'fsubaccount'  => $fcustno,
                'frefno'       => $fsono,
                'frate'        => 1.0,
                'famount'      => round($subtotal, 2),
                'famount_rp'   => round($subtotal, 2),
                'faccountnote' => $accountNote,
                'fusercreate'  => $userid,
                'fdatetime'    => $now,
            ],
            // Line 2 or 3: Kredit – Kurangi Piutang Usaha (grand total, from set_account: ReturnSalesBillowPotPiutang)
            [
                'fjurnalmtid'  => $jurnalId,
                'fbranchcode'  => $kodeCabang,
                'fjurnaltype'  => $fjurnaltype,
                'fjurnalno'    => $fjurnalno,
                'flineno'      => ($ppnAmount > 0 ? 3 : 2),
                'faccount'     => (string) $accountReturnSalesPiutang,
                'fdk'          => 'K',
                'fsubaccount'  => $fcustno,
                'frefno'       => $fsono,
                'frate'        => 1.0,
                'famount'      => round($grandTotal, 2),
                'famount_rp'   => round($grandTotal, 2),
                'faccountnote' => 'Kurangi Piutang Usaha',
                'fusercreate'  => $userid,
                'fdatetime'    => $now,
            ],
        ];

        // Line 2: Debit – PPN Sales (only if tax > 0, from set_account: PPNSales)
        if ($ppnAmount > 0) {
            $jurnalDt[] = [
                'fjurnalmtid'  => $jurnalId,
                'fbranchcode'  => $kodeCabang,
                'fjurnaltype'  => $fjurnaltype,
                'fjurnalno'    => $fjurnalno,
                'flineno'      => 2,
                'faccount'     => (string) $accountPPNSales,
                'fdk'          => 'D',
                'fsubaccount'  => $fcustno,
                'frefno'       => $fsono,
                'frate'        => 1.0,
                'famount'      => round($ppnAmount, 2),
                'famount_rp'   => round($ppnAmount, 2),
                'faccountnote' => 'PPN Penjualan',
                'fusercreate'  => $userid,
                'fdatetime'    => $now,
            ];
        }

        DB::table('jurnaldt')->insert($jurnalDt);
    }

    private function deleteReturPenjualanJournalEntries(string $fsono): void
    {
        $jurnalIds = DB::table('jurnaldt')
            ->where('frefno', $fsono)
            ->whereIn('fjurnaltype', ['JRJ', 'RUJ'])
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
