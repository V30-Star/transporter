<?php

namespace Tests\Http;

use App\Models\Account;
use App\Models\Currency;
use App\Models\Dealer;
use App\Models\Groupcustomer;
use App\Models\Groupproduct;
use App\Models\Merek;
use App\Models\Rekening;
use App\Models\Salesman;
use App\Models\Satuan;
use App\Models\Subaccount;
use App\Models\Supplier;
use App\Models\TypePembayaran;
use App\Models\Wh;
use App\Models\Wilayah;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * CRUD + validasi + index untuk master sederhana (kode + nama).
 * Menu baru: tambah satu baris di menus(). Semua tulisan di-rollback (lihat LiveDbTestCase).
 */
class MasterCrudHttpTest extends LiveDbTestCase
{
    private const CODE = 'ZZDUSK1';

    private const NAME = 'ZZ_DUSK_HTTP';

    public static function menus(): array
    {
        $m = fn (string $route, string $model, ?string $code, string $name, array $extra = [], ?string $perm = null) => [
            compact('route', 'model', 'code', 'name', 'extra', 'perm'),
        ];

        return [
            'groupcustomer' => $m('groupcustomer', Groupcustomer::class, 'fgroupcode', 'fgroupname'),
            'wilayah' => $m('wilayah', Wilayah::class, 'fwilayahcode', 'fwilayahname'),
            'merek' => $m('merek', Merek::class, 'fmerekcode', 'fmerekname'),
            'satuan' => $m('satuan', Satuan::class, 'fsatuancode', 'fsatuanname'),
            'groupproduct' => $m('groupproduct', Groupproduct::class, 'fgroupcode', 'fgroupname'),
            'dealer' => $m('dealer', Dealer::class, 'fdealercode', 'fdealername'),
            'salesman' => $m('salesman', Salesman::class, 'fsalesmancode', 'fsalesmanname'),
            'subaccount' => $m('subaccount', Subaccount::class, 'fsubaccountcode', 'fsubaccountname'),
            'currency' => $m('currency', Currency::class, 'fcurrcode', 'fcurrname', ['frate' => 1]),
            'rekening' => $m('rekening', Rekening::class, null, 'frekeningname'),
            'gudang' => $m('gudang', Wh::class, 'fwhcode', 'fwhname', ['faddress' => 'ZZ ALAMAT', 'fbranchcode' => 'ZZCAB']),
            'supplier' => $m('supplier', Supplier::class, 'fsuppliercode', 'fsuppliername', ['fnpwp' => '01.234.567.8-901.000', 'faddress' => 'ZZ ALAMAT', 'fcurr' => 'IDR']),
            'account' => $m('account', Account::class, 'faccount', 'faccname', ['fnormal' => 'D', 'fend' => '1', 'fuserlevel' => '1']),
            'typepembayaran' => $m('typepembayaran', TypePembayaran::class, null, 'fmastername', ['fnote1' => 'ZZACC'], 'TypePembayaran'),
        ];
    }

    private function payload(array $c, array $override = []): array
    {
        $base = [$c['name'] => self::NAME] + $c['extra'];
        if ($c['code']) {
            $base[$c['code']] = self::CODE;
        }

        return array_merge($base, $override);
    }

    #[DataProvider('menus')]
    public function test_crud(array $c): void
    {
        $model = $c['model'];
        $this->grant($c);

        $this->atomic(fn () => $this->post(route("{$c['route']}.store"), $this->payload($c)));
        $row = $this->find($c);
        $this->assertNotNull($row, 'Data tidak tersimpan. ' . $this->why());
        $id = $row->getKey();

        $this->get(route("{$c['route']}.view", $id))->assertOk();
        $this->get(route("{$c['route']}.edit", $id))->assertOk();

        $this->atomic(fn () => $this->patch(route("{$c['route']}.update", $id), $this->payload($c, [$c['name'] => self::NAME . '_EDIT'])));
        $this->assertSame(self::NAME . '_EDIT', $model::find($id)->{$c['name']}, 'Edit gagal. ' . $this->why());

        $this->atomic(fn () => $this->deleteJson(route("{$c['route']}.destroy", $id)));
        $this->assertNull($model::find($id), 'Delete gagal. ' . $this->why());
    }

    private function grant(array $c): void
    {
        if ($c['perm']) {
            $this->grantPermissions(...array_map(fn ($a) => $a . $c['perm'], ['view', 'create', 'update', 'delete']));
        }
    }

    // Cari baris uji lewat nilai accessor (sebagian kolom, mis. nama rekening, terenkripsi di DB).
    private function find(array $c): ?object
    {
        $model = $c['model'];

        return $model::where($c['name'], self::NAME)->first()
            ?? $model::all()->first(fn ($r) => $r->{$c['name']} === self::NAME);
    }

    // Pesan error terakhir dari aplikasi (flash/validasi) untuk diagnosis.
    private function why(): string
    {
        return json_encode([session('error'), session('errors')?->all(), 'status' => $this->lastResponse?->status(), 'to' => $this->lastResponse?->headers->get('Location'), 'body' => substr((string) $this->lastResponse?->getContent(), 0, 300)]);
    }

    #[DataProvider('menus')]
    public function test_validation_fails(array $c): void
    {
        $model = $c['model'];
        $this->grant($c);

        $this->atomic(fn () => $this->post(route("{$c['route']}.store"), $this->payload($c, [$c['name'] => ''])));
        $this->assertTrue(session('errors')?->has($c['name']) ?? false, "Nama kosong: error '{$c['name']}' tidak muncul. " . $this->why());

        if ($c['code']) {
            // Kode tanpa spasi tepi: input di-trim middleware, jadi kode berspasi di DB tidak bisa dipakai uji duplikat.
            $existing = $model::query()->whereRaw("{$c['code']} = trim({$c['code']})")->value($c['code']);
            $this->atomic(fn () => $this->post(route("{$c['route']}.store"), $this->payload($c, [$c['code'] => $existing])));
            $this->assertTrue(session('errors')?->has($c['code']) ?? false, "Kode duplikat ('$existing'): error '{$c['code']}' tidak muncul. " . $this->why());
        }

        $this->assertNull($this->find($c), 'Data tidak boleh tersimpan saat validasi gagal.');
    }

    #[DataProvider('menus')]
    public function test_index_opens(array $c): void
    {
        $this->grant($c);
        $this->get(route("{$c['route']}.index"))->assertOk();
    }

    /**
     * Data baru harus mendapat ID unik. Banyak tabel di DB ini tanpa PRIMARY KEY dan sequence-nya tertinggal
     * dari MAX(id), sehingga ID baru bisa sama dengan baris lama (edit/hapus lalu mengenai baris yang salah).
     */
    #[DataProvider('menus')]
    public function test_new_row_gets_unique_id(array $c): void
    {
        $model = $c['model'];
        $this->grant($c);

        $this->atomic(fn () => $this->post(route("{$c['route']}.store"), $this->payload($c)));
        $row = $this->find($c);
        $this->assertNotNull($row, 'Data tidak tersimpan. ' . $this->why());

        $pk = $row->getKeyName();
        $this->assertSame(1, $model::where($pk, $row->getKey())->count(), "ID baru {$row->getKey()} sudah dipakai baris lain (sequence tertinggal, tabel tanpa PRIMARY KEY).");
    }
}
