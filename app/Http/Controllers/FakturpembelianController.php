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

    private function formatDisplayTransactionNumber(?string $number, bool $useSlash = false): string
    {
        $normalized = trim((string) $number);
        if ($normalized === '') {
            return '-';
        }

        $separator = $useSlash ? '/' : '.';

        return (string) preg_replace('/[.\/](\d+)$/', $separator.'$1', $normalized, 1);
    }

    private function getReferenceUnitMaps($details): array
    {
        $detailRows = collect($details);

        $poIds = $detailRows
            ->filter(fn ($detail) => (int) ($detail->frefdtid ?? 0) > 0 && trim((string) ($detail->frefso ?? '')) !== '')
            ->map(fn ($detail) => (int) $detail->frefdtid)
            ->unique()
            ->values()
            ->all();

        $pbIds = $detailRows
            ->filter(fn ($detail) => (int) ($detail->frefdtid ?? 0) > 0 && trim((string) ($detail->frefso ?? '')) === '')
            ->map(fn ($detail) => (int) $detail->frefdtid)
            ->unique()
            ->values()
            ->all();

        $poUnits = empty($poIds)
            ? []
            : DB::table('tr_pod')
                ->whereIn('fpodid', $poIds)
                ->pluck('fsatuan', 'fpodid')
                ->map(fn ($value) => trim((string) $value))
                ->all();

        $pbUnits = empty($pbIds)
            ? []
            : DB::table('trstockdt')
                ->whereIn('fstockdtid', $pbIds)
                ->pluck('fsatuan', 'fstockdtid')
                ->map(fn ($value) => trim((string) $value))
                ->all();

        return [$poUnits, $pbUnits];
    }

    private function resolveDetailDisplayUnit($detail, array $poUnits = [], array $pbUnits = []): string
    {
        return trim((string) ($detail->fsatuan ?? ''));
    }

    private function getSupplierAdvanceWarningMap(): array
    {
        return DB::table('trstockmt')
            ->selectRaw('TRIM(COALESCE(fsupplier, \'\')) as fsupplier')
            ->selectRaw('SUM(COALESCE(famountremain, 0)) as total_remain')
            ->selectRaw('SUM(COALESCE(famountremain_rp, 0)) as total_remain_rp')
            ->where('fstockmtcode', 'BUY')
            ->where('ftypebuy', 2)
            ->where(function ($query) {
                $query->where('famountremain', '>', 0)
                    ->orWhere('famountremain_rp', '>', 0);
            })
            ->groupBy(DB::raw('TRIM(COALESCE(fsupplier, \'\'))'))
            ->get()
            ->filter(fn ($row) => trim((string) ($row->fsupplier ?? '')) !== '')
            ->mapWithKeys(function ($row) {
                $supplierCode = trim((string) ($row->fsupplier ?? ''));
                $remainRp = (float) ($row->total_remain_rp ?? 0);

                return [
                    $supplierCode => [
                        'message' => $remainRp > 0
                            ? 'Supplier ini ada sisa uang muka sebesar Rp '.number_format($remainRp, 2, ',', '.').'.'
                            : 'Supplier ini ada sisa uang muka.',
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

        $availableYearsQuery = PenerimaanPembelianHeader::selectRaw('DISTINCT EXTRACT(YEAR FROM fdatetime) as year')
            ->where('fstockmtcode', 'BUY')
            ->whereNotNull('fdatetime');
        $this->applyBranchVisibilityScope($availableYearsQuery, 'trstockmt.fbranchcode');
        $availableYears = $availableYearsQuery
            ->orderByRaw('EXTRACT(YEAR FROM fdatetime) DESC')
            ->pluck('year');

        if ($request->ajax()) {
            $query = PenerimaanPembelianHeader::query()
                ->where('trstockmt.fstockmtcode', 'BUY')
                ->leftJoin('mssupplier', 'trstockmt.fsupplier', '=', 'mssupplier.fsuppliercode')
                ->leftJoin('mswh', 'trstockmt.ffrom', '=', 'mswh.fwhcode');
            $this->applyBranchVisibilityScope($query, 'trstockmt.fbranchcode');
            $totalRecords = (clone $query)->count();
            if ($search = trim((string) $request->input('search.value'))) {
                $likeOp = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
                $query->where(function ($q) use ($search, $likeOp) {
                    $q->where('trstockmt.fstockmtno', $likeOp, "%{$search}%")
                        ->orWhere('trstockmt.frefno', $likeOp, "%{$search}%")
                        ->orWhere('trstockmt.frefpo', $likeOp, "%{$search}%")
                        ->orWhere('mssupplier.fsuppliername', $likeOp, "%{$search}%")
                        ->orWhere('mssupplier.fsuppliercode', $likeOp, "%{$search}%");
                });
            }

            // Pencarian per kolom
            $colSearchGudang = $request->input('columns.3.search.value');
            if ($colSearchGudang !== null && $colSearchGudang !== '') {
                $likeOp = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
                $query->where(function ($q) use ($colSearchGudang, $likeOp) {
                    $q->where('trstockmt.ffrom', $likeOp, "%{$colSearchGudang}%")
                        ->orWhere('mswh.fwhname', $likeOp, "%{$colSearchGudang}%");
                });
            }
            if ($year) {
                $query->whereRaw('EXTRACT(YEAR FROM trstockmt.fdatetime) = ?', [$year]);
            }
            if ($month) {
                $query->whereRaw('EXTRACT(MONTH FROM trstockmt.fdatetime) = ?', [$month]);
            }
            $filteredRecords = (clone $query)->count();
            $orderColIdx = $request->input('order.0.column', 0);
            $orderDir = $request->input('order.0.dir', 'desc');

            $sortableColumns = [
                'trstockmt.fstockmtno',
                'trstockmt.fstockmtdate',
                'trstockmt.frefno',
                'mswh.fwhname',
                'mssupplier.fsuppliername',
                'trstockmt.frefpo',
                'trstockmt.famountmt',
            ];

            if (isset($sortableColumns[$orderColIdx])) {
                $query->orderBy($sortableColumns[$orderColIdx], $orderDir);
            } else {
                $query->orderBy('fstockmtid', 'desc');
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
                    'trstockmt.frefno',
                    'trstockmt.frefpo',
                    'trstockmt.famountmt',
                    'trstockmt.ffrom',
                    'mswh.fwhname',
                    'mssupplier.fsuppliername',
                ]);

            $data = $records->map(function ($row) {
                $warehouse = trim((string) ($row->fwhname ?? ''));
                $warehouseCode = trim((string) ($row->ffrom ?? ''));
                $reference = trim((string) ($row->frefpo ?? '')) ?: trim((string) ($row->frefno ?? ''));

                return [
                    'fstockmtid' => $row->fstockmtid,
                    'fstockmtno' => $row->fstockmtno,
                    'fstockmtno_display' => $this->formatDisplayTransactionNumber($row->fstockmtno, (int) ($row->fapplyppn ?? 0) === 1),
                    'fstockmtdate' => Carbon::parse($row->fstockmtdate)->format('d/m/Y'),
                    'ffakturno' => trim((string) ($row->frefno ?? '')),
                    'fgudang' => $warehouse !== '' ? trim($warehouseCode.' - '.$warehouse) : $warehouseCode,
                    'fsuppliername' => trim((string) ($row->fsuppliername ?? '')),
                    'freferensi' => $reference,
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
            'month'
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
            ->where('tr_poh.fprdin', '0');

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
            ->orderBy('tr_pod.fprdcode')
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

        $buySub = DB::table('trstockdt')
            ->selectRaw('frefdtno, fprdcode, SUM(COALESCE(fqtykecil, 0)) AS fqtybuy')
            ->where('fstockmtcode', 'BUY')
            ->groupBy('frefdtno', 'fprdcode');

        $query = PenerimaanPembelianHeader::query()
            ->leftJoin('mssupplier', 'trstockmt.fsupplier', '=', 'mssupplier.fsuppliercode')
            ->select([
                'trstockmt.fstockmtid',
                'trstockmt.fstockmtno',
                'mssupplier.fsuppliername',
                'trstockmt.fstockmtdate',
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
                    ->orWhere('mssupplier.fsuppliername', $likeOp, "%{$search}%")
                    ->orWhereRaw("TO_CHAR(trstockmt.fstockmtdate, 'YYYY-MM-DD HH24:MI:SS') {$likeOp} ?", ["%{$search}%"]);
            });
        }

        $recordsFiltered = (clone $query)->count();

        $query->orderByDesc('trstockmt.fstockmtdate')
            ->orderByDesc('trstockmt.fstockmtid');

        $rows = $query->skip($start)->take($perPage)->get()->map(function ($t) {
            return [
                'fstockmtid' => $t->fstockmtid,
                'fstockmtno' => $t->fstockmtno,
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
            ->orderBy('trstockdt.fprdcode')
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
                'fsupplier' => trim($header->fsupplier ?? ''),
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

        if ($sat !== '' && $satBesar !== '' && strcasecmp($sat, $satBesar) === 0 && $rasio > 0) {
            return $qtyKecil / $rasio;
        }

        if ($sat !== '' && $satBesar2 !== '' && strcasecmp($sat, $satBesar2) === 0 && $rasio2 > 0) {
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

        if ($sat !== '' && $satBesar !== '' && strcasecmp($sat, $satBesar) === 0 && $rasio > 0) {
            return $qty * $rasio;
        }

        if ($sat !== '' && $satBesar2 !== '' && strcasecmp($sat, $satBesar2) === 0 && $rasio2 > 0) {
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
                ->selectRaw('d.fpodid as detail_id, COALESCE(d.fqtykecil, 0) - COALESCE(u.qty_used, 0) as remain_kecil')
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
                ->selectRaw('d.fstockdtid as detail_id, COALESCE(d.fqtykecil, 0) - COALESCE(u.qty_used, 0) as remain_kecil')
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
            $availableKecil = $remainKecil + (float) ($extraAvailableBySourceRef[$sourceKey] ?? 0);
            if ($needKecil > $availableKecil + $tolerance) {
                $available = $this->qtyKecilToSourceUnit((object) [
                    'fsatuan' => $sat,
                    'fsatuanbesar' => $product->fsatuanbesar ?? '',
                    'fsatuanbesar2' => $product->fsatuanbesar2 ?? '',
                    'fqtykecil_master' => $product->fqtykecil_master ?? 0,
                    'fqtykecil2_master' => $product->fqtykecil2_master ?? 0,
                ], $availableKecil);
                $errors->add("fqty.$i", "Qty item {$code} melebihi sisa {$sourceType}. Maksimal {$available}.");
            }
        }

        return $errors;
    }

    private function generatetr_poh_Code(?Carbon $onDate = null, $branch = null): string
    {
        $date = $onDate ?: now();

        $branch = $branch
            ?? Auth::guard('sysuser')->user()?->fcabang
            ?? Auth::user()?->fcabang
            ?? null;

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

        $lockKey = crc32('PO|' . $kodeCabang . '|' . $date->format('Y-m'));
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

            $last = DB::table('tr_poh')
                ->where('fpono', 'like', $prefix . '%')
                ->selectRaw("MAX(CAST(split_part(fpono, '.', 5) AS int)) AS lastno")
                ->value('lastno');

            $next = (int) $last + 1;
        } else {
            $lastCode = DB::table('tr_poh')
                ->where('fpono', 'like', $prefix . '%')
                ->orderByDesc('fpono')
                ->value('fpono');

            $next = 1;
            if ($lastCode && ($pos = strrpos($lastCode, '.')) !== false) {
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
            ->where('trstockmt.fstockmtno', $fstockmtno)
            ->first([
                'trstockmt.*',
                's.fsuppliername as supplier_name',
                'c.fcabangname as cabang_name',
                'w.fwhname as fwhnamen',
            ]);

        if (! $hdr) {
            return redirect()->back()->with('error', 'PO tidak ada.');
        }

        $dt = PenerimaanPembelianDetail::query()
            ->leftJoin('msprd as p', 'p.fprdcode', '=', 'trstockdt.fprdcode')
            ->where('trstockdt.fstockmtno', $fstockmtno)
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
            'displayFstockmtno' => $this->formatDisplayTransactionNumber($hdr->fstockmtno ?? null, (int) ($hdr->fapplyppn ?? 0) === 1),
            'fmt' => $fmt,
            'company_name' => config('app.company_name', 'PT. DEMO VERSION'),
            'company_city' => config('app.company_city', 'Tangerang'),
        ]);
    }

    public function create(Request $request)
    {
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
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'supplierAdvanceWarnings' => $supplierAdvanceWarnings,
        ]);
    }

    public function store(Request $request)
    {
        try {
            $rawCodes = collect($request->input('fitemcode', []));
            $hasOpeningBalanceItem = $rawCodes->contains(fn ($code) => $this->isOpeningBalanceProductCode($code));

            if ($hasOpeningBalanceItem) {
                $request->merge([
                    'ftypebuy' => '1',
                ]);
            }

            // 1) VALIDASI
            $request->validate([
                'fstockmtdate' => ['required', 'date'],
                'fsupplier' => ['required', 'string', 'max:30'],
                'ffrom' => ['required', 'string', 'max:30'],
                'frefno' => ['required', 'string', 'max:100'],
                'ftypebuy' => ['nullable', 'integer'],
                'fprdjadi' => ['required_if:ftypebuy,1'],
                'fqty' => ['required', 'array'],
                'fqty.*' => ['numeric', 'min:0.001'],
                'fprice' => ['required', 'array'],
                'fprice.*' => ['numeric', 'min:0'],
                'fdiscpersen' => ['nullable', 'array'],
                'fdiscpersen.*' => ['nullable', 'string', 'regex:/^\s*\d+(?:\.\d+)?(?:\s*\+\s*\d+(?:\.\d+)?)*\s*$/'],
                'frefnoacak' => ['nullable', 'array'],
                'frefnoacak.*' => ['nullable', 'regex:/^\d{3}(,\s*\d{3})*$/'],
            ], [
                'ffrom.required' => 'Gudang wajib diisi.',
                'frefno.required' => 'No faktur wajib diisi.',
                'fprdjadi.required_if' => 'Account wajib diisi ketika tipe pembelian adalah Non Stok.',
                'fdiscpersen.*.regex' => 'Format diskon item harus angka atau format seperti 10+2.',
            ]);

            $this->ensureNoDuplicateDetailCodes($request->input('fitemcode', []));

            // 2) HEADER FIELDS
            $fstockmtno = trim((string) $request->input('fstockmtno'));
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
            $frate = max(1, (float) $request->input('frate', 1));
            $userid = auth('sysuser')->user()->fsysuserid ?? 'admin';
            $now = now();
            $fincludeppn = $request->boolean('fincludeppn') ? 1 : 0;
            $fapplyppn = $request->boolean('fapplyppn') ? 1 : 0;

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

            if ((string) $ftypebuy === '2') {
                $invalidAdvanceCodes = collect($codes)
                    ->map(fn ($code) => trim((string) $code))
                    ->filter(fn ($code) => $code !== '' && strtoupper($code) !== 'UM')
                    ->unique()
                    ->values()
                    ->all();

                if (! empty($invalidAdvanceCodes)) {
                    return back()->withInput()->withErrors([
                        'detail' => 'Tipe uang muka hanya boleh pakai produk UM. Kode tidak valid: ' . strtoupper(implode(', ', $invalidAdvanceCodes)) . '.',
                    ]);
                }
            }

            if ($this->hasMixedOpeningBalanceAndSourceRows($codes, $qtys, $sources)) {
                return back()->withInput()->withErrors([
                    'detail' => 'Item awal tidak boleh digabung dengan item referensi PO / TER.',
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
                return back()->withErrors($errors)->withInput();
            }

            if ($validationMessage = $this->validateUniqueHeaderReference($frefno, $frefpo)) {
                return back()->withInput()->withErrors([
                    'detail' => $validationMessage,
                ]);
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

                $priceNet = ($price + $biaya) - ($price * ($discP / 100));
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
                    'fcode' => $sourceType === 'PO' ? 'P' : 'T',
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
                    $message .= ' Kode item yang tidak dikenali: '.implode(', ', array_values(array_unique($skippedDetailCodes))).'.';
                }

                return back()->withInput()->withErrors([
                    'detail' => $message,
                ]);
            }

            $ppnAmount = (float) $request->input('famountpopajak', 0);
            $grandTotal = $subtotal + $ppnAmount;

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
                $ftypebuy,
                $frefno,
                $frefpo,
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
                $fstockmtcode = 'BUY';

                // B. Penomoran
                if (empty($fstockmtno)) {
                    $prefix = "$fstockmtcode.$kodeCabang.$yy.$mm.";
                    $lockKey = crc32("STOCKMT|$fstockmtcode|$kodeCabang|" . $fstockmtdate->format('Y-m'));
                    DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

                    $last = DB::table('trstockmt')
                        ->where('fstockmtno', 'like', "$prefix%")
                        ->selectRaw("MAX(CAST(split_part(fstockmtno, '.', 5) AS int)) AS lastno")
                        ->value('lastno');

                    $fstockmtno = $prefix . str_pad((string) ((int) $last + 1), 4, '0', STR_PAD_LEFT);
                }

                // C. Insert Header
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
                    'ffrom' => $ffrom,
                    'fprdjadi' => $fprdjadi,
                    'fprdjadiid' => $faccid,
                    'fket' => $fket,
                    'fusercreate' => $userid,
                    'fdatetime' => $now,
                    'fbranchcode' => $kodeCabang,
                    'fincludeppn' => $fincludeppn,
                    'fapplyppn' => $fapplyppn,
                    'fppnpersen' => $request->input('ppn_rate', 0),
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
                    (float) $subtotal,
                    (float) $ppnAmount,
                    (float) $grandTotal,
                    (float) $frate,
                    (string) $userid
                );
            });

            return redirect()->route('fakturpembelian.create')
                ->with('success', 'Faktur pembelian '.$this->formatDisplayTransactionNumber($fstockmtno, $fapplyppn === 1).' berhasil disimpan.');
        } catch (\Exception $e) {
            Log::error('FakturPembelian@store ERROR: ' . $e->getMessage());

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
            ->findOrFail($fstockmtid);

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
            'accounts' => $accounts,
            'productMap' => $productMap,
            'currentAccount' => $currentAccount,
            'currentAccountId' => $currentAccountId,
            'currentAccountName' => $currentAccountName,
            'fakturpembelian' => $fakturpembelian,
            'displayFstockmtno' => $this->formatDisplayTransactionNumber($fakturpembelian->fstockmtno ?? null, (int) ($fakturpembelian->fapplyppn ?? 0) === 1),
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
            ->findOrFail($fstockmtid); // Temukan header berdasarkan $fstockmtid

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
            'displayFstockmtno' => $this->formatDisplayTransactionNumber($fakturpembelian->fstockmtno ?? null, (int) ($fakturpembelian->fapplyppn ?? 0) === 1),
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
            $rawCodes = collect($request->input('fitemcode', []));
            $hasOpeningBalanceItem = $rawCodes->contains(fn ($code) => $this->isOpeningBalanceProductCode($code));

            if ($hasOpeningBalanceItem) {
                $request->merge([
                    'ftypebuy' => '1',
                ]);
            }

            // VALIDASI
            $validatedData = $request->validate([
                'fstockmtno' => ['nullable', 'string', 'max:100'],
                'fstockmtdate' => ['required', 'date'],
                'fsupplier' => ['required', 'string', 'max:30'],
                'ffrom' => ['required', 'string', 'max:30'],
                'fket' => ['nullable', 'string', 'max:50'],
                'fbranchcode' => ['nullable', 'string', 'max:20'],
                'faccid' => ['nullable', 'integer'],
                'fsatuan' => ['nullable', 'array'],
                'fsatuan.*' => ['nullable', 'string', 'max:20'],
                'frefdtno' => ['nullable', 'array'],
                'frefdtno.*' => ['nullable', 'string', 'max:20'],
                'fqty' => ['required', 'array'],
                'fqty.*' => ['numeric', 'min:0'],
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
                'frefpo' => ['nullable', 'string'],
                'frefnoacak' => ['nullable', 'array'],
                'frefnoacak.*' => ['nullable', 'regex:/^\d{3}(,\s*\d{3})*$/'],
                'fprdjadi' => ['required_if:ftypebuy,1'],
            ], [
                'ffrom.required' => 'Gudang wajib diisi.',
                'fstockmtdate.required' => 'Tanggal transaksi wajib diisi.',
                'fsupplier.required' => 'Supplier wajib diisi.',
                'frefno.required' => 'No. faktur wajib diisi.',
                'fsatuan.*.max' => 'Satuan maksimal 5 karakter.',
                'fprdjadi.required_if' => 'Account wajib diisi.',
                'fdiscpersen.*.regex' => 'Format diskon harus angka atau 10+2.',
            ]);

            $this->ensureNoDuplicateDetailCodes($request->input('fitemcode', []));

            // 2. Muat header yang ada
            $header = PenerimaanPembelianHeader::findOrFail($fstockmtid);

            if ($message = $this->getPostedPeriodLockMessage($header->fstockmtdate)) {
                return redirect()->route('fakturpembelian.view', $header->fstockmtid)->with('error', $message);
            }

            if ($message = $this->getUsageLockMessage($header)) {
                return redirect()->route('fakturpembelian.index')->with('error', $message);
            }

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
            $ftypebuy = $request->input('ftypebuy');
            $fcurrency = $request->input('fcurrency', 'IDR');
            $frate = (float) $request->input('frate', 1);
            if ($frate <= 0) {
                $frate = 1;
            }
            $fincludeppn = $request->boolean('fincludeppn') ? 1 : 0;
            $fapplyppn = $request->boolean('fapplyppn') ? 1 : 0;
            $fppnpersen = (float) $request->input('ppn_rate', 0);
            $frefno = $request->input('frefno');
            $frefpo = $request->input('frefpo');

            $ppnAmount = (float) $request->input('famountpajak', 0);
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

            if ((string) $ftypebuy === '2') {
                $invalidAdvanceCodes = collect($codes)
                    ->map(fn ($code) => trim((string) $code))
                    ->filter(fn ($code) => $code !== '' && strtoupper($code) !== 'UM')
                    ->unique()
                    ->values()
                    ->all();

                if (! empty($invalidAdvanceCodes)) {
                    return back()->withInput()->withErrors([
                        'detail' => 'Tipe uang muka hanya boleh pakai produk UM. Kode tidak valid: ' . strtoupper(implode(', ', $invalidAdvanceCodes)) . '.',
                    ]);
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

            if ($validationMessage = $this->validateUniqueHeaderReference($frefno, $frefpo, $header->fstockmtno)) {
                return back()->withInput()->withErrors([
                    'detail' => $validationMessage,
                ]);
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
                $frefdtid = $isSaldoAwal ? null : (isset($refdtids[$i]) ? (int) $refdtids[$i] : null);
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

                $priceNet = ($price + $biaya) - ($price * ($discP / 100));
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
                    'fuserupdate' => (Auth::user()->fname ?? 'system'),
                    'fdatetime' => $now,
                    'fdesc' => $desc ?: null,
                    'fketdt' => $desc ?: null,
                    'fcode' => $sourceType === 'PO' ? 'P' : 'T',
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
                $sourceUsageByRef

            ) {

                // Logika Branch yang diperbaiki untuk PostgreSQL
                $kodeCabang = 'NA';
                if (! empty($fbranchcode)) {
                    $qCabang = DB::table('mscabang');

                    if (is_numeric($fbranchcode)) {
                        // Jika angka, cari ke ID
                        $qCabang->where('fcabangid', (int) $fbranchcode);
                    } else {
                        // Jika huruf (seperti 'BG'), langsung cari ke Kode
                        $qCabang->where('fcabangkode', $fbranchcode);
                    }

                    $cabang = $qCabang->first();
                    $kodeCabang = $cabang ? $cabang->fcabangkode : 'NA';
                }

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
                    'famountremain' => round($grandTotal, 2),
                    'famountremain_rp' => round($grandTotal * $frate, 2),
                    'frefno' => $request->input('frefno'),
                    'frefpo' => $request->input('frefpo'),
                    'ffrom' => $ffrom,
                    'fprdjadi' => $fprdjadi,
                    'fprdjadiid' => $faccid,
                    'fket' => $fket,
                    'fuserupdate' => (Auth::user()->fname ?? 'system'),
                    'fdatetime' => $now,
                    'fbranchcode' => $kodeCabang, // Menggunakan hasil pencarian aman tadi
                    'ftempohr' => $ftempohr,
                    'fincludeppn' => $fincludeppn,
                    'fapplyppn' => $fapplyppn,
                    'fppnpersen' => $fppnpersen,
                    'ftypebuy' => $ftypebuy,
                    'fjatuhtempo' => $request->input('fjatuhtempo') ? \Carbon\Carbon::parse($request->input('fjatuhtempo'))->startOfDay() : null,
                ]);

                // Hapus detail lama dan masukkan yang baru
                $this->adjustSourceQtyKecil($oldUsageBySourceRef, 1);
                $header->details()->delete();

                $nextNouRef = 1;
                foreach ($rowsDt as &$r) {
                    $r['fstockmtcode'] = 'BUY';
                    $r['fstockmtno'] = $fstockmtno;
                }

                DB::table('trstockdt')->insert($rowsDt);
                $this->adjustSourceQtyKecil($sourceUsageByRef, -1);

                $this->syncFakturPembelianJournalEntries(
                    (string) $fstockmtno,
                    $fstockmtdate,
                    (string) $kodeCabang,
                    (string) $fsupplier,
                    (float) $subtotal,
                    (float) $ppnAmount,
                    (float) $grandTotal,
                    (float) $frate,
                    (string) (Auth::user()->fname ?? 'system')
                );
            });

            return redirect()
                ->route('fakturpembelian.index')
                ->with('success', 'Faktur pembelian '.$this->formatDisplayTransactionNumber($fstockmtno, $fapplyppn === 1).' berhasil diupdate.');
        } catch (\Exception $e) {

            return back()
                ->withInput()
                ->withErrors(['error' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
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
                ->route('fakturpembelian.view', $fakturpembelian->fstockmtid)
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
            'displayFstockmtno' => $this->formatDisplayTransactionNumber($fakturpembelian->fstockmtno ?? null, (int) ($fakturpembelian->fapplyppn ?? 0) === 1),
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
                return redirect()->route('fakturpembelian.view', $fakturpembelian->fstockmtid)->with('error', $message);
            }

            if ($message = $this->getUsageLockMessage($fakturpembelian)) {
                return redirect()->route('fakturpembelian.index')->with('error', $message);
            }

            DB::transaction(function () use ($fakturpembelian) {
                $oldUsageBySourceRef = [];
                $oldDetails = DB::table('trstockdt')
                    ->where('fstockmtno', $fakturpembelian->fstockmtno)
                    ->get(['frefdtid', 'fqtykecil']);

                foreach ($oldDetails as $oldDetail) {
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

            return redirect()->route('fakturpembelian.index')->with('success', 'Faktur pembelian ' . $this->formatDisplayTransactionNumber($fakturpembelian->fstockmtno, (int) ($fakturpembelian->fapplyppn ?? 0) === 1) . ' berhasil dihapus.');
        } catch (\Exception $e) {
            // Jika terjadi kesalahan saat menghapus, kembali ke halaman delete dengan pesan error
            return redirect()->route('fakturpembelian.delete', $fstockmtid)->with('error', 'Faktur pembelian belum bisa dihapus. Coba lagi.');
        }
    }

    private function getUsageLockMessage(PenerimaanPembelianHeader $header): ?string
    {
        $usedBy = DB::table('trstockmt')
            ->where('fstockmtcode', 'REB')
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

        $parts = array_filter(explode('+', $normalized), fn ($part) => $part !== '');
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
            ->map(fn ($value) => trim((string) ($value ?? '')))
            ->filter(fn ($value) => $value !== '')
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

    private function syncFakturPembelianJournalEntries(
        string $fstockmtno,
        Carbon $fstockmtdate,
        string $kodeCabang,
        string $fsupplier,
        float $subtotal,
        float $ppnAmount,
        float $grandTotal,
        float $frate,
        string $userid
    ): void {
        $this->deleteFakturPembelianJournalEntries($fstockmtno);

        $fjurnaltype = 'JBL';
        $yy = $fstockmtdate->format('y');
        $mm = $fstockmtdate->format('m');
        $jurnalPrefix = sprintf('%s.%s.%s.%s.', $fjurnaltype, $kodeCabang, $yy, $mm);

        if (DB::getDriverName() === 'pgsql') {
            $lockKey = crc32('JURNAL|' . $fjurnaltype . '|' . $kodeCabang . '|' . $fstockmtdate->format('Y-m'));
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
            'fjurnaldate' => $fstockmtdate,
            'fjurnalnote' => "Faktur Pembelian $fstockmtno dari $fsupplier",
            'fbalance' => round($grandTotal, 2),
            'fbalance_rp' => round($grandTotal * $frate, 2),
            'fdatetime' => $now,
            'fuserid' => $userid,
        ], 'fjurnalmtid');

        $jurnalDt = [
            ['fjurnalmtid' => $jurnalId, 'fbranchcode' => $kodeCabang, 'fjurnaltype' => $fjurnaltype, 'fjurnalno' => $fjurnalno, 'flineno' => 1, 'faccount' => '11400', 'fdk' => 'D', 'fsubaccount' => $fsupplier, 'frefno' => $fstockmtno, 'frate' => $frate, 'famount' => round($subtotal, 2), 'famount_rp' => round($subtotal * $frate, 2), 'faccountnote' => 'Persediaan', 'fusercreate' => $userid, 'fdatetime' => $now],
            ['fjurnalmtid' => $jurnalId, 'fbranchcode' => $kodeCabang, 'fjurnaltype' => $fjurnaltype, 'fjurnalno' => $fjurnalno, 'flineno' => ($ppnAmount > 0 ? 3 : 2), 'faccount' => '21100', 'fdk' => 'K', 'fsubaccount' => $fsupplier, 'frefno' => $fstockmtno, 'frate' => $frate, 'famount' => round($grandTotal, 2), 'famount_rp' => round($grandTotal * $frate, 2), 'faccountnote' => 'Hutang Dagang', 'fusercreate' => $userid, 'fdatetime' => $now],
        ];

        if ($ppnAmount > 0) {
            $jurnalDt[] = ['fjurnalmtid' => $jurnalId, 'fbranchcode' => $kodeCabang, 'fjurnaltype' => $fjurnaltype, 'fjurnalno' => $fjurnalno, 'flineno' => 2, 'faccount' => '11500', 'fdk' => 'D', 'fsubaccount' => null, 'frefno' => $fstockmtno, 'frate' => $frate, 'famount' => round($ppnAmount, 2), 'famount_rp' => round($ppnAmount * $frate, 2), 'faccountnote' => 'PPN Masukan', 'fusercreate' => $userid, 'fdatetime' => $now];
        }

        DB::table('jurnaldt')->insert($jurnalDt);
    }

    private function deleteFakturPembelianJournalEntries(string $fstockmtno): void
    {
        $jurnalIds = DB::table('jurnaldt')
            ->where('frefno', $fstockmtno)
            ->where('fjurnaltype', 'JBL')
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
