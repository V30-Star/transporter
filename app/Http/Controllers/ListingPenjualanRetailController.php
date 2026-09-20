<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ListingPenjualanRetailController extends ListingPenjualanController
{
    protected string $viewPrefix = 'listingpenjualanretail';
    protected string $reportTitle = 'LISTING PENJUALAN RETAIL';
    protected string $exportFilenamePrefix = 'listing_penjualan_retail_';

    protected function buildQuery(Request $request)
    {
        $query = parent::buildQuery($request);

        return $query->where('m.ftrcode', 'INV')
            ->whereNotNull('m.fwhcode')
            ->where('m.fwhcode', '!=', '');
    }
}
