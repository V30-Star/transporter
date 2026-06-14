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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PenerimaanBarangController extends Controller
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

    public function index(Request $request)
    {
        $canCreate = in_array('createPenerimaanBarang', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updatePenerimaanBarang', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deletePenerimaanBarang', explode(',', session('user_restricted_permissions', '')));
        $showActionsColumn = $canEdit || $canDelete;

        $year = $request->query('year');
        $month = $request->query('month');

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

            $orderColIdx = $request->input('order.0.column', 0);
            $orderDir = $request->input('order.0.dir', 'desc');

            $sortableColumns = [
                'trstockmt.fstockmtno',
                'trstockmt.fstockmtdate',
                'trstockmt.fstockmtdate',
                'trstockmt.fstockmtdate',
                'trstockmt.fket',
                'trstockmt.fstockmtdate',
                'trstockmt.famountmt',
            ];

            if (isset($sortableColumns[$orderColIdx])) {
                $query->orderBy($sortableColumns[$orderColIdx], $orderDir);
            } else {
                $query->orderBy('trstockmt.fstockmtid', 'desc');
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
                'fstockmtno_display' => $this->formatDisplayTransactionNumber($row->fstockmtno, false),
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
            'month'
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
                DB::raw('GREATEST(COALESCE(d.fqtykecil, 0) - COALESCE(st.fqtykecilterima, 0), 0) AS fqtykecil_sisa'),
                DB::raw("COALESCE(
                    CASE
                        WHEN d.fsatuan = m.fsatuanbesar
                            THEN (COALESCE(d.fqtykecil, 0) - COALESCE(st.fqtykecilterima, 0)) / NULLIF(m.fqtykecil, 0)
                        WHEN d.fsatuan = m.fsatuanbesar2
                            THEN (COALESCE(d.fqtykecil, 0) - COALESCE(st.fqtykecilterima, 0)) / NULLIF(m.fqtykecil2, 0)
                        ELSE COALESCE(d.fqtykecil, 0) - COALESCE(st.fqtykecilterima, 0)
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
            ->orderBy('d.fnou')
            ->get()
            ->map(function ($item) use ($header) {
                $item->frefdtno = (string) $header->fpono;
                $remainKecil = (float) ($item->fqtykecil_sisa ?? 0);
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
            });

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
        if ($sat !== '' && $besar !== '' && strcasecmp($sat, $besar) === 0 && $rasio > 0) {
            return $qty * $rasio;
        }
        if ($sat !== '' && $besar2 !== '' && strcasecmp($sat, $besar2) === 0 && $rasio2 > 0) {
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
            $messages["fitemcode.$index"] = "Kode produk {$code} tidak boleh sama dalam satu Penerimaan Barang.";
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

    private function generateStockMtCode(?Carbon $onDate = null, $branch = null, string $prefix = 'TER'): string
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

        $lockKey = crc32("STOCKMT|{$prefix}|{$kodeCabang}|" . $date->format('Y-m'));
        DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

        $noPrefix = sprintf('%s.%s.%s.%s.', $prefix, $kodeCabang, $date->format('Y'), $date->format('m'));

        $last = DB::table('trstockmt')
            ->where('fstockmtno', 'like', $noPrefix . '%')
            ->selectRaw("MAX(CAST(split_part(fstockmtno, '.', 5) AS int)) AS lastno")
            ->value('lastno');

        $next = (int) $last + 1;

        return $noPrefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function print(string $fstockmtno)
    {
        $supplierSub = Supplier::select('fsuppliercode', 'fsuppliername');

        $hdr = PenerimaanPembelianHeader::query()
            ->leftJoinSub($supplierSub, 's', fn($j) => $j->on('s.fsuppliercode', '=', 'trstockmt.fsupplier'))
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
            ->leftJoin('msprd as p', 'p.fprdcode', '=', 'trstockdt.fprdcode') // join by varchar code
            ->where('trstockdt.fstockmtno', $fstockmtno)
            ->orderBy('trstockdt.fprdcode')
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
            'displayFstockmtno' => $this->formatDisplayTransactionNumber($hdr->fstockmtno ?? null, false),
            'fmt' => $fmt,
            'company_name' => config('app.company_name', 'PT. DEMO VERSION'),
            'company_city' => config('app.company_city', 'Tangerang'),
        ]);
    }

    public function create(Request $request)
    {
        $suppliers = Supplier::orderBy('fsuppliername', 'asc')->get(['fsuppliercode', 'fsuppliername']);

        $warehouses = DB::table('mswh')
            ->select('fwhid', 'fwhcode', 'fwhname', 'fbranchcode', 'fnonactive')
            ->where('fnonactive', '0')
            ->orderBy('fwhcode')
            ->get();

        ['fcabang' => $fcabang, 'fbranchcode' => $fbranchcode] = $this->resolveBranchContext();

        $products = $this->browseProducts();
        $productMap = $this->browseProductMap($products);

        return view('penerimaanbarang.create', [
            'warehouses' => $warehouses,
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
        // 1) VALIDASI
        $request->validate([
            'fstockmtno' => ['nullable', 'string', 'max:100'],
            'fstockmtdate' => ['required', 'date'],
            'fsupplier' => ['required', 'string', 'max:30'],
            'ffrom' => ['nullable', 'string', 'max:30'],
            'fket' => ['nullable', 'string', 'max:500'],
            'fbranchcode' => ['nullable', 'string', 'max:20'],
            'fitemcode' => ['required', 'array', 'min:1'],
            'fitemcode.*' => ['required', 'string', 'max:50'],
            'fqty' => ['required', 'array'],
            'fqty.*' => ['numeric', 'min:0.001'],
            'fprice' => ['required', 'array'],
            'fprice.*' => ['numeric', 'min:0'],
            'fnoacak' => ['nullable', 'array'],
            'fnoacak.*' => ['nullable', 'regex:/^[1-9]{3}$/'],
            'frefnoacak' => ['nullable', 'array'],
            'frefnoacak.*' => ['nullable', 'regex:/^\d{3}$/'],
        ]);

        $this->ensureNoDuplicateDetailCodes($request->input('fitemcode', []));

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

            $frefdtid = isset($refdtids[$i]) ? (int) $refdtids[$i] : null;
            if (! $frefdtid) {
                return back()->withInput()->withErrors(['detail' => 'Penerimaan Barang hanya boleh input produk dari Add PO.']);
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
            return back()->withInput()->withErrors(['detail' => 'Minimal satu item valid diperlukan.']);
        }

        if ($validationMessage = $this->validateUniqueReferenceUsage($rowsDt)) {
            return back()->withInput()->withErrors(['detail' => $validationMessage]);
        }

        $podAgg = $this->aggregatePodReceiptByPod($rowsDt);

        $grandTotal = $subtotal + $ppnAmount;

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

                $yy = $fstockmtdate->format('Y');
                $mm = $fstockmtdate->format('m');
                $fstockmtcode = 'TER';

                // B. Penomoran Otomatis
                if (empty($fstockmtno)) {
                    $prefix = sprintf('%s.%s.%s.%s.', $fstockmtcode, $kodeCabang, $yy, $mm);
                    $lockKey = crc32("STOCKMT|{$fstockmtcode}|{$kodeCabang}|" . $fstockmtdate->format('Y-m'));
                    DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

                    $last = DB::table('trstockmt')
                        ->where('fstockmtno', 'like', $prefix . '%')
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
                ], 'fstockmtid');

                // D. Insert Details
                foreach ($rowsDt as &$r) {
                    $r['fstockmtcode'] = $fstockmtcode;
                    $r['fstockmtno'] = $fstockmtno;
                }
                DB::table('trstockdt')->insert($rowsDt);
                $this->adjustPoReferenceQtyKecil($podAgg, -1);
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['detail' => $e->getMessage()]);
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['detail' => 'Gagal simpan: ' . $e->getMessage()]);
        }

        return redirect()->route('penerimaanbarang.create')->with('success', 'Penerimaan barang '.$this->formatDisplayTransactionNumber($fstockmtno, false).' berhasil disimpan.');
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
                'fqty' => (float) ($d->fqty ?? 0),
                'fterima' => (float) ($d->fterima ?? 0),
                'fprice' => (float) ($d->fprice ?? 0),
                'famount' => (float) ($d->famount ?? 0),
                'fdisc' => (float) ($d->fdiscpersen ?? 0),
                'ftotal' => (float) ($d->ftotprice ?? 0),
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

        return view($viewName, [
            'suppliers' => $suppliers,
            'selectedSupplierCode' => $penerimaanbarang->fsupplier,
            'fcabang' => $selectedBranchName ?? $defaultCabangName,
            'fbranchcode' => $selectedBranchCode ?: $defaultBranchCode,
            'warehouses' => $warehouses,
            'products' => $products,
            'productMap' => $productMap,
            'penerimaanbarang' => $penerimaanbarang,
            'displayFstockmtno' => $this->formatDisplayTransactionNumber($penerimaanbarang->fstockmtno ?? null, false),
            'savedItems' => $savedItems,
            'ppnAmount' => (float) ($penerimaanbarang->famountpopajak ?? 0),
            'famountponet' => (float) ($penerimaanbarang->famountponet ?? 0),
            'famountpo' => (float) ($penerimaanbarang->famountpo ?? 0),
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'isUsageLocked' => ! empty($usageLockMessage),
            'usageLockMessage' => $usageLockMessage,
            'action' => $action,
        ]);
    }

    public function update(Request $request, $fstockmtid)
    {
        $request->validate([
            'fstockmtno' => ['nullable', 'string', 'max:100'],
            'fstockmtdate' => ['required', 'date'],
            'fsupplier' => ['required', 'string', 'max:30'],
            'ffrom' => ['nullable', 'string', 'max:30'],
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
            'fqty.*' => ['numeric', 'min:0.001'],
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
        ]);

        $this->ensureNoDuplicateDetailCodes($request->input('fitemcode', []));

        $header = PenerimaanPembelianHeader::findOrFail($fstockmtid);

        if ($message = $this->getPostedPeriodLockMessage($header->fstockmtdate)) {
            return redirect()->route('penerimaanbarang.view', $header->fstockmtid)->with('error', $message);
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
            $rid = isset($refdtids[$i]) ? (int) $refdtids[$i] : null;
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

            if (! $rid) {
                return back()->withInput()->withErrors(['detail' => 'Penerimaan Barang hanya boleh input produk dari Add PO.']);
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
                'frefsoid' => null,
                'fqty' => $qty,
                'fqtykecil' => $qtyKecil,
                'fqtyremain' => $qtyKecil,
                'fprice' => $price,
                'fprice_rp' => $price * $frate,
                'ftotprice' => $amount,
                'ftotprice_rp' => $amount * $frate,
                'fuserupdate' => Auth::user()->fname ?? 'system',
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
                $oldReceiptLines
            ) {
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
                    'fuserupdate' => Auth::user()->fname ?? 'system',
                    'fbranchcode' => $kodeCabang,
                ]);

                DB::table('trstockdt')->where('fstockmtno', $header->fstockmtno)->delete();

                $nextNouRef = 1;
                foreach ($rowsDt as &$r) {
                    $r['fstockmtcode'] = $header->fstockmtcode;
                    $r['fstockmtno'] = $header->fstockmtno;
                }
                unset($r);

                DB::table('trstockdt')->insert($rowsDt);
                $this->adjustPoReferenceQtyKecil($podAgg, -1);
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['detail' => $e->getMessage()]);
        }

        return redirect()->route('penerimaanbarang.index')
            ->with('success', 'Penerimaan barang '.$this->formatDisplayTransactionNumber($header->fstockmtno, false).' berhasil diupdate.');
    }

    public function destroy($fstockmtid)
    {
        try {
            $penerimaanbarang = PenerimaanPembelianHeader::findOrFail($fstockmtid);

            if ($message = $this->getPostedPeriodLockMessage($penerimaanbarang->fstockmtdate)) {
                return redirect()->route('penerimaanbarang.view', $penerimaanbarang->fstockmtid)->with('error', $message);
            }

            if ($message = $this->getUsageLockMessage($penerimaanbarang)) {
                return redirect()->route('penerimaanbarang.index')->with('error', $message);
            }

            DB::transaction(function () use ($penerimaanbarang) {
                $oldUsageByPod = DB::table('trstockdt')
                    ->where('fstockmtno', $penerimaanbarang->fstockmtno)
                    ->get(['frefdtid', 'fqtykecil'])
                    ->groupBy(fn($row) => (int) ($row->frefdtid ?? 0))
                    ->map(fn($rows) => (float) $rows->sum(fn($row) => (float) ($row->fqtykecil ?? 0)))
                    ->all();

                $this->adjustPoReferenceQtyKecil($oldUsageByPod, 1);
                DB::table('trstockdt')
                    ->where('fstockmtno', $penerimaanbarang->fstockmtno)
                    ->delete();

                $penerimaanbarang->delete();
            });

            return redirect()->route('penerimaanbarang.index')
                ->with('success', 'Penerimaan barang ' . $this->formatDisplayTransactionNumber($penerimaanbarang->fstockmtno, false) . ' berhasil dihapus.');
        } catch (\Exception $e) {
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
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
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
}
