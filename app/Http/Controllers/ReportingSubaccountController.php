<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subaccount;
use Carbon\Carbon;

class ReportingSubaccountController extends Controller
{
  public function index()
  {
    // Mengambil semua sub account untuk dropdown di modal
    $subAccounts = Subaccount::orderBy('fsubaccountcode', 'asc')->get();
    return view('reportingsubaccount.index', compact('subAccounts'));
  }

  public function print(Request $request)
  {
    $query = Subaccount::query();

    // Filter Rentang Kode (From - To)
    if ($request->subaccount_from && $request->subaccount_to) {
      $query->whereBetween('fsubaccountcode', [$request->subaccount_from, $request->subaccount_to]);
    }

    $data = $query->orderBy('fsubaccountcode', 'asc')->get();
    $printDate = Carbon::now()->format('d/m/Y H:i');

    return view('reportingsubaccount.print', compact('data', 'printDate'));
  }
}
