<?php

use App\Http\Controllers\AccountController;
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
use App\Http\Controllers\WhController;

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

        Route::get('/gudang', action: [WhController::class, 'index'])->name('gudang.index');
        Route::post('/gudang',        [WhController::class, 'store'])->name('gudang.store');
        Route::get('/gudang/create', [WhController::class, 'create'])->name('gudang.create');
        Route::get('/gudang/{fwhid}/edit', [WhController::class, 'edit'])->name('gudang.edit');
        Route::patch('/gudang/{fwhid}', [WhController::class, 'update'])->name('gudang.update');
        Route::delete('/gudang/{fwhid}', [WhController::class, 'destroy'])->name('gudang.destroy');

        Route::get('/groupproduct', action: [GroupproductController::class, 'index'])->name('groupproduct.index');
        Route::post('/groupproduct',        [GroupproductController::class, 'store'])->name('groupproduct.store');
        Route::get('/groupproduct/create', [GroupproductController::class, 'create'])->name('groupproduct.create');
        Route::get('/groupproduct/{fgroupid}/edit', [GroupproductController::class, 'edit'])->name('groupproduct.edit');
        Route::patch('/groupproduct/{fgroupid}', [GroupproductController::class, 'update'])->name('groupproduct.update');
        Route::delete('/groupproduct/{fgroupid}', [GroupproductController::class, 'destroy'])->name('groupproduct.destroy');

        Route::get('/product', action: [ProductController::class, 'index'])->name('product.index');
        Route::post('/product',        [ProductController::class, 'store'])->name('product.store');
        Route::get('/product/create', [ProductController::class, 'create'])->name('product.create');
        Route::get('/product/{fprdid}/edit', [ProductController::class, 'edit'])->name('product.edit');
        Route::patch('/product/{fprdid}', [ProductController::class, 'update'])->name('product.update');
        Route::delete('/product/{fprdid}', [ProductController::class, 'destroy'])->name('product.destroy');

        Route::get('/supplier', action: [SupplierController::class, 'index'])->name('supplier.index');
        Route::post('/supplier',        [SupplierController::class, 'store'])->name('supplier.store');
        Route::get('/supplier/create', [SupplierController::class, 'create'])->name('supplier.create');
        Route::get('/supplier/{fprdid}/edit', [SupplierController::class, 'edit'])->name('supplier.edit');
        Route::patch('/supplier/{fprdid}', [SupplierController::class, 'update'])->name('supplier.update');
        Route::delete('/supplier/{fprdid}', [SupplierController::class, 'destroy'])->name('supplier.destroy');

        Route::get('/subaccount', action: [SubaccountController::class, 'index'])->name('subaccount.index');
        Route::post('/subaccount',        [SubaccountController::class, 'store'])->name('subaccount.store');
        Route::get('/subaccount/create', [SubaccountController::class, 'create'])->name('subaccount.create');
        Route::get('/subaccount/{fsubaccountid}/edit', [SubaccountController::class, 'edit'])->name('subaccount.edit');
        Route::patch('/subaccount/{fsubaccountid}', [SubaccountController::class, 'update'])->name('subaccount.update');
        Route::delete('/subaccount/{fsubaccountid}', [SubaccountController::class, 'destroy'])->name('subaccount.destroy');

        Route::get('/rekening', action: [RekeningController::class, 'index'])->name('rekening.index');
        Route::post('/rekening',        [RekeningController::class, 'store'])->name('rekening.store');
        Route::get('/rekening/create', [RekeningController::class, 'create'])->name('rekening.create');
        Route::get('/rekening/{frekeningid}/edit', [RekeningController::class, 'edit'])->name('rekening.edit');
        Route::patch('/rekening/{frekeningid}', [RekeningController::class, 'update'])->name('rekening.update');
        Route::delete('/rekening/{frekeningid}', [RekeningController::class, 'destroy'])->name('rekening.destroy');

        Route::get('/account', action: [AccountController::class, 'index'])->name('account.index');
        Route::post('/account',        [AccountController::class, 'store'])->name('account.store');
        Route::get('/account/create', [AccountController::class, 'create'])->name('account.create');
        Route::get('/account/{faccid}/edit', [AccountController::class, 'edit'])->name('account.edit');
        Route::patch('/account/{faccid}', [AccountController::class, 'update'])->name('account.update');
        Route::delete('/account/{faccid}', [AccountController::class, 'destroy'])->name('account.destroy');

        Route::get('/tr_prh', action: [Tr_prhController::class, 'index'])->name('tr_prh.index');
        Route::post('/tr_prh',        [Tr_prhController::class, 'store'])->name('tr_prh.store');
        Route::get('/tr_prh/create', [Tr_prhController::class, 'create'])->name('tr_prh.create');
        Route::get('/tr_prh/{fprid}/edit', [Tr_prhController::class, 'edit'])->name('tr_prh.edit');
        Route::patch('/tr_prh/{fprid}', [Tr_prhController::class, 'update'])->name('tr_prh.update');
        Route::delete('/tr_prh/{fprid}', [Tr_prhController::class, 'destroy'])->name('tr_prh.destroy');
        Route::get('/tr_prh/{fprno}/print', [Tr_prhController::class, 'print'])
            ->name('tr_prh.print');

        Route::get('/tr_poh', action: [Tr_pohController::class, 'index'])->name('tr_poh.index');
        Route::post('/tr_poh',        [Tr_pohController::class, 'store'])->name('tr_poh.store');
        Route::get('/tr_poh/create', [Tr_pohController::class, 'create'])->name('tr_poh.create');
        Route::get('/tr_poh/{fpohdid}/edit', [Tr_pohController::class, 'edit'])->name('tr_poh.edit');
        Route::patch('/tr_poh/{fpohdid}', [Tr_pohController::class, 'update'])->name('tr_poh.update');
        Route::delete('/tr_poh/{fpohdid}', [Tr_pohController::class, 'destroy'])->name('tr_poh.destroy');
        Route::get('/tr_poh/{fpono}/print', [Tr_pohController::class, 'print'])
            ->name('tr_poh.print');

        Route::get('/tr-poh/pickable', [Tr_pohController::class, 'pickable'])
            ->name('tr_poh.pickable'); // sumber data modal

        Route::get('/tr-poh/{id}/items', [Tr_pohController::class, 'items'])
            ->name('tr_poh.items');    // endpoint ambil header+items PR

        Route::get('/products/browse', [\App\Http\Controllers\ProductBrowseController::class, 'index'])
            ->name('products.browse');

        Route::get('/groupproducts/browse', [GroupproductController::class, 'browse'])
            ->name('groupproducts.browse');

        Route::get('/mereks/browse', [MerekController::class, 'browse'])->name('mereks.browse');

        Route::get('/suppliers/browse', [SupplierController::class, 'browse'])->name('suppliers.browse');

        Route::get('/product-name-suggest', [ProductController::class, 'suggestNames'])
            ->name('product.name.suggest');

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
