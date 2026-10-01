<?php

namespace Tests\Http;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Tests\Http\Concerns\MakesTestMaster;

class MutasiHttpTest extends LiveDbTestCase
{
    use MakesTestMaster;

    private const KET = 'ZZ_DUSK_MUT';

    private string $productCode;

    private string $unit;

    private string $branch;

    private string $gudangA = 'ZZDUSKW1';

    private string $gudangB = 'ZZDUSKW2';

    protected function setUp(): void
    {
        parent::setUp();

        $this->grantPermissions(
            'viewMutasi', 'createMutasi', 'updateMutasi', 'deleteMutasi',
            'viewAdjstock', 'createAdjstock', 'createGudang'
        );

        $range = [now()->startOfDay(), now()->endOfDay()];
        $today = max(
            DB::table('trstockmt')->where('fstockmtcode', 'MUT')->whereBetween('fdatetime', $range)->count(),
            DB::table('trstockmt')->where('fstockmtcode', 'ADJ')->whereBetween('fdatetime', $range)->count(),
        );
        if ($today >= 11) {
            $this->markTestSkipped("Batas 15 dokumen/hari hampir tercapai ($today hari ini).");
        }

        $this->branch = $this->userBranchCode();

        $product = $this->makeProduct();
        $this->productCode = $product->fprdcode;
        $this->unit = trim((string) $product->fsatuankecil);

        $this->makeWarehouse($this->gudangA);
        $this->makeWarehouse($this->gudangB);
        $this->adjustIn($this->gudangA, 6);
    }

    private function userBranchCode(): string
    {
        $raw = trim((string) auth('sysuser')->user()->fcabang);
        if (is_numeric($raw)) {
            return trim((string) DB::table('mscabang')->where('fcabangid', (int) $raw)->value('fcabangkode'));
        }

        return trim((string) (DB::table('mscabang')->whereRaw('LOWER(fcabangkode)=LOWER(?)', [$raw])->orWhereRaw('LOWER(fcabangname)=LOWER(?)', [$raw])->value('fcabangkode') ?: $raw));
    }

    private function makeWarehouse(string $code): void
    {
        $this->atomic(fn () => $this->post(route('gudang.store'), [
            'fwhcode' => $code, 'fwhname' => 'ZZ GUDANG ' . $code, 'faddress' => 'ZZ', 'fbranchcode' => $this->branch,
        ]));
        $this->assertTrue(DB::table('mswh')->where('fwhcode', $code)->exists(), "Gudang $code gagal dibuat. " . $this->lastFlash());
    }

    private function adjustIn(string $gudang, float $qty, ?string $product = null, ?string $unit = null): void
    {
        $this->atomic(fn () => $this->post(route('adjstock.store'), [
            'fstockmtdate' => now()->format('Y-m-d'), 'ffrom' => $gudang, 'ftrancode' => 'M', 'fket' => 'ZZ_DUSK_MUT_ADJ',
            'fbranchcode' => auth('sysuser')->user()->fcabang,
            'fitemcode' => [$product ?? $this->productCode], 'fsatuan' => [$unit ?? $this->unit], 'fqty' => [$qty], 'fprice' => [5000], 'fdesc' => [''],
        ]));
        $this->assertEquals($qty > 0 ? true : false, DB::table('trstockmt')->where('fket', 'ZZ_DUSK_MUT_ADJ')->exists(), 'Stok awal uji gagal dibuat. ' . $this->lastFlash());
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'fstockmtdate' => now()->format('Y-m-d'),
            'ffrom' => $this->gudangA,
            'fto' => $this->gudangB,
            'fket' => self::KET,
            'fbranchcode' => auth('sysuser')->user()->fcabang,
            'fitemcode' => [$this->productCode],
            'fsatuan' => [$this->unit],
            'fqty' => [4],
            'fprice' => [0],
            'fdesc' => [''],
        ], $override);
    }

    private function store(array $override = [])
    {
        return $this->atomic(fn () => $this->post(route('mutasi.store'), $this->payload($override)));
    }

    private function update(object $h, array $override = [])
    {
        return $this->atomic(fn () => $this->patch(route('mutasi.update', $h->fstockmtid), $this->payload($override)));
    }

    private function header(string $ket = self::KET): ?object
    {
        return DB::table('trstockmt')->where('fstockmtcode', 'MUT')->where('fket', $ket)->first();
    }

    private function saldo(string $gudang, ?string $code = null): float
    {
        return (float) DB::table('prdwh')->where('fprdcode', $code ?? $this->productCode)->whereRaw('trim(fwhcode) = ?', [$gudang])->sum('fsaldo');
    }

    private function totalStock(?string $code = null): float
    {
        return (float) DB::table('msprd')->where('fprdcode', $code ?? $this->productCode)->value('fstok');
    }

    public function test_crud_moves_stock_between_warehouses(): void
    {
        $this->assertEquals(6, $this->saldo($this->gudangA));

        $this->store();
        $h = $this->header();
        $this->assertNotNull($h, 'Mutasi tidak tersimpan. ' . $this->lastFlash());
        $this->assertMatchesRegularExpression('/^MUT\.[A-Za-z0-9]+\.\d{4}\.\d{4}$/', $h->fstockmtno, 'Format nomor tidak sesuai: ' . $h->fstockmtno);
        $this->assertSame($this->gudangA, trim($h->ffrom));
        $this->assertSame($this->gudangB, trim($h->fto));
        $this->assertCount(1, DB::table('trstockdt')->where('fstockmtno', $h->fstockmtno)->get());

        $this->assertEquals(2, $this->saldo($this->gudangA), 'Gudang asal 6 - 4 = 2.');
        $this->assertEquals(4, $this->saldo($this->gudangB), 'Gudang tujuan 0 + 4 = 4.');
        $this->assertEquals(6, $this->totalStock(), 'Mutasi tidak mengubah total stok produk.');

        $this->get(route('mutasi.view', $h->fstockmtid))->assertOk();
        $this->get(route('mutasi.edit', $h->fstockmtid))->assertOk();

        $this->update($h, ['fqty' => [3]]);
        $this->assertEquals(3, $this->saldo($this->gudangA), 'Update qty 3: asal 6 - 3.');
        $this->assertEquals(3, $this->saldo($this->gudangB), 'Update qty 3: tujuan 3.');
        $this->assertEquals(6, $this->totalStock());
        $this->assertTrue(DB::table('log_trstockmt')->where('fstockmtno', $h->fstockmtno)->where('feditmode', 'U')->exists(), 'Log update tidak tercatat. ' . $this->lastFlash());

        $this->get(route('mutasi.delete', $h->fstockmtid))->assertOk();
        $this->atomic(fn () => $this->deleteJson(route('mutasi.destroy', $h->fstockmtid)));
        $this->assertNull($this->header(), 'Delete gagal. ' . $this->lastFlash());
        $this->assertEquals(6, $this->saldo($this->gudangA), 'Hapus mutasi: asal kembali 6.');
        $this->assertEquals(0, $this->saldo($this->gudangB), 'Hapus mutasi: tujuan kembali 0.');
        $this->assertTrue(DB::table('log_trstockmt')->where('fstockmtno', $h->fstockmtno)->where('feditmode', 'D')->exists(), 'Log delete tidak tercatat.');
    }

    public function test_moving_more_than_the_source_stock_needs_force_save(): void
    {
        $this->store(['fqty' => [7]]);
        $this->assertNull($this->header(), 'Mutasi 7 unit (stok asal 6) harus ditahan tanpa force_save. ' . $this->lastFlash());
        $this->assertEquals(6, $this->saldo($this->gudangA));
        $this->assertEquals(0, $this->saldo($this->gudangB));
    }

    public function test_moving_in_a_bigger_unit_moves_the_small_unit_quantity(): void
    {
        [$kecil, $besar] = DB::table('mssatuan')->whereRaw('fsatuancode = trim(fsatuancode)')->limit(2)->pluck('fsatuancode')->all();
        $this->atomic(fn () => $this->post(route('product.store'), [
            'fprdcode' => 'ZZDUSKP2', 'fprdname' => 'ZZ_DUSK_PRODUK_BESAR', 'ftype' => 'Produk',
            'fgroupcode' => DB::table('ms_groupprd')->whereRaw('fgroupcode = trim(fgroupcode)')->value('fgroupcode'),
            'fmerek' => DB::table('msmerek')->whereRaw('fmerekcode = trim(fmerekcode)')->value('fmerekcode'),
            'fsatuankecil' => $kecil, 'fsatuanbesar' => $besar, 'fqtykecil' => 12, 'fsatuandefault' => '1',
        ]));
        $this->assertNotNull(Product::where('fprdcode', 'ZZDUSKP2')->first(), $this->lastFlash());
        $this->adjustIn($this->gudangA, 24, 'ZZDUSKP2', $kecil);

        $this->store(['fitemcode' => ['ZZDUSKP2'], 'fsatuan' => [$besar], 'fqty' => [1]]);
        $this->assertNotNull($this->header(), $this->lastFlash());
        $this->assertEquals(12, $this->saldo($this->gudangA, 'ZZDUSKP2'), 'Mutasi 1 satuan besar = 12 unit: asal 24 - 12.');
        $this->assertEquals(12, $this->saldo($this->gudangB, 'ZZDUSKP2'), 'Tujuan 12.');
    }

    public function test_the_two_warehouses_must_differ(): void
    {
        $this->store(['fto' => $this->gudangA]);
        $this->assertNull($this->header());
        $this->assertTrue($this->sessionHasError('fto'), $this->lastFlash());
        $this->assertEquals(6, $this->saldo($this->gudangA));
    }

    public function test_the_source_warehouse_must_belong_to_the_user_branch(): void
    {
        $this->atomic(fn () => $this->post(route('gudang.store'), [
            'fwhcode' => 'ZZDUSKW3', 'fwhname' => 'ZZ GUDANG LUAR', 'faddress' => 'ZZ', 'fbranchcode' => 'ZZCAB',
        ]));
        $this->assertTrue(DB::table('mswh')->where('fwhcode', 'ZZDUSKW3')->exists(), $this->lastFlash());

        $this->store(['ffrom' => 'ZZDUSKW3']);
        $this->assertNull($this->header(), 'Gudang asal di luar cabang user harus ditolak.');
        $this->assertTrue($this->sessionHasError('ffrom'), $this->lastFlash());
    }

    public function test_an_unknown_destination_warehouse_is_rejected(): void
    {
        $this->store(['fto' => 'ZZNOWH']);
        $this->assertNull($this->header());
        $this->assertTrue($this->sessionHasError('fto'), $this->lastFlash());
    }

    public function test_editing_or_deleting_is_blocked_when_the_destination_stock_was_already_moved_on(): void
    {
        $this->makeWarehouse('ZZDUSKW3');
        $this->store(['fqty' => [6]]);
        $first = $this->header();
        $this->assertNotNull($first, $this->lastFlash());

        $this->store(['fket' => self::KET . '_2', 'ffrom' => $this->gudangB, 'fto' => 'ZZDUSKW3', 'fqty' => [4]]);
        $this->assertNotNull($this->header(self::KET . '_2'), 'Mutasi lanjutan gagal. ' . $this->lastFlash());
        $this->assertEquals(2, $this->saldo($this->gudangB));

        $this->update($first, ['fqty' => [1]]);
        $this->assertEquals(2, $this->saldo($this->gudangB), 'Mengurangi mutasi pertama menjadi 1 membuat gudang tujuan 1 - 4 = -3: harus ditahan tanpa force_save. ' . $this->lastFlash());

        $this->atomic(fn () => $this->deleteJson(route('mutasi.destroy', $first->fstockmtid)));
        $this->assertNotNull($this->header(), 'Menghapus mutasi pertama membuat gudang tujuan minus: harus ditahan tanpa force_save.');
        $this->assertEquals(2, $this->saldo($this->gudangB));
    }

    public function test_an_order_without_any_valid_item_is_rejected(): void
    {
        $this->store(['fqty' => [0]]);
        $this->assertNull($this->header(), 'Mutasi tanpa item bernilai tidak boleh tersimpan.');
        $this->assertEquals(6, $this->saldo($this->gudangA));
    }

    public function test_validation_fails(): void
    {
        $cases = [
            'tanggal kosong' => [['fstockmtdate' => ''], 'fstockmtdate'],
            'tanggal tidak valid' => [['fstockmtdate' => 'bukan-tanggal'], 'fstockmtdate'],
            'gudang asal kosong' => [['ffrom' => ''], 'ffrom'],
            'gudang tujuan kosong' => [['fto' => ''], 'fto'],
            'tanpa item' => [['fitemcode' => []], 'fitemcode'],
            'qty bukan angka' => [['fqty' => ['abc']], 'fqty.0'],
            'keterangan lebih dari 50 karakter' => [['fket' => str_repeat('A', 51)], 'fket'],
        ];

        foreach ($cases as $label => [$override, $field]) {
            $this->store($override);
            $this->assertTrue($this->sessionHasError($field), "Kasus '$label': error '$field' tidak muncul. " . $this->lastFlash());
        }

        $this->assertNull($this->header(), 'Tidak boleh tersimpan saat validasi gagal.');
        $this->assertEquals(6, $this->saldo($this->gudangA), 'Saldo tidak boleh berubah.');
    }

    public function test_index_opens(): void
    {
        $this->get(route('mutasi.index'))->assertOk();
    }
}
