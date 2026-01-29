<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdjstockController;
use App\Http\Controllers\Tr_pohController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\GroupcustomerController;
use App\Http\Controllers\GroupproductController;
use App\Http\Controllers\MerekController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RekeningController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\RoleAccessController;
use App\Http\Controllers\WilayahController;
use App\Http\Controllers\SalesmanController;
use App\Http\Controllers\SatuanController;
use App\Http\Controllers\SubaccountController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SysUserController;
use App\Http\Controllers\Tr_prhController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AssemblingController;
use App\Http\Controllers\WhController;
use App\Http\Controllers\PenerimaanBarangController;
use App\Http\Controllers\FakturpembelianController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MutasiController;
use App\Http\Controllers\PemakaianbarangController;
use App\Http\Controllers\ReportingController;
use App\Http\Controllers\ReportingFakturPembelianController;
use App\Http\Controllers\ReportingPrController;
use App\Http\Controllers\ReportingPenerimaanBarangController;
use App\Http\Controllers\ReturPembelianController;
use App\Http\Controllers\ReportingAdjStockController;
use App\Http\Controllers\ReportingAssemblingController;
use App\Http\Controllers\ReportingPemakaianBarangController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\SuratJalanController;

Route::get('/', function () {
    return view('welcome');
});

// Semua route di bawah hanya untuk user yang sudah login
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Settings page
    Route::get('/settings', function () {
        return view('settings');
    })->name('settings');

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Master Menu Routes
    Route::prefix('master')->group(function () {

        Route::get('/customer', [CustomerController::class, 'index'])->name('customer.index');
        Route::post('/customer',        [CustomerController::class, 'store'])->name('customer.store');
        Route::get('/customer/create', [CustomerController::class, 'create'])->name('customer.create');
        Route::get('/customer/{fcustomerid}/edit', [CustomerController::class, 'edit'])->name('customer.edit');
        Route::get('/customer/{fcustomerid}/delete', [CustomerController::class, 'delete'])->name('customer.delete');
        Route::patch('/customer/{fcustomerid}', [CustomerController::class, 'update'])->name('customer.update');
        Route::delete('/customer/{fcustomerid}', [CustomerController::class, 'destroy'])->name('customer.destroy');

        Route::get('/groupcustomer', [GroupcustomerController::class, 'index'])->name('groupcustomer.index');
        Route::post('/groupcustomer',        [GroupcustomerController::class, 'store'])->name('groupcustomer.store');
        Route::get('/groupcustomer/create', [GroupcustomerController::class, 'create'])->name('groupcustomer.create');
        Route::get('/groupcustomer/{fgroupid}/edit', [GroupcustomerController::class, 'edit'])->name('groupcustomer.edit');
        Route::get('/groupcustomer/{fgroupid}/delete', [GroupcustomerController::class, 'delete'])->name('groupcustomer.delete');
        Route::patch('/groupcustomer/{fgroupid}', [GroupcustomerController::class, 'update'])->name('groupcustomer.update');
        Route::delete('/groupcustomer/{fgroupid}', [GroupcustomerController::class, 'destroy'])->name('groupcustomer.destroy');

        // SysUser Routes
        Route::get('/sysuser', [SysUserController::class, 'index'])->name('sysuser.index');
        Route::post('/sysuser',        [SysUserController::class, 'store'])->name('sysuser.store');
        Route::get('/sysuser/create', [SysUserController::class, 'create'])->name('sysuser.create');
        Route::get('/sysuser/{fuid}/edit', [SysUserController::class, 'edit'])->name('sysuser.edit');
        Route::patch('/sysuser/{fuid}', [SysUserController::class, 'update'])->name('sysuser.update');
        Route::delete('/sysuser/{fuid}', [SysUserController::class, 'destroy'])->name('sysuser.destroy');

        Route::get('/wilayah', [WilayahController::class, 'index'])->name('wilayah.index');
        Route::post('/wilayah',        [WilayahController::class, 'store'])->name('wilayah.store');
        Route::get('/wilayah/create', [WilayahController::class, 'create'])->name('wilayah.create');
        Route::get('/wilayah/{fwilayahid}/edit', [WilayahController::class, 'edit'])->name('wilayah.edit');
        Route::get('/wilayah/{fwilayahid}/delete', [WilayahController::class, 'delete'])->name('wilayah.delete');
        Route::patch('/wilayah/{fwilayahid}', [WilayahController::class, 'update'])->name('wilayah.update');
        Route::delete('/wilayah/{fwilayahid}', [WilayahController::class, 'destroy'])->name('wilayah.destroy');

        Route::get('/salesman', [SalesmanController::class, 'index'])->name('salesman.index');
        Route::post('/salesman',        [SalesmanController::class, 'store'])->name('salesman.store');
        Route::get('/salesman/create', [SalesmanController::class, 'create'])->name('salesman.create');
        Route::get('/salesman/{fsalesmanid}/edit', [SalesmanController::class, 'edit'])->name('salesman.edit');
        Route::get('/salesman/{fsalesmanid}/delete', [SalesmanController::class, 'delete'])->name('salesman.delete');
        Route::patch('/salesman/{fsalesmanid}', [SalesmanController::class, 'update'])->name('salesman.update');
        Route::delete('/salesman/{fsalesmanid}', [SalesmanController::class, 'destroy'])->name('salesman.destroy');

        Route::get('/satuan', [SatuanController::class, 'index'])->name('satuan.index');
        Route::post('/satuan',        [SatuanController::class, 'store'])->name('satuan.store');
        Route::get('/satuan/create', [SatuanController::class, 'create'])->name('satuan.create');
        Route::get('/satuan/{fsatuanid}/edit', [SatuanController::class, 'edit'])->name('satuan.edit');
        Route::get('/satuan/{fsatuanid}/delete', [SatuanController::class, 'delete'])->name('satuan.delete');
        Route::patch('/satuan/{fsatuanid}', [SatuanController::class, 'update'])->name('satuan.update');
        Route::delete('/satuan/{fsatuanid}', [SatuanController::class, 'destroy'])->name('satuan.destroy');

        Route::get('/merek', [MerekController::class, 'index'])->name('merek.index');
        Route::post('/merek',        [MerekController::class, 'store'])->name('merek.store');
        Route::get('/merek/create', [MerekController::class, 'create'])->name('merek.create');
        Route::get('/merek/{fmerekid}/edit', [MerekController::class, 'edit'])->name('merek.edit');
        Route::get('/merek/{fmerekid}/delete', [MerekController::class, 'delete'])->name('merek.delete');
        Route::patch('/merek/{fmerekid}', [MerekController::class, 'update'])->name('merek.update');
        Route::delete('/merek/{fmerekid}', [MerekController::class, 'destroy'])->name('merek.destroy');

        Route::get('/gudang', [WhController::class, 'index'])->name('gudang.index');
        Route::post('/gudang',        [WhController::class, 'store'])->name('gudang.store');
        Route::get('/gudang/create', [WhController::class, 'create'])->name('gudang.create');
        Route::get('/gudang/{fwhid}/edit', [WhController::class, 'edit'])->name('gudang.edit');
        Route::get('/gudang/{fwhid}/delete', [WhController::class, 'delete'])->name('gudang.delete');
        Route::patch('/gudang/{fwhid}', [WhController::class, 'update'])->name('gudang.update');
        Route::delete('/gudang/{fwhid}', [WhController::class, 'destroy'])->name('gudang.destroy');

        Route::get('/groupproduct', [GroupproductController::class, 'index'])->name('groupproduct.index');
        Route::post('/groupproduct',        [GroupproductController::class, 'store'])->name('groupproduct.store');
        Route::get('/groupproduct/create', [GroupproductController::class, 'create'])->name('groupproduct.create');
        Route::get('/groupproduct/{fgroupid}/edit', [GroupproductController::class, 'edit'])->name('groupproduct.edit');
        Route::get('/groupproduct/{fgroupid}/delete', [GroupproductController::class, 'delete'])->name('groupproduct.delete');
        Route::patch('/groupproduct/{fgroupid}', [GroupproductController::class, 'update'])->name('groupproduct.update');
        Route::delete('/groupproduct/{fgroupid}', [GroupproductController::class, 'destroy'])->name('groupproduct.destroy');

        Route::get('/product', [ProductController::class, 'index'])->name('product.index');
        Route::post('/product',        [ProductController::class, 'store'])->name('product.store');
        Route::get('/product/create', [ProductController::class, 'create'])->name('product.create');
        Route::get('/product/{fprdid}/edit', [ProductController::class, 'edit'])->name('product.edit');
        Route::get('/product/{fprdid}/delete', [ProductController::class, 'delete'])->name('product.delete');
        Route::patch('/product/{fprdid}', [ProductController::class, 'update'])->name('product.update');
        Route::delete('/product/{fprdid}', [ProductController::class, 'destroy'])->name('product.destroy');

        Route::get('/supplier', [SupplierController::class, 'index'])->name('supplier.index');
        Route::post('/supplier',        [SupplierController::class, 'store'])->name('supplier.store');
        Route::get('/supplier/create', [SupplierController::class, 'create'])->name('supplier.create');
        Route::get('/supplier/{fsupplierid}/edit', [SupplierController::class, 'edit'])->name('supplier.edit');
        Route::get('/supplier/{fsupplierid}/delete', [SupplierController::class, 'delete'])->name('supplier.delete');
        Route::patch('/supplier/{fsupplierid}', [SupplierController::class, 'update'])->name('supplier.update');
        Route::delete('/supplier/{fsupplierid}', [SupplierController::class, 'destroy'])->name('supplier.destroy');

        Route::get('/subaccount', [SubaccountController::class, 'index'])->name('subaccount.index');
        Route::post('/subaccount',        [SubaccountController::class, 'store'])->name('subaccount.store');
        Route::get('/subaccount/create', [SubaccountController::class, 'create'])->name('subaccount.create');
        Route::get('/subaccount/{fsubaccountid}/edit', [SubaccountController::class, 'edit'])->name('subaccount.edit');
        Route::get('/subaccount/{fsubaccountid}/delete', [SubaccountController::class, 'delete'])->name('subaccount.delete');
        Route::patch('/subaccount/{fsubaccountid}', [SubaccountController::class, 'update'])->name('subaccount.update');
        Route::delete('/subaccount/{fsubaccountid}', [SubaccountController::class, 'destroy'])->name('subaccount.destroy');

        Route::get('/rekening', [RekeningController::class, 'index'])->name('rekening.index');
        Route::post('/rekening',        [RekeningController::class, 'store'])->name('rekening.store');
        Route::get('/rekening/create', [RekeningController::class, 'create'])->name('rekening.create');
        Route::get('/rekening/{frekeningid}/edit', [RekeningController::class, 'edit'])->name('rekening.edit');
        Route::get('/rekening/{frekeningid}/delete', [RekeningController::class, 'delete'])->name('rekening.delete');
        Route::patch('/rekening/{frekeningid}', [RekeningController::class, 'update'])->name('rekening.update');
        Route::delete('/rekening/{frekeningid}', [RekeningController::class, 'destroy'])->name('rekening.destroy');

        Route::get('/currency', [CurrencyController::class, 'index'])->name('currency.index');
        Route::post('/currency',        [CurrencyController::class, 'store'])->name('currency.store');
        Route::get('/currency/create', [CurrencyController::class, 'create'])->name('currency.create');
        Route::get('/currency/{frekeningid}/edit', [CurrencyController::class, 'edit'])->name('currency.edit');
        Route::get('/currency/{frekeningid}/delete', [CurrencyController::class, 'delete'])->name('currency.delete');
        Route::patch('/currency/{frekeningid}', [CurrencyController::class, 'update'])->name('currency.update');
        Route::delete('/currency/{frekeningid}', [CurrencyController::class, 'destroy'])->name('currency.destroy');

        Route::get('/account', [AccountController::class, 'index'])->name('account.index');
        Route::post('/account',        [AccountController::class, 'store'])->name('account.store');
        Route::get('/account/create', [AccountController::class, 'create'])->name('account.create');
        Route::get('/account/{faccid}/edit', [AccountController::class, 'edit'])->name('account.edit');
        Route::get('/account/{faccid}/delete', [AccountController::class, 'delete'])->name('account.delete');
        Route::patch('/account/{faccid}', [AccountController::class, 'update'])->name('account.update');
        Route::delete('/account/{faccid}', [AccountController::class, 'destroy'])->name('account.destroy');

        Route::get('/tr_prh', [Tr_prhController::class, 'index'])->name('tr_prh.index');
        Route::post('/tr_prh',        [Tr_prhController::class, 'store'])->name('tr_prh.store');
        Route::get('/tr_prh/create', [Tr_prhController::class, 'create'])->name('tr_prh.create');
        Route::get('/tr_prh/{fprid}/view', [Tr_prhController::class, 'view'])->name('tr_prh.view');
        Route::get('/tr_prh/{fprid}/edit', [Tr_prhController::class, 'edit'])->name('tr_prh.edit');
        Route::get('/tr_prh/{fprid}/delete', [Tr_prhController::class, 'delete'])->name('tr_prh.delete');
        Route::patch('/tr_prh/{fprid}', [Tr_prhController::class, 'update'])->name('tr_prh.update');
        Route::delete('/tr_prh/{fprid}', [Tr_prhController::class, 'destroy'])->name('tr_prh.destroy');
        Route::get('/tr_prh/{fprno}/print', [Tr_prhController::class, 'print'])
            ->name('tr_prh.print');

        Route::get('/tr_poh',  [Tr_pohController::class, 'index'])->name('tr_poh.index');
        Route::post('/tr_poh',        [Tr_pohController::class, 'store'])->name('tr_poh.store');
        Route::get('/tr_poh/create', [Tr_pohController::class, 'create'])->name('tr_poh.create');
        Route::get('/tr_poh/{fprid}/view', [Tr_pohController::class, 'view'])->name('tr_poh.view');
        Route::get('/tr_poh/{fpohdid}/edit', [Tr_pohController::class, 'edit'])->name('tr_poh.edit');
        Route::get('/tr_poh/{fpohdid}/delete', [Tr_pohController::class, 'delete'])->name('tr_poh.delete');
        Route::patch('/tr_poh/{fpohdid}', [Tr_pohController::class, 'update'])->name('tr_poh.update');
        Route::delete('/tr_poh/{fpohdid}', [Tr_pohController::class, 'destroy'])->name('tr_poh.destroy');
        Route::get('/tr_poh/{fpono}/print', [Tr_pohController::class, 'print'])
            ->where('fpono', '.*')
            ->name('tr_poh.print');
        Route::get('/tr-poh/pickable', [Tr_pohController::class, 'pickable'])
            ->name('tr_poh.pickable');
        Route::get('/tr-poh/{id}/items', [Tr_pohController::class, 'items'])
            ->name('tr_poh.items');

        Route::get('/penerimaanbarang',  [PenerimaanBarangController::class, 'index'])->name('penerimaanbarang.index');
        Route::post('/penerimaanbarang',        [PenerimaanBarangController::class, 'store'])->name('penerimaanbarang.store');
        Route::get('/penerimaanbarang/create', [PenerimaanBarangController::class, 'create'])->name('penerimaanbarang.create');
        Route::get('/penerimaanbarang/{fstockmtid}/view', [PenerimaanBarangController::class, 'view'])->name('penerimaanbarang.view');
        Route::get('/penerimaanbarang/{fstockmtid}/edit', [PenerimaanBarangController::class, 'edit'])->name('penerimaanbarang.edit');
        Route::get('/penerimaanbarang/{fstockmtid}/delete', [PenerimaanBarangController::class, 'delete'])->name('penerimaanbarang.delete');
        Route::patch('/penerimaanbarang/{fstockmtid}', [PenerimaanBarangController::class, 'update'])->name('penerimaanbarang.update');
        Route::delete('/penerimaanbarang/{fstockmtid}', [PenerimaanBarangController::class, 'destroy'])->name('penerimaanbarang.destroy');
        Route::get('/penerimaanbarang/{fstockmtno}/print', [PenerimaanBarangController::class, 'print'])
            ->name('penerimaanbarang.print');
        Route::get('/penerimaan-barang/pickable', [PenerimaanBarangController::class, 'pickable'])->name('penerimaanbarang.pickable');
        Route::get('/penerimaan-barang/{id}/items', [PenerimaanBarangController::class, 'items'])->name('penerimaanbarang.items');

        Route::get('/fakturpembelian',  [FakturpembelianController::class, 'index'])->name('fakturpembelian.index');
        Route::post('/fakturpembelian',        [FakturpembelianController::class, 'store'])->name('fakturpembelian.store');
        Route::get('/fakturpembelian/create', [FakturpembelianController::class, 'create'])->name('fakturpembelian.create');
        Route::get('/fakturpembelian/{fstockmtid}/view', [FakturpembelianController::class, 'view'])->name('fakturpembelian.view');
        Route::get('/fakturpembelian/{fstockmtid}/edit', [FakturpembelianController::class, 'edit'])->name('fakturpembelian.edit');
        Route::get('/fakturpembelian/{fstockmtid}/delete', [FakturpembelianController::class, 'delete'])->name('fakturpembelian.delete');
        Route::patch('/fakturpembelian/{fstockmtid}', [FakturpembelianController::class, 'update'])->name('fakturpembelian.update');
        Route::delete('/fakturpembelian/{fstockmtid}', [FakturpembelianController::class, 'destroy'])->name('fakturpembelian.destroy');
        Route::get('/fakturpembelian/{fstockmtno}/print', [FakturpembelianController::class, 'print'])
            ->name('fakturpembelian.print');
        Route::get('/fakturpembelian/{id}/items', [FakturpembelianController::class, 'items'])
            ->name('fakturpembelian.items');
        Route::get('/fakturpembelian/pickable', [FakturpembelianController::class, 'pickable'])
            ->name('fakturpembelian.pickable');

        Route::get('/adjstock',  [AdjstockController::class, 'index'])->name('adjstock.index');
        Route::post('/adjstock',        [AdjstockController::class, 'store'])->name('adjstock.store');
        Route::get('/adjstock/create', [AdjstockController::class, 'create'])->name('adjstock.create');
        Route::get('/adjstock/{fstockmtid}/view', [AdjstockController::class, 'view'])->name('adjstock.view');
        Route::get('/adjstock/{fstockmtid}/edit', [AdjstockController::class, 'edit'])->name('adjstock.edit');
        Route::get('/adjstock/{fstockmtid}/delete', [AdjstockController::class, 'delete'])->name('adjstock.delete');
        Route::patch('/adjstock/{fstockmtid}', [AdjstockController::class, 'update'])->name('adjstock.update');
        Route::delete('/adjstock/{fstockmtid}', [AdjstockController::class, 'destroy'])->name('adjstock.destroy');
        Route::get('/adjstock/{fstockmtno}/print', [AdjstockController::class, 'print'])
            ->name('adjstock.print');
        Route::get('/adjstock/{id}/items', [AdjstockController::class, 'items'])
            ->name('adjstock.items');
        Route::get('/adjstock/pickable', [AdjstockController::class, 'pickable'])
            ->name('adjstock.pickable');

        Route::get('/mutasi',  [MutasiController::class, 'index'])->name('mutasi.index');
        Route::post('/mutasi',        [MutasiController::class, 'store'])->name('mutasi.store');
        Route::get('/mutasi/create', [MutasiController::class, 'create'])->name('mutasi.create');
        Route::get('/mutasi/{fstockmtid}/view', [MutasiController::class, 'view'])->name('mutasi.view');
        Route::get('/mutasi/{fstockmtid}/edit', [MutasiController::class, 'edit'])->name('mutasi.edit');
        Route::get('/mutasi/{fstockmtid}/delete', [MutasiController::class, 'delete'])->name('mutasi.delete');
        Route::patch('/mutasi/{fstockmtid}', [MutasiController::class, 'update'])->name('mutasi.update');
        Route::delete('/mutasi/{fstockmtid}', [MutasiController::class, 'destroy'])->name('mutasi.destroy');
        Route::get('/mutasi/{fstockmtno}/print', [MutasiController::class, 'print'])
            ->name('mutasi.print');
        Route::get('/mutasi/{id}/items', [MutasiController::class, 'items'])
            ->name('mutasi.items');
        Route::get('/mutasi/pickable', [MutasiController::class, 'pickable'])
            ->name('mutasi.pickable');

        Route::get('/pemakaianbarang',  [PemakaianbarangController::class, 'index'])->name('pemakaianbarang.index');
        Route::post('/pemakaianbarang',        [PemakaianbarangController::class, 'store'])->name('pemakaianbarang.store');
        Route::get('/pemakaianbarang/create', [PemakaianbarangController::class, 'create'])->name('pemakaianbarang.create');
        Route::get('/pemakaianbarang/{fstockmtid}/view', [PemakaianbarangController::class, 'view'])->name('pemakaianbarang.view');
        Route::get('/pemakaianbarang/{fstockmtid}/edit', [PemakaianbarangController::class, 'edit'])->name('pemakaianbarang.edit');
        Route::get('/pemakaianbarang/{fstockmtid}/delete', [PemakaianbarangController::class, 'delete'])->name('pemakaianbarang.delete');
        Route::patch('/pemakaianbarang/{fstockmtid}', [PemakaianbarangController::class, 'update'])->name('pemakaianbarang.update');
        Route::delete('/pemakaianbarang/{fstockmtid}', [PemakaianbarangController::class, 'destroy'])->name('pemakaianbarang.destroy');
        Route::get('/pemakaianbarang/{fstockmtno}/print', [PemakaianbarangController::class, 'print'])
            ->name('pemakaianbarang.print');
        Route::get('/pemakaianbarang/{id}/items', [PemakaianbarangController::class, 'items'])
            ->name('pemakaianbarang.items');
        Route::get('/pemakaianbarang/pickable', [PemakaianbarangController::class, 'pickable'])
            ->name('pemakaianbarang.pickable');

        Route::get('/returpembelian',  [ReturPembelianController::class, 'index'])->name('returpembelian.index');
        Route::post('/returpembelian',        [ReturPembelianController::class, 'store'])->name('returpembelian.store');
        Route::get('/returpembelian/create', [ReturPembelianController::class, 'create'])->name('returpembelian.create');
        Route::get('/returpembelian/{fstockmtid}/view', [ReturPembelianController::class, 'view'])->name('returpembelian.view');
        Route::get('/returpembelian/{fstockmtid}/edit', [ReturPembelianController::class, 'edit'])->name('returpembelian.edit');
        Route::get('/returpembelian/{fstockmtid}/delete', [ReturPembelianController::class, 'delete'])->name('returpembelian.delete');
        Route::patch('/returpembelian/{fstockmtid}', [ReturPembelianController::class, 'update'])->name('returpembelian.update');
        Route::delete('/returpembelian/{fstockmtid}', [ReturPembelianController::class, 'destroy'])->name('returpembelian.destroy');
        Route::get('/returpembelian/{fstockmtno}/print', [ReturPembelianController::class, 'print'])
            ->name('returpembelian.print');
        Route::get('/returpembelian/{id}/items', [ReturPembelianController::class, 'items'])
            ->name('returpembelian.items');
        Route::get('/returpembelian/pickable', [ReturPembelianController::class, 'pickable'])
            ->name('returpembelian.pickable');

        Route::get('/assembling',  [AssemblingController::class, 'index'])->name('assembling.index');
        Route::post('/assembling',        [AssemblingController::class, 'store'])->name('assembling.store');
        Route::get('/assembling/create', [AssemblingController::class, 'create'])->name('assembling.create');
        Route::get('/assembling/{fstockmtid}/view', [AssemblingController::class, 'view'])->name('assembling.view');
        Route::get('/assembling/{fstockmtid}/edit', [AssemblingController::class, 'edit'])->name('assembling.edit');
        Route::get('/assembling/{fstockmtid}/delete', [AssemblingController::class, 'delete'])->name('assembling.delete');
        Route::patch('/assembling/{fstockmtid}', [AssemblingController::class, 'update'])->name('assembling.update');
        Route::delete('/assembling/{fstockmtid}', [AssemblingController::class, 'destroy'])->name('assembling.destroy');
        Route::get('/assembling/{fstockmtno}/print', [AssemblingController::class, 'print'])
            ->name('assembling.print');
        Route::get('/assembling/{id}/items', [AssemblingController::class, 'items'])
            ->name('assembling.items');
        Route::get('/assembling/pickable', [AssemblingController::class, 'pickable'])
            ->name('assembling.pickable');

        Route::get('/salesorder',  [SalesOrderController::class, 'index'])->name('salesorder.index');
        Route::post('/salesorder',        [SalesOrderController::class, 'store'])->name('salesorder.store');
        Route::get('/salesorder/create', [SalesOrderController::class, 'create'])->name('salesorder.create');
        Route::get('/salesorder/{ftrsomtid}/view', [SalesOrderController::class, 'view'])->name('salesorder.view');
        Route::get('/salesorder/{ftrsomtid}/edit', [SalesOrderController::class, 'edit'])->name('salesorder.edit');
        Route::get('/salesorder/{ftrsomtid}/delete', [SalesOrderController::class, 'delete'])->name('salesorder.delete');
        Route::patch('/salesorder/{ftrsomtid}', [SalesOrderController::class, 'update'])->name('salesorder.update');
        Route::delete('/salesorder/{ftrsomtid}', [SalesOrderController::class, 'destroy'])->name('salesorder.destroy');
        Route::get('/salesorder/{fstockmtno}/print', [SalesOrderController::class, 'print'])
            ->name('salesorder.print');
        Route::get('/salesorder/{id}/items', [SalesOrderController::class, 'items'])
            ->name('salesorder.items');
        Route::get('/salesorder/pickable', [SalesOrderController::class, 'pickable'])
            ->name('salesorder.pickable');

        Route::get('/suratjalan',  [SuratJalanController::class, 'index'])->name('suratjalan.index');
        Route::post('/suratjalan',        [SuratJalanController::class, 'store'])->name('suratjalan.store');
        Route::get('/suratjalan/create', [SuratJalanController::class, 'create'])->name('suratjalan.create');
        Route::get('/suratjalan/{ftrsomtid}/view', [SuratJalanController::class, 'view'])->name('suratjalan.view');
        Route::get('/suratjalan/{ftrsomtid}/edit', [SuratJalanController::class, 'edit'])->name('suratjalan.edit');
        Route::get('/suratjalan/{ftrsomtid}/delete', [SuratJalanController::class, 'delete'])->name('suratjalan.delete');
        Route::patch('/suratjalan/{ftrsomtid}', [SuratJalanController::class, 'update'])->name('suratjalan.update');
        Route::delete('/suratjalan/{ftrsomtid}', [SuratJalanController::class, 'destroy'])->name('suratjalan.destroy');
        Route::get('/suratjalan/{fstockmtno}/print', [SuratJalanController::class, 'print'])
            ->name('suratjalan.print');
        Route::get('/suratjalan/{id}/items', [SuratJalanController::class, 'items'])
            ->name('suratjalan.items');
        Route::get('/suratjalan/pickable', [SuratJalanController::class, 'pickable'])
            ->name('suratjalan.pickable');

        Route::get('/invoice',  [InvoiceController::class, 'index'])->name('invoice.index');
        Route::post('/invoice',        [InvoiceController::class, 'store'])->name('invoice.store');
        Route::get('/invoice/create', [InvoiceController::class, 'create'])->name('invoice.create');
        Route::get('/invoice/{ftranmtid}/view', [InvoiceController::class, 'view'])->name('invoice.view');
        Route::get('/invoice/{ftranmtid}/edit', [InvoiceController::class, 'edit'])->name('invoice.edit');
        Route::get('/invoice/{ftranmtid}/delete', [InvoiceController::class, 'delete'])->name('invoice.delete');
        Route::patch('/invoice/{ftranmtid}', [InvoiceController::class, 'update'])->name('invoice.update');
        Route::delete('/invoice/{ftranmtid}', [InvoiceController::class, 'destroy'])->name('invoice.destroy');
        Route::get('/invoice/{fstockmtno}/print', [InvoiceController::class, 'print'])
            ->name('invoice.print');
        Route::get('/invoice/{id}/items', [InvoiceController::class, 'items'])
            ->name('invoice.items');
        Route::get('/invoice/pickable', [InvoiceController::class, 'pickable'])
            ->name('invoice.pickable');

        Route::get('/products/browse', [\App\Http\Controllers\ProductBrowseController::class, 'index'])
            ->name('products.browse');

        Route::get('/reporting',  [ReportingController::class, 'index'])->name('reporting.index');
        Route::get('/reporting/exportExcel', [ReportingController::class, 'exportExcel'])->name('reporting.exportExcel');
        Route::get('/reporting/print', [ReportingController::class, 'printPoh'])->name('reporting.printPoh');

        Route::get('/reportingpr',  [ReportingPrController::class, 'index'])->name('reportingpr.index');
        Route::get('/reportingpr/exportExcel', [ReportingPrController::class, 'exportExcel'])->name('reportingpr.exportExcel');
        Route::get('/reportingpr/print', [ReportingPrController::class, 'printPrh'])->name('reportingpr.printPrh');

        Route::get('/reportingpenerimaanbarang',  [ReportingPenerimaanBarangController::class, 'index'])->name('reportingpenerimaanbarang.index');
        Route::get('/reportingpenerimaanbarang/exportExcel', [ReportingPenerimaanBarangController::class, 'exportExcel'])->name('reportingpenerimaanbarang.exportExcel');
        Route::get('/reportingpenerimaanbarang/print', [ReportingPenerimaanBarangController::class, 'printPenerimaanBarang'])->name('reportingpenerimaanbarang.printPenerimaanBarang');

        Route::get('/reportingfakturpembelian',  [ReportingFakturPembelianController::class, 'index'])->name('reportingfakturpembelian.index');
        Route::get('/reportingfakturpembelian/exportExcel', [ReportingFakturPembelianController::class, 'exportExcel'])->name('reportingfakturpembelian.exportExcel');
        Route::get('/reportingfakturpembelian/print', [ReportingFakturPembelianController::class, 'printFakturPembelian'])->name('reportingfakturpembelian.printFakturPembelian');

        Route::get('/reportingadjstock',  [ReportingAdjStockController::class, 'index'])->name('reportingadjstock.index');
        Route::get('/reportingadjstock/exportExcel', [ReportingAdjStockController::class, 'exportExcel'])->name('reportingadjstock.exportExcel');
        Route::get('/reportingadjstock/print', [ReportingAdjStockController::class, 'printAdjStock'])->name('reportingadjstock.printAdjStock');

        Route::get('/reportingpemakaianbarang',  [ReportingPemakaianBarangController::class, 'index'])->name('reportingpemakaianbarang.index');
        Route::get('/reportingpemakaianbarang/exportExcel', [ReportingPemakaianBarangController::class, 'exportExcel'])->name('reportingpemakaianbarang.exportExcel');
        Route::get('/reportingpemakaianbarang/print', [ReportingPemakaianBarangController::class, 'printPemakaianBarang'])->name('reportingpemakaianbarang.printPemakaianBarang');

        Route::get('/reportingassembling',  [ReportingAssemblingController::class, 'index'])->name('reportingassembling.index');
        Route::get('/reportingassembling/exportExcel', [ReportingAssemblingController::class, 'exportExcel'])->name('reportingassembling.exportExcel');
        Route::get('/reportingassembling/print', [ReportingAssemblingController::class, 'printAssembling'])->name('reportingassembling.printAssembling');

        Route::get('/gudang/browse', [WhController::class, 'browse'])
            ->name('gudang.browse');

        Route::get('/account/browse', [AccountController::class, 'browse'])
            ->name('account.browse');

        Route::get('/suppliers/browse', [SupplierController::class, 'browse'])->name('suppliers.browse');
        Route::get('/customer/browse', [CustomerController::class, 'browse'])->name('customer.browse');
        Route::get('/salesman/browse', [SalesmanController::class, 'browse'])->name('salesman.browse');
        Route::get('/product/browse', [ProductController::class, 'browse'])->name('product.browse');
        Route::get('/wh/browse', [WhController::class, 'browse'])->name('wh.browse');
        Route::get('/account/browse', [AccountController::class, 'browse'])->name('account.browse');
        Route::get('/group/browse', [GroupproductController::class, 'browse'])->name('group.browse');
        Route::get('/merek/browse', [MerekController::class, 'browse'])->name('merek.browse');

        Route::get('/product-name-suggest', [ProductController::class, 'suggestNames'])
            ->name('product.name.suggest');
        Route::get('/product/suggest-codes', [ProductController::class, 'suggestCodes'])->name('product.suggest-codes');

        Route::get('/account/suggest', [AccountController::class, 'suggestAccounts'])->name('account.suggest');

        Route::get('/customer-name-suggest', [CustomerController::class, 'suggestNames'])
            ->name('customer.name.suggest');

        Route::get('/accounts/browse', [AccountController::class, 'browse'])
            ->name('accounts.browse');

        Route::get('/roleaccess/{fuid}', [RoleAccessController::class, 'index'])->name('roleaccess.index');
        Route::post('/roleaccess', [RoleAccessController::class, 'store'])->name('roleaccess.store');

        Route::get('/roleaccess/{fuid}/permissions', [RoleAccessController::class, 'getPermissions'])
            ->name('roleaccess.permissions');

        Route::post('/roleaccess/clone', [RoleAccessController::class, 'cloneToUser'])
            ->name('roleaccess.clone');

        Route::get('/customer/create', [CustomerController::class, 'create'])->name('customer.create');
    });
});

Route::get('/approval-page', [ApprovalController::class, 'showApprovalPage'])
    ->name('approval.page');

Route::post('/approve/{fprno}', [ApprovalController::class, 'approveRequest'])
    ->name('approval.submit');

Route::post('/reject/{fprno}', [ApprovalController::class, 'rejectRequest'])
    ->where('fprno', '[A-Za-z0-9\.\-]+')
    ->name('approval.reject');

Route::get('/approval/info/{fprno}', [ApprovalController::class, 'infoApprovalPage'])
    ->name('approval.info');

Route::get('/approval-page-po', [ApprovalController::class, 'showApprovalPagePO'])
    ->name('approval.page.po');

Route::post('/approve-po/{fpono}', [ApprovalController::class, 'approveRequestPO'])
    ->name('approval.po.submit');

Route::post('/reject-po/{fpono}', [ApprovalController::class, 'rejectRequestPO'])
    ->where('fpono', '[A-Za-z0-9\.\-]+')
    ->name('approval.po.reject');

Route::get('/approval-po/info/{fpono}', [ApprovalController::class, 'infoApprovalPagePO'])
    ->name('approval.po.info');

require __DIR__ . '/auth.php';
