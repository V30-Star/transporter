<?php

namespace Tests\Http;

use Illuminate\Support\Facades\DB;
use Tests\Http\Concerns\MakesTestMaster;

class JurnalTransaksiHttpTest extends LiveDbTestCase
{
    use MakesTestMaster;

    private const NOTE = 'ZZ_DUSK_JURNAL';

    private string $debit;

    private string $kredit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->grantPermissions(
            'viewJurnaltransaksi', 'createJurnaltransaksi', 'updateJurnaltransaksi', 'deleteJurnaltransaksi',
            'viewjurnaltransaksi', 'createjurnaltransaksi', 'updatejurnaltransaksi', 'deletejurnaltransaksi',
            'createAccount'
        );

        $today = DB::table('jurnalmt')->whereBetween('fdatetime', [now()->startOfDay(), now()->endOfDay()])->count();
        if ($today >= 12) {
            $this->markTestSkipped("Batas 15 dokumen/hari hampir tercapai ($today hari ini).");
        }

        $this->debit = $this->makeDetailAccount('ZZDUSKD1', 'D');
        $this->kredit = $this->makeDetailAccount('ZZDUSKD2', 'K');
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'fjurnaltype' => 'SJU',
            'fjurnaldate' => now()->format('Y-m-d'),
            'fjurnalnote' => self::NOTE,
            'fbranchcode' => auth('sysuser')->user()->fcabang,
            'faccount' => [$this->debit, $this->kredit],
            'fdk' => ['D', 'K'],
            'famount' => [10000, 10000],
            'fsubaccount' => ['', ''],
            'faccountnote' => ['', ''],
            'frefno' => ['', ''],
            'frate' => [1, 1],
        ], $override);
    }

    private function store(array $override = [])
    {
        return $this->atomic(fn () => $this->post(route('jurnaltransaksi.store'), $this->payload($override)));
    }

    private function header(): ?object
    {
        return DB::table('jurnalmt')->where('fjurnalnote', self::NOTE)->first();
    }

    private function lines(object $h)
    {
        return DB::table('jurnaldt')->where('fjurnalmtid', $h->fjurnalmtid)->orderBy('flineno')->get();
    }

    public function test_crud_balanced_journal(): void
    {
        $this->store();
        $h = $this->header();
        $this->assertNotNull($h, 'Jurnal tidak tersimpan. ' . $this->lastFlash());
        $this->assertMatchesRegularExpression('/^JV\.SJU\.[A-Za-z0-9]+\.\d{4}\.\d{4}$/', $h->fjurnalno, 'Format nomor tidak sesuai: ' . $h->fjurnalno);
        $this->assertEquals(10000, $h->fbalance);

        $lines = $this->lines($h);
        $this->assertCount(2, $lines);
        $this->assertSame('D', trim($lines[0]->fdk));
        $this->assertSame($this->debit, trim($lines[0]->faccount));
        $this->assertSame('K', trim($lines[1]->fdk));

        $this->get(route('jurnaltransaksi.index'))->assertOk();
        $this->get(route('jurnaltransaksi.view', $h->fjurnalmtid))->assertOk();
        $this->get(route('jurnaltransaksi.edit', $h->fjurnalmtid))->assertOk();
        $this->get(route('jurnaltransaksi.print', $h->fjurnalno))->assertOk();

        $this->atomic(fn () => $this->patch(route('jurnaltransaksi.update', $h->fjurnalmtid), $this->payload([
            'fjurnalno' => $h->fjurnalno, 'famount' => [25000, 25000], 'faccountnote' => ['baris 1', 'baris 2'],
        ])));
        $h2 = $this->header();
        $this->assertEquals(25000, $h2->fbalance, 'Total harus dihitung ulang saat update. ' . $this->lastFlash());
        $this->assertEquals(25000, $this->lines($h2)->where('fdk', 'D')->sum('famount'));
        $this->assertSame($h->fjurnalno, $h2->fjurnalno, 'Nomor jurnal tidak boleh berubah saat update.');

        $this->get(route('jurnaltransaksi.delete', $h->fjurnalmtid))->assertOk();
        $this->atomic(fn () => $this->deleteJson(route('jurnaltransaksi.destroy', $h->fjurnalmtid)));
        $this->assertNull($this->header(), 'Delete gagal. ' . $this->lastFlash());
        $this->assertSame(0, DB::table('jurnaldt')->where('fjurnalmtid', $h->fjurnalmtid)->count(), 'Baris jurnal harus ikut terhapus.');
    }

    public function test_an_unbalanced_journal_is_rejected(): void
    {
        $this->store(['famount' => [10000, 9000]]);
        $this->assertNull($this->header(), 'Jurnal tidak seimbang harus ditolak.');
        $this->assertTrue($this->sessionHasError('detail'), $this->lastFlash());
    }

    public function test_an_unbalanced_update_is_rejected_and_keeps_the_old_lines(): void
    {
        $this->store();
        $h = $this->header();
        $this->assertNotNull($h, $this->lastFlash());

        $this->atomic(fn () => $this->patch(route('jurnaltransaksi.update', $h->fjurnalmtid), $this->payload(['fjurnalno' => $h->fjurnalno, 'famount' => [10000, 1]])));
        $this->assertEquals(10000, $this->header()->fbalance);
        $this->assertCount(2, $this->lines($h));
    }

    public function test_a_half_filled_row_is_rejected_instead_of_silently_dropped(): void
    {
        $this->store([
            'faccount' => [$this->debit, $this->kredit, $this->debit],
            'fdk' => ['D', 'K', ''],
            'famount' => [10000, 10000, 5000],
            'fsubaccount' => ['', '', ''], 'faccountnote' => ['', '', ''], 'frefno' => ['', '', ''], 'frate' => [1, 1, 1],
        ]);
        $this->assertNull($this->header(), 'Baris dengan account dan jumlah tetapi tanpa D/K tidak boleh dibuang diam-diam. ' . $this->lastFlash());
    }

    public function test_a_duplicate_manual_journal_number_is_rejected_cleanly(): void
    {
        $this->store(['fjurnalno' => 'JV.ZZ.DUP.0001']);
        $this->assertNotNull($this->header(), $this->lastFlash());

        $this->store(['fjurnalno' => 'JV.ZZ.DUP.0001', 'fjurnalnote' => self::NOTE . '_2']);
        $this->assertSame(1, DB::table('jurnalmt')->where('fjurnalno', 'JV.ZZ.DUP.0001')->count());
        $this->assertTrue($this->sessionHasError('fjurnalno') || $this->sessionHasError('detail'), 'Nomor ganda harus ditolak dengan pesan validasi, bukan error SQL. ' . $this->lastFlash());
    }

    public function test_only_the_general_journal_type_is_allowed(): void
    {
        foreach (['ZZZ', 'JBL', 'SLS'] as $type) {
            $this->store(['fjurnaltype' => $type]);
            $this->assertNull($this->header(), "Tipe $type harus ditolak, hanya SJU yang boleh. " . $this->lastFlash());
            $this->assertTrue($this->sessionHasError('fjurnaltype'), $this->lastFlash());
        }
    }

    public function test_an_account_with_subaccount_needs_one_and_others_must_not_have_one(): void
    {
        $this->store(['fsubaccount' => ['XX', '']]);
        $this->assertNull($this->header(), 'Sub account di account tanpa sub account harus ditolak.');
        $this->assertTrue($this->sessionHasError('detail'), $this->lastFlash());
    }

    public function test_validation_fails(): void
    {
        $cases = [
            'tanggal kosong' => [['fjurnaldate' => ''], 'fjurnaldate'],
            'tanggal tidak valid' => [['fjurnaldate' => 'bukan-tanggal'], 'fjurnaldate'],
            'tipe kosong' => [['fjurnaltype' => ''], 'fjurnaltype'],
            'tanpa baris' => [['faccount' => []], 'faccount'],
            'D/K tidak valid' => [['fdk' => ['X', 'K']], 'fdk.0'],
            'jumlah negatif' => [['famount' => [-1, 10000]], 'famount.0'],
            'keterangan lebih dari 500 karakter' => [['fjurnalnote' => str_repeat('A', 501)], 'fjurnalnote'],
        ];

        foreach ($cases as $label => [$override, $field]) {
            $this->store($override);
            $this->assertTrue($this->sessionHasError($field), "Kasus '$label': error '$field' tidak muncul. " . $this->lastFlash());
        }

        $this->assertNull($this->header(), 'Tidak boleh tersimpan saat validasi gagal.');
    }

    public function test_index_opens(): void
    {
        $this->get(route('jurnaltransaksi.index'))->assertOk();
    }
}
