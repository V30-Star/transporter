<?php

namespace Tests\Http;

use Illuminate\Support\Facades\DB;
use Tests\Http\Concerns\MakesTestMaster;

class SalesOrderHttpTest extends LiveDbTestCase
{
    use MakesTestMaster;

    private const KET = 'ZZ_DUSK_SO';

    private string $customer;

    private string $productCode;

    private string $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->grantPermissions('viewSalesOrder', 'createSalesOrder', 'updateSalesOrder', 'deleteSalesOrder');

        $today = DB::table('trsomt')->whereBetween('fdatetime', [now()->startOfDay(), now()->endOfDay()])->count();
        if ($today >= 13) {
            $this->markTestSkipped("Batas 15 SO/hari hampir tercapai ($today hari ini).");
        }

        $this->customer = $this->makeCustomer();
        $product = $this->makeProduct();
        $this->productCode = $product->fprdcode;
        $this->unit = trim((string) $product->fsatuankecil);
    }

    /** SO 5 unit @ 10.000. force_save: stok produk uji tidak tercatat, pengaturan stok boleh minus dipakai. */
    private function payload(array $override = []): array
    {
        return array_merge([
            'fsodate' => now()->format('Y-m-d'),
            'fcustno' => $this->customer,
            'fket' => self::KET,
            'fbranchcode' => substr((string) auth('sysuser')->user()->fcabang, 0, 2),
            'fprdcode' => [$this->productCode],
            'fsatuan' => [$this->unit],
            'fqty' => [5],
            'fprice' => [10000],
            'fdisc' => ['0'],
            'fnoacak' => ['123'],
            'fdesc' => [''],
            'force_save' => 1,
        ], $override);
    }

    private function store(array $override = [])
    {
        return $this->atomic(fn () => $this->post(route('salesorder.store'), $this->payload($override)));
    }

    private function header(string $ket = self::KET): ?object
    {
        return DB::table('trsomt')->where('fket', $ket)->first();
    }

    private function details(string $fsono)
    {
        return DB::table('trsodt')->where('fsono', $fsono)->get();
    }

    public function test_crud_with_details(): void
    {
        $this->store();
        $h = $this->header();
        $this->assertNotNull($h, 'Sales order tidak tersimpan. ' . $this->lastFlash());
        $this->assertMatchesRegularExpression('#^SO/[A-Za-z0-9]+/\d{4}/\d{4}$#', $h->fsono, 'Format nomor SO tidak sesuai: ' . $h->fsono);
        $this->assertEquals(50000, $h->famountgross);
        $this->assertEquals(50000, $h->famountsonet);
        $this->assertEquals(0, $h->famountpajak);
        $this->assertEquals(50000, $h->famountso);
        $this->assertEquals(1, $h->fapproval, 'Tanpa batas kredit, SO langsung disetujui.');

        $rows = $this->details($h->fsono);
        $this->assertCount(1, $rows);
        $this->assertEquals(5, $rows[0]->fqty);
        $this->assertEquals(5, $rows[0]->fqtykecil);
        $this->assertEquals(5, $rows[0]->fqtyremain);
        $this->assertEquals(50000, $rows[0]->famount);

        $this->get(route('salesorder.view', $h->ftrsomtid))->assertOk();
        $this->get(route('salesorder.edit', $h->ftrsomtid))->assertOk();

        $this->atomic(fn () => $this->patch(route('salesorder.update', $h->ftrsomtid), $this->payload(['fqty' => [8]])));
        $updated = $this->header();
        $this->assertEquals(80000, $updated->famountso ?? null, 'Total harus dihitung ulang saat update. ' . $this->lastFlash());
        $this->assertEquals(80000, $this->details($h->fsono)->sum('famount'));

        $this->get(route('salesorder.delete', $h->ftrsomtid))->assertOk();
        $this->atomic(fn () => $this->deleteJson(route('salesorder.destroy', $h->ftrsomtid)));
        $this->assertNull($this->header(), 'Delete gagal. ' . $this->lastFlash());
        $this->assertCount(0, $this->details($h->fsono), 'Detail harus ikut terhapus.');
    }

    public function test_line_and_header_discounts_reduce_the_net_amount(): void
    {
        $this->store(['fdisc' => ['10'], 'fdiscpersen' => 5]);
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());
        $row = $this->details($h->fsono)->first();
        $this->assertEquals(5000, $row->fdiscount, 'Diskon baris 10% dari 50.000.');
        $this->assertEquals(45000, $row->famount);
        $this->assertEquals(50000, $h->famountgross);
        $this->assertEquals(42750, $h->famountsonet, 'Diskon header 5% dari 45.000.');
        $this->assertEquals(42750, $h->famountso);
        $this->assertEquals(7250, $h->fdiscount, 'Total diskon = 5.000 + 2.250.');
    }

    public function test_ppn_is_added_on_top_and_changes_the_number_format(): void
    {
        $this->store(['fapplyppn' => 1, 'ppn_rate' => 11]);
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());
        $this->assertMatchesRegularExpression('/^SO\.[A-Za-z0-9]+\.\d{4}\.\d{4}$/', $h->fsono, 'SO dengan PPN memakai titik: ' . $h->fsono);
        $this->assertEquals(50000, $h->famountsonet);
        $this->assertEquals(5500, $h->famountpajak);
        $this->assertEquals(55500, $h->famountso);
        $this->assertEquals(11, $h->fppnpersen);
    }

    public function test_number_increments(): void
    {
        $this->store();
        $this->store(['fket' => self::KET . '_2']);
        $a = $this->header();
        $b = $this->header(self::KET . '_2');
        $this->assertNotNull($a, $this->lastFlash());
        $this->assertNotNull($b, $this->lastFlash());
        $num = fn ($no) => (int) substr($no, max(strrpos($no, '.'), strrpos($no, '/')) + 1);
        $this->assertSame($num($a->fsono) + 1, $num($b->fsono), 'Nomor SO harus berurutan.');
    }

    public function test_credit_limit_requires_approval(): void
    {
        DB::table('mscustomer')->where('fcustomercode', $this->customer)->update(['flimit' => 10000]);

        $this->store();
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());
        $this->assertEquals(0, $h->fapproval, 'SO 50.000 melebihi batas kredit 10.000 harus menunggu persetujuan.');
        $this->assertSame('0', trim((string) $h->fneedacc));
        $this->assertNull($h->fuseracc);

        $this->grantPermissions('approveSalesOrder');
        $this->store(['fket' => self::KET . '_2', 'approve_now' => 1]);
        $approved = $this->header(self::KET . '_2');
        $this->assertNotNull($approved, $this->lastFlash());
        $this->assertEquals(1, $approved->fapproval, 'Pengguna berhak approve dengan approve_now harus langsung menyetujui.');
        $this->assertNotEmpty($approved->fuseracc);
    }

    public function test_insufficient_stock_needs_force_save(): void
    {
        $this->store(['force_save' => 0]);
        $this->assertNull($this->header(), 'Tanpa force_save, SO dengan stok tidak cukup harus ditahan. ' . $this->lastFlash());
        $this->assertNotEmpty(session('error'), 'Pesan stok tidak cukup harus tampil.');
    }

    public function test_validation_fails(): void
    {
        $cases = [
            'tanggal kosong' => [['fsodate' => ''], 'fsodate'],
            'tanggal tidak valid' => [['fsodate' => 'bukan-tanggal'], 'fsodate'],
            'customer kosong' => [['fcustno' => ''], 'fcustno'],
            'tanpa item' => [['fprdcode' => []], 'fprdcode'],
            'qty negatif' => [['fqty' => [-1]], 'fqty.0'],
            'harga negatif' => [['fprice' => [-1]], 'fprice.0'],
            'diskon header di atas 100' => [['fdiscpersen' => 150], 'fdiscpersen'],
            'no acak bukan 3 digit 1-9' => [['fnoacak' => ['120']], 'fnoacak.0'],
            'keterangan lebih dari 300 karakter' => [['fket' => str_repeat('A', 301)], 'fket'],
        ];

        foreach ($cases as $label => [$override, $field]) {
            $this->store($override);
            $this->assertTrue($this->sessionHasError($field), "Kasus '$label': error '$field' tidak muncul. " . $this->lastFlash());
        }

        $this->assertNull($this->header(), 'Tidak boleh tersimpan saat validasi gagal.');
    }

    public function test_an_order_without_any_valid_item_is_rejected(): void
    {
        $this->store(['fqty' => [0]]);
        $h = $this->header();
        $this->assertNull($h, 'SO tanpa satu pun item bernilai (qty 0) tidak boleh tersimpan.');
    }

    public function test_updating_an_order_cannot_leave_it_without_items(): void
    {
        $this->store();
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());

        $this->atomic(fn () => $this->patch(route('salesorder.update', $h->ftrsomtid), $this->payload(['fqty' => [0]])));
        $this->assertCount(1, $this->details($h->fsono), 'Update tanpa item bernilai tidak boleh menghapus semua detail SO. ' . $this->lastFlash());
        $this->assertEquals(50000, $this->header()->famountso, 'Total SO tidak boleh berubah.');
    }

    public function test_duplicate_customer_po_reference_is_detected(): void
    {
        $this->store(['frefpo' => 'PO-ZZ-001']);
        $this->assertNotNull($this->header(), $this->lastFlash());

        $this->postJson(route('salesorder.duplicate-refpo-check'), ['fcustno' => $this->customer, 'frefpo' => 'po-zz-001'])
            ->assertOk()->assertJson(['exists' => true]);
        $this->postJson(route('salesorder.duplicate-refpo-check'), ['fcustno' => $this->customer, 'frefpo' => 'PO-ZZ-002'])
            ->assertOk()->assertJson(['exists' => false]);
    }

    public function test_index_opens(): void
    {
        $this->get(route('salesorder.index'))->assertOk();
    }
}
