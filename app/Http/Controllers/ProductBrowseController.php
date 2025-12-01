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

    $builder = DB::table('msprd')
      ->leftJoin('msmerek', 'msprd.fmerek', '=', 'msmerek.fmerekid')
      ->select([
        'msprd.fprdcode',
        'msprd.fprdname',
        'msprd.fmerek',
        'msmerek.fmerekname', // Ambil nama merek
        'msprd.fsatuankecil',
        'msprd.fsatuanbesar',
        'msprd.fsatuanbesar2',
        DB::raw("
                CASE 
                    WHEN msprd.fminstock ~ '^[0-9]+(\\.[0-9]+)?$' 
                        THEN (msprd.fminstock)::double precision
                    ELSE 0::double precision
                END AS fminstock
            "),
      ]);

    if ($q !== '') {
      $builder->where(function ($w) use ($q) {
        $w->where('msprd.fprdcode', 'like', "%{$q}%")
          ->orWhere('msprd.fprdname', 'like', "%{$q}%");
      });
    }

    $data = $builder->orderBy('msprd.fprdname')->paginate($perPage);

    return response()->json([
      'data' => $data->items(),
      'current_page' => $data->currentPage(),
      'last_page' => $data->lastPage(),
      'per_page' => $data->perPage(),
      'total' => $data->total(),
    ]);
  }
}
