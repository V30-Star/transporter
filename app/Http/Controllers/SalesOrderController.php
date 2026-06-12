<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ProductBrowseHelper;
use App\Mail\GenericApprovalNotification;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Salesman;
use App\Models\SalesOrderDetail;
use App\Models\SalesOrderHeader;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use App\Support\ApprovalState;

class SalesOrderController extends Controller
{
    use ProductBrowseHelper;

    private const MEMO_DEBIT_ACCOUNT = '11300';
    private const MEMO_CREDIT_ACCOUNT = '41000';

    private function formatDisplayTransactionNumber(?string $number, bool $useSlash = false): string
    {
        $normalized = trim((string) $number);
        if ($normalized === '') {
            return '-';
        }

        $separator = $useSlash ? '/' : '.';

        return (string) preg_replace('/[.\/](\d+)$/', $separator . '$1', $normalized, 1);
    }

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
            $messages["fprdcode.$index"] = "Kode produk {$code} tidak boleh sama dalam satu Sales Order.";
        }

        throw ValidationException::withMessages($messages);
    }

    private function canApproveCreditLimit(): bool
    {
        return in_array('approveSalesOrder', explode(',', session('user_restricted_permissions', '')), true);
    }

    private function canContinueToSuratJalan(): bool
    {
        return in_array('BolehLanjutKeSuratJalan', explode(',', session('user_restricted_permissions', '')), true);
    }

    private function canCreateSuratJalan(): bool
    {
        return in_array('createSuratJalan', explode(',', session('user_restricted_permissions', '')), true);
    }

    private function getApprovalRecipients(): array
    {
        return array_values(array_filter([
            trim((string) config('approval.sales_order.stage1', '')),
            trim((string) config('approval.sales_order.stage2', '')),
        ]));
    }

    private function sendApprovalNotification(string $fsono, string $approver): void
    {
        $header = DB::table('trsomt as so')
            ->leftJoin('mscustomer as c', 'so.fcustno', '=', 'c.fcustomercode')
            ->leftJoin('mssalesman as s', 'so.fsalesman', '=', 's.fsalesmancode')
            ->where('so.fsono', $fsono)
            ->first([
                'so.*',
                'c.fcustomername',
                's.fsalesmanname',
            ]);

        if (! $header) {
            return;
        }

        $items = DB::table('trsodt as d')
            ->leftJoin('msprd as p', 'd.fprdcode', '=', 'p.fprdcode')
            ->where('d.fsono', $fsono)
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
                'Approval Sales Order',
                'Sales Order Approval',
                $fsono,
                $approver,
                route('approval.salesorder.page', ['fsono' => $fsono, 'token' => $header->fapproval_token]),
                $fields,
                $items
            ));
        }

        if (! empty($recipients[1]) && ! empty($header->fapproval_token2)) {
            Mail::to($recipients[1])->send(new GenericApprovalNotification(
                'Approval Sales Order',
                'Sales Order Approval',
                $fsono,
                $approver,
                route('approval.salesorder.page', ['fsono' => $fsono, 'token' => $header->fapproval_token2]),
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

    private function markApprovalStateApproved(array $approvalState, string $userid, $approvedAt): array
    {
        $approvalState['fapproval'] = '2';
        $approvalState['fuserapproved'] = mb_substr($userid, 0, 30);
        $approvalState['fdateapproved'] = $approvedAt;
        $approvalState['fapproval_token'] = null;

        if (array_key_exists('fapproval2', $approvalState)) {
            $approvalState['fapproval2'] = null;
            $approvalState['fuserapproved2'] = null;
            $approvalState['fdateapproved2'] = null;
            $approvalState['fapproval_reason2'] = null;
            $approvalState['fapproval_token2'] = null;
        }

        return $approvalState;
    }

    private function shouldRequestSalesOrderApproval(Request $request): bool
    {
        return trim((string) $request->input('fneedacc', '0')) === '1';
    }

    private function getApprovalLockMessage($record): ?string
    {
        if (trim((string) data_get($record, 'fneedacc', '0')) !== '1') {
            return null;
        }

        return ApprovalState::isEditBlockedRecord($record)
            ? 'Sales Order belum dapat diubah karena status approval saat ini belum mengizinkan edit.'
            : null;
    }

    private function getCustomerCreditChecks(string $customerCode, float $currentTransactionAmount = 0): array
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

        $outstandingTotal = (float) DB::table('tranmt')
            ->where('fcustno', $customerCode)
            ->whereRaw('COALESCE(famountremain, 0) > 0')
            ->sum('famountremain');

        $transactionAmount = max(0, $currentTransactionAmount);
        $projectedTotal = $outstandingTotal + $transactionAmount;
        $limit = (float) ($customer->flimit ?? 0);
        $maxTempo = (int) ($customer->fmaxtempo ?? 0);

        $overdueItems = collect();
        if ($maxTempo > 0) {
            $overdueItems = DB::table('tranmt')
                ->where('fcustno', $customerCode)
                ->whereRaw('COALESCE(famountremain, 0) > 0')
                ->whereNotNull('fjatuhtempo')
                ->whereRaw('CAST(NOW() AS DATE) - CAST(fjatuhtempo AS DATE) > ?', [$maxTempo])
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

    private function resolveSalesOrderCreditApproval(Request $request, float $grandTotal): array
    {
        $checks = $this->getCustomerCreditChecks(
            (string) $request->input('fcustno', ''),
            $grandTotal
        );

        $needsApproval = (bool) ($checks['limit_check']['exceeded'] ?? false)
            || (bool) ($checks['overdue_check']['has_overdue'] ?? false);

        if (! $needsApproval) {
            return [
                'fneedacc' => '0',
                'fuseracc' => null,
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
            'fneedacc' => '1',
            'fuseracc' => mb_substr($approvedBy, 0, 30),
            'checks' => $checks,
        ];
    }

    public function creditCheck(Request $request)
    {
        $validated = $request->validate([
            'fcustno' => ['required', 'string', 'max:20'],
            'famountso' => ['nullable', 'numeric', 'min:0'],
        ]);

        $checks = $this->getCustomerCreditChecks(
            (string) $validated['fcustno'],
            (float) ($validated['famountso'] ?? 0)
        );

        return response()->json([
            'can_approve' => $this->canApproveCreditLimit(),
            'current_user' => auth('sysuser')->user()->fname ?? auth()->user()->name ?? 'system',
            'checks' => $checks,
        ]);
    }

    public function duplicateRefPoCheck(Request $request)
    {
        $validated = $request->validate([
            'fcustno' => ['required', 'string', 'max:20'],
            'frefpo' => ['required', 'string', 'max:100'],
            'except_id' => ['nullable', 'integer'],
        ]);

        $customerCode = trim((string) $validated['fcustno']);
        $refPo = trim((string) $validated['frefpo']);
        $exceptId = (int) ($validated['except_id'] ?? 0);

        $query = DB::table('trsomt as so')
            ->leftJoin('mscustomer as c', 'so.fcustno', '=', 'c.fcustomercode')
            ->whereRaw('TRIM(COALESCE(so.fcustno, \'\')) = ?', [$customerCode])
            ->whereRaw('LOWER(TRIM(COALESCE(so.frefpo, \'\'))) = LOWER(?)', [$refPo]);

        if ($exceptId > 0) {
            $query->where('so.ftrsomtid', '<>', $exceptId);
        }

        $existing = $query->orderByDesc('so.ftrsomtid')->first([
            'so.ftrsomtid',
            'so.fsono',
            'so.fsodate',
            'so.frefpo',
            'so.fcustno',
            'c.fcustomername',
        ]);

        return response()->json([
            'exists' => (bool) $existing,
            'record' => $existing ? [
                'ftrsomtid' => (int) ($existing->ftrsomtid ?? 0),
                'fsono' => (string) ($existing->fsono ?? ''),
                'fsodate' => ! empty($existing->fsodate)
                    ? Carbon::parse($existing->fsodate)->format('Y-m-d')
                    : null,
                'frefpo' => (string) ($existing->frefpo ?? ''),
                'fcustno' => (string) ($existing->fcustno ?? ''),
                'fcustomername' => (string) ($existing->fcustomername ?? ''),
            ] : null,
        ]);
    }

    public function index(Request $request)
    {
        $canCreate = in_array('createTr_poh', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updateTr_poh', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteTr_poh', explode(',', session('user_restricted_permissions', '')));
        $showActionsColumn = $canEdit || $canDelete;

        $year = $request->query('year');
        $month = $request->query('month');

        $availableYearsQuery = SalesOrderHeader::selectRaw('DISTINCT EXTRACT(YEAR FROM fsodate) as year')
            ->whereNotNull('fsodate');
        $this->applyBranchVisibilityScope($availableYearsQuery, 'trsomt.fbranchcode');
        $availableYears = $availableYearsQuery
            ->orderByRaw('EXTRACT(YEAR FROM fsodate) DESC')
            ->pluck('year');

        if ($request->ajax()) {

            $query = SalesOrderHeader::query()
                ->leftJoin('mscustomer as c', function ($join) {
                    $join->on(DB::raw('TRIM(trsomt.fcustno)'), '=', DB::raw('TRIM(c.fcustomercode)'));
                })
                ->select(
                    'trsomt.*',
                    'c.fcustomername',
                    DB::raw("
                        CASE
                            WHEN NULLIF(TRIM(COALESCE(trsomt.frefpo, '')), '') IS NOT NULL
                                AND EXISTS (
                                    SELECT 1
                                    FROM trsomt so2
                                    WHERE TRIM(COALESCE(so2.fcustno, '')) = TRIM(COALESCE(trsomt.fcustno, ''))
                                      AND TRIM(COALESCE(so2.frefpo, '')) = TRIM(COALESCE(trsomt.frefpo, ''))
                                      AND so2.ftrsomtid <> trsomt.ftrsomtid
                                )
                            THEN 'Yes'
                            ELSE 'No'
                        END AS frefno_confirm
                    ")
                );
            $this->applyBranchVisibilityScope($query, 'trsomt.fbranchcode');
            $totalRecords = (clone $query)->count();

            if ($search = $request->input('search.value')) {
                $query->where(function ($q) use ($search) {
                    $q->where('trsomt.fsono', 'like', "%{$search}%")
                        ->orWhere('trsomt.frefpo', 'like', "%{$search}%")
                        ->orWhere('c.fcustomername', 'like', "%{$search}%");
                });
            }

            // Pencarian per kolom
            $colSearchSo = $request->input('columns.1.search.value');
            if ($colSearchSo !== null && $colSearchSo !== '') {
                $query->where('trsomt.fsono', 'ilike', "%{$colSearchSo}%");
            }

            $colSearchRef = $request->input('columns.3.search.value');
            if ($colSearchRef !== null && $colSearchRef !== '') {
                $query->where('trsomt.frefpo', 'ilike', "%{$colSearchRef}%");
            }

            $colSearchCust = $request->input('columns.4.search.value');
            if ($colSearchCust !== null && $colSearchCust !== '') {
                $query->where('c.fcustomername', 'ilike', "%{$colSearchCust}%");
            }

            if ($year) {
                $query->whereRaw('EXTRACT(YEAR FROM fsodate) = ?', [$year]);
            }

            if ($month) {
                $query->whereRaw('EXTRACT(MONTH FROM fsodate) = ?', [$month]);
            }

            $filteredRecords = (clone $query)->count();

            $orderColIdx = $request->input('order.0.column', 2);
            $orderDir = $request->input('order.0.dir', 'desc');

            $sortableColumns = ['fbranchcode', 'fsono', 'fsodate', 'fcustomername', 'famountso', 'fusercreate'];

            if (isset($sortableColumns[$orderColIdx])) {
                $query->orderBy($sortableColumns[$orderColIdx], $orderDir);
            }

            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            $records = $query->skip($start)
                ->take($length)
                ->get();

            $data = $records->map(function ($row) {
                return [
                    'ftrsomtid' => $row->ftrsomtid,
                    'fbranchcode' => $row->fbranchcode,
                    'fsono' => $row->fsono,
                    'fsono_display' => $this->formatDisplayTransactionNumber($row->fsono, (int) ($row->fapplyppn ?? 0) === 1),
                    'fsodate' => $row->fsodate instanceof \Carbon\Carbon
                        ? $row->fsodate->format('Y-m-d')
                        : $row->fsodate,
                    'frefpo' => $row->frefpo ?? '',
                    'frefno_confirm' => $row->frefno_confirm ?? 'No',
                    'fcustno' => $row->fcustno ?? '',
                    'fsalesman' => $row->fsalesman,
                    'fdiscpersen' => $row->fdiscpersen,
                    'fdiscount' => $row->fdiscount,
                    'famountgross' => $row->famountgross,
                    'famountsonet' => $row->famountsonet,
                    'famountpajak' => $row->famountpajak,
                    'famountso' => $row->famountso,
                    'fket' => $row->fket,
                    'fcustomername' => $row->fcustomername ?: ($row->fcustno ?? ''),
                    'falamatkirim' => $row->falamatkirim,
                    'fprdout' => $row->fprdout,
                    'fusercreate' => $row->fusercreate,
                    'fuserupdate' => $row->fuserupdate,
                    'fdatetime' => $row->fdatetime,
                    'fclose' => $row->fclose,
                    'fincludeppn' => $row->fincludeppn,
                    'fuseracc' => $row->fuseracc,
                    'fneedacc' => $row->fneedacc,
                    'ftempohr' => $row->ftempohr,
                    'fprint' => $row->fprint,
                    'fapproval' => $row->fapproval,
                    'fapproval2' => $row->fapproval2,
                ];
            });

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        }

        return view('salesorder.index', compact(
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

        $query = SalesOrderHeader::leftJoin('mscustomer', 'trsomt.fcustno', '=', 'mscustomer.fcustomercode')
            ->leftJoin('mscabang', 'trsomt.fbranchcode', '=', 'mscabang.fcabangkode')
            ->select(
                'trsomt.ftrsomtid',
                'trsomt.fsono',
                'trsomt.fcustno',
                'trsomt.fsodate',
                'trsomt.frefpo',
                'trsomt.fbranchcode',
                'mscabang.fcabangname',
                'mscustomer.fcustomername',
                'mscustomer.faddress'
            )
            ->where(function ($q) {
                $q->whereNull('trsomt.fneedacc')
                    ->orWhereRaw("COALESCE(TRIM(CAST(trsomt.fneedacc AS TEXT)), '0') = '0'");
            });
        ApprovalState::applyApprovedFilter($query, 'trsomt.');

        if ($customerCode !== '') {
            $query->whereRaw('TRIM(COALESCE(trsomt.fcustno, \'\')) = ?', [$customerCode]);
        }

        if ($onlyRemaining) {
            $query->whereExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('trsodt as d')
                    ->whereColumn('d.fsono', 'trsomt.fsono')
                    ->whereRaw('COALESCE(d.fqtyremain, 0) > 0');
            });
        }

        $recordsTotal = SalesOrderHeader::query()
            ->where(function ($q) {
                $q->whereNull('trsomt.fneedacc')
                    ->orWhereRaw("COALESCE(TRIM(CAST(trsomt.fneedacc AS TEXT)), '0') = '0'");
            })
            ->when($customerCode !== '', function ($q) use ($customerCode) {
                $q->whereRaw('TRIM(COALESCE(trsomt.fcustno, \'\')) = ?', [$customerCode]);
            })
            ->when($onlyRemaining, function ($q) {
                $q->whereExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('trsodt as d')
                        ->whereColumn('d.fsono', 'trsomt.fsono')
                        ->whereRaw('COALESCE(d.fqtyremain, 0) > 0');
                });
            })
            ->count();

        if ($request->filled('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('trsomt.fsono', 'ilike', "%{$search}%")
                    ->orWhere('trsomt.fcustno', 'ilike', "%{$search}%")
                    ->orWhere('mscustomer.fcustomername', 'ilike', "%{$search}%")
                    ->orWhere('mscustomer.faddress', 'ilike', "%{$search}%")
                    ->orWhere('trsomt.frefpo', 'ilike', "%{$search}%")
                    ->orWhere('trsomt.fbranchcode', 'ilike', "%{$search}%")
                    ->orWhere('mscabang.fcabangname', 'ilike', "%{$search}%");
            });
        }

        $recordsFiltered = $query->count();

        $orderColumn = $request->input('order_column', 'fsodate');
        $orderDir = $request->input('order_dir', 'desc');

        $allowedColumns = ['fbranchcode', 'fcabangname', 'fsono', 'fsodate', 'fcustno', 'fcustomername', 'faddress', 'frefpo'];

        if (in_array($orderColumn, $allowedColumns)) {
            if ($orderColumn == 'fcustomername') {
                $query->orderBy('mscustomer.fcustomername', $orderDir);
            } elseif ($orderColumn == 'faddress') {
                $query->orderBy('mscustomer.faddress', $orderDir);
            } elseif ($orderColumn == 'fcabangname') {
                $query->orderBy('mscabang.fcabangname', $orderDir);
            } else {
                $query->orderBy('trsomt.' . $orderColumn, $orderDir);
            }
        } else {
            $query->orderBy('trsomt.fsodate', 'desc');
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
        $allowPending = request()->boolean('allow_pending');
        $header = SalesOrderHeader::where('ftrsomtid', $id)->firstOrFail();
        abort_if(! $allowPending && trim((string) ($header->fneedacc ?? '0')) === '1', 404);
        abort_if(! $allowPending && ! ApprovalState::isApprovedRecord($header), 404);
        $remainMap = $this->getSoRemainByIds(
            DB::table('trsodt')->where('fsono', $header->fsono)->pluck('ftrsodtid')->all()
        );

        $items = SalesOrderDetail::where('trsodt.fsono', $header->fsono)
            ->leftJoin('msprd as m', 'm.fprdcode', '=', 'trsodt.fprdcode')
            ->select([
                'trsodt.ftrsodtid as frefdtno',
                'trsodt.fsono as fnouref',
                DB::raw("COALESCE(trsodt.fnoacak::text, '') as frefnoacak"),
                'trsodt.fprdcode as fitemcode',
                'm.fprdname as fitemname',
                DB::raw("COALESCE(NULLIF(TRIM(trsodt.fsatuan), ''), NULLIF(TRIM(m.fsatuankecil), ''), '') as fsatuan"),
                'trsodt.fqty',
                'trsodt.fdiscpersen',
                'trsodt.fqtykecil',
                'trsodt.fqtyremain as fqtyremain_source',
                'm.fsatuankecil',
                'm.fsatuanbesar',
                'm.fsatuanbesar2',
                'm.fqtykecil as qtyratio_besar',
                'm.fqtykecil2 as qtyratio_besar2',
                'trsodt.fprice as fprice',
                'trsodt.fprice as fharga',
            ])
            ->orderBy('trsodt.ftrsodtid')
            ->get()
            ->map(function ($item) use ($remainMap) {
                $remainKecil = max(0, (float) ($item->fqtyremain_source ?? 0));
                $item->fqty_dokumen = (float) ($item->fqty ?? 0);
                $item->fqtyremain = $remainKecil;
                $item->maxqty = $remainKecil;
                $item->fqtyremain_dokumen = $this->convertSoRemainToDisplayUnit(
                    $remainKecil,
                    (string) ($item->fsatuan ?? ''),
                    (object) [
                        'fsatuanbesar' => $item->fsatuanbesar,
                        'fsatuanbesar2' => $item->fsatuanbesar2,
                        'fprd_qtykonversi' => $item->qtyratio_besar,
                        'fprd_qtykonversi2' => $item->qtyratio_besar2,
                    ]
                );

                return $item;
            });

        return response()->json([
            'header' => [
                'ftrsomtid' => $header->ftrsomtid,
                'fsono' => $header->fsono,
                'frefpo' => trim((string) ($header->frefpo ?? '')),
                'fcustno' => $header->fcustno,
                'fsodate' => $header->fsodate,
                'ftempohr' => (float) ($header->ftempohr ?? 0),
                'fsalesman' => trim((string) ($header->fsalesman ?? '')),
                'fsalesmanname' => trim((string) optional($header->salesman)->fsalesmanname),
                'fapplyppn' => (int) ($header->fapplyppn ?? 0),
                'fincludeppn' => (int) ($header->fincludeppn ?? 0),
                'fppnpersen' => (float) ($header->fppnpersen ?? 11),
                'fketinternal' => trim((string) ($header->fketinternal ?? '')),
                'falamatkirim' => trim((string) ($header->falamatkirim ?? '')),
                'fcustomername' => trim((string) optional($header->customer)->fcustomername),
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

        $prefix = sprintf('PO.%s.%s.%s.', $kodeCabang, $date->format('y'), $date->format('m'));

        // kunci per (branch, tahun-bulan) — TANPA bikin tabel baru
        $lockKey = crc32('PO|' . $kodeCabang . '|' . $date->format('Y-m'));
        DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

        $last = DB::table('trsomt')
            ->where('fsono', 'like', $prefix . '%')
            ->selectRaw("MAX(CAST(split_part(fsono, '.', 5) AS int)) AS lastno")
            ->value('lastno');

        $next = (int) $last + 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function print(string $fsono)
    {
        // Header: find by SO code (string)
        $hdr = DB::table('trsomt')
            ->leftJoin('mscustomer as c', 'c.fcustomercode', '=', 'trsomt.fcustno')
            ->leftJoin('mscabang as b', 'b.fcabangkode', '=', 'trsomt.fbranchcode')
            ->leftJoin('mssalesman as s', 's.fsalesmancode', '=', 'trsomt.fsalesman')
            ->where('trsomt.fsono', $fsono)
            ->first([
                'trsomt.*',
                'c.fcustomername as customer_name',
                'b.fcabangname as cabang_name',
                's.fsalesmanname as salesman_name',
            ]);

        if (! $hdr) {
            return redirect()->back()->with('error', 'Sales Order tidak ada.');
        }

        DB::table('trsomt')->where('fsono', $hdr->fsono)->update(['fprint' => 1]);

        // Detail: join dengan product
        $dt = DB::table('trsodt')
            ->leftJoin('msprd as p', 'p.fprdcode', '=', 'trsodt.fprdcode')
            ->where('trsodt.fsono', $hdr->fsono)
            ->orderBy('trsodt.ftrsodtid')
            ->get([
                'trsodt.*',
                'p.fprdcode as product_code',
                'p.fprdname as product_name',
                'p.fminstock as stock',
            ]);

        // Format date helper
        $fmt = fn($d) => $d
            ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d F Y')
            : '-';

        return view('salesorder.print', [
            'hdr' => $hdr,
            'dt' => $dt,
            'displayFsono' => $this->formatDisplayTransactionNumber($hdr->fsono ?? null, (int) ($hdr->fapplyppn ?? 0) === 1),
            'fmt' => $fmt,
            'company_name' => config('app.company_name', 'PT. DEMO VERSION'),
            'company_city' => config('app.company_city', 'Tangerang'),
        ]);
    }

    public function create(Request $request)
    {
        $customers = Customer::orderBy('fcustomername', 'asc')
            ->get(['fcustomercode', 'fcustomername', 'ftempo', 'fsalesman']);

        $salesmans = Salesman::orderBy('fsalesmanname', 'asc')
            ->get(['fsalesmancode', 'fsalesmanname']);

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

        $productMap = $products->mapWithKeys(function ($p) {
            $defaultUnit = $this->resolveProductDefaultUnit($p);
            $orderedUnits = array_values(array_unique(array_filter(array_map('trim', [
                $defaultUnit,
                $p->fsatuankecil,
                $p->fsatuanbesar,
                $p->fsatuanbesar2,
            ]))));

            return [
                $p->fprdcode => [
                    'name'         => $p->fprdname,
                    'default_unit' => $defaultUnit,
                    'units'        => $orderedUnits,
                    'stock'        => $p->fminstock ?? 0,
                    'unit_ratios'  => [
                        'satuankecil'  => 1,
                        'satuanbesar'  => (float) ($p->fqtykecil ?? 1),
                        'satuanbesar2' => (float) ($p->fqtykecil2 ?? 1),
                    ],
                ],
            ];
        })->toArray();

        return view('salesorder.create', [
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
        ]);
    }

    public function store(Request $request)
    {
        $shouldSendApprovalNotification = false;
        $canContinueToSuratJalan = $this->canContinueToSuratJalan();
        $needsApprovalNotification = ! $canContinueToSuratJalan && $this->shouldRequestSalesOrderApproval($request);
        // VALIDATION
        $request->validate([
            'fsono' => ['nullable', 'string', 'max:25'],
            'fsodate' => ['required', 'date'],
            'fkirimdate' => ['nullable', 'date'],
            'fcustno' => ['required', 'string', 'max:20'],
            'fsalesman' => ['nullable', 'string', 'max:20'],
            'fincludeppn' => ['nullable'],
            'fket' => ['nullable', 'string', 'max:300'],
            'frefpo' => ['nullable', 'string', 'max:100'],
            'falamatkirim' => ['nullable', 'string', 'max:300'],
            'fbranchcode' => ['nullable', 'string', 'max:2'],
            'ftempohr' => ['nullable', 'string', 'max:3'],
            'fprdcode' => ['required', 'array', 'min:1'],
            'fprdcode.*' => ['required', 'string', 'max:20'],
            'fsatuan' => ['nullable', 'array'],
            'fsatuan.*' => ['nullable', 'string', 'max:10'],
            'fqty' => ['required', 'array'],
            'fqty.*' => ['numeric', 'min:0'],
            'fprice' => ['nullable', 'array'],
            'fprice.*' => ['numeric', 'min:0'],
            'fdisc' => ['nullable', 'array'],
            'fdiscpersen' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fnoacak' => ['nullable', 'array'],
            'fnoacak.*' => ['nullable', 'regex:/^[1-9]{3}$/'],
            'frefnoacak' => ['nullable', 'array'],
            'frefnoacak.*' => ['nullable', 'regex:/^\d{3}$/'],
        ], [
            'fsodate.required' => 'Tanggal SO wajib diisi.',
            'fcustno.required' => 'Customer wajib dipilih.',
            'fprdcode.required' => 'Minimal harus ada 1 item.',
            'fqty.*.min' => 'Jumlah item tidak boleh minus.',
            'fprice.*.min' => 'Harga item tidak boleh minus.',
            'fnoacak.*.regex' => 'Nomor acak harus 3 digit angka 1 sampai 9.',
            'frefnoacak.*.regex' => 'Nomor referensi acak harus 3 digit angka.',
        ]);

        $this->ensureNoDuplicateDetailCodes($request->input('fprdcode', []));

        // HEADER VALUES
        $fsodate = Carbon::parse($request->fsodate)->startOfDay();
        $this->ensureCreateDateWithinEditPeriod($fsodate);
        $fsono = $request->input('fsono');
        $resolvedSalesmanCode = $this->resolveSalesmanCode(
            $request->input('fsalesman'),
            $request->input('filter_salesman_id')
        );
        $fincludeppn = $request->boolean('fincludeppn') ? '1' : '0';
        $userid = auth('sysuser')->user()->fname ?? 'admin';
        $now = now();
        $fincludeppn = $request->boolean('fincludeppn') ? 1 : 0;
        $fapplyppn = $request->boolean('fapplyppn') ? 1 : 0;
        $headerDiscPercent = max(0, min(100, (float) $request->input('fdiscpersen', 0)));

        // DETAIL ARRAYS
        $itemCodes = $request->input('fprdcode', []);
        $satuans = $request->input('fsatuan', []);
        $qtys = $request->input('fqty', []);
        $prices = $request->input('fprice', []);
        $discs = $request->input('fdisc', []);
        $descs = $request->input('fdesc', []);
        $fnoacaks = $request->input('fnoacak', []);
        $frefnoacaks = $request->input('frefnoacak', []);

        // BUILD DETAIL ROWS
        $rowsSodt = [];
        $totalGross = 0.0;
        $totalDisc = 0.0;
        $usedNoAcaks = [];

        $rowCount = count($itemCodes);

        for ($i = 0; $i < $rowCount; $i++) {
            $itemCode = trim($itemCodes[$i] ?? '');
            $qty = (float) ($qtys[$i] ?? 0);
            $price = (float) ($prices[$i] ?? 0);
            $discRaw = $this->normalizeDiscountInput($discs[$i] ?? 0);

            if (empty($itemCode) || $qty <= 0) {
                continue;
            }

            $produk = DB::table('msprd')
                ->where('fprdcode', $itemCode)
                ->select(
                    'fprdid',
                    'fsatuankecil',
                    'fsatuanbesar',
                    'fsatuanbesar2',
                    'fqtykecil',
                    'fqtykecil2'
                )
                ->first();

            $satuan = trim((string) ($satuans[$i] ?? ''));

            // Konversi Qty Kecil
            $qtyKecil = $qty;
            if (
                $produk
                && $satuan !== ''
                && $satuan === trim((string) ($produk->fsatuanbesar ?? ''))
                && (float) ($produk->fqtykecil ?? 0) > 0
            ) {
                $qtyKecil = $qty * (float) $produk->fqtykecil;
            } elseif (
                $produk
                && $satuan !== ''
                && $satuan === trim((string) ($produk->fsatuanbesar2 ?? ''))
                && (float) ($produk->fqtykecil2 ?? 0) > 0
            ) {
                $qtyKecil = $qty * (float) $produk->fqtykecil2;
            }

            // Hitung Diskon
            $discPersen = $this->parseDiscount($discRaw);
            $subtotal = $qty * $price;
            $discount = $subtotal * ($discPersen / 100);
            $amount = $subtotal - $discount;

            $totalGross += $subtotal;
            $totalDisc += $discount;

            $rowsSodt[] = [
                'fprdcode' => mb_substr($itemCode, 0, 20),
                'fnoacak' => $this->normalizeRandomNumber($fnoacaks[$i] ?? null, $usedNoAcaks),
                'fsatuan' => mb_substr($satuan, 0, 20),
                'fdesc' => $descs[$i] ?? '',
                'fqty' => $qty,
                'fprice' => $price,
                'fdiscpersen' => $discRaw,
                'fdiscount' => round($discount, 2),
                'famount' => round($amount, 2),
                'fqtykecil' => $qtyKecil,
                'fqtyremain' => $qtyKecil,
            ];
        }

        $amountNetBeforeHeaderDisc = $totalGross - $totalDisc;
        $headerDiscountAmount = $amountNetBeforeHeaderDisc * ($headerDiscPercent / 100);
        $totalDisc += $headerDiscountAmount;
        $amountNet = $amountNetBeforeHeaderDisc - $headerDiscountAmount;

        $fppnpersen = $fapplyppn === 1 ? (float) $request->input('ppn_rate', 11) : 0;
        if ($fapplyppn === 1) {
            if ($fincludeppn === 1) {
                $grandTotal = $amountNet;
                $ppnAmount = $grandTotal * ($fppnpersen / (100 + $fppnpersen));
                $amountNet = $grandTotal - $ppnAmount;
            } else {
                $ppnAmount = $amountNet * ($fppnpersen / 100);
                $grandTotal = $amountNet + $ppnAmount;
            }
        } else {
            $ppnAmount = 0;
            $fppnpersen = 0;
            $grandTotal = $amountNet;
        }
        $creditApproval = $this->resolveSalesOrderCreditApproval($request, $grandTotal);
        $requiresApprovalBeforeContinue = trim((string) ($creditApproval['fneedacc'] ?? '0')) === '1';
        if ($canContinueToSuratJalan) {
            $creditApproval['fneedacc'] = '0';
            $creditApproval['fuseracc'] = mb_substr($userid, 0, 30);
        }

        // TRANSACTION
        try {
            $ftrsomtid = null;

            DB::transaction(function () use (
                $request,
                $fsodate,
                $fincludeppn,
                $fapplyppn,
                $userid,
                $now,
                $rowsSodt,
                &$fsono,
                $totalGross,
                $totalDisc,
                $amountNet,
                $grandTotal,
                $ppnAmount,
                $headerDiscPercent,
                $resolvedSalesmanCode,
                $creditApproval,
                $needsApprovalNotification,
                $canContinueToSuratJalan,
                &$shouldSendApprovalNotification,
                &$ftrsomtid

            ) {
                // A. Generate fsono (Auto Numbering)
                if (empty($fsono)) {
                    $rawBranch = $request->input('fbranchcode');
                    $kodeCabang = 'NA';

                    if ($rawBranch !== null) {
                        $needle = trim((string) $rawBranch);
                        $kodeCabang = (strlen($needle) <= 2) ? $needle : (DB::table('mscabang')
                            ->whereRaw('LOWER(fcabangcode)=LOWER(?)', [$needle])
                            ->value('fcabangcode') ?: 'NA');
                    }

                    $yy = $fsodate->format('y');
                    $mm = $fsodate->format('m');
                    $prefix = sprintf('SO.%s.%s.%s.', $kodeCabang, $yy, $mm);

                    $lockKey = crc32('SO|' . $kodeCabang . '|' . $fsodate->format('Y-m'));
                    DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

                    $last = DB::table('trsomt')
                        ->where('fsono', 'like', $prefix . '%')
                        ->selectRaw("MAX(CAST(split_part(fsono, '.', 5) AS int)) AS lastno")
                        ->value('lastno');

                    $fsono = $prefix . str_pad((string) ((int) $last + 1), 4, '0', STR_PAD_LEFT);
                }

                $lastRefNo = DB::table('trsomt')
                    ->selectRaw("MAX(NULLIF(frefpo, '')::int) as max_no")
                    ->value('max_no') ?? 0;
                $nextRefNo = $lastRefNo + 1;

                $approvalState = $this->initializeApprovalState();
                if ($canContinueToSuratJalan) {
                    $approvalState = $this->markApprovalStateApproved($approvalState, $userid, $now);
                }
                $fppnpersen = $fapplyppn === 1 ? (float) $request->input('ppn_rate', 11) : 0;

                // C. Insert Header
                $ftrsomtid = DB::table('trsomt')->insertGetId([
                    'fsono' => $fsono,
                    'fsodate' => $fsodate,
                    'fbranchcode' => mb_substr($request->input('fbranchcode', ''), 0, 2),
                    'fcustno' => mb_substr($request->input('fcustno', ''), 0, 20),
                    'fsalesman' => $resolvedSalesmanCode,
                    'ftempohr' => mb_substr($request->input('ftempohr', '0'), 0, 3),
                    'frefpo' => mb_substr($request->input('frefpo', ''), 0, 100),
                    'fket' => mb_substr($request->input('fket', ''), 0, 300),
                    'falamatkirim' => mb_substr($request->input('falamatkirim', ''), 0, 300),
                    'fusercreate' => mb_substr($userid, 0, 10),
                    'fdatetime' => $now,
                    'famountgross' => round($totalGross, 2),
                    'fdiscount' => round($totalDisc, 2),
                    'fdiscpersen' => round($headerDiscPercent, 2),
                    'fincludeppn' => $fincludeppn,
                    'fapplyppn' => $fapplyppn,
                    'fppnpersen' => $fppnpersen,
                    'famountsonet' => round($amountNet, 2),
                    'famountpajak' => round($ppnAmount, 2),
                    'famountso' => 0,
                    'fprdout' => '0',
                    'fclose' => '0',
                    'fprint' => 0,
                    'fketinternal' => mb_substr($request->input('fketinternal', ''), 0, 300),
                    'fneedacc' => $creditApproval['fneedacc'],
                    'fuseracc' => $creditApproval['fuseracc'],
                    ...$approvalState,
                ], 'ftrsomtid');

                // D. Insert Details
                foreach ($rowsSodt as &$r) {
                    $r['fsono'] = $fsono;
                }
                DB::table('trsodt')->insert($rowsSodt);

                // E. Final Total Update
                $totalAmountSo = DB::table('trsodt')->where('fsono', $fsono)->sum('famount');
                DB::table('trsomt')->where('ftrsomtid', $ftrsomtid)->update([
                    'famountso' => round($grandTotal, 2),
                ]);

                $shouldSendApprovalNotification = $needsApprovalNotification
                    && ApprovalState::hasApprovalProgress((object) $approvalState);
            });

            if ($shouldSendApprovalNotification) {
                $this->sendApprovalNotification($fsono, $userid);
            }

            $redirect = redirect()
                ->route('salesorder.create')
                ->with('success', 'Sales Order ' . $this->formatDisplayTransactionNumber($fsono, (int) $fapplyppn === 1) . ' berhasil disimpan.');

            if (! $canContinueToSuratJalan || ! $this->canCreateSuratJalan() || $requiresApprovalBeforeContinue) {
                return $redirect;
            }

            return $redirect->with('success_prompt', [
                'type' => 'salesorder_create_suratjalan',
                'redirect_url' => route('suratjalan.create', ['sales_order_id' => $ftrsomtid]),
            ]);
        } catch (\Exception $e) {
            report($e);
            return back()->withInput()->withErrors(['error' => 'Sales Order belum bisa disimpan. Cek data.']);
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

    private function normalizeDiscountInput($discInput): string
    {
        $value = trim((string) ($discInput ?? ''));
        if ($value === '') {
            return '0';
        }

        $value = preg_replace('/\s+/', '', $value) ?? '0';

        return $value === '' ? '0' : mb_substr($value, 0, 50);
    }

    public function edit(Request $request, $ftrsomtid)
    {
        $customers = Customer::orderBy('fcustomername', 'asc')
            ->get(['fcustomercode', 'fcustomername', 'ftempo', 'fsalesman']);

        $salesmans = Salesman::orderBy('fsalesmanname', 'asc')
            ->get(['fsalesmancode', 'fsalesmanname']);

        $salesorder = SalesOrderHeader::with(['customer', 'details' => function ($q) {
            $q->orderBy('trsodt.ftrsodtid')
                ->leftJoin('msprd', function ($j) {
                    $j->on('msprd.fprdcode', '=', 'trsodt.fprdcode');
                })
                ->select(
                    'trsodt.*',
                    'msprd.fprdcode as fprdcode',
                    'msprd.fprdname',
                    'msprd.fsatuanbesar',
                    'msprd.fsatuanbesar2',
                    'msprd.fqtykecil  as fprd_qtykonversi',
                    'msprd.fqtykecil2 as fprd_qtykonversi2'
                );
        }])->findOrFail($ftrsomtid);

        if ($message = $this->getPostedPeriodLockMessage($salesorder->fsodate, 'Sales Order ini')) {
            return redirect()->route('salesorder.view', $salesorder->ftrsomtid)->with('error', $message);
        }

        if ($message = $this->getApprovalLockMessage($salesorder)) {
            return redirect()->route('salesorder.view', $salesorder->ftrsomtid)->with('error', $message);
        }

        if (! $salesorder->customer) {
            $salesorder->setRelation('customer', Customer::where('fcustomercode', trim((string) $salesorder->fcustno))->first());
        }

        ['fcabang' => $fcabang, 'fbranchcode' => $fbranchcode] = $this->resolveBranchContext($salesorder->fbranchcode ?? null);

        $usageLockMessage = $this->getUsageLockMessage($salesorder);

        if (! empty($usageLockMessage)) {
            return redirect()
                ->route('salesorder.view', $salesorder->ftrsomtid)
                ->with('error', $usageLockMessage);
        }

        $soRemainMap = $this->getSoRemainByIds($salesorder->details->pluck('ftrsodtid')->all());

        $savedItems = $salesorder->details->map(function ($d) use ($soRemainMap) {
            $id    = (int) ($d->ftrsodtid ?? 0);
            $entry = $soRemainMap[$id] ?? ['remain_kecil' => 0, 'remain_dokumen' => 0, 'usage_dokumen' => 0];

            return [
                'uid'                => $d->ftrsodtid,
                'fprdcode'           => (string) ($d->fprdcode ?? ''),
                'fitemname'          => (string) ($d->fprdname ?? ''),
                'fsatuan'            => (string) ($d->fsatuan ?? ''),
                'fnoacak'            => (string) ($d->fnoacak ?? ''),
                'frefdtno'           => (string) ($d->ftrsodtid ?? ''),
                'fqty'               => (float) ($d->fqty ?? 0),
                'fqtyremain'         => (float) $entry['remain_kecil'],
                'fqtyremain_dokumen' => (float) $entry['remain_dokumen'],
                'fqtysrj'            => (float) $entry['usage_dokumen'],
                'fterima'            => (float) ($d->fterima ?? 0),
                'fprice'             => (float) ($d->fprice ?? 0),
                'fdisc'              => $this->normalizeDiscountInput($d->fdiscpersen ?? 0),
                'ftotal'             => (float) ($d->famount ?? 0),
                'fdesc'              => (string) ($d->fdesc ?? ''),
                'fketdt'             => (string) ($d->fketdt ?? ''),
            ];
        })->values();

        $selectedSupplierCode = $salesorder->fsupplier;

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
        $productMap = $products->mapWithKeys(function ($p) {
            $defaultUnit = $this->resolveProductDefaultUnit($p);
            $orderedUnits = array_values(array_unique(array_filter(array_map('trim', [
                $defaultUnit,
                $p->fsatuankecil,
                $p->fsatuanbesar,
                $p->fsatuanbesar2,
            ]))));

            return [
                $p->fprdcode => [
                    'name' => $p->fprdname,
                    'default_unit' => $defaultUnit,
                    'units' => $orderedUnits,
                    'stock' => $p->fminstock ?? 0,
                    'unit_ratios' => [
                        'satuankecil' => 1,
                        'satuanbesar' => (float) ($p->fqtykecil ?? 1),
                        'satuanbesar2' => (float) ($p->fqtykecil2 ?? 1),
                    ],
                ],
            ];
        })->toArray();

        // Pass the data to the view
        return view('salesorder.edit', [
            'customers' => $customers,
            'salesmans' => $salesmans,
            'selectedSupplierCode' => $selectedSupplierCode, // Kirim kode supplier ke view
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'fppnpersen' => (float) ($salesorder->fppnpersen ?? 11),
            'products' => $products,
            'productMap' => $productMap,
            'salesorder' => $salesorder,
            'displayFsono' => $this->formatDisplayTransactionNumber($salesorder->fsono ?? null, (int) ($salesorder->fapplyppn ?? 0) === 1),
            'savedItems' => $savedItems,
            'ppnAmount' => (float) ($salesorder->famountpopajak ?? 0), // total PPN from DB
            'famountgross' => (float) ($salesorder->famountgross ?? 0),  // nilai Grand Total dari DB
            'famountso' => (float) ($salesorder->famountso ?? 0),  // nilai Grand Total dari DB
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'filterSalesmanId' => $request->query('filter_salesman_id'),
            'isUsageLocked' => ! empty($usageLockMessage),
            'usageLockMessage' => $usageLockMessage,
            'action' => 'edit',
        ]);
    }

    public function view(Request $request, $ftrsomtid)
    {
        $customers = Customer::orderBy('fcustomername', 'asc')
            ->get(['fcustomercode', 'fcustomername', 'ftempo', 'fsalesman']);

        $salesmans = Salesman::orderBy('fsalesmanname', 'asc')
            ->get(['fsalesmancode', 'fsalesmanname']);
        $salesorder = SalesOrderHeader::with(['customer', 'details' => function ($q) {
            $q->orderBy('trsodt.ftrsodtid')
                ->leftJoin('msprd', function ($j) {
                    $j->on('msprd.fprdcode', '=', 'trsodt.fprdcode');
                })
                ->select(
                    'trsodt.*',
                    'msprd.fprdcode      as fprdcode',
                    'msprd.fprdname',
                    'msprd.fsatuanbesar',
                    'msprd.fsatuanbesar2',
                    'msprd.fqtykecil2    as fprd_qtykonversi2',
                    'msprd.fqtykecil     as fprd_qtykonversi'  // alias jelas, tidak konflik
                );
        }])->findOrFail($ftrsomtid);

        if (! $salesorder->customer) {
            $salesorder->setRelation('customer', Customer::where('fcustomercode', trim((string) $salesorder->fcustno))->first());
        }

        ['fcabang' => $fcabang, 'fbranchcode' => $fbranchcode] = $this->resolveBranchContext($salesorder->fbranchcode ?? null);

        $approvalLockMessage = $this->getApprovalLockMessage($salesorder);

        $soRemainMap = $this->getSoRemainByIds($salesorder->details->pluck('ftrsodtid')->all());

        $savedItems = $salesorder->details->map(function ($d) use ($soRemainMap) {
            $id    = (int) ($d->ftrsodtid ?? 0);
            $entry = $soRemainMap[$id] ?? ['remain_kecil' => 0, 'remain_dokumen' => 0, 'usage_dokumen' => 0];

            return [
                'uid'                => $d->ftrsodtid,
                'fprdcode'           => (string) ($d->fprdcode ?? ''),
                'fitemname'          => (string) ($d->fprdname ?? ''),
                'fsatuan'            => (string) ($d->fsatuan ?? ''),
                'fnoacak'            => (string) ($d->fnoacak ?? ''),
                'frefdtno'           => (string) ($d->ftrsodtid ?? ''),
                'fqty'               => (float) ($d->fqty ?? 0),
                'fqtyremain'         => (float) $entry['remain_kecil'],
                'fqtyremain_dokumen' => (float) $entry['remain_dokumen'],
                'fqtysrj'            => (float) $entry['usage_dokumen'],
                'fterima'            => (float) ($d->fterima ?? 0),
                'fprice'             => (float) ($d->fprice ?? 0),
                'fdisc'              => $this->normalizeDiscountInput($d->fdiscpersen ?? 0),
                'ftotal'             => (float) ($d->famount ?? 0),
                'fdesc'              => (string) ($d->fdesc ?? ''),
                'fketdt'             => (string) ($d->fketdt ?? ''),
            ];
        })->values();

        $selectedSupplierCode = $salesorder->fsupplier;

        $products = Product::select(
            'fprdid',
            'fprdcode',
            'fprdname',
            'fsatuandefault',
            'fsatuankecil',
            'fsatuanbesar',
            'fsatuanbesar2',
            'fminstock'
        )->orderBy('fprdname')->get();

        $productMap = $products->mapWithKeys(function ($p) {
            $defaultUnit = $this->resolveProductDefaultUnit($p);
            $orderedUnits = array_values(array_unique(array_filter(array_map('trim', [
                $defaultUnit,
                $p->fsatuankecil,
                $p->fsatuanbesar,
                $p->fsatuanbesar2,
            ]))));

            return [
                (string) $p->fprdcode => [
                    'name' => $p->fprdname,
                    'default_unit' => $defaultUnit,
                    'units' => $orderedUnits,
                    'stock' => (float) ($p->fminstock ?? 0),
                ],
            ];
        })->toArray();

        return view('salesorder.edit', [
            'customers' => $customers,
            'salesmans' => $salesmans,
            'selectedSupplierCode' => $selectedSupplierCode,
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'products' => $products,
            'productMap' => $productMap,
            'fppnpersen' => (float) ($salesorder->fppnpersen ?? 11),
            'salesorder' => $salesorder,
            'displayFsono' => $this->formatDisplayTransactionNumber($salesorder->fsono ?? null, (int) ($salesorder->fapplyppn ?? 0) === 1),
            'savedItems' => $savedItems,
            'ppnAmount' => (float) ($salesorder->famountpopajak ?? 0),
            'famountgross' => (float) ($salesorder->famountgross ?? 0),
            'famountso' => (float) ($salesorder->famountso ?? 0),
            'approvalLockMessage' => $approvalLockMessage,
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'filterSalesmanId' => $request->query('filter_salesman_id'),
            'action' => 'view',
        ]);
    }

    public function update(Request $request, $ftrsomtid)
    {
        $shouldSendApprovalNotification = false;
        $canContinueToSuratJalan = $this->canContinueToSuratJalan();
        $needsApprovalNotification = $this->shouldRequestSalesOrderApproval($request);
        // 1. VALIDATION (Sama seperti store)
        $request->validate([
            'fsono' => ['nullable', 'string', 'max:25'],
            'fsodate' => ['required', 'date'],
            'fkirimdate' => ['nullable', 'date'],
            'fcustno' => ['required', 'string', 'max:20'],
            'fsalesman' => ['nullable', 'string', 'max:20'],
            'fincludeppn' => ['nullable'],
            'fket' => ['nullable', 'string', 'max:300'],
            'frefpo' => ['nullable', 'string', 'max:100'],
            'falamatkirim' => ['nullable', 'string', 'max:300'],
            'fbranchcode' => ['nullable', 'string', 'max:2'],
            'ftempohr' => ['nullable', 'string', 'max:3'],

            'fprdcode' => ['required', 'array', 'min:1'],
            'fprdcode.*' => ['required', 'string', 'max:50'],

            'fsatuan' => ['nullable', 'array'],
            'fsatuan.*' => ['nullable', 'string', 'max:20'],

            'fitemname' => ['nullable', 'array'],
            'fitemname.*' => ['nullable', 'string', 'max:200'],

            'fqty' => ['required', 'array'],
            'fqty.*' => ['numeric', 'min:0'],
            'fapplyppn' => ['nullable'],
            'fppnpersen' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fdiscpersen' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'fprice' => ['nullable', 'array'],
            'fprice.*' => ['numeric', 'min:0'],

            'fdisc' => ['nullable', 'array'],
            'fdisc.*' => ['nullable'], // Support "10+2"
            'fnoacak' => ['nullable', 'array'],
            'fnoacak.*' => ['nullable', 'regex:/^[1-9]{3}$/'],
            'frefnoacak' => ['nullable', 'array'],
            'frefnoacak.*' => ['nullable', 'regex:/^\d{3}$/'],
        ], [
            'fsodate.required' => 'Tanggal SO wajib diisi.',
            'fcustno.required' => 'Customer wajib dipilih.',
            'fprdcode.required' => 'Minimal harus ada 1 item.',
            'fqty.*.min' => 'Jumlah item tidak boleh minus.',
            'fprice.*.min' => 'Harga item tidak boleh minus.',
            'fnoacak.*.regex' => 'Nomor acak harus 3 digit angka 1 sampai 9.',
            'frefnoacak.*.regex' => 'Nomor referensi acak harus 3 digit angka.',
        ]);

        $this->ensureNoDuplicateDetailCodes($request->input('fprdcode', []));

        // 2. LOAD HEADER
        $header = DB::table('trsomt')->where('ftrsomtid', $ftrsomtid)->first();
        if (! $header) {
            return abort(404, 'Sales Order tidak ada.');
        }
        if ($message = $this->getPostedPeriodLockMessage($header->fsodate, 'Sales Order ini')) {
            return redirect()->route('salesorder.view', $ftrsomtid)->with('error', $message);
        }
        if ($message = $this->getApprovalLockMessage((object) $header)) {
            return redirect()->route('salesorder.view', $ftrsomtid)->with('error', $message);
        }

        if ($message = $this->getUsageLockMessage((object) $header)) {
            return redirect()->route('salesorder.index')->with('error', $message);
        }

        // 3. HEADER VALUES
        $fsodate = Carbon::parse($request->fsodate)->startOfDay();
        $this->ensureCreateDateWithinEditPeriod($fsodate, $header->fsodate);
        $resolvedSalesmanCode = $this->resolveSalesmanCode(
            $request->input('fsalesman'),
            $request->input('filter_salesman_id')
        );
        $fincludeppn = $request->input('fincludeppn', '0'); // 0: Exclude, 1: Include
        $fapplyppn = $request->input('fapplyppn') == '1' ? '1' : '0';
        $fppnpersen = (float) $request->input('fppnpersen', 11);
        $fclose = $request->input('fclose') ? '1' : '0';
        $userid = auth('sysuser')->user()->fname ?? 'admin';
        $now = now();
        $headerDiscPercent = max(0, min(100, (float) $request->input('fdiscpersen', 0)));

        // 4. DETAIL ARRAYS
        $itemCodes = $request->input('fprdcode', []);
        $itemNames = $request->input('fitemname', []);
        $satuans = $request->input('fsatuan', []);
        $qtys = $request->input('fqty', []);
        $prices = $request->input('fprice', []);
        $discs = $request->input('fdisc', []);
        $descs = $request->input('fdesc', []);
        $fnoacaks = $request->input('fnoacak', []);
        $frefnoacaks = $request->input('frefnoacak', []);

        // 5. BUILD DETAIL ROWS (Logika sama dengan store)
        $rowsSodt = [];
        $totalGross = 0.0;
        $totalDisc = 0.0;
        $usedNoAcaks = [];
        $rowCount = max(
            count($itemCodes),
            count($satuans),
            count($qtys),
            count($prices),
            count($discs),
            count($descs),
            count($itemNames)
        );

        for ($i = 0; $i < $rowCount; $i++) {
            $itemCode = trim($itemCodes[$i] ?? '');
            $itemName = trim((string) ($itemNames[$i] ?? ''));
            $satuan = trim((string) ($satuans[$i] ?? ''));
            $qty = (float) ($qtys[$i] ?? 0);
            $price = (float) ($prices[$i] ?? 0);
            $discRaw = $this->normalizeDiscountInput($discs[$i] ?? 0);
            $desc = (string) ($descs[$i] ?? '');

            if (empty($itemCode) || $qty <= 0) {
                continue;
            }

            $produk = DB::table('msprd')
                ->where('fprdcode', $itemCode)
                ->select(
                    'fprdid',
                    'fsatuankecil',
                    'fsatuanbesar',
                    'fsatuanbesar2',
                    'fqtykecil',
                    'fqtykecil2'
                )
                ->first();

            $qtyKecil = $qty;
            if (
                $produk
                && $satuan !== ''
                && $satuan === trim((string) ($produk->fsatuanbesar ?? ''))
                && (float) ($produk->fqtykecil ?? 0) > 0
            ) {
                $qtyKecil = $qty * (float) $produk->fqtykecil;
            } elseif (
                $produk
                && $satuan !== ''
                && $satuan === trim((string) ($produk->fsatuanbesar2 ?? ''))
                && (float) ($produk->fqtykecil2 ?? 0) > 0
            ) {
                $qtyKecil = $qty * (float) $produk->fqtykecil2;
            }

            $discPersen = $this->parseDiscount($discRaw);
            $subtotal = $qty * $price;
            $discount = $subtotal * ($discPersen / 100);
            $amount = $subtotal - $discount;

            $totalGross += $subtotal;
            $totalDisc += $discount;

            $rowsSodt[] = [
                'fsono' => $header->fsono, // Gunakan fsono yang sudah ada
                'fprdcode' => $itemCode,
                'fnoacak' => $this->normalizeRandomNumber($fnoacaks[$i] ?? null, $usedNoAcaks),
                'fsatuan' => mb_substr($satuan, 0, 20),
                'fdesc' => $desc,
                'fqty' => $qty,
                'fprice' => $price,
                'fpricenet' => $amount,
                'fdiscpersen' => $discRaw,
                'fdiscount' => round($discount, 2),
                'famount' => round($amount, 2),
                'fqtykecil' => $qtyKecil,
                'fqtyremain' => $qtyKecil,
            ];
        }

        // 6. CALCULATE TOTALS
        $amountNetBeforeHeaderDisc = $totalGross - $totalDisc;
        $headerDiscountAmount = $amountNetBeforeHeaderDisc * ($headerDiscPercent / 100);
        $totalDisc += $headerDiscountAmount;
        $amountNet = $amountNetBeforeHeaderDisc - $headerDiscountAmount;

        if ($fapplyppn === '1') {
            if ($fincludeppn === '1') {
                // Include: amountNet sudah termasuk pajak
                $grandTotal = $amountNet;
                $ppnAmount = $grandTotal * ($fppnpersen / (100 + $fppnpersen));
                $amountNet = $grandTotal - $ppnAmount; // DPP dihitung mundur
            } else {
                // Exclude: amountNet + pajak
                $ppnAmount = $amountNet * ($fppnpersen / 100);
                $grandTotal = $amountNet + $ppnAmount;
            }
        } else {
            $ppnAmount = 0;
            $fppnpersen = 0;
            $grandTotal = $amountNet;
        }
        $creditApproval = $this->resolveSalesOrderCreditApproval($request, $grandTotal);
        $requiresApprovalBeforeContinue = trim((string) ($creditApproval['fneedacc'] ?? '0')) === '1';

        // 7. TRANSACTION
        DB::transaction(function () use (
            $request,
            $ftrsomtid,
            $header,
            $fsodate,
            $fincludeppn,
            $fclose,
            $userid,
            $now,
            $rowsSodt,
            $totalGross,
            $totalDisc,
            $amountNet,
            $grandTotal,
            $ppnAmount,
            $fapplyppn,
            $fppnpersen,
            $headerDiscPercent,
            $resolvedSalesmanCode,
            $creditApproval,
            $needsApprovalNotification,
            &$shouldSendApprovalNotification
        ) {
            // Update Header
            DB::table('trsomt')->where('ftrsomtid', $ftrsomtid)->update([
                'fsodate' => $fsodate,
                'fbranchcode' => mb_substr($request->input('fbranchcode', ''), 0, 2),
                'fcustno' => mb_substr($request->input('fcustno', ''), 0, 20),
                'fsalesman' => $resolvedSalesmanCode,
                'ftempohr' => mb_substr($request->input('ftempohr', '0'), 0, 3),
                'frefpo' => mb_substr($request->input('frefpo', ''), 0, 100),
                'fincludeppn' => $fincludeppn,
                'fclose' => $fclose,
                'fket' => mb_substr($request->input('fket', ''), 0, 300),
                'fketinternal' => mb_substr($request->input('fketinternal', ''), 0, 300),
                'falamatkirim' => mb_substr($request->input('falamatkirim', ''), 0, 300),
                'fuserupdate' => mb_substr($userid, 0, 10),
                'fdatetime' => $now,
                'fapplyppn' => $fapplyppn,
                'fppnpersen' => $fppnpersen,
                'famountgross' => round($totalGross, 2),
                'fdiscount' => round($totalDisc, 2),
                'fdiscpersen' => round($headerDiscPercent, 2),
                'famountsonet' => round($amountNet, 2),
                'famountpajak' => round($ppnAmount, 2),
                'famountso' => round($grandTotal, 2),
                'fneedacc' => $creditApproval['fneedacc'],
                'fuseracc' => $creditApproval['fuseracc'],
            ]);

            $shouldSendApprovalNotification = false;

            // Delete old details and insert new ones
            DB::table('trsodt')->where('fsono', $header->fsono)->delete();
            if (! empty($rowsSodt)) {
                DB::table('trsodt')->insert($rowsSodt);
            }
        });

        if ($needsApprovalNotification && $shouldSendApprovalNotification) {
            $this->sendApprovalNotification($header->fsono, $userid);
        }

        $redirect = redirect()
            ->route('salesorder.index')
            ->with('success', 'Sales Order ' . $this->formatDisplayTransactionNumber($header->fsono, (int) ($header->fapplyppn ?? 0) === 1) . ' berhasil diupdate.');

        if (! $canContinueToSuratJalan || ! $this->canCreateSuratJalan() || $requiresApprovalBeforeContinue) {
            return $redirect;
        }

        return $redirect->with('success_prompt', [
            'type' => 'salesorder_create_suratjalan',
            'redirect_url' => route('suratjalan.create', ['sales_order_id' => $ftrsomtid]),
        ]);
    }

    public function delete(Request $request, $ftrsomtid)
    {
        $customers = Customer::orderBy('fcustomername', 'asc')
            ->get(['fcustomercode', 'fcustomername']);

        $salesmans = Salesman::orderBy('fsalesmanname', 'asc')
            ->get(['fsalesmancode', 'fsalesmanname']);

        $salesorder = SalesOrderHeader::with(['customer', 'details' => function ($q) { // TAMBAHKAN 'customer' di sini
            $q->orderBy('trsodt.ftrsodtid')
                ->leftJoin('msprd', function ($j) {
                    $j->on('msprd.fprdcode', '=', 'trsodt.fprdcode');
                })
                ->select(
                    'trsodt.*',
                    'msprd.fprdcode as fprdcode',
                    'msprd.fprdname',
                    'msprd.fsatuanbesar',
                    'msprd.fsatuanbesar2',
                    'msprd.fqtykecil as fprd_qtykonversi',
                    'msprd.fqtykecil2 as fprd_qtykonversi2'
                );
        }])->findOrFail($ftrsomtid);

        if ($message = $this->getPostedPeriodLockMessage($salesorder->fsodate, 'Sales Order ini')) {
            return redirect()->route('salesorder.view', $salesorder->ftrsomtid)->with('error', $message);
        }

        if (! $salesorder->customer) {
            $salesorder->setRelation('customer', Customer::where('fcustomercode', trim((string) $salesorder->fcustno))->first());
        }

        ['fcabang' => $fcabang, 'fbranchcode' => $fbranchcode] = $this->resolveBranchContext($salesorder->fbranchcode ?? null);

        $usageLockMessage = $this->getUsageLockMessage($salesorder);

        if (! empty($usageLockMessage)) {
            return redirect()
                ->route('salesorder.view', $salesorder->ftrsomtid)
                ->with('error', $usageLockMessage);
        }

        $soRemainMap = $this->getSoRemainByIds($salesorder->details->pluck('ftrsodtid')->all());

        $savedItems = $salesorder->details->map(function ($d) use ($soRemainMap) {
            $id    = (int) ($d->ftrsodtid ?? 0);
            $entry = $soRemainMap[$id] ?? ['remain_kecil' => 0, 'remain_dokumen' => 0, 'usage_dokumen' => 0];

            return [
                'uid'                => $d->ftrsodtid,
                'fprdcode'           => (string) ($d->fprdcode ?? ''),
                'fitemname'          => (string) ($d->fprdname ?? ''),
                'fsatuan'            => (string) ($d->fsatuan ?? ''),
                'fnoacak'            => (string) ($d->fnoacak ?? ''),
                'frefdtno'           => (string) ($d->ftrsodtid ?? ''),
                'fqty'               => (float) ($d->fqty ?? 0),
                'fqtyremain'         => (float) $entry['remain_kecil'],
                'fqtyremain_dokumen' => (float) $entry['remain_dokumen'],
                'fqtysrj'            => (float) $entry['usage_dokumen'],
                'fterima'            => (float) ($d->fterima ?? 0),
                'fprice'             => (float) ($d->fprice ?? 0),
                'fdisc'              => $this->normalizeDiscountInput($d->fdiscpersen ?? 0),
                'ftotal'             => (float) ($d->famount ?? 0),
                'fdesc'              => (string) ($d->fdesc ?? ''),
                'fketdt'             => (string) ($d->fketdt ?? ''),
            ];
        })->values();

        $selectedSupplierCode = $salesorder->fsupplier;

        // Fetch all products for product mapping
        $products = Product::select(
            'fprdid',
            'fprdcode',
            'fprdname',
            'fsatuandefault',
            'fsatuankecil',
            'fsatuanbesar',
            'fsatuanbesar2',
            'fminstock'
        )->orderBy('fprdname')->get();

        // Prepare the product map for frontend
        $productMap = $products->mapWithKeys(function ($p) {
            $defaultUnit = $this->resolveProductDefaultUnit($p);
            $orderedUnits = array_values(array_unique(array_filter(array_map('trim', [
                $defaultUnit,
                $p->fsatuankecil,
                $p->fsatuanbesar,
                $p->fsatuanbesar2,
            ]))));

            return [
                $p->fprdcode => [
                    'name' => $p->fprdname,
                    'default_unit' => $defaultUnit,
                    'units' => $orderedUnits,
                    'stock' => $p->fminstock ?? 0,
                ],
            ];
        })->toArray();

        // Pass the data to the view
        return view('salesorder.edit', [
            'customers' => $customers,
            'salesmans' => $salesmans,
            'selectedSupplierCode' => $selectedSupplierCode, // Kirim kode supplier ke view
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'products' => $products,
            'productMap' => $productMap,
            'salesorder' => $salesorder,
            'displayFsono' => $this->formatDisplayTransactionNumber($salesorder->fsono ?? null, (int) ($salesorder->fapplyppn ?? 0) === 1),
            'savedItems' => $savedItems,
            'fppnpersen' => (float) ($salesorder->fppnpersen ?? 11),
            'ppnAmount' => (float) ($salesorder->famountpopajak ?? 0), // total PPN from DB
            'famountgross' => (float) ($salesorder->famountgross ?? 0),  // nilai Grand Total dari DB
            'famountso' => (float) ($salesorder->famountso ?? 0),  // nilai Grand Total dari DB
            'filterSupplierId' => $request->query('filter_supplier_id'),
            'filterSalesmanId' => $request->query('filter_salesman_id'),
            'isUsageLocked' => ! empty($usageLockMessage),
            'usageLockMessage' => $usageLockMessage,
            'action' => 'delete',
        ]);
    }

    public function destroy($ftrsomtid)
    {
        try {
            $salesorder = SalesOrderHeader::findOrFail($ftrsomtid);

            if ($message = $this->getPostedPeriodLockMessage($salesorder->fsodate, 'Sales Order ini')) {
                return redirect()->route('salesorder.view', $salesorder->ftrsomtid)->with('error', $message);
            }

            if ($message = $this->getUsageLockMessage($salesorder)) {
                return redirect()->route('salesorder.view', $salesorder->ftrsomtid)->with('error', $message);
            }

            DB::transaction(function () use ($salesorder) {
                DB::table('trsodt')
                    ->where('fsono', $salesorder->fsono)
                    ->delete();

                DB::table('trsomt')
                    ->where('ftrsomtid', $salesorder->ftrsomtid)
                    ->delete();
            });

            return redirect()->route('salesorder.index')->with('success', 'Sales Order ' . $this->formatDisplayTransactionNumber($salesorder->fsono, (int) ($salesorder->fapplyppn ?? 0) === 1) . ' berhasil dihapus.');
        } catch (\Exception $e) {
            report($e);
            return redirect()->route('salesorder.view', $ftrsomtid)->with('error', 'Sales Order belum bisa dihapus. Coba lagi.');
        }
    }

    /**
     * @param int[] $soDetailIds
     * @return array<int, array{remain_kecil: float, remain_dokumen: float}>
     */
    private function getSoRemainByIds(array $soDetailIds): array
    {
        $ids = collect($soDetailIds)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            return [];
        }

        $sourceRows = DB::table('trsodt as d')
            ->leftJoin('msprd as p', DB::raw("TRIM(p.fprdcode)"), '=', DB::raw("TRIM(d.fprdcode)"))
            ->whereIn('d.ftrsodtid', $ids)
            ->selectRaw("
                d.ftrsodtid,
                TRIM(COALESCE(d.fsono, '')) as ref_doc,
                TRIM(COALESCE(d.fprdcode, '')) as product_code,
                COALESCE(d.fnoacak::text, '') as ref_noacak,
                COALESCE(d.fqtykecil, 0) as source_qty_kecil,
                TRIM(d.fsatuan) as fsatuan,
                TRIM(COALESCE(p.fsatuanbesar, '')) as fsatuanbesar,
                TRIM(COALESCE(p.fsatuanbesar2, '')) as fsatuanbesar2,
                COALESCE(p.fqtykecil, 0) as fqtykecil_konversi,
                COALESCE(p.fqtykecil2, 0) as fqtykecil2_konversi
            ")
            ->get();

        if ($sourceRows->isEmpty()) {
            return [];
        }

        $docNos = $sourceRows
            ->pluck('ref_doc')
            ->map(fn($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $srjUsageRows = DB::table('trstockdt as d')
            ->join('trstockmt as h', 'h.fstockmtno', '=', 'd.fstockmtno')
            ->where('h.fstockmtcode', 'SRJ')
            ->whereIn('d.frefso', $docNos)
            ->selectRaw("
            TRIM(COALESCE(d.frefso, '')) as ref_doc,
            TRIM(COALESCE(d.fprdcode, '')) as product_code,
            COALESCE(d.frefnoacak::text, '') as ref_noacak,
            SUM(COALESCE(d.fqtykecil, 0)) as used_qty_kecil
            ")
            ->groupByRaw("TRIM(COALESCE(d.frefso, '')), TRIM(COALESCE(d.fprdcode, '')), COALESCE(d.frefnoacak::text, '')")
            ->get();

        $invoiceUsageRows = DB::table('trandt as d')
            ->join('tranmt as h', 'h.fsono', '=', 'd.fsono')
            ->where('h.ftrcode', 'INV')
            ->whereRaw("TRIM(COALESCE(d.frefcode, '')) = 'SO'")
            ->whereIn(DB::raw("COALESCE(NULLIF(TRIM(COALESCE(d.frefso, '')), ''), NULLIF(TRIM(COALESCE(d.frefsrj, '')), ''))"), $docNos)
            ->selectRaw("
            COALESCE(NULLIF(TRIM(COALESCE(d.frefso, '')), ''), NULLIF(TRIM(COALESCE(d.frefsrj, '')), '')) as ref_doc,
            TRIM(COALESCE(d.fprdcode, '')) as product_code,
            COALESCE(d.frefnosoacak::text, d.frefnoacak::text, '') as ref_noacak,
            SUM(COALESCE(d.fqtykecil, 0)) as used_qty_kecil
            ")
            ->groupByRaw("COALESCE(NULLIF(TRIM(COALESCE(d.frefso, '')), ''), NULLIF(TRIM(COALESCE(d.frefsrj, '')), '')), TRIM(COALESCE(d.fprdcode, '')), COALESCE(d.frefnosoacak::text, d.frefnoacak::text, '')")
            ->get();

        $srjMap = [];
        foreach ($srjUsageRows as $row) {
            $key = trim((string) ($row->ref_doc ?? '')) . '|' . trim((string) ($row->product_code ?? '')) . '|' . trim((string) ($row->ref_noacak ?? ''));
            $srjMap[$key] = ($srjMap[$key] ?? 0.0) + (float) ($row->used_qty_kecil ?? 0);
        }

        $invMap = [];
        foreach ($invoiceUsageRows as $row) {
            $key = trim((string) ($row->ref_doc ?? '')) . '|' . trim((string) ($row->product_code ?? '')) . '|' . trim((string) ($row->ref_noacak ?? ''));
            $invMap[$key] = ($invMap[$key] ?? 0.0) + (float) ($row->used_qty_kecil ?? 0);
        }

        $result = [];
        foreach ($sourceRows as $row) {
            $key = trim((string) ($row->ref_doc ?? '')) . '|' . trim((string) ($row->product_code ?? '')) . '|' . trim((string) ($row->ref_noacak ?? ''));

            $sourceQtyKecil = (float) ($row->source_qty_kecil ?? 0);
            $srjQty         = (float) ($srjMap[$key] ?? 0);
            $invQty         = (float) ($invMap[$key] ?? 0);

            $remainKecil = max(0, $sourceQtyKecil - $srjQty - $invQty);

            $srjMinInv     = max(0, $srjQty - $invQty);
            $fsatuan       = trim((string) ($row->fsatuan ?? ''));
            $fsatuanbesar  = trim((string) ($row->fsatuanbesar ?? ''));
            $fsatuanbesar2 = trim((string) ($row->fsatuanbesar2 ?? ''));
            $fqtykecil1    = (float) ($row->fqtykecil_konversi ?? 0);
            $fqtykecil2    = (float) ($row->fqtykecil2_konversi ?? 0);

            if ($fsatuan === $fsatuanbesar && $fqtykecil1 > 0) {
                $remainDokumen = $srjMinInv / $fqtykecil1;
                $usageDokumen = ($srjQty + $invQty) / $fqtykecil1;
            } elseif ($fsatuan === $fsatuanbesar2 && $fqtykecil2 > 0) {
                $remainDokumen = $srjMinInv / $fqtykecil2;
                $usageDokumen = ($srjQty + $invQty) / $fqtykecil2;
            } else {
                $remainDokumen = $srjMinInv;
                $usageDokumen = $srjQty + $invQty;
            }

            $result[(int) $row->ftrsodtid] = [
                'remain_kecil'   => $remainKecil,
                'remain_dokumen' => $remainDokumen,
                'usage_dokumen'  => $usageDokumen,
            ];
        }

        return $result;
    }

    private function convertSoRemainToDisplayUnit(float $qtyKecil, string $unit, object $detail): float
    {
        $unit = trim($unit);
        $satBesar = trim((string) ($detail->fsatuanbesar ?? ''));
        $satBesar2 = trim((string) ($detail->fsatuanbesar2 ?? ''));
        $ratioBesar = (float) ($detail->fprd_qtykonversi ?? 0);
        $ratioBesar2 = (float) ($detail->fprd_qtykonversi2 ?? 0);

        if ($unit !== '' && $unit === $satBesar2 && $ratioBesar2 > 0) {
            return $qtyKecil / $ratioBesar2;
        }

        if ($unit !== '' && $unit === $satBesar && $ratioBesar > 0) {
            return $qtyKecil / $ratioBesar;
        }

        return $qtyKecil;
    }

    private function getUsageLockMessage($header): ?string
    {
        $fsono = trim((string) ($header->fsono ?? ''));
        if ($fsono === '') {
            return null;
        }

        $usedBySrj = DB::table('trstockdt as dt')
            ->join('trstockmt as mt', 'mt.fstockmtno', '=', 'dt.fstockmtno')
            ->where('mt.fstockmtcode', 'SRJ')
            ->where('dt.frefso', $fsono)
            ->select('mt.fstockmtno')
            ->distinct()
            ->orderBy('mt.fstockmtno')
            ->pluck('mt.fstockmtno');

        $usedBySalesDocs = DB::table('trandt as dt')
            ->join('tranmt as mt', 'mt.fsono', '=', 'dt.fsono')
            ->where('dt.frefso', $fsono)
            ->select('mt.fsono')
            ->distinct()
            ->orderBy('mt.fsono')
            ->pluck('mt.fsono');

        $usedByInvoice = $usedBySalesDocs->filter(fn($no) => str_starts_with((string) $no, 'INV.'));
        $usedByRetur = $usedBySalesDocs->filter(fn($no) => str_starts_with((string) $no, 'REJ.'));

        if ($usedBySrj->isEmpty() && $usedByInvoice->isEmpty() && $usedByRetur->isEmpty()) {
            return null;
        }

        $formattedLines = [];
        if ($usedBySrj->isNotEmpty()) {
            foreach ($usedBySrj as $number) {
                $formattedLines[] = '      ' . $this->formatDisplayTransactionNumber((string) $number, false);
            }
        }
        if ($usedByInvoice->isNotEmpty()) {
            foreach ($usedByInvoice as $number) {
                $formattedLines[] = '      ' . $this->formatDisplayTransactionNumber((string) $number, false);
            }
        }
        if ($usedByRetur->isNotEmpty()) {
            foreach ($usedByRetur as $number) {
                $formattedLines[] = '      ' . $this->formatDisplayTransactionNumber((string) $number, false);
            }
        }

        return "SO ini tidak boleh diedit/delete.\n    Sudah direferensi di :\n" . implode("\n", $formattedLines);
    }

    private function resolveSalesmanCode($primaryValue, $fallbackValue = null): ?string
    {
        $candidate = trim((string) ($primaryValue ?? ''));
        if ($candidate === '') {
            $candidate = trim((string) ($fallbackValue ?? ''));
        }

        if ($candidate === '') {
            return null;
        }

        $code = DB::table('mssalesman')
            ->whereRaw('LOWER(fsalesmancode) = LOWER(?)', [$candidate])
            ->value('fsalesmancode');

        if ($code) {
            return mb_substr((string) $code, 0, 20);
        }

        if (ctype_digit($candidate)) {
            $codeById = DB::table('mssalesman')
                ->where('fsalesmanid', (int) $candidate)
                ->value('fsalesmancode');

            if ($codeById) {
                return mb_substr((string) $codeById, 0, 20);
            }
        }

        return mb_substr($candidate, 0, 20);
    }
}
