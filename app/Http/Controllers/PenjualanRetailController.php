<?php

namespace App\Http\Controllers;

class PenjualanRetailController extends InvoiceController
{
    protected string $routeNamePrefix = 'penjualanretail';
    protected string $viewNamePrefix = 'penjualanretail';
    protected string $permissionPrefix = 'PenjualanRetail';
    protected string $moduleTitle = 'Penjualan Retail';
}
