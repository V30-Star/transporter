<?php

namespace Tests\Http\Concerns;

use App\Models\Tr_poh;
use Illuminate\Support\Facades\DB;

/**
 * Rantai pembelian uji (semua di dalam transaksi test, di-rollback):
 * supplier + produk + gudang -> PO (10 @ 5000) -> penerimaan barang (6) -> faktur pembelian (6, total 30000).
 */
trait MakesPurchaseChain
{
    use MakesTestMaster;

    protected string $supplier;

    protected string $productCode;

    protected string $unit;

    protected string $gudang;

    protected object $poh;

    protected object $pod;

    protected object $ter;

    protected object $buy;

    protected function setUpPurchaseChain(string $invoiceNo = 'ZZ-INV-CHAIN'): void
    {
        $this->grantPermissions(
            'viewFakturPembelian', 'createFakturPembelian', 'updateFakturPembelian', 'deleteFakturPembelian',
            'viewPenerimaanBarang', 'createPenerimaanBarang', 'updatePenerimaanBarang', 'deletePenerimaanBarang',
            'viewTr_poh', 'createTr_poh', 'updateTr_poh', 'deleteTr_poh',
            'createGudang',
        );

        $range = [now()->startOfDay(), now()->endOfDay()];
        $today = max(
            Tr_poh::whereBetween('fdatetime', $range)->count(),
            DB::table('trstockmt')->whereIn('fstockmtcode', ['TER', 'BUY', 'REB'])->whereBetween('fdatetime', $range)->count(),
        );
        if ($today >= 11) {
            $this->markTestSkipped("Batas harian dokumen hampir tercapai ($today hari ini).");
        }

        $this->supplier = $this->makeSupplier();
        $product = $this->makeProduct();
        $this->productCode = $product->fprdcode;
        $this->unit = trim((string) $product->fsatuankecil);

        $this->atomic(fn () => $this->post(route('gudang.store'), [
            'fwhcode' => 'ZZDUSKW1', 'fwhname' => 'ZZ GUDANG UJI', 'faddress' => 'ZZ', 'fbranchcode' => 'ZZCAB',
        ]));
        $this->gudang = 'ZZDUSKW1';
        $this->assertTrue(DB::table('mswh')->where('fwhcode', $this->gudang)->exists(), 'Gudang uji gagal dibuat. ' . $this->lastFlash());

        $branch = auth('sysuser')->user()->fcabang;

        $this->atomic(fn () => $this->post(route('tr_poh.store'), [
            'fpodate' => now()->format('Y-m-d'), 'fsupplier' => $this->supplier, 'fket' => 'ZZ_DUSK_PO_REF', 'fbranchcode' => $branch,
            'fitemcode' => [$this->productCode], 'fsatuan' => [$this->unit], 'fqty' => [10], 'fprice' => [5000],
            'fdisc' => ['0'], 'fnoacak' => ['123'], 'fdesc' => [''], 'frefdtno' => [''],
        ]));
        $this->poh = DB::table('tr_poh')->where('fket', 'ZZ_DUSK_PO_REF')->first();
        $this->assertNotNull($this->poh, 'PO uji gagal dibuat. ' . $this->lastFlash());
        $this->pod = DB::table('tr_pod')->where('fpono', $this->poh->fpono)->first();

        $this->atomic(fn () => $this->post(route('penerimaanbarang.store'), [
            'fstockmtdate' => now()->format('Y-m-d'), 'fsupplier' => $this->supplier, 'ffrom' => $this->gudang,
            'fket' => 'ZZ_DUSK_TER_REF', 'fbranchcode' => $branch,
            'fitemcode' => [$this->productCode], 'fsatuan' => [$this->unit], 'fpono' => [$this->poh->fpono],
            'frefdtid' => [$this->pod->fpodid], 'fqty' => [6], 'fprice' => [5000], 'fnoacak' => ['123'], 'frefnoacak' => ['123'], 'fdesc' => [''],
        ]));
        $this->ter = DB::table('trstockmt')->where('fket', 'ZZ_DUSK_TER_REF')->first();
        $this->assertNotNull($this->ter, 'Penerimaan barang uji gagal dibuat. ' . $this->lastFlash());
        $terDt = DB::table('trstockdt')->where('fstockmtno', $this->ter->fstockmtno)->first();

        $this->atomic(fn () => $this->post(route('fakturpembelian.store'), [
            'fstockmtdate' => now()->format('Y-m-d'), 'fsupplier' => $this->supplier, 'ffrom' => $this->gudang,
            'frefno' => $invoiceNo, 'fket' => 'ZZ_DUSK_BUY_REF', 'fbranchcode' => $branch, 'ftypebuy' => 0,
            'fitemcode' => [$this->productCode], 'fsatuan' => [$this->unit], 'fsource' => ['PB'],
            'frefdtid' => [$terDt->fstockdtid], 'frefdtno' => [$this->ter->fstockmtno], 'frefnoacak' => ['123'],
            'fqty' => [6], 'fprice' => [5000], 'fdiscpersen' => ['0'], 'fbiaya' => [0], 'fdesc' => [''],
        ]));
        $this->buy = DB::table('trstockmt')->where('fstockmtcode', 'BUY')->where('fket', 'ZZ_DUSK_BUY_REF')->first();
        $this->assertNotNull($this->buy, 'Faktur pembelian uji gagal dibuat. ' . $this->lastFlash());
    }
}
