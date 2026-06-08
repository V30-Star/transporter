<?php

namespace Tests\Feature;

use App\Models\Sysuser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ListingPenjualanTest extends TestCase
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

        Schema::create('ms_groupprd', function ($table) {
            $table->string('fgroupcode')->primary();
            $table->string('fgroupname');
        });

        Schema::create('msmerek', function ($table) {
            $table->string('fmerekcode')->primary();
            $table->string('fmerekname');
        });

        Schema::create('mssalesman', function ($table) {
            $table->string('fsalesmancode')->primary();
            $table->string('fsalesmanname');
        });

        DB::table('mscabang')->insert([
            ['fcabangkode' => 'JKT', 'fcabangname' => 'Jakarta'],
            ['fcabangkode' => 'BDG', 'fcabangname' => 'Bandung'],
        ]);

        DB::table('ms_groupprd')->insert([
            ['fgroupcode' => 'G01', 'fgroupname' => 'Group Satu'],
        ]);

        DB::table('msmerek')->insert([
            ['fmerekcode' => 'M01', 'fmerekname' => 'Merek Satu'],
        ]);

        DB::table('mssalesman')->insert([
            ['fsalesmancode' => 'S01', 'fsalesmanname' => 'Salesman Satu'],
        ]);
    }

    public function test_sales_listing_displays_branches()
    {
        $user = Sysuser::create([
            'fsysuserid' => 'admin',
            'fname' => 'Administrator',
            'password' => bcrypt('password'),
            'fcabang' => 'JKT',
        ]);

        session(['user_restricted_permissions' => 'semuacabang']);

        $response = $this->actingAs($user, 'sysuser')
            ->get(route('listingpenjualan.index'));

        $response->assertStatus(200);
        $response->assertViewHas('branches');
        $response->assertSee('JKT - Jakarta');
        $response->assertSee('BDG - Bandung');
    }
}
