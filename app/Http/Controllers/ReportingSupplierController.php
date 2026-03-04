<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Supplier; // Pastikan Model mssupplier sudah dibuat
use Carbon\Carbon;

class ReportingSupplierController extends Controller
{
  public function index()
  {
    // Mengambil data supplier untuk dropdown filter
    $suppliers = DB::table('public.mssupplier')
      ->orderBy('fsuppliercode', 'asc')
      ->get();

    return view('reportingsupplier.index', compact('suppliers'));
  }

  public function print(Request $request)
  {
    $query = DB::table('public.mssupplier');

    // Filter Rentang Kode Supplier (From - To)
    if ($request->supplier_from && $request->supplier_to) {
      $query->whereBetween('fsuppliercode', [$request->supplier_from, $request->supplier_to]);
    }

    $data = $query->orderBy('fsuppliercode', 'asc')->get();
    $printDate = Carbon::now()->format('d/m/Y H:i');

    return view('reportingsupplier.print', compact('data', 'printDate'));
  }
}
