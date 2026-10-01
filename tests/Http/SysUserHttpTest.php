<?php

namespace Tests\Http;

use App\Models\RoleAccess;
use App\Models\Sysuser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SysUserHttpTest extends LiveDbTestCase
{
    private const LOGIN = 'ZZDUSK.1';

    protected function setUp(): void
    {
        parent::setUp();

        $this->grantPermissions('viewSysuser', 'createSysuser', 'updateSysuser', 'deleteSysuser', 'roleaccess');
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'fsysuserid' => self::LOGIN,
            'fname' => 'zz user uji',
            'password' => 'rahasia1',
            'password_confirmation' => 'rahasia1',
            'fsalesman' => '',
            'fuserlevel' => 'User',
            'fcabang' => trim((string) DB::table('mscabang')->value('fcabangkode')),
        ], $override);
    }

    private function store(array $override = [])
    {
        return $this->atomic(fn () => $this->post(route('sysuser.store'), $this->payload($override)));
    }

    private function update(Sysuser $user, array $override = [])
    {
        return $this->atomic(fn () => $this->patch(route('sysuser.update', $user->fuid), $this->payload(array_merge(['password' => '', 'password_confirmation' => ''], $override))));
    }

    private function user(string $login = self::LOGIN): ?Sysuser
    {
        return Sysuser::where('fsysuserid', $login)->first();
    }

    public function test_crud_user(): void
    {
        $this->store();
        $u = $this->user();
        $this->assertNotNull($u, 'User tidak tersimpan. ' . $this->lastFlash());
        $this->assertSame('ZZ USER UJI', $u->fname, 'Nama disimpan huruf besar.');
        $this->assertSame('1', trim((string) $u->fuserlevel), 'Level User = 1.');
        $this->assertTrue(Hash::check('rahasia1', $u->password), 'Password harus tersimpan sebagai hash.');
        $this->assertNotSame('rahasia1', $u->password);

        $this->get(route('sysuser.index'))->assertOk();
        $this->get(route('sysuser.view', $u->fuid))->assertOk();
        $this->get(route('sysuser.edit', $u->fuid))->assertOk();

        $oldHash = $u->password;
        $this->update($u, ['fname' => 'zz user baru', 'fuserlevel' => 'Admin']);
        $u = $this->user();
        $this->assertSame('ZZ USER BARU', $u->fname, 'Update tidak tersimpan. ' . $this->lastFlash());
        $this->assertSame('2', trim((string) $u->fuserlevel), 'Level Admin = 2.');
        $this->assertSame($oldHash, $u->password, 'Password kosong saat update = password tidak berubah.');
        $this->assertTrue(DB::table('logsysuser')->where('fuid', $u->fuid)->where('feditmode', 'U')->exists(), 'Log update tidak tercatat.');

        $this->update($u, ['password' => 'baru1234', 'password_confirmation' => 'baru1234']);
        $this->assertTrue(Hash::check('baru1234', $this->user()->password), 'Password baru tidak tersimpan. ' . $this->lastFlash());

        $this->get(route('sysuser.delete', $u->fuid))->assertOk();
        $this->atomic(fn () => $this->deleteJson(route('sysuser.destroy', $u->fuid)));
        $this->assertNull($this->user(), 'Delete gagal. ' . $this->lastFlash());
        $this->assertTrue(DB::table('logsysuser')->where('fuid', $u->fuid)->where('feditmode', 'D')->exists(), 'Log delete tidak tercatat.');
    }

    public function test_the_creator_is_not_overwritten_by_an_update(): void
    {
        $this->store();
        $u = $this->user();
        $this->assertNotNull($u, $this->lastFlash());
        DB::table('sysuser')->where('fuid', $u->fuid)->update(['fusercreate' => 'ZZ PEMBUAT ASLI']);

        $this->update($u, ['fname' => 'zz user baru']);
        $this->assertSame('ZZ PEMBUAT ASLI', $this->user()->fusercreate, 'Update tidak boleh mengganti pembuat user (fusercreate). ' . $this->lastFlash());
    }

    public function test_update_enforces_the_same_minimum_password_length_as_create(): void
    {
        $this->store();
        $u = $this->user();

        $this->update($u, ['password' => '1', 'password_confirmation' => '1']);
        $this->assertTrue(Hash::check('rahasia1', $this->user()->password), 'Password 1 karakter harus ditolak (create mewajibkan minimal 6). ' . $this->lastFlash());
    }

    public function test_a_user_level_is_required(): void
    {
        $payload = $this->payload();
        unset($payload['fuserlevel']);
        $this->atomic(fn () => $this->post(route('sysuser.store'), $payload));
        $this->assertNull($this->user(), 'Tanpa level, user tidak boleh tersimpan.');
        $this->assertTrue($this->sessionHasError('fuserlevel'), 'Level kosong harus memberi error validasi, bukan error 500. ' . $this->lastFlash());
    }

    public function test_the_branch_is_required_and_must_exist(): void
    {
        $payload = $this->payload();
        unset($payload['fcabang']);
        $this->atomic(fn () => $this->post(route('sysuser.store'), $payload));
        $this->assertNull($this->user(), 'Tanpa cabang, user tidak boleh tersimpan. ' . $this->lastFlash());

        $this->store(['fcabang' => 'ZZNOCAB']);
        $this->assertNull($this->user(), 'Cabang yang tidak ada harus ditolak. ' . $this->lastFlash());
    }

    public function test_a_duplicate_login_is_rejected(): void
    {
        $this->store();
        $this->store(['fname' => 'lain']);
        $this->assertSame(1, Sysuser::where('fsysuserid', self::LOGIN)->count());
        $this->assertTrue($this->sessionHasError('fsysuserid'), $this->lastFlash());
    }

    public function test_a_user_who_has_menu_access_cannot_be_deleted(): void
    {
        $this->store();
        $u = $this->user();
        RoleAccess::create(['fusercreate' => $u->fuid, 'fpermission' => 'viewProduct']);

        $this->atomic(fn () => $this->deleteJson(route('sysuser.destroy', $u->fuid)));
        $this->assertNotNull($this->user(), 'User yang sudah punya role access tidak boleh dihapus.');
    }

    public function test_validation_fails(): void
    {
        $cases = [
            'login kosong' => [['fsysuserid' => ''], 'fsysuserid'],
            'login tanpa huruf besar' => [['fsysuserid' => 'zzdusk.1'], 'fsysuserid'],
            'login tanpa angka' => [['fsysuserid' => 'ZZDUSK.A'], 'fsysuserid'],
            'login tanpa simbol' => [['fsysuserid' => 'ZZDUSK1'], 'fsysuserid'],
            'nama kosong' => [['fname' => ''], 'fname'],
            'nama lebih dari 100 karakter' => [['fname' => str_repeat('A', 101)], 'fname'],
            'password kosong' => [['password' => '', 'password_confirmation' => ''], 'password'],
            'password kurang dari 6 karakter' => [['password' => 'abc12', 'password_confirmation' => 'abc12'], 'password'],
            'konfirmasi password tidak cocok' => [['password_confirmation' => 'lain1234'], 'password'],
            'level tidak valid' => [['fuserlevel' => 'Super'], 'fuserlevel'],
        ];

        foreach ($cases as $label => [$override, $field]) {
            $this->store($override);
            $this->assertTrue($this->sessionHasError($field), "Kasus '$label': error '$field' tidak muncul. " . $this->lastFlash());
        }

        $this->assertNull($this->user(), 'Tidak boleh tersimpan saat validasi gagal.');
    }
}
