<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ProductBrowseHelper;
use App\Models\PenerimaanPembelianDetail;
use App\Models\PenerimaanPembelianHeader;
use App\Models\Product;
use App\Models\Supplier;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PenerimaanBarangController extends Controller
{
    use ProductBrowseHelper;

    private const DAILY_CREATE_LIMIT = 15;

    private function todayCreateCount(): int
    {
        return PenerimaanPembelianHeader::where('fstockmtcode', 'TER')
            ->whereBetween('fdatetime', [now()->startOfDay(), now()->endOfDay()])
            ->count();
    }

    private function hasReachedDailyCreateLimit(): bool
    {
        return $this->todayCreateCount() >= self::DAILY_CREATE_LIMIT;
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

    public function index(Request $request)
    {
        $canCreate = in_array('createPenerimaanBarang', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updatePenerimaanBarang', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deletePenerimaanBarang', explode(',', session('user_restricted_permissions', '')));
        $showActionsColumn = $canEdit || $canDelete;

        $year = $request->query('year');
        $month = $request->query('month');
        $createLimitReached = $this->hasReachedDailyCreateLimit();

        $availableYearsQuery = PenerimaanPembelianHeader::selectRaw('DISTINCT EXTRACT(YEAR FROM fdatetime) as year')
            ->where('fstockmtcode', 'TER')
            ->whereNotNull('fdatetime');
        $this->applyBranchVisibilityScope($availableYearsQuery, 'trstockmt.fbranchcode');
        $availableYears = $availableYearsQuery
            ->orderByRaw('EXTRACT(YEAR FROM fdatetime) DESC')
            ->pluck('year');

        if ($request->ajax()) {
            $query = PenerimaanPembelianHeader::where('trstockmt.fstockmtcode', 'TER')
                ->leftJoin('mssupplier as s', 's.fsuppliercode', '=', 'trstockmt.fsupplier');
            $this->applyBranchVisibilityScope($query, 'trstockmt.fbranchcode');
            $totalRecords = (clone $query)->count();

            if ($search = $request->input('search.value')) {
                $query->where(function ($q) use ($search) {
                    $q->where('trstockmt.fstockmtno', 'like', "%{$search}%")
                        ->orWhereExists(function ($sub) use ($search) {
                            $sub->select(DB::raw(1))
                                ->from('trstockdt')
                                ->whereColumn('trstockdt.fstockmtno', 'trstockmt.fstockmtno')
                                ->where('trstockdt.frefdtno', 'ilike', "%{$search}%");
                        });
                });
            }
            if ($year) {
                $query->whereRaw('EXTRACT(YEAR FROM trstockmt.fdatetime) = ?', [$year]);
            }
            if ($month) {
                $query->whereRaw('EXTRACT(MONTH FROM trstockmt.fdatetime) = ?', [$month]);
            }

            $columnSearches = collect($request->input('columns', []))
                ->mapWithKeys(function ($column) {
                    $name = trim((string) ($column['name'] ?? ''));
                    $value = trim((string) data_get($column, 'search.value', ''));

                    return $name !== '' ? [$name => $value] : [];
                });

            $supplierSearch = trim((string) ($columnSearches->get('fsuppliername', '')));
            if ($supplierSearch !== '') {
                $query->where('s.fsuppliername', 'ilike', "%{$supplierSearch}%");
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
            $records = $query->skip($start)->take($length)->get([
                'trstockmt.fstockmtid',
                'trstockmt.fstockmtno',
                'trstockmt.fstockmtdate',
                'trstockmt.ffrom',
                'trstockmt.fsupplier',
                'trstockmt.fket',
                'trstockmt.famountmt',
                'trstockmt.fbranchcode',
                'trstockmt.fusercreate'
            ]);

            $supplierCodes = $records->pluck('fsupplier')->filter()->unique();
            $suppliers = DB::table('mssupplier')->whereIn('fsuppliercode', $supplierCodes)->pluck('fsuppliername', 'fsuppliercode');

            $stockMtNos = $records->pluck('fstockmtno');
            $trstockdts = DB::table('trstockdt')
                ->whereIn('fstockmtno', $stockMtNos)
                ->select('fstockmtno', DB::raw('MAX(frefdtno) as frefpo'))
                ->groupBy('fstockmtno')
                ->get()
                ->pluck('frefpo', 'fstockmtno');

            $data = $records->map(fn($row) => [
                'fstockmtid' => $row->fstockmtid,
                'fbranchcode' => $row->fbranchcode,
                'fstockmtno' => $row->fstockmtno,
                'fstockmtno_display' => $this->formatDisplayTransactionNumber($row->fstockmtno, str_contains((string) $row->fstockmtno, '/')),
                'fstockmtdate' => $row->fstockmtdate
                    ? ($row->fstockmtdate instanceof \Carbon\Carbon ? $row->fstockmtdate : \Carbon\Carbon::parse($row->fstockmtdate))->format('d-m-Y')
                    : '',
                'fwhcode' => $row->ffrom ?? '-',
                'fsuppliername' => $suppliers[$row->fsupplier] ?? '-',
                'frefpo' => $trstockdts[$row->fstockmtno] ?? '-',
                'famountmt' => 'Rp ' . number_format((float) $row->famountmt, 0, ',', '.'),
                'fusercreate' => $row->fusercreate ?? '-',
            ]);

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        }

        return view('penerimaanbarang.index', compact(
            'canCreate',
            'canEdit',
            'canDelete',
            'showActionsColumn',
            'availableYears',
            'year',
            'month',
            'createLimitReached'
        ));
    }

    public function pickable(Request $request)
    {
        $supplierCode = trim((string) $request->input('supplier', ''));

        $receiptSub = DB::table('trstockdt')
            ->selectRaw('CAST(frefdtid AS BIGINT) AS fpodid, SUM(COALESCE(fqtykecil, 0)) AS fqtykecilterima')
            ->where('fstockmtcode', 'TER')
            ->whereNotNull('frefdtid')
            ->groupBy(DB::raw('CAST(frefdtid AS BIGINT)'));

        $query = DB::table('tr_poh')
            ->leftJoin('mssupplier', 'tr_poh.fsupplier', '=', 'mssupplier.fsuppliercode')
            ->select('tr_poh.*', 'mssupplier.fsuppliername', 'mssupplier.fsuppliercode')
            ->whereIn('tr_poh.fclose', ['0', ''])
            ->where('tr_poh.fapproval', 1)
            ->whereExists(function ($sub) use ($receiptSub) {
                $sub->select(DB::raw(1))
                    ->from('tr_pod as d')
                    ->leftJoinSub($receiptSub, 'ter', function ($join) {
                        $join->on('ter.fpodid', '=', 'd.fpodid');
                    })
                    ->whereColumn('d.fpono', 'tr_poh.fpono')
                    ->whereRaw('GREATEST(COALESCE(d.fqtykecil, 0) - COALESCE(ter.fqtykecilterima, 0), 0) > 0');
            });

        $recordsTotal = (clone $query)->count();

        if ($supplierCode !== '') {
            $query->where('tr_poh.fsupplier', $supplierCode);
        }

        if ($request->filled('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tr_poh.fpono', 'ilike', "%{$search}%")
                    ->orWhere('mssupplier.fsuppliername', 'ilike', "%{$search}%")
                    ->orWhere('mssupplier.fsuppliercode', 'ilike', "%{$search}%");
            });
        }

        $recordsFiltered = $query->count();

        $orderColumn = $request->input('order_column', 'fpodate');
        $orderDir = $request->input('order_dir', 'desc');
        $allowedCols = ['fpono', 'fsupplier', 'fpodate'];

        if (in_array($orderColumn, $allowedCols)) {
            if (in_array($orderColumn, ['fpono', 'fpodate'])) {
                $query->orderBy('tr_poh.' . $orderColumn, $orderDir);
            } else {
                $query->orderBy('mssupplier.fsuppliername', $orderDir);
            }
        } else {
            $query->orderBy('tr_poh.fpodate', 'desc');
        }

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $data = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => (int) $recordsTotal,
            'recordsFiltered' => (int) $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function items($id)
    {
        $header = DB::table('tr_poh')->where('fpohid', $id)->first();

        if (! $header) {
            return response()->json(['message' => 'PO tidak ada.'], 404);
        }

        $receiptSub = DB::table('trstockdt')
            ->selectRaw('frefdtno, fprdcode, frefnoacak, SUM(COALESCE(fqtykecil, 0)) AS fqtykecilterima')
            ->where('fstockmtcode', 'TER')
            ->groupBy('frefdtno', 'fprdcode', 'frefnoacak');

        $items = DB::table('tr_pod as d')
            ->where('d.fpono', $header->fpono)
            ->whereRaw('COALESCE(d.fqtyremain, 0) > 0')
            ->leftJoin('msprd as m', 'm.fprdid', '=', 'd.fprdid')
            ->leftJoinSub($receiptSub, 'st', function ($join) {
                $join->on('st.frefdtno', '=', 'd.fpono')
                    ->on('st.fprdcode', '=', 'd.fprdcode')
                    ->on('st.frefnoacak', '=', 'd.fnoacak');
            })
            ->select([
                'd.fpodid as frefdtid',
                'm.fprdcode as fitemcode',
                'm.fprdname as fitemname',
                'd.fqty',
                'd.fqtyremain',
                'd.fsatuan as fsatuan',
                'd.fpono',
                'd.fprice as fprice',
                'd.fprice_rp as fprice_rp',
                'd.famount as ftotal',
                'd.fdesc as fdesc',
                'd.frefdtno',
                DB::raw("COALESCE(d.fnoacak::text, '') as frefnoacak"),
                'm.fsatuankecil',
                'm.fsatuanbesar',
                'm.fsatuanbesar2',
                'm.fqtykecil',
                'm.fqtykecil2',
                DB::raw('COALESCE(st.fqtykecilterima, 0) AS fqtykecilterima'),
                DB::raw('GREATEST(COALESCE(d.fqtyremain, d.fqtykecil, 0) - COALESCE(st.fqtykecilterima, 0), 0) AS fqtykecil_sisa'),
                DB::raw("COALESCE(
                    CASE
                        WHEN d.fsatuan = m.fsatuanbesar
                            THEN (COALESCE(d.fqtyremain, d.fqtykecil, 0) - COALESCE(st.fqtykecilterima, 0)) / NULLIF(m.fqtykecil, 0)
                        WHEN d.fsatuan = m.fsatuanbesar2
                            THEN (COALESCE(d.fqtyremain, d.fqtykecil, 0) - COALESCE(st.fqtykecilterima, 0)) / NULLIF(m.fqtykecil2, 0)
                        ELSE COALESCE(d.fqtyremain, d.fqtykecil, 0) - COALESCE(st.fqtykecilterima, 0)
                    END, 0) AS fqtysisapo"),
                DB::raw("COALESCE(
                    CASE
                        WHEN d.fsatuan = m.fsatuanbesar
                            THEN COALESCE(st.fqtykecilterima, 0) / NULLIF(m.fqtykecil, 0)
                        WHEN d.fsatuan = m.fsatuanbesar2
                            THEN COALESCE(st.fqtykecilterima, 0) / NULLIF(m.fqtykecil2, 0)
                        ELSE COALESCE(st.fqtykecilterima, 0)
                    END, 0) AS fqtyditer"),
                DB::raw('0::numeric as fterima'),
            ])
            ->orderBy('d.fpodid')
            ->get()
            ->map(function ($item) use ($header) {
                $item->frefdtno = (string) $header->fpono;
                $remainKecil = (float) ($item->fqtykecil_sisa ?? $item->fqtyremain ?? 0);
                $item->fqtyremain = $remainKecil;
                $item->fqtykecil_ref = $remainKecil;
                $item->maxqty = $this->qtyKecilToUnit($item, (string) ($item->fsatuan ?? ''), $remainKecil);
                $item->maxqty_satuan = (string) ($item->fsatuan ?? '');
                $item->units = array_values(array_filter([
                    $item->fsatuankecil ?? '',
                    $item->fsatuanbesar ?? '',
                    $item->fsatuanbesar2 ?? '',
                ]));

                return $item;
            })
            ->filter(fn($item) => (float) ($item->fqtyremain ?? 0) > 0 && (float) ($item->maxqty ?? 0) > 0)
            ->values();

        return response()->json([
            'header' => [
                'fpohid' => $header->fpohid,
                'fpono' => $header->fpono,
                'fsupplier' => trim($header->fsupplier ?? ''),
                'fpodate' => $header->fpodate ? date('Y-m-d H:i:s', strtotime($header->fpodate)) : null,
            ],
            'items' => $items,
        ]);
    }

    private function qtyPoToKecil(?object $product, string $sat, float $qty): float
    {
        if (! $product) {
            return $qty;
        }
        $sat = trim($sat);
        $besar = trim((string) ($product->fsatuanbesar ?? ''));
        $besar2 = trim((string) ($product->fsatuanbesar2 ?? ''));
        $rasio = (float) ($product->fqtykecil ?? 0);
        $rasio2 = (float) ($product->fqtykecil2 ?? 0);
        if ($sat !== '' && $besar !== '' && $sat === $besar && $rasio > 0) {
            return $qty * $rasio;
        }
        if ($sat !== '' && $besar2 !== '' && $sat === $besar2 && $rasio2 > 0) {
            return $qty * $rasio2;
        }

        return $qty;
    }

    private function qtyKecilToUnit(?object $product, string $sat, float $qtyKecil): float
    {
        if (! $product) {
            return $qtyKecil;
        }

        $sat = trim($sat);
        $besar = trim((string) ($product->fsatuanbesar ?? ''));
        $besar2 = trim((string) ($product->fsatuanbesar2 ?? ''));
        $rasio = (float) ($product->fqtykecil ?? 0);
        $rasio2 = (float) ($product->fqtykecil2 ?? 0);

        if ($sat !== '' && $besar !== '' && strcasecmp($sat, $besar) === 0 && $rasio > 0) {
            return $qtyKecil / $rasio;
        }
        if ($sat !== '' && $besar2 !== '' && strcasecmp($sat, $besar2) === 0 && $rasio2 > 0) {
            return $qtyKecil / $rasio2;
        }

        return $qtyKecil;
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

    /**
     * @param  array<int, array<string, mixed>>  $rows  baris sementara dengan frefdtid (fpodid) & fqtykecil
     * @return array<int, float>
     */
    private function aggregatePodReceiptByPod(array $rows): array
    {
        $agg = [];
        foreach ($rows as $r) {
            $fid = (int) ($r['frefdtid'] ?? 0);
            if ($fid <= 0) {
                continue;
            }
            $agg[$fid] = ($agg[$fid] ?? 0) + (float) ($r['fqtykecil'] ?? 0);
        }

        return $agg;
    }

    private function ensureNoDuplicateDetailCodes(array $codes, array $noAcaks = []): void
    {
        $seen = [];
        $duplicates = [];

        foreach ($codes as $index => $rawCode) {
            $code = strtoupper(trim((string) $rawCode));
            $noAcak = trim((string) ($noAcaks[$index] ?? ''));
            if ($code === '') {
                continue;
            }

            $key = $code . '|' . $noAcak;
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
            $messages["fitemcode.$index"] = "Kode produk {$code} dengan nomor acak yang sama tidak boleh dobel dalam satu Penerimaan Barang.";
        }

        throw ValidationException::withMessages($messages);
    }

    /**
     * Hitung sisa PO dinamis dalam satuan kecil berdasarkan detail PO dikurangi transaksi turunan.
     *
     * @param  array<int, int|string>  $podIds
     * @return array<int, float>
     */
    private function getPodRemainByIds(array $podIds): array
    {
        $ids = collect($podIds)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            return [];
        }

        $receiptSub = DB::table('trstockdt')
            ->selectRaw('CAST(frefdtid AS INTEGER) AS fpodid, SUM(COALESCE(fqtykecil, 0)) AS fqtykecilterima')
            ->where('fstockmtcode', 'TER')
            ->whereNotNull('frefdtid')
            ->groupByRaw('CAST(frefdtid AS INTEGER)');

        return DB::table('tr_pod as d')
            ->leftJoinSub($receiptSub, 'st', function ($join) {
                $join->on('st.fpodid', '=', 'd.fpodid');
            })
            ->whereIn('d.fpodid', $ids)
            ->selectRaw('d.fpodid, GREATEST(COALESCE(d.fqtykecil, 0) - COALESCE(st.fqtykecilterima, 0), 0) AS remain_kecil')
            ->pluck('remain_kecil', 'd.fpodid')
            ->map(fn($value) => (float) $value)
            ->all();
    }

    /**
     * Ambil metric referensi PO untuk tampilan Sisa PO / Qty Diterima
     * menggunakan rumus yang sama dengan query browse PO.
     *
     * @return array<int, array<string, float>>
     */
    private function getPoReferenceMetricsByPodIds(array $podIds): array
    {
        $ids = collect($podIds)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            return [];
        }

        $receiptSub = DB::table('trstockdt')
            ->selectRaw('frefdtno, fprdcode, frefnoacak, SUM(COALESCE(fqtykecil, 0)) AS fqtykecilterima')
            ->where('fstockmtcode', 'TER')
            ->groupBy('frefdtno', 'fprdcode', 'frefnoacak');

        return DB::table('tr_pod as d')
            ->leftJoin('tr_poh as h', 'h.fpono', '=', 'd.fpono')
            ->leftJoinSub($receiptSub, 'st', function ($join) {
                $join->on('st.frefdtno', '=', 'h.fpono')
                    ->on('st.fprdcode', '=', 'd.fprdcode')
                    ->on('st.frefnoacak', '=', 'd.fnoacak');
            })
            ->leftJoin('msprd as p', 'd.fprdcode', '=', 'p.fprdcode')
            ->whereIn('d.fpodid', $ids)
            ->select([
                'd.fpodid',
                DB::raw("COALESCE(
                    CASE
                        WHEN d.fsatuan = p.fsatuanbesar
                            THEN (COALESCE(d.fqtykecil, 0) - COALESCE(st.fqtykecilterima, 0)) / NULLIF(p.fqtykecil, 0)
                        WHEN d.fsatuan = p.fsatuanbesar2
                            THEN (COALESCE(d.fqtykecil, 0) - COALESCE(st.fqtykecilterima, 0)) / NULLIF(p.fqtykecil2, 0)
                        ELSE COALESCE(d.fqtykecil, 0) - COALESCE(st.fqtykecilterima, 0)
                    END, 0) AS fqtysisapo"),
                DB::raw("COALESCE(
                    CASE
                        WHEN d.fsatuan = p.fsatuanbesar
                            THEN COALESCE(st.fqtykecilterima, 0) / NULLIF(p.fqtykecil, 0)
                        WHEN d.fsatuan = p.fsatuanbesar2
                            THEN COALESCE(st.fqtykecilterima, 0) / NULLIF(p.fqtykecil2, 0)
                        ELSE COALESCE(st.fqtykecilterima, 0)
                    END, 0) AS fqtyditer"),
            ])
            ->get()
            ->mapWithKeys(fn($row) => [
                (int) $row->fpodid => [
                    'fqtysisapo' => (float) ($row->fqtysisapo ?? 0),
                    'fqtyditer' => (float) ($row->fqtyditer ?? 0),
                ],
            ])
            ->all();
    }

    private function adjustPoReferenceQtyKecil(array $usageByPod, int $direction): void
    {
        $podIds = collect(array_keys($usageByPod))
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($podIds)) {
            return;
        }

        $poNos = DB::table('tr_pod')
            ->whereIn('fpodid', $podIds)
            ->pluck('fpono')
            ->filter(fn($pono) => trim((string) $pono) !== '')
            ->unique()
            ->values()
            ->all();

        if (empty($poNos)) {
            return;
        }

        $receiptSub = DB::table('trstockdt')
            ->selectRaw('CAST(frefdtid AS BIGINT) AS fpodid, SUM(COALESCE(fqtykecil, 0)) AS fqtykecilterima')
            ->where('fstockmtcode', 'TER')
            ->whereNotNull('frefdtid')
            ->groupBy(DB::raw('CAST(frefdtid AS BIGINT)'));

        $details = DB::table('tr_pod as d')
            ->leftJoinSub($receiptSub, 'ter', function ($join) {
                $join->on('ter.fpodid', '=', 'd.fpodid');
            })
            ->whereIn('d.fpono', $poNos)
            ->orderBy('d.fpodid')
            ->get([
                'd.fpodid',
                'd.fpono',
                DB::raw('COALESCE(d.fqtykecil, 0) AS fqtykecil'),
                DB::raw('COALESCE(ter.fqtykecilterima, 0) AS fqtykecilterima'),
            ]);

        if ($details->isEmpty()) {
            return;
        }

        $statusByPo = [];
        $tolerance = 0.00001;

        foreach ($details as $detail) {
            $podId = (int) ($detail->fpodid ?? 0);
            $poNo = (string) ($detail->fpono ?? '');
            $qtyKecil = max(0, (float) ($detail->fqtykecil ?? 0));
            $qtyTerima = max(0, (float) ($detail->fqtykecilterima ?? 0));

            if ($direction > 0) {
                $qtyTerima = max(0, $qtyTerima - max(0, (float) ($usageByPod[$podId] ?? 0)));
            }

            $qtyRemain = max(0, $qtyKecil - $qtyTerima);

            DB::table('tr_pod')
                ->where('fpodid', $podId)
                ->update([
                    'fqtyremain' => $qtyRemain,
                ]);

            if (! isset($statusByPo[$poNo])) {
                $statusByPo[$poNo] = [
                    'has_received' => false,
                    'all_complete' => true,
                ];
            }

            if ($qtyTerima > $tolerance) {
                $statusByPo[$poNo]['has_received'] = true;
            }

            if ($qtyRemain > $tolerance) {
                $statusByPo[$poNo]['all_complete'] = false;
            }
        }

        foreach ($statusByPo as $poNo => $meta) {
            $status = '0';

            if ($meta['all_complete']) {
                $status = '1';
            } elseif ($meta['has_received']) {
                $status = '2';
            }

            DB::table('tr_poh')
                ->where('fpono', $poNo)
                ->update([
                    'fprdin' => $status,
                ]);
        }
    }

    private function validateTrPodRemain(array $aggregateByPod, array $extraAvailableByPod = []): void
    {
        if (empty($aggregateByPod)) {
            return;
        }

        $podMetaMap = DB::table('tr_pod as d')
            ->leftJoin('msprd as p', 'p.fprdcode', '=', 'd.fprdcode')
            ->whereIn('d.fpodid', array_keys($aggregateByPod))
            ->get([
                'd.fpodid',
                'd.fpono',
                'd.fprdcode',
                'd.fsatuan',
                'p.fsatuankecil',
                'p.fsatuanbesar',
                'p.fsatuanbesar2',
                'p.fqtykecil',
                'p.fqtykecil2',
            ])
            ->keyBy('fpodid');

        $remainMap = $this->getPodRemainByIds(array_keys($aggregateByPod));
        $tolerance = 0.00001;

        foreach ($aggregateByPod as $podId => $qtyKecilNeed) {
            $needKecil = (float) $qtyKecilNeed;
            if ($needKecil <= 0) {
                continue;
            }

            $remainKecil = (float) ($remainMap[(int) $podId] ?? 0);
            $oldKecil = max(0, (float) ($extraAvailableByPod[(int) $podId] ?? 0));
            $deltaNeedKecil = max(0, $needKecil - $oldKecil);
            $availableKecil = $remainKecil + $oldKecil;

            if ($deltaNeedKecil > $remainKecil + $tolerance) {
                $meta = $podMetaMap->get((int) $podId);
                $poNo = trim((string) ($meta->fpono ?? ''));
                $prdCode = trim((string) ($meta->fprdcode ?? ''));
                $satuan = trim((string) ($meta->fsatuan ?? ''));
                $satBesar = trim((string) ($meta->fsatuanbesar ?? ''));
                $satBesar2 = trim((string) ($meta->fsatuanbesar2 ?? ''));
                $rasio = (float) ($meta->fqtykecil ?? 0);
                $rasio2 = (float) ($meta->fqtykecil2 ?? 0);
                $parts = array_filter([
                    $poNo !== '' ? "PO {$poNo}" : null,
                    $prdCode !== '' ? "Produk {$prdCode}" : null,
                    "Detail ID {$podId}",
                ]);
                $label = implode(' / ', $parts);
                $availableInPoUnit = $availableKecil;
                if ($satuan !== '' && strcasecmp($satuan, $satBesar) === 0 && $rasio > 0) {
                    $availableInPoUnit = $availableKecil / $rasio;
                } elseif ($satuan !== '' && strcasecmp($satuan, $satBesar2) === 0 && $rasio2 > 0) {
                    $availableInPoUnit = $availableKecil / $rasio2;
                }
                $availableInPoUnitText = rtrim(rtrim(number_format($availableInPoUnit, 4, '.', ''), '0'), '.');
                $availableKecilText = rtrim(rtrim(number_format($availableKecil, 4, '.', ''), '0'), '.');

                throw new \RuntimeException(
                    "Qty PO melebihi batas pada {$label}. Maksimal {$availableKecilText} dalam satuan kecil"
                        . ($satuan !== '' ? " atau {$availableInPoUnitText} {$satuan}" : '')
                        . ", berdasarkan total penerimaan barang."
                );
            }
        }
    }

    private function poHasPpnForRows(array $rows): bool
    {
        $podIds = collect($rows)
            ->pluck('frefdtid')
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $flags = DB::table('tr_pod as d')
            ->join('tr_poh as h', 'h.fpono', '=', 'd.fpono')
            ->whereIn('d.fpodid', $podIds)
            ->pluck('h.fapplyppn')
            ->map(fn($flag) => (int) $flag)
            ->unique()
            ->values();

        if ($flags->count() > 1) {
            throw new \RuntimeException('Penerimaan Barang tidak bisa gabung PO PPN dan non-PPN dalam satu transaksi.');
        }

        return (int) ($flags->first() ?? 1) === 1;
    }

    private function generateStockMtCode(?Carbon $onDate = null, $branch = null, string $prefix = 'TER', bool $hasPpn = true): string
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

        $sep = $hasPpn ? '.' : '/';
        $lockKey = crc32("STOCKMT|{$prefix}|{$kodeCabang}|" . $date->format('y-m'));
        DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

        $noPrefix = sprintf('%s%s%s%s%s%s', $prefix, $sep, $kodeCabang, $sep, $date->format('y') . $date->format('m'), $sep);

        $last = DB::table('trstockmt')
            ->where('fstockmtno', 'like', $noPrefix . '%')
            ->selectRaw("MAX(CAST(SUBSTRING(fstockmtno FROM '([0-9]+)$') AS int)) AS lastno")
            ->value('lastno');

        $next = (int) $last + 1;

        return $noPrefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function print(string $fstockmtno)
    {
        $supplierSub = Supplier::select('fsuppliercode', 'fsuppliername');

        $base = PenerimaanPembelianHeader::query()
            ->leftJoinSub($supplierSub, 's', fn($j) => $j->on('s.fsuppliercode', '=', 'trstockmt.fsupplier'))
            ->leftJoin('mscabang as c', 'c.fcabangkode', '=', 'trstockmt.fbranchcode')
            ->leftJoin('mswh as w', 'w.fwhcode', '=', 'trstockmt.ffrom');

        $cols = [
            'trstockmt.*',
            's.fsuppliername as supplier_name',
            'c.fcabangname as cabang_name',
            'w.fwhname as fwhnamen',
        ];

        if (is_numeric($fstockmtno)) {
            $hdr = (clone $base)->where('trstockmt.fstockmtid', (int) $fstockmtno)->first($cols);
        } else {
            $hdr = (clone $base)->where('trstockmt.fstockmtno', $fstockmtno)->first($cols);
            if (! $hdr) {
                $alt = str_contains($fstockmtno, '.') ? str_replace('.', '/', $fstockmtno) : str_replace('/', '.', $fstockmtno);
                $hdr = (clone $base)->where('trstockmt.fstockmtno', $alt)->orderByDesc('trstockmt.fstockmtid')->first($cols);
            }
        }

        if (! $hdr) {
            return redirect()->back()->with('error', 'Penerimaan Barang tidak ada.');
        }

        if ((int) ($hdr->fapproval ?? 0) !== 1) {
            return redirect()->back()->with('error', 'Penerimaan Barang belum di-approve dan tidak boleh dicetak.');
        }

        if (! $this->canPrintAgain() && (int) ($hdr->fprint ?? 0) === 1) {
            return redirect()->back()->with('error', 'Penerimaan Barang Sudah Pernah diPrint.');
        }

        DB::table('trstockmt')->where('fstockmtid', $hdr->fstockmtid)->update(['fprint' => 1]);
        log_print_transaction($hdr->fstockmtno);

        $dt = PenerimaanPembelianDetail::query()
            ->leftJoin('msprd as p', function ($j) {
                $j->on(DB::raw('TRIM(p.fprdcode)'), '=', DB::raw('TRIM(trstockdt.fprdcode)'));
            })
            ->where('trstockdt.fstockmtno', $hdr->fstockmtno)
            ->orderBy('trstockdt.fstockdtid')
            ->get([
                'trstockdt.*',
                'p.fprdname as product_name',
                'p.fprdcode as product_code',
                'p.fminstock as stock',
                'trstockdt.fqtykecil',
            ]);

        $fmt = fn($d) => $d ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d F Y') : '-';

        return view('penerimaanbarang.print', [
            'hdr' => $hdr,
            'dt' => $dt,
            'displayFstockmtno' => $this->formatDisplayTransactionNumber($hdr->fstockmtno ?? null, str_contains((string) ($hdr->fstockmtno ?? ''), '/')),
            'fmt' => $fmt,
            'company_name' => company_name(),
            'company_city' => config('app.company_city', 'Tangerang'),
        ]);
    }

    public function create(Request $request)
    {
        if ($this->hasReachedDailyCreateLimit()) {
            return redirect()
                ->route('penerimaanbarang.index')
                ->with('create_limit_exceeded', true);
        }

        $suppliers = Supplier::orderBy('fsuppliername', 'asc')->get(['fsuppliercode', 'fsuppliername']);

        $warehouses = DB::table('mswh')
            ->select('fwhid', 'fwhcode', 'fwhname', 'fbranchcode', 'fnonactive')
            ->where('fnonactive', '0')
            ->orderBy('fwhcode')
            ->get();

        ['fcabang' => $fcabang, 'fbranchcode' => $fbranchcode] = $this->resolveBranchContext();

        $products = $this->browseProducts();
        $productMap = $this->browseProductMap($products);

        $lastWarehouse = DB::table('trstockmt')
            ->where('fstockmtcode', 'TER')
            ->when($fbranchcode, fn($q) => $q->where('fbranchcode', $fbranchcode))
            ->whereNotNull('ffrom')
            ->where('ffrom', '!=', '')
            ->latest('fstockmtid')
            ->value('ffrom');

        return view('penerimaanbarang.create', [
            'warehouses' => $warehouses,
            'lastWarehouse' => $lastWarehouse,
            'suppliers' => $suppliers,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'products' => $products,
            'productMap' => $productMap,
            'filterSupplierId' => $request->query('filter_supplier_id'),
        ]);
    }

    public function store(Request $request)
    {
        if ($this->hasReachedDailyCreateLimit()) {
            return redirect()
                ->route('penerimaanbarang.index')
                ->with('create_limit_exceeded', true);
        }

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
            'fket' => ['nullable', 'string', 'max:500'],
            'fbranchcode' => ['nullable', 'string', 'max:20'],
            'fitemcode' => ['required', 'array', 'min:1'],
            'fitemcode.*' => ['required', 'string', 'max:50'],
            'frefdtid' => ['nullable', 'array'],
            'frefdtid.*' => ['nullable'],
            'fqty' => ['required', 'array'],
            'fqty.*' => ['numeric', 'min:0.001'],
            'fprice' => ['required', 'array'],
            'fprice.*' => ['numeric', 'min:0'],
            'fnoacak' => ['nullable', 'array'],
            'fnoacak.*' => ['nullable', 'regex:/^[1-9]{3}$/'],
            'frefnoacak' => ['nullable', 'array'],
            'frefnoacak.*' => ['nullable', 'regex:/^\d{3}$/'],
        ], [
            'ffrom.required' => 'Gudang wajib di isi.',
        ]);

        $this->ensureNoDuplicateDetailCodes($request->input('fitemcode', []), $request->input('fnoacak', []));

        // 2) HEADER FIELDS
        $fstockmtno = trim((string) $request->input('fstockmtno', ''));
        $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
        $this->ensureCreateDateWithinEditPeriod($fstockmtdate);
        $fsupplier = trim((string) $request->input('fsupplier'));
        $ffrom = trim((string) $request->input('ffrom'));
        $fket = trim((string) $request->input('fket', ''));
        $fbranchcode = $request->input('fbranchcode');
        $fcurrency = $request->input('fcurrency', 'IDR');
        $frate = max(1, (float) $request->input('frate', 1));
        $ppnAmount = (float) $request->input('famountpopajak', 0);
        $userid = auth('sysuser')->user()->fsysuserid ?? 'admin';
        $now = now();

        // 3) DETAIL ARRAYS
        $codes = $request->input('fitemcode', []);
        $satuans = $request->input('fsatuan', []);
        $fponos = $request->input('fpono', []);
        $refdtids = $request->input('frefdtid', []);
        $fnoacaks = $request->input('fnoacak', []);
        $frefnoacaks = $request->input('frefnoacak', []);
        $qtys = $request->input('fqty', []);
        $prices = $request->input('fprice', []);
        $descs = $request->input('fdesc', []);

        // 4) BUILD ROWS
        $uniqueCodes = array_values(array_unique(array_filter(array_map(fn($c) => trim((string) $c), $codes))));
        $prodMeta = DB::table('msprd')->whereIn('fprdcode', $uniqueCodes)->get()->keyBy('fprdcode');

        $rowsDt = [];
        $subtotal = 0.0;
        $errors = [];
        $usedNoAcaks = [];

        for ($i = 0, $cnt = count($codes); $i < $cnt; $i++) {
            $code = trim((string) ($codes[$i] ?? ''));
            $qty = (float) ($qtys[$i] ?? 0);

            if ($code === '' || $qty <= 0) {
                continue;
            }

            $meta = $prodMeta[$code] ?? null;
            if (! $meta) {
                continue;
            }

            $sat = trim((string) ($satuans[$i] ?? ''));
            if ($sat === '') {
                $sat = mb_substr($meta->fsatuankecil ?? $meta->fsatuanbesar ?? '', 0, 5);
            }

            $rawRefId = $refdtids[$i] ?? null;
            $frefdtid = ($rawRefId !== null && $rawRefId !== '') ? (int) $rawRefId : null;
            if ($frefdtid === null || $frefdtid <= 0) {
                // Skip rows without a valid PO reference
                continue;
            }
            if ($frefdtid > 0) {
                $poUnit = DB::table('tr_pod')
                    ->where('fpodid', $frefdtid)
                    ->value('fsatuan');
                if ($poUnit === null) {
                    return back()->withInput()->withErrors(['detail' => 'Detail PO tidak valid untuk produk ' . $code . '.']);
                }
                $sat = trim($poUnit);
            }

            $qtyKecil = $this->qtyPoToKecil($meta, $sat, $qty);

            $price = (float) ($prices[$i] ?? 0);
            $amount = $qty * $price;
            $subtotal += $amount;

            $rowsDt[] = [
                'fprdcode' => $code,
                'frefdtno' => trim((string) ($fponos[$i] ?? '')),
                'frefso' => trim((string) ($fponos[$i] ?? '')),
                'frefdtid' => $frefdtid,
                'fnoacak' => $this->normalizeRandomNumber($fnoacaks[$i] ?? null, $usedNoAcaks),
                'frefnoacak' => $frefdtid ? $this->normalizeReferenceRandomNumber($frefnoacaks[$i] ?? null) : null,
                'fqty' => $qty,
                'fqtykecil' => $qtyKecil,
                'fqtyremain' => $qtyKecil,
                'fprice' => $price,
                'fprice_rp' => $price * $frate,
                'ftotprice' => $amount,
                'ftotprice_rp' => $amount * $frate,
                'fusercreate' => $userid,
                'fdatetime' => $now,
                'fcode' => 'R',
                'fdesc' => trim((string) ($descs[$i] ?? '')),
                'fsatuan' => mb_substr($sat, 0, 5),
                'fclosedt' => 0,
            ];
        }

        if (empty($rowsDt)) {
            $msg = 'Minimal satu item valid diperlukan.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return back()->withInput()->withErrors(['detail' => $msg]);
        }

        if ($validationMessage = $this->validateUniqueReferenceUsage($rowsDt)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $validationMessage], 422);
            }
            return back()->withInput()->withErrors(['detail' => $validationMessage]);
        }

        $podAgg = $this->aggregatePodReceiptByPod($rowsDt);

        $grandTotal = $subtotal + $ppnAmount;
        $isApproved = $request->boolean('approve_now') || $request->input('approve_now') === '1';

        // 5) TRANSACTION
        try {
            DB::transaction(function () use (
                $fstockmtdate,
                $fsupplier,
                $ffrom,
                $fket,
                $fbranchcode,
                $fcurrency,
                $frate,
                $userid,
                $now,
                $isApproved,
                &$fstockmtno,
                &$rowsDt,
                $subtotal,
                $ppnAmount,
                $grandTotal,
                $podAgg
            ) {
                $this->validateTrPodRemain($podAgg);

                // A. Resolve Cabang
                $rawBranch = trim((string) $fbranchcode);
                $kodeCabang = DB::table('mscabang')
                    ->where('fcabangid', is_numeric($rawBranch) ? (int) $rawBranch : -1)
                    ->orWhere('fcabangkode', $rawBranch)
                    ->value('fcabangkode') ?? 'NA';

                $fstockmtcode = 'TER';

                // B. Penomoran Otomatis
                if (empty($fstockmtno)) {
                    $fstockmtno = $this->generateStockMtCode($fstockmtdate, $kodeCabang, $fstockmtcode, $this->poHasPpnForRows($rowsDt));
                } else {
                    $fstockmtno = $this->formatDisplayTransactionNumber($fstockmtno, ! $this->poHasPpnForRows($rowsDt));
                }

                // C. Insert Header
                $masterId = DB::table('trstockmt')->insertGetId([
                    'fstockmtno' => $fstockmtno,
                    'fstockmtcode' => $fstockmtcode,
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
                    'ffrom' => $ffrom,
                    'fket' => $fket,
                    'fusercreate' => $userid,
                    'fdatetime' => $now,
                    'fbranchcode' => $kodeCabang,
                    'fprdout' => '0',
                    'fsudahtagih' => '0',
                    'fprint' => 0,
                    'fapproval' => $isApproved ? '1' : '0',
                    'fuserapproved' => $isApproved ? (auth('sysuser')->user()->fsysuserid ?? Auth::user()->fname ?? $userid ?? 'system') : null,
                    'fdateapproved' => $isApproved ? $now : null,
                ], 'fstockmtid');

                // D. Insert Details
                foreach ($rowsDt as &$r) {
                    $r['fstockmtcode'] = $fstockmtcode;
                    $r['fstockmtno'] = $fstockmtno;
                }
                DB::table('trstockdt')->insert($rowsDt);
                $this->adjustPoReferenceQtyKecil($podAgg, -1);

                $this->syncGoodsReceiptJournalEntries(
                    $fstockmtno,
                    $fstockmtdate,
                    $kodeCabang,
                    $fsupplier,
                    $subtotal,
                    $ppnAmount,
                    $grandTotal,
                    $frate,
                    $userid
                );
            });
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->withInput()->withErrors(['detail' => $e->getMessage()]);
        } catch (Exception $e) {
            Log::error('PenerimaanBarang@store ERROR: ' . $e->getMessage());
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Gagal simpan: ' . $e->getMessage()], 500);
            }
            return back()->withInput()->withErrors(['detail' => 'Gagal simpan: ' . $e->getMessage()]);
        }

        $successPrompt = $isApproved ? [
            'type' => 'penerimaanbarang_create',
            'redirect_url' => route('penerimaanbarang.print', $fstockmtno),
        ] : null;

        if ($request->expectsJson()) {
            $payload = [
                'message' => 'Penerimaan barang berhasil disimpan.',
                'redirect_url' => route('penerimaanbarang.create'),
            ];
            if ($successPrompt) {
                $payload['success_prompt'] = $successPrompt;
            }
            return response()->json($payload);
        }

        $redirect = redirect()->route('penerimaanbarang.create')
            ->with('success', "Penerimaan Barang {$fstockmtno} berhasil disimpan.");
        if ($successPrompt) {
            $redirect->with('success_prompt', $successPrompt);
        }
        return $redirect;
    }

    public function edit(Request $request, $fstockmtid)
    {
        return $this->loadFormView($request, $fstockmtid, 'penerimaanbarang.edit', 'edit');
    }

    public function view(Request $request, $fstockmtid)
    {
        return $this->loadFormView($request, $fstockmtid, 'penerimaanbarang.edit', 'view');
    }

    public function delete(Request $request, $fstockmtid)
    {
        return $this->loadFormView($request, $fstockmtid, 'penerimaanbarang.edit', 'delete');
    }

    /**
     * Shared loader untuk edit / view / delete.
     */
    private function loadFormView(Request $request, $fstockmtid, string $viewName, string $action)
    {
        $suppliers = Supplier::orderBy('fsuppliername', 'asc')->get(['fsuppliercode', 'fsuppliername']);

        $warehouses = DB::table('mswh')
            ->select('fwhid', 'fwhcode', 'fwhname', 'fbranchcode', 'fnonactive')
            ->where('fnonactive', '0')
            ->orderBy('fwhcode')
            ->get();
        ['fcabang' => $defaultCabangName, 'fbranchcode' => $defaultBranchCode] = $this->resolveBranchContext();

        $penerimaanbarang = PenerimaanPembelianHeader::with([
            'details' => function ($q) {
                $q->leftJoin('msprd', 'msprd.fprdcode', '=', 'trstockdt.fprdcode')
                    ->leftJoin('trstockmt', 'trstockmt.fstockmtno', '=', 'trstockdt.fstockmtno')
                    ->leftJoin('mswh', 'mswh.fwhcode', '=', 'trstockmt.ffrom')
                    ->leftJoin('tr_pod as pod', 'pod.fpodid', '=', 'trstockdt.frefdtid')
                    ->select(
                        'trstockdt.*',
                        'msprd.fprdname',
                        'msprd.fprdcode as fitemcode_text',
                        'msprd.fsatuankecil',
                        'msprd.fsatuanbesar',
                        'msprd.fsatuanbesar2',
                        'msprd.fqtykecil',
                        'msprd.fqtykecil2',
                        'mswh.fwhname as fwhname',
                    )
                    ->orderBy('trstockdt.fstockdtid');
            },
        ])->findOrFail($fstockmtid);

        if (in_array($action, ['edit', 'delete'], true)) {
            if ($message = $this->getPostedPeriodLockMessage($penerimaanbarang->fstockmtdate)) {
                return redirect()
                    ->route('penerimaanbarang.view', $penerimaanbarang->fstockmtid)
                    ->with('error', $message);
            }
        }

        ['fcabang' => $selectedBranchName, 'fbranchcode' => $selectedBranchCode] = $this->resolveBranchContext($penerimaanbarang->fbranchcode ?? null);
        $usageLockMessage = $action === 'view' ? null : $this->getUsageLockMessage($penerimaanbarang);

        if (in_array($action, ['edit', 'delete'], true) && ! empty($usageLockMessage)) {
            return redirect()
                ->route('penerimaanbarang.view', $penerimaanbarang->fstockmtid)
                ->with('error', $usageLockMessage);
        }

        $oldUsageByPod = $penerimaanbarang->details
            ->groupBy(fn($d) => (int) ($d->frefdtid ?? 0))
            ->map(fn($rows) => (float) $rows->sum(fn($r) => (float) ($r->fqtykecil ?? 0)))
            ->all();

        $refPodIds = $penerimaanbarang->details->pluck('frefdtid')->all();
        $podRemainMap = $this->getPodRemainByIds($refPodIds);
        $poMetricMap = $this->getPoReferenceMetricsByPodIds($refPodIds);

        $savedItems = $penerimaanbarang->details->map(function ($d) use ($oldUsageByPod, $podRemainMap, $poMetricMap) {
            $remainKecil = $d->frefdtid
                ? max(0, (float) ($podRemainMap[(int) $d->frefdtid] ?? 0) + (float) ($oldUsageByPod[(int) $d->frefdtid] ?? 0))
                : 0;
            $poMetrics = $d->frefdtid
                ? ($poMetricMap[(int) $d->frefdtid] ?? ['fqtysisapo' => 0, 'fqtyditer' => 0])
                : ['fqtysisapo' => 0, 'fqtyditer' => 0];

            $itemQty = (float) ($d->fqty ?? 0);
            $itemPrice = (float) ($d->fprice ?? 0);
            $itemTotPrice = (float) ($d->ftotprice ?? 0);
            $itemTotal = $itemTotPrice > 0 ? $itemTotPrice : ($itemQty * $itemPrice);

            return [
                'uid' => $d->fstockdtid,
                'fitemcode' => $d->fitemcode_text ?? $d->fprdcode ?? '',
                'fitemname' => $d->fprdname ?? '',
                'fsatuan' => $d->fsatuan ?? '',
                'fprno' => $d->frefpr ?? '-',
                'frefdtno' => $d->frefdtno ?? null,
                'frefdtid' => $d->frefdtid ?? null,
                'fnoacak' => $d->fnoacak ?? '',
                'frefnoacak' => $d->frefnoacak ?? '',
                'fqty' => $itemQty,
                'fterima' => (float) ($d->fterima ?? 0),
                'fprice' => $itemPrice,
                'famount' => (float) ($d->famount ?? 0),
                'fdisc' => (float) ($d->fdiscpersen ?? 0),
                'ftotal' => $itemTotal,
                'fdesc' => is_array($d->fdesc) ? implode(', ', $d->fdesc) : ($d->fdesc ?? ''),
                'fketdt' => $d->fketdt ?? '',
                'fqtyremain' => $remainKecil,
                'fqtykecil_ref' => $remainKecil,
                'fqtysisapo' => (float) ($poMetrics['fqtysisapo'] ?? 0),
                'fqtyditer' => (float) ($poMetrics['fqtyditer'] ?? 0),
                'fqtymaxedit' => $this->qtyKecilToUnit($d, (string) ($d->fsatuan ?? ''), $remainKecil),
                'fsatuankecil' => $d->fsatuankecil ?? '',
                'fsatuanbesar' => $d->fsatuanbesar ?? '',
                'fsatuanbesar2' => $d->fsatuanbesar2 ?? '',
                'fqtykecil' => (float) ($d->fqtykecil ?? 0),
                'fqtykecil2' => (float) ($d->fqtykecil2 ?? 0),
                'maxqty' => 0,
                'units' => array_values(array_filter([
                    $d->fsatuankecil ?? '',
                    $d->fsatuanbesar ?? '',
                    $d->fsatuanbesar2 ?? '',
                ])),
            ];
        })->values();

        $products = $this->browseProducts();
        $productMap = $this->browseProductMap($products);

        $sumDetailsTotal = (float) $savedItems->sum('ftotal');
        $headerAmountMt = (float) ($penerimaanbarang->famountmt ?? 0);
        $headerAmount = (float) ($penerimaanbarang->famount ?? 0);
        $headerAmountPajak = (float) ($penerimaanbarang->famountpajak ?? $penerimaanbarang->famountpopajak ?? 0);

        $calcFamountponet = $headerAmountMt > 0
            ? $headerAmountMt
            : ($headerAmount > 0 ? ($headerAmount + $headerAmountPajak) : $sumDetailsTotal);

        return view($viewName, [
            'suppliers' => $suppliers,
            'selectedSupplierCode' => $penerimaanbarang->fsupplier,
            'fcabang' => $selectedBranchName ?? $defaultCabangName,
            'fbranchcode' => $selectedBranchCode ?: $defaultBranchCode,
            'warehouses' => $warehouses,
            'products' => $products,
            'productMap' => $productMap,
            'penerimaanbarang' => $penerimaanbarang,
            'displayFstockmtno' => $this->formatDisplayTransactionNumber($penerimaanbarang->fstockmtno ?? null, str_contains((string) ($penerimaanbarang->fstockmtno ?? ''), '/')),
            'savedItems' => $savedItems,
            'ppnAmount' => $headerAmountPajak,
            'famountponet' => $calcFamountponet,
            'famountpo' => $headerAmount > 0 ? $headerAmount : $sumDetailsTotal,
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'isUsageLocked' => ! empty($usageLockMessage),
            'usageLockMessage' => $usageLockMessage,
            'action' => $action,
        ]);
    }

    public function update(Request $request, $fstockmtid)
    {
        $allowNegativeStockQty = stock_boleh_minus();
        $request->validate([
            'fstockmtno' => ['nullable', 'string', 'max:100'],
            'fstockmtdate' => ['required', 'date'],
            'fsupplier' => ['required', 'string', 'max:30'],
            'ffrom' => ['required', 'string', 'max:30'],
            'fket' => ['nullable', 'string', 'max:500'],
            'fbranchcode' => ['nullable', 'string', 'max:20'],
            'fitemcode' => ['required', 'array', 'min:1'],
            'fitemcode.*' => ['required', 'string', 'max:50'],
            'frefdtno' => ['nullable', 'array'],
            'frefdtno.*' => ['nullable', 'string', 'max:50'],
            'frefdtid' => ['nullable', 'array'],
            'frefdtid.*' => ['nullable', 'integer'],
            'fsatuan' => ['nullable', 'array'],
            'fsatuan.*' => ['nullable', 'string', 'max:5'],
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
            'fnoacak' => ['nullable', 'array'],
            'fnoacak.*' => ['nullable', 'regex:/^[1-9]{3}$/'],
            'frefnoacak' => ['nullable', 'array'],
            'frefnoacak.*' => ['nullable', 'regex:/^\d{3}$/'],
            'fcurrency' => ['nullable', 'string', 'max:5'],
            'frate' => ['nullable', 'numeric', 'min:0'],
            'famountpopajak' => ['nullable', 'numeric', 'min:0'],
        ], [
            'ffrom.required' => 'Gudang wajib di isi.',
        ]);

        $this->ensureNoDuplicateDetailCodes($request->input('fitemcode', []), $request->input('fnoacak', []));

        $header = PenerimaanPembelianHeader::findOrFail($fstockmtid);

        if ($message = $this->getPostedPeriodLockMessage($header->fstockmtdate)) {
            return redirect()->route('penerimaanbarang.edit', $header->fstockmtid)->with('error', $message);
        }

        if ($message = $this->getUsageLockMessage($header)) {
            return redirect()->route('penerimaanbarang.index')->with('error', $message);
        }

        $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
        $this->ensureCreateDateWithinEditPeriod($fstockmtdate, $header->fstockmtdate);
        $fsupplier = trim((string) $request->input('fsupplier'));
        $ffrom = trim((string) $request->input('ffrom'));
        $fket = trim((string) $request->input('fket', ''));
        $fbranchcode = $request->input('fbranchcode');
        $fcurrency = $request->input('fcurrency', 'IDR');
        $frate = max(1, (float) $request->input('frate', 1));
        $ppnAmount = (float) $request->input('famountpopajak', 0);
        $now = now();

        $userLogin = Auth::guard('sysuser')->user() ?? Auth::user();
        $userName = $userLogin->fname ?? 'system';
        $userIdLog = $userLogin->fuserid ?? 'SYSTEM';

        $codes = $request->input('fitemcode', []);
        $satuans = $request->input('fsatuan', []);
        $refdtnos = $request->input('frefdtno', []);
        $refdtids = $request->input('frefdtid', []);
        $fnoacaks = $request->input('fnoacak', []);
        $frefnoacaks = $request->input('frefnoacak', []);
        $qtys = $request->input('fqty', []);
        $prices = $request->input('fprice', []);
        $descs = $request->input('fdesc', []);

        $uniqueCodes = array_values(array_unique(array_filter(array_map(fn($c) => trim((string) $c), $codes))));
        $prodMeta = DB::table('msprd')
            ->whereIn('fprdcode', $uniqueCodes)
            ->get()
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

        $rowsDt = [];
        $subtotal = 0.0;
        $usedNoAcaks = [];
        for ($i = 0, $cnt = count($codes); $i < $cnt; $i++) {
            $code = trim((string) ($codes[$i] ?? ''));
            $sat = trim((string) ($satuans[$i] ?? ''));
            $rno = trim((string) ($refdtnos[$i] ?? ''));
            $rawRid = $refdtids[$i] ?? null;
            $rid = ($rawRid !== null && $rawRid !== '') ? (int) $rawRid : null;
            $qty = (float) ($qtys[$i] ?? 0);
            $price = (float) ($prices[$i] ?? 0);
            $desc = trim((string) ($descs[$i] ?? ''));

            if ($code === '' || $qty <= 0) {
                continue;
            }

            $meta = $prodMeta[$code] ?? null;
            if (! $meta) {
                continue;
            }

            if ($rid === null || $rid <= 0) {
                continue;
            }

            if ($sat === '') {
                $sat = $pickDefaultSat($meta);
            }
            if ($rid !== null && $rid > 0) {
                $poUnit = DB::table('tr_pod')
                    ->where('fpodid', $rid)
                    ->value('fsatuan');
                if ($poUnit === null) {
                    return back()->withInput()->withErrors(['detail' => 'Detail PO tidak valid untuk produk ' . $code . '.']);
                }
                $sat = trim($poUnit);
            }
            $sat = mb_substr($sat, 0, 5);
            if ($sat === '') {
                continue;
            }

            $qtyKecil = $this->qtyPoToKecil($meta, $sat, $qty);

            $amount = $qty * $price;
            $subtotal += $amount;

            $rowsDt[] = [
                'fprdcode' => $code,
                'frefdtno' => $rno ?: null,
                'frefso' => $rno ?: null,
                'frefdtid' => $rid,
                'fnoacak' => $this->normalizeRandomNumber($fnoacaks[$i] ?? null, $usedNoAcaks),
                'frefnoacak' => $rid ? $this->normalizeReferenceRandomNumber($frefnoacaks[$i] ?? null) : null,
                'fqty' => $qty,
                'fqtykecil' => $qtyKecil,
                'fqtyremain' => $qtyKecil,
                'fprice' => $price,
                'fprice_rp' => $price * $frate,
                'ftotprice' => $amount,
                'ftotprice_rp' => $amount * $frate,
                'fuserupdate' => $userName,
                'fdatetime' => $now,
                'fketdt' => '',
                'fcode' => '0',
                'fdesc' => $desc,
                'fsatuan' => $sat,
                'fclosedt' => '0',
                'fdiscpersen' => 0,
                'fbiaya' => 0,
            ];
        }

        if (empty($rowsDt)) {
            return back()->withInput()->withErrors(['detail' => 'Minimal satu item valid (Kode, Satuan, Qty > 0).']);
        }

        if ($validationMessage = $this->validateUniqueReferenceUsage($rowsDt, $header->fstockmtno)) {
            return back()->withInput()->withErrors(['detail' => $validationMessage]);
        }

        if ($stockResponse = $this->validateStockMinusLines(
            $this->buildStockMinusLinesFromNetChange($rowsDt, (string) $ffrom, $this->fetchStockDetailRows((string) $header->fstockmtno), (string) $header->ffrom),
            $request->boolean('force_save')
        )) {
            return $stockResponse;
        }

        $podAgg = $this->aggregatePodReceiptByPod($rowsDt);
        $oldReceiptLines = DB::table('trstockdt')->where('fstockmtno', $header->fstockmtno)->get(['frefdtid', 'fqtykecil']);

        $grandTotal = $subtotal + $ppnAmount;

        try {
            DB::transaction(function () use (
                $header,
                $fstockmtdate,
                $fsupplier,
                $ffrom,
                $fket,
                $fbranchcode,
                $fcurrency,
                $frate,
                &$rowsDt,
                $subtotal,
                $ppnAmount,
                $grandTotal,
                $podAgg,
                $oldReceiptLines,
                $userName,
                $userIdLog
            ) {
                $now = now();
                $trxLogId = 'LOG' . $now->format('YmdHis') . rand(100, 999);

                $oldUsageByPod = [];
                foreach ($oldReceiptLines as $oldLine) {
                    $oldRefId = (int) ($oldLine->frefdtid ?? 0);
                    if ($oldRefId <= 0) {
                        continue;
                    }
                    $oldUsageByPod[$oldRefId] = ($oldUsageByPod[$oldRefId] ?? 0) + (float) ($oldLine->fqtykecil ?? 0);
                }

                $this->validateTrPodRemain($podAgg, $oldUsageByPod);
                $this->adjustPoReferenceQtyKecil($oldUsageByPod, 1);

                $kodeCabang = $header->fbranchcode;
                if ($fbranchcode && $fbranchcode !== $header->fbranchcode) {
                    $kodeCabang = $this->resolveKodeCabang($fbranchcode) ?: $kodeCabang;
                }

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
                    'ffrom' => $ffrom,
                    'fket' => $fket,
                    'fuserupdate' => $userName,
                    'fbranchcode' => $kodeCabang,
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

                DB::table('trstockdt')->where('fstockmtno', $header->fstockmtno)->delete();

                foreach ($rowsDt as &$r) {
                    $r['fstockmtcode'] = $header->fstockmtcode;
                    $r['fstockmtno'] = $header->fstockmtno;

                    // Simpan row ke trstockdt
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

                $this->adjustPoReferenceQtyKecil($podAgg, -1);

                $this->syncGoodsReceiptJournalEntries(
                    $header->fstockmtno,
                    $fstockmtdate,
                    $kodeCabang,
                    $fsupplier,
                    $subtotal,
                    $ppnAmount,
                    $grandTotal,
                    $frate,
                    $userName
                );
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();

            if ($request->expectsJson()) {
                return response()->json(['message' => $firstError ?: 'Gagal update penerimaan barang.'], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('error', $firstError ?: 'Gagal mengupdate penerimaan barang. Cek data.');
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('PenerimaanBarang@update ERROR: ' . $e->getMessage());
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Gagal update: ' . $e->getMessage()], 500);
            }
            return back()->withInput()->with('error', 'Gagal update: ' . $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Penerimaan Barang {$header->fstockmtno} berhasil diupdate.",
                'redirect_url' => route('penerimaanbarang.index'),
            ]);
        }

        return redirect()->route('penerimaanbarang.index')
            ->with('success', "Penerimaan Barang {$header->fstockmtno} berhasil diupdate.");
    }

    public function destroy($fstockmtid)
    {
        try {
            $penerimaanbarang = PenerimaanPembelianHeader::findOrFail($fstockmtid);

            if ($message = $this->getPostedPeriodLockMessage($penerimaanbarang->fstockmtdate)) {
                return redirect()->route('penerimaanbarang.edit', $penerimaanbarang->fstockmtid)->with('error', $message);
            }

            if ($message = $this->getUsageLockMessage($penerimaanbarang)) {
                return redirect()->route('penerimaanbarang.index')->with('error', $message);
            }

            if ($stockResponse = $this->validateStockMinusLines(
                $this->buildStockMinusLinesFromNetChange([], (string) $penerimaanbarang->ffrom, $this->fetchStockDetailRows((string) $penerimaanbarang->fstockmtno), (string) $penerimaanbarang->ffrom),
                request()->boolean('force_save')
            )) {
                return $stockResponse;
            }

            $userLogin = Auth::guard('sysuser')->user() ?? Auth::user();
            $userIdLog = $userLogin->fuserid ?? 'SYSTEM';

            DB::transaction(function () use ($penerimaanbarang, $userIdLog) {
                $now = now();
                $trxLogId = 'LOG' . $now->format('YmdHis') . rand(100, 999);

                // 1. INSERT Log Header (Delete)
                DB::table('log_trstockmt')->insert([
                    'ftrxlogid'        => $trxLogId,
                    'fstockmtid'       => $penerimaanbarang->fstockmtid,
                    'fstockmtno'       => $penerimaanbarang->fstockmtno,
                    'fbranchcode'      => $penerimaanbarang->fbranchcode,
                    'fstockmtcode'     => $penerimaanbarang->fstockmtcode,
                    'fstockmtdate'     => $penerimaanbarang->fstockmtdate,
                    'fprdout'          => $penerimaanbarang->fprdout,
                    'fsupplier'        => $penerimaanbarang->fsupplier,
                    'fcurrency'        => $penerimaanbarang->fcurrency,
                    'frate'            => $penerimaanbarang->frate,
                    'ftypebuy'         => $penerimaanbarang->ftypebuy,
                    'ftempohr'         => $penerimaanbarang->ftempohr,
                    'ftrancode'        => $penerimaanbarang->ftrancode,
                    'fsalesman'        => $penerimaanbarang->fsalesman,
                    'fjatuhtempo'      => $penerimaanbarang->fjatuhtempo,
                    'fprint'           => $penerimaanbarang->fprint,
                    'fsudahtagih'      => $penerimaanbarang->fsudahtagih,
                    'fdiscount'        => $penerimaanbarang->fdiscount,
                    'fupdatedat'       => $penerimaanbarang->fupdatedat,
                    'famount'          => $penerimaanbarang->famount,
                    'famount_rp'       => $penerimaanbarang->famount_rp,
                    'famountpajak'     => $penerimaanbarang->famountpajak,
                    'famountpajak_rp'  => $penerimaanbarang->famountpajak_rp,
                    'famountmt'        => $penerimaanbarang->famountmt,
                    'famountmt_rp'     => $penerimaanbarang->famountmt_rp,
                    'famountremain'    => $penerimaanbarang->famountremain,
                    'famountremain_rp' => $penerimaanbarang->famountremain_rp,
                    'frefno'           => $penerimaanbarang->frefno,
                    'frefpo'           => $penerimaanbarang->frefpo,
                    'ffrom'            => $penerimaanbarang->ffrom,
                    'fto'              => $penerimaanbarang->fto,
                    'fkirim'           => $penerimaanbarang->fkirim,
                    'fprdjadi'         => $penerimaanbarang->fprdjadi,
                    'fqtyjadi'         => $penerimaanbarang->fqtyjadi,
                    'fket'             => $penerimaanbarang->fket,
                    'fincludeppn'      => $penerimaanbarang->fincludeppn,
                    'fppnpersen'       => $penerimaanbarang->fppnpersen,
                    'fapplyppn'        => $penerimaanbarang->fapplyppn,
                    'fketinternal'     => $penerimaanbarang->fketinternal,
                    'fusercreate'      => $penerimaanbarang->fusercreate,
                    'fdatetime'        => $penerimaanbarang->fdatetime,
                    'fuserupdate'      => $penerimaanbarang->fuserupdate,
                    'feditmode'        => 'D',
                    'fuseridlog'       => $userIdLog,
                    'fdatetimelog'     => $now,
                ]);

                // 2. Ambil seluruh detail lalu catat ke log_trstockdt (Delete)
                $details = DB::table('trstockdt')->where('fstockmtno', $penerimaanbarang->fstockmtno)->get();
                foreach ($details as $detail) {
                    DB::table('log_trstockdt')->insert([
                        'ftrxlogid'     => $trxLogId,
                        'fstockdtid'    => $detail->fstockdtid,
                        'fstockmtcode'  => $detail->fstockmtcode,
                        'fstockmtno'    => $detail->fstockmtno,
                        'fprdcode'      => $detail->fprdcode,
                        'frefdtno'      => $detail->frefdtno,
                        'fqty'          => $detail->fqty,
                        'fqtyremain'    => $detail->fqtyremain,
                        'fsatuan'       => $detail->fsatuan,
                        'fqtykecil'     => $detail->fqtykecil,
                        'fprice'        => $detail->fprice,
                        'fprice_rp'     => $detail->fprice_rp,
                        'ftotprice'     => $detail->ftotprice,
                        'ftotprice_rp'  => $detail->ftotprice_rp,
                        'fketdt'        => $detail->fketdt,
                        'fcode'         => $detail->fcode,
                        'frefso'        => $detail->frefso,
                        'fdesc'         => $detail->fdesc,
                        'fclosedt'      => $detail->fclosedt,
                        'fdiscpersen'   => $detail->fdiscpersen,
                        'fbiaya'        => $detail->fbiaya,
                        'fpricenet'     => $detail->fpricenet,
                        'fnoacak'       => $detail->fnoacak,
                        'frefnoacak'    => $detail->frefnoacak,
                        'frefnoacak_so' => $detail->frefnoacak_so,
                        'fusercreate'   => $detail->fusercreate,
                        'fdatetime'     => $detail->fdatetime,
                        'fupdatedat'    => $detail->fupdatedat,
                        'fuserupdate'   => $detail->fuserupdate,
                        'feditmode'     => 'D',
                        'fuseridlog'    => $userIdLog,
                        'fdatetimelog'  => $now,
                    ]);
                }

                $oldUsageByPod = $details
                    ->groupBy(fn($row) => (int) ($row->frefdtid ?? 0))
                    ->map(fn($rows) => (float) $rows->sum(fn($row) => (float) ($row->fqtykecil ?? 0)))
                    ->all();

                $this->adjustPoReferenceQtyKecil($oldUsageByPod, 1);
                DB::table('trstockdt')
                    ->where('fstockmtno', $penerimaanbarang->fstockmtno)
                    ->delete();

                $this->deleteGoodsReceiptJournalEntries($penerimaanbarang->fstockmtno);
                $penerimaanbarang->delete();
            });

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Penerimaan barang berhasil dihapus.',
                    'redirect_url' => route('penerimaanbarang.index'),
                ]);
            }

            return redirect()->route('penerimaanbarang.index')
                ->with('success', 'Penerimaan barang berhasil dihapus.');
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Penerimaan barang belum bisa dihapus. Coba lagi: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->route('penerimaanbarang.delete', $fstockmtid)
                ->with('error', 'Penerimaan barang belum bisa dihapus. Coba lagi.');
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function resolveKodeCabang($fbranchcode): string
    {
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

        return $kodeCabang ?: 'NA';
    }

    private function validateUniqueReferenceUsage(array $rowsDt, ?string $exceptStockMtNo = null): ?string
    {
        $referenceDetailIds = collect($rowsDt)
            ->pluck('frefdtid')
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($referenceDetailIds)) {
            return null;
        }

        $query = DB::table('trstockdt as d')
            ->join('trstockmt as h', 'h.fstockmtno', '=', 'd.fstockmtno')
            ->where('h.fstockmtcode', 'TER')
            ->whereIn('d.frefdtid', $referenceDetailIds);

        if (! empty($exceptStockMtNo)) {
            $query->where('h.fstockmtno', '<>', $exceptStockMtNo);
        }

        $existing = $query
            ->orderBy('h.fstockmtno')
            ->select(
                'h.fstockmtno as transaction_no',
                DB::raw("COALESCE(NULLIF(TRIM(d.frefdtno), ''), NULLIF(TRIM(d.frefso), '')) as ref_no")
            )
            ->first();

        if (! $existing) {
            return null;
        }

        $refNo = trim((string) ($existing->ref_no ?? ''));
        $transactionNo = trim((string) ($existing->transaction_no ?? ''));

        if ($refNo === '' || $transactionNo === '') {
            return 'No. referensi sudah ada di transaksi lain.';
        }

        return 'No. referensi ' . $refNo . ' sudah ada di transaksi ' . $transactionNo . '.';
    }

    private function getUsageLockMessage(PenerimaanPembelianHeader $header): ?string
    {
        $detailIds = DB::table('trstockdt')
            ->where('fstockmtno', $header->fstockmtno)
            ->pluck('fstockdtid')
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->values();

        if ($detailIds->isEmpty()) {
            return null;
        }

        $usedBy = DB::table('trstockdt')
            ->where('fstockmtcode', 'BUY')
            ->whereIn('frefdtid', $detailIds->all())
            ->select('fstockmtno')
            ->distinct()
            ->orderBy('fstockmtno')
            ->pluck('fstockmtno');

        if ($usedBy->isEmpty()) {
            return null;
        }

        return "Information\nPenerimaan ini tidak dapat di-Edit/Delete.\nMasih ada Referensi di Transaksi:\n" . $usedBy->implode(', ');
    }

    private function syncGoodsReceiptJournalEntries(
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
        $this->deleteGoodsReceiptJournalEntries($fstockmtno);

        // --- Lookup accounts from set_account table ---
        $accountPersediaan = DB::table('set_account')->where('faccount_name', 'PEMBELIAN')->value('faccount');
        $accountClearing = DB::table('set_account')->where('faccount_name', 'PENERIMAANYGBLMDITAGIH')->value('faccount');

        $fjurnaltype  = 'TER';
        $hasPpn = (string) ($hdr->fapplyppn ?? '0') === '1' || (string) ($hdr->fincludeppn ?? '0') === '1';
        $sep = $hasPpn ? '.' : '/';
        $jurnalPrefix = sprintf('JV%s%s%s%s%s%s%s', $sep, $fjurnaltype, $sep, $kodeCabang, $sep, $fstockmtdate->format('y') . $fstockmtdate->format('m'), $sep);

        if (DB::getDriverName() === 'pgsql') {
            $lockKey = crc32('JURNAL|' . $fjurnaltype . '|' . $kodeCabang . '|' . $fstockmtdate->format('y-m'));
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
        $now       = now();

        $jurnalId = DB::table('jurnalmt')->insertGetId([
            'fbranchcode' => $kodeCabang,
            'fjurnalno'   => $fjurnalno,
            'fjurnaltype' => $fjurnaltype,
            'fjurnaldate' => $fstockmtdate,
            'fjurnalnote' => "Penerimaan Barang $fstockmtno dari $fsupplier",
            'fbalance'    => round($grandTotal, 2),
            'fbalance_rp' => round($grandTotal, 2),
            'fdatetime'   => $now,
            'fuserid'     => $userid,
        ], 'fjurnalmtid');

        $jurnalDt = [
            [
                'fjurnalmtid'  => $jurnalId,
                'fbranchcode'  => $kodeCabang,
                'fjurnaltype'  => $fjurnaltype,
                'fjurnalno'    => $fjurnalno,
                'flineno'      => 1,
                'faccount'     => (string) $accountPersediaan,
                'fdk'          => 'D',
                'fsubaccount'  => $fsupplier,
                'frefno'       => $fstockmtno,
                'frate'        => 1,
                'famount'      => round($subtotal, 2),
                'famount_rp'   => round($subtotal, 2),
                'faccountnote' => 'Persediaan / Saldo Awal',
                'fusercreate'  => $userid,
                'fdatetime'    => $now,
            ],
            [
                'fjurnalmtid'  => $jurnalId,
                'fbranchcode'  => $kodeCabang,
                'fjurnaltype'  => $fjurnaltype,
                'fjurnalno'    => $fjurnalno,
                'flineno'      => ($ppnAmount > 0 ? 3 : 2),
                'faccount'     => (string) $accountClearing,
                'fdk'          => 'K',
                'fsubaccount'  => $fsupplier,
                'frefno'       => $fstockmtno,
                'frate'        => 1,
                'famount'      => round($grandTotal, 2),
                'famount_rp'   => round($grandTotal, 2),
                'faccountnote' => 'Faktur Beli Belum Ditagih',
                'fusercreate'  => $userid,
                'fdatetime'    => $now,
            ],
        ];

        DB::table('jurnaldt')->insert($jurnalDt);
    }

    private function deleteGoodsReceiptJournalEntries(string $fstockmtno): void
    {
        $jurnalIds = DB::table('jurnaldt')
            ->where('frefno', $fstockmtno)
            ->where('fjurnaltype', 'TER')
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
