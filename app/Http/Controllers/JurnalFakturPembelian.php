<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JurnalFakturPembelian
{
    private const JURNAL_TYPE = 'JBL';

    /**
     * Nama-nama akun (faccname) di tabel account.
     * Setara properti cAccount_* pada versi Delphi.
     */
    private const ACCOUNT_HUTANG                  = 'HUTANG DAGANG';
    private const ACCOUNT_UMBUY                    = 'UANG MUKA PEMBELIAN';
    private const ACCOUNT_SALDOAWAL                 = 'SALDO AWAL';
    private const ACCOUNT_PEMBELIAN                 = 'PEMBELIAN';
    private const ACCOUNT_FAKTURBELIYGBLMDITAGIH    = 'PENERIMAAN PRODUK BELUM DITAGIH';
    private const ACCOUNT_PPNBELI                   = 'PPN';
    private const ACCOUNT_SELISIHBAYAR              = 'SELISIH HARGA JUAL';

    /** Trancode index — setara cbftrancode.ItemIndex di Delphi */
    private const TRANCODE_STOK    = 0; // persediaan normal
    private const TRANCODE_NONSTOK = 1; // non stok, akun diambil dari $toAccount
    private const TRANCODE_UM      = 2; // uang muka pembelian

    /** Cache kode akun per (faccname|currency) supaya tidak query berulang dalam 1 request. */
    private static array $accountCodeCache = [];

    public static function sync(
        string $fstockmtno,
        Carbon $fstockmtdate,
        string $branchCode,
        string $supplierCode,
        string $userName,
        int $trancodeIndex = self::TRANCODE_STOK,
        string $currency = 'IDR',
        ?string $toAccount = null,
        ?string $rateText = null
    ): void {
        self::delete($fstockmtno);
        self::create(
            $fstockmtno,
            $fstockmtdate,
            $branchCode,
            $supplierCode,
            $userName,
            $trancodeIndex,
            $currency,
            $toAccount,
            $rateText
        );
    }

    public static function create(
        string $fstockmtno,
        Carbon $fstockmtdate,
        string $branchCode,
        string $supplierCode,
        string $userName,
        int $trancodeIndex = self::TRANCODE_STOK,
        string $currency = 'IDR',
        ?string $toAccount = null,
        ?string $rateText = null
    ): void {
        $fstockmtno = trim($fstockmtno);
        if ($fstockmtno === '') {
            throw ValidationException::withMessages([
                'fstockmtno' => 'Nomor stock movement / faktur pembelian wajib diisi untuk membuat jurnal.',
            ]);
        }

        // Header transaksi pembelian (trstockmt)
        $header = DB::table('trstockmt')
            ->whereRaw('trim(fstockmtno) = ?', [$fstockmtno])
            ->first(['famountmt', 'famountmt_rp', 'frate', 'famountpajak', 'famountpajak_rp', 'fapplyppn']);

        if (! $header) {
            throw ValidationException::withMessages([
                'fstockmtno' => 'Transaksi pembelian tidak ditemukan untuk membuat jurnal.',
            ]);
        }

        $fapplyPPN = (int) ($header->fapplyppn ?? 0);
        $famountMT = round((float) ($header->famountmt ?? 0), 2);
        $famountMTRp = round((float) ($header->famountmt_rp ?? 0), 2);
        $frate = (float) ($header->frate ?? 1);
        $famountPajakRp = ($fapplyPPN === 1) ? round((float) ($header->famountpajak_rp ?? 0), 2) : 0;

        if ($famountMT <= 0 || $famountMTRp <= 0) {
            throw ValidationException::withMessages([
                'famountmt' => 'Total transaksi pembelian harus lebih besar dari 0 untuk membuat jurnal.',
            ]);
        }

        // Apakah ini transaksi saldo awal (perlakuan khusus akun persediaan)
        $lSaldoAwal = DB::table('trstockdt')
            ->where('fstockmtno', $fstockmtno)
            ->where('fprdcode', 'AWAL')
            ->exists();

        // Faktur yang belum ditagih (fcode = 'T') — utk jurnal balik
        $nFakturYgBelumDitagih = 0.0;
        if (! $lSaldoAwal) {
            $nFakturYgBelumDitagih = round((float) (
                DB::table('trstockdt')
                ->where('fstockmtno', $fstockmtno)
                ->where('fcode', 'T')
                ->sum('ftotprice_rp')
            ), 2);
        }

        // Persediaan / Non Stok / Uang Muka
        if ($trancodeIndex === self::TRANCODE_UM) {
            // Untuk Uang Muka Pembelian (ftypebuy = 2), ambil total harga seluruh item detail
            $nPersediaan = round((float) DB::table('trstockdt')
                ->where('fstockmtno', $fstockmtno)
                ->sum('ftotprice_rp'), 2);

            // Fallback ke nominal header (famountmt_rp) jika detail belum terisi
            if ($nPersediaan <= 0) {
                $nPersediaan = $famountMTRp;
            }
        } elseif ($lSaldoAwal) {
            // Jika Saldo Awal, hitung seluruh nominal tanpa membedakan fcode
            $nPersediaan = round((float) DB::table('trstockdt')
                ->where('fstockmtno', $fstockmtno)
                ->sum('ftotprice_rp'), 2);

            // Fallback jika detail kosong
            if ($nPersediaan <= 0) {
                $nPersediaan = $famountMTRp;
            }
        } else {
            // Transaksi Normal / Non Stok
            $nPersediaan = round((float) DB::table('trstockdt')
                ->where('fstockmtno', $fstockmtno)
                ->where('fprdcode', '<>', 'UM')
                ->where(function ($q) {
                    $q->where('fcode', 'P')->orWhereNull('fcode')->orWhere('fcode', '');
                })
                ->sum('ftotprice_rp'), 2);
        }

        // Kurangi uang muka (hanya berlaku jika BUKAN transaksi Uang Muka Pembelian)
        $nKurangiUangMuka = 0.0;
        if ($trancodeIndex !== self::TRANCODE_UM) {
            $nKurangiUangMuka = round((float) (
                DB::table('trstockdt')
                ->where('fstockmtno', $fstockmtno)
                ->where('fprdcode', 'UM')
                ->selectRaw('SUM(ABS(ftotprice_rp)) as total')
                ->value('total')
            ), 2);
        }

        $kodeCabang = trim($branchCode) !== '' ? trim($branchCode) : trim((string) (session('fcabang') ?: '01'));
        $supplierCode = trim($supplierCode);
        $userName = trim($userName) !== '' ? trim($userName) : 'System';
        $subaccount = $supplierCode !== '' ? $supplierCode : null;
        $currency = trim($currency) !== '' ? trim($currency) : 'IDR';
        $fjurnalno = self::generateJurnalNumber($kodeCabang, $fstockmtdate);
        $now = now();
        $note = 'Jurnal Pembelian ' . $fstockmtno;
        $noteWithRate = $currency !== 'IDR' && $rateText
            ? $note . ' Rate: ' . trim($rateText)
            : $note;

        $lineNo = 0;
        $lines = [];
        $debit = 0.0;
        $kredit = 0.0;

        // 1) Persediaan / Non Stok / Uang Muka (Debet)
        if ($nPersediaan > 0) {
            $lineNo++;

            if ($lSaldoAwal) {
                // Jika mengandung produk 'AWAL', paksa akun ke SALDOAWAL
                $account = self::accountCode(self::ACCOUNT_SALDOAWAL);
            } elseif ($trancodeIndex === self::TRANCODE_NONSTOK) {
                $account = trim((string) $toAccount);
                if ($account === '') {
                    throw ValidationException::withMessages([
                        'toAccount' => 'Akun tujuan (non stok) wajib diisi untuk transaksi non stok.',
                    ]);
                }
            } elseif ($trancodeIndex === self::TRANCODE_UM) {
                $account = self::accountCode(self::ACCOUNT_UMBUY);
            } else {
                $account = self::accountCode(self::ACCOUNT_PEMBELIAN);
            }

            $lines[] = [
                'fbranchcode' => $kodeCabang,
                'fjurnaltype' => self::JURNAL_TYPE,
                'fjurnalno' => $fjurnalno,
                'flineno' => $lineNo,
                'faccount' => $account,
                'fdk' => 'D',
                'fsubaccount' => null,
                'frefno' => null,
                'frate' => 1, // karena persediaan currency IDR
                'famount' => $nPersediaan,
                'famount_rp' => $nPersediaan,
                'faccountnote' => $note,
                'fusercreate' => $userName,
                'fdatetime' => $now,
            ];
            $debit += $nPersediaan;
        }

        // 2) Faktur yang belum ditagih (Debet)
        if ($nFakturYgBelumDitagih > 0) {
            $lineNo++;
            $lines[] = [
                'fbranchcode' => $kodeCabang,
                'fjurnaltype' => self::JURNAL_TYPE,
                'fjurnalno' => $fjurnalno,
                'flineno' => $lineNo,
                'faccount' => self::accountCode(self::ACCOUNT_FAKTURBELIYGBLMDITAGIH),
                'fdk' => 'D',
                'fsubaccount' => null,
                'frefno' => null,
                'frate' => 1,
                'famount' => $nFakturYgBelumDitagih,
                'famount_rp' => $nFakturYgBelumDitagih,
                'faccountnote' => $note,
                'fusercreate' => $userName,
                'fdatetime' => $now,
            ];
            $debit += $nFakturYgBelumDitagih;
        }

        // 3) PPN — dimasukkan ke HPP (Debet)
        if ($famountPajakRp > 0) {
            $lineNo++;
            $lines[] = [
                'fbranchcode' => $kodeCabang,
                'fjurnaltype' => self::JURNAL_TYPE,
                'fjurnalno' => $fjurnalno,
                'flineno' => $lineNo,
                'faccount' => self::accountCode(self::ACCOUNT_PPNBELI),
                'fdk' => 'D',
                'fsubaccount' => null,
                'frefno' => null,
                'frate' => 1, // karena currency IDR
                'famount' => $famountPajakRp,
                'famount_rp' => $famountPajakRp,
                'faccountnote' => $noteWithRate,
                'fusercreate' => $userName,
                'fdatetime' => $now,
            ];
            $debit += $famountPajakRp;
        }

        // 4) Kurangi uang muka (Kredit)
        if ($nKurangiUangMuka > 0) {
            $lineNo++;
            $lines[] = [
                'fbranchcode' => $kodeCabang,
                'fjurnaltype' => self::JURNAL_TYPE,
                'fjurnalno' => $fjurnalno,
                'flineno' => $lineNo,
                'faccount' => self::accountCode(self::ACCOUNT_UMBUY),
                'fdk' => 'K',
                'fsubaccount' => $subaccount,
                'frefno' => $fstockmtno,
                'frate' => 1, // karena currency IDR
                'famount' => $nKurangiUangMuka,
                'famount_rp' => $nKurangiUangMuka,
                'faccountnote' => $note,
                'fusercreate' => $userName,
                'fdatetime' => $now,
            ];
            $kredit += $nKurangiUangMuka;
        }

        // 5) Hutang Dagang (Kredit) — akun dicari per currency, fallback ke akun default
        $lineNo++;
        $lines[] = [
            'fbranchcode' => $kodeCabang,
            'fjurnaltype' => self::JURNAL_TYPE,
            'fjurnalno' => $fjurnalno,
            'flineno' => $lineNo,
            'faccount' => self::accountCode(self::ACCOUNT_HUTANG, $currency),
            'fdk' => 'K',
            'fsubaccount' => $subaccount,
            'frefno' => $fstockmtno,
            'frate' => $frate,
            'famount' => $famountMT,
            'famount_rp' => $famountMTRp,
            'faccountnote' => $noteWithRate,
            'fusercreate' => $userName,
            'fdatetime' => $now,
        ];
        $kredit += $famountMTRp;

        // 6) Selisih bayar, jika debet <> kredit
        if (abs($debit - $kredit) > 0.01) {
            $lineNo++;
            $selisih = round(abs($debit - $kredit), 2);
            $lines[] = [
                'fbranchcode' => $kodeCabang,
                'fjurnaltype' => self::JURNAL_TYPE,
                'fjurnalno' => $fjurnalno,
                'flineno' => $lineNo,
                'faccount' => self::accountCode(self::ACCOUNT_SELISIHBAYAR),
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
            'fjurnaldate' => $fstockmtdate,
            'fjurnalnote' => $note,
            'fbalance' => $famountMT,
            'fbalance_rp' => $famountMTRp,
            'fdatetime' => $now,
            'fuserid' => $userName,
        ], 'fjurnalmtid');

        foreach ($lines as &$line) {
            $line['fjurnalmtid'] = $jurnalId;
        }
        unset($line);

        DB::table('jurnaldt')->insert($lines);
    }

    public static function delete(string $fstockmtno): void
    {
        $fstockmtno = trim($fstockmtno);
        if ($fstockmtno === '') {
            return;
        }

        $existingJurnalIds = DB::table('jurnaldt')
            ->whereRaw('trim(frefno) = ?', [$fstockmtno])
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
     * Ambil kode akun (kolom faccount) dari tabel account berdasarkan
     * faccname. Jika $currency diisi, coba cari akun spesifik untuk
     * currency tsb terlebih dahulu (mis. hutang dagang per mata uang),
     * lalu fallback ke akun default (tanpa filter currency) jika tidak ada.
     * Hasil di-cache per request.
     */
    private static function accountCode(string $accountName, ?string $currency = null): string
    {
        $cacheKey = $accountName . '|' . ($currency ?? '');
        if (array_key_exists($cacheKey, self::$accountCodeCache)) {
            return self::$accountCodeCache[$cacheKey];
        }

        $faccount = null;

        if ($currency !== null && $currency !== '' && $currency !== 'IDR') {
            $faccount = DB::table('account')
                ->where('faccname', $accountName)
                ->where('fcurrency', $currency)
                ->value('faccount');
        }

        if ($faccount === null) {
            $faccount = DB::table('account')
                ->where('faccname', $accountName)
                ->value('faccount');
        }

        if ($faccount === null || trim((string) $faccount) === '') {
            throw ValidationException::withMessages([
                'account' => "Kode akun untuk '{$accountName}' belum diset pada tabel account.",
            ]);
        }

        return self::$accountCodeCache[$cacheKey] = trim((string) $faccount);
    }

    private static function generateJurnalNumber(string $kodeCabang, Carbon $fstockmtdate): string
    {
        $prefix = sprintf('%s.%s.%s%s.', self::JURNAL_TYPE, $kodeCabang, $fstockmtdate->format('y'), $fstockmtdate->format('m'));

        if (DB::getDriverName() === 'pgsql') {
            $lockKey = crc32('JURNAL|' . self::JURNAL_TYPE . '|' . $kodeCabang . '|' . $fstockmtdate->format('y-m'));
            DB::statement('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

            $lastNo = DB::table('jurnalmt')
                ->where('fjurnalno', 'like', $prefix . '%')
                ->selectRaw("MAX(CAST(split_part(fjurnalno, '.', 4) AS int)) AS lastno")
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
                'jurnal' => 'Jurnal faktur pembelian minimal harus memiliki dua baris.',
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
                    'jurnal' => 'Account jurnal faktur pembelian belum lengkap.',
                ]);
            }

            if ($amount <= 0 || $amountRp <= 0) {
                throw ValidationException::withMessages([
                    'jurnal' => 'Nominal jurnal faktur pembelian harus lebih besar dari 0.',
                ]);
            }

            if (! in_array($dk, ['D', 'K'], true)) {
                throw ValidationException::withMessages([
                    'jurnal' => 'Posisi D/K jurnal faktur pembelian tidak valid.',
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
                'jurnal' => 'Jurnal faktur pembelian tidak balance.',
            ]);
        }
    }
}
