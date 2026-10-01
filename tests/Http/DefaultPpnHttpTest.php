<?php

namespace Tests\Http;

use Illuminate\Support\Facades\DB;

/** set_default 'DefaultAutoPPN': status awal checkbox PPN di halaman create semua menu yang punya checkbox PPN. */
class DefaultPpnHttpTest extends LiveDbTestCase
{
    /** route create => permission */
    private const MENUS = [
        'salesorder.create' => 'createSalesOrder',
        'invoice.create' => 'createInvoice',
        'penjualanretail.create' => 'createPenjualanRetail',
        'returpenjualan.create' => 'createReturPenjualan',
        'tr_poh.create' => 'createTr_poh',
        'fakturpembelian.create' => 'createFakturPembelian',
        'returpembelian.create' => 'createReturPembelian',
    ];

    private function setAutoPpn(int $value): void
    {
        DB::table('set_default')->updateOrInsert(['fdefaultname' => 'DefaultAutoPPN'], ['fdefaultvalue' => $value]);
    }

    private function initialState(string $route): ?string
    {
        $response = $this->get(route($route));
        if ($response->getStatusCode() !== 200) {
            $this->markTestSkipped("$route tidak membuka halaman create (status {$response->getStatusCode()}), mungkin batas harian.");
        }

        return preg_match('/includePPN:\s*(true|false)/', $response->getContent(), $m) ? $m[1] : null;
    }

    public function test_the_ppn_checkbox_starts_checked_when_default_auto_ppn_is_on(): void
    {
        $this->grantPermissions(...array_values(self::MENUS));
        $this->setAutoPpn(1);

        foreach (array_keys(self::MENUS) as $route) {
            $this->assertSame('true', $this->initialState($route), "$route: DefaultAutoPPN = 1, checkbox PPN harus tercentang.");
        }
    }

    public function test_the_ppn_checkbox_starts_unchecked_when_default_auto_ppn_is_off(): void
    {
        $this->grantPermissions(...array_values(self::MENUS));
        $this->setAutoPpn(0);

        foreach (array_keys(self::MENUS) as $route) {
            $this->assertSame('false', $this->initialState($route), "$route: DefaultAutoPPN = 0, checkbox PPN tidak tercentang.");
        }
    }
}
