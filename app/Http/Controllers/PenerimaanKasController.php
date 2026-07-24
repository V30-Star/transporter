<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Subaccount;
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

class PenerimaanKasController extends Controller
{
    private const TRAN_CODE = 'BKM';
    private const HEADER_ACCOUNT_NAMES = ['KASBANKHEADER', 'UANGMUKAPEMBELIAN', 'UANGMUKAPENJUALAN'];
    private const GIRO_MUNDUR_ACCOUNT_NAME = 'PIUTANGGIRO';
    private const SYSTEM_JOURNAL_ACCOUNT_NAMES = ['LABATAHUNBERJALAN', 'LABADITAHAN', 'IKHTISARLABARUGI'];
    private const STOCK_JOURNAL_ACCOUNT_NAMES = ['PERSEDIAANAWAL', 'PERSEDIAAN', 'PERSEDIAANAHIR', 'HPP'];
    private const REFERENCE_REQUIRED_ACCOUNT_NAMES = ['PIUTANGDAGANG', 'HUTANGDAGANG'];

    public function index()
    {
        $records = Trkasmt::query()
            ->where('trkasmt.ftrancode', self::TRAN_CODE)
            ->leftJoin('trkasdt as dt', 'dt.fkasmtid', '=', 'trkasmt.fkasmtid')
            ->leftJoin('account as acc', 'acc.faccount', '=', 'dt.faccount')
            ->select([
                'trkasmt.fkasmtid',
                'trkasmt.fkasmtno',
                'trkasmt.fkasmtdate',
                'trkasmt.fnogiro',
                'trkasmt.fdkheader',
                'trkasmt.fbranchcode',
                'trkasmt.faccountno as account_summary',
                DB::raw("
                    COALESCE(
                        string_agg(
                            DISTINCT NULLIF(trim(dt.fnote), ''),
                            ', ' ORDER BY NULLIF(trim(dt.fnote), '')
                        ),
                        COALESCE(NULLIF(trim(trkasmt.fket), ''), '-')
                    ) as description_summary
                "),
                DB::raw('ABS(COALESCE(SUM(COALESCE(dt.fkasdtvalue, 0)), COALESCE(trkasmt.famountpay, 0), 0)) as payment_amount'),
            ])
            ->groupBy('trkasmt.fkasmtid', 'trkasmt.fkasmtno', 'trkasmt.fkasmtdate', 'trkasmt.fbranchcode', 'trkasmt.fnogiro', 'trkasmt.fket', 'trkasmt.fdkheader')
            ->orderByDesc('trkasmt.fkasmtdate')
            ->orderByDesc('trkasmt.fkasmtid')
            ->get();

        return view('penerimaankas.index', [
            'records' => $records,
            'canCreate' => $this->hasPermission('createPenerimaanKas'),
            'canEdit' => $this->hasPermission('updatePenerimaanKas'),
            'canDelete' => $this->hasPermission('deletePenerimaanKas'),
        ]);
    }

    public function create()
    {
        return view('penerimaankas.create', $this->formViewData(new Trkasmt([
            'fkasmtdate' => now()->toDateString(),
        ]), collect(array_map(fn() => new Trkasdt, range(1, 5))), [
            'pageTitle' => 'Penerimaan Kas/Bank',
            'formAction' => route('penerimaankas.store'),
            'formMethod' => 'POST',
            'isReadOnly' => false,
        ]));
    }

    public function store(Request $request)
    {
        $payload = $this->validatePayload($request);
        $this->ensureCreateDateWithinEditPeriod($payload['fkasmtdate']);
        $savedHeaderId = null;

        $header = DB::transaction(function () use ($payload, &$savedHeaderId) {
            $now = now();
            $details = $this->normalizeDetails($payload['details']);
            $totalAmount = $details->sum(fn(array $detail) => (float) $detail['fkasdtvalue']);
            $voucherNoInput = trim((string) ($payload['fkasmtno'] ?? ''));
            $isGiroMundur = ($payload['fgiromundur'] ?? '0') === '1';

            $headerAccount = $this->resolveHeaderAccount(
                $isGiroMundur ? $this->resolveSetAccountCode(self::GIRO_MUNDUR_ACCOUNT_NAME) : ($payload['faccountheader'] ?? null)
            );
            $voucherNo = $voucherNoInput !== ''
                ? $voucherNoInput
                : $this->generateVoucherNo(Carbon::parse($payload['fkasmtdate']), $payload['fbranchcode'], $headerAccount);
            $headerId = $this->nextIntegerId('trkasmt', 'fkasmtid');
            $savedHeaderId = $headerId;

            $header = Trkasmt::create([
                'fkasmtid' => $headerId,
                'fkasmtno' => $voucherNo,
                'ftrancode' => self::TRAN_CODE,
                'fbranchcode' => $payload['fbranchcode'],
                'fkasmtdate' => $payload['fkasmtdate'],
                'frate' => 1,
                'fwhom' => $payload['fwhom'] ?? null,
                'faccountheader' => $headerAccount?->faccount,
                'faccountheaderid' => $headerAccount?->faccid,
                'fdkheader' => $this->resolveHeaderDk($totalAmount),
                'fket' => $payload['fket'] ?? null,
                'famountpay' => $totalAmount,
                'famountpay_rp' => $totalAmount,
                'fuserid' => $this->currentUserId(),
                'fdatetime' => $now,
                'fgiromundur' => $isGiroMundur ? '1' : '0',
                'fnogiro' => $payload['fnogiro'] ?? null,
                'ftgljatuhtempo' => ! empty($payload['ftgljatuhtempo']) ? Carbon::parse($payload['ftgljatuhtempo'])->startOfDay() : null,
                'faccountno' => $headerAccount?->faccount,
                'faccountnoid' => $headerAccount?->faccid,
                'fstatusgiro' => '0',
            ]);

            $nextDetailId = $this->nextIntegerId('trkasdt', 'fkasdtid');

            foreach ($details->values() as $index => $detail) {
                Trkasdt::create([
                    'fkasdtid' => $nextDetailId + $index,
                    'fkasmtid' => $savedHeaderId,
                    'ftrancode' => self::TRAN_CODE,
                    'faccount' => $detail['faccount'],
                    'frefno' => $detail['frefno'] ?? null,
                    'fsubaccount' => $detail['fsubaccount'] ?? null,
                    'fdk' => $this->resolveDetailDk($detail['fkasdtvalue']),
                    'fnote' => $detail['fnote'] ?? null,
                    'fkasdtvalue' => $detail['fkasdtvalue'],
                    'fvalue_rp' => $detail['fkasdtvalue'],
                    'fjurnal' => $this->resolveDetailJournalAmount($detail['fkasdtvalue']),
                    'fjurnal_rp' => $this->resolveDetailJournalAmount($detail['fkasdtvalue']),
                    'fuserid' => $this->currentUserId(),
                    'fdatetime' => $now,
                    'fnou' => $index + 1,
                ]);
            }

            return $header;
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Penerimaan kas ' . $header->fkasmtno . ' berhasil disimpan.',
                'redirect_url' => route('penerimaankas.create'),
            ]);
        }

        return redirect()
            ->route('penerimaankas.create')
            ->with('success', 'Penerimaan kas ' . $header->fkasmtno . ' berhasil disimpan.');
    }

    public function view($fkasmtno)
    {
        $header = $this->findHeader($fkasmtno);

        return view('penerimaankas.view', $this->formViewData($header, $header->details, [
            'pageTitle' => 'View Penerimaan Kas/Bank',
            'isReadOnly' => true,
            'printRoute' => route('penerimaankas.print', $header->fkasmtno),
        ]));
    }

    public function edit($fkasmtno)
    {
        $header = $this->findHeader($fkasmtno);

        if ($message = $this->getPostedPeriodLockMessage($header->fkasmtdate)) {
            return redirect()->route('penerimaankas.view', $header->fkasmtno)->with('error', $message);
        }

        if ($message = $this->getClearedGiroLockMessage($header, 'Penerimaan kas ini')) {
            return redirect()->route('penerimaankas.view', $header->fkasmtno)->with('error', $message);
        }

        return view('penerimaankas.edit', $this->formViewData($header, $header->details, [
            'pageTitle' => 'Edit Penerimaan Kas/Bank',
            'formAction' => route('penerimaankas.update', $header->fkasmtno),
            'formMethod' => 'PATCH',
            'isReadOnly' => false,
        ]));
    }

    public function delete($fkasmtno)
    {
        $header = $this->findHeader($fkasmtno);

        if ($message = $this->getPostedPeriodLockMessage($header->fkasmtdate)) {
            return redirect()->route('penerimaankas.view', $header->fkasmtno)->with('error', $message);
        }

        if ($message = $this->getClearedGiroLockMessage($header, 'Penerimaan kas ini')) {
            return redirect()->route('penerimaankas.view', $header->fkasmtno)->with('error', $message);
        }

        return view('penerimaankas.delete', $this->formViewData($header, $header->details, [
            'pageTitle' => 'Hapus Penerimaan Kas/Bank',
            'formAction' => route('penerimaankas.destroy', $header->fkasmtno),
            'formMethod' => 'DELETE',
            'isReadOnly' => true,
        ]));
    }

    public function update(Request $request, $fkasmtno)
    {
        $header = $this->findHeader($fkasmtno);

        if ($message = $this->getPostedPeriodLockMessage($header->fkasmtdate)) {
            return redirect()->route('penerimaankas.view', $header->fkasmtno)->with('error', $message);
        }

        if ($message = $this->getClearedGiroLockMessage($header, 'Penerimaan kas ini')) {
            return redirect()->route('penerimaankas.view', $header->fkasmtno)->with('error', $message);
        }
        $payload = $this->validatePayload($request, $header);
        $this->ensureCreateDateWithinEditPeriod($payload['fkasmtdate'], $header->fkasmtdate);

        DB::transaction(function () use ($payload, $header) {
            $now = now();
            $details = $this->normalizeDetails($payload['details']);
            $totalAmount = $details->sum(fn(array $detail) => (float) $detail['fkasdtvalue']);
            $isGiroMundur = ($payload['fgiromundur'] ?? '0') === '1';
            $headerAccount = $this->resolveHeaderAccount(
                $isGiroMundur ? $this->resolveSetAccountCode(self::GIRO_MUNDUR_ACCOUNT_NAME) : ($payload['faccountheader'] ?? null)
            );
            $voucherNoInput = trim((string) ($payload['fkasmtno'] ?? ''));

            $header->update([
                'fkasmtno' => $voucherNoInput !== '' ? $voucherNoInput : $header->fkasmtno,
                'fbranchcode' => $payload['fbranchcode'],
                'fkasmtdate' => $payload['fkasmtdate'],
                'fwhom' => $payload['fwhom'] ?? null,
                'faccountheader' => $headerAccount?->faccount,
                'faccountheaderid' => $headerAccount?->faccid,
                'faccountno' => $headerAccount?->faccount,
                'faccountnoid' => $headerAccount?->faccid,
                'fdkheader' => $this->resolveHeaderDk($totalAmount),
                'fket' => $payload['fket'] ?? null,
                'fnogiro' => $payload['fnogiro'] ?? null,
                'fgiromundur' => $isGiroMundur ? '1' : '0',
                'ftgljatuhtempo' => ! empty($payload['ftgljatuhtempo']) ? Carbon::parse($payload['ftgljatuhtempo'])->startOfDay() : null,
                'famountpay' => $totalAmount,
                'famountpay_rp' => $totalAmount,
                'fuserid' => $this->currentUserId(),
                'fdatetime' => $now,
            ]);

            Trkasdt::where('fkasmtid', $header->fkasmtid)->delete();

            $nextDetailId = $this->nextIntegerId('trkasdt', 'fkasdtid');

            foreach ($details->values() as $index => $detail) {
                Trkasdt::create([
                    'fkasdtid' => $nextDetailId + $index,
                    'fkasmtid' => $header->fkasmtid,
                    'ftrancode' => self::TRAN_CODE,
                    'faccount' => $detail['faccount'],
                    'frefno' => $detail['frefno'] ?? null,
                    'fsubaccount' => $detail['fsubaccount'] ?? null,
                    'fdk' => $this->resolveDetailDk($detail['fkasdtvalue']),
                    'fnote' => $detail['fnote'] ?? null,
                    'fkasdtvalue' => $detail['fkasdtvalue'],
                    'fvalue_rp' => $detail['fkasdtvalue'],
                    'fjurnal' => $this->resolveDetailJournalAmount($detail['fkasdtvalue']),
                    'fjurnal_rp' => $this->resolveDetailJournalAmount($detail['fkasdtvalue']),
                    'fuserid' => $this->currentUserId(),
                    'fdatetime' => $now,
                    'fnou' => $index + 1,
                ]);
            }
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Penerimaan kas ' . $header->fkasmtno . ' berhasil diupdate.',
                'redirect_url' => route('penerimaankas.edit', ['fkasmtno' => $header->fkasmtno]),
            ]);
        }

        return redirect()
            ->route('penerimaankas.edit', ['fkasmtno' => $header->fkasmtno])
            ->with('success', 'Penerimaan kas ' . $header->fkasmtno . ' berhasil diupdate.');
    }

    public function destroy($fkasmtno)
    {
        $header = $this->findHeader($fkasmtno);

        if ($message = $this->getPostedPeriodLockMessage($header->fkasmtdate)) {
            return redirect()->route('penerimaankas.view', $header->fkasmtno)->with('error', $message);
        }
        if ($message = $this->getClearedGiroLockMessage($header, 'Penerimaan kas ini')) {
            return redirect()->route('penerimaankas.view', $header->fkasmtno)->with('error', $message);
        }
        $deletedNo = $header->fkasmtno;

        DB::transaction(function () use ($header) {
            Trkasdt::where('fkasmtid', $header->fkasmtid)->delete();
            $header->delete();
        });

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Penerimaan kas ' . $deletedNo . ' berhasil dihapus.',
                'redirect_url' => route('penerimaankas.index'),
            ]);
        }

        return redirect()
            ->route('penerimaankas.index')
            ->with('success', 'Penerimaan kas ' . $deletedNo . ' berhasil dihapus.');
    }

    public function print(string $fkasmtno)
    {
        $header = Trkasmt::query()
            ->leftJoin('account as acc', 'acc.faccount', '=', 'trkasmt.faccountheader')
            ->where('trkasmt.ftrancode', self::TRAN_CODE)
            ->where('trkasmt.fkasmtno', $fkasmtno)
            ->first([
                'trkasmt.*',
                'acc.faccname as header_account_name',
            ]);

        if (! $header) {
            return redirect()->back()->with('error', 'Penerimaan kas tidak ada.');
        }

        $details = DB::table('trkasdt as dt')
            ->leftJoin('account as acc', 'acc.faccount', '=', 'dt.faccount')
            ->leftJoin('mssubaccount as sub', 'sub.fsubaccountcode', '=', 'dt.fsubaccount')
            ->where('dt.fkasmtid', $header->fkasmtid)
            ->orderBy('dt.fnou')
            ->get([
                'dt.*',
                'acc.faccname as account_name',
                'sub.fsubaccountname as subaccount_name',
            ]);

        $totalAmount = (float) $details->sum(fn($detail) => (float) ($detail->fkasdtvalue ?? 0));
        $fmt = fn($date) => $date ? Carbon::parse($date)->translatedFormat('d F Y') : '-';

        return view('penerimaankas.print', [
            'hdr' => $header,
            'dt' => $details,
            'fmt' => $fmt,
            'totalAmount' => $totalAmount,
            'company_name' => config('app.company_name', 'PT. DEMO VERSION'),
            'company_city' => config('app.company_city', 'Tangerang'),
        ]);
    }

    private function formViewData(Trkasmt $header, Collection $details, array $overrides = []): array
    {
        $headerAccounts = $this->resolveHeaderAccounts();

        return array_merge([
            'pengeluaranKas' => $header,
            'details' => $details->isNotEmpty() ? $details : collect([new Trkasdt]),
            'currentBranchCode' => $this->resolveBranchCode(),
            'branches' => DB::table('mscabang')
                ->orderBy('fcabangkode')
                ->get(['fcabangid', 'fcabangkode', 'fcabangname']),
            'giroMundurHeaderAccount' => ($giroCode = $this->resolveSetAccountCode(self::GIRO_MUNDUR_ACCOUNT_NAME))
                ? Account::query()->where('faccount', $giroCode)->first(['faccid', 'faccount', 'faccname'])
                : null,
            'headerAccounts' => $headerAccounts,
            'accounts' => Account::query()
                ->where('fend', 1)
                ->where('fnonactive', '0')
                ->orderBy('faccount')
                ->get(['faccid', 'faccount', 'faccname', 'fhavesubaccount', 'ftypesubaccount']),
            'subaccounts' => Subaccount::query()
                ->where('fnonactive', '0')
                ->orderBy('fsubaccountcode')
                ->get(['fsubaccountid', 'fsubaccountcode', 'fsubaccountname']),
            'customers' => Customer::query()
                ->orderBy('fcustomercode')
                ->get(['fcustomerid', 'fcustomercode', 'fcustomername']),
            'suppliers' => Supplier::query()
                ->orderBy('fsuppliercode')
                ->get(['fsupplierid', 'fsuppliercode', 'fsuppliername']),
            'journalAccountValidation' => $this->resolveJournalAccountValidationConfig(),
        ], $overrides);
    }

    private function validatePayload(Request $request, ?Trkasmt $header = null): array
    {
        $allowedHeaderAccounts = $this->resolveHeaderAccounts()
            ->pluck('faccount')
            ->filter()
            ->map(fn($value) => trim((string) $value))
            ->values()
            ->all();
        $giroAccount = trim((string) $this->resolveSetAccountCode(self::GIRO_MUNDUR_ACCOUNT_NAME));
        $isGiroMundur = $request->boolean('fgiromundur');

        $request->merge([
            'details' => $this->filterEmptyDetailRows($request->input('details', [])),
            'fgiromundur' => $isGiroMundur ? '1' : '0',
            'fbranchcode' => trim((string) $request->input('fbranchcode', $header?->fbranchcode ?? $this->resolveBranchCode())),
        ]);

        $payload = $request->validate([
            'fkasmtno' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('trkasmt', 'fkasmtno')->ignore($header?->fkasmtno, 'fkasmtno'),
            ],
            'fkasmtdate' => ['required', 'date'],
            'fbranchcode' => ['required', 'string', 'max:10', Rule::exists('mscabang', 'fcabangkode')],
            'fnogiro' => ['nullable', 'string', 'max:35', Rule::unique('trkasmt', 'fnogiro')->ignore($header?->fkasmtid, 'fkasmtid')],
            'fwhom' => ['nullable', 'string', 'max:40'],
            'fgiromundur' => ['nullable', 'in:0,1'],
            'ftgljatuhtempo' => ['nullable', 'date', Rule::requiredIf($isGiroMundur), 'before_or_equal:fkasmtdate'],
            'faccountheader' => [
                'nullable',
                'string',
                'max:15',
                Rule::in($isGiroMundur ? array_values(array_filter([$giroAccount])) : $allowedHeaderAccounts),
                Rule::exists('account', 'faccount'),
            ],
            'fket' => ['nullable', 'string', 'max:50'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.faccount' => ['required', 'string', 'max:10'],
            'details.*.frefno' => ['nullable', 'string', 'max:30'],
            'details.*.fsubaccount' => ['nullable', 'string', 'max:50'],
            'details.*.fnote' => ['nullable', 'string', 'max:100'],
            'details.*.fkasdtvalue' => ['required', 'numeric', 'not_in:0'],
        ], [
            'fkasmtdate.required' => 'Tanggal wajib diisi.',
            'fbranchcode.required' => 'Cabang wajib diisi.',
            'fnogiro.unique' => 'No. giro / cek sudah dipakai.',
            'ftgljatuhtempo.required' => 'Tgl. jatuh tempo wajib diisi saat giro mundur.',
            'ftgljatuhtempo.before_or_equal' => 'Tgl. jatuh tempo tidak boleh melebihi tanggal.',
            'faccountheader.in' => 'Cash / bank account tidak valid.',
            'details.required' => 'Minimal 1 detail penerimaan.',
            'details.*.faccount.required' => 'Account detail wajib diisi.',
            'details.*.fkasdtvalue.required' => 'Jumlah bayar wajib diisi.',
            'details.*.fkasdtvalue.not_in' => 'Jumlah bayar tidak boleh 0.',
        ]);

        if ($isGiroMundur) {
            $payload['faccountheader'] = $giroAccount;
        }

        $this->validateJournalDetailAccounts($payload['details']);
        $this->validateDetailSubaccountAccess($payload['details']);

        return $payload;
    }

    private function validateJournalDetailAccounts(array $details): void
    {
        $validationConfig = $this->resolveJournalAccountValidationConfig();
        $normalizedCodes = collect($details)
            ->pluck('faccount')
            ->map(fn($value) => strtoupper(trim((string) $value)))
            ->filter()
            ->values()
            ->all();

        $detailAccounts = DB::table('account')
            ->select('faccount', 'faccname', 'fhavesubaccount', 'ftypesubaccount')
            ->where('fend', 1)
            ->where('fnonactive', '0')
            ->whereIn(DB::raw('UPPER(faccount)'), $normalizedCodes)
            ->get()
            ->keyBy(fn($account) => strtoupper(trim((string) $account->faccount)));

        foreach ($details as $index => $detail) {
            $accountCode = strtoupper(trim((string) ($detail['faccount'] ?? '')));
            $referenceNo = trim((string) ($detail['frefno'] ?? ''));
            $subaccountCode = trim((string) ($detail['fsubaccount'] ?? ''));

            if ($accountCode === '') {
                continue;
            }

            if (isset($validationConfig['system'][$accountCode])) {
                throw ValidationException::withMessages([
                    "details.$index.faccount" => 'Account ' . $validationConfig['system'][$accountCode]['display_name'] . ' tidak bisa dipakai di transaksi ini.',
                ]);
            }

            if (isset($validationConfig['stock'][$accountCode])) {
                throw ValidationException::withMessages([
                    "details.$index.faccount" => 'Account ' . $validationConfig['stock'][$accountCode]['display_name'] . ' sebaiknya diproses lewat Adjustment Stok.',
                ]);
            }

            if (isset($validationConfig['reference'][$accountCode]) && $referenceNo === '') {
                throw ValidationException::withMessages([
                    "details.$index.frefno" => 'No. Referensi wajib diisi untuk account Piutang atau Hutang Dagang.',
                ]);
            }

            $account = $detailAccounts->get($accountCode);

            if (! $account) {
                throw ValidationException::withMessages([
                    "details.$index.faccount" => 'Account yang dipilih tidak ditemukan, bukan account detail, atau nonaktif.',
                ]);
            }

            if ((string) ($account->fhavesubaccount ?? '0') === '1' && $subaccountCode === '') {
                throw ValidationException::withMessages([
                    "details.$index.fsubaccount" => 'Sub Account wajib dipilih untuk account ini.',
                ]);
            }
        }
    }

    private function validateDetailSubaccountAccess(array $details): void
    {
        $accounts = Account::query()
            ->whereIn('faccount', collect($details)->pluck('faccount')->filter()->all())
            ->get(['faccount', 'fhavesubaccount', 'ftypesubaccount'])
            ->keyBy('faccount');

        $subaccountCodes = collect($details)->pluck('fsubaccount')->filter()->unique()->values()->all();
        $subaccounts = Subaccount::query()
            ->whereIn('fsubaccountcode', $subaccountCodes)
            ->get(['fsubaccountcode'])
            ->keyBy('fsubaccountcode');
        $customers = Customer::query()
            ->whereIn('fcustomercode', $subaccountCodes)
            ->get(['fcustomercode'])
            ->keyBy('fcustomercode');
        $suppliers = Supplier::query()
            ->whereIn('fsuppliercode', $subaccountCodes)
            ->get(['fsuppliercode'])
            ->keyBy('fsuppliercode');

        $errors = [];

        foreach ($details as $index => $detail) {
            $accountCode = trim((string) ($detail['faccount'] ?? ''));
            $subaccountCode = trim((string) ($detail['fsubaccount'] ?? ''));

            if ($accountCode === '') {
                continue;
            }

            $account = $accounts->get($accountCode);
            $hasSubaccount = (string) ($account?->fhavesubaccount ?? '0') === '1';
            $subaccountType = $this->normalizeSubaccountType($account?->ftypesubaccount ?? 'S');

            if ($subaccountCode === '') {
                continue;
            }

            if (! $hasSubaccount) {
                $errors["details.$index.fsubaccount"] = 'Sub Account hanya boleh diisi untuk account yang memang memakai Sub Account.';
                continue;
            }

            if ($subaccountType === 'C' && ! $customers->has($subaccountCode)) {
                $errors["details.$index.fsubaccount"] = 'Customer yang dipilih tidak ditemukan.';
                continue;
            }

            if ($subaccountType === 'P' && ! $suppliers->has($subaccountCode)) {
                $errors["details.$index.fsubaccount"] = 'Supplier yang dipilih tidak ditemukan.';
                continue;
            }

            if (! in_array($subaccountType, ['C', 'P'], true) && ! $subaccounts->has($subaccountCode)) {
                $errors["details.$index.fsubaccount"] = 'Sub Account yang dipilih tidak ditemukan.';
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function filterEmptyDetailRows(array $details): array
    {
        return collect($details)
            ->filter(function ($detail) {
                if (!is_array($detail)) {
                    return false;
                }

                $account = trim((string) ($detail['faccount'] ?? ''));
                $reference = trim((string) ($detail['frefno'] ?? ''));
                $subaccount = trim((string) ($detail['fsubaccount'] ?? ''));
                $note = trim((string) ($detail['fnote'] ?? ''));
                $amount = (float) str_replace(',', '', (string) ($detail['fkasdtvalue'] ?? ''));

                return $account !== '' || $reference !== '' || $subaccount !== '' || $note !== '' || $amount != 0.0;
            })
            ->values()
            ->all();
    }

    private function normalizeSubaccountType($value): string
    {
        $normalized = strtoupper(trim((string) $value));

        return match ($normalized) {
            'C', 'CUSTOMER' => 'C',
            'P', 'SUPPLIER' => 'P',
            default => 'S',
        };
    }

    private function normalizeDetails(array $details): Collection
    {
        return collect($details)
            ->map(function (array $detail) {
                return [
                    'faccount' => trim((string) ($detail['faccount'] ?? '')),
                    'frefno' => trim((string) ($detail['frefno'] ?? '')) ?: null,
                    'fsubaccount' => trim((string) ($detail['fsubaccount'] ?? '')) ?: null,
                    'fnote' => trim((string) ($detail['fnote'] ?? '')) ?: null,
                    'fkasdtvalue' => round((float) ($detail['fkasdtvalue'] ?? 0), 2),
                ];
            })
            ->filter(fn(array $detail) => $detail['faccount'] !== '');
    }

    private function resolveHeaderAccount(?string $accountCode): ?Account
    {
        $accountCode = trim((string) $accountCode);

        if ($accountCode === '') {
            return null;
        }

        return Account::query()
            ->where('faccount', $accountCode)
            ->first(['faccid', 'faccount', 'faccname']);
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

    private function resolveSetAccountCodeMap(array $accountNames): array
    {
        return DB::table('set_account')
            ->whereIn('faccount_name', $accountNames)
            ->pluck('faccount', 'faccount_name')
            ->map(fn($value) => trim((string) $value))
            ->toArray();
    }

    private function resolveJournalAccountValidationConfig(): array
    {
        $setAccountNames = array_merge(
            self::SYSTEM_JOURNAL_ACCOUNT_NAMES,
            self::STOCK_JOURNAL_ACCOUNT_NAMES,
            self::REFERENCE_REQUIRED_ACCOUNT_NAMES,
        );

        $codeMap = $this->resolveSetAccountCodeMap($setAccountNames);
        $accountMetadata = Account::query()
            ->whereIn('faccount', collect($codeMap)->filter()->all())
            ->get(['faccount', 'faccname'])
            ->keyBy(fn($account) => strtoupper(trim((string) $account->faccount)));

        $buildGroup = function (array $names) use ($codeMap, $accountMetadata) {
            $group = [];

            foreach ($names as $name) {
                $code = strtoupper(trim((string) ($codeMap[$name] ?? '')));
                if ($code === '') {
                    continue;
                }

                $group[$code] = [
                    'set_name' => $name,
                    'display_name' => trim((string) ($accountMetadata->get($code)->faccname ?? $name)),
                ];
            }

            return $group;
        };

        return [
            'system' => $buildGroup(self::SYSTEM_JOURNAL_ACCOUNT_NAMES),
            'stock' => $buildGroup(self::STOCK_JOURNAL_ACCOUNT_NAMES),
            'reference' => $buildGroup(self::REFERENCE_REQUIRED_ACCOUNT_NAMES),
        ];
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

                    if (! $kodeCabang) {
                        $kodeCabang = DB::table('mscabang')
                            ->whereRaw('LOWER(fcabangname) = LOWER(?)', [$needle])
                            ->value('fcabangkode');
                    }
                }

                if (! empty($kodeCabang)) {
                    return trim((string) $kodeCabang);
                }
            }
        }

        return 'NA';
    }

    private function resolveHeaderDk(float $amount): string
    {
        return 'D';
    }

    private function resolveDetailDk(float $amount): string
    {
        return $amount >= 0 ? 'K' : 'D';
    }

    private function resolveDetailJournalAmount(float $amount): float
    {
        return $amount < 0 ? abs($amount) : $amount;
    }

    private function findHeader($fkasmtno): Trkasmt
    {
        return Trkasmt::with(['details', 'headerAccount'])
            ->where('ftrancode', self::TRAN_CODE)
            ->where('fkasmtno', $fkasmtno)
            ->firstOrFail();
    }

    private function resolveBankType(?Account $headerAccount = null): string
    {
        $bankType = trim((string) ($headerAccount?->finitjurnal ?? ''));

        return $bankType !== '' ? $bankType : '00';
    }

    private function generateVoucherNo(Carbon $date, ?string $branchCode = null, ?Account $headerAccount = null): string
    {
        $branchCode = trim((string) ($branchCode ?: $this->resolveBranchCode())) ?: 'NA';
        $bankType = $this->resolveBankType($headerAccount);
        $prefix = sprintf('%s.%s.%s%s.%s.', self::TRAN_CODE, $branchCode, $date->format('y'), $date->format('m'), $bankType);
        $lastNumber = DB::table('trkasmt')
            ->where('fkasmtno', 'like', $prefix . '%')
            ->selectRaw("
                MAX(
                    CASE
                        WHEN split_part(fkasmtno, '.', 4) ~ '^[0-9]+$'
                        THEN CAST(split_part(fkasmtno, '.', 4) AS integer)
                        ELSE NULL
                    END
                ) as last_no
            ")
            ->value('last_no');

        return $prefix . str_pad((string) (((int) $lastNumber) + 1), 4, '0', STR_PAD_LEFT);
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

    private function hasPermission(string $permission): bool
    {
        $permissions = array_filter(explode(',', (string) session('user_restricted_permissions', '')));

        if (empty($permissions)) {
            return true;
        }

        return in_array($permission, $permissions, true);
    }
}
