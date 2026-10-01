<?php

namespace Tests\Http;

use App\Models\Tr_poh;
use App\Models\Tr_prh;
use Illuminate\Support\Facades\DB;
use Tests\Http\Concerns\MakesTestMaster;

class Tr_pohHttpTest extends LiveDbTestCase
{
    use MakesTestMaster;

    private const KET = 'ZZ_DUSK_PO';

    private string $supplier;

    private string $productCode;

    private string $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->grantPermissions(
            'viewTr_poh', 'createTr_poh', 'updateTr_poh', 'deleteTr_poh',
            'viewTr_prh', 'createTr_prh', 'updateTr_prh', 'deleteTr_prh',
        );
        $this->supplier = $this->makeSupplier();
        $product = $this->makeProduct();
        $this->productCode = $product->fprdcode;
        $this->unit = trim((string) $product->fsatuankecil);

        // Batas harian aplikasi: 15 dokumen/hari, dihitung dari data asli.
        $range = [now()->startOfDay(), now()->endOfDay()];
        $today = max(
            Tr_poh::whereBetween('fdatetime', $range)->count(),
            Tr_prh::whereBetween('fcreatedat', $range)->count(),
        );
        if ($today >= 13) {
            $this->markTestSkipped("Batas 15 dokumen/hari hampir tercapai ($today hari ini).");
        }
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'fpodate' => now()->format('Y-m-d'),
            'fsupplier' => $this->supplier,
            'fket' => self::KET,
            'fbranchcode' => auth('sysuser')->user()->fcabang,
            'fitemcode' => [$this->productCode],
            'fsatuan' => [$this->unit],
            'fqty' => [10],
            'fprice' => [5000],
            'fdisc' => ['0'],
            'fnoacak' => ['123'],
            'fdesc' => [''],
            'frefdtno' => [''],
            'famountponet' => 50000,
            'famountpopajak' => 0,
            'famountpo' => 50000,
        ], $override);
    }

    private function store(array $override = [])
    {
        return $this->atomic(fn () => $this->post(route('tr_poh.store'), $this->payload($override)));
    }

    private function header(string $ket = self::KET): ?Tr_poh
    {
        return Tr_poh::where('fket', $ket)->first();
    }

    private function details(string $fpono)
    {
        return DB::table('tr_pod')->where('fpono', $fpono)->orderBy('fnou')->get();
    }

    /** Buat PR uji berisi 10 unit produk uji; kembalikan [header, detail]. */
    private function makePr(): array
    {
        $this->atomic(fn () => $this->post(route('tr_prh.store'), [
            'fprdate' => now()->format('Y-m-d'),
            'fsupplier' => $this->supplier,
            'fket' => 'ZZ_DUSK_PR_REF',
            'fbranchcode' => auth('sysuser')->user()->fcabang,
            'fitemcode' => [$this->productCode],
            'fsatuan' => [$this->unit],
            'fqty' => [10],
            'fnoacak' => ['123'],
            'fdesc' => [''],
            'fketdt' => [''],
        ]));
        $pr = Tr_prh::where('fket', 'ZZ_DUSK_PR_REF')->first();
        $this->assertNotNull($pr, 'PR uji gagal dibuat. ' . $this->lastFlash());

        return [$pr, DB::table('tr_prd')->where('fprno', $pr->fprno)->first()];
    }

    public function test_crud_with_details(): void
    {
        $this->store();
        $h = $this->header();
        $this->assertNotNull($h, 'PO tidak tersimpan. ' . $this->lastFlash());
        $this->assertMatchesRegularExpression('#^PO/[A-Za-z0-9]+/\d{4}/\d{4}$#', $h->fpono, 'Format nomor PO (tanpa PPN) tidak sesuai: ' . $h->fpono);
        $this->assertSame($this->supplier, trim($h->fsupplier));
        $this->assertEquals(50000, $h->famountponet);

        $rows = $this->details($h->fpono);
        $this->assertCount(1, $rows);
        $this->assertEquals(10, $rows[0]->fqty);
        $this->assertEquals(10, $rows[0]->fqtykecil);
        $this->assertEquals(10, $rows[0]->fqtyremain);
        $this->assertEquals(5000, $rows[0]->fpricenet);
        $this->assertEquals(50000, $rows[0]->famount);

        $this->get(route('tr_poh.view', $h->fpohid))->assertOk();
        $this->get(route('tr_poh.edit', $h->fpohid))->assertOk();

        $this->atomic(fn () => $this->patch(route('tr_poh.update', $h->fpohid), $this->payload([
            'fqty' => [20],
            'fprice' => [4000],
        ])));
        $rows = $this->details($h->fpono);
        $this->assertEquals(20, $rows->first()->fqty ?? null, 'Update qty gagal. ' . $this->lastFlash());
        $this->assertEquals(80000, $rows->first()->famount ?? null);
        $this->assertEquals(80000, $this->header()->famountponet, 'Total PO harus dihitung ulang dari detail saat update.');

        $this->get(route('tr_poh.delete', $h->fpohid))->assertOk();
        $this->atomic(fn () => $this->deleteJson(route('tr_poh.destroy', $h->fpohid)));
        $this->assertNull($this->header(), 'Delete PO gagal. ' . $this->lastFlash());
        $this->assertCount(0, $this->details($h->fpono));
        $this->assertTrue(DB::table('log_tr_poh')->where('fpono', $h->fpono)->where('feditmode', 'D')->exists(), 'Log delete tidak tercatat.');
    }

    public function test_discount_reduces_net_price_and_amount(): void
    {
        $this->store(['fdisc' => ['10'], 'fprice' => [10000], 'fqty' => [2], 'famountponet' => 18000, 'famountpo' => 18000]);
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());
        $row = $this->details($h->fpono)->first();
        $this->assertEquals(10000, $row->fpricegross);
        $this->assertEquals(9000, $row->fpricenet, 'Harga net harus 10% di bawah harga kotor.');
        $this->assertEquals(18000, $row->famount);
        $this->assertEquals(18000, $h->famountponet);
    }

    public function test_number_format_follows_ppn_and_increments(): void
    {
        $this->store(['fket' => self::KET . '_A']);
        $this->store(['fket' => self::KET . '_B']);
        $a = $this->header(self::KET . '_A');
        $b = $this->header(self::KET . '_B');
        $this->assertNotNull($a, $this->lastFlash());
        $this->assertNotNull($b, $this->lastFlash());
        $num = fn ($no) => (int) substr($no, max(strrpos($no, '.'), strrpos($no, '/')) + 1);
        $this->assertSame($num($a->fpono) + 1, $num($b->fpono), 'Nomor PO harus berurutan.');

        $this->store(['fket' => self::KET . '_PPN', 'fapplyppn' => 1]);
        $ppn = $this->header(self::KET . '_PPN');
        $this->assertNotNull($ppn, $this->lastFlash());
        $this->assertMatchesRegularExpression('/^PO\.[A-Za-z0-9]+\.\d{4}\.\d{4}$/', $ppn->fpono, 'PO dengan PPN memakai pemisah titik: ' . $ppn->fpono);
        $this->assertEquals(1, $ppn->fapplyppn);
    }

    public function test_validation_fails(): void
    {
        $dup = [
            'fitemcode' => [$this->productCode, $this->productCode],
            'fsatuan' => [$this->unit, $this->unit],
            'fqty' => [1, 2],
            'fprice' => [100, 100],
            'fdisc' => ['0', '0'],
            'fnoacak' => ['123', '123'],
            'fdesc' => ['', ''],
            'frefdtno' => ['', ''],
        ];

        $cases = [
            'supplier kosong' => [['fsupplier' => ''], 'fsupplier'],
            'tanggal kosong' => [['fpodate' => ''], 'fpodate'],
            'tanggal tidak valid' => [['fpodate' => 'bukan-tanggal'], 'fpodate'],
            'tanpa item' => [['fitemcode' => []], 'fitemcode'],
            'qty nol' => [['fqty' => [0]], 'fqty.0'],
            'qty bukan angka' => [['fqty' => ['abc']], 'fqty.0'],
            'harga negatif' => [['fprice' => [-1]], 'fprice.0'],
            'format diskon salah' => [['fdisc' => ['abc']], 'fdisc.0'],
            'no acak bukan 3 digit 1-9' => [['fnoacak' => ['120']], 'fnoacak.0'],
            'PPN lebih dari 100' => [['ppn_rate' => 150], 'ppn_rate'],
            'keterangan lebih dari 300 karakter' => [['fket' => str_repeat('A', 301)], 'fket'],
            'produk dobel dengan no acak sama' => [$dup, 'fitemcode.1'],
        ];

        foreach ($cases as $label => [$override, $field]) {
            $this->store($override);
            $this->assertTrue($this->sessionHasError($field), "Kasus '$label': error '$field' tidak muncul. " . $this->lastFlash());
        }

        $this->assertNull($this->header(), 'PO tidak boleh tersimpan saat validasi gagal.');
    }

    public function test_reference_to_pr_consumes_and_restores_remaining_qty(): void
    {
        [$pr, $prd] = $this->makePr();
        $remain = fn () => (float) DB::table('tr_prd')->where('fprdid', $prd->fprdid)->value('fqtyremain');
        $this->assertEquals(10, $remain());

        $this->store([
            'fqty' => [4],
            'frefdtno' => [$pr->fprno],
            'frefdtid' => [$prd->fprdid],
            'frefnoacak' => ['123'],
            'famountponet' => 20000,
            'famountpo' => 20000,
        ]);
        $h = $this->header();
        $this->assertNotNull($h, 'PO dengan referensi PR tidak tersimpan. ' . $this->lastFlash());
        $this->assertEquals(6, $remain(), 'Sisa qty PR harus berkurang sebesar qty PO.');
        $this->assertSame($prd->fprdid, (int) $this->details($h->fpono)->first()->frefdtid);

        $this->atomic(fn () => $this->deleteJson(route('tr_poh.destroy', $h->fpohid)));
        $this->assertNull($this->header(), 'Delete PO gagal. ' . $this->lastFlash());
        $this->assertEquals(10, $remain(), 'Sisa qty PR harus kembali penuh setelah PO dihapus.');
    }

    public function test_po_cannot_exceed_remaining_pr_qty(): void
    {
        [$pr, $prd] = $this->makePr();

        $this->store([
            'fqty' => [11],
            'frefdtno' => [$pr->fprno],
            'frefdtid' => [$prd->fprdid],
            'frefnoacak' => ['123'],
        ]);
        $this->assertTrue($this->sessionHasError('detail'), 'PO melebihi sisa qty PR harus ditolak. ' . $this->lastFlash());
        $this->assertNull($this->header());
        $this->assertEquals(10, DB::table('tr_prd')->where('fprdid', $prd->fprdid)->value('fqtyremain'), 'Sisa qty PR tidak boleh berubah.');
    }

    public function test_pr_referenced_by_po_cannot_be_deleted(): void
    {
        [$pr, $prd] = $this->makePr();
        $this->store([
            'fqty' => [4],
            'frefdtno' => [$pr->fprno],
            'frefdtid' => [$prd->fprdid],
            'frefnoacak' => ['123'],
        ]);
        $this->assertNotNull($this->header(), $this->lastFlash());

        $this->atomic(fn () => $this->deleteJson(route('tr_prh.destroy', $pr->fprhid)))->assertStatus(422);
        $this->assertNotNull(Tr_prh::find($pr->fprhid), 'PR yang sudah dipakai PO tidak boleh terhapus.');
    }

    public function test_index_opens(): void
    {
        $this->get(route('tr_poh.index'))->assertOk();
    }
}
