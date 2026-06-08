<?php

namespace Tests\Feature;

use App\Models\Sysuser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ListingPenerimaanBarangTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('sysuser', function ($table) {
            $table->increments('fuid');
            $table->string('fsysuserid')->unique();
            $table->string('fname');
            $table->string('password');
            $table->string('fcabang')->nullable();
            $table->timestamps();
        });

        Schema::create('mscabang', function ($table) {
            $table->string('fcabangkode')->primary();
            $table->string('fcabangname');
        });

        Schema::create('mssupplier', function ($table) {
            $table->increments('fsupplierid');
            $table->string('fsuppliercode')->unique();
            $table->string('fsuppliername');
        });

        Schema::create('mswh', function ($table) {
            $table->increments('fwhid');
            $table->string('fwhcode')->unique();
            $table->string('fwhname');
            $table->string('fnonactive')->default('0');
        });

        DB::table('mscabang')->insert([
            ['fcabangkode' => 'JKT', 'fcabangname' => 'Jakarta'],
            ['fcabangkode' => 'BDG', 'fcabangname' => 'Bandung'],
        ]);

        DB::table('mssupplier')->insert([
            ['fsuppliercode' => 'SUP01', 'fsuppliername' => 'Supplier Satu'],
        ]);

        DB::table('mswh')->insert([
            ['fwhcode' => 'G01', 'fwhname' => 'Gudang Utama', 'fnonactive' => '0'],
        ]);
    }

    public function test_goods_receipt_listing_displays_branches()
    {
        $user = Sysuser::create([
            'fsysuserid' => 'admin',
            'fname' => 'Administrator',
            'password' => bcrypt('password'),
            'fcabang' => 'JKT',
        ]);

        session(['user_restricted_permissions' => 'semuacabang']);

        $response = $this->actingAs($user, 'sysuser')
            ->get(route('listingpenerimaanbarang.index'));

        $response->assertStatus(200);
        $response->assertViewHas('branches');
        $response->assertSee('JKT - Jakarta');
        $response->assertSee('BDG - Bandung');
    }
}
