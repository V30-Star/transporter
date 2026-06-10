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

        if (DB::getDriverName() === 'sqlite') {
            try {
                DB::statement("DETACH DATABASE public");
            } catch (\Exception $e) {
                // Ignore
            }
            DB::statement("ATTACH DATABASE ':memory:' AS public");

            Schema::create('public.trsomt', function ($table) {
                $table->increments('fstockmtid');
                $table->string('fsono');
                $table->string('fcustno');
                $table->string('fsalesman')->nullable();
                $table->string('fbranchcode');
                $table->date('fsodate');
                $table->string('fclose')->default('0');
                $table->decimal('famountso', 15, 2)->default(0);
                $table->decimal('famountgross', 15, 2)->default(0);
                $table->decimal('fdiscount', 15, 2)->default(0);
                $table->decimal('famountpajak', 15, 2)->default(0);
            });

            Schema::create('public.trsodt', function ($table) {
                $table->increments('fstockdtid');
                $table->string('fsono');
                $table->string('fitemno');
                $table->string('fprdcode');
                $table->string('fitemdesc');
                $table->decimal('fqty', 15, 2);
                $table->decimal('fprice', 15, 2);
            });

            Schema::create('public.mscustomer', function ($table) {
                $table->increments('fcustomerid');
                $table->string('fcustomercode')->unique();
                $table->string('fcustomername');
            });

            Schema::create('public.mssalesman', function ($table) {
                $table->string('fsalesmancode')->primary();
                $table->string('fsalesmanname');
            });
        }
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

    public function test_print_shows_all_so_when_filter_is_all()
    {
        $user = Sysuser::create([
            'fsysuserid' => 'admin',
            'fname' => 'Administrator',
            'password' => bcrypt('password'),
            'fcabang' => 'JKT',
        ]);

        session(['user_restricted_permissions' => 'semuacabang']);

        DB::table('public.mscustomer')->insert([
            'fcustomercode' => 'CUST01',
            'fcustomername' => 'Customer Satu',
        ]);

        DB::table('public.trsomt')->insert([
            [
                'fsono' => 'SO-001',
                'fcustno' => 'CUST01',
                'fsalesman' => null,
                'fbranchcode' => 'JKT',
                'fsodate' => date('Y-m-d'),
                'fclose' => '0',
                'famountso' => 100000,
                'famountgross' => 100000,
                'fdiscount' => 0,
                'famountpajak' => 0,
            ],
            [
                'fsono' => 'SO-002',
                'fcustno' => 'CUST01',
                'fsalesman' => null,
                'fbranchcode' => 'JKT',
                'fsodate' => date('Y-m-d'),
                'fclose' => '1',
                'famountso' => 200000,
                'famountgross' => 200000,
                'fdiscount' => 0,
                'famountpajak' => 0,
            ],
        ]);

        DB::table('public.trsodt')->insert([
            [
                'fsono' => 'SO-001',
                'fitemno' => 'PRD01',
                'fprdcode' => 'PRD01',
                'fitemdesc' => 'Produk Satu',
                'fqty' => 1,
                'fprice' => 100000,
            ],
            [
                'fsono' => 'SO-002',
                'fitemno' => 'PRD01',
                'fprdcode' => 'PRD01',
                'fitemdesc' => 'Produk Satu',
                'fqty' => 2,
                'fprice' => 100000,
            ],
        ]);

        $response = $this->actingAs($user, 'sysuser')
            ->get(route('listingso.print', [
                'so_filter' => 'all',
                'branch_codes' => ['JKT'],
                'date_from' => date('Y-m-d'),
                'date_to' => date('Y-m-d'),
            ]));

        $response->assertStatus(200);
        $response->assertSee('SO-001');
        $response->assertSee('SO-002');
    }

    public function test_print_shows_only_pending_so_when_filter_is_only_pending()
    {
        $user = Sysuser::create([
            'fsysuserid' => 'admin',
            'fname' => 'Administrator',
            'password' => bcrypt('password'),
            'fcabang' => 'JKT',
        ]);

        session(['user_restricted_permissions' => 'semuacabang']);

        DB::table('public.mscustomer')->insert([
            'fcustomercode' => 'CUST01',
            'fcustomername' => 'Customer Satu',
        ]);

        DB::table('public.trsomt')->insert([
            [
                'fsono' => 'SO-001',
                'fcustno' => 'CUST01',
                'fsalesman' => null,
                'fbranchcode' => 'JKT',
                'fsodate' => date('Y-m-d'),
                'fclose' => '0',
                'famountso' => 100000,
                'famountgross' => 100000,
                'fdiscount' => 0,
                'famountpajak' => 0,
            ],
            [
                'fsono' => 'SO-002',
                'fcustno' => 'CUST01',
                'fsalesman' => null,
                'fbranchcode' => 'JKT',
                'fsodate' => date('Y-m-d'),
                'fclose' => '1',
                'famountso' => 200000,
                'famountgross' => 200000,
                'fdiscount' => 0,
                'famountpajak' => 0,
            ],
        ]);

        DB::table('public.trsodt')->insert([
            [
                'fsono' => 'SO-001',
                'fitemno' => 'PRD01',
                'fprdcode' => 'PRD01',
                'fitemdesc' => 'Produk Satu',
                'fqty' => 1,
                'fprice' => 100000,
            ],
            [
                'fsono' => 'SO-002',
                'fitemno' => 'PRD01',
                'fprdcode' => 'PRD01',
                'fitemdesc' => 'Produk Satu',
                'fqty' => 2,
                'fprice' => 100000,
            ],
        ]);

        $response = $this->actingAs($user, 'sysuser')
            ->get(route('listingso.print', [
                'so_filter' => 'only_pending',
                'branch_codes' => ['JKT'],
                'date_from' => date('Y-m-d'),
                'date_to' => date('Y-m-d'),
            ]));

        $response->assertStatus(200);
        $response->assertSee('SO-001');
        $response->assertDontSee('SO-002');
    }

    public function test_print_filters_by_single_customer()
    {
        $user = Sysuser::create([
            'fsysuserid' => 'admin',
            'fname' => 'Administrator',
            'password' => bcrypt('password'),
            'fcabang' => 'JKT',
        ]);

        session(['user_restricted_permissions' => 'semuacabang']);

        DB::table('public.mscustomer')->insert([
            ['fcustomercode' => 'CUST01', 'fcustomername' => 'Customer Satu'],
            ['fcustomercode' => 'CUST02', 'fcustomername' => 'Customer Dua'],
        ]);

        DB::table('public.trsomt')->insert([
            [
                'fsono' => 'SO-001',
                'fcustno' => 'CUST01',
                'fsalesman' => null,
                'fbranchcode' => 'JKT',
                'fsodate' => date('Y-m-d'),
                'fclose' => '0',
                'famountso' => 100000,
                'famountgross' => 100000,
                'fdiscount' => 0,
                'famountpajak' => 0,
            ],
            [
                'fsono' => 'SO-002',
                'fcustno' => 'CUST02',
                'fsalesman' => null,
                'fbranchcode' => 'JKT',
                'fsodate' => date('Y-m-d'),
                'fclose' => '0',
                'famountso' => 200000,
                'famountgross' => 200000,
                'fdiscount' => 0,
                'famountpajak' => 0,
            ],
        ]);

        DB::table('public.trsodt')->insert([
            [
                'fsono' => 'SO-001',
                'fitemno' => 'PRD01',
                'fprdcode' => 'PRD01',
                'fitemdesc' => 'Produk Satu',
                'fqty' => 1,
                'fprice' => 100000,
            ],
            [
                'fsono' => 'SO-002',
                'fitemno' => 'PRD01',
                'fprdcode' => 'PRD01',
                'fitemdesc' => 'Produk Satu',
                'fqty' => 2,
                'fprice' => 100000,
            ],
        ]);

        $response = $this->actingAs($user, 'sysuser')
            ->get(route('listingso.print', [
                'customer_code' => 'CUST01',
                'branch_codes' => ['JKT'],
                'date_from' => date('Y-m-d'),
                'date_to' => date('Y-m-d'),
            ]));

        $response->assertStatus(200);
        $response->assertSee('SO-001');
        $response->assertDontSee('SO-002');
    }
}
