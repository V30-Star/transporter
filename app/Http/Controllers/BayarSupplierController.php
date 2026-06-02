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
    private const TRAN_CODE = 'BKK';
    private const PAYABLE_SET_ACCOUNT = 'HUTANGDAGANG';

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
                DB::raw("COALESCE(NULLIF(concat_ws(' - ', trkasmt.faccountheader, acc.faccname), ''), '-') as account_summary"),
                DB::raw("COALESCE(string_agg(DISTINCT NULLIF(TRIM(COALESCE(CASE WHEN TRIM(COALESCE(dt.freftype, '')) != 'ADM' THEN dt.frefno ELSE NULL END, '')), ''), ', ' ORDER BY NULLIF(TRIM(COALESCE(CASE WHEN TRIM(COALESCE(dt.freftype, '')) != 'ADM' THEN dt.frefno ELSE NULL END, '')), '')), '-') as pbl_summary"),
                DB::raw("COALESCE(NULLIF(TRIM(supp.fsuppliername), ''), '-') as supplier_name"),
            ])
            ->groupBy(
                'trkasmt.fkasmtid',
                'trkasmt.fkasmtno',
                'trkasmt.fkasmtdate',
                'trkasmt.fnogiro',
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
            'submitLabel' => 'Simpan',
            'draftKey' => 'bayarsupplier:create',
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
            'mt.fstockmtno',
            'mt.fstockmtdate',
            'mt.fsupplier',
            'mt.famountmt',
            'mt.famountremain',
            'mt.ftgljatuhtempo',
            's.fsuppliername',
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
                    'fstockmtno' => trim((string) ($row->fstockmtno ?? '')),
                    'fstockmtdate' => !empty($row->fstockmtdate) ? Carbon::parse($row->fstockmtdate)->format('Y-m-d') : null,
                    'fsupplier' => trim((string) ($row->fsupplier ?? '')),
                    'fsuppliername' => trim((string) ($row->fsuppliername ?? '')),
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
        $request->merge([
            'details' => $this->filterEmptyDetailRows($request->input('details', [])),
            'fbranchcode' => trim((string) $request->input('fbranchcode', $this->resolveBranchCode())),
            'fgiromundur' => $request->boolean('fgiromundur') ? '1' : '0',
        ]);

        $validated = $request->validate([
            'fkasmtno' => ['nullable', 'string', 'max:30', Rule::unique('trkasmt', 'fkasmtno')],
            'fkasmtdate' => ['required', 'date'],
            'fbranchcode' => ['required', 'string', 'max:10'],
            'fsupplier' => ['required', 'string', 'max:30', Rule::exists('mssupplier', 'fsuppliercode')],
            'faccountheader' => ['required', 'string', 'max:15', Rule::exists('account', 'faccount')->where(fn ($query) => $query->where('fend', 1))],
            'fnogiro' => ['nullable', 'string', 'max:35'],
            'fgiromundur' => ['nullable', 'in:0,1'],
            'ftgljatuhtempo' => ['nullable', 'date', Rule::requiredIf($request->input('fgiromundur') === '1'), 'after_or_equal:fkasmtdate'],
            'fket' => ['nullable', 'string', 'max:50'],
            'fbiayaadminbank' => ['nullable', 'numeric', 'min:0'],
            'faccountadmin' => [Rule::requiredIf((float) $request->input('fbiayaadminbank') > 0), 'nullable', 'string', 'max:15', Rule::exists('account', 'faccount')->where(fn ($query) => $query->where('fend', 1))],
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
            'faccountadmin.required' => 'Account biaya admin bank wajib diisi.',
            'details.required' => 'Minimal 1 faktur wajib diisi.',
            'details.*.frefno.required' => 'No. penerimaan wajib diisi.',
            'details.*.fkasdtvalue.required' => 'Total bayar wajib diisi.',
        ]);

        $supplier = Supplier::query()
            ->where('fsuppliercode', $validated['fsupplier'])
            ->firstOrFail(['fsupplierid', 'fsuppliercode', 'fsuppliername']);
        $headerAccount = Account::query()
            ->where('faccount', $validated['faccountheader'])
            ->firstOrFail(['faccid', 'faccount', 'faccname']);
        $detailRows = $this->normalizeDetails($validated['details']);
        $payableAccount = $this->resolveRequiredAccount(self::PAYABLE_SET_ACCOUNT, 'Akun hutang dagang belum disetting.');
        $bankAdminFee = round((float) ($validated['fbiayaadminbank'] ?? 0), 2);
        $adminAccount = null;

        if ($bankAdminFee > 0 && !empty($validated['faccountadmin'])) {
            $adminAccount = Account::query()
                ->where('faccount', $validated['faccountadmin'])
                ->firstOrFail(['faccid', 'faccount', 'faccname']);
        }

        $voucherNo = trim((string) ($validated['fkasmtno'] ?? '')) ?: $this->generateVoucherNo(Carbon::parse($validated['fkasmtdate']));
        $totalBayar = round((float) $detailRows->sum(fn (array $row) => (float) ($row['fkasdtvalue'] ?? 0)), 2);
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
                    'faccount' => $payableAccount->faccount,
                    'faccountid' => $payableAccount->faccid,
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

    private function filterEmptyDetailRows(array $details): array
    {
        return collect($details)
            ->filter(fn ($detail) => is_array($detail) && (trim((string) ($detail['frefno'] ?? '')) !== '' || (float) ($detail['fkasdtvalue'] ?? 0) !== 0.0))
            ->values()
            ->all();
    }

    private function normalizeDetails(array $details): Collection
    {
        return collect($details)
            ->map(fn (array $detail) => [
                'frefno' => trim((string) ($detail['frefno'] ?? '')),
                'fnilai_order' => round(abs((float) ($detail['fnilai_order'] ?? 0)), 2),
                'fsisa_hutang' => round(abs((float) ($detail['fsisa_hutang'] ?? 0)), 2),
                'fdiscpersen' => round((float) ($detail['fdiscpersen'] ?? 0), 2),
                'fdiscount' => round(abs((float) ($detail['fdiscount'] ?? 0)), 2),
                'fkasdtvalue' => round(abs((float) ($detail['fkasdtvalue'] ?? 0)), 2),
            ])
            ->filter(fn (array $detail) => $detail['frefno'] !== '' && (float) $detail['fkasdtvalue'] > 0)
            ->values();
    }

    private function resolveRequiredAccount(string $accountName, string $message): Account
    {
        $accountCode = trim((string) DB::table('set_account')->where('faccount_name', $accountName)->value('faccount'));

        if ($accountCode === '') {
            throw ValidationException::withMessages(['faccountheader' => $message]);
        }

        return Account::query()
            ->where('faccount', $accountCode)
            ->first(['faccid', 'faccount', 'faccname'])
            ?? throw ValidationException::withMessages(['faccountheader' => $message]);
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

    private function generateVoucherNo(Carbon $date): string
    {
        $prefix = 'BKK.' . $date->format('ym') . '.';
        $lastNumber = DB::table('trkasmt')
            ->where('fkasmtno', 'like', $prefix . '%')
            ->selectRaw("MAX(CAST(split_part(fkasmtno, '.', 3) AS integer)) as last_no")
            ->value('last_no');

        return $prefix . str_pad((string) (((int) $lastNumber) + 1), 4, '0', STR_PAD_LEFT);
    }

    private function formViewData(?Trkasmt $header = null, array $overrides = []): array
    {
        $supplierCode = trim((string) old('fsupplier', ''));
        $accountCode = trim((string) old('faccountheader', ''));
        $adminAccountCode = trim((string) old('faccountadmin', ''));

        $selectedSupplier = $supplierCode !== ''
            ? Supplier::query()->where('fsuppliercode', $supplierCode)->first(['fsupplierid', 'fsuppliercode', 'fsuppliername', 'ftempo'])
            : null;
        $selectedAccount = $accountCode !== ''
            ? Account::query()->where('faccount', $accountCode)->first(['faccid', 'faccount', 'faccname'])
            : null;
        $selectedAdminAccount = $adminAccountCode !== ''
            ? Account::query()->where('faccount', $adminAccountCode)->first(['faccid', 'faccount', 'faccname'])
            : null;
        $branchCode = old('fbranchcode', $header?->fbranchcode ?: $this->resolveBranchCode());

        return array_merge([
            'voucherNo' => old('fkasmtno', $header?->fkasmtno),
            'transactionDate' => old('fkasmtdate', optional($header?->fkasmtdate)->format('Y-m-d') ?? now()->format('Y-m-d')),
            'currentBranchCode' => $branchCode,
            'currentBranchLabel' => $this->resolveBranchLabel((string) $branchCode),
            'selectedSupplier' => $selectedSupplier,
            'selectedAccount' => $selectedAccount,
            'selectedAdminAccount' => $selectedAdminAccount,
            'detailRows' => old('details', []),
            'bankAdminFee' => old('fbiayaadminbank', 0),
            'dueDate' => old('ftgljatuhtempo', optional($header?->ftgljatuhtempo)->format('Y-m-d')),
            'giroMundur' => old('fgiromundur', ($header?->fgiromundur ?? '0')) === '1',
            'noteValue' => old('fket', $header?->fket),
            'giroNo' => old('fnogiro', $header?->fnogiro),
        ], $overrides);
    }
}
