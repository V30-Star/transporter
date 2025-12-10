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
use App\Http\Controllers\RoleAccessController;
use App\Http\Controllers\WilayahController;
use App\Http\Controllers\RuteController;
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
use App\Http\Controllers\MutasiController;
use App\Http\Controllers\PemakaianbarangController;
use App\Http\Controllers\ReportingController;
use App\Http\Controllers\ReturPembelianController;

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
        Route::patch('/customer/{fcustomerid}', [CustomerController::class, 'update'])->name('customer.update');
        Route::delete('/customer/{fcustomerid}', [CustomerController::class, 'destroy'])->name('customer.destroy');

        Route::get('/groupcustomer', [GroupcustomerController::class, 'index'])->name('groupcustomer.index');
        Route::post('/groupcustomer',        [GroupcustomerController::class, 'store'])->name('groupcustomer.store');
        Route::get('/groupcustomer/create', [GroupcustomerController::class, 'create'])->name('groupcustomer.create');
        Route::get('/groupcustomer/{fgroupid}/edit', [GroupcustomerController::class, 'edit'])->name('groupcustomer.edit');
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
        Route::patch('/wilayah/{fwilayahid}', [WilayahController::class, 'update'])->name('wilayah.update');
        Route::delete('/wilayah/{fwilayahid}', [WilayahController::class, 'destroy'])->name('wilayah.destroy');

        Route::get('/salesman', [SalesmanController::class, 'index'])->name('salesman.index');
        Route::post('/salesman',        [SalesmanController::class, 'store'])->name('salesman.store');
        Route::get('/salesman/create', [SalesmanController::class, 'create'])->name('salesman.create');
        Route::get('/salesman/{fsalesmanid}/edit', [SalesmanController::class, 'edit'])->name('salesman.edit');
        Route::patch('/salesman/{fsalesmanid}', [SalesmanController::class, 'update'])->name('salesman.update');
        Route::delete('/salesman/{fsalesmanid}', [SalesmanController::class, 'destroy'])->name('salesman.destroy');

        Route::get('/satuan', [SatuanController::class, 'index'])->name('satuan.index');
        Route::post('/satuan',        [SatuanController::class, 'store'])->name('satuan.store');
        Route::get('/satuan/create', [SatuanController::class, 'create'])->name('satuan.create');
        Route::get('/satuan/{fsatuanid}/edit', [SatuanController::class, 'edit'])->name('satuan.edit');
        Route::patch('/satuan/{fsatuanid}', [SatuanController::class, 'update'])->name('satuan.update');
        Route::delete('/satuan/{fsatuanid}', [SatuanController::class, 'destroy'])->name('satuan.destroy');

        Route::get('/merek', [MerekController::class, 'index'])->name('merek.index');
        Route::post('/merek',        [MerekController::class, 'store'])->name('merek.store');
        Route::get('/merek/create', [MerekController::class, 'create'])->name('merek.create');
        Route::get('/merek/{fmerekid}/edit', [MerekController::class, 'edit'])->name('merek.edit');
        Route::patch('/merek/{fmerekid}', [MerekController::class, 'update'])->name('merek.update');
        Route::delete('/merek/{fmerekid}', [MerekController::class, 'destroy'])->name('merek.destroy');

        Route::get('/gudang', [WhController::class, 'index'])->name('gudang.index');
        Route::post('/gudang',        [WhController::class, 'store'])->name('gudang.store');
        Route::get('/gudang/create', [WhController::class, 'create'])->name('gudang.create');
        Route::get('/gudang/{fwhid}/edit', [WhController::class, 'edit'])->name('gudang.edit');
        Route::patch('/gudang/{fwhid}', [WhController::class, 'update'])->name('gudang.update');
        Route::delete('/gudang/{fwhid}', [WhController::class, 'destroy'])->name('gudang.destroy');

        Route::get('/groupproduct', [GroupproductController::class, 'index'])->name('groupproduct.index');
        Route::post('/groupproduct',        [GroupproductController::class, 'store'])->name('groupproduct.store');
        Route::get('/groupproduct/create', [GroupproductController::class, 'create'])->name('groupproduct.create');
        Route::get('/groupproduct/{fgroupid}/edit', [GroupproductController::class, 'edit'])->name('groupproduct.edit');
        Route::patch('/groupproduct/{fgroupid}', [GroupproductController::class, 'update'])->name('groupproduct.update');
        Route::delete('/groupproduct/{fgroupid}', [GroupproductController::class, 'destroy'])->name('groupproduct.destroy');

        Route::get('/product', [ProductController::class, 'index'])->name('product.index');
        Route::post('/product',        [ProductController::class, 'store'])->name('product.store');
        Route::get('/product/create', [ProductController::class, 'create'])->name('product.create');
        Route::get('/product/{fprdid}/edit', [ProductController::class, 'edit'])->name('product.edit');
        Route::patch('/product/{fprdid}', [ProductController::class, 'update'])->name('product.update');
        Route::delete('/product/{fprdid}', [ProductController::class, 'destroy'])->name('product.destroy');

        Route::get('/supplier', [SupplierController::class, 'index'])->name('supplier.index');
        Route::post('/supplier',        [SupplierController::class, 'store'])->name('supplier.store');
        Route::get('/supplier/create', [SupplierController::class, 'create'])->name('supplier.create');
        Route::get('/supplier/{fprdid}/edit', [SupplierController::class, 'edit'])->name('supplier.edit');
        Route::patch('/supplier/{fprdid}', [SupplierController::class, 'update'])->name('supplier.update');
        Route::delete('/supplier/{fprdid}', [SupplierController::class, 'destroy'])->name('supplier.destroy');

        Route::get('/subaccount', [SubaccountController::class, 'index'])->name('subaccount.index');
        Route::post('/subaccount',        [SubaccountController::class, 'store'])->name('subaccount.store');
        Route::get('/subaccount/create', [SubaccountController::class, 'create'])->name('subaccount.create');
        Route::get('/subaccount/{fsubaccountid}/edit', [SubaccountController::class, 'edit'])->name('subaccount.edit');
        Route::patch('/subaccount/{fsubaccountid}', [SubaccountController::class, 'update'])->name('subaccount.update');
        Route::delete('/subaccount/{fsubaccountid}', [SubaccountController::class, 'destroy'])->name('subaccount.destroy');

        Route::get('/rekening', [RekeningController::class, 'index'])->name('rekening.index');
        Route::post('/rekening',        [RekeningController::class, 'store'])->name('rekening.store');
        Route::get('/rekening/create', [RekeningController::class, 'create'])->name('rekening.create');
        Route::get('/rekening/{frekeningid}/edit', [RekeningController::class, 'edit'])->name('rekening.edit');
        Route::patch('/rekening/{frekeningid}', [RekeningController::class, 'update'])->name('rekening.update');
        Route::delete('/rekening/{frekeningid}', [RekeningController::class, 'destroy'])->name('rekening.destroy');

        Route::get('/account', [AccountController::class, 'index'])->name('account.index');
        Route::post('/account',        [AccountController::class, 'store'])->name('account.store');
        Route::get('/account/create', [AccountController::class, 'create'])->name('account.create');
        Route::get('/account/{faccid}/edit', [AccountController::class, 'edit'])->name('account.edit');
        Route::patch('/account/{faccid}', [AccountController::class, 'update'])->name('account.update');
        Route::delete('/account/{faccid}', [AccountController::class, 'destroy'])->name('account.destroy');

        Route::get('/tr_prh', [Tr_prhController::class, 'index'])->name('tr_prh.index');
        Route::post('/tr_prh',        [Tr_prhController::class, 'store'])->name('tr_prh.store');
        Route::get('/tr_prh/create', [Tr_prhController::class, 'create'])->name('tr_prh.create');
        Route::get('/tr_prh/{fprid}/edit', [Tr_prhController::class, 'edit'])->name('tr_prh.edit');
        Route::patch('/tr_prh/{fprid}', [Tr_prhController::class, 'update'])->name('tr_prh.update');
        Route::delete('/tr_prh/{fprid}', [Tr_prhController::class, 'destroy'])->name('tr_prh.destroy');
        Route::get('/tr_prh/{fprno}/print', [Tr_prhController::class, 'print'])
            ->name('tr_prh.print');

        Route::get('/tr_poh',  [Tr_pohController::class, 'index'])->name('tr_poh.index');
        Route::post('/tr_poh',        [Tr_pohController::class, 'store'])->name('tr_poh.store');
        Route::get('/tr_poh/create', [Tr_pohController::class, 'create'])->name('tr_poh.create');
        Route::get('/tr_poh/{fpohdid}/edit', [Tr_pohController::class, 'edit'])->name('tr_poh.edit');
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
        Route::get('/penerimaanbarang/{fstockmtid}/edit', [PenerimaanBarangController::class, 'edit'])->name('penerimaanbarang.edit');
        Route::patch('/penerimaanbarang/{fstockmtid}', [PenerimaanBarangController::class, 'update'])->name('penerimaanbarang.update');
        Route::delete('/penerimaanbarang/{fstockmtid}', [PenerimaanBarangController::class, 'destroy'])->name('penerimaanbarang.destroy');
        Route::get('/penerimaanbarang/{fstockmtno}/print', [PenerimaanBarangController::class, 'print'])
            ->name('penerimaanbarang.print');
        Route::get('/penerimaan-barang/pickable', [PenerimaanBarangController::class, 'pickable'])->name('penerimaanbarang.pickable');
        Route::get('/penerimaan-barang/{id}/items', [PenerimaanBarangController::class, 'items'])->name('penerimaanbarang.items');

        Route::get('/fakturpembelian',  [FakturpembelianController::class, 'index'])->name('fakturpembelian.index');
        Route::post('/fakturpembelian',        [FakturpembelianController::class, 'store'])->name('fakturpembelian.store');
        Route::get('/fakturpembelian/create', [FakturpembelianController::class, 'create'])->name('fakturpembelian.create');
        Route::get('/fakturpembelian/{fstockmtid}/edit', [FakturpembelianController::class, 'edit'])->name('fakturpembelian.edit');
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
        Route::get('/adjstock/{fstockmtid}/edit', [AdjstockController::class, 'edit'])->name('adjstock.edit');
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
        Route::get('/mutasi/{fstockmtid}/edit', [MutasiController::class, 'edit'])->name('mutasi.edit');
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
        Route::get('/pemakaianbarang/{fstockmtid}/edit', [PemakaianbarangController::class, 'edit'])->name('pemakaianbarang.edit');
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
        Route::get('/returpembelian/{fstockmtid}/edit', [ReturPembelianController::class, 'edit'])->name('returpembelian.edit');
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
        Route::get('/assembling/{fstockmtid}/edit', [AssemblingController::class, 'edit'])->name('assembling.edit');
        Route::patch('/assembling/{fstockmtid}', [AssemblingController::class, 'update'])->name('assembling.update');
        Route::delete('/assembling/{fstockmtid}', [AssemblingController::class, 'destroy'])->name('assembling.destroy');
        Route::get('/assembling/{fstockmtno}/print', [AssemblingController::class, 'print'])
            ->name('assembling.print');
        Route::get('/assembling/{id}/items', [AssemblingController::class, 'items'])
            ->name('assembling.items');
        Route::get('/assembling/pickable', [AssemblingController::class, 'pickable'])
            ->name('assembling.pickable');

        Route::get('/products/browse', [\App\Http\Controllers\ProductBrowseController::class, 'index'])
            ->name('products.browse');

        Route::get('/reporting',  [ReportingController::class, 'index'])->name('reporting.index');
        Route::get('/reporting/exportExcel', [ReportingController::class, 'exportExcel'])->name('reporting.exportExcel');
        Route::get('/reporting/print', [ReportingController::class, 'printPoh'])->name('reporting.printPoh');

        Route::get('/gudang/browse', [WhController::class, 'browse'])
            ->name('gudang.browse');

        Route::get('/account/browse', [AccountController::class, 'browse'])
            ->name('account.browse');

        Route::get('/suppliers/browse', [SupplierController::class, 'browse'])->name('suppliers.browse');
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
