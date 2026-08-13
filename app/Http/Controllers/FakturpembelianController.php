<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ProductBrowseHelper;
use App\Models\PenerimaanPembelianDetail;
use App\Models\PenerimaanPembelianHeader;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Tr_pod;
use App\Models\Tr_poh;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class FakturpembelianController extends Controller
{
    use ProductBrowseHelper;

    private const DAILY_CREATE_LIMIT = 15;

    private function todayCreateCount(): int
    {
        return PenerimaanPembelianHeader::where('fstockmtcode', 'BUY')
            ->whereBetween('fdatetime', [now()->startOfDay(), now()->endOfDay()])
            ->count();
    }

    private function hasReachedDailyCreateLimit(): bool
    {
        return $this->todayCreateCount() >= self::DAILY_CREATE_LIMIT;
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
            ->select('d.fprice', 'd.fsatuan', 'd.fdiscpersen')
            ->first();
    }

    private function latestPurchaseHistoryAnySupplier(string $productCode, string $unit): ?object
    {
        return DB::table('trstockmt as m')
            ->join('trstockdt as d', 'm.fstockmtno', '=', 'd.fstockmtno')
            ->where('m.fstockmtcode', 'BUY')
            ->whereRaw('TRIM(d.fprdcode) = ?', [$productCode])
            ->whereRaw('TRIM(d.fsatuan) = ?', [$unit])
            ->orderByDesc('m.fstockmtdate')
            ->select('d.fprice', 'd.fsatuan', 'd.fdiscpersen')
            ->first();
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

    private function calculateProductHpp(string $productCode, string $unit): float
    {
        $product = DB::table('msprd')
            ->whereRaw('TRIM(fprdcode) = ?', [$productCode])
            ->first([
                'fhpp',
                'fsatuankecil',
                'fsatuanbesar',
                'fsatuanbesar2',
                'fqtykecil',
                'fqtykecil2',
            ]);

        if (! $product) {
            return 0.0;
        }

        $baseHpp = (float) ($product->fhpp ?? 0);
        $unitUpper = strtoupper(trim($unit));
        $smallUnit = strtoupper(trim((string) ($product->fsatuankecil ?? '')));
        $largeUnit = strtoupper(trim((string) ($product->fsatuanbesar ?? '')));
        $largeUnit2 = strtoupper(trim((string) ($product->fsatuanbesar2 ?? '')));

        if ($unitUpper === $smallUnit || $unitUpper === '') {
            return $baseHpp;
        }
        if ($unitUpper === $largeUnit) {
            $ratio = (float) ($product->fqtykecil ?? 1);
            return $baseHpp * ($ratio > 0 ? $ratio : 1);
        }
        if ($unitUpper === $largeUnit2) {
            $ratio = (float) ($product->fqtykecil2 ?? 1);
            return $baseHpp * ($ratio > 0 ? $ratio : 1);
        }

        return $baseHpp;
    }


    private function getReferenceUnitMaps($details): array
    {
        $detailRows = collect($details);

        $poIds = $detailRows
            ->filter(fn($detail) => (int) ($detail->frefdtid ?? 0) > 0 && trim((string) ($detail->frefso ?? '')) !== '')
            ->map(fn($detail) => (int) $detail->frefdtid)
            ->unique()
            ->values()
            ->all();

        $pbIds = $detailRows
            ->filter(fn($detail) => (int) ($detail->frefdtid ?? 0) > 0 && trim((string) ($detail->frefso ?? '')) === '')
            ->map(fn($detail) => (int) $detail->frefdtid)
            ->unique()
            ->values()
            ->all();

        $poUnits = empty($poIds)
            ? []
            : DB::table('tr_pod')
            ->whereIn('fpodid', $poIds)
            ->pluck('fsatuan', 'fpodid')
            ->map(fn($value) => trim((string) $value))
            ->all();

        $pbUnits = empty($pbIds)
            ? []
            : DB::table('trstockdt')
            ->whereIn('fstockdtid', $pbIds)
            ->pluck('fsatuan', 'fstockdtid')
            ->map(fn($value) => trim((string) $value))
            ->all();

        return [$poUnits, $pbUnits];
    }

    private function resolveDetailDisplayUnit($detail, array $poUnits = [], array $pbUnits = []): string
    {
        return trim((string) ($detail->fsatuan ?? ''));
    }

    private function getSupplierAdvanceWarningMap(): array
    {
        $documentsBySupplier = DB::table('trsisadp_pembelian')
            ->selectRaw('TRIM(COALESCE(fsupplier, \'\')) as fsupplier')
            ->addSelect(['fstockmtno', 'fstockmtdate', 'fsisadp', 'fsisadp_rp'])
            ->where(function ($query) {
                $query->where('fsisadp', '>', 0)
                    ->orWhere('fsisadp_rp', '>', 0);
            })
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
            ->where(function ($query) {
                $query->where('fsisadp', '>', 0)
                    ->orWhere('fsisadp_rp', '>', 0);
            })
            ->groupBy(DB::raw('TRIM(COALESCE(fsupplier, \'\'))'))
            ->get()
            ->filter(fn($row) => trim((string) ($row->fsupplier ?? '')) !== '')
            ->mapWithKeys(function ($row) use ($documentsBySupplier) {
                $supplierCode = trim((string) ($row->fsupplier ?? ''));
                $remainRp = (float) ($row->total_remain_rp ?? 0);

                return [
                    $supplierCode => [
                        'message' => $remainRp > 0
                            ? 'Supplier ini memiliki DP sebesar ' . number_format($remainRp, 2, ',', '.') . '.'
                            : 'Supplier ini memiliki DP.',
                        'documents' => $documentsBySupplier->get($supplierCode, collect())->values()->all(),
                    ],
                ];
            })
            ->all();
    }

    public function index(Request $request)
    {
        $canCreate = in_array('createFakturPembelian', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updateFakturPembelian', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteFakturPembelian', explode(',', session('user_restricted_permissions', '')));
        $canPrint = in_array('printFakturPembelian', explode(',', session('user_restricted_permissions', '')));
        $showActionsColumn = $canEdit || $canDelete || $canPrint;

        $year = $request->query('year');
        $month = $request->query('month');
        $createLimitReached = $this->hasReachedDailyCreateLimit();

        $availableYearsQuery = PenerimaanPembelianHeader::selectRaw('DISTINCT EXTRACT(YEAR FROM fdatetime) as year')
            ->where('fstockmtcode', 'BUY')
            ->whereNotNull('fdatetime');
        $this->applyBranchVisibilityScope($availableYearsQuery, 'trstockmt.fbranchcode');
        $availableYears = $availableYearsQuery
            ->orderByRaw('EXTRACT(YEAR FROM fdatetime) DESC')
            ->pluck('year');

        if ($request->ajax()) {
            $referenceSub = DB::table('trstockdt')
                ->select('fstockmtno')
                ->selectRaw("string_agg(DISTINCT NULLIF(TRIM(COALESCE(frefdtno::text, '')), ''), ', ' ORDER BY NULLIF(TRIM(COALESCE(frefdtno::text, '')), '')) as frefdtno_summary")
                ->where('fstockmtcode', 'BUY')
                ->groupBy('fstockmtno');

            $query = PenerimaanPembelianHeader::query()
                ->where('trstockmt.fstockmtcode', 'BUY')
                ->leftJoin('mssupplier', 'trstockmt.fsupplier', '=', 'mssupplier.fsuppliercode')
                ->leftJoin('mswh', 'trstockmt.ffrom', '=', 'mswh.fwhcode')
                ->leftJoinSub($referenceSub, 'refdt', function ($join) {
                    $join->on('refdt.fstockmtno', '=', 'trstockmt.fstockmtno');
                });
            $this->applyBranchVisibilityScope($query, 'trstockmt.fbranchcode');
            $totalRecords = (clone $query)->count();
            if ($search = trim((string) $request->input('search.value'))) {
                $likeOp = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
                $query->where(function ($q) use ($search, $likeOp) {
                    $q->where('trstockmt.fstockmtno', $likeOp, "%{$search}%")
                        ->orWhere('trstockmt.frefno', $likeOp, "%{$search}%")
                        ->orWhere('trstockmt.frefpo', $likeOp, "%{$search}%")
                        ->orWhere('refdt.frefdtno_summary', $likeOp, "%{$search}%")
                        ->orWhere('trstockmt.ffrom', $likeOp, "%{$search}%")
                        ->orWhere('trstockmt.fbranchcode', $likeOp, "%{$search}%")
                        ->orWhere('trstockmt.fusercreate', $likeOp, "%{$search}%")
                        ->orWhere('mssupplier.fsuppliername', $likeOp, "%{$search}%")
                        ->orWhere('mssupplier.fsuppliercode', $likeOp, "%{$search}%");
                });
            }

            // Pencarian per kolom
            $colSearchGudang = $request->input('columns.5.search.value');
            if ($colSearchGudang !== null && $colSearchGudang !== '') {
                $likeOp = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
                $query->where(function ($q) use ($colSearchGudang, $likeOp) {
                    $q->where('trstockmt.ffrom', $likeOp, "%{$colSearchGudang}%")
                        ->orWhere('mswh.fwhname', $likeOp, "%{$colSearchGudang}%");
                });
            }

            $colSearchSupplier = $request->input('columns.6.search.value');
            if ($colSearchSupplier !== null && $colSearchSupplier !== '') {
                $likeOp = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
                $query->where(function ($q) use ($colSearchSupplier, $likeOp) {
                    $q->where('mssupplier.fsuppliername', $likeOp, "%{$colSearchSupplier}%")
                        ->orWhere('mssupplier.fsuppliercode', $likeOp, "%{$colSearchSupplier}%");
                });
            }

            if ($year) {
                $query->whereRaw('EXTRACT(YEAR FROM trstockmt.fdatetime) = ?', [$year]);
            }
            if ($month) {
                $query->whereRaw('EXTRACT(MONTH FROM trstockmt.fdatetime) = ?', [$month]);
            }
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
                } elseif ($colName === 'ftypebuy') {
                    $orderColumn = 'trstockmt.ftypebuy';
                } elseif ($colName === 'ffakturno') {
                    $orderColumn = 'trstockmt.frefno';
                } elseif ($colName === 'fgudang') {
                    $orderColumn = 'trstockmt.ffrom';
                } elseif ($colName === 'fsuppliername') {
                    $orderColumn = 'mssupplier.fsuppliername';
                } elseif ($colName === 'freferensi') {
                    $orderColumn = 'refdt.frefdtno_summary';
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
            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            $records = $query->skip($start)
                ->take($length)
                ->get([
                    'trstockmt.fstockmtid',
                    'trstockmt.fstockmtno',
                    'trstockmt.fapplyppn',
                    'trstockmt.fstockmtdate',
                    'trstockmt.ftypebuy',
                    'trstockmt.frefno',
                    'trstockmt.frefpo',
                    'trstockmt.famountmt',
                    'trstockmt.ffrom',
                    'trstockmt.fbranchcode',
                    'trstockmt.fusercreate',
                    'mswh.fwhname',
                    'mssupplier.fsuppliername',
                    'refdt.frefdtno_summary',
                ]);

            $data = $records->map(function ($row) {
                $warehouseCode = trim((string) ($row->ffrom ?? ''));

                return [
                    'fstockmtid' => $row->fstockmtid,
                    'fstockmtno' => $row->fstockmtno,
                    'fstockmtno_display' => $this->formatDisplayTransactionNumber($row->fstockmtno, (int) ($row->fapplyppn ?? 0) === 0 && (int) ($row->fincludeppn ?? 0) === 0),
                    'fstockmtdate' => $row->fstockmtdate
                        ? ($row->fstockmtdate instanceof \Carbon\Carbon ? $row->fstockmtdate : \Carbon\Carbon::parse($row->fstockmtdate))->format('d-m-Y')
                        : '',
                    'ftypebuy' => $row->ftypebuy,
                    'ffakturno' => trim((string) ($row->frefno ?? '')),
                    'fgudang' => $warehouseCode,
                    'fbranchcode' => trim((string) ($row->fbranchcode ?? '')),
                    'fsuppliername' => trim((string) ($row->fsuppliername ?? '')),
                    'freferensi' => trim((string) ($row->frefdtno_summary ?? '')),
                    'fusercreate' => trim((string) ($row->fusercreate ?? '')),
                    'famountmt' => (float) ($row->famountmt ?? 0),
                ];
            });

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        }

        return view('fakturpembelian.index', compact(
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

    public function pickablePO(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $supplierCode = trim((string) $request->get('supplier_code', ''));
        $perPage = max(1, (int) $request->get('length', $request->get('per_page', 10)));
        $start = max(0, (int) $request->get('start', 0));
        $draw = (int) $request->get('draw', 0);

        $terSub = DB::table('trstockdt')
            ->selectRaw('fprdcode, frefdtno, SUM(COALESCE(fqtykecil, 0)) AS fqtyterima')
            ->where(function ($q) {
                $q->where('fstockmtcode', 'TER')
                    ->orWhere(function ($qq) {
                        $qq->where('fcode', 'P')
                            ->where('fstockmtcode', 'BUY');
                    });
            })
            ->groupBy('frefdtno', 'fprdcode');

        $query = Tr_poh::query()
            ->leftJoin('mssupplier', 'tr_poh.fsupplier', '=', 'mssupplier.fsuppliercode')
            ->select([
                'tr_poh.fpohid',
                'tr_poh.fpono',
                'mssupplier.fsuppliername',
                'tr_poh.fpodate',
            ])
            ->where('tr_poh.fprdin', '0')
            ->where('tr_poh.fapproval', 1);

        if ($supplierCode !== '') {
            $query->where('tr_poh.fsupplier', $supplierCode);
        }

        $recordsTotal = (clone $query)->count();

        if ($search !== '') {
            $likeOp = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
            $query->where(function ($q) use ($search, $likeOp) {
                $q->where('tr_poh.fpono', $likeOp, "%{$search}%")
                    ->orWhere('mssupplier.fsuppliername', $likeOp, "%{$search}%")
                    ->orWhereRaw("TO_CHAR(tr_poh.fpodate, 'YYYY-MM-DD HH24:MI:SS') {$likeOp} ?", ["%{$search}%"]);
            });
        }

        $recordsFiltered = (clone $query)->count();

        $query->orderByDesc('tr_poh.fpodate')
            ->orderByDesc('tr_poh.fpohid');

        $rows = $query->skip($start)->take($perPage)->get()->map(function ($t) {
            return [
                'fpohid' => $t->fpohid,
                'fpono' => $t->fpono,
                'fsupplier' => trim($t->fsuppliername ?? ''),
                'fpodate' => $t->fpodate ? \Carbon\Carbon::parse($t->fpodate)->format('Y-m-d H:i:s') : 'No Date',
                'items_url' => route('fakturpembelian.itemsPO', $t->fpohid),
            ];
        });

        return response()->json([
            'data' => $rows,
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
        ]);
    }

    public function itemsPO($id)
    {
        $header = Tr_poh::where('fpohid', $id)
            ->where('fprdin', '0')
            ->firstOrFail();
        $terSub = DB::table('trstockdt')
            ->selectRaw('fprdcode, frefdtno, SUM(COALESCE(fqtykecil, 0)) AS fqtyterima')
            ->where(function ($q) {
                $q->where('fstockmtcode', 'TER')
                    ->orWhere(function ($qq) {
                        $qq->where('fcode', 'P')
                            ->where('fstockmtcode', 'BUY');
                    });
            })
            ->groupBy('frefdtno', 'fprdcode');

        $items = Tr_pod::query()
            ->where('tr_pod.fpono', $header->fpono)
            ->leftJoin('msprd as m', 'm.fprdcode', '=', 'tr_pod.fprdcode')
            ->leftJoinSub($terSub, 'ter', function ($join) {
                $join->on('ter.frefdtno', '=', 'tr_pod.fpono')
                    ->on('ter.fprdcode', '=', 'tr_pod.fprdcode');
            })
            ->select([
                'tr_pod.fpodid as frefdtid',
                DB::raw('tr_pod.fpono as frefdtno'),
                'tr_pod.fprdcode as fitemcode',
                'm.fprdname as fitemname',
                'tr_pod.fdesc',
                'tr_pod.fsatuan as fsatuan',
                'tr_pod.fprice',
                'tr_pod.fdisc',
                'tr_pod.famount as fbiaya',
                'tr_pod.fpricenet as fharga',
                DB::raw("COALESCE(tr_pod.fnoacak::text, '') as frefnoacak"),
                DB::raw('COALESCE(tr_pod.fqtykecil, 0) as fqtypo'),
                DB::raw('COALESCE(ter.fqtyterima, 0) as fqtyterima'),
                DB::raw("COALESCE(
                    CASE
                        WHEN tr_pod.fsatuan = m.fsatuanbesar
                            THEN (COALESCE(tr_pod.fqtykecil, 0) - COALESCE(ter.fqtyterima, 0)) / NULLIF(m.fqtykecil, 0)
                        WHEN tr_pod.fsatuan = m.fsatuanbesar2
                            THEN (COALESCE(tr_pod.fqtykecil, 0) - COALESCE(ter.fqtyterima, 0)) / NULLIF(m.fqtykecil2, 0)
                        ELSE COALESCE(tr_pod.fqtykecil, 0) - COALESCE(ter.fqtyterima, 0)
                    END, 0) as fqtysisa"),
                DB::raw('COALESCE(tr_pod.fqtykecil, 0) - COALESCE(ter.fqtyterima, 0) as fqtyremain'),
                DB::raw('0::numeric as fdiskon'),
            ])
            ->orderBy('tr_pod.fpodid')
            ->get()
            ->map(function ($item) {
                $item->fqty = (float) ($item->fqtysisa ?? 0);
                $item->fqtyremain = (float) ($item->fqtyremain ?? 0);
                $item->fqtykecil = $item->fqtyremain;

                return $item;
            });

        return response()->json([
            'header' => [
                'fpohid' => $header->fpohid,
                'fpono' => $header->fpono,
                'fsupplier' => trim($header->fsupplier ?? ''),
                'fpodate' => optional($header->fpodate)->format('Y-m-d H:i:s'),
                'ftempohr' => (int) ($header->ftempohr ?? 0),
                'fapplyppn' => (int) ($header->fapplyppn ?? 0),
                'fincludeppn' => (int) ($header->fincludeppn ?? 0),
                'fppnpersen' => (float) ($header->fppnpersen ?? 0),
            ],
            'items' => $items,
        ]);
    }

    public function pickablePB(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $supplierCode = trim((string) $request->get('supplier_code', ''));
        $perPage = max(1, (int) $request->get('length', $request->get('per_page', 10)));
        $start = max(0, (int) $request->get('start', 0));
        $draw = (int) $request->get('draw', 0);

        $refdtQuery = DB::table('trstockdt')
            ->select('fstockmtno')
            ->selectRaw("string_agg(DISTINCT NULLIF(TRIM(COALESCE(frefdtno::text, '')), ''), ', ' ORDER BY NULLIF(TRIM(COALESCE(frefdtno::text, '')), '')) as frefdtno_summary")
            ->where('fstockmtcode', 'TER')
            ->groupBy('fstockmtno');

        $query = PenerimaanPembelianHeader::query()
            ->leftJoin('mssupplier', 'trstockmt.fsupplier', '=', 'mssupplier.fsuppliercode')
            ->leftJoinSub($refdtQuery, 'refdt', function ($join) {
                $join->on('refdt.fstockmtno', '=', 'trstockmt.fstockmtno');
            })
            ->select([
                'trstockmt.fstockmtid',
                'trstockmt.fstockmtno',
                'trstockmt.frefpo',
                'trstockmt.fbranchcode',
                'mssupplier.fsuppliername',
                'trstockmt.fstockmtdate',
                'refdt.frefdtno_summary',
                'trstockmt.ffrom',
            ])
            ->where('trstockmt.fstockmtcode', 'TER')
            ->where('trstockmt.fprdout', '0');

        if ($supplierCode !== '') {
            $query->where('trstockmt.fsupplier', $supplierCode);
        }

        $recordsTotal = (clone $query)->count();

        if ($search !== '') {
            $likeOp = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
            $query->where(function ($q) use ($search, $likeOp) {
                $q->where('trstockmt.fstockmtno', $likeOp, "%{$search}%")
                    ->orWhere('trstockmt.frefpo', $likeOp, "%{$search}%")
                    ->orWhere('refdt.frefdtno_summary', $likeOp, "%{$search}%")
                    ->orWhere('trstockmt.fbranchcode', $likeOp, "%{$search}%")
                    ->orWhere('trstockmt.ffrom', $likeOp, "%{$search}%")
                    ->orWhere('mssupplier.fsuppliername', $likeOp, "%{$search}%")
                    ->orWhereRaw("TO_CHAR(trstockmt.fstockmtdate, 'YYYY-MM-DD HH24:MI:SS') {$likeOp} ?", ["%{$search}%"]);
            });
        }

        $recordsFiltered = (clone $query)->count();

        $query->orderByDesc('trstockmt.fstockmtdate')
            ->orderByDesc('trstockmt.fstockmtid');

        $rows = $query->skip($start)->take($perPage)->get()->map(function ($t) {
            $poNo = trim((string) ($t->frefdtno_summary ?? ''));
            if ($poNo === '') {
                $poNo = trim((string) ($t->frefpo ?? ''));
            }
            return [
                'fstockmtid' => $t->fstockmtid,
                'fstockmtno' => $t->fstockmtno,
                'frefpo' => $poNo !== '' ? $poNo : '-',
                'fbranchcode' => trim($t->fbranchcode ?? ''),
                'fgudang' => trim($t->ffrom ?? ''),
                'fsupplier' => trim($t->fsuppliername ?? ''),
                'fstockmtdate' => $t->fstockmtdate ? \Carbon\Carbon::parse($t->fstockmtdate)->format('Y-m-d H:i:s') : 'No Date',
                'items_url' => route('fakturpembelian.itemsPB', $t->fstockmtid),
            ];
        });

        return response()->json([
            'data' => $rows,
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
        ]);
    }

    public function itemsPB($id)
    {
        $header = PenerimaanPembelianHeader::where('fstockmtid', $id)
            ->where('fstockmtcode', 'TER')
            ->where('fprdout', '0')
            ->firstOrFail();
        $buySub = DB::table('trstockdt')
            ->selectRaw('frefdtno, fprdcode, SUM(COALESCE(fqtykecil, 0)) AS fqtybuy')
            ->where('fstockmtcode', 'BUY')
            ->groupBy('frefdtno', 'fprdcode');

        $items = PenerimaanPembelianDetail::query()
            ->where('trstockdt.fstockmtno', $header->fstockmtno)
            ->where('trstockdt.fstockmtcode', 'TER')
            ->leftJoin('trstockmt as hdr', 'hdr.fstockmtno', '=', 'trstockdt.fstockmtno')
            ->leftJoin('tr_poh as po', 'po.fpono', '=', 'trstockdt.frefso')
            ->leftJoin('msprd as m', 'm.fprdcode', '=', 'trstockdt.fprdcode')
            ->leftJoinSub($buySub, 'buy', function ($join) {
                $join->on('buy.frefdtno', '=', 'trstockdt.fstockmtno')
                    ->on('buy.fprdcode', '=', 'trstockdt.fprdcode');
            })
            ->select([
                'trstockdt.fstockdtid as frefdtid',
                'trstockdt.frefdtno',
                'trstockdt.fprdcode as fitemcode',
                'm.fprdname as fitemname',
                'hdr.fsupplier',
                'trstockdt.fdesc',
                'trstockdt.fsatuan as fsatuan',
                'trstockdt.fprice',
                'trstockdt.fdiscpersen',
                'trstockdt.fbiaya',
                'trstockdt.ftotprice as fharga',
                'trstockdt.frefso',
                'po.fapplyppn',
                'po.fincludeppn',
                'po.fppnpersen',
                'po.famountpopajak',
                'po.ftempohr',
                DB::raw("TRIM(BOTH ', ' FROM CONCAT_WS(', ', NULLIF(TRIM(COALESCE(trstockdt.frefnoacak::text, '')), ''), NULLIF(TRIM(COALESCE(trstockdt.fnoacak::text, '')), ''))) as frefnoacak"),
                DB::raw('COALESCE(trstockdt.fqtykecil, 0) as fqtykecil'),
                DB::raw('COALESCE(buy.fqtybuy, 0) as fqtybuy'),
                DB::raw("COALESCE(
                    CASE
                        WHEN trstockdt.fsatuan = m.fsatuanbesar
                            THEN (COALESCE(trstockdt.fqtykecil, 0) - COALESCE(buy.fqtybuy, 0)) / NULLIF(m.fqtykecil, 0)
                        WHEN trstockdt.fsatuan = m.fsatuanbesar2
                            THEN (COALESCE(trstockdt.fqtykecil, 0) - COALESCE(buy.fqtybuy, 0)) / NULLIF(m.fqtykecil2, 0)
                        ELSE COALESCE(trstockdt.fqtykecil, 0) - COALESCE(buy.fqtybuy, 0)
                    END, 0) as fqtysisa"),
                DB::raw('COALESCE(trstockdt.fqtykecil, 0) - COALESCE(buy.fqtybuy, 0) as fqtyremain'),
                DB::raw('0::numeric as fdiskon'),
            ])
            ->orderBy('trstockdt.fstockdtid')
            ->get()
            ->map(function ($item) {
                $item->fqty = (float) ($item->fqtysisa ?? 0);
                $item->fqtyremain = (float) ($item->fqtyremain ?? 0);
                $item->fqtykecil = $item->fqtyremain;

                return $item;
            });

        return response()->json([
            'header' => [
                'fstockmtid' => $header->fstockmtid,
                'fstockmtno' => $header->fstockmtno,
                'frefpo' => trim($header->frefpo ?? ''),
                'fsupplier' => trim($header->fsupplier ?? ''),
                'ffrom' => trim($header->ffrom ?? ''),
                'fgudang' => trim($header->ffrom ?? ''),
                'fstockmtdate' => optional($header->fstockmtdate)->format('Y-m-d H:i:s'),
                'fapplyppn' => (int) ($items->first()->fapplyppn ?? 0),
                'fincludeppn' => (int) ($items->first()->fincludeppn ?? 0),
                'fppnpersen' => (float) ($items->first()->fppnpersen ?? 0),
                'famountpopajak' => (float) ($items->first()->famountpopajak ?? 0),
                'ftempohr' => (int) ($items->first()->ftempohr ?? 0),
            ],
            'items' => $items,
        ]);
    }

    private function qtyKecilToSourceUnit(?object $row, float $qtyKecil): float
    {
        if (! $row) {
            return $qtyKecil;
        }

        $sat = trim((string) ($row->fsatuan ?? ''));
        $satBesar = trim((string) ($row->fsatuanbesar ?? ''));
        $satBesar2 = trim((string) ($row->fsatuanbesar2 ?? ''));
        $rasio = (float) ($row->fqtykecil_master ?? 0);
        $rasio2 = (float) ($row->fqtykecil2_master ?? 0);

        if ($sat !== '' && $satBesar !== '' && $sat === $satBesar && $rasio > 0) {
            return $qtyKecil / $rasio;
        }

        if ($sat !== '' && $satBesar2 !== '' && $sat === $satBesar2 && $rasio2 > 0) {
            return $qtyKecil / $rasio2;
        }

        return $qtyKecil;
    }

    private function qtySourceUnitToKecil(?object $row, string $sat, float $qty): float
    {
        if (! $row) {
            return $qty;
        }

        $sat = trim((string) $sat);
        $satBesar = trim((string) ($row->fsatuanbesar ?? ''));
        $satBesar2 = trim((string) ($row->fsatuanbesar2 ?? ''));
        $rasio = (float) ($row->fqtykecil_master ?? $row->fqtykecil ?? 0);
        $rasio2 = (float) ($row->fqtykecil2_master ?? $row->fqtykecil2 ?? 0);

        if ($sat !== '' && $satBesar !== '' && $sat === $satBesar && $rasio > 0) {
            return $qty * $rasio;
        }

        if ($sat !== '' && $satBesar2 !== '' && $sat === $satBesar2 && $rasio2 > 0) {
            return $qty * $rasio2;
        }

        return $qty;
    }

    private function isOpeningBalanceProductCode(?string $code): bool
    {
        return strtoupper(trim((string) $code)) === 'AWAL';
    }

    private function hasMixedOpeningBalanceAndSourceRows(array $codes, array $qtys, array $sources): bool
    {
        $hasOpeningBalance = false;
        $hasSourceReference = false;
        $rowCount = max(count($codes), count($qtys), count($sources));

        for ($i = 0; $i < $rowCount; $i++) {
            $code = trim((string) ($codes[$i] ?? ''));
            $qty = (float) ($qtys[$i] ?? 0);
            $sourceType = strtoupper(trim((string) ($sources[$i] ?? '')));

            if ($code === '' || $qty <= 0) {
                continue;
            }

            if ($this->isOpeningBalanceProductCode($code)) {
                $hasOpeningBalance = true;
            }

            if (in_array($sourceType, ['PO', 'PB'], true)) {
                $hasSourceReference = true;
            }

            if ($hasOpeningBalance && $hasSourceReference) {
                return true;
            }
        }

        return false;
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
            $messages["fitemcode.$index"] = "Kode produk {$code} tidak boleh sama dalam satu Faktur Pembelian.";
        }

        throw ValidationException::withMessages($messages);
    }

    private function getSourceRemainMap(string $sourceType, array $detailIds): array
    {
        $ids = collect($detailIds)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            return [];
        }

        if ($sourceType === 'PO') {
            $usedSub = DB::table('trstockdt')
                ->selectRaw('fprdcode, frefdtno, SUM(COALESCE(fqtykecil, 0)) AS qty_used')
                ->where(function ($q) {
                    $q->where('fstockmtcode', 'TER')
                        ->orWhere(function ($qq) {
                            $qq->where('fcode', 'P')
                                ->where('fstockmtcode', 'BUY');
                        });
                })
                ->groupBy('frefdtno', 'fprdcode');

            $rows = DB::table('tr_pod as d')
                ->leftJoin('msprd as p', 'p.fprdcode', '=', 'd.fprdcode')
                ->leftJoinSub($usedSub, 'u', function ($join) {
                    $join->on('u.frefdtno', '=', 'd.fpono')
                        ->on('u.fprdcode', '=', 'd.fprdcode');
                })
                ->whereIn('d.fpodid', $ids)
                ->select([
                    'd.fpodid as detail_id',
                    'd.fsatuan',
                    DB::raw('GREATEST(COALESCE(d.fqtykecil, 0) - COALESCE(u.qty_used, 0), 0) as total_kecil'),
                    'p.fsatuanbesar',
                    'p.fsatuanbesar2',
                    DB::raw('COALESCE(p.fqtykecil, 0) as fqtykecil_master'),
                    DB::raw('COALESCE(p.fqtykecil2, 0) as fqtykecil2_master'),
                ])
                ->get();

            return $rows->mapWithKeys(function ($row) {
                $remainKecil = max(0, (float) ($row->total_kecil ?? 0));

                return [(int) $row->detail_id => $this->qtyKecilToSourceUnit($row, $remainKecil)];
            })->all();
        }

        if ($sourceType === 'PB') {
            $usedSub = DB::table('trstockdt')
                ->selectRaw('frefdtno, fprdcode, SUM(COALESCE(fqtykecil, 0)) AS qty_used')
                ->where('fstockmtcode', 'BUY')
                ->groupBy('frefdtno', 'fprdcode');

            $rows = DB::table('trstockdt as d')
                ->leftJoin('msprd as p', 'p.fprdcode', '=', 'd.fprdcode')
                ->leftJoinSub($usedSub, 'u', function ($join) {
                    $join->on('u.frefdtno', '=', 'd.fstockmtno')
                        ->on('u.fprdcode', '=', 'd.fprdcode');
                })
                ->whereIn('d.fstockdtid', $ids)
                ->select([
                    'd.fstockdtid as detail_id',
                    'd.fsatuan',
                    DB::raw('GREATEST(COALESCE(d.fqtykecil, 0) - COALESCE(u.qty_used, 0), 0) as total_kecil'),
                    'p.fsatuanbesar',
                    'p.fsatuanbesar2',
                    DB::raw('COALESCE(p.fqtykecil, 0) as fqtykecil_master'),
                    DB::raw('COALESCE(p.fqtykecil2, 0) as fqtykecil2_master'),
                ])
                ->get();

            return $rows->mapWithKeys(function ($row) {
                $remainKecil = max(0, (float) ($row->total_kecil ?? 0));

                return [(int) $row->detail_id => $this->qtyKecilToSourceUnit($row, $remainKecil)];
            })->all();
        }

        return [];
    }

    private function getSourceRemainKecilMap(string $sourceType, array $detailIds): array
    {
        $ids = collect($detailIds)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            return [];
        }

        if ($sourceType === 'PO') {
            $usedSub = DB::table('trstockdt')
                ->selectRaw('fprdcode, frefdtno, SUM(COALESCE(fqtykecil, 0)) AS qty_used')
                ->where(function ($q) {
                    $q->where('fstockmtcode', 'TER')
                        ->orWhere(function ($qq) {
                            $qq->where('fcode', 'P')
                                ->where('fstockmtcode', 'BUY');
                        });
                })
                ->groupBy('frefdtno', 'fprdcode');

            return DB::table('tr_pod as d')
                ->leftJoinSub($usedSub, 'u', function ($join) {
                    $join->on('u.frefdtno', '=', 'd.fpono')
                        ->on('u.fprdcode', '=', 'd.fprdcode');
                })
                ->whereIn('d.fpodid', $ids)
                ->selectRaw('d.fpodid as detail_id, COALESCE(d.fqtykecil, 0) as remain_kecil')
                ->pluck('remain_kecil', 'detail_id')
                ->map(fn($value) => (float) $value)
                ->all();
        }

        if ($sourceType === 'PB') {
            $usedSub = DB::table('trstockdt')
                ->selectRaw('frefdtno, fprdcode, SUM(COALESCE(fqtykecil, 0)) AS qty_used')
                ->where('fstockmtcode', 'BUY')
                ->groupBy('frefdtno', 'fprdcode');

            return DB::table('trstockdt as d')
                ->leftJoinSub($usedSub, 'u', function ($join) {
                    $join->on('u.frefdtno', '=', 'd.fstockmtno')
                        ->on('u.fprdcode', '=', 'd.fprdcode');
                })
                ->whereIn('d.fstockdtid', $ids)
                ->selectRaw('d.fstockdtid as detail_id, COALESCE(d.fqtykecil, 0) as remain_kecil')
                ->pluck('remain_kecil', 'detail_id')
                ->map(fn($value) => (float) $value)
                ->all();
        }

        return [];
    }

    private function getSourceRemain(string $sourceType, int $detailId): ?float
    {
        if ($detailId <= 0 || ! in_array($sourceType, ['PO', 'PB'], true)) {
            return null;
        }

        $remainMap = $this->getSourceRemainMap($sourceType, [$detailId]);

        return array_key_exists($detailId, $remainMap) ? (float) $remainMap[$detailId] : null;
    }

    private function detectSourceTypeByDetailId(int $detailId): ?string
    {
        if ($detailId <= 0) {
            return null;
        }

        if (DB::table('tr_pod')->where('fpodid', $detailId)->exists()) {
            return 'PO';
        }

        if (DB::table('trstockdt')->where('fstockdtid', $detailId)->exists()) {
            return 'PB';
        }

        return null;
    }

    private function normalizeReferenceRandomNumbers($value): ?string
    {
        $parts = preg_split('/\s*,\s*/', trim((string) ($value ?? ''))) ?: [];
        $normalized = [];

        foreach ($parts as $part) {
            $candidate = trim((string) $part);
            if (! preg_match('/^\d{3}$/', $candidate)) {
                continue;
            }
            if (! in_array($candidate, $normalized, true)) {
                $normalized[] = $candidate;
            }
        }

        return empty($normalized) ? null : implode(',', $normalized);
    }

    private function normalizeReferenceRandomNumberSingle($value): ?string
    {
        $parts = preg_split('/\s*,\s*/', trim((string) ($value ?? ''))) ?: [];
        $normalized = [];

        foreach ($parts as $part) {
            $candidate = trim((string) $part);
            if (preg_match('/^\d{3}$/', $candidate)) {
                $normalized[] = $candidate;
            }
        }

        if (empty($normalized)) {
            return null;
        }

        return (string) end($normalized);
    }

    private function adjustSourceQtyKecil(array $usageBySourceRef, int $direction): void {}

    private function validateSourceRemainForRows(array $codes, array $qtys, array $sources, array $refdtids, array $satuans, array $extraAvailableBySourceRef = []): \Illuminate\Support\MessageBag
    {
        $errors = new \Illuminate\Support\MessageBag;
        $tolerance = 0.00001;

        $products = DB::table('msprd')
            ->whereIn('fprdcode', array_values(array_unique(array_filter(array_map(fn($code) => trim((string) $code), $codes)))))
            ->get([
                'fprdcode',
                'fsatuankecil',
                'fsatuanbesar',
                'fsatuanbesar2',
                'fqtykecil',
                'fqtykecil2',
            ])
            ->map(function ($row) {
                $row->fqtykecil_master = $row->fqtykecil ?? 0;
                $row->fqtykecil2_master = $row->fqtykecil2 ?? 0;

                return $row;
            })
            ->keyBy('fprdcode');

        $poIds = [];
        $pbIds = [];
        foreach ($sources as $i => $sourceRaw) {
            $sourceType = strtoupper(trim((string) ($sourceRaw ?? '')));
            $detailId = (int) ($refdtids[$i] ?? 0);
            if ($detailId <= 0) {
                continue;
            }
            if ($sourceType === 'PO') {
                $poIds[] = $detailId;
            } elseif ($sourceType === 'PB') {
                $pbIds[] = $detailId;
            }
        }

        $remainKecilBySource = [
            'PO' => $this->getSourceRemainKecilMap('PO', $poIds),
            'PB' => $this->getSourceRemainKecilMap('PB', $pbIds),
        ];

        foreach ($codes as $i => $codeRaw) {
            $code = trim((string) ($codeRaw ?? ''));
            if ($code === '') {
                continue;
            }

            $sourceType = strtoupper(trim((string) ($sources[$i] ?? '')));
            $detailId = (int) ($refdtids[$i] ?? 0);
            $qty = (float) ($qtys[$i] ?? 0);

            if (! in_array($sourceType, ['PO', 'PB'], true) || $detailId <= 0) {
                continue;
            }

            if ($qty <= 0) {
                $errors->add("fqty.$i", "Qty item {$code} harus lebih dari 0.");

                continue;
            }

            $remainKecil = $remainKecilBySource[$sourceType][$detailId] ?? null;
            if ($remainKecil === null) {
                $errors->add("fqty.$i", "Referensi {$sourceType} untuk item {$code} tidak ditemukan.");

                continue;
            }

            $product = $products->get($code);
            $sat = trim((string) ($satuans[$i] ?? ''));
            $needKecil = $this->qtySourceUnitToKecil($product, $sat, $qty);
            $sourceKey = $sourceType . ':' . $detailId;
            // ponytail: compares against original source qty per scope; cumulative over-use across docs no longer blocked, restore remain_qty_kecil compare + except-current usage if that becomes required
            $availableKecil = $remainKecil;
            if ($needKecil > $availableKecil + $tolerance) {
                $available = $this->qtyKecilToSourceUnit((object) [
                    'fsatuan' => $sat,
                    'fsatuanbesar' => $product->fsatuanbesar ?? '',
                    'fsatuanbesar2' => $product->fsatuanbesar2 ?? '',
                    'fqtykecil_master' => $product->fqtykecil_master ?? 0,
                    'fqtykecil2_master' => $product->fqtykecil2_master ?? 0,
                ], $availableKecil);
                $errors->add("fqty.$i", "Qty item {$code} melebihi qty referensi {$sourceType}. Maksimal {$available}.");
            }
        }

        return $errors;
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
        $prefix = sprintf('BUY%s%s%s%s%s', $sep, $kodeCabang, $sep, $date->format('y') . $date->format('m'), $sep);

        $lockKey = crc32('STOCKMT|BUY|' . $kodeCabang . '|' . $date->format('y-m'));
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
            return redirect()->back()->with('error', 'PO tidak ada.');
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
                'trstockdt.fqtykecil',
            ]);

        $fmt = fn($d) => $d
            ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d F Y')
            : '-';

        return view('fakturpembelian.print', [
            'hdr' => $hdr,
            'dt' => $dt,
            'displayFstockmtno' => $this->formatDisplayTransactionNumber($hdr->fstockmtno ?? null, (int) ($hdr->fapplyppn ?? 0) === 0 && (int) ($hdr->fincludeppn ?? 0) === 0),
            'fmt' => $fmt,
            'company_name' => config('app.company_name', 'PT. DEMO VERSION'),
            'company_city' => config('app.company_city', 'Tangerang'),
        ]);
    }

    public function create(Request $request)
    {
        if ($this->hasReachedDailyCreateLimit()) {
            return redirect()
                ->route('fakturpembelian.index')
                ->with('create_limit_exceeded', true);
        }

        $suppliers = Supplier::orderBy('fsuppliername', 'asc')
            ->get(['fsuppliercode', 'fsuppliername']);
        $supplierAdvanceWarnings = $this->getSupplierAdvanceWarningMap();

        $warehouses = DB::table('mswh')
            ->select('fwhid', 'fwhcode', 'fwhname', 'fbranchcode', 'fnonactive')
            ->where('fnonactive', '0')
            ->orderBy('fwhcode')
            ->get();

        $accounts = DB::table('account')
            ->select('faccid', 'faccount', 'faccname', 'fnonactive')
            ->where('fend', 1)
            ->where('fnonactive', '0')
            ->orderBy('faccount')
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

        $products = $this->browseProducts();

        return view('fakturpembelian.create', [
            'newtr_prh_code' => $newtr_prh_code,
            'warehouses' => $warehouses,
            'accounts' => $accounts,
            'suppliers' => $suppliers,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'products' => $products,
            'defaultPpnTarif' => $this->getDefaultPpnTarif(),
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'supplierAdvanceWarnings' => $supplierAdvanceWarnings,
        ]);
    }

    public function priceInfo(Request $request)
    {
        $data = $request->validate([
            'fsupplier' => ['required', 'string', 'max:20'],
            'fprdcode' => ['required', 'string', 'max:20'],
            'fsatuan' => ['required', 'string', 'max:20'],
        ]);

        $supplierCode = trim($data['fsupplier']);
        $productCode = trim($data['fprdcode']);
        $unit = trim($data['fsatuan']);
        $history = $this->latestPurchaseHistory($supplierCode, $productCode, $unit);

        return response()->json([
            'price' => $history ? (float) ($history->fprice ?? 0) : null,
            'unit' => $history && trim((string) ($history->fsatuan ?? '')) !== ''
                ? trim((string) $history->fsatuan)
                : $unit,
            'discount' => $history ? $this->normalizeDiscountInput($history->fdiscpersen ?? 0) : null,
            'source' => [
                'price' => $history ? 'history' : 'default',
                'discount' => $history ? 'history' : 'default',
            ],
        ]);
    }

    public function store(Request $request)
    {
        try {
            if ($this->hasReachedDailyCreateLimit()) {
                return redirect()
                    ->route('fakturpembelian.index')
                    ->with('create_limit_exceeded', true);
            }

            $rawCodes = collect($request->input('fitemcode', []));
            $hasOpeningBalanceItem = $rawCodes->contains(fn($code) => $this->isOpeningBalanceProductCode($code));

            if ($hasOpeningBalanceItem) {
                $request->merge([
                    'ftypebuy' => '1',
                ]);
            }

            $allowNegativeStockQty = stock_boleh_minus();
            $hasPpn = $request->boolean('fapplyppn') || $request->input('fapplyppn') == '1';
            $typeBuy = (int) $request->input('ftypebuy', 0);
            // 1) VALIDASI
            $request->validate([
                'fstockmtno' => [
                    'nullable',
                    'string',
                    'max:100',
                    function ($attribute, $value, $fail) use ($request) {
                        if (! $request->boolean('auto_generate', true) && empty(trim((string) $value))) {
                            $fail('No. Transaksi wajib diisi jika Auto tidak dicentang.');
                        }
                    },
                ],
                'fstockmtdate' => ['required', 'date'],
                'fsupplier' => ['required', 'string', 'max:30'],
                'ffrom' => ['required', 'string', 'max:30'],
                'frefno' => ['required', 'string', 'max:100'],
                'frefpo' => [$hasPpn ? 'required' : 'nullable', 'string', 'max:100'],
                'ftypebuy' => ['nullable', 'integer'],
                'fprdjadi' => ['required_if:ftypebuy,1'],
                'fitemcode' => [
                    'required',
                    'array',
                    'min:1',
                    function ($attribute, $value, $fail) use ($typeBuy) {
                        foreach ((array) $value as $code) {
                            $c = strtoupper(trim((string) $code));
                            if (empty($c)) continue;

                            if ($typeBuy === 2 && ! str_starts_with($c, 'UM')) {
                                $fail('Tipe Faktur Uang Muka hanya boleh menggunakan produk kode UM.');
                                return;
                            }
                            if ($typeBuy !== 2 && str_starts_with($c, 'UM')) {
                                $fail('Produk kode UM hanya boleh digunakan untuk Tipe Faktur Uang Muka.');
                                return;
                            }
                        }
                    },
                ],
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
                'fdiscpersen' => ['nullable', 'array'],
                'fdiscpersen.*' => ['nullable', 'string', 'regex:/^\s*\d+(?:\.\d+)?(?:\s*\+\s*\d+(?:\.\d+)?)*\s*$/'],
                'frefnoacak' => ['nullable', 'array'],
                'frefnoacak.*' => ['nullable', 'regex:/^\d{3}(,\s*\d{3})*$/'],
            ], [
                'ffrom.required' => 'Gudang wajib di isi.',
                'frefno.required' => 'No faktur wajib diisi.',
                'frefpo.required' => 'Faktur Pajak wajib diisi karena pembelian ada PPN.',
                'fprdjadi.required_if' => 'Account wajib diisi ketika tipe pembelian adalah Non Stok.',
                'fdiscpersen.*.regex' => 'Format diskon item harus angka atau format seperti 10+2.',
            ]);

            $this->ensureNoDuplicateDetailCodes($request->input('fitemcode', []));

            // 2) HEADER FIELDS
            $fstockmtnoRaw = strtoupper(trim((string) $request->input('fstockmtno')));
            $fstockmtno = $fstockmtnoRaw !== '' ? $this->formatDisplayTransactionNumber($fstockmtnoRaw, (int) ($request->input('fapplyppn') ?? 0) === 0 && (int) ($request->input('fincludeppn') ?? 0) === 0) : '';
            $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
            $this->ensureCreateDateWithinEditPeriod($fstockmtdate);
            $fsupplier = trim((string) $request->input('fsupplier'));
            $ffrom = trim((string) $request->input('ffrom'));
            $fket = trim((string) $request->input('fket', ''));
            $fbranchcode = $request->input('fbranchcode');
            $faccid = $request->input('faccid');
            $fprdjadi = $request->input('fprdjadi');
            $ftempohr = $request->input('ftempohr');
            $ftypebuy = $request->input('ftypebuy');
            $frefno = $request->input('frefno');
            $frefpo = $request->input('frefpo');
            $fcurrency = $request->input('fcurrency', 'IDR');
            $frate = max(1, (float) $request->input('frate', 1));
            $userid = auth('sysuser')->user()->fsysuserid ?? 'admin';
            $now = now();
            $fincludeppn = 0; // PPN Faktur Pembelian selalu Exclude (0)
            $fapplyppn = $request->boolean('fapplyppn') ? 1 : 0;
            if ($fapplyppn === 0) {
                $fincludeppn = 0;
                $fppnpersen = 0;
            } else {
                $defaultPpnTarif = $this->getDefaultPpnTarif();
                $rawPpn = (float) $request->input('ppn_rate', $defaultPpnTarif);
                $fppnpersen = $rawPpn > 0 ? $rawPpn : $defaultPpnTarif;
            }

            // 3) DETAIL ARRAYS
            $codes = $request->input('fitemcode', []);
            $satuans = $request->input('fsatuan', []);
            $refdtnos = $request->input('frefdtno', []);
            $refdtids = $request->input('frefdtid', []);
            $sources = $request->input('fsource', []);
            $frefnoacaks = $request->input('frefnoacak', []);
            $qtys = $request->input('fqty', []);
            $prices = $request->input('fprice', []);
            $biayas = $request->input('fbiaya', []);
            $discs = $request->input('fdiscpersen', []);
            $descs = $request->input('fdesc', []);

            $typeBuy = (int) $request->input('ftypebuy', $header->ftypebuy ?? 0);
            $hasUM = collect($codes)->map(fn($code) => strtoupper(trim((string) $code)))->contains('UM');
            if ($typeBuy === 2) {
                $invalidAdvanceCodes = collect($codes)
                    ->map(fn($code) => trim((string) $code))
                    ->filter(fn($code) => $code !== '' && !str_starts_with(strtoupper($code), 'UM'))
                    ->unique()
                    ->values()
                    ->all();

                if (! empty($invalidAdvanceCodes)) {
                    $msg = 'Tipe Faktur Uang Muka hanya boleh menggunakan produk kode UM.';
                    if ($request->expectsJson()) {
                        return response()->json(['message' => $msg], 422);
                    }

                    return back()
                        ->withInput()
                        ->with('error', $msg);
                }
            } else {
                $invalidUmCodes = collect($codes)
                    ->map(fn($code) => trim((string) $code))
                    ->filter(fn($code) => $code !== '' && str_starts_with(strtoupper($code), 'UM'))
                    ->unique()
                    ->values()
                    ->all();

                if (! empty($invalidUmCodes)) {
                    $msg = 'Produk kode UM hanya boleh digunakan untuk Tipe Faktur Uang Muka.';
                    if ($request->expectsJson()) {
                        return response()->json(['message' => $msg], 422);
                    }

                    return back()
                        ->withInput()
                        ->with('error', $msg);
                }
            }

            $submittedCodes = collect($codes)
                ->map(fn($code) => trim((string) $code))
                ->filter(fn($code) => $code !== '')
                ->unique()
                ->values();

            if ($submittedCodes->isNotEmpty()) {
                if ((string) $ftypebuy === '1') {
                    $invalidServiceCodes = DB::table('msprd')
                        ->whereIn('fprdcode', $submittedCodes->all())
                        ->whereRaw("LOWER(TRIM(COALESCE(ftype, ''))) != ?", ['jasa'])
                        ->pluck('fprdcode')
                        ->all();

                    if (! empty($invalidServiceCodes)) {
                        $invalidList = implode(', ', $invalidServiceCodes);
                        $message = "Tipe Pembelian: Non Stok.\nHanya boleh input produk dengan tipe Jasa !!! (Kode item: {$invalidList})";
                        if ($request->expectsJson()) {
                            return response()->json(['message' => $message], 422);
                        }

                        return back()->withInput()->withErrors([
                            'detail' => $message,
                        ]);
                    }
                } else {
                    $invalidJasaCodes = DB::table('msprd')
                        ->whereIn('fprdcode', $submittedCodes->all())
                        ->whereRaw("LOWER(TRIM(COALESCE(ftype, ''))) = ?", ['jasa'])
                        ->pluck('fprdcode')
                        ->all();

                    if (! empty($invalidJasaCodes)) {
                        $invalidList = implode(', ', $invalidJasaCodes);
                        $typeName = match ((string) $ftypebuy) {
                            '0' => 'Stok',
                            '2' => 'Uang Muka',
                            '3' => 'Lain-lain',
                            default => 'Stok',
                        };
                        $message = "Tipe Pembelian: {$typeName}.\nProduk dengan tipe Jasa tidak boleh diinput untuk tipe ini !!! (Kode item: {$invalidList})";
                        if ($request->expectsJson()) {
                            return response()->json(['message' => $message], 422);
                        }

                        return back()->withInput()->withErrors([
                            'detail' => $message,
                        ]);
                    }
                }
            }

            if ($this->hasMixedOpeningBalanceAndSourceRows($codes, $qtys, $sources)) {
                $message = 'Item awal tidak boleh digabung dengan item referensi PO / TER.';
                if ($request->expectsJson()) {
                    return response()->json(['message' => $message], 422);
                }
                return back()->withInput()->withErrors([
                    'detail' => $message,
                ]);
            }

            // 4) BUILD ROWS
            $uniqueCodes = array_values(array_unique(array_filter(array_map(fn($c) => trim((string) $c), $codes))));
            $prodMeta = DB::table('msprd')->whereIn('fprdcode', $uniqueCodes)->get()->keyBy('fprdcode');

            $rowsDt = [];
            $usedNoAcaks = [];
            $subtotal = 0.0;
            $sourceUsageByRef = [];
            $skippedDetailCodes = [];

            $lineCounter = 1;

            $oldUsageByRefId = [];

            $extraAvailableBySourceRef = [];
            foreach ($sources as $i => $src) {
                $sourceType = strtoupper(trim((string) ($src ?? '')));
                $detailId = (int) ($refdtids[$i] ?? 0);
                if (! in_array($sourceType, ['PO', 'PB'], true) || $detailId <= 0) {
                    continue;
                }
                $extraAvailableBySourceRef[$sourceType . ':' . $detailId] = (float) ($oldUsageByRefId[$detailId] ?? 0);
            }

            $sourceValidationCodes = [];
            $sourceValidationQtys = [];
            $sourceValidationSources = [];
            $sourceValidationRefdtids = [];
            $sourceValidationSatuans = [];

            foreach ($codes as $i => $code) {
                if ($this->isOpeningBalanceProductCode($code)) {
                    continue;
                }

                $sourceValidationCodes[] = $code;
                $sourceValidationQtys[] = $qtys[$i] ?? null;
                $sourceValidationSources[] = $sources[$i] ?? null;
                $sourceValidationRefdtids[] = $refdtids[$i] ?? null;
                $sourceValidationSatuans[] = $satuans[$i] ?? null;
            }

            $errors = $this->validateSourceRemainForRows(
                $sourceValidationCodes,
                $sourceValidationQtys,
                $sourceValidationSources,
                $sourceValidationRefdtids,
                $sourceValidationSatuans,
                $extraAvailableBySourceRef
            );

            if ($errors->isNotEmpty()) {
                if ($request->expectsJson()) {
                    return response()->json(['errors' => $errors->toArray()], 422);
                }
                return back()->withErrors($errors)->withInput();
            }

            for ($i = 0; $i < count($codes); $i++) {
                $code = trim((string) ($codes[$i] ?? ''));
                $qty = (float) ($qtys[$i] ?? 0);
                if ($code === '' || $qty <= 0) {
                    continue;
                }

                $meta = $prodMeta[$code] ?? null;
                if (! $meta) {
                    $skippedDetailCodes[] = $code;
                    continue;
                }

                $isSaldoAwal = $this->isOpeningBalanceProductCode($code);
                $sat = mb_substr(trim((string) ($satuans[$i] ?? $meta->fsatuankecil ?? '')), 0, 5);
                $sourceType = $isSaldoAwal ? '' : strtoupper(trim((string) ($sources[$i] ?? '')));
                $isAdvancePaymentDetail = (string) $ftypebuy === '2';
                $frefdtid = $isSaldoAwal ? null : (isset($refdtids[$i]) ? (int) $refdtids[$i] : null);
                $qtyKecil = $qty;
                if ($sat === trim((string) ($meta->fsatuanbesar ?? '')) && (float) ($meta->fqtykecil ?? 0) > 0) {
                    $qtyKecil = $qty * (float) $meta->fqtykecil;
                } elseif ($sat === trim((string) ($meta->fsatuanbesar2 ?? '')) && (float) ($meta->fqtykecil2 ?? 0) > 0) {
                    $qtyKecil = $qty * (float) $meta->fqtykecil2;
                }
                if ($isSaldoAwal) {
                    $qtyKecil = 0;
                }

                $price = (float) ($prices[$i] ?? 0);
                $biaya = (float) ($biayas[$i] ?? 0);
                $discRaw = $this->normalizeDiscountInput($discs[$i] ?? 0);
                $discP = $this->parseDiscountExpression($discRaw);
                $sourceType = $isSaldoAwal ? '' : strtoupper(trim((string) ($sources[$i] ?? '')));
                $isAdvancePaymentDetail = (string) $ftypebuy === '2';

                $discAmount = $price * ($discP / 100);
                $priceNet = $price - $discAmount + $biaya;
                $amount = $qty * $priceNet;
                $subtotal += $amount;

                $rowsDt[] = [
                    'fprdcode' => $code,
                    'fnoacak' => $this->normalizeRandomNumber(null, $usedNoAcaks),
                    'frefdtno' => $isSaldoAwal ? null : (trim((string) ($refdtnos[$i] ?? '')) ?: null),
                    'frefso' => $sourceType === 'PO' ? (trim((string) ($refdtnos[$i] ?? '')) ?: null) : null,
                    'frefdtid' => $isSaldoAwal ? null : (isset($refdtids[$i]) ? (int) $refdtids[$i] : null),
                    'frefnoacak' => $isSaldoAwal ? null : $this->normalizeReferenceRandomNumberSingle($frefnoacaks[$i] ?? null),
                    'fqty' => $qty,
                    'fqtykecil' => $qtyKecil,
                    'fqtyremain' => $qtyKecil,
                    'fprice' => $price,
                    'fbiaya' => $biaya,
                    'fpricenet' => $priceNet,
                    'fprice_rp' => $price * $frate,
                    'ftotprice' => $amount,
                    'ftotprice_rp' => $amount * $frate,
                    'fusercreate' => $userid,
                    'fdatetime' => $now,
                    'fcode' => ($isAdvancePaymentDetail || $sourceType === 'PO') ? 'P' : 'T',
                    'fdesc' => trim((string) ($descs[$i] ?? '')) ?: null,
                    'fdiscpersen' => $discRaw,
                    'fsatuan' => $sat,
                    'fclosedt' => '0',
                ];

                $detailId = isset($refdtids[$i]) ? (int) $refdtids[$i] : 0;
                if (in_array($sourceType, ['PO', 'PB'], true) && $detailId > 0) {
                    $sourceKey = $sourceType . ':' . $detailId;
                    $sourceUsageByRef[$sourceKey] = ($sourceUsageByRef[$sourceKey] ?? 0) + $qtyKecil;
                }
            }

            if (empty($rowsDt)) {
                $message = 'Detail item transaksi pembelian tidak berhasil dibentuk, sehingga data detail tidak tersimpan.';
                if (! empty($skippedDetailCodes)) {
                    $message .= ' Kode item yang tidak dikenali: ' . implode(', ', array_values(array_unique($skippedDetailCodes))) . '.';
                }

                if ($request->expectsJson()) {
                    return response()->json(['message' => $message], 422);
                }
                return back()->withInput()->withErrors([
                    'detail' => $message,
                ]);
            }

            $ppnAmount = $fapplyppn === 1 ? (float) $request->input('famountpajak', 0) : 0.0;
            $grandTotal = $subtotal + $ppnAmount;

            if ((string) $ftypebuy === '2' && $subtotal <= 0) {
                $message = 'Nominal Uang Muka harus lebih besar dari 0.';
                if ($request->expectsJson()) {
                    return response()->json(['message' => $message], 422);
                }
                return back()->withInput()->withErrors([
                    'fprice' => $message,
                ]);
            }
            // 5) TRANSACTION
            DB::transaction(function () use (
                $request,
                $fstockmtdate,
                $fsupplier,
                $ffrom,
                $fket,
                $fbranchcode,
                $frate,
                $userid,
                $now,
                $ftempohr,
                $fincludeppn,
                $fapplyppn,
                $fppnpersen,
                $ftypebuy,
                $frefno,
                $frefpo,
                $fcurrency,
                $faccid,
                $fprdjadi,
                &$fstockmtno,
                &$rowsDt,
                $subtotal,
                $ppnAmount,
                $grandTotal,
                $sourceUsageByRef
            ) {
                // A. Resolve Cabang
                $rawBranch = trim((string) $fbranchcode);
                $kodeCabang = DB::table('mscabang')
                    ->where('fcabangid', is_numeric($rawBranch) ? (int) $rawBranch : -1)
                    ->orWhere('fcabangkode', $rawBranch)
                    ->value('fcabangkode') ?? 'NA';

                $yy = $fstockmtdate->format('y');
                $mm = $fstockmtdate->format('m');
                $isAdvancePayment = (int) $ftypebuy === 2;
                $fstockmtcode = 'BUY';
                $prefixCode = $isAdvancePayment ? 'UMB' : 'BUY';

                // B. Penomoran
                if (empty($fstockmtno)) {
                    $sep = ($fapplyppn === 1 || $fincludeppn === 1) ? '.' : '/';
                    $prefix = sprintf('%s%s%s%s%s%s', $prefixCode, $sep, $kodeCabang, $sep, $yy . $mm, $sep);
                    $lockKey = crc32("STOCKMT|$prefixCode|$kodeCabang|" . $fstockmtdate->format('y-m'));

                    if (DB::getDriverName() === 'pgsql') {
                        DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

                        $last = DB::table('trstockmt')
                            ->where(function ($q) use ($prefix, $prefixCode, $sep, $kodeCabang, $yy, $mm) {
                                $yymm = $yy . $mm;
                                $q->where('fstockmtno', 'like', "{$prefix}%");
                                if ($prefixCode === 'UMB') {
                                    $q->orWhere('fstockmtno', 'like', "UM{$sep}{$kodeCabang}{$sep}{$yymm}{$sep}%");
                                }
                            })
                            ->selectRaw("MAX(CAST(SUBSTRING(fstockmtno FROM '([0-9]+)$') AS int)) AS lastno")
                            ->value('lastno');

                        $next = (int) $last + 1;
                    } else {
                        $lastCode = DB::table('trstockmt')
                            ->where(function ($q) use ($prefix, $prefixCode, $sep, $kodeCabang, $yy, $mm) {
                                $yymm = $yy . $mm;
                                $q->where('fstockmtno', 'like', "{$prefix}%");
                                if ($prefixCode === 'UMB') {
                                    $q->orWhere('fstockmtno', 'like', "UM{$sep}{$kodeCabang}{$sep}{$yymm}{$sep}%");
                                }
                            })
                            ->orderByDesc('fstockmtno')
                            ->value('fstockmtno');

                        $next = 1;
                        if ($lastCode && ($pos = max((int) strrpos($lastCode, '.'), (int) strrpos($lastCode, '/'))) !== false && $pos > 0) {
                            $next = ((int) substr($lastCode, $pos + 1)) + 1;
                        }
                    }

                    $fstockmtno = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
                }

                // C. Insert Header
                $ftrancode = match ((string) $ftypebuy) {
                    '0' => '0',
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    default => '0',
                };

                $masterId = DB::table('trstockmt')->insertGetId([
                    'fstockmtno' => $fstockmtno,
                    'fstockmtcode' => $fstockmtcode,
                    'fstockmtdate' => $fstockmtdate,
                    'fsupplier' => $fsupplier,
                    'frate' => $frate,
                    'famount' => round($subtotal, 2),
                    'famount_rp' => round($subtotal * $frate, 2),
                    'famountpajak' => round($ppnAmount, 2),
                    'famountpajak_rp' => round($ppnAmount * $frate, 2),
                    'famountmt' => round($grandTotal, 2),
                    'famountmt_rp' => round($grandTotal * $frate, 2),
                    'famountremain' => round($grandTotal, 2),
                    'famountremain_rp' => round($grandTotal * $frate, 2),
                    'frefno' => $frefno,
                    'frefpo' => $frefpo,
                    'ftrancode' => $ftrancode,
                    'ffrom' => $ffrom,
                    'fprdjadi' => $fprdjadi,
                    'fprdjadiid' => $faccid,
                    'fket' => $fket,
                    'fusercreate' => $userid,
                    'fdatetime' => $now,
                    'fbranchcode' => $kodeCabang,
                    'fincludeppn' => $fincludeppn,
                    'fapplyppn' => $fapplyppn,
                    'fppnpersen' => $fppnpersen,
                    'ftempohr' => $ftempohr,
                    'ftypebuy' => $ftypebuy,
                    'fprdout' => '0',
                    'fsudahtagih' => '0',
                    'fprint' => 0,
                ], 'fstockmtid');

                // D. Insert Details
                foreach ($rowsDt as &$r) {
                    $r['fstockmtno'] = $fstockmtno;
                    $r['fstockmtcode'] = $fstockmtcode;
                }
                DB::table('trstockdt')->insert($rowsDt);
                $this->adjustSourceQtyKecil($sourceUsageByRef, -1);

                $this->syncFakturPembelianJournalEntries(
                    (string) $fstockmtno,
                    $fstockmtdate,
                    (string) $kodeCabang,
                    (string) $fsupplier,
                    (string) $userid,
                    (int) $ftypebuy,
                    (string) $fcurrency,
                    (string) $fprdjadi,
                    (string) $frate
                );
            });

            $successMessage = "Faktur pembelian {$fstockmtno} berhasil disimpan.";

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $successMessage,
                    'redirect_url' => route('fakturpembelian.create'),
                    'success_prompt' => [
                        'type' => 'fakturpembelian_create',
                        'redirect_url' => route('fakturpembelian.print', $fstockmtno),
                    ],
                ]);
            }

            return redirect()->route('fakturpembelian.create')
                ->with('success', $successMessage)
                ->with('success_prompt', [
                    'type' => 'fakturpembelian_create',
                    'redirect_url' => route('fakturpembelian.print', $fstockmtno),
                ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('FakturPembelian@store VALIDATION ERROR: ' . $e->getMessage(), [
                'errors' => $e->errors(),
            ]);
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => collect($e->errors())->flatten()->first() ?? $e->getMessage(),
                    'errors' => $e->errors(),
                ], 422);
            }
            return back()->withInput()->withErrors($e->errors());
        } catch (\Throwable $e) {
            Log::error('FakturPembelian@store ERROR: ' . $e->getMessage(), [
                'exception' => $e,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Gagal simpan: ' . $e->getMessage()], 500);
            }
            return back()->withInput()->withErrors(['error' => 'Gagal simpan: ' . $e->getMessage()]);
        }
    }

    public function edit(Request $request, $fstockmtid)
    {
        $supplierAdvanceWarnings = $this->getSupplierAdvanceWarningMap();
        $suppliers = Supplier::orderBy('fsuppliername', 'asc')
            ->get(['fsuppliercode', 'fsuppliername']);

        $fakturpembelian = PenerimaanPembelianHeader::with([
            'details' => function ($query) {
                $query
                    ->join('msprd', 'msprd.fprdcode', '=', 'trstockdt.fprdcode')
                    ->select(
                        'trstockdt.*',
                        'msprd.fprdname',
                        'msprd.fprdcode as fitemcode_text'
                    )
                    ->orderBy('trstockdt.fstockdtid', 'asc');
            },
        ])
            ->where(function ($q) use ($fstockmtid) {
                if (is_numeric($fstockmtid)) {
                    $q->where('fstockmtid', (int) $fstockmtid);
                }
                $slash = str_replace('.', '/', $fstockmtid);
                $dot = str_replace('/', '.', $fstockmtid);
                $q->orWhere('fstockmtno', $fstockmtid)
                  ->orWhere('fstockmtno', $slash)
                  ->orWhere('fstockmtno', $dot);
            })->firstOrFail();

        if ($message = $this->getPostedPeriodLockMessage($fakturpembelian->fstockmtdate)) {
            return redirect()
                ->route('fakturpembelian.view', $fakturpembelian->fstockmtid)
                ->with('error', $message);
        }

        $savedAccountCode = $fakturpembelian->fprdjadi;

        $accounts = DB::table('account')
            ->select('faccid', 'faccount', 'faccname', 'fnonactive')
            ->where('fend', 1)
            ->where('fnonactive', '0')
            ->orderBy('faccount')
            ->get();

        $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

        $branch = DB::table('mscabang')
            ->when(is_numeric($raw), fn($q) => $q->where('fcabangid', (int) $raw))
            ->when(! is_numeric($raw), fn($q) => $q
                ->where('fcabangkode', $raw)
                ->orWhere('fcabangname', $raw))
            ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

        $warehouses = DB::table('mswh')
            ->select('fwhid', 'fwhcode', 'fwhname', 'fbranchcode', 'fnonactive')
            ->where('fnonactive', '0')
            ->orderBy('fwhcode')
            ->get();

        $defaultCabang = $branch->fcabangname ?? (string) $raw;
        $defaultBranchCode = $branch->fcabangkode ?? (string) $raw;
        $savedBranchCode = trim((string) ($fakturpembelian->fbranchcode ?? ''));
        $savedBranchName = $savedBranchCode !== ''
            ? DB::table('mscabang')->where('fcabangkode', $savedBranchCode)->value('fcabangname')
            : null;

        $currentAccount = trim($fakturpembelian->fprdjadi ?? '');
        $currentAccountRecord = $accounts->firstWhere('faccount', trim($fakturpembelian->fprdjadi ?? ''));
        $currentAccountId = $currentAccountRecord?->faccid ?? '';
        $currentAccountName = $currentAccountRecord?->faccname ?? '';

        $detailRefIds = $fakturpembelian->details
            ->pluck('frefdtid')
            ->filter(fn($id) => (int) $id > 0)
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $poRefSet = [];
        $pbRefSet = [];
        if (! empty($detailRefIds)) {
            $poRefSet = DB::table('tr_pod')
                ->whereIn('fpodid', $detailRefIds)
                ->pluck('fpodid')
                ->map(fn($id) => (int) $id)
                ->flip()
                ->all();

            $pbRefSet = DB::table('trstockdt')
                ->whereIn('fstockdtid', $detailRefIds)
                ->pluck('fstockdtid')
                ->map(fn($id) => (int) $id)
                ->flip()
                ->all();
        }

        $oldUsageBySourceRef = [];
        foreach ($fakturpembelian->details as $d) {
            $detailId = (int) ($d->frefdtid ?? 0);
            if ($detailId <= 0) {
                continue;
            }

            $sourceType = isset($poRefSet[$detailId]) ? 'PO' : (isset($pbRefSet[$detailId]) ? 'PB' : '');
            if ($sourceType === '') {
                continue;
            }

            $sourceKey = $sourceType . ':' . $detailId;
            $oldUsageBySourceRef[$sourceKey] = ($oldUsageBySourceRef[$sourceKey] ?? 0) + (float) ($d->fqty ?? 0);
        }

        [$poUnits, $pbUnits] = $this->getReferenceUnitMaps($fakturpembelian->details);

        // 4. Map the data for savedItems
        $savedItems = $fakturpembelian->details->map(function ($d) use ($poRefSet, $pbRefSet, $oldUsageBySourceRef, $poUnits, $pbUnits) {
            $detailId = (int) ($d->frefdtid ?? 0);
            $sourceType = isset($poRefSet[$detailId]) ? 'PO' : (isset($pbRefSet[$detailId]) ? 'PB' : '');
            $sourceRemain = $sourceType !== '' && $detailId > 0 ? $this->getSourceRemain($sourceType, $detailId) : null;

            $maxFromSource = null;
            if ($sourceType !== '' && $detailId > 0) {
                $sourceKey = $sourceType . ':' . $detailId;
                $maxFromSource = max(0, (float) ($sourceRemain ?? 0) + (float) ($oldUsageBySourceRef[$sourceKey] ?? 0));
            }

            return [
                'uid' => $d->fstockdtid,
                'fitemcode' => $d->fitemcode_text ?? '',
                'fitemname' => $d->fprdname ?? '',
                'fsatuan' => $this->resolveDetailDisplayUnit($d, $poUnits, $pbUnits),
                'fdisplayunit' => $this->resolveDetailDisplayUnit($d, $poUnits, $pbUnits),
                'fprno' => $d->frefpr ?? '-',
                'frefpr' => $d->frefpr ?? null,
                'fpono' => $d->fpono ?? null,
                'famountponet' => $d->famountponet ?? null,
                'famountpo' => $d->famountpo ?? null,
                'frefdtno' => $d->frefdtno ?? null,
                'frefdtid' => $detailId > 0 ? $detailId : null,
                'frefnoacak' => $d->frefnoacak ?? null,
                'fsource' => $sourceType,
                'fqty' => (float) ($d->fqty ?? 0),
                'fterima' => (float) ($d->fterima ?? 0),
                'fprice' => (float) ($d->fprice ?? 0),
                'fdiscpersen' => $this->normalizeDiscountInput($d->fdiscpersen ?? 0),
                'fbiaya' => (float) ($d->fbiaya ?? 0),
                'ftotprice' => (float) ($d->ftotprice ?? 0),
                'ftotal' => (float) ($d->ftotprice ?? 0),
                'fdesc' => is_array($d->fdesc)
                    ? implode(', ', $d->fdesc)
                    : (trim((string) ($d->fdesc ?? '')) !== '' ? $d->fdesc : ($d->fketdt ?? '')),
                'fketdt' => $d->fketdt ?? '',
                'maxqty' => $maxFromSource,
                'units' => [],
            ];
        })->values();

        $selectedSupplierCode = $fakturpembelian->fsupplier;

        $products = $this->browseProducts();
        $productMap = $this->browseProductMap($products);
        $biayaGlobal = (float) $savedItems->sum(function ($item) {
            return ((float) ($item['fbiaya'] ?? 0)) * ((float) ($item['fqty'] ?? 0));
        });
        $usageLockMessage = $this->getUsageLockMessage($fakturpembelian);

        if (! empty($usageLockMessage)) {
            return redirect()
                ->route('fakturpembelian.view', $fakturpembelian->fstockmtid)
                ->with('error', $usageLockMessage);
        }

        return view('fakturpembelian.edit', [
            'suppliers' => $suppliers,
            'selectedSupplierCode' => $selectedSupplierCode,
            'fcabang' => $savedBranchName ?? $defaultCabang,
            'fbranchcode' => $savedBranchCode ?: $defaultBranchCode,
            'warehouses' => $warehouses,
            'products' => $products,
            'defaultPpnTarif' => $this->getDefaultPpnTarif(),
            'accounts' => $accounts,
            'productMap' => $productMap,
            'currentAccount' => $currentAccount,
            'currentAccountId' => $currentAccountId,
            'currentAccountName' => $currentAccountName,
            'fakturpembelian' => $fakturpembelian,
            'displayFstockmtno' => $this->formatDisplayTransactionNumber($fakturpembelian->fstockmtno ?? null, (int) ($fakturpembelian->fapplyppn ?? 0) === 0 && (int) ($fakturpembelian->fincludeppn ?? 0) === 0),
            'savedItems' => $savedItems,
            'biayaGlobal' => $biayaGlobal,
            'ppnAmount' => (float) ($fakturpembelian->famountpopajak ?? 0),
            'famountponet' => (float) ($fakturpembelian->famountponet ?? 0),
            'famountpo' => (float) ($fakturpembelian->famountpo ?? 0),
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'isUsageLocked' => false,
            'usageLockMessage' => null,
            'action' => 'edit',
            'supplierAdvanceWarnings' => $supplierAdvanceWarnings,
        ]);
    }

    public function view(Request $request, $fstockmtid)
    {
        $supplierAdvanceWarnings = $this->getSupplierAdvanceWarningMap();
        $suppliers = Supplier::orderBy('fsuppliername', 'asc')
            ->get(['fsuppliercode', 'fsuppliername']);

        $fakturpembelian = PenerimaanPembelianHeader::with([
            'details' => function ($query) {
                $query
                    ->join('msprd', 'msprd.fprdcode', '=', 'trstockdt.fprdcode')
                    ->select(
                        'trstockdt.*',
                        'msprd.fprdname',
                        'msprd.fprdcode as fitemcode_text'
                    )
                    ->orderBy('trstockdt.fstockdtid', 'asc');
            },
        ])
            ->where(function ($q) use ($fstockmtid) {
                if (is_numeric($fstockmtid)) {
                    $q->where('fstockmtid', (int) $fstockmtid);
                }
                $slash = str_replace('.', '/', $fstockmtid);
                $dot = str_replace('/', '.', $fstockmtid);
                $q->orWhere('fstockmtno', $fstockmtid)
                  ->orWhere('fstockmtno', $slash)
                  ->orWhere('fstockmtno', $dot);
            })->firstOrFail();

        // 2. Ambil kode akun yang tersimpan dari faktur
        $savedAccountCode = $fakturpembelian->fprdjadi;

        // 3. UBAH QUERY INI: Gunakan $savedAccountCode
        $accounts = DB::table('account')
            ->select('faccid', 'faccount', 'faccname', 'fnonactive')
            ->where('fend', 1)
            ->where('fnonactive', '0')
            ->orderBy('faccount') // <-- Perbaikan nama kolom
            ->get();

        // --- Sisa kode Anda ---
        $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

        $branch = DB::table('mscabang')
            ->when(is_numeric($raw), fn($q) => $q->where('fcabangid', (int) $raw))
            ->when(! is_numeric($raw), fn($q) => $q
                ->where('fcabangkode', $raw)
                ->orWhere('fcabangname', $raw))
            ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

        $warehouses = DB::table('mswh')
            ->select('fwhid', 'fwhcode', 'fwhname', 'fbranchcode', 'fnonactive')
            ->where('fnonactive', '0') // hanya yang aktif
            ->orderBy('fwhcode')
            ->get();

        $defaultCabang = $branch->fcabangname ?? (string) $raw;
        $defaultBranchCode = $branch->fcabangkode ?? (string) $raw;
        $savedBranchCode = trim((string) ($fakturpembelian->fbranchcode ?? ''));
        $savedBranchName = $savedBranchCode !== ''
            ? DB::table('mscabang')->where('fcabangkode', $savedBranchCode)->value('fcabangname')
            : null;
        $currentAccount = trim($fakturpembelian->fprdjadi ?? '');
        $currentAccountRecord = $accounts->firstWhere('faccount', trim($fakturpembelian->fprdjadi ?? ''));
        $currentAccountId = $currentAccountRecord?->faccid ?? '';
        $currentAccountName = $currentAccountRecord?->faccname ?? '';
        [$poUnits, $pbUnits] = $this->getReferenceUnitMaps($fakturpembelian->details);

        $savedItems = $fakturpembelian->details->map(function ($d) use ($poUnits, $pbUnits) {
            return [
                'uid' => $d->fstockdtid,
                'fitemcode' => $d->fitemcode_text ?? '',
                'fitemname' => $d->fprdname ?? '',
                'fsatuan' => $this->resolveDetailDisplayUnit($d, $poUnits, $pbUnits),
                'fdisplayunit' => $this->resolveDetailDisplayUnit($d, $poUnits, $pbUnits),
                'fprno' => $d->frefpr ?? '-',
                'frefpr' => $d->frefpr ?? null,
                'fpono' => $d->fpono ?? null,
                'famountponet' => $d->famountponet ?? null,
                'famountpo' => $d->famountpo ?? null,
                'frefdtno' => $d->frefdtno ?? null,
                'frefnoacak' => $d->frefnoacak ?? null,
                'fqty' => (float) ($d->fqty ?? 0),
                'fterima' => (float) ($d->fterima ?? 0),
                'fprice' => (float) ($d->fprice ?? 0),
                'fdiscpersen' => $this->normalizeDiscountInput($d->fdiscpersen ?? 0),
                'fbiaya' => (float) ($d->fbiaya ?? 0),
                'ftotprice' => (float) ($d->ftotprice ?? 0),
                'ftotal' => (float) ($d->ftotprice ?? 0),
                'fdesc' => is_array($d->fdesc)
                    ? implode(', ', $d->fdesc)
                    : (trim((string) ($d->fdesc ?? '')) !== '' ? $d->fdesc : ($d->fketdt ?? '')),
                'fketdt' => $d->fketdt ?? '',
                'units' => [],
            ];
        })->values();

        $selectedSupplierCode = $fakturpembelian->fsupplier;

        $products = $this->browseProducts();
        $productMap = $this->browseProductMap($products);
        $biayaGlobal = (float) $savedItems->sum(function ($item) {
            return ((float) ($item['fbiaya'] ?? 0)) * ((float) ($item['fqty'] ?? 0));
        });
        $usageLockMessage = $this->getUsageLockMessage($fakturpembelian);

        if (! empty($usageLockMessage)) {
            return redirect()
                ->route('fakturpembelian.view', $fakturpembelian->fstockmtid)
                ->with('error', $usageLockMessage);
        }

        return view('fakturpembelian.edit', [
            'suppliers' => $suppliers,
            'selectedSupplierCode' => $selectedSupplierCode,
            'fcabang' => $savedBranchName ?? $defaultCabang,
            'fbranchcode' => $savedBranchCode ?: $defaultBranchCode,
            'warehouses' => $warehouses,
            'products' => $products,
            'accounts' => $accounts,
            'productMap' => $productMap,
            'currentAccount' => $currentAccount,
            'currentAccountId' => $currentAccountId,
            'currentAccountName' => $currentAccountName,
            'fakturpembelian' => $fakturpembelian,
            'displayFstockmtno' => $this->formatDisplayTransactionNumber($fakturpembelian->fstockmtno ?? null, (int) ($fakturpembelian->fapplyppn ?? 0) === 0 && (int) ($fakturpembelian->fincludeppn ?? 0) === 0),
            'savedItems' => $savedItems,
            'biayaGlobal' => $biayaGlobal,
            'ppnAmount' => (float) ($fakturpembelian->famountpopajak ?? 0),
            'famountponet' => (float) ($fakturpembelian->famountponet ?? 0),
            'famountpo' => (float) ($fakturpembelian->famountpo ?? 0),
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'isUsageLocked' => ! empty($this->getUsageLockMessage($fakturpembelian)),
            'usageLockMessage' => $this->getUsageLockMessage($fakturpembelian),
            'action' => 'view',
            'supplierAdvanceWarnings' => $supplierAdvanceWarnings,
        ]);
    }

    public function update(Request $request, $fstockmtid)
    {
        try {
            $allowNegativeStockQty = stock_boleh_minus();
            $rawCodes = collect($request->input('fitemcode', []));
            $hasOpeningBalanceItem = $rawCodes->contains(fn($code) => $this->isOpeningBalanceProductCode($code));

            if ($hasOpeningBalanceItem) {
                $request->merge([
                    'ftypebuy' => '1',
                ]);
            }

            $fapplyppn = $request->boolean('fapplyppn') ? 1 : 0;
            $hasPpn = $fapplyppn === 1;
            $header = PenerimaanPembelianHeader::find($fstockmtid);
            $typeBuy = (int) $request->input('ftypebuy', $header->ftypebuy ?? 0);

            // VALIDASI
            $validatedData = $request->validate([
                'fstockmtno' => ['nullable', 'string', 'max:100'],
                'fstockmtdate' => ['required', 'date'],
                'fsupplier' => ['required', 'string', 'max:30'],
                'ffrom' => ['required', 'string', 'max:30'],
                'fket' => ['nullable', 'string', 'max:50'],
                'fbranchcode' => ['nullable', 'string', 'max:20'],
                'faccid' => ['nullable', 'integer'],
                'fitemcode' => [
                    'required',
                    'array',
                    'min:1',
                    function ($attribute, $value, $fail) use ($typeBuy) {
                        foreach ((array) $value as $code) {
                            $c = strtoupper(trim((string) $code));
                            if (empty($c)) continue;

                            if ($typeBuy === 2 && ! str_starts_with($c, 'UM')) {
                                $fail('Tipe Faktur Uang Muka hanya boleh menggunakan produk kode UM.');
                                return;
                            }
                            if ($typeBuy !== 2 && str_starts_with($c, 'UM')) {
                                $fail('Produk kode UM hanya boleh digunakan untuk Tipe Faktur Uang Muka.');
                                return;
                            }
                        }
                    },
                ],
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
                'fdiscpersen' => ['nullable', 'array'],
                'fdiscpersen.*' => ['nullable', 'string', 'regex:/^\s*\d+(?:\.\d+)?(?:\s*\+\s*\d+(?:\.\d+)?)*\s*$/'],
                'fbiaya' => ['required', 'array'],
                'fbiaya.*' => ['nullable', 'numeric', 'min:0'],
                'fdesc' => ['nullable', 'array'],
                'fdesc.*' => ['nullable', 'string', 'max:500'],
                'fcurrency' => ['nullable', 'string', 'max:5'],
                'frate' => ['nullable', 'numeric', 'min:0'],
                'famountpopajak' => ['nullable', 'numeric', 'min:0'],
                'famount' => ['nullable', 'numeric', 'min:0'],
                'famountpajak' => ['nullable', 'numeric', 'min:0'],
                'famountmt' => ['nullable', 'numeric', 'min:0'],
                'fincludeppn' => ['nullable', 'boolean'],
                'fapplyppn' => ['nullable', 'boolean'],
                'ppn_rate' => ['nullable', 'numeric', 'min:0'],
                'fjatuhtempo' => ['nullable', 'date'],
                'ftempohr' => ['nullable', 'integer'],
                'ftypebuy' => ['nullable', 'integer'],
                'frefno' => ['required', 'string', 'max:100'],
                'frefpo' => [$hasPpn ? 'required' : 'nullable', 'string', 'max:100'],
                'frefnoacak' => ['nullable', 'array'],
                'frefnoacak.*' => ['nullable', 'regex:/^\d{3}(,\s*\d{3})*$/'],
                'fprdjadi' => ['required_if:ftypebuy,1'],
            ], [
                'ffrom.required' => 'Gudang wajib di isi.',
                'fstockmtdate.required' => 'Tanggal transaksi wajib diisi.',
                'fsupplier.required' => 'Supplier wajib diisi.',
                'frefno.required' => 'No. faktur wajib diisi.',
                'frefpo.required' => 'Faktur Pajak wajib diisi karena pembelian ada PPN.',
                'fsatuan.*.max' => 'Satuan maksimal 5 karakter.',
                'fprdjadi.required_if' => 'Account wajib diisi.',
                'fdiscpersen.*.regex' => 'Format diskon harus angka atau 10+2.',
            ]);

            $this->ensureNoDuplicateDetailCodes($request->input('fitemcode', []));

            // 2. Muat header yang ada
            $header = PenerimaanPembelianHeader::findOrFail($fstockmtid);

            if ($message = $this->getPostedPeriodLockMessage($header->fstockmtdate)) {
                return redirect()->route('fakturpembelian.edit', $header->fstockmtid)->with('error', $message);
            }

            if ($message = $this->getUsageLockMessage($header)) {
                return redirect()->route('fakturpembelian.index')->with('error', $message);
            }

            $userLogin = Auth::guard('sysuser')->user() ?? Auth::user();
            $userName = $userLogin->fname ?? 'system';
            $userIdLog = $userLogin->fuserid ?? 'SYSTEM';

            // HEADER FIELDS
            $fstockmtno = $header->fstockmtno;
            $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
            $this->ensureCreateDateWithinEditPeriod($fstockmtdate, $header->fstockmtdate);
            $fsupplier = trim((string) $request->input('fsupplier'));
            $ffrom = trim((string) $request->input('ffrom'));
            $fket = trim((string) $request->input('fket', ''));
            $fbranchcode = $request->input('fbranchcode');
            $faccid = $request->input('faccid');
            $fprdjadi = $request->input('fprdjadi');
            $ftempohr = $request->input('ftempohr');
            $ftypebuy = $request->input('ftypebuy', $header->ftypebuy ?? 0);
            $isAdvancePaymentDetail = (string) $ftypebuy === '2';
            $fcurrency = $request->input('fcurrency', 'IDR');
            $frate = (float) $request->input('frate', 1);
            if ($frate <= 0) {
                $frate = 1;
            }
            $fincludeppn = 0; // PPN Faktur Pembelian selalu Exclude (0)

            if ($fapplyppn === 0) {
                $fincludeppn = 0;
                $fppnpersen = 0;
                $ppnAmount = 0.0;
            } else {
                $defaultPpnTarif = $this->getDefaultPpnTarif();
                $rawPpn = (float) $request->input('ppn_rate', $defaultPpnTarif);
                $fppnpersen = $rawPpn > 0 ? $rawPpn : $defaultPpnTarif;
                $ppnAmount = (float) $request->input('famountpajak', 0);
            }
            $frefno = $request->input('frefno');
            $frefpo = $request->input('frefpo');
            $now = now();

            // DETAIL ARRAYS
            $codes = $request->input('fitemcode', []);
            $satuans = $request->input('fsatuan', []);
            $refdtno = $request->input('frefdtno', []);
            $refdtids = $request->input('frefdtid', []);
            $sources = $request->input('fsource', []);
            $frefnoacaks = $request->input('frefnoacak', []);
            $qtys = $request->input('fqty', []);
            $prices = $request->input('fprice', []);
            $biayas = $request->input('fbiaya', []);
            $discs = $request->input('fdiscpersen', []);
            $descs = $request->input('fdesc', []);

            $typeBuy = (int) $request->input('ftypebuy', $header->ftypebuy ?? 0);
            $hasUM = collect($codes)->map(fn($code) => strtoupper(trim((string) $code)))->contains('UM');
            if ($typeBuy === 2) {
                $invalidAdvanceCodes = collect($codes)
                    ->map(fn($code) => trim((string) $code))
                    ->filter(fn($code) => $code !== '' && !str_starts_with(strtoupper($code), 'UM'))
                    ->unique()
                    ->values()
                    ->all();

                if (! empty($invalidAdvanceCodes)) {
                    $msg = 'Tipe Faktur Uang Muka hanya boleh menggunakan produk kode UM.';
                    if ($request->expectsJson()) {
                        return response()->json(['message' => $message], 422);
                    }

                    return back()
                        ->withInput()
                        ->with('error', $msg);
                }
            }

            $submittedCodes = collect($codes)
                ->map(fn($code) => trim((string) $code))
                ->filter(fn($code) => $code !== '')
                ->unique()
                ->values();

            if ($submittedCodes->isNotEmpty()) {
                if ((string) $ftypebuy === '1') {
                    $invalidServiceCodes = DB::table('msprd')
                        ->whereIn('fprdcode', $submittedCodes->all())
                        ->whereRaw("LOWER(TRIM(COALESCE(ftype, ''))) != ?", ['jasa'])
                        ->pluck('fprdcode')
                        ->all();

                    if (! empty($invalidServiceCodes)) {
                        $invalidList = implode(', ', $invalidServiceCodes);
                        $message = "Tipe Pembelian: Non Stok.\nHanya boleh input produk dengan tipe Jasa !!! (Kode item: {$invalidList})";
                        if ($request->expectsJson()) {
                            return response()->json(['message' => $message], 422);
                        }

                        return back()->withInput()->withErrors([
                            'detail' => $message,
                        ]);
                    }
                } else {
                    $invalidJasaCodes = DB::table('msprd')
                        ->whereIn('fprdcode', $submittedCodes->all())
                        ->whereRaw("LOWER(TRIM(COALESCE(ftype, ''))) = ?", ['jasa'])
                        ->pluck('fprdcode')
                        ->all();

                    if (! empty($invalidJasaCodes)) {
                        $invalidList = implode(', ', $invalidJasaCodes);
                        $typeName = match ((string) $ftypebuy) {
                            '0' => 'Stok',
                            '2' => 'Uang Muka',
                            '3' => 'Lain-lain',
                            default => 'Stok',
                        };
                        $message = "Tipe Pembelian: {$typeName}.\nProduk dengan tipe Jasa tidak boleh diinput untuk tipe ini !!! (Kode item: {$invalidList})";
                        if ($request->expectsJson()) {
                            return response()->json(['message' => $message], 422);
                        }

                        return back()->withInput()->withErrors([
                            'detail' => $message,
                        ]);
                    }
                }
            }

            if ($this->hasMixedOpeningBalanceAndSourceRows($codes, $qtys, $sources)) {
                return back()->withInput()->withErrors([
                    'detail' => 'Item awal tidak boleh digabung dengan item referensi PO / TER.',
                ]);
            }

            // LOAD PRODUCT METADATA
            $uniqueCodes = array_values(array_unique(array_filter(array_map(fn($c) => trim((string) $c), $codes))));
            $prodMeta = DB::table('msprd')
                ->whereIn('fprdcode', $uniqueCodes)
                ->get(['fprdid', 'fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2', 'fqtykecil', 'fqtykecil2'])
                ->keyBy('fprdcode');

            // BUILD DETAIL ROWS
            $rowsDt = [];
            $usedNoAcaks = [];
            $subtotal = 0.0;
            $rowCount = count($codes);
            $sourceUsageByRef = [];

            $requestSourceByRefId = [];
            foreach ($sources as $i => $sourceRaw) {
                $sourceType = strtoupper(trim((string) ($sourceRaw ?? '')));
                $detailId = (int) ($refdtids[$i] ?? 0);
                if (in_array($sourceType, ['PO', 'PB'], true) && $detailId > 0) {
                    $requestSourceByRefId[$detailId] = $sourceType;
                }
            }

            $oldUsageBySourceRef = [];
            $oldDetails = DB::table('trstockdt')
                ->where('fstockmtno', $header->fstockmtno)
                ->get(['frefdtid', 'fqtykecil']);

            foreach ($oldDetails as $oldDetail) {
                $detailId = (int) ($oldDetail->frefdtid ?? 0);
                $qtyUsed = (float) ($oldDetail->fqtykecil ?? 0);

                if ($detailId <= 0 || $qtyUsed <= 0) {
                    continue;
                }

                $sourceType = $requestSourceByRefId[$detailId] ?? $this->detectSourceTypeByDetailId($detailId);
                if (! in_array($sourceType, ['PO', 'PB'], true)) {
                    continue;
                }

                $sourceKey = $sourceType . ':' . $detailId;
                $oldUsageBySourceRef[$sourceKey] = ($oldUsageBySourceRef[$sourceKey] ?? 0) + $qtyUsed;
            }

            $sourceValidationCodes = [];
            $sourceValidationQtys = [];
            $sourceValidationSources = [];
            $sourceValidationRefdtids = [];
            $sourceValidationSatuans = [];

            foreach ($codes as $i => $code) {
                if ($this->isOpeningBalanceProductCode($code)) {
                    continue;
                }

                $sourceValidationCodes[] = $code;
                $sourceValidationQtys[] = $qtys[$i] ?? null;
                $sourceValidationSources[] = $sources[$i] ?? null;
                $sourceValidationRefdtids[] = $refdtids[$i] ?? null;
                $sourceValidationSatuans[] = $satuans[$i] ?? null;
            }

            $errors = $this->validateSourceRemainForRows(
                $sourceValidationCodes,
                $sourceValidationQtys,
                $sourceValidationSources,
                $sourceValidationRefdtids,
                $sourceValidationSatuans,
                $oldUsageBySourceRef
            );

            if ($errors->isNotEmpty()) {
                return back()->withErrors($errors)->withInput();
            }

            for ($i = 0; $i < $rowCount; $i++) {
                $code = trim((string) ($codes[$i] ?? ''));
                $qty = (float) ($qtys[$i] ?? 0);

                if ($code === '' || $qty <= 0) {
                    continue;
                }

                $meta = $prodMeta[$code] ?? null;
                if (! $meta) {
                    continue;
                }

                $isSaldoAwal = $this->isOpeningBalanceProductCode($code);
                $sat = trim((string) ($satuans[$i] ?? ''));
                if ($sat === '') {
                    $sat = $meta->fsatuankecil ?? '';
                }
                $sourceType = $isSaldoAwal ? '' : strtoupper(trim((string) ($sources[$i] ?? '')));
                $price = (float) ($prices[$i] ?? 0);
                $biaya = (float) ($biayas[$i] ?? 0);
                $discRaw = $this->normalizeDiscountInput($discs[$i] ?? 0);
                $discP = $this->parseDiscountExpression($discRaw);
                $desc = trim((string) ($descs[$i] ?? ''));

                // Konversi Satuan & Qty Kecil
                $qtyKecil = $qty;
                if ($sat === trim((string) ($meta->fsatuanbesar ?? '')) && (float) ($meta->fqtykecil ?? 0) > 0) {
                    $qtyKecil = $qty * (float) $meta->fqtykecil;
                } elseif ($sat === trim((string) ($meta->fsatuanbesar2 ?? '')) && (float) ($meta->fqtykecil2 ?? 0) > 0) {
                    $qtyKecil = $qty * (float) $meta->fqtykecil2;
                }
                if ($isSaldoAwal) {
                    $qtyKecil = 0;
                }

                $discAmount = $price * ($discP / 100);
                $priceNet = $price - $discAmount + $biaya;
                $amount = $qty * $priceNet;
                $subtotal += $amount;

                $rowsDt[] = [
                    'fprdcode' => $code,
                    'fnoacak' => $this->normalizeRandomNumber(null, $usedNoAcaks),
                    'frefdtno' => $isSaldoAwal ? null : (! empty($refdtno[$i]) ? $refdtno[$i] : null),
                    'frefso' => $sourceType === 'PO' ? (! empty($refdtno[$i]) ? $refdtno[$i] : null) : null,
                    'frefdtid' => $isSaldoAwal ? null : (isset($refdtids[$i]) ? (int) $refdtids[$i] : null),
                    'frefnoacak' => $isSaldoAwal ? null : $this->normalizeReferenceRandomNumberSingle($frefnoacaks[$i] ?? null),
                    'fqty' => $qty,
                    'fqtykecil' => $qtyKecil,
                    'fqtyremain' => $qtyKecil,
                    'fprice' => $price,
                    'fbiaya' => $biaya,
                    'fpricenet' => $priceNet,
                    'fprice_rp' => $price * $frate,
                    'ftotprice' => $amount,
                    'ftotprice_rp' => $amount * $frate,
                    'fuserupdate' => $userName,
                    'fdatetime' => $now,
                    'fdesc' => $desc ?: null,
                    'fketdt' => $desc ?: null,
                    'fcode' => ($isAdvancePaymentDetail || $sourceType === 'PO') ? 'P' : 'T',
                    'fdiscpersen' => $discRaw,
                    'fsatuan' => $sat,
                    'fclosedt' => '0',
                ];

                $detailId = isset($refdtids[$i]) ? (int) $refdtids[$i] : 0;
                if (in_array($sourceType, ['PO', 'PB'], true) && $detailId > 0) {
                    $sourceKey = $sourceType . ':' . $detailId;
                    $sourceUsageByRef[$sourceKey] = ($sourceUsageByRef[$sourceKey] ?? 0) + $qtyKecil;
                }
            }

            if (empty($rowsDt)) {
                return back()->withInput()->withErrors(['detail' => 'Minimal satu item valid (Kode, Satuan, Qty > 0).']);
            }

            if ($stockResponse = $this->validateStockMinusLines(
                $this->buildStockMinusLinesFromNetChange($rowsDt, (string) $ffrom, $this->fetchStockDetailRows((string) $header->fstockmtno), (string) $header->ffrom),
                $request->boolean('force_save')
            )) {
                return $stockResponse;
            }

            $grandTotal = $subtotal + $ppnAmount;

            // DATABASE TRANSACTION
            DB::transaction(function () use (
                $request,
                $header,
                $fstockmtdate,
                $fsupplier,
                $ffrom,
                $fket,
                $fcurrency,
                $frate,
                $fincludeppn,
                $fapplyppn,
                $fppnpersen,
                $now,
                $ftempohr,
                $ftypebuy,
                &$fstockmtno,
                &$rowsDt,
                $subtotal,
                $ppnAmount,
                $grandTotal,
                $faccid,
                $fprdjadi,
                $fbranchcode,
                $oldUsageBySourceRef,
                $sourceUsageByRef,
                $userName,
                $userIdLog
            ) {
                $trxLogId = 'LOG' . $now->format('YmdHis') . rand(100, 999);

                $kodeCabang = 'NA';
                if (! empty($fbranchcode)) {
                    $qCabang = DB::table('mscabang');

                    if (is_numeric($fbranchcode)) {
                        $qCabang->where('fcabangid', (int) $fbranchcode);
                    } else {
                        $qCabang->where('fcabangkode', $fbranchcode);
                    }

                    $cabang = $qCabang->first();
                    $kodeCabang = $cabang ? $cabang->fcabangkode : 'NA';
                }

                $paidAmount = (float) DB::table('trkasmt as m')
                    ->join('trkasdt as d', 'm.fkasmtid', '=', 'd.fkasmtid')
                    ->where('m.ftrancode', 'PAY')
                    ->where('d.frefno', $header->fstockmtno)
                    ->selectRaw('COALESCE(SUM(COALESCE(d.fkasdtvalue, 0) + COALESCE(d.fdiscount, 0)), 0) as total')
                    ->value('total');
                $journalPaidAmount = (float) DB::table('jurnalmt as m')
                    ->join('jurnaldt as d', 'm.fjurnalno', '=', 'd.fjurnalno')
                    ->join('account as a', 'a.faccount', '=', 'd.faccount')
                    ->where('d.frefno', $header->fstockmtno)
                    ->whereIn('a.faccupline', function ($sub) {
                        $sub->select('faccount')
                            ->from('set_account')
                            ->where('faccount_name', 'HUTANGDAGANG');
                    })
                    ->where('d.fdk', 'D')
                    ->selectRaw('COALESCE(SUM(d.famount), 0) as total')
                    ->value('total');
                $paidAmountRp = (float) DB::table('trkasmt as m')
                    ->join('trkasdt as d', 'm.fkasmtid', '=', 'd.fkasmtid')
                    ->where('m.ftrancode', 'PAY')
                    ->where('d.frefno', $header->fstockmtno)
                    ->selectRaw('COALESCE(SUM(COALESCE(d.fvalue_rp, 0) + COALESCE(d.fdiscountrp, 0)), 0) as total')
                    ->value('total');
                $journalPaidAmountRp = (float) DB::table('jurnalmt as m')
                    ->join('jurnaldt as d', 'm.fjurnalno', '=', 'd.fjurnalno')
                    ->join('account as a', 'a.faccount', '=', 'd.faccount')
                    ->where('d.frefno', $header->fstockmtno)
                    ->whereIn('a.faccupline', function ($sub) {
                        $sub->select('faccount')
                            ->from('set_account')
                            ->where('faccount_name', 'HUTANGDAGANG');
                    })
                    ->where('d.fdk', 'D')
                    ->selectRaw('COALESCE(SUM(d.famount_rp), 0) as total')
                    ->value('total');
                $amountRemain = max($grandTotal - ($paidAmount + $journalPaidAmount), 0);
                $amountRemainRp = max(($grandTotal * $frate) - ($paidAmountRp + $journalPaidAmountRp), 0);

                $ftrancode = match ((string) $ftypebuy) {
                    '0' => '0',
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    default => '0',
                };

                // Update Header
                $header->update([
                    'fstockmtdate' => $fstockmtdate,
                    'fsupplier' => $fsupplier,
                    'fcurrency' => $fcurrency,
                    'frate' => $frate,
                    'famount' => round($subtotal, 2),
                    'famount_rp' => round($subtotal * $frate, 2),
                    'famountpajak' => round($ppnAmount, 2),
                    'famountpajak_rp' => round($ppnAmount * $frate, 2),
                    'famountmt' => round($grandTotal, 2),
                    'famountmt_rp' => round($grandTotal * $frate, 2),
                    'famountremain' => round($amountRemain, 2),
                    'famountremain_rp' => round($amountRemainRp, 2),
                    'frefno' => $request->input('frefno'),
                    'frefpo' => $request->input('frefpo'),
                    'ftrancode' => $ftrancode,
                    'ffrom' => $ffrom,
                    'fprdjadi' => $fprdjadi,
                    'fprdjadiid' => $faccid,
                    'fket' => $fket,
                    'fuserupdate' => $userName,
                    'fdatetime' => $now,
                    'fbranchcode' => $kodeCabang,
                    'ftempohr' => $ftempohr,
                    'fincludeppn' => $fincludeppn,
                    'fapplyppn' => $fapplyppn,
                    'fppnpersen' => $fppnpersen,
                    'ftypebuy' => $ftypebuy,
                    'fjatuhtempo' => $request->input('fjatuhtempo') ? \Carbon\Carbon::parse($request->input('fjatuhtempo'))->startOfDay() : null,
                ]);

                $updatedHeader = PenerimaanPembelianHeader::findOrFail($header->fstockmtid);

                // 1. INSERT Log Header (Update)
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

                // Hapus detail lama dan masukkan yang baru
                $this->adjustSourceQtyKecil($oldUsageBySourceRef, 1);
                $header->details()->delete();

                foreach ($rowsDt as &$r) {
                    $r['fstockmtcode'] = 'BUY';
                    $r['fstockmtno'] = $fstockmtno;

                    $insertedDtId = DB::table('trstockdt')->insertGetId($r, 'fstockdtid');
                    $dtObj = DB::table('trstockdt')->where('fstockdtid', $insertedDtId)->first();

                    // 2. INSERT Log Detail (Update)
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

                $this->adjustSourceQtyKecil($sourceUsageByRef, -1);

                $this->syncFakturPembelianJournalEntries(
                    (string) $fstockmtno,
                    $fstockmtdate,
                    (string) $kodeCabang,
                    (string) $fsupplier,
                    (string) $userName,
                    (int) $ftypebuy,
                    (string) $fcurrency,
                    (string) $fprdjadi,
                    (string) $frate
                );
            });

            $successMessage = "Faktur pembelian {$fstockmtno} berhasil diupdate.";

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $successMessage,
                    'redirect_url' => route('fakturpembelian.index'),
                ]);
            }

            return redirect()
                ->route('fakturpembelian.index')
                ->with('success', $successMessage);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();

            if ($request->expectsJson()) {
                return response()->json(['message' => $firstError ?: 'Gagal update faktur pembelian.'], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('error', $firstError ?: 'Gagal mengupdate faktur pembelian. Cek data.');
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
                ], 500);
            }
            return back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    public function delete(Request $request, $fstockmtid)
    {
        $supplierAdvanceWarnings = $this->getSupplierAdvanceWarningMap();
        $suppliers = Supplier::orderBy('fsuppliername', 'asc')
            ->get(['fsuppliercode', 'fsuppliername']);

        $fakturpembelian = PenerimaanPembelianHeader::with([
            'details' => function ($query) {
                $query
                    ->join('msprd', 'msprd.fprdcode', '=', 'trstockdt.fprdcode')
                    ->select(
                        'trstockdt.*',
                        'msprd.fprdname',
                        'msprd.fprdcode as fitemcode_text'
                    )
                    ->orderBy('trstockdt.fstockdtid', 'asc');
            },
        ])
            ->findOrFail($fstockmtid); // Temukan header berdasarkan $fstockmtid

        if ($message = $this->getPostedPeriodLockMessage($fakturpembelian->fstockmtdate)) {
            return redirect()
                ->route('fakturpembelian.edit', $fakturpembelian->fstockmtid)
                ->with('error', $message);
        }

        // 2. Ambil kode akun yang tersimpan dari faktur
        $savedAccountCode = $fakturpembelian->fprdjadi;

        // 3. UBAH QUERY INI: Gunakan $savedAccountCode
        $accounts = DB::table('account')
            ->select('faccid', 'faccount', 'faccname', 'fnonactive')
            ->where('fend', 1)
            ->where('fnonactive', '0')
            ->orderBy('faccount') // <-- Perbaikan nama kolom
            ->get();

        // --- Sisa kode Anda ---
        $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

        $branch = DB::table('mscabang')
            ->when(is_numeric($raw), fn($q) => $q->where('fcabangid', (int) $raw))
            ->when(! is_numeric($raw), fn($q) => $q
                ->where('fcabangkode', $raw)
                ->orWhere('fcabangname', $raw))
            ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

        $warehouses = DB::table('mswh')
            ->select('fwhid', 'fwhcode', 'fwhname', 'fbranchcode', 'fnonactive')
            ->where('fnonactive', '0') // hanya yang aktif
            ->orderBy('fwhcode')
            ->get();

        $defaultCabang = $branch->fcabangname ?? (string) $raw;
        $defaultBranchCode = $branch->fcabangkode ?? (string) $raw;
        $savedBranchCode = trim((string) ($fakturpembelian->fbranchcode ?? ''));
        $savedBranchName = $savedBranchCode !== ''
            ? DB::table('mscabang')->where('fcabangkode', $savedBranchCode)->value('fcabangname')
            : null;
        $currentAccount = trim($fakturpembelian->fprdjadi ?? '');
        $currentAccountRecord = $accounts->firstWhere('faccount', trim($fakturpembelian->fprdjadi ?? ''));
        $currentAccountId = $currentAccountRecord?->faccid ?? '';
        $currentAccountName = $currentAccountRecord?->faccname ?? '';
        [$poUnits, $pbUnits] = $this->getReferenceUnitMaps($fakturpembelian->details);

        $savedItems = $fakturpembelian->details->map(function ($d) use ($poUnits, $pbUnits) {
            return [
                'uid' => $d->fstockdtid,
                'fitemcode' => $d->fitemcode_text ?? '',
                'fitemname' => $d->fprdname ?? '',
                'fsatuan' => $this->resolveDetailDisplayUnit($d, $poUnits, $pbUnits),
                'fdisplayunit' => $this->resolveDetailDisplayUnit($d, $poUnits, $pbUnits),
                'fprno' => $d->frefpr ?? '-',
                'frefpr' => $d->frefpr ?? null,
                'fpono' => $d->fpono ?? null,
                'famountponet' => $d->famountponet ?? null,
                'famountpo' => $d->famountpo ?? null,
                'frefdtno' => $d->frefdtno ?? null,
                'frefnoacak' => $d->frefnoacak ?? null,
                'fqty' => (float) ($d->fqty ?? 0),
                'fterima' => (float) ($d->fterima ?? 0),
                'fprice' => (float) ($d->fprice ?? 0),
                'fdiscpersen' => $this->normalizeDiscountInput($d->fdiscpersen ?? 0),
                'fbiaya' => (float) ($d->fbiaya ?? 0),
                'ftotprice' => (float) ($d->ftotprice ?? 0),
                'ftotal' => (float) ($d->ftotprice ?? 0),
                'fdesc' => is_array($d->fdesc)
                    ? implode(', ', $d->fdesc)
                    : (trim((string) ($d->fdesc ?? '')) !== '' ? $d->fdesc : ($d->fketdt ?? '')),
                'fketdt' => $d->fketdt ?? '',
                'units' => [],
            ];
        })->values();

        $selectedSupplierCode = $fakturpembelian->fsupplier;

        $products = $this->browseProducts();
        $productMap = $this->browseProductMap($products);
        $biayaGlobal = (float) $savedItems->sum(function ($item) {
            return ((float) ($item['fbiaya'] ?? 0)) * ((float) ($item['fqty'] ?? 0));
        });

        return view('fakturpembelian.edit', [
            'suppliers' => $suppliers,
            'selectedSupplierCode' => $selectedSupplierCode,
            'fcabang' => $savedBranchName ?? $defaultCabang,
            'fbranchcode' => $savedBranchCode ?: $defaultBranchCode,
            'warehouses' => $warehouses,
            'products' => $products,
            'accounts' => $accounts,
            'productMap' => $productMap,
            'currentAccount' => $currentAccount,
            'currentAccountId' => $currentAccountId,
            'currentAccountName' => $currentAccountName,
            'fakturpembelian' => $fakturpembelian,
            'displayFstockmtno' => $this->formatDisplayTransactionNumber($fakturpembelian->fstockmtno ?? null, (int) ($fakturpembelian->fapplyppn ?? 0) === 0 && (int) ($fakturpembelian->fincludeppn ?? 0) === 0),
            'savedItems' => $savedItems,
            'biayaGlobal' => $biayaGlobal,
            'ppnAmount' => (float) ($fakturpembelian->famountpopajak ?? 0),
            'famountponet' => (float) ($fakturpembelian->famountponet ?? 0),
            'famountpo' => (float) ($fakturpembelian->famountpo ?? 0),
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'isUsageLocked' => false,
            'usageLockMessage' => null,
            'action' => 'delete',
            'supplierAdvanceWarnings' => $supplierAdvanceWarnings,
        ]);
    }

    public function destroy($fstockmtid)
    {
        try {
            $fakturpembelian = PenerimaanPembelianHeader::findOrFail($fstockmtid);

            if ($message = $this->getPostedPeriodLockMessage($fakturpembelian->fstockmtdate)) {
                return redirect()->route('fakturpembelian.edit', $fakturpembelian->fstockmtid)->with('error', $message);
            }

            if ($message = $this->getUsageLockMessage($fakturpembelian)) {
                return redirect()->route('fakturpembelian.index')->with('error', $message);
            }

            if ($stockResponse = $this->validateStockMinusLines(
                $this->buildStockMinusLinesFromNetChange([], (string) $fakturpembelian->ffrom, $this->fetchStockDetailRows((string) $fakturpembelian->fstockmtno), (string) $fakturpembelian->ffrom),
                request()->boolean('force_save')
            )) {
                return $stockResponse;
            }

            $userLogin = Auth::guard('sysuser')->user() ?? Auth::user();
            $userIdLog = $userLogin->fuserid ?? 'SYSTEM';

            DB::transaction(function () use ($fakturpembelian, $userIdLog) {
                $now = now();
                $trxLogId = 'LOG' . $now->format('YmdHis') . rand(100, 999);

                // 1. INSERT Log Header (Delete)
                DB::table('log_trstockmt')->insert([
                    'ftrxlogid'        => $trxLogId,
                    'fstockmtid'       => $fakturpembelian->fstockmtid,
                    'fstockmtno'       => $fakturpembelian->fstockmtno,
                    'fbranchcode'      => $fakturpembelian->fbranchcode,
                    'fstockmtcode'     => $fakturpembelian->fstockmtcode,
                    'fstockmtdate'     => $fakturpembelian->fstockmtdate,
                    'fprdout'          => $fakturpembelian->fprdout,
                    'fsupplier'        => $fakturpembelian->fsupplier,
                    'fcurrency'        => $fakturpembelian->fcurrency,
                    'frate'            => $fakturpembelian->frate,
                    'ftypebuy'         => $fakturpembelian->ftypebuy,
                    'ftempohr'         => $fakturpembelian->ftempohr,
                    'ftrancode'        => $fakturpembelian->ftrancode,
                    'fsalesman'        => $fakturpembelian->fsalesman,
                    'fjatuhtempo'      => $fakturpembelian->fjatuhtempo,
                    'fprint'           => $fakturpembelian->fprint,
                    'fsudahtagih'      => $fakturpembelian->fsudahtagih,
                    'fdiscount'        => $fakturpembelian->fdiscount,
                    'fupdatedat'       => $fakturpembelian->fupdatedat,
                    'famount'          => $fakturpembelian->famount,
                    'famount_rp'       => $fakturpembelian->famount_rp,
                    'famountpajak'     => $fakturpembelian->famountpajak,
                    'famountpajak_rp'  => $fakturpembelian->famountpajak_rp,
                    'famountmt'        => $fakturpembelian->famountmt,
                    'famountmt_rp'     => $fakturpembelian->famountmt_rp,
                    'famountremain'    => $fakturpembelian->famountremain,
                    'famountremain_rp' => $fakturpembelian->famountremain_rp,
                    'frefno'           => $fakturpembelian->frefno,
                    'frefpo'           => $fakturpembelian->frefpo,
                    'ffrom'            => $fakturpembelian->ffrom,
                    'fto'              => $fakturpembelian->fto,
                    'fkirim'           => $fakturpembelian->fkirim,
                    'fprdjadi'         => $fakturpembelian->fprdjadi,
                    'fqtyjadi'         => $fakturpembelian->fqtyjadi,
                    'fket'             => $fakturpembelian->fket,
                    'fincludeppn'      => $fakturpembelian->fincludeppn,
                    'fppnpersen'       => $fakturpembelian->fppnpersen,
                    'fapplyppn'        => $fakturpembelian->fapplyppn,
                    'fketinternal'     => $fakturpembelian->fketinternal,
                    'fusercreate'      => $fakturpembelian->fusercreate,
                    'fdatetime'        => $fakturpembelian->fdatetime,
                    'fuserupdate'      => $fakturpembelian->fuserupdate,
                    'feditmode'        => 'D',
                    'fuseridlog'       => $userIdLog,
                    'fdatetimelog'     => $now,
                ]);

                $oldUsageBySourceRef = [];
                $oldDetails = DB::table('trstockdt')
                    ->where('fstockmtno', $fakturpembelian->fstockmtno)
                    ->get();

                // 2. Ambil seluruh detail lalu catat ke log_trstockdt (Delete)
                foreach ($oldDetails as $oldDetail) {
                    DB::table('log_trstockdt')->insert([
                        'ftrxlogid'     => $trxLogId,
                        'fstockdtid'    => $oldDetail->fstockdtid,
                        'fstockmtcode'  => $oldDetail->fstockmtcode,
                        'fstockmtno'    => $oldDetail->fstockmtno,
                        'fprdcode'      => $oldDetail->fprdcode,
                        'frefdtno'      => $oldDetail->frefdtno,
                        'fqty'          => $oldDetail->fqty,
                        'fqtyremain'    => $oldDetail->fqtyremain,
                        'fsatuan'       => $oldDetail->fsatuan,
                        'fqtykecil'     => $oldDetail->fqtykecil,
                        'fprice'        => $oldDetail->fprice,
                        'fprice_rp'     => $oldDetail->fprice_rp,
                        'ftotprice'     => $oldDetail->ftotprice,
                        'ftotprice_rp'  => $oldDetail->ftotprice_rp,
                        'fketdt'        => $oldDetail->fketdt,
                        'fcode'         => $oldDetail->fcode,
                        'frefso'        => $oldDetail->frefso,
                        'fdesc'         => $oldDetail->fdesc,
                        'fclosedt'      => $oldDetail->fclosedt,
                        'fdiscpersen'   => $oldDetail->fdiscpersen,
                        'fbiaya'        => $oldDetail->fbiaya,
                        'fpricenet'     => $oldDetail->fpricenet,
                        'fnoacak'       => $oldDetail->fnoacak,
                        'frefnoacak'    => $oldDetail->frefnoacak,
                        'frefnoacak_so' => $oldDetail->frefnoacak_so,
                        'fusercreate'   => $oldDetail->fusercreate,
                        'fdatetime'     => $oldDetail->fdatetime,
                        'fupdatedat'    => $oldDetail->fupdatedat,
                        'fuserupdate'   => $oldDetail->fuserupdate,
                        'feditmode'     => 'D',
                        'fuseridlog'    => $userIdLog,
                        'fdatetimelog'  => $now,
                    ]);

                    $detailId = (int) ($oldDetail->frefdtid ?? 0);
                    $qtyUsed = (float) ($oldDetail->fqtykecil ?? 0);

                    if ($detailId <= 0 || $qtyUsed <= 0) {
                        continue;
                    }

                    $sourceType = $this->detectSourceTypeByDetailId($detailId);
                    if (! in_array($sourceType, ['PO', 'PB'], true)) {
                        continue;
                    }

                    $sourceKey = $sourceType . ':' . $detailId;
                    $oldUsageBySourceRef[$sourceKey] = ($oldUsageBySourceRef[$sourceKey] ?? 0) + $qtyUsed;
                }

                $this->adjustSourceQtyKecil($oldUsageBySourceRef, 1);
                DB::table('trstockdt')
                    ->where('fstockmtno', $fakturpembelian->fstockmtno)
                    ->delete();

                $this->deleteFakturPembelianJournalEntries((string) $fakturpembelian->fstockmtno);

                $fakturpembelian->delete();
            });

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Faktur pembelian berhasil dihapus.',
                    'redirect_url' => route('fakturpembelian.index'),
                ]);
            }

            return redirect()->route('fakturpembelian.index')->with('success', 'Faktur pembelian berhasil dihapus.');
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Faktur pembelian belum bisa dihapus. Coba lagi: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->route('fakturpembelian.delete', $fstockmtid)->with('error', 'Faktur pembelian belum bisa dihapus. Coba lagi.');
        }
    }

    private function getUsageLockMessage(PenerimaanPembelianHeader $header): ?string
    {
        $usedBy = DB::table('trstockmt')
            ->whereIn('fstockmtcode', ['REB', 'RUB'])
            ->where(function ($query) use ($header) {
                $query->where('frefno', $header->fstockmtno)
                    ->orWhere('frefpo', $header->fstockmtno);
            })
            ->select('fstockmtno')
            ->distinct()
            ->orderBy('fstockmtno')
            ->pluck('fstockmtno');

        if ($usedBy->isEmpty()) {
            return null;
        }

        return 'Faktur pembelian ' . (string) $header->fstockmtno . ' sudah dipakai retur pembelian: ' . $usedBy->implode(', ') . '.';
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

    private function parseDiscountExpression($discInput): float
    {
        $normalized = $this->normalizeDiscountInput($discInput);

        if ($normalized === '0') {
            return 0;
        }

        $parts = array_filter(explode('+', $normalized), fn($part) => $part !== '');
        if (empty($parts)) {
            return 0;
        }

        $total = 0.0;
        foreach ($parts as $part) {
            if (! is_numeric($part)) {
                return 0;
            }
            $total += (float) $part;
        }

        return max(0, min(100, round($total, 4)));
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
                ->where('fstockmtcode', 'BUY')
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
                return 'No. referensi sudah ada di transaksi ' . strtoupper(trim((string) ($existing->fstockmtno ?? ''))) . '.';
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

    private function syncFakturPembelianJournalEntries(
        string $fstockmtno,
        Carbon $fstockmtdate,
        string $kodeCabang,
        string $fsupplier,
        string $userid,
        int $ftypebuy = 0,
        string $fcurrency = 'IDR',
        ?string $toAccount = null,
        ?string $rateText = null
    ): void {
        $trancodeIndex = match ($ftypebuy) {
            1 => 1,
            2 => 2,
            default => 0,
        };

        JurnalFakturPembelian::sync(
            $fstockmtno,
            $fstockmtdate,
            $kodeCabang,
            $fsupplier,
            $userid,
            $trancodeIndex,
            $fcurrency,
            $toAccount,
            $rateText
        );
    }

    private function deleteFakturPembelianJournalEntries(string $fstockmtno): void
    {
        JurnalFakturPembelian::delete($fstockmtno);
    }
}
