<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoutePermission
{
    private const MODULES = [
        'account' => 'Account',
        'adjstock' => 'Adjstock',
        'assembling' => 'Assembling',
        'bayarsupplier' => 'BayarSupplier',
        'currency' => 'Currency',
        'customer' => 'Customer',
        'dealer' => 'Dealer',
        'fakturpembelian' => 'FakturPembelian',
        'groupcustomer' => 'GroupCustomer',
        'groupproduct' => 'GroupProduct',
        'gudang' => 'Gudang',
        'invoice' => 'Invoice',
        'jurnalpembelian' => 'jurnaltransaksi',
        'jurnaltransaksi' => 'jurnaltransaksi',
        'lembarpenagihan' => 'LembarPenagihan',
        'merek' => 'Merek',
        'mutasi' => 'Mutasi',
        'pemakaianbarang' => 'Pemakaianbarang',
        'penerimaanbarang' => 'PenerimaanBarang',
        'penerimaankas' => 'PenerimaanKas',
        'pengeluarankas' => 'PengeluaranKas',
        'pelunasancustomer' => 'PelunasanCustomer',
        'penjualanretail' => 'PenjualanRetail',
        'product' => 'Product',
        'rekening' => 'Rekening',
        'returpembelian' => 'ReturPembelian',
        'returpenjualan' => 'ReturPenjualan',
        'roleaccess' => 'roleaccess',
        'salesman' => 'Salesman',
        'salesorder' => 'SalesOrder',
        'satuan' => 'Satuan',
        'subaccount' => 'SubAccount',
        'supplier' => 'Supplier',
        'suratjalan' => 'SuratJalan',
        'sysuser' => 'Sysuser',
        'tr_poh' => 'Tr_poh',
        'tr_prh' => 'Tr_prh',
        'typepembayaran' => 'TypePembayaran',
        'wilayah' => 'Wilayah',
    ];

    private const REPORT_PRINTS = [
        'listingpenjualan' => 'printListingPenjualan',
        'listingpenjualanhpp' => 'printListingPenjualanHpp',
        'listingpenjualanretail' => 'printListingPenjualanRetail',
        'listingpiutangpenjualan' => 'printListingPiutangPenjualan',
        'reportingrekappenjualan' => 'printLaporanRekapPenjualan',
        'reportingrekappenjualancustomerproduk' => 'printLaporanRekapPenjualan',
        'reportingrekappenjualansalescustomer' => 'printLaporanRekapPenjualan',
        'reportingrekappenjualansalesproduk' => 'printLaporanRekapPenjualan',
        'reportingpenjualandp' => 'printLaporanRekapPenjualan',
        'analisaumurpiutang' => 'printAnalisaUmurPiutang',
        'bukupiutang' => 'printBukuPiutang',
        'listingfakturpajakpenjualan' => 'printListingFakturPajakPenjualan',
        'listingreturpenjualan' => 'printListingReturPenjualan',
        'listingpr' => 'printListingPermintaanPembelian',
        'listingpo' => 'printListingOrderPembelian',
        'listingpenerimaanbarang' => 'printListingPenerimaanBarang',
        'listingfakturpembelian' => 'printListingFakturPembelian',
        'listingreturpembelian' => 'printListingReturPembelian',
        'listinghutangdagang' => 'printListingHutangDagang',
        'analisaumurhutang' => 'printAnalisaUmurHutang',
        'bukuhutang' => 'printBukuHutang',
        'reportingadjstock' => 'printListingAdjustmentStok',
        'listingmutasistok' => 'printListingMutasiStok',
        'laporankartustok' => 'printLaporanKartuStok',
        'stokdalamrupiah' => 'printStokDalamRupiah',
        'reportingpemakaianbarang' => 'printListingPemakaianBarang',
        'reportingassembling' => 'printListingAssembling',
        'listingsuratjalan' => 'printListingSuratJalan',
        'reportingpelunasancustomer' => 'printLaporanPelunasanCustomer',
        'listingpenerimaankasbank' => 'printListingPenerimaanKasBank',
        'listingpengeluarankasbank' => 'printListingPengeluaranKasBank',
        'laporanuangkasir' => 'printLaporanUangKasir',
        'reportingpelunasansupplier' => 'printLaporanBayarSupplier',
        'listingjurnal' => 'printListingJurnalTransaksi',
        'bukubesar' => 'printBukuBesar',
        'trialbalance' => 'printTrialBalance',
        'reportingaccount' => 'printChartOfAccount',
        'reportingsubaccount' => 'printSubAccount',
        'reportingcustomer' => 'printLaporanCustomer',
        'reportingsupplier' => 'printLaporanSupplier',
        'reportingproduct' => 'printLaporanProduk',
        'listingso' => 'printListingSalesOrder',
        'listingsobelum' => 'printSoBelumTerkirim',
    ];

    private const ACTIONS = [
        'index' => 'view',
        'view' => 'view',
        'show' => 'view',
        'create' => 'create',
        'store' => 'create',
        'edit' => 'update',
        'update' => 'update',
        'delete' => 'delete',
        'destroy' => 'delete',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $required = $this->requiredPermissions($request->route()?->getName(), $request->method());
        if ($required === []) {
            return $next($request);
        }

        $rawPermissions = session('user_restricted_permissions');
        if ($rawPermissions === null) {
            return $next($request);
        }

        $permissions = array_filter(array_map('trim', explode(',', (string) $rawPermissions)));
        foreach ($required as $permission) {
            if (in_array($permission, $permissions, true)) {
                return $next($request);
            }
        }

        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }

    private function requiredPermissions(?string $routeName, string $method): array
    {
        if (! is_string($routeName) || ! str_contains($routeName, '.')) {
            return [];
        }

        [$module, $action] = explode('.', $routeName, 2);

        if ($module === 'editperiode') {
            return ['editPeriode', 'roleaccess', 'viewEditperiode', 'createEditperiode', 'updateEditperiode', 'deleteEditperiode'];
        }

        if ($module === 'systemsetting') {
            return ['systemSetting', 'editSystemSetting', 'roleaccess'];
        }

        if ($module === 'penamaanperusahaan') {
            return ['penamaanPerusahaan', 'editPenamaanPerusahaan', 'roleaccess'];
        }

        if ($module === 'roleaccess') {
            return ['roleaccess'];
        }

        if ($module === 'loguser') {
            return ['viewSysuser', 'createSysuser', 'updateSysuser', 'deleteSysuser', 'roleaccess'];
        }

        if ($module === 'dashboardwewenang') {
            return ['roleaccess', 'viewSysuser'];
        }

        if ($module === 'sysuser') {
            $permissionAction = self::ACTIONS[$action] ?? $this->fallbackAction($method);
            if ($action === 'index') {
                return ['viewSysuser', 'createSysuser', 'updateSysuser', 'deleteSysuser', 'roleaccess'];
            }
            return [$permissionAction . 'Sysuser', 'roleaccess'];
        }

        if ($module === 'pelunasancustomer') {
            $permissionAction = self::ACTIONS[$action] ?? $this->fallbackAction($method);
            if ($action === 'index') {
                return [
                    'viewPelunasanCustomer', 'createPelunasanCustomer', 'updatePelunasanCustomer', 'deletePelunasanCustomer',
                    'viewPenerimaanKas', 'createPenerimaanKas', 'updatePenerimaanKas', 'deletePenerimaanKas',
                ];
            }
            return [
                $permissionAction . 'PelunasanCustomer',
                $permissionAction . 'PenerimaanKas',
            ];
        }

        if ($module === 'bayarsupplier') {
            $permissionAction = self::ACTIONS[$action] ?? $this->fallbackAction($method);
            if ($action === 'index') {
                return [
                    'viewBayarSupplier', 'createBayarSupplier', 'updateBayarSupplier', 'deleteBayarSupplier',
                    'viewPenerimaanKas', 'createPenerimaanKas', 'updatePenerimaanKas', 'deletePenerimaanKas',
                ];
            }
            return [
                $permissionAction . 'BayarSupplier',
                $permissionAction . 'PenerimaanKas',
            ];
        }

        if (isset(self::REPORT_PRINTS[$module])) {
            return [self::REPORT_PRINTS[$module]];
        }

        if (str_starts_with($module, 'listing') || str_starts_with($module, 'reporting')) {
            return ['view' . $module];
        }

        if ($module === 'pemakaianbarang') {
            $permissionAction = self::ACTIONS[$action] ?? $this->fallbackAction($method);
            if ($action === 'index') {
                return [
                    'viewPemakaianbarang', 'viewPemakaianBarang',
                    'createPemakaianbarang', 'createPemakaianBarang',
                    'updatePemakaianbarang', 'updatePemakaianBarang',
                    'deletePemakaianbarang', 'deletePemakaianBarang',
                ];
            }
            return [
                $permissionAction . 'Pemakaianbarang',
                $permissionAction . 'PemakaianBarang',
            ];
        }

        if ($module === 'adjstock') {
            $permissionAction = self::ACTIONS[$action] ?? $this->fallbackAction($method);
            if ($action === 'index') {
                return [
                    'viewAdjstock', 'createAdjstock', 'updateAdjstock', 'deleteAdjstock',
                    'viewPenerimaanBarang', 'createPenerimaanBarang', 'updatePenerimaanBarang', 'deletePenerimaanBarang',
                ];
            }
            return [
                $permissionAction . 'Adjstock',
                $permissionAction . 'PenerimaanBarang',
            ];
        }

        if ($module === 'mutasi') {
            $permissionAction = self::ACTIONS[$action] ?? $this->fallbackAction($method);
            if ($action === 'index') {
                return [
                    'viewMutasi', 'createMutasi', 'updateMutasi', 'deleteMutasi',
                    'viewPenerimaanBarang', 'createPenerimaanBarang', 'updatePenerimaanBarang', 'deletePenerimaanBarang',
                ];
            }
            return [
                $permissionAction . 'Mutasi',
                $permissionAction . 'PenerimaanBarang',
            ];
        }

        $suffix = self::MODULES[$module] ?? null;
        if ($suffix === null) {
            return [];
        }

        if ($action === 'print') {
            return ['print' . $suffix, 'view' . $suffix];
        }

        if ($action === 'index') {
            return [
                'view' . $suffix,
                'create' . $suffix,
                'update' . $suffix,
                'delete' . $suffix,
            ];
        }

        $permissionAction = self::ACTIONS[$action] ?? null;
        return [($permissionAction ?? $this->fallbackAction($method)) . $suffix];
    }

    private function fallbackAction(string $method): string
    {
        return match (strtoupper($method)) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'view',
        };
    }
}
