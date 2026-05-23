<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Trkasdt;
use App\Models\Trkasmt;
use App\Models\Tranmt;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PelunasanCustomerController extends Controller
{
    private const TRAN_CODE = 'BKM';
    private const RECEIVABLE_SET_ACCOUNT = 'PIUTANGDAGANG';
    private const RETURN_RECEIVABLE_SET_ACCOUNT_CANDIDATES = [
        'RETURPENJUALANBELUMPOTONGPIUTANG',
        'RETURPENJUALAN_BELUM_POTONG_PIUTANG',
        'RETUR PENJUALAN BELUM POTONG PIUTANG',
    ];

    public function index()
    {
        $records = Trkasmt::query()
            ->where('trkasmt.ftrancode', self::TRAN_CODE)
            ->leftJoin('trkasdt as dt', 'dt.fkasmtid', '=', 'trkasmt.fkasmtid')
            ->leftJoin('account as acc', 'acc.faccount', '=', 'trkasmt.faccountheader')
            ->leftJoin('tranmt as inv', DB::raw('TRIM(inv.fsono)'), '=', DB::raw('TRIM(dt.frefno)'))
            ->leftJoin('mscustomer as cust', DB::raw('TRIM(cust.fcustomercode)'), '=', DB::raw('TRIM(inv.fcustno)'))
            ->select([
                'trkasmt.fkasmtid',
                'trkasmt.fkasmtno',
                'trkasmt.fkasmtdate',
                'trkasmt.fnogiro',
                'trkasmt.fuserid',
                DB::raw("
                    COALESCE(
                        NULLIF(concat_ws(' - ', trkasmt.faccountheader, acc.faccname), ''),
                        '-'
                    ) as account_summary
                "),
                DB::raw("
                    COALESCE(
                        string_agg(
                            DISTINCT NULLIF(TRIM(COALESCE(dt.frefno, '')), ''),
                            ', ' ORDER BY NULLIF(TRIM(COALESCE(dt.frefno, '')), '')
                        ),
                        '-'
                    ) as invoice_summary
                "),
                DB::raw("
                    COALESCE(
                        string_agg(
                            DISTINCT NULLIF(TRIM(COALESCE(cust.fcustomername, '')), ''),
                            ', ' ORDER BY NULLIF(TRIM(COALESCE(cust.fcustomername, '')), '')
                        ),
                        '-'
                    ) as customer_summary
                "),
                DB::raw('ABS(COALESCE(SUM(COALESCE(dt.fkasdtvalue, 0)), COALESCE(trkasmt.famountpay, 0), 0)) as payment_amount'),
            ])
            ->groupBy(
                'trkasmt.fkasmtid',
                'trkasmt.fkasmtno',
                'trkasmt.fkasmtdate',
                'trkasmt.fnogiro',
                'trkasmt.fuserid',
                'trkasmt.faccountheader',
                'acc.faccname'
            )
            ->orderByDesc('trkasmt.fkasmtdate')
            ->orderByDesc('trkasmt.fkasmtid')
            ->get();

        return view('pelunasancustomer.index', [
            'records' => $records,
        ]);
    }

    public function create()
    {
        return view('pelunasancustomer.create', $this->formViewData(null, [
            'pageTitle' => 'Pelunasan Customer',
            'formAction' => route('pelunasancustomer.store'),
            'formMethod' => 'POST',
            'isReadOnly' => false,
            'isDeleteMode' => false,
            'submitLabel' => 'Simpan',
            'draftKey' => 'pelunasancustomer:create',
        ]));
    }

    public function view($fkasmtno)
    {
        $header = $this->findHeader($fkasmtno);

        return view('pelunasancustomer.view', $this->formViewData($header, [
            'pageTitle' => 'View Pelunasan Customer',
            'formAction' => '#',
            'formMethod' => 'POST',
            'isReadOnly' => true,
            'isDeleteMode' => false,
            'submitLabel' => null,
            'draftKey' => null,
        ]));
    }

    public function edit($fkasmtno)
    {
        $header = $this->findHeader($fkasmtno);

        return view('pelunasancustomer.edit', $this->formViewData($header, [
            'pageTitle' => 'Edit Pelunasan Customer',
            'formAction' => route('pelunasancustomer.update', $header->fkasmtno),
            'formMethod' => 'PATCH',
            'isReadOnly' => false,
            'isDeleteMode' => false,
            'submitLabel' => 'Simpan',
            'draftKey' => 'pelunasancustomer:edit:' . $header->fkasmtno,
        ]));
    }

    public function delete($fkasmtno)
    {
        $header = $this->findHeader($fkasmtno);

        return view('pelunasancustomer.delete', $this->formViewData($header, [
            'pageTitle' => 'Hapus Pelunasan Customer',
            'formAction' => route('pelunasancustomer.destroy', $header->fkasmtno),
            'formMethod' => 'DELETE',
            'isReadOnly' => true,
            'isDeleteMode' => true,
            'submitLabel' => 'Hapus',
            'draftKey' => null,
        ]));
    }

    public function pickableNota(Request $request)
    {
        $customerCode = trim((string) $request->input('customer_code', $request->input('fcustomer', '')));

        $baseQuery = Tranmt::query()
            ->from('tranmt as mt')
            ->leftJoin('trandt as dt', 'dt.fsono', '=', 'mt.fsono')
            ->leftJoin('mscustomer as c', 'c.fcustomercode', '=', 'mt.fcustno')
            ->whereIn('mt.ftrcode', ['INV', 'REJ'])
            ->whereRaw('COALESCE(mt.famountremain, 0) > 0')
            ->when($customerCode !== '', function ($query) use ($customerCode) {
                $query->whereRaw('TRIM(COALESCE(mt.fcustno, \'\')) = ?', [$customerCode]);
            });

        $query = (clone $baseQuery)
            ->select([
                'mt.ftranmtid',
                'mt.fsono',
                'mt.fsodate',
                'mt.fcustno',
                'mt.ftrcode',
                'mt.famountremain',
                'mt.fjatuhtempo',
                'c.fcustomername',
                DB::raw('COALESCE(SUM(COALESCE(dt.famount, 0)), 0) as famount'),
                DB::raw('COUNT(dt.ftrandtid) as detail_count'),
            ])
            ->groupBy(
                'mt.ftranmtid',
                'mt.fsono',
                'mt.fsodate',
                'mt.fcustno',
                'mt.ftrcode',
                'mt.famountremain',
                'mt.fjatuhtempo',
                'c.fcustomername'
            );

        $recordsTotal = (clone $baseQuery)
            ->distinct('mt.ftranmtid')
            ->count('mt.ftranmtid');

        if ($request->filled('search') && trim((string) $request->input('search')) !== '') {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('mt.fsono', 'ilike', "%{$search}%")
                    ->orWhere('mt.fcustno', 'ilike', "%{$search}%")
                    ->orWhere('mt.ftrcode', 'ilike', "%{$search}%")
                    ->orWhere('c.fcustomername', 'ilike', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $query)->get()->count();

        $allowedColumns = ['fsono', 'fsodate', 'fcustno', 'fcustomername', 'ftrcode', 'famountremain'];
        $orderColumn = (string) $request->input('order_column', 'fsodate');
        $orderDir = strtolower((string) $request->input('order_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (in_array($orderColumn, $allowedColumns, true)) {
            if ($orderColumn === 'fcustomername') {
                $query->orderBy('c.fcustomername', $orderDir);
            } else {
                $query->orderBy('mt.' . $orderColumn, $orderDir);
            }
        } else {
            $query->orderBy('mt.fsodate', 'desc');
        }

        $data = $query
            ->skip((int) $request->input('start', 0))
            ->take((int) $request->input('length', 10))
            ->get()
            ->map(function ($row) {
                return [
                    'ftranmtid' => (int) ($row->ftranmtid ?? 0),
                    'fsono' => trim((string) ($row->fsono ?? '')),
                    'fsodate' => !empty($row->fsodate) ? Carbon::parse($row->fsodate)->format('Y-m-d') : null,
                    'fcustno' => trim((string) ($row->fcustno ?? '')),
                    'fcustomername' => trim((string) ($row->fcustomername ?? '')),
                    'ftrcode' => trim((string) ($row->ftrcode ?? '')),
                    'famount' => (float) ($row->famount ?? 0),
                    'famountremain' => (float) ($row->famountremain ?? 0),
                    'fjatuhtempo' => !empty($row->fjatuhtempo) ? Carbon::parse($row->fjatuhtempo)->format('Y-m-d') : null,
                    'detail_count' => (int) ($row->detail_count ?? 0),
                ];
            })
            ->values();

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'details' => $this->filterEmptyDetailRows($request->input('details', [])),
            'fbranchcode' => trim((string) $request->input('fbranchcode', $this->resolveBranchCode())),
            'fgiromundur' => $request->boolean('fgiromundur') ? '1' : '0',
        ]);

        $validated = $request->validate([
            'fkasmtno' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('trkasmt', 'fkasmtno'),
            ],
            'fkasmtdate' => ['required', 'date'],
            'fbranchcode' => ['required', 'string', 'max:10'],
            'fcustomer' => ['required', 'string', 'max:20', Rule::exists('mscustomer', 'fcustomercode')],
            'faccountheader' => ['required', 'string', 'max:15', Rule::exists('account', 'faccount')],
            'fnogiro' => ['nullable', 'string', 'max:35'],
            'fgiromundur' => ['nullable', 'in:0,1'],
            'ftgljatuhtempo' => ['nullable', 'date', Rule::requiredIf($request->input('fgiromundur') === '1'), 'after_or_equal:fkasmtdate'],
            'fket' => ['nullable', 'string', 'max:50'],
            'fbiayaadminbank' => ['nullable', 'numeric', 'min:0'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.frefno' => ['required', 'string', 'max:30'],
            'details.*.fnilai_nota' => ['nullable', 'numeric'],
            'details.*.fsisa_piutang' => ['nullable', 'numeric'],
            'details.*.fdiscpersen' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'details.*.fdiscount' => ['nullable', 'numeric'],
            'details.*.fkasdtvalue' => ['required', 'numeric', 'not_in:0'],
            'details.*.ftrcode' => ['nullable', 'string', 'max:10'],
        ], [
            'fkasmtdate.required' => 'Tanggal wajib diisi.',
            'fcustomer.required' => 'Customer wajib dipilih.',
            'faccountheader.required' => 'Account wajib dipilih.',
            'ftgljatuhtempo.required' => 'Tgl. jatuh tempo wajib diisi saat giro mundur aktif.',
            'details.required' => 'Minimal 1 detail faktur wajib diisi.',
            'details.*.frefno.required' => 'No. nota wajib diisi.',
            'details.*.fkasdtvalue.required' => 'Total bayar wajib diisi.',
            'details.*.fkasdtvalue.not_in' => 'Total bayar tidak boleh 0.',
        ]);

        $customer = Customer::query()
            ->where('fcustomercode', $validated['fcustomer'])
            ->firstOrFail(['fcustomerid', 'fcustomercode', 'fcustomername']);
        $headerAccount = Account::query()
            ->where('faccount', $validated['faccountheader'])
            ->firstOrFail(['faccid', 'faccount', 'faccname']);
        $detailRows = $this->normalizeDetails($validated['details']);
        $detailEntries = $this->buildJournalDetailEntries($detailRows, $validated['fkasmtdate'], $customer);
        $voucherNo = trim((string) ($validated['fkasmtno'] ?? '')) ?: $this->generateVoucherNo(Carbon::parse($validated['fkasmtdate']));
        $totalPenerimaan = round((float) $detailRows->sum(fn(array $row) => (float) ($row['fkasdtvalue'] ?? 0)), 2);
        $now = now();

        DB::transaction(function () use ($validated, $customer, $headerAccount, $detailEntries, $voucherNo, $totalPenerimaan, $now) {
            $headerId = $this->nextIntegerId('trkasmt', 'fkasmtid');
            $nextDetailId = $this->nextIntegerId('trkasdt', 'fkasdtid');

            Trkasmt::create([
                'fkasmtid' => $headerId,
                'fkasmtno' => $voucherNo,
                'ftrancode' => self::TRAN_CODE,
                'fkasmtdate' => $validated['fkasmtdate'],
                'frate' => 1,
                'fwhom' => $customer->fcustomername,
                'faccountheader' => $headerAccount->faccount,
                'faccountheaderid' => $headerAccount->faccid,
                'fdkheader' => 'D',
                'fcustomer' => $customer->fcustomerid,
                'fket' => $validated['fket'] ?? null,
                'famountpay' => $totalPenerimaan,
                'famountpay_rp' => $totalPenerimaan,
                'fuserid' => $this->currentUserId(),
                'fdatetime' => $now,
                'fgiromundur' => $validated['fgiromundur'] ?? '0',
                'fnogiro' => $validated['fnogiro'] ?? null,
                'ftgljatuhtempo' => !empty($validated['ftgljatuhtempo']) ? Carbon::parse($validated['ftgljatuhtempo'])->startOfDay() : null,
                'faccountno' => $headerAccount->faccount,
                'faccountnoid' => $headerAccount->faccid,
                'fstatusgiro' => '0',
                'fbranchcode' => $validated['fbranchcode'],
            ]);

            foreach ($detailEntries as $index => $entry) {
                Trkasdt::create([
                    'fkasdtid' => $nextDetailId + $index,
                    'fkasmtid' => $headerId,
                    'ftrancode' => self::TRAN_CODE,
                    'faccount' => $entry['account']->faccount,
                    'faccountid' => $entry['account']->faccid,
                    'fdk' => $entry['fdk'],
                    'frefno' => $entry['frefno'],
                    'fnote' => $entry['fnote'],
                    'fsubaccount' => $entry['fsubaccount'],
                    'fdiscpersen' => $entry['fdiscpersen'],
                    'fdiscount' => $entry['fdiscount'],
                    'fkasdtvalue' => $entry['fkasdtvalue'],
                    'fvalue_rp' => $entry['fvalue_rp'],
                    'fjurnal' => $entry['fjurnal'],
                    'fjurnal_rp' => $entry['fjurnal_rp'],
                    'fuserid' => $this->currentUserId(),
                    'fdatetime' => $now,
                    'fdiscountrp' => $entry['fdiscountrp'],
                    'fnou' => $index + 1,
                    'freftype' => $entry['ftrcode'],
                ]);
            }
        });

        return redirect()
            ->route('pelunasancustomer.create')
            ->with('success', 'Pelunasan customer ' . $voucherNo . ' berhasil disimpan.');
    }

    public function update(Request $request, $fkasmtno)
    {
        $header = $this->findHeader($fkasmtno);

        $request->merge([
            'details' => $this->filterEmptyDetailRows($request->input('details', [])),
            'fbranchcode' => trim((string) $request->input('fbranchcode', $header->fbranchcode ?: $this->resolveBranchCode())),
            'fgiromundur' => $request->boolean('fgiromundur') ? '1' : '0',
        ]);

        $validated = $request->validate([
            'fkasmtno' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('trkasmt', 'fkasmtno')->ignore($header->fkasmtid, 'fkasmtid'),
            ],
            'fkasmtdate' => ['required', 'date'],
            'fbranchcode' => ['required', 'string', 'max:10'],
            'fcustomer' => ['required', 'string', 'max:20', Rule::exists('mscustomer', 'fcustomercode')],
            'faccountheader' => ['required', 'string', 'max:15', Rule::exists('account', 'faccount')],
            'fnogiro' => ['nullable', 'string', 'max:35'],
            'fgiromundur' => ['nullable', 'in:0,1'],
            'ftgljatuhtempo' => ['nullable', 'date', Rule::requiredIf($request->input('fgiromundur') === '1'), 'after_or_equal:fkasmtdate'],
            'fket' => ['nullable', 'string', 'max:50'],
            'fbiayaadminbank' => ['nullable', 'numeric', 'min:0'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.frefno' => ['required', 'string', 'max:30'],
            'details.*.fnilai_nota' => ['nullable', 'numeric'],
            'details.*.fsisa_piutang' => ['nullable', 'numeric'],
            'details.*.fdiscpersen' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'details.*.fdiscount' => ['nullable', 'numeric'],
            'details.*.fkasdtvalue' => ['required', 'numeric', 'not_in:0'],
            'details.*.ftrcode' => ['nullable', 'string', 'max:10'],
        ], [
            'fkasmtdate.required' => 'Tanggal wajib diisi.',
            'fcustomer.required' => 'Customer wajib dipilih.',
            'faccountheader.required' => 'Account wajib dipilih.',
            'ftgljatuhtempo.required' => 'Tgl. jatuh tempo wajib diisi saat giro mundur aktif.',
            'details.required' => 'Minimal 1 detail faktur wajib diisi.',
            'details.*.frefno.required' => 'No. nota wajib diisi.',
            'details.*.fkasdtvalue.required' => 'Total bayar wajib diisi.',
            'details.*.fkasdtvalue.not_in' => 'Total bayar tidak boleh 0.',
        ]);

        $customer = Customer::query()
            ->where('fcustomercode', $validated['fcustomer'])
            ->firstOrFail(['fcustomerid', 'fcustomercode', 'fcustomername']);
        $headerAccount = Account::query()
            ->where('faccount', $validated['faccountheader'])
            ->firstOrFail(['faccid', 'faccount', 'faccname']);
        $detailRows = $this->normalizeDetails($validated['details']);
        $detailEntries = $this->buildJournalDetailEntries($detailRows, $validated['fkasmtdate'], $customer);
        $voucherNo = trim((string) ($validated['fkasmtno'] ?? '')) ?: $header->fkasmtno;
        $totalPenerimaan = round((float) $detailRows->sum(fn(array $row) => (float) ($row['fkasdtvalue'] ?? 0)), 2);
        $now = now();

        DB::transaction(function () use ($validated, $customer, $headerAccount, $detailEntries, $voucherNo, $totalPenerimaan, $now, $header) {
            $header->update([
                'fkasmtno' => $voucherNo,
                'fkasmtdate' => $validated['fkasmtdate'],
                'fwhom' => $customer->fcustomername,
                'faccountheader' => $headerAccount->faccount,
                'faccountheaderid' => $headerAccount->faccid,
                'fcustomer' => $customer->fcustomerid,
                'fket' => $validated['fket'] ?? null,
                'famountpay' => $totalPenerimaan,
                'famountpay_rp' => $totalPenerimaan,
                'fuserid' => $this->currentUserId(),
                'fdatetime' => $now,
                'fgiromundur' => $validated['fgiromundur'] ?? '0',
                'fnogiro' => $validated['fnogiro'] ?? null,
                'ftgljatuhtempo' => !empty($validated['ftgljatuhtempo']) ? Carbon::parse($validated['ftgljatuhtempo'])->startOfDay() : null,
                'faccountno' => $headerAccount->faccount,
                'faccountnoid' => $headerAccount->faccid,
                'fbranchcode' => $validated['fbranchcode'],
            ]);

            Trkasdt::where('fkasmtid', $header->fkasmtid)->delete();

            $nextDetailId = $this->nextIntegerId('trkasdt', 'fkasdtid');
            foreach ($detailEntries as $index => $entry) {
                Trkasdt::create([
                    'fkasdtid' => $nextDetailId + $index,
                    'fkasmtid' => $header->fkasmtid,
                    'ftrancode' => self::TRAN_CODE,
                    'faccount' => $entry['account']->faccount,
                    'faccountid' => $entry['account']->faccid,
                    'fdk' => $entry['fdk'],
                    'frefno' => $entry['frefno'],
                    'fnote' => $entry['fnote'],
                    'fsubaccount' => $entry['fsubaccount'],
                    'fdiscpersen' => $entry['fdiscpersen'],
                    'fdiscount' => $entry['fdiscount'],
                    'fkasdtvalue' => $entry['fkasdtvalue'],
                    'fvalue_rp' => $entry['fvalue_rp'],
                    'fjurnal' => $entry['fjurnal'],
                    'fjurnal_rp' => $entry['fjurnal_rp'],
                    'fuserid' => $this->currentUserId(),
                    'fdatetime' => $now,
                    'fdiscountrp' => $entry['fdiscountrp'],
                    'fnou' => $index + 1,
                    'freftype' => $entry['ftrcode'],
                ]);
            }
        });

        return redirect()
            ->route('pelunasancustomer.edit', $voucherNo)
            ->with('success', 'Pelunasan customer ' . $voucherNo . ' berhasil diperbarui.');
    }

    public function destroy($fkasmtno)
    {
        $header = $this->findHeader($fkasmtno);

        DB::transaction(function () use ($header) {
            Trkasdt::where('fkasmtid', $header->fkasmtid)->delete();
            $header->delete();
        });

        return redirect()
            ->route('pelunasancustomer.index')
            ->with('success', 'Pelunasan customer ' . $fkasmtno . ' berhasil dihapus.');
    }

    private function filterEmptyDetailRows(array $details): array
    {
        return collect($details)
            ->filter(function ($detail) {
                if (!is_array($detail)) {
                    return false;
                }

                return trim((string) ($detail['frefno'] ?? '')) !== ''
                    || trim((string) ($detail['fnilai_nota'] ?? '')) !== ''
                    || trim((string) ($detail['fsisa_piutang'] ?? '')) !== ''
                    || trim((string) ($detail['fdiscpersen'] ?? '')) !== ''
                    || trim((string) ($detail['fdiscount'] ?? '')) !== ''
                    || trim((string) ($detail['fkasdtvalue'] ?? '')) !== ''
                    || trim((string) ($detail['ftrcode'] ?? '')) !== '';
            })
            ->values()
            ->all();
    }

    private function normalizeDetails(array $details): Collection
    {
        return collect($details)
            ->map(function (array $detail) {
                $trCode = strtoupper(trim((string) ($detail['ftrcode'] ?? 'INV')));

                return [
                    'frefno' => trim((string) ($detail['frefno'] ?? '')),
                    'fnilai_nota' => round(abs((float) ($detail['fnilai_nota'] ?? 0)), 2),
                    'fsisa_piutang' => round(abs((float) ($detail['fsisa_piutang'] ?? 0)), 2),
                    'fdiscpersen' => round((float) ($detail['fdiscpersen'] ?? 0), 2),
                    'fdiscount' => round(abs((float) ($detail['fdiscount'] ?? 0)), 2),
                    'fkasdtvalue' => round(abs((float) ($detail['fkasdtvalue'] ?? 0)), 2),
                    'ftrcode' => $trCode !== '' ? $trCode : 'INV',
                ];
            })
            ->filter(fn(array $detail) => $detail['frefno'] !== '' && (float) ($detail['fkasdtvalue'] ?? 0) !== 0.0)
            ->values();
    }

    private function buildJournalDetailEntries(Collection $detailRows, string $paymentDate, Customer $customer): Collection
    {
        $receivableAccount = $this->resolveRequiredAccount(self::RECEIVABLE_SET_ACCOUNT, 'Akun piutang dagang belum disetting.');
        $returnReceivableAccount = $this->resolveRequiredAccountFromCandidates(
            self::RETURN_RECEIVABLE_SET_ACCOUNT_CANDIDATES,
            'Akun retur penjualan belum potong piutang belum disetting.'
        );
        $referenceMap = $this->resolveReferenceTransactions($detailRows, Carbon::parse($paymentDate));

        return $detailRows->values()->map(function (array $row) use ($customer, $receivableAccount, $returnReceivableAccount, $referenceMap) {
            $refNo = trim((string) ($row['frefno'] ?? ''));
            $reference = $referenceMap[$refNo] ?? null;
            $trCode = strtoupper(trim((string) ($row['ftrcode'] ?? $reference?->ftrcode ?? 'INV')));
            $paymentAmount = round(abs((float) ($row['fkasdtvalue'] ?? 0)), 2);
            $discountAmount = round(abs((float) ($row['fdiscount'] ?? 0)), 2);
            $journalAmount = $trCode === 'REJ'
                ? $paymentAmount
                : round($paymentAmount + $discountAmount, 2);
            $account = $trCode === 'REJ' ? $returnReceivableAccount : $receivableAccount;
            $note = $trCode === 'REJ'
                ? 'Retur ' . $customer->fcustomername
                : $customer->fcustomername;

            return [
                'account' => $account,
                'fdk' => $trCode === 'REJ' ? 'D' : 'K',
                'frefno' => $refNo,
                'fnote' => $note,
                'fsubaccount' => $trCode === 'REJ' ? null : $customer->fcustomercode,
                'fdiscpersen' => round((float) ($row['fdiscpersen'] ?? 0), 2),
                'fdiscount' => $discountAmount,
                'fdiscountrp' => $discountAmount,
                'fkasdtvalue' => $paymentAmount,
                'fvalue_rp' => $journalAmount,
                'fjurnal' => $journalAmount,
                'fjurnal_rp' => $journalAmount,
                'ftrcode' => $trCode,
            ];
        });
    }

    private function resolveReferenceTransactions(Collection $detailRows, Carbon $paymentDate): array
    {
        $refNos = $detailRows
            ->pluck('frefno')
            ->map(fn($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        $references = Tranmt::query()
            ->whereIn('fsono', $refNos)
            ->whereIn('ftrcode', ['INV', 'REJ'])
            ->get(['fsono', 'ftrcode', 'fsodate'])
            ->keyBy(fn($row) => trim((string) $row->fsono));

        foreach ($detailRows as $index => $row) {
            $refNo = trim((string) ($row['frefno'] ?? ''));
            $trCode = strtoupper(trim((string) ($row['ftrcode'] ?? 'INV')));
            $reference = $references->get($refNo);

            if (!$reference) {
                throw ValidationException::withMessages([
                    "details.{$index}.frefno" => "No. nota {$refNo} tidak ditemukan.",
                ]);
            }

            if (strtoupper(trim((string) ($reference->ftrcode ?? ''))) !== $trCode) {
                throw ValidationException::withMessages([
                    "details.{$index}.frefno" => "Tipe transaksi nota {$refNo} tidak sesuai.",
                ]);
            }

            $referenceDate = !empty($reference->fsodate)
                ? Carbon::parse($reference->fsodate)->startOfDay()
                : null;

            if ($referenceDate && $referenceDate->gt($paymentDate->copy()->startOfDay())) {
                $label = $trCode === 'REJ' ? 'Tanggal retur' : 'Tanggal faktur';

                throw ValidationException::withMessages([
                    "details.{$index}.frefno" => "{$label} {$refNo} tidak boleh lebih besar dari tanggal bayar.",
                ]);
            }
        }

        return $references->all();
    }

    private function resolveRequiredAccount(string $accountName, string $message): Account
    {
        $accountCode = $this->resolveSetAccountCode($accountName);

        if (!$accountCode) {
            throw ValidationException::withMessages([
                'faccountheader' => $message,
            ]);
        }

        return Account::query()
            ->where('faccount', $accountCode)
            ->first(['faccid', 'faccount', 'faccname'])
            ?? throw ValidationException::withMessages([
                'faccountheader' => $message,
            ]);
    }

    private function resolveRequiredAccountFromCandidates(array $accountNames, string $message): Account
    {
        foreach ($accountNames as $accountName) {
            $accountCode = $this->resolveSetAccountCode($accountName);

            if (!$accountCode) {
                continue;
            }

            $account = Account::query()
                ->where('faccount', $accountCode)
                ->first(['faccid', 'faccount', 'faccname']);

            if ($account) {
                return $account;
            }
        }

        throw ValidationException::withMessages([
            'faccountheader' => $message,
        ]);
    }

    private function resolveBranchCode(): string
    {
        $branch = Auth::guard('sysuser')->user()?->fcabang
            ?? Auth::user()?->fcabang
            ?? session('fcabang');

        if ($branch !== null) {
            $needle = trim((string) $branch);

            if ($needle !== '') {
                if (is_numeric($needle)) {
                    $kodeCabang = DB::table('mscabang')
                        ->where('fcabangid', (int) $needle)
                        ->value('fcabangkode');
                } else {
                    $kodeCabang = DB::table('mscabang')
                        ->whereRaw('LOWER(fcabangkode) = LOWER(?)', [$needle])
                        ->value('fcabangkode');

                    if (!$kodeCabang) {
                        $kodeCabang = DB::table('mscabang')
                            ->whereRaw('LOWER(fcabangname) = LOWER(?)', [$needle])
                            ->value('fcabangkode');
                    }
                }

                if (!empty($kodeCabang)) {
                    return trim((string) $kodeCabang);
                }
            }
        }

        return 'NA';
    }

    private function nextIntegerId(string $table, string $column): int
    {
        return ((int) DB::table($table)->max($column)) + 1;
    }

    private function currentUserId(): ?string
    {
        $user = Auth::user();

        return $user->fsysuserid ?? $user->name ?? $user->fname ?? null;
    }

    private function generateVoucherNo(Carbon $date): string
    {
        $prefix = 'BKM.' . $date->format('ym') . '.';
        $lastNumber = DB::table('trkasmt')
            ->where('fkasmtno', 'like', $prefix . '%')
            ->selectRaw("MAX(CAST(split_part(fkasmtno, '.', 3) AS integer)) as last_no")
            ->value('last_no');

        return $prefix . str_pad((string) (((int) $lastNumber) + 1), 4, '0', STR_PAD_LEFT);
    }

    private function findHeader(string $fkasmtno): Trkasmt
    {
        return Trkasmt::query()
            ->with(['details', 'headerAccount'])
            ->where('ftrancode', self::TRAN_CODE)
            ->where('fkasmtno', $fkasmtno)
            ->firstOrFail();
    }

    private function formViewData(?Trkasmt $header = null, array $overrides = []): array
    {
        $customerCode = trim((string) old('fcustomer', ''));
        $accountCode = trim((string) old('faccountheader', ''));

        $selectedCustomer = null;
        if ($customerCode !== '') {
            $selectedCustomer = Customer::query()
                ->where('fcustomercode', $customerCode)
                ->first(['fcustomerid', 'fcustomercode', 'fcustomername', 'ftempo']);
        } elseif ($header && !empty($header->fcustomer)) {
            $selectedCustomer = Customer::query()
                ->where('fcustomerid', $header->fcustomer)
                ->first(['fcustomerid', 'fcustomercode', 'fcustomername', 'ftempo']);
        }

        $selectedAccount = null;
        if ($accountCode !== '') {
            $selectedAccount = Account::query()
                ->where('faccount', $accountCode)
                ->first(['faccid', 'faccount', 'faccname']);
        } elseif ($header && !empty($header->faccountheader)) {
            $selectedAccount = Account::query()
                ->where('faccount', $header->faccountheader)
                ->first(['faccid', 'faccount', 'faccname']);
        }

        $detailRows = $header
            ? $header->details->values()->map(function ($detail, $index) {
                return [
                    'uid' => 'pc-existing-' . $index . '-' . $detail->fkasdtid,
                    'frefno' => trim((string) ($detail->frefno ?? '')),
                    'fnilai_nota' => (float) ($detail->fvalue_rp ?? $detail->fjurnal_rp ?? $detail->fkasdtvalue ?? 0),
                    'fsisa_piutang' => (float) ($detail->fvalue_rp ?? $detail->fjurnal_rp ?? $detail->fkasdtvalue ?? 0),
                    'fdiscpersen' => (float) ($detail->fdiscpersen ?? 0),
                    'fdiscount' => (float) ($detail->fdiscount ?? 0),
                    'fkasdtvalue' => (float) ($detail->fkasdtvalue ?? 0),
                    'ftrcode' => trim((string) ($detail->freftype ?? 'INV')),
                ];
            })->all()
            : [];

        return array_merge([
            'voucherNo' => old('fkasmtno', $header?->fkasmtno),
            'transactionDate' => old('fkasmtdate', optional($header?->fkasmtdate)->format('Y-m-d') ?? now()->format('Y-m-d')),
            'currentBranchCode' => old('fbranchcode', $header?->fbranchcode ?: $this->resolveBranchCode()),
            'selectedCustomer' => $selectedCustomer,
            'selectedAccount' => $selectedAccount,
            'detailRows' => $detailRows,
            'headerData' => $header,
            'bankAdminFee' => old('fbiayaadminbank', 0),
            'dueDate' => old('ftgljatuhtempo', optional($header?->ftgljatuhtempo)->format('Y-m-d')),
            'giroMundur' => old('fgiromundur', ($header?->fgiromundur ?? '0')) === '1',
            'noteValue' => old('fket', $header?->fket),
            'giroNo' => old('fnogiro', $header?->fnogiro),
        ], $overrides);
    }
}
