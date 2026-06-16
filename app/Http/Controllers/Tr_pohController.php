<?php

namespace App\Http\Controllers;

use App\Mail\ApprovalEmailPo;
use App\Http\Controllers\Concerns\ProductBrowseHelper;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Tr_pod;
use App\Models\Tr_poh;
use App\Models\Tr_prd;
use App\Models\Tr_prh;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail; // sekalian biar aman untuk tanggal
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Tr_pohController extends Controller
{
    private const MEMO_DEBIT_ACCOUNT = '11400';
    private const MEMO_CREDIT_ACCOUNT = '21100';

    use ProductBrowseHelper;

    private function formatDisplayTransactionNumber(?string $number, bool $useSlash = false): string
    {
        $normalized = trim((string) $number);
        if ($normalized === '') {
            return '-';
        }

        $separator = $useSlash ? '/' : '.';

        return (string) preg_replace('/[.\/](\d+)$/', $separator . '$1', $normalized, 1);
    }

    private function canApprovePurchaseOrder(): bool
    {
        return in_array('approvePO', explode(',', session('user_restricted_permissions', '')));
    }

    private function getApprovalRecipients(): array
    {
        return array_values(array_filter([
            trim((string) config('approval.purchase_order.stage1', '')),
            trim((string) config('approval.purchase_order.stage2', '')),
        ]));
    }

    public function index(Request $request)
    {
        // Ambil izin (permissions)
        $canCreate = in_array('createTr_poh', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updateTr_poh', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteTr_poh', explode(',', session('user_restricted_permissions', '')));
        $showActionsColumn = $canEdit || $canDelete;

        $status = trim((string) $request->query('status', 'all'));
        $year = $request->query('year');
        $month = $request->query('month');

        // Ambil tahun-tahun yang tersedia
        $availableYearsQuery = Tr_poh::selectRaw('DISTINCT EXTRACT(YEAR FROM fdatetime) as year')
            ->whereNotNull('fdatetime');
        $this->applyBranchVisibilityScope($availableYearsQuery, 'tr_poh.fbranchcode');
        $availableYears = $availableYearsQuery
            ->orderByRaw('EXTRACT(YEAR FROM fdatetime) DESC')
            ->pluck('year');

        // --- Handle Request AJAX dari DataTables ---
        if ($request->ajax()) {

            // Gunakan prefix tabel 'tr_poh.*' untuk menghindari kolom ganda (ambiguous)
            $query = Tr_poh::query()
                ->select([
                    'tr_poh.fpohid',
                    'tr_poh.fpono',
                    'tr_poh.fapplyppn',
                    'tr_poh.fsupplier',
                    'tr_poh.fpodate',
                    'tr_poh.fclose',
                    'tr_poh.fprdin',
                    'tr_poh.fusercreate',
                    'tr_poh.fapproval',
                    'tr_poh.fdatetime',
                    'tr_poh.fbranchcode',
                    'tr_poh.famountpo',
                    'tr_poh.fcurrency',
                    'mssupplier.fsuppliername',
                    'tr_prh.fprno',
                    'mscurrency.fcurrname',
                    DB::raw('STRING_AGG(DISTINCT tr_pod.frefdtno, \', \') as frefdtno'),
                ])
                ->leftJoin('mssupplier', 'tr_poh.fsupplier', '=', 'mssupplier.fsuppliercode')
                ->leftJoin('tr_pod', 'tr_poh.fpono', '=', 'tr_pod.fpono')
                ->leftJoin('mscurrency', 'tr_poh.fcurrency', '=', 'mscurrency.fcurrcode')
                ->leftJoin('tr_prh', 'tr_prh.fprno', '=', 'tr_pod.frefdtno');
            $this->applyBranchVisibilityScope($query, 'tr_poh.fbranchcode');
            $totalRecords = (clone $query)->distinct('tr_poh.fpohid')->count('tr_poh.fpohid');

            // Handle Search - Beri prefix tabel agar tidak bingung
            if ($search = $request->input('search.value')) {
                $query->where(function ($q) use ($search) {
                    $q->where('tr_poh.fpono', 'ILIKE', "%{$search}%")
                        ->orWhere('tr_poh.fsupplier', 'ILIKE', "%{$search}%")
                        ->orWhere('mssupplier.fsuppliername', 'ILIKE', "%{$search}%")
                        ->orWhere('tr_pod.frefdtno', 'ILIKE', "%{$search}%");
                });
            }

            // Filter status bisnis PO
            $statusFilter = trim((string) $request->query('status', 'all'));
            if ($statusFilter === 'open') {
                $query->where('tr_poh.fclose', '0')
                    ->whereNotIn('tr_poh.fprdin', ['1', '2']);
            } elseif ($statusFilter === 'done') {
                $query->where('tr_poh.fclose', '0')
                    ->where('tr_poh.fprdin', '1');
            } elseif ($statusFilter === 'partial') {
                $query->where('tr_poh.fclose', '0')
                    ->where('tr_poh.fprdin', '2');
            } elseif ($statusFilter === 'close') {
                $query->where('tr_poh.fclose', '1');
            }

            // Filter tahun & bulan
            if ($year) {
                $query->whereRaw('EXTRACT(YEAR FROM tr_poh.fdatetime) = ?', [$year]);
            }
            if ($month) {
                $query->whereRaw('EXTRACT(MONTH FROM tr_poh.fdatetime) = ?', [$month]);
            }

            $columnSearches = collect($request->input('columns', []))
                ->mapWithKeys(function ($column) {
                    $name = trim((string) ($column['name'] ?? ''));
                    $value = trim((string) data_get($column, 'search.value', ''));

                    return $name !== '' ? [$name => $value] : [];
                });

            $supplierSearch = trim((string) ($columnSearches->get('fsuppliername', '')));
            if ($supplierSearch !== '') {
                $query->where('mssupplier.fsuppliername', 'ILIKE', "%{$supplierSearch}%");
            }

            // Karena join ke child (tr_pod), gunakan groupBy agar baris tidak double di index
            $query->groupBy(
                'tr_poh.fpohid',
                'tr_poh.fpono',
                'tr_poh.fapplyppn',
                'tr_poh.fsupplier',
                'tr_poh.fpodate',
                'tr_poh.fclose',
                'tr_poh.fprdin',
                'tr_poh.fusercreate',
                'tr_poh.fapproval',
                'tr_poh.fbranchcode',
                'tr_poh.famountpo',
                'tr_poh.fcurrency',
                'tr_poh.fdatetime', // Pastikan semua kolom tr_poh masuk atau gunakan agregat
                'mssupplier.fsuppliername',
                'tr_prh.fprno',
                'mscurrency.fcurrname'
            );

            $filteredRecords = DB::table(DB::raw("({$query->toSql()}) as sub"))
                ->mergeBindings($query->getQuery())
                ->count();

            // Sorting
            $orderColIdx = $request->input('order.0.column', 2);
            $orderDir = $request->input('order.0.dir', 'desc');
            $sortableColumns = [
                0 => 'tr_poh.fbranchcode',
                1 => 'tr_poh.fpono',
                2 => 'tr_poh.fpodate',
                3 => 'mssupplier.fsuppliername',
                4 => 'tr_prh.fprno',
                5 => 'tr_poh.fcurrency',
                6 => 'tr_poh.famountpo',
                7 => 'tr_poh.fapproval',
                8 => 'tr_poh.fclose',
                9 => 'tr_poh.fusercreate',
            ];

            if (isset($sortableColumns[$orderColIdx]) && $sortableColumns[$orderColIdx] !== '') {
                $query->orderBy($sortableColumns[$orderColIdx], $orderDir);
            } else {
                $query->orderBy('tr_poh.fpodate', 'desc');
            }

            // Paginasi
            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            $records = $query->skip($start)->take($length)->get();

            // Format Data
            $data = $records->map(function ($row) {
                return [
                    'fpono' => $row->fpono,
                    'fpono_display' => $this->formatDisplayTransactionNumber($row->fpono, (int) ($row->fapplyppn ?? 0) === 1),
                    'fpohid' => $row->fpohid,
                    'fsupplier' => $row->fsupplier,
                    'fpodate' => $row->fpodate
                        ? ($row->fpodate instanceof \Carbon\Carbon ? $row->fpodate : \Carbon\Carbon::parse($row->fpodate))->format('d-m-Y')
                        : '',
                    'fprdin' => (string) ($row->fprdin ?? '0'),
                    'fclose' => (string) ($row->fclose ?? '0'),
                    'fusercreate' => $row->fusercreate,
                    'fapproval' => $row->fapproval,
                    'fsuppliername' => $row->fsuppliername,
                    'fprno' => $row->fprno, // Kolom dari tr_prd
                    'frefdtno' => $row->frefdtno,
                    'fbranchcode' => $row->fbranchcode,
                    'famountpo' => $row->famountpo,
                    'fcurrency' => $row->fcurrency,
                    'fcurrname' => $row->fcurrname,
                ];
            });

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        }

        return view('tr_poh.index', compact(
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
        // Base query dengan JOIN
        $query = Tr_prh::leftJoin('mssupplier', 'tr_prh.fsupplier', '=', 'mssupplier.fsuppliercode')
            ->leftJoin('mscabang', 'tr_prh.fbranchcode', '=', 'mscabang.fcabangkode')
            ->select(
                'tr_prh.*',
                'mssupplier.fsuppliername',
                'mssupplier.fsuppliercode',
                'mscabang.fcabangname'
            )
            ->whereIn('tr_prh.fclose', ['0', ''])
            ->whereIn('tr_prh.fprdin', ['0', '']);

        // Total records tanpa filter
        $recordsTotal = Tr_prh::count();

        // Search
        if ($request->filled('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tr_prh.fprno', 'ilike', "%{$search}%")
                    ->orWhere('mssupplier.fsuppliername', 'ilike', "%{$search}%")
                    ->orWhere('mssupplier.fsuppliercode', 'ilike', "%{$search}%")
                    ->orWhere('tr_prh.fbranchcode', 'ilike', "%{$search}%")
                    ->orWhere('mscabang.fcabangname', 'ilike', "%{$search}%");
            });
        }

        // Total records setelah filter
        $recordsFiltered = $query->count();

        // Sorting
        $orderColumn = $request->input('order_column', 'fprdate');
        $orderDir = $request->input('order_dir', 'desc');

        $allowedColumns = ['fprno', 'fsupplier', 'fsuppliername', 'fprdate', 'fbranchcode', 'fneeddate'];
        if (in_array($orderColumn, $allowedColumns)) {
            if ($orderColumn === 'fprno' || $orderColumn === 'fprdate' || $orderColumn === 'fbranchcode' || $orderColumn === 'fneeddate') {
                $query->orderBy('tr_prh.' . $orderColumn, $orderDir);
            } elseif ($orderColumn === 'fsupplier' || $orderColumn === 'fsuppliername') {
                $query->orderBy('mssupplier.fsuppliername', $orderDir);
            }
        } else {
            $query->orderBy('tr_prh.fprdate', 'desc');
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
            'data' => $data,
        ]);
    }

    public function items($id)
    {
        $header = Tr_prh::where('fprhid', $id)->firstOrFail();

        $items = DB::table('tr_prd as d')
            ->leftJoin('msprd as m', 'm.fprdcode', '=', 'd.fprdcode')
            ->leftJoin(DB::raw('(
            SELECT
                frefdtno,
                fprdcode,
                frefnoacak,
                SUM(fqtykecil) AS fqtykecilpo
            FROM tr_pod
            GROUP BY frefdtno, fprdcode, frefnoacak
        ) as o'), function ($join) {
                $join->on('o.frefdtno', '=', 'd.fprno')
                    ->on('o.fprdcode', '=', 'd.fprdcode')
                    ->on('o.frefnoacak', '=', 'd.fnoacak');
            })
            ->where('d.fprno', $header->fprno)
            ->select([
                DB::raw('d.fprdcode::text as frefdtno'),
                'm.fprdcode as fitemcode',
                'm.fprdname as fitemname',
                'd.fqty',
                DB::raw('COALESCE(d.fqtykecil, 0) as fqtykecil_pr'),
                'd.fsatuan',
                'd.fdesc',
                'd.fketdt',
                'd.fprno',
                DB::raw('COALESCE(d.fprice, 0) as fprice'),
                DB::raw('0::numeric as fdisc'),
                DB::raw('d.fprno::text as fnouref'),
                DB::raw('d.fprdid::text as frefdtid'),
                DB::raw("COALESCE(d.fnoacak::text, '') as frefnoacak"),
                'm.fsatuankecil',
                'm.fsatuanbesar',
                'm.fsatuanbesar2',
                'm.fqtykecil',
                'm.fqtykecil2',
                DB::raw('COALESCE(o.fqtykecilpo, 0) AS fqtykecilpo'),
                DB::raw('GREATEST(COALESCE(d.fqtykecil, 0) - COALESCE(o.fqtykecilpo, 0), 0) AS fqtykecil_sisa'),
                DB::raw('COALESCE(
                    CASE
                        WHEN d.fsatuan = m.fsatuanbesar
                            THEN (COALESCE(d.fqtykecil, 0) - COALESCE(o.fqtykecilpo, 0)) / NULLIF(m.fqtykecil, 0)
                        WHEN d.fsatuan = m.fsatuanbesar2
                            THEN (COALESCE(d.fqtykecil, 0) - COALESCE(o.fqtykecilpo, 0)) / NULLIF(m.fqtykecil2, 0)
                        ELSE COALESCE(d.fqtykecil, 0) - COALESCE(o.fqtykecilpo, 0)
                    END, 0) AS fqtysisapr'),
                DB::raw('COALESCE(
                    CASE
                        WHEN d.fsatuan = m.fsatuanbesar
                            THEN COALESCE(o.fqtykecilpo, 0) / NULLIF(m.fqtykecil, 0)
                        WHEN d.fsatuan = m.fsatuanbesar2
                            THEN COALESCE(o.fqtykecilpo, 0) / NULLIF(m.fqtykecil2, 0)
                        ELSE COALESCE(o.fqtykecilpo, 0)
                    END, 0) AS fqtydipo'),
            ])
            ->orderBy('d.fprdcode')
            ->get()
            ->map(function ($item) use ($header) {
                $qty = (float) $item->fqty;
                $fqtypo = (float) ($item->fqtykecilpo ?? 0);
                $fqtysisapr = (float) ($item->fqtysisapr ?? 0);
                $fqtydipo = (float) ($item->fqtydipo ?? 0);
                $satuan = trim((string) $item->fsatuan);
                $satKecil = trim((string) ($item->fsatuankecil ?? ''));
                $satBesar = trim((string) ($item->fsatuanbesar ?? ''));
                $satBesar2 = trim((string) ($item->fsatuanbesar2 ?? ''));
                $rasio = (float) ($item->fqtykecil ?? 0);
                $rasio2 = (float) ($item->fqtykecil2 ?? 0);

                $sisaKecil = max(0, (float) ($item->fqtykecil_sisa ?? 0));

                return [
                    'frefdtno' => (string) $header->fprno,
                    'fitemcode' => $item->fitemcode,
                    'fitemname' => $item->fitemname,
                    'fqty' => $qty,
                    'maxqty' => $fqtysisapr,
                    'maxqty_satuan' => $satKecil,
                    'fsatuan' => $satuan,
                    'fdesc' => $item->fdesc ?? '',
                    'fketdt' => $item->fketdt ?? '',
                    'fprhid' => $header->fprhid,
                    'fprno' => $item->fprno ?? $header->fprno,
                    'fprice' => (float) $item->fprice,
                    'fdisc' => 0,
                    'fnouref' => $item->fnouref,
                    'frefdtid' => $item->frefdtid,
                    'frefnoacak' => (string) ($item->frefnoacak ?? ''),
                    'fqtypo' => $fqtypo,
                    'fqtysisapr' => $fqtysisapr,
                    'fqtydipo' => $fqtydipo,
                    'fqtykecil_ref' => $sisaKecil,
                    'fqtyremain' => $sisaKecil,
                    'fqtypr' => $qty,
                    'fsatuankecil' => $satKecil,
                    'fsatuanbesar' => $satBesar,
                    'fsatuanbesar2' => $satBesar2,
                    'fqtykecil' => $rasio,
                    'fqtykecil2' => $rasio2,
                ];
            });

        return response()->json([
            'header' => [
                'fprhid' => $header->fprhid,
                'fprno' => $header->fprno,
                'fsupplier' => trim($header->fsupplier ?? ''),
                'fprdate' => optional($header->fprdate)->format('Y-m-d H:i:s'),
            ],
            'items' => $items,
        ]);
    }

    /**
     * Konversi qty baris PO ke satuan kecil (sesuai logika PR).
     */
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

    private function normalizeReferenceRandomNumber($value, bool $hasReference): ?string
    {
        $value = trim((string) ($value ?? ''));

        if ($value !== '' && preg_match('/^\d{3}$/', $value)) {
            return $value;
        }

        return $hasReference ? null : null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rowsPod
     * @return array<int, float>
     */
    private function aggregatePrdUsageByPrd(array $rowsPod): array
    {
        $agg = [];
        foreach ($rowsPod as $r) {
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
            $messages["fitemcode.$index"] = "Kode produk {$code} tidak boleh sama dalam satu Order Pembelian.";
        }

        throw ValidationException::withMessages($messages);
    }

    /**
     * Hitung sisa PR dinamis dalam satuan kecil berdasarkan fqtykecil dikurangi pemakaian PO.
     *
     * @param  array<int, int|string>  $prDetailIds
     * @return array<int, float>
     */
    private function getPrRemainByDetailIds(array $prDetailIds): array
    {
        $ids = collect($prDetailIds)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            return [];
        }

        $poUsageSub = DB::table('tr_pod')
            ->selectRaw('CAST(frefdtid AS INTEGER) AS fprdid, SUM(COALESCE(fqtykecil, 0)) AS fqtykecilpo')
            ->whereNotNull('frefdtid')
            ->groupByRaw('CAST(frefdtid AS INTEGER)');

        return DB::table('tr_prd as d')
            ->leftJoinSub($poUsageSub, 'po', function ($join) {
                $join->on('po.fprdid', '=', 'd.fprdid');
            })
            ->whereIn('d.fprdid', $ids)
            ->selectRaw('d.fprdid, GREATEST(COALESCE(d.fqtykecil, 0) - COALESCE(po.fqtykecilpo, 0), 0) AS remain_kecil')
            ->pluck('remain_kecil', 'd.fprdid')
            ->map(fn($value) => (float) $value)
            ->all();
    }

    /**
     * @param  array<int, float|int>  $usageByRef
     */
    private function adjustPrReferenceQtyKecil(array $usageByRef, int $direction): void
    {
        // Pemakaian PR dari PO tidak lagi mengurangi / mengembalikan tr_prd.fqtykecil.
    }

    /**
     * Validasi sisa PR dinamis per baris detail PR.
     *
     * @param  array<int, float>  $aggregateByPrd
     * @param  array<int, float>  $extraAvailableByPrd
     */
    private function validatePrdRemain(array $aggregateByPrd, array $extraAvailableByPrd = []): void
    {
        if (empty($aggregateByPrd)) {
            return;
        }

        $prMetaMap = DB::table('tr_prd as d')
            ->leftJoin('tr_prh as h', 'h.fprno', '=', 'd.fprno')
            ->leftJoin('msprd as p', 'p.fprdcode', '=', 'd.fprdcode')
            ->whereIn('d.fprdid', array_keys($aggregateByPrd))
            ->get([
                'd.fprdid',
                'd.fprno',
                'd.fprdcode',
                'd.fsatuan',
                'p.fsatuankecil',
                'p.fsatuanbesar',
                'p.fsatuanbesar2',
                'p.fqtykecil',
                'p.fqtykecil2',
            ])
            ->keyBy('fprdid');

        $remainMap = $this->getPrRemainByDetailIds(array_keys($aggregateByPrd));
        $tolerance = 0.00001;

        foreach ($aggregateByPrd as $prDetailId => $qtyKecilNeed) {
            $needKecil = (float) $qtyKecilNeed;
            if ($needKecil <= 0) {
                continue;
            }

            $remainKecil = (float) ($remainMap[(int) $prDetailId] ?? 0);
            $extraKecil = (float) ($extraAvailableByPrd[(int) $prDetailId] ?? 0);
            $availableKecil = $remainKecil + $extraKecil;

            if ($needKecil > $availableKecil + $tolerance) {
                $meta = $prMetaMap->get((int) $prDetailId);
                $prNo = trim((string) ($meta->fprno ?? ''));
                $prdCode = trim((string) ($meta->fprdcode ?? ''));
                $satuan = trim((string) ($meta->fsatuan ?? ''));
                $satBesar = trim((string) ($meta->fsatuanbesar ?? ''));
                $satBesar2 = trim((string) ($meta->fsatuanbesar2 ?? ''));
                $rasio = (float) ($meta->fqtykecil ?? 0);
                $rasio2 = (float) ($meta->fqtykecil2 ?? 0);
                $parts = array_filter([
                    $prNo !== '' ? "PR {$prNo}" : null,
                    $prdCode !== '' ? "Produk {$prdCode}" : null,
                    "Detail ID {$prDetailId}",
                ]);
                $label = implode(' / ', $parts);
                $availableInPrUnit = $availableKecil;
                if ($satuan !== '' && strcasecmp($satuan, $satBesar) === 0 && $rasio > 0) {
                    $availableInPrUnit = $availableKecil / $rasio;
                } elseif ($satuan !== '' && strcasecmp($satuan, $satBesar2) === 0 && $rasio2 > 0) {
                    $availableInPrUnit = $availableKecil / $rasio2;
                }
                $availableInPrUnitText = rtrim(rtrim(number_format($availableInPrUnit, 4, '.', ''), '0'), '.');
                $availableKecilText = rtrim(rtrim(number_format($availableKecil, 4, '.', ''), '0'), '.');

                throw new \RuntimeException(
                    "Qty PR melebihi batas pada {$label}. Maksimal {$availableKecilText} dalam satuan kecil"
                        . ($satuan !== '' ? " atau {$availableInPrUnitText} {$satuan}" : '')
                        . ", berdasarkan total pemakaian PO."
                );
            }
        }
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

        $prefix = sprintf('PO.%s.%s.%s.', $kodeCabang, $date->format('Y'), $date->format('m'));

        // kunci per (branch, tahun-bulan) — TANPA bikin tabel baru
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

    public function print(string $fpono)
    {
        $supplierTable = (new Supplier)->getTable();

        $hdr = Tr_poh::query()
            ->leftJoin("{$supplierTable} as s", 's.fsuppliercode', '=', 'tr_poh.fsupplier')
            ->leftJoin('mscabang as c', 'c.fcabangkode', '=', 'tr_poh.fbranchcode')
            ->where('tr_poh.fpono', $fpono)
            ->first([
                'tr_poh.*',
                's.fsuppliername as supplier_name',
                's.faddress as supplier_address',
                'c.fcabangname as cabang_name',
            ]);

        if (! $hdr) {
            return redirect()->back()->with('error', 'PO tidak ada.');
        }

        $dt = DB::table('tr_pod')
            ->leftJoin('msprd as p', function ($j) {
                $j->on('p.fprdid', '=', DB::raw('CAST(tr_pod.fprdid AS INTEGER)'));
            })
            ->where('tr_pod.fpono', $hdr->fpono)
            ->orderBy('tr_pod.fnou')
            ->get([
                'tr_pod.*',
                'p.fprdcode as product_code',
                'p.fprdname as product_name',
            ]);

        // Hitung totals — jika fincludeppn = 1 baru tambah PPN
        $subtotal = $dt->sum('famount');
        $ppnPersen = (float) ($hdr->fppnpersen ?? 11);
        $ppnAmount = $hdr->fincludeppn == '1' ? round($subtotal * $ppnPersen / 100, 2) : 0;
        $grandTotal = round($subtotal + $ppnAmount, 2);

        $fmt = fn($d) => $d
            ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d F Y')
            : '-';

        return view('tr_poh.print', [
            'hdr' => $hdr,
            'dt' => $dt,
            'displayFpono' => $this->formatDisplayTransactionNumber($hdr->fpono ?? null, (int) ($hdr->fapplyppn ?? 0) === 1),
            'fmt' => $fmt,
            'subtotal' => $subtotal,
            'ppnPersen' => $ppnPersen,
            'ppnAmount' => $ppnAmount,
            'grandTotal' => $grandTotal,
            'company_name' => config('app.company_name', 'PT. DEMO VERSION'),
            'company_city' => config('app.company_city', 'Tangerang'),
        ]);
    }

    public function lastPrice(Request $request)
    {
        $fprdcode = trim($request->input('fprdcode', ''));
        $fsupplier = trim($request->input('fsupplier', ''));
        $fsatuan = trim($request->input('fsatuan', ''));

        if (! $fprdcode || ! $fsupplier || ! $fsatuan) {
            return response()->json(['fprice' => 0, 'fdisc' => 0]);
        }

        $row = DB::table('tr_poh as m')
            ->join('tr_pod as d', 'm.fpono', '=', 'd.fpono')
            ->whereRaw('trim(d.fprdcode) = ?', [$fprdcode])
            ->whereRaw('trim(m.fsupplier::text) = ?', [$fsupplier])
            ->whereRaw('trim(d.fsatuan) = ?', [$fsatuan])
            ->orderBy('m.fpodate', 'desc')
            ->select('d.fprice', 'd.fdisc')
            ->first();

        return response()->json([
            'found' => (bool) $row,
            'fprice' => $row ? (float) $row->fprice : 0,
            'fdisc' => $row ? (float) $row->fdisc : 0,
        ]);
    }

    public function create(Request $request)
    {
        $suppliers = Supplier::orderBy('fsuppliername', 'asc')
            ->get(['fsupplierid', 'fsuppliercode', 'fsuppliername', 'ftempo', 'fcurr']);

        $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

        $branch = DB::table('mscabang')
            ->when(is_numeric($raw), fn($q) => $q->where('fcabangid', (int) $raw))
            ->when(
                ! is_numeric($raw),
                fn($q) => $q->where('fcabangkode', $raw)->orWhere('fcabangname', $raw)
            )
            ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

        $canApproval = $this->canApprovePurchaseOrder();

        $fcabang = $branch->fcabangname ?? (string) $raw;
        $fbranchcode = $branch->fcabangkode ?? (string) $raw;

        $newtr_prh_code = $this->generatetr_poh_Code(now(), $fbranchcode);

        $currencies = DB::table('mscurrency')
            ->where(function ($q) {
                $q->whereNull('fnonactive')->orWhere('fnonactive', '0')->orWhere('fnonactive', '');
            })
            ->orderBy('fcurrname')
            ->get(['fcurrid', 'fcurrcode', 'fcurrname', 'frate']);

        $products = $this->browseProducts();

        return view('tr_poh.create', [
            'newtr_prh_code' => $newtr_prh_code,
            'perms' => ['can_approval' => $canApproval],
            'suppliers' => $suppliers,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'products' => $products,
            'currencies' => $currencies,
            'filterSupplierId' => $request->query('filter_supplier_id'),
        ]);
    }

    public function store(Request $request)
    {
        // VALIDATION
        $validator = Validator::make($request->all(), [
            'fpohid' => ['nullable', 'string', 'max:25'],
            'fpodate' => ['required', 'date'],
            'fkirimdate' => ['nullable', 'date'],
            'fsupplier' => ['required', 'string', 'max:30'],
            'fincludeppn' => ['nullable'],
            'fket' => ['nullable', 'string', 'max:300'],
            'fbranchcode' => ['nullable', 'string', 'max:20'],
            'ftempohr' => ['nullable', 'string', 'max:3'],

            'fitemcode' => ['required', 'array', 'min:1'],
            'fitemcode.*' => ['required', 'string', 'max:50'],

            'fsatuan' => ['nullable', 'array'],
            'fsatuan.*' => ['nullable', 'string', 'max:20'],

            'fapproval' => ['nullable'],

            'frefdtno' => ['nullable'],
            'frefdtno.*' => ['nullable'],

            'fnoacak' => ['nullable', 'array'],
            'fnoacak.*' => ['nullable', 'regex:/^[1-9]{3}$/'],
            'frefnoacak' => ['nullable', 'array'],
            'frefnoacak.*' => ['nullable', 'regex:/^\d{3}$/'],

            'fqty' => ['required', 'array'],
            'fqty.*' => ['numeric', 'gt:0', 'min:0'],

            'fprice' => ['nullable', 'array'],
            'fprice.*' => ['numeric', 'min:0'],

            'fdisc' => ['nullable', 'array'],
            'fdisc.*' => ['nullable', 'string', 'regex:/^\s*\d+(?:\.\d+)?(?:\s*\+\s*\d+(?:\.\d+)?)*\s*$/'],

            'frefpr' => ['nullable', 'array'],
            'frefpr.*' => ['nullable', 'string', 'max:30'],

            'fdesc' => ['nullable', 'array'],
            'fdesc.*' => ['nullable', 'string', 'max:500'],

            'ppn_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'fpodate.required' => 'Tanggal PO wajib diisi.',
            'fsupplier.required' => 'Supplier wajib diisi.',
            'fitemcode.required' => 'Minimal 1 item.',
            'fqty.*.gt' => 'Hapus baris atau isi qty. Qty tidak boleh 0.',
            'fnoacak.*.regex' => 'No. acak PO harus 3 digit 1-9.',
            'frefnoacak.*.regex' => 'No. referensi acak harus 3 digit.',
            'fdisc.*.regex' => 'Format diskon item harus angka atau format seperti 10+2.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $this->ensureNoDuplicateDetailCodes($request->input('fitemcode', []));

        // HEADER VALUES
        $fpodate = Carbon::parse($request->fpodate)->startOfDay();
        $this->ensureCreateDateWithinEditPeriod($fpodate);
        $fkirimdate = $request->filled('fkirimdate') ? Carbon::parse($request->fkirimdate)->startOfDay() : null;
        $fpohid = $request->input('fpohid'); // can be null; we will generate if empty
        $fincludeppn = $request->boolean('fincludeppn') ? 1 : 0;
        $fapplyppn = $request->boolean('fapplyppn') ? 1 : 0;
        $userid = auth('sysuser')->user()->fname ?? 'admin';
        $now = now();

        // DETAIL ARRAYS
        $codes = $request->input('fitemcode', []);
        $satuans = $request->input('fsatuan', []);
        $refdtno = $request->input('frefdtno', []);
        $frefdtids = $request->input('frefdtid', []);
        $fnoacaks = $request->input('fnoacak', []);
        $frefnoacaks = $request->input('frefnoacak', []);
        $qtys = $request->input('fqty', []);
        $prices = $request->input('fprice', []);
        $discs = $request->input('fdisc', []);
        $refprs = $request->input('frefpr', []);
        $descs = $request->input('fdesc', []);

        // TOTALS (from frontend)
        $totalHarga = (float) $request->input('famountponet', 0);
        $ppnAmount = (float) $request->input('famountpopajak', 0);
        $grandTotal = (float) $request->input('famountpo', 0);

        // Load product metadata: map code -> (fprdid, satuans)
        $uniqueCodes = array_values(array_unique(array_filter(array_map(fn($c) => trim((string) $c), $codes))));
        $prodMeta = DB::table('msprd')
            ->whereIn('fprdcode', $uniqueCodes)
            ->get(['fprdid', 'fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'])
            ->keyBy('fprdcode');

        $pickDefaultSat = function (string $code) use ($prodMeta): string {
            $m = $prodMeta[$code] ?? null;
            if (! $m) {
                return '';
            }
            foreach (['fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'] as $k) {
                $v = trim((string) ($m->$k ?? ''));
                if ($v !== '') {
                    return mb_substr($v, 0, 5);
                }
            }

            return '';
        };

        // BUILD DETAIL ROWS (use fprdid, not fprdid)
        $rowsPod = [];
        $totalHarga = 0.0; // recompute to be safe
        $rowCount = max(count($codes), count($satuans), count($refdtno), count($frefdtids), count($fnoacaks), count($frefnoacaks), count($qtys), count($prices), count($discs), count($refprs), count($descs));
        $usedNoAcaks = [];

        for ($i = 0; $i < $rowCount; $i++) {
            $code = trim($codes[$i] ?? '');
            $sat = trim((string) ($satuans[$i] ?? ''));
            $refdt = trim((string) ($refdtno[$i] ?? ''));
            $qty = (float) ($qtys[$i] ?? 0);
            $price = (float) ($prices[$i] ?? 0);
            $discRaw = $this->normalizeDiscountInput($discs[$i] ?? 0);
            $discP = $this->parseDiscountExpression($discRaw);
            $desc = (string) ($descs[$i] ?? '');
            $frefdtid = (int) ($frefdtids[$i] ?? 0);
            $fnoacak = $this->normalizeRandomNumber($fnoacaks[$i] ?? null, $usedNoAcaks);
            $frefnoacak = $this->normalizeReferenceRandomNumber($frefnoacaks[$i] ?? null, $frefdtid > 0);

            if ($code === '' || $qty <= 0) {
                continue;
            }

            if ($sat === '') {
                $sat = $pickDefaultSat($code);
            }
            if ($frefdtid > 0) {
                $prUnit = DB::table('tr_prd')
                    ->where('fprdid', $frefdtid)
                    ->value('fsatuan');
                if ($prUnit !== null) {
                    $sat = trim($prUnit);
                }
            }
            $sat = mb_substr($sat, 0, 20);
            if ($sat === '') {
                continue;
            }

            $productId = (int) (($prodMeta[$code]->fprdid ?? null) ?? 0);
            if ($productId === 0) {
                continue;
            }

            $product = DB::table('msprd')
                ->where('fprdcode', $code)
                ->select('fprdid', 'fprdcode', 'fsatuanbesar', 'fsatuanbesar2', 'fqtykecil', 'fqtykecil2')
                ->first();

            $qtyKecil = $this->qtyPoToKecil($product, $sat, $qty);

            $priceGross = $price;
            $priceNet = $priceGross * (1 - ($discP / 100));
            $amount = $qty * $priceNet;

            $totalHarga += $amount;

            $rowsPod[] = [
                'fprdid' => $productId,   // <-- integer FK to msprd.fprdid
                'fprdcode' => $product->fprdcode ?? '',   // <-- integer FK to msprd.fprdid
                'fqty' => $qty,
                'fdisc' => $discRaw,
                'fprice' => $price,
                'fprice_rp' => $price,
                'fpricegross' => $priceGross,
                'fpricenet' => $priceNet,
                'famount' => $amount,
                'famount_rp' => $amount,
                'fusercreate' => $userid,
                'fdatetime' => $now,
                'fsatuan' => $sat,
                'frefdtno' => $refdt,
                'fdesc' => $desc,
                'fqtykecil' => $qtyKecil,
                'fqtyremain' => $qtyKecil,
                'frefdtid' => $frefdtid ?: null,
                'fnoacak' => $fnoacak,
                'frefnoacak' => $frefnoacak,
            ];
        }

        if (empty($rowsPod)) {
            return back()->withInput()->withErrors(['detail' => 'Minimal satu item valid (Kode, Satuan, Qty > 0).']);
        }

        if ($validationMessage = $this->validateUniqueReferenceUsage($rowsPod)) {
            return back()->withInput()->withErrors(['detail' => $validationMessage]);
        }

        $prdAgg = $this->aggregatePrdUsageByPrd($rowsPod);

        // TRANSACTION
        $fpono = null;
        try {
            DB::transaction(function () use (
                $request,
                $fpodate,
                $fkirimdate,
                $fincludeppn,
                $fapplyppn,
                $userid,
                $now,
                $rowsPod,
                $fpohid,
                $totalHarga,
                $ppnAmount,
                $grandTotal,
                $prdAgg,
                &$fpono
            ) {
                $this->validatePrdRemain($prdAgg);

                // Generate human code if not provided
                if (empty($fpohid)) {
                    $rawBranch = $request->input('fbranchcode');
                    $kodeCabang = null;
                    if ($rawBranch !== null) {
                        $needle = trim((string) $rawBranch);
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

                    $yy = $fpodate->format('Y');
                    $mm = $fpodate->format('m');
                    $prefix = sprintf('PO.%s.%s.%s.', $kodeCabang, $yy, $mm);

                    // advisory lock per (branch, y-m)
                    $lockKey = crc32('PO|' . $kodeCabang . '|' . $fpodate->format('Y-m'));
                    DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

                    // get last sequence under this prefix
                    $last = DB::table('tr_poh')
                        ->where('fpono', 'like', $prefix . '%')
                        ->selectRaw("MAX(CAST(split_part(fpono, '.', 5) AS int)) AS lastno")
                        ->value('lastno');

                    $next = (int) $last + 1;
                    $fpohid = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
                }

                $fcurrency = $request->input('fcurrency', 'IDR');
                $frate = $request->input('frate', 15500);
                $ftempohr = $request->input('ftempohr', 0);
                $isApproval = $this->canApprovePurchaseOrder() ? (int) ($request->input('fapproval', 0)) : 0;

                // INSERT HEADER and GET fpohid
                $fpohid = DB::table('tr_poh')->insertGetId([
                    'fpono' => $fpohid,     // human-readable code stays in header
                    'fpodate' => $fpodate,
                    'fkirimdate' => $fkirimdate,
                    'fcurrency' => $fcurrency,
                    'ftempohr' => $ftempohr,
                    'frate' => $frate,
                    'fbranchcode' => $request->fbranchcode,
                    'fsupplier' => $request->input('fsupplier'),
                    'fincludeppn' => $fincludeppn,
                    'fapplyppn' => $fapplyppn,
                    'fket' => $request->input('fket'),
                    'fusercreate' => $userid,
                    'fdatetime' => $now,
                    'famountponet' => round($totalHarga, 2),
                    'famountpopajak' => $ppnAmount,
                    'famountpo' => $grandTotal,
                    'fapproval' => $isApproval,
                    'fppnpersen' => $request->input('ppn_rate', 0),
                    'fclose' => '0',
                    'fprdin' => '0',
                ], 'fpohid');

                $fpono = DB::table('tr_poh')->where('fpohid', $fpohid)->value('fpono');

                // EMAIL after commit — use fpohid and fprdid
                if ($isApproval === 1) {
                    DB::afterCommit(function () use ($fpohid) {
                        $hdr = Tr_poh::where('fpohid', $fpohid)->first();

                        $dt = Tr_pod::from('tr_pod as d')
                            ->leftJoin('msprd as p', 'p.fprdid', '=', 'd.fprdid')  // <-- FK to msprd.fprdid
                            ->where('d.fpono', $hdr->fpono)
                            ->orderBy('p.fprdname')
                            ->get([
                                'd.*',
                                'p.fprdname as product_name',
                                'p.fminstock as stock',
                            ]);

                        $productName = $dt->pluck('product_name')->implode(', ');
                        $approver = auth('sysuser')->user()->fname ?? '-';

                        $approvalRecipients = $this->getApprovalRecipients();
                        if ($approvalRecipients !== []) {
                            Mail::to($approvalRecipients[0])
                                ->cc(array_slice($approvalRecipients, 1))
                                ->send(new ApprovalEmailPo($hdr, $dt, $productName, $approver, 'Order Pembelian (PO)'));
                        }
                    });
                }

                // numbering + insert details — use fpono
                $lastNou = (int) DB::table('tr_pod')->where('fpono', $fpono)->max('fnou');
                $nextNou = $lastNou + 1;

                foreach ($rowsPod as &$r) {
                    $r['fnou'] = $nextNou++;
                    $r['fpono'] = $fpono;
                }
                unset($r);

                DB::table('tr_pod')->insert($rowsPod);
                $this->adjustPrReferenceQtyKecil($prdAgg, -1);
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['detail' => $e->getMessage()]);
        }

        return redirect()
            ->route('tr_poh.create')
            ->with('success', 'Order pembelian ' . $this->formatDisplayTransactionNumber($fpono, $fapplyppn === 1) . ' berhasil disimpan.');
    }

    public function edit(Request $request, $fpohid)
    {
        $suppliers = Supplier::orderBy('fsuppliername', 'asc')
            ->get(['fsupplierid', 'fsuppliercode', 'fsuppliername', 'ftempo', 'fcurr']);

        $currencies = DB::table('mscurrency')
            ->where(function ($q) {
                $q->whereNull('fnonactive')
                    ->orWhere('fnonactive', '0')
                    ->orWhere('fnonactive', '');
            })
            ->orderBy('fcurrname')
            ->get(['fcurrid', 'fcurrcode', 'fcurrname', 'frate']);

        $tr_poh = Tr_poh::with(['details' => function ($q) {
            $q->leftJoin('msprd as m', 'm.fprdid', '=', 'tr_pod.fprdid')
                ->leftJoin(DB::raw('(
            SELECT 
                frefdtno,           
                fprdcode,           
                SUM(fqty) AS total_terima
            FROM trstockdt
            WHERE 
                (fstockmtcode = \'TER\' OR (fcode = \'P\' AND fstockmtcode = \'BUY\'))
                AND frefdtno IS NOT NULL 
            GROUP BY 
                frefdtno, fprdcode  
        ) as r'), function ($join) {
                    $join->on('r.frefdtno', '=', 'tr_pod.frefdtno')
                        ->on('r.fprdcode', '=', 'm.fprdcode');
                })
                ->select(
                    'tr_pod.*',
                    'm.fprdcode as fitemcode',
                    'm.fprdname',
                    'm.fsatuankecil',
                    'm.fsatuanbesar',
                    'm.fsatuanbesar2',
                    DB::raw('COALESCE(m.fqtykecil, 0) as fqtykecil_master'),
                    DB::raw('COALESCE(m.fqtykecil2, 0) as fqtykecil2_master'),
                    DB::raw('COALESCE((SELECT pr.fqty FROM tr_prd pr WHERE tr_pod.frefdtid IS NOT NULL AND pr.fprdid = CAST(tr_pod.frefdtid AS INTEGER) LIMIT 1), 0) as fqtypr'),
                    DB::raw("COALESCE((SELECT pr.fsatuan FROM tr_prd pr WHERE tr_pod.frefdtid IS NOT NULL AND pr.fprdid = CAST(tr_pod.frefdtid AS INTEGER) LIMIT 1), '') as fqtypr_satuan"),
                    DB::raw('COALESCE(r.total_terima, 0) AS fqtyterima')
                );
        }])->findOrFail($fpohid);

        if ($message = $this->getPostedPeriodLockMessage($tr_poh->fpodate, 'Data ini')) {
            return redirect()
                ->route('tr_poh.view', $tr_poh->fpohid)
                ->with('error', $message);
        }
        ['fcabang' => $fcabang, 'fbranchcode' => $fbranchcode] = $this->resolveBranchContext($tr_poh->fbranchcode ?? null);
        $details = $this->getPoDetailsWithTerimaUsage($tr_poh->fpono);

        $existingTerima = DB::table('trstockdt')
            ->where('frefdtno', $tr_poh->fpono)
            ->select(
                'fstockmtno',
                'fdatetime',
                DB::raw('SUM(fqty) as total_qty')
            )
            ->groupBy('fstockmtno', 'fdatetime')
            ->orderBy('fdatetime', 'desc')
            ->get();

        $blockedByTerima = $existingTerima->isNotEmpty();

        if ($blockedByTerima) {
            return redirect()
                ->route('tr_poh.view', $tr_poh->fpohid)
                ->with('error', $this->getUsageLockMessage($tr_poh));
        }

        // Lookup currency berdasarkan fcurrency (currency code) di tr_poh
        $currentCurrency = DB::table('mscurrency')
            ->where('fcurrcode', $tr_poh->fcurrency)
            ->first(['fcurrid', 'fcurrcode', 'fcurrname', 'frate']);

        $products = $this->browseProducts();
        $productMap = $this->browseProductMap($products, 'fprdid');

        $oldUsageByRef = $details
            ->groupBy(fn($d) => (int) ($d->frefdtid ?? 0))
            ->map(fn($rows) => (float) $rows->sum(fn($r) => (float) ($r->fqtykecil ?? 0)))
            ->all();

        $prRemainMap = $this->getPrRemainByDetailIds($details->pluck('frefdtid')->all());

        $savedItems = $details->map(function ($d) use ($products, $oldUsageByRef, $prRemainMap) {
            $qtyPR = (float) $d->fqtypr;
            $satPR = trim((string) $d->fqtypr_satuan);
            $satKecil = trim((string) $d->fsatuankecil);
            $satBesar = trim((string) $d->fsatuanbesar);
            $satBesar2 = trim((string) $d->fsatuanbesar2);
            $rasio = (float) ($d->fqtykecil_master ?? 0);
            $rasio2 = (float) ($d->fqtykecil2_master ?? 0);

            $prod = $products->firstWhere('fprdcode', $d->fitemcode);

            $refId = (int) ($d->frefdtid ?? 0);
            $sisaKecil = max(0, (float) ($prRemainMap[$refId] ?? 0) + (float) ($oldUsageByRef[$refId] ?? 0));

            // Siapkan units untuk dropdown
            $units = array_values(array_filter(array_map('trim', [$satKecil, $satBesar, $satBesar2])));
            $fsatuan = trim((string) $d->fsatuan);
            if ($fsatuan !== '' && ! in_array($fsatuan, $units)) {
                array_unshift($units, $fsatuan);
            }
            // Pastikan fsatuan ada di units
            $matchedSatuan = $fsatuan;
            foreach ($units as $u) {
                if (strtolower(trim($u)) === strtolower($fsatuan)) {
                    $matchedSatuan = trim($u); // ← pakai versi dari master (sudah di-trim, case benar)
                    break;
                }
            }

            $fsatuan = $matchedSatuan;

            if ($fsatuan !== '' && ! in_array($fsatuan, $units)) {
                array_unshift($units, $fsatuan);  // ← tetap ada walau tidak di master
            }

            return [
                'uid' => (string) ($d->fpodid ?? \Illuminate\Support\Str::uuid()),
                'fitemcode' => (string) ($d->fitemcode ?? ''),
                'fitemname' => (string) ($d->fprdname ?? ''),
                'fsatuan' => $fsatuan,   // ← pakai yang sudah di-trim
                'units' => $units,     // ← sudah include fsatuan
                'frefdtno' => (string) ($d->frefdtno ?? ''),
                'fpono' => (string) ($d->fpono ?? ''),
                'fnouref' => (string) ($d->fnouref ?? ''),
                'frefpr' => (string) ($d->fprhid ?? ''),
                'fprhid' => (string) ($d->fprhid ?? ''),
                'fprno' => (string) ($d->frefdtno ?? ''),
                'fqty' => (float) ($d->fqty ?? 0),
                'fqtyterima' => (float) ($d->fqtyterima ?? 0),
                'fterima' => (float) ($d->fterima ?? 0),
                'fprice' => (float) ($d->fprice ?? 0),
                'fdisc' => $this->normalizeDiscountInput($d->fdisc ?? 0),
                'ftotal' => (float) ($d->famount ?? 0),
                'fdesc' => (string) ($d->fdesc ?? ''),
                'fketdt' => (string) ($d->fketdt ?? ''),
                'frefdtid' => (string) ($d->frefdtid ?? ''), // PASTIKAN INI ADA
                'fnoacak' => (string) ($d->fnoacak ?? ''),
                'frefnoacak' => (string) ($d->frefnoacak ?? ''),
                // Data konversi untuk JavaScript
                'fqtypo' => (float) ($d->fqtypo ?? 0),
                'fqtykecil_ref' => $sisaKecil,
                'fqtyremain' => $sisaKecil,
                'fqtypr' => $qtyPR,
                'fqtypr_satuan' => $satPR,
                'fsatuankecil' => $satKecil,
                'fsatuanbesar' => $satBesar,
                'fsatuanbesar2' => $satBesar2,
                'fqtykecil' => $rasio,
                'fqtykecil2' => $rasio2,

                // maxqty dikirim dalam satuan terkecil (Base)
                'maxqty' => $sisaKecil,
                'maxqty_satuan' => $satKecil,
            ];
        })->values();

        return view('tr_poh.edit', [
            'suppliers' => $suppliers,
            'selectedSupplierCode' => $tr_poh->fsupplier,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'products' => $products,
            'existingTerima' => $existingTerima,
            'blockedByTerima' => $blockedByTerima,
            'usageLockMessage' => $blockedByTerima ? $this->getUsageLockMessage($tr_poh) : null,
            'productMap' => $productMap,
            'tr_poh' => $tr_poh,
            'displayFpono' => $this->formatDisplayTransactionNumber($tr_poh->fpono ?? null, (int) ($tr_poh->fapplyppn ?? 0) === 1),
            'savedItems' => $savedItems,
            'currencies' => $currencies,
            'currentCurrency' => $currentCurrency,   // <-- currency aktif dari join
            'ppnAmount' => (float) ($tr_poh->famountpopajak ?? 0),
            'famountponet' => (float) ($tr_poh->famountponet ?? 0),
            'famountpo' => (float) ($tr_poh->famountpo ?? 0),
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'action' => 'edit',
        ]);
    }

    public function view(Request $request, $fpohid)
    {
        $suppliers = Supplier::orderBy('fsuppliername', 'asc')
            ->get(['fsupplierid', 'fsuppliercode', 'fsuppliername', 'ftempo', 'fcurr']);

        $tr_poh = Tr_poh::with(['details' => function ($q) {
            $q->leftJoin('msprd', function ($j) {
                $j->on('msprd.fprdid', '=', 'tr_pod.fprdid');
            })
                ->leftJoin(DB::raw('(SELECT frefdtno, fprdcode, SUM(fqty) AS total_terima FROM trstockdt GROUP BY frefdtno, fprdcode) r'), function ($join) {
                    $join->on('r.frefdtno', '=', 'tr_pod.fpono')
                        ->on('r.fprdcode', '=', 'msprd.fprdcode');
                })
                ->select(
                    'tr_pod.*',
                    'msprd.fprdcode as fitemcode',
                    'msprd.fprdname',
                    'msprd.fsatuankecil',
                    'msprd.fsatuanbesar',
                    'msprd.fsatuanbesar2',
                    DB::raw('COALESCE(r.total_terima, 0) AS fqtyterima')
                );
        }])->findOrFail($fpohid);
        ['fcabang' => $fcabang, 'fbranchcode' => $fbranchcode] = $this->resolveBranchContext($tr_poh->fbranchcode ?? null);
        $details = $this->getPoDetailsWithTerimaUsage($tr_poh->fpono);

        $currencies = DB::table('mscurrency')
            ->where(function ($q) {
                $q->whereNull('fnonactive')
                    ->orWhere('fnonactive', '0')
                    ->orWhere('fnonactive', '');
            })
            ->orderBy('fcurrname')
            ->get(['fcurrid', 'fcurrcode', 'fcurrname', 'frate']);

        // Lookup currency aktif dari mscurrency berdasarkan fcurrency (currency code)
        $currentCurrency = DB::table('mscurrency')
            ->where('fcurrcode', $tr_poh->fcurrency)
            ->first(['fcurrid', 'fcurrcode', 'fcurrname', 'frate']);

        $products = $this->browseProducts();
        $productMap = $this->browseProductMap($products);

        $savedItems = $details->map(function ($d) {
            $satKecil = trim((string) ($d->fsatuankecil ?? ''));
            $satBesar = trim((string) ($d->fsatuanbesar ?? ''));
            $satBesar2 = trim((string) ($d->fsatuanbesar2 ?? ''));
            $units = array_values(array_filter(array_map('trim', [$satKecil, $satBesar, $satBesar2])));
            $fsatuan = trim((string) ($d->fsatuan ?? ''));

            if ($fsatuan !== '' && ! in_array($fsatuan, $units)) {
                array_unshift($units, $fsatuan);
            }

            return [
                'uid' => (string) ($d->fpodid ?? \Illuminate\Support\Str::uuid()),
                'fitemcode' => (string) ($d->fitemcode ?? ''),
                'fitemname' => (string) ($d->fprdname ?? ''),
                'fsatuan' => $fsatuan,
                'units' => $units,
                'frefdtno' => (string) ($d->frefdtno ?? ''),
                'fprno' => (string) ($d->frefdtno ?? ''),
                'fqty' => (float) ($d->fqty ?? 0),
                'fqtyterima' => (float) ($d->fqtyterima ?? 0),
                'fterima' => (float) ($d->fterima ?? 0),
                'fprice' => (float) ($d->fprice ?? 0),
                'fdisc' => $this->normalizeDiscountInput($d->fdisc ?? 0),
                'ftotal' => (float) ($d->famount ?? 0),
                'fdesc' => (string) ($d->fdesc ?? ''),
                'fketdt' => (string) ($d->fketdt ?? ''),
            ];
        })->values();

        return view('tr_poh.edit', [
            'suppliers' => $suppliers,
            'selectedSupplierCode' => $tr_poh->fsupplier,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'existingTerima' => collect(),
            'blockedByTerima' => false,
            'usageLockMessage' => null,
            'products' => $products,
            'productMap' => $productMap,
            'tr_poh' => $tr_poh,
            'displayFpono' => $this->formatDisplayTransactionNumber($tr_poh->fpono ?? null, (int) ($tr_poh->fapplyppn ?? 0) === 1),
            'savedItems' => $savedItems,
            'currencies' => $currencies,
            'currentCurrency' => $currentCurrency,
            'ppnAmount' => (float) ($tr_poh->famountpopajak ?? 0),
            'famountponet' => (float) ($tr_poh->famountponet ?? 0),
            'famountpo' => (float) ($tr_poh->famountpo ?? 0),
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'action' => 'view',
        ]);
    }

    public function update(Request $request, $fpohid)
    {
        $header = Tr_poh::where('fpohid', $fpohid)->firstOrFail();

        if ($message = $this->getPostedPeriodLockMessage($header->fpodate, 'Data ini')) {
            return redirect()->route('tr_poh.view', $header->fpohid)->with('error', $message);
        }
        $isCloseOnly = $request->boolean('close_only');
        $canClosePo = $isCloseOnly
            && $request->has('fclose')
            && trim((string) ($header->fprdin ?? '')) !== '1';

        if ($message = $this->getUsageLockMessage($header)) {
            if (! $canClosePo) {
                return redirect()->route('tr_poh.index')->with('error', $message);
            }
        }

        if ($isCloseOnly) {
            if (! $canClosePo) {
                return back()->withInput()->with('error', 'Status close PO tidak bisa diupdate. FPRDIN tidak boleh = 1.');
            }

            Tr_poh::where('fpohid', $header->fpohid)->update([
                'fclose' => '1',
                'fuserupdate' => (Auth::guard('sysuser')->user()?->fname ?? Auth::user()?->fname ?? 'system'),
                'fupdatedat' => now(),
            ]);

            return redirect()
                ->route('tr_poh.index')
                ->with('success', 'Status close PO ' . $this->formatDisplayTransactionNumber($header->fpono, (int) ($header->fapplyppn ?? 0) === 1) . ' berhasil diupdate.');
        }

        $validator = Validator::make($request->all(), [
            'fpodate' => ['required', 'date'],
            'fkirimdate' => ['nullable', 'date'],
            'fsupplier' => ['required', 'string', 'max:30'],
            'fincludeppn' => ['nullable'],
            'fket' => ['nullable', 'string', 'max:300'],
            'fbranchcode' => ['nullable', 'string', 'max:20'],
            'fitemcode' => ['required', 'array', 'min:1'],
            'fitemcode.*' => ['required', 'string', 'max:50'],
            'fsatuan' => ['nullable', 'array'],
            'fsatuan.*' => ['nullable', 'string', 'max:20'],
            'frefdtno' => ['nullable'],
            'frefdtno.*' => ['nullable'],
            'fnoacak' => ['nullable', 'array'],
            'fnoacak.*' => ['nullable', 'regex:/^[1-9]{3}$/'],
            'frefnoacak' => ['nullable', 'array'],
            'frefnoacak.*' => ['nullable', 'regex:/^\d{3}$/'],
            'fqty' => ['required', 'array'],
            'fqty.*' => ['numeric', 'min:0', 'gt:0'],
            'fprice' => ['nullable', 'array'],
            'fprice.*' => ['numeric', 'min:0'],
            'fdisc' => ['nullable', 'array'],
            'fdisc.*' => ['nullable', 'string', 'regex:/^\s*\d+(?:\.\d+)?(?:\s*\+\s*\d+(?:\.\d+)?)*\s*$/'],
            'frefpr' => ['nullable', 'array'],
            'frefpr.*' => ['nullable', 'string', 'max:30'],
            'fdesc' => ['nullable', 'array'],
            'fdesc.*' => ['nullable', 'string', 'max:500'],
            'ppn_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'fpodate.required' => 'Tanggal PO wajib diisi.',
            'fsupplier.required' => 'Supplier wajib diisi.',
            'fitemcode.required' => 'Minimal 1 item.',
            'fqty.*.gt' => 'Harap hapus data atau isi qty data pada detail item (Qty tidak boleh 0).',
            'fnoacak.*.regex' => 'No acak PO harus terdiri dari 3 digit angka 1-9 tanpa 0.',
            'frefnoacak.*.regex' => 'No referensi acak harus terdiri dari 3 digit angka.',
            'fdisc.*.regex' => 'Format diskon item harus angka atau format seperti 10+2.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $this->ensureNoDuplicateDetailCodes($request->input('fitemcode', []));

        $fponoId = (int) $header->fpohid;

        $fpodate = \Carbon\Carbon::parse($request->fpodate)->startOfDay();
        $this->ensureCreateDateWithinEditPeriod($fpodate, $header->fpodate);
        $fkirimdate = $request->filled('fkirimdate')
            ? \Carbon\Carbon::parse($request->fkirimdate)->startOfDay()
            : null;
        $fincludeppn = $request->boolean('fincludeppn') ? 1 : 0;
        $userid = Auth::guard('sysuser')->user()?->fname
            ?? Auth::user()?->fname
            ?? 'system';
        $now = now();

        $codes = $request->input('fitemcode', []);
        $satuans = $request->input('fsatuan', []);
        $refdtns = $request->input('frefdtno', []);
        $frefdtids = $request->input('frefdtid', []);
        $fnoacaks = $request->input('fnoacak', []);
        $frefnoacaks = $request->input('frefnoacak', []);
        $qtys = $request->input('fqty', []);
        $prices = $request->input('fprice', []);
        $discs = $request->input('fdisc', []);
        $refprs = $request->input('frefpr', []);
        $descs = $request->input('fdesc', []);
        $fapplyppn = $request->boolean('fapplyppn') ? 1 : 0;

        $ppnRate = (float) $request->input('ppn_rate', 0);
        $ppnRate = max(0, min(100, $ppnRate));

        $uniqueCodes = array_values(array_unique(
            array_filter(array_map(fn($c) => trim((string) $c), $codes))
        ));

        $prodMeta = DB::table('msprd')
            ->whereIn('fprdcode', $uniqueCodes)
            ->get(['fprdid', 'fprdcode', 'fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'])
            ->keyBy('fprdcode');

        $pickDefaultSat = function ($code) use ($prodMeta) {
            $m = $prodMeta[$code] ?? null;
            if (! $m) {
                return '';
            }
            foreach (['fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'] as $k) {
                $v = trim((string) ($m->$k ?? ''));
                if ($v !== '') {
                    return mb_substr($v, 0, 20);
                }
            }

            return '';
        };

        $rowsPod = [];
        $totalHarga = 0.0;
        $rowCount = max(
            count($codes),
            count($satuans),
            count($refdtns),
            count($frefdtids),
            count($fnoacaks),
            count($frefnoacaks),
            count($qtys),
            count($prices),
            count($discs),
            count($refprs),
            count($descs)
        );
        $usedNoAcaks = [];

        for ($i = 0; $i < $rowCount; $i++) {
            $code = trim((string) ($codes[$i] ?? ''));
            $sat = trim((string) ($satuans[$i] ?? ''));
            $refdt = trim((string) ($refdtns[$i] ?? ''));
            $qty = (float) ($qtys[$i] ?? 0);
            $price = (float) ($prices[$i] ?? 0);
            $discRaw = $this->normalizeDiscountInput($discs[$i] ?? 0);
            $discP = $this->parseDiscountExpression($discRaw);
            $desc = (string) ($descs[$i] ?? '');
            $frefdtid = (int) ($frefdtids[$i] ?? 0);
            $fnoacak = $this->normalizeRandomNumber($fnoacaks[$i] ?? null, $usedNoAcaks);
            $frefnoacak = $this->normalizeReferenceRandomNumber($frefnoacaks[$i] ?? null, $frefdtid > 0);

            if ($code === '' || $qty <= 0) {
                continue;
            }

            $product = DB::table('msprd')
                ->where('fprdcode', $code)
                ->select('fprdid', 'fprdcode', 'fsatuanbesar', 'fsatuanbesar2', 'fqtykecil', 'fqtykecil2')
                ->first();

            if (! $product) {
                continue;
            }

            $productId = (int) $product->fprdid;
            if ($productId === 0) {
                continue;
            }

            if ($sat === '') {
                $sat = $pickDefaultSat($code);
            }
            if ($frefdtid > 0) {
                $prUnit = DB::table('tr_prd')
                    ->where('fprdid', $frefdtid)
                    ->value('fsatuan');
                if ($prUnit !== null) {
                    $sat = trim($prUnit);
                }
            }
            $sat = mb_substr($sat, 0, 20);
            if ($sat === '') {
                continue;
            }

            $qtyKecil = $this->qtyPoToKecil($product, $sat, $qty);

            $priceGross = $price;
            $priceNet = $priceGross * (1 - ($discP / 100));
            $amount = $qty * $priceNet;
            $totalHarga += $amount;

            $rowsPod[] = [
                'fprdid' => $productId,
                'fprdcode' => $product->fprdcode ?? '',
                'fqty' => $qty,
                'fdisc' => $discRaw,
                'fprice' => $price,
                'fprice_rp' => $price,
                'fpricegross' => $priceGross,
                'fpricenet' => $priceNet,
                'famount' => $amount,
                'famount_rp' => $amount,
                'fuserupdate' => $userid,
                'fdatetime' => $now,
                'fsatuan' => $sat,
                'fdesc' => $desc,
                'fqtykecil' => $qtyKecil,
                'fqtyremain' => $qtyKecil,
                'frefdtno' => $refdt,
                'frefdtid' => $frefdtid ?: null,
                'fnoacak' => $fnoacak,
                'frefnoacak' => $frefnoacak,
            ];
        }

        if (empty($rowsPod)) {
            return back()->withInput()->withErrors([
                'detail' => 'Minimal satu item valid (Kode Produk ada di master, Satuan tidak kosong, Qty > 0).',
            ]);
        }

        if ($validationMessage = $this->validateUniqueReferenceUsage($rowsPod, $header->fpono)) {
            return back()->withInput()->withErrors([
                'detail' => $validationMessage,
            ]);
        }

        $oldUsageByRef = DB::table('tr_pod')
            ->where('fpono', $header->fpono)
            ->whereNotNull('frefdtid')
            ->selectRaw('CAST(frefdtid AS INTEGER) AS fprdid, SUM(COALESCE(fqtykecil, 0)) AS used_kecil')
            ->groupByRaw('CAST(frefdtid AS INTEGER)')
            ->pluck('used_kecil', 'fprdid')
            ->map(fn($value) => (float) $value)
            ->all();

        $prdAgg = $this->aggregatePrdUsageByPrd($rowsPod);

        $ppnAmount = $fincludeppn ? round($totalHarga * ($ppnRate / 100), 2) : 0.0;
        $grandTotal = round($totalHarga + $ppnAmount, 2);

        try {
            DB::transaction(function () use (
                $request,
                $header,
                $fpodate,
                $fkirimdate,
                $userid,
                $fapplyppn,
                $rowsPod,
                $fponoId,
                $totalHarga,
                $ppnAmount,
                $grandTotal,
                $fincludeppn,
                $prdAgg,
                $oldUsageByRef
            ) {
                $this->validatePrdRemain($prdAgg, $oldUsageByRef);
                $this->adjustPrReferenceQtyKecil($oldUsageByRef, 1);

                $fpohid = DB::table('tr_poh')
                    ->where('fpohid', $fponoId)
                    ->update([
                        'fpodate' => $fpodate,
                        'fkirimdate' => $fkirimdate,
                        'fcurrency' => $request->input('fcurrency', 'IDR'),
                        'ftempohr' => $request->input('ftempohr', 0),
                        'frate' => $request->input('frate', 15500),
                        'fsupplier' => $request->input('fsupplier'),
                        'fincludeppn' => $fincludeppn,
                        'fket' => $request->input('fket'),
                        'fuserupdate' => $userid,
                        'fupdatedat' => now(),
                        'famountponet' => round($totalHarga, 2),
                        'famountpopajak' => $ppnAmount,
                        'famountpo' => $grandTotal,
                        'fapplyppn' => $fapplyppn,
                        'fppnpersen' => $request->input('ppn_rate', 0),
                        'fclose' => $request->has('fclose') ? '1' : (string) ($header->fclose ?? '0'),
                        'fprdin' => (string) ($header->fprdin ?? '0'),
                    ]);
                $fpono = DB::table('tr_poh')->where('fpohid', $fpohid)->value('fpono');

                DB::table('tr_pod')->where('fpono', $header->fpono)->delete();

                $nextNou = 1;
                foreach ($rowsPod as &$r) {
                    $r['fpono'] = $header->fpono;
                    $r['fnou'] = $nextNou++;
                }
                unset($r);

                DB::table('tr_pod')->insert($rowsPod);
                $this->adjustPrReferenceQtyKecil($prdAgg, -1);
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['detail' => $e->getMessage()]);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Order pembelian belum bisa disimpan. Coba lagi.');
        }

        return redirect()
            ->route('tr_poh.index')
            ->with('success', 'Order pembelian ' . $this->formatDisplayTransactionNumber($header->fpono, $fapplyppn === 1) . ' berhasil diupdate.');
    }

    public function delete(Request $request, $fpohid)
    {
        $suppliers = Supplier::orderBy('fsuppliername', 'asc')
            ->get(['fsupplierid', 'fsuppliercode', 'fsuppliername', 'ftempo', 'fcurr']);

        $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

        $currencies = DB::table('mscurrency')
            ->where(function ($q) {
                $q->whereNull('fnonactive')
                    ->orWhere('fnonactive', '0')
                    ->orWhere('fnonactive', '');
            })
            ->orderBy('fcurrname')
            ->get(['fcurrid', 'fcurrcode', 'fcurrname', 'frate']);

        $branch = DB::table('mscabang')
            ->when(is_numeric($raw), fn($q) => $q->where('fcabangid', (int) $raw))
            ->when(! is_numeric($raw), fn($q) => $q
                ->where('fcabangkode', $raw)
                ->orWhere('fcabangname', $raw))
            ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

        $fcabang = $branch->fcabangname ?? (string) $raw;   // tampilan
        $fbranchcode = $branch->fcabangkode ?? (string) $raw;   // hidden post

        $tr_poh = Tr_poh::with(['details' => function ($q) {
            $q->leftJoin('msprd as m', 'm.fprdid', '=', 'tr_pod.fprdid')
                ->leftJoin(DB::raw('(
        SELECT
            fprdcode,
            frefdtno,
            SUM(fqty) AS total_terima
        FROM trstockdt
        WHERE
            (fstockmtcode = \'TER\' OR (fcode = \'P\' AND fstockmtcode = \'BUY\'))
        GROUP BY
            fprdcode,
            frefdtno
    ) as r'), function ($join) {
                    $join->on('r.frefdtno', '=', 'tr_pod.frefdtno')   // ← join via frefdtno
                        ->on('r.fprdcode', '=', 'm.fprdcode');
                })
                ->select(
                    'tr_pod.*',
                    'm.fprdcode as fitemcode',
                    'm.fprdname',
                    'm.fsatuankecil',
                    'm.fsatuanbesar',
                    'm.fsatuanbesar2',
                    'm.fqtykecil',
                    'm.fqtykecil2',
                    DB::raw('COALESCE((SELECT pr.fqty FROM tr_prd pr WHERE tr_pod.frefdtid IS NOT NULL AND pr.fprdid = CAST(tr_pod.frefdtid AS INTEGER) LIMIT 1), 0) as fqtypr'),
                    DB::raw("COALESCE((SELECT pr.fsatuan FROM tr_prd pr WHERE tr_pod.frefdtid IS NOT NULL AND pr.fprdid = CAST(tr_pod.frefdtid AS INTEGER) LIMIT 1), '') as fqtypr_satuan"),
                    DB::raw('COALESCE(r.total_terima, 0) AS fqtyterima'),
                );
        }])->findOrFail($fpohid);

        if ($message = $this->getPostedPeriodLockMessage($tr_poh->fpodate, 'Data ini')) {
            return redirect()
                ->route('tr_poh.view', $tr_poh->fpohid)
                ->with('error', $message);
        }
        $details = $this->getPoDetailsWithTerimaUsage($tr_poh->fpono);

        // Cek apakah PO sudah ada penerimaan barang
        $existingTerima = DB::table('trstockdt')
            ->where('frefdtno', $tr_poh->fpono)
            ->select('fstockmtno', 'fdatetime', DB::raw('SUM(fqty) as total_qty'))
            ->groupBy('fstockmtno', 'fdatetime')
            ->get();

        $blockedByTerima = $existingTerima->isNotEmpty();

        if ($blockedByTerima) {
            return redirect()
                ->route('tr_poh.view', $tr_poh->fpohid)
                ->with('error', $this->getUsageLockMessage($tr_poh));
        }

        // Lookup currency berdasarkan fcurrency (currency code) di tr_poh
        $currentCurrency = DB::table('mscurrency')
            ->where('fcurrcode', $tr_poh->fcurrency)
            ->first(['fcurrid', 'fcurrcode', 'fcurrname', 'frate']);

        $products = $this->browseProducts();
        $productMap = $this->browseProductMap($products, 'fprdid');

        $savedItems = $details->map(function ($d) use ($products) {
            $prod = $products->firstWhere('fprdcode', $d->fitemcode);
            $units = $prod
                ? array_values(array_filter([
                    $prod->fsatuankecil,
                    $prod->fsatuanbesar,
                    $prod->fsatuanbesar2,
                ]))
                : ($d->fsatuan ? [$d->fsatuan] : []);

            // Pastikan fsatuan ada di units
            if ($d->fsatuan && ! in_array($d->fsatuan, $units)) {
                array_unshift($units, $d->fsatuan);
            }

            return [
                'uid' => (string) ($d->fpodid ?? \Illuminate\Support\Str::uuid()),
                'fitemcode' => (string) ($d->fitemcode ?? ''),
                'fitemname' => (string) ($d->fprdname ?? ''),
                'fsatuan' => (string) ($d->fsatuan ?? ''),
                'units' => $units,
                'frefdtno' => (string) ($d->frefdtno ?? ''),
                'fpono' => (string) ($d->fpono ?? ''),
                'frefdtid' => (string) ($d->frefdtid ?? ''),
                'fnouref' => (string) ($d->fnouref ?? ''),
                'fnoacak' => (string) ($d->fnoacak ?? ''),
                'frefnoacak' => (string) ($d->frefnoacak ?? ''),
                'fqtyterima' => (float) ($d->fqtyterima ?? 0),
                'frefpr' => (string) ($d->fprhid ?? ''),
                'fprhid' => (string) ($d->fprhid ?? ''),
                'fprno' => (string) ($d->frefdtno ?? ''),
                'fqty' => (float) ($d->fqty ?? 0),
                'fterima' => (float) ($d->fterima ?? 0),
                'fprice' => (float) ($d->fprice ?? 0),
                'fdisc' => $this->normalizeDiscountInput($d->fdisc ?? 0),
                'ftotal' => (float) ($d->famount ?? 0),
                'fdesc' => (string) ($d->fdesc ?? ''),
                'fketdt' => (string) ($d->fketdt ?? ''),
                'maxqty' => 0,
            ];
        })->values();

        // Pass the data to the view
        return view('tr_poh.edit', [
            'suppliers' => $suppliers,
            'selectedSupplierCode' => $tr_poh->fsupplier,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'existingTerima' => $existingTerima,
            'blockedByTerima' => $blockedByTerima,
            'usageLockMessage' => $blockedByTerima ? $this->getUsageLockMessage($tr_poh) : null,
            'products' => $products,
            'productMap' => $productMap,
            'tr_poh' => $tr_poh,
            'displayFpono' => $this->formatDisplayTransactionNumber($tr_poh->fpono ?? null, (int) ($tr_poh->fapplyppn ?? 0) === 1),
            'savedItems' => $savedItems,
            'currencies' => $currencies,
            'currentCurrency' => $currentCurrency,   // <-- currency aktif dari join
            'ppnAmount' => (float) ($tr_poh->famountpopajak ?? 0),
            'famountponet' => (float) ($tr_poh->famountponet ?? 0),
            'famountpo' => (float) ($tr_poh->famountpo ?? 0),
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'action' => 'delete',
        ]);
    }

    public function destroy($fpohid)
    {
        try {
            $tr_poh = Tr_poh::findOrFail($fpohid);

            if ($message = $this->getPostedPeriodLockMessage($tr_poh->fpodate, 'Data ini')) {
                return redirect()->route('tr_poh.view', $tr_poh->fpohid)->with('error', $message);
            }

            if ($message = $this->getUsageLockMessage($tr_poh)) {
                return redirect()->route('tr_poh.index')->with('error', $message);
            }

            DB::transaction(function () use ($tr_poh) {
                $oldUsageByRef = DB::table('tr_pod')
                    ->where('fpono', $tr_poh->fpono)
                    ->get(['frefdtid', 'fqtykecil'])
                    ->groupBy(fn($row) => (int) ($row->frefdtid ?? 0))
                    ->map(fn($rows) => (float) $rows->sum(fn($row) => (float) ($row->fqtykecil ?? 0)))
                    ->all();

                $this->adjustPrReferenceQtyKecil($oldUsageByRef, 1);
                DB::table('tr_pod')->where('fpono', $tr_poh->fpono)->delete();
                $tr_poh->delete();
            });

            return redirect()->route('tr_poh.index')
                ->with('success', 'Order pembelian ' . $this->formatDisplayTransactionNumber($tr_poh->fpono, (int) ($tr_poh->fapplyppn ?? 0) === 1) . ' berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Order pembelian belum bisa dihapus. Coba lagi.');
        }
    }

    private function getPoDetailsWithTerimaUsage(string $fpono)
    {
        $terimaUsageSub = $this->getPoTerimaUsageSubquery();

        return DB::table('tr_pod as d')
            ->leftJoin('msprd as p', 'p.fprdid', '=', 'd.fprdid')
            ->leftJoinSub($terimaUsageSub, 'ter', function ($join) {
                $join->on('ter.frefdtid', '=', 'd.fpodid');
            })
            ->where('d.fpono', $fpono)
            ->select([
                'd.*',
                'p.fprdcode as fitemcode',
                'p.fprdname',
                'p.fsatuankecil',
                'p.fsatuanbesar',
                'p.fsatuanbesar2',
                DB::raw('COALESCE(p.fqtykecil, 0) as fqtykecil_master'),
                DB::raw('COALESCE(p.fqtykecil2, 0) as fqtykecil2_master'),
                DB::raw('COALESCE((SELECT pr.fqty FROM tr_prd pr WHERE d.frefdtid IS NOT NULL AND pr.fprdid = CAST(d.frefdtid AS INTEGER) LIMIT 1), 0) as fqtypr'),
                DB::raw("COALESCE((SELECT pr.fsatuan FROM tr_prd pr WHERE d.frefdtid IS NOT NULL AND pr.fprdid = CAST(d.frefdtid AS INTEGER) LIMIT 1), '') as fqtypr_satuan"),
                DB::raw('COALESCE(ter.fqtyterima, 0) AS fqtyterima'),
            ])
            ->orderBy('d.fnou')
            ->get();
    }


    private function getPoTerimaUsageSubquery()
    {
        return DB::table('trstockdt as d')
            ->join('msprd as p', 'd.fprdcode', '=', 'p.fprdcode')
            ->where(function ($query) {
                $query->where('d.fstockmtcode', 'TER')
                    ->orWhere(function ($subQuery) {
                        $subQuery->where('d.fcode', 'P')
                            ->where('d.fstockmtcode', 'BUY');
                    });
            })
            ->selectRaw("
                CAST(d.frefdtid AS BIGINT) AS frefdtid,
                SUM(
                    CASE
                        WHEN d.fsatuan = p.fsatuanbesar THEN COALESCE(NULLIF(d.fqtykecil, 0) / NULLIF(p.fqtykecil, 0), d.fqty, 0)
                        WHEN d.fsatuan = p.fsatuanbesar2 THEN COALESCE(NULLIF(d.fqtykecil, 0) / NULLIF(p.fqtykecil2, 0), d.fqty, 0)
                        ELSE COALESCE(NULLIF(d.fqtykecil, 0), d.fqty, 0)
                    END
                ) AS fqtyterima
            ")
            ->whereNotNull('d.frefdtid')
            ->groupBy(DB::raw('CAST(d.frefdtid AS BIGINT)'));
    }

    private function getUsageLockMessage(Tr_poh $header): ?string
    {
        $usedBy = DB::table('trstockdt')
            ->where('frefdtno', $header->fpono)
            ->select('fstockmtno')
            ->distinct()
            ->orderBy('fstockmtno')
            ->pluck('fstockmtno');

        if ($usedBy->isEmpty()) {
            return null;
        }

        return "Information\nOrder ini tidak dapat di-Edit/Delete.\nMasih ada Referensi di Transaksi:\n" . $usedBy->implode(', ');
    }

    private function validateUniqueReferenceUsage(array $rowsPod, ?string $exceptPono = null): ?string
    {
        $referenceDetailIds = collect($rowsPod)
            ->pluck('frefdtid')
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($referenceDetailIds)) {
            return null;
        }

        $query = DB::table('tr_pod as d')
            ->join('tr_poh as h', 'h.fpono', '=', 'd.fpono')
            ->whereIn(DB::raw('CAST(d.frefdtid AS INTEGER)'), $referenceDetailIds);

        if (! empty($exceptPono)) {
            $query->where('h.fpono', '<>', $exceptPono);
        }

        $existing = $query
            ->orderBy('h.fpono')
            ->select(
                'h.fpono as transaction_no',
                DB::raw("COALESCE(NULLIF(TRIM(d.frefdtno), ''), CAST(d.frefdtid AS TEXT)) as ref_no")
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
}
