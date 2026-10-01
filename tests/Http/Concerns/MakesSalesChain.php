<?php

namespace Tests\Http\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Rantai penjualan uji (di dalam transaksi test, di-rollback), di atas rantai pembelian (stok 6 unit):
 * customer -> SO (5 @ 10.000) -> surat jalan (3) -> faktur penjualan (3, total 30.000, kredit).
 */
trait MakesSalesChain
{
    use MakesPurchaseChain;

    protected string $customer;

    protected object $so;

    protected object $srj;

    protected object $inv;

    protected function setUpSalesChain(): void
    {
        $this->grantPermissions(
            'viewInvoice', 'createInvoice', 'updateInvoice', 'deleteInvoice',
            'viewSuratJalan', 'createSuratJalan', 'updateSuratJalan', 'deleteSuratJalan',
            'viewSalesOrder', 'createSalesOrder', 'updateSalesOrder', 'deleteSalesOrder',
        );

        $range = [now()->startOfDay(), now()->endOfDay()];
        $today = max(
            DB::table('tranmt')->whereIn('ftrcode', ['INV', 'REJ'])->whereBetween('fdatetime', $range)->count(),
            DB::table('trsomt')->whereBetween('fdatetime', $range)->count(),
            DB::table('trstockmt')->whereIn('fstockmtcode', ['SRJ', 'REJ'])->whereBetween('fdatetime', $range)->count(),
        );
        if ($today >= 10) {
            $this->markTestSkipped("Batas harian dokumen hampir tercapai ($today hari ini).");
        }

        $this->setUpPurchaseChain();
        $this->customer = $this->makeCustomer();
        $branch = auth('sysuser')->user()->fcabang;

        $this->atomic(fn () => $this->post(route('salesorder.store'), [
            'fsodate' => now()->format('Y-m-d'), 'fcustno' => $this->customer, 'fket' => 'ZZ_DUSK_SO_REF',
            'fbranchcode' => substr((string) $branch, 0, 2),
            'fprdcode' => [$this->productCode], 'fsatuan' => [$this->unit], 'fqty' => [5], 'fprice' => [10000],
            'fdisc' => ['0'], 'fnoacak' => ['123'], 'fdesc' => [''],
        ]));
        $this->so = DB::table('trsomt')->where('fket', 'ZZ_DUSK_SO_REF')->first();
        $this->assertNotNull($this->so, 'SO uji gagal dibuat. ' . $this->lastFlash());

        $this->atomic(fn () => $this->post(route('suratjalan.store'), [
            'fstockmtdate' => now()->format('Y-m-d'), 'fsupplier' => $this->customer, 'ffrom' => $this->gudang,
            'fket' => 'ZZ_DUSK_SRJ_REF', 'fkirim' => 'ZZ', 'fbranchcode' => $branch,
            'fitemcode' => [$this->productCode], 'fsatuan' => [$this->unit], 'frefso' => [$this->so->fsono],
            'frefnoacak' => ['123'], 'fqty' => [3], 'fprice' => [10000], 'fdiscpersen' => [0], 'fnoacak' => ['123'], 'fdesc' => [''],
        ]));
        $this->srj = DB::table('trstockmt')->where('fstockmtcode', 'SRJ')->where('fket', 'ZZ_DUSK_SRJ_REF')->first();
        $this->assertNotNull($this->srj, 'Surat jalan uji gagal dibuat. ' . $this->lastFlash());

        $this->atomic(fn () => $this->post(route('invoice.store'), [
            'fsodate' => now()->format('Y-m-d'), 'fcustno' => $this->customer, 'fket' => 'ZZ_DUSK_INV_REF', 'fbranchcode' => $branch,
            'ftypesales' => 0, 'fitemcode' => [$this->productCode], 'fsatuan' => [$this->unit],
            'frefsrj' => [$this->srj->fstockmtno], 'frefdtno' => [$this->srj->fstockmtno], 'frefnoacak' => ['123'],
            'fqty' => [3], 'fprice' => [10000], 'fdisc' => ['0'], 'fnoacak' => ['123'], 'fdesc' => [''],
        ]));
        $this->inv = DB::table('tranmt')->where('ftrcode', 'INV')->where('fket', 'ZZ_DUSK_INV_REF')->first();
        $this->assertNotNull($this->inv, 'Faktur uji gagal dibuat. ' . $this->lastFlash());
    }
}
