<?php

namespace Tests\Http;

use Illuminate\Support\Facades\DB;
use Tests\Http\Concerns\MakesPurchaseChain;

/** Cek stok Sales Order terhadap saldo kartu stok (prdwh) hasil penerimaan barang. */
class SalesOrderStockHttpTest extends LiveDbTestCase
{
    use MakesPurchaseChain;

    private const KET = 'ZZ_DUSK_SOSTOK';

    private string $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->grantPermissions('viewSalesOrder', 'createSalesOrder', 'updateSalesOrder', 'deleteSalesOrder');
        $this->setUpPurchaseChain();
        $this->customer = $this->makeCustomer();
    }

    private function saldo(): float
    {
        return (float) DB::table('prdwh')->where('fprdcode', $this->productCode)->sum('fsaldo');
    }

    private function store(int $qty, array $override = [])
    {
        return $this->atomic(fn () => $this->post(route('salesorder.store'), array_merge([
            'fsodate' => now()->format('Y-m-d'),
            'fcustno' => $this->customer,
            'fket' => self::KET,
            'fbranchcode' => substr((string) auth('sysuser')->user()->fcabang, 0, 2),
            'fprdcode' => [$this->productCode],
            'fsatuan' => [$this->unit],
            'fqty' => [$qty],
            'fprice' => [10000],
            'fdisc' => ['0'],
            'fnoacak' => ['123'],
            'fdesc' => [''],
        ], $override)));
    }

    private function header(): ?object
    {
        return DB::table('trsomt')->where('fket', self::KET)->first();
    }

    public function test_stock_card_balance_reflects_the_goods_receipt(): void
    {
        $this->assertEquals(6, $this->saldo(), 'Saldo kartu stok produk uji harus 6 setelah penerimaan 6 unit.');
    }

    public function test_order_within_the_stock_card_balance_is_saved_without_force(): void
    {
        $this->store(5);
        $this->assertNotNull($this->header(), 'SO 5 unit (stok 6) harus tersimpan tanpa force_save. ' . $this->lastFlash());
    }

    public function test_order_above_the_stock_card_balance_is_held(): void
    {
        $this->store(7);
        $this->assertNull($this->header(), 'SO 7 unit (stok 6) harus ditahan tanpa force_save. ' . $this->lastFlash());
        $this->assertNotEmpty(session('error'), 'Pesan stok tidak cukup harus tampil.');
        $this->assertStringContainsString('Stok => 6', (string) session('error'), 'Pesan harus menampilkan saldo kartu stok (6).');
    }

    public function test_order_above_the_balance_can_be_forced_when_negative_stock_is_allowed(): void
    {
        if (trim((string) DB::table('setini')->value('fstokbolehminus')) !== '1') {
            $this->markTestSkipped('Pengaturan stok boleh minus mati.');
        }

        $this->store(7, ['force_save' => 1]);
        $this->assertNotNull($this->header(), 'Dengan force_save dan stok boleh minus, SO harus tersimpan. ' . $this->lastFlash());
    }
}
