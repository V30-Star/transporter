<?php

namespace Tests\Http;

use Illuminate\Support\Facades\DB;
use Tests\Http\Concerns\MakesPurchaseChain;

/**
 * Saldo kartu stok (prdwh/msprd.fstok, dijaga trigger DB dari trstockdt) mengikuti transaksi.
 * Rantai uji: penerimaan 6 unit, lalu faktur yang menagih penerimaan itu.
 */
class StockCardHttpTest extends LiveDbTestCase
{
    use MakesPurchaseChain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->grantPermissions('viewReturPembelian', 'createReturPembelian', 'updateReturPembelian', 'deleteReturPembelian');
        $this->setUpPurchaseChain();
    }

    private function saldo(): float
    {
        return (float) DB::table('prdwh')->where('fprdcode', $this->productCode)->where('fwhcode', $this->gudang)->sum('fsaldo');
    }

    private function fstok(): float
    {
        return (float) DB::table('msprd')->where('fprdcode', $this->productCode)->value('fstok');
    }

    private function retur(int $qty)
    {
        return $this->atomic(fn () => $this->post(route('returpembelian.store'), [
            'fstockmtdate' => now()->format('Y-m-d'), 'fsupplier' => $this->supplier, 'ffrom' => $this->gudang,
            'fket' => 'ZZ_DUSK_REB_STOK', 'fbranchcode' => auth('sysuser')->user()->fcabang, 'frefno' => $this->buy->fstockmtno,
            'fitemcode' => [$this->productCode], 'fsatuan' => [$this->unit], 'frefdtno' => [$this->buy->fstockmtno],
            'fqty' => [$qty], 'fprice' => [5000], 'fdesc' => [''],
        ]));
    }

    public function test_receipt_adds_stock_and_billing_it_does_not_add_it_again(): void
    {
        $this->assertEquals(6, $this->saldo(), 'Saldo gudang harus 6 (penerimaan 6 unit); menagih penerimaan tidak boleh menambah stok lagi.');
        $this->assertEquals(6, $this->fstok(), 'msprd.fstok harus 6.');
    }

    public function test_editing_the_invoice_does_not_change_stock(): void
    {
        $this->atomic(fn () => $this->patch(route('fakturpembelian.update', $this->buy->fstockmtid), [
            'fstockmtdate' => now()->format('Y-m-d'), 'fsupplier' => $this->supplier, 'ffrom' => $this->gudang, 'frefno' => 'ZZ-INV-CHAIN',
            'fket' => 'ZZ_DUSK_BUY_REF', 'fbranchcode' => auth('sysuser')->user()->fcabang, 'ftypebuy' => 0,
            'fitemcode' => [$this->productCode], 'fsatuan' => [$this->unit], 'fsource' => ['PB'],
            'frefdtid' => [DB::table('trstockdt')->where('fstockmtno', $this->ter->fstockmtno)->value('fstockdtid')],
            'frefdtno' => [$this->ter->fstockmtno], 'frefnoacak' => ['123'], 'fqty' => [5], 'fprice' => [5000],
            'fdiscpersen' => ['0'], 'fbiaya' => [0], 'fdesc' => [''],
        ]));
        $this->assertEquals(6, $this->saldo(), 'Mengedit faktur tidak boleh mengubah saldo stok. ' . $this->lastFlash());
    }

    public function test_purchase_return_reduces_stock_and_deleting_it_restores_it(): void
    {
        $this->retur(2);
        $retur = DB::table('trstockmt')->where('fstockmtcode', 'REB')->where('fket', 'ZZ_DUSK_REB_STOK')->first();
        $this->assertNotNull($retur, 'Retur uji gagal dibuat. ' . $this->lastFlash());
        $this->assertEquals(4, $this->saldo(), 'Retur pembelian 2 unit harus mengurangi saldo gudang dari 6 menjadi 4.');
        $this->assertEquals(4, $this->fstok(), 'msprd.fstok harus ikut turun menjadi 4.');

        $this->atomic(fn () => $this->deleteJson(route('returpembelian.destroy', $retur->fstockmtid)));
        $this->assertEquals(6, $this->saldo(), 'Menghapus retur harus mengembalikan saldo ke 6.');
    }
}
