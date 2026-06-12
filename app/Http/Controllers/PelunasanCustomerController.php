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
    private const TRAN_CODE = 'RCP';
    private const GIRO_MUNDUR_ACCOUNT_NAME = 'PIUTANGGIRO';

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
                            DISTINCT NULLIF(TRIM(COALESCE(CASE WHEN TRIM(COALESCE(dt.freftype, '')) != 'ADM' THEN dt.frefno ELSE NULL END, '')), ''),
                            ', ' ORDER BY NULLIF(TRIM(COALESCE(CASE WHEN TRIM(COALESCE(dt.freftype, '')) != 'ADM' THEN dt.frefno ELSE NULL END, '')), '')
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
                DB::raw("ABS(COALESCE(SUM(COALESCE(CASE WHEN TRIM(COALESCE(dt.freftype, '')) != 'ADM' THEN dt.fkasdtvalue ELSE 0 END, 0)), COALESCE(trkasmt.famountpay, 0), 0)) as payment_amount"),
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
                'mt.famountso',
                'mt.famountremain',
                'mt.fjatuhtempo',
                'c.fcustomername',
                'c.ftempo',
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
                'c.fcustomername',
                'c.ftempo'
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
                $trCode = trim((string) ($row->ftrcode ?? ''));
                $amount = (float) ($row->famount ?? 0);
                $amountRemain = (float) ($row->famountremain ?? 0);
                $amountSo = (float) ($row->famountso ?? 0);

                if (strtoupper($trCode) === 'REJ') {
                    $amount *= -1;
                    $amountSo *= -1;
                    if ($amountRemain < 0) {
                        $amountRemain *= -1;
                    }
                }

                return [
                    'ftranmtid' => (int) ($row->ftranmtid ?? 0),
                    'fsono' => trim((string) ($row->fsono ?? '')),
                    'fsodate' => !empty($row->fsodate) ? Carbon::parse($row->fsodate)->format('Y-m-d') : null,
                    'fcustno' => trim((string) ($row->fcustno ?? '')),
                    'fcustomername' => trim((string) ($row->fcustomername ?? '')),
                    'ftempo' => (int) ($row->ftempo ?? 0),
                    'ftrcode' => $trCode,
                    'famount' => $amount,
                    'famountso' => $amountSo,
                    'famountremain' => $amountRemain,
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
        $isGiroMundur = $request->boolean('fgiromundur');
        $giroAccount = trim((string) $this->resolveSetAccountCode(self::GIRO_MUNDUR_ACCOUNT_NAME));

        $request->merge([
            'details' => $this->filterEmptyDetailRows($request->input('details', [])),
            'fbranchcode' => trim((string) $request->input('fbranchcode', $this->resolveBranchCode())),
            'fgiromundur' => $isGiroMundur ? '1' : '0',
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
            'faccountheader' => ['required'],
            'fnogiro' => ['nullable', 'string', 'max:35'],
            'fgiromundur' => ['nullable', 'in:0,1'],
            'ftgljatuhtempo' => ['nullable', 'date', Rule::requiredIf($isGiroMundur), 'before_or_equal:fkasmtdate'],
            'fket' => ['nullable', 'string', 'max:50'],
            'fbiayaadminbank' => ['nullable', 'numeric', 'min:0'],
            'fhargaadmin' => ['nullable', 'numeric', 'min:0'],
            'faccountadmin' => [
                'nullable',
                'string',
                'max:15',
                Rule::exists('account', 'faccount')->where(function ($query) {
                    $query->where('fend', 1);
                }),
            ],
            'fhargaadmin2' => ['nullable', 'numeric', 'min:0'],
            'faccountadmin2' => [
                'nullable',
                'string',
                'max:15',
                Rule::exists('account', 'faccount')->where(function ($query) {
                    $query->where('fend', 1);
                }),
            ],
            'details' => ['required', 'array', 'min:1'],
            'details.*.frefno' => ['required', 'string', 'max:30'],
            'details.*.fdatetime' => ['nullable', 'date'],
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
            'faccountheader.exists' => 'Account tidak valid atau bukan account detail (fend=1).',
            'ftgljatuhtempo.required' => 'Tgl. jatuh tempo wajib diisi saat giro mundur aktif.',
            'ftgljatuhtempo.before_or_equal' => 'Tgl. jatuh tempo tidak boleh melebihi tanggal transaksi.',
            'faccountadmin.exists' => 'Account admin bank tidak valid atau bukan account detail (fend=1).',
            'faccountadmin2.exists' => 'Account admin bank 2 tidak valid atau bukan account detail (fend=1).',
            'details.required' => 'Minimal 1 detail faktur wajib diisi.',
            'details.*.frefno.required' => 'No. nota wajib diisi.',
            'details.*.fkasdtvalue.required' => 'Total bayar wajib diisi.',
            'details.*.fkasdtvalue.not_in' => 'Total bayar tidak boleh 0.',
        ]);

        if ($isGiroMundur && $giroAccount !== '') {
            $validated['faccountheader'] = $giroAccount;
        }

        $customer = Customer::query()
            ->where('fcustomercode', $validated['fcustomer'])
            ->firstOrFail(['fcustomerid', 'fcustomercode', 'fcustomername']);
        $headerAccount = Account::query()
            ->where('faccount', $validated['faccountheader'])
            ->firstOrFail(['faccid', 'faccount', 'faccname', 'finitjurnal']);
        $detailRows = $this->normalizeDetails($validated['details']);
        $this->validateReferenceCustomers($detailRows, $customer->fcustomercode);
        $this->validatePaymentDoesNotExceedRemainingReceivable($detailRows);
        $detailEntries = $this->buildJournalDetailEntries($detailRows, $validated['fkasmtdate'], $customer);
        $bankAdminFee = round((float) ($validated['fbiayaadminbank'] ?? 0), 2);
        if ($bankAdminFee > 0 && !empty($validated['faccountadmin'])) {
            $adminAccount = Account::query()
                ->where('faccount', $validated['faccountadmin'])
                ->firstOrFail(['faccid', 'faccount', 'faccname']);

            $detailEntries->push([
                'account' => $adminAccount,
                'fdk' => 'D',
                'frefno' => 'ADM',
                'fnote' => 'BIAYA ADMIN BANK',
                'fsubaccount' => null,
                'fdiscpersen' => 0,
                'fdiscount' => 0,
                'fdiscountrp' => 0,
                'fkasdtvalue' => $bankAdminFee,
                'fvalue_rp' => $bankAdminFee,
                'fjurnal' => $bankAdminFee,
                'fjurnal_rp' => $bankAdminFee,
                'ftrcode' => 'ADM',
            ]);
        }
        $voucherNo = trim((string) ($validated['fkasmtno'] ?? '')) ?: $this->generateVoucherNo(Carbon::parse($validated['fkasmtdate']), $headerAccount);
        $hargaAdmin = round((float) ($validated['fhargaadmin'] ?? 0), 2);
        $hargaAdmin2 = round((float) ($validated['fhargaadmin2'] ?? 0), 2);
        $totalPenerimaan = round((float) $detailRows->sum(fn(array $row) => (float) ($row['fkasdtvalue'] ?? 0)), 2);
        $netPaymentAmount = round($totalPenerimaan - $bankAdminFee - $hargaAdmin - $hargaAdmin2, 2);
        $now = now();

        DB::transaction(function () use ($validated, $customer, $headerAccount, $detailEntries, $voucherNo, $netPaymentAmount, $now) {
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
                'fdkheader' => $this->resolveHeaderDk($netPaymentAmount),
                'fcustomer' => $customer->fcustomerid,
                'fket' => $validated['fket'] ?? null,
                'famountpay' => $netPaymentAmount,
                'famountpay_rp' => $netPaymentAmount,
                'fuserid' => $this->currentUserId(),
                'fdatetime' => $now,
                'fgiromundur' => $validated['fgiromundur'] ?? '0',
                'fnogiro' => $validated['fnogiro'] ?? null,
                'ftgljatuhtempo' => !empty($validated['ftgljatuhtempo']) ? Carbon::parse($validated['ftgljatuhtempo'])->startOfDay() : null,
                'faccountno' => $headerAccount->faccount,
                'faccountnoid' => $headerAccount->faccid,
                'fstatusgiro' => '0',
                'fbranchcode' => $validated['fbranchcode'],
                'faccadj' => $validated['faccountadmin'] ?? null,
                'fadjustment' => (float) ($validated['fhargaadmin'] ?? 0),
                'faccadj2' => $validated['faccountadmin2'] ?? null,
                'fadjustment2' => (float) ($validated['fhargaadmin2'] ?? 0),
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
                    'fdatetime' => !empty($entry['fdatetime']) ? Carbon::parse($entry['fdatetime']) : $now,
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
        $isGiroMundur = $request->boolean('fgiromundur');
        $giroAccount = trim((string) $this->resolveSetAccountCode(self::GIRO_MUNDUR_ACCOUNT_NAME));

        $request->merge([
            'details' => $this->filterEmptyDetailRows($request->input('details', [])),
            'fbranchcode' => trim((string) $request->input('fbranchcode', $header->fbranchcode ?: $this->resolveBranchCode())),
            'fgiromundur' => $isGiroMundur ? '1' : '0',
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
            'faccountheader' => ['required'],
            'fnogiro' => ['nullable', 'string', 'max:35'],
            'fgiromundur' => ['nullable', 'in:0,1'],
            'ftgljatuhtempo' => ['nullable', 'date', Rule::requiredIf($isGiroMundur), 'before_or_equal:fkasmtdate'],
            'fket' => ['nullable', 'string', 'max:50'],
            'fbiayaadminbank' => ['nullable', 'numeric', 'min:0'],
            'fhargaadmin' => ['nullable', 'numeric', 'min:0'],
            'faccountadmin' => [
                'nullable',
                'string',
                'max:15',
                Rule::exists('account', 'faccount')->where(function ($query) {
                    $query->where('fend', 1);
                }),
            ],
            'fhargaadmin2' => ['nullable', 'numeric', 'min:0'],
            'faccountadmin2' => [
                'nullable',
                'string',
                'max:15',
                Rule::exists('account', 'faccount')->where(function ($query) {
                    $query->where('fend', 1);
                }),
            ],
            'details' => ['required', 'array', 'min:1'],
            'details.*.frefno' => ['required', 'string', 'max:30'],
            'details.*.fdatetime' => ['nullable', 'date'],
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
            'faccountheader.exists' => 'Account tidak valid atau bukan account detail (fend=1).',
            'ftgljatuhtempo.required' => 'Tgl. jatuh tempo wajib diisi saat giro mundur aktif.',
            'ftgljatuhtempo.before_or_equal' => 'Tgl. jatuh tempo tidak boleh melebihi tanggal transaksi.',
            'faccountadmin.exists' => 'Account admin bank tidak valid atau bukan account detail (fend=1).',
            'faccountadmin2.exists' => 'Account admin bank 2 tidak valid atau bukan account detail (fend=1).',
            'details.required' => 'Minimal 1 detail faktur wajib diisi.',
            'details.*.frefno.required' => 'No. nota wajib diisi.',
            'details.*.fkasdtvalue.required' => 'Total bayar wajib diisi.',
            'details.*.fkasdtvalue.not_in' => 'Total bayar tidak boleh 0.',
        ]);

        if ($isGiroMundur && $giroAccount !== '') {
            $validated['faccountheader'] = $giroAccount;
        }

        $customer = Customer::query()
            ->where('fcustomercode', $validated['fcustomer'])
            ->firstOrFail(['fcustomerid', 'fcustomercode', 'fcustomername']);
        $headerAccount = Account::query()
            ->where('faccount', $validated['faccountheader'])
            ->firstOrFail(['faccid', 'faccount', 'faccname', 'finitjurnal']);
        $detailRows = $this->normalizeDetails($validated['details']);
        $this->validateReferenceCustomers($detailRows, $customer->fcustomercode);
        $this->validatePaymentDoesNotExceedRemainingReceivable($detailRows, $header);
        $detailEntries = $this->buildJournalDetailEntries($detailRows, $validated['fkasmtdate'], $customer);
        $bankAdminFee = round((float) ($validated['fbiayaadminbank'] ?? 0), 2);
        if ($bankAdminFee > 0 && !empty($validated['faccountadmin'])) {
            $adminAccount = Account::query()
                ->where('faccount', $validated['faccountadmin'])
                ->firstOrFail(['faccid', 'faccount', 'faccname']);

            $detailEntries->push([
                'account' => $adminAccount,
                'fdk' => 'D',
                'frefno' => 'ADM',
                'fnote' => 'BIAYA ADMIN BANK',
                'fsubaccount' => null,
                'fdiscpersen' => 0,
                'fdiscount' => 0,
                'fdiscountrp' => 0,
                'fkasdtvalue' => $bankAdminFee,
                'fvalue_rp' => $bankAdminFee,
                'fjurnal' => $bankAdminFee,
                'fjurnal_rp' => $bankAdminFee,
                'ftrcode' => 'ADM',
            ]);
        }
        $voucherNo = trim((string) ($validated['fkasmtno'] ?? '')) ?: $header->fkasmtno;
        $hargaAdmin = round((float) ($validated['fhargaadmin'] ?? 0), 2);
        $hargaAdmin2 = round((float) ($validated['fhargaadmin2'] ?? 0), 2);
        $totalPenerimaan = round((float) $detailRows->sum(fn(array $row) => (float) ($row['fkasdtvalue'] ?? 0)), 2);
        $netPaymentAmount = round($totalPenerimaan - $bankAdminFee - $hargaAdmin - $hargaAdmin2, 2);
        $now = now();

        DB::transaction(function () use ($validated, $customer, $headerAccount, $detailEntries, $voucherNo, $netPaymentAmount, $now, $header) {
            $header->update([
                'fkasmtno' => $voucherNo,
                'fkasmtdate' => $validated['fkasmtdate'],
                'fwhom' => $customer->fcustomername,
                'faccountheader' => $headerAccount->faccount,
                'faccountheaderid' => $headerAccount->faccid,
                'fdkheader' => $this->resolveHeaderDk($netPaymentAmount),
                'fcustomer' => $customer->fcustomerid,
                'fket' => $validated['fket'] ?? null,
                'famountpay' => $netPaymentAmount,
                'famountpay_rp' => $netPaymentAmount,
                'fuserid' => $this->currentUserId(),
                'fdatetime' => $now,
                'fgiromundur' => $validated['fgiromundur'] ?? '0',
                'fnogiro' => $validated['fnogiro'] ?? null,
                'ftgljatuhtempo' => !empty($validated['ftgljatuhtempo']) ? Carbon::parse($validated['ftgljatuhtempo'])->startOfDay() : null,
                'faccountno' => $headerAccount->faccount,
                'faccountnoid' => $headerAccount->faccid,
                'fbranchcode' => $validated['fbranchcode'],
                'faccadj' => $validated['faccountadmin'] ?? null,
                'fadjustment' => (float) ($validated['fhargaadmin'] ?? 0),
                'faccadj2' => $validated['faccountadmin2'] ?? null,
                'fadjustment2' => (float) ($validated['fhargaadmin2'] ?? 0),
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
                    'fdatetime' => !empty($entry['fdatetime']) ? Carbon::parse($entry['fdatetime']) : $now,
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

                $frefno = trim((string) ($detail['frefno'] ?? ''));
                $fkasdtvalue = (float) ($detail['fkasdtvalue'] ?? 0);

                // Jika frefno kosong dan fkasdtvalue adalah 0, anggap baris ini kosong/diabaikan
                if ($frefno === '' && $fkasdtvalue === 0.0) {
                    return false;
                }

                return true;
            })
            ->values()
            ->all();
    }

    private function normalizeDetails(array $details): Collection
    {
        return collect($details)
            ->map(function (array $detail) {
                $trCode = strtoupper(trim((string) ($detail['ftrcode'] ?? 'INV')));

                $sisa = round(abs((float) ($detail['fsisa_piutang'] ?? 0)), 2);

                return [
                    'frefno' => trim((string) ($detail['frefno'] ?? '')),
                    'fdatetime' => !empty($detail['fdatetime']) ? Carbon::parse($detail['fdatetime'])->format('Y-m-d') : null,
                    'fnilai_nota' => round(abs((float) ($detail['fnilai_nota'] ?? 0)), 2),
                    'fsisa_piutang' => $sisa,
                    'fdiscpersen' => $trCode === 'REJ' ? 0 : round((float) ($detail['fdiscpersen'] ?? 0), 2),
                    'fdiscount' => $trCode === 'REJ' ? 0 : round(abs((float) ($detail['fdiscount'] ?? 0)), 2),
                    'fkasdtvalue' => round(abs((float) ($detail['fkasdtvalue'] ?? 0)), 2),
                    'ftrcode' => $trCode !== '' ? $trCode : 'INV',
                ];
            })
            ->filter(fn(array $detail) => $detail['frefno'] !== '' && (float) ($detail['fkasdtvalue'] ?? 0) !== 0.0)
            ->values();
    }

    private function buildJournalDetailEntries(Collection $detailRows, string $paymentDate, Customer $customer): Collection
    {
        $receivableCode = trim((string) DB::table('set_account')->where('faccount_name', 'PIUTANGDAGANG')->value('faccount'));
        $receivableAccount = $receivableCode !== ''
            ? Account::query()->where('faccount', $receivableCode)->where('fend', 1)->first(['faccid', 'faccount', 'faccname'])
            : null;

        $returnReceivableCode = trim((string) DB::table('set_account')->where('faccount_name', 'RETJUALBLMPOTPIUTANG')->value('faccount'));
        $returnReceivableAccount = $returnReceivableCode !== ''
            ? Account::query()->where('faccount', $returnReceivableCode)->where('fend', 1)->first(['faccid', 'faccount', 'faccname'])
            : null;

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
                'fdatetime' => $row['fdatetime'] ?? null,
                'ftrcode' => $trCode,
            ];
        });
    }

    private function validatePaymentDoesNotExceedRemainingReceivable(Collection $detailRows, ?Trkasmt $exceptHeader = null): void
    {
        $refNos = $detailRows
            ->pluck('frefno')
            ->map(fn($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        if ($refNos->isEmpty()) {
            return;
        }

        $remainingByRef = Tranmt::query()
            ->whereIn('fsono', $refNos)
            ->whereIn('ftrcode', ['INV', 'REJ'])
            ->pluck('famountremain', 'fsono')
            ->mapWithKeys(fn($remain, $refNo) => [trim((string) $refNo) => abs((float) $remain)]);

        $existingPaymentByRef = collect();
        if ($exceptHeader) {
            $existingPaymentByRef = Trkasdt::query()
                ->where('fkasmtid', $exceptHeader->fkasmtid)
                ->whereIn('frefno', $refNos)
                ->whereRaw("TRIM(COALESCE(freftype, '')) != 'ADM'")
                ->selectRaw("TRIM(COALESCE(frefno, '')) as frefno, SUM(ABS(COALESCE(fkasdtvalue, 0))) as total_payment")
                ->groupByRaw("TRIM(COALESCE(frefno, ''))")
                ->pluck('total_payment', 'frefno')
                ->mapWithKeys(fn($payment, $refNo) => [trim((string) $refNo) => (float) $payment]);
        }

        foreach ($detailRows as $index => $row) {
            $refNo = trim((string) ($row['frefno'] ?? ''));
            $payment = round(abs((float) ($row['fkasdtvalue'] ?? 0)), 2);
            $allowed = round(($remainingByRef->get($refNo, 0) + $existingPaymentByRef->get($refNo, 0)), 2);

            if ($payment > $allowed) {
                throw ValidationException::withMessages([
                    "details.{$index}.fkasdtvalue" => 'Total bayar tidak boleh melebihi sisa piutang.',
                ]);
            }
        }
    }

    private function validateReferenceCustomers(Collection $detailRows, string $customerCode): void
    {
        $customerCode = trim($customerCode);
        $refNos = $detailRows
            ->pluck('frefno')
            ->map(fn($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        if ($customerCode === '' || $refNos->isEmpty()) {
            return;
        }

        $customerByRef = Tranmt::query()
            ->whereIn('fsono', $refNos)
            ->whereIn('ftrcode', ['INV', 'REJ'])
            ->pluck('fcustno', 'fsono')
            ->mapWithKeys(fn($custNo, $refNo) => [trim((string) $refNo) => trim((string) $custNo)]);

        foreach ($detailRows as $index => $row) {
            $refNo = trim((string) ($row['frefno'] ?? ''));
            $refCustomer = $customerByRef->get($refNo, '');

            if ($refCustomer === '') {
                throw ValidationException::withMessages([
                    "details.{$index}.frefno" => 'no customer exists.',
                ]);
            }

            if ($refCustomer !== $customerCode) {
                throw ValidationException::withMessages([
                    "details.{$index}.frefno" => 'Nota harus sesuai customer yang dipilih.',
                ]);
            }
        }
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

        $returStockDateMap = $refNos
            ->filter(fn($refNo) => str_starts_with(strtoupper((string) $refNo), 'REJ.'))
            ->mapWithKeys(function ($refNo) {
                $trimmed = trim((string) $refNo);
                $rebNo = preg_replace('/^REJ\./i', 'REB.', $trimmed);

                return [$trimmed => $rebNo];
            });

        $stockDatesByRebNo = $returStockDateMap->isNotEmpty()
            ? DB::table('trstockmt')
                ->whereIn('fstockmtno', $returStockDateMap->values()->all())
                ->pluck('fstockmtdate', 'fstockmtno')
            : collect();

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

            $referenceDateValue = null;
            $referenceLabel = $trCode === 'REJ' ? 'Tanggal retur' : 'Tanggal faktur';

            if ($trCode === 'REJ') {
                $rebNo = $returStockDateMap->get($refNo);
                $referenceDateValue = $rebNo ? $stockDatesByRebNo->get($rebNo) : null;

                if (empty($referenceDateValue) && !empty($reference->fsodate)) {
                    $referenceDateValue = $reference->fsodate;
                }
            } elseif (!empty($reference->fsodate)) {
                $referenceDateValue = $reference->fsodate;
            }

            $referenceDate = !empty($referenceDateValue)
                ? Carbon::parse($referenceDateValue)->startOfDay()
                : null;

            if ($referenceDate && $referenceDate->gt($paymentDate->copy()->startOfDay())) {
                throw ValidationException::withMessages([
                    "details.{$index}.frefno" => "{$referenceLabel} {$refNo} tidak boleh lebih besar dari tanggal transaksi kas/bank.",
                ]);
            }
        }

        return $references->all();
    }

    private function resolveSetAccountCode(string $accountName): ?string
    {
        $accountCode = DB::table('set_account')
            ->where('faccount_name', $accountName)
            ->value('faccount');

        $accountCode = trim((string) $accountCode);

        return $accountCode !== '' ? $accountCode : null;
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

    private function resolveBranchLabel(?string $branchCode): string
    {
        $branchCode = trim((string) $branchCode);

        if ($branchCode === '') {
            return 'NA';
        }

        $branchName = DB::table('mscabang')
            ->whereRaw('LOWER(fcabangkode) = LOWER(?)', [$branchCode])
            ->value('fcabangname');

        $branchName = trim((string) $branchName);

        return $branchName !== ''
            ? $branchCode . ' - ' . $branchName
            : $branchCode;
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

    private function generateVoucherNo(Carbon $date, ?Account $headerAccount = null): string
    {
        $branchCode = $this->resolveBranchCode();
        $bankType = $this->resolveBankType($headerAccount);
        $prefix = sprintf('%s.%s.%s%s', self::TRAN_CODE, $branchCode, $date->format('ym'), $bankType);

        $lastNumber = DB::table('trkasmt')
            ->where('ftrancode', self::TRAN_CODE)
            ->where('fkasmtno', 'like', $prefix . '%')
            ->selectRaw("
                MAX(
                    CASE
                        WHEN RIGHT(fkasmtno, 4) ~ '^[0-9]{4}$'
                        THEN CAST(RIGHT(fkasmtno, 4) AS integer)
                        ELSE NULL
                    END
                ) as last_no
            ")
            ->value('last_no');

        return $prefix . str_pad((string) (((int) $lastNumber) + 1), 4, '0', STR_PAD_LEFT);
    }

    private function resolveBankType(?Account $headerAccount = null): string
    {
        $bankType = trim((string) ($headerAccount?->finitjurnal ?? ''));

        return $bankType !== '' ? $bankType : '00';
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

        $adminFeeDetail = $header 
            ? $header->details->firstWhere('freftype', 'ADM') 
            : null;

        $bankAdminFee = 0;
        $selectedAdminAccount = null;

        $hargaAdmin = $header ? (float) $header->fadjustment : 0;
        $hargaAdmin2 = $header ? (float) $header->fadjustment2 : 0;

        if ($adminFeeDetail) {
            $bankAdminFee = (float) $adminFeeDetail->fkasdtvalue;
            $selectedAdminAccount = Account::query()
                ->where('faccount', $adminFeeDetail->faccount)
                ->first(['faccid', 'faccount', 'faccname']);
        }

        $referenceRemainMap = collect();
        if ($header) {
            $refNos = $header->details
                ->filter(fn($detail) => trim((string) ($detail->freftype ?? 'INV')) !== 'ADM')
                ->pluck('frefno')
                ->map(fn($value) => trim((string) $value))
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (!empty($refNos)) {
                $referenceRemainMap = Tranmt::query()
                    ->whereIn('fsono', $refNos)
                    ->select(['fsono', 'famountso', 'famountremain', 'ftrcode'])
                    ->get()
                    ->keyBy(fn($row) => trim((string) ($row->fsono ?? '')));
            }
        }

        $detailRows = $header
            ? $header->details
                ->filter(fn($detail) => trim((string)($detail->freftype ?? 'INV')) !== 'ADM')
                ->values()
                ->map(function ($detail, $index) use ($referenceRemainMap) {
                    $trCode = trim((string) ($detail->freftype ?? 'INV'));
                    $baseAmount = (float) ($detail->fvalue_rp ?? $detail->fjurnal_rp ?? $detail->fkasdtvalue ?? 0);
                    $actualPayment = (float) ($detail->fkasdtvalue ?? 0);
                    $actualDiscount = (float) ($detail->fdiscount ?? 0);
                    $paymentAmount = $actualPayment;
                    $discountAmount = $actualDiscount;
                    $refNo = trim((string) ($detail->frefno ?? ''));
                    $reference = $referenceRemainMap->get($refNo);

                    if ($reference) {
                        $currentNotaAmount = (float) ($reference->famountso ?? 0);
                        $currentRemainAmount = (float) ($reference->famountremain ?? 0);

                        if (strtoupper(trim((string) ($reference->ftrcode ?? $trCode))) === 'REJ') {
                            $currentNotaAmount = abs($currentNotaAmount);
                            $currentRemainAmount = abs($currentRemainAmount);
                        }

                        $baseAmount = $currentNotaAmount;
                        $paymentAmount = max($currentRemainAmount - $discountAmount, 0);
                    }

                    if (strtoupper($trCode) === 'REJ') {
                        $baseAmount = $baseAmount < 0 ? $baseAmount * -1 : $baseAmount;
                        $paymentAmount = $paymentAmount < 0 ? $paymentAmount * -1 : $paymentAmount;
                    }

                    $adjustedRemain = $reference
                        ? (strtoupper($trCode) === 'REJ' ? 0 : max(abs((float) ($reference->famountremain ?? 0)) - $actualPayment - $actualDiscount, 0))
                        : $baseAmount;

                    return [
                        'uid' => 'pc-existing-' . $index . '-' . $detail->fkasdtid,
                        'frefno' => $refNo,
                        'fnilai_nota' => $baseAmount,
                        'fsisa_piutang' => $adjustedRemain,
                        'fdiscpersen' => (float) ($detail->fdiscpersen ?? 0),
                        'fdiscount' => $discountAmount,
                        'fkasdtvalue' => $actualPayment,
                        'ftrcode' => $trCode,
                        'fdatetime' => !empty($detail->fdatetime) ? Carbon::parse($detail->fdatetime)->format('Y-m-d') : null,
                    ];
                })->all()
            : [];

        $adminAccountCode = old('faccountadmin', $selectedAdminAccount->faccount ?? $header->faccadj ?? '');
        $selectedAdminAccountModel = null;
        if ($adminAccountCode !== '') {
            $selectedAdminAccountModel = Account::query()
                ->where('faccount', $adminAccountCode)
                ->first(['faccid', 'faccount', 'faccname']);
        }

        $adminAccount2Code = old('faccountadmin2', $header->faccadj2 ?? '');
        $selectedAdminAccount2Model = null;
        if ($adminAccount2Code !== '') {
            $selectedAdminAccount2Model = Account::query()
                ->where('faccount', $adminAccount2Code)
                ->first(['faccid', 'faccount', 'faccname']);
        }

        return array_merge([
            'voucherNo' => old('fkasmtno', $header?->fkasmtno),
            'transactionDate' => old('fkasmtdate', optional($header?->fkasmtdate)->format('Y-m-d') ?? now()->format('Y-m-d')),
            'currentBranchCode' => old('fbranchcode', $header?->fbranchcode ?: $this->resolveBranchCode()),
            'currentBranchLabel' => $this->resolveBranchLabel((string) old('fbranchcode', $header?->fbranchcode ?: $this->resolveBranchCode())),
            'selectedCustomer' => $selectedCustomer,
            'selectedAccount' => $selectedAccount,
            'selectedAdminAccount' => $selectedAdminAccountModel,
            'selectedAdminAccount2' => $selectedAdminAccount2Model,
            'detailRows' => $detailRows,
            'headerData' => $header,
            'bankAdminFee' => old('fbiayaadminbank', $bankAdminFee),
            'hargaAdminValue' => old('fhargaadmin', $hargaAdmin),
            'hargaAdmin2Value' => old('fhargaadmin2', $hargaAdmin2),
            'dueDate' => old('ftgljatuhtempo', optional($header?->ftgljatuhtempo)->format('Y-m-d')),
            'giroMundur' => old('fgiromundur', ($header?->fgiromundur ?? '0')) === '1',
            'giroMundurHeaderAccount' => ($giroCode = $this->resolveSetAccountCode(self::GIRO_MUNDUR_ACCOUNT_NAME))
                ? Account::query()->where('faccount', $giroCode)->first(['faccid', 'faccount', 'faccname'])
                : null,
            'noteValue' => old('fket', $header?->fket),
            'giroNo' => old('fnogiro', $header?->fnogiro),
            'headerAccounts' => $this->resolveHeaderAccounts(),
        ], $overrides);
    }

    private function resolveSetAccountCodes(array $accountNames): array
    {
        return DB::table('set_account')
            ->whereIn('faccount_name', $accountNames)
            ->pluck('faccount')
            ->filter()
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->values()
            ->all();
    }

    private function resolveHeaderAccounts(): Collection
    {
        $kasBankHeaderCode = $this->resolveSetAccountCode('KASBANKHEADER');
        $uangMukaCodes = $this->resolveSetAccountCodes(['UANGMUKAPEMBELIAN', 'UANGMUKAPENJUALAN']);

        return Account::query()
            ->where('fend', 1)
            ->where('fnonactive', '0')
            ->where(function ($query) use ($kasBankHeaderCode, $uangMukaCodes) {
                if (! empty($kasBankHeaderCode)) {
                    $query->orWhere('faccupline', $kasBankHeaderCode);
                }

                if (! empty($uangMukaCodes)) {
                    $query->orWhereIn('faccount', $uangMukaCodes);
                }
            })
             ->orderBy('faccount')
            ->get([
                'faccid',
                'faccount',
                'faccname',
                'faccupline',
            ]);
    }

    private function resolveHeaderDk(float $amount): string
    {
        return $amount >= 0 ? 'D' : 'K';
    }
}
