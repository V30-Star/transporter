<?php

namespace Tests\Http;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Tests\Http\Concerns\MakesTestMaster;

class AdjstockHttpTest extends LiveDbTestCase
{
    use MakesTestMaster;

    private const KET = 'ZZ_DUSK_ADJ';

    private string $productCode;

    private string $unit;

    private string $gudang = 'ZZDUSKW1';

    protected function setUp(): void
    {
        parent::setUp();

        $this->grantPermissions('viewAdjstock', 'createAdjstock', 'updateAdjstock', 'deleteAdjstock', 'createGudang');

        $today = DB::table('trstockmt')->where('fstockmtcode', 'ADJ')->whereBetween('fdatetime', [now()->startOfDay(), now()->endOfDay()])->count();
        if ($today >= 13) {
            $this->markTestSkipped("Batas 15 dokumen/hari hampir tercapai ($today hari ini).");
        }

        $product = $this->makeProduct();
        $this->productCode = $product->fprdcode;
        $this->unit = trim((string) $product->fsatuankecil);

        $this->atomic(fn () => $this->post(route('gudang.store'), [
            'fwhcode' => $this->gudang, 'fwhname' => 'ZZ GUDANG UJI', 'faddress' => 'ZZ', 'fbranchcode' => 'ZZCAB',
        ]));
        $this->assertTrue(DB::table('mswh')->where('fwhcode', $this->gudang)->exists(), 'Gudang uji gagal dibuat. ' . $this->lastFlash());
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'fstockmtdate' => now()->format('Y-m-d'),
            'ffrom' => $this->gudang,
            'ftrancode' => 'M',
            'fket' => self::KET,
            'fbranchcode' => auth('sysuser')->user()->fcabang,
            'fitemcode' => [$this->productCode],
            'fsatuan' => [$this->unit],
            'fqty' => [5],
            'fprice' => [5000],
            'fdesc' => [''],
        ], $override);
    }

    private function store(array $override = [])
    {
        return $this->atomic(fn () => $this->post(route('adjstock.store'), $this->payload($override)));
    }

    private function header(string $ket = self::KET): ?object
    {
        return DB::table('trstockmt')->where('fstockmtcode', 'ADJ')->where('fket', $ket)->first();
    }

    private function details(string $no)
    {
        return DB::table('trstockdt')->where('fstockmtno', $no)->get();
    }

    private function saldo(?string $code = null): float
    {
        return (float) DB::table('prdwh')->where('fprdcode', $code ?? $this->productCode)->where('fwhcode', $this->gudang)->sum('fsaldo');
    }

    public function test_crud_stock_in_adjustment(): void
    {
        $this->assertEquals(0, $this->saldo());

        $this->store();
        $h = $this->header();
        $this->assertNotNull($h, 'Adjustment stok tidak tersimpan. ' . $this->lastFlash());
        $this->assertMatchesRegularExpression('/^ADJ\.[A-Za-z0-9]+\.\d{4}\.\d{4}$/', $h->fstockmtno, 'Format nomor tidak sesuai: ' . $h->fstockmtno);
        $this->assertSame('M', trim($h->ftrancode));
        $this->assertEquals(25000, $h->famount);
        $this->assertEquals(1, $h->fapproval, 'Adjustment stok langsung disetujui.');

        $rows = $this->details($h->fstockmtno);
        $this->assertCount(1, $rows);
        $this->assertEquals(5, $rows[0]->fqty);
        $this->assertEquals(5, $rows[0]->fqtykecil);
        $this->assertEquals(5, $this->saldo(), 'Adjustment masuk 5 unit harus menambah saldo gudang menjadi 5.');
        $this->assertEquals(5, (float) DB::table('msprd')->where('fprdcode', $this->productCode)->value('fstok'), 'msprd.fstok ikut naik.');

        $this->get(route('adjstock.view', $h->fstockmtid))->assertOk();
        $this->get(route('adjstock.edit', $h->fstockmtid))->assertOk();

        $this->atomic(fn () => $this->patch(route('adjstock.update', $h->fstockmtid), $this->payload(['fqty' => [8]])));
        $this->assertEquals(40000, $this->header()->famount ?? null, 'Total harus dihitung ulang saat update. ' . $this->lastFlash());
        $this->assertEquals(8, $this->saldo(), 'Saldo harus mengikuti qty terbaru.');
        $this->assertEquals(40000, DB::table('jurnaldt')->where('frefno', $h->fstockmtno)->where('fdk', 'D')->sum('famount'), 'Jurnal harus dihitung ulang saat update.');
        $this->assertTrue(DB::table('log_trstockmt')->where('fstockmtno', $h->fstockmtno)->where('feditmode', 'U')->exists(), 'Log update tidak tercatat.');

        $this->get(route('adjstock.delete', $h->fstockmtid))->assertOk();
        $this->atomic(fn () => $this->deleteJson(route('adjstock.destroy', $h->fstockmtid)));
        $this->assertNull($this->header(), 'Delete gagal. ' . $this->lastFlash());
        $this->assertCount(0, $this->details($h->fstockmtno));
        $this->assertSame(0, DB::table('jurnaldt')->where('frefno', $h->fstockmtno)->count(), 'Jurnal harus ikut terhapus.');
        $this->assertEquals(0, $this->saldo(), 'Menghapus adjustment harus mengembalikan saldo ke 0.');
        $this->assertTrue(DB::table('log_trstockmt')->where('fstockmtno', $h->fstockmtno)->where('feditmode', 'D')->exists(), 'Log delete tidak tercatat.');
    }

    public function test_stock_out_adjustment_reduces_the_balance(): void
    {
        $this->store(['fket' => self::KET . '_IN', 'fqty' => [6]]);
        $this->assertEquals(6, $this->saldo());

        $this->store(['ftrancode' => 'K', 'fqty' => [4]]);
        $out = $this->header();
        $this->assertNotNull($out, 'Adjustment keluar tidak tersimpan. ' . $this->lastFlash());
        $this->assertEquals(2, $this->saldo(), 'Adjustment keluar 4 unit harus mengurangi saldo dari 6 menjadi 2.');

        $this->atomic(fn () => $this->deleteJson(route('adjstock.destroy', $out->fstockmtid)));
        $this->assertEquals(6, $this->saldo(), 'Menghapus adjustment keluar harus mengembalikan saldo ke 6.');
    }

    public function test_stock_out_more_than_the_balance_needs_force_save(): void
    {
        $this->store(['fket' => self::KET . '_IN', 'fqty' => [6]]);

        $this->store(['ftrancode' => 'K', 'fqty' => [7]]);
        $this->assertNull($this->header(), 'Adjustment keluar 7 unit (stok 6) harus ditahan tanpa force_save. ' . $this->lastFlash());
        $this->assertStringContainsString('Stok => 6', (string) session('error'), 'Pesan harus menampilkan saldo gudang.');
        $this->assertEquals(6, $this->saldo(), 'Saldo tidak boleh berubah saat ditahan.');
    }

    public function test_the_transaction_type_must_be_in_or_out(): void
    {
        $this->store(['ftrancode' => 'X']);
        $this->assertNull($this->header(), 'Jenis adjustment selain M (masuk) dan K (keluar) tidak boleh tersimpan karena tidak mengubah stok. ' . $this->lastFlash());
        $this->assertEquals(0, $this->saldo());
    }

    public function test_adjusting_in_a_bigger_unit_changes_stock_by_the_small_unit_quantity(): void
    {
        [$kecil, $besar] = DB::table('mssatuan')->whereRaw('fsatuancode = trim(fsatuancode)')->limit(2)->pluck('fsatuancode')->all();
        $this->grantPermissions('createProduct');
        $this->atomic(fn () => $this->post(route('product.store'), [
            'fprdcode' => 'ZZDUSKP2', 'fprdname' => 'ZZ_DUSK_PRODUK_BESAR', 'ftype' => 'Produk',
            'fgroupcode' => DB::table('ms_groupprd')->whereRaw('fgroupcode = trim(fgroupcode)')->value('fgroupcode'),
            'fmerek' => DB::table('msmerek')->whereRaw('fmerekcode = trim(fmerekcode)')->value('fmerekcode'),
            'fsatuankecil' => $kecil, 'fsatuanbesar' => $besar, 'fqtykecil' => 12, 'fsatuandefault' => '1',
        ]));
        $this->assertNotNull(Product::where('fprdcode', 'ZZDUSKP2')->first(), $this->lastFlash());

        $this->store(['fitemcode' => ['ZZDUSKP2'], 'fsatuan' => [$besar], 'fqty' => [1], 'fprice' => [60000]]);
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());
        $this->assertEquals(12, $this->details($h->fstockmtno)->first()->fqtykecil);
        $this->assertEquals(12, $this->saldo('ZZDUSKP2'), 'Adjustment masuk 1 satuan besar (12 unit) harus menambah stok 12.');
    }

    /** Perlu storage/app/fix_tg_trstockdt_update_prdwh.sql sudah dijalankan di DB. */
    public function test_cost_of_a_bigger_unit_receipt_is_per_small_unit_even_when_stock_is_empty(): void
    {
        [$kecil, $besar] = DB::table('mssatuan')->whereRaw('fsatuancode = trim(fsatuancode)')->limit(2)->pluck('fsatuancode')->all();
        $this->grantPermissions('createProduct');
        $this->atomic(fn () => $this->post(route('product.store'), [
            'fprdcode' => 'ZZDUSKP2', 'fprdname' => 'ZZ_DUSK_PRODUK_BESAR', 'ftype' => 'Produk',
            'fgroupcode' => DB::table('ms_groupprd')->whereRaw('fgroupcode = trim(fgroupcode)')->value('fgroupcode'),
            'fmerek' => DB::table('msmerek')->whereRaw('fmerekcode = trim(fmerekcode)')->value('fmerekcode'),
            'fsatuankecil' => $kecil, 'fsatuanbesar' => $besar, 'fqtykecil' => 12, 'fsatuandefault' => '1',
        ]));
        $this->assertNotNull(Product::where('fprdcode', 'ZZDUSKP2')->first(), $this->lastFlash());
        $hpp = fn () => (float) DB::table('msprd')->where('fprdcode', 'ZZDUSKP2')->value('fhpp');

        // stok kosong: 1 satuan besar (12 unit) seharga 60.000 -> HPP 5.000 per satuan kecil
        $this->store(['fket' => self::KET . '_A', 'fitemcode' => ['ZZDUSKP2'], 'fsatuan' => [$besar], 'fqty' => [1], 'fprice' => [60000]]);
        $this->assertNotNull($this->header(self::KET . '_A'), $this->lastFlash());
        $this->assertEquals(5000, $hpp(), 'Stok kosong: HPP harus 60.000 / 12 = 5.000 per satuan kecil, bukan 60.000.');

        // stok ada (12 unit @5.000): tambah 1 besar seharga 72.000 -> (12x5.000 + 72.000) / 24 = 5.500
        $this->store(['fket' => self::KET . '_B', 'fitemcode' => ['ZZDUSKP2'], 'fsatuan' => [$besar], 'fqty' => [1], 'fprice' => [72000]]);
        $this->assertNotNull($this->header(self::KET . '_B'), $this->lastFlash());
        $this->assertEquals(5500, $hpp(), 'Stok ada: HPP rata-rata bergerak (cabang yang sudah benar) tidak boleh berubah.');
    }

    public function test_adjustment_posts_a_journal(): void
    {
        $this->store();
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());

        $lines = DB::table('jurnaldt')->where('frefno', $h->fstockmtno)->get();
        $this->assertGreaterThanOrEqual(2, $lines->count(), 'Adjustment stok mengubah nilai persediaan: jurnal (persediaan dan ADJUSTMENTSTOK) harus tercatat.');
        $this->assertEquals($lines->where('fdk', 'D')->sum('famount'), $lines->where('fdk', 'K')->sum('famount'));
        $this->assertEquals(25000, $lines->where('fdk', 'D')->where('faccount', '11510')->sum('famount'), 'Masuk: Dr PERSEDIAAN.');
        $this->assertEquals(25000, $lines->where('fdk', 'K')->where('faccount', '51400')->sum('famount'), 'Masuk: Cr ADJUSTMENTSTOK.');

        $this->store(['fket' => self::KET . '_OUT', 'ftrancode' => 'K', 'fqty' => [2]]);
        $out = $this->header(self::KET . '_OUT');
        $this->assertNotNull($out, $this->lastFlash());
        $lines = DB::table('jurnaldt')->where('frefno', $out->fstockmtno)->get();
        $this->assertEquals(10000, $lines->where('fdk', 'D')->where('faccount', '51400')->sum('famount'), 'Keluar: Dr ADJUSTMENTSTOK.');
        $this->assertEquals(10000, $lines->where('fdk', 'K')->where('faccount', '11510')->sum('famount'), 'Keluar: Cr PERSEDIAAN.');
    }

    public function test_an_order_without_any_valid_item_is_rejected(): void
    {
        $this->store(['fqty' => [0]]);
        $this->assertNull($this->header(), 'Adjustment tanpa item bernilai tidak boleh tersimpan.');
    }

    public function test_validation_fails(): void
    {
        $cases = [
            'tanggal kosong' => [['fstockmtdate' => ''], 'fstockmtdate'],
            'tanggal tidak valid' => [['fstockmtdate' => 'bukan-tanggal'], 'fstockmtdate'],
            'gudang kosong' => [['ffrom' => ''], 'ffrom'],
            'tanpa item' => [['fitemcode' => []], 'fitemcode'],
            'qty bukan angka' => [['fqty' => ['abc']], 'fqty.0'],
            'harga negatif' => [['fprice' => [-1]], 'fprice.0'],
            'keterangan lebih dari 50 karakter' => [['fket' => str_repeat('A', 51)], 'fket'],
        ];

        foreach ($cases as $label => [$override, $field]) {
            $this->store($override);
            $this->assertTrue($this->sessionHasError($field), "Kasus '$label': error '$field' tidak muncul. " . $this->lastFlash());
        }

        $this->assertNull($this->header(), 'Tidak boleh tersimpan saat validasi gagal.');
        $this->assertEquals(0, $this->saldo(), 'Saldo tidak boleh berubah.');
    }

    public function test_index_opens(): void
    {
        $this->get(route('adjstock.index'))->assertOk();
    }
}
