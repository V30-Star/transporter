<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductBrowseController extends Controller
{
  public function index(Request $request)
  {
    $q = trim($request->get('q', ''));
    $perPage = (int) $request->get('per_page', 10);
    $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;

    $builder = DB::table('msproduct')
      ->select([
        'fproductcode',
        'fproductname',
        'fsatuankecil',
        'fsatuanbesar',
        'fsatuanbesar2',
        DB::raw("
            CASE 
              WHEN fminstock ~ '^[0-9]+(\\.[0-9]+)?$' 
                THEN (fminstock)::double precision
              ELSE 0::double precision
            END AS fminstock
        "),
      ]);

    if ($q !== '') {
      $builder->where(function ($w) use ($q) {
        $w->where('fproductcode', 'like', "%{$q}%")
          ->orWhere('fproductname', 'like', "%{$q}%");
      });
    }

    $data = $builder->orderBy('fproductname')->paginate($perPage);

    return response()->json([
      'data' => $data->items(),
      'current_page' => $data->currentPage(),
      'last_page' => $data->lastPage(),
      'per_page' => $data->perPage(),
      'total' => $data->total(),
    ]);
  }
}
