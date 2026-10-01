<?php

namespace Tests\Http;

use Illuminate\Support\Facades\DB;
use Tests\Http\Concerns\MakesPurchaseChain;

class InvoiceHttpTest extends LiveDbTestCase
{
    use MakesPurchaseChain;

    private const KET = 'ZZ_DUSK_INV';

    private string $customer;

    private object $so;

    private object $srj;

    protected function setUp(): void
    {
        parent::setUp();

        $this->grantPermissions(
            'viewInvoice', 'createInvoice', 'updateInvoice', 'deleteInvoice',
            'viewSuratJalan', 'createSuratJalan', 'updateSuratJalan', 'deleteSuratJalan',
            'viewSalesOrder', 'createSalesOrder', 'updateSalesOrder', 'deleteSalesOrder',
        );

        $range = [now()->startOfDay(), now()->endOfDay()];
        $today = max(
            DB::table('tranmt')->where('ftrcode', 'INV')->whereBetween('fdatetime', $range)->count(),
            DB::table('trsomt')->whereBetween('fdatetime', $range)->count(),
            DB::table('trstockmt')->where('fstockmtcode', 'SRJ')->whereBetween('fdatetime', $range)->count(),
        );
        if ($today >= 11) {
            $this->markTestSkipped("Batas harian dokumen hampir tercapai ($today hari ini).");
        }

        $this->setUpPurchaseChain();   // stok 6 unit di gudang uji
        $this->customer = $this->makeCustomer();

        $branch = auth('sysuser')->user()->fcabang;
        $this->atomic(fn () => $this->post(route('salesorder.store'), [
            'fsodate' => now()->format('Y-m-d'), 'fcustno' => $this->customer, 'fket' => 'ZZ_DUSK_SO_REF',
            'fbranchcode' => substr((string) $branch, 0, 2),
            'fprdcode' => [$this->productCode], 'fsatuan' => [$this->unit], 'fqty' => [5], 'fprice' => [10000],
            'fdisc' => ['0'], 'fnoacak' => ['123'], 'fdesc' => [''],
        ]));
        $this->so = DB::table('trsomt')->where('fket', 'ZZ_DUSK_SO_REF')->first();
        $this->assertNotNull($this->so, 'SO uji gagal dibuat. ' . $this->lastFlash());

        $this->atomic(fn () => $this->post(route('suratjalan.store'), [
            'fstockmtdate' => now()->format('Y-m-d'), 'fsupplier' => $this->customer, 'ffrom' => $this->gudang,
            'fket' => 'ZZ_DUSK_SRJ_REF', 'fkirim' => 'ZZ', 'fbranchcode' => $branch,
            'fitemcode' => [$this->productCode], 'fsatuan' => [$this->unit], 'frefso' => [$this->so->fsono],
            'frefnoacak' => ['123'], 'fqty' => [3], 'fprice' => [10000], 'fdiscpersen' => [0], 'fnoacak' => ['123'], 'fdesc' => [''],
        ]));
        $this->srj = DB::table('trstockmt')->where('fstockmtcode', 'SRJ')->where('fket', 'ZZ_DUSK_SRJ_REF')->first();
        $this->assertNotNull($this->srj, 'Surat jalan uji gagal dibuat. ' . $this->lastFlash());
    }

    /** Faktur 3 unit @ 10.000 yang menagih surat jalan uji. */
    private function payload(array $override = []): array
    {
        return array_merge([
            'fsodate' => now()->format('Y-m-d'),
            'fcustno' => $this->customer,
            'fket' => self::KET,
            'fbranchcode' => auth('sysuser')->user()->fcabang,
            'ftypesales' => 0,
            'fitemcode' => [$this->productCode],
            'fsatuan' => [$this->unit],
            'frefsrj' => [$this->srj->fstockmtno],
            'frefdtno' => [$this->srj->fstockmtno],
            'frefnoacak' => ['123'],
            'fqty' => [3],
            'fprice' => [10000],
            'fdisc' => ['0'],
            'fnoacak' => ['123'],
            'fdesc' => [''],
        ], $override);
    }

    private function store(array $override = [])
    {
        return $this->atomic(fn () => $this->post(route('invoice.store'), $this->payload($override)));
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
        // Nomor jurnal = 'JV' + pemisah + nomor faktur; hanya baris piutang yang memuat frefno, jadi cari lewat nomor jurnal.
        return DB::table('jurnaldt')->where('fjurnaltype', 'SLS')->whereIn('fjurnalno', ['JV/' . ltrim($fsono, '/'), 'JV.' . ltrim($fsono, '.')])->get();
    }

    private function saldo(): float
    {
        return (float) DB::table('prdwh')->where('fprdcode', $this->productCode)->where('fwhcode', $this->gudang)->sum('fsaldo');
    }

    private function srjRemain(): float
    {
        return (float) DB::table('trstockdt')->where('fstockmtno', $this->srj->fstockmtno)->value('fqtyremain');
    }

    public function test_crud_billing_a_delivery_note(): void
    {
        $this->assertEquals(3, $this->saldo(), 'Setelah surat jalan 3 unit, stok gudang 3.');

        $this->store();
        $h = $this->header();
        $this->assertNotNull($h, 'Faktur penjualan tidak tersimpan. ' . $this->lastFlash());
        $this->assertMatchesRegularExpression('#^INV[/.][A-Za-z0-9]+[/.]\d{4}[/.]\d{4}$#', $h->fsono, 'Format nomor tidak sesuai: ' . $h->fsono);
        $this->assertEquals(30000, $h->famountgross);
        $this->assertEquals(30000, $h->famountso);
        $this->assertEquals(30000, $h->famountremain, 'Faktur kredit: piutang awal = total faktur.');
        $this->assertEquals(0, $h->ftunai);
        $this->assertSame('0', trim((string) $h->fneedacc), 'Faktur biasa tidak bertanda butuh persetujuan.');

        $rows = $this->details($h->fsono);
        $this->assertCount(1, $rows);
        $this->assertEquals(3, $rows[0]->fqty);
        $this->assertEquals(30000, $rows[0]->famount);
        $this->assertSame($this->srj->fstockmtno, trim($rows[0]->frefsrj));
        $this->assertEquals(3, $this->saldo(), 'Faktur dari surat jalan tidak boleh mengubah stok (barang sudah keluar lewat SJ).');
        $this->assertEquals(3, (float) DB::table('prdwh')->where('fprdcode', $this->productCode)->sum('fsaldo'), 'Total saldo semua gudang tidak boleh berubah oleh faktur dari surat jalan.');
        $this->assertEquals(3, (float) DB::table('msprd')->where('fprdcode', $this->productCode)->value('fstok'), 'msprd.fstok tidak boleh berkurang dua kali (SJ dan faktur).');

        $lines = $this->journalLines($h->fsono);
        $this->assertGreaterThanOrEqual(2, $lines->count(), 'Jurnal penjualan: piutang (D) dan penjualan (K).');
        $this->assertEquals(30000, $lines->where('fdk', 'D')->sum('famount'));
        $this->assertEquals(30000, $lines->where('fdk', 'K')->sum('famount'));

        $this->get(route('invoice.view', $h->ftranmtid))->assertOk();
        $this->get(route('invoice.edit', $h->ftranmtid))->assertOk();

        $this->atomic(fn () => $this->patch(route('invoice.update', $h->ftranmtid), $this->payload(['fqty' => [2]])));
        $updated = $this->header();
        $this->assertEquals(20000, $updated->famountso ?? null, 'Total harus dihitung ulang saat update. ' . $this->lastFlash());
        $lines = $this->journalLines($updated->fsono);
        $this->assertEquals(20000, $lines->where('fdk', 'D')->sum('famount'), 'Jurnal lama harus diganti.');
        $this->assertEquals(20000, $lines->where('fdk', 'K')->sum('famount'));

        $this->get(route('invoice.delete', $h->ftranmtid))->assertOk();
        $this->atomic(fn () => $this->deleteJson(route('invoice.destroy', $h->ftranmtid)));
        $this->assertNull($this->header(), 'Delete gagal. ' . $this->lastFlash());
        $this->assertCount(0, $this->details($h->fsono));
        $this->assertCount(0, $this->journalLines($h->fsono), 'Jurnal harus ikut terhapus.');
        $this->assertEquals(3, $this->srjRemain(), 'Sisa surat jalan harus kembali penuh setelah faktur dihapus.');
    }

    public function test_ppn_is_added_and_journaled(): void
    {
        $this->store(['fapplyppn' => 1, 'fppnpersen' => 11]);
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());
        $this->assertEquals(30000, $h->famountsonet);
        $this->assertEquals(3300, $h->famountpajak);
        $this->assertEquals(33300, $h->famountso);
        $this->assertEquals(33300, $h->famountremain);

        $lines = $this->journalLines($h->fsono);
        $this->assertEquals(33300, $lines->where('fdk', 'D')->sum('famount'));
        $this->assertEquals(33300, $lines->where('fdk', 'K')->sum('famount'), 'Jurnal faktur dengan PPN harus seimbang (penjualan 30.000 + PPN 3.300).');
    }

    public function test_line_discount_reduces_the_amount_and_the_journal_stays_balanced(): void
    {
        $this->store(['fdisc' => ['10']]);
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());
        $this->assertEquals(27000, $this->details($h->fsono)->first()->famount, 'Diskon baris 10% dari 30.000.');
        $this->assertEquals(27000, $h->famountso);

        $lines = $this->journalLines($h->fsono);
        $this->assertEquals($lines->where('fdk', 'D')->sum('famount'), $lines->where('fdk', 'K')->sum('famount'), 'Jurnal harus seimbang.');
    }

    public function test_cash_invoice_has_no_receivable(): void
    {
        $kas = $this->makeCashAccount();
        $this->store(['ftunai' => 1, 'faccount_pembayaran' => $kas]);
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());
        $this->assertEquals(1, $h->ftunai);
        $this->assertEquals(0, $h->famountremain, 'Faktur tunai tidak menyisakan piutang.');

        $lines = $this->journalLines($h->fsono);
        $this->assertEquals(30000, $lines->where('fdk', 'D')->where('faccount', $kas)->sum('famount'), 'Faktur tunai mendebit account kas yang dipilih.');
    }

    public function test_invoices_are_partial_until_the_delivery_note_is_used_up(): void
    {
        $this->store(['fqty' => [2]]);
        $this->assertNotNull($this->header(), $this->lastFlash());

        $this->store(['fket' => self::KET . '_2', 'fqty' => [2]]);
        $this->assertNull($this->header(self::KET . '_2'), 'Tagihan 2 melebihi sisa surat jalan 1, harus ditolak.');

        $this->store(['fket' => self::KET . '_2', 'fqty' => [1]]);
        $this->assertNotNull($this->header(self::KET . '_2'), 'Tagihan sebesar sisa surat jalan harus diterima. ' . $this->lastFlash());

        $this->store(['fket' => self::KET . '_3', 'fqty' => [1]]);
        $this->assertNull($this->header(self::KET . '_3'), 'Surat jalan yang sudah habis ditagih tidak boleh ditagih lagi.');
    }

    public function test_price_cannot_exceed_the_referenced_delivery_note(): void
    {
        $this->store(['fprice' => [12000]]);
        $this->assertNull($this->header(), 'Harga di atas harga surat jalan harus ditolak. ' . $this->lastFlash());
    }

    public function test_the_delivery_note_billed_by_an_invoice_is_locked(): void
    {
        $this->store();
        $this->assertNotNull($this->header(), $this->lastFlash());

        $this->atomic(fn () => $this->deleteJson(route('suratjalan.destroy', $this->srj->fstockmtid)))->assertStatus(422);
        $this->assertTrue(DB::table('trstockmt')->where('fstockmtid', $this->srj->fstockmtid)->exists(), 'Surat jalan yang sudah ditagih tidak boleh terhapus.');
    }

    public function test_credit_limit_requires_approval(): void
    {
        DB::table('mscustomer')->where('fcustomercode', $this->customer)->update(['flimit' => 10000]);

        $this->store();
        $this->assertNull($this->header(), 'Faktur 30.000 melebihi batas kredit 10.000 harus ditahan sampai disetujui. ' . $this->lastFlash());
        $this->assertTrue($this->sessionHasError('fcustno'), $this->lastFlash());

        // Disetujui oleh pengguna berwenang (konfirmasi "Yes" mengirim fuseracc).
        $this->store(['fuseracc' => 'ADMIN']);
        $h = $this->header();
        $this->assertNotNull($h, 'Faktur dengan persetujuan harus tersimpan. ' . $this->lastFlash());
        $this->assertSame('ADMIN', trim((string) $h->fuseracc), 'Penyetuju harus tercatat.');
        $this->assertSame('1', trim((string) $h->fneedacc), 'Faktur yang butuh persetujuan harus bertanda fneedacc = 1.');
    }

    public function test_an_invoice_without_any_valid_item_is_rejected(): void
    {
        $this->store(['fqty' => [0]]);
        $this->assertNull($this->header(), 'Faktur tanpa item bernilai tidak boleh tersimpan. ' . $this->lastFlash());
    }

    public function test_validation_fails(): void
    {
        $cases = [
            'tanggal kosong' => [['fsodate' => ''], 'fsodate'],
            'tanggal tidak valid' => [['fsodate' => 'bukan-tanggal'], 'fsodate'],
            'customer kosong' => [['fcustno' => ''], 'fcustno'],
            'tipe penjualan tidak valid' => [['ftypesales' => 2], 'ftypesales'],
            'tanpa item' => [['fitemcode' => []], 'fitemcode'],
            'qty bukan angka' => [['fqty' => ['abc']], 'fqty.0'],
            'qty di bawah 0,01' => [['fqty' => [0]], 'fqty.0'],
            'diskon header di atas 100' => [['fdiscpersen' => 150], 'fdiscpersen'],
            'no acak bukan 3 digit 1-9' => [['fnoacak' => ['120']], 'fnoacak.0'],
            'keterangan internal lebih dari 300 karakter' => [['fketinternal' => str_repeat('A', 301)], 'fketinternal'],
        ];

        foreach ($cases as $label => [$override, $field]) {
            $this->store($override);
            $this->assertTrue($this->sessionHasError($field), "Kasus '$label': error '$field' tidak muncul. " . $this->lastFlash());
        }

        $this->assertNull($this->header(), 'Tidak boleh tersimpan saat validasi gagal.');
        $this->assertEquals(3, $this->srjRemain(), 'Sisa surat jalan tidak boleh berubah.');
    }

    public function test_index_opens(): void
    {
        $this->get(route('invoice.index'))->assertOk();
    }
}
