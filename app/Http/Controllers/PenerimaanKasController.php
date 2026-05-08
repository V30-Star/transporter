<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Subaccount;
use App\Models\Trkasdt;
use App\Models\Trkasmt;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PenerimaanKasController extends Controller
{
    private const TRAN_CODE = 'BKM';

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
                DB::raw("
                    COALESCE(
                        string_agg(
                            DISTINCT concat_ws(' - ', dt.faccount, acc.faccname),
                            ', ' ORDER BY concat_ws(' - ', dt.faccount, acc.faccname)
                        ),
                        '-'
                    ) as account_summary
                "),
                DB::raw("
                    COALESCE(
                        string_agg(
                            DISTINCT NULLIF(trim(dt.fnote), ''),
                            ', ' ORDER BY NULLIF(trim(dt.fnote), '')
                        ),
                        COALESCE(NULLIF(trim(trkasmt.fket), ''), '-')
                    ) as description_summary
                "),
                DB::raw('COALESCE(SUM(COALESCE(dt.fkasdtvalue, 0)), COALESCE(trkasmt.famountpay, 0), 0) as payment_amount'),
            ])
            ->groupBy('trkasmt.fkasmtid', 'trkasmt.fkasmtno', 'trkasmt.fkasmtdate', 'trkasmt.fnogiro', 'trkasmt.fket')
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
        ]), collect([new Trkasdt]), [
            'pageTitle' => 'Penerimaan Kas',
            'formAction' => route('penerimaankas.store'),
            'formMethod' => 'POST',
            'isReadOnly' => false,
        ]));
    }

    public function store(Request $request)
    {
        $payload = $this->validatePayload($request);
        $savedHeaderId = null;

        $header = DB::transaction(function () use ($payload, &$savedHeaderId) {
            $now = now();
            $details = $this->normalizeDetails($payload['details']);
            $totalAmount = $details->sum(fn (array $detail) => (float) $detail['fkasdtvalue']);
            $voucherNoInput = trim((string) ($payload['fkasmtno'] ?? ''));

            $headerAccount = $this->resolveHeaderAccount($payload['faccountheader'] ?? null);
            $voucherNo = $voucherNoInput !== ''
                ? $voucherNoInput
                : $this->generateVoucherNo(Carbon::parse($payload['fkasmtdate']));
            $headerId = $this->nextIntegerId('trkasmt', 'fkasmtid');
            $savedHeaderId = $headerId;

            $header = Trkasmt::create([
                'fkasmtid' => $headerId,
                'fkasmtno' => $voucherNo,
                'ftrancode' => self::TRAN_CODE,
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
                'fgiromundur' => '0',
                'fnogiro' => $payload['fnogiro'] ?? null,
                'faccountno' => $headerAccount?->faccount,
                'faccountnoid' => $headerAccount?->faccid,
                'fstatusgiro' => '0',
            ]);

            $nextDetailId = $this->nextIntegerId('trkasdt', 'fkasdtid');
            $accountMap = Account::query()
                ->whereIn('faccount', $details->pluck('faccount')->all())
                ->get(['faccid', 'faccount'])
                ->keyBy('faccount');

            foreach ($details->values() as $index => $detail) {
                $account = $accountMap->get($detail['faccount']);

                Trkasdt::create([
                    'fkasdtid' => $nextDetailId + $index,
                    'fkasmtid' => $savedHeaderId,
                    'ftrancode' => self::TRAN_CODE,
                    'faccount' => $detail['faccount'],
                    'faccountid' => $account?->faccid,
                    'fsubaccount' => $detail['fsubaccount'] ?? null,
                    'fdk' => $this->resolveDetailDk($detail['fkasdtvalue']),
                    'fnote' => $detail['fnote'] ?? null,
                    'fkasdtvalue' => $detail['fkasdtvalue'],
                    'fvalue_rp' => $detail['fkasdtvalue'],
                    'fjurnal' => $detail['fkasdtvalue'],
                    'fjurnal_rp' => $detail['fkasdtvalue'],
                    'fuserid' => $this->currentUserId(),
                    'fdatetime' => $now,
                    'fnou' => $index + 1,
                ]);
            }

            return $header;
        });

        return redirect()
            ->route('penerimaankas.create')
            ->with('success', 'Data Penerimaan Kas '.$header->fkasmtno.' berhasil disimpan.');
    }

    public function view($fkasmtno)
    {
        $header = $this->findHeader($fkasmtno);

        return view('penerimaankas.view', $this->formViewData($header, $header->details, [
            'pageTitle' => 'View Penerimaan Kas',
            'isReadOnly' => true,
            'printRoute' => route('penerimaankas.print', $header->fkasmtno),
        ]));
    }

    public function edit($fkasmtno)
    {
        $header = $this->findHeader($fkasmtno);

        return view('penerimaankas.edit', $this->formViewData($header, $header->details, [
            'pageTitle' => 'Edit Penerimaan Kas',
            'formAction' => route('penerimaankas.update', $header->fkasmtno),
            'formMethod' => 'PATCH',
            'isReadOnly' => false,
        ]));
    }

    public function delete($fkasmtno)
    {
        $header = $this->findHeader($fkasmtno);

        return view('penerimaankas.delete', $this->formViewData($header, $header->details, [
            'pageTitle' => 'Hapus Penerimaan Kas',
            'formAction' => route('penerimaankas.destroy', $header->fkasmtno),
            'formMethod' => 'DELETE',
            'isReadOnly' => true,
        ]));
    }

    public function update(Request $request, $fkasmtno)
    {
        $header = $this->findHeader($fkasmtno);
        $payload = $this->validatePayload($request, $header);

        DB::transaction(function () use ($payload, $header) {
            $now = now();
            $details = $this->normalizeDetails($payload['details']);
            $totalAmount = $details->sum(fn (array $detail) => (float) $detail['fkasdtvalue']);
            $headerAccount = $this->resolveHeaderAccount($payload['faccountheader'] ?? null);
            $voucherNoInput = trim((string) ($payload['fkasmtno'] ?? ''));

            $header->update([
                'fkasmtno' => $voucherNoInput !== '' ? $voucherNoInput : $header->fkasmtno,
                'fkasmtdate' => $payload['fkasmtdate'],
                'fwhom' => $payload['fwhom'] ?? null,
                'faccountheader' => $headerAccount?->faccount,
                'faccountheaderid' => $headerAccount?->faccid,
                'faccountno' => $headerAccount?->faccount,
                'faccountnoid' => $headerAccount?->faccid,
                'fdkheader' => $this->resolveHeaderDk($totalAmount),
                'fket' => $payload['fket'] ?? null,
                'fnogiro' => $payload['fnogiro'] ?? null,
                'famountpay' => $totalAmount,
                'famountpay_rp' => $totalAmount,
                'fuserid' => $this->currentUserId(),
                'fdatetime' => $now,
            ]);

            Trkasdt::where('fkasmtid', $header->fkasmtid)->delete();

            $nextDetailId = $this->nextIntegerId('trkasdt', 'fkasdtid');
            $accountMap = Account::query()
                ->whereIn('faccount', $details->pluck('faccount')->all())
                ->get(['faccid', 'faccount'])
                ->keyBy('faccount');

            foreach ($details->values() as $index => $detail) {
                $account = $accountMap->get($detail['faccount']);

                Trkasdt::create([
                    'fkasdtid' => $nextDetailId + $index,
                    'fkasmtid' => $header->fkasmtid,
                    'ftrancode' => self::TRAN_CODE,
                    'faccount' => $detail['faccount'],
                    'faccountid' => $account?->faccid,
                    'fsubaccount' => $detail['fsubaccount'] ?? null,
                    'fdk' => $this->resolveDetailDk($detail['fkasdtvalue']),
                    'fnote' => $detail['fnote'] ?? null,
                    'fkasdtvalue' => $detail['fkasdtvalue'],
                    'fvalue_rp' => $detail['fkasdtvalue'],
                    'fjurnal' => $detail['fkasdtvalue'],
                    'fjurnal_rp' => $detail['fkasdtvalue'],
                    'fuserid' => $this->currentUserId(),
                    'fdatetime' => $now,
                    'fnou' => $index + 1,
                ]);
            }
        });

        return redirect()
            ->route('penerimaankas.edit', ['fkasmtno' => $header->fkasmtno])
            ->with('success', 'Data Penerimaan Kas '.$header->fkasmtno.' berhasil diperbarui.');
    }

    public function destroy($fkasmtno)
    {
        $header = $this->findHeader($fkasmtno);
        $deletedNo = $header->fkasmtno;

        DB::transaction(function () use ($header) {
            Trkasdt::where('fkasmtid', $header->fkasmtid)->delete();
            $header->delete();
        });

        if (! request()->expectsJson()) {
            return redirect()
                ->route('penerimaankas.index')
                ->with('success', 'Data Penerimaan Kas '.$deletedNo.' berhasil dihapus.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Data Penerimaan Kas '.$deletedNo.' berhasil dihapus.',
        ]);
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
            return redirect()->back()->with('error', 'Data Penerimaan Kas tidak ditemukan.');
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

        $totalAmount = (float) $details->sum(fn ($detail) => (float) ($detail->fkasdtvalue ?? 0));
        $fmt = fn ($date) => $date ? Carbon::parse($date)->translatedFormat('d F Y') : '-';

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
        return array_merge([
            'pengeluaranKas' => $header,
            'details' => $details->isNotEmpty() ? $details : collect([new Trkasdt]),
            'accounts' => Account::query()
                ->where('fend', 1)
                ->where('fnonactive', '0')
                ->orderBy('faccount')
                ->get(['faccid', 'faccount', 'faccname']),
            'subaccounts' => Subaccount::query()
                ->where('fnonactive', '0')
                ->orderBy('fsubaccountcode')
                ->get(['fsubaccountid', 'fsubaccountcode', 'fsubaccountname']),
        ], $overrides);
    }

    private function validatePayload(Request $request, ?Trkasmt $header = null): array
    {
        $request->merge([
            'details' => $this->filterEmptyDetailRows($request->input('details', [])),
        ]);

        $payload = $request->validate([
            'fkasmtno' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('trkasmt', 'fkasmtno')->ignore($header?->fkasmtno, 'fkasmtno'),
            ],
            'fkasmtdate' => ['required', 'date'],
            'fnogiro' => ['nullable', 'string', 'max:35'],
            'fwhom' => ['nullable', 'string', 'max:40'],
            'faccountheader' => ['nullable', 'string', 'max:15', Rule::exists('account', 'faccount')],
            'fket' => ['nullable', 'string', 'max:50'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.faccount' => ['required', 'string', 'max:10', Rule::exists('account', 'faccount')],
            'details.*.fsubaccount' => ['nullable', 'string', 'max:50', Rule::exists('mssubaccount', 'fsubaccountcode')],
            'details.*.fnote' => ['nullable', 'string', 'max:100'],
            'details.*.fkasdtvalue' => ['required', 'numeric', 'not_in:0'],
        ], [
            'fkasmtdate.required' => 'Tanggal wajib diisi.',
            'details.required' => 'Minimal harus ada satu detail penerimaan.',
            'details.*.faccount.required' => 'Account detail wajib diisi.',
            'details.*.fkasdtvalue.required' => 'Jumlah bayar wajib diisi.',
            'details.*.fkasdtvalue.not_in' => 'Jumlah bayar tidak boleh 0.',
        ]);

        return $payload;
    }

    private function filterEmptyDetailRows(array $details): array
    {
        return collect($details)
            ->filter(function ($detail) {
                if (!is_array($detail)) {
                    return false;
                }

                $account = trim((string) ($detail['faccount'] ?? ''));
                $subaccount = trim((string) ($detail['fsubaccount'] ?? ''));
                $note = trim((string) ($detail['fnote'] ?? ''));
                $amount = trim((string) ($detail['fkasdtvalue'] ?? ''));

                return $account !== '' || $subaccount !== '' || $note !== '' || $amount !== '';
            })
            ->values()
            ->all();
    }

    private function normalizeDetails(array $details): Collection
    {
        return collect($details)
            ->map(function (array $detail) {
                return [
                    'faccount' => trim((string) ($detail['faccount'] ?? '')),
                    'fsubaccount' => trim((string) ($detail['fsubaccount'] ?? '')) ?: null,
                    'fnote' => trim((string) ($detail['fnote'] ?? '')) ?: null,
                    'fkasdtvalue' => round((float) ($detail['fkasdtvalue'] ?? 0), 2),
                ];
            })
            ->filter(fn (array $detail) => $detail['faccount'] !== '');
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

    private function resolveHeaderDk(float $amount): string
    {
        return 'D';
    }

    private function resolveDetailDk(float $amount): string
    {
        return $amount >= 0 ? 'K' : 'D';
    }

    private function findHeader($fkasmtno): Trkasmt
    {
        return Trkasmt::with(['details', 'headerAccount'])
            ->where('ftrancode', self::TRAN_CODE)
            ->where('fkasmtno', $fkasmtno)
            ->firstOrFail();
    }

    private function generateVoucherNo(Carbon $date): string
    {
        $prefix = 'KM.'.$date->format('ym').'.';
        $lastNumber = DB::table('trkasmt')
            ->where('fkasmtno', 'like', $prefix.'%')
            ->selectRaw("MAX(CAST(split_part(fkasmtno, '.', 3) AS integer)) as last_no")
            ->value('last_no');

        return $prefix.str_pad((string) (((int) $lastNumber) + 1), 4, '0', STR_PAD_LEFT);
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
