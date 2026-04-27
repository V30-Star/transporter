<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Trkasdt;
use App\Models\Trkasmt;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PengeluaranKasController extends Controller
{
    private const TRAN_CODE = 'BKK';

    public function index()
    {
        $records = Trkasmt::query()
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

        return view('pengeluarankas.index', [
            'records' => $records,
            'canCreate' => $this->hasPermission('createPengeluaranKas'),
            'canEdit' => $this->hasPermission('updatePengeluaranKas'),
            'canDelete' => $this->hasPermission('deletePengeluaranKas'),
        ]);
    }

    public function create()
    {
        return view('pengeluarankas.create', $this->formViewData(new Trkasmt([
            'fkasmtdate' => now()->toDateString(),
        ]), collect([new Trkasdt]), [
            'pageTitle' => 'Pengeluaran Kas',
            'formAction' => route('pengeluarankas.store'),
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

            $headerAccount = $this->resolveHeaderAccount($payload['faccountheader'] ?? null);
            $voucherNo = $payload['fkasmtno'] ?: $this->generateVoucherNo(Carbon::parse($payload['fkasmtdate']));
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
                'fdkheader' => 'K',
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
                    'fdk' => 'D',
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
            ->route('pengeluarankas.view', ['fkasmtno' => $header->fkasmtno])
            ->with('success', 'Data Pengeluaran Kas '.$header->fkasmtno.' berhasil disimpan.');
    }

    public function view($fkasmtno)
    {
        $header = $this->findHeader($fkasmtno);

        return view('pengeluarankas.view', $this->formViewData($header, $header->details, [
            'pageTitle' => 'View Pengeluaran Kas',
            'isReadOnly' => true,
        ]));
    }

    public function edit($fkasmtno)
    {
        $header = $this->findHeader($fkasmtno);

        return view('pengeluarankas.edit', $this->formViewData($header, $header->details, [
            'pageTitle' => 'Edit Pengeluaran Kas',
            'formAction' => route('pengeluarankas.update', $header->fkasmtno),
            'formMethod' => 'PATCH',
            'isReadOnly' => false,
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

            $header->update([
                'fkasmtno' => $payload['fkasmtno'] ?: $header->fkasmtno,
                'fkasmtdate' => $payload['fkasmtdate'],
                'fwhom' => $payload['fwhom'] ?? null,
                'faccountheader' => $headerAccount?->faccount,
                'faccountheaderid' => $headerAccount?->faccid,
                'faccountno' => $headerAccount?->faccount,
                'faccountnoid' => $headerAccount?->faccid,
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
                    'fdk' => 'D',
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
            ->route('pengeluarankas.index')
            ->with('success', 'Data Pengeluaran Kas '.$header->fkasmtno.' berhasil diperbarui.');
    }

    public function destroy($fkasmtno)
    {
        $header = $this->findHeader($fkasmtno);

        DB::transaction(function () use ($header) {
            Trkasdt::where('fkasmtid', $header->fkasmtid)->delete();
            $header->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Data Pengeluaran Kas '.$header->fkasmtno.' berhasil dihapus.',
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
        ], $overrides);
    }

    private function validatePayload(Request $request, ?Trkasmt $header = null): array
    {
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
            'details.*.fnote' => ['nullable', 'string', 'max:100'],
            'details.*.fkasdtvalue' => ['required', 'numeric', 'gt:0'],
        ], [
            'fkasmtdate.required' => 'Tanggal wajib diisi.',
            'details.required' => 'Minimal harus ada satu detail pengeluaran.',
            'details.*.faccount.required' => 'Account detail wajib diisi.',
            'details.*.fkasdtvalue.required' => 'Jumlah bayar wajib diisi.',
            'details.*.fkasdtvalue.gt' => 'Jumlah bayar harus lebih besar dari 0.',
        ]);

        return $payload;
    }

    private function normalizeDetails(array $details): Collection
    {
        return collect($details)
            ->map(function (array $detail) {
                return [
                    'faccount' => trim((string) ($detail['faccount'] ?? '')),
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

    private function findHeader($fkasmtno): Trkasmt
    {
        return Trkasmt::with(['details', 'headerAccount'])
            ->where('fkasmtno', $fkasmtno)
            ->firstOrFail();
    }

    private function generateVoucherNo(Carbon $date): string
    {
        $prefix = 'PK.'.$date->format('ym').'.';
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
