<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JurnalFakturPenjualan
{
    private const JURNAL_TYPE = 'SLS';

    /**
     * Nama-nama akun (faccount_name) di tabel set_account.
     * Ini menggantikan konstanta MEMO_DEBIT_ACCOUNT / MEMO_CREDIT_ACCOUNT
     * yang sebelumnya hardcode kode akun. Sekarang kode akun (faccount)
     * diambil dari tabel set_account berdasarkan faccount_name berikut,
     * setara dengan properti cAccount_* pada versi Delphi.
     */
    private const ACCOUNT_JUALTUNAI          = 'PENJUALANTUNAI';
    private const ACCOUNT_PIUTANG            = 'PIUTANGDAGANG';
    private const ACCOUNT_UMSALES            = 'UANGMUKA';
    private const ACCOUNT_DISCSALES          = 'DISCPENJUALAN';
    private const ACCOUNT_SALDOAWAL          = 'SALDOAWAL';
    private const ACCOUNT_SALES              = 'PENJUALAN';
    private const ACCOUNT_PPNSALES           = 'PPNJUAL';
    private const ACCOUNT_SELISIHPEMBULATAN  = 'SELISIHPEMBULATAN';

    /** Cache kode akun per faccount_name supaya tidak query berulang dalam 1 request. */
    private static array $accountCodeCache = [];

    public static function sync(
        string $fsono,
        Carbon $fsodate,
        string $branchCode,
        string $customerCode,
        string $userName,
        bool $isCash = false,
        ?string $kodeFp = null
    ): void {
        self::delete($fsono);
        self::create($fsono, $fsodate, $branchCode, $customerCode, $userName, $isCash, $kodeFp);
    }

    public static function create(
        string $fsono,
        Carbon $fsodate,
        string $branchCode,
        string $customerCode,
        string $userName,
        bool $isCash = false,
        ?string $kodeFp = null
    ): void {
        $fsono = trim($fsono);
        if ($fsono === '') {
            throw ValidationException::withMessages([
                'fsono' => 'Nomor faktur penjualan wajib diisi untuk membuat jurnal.',
            ]);
        }

        // Header faktur (tranmt)
        $invoice = DB::table('tranmt')
            ->where('fsono', $fsono)
            ->first(['famountso', 'famountso_rp', 'frate', 'ftypesales']);

        if (! $invoice) {
            throw ValidationException::withMessages([
                'fsono' => 'Faktur penjualan tidak ditemukan untuk membuat jurnal.',
            ]);
        }

        $famountSO = round((float) ($invoice->famountso ?? 0), 2);
        $famountSORp = round((float) ($invoice->famountso_rp ?? 0), 2);
        $frate = (float) ($invoice->frate ?? 1);

        if ($famountSO <= 0 || $famountSORp <= 0) {
            throw ValidationException::withMessages([
                'famountso' => 'Total faktur penjualan harus lebih besar dari 0 untuk membuat jurnal.',
            ]);
        }

        // Ringkasan detail faktur (trandt) — setara query gabungan tranmt+trandt di versi Delphi
        $summary = DB::table('tranmt as m')
            ->join('trandt as d', 'm.fsono', '=', 'd.fsono')
            ->where('m.fsono', $fsono)
            ->selectRaw("
                MIN(ABS(m.famountpajak_rp)) as famountpajak_rp,
                SUM(CASE WHEN m.ftypesales = '0' AND d.fprdcode = 'UM' THEN ABS(d.fqty * d.fsalesnet) ELSE 0 END) as fkurangiuangmuka,
                SUM(CASE WHEN d.fprdcode <> 'UM' THEN d.fqty * d.fsalesnet ELSE 0 END) as ftotalpenjualan,
                CASE WHEN MIN(m.fincludeppn) = '1' THEN (MIN(m.fppnpersen) / 100) * MIN(m.fdiscount) ELSE MIN(m.fdiscount) END as fdiscount
            ")
            ->groupBy('m.fsono')
            ->first();

        $famountPajakRp = round((float) ($summary->famountpajak_rp ?? 0), 2);
        $fkurangiUangMuka = round((float) ($summary->fkurangiuangmuka ?? 0), 2);
        $ftotalPenjualan = round((float) ($summary->ftotalpenjualan ?? 0), 2);
        $fdiscount = round((float) ($summary->fdiscount ?? 0), 2);

        // Apakah ini transaksi saldo awal (perlakuan khusus akun penjualan)
        $lSaldoAwal = DB::table('trandt')
            ->where('fsono', $fsono)
            ->where('fprdcode', 'AWAL')
            ->exists();

        // Apakah baris penjualan ini sebenarnya uang muka
        $lUangMuka = (string) ($invoice->ftypesales ?? '') === '1';

        $kodeCabang = trim($branchCode) !== '' ? trim($branchCode) : trim((string) (session('fcabang') ?: '01'));
        $customerCode = trim($customerCode);
        $userName = trim($userName) !== '' ? trim($userName) : 'System';
        $subaccount = $customerCode !== '' ? $customerCode : null;
        $fjurnalno = self::generateJurnalNumber($kodeCabang, $fsodate);
        $now = now();
        $note = 'Memo Invoice ' . $fsono;

        $lineNo = 0;
        $lines = [];
        $debit = 0.0;
        $kredit = 0.0;

        // 1) Piutang / Jual Tunai (Debet)
        $lineNo++;
        $lines[] = [
            'fbranchcode' => $kodeCabang,
            'fjurnaltype' => self::JURNAL_TYPE,
            'fjurnalno' => $fjurnalno,
            'flineno' => $lineNo,
            'faccount' => self::accountCode($isCash ? self::ACCOUNT_JUALTUNAI : self::ACCOUNT_PIUTANG),
            'fdk' => 'D',
            'fsubaccount' => $subaccount,
            'frefno' => $fsono,
            'frate' => $frate,
            'famount' => $famountSO,
            'famount_rp' => $famountSORp,
            'faccountnote' => $note,
            'fusercreate' => $userName,
            'fdatetime' => $now,
        ];
        $debit += $famountSORp;

        // 2) Dikurangi uang muka (Debet)
        if ($fkurangiUangMuka > 0) {
            $lineNo++;
            $lines[] = [
                'fbranchcode' => $kodeCabang,
                'fjurnaltype' => self::JURNAL_TYPE,
                'fjurnalno' => $fjurnalno,
                'flineno' => $lineNo,
                'faccount' => self::accountCode(self::ACCOUNT_UMSALES),
                'fdk' => 'D',
                'fsubaccount' => $subaccount,
                'frefno' => $fsono,
                'frate' => 1,
                'famount' => $fkurangiUangMuka,
                'famount_rp' => $fkurangiUangMuka,
                'faccountnote' => $note,
                'fusercreate' => $userName,
                'fdatetime' => $now,
            ];
            $debit += $fkurangiUangMuka;
        }

        // 3) Discount penjualan (Debet)
        if ($fdiscount > 0) {
            $lineNo++;
            $lines[] = [
                'fbranchcode' => $kodeCabang,
                'fjurnaltype' => self::JURNAL_TYPE,
                'fjurnalno' => $fjurnalno,
                'flineno' => $lineNo,
                'faccount' => self::accountCode(self::ACCOUNT_DISCSALES),
                'fdk' => 'D',
                'fsubaccount' => null,
                'frefno' => null,
                'frate' => 1,
                'famount' => $fdiscount,
                'famount_rp' => $fdiscount,
                'faccountnote' => $note,
                'fusercreate' => $userName,
                'fdatetime' => $now,
            ];
            $debit += $fdiscount;
        }

        // 4) Penjualan / Uang Muka / Saldo Awal (Kredit)
        if ($ftotalPenjualan > 0) {
            $lineNo++;

            if ($lUangMuka) {
                $account = self::accountCode(self::ACCOUNT_UMSALES);
            } elseif ($lSaldoAwal) {
                $account = self::accountCode(self::ACCOUNT_SALDOAWAL);
            } else {
                $account = self::accountCode(self::ACCOUNT_SALES);
            }

            $lines[] = [
                'fbranchcode' => $kodeCabang,
                'fjurnaltype' => self::JURNAL_TYPE,
                'fjurnalno' => $fjurnalno,
                'flineno' => $lineNo,
                'faccount' => $account,
                'fdk' => 'K',
                'fsubaccount' => null,
                'frefno' => null,
                'frate' => 1,
                'famount' => $ftotalPenjualan,
                'famount_rp' => $ftotalPenjualan,
                'faccountnote' => $note,
                'fusercreate' => $userName,
                'fdatetime' => $now,
            ];
            $kredit += $ftotalPenjualan;
        }

        // 5) PPN (Kredit) — kecuali kode faktur pajak 070 (ditanggung negara / kawasan berikat)
        if ($famountPajakRp > 0 && trim((string) $kodeFp) !== '070') {
            $lineNo++;
            $lines[] = [
                'fbranchcode' => $kodeCabang,
                'fjurnaltype' => self::JURNAL_TYPE,
                'fjurnalno' => $fjurnalno,
                'flineno' => $lineNo,
                'faccount' => self::accountCode(self::ACCOUNT_PPNSALES),
                'fdk' => 'K',
                'fsubaccount' => null,
                'frefno' => null,
                'frate' => 1,
                'famount' => $famountPajakRp,
                'famount_rp' => $famountPajakRp,
                'faccountnote' => $note,
                'fusercreate' => $userName,
                'fdatetime' => $now,
            ];
            $kredit += $famountPajakRp;
        }

        // 6) Selisih pembulatan, jika debet <> kredit
        if (abs($debit - $kredit) > 0.01) {
            $lineNo++;
            $selisih = round(abs($debit - $kredit), 2);
            $lines[] = [
                'fbranchcode' => $kodeCabang,
                'fjurnaltype' => self::JURNAL_TYPE,
                'fjurnalno' => $fjurnalno,
                'flineno' => $lineNo,
                'faccount' => self::accountCode(self::ACCOUNT_SELISIHPEMBULATAN),
                'fdk' => $debit > $kredit ? 'K' : 'D',
                'fsubaccount' => null,
                'frefno' => null,
                'frate' => 1, // karena dirupiahkan
                'famount' => $selisih,
                'famount_rp' => $selisih,
                'faccountnote' => $note,
                'fusercreate' => $userName,
                'fdatetime' => $now,
            ];
            $debit > $kredit ? $kredit += $selisih : $debit += $selisih;
        }

        self::validateLines($lines);

        $jurnalId = DB::table('jurnalmt')->insertGetId([
            'fbranchcode' => $kodeCabang,
            'fjurnalno' => $fjurnalno,
            'fjurnaltype' => self::JURNAL_TYPE,
            'fjurnaldate' => $fsodate,
            'fjurnalnote' => 'Jurnal Faktur Penjualan ' . $fsono,
            'fbalance' => $debit,
            'fbalance_rp' => $debit,
            'fdatetime' => $now,
            'fuserid' => $userName,
        ], 'fjurnalmtid');

        foreach ($lines as &$line) {
            $line['fjurnalmtid'] = $jurnalId;
        }
        unset($line);

        DB::table('jurnaldt')->insert($lines);
    }

    public static function delete(string $fsono): void
    {
        $fsono = trim($fsono);
        if ($fsono === '') {
            return;
        }

        $existingJurnalIds = DB::table('jurnaldt')
            ->where('frefno', $fsono)
            ->where('fjurnaltype', self::JURNAL_TYPE)
            ->pluck('fjurnalmtid')
            ->filter()
            ->unique()
            ->values();

        if ($existingJurnalIds->isEmpty()) {
            return;
        }

        DB::table('jurnaldt')->whereIn('fjurnalmtid', $existingJurnalIds->all())->delete();
        DB::table('jurnalmt')->whereIn('fjurnalmtid', $existingJurnalIds->all())->delete();
    }

    /**
     * Ambil kode akun (kolom faccount) dari tabel set_account berdasarkan
     * faccount_name. Hasil di-cache per request supaya tidak query berulang
     * untuk nama akun yang sama.
     */
    private static function accountCode(string $accountName): string
    {
        if (array_key_exists($accountName, self::$accountCodeCache)) {
            return self::$accountCodeCache[$accountName];
        }

        $faccount = DB::table('set_account')
            ->where('faccount_name', $accountName)
            ->value('faccount');

        if ($faccount === null || trim((string) $faccount) === '') {
            throw ValidationException::withMessages([
                'set_account' => "Kode akun untuk '{$accountName}' belum diset pada tabel set_account.",
            ]);
        }

        return self::$accountCodeCache[$accountName] = trim((string) $faccount);
    }

    private static function generateJurnalNumber(string $kodeCabang, Carbon $fsodate): string
    {
        $prefix = sprintf('%s.%s.%s%s.', self::JURNAL_TYPE, $kodeCabang, $fsodate->format('y'), $fsodate->format('m'));

        if (DB::getDriverName() === 'pgsql') {
            $lockKey = crc32('JURNAL|' . self::JURNAL_TYPE . '|' . $kodeCabang . '|' . $fsodate->format('y-m'));
            DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

            $lastNo = DB::table('jurnalmt')
                ->where('fjurnalno', 'like', $prefix . '%')
                ->selectRaw("
                    MAX(
                        CASE 
                            WHEN split_part(fjurnalno, '.', 6) ~ '^[0-9]+$' 
                            THEN split_part(fjurnalno, '.', 6)::integer 
                            ELSE NULL 
                        END
                    ) AS lastno
                ")
                ->value('lastno');

            $nextNo = (int) $lastNo + 1;
        } else {
            $lastJurnalNo = DB::table('jurnalmt')
                ->where('fjurnalno', 'like', $prefix . '%')
                ->orderByDesc('fjurnalno')
                ->value('fjurnalno');

            $nextNo = 1;
            if ($lastJurnalNo && ($pos = strrpos($lastJurnalNo, '.')) !== false) {
                $nextNo = ((int) substr($lastJurnalNo, $pos + 1)) + 1;
            }
        }

        return $prefix . str_pad((string) $nextNo, 4, '0', STR_PAD_LEFT);
    }

    private static function validateLines(array $lines): void
    {
        if (count($lines) < 2) {
            throw ValidationException::withMessages([
                'jurnal' => 'Jurnal faktur penjualan minimal harus memiliki dua baris.',
            ]);
        }

        $debit = 0.0;
        $credit = 0.0;

        foreach ($lines as $line) {
            $account = trim((string) ($line['faccount'] ?? ''));
            $amount = round((float) ($line['famount'] ?? 0), 2);
            $amountRp = round((float) ($line['famount_rp'] ?? 0), 2);
            $dk = trim((string) ($line['fdk'] ?? ''));

            if ($account === '') {
                throw ValidationException::withMessages([
                    'jurnal' => 'Account jurnal faktur penjualan belum lengkap.',
                ]);
            }

            if ($amount <= 0 || $amountRp <= 0) {
                throw ValidationException::withMessages([
                    'jurnal' => 'Nominal jurnal faktur penjualan harus lebih besar dari 0.',
                ]);
            }

            if (! in_array($dk, ['D', 'K'], true)) {
                throw ValidationException::withMessages([
                    'jurnal' => 'Posisi D/K jurnal faktur penjualan tidak valid.',
                ]);
            }

            if ($dk === 'D') {
                $debit += $amountRp;
            } else {
                $credit += $amountRp;
            }
        }

        if (abs($debit - $credit) > 0.01) {
            throw ValidationException::withMessages([
                'jurnal' => 'Jurnal faktur penjualan tidak balance.',
            ]);
        }
    }
}