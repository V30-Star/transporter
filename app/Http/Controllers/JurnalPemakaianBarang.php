<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Jurnal Pemakaian Barang (PBR), nilai memakai HPP produk saat disimpan.
 * Per baris: Dr account biaya baris (frefdtno, subaccount frefso). Total: Cr PERSEDIAAN.
 * Qty bertanda negatif membalik arah baris tersebut.
 */
class JurnalPemakaianBarang
{
    private const JURNAL_TYPE = 'PBR';

    private const ACCOUNT_PERSEDIAAN = 'PERSEDIAAN';

    public static function sync(string $fstockmtno, Carbon $date, string $branchCode, string $userName): void
    {
        self::delete($fstockmtno);
        self::create($fstockmtno, $date, $branchCode, $userName);
    }

    public static function create(string $fstockmtno, Carbon $date, string $branchCode, string $userName): void
    {
        $fstockmtno = trim($fstockmtno);
        $rows = DB::table('trstockdt as d')
            ->leftJoin('msprd as p', 'p.fprdcode', '=', 'd.fprdcode')
            ->where('d.fstockmtno', $fstockmtno)
            ->get(['d.fqtykecil', 'd.frefdtno', 'd.frefso', 'p.fhpp']);

        $lines = [];
        $net = 0.0;
        foreach ($rows as $row) {
            $amount = round((float) $row->fqtykecil * (float) $row->fhpp, 2);
            if (abs($amount) < 0.005) {
                continue;
            }

            $account = trim((string) $row->frefdtno);
            if ($account === '') {
                throw ValidationException::withMessages(['frefdtno' => 'Account biaya wajib diisi untuk membuat jurnal pemakaian barang.']);
            }

            $lines[] = ['account' => $account, 'sub' => trim((string) $row->frefso) ?: null, 'dk' => $amount > 0 ? 'D' : 'K', 'amount' => abs($amount)];
            $net += $amount;
        }

        if ($lines === []) {
            return;
        }

        $net = round($net, 2);
        if (abs($net) >= 0.005) {
            $lines[] = ['account' => self::persediaan(), 'sub' => null, 'dk' => $net > 0 ? 'K' : 'D', 'amount' => abs($net)];
        }

        $balance = round(collect($lines)->where('dk', 'D')->sum('amount'), 2);
        $fjurnalno = 'JV.' . ltrim($fstockmtno, '.');
        $kodeCabang = trim($branchCode) !== '' ? trim($branchCode) : trim((string) (session('fcabang') ?: '01'));
        $userName = trim($userName) !== '' ? trim($userName) : 'System';
        $note = 'Pemakaian Barang ' . $fstockmtno;
        $now = now();

        $jurnalId = DB::table('jurnalmt')->insertGetId([
            'fbranchcode' => $kodeCabang,
            'fjurnalno' => $fjurnalno,
            'fjurnaltype' => self::JURNAL_TYPE,
            'fjurnaldate' => $date,
            'fjurnalnote' => $note,
            'fbalance' => $balance,
            'fbalance_rp' => $balance,
            'fdatetime' => $now,
            'fuserid' => $userName,
        ], 'fjurnalmtid');

        DB::table('jurnaldt')->insert(array_map(fn ($line, $i) => [
            'fjurnalmtid' => $jurnalId,
            'fbranchcode' => $kodeCabang,
            'fjurnaltype' => self::JURNAL_TYPE,
            'fjurnalno' => $fjurnalno,
            'flineno' => $i + 1,
            'faccount' => $line['account'],
            'fdk' => $line['dk'],
            'fsubaccount' => $line['sub'],
            'frefno' => $fstockmtno,
            'frate' => 1,
            'famount' => $line['amount'],
            'famount_rp' => $line['amount'],
            'faccountnote' => $note,
            'fusercreate' => $userName,
            'fdatetime' => $now,
        ], $lines, array_keys($lines)));
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

    private static function persediaan(): string
    {
        $faccount = trim((string) DB::table('set_account')->where('faccount_name', self::ACCOUNT_PERSEDIAAN)->value('faccount'));
        if ($faccount === '') {
            throw ValidationException::withMessages(['set_account' => "Kode akun untuk '" . self::ACCOUNT_PERSEDIAAN . "' belum diset pada tabel set_account."]);
        }

        return $faccount;
    }
}
