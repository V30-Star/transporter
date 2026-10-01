<?php

namespace Tests\Http;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/** Edit Periode, System Setting, Penamaan Perusahaan. Semua mengubah satu baris setini; di-rollback. */
class SettingsHttpTest extends LiveDbTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->grantPermissions(
            'viewEditPeriode', 'updateEditPeriode', 'viewSystemSetting', 'updateSystemSetting',
            'viewPenamaanPerusahaan', 'updatePenamaanPerusahaan'
        );
    }

    private function setini(): object
    {
        return DB::table('setini')->first();
    }

    private function patchTo(string $route, array $data)
    {
        return $this->atomic(fn () => $this->patch(route($route), $data));
    }

    // ---------- Edit Periode ----------

    public function test_edit_periode_opens_and_saves_a_valid_period(): void
    {
        $this->get(route('editperiode.edit'))->assertOk();

        $this->patchTo('editperiode.update', ['fyrmth' => '202601']);
        $this->assertSame('202601', trim((string) $this->setini()->fyrmth), 'Periode tidak tersimpan. ' . $this->lastFlash());
    }

    public function test_edit_periode_rejects_a_bad_period(): void
    {
        $before = trim((string) $this->setini()->fyrmth);

        foreach (['' => 'fyrmth', '2026' => 'fyrmth', '2026ab' => 'fyrmth', '2026-1' => 'fyrmth'] as $value => $field) {
            $this->patchTo('editperiode.update', ['fyrmth' => (string) $value]);
            $this->assertTrue($this->sessionHasError($field), "Periode '$value' harus ditolak. " . $this->lastFlash());
        }

        $this->patchTo('editperiode.update', ['fyrmth' => '202613']);
        $this->assertSame($before, trim((string) $this->setini()->fyrmth), 'Bulan 13 harus ditolak. ' . $this->lastFlash());

        $this->patchTo('editperiode.update', ['fyrmth' => '202600']);
        $this->assertSame($before, trim((string) $this->setini()->fyrmth), 'Bulan 00 harus ditolak.');
    }

    public function test_edit_periode_rejects_an_implausible_year(): void
    {
        $before = trim((string) $this->setini()->fyrmth);
        $nextYear = (int) now()->format('Y') + 1;

        foreach (['199912', '000101', '999912', ($nextYear + 1) . '01'] as $period) {
            $this->patchTo('editperiode.update', ['fyrmth' => $period]);
            $this->assertSame($before, trim((string) $this->setini()->fyrmth), "Periode $period harus ditolak. " . $this->lastFlash());
        }

        $this->patchTo('editperiode.update', ['fyrmth' => $nextYear . '01']);
        $this->assertSame($nextYear . '01', trim((string) $this->setini()->fyrmth), 'Tahun depan masih boleh. ' . $this->lastFlash());
    }

    // ---------- System Setting ----------

    public function test_system_setting_opens_and_saves_with_encrypted_address_fields(): void
    {
        $this->get(route('systemsetting.edit'))->assertOk();

        $this->patchTo('systemsetting.update', [
            'fcity' => 'ZZ KOTA', 'falamat1' => 'ZZ ALAMAT 1', 'falamat2' => 'ZZ ALAMAT 2', 'ftelp' => '021-123', 'ffax' => '021-456',
            'fnpwp' => '01.234.567.8-901.000', 'falamat1npwp' => 'ZZ NPWP 1', 'falamat2npwp' => 'ZZ NPWP 2',
            'fnamattdfakturpenjualan' => 'ZZ TTD 1', 'fnamattdfakturpenjualan2' => 'ZZ TTD 2', 'fnamattdpo' => 'ZZ PO 1', 'fnamattdpo2' => 'ZZ PO 2',
            'fppntarif' => 11,
        ]);

        $s = $this->setini();
        $this->assertSame('ZZ KOTA', trim((string) $s->fcity), 'System setting tidak tersimpan. ' . $this->lastFlash());
        $this->assertSame('ZZ ALAMAT 1', Crypt::decryptString($s->falamat1), 'Alamat harus tersimpan terenkripsi.');
        $this->assertNotSame('ZZ ALAMAT 1', $s->falamat1);
        $this->assertSame('01.234.567.8-901.000', Crypt::decryptString($s->fnpwp));
        $this->assertSame('ZZ TTD 1', trim((string) $s->fnamattdfakturpenjualan));
        $this->assertEquals(11, $s->fppntarif);

        $this->get(route('systemsetting.edit'))->assertOk()->assertSee('ZZ ALAMAT 1');
    }

    public function test_system_setting_keeps_the_tax_rate_when_it_is_not_sent(): void
    {
        $this->patchTo('systemsetting.update', ['fppntarif' => 12]);
        $this->patchTo('systemsetting.update', ['fcity' => 'ZZ KOTA']);
        $this->assertEquals(12, $this->setini()->fppntarif, 'Tarif PPN tidak boleh berubah jika tidak dikirim.');
    }

    public function test_system_setting_validation_fails(): void
    {
        $cases = [
            'tarif ppn di atas 100' => [['fppntarif' => 101], 'fppntarif'],
            'tarif ppn negatif' => [['fppntarif' => -1], 'fppntarif'],
            'tarif ppn bukan angka' => [['fppntarif' => 'abc'], 'fppntarif'],
            'kota lebih dari 100 karakter' => [['fcity' => str_repeat('A', 101)], 'fcity'],
            'alamat lebih dari 200 karakter' => [['falamat1' => str_repeat('A', 201)], 'falamat1'],
            'npwp lebih dari 50 karakter' => [['fnpwp' => str_repeat('1', 51)], 'fnpwp'],
        ];

        $before = $this->setini();
        foreach ($cases as $label => [$data, $field]) {
            $this->patchTo('systemsetting.update', $data);
            $this->assertTrue($this->sessionHasError($field), "Kasus '$label': error '$field' tidak muncul. " . $this->lastFlash());
        }

        $this->assertEquals($before->fppntarif, $this->setini()->fppntarif, 'Data tidak boleh berubah saat validasi gagal.');
    }

    // ---------- Penamaan Perusahaan ----------

    private function storePassword(string $plain): void
    {
        DB::table('setini')->update(['fpasswordperusahaan' => Crypt::encryptString($plain)]);
    }

    private function throttleKey(): string
    {
        return 'penamaan-verify:' . auth('sysuser')->id() . '|127.0.0.1';
    }

    protected function tearDown(): void
    {
        \Illuminate\Support\Facades\RateLimiter::clear($this->throttleKey());

        parent::tearDown();
    }

    public function test_penamaan_perusahaan_has_no_default_password_when_none_is_set(): void
    {
        DB::table('setini')->update(['fpasswordperusahaan' => null]);

        $this->atomic(fn () => $this->post(route('penamaanperusahaan.verify'), ['password' => 'admin1234']));
        $this->assertFalse((bool) session('penamaan_perusahaan_auth'), 'Password bawaan admin1234 tidak boleh membuka akses. ' . $this->lastFlash());

        // belum ada password: halaman pengaturan awal langsung terbuka
        $this->get(route('penamaanperusahaan.index'))->assertOk()->assertDontSee('Verifikasi Akses');
        $this->patchTo('penamaanperusahaan.update', ['fproject' => 'ZZ PERUSAHAAN', 'new_password' => 'awal1234', 'new_password_confirmation' => 'awal1234']);
        $this->assertSame('ZZ PERUSAHAAN', decrypt_value((string) $this->setini()->fproject), $this->lastFlash());
        $this->assertSame('awal1234', decrypt_value((string) $this->setini()->fpasswordperusahaan));
    }

    public function test_penamaan_perusahaan_blocks_repeated_wrong_passwords(): void
    {
        $this->storePassword('zz-rahasia');

        for ($i = 0; $i < 5; $i++) {
            $this->atomic(fn () => $this->post(route('penamaanperusahaan.verify'), ['password' => 'salah' . $i]));
        }

        $this->atomic(fn () => $this->post(route('penamaanperusahaan.verify'), ['password' => 'zz-rahasia']));
        $this->assertFalse((bool) session('penamaan_perusahaan_auth'), 'Setelah 5 kali salah, percobaan berikutnya harus diblokir sementara.');
        $this->assertStringContainsString('Terlalu banyak percobaan', (string) session('error'), $this->lastFlash());
    }

    public function test_penamaan_perusahaan_asks_for_the_password_first(): void
    {
        $this->storePassword('zz-rahasia');
        $this->get(route('penamaanperusahaan.index'))->assertOk()->assertSee('Verifikasi Akses');

        $this->patchTo('penamaanperusahaan.update', ['fproject' => 'ZZ PERUSAHAAN']);
        $this->assertNotSame('ZZ PERUSAHAAN', decrypt_value((string) $this->setini()->fproject), 'Tanpa verifikasi, nama perusahaan tidak boleh berubah.');
    }

    public function test_penamaan_perusahaan_rejects_a_wrong_password_and_accepts_the_right_one(): void
    {
        $this->storePassword('zz-rahasia');

        $this->atomic(fn () => $this->post(route('penamaanperusahaan.verify'), ['password' => 'salah']));
        $this->assertSame('Password salah! Akses ditolak.', session('error'), $this->lastFlash());
        $this->assertFalse((bool) session('penamaan_perusahaan_auth'));

        $this->atomic(fn () => $this->post(route('penamaanperusahaan.verify'), ['password' => '']));
        $this->assertTrue($this->sessionHasError('password'), $this->lastFlash());

        $this->atomic(fn () => $this->post(route('penamaanperusahaan.verify'), ['password' => 'zz-rahasia']));
        $this->assertTrue((bool) session('penamaan_perusahaan_auth'), 'Password benar harus membuka akses. ' . $this->lastFlash());
    }

    public function test_penamaan_perusahaan_saves_the_name_and_a_new_password(): void
    {
        $this->storePassword('zz-rahasia');

        $this->withSession(['penamaan_perusahaan_auth' => true]);
        $this->get(route('penamaanperusahaan.index'))->assertOk();

        $this->patchTo('penamaanperusahaan.update', ['fproject' => 'ZZ PERUSAHAAN', 'new_password' => 'baru1234', 'new_password_confirmation' => 'baru1234']);
        $s = $this->setini();
        $this->assertSame('ZZ PERUSAHAAN', decrypt_value((string) $s->fproject), 'Nama perusahaan tidak tersimpan. ' . $this->lastFlash());
        $this->assertSame('baru1234', decrypt_value((string) $s->fpasswordperusahaan), 'Password baru tidak tersimpan.');

        $this->patchTo('penamaanperusahaan.update', ['fproject' => 'ZZ PERUSAHAAN 2']);
        $this->assertSame('baru1234', decrypt_value((string) $this->setini()->fpasswordperusahaan), 'Password tidak boleh berubah jika kolom password baru kosong.');
    }

    public function test_penamaan_perusahaan_validation_fails(): void
    {
        $this->storePassword('zz-rahasia');
        $this->withSession(['penamaan_perusahaan_auth' => true]);

        $cases = [
            'nama kosong' => [['fproject' => ''], 'fproject'],
            'nama lebih dari 200 karakter' => [['fproject' => str_repeat('A', 201)], 'fproject'],
            'password baru kurang dari 4 karakter' => [['fproject' => 'ZZ', 'new_password' => 'abc', 'new_password_confirmation' => 'abc'], 'new_password'],
            'konfirmasi password tidak cocok' => [['fproject' => 'ZZ', 'new_password' => 'abcd', 'new_password_confirmation' => 'abce'], 'new_password'],
        ];

        foreach ($cases as $label => [$data, $field]) {
            $this->patchTo('penamaanperusahaan.update', $data);
            $this->assertTrue($this->sessionHasError($field), "Kasus '$label': error '$field' tidak muncul. " . $this->lastFlash());
        }

        $this->assertSame('zz-rahasia', decrypt_value((string) $this->setini()->fpasswordperusahaan), 'Password tidak boleh berubah saat validasi gagal.');
    }

    public function test_penamaan_perusahaan_lock_clears_the_session_access(): void
    {
        $this->withSession(['penamaan_perusahaan_auth' => true]);
        $this->atomic(fn () => $this->post(route('penamaanperusahaan.lock')));
        $this->assertFalse((bool) session('penamaan_perusahaan_auth'), 'Kunci harus menghapus akses. ' . $this->lastFlash());
    }
}
