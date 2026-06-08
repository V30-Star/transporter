<?php

namespace Tests\Feature;

use App\Models\Sysuser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReportingPemakaianBarangTest extends TestCase
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

        Schema::create('account', function ($table) {
            $table->string('faccid')->primary();
            $table->string('faccount');
            $table->string('faccname');
            $table->string('fnonactive')->default('0');
        });

        Schema::create('mssubaccount', function ($table) {
            $table->string('fsubaccountid')->primary();
            $table->string('fsubaccountname');
        });

        Schema::create('mswh', function ($table) {
            $table->string('fwhcode')->primary();
            $table->string('fwhname');
            $table->string('fnonactive')->default('0');
        });

        Schema::create('trstockmt', function ($table) {
            $table->increments('fstockmtid');
            $table->string('fstockmtno')->unique();
            $table->string('fstockmtcode');
            $table->date('fstockmtdate');
            $table->string('ftrancode')->nullable();
            $table->string('frefno')->nullable();
            $table->string('fto')->nullable();
            $table->decimal('famount', 15, 2)->default(0);
            $table->string('fket')->nullable();
            $table->string('fusercreate')->nullable();
            $table->string('fbranchcode')->nullable();
            $table->string('ffrom')->nullable();
            $table->string('fsupplier')->nullable();
            $table->decimal('famountremain', 15, 2)->nullable();
            $table->decimal('famountpajak', 15, 2)->nullable();
            $table->decimal('famountmt', 15, 2)->nullable();
        });

        Schema::create('trstockdt', function ($table) {
            $table->increments('fstockdtid');
            $table->string('fstockmtno');
            $table->string('fprdcode');
            $table->string('fnouref')->nullable();
            $table->string('frefdtno')->nullable();
            $table->string('frefso')->nullable();
            $table->decimal('fqty', 15, 2)->default(0);
            $table->decimal('fqty_receive', 15, 2)->default(0);
            $table->decimal('fprice', 15, 2)->default(0);
            $table->decimal('ftotprice', 15, 2)->default(0);
            $table->decimal('famountmt', 15, 2)->default(0);
            $table->string('fketdt')->nullable();
        });

        Schema::create('msprd', function ($table) {
            $table->string('fprdcode')->primary();
            $table->string('fprdname');
        });

        DB::table('mscabang')->insert([
            ['fcabangkode' => 'JKT', 'fcabangname' => 'Jakarta'],
            ['fcabangkode' => 'BDG', 'fcabangname' => 'Bandung'],
        ]);

        DB::table('mssupplier')->insert([
            ['fsuppliercode' => 'SUP01', 'fsuppliername' => 'Supplier Satu'],
        ]);
    }

    public function test_reporting_pemakaianbarang_displays_branches()
    {
        $user = Sysuser::create([
            'fsysuserid' => 'admin',
            'fname' => 'Administrator',
            'password' => bcrypt('password'),
            'fcabang' => 'JKT',
        ]);

        session(['user_restricted_permissions' => 'semuacabang']);

        $response = $this->actingAs($user, 'sysuser')
            ->get(route('reportingpemakaianbarang.index'));

        $response->assertStatus(200);
        $response->assertViewHas('branches');
        $response->assertSee('JKT - Jakarta');
        $response->assertSee('BDG - Bandung');
    }

    public function test_reporting_pemakaianbarang_index_with_filter()
    {
        $user = Sysuser::create([
            'fsysuserid' => 'admin',
            'fname' => 'Administrator',
            'password' => bcrypt('password'),
            'fcabang' => 'JKT',
        ]);

        session(['user_restricted_permissions' => 'semuacabang']);

        $response = $this->actingAs($user, 'sysuser')
            ->get(route('reportingpemakaianbarang.index', [
                'filter_date_from' => '2026-06-01',
                'filter_date_to' => '2026-06-30',
            ]));

        $response->assertStatus(200);
    }
}
