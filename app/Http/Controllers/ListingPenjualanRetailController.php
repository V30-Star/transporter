<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListingPenjualanRetailController extends ListingPenjualanController
{
    protected string $viewPrefix = 'listingpenjualanretail';
    protected string $reportTitle = 'LISTING PENJUALAN RETAIL';
    protected string $exportFilenamePrefix = 'listing_penjualan_retail_';

    public function index()
    {
        $branches = DB::table('mscabang')->orderBy('fcabangkode')->get();
        $isAuthorized = $this->canAccessAllBranches();
        $userBranchCode = $this->getCurrentBranchCode();
        $typePembayarans = DB::table('tbmaster')
            ->where('ftblcode', 'TYPEBAYAR')
            ->orderBy('fmasternum')
            ->orderBy('fmastername')
            ->pluck('fmastername');

        return view($this->viewPrefix . '.index', compact('branches', 'isAuthorized', 'userBranchCode', 'typePembayarans'));
    }

    protected function buildQuery(Request $request)
    {
        $query = parent::buildQuery($request);

        return $query->where('m.ftrcode', 'INV')
            ->whereNotNull('m.fwhcode')
            ->where('m.fwhcode', '!=', '');
    }
}
