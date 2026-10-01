<?php

namespace Tests\Http;

use App\Models\Tr_poh;
use Illuminate\Support\Facades\DB;
use Tests\Http\Concerns\MakesTestMaster;

class PenerimaanBarangHttpTest extends LiveDbTestCase
{
    use MakesTestMaster;

    private const KET = 'ZZ_DUSK_TER';

    private string $supplier;

    private string $productCode;

    private string $unit;

    private string $gudang;

    private object $pod;

    private object $poh;

    protected function setUp(): void
    {
        parent::setUp();

        $this->grantPermissions(
            'viewPenerimaanBarang', 'createPenerimaanBarang', 'updatePenerimaanBarang', 'deletePenerimaanBarang',
            'viewTr_poh', 'createTr_poh', 'updateTr_poh', 'deleteTr_poh',
            'createGudang',
        );
        $this->supplier = $this->makeSupplier();
        $product = $this->makeProduct();
        $this->productCode = $product->fprdcode;
        $this->unit = trim((string) $product->fsatuankecil);
        $this->gudang = $this->makeGudang();

        $range = [now()->startOfDay(), now()->endOfDay()];
        $today = max(
            Tr_poh::whereBetween('fdatetime', $range)->count(),
            DB::table('trstockmt')->where('fstockmtcode', 'TER')->whereBetween('fdatetime', $range)->count(),
        );
        if ($today >= 13) {
            $this->markTestSkipped("Batas 15 dokumen/hari hampir tercapai ($today hari ini).");
        }

        [$this->poh, $this->pod] = $this->makePo();
    }

    private function makeGudang(string $code = 'ZZDUSKW1'): string
    {
        $this->atomic(fn () => $this->post(route('gudang.store'), [
            'fwhcode' => $code, 'fwhname' => 'ZZ GUDANG UJI', 'faddress' => 'ZZ', 'fbranchcode' => 'ZZCAB',
        ]));
        $this->assertTrue(DB::table('mswh')->where('fwhcode', $code)->exists(), 'Gudang uji gagal dibuat. ' . $this->lastFlash());

        return $code;
    }

    /** PO uji: 10 unit @ 5000, belum diterima. */
    private function makePo(string $ket = 'ZZ_DUSK_PO_REF'): array
    {
        $this->atomic(fn () => $this->post(route('tr_poh.store'), [
            'fpodate' => now()->format('Y-m-d'),
            'fsupplier' => $this->supplier,
            'fket' => $ket,
            'fbranchcode' => auth('sysuser')->user()->fcabang,
            'fitemcode' => [$this->productCode],
            'fsatuan' => [$this->unit],
            'fqty' => [10],
            'fprice' => [5000],
            'fdisc' => ['0'],
            'fnoacak' => ['123'],
            'fdesc' => [''],
            'frefdtno' => [''],
        ]));
        $poh = DB::table('tr_poh')->where('fket', $ket)->first();
        $this->assertNotNull($poh, 'PO uji gagal dibuat. ' . $this->lastFlash());

        return [$poh, DB::table('tr_pod')->where('fpono', $poh->fpono)->first()];
    }

    private function poRemain(): float
    {
        return (float) DB::table('tr_pod')->where('fpodid', $this->pod->fpodid)->value('fqtyremain');
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'fstockmtdate' => now()->format('Y-m-d'),
            'fsupplier' => $this->supplier,
            'ffrom' => $this->gudang,
            'fket' => self::KET,
            'fbranchcode' => auth('sysuser')->user()->fcabang,
            'fitemcode' => [$this->productCode],
            'fsatuan' => [$this->unit],
            'fpono' => [$this->poh->fpono],
            'frefdtid' => [$this->pod->fpodid],
            'fqty' => [6],
            'fprice' => [5000],
            'fnoacak' => ['123'],
            'frefnoacak' => ['123'],
            'fdesc' => [''],
        ], $override);
    }

    private function store(array $override = [])
    {
        return $this->atomic(fn () => $this->post(route('penerimaanbarang.store'), $this->payload($override)));
    }

    private function header(string $ket = self::KET): ?object
    {
        return DB::table('trstockmt')->where('fket', $ket)->first();
    }

    private function details(string $no)
    {
        return DB::table('trstockdt')->where('fstockmtno', $no)->get();
    }

    private function journalLines(string $no)
    {
        return DB::table('jurnaldt')->where('frefno', $no)->where('fjurnaltype', 'TER')->get();
    }

    public function test_crud_with_po_reference_and_journal(): void
    {
        $this->store();
        $h = $this->header();
        $this->assertNotNull($h, 'Penerimaan barang tidak tersimpan. ' . $this->lastFlash());
        $this->assertMatchesRegularExpression('#^TER/[A-Za-z0-9]+/\d{4}/\d{4}$#', $h->fstockmtno, 'Format nomor tidak sesuai: ' . $h->fstockmtno);
        $this->assertEquals(30000, $h->famount);
        $this->assertEquals(30000, $h->famountmt);
        $this->assertEquals(0, $h->famountpajak);

        $rows = $this->details($h->fstockmtno);
        $this->assertCount(1, $rows);
        $this->assertEquals(6, $rows[0]->fqty);
        $this->assertEquals(6, $rows[0]->fqtykecil);
        $this->assertEquals($this->pod->fpodid, $rows[0]->frefdtid);
        $this->assertEquals(4, $this->poRemain(), 'Sisa qty PO harus berkurang sebesar qty diterima.');

        $lines = $this->journalLines($h->fstockmtno);
        $this->assertCount(2, $lines, 'Jurnal penerimaan harus 2 baris (persediaan D, penerimaan belum ditagih K).');
        $this->assertEquals($lines->where('fdk', 'D')->sum('famount'), $lines->where('fdk', 'K')->sum('famount'), 'Jurnal harus seimbang.');
        $this->assertEquals(30000, $lines->where('fdk', 'D')->sum('famount'));

        $this->get(route('penerimaanbarang.view', $h->fstockmtid))->assertOk();
        $this->get(route('penerimaanbarang.edit', $h->fstockmtid))->assertOk();

        $this->atomic(fn () => $this->patch(route('penerimaanbarang.update', $h->fstockmtid), $this->payload(['fqty' => [8]])));
        $updated = $this->header();
        $this->assertEquals(40000, $updated->famount ?? null, 'Total harus dihitung ulang saat update. ' . $this->lastFlash());
        $this->assertEquals(2, $this->poRemain(), 'Sisa qty PO harus mengikuti qty terbaru.');
        $this->assertCount(2, $this->journalLines($updated->fstockmtno), 'Jurnal lama harus diganti, bukan ditambah.');
        $this->assertEquals(40000, $this->journalLines($updated->fstockmtno)->where('fdk', 'K')->sum('famount'));

        $this->get(route('penerimaanbarang.delete', $h->fstockmtid))->assertOk();
        $this->atomic(fn () => $this->deleteJson(route('penerimaanbarang.destroy', $h->fstockmtid)));
        $this->assertNull($this->header(), 'Delete gagal. ' . $this->lastFlash());
        $this->assertCount(0, $this->details($h->fstockmtno));
        $this->assertCount(0, $this->journalLines($h->fstockmtno), 'Jurnal harus ikut terhapus.');
        $this->assertEquals(10, $this->poRemain(), 'Sisa qty PO harus kembali penuh.');
        $this->assertTrue(DB::table('log_trstockmt')->where('fstockmtno', $h->fstockmtno)->where('feditmode', 'D')->exists(), 'Log delete tidak tercatat.');
    }

    public function test_journal_is_balanced_when_ppn_is_present(): void
    {
        $this->store(['famountpopajak' => 3300]);
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());
        $this->assertEquals(3300, $h->famountpajak);
        $this->assertEquals(33300, $h->famountmt);

        $lines = $this->journalLines($h->fstockmtno);
        $ppnAccount = trim((string) DB::table('set_account')->where('faccount_name', 'PPNBELI')->value('faccount'));
        $this->assertCount(3, $lines, 'Jurnal dengan PPN harus 3 baris. ' . $this->lastFlash());
        $this->assertEquals(3300, $lines->first(fn ($l) => trim($l->faccount) === $ppnAccount)?->famount, 'Baris PPN masukan (set_account PPNBELI) tidak ada.');
        $this->assertEquals(
            $lines->where('fdk', 'D')->sum('famount'),
            $lines->where('fdk', 'K')->sum('famount'),
            'Jurnal tidak seimbang saat ada PPN: debit ' . $lines->where('fdk', 'D')->sum('famount') . ' vs kredit ' . $lines->where('fdk', 'K')->sum('famount')
        );
    }

    public function test_receipt_cannot_exceed_po_quantity(): void
    {
        $this->store(['fqty' => [11]]);
        $this->assertNull($this->header(), 'Penerimaan melebihi qty PO harus ditolak.');
        $this->assertTrue($this->sessionHasError('detail'), $this->lastFlash());
        $this->assertEquals(10, $this->poRemain(), 'Sisa PO tidak boleh berubah saat penerimaan ditolak.');

        $this->store(['fqty' => [10]]);
        $this->assertNotNull($this->header(), 'Penerimaan sama dengan qty PO harus diterima. ' . $this->lastFlash());
        $this->assertEquals(0, $this->poRemain());
    }

    public function test_po_line_can_only_be_received_once(): void
    {
        $this->store();
        $this->assertNotNull($this->header(), $this->lastFlash());

        $this->store(['fket' => self::KET . '_2', 'fqty' => [2]]);
        $this->assertNull($this->header(self::KET . '_2'), 'Baris PO yang sudah diterima tidak boleh diterima lagi.');
        $this->assertStringContainsString('sudah ada di transaksi', json_encode(session('errors')?->all()), $this->lastFlash());
        $this->assertEquals(4, $this->poRemain(), 'Sisa PO tidak boleh berubah.');
    }

    public function test_po_referenced_by_receipt_is_locked(): void
    {
        $this->store();
        $this->assertNotNull($this->header(), $this->lastFlash());

        $this->atomic(fn () => $this->deleteJson(route('tr_poh.destroy', $this->poh->fpohid)))->assertStatus(422);
        $this->assertNotNull(Tr_poh::find($this->poh->fpohid), 'PO yang sudah diterima tidak boleh terhapus.');
    }

    public function test_row_without_po_reference_is_not_saved(): void
    {
        $this->store(['frefdtid' => ['']]);
        $this->assertNull($this->header(), 'Penerimaan tanpa referensi PO tidak boleh tersimpan.');
        $this->assertTrue($this->sessionHasError('detail'), $this->lastFlash());
    }

    public function test_validation_fails(): void
    {
        $dup = [
            'fitemcode' => [$this->productCode, $this->productCode],
            'fsatuan' => [$this->unit, $this->unit],
            'fpono' => [$this->poh->fpono, $this->poh->fpono],
            'frefdtid' => [$this->pod->fpodid, $this->pod->fpodid],
            'fqty' => [1, 2],
            'fprice' => [5000, 5000],
            'fnoacak' => ['123', '123'],
            'frefnoacak' => ['123', '123'],
            'fdesc' => ['', ''],
        ];

        $cases = [
            'tanggal kosong' => [['fstockmtdate' => ''], 'fstockmtdate'],
            'tanggal tidak valid' => [['fstockmtdate' => 'bukan-tanggal'], 'fstockmtdate'],
            'supplier kosong' => [['fsupplier' => ''], 'fsupplier'],
            'gudang kosong' => [['ffrom' => ''], 'ffrom'],
            'tanpa item' => [['fitemcode' => []], 'fitemcode'],
            'qty nol' => [['fqty' => [0]], 'fqty.0'],
            'qty bukan angka' => [['fqty' => ['abc']], 'fqty.0'],
            'harga negatif' => [['fprice' => [-1]], 'fprice.0'],
            'no acak bukan 3 digit 1-9' => [['fnoacak' => ['120']], 'fnoacak.0'],
            'keterangan lebih dari 500 karakter' => [['fket' => str_repeat('A', 501)], 'fket'],
            'produk dobel dengan no acak sama' => [$dup, 'fitemcode.1'],
        ];

        foreach ($cases as $label => [$override, $field]) {
            $this->store($override);
            $this->assertTrue($this->sessionHasError($field), "Kasus '$label': error '$field' tidak muncul. " . $this->lastFlash());
        }

        $this->assertNull($this->header(), 'Tidak boleh tersimpan saat validasi gagal.');
        $this->assertEquals(10, $this->poRemain(), 'Sisa PO tidak boleh berubah.');
    }

    public function test_number_increments(): void
    {
        [$poh2, $pod2] = $this->makePo('ZZ_DUSK_PO_REF2');

        $this->store(['fqty' => [2]]);
        $this->store([
            'fket' => self::KET . '_2', 'fqty' => [2],
            'fpono' => [$poh2->fpono], 'frefdtid' => [$pod2->fpodid],
        ]);
        $a = $this->header();
        $b = $this->header(self::KET . '_2');
        $this->assertNotNull($a, $this->lastFlash());
        $this->assertNotNull($b, $this->lastFlash());
        $num = fn ($no) => (int) substr($no, max(strrpos($no, '.'), strrpos($no, '/')) + 1);
        $this->assertSame($num($a->fstockmtno) + 1, $num($b->fstockmtno), 'Nomor harus berurutan.');
    }

    public function test_index_opens(): void
    {
        $this->get(route('penerimaanbarang.index'))->assertOk();
    }
}
