<?php

namespace Tests\Http;

use Illuminate\Support\Facades\DB;
use Tests\Http\Concerns\MakesSalesChain;

class PelunasanCustomerHttpTest extends LiveDbTestCase
{
    use MakesSalesChain;

    private const KET = 'ZZ_DUSK_RCP';

    private string $kas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->grantPermissions('viewPelunasanCustomer', 'createPelunasanCustomer', 'updatePelunasanCustomer', 'deletePelunasanCustomer');
        $this->setUpSalesChain();
        $this->kas = $this->makeCashAccount();
    }

    private function detail(array $override = []): array
    {
        return array_merge([
            'frefno' => $this->inv->fsono,
            'ftrcode' => 'INV',
            'fnilai_nota' => 30000,
            'fsisa_piutang' => 30000,
            'original_sisa' => 30000,
            'fdiscpersen' => 0,
            'fdiscount' => 0,
            'fkasdtvalue' => 30000,
        ], $override);
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'fkasmtdate' => now()->format('Y-m-d'),
            'fcustomer' => $this->customer,
            'faccountheader' => $this->kas,
            'fket' => self::KET,
            'details' => [$this->detail()],
        ], $override);
    }

    private function store(array $override = [])
    {
        return $this->atomic(fn () => $this->post(route('pelunasancustomer.store'), $this->payload($override)));
    }

    private function header(string $ket = self::KET): ?object
    {
        return DB::table('trkasmt')->where('ftrancode', 'RCP')->where('fket', $ket)->first();
    }

    private function details(int $headerId)
    {
        return DB::table('trkasdt')->where('fkasmtid', $headerId)->orderBy('fnou')->get();
    }

    private function remain(): float
    {
        return (float) DB::table('tranmt')->where('ftranmtid', $this->inv->ftranmtid)->value('famountremain');
    }

    public function test_crud_collecting_an_invoice_in_full(): void
    {
        $this->assertEquals(30000, $this->remain(), 'Piutang faktur awal sama dengan total faktur.');

        $this->store();
        $h = $this->header();
        $this->assertNotNull($h, 'Pelunasan customer tidak tersimpan. ' . $this->lastFlash());
        $this->assertSame($this->kas, trim($h->faccountheader));
        $this->assertEquals(30000, $h->famountpay);
        $this->assertSame('D', trim($h->fdkheader), 'Kas masuk: header D.');

        $rows = $this->details($h->fkasmtid);
        $this->assertCount(1, $rows);
        $this->assertSame($this->inv->fsono, trim($rows[0]->frefno));
        $this->assertSame('K', trim($rows[0]->fdk), 'Pelunasan piutang: detail K (mengurangi piutang).');
        $this->assertEquals(30000, $rows[0]->fkasdtvalue);
        $this->assertEquals(0, $this->remain(), 'Piutang faktur harus 0 setelah dilunasi.');

        $this->get(route('pelunasancustomer.view', $h->fkasmtno))->assertOk();
        $this->get(route('pelunasancustomer.edit', $h->fkasmtno))->assertOk();

        $this->atomic(fn () => $this->patch(route('pelunasancustomer.update', $h->fkasmtno), $this->payload([
            'details' => [$this->detail(['fkasdtvalue' => 20000])],
        ])));
        $this->assertEquals(20000, $this->header()->famountpay ?? null, 'Update total terima gagal. ' . $this->lastFlash());
        $this->assertEquals(10000, $this->remain(), 'Piutang harus dihitung ulang saat update.');

        $this->get(route('pelunasancustomer.delete', $h->fkasmtno))->assertOk();
        $this->atomic(fn () => $this->deleteJson(route('pelunasancustomer.destroy', $h->fkasmtno)));
        $this->assertNull($this->header(), 'Delete gagal. ' . $this->lastFlash());
        $this->assertCount(0, $this->details($h->fkasmtid));
        $this->assertEquals(30000, $this->remain(), 'Piutang harus kembali penuh setelah pelunasan dihapus.');
        $this->assertTrue(DB::table('log_trkasmt')->where('fkasmtno', $h->fkasmtno)->where('feditmode', 'D')->exists(), 'Log delete tidak tercatat.');
    }

    public function test_voucher_number_carries_the_bank_initial(): void
    {
        $this->store();
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());
        $this->assertMatchesRegularExpression('/^RCP\.[A-Za-z0-9]+\.\d{4}\.[A-Z]{2}\.\d{4}$/', $h->fkasmtno, 'Nomor voucher harus memuat inisial bank dari account kas, bukan 00: ' . $h->fkasmtno);
    }

    public function test_discount_percent_counts_as_settlement(): void
    {
        $this->store(['details' => [$this->detail(['fdiscpersen' => 10, 'fkasdtvalue' => 27000])]]);
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());
        $this->assertEquals(27000, $h->famountpay, 'Kas masuk hanya sebesar yang dibayar, tanpa diskon.');
        $this->assertEquals(0, $this->remain(), 'Bayar 27.000 + diskon 10% (3.000) melunasi faktur 30.000.');
    }

    public function test_bank_admin_fee_is_deducted_from_the_cash_received(): void
    {
        $biaya = $this->makeDetailAccount('ZZDUSKB1', 'D');
        $this->store(['fbiayaadminbank' => 2500, 'faccountadmin' => $biaya]);
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());
        $this->assertEquals(27500, $h->famountpay, 'Kas masuk bersih = pembayaran 30.000 - biaya admin bank 2.500.');
        $adm = $this->details($h->fkasmtid)->first(fn ($r) => trim((string) $r->freftype) === 'ADM');
        $this->assertNotNull($adm, 'Biaya admin bank harus tercatat sebagai baris ADM.');
        $this->assertSame($biaya, trim($adm->faccount));
        $this->assertEquals(2500, $adm->fkasdtvalue);
        $this->assertEquals(0, $this->remain(), 'Biaya admin tidak boleh mengurangi pelunasan piutang.');
    }

    public function test_extra_admin_charge_is_deducted_and_kept_on_the_header(): void
    {
        $biaya = $this->makeDetailAccount('ZZDUSKB1', 'D');
        $this->store(['fhargaadmin' => 1500, 'faccountadmin' => $biaya]);
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());
        $this->assertEquals(28500, $h->famountpay, 'Kas masuk bersih = 30.000 - biaya tambahan 1.500.');
        $this->assertEquals(1500, $h->fadjustment);
        $this->assertSame($biaya, trim((string) $h->faccadj));
    }

    public function test_payment_cannot_exceed_the_remaining_receivable(): void
    {
        $this->store(['details' => [$this->detail(['fkasdtvalue' => 30001])]]);
        $this->assertNull($this->header(), 'Penerimaan melebihi sisa piutang harus ditolak. ' . $this->lastFlash());
        $this->assertTrue($this->sessionHasError('details.0.fkasdtvalue'), $this->lastFlash());
        $this->assertEquals(30000, $this->remain());
    }

    public function test_partial_collection_leaves_the_balance_collectable_later(): void
    {
        $this->store(['details' => [$this->detail(['fkasdtvalue' => 20000])]]);
        $this->assertNotNull($this->header(), $this->lastFlash());
        $this->assertEquals(10000, $this->remain());

        $this->store(['fket' => self::KET . '_2', 'details' => [$this->detail(['fkasdtvalue' => 10000, 'fsisa_piutang' => 10000, 'original_sisa' => 10000])]]);
        $this->assertNotNull($this->header(self::KET . '_2'), 'Sisa piutang 10.000 harus bisa ditagih di dokumen berikutnya. ' . $this->lastFlash());
        $this->assertEquals(0, $this->remain());
    }

    public function test_installments_cannot_exceed_the_remaining_receivable(): void
    {
        $this->store(['details' => [$this->detail(['fkasdtvalue' => 20000])]]);
        $first = $this->header();
        $this->assertNotNull($first, $this->lastFlash());

        $this->store(['fket' => self::KET . '_2', 'details' => [$this->detail(['fkasdtvalue' => 15000, 'fsisa_piutang' => 10000, 'original_sisa' => 10000])]]);
        $this->assertNull($this->header(self::KET . '_2'), 'Cicilan 15.000 melebihi sisa 10.000 harus ditolak.');
        $this->assertEquals(10000, $this->remain());

        $this->atomic(fn () => $this->patch(route('pelunasancustomer.update', $first->fkasmtno), $this->payload([
            'details' => [$this->detail(['fkasdtvalue' => 30000])],
        ])));
        $this->assertEquals(30000, $this->header()->famountpay ?? null, 'Edit cicilan sampai penuh harus diterima. ' . $this->lastFlash());
        $this->assertEquals(0, $this->remain());
    }

    public function test_the_receiving_account_must_be_a_cash_or_bank_account(): void
    {
        $bukanKas = $this->makeDetailAccount('ZZDUSKD2', 'K');
        $this->store(['faccountheader' => $bukanKas]);
        $this->assertNull($this->header(), 'Penerimaan tidak boleh masuk ke account yang bukan kas/bank.');
    }

    public function test_the_invoice_must_belong_to_the_selected_customer(): void
    {
        $lain = $this->makeCustomer('ZZ_DUSK_CUSTOMER_LAIN');
        $this->store(['fcustomer' => $lain]);
        $this->assertNull($this->header(), 'Nota customer lain tidak boleh dilunasi.');
        $this->assertTrue($this->sessionHasError('details.0.frefno'), $this->lastFlash());
    }

    public function test_the_collected_invoice_is_locked_for_edit_and_delete(): void
    {
        $this->store();
        $this->assertNotNull($this->header(), $this->lastFlash());

        $this->atomic(fn () => $this->deleteJson(route('invoice.destroy', $this->inv->ftranmtid)))->assertStatus(422);
        $this->assertTrue(DB::table('tranmt')->where('ftranmtid', $this->inv->ftranmtid)->exists(), 'Faktur yang sudah dilunasi tidak boleh terhapus.');
    }

    public function test_validation_fails(): void
    {
        $cases = [
            'tanggal kosong' => [['fkasmtdate' => ''], 'fkasmtdate'],
            'tanggal tidak valid' => [['fkasmtdate' => 'bukan-tanggal'], 'fkasmtdate'],
            'customer kosong' => [['fcustomer' => ''], 'fcustomer'],
            'customer tidak ada' => [['fcustomer' => 'ZZTIDAKADA'], 'fcustomer'],
            'account kosong' => [['faccountheader' => ''], 'faccountheader'],
            'keterangan lebih dari 50 karakter' => [['fket' => str_repeat('A', 51)], 'fket'],
            'tanpa nota' => [['details' => []], 'details'],
            'nota tidak ada' => [['details' => [$this->detail(['frefno' => 'INV/ZZ/0000/0000'])]], 'details.0.frefno'],
            'jumlah nol' => [['details' => [$this->detail(['fkasdtvalue' => 0])]], 'details.0.fkasdtvalue'],
            'jumlah bukan angka' => [['details' => [$this->detail(['fkasdtvalue' => 'abc'])]], 'details.0.fkasdtvalue'],
            'diskon di atas 100 persen' => [['details' => [$this->detail(['fdiscpersen' => 150])]], 'details.0.fdiscpersen'],
            'sisa piutang melebihi nilai nota' => [['details' => [$this->detail(['fsisa_piutang' => 40000])]], 'details.0.fsisa_piutang'],
            'biaya admin tanpa account' => [['fhargaadmin' => 1000], 'faccountadmin'],
            'giro mundur tanpa jatuh tempo' => [['fgiromundur' => 1], 'ftgljatuhtempo'],
        ];

        foreach ($cases as $label => [$override, $field]) {
            $this->store($override);
            $this->assertTrue($this->sessionHasError($field), "Kasus '$label': error '$field' tidak muncul. " . $this->lastFlash());
        }

        $this->assertNull($this->header(), 'Tidak boleh tersimpan saat validasi gagal.');
        $this->assertEquals(30000, $this->remain(), 'Piutang tidak boleh berubah.');
    }

    public function test_index_opens(): void
    {
        $this->get(route('pelunasancustomer.index'))->assertOk();
    }
}
