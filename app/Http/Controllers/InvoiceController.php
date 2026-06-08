<?php

namespace App\Http\Controllers;

use App\Mail\GenericApprovalNotification;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Salesman;
use App\Models\Supplier;
use App\Models\Tr_prd;
use App\Models\Tr_prh;
use App\Models\Tranmt;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
// sekalian biar aman untuk tanggal
use Illuminate\Validation\ValidationException;
use App\Support\ApprovalState;

class InvoiceController extends Controller
{
    private const MEMO_DEBIT_ACCOUNT = '11300';
    private const MEMO_CREDIT_ACCOUNT = '41000';

    private ?bool $tranmtHasInternalNoteColumn = null;

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

    private function formatDisplayTransactionNumber(?string $number, bool $useSlash = false): string
    {
        $normalized = trim((string) $number);
        if ($normalized === '') {
            return '-';
        }

        $separator = $useSlash ? '/' : '.';

        return (string) preg_replace('/[.\/](\d+)$/', $separator . '$1', $normalized, 1);
    }

    private function tranmtHasInternalNoteColumn(): bool
    {
        if ($this->tranmtHasInternalNoteColumn === null) {
            $this->tranmtHasInternalNoteColumn = Schema::hasColumn('tranmt', 'fketinternal');
        }

        return $this->tranmtHasInternalNoteColumn;
    }

    private function ensureNoDuplicateDetailCodes(array $codes, array $detailPayload = []): void
    {
        $seenCodes = [];
        $seenSignatures = [];
        $duplicates = [];

        foreach ($codes as $index => $rawCode) {
            $code = strtoupper(trim((string) $rawCode));
            if ($code === '') {
                continue;
            }

            $signatureParts = [$code];
            foreach ($detailPayload as $field => $values) {
                $value = is_array($values) ? ($values[$index] ?? '') : '';
                $signatureParts[] = is_scalar($value) ? trim((string) $value) : json_encode($value);
            }
            $rowSignature = implode('|', $signatureParts);

            // Abaikan baris payload yang identik persis dua kali dari form.
            if (isset($seenSignatures[$rowSignature])) {
                continue;
            }

            $seenSignatures[$rowSignature] = true;

            if (isset($seenCodes[$code])) {
                $duplicates[$index] = $code;
                continue;
            }

            $seenCodes[$code] = true;
        }

        if ($duplicates === []) {
            return;
        }

        $messages = [];
        foreach ($duplicates as $index => $code) {
            $messages["fitemcode.$index"] = "Kode produk {$code} tidak boleh sama dalam satu Faktur Penjualan.";
        }

        throw ValidationException::withMessages($messages);
    }

    private function normalizeInvoiceDetailPayload(array $payload): array
    {
        if ($payload === []) {
            return [];
        }

        $fields = array_keys($payload);
        $rowIndexes = [];

        foreach ($payload as $values) {
            if (is_array($values)) {
                $rowIndexes = array_merge($rowIndexes, array_keys($values));
            }
        }

        $rowIndexes = array_values(array_unique($rowIndexes));
        usort($rowIndexes, static function ($a, $b) {
            if (is_numeric($a) && is_numeric($b)) {
                return (int) $a <=> (int) $b;
            }

            return strcmp((string) $a, (string) $b);
        });

        $uniqueRows = [];
        $orderedKeys = [];

        foreach ($rowIndexes as $index) {
            $row = [];
            foreach ($fields as $field) {
                $values = $payload[$field] ?? [];
                $row[$field] = is_array($values) ? ($values[$index] ?? null) : null;
            }

            $code = strtoupper(trim((string) ($row['fitemcode'] ?? '')));
            if ($code === '') {
                continue;
            }

            $signatureParts = [$code];
            foreach ($fields as $field) {
                if ($field === 'fitemcode') {
                    continue;
                }

                $value = $row[$field] ?? '';
                $signatureParts[] = is_scalar($value) ? trim((string) $value) : json_encode($value);
            }

            $signature = implode('|', $signatureParts);
            if (!isset($uniqueRows[$signature])) {
                $orderedKeys[] = $signature;
            }
            $uniqueRows[$signature] = [
                'index' => $index,
                'row' => $row,
            ];
        }

        $normalized = [];
        foreach ($fields as $field) {
            $normalized[$field] = [];
        }

        foreach ($orderedKeys as $signature) {
            $index = $uniqueRows[$signature]['index'];
            $row = $uniqueRows[$signature]['row'];
            foreach ($fields as $field) {
                $normalized[$field][$index] = $row[$field] ?? null;
            }
        }

        return $normalized;
    }

    private function getReverseJournalBaseAmountByStockDocs(array $stockDocNos): float
    {
        $docNos = collect($stockDocNos)
            ->map(fn($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($docNos)) {
            return 0.0;
        }

        return (float) DB::table('trstockdt')
            ->whereIn('fstockmtno', $docNos)
            ->where('fcode', 'T')
            ->sum(DB::raw('COALESCE(ftotprice_rp, 0)'));
    }

    private function validateReverseJournalBaseAmount(array $stockDocNos, float $invoiceGrandTotalRp): ?string
    {
        if (empty($stockDocNos)) {
            return null;
        }

        $reverseJournalBaseAmount = $this->getReverseJournalBaseAmountByStockDocs($stockDocNos);

        if ($reverseJournalBaseAmount <= 0) {
            return 'Referensi Surat Jalan belum punya nilai dasar jurnal balik.';
        }

        if (($invoiceGrandTotalRp - $reverseJournalBaseAmount) > 0.000001) {
            return 'Total faktur penjualan melebihi nilai referensi Surat Jalan untuk jurnal balik. Maksimal Rp ' . number_format($reverseJournalBaseAmount, 2, ',', '.') . '.';
        }

        return null;
    }

    private function getInventoryBaseAmountByStockDocs(array $stockDocNos): float
    {
        $docNos = collect($stockDocNos)
            ->map(fn($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($docNos)) {
            return 0.0;
        }

        return (float) DB::table('trstockdt')
            ->whereIn('fstockmtno', $docNos)
            ->where('fprdcode', '<>', 'UM')
            ->where(function ($query) {
                $query->where('fcode', 'P')
                    ->orWhereNull('fcode')
                    ->orWhereRaw("COALESCE(TRIM(CAST(fcode AS TEXT)), '') = ''");
            })
            ->sum(DB::raw('COALESCE(ftotprice_rp, 0)'));
    }

    private function validateInventoryBaseAmount(array $stockDocNos): ?string
    {
        if (empty($stockDocNos)) {
            return null;
        }

        $inventoryBaseAmount = $this->getInventoryBaseAmountByStockDocs($stockDocNos);

        if ($inventoryBaseAmount <= 0) {
            return 'Referensi Surat Jalan belum punya nilai persediaan.';
        }

        return null;
    }

    private function getAdvanceReductionAmountByStockDocs(array $stockDocNos): float
    {
        $docNos = collect($stockDocNos)
            ->map(fn($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($docNos)) {
            return 0.0;
        }

        return (float) DB::table('trstockdt')
            ->whereIn('fstockmtno', $docNos)
            ->where('fprdcode', 'UM')
            ->sum(DB::raw('ABS(COALESCE(ftotprice_rp, 0))'));
    }

    private function validateAdvanceReductionAmount(array $stockDocNos): ?string
    {
        if (empty($stockDocNos)) {
            return null;
        }

        $advanceReductionAmount = $this->getAdvanceReductionAmountByStockDocs($stockDocNos);

        if ($advanceReductionAmount <= 0) {
            return 'Referensi Surat Jalan belum punya nilai pengurang uang muka.';
        }

        return null;
    }

    private function getCustomerTaxCode(string $customerCode): ?string
    {
        $customerCode = trim($customerCode);
        if ($customerCode === '') {
            return null;
        }

        $taxCode = Customer::query()
            ->where('fcustomercode', $customerCode)
            ->value('fkodefp');

        if ($taxCode === null) {
            return null;
        }

        return mb_substr((string) $taxCode, 0, 50);
    }

    private function canApproveCreditLimit(): bool
    {
        return in_array('approveFakturPenjualan', explode(',', session('user_restricted_permissions', '')));
    }

    private function canCreateSuratJalan(): bool
    {
        return in_array('createSuratJalan', explode(',', session('user_restricted_permissions', '')), true);
    }

    private function getApprovalRecipients(): array
    {
        return array_values(array_filter([
            trim((string) config('approval.invoice.stage1', '')),
            trim((string) config('approval.invoice.stage2', '')),
        ]));
    }

    private function sendApprovalNotification(string $fsono, string $approver): void
    {
        $header = DB::table('tranmt as mt')
            ->leftJoin('mscustomer as c', 'mt.fcustno', '=', 'c.fcustomercode')
            ->leftJoin('mssalesman as s', 'mt.fsalesman', '=', 's.fsalesmancode')
            ->where('mt.fsono', $fsono)
            ->first([
                'mt.*',
                'c.fcustomername',
                's.fsalesmanname',
            ]);

        if (! $header) {
            return;
        }

        $items = DB::table('trandt as d')
            ->leftJoin('msprd as p', 'd.fprdcode', '=', 'p.fprdcode')
            ->where('d.fsono', $fsono)
            ->orderBy('d.fnou')
            ->get([
                'd.fprdcode',
                'd.fdesc',
                'd.fqty',
                'd.fprice',
                'd.famount',
                'd.fsatuan',
                'p.fprdname',
            ])
            ->map(fn($item) => [
                'code' => $item->fprdcode,
                'name' => trim(($item->fprdname ?? '-') . (! empty($item->fdesc) ? ' / ' . $item->fdesc : '')),
                'qty' => number_format((float) $item->fqty, 2, ',', '.') . ' ' . ($item->fsatuan ?? ''),
                'price' => format_number($item->fprice ?? 0),
                'total' => format_number($item->famount ?? 0),
            ])
            ->all();

        $fields = [
            ['label' => 'Tanggal', 'value' => $header->fsodate ? Carbon::parse($header->fsodate)->format('d-m-Y') : '-'],
            ['label' => 'Customer', 'value' => trim(($header->fcustno ?? '-') . ' - ' . ($header->fcustomername ?? '-'))],
            ['label' => 'Salesman', 'value' => $header->fsalesmanname ?? ($header->fsalesman ?? '-')],
            ['label' => 'Persetujuan Kredit', 'value' => $header->fuseracc ?? '-'],
            ['label' => 'Total', 'value' => format_number($header->famountso ?? 0)],
            ['label' => 'Keterangan', 'value' => $header->fket ?? '-'],
        ];

        $recipients = array_slice($this->getApprovalRecipients(), 0, 2);

        if (! empty($recipients[0]) && ! empty($header->fapproval_token)) {
            Mail::to($recipients[0])->send(new GenericApprovalNotification(
                'Approval Faktur Penjualan',
                'Faktur Penjualan Approval',
                $fsono,
                $approver,
                route('approval.invoice.page', ['fsono' => $fsono, 'token' => $header->fapproval_token]),
                $fields,
                $items
            ));
        }

        if (! empty($recipients[1]) && ! empty($header->fapproval_token2)) {
            Mail::to($recipients[1])->send(new GenericApprovalNotification(
                'Approval Faktur Penjualan',
                'Faktur Penjualan Approval',
                $fsono,
                $approver,
                route('approval.invoice.page', ['fsono' => $fsono, 'token' => $header->fapproval_token2]),
                $fields,
                $items
            ));
        }
    }

    private function initializeApprovalState(): array
    {
        return ApprovalState::initializeApprovalColumns(
            array_slice($this->getApprovalRecipients(), 0, 2),
            fn() => \Illuminate\Support\Str::random(64)
        );
    }

    private function getApprovalLockMessage($record): ?string
    {
        return ApprovalState::isEditBlockedRecord($record)
            ? 'Faktur Penjualan belum dapat diubah karena status approval saat ini belum mengizinkan edit.'
            : null;
    }

    private function getCustomerCreditChecks(string $customerCode, float $currentTransactionAmount = 0, ?int $exceptTranmtId = null): array
    {
        $customerCode = trim($customerCode);
        $customer = Customer::query()
            ->where('fcustomercode', $customerCode)
            ->first(['fcustomercode', 'fcustomername', 'flimit', 'fmaxtempo']);

        if (! $customer) {
            return [
                'customer' => null,
                'limit_check' => [
                    'enabled' => false,
                    'exceeded' => false,
                    'limit' => 0,
                    'outstanding_total' => 0,
                    'transaction_amount' => $currentTransactionAmount,
                    'projected_total' => 0,
                ],
                'overdue_check' => [
                    'enabled' => false,
                    'has_overdue' => false,
                    'max_tempo' => 0,
                    'items' => [],
                ],
            ];
        }

        $outstandingQuery = DB::table('tranmt')
            ->where('fcustno', $customerCode)
            ->whereRaw('COALESCE(famountremain, 0) > 0');

        if ($exceptTranmtId) {
            $outstandingQuery->where('ftranmtid', '<>', $exceptTranmtId);
        }

        $outstandingTotal = (float) $outstandingQuery->sum('famountremain');
        $transactionAmount = max(0, $currentTransactionAmount);
        $projectedTotal = $outstandingTotal + $transactionAmount;
        $limit = (float) ($customer->flimit ?? 0);
        $maxTempo = (int) ($customer->fmaxtempo ?? 0);

        $overdueItems = collect();
        if ($maxTempo > 0) {
            $overdueQuery = DB::table('tranmt')
                ->where('fcustno', $customerCode)
                ->whereRaw('COALESCE(famountremain, 0) > 0')
                ->whereNotNull('fjatuhtempo')
                ->whereRaw('CAST(NOW() AS DATE) - CAST(fjatuhtempo AS DATE) > ?', [$maxTempo]);

            if ($exceptTranmtId) {
                $overdueQuery->where('ftranmtid', '<>', $exceptTranmtId);
            }

            $overdueItems = $overdueQuery
                ->orderBy('fjatuhtempo')
                ->limit(10)
                ->get([
                    'ftranmtid',
                    'fsono',
                    'fjatuhtempo',
                    'famountremain',
                ]);
        }

        return [
            'customer' => [
                'code' => $customer->fcustomercode,
                'name' => $customer->fcustomername,
            ],
            'limit_check' => [
                'enabled' => $limit > 0,
                'exceeded' => $limit > 0 && $projectedTotal > $limit,
                'limit' => $limit,
                'outstanding_total' => $outstandingTotal,
                'transaction_amount' => $transactionAmount,
                'projected_total' => $projectedTotal,
            ],
            'overdue_check' => [
                'enabled' => $maxTempo > 0,
                'has_overdue' => $overdueItems->isNotEmpty(),
                'max_tempo' => $maxTempo,
                'items' => $overdueItems->map(fn($row) => [
                    'ftranmtid' => (int) ($row->ftranmtid ?? 0),
                    'fsono' => (string) ($row->fsono ?? ''),
                    'fjatuhtempo' => ! empty($row->fjatuhtempo)
                        ? Carbon::parse($row->fjatuhtempo)->format('Y-m-d')
                        : null,
                    'famountremain' => (float) ($row->famountremain ?? 0),
                ])->values()->all(),
            ],
        ];
    }

    private function resolveInvoiceCreditApproval(Request $request, float $grandTotal, ?int $exceptTranmtId = null): array
    {
        $checks = $this->getCustomerCreditChecks(
            (string) $request->input('fcustno', ''),
            $grandTotal,
            $exceptTranmtId
        );

        $needsApproval = (bool) ($checks['limit_check']['exceeded'] ?? false)
            || (bool) ($checks['overdue_check']['has_overdue'] ?? false);

        if (! $needsApproval) {
            return [
                'fuseracc' => '0',
                'checks' => $checks,
            ];
        }

        if (! $this->canApproveCreditLimit()) {
            throw ValidationException::withMessages([
                'fcustno' => "Transaksi ini butuh persetujuan.\n- Limit piutang customer sudah terlampaui, atau\n- Ada tagihan customer yang sudah lewat jatuh tempo.\n\nSilakan hubungi user yang berwenang.",
            ]);
        }

        $approvedBy = trim((string) $request->input('fuseracc', ''));
        if ($approvedBy === '') {
            throw ValidationException::withMessages([
                'fcustno' => "Transaksi ini butuh persetujuan.\n- Pilih Yes pada konfirmasi untuk melanjutkan.",
            ]);
        }

        return [
            'fuseracc' => mb_substr($approvedBy, 0, 30),
            'checks' => $checks,
        ];
    }

    private function shouldRequestInvoiceApproval(Request $request): bool
    {
        return trim((string) $request->input('fneedacc', '0')) === '1';
    }

    public function creditCheck(Request $request)
    {
        $validated = $request->validate([
            'fcustno' => ['required', 'string', 'max:10'],
            'famountso' => ['nullable', 'numeric', 'min:0'],
            'ftranmtid' => ['nullable', 'integer'],
        ]);

        $checks = $this->getCustomerCreditChecks(
            (string) $validated['fcustno'],
            (float) ($validated['famountso'] ?? 0),
            isset($validated['ftranmtid']) ? (int) $validated['ftranmtid'] : null
        );

        return response()->json([
            'can_approve' => $this->canApproveCreditLimit(),
            'current_user' => auth('sysuser')->user()->fname ?? auth()->user()->name ?? 'system',
            'checks' => $checks,
        ]);
    }

    public function index(Request $request)
    {
        $canCreate = in_array('createInvoice', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updateInvoice', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteInvoice', explode(',', session('user_restricted_permissions', '')));
        $showActionsColumn = $canEdit || $canDelete;

        $year = $request->query('year');
        $month = $request->query('month');

        $availableYearsQuery = Tranmt::query()
            ->selectRaw('DISTINCT EXTRACT(YEAR FROM fsodate) as year')
            ->where('ftrcode', 'INV')
            ->whereNotNull('fsodate');
        $this->applyBranchVisibilityScope($availableYearsQuery, 'tranmt.fbranchcode');
        $availableYears = $availableYearsQuery
            ->orderByRaw('EXTRACT(YEAR FROM fsodate) DESC')
            ->pluck('year');

        if ($request->ajax()) {
            $referenceSummaryQuery = DB::table('trandt as d')
                ->selectRaw("
                    d.fsono,
                    STRING_AGG(DISTINCT CASE WHEN d.frefso LIKE 'SO.%' THEN NULLIF(TRIM(d.frefso), '') ELSE NULL END, ', ') as so_refs,
                    STRING_AGG(DISTINCT CASE WHEN d.frefsrj LIKE 'SRJ.%' OR d.frefsrj LIKE 'SJ.%' THEN NULLIF(TRIM(d.frefsrj), '') ELSE NULL END, ', ') as srj_refs
                ")
                ->groupBy('d.fsono');

            $query = Tranmt::query()
                ->leftJoin('mscabang as b', 'b.fcabangkode', '=', 'tranmt.fbranchcode')
                ->leftJoin('mscustomer as cust', 'cust.fcustomercode', '=', 'tranmt.fcustno')
                ->leftJoinSub($referenceSummaryQuery, 'ref_summary', function ($join) {
                    $join->on('ref_summary.fsono', '=', 'tranmt.fsono');
                })
                ->where('tranmt.ftrcode', 'INV')
                ->select([
                    'tranmt.ftranmtid',
                    'tranmt.fbranchcode',
                    'b.fcabangname',
                    'tranmt.fsono',
                    'tranmt.fincludeppn',
                    'tranmt.ftaxno',
                    'tranmt.fsodate',
                    'tranmt.frefno',
                    'cust.fcustomername',
                    'tranmt.famountso',
                    'tranmt.famountremain',
                    'tranmt.fuserid',
                    'tranmt.fprdout',
                    'tranmt.fsudahtagih',
                    'tranmt.fapproval',
                    'tranmt.fapproval2',
                    DB::raw("COALESCE(ref_summary.so_refs, '') as so_refs"),
                    DB::raw("COALESCE(ref_summary.srj_refs, '') as srj_refs"),
                ]);
            $this->applyBranchVisibilityScope($query, 'tranmt.fbranchcode');

            $totalRecords = (clone $query)->count();

            if ($search = trim((string) $request->input('search.value', ''))) {
                $query->where(function ($q) use ($search) {
                    $q->where('tranmt.fsono', 'ilike', "%{$search}%")
                        ->orWhere('tranmt.ftaxno', 'ilike', "%{$search}%")
                        ->orWhere('tranmt.frefno', 'ilike', "%{$search}%")
                        ->orWhere('cust.fcustomername', 'ilike', "%{$search}%")
                        ->orWhereRaw("COALESCE(ref_summary.so_refs, '') ILIKE ?", ["%{$search}%"])
                        ->orWhereRaw("COALESCE(ref_summary.srj_refs, '') ILIKE ?", ["%{$search}%"]);
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
                $query->where('cust.fcustomername', 'ilike', "%{$customerSearch}%");
            }

            $filteredRecords = (clone $query)->count();

            $orderColIdx = (int) $request->input('order.0.column', 3);
            $orderDir = $request->input('order.0.dir', 'desc');
            $sortableColumns = [
                0 => 'tranmt.fbranchcode',
                1 => 'tranmt.fsono',
                2 => 'tranmt.ftaxno',
                3 => 'tranmt.fsodate',
                4 => 'ref_summary.srj_refs',
                5 => 'ref_summary.so_refs',
                6 => 'cust.fcustomername',
                7 => 'tranmt.famountso',
                8 => 'tranmt.famountremain',
                9 => 'tranmt.frefno',
                10 => 'tranmt.fuserid',
                11 => 'tranmt.fsudahtagih',
            ];

            if (isset($sortableColumns[$orderColIdx])) {
                $query->orderBy($sortableColumns[$orderColIdx], $orderDir);
            } else {
                $query->orderBy('tranmt.fsodate', 'desc')->orderBy('tranmt.fsono', 'desc');
            }

            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 10);
            $records = $query->skip($start)->take($length)->get();

            $data = $records->map(function ($row) {
                $soRefs = trim((string) ($row->so_refs ?? ''));
                $srjRefs = trim((string) ($row->srj_refs ?? ''));

                return [
                    'ftranmtid' => $row->ftranmtid,
                    'fbranchcode' => $row->fbranchcode,
                    'fsono' => trim((string) ($row->fsono ?? '')),
                    'fsono_display' => $this->formatDisplayTransactionNumber($row->fsono ?? null, (string) ($row->fincludeppn ?? '0') === '1'),
                    'ftaxno' => trim((string) ($row->ftaxno ?? '')),
                    'fsodate' => $row->fsodate instanceof \Carbon\Carbon
                        ? $row->fsodate->format('Y-m-d')
                        : $row->fsodate,
                    'frefno' => $srjRefs !== '' ? $srjRefs : $soRefs,
                    'fso_refs' => $soRefs,
                    'fcustomername' => trim((string) ($row->fcustomername ?? '')),
                    'famountso' => (float) ($row->famountso ?? 0),
                    'famountremain' => (float) ($row->famountremain ?? 0),
                    'frefpo' => trim((string) ($row->frefno ?? '')),
                    'fuserid' => trim((string) ($row->fuserid ?? '')),
                    'fsudahtagih' => trim((string) ($row->fsudahtagih ?? '0')),
                    'fclose' => trim((string) ($row->fclose ?? '0')),
                    'fapproval' => trim((string) ($row->fapproval ?? '')),
                    'fapproval2' => trim((string) ($row->fapproval2 ?? '')),
                ];
            });

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        }

        return view('invoice.index', compact(
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
        $customerCode = trim((string) $request->input('customer_code', $request->input('fcustno', '')));
        $onlyRemaining = $request->boolean('only_remaining');

        $query = DB::table('tranmt as mt')
            ->leftJoin('mscustomer as c', 'c.fcustomercode', '=', 'mt.fcustno')
            ->leftJoin('mscabang as cb', 'cb.fcabangkode', '=', 'mt.fbranchcode')
            ->where('mt.ftrcode', 'INV')
            ->where('mt.fprdout', '0')
            ->select(
                'mt.ftranmtid',
                'mt.fsono',
                'mt.frefno',
                'mt.fsodate',
                'mt.fcustno',
                'mt.fbranchcode',
                'cb.fcabangname',
                'mt.ftrcode',
                'mt.fprdout',
                'c.fcustomername'
            );

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
            ->where('mt.ftrcode', 'INV')
            ->where('mt.fprdout', '0')
            ->when($customerCode !== '', function ($query) use ($customerCode) {
                $query->whereRaw('TRIM(COALESCE(mt.fcustno, \'\')) = ?', [$customerCode]);
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
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('mt.fsono', 'ilike', "%{$search}%")
                    ->orWhere('mt.frefno', 'ilike', "%{$search}%")
                    ->orWhere('mt.fcustno', 'ilike', "%{$search}%")
                    ->orWhere('c.fcustomername', 'ilike', "%{$search}%")
                    ->orWhere('mt.fbranchcode', 'ilike', "%{$search}%")
                    ->orWhere('cb.fcabangname', 'ilike', "%{$search}%");
            });
        }

        $recordsFiltered = $query->count();

        $orderColumn = $request->input('order_column', 'fsodate');
        $orderDir = $request->input('order_dir', 'desc');

        $allowedColumns = ['fsono', 'frefno', 'fsodate', 'fcustomername', 'fbranchcode', 'fcabangname'];
        if (in_array($orderColumn, $allowedColumns, true)) {
            if ($orderColumn === 'fcustomername') {
                $query->orderBy('c.fcustomername', $orderDir);
            } elseif ($orderColumn === 'fbranchcode') {
                $query->orderBy('mt.fbranchcode', $orderDir);
            } elseif ($orderColumn === 'fcabangname') {
                $query->orderBy('cb.fcabangname', $orderDir);
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

    public function items($id)
    {
        $header = DB::table('tranmt')
            ->leftJoin('mscustomer', 'mscustomer.fcustomercode', '=', 'tranmt.fcustno')
            ->where('tranmt.ftranmtid', $id)
            ->where('tranmt.ftrcode', 'INV')
            ->select('tranmt.*', 'mscustomer.fcustomername')
            ->firstOrFail();

        $items = DB::table('trandt as d')
            ->leftJoin('msprd as m', 'm.fprdcode', '=', 'd.fprdcode')
            ->where('d.fsono', $header->fsono)
            ->select([
                'd.ftrandtid as frefdtno',
                DB::raw("COALESCE(d.fnoacak::text, '') as frefnoacak"),
                'd.fprdcode as fitemcode',
                'm.fprdname as fitemname',
                'd.fqty',
                'd.fqtyremain',
                'd.fsatuan',
                'd.fprice as fharga',
                'd.famount as ftotal',
                'd.fdesc',
            ])
            ->orderBy('d.fnou')
            ->get();

        return response()->json([
            'header' => [
                'ftranmtid' => $header->ftranmtid,
                'fsono' => $header->fsono,
                'fcustno' => trim((string) ($header->fcustno ?? '')),
                'fcustomername' => trim((string) ($header->fcustomername ?? '')),
                'fsodate' => ! empty($header->fsodate) ? Carbon::parse($header->fsodate)->format('Y-m-d H:i:s') : null,
                'ftrcode' => trim((string) ($header->ftrcode ?? 'INV')),
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

        if (in_array($sourceCode, ['S', 'SO'], true)) {
            return [
                'frefnosoacak' => $normalized,
                'frefnoacak' => $normalized,
            ];
        }

        if (in_array($sourceCode, ['R', 'SRJ'], true)) {
            return [
                'frefnosoacak' => null,
                'frefnoacak' => $normalized,
            ];
        }

        return [
            'frefnosoacak' => null,
            'frefnoacak' => null,
        ];
    }

    private function isDocumentSrj(?string $docNo): bool
    {
        $docNo = strtoupper(trim((string) ($docNo ?? '')));
        return strpos($docNo, 'SRJ.') === 0 || strpos($docNo, 'SJ.') === 0;
    }

    private function resolveInvoiceReferenceSourceDetail(string $sourceCode, string $docNo, string $productCode, $refNoAcak = null): ?object
    {
        $sourceCode = strtoupper(trim($sourceCode));
        $docNo = trim($docNo);
        $productCode = trim($productCode);
        $normalizedRefNoAcak = $this->normalizeReferenceRandomNumbers($refNoAcak);

        if ($docNo === '' || $productCode === '') {
            return null;
        }

        if (in_array($sourceCode, ['S', 'SO'], true)) {
            return DB::table('trsodt')
                ->where('fsono', $docNo)
                ->where('fprdcode', $productCode)
                ->when($normalizedRefNoAcak !== null, function ($query) use ($normalizedRefNoAcak) {
                    $query->where('fnoacak', $normalizedRefNoAcak);
                })
                ->orderBy('ftrsodtid')
                ->first(['fsatuan', 'fqty', 'fqtykecil']);
        }

        if (in_array($sourceCode, ['R', 'SRJ'], true)) {
            return DB::table('trstockdt')
                ->where('fstockmtno', $docNo)
                ->where('fprdcode', $productCode)
                ->when($normalizedRefNoAcak !== null, function ($query) use ($normalizedRefNoAcak) {
                    $query->where('fnoacak', $normalizedRefNoAcak);
                })
                ->orderBy('fstockdtid')
                ->first(['fsatuan', 'fqty', 'fqtykecil', 'frefso', 'fnoacak']);
        }

        return null;
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

        // kunci per (branch, tahun-bulan) ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â TANPA bikin tabel baru
        $lockKey = crc32('PO|' . $kodeCabang . '|' . $date->format('Y-m'));
        DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

        $last = DB::table('tranmt')
            ->where('fsono', 'like', $prefix . '%')
            ->selectRaw("MAX(CAST(split_part(fsono, '.', 5) AS int)) AS lastno")
            ->value('lastno');

        $next = (int) $last + 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function print(string $fsono)
    {
        $fsono = trim($fsono);

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
            return redirect()->back()->with('error', 'Faktur penjualan tidak ada.');
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

        return view('invoice.print', [
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
            ->get(['fcustomerid', 'fcustomercode', 'fcustomername', 'fkodefp', 'fsalesman']);

        $salesmans = Salesman::orderBy('fsalesmanname', 'asc')
            ->get(['fsalesmanid', 'fsalesmancode', 'fsalesmanname']);

        $raw = (Auth::guard('sysuser')->user() ?? Auth::user())?->fcabang;

        $branch = DB::table('mscabang')
            ->when(is_numeric($raw), fn($q) => $q->where('fcabangid', (int) $raw))
            ->when(
                ! is_numeric($raw),
                fn($q) => $q->where('fcabangkode', $raw)->orWhere('fcabangname', $raw)
            )
            ->first(['fcabangid', 'fcabangkode', 'fcabangname']);

        $canApproval = $this->canApproveCreditLimit();

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

        return view('invoice.create', [
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
            'autoLoadSuratJalanId' => $request->query('surat_jalan_id'),
        ]);
    }

    public function store(Request $request)
    {
        $shouldSendApprovalNotification = false;
        $needsApprovalNotification = $this->shouldRequestInvoiceApproval($request);
        // 1. VALIDASI (Tetap sama)
        $request->validate([
            'fsodate' => ['required', 'date'],
            'fjatuhtempo' => ['nullable', 'date'],
            'fcustno' => ['required', 'string', 'max:10'],
            'frefno' => ['nullable', 'string', 'max:100'],
            'ftypesales' => ['required', 'in:0,1'],
            'fketinternal' => ['nullable', 'string', 'max:300'],
            'fitemcode' => ['required', 'array', 'min:1'],
            'fitemcode.*' => ['required', 'string', 'max:30'],
            'fqty' => ['required', 'array'],
            'fqty.*' => ['numeric', 'min:0.01'],
            'fprice' => ['required', 'array'],
            'fprice.*' => ['numeric', 'min:0'],
            'fdisc' => ['nullable', 'array'],
            'fdiscpersen' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'frefcode' => ['nullable', 'array'],
            'frefcode.*' => ['nullable', 'string', 'max:30'],
            'frefso' => ['nullable'],
            'frefsrj' => ['nullable'],
            'fnoacak' => ['nullable', 'array'],
            'fnoacak.*' => ['nullable', 'regex:/^[1-9]{3}$/'],
            'frefnoacak' => ['nullable', 'array'],
            'frefnoacak.*' => ['nullable', 'regex:/^\d{3}$/'],
        ], [
            'fsodate.required' => 'Tanggal Faktur Penjualan wajib diisi.',
            'fcustno.required' => 'Customer wajib diisi.',
            'fitemcode.required' => 'Minimal harus ada 1 item barang.',
        ]);

        $normalizedDetailPayload = $this->normalizeInvoiceDetailPayload([
            'fitemcode' => $request->input('fitemcode', []),
            'fitemname' => $request->input('fitemname', []),
            'fsatuan'   => $request->input('fsatuan', []),
            'frefdtno'  => $request->input('frefdtno', []),
            'frefcode'  => $request->input('frefcode', []),
            'fnouref'   => $request->input('fnouref', []),
            'frefpr'    => $request->input('frefpr', []),
            'frefso'    => $request->input('frefso', []),
            'frefsrj'   => $request->input('frefsrj', []),
            'fnoacak'   => $request->input('fnoacak', []),
            'frefnoacak' => $request->input('frefnoacak', []),
            'fqty'      => $request->input('fqty', []),
            'fprice'    => $request->input('fprice', []),
            'fdisc'     => $request->input('fdisc', []),
            'ftotal'    => $request->input('ftotal', []),
            'fdesc'     => $request->input('fdesc', []),
            'fketdt'    => $request->input('fketdt', []),
        ]);
        $request->merge($normalizedDetailPayload);
        $this->ensureNoDuplicateDetailCodes(
            $normalizedDetailPayload['fitemcode'] ?? [],
            $normalizedDetailPayload
        );

        // 2. INISIALISASI DATA HEADER (Tetap sama)
        $fsodate = Carbon::parse($request->fsodate);
        $fjatuhtempo = $request->input('fjatuhtempo') ? Carbon::parse($request->input('fjatuhtempo'))->startOfDay() : null;
        $this->ensureCreateDateWithinEditPeriod($fsodate);
        $fincludeppn = $request->boolean('fincludeppn') ? '1' : '0';
        $fapplyppn = $request->boolean('fapplyppn') ? '1' : '0';
        $ppnPersen = $request->input('fppnpersen', 0);
        $headerDiscPercent = max(0, min(100, (float) $request->input('fdiscpersen', 0)));
        $userid = mb_substr(auth('sysuser')->user()->fname ?? 'admin', 0, 10);
        $now = now();
        $fcurrency = $request->input('fcurrency', 'IDR');
        $frate = (float) $request->input('frate', 1);
        $fkodefp = trim((string) $request->input('fkodefp', ''));
        if ($fkodefp === '') {
            $fkodefp = (string) ($this->getCustomerTaxCode((string) $request->input('fcustno', '')) ?? '');
        }

        // 3. PROSES DETAIL (ARRAY)
        $itemCodes = $request->input('fitemcode', []);
        $typeSales = (int) $request->input('ftypesales');
        $itemDescs = $request->input('fitemname', []);
        $satuans = $request->input('fsatuan', []);
        $qtys = $request->input('fqty', []);
        $prices = $request->input('fprice', []);
        $discs = $request->input('fdisc', []);

        $frefso = $request->input('frefso', []);
        $frefsrj = $request->input('frefsrj', []);
        $fnoacaks = $request->input('fnoacak', []);
        $frefnoacaks = $request->input('frefnoacak', []);

        $detailRows = [];
        $totalGross = 0;
        $totalDisc = 0;
        $usedNoAcaks = [];

        // Logika UM (Tetap sama)
        $hasUM = in_array('UM', $itemCodes);
        if ($hasUM && $typeSales === 0) {
            return back()->withInput()->with('error', 'Produk UM hanya bisa dipakai pada transaksi Uang Muka.');
        }
        if (! $hasUM && $typeSales === 1) {
            return back()->withInput()->with('error', 'Transaksi Uang Muka harus memakai produk UM.');
        }

        $productCodes = collect($itemCodes)
            ->map(fn($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $products = DB::table('msprd')
            ->whereIn('fprdcode', $productCodes)
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
                'fhpp',
            ])
            ->keyBy('fprdcode');

        $totalSalesNet = 0.0;
        foreach ($itemCodes as $i => $code) {
            $code = trim((string) $code);
            $qty = (float) ($qtys[$i] ?? 0);
            $price = (float) ($prices[$i] ?? 0);
            if (empty($code) || $qty <= 0) {
                continue;
            }

            $refSoNo = trim((string) ($frefso[$i] ?? ''));
            $refSrjNo = trim((string) ($frefsrj[$i] ?? ''));
            if ($refSrjNo !== '') {
                $refSoNo = ''; // Will resolve from trstockdt later
            } elseif ($refSoNo !== '') {
                $refSrjNo = $refSoNo;
            }

            $frefcodeVal = 'INV';
            if ($refSrjNo !== '') {
                if ($this->isDocumentSrj($refSrjNo)) {
                    $frefcodeVal = 'SRJ';
                } else {
                    $frefcodeVal = 'SO';
                }
            }
            $refCode = $frefcodeVal;

            $fnoacakVal = $this->normalizeRandomNumber($fnoacaks[$i] ?? null, $usedNoAcaks);

            $product = $products->get($code);
            if (! $product) {
                return back()->withInput()->with('error', "Produk {$code} tidak ada.");
            }

            if ($product->fnonactive == '1') {
                return back()->withInput()->with('error', "Produk {$product->fprdname} sudah tidak tersedia.");
            }

            $sat = trim((string) ($satuans[$i] ?? ''));

            $referenceRatio = null;
            $referenceDetail = null;
            $frefnoacakVal = $frefnoacaks[$i] ?? null;
            if ($refSrjNo !== '') {
                $referenceDetail = $this->resolveInvoiceReferenceSourceDetail('SRJ', $refSrjNo, $code, $frefnoacaks[$i] ?? null);
                if ($referenceDetail && ! empty($referenceDetail->frefso)) {
                    $refSoNo = trim((string) $referenceDetail->frefso);
                } else {
                    $refSoNo = '';
                }
                if ($referenceDetail && ! empty($referenceDetail->fnoacak)) {
                    $frefnoacakVal = trim((string) $referenceDetail->fnoacak);
                }
            } elseif ($refSoNo !== '') {
                $referenceDetail = $this->resolveInvoiceReferenceSourceDetail('SO', $refSoNo, $code, $frefnoacaks[$i] ?? null);
            }
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

            if ($sat === '' && $product) {
                foreach (['fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'] as $k) {
                    $v = trim((string) ($product->$k ?? ''));
                    if ($v !== '') {
                        $sat = mb_substr($v, 0, 5);
                        break;
                    }
                }
            }

            $qtyKecil = $qty;
            if ($referenceRatio !== null && $referenceRatio > 0) {
                $qtyKecil = $qty * $referenceRatio;
            } elseif ($product && $sat === trim((string) ($product->fsatuanbesar ?? '')) && (float) ($product->fqtykecil ?? 0) > 0) {
                $qtyKecil = $qty * (float) $product->fqtykecil;
            } elseif ($product && $sat === trim((string) ($product->fsatuanbesar2 ?? '')) && (float) ($product->fqtykecil2 ?? 0) > 0) {
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
                'fsono' => '', // Akan diisi di dalam transaksi
                'fnou' => $i + 1,
                'fprdcode' => mb_substr($code, 0, 30),
                'fdesc' => $itemDescs[$i] ?? '',
                'fqty' => $qty,
                'fqtykecil' => $qtyKecil,
                'fqtyremain' => $qtyKecil,
                'fhpp' => (float) ($product->fhpp ?? 0),
                'fprice' => $price,
                'fprice_rp' => $price * $frate,
                'fdisc' => $discRaw,
                'fpricenet' => $netPrice,
                'fpricenet_rp' => $netPrice * $frate,
                'fsalesnet' => $fsalesnet,
                'famount' => $amountRow,
                'famount_rp' => $amountRow * $frate,
                'fsatuan' => mb_substr($sat, 0, 5),
                'fuserid' => $userid,
                'fdatetime' => $now,
                'frefcode' => $refCode,
                'frefso'  => $refSoNo,
                'frefsrj' => $refSrjNo,
                'fnoacak' => $fnoacakVal,
            ], $this->buildReferenceRandomNumberColumns($refCode, $frefnoacakVal));
        }

        [$soUsageByReference, $srjUsageByReference] = $this->buildInvoiceReferenceUsageMaps($detailRows);

        if ($validationMessage = $this->validateReferenceUsage($soUsageByReference, $srjUsageByReference)) {
            return back()->withInput()->with('error', $validationMessage);
        }

        $srjReferenceDocs = collect($detailRows)
            ->pluck('frefsrj')
            ->map(fn($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $headerRefNo = trim((string) $request->input('frefno', ''));

        $amountNetBeforeHeaderDisc = $totalGross - $totalDisc;
        $headerDiscountAmount = $amountNetBeforeHeaderDisc * ($headerDiscPercent / 100);
        $totalDisc += $headerDiscountAmount;
        $amountNet = $amountNetBeforeHeaderDisc - $headerDiscountAmount;
        $ppnPersen = (float) $request->input('fppnpersen', 11);
        $ppnAmount = ($fincludeppn === '1') ? ($amountNet * ($ppnPersen / 100)) : 0;
        $grandTotal = $amountNet + $ppnAmount;

        // if ($validationMessage = $this->validateReverseJournalBaseAmount($srjReferenceDocs, $grandTotal * $frate)) {
        //     return back()->withInput()->with('error', $validationMessage);
        // }

        // if ($validationMessage = $this->validateInventoryBaseAmount($srjReferenceDocs)) {
        //     return back()->withInput()->with('error', $validationMessage);
        // }

        // if ($validationMessage = $this->validateAdvanceReductionAmount($srjReferenceDocs)) {
        //     return back()->withInput()->with('error', $validationMessage);
        // }

        $creditApproval = $this->resolveInvoiceCreditApproval($request, $grandTotal);
        $fsono = trim((string) $request->input('fsono', ''));
        $hasSrjReference = ! empty($srjReferenceDocs);

        // 5. DATABASE TRANSACTION
        try {
            $ftranmtid = null;

            DB::transaction(function () use ($fapplyppn, $request, $fsodate, $fincludeppn, $userid, $now, $detailRows, $totalGross, $totalDisc, $amountNet, $ppnAmount, $grandTotal, $fcurrency, $frate, $ppnPersen, $creditApproval, $fkodefp, $needsApprovalNotification, &$shouldSendApprovalNotification, &$fsono, &$ftranmtid, $headerDiscPercent, $totalSalesNet, $fjatuhtempo, $headerRefNo) {

                // Penomoran Otomatis
                if (empty($fsono)) {
                    $isAdvancePayment = (int) $request->input('ftypesales', 0) === 1;
                    $branchCode = trim((string) ($request->fbranchcode ?? 'BG')) ?: 'BG';
                    $prefix = $isAdvancePayment ? sprintf('UM.%s.', $branchCode) : 'INV.' . $fsodate->format('ym') . '.';
                    $digits = $isAdvancePayment ? 3 : 4;
                    $lastRecord = DB::table('tranmt')->where('fsono', 'like', $prefix . '%')->orderBy('fsono', 'desc')->lockForUpdate()->first();
                    $nextNumber = $lastRecord ? ((int) substr(trim($lastRecord->fsono), -$digits) + 1) : 1;
                    $fsono = $prefix . str_pad((string) $nextNumber, $digits, '0', STR_PAD_LEFT);
                }

                $approvalState = $this->initializeApprovalState();

                foreach ($detailRows as $detail) {
                    $prdCode = $detail['fprdcode'];
                    $refCode = $detail['frefcode'];
                    if ($refCode === 'SRJ') {
                        $fprdoutVal = '1';
                        break;
                    } elseif ($prdCode === 'AWAL') {
                        $fprdoutVal = '1';
                        break;
                    } elseif ($prdCode === 'UM') {
                        $fprdoutVal = '1';
                        break;
                    } else {
                        $fprdoutVal = '0';
                    }
                }

                $headerInsert = [
                    'ftaxno' => mb_substr($fsono, 0, 50),
                    'fsono' => $fsono,
                    'fsodate' => $fsodate,
                    'fcustno' => mb_substr($request->fcustno, 0, 10),
                    'fkodefp' => $fkodefp,
                    'fsalesman' => mb_substr((string) $request->input('fsalesman', ''), 0, 30),
                    'fdiscpersen' => round($headerDiscPercent, 2),
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
                    'frefno' => mb_substr($headerRefNo, 0, 100),
                    'fuserid' => $userid,
                    'fdatetime' => $now,
                    'fincludeppn' => $fincludeppn,
                    'fapplyppn' => $fapplyppn,
                    'fppnpersen' => $ppnPersen,
                    'ftypesales' => $request->input('ftypesales', 0),
                    'fbranchcode' => $request->fbranchcode,
                    'ftrcode' => 'INV',
                    'fprdout' => $fprdoutVal,
                    'fneedacc' => $needsApprovalNotification ? '1' : '0',
                    'fuseracc' => $creditApproval['fuseracc'],
                    'fprint' => 0,
                    'fjatuhtempo' => $fjatuhtempo,
                    ...$approvalState,
                ];
                if ($this->tranmtHasInternalNoteColumn()) {
                    $headerInsert['fketinternal'] = mb_substr((string) $request->input('fketinternal', ''), 0, 300);
                }
                $ftranmtid = DB::table('tranmt')->insertGetId($headerInsert, 'ftranmtid');

                // --- UPDATE DETAIL ROWS DENGAN ID HEADER DAN NOMOR SONO ---
                foreach ($detailRows as &$row) {
                    $row['fsono'] = $fsono;
                }

                // INSERT DETAIL
                DB::table('trandt')->insert($detailRows);

                $this->syncInvoiceJournalEntries(
                    (string) $fsono,
                    $fsodate,
                    (string) ($request->fbranchcode ?? 'BG'),
                    (string) $request->fcustno,
                    (string) $userid
                );

                $shouldSendApprovalNotification = $needsApprovalNotification
                    && ApprovalState::hasApprovalProgress((object) $approvalState);
            });

            if ($shouldSendApprovalNotification) {
                $this->sendApprovalNotification($fsono, $userid);
            }

            $redirect = redirect()->route('invoice.index')->with('success', 'Faktur penjualan ' . $this->formatDisplayTransactionNumber($fsono, $fincludeppn === '1') . ' berhasil disimpan.');

            if ($hasSrjReference || $needsApprovalNotification || ! $this->canCreateSuratJalan() || ! $ftranmtid) {
                return $redirect;
            }

            return $redirect->with('success_prompt', [
                'type' => 'invoice_create_suratjalan',
                'redirect_url' => route('suratjalan.create', ['invoice_id' => $ftranmtid]),
            ]);
        } catch (\Exception $e) {
            report($e);
            return back()->withInput()->with('error', 'Faktur penjualan belum bisa disimpan. Cek data.');
        }
    }

    // ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã¢â‚¬Å“ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¦ TAMBAHKAN METHOD HELPER UNTUK PARSE DISCOUNT
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

    /**
     * Sisa SO dinamis dalam satuan kecil.
     *
     * @param  array<int, int|string>  $soDetailIds
     * @return array<int, float>
     */
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
                'd.frefnosoacak',
                'd.frefnoacak',
                'd.fprdcode',
                'd.fsatuan',
                'p.fqtykecil',
                'p.fqtykecil2',
                'p.fsatuanbesar',
                'p.fsatuanbesar2',
            ]);

        $soStats = $this->getInvoiceReferenceStats(
            'SO',
            $rows->pluck('frefso')->filter()->map(fn($value) => trim((string) $value))->unique()->values()->all()
        );
        $srjStats = $this->getInvoiceReferenceStats(
            'SRJ',
            $rows->pluck('frefsrj')->filter()->map(fn($value) => trim((string) $value))->unique()->values()->all()
        );

        return $rows->keyBy('ftrandtid')->map(function ($row) use ($soStats, $srjStats) {
            $refCode = strtoupper(trim((string) ($row->frefcode ?? '')));
            $docNoToCheck = trim((string) ($row->frefsrj ?? $row->frefso ?? ''));
            $isSrj = $refCode === 'SRJ' || $this->isDocumentSrj($docNoToCheck);
            $docNo = trim((string) ($isSrj ? ($row->frefsrj ?? '') : ($row->frefso ?? '')));
            $refNoAcak = $this->normalizeReferenceRandomNumbers($isSrj ? ($row->frefnoacak ?? null) : ($row->frefnosoacak ?? null)) ?? '';
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
            $soStats = $this->getInvoiceReferenceStats('SO', $this->extractReferenceDocsFromUsageKeys(array_keys($soUsageByReference)), $exceptFsono);

            foreach ($soUsageByReference as $referenceKey => $qtyKecil) {
                $stat = $soStats[$referenceKey] ?? null;
                $available = max(0, (float) ($stat['remain_qty_kecil'] ?? 0));
                if ((float) $qtyKecil - $available > 0.000001) {
                    $label = trim((string) ($stat['product_name'] ?? $stat['product_code'] ?? $referenceKey));
                    $refno = trim((string) ($stat['ref_doc'] ?? ''));
                    $unit = trim((string) ($stat['source_unit'] ?? 'Qty'));
                    return "Warning\nProduk {$label} @" . number_format((float) $qtyKecil, 2, ',', '.') . " {$unit}\nMelebihi Qty Sales Order" . ($refno !== '' ? " ({$refno})" : '') . " !!!";
                }
            }
        }

        if (! empty($srjUsageByReference)) {
            $srjStats = $this->getInvoiceReferenceStats('SRJ', $this->extractReferenceDocsFromUsageKeys(array_keys($srjUsageByReference)), $exceptFsono);

            foreach ($srjUsageByReference as $referenceKey => $qtyKecil) {
                $stat = $srjStats[$referenceKey] ?? null;
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
            $soStats = $this->getInvoiceReferenceStats('SO', $this->extractReferenceDocsFromUsageKeys(array_keys($soUsageByReference)), $exceptFsono);
            foreach ($soUsageByReference as $referenceKey => $qtyKecil) {
                if ((float) ($soStats[$referenceKey]['used_qty_kecil'] ?? 0) > 0) {
                    $refNo = trim((string) ($soStats[$referenceKey]['ref_doc'] ?? ''));
                    $transactionNo = trim((string) ($soStats[$referenceKey]['used_by_transaction'] ?? ''));
                    return 'Referensi ' . $refNo . ' sudah dipakai di transaksi ' . $transactionNo . '.';
                }
            }
        }

        if (! empty($srjUsageByReference)) {
            $srjStats = $this->getInvoiceReferenceStats('SRJ', $this->extractReferenceDocsFromUsageKeys(array_keys($srjUsageByReference)), $exceptFsono);
            foreach ($srjUsageByReference as $referenceKey => $qtyKecil) {
                if ((float) ($srjStats[$referenceKey]['used_qty_kecil'] ?? 0) > 0) {
                    $refNo = trim((string) ($srjStats[$referenceKey]['ref_doc'] ?? ''));
                    $transactionNo = trim((string) ($srjStats[$referenceKey]['used_by_transaction'] ?? ''));
                    return 'Referensi ' . $refNo . ' sudah dipakai di transaksi ' . $transactionNo . '.';
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

    private function buildInvoiceReferenceUsageMaps(array $detailRows): array
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
            $soRefNoAcak = $this->normalizeReferenceRandomNumbers($row['frefnosoacak'] ?? null) ?? '';
            $srjRefNoAcak = $this->normalizeReferenceRandomNumbers($row['frefnoacak'] ?? null) ?? '';

            $isSrj = false;
            if ($srjDocNo !== '' && $this->isDocumentSrj($srjDocNo)) {
                $isSrj = true;
            } elseif ($soDocNo !== '' && $this->isDocumentSrj($soDocNo)) {
                $isSrj = true;
            }

            if ($isSrj) {
                $docNo = ($srjDocNo !== '') ? $srjDocNo : $soDocNo;
                $refNoAcak = ($srjRefNoAcak !== '') ? $srjRefNoAcak : $soRefNoAcak;
                $key = $this->buildReferenceUsageKey($docNo, $productCode, $refNoAcak);
                $srjUsage[$key] = ($srjUsage[$key] ?? 0) + $qtyKecil;
            } else {
                $docNo = ($soDocNo !== '') ? $soDocNo : $srjDocNo;
                $refNoAcak = ($soRefNoAcak !== '') ? $soRefNoAcak : $srjRefNoAcak;
                if ($docNo !== '') {
                    $key = $this->buildReferenceUsageKey($docNo, $productCode, $refNoAcak);
                    $soUsage[$key] = ($soUsage[$key] ?? 0) + $qtyKecil;
                }
            }
        }

        return [$soUsage, $srjUsage];
    }

    private function buildInvoiceReferenceRestoreMaps(string $fsono): array
    {
        $rows = DB::table('trandt as d')
            ->where('d.fsono', $fsono)
            ->get([
                'd.frefcode',
                'd.frefso',
                'd.frefsrj',
                'd.fprdcode',
                'd.frefnosoacak',
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
            $refCode = strtoupper(trim((string) ($row->frefcode ?? '')));
            $soDocNo = trim((string) ($row->frefso ?? ''));
            $srjDocNo = trim((string) ($row->frefsrj ?? ''));
            $soRefNoAcak = $this->normalizeReferenceRandomNumbers($row->frefnosoacak ?? null) ?? '';
            $srjRefNoAcak = $this->normalizeReferenceRandomNumbers($row->frefnoacak ?? null) ?? '';

            $isSrj = false;
            if ($srjDocNo !== '' && $this->isDocumentSrj($srjDocNo)) {
                $isSrj = true;
            } elseif ($soDocNo !== '' && $this->isDocumentSrj($soDocNo)) {
                $isSrj = true;
            }

            if ($isSrj) {
                $docNo = ($srjDocNo !== '') ? $srjDocNo : $soDocNo;
                $refNoAcak = ($srjRefNoAcak !== '') ? $srjRefNoAcak : $soRefNoAcak;
                $key = $this->buildReferenceUsageKey($docNo, $productCode, $refNoAcak);
                $srjRestore[$key] = ($srjRestore[$key] ?? 0) + $qtyKecil;
            } else {
                $docNo = ($soDocNo !== '') ? $soDocNo : $srjDocNo;
                $refNoAcak = ($soRefNoAcak !== '') ? $soRefNoAcak : $srjRefNoAcak;
                if ($docNo !== '') {
                    $key = $this->buildReferenceUsageKey($docNo, $productCode, $refNoAcak);
                    $soRestore[$key] = ($soRestore[$key] ?? 0) + $qtyKecil;
                }
            }

            if ($refCode === 'UM' && trim((string) ($row->frefso ?? '')) === '' && trim((string) ($row->frefsrj ?? '')) === '') {
                continue;
            }
        }

        return [$soRestore, $srjRestore];
    }

    private function getInvoiceReferenceStats(string $type, array $docNos, ?string $exceptFsono = null): array
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
            $sourceRows = DB::table('trsodt as d')
                ->leftJoin('msprd as p', 'p.fprdcode', '=', 'd.fprdcode')
                ->whereIn('d.fsono', $docNos)
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
            $key = $this->buildReferenceUsageKey($row->ref_doc ?? '', $row->product_code ?? '', $row->ref_noacak ?? '');
            $stats[$key] = [
                'ref_doc' => trim((string) ($row->ref_doc ?? '')),
                'product_code' => trim((string) ($row->product_code ?? '')),
                'product_name' => trim((string) ($row->product_name ?? '')),
                'source_unit' => trim((string) ($row->source_unit ?? '')),
                'source_qty_kecil' => (float) ($row->source_qty_kecil ?? 0),
                'used_qty_kecil' => 0.0,
                'remain_qty_kecil' => (float) ($row->remain_qty_kecil ?? $row->source_qty_kecil ?? 0),
                'used_by_transaction' => '',
            ];
        }

        foreach ($usageRows as $row) {
            $key = $this->buildReferenceUsageKey($row->ref_doc ?? '', $row->product_code ?? '', $row->ref_noacak ?? '');
            if (! isset($stats[$key])) {
                $stats[$key] = [
                    'ref_doc' => trim((string) ($row->ref_doc ?? '')),
                    'product_code' => trim((string) ($row->product_code ?? '')),
                    'product_name' => trim((string) ($row->product_code ?? '')),
                    'source_unit' => '',
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

    private function restoreInvoiceReferenceUsage(array $soRestoreByReference, array $srjRestoreByReference): void
    {
        if (! empty($soRestoreByReference)) {
            $docNos = $this->extractReferenceDocsFromUsageKeys(array_keys($soRestoreByReference));
            $sourceRows = DB::table('trsodt as d')
                ->whereIn('d.fsono', $docNos)
                ->selectRaw("
                    d.ftrsodtid,
                    COALESCE(d.fqtykecil, 0) as source_qty_kecil,
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
        $qtyKecilBase = (float) ($productRow->fqtykecil ?? 0);
        $qtyKecilBase2 = (float) ($productRow->fqtykecil2 ?? 0);

        if ($unit !== '' && $unit === trim((string) ($productRow->fsatuanbesar ?? '')) && $qtyKecilBase > 0) {
            return $qtyKecil / $qtyKecilBase;
        }

        if ($unit !== '' && $unit === trim((string) ($productRow->fsatuanbesar2 ?? '')) && $qtyKecilBase2 > 0) {
            return $qtyKecil / $qtyKecilBase2;
        }

        return $qtyKecil;
    }

    public function edit(Request $request, $ftranmtid)
    {
        $customers = Customer::orderBy('fcustomername', 'asc')
            ->get(['fcustomerid', 'fcustomercode', 'fcustomername', 'fkodefp', 'fsalesman']);

        $salesmans = Salesman::orderBy('fsalesmanname', 'asc')
            ->get(['fsalesmanid', 'fsalesmancode', 'fsalesmanname']);

        $invoice = Tranmt::with(['customer', 'details' => function ($q) {
            $q->leftJoin('msprd', 'msprd.fprdcode', '=', 'trandt.fprdcode')
                ->leftJoin('trsomt as so_hdr', 'so_hdr.fsono', '=', 'trandt.frefso')
                ->leftJoin('trstockmt as sj_hdr', 'sj_hdr.fstockmtno', '=', 'trandt.frefsrj')
                ->select(
                    'trandt.*',
                    'msprd.fprdcode as fitemcode',
                    'msprd.fprdname',
                    'so_hdr.fsono as fsono_ref',
                    'sj_hdr.fstockmtno as fstockno_ref'
                )
                ->orderBy('trandt.ftrandtid', 'asc');
        }])->findOrFail($ftranmtid);

        if ($message = $this->getPostedPeriodLockMessage($invoice->fsodate, 'Faktur ini')) {
            return redirect()->route('invoice.view', $invoice->ftranmtid)->with('error', $message);
        }

        if ($message = $this->getApprovalLockMessage($invoice)) {
            return redirect()->route('invoice.view', $invoice->ftranmtid)->with('error', $message);
        }

        if (! $invoice->customer) {
            $invoice->setRelation('customer', Customer::where('fcustomercode', trim((string) $invoice->fcustno))->first());
        }

        ['fcabang' => $fcabang, 'fbranchcode' => $fbranchcode] = $this->resolveBranchContext($invoice->fbranchcode ?? null);

        $usageLockMessage = $this->getUsageLockMessage($invoice);

        if (! empty($usageLockMessage)) {
            return redirect()->route('invoice.view', $invoice->ftranmtid)->with('error', $usageLockMessage);
        }

        $referenceSummary = $this->getReferenceSummaryByTranNo((string) $invoice->fsono);

        $savedItems = $invoice->details->map(function ($d) use ($referenceSummary) {
            $refCode = trim($d->frefcode ?? '');
            if (empty($refCode)) {
                if (! empty(trim($d->frefso ?? ''))) {
                    $refCode = 'SO';
                }
                if (! empty(trim($d->frefsrj ?? ''))) {
                    $refCode = 'SRJ';
                }
            }

            // SO lines store the ref in frefso; SRJ lines in frefsrj (frefso is often '' not null, so ?? alone is wrong).
            $trimSo = trim($d->frefso ?? '');
            $trimSrj = trim($d->frefsrj ?? '');
            $detailRef = $trimSo !== '' ? $trimSo : ($trimSrj !== '' ? $trimSrj : '');

            // Priority: Joined Header Number -> Stored Detail String -> Type Prefix
            $refNoDisplay = $d->fsono_ref ?? ($d->fstockno_ref ?? ($detailRef !== '' ? $detailRef : $refCode));

            $summary = $referenceSummary[(int) ($d->ftrandtid ?? 0)] ?? ['fqtyterinvoice' => 0, 'fqtysisa_ref' => 0];
            $maxqty = max(0.0, (float) ($d->fqty ?? 0) + (float) ($summary['fqtysisa_ref'] ?? 0));

            return [
                'uid' => $d->ftrandtid,
                'fitemcode' => trim($d->fitemcode ?? ''),
                'fitemname' => trim($d->fprdname ?? ''),
                'fsatuan' => trim($d->fsatuan ?? ''),
                'fdisplayunit' => trim($d->fsatuan ?? ''),
                'frefdtno' => (string) ($d->frefdtno ?? ''),
                'frefcode' => $refCode,
                'frefso' => trim($d->frefso ?? ''),
                'frefsrj' => trim($d->frefsrj ?? ''),
                'fnoacak' => (string) ($d->fnoacak ?? ''),
                'frefnoacak' => (string) ($d->frefnoacak ?? ''),
                'frefno_display' => $refNoDisplay,
                'fqty' => (float) ($d->fqty ?? 0),
                'fterima' => (float) ($d->fterima ?? 0),
                'fprice' => (float) ($d->fprice ?? 0),
                'fdisc' => $this->normalizeDiscountInput($d->fdisc ?? 0),
                'ftotal' => (float) ($d->famount ?? 0),
                'fdesc' => (string) ($d->fdesc ?? ''),
                'frefpr' => $refNoDisplay,
                'fsono_ref' => trim($d->fsono_ref ?? ''),
                'fstockno_ref' => trim($d->fstockno_ref ?? ''),
                'fketdt' => (string) ($d->fketdt ?? ''),
                'fqtyremain' => $maxqty,
                'maxqty' => $maxqty,
                'fqtyterinvoice' => (float) ($summary['fqtyterinvoice'] ?? 0),
                'fqtysisa_ref' => (float) ($summary['fqtysisa_ref'] ?? 0),
            ];
        })->values();
        $selectedSupplierCode = $invoice->fsupplier;

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
        return view('invoice.edit', [
            'customers' => $customers,
            'salesmans' => $salesmans,
            'selectedSupplierCode' => $selectedSupplierCode, // Kirim kode supplier ke view
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'products' => $products,
            'productMap' => $productMap,
            'invoice' => $invoice,
            'displayFsono' => $this->formatDisplayTransactionNumber($invoice->fsono ?? null, (string) ($invoice->fincludeppn ?? '0') === '1'),
            'savedItems' => $savedItems,
            'ppnAmount' => (float) ($invoice->famountpopajak ?? 0), // total PPN from DB
            'famountgross' => (float) ($invoice->famountgross ?? 0),  // nilai Grand Total dari DB
            'famountso' => (float) ($invoice->famountso ?? 0),  // nilai Grand Total dari DB
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
            ->get(['fcustomerid', 'fcustomercode', 'fcustomername']);

        $salesmans = Salesman::orderBy('fsalesmanname', 'asc')
            ->get(['fsalesmanid', 'fsalesmancode', 'fsalesmanname']);

        $invoice = Tranmt::with(['customer', 'details' => function ($q) {
            $q->leftJoin('msprd', 'msprd.fprdcode', '=', 'trandt.fprdcode')
                ->leftJoin('trsomt as so_hdr', 'so_hdr.fsono', '=', 'trandt.frefso')
                ->leftJoin('trstockmt as sj_hdr', 'sj_hdr.fstockmtno', '=', 'trandt.frefsrj')
                ->select(
                    'trandt.*',
                    'msprd.fprdcode as fitemcode',
                    'msprd.fprdname',
                    'so_hdr.fsono as fsono_ref',
                    'sj_hdr.fstockmtno as fstockno_ref'
                )
                ->orderBy('trandt.ftrandtid', 'asc');
        }])->findOrFail($ftranmtid);

        if (! $invoice->customer) {
            $invoice->setRelation('customer', Customer::where('fcustomercode', trim((string) $invoice->fcustno))->first());
        }

        ['fcabang' => $fcabang, 'fbranchcode' => $fbranchcode] = $this->resolveBranchContext($invoice->fbranchcode ?? null);

        $approvalLockMessage = $this->getApprovalLockMessage($invoice);

        $referenceSummary = $this->getReferenceSummaryByTranNo((string) $invoice->fsono);

        $savedItems = $invoice->details->map(function ($d) use ($referenceSummary) {
            $trimSo = trim($d->frefso ?? '');
            $trimSrj = trim($d->frefsrj ?? '');
            $detailRef = $trimSo !== '' ? $trimSo : ($trimSrj !== '' ? $trimSrj : '');
            $refNoDisplay = $d->fsono_ref ?? ($d->fstockno_ref ?? ($detailRef !== '' ? $detailRef : ($d->frefcode ?? '-')));
            $summary = $referenceSummary[(int) ($d->ftrandtid ?? 0)] ?? ['fqtyterinvoice' => 0, 'fqtysisa_ref' => 0];

            return [
                'uid' => $d->ftrandtid,
                'fitemcode' => (string) ($d->fitemcode ?? ''),
                'fitemname' => (string) ($d->fprdname ?? ''),
                'fsatuan' => trim((string) ($d->fsatuan ?? '')),
                'fdisplayunit' => trim((string) ($d->fsatuan ?? '')),
                'frefdtno' => (string) ($d->frefdtno ?? ''),
                'fqty' => (float) ($d->fqty ?? 0),
                'fterima' => (float) ($d->fterima ?? 0),
                'fprice' => (float) ($d->fprice ?? 0),
                'fdisc' => $this->normalizeDiscountInput($d->fdisc ?? 0),
                'ftotal' => (float) ($d->famount ?? 0),
                'fdesc' => (string) ($d->fdesc ?? ''),
                'frefcode' => (string) ($d->frefcode ?? ''),
                'fnoacak' => (string) ($d->fnoacak ?? ''),
                'frefnoacak' => (string) ($d->frefnoacak ?? ''),
                'frefno_display' => $refNoDisplay,
                'fketdt' => (string) ($d->fketdt ?? ''),
                'fqtyterinvoice' => (float) ($summary['fqtyterinvoice'] ?? 0),
                'fqtysisa_ref' => (float) ($summary['fqtysisa_ref'] ?? 0),
            ];
        })->values();
        $selectedSupplierCode = $invoice->fsupplier;

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
        return view('invoice.edit', [
            'customers' => $customers,
            'salesmans' => $salesmans,
            'selectedSupplierCode' => $selectedSupplierCode, // Kirim kode supplier ke view
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'products' => $products,
            'productMap' => $productMap,
            'invoice' => $invoice,
            'displayFsono' => $this->formatDisplayTransactionNumber($invoice->fsono ?? null, (string) ($invoice->fincludeppn ?? '0') === '1'),
            'savedItems' => $savedItems,
            'approvalLockMessage' => $approvalLockMessage,
            'ppnAmount' => (float) ($invoice->famountpopajak ?? 0), // total PPN from DB
            'famountgross' => (float) ($invoice->famountgross ?? 0),  // nilai Grand Total dari DB
            'famountso' => (float) ($invoice->famountso ?? 0),  // nilai Grand Total dari DB
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'filterSalesmanId' => $request->query('filter_salesman_id'),
            'isUsageLocked' => false,
            'usageLockMessage' => null,
            'action' => 'view',
        ]);
    }

    public function update(Request $request, $ftranmtid)
    {
        $shouldSendApprovalNotification = false;
        $needsApprovalNotification = $this->shouldRequestInvoiceApproval($request);
        // 1. VALIDASI
        $request->validate([
            'fsodate' => ['required', 'date'],
            'fjatuhtempo' => ['nullable', 'date'],
            'fcustno' => ['required', 'string', 'max:10'],
            'frefno' => ['nullable', 'string', 'max:100'],
            'ftypesales' => ['required', 'in:0,1'],
            'ftaxno' => ['nullable', 'string', 'max:50'],
            'fketinternal' => ['nullable', 'string', 'max:300'],
            'fitemcode' => ['required', 'array', 'min:1'],
            'fitemcode.*' => ['required', 'string', 'max:30'],
            'fqty' => ['required', 'array'],
            'fqty.*' => ['numeric', 'min:0.01'],
            'fprice' => ['required', 'array'],
            'fprice.*' => ['numeric', 'min:0'],
            'frefcode' => ['nullable', 'array'],
            'frefcode.*' => ['nullable', 'string', 'max:30'],
            'fdiscpersen' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fnoacak' => ['nullable', 'array'],
            'fnoacak.*' => ['nullable', 'regex:/^[1-9]{3}$/'],
            'frefnoacak' => ['nullable', 'array'],
            'frefnoacak.*' => ['nullable', 'regex:/^\d{3}$/'],
        ]);

        $normalizedDetailPayload = $this->normalizeInvoiceDetailPayload([
            'fitemcode' => $request->input('fitemcode', []),
            'fitemname' => $request->input('fitemname', []),
            'fsatuan'   => $request->input('fsatuan', []),
            'frefdtno'  => $request->input('frefdtno', []),
            'frefcode'  => $request->input('frefcode', []),
            'fnouref'   => $request->input('fnouref', []),
            'frefpr'    => $request->input('frefpr', []),
            'frefso'    => $request->input('frefso', []),
            'frefsrj'   => $request->input('frefsrj', []),
            'fnoacak'   => $request->input('fnoacak', []),
            'frefnoacak' => $request->input('frefnoacak', []),
            'fqty'      => $request->input('fqty', []),
            'fprice'    => $request->input('fprice', []),
            'fdisc'     => $request->input('fdisc', []),
            'ftotal'    => $request->input('ftotal', []),
            'fdesc'     => $request->input('fdesc', []),
            'fketdt'    => $request->input('fketdt', []),
        ]);
        $request->merge($normalizedDetailPayload);
        $this->ensureNoDuplicateDetailCodes(
            $normalizedDetailPayload['fitemcode'] ?? [],
            $normalizedDetailPayload
        );

        // 2. LOAD HEADER
        $header = DB::table('tranmt')->where('ftranmtid', $ftranmtid)->first();
        if (! $header) {

            return abort(404, 'Faktur penjualan tidak ada.');
        }
        if ($message = $this->getPostedPeriodLockMessage($header->fsodate, 'Faktur ini')) {
            return redirect()->route('invoice.view', $ftranmtid)->with('error', $message);
        }
        if ($message = $this->getApprovalLockMessage((object) $header)) {
            return redirect()->route('invoice.view', $ftranmtid)->with('error', $message);
        }

        if ($message = $this->getUsageLockMessage((object) $header)) {
            return redirect()->route('invoice.index')->with('error', $message);
        }

        // 3. INISIALISASI DATA
        $fsodate = Carbon::parse($request->fsodate);
        $fjatuhtempo = $request->input('fjatuhtempo') ? Carbon::parse($request->input('fjatuhtempo'))->startOfDay() : null;
        $this->ensureCreateDateWithinEditPeriod($fsodate, $header->fsodate);
        $fincludeppn = $request->boolean('fincludeppn') ? '1' : '0';
        $fapplyppn = $request->boolean('fapplyppn') ? '1' : '0';
        $headerDiscPercent = max(0, min(100, (float) $request->input('fdiscpersen', 0)));
        $userid = mb_substr(auth('sysuser')->user()->fname ?? 'admin', 0, 10);
        $now = now();
        $frate = (float) $request->input('frate', $header->frate ?? 1);
        $ppnPersen = (float) $request->input('fppnpersen', 11);
        $fkodefp = trim((string) $request->input('fkodefp', ''));
        if ($fkodefp === '') {
            $fkodefp = (string) ($this->getCustomerTaxCode((string) $request->input('fcustno', '')) ?? '');
        }

        $itemCodes = $request->input('fitemcode', []);
        $typeSales = (int) $request->input('ftypesales');
        $itemDescs = $request->input('fitemname', []);
        $satuans = $request->input('fsatuan', []);
        $qtys = $request->input('fqty', []);
        $prices = $request->input('fprice', []);
        $discs = $request->input('fdisc', []);
        $frefcodes = $request->input('frefcode', []);
        $frefso = $request->input('frefso', []);
        $frefsrj = $request->input('frefsrj', []);
        $fnoacaks = $request->input('fnoacak', []);
        $frefnoacaks = $request->input('frefnoacak', []);
        // 4. BUILD DETAIL ROWS
        $detailRows = [];
        $totalGross = 0;
        $totalDisc = 0;
        $totalSalesNet = 0.0;
        $usedNoAcaks = [];

        $hasUM = in_array('UM', $itemCodes);
        if ($hasUM && $typeSales === 0) {

            return back()->withInput()->with('error', 'Produk UM hanya bisa dipakai pada transaksi Uang Muka.');
        }

        // Ambil data produk masal
        $productCodes = collect($itemCodes)
            ->map(fn($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $products = DB::table('msprd')
            ->whereIn('fprdcode', $productCodes)
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
                'fhpp',
            ])
            ->keyBy('fprdcode');

        foreach ($itemCodes as $i => $code) {
            $code = trim((string) $code);
            $qty = (float) ($qtys[$i] ?? 0);
            $price = (float) ($prices[$i] ?? 0);

            if (empty($code) || $qty <= 0) {

                continue;
            }

            $refSoNo = trim((string) ($frefso[$i] ?? ''));
            $refSrjNo = trim((string) ($frefsrj[$i] ?? ''));
            if ($refSrjNo !== '') {
                $refSoNo = ''; // Will resolve from trstockdt later
            } elseif ($refSoNo !== '') {
                $refSrjNo = $refSoNo;
            }

            $frefcodeVal = 'INV';
            if ($refSrjNo !== '') {
                if ($this->isDocumentSrj($refSrjNo)) {
                    $frefcodeVal = 'SRJ';
                } else {
                    $frefcodeVal = 'SO';
                }
            } elseif (is_array($frefcodes) && ! empty($frefcodes[$i])) {
                $frefcodeVal = $frefcodes[$i];
            }
            $refCode = $frefcodeVal;

            $fnoacakVal = $this->normalizeRandomNumber($fnoacaks[$i] ?? null, $usedNoAcaks);
            $product = $products->get($code);

            if (! $product) {

                return back()->withInput()->with('error', "Produk {$code} tidak ada.");
            }

            if ($product->fnonactive == '1') {
                return back()->withInput()->with('error', "Produk {$product->fprdname} sudah tidak tersedia.");
            }

            // Konversi Satuan
            $sat = trim((string) ($satuans[$i] ?? ''));

            $referenceRatio = null;
            $referenceDetail = null;
            $frefnoacakVal = $frefnoacaks[$i] ?? null;
            if ($refSrjNo !== '') {
                $referenceDetail = $this->resolveInvoiceReferenceSourceDetail('SRJ', $refSrjNo, $code, $frefnoacaks[$i] ?? null);
                if ($referenceDetail && ! empty($referenceDetail->frefso)) {
                    $refSoNo = trim((string) $referenceDetail->frefso);
                } else {
                    $refSoNo = '';
                }
                if ($referenceDetail && ! empty($referenceDetail->fnoacak)) {
                    $frefnoacakVal = trim((string) $referenceDetail->fnoacak);
                }
            } elseif ($refSoNo !== '') {
                $referenceDetail = $this->resolveInvoiceReferenceSourceDetail('SO', $refSoNo, $code, $frefnoacaks[$i] ?? null);
            }
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

            if ($sat === '' && $product) {
                foreach (['fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'] as $k) {
                    $v = trim((string) ($product->$k ?? ''));
                    if ($v !== '') {
                        $sat = mb_substr($v, 0, 5);
                        break;
                    }
                }
            }

            $qtyKecil = $qty;
            if ($referenceRatio !== null && $referenceRatio > 0) {
                $qtyKecil = $qty * $referenceRatio;
            } elseif ($product && $sat === trim((string) ($product->fsatuanbesar ?? '')) && (float) ($product->fqtykecil ?? 0) > 0) {
                $qtyKecil = $qty * (float) $product->fqtykecil;
            } elseif ($product && $sat === trim((string) ($product->fsatuanbesar2 ?? '')) && (float) ($product->fqtykecil2 ?? 0) > 0) {
                $qtyKecil = $qty * (float) $product->fqtykecil2;
            }

            // Kalkulasi Baris
            $discRaw = $this->normalizeDiscountInput($discs[$i] ?? 0);
            $discPersen = $this->parseDiscount($discRaw);
            $subtotal = $qty * $price;
            $discAmount = $subtotal * ($discPersen / 100);
            $netPrice = $price - ($price * ($discPersen / 100));
            $amountRow = $subtotal - $discAmount;

            if ($fincludeppn == 1 && $fapplyppn == 1) {
                $fsalesnet = (100 / (100 + $ppnPersen)) * $netPrice;
            } else {
                $fsalesnet = $netPrice;
            }

            $totalSalesNet += $qty * $fsalesnet;

            $totalGross += $subtotal;
            $totalDisc += $discAmount;

            $rowData = array_merge([
                'fsono' => $header->fsono,
                'fnou' => $i + 1,
                'fprdcode' => mb_substr($code, 0, 30),
                'fdesc' => $itemDescs[$i] ?? '',
                'fqty' => $qty,
                'fqtykecil' => $qtyKecil,
                'fqtyremain' => $qtyKecil,
                'fhpp' => (float) ($product->fhpp ?? 0),
                'fprice' => $price,
                'fprice_rp' => $price * $frate,
                'fdisc' => $discRaw,
                'fpricenet' => $netPrice,
                'fpricenet_rp' => $netPrice * $frate,
                'fsalesnet' => $fsalesnet,
                'famount' => $amountRow,
                'famount_rp' => $amountRow * $frate,
                'fsatuan' => mb_substr($sat, 0, 5),
                'fuserid' => $userid,
                'fdatetime' => $now,
                'frefcode' => $refCode,
                'frefso' => $refSoNo,
                'frefsrj' => $refSrjNo,
                'fnoacak' => $fnoacakVal,
            ], $this->buildReferenceRandomNumberColumns($refCode, $frefnoacakVal));

            $detailRows[] = $rowData;
        }

        [$oldSoRestoreByReference, $oldSrjRestoreByReference] = $this->buildInvoiceReferenceRestoreMaps($header->fsono);

        [$soUsageByReference, $srjUsageByReference] = $this->buildInvoiceReferenceUsageMaps($detailRows);

        if ($validationMessage = $this->validateReferenceUsage(
            $soUsageByReference,
            $srjUsageByReference,
            $header->fsono
        )) {
            return back()->withInput()->with('error', $validationMessage);
        }

        $srjReferenceDocs = collect($detailRows)
            ->pluck('frefsrj')
            ->map(fn($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $headerRefNo = trim((string) $request->input('frefno', ''));

        // 5. KALKULASI TOTAL AKHIR
        $amountNetBeforeHeaderDisc = $totalGross - $totalDisc;
        $headerDiscountAmount = $amountNetBeforeHeaderDisc * ($headerDiscPercent / 100);
        $totalDisc += $headerDiscountAmount;
        $amountNet = $amountNetBeforeHeaderDisc - $headerDiscountAmount;
        $ppnPersen = (float) $request->input('fppnpersen', 11);
        $ppnAmount = ($fincludeppn === '1') ? ($amountNet * ($ppnPersen / 100)) : 0;
        $grandTotal = $amountNet + $ppnAmount;

        // if ($validationMessage = $this->validateReverseJournalBaseAmount($srjReferenceDocs, $grandTotal * $frate)) {
        //     return back()->withInput()->with('error', $validationMessage);
        // }

        // if ($validationMessage = $this->validateInventoryBaseAmount($srjReferenceDocs)) {
        //     return back()->withInput()->with('error', $validationMessage);
        // }

        // if ($validationMessage = $this->validateAdvanceReductionAmount($srjReferenceDocs)) {
        //     return back()->withInput()->with('error', $validationMessage);
        // }

        $creditApproval = $this->resolveInvoiceCreditApproval($request, $grandTotal, (int) $ftranmtid);
        $fsono = trim((string) $request->input('fsono', ''));
        $hasSrjReference = ! empty($srjReferenceDocs);

        // 6. TRANSACTION
        try {
            DB::transaction(function () use (
                $request,
                $ftranmtid,
                $header,
                $fsodate,
                $fincludeppn,
                $fapplyppn,
                $userid,
                $now,
                $detailRows,
                $oldSoRestoreByReference,
                $oldSrjRestoreByReference,
                $totalGross,
                $totalDisc,
                $amountNet,
                $ppnAmount,
                $grandTotal,
                $frate,
                $ppnPersen,
                $headerDiscPercent,
                $creditApproval,
                $fkodefp,
                $needsApprovalNotification,
                &$shouldSendApprovalNotification,
                $totalSalesNet,
                $fjatuhtempo,
                $headerRefNo
            ) {
                // Update Header
                foreach ($detailRows as $detail) {
                    $prdCode = $detail['fprdcode'];
                    $refCode = $detail['frefcode'];
                    if ($refCode === 'SRJ') {
                        $fprdoutVal = '1';
                        break;
                    } elseif ($prdCode === 'AWAL') {
                        $fprdoutVal = '1';
                        break;
                    } elseif ($prdCode === 'UM') {
                        $fprdoutVal = '1';
                        break;
                    } else {
                        $fprdoutVal = '0';
                    }
                }

                $headerUpdate = [
                    'ftaxno' => mb_substr((string) ($header->fsono ?? ''), 0, 50),
                    'fsodate' => $fsodate,
                    'fcustno' => mb_substr((string) $request->fcustno, 0, 10),
                    'fkodefp' => $fkodefp,
                    'fsalesman' => mb_substr((string) $request->input('fsalesman', ''), 0, 30),
                    'fdiscpersen' => round($headerDiscPercent, 2),
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
                    'frefno' => mb_substr($headerRefNo, 0, 100),
                    'fuserid' => $userid,
                    'fdatetime' => $now,
                    'fincludeppn' => $fincludeppn,
                    'fapplyppn' => $fapplyppn,
                    'fppnpersen' => $ppnPersen,
                    'fbranchcode' => $request->fbranchcode,
                    'ftypesales' => (int) $request->input('ftypesales', 0),
                    'fprdout' => $fprdoutVal,
                    'fneedacc' => $needsApprovalNotification ? '1' : '0',
                    'fuseracc' => $creditApproval['fuseracc'],
                    'fjatuhtempo' => $fjatuhtempo,
                ];
                if ($this->tranmtHasInternalNoteColumn()) {
                    $headerUpdate['fketinternal'] = mb_substr((string) $request->input('fketinternal', ''), 0, 300);
                }
                DB::table('tranmt')->where('ftranmtid', $ftranmtid)->update($headerUpdate);

                $shouldSendApprovalNotification = false;

                // Hapus detail lama
                DB::table('trandt')->where('fsono', $header->fsono)->delete();

                $this->restoreInvoiceReferenceUsage($oldSoRestoreByReference, $oldSrjRestoreByReference);

                // Insert detail baru
                if (! empty($detailRows)) {
                    DB::table('trandt')->insert($detailRows);
                }

                $this->syncInvoiceJournalEntries(
                    (string) $header->fsono,
                    $fsodate,
                    (string) ($request->fbranchcode ?? ($header->fbranchcode ?? 'BG')),
                    (string) $request->fcustno,
                    (string) $userid
                );
            });

            if ($shouldSendApprovalNotification) {
                $this->sendApprovalNotification($header->fsono, $userid);
            }

            $redirect = redirect()->route('invoice.index')->with('success', 'Faktur penjualan ' . $this->formatDisplayTransactionNumber($header->fsono, $fincludeppn === '1') . ' berhasil diupdate.');

            if ($hasSrjReference || $needsApprovalNotification || ! $this->canCreateSuratJalan()) {
                return $redirect;
            }

            return $redirect->with('success_prompt', [
                'type' => 'invoice_create_suratjalan',
                'redirect_url' => route('suratjalan.create', ['invoice_id' => $ftranmtid]),
            ]);
        } catch (\Exception $e) {
            report($e);
            return back()->withInput()->with('error', 'Faktur penjualan belum bisa diupdate. Cek data.');
        }
    }

    public function delete(Request $request, $ftranmtid)
    {
        $customers = Customer::orderBy('fcustomername', 'asc')
            ->get(['fcustomerid', 'fcustomercode', 'fcustomername']);

        $salesmans = Salesman::orderBy('fsalesmanname', 'asc')
            ->get(['fsalesmanid', 'fsalesmancode', 'fsalesmanname']);

        $invoice = Tranmt::with(['customer', 'details' => function ($q) {
            $q->leftJoin('msprd', 'msprd.fprdcode', '=', 'trandt.fprdcode')
                ->leftJoin('trsomt as so_hdr', 'so_hdr.fsono', '=', 'trandt.frefso')
                ->leftJoin('trstockmt as sj_hdr', 'sj_hdr.fstockmtno', '=', 'trandt.frefsrj')
                ->select(
                    'trandt.*',
                    'msprd.fprdcode as fitemcode',
                    'msprd.fprdname',
                    'so_hdr.fsono as fsono_ref',
                    'sj_hdr.fstockmtno as fstockno_ref'
                )
                ->orderBy('trandt.ftrandtid', 'asc');
        }])->findOrFail($ftranmtid);

        if ($message = $this->getPostedPeriodLockMessage($invoice->fsodate, 'Faktur ini')) {
            return redirect()->route('invoice.view', $invoice->ftranmtid)->with('error', $message);
        }

        if ($message = $this->getApprovalLockMessage($invoice)) {
            return redirect()->route('invoice.view', $invoice->ftranmtid)->with('error', $message);
        }

        if (! $invoice->customer) {
            $invoice->setRelation('customer', Customer::where('fcustomercode', trim((string) $invoice->fcustno))->first());
        }

        ['fcabang' => $fcabang, 'fbranchcode' => $fbranchcode] = $this->resolveBranchContext($invoice->fbranchcode ?? null);

        $usageLockMessage = $this->getUsageLockMessage($invoice);

        if (! empty($usageLockMessage)) {
            return redirect()->route('invoice.view', $invoice->ftranmtid)->with('error', $usageLockMessage);
        }

        $referenceSummary = $this->getReferenceSummaryByTranNo((string) $invoice->fsono);

        $savedItems = $invoice->details->map(function ($d) use ($referenceSummary) {
            $trimSo = trim($d->frefso ?? '');
            $trimSrj = trim($d->frefsrj ?? '');
            $detailRef = $trimSo !== '' ? $trimSo : ($trimSrj !== '' ? $trimSrj : '');
            $refNoDisplay = $d->fsono_ref ?? ($d->fstockno_ref ?? ($detailRef !== '' ? $detailRef : ($d->frefcode ?? '-')));
            $summary = $referenceSummary[(int) ($d->ftrandtid ?? 0)] ?? ['fqtyterinvoice' => 0, 'fqtysisa_ref' => 0];

            return [
                'uid' => $d->ftrandtid,
                'fitemcode' => (string) ($d->fitemcode ?? ''),
                'fitemname' => (string) ($d->fprdname ?? ''),
                'fsatuan' => trim((string) ($d->fsatuan ?? '')),
                'fdisplayunit' => trim((string) ($d->fsatuan ?? '')),
                'frefdtno' => (string) ($d->frefdtno ?? ''),
                'fqty' => (float) ($d->fqty ?? 0),
                'fterima' => (float) ($d->fterima ?? 0),
                'fprice' => (float) ($d->fprice ?? 0),
                'fdisc' => $this->normalizeDiscountInput($d->fdisc ?? 0),
                'ftotal' => (float) ($d->famount ?? 0),
                'fdesc' => (string) ($d->fdesc ?? ''),
                'frefcode' => (string) ($d->frefcode ?? ''),
                'fnoacak' => (string) ($d->fnoacak ?? ''),
                'frefnoacak' => (string) ($d->frefnoacak ?? ''),
                'frefno_display' => $refNoDisplay,
                'fketdt' => (string) ($d->fketdt ?? ''),
                'fqtyterinvoice' => (float) ($summary['fqtyterinvoice'] ?? 0),
                'fqtysisa_ref' => (float) ($summary['fqtysisa_ref'] ?? 0),
            ];
        })->values();
        $selectedSupplierCode = $invoice->fsupplier;

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
        return view('invoice.edit', [
            'customers' => $customers,
            'salesmans' => $salesmans,
            'selectedSupplierCode' => $selectedSupplierCode, // Kirim kode supplier ke view
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'products' => $products,
            'productMap' => $productMap,
            'invoice' => $invoice,
            'displayFsono' => $this->formatDisplayTransactionNumber($invoice->fsono ?? null, (string) ($invoice->fincludeppn ?? '0') === '1'),
            'savedItems' => $savedItems,
            'ppnAmount' => (float) ($invoice->famountpopajak ?? 0), // total PPN from DB
            'famountgross' => (float) ($invoice->famountgross ?? 0),  // nilai Grand Total dari DB
            'famountso' => (float) ($invoice->famountso ?? 0),  // nilai Grand Total dari DB
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
            $invoice = Tranmt::findOrFail($ftranmtid);

            if ($message = $this->getPostedPeriodLockMessage($invoice->fsodate, 'Faktur ini')) {
                return redirect()->route('invoice.view', $invoice->ftranmtid)->with('error', $message);
            }

            if ($message = $this->getUsageLockMessage($invoice)) {
                return redirect()->route('invoice.index')->with('error', $message);
            }

            DB::transaction(function () use ($invoice) {
                [$oldSoRestoreByReference, $oldSrjRestoreByReference] = $this->buildInvoiceReferenceRestoreMaps($invoice->fsono);
                $this->restoreInvoiceReferenceUsage($oldSoRestoreByReference, $oldSrjRestoreByReference);

                DB::table('trandt')
                    ->where('fsono', $invoice->fsono)
                    ->delete();

                $this->deleteInvoiceJournalEntries((string) $invoice->fsono);

                $invoice->delete();
            });

            return redirect()->route('invoice.index')->with('success', 'Faktur penjualan ' . $this->formatDisplayTransactionNumber($invoice->fsono, (string) ($invoice->fincludeppn ?? '0') === '1') . ' berhasil dihapus.');
        } catch (\Exception $e) {
            // Jika terjadi kesalahan saat menghapus, kembali ke halaman delete dengan pesan error
            report($e);
            return redirect()->route('invoice.delete', $ftranmtid)->with('error', 'Faktur penjualan belum bisa dihapus. Coba lagi.');
        }
    }

    private function getUsageLockMessage($header): ?string
    {
        return null;
    }

    private function createInvoiceJournalEntries(
        string $fsono,
        Carbon $fsodate,
        string $branchCode,
        string $customerCode,
        string $userName
    ): void {
        $kodeCabang = trim($branchCode) !== '' ? trim($branchCode) : trim((string) (session('fcabang') ?: '01'));
        $customerCode = trim($customerCode);
        $fjurnaltype = 'JIV';
        $prefix = sprintf('%s.%s.%s.%s.', $fjurnaltype, $kodeCabang, $fsodate->format('y'), $fsodate->format('m'));

        if (DB::getDriverName() === 'pgsql') {
            $lockKey = crc32('JURNAL|' . $fjurnaltype . '|' . $kodeCabang . '|' . $fsodate->format('Y-m'));
            DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

            $lastNo = DB::table('jurnalmt')
                ->where('fjurnalno', 'like', $prefix . '%')
                ->selectRaw("MAX(CAST(split_part(fjurnalno, '.', 5) AS int)) AS lastno")
                ->value('lastno');

            $nextNo = (int) $lastNo + 1;
        } else {
            $lastJurnalNo = DB::table('jurnalmt')
                ->where('fjurnalno', 'like', $prefix . '%')
                ->orderByDesc('fjurnalno')
                ->value('fjurnalno');

            $nextNo = 1;
            if ($lastJurnalNo && ($pos = strrpos($lastJurnalNo, '.')) !== false) {
                $nextNo = ((int) substr($lastJurnalNo, $pos + 1)) + 1;
            }
        }

        $fjurnalno = $prefix . str_pad((string) $nextNo, 4, '0', STR_PAD_LEFT);
        $now = now();
        $subaccount = $customerCode !== '' ? $customerCode : null;

        $jurnalId = DB::table('jurnalmt')->insertGetId([
            'fbranchcode' => $kodeCabang,
            'fjurnalno' => $fjurnalno,
            'fjurnaltype' => $fjurnaltype,
            'fjurnaldate' => $fsodate,
            'fjurnalnote' => 'Jurnal Faktur Penjualan ' . $fsono,
            'fbalance' => 0,
            'fbalance_rp' => 0,
            'fdatetime' => $now,
            'fuserid' => $userName,
        ], 'fjurnalmtid');

        DB::table('jurnaldt')->insert([
            [
                'fjurnalmtid' => $jurnalId,
                'fbranchcode' => $kodeCabang,
                'fjurnaltype' => $fjurnaltype,
                'fjurnalno' => $fjurnalno,
                'flineno' => 1,
                'faccount' => self::MEMO_DEBIT_ACCOUNT,
                'fdk' => 'D',
                'fsubaccount' => $subaccount,
                'frefno' => $fsono,
                'frate' => 1,
                'famount' => 0,
                'famount_rp' => 0,
                'faccountnote' => 'Memo Invoice ' . $fsono,
                'fusercreate' => $userName,
                'fdatetime' => $now,
            ],
            [
                'fjurnalmtid' => $jurnalId,
                'fbranchcode' => $kodeCabang,
                'fjurnaltype' => $fjurnaltype,
                'fjurnalno' => $fjurnalno,
                'flineno' => 2,
                'faccount' => self::MEMO_CREDIT_ACCOUNT,
                'fdk' => 'K',
                'fsubaccount' => $subaccount,
                'frefno' => $fsono,
                'frate' => 1,
                'famount' => 0,
                'famount_rp' => 0,
                'faccountnote' => 'Memo Invoice ' . $fsono,
                'fusercreate' => $userName,
                'fdatetime' => $now,
            ],
        ]);
    }

    private function syncInvoiceJournalEntries(
        string $fsono,
        Carbon $fsodate,
        string $branchCode,
        string $customerCode,
        string $userName
    ): void {
        $this->deleteInvoiceJournalEntries($fsono);
        $this->createInvoiceJournalEntries($fsono, $fsodate, $branchCode, $customerCode, $userName);
    }

    private function deleteInvoiceJournalEntries(string $fsono): void
    {
        $existingJurnalIds = DB::table('jurnaldt')
            ->where('frefno', $fsono)
            ->where('fjurnaltype', 'JIV')
            ->pluck('fjurnalmtid')
            ->filter()
            ->unique()
            ->values();

        if ($existingJurnalIds->isEmpty()) {
            return;
        }

        DB::table('jurnaldt')->whereIn('fjurnalmtid', $existingJurnalIds->all())->delete();
        DB::table('jurnalmt')->whereIn('fjurnalmtid', $existingJurnalIds->all())->delete();
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
}
