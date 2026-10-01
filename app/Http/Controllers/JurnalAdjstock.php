<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Jurnal Adjustment Stok.
 * Masuk (M): Dr PERSEDIAAN, Cr ADJUSTMENTSTOK. Keluar (K): sebaliknya.
 * Qty bertanda negatif membalik arah baris tersebut.
 */
class JurnalAdjstock
{
    private const JURNAL_TYPE = 'ADJ';

    private const ACCOUNT_PERSEDIAAN = 'PERSEDIAAN';

    private const ACCOUNT_ADJUSTMENT = 'ADJUSTMENTSTOK';

    public static function sync(string $fstockmtno, Carbon $fstockmtdate, string $branchCode, string $userName): void
    {
        self::delete($fstockmtno);
        self::create($fstockmtno, $fstockmtdate, $branchCode, $userName);
    }

    public static function create(string $fstockmtno, Carbon $fstockmtdate, string $branchCode, string $userName): void
    {
        $fstockmtno = trim($fstockmtno);
        $header = DB::table('trstockmt')->whereRaw('trim(fstockmtno) = ?', [$fstockmtno])->first(['ftrancode', 'frate']);
        if (! $header) {
            throw ValidationException::withMessages(['fstockmtno' => 'Adjustment stok tidak ditemukan untuk membuat jurnal.']);
        }

        $sign = strtoupper(trim((string) $header->ftrancode)) === 'K' ? -1 : 1;
        $net = $sign * round((float) DB::table('trstockdt')->where('fstockmtno', $fstockmtno)->sum('ftotprice_rp'), 2);
        if (abs($net) < 0.005) {
            return;
        }

        $amount = round(abs($net), 2);
        $persediaan = self::accountCode(self::ACCOUNT_PERSEDIAAN);
        $adjustment = self::accountCode(self::ACCOUNT_ADJUSTMENT);
        [$debit, $kredit] = $net > 0 ? [$persediaan, $adjustment] : [$adjustment, $persediaan];

        $fjurnalno = 'JV.' . ltrim($fstockmtno, '.');
        $kodeCabang = trim($branchCode) !== '' ? trim($branchCode) : trim((string) (session('fcabang') ?: '01'));
        $userName = trim($userName) !== '' ? trim($userName) : 'System';
        $note = 'Adjustment Stok ' . $fstockmtno;
        $now = now();

        $jurnalId = DB::table('jurnalmt')->insertGetId([
            'fbranchcode' => $kodeCabang,
            'fjurnalno' => $fjurnalno,
            'fjurnaltype' => self::JURNAL_TYPE,
            'fjurnaldate' => $fstockmtdate,
            'fjurnalnote' => $note,
            'fbalance' => $amount,
            'fbalance_rp' => $amount,
            'fdatetime' => $now,
            'fuserid' => $userName,
        ], 'fjurnalmtid');

        $line = fn (int $no, string $account, string $dk) => [
            'fjurnalmtid' => $jurnalId,
            'fbranchcode' => $kodeCabang,
            'fjurnaltype' => self::JURNAL_TYPE,
            'fjurnalno' => $fjurnalno,
            'flineno' => $no,
            'faccount' => $account,
            'fdk' => $dk,
            'fsubaccount' => null,
            'frefno' => $fstockmtno,
            'frate' => 1,
            'famount' => $amount,
            'famount_rp' => $amount,
            'faccountnote' => $note,
            'fusercreate' => $userName,
            'fdatetime' => $now,
        ];

        DB::table('jurnaldt')->insert([$line(1, $debit, 'D'), $line(2, $kredit, 'K')]);
    }

    public static function delete(string $fstockmtno): void
    {
        $fjurnalno = 'JV.' . ltrim(trim($fstockmtno), '.');
        $ids = DB::table('jurnalmt')->where('fjurnalno', $fjurnalno)->where('fjurnaltype', self::JURNAL_TYPE)->pluck('fjurnalmtid')->all();
        if ($ids === []) {
            return;
        }

        DB::table('jurnaldt')->whereIn('fjurnalmtid', $ids)->delete();
        DB::table('jurnalmt')->whereIn('fjurnalmtid', $ids)->delete();
    }

    private static function accountCode(string $accountName): string
    {
        $faccount = trim((string) DB::table('set_account')->where('faccount_name', $accountName)->value('faccount'));
        if ($faccount === '') {
            throw ValidationException::withMessages(['set_account' => "Kode akun untuk '{$accountName}' belum diset pada tabel set_account."]);
        }

        return $faccount;
    }
}
