<?php

namespace Tests\Http;

use Illuminate\Support\Facades\DB;
use Tests\Http\Concerns\MakesPurchaseChain;

class ReturPenjualanHttpTest extends LiveDbTestCase
{
    use MakesPurchaseChain;

    private const KET = 'ZZ_DUSK_REJ';

    private string $customer;

    private object $inv;

    protected function setUp(): void
    {
        parent::setUp();

        $this->grantPermissions(
            'viewReturPenjualan', 'createReturPenjualan', 'updateReturPenjualan', 'deleteReturPenjualan',
            'viewInvoice', 'createInvoice', 'updateInvoice', 'deleteInvoice',
            'viewSuratJalan', 'createSuratJalan', 'viewSalesOrder', 'createSalesOrder',
        );

        $range = [now()->startOfDay(), now()->endOfDay()];
        $today = max(
            DB::table('tranmt')->whereIn('ftrcode', ['INV', 'REJ'])->whereBetween('fdatetime', $range)->count(),
            DB::table('trsomt')->whereBetween('fdatetime', $range)->count(),
            DB::table('trstockmt')->whereIn('fstockmtcode', ['SRJ', 'REJ'])->whereBetween('fdatetime', $range)->count(),
        );
        if ($today >= 10) {
            $this->markTestSkipped("Batas harian dokumen hampir tercapai ($today hari ini).");
        }

        $this->setUpPurchaseChain();   // stok 6 unit di gudang uji
        $this->customer = $this->makeCustomer();
        $branch = auth('sysuser')->user()->fcabang;

        $this->atomic(fn () => $this->post(route('salesorder.store'), [
            'fsodate' => now()->format('Y-m-d'), 'fcustno' => $this->customer, 'fket' => 'ZZ_DUSK_SO_REF',
            'fbranchcode' => substr((string) $branch, 0, 2),
            'fprdcode' => [$this->productCode], 'fsatuan' => [$this->unit], 'fqty' => [5], 'fprice' => [10000],
            'fdisc' => ['0'], 'fnoacak' => ['123'], 'fdesc' => [''],
        ]));
        $so = DB::table('trsomt')->where('fket', 'ZZ_DUSK_SO_REF')->first();
        $this->assertNotNull($so, 'SO uji gagal dibuat. ' . $this->lastFlash());

        $this->atomic(fn () => $this->post(route('suratjalan.store'), [
            'fstockmtdate' => now()->format('Y-m-d'), 'fsupplier' => $this->customer, 'ffrom' => $this->gudang,
            'fket' => 'ZZ_DUSK_SRJ_REF', 'fkirim' => 'ZZ', 'fbranchcode' => $branch,
            'fitemcode' => [$this->productCode], 'fsatuan' => [$this->unit], 'frefso' => [$so->fsono],
            'frefnoacak' => ['123'], 'fqty' => [3], 'fprice' => [10000], 'fdiscpersen' => [0], 'fnoacak' => ['123'], 'fdesc' => [''],
        ]));
        $srj = DB::table('trstockmt')->where('fstockmtcode', 'SRJ')->where('fket', 'ZZ_DUSK_SRJ_REF')->first();
        $this->assertNotNull($srj, 'Surat jalan uji gagal dibuat. ' . $this->lastFlash());

        $this->atomic(fn () => $this->post(route('invoice.store'), [
            'fsodate' => now()->format('Y-m-d'), 'fcustno' => $this->customer, 'fket' => 'ZZ_DUSK_INV_REF', 'fbranchcode' => $branch,
            'ftypesales' => 0, 'fitemcode' => [$this->productCode], 'fsatuan' => [$this->unit],
            'frefsrj' => [$srj->fstockmtno], 'frefdtno' => [$srj->fstockmtno], 'frefnoacak' => ['123'],
            'fqty' => [3], 'fprice' => [10000], 'fdisc' => ['0'], 'fnoacak' => ['123'], 'fdesc' => [''],
        ]));
        $this->inv = DB::table('tranmt')->where('ftrcode', 'INV')->where('fket', 'ZZ_DUSK_INV_REF')->first();
        $this->assertNotNull($this->inv, 'Faktur uji gagal dibuat. ' . $this->lastFlash());
    }

    /** Retur 1 unit @ 10.000 atas faktur uji (3 unit). */
    private function payload(array $override = []): array
    {
        return array_merge([
            'fsodate' => now()->format('Y-m-d'),
            'fcustno' => $this->customer,
            'ffrom' => $this->gudang,
            'fket' => self::KET,
            'fbranchcode' => auth('sysuser')->user()->fcabang,
            'fitemcode' => [$this->productCode],
            'fsatuan' => [$this->unit],
            'frefso' => [$this->inv->fsono],
            'frefnoacak' => ['123'],
            'fqty' => [1],
            'fprice' => [10000],
            'fnoacak' => ['123'],
            'fdesc' => [''],
        ], $override);
    }

    private function store(array $override = [])
    {
        return $this->atomic(fn () => $this->post(route('returpenjualan.store'), $this->payload($override)));
    }

    private function header(string $ket = self::KET): ?object
    {
        return DB::table('tranmt')->where('ftrcode', 'REJ')->where('fket', $ket)->first();
    }

    private function details(string $fsono)
    {
        return DB::table('trandt')->where('fsono', $fsono)->get();
    }

    private function journalLines(string $fsono)
    {
        return DB::table('jurnaldt')->whereIn('fjurnalno', ['JV/' . ltrim($fsono, '/'), 'JV.' . ltrim($fsono, '.')])->get();
    }

    private function saldo(): float
    {
        return (float) DB::table('prdwh')->where('fprdcode', $this->productCode)->where('fwhcode', $this->gudang)->sum('fsaldo');
    }

    public function test_crud_returning_goods_from_an_invoice(): void
    {
        $this->assertEquals(3, $this->saldo(), 'Setelah surat jalan 3 unit, stok gudang 3.');

        $this->store();
        $h = $this->header();
        $this->assertNotNull($h, 'Retur penjualan tidak tersimpan. ' . $this->lastFlash());
        $this->assertMatchesRegularExpression('#^REJ[/.][A-Za-z0-9]+[/.]\d{4}[/.]\d{4}$#', $h->fsono, 'Format nomor tidak sesuai: ' . $h->fsono);
        $this->assertEquals(10000, $h->famountso);
        $this->assertEquals(10000, $h->famountremain);

        $rows = $this->details($h->fsono);
        $this->assertCount(1, $rows);
        $this->assertSame($this->inv->fsono, trim($rows[0]->frefso));
        $this->assertEquals(1, $rows[0]->fqty);
        $this->assertEquals(10000, $rows[0]->famount);

        $this->assertEquals(4, $this->saldo(), 'Retur 1 unit harus menambah stok gudang dari 3 menjadi 4.');

        $lines = $this->journalLines($h->fsono);
        $this->assertGreaterThanOrEqual(2, $lines->count(), 'Jurnal retur harus punya baris debit dan kredit.');
        $this->assertEquals($lines->where('fdk', 'D')->sum('famount'), $lines->where('fdk', 'K')->sum('famount'), 'Jurnal retur harus seimbang.');
        $this->assertEquals(10000, $lines->where('fdk', 'K')->sum('famount'));

        $this->get(route('returpenjualan.view', $h->ftranmtid))->assertOk();
        $this->get(route('returpenjualan.edit', $h->ftranmtid))->assertOk();

        $this->atomic(fn () => $this->patch(route('returpenjualan.update', $h->ftranmtid), $this->payload(['fqty' => [2]])));
        $this->assertEquals(20000, $this->header()->famountso ?? null, 'Total harus dihitung ulang saat update. ' . $this->lastFlash());
        $this->assertEquals(5, $this->saldo(), 'Stok harus mengikuti qty retur terbaru (3 + 2).');
        $lines = $this->journalLines($h->fsono);
        $this->assertEquals(20000, $lines->where('fdk', 'K')->sum('famount'), 'Jurnal lama harus diganti.');

        $this->get(route('returpenjualan.delete', $h->ftranmtid))->assertOk();
        $this->atomic(fn () => $this->deleteJson(route('returpenjualan.destroy', $h->ftranmtid)));
        $this->assertNull($this->header(), 'Delete gagal. ' . $this->lastFlash());
        $this->assertCount(0, $this->details($h->fsono));
        $this->assertCount(0, $this->journalLines($h->fsono), 'Jurnal harus ikut terhapus.');
        $this->assertEquals(3, $this->saldo(), 'Menghapus retur harus mengembalikan stok ke 3.');
    }

    public function test_return_qty_cannot_exceed_the_invoice_qty(): void
    {
        $this->store(['fqty' => [4]]);
        $this->assertNull($this->header(), 'Retur 4 melebihi qty faktur 3 harus ditolak. ' . $this->lastFlash());
        $this->assertEquals(3, $this->saldo(), 'Stok tidak boleh berubah.');

        $this->store(['fqty' => [3]]);
        $this->assertNotNull($this->header(), 'Retur sebesar qty faktur harus diterima. ' . $this->lastFlash());
    }

    public function test_returns_are_partial_until_the_invoice_qty_is_used_up(): void
    {
        $this->store(['fqty' => [2]]);
        $this->assertNotNull($this->header(), $this->lastFlash());

        $this->store(['fket' => self::KET . '_2', 'fqty' => [2]]);
        $this->assertNull($this->header(self::KET . '_2'), 'Retur 2 melebihi sisa faktur 1 (total 2 + 2 > 3), harus ditolak.');

        $this->store(['fket' => self::KET . '_2', 'fqty' => [1]]);
        $this->assertNotNull($this->header(self::KET . '_2'), 'Retur sebesar sisa faktur harus diterima. ' . $this->lastFlash());

        $this->store(['fket' => self::KET . '_3', 'fqty' => [1]]);
        $this->assertNull($this->header(self::KET . '_3'), 'Faktur yang qty-nya sudah habis diretur tidak boleh diretur lagi.');
    }

    public function test_editing_a_return_does_not_count_its_own_qty(): void
    {
        $this->store(['fqty' => [2]]);
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());

        $this->atomic(fn () => $this->patch(route('returpenjualan.update', $h->ftranmtid), $this->payload(['fqty' => [3]])));
        $this->assertEquals(30000, $this->header()->famountso ?? null, 'Edit ke qty faktur penuh harus diterima. ' . $this->lastFlash());

        $this->atomic(fn () => $this->patch(route('returpenjualan.update', $h->ftranmtid), $this->payload(['fqty' => [4]])));
        $this->assertEquals(30000, $this->header()->famountso, 'Edit melebihi qty faktur harus ditolak. ' . $this->lastFlash());
    }

    public function test_every_item_needs_an_invoice_or_delivery_note_reference(): void
    {
        $this->store(['frefso' => [''], 'frefnoacak' => ['']]);
        $this->assertNull($this->header(), 'Retur tanpa referensi harus ditolak.');
        $this->assertTrue($this->sessionHasError('fitemcode.0'), $this->lastFlash());
    }

    public function test_the_invoice_with_a_return_is_locked(): void
    {
        $this->store();
        $this->assertNotNull($this->header(), $this->lastFlash());

        $this->atomic(fn () => $this->deleteJson(route('invoice.destroy', $this->inv->ftranmtid)))->assertStatus(422);
        $this->assertTrue(DB::table('tranmt')->where('ftranmtid', $this->inv->ftranmtid)->exists(), 'Faktur yang sudah diretur tidak boleh terhapus.');
    }

    public function test_validation_fails(): void
    {
        $cases = [
            'tanggal kosong' => [['fsodate' => ''], 'fsodate'],
            'tanggal tidak valid' => [['fsodate' => 'bukan-tanggal'], 'fsodate'],
            'customer kosong' => [['fcustno' => ''], 'fcustno'],
            'gudang kosong' => [['ffrom' => ''], 'ffrom'],
            'tanpa item' => [['fitemcode' => []], 'fitemcode'],
            'qty negatif' => [['fqty' => [-1]], 'fqty.0'],
            'harga negatif' => [['fprice' => [-1]], 'fprice.0'],
            'no acak bukan 3 digit 1-9' => [['fnoacak' => ['120']], 'fnoacak.0'],
        ];

        foreach ($cases as $label => [$override, $field]) {
            $this->store($override);
            $this->assertTrue($this->sessionHasError($field), "Kasus '$label': error '$field' tidak muncul. " . $this->lastFlash());
        }

        $this->assertNull($this->header(), 'Tidak boleh tersimpan saat validasi gagal.');
        $this->assertEquals(3, $this->saldo(), 'Stok tidak boleh berubah.');
    }

    public function test_a_return_without_any_valid_item_is_rejected(): void
    {
        $this->store(['fqty' => [0]]);
        $this->assertNull($this->header(), 'Retur tanpa item bernilai tidak boleh tersimpan. ' . $this->lastFlash());
    }

    public function test_index_opens(): void
    {
        $this->get(route('returpenjualan.index'))->assertOk();
    }
}
