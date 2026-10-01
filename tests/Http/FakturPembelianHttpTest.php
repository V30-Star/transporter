<?php

namespace Tests\Http;

use App\Models\Tr_poh;
use Illuminate\Support\Facades\DB;
use Tests\Http\Concerns\MakesTestMaster;

class FakturPembelianHttpTest extends LiveDbTestCase
{
    use MakesTestMaster;

    private const KET = 'ZZ_DUSK_BUY';

    private string $supplier;

    private string $productCode;

    private string $unit;

    private string $gudang;

    private object $poh;

    private object $pod;

    private object $ter;

    private object $terDt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->grantPermissions(
            'viewFakturPembelian', 'createFakturPembelian', 'updateFakturPembelian', 'deleteFakturPembelian',
            'viewPenerimaanBarang', 'createPenerimaanBarang', 'updatePenerimaanBarang', 'deletePenerimaanBarang',
            'viewTr_poh', 'createTr_poh', 'updateTr_poh', 'deleteTr_poh',
            'createGudang',
        );
        $this->supplier = $this->makeSupplier();
        $product = $this->makeProduct();
        $this->productCode = $product->fprdcode;
        $this->unit = trim((string) $product->fsatuankecil);

        $range = [now()->startOfDay(), now()->endOfDay()];
        $today = max(
            Tr_poh::whereBetween('fdatetime', $range)->count(),
            DB::table('trstockmt')->whereIn('fstockmtcode', ['TER', 'BUY'])->whereBetween('fdatetime', $range)->count(),
        );
        if ($today >= 12) {
            $this->markTestSkipped("Batas harian dokumen hampir tercapai ($today hari ini).");
        }

        $this->gudang = $this->makeGudang();
        $this->makePoAndTer();
    }

    private function makeGudang(string $code = 'ZZDUSKW1'): string
    {
        $this->atomic(fn () => $this->post(route('gudang.store'), [
            'fwhcode' => $code, 'fwhname' => 'ZZ GUDANG UJI', 'faddress' => 'ZZ', 'fbranchcode' => 'ZZCAB',
        ]));
        $this->assertTrue(DB::table('mswh')->where('fwhcode', $code)->exists(), 'Gudang uji gagal dibuat. ' . $this->lastFlash());

        return $code;
    }

    /** PO uji (10 @ 5000) lalu penerimaan barang (6 @ 5000) yang merujuk PO itu. */
    private function makePoAndTer(): void
    {
        $branch = auth('sysuser')->user()->fcabang;

        $this->atomic(fn () => $this->post(route('tr_poh.store'), [
            'fpodate' => now()->format('Y-m-d'), 'fsupplier' => $this->supplier, 'fket' => 'ZZ_DUSK_PO_REF', 'fbranchcode' => $branch,
            'fitemcode' => [$this->productCode], 'fsatuan' => [$this->unit], 'fqty' => [10], 'fprice' => [5000],
            'fdisc' => ['0'], 'fnoacak' => ['123'], 'fdesc' => [''], 'frefdtno' => [''],
        ]));
        $this->poh = DB::table('tr_poh')->where('fket', 'ZZ_DUSK_PO_REF')->first();
        $this->assertNotNull($this->poh, 'PO uji gagal dibuat. ' . $this->lastFlash());
        $this->pod = DB::table('tr_pod')->where('fpono', $this->poh->fpono)->first();

        $this->atomic(fn () => $this->post(route('penerimaanbarang.store'), [
            'fstockmtdate' => now()->format('Y-m-d'), 'fsupplier' => $this->supplier, 'ffrom' => $this->gudang,
            'fket' => 'ZZ_DUSK_TER_REF', 'fbranchcode' => $branch,
            'fitemcode' => [$this->productCode], 'fsatuan' => [$this->unit], 'fpono' => [$this->poh->fpono],
            'frefdtid' => [$this->pod->fpodid], 'fqty' => [6], 'fprice' => [5000], 'fnoacak' => ['123'], 'frefnoacak' => ['123'], 'fdesc' => [''],
        ]));
        $this->ter = DB::table('trstockmt')->where('fket', 'ZZ_DUSK_TER_REF')->first();
        $this->assertNotNull($this->ter, 'Penerimaan barang uji gagal dibuat. ' . $this->lastFlash());
        $this->terDt = DB::table('trstockdt')->where('fstockmtno', $this->ter->fstockmtno)->first();
    }

    /** Payload faktur yang menagih penerimaan barang (sumber PB). */
    private function payload(array $override = []): array
    {
        return array_merge([
            'fstockmtdate' => now()->format('Y-m-d'),
            'fsupplier' => $this->supplier,
            'ffrom' => $this->gudang,
            'frefno' => 'ZZ-INV-001',
            'fket' => self::KET,
            'fbranchcode' => auth('sysuser')->user()->fcabang,
            'ftypebuy' => 0,
            'fitemcode' => [$this->productCode],
            'fsatuan' => [$this->unit],
            'fsource' => ['PB'],
            'frefdtid' => [$this->terDt->fstockdtid],
            'frefdtno' => [$this->ter->fstockmtno],
            'frefnoacak' => ['123'],
            'fqty' => [6],
            'fprice' => [5000],
            'fdiscpersen' => ['0'],
            'fbiaya' => [0],
            'fdesc' => [''],
        ], $override);
    }

    private function store(array $override = [])
    {
        return $this->atomic(fn () => $this->post(route('fakturpembelian.store'), $this->payload($override)));
    }

    private function header(string $ket = self::KET): ?object
    {
        return DB::table('trstockmt')->where('fstockmtcode', 'BUY')->where('fket', $ket)->first();
    }

    private function details(string $no)
    {
        return DB::table('trstockdt')->where('fstockmtno', $no)->get();
    }

    private function journalLines(string $no)
    {
        return DB::table('jurnaldt')->where('fjurnaltype', 'JBL')->where('fjurnalno', 'like', '%' . ltrim($no, '/.'))->get();
    }

    public function test_crud_billing_a_goods_receipt(): void
    {
        $this->store();
        $h = $this->header();
        $this->assertNotNull($h, 'Faktur pembelian tidak tersimpan. ' . $this->lastFlash());
        $this->assertMatchesRegularExpression('#^BUY/[A-Za-z0-9]+/\d{4}/\d{4}$#', $h->fstockmtno, 'Format nomor tidak sesuai: ' . $h->fstockmtno);
        $this->assertSame('ZZ-INV-001', trim($h->frefno));
        $this->assertEquals(30000, $h->famount);
        $this->assertEquals(30000, $h->famountmt);
        $this->assertEquals(30000, $h->famountremain, 'Sisa hutang awal harus sama dengan total faktur.');

        $rows = $this->details($h->fstockmtno);
        $this->assertCount(1, $rows);
        $this->assertSame('T', trim($rows[0]->fcode), 'Faktur dari penerimaan barang memakai kode detail T.');
        $this->assertEquals(6, $rows[0]->fqty);
        $this->assertEquals(5000, $rows[0]->fpricenet);
        $this->assertEquals(30000, $rows[0]->ftotprice);
        $this->assertEquals($this->terDt->fstockdtid, $rows[0]->frefdtid);

        $lines = $this->journalLines($h->fstockmtno);
        $this->assertCount(2, $lines, 'Jurnal faktur: faktur belum ditagih (D) dan hutang dagang (K).');
        $this->assertEquals(30000, $lines->where('fdk', 'D')->sum('famount'));
        $this->assertEquals(30000, $lines->where('fdk', 'K')->sum('famount'));

        $this->get(route('fakturpembelian.view', $h->fstockmtid))->assertOk();
        $this->get(route('fakturpembelian.edit', $h->fstockmtid))->assertOk();

        $this->atomic(fn () => $this->patch(route('fakturpembelian.update', $h->fstockmtid), $this->payload(['fqty' => [5]])));
        $updated = $this->header();
        $this->assertEquals(25000, $updated->famountmt ?? null, 'Total harus dihitung ulang saat update. ' . $this->lastFlash());
        $lines = $this->journalLines($updated->fstockmtno);
        $this->assertCount(2, $lines, 'Jurnal lama harus diganti, bukan ditambah.');
        $this->assertEquals(25000, $lines->where('fdk', 'K')->sum('famount'));

        $this->get(route('fakturpembelian.delete', $h->fstockmtid))->assertOk();
        $this->atomic(fn () => $this->deleteJson(route('fakturpembelian.destroy', $h->fstockmtid)));
        $this->assertNull($this->header(), 'Delete gagal. ' . $this->lastFlash());
        $this->assertCount(0, $this->details($h->fstockmtno));
        $this->assertCount(0, $this->journalLines($h->fstockmtno), 'Jurnal harus ikut terhapus.');
        $this->assertTrue(DB::table('log_trstockmt')->where('fstockmtno', $h->fstockmtno)->where('feditmode', 'D')->exists(), 'Log delete tidak tercatat.');
    }

    public function test_billing_a_purchase_order_directly_posts_purchases_account(): void
    {
        $this->store([
            'fsource' => ['PO'], 'frefdtid' => [$this->pod->fpodid], 'frefdtno' => [$this->poh->fpono],
            'fqty' => [4],
        ]);
        $h = $this->header();
        $this->assertNotNull($h, 'Faktur dari PO tidak tersimpan. ' . $this->lastFlash());
        $this->assertSame('P', trim($this->details($h->fstockmtno)->first()->fcode), 'Faktur dari PO memakai kode detail P.');
        $lines = $this->journalLines($h->fstockmtno);
        $this->assertEquals(20000, $lines->where('fdk', 'D')->sum('famount'));
        $this->assertEquals(20000, $lines->where('fdk', 'K')->sum('famount'));
    }

    public function test_ppn_requires_tax_invoice_and_is_journaled(): void
    {
        $this->store(['fapplyppn' => 1, 'famountpajak' => 3300]);
        $this->assertTrue($this->sessionHasError('frefpo'), 'Faktur dengan PPN wajib No. Faktur Pajak. ' . $this->lastFlash());
        $this->assertNull($this->header());

        $this->store(['fapplyppn' => 1, 'famountpajak' => 3300, 'frefpo' => '010.000-26.00000001']);
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());
        $this->assertMatchesRegularExpression('#^BUY\.[A-Za-z0-9]+\.\d{4}\.\d{4}$#', $h->fstockmtno, 'Nomor faktur dengan PPN memakai titik: ' . $h->fstockmtno);
        $this->assertEquals(3300, $h->famountpajak);
        $this->assertEquals(33300, $h->famountmt);

        $lines = $this->journalLines($h->fstockmtno);
        $this->assertCount(3, $lines, 'Jurnal dengan PPN: faktur belum ditagih, PPN beli, hutang.');
        $this->assertEquals(33300, $lines->where('fdk', 'D')->sum('famount'));
        $this->assertEquals(33300, $lines->where('fdk', 'K')->sum('famount'));
    }

    public function test_price_and_qty_cannot_exceed_the_referenced_receipt(): void
    {
        $this->store(['fprice' => [6000]]);
        $this->assertTrue($this->sessionHasError('fprice.0'), 'Harga di atas harga penerimaan harus ditolak. ' . $this->lastFlash());

        $this->store(['fqty' => [7]]);
        $this->assertTrue($this->sessionHasError('fqty.0'), 'Qty di atas qty penerimaan harus ditolak. ' . $this->lastFlash());

        $this->store(['frefdtid' => [999999999]]);
        $this->assertTrue($this->sessionHasError('fqty.0'), 'Referensi yang tidak ada harus ditolak. ' . $this->lastFlash());

        $this->assertNull($this->header());
    }

    public function test_billing_is_partial_until_the_receipt_is_fully_billed(): void
    {
        // Penerimaan 6 unit: tagih 4, lalu 2 (lunas) diterima; tagihan tambahan ditolak.
        $this->store(['fqty' => [4], 'frefno' => 'ZZ-INV-001']);
        $first = $this->header();
        $this->assertNotNull($first, 'Penagihan sebagian pertama harus diterima. ' . $this->lastFlash());

        $this->store(['fqty' => [3], 'fket' => self::KET . '_2', 'frefno' => 'ZZ-INV-002']);
        $this->assertNull($this->header(self::KET . '_2'), 'Tagihan 3 melebihi sisa 2 harus ditolak (dobel hutang).');
        $this->assertTrue($this->sessionHasError('fqty.0'), $this->lastFlash());

        $this->store(['fqty' => [2], 'fket' => self::KET . '_2', 'frefno' => 'ZZ-INV-002']);
        $this->assertNotNull($this->header(self::KET . '_2'), 'Tagihan sebesar sisa harus diterima. ' . $this->lastFlash());

        $this->store(['fqty' => [1], 'fket' => self::KET . '_3', 'frefno' => 'ZZ-INV-003']);
        $this->assertNull($this->header(self::KET . '_3'), 'Penerimaan yang sudah lunas ditagih tidak boleh ditagih lagi.');
    }

    public function test_editing_an_invoice_does_not_count_its_own_usage(): void
    {
        $this->store(['fqty' => [4], 'frefno' => 'ZZ-INV-001']);
        $first = $this->header();
        $this->assertNotNull($first, $this->lastFlash());

        // Naik dari 4 ke 6 (sisa penerimaan hanya 2 selain milik faktur ini) harus boleh.
        $this->atomic(fn () => $this->patch(route('fakturpembelian.update', $first->fstockmtid), $this->payload(['fqty' => [6]])));
        $this->assertEquals(30000, $this->header()->famountmt ?? null, 'Edit ke qty penuh harus diterima. ' . $this->lastFlash());

        // Tetapi 7 melebihi qty penerimaan.
        $this->atomic(fn () => $this->patch(route('fakturpembelian.update', $first->fstockmtid), $this->payload(['fqty' => [7]])));
        $this->assertEquals(30000, $this->header()->famountmt, 'Edit melebihi qty penerimaan harus ditolak. ' . $this->lastFlash());
        $this->assertTrue($this->sessionHasError('fqty.0'), $this->lastFlash());
    }

    public function test_goods_receipt_billed_by_invoice_is_locked(): void
    {
        $this->store();
        $this->assertNotNull($this->header(), $this->lastFlash());

        $this->atomic(fn () => $this->deleteJson(route('penerimaanbarang.destroy', $this->ter->fstockmtid)))->assertStatus(422);
        $this->assertTrue(DB::table('trstockmt')->where('fstockmtid', $this->ter->fstockmtid)->exists(), 'Penerimaan yang sudah ditagih tidak boleh terhapus.');
    }

    public function test_validation_fails(): void
    {
        $cases = [
            'tanggal kosong' => [['fstockmtdate' => ''], 'fstockmtdate'],
            'tanggal tidak valid' => [['fstockmtdate' => 'bukan-tanggal'], 'fstockmtdate'],
            'supplier kosong' => [['fsupplier' => ''], 'fsupplier'],
            'gudang kosong' => [['ffrom' => ''], 'ffrom'],
            'no faktur supplier kosong' => [['frefno' => ''], 'frefno'],
            'tanpa item' => [['fitemcode' => []], 'fitemcode'],
            'qty nol' => [['fqty' => [0]], 'fqty.0'],
            'qty bukan angka' => [['fqty' => ['abc']], 'fqty.0'],
            'harga bukan angka' => [['fprice' => ['abc']], 'fprice.0'],
            'format diskon salah' => [['fdiscpersen' => ['abc']], 'fdiscpersen.0'],
            'no acak referensi salah' => [['frefnoacak' => ['12']], 'frefnoacak.0'],
        ];

        foreach ($cases as $label => [$override, $field]) {
            $this->store($override);
            $this->assertTrue($this->sessionHasError($field), "Kasus '$label': error '$field' tidak muncul. " . $this->lastFlash());
        }

        $this->assertNull($this->header(), 'Tidak boleh tersimpan saat validasi gagal.');
    }

    public function test_index_opens(): void
    {
        $this->get(route('fakturpembelian.index'))->assertOk();
    }
}
