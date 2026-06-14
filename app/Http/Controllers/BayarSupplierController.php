<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Supplier;
use App\Models\Trkasdt;
use App\Models\Trkasmt;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BayarSupplierController extends Controller
{
    private const TRAN_CODE = 'PAY';
    private const GIRO_MUNDUR_ACCOUNT_NAME = 'HUTANGGIRO';

    public function index()
    {
        $records = Trkasmt::query()
            ->where('trkasmt.ftrancode', self::TRAN_CODE)
            ->whereNotNull('trkasmt.fsupplier')
            ->leftJoin('trkasdt as dt', 'dt.fkasmtid', '=', 'trkasmt.fkasmtid')
            ->leftJoin('account as acc', 'acc.faccount', '=', 'trkasmt.faccountheader')
            ->leftJoin('mssupplier as supp', 'supp.fsuppliercode', '=', 'trkasmt.fsupplier')
            ->select([
                'trkasmt.fkasmtid',
                'trkasmt.fkasmtno',
                'trkasmt.fkasmtdate',
                'trkasmt.fnogiro',
                'trkasmt.fuserid',
                DB::raw("COALESCE(NULLIF(concat_ws(' - ', trkasmt.faccountheader, acc.faccname), ''), '-') as account_summary"),
                DB::raw("COALESCE(string_agg(DISTINCT NULLIF(TRIM(COALESCE(CASE WHEN TRIM(COALESCE(dt.freftype, '')) != 'ADM' THEN dt.frefno ELSE NULL END, '')), ''), ', ' ORDER BY NULLIF(TRIM(COALESCE(CASE WHEN TRIM(COALESCE(dt.freftype, '')) != 'ADM' THEN dt.frefno ELSE NULL END, '')), '')), '-') as pbl_summary"),
                DB::raw("COALESCE(NULLIF(TRIM(supp.fsuppliername), ''), '-') as supplier_name"),
                DB::raw("ABS(COALESCE(SUM(COALESCE(CASE WHEN TRIM(COALESCE(dt.freftype, '')) != 'ADM' THEN dt.fkasdtvalue ELSE 0 END, 0)), COALESCE(trkasmt.famountpay, 0), 0)) as payment_amount"),
            ])
            ->groupBy(
                'trkasmt.fkasmtid',
                'trkasmt.fkasmtno',
                'trkasmt.fkasmtdate',
                'trkasmt.fnogiro',
                'trkasmt.fuserid',
                'trkasmt.faccountheader',
                'acc.faccname',
                'supp.fsuppliername'
            )
            ->orderByDesc('trkasmt.fkasmtdate')
            ->orderByDesc('trkasmt.fkasmtid')
            ->get();

        return view('bayarsupplier.index', [
            'records' => $records,
        ]);
    }

    public function create()
    {
        return view('bayarsupplier.create', $this->formViewData(null, [
            'pageTitle' => 'Bayar Supplier',
            'formAction' => route('bayarsupplier.store'),
            'formMethod' => 'POST',
            'isReadOnly' => false,
            'isDeleteMode' => false,
            'submitLabel' => 'Simpan',
            'draftKey' => 'bayarsupplier:create',
        ]));
    }

    public function view($fkasmtno)
    {
        $header = $this->findHeader($fkasmtno);

        return view('bayarsupplier.view', $this->formViewData($header, [
            'pageTitle' => 'View Bayar Supplier',
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

        if ($message = $this->getClearedGiroLockMessage($header, 'Bayar supplier ini')) {
            return redirect()->route('bayarsupplier.view', $header->fkasmtno)->with('error', $message);
        }

        return view('bayarsupplier.edit', $this->formViewData($header, [
            'pageTitle' => 'Edit Bayar Supplier',
            'formAction' => route('bayarsupplier.update', $header->fkasmtno),
            'formMethod' => 'PATCH',
            'isReadOnly' => false,
            'isDeleteMode' => false,
            'submitLabel' => 'Simpan',
            'draftKey' => 'bayarsupplier:edit:' . $header->fkasmtno,
        ]));
    }

    public function delete($fkasmtno)
    {
        $header = $this->findHeader($fkasmtno);

        if ($message = $this->getClearedGiroLockMessage($header, 'Bayar supplier ini')) {
            return redirect()->route('bayarsupplier.view', $header->fkasmtno)->with('error', $message);
        }

        return view('bayarsupplier.delete', $this->formViewData($header, [
            'pageTitle' => 'Hapus Bayar Supplier',
            'formAction' => route('bayarsupplier.destroy', $header->fkasmtno),
            'formMethod' => 'DELETE',
            'isReadOnly' => true,
            'isDeleteMode' => true,
            'submitLabel' => 'Hapus',
            'draftKey' => null,
        ]));
    }

    public function pickablePbl(Request $request)
    {
        $supplierCode = trim((string) $request->input('supplier_code', $request->input('fsupplier', '')));

        $baseQuery = DB::table('trstockmt as mt')
            ->leftJoin('mssupplier as s', 's.fsuppliercode', '=', 'mt.fsupplier')
            ->where('mt.fstockmtcode', 'BUY')
            ->whereRaw('COALESCE(mt.famountremain, 0) > 0')
            ->when($supplierCode !== '', function ($query) use ($supplierCode) {
                $query->whereRaw('TRIM(COALESCE(mt.fsupplier, \'\')) = ?', [$supplierCode]);
            });

        $query = (clone $baseQuery)->select([
            'mt.fstockmtid',
            'mt.fstockmtcode',
            'mt.fstockmtno',
            'mt.fstockmtdate',
            'mt.fsupplier',
            'mt.famountmt',
            'mt.famountremain',
            'mt.ftgljatuhtempo',
            's.fsuppliername',
            's.ftempo',
        ]);

        $recordsTotal = (clone $baseQuery)->count('mt.fstockmtid');

        if ($request->filled('search') && trim((string) $request->input('search')) !== '') {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('mt.fstockmtno', 'ilike', "%{$search}%")
                    ->orWhere('mt.fsupplier', 'ilike', "%{$search}%")
                    ->orWhere('s.fsuppliername', 'ilike', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $query)->count();
        $allowedColumns = ['fstockmtno', 'fstockmtdate', 'fsupplier', 'fsuppliername', 'famountremain'];
        $orderColumn = (string) $request->input('order_column', 'fstockmtdate');
        $orderDir = strtolower((string) $request->input('order_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (in_array($orderColumn, $allowedColumns, true)) {
            $query->orderBy($orderColumn === 'fsuppliername' ? 's.fsuppliername' : 'mt.' . $orderColumn, $orderDir);
        } else {
            $query->orderBy('mt.fstockmtdate', 'desc');
        }

        $data = $query
            ->skip((int) $request->input('start', 0))
            ->take((int) $request->input('length', 10))
            ->get()
            ->map(function ($row) {
                return [
                    'fstockmtid' => (int) ($row->fstockmtid ?? 0),
                    'fstockmtcode' => trim((string) ($row->fstockmtcode ?? 'BUY')),
                    'ftrcode' => trim((string) ($row->fstockmtcode ?? 'BUY')),
                    'fstockmtno' => trim((string) ($row->fstockmtno ?? '')),
                    'fstockmtdate' => !empty($row->fstockmtdate) ? Carbon::parse($row->fstockmtdate)->format('Y-m-d') : null,
                    'fsupplier' => trim((string) ($row->fsupplier ?? '')),
                    'fsuppliername' => trim((string) ($row->fsuppliername ?? '')),
                    'ftempo' => (int) ($row->ftempo ?? 0),
                    'famountmt' => (float) ($row->famountmt ?? 0),
                    'famountremain' => (float) ($row->famountremain ?? 0),
                    'ftgljatuhtempo' => !empty($row->ftgljatuhtempo) ? Carbon::parse($row->ftgljatuhtempo)->format('Y-m-d') : null,
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
            'fkasmtno' => ['nullable', 'string', 'max:30', Rule::unique('trkasmt', 'fkasmtno')],
            'fkasmtdate' => ['required', 'date'],
            'fbranchcode' => ['required', 'string', 'max:10'],
            'fsupplier' => ['required', 'string', 'max:30', Rule::exists('mssupplier', 'fsuppliercode')],
            'faccountheader' => ['required'],
            'fnogiro' => ['nullable', 'string', 'max:35', Rule::unique('trkasmt', 'fnogiro')->ignore($request->fkasmtid, 'fkasmtid')],
            'fgiromundur' => ['nullable', 'in:0,1'],
            'ftgljatuhtempo' => ['nullable', 'date', Rule::requiredIf($isGiroMundur), 'before_or_equal:fkasmtdate'],
            'fket' => ['nullable', 'string', 'max:50'],
            'fbiayaadminbank' => ['nullable', 'numeric', 'min:0'],
            'faccountadmin' => [Rule::requiredIf((float) $request->input('fbiayaadminbank') > 0), 'nullable', 'string', 'max:15', Rule::exists('account', 'faccount')->where(fn($query) => $query->where('fend', 1))],
            'details' => ['required', 'array', 'min:1'],
            'details.*.frefno' => ['required', 'string', 'max:30'],
            'details.*.fnilai_order' => ['nullable', 'numeric'],
            'details.*.fsisa_hutang' => ['nullable', 'numeric'],
            'details.*.fdiscpersen' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'details.*.fdiscount' => ['nullable', 'numeric', 'min:0'],
            'details.*.fkasdtvalue' => ['required', 'numeric', 'min:0.01'],
        ], [
            'fsupplier.required' => 'Supplier wajib dipilih.',
            'faccountheader.required' => 'Account wajib dipilih.',
            'ftgljatuhtempo.required' => 'Tgl. jatuh tempo wajib diisi saat giro mundur aktif.',
            'ftgljatuhtempo.before_or_equal' => 'Tgl. jatuh tempo tidak boleh melebihi tanggal transaksi.',
            'faccountadmin.required' => 'Account biaya admin bank wajib diisi.',
            'details.required' => 'Minimal 1 faktur wajib diisi.',
            'details.*.frefno.required' => 'No. penerimaan wajib diisi.',
            'details.*.fkasdtvalue.required' => 'Total bayar wajib diisi.',
            'fnogiro.unique' => 'No. giro / cek sudah dipakai.',
        ]);

        if ($isGiroMundur && $giroAccount !== '') {
            $validated['faccountheader'] = $giroAccount;
        }

        $supplier = Supplier::query()
            ->where('fsuppliercode', $validated['fsupplier'])
            ->firstOrFail(['fsupplierid', 'fsuppliercode', 'fsuppliername']);
        $headerAccount = Account::query()
            ->where('faccount', $validated['faccountheader'])
            ->firstOrFail(['faccid', 'faccount', 'faccname']);
        $detailRows = $this->normalizeDetails($validated['details']);

        $this->ensureCreateDateWithinEditPeriod($validated['fkasmtdate']);
        $this->validateUniqueReferenceRows($detailRows);
        $this->validateReferencesNotAlreadyUsed($detailRows);

        $this->validateReferenceSuppliers($detailRows, $supplier->fsuppliercode);
        $this->resolveReferenceTransactions($detailRows, Carbon::parse($validated['fkasmtdate']));
        $this->validatePaymentDoesNotExceedRemainingPayable($detailRows);

        $payableCode = trim((string) DB::table('set_account')->where('faccount_name', 'HUTANGDAGANG')->value('faccount'));
        $payableAccount = $payableCode !== ''
            ? Account::query()->where('faccount', $payableCode)->where('fend', 1)->first(['faccid', 'faccount', 'faccname'])
            : null;
        $bankAdminFee = round((float) ($validated['fbiayaadminbank'] ?? 0), 2);
        $adminAccount = null;

        if ($bankAdminFee > 0 && !empty($validated['faccountadmin'])) {
            $adminAccount = Account::query()
                ->where('faccount', $validated['faccountadmin'])
                ->firstOrFail(['faccid', 'faccount', 'faccname']);
        }

        $voucherNo = trim((string) ($validated['fkasmtno'] ?? '')) ?: $this->generateVoucherNo(Carbon::parse($validated['fkasmtdate']), $validated['fbranchcode']);
        $totalBayar = round((float) $detailRows->sum(fn(array $row) => (float) ($row['fkasdtvalue'] ?? 0)), 2);
        $totalKasKeluar = round($totalBayar + $bankAdminFee, 2);
        $now = now();

        DB::transaction(function () use ($validated, $supplier, $headerAccount, $detailRows, $payableAccount, $adminAccount, $bankAdminFee, $voucherNo, $totalKasKeluar, $now) {
            $headerId = $this->nextIntegerId('trkasmt', 'fkasmtid');
            $nextDetailId = $this->nextIntegerId('trkasdt', 'fkasdtid');

            Trkasmt::create([
                'fkasmtid' => $headerId,
                'fkasmtno' => $voucherNo,
                'ftrancode' => self::TRAN_CODE,
                'fkasmtdate' => $validated['fkasmtdate'],
                'frate' => 1,
                'fwhom' => $supplier->fsuppliername,
                'faccountheader' => $headerAccount->faccount,
                'faccountheaderid' => $headerAccount->faccid,
                'fdkheader' => 'K',
                'fsupplier' => $supplier->fsupplierid,
                'fket' => $validated['fket'] ?? null,
                'famountpay' => $totalKasKeluar,
                'famountpay_rp' => $totalKasKeluar,
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

            foreach ($detailRows->values() as $index => $row) {
                $paymentAmount = round((float) $row['fkasdtvalue'], 2);
                $discountAmount = round((float) $row['fdiscount'], 2);
                $journalAmount = round($paymentAmount + $discountAmount, 2);

                Trkasdt::create([
                    'fkasdtid' => $nextDetailId + $index,
                    'fkasmtid' => $headerId,
                    'ftrancode' => self::TRAN_CODE,
                    'faccount' => $payableAccount?->faccount,
                    'faccountid' => $payableAccount?->faccid,
                    'fdk' => 'D',
                    'frefno' => $row['frefno'],
                    'fnote' => $supplier->fsuppliername,
                    'fsubaccount' => $supplier->fsuppliercode,
                    'fdiscpersen' => $row['fdiscpersen'],
                    'fdiscount' => $discountAmount,
                    'fdiscountrp' => $discountAmount,
                    'fkasdtvalue' => $paymentAmount,
                    'fvalue_rp' => $journalAmount,
                    'fjurnal' => $journalAmount,
                    'fjurnal_rp' => $journalAmount,
                    'fuserid' => $this->currentUserId(),
                    'fdatetime' => $now,
                    'fnou' => $index + 1,
                    'freftype' => 'PBL',
                ]);
            }

            if ($bankAdminFee > 0 && $adminAccount) {
                Trkasdt::create([
                    'fkasdtid' => $nextDetailId + $detailRows->count(),
                    'fkasmtid' => $headerId,
                    'ftrancode' => self::TRAN_CODE,
                    'faccount' => $adminAccount->faccount,
                    'faccountid' => $adminAccount->faccid,
                    'fdk' => 'D',
                    'frefno' => 'ADM',
                    'fnote' => 'BIAYA ADMIN BANK',
                    'fkasdtvalue' => $bankAdminFee,
                    'fvalue_rp' => $bankAdminFee,
                    'fjurnal' => $bankAdminFee,
                    'fjurnal_rp' => $bankAdminFee,
                    'fuserid' => $this->currentUserId(),
                    'fdatetime' => $now,
                    'fnou' => $detailRows->count() + 1,
                    'freftype' => 'ADM',
                ]);
            }
        });

        return redirect()
            ->route('bayarsupplier.create')
            ->with('success', 'Bayar supplier ' . $voucherNo . ' berhasil disimpan.');
    }

    public function update(Request $request, $fkasmtno)
    {
        $header = $this->findHeader($fkasmtno);

        if ($message = $this->getClearedGiroLockMessage($header, 'Bayar supplier ini')) {
            return redirect()->route('bayarsupplier.view', $header->fkasmtno)->with('error', $message);
        }
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
            'fsupplier' => ['required', 'string', 'max:30', Rule::exists('mssupplier', 'fsuppliercode')],
            'faccountheader' => ['required'],
            'fnogiro' => ['nullable', 'string', 'max:35', Rule::unique('trkasmt', 'fnogiro')->ignore($request->fkasmtid, 'fkasmtid')],
            'fgiromundur' => ['nullable', 'in:0,1'],
            'ftgljatuhtempo' => ['nullable', 'date', Rule::requiredIf($isGiroMundur), 'before_or_equal:fkasmtdate'],
            'fket' => ['nullable', 'string', 'max:50'],
            'fbiayaadminbank' => ['nullable', 'numeric', 'min:0'],
            'faccountadmin' => [Rule::requiredIf((float) $request->input('fbiayaadminbank') > 0), 'nullable', 'string', 'max:15', Rule::exists('account', 'faccount')->where(fn($query) => $query->where('fend', 1))],
            'details' => ['required', 'array', 'min:1'],
            'details.*.frefno' => ['required', 'string', 'max:30'],
            'details.*.fnilai_order' => ['nullable', 'numeric'],
            'details.*.fsisa_hutang' => ['nullable', 'numeric'],
            'details.*.fdiscpersen' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'details.*.fdiscount' => ['nullable', 'numeric', 'min:0'],
            'details.*.fkasdtvalue' => ['required', 'numeric', 'min:0.01'],
        ], [
            'fsupplier.required' => 'Supplier wajib dipilih.',
            'faccountheader.required' => 'Account wajib dipilih.',
            'ftgljatuhtempo.required' => 'Tgl. jatuh tempo wajib diisi saat giro mundur aktif.',
            'ftgljatuhtempo.before_or_equal' => 'Tgl. jatuh tempo tidak boleh melebihi tanggal transaksi.',
            'faccountadmin.required' => 'Account biaya admin bank wajib diisi.',
            'details.required' => 'Minimal 1 faktur wajib diisi.',
            'details.*.frefno.required' => 'No. penerimaan wajib diisi.',
            'details.*.fkasdtvalue.required' => 'Total bayar wajib diisi.',
            'fnogiro.unique' => 'No. giro / cek sudah dipakai.',
        ]);

        if ($isGiroMundur && $giroAccount !== '') {
            $validated['faccountheader'] = $giroAccount;
        }

        $supplier = Supplier::query()
            ->where('fsuppliercode', $validated['fsupplier'])
            ->firstOrFail(['fsupplierid', 'fsuppliercode', 'fsuppliername']);
        $headerAccount = Account::query()
            ->where('faccount', $validated['faccountheader'])
            ->firstOrFail(['faccid', 'faccount', 'faccname']);
        $detailRows = $this->normalizeDetails($validated['details']);

        $this->ensureCreateDateWithinEditPeriod($validated['fkasmtdate'], $header->fkasmtdate);
        $this->validateUniqueReferenceRows($detailRows);
        $this->validateReferencesNotAlreadyUsed($detailRows, $header);

        $this->validateReferenceSuppliers($detailRows, $supplier->fsuppliercode);
        $this->resolveReferenceTransactions($detailRows, Carbon::parse($validated['fkasmtdate']));
        $this->validatePaymentDoesNotExceedRemainingPayable($detailRows, $header);

        $payableCode = trim((string) DB::table('set_account')->where('faccount_name', 'HUTANGDAGANG')->value('faccount'));
        $payableAccount = $payableCode !== ''
            ? Account::query()->where('faccount', $payableCode)->where('fend', 1)->first(['faccid', 'faccount', 'faccname'])
            : null;
        $bankAdminFee = round((float) ($validated['fbiayaadminbank'] ?? 0), 2);
        $adminAccount = null;

        if ($bankAdminFee > 0 && !empty($validated['faccountadmin'])) {
            $adminAccount = Account::query()
                ->where('faccount', $validated['faccountadmin'])
                ->firstOrFail(['faccid', 'faccount', 'faccname']);
        }

        $voucherNo = trim((string) ($validated['fkasmtno'] ?? '')) ?: $header->fkasmtno;
        $totalBayar = round((float) $detailRows->sum(fn(array $row) => (float) ($row['fkasdtvalue'] ?? 0)), 2);
        $totalKasKeluar = round($totalBayar + $bankAdminFee, 2);
        $now = now();

        DB::transaction(function () use ($validated, $supplier, $headerAccount, $detailRows, $payableAccount, $adminAccount, $bankAdminFee, $voucherNo, $totalKasKeluar, $now, $header) {
            $header->update([
                'fkasmtno' => $voucherNo,
                'fkasmtdate' => $validated['fkasmtdate'],
                'fwhom' => $supplier->fsuppliername,
                'faccountheader' => $headerAccount->faccount,
                'faccountheaderid' => $headerAccount->faccid,
                'fsupplier' => $supplier->fsupplierid,
                'fket' => $validated['fket'] ?? null,
                'famountpay' => $totalKasKeluar,
                'famountpay_rp' => $totalKasKeluar,
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
            foreach ($detailRows->values() as $index => $row) {
                $paymentAmount = round((float) $row['fkasdtvalue'], 2);
                $discountAmount = round((float) $row['fdiscount'], 2);
                $journalAmount = round($paymentAmount + $discountAmount, 2);

                Trkasdt::create([
                    'fkasdtid' => $nextDetailId + $index,
                    'fkasmtid' => $header->fkasmtid,
                    'ftrancode' => self::TRAN_CODE,
                    'faccount' => $payableAccount?->faccount,
                    'faccountid' => $payableAccount?->faccid,
                    'fdk' => 'D',
                    'frefno' => $row['frefno'],
                    'fnote' => $supplier->fsuppliername,
                    'fsubaccount' => $supplier->fsuppliercode,
                    'fdiscpersen' => $row['fdiscpersen'],
                    'fdiscount' => $discountAmount,
                    'fdiscountrp' => $discountAmount,
                    'fkasdtvalue' => $paymentAmount,
                    'fvalue_rp' => $journalAmount,
                    'fjurnal' => $journalAmount,
                    'fjurnal_rp' => $journalAmount,
                    'fuserid' => $this->currentUserId(),
                    'fdatetime' => $now,
                    'fnou' => $index + 1,
                    'freftype' => 'PBL',
                ]);
            }

            if ($bankAdminFee > 0 && $adminAccount) {
                Trkasdt::create([
                    'fkasdtid' => $nextDetailId + $detailRows->count(),
                    'fkasmtid' => $header->fkasmtid,
                    'ftrancode' => self::TRAN_CODE,
                    'faccount' => $adminAccount->faccount,
                    'faccountid' => $adminAccount->faccid,
                    'fdk' => 'D',
                    'frefno' => 'ADM',
                    'fnote' => 'BIAYA ADMIN BANK',
                    'fkasdtvalue' => $bankAdminFee,
                    'fvalue_rp' => $bankAdminFee,
                    'fjurnal' => $bankAdminFee,
                    'fjurnal_rp' => $bankAdminFee,
                    'fuserid' => $this->currentUserId(),
                    'fdatetime' => $now,
                    'fnou' => $detailRows->count() + 1,
                    'freftype' => 'ADM',
                ]);
            }
        });

        return redirect()
            ->route('bayarsupplier.edit', $voucherNo)
            ->with('success', 'Bayar supplier ' . $voucherNo . ' berhasil diperbarui.');
    }

    public function destroy($fkasmtno)
    {
        $header = $this->findHeader($fkasmtno);

        if ($message = $this->getClearedGiroLockMessage($header, 'Bayar supplier ini')) {
            return redirect()->route('bayarsupplier.view', $header->fkasmtno)->with('error', $message);
        }

        DB::transaction(function () use ($header) {
            Trkasdt::where('fkasmtid', $header->fkasmtid)->delete();
            $header->delete();
        });

        return redirect()
            ->route('bayarsupplier.index')
            ->with('success', 'Bayar supplier ' . $fkasmtno . ' berhasil dihapus.');
    }

    private function filterEmptyDetailRows(array $details): array
    {
        return collect($details)
            ->filter(fn($detail) => is_array($detail) && (trim((string) ($detail['frefno'] ?? '')) !== '' || (float) ($detail['fkasdtvalue'] ?? 0) !== 0.0))
            ->values()
            ->all();
    }

    private function normalizeDetails(array $details): Collection
    {
        return collect($details)
            ->map(fn(array $detail) => [
                'frefno' => trim((string) ($detail['frefno'] ?? '')),
                'fsupplier' => trim((string) ($detail['fsupplier'] ?? '')),
                'fsuppliername' => trim((string) ($detail['fsuppliername'] ?? '')),
                'ftempo' => (int) ($detail['ftempo'] ?? 0),
                'fnilai_order' => round(abs((float) ($detail['fnilai_order'] ?? 0)), 2),
                'fsisa_hutang' => round(abs((float) ($detail['fsisa_hutang'] ?? 0)), 2),
                'fdiscpersen' => round((float) ($detail['fdiscpersen'] ?? 0), 2),
                'fdiscount' => round(abs((float) ($detail['fdiscount'] ?? 0)), 2),
                'fkasdtvalue' => round(abs((float) ($detail['fkasdtvalue'] ?? 0)), 2),
            ])
            ->filter(fn(array $detail) => $detail['frefno'] !== '' && (float) $detail['fkasdtvalue'] > 0)
            ->values();
    }



    private function resolveBranchCode(): string
    {
        $branch = Auth::guard('sysuser')->user()?->fcabang ?? Auth::user()?->fcabang ?? session('fcabang');

        if ($branch !== null) {
            $needle = trim((string) $branch);

            if ($needle !== '') {
                $kodeCabang = is_numeric($needle)
                    ? DB::table('mscabang')->where('fcabangid', (int) $needle)->value('fcabangkode')
                    : DB::table('mscabang')->whereRaw('LOWER(fcabangkode) = LOWER(?)', [$needle])->value('fcabangkode');

                if (!$kodeCabang && !is_numeric($needle)) {
                    $kodeCabang = DB::table('mscabang')->whereRaw('LOWER(fcabangname) = LOWER(?)', [$needle])->value('fcabangkode');
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

        $branchName = trim((string) DB::table('mscabang')->whereRaw('LOWER(fcabangkode) = LOWER(?)', [$branchCode])->value('fcabangname'));

        return $branchName !== '' ? $branchCode . ' - ' . $branchName : $branchCode;
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

    private function generateVoucherNo(Carbon $date, ?string $branchCode = null): string
    {
        $branchCode = trim((string) ($branchCode ?: $this->resolveBranchCode())) ?: 'NA';
        $prefix = sprintf('%s.%s.%s.%s.', self::TRAN_CODE, $branchCode, $date->format('Y'), $date->format('m'));
        $lastNumber = DB::table('trkasmt')
            ->where('fkasmtno', 'like', $prefix . '%')
            ->selectRaw("MAX(CAST(split_part(fkasmtno, '.', 5) AS integer)) as last_no")
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

    private function validatePaymentDoesNotExceedRemainingPayable(Collection $detailRows, ?Trkasmt $exceptHeader = null): void
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

        $remainingByRef = DB::table('trstockmt')
            ->whereIn('fstockmtno', $refNos)
            ->where('fstockmtcode', 'BUY')
            ->pluck('famountremain', 'fstockmtno')
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
                    "details.{$index}.fkasdtvalue" => 'Total bayar tidak boleh melebihi sisa hutang.',
                ]);
            }
        }
    }

    private function validateUniqueReferenceRows(Collection $detailRows): void
    {
        $seen = [];

        foreach ($detailRows as $index => $row) {
            $refNo = trim((string) ($row['frefno'] ?? ''));

            if ($refNo === '') {
                continue;
            }

            $key = strtoupper($refNo);
            if (isset($seen[$key])) {
                throw ValidationException::withMessages([
                    "details.{$index}.frefno" => "No. penerimaan {$refNo} tidak boleh sama.",
                ]);
            }

            $seen[$key] = true;
        }
    }

    private function validateReferencesNotAlreadyUsed(Collection $detailRows, ?Trkasmt $exceptHeader = null): void
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

        $usedRefs = Trkasdt::query()
            ->where('ftrancode', self::TRAN_CODE)
            ->whereIn('frefno', $refNos)
            ->whereRaw("TRIM(COALESCE(freftype, '')) != 'ADM'")
            ->when($exceptHeader, fn($query) => $query->where('fkasmtid', '!=', $exceptHeader->fkasmtid))
            ->pluck('frefno')
            ->map(fn($value) => strtoupper(trim((string) $value)))
            ->flip();

        if ($usedRefs->isEmpty()) {
            return;
        }

        foreach ($detailRows as $index => $row) {
            $refNo = trim((string) ($row['frefno'] ?? ''));

            if ($refNo !== '' && $usedRefs->has(strtoupper($refNo))) {
                throw ValidationException::withMessages([
                    "details.{$index}.frefno" => "No. penerimaan {$refNo} sudah pernah dibuat Bayar Supplier.",
                ]);
            }
        }
    }

    private function validateReferenceSuppliers(Collection $detailRows, string $supplierCode): void
    {
        $supplierCode = trim($supplierCode);
        $refNos = $detailRows
            ->pluck('frefno')
            ->map(fn($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        if ($supplierCode === '' || $refNos->isEmpty()) {
            return;
        }

        $supplierByRef = DB::table('trstockmt')
            ->whereIn('fstockmtno', $refNos)
            ->where('fstockmtcode', 'BUY')
            ->pluck('fsupplier', 'fstockmtno')
            ->mapWithKeys(fn($supplier, $refNo) => [trim((string) $refNo) => trim((string) $supplier)]);

        foreach ($detailRows as $index => $row) {
            $refNo = trim((string) ($row['frefno'] ?? ''));
            $refSupplier = $supplierByRef->get($refNo, '');

            if ($refSupplier !== '' && $refSupplier !== $supplierCode) {
                throw ValidationException::withMessages([
                    "details.{$index}.frefno" => 'Nota harus sesuai supplier yang dipilih.',
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

        $references = DB::table('trstockmt')
            ->whereIn('fstockmtno', $refNos)
            ->where('fstockmtcode', 'BUY')
            ->get(['fstockmtno', 'fstockmtdate'])
            ->keyBy(fn($row) => trim((string) $row->fstockmtno));

        foreach ($detailRows as $index => $row) {
            $refNo = trim((string) ($row['frefno'] ?? ''));
            $reference = $references->get($refNo);

            if (!$reference) {
                throw ValidationException::withMessages([
                    "details.{$index}.frefno" => "No. penerimaan {$refNo} tidak ditemukan.",
                ]);
            }

            $referenceDate = !empty($reference->fstockmtdate)
                ? Carbon::parse($reference->fstockmtdate)->startOfDay()
                : null;

            if ($referenceDate && $referenceDate->gt($paymentDate->copy()->startOfDay())) {
                throw ValidationException::withMessages([
                    "details.{$index}.frefno" => "Tanggal penerimaan {$refNo} tidak boleh lebih besar dari tanggal transaksi kas/bank.",
                ]);
            }
        }

        return $references->all();
    }

    private function formViewData(?Trkasmt $header = null, array $overrides = []): array
    {
        $supplierCode = trim((string) old('fsupplier', ''));
        $accountCode = trim((string) old('faccountheader', ''));

        $selectedSupplier = null;
        if ($supplierCode !== '') {
            $selectedSupplier = Supplier::query()
                ->where('fsuppliercode', $supplierCode)
                ->first(['fsupplierid', 'fsuppliercode', 'fsuppliername', 'ftempo']);
        } elseif ($header && !empty($header->fsupplier)) {
            $selectedSupplier = Supplier::query()
                ->where('fsupplierid', $header->fsupplier)
                ->first(['fsupplierid', 'fsuppliercode', 'fsuppliername', 'ftempo']);
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
        $hargaAdminValue = 0;
        $hargaAdmin2Value = 0;
        $selectedAdminAccount = null;
        $selectedAdminAccount2 = null;

        if ($adminFeeDetail) {
            $bankAdminFee = (float) $adminFeeDetail->fbiayaadminbank;

            // Admin account 1 (ADM detail line 1)
            $adminDetail1 = $header
                ? $header->details->filter(fn($d) => trim((string)($d->freftype ?? '')) === 'ADM')->values()->get(0)
                : null;
            // Admin account 2 (ADM detail line 2)
            $adminDetail2 = $header
                ? $header->details->filter(fn($d) => trim((string)($d->freftype ?? '')) === 'ADM')->values()->get(1)
                : null;

            if ($adminDetail1) {
                $hargaAdminValue = (float) $adminDetail1->fkasdtvalue;
                $selectedAdminAccount = Account::query()
                    ->where('faccount', $adminDetail1->faccount)
                    ->first(['faccid', 'faccount', 'faccname']);
            }

            if ($adminDetail2) {
                $hargaAdmin2Value = (float) $adminDetail2->fkasdtvalue;
                $selectedAdminAccount2 = Account::query()
                    ->where('faccount', $adminDetail2->faccount)
                    ->first(['faccid', 'faccount', 'faccname']);
            }
        }

        $adminAccountCode = old('faccountadmin', $selectedAdminAccount->faccount ?? '');
        $selectedAdminAccountModel = null;
        if ($adminAccountCode !== '') {
            $selectedAdminAccountModel = Account::query()
                ->where('faccount', $adminAccountCode)
                ->first(['faccid', 'faccount', 'faccname']);
        }

        $adminAccount2Code = old('faccountadmin2', $selectedAdminAccount2->faccount ?? '');
        $selectedAdminAccount2Model = null;
        if ($adminAccount2Code !== '') {
            $selectedAdminAccount2Model = Account::query()
                ->where('faccount', $adminAccount2Code)
                ->first(['faccid', 'faccount', 'faccname']);
        }

        $referenceRemainMap = collect();
        if ($header) {
            $refNos = $header->details
                ->filter(fn($detail) => trim((string) ($detail->freftype ?? 'PBL')) !== 'ADM')
                ->pluck('frefno')
                ->map(fn($value) => trim((string) $value))
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (!empty($refNos)) {
                $referenceRemainMap = DB::table('trstockmt')
                    ->whereIn('fstockmtno', $refNos)
                    ->where('fstockmtcode', 'BUY')
                    ->leftJoin('mssupplier as s', 's.fsuppliercode', '=', 'trstockmt.fsupplier')
                    ->select([
                        'trstockmt.fstockmtno',
                        'trstockmt.fstockmtcode',
                        'trstockmt.famountmt',
                        'trstockmt.famountremain',
                        'trstockmt.fsupplier',
                        's.fsuppliername',
                        's.ftempo',
                    ])
                    ->get()
                    ->keyBy(fn($row) => trim((string) ($row->fstockmtno ?? '')));
            }
        }

        $detailRows = [];
        if ($header) {
            $detailRows = $header->details
                ->filter(fn($detail) => trim((string)($detail->freftype ?? 'PBL')) !== 'ADM')
                ->values()
                ->map(function ($detail, $index) use ($referenceRemainMap) {
                    $refNo = trim((string) ($detail->frefno ?? ''));
                    $reference = $referenceRemainMap->get($refNo);

                    $fnilaiOrder = (float) ($detail->fvalue_rp ?? $detail->fjurnal_rp ?? $detail->fkasdtvalue ?? 0);
                    $fsisaHutang = $fnilaiOrder;
                    $actualPayment = (float) ($detail->fkasdtvalue ?? 0);
                    $actualDiscount = (float) ($detail->fdiscount ?? 0);

                    if ($reference) {
                        $fnilaiOrder = (float) ($reference->famountmt ?? 0);
                        $fsisaHutang = (float) ($reference->famountremain ?? 0);
                    }

                    $adjustedRemain = $reference
                        ? max(abs((float) ($reference->famountremain ?? 0)) - $actualPayment - $actualDiscount, 0)
                        : $fnilaiOrder;

                    return [
                        'uid' => 'bs-existing-' . $index . '-' . $detail->fkasdtid,
                        'frefno' => $refNo,
                        'ftrcode' => trim((string) ($reference->fstockmtcode ?? $detail->freftype ?? 'BUY')),
                        'fsupplier' => trim((string) ($reference->fsupplier ?? '')),
                        'fsuppliername' => trim((string) ($reference->fsuppliername ?? '')),
                        'ftempo' => (int) ($reference->ftempo ?? 0),
                        'fnilai_order' => $fnilaiOrder,
                        'fsisa_hutang' => $adjustedRemain,
                        'fdiscpersen' => (float) ($detail->fdiscpersen ?? 0),
                        'fdiscount' => (float) ($detail->fdiscount ?? 0),
                        'fkasdtvalue' => $actualPayment,
                    ];
                })
                ->all();
        }

        $branchCode = old('fbranchcode', $header?->fbranchcode ?: $this->resolveBranchCode());

        return array_merge([
            'voucherNo' =>
            old('fkasmtno', $header?->fkasmtno),
            'transactionDate' =>
            old('fkasmtdate', optional($header?->fkasmtdate)->format('Y-m-d') ?? now()->format('Y-m-d')),
            'currentBranchCode' => $branchCode,
            'currentBranchLabel' => $this->resolveBranchLabel((string) $branchCode),
            'selectedSupplier' => $selectedSupplier,
            'selectedAccount' => $selectedAccount,
            'selectedAdminAccount' => $selectedAdminAccountModel,
            'selectedAdminAccount2' => $selectedAdminAccount2Model,
            'detailRows' => $detailRows,
            'headerData' => $header,
            'bankAdminFee' => old('fbiayaadminbank', $bankAdminFee),
            'hargaAdminValue' => old('fhargaadmin', $hargaAdminValue),
            'hargaAdmin2Value' => old('fhargaadmin2', $hargaAdmin2Value),
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

    private function resolveSetAccountCode(string $accountName): ?string
    {
        $accountCode = DB::table('set_account')
            ->where('faccount_name', $accountName)
            ->value('faccount');

        $accountCode = trim((string) $accountCode);

        return $accountCode !== '' ? $accountCode : null;
    }

    private function resolveSetAccountCodes(array $accountNames): array
    {
        return DB::table('set_account')
            ->whereIn('faccount_name', $accountNames)
            ->pluck('faccount')
            ->filter()
            ->map(fn($value) => trim((string) $value))
            ->filter(fn($value) => $value !== '')
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
}
