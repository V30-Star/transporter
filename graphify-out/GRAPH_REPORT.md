# Graph Report - .  (2026-07-29)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 2142 nodes · 4908 edges · 456 communities (358 shown, 98 thin omitted)
- Extraction: 96% EXTRACTED · 4% INFERRED · 0% AMBIGUOUS · INFERRED: 202 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `c77399f7`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Community 0
- Community 1
- Community 2
- Community 3
- Community 4
- Community 5
- Community 6
- Community 7
- Community 8
- Community 9
- Community 10
- Community 11
- Community 12
- Community 13
- Community 14
- Community 15
- Community 16
- Community 17
- Community 18
- Community 19
- Community 20
- Community 21
- Community 22
- Community 23
- Community 24
- Community 25
- Community 26
- Community 27
- Community 29
- Community 30
- Community 31
- Community 32
- Community 33
- Community 34
- Community 35
- Community 36
- Community 37
- Community 38
- Community 39
- Community 40
- Community 41
- Community 42
- Community 43
- Community 44
- Community 45
- Community 46
- Community 47
- Community 48
- Community 49
- Community 50
- Community 51
- Community 52
- Community 53
- Community 54
- Community 55
- Community 56
- Community 57
- Community 58
- Community 59
- Community 60
- Community 61
- Community 62
- Community 63
- Community 64
- Community 65
- Community 66
- Community 67
- Community 68
- Community 69
- Community 70
- Community 71
- Community 72
- Community 73
- Community 74
- Community 75
- Community 76
- Community 77
- Community 78
- Community 79
- Community 80
- Community 81
- Community 82
- Community 83
- Community 84
- Community 85
- Community 86
- Community 87
- Community 88
- Community 89
- Community 90
- Community 91
- Community 92
- Community 93
- Community 94
- Community 95
- Community 96
- Community 103
- Community 104
- Community 105
- Community 106
- Community 107
- Community 108
- Community 109
- Community 110
- Community 111
- Community 113
- Community 114
- Community 115
- Community 116
- Community 118
- Community 119
- Community 120
- Community 121
- Community 122
- Community 123
- Community 124
- Community 125
- Community 126
- Community 127
- Community 128
- Community 129
- Community 130
- Community 131
- Community 132
- Community 133
- Community 134
- Community 135
- Community 136
- Community 137
- Community 138
- Community 139
- Community 140
- Community 141
- Community 142
- Community 143
- Community 144
- Community 145
- Community 146
- Community 147
- Community 148

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

## Communities (456 total, 98 thin omitted)

### Community 0 - "Community 0"
Cohesion: 0.06
Nodes (11): format_currency(), format_number(), stock_boleh_minus(), terbilang(), terbilangInteger(), ApprovalController, Carbon, Tr_prhController (+3 more)

### Community 1 - "Community 1"
Cohesion: 0.06
Nodes (14): CurrencyController, RoleAccessController, SatuanController, WhController, Cabang, Currency, LogUser, RoleAccess (+6 more)

### Community 2 - "Community 2"
Cohesion: 0.06
Nodes (17): SalesmanController, SysUserController, Salesman, Sysuser, Illuminate\Auth\Authenticatable, Illuminate\Contracts\Auth\Authenticatable, Illuminate\Foundation\Testing\RefreshDatabase, ListingFakturPembelianTest (+9 more)

### Community 4 - "Community 4"
Cohesion: 0.09
Nodes (8): CustomerController, GroupcustomerController, RekeningController, WilayahController, Customer, Groupcustomer, Rekening, Wilayah

### Community 5 - "Community 5"
Cohesion: 0.08
Nodes (5): Carbon, ReturPenjualanController, ApprovalState, Illuminate\Database\Eloquent\Builder, Illuminate\Database\Query\Builder

### Community 6 - "Community 6"
Cohesion: 0.09
Nodes (5): GroupproductController, MerekController, ProductController, Groupproduct, Merek

### Community 7 - "Community 7"
Cohesion: 0.07
Nodes (11): EditPeriodeController, ListingMutasiStokController, ListingSOController, ProductBrowseController, ReportingAdjStockController, ReportingAssemblingController, ReportingFakturPembelianController, ReportingPemakaianBarangController (+3 more)

### Community 8 - "Community 8"
Cohesion: 0.12
Nodes (3): Carbon, SalesOrderController, SalesOrderHeader

### Community 9 - "Community 9"
Cohesion: 0.12
Nodes (3): FakturpembelianController, Carbon, MessageBag

### Community 11 - "Community 11"
Cohesion: 0.16
Nodes (3): PelunasanCustomerController, Carbon, Tranmt

### Community 12 - "Community 12"
Cohesion: 0.16
Nodes (4): BayarSupplierController, Carbon, Trkasdt, Illuminate\Support\Collection

### Community 14 - "Community 14"
Cohesion: 0.16
Nodes (3): Carbon, Tr_pohController, Tr_poh

### Community 17 - "Community 17"
Cohesion: 0.06
Nodes (30): alpinejs, autoprefixer, axios, concurrently, laravel-vite-plugin, dependencies, select2, devDependencies (+22 more)

### Community 20 - "Community 20"
Cohesion: 0.13
Nodes (3): Controller, Carbon, Builder

### Community 22 - "Community 22"
Cohesion: 0.21
Nodes (3): PemakaianbarangController, Carbon, PenerimaanPembelianHeader

### Community 23 - "Community 23"
Cohesion: 0.18
Nodes (6): browseProductData(), browseProductMap(), browseProductMapCached(), browseProducts(), resolveBrowseProductDefaultUnit(), Product

### Community 26 - "Community 26"
Cohesion: 0.19
Nodes (3): ReportingSubaccountController, SubaccountController, Subaccount

### Community 29 - "Community 29"
Cohesion: 0.16
Nodes (7): ConfirmablePasswordController, EmailVerificationNotificationController, EmailVerificationPromptController, PasswordController, VerifyEmailController, Illuminate\Foundation\Auth\EmailVerificationRequest, Illuminate\Http\RedirectResponse

### Community 30 - "Community 30"
Cohesion: 0.15
Nodes (3): JurnalFakturPembelian, JurnalFakturPenjualan, Carbon\Carbon

### Community 32 - "Community 32"
Cohesion: 0.18
Nodes (6): NewPasswordController, PasswordResetLinkController, AppLayout, GuestLayout, Illuminate\View\Component, Illuminate\View\View

### Community 35 - "Community 35"
Cohesion: 0.17
Nodes (4): AuthenticatedSessionController, LoginRequest, RouteServiceProvider, Illuminate\Foundation\Support\Providers\RouteServiceProvider

### Community 36 - "Community 36"
Cohesion: 0.17
Nodes (6): RegisteredUserController, User, DatabaseSeeder, Illuminate\Database\Seeder, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable

### Community 38 - "Community 38"
Cohesion: 0.26
Nodes (6): ApprovalEmail, ApprovalEmailPo, GenericApprovalNotification, Illuminate\Bus\Queueable, Illuminate\Mail\Mailable, Illuminate\Queue\SerializesModels

### Community 39 - "Community 39"
Cohesion: 0.14
Nodes (14): scripts, dev, post-autoload-dump, post-root-package-install, post-update-cmd, test, Composer\\Config::disableProcessTimeout, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump (+6 more)

### Community 40 - "Community 40"
Cohesion: 0.14
Nodes (13): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+5 more)

### Community 41 - "Community 41"
Cohesion: 0.23
Nodes (4): GoogleDriveService, Drive, Google\Client, Google\Service\Drive

### Community 42 - "Community 42"
Cohesion: 0.24
Nodes (5): static, SysuserFactory, static, UserFactory, Illuminate\Database\Eloquent\Factories\Factory

### Community 44 - "Community 44"
Cohesion: 0.18
Nodes (11): require-dev, fakerphp/faker, laravel/breeze, laravel/dusk, laravel/pail, laravel/pint, laravel/sail, mockery/mockery (+3 more)

### Community 45 - "Community 45"
Cohesion: 0.24
Nodes (3): ProfileController, ProfileUpdateRequest, Illuminate\Foundation\Http\FormRequest

### Community 46 - "Community 46"
Cohesion: 0.20
Nodes (10): require, blade-ui-kit/blade-heroicons, blade-ui-kit/blade-icons, google/apiclient, laravel/framework, laravel/tinker, maatwebsite/excel, openspout/openspout (+2 more)

### Community 57 - "Community 57"
Cohesion: 0.43
Nodes (4): RedirectIfAuthenticated, SetApplicationLocale, Closure, Symfony\Component\HttpFoundation\Response

### Community 60 - "Community 60"
Cohesion: 0.50
Nodes (4): post-create-project-cmd, @php artisan key:generate --ansi, @php artisan migrate --graceful --ansi, @php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\

### Community 62 - "Community 62"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 63 - "Community 63"
Cohesion: 0.29
Nodes (7): autoload, files, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\, app/Helpers/NumberHelper.php

### Community 80 - "Community 80"
Cohesion: 0.33
Nodes (5): components.transaction.browse-customer-script, components.transaction.browse-product-script, components.transaction.browse-salesman-script, components.transaction.invoice-so-modal-script, components.transaction.invoice-srj-modal-script

### Community 81 - "Community 81"
Cohesion: 0.33
Nodes (5): components.transaction.browse-customer-script, components.transaction.browse-product-script, components.transaction.browse-salesman-script, components.transaction.invoice-so-modal-script, components.transaction.invoice-srj-modal-script

### Community 82 - "Community 82"
Cohesion: 0.33
Nodes (5): components.transaction.browse-customer-script, components.transaction.browse-product-script, components.transaction.browse-warehouse-script, components.transaction.suratjalan-invoice-modal-script, components.transaction.suratjalan-so-modal-script

### Community 83 - "Community 83"
Cohesion: 0.33
Nodes (5): components.transaction.browse-customer-script, components.transaction.browse-product-script, components.transaction.browse-warehouse-script, components.transaction.suratjalan-invoice-modal-script, components.transaction.suratjalan-so-modal-script

### Community 88 - "Community 88"
Cohesion: 0.50
Nodes (3): profile.partials.delete-user-form, profile.partials.update-password-form, profile.partials.update-profile-information-form

### Community 89 - "Community 89"
Cohesion: 0.50
Nodes (3): components.transaction.browse-customer-script, components.transaction.browse-product-script, components.transaction.browse-salesman-script

### Community 90 - "Community 90"
Cohesion: 0.50
Nodes (3): components.transaction.browse-customer-script, components.transaction.browse-product-script, components.transaction.browse-salesman-script

### Community 96 - "Community 96"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

## Knowledge Gaps
- **148 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+143 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **98 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Controller` connect `Community 20` to `Community 0`, `Community 1`, `Community 2`, `Community 3`, `Community 4`, `Community 5`, `Community 6`, `Community 7`, `Community 8`, `Community 9`, `Community 10`, `Community 11`, `Community 12`, `Community 13`, `Community 14`, `Community 15`, `Community 16`, `Community 18`, `Community 19`, `Community 21`, `Community 22`, `Community 24`, `Community 25`, `Community 26`, `Community 27`, `Community 29`, `Community 31`, `Community 32`, `Community 33`, `Community 34`, `Community 35`, `Community 36`, `Community 37`, `Community 43`, `Community 45`, `Community 47`, `Community 48`, `Community 49`, `Community 50`, `Community 52`, `Community 53`, `Community 54`, `Community 55`, `Community 56`, `Community 58`, `Community 59`, `Community 61`, `Community 65`, `Community 66`, `Community 67`, `Community 68`, `Community 69`, `Community 70`, `Community 71`, `Community 72`, `Community 73`, `Community 74`, `Community 75`, `Community 76`, `Community 77`, `Community 78`, `Community 79`, `Community 84`, `Community 85`?**
  _High betweenness centrality (0.118) - this node is a cross-community bridge._
- **Why does `InvoiceController` connect `Community 3` to `Community 28`, `Community 20`, `Community 30`?**
  _High betweenness centrality (0.032) - this node is a cross-community bridge._
- **Why does `Supplier` connect `Community 34` to `Community 0`, `Community 1`, `Community 7`, `Community 9`, `Community 12`, `Community 13`, `Community 14`, `Community 15`, `Community 18`, `Community 19`, `Community 21`, `Community 22`, `Community 23`, `Community 25`, `Community 27`, `Community 28`, `Community 51`, `Community 84`, `Community 85`?**
  _High betweenness centrality (0.027) - this node is a cross-community bridge._
- **Are the 5 inferred relationships involving `PenerimaanPembelianHeader` (e.g. with `.getAdjStockQuery()` and `.getAssemblingQuery()`) actually correct?**
  _`PenerimaanPembelianHeader` has 5 INFERRED edges - model-reasoned connections that need verification._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _148 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Community 0` be split into smaller, more focused modules?**
  _Cohesion score 0.06044303797468355 - nodes in this community are weakly interconnected._
- **Should `Community 1` be split into smaller, more focused modules?**
  _Cohesion score 0.060939060939060936 - nodes in this community are weakly interconnected._