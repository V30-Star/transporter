<?php

namespace Tests\Http;

use Illuminate\Support\Facades\DB;
use Tests\Http\Concerns\MakesTestMaster;

abstract class KasHttpTestCase extends LiveDbTestCase
{
    use MakesTestMaster;

    /** Nama dasar route (penerimaankas / pengeluarankas). */
    protected string $route;

    /** Akhiran izin (PenerimaanKas / PengeluaranKas). */
    protected string $perm;

    /** Kode transaksi di nomor voucher (BKM / BKK). */
    protected string $tran;

    /** D/K header dan detail: penerimaan = D/K, pengeluaran = K/D. */
    protected string $headerDk = 'D';

    protected string $detailDk = 'K';

    private string $ket;

    private string $kas;

    private string $detail;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ket = 'ZZ_DUSK_' . $this->tran;
        $this->grantPermissions('view' . $this->perm, 'create' . $this->perm, 'update' . $this->perm, 'delete' . $this->perm);
        $this->kas = $this->makeCashAccount();
        $this->detail = $this->makeDetailAccount();
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'fkasmtdate' => now()->format('Y-m-d'),
            'faccountheader' => $this->kas,
            'fwhom' => 'ZZ PENYETOR',
            'fket' => $this->ket,
            'details' => [['faccount' => $this->detail, 'fnote' => 'ZZ CATATAN', 'fkasdtvalue' => 100000]],
        ], $override);
    }

    private function store(array $override = [])
    {
        return $this->atomic(fn () => $this->post(route($this->route . '.store'), $this->payload($override)));
    }

    private function header(?string $ket = null): ?object
    {
        return DB::table('trkasmt')->where('fket', $ket ?? $this->ket)->first();
    }

    private function details(int $headerId)
    {
        return DB::table('trkasdt')->where('fkasmtid', $headerId)->orderBy('fnou')->get();
    }

    public function test_crud_with_details(): void
    {
        $this->store();
        $h = $this->header();
        $this->assertNotNull($h, 'Voucher kas tidak tersimpan. ' . $this->lastFlash());
        $this->assertMatchesRegularExpression('/^' . $this->tran . '\.[A-Za-z0-9]+\.\d{4}\.[A-Z]{2}\.\d{4}$/', $h->fkasmtno, 'Format nomor voucher tidak sesuai: ' . $h->fkasmtno);
        $this->assertSame($this->kas, trim($h->faccountheader));
        $this->assertEquals(100000, $h->famountpay);
        $this->assertSame($this->headerDk, trim($h->fdkheader));

        $rows = $this->details($h->fkasmtid);
        $this->assertCount(1, $rows);
        $this->assertSame($this->detail, trim($rows[0]->faccount));
        $this->assertEquals(100000, $rows[0]->fkasdtvalue);
        $this->assertSame($this->detailDk, trim($rows[0]->fdk), 'Posisi D/K detail untuk nilai positif tidak sesuai.');
        $this->assertEquals(100000, $rows[0]->fjurnal);

        $this->get(route($this->route . '.view', $h->fkasmtno))->assertOk();
        $this->get(route($this->route . '.edit', $h->fkasmtno))->assertOk();

        $this->atomic(fn () => $this->patch(route($this->route . '.update', $h->fkasmtno), $this->payload([
            'details' => [
                ['faccount' => $this->detail, 'fnote' => 'ZZ BARIS 1', 'fkasdtvalue' => 150000],
                ['faccount' => $this->detail, 'fnote' => 'ZZ BARIS 2', 'fkasdtvalue' => 50000],
            ],
        ])));
        $updated = $this->header();
        $this->assertEquals(200000, $updated->famountpay, 'Total harus jumlah semua baris detail. ' . $this->lastFlash());
        $this->assertCount(2, $this->details($updated->fkasmtid));
        $this->assertTrue(DB::table('log_trkasmt')->where('fkasmtno', $h->fkasmtno)->where('feditmode', 'U')->exists(), 'Log update tidak tercatat.');

        $this->get(route($this->route . '.delete', $h->fkasmtno))->assertOk();
        $this->atomic(fn () => $this->deleteJson(route($this->route . '.destroy', $h->fkasmtno)));
        $this->assertNull($this->header(), 'Delete gagal. ' . $this->lastFlash());
        $this->assertCount(0, $this->details($h->fkasmtid), 'Detail harus ikut terhapus.');
        $this->assertTrue(DB::table('log_trkasmt')->where('fkasmtno', $h->fkasmtno)->where('feditmode', 'D')->exists(), 'Log delete tidak tercatat.');
    }

    public function test_number_increments_per_bank_and_month(): void
    {
        $this->store();
        $this->store(['fket' => $this->ket . '_2']);
        $first = $this->header();
        $second = $this->header($this->ket . '_2');
        $this->assertNotNull($first, $this->lastFlash());
        $this->assertNotNull($second, $this->lastFlash());

        $num = fn ($no) => (int) substr($no, strrpos($no, '.') + 1);
        $this->assertSame($num($first->fkasmtno) + 1, $num($second->fkasmtno), 'Nomor voucher harus berurutan.');
    }

    public function test_validation_fails(): void
    {
        $cases = [
            'tanggal kosong' => [['fkasmtdate' => ''], 'fkasmtdate'],
            'tanggal tidak valid' => [['fkasmtdate' => 'bukan-tanggal'], 'fkasmtdate'],
            'cabang tidak ada' => [['fbranchcode' => 'ZZ'], 'fbranchcode'],
            'keterangan lebih dari 50 karakter' => [['fket' => str_repeat('A', 51)], 'fket'],
            'tanpa detail' => [['details' => []], 'details'],
            'jumlah nol' => [['details' => [['faccount' => $this->detail, 'fkasdtvalue' => 0]]], 'details.0.fkasdtvalue'],
            'jumlah bukan angka' => [['details' => [['faccount' => $this->detail, 'fkasdtvalue' => 'abc']]], 'details.0.fkasdtvalue'],
            'account detail tidak ada' => [['details' => [['faccount' => 'ZZTIDAKADA', 'fkasdtvalue' => 1000]]], 'details.0.faccount'],
            'account header bukan kas/bank' => [['faccountheader' => $this->detail], 'faccountheader'],
            'account header tidak ada' => [['faccountheader' => 'ZZTIDAKADA'], 'faccountheader'],
            'sub account untuk account tanpa sub account' => [['details' => [['faccount' => $this->detail, 'fsubaccount' => 'XYZ', 'fkasdtvalue' => 1000]]], 'details.0.fsubaccount'],
        ];

        foreach ($cases as $label => [$override, $field]) {
            $this->store($override);
            $this->assertTrue($this->sessionHasError($field), "Kasus '$label': error '$field' tidak muncul. " . $this->lastFlash());
        }

        $this->assertNull($this->header(), 'Tidak boleh tersimpan saat validasi gagal.');
    }

    public function test_duplicate_voucher_number_is_rejected(): void
    {
        $this->store(['fkasmtno' => 'ZZ.DUSK.0001', 'auto_generate' => false]);
        $this->assertNotNull($this->header(), $this->lastFlash());

        $this->store(['fkasmtno' => 'ZZ.DUSK.0001', 'auto_generate' => false, 'fket' => $this->ket . '_DUP']);
        $this->assertTrue($this->sessionHasError('fkasmtno'), 'Nomor voucher ganda harus ditolak. ' . $this->lastFlash());
        $this->assertNull($this->header($this->ket . '_DUP'));
    }

    public function test_index_opens(): void
    {
        $this->get(route($this->route . '.index'))->assertOk();
    }
}
