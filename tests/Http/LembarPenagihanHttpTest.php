<?php

namespace Tests\Http;

use Illuminate\Support\Facades\DB;
use Tests\Http\Concerns\MakesSalesChain;

class LembarPenagihanHttpTest extends LiveDbTestCase
{
    use MakesSalesChain;

    private const NOTE = 'ZZ_DUSK_LPT';

    protected function setUp(): void
    {
        parent::setUp();

        $this->grantPermissions(
            'viewLembarPenagihan', 'createLembarPenagihan', 'updateLembarPenagihan', 'deleteLembarPenagihan',
            'viewPelunasanCustomer', 'createPelunasanCustomer',
        );
        $this->setUpSalesChain();
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'ftagihandate' => now()->format('Y-m-d'),
            'fcustno' => $this->customer,
            'fnote' => self::NOTE,
            'frefsono' => [$this->inv->fsono],
            'frefcode' => ['INV'],
            'famount' => [30000],
        ], $override);
    }

    private function store(array $override = [])
    {
        return $this->atomic(fn () => $this->post(route('lembarpenagihan.store'), $this->payload($override)));
    }

    private function header(string $note = self::NOTE): ?object
    {
        return DB::table('trtagihanmt')->where('fnote', $note)->first();
    }

    private function details(string $no)
    {
        return DB::table('trtagihandt')->where('ftagihanno', $no)->get();
    }

    private function payInvoiceInFull(): void
    {
        $this->grantPermissions('viewPelunasanCustomer', 'createPelunasanCustomer');
        $this->atomic(fn () => $this->post(route('pelunasancustomer.store'), [
            'fkasmtdate' => now()->format('Y-m-d'), 'fcustomer' => $this->customer,
            'faccountheader' => $this->makeCashAccount(), 'fket' => 'ZZ_DUSK_RCP_LP',
            'details' => [[
                'frefno' => $this->inv->fsono, 'ftrcode' => 'INV', 'fnilai_nota' => 30000, 'fsisa_piutang' => 30000,
                'original_sisa' => 30000, 'fdiscpersen' => 0, 'fdiscount' => 0, 'fkasdtvalue' => 30000,
            ]],
        ]));
        $this->assertEquals(0, (float) DB::table('tranmt')->where('ftranmtid', $this->inv->ftranmtid)->value('famountremain'), 'Pelunasan uji gagal. ' . $this->lastFlash());
    }

    public function test_crud_collection_sheet(): void
    {
        $this->store();
        $h = $this->header();
        $this->assertNotNull($h, 'Lembar penagihan tidak tersimpan. ' . $this->lastFlash());
        $this->assertMatchesRegularExpression('/^LPT\.[A-Za-z0-9]+\.\d{4}\.\d{4}$/', $h->ftagihanno, 'Format nomor tidak sesuai: ' . $h->ftagihanno);
        $this->assertSame($this->customer, trim($h->fcustno));
        $this->assertEquals(30000, $h->famounttagihan);

        $rows = $this->details($h->ftagihanno);
        $this->assertCount(1, $rows);
        $this->assertSame($this->inv->fsono, trim($rows[0]->frefsono));
        $this->assertSame('INV', trim($rows[0]->frefcode));
        $this->assertEquals(30000, $rows[0]->famount);

        $this->get(route('lembarpenagihan.view', $h->ftagihanid))->assertOk();
        $this->get(route('lembarpenagihan.edit', $h->ftagihanid))->assertOk();

        $this->atomic(fn () => $this->patch(route('lembarpenagihan.update', $h->ftagihanid), $this->payload(['famount' => [25000], 'fnote' => self::NOTE])));
        $this->assertEquals(25000, $this->header()->famounttagihan ?? null, 'Total tagihan harus dihitung ulang saat update. ' . $this->lastFlash());
        $this->assertCount(1, $this->details($h->ftagihanno), 'Detail lama diganti, bukan ditambah.');
        $this->assertTrue(DB::table('log_trtagihanmt')->where('ftagihanno', $h->ftagihanno)->where('feditmode', 'U')->exists(), 'Log update tidak tercatat.');

        $this->get(route('lembarpenagihan.delete', $h->ftagihanid))->assertOk();
        $this->atomic(fn () => $this->deleteJson(route('lembarpenagihan.destroy', $h->ftagihanid)));
        $this->assertNull($this->header(), 'Delete gagal. ' . $this->lastFlash());
        $this->assertCount(0, $this->details($h->ftagihanno), 'Detail harus ikut terhapus.');
        $this->assertTrue(DB::table('log_trtagihanmt')->where('ftagihanno', $h->ftagihanno)->where('feditmode', 'D')->exists(), 'Log delete tidak tercatat.');
    }

    public function test_number_increments(): void
    {
        $this->store();
        $this->store(['fnote' => self::NOTE . '_2']);
        $a = $this->header();
        $b = $this->header(self::NOTE . '_2');
        $this->assertNotNull($a, $this->lastFlash());
        $this->assertNotNull($b, $this->lastFlash());
        $num = fn ($no) => (int) substr($no, strrpos($no, '.') + 1);
        $this->assertSame($num($a->ftagihanno) + 1, $num($b->ftagihanno), 'Nomor harus berurutan.');
    }

    public function test_manual_number_must_be_unique(): void
    {
        $this->store(['ftagihanno' => 'zz.dusk.0001']);
        $this->assertSame('ZZ.DUSK.0001', trim((string) $this->header()?->ftagihanno), 'Nomor manual disimpan huruf besar. ' . $this->lastFlash());

        $this->store(['ftagihanno' => 'ZZ.DUSK.0001', 'fnote' => self::NOTE . '_2']);
        $this->assertNull($this->header(self::NOTE . '_2'), 'Nomor ganda harus ditolak.');
        $this->assertTrue($this->sessionHasError('ftagihanno'), $this->lastFlash());
    }

    public function test_a_sheet_with_a_fully_paid_invoice_is_locked(): void
    {
        $this->store();
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());

        $this->payInvoiceInFull();
        $this->assertEquals('1', trim((string) DB::table('tranmt')->where('ftranmtid', $this->inv->ftranmtid)->value('fsudahtagih')), 'Faktur yang ada di lembar penagihan dan lunas bertanda fsudahtagih = 1.');

        $this->atomic(fn () => $this->patch(route('lembarpenagihan.update', $h->ftagihanid), $this->payload(['famount' => [1000]])))->assertSessionHas('error');
        $this->assertEquals(30000, $this->header()->famounttagihan, 'Lembar yang sudah dipakai tidak boleh diedit.');

        $this->atomic(fn () => $this->deleteJson(route('lembarpenagihan.destroy', $h->ftagihanid)))->assertStatus(422);
        $this->assertNotNull($this->header(), 'Lembar yang sudah dipakai tidak boleh dihapus.');
    }

    public function test_deleting_the_sheet_clears_the_collected_flag(): void
    {
        $this->payInvoiceInFull();
        $this->assertEquals('0', trim((string) DB::table('tranmt')->where('ftranmtid', $this->inv->ftranmtid)->value('fsudahtagih')), 'Belum masuk lembar penagihan.');

        $this->store();
        $this->assertEquals('1', trim((string) DB::table('tranmt')->where('ftranmtid', $this->inv->ftranmtid)->value('fsudahtagih')), 'Faktur lunas yang dimasukkan ke lembar penagihan bertanda 1.');
    }

    public function test_pickable_lists_only_approved_open_invoices_of_the_customer(): void
    {
        $listed = fn () => collect($this->getJson(route('lembarpenagihan.pickable-invoices', ['fcustno' => $this->customer]))->assertOk()->json('data'))
            ->pluck('fsono')->map(fn ($v) => trim($v))->all();

        $this->assertNotContains($this->inv->fsono, $listed(), 'Faktur yang belum disetujui tidak boleh bisa dipilih.');

        DB::table('tranmt')->where('ftranmtid', $this->inv->ftranmtid)->update(['fapproval' => 1]);
        $this->assertContains($this->inv->fsono, $listed(), 'Faktur yang sudah disetujui dan masih berpiutang harus bisa dipilih.');

        $other = $this->makeCustomer('ZZ_DUSK_CUSTOMER_LAIN');
        $otherList = collect($this->getJson(route('lembarpenagihan.pickable-invoices', ['fcustno' => $other]))->json('data'))->pluck('fsono')->map(fn ($v) => trim($v))->all();
        $this->assertNotContains($this->inv->fsono, $otherList, 'Faktur customer lain tidak boleh muncul.');

        DB::table('tranmt')->where('ftranmtid', $this->inv->ftranmtid)->update(['famountremain' => 0]);
        $this->assertNotContains($this->inv->fsono, $listed(), 'Faktur yang sudah lunas tidak boleh bisa dipilih lagi.');
    }

    public function test_the_listed_invoice_must_exist_and_belong_to_the_customer(): void
    {
        $this->store(['frefsono' => ['INV/ZZ/0000/0000']]);
        $this->assertNull($this->header(), 'Nota yang tidak ada tidak boleh dimasukkan ke lembar penagihan. ' . $this->lastFlash());

        $lain = $this->makeCustomer('ZZ_DUSK_CUSTOMER_LAIN');
        $this->store(['fcustno' => $lain, 'fnote' => self::NOTE . '_2']);
        $this->assertNull($this->header(self::NOTE . '_2'), 'Nota customer lain tidak boleh dimasukkan ke lembar customer ini.');
    }

    public function test_the_same_invoice_cannot_be_listed_twice_in_one_sheet(): void
    {
        $this->store(['frefsono' => [$this->inv->fsono, $this->inv->fsono], 'frefcode' => ['INV', 'INV'], 'famount' => [30000, 30000]]);
        $this->assertNull($this->header(), 'Nota yang sama dua kali dalam satu lembar harus ditolak (tagihan dobel).');
    }

    public function test_validation_fails(): void
    {
        $cases = [
            'tanggal kosong' => [['ftagihandate' => ''], 'ftagihandate'],
            'tanggal tidak valid' => [['ftagihandate' => 'bukan-tanggal'], 'ftagihandate'],
            'customer kosong' => [['fcustno' => ''], 'fcustno'],
            'tanpa nota' => [['frefsono' => [], 'frefcode' => [], 'famount' => []], 'frefsono'],
            'nota lebih dari 20 karakter' => [['frefsono' => [str_repeat('A', 21)]], 'frefsono.0'],
            'kode referensi lebih dari 3 karakter' => [['frefcode' => ['INVO']], 'frefcode.0'],
            'jumlah bukan angka' => [['famount' => ['abc']], 'famount.0'],
            'jumlah kosong' => [['famount' => ['']], 'famount.0'],
        ];

        foreach ($cases as $label => [$override, $field]) {
            $this->store($override);
            $this->assertTrue($this->sessionHasError($field), "Kasus '$label': error '$field' tidak muncul. " . $this->lastFlash());
        }

        $this->assertNull($this->header(), 'Tidak boleh tersimpan saat validasi gagal.');
    }

    public function test_index_opens(): void
    {
        $this->get(route('lembarpenagihan.index'))->assertOk();
    }
}
