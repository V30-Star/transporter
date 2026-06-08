<?php

namespace Tests\Feature;

use App\Models\Sysuser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReportingKasTest extends TestCase
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

        Schema::create('account', function ($table) {
            $table->string('faccid')->primary();
            $table->string('faccount')->unique();
            $table->string('faccname');
            $table->string('fnonactive')->default('0');
        });

        Schema::create('mssubaccount', function ($table) {
            $table->string('fsubaccountcode')->primary();
            $table->string('fsubaccountname');
        });

        Schema::create('trkasmt', function ($table) {
            $table->increments('fkasmtid');
            $table->string('fkasmtno')->unique();
            $table->string('ftrancode');
            $table->date('fkasmtdate');
            $table->string('faccountheader');
            $table->string('fket')->nullable();
            $table->string('fnogiro')->nullable();
            $table->decimal('famountpay', 15, 2)->default(0);
            $table->string('fgiromundur')->nullable();
            $table->string('fbranchcode')->nullable();
        });

        Schema::create('trkasdt', function ($table) {
            $table->increments('fkasdtid');
            $table->integer('fkasmtid');
            $table->string('faccount');
            $table->string('fsubaccount')->nullable();
            $table->string('fnote')->nullable();
            $table->decimal('fkasdtvalue', 15, 2)->default(0);
            $table->string('fdk')->nullable();
            $table->integer('fnou');
        });

        DB::table('mscabang')->insert([
            ['fcabangkode' => 'JKT', 'fcabangname' => 'Jakarta'],
            ['fcabangkode' => 'BDG', 'fcabangname' => 'Bandung'],
        ]);

        DB::table('account')->insert([
            ['faccid' => 'ACC01', 'faccount' => '10001', 'faccname' => 'Kas Besar', 'fnonactive' => '0'],
        ]);
    }

    public function test_reporting_kas_displays_branches()
    {
        $user = Sysuser::create([
            'fsysuserid' => 'admin',
            'fname' => 'Administrator',
            'password' => bcrypt('password'),
            'fcabang' => 'JKT',
        ]);

        session(['user_restricted_permissions' => 'semuacabang']);

        // Test BKK (Pengeluaran)
        $response1 = $this->actingAs($user, 'sysuser')
            ->get(route('reportingkas.pengeluaran.index'));

        $response1->assertStatus(200);
        $response1->assertViewHas('branches');
        $response1->assertSee('JKT - Jakarta');
        $response1->assertSee('BDG - Bandung');

        // Test BKM (Penerimaan)
        $response2 = $this->actingAs($user, 'sysuser')
            ->get(route('reportingkas.penerimaan.index'));

        $response2->assertStatus(200);
        $response2->assertViewHas('branches');
        $response2->assertSee('JKT - Jakarta');
        $response2->assertSee('BDG - Bandung');
    }

    public function test_reporting_kas_print_filtered()
    {
        $user = Sysuser::create([
            'fsysuserid' => 'admin',
            'fname' => 'Administrator',
            'password' => bcrypt('password'),
            'fcabang' => 'JKT',
        ]);

        session(['user_restricted_permissions' => 'semuacabang']);

        // Insert mock cash out (BKK)
        DB::table('trkasmt')->insert([
            'fkasmtno' => 'BKK-001',
            'ftrancode' => 'BKK',
            'fkasmtdate' => '2026-06-05',
            'faccountheader' => '10001',
            'famountpay' => 100000,
            'fbranchcode' => 'JKT',
        ]);

        // Print Pengeluaran Kas BKK
        $response = $this->actingAs($user, 'sysuser')
            ->get(route('reportingkas.pengeluaran.print', [
                'branch_codes' => ['JKT'],
                'filter_date_from' => '2026-06-01',
                'filter_date_to' => '2026-06-30',
            ]));

        $response->assertStatus(200);
        $response->assertSee('BKK-001');
    }
}
