<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportingProductController extends Controller
{
  public function index()
  {
    $products = DB::table('msprd')->orderBy('fprdcode', 'asc')->get();
    $groups = DB::table('msprd')->select('fgroupcode')->distinct()->whereNotNull('fgroupcode')->get();
    $mereks = DB::table('msprd')->select('fmerek')->distinct()->whereNotNull('fmerek')->get();
    // Ambil data gudang
    $warehouses = DB::table('mswh')->where('fnonactive', '0')->orderBy('fwhname', 'asc')->get();

    return view('reportingproduct.index', compact('products', 'groups', 'mereks', 'warehouses'));
  }

  public function print(Request $request)
  {
    $query = DB::table('msprd');

    // Filter Dasar
    if ($request->prd_from && $request->prd_to) {
      $query->whereBetween('fprdcode', [$request->prd_from, $request->prd_to]);
    }
    if ($request->group) $query->where('fgroupcode', $request->group);
    if ($request->merek) $query->where('fmerek', $request->merek);

    // Filter Stok (Only Stock)
    if ($request->has('only_stock')) {
      $query->whereRaw("CAST(COALESCE(fstock, '0') AS FLOAT) > 0");
    }

    $orderBy = $request->sort_by == 'name' ? 'fprdname' : 'fprdcode';
    $data = $query->orderBy($orderBy, 'asc')->get();

    // Kirim pilihan kolom harga ke view agar bisa di-hide/show
    $showCols = [
      'hpp' => $request->has('show_hpp'),
      'price1' => $request->has('show_price1'),
      'price2' => $request->has('show_price2'),
      'price3' => $request->has('show_price3'),
    ];

    $warehouseName = $request->warehouse ? DB::table('mswh')->where('fwhcode', $request->warehouse)->value('fwhname') : 'Semua Gudang';

    return view('reportingproduct.print', compact('data', 'showCols', 'warehouseName'));
  }
}
