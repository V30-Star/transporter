<?php

namespace Tests\Http;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Tests\Http\Concerns\MakesPurchaseChain;

class PenjualanRetailHttpTest extends LiveDbTestCase
{
    use MakesPurchaseChain;

    private const KET = 'ZZ_DUSK_RETAIL';

    private string $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->grantPermissions('viewPenjualanRetail', 'createPenjualanRetail', 'updatePenjualanRetail', 'deletePenjualanRetail');

        $today = DB::table('tranmt')->where('ftrcode', 'INV')->whereBetween('fdatetime', [now()->startOfDay(), now()->endOfDay()])->count();
        if ($today >= 10) {
            $this->markTestSkipped("Batas harian dokumen hampir tercapai ($today hari ini).");
        }

        $this->setUpPurchaseChain();   // stok 6 unit di gudang uji
        $this->customer = $this->makeCustomer();
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'fsodate' => now()->format('Y-m-d'),
            'fcustno' => $this->customer,
            'fwhcode' => $this->gudang,
            'fket' => self::KET,
            'fbranchcode' => auth('sysuser')->user()->fcabang,
            'ftypesales' => 0,
            'fitemcode' => [$this->productCode],
            'fsatuan' => [$this->unit],
            'fqty' => [2],
            'fprice' => [10000],
            'fdisc' => ['0'],
            'fnoacak' => ['123'],
            'fdesc' => [''],
            'ftunai' => 1,
        ], $override);
    }

    private function setTunaiPermission(bool $can): void
    {
        $role = \App\Models\RoleAccess::where('fusercreate', auth('sysuser')->user()->fuid)->firstOrFail();
        $list = array_values(array_filter(array_map('trim', explode(',', (string) $role->fpermission)), fn ($p) => strtolower($p) !== 'bolehpenjualantunai'));
        if ($can) {
            $list[] = 'BolehPenjualanTunai';
        }
        $role->fpermission = implode(',', $list);
        $role->save();
        session(['user_restricted_permissions' => $role->fpermission]);
    }

    private function setAutoTunai(int $value): void
    {
        DB::table('set_default')->updateOrInsert(['fdefaultname' => 'DefaultAutoTunai'], ['fdefaultvalue' => $value]);
    }

    private function unsetKey(array $payload, string $key): array
    {
        unset($payload[$key]);

        return $payload;
    }

    private function store(array $override = [])
    {
        return $this->atomic(fn () => $this->post(route('penjualanretail.store'), $this->payload($override)));
    }

    private function header(string $ket = self::KET): ?object
    {
        return DB::table('tranmt')->where('ftrcode', 'INV')->where('fket', $ket)->first();
    }

    private function details(string $fsono)
    {
        return DB::table('trandt')->where('fsono', $fsono)->get();
    }

    private function journalLines(string $fsono)
    {
        return DB::table('jurnaldt')->where('fjurnaltype', 'SLS')->whereIn('fjurnalno', ['JV/' . ltrim($fsono, '/'), 'JV.' . ltrim($fsono, '.')])->get();
    }

    private function saldo(?string $code = null): float
    {
        return (float) DB::table('prdwh')->where('fprdcode', $code ?? $this->productCode)->where('fwhcode', $this->gudang)->sum('fsaldo');
    }

    public function test_crud_retail_sale_reduces_stock_and_journals_cash(): void
    {
        $kas = $this->makeCashAccount();
        $this->assertEquals(6, $this->saldo());

        $this->store(['faccount_pembayaran' => $kas]);
        $h = $this->header();
        $this->assertNotNull($h, 'Penjualan retail tidak tersimpan. ' . $this->lastFlash());
        $this->assertEquals(1, $h->ftunai, 'Penjualan retail selalu tunai.');
        $this->assertEquals(0, $h->famountremain, 'Penjualan retail tidak menyisakan piutang.');
        $this->assertEquals(0, $h->fgrosir, 'Penjualan retail bertanda fgrosir = 0.');
        $this->assertSame($this->gudang, trim($h->fwhcode));
        $this->assertEquals(20000, $h->famountso);
        $this->assertEquals(1, $h->fapproval, 'Penjualan retail langsung disetujui.');

        $this->assertEquals(4, $this->saldo(), 'Penjualan 2 unit harus mengurangi stok gudang dari 6 menjadi 4.');
        $this->assertEquals(4, (float) DB::table('msprd')->where('fprdcode', $this->productCode)->value('fstok'), 'msprd.fstok ikut turun menjadi 4.');

        $lines = $this->journalLines($h->fsono);
        $this->assertEquals(20000, $lines->where('fdk', 'D')->where('faccount', $kas)->sum('famount'), 'Penjualan tunai mendebit account kas yang dipilih.');
        $this->assertEquals($lines->where('fdk', 'D')->sum('famount'), $lines->where('fdk', 'K')->sum('famount'), 'Jurnal harus seimbang.');

        $this->get(route('penjualanretail.view', $h->ftranmtid))->assertOk();
        $this->get(route('penjualanretail.edit', $h->ftranmtid))->assertOk();

        $this->atomic(fn () => $this->patch(route('penjualanretail.update', $h->ftranmtid), $this->payload(['fqty' => [3], 'faccount_pembayaran' => $kas])));
        $this->assertEquals(30000, $this->header()->famountso ?? null, 'Total harus dihitung ulang saat update. ' . $this->lastFlash());
        $this->assertEquals(3, $this->saldo(), 'Stok harus mengikuti qty terbaru (6 - 3).');

        $this->get(route('penjualanretail.delete', $h->ftranmtid))->assertOk();
        $this->atomic(fn () => $this->deleteJson(route('penjualanretail.destroy', $h->ftranmtid)));
        $this->assertNull($this->header(), 'Delete gagal. ' . $this->lastFlash());
        $this->assertCount(0, $this->journalLines($h->fsono), 'Jurnal harus ikut terhapus.');
        $this->assertEquals(6, $this->saldo(), 'Menghapus penjualan harus mengembalikan stok ke 6.');
    }

    public function test_the_cash_checkbox_is_honored_for_a_user_who_may_sell_cash(): void
    {
        $this->setTunaiPermission(true);

        $this->atomic(fn () => $this->post(route('penjualanretail.store'), $this->unsetKey($this->payload(), 'ftunai')));
        $h = $this->header();
        $this->assertNotNull($h, 'Penjualan retail non-tunai tidak tersimpan. ' . $this->lastFlash());
        $this->assertEquals(0, $h->ftunai, 'Cash tidak dicentang: non-tunai.');
        $this->assertEquals(20000, $h->famountremain, 'Non-tunai menyisakan piutang sebesar total.');
        $this->assertEquals(4, $this->saldo(), 'Stok tetap berkurang pada penjualan retail non-tunai.');
    }

    public function test_a_user_without_the_cash_permission_always_sells_cash(): void
    {
        $this->setTunaiPermission(false);

        $this->atomic(fn () => $this->post(route('penjualanretail.store'), $this->unsetKey($this->payload(), 'ftunai')));
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());
        $this->assertEquals(1, $h->ftunai, 'Tanpa izin BolehPenjualanTunai (checkbox tersembunyi), tetap tunai.');
        $this->assertEquals(0, $h->famountremain);
    }

    public function test_the_cash_checkbox_default_follows_set_default(): void
    {
        $this->setTunaiPermission(true);

        $this->setAutoTunai(1);
        $this->assertMatchesRegularExpression('/id="ftunai"[^>]*checked/s', $this->get(route('penjualanretail.create'))->getContent(), 'DefaultAutoTunai = 1: Cash tercentang.');

        $this->setAutoTunai(0);
        $this->assertDoesNotMatchRegularExpression('/id="ftunai"[^>]*checked/s', $this->get(route('penjualanretail.create'))->getContent(), 'DefaultAutoTunai = 0: Cash tidak tercentang.');
    }

    public function test_the_warehouse_is_required(): void
    {
        $this->store(['fwhcode' => '']);
        $this->assertNull($this->header(), 'Penjualan retail tanpa gudang harus ditolak.');
        $this->assertTrue($this->sessionHasError('fwhcode'), $this->lastFlash());
    }

    public function test_selling_more_than_the_stock_is_allowed_only_when_negative_stock_is_enabled(): void
    {
        if (trim((string) DB::table('setini')->value('fstokbolehminus')) !== '1') {
            $this->markTestSkipped('Pengaturan stok boleh minus mati.');
        }

        $this->store(['fqty' => [7]]);
        $this->assertNotNull($this->header(), 'Stok boleh minus: penjualan 7 unit (stok 6) harus tersimpan. ' . $this->lastFlash());
        $this->assertEquals(-1, $this->saldo(), 'Saldo gudang 6 - 7 = -1.');
    }

    public function test_selling_in_a_bigger_unit_reduces_stock_by_the_small_unit_quantity(): void
    {
        [$kecil, $besar] = DB::table('mssatuan')->whereRaw('fsatuancode = trim(fsatuancode)')->limit(2)->pluck('fsatuancode')->all();
        $this->grantPermissions('createProduct');
        $this->atomic(fn () => $this->post(route('product.store'), [
            'fprdcode' => 'ZZDUSKP2', 'fprdname' => 'ZZ_DUSK_PRODUK_BESAR', 'ftype' => 'Produk',
            'fgroupcode' => DB::table('ms_groupprd')->whereRaw('fgroupcode = trim(fgroupcode)')->value('fgroupcode'),
            'fmerek' => DB::table('msmerek')->whereRaw('fmerekcode = trim(fmerekcode)')->value('fmerekcode'),
            'fsatuankecil' => $kecil, 'fsatuanbesar' => $besar, 'fqtykecil' => 12, 'fsatuandefault' => '1',
        ]));
        $this->assertNotNull(Product::where('fprdcode', 'ZZDUSKP2')->first(), 'Produk satuan besar uji gagal dibuat. ' . $this->lastFlash());

        $this->store(['fitemcode' => ['ZZDUSKP2'], 'fsatuan' => [$besar], 'fqty' => [1], 'fprice' => [120000]]);
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());
        $row = $this->details($h->fsono)->first();
        $this->assertEquals(1, $row->fqty);
        $this->assertEquals(12, $row->fqtykecil, 'Satu satuan besar = 12 satuan kecil.');
        $this->assertEquals(-12, $this->saldo('ZZDUSKP2'), 'Menjual 1 satuan besar (12 unit) harus mengurangi stok 12 unit, bukan 1.');
    }

    public function test_ppn_and_discount_are_calculated(): void
    {
        $this->store(['fapplyppn' => 1, 'fppnpersen' => 11, 'fdisc' => ['10']]);
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());
        $this->assertEquals(18000, $h->famountsonet, '20.000 dikurangi diskon baris 10%.');
        $this->assertEquals(1980, $h->famountpajak);
        $this->assertEquals(19980, $h->famountso);
    }

    public function test_an_order_without_any_valid_item_is_rejected(): void
    {
        $this->store(['fqty' => [0]]);
        $this->assertNull($this->header(), 'Penjualan tanpa item bernilai tidak boleh tersimpan. ' . $this->lastFlash());
    }

    public function test_validation_fails(): void
    {
        $cases = [
            'tanggal kosong' => [['fsodate' => ''], 'fsodate'],
            'tanggal tidak valid' => [['fsodate' => 'bukan-tanggal'], 'fsodate'],
            'customer kosong' => [['fcustno' => ''], 'fcustno'],
            'tanpa item' => [['fitemcode' => []], 'fitemcode'],
            'qty di bawah 0,01' => [['fqty' => [0]], 'fqty.0'],
            'qty bukan angka' => [['fqty' => ['abc']], 'fqty.0'],
            'diskon header di atas 100' => [['fdiscpersen' => 150], 'fdiscpersen'],
            'no acak bukan 3 digit 1-9' => [['fnoacak' => ['120']], 'fnoacak.0'],
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
        $this->get(route('penjualanretail.index'))->assertOk();
    }
}
