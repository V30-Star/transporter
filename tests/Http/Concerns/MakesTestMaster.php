<?php

namespace Tests\Http\Concerns;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Data master khusus uji (kode ZZDUSK*). Dibuat lewat route aplikasi sendiri di dalam transaksi test,
 * jadi ikut di-rollback dan tidak menyentuh master asli.
 */
trait MakesTestMaster
{
    protected function makeSupplier(string $code = 'ZZDUSKS1'): string
    {
        $this->grantPermissions('createSupplier');
        $this->atomic(fn () => $this->post(route('supplier.store'), [
            'fsuppliercode' => $code,
            'fsuppliername' => 'ZZ_DUSK_SUPPLIER',
            'fnpwp' => '01.234.567.8-901.000',
            'faddress' => 'ZZ ALAMAT',
            'fcurr' => 'IDR',
        ]));
        $this->assertTrue(DB::table('mssupplier')->where('fsuppliercode', $code)->exists(), 'Supplier uji gagal dibuat. ' . $this->lastFlash());

        return $code;
    }

    /** Customer uji (kode dibuat otomatis oleh aplikasi). Mengembalikan kode customer. */
    protected function makeCustomer(string $name = 'ZZ_DUSK_CUSTOMER'): string
    {
        $this->grantPermissions('createCustomer');
        $this->atomic(fn () => $this->post(route('customer.store'), ['fcustomername' => $name, 'fhargalevel' => '0', 'fkodefp' => '010', 'ftempo' => 0, 'fmaxtempo' => 0]));
        $code = DB::table('mscustomer')->where('fcustomername', $name)->value('fcustomercode');
        $this->assertNotEmpty($code, 'Customer uji gagal dibuat. ' . $this->lastFlash());

        return trim($code);
    }

    /** Account kas/bank uji: anak dari KASBANKHEADER, dengan inisial jurnal 2 huruf yang belum dipakai. */
    protected function makeCashAccount(string $code = 'ZZDUSKK1'): string
    {
        $header = trim((string) DB::table('set_account')->where('faccount_name', 'KASBANKHEADER')->value('faccount'));
        $this->assertNotSame('', $header, 'set_account KASBANKHEADER belum diatur.');

        $used = DB::table('account')->whereNotNull('finitjurnal')->pluck('finitjurnal')->map(fn ($v) => strtoupper(trim($v)))->all();
        $init = collect(range('A', 'Z'))->map(fn ($c) => 'Z' . $c)->first(fn ($i) => ! in_array($i, $used, true));

        $this->atomic(fn () => $this->post(route('account.store'), [
            'faccount' => $code,
            'faccname' => 'ZZ KAS UJI',
            'faccupline' => $header,
            'finitjurnal' => $init,
            'fnormal' => 'D',
            'fend' => '1',
            'fuserlevel' => '1',
        ]));
        $this->assertTrue(DB::table('account')->where('faccount', $code)->exists(), 'Account kas uji gagal dibuat. ' . $this->lastFlash());

        return $code;
    }

    /** Account detail biasa (bukan header, aktif) untuk baris jurnal/kas. */
    protected function makeDetailAccount(string $code = 'ZZDUSKD1', string $normal = 'K'): string
    {
        $this->atomic(fn () => $this->post(route('account.store'), [
            'faccount' => $code,
            'faccname' => 'ZZ DETAIL UJI',
            'fnormal' => $normal,
            'fend' => '1',
            'fuserlevel' => '1',
        ]));
        $this->assertTrue(DB::table('account')->where('faccount', $code)->exists(), 'Account detail uji gagal dibuat. ' . $this->lastFlash());

        return $code;
    }

    protected function makeProduct(string $code = 'ZZDUSKP1'): Product
    {
        $this->grantPermissions('createProduct');
        $pick = fn (string $table, string $col) => DB::table($table)->whereRaw("$col = trim($col)")->value($col);

        $this->atomic(fn () => $this->post(route('product.store'), [
            'fprdcode' => $code,
            'fprdname' => 'ZZ_DUSK_PRODUK',
            'ftype' => 'Produk',
            'fgroupcode' => $pick('ms_groupprd', 'fgroupcode'),
            'fmerek' => $pick('msmerek', 'fmerekcode'),
            'fsatuankecil' => $pick('mssatuan', 'fsatuancode'),
            'fsatuandefault' => '1',
        ]));
        $product = Product::where('fprdcode', $code)->first();
        $this->assertNotNull($product, 'Produk uji gagal dibuat. ' . $this->lastFlash());

        return $product;
    }
}
