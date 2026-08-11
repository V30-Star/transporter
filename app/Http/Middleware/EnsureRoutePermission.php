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
        'wilayah' => 'Wilayah',
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
            return ['editPeriode'];
        }

        if ($module === 'roleaccess') {
            return ['roleaccess'];
        }

        if (str_starts_with($module, 'listing') || str_starts_with($module, 'reporting')) {
            return ['view' . $module];
        }

        $suffix = self::MODULES[$module] ?? null;
        if ($suffix === null) {
            return [];
        }

        if ($action === 'print') {
            return ['print' . $suffix, 'view' . $suffix];
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
