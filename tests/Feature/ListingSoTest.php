<?php

namespace Tests\Feature;

use App\Models\Sysuser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ListingSoTest extends TestCase
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

        Schema::create('mscustomer', function ($table) {
            $table->increments('fcustomerid');
            $table->string('fcustomercode')->unique();
            $table->string('fcustomername');
        });

        Schema::create('msprd', function ($table) {
            $table->string('fprdcode')->primary();
            $table->string('fprdname');
        });

        DB::table('mscabang')->insert([
            ['fcabangkode' => 'JKT', 'fcabangname' => 'Jakarta'],
            ['fcabangkode' => 'BDG', 'fcabangname' => 'Bandung'],
        ]);

        DB::table('mscustomer')->insert([
            ['fcustomercode' => 'CUST01', 'fcustomername' => 'Customer Satu'],
        ]);

        DB::table('msprd')->insert([
            ['fprdcode' => 'PRD01', 'fprdname' => 'Produk Satu'],
        ]);
    }

    public function test_sales_order_listing_displays_branches()
    {
        $user = Sysuser::create([
            'fsysuserid' => 'admin',
            'fname' => 'Administrator',
            'password' => bcrypt('password'),
            'fcabang' => 'JKT',
        ]);

        session(['user_restricted_permissions' => 'semuacabang']);

        $response = $this->actingAs($user, 'sysuser')
            ->get(route('listingso.index'));

        $response->assertStatus(200);
        $response->assertViewHas('branches');
        $response->assertSee('JKT - Jakarta');
        $response->assertSee('BDG - Bandung');
    }
}
