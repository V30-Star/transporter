# Graph Report - transporter  (2026-07-29)

## Corpus Check
- 535 files · ~757,630 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 2166 nodes · 4929 edges · 464 communities (357 shown, 107 thin omitted)
- Extraction: 96% EXTRACTED · 4% INFERRED · 0% AMBIGUOUS · INFERRED: 202 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `ddf6901f`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Tr_prhController
- Illuminate\Database\Eloquent\Factories\HasFactory
- Sysuser
- InvoiceController
- Customer
- ReturPenjualanController
- Product
- Illuminate\Http\Request
- SalesOrderController
- FakturpembelianController
- SuratJalanController
- PelunasanCustomerController
- BayarSupplierController
- PenerimaanKasController
- Carbon\Carbon
- PenerimaanBarangController
- PengeluaranKasController
- devDependencies
- MutasiController
- JurnalTransaksiController
- Controller
- AdjstockController
- PemakaianbarangController
- Illuminate\Foundation\Testing\RefreshDatabase
- LembarPenagihanController
- ReturPembelianController
- Subaccount
- PenerimaanPembelianHeader
- web.php
- Illuminate\Http\RedirectResponse
- Currency
- Account
- Illuminate\View\View
- LaporanKartuStokController
- Supplier
- LoginRequest
- User
- Release Notes
- Sysuser.php
- scripts
- composer.json
- GoogleDriveService
- SysuserFactory
- ReportingKasController
- require-dev
- ProfileController.php
- require
- BukuHutangController
- BukuPiutangController
- ListingPenjualanHppController
- ListingSOBelumController
- Trkasmt
- ReportingAccountController
- BukuBesarController
- ListingJurnalController
- ReportingPelunasanCustomerController
- ReportingPelunasanSupplierController
- RedirectIfAuthenticated.php
- AnalisaUmurHutangController
- AnalisaUmurPiutangController
- post-create-project-cmd
- ReportingRekapPenjualanController
- config
- psr-4
- PenerimaanPembelianDetail
- ListingFakturPajakPenjualanController
- ListingFakturPembelianController
- ListingHutangDagangController
- README.md
- ListingPenerimaanKasBankController
- AuthenticatedSessionController.php
- ListingPenjualanController
- ListingPiutangPenjualanController
- ListingPOController
- ListingPRController
- ListingReturPembelianController
- ListingReturPenjualanController
- ListingSuratJalanController
- ReportingCustomerController
- ReportingSupplierController
- invoice/create.blade.php
- invoice/edit.blade.php
- suratjalan/create.blade.php
- suratjalan/edit.blade.php
- ReportingController
- ReportingPrController
- AppServiceProvider
- RoleAccess
- profile/edit.blade.php
- salesorder/create.blade.php
- salesorder/edit.blade.php
- Kernel
- EncryptCookies
- VerifyCsrfToken
- pelunasancustomer/_form.blade.php
- reportingpelunasansupplier/index.blade.php
- extra
- ListingMutasiStokController
- graphify.js
- fakturpembelian/create.blade.php
- fakturpembelian/edit.blade.php
- penerimaanbarang/create.blade.php
- penerimaanbarang/edit.blade.php
- reportingpelunasancustomer/index.blade.php
- returpenjualan/create.blade.php
- returpenjualan/edit.blade.php
- adjstock.edit
- bootstrap/app.php
- components.transaction.form-base-styles
- invoice.edit
- mutasi.edit
- bayarsupplier/create.blade.php
- bayarsupplier/delete.blade.php
- bayarsupplier/edit.blade.php
- bayarsupplier/view.blade.php
- jurnaltransaksi/create.blade.php
- jurnaltransaksi/edit.blade.php
- lembarpenagihan/create.blade.php
- lembarpenagihan/delete.blade.php
- lembarpenagihan/edit.blade.php
- lembarpenagihan/_form.blade.php
- lembarpenagihan/view.blade.php
- pelunasancustomer/create.blade.php
- pelunasancustomer/delete.blade.php
- pelunasancustomer/edit.blade.php
- pelunasancustomer/view.blade.php
- penerimaankas/create.blade.php
- penerimaankas/delete.blade.php
- penerimaankas/edit.blade.php
- penerimaankas/view.blade.php
- pengeluarankas/create.blade.php
- pengeluarankas/delete.blade.php
- pengeluarankas/edit.blade.php
- pengeluarankas/_form.blade.php
- pengeluarankas/view.blade.php
- reportingadjstock/index.blade.php
- returpembelian/create.blade.php
- returpembelian/edit.blade.php
- returpembelian/view.blade.php
- returpenjualan/view.blade.php
- tr_poh/create.blade.php
- tr_poh/edit.blade.php
- ReportingProductController
- ReportingPenerimaanBarangController
- ReportingAssemblingTest
- ReportingKasTest
- ReportingPemakaianBarangTest
- NewPasswordController.php
- AGENTS.md

## God Nodes (most connected - your core abstractions)
1. `Controller` - 120 edges
2. `Supplier` - 93 edges
3. `PenerimaanPembelianHeader` - 89 edges
4. `Product` - 81 edges
5. `InvoiceController` - 68 edges
6. `Account` - 49 edges
7. `FakturpembelianController` - 48 edges
8. `ReturPenjualanController` - 45 edges
9. `SalesOrderController` - 45 edges
10. `Customer` - 41 edges

## Surprising Connections (you probably didn't know these)
- `AccountController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/AccountController.php → app/Http/Controllers/Controller.php
- `AdjstockController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/AdjstockController.php → app/Http/Controllers/Controller.php
- `AnalisaUmurHutangController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/AnalisaUmurHutangController.php → app/Http/Controllers/Controller.php
- `AnalisaUmurPiutangController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/AnalisaUmurPiutangController.php → app/Http/Controllers/Controller.php
- `ApprovalController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/ApprovalController.php → app/Http/Controllers/Controller.php

## Import Cycles
- None detected.

## Communities (464 total, 107 thin omitted)

### Community 0 - "Tr_prhController"
Cohesion: 0.06
Nodes (11): format_currency(), format_number(), stock_boleh_minus(), terbilang(), terbilangInteger(), ApprovalController, Carbon, Tr_prhController (+3 more)

### Community 1 - "Illuminate\Database\Eloquent\Factories\HasFactory"
Cohesion: 0.06
Nodes (15): SalesmanController, WhController, ApprovalEmail, ApprovalEmailPo, GenericApprovalNotification, Cabang, Salesman, SalesOrderDetail (+7 more)

### Community 2 - "Sysuser"
Cohesion: 0.20
Nodes (4): SysUserController, Sysuser, Illuminate\Auth\Authenticatable, ListingSoTest

### Community 3 - "InvoiceController"
Cohesion: 0.08
Nodes (3): InvoiceController, Carbon, Tranmt

### Community 4 - "Customer"
Cohesion: 0.09
Nodes (8): CustomerController, GroupcustomerController, RekeningController, WilayahController, Customer, Groupcustomer, Rekening, Wilayah

### Community 5 - "ReturPenjualanController"
Cohesion: 0.08
Nodes (5): Carbon, ReturPenjualanController, ApprovalState, Illuminate\Database\Eloquent\Builder, Illuminate\Database\Query\Builder

### Community 6 - "Product"
Cohesion: 0.07
Nodes (8): GroupproductController, MerekController, ProductController, SatuanController, Groupproduct, Merek, Product, Satuan

### Community 7 - "Illuminate\Http\Request"
Cohesion: 0.09
Nodes (9): LaporanKartuStokController, ListingPenerimaanBarangController, ReportingAdjStockController, ReportingAssemblingController, ReportingFakturPembelianController, ReportingPemakaianBarangController, Authenticate, Illuminate\Auth\Middleware\Authenticate (+1 more)

### Community 8 - "SalesOrderController"
Cohesion: 0.12
Nodes (3): Carbon, SalesOrderController, SalesOrderHeader

### Community 9 - "FakturpembelianController"
Cohesion: 0.12
Nodes (3): FakturpembelianController, Carbon, MessageBag

### Community 11 - "PelunasanCustomerController"
Cohesion: 0.18
Nodes (3): PelunasanCustomerController, Carbon, Illuminate\Support\Collection

### Community 12 - "BayarSupplierController"
Cohesion: 0.17
Nodes (3): BayarSupplierController, Carbon, Trkasdt

### Community 14 - "Carbon\Carbon"
Cohesion: 0.06
Nodes (12): browseProductData(), browseProductMap(), browseProductMapCached(), browseProducts(), resolveBrowseProductDefaultUnit(), JurnalFakturPembelian, JurnalFakturPenjualan, Carbon (+4 more)

### Community 17 - "devDependencies"
Cohesion: 0.06
Nodes (30): alpinejs, autoprefixer, axios, concurrently, laravel-vite-plugin, dependencies, select2, devDependencies (+22 more)

### Community 20 - "Controller"
Cohesion: 0.12
Nodes (3): Controller, Carbon, ProductBrowseController

### Community 23 - "Illuminate\Foundation\Testing\RefreshDatabase"
Cohesion: 0.18
Nodes (6): Illuminate\Foundation\Testing\RefreshDatabase, ListingFakturPembelianTest, ListingPenerimaanBarangTest, ListingPenjualanTest, ListingSoBelumTest, Tests\TestCase

### Community 26 - "Subaccount"
Cohesion: 0.21
Nodes (3): ReportingSubaccountController, SubaccountController, Subaccount

### Community 27 - "PenerimaanPembelianHeader"
Cohesion: 0.19
Nodes (3): AssemblingController, Carbon, PenerimaanPembelianHeader

### Community 28 - "web.php"
Cohesion: 0.12
Nodes (3): EditPeriodeController, ListingPengeluaranKasBankController, ListingSOController

### Community 29 - "Illuminate\Http\RedirectResponse"
Cohesion: 0.15
Nodes (7): ConfirmablePasswordController, EmailVerificationNotificationController, PasswordController, PasswordResetLinkController, VerifyEmailController, Illuminate\Foundation\Auth\EmailVerificationRequest, Illuminate\Http\RedirectResponse

### Community 32 - "Illuminate\View\View"
Cohesion: 0.29
Nodes (5): EmailVerificationPromptController, AppLayout, GuestLayout, Illuminate\View\Component, Illuminate\View\View

### Community 36 - "User"
Cohesion: 0.17
Nodes (6): RegisteredUserController, User, DatabaseSeeder, Illuminate\Database\Seeder, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable

### Community 37 - "Release Notes"
Cohesion: 0.15
Nodes (12): Release Notes, [Unreleased](https://github.com/laravel/laravel/compare/v12.0.9...12.x), [v12.0.0 (2025-??-??)](https://github.com/laravel/laravel/compare/v11.0.2...v12.0.0), [v12.0.1](https://github.com/laravel/laravel/compare/v12.0.0...v12.0.1) - 2025-02-24, [v12.0.2](https://github.com/laravel/laravel/compare/v12.0.1...v12.0.2) - 2025-03-04, [v12.0.3](https://github.com/laravel/laravel/compare/v12.0.2...v12.0.3) - 2025-03-17, [v12.0.4](https://github.com/laravel/laravel/compare/v12.0.3...v12.0.4) - 2025-03-31, [v12.0.5](https://github.com/laravel/laravel/compare/v12.0.4...v12.0.5) - 2025-04-02 (+4 more)

### Community 39 - "scripts"
Cohesion: 0.14
Nodes (14): scripts, dev, post-autoload-dump, post-root-package-install, post-update-cmd, test, Composer\\Config::disableProcessTimeout, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump (+6 more)

### Community 40 - "composer.json"
Cohesion: 0.14
Nodes (13): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+5 more)

### Community 41 - "GoogleDriveService"
Cohesion: 0.23
Nodes (4): GoogleDriveService, Drive, Google\Client, Google\Service\Drive

### Community 42 - "SysuserFactory"
Cohesion: 0.24
Nodes (5): static, SysuserFactory, static, UserFactory, Illuminate\Database\Eloquent\Factories\Factory

### Community 44 - "require-dev"
Cohesion: 0.18
Nodes (11): require-dev, fakerphp/faker, laravel/breeze, laravel/dusk, laravel/pail, laravel/pint, laravel/sail, mockery/mockery (+3 more)

### Community 46 - "require"
Cohesion: 0.20
Nodes (10): require, blade-ui-kit/blade-heroicons, blade-ui-kit/blade-icons, google/apiclient, laravel/framework, laravel/tinker, maatwebsite/excel, openspout/openspout (+2 more)

### Community 57 - "RedirectIfAuthenticated.php"
Cohesion: 0.24
Nodes (6): RedirectIfAuthenticated, SetApplicationLocale, RouteServiceProvider, Closure, Illuminate\Foundation\Support\Providers\RouteServiceProvider, Symfony\Component\HttpFoundation\Response

### Community 60 - "post-create-project-cmd"
Cohesion: 0.50
Nodes (4): post-create-project-cmd, @php artisan key:generate --ansi, @php artisan migrate --graceful --ansi, @php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\

### Community 62 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 63 - "psr-4"
Cohesion: 0.29
Nodes (7): autoload, files, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\, app/Helpers/NumberHelper.php

### Community 68 - "README.md"
Cohesion: 0.22
Nodes (8): About Laravel, Code of Conduct, Contributing, Laravel Sponsors, Learning Laravel, License, Premium Partners, Security Vulnerabilities

### Community 80 - "invoice/create.blade.php"
Cohesion: 0.33
Nodes (5): components.transaction.browse-customer-script, components.transaction.browse-product-script, components.transaction.browse-salesman-script, components.transaction.invoice-so-modal-script, components.transaction.invoice-srj-modal-script

### Community 81 - "invoice/edit.blade.php"
Cohesion: 0.33
Nodes (5): components.transaction.browse-customer-script, components.transaction.browse-product-script, components.transaction.browse-salesman-script, components.transaction.invoice-so-modal-script, components.transaction.invoice-srj-modal-script

### Community 82 - "suratjalan/create.blade.php"
Cohesion: 0.33
Nodes (5): components.transaction.browse-customer-script, components.transaction.browse-product-script, components.transaction.browse-warehouse-script, components.transaction.suratjalan-invoice-modal-script, components.transaction.suratjalan-so-modal-script

### Community 83 - "suratjalan/edit.blade.php"
Cohesion: 0.33
Nodes (5): components.transaction.browse-customer-script, components.transaction.browse-product-script, components.transaction.browse-warehouse-script, components.transaction.suratjalan-invoice-modal-script, components.transaction.suratjalan-so-modal-script

### Community 88 - "profile/edit.blade.php"
Cohesion: 0.50
Nodes (3): profile.partials.delete-user-form, profile.partials.update-password-form, profile.partials.update-profile-information-form

### Community 89 - "salesorder/create.blade.php"
Cohesion: 0.50
Nodes (3): components.transaction.browse-customer-script, components.transaction.browse-product-script, components.transaction.browse-salesman-script

### Community 90 - "salesorder/edit.blade.php"
Cohesion: 0.50
Nodes (3): components.transaction.browse-customer-script, components.transaction.browse-product-script, components.transaction.browse-salesman-script

### Community 96 - "extra"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

## Knowledge Gaps
- **167 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+162 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **107 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Controller` connect `Controller` to `Tr_prhController`, `Illuminate\Database\Eloquent\Factories\HasFactory`, `Sysuser`, `InvoiceController`, `Customer`, `ReturPenjualanController`, `Product`, `Illuminate\Http\Request`, `SalesOrderController`, `FakturpembelianController`, `SuratJalanController`, `PelunasanCustomerController`, `BayarSupplierController`, `PenerimaanKasController`, `Carbon\Carbon`, `PenerimaanBarangController`, `PengeluaranKasController`, `MutasiController`, `JurnalTransaksiController`, `AdjstockController`, `PemakaianbarangController`, `LembarPenagihanController`, `ReturPembelianController`, `Subaccount`, `PenerimaanPembelianHeader`, `web.php`, `Illuminate\Http\RedirectResponse`, `Currency`, `Account`, `Illuminate\View\View`, `LaporanKartuStokController`, `Supplier`, `User`, `ReportingKasController`, `ProfileController.php`, `BukuHutangController`, `BukuPiutangController`, `ListingPenjualanHppController`, `ListingSOBelumController`, `ReportingAccountController`, `BukuBesarController`, `ListingJurnalController`, `ReportingPelunasanCustomerController`, `ReportingPelunasanSupplierController`, `AnalisaUmurHutangController`, `AnalisaUmurPiutangController`, `ReportingRekapPenjualanController`, `ListingFakturPajakPenjualanController`, `ListingFakturPembelianController`, `ListingHutangDagangController`, `ListingPenerimaanKasBankController`, `AuthenticatedSessionController.php`, `ListingPenjualanController`, `ListingPiutangPenjualanController`, `ListingPOController`, `ListingPRController`, `ListingReturPembelianController`, `ListingReturPenjualanController`, `ListingSuratJalanController`, `NewPasswordController.php`, `ReportingCustomerController`, `ReportingPenerimaanBarangController`, `ReportingProductController`, `ReportingSupplierController`, `ReportingController`, `ReportingPrController`, `RoleAccess`, `ListingMutasiStokController`?**
  _High betweenness centrality (0.117) - this node is a cross-community bridge._
- **Why does `InvoiceController` connect `InvoiceController` to `Illuminate\Database\Eloquent\Factories\HasFactory`, `Controller`, `Carbon\Carbon`?**
  _High betweenness centrality (0.031) - this node is a cross-community bridge._
- **Why does `Supplier` connect `Supplier` to `Tr_prhController`, `Illuminate\Database\Eloquent\Factories\HasFactory`, `Illuminate\Http\Request`, `FakturpembelianController`, `BayarSupplierController`, `PenerimaanKasController`, `Carbon\Carbon`, `PenerimaanBarangController`, `MutasiController`, `JurnalTransaksiController`, `AdjstockController`, `PemakaianbarangController`, `ReturPembelianController`, `PenerimaanPembelianHeader`, `web.php`, `Trkasmt`, `ReportingPenerimaanBarangController`, `ReportingController`, `ReportingPrController`?**
  _High betweenness centrality (0.026) - this node is a cross-community bridge._
- **Are the 5 inferred relationships involving `PenerimaanPembelianHeader` (e.g. with `.getAdjStockQuery()` and `.getAssemblingQuery()`) actually correct?**
  _`PenerimaanPembelianHeader` has 5 INFERRED edges - model-reasoned connections that need verification._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _167 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Tr_prhController` be split into smaller, more focused modules?**
  _Cohesion score 0.061668289516390785 - nodes in this community are weakly interconnected._
- **Should `Illuminate\Database\Eloquent\Factories\HasFactory` be split into smaller, more focused modules?**
  _Cohesion score 0.05877167205406994 - nodes in this community are weakly interconnected._