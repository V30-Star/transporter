<?php

namespace Tests\Http;

use Illuminate\Support\Facades\DB;
use Tests\Http\Concerns\MakesTestMaster;

class AssemblingHttpTest extends LiveDbTestCase
{
    use MakesTestMaster;

    private const KET = 'ZZ_DUSK_LHP';

    private string $bahan;

    private string $jadi;

    private string $unit;

    private string $gudang = 'ZZDUSKW1';

    protected function setUp(): void
    {
        parent::setUp();

        $this->grantPermissions(
            'viewAssembling', 'createAssembling', 'updateAssembling', 'deleteAssembling',
            'viewAdjstock', 'createAdjstock', 'createGudang'
        );

        $range = [now()->startOfDay(), now()->endOfDay()];
        $today = max(
            DB::table('trstockmt')->where('fstockmtcode', 'LHP')->whereBetween('fdatetime', $range)->count(),
            DB::table('trstockmt')->where('fstockmtcode', 'ADJ')->whereBetween('fdatetime', $range)->count(),
        );
        if ($today >= 11) {
            $this->markTestSkipped("Batas 15 dokumen/hari hampir tercapai ($today hari ini).");
        }

        $p1 = $this->makeProduct('ZZDUSKP1');
        $p2 = $this->makeProduct('ZZDUSKP2');
        $this->bahan = $p1->fprdcode;
        $this->jadi = $p2->fprdcode;
        $this->unit = trim((string) $p1->fsatuankecil);

        $this->atomic(fn () => $this->post(route('gudang.store'), [
            'fwhcode' => $this->gudang, 'fwhname' => 'ZZ GUDANG UJI', 'faddress' => 'ZZ', 'fbranchcode' => 'ZZCAB',
        ]));
        $this->assertTrue(DB::table('mswh')->where('fwhcode', $this->gudang)->exists(), 'Gudang uji gagal dibuat. ' . $this->lastFlash());

        $this->adjust('M', 6, $this->bahan, 'ZZ_DUSK_LHP_ADJ');
    }

    private function adjust(string $type, float $qty, string $product, string $ket): void
    {
        $this->atomic(fn () => $this->post(route('adjstock.store'), [
            'fstockmtdate' => now()->format('Y-m-d'), 'ffrom' => $this->gudang, 'ftrancode' => $type, 'fket' => $ket,
            'fbranchcode' => auth('sysuser')->user()->fcabang,
            'fitemcode' => [$product], 'fsatuan' => [$this->unit], 'fqty' => [$qty], 'fprice' => [5000], 'fdesc' => [''],
        ]));
        $this->assertTrue(DB::table('trstockmt')->where('fket', $ket)->exists(), "Adjustment $type uji gagal. " . $this->lastFlash());
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'fstockmtdate' => now()->format('Y-m-d'),
            'ffrom' => $this->gudang,
            'fket' => self::KET,
            'fbranchcode' => auth('sysuser')->user()->fcabang,
            'fitemcode' => [$this->bahan, $this->jadi],
            'fitemtype' => ['bahan_baku', 'barang_jadi'],
            'fsatuan' => [$this->unit, $this->unit],
            'fqty' => [4, 2],
            'fdesc' => ['', ''],
        ], $override);
    }

    private function store(array $override = [])
    {
        return $this->atomic(fn () => $this->post(route('assembling.store'), $this->payload($override)));
    }

    private function header(): ?object
    {
        return DB::table('trstockmt')->where('fstockmtcode', 'LHP')->where('fket', self::KET)->first();
    }

    private function saldo(string $code): float
    {
        return (float) DB::table('prdwh')->where('fprdcode', $code)->whereRaw('trim(fwhcode) = ?', [$this->gudang])->sum('fsaldo');
    }

    public function test_crud_assembling_consumes_materials_and_produces_goods(): void
    {
        $this->assertEquals(6, $this->saldo($this->bahan));
        $this->assertEquals(0, $this->saldo($this->jadi));

        $this->store();
        $h = $this->header();
        $this->assertNotNull($h, 'Assembling tidak tersimpan. ' . $this->lastFlash());
        $this->assertMatchesRegularExpression('/^LHP\.[A-Za-z0-9]+\.\d{4}\.\d{4}$/', $h->fstockmtno, 'Format nomor tidak sesuai: ' . $h->fstockmtno);
        $rows = DB::table('trstockdt')->where('fstockmtno', $h->fstockmtno)->get()->keyBy(fn ($r) => trim($r->fprdcode));
        $this->assertSame('B', trim($rows[$this->bahan]->fcode));
        $this->assertSame('J', trim($rows[$this->jadi]->fcode));

        $this->assertEquals(2, $this->saldo($this->bahan), 'Bahan baku 6 - 4 = 2.');
        $this->assertEquals(2, $this->saldo($this->jadi), 'Barang jadi 0 + 2 = 2.');

        $this->get(route('assembling.view', $h->fstockmtid))->assertOk();
        $this->get(route('assembling.edit', $h->fstockmtid))->assertOk();

        $this->atomic(fn () => $this->patch(route('assembling.update', $h->fstockmtid), $this->payload(['fqty' => [3, 1]])));
        $this->assertEquals(3, $this->saldo($this->bahan), 'Update: bahan 6 - 3. ' . $this->lastFlash());
        $this->assertEquals(1, $this->saldo($this->jadi), 'Update: barang jadi 1.');
        $this->assertTrue(DB::table('log_trstockmt')->where('fstockmtno', $h->fstockmtno)->where('feditmode', 'U')->exists(), 'Log update tidak tercatat.');

        $this->get(route('assembling.delete', $h->fstockmtid))->assertOk();
        $this->atomic(fn () => $this->deleteJson(route('assembling.destroy', $h->fstockmtid)));
        $this->assertNull($this->header(), 'Delete gagal. ' . $this->lastFlash());
        $this->assertEquals(6, $this->saldo($this->bahan), 'Hapus: bahan kembali 6.');
        $this->assertEquals(0, $this->saldo($this->jadi), 'Hapus: barang jadi kembali 0.');
        $this->assertTrue(DB::table('log_trstockmt')->where('fstockmtno', $h->fstockmtno)->where('feditmode', 'D')->exists(), 'Log delete tidak tercatat.');
    }

    public function test_using_more_materials_than_the_stock_needs_force_save(): void
    {
        $this->store(['fqty' => [7, 2]]);
        $this->assertNull($this->header(), 'Bahan baku 7 (stok 6) harus ditahan tanpa force_save. ' . $this->lastFlash());
        $this->assertEquals(6, $this->saldo($this->bahan));
        $this->assertEquals(0, $this->saldo($this->jadi));
    }

    public function test_editing_or_deleting_is_blocked_when_the_goods_were_already_used(): void
    {
        $this->store();
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());
        $this->adjust('K', 2, $this->jadi, 'ZZ_DUSK_LHP_OUT');
        $this->assertEquals(0, $this->saldo($this->jadi));

        $this->atomic(fn () => $this->patch(route('assembling.update', $h->fstockmtid), $this->payload(['fqty' => [2, 1]])));
        $this->assertEquals(2, $this->saldo($this->bahan), 'Mengurangi barang jadi yang sudah terpakai harus ditahan tanpa force_save. ' . $this->lastFlash());

        $this->atomic(fn () => $this->deleteJson(route('assembling.destroy', $h->fstockmtid)));
        $this->assertNotNull($this->header(), 'Menghapus assembling yang barang jadinya sudah terpakai harus ditahan tanpa force_save.');
    }

    public function test_every_row_needs_an_item_type(): void
    {
        $this->store(['fitemtype' => ['bahan_baku', '']]);
        $this->assertNull($this->header(), 'Baris tanpa tipe item (bahan/jadi) tidak mengubah stok sama sekali: harus ditolak. ' . $this->lastFlash());
        $this->assertEquals(6, $this->saldo($this->bahan));
    }

    public function test_it_needs_at_least_one_material_and_one_finished_good(): void
    {
        $this->store(['fitemcode' => [$this->bahan], 'fitemtype' => ['bahan_baku'], 'fsatuan' => [$this->unit], 'fqty' => [4], 'fdesc' => ['']]);
        $this->assertNull($this->header(), 'Assembling tanpa barang jadi harus ditolak. ' . $this->lastFlash());

        $this->store(['fitemcode' => [$this->jadi], 'fitemtype' => ['barang_jadi'], 'fsatuan' => [$this->unit], 'fqty' => [2], 'fdesc' => ['']]);
        $this->assertNull($this->header(), 'Assembling tanpa bahan baku harus ditolak. ' . $this->lastFlash());
        $this->assertEquals(0, $this->saldo($this->jadi));
    }

    public function test_the_finished_good_carries_the_material_cost(): void
    {
        $this->store();
        $this->assertNotNull($this->header(), $this->lastFlash());

        $hpp = DB::table('msprd')->where('fprdcode', $this->jadi)->value('fhpp');
        $this->assertEquals(10000, (float) $hpp, 'HPP barang jadi = nilai bahan baku (4 x 5000) / 2 unit jadi = 10000.');
    }

    public function test_assembling_posts_no_journal_because_it_is_neutral(): void
    {
        $this->store();
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());

        $this->assertSame(0, DB::table('jurnaldt')->where('frefno', $h->fstockmtno)->count(), 'Nilai hanya pindah antar persediaan: tidak ada jurnal.');
    }

    public function test_the_material_cost_is_recomputed_on_update(): void
    {
        $this->store();
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());

        $this->atomic(fn () => $this->patch(route('assembling.update', $h->fstockmtid), $this->payload(['fqty' => [3, 1]])));
        $good = DB::table('trstockdt')->where('fstockmtno', $h->fstockmtno)->whereRaw("trim(fprdcode) = ?", [$this->jadi])->first();
        $this->assertEquals(15000, (float) $good->ftotprice, 'Nilai barang jadi = bahan 3 x 5000. ' . $this->lastFlash());
    }

    public function test_an_order_without_any_valid_item_is_rejected(): void
    {
        $this->store(['fqty' => [0, 0]]);
        $this->assertNull($this->header(), 'Assembling tanpa item bernilai tidak boleh tersimpan.');
        $this->assertEquals(6, $this->saldo($this->bahan));
    }

    public function test_validation_fails(): void
    {
        $cases = [
            'tanggal kosong' => [['fstockmtdate' => ''], 'fstockmtdate'],
            'tanggal tidak valid' => [['fstockmtdate' => 'bukan-tanggal'], 'fstockmtdate'],
            'gudang kosong' => [['ffrom' => ''], 'ffrom'],
            'tanpa item' => [['fitemcode' => []], 'fitemcode'],
            'qty bukan angka' => [['fqty' => ['abc', 2]], 'fqty.0'],
            'tipe item tidak valid' => [['fitemtype' => ['bahan_baku', 'lain']], 'fitemtype.1'],
            'keterangan lebih dari 50 karakter' => [['fket' => str_repeat('A', 51)], 'fket'],
        ];

        foreach ($cases as $label => [$override, $field]) {
            $this->store($override);
            $this->assertTrue($this->sessionHasError($field), "Kasus '$label': error '$field' tidak muncul. " . $this->lastFlash());
        }

        $this->assertNull($this->header(), 'Tidak boleh tersimpan saat validasi gagal.');
        $this->assertEquals(6, $this->saldo($this->bahan), 'Stok tidak boleh berubah.');
    }

    public function test_index_opens(): void
    {
        $this->get(route('assembling.index'))->assertOk();
    }
}
