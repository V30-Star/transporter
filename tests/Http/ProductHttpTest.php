<?php

namespace Tests\Http;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductHttpTest extends LiveDbTestCase
{
    private const CODE = 'ZZDUSK1';

    private const NAME = 'ZZ_DUSK_HTTP';

    private function payload(array $override = []): array
    {
        // Kode group/merek/satuan diambil dari master yang sudah ada (tanpa spasi tepi, karena input di-trim).
        $code = fn (string $table, string $col) => DB::table($table)->whereRaw("$col = trim($col)")->value($col);

        return array_merge([
            'fprdcode' => self::CODE,
            'fprdname' => self::NAME,
            'ftype' => 'Produk',
            'fgroupcode' => $code('ms_groupprd', 'fgroupcode'),
            'fmerek' => $code('msmerek', 'fmerekcode'),
            'fsatuankecil' => $code('mssatuan', 'fsatuancode'),
            'fsatuandefault' => '1',
        ], $override);
    }

    private function row(): ?Product
    {
        return Product::where('fprdcode', self::CODE)->first();
    }

    private function why(): string
    {
        return json_encode([session('error'), session('errors')?->all(), 'status' => $this->lastResponse?->status()]);
    }

    private function hasErrors(string $field): bool
    {
        return session('errors')?->has($field) ?? false;
    }

    public function test_crud(): void
    {
        $this->atomic(fn () => $this->post(route('product.store'), $this->payload()));
        $row = $this->row();
        $this->assertNotNull($row, 'Produk tidak tersimpan. ' . $this->why());
        $id = $row->getKey();

        $this->get(route('product.view', $id))->assertOk();
        $this->get(route('product.edit', $id))->assertOk();

        $this->atomic(fn () => $this->patch(route('product.update', $id), $this->payload(['fprdname' => self::NAME . '_EDIT'])));
        $this->assertSame(self::NAME . '_EDIT', Product::find($id)->fprdname, 'Edit gagal. ' . $this->why());

        $this->atomic(fn () => $this->deleteJson(route('product.destroy', $id)));
        $this->assertNull(Product::find($id), 'Delete gagal. ' . $this->why());
    }

    // ---------- Approval produk (permission approveProduct + set_default BolehApproveProduk) ----------

    private function setApprover(bool $can): void
    {
        $role = \App\Models\RoleAccess::where('fusercreate', auth('sysuser')->user()->fuid)->firstOrFail();
        $list = array_values(array_filter(array_map('trim', explode(',', (string) $role->fpermission)), fn ($p) => strtolower($p) !== 'approveproduct'));
        if ($can) {
            $list[] = 'approveProduct';
        }
        $role->fpermission = implode(',', $list);
        $role->save();
        session(['user_restricted_permissions' => $role->fpermission]);
    }

    private function setAutoApprove(int $value): void
    {
        DB::table('set_default')->updateOrInsert(['fdefaultname' => 'BolehApproveProduk'], ['fdefaultvalue' => $value]);
    }

    private function storeProduct(array $override = []): ?Product
    {
        $this->atomic(fn () => $this->post(route('product.store'), $this->payload($override)));

        return $this->row();
    }

    public function test_auto_approve_applies_to_a_user_without_the_approve_permission(): void
    {
        $this->setApprover(false);
        $this->setAutoApprove(1);

        $p = $this->storeProduct();
        $this->assertNotNull($p, $this->why());
        $this->assertSame('1', trim((string) $p->fapproval), 'set_default = 1: produk baru langsung disetujui.');
        $this->assertNotEmpty($p->fuserapproved);
        $this->assertNotNull($p->fdateapproved);
    }

    public function test_without_auto_approve_a_plain_user_creates_an_unapproved_product(): void
    {
        $this->setApprover(false);
        $this->setAutoApprove(0);

        $p = $this->storeProduct(['approve_now' => '1']);
        $this->assertNotNull($p, $this->why());
        $this->assertSame('0', trim((string) $p->fapproval), 'Tanpa auto approve dan tanpa hak approve, produk menunggu.');
        $this->assertNull($p->fuserapproved);
    }

    public function test_an_approver_can_switch_auto_approve_off_or_on_per_product(): void
    {
        $this->setApprover(true);

        $this->setAutoApprove(1);
        $this->assertSame('0', trim((string) $this->storeProduct(['approve_now' => '0'])->fapproval), 'Approver memilih tidak approve.');
        DB::table('msprd')->where('fprdcode', self::CODE)->delete();
        $this->assertSame('1', trim((string) $this->storeProduct()->fapproval), 'Tanpa pilihan eksplisit, mengikuti auto approve.');
        DB::table('msprd')->where('fprdcode', self::CODE)->delete();

        $this->setAutoApprove(0);
        $this->assertSame('1', trim((string) $this->storeProduct(['approve_now' => '1'])->fapproval), 'Approver memilih approve.');
    }

    public function test_editing_an_unapproved_product_keeps_it_waiting_even_when_auto_approve_is_on(): void
    {
        $this->setApprover(false);
        $this->setAutoApprove(0);
        $p = $this->storeProduct();
        $this->assertSame('0', trim((string) $p->fapproval));

        $this->setAutoApprove(1);
        $this->atomic(fn () => $this->patch(route('product.update', $p->getKey()), $this->payload(['fprdname' => self::NAME . '_EDIT'])));
        $p = Product::find($p->getKey());
        $this->assertSame(self::NAME . '_EDIT', $p->fprdname, $this->why());
        $this->assertSame('0', trim((string) $p->fapproval), 'Produk lama tetap menunggu approval saat diedit.');
    }

    public function test_the_create_form_shows_the_approve_toggle_checked_only_when_auto_approve_is_on(): void
    {
        $this->setAutoApprove(1);
        $this->assertMatchesRegularExpression('/id="approvalToggle"[^>]*checked/', $this->get(route('product.create'))->getContent(), 'Toggle Approve harus tercentang.');

        $this->setAutoApprove(0);
        $this->assertDoesNotMatchRegularExpression('/id="approvalToggle"[^>]*checked/', $this->get(route('product.create'))->getContent(), 'Toggle Approve tidak tercentang.');
    }

    public function test_code_is_generated_when_empty(): void
    {
        $this->atomic(fn () => $this->post(route('product.store'), $this->payload(['fprdcode' => '', 'fprdname' => self::NAME . '_AUTO'])));
        $row = Product::where('fprdname', self::NAME . '_AUTO')->first();
        $this->assertNotNull($row, 'Produk tidak tersimpan. ' . $this->why());
        $this->assertNotEmpty(trim((string) $row->fprdcode), 'Kode produk tidak dibuat otomatis.');
        $this->assertSame(trim($row->fprdcode), trim((string) $row->fbarcode), 'Barcode kosong harus memakai kode produk.');
    }

    public function test_validation_fails(): void
    {
        $existing = Product::query()->whereRaw('fprdcode = trim(fprdcode)')->value('fprdcode');
        $satuan = $this->payload()['fsatuankecil'];

        $cases = [
            'nama kosong' => [['fprdname' => ''], 'fprdname'],
            'tipe produk di luar Produk/Jasa' => [['ftype' => 'Barang'], 'ftype'],
            'tipe produk kosong' => [['ftype' => ''], 'ftype'],
            'group kosong' => [['fgroupcode' => ''], 'fgroupcode'],
            'merek kosong' => [['fmerek' => ''], 'fmerek'],
            'satuan 1 kosong' => [['fsatuankecil' => ''], 'fsatuankecil'],
            'kode duplikat' => [['fprdcode' => $existing], 'fprdcode'],
            'satuan 2 sama dengan satuan 1' => [['fsatuanbesar' => $satuan, 'fqtykecil' => 12], 'fsatuanbesar'],
            'satuan default 2 tanpa satuan 2' => [['fsatuandefault' => '2'], 'fsatuandefault'],
            'isi satuan 2 tidak lebih dari 1' => [['fsatuanbesar' => 'ZZSATUAN', 'fqtykecil' => 1], 'fqtykecil'],
        ];

        foreach ($cases as $label => [$override, $field]) {
            $this->atomic(fn () => $this->post(route('product.store'), $this->payload($override)));
            $this->assertTrue($this->hasErrors($field), "Kasus '$label': error '$field' tidak muncul. " . $this->why());
        }

        $this->assertNull($this->row(), 'Produk tidak boleh tersimpan saat validasi gagal.');
    }

    public function test_index_opens(): void
    {
        $this->get(route('product.index'))->assertOk();
    }

    public function test_new_row_gets_unique_id(): void
    {
        $this->atomic(fn () => $this->post(route('product.store'), $this->payload()));
        $row = $this->row();
        $this->assertNotNull($row, 'Produk tidak tersimpan. ' . $this->why());
        $this->assertSame(1, Product::where('fprdid', $row->getKey())->count(), "ID baru {$row->getKey()} sudah dipakai baris lain.");
    }
}
