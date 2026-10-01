<?php

namespace Tests\Http;

use Illuminate\Support\Facades\DB;
use Tests\Http\Concerns\MakesPurchaseChain;

class SuratJalanHttpTest extends LiveDbTestCase
{
    use MakesPurchaseChain;

    private const KET = 'ZZ_DUSK_SRJ';

    private string $customer;

    private object $so;

    protected function setUp(): void
    {
        parent::setUp();

        $this->grantPermissions(
            'viewSuratJalan', 'createSuratJalan', 'updateSuratJalan', 'deleteSuratJalan',
            'viewSalesOrder', 'createSalesOrder', 'updateSalesOrder', 'deleteSalesOrder',
        );

        $range = [now()->startOfDay(), now()->endOfDay()];
        $today = max(
            DB::table('trsomt')->whereBetween('fdatetime', $range)->count(),
            DB::table('trstockmt')->where('fstockmtcode', 'SRJ')->whereBetween('fdatetime', $range)->count(),
        );
        if ($today >= 12) {
            $this->markTestSkipped("Batas harian dokumen hampir tercapai ($today hari ini).");
        }

        $this->setUpPurchaseChain();   // stok 6 unit di gudang uji
        $this->customer = $this->makeCustomer();
        $this->so = $this->makeSalesOrder(5);
    }

    private function makeSalesOrder(int $qty, string $ket = 'ZZ_DUSK_SO_REF'): object
    {
        $this->atomic(fn () => $this->post(route('salesorder.store'), [
            'fsodate' => now()->format('Y-m-d'), 'fcustno' => $this->customer, 'fket' => $ket,
            'fbranchcode' => substr((string) auth('sysuser')->user()->fcabang, 0, 2),
            'fprdcode' => [$this->productCode], 'fsatuan' => [$this->unit], 'fqty' => [$qty], 'fprice' => [10000],
            'fdisc' => ['0'], 'fnoacak' => ['123'], 'fdesc' => [''], 'force_save' => 1,
        ]));
        $so = DB::table('trsomt')->where('fket', $ket)->first();
        $this->assertNotNull($so, 'SO uji gagal dibuat. ' . $this->lastFlash());

        return $so;
    }

    private function soRemain(?object $so = null): float
    {
        return (float) DB::table('trsodt')->where('fsono', ($so ?? $this->so)->fsono)->value('fqtyremain');
    }

    private function saldo(): float
    {
        return (float) DB::table('prdwh')->where('fprdcode', $this->productCode)->where('fwhcode', $this->gudang)->sum('fsaldo');
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'fstockmtdate' => now()->format('Y-m-d'),
            'fsupplier' => $this->customer,
            'ffrom' => $this->gudang,
            'fket' => self::KET,
            'fkirim' => 'ZZ ALAMAT KIRIM',
            'fbranchcode' => auth('sysuser')->user()->fcabang,
            'fitemcode' => [$this->productCode],
            'fsatuan' => [$this->unit],
            'frefso' => [$this->so->fsono],
            'frefnoacak' => ['123'],
            'fqty' => [3],
            'fprice' => [10000],
            'fdiscpersen' => [0],
            'fnoacak' => ['123'],
            'fdesc' => [''],
        ], $override);
    }

    private function store(array $override = [])
    {
        return $this->atomic(fn () => $this->post(route('suratjalan.store'), $this->payload($override)));
    }

    private function header(string $ket = self::KET): ?object
    {
        return DB::table('trstockmt')->where('fstockmtcode', 'SRJ')->where('fket', $ket)->first();
    }

    private function details(string $no)
    {
        return DB::table('trstockdt')->where('fstockmtno', $no)->get();
    }

    public function test_crud_shipping_a_sales_order(): void
    {
        $this->assertEquals(6, $this->saldo());
        $this->assertEquals(5, $this->soRemain());

        $this->store();
        $h = $this->header();
        $this->assertNotNull($h, 'Surat jalan tidak tersimpan. ' . $this->lastFlash());
        $this->assertMatchesRegularExpression('#^SRJ/[A-Za-z0-9]+/\d{4}/\d{4}$#', $h->fstockmtno, 'Format nomor tidak sesuai: ' . $h->fstockmtno);
        $this->assertEquals(30000, $h->famount);
        $this->assertSame('ZZ ALAMAT KIRIM', trim($h->fkirim));

        $rows = $this->details($h->fstockmtno);
        $this->assertCount(1, $rows);
        $this->assertSame($this->so->fsono, trim($rows[0]->frefso));
        $this->assertSame('S', trim($rows[0]->fcode), 'Surat jalan dari SO memakai kode detail S.');
        $this->assertEquals(3, $rows[0]->fqtykecil);

        $this->assertEquals(3, $this->saldo(), 'Surat jalan 3 unit harus mengurangi stok gudang dari 6 menjadi 3.');
        $this->assertEquals(2, $this->soRemain(), 'Sisa qty SO harus berkurang dari 5 menjadi 2.');

        $this->get(route('suratjalan.view', $h->fstockmtid))->assertOk();
        $this->get(route('suratjalan.edit', $h->fstockmtid))->assertOk();

        $this->atomic(fn () => $this->patch(route('suratjalan.update', $h->fstockmtid), $this->payload(['fqty' => [4]])));
        $this->assertEquals(40000, $this->header()->famount ?? null, 'Total harus dihitung ulang saat update. ' . $this->lastFlash());
        $this->assertEquals(2, $this->saldo(), 'Stok harus mengikuti qty terbaru (6 - 4).');
        $this->assertEquals(1, $this->soRemain(), 'Sisa SO harus mengikuti qty terbaru (5 - 4).');

        $this->get(route('suratjalan.delete', $h->fstockmtid))->assertOk();
        $this->atomic(fn () => $this->deleteJson(route('suratjalan.destroy', $h->fstockmtid)));
        $this->assertNull($this->header(), 'Delete gagal. ' . $this->lastFlash());
        $this->assertCount(0, $this->details($h->fstockmtno));
        $this->assertEquals(6, $this->saldo(), 'Menghapus surat jalan harus mengembalikan stok.');
        $this->assertEquals(5, $this->soRemain(), 'Menghapus surat jalan harus mengembalikan sisa SO.');
    }

    public function test_shipments_are_partial_until_the_sales_order_is_used_up(): void
    {
        $this->store(['fqty' => [3]]);
        $this->assertNotNull($this->header(), $this->lastFlash());

        $this->store(['fket' => self::KET . '_2', 'fqty' => [3]]);
        $this->assertNull($this->header(self::KET . '_2'), 'Pengiriman 3 melebihi sisa SO 2, harus ditolak.');

        $this->store(['fket' => self::KET . '_2', 'fqty' => [2]]);
        $this->assertNotNull($this->header(self::KET . '_2'), 'Pengiriman sebesar sisa SO harus diterima. ' . $this->lastFlash());
        $this->assertEquals(0, $this->soRemain());
        $this->assertEquals(1, $this->saldo());

        $this->store(['fket' => self::KET . '_3', 'fqty' => [1]]);
        $this->assertNull($this->header(self::KET . '_3'), 'SO yang sudah habis dikirim tidak boleh dikirim lagi.');
    }

    public function test_editing_a_shipment_does_not_count_its_own_qty(): void
    {
        $this->store(['fqty' => [3]]);
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());

        $this->atomic(fn () => $this->patch(route('suratjalan.update', $h->fstockmtid), $this->payload(['fqty' => [5]])));
        $this->assertEquals(50000, $this->header()->famount ?? null, 'Edit ke qty SO penuh harus diterima. ' . $this->lastFlash());

        $this->atomic(fn () => $this->patch(route('suratjalan.update', $h->fstockmtid), $this->payload(['fqty' => [6]])));
        $this->assertEquals(50000, $this->header()->famount, 'Edit melebihi qty SO harus ditolak. ' . $this->lastFlash());
    }

    public function test_shipping_more_than_the_stock_needs_force_save(): void
    {
        $so = $this->makeSalesOrder(8, 'ZZ_DUSK_SO_REF2');

        $this->store(['frefso' => [$so->fsono], 'fqty' => [7]]);
        $this->assertNull($this->header(), 'Pengiriman 7 unit (stok 6) harus ditahan tanpa force_save. ' . $this->lastFlash());
        $this->assertStringContainsString('Stok => 6', (string) session('error'), 'Pesan harus menampilkan saldo gudang.');

        if (trim((string) DB::table('setini')->value('fstokbolehminus')) === '1') {
            $this->store(['frefso' => [$so->fsono], 'fqty' => [7], 'force_save' => 1]);
            $this->assertNotNull($this->header(), 'Dengan force_save dan stok boleh minus, pengiriman harus tersimpan. ' . $this->lastFlash());
            $this->assertEquals(-1, $this->saldo(), 'Stok boleh minus: 6 - 7 = -1.');
        }
    }

    public function test_a_shipment_without_sales_order_reference_is_allowed(): void
    {
        $this->store(['frefso' => [''], 'frefnoacak' => [''], 'fqty' => [2]]);
        $h = $this->header();
        $this->assertNotNull($h, 'Surat jalan tanpa referensi SO harus tersimpan. ' . $this->lastFlash());
        $this->assertSame('0', trim($this->details($h->fstockmtno)->first()->fcode));
        $this->assertEquals(4, $this->saldo());
        $this->assertEquals(5, $this->soRemain(), 'SO tidak boleh terpengaruh.');
    }

    public function test_the_sales_order_used_by_a_shipment_is_locked(): void
    {
        $this->store();
        $this->assertNotNull($this->header(), $this->lastFlash());

        $this->atomic(fn () => $this->deleteJson(route('salesorder.destroy', $this->so->ftrsomtid)))->assertStatus(422);
        $this->assertTrue(DB::table('trsomt')->where('ftrsomtid', $this->so->ftrsomtid)->exists(), 'SO yang sudah dikirim tidak boleh terhapus.');
    }

    public function test_validation_fails(): void
    {
        $cases = [
            'tanggal kosong' => [['fstockmtdate' => ''], 'fstockmtdate'],
            'tanggal tidak valid' => [['fstockmtdate' => 'bukan-tanggal'], 'fstockmtdate'],
            'customer kosong' => [['fsupplier' => ''], 'fsupplier'],
            'gudang kosong' => [['ffrom' => ''], 'ffrom'],
            'tanpa item' => [['fitemcode' => []], 'fitemcode'],
            'qty nol' => [['fqty' => [0]], 'fqty.0'],
            'qty bukan angka' => [['fqty' => ['abc']], 'fqty.0'],
            'harga negatif' => [['fprice' => [-1]], 'fprice.0'],
            'diskon di atas 100' => [['fdiscpersen' => [150]], 'fdiscpersen.0'],
            'no acak bukan 3 digit 1-9' => [['fnoacak' => ['120']], 'fnoacak.0'],
            'keterangan lebih dari 50 karakter' => [['fket' => str_repeat('A', 51)], 'fket'],
        ];

        foreach ($cases as $label => [$override, $field]) {
            $this->store($override);
            $this->assertTrue($this->sessionHasError($field), "Kasus '$label': error '$field' tidak muncul. " . $this->lastFlash());
        }

        $this->assertNull($this->header(), 'Tidak boleh tersimpan saat validasi gagal.');
        $this->assertEquals(6, $this->saldo(), 'Stok tidak boleh berubah.');
        $this->assertEquals(5, $this->soRemain(), 'Sisa SO tidak boleh berubah.');
    }

    public function test_index_opens(): void
    {
        $this->get(route('suratjalan.index'))->assertOk();
    }
}
