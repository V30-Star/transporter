<?php

namespace Tests\Http;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Tests\Http\Concerns\MakesTestMaster;

class PemakaianBarangHttpTest extends LiveDbTestCase
{
    use MakesTestMaster;

    private const KET = 'ZZ_DUSK_PBR';

    private string $productCode;

    private string $unit;

    private string $gudang = 'ZZDUSKW1';

    private string $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->grantPermissions(
            'viewPemakaianbarang', 'createPemakaianbarang', 'updatePemakaianbarang', 'deletePemakaianbarang',
            'viewAdjstock', 'createAdjstock', 'createGudang', 'createAccount'
        );

        $range = [now()->startOfDay(), now()->endOfDay()];
        $today = max(
            DB::table('trstockmt')->where('fstockmtcode', 'PBR')->whereBetween('fdatetime', $range)->count(),
            DB::table('trstockmt')->where('fstockmtcode', 'ADJ')->whereBetween('fdatetime', $range)->count(),
        );
        if ($today >= 11) {
            $this->markTestSkipped("Batas 15 dokumen/hari hampir tercapai ($today hari ini).");
        }

        $product = $this->makeProduct();
        $this->productCode = $product->fprdcode;
        $this->unit = trim((string) $product->fsatuankecil);
        $this->account = $this->makeDetailAccount('ZZDUSKD1', 'D');

        $this->atomic(fn () => $this->post(route('gudang.store'), [
            'fwhcode' => $this->gudang, 'fwhname' => 'ZZ GUDANG UJI', 'faddress' => 'ZZ', 'fbranchcode' => 'ZZCAB',
        ]));
        $this->assertTrue(DB::table('mswh')->where('fwhcode', $this->gudang)->exists(), 'Gudang uji gagal dibuat. ' . $this->lastFlash());

        $this->adjustIn(6);
    }

    private function adjustIn(float $qty, ?string $product = null, ?string $unit = null): void
    {
        $this->atomic(fn () => $this->post(route('adjstock.store'), [
            'fstockmtdate' => now()->format('Y-m-d'), 'ffrom' => $this->gudang, 'ftrancode' => 'M', 'fket' => 'ZZ_DUSK_PBR_ADJ',
            'fbranchcode' => auth('sysuser')->user()->fcabang,
            'fitemcode' => [$product ?? $this->productCode], 'fsatuan' => [$unit ?? $this->unit], 'fqty' => [$qty], 'fprice' => [5000], 'fdesc' => [''],
        ]));
        $this->assertTrue(DB::table('trstockmt')->where('fket', 'ZZ_DUSK_PBR_ADJ')->exists(), 'Stok awal uji gagal dibuat. ' . $this->lastFlash());
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'fstockmtdate' => now()->format('Y-m-d'),
            'ffrom' => $this->gudang,
            'fket' => self::KET,
            'fbranchcode' => auth('sysuser')->user()->fcabang,
            'fitemcode' => [$this->productCode],
            'fsatuan' => [$this->unit],
            'frefdtno' => [$this->account],
            'frefso' => [''],
            'fqty' => [2],
            'fdesc' => [''],
        ], $override);
    }

    private function store(array $override = [])
    {
        return $this->atomic(fn () => $this->post(route('pemakaianbarang.store'), $this->payload($override)));
    }

    private function header(): ?object
    {
        return DB::table('trstockmt')->where('fstockmtcode', 'PBR')->where('fket', self::KET)->first();
    }

    private function saldo(?string $code = null): float
    {
        return (float) DB::table('prdwh')->where('fprdcode', $code ?? $this->productCode)->whereRaw('trim(fwhcode) = ?', [$this->gudang])->sum('fsaldo');
    }

    public function test_crud_usage_reduces_stock(): void
    {
        $this->assertEquals(6, $this->saldo());

        $this->store();
        $h = $this->header();
        $this->assertNotNull($h, 'Pemakaian barang tidak tersimpan. ' . $this->lastFlash());
        $this->assertMatchesRegularExpression('/^PBR\.[A-Za-z0-9]+\.\d{4}\.\d{4}$/', $h->fstockmtno, 'Format nomor tidak sesuai: ' . $h->fstockmtno);
        $rows = DB::table('trstockdt')->where('fstockmtno', $h->fstockmtno)->get();
        $this->assertCount(1, $rows);
        $this->assertSame($this->account, trim($rows[0]->frefdtno), 'Account biaya tersimpan di frefdtno.');
        $this->assertEquals(2, $rows[0]->fqtykecil);

        $this->assertEquals(4, $this->saldo(), 'Pemakaian 2 unit: stok 6 - 2.');
        $this->assertEquals(4, (float) DB::table('msprd')->where('fprdcode', $this->productCode)->value('fstok'));

        $this->get(route('pemakaianbarang.view', $h->fstockmtid))->assertOk();
        $this->get(route('pemakaianbarang.edit', $h->fstockmtid))->assertOk();

        $this->atomic(fn () => $this->patch(route('pemakaianbarang.update', $h->fstockmtid), $this->payload(['fqty' => [3]])));
        $this->assertEquals(3, $this->saldo(), 'Update qty 3: stok 6 - 3. ' . $this->lastFlash());
        $this->assertTrue(DB::table('log_trstockmt')->where('fstockmtno', $h->fstockmtno)->where('feditmode', 'U')->exists(), 'Log update tidak tercatat.');

        $this->get(route('pemakaianbarang.delete', $h->fstockmtid))->assertOk();
        $this->atomic(fn () => $this->deleteJson(route('pemakaianbarang.destroy', $h->fstockmtid)));
        $this->assertNull($this->header(), 'Delete gagal. ' . $this->lastFlash());
        $this->assertEquals(6, $this->saldo(), 'Hapus pemakaian: stok kembali 6.');
        $this->assertTrue(DB::table('log_trstockmt')->where('fstockmtno', $h->fstockmtno)->where('feditmode', 'D')->exists(), 'Log delete tidak tercatat.');
    }

    public function test_using_more_than_the_stock_needs_force_save(): void
    {
        $this->store(['fqty' => [7]]);
        $this->assertNull($this->header(), 'Pemakaian 7 unit (stok 6) harus ditahan tanpa force_save. ' . $this->lastFlash());
        $this->assertEquals(6, $this->saldo());
    }

    public function test_editing_to_a_bigger_qty_than_the_stock_needs_force_save(): void
    {
        $this->store();
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());

        $this->atomic(fn () => $this->patch(route('pemakaianbarang.update', $h->fstockmtid), $this->payload(['fqty' => [7]])));
        $this->assertEquals(4, $this->saldo(), 'Update jadi 7 unit (stok 6 + 2 lama) harus ditahan tanpa force_save. ' . $this->lastFlash());
    }

    public function test_using_in_a_bigger_unit_reduces_the_small_unit_quantity(): void
    {
        [$kecil, $besar] = DB::table('mssatuan')->whereRaw('fsatuancode = trim(fsatuancode)')->limit(2)->pluck('fsatuancode')->all();
        $this->atomic(fn () => $this->post(route('product.store'), [
            'fprdcode' => 'ZZDUSKP2', 'fprdname' => 'ZZ_DUSK_PRODUK_BESAR', 'ftype' => 'Produk',
            'fgroupcode' => DB::table('ms_groupprd')->whereRaw('fgroupcode = trim(fgroupcode)')->value('fgroupcode'),
            'fmerek' => DB::table('msmerek')->whereRaw('fmerekcode = trim(fmerekcode)')->value('fmerekcode'),
            'fsatuankecil' => $kecil, 'fsatuanbesar' => $besar, 'fqtykecil' => 12, 'fsatuandefault' => '1',
        ]));
        $this->assertNotNull(Product::where('fprdcode', 'ZZDUSKP2')->first(), $this->lastFlash());
        $this->atomic(fn () => DB::table('trstockmt')->where('fket', 'ZZ_DUSK_PBR_ADJ')->update(['fket' => 'ZZ_DUSK_PBR_ADJ0']));
        $this->adjustIn(24, 'ZZDUSKP2', $kecil);

        $this->store(['fitemcode' => ['ZZDUSKP2'], 'fsatuan' => [$besar], 'fqty' => [1]]);
        $this->assertNotNull($this->header(), $this->lastFlash());
        $this->assertEquals(12, $this->saldo('ZZDUSKP2'), 'Pemakaian 1 satuan besar = 12 unit: 24 - 12.');
    }

    public function test_the_expense_account_must_exist(): void
    {
        $this->store(['frefdtno' => ['ZZNOACC']]);
        $this->assertNull($this->header(), 'Account biaya yang tidak ada di master account harus ditolak. ' . $this->lastFlash());
        $this->assertEquals(6, $this->saldo());
    }

    public function test_the_expense_account_is_required_and_must_be_an_active_detail_account(): void
    {
        $this->store(['frefdtno' => ['']]);
        $this->assertNull($this->header(), 'Account kosong harus ditolak. ' . $this->lastFlash());
        $this->assertTrue($this->sessionHasError('frefdtno'), $this->lastFlash());

        $headerAccount = trim((string) DB::table('account')->where('fend', 0)->where('fnonactive', '0')->value('faccount'));
        if ($headerAccount !== '') {
            $this->store(['frefdtno' => [$headerAccount]]);
            $this->assertNull($this->header(), 'Akun header (bukan detail) harus ditolak.');
        }

        $this->assertEquals(6, $this->saldo());
    }

    public function test_usage_posts_a_journal_valued_at_hpp(): void
    {
        $hpp = (float) DB::table('msprd')->where('fprdcode', $this->productCode)->value('fhpp');
        $this->assertGreaterThan(0, $hpp, 'HPP produk uji harus terisi dari penambahan stok awal.');

        $this->store();
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());

        $lines = DB::table('jurnaldt')->where('frefno', $h->fstockmtno)->get();
        $this->assertEquals(2 * $hpp, $lines->where('fdk', 'D')->where('faccount', $this->account)->sum('famount'), 'Dr account biaya = qty x HPP.');
        $this->assertEquals(2 * $hpp, $lines->where('fdk', 'K')->where('faccount', '11510')->sum('famount'), 'Cr PERSEDIAAN = qty x HPP.');

        $this->atomic(fn () => $this->patch(route('pemakaianbarang.update', $h->fstockmtid), $this->payload(['fqty' => [3]])));
        $lines = DB::table('jurnaldt')->where('frefno', $h->fstockmtno)->get();
        $this->assertEquals(3 * $hpp, $lines->where('fdk', 'D')->sum('famount'), 'Jurnal dihitung ulang saat update.');
        $this->assertEquals($lines->where('fdk', 'D')->sum('famount'), $lines->where('fdk', 'K')->sum('famount'));

        $this->atomic(fn () => $this->deleteJson(route('pemakaianbarang.destroy', $h->fstockmtid)));
        $this->assertSame(0, DB::table('jurnaldt')->where('frefno', $h->fstockmtno)->count(), 'Jurnal ikut terhapus.');
    }

    public function test_an_order_without_any_valid_item_is_rejected(): void
    {
        $this->store(['fqty' => [0]]);
        $this->assertNull($this->header(), 'Pemakaian tanpa item bernilai tidak boleh tersimpan.');
        $this->assertEquals(6, $this->saldo());
    }

    public function test_validation_fails(): void
    {
        $cases = [
            'tanggal kosong' => [['fstockmtdate' => ''], 'fstockmtdate'],
            'tanggal tidak valid' => [['fstockmtdate' => 'bukan-tanggal'], 'fstockmtdate'],
            'gudang kosong' => [['ffrom' => ''], 'ffrom'],
            'tanpa item' => [['fitemcode' => []], 'fitemcode'],
            'qty bukan angka' => [['fqty' => ['abc']], 'fqty.0'],
            'keterangan lebih dari 500 karakter' => [['fket' => str_repeat('A', 501)], 'fket'],
        ];

        foreach ($cases as $label => [$override, $field]) {
            $this->store($override);
            $this->assertTrue($this->sessionHasError($field), "Kasus '$label': error '$field' tidak muncul. " . $this->lastFlash());
        }

        $this->assertNull($this->header(), 'Tidak boleh tersimpan saat validasi gagal.');
        $this->assertEquals(6, $this->saldo(), 'Stok tidak boleh berubah.');
    }

    public function test_index_opens(): void
    {
        $this->get(route('pemakaianbarang.index'))->assertOk();
    }
}
