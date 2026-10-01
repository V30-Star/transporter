<?php

namespace Tests\Http;

use App\Models\RoleAccess;
use App\Models\Sysuser;
use Illuminate\Support\Facades\DB;

class RoleAccessHttpTest extends LiveDbTestCase
{
    private Sysuser $target;

    private Sysuser $source;

    protected function setUp(): void
    {
        parent::setUp();

        $this->grantPermissions('roleaccess', 'viewSysuser', 'createSysuser');

        $branch = trim((string) DB::table('mscabang')->value('fcabangkode'));
        foreach (['target' => 'ZZDUSK.T1', 'source' => 'ZZDUSK.S1'] as $prop => $login) {
            $this->atomic(fn () => $this->post(route('sysuser.store'), [
                'fsysuserid' => $login, 'fname' => 'zz ' . $prop, 'password' => 'rahasia1', 'password_confirmation' => 'rahasia1',
                'fsalesman' => '', 'fuserlevel' => 'User', 'fcabang' => $branch,
            ]));
            $this->$prop = Sysuser::where('fsysuserid', $login)->first();
            $this->assertNotNull($this->$prop, "User uji $login gagal dibuat. " . $this->lastFlash());
        }
    }

    private function role(Sysuser $user): ?RoleAccess
    {
        return RoleAccess::where('fusercreate', $user->fuid)->first();
    }

    private function save(Sysuser $user, ?array $permissions)
    {
        $data = ['fuid' => $user->fuid] + ($permissions === null ? [] : ['permission' => $permissions]);

        return $this->atomic(fn () => $this->post(route('roleaccess.store'), $data));
    }

    public function test_the_page_opens_for_a_user_with_and_without_access(): void
    {
        $this->get(route('roleaccess.index', $this->target->fuid))->assertOk();

        $this->save($this->target, ['viewProduct']);
        $this->get(route('roleaccess.index', $this->target->fuid))->assertOk();
    }

    public function test_saving_creates_updates_and_removes_the_role(): void
    {
        $this->assertNull($this->role($this->target));

        $this->save($this->target, ['viewProduct', 'createProduct']);
        $this->assertSame('viewProduct,createProduct', $this->role($this->target)?->fpermission, 'Role tidak tersimpan. ' . $this->lastFlash());

        $this->save($this->target, ['viewCustomer']);
        $this->assertSame('viewCustomer', $this->role($this->target)->fpermission, 'Role harus diganti, bukan ditambah.');
        $this->assertSame(1, RoleAccess::where('fusercreate', $this->target->fuid)->count(), 'Satu user hanya boleh punya satu role.');

        $this->save($this->target, null);
        $this->assertNull($this->role($this->target), 'Tanpa permission, role harus dihapus.');
    }

    public function test_permissions_endpoint_lists_the_saved_permissions(): void
    {
        $this->save($this->source, ['viewProduct', 'createProduct']);

        $this->get(route('roleaccess.permissions', $this->source->fuid))
            ->assertOk()
            ->assertJson(['permissions' => ['viewProduct', 'createProduct']]);

        $this->get(route('roleaccess.permissions', $this->target->fuid))->assertOk()->assertJson(['permissions' => []]);
    }

    public function test_clone_copies_the_permissions_from_another_user(): void
    {
        $this->save($this->source, ['viewProduct', 'createProduct']);

        $this->atomic(fn () => $this->post(route('roleaccess.clone'), [
            'source_fuid' => $this->source->fuid, 'fuid' => $this->target->fuid, 'fusercreate' => $this->target->fsysuserid,
        ]));
        $this->assertSame('viewProduct,createProduct', $this->role($this->target)?->fpermission, 'Clone tidak tersimpan. ' . $this->lastFlash());
        $this->assertSame('viewProduct,createProduct', $this->role($this->source)->fpermission, 'Role sumber tidak boleh berubah.');
    }

    public function test_the_role_cannot_be_saved_for_an_unknown_user_or_with_a_bad_payload(): void
    {
        $this->atomic(fn () => $this->post(route('roleaccess.store'), ['fuid' => 99999999, 'permission' => ['viewProduct']]));
        $this->assertTrue($this->sessionHasError('fuid'), $this->lastFlash());

        $this->atomic(fn () => $this->post(route('roleaccess.store'), ['fuid' => $this->target->fuid, 'permission' => 'viewProduct']));
        $this->assertTrue($this->sessionHasError('permission'), $this->lastFlash());
        $this->assertNull($this->role($this->target));

        $this->atomic(fn () => $this->post(route('roleaccess.clone'), ['source_fuid' => 99999999, 'fuid' => $this->target->fuid, 'fusercreate' => 'x']));
        $this->assertTrue($this->sessionHasError('source_fuid'), $this->lastFlash());
    }

    public function test_only_known_permission_names_can_be_saved(): void
    {
        $this->save($this->target, ['viewProduct', 'bukanPermissionApapun']);
        $this->assertNull($this->role($this->target), 'Nama permission yang tidak dikenal harus ditolak. ' . $this->lastFlash());
    }
}
