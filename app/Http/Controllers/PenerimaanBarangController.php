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

class PenerimaanBarangController extends Controller
{
    use ProductBrowseHelper;
    public function index(Request $request)
    {
        $canCreate = in_array('createPenerimaanBarang', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updatePenerimaanBarang', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deletePenerimaanBarang', explode(',', session('user_restricted_permissions', '')));
        $showActionsColumn = $canEdit || $canDelete;

        $year = $request->query('year');
        $month = $request->query('month');

        $availableYears = PenerimaanPembelianHeader::selectRaw('DISTINCT EXTRACT(YEAR FROM fdatetime) as year')
            ->where('fstockmtcode', 'TER')
            ->whereNotNull('fdatetime')
            ->orderByRaw('EXTRACT(YEAR FROM fdatetime) DESC')
            ->pluck('year');

        if ($request->ajax()) {
            $query = PenerimaanPembelianHeader::where('fstockmtcode', 'TER');

            $totalRecords = PenerimaanPembelianHeader::where('fstockmtcode', 'TER')->count();

            if ($search = $request->input('search.value')) {
                $query->where(function ($q) use ($search) {
                    $q->where('fstockmtno', 'like', "%{$search}%")
                        ->orWhereExists(function ($sub) use ($search) {
                            $sub->select(DB::raw(1))
                                ->from('trstockdt')
                                ->whereColumn('trstockdt.fstockmtno', 'trstockmt.fstockmtno')
                                ->where('trstockdt.frefdtno', 'ilike', "%{$search}%");
                        });
                });
            }
            if ($year) {
                $query->whereRaw('EXTRACT(YEAR FROM fdatetime) = ?', [$year]);
            }
            if ($month) {
                $query->whereRaw('EXTRACT(MONTH FROM fdatetime) = ?', [$month]);
            }

            $filteredRecords = (clone $query)->count();

            $orderColIdx = $request->input('order.0.column', 0);
            $orderDir = $request->input('order.0.dir', 'desc');

            $sortableColumns = [
                'fstockmtno',
                'fstockmtdate',
                'fstockmtdate',
                'fstockmtdate',
                'fket',
                'fstockmtdate',
                'famountmt',
            ];

            if (isset($sortableColumns[$orderColIdx])) {
                $query->orderBy($sortableColumns[$orderColIdx], $orderDir);
            } else {
                $query->orderBy('fstockmtid', 'desc');
            }

            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            $records = $query->skip($start)->take($length)->get(['fstockmtid', 'fstockmtno', 'fstockmtdate', 'ffrom', 'fsupplier', 'fket', 'famountmt']);

            $warehouseCodes = $records->pluck('ffrom')->filter()->unique();
            $warehouses = DB::table('mswh')->whereIn('fwhcode', $warehouseCodes)->pluck('fwhname', 'fwhcode');

            $supplierCodes = $records->pluck('fsupplier')->filter()->unique();
            $suppliers = DB::table('mssupplier')->whereIn('fsuppliercode', $supplierCodes)->pluck('fsuppliername', 'fsuppliercode');

            $stockMtNos = $records->pluck('fstockmtno');
            $trstockdts = DB::table('trstockdt')
                ->whereIn('fstockmtno', $stockMtNos)
                ->select('fstockmtno', DB::raw('MAX(frefdtno) as frefpo'))
                ->groupBy('fstockmtno')
                ->get()
                ->pluck('frefpo', 'fstockmtno');

            $data = $records->map(fn ($row) => [
                'fstockmtid' => $row->fstockmtid,
                'fstockmtno' => $row->fstockmtno,
                'fstockmtdate' => Carbon::parse($row->fstockmtdate)->format('d/m/Y'),
                'fwhname' => $warehouses[$row->ffrom] ?? '-',
                'fsuppliername' => $suppliers[$row->fsupplier] ?? '-',
                'fket' => $row->fket ?? '-',
                'frefpo' => $trstockdts[$row->fstockmtno] ?? '-',
                'famountmt' => 'Rp '.number_format((float) $row->famountmt, 0, ',', '.'),
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

        $query = DB::table('tr_poh')
            ->leftJoin('mssupplier', 'tr_poh.fsupplier', '=', 'mssupplier.fsuppliercode')
            ->select('tr_poh.*', 'mssupplier.fsuppliername', 'mssupplier.fsuppliercode')
            ->where('tr_poh.fprdin', '0');

        $recordsTotal = DB::table('tr_poh')->count();

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
                $query->orderBy('tr_poh.'.$orderColumn, $orderDir);
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
            return response()->json(['message' => 'PO tidak ditemukan'], 404);
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
                'm.fprdid as fprdcodeid',
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
                $item->maxqty = $remainKecil;

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
            $candidate = (string) random_int(1, 9).random_int(1, 9).random_int(1, 9);
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

    /**
     * Hitung sisa PO dinamis dalam satuan kecil berdasarkan detail PO dikurangi transaksi turunan.
     *
     * @param  array<int, int|string>  $podIds
     * @return array<int, float>
     */
    private function getPodRemainByIds(array $podIds): array
    {
        $ids = collect($podIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
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
            ->map(fn ($value) => (float) $value)
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
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
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
            ->mapWithKeys(fn ($row) => [
                (int) $row->fpodid => [
                    'fqtysisapo' => (float) ($row->fqtysisapo ?? 0),
                    'fqtyditer' => (float) ($row->fqtyditer ?? 0),
                ],
            ])
            ->all();
    }

    private function adjustPoReferenceQtyKecil(array $usageByPod, int $direction): void
    {
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
                    .($satuan !== '' ? " atau {$availableInPoUnitText} {$satuan}" : '')
                    .", berdasarkan total penerimaan barang."
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

        $lockKey = crc32("STOCKMT|{$prefix}|{$kodeCabang}|".$date->format('Y-m'));
        DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

        $noPrefix = sprintf('%s.%s.%s.%s.', $prefix, $kodeCabang, $date->format('y'), $date->format('m'));

        $last = DB::table('trstockmt')
            ->where('fstockmtno', 'like', $noPrefix.'%')
            ->selectRaw("MAX(CAST(split_part(fstockmtno, '.', 5) AS int)) AS lastno")
            ->value('lastno');

        $next = (int) $last + 1;

        return $noPrefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function print(string $fstockmtno)
    {
        $supplierSub = Supplier::select('fsuppliercode', 'fsuppliername');

        $hdr = PenerimaanPembelianHeader::query()
            ->leftJoinSub($supplierSub, 's', fn ($j) => $j->on('s.fsuppliercode', '=', 'trstockmt.fsupplier'))
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
            return redirect()->back()->with('error', 'PO tidak ditemukan.');
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

        $fmt = fn ($d) => $d ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d F Y') : '-';

        return view('penerimaanbarang.print', [
            'hdr' => $hdr,
            'dt' => $dt,
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

        $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;
        $branch = DB::table('mscabang')
            ->when(is_numeric($raw), fn ($q) => $q->where('fcabangid', (int) $raw))
            ->when(! is_numeric($raw), fn ($q) => $q->where('fcabangkode', $raw)->orWhere('fcabangname', $raw))
            ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

        $canApproval = in_array('approvalpr', explode(',', session('user_restricted_permissions', '')));
        $fcabang = $branch->fcabangname ?? (string) $raw;
        $fbranchcode = $branch->fcabangkode ?? (string) $raw;

        $products = $this->browseProducts();
        $productMap = $this->browseProductMap($products);

        return view('penerimaanbarang.create', [
            'warehouses' => $warehouses,
            'perms' => ['can_approval' => $canApproval],
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

        // 2) HEADER FIELDS
        $fstockmtno = trim((string) $request->input('fstockmtno', ''));
        $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
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
        $prdIds = $request->input('fprdcodeid', []);
        $satuans = $request->input('fsatuan', []);
        $fponos = $request->input('fpono', []);
        $refdtids = $request->input('frefdtid', []);
        $fnoacaks = $request->input('fnoacak', []);
        $frefnoacaks = $request->input('frefnoacak', []);
        $qtys = $request->input('fqty', []);
        $prices = $request->input('fprice', []);
        $descs = $request->input('fdesc', []);

        // 4) BUILD ROWS
        $uniqueCodes = array_values(array_unique(array_filter(array_map(fn ($c) => trim((string) $c), $codes))));
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

            $qtyKecil = $this->qtyPoToKecil($meta, $sat, $qty);

            $price = (float) ($prices[$i] ?? 0);
            $amount = $qty * $price;
            $subtotal += $amount;
            $frefdtid = isset($refdtids[$i]) ? (int) $refdtids[$i] : null;

            $rowsDt[] = [
                'fprdcode' => $code,
                'fprdcodeid' => isset($prdIds[$i]) ? (int) $prdIds[$i] : (int) $meta->fprdid,
                'frefdtno' => trim((string) ($fponos[$i] ?? '')),
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

                $yy = $fstockmtdate->format('y');
                $mm = $fstockmtdate->format('m');
                $fstockmtcode = 'TER';

                // B. Penomoran Otomatis
                if (empty($fstockmtno)) {
                    $prefix = sprintf('%s.%s.%s.%s.', $fstockmtcode, $kodeCabang, $yy, $mm);
                    $lockKey = crc32("STOCKMT|{$fstockmtcode}|{$kodeCabang}|".$fstockmtdate->format('Y-m'));
                    DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

                    $last = DB::table('trstockmt')
                        ->where('fstockmtno', 'like', $prefix.'%')
                        ->selectRaw("MAX(CAST(split_part(fstockmtno, '.', 5) AS int)) AS lastno")
                        ->value('lastno');

                    $fstockmtno = $prefix.str_pad((string) ((int) $last + 1), 4, '0', STR_PAD_LEFT);
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

                // E. Jurnal
                $fjurnaltype = 'JTB';
                $jurnalPrefix = sprintf('%s.%s.%s.%s.', $fjurnaltype, $kodeCabang, $yy, $mm);
                if (DB::getDriverName() === 'pgsql') {
                    $lastJ = DB::table('jurnalmt')->where('fjurnalno', 'like', $jurnalPrefix.'%')
                        ->selectRaw("MAX(CAST(split_part(fjurnalno, '.', 5) AS int)) AS lastno")->value('lastno');
                    $nextJ = (int) $lastJ + 1;
                } else {
                    $lastJurnalNo = DB::table('jurnalmt')
                        ->where('fjurnalno', 'like', $jurnalPrefix.'%')
                        ->orderByDesc('fjurnalno')
                        ->value('fjurnalno');

                    $nextJ = 1;
                    if ($lastJurnalNo && ($pos = strrpos($lastJurnalNo, '.')) !== false) {
                        $nextJ = ((int) substr($lastJurnalNo, $pos + 1)) + 1;
                    }
                }
                $fjurnalno = $jurnalPrefix.str_pad((string) $nextJ, 4, '0', STR_PAD_LEFT);

                $jurnalId = DB::table('jurnalmt')->insertGetId([
                    'fbranchcode' => $kodeCabang,
                    'fjurnalno' => $fjurnalno,
                    'fjurnaltype' => $fjurnaltype,
                    'fjurnaldate' => $fstockmtdate,
                    'fjurnalnote' => "Penerimaan $fstockmtno dari $fsupplier",
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
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['detail' => $e->getMessage()]);
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['detail' => 'Gagal simpan: '.$e->getMessage()]);
        }

        return redirect()->route('penerimaanbarang.create')->with('success', "Transaksi {$fstockmtno} berhasil disimpan.");
    }

    public function edit(Request $request, $fstockmtid)
    {
        return $this->loadFormView($request, $fstockmtid, 'penerimaanbarang.edit', 'edit');
    }

    public function view(Request $request, $fstockmtid)
    {
        return $this->loadFormView($request, $fstockmtid, 'penerimaanbarang.view', 'view');
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

        $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;
        $branch = DB::table('mscabang')
            ->when(is_numeric($raw), fn ($q) => $q->where('fcabangid', (int) $raw))
            ->when(! is_numeric($raw), fn ($q) => $q->where('fcabangkode', $raw)->orWhere('fcabangname', $raw))
            ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

        $warehouses = DB::table('mswh')
            ->select('fwhid', 'fwhcode', 'fwhname', 'fbranchcode', 'fnonactive')
            ->where('fnonactive', '0')
            ->orderBy('fwhcode')
            ->get();

        $defaultCabangName = $branch->fcabangname ?? (string) $raw;
        $defaultBranchCode = $branch->fcabangkode ?? (string) $raw;

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

        $selectedBranchCode = trim((string) ($penerimaanbarang->fbranchcode ?? ''));
        $selectedBranchName = $selectedBranchCode !== ''
          ? DB::table('mscabang')->where('fcabangkode', $selectedBranchCode)->value('fcabangname')
          : null;
        $usageLockMessage = $action === 'view' ? null : $this->getUsageLockMessage($penerimaanbarang);

        $oldUsageByPod = $penerimaanbarang->details
            ->groupBy(fn ($d) => (int) ($d->frefdtid ?? 0))
            ->map(fn ($rows) => (float) $rows->sum(fn ($r) => (float) ($r->fqtykecil ?? 0)))
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
                'fprdcodeid' => $d->fprdcodeid ?? null,
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
                'units' => [],
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
            'fprdcodeid' => ['nullable', 'array'],
            'fprdcodeid.*' => ['nullable', 'integer'],
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

        $header = PenerimaanPembelianHeader::findOrFail($fstockmtid);

        if ($message = $this->getUsageLockMessage($header)) {
            return redirect()->route('penerimaanbarang.index')->with('error', $message);
        }

        $fstockmtdate = Carbon::parse($request->fstockmtdate)->startOfDay();
        $fsupplier = trim((string) $request->input('fsupplier'));
        $ffrom = trim((string) $request->input('ffrom'));
        $fket = trim((string) $request->input('fket', ''));
        $fbranchcode = $request->input('fbranchcode');
        $fcurrency = $request->input('fcurrency', 'IDR');
        $frate = max(1, (float) $request->input('frate', 1));
        $ppnAmount = (float) $request->input('famountpopajak', 0);
        $now = now();

        $codes = $request->input('fitemcode', []);
        $prdIds = $request->input('fprdcodeid', []);
        $satuans = $request->input('fsatuan', []);
        $refdtnos = $request->input('frefdtno', []);
        $refdtids = $request->input('frefdtid', []);
        $fnoacaks = $request->input('fnoacak', []);
        $frefnoacaks = $request->input('frefnoacak', []);
        $qtys = $request->input('fqty', []);
        $prices = $request->input('fprice', []);
        $descs = $request->input('fdesc', []);

        $uniqueCodes = array_values(array_unique(array_filter(array_map(fn ($c) => trim((string) $c), $codes))));
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

            $prdCodeId = isset($prdIds[$i]) && $prdIds[$i] !== '' ? (int) $prdIds[$i] : (int) $meta->fprdid;

            if ($sat === '') {
                $sat = $pickDefaultSat($meta);
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
                'fprdcodeid' => $prdCodeId,
                'frefdtno' => $rno ?: null,
                'frefdtid' => $rid,
                'fnoacak' => $this->normalizeRandomNumber($fnoacaks[$i] ?? null, $usedNoAcaks),
                'frefnoacak' => $rid ? $this->normalizeReferenceRandomNumber($frefnoacaks[$i] ?? null) : null,
                'frefso' => null,
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
            ->with('success', "Transaksi {$header->fstockmtno} berhasil diperbarui.");
    }

    public function destroy($fstockmtid)
    {
        try {
            $penerimaanbarang = PenerimaanPembelianHeader::findOrFail($fstockmtid);

            if ($message = $this->getUsageLockMessage($penerimaanbarang)) {
                return redirect()->route('penerimaanbarang.index')->with('error', $message);
            }

            DB::transaction(function () use ($penerimaanbarang) {
                $oldUsageByPod = DB::table('trstockdt')
                    ->where('fstockmtno', $penerimaanbarang->fstockmtno)
                    ->get(['frefdtid', 'fqtykecil'])
                    ->groupBy(fn ($row) => (int) ($row->frefdtid ?? 0))
                    ->map(fn ($rows) => (float) $rows->sum(fn ($row) => (float) ($row->fqtykecil ?? 0)))
                    ->all();

                $this->adjustPoReferenceQtyKecil($oldUsageByPod, 1);
                DB::table('trstockdt')
                    ->where('fstockmtno', $penerimaanbarang->fstockmtno)
                    ->delete();

                $jurnalIds = DB::table('jurnaldt')
                    ->where('frefno', $penerimaanbarang->fstockmtno)
                    ->pluck('fjurnalmtid')
                    ->filter(fn ($id) => ! is_null($id))
                    ->unique()
                    ->values();

                if ($jurnalIds->isNotEmpty()) {
                    DB::table('jurnaldt')->whereIn('fjurnalmtid', $jurnalIds->all())->delete();
                    DB::table('jurnalmt')->whereIn('fjurnalmtid', $jurnalIds->all())->delete();
                }

                $penerimaanbarang->delete();
            });

            return redirect()->route('penerimaanbarang.index')
                ->with('success', 'Data Penerimaan Barang '.$penerimaanbarang->fstockmtno.' berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->route('penerimaanbarang.delete', $fstockmtid)
                ->with('error', 'Gagal menghapus data: '.$e->getMessage());
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

    private function getUsageLockMessage(PenerimaanPembelianHeader $header): ?string
    {
        $detailIds = DB::table('trstockdt')
            ->where('fstockmtno', $header->fstockmtno)
            ->pluck('fstockdtid')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
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

        return 'Penerimaan Barang '.$header->fstockmtno.' tidak dapat diubah atau dihapus karena sudah digunakan pada Faktur Pembelian: '.$usedBy->implode(', ').'.';
    }
}
