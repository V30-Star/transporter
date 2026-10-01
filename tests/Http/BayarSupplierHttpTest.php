<?php

namespace Tests\Http;

use Illuminate\Support\Facades\DB;
use Tests\Http\Concerns\MakesPurchaseChain;

class BayarSupplierHttpTest extends LiveDbTestCase
{
    use MakesPurchaseChain;

    private const KET = 'ZZ_DUSK_PAY';

    private string $kas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->grantPermissions('viewBayarSupplier', 'createBayarSupplier', 'updateBayarSupplier', 'deleteBayarSupplier');
        $this->setUpPurchaseChain();
        $this->kas = $this->makeCashAccount();
    }

    private function detail(array $override = []): array
    {
        return array_merge([
            'frefno' => $this->buy->fstockmtno,
            'fnilai_order' => 30000,
            'fsisa_hutang' => 30000,
            'fdiscpersen' => 0,
            'fdiscount' => 0,
            'fkasdtvalue' => 30000,
        ], $override);
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'fkasmtdate' => now()->format('Y-m-d'),
            'fsupplier' => $this->supplier,
            'faccountheader' => $this->kas,
            'fket' => self::KET,
            'details' => [$this->detail()],
        ], $override);
    }

    private function store(array $override = [])
    {
        return $this->atomic(fn () => $this->post(route('bayarsupplier.store'), $this->payload($override)));
    }

    private function header(string $ket = self::KET): ?object
    {
        return DB::table('trkasmt')->where('ftrancode', 'PAY')->where('fket', $ket)->first();
    }

    private function details(int $headerId)
    {
        return DB::table('trkasdt')->where('fkasmtid', $headerId)->orderBy('fnou')->get();
    }

    private function remain(): float
    {
        return (float) DB::table('trstockmt')->where('fstockmtid', $this->buy->fstockmtid)->value('famountremain');
    }

    public function test_crud_paying_an_invoice_in_full(): void
    {
        $this->assertEquals(30000, $this->remain(), 'Sisa hutang faktur awal harus sama dengan total faktur.');

        $this->store();
        $h = $this->header();
        $this->assertNotNull($h, 'Bayar supplier tidak tersimpan. ' . $this->lastFlash());
        $this->assertSame($this->kas, trim($h->faccountheader));
        $this->assertEquals(30000, $h->famountpay);
        $this->assertSame('K', trim($h->fdkheader), 'Kas keluar: header K.');

        $rows = $this->details($h->fkasmtid);
        $this->assertCount(1, $rows);
        $this->assertSame($this->buy->fstockmtno, trim($rows[0]->frefno));
        $this->assertSame('D', trim($rows[0]->fdk), 'Pelunasan hutang: detail D (mengurangi hutang).');
        $this->assertEquals(30000, $rows[0]->fkasdtvalue);
        $this->assertEquals(0, $this->remain(), 'Sisa hutang faktur harus 0 setelah dibayar penuh.');

        $this->get(route('bayarsupplier.view', $h->fkasmtno))->assertOk();
        $this->get(route('bayarsupplier.edit', $h->fkasmtno))->assertOk();

        $this->atomic(fn () => $this->patch(route('bayarsupplier.update', $h->fkasmtno), $this->payload([
            'details' => [$this->detail(['fkasdtvalue' => 20000, 'fsisa_hutang' => 30000])],
        ])));
        $this->assertEquals(20000, $this->header()->famountpay ?? null, 'Update total bayar gagal. ' . $this->lastFlash());
        $this->assertEquals(10000, $this->remain(), 'Sisa hutang harus dihitung ulang saat update.');

        $this->get(route('bayarsupplier.delete', $h->fkasmtno))->assertOk();
        $this->atomic(fn () => $this->deleteJson(route('bayarsupplier.destroy', $h->fkasmtno)));
        $this->assertNull($this->header(), 'Delete gagal. ' . $this->lastFlash());
        $this->assertCount(0, $this->details($h->fkasmtid));
        $this->assertEquals(30000, $this->remain(), 'Sisa hutang harus kembali penuh setelah pembayaran dihapus.');
        $this->assertTrue(DB::table('log_trkasmt')->where('fkasmtno', $h->fkasmtno)->where('feditmode', 'D')->exists(), 'Log delete tidak tercatat.');
    }

    public function test_voucher_number_carries_the_bank_initial(): void
    {
        $this->store();
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());
        $this->assertMatchesRegularExpression('/^PAY\.[A-Za-z0-9]+\.\d{4}\.[A-Z]{2}\.\d{4}$/', $h->fkasmtno, 'Nomor voucher harus memuat inisial bank dari account kas, bukan 00: ' . $h->fkasmtno);
    }

    public function test_discount_counts_as_settlement(): void
    {
        $this->store(['details' => [$this->detail(['fkasdtvalue' => 28000, 'fdiscount' => 2000])]]);
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());
        $this->assertEquals(28000, $h->famountpay, 'Kas keluar hanya sebesar yang dibayar, tanpa diskon.');
        $this->assertEquals(0, $this->remain(), 'Bayar 28.000 + diskon 2.000 melunasi faktur 30.000.');
    }

    public function test_bank_admin_fee_is_added_to_cash_out(): void
    {
        $biaya = $this->makeDetailAccount('ZZDUSKB1', 'D');
        $this->store(['fbiayaadminbank' => 2500, 'faccountadmin' => $biaya]);
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());
        $this->assertEquals(32500, $h->famountpay, 'Kas keluar = pembayaran + biaya admin bank.');
        $rows = $this->details($h->fkasmtid);
        $this->assertCount(2, $rows, 'Detail pembayaran + satu baris biaya admin.');
        $adm = $rows->first(fn ($r) => trim((string) $r->freftype) === 'ADM');
        $this->assertNotNull($adm);
        $this->assertSame($biaya, trim($adm->faccount));
        $this->assertEquals(2500, $adm->fkasdtvalue);
        $this->assertEquals(0, $this->remain(), 'Biaya admin tidak boleh mengurangi sisa hutang faktur.');
    }

    public function test_payment_cannot_exceed_the_remaining_payable(): void
    {
        $this->store(['details' => [$this->detail(['fkasdtvalue' => 30001])]]);
        $this->assertNull($this->header(), 'Pembayaran melebihi sisa hutang harus ditolak.');
        $this->assertTrue($this->sessionHasError('details.0.fkasdtvalue'), $this->lastFlash());
        $this->assertEquals(30000, $this->remain(), 'Sisa hutang tidak boleh berubah.');
    }

    public function test_partial_payment_leaves_the_balance_payable_later(): void
    {
        $this->store(['details' => [$this->detail(['fkasdtvalue' => 20000])]]);
        $this->assertNotNull($this->header(), $this->lastFlash());
        $this->assertEquals(10000, $this->remain());

        // Faktur masih muncul di daftar pilih (sisa > 0), jadi sisanya harus bisa dibayar dokumen berikutnya.
        $this->store(['fket' => self::KET . '_2', 'details' => [$this->detail(['fkasdtvalue' => 10000, 'fsisa_hutang' => 10000])]]);
        $this->assertNotNull($this->header(self::KET . '_2'), 'Sisa hutang 10.000 harus bisa dibayar di dokumen berikutnya. ' . $this->lastFlash());
        $this->assertEquals(0, $this->remain());
    }

    public function test_installments_cannot_exceed_the_remaining_payable(): void
    {
        $this->store(['details' => [$this->detail(['fkasdtvalue' => 20000])]]);
        $first = $this->header();
        $this->assertNotNull($first, $this->lastFlash());

        $this->store(['fket' => self::KET . '_2', 'details' => [$this->detail(['fkasdtvalue' => 15000, 'fsisa_hutang' => 10000])]]);
        $this->assertNull($this->header(self::KET . '_2'), 'Cicilan 15.000 melebihi sisa 10.000 harus ditolak.');
        $this->assertTrue($this->sessionHasError('details.0.fkasdtvalue'), $this->lastFlash());
        $this->assertEquals(10000, $this->remain());

        // Mengedit cicilan pertama tidak menghitung nilainya sendiri: 20.000 -> 30.000 (penuh) boleh.
        $this->atomic(fn () => $this->patch(route('bayarsupplier.update', $first->fkasmtno), $this->payload([
            'details' => [$this->detail(['fkasdtvalue' => 30000])],
        ])));
        $this->assertEquals(30000, $this->header()->famountpay ?? null, 'Edit cicilan sampai penuh harus diterima. ' . $this->lastFlash());
        $this->assertEquals(0, $this->remain());
    }

    public function test_the_payment_account_must_be_a_cash_or_bank_account(): void
    {
        $bukanKas = $this->makeDetailAccount('ZZDUSKD2', 'K');
        $this->store(['faccountheader' => $bukanKas]);
        $this->assertNull($this->header(), 'Pembayaran supplier tidak boleh keluar dari account yang bukan kas/bank.');
    }

    public function test_the_invoice_must_belong_to_the_selected_supplier(): void
    {
        $lain = $this->makeSupplier('ZZDUSKS2');
        $this->store(['fsupplier' => $lain]);
        $this->assertNull($this->header(), 'Nota supplier lain tidak boleh dibayar.');
        $this->assertTrue($this->sessionHasError('details.0.frefno'), $this->lastFlash());
    }

    public function test_the_paid_invoice_is_locked_for_edit_and_delete(): void
    {
        $this->store();
        $this->assertNotNull($this->header(), $this->lastFlash());

        $this->atomic(fn () => $this->deleteJson(route('fakturpembelian.destroy', $this->buy->fstockmtid)))->assertStatus(422);
        $this->assertTrue(DB::table('trstockmt')->where('fstockmtid', $this->buy->fstockmtid)->exists(), 'Faktur yang sudah dibayar tidak boleh terhapus.');
    }

    public function test_validation_fails(): void
    {
        $cases = [
            'tanggal kosong' => [['fkasmtdate' => ''], 'fkasmtdate'],
            'tanggal tidak valid' => [['fkasmtdate' => 'bukan-tanggal'], 'fkasmtdate'],
            'supplier kosong' => [['fsupplier' => ''], 'fsupplier'],
            'supplier tidak ada' => [['fsupplier' => 'ZZTIDAKADA'], 'fsupplier'],
            'account kosong' => [['faccountheader' => ''], 'faccountheader'],
            'keterangan lebih dari 50 karakter' => [['fket' => str_repeat('A', 51)], 'fket'],
            'tanpa faktur' => [['details' => []], 'details'],
            'faktur tidak ada' => [['details' => [$this->detail(['frefno' => 'BUY/ZZ/0000/0000'])]], 'details.0.frefno'],
            'jumlah nol' => [['details' => [$this->detail(['fkasdtvalue' => 0])]], 'details.0.fkasdtvalue'],
            'jumlah bukan angka' => [['details' => [$this->detail(['fkasdtvalue' => 'abc'])]], 'details.0.fkasdtvalue'],
            'diskon di atas 100 persen' => [['details' => [$this->detail(['fdiscpersen' => 150])]], 'details.0.fdiscpersen'],
            'biaya admin tanpa account' => [['fbiayaadminbank' => 1000], 'faccountadmin'],
            'giro mundur tanpa jatuh tempo' => [['fgiromundur' => 1], 'ftgljatuhtempo'],
        ];

        foreach ($cases as $label => [$override, $field]) {
            $this->store($override);
            $this->assertTrue($this->sessionHasError($field), "Kasus '$label': error '$field' tidak muncul. " . $this->lastFlash());
        }

        $this->assertNull($this->header(), 'Tidak boleh tersimpan saat validasi gagal.');
        $this->assertEquals(30000, $this->remain(), 'Sisa hutang tidak boleh berubah.');
    }

    public function test_index_opens(): void
    {
        $this->get(route('bayarsupplier.index'))->assertOk();
    }
}
