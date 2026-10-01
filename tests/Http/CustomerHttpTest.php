<?php

namespace Tests\Http;

use Illuminate\Support\Facades\DB;

class CustomerHttpTest extends LiveDbTestCase
{
    private const NAME = 'ZZ_DUSK_HTTP';

    private function payload(array $override = []): array
    {
        return array_merge([
            'fcustomername' => self::NAME,
            'fhargalevel' => '0',
            'fkodefp' => '010',
            'ftempo' => 0,
            'fmaxtempo' => 0,
        ], $override);
    }

    private function row(string $name = self::NAME): ?object
    {
        return DB::table('mscustomer')->where('fcustomername', $name)->first();
    }

    public function test_crud(): void
    {
        $this->post(route('customer.store'), $this->payload())
            ->assertRedirect(route('customer.create'))
            ->assertSessionHas('success');
        $row = $this->row();
        $this->assertNotNull($row, 'Customer tidak tersimpan.');

        $this->get(route('customer.view', $row->fcustomerid))->assertOk()->assertSee(self::NAME);
        $this->get(route('customer.edit', $row->fcustomerid))->assertOk();

        $this->patch(route('customer.update', $row->fcustomerid), $this->payload([
            'fcustomercode' => $row->fcustomercode,
            'fcustomername' => self::NAME . '_EDIT',
        ]));
        $this->assertNotNull($this->row(self::NAME . '_EDIT'), 'Edit customer gagal.');

        $this->get(route('customer.delete', $row->fcustomerid))->assertOk();
        $this->deleteJson(route('customer.destroy', $row->fcustomerid))->assertOk()->assertJson(['success' => true]);
        $this->assertNull($this->row(self::NAME . '_EDIT'), 'Delete customer gagal.');
    }

    public function test_create_with_all_fields(): void
    {
        $this->post(route('customer.store'), $this->payload([
            'fnpwp' => '01.234.567.8-901.000',
            'ftelp' => '021555123',
            'ffax' => '021555124',
            'femail' => 'zz_dusk@example.com',
            'fkontakperson' => 'ZZ KONTAK',
            'fjabatan' => 'ZZ JABATAN',
            'faddress' => 'ZZ ALAMAT SURAT',
            'fkirimaddress1' => 'ZZ KIRIM 1',
            'ftaxaddress' => 'ZZ ALAMAT PAJAK',
            'fhargalevel' => '1',
            'fjadwaltukarfakturmingguan' => '2',
            'fjadwaltukarfakturhari' => '1',
            'fmemo' => 'ZZ MEMO',
        ]))->assertSessionHas('success');

        $row = $this->row();
        $this->assertSame('01.234.567.8-901.000', $row->fnpwp);
        $this->assertSame('021555123', $row->ftelp);
        $this->assertSame('zz_dusk@example.com', $row->femail);
        $this->assertSame('ZZ ALAMAT SURAT', $row->faddress);
        $this->assertSame('ZZ ALAMAT PAJAK', $row->ftaxaddress);
        $this->assertEquals(1, $row->fhargalevel);
        $this->assertEquals(2, $row->fjadwaltukarfakturmingguan);
        $this->assertEquals(1, $row->fjadwaltukarfakturhari);
    }

    public function test_validation_fails(): void
    {
        $code = DB::table('mscustomer')->whereRaw('length(fcustomercode) <= 10')->value('fcustomercode');

        $cases = [
            'nama kosong' => [['fcustomername' => ''], 'fcustomername'],
            'NPWP dan NIK bersamaan' => [['fnpwp' => '01.234.567.8-901.000', 'fnik' => '3171234567890001', 'fnamaktp' => 'ZZ KTP'], 'fnpwp'],
            'NIK tanpa nama KTP' => [['fnik' => '3171234567890001'], 'fnamaktp'],
            'kode duplikat' => [['fcustomercode' => $code], 'fcustomercode'],
        ];

        foreach ($cases as $label => [$override, $errorField]) {
            $response = $this->post(route('customer.store'), $this->payload($override));
            $this->assertTrue(session('errors')?->has($errorField) ?? false, "Kasus '$label': error '$errorField' tidak muncul.");
        }

        $this->assertNull($this->row(), 'Customer tidak boleh tersimpan saat validasi gagal.');
    }

    public function test_index_page_search_and_status_filter(): void
    {
        $name = DB::table('mscustomer')->where('fnonactive', '0')->value('fcustomername');

        $this->get(route('customer.index'))->assertOk();

        $ajax = ['X-Requested-With' => 'XMLHttpRequest'];
        $this->withHeaders($ajax)->get(route('customer.index', ['draw' => 1, 'start' => 0, 'length' => 10, 'search' => ['value' => $name]]))
            ->assertOk()->assertJsonPath('data.0.fcustomername', $name);

        foreach (['all', 'active', 'nonactive'] as $status) {
            $this->withHeaders($ajax)->get(route('customer.index', ['draw' => 1, 'start' => 0, 'length' => 10, 'status' => $status]))
                ->assertOk()->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
        }
    }

    public function test_delete_locked_when_used_in_transaction(): void
    {
        $id = null;
        foreach (['trsomt' => 'fcustno', 'tranmt' => 'fcustno', 'trtagihanmt' => 'fcustno', 'trkasmt' => 'fcustomercode', 'trsisadp_penjualan' => 'fcustno'] as $table => $col) {
            $id = DB::table('mscustomer')->whereIn('fcustomercode', DB::table($table)->select($col))->value('fcustomerid');
            if ($id) {
                break;
            }
        }
        if (! $id) {
            $this->markTestSkipped('Tidak ada customer yang dipakai transaksi.');
        }

        $this->get(route('customer.delete', $id))->assertRedirect(route('customer.edit', $id))->assertSessionHas('error');
        $this->deleteJson(route('customer.destroy', $id))->assertStatus(422)->assertJson(['success' => false]);
        $this->assertTrue(DB::table('mscustomer')->where('fcustomerid', $id)->exists());
    }
}
