<?php

namespace Tests\Http;

use App\Models\Tr_prh;
use Illuminate\Support\Facades\DB;
use Tests\Http\Concerns\MakesTestMaster;

class Tr_prhHttpTest extends LiveDbTestCase
{
    use MakesTestMaster;

    private const KET = 'ZZ_DUSK_PR';

    private string $supplier;

    private string $productCode;

    private string $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->grantPermissions('viewTr_prh', 'createTr_prh', 'updateTr_prh', 'deleteTr_prh');
        $this->supplier = $this->makeSupplier();
        $product = $this->makeProduct();
        $this->productCode = $product->fprdcode;
        $this->unit = trim((string) $product->fsatuankecil);

        // Batas harian aplikasi: 15 PR/hari (dihitung dari data asli).
        $today = Tr_prh::whereBetween('fcreatedat', [now()->startOfDay(), now()->endOfDay()])->count();
        if ($today >= 13) {
            $this->markTestSkipped("Batas 15 PR/hari hampir tercapai ($today PR hari ini).");
        }
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'fprdate' => now()->format('Y-m-d'),
            'fsupplier' => $this->supplier,
            'fket' => self::KET,
            'fbranchcode' => auth('sysuser')->user()->fcabang,
            'fitemcode' => [$this->productCode],
            'fsatuan' => [$this->unit],
            'fqty' => [5],
            'fnoacak' => ['123'],
            'fdesc' => [''],
            'fketdt' => [''],
        ], $override);
    }

    private function header(string $ket = self::KET): ?Tr_prh
    {
        return Tr_prh::where('fket', $ket)->first();
    }

    private function details(string $fprno)
    {
        return DB::table('tr_prd')->where('fprno', $fprno)->get();
    }

    private function store(array $override = [])
    {
        return $this->atomic(fn () => $this->post(route('tr_prh.store'), $this->payload($override)));
    }

    public function test_crud_with_details(): void
    {
        $this->store();
        $h = $this->header();
        $this->assertNotNull($h, 'PR tidak tersimpan. ' . $this->lastFlash());
        $this->assertMatchesRegularExpression('/^PR\.[A-Za-z0-9]+\.\d{4}\.\d{4}$/', $h->fprno, 'Format nomor PR tidak sesuai.');
        $this->assertEquals(0, $h->fapproval);

        $rows = $this->details($h->fprno);
        $this->assertCount(1, $rows);
        $this->assertEquals(5, $rows[0]->fqty);
        $this->assertEquals(5, $rows[0]->fqtykecil, 'Qty satuan kecil harus sama dengan qty untuk satuan kecil.');
        $this->assertEquals(5, $rows[0]->fqtyremain);

        $this->get(route('tr_prh.view', $h->fprhid))->assertOk();
        $this->get(route('tr_prh.edit', $h->fprhid))->assertOk();

        $this->atomic(fn () => $this->patch(route('tr_prh.update', $h->fprhid), $this->payload([
            'fqty' => [8],
            'fprdid' => [$rows[0]->fprdid],
        ])));
        $this->assertEquals(8, $this->details($h->fprno)->first()->fqty ?? null, 'Update qty detail gagal. ' . $this->lastFlash());

        $this->get(route('tr_prh.delete', $h->fprhid))->assertOk();
        $this->atomic(fn () => $this->deleteJson(route('tr_prh.destroy', $h->fprhid)));
        $this->assertNull($this->header(), 'Delete PR gagal. ' . $this->lastFlash());
        $this->assertCount(0, $this->details($h->fprno), 'Detail PR harus ikut terhapus.');
        $this->assertTrue(DB::table('log_tr_prh')->where('fprno', $h->fprno)->where('feditmode', 'D')->exists(), 'Log delete tidak tercatat.');
    }

    public function test_number_increments_within_month(): void
    {
        $this->store();
        $first = $this->header();
        $this->assertNotNull($first, $this->lastFlash());

        $this->store(['fket' => self::KET . '_2']);
        $second = $this->header(self::KET . '_2');
        $this->assertNotNull($second, $this->lastFlash());

        $num = fn ($no) => (int) substr($no, strrpos($no, '.') + 1);
        $this->assertSame($num($first->fprno) + 1, $num($second->fprno), 'Nomor PR harus berurutan.');
    }

    public function test_validation_fails(): void
    {
        $cases = [
            'tanpa detail' => [['fitemcode' => [], 'fsatuan' => [], 'fqty' => [], 'fnoacak' => [], 'fdesc' => [], 'fketdt' => []], 'detail'],
            'qty nol' => [['fqty' => [0]], 'fqty.0'],
            'qty bukan angka' => [['fqty' => ['abc']], 'fqty.0'],
            'no acak bukan 3 digit 1-9' => [['fnoacak' => ['120']], 'fnoacak.0'],
            'tanggal tidak valid' => [['fprdate' => 'bukan-tanggal'], 'fprdate'],
            'keterangan terlalu panjang' => [['fket' => str_repeat('A', 301)], 'fket'],
            'produk dobel dengan no acak sama' => [[
                'fitemcode' => [$this->productCode, $this->productCode],
                'fsatuan' => [$this->unit, $this->unit],
                'fqty' => [1, 2],
                'fnoacak' => ['123', '123'],
                'fdesc' => ['', ''],
                'fketdt' => ['', ''],
            ], 'fitemcode.1'],
        ];

        // Qty negatif hanya ditolak jika pengaturan "stok boleh minus" (setini.fstokbolehminus) mati.
        if (trim((string) DB::table('setini')->value('fstokbolehminus')) !== '1') {
            $cases['qty negatif'] = [['fqty' => [-3]], 'fqty.0'];
        }

        foreach ($cases as $label => [$override, $field]) {
            $this->store($override);
            $this->assertTrue($this->sessionHasError($field), "Kasus '$label': error '$field' tidak muncul. " . $this->lastFlash());
        }

        $this->assertNull($this->header(), 'PR tidak boleh tersimpan saat validasi gagal.');
    }

    public function test_unknown_product_is_not_saved_as_detail(): void
    {
        $this->store(['fitemcode' => ['ZZ_TIDAK_ADA']]);
        $h = $this->header();
        $this->assertTrue($h === null || $this->details($h->fprno)->isEmpty(), 'Produk yang tidak ada tidak boleh menjadi detail PR. ' . $this->lastFlash());
    }

    public function test_index_opens(): void
    {
        $this->get(route('tr_prh.index'))->assertOk();
    }
}
