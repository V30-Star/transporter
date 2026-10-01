<?php

namespace Tests\Http;

use Illuminate\Support\Facades\DB;
use Tests\Http\Concerns\MakesPurchaseChain;

class ReturPembelianHttpTest extends LiveDbTestCase
{
    use MakesPurchaseChain;

    private const KET = 'ZZ_DUSK_REB';

    protected function setUp(): void
    {
        parent::setUp();

        $this->grantPermissions('viewReturPembelian', 'createReturPembelian', 'updateReturPembelian', 'deleteReturPembelian');
        $this->setUpPurchaseChain();
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'fstockmtdate' => now()->format('Y-m-d'),
            'fsupplier' => $this->supplier,
            'ffrom' => $this->gudang,
            'fket' => self::KET,
            'fbranchcode' => auth('sysuser')->user()->fcabang,
            'frefno' => $this->buy->fstockmtno,
            'fitemcode' => [$this->productCode],
            'fsatuan' => [$this->unit],
            'frefdtno' => [$this->buy->fstockmtno],
            'fqty' => [2],
            'fprice' => [5000],
            'fdesc' => [''],
        ], $override);
    }

    private function store(array $override = [])
    {
        return $this->atomic(fn () => $this->post(route('returpembelian.store'), $this->payload($override)));
    }

    private function header(string $ket = self::KET): ?object
    {
        return DB::table('trstockmt')->where('fstockmtcode', 'REB')->where('fket', $ket)->first();
    }

    private function details(string $no)
    {
        return DB::table('trstockdt')->where('fstockmtno', $no)->get();
    }

    private function journalLines(string $no)
    {
        return DB::table('jurnaldt')->where('frefno', $no)->whereIn('fjurnaltype', ['JRB', 'RUB'])->get();
    }

    public function test_crud_with_journal(): void
    {
        $this->store();
        $h = $this->header();
        $this->assertNotNull($h, 'Retur pembelian tidak tersimpan. ' . $this->lastFlash());
        $this->assertMatchesRegularExpression('#^REB/[A-Za-z0-9]+/\d{4}/\d{4}$#', $h->fstockmtno, 'Format nomor tidak sesuai: ' . $h->fstockmtno);
        $this->assertEquals(10000, $h->famount);
        $this->assertEquals(10000, $h->famountmt);

        $rows = $this->details($h->fstockmtno);
        $this->assertCount(1, $rows);
        $this->assertEquals(2, $rows[0]->fqty);
        $this->assertEquals(2, $rows[0]->fqtykecil);
        $this->assertSame($this->buy->fstockmtno, trim($rows[0]->frefdtno));

        $lines = $this->journalLines($h->fstockmtno);
        $this->assertCount(2, $lines, 'Jurnal retur: retur belum potong hutang (D) dan persediaan (K).');
        $this->assertEquals(10000, $lines->where('fdk', 'D')->sum('famount'));
        $this->assertEquals(10000, $lines->where('fdk', 'K')->sum('famount'));

        $this->get(route('returpembelian.view', $h->fstockmtid))->assertOk();
        $this->get(route('returpembelian.edit', $h->fstockmtid))->assertOk();

        $this->atomic(fn () => $this->patch(route('returpembelian.update', $h->fstockmtid), $this->payload(['fqty' => [3]])));
        $updated = $this->header();
        $this->assertEquals(15000, $updated->famountmt ?? null, 'Total harus dihitung ulang saat update. ' . $this->lastFlash());
        $lines = $this->journalLines($updated->fstockmtno);
        $this->assertCount(2, $lines, 'Jurnal lama harus diganti, bukan ditambah.');
        $this->assertEquals(15000, $lines->where('fdk', 'K')->sum('famount'));

        $this->get(route('returpembelian.delete', $h->fstockmtid))->assertOk();
        $this->atomic(fn () => $this->deleteJson(route('returpembelian.destroy', $h->fstockmtid)));
        $this->assertNull($this->header(), 'Delete gagal. ' . $this->lastFlash());
        $this->assertCount(0, $this->details($h->fstockmtno));
        $this->assertCount(0, $this->journalLines($h->fstockmtno), 'Jurnal harus ikut terhapus.');
        $this->assertTrue(DB::table('log_trstockmt')->where('fstockmtno', $h->fstockmtno)->where('feditmode', 'D')->exists(), 'Log delete tidak tercatat.');
    }

    public function test_journal_is_balanced_when_ppn_is_present(): void
    {
        $this->store(['famountpajak' => 1100]);
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());
        $this->assertEquals(1100, $h->famountpajak);
        $this->assertEquals(11100, $h->famountmt);

        $lines = $this->journalLines($h->fstockmtno);
        $this->assertCount(3, $lines, 'Jurnal retur dengan PPN: potong hutang, persediaan, reverse PPN masukan.');
        $this->assertEquals(
            $lines->where('fdk', 'D')->sum('famount'),
            $lines->where('fdk', 'K')->sum('famount'),
            'Jurnal retur dengan PPN tidak seimbang.'
        );
    }

    public function test_return_qty_cannot_exceed_the_invoice_qty(): void
    {
        $this->store(['fqty' => [7]]);
        $this->assertNull($this->header(), 'Retur melebihi qty faktur harus ditolak.');
        $this->assertTrue($this->sessionHasError('detail'), $this->lastFlash());

        $this->store(['fqty' => [6]]);
        $this->assertNotNull($this->header(), 'Retur sebesar qty faktur harus diterima. ' . $this->lastFlash());
    }

    public function test_returns_are_partial_until_the_invoice_qty_is_used_up(): void
    {
        // Faktur 6 unit: retur 4, lalu 2 (habis) diterima; retur tambahan ditolak.
        $this->store(['fqty' => [4]]);
        $this->assertNotNull($this->header(), 'Retur sebagian pertama harus diterima. ' . $this->lastFlash());

        $this->store(['fket' => self::KET . '_2', 'fqty' => [3]]);
        $this->assertNull($this->header(self::KET . '_2'), 'Retur 3 melebihi sisa 2, harus ditolak.');
        $this->assertTrue($this->sessionHasError('detail'), $this->lastFlash());

        $this->store(['fket' => self::KET . '_2', 'fqty' => [2]]);
        $this->assertNotNull($this->header(self::KET . '_2'), 'Retur sebesar sisa harus diterima. ' . $this->lastFlash());

        $this->store(['fket' => self::KET . '_3', 'fqty' => [1]]);
        $this->assertNull($this->header(self::KET . '_3'), 'Faktur yang qty-nya sudah habis diretur tidak boleh diretur lagi.');
    }

    public function test_returns_are_limited_even_without_header_reference(): void
    {
        $this->store(['fqty' => [4]]);
        $this->assertNotNull($this->header(), $this->lastFlash());

        // Referensi header kosong, tetapi baris detail merujuk faktur yang sama: 4 + 3 > 6.
        $this->store(['fket' => self::KET . '_2', 'frefno' => '', 'fqty' => [3]]);
        $this->assertNull($this->header(self::KET . '_2'), 'Total retur (4 + 3) melebihi qty faktur (6), harus ditolak.');
    }

    public function test_editing_a_return_does_not_count_its_own_qty(): void
    {
        $this->store(['fqty' => [4]]);
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());

        $this->atomic(fn () => $this->patch(route('returpembelian.update', $h->fstockmtid), $this->payload(['fqty' => [6]])));
        $this->assertEquals(30000, $this->header()->famountmt ?? null, 'Edit ke qty faktur penuh harus diterima. ' . $this->lastFlash());

        $this->atomic(fn () => $this->patch(route('returpembelian.update', $h->fstockmtid), $this->payload(['fqty' => [7]])));
        $this->assertEquals(30000, $this->header()->famountmt, 'Edit melebihi qty faktur harus ditolak. ' . $this->lastFlash());
    }

    public function test_invoice_returned_only_by_detail_reference_is_locked(): void
    {
        $this->store(['frefno' => '']);
        $this->assertNotNull($this->header(), $this->lastFlash());

        $this->atomic(fn () => $this->deleteJson(route('fakturpembelian.destroy', $this->buy->fstockmtid)))->assertStatus(422);
        $this->assertTrue(DB::table('trstockmt')->where('fstockmtid', $this->buy->fstockmtid)->exists(), 'Faktur yang sudah diretur (lewat detail) tidak boleh terhapus.');
    }

    public function test_invoice_with_a_return_is_locked(): void
    {
        $this->store();
        $this->assertNotNull($this->header(), $this->lastFlash());

        $this->atomic(fn () => $this->deleteJson(route('fakturpembelian.destroy', $this->buy->fstockmtid)))->assertStatus(422);
        $this->assertTrue(DB::table('trstockmt')->where('fstockmtid', $this->buy->fstockmtid)->exists(), 'Faktur yang sudah diretur tidak boleh terhapus.');
    }

    public function test_validation_fails(): void
    {
        $cases = [
            'tanggal kosong' => [['fstockmtdate' => ''], 'fstockmtdate'],
            'tanggal tidak valid' => [['fstockmtdate' => 'bukan-tanggal'], 'fstockmtdate'],
            'supplier kosong' => [['fsupplier' => ''], 'fsupplier'],
            'gudang kosong' => [['ffrom' => ''], 'ffrom'],
            'tanpa item' => [['fitemcode' => []], 'fitemcode'],
            'qty nol' => [['fqty' => [0]], 'fqty.0'],
            'qty bukan angka' => [['fqty' => ['abc']], 'fqty.0'],
            'harga negatif' => [['fprice' => [-1]], 'fprice.0'],
            'harga bukan angka' => [['fprice' => ['abc']], 'fprice.0'],
            'keterangan lebih dari 50 karakter' => [['fket' => str_repeat('A', 51)], 'fket'],
        ];

        foreach ($cases as $label => [$override, $field]) {
            $this->store($override);
            $this->assertTrue($this->sessionHasError($field), "Kasus '$label': error '$field' tidak muncul. " . $this->lastFlash());
        }

        $this->assertNull($this->header(), 'Tidak boleh tersimpan saat validasi gagal.');
    }

    public function test_index_opens(): void
    {
        $this->get(route('returpembelian.index'))->assertOk();
    }
}
